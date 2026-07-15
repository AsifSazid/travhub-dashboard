<?php
/**
 * FILE PATH: /api/tasks/notes.php
 * Mind Board rich notes — text / image / audio / video / file
 *
 * SMB path structure:
 *   {prefix}_clients/{clientSysId}_{clientName}/{workSysId}/{taskSysId}/notes/{fileName}
 *
 * DB stores: file_name only
 * Served via: /api/file/serve.php?note_id=SYS_ID
 */

ob_start();
session_start();
date_default_timezone_set('Asia/Dhaka');

require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';
require_once '../../server/sys_id_generator_v2.php';
require_once '../../server/generate_meta_data.php';
require_once '../../server/smb_upload_handler.php';

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

function _noteFileUrl(string $noteSysId): string {
    $ipPort = trim(@file_get_contents(__DIR__ . '/../../ippath.txt') ?? '');
    return rtrim($ipPort, '/') . '/api/file/serve.php?note_id=' . rawurlencode($noteSysId);
}

// Build SMB context — client info comes from tasks table
function _getCtx(PDO $pdo, string $taskSysId, string $workSysId, string $module = 'notes'): array {
    $stmt = $pdo->prepare("SELECT client_sys_id, client_name FROM tasks WHERE sys_id = ? LIMIT 1");
    $stmt->execute([$taskSysId]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    return [
        'client_sys_id' => $task['client_sys_id'] ?? 'UNKNOWN',
        'client_name'   => $task['client_name']   ?? 'Unknown',
        'work_sys_id'   => $workSysId,
        'task_sys_id'   => $taskSysId,
        'module'        => $module,
    ];
}

try {
    switch ($action) {

        case 'list': {
            $taskSysId = $_GET['task_sys_id'] ?? '';
            if (!$taskSysId) throw new Exception('task_sys_id required');
            $stmt = $pdo->prepare("SELECT * FROM task_notes WHERE task_sys_id=? ORDER BY sort_order ASC, id ASC");
            $stmt->execute([$taskSysId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as &$r) {
                $r['meta_data'] = $r['meta_data'] ? json_decode($r['meta_data'], true) : [];
                $r['file_url']  = $r['file_name'] ? _noteFileUrl($r['sys_id']) : null;
            }
            unset($r);
            ob_clean();
            echo json_encode(['status' => 'success', 'data' => $rows]);
            break;
        }

        case 'store': {
            $taskSysId = $body['task_sys_id'] ?? '';
            $workSysId = $body['work_sys_id'] ?? '';
            $content   = trim($body['content'] ?? '');
            if (!$taskSysId) throw new Exception('task_sys_id required');
            if (!$workSysId) throw new Exception('work_sys_id required');
            if (!$content)   throw new Exception('content required');
            $ids  = generateV2IDs($pdo, 'task_notes');
            $meta = buildMetaData(null, $userName);
            $stmt = $pdo->prepare("SELECT MAX(sort_order) FROM task_notes WHERE task_sys_id=?");
            $stmt->execute([$taskSysId]);
            $maxSort = ((int)$stmt->fetchColumn()) + 1;
            $pdo->prepare("INSERT INTO task_notes (uuid,sys_id,task_sys_id,work_sys_id,note_type,content,sort_order,created_by,meta_data) VALUES (?,?,?,?,'text',?,?,?,?)")
                ->execute([$ids['uuid'],$ids['sys_id'],$taskSysId,$workSysId,$content,$maxSort,$userName,$meta]);
            ob_clean();
            echo json_encode(['status'=>'success','message'=>'Note saved','sys_id'=>$ids['sys_id']]);
            break;
        }

        case 'upload': {
            $taskSysId = $_POST['task_sys_id'] ?? '';
            $workSysId = $_POST['work_sys_id'] ?? '';
            $caption   = trim($_POST['content'] ?? $_POST['caption'] ?? '');
            if (!$taskSysId) throw new Exception('task_sys_id required');
            if (!$workSysId) throw new Exception('work_sys_id required');
            if (empty($_FILES['file'])) throw new Exception('No file uploaded');

            $ctx    = _getCtx($pdo, $taskSysId, $workSysId, 'notes');
            $upload = smbUploadFile($_FILES['file'], $ctx, $caption);
            if (!$upload['success']) throw new Exception('Upload failed: ' . $upload['error']);

            $ids  = generateV2IDs($pdo, 'task_notes');
            $meta = buildMetaData(null, $userName);
            $stmt = $pdo->prepare("SELECT MAX(sort_order) FROM task_notes WHERE task_sys_id=?");
            $stmt->execute([$taskSysId]);
            $maxSort = ((int)$stmt->fetchColumn()) + 1;

            $pdo->prepare("INSERT INTO task_notes (uuid,sys_id,task_sys_id,work_sys_id,note_type,content,file_name,file_size,mime_type,pages_json,sort_order,created_by,meta_data) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)")
                ->execute([
                    $ids['uuid'], $ids['sys_id'],
                    $taskSysId, $workSysId,
                    $upload['file_type'],             // 'pdf_images' or 'image'/'audio' etc
                    $caption ?: null,
                    $upload['file_name'],
                    $upload['file_size'],
                    $upload['mime_type'],
                    isset($upload['pages_json']) ? json_encode($upload['pages_json']) : null,
                    $maxSort, $userName, $meta,
                ]);

            ob_clean();
            echo json_encode(['status'=>'success','message'=>'File uploaded','sys_id'=>$ids['sys_id'],'file_url'=>_noteFileUrl($ids['sys_id']),'note_type'=>$upload['file_type'],'file_name'=>$upload['file_name']]);
            break;
        }

        case 'delete': {
            $sysId = $body['sys_id'] ?? $body['note_sys_id'] ?? '';
            if (!$sysId) throw new Exception('sys_id required');
            $stmt = $pdo->prepare("SELECT * FROM task_notes WHERE sys_id=? LIMIT 1");
            $stmt->execute([$sysId]);
            $note = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($note && $note['file_name']) {
                $ctx = _getCtx($pdo, $note['task_sys_id'], $note['work_sys_id'], 'notes');
                smbDeleteFile($ctx, $note['file_name']);
            }
            $pdo->prepare("DELETE FROM task_notes WHERE sys_id=?")->execute([$sysId]);
            ob_clean();
            echo json_encode(['status'=>'success','message'=>'Note deleted']);
            break;
        }

        case 'reorder': {
            $items = $body['items'] ?? [];
            if (empty($items)) throw new Exception('items required');
            $stmt = $pdo->prepare("UPDATE task_notes SET sort_order=? WHERE sys_id=?");
            foreach ($items as $item) $stmt->execute([(int)$item['sort_order'], $item['sys_id']]);
            ob_clean();
            echo json_encode(['status'=>'success']);
            break;
        }

        default: throw new Exception("Unknown action: '{$action}'");
    }

} catch (Exception $e) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}