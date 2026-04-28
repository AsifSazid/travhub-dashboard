<?php
session_start();
// api/masterdata/activities/delete.php
// POST { "sys_id": "...", "restore": false }
header('Content-Type: application/json');
require_once('../../../server/db_connection.php');
require_once('../../../server/generate_meta_data.php');
require_once('../../../server/json-sync-helper.php');

$input   = json_decode(file_get_contents('php://input'), true) ?: [];
$sys_id  = trim($input['sys_id'] ?? '');
$restore = !empty($input['restore']);

if (empty($sys_id)) { echo json_encode(['success'=>false,'message'=>'sys_id required']); exit; }

try {
    $chk = $pdo->prepare("SELECT id, name, meta_data FROM activities WHERE sys_id = ? LIMIT 1");
    $chk->execute([$sys_id]);
    $row = $chk->fetch(PDO::FETCH_ASSOC);
    if (!$row) { echo json_encode(['success'=>false,'message'=>'Activity not found']); exit; }

    $metaJson  = buildMetaData($row['meta_data'], $_SESSION['user_name'] ?? 'system');
    $newStatus = $restore ? 'active' : 'deleted';
    $pdo->prepare("UPDATE activities SET status = ?, meta_data = ? WHERE sys_id = ?")
        ->execute([$newStatus, $metaJson, $sys_id]);

    exportActivitiesToJson($pdo, __DIR__ . '/../../../activities.json');
    $action = $restore ? 'restored' : 'deleted';
    echo json_encode(['success'=>true,'action'=>$action,'message'=>"Activity '{$row['name']}' {$action}."]);

} catch (Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}