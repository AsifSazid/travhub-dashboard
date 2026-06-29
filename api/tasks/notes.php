<?php
/**
 * FILE PATH: /api/tasks/notes.php
 * Mind Board rich notes — text / image / audio / video / file
 *
 * GET  ?action=list&task_sys_id=THR-A26-TK-0001   → list all notes for task
 * POST action=store   { task_sys_id, work_sys_id, note_type, content }   → text note
 * POST (multipart)    action=upload  + file field                         → media note
 * POST action=delete  { sys_id }
 * POST action=reorder { items:[{sys_id, sort_order}] }
 *
 * Media files stored under: /storage/tasks/{work_sys_id}/{task_sys_id}/notes/
 * SMB path:                  {SERVER_CUS_PATH}_tasks/{work_sys_id}/{task_sys_id}/notes/
 */

ob_start();
session_start();
date_default_timezone_set('Asia/Dhaka');

require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';
require_once '../../server/sys_id_generator_v2.php';
require_once '../../server/generate_meta_data.php';
require_once '../../server/live_storage.php';
require_once '../../server/safe_folder_name.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

$body = [];
if ($method === 'POST' && strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $body['action'] ?? $action;
}
if ($method === 'POST' && !empty($_POST)) {
    $action = $_POST['action'] ?? $action;
}

$userName = $_SESSION['user_name'] ?? 'system';

// ── Local storage root ────────────────────────────────────────
$storageRoot = realpath(__DIR__ . '/../../storage/tasks');
if (!$storageRoot) {
    mkdir(__DIR__ . '/../../storage/tasks', 0755, true);
    $storageRoot = realpath(__DIR__ . '/../../storage/tasks');
}

function _taskNotesDir(string $workSysId, string $taskSysId): string {
    global $storageRoot;
    $wClean = safeFolderName($workSysId);
    $tClean = safeFolderName($taskSysId);
    $dir    = "{$storageRoot}/{$wClean}/{$tClean}/notes";
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    return $dir;
}

function _taskNotesUrl(string $workSysId, string $taskSysId, string $fileName): string {
    $wClean = safeFolderName($workSysId);
    $tClean = safeFolderName($taskSysId);
    return "/storage/tasks/{$wClean}/{$tClean}/notes/" . rawurlencode($fileName);
}

