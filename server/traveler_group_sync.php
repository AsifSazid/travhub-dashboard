<?php
/**
 * FILE PATH: server/traveler_group_sync.php
 *
 * Work ↔ Travel Group sync — একটা Work তৈরি হলে automatically একটা
 * traveler_groups row তৈরি হয় (linked_work_id দিয়ে bind করা), এবং সেই
 * Work-এ traveler link/unlink হলে group membership-ও sync হয়।
 *
 * এই দুইটা helper api/leads/move-to-work.php (group create) এবং
 * api/works/travelers.php (membership sync)-এ ব্যবহৃত হয়।
 */

require_once __DIR__ . '/sys_id_generator_v2.php';
require_once __DIR__ . '/generate_meta_data.php';

/**
 * Work তৈরি হওয়ার সময় কল করা হয় — যদি এই work-এর জন্য group আগে থেকে না
 * থাকে, একটা নতুন traveler_groups row বানায় (linked_work_id সহ)। আগে
 * থেকে থাকলে idempotent — কিছুই করে না।
 */
function ensureWorkTravelGroup(PDO $pdo, string $workSysId, string $groupName, string $userName): string
{
    $existing = $pdo->prepare("SELECT sys_id FROM traveler_groups WHERE linked_work_id = ? LIMIT 1");
    $existing->execute([$workSysId]);
    $row = $existing->fetch(PDO::FETCH_ASSOC);
    if ($row) return $row['sys_id'];

    $gv2 = generateV2IDs($pdo, 'traveler_groups');
    $metaJson = buildMetaData(null, $userName);

    $pdo->prepare("
        INSERT INTO traveler_groups (uuid, sys_id, group_name, description, linked_work_id, meta_data)
        VALUES (?, ?, ?, ?, ?, ?)
    ")->execute([
        $gv2['uuid'], $gv2['sys_id'], $groupName,
        'Auto-created from Work ' . $workSysId,
        $workSysId, $metaJson,
    ]);

    return $gv2['sys_id'];
}

/**
 * api/works/travelers.php-এর link/unlink action-এর পর কল করা হয় — এই
 * work-এর সাথে bind করা group-এ traveler add/remove করে, membership sync
 * রাখে। Group না থাকলে (পুরনো work, group-linkage আসার আগে তৈরি) চুপচাপ
 * কিছুই করে না — ভাঙা রেফারেন্স তৈরি করে না।
 */
function syncWorkTravelGroupMember(PDO $pdo, string $workSysId, string $travelerId, string $action, string $userName): void
{
    $g = $pdo->prepare("SELECT sys_id FROM traveler_groups WHERE linked_work_id = ? LIMIT 1");
    $g->execute([$workSysId]);
    $group = $g->fetch(PDO::FETCH_ASSOC);
    if (!$group) return; // পুরনো work, linked group নেই — কিছু করার নেই

    $groupId = $group['sys_id'];

    if ($action === 'link') {
        $exists = $pdo->prepare("SELECT sys_id FROM traveler_group_members WHERE group_id = ? AND traveler_id = ?");
        $exists->execute([$groupId, $travelerId]);
        if ($exists->fetch()) return; // আগে থেকেই সদস্য

        // group-এর বাকি সদস্যদের সাথে auto family-link (group_member relation)
        // তৈরির logic api/travelers/traveler-groups.php-এর addGroupMember()-এ
        // আছে — এখানে সেটা duplicate না করে সরাসরি একই pattern reuse করছি
        addTravelerToGroupWithAutoLink($pdo, $groupId, $travelerId, $userName);

    } elseif ($action === 'unlink') {
        $pdo->prepare("DELETE FROM traveler_group_members WHERE group_id = ? AND traveler_id = ?")
            ->execute([$groupId, $travelerId]);
        // ⚠️ group_member family-link ইচ্ছাকৃতভাবে মোছা হচ্ছে না — Family
        // Links ও Group membership independent থাকার সিদ্ধান্ত অনুযায়ী
        // (traveler-links.php থেকে unlink করলেও group membership অক্ষত
        // থাকে, তেমনি এখানেও group থেকে সরালে family link অক্ষত থাকবে)
    }
}

/**
 * traveler_group_members এ row বসায় + group-এর বাকি সবার সাথে
 * relation_type='group_member' দিয়ে traveler_links auto-link তৈরি করে।
 * api/travelers/traveler-groups.php-এর addGroupMember()-এর সাথে সমান্তরাল
 * রাখা হয়েছে যাতে দুই পথ (manual join vs work-triggered) থেকে একই আচরণ আসে।
 */
