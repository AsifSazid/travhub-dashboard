<?php
session_start();
// api/masterdata/countries/get.php
// GET  ?sys_id=THR-26-CNT-01
header('Content-Type: application/json');
require_once('../../../server/db_connection.php');

$sys_id = trim($_GET['sys_id'] ?? '');

if (empty($sys_id)) {
    echo json_encode(['success' => false, 'message' => 'sys_id is required']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, uuid, sys_id, name, code, currency, currency_code,
               default_rate, region, cities, status, meta_data, created_at, updated_at
        FROM countries
        WHERE sys_id = :sys_id
        LIMIT 1
    ");
    $stmt->execute([':sys_id' => $sys_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Country not found']);
        exit;
    }

    $row['cities']    = json_decode($row['cities']    ?? '[]', true) ?: [];
    $row['meta_data'] = json_decode($row['meta_data'] ?? '{}', true) ?: [];
    $row['id']           = (int)$row['id'];
    $row['default_rate'] = (float)$row['default_rate'];

    echo json_encode(['success' => true, 'data' => $row]);

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}