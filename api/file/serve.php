<?php
/**
 * FILE PATH: /api/file/serve.php
 * Streams SMB file to browser. SMB path never exposed to client.
 *
 * ?note_id=SYS_ID        → task note file
 * ?note_id=...&dl=1      → force download
 */

ob_start();
session_start();

if (empty($_SESSION['user_id']) && empty($_SESSION['user_name'])) {
    http_response_code(401); echo 'Unauthorized'; exit;
}

require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';
require_once '../../server/smb_upload_handler.php';

$noteId   = $_GET['note_id'] ?? '';
$download = !empty($_GET['dl']);

try {
    if ($noteId) {
        $stmt = $pdo->prepare("
            SELECT n.*, t.client_sys_id, t.client_name
            FROM task_notes n
            JOIN tasks t ON t.sys_id = n.task_sys_id
            WHERE n.sys_id = ? LIMIT 1
        ");
        $stmt->execute([$noteId]);
        $note = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$note || !$note['file_name']) {
            http_response_code(404); echo 'File not found'; exit;
        }

        $ctx = [
            'client_sys_id' => $note['client_sys_id'] ?? 'UNKNOWN',
            'client_name'   => $note['client_name']   ?? 'Unknown',
            'work_sys_id'   => $note['work_sys_id'],
            'task_sys_id'   => $note['task_sys_id'],
            'module'        => 'notes',
        ];

        // PDF pages: serve specific page image
        if ($note['note_type'] === 'pdf_images' && $note['pages_json']) {
            $pages   = json_decode($note['pages_json'], true) ?? [];
            $pageIdx = (int)($_GET['page'] ?? 0);
            $pgFile  = $pages[$pageIdx] ?? ($pages[0] ?? null);
            if (!$pgFile) { http_response_code(404); echo 'Page not found'; exit; }
            ob_end_clean();
            $ok = smbServeFile($ctx, $pgFile, 'image/png');
            if (!$ok) { http_response_code(404); echo 'File not available'; }
            exit;
        }

        if ($download) {
            header('Content-Disposition: attachment; filename="' . $note['file_name'] . '"');
        }

        ob_end_clean();
        $ok = smbServeFile($ctx, $note['file_name'], $note['mime_type'] ?? 'application/octet-stream');
        if (!$ok) { http_response_code(404); echo 'File not available'; }
        exit;
    }

    http_response_code(400); echo 'Missing file reference';

} catch (Exception $e) {
    http_response_code(500);
    error_log('[serve.php] ' . $e->getMessage());
}