<?php
session_start();
require_once '../../../server/api_bootstrap.php';
require_once '../../../server/db_connection.php';

$sys_id = trim($_GET['sys_id'] ?? '');
if (!$sys_id) { echo json_encode(['success'=>false,'message'=>'sys_id required']); exit; }

try {
    $stmt = $pdo->prepare("SELECT * FROM countries WHERE sys_id = ? LIMIT 1");
    $stmt->execute([$sys_id]);
    $row = $stmt->fetch();
    if (!$row) { echo json_encode(['success'=>false,'message'=>'Not found']); exit; }

    $row['cities']    = json_decode($row['cities']    ?? '[]', true) ?: [];
    $row['meta_data'] = json_decode($row['meta_data'] ?? '{}', true) ?: [];
    $row['default_rate'] = (float)$row['default_rate'];
    $row['id']           = (int)$row['id'];

    echo json_encode(['success'=>true,'data'=>$row], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}