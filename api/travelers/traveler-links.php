<?php
/**
 * FILE PATH: api/travelers/traveler-links.php
 *
 * Family-relation link (spouse/parent/sibling) — bidirectional, একটা row
 * দুই দিক থেকে দেখানো হয়। 'parent' এ traveler_a সবসময় parent, traveler_b
 * child ধরা হয় (normalize করার সময় ঠিক করা হয়, সেটাই নিচে দেখুন)।
 *
 * GET  ?traveler_id=...              → এই traveler-এর সব link, direction-aware label সহ
 * POST { traveler_a, traveler_b, relation_type, parent_is_a } → নতুন link
 *   parent_is_a: relation_type='parent' হলে বলে দিতে হবে কে parent (true হলে traveler_a parent)
 * DELETE ?sys_id=...                 → link মুছে দাও
 *
 * OUTPUT (GET):
 *   { success, links: [{ link_sys_id, other_traveler: {sys_id,name}, relation_label, relation_type }] }
 */

session_start();
require '../../server/db_connection.php';
require '../../server/sys_id_generator_v2.php';
require '../../server/generate_meta_data.php';

header('Content-Type: application/json');
ini_set('display_errors', 0);

$method = $_SERVER['REQUEST_METHOD'];

// relation_type অনুযায়ী, "আমি traveler_a" হলে অন্যজনকে কী বলে ডাকব
// আর "আমি traveler_b" হলে কী বলে ডাকব — এই map দিয়েই bidirectional label ঠিক হয়
const RELATION_LABELS = [
    'spouse'       => ['a' => 'Spouse',       'b' => 'Spouse'],
    'sibling'      => ['a' => 'Sibling',      'b' => 'Sibling'],
    'other'        => ['a' => 'Other',        'b' => 'Other'],
    'group_member' => ['a' => 'Group Member', 'b' => 'Group Member'], // traveler-groups.php থেকে auto-link হয়
    'parent'       => ['a' => 'Child',        'b' => 'Parent'], // a=parent সাজানো থাকে (নিচে দেখুন), তাই a এর দৃষ্টিতে b হলো child... ⚠️ নিচের normalize এ a সবসময় parent
];

