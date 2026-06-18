<?php
/**
 * api/packages/delete.php (Gen-3)
 * POST { sys_id, restore? }
 * Soft delete: status → deleted. restore=true → status → active
 */
session_start();
require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';
require_once '../../server/generate_meta_data.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false,'message'=>'POST only']); exit;
}

$in      = json_decode(file_get_contents('php://input'), true) ?: [];
$sys_id  = trim($in['sys_id']  ?? '');
$restore = !empty($in['restore']);

if (!$sys_id) { echo json_encode(['success'=>false,'message'=>'sys_id required']); exit; }

try {
    $row = $pdo->prepare("SELECT meta_data FROM packages WHERE sys_id = ? LIMIT 1");
    $row->execute([$sys_id]);
    $existing = $row->fetch();
    if (!$existing) { echo json_encode(['success'=>false,'message'=>'Package not found']); exit; }

    $newStatus = $restore ? 'active' : 'deleted';
    $meta      = buildMetaData($existing['meta_data'], $_SESSION['user_name'] ?? 'system');

    $pdo->prepare("UPDATE packages SET status = ?, meta_data = ? WHERE sys_id = ?")
        ->execute([$newStatus, $meta, $sys_id]);

    echo json_encode(['success'=>true,'action'=>$restore?'restored':'deleted','sys_id'=>$sys_id]);

} catch (Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