function addTravelerToGroupWithAutoLink(PDO $pdo, string $groupId, string $travelerId, string $userName): void
{
    $existingStmt = $pdo->prepare("SELECT traveler_id FROM traveler_group_members WHERE group_id = ?");
    $existingStmt->execute([$groupId]);
    $existingMembers = $existingStmt->fetchAll(PDO::FETCH_COLUMN);

    $mv2 = generateV2IDs($pdo, 'traveler_group_members');
    $memberMeta = buildMetaData(null, $userName);
    $pdo->prepare("
        INSERT INTO traveler_group_members (uuid, sys_id, group_id, traveler_id, meta_data)
        VALUES (?, ?, ?, ?, ?)
    ")->execute([$mv2['uuid'], $mv2['sys_id'], $groupId, $travelerId, $memberMeta]);

    // ⚠️ Bidirectional sync — এই group যদি কোনো Work-এর সাথে linked থাকে,
    // Group থেকে যোগ করা traveler-ও সেই Work-এ (works.traveler_sys_ids)
    // যোগ হবে, যাতে Group ↔ Work উভয় দিক থেকেই সামঞ্জস্যপূর্ণ থাকে
    syncGroupMemberToWork($pdo, $groupId, $travelerId, 'link', $userName);

    foreach ($existingMembers as $otherId) {
        if ($otherId === $travelerId) continue;

        [$a, $b] = (strcmp($travelerId, $otherId) <= 0) ? [$travelerId, $otherId] : [$otherId, $travelerId];

        $dup = $pdo->prepare("SELECT sys_id FROM traveler_links WHERE traveler_a_id = ? AND traveler_b_id = ?");
        $dup->execute([$a, $b]);
        if ($dup->fetch()) continue;

        try {
            $lv2 = generateV2IDs($pdo, 'traveler_links');
            $linkMeta = buildMetaData(null, $userName);
            $pdo->prepare("
                INSERT INTO traveler_links (uuid, sys_id, traveler_a_id, traveler_b_id, relation_type, meta_data)
                VALUES (?, ?, ?, ?, 'group_member', ?)
            ")->execute([$lv2['uuid'], $lv2['sys_id'], $a, $b, $linkMeta]);
        } catch (Throwable $e) {
            error_log('[traveler_group_sync] auto-link skip: ' . $e->getMessage());
        }
    }
}

/**
 * Group-এ member add/remove হলে কল করা হয় — যদি এই group কোনো Work-এর সাথে
 * linked থাকে (linked_work_id), সেই Work-এর traveler_sys_ids-ও sync করে
 * (api/works/travelers.php-এর link/unlink actions যেই structure বানায়,
 * ঠিক সেই একই structure — শুধু এখানে reverse direction-এ)।
 *
 * Work না থাকলে (standalone group) চুপচাপ কিছু করে না।
 */
function syncGroupMemberToWork(PDO $pdo, string $groupId, string $travelerId, string $action, string $userName): void
{
    $g = $pdo->prepare("SELECT linked_work_id FROM traveler_groups WHERE sys_id = ? LIMIT 1");
    $g->execute([$groupId]);
    $group = $g->fetch(PDO::FETCH_ASSOC);
    if (!$group || !$group['linked_work_id']) return; // standalone group, কিছু করার নেই

    $workSysId = $group['linked_work_id'];

    $wStmt = $pdo->prepare("SELECT traveler_sys_ids, meta_data FROM works WHERE sys_id = ? LIMIT 1");
    $wStmt->execute([$workSysId]);
    $work = $wStmt->fetch(PDO::FETCH_ASSOC);
    if (!$work) return; // work মুছে গেছে বা পাওয়া যাচ্ছে না

    $refs = _normalizeWorkTravelerRefs(json_decode($work['traveler_sys_ids'] ?? '[]', true) ?: []);

    if ($action === 'link') {
        foreach ($refs as $r) {
            if ($r['traveler_sys_id'] === $travelerId) return; // আগে থেকেই আছে
        }

        $tCheck = $pdo->prepare("SELECT sys_id, name, passport_info, smb_path FROM travelers WHERE sys_id = ? LIMIT 1");
        $tCheck->execute([$travelerId]);
        $traveler = $tCheck->fetch(PDO::FETCH_ASSOC);
        if (!$traveler) return;

        $passport = !empty($traveler['passport_info']) ? (json_decode($traveler['passport_info'], true) ?? []) : [];

        $refs[] = [
            'traveler_sys_id'   => $traveler['sys_id'],
            'name'              => $traveler['name'],
            'given_name'        => $passport['given_name']    ?? $passport['first_name']  ?? '',
            'surname'           => $passport['surname']       ?? $passport['last_name']   ?? '',
            'passport_no'       => $passport['passport_no']   ?? $passport['passport_number'] ?? '',
            'passport_expiry'   => $passport['expiry_date']   ?? $passport['date_of_expiry']  ?? '',
            'dob'               => $passport['date_of_birth'] ?? $passport['dob']              ?? '',
            'passport_smb_path' => $traveler['smb_path']      ?? '',
        ];
    } elseif ($action === 'unlink') {
        $refs = array_values(array_filter($refs, fn($r) => $r['traveler_sys_id'] !== $travelerId));
    }

    $meta = buildMetaData($work['meta_data'], $userName);
    $pdo->prepare("UPDATE works SET traveler_sys_ids = ?, meta_data = ? WHERE sys_id = ?")
        ->execute([json_encode($refs, JSON_UNESCAPED_UNICODE), $meta, $workSysId]);
}

/**
 * api/works/travelers.php-এর _normalizeTravelerRefs()-এর সাথে সমান্তরাল —
 * legacy plain-string ডেটাও handle করে।
 */
function _normalizeWorkTravelerRefs(array $raw): array
{
    $out = [];
    foreach ($raw as $item) {
        if (is_array($item) && isset($item['traveler_sys_id'])) {
            $out[] = $item;
        } elseif (is_string($item)) {
            $out[] = ['traveler_sys_id' => $item, 'name' => ''];
        }
    }
    return $out;
}