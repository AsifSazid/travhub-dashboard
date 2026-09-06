<?php
/**
 * FILE PATH: api/travelers/restore-document.php
 *
 * Soft-deleted traveler_documents row (status='deleted') কে আবার 'active' করে।
 * SMB ফাইল কখনো মোছা হয়নি, তাই শুধু DB status বদলালেই Documents tab-এ
 * আবার দেখা যাবে। meta_data-তে restore audit trail যোগ হয়।
 *
 * INPUT (JSON POST): sys_id
 * OUTPUT: { success, message }
 */

require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';
session_start();
date_default_timezone_set('Asia/Dhaka');

header('Content-Type: application/json');
ini_set('display_errors', 0);

// শুধু admin (role='0') document restore করতে পারবে — delete-এর সমান্তরাল restriction
if (empty($_SESSION['role']) || $_SESSION['role'] != '0') {
    echo json_encode(['success' => false, 'message' => 'এই কাজের জন্য admin অনুমতি প্রয়োজন']);
    exit;
}

$body  = json_decode(file_get_contents('php://input'), true) ?: [];
$sysId = trim($body['sys_id'] ?? '');

if (!$sysId) jsonOut(['success' => false, 'message' => 'sys_id প্রয়োজন']);

try {
    $stmt = $pdo->prepare("SELECT status, meta_data FROM traveler_documents WHERE sys_id = ? LIMIT 1");
    $stmt->execute([$sysId]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doc) jsonOut(['success' => false, 'message' => 'Document পাওয়া যায়নি']);
    if ($doc['status'] !== 'deleted') jsonOut(['success' => false, 'message' => 'এই document deleted অবস্থায় নেই']);

    $meta = json_decode($doc['meta_data'] ?? '{}', true) ?: [];
    $meta['restored_by_date'] = [
        'by'   => $_SESSION['user_name'] ?? 'system',
        'date' => date('Y-m-d H:i:s'),
    ];

    $pdo->prepare("UPDATE traveler_documents SET status = 'active', meta_data = ? WHERE sys_id = ?")
        ->execute([json_encode($meta, JSON_UNESCAPED_UNICODE), $sysId]);

    jsonOut(['success' => true, 'message' => 'Document restore হয়েছে']);
} catch (Throwable $e) {
    error_log('[restore-document] ' . $e->getMessage());
    jsonOut(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

function jsonOut(array $d): never { echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }