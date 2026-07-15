<?php
/**
 * FILE PATH: /server/smb_upload_handler.php
 *
 * Reusable SMB file upload handler for TravHub.
 *
 * Path structure:
 *   {SERVER_CUS_PATH}_clients/{clientSysId}_{clientName}/{workSysId}/{taskSysId}/{module}/{fileName}
 *
 * Example:
 *   dev_clients/THR-CL-20-00K001_MajorMehediHasan/THR-A26-WK-0001/THR-A26-TK-0002/notes/file.pdf
 *
 * Usage:
 *   require_once __DIR__ . '/smb_upload_handler.php';
 *
 *   $result = smbUploadFile($_FILES['file'], [
 *       'client_sys_id' => 'THR-CL-20-00K001',
 *       'client_name'   => 'Major Mehedi Hasan',
 *       'work_sys_id'   => 'THR-A26-WK-0001',
 *       'task_sys_id'   => 'THR-A26-TK-0002',
 *       'module'        => 'notes',           // notes | confirmation | documents
 *   ]);
 *
 *   if ($result['success']) {
 *       $fileName = $result['file_name']; // store in DB
 *   }
 *
 *   smbDeleteFile($ctx, $fileName);
 */

require_once __DIR__ . '/live_storage.php';
require_once __DIR__ . '/safe_folder_name.php';
require_once __DIR__ . '/make-smb-dir.php';

// ── Mime → type map ───────────────────────────────────────────
const SMB_MIME_MAP = [
    'image/jpeg' => 'image', 'image/png'  => 'image',
    'image/gif'  => 'image', 'image/webp' => 'image',
    'image/bmp'  => 'image',
    'audio/mpeg' => 'audio', 'audio/wav'  => 'audio',
    'audio/ogg'  => 'audio', 'audio/mp4'  => 'audio',
    'audio/webm' => 'audio',
    'video/mp4'  => 'video', 'video/webm' => 'video',
    'video/quicktime' => 'video',
    'application/pdf' => 'file',
];

// ── Get server prefix from server-name.txt ────────────────────
function _smbServerPrefix(): string {
    static $p = null;
    if ($p !== null) return $p;
    $p = trim(@file_get_contents(__DIR__ . '/../server-name.txt') ?? '');
    return $p;
}

// ── Build client folder name ──────────────────────────────────
function _smbClientFolder(string $clientSysId, string $clientName): string {
    // CamelCase: "Major Mehedi Hasan" → "MajorMehediHasan"
    $words    = preg_split('/[\s_]+/', trim($clientName));
    $camel    = implode('', array_map('ucfirst', $words));
    // Remove any remaining unsafe chars
    $camel    = preg_replace('/[^A-Za-z0-9\-]/', '', $camel);
    return $clientSysId . '_' . $camel;
}

// ── Build full SMB path ───────────────────────────────────────
function smbBuildPath(array $ctx, string $fileName = ''): string {
    $prefix = _smbServerPrefix();                    // "dev"
    $cl     = _smbClientFolder($ctx['client_sys_id'], $ctx['client_name']); // "THR-CL-20-00K001_MajorMehediHasan"
    $w      = safeFolderName($ctx['work_sys_id']);   // "THR-A26-WK-0001"
    $t      = safeFolderName($ctx['task_sys_id']);   // "THR-A26-TK-0002"
    $m      = safeFolderName($ctx['module']);         // "notes"

    $path = "{$prefix}_clients/{$cl}/{$w}/{$t}/{$m}";
    return $fileName ? $path . '/' . $fileName : $path;
}

