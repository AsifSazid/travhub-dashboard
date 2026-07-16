<?php
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

// SMB root prefix: _clients | _vendors | _accounts
$rootSuffix = match($userType) {
    'vendor'  => '_vendors',
    'account' => '_accounts',
    default   => '_clients',
};

// Build CamelCase entity name (same as _smbClientFolder pattern)
$words     = preg_split('/[\s_]+/', trim($userName));
$camelName = implode('', array_map('ucfirst', $words));
$camelName = preg_replace('/[^A-Za-z0-9\-]/', '', $camelName);

$entityFolder = ($userSysId ?: 'UNKNOWN') . '_' . $camelName;

// ── Build SMB context ─────────────────────────────────────────
// We'll use smbEnsureDir/smbBuildPath directly with custom root
$serverPrefix = trim(@file_get_contents(__DIR__ . '/../../server-name.txt') ?? '');
$root         = $serverPrefix . $rootSuffix; // e.g. "dev_clients"
$w            = safeFolderName($feWorkSysId);
$t            = safeFolderName($feTaskSysId);

// Sequential mkdir
$omv = new OMV_SMB_Manager();
_finSmbMkdir($omv, $root, $entityFolder);
_finSmbMkdir($omv, "$root/$entityFolder", $w);
_finSmbMkdir($omv, "$root/$entityFolder/$w", $t);
_finSmbMkdir($omv, "$root/$entityFolder/$w/$t", 'financial');

$smbDir = "$root/$entityFolder/$w/$t/financial";

function _finSmbMkdir(OMV_SMB_Manager $omv, string $parent, string $child): void {
    $r = $omv->create_folder("$parent/$child");
    if ($r !== true) {
        if (
            stripos((string)$r, 'NT_STATUS_OBJECT_NAME_COLLISION') === false &&
            stripos((string)$r, 'already exists') === false
        ) {
            error_log("[fin upload SMB mkdir] $parent/$child → $r");
        }
    }
}

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

    // Move to temp
    $tempLocal = sys_get_temp_dir() . '/fin_up_' . uniqid() . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $tempLocal)) {
        $errors[] = "File #{$idx}: Failed to move to temp"; continue;
    }

    // Safe filename: entityId + index + ext
    $suffix   = $idx === 0 ? '' : '_' . $idx;
    $safeName = safeFolderName(pathinfo($file['name'], PATHINFO_FILENAME))
              . '_' . time() . '_' . substr(md5(uniqid()), 0, 6) . '.' . $ext;

    $smbPath = "$smbDir/$safeName";
    $result  = $omv->paste_file($tempLocal, $smbPath);
    if (file_exists($tempLocal)) unlink($tempLocal);

    if ($result === true) {
        $uploaded[] = [
            'original_name' => $file['name'],
            'saved_name'    => $safeName,
            'smb_dir'       => $smbDir,
            'size'          => $file['size'],
            'ext'           => $ext,
        ];
    } else {
        $errors[] = "File #{$idx}: SMB upload failed — $result";
    }
}

// ── Update financial_entries.files_json ───────────────────────
if (!empty($uploaded)) {
    try {
        $stmt = $pdo->prepare("SELECT files_json FROM financial_entries WHERE sys_id = ? LIMIT 1");
        $stmt->execute([$entityId]);
        $existing  = json_decode($stmt->fetchColumn() ?: '[]', true) ?? [];
        $newFiles  = array_column($uploaded, 'saved_name');
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