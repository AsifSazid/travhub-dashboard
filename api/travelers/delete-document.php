<?php
/**
 * FILE PATH: api/travelers/delete-document.php
 *
 * Soft-delete a traveler_documents row (status → 'deleted').
 * SMB file থাকবেই — শুধু DB status বদলায়, তাই list-documents.php এর
 * status=active ফিল্টারে এটা আর দেখা যাবে না, কিন্তু recover করা সম্ভব।
 *
 * শর্ত: reason আবশ্যক + confirmation হিসেবে ইউজারকে exact sys_id পাঠাতে হবে
 * (accidental one-click delete এড়াতে)।
 *
 * INPUT (JSON POST):
 *   sys_id            — traveler_documents.sys_id (যেটা delete হবে)
 *   confirm_sys_id     — ইউজার আবার একই sys_id টাইপ/confirm করেছে কিনা
 *   reason             — কেন delete করা হচ্ছে (আবশ্যক, খালি রাখা যাবে না)
 *
 * OUTPUT: { success, message }
 */

require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';
session_start();

header('Content-Type: application/json');
ini_set('display_errors', 0);

$raw  = file_get_contents('php://input');
$body = json_decode($raw, true) ?: [];

$sysId        = trim($body['sys_id'] ?? '');
$confirmSysId = trim($body['confirm_sys_id'] ?? '');
$reason       = trim($body['reason'] ?? '');

if (!$sysId) {
    jsonOut(['success' => false, 'message' => 'sys_id প্রয়োজন']);
}
if ($confirmSysId !== $sysId) {
    jsonOut(['success' => false, 'message' => 'Confirmation mismatch — sys_id মেলেনি, delete বাতিল করা হলো']);
}
if ($reason === '' || mb_strlen($reason) < 5) {
    jsonOut(['success' => false, 'message' => 'Delete করার কারণ লিখুন (কমপক্ষে ৫ অক্ষর)']);
}

try {
    $stmt = $pdo->prepare("SELECT * FROM traveler_documents WHERE sys_id = ? LIMIT 1");
    $stmt->execute([$sysId]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doc) {
        jsonOut(['success' => false, 'message' => 'Document পাওয়া যায়নি']);
    }
    if ($doc['status'] === 'deleted') {
        jsonOut(['success' => false, 'message' => 'এই document আগে থেকেই deleted']);
    }

    // meta_data এ delete audit trail যোগ করো (architectural convention অনুযায়ী —
    // আলাদা কলাম না বানিয়ে meta_data JSON এ রাখা হচ্ছে)
    $metaData = json_decode($doc['meta_data'] ?? '{}', true) ?: [];
    $metaData['deleted_by_date'] = [
        'by'     => $_SESSION['user_name'] ?? 'system',
        'date'   => date('Y-m-d H:i:s'),
        'reason' => $reason,
    ];

    $pdo->prepare("
        UPDATE traveler_documents
        SET status = 'deleted', meta_data = ?
        WHERE sys_id = ?
    ")->execute([json_encode($metaData, JSON_UNESCAPED_UNICODE), $sysId]);

    jsonOut(['success' => true, 'message' => 'Document soft-deleted হয়েছে']);

} catch (Throwable $e) {
    error_log('[delete-document] Error: ' . $e->getMessage());
    jsonOut(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

function jsonOut(array $data): never
{
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}