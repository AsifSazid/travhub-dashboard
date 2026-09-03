<?php
/**
 * FILE PATH: api/travelers/search-groups.php
 *
 * Group name or sys_id দিয়ে existing travel group খোঁজা — "Join Existing
 * Group" modal-এর typeahead search-এর জন্য।
 *
 * INPUT (GET): q
 * OUTPUT: { success, groups: [{sys_id, group_name, description, member_count}] }
 */

require '../../server/db_connection.php';
header('Content-Type: application/json');
ini_set('display_errors', 0);

$q = trim($_GET['q'] ?? '');

if (mb_strlen($q) < 2) {
    jsonOut(['success' => true, 'groups' => []]);
}

try {
    $stmt = $pdo->prepare("
        SELECT g.sys_id, g.group_name, g.description, g.linked_work_id,
               (SELECT COUNT(*) FROM traveler_group_members gm WHERE gm.group_id = g.sys_id) as member_count
        FROM traveler_groups g
        WHERE g.group_name LIKE ? OR g.sys_id LIKE ?
        ORDER BY g.id DESC
        LIMIT 15
    ");
    $stmt->execute(['%' . $q . '%', '%' . $q . '%']);
    jsonOut(['success' => true, 'groups' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) {
    error_log('[search-groups] ' . $e->getMessage());
    jsonOut(['success' => false, 'message' => 'Server error: ' . $e->getMessage(), 'groups' => []]);
}

function jsonOut(array $d): never { echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }