<?php
/**
 * FILE PATH: api/travelers/rename-document.php
 *
 * Traveler document rename — SMB তে actual filename বদলায়, DB এর
 * suggested_filename_stem + pages কলাম sync রাখে (mismatch এড়াতে সবসময়
 * দুটোই একসাথে আপডেট করা হয়)।
 *
 * NOTE: doc_type='passport' rename করা যাবে না — এর filename systematic
 * pattern (current_passport_bio_page / previous_passport_bio_page_p{n})
 * মেনে চলে renewal-chain এর জন্য, ম্যানুয়াল rename সেই chain ভেঙে দেবে।
 *
 * INPUT (JSON POST):
 *   sys_id      — traveler_documents.sys_id
 *   new_stem    — নতুন filename stem (extension ছাড়া, page suffix ছাড়া)
 *
 * OUTPUT: { success, message, new_filenames? }
 */

require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';
require_once '../../server/live_storage.php';

header('Content-Type: application/json');
ini_set('display_errors', 0);

$raw  = file_get_contents('php://input');
$body = json_decode($raw, true) ?: [];

$sysId   = trim($body['sys_id'] ?? '');
$newStem = trim($body['new_stem'] ?? '');

if (!$sysId || !$newStem) {
    jsonOut(['success' => false, 'message' => 'sys_id ও new_stem প্রয়োজন']);
}

// শুধু নিরাপদ characters (a-z, 0-9, underscore) — SMB path এ সমস্যা এড়াতে
$newStem = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $newStem));
$newStem = trim($newStem, '_');
if ($newStem === '' || mb_strlen($newStem) > 80) {
    jsonOut(['success' => false, 'message' => 'নাম অবৈধ বা খুব বড়/ছোট']);
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
        jsonOut(['success' => false, 'message' => 'Deleted document rename করা যাবে না']);
    }
    if ($doc['doc_type'] === 'passport') {
        jsonOut(['success' => false, 'message' => 'Passport ফাইলের নাম systematic — ম্যানুয়ালি rename করা যাবে না (renewal chain এর জন্য প্রয়োজন)']);
    }

    $pages = json_decode($doc['pages'] ?? '[]', true) ?: [];
    if (!$pages) {
        jsonOut(['success' => false, 'message' => 'এই document-এর কোনো page তথ্য নেই, rename করা সম্ভব না']);
    }

    if (!class_exists('OMV_SMB_Manager')) {
        jsonOut(['success' => false, 'message' => 'SMB সংযোগ উপলব্ধ নেই']);
    }

    $SERVER_CUS_PATH = trim(@file_get_contents(__DIR__ . '/../../server-name.txt') ?? 'dev');
    $cleanSysId = preg_replace('/\s+/u', '', $doc['traveler_sys_id']);
    $cleanName  = preg_replace('/\s+/u', '', $doc['traveler_name']);
    $smbBase    = "{$SERVER_CUS_PATH}_travelers/{$cleanSysId}_{$cleanName}/{$doc['smb_folder']}";

    $omv = new OMV_SMB_Manager();
    $updatedPages = [];
    $multiPage = count($pages) > 1;

    foreach ($pages as $page) {
        $oldFilename = $page['filename'] ?? '';
        if (!$oldFilename) {
            jsonOut(['success' => false, 'message' => 'একটা page-এর filename তথ্য নেই, rename বাতিল']);
        }
        $ext = strtolower(pathinfo($oldFilename, PATHINFO_EXTENSION)) ?: 'jpg';
        $pageNo = $page['page_no'] ?? 1;
        $newFilename = $newStem . ($multiPage ? "_p{$pageNo}" : '') . ".{$ext}";

        $oldPath = "{$smbBase}/{$oldFilename}";
        $newPath = "{$smbBase}/{$newFilename}";

        if (!$omv->rename_item($oldPath, $newPath)) {
            // partial rename এড়াতে — যা এ পর্যন্ত rename হয়ে গেছে সেটা আর ফেরানো
            // যাচ্ছে না, তাই error log করে ইউজারকে জানানো হচ্ছে DB sync ব্যাহত হয়েছে
            error_log("[rename-document] Partial rename failure for {$sysId} at page {$pageNo}");
            jsonOut(['success' => false, 'message' => "SMB rename ব্যর্থ হয়েছে ({$oldFilename}) — কিছু page rename হয়ে থাকতে পারে, Documents tab রিফ্রেশ করে চেক করুন"]);
        }

        $page['filename'] = $newFilename;
        $updatedPages[] = $page;
    }

    $pdo->prepare("
        UPDATE traveler_documents
        SET suggested_filename_stem = ?, pages = ?
        WHERE sys_id = ?
    ")->execute([$newStem, json_encode($updatedPages, JSON_UNESCAPED_UNICODE), $sysId]);

    jsonOut(['success' => true, 'message' => 'Rename সফল হয়েছে', 'new_filenames' => array_column($updatedPages, 'filename')]);

} catch (Throwable $e) {
    error_log('[rename-document] Error: ' . $e->getMessage());
    jsonOut(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

function jsonOut(array $data): never
{
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}