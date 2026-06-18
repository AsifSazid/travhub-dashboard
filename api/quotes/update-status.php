<?php
/**
 * api/quotes/update-status.php (Gen-3)
 * POST { sys_id, quote_status }
 * valid: draft → sent → accepted | expired
 * accepted also sets packages.completion_status = 'confirmed'
 */
session_start();
require_once '../../../server/api_bootstrap.php';
require_once '../../../server/db_connection.php';
require_once '../../../server/generate_meta_data.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false,'message'=>'POST only']); exit;
}

$in           = json_decode(file_get_contents('php://input'), true) ?: [];
$sys_id       = trim($in['sys_id']       ?? '');
$quote_status = trim($in['quote_status'] ?? '');

$validStatuses = ['draft','sent','accepted','expired','superseded'];
if (!$sys_id || !in_array($quote_status, $validStatuses)) {
    echo json_encode(['success'=>false,'message'=>'sys_id and valid quote_status required']); exit;
}

try {
    $row = $pdo->prepare("SELECT * FROM quotes WHERE sys_id=? AND status!='deleted' LIMIT 1");
    $row->execute([$sys_id]); $quote = $row->fetch();
    if (!$quote) { echo json_encode(['success'=>false,'message'=>'Quote not found']); exit; }

    $meta = buildMetaData($quote['meta_data'], $_SESSION['user_name'] ?? 'system');

    $pdo->prepare("UPDATE quotes SET quote_status=?, meta_data=? WHERE sys_id=?")
        ->execute([$quote_status, $meta, $sys_id]);

    // If accepted → confirm package
    if ($quote_status === 'accepted') {
        $pdo->prepare("
            UPDATE packages SET
                completion_status = 'confirmed',
                active_quote_sys_id = ?
            WHERE sys_id = ?
        ")->execute([$sys_id, $quote['package_sys_id']]);
    }

    echo json_encode(['success'=>true,'action'=>'status_updated',
        'sys_id'=>$sys_id, 'quote_status'=>$quote_status]);

} catch (Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
