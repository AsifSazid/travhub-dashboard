<?php
// FILE PATH: api/financial_entries/upload-file.php
/**
 * FILE PATH: /api/financial_entries/upload-file.php
 * Finance file upload — receive/payment/invoice documents
 *
 * POST: multipart/form-data
 *   entity_type:  'receive' | 'payment' | 'invoice'
 *   entity_id:    sys_id of the financial entry
 *   work_sys_id:  work sys_id
 *   task_sys_id:  task sys_id (optional)
 *   files[]:      uploaded files
 *
 * SMB path structure:
 *   CLIENT:  dev_clients/{clientSysId}_{ClientName}/{work_sys_id}/{task_sys_id}/financial/{file}
 *   VENDOR:  dev_vendors/{vendorSysId}_{VendorName}/{work_sys_id}/{task_sys_id}/financial/{file}
 *   ACCOUNT: dev_accounts/{accSysId}_{AccName}/{work_sys_id}/{task_sys_id}/financial/{file}
 *
 * No local storage — SMB only.
 * Files served via /api/file/serve.php?fin_file_id=SYS_ID
 */

session_start();
ob_start();
date_default_timezone_set('Asia/Dhaka');

require_once '../../server/db_connection.php';
require_once '../../server/smb_upload_handler.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean(); http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']); exit;
}

// ── Input ─────────────────────────────────────────────────────
$entityType = $_POST['entity_type'] ?? '';  // receive | payment | invoice
$entityId   = $_POST['entity_id']   ?? '';  // financial entry sys_id
$workSysId  = $_POST['work_sys_id'] ?? '';
$taskSysId  = $_POST['task_sys_id'] ?? '';

if (!$entityType || !$entityId || !$workSysId) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'entity_type, entity_id, work_sys_id required']); exit;
}

if (!in_array($entityType, ['receive', 'payment', 'invoice'], true)) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid entity_type']); exit;
}

if (empty($_FILES['files'])) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'No files uploaded']); exit;
}

// ── Get entity context from DB ────────────────────────────────
// financial_entries table: user_type (client/vendor/account), user_sys_id, user_name, vendor_type
try {
    $stmt = $pdo->prepare("
        SELECT user_type, user_sys_id, user_name, vendor_type, work_sys_id, task_sys_id
        FROM financial_entries WHERE sys_id = ? LIMIT 1
    ");
    $stmt->execute([$entityId]);
    $entry = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $entry = null;
}

// Fallback: use posted work/task sys_id if not in entry
$feWorkSysId = $entry['work_sys_id'] ?? $workSysId;
$feTaskSysId = $entry['task_sys_id'] ?? $taskSysId ?: 'general';
$userType    = $entry['user_type']   ?? 'client';
$userSysId   = $entry['user_sys_id'] ?? '';
$userName    = $entry['user_name']   ?? '';

// ── Determine SMB root + entity name ─────────────────────────
// Fetch fresh name from DB if not in entry
if (!$userName && $userSysId) {
    try {
        if ($userType === 'client') {
            $s = $pdo->prepare("SELECT name FROM clients WHERE sys_id = ? LIMIT 1");
        } elseif ($userType === 'vendor') {
            $s = $pdo->prepare("SELECT name FROM vendors WHERE sys_id = ? LIMIT 1");
        } else {
            $s = $pdo->prepare("SELECT acc_name AS name FROM accounts WHERE sys_id = ? LIMIT 1");
        }
        $s->execute([$userSysId]);
        $userName = $s->fetchColumn() ?: 'Unknown';
    } catch (Exception $e) {
        $userName = 'Unknown';
    }
}

// ── Always use client path for financial files ────────────────
// Financial files go to dev_clients/{client}/{work}/{task}/financial/
// regardless of whether entry is client/vendor/account type

$SERVER_CUS_PATH = trim(file_get_contents('../../server-name.txt'));

// Get client info from com_works
try {
    $stmt = $pdo->prepare("
        SELECT w.client_sys_id, c.name AS client_name
        FROM com_works w
        LEFT JOIN clients c ON c.sys_id = w.client_sys_id
        WHERE w.sys_id = ? LIMIT 1
    ");
    $stmt->execute([$feWorkSysId]);
    $workRow = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $workRow = null;
}

$clientSysId = preg_replace('/\s+/u', '', $workRow['client_sys_id'] ?? 'UNKNOWN');
$clientWords = preg_split('/[\s_]+/', trim($workRow['client_name'] ?? 'Unknown'));
$clientCamel = implode('', array_map('ucfirst', $clientWords));
$clientCamel = preg_replace('/[^A-Za-z0-9\-]/', '', $clientCamel);
$clFolder    = $clientSysId . '_' . $clientCamel;

$wFolder = function_exists('safeFolderName') ? safeFolderName($feWorkSysId) : preg_replace('/[^A-Za-z0-9_\-]/', '', $feWorkSysId);
$tFolder = $feTaskSysId !== 'general' ? (function_exists('safeFolderName') ? safeFolderName($feTaskSysId) : $feTaskSysId) : '';

$smbRoot = $SERVER_CUS_PATH . '_clients';
$smbCl   = $smbRoot . '/' . $clFolder;
$smbWork = $smbCl . '/' . $wFolder;
$smbBase = $tFolder ? $smbWork . '/' . $tFolder : $smbWork;

// Ensure financial folder exists
require_once '../../server/make-smb-dir.php';
require_once '../../server/safe_folder_name.php';
$omvMkdir = new OMV_SMB_Manager();
foreach ([$smbRoot, $smbCl, $smbWork, $smbBase] as $dir) {
    $r = $omvMkdir->create_folder($dir);
}
$smbDir = makeSMBDir($smbBase, 'financial');

if (str_starts_with($smbDir, '❌')) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'SMB folder error: ' . $smbDir]);
    exit;
}

