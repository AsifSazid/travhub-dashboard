<?php
session_start();
require_once '../../../server/api_bootstrap.php';
require_once '../../../server/db_connection.php';
require_once '../../../server/generate_meta_data.php';
require_once '../../../server/json_sync.php';

$in      = json_decode(file_get_contents('php://input'), true) ?: [];
$sys_id  = trim($in['sys_id']  ?? '');
$restore = !empty($in['restore']);
if (!$sys_id) { echo json_encode(['success'=>false,'message'=>'sys_id required']); exit; }
try {
    $row = $pdo->prepare("SELECT meta_data FROM activities WHERE sys_id = ? LIMIT 1");
    $row->execute([$sys_id]); $existing = $row->fetch();
    if (!$existing) { echo json_encode(['success'=>false,'message'=>'Not found']); exit; }
    $meta      = buildMetaData($existing['meta_data'], $_SESSION['user_name'] ?? 'system');
    $newStatus = $restore ? 'active' : 'deleted';
    $pdo->prepare("UPDATE activities SET status = ?, meta_data = ? WHERE sys_id = ?")->execute([$newStatus, $meta, $sys_id]);
    syncActivitiesJson($pdo);
    $action = $restore ? 'restored' : 'deleted';
    echo json_encode(['success'=>true,'action'=>$action,'message'=>"Record {$action}."]);
} catch (Throwable $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
