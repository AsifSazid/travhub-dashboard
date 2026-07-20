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
    if (!empty($_GET['conf_id']))   { _serveConf($_GET['conf_id'], $dl);   exit; }
    if (!empty($_GET['doc_id']))    { _serveDoc($_GET['doc_id'], $dl);     exit; }
    if (!empty($_GET['fin_id']))    { _serveFin($_GET['fin_id'], $dl);     exit; }

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
    // tasks + works (current) — client info JSON column এ
    $stmt = $pdo->prepare("
        SELECT n.*,
               JSON_UNQUOTE(JSON_EXTRACT(w.client_info, '$.sys_id')) AS client_sys_id,
               JSON_UNQUOTE(JSON_EXTRACT(w.client_info, '$.name'))   AS client_name
        FROM task_notes n
        LEFT JOIN tasks t ON t.sys_id = n.task_sys_id
        LEFT JOIN works w ON w.sys_id = t.work_sys_id
        WHERE n.sys_id = ? LIMIT 1
    ");
    $stmt->execute([$noteId]);
    $note = $stmt->fetch(PDO::FETCH_ASSOC);

    // old_tasks + com_works (legacy) — আলাদা column আছে
    if ($note && empty($note['client_sys_id'])) {
        $stmt2 = $pdo->prepare("
            SELECT n.*, t.client_sys_id, t.client_name
            FROM task_notes n
            LEFT JOIN old_tasks t ON t.sys_id = n.task_sys_id
            WHERE n.sys_id = ? LIMIT 1
        ");
        $stmt2->execute([$noteId]);
        $note2 = $stmt2->fetch(PDO::FETCH_ASSOC);
        if ($note2 && !empty($note2['client_sys_id'])) {
            $note = $note2;
        }
    }

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

    // PDF pages — dl=1 হলে original PDF serve করো, নইলে PNG page serve করো
    if ($note['note_type'] === 'pdf_images') {
        if ($dl && $note['file_name']) {
            // Download mode — original PDF serve করো
            header('Content-Disposition: attachment; filename="' . $note['file_name'] . '"');
            ob_end_clean();
            if (!smbServeFile($ctx, $note['file_name'], 'application/pdf')) {
                http_response_code(404); echo 'File not available';
            }
            return;
        }
        // View mode — PNG page serve করো
        $pages   = json_decode($note['pages_json'] ?? '[]', true) ?? [];
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

// ── Air Ticket Confirmation files ────────────────────────────
// ?conf_id=C-001&task_id=THR-A26-TK-0002&page=0
// Confirmation files are stored in at_confirmations[].files_json
// files_json entries are SMB token strings (generated by smbFileUrl())
function _serveConf(string $confId, bool $dl): void
{
    global $pdo;

    $taskId = $_GET['task_id'] ?? '';
    if (!$taskId) {
        ob_clean(); http_response_code(400); echo 'task_id required for conf_id'; return;
    }

    $stmt = $pdo->prepare("SELECT at_confirmations FROM air_tickets WHERE task_sys_id = ? LIMIT 1");
    $stmt->execute([$taskId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        ob_clean(); http_response_code(404); echo 'Air ticket not found'; return;
    }

    $confirmations = json_decode($row['at_confirmations'] ?? '[]', true) ?? [];
    $conf = null;
    foreach ($confirmations as $c) {
        if (($c['sys_id'] ?? '') === $confId) { $conf = $c; break; }
    }

    if (!$conf) {
        ob_clean(); http_response_code(404); echo 'Confirmation not found'; return;
    }

    $files    = $conf['files_json'] ?? [];
    $pageIdx  = (int)($_GET['page'] ?? 0);
    $token    = is_array($files) ? ($files[$pageIdx] ?? ($files[0] ?? null)) : null;

    if (!$token) {
        ob_clean(); http_response_code(404); echo 'No file at this index'; return;
    }

    // files_json stores SMB tokens — reuse _serveToken
    _serveToken($token, $dl);
}

// ── Traveler document file ────────────────────────────────────
// ?doc_id=THR-DC-26-00K001&file=passport_2025_page_1.png
// Documents live in: {SERVER_CUS_PATH}_travelers/{sysId}_{Name}/{doc_type}/{file}
// doc_type and traveler info are looked up from documents + travelers tables.
function _serveDoc(string $docId, bool $dl): void
{
    global $pdo;

    $fileName = $_GET['file'] ?? '';
    if (!$fileName) {
        ob_clean(); http_response_code(400); echo 'file param required for doc_id'; return;
    }

    // Resolve doc_type and traveler from documents table
    $stmt = $pdo->prepare("
        SELECT d.doc_type, t.sys_id AS traveler_sys_id, t.name AS traveler_name
        FROM documents d
        JOIN travelers t ON t.sys_id = d.traveler_id
        WHERE d.sys_id = ? LIMIT 1
    ");
    $stmt->execute([$docId]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doc) {
        ob_clean(); http_response_code(404); echo 'Document record not found'; return;
    }

    $SERVER_CUS_PATH = trim(@file_get_contents(__DIR__ . '/../../server-name.txt'));
    $cleanSysId      = preg_replace('/\s+/u', '', $doc['traveler_sys_id']);
    $cleanName       = preg_replace('/\s+/u', '', $doc['traveler_name']);
    $travelerFolder  = "{$cleanSysId}_{$cleanName}";
    $docType         = $doc['doc_type'] ?: 'all_documents';

    // SMB path: dev_travelers/{sysId}_{Name}/{doc_type}/{file}
    $smbPath = "{$SERVER_CUS_PATH}_travelers/{$travelerFolder}/{$docType}/" . basename($fileName);

    $omv      = new OMV_SMB_Manager();
    $ext      = strtolower(pathinfo($smbPath, PATHINFO_EXTENSION));
    $mime     = _extToMime($ext);
    $tempFile = sys_get_temp_dir() . '/smb_doc_' . uniqid() . '.' . $ext;

    if (!$omv->get_file($smbPath, $tempFile) || !file_exists($tempFile) || filesize($tempFile) === 0) {
        if (file_exists($tempFile)) unlink($tempFile);
        ob_clean(); http_response_code(404); echo 'File not available'; return;
    }

    if ($dl) header('Content-Disposition: attachment; filename="' . basename($fileName) . '"');
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($tempFile));
    header('Cache-Control: private, max-age=3600');
    ob_end_clean();
    readfile($tempFile);
    unlink($tempFile);
}

// ── Financial entry file ──────────────────────────────────────
// ?fin_id=THR-FE-26-00K001&page=0
// Financial files stored in financial_entries.files_json as SMB token strings.
function _serveFin(string $finId, bool $dl): void
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT files_json FROM financial_entries WHERE sys_id = ? LIMIT 1");
    $stmt->execute([$finId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        ob_clean(); http_response_code(404); echo 'Financial entry not found'; return;
    }

    $files   = json_decode($row['files_json'] ?? '[]', true) ?? [];
    $pageIdx = (int)($_GET['page'] ?? 0);
    $token   = is_array($files) ? ($files[$pageIdx] ?? ($files[0] ?? null)) : null;

    if (!$token) {
        ob_clean(); http_response_code(404); echo 'No file at this index'; return;
    }

    // files_json stores SMB tokens — reuse _serveToken
    _serveToken($token, $dl);
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