try {
    if ($method === 'GET') {
        $travelerId = trim($_GET['traveler_id'] ?? '');
        if (!$travelerId) jsonOut(['success' => false, 'message' => 'traveler_id প্রয়োজন']);

        $stmt = $pdo->prepare("
            SELECT tl.sys_id as link_sys_id, tl.traveler_a_id, tl.traveler_b_id, tl.relation_type,
                   ta.name as a_name, tb.name as b_name
            FROM traveler_links tl
            JOIN travelers ta ON ta.sys_id = tl.traveler_a_id
            JOIN travelers tb ON tb.sys_id = tl.traveler_b_id
            WHERE tl.traveler_a_id = ? OR tl.traveler_b_id = ?
            ORDER BY tl.id DESC
        ");
        $stmt->execute([$travelerId, $travelerId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $links = [];
        foreach ($rows as $r) {
            $isA = ($r['traveler_a_id'] === $travelerId);
            $other = $isA
                ? ['sys_id' => $r['traveler_b_id'], 'name' => $r['b_name']]
                : ['sys_id' => $r['traveler_a_id'], 'name' => $r['a_name']];

            $rel = $r['relation_type'];
            if ($rel === 'parent') {
                // convention: traveler_a সবসময় parent। আমি a হলে other=child,
                // আমি b হলে other=parent
                $label = $isA ? 'Child' : 'Parent';
            } else {
                $label = RELATION_LABELS[$rel]['a'] ?? ucfirst($rel);
            }

            $links[] = [
                'link_sys_id'    => $r['link_sys_id'],
                'other_traveler' => $other,
                'relation_label' => $label,
                'relation_type'  => $rel,
            ];
        }

        jsonOut(['success' => true, 'links' => $links]);

    } elseif ($method === 'POST') {
        $body        = json_decode(file_get_contents('php://input'), true) ?: [];
        $travelerA   = trim($body['traveler_a'] ?? '');
        $travelerB   = trim($body['traveler_b'] ?? '');
        $relation    = trim($body['relation_type'] ?? '');
        $parentIsA   = $body['parent_is_a'] ?? null; // relation_type='parent' হলে required

        // group_member এখানে user-facing POST-এ allow না — সেটা শুধু
        // addGroupMember() নিজে থেকে internal তৈরি করে, manual add নয়
        $validRelations = ['spouse', 'parent', 'sibling', 'other'];
        if (!$travelerA || !$travelerB) jsonOut(['success' => false, 'message' => 'দুইজন traveler প্রয়োজন']);
        if ($travelerA === $travelerB) jsonOut(['success' => false, 'message' => 'একই traveler-কে নিজের সাথে link করা যাবে না']);
        if (!in_array($relation, $validRelations, true)) jsonOut(['success' => false, 'message' => 'অবৈধ relation type']);

        // 'parent' হলে traveler_a কে সবসময় parent বানিয়ে normalize করা হয়
        if ($relation === 'parent') {
            if ($parentIsA === null) jsonOut(['success' => false, 'message' => 'কে parent তা বলা আবশ্যক (parent_is_a)']);
            if (!$parentIsA) {
                [$travelerA, $travelerB] = [$travelerB, $travelerA]; // swap — a always parent
            }
        } else {
            // spouse/sibling — direction-independent, তাই sorted order এ
            // normalize করে duplicate (A,B) vs (B,A) row আটকানো
            if (strcmp($travelerA, $travelerB) > 0) {
                [$travelerA, $travelerB] = [$travelerB, $travelerA];
            }
        }

        // দুই traveler-ই আসলে exist করে কিনা যাচাই
        $chk = $pdo->prepare("SELECT COUNT(*) FROM travelers WHERE sys_id IN (?, ?)");
        $chk->execute([$travelerA, $travelerB]);
        if ((int)$chk->fetchColumn() !== 2) jsonOut(['success' => false, 'message' => 'একজন বা দুইজন traveler পাওয়া যায়নি']);

        // ইতিমধ্যে link আছে কিনা (unique key এমনিতেও আটকাবে, কিন্তু readable error দেওয়ার জন্য আগে check)
        $dup = $pdo->prepare("SELECT sys_id FROM traveler_links WHERE traveler_a_id = ? AND traveler_b_id = ?");
        $dup->execute([$travelerA, $travelerB]);
        if ($dup->fetch()) jsonOut(['success' => false, 'message' => 'এই দুইজনের মধ্যে ইতিমধ্যে একটা link আছে']);

        $v2 = generateV2IDs($pdo, 'traveler_links');
        $metaDataJson = buildMetaData(null, $_SESSION['user_name'] ?? 'system');
        $pdo->prepare("
            INSERT INTO traveler_links (uuid, sys_id, traveler_a_id, traveler_b_id, relation_type, meta_data)
            VALUES (?, ?, ?, ?, ?, ?)
        ")->execute([
            $v2['uuid'], $v2['sys_id'], $travelerA, $travelerB, $relation, $metaDataJson,
        ]);

        jsonOut(['success' => true, 'link_sys_id' => $v2['sys_id']]);

    } elseif ($method === 'DELETE') {
        parse_str(file_get_contents('php://input'), $parsed);
        $sysId = trim($_GET['sys_id'] ?? $parsed['sys_id'] ?? '');
        if (!$sysId) jsonOut(['success' => false, 'message' => 'sys_id প্রয়োজন']);

        $pdo->prepare("DELETE FROM traveler_links WHERE sys_id = ?")->execute([$sysId]);
        jsonOut(['success' => true]);

    } else {
        jsonOut(['success' => false, 'message' => 'Unsupported method']);
    }
} catch (Throwable $e) {
    error_log('[traveler-links] ' . $e->getMessage());
    jsonOut(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

function jsonOut(array $d): never { echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }