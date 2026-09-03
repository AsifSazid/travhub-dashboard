<?php
/**
 * FILE PATH: api/travelers/traveler-groups.php
 *
 * Named travel group (e.g. "Dubai Trip 2026") — many-to-many membership।
 *
 * GET    ?traveler_id=...                 → এই traveler যেসব group-এর সদস্য
 * GET    ?action=members&group_id=...     → একটা group-এর সব সদস্য
 * POST   { action:'create_group', group_name, description }
 * POST   { action:'add_member', group_id, traveler_id }
 * POST   { action:'remove_member', group_id, traveler_id }
 * DELETE ?group_id=...                    → পুরো group মুছে দাও (সব membership সহ)
 *
 * ⚠️ কেউ group-এ join করলে (create_group এ প্রথম সদস্য, বা add_member),
 * addGroupMember() স্বয়ংক্রিয়ভাবে সেই group-এর প্রতিটা existing member-এর
 * সাথে একটা traveler_links row (relation_type='group_member') বানিয়ে দেয় —
 * যাতে Family Links section-এই তাদের bidirectional link দেখা যায়। এই দুটো
 * (group membership vs family-link row) একে অপর থেকে independent —
 * traveler_links থেকে unlink করলে group membership অক্ষত থাকে, উল্টোটাও।
 *
 * ⚠️ Bidirectional Work sync — group যদি কোনো Work-এর সাথে linked থাকে
 * (linked_work_id), add_member/remove_member এখানেই সেই Work-এর
 * traveler_sys_ids-ও sync করে দেয় (server/traveler_group_sync.php এর
 * syncGroupMemberToWork())। উল্টো দিকটাও (Work থেকে link/unlink) আছে,
 * api/works/travelers.php এর syncWorkTravelGroupMember() এ।
 */

session_start();
require '../../server/db_connection.php';
require '../../server/sys_id_generator_v2.php';
require '../../server/generate_meta_data.php';
require '../../server/traveler_group_sync.php';