// smbDir already set above from client path
$omv = new OMV_SMB_Manager();

// ── Normalize files array ─────────────────────────────────────
$files = [];
if (is_array($_FILES['files']['name'])) {
    for ($i = 0; $i < count($_FILES['files']['name']); $i++) {
        $files[] = [
            'name'     => $_FILES['files']['name'][$i],
            'type'     => $_FILES['files']['type'][$i],
            'tmp_name' => $_FILES['files']['tmp_name'][$i],
            'error'    => $_FILES['files']['error'][$i],
            'size'     => $_FILES['files']['size'][$i],
        ];
    }
} else {
    $files[] = $_FILES['files'];
}

// ── Upload each file ──────────────────────────────────────────
$uploaded = [];
$errors   = [];

// Count valid files to determine single vs multiple naming
$validCount = count(array_filter($files, fn($f) => $f['error'] === UPLOAD_ERR_OK));
$fileIndex  = 1; // 1-based

foreach ($files as $idx => $file) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "File #{$idx}: Upload error code " . $file['error']; continue;
    }

    $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'doc', 'docx', 'xls', 'xlsx'];
    if (!in_array($ext, $allowed, true)) {
        $errors[] = "File #{$idx}: .{$ext} not allowed"; continue;
    }
    if ($file['size'] > 10 * 1024 * 1024) {
        $errors[] = "File #{$idx}: Too large (max 10MB)"; continue;
    }

    $tempLocal = sys_get_temp_dir() . '/fin_up_' . uniqid() . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $tempLocal)) {
        $errors[] = "File #{$idx}: Failed to move to temp"; continue;
    }

    $idxPad = str_pad($fileIndex, 2, '0', STR_PAD_LEFT);

    if ($ext === 'pdf') {
        // PDF → PNG via Imagick
        $pages = _finPdfToPngs($tempLocal, $entityId, $idxPad, $validCount, $smbDir, $omv);
        @unlink($tempLocal);
        if (!empty($pages)) {
            foreach ($pages as $pg) {
                $uploaded[] = ['original_name' => $file['name'], 'saved_name' => $pg, 'smb_dir' => $smbDir, 'size' => $file['size'], 'ext' => 'png'];
            }
        } else {
            $errors[] = "File #{$idx}: PDF conversion failed";
        }
        $fileIndex++; continue;
    }

    // Image / doc naming:
    // Single file total  → {sys_id}_01.ext
    // Multiple files     → {sys_id}_01_01.ext, {sys_id}_01_02.ext ...
    $safeName = $validCount === 1
        ? $entityId . '_01.' . $ext
        : $entityId . '_01_' . $idxPad . '.' . $ext;

    $result = $omv->paste_file($tempLocal, "$smbDir/$safeName");
    @unlink($tempLocal);

    if ($result === true) {
        $uploaded[] = ['original_name' => $file['name'], 'saved_name' => $safeName, 'smb_dir' => $smbDir, 'size' => $file['size'], 'ext' => $ext];
    } else {
        $errors[] = "File #{$idx}: SMB upload failed — $result";
    }
    $fileIndex++;
}