// ── Sequential SMB mkdir ──────────────────────────────────────
// Each call creates ONE new folder on an EXISTING parent.
// "dev_clients" must already exist on SMB (created during client setup).
// "dev_clients/clientFolder" must exist (created during client creation).
// We create: work → task → module
function smbEnsureDir(array $ctx): bool {
    $prefix = _smbServerPrefix();
    $cl     = _smbClientFolder($ctx['client_sys_id'], $ctx['client_name']);
    $w      = safeFolderName($ctx['work_sys_id']);
    $t      = safeFolderName($ctx['task_sys_id']);
    $m      = safeFolderName($ctx['module']);

    $clientBase = "{$prefix}_clients/{$cl}"; // already exists from client creation

    // Step 1: work folder
    $r1 = makeSMBDir($clientBase, $w);
    _smbOkOrLog($r1, "work: $clientBase/$w");

    // Step 2: task folder
    $r2 = makeSMBDir("$clientBase/$w", $t);
    _smbOkOrLog($r2, "task: $clientBase/$w/$t");

    // Step 3: module folder
    $r3 = makeSMBDir("$clientBase/$w/$t", $m);
    _smbOkOrLog($r3, "module: $clientBase/$w/$t/$m");

    return true;
}

function _smbOkOrLog($result, string $ctx): void {
    if (is_string($result) && str_starts_with($result, '❌')) {
        if (
            stripos($result, 'NT_STATUS_OBJECT_NAME_COLLISION') === false &&
            stripos($result, 'already exists') === false
        ) {
            error_log("[SMB mkdir] $ctx → $result");
        }
    }
}

// ── PDF to PNG conversion ─────────────────────────────────────
// PDF upload হলে Imagick দিয়ে page-by-page PNG convert করে SMB তে save করে
// Returns: array of page file names ['basename_page_1.png', ...]
// No local storage — temp files only
function _smbConvertPdfToImages(string $pdfTempPath, string $baseName, array $ctx): array {
    $pages  = [];
    $omv    = new OMV_SMB_Manager();
    $smbDir = smbBuildPath($ctx); // module folder, no filename

    try {
        $imagick = new Imagick();
        $imagick->setResolution(150, 150);
        $imagick->readImage($pdfTempPath);
        $totalPages = count($imagick);

        foreach ($imagick as $pageNum => $page) {
            $page->setImageFormat('png');
            $page->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);

            $pngName = $baseName . '_page_' . ($pageNum + 1) . '.png';
            $tempPng = sys_get_temp_dir() . '/smb_pdf_' . uniqid() . '.png';

            $page->writeImage($tempPng);

            if (file_exists($tempPng)) {
                $result = $omv->paste_file($tempPng, $smbDir . '/' . $pngName);
                unlink($tempPng);
                if ($result === true) {
                    $pages[] = $pngName;
                } else {
                    error_log("[SMB PDF page] upload failed: $result");
                }
            }
        }

        $imagick->clear();
        $imagick->destroy();

    } catch (Exception $e) {
        error_log('[SMB PDF convert] ' . $e->getMessage());
        return [];
    }

    return $pages;
}

// ── Upload file to SMB ────────────────────────────────────────
/**
 * @param array $fileArr  $_FILES['field']
 * @param array $ctx      ['client_sys_id', 'client_name', 'work_sys_id', 'task_sys_id', 'module']
 * @param string $caption optional
 * @return array {success, file_name, smb_path, file_type, mime_type, file_size, error}
 */