header('Content-Type: application/json');
ini_set('display_errors', 0);

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $action = $_GET['action'] ?? '';

        if ($action === 'members') {
            $groupId = trim($_GET['group_id'] ?? '');
            if (!$groupId) jsonOut(['success' => false, 'message' => 'group_id প্রয়োজন']);

            $g = $pdo->prepare("SELECT sys_id, group_name, description FROM traveler_groups WHERE sys_id = ?");
            $g->execute([$groupId]);
            $group = $g->fetch(PDO::FETCH_ASSOC);
            if (!$group) jsonOut(['success' => false, 'message' => 'Group পাওয়া যায়নি']);

            $m = $pdo->prepare("
                SELECT t.sys_id, t.name
                FROM traveler_group_members gm
                JOIN travelers t ON t.sys_id = gm.traveler_id
                WHERE gm.group_id = ?
                ORDER BY gm.id ASC
            ");
            $m->execute([$groupId]);
            $members = $m->fetchAll(PDO::FETCH_ASSOC);

            jsonOut(['success' => true, 'group' => $group, 'members' => $members]);
        }

        // ডিফল্ট: এই traveler যেসব group-এর সদস্য, তার তালিকা
        $travelerId = trim($_GET['traveler_id'] ?? '');
        if (!$travelerId) jsonOut(['success' => false, 'message' => 'traveler_id প্রয়োজন']);

        $stmt = $pdo->prepare("
            SELECT g.sys_id, g.group_name, g.description, g.linked_work_id,
                   (SELECT COUNT(*) FROM traveler_group_members gm2 WHERE gm2.group_id = g.sys_id) as member_count
            FROM traveler_group_members gm
            JOIN traveler_groups g ON g.sys_id = gm.group_id
            WHERE gm.traveler_id = ?
            ORDER BY g.id DESC
        ");
        $stmt->execute([$travelerId]);
        jsonOut(['success' => true, 'groups' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

    } elseif ($method === 'POST') {
        $body   = json_decode(file_get_contents('php://input'), true) ?: [];
        $action = $body['action'] ?? '';

        if ($action === 'create_group') {
            $name = trim($body['group_name'] ?? '');
            $desc = trim($body['description'] ?? '');
            $firstMember = trim($body['traveler_id'] ?? ''); // group বানানোর সময় প্রথম সদস্য হিসেবে যোগ করা হয়

            if (!$name) jsonOut(['success' => false, 'message' => 'Group name প্রয়োজন']);

            $gv2 = generateV2IDs($pdo, 'traveler_groups');
            $groupMetaJson = buildMetaData(null, $_SESSION['user_name'] ?? 'system');
            $pdo->prepare("
                INSERT INTO traveler_groups (uuid, sys_id, group_name, description, meta_data)
                VALUES (?, ?, ?, ?, ?)
            ")->execute([$gv2['uuid'], $gv2['sys_id'], $name, $desc, $groupMetaJson]);

            if ($firstMember) {
                addGroupMember($pdo, $gv2['sys_id'], $firstMember);
            }

            jsonOut(['success' => true, 'group_sys_id' => $gv2['sys_id']]);

        } elseif ($action === 'add_member') {
            $groupId    = trim($body['group_id'] ?? '');
            $travelerId = trim($body['traveler_id'] ?? '');
            if (!$groupId || !$travelerId) jsonOut(['success' => false, 'message' => 'group_id ও traveler_id প্রয়োজন']);

            $g = $pdo->prepare("SELECT sys_id FROM traveler_groups WHERE sys_id = ?");
            $g->execute([$groupId]);
            if (!$g->fetch()) jsonOut(['success' => false, 'message' => 'Group পাওয়া যায়নি']);

            $exists = $pdo->prepare("SELECT sys_id FROM traveler_group_members WHERE group_id = ? AND traveler_id = ?");
            $exists->execute([$groupId, $travelerId]);
            if ($exists->fetch()) jsonOut(['success' => false, 'message' => 'ইতিমধ্যে এই group-এর সদস্য']);

            addGroupMember($pdo, $groupId, $travelerId);
            jsonOut(['success' => true]);

        } elseif ($action === 'remove_member') {
            $groupId    = trim($body['group_id'] ?? '');
            $travelerId = trim($body['traveler_id'] ?? '');
            if (!$groupId || !$travelerId) jsonOut(['success' => false, 'message' => 'group_id ও traveler_id প্রয়োজন']);

            $pdo->prepare("DELETE FROM traveler_group_members WHERE group_id = ? AND traveler_id = ?")
                ->execute([$groupId, $travelerId]);

            // Bidirectional sync — এই group Work-linked হলে Work থেকেও সরাও
            syncGroupMemberToWork($pdo, $groupId, $travelerId, 'unlink', $_SESSION['user_name'] ?? 'system');

            jsonOut(['success' => true]);

        } else {
            jsonOut(['success' => false, 'message' => 'অবৈধ action']);
        }

    } elseif ($method === 'DELETE') {
        $groupId = trim($_GET['group_id'] ?? '');
        if (!$groupId) jsonOut(['success' => false, 'message' => 'group_id প্রয়োজন']);

        $pdo->prepare("DELETE FROM traveler_group_members WHERE group_id = ?")->execute([$groupId]);
        $pdo->prepare("DELETE FROM traveler_groups WHERE sys_id = ?")->execute([$groupId]);
        jsonOut(['success' => true]);

    } else {
        jsonOut(['success' => false, 'message' => 'Unsupported method']);
    }
} catch (Throwable $e) {
    error_log('[traveler-groups] ' . $e->getMessage());
    jsonOut(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

function addGroupMember(PDO $pdo, string $groupId, string $travelerId): void
{
    // এখন shared helper (server/traveler_group_sync.php) reuse করা হচ্ছে,
    // যাতে manual "Join Group" (এখানে) এবং work-triggered auto-add
    // (api/leads/move-to-work.php → api/works/travelers.php) — দুই পথ
    // থেকেই membership + group_member auto-link একই আচরণ করে, কোড
    // duplicate না হয়।
    addTravelerToGroupWithAutoLink($pdo, $groupId, $travelerId, $_SESSION['user_name'] ?? 'system');
}

function jsonOut(array $d): never { echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }