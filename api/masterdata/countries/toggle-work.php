<?php
/**
 * api/masterdata/countries/toggle-work.php
 * POST { sys_id, for_work: 0|1 }
 */
session_start();
require_once '../../../server/api_bootstrap.php';
require_once '../../../server/db_connection.php';
require_once '../../../server/generate_meta_data.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false,'message'=>'POST only']); exit;
}

$in       = json_decode(file_get_contents('php://input'), true) ?: [];
$sys_id   = trim($in['sys_id']   ?? '');
$for_work = isset($in['for_work']) ? (int)(bool)$in['for_work'] : null;

if (!$sys_id || $for_work === null) {
    echo json_encode(['success'=>false,'message'=>'sys_id and for_work required']); exit;
}

try {
    $row = $pdo->prepare("SELECT name, meta_data FROM countries WHERE sys_id = ? AND status != 'deleted' LIMIT 1");
    $row->execute([$sys_id]);
    $existing = $row->fetch();
    if (!$existing) { echo json_encode(['success'=>false,'message'=>'Country not found']); exit; }

    $meta = buildMetaData($existing['meta_data'], $_SESSION['user_name'] ?? 'system');
    $pdo->prepare("UPDATE countries SET for_work = ?, meta_data = ? WHERE sys_id = ?")
        ->execute([$for_work, $meta, $sys_id]);

    echo json_encode([
        'success'  => true,
        'sys_id'   => $sys_id,
        'name'     => $existing['name'],
        'for_work' => $for_work,
        'message'  => $for_work
            ? "{$existing['name']} enabled for Work/Lead/Task."
            : "{$existing['name']} removed from Work/Lead/Task.",
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}