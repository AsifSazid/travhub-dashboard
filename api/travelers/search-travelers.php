<?php
/**
 * FILE PATH: api/travelers/search-travelers.php
 *
 * নাম দিয়ে traveler খোঁজা — Linked Travellers-এ "Add Link" modal-এর
 * typeahead search-এর জন্য। কারো sys_id মনে রাখতে হবে না, নাম দিয়েই খোঁজা যায়।
 *
 * INPUT (GET): q (নামের অংশ), exclude (এই sys_id বাদ দেবে — নিজেকে নিজে
 *              link করা এড়াতে)
 * OUTPUT: { success, travelers: [{sys_id, name, passport_no, nid_no}] }
 */

require '../../server/db_connection.php';
header('Content-Type: application/json');
ini_set('display_errors', 0);

$q       = trim($_GET['q'] ?? '');
$exclude = trim($_GET['exclude'] ?? '');

if (mb_strlen($q) < 2) {
    jsonOut(['success' => true, 'travelers' => []]); // খুব ছোট query-তে ফাঁকা রেজাল্ট, load কমাতে
}

try {
    $sql = "SELECT sys_id, name, passport_no, nid_no FROM travelers WHERE name LIKE ?";
    $params = ['%' . $q . '%'];

    if ($exclude) {
        $sql .= " AND sys_id != ?";
        $params[] = $exclude;
    }
    $sql .= " ORDER BY name ASC LIMIT 15";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    jsonOut(['success' => true, 'travelers' => $rows]);
} catch (Throwable $e) {
    error_log('[search-travelers] ' . $e->getMessage());
    jsonOut(['success' => false, 'message' => 'Server error', 'travelers' => []]);
}

function jsonOut(array $d): never { echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }