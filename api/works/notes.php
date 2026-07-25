<?php
/**
 * FILE PATH: /api/works/notes.php
 * Mind Board rich notes — text / image / audio / video / file / pdf_images
 *
 * Supports TWO board types via board_type column:
 *   board_type='task' → Task Mind Board (task_sys_id required)
 *   board_type='work' → Work Mind Board (work_sys_id only, task_sys_id NULL)
 *
 * GET  ?action=list&task_sys_id=X        → task board notes
 * GET  ?action=list&work_sys_id=X        → work board notes
 * POST action=store { task_sys_id|work_sys_id, work_sys_id, content }
 * POST (multipart)  action=upload + file field
 * POST action=delete { note_sys_id }
 * POST action=reorder { items:[{sys_id, sort_order}] }
 */

ob_start();
session_start();
date_default_timezone_set('Asia/Dhaka');

require_once '../server/api_bootstrap.php';
require_once '../server/db_connection.php';
require_once '../server/sys_id_generator_v2.php';
require_once '../server/generate_meta_data.php';
require_once '../server/live_storage.php';
require_once '../server/safe_folder_name.php';
require_once '../server/smb_upload_handler.php';

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

// ── Helpers ───────────────────────────────────────────────────

/**
 * tasks JOIN works (current) অথবা old_tasks JOIN com_works (legacy) — দুটোতেই try করে ctx বানাও
 */
function _notesGetCtx(PDO $pdo, string $taskSysId): array {
    // Current system: tasks + works
    // works table এ client info JSON column এ: client_info = {"sys_id":"...","name":"..."}
    $stmt = $pdo->prepare("
        SELECT t.sys_id AS task_sys_id,
               t.work_sys_id,
               JSON_UNQUOTE(JSON_EXTRACT(w.client_info, '$.sys_id')) AS client_sys_id,
               JSON_UNQUOTE(JSON_EXTRACT(w.client_info, '$.name'))   AS client_name
        FROM tasks t
        JOIN works w ON w.sys_id = t.work_sys_id
        WHERE t.sys_id = ?
        LIMIT 1
    ");
    $stmt->execute([$taskSysId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // Legacy system fallback: old_tasks + com_works (com_works এ আলাদা column আছে)
    if (!$row) {
        $stmt2 = $pdo->prepare("
            SELECT t.sys_id AS task_sys_id,
                   t.work_sys_id,
                   w.client_sys_id,
                   w.client_name
            FROM old_tasks t
            JOIN com_works w ON w.sys_id = t.work_sys_id
            WHERE t.sys_id = ?
            LIMIT 1
        ");
        $stmt2->execute([$taskSysId]);
        $row = $stmt2->fetch(PDO::FETCH_ASSOC);
    }

    if (!$row) throw new Exception("Task not found: $taskSysId");
    if (empty($row['client_sys_id'])) throw new Exception("Work has no client — cannot resolve SMB path");

    return [
        'client_sys_id' => $row['client_sys_id'],
        'client_name'   => $row['client_name'],
        'work_sys_id'   => $row['work_sys_id'],
        'task_sys_id'   => $row['task_sys_id'],
        'module'        => 'notes',
    ];
}

/** {note_sys_id}_01.ext */
function _noteFileName(string $noteSysId, string $ext): string {
    return $noteSysId . '_01.' . $ext;
}

/** PDF pages: {note_sys_id}_01.png (1pg) or {note_sys_id}_01-01.png, _01-02.png ... */
function _noteConvertPdf(string $tmpPath, string $noteSysId, array $ctx): array {
    if (!class_exists('Imagick')) return [];
    try {
        $omv  = new OMV_SMB_Manager();
        $base = smbBuildPath($ctx); // {prefix}_clients/…/notes

        $im = new Imagick();
        $im->setResolution(150, 150);
        $im->readImage($tmpPath);
        $pageCount = $im->getNumberImages();

        $pages = [];
        foreach ($im as $pNum => $page) {
            $page->setImageFormat('png');
            $page->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);
            $pagePad = str_pad($pNum + 1, 2, '0', STR_PAD_LEFT);

            // Single page → {sys_id}_01.png | Multi page → {sys_id}_01-01.png
            $pngName  = $pageCount === 1
                ? $noteSysId . '_01.png'
                : $noteSysId . '_01-' . $pagePad . '.png';

            $tmpPng = sys_get_temp_dir() . '/note_pg_' . uniqid() . '.png';
            $page->writeImage($tmpPng);

            $omv->paste_file($tmpPng, "{$base}/{$pngName}");
            if (file_exists($tmpPng)) unlink($tmpPng);

            $pages[] = $pngName;
        }
        $im->clear();
        $im->destroy();
        return $pages;
    } catch (Exception $e) {
        error_log('[notes pdf convert] ' . $e->getMessage());
        return [];
    }
}

/** sort_order এর পরের value — task or work */
function _nextSort(PDO $pdo, string $id, bool $byWork = false): int {
    $field = $byWork ? 'work_sys_id' : 'task_sys_id';
    $s = $pdo->prepare("SELECT MAX(sort_order) FROM task_notes WHERE board_type=? AND {$field}=?");
    $s->execute([$byWork ? 'work' : 'task', $id]);
    return ((int)$s->fetchColumn()) + 1;
}

/** Work context (client info) from works table directly — no task needed */
function _notesGetCtxByWork(PDO $pdo, string $workSysId): array {
    $stmt = $pdo->prepare("
        SELECT w.sys_id AS work_sys_id,
               JSON_UNQUOTE(JSON_EXTRACT(w.client_info, '$.sys_id')) AS client_sys_id,
               JSON_UNQUOTE(JSON_EXTRACT(w.client_info, '$.name'))   AS client_name
        FROM works w
        WHERE w.sys_id = ?
        LIMIT 1
    ");
    $stmt->execute([$workSysId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return ['work_sys_id' => $workSysId, 'task_sys_id' => null, 'client_sys_id' => null, 'client_name' => 'unknown'];
    return [
        'work_sys_id'  => $row['work_sys_id'],
        'task_sys_id'  => null,
        'client_sys_id'=> $row['client_sys_id'] ?? null,
        'client_name'  => $row['client_name']   ?? 'unknown',
    ];
}

try {
    switch ($action) {

        // ── LIST ──────────────────────────────────────────────
        case 'list':
            $taskSysId = $_GET['task_sys_id'] ?? '';
            $workSysId = $_GET['work_sys_id']  ?? '';
            $byWork    = !$taskSysId && $workSysId;

            if (!$taskSysId && !$workSysId) throw new Exception('task_sys_id or work_sys_id required');

            if ($byWork) {
                $stmt = $pdo->prepare("
                    SELECT * FROM task_notes WHERE board_type = 'work' AND work_sys_id = ?
                    ORDER BY sort_order ASC, id ASC
                ");
                $stmt->execute([$workSysId]);
            } else {
                $stmt = $pdo->prepare("
                    SELECT * FROM task_notes WHERE board_type = 'task' AND task_sys_id = ?
                    ORDER BY sort_order ASC, id ASC
                ");
                $stmt->execute([$taskSysId]);
            }
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as &$r) {
                $r['meta_data']  = $r['meta_data']  ? json_decode($r['meta_data'],  true) : [];
                $r['pages_json'] = $r['pages_json'] ? json_decode($r['pages_json'], true) : null;
                // Serve URL — serve.php handles SMB fetch
                $r['serve_url'] = $r['sys_id']
                    ? '../file/serve.php?note_id=' . urlencode($r['sys_id'])
                    : null;
            }

            ob_clean();
            echo json_encode(['status' => 'success', 'data' => $rows]);
            break;

        // ── STORE TEXT NOTE ───────────────────────────────────
        case 'store':
            $taskSysId = $body['task_sys_id'] ?? '';
            $workSysId = $body['work_sys_id'] ?? '';
            $content   = trim($body['content'] ?? '');
            $byWork    = !$taskSysId && $workSysId;

            if (!$taskSysId && !$workSysId) throw new Exception('task_sys_id or work_sys_id required');
            if (!$workSysId && $taskSysId) {
                // Try to resolve work_sys_id from task
                $tw = $pdo->prepare("SELECT work_sys_id FROM tasks WHERE sys_id=? LIMIT 1");
                $tw->execute([$taskSysId]);
                $workSysId = $tw->fetchColumn() ?: '';
            }
            if (!$content) throw new Exception('content required');

            $boardType = $byWork ? 'work' : 'task';
            $ids  = generateV2IDs($pdo, 'task_notes');
            $meta = buildMetaData(null, $userName);

            $pdo->prepare("
                INSERT INTO task_notes
                    (uuid, sys_id, task_sys_id, work_sys_id, board_type, note_type, content, sort_order, created_by, meta_data)
                VALUES (?, ?, ?, ?, ?, 'text', ?, ?, ?, ?)
            ")->execute([
                $ids['uuid'], $ids['sys_id'],
                $byWork ? null : $taskSysId,
                $workSysId,
                $boardType,
                $content,
                _nextSort($pdo, $byWork ? $workSysId : $taskSysId, $byWork),
                $userName, $meta,
            ]);

            ob_clean();
            echo json_encode(['status' => 'success', 'sys_id' => $ids['sys_id']]);
            break;

        // ── UPLOAD MEDIA NOTE ─────────────────────────────────
        case 'upload':
            $taskSysId = $_POST['task_sys_id'] ?? '';
            $workSysId = $_POST['work_sys_id'] ?? '';
            $caption   = trim($_POST['content'] ?? $_POST['caption'] ?? '');
            $byWork    = !$taskSysId && $workSysId;

            if (!$taskSysId && !$workSysId) throw new Exception('task_sys_id or work_sys_id required');
            if (!$workSysId && $taskSysId) {
                $tw = $pdo->prepare("SELECT work_sys_id FROM tasks WHERE sys_id=? LIMIT 1");
                $tw->execute([$taskSysId]);
                $workSysId = $tw->fetchColumn() ?: '';
            }
            if (empty($_FILES['file'])) throw new Exception('No file uploaded');

            $file     = $_FILES['file'];
            $origName = $file['name'];
            $tmpPath  = $file['tmp_name'];
            $fileSize = $file['size'];
            $mimeType = $file['type'] ?: 'application/octet-stream';

            if (function_exists('mime_content_type')) {
                $detected = mime_content_type($tmpPath);
                if ($detected) $mimeType = $detected;
            }

            if ($file['error'] !== UPLOAD_ERR_OK) throw new Exception('Upload error: ' . $file['error']);

            // ── Step 1: resolve SMB ctx (client path) ─────────
            $ctx = $byWork ? _notesGetCtxByWork($pdo, $workSysId) : _notesGetCtx($pdo, $taskSysId);
            $boardType = $byWork ? 'work' : 'task';

            // ── Step 2: generate note IDs ─────────────────────
            $ids     = generateV2IDs($pdo, 'task_notes');
            $noteId  = $ids['sys_id'];
            $ext     = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            $meta    = buildMetaData(null, $userName);
            $sortId  = $byWork ? $workSysId : $taskSysId;
            $maxSort = _nextSort($pdo, $sortId, $byWork);

            // ── Step 3: ensure SMB notes dir exists ───────────
            smbEnsureDir($ctx);
            $smbBase = smbBuildPath($ctx); // {prefix}_clients/…/notes
            $omv     = new OMV_SMB_Manager();

            // ── Step 4: PDF → PNG branch ──────────────────────
            if ($mimeType === 'application/pdf') {
                $pages = _noteConvertPdf($tmpPath, $noteId, $ctx);

                if (!empty($pages)) {
                    // Success — pdf_images note
                    $fileName = $noteId . '_01.pdf'; // original PDF reference
                    // PDF original ও SMB এ রাখি (optional, serve এ লাগতে পারে)
                    $omv->paste_file($tmpPath, "{$smbBase}/{$fileName}");

                    $pdo->prepare("
                        INSERT INTO task_notes
                            (uuid, sys_id, task_sys_id, work_sys_id, board_type, note_type, content,
                             file_name, file_path, file_size, mime_type, pages_json,
                             sort_order, created_by, meta_data)
                        VALUES (?, ?, ?, ?, ?, 'pdf_images', ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ")->execute([
                        $ids['uuid'], $noteId, $byWork ? null : $taskSysId, $workSysId, $boardType,
                        $caption ?: null,
                        $fileName,
                        $smbBase . '/' . $fileName,
                        $fileSize, $mimeType,
                        json_encode($pages),
                        $maxSort, $userName, $meta,
                    ]);

                    ob_clean();
                    echo json_encode([
                        'status'    => 'success',
                        'sys_id'    => $noteId,
                        'note_type' => 'pdf_images',
                        'pages'     => count($pages),
                    ]);
                    break;
                }
                // Imagick নেই বা fail → normal file হিসেবে fall through
            }

            // ── Step 5: normal file upload ────────────────────
            $noteType = 'file';
            if (str_starts_with($mimeType, 'image/'))     $noteType = 'image';
            elseif (str_starts_with($mimeType, 'audio/')) $noteType = 'audio';
            elseif (str_starts_with($mimeType, 'video/')) $noteType = 'video';

            $fileName = _noteFileName($noteId, $ext); // {note_sys_id}_01.ext
            $smbPath  = "{$smbBase}/{$fileName}";

            // Temp এ move করে SMB তে upload, তারপর delete
            $tempLocal = sys_get_temp_dir() . '/note_up_' . uniqid() . '.' . $ext;
            if (!move_uploaded_file($tmpPath, $tempLocal)) throw new Exception('Failed to move to temp');
            $omv->paste_file($tempLocal, $smbPath);
            if (file_exists($tempLocal)) unlink($tempLocal);

            $pdo->prepare("
                INSERT INTO task_notes
                    (uuid, sys_id, task_sys_id, work_sys_id, board_type, note_type, content,
                     file_name, file_path, file_size, mime_type,
                     sort_order, created_by, meta_data)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ")->execute([
                $ids['uuid'], $noteId, $byWork ? null : $taskSysId, $workSysId, $boardType,
                $noteType, $caption ?: null,
                $fileName,
                $smbPath,
                $fileSize, $mimeType,
                $maxSort, $userName, $meta,
            ]);

            ob_clean();
            echo json_encode([
                'status'    => 'success',
                'sys_id'    => $noteId,
                'note_type' => $noteType,
                'serve_url' => '../file/serve.php?note_id=' . urlencode($noteId),
            ]);
            break;

        // ── DELETE SINGLE PAGE (pdf_images) ──────────────────
        case 'delete_page':
            $noteSysId = $body['note_sys_id'] ?? '';
            $pageIndex = $body['page_index'] ?? null;
            if (!$noteSysId) throw new Exception('note_sys_id required');
            if ($pageIndex === null) throw new Exception('page_index required');

            $n = $pdo->prepare("SELECT * FROM task_notes WHERE sys_id=? AND note_type='pdf_images' LIMIT 1");
            $n->execute([$noteSysId]);
            $note = $n->fetch(PDO::FETCH_ASSOC);
            if (!$note) throw new Exception('Note not found');

            $pages     = json_decode($note['pages_json'] ?? '[]', true) ?? [];
            $pageIndex = (int)$pageIndex;
            if (!isset($pages[$pageIndex])) throw new Exception('Page not found');

            $ctx     = ($note['board_type'] ?? 'task') === 'work'
                ? _notesGetCtxByWork($pdo, $note['work_sys_id'])
                : _notesGetCtx($pdo, $note['task_sys_id']);
            $omv     = new OMV_SMB_Manager();
            $base    = smbBuildPath($ctx);

            // ── Step 1: deleted page PNG SMB থেকে delete ─────
            try { $omv->delete_file("{$base}/{$pages[$pageIndex]}"); } catch(Exception $e) { error_log('[delete_page] ' . $e->getMessage()); }

            // ── Step 2: pages_json update ─────────────────────
            array_splice($pages, $pageIndex, 1);
            $remainingPages = array_values($pages);

            // ── Step 3: remaining pages থেকে নতুন PDF বানাও ──
            if (!empty($remainingPages) && class_exists('Imagick') && $note['file_name']) {
                try {
                    $tmpDir = sys_get_temp_dir() . '/pdf_rebuild_' . uniqid();
                    mkdir($tmpDir, 0755, true);

                    // প্রতিটা PNG SMB থেকে local temp এ নামাও
                    $localPngs = [];
                    foreach ($remainingPages as $i => $pgName) {
                        $localPng = "{$tmpDir}/page_{$i}.png";
                        if ($omv->get_file("{$base}/{$pgName}", $localPng)) {
                            $localPngs[] = $localPng;
                        }
                    }

                    if (!empty($localPngs)) {
                        $imk = new Imagick();
                        foreach ($localPngs as $png) {
                            $page = new Imagick($png);
                            $page->setImageFormat('pdf');
                            $imk->addImage($page);
                            $page->destroy();
                        }
                        $newPdfPath = "{$tmpDir}/{$note['file_name']}.pdf";
                        $imk->writeImages($newPdfPath, true);
                        $imk->destroy();
                        $omv->paste_file($newPdfPath, "{$base}/{$note['file_name']}");
                        unlink($newPdfPath);
                    }

                    foreach ($localPngs as $f) { if (file_exists($f)) unlink($f); }
                    if (is_dir($tmpDir)) rmdir($tmpDir);

                } catch (Exception $rebuildErr) {
                    error_log('[delete_page rebuild] ' . $rebuildErr->getMessage());
                }
            }

            // ── Step 4: DB update ─────────────────────────────
            $pdo->prepare("UPDATE task_notes SET pages_json=? WHERE sys_id=?")
                ->execute([json_encode($remainingPages), $noteSysId]);

            ob_clean();
            echo json_encode(['status' => 'success', 'pages' => $remainingPages]);
            break;

        // ── DELETE ────────────────────────────────────────────
        case 'delete':
            $noteSysId = $body['note_sys_id'] ?? $body['sys_id'] ?? '';
            if (!$noteSysId) throw new Exception('note_sys_id required');

            $n = $pdo->prepare("SELECT * FROM task_notes WHERE sys_id=? LIMIT 1");
            $n->execute([$noteSysId]);
            $note = $n->fetch(PDO::FETCH_ASSOC);

            if ($note) {
                try {
                    $ctx = ($note['board_type'] ?? 'task') === 'work'
                        ? _notesGetCtxByWork($pdo, $note['work_sys_id'])
                        : _notesGetCtx($pdo, $note['task_sys_id']);
                    $omv  = new OMV_SMB_Manager();
                    $base = smbBuildPath($ctx);

                    // Main file delete
                    if ($note['file_name']) {
                        $omv->delete_file("{$base}/{$note['file_name']}");
                    }

                    // PDF pages delete
                    if ($note['pages_json']) {
                        $pages = json_decode($note['pages_json'], true) ?? [];
                        foreach ($pages as $pg) {
                            $omv->delete_file("{$base}/{$pg}");
                        }
                    }
                } catch (Exception $delErr) {
                    error_log('[notes delete SMB] ' . $delErr->getMessage());
                }
            }

            $pdo->prepare("DELETE FROM task_notes WHERE sys_id=?")->execute([$noteSysId]);
            ob_clean();
            echo json_encode(['status' => 'success']);
            break;

        // ── REORDER ───────────────────────────────────────────
        case 'reorder':
            $items = $body['items'] ?? [];
            if (empty($items)) throw new Exception('items required');
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