<?php
/**
 * api/quotes/list.php (Gen-3)
 * GET ?package_sys_id=THR-PK-26-00K001
 */
session_start();
require_once '../../../server/api_bootstrap.php';
require_once '../../../server/db_connection.php';

$package_sys_id = trim($_GET['package_sys_id'] ?? '');
if (!$package_sys_id) { echo json_encode(['success'=>false,'message'=>'package_sys_id required']); exit; }

try {
    $stmt = $pdo->prepare("
        SELECT * FROM quotes
        WHERE package_sys_id=? AND status!='deleted'
        ORDER BY version DESC
    ");
    $stmt->execute([$package_sys_id]);
    $rows = $stmt->fetchAll();

    foreach ($rows as &$r) {
        if (!empty($r['fx_snapshot'])) {
            $r['fx_snapshot'] = json_decode($r['fx_snapshot'], true);
        }
    }
    unset($r);

    echo json_encode(['success'=>true,'data'=>$rows], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