function _finPdfToPngs(string $pdfPath, string $entityId, string $idxPad, int $totalFiles, string $smbDir, OMV_SMB_Manager $omv): array {
    $saved = [];
    if (!extension_loaded('imagick')) { error_log('[fin upload] Imagick not loaded'); return $saved; }
    try {
        $im = new Imagick();
        $im->setResolution(150, 150);
        $im->readImage($pdfPath);
        $pageCount = count($im);
        foreach ($im as $pNum => $page) {
            $page->setImageFormat('png');
            $page->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);
            $pagePad = str_pad($pNum + 1, 2, '0', STR_PAD_LEFT);
            // Single file, single page  → {sys_id}_01.png
            // Single file, multi page   → {sys_id}_01-01.png, _01-02.png
            // Multi file, single page   → {sys_id}_01_02.png
            // Multi file, multi page    → {sys_id}_01_02-01.png, _02-02.png
            if ($totalFiles === 1) {
                $pngName = $pageCount === 1 ? $entityId . '_01.png' : $entityId . '_01-' . $pagePad . '.png';
            } else {
                $pngName = $pageCount === 1 ? $entityId . '_01_' . $idxPad . '.png' : $entityId . '_01_' . $idxPad . '-' . $pagePad . '.png';
            }
            $tmp = sys_get_temp_dir() . '/' . $pngName;
            $page->writeImage($tmp);
            if (file_exists($tmp)) {
                $r = $omv->paste_file($tmp, "$smbDir/$pngName");
                @unlink($tmp);
                if ($r === true) $saved[] = $pngName;
                else error_log("[fin upload] PDF page SMB fail: $pngName → $r");
            }
        }
        $im->clear(); $im->destroy();
    } catch (Exception $e) { error_log('[fin upload] PDF error: ' . $e->getMessage()); }
    return $saved;
}

if (!empty($uploaded)) {
    try {
        $stmt = $pdo->prepare("SELECT files_json FROM financial_entries WHERE sys_id = ? LIMIT 1");
        $stmt->execute([$entityId]);
        $existing  = json_decode($stmt->fetchColumn() ?: '[]', true) ?? [];
        $newFiles  = array_map(fn($u) => $smbDir . '/' . $u['saved_name'], $uploaded);
        $merged    = array_values(array_unique(array_merge($existing, $newFiles)));
        $pdo->prepare("UPDATE financial_entries SET files_json = ? WHERE sys_id = ?")
            ->execute([json_encode($merged), $entityId]);
    } catch (Exception $e) {
        error_log('[fin upload] files_json update failed: ' . $e->getMessage());
    }
}

ob_clean();
echo json_encode([
    'success'  => count($uploaded) > 0,
    'uploaded' => $uploaded,
    'errors'   => $errors,
    'smb_root' => $smbDir,
    'message'  => count($uploaded) . ' file(s) uploaded' . (count($errors) ? ', ' . count($errors) . ' failed' : ''),
]);