function smbUploadFile(array $fileArr, array $ctx, string $caption = ''): array {
    // Validate context
    foreach (['client_sys_id','client_name','work_sys_id','task_sys_id','module'] as $k) {
        if (empty($ctx[$k])) return ['success'=>false,'error'=>"Missing context: $k"];
    }

    // Validate file
    if (!isset($fileArr['tmp_name']) || $fileArr['error'] !== UPLOAD_ERR_OK) {
        $errMap = [
            UPLOAD_ERR_INI_SIZE   => 'File too large (php.ini)',
            UPLOAD_ERR_FORM_SIZE  => 'File too large (form)',
            UPLOAD_ERR_PARTIAL    => 'Partial upload',
            UPLOAD_ERR_NO_FILE    => 'No file uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'No temp dir',
            UPLOAD_ERR_CANT_WRITE => 'Cannot write to disk',
        ];
        $code = $fileArr['error'] ?? -1;
        return ['success'=>false,'error'=>$errMap[$code] ?? "Upload error: $code"];
    }

    $origName = $fileArr['name']    ?? 'file';
    $tmpPath  = $fileArr['tmp_name'];
    $fileSize = $fileArr['size']    ?? 0;
    $mimeType = $fileArr['type']    ?? 'application/octet-stream';

    // Detect type
    if (function_exists('mime_content_type')) {
        $detected = mime_content_type($tmpPath);
        if ($detected) $mimeType = $detected;
    }
    $fileType = SMB_MIME_MAP[$mimeType] ?? 'file';

    // Safe filename
    $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    $base     = pathinfo($origName, PATHINFO_FILENAME);
    $safeName = safeFolderName($base) . '_' . time() . '_' . substr(md5(uniqid()), 0, 6) . '.' . $ext;

    // Move to local temp (smbclient needs readable path)
    $tempLocal = sys_get_temp_dir() . '/smb_up_' . uniqid() . '.' . $ext;
    if (!move_uploaded_file($tmpPath, $tempLocal)) {
        return ['success'=>false,'error'=>'Failed to move uploaded file to temp'];
    }

    // ── PDF → images conversion ───────────────────────────────
    if ($ext === 'pdf' || $mimeType === 'application/pdf') {
        smbEnsureDir($ctx);
        $baseName = safeFolderName(pathinfo($origName, PATHINFO_FILENAME)) . '_' . time() . '_' . substr(md5(uniqid()), 0, 6);
        $pages    = _smbConvertPdfToImages($tempLocal, $baseName, $ctx);
        if (file_exists($tempLocal)) unlink($tempLocal);
        if (empty($pages)) {
            return ['success' => false, 'error' => 'PDF conversion failed — Imagick/Ghostscript required'];
        }
        return [
            'success'    => true,
            'file_name'  => $baseName . '.pdf',  // reference in DB
            'pages_json' => $pages,               // ['page_1.png', 'page_2.png', ...]
            'file_type'  => 'pdf_images',
            'mime_type'  => 'application/pdf',
            'file_size'  => $fileSize,
            'caption'    => $caption,
            'smb_path'   => smbBuildPath($ctx),
        ];
    }

    // ── Regular file upload ───────────────────────────────────
    // Ensure SMB directory exists
    smbEnsureDir($ctx);

    // Upload to SMB
    $smbPath = smbBuildPath($ctx, $safeName);
    $omv     = new OMV_SMB_Manager();
    $result  = $omv->paste_file($tempLocal, $smbPath);

    if (file_exists($tempLocal)) unlink($tempLocal);

    if ($result !== true) {
        return ['success'=>false,'error'=>'SMB upload failed: ' . $result];
    }

    return [
        'success'   => true,
        'file_name' => $safeName,   // ← only this in DB
        'smb_path'  => $smbPath,
        'file_type' => $fileType,
        'mime_type' => $mimeType,
        'file_size' => $fileSize,
        'caption'   => $caption,
    ];
}

// ── Delete file from SMB ──────────────────────────────────────
function smbDeleteFile(array $ctx, string $fileName): bool {
    if (!$fileName) return false;
    $omv = new OMV_SMB_Manager();
    return $omv->delete_file(smbBuildPath($ctx, $fileName));
}

// ── Stream file to browser ────────────────────────────────────
function smbServeFile(array $ctx, string $fileName, string $mimeType = 'application/octet-stream'): bool {
    $omv      = new OMV_SMB_Manager();
    $smbPath  = smbBuildPath($ctx, $fileName);
    $ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $tempFile = sys_get_temp_dir() . '/smb_serve_' . uniqid() . '.' . $ext;

    $ok = $omv->get_file($smbPath, $tempFile);
    if (!$ok || !file_exists($tempFile) || filesize($tempFile) === 0) {
        if (file_exists($tempFile)) unlink($tempFile);
        return false;
    }

    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . filesize($tempFile));
    header('Cache-Control: private, max-age=3600');
    header('X-Content-Type-Options: nosniff');
    readfile($tempFile);
    unlink($tempFile);
    return true;
}