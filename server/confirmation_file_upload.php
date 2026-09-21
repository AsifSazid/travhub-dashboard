<?php
/**
 * FILE PATH: /server/confirmation_file_upload.php
 *
 * Shared helper for uploading a file to a service's Confirmation stage,
 * BEFORE a task exists.
 *
 * ── WHY THIS EXISTS (read before copy-pasting into a new service module) ──
 * A Confirmation is always created and edited while its parent Work is still
 * in the "pending" stage — a task (in the `tasks` table) is only created
 * afterward, when the user explicitly clicks "Confirm & Create Task". This
 * means confirmation file uploads NEVER have a task_sys_id to rely on yet.
 *
 * The original air_ticket implementation of this (upload_conf_file in
 * api/air-tickets/endpoints.php) originally required a `tasks` JOIN to
 * resolve client info — which always failed with "Task/Work/Client not
 * found", because the task simply doesn't exist at that point. Fixed by
 * looking up client info directly from `works` via work_sys_id instead.
 *
 * When adding a Confirmation stage to a NEW service module (hotel, visa,
 * umrah, transport, ...), reuse this helper rather than re-deriving client
 * info from `tasks` — that mistake is exactly what caused the original bug.
 *
 * ── FOLDER STRUCTURE ──
 * Each confirmation gets its own sub-folder, so browsing the SMB share
 * immediately shows which files belong to which confirmation:
 *   _clients/{client}/{work}/{service_slug}/confirmations/{confSysId}/f01.ext
 *
 * ── USAGE ──
 *   require_once __DIR__ . '/../../server/confirmation_file_upload.php';
 *   $saved = uploadConfirmationFile($pdo, [
 *       'work_sys_id'   => $confWorkSysId,
 *       'service_slug'  => 'hotel',              // 'air_ticket' | 'hotel' | 'visa' | ...
 *       'conf_sys_id'   => $confSysId,
 *       'existing_count'=> count($existingFiles), // for the f01/f02/... index
 *       'uploaded_by'   => $userName,
 *       'php_file'      => $_FILES['file'],       // the raw $_FILES['file'] entry
 *   ]);
 *   // $saved = ['name','file_name','smb_token','mime_type','uploaded_at','uploaded_by']
 *   // AI extraction (if any) is service-specific — run it yourself against
 *   // $saved['file_name'] / the temp path returned in $saved['_temp_local']
 *   // (delete that temp file yourself once you're done with it) and merge
 *   // an 'extracted_data' key into the entry before saving it into your
 *   // service's own confirmation row.
 */

require_once __DIR__ . '/smb_upload_handler.php';
require_once __DIR__ . '/safe_folder_name.php';
require_once __DIR__ . '/live_storage.php';

/**
 * @throws Exception on any validation/upload failure — catch and convert to
 *         your endpoint's normal error-response shape.
 */
function uploadConfirmationFile(PDO $pdo, array $args): array
{
    $workSysId  = $args['work_sys_id']  ?? '';
    $serviceSlug= $args['service_slug'] ?? '';
    $confSysId  = $args['conf_sys_id']  ?? '';
    $existingCt = (int)($args['existing_count'] ?? 0);
    $uploadedBy = $args['uploaded_by']  ?? 'system';
    $file       = $args['php_file']     ?? null;

    if (!$workSysId)   throw new Exception('work_sys_id required');
    if (!$serviceSlug) throw new Exception('service_slug required');
    if (!$confSysId)   throw new Exception('conf_sys_id required');
    if (!$file)        throw new Exception('No file uploaded');
    if ($file['error'] !== UPLOAD_ERR_OK) throw new Exception('Upload error: ' . $file['error']);

    // ── Client info — from `works` directly, NEVER from `tasks` ───────
    // (see file header — this is the exact bug this helper prevents)
    $wStmt = $pdo->prepare("
        SELECT JSON_UNQUOTE(JSON_EXTRACT(client_info, '$.sys_id')) AS client_sys_id,
               JSON_UNQUOTE(JSON_EXTRACT(client_info, '$.name'))   AS client_name
        FROM works WHERE sys_id = ? LIMIT 1
    ");
    $wStmt->execute([$workSysId]);
    $wRow = $wStmt->fetch(PDO::FETCH_ASSOC);
    if (!$wRow || empty($wRow['client_sys_id'])) throw new Exception('Work/Client not found');

    $ctx = [
        'client_sys_id' => $wRow['client_sys_id'],
        'client_name'   => $wRow['client_name'],
        'work_sys_id'   => $workSysId,
        'service_slug'  => $serviceSlug,
        'sub_folder'    => 'confirmations/' . $confSysId, // per-confirmation folder
    ];

    // ── File info ───────────────────────────────────────────────────
    $origName = $file['name'];
    $tmpPath  = $file['tmp_name'];
    $mimeType = $file['type'] ?: 'application/octet-stream';
    if (function_exists('finfo_file')) {
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $detected = finfo_file($finfo, $tmpPath);
        finfo_close($finfo);
        if ($detected) $mimeType = $detected;
    } elseif (function_exists('mime_content_type')) {
        $detected = mime_content_type($tmpPath);
        if ($detected) $mimeType = $detected;
    }
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

    // f01, f02, ... — confSysId is already the folder name, no need to repeat it
    $fileIdx  = str_pad($existingCt + 1, 2, '0', STR_PAD_LEFT);
    $fileName = 'f' . $fileIdx . ($ext ? ".{$ext}" : '');

    // ── SMB upload ──────────────────────────────────────────────────
    smbEnsureDir($ctx);
    $smbBase = smbBuildPath($ctx);
    $omv     = new OMV_SMB_Manager();

    $tempLocal = sys_get_temp_dir() . '/conf_up_' . uniqid() . ($ext ? ".{$ext}" : '');
    if (!move_uploaded_file($tmpPath, $tempLocal)) throw new Exception('Failed to move file');
    $omv->paste_file($tempLocal, "{$smbBase}/{$fileName}");
    $smbToken = smbFileUrl("{$smbBase}/{$fileName}");

    return [
        'name'        => $origName,
        'file_name'   => $fileName,
        'smb_token'   => $smbToken,
        'mime_type'   => $mimeType,
        'uploaded_at' => date('d-m-Y H:i'),
        'uploaded_by' => $uploadedBy,
        // Caller owns this temp file: run AI extraction against it if needed,
        // then unlink() it. Not deleted here since some callers extract async.
        '_temp_local' => $tempLocal,
    ];
}