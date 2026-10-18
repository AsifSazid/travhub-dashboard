<?php
/**
 * FILE PATH: api/visa-services/upload-approval-doc.php
 * Upload visa approval document
 *
 * - Stores in SMB: {SERVER}_travelers/{sys_id}_{name}/countries_documents/
 * - Inserts into traveler_documents (doc_type='visa')
 * - Saves reference in vs_travelers[traveler].approval_doc
 * - 24-hour lock after upload
 *
 * POST: multipart/form-data
 *   work_sys_id, traveler_sys_id, file
 */

ob_start();
session_start();
include_once '../../authenticate.php';
ob_end_clean();

require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';
require_once '../../server/sys_id_generator_v2.php';
require_once '../../server/live_storage.php'; // OMV_SMB_Manager

header('Content-Type: application/json');

function jsonErr(string $msg): never {
    echo json_encode(['status' => 'error', 'message' => $msg]);
    exit;
}

$workSysId  = trim($_POST['work_sys_id']     ?? '');
$travSysId  = trim($_POST['traveler_sys_id'] ?? '');

if (!$workSysId)  jsonErr('work_sys_id required');
if (!$travSysId)  jsonErr('traveler_sys_id required');
if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    jsonErr('No file uploaded');
}

// ── Fetch visa service ───────────────────────────────────────
$vsSt = $pdo->prepare("SELECT * FROM visa_services WHERE work_sys_id = ? LIMIT 1");
$vsSt->execute([$workSysId]);
$vsRow = $vsSt->fetch(PDO::FETCH_ASSOC);
if (!$vsRow) jsonErr('Visa service not found');

$travelers = json_decode($vsRow['vs_travelers'] ?? '[]', true) ?: [];
$travIdx   = null;
foreach ($travelers as $i => $t) {
    if (($t['sys_id'] ?? '') === $travSysId) { $travIdx = $i; break; }
}
if ($travIdx === null) jsonErr('Traveler not found in visa service');

// ── Check 24-hour lock ───────────────────────────────────────
$existing = $travelers[$travIdx]['approval_doc'] ?? null;
if ($existing) {
    $lockedAt = strtotime($existing['locked_at'] ?? '0');
    if ($lockedAt && time() > $lockedAt) {
        jsonErr('Approval document is locked — cannot be changed after 24 hours');
    }
}

// ── Fetch traveler for SMB path ──────────────────────────────
$trSt = $pdo->prepare("SELECT sys_id, name, server_path FROM travelers WHERE sys_id = ? LIMIT 1");
$trSt->execute([$travSysId]);
$traveler = $trSt->fetch(PDO::FETCH_ASSOC);
if (!$traveler) jsonErr('Traveler not found');

// ── Build SMB path (same as commit-documents.php) ───────────
$serverPrefix = trim(@file_get_contents(__DIR__ . '/../../server-name.txt') ?? 'dev');
$cleanSysId   = preg_replace('/\s+/u', '', $traveler['sys_id']);
$cleanName    = preg_replace('/\s+/u', '', $traveler['name']);
$smbFolder    = 'countries_documents';
$smbBase      = "{$serverPrefix}_travelers/{$cleanSysId}_{$cleanName}/{$smbFolder}";

// Original + safe filename
$origFilename = $_FILES['file']['name'];
$ext          = strtolower(pathinfo($origFilename, PATHINFO_EXTENSION));
$safeStem     = preg_replace('/[^a-zA-Z0-9_\-]/', '_', pathinfo($origFilename, PATHINFO_FILENAME));
$safeFilename = $safeStem . '.' . $ext;
$smbPath      = $smbBase . '/' . $safeFilename;

// server_path for DB (local filesystem reference)
$serverPath   = $traveler['server_path']
    ? rtrim($traveler['server_path'], '/') . '/' . $smbFolder
    : null;

// ── Upload to SMB (PDF → PNG via Imagick, images direct) ────
$tmpPath    = $_FILES['file']['tmp_name'];
$storedPages = [];

try {
    $omv = new OMV_SMB_Manager();
    $omv->create_folder($smbBase);

    if ($ext === 'pdf' && extension_loaded('imagick')) {
        // Convert PDF pages → PNG, upload each page
        $imagick = new Imagick();
        $imagick->setResolution(200, 200);
        $imagick->readImage($tmpPath);
        $totalPages = $imagick->getNumberImages();

        for ($i = 0; $i < $totalPages; $i++) {
            $imagick->setIteratorIndex($i);
            $imagick->setImageFormat('png');
            $pageNo      = $i + 1;
            $pageFile    = $safeStem . '_p' . $pageNo . '.png';
            $pageSmbPath = $smbBase . '/' . $pageFile;
            $pageTmp     = sys_get_temp_dir() . '/vs_appr_' . uniqid() . '.png';
            file_put_contents($pageTmp, $imagick->getImageBlob());
            $ok = $omv->paste_file($pageTmp, $pageSmbPath);
            @unlink($pageTmp);
            if ($ok === true) {
                $storedPages[] = ['page_no'=>$pageNo,'filename'=>$pageFile,'page_type'=>'visa','country'=>null];
            }
        }
        $imagick->clear();
        $imagick->destroy();
        // Use first page filename as the main reference
        $safeFilename = $storedPages[0]['filename'] ?? ($safeStem . '_p1.png');
        $smbPath      = $smbBase . '/' . $safeFilename;
    } else {
        // Image or PDF without Imagick — upload directly
        $result = $omv->paste_file($tmpPath, $smbPath);
        if ($result !== true) {
            jsonErr('SMB upload failed: ' . (is_string($result) ? $result : 'unknown'));
        }
        $storedPages[] = ['page_no'=>1,'filename'=>$safeFilename,'page_type'=>'visa','country'=>null];
    }
} catch (Throwable $e) {
    jsonErr('SMB error: ' . $e->getMessage());
}

if (empty($storedPages)) jsonErr('File upload failed — no pages stored');

// ── Insert into traveler_documents ───────────────────────────
$now      = date('Y-m-d H:i:s');
$v2       = generateV2IDs($pdo, 'traveler_documents');
$docSysId = $v2['sys_id'];
$metaJson = json_encode([
    'created_by_date' => [
        'by'          => $_SESSION['employee_id'] ?? $_SESSION['user_name'] ?? 'system',
        'date'        => $now,
        'via'         => 'visa_approval_upload',
        'work_sys_id' => $workSysId,
    ],
    'updated_by_date' => [],
], JSON_UNESCAPED_UNICODE);

$pagesJson = json_encode($storedPages, JSON_UNESCAPED_UNICODE);

$pdo->prepare("
    INSERT INTO traveler_documents (
        uuid, sys_id, traveler_id, batch_id,
        doc_type, doc_number,
        passport_status, summary, doc_data,
        confidence, needs_review, classification_mode,
        original_filename, suggested_filename_stem,
        smb_folder, server_path,
        page_count, pages,
        is_primary, status, meta_data
    ) VALUES (
        ?, ?, ?, NULL,
        'visa', NULL,
        NULL, 'Visa Approval Document', NULL,
        1.0, 0, 'manual',
        ?, ?,
        ?, ?,
        1, ?,
        0, 'active', ?
    )
")->execute([
    $v2['uuid'], $docSysId, $travSysId,
    $origFilename, $safeStem,
    $smbFolder, $serverPath,
    $pagesJson,
    $metaJson,
]);

// Build serve URL
$serveUrl = '/api/file/serve.php?doc_id=' . urlencode($docSysId) . '&file=' . urlencode($storedPages[0]['filename'] ?? $safeFilename);

// ── Generate opaque doc_key for public tracker ───────────────
$docKey = bin2hex(random_bytes(16));

// ── Update vs_travelers ──────────────────────────────────────
$travelers[$travIdx]['approval_doc'] = [
    'doc_sys_id'  => $docSysId,
    'filename'    => $safeFilename,
    'serve_url'   => $serveUrl,
    'smb_path'    => $smbPath,
    'doc_key'     => $docKey,
    'uploaded_at' => $now,
    'uploaded_by' => $_SESSION['employee_id'] ?? $_SESSION['user_name'] ?? 'system',
    'locked_at'   => date('Y-m-d H:i:s', strtotime('+24 hours')),
];

// Auto-set status to approved
if (($travelers[$travIdx]['app_status'] ?? '') !== 'approved') {
    $travelers[$travIdx]['app_status'] = 'approved';
    $travelers[$travIdx]['status_history'][] = [
        'status' => 'approved',
        'at'     => date('d/m/Y H:i'),
        'from'   => $travelers[$travIdx]['app_status'] ?? 'submitted',
    ];
}

$pdo->prepare("UPDATE visa_services SET vs_travelers = ? WHERE work_sys_id = ?")
    ->execute([json_encode($travelers, JSON_UNESCAPED_UNICODE), $workSysId]);

// ── Return fresh data ────────────────────────────────────────
$fresh = $pdo->prepare("SELECT * FROM visa_services WHERE work_sys_id = ? LIMIT 1");
$fresh->execute([$workSysId]);
$freshRow = $fresh->fetch(PDO::FETCH_ASSOC);
$freshRow['vs_travelers'] = json_decode($freshRow['vs_travelers'] ?? '[]', true);
$freshRow['meta_data']    = json_decode($freshRow['meta_data']    ?? '{}', true);

echo json_encode([
    'status'     => 'success',
    'message'    => 'Approval document uploaded',
    'doc_sys_id' => $docSysId,
    'serve_url'  => $serveUrl,
    'smb_path'   => $smbPath,
    'locked_at'  => $travelers[$travIdx]['approval_doc']['locked_at'],
    'data'       => $freshRow,
]);