try {
    switch ($action) {

        // ── LIST ──────────────────────────────────────────────
        case 'list':
            $taskSysId = $_GET['task_sys_id'] ?? '';
            if (!$taskSysId) throw new Exception('task_sys_id required');

            $stmt = $pdo->prepare("
                SELECT * FROM task_notes WHERE task_sys_id = ?
                ORDER BY sort_order ASC, id ASC
            ");
            $stmt->execute([$taskSysId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Add public URL for media types
            foreach ($rows as &$r) {
                $r['meta_data'] = $r['meta_data'] ? json_decode($r['meta_data'], true) : [];
                if ($r['file_name'] && $r['work_sys_id']) {
                    $r['file_url'] = _taskNotesUrl($r['work_sys_id'], $r['task_sys_id'], $r['file_name']);
                } else {
                    $r['file_url'] = null;
                }
            }

            ob_clean();
            echo json_encode(['status' => 'success', 'data' => $rows]);
            break;

        // ── STORE TEXT NOTE ───────────────────────────────────
        case 'store':
            $taskSysId = $body['task_sys_id'] ?? '';
            $workSysId = $body['work_sys_id'] ?? '';
            $content   = trim($body['content'] ?? '');

            if (!$taskSysId) throw new Exception('task_sys_id required');
            if (!$workSysId) throw new Exception('work_sys_id required');
            if (!$content)   throw new Exception('content is required for text notes');

            $ids  = generateV2IDs($pdo, 'task_notes');
            $meta = buildMetaData(null, $userName);

            // Get next sort order
            $maxSort = (int)$pdo->prepare("SELECT MAX(sort_order) FROM task_notes WHERE task_sys_id=?")
                                 ->execute([$taskSysId]) ? 0 : 0;
            $stmt3 = $pdo->prepare("SELECT MAX(sort_order) FROM task_notes WHERE task_sys_id=?");
            $stmt3->execute([$taskSysId]);
            $maxSort = ((int)$stmt3->fetchColumn()) + 1;

            $pdo->prepare("
                INSERT INTO task_notes (uuid, sys_id, task_sys_id, work_sys_id, note_type, content, sort_order, created_by, meta_data)
                VALUES (?, ?, ?, ?, 'text', ?, ?, ?, ?)
            ")->execute([$ids['uuid'], $ids['sys_id'], $taskSysId, $workSysId, $content, $maxSort, $userName, $meta]);

            ob_clean();
            echo json_encode(['status' => 'success', 'message' => 'Note saved', 'sys_id' => $ids['sys_id']]);
            break;

        // ── UPLOAD MEDIA NOTE ─────────────────────────────────
        case 'upload':
            $taskSysId = $_POST['task_sys_id'] ?? '';
            $workSysId = $_POST['work_sys_id'] ?? '';
            $caption   = trim($_POST['caption'] ?? '');

            if (!$taskSysId) throw new Exception('task_sys_id required');
            if (!$workSysId) throw new Exception('work_sys_id required');
            if (empty($_FILES['file'])) throw new Exception('No file uploaded');

            $file     = $_FILES['file'];
            $origName = $file['name'];
            $tmpPath  = $file['tmp_name'];
            $fileSize = $file['size'];
            $mimeType = $file['type'];

            if ($file['error'] !== UPLOAD_ERR_OK) throw new Exception('Upload error: ' . $file['error']);

            // Determine note_type from mime
            $noteType = 'file';
            if (str_starts_with($mimeType, 'image/'))  $noteType = 'image';
            elseif (str_starts_with($mimeType, 'audio/')) $noteType = 'audio';
            elseif (str_starts_with($mimeType, 'video/')) $noteType = 'video';

            // Safe filename: sys_id + original ext
            $ids     = generateV2IDs($pdo, 'task_notes');
            $ext     = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            $safeName = safeFolderName(pathinfo($origName, PATHINFO_FILENAME)) . '_' . time() . '.' . $ext;

            // Save locally
            $dir      = _taskNotesDir($workSysId, $taskSysId);
            $destPath = $dir . '/' . $safeName;
            if (!move_uploaded_file($tmpPath, $destPath)) throw new Exception('Failed to save file locally');

            // Save to SMB
            $omv = new OMV_SMB_Manager();
            $serverCusPath = trim(@file_get_contents(__DIR__ . '/../../server-name.txt') ?? '');
            $wClean = safeFolderName($workSysId);
            $tClean = safeFolderName($taskSysId);
            $smbDir = "{$serverCusPath}_tasks/{$wClean}/{$tClean}/notes";
            $omv->create_folder($smbDir);
            $omv->paste_file($destPath, "{$smbDir}/{$safeName}");

            $meta = buildMetaData(null, $userName);
            $stmt5 = $pdo->prepare("SELECT MAX(sort_order) FROM task_notes WHERE task_sys_id=?");
            $stmt5->execute([$taskSysId]);
            $maxSort = ((int)$stmt5->fetchColumn()) + 1;

            $pdo->prepare("
                INSERT INTO task_notes (uuid, sys_id, task_sys_id, work_sys_id, note_type, content, file_name, file_path, file_size, mime_type, sort_order, created_by, meta_data)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ")->execute([
                $ids['uuid'], $ids['sys_id'], $taskSysId, $workSysId,
                $noteType, $caption ?: null,
                $safeName,
                "tasks/{$wClean}/{$tClean}/notes/{$safeName}",
                $fileSize, $mimeType,
                $maxSort, $userName, $meta,
            ]);

            ob_clean();
            echo json_encode([
                'status'   => 'success',
                'message'  => 'File uploaded',
                'sys_id'   => $ids['sys_id'],
                'file_url' => _taskNotesUrl($workSysId, $taskSysId, $safeName),
                'note_type'=> $noteType,
            ]);
            break;

        // ── DELETE ────────────────────────────────────────────
        case 'delete':
            $sysId = $body['sys_id'] ?? '';
            if (!$sysId) throw new Exception('sys_id required');

            // Fetch first to get file info
            $n = $pdo->prepare("SELECT * FROM task_notes WHERE sys_id=? LIMIT 1");
            $n->execute([$sysId]);
            $note = $n->fetch(PDO::FETCH_ASSOC);

            if ($note && $note['file_name']) {
                $dir      = _taskNotesDir($note['work_sys_id'], $note['task_sys_id']);
                $filePath = $dir . '/' . $note['file_name'];
                if (file_exists($filePath)) unlink($filePath);

                // SMB delete
                $omv = new OMV_SMB_Manager();
                $serverCusPath = trim(@file_get_contents(__DIR__ . '/../../server-name.txt') ?? '');
                $wClean = safeFolderName($note['work_sys_id']);
                $tClean = safeFolderName($note['task_sys_id']);
                $omv->delete_file("{$serverCusPath}_tasks/{$wClean}/{$tClean}/notes/{$note['file_name']}");
            }

            $pdo->prepare("DELETE FROM task_notes WHERE sys_id=?")->execute([$sysId]);
            ob_clean();
            echo json_encode(['status' => 'success', 'message' => 'Note deleted']);
            break;

        // ── REORDER ───────────────────────────────────────────
        case 'reorder':
            $items = $body['items'] ?? [];
            if (empty($items)) throw new Exception('items array required');
            $stmt = $pdo->prepare("UPDATE task_notes SET sort_order=? WHERE sys_id=?");
            foreach ($items as $item) {
                $stmt->execute([(int)$item['sort_order'], $item['sys_id']]);
            }
            ob_clean();
            echo json_encode(['status' => 'success']);
            break;

        default:
            throw new Exception("Unknown action: '{$action}'");
    }

} catch (Exception $e) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}