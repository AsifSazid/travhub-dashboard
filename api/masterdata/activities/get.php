<?php
session_start();
// api/masterdata/activities/get.php
// GET  ?sys_id=THR-26-CNT-01-ACT-01
header('Content-Type: application/json');
require_once('../../../server/db_connection.php');

$sys_id = trim($_GET['sys_id'] ?? '');
if (empty($sys_id)) {
    echo json_encode(['success' => false, 'message' => 'sys_id required']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM activities WHERE sys_id = ? LIMIT 1");
    $stmt->execute([$sys_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Activity not found']);
        exit;
    }

    $jsonFields = ['pickup_from_city','dropoff_city','itineraries','inclusions','exclusions','transfers','meta_data'];
    foreach ($jsonFields as $f) {
        $row[$f] = json_decode($row[$f] ?? ($f === 'meta_data' ? '{}' : '[]'), true) ?: ($f === 'meta_data' ? [] : []);
    }
    $row['id']             = (int)$row['id'];
    $row['duration_hours'] = (float)($row['duration_hours'] ?? 0);
    $row['popularity']     = (int)($row['popularity'] ?? 3);

    echo json_encode(['success' => true, 'data' => $row]);

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}