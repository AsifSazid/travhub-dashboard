<?php
/**
 * FILE PATH: api/travelers/move-document.php
 *
 * Traveler document move — ভুল folder-এ classify হয়ে যাওয়া document-কে
 * সঠিক smb_folder-এ সরায় (SMB তে actual file move + DB smb_folder sync)।
 * filename অপরিবর্তিত থাকে, শুধু folder বদলায়।
 *
 * NOTE: passport document move করা যাবে না — smb_folder সবসময়
 * passport_identity ফিক্সড থাকে renewal-chain এর জন্য।
 *
 * INPUT (JSON POST):
 *   sys_id         — traveler_documents.sys_id
 *   new_smb_folder — নতুন folder key (যেমন 'nid', 'passport_identity', ...)
 *
 * OUTPUT: { success, message }
 */

require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';
require_once '../../server/live_storage.php';
session_start();

header('Content-Type: application/json');
ini_set('display_errors', 0);

// doc_type_registry.sql এ যা আছে তার সাথে মেলানো — শুধু এই folder গুলোতেই
// move করা যাবে, arbitrary string দিয়ে SMB path injection এড়াতে
const VALID_SMB_FOLDERS = [
    'passport_identity', 'nid', 'countries_documents', 'travel_documents',
    'financial_documents', 'professional_documents', 'personal_documents',
    'photos_signature', 'travel_history', 'all_documents',
];

$raw  = file_get_contents('php://input');
$body = json_decode($raw, true) ?: [];

$sysId        = trim($body['sys_id'] ?? '');
$newSmbFolder = trim($body['new_smb_folder'] ?? '');

if (!$sysId || !$newSmbFolder) {
    jsonOut(['success' => false, 'message' => 'sys_id ও new_smb_folder প্রয়োজন']);
}
if (!in_array($newSmbFolder, VALID_SMB_FOLDERS, true)) {
    jsonOut(['success' => false, 'message' => 'অবৈধ folder']);
}

try {
    $stmt = $pdo->prepare("
        SELECT td.*, t.sys_id as traveler_sys_id, t.name as traveler_name
        FROM traveler_documents td
        JOIN travelers t ON t.sys_id = td.traveler_id
        WHERE td.sys_id = ? LIMIT 1
    ");
    $stmt->execute([$sysId]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doc) {
        jsonOut(['success' => false, 'message' => 'Document পাওয়া যায়নি']);
    }
    if ($doc['status'] === 'deleted') {
        jsonOut(['success' => false, 'message' => 'Deleted document move করা যাবে না']);
    }
    if ($doc['doc_type'] === 'passport') {
        jsonOut(['success' => false, 'message' => 'Passport document move করা যাবে না — folder সবসময় passport_identity']);
    }
    if ($doc['smb_folder'] === $newSmbFolder) {
        jsonOut(['success' => false, 'message' => 'এই document ইতিমধ্যে এই folder-এই আছে']);
    }

    $pages = json_decode($doc['pages'] ?? '[]', true) ?: [];
    if (!$pages) {
        jsonOut(['success' => false, 'message' => 'এই document-এর কোনো page তথ্য নেই, move করা সম্ভব না']);
    }

    if (!class_exists('OMV_SMB_Manager')) {
        jsonOut(['success' => false, 'message' => 'SMB সংযোগ উপলব্ধ নেই']);
    }

    $SERVER_CUS_PATH = trim(@file_get_contents(__DIR__ . '/../../server-name.txt') ?? 'dev');
    $cleanSysId = preg_replace('/\s+/u', '', $doc['traveler_sys_id']);
    $cleanName  = preg_replace('/\s+/u', '', $doc['traveler_name']);
    $oldBase    = "{$SERVER_CUS_PATH}_travelers/{$cleanSysId}_{$cleanName}/{$doc['smb_folder']}";
    $newBase    = "{$SERVER_CUS_PATH}_travelers/{$cleanSysId}_{$cleanName}/{$newSmbFolder}";

    $omv = new OMV_SMB_Manager();

    // নতুন folder না থাকলে বানাও
    try { $omv->create_folder($newBase); } catch (Throwable $e) {}

    foreach ($pages as $page) {
        $filename = $page['filename'] ?? '';
        if (!$filename) {
            jsonOut(['success' => false, 'message' => 'একটা page-এর filename তথ্য নেই, move বাতিল']);
        }
        $oldPath = "{$oldBase}/{$filename}";
        $newPath = "{$newBase}/{$filename}";

        if (!$omv->move_item($oldPath, $newPath)) {
            // partial move এড়াতে না পারলেও অন্তত জানিয়ে দেওয়া — যা সরানো হয়ে
            // গেছে সেটা আর ফেরানো যাচ্ছে না এই মুহূর্তে
            error_log("[move-document] Partial move failure for {$sysId} at file {$filename}");
            jsonOut(['success' => false, 'message' => "SMB move ব্যর্থ হয়েছে ({$filename}) — কিছু file সরে থাকতে পারে, Documents tab রিফ্রেশ করে চেক করুন"]);
        }
    }

    $pdo->prepare("UPDATE traveler_documents SET smb_folder = ? WHERE sys_id = ?")
        ->execute([$newSmbFolder, $sysId]);

    jsonOut(['success' => true, 'message' => 'Move সফল হয়েছে']);

} catch (Throwable $e) {
    error_log('[move-document] Error: ' . $e->getMessage());
    jsonOut(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

function jsonOut(array $data): never
{
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}