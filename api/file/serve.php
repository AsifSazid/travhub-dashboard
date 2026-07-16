<?php
/**
 * FILE PATH: /api/file/serve.php
 * Central file serve gateway — all SMB files go through here.
 * SMB paths are NEVER exposed to the client.
 *
 * ── Current handlers ──────────────────────────────────────────
 * ?note_id=SYS_ID            → task Mind Board note (image/audio/file/pdf_images)
 * ?note_id=...&page=0        → specific PDF page
 * ?note_id=...&dl=1          → force download
 * ?smb_token=TOKEN           → signed SMB path (file explorer, financial etc)
 * ?smb_token=TOKEN&dl=1      → force download
 *
 * ── Adding new handlers ───────────────────────────────────────
 * 1. Add new query param check in the dispatcher below
 * 2. Write a _serveXxx() function following the same pattern
 * 3. That's it — auth, ob_clean, error handling already done
 *
 * ── Token (for smb_token) ─────────────────────────────────────
 * Generate: smbFileUrl($smbPath)  from smb_upload_handler.php
 * Verify:   smbVerifyToken($token) below
 */

ob_start();
session_start();

// ── Auth ──────────────────────────────────────────────────────
if (empty($_SESSION['user_id']) && empty($_SESSION['user_name'])) {
    ob_clean(); http_response_code(401); echo 'Unauthorized'; exit;
}

require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';
require_once '../../server/smb_upload_handler.php';
require_once '../../server/env.php';

$dl = !empty($_GET['dl']);

try {
    // ── Dispatcher ────────────────────────────────────────────
    if (!empty($_GET['note_id']))   { _serveNote($_GET['note_id'], $dl);   exit; }
    if (!empty($_GET['smb_token'])) { _serveToken($_GET['smb_token'], $dl); exit; }
    // Future handlers — add here:
    // if (!empty($_GET['conf_id']))  { _serveConf($_GET['conf_id'], $dl);  exit; }
    // if (!empty($_GET['doc_id']))   { _serveDoc($_GET['doc_id'], $dl);    exit; }
    // if (!empty($_GET['fin_id']))   { _serveFin($_GET['fin_id'], $dl);    exit; }

    ob_clean(); http_response_code(400); echo 'Missing file reference';

} catch (Exception $e) {
    ob_clean(); http_response_code(500);
    error_log('[serve.php] ' . $e->getMessage());
}

// ═══════════════════════════════════════════════════════════════
// HANDLERS
// ═══════════════════════════════════════════════════════════════

// ── Task Mind Board notes ─────────────────────────────────────
function _serveNote(string $noteId, bool $dl): void
{
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT n.*, t.client_sys_id, t.client_name
        FROM task_notes n
        LEFT JOIN tasks t ON t.sys_id = n.task_sys_id
        WHERE n.sys_id = ? LIMIT 1
    ");
    $stmt->execute([$noteId]);
    $note = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$note || !$note['file_name']) {
        ob_clean(); http_response_code(404); echo 'File not found'; return;
    }

    $ctx = [
        'client_sys_id' => $note['client_sys_id'] ?? 'UNKNOWN',
        'client_name'   => $note['client_name']   ?? 'Unknown',
        'work_sys_id'   => $note['work_sys_id'],
        'task_sys_id'   => $note['task_sys_id'],
        'module'        => 'notes',
    ];

    // PDF pages
    if ($note['note_type'] === 'pdf_images' && $note['pages_json']) {
        $pages   = json_decode($note['pages_json'], true) ?? [];
        $pageIdx = (int)($_GET['page'] ?? 0);
        $pgFile  = $pages[$pageIdx] ?? ($pages[0] ?? null);
        if (!$pgFile) { ob_clean(); http_response_code(404); echo 'Page not found'; return; }
        ob_end_clean();
        if (!smbServeFile($ctx, $pgFile, 'image/png')) {
            http_response_code(404); echo 'File not available';
        }
        return;
    }

    if ($dl) header('Content-Disposition: attachment; filename="' . $note['file_name'] . '"');
    ob_end_clean();
    if (!smbServeFile($ctx, $note['file_name'], $note['mime_type'] ?? 'application/octet-stream')) {
        http_response_code(404); echo 'File not available';
    }
}

// ── Signed SMB token (file explorer, financial, etc) ─────────
function _serveToken(string $token, bool $dl): void
{
    $smbPath = _smbVerifyToken($token);
    if (!$smbPath) {
        ob_clean(); http_response_code(403); echo 'Invalid or expired token'; return;
    }

    $omv      = new OMV_SMB_Manager();
    $ext      = strtolower(pathinfo($smbPath, PATHINFO_EXTENSION));
    $mime     = _extToMime($ext);
    $fileName = basename($smbPath);
    $tempFile = sys_get_temp_dir() . '/smb_serve_' . uniqid() . '.' . $ext;

    if (!$omv->get_file($smbPath, $tempFile) || !file_exists($tempFile) || filesize($tempFile) === 0) {
        if (file_exists($tempFile)) unlink($tempFile);
        ob_clean(); http_response_code(404); echo 'File not available'; return;
    }

    if ($dl) header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($tempFile));
    header('Cache-Control: private, max-age=3600');
    ob_end_clean();
    readfile($tempFile);
    unlink($tempFile);
}

// ═══════════════════════════════════════════════════════════════
// SHARED UTILITIES
// ═══════════════════════════════════════════════════════════════

function _smbSecret(): string {
    static $s = null;
    if ($s) return $s;
    $s = env('SMB_TOKEN_SECRET', 'th_smb_s3cr3t_2025');
    return $s;
}

function _smbVerifyToken(string $token): ?string {
    $pad     = strlen($token) % 4;
    $padded  = $pad ? $token . str_repeat('=', 4 - $pad) : $token;
    $decoded = base64_decode(strtr($padded, '-_', '+/'), true);
    if (!$decoded) return null;
    $pos     = strrpos($decoded, '|');
    if ($pos === false) return null;
    $path    = substr($decoded, 0, $pos);
    $sig     = substr($decoded, $pos + 1);
    return hash_equals(hash_hmac('sha256', $path, _smbSecret(), true), $sig) ? $path : null;
}

function _extToMime(string $ext): string {
    return [
        'pdf'  => 'application/pdf',
        'jpg'  => 'image/jpeg', 'jpeg' => 'image/jpeg',
        'png'  => 'image/png',  'gif'  => 'image/gif',
        'webp' => 'image/webp', 'bmp'  => 'image/bmp',
        'mp3'  => 'audio/mpeg', 'wav'  => 'audio/wav',
        'mp4'  => 'video/mp4',  'webm' => 'video/webm',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls'  => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ][$ext] ?? 'application/octet-stream';
}