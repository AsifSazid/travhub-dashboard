<?php
/**
 * api/quotes/get.php (Gen-3)
 * GET ?sys_id=THR-QT-26-00K001
 * Returns quote + all lines
 */
session_start();
require_once '../../../server/api_bootstrap.php';
require_once '../../../server/db_connection.php';

$sys_id = trim($_GET['sys_id'] ?? '');
if (!$sys_id) { echo json_encode(['success'=>false,'message'=>'sys_id required']); exit; }

try {
    $qStmt = $pdo->prepare("SELECT * FROM quotes WHERE sys_id=? AND status!='deleted' LIMIT 1");
    $qStmt->execute([$sys_id]);
    $quote = $qStmt->fetch();
    if (!$quote) { echo json_encode(['success'=>false,'message'=>'Quote not found']); exit; }

    if (!empty($quote['fx_snapshot'])) {
        $quote['fx_snapshot'] = json_decode($quote['fx_snapshot'], true);
    }

    $lStmt = $pdo->prepare("
        SELECT * FROM quote_lines WHERE quote_sys_id=? AND status!='deleted' ORDER BY id ASC
    ");
    $lStmt->execute([$sys_id]);
    $quote['lines'] = $lStmt->fetchAll();

    echo json_encode(['success'=>true,'data'=>$quote], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
