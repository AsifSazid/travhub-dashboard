<?php
session_start();
// api/masterdata/activities/get.php
// GET ?sys_id=THR-26-CNT-01-CTS-01-ACT-01
header('Content-Type: application/json');
// include_once('../../../authenticate.php');
require_once('../../../server/db_connection.php');

$sys_id = trim($_GET['sys_id'] ?? '');
if (empty($sys_id)) { echo json_encode(['success'=>false,'message'=>'sys_id required']); exit; }

try {
    $stmt = $pdo->prepare("SELECT * FROM activities WHERE sys_id = ? LIMIT 1");
    $stmt->execute([$sys_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) { echo json_encode(['success'=>false,'message'=>'Activity not found']); exit; }

    $row['id']             = (int)$row['id'];
    $row['duration_hours'] = (float)$row['duration_hours'];
    $row['popularity']     = (int)$row['popularity'];
    $row['meta_data']      = json_decode($row['meta_data'] ?? '{}', true) ?: [];

    echo json_encode(['success'=>true,'data'=>$row]);
} catch (Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}