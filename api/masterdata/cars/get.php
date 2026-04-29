<?php
session_start();
// api/masterdata/cars/get.php
// GET  ?sys_id=THR-26-CNT-01-CAR-01
header('Content-Type: application/json');
require_once('../../../server/db_connection.php');

$sys_id = trim($_GET['sys_id'] ?? '');
if (empty($sys_id)) {
    echo json_encode(['success' => false, 'message' => 'sys_id required']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM cars WHERE sys_id = ? LIMIT 1");
    $stmt->execute([$sys_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Car not found']);
        exit;
    }

    $row['id']          = (int)$row['id'];
    $row['seats']       = (int)$row['seats'];
    $row['has_luggage'] = (bool)$row['has_luggage'];
    $row['meta_data']   = json_decode($row['meta_data'] ?? '{}', true) ?: [];

    echo json_encode(['success' => true, 'data' => $row]);

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}