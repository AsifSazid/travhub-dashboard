<?php
// FILE PATH: api/old_tasks/upload-file.php
// Uploads files to task's SMB folder (files/) without calling Gemini.
// Updates all_file_name in old_tasks DB.
// Naming: {task_sys_id}_01.ext / {task_sys_id}_01_01.ext / {task_sys_id}_01-01.png (PDF pages)

ob_start();
session_start();

require '../../server/db_connection.php';
require '../../server/make-smb-dir.php';
require_once '../../server/live_storage.php';
require_once '../../server/safe_folder_name.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$SERVER_CUS_PATH = trim(file_get_contents('../../server-name.txt'));

// ── Helper: SMB upload ──────────────────────────────────────
function uploadToSmb(string $localPath, string $smbDest): bool {
    $omv    = new OMV_SMB_Manager();
    $result = $omv->paste_file($localPath, $smbDest);
    if ($result !== true) {
        error_log("[task upload] SMB failed: $smbDest → $result");
        return false;
    }
    return true;
}

// ── Helper: PDF → PNG pages ─────────────────────────────────
function pdfToPngsSmb(string $pdfTmp, string $sysId, string $idxPad, int $totalFiles, string $smbDir): array {
    $saved = [];
    if (!extension_loaded('imagick')) return $saved;
    try {
        $im = new Imagick();
        $im->setResolution(150, 150);
        $im->readImage($pdfTmp);
        $pageCount = count($im);
        foreach ($im as $pNum => $page) {
            $page->setImageFormat('png');
            $page->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);
            $pagePad = str_pad($pNum + 1, 2, '0', STR_PAD_LEFT);
            if ($totalFiles === 1) {
                $name = $pageCount === 1
                    ? $sysId . '_01.png'
                    : $sysId . '_01-' . $pagePad . '.png';
            } else {
                $name = $pageCount === 1
                    ? $sysId . '_01_' . $idxPad . '.png'
                    : $sysId . '_01_' . $idxPad . '-' . $pagePad . '.png';
            }
            $tmp = sys_get_temp_dir() . '/' . $name;
            $page->writeImage($tmp);
            if (file_exists($tmp)) {
                if (uploadToSmb($tmp, "$smbDir/$name")) $saved[] = $name;
                @unlink($tmp);
            }
        }
        $im->clear(); $im->destroy();
    } catch (Exception $e) {
        error_log('[task upload pdfToPng] ' . $e->getMessage());
    }
    return $saved;
}

// ── Input ────────────────────────────────────────────────────
$taskId = $_GET['task_id'] ?? $_POST['task_id'] ?? null;
if (!$taskId) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'task_id required']);
    exit;
}
if (empty($_FILES['files']['name'][0])) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'No files uploaded']);
    exit;
}

// ── Get task + work + client info ────────────────────────────
$stmt = $pdo->prepare("
    SELECT t.sys_id, t.work_sys_id, t.all_file_name,
           w.client_sys_id, c.name AS client_name
    FROM old_tasks t
    JOIN com_works w ON w.sys_id = t.work_sys_id
    LEFT JOIN clients c ON c.sys_id = w.client_sys_id
    WHERE t.sys_id = ? LIMIT 1
");
$stmt->execute([$taskId]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$task) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Task not found']);
    exit;
}

// ── Build SMB path ───────────────────────────────────────────
$clientSysId = preg_replace('/\s+/u', '', $task['client_sys_id'] ?? '');
$words       = preg_split('/[\s_]+/', trim($task['client_name'] ?? 'Unknown'));
$camel       = implode('', array_map('ucfirst', $words));
$camel       = preg_replace('/[^A-Za-z0-9\-]/', '', $camel);
$clFolder    = $clientSysId . '_' . $camel;

$wFolder  = safeFolderName($task['work_sys_id']);
$tFolder  = safeFolderName($task['sys_id']);

$smbRoot  = $SERVER_CUS_PATH . '_clients';
$smbCl    = makeSMBDir($smbRoot,  $clFolder);
$smbWork  = makeSMBDir($smbCl,    $wFolder);
$smbTask  = makeSMBDir($smbWork,  $tFolder);
$smbFiles = makeSMBDir($smbTask,  'files');

if (str_starts_with($smbFiles, '❌')) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'SMB folder error: ' . $smbFiles]);
    exit;
}

// ── Count valid files for naming ─────────────────────────────
$validCount = count(array_filter($_FILES['files']['error'], fn($e) => $e === UPLOAD_ERR_OK));
$fileIndex  = 1;
$saved      = [];
$errors     = [];

// ── Existing file names from DB ──────────────────────────────
$existing = [];
try { $existing = json_decode($task['all_file_name'] ?? '[]', true) ?? []; } catch (Exception $e) { $existing = []; }

// ── Process each file ────────────────────────────────────────
foreach ($_FILES['files']['name'] as $i => $name) {
    if ($_FILES['files']['error'][$i] !== UPLOAD_ERR_OK) {
        $errors[] = "Error uploading: $name"; continue;
    }

    $ext    = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $tmp    = $_FILES['files']['tmp_name'][$i];
    $idxPad = str_pad($fileIndex, 2, '0', STR_PAD_LEFT);

    if ($ext === 'pdf') {
        $pages = pdfToPngsSmb($tmp, $tFolder, $idxPad, $validCount, $smbFiles);
        if ($pages) {
            $saved = array_merge($saved, $pages);
        } else {
            $errors[] = "PDF conversion failed: $name";
        }
    } else {
        $safeName = $validCount === 1
            ? $tFolder . '_01.' . $ext
            : $tFolder . '_01_' . $idxPad . '.' . $ext;

        if (uploadToSmb($tmp, "$smbFiles/$safeName")) {
            $saved[] = $safeName;
        } else {
            $errors[] = "Upload failed: $name";
        }
    }
    $fileIndex++;
}

// ── Update DB ────────────────────────────────────────────────
if (!empty($saved)) {
    $allFiles = array_values(array_unique(array_merge($existing, $saved)));
    $pdo->prepare("UPDATE old_tasks SET all_file_name = ? WHERE sys_id = ?")
        ->execute([json_encode($allFiles), $taskId]);
}

ob_clean();
echo json_encode([
    'success'  => count($saved) > 0,
    'uploaded' => $saved,
    'errors'   => $errors,
    'message'  => count($saved) . ' file(s) uploaded',
]);