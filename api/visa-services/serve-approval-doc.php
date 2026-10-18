<?php
/**
 * FILE PATH: api/visa-services/serve-approval-doc.php
 * Public endpoint — serves visa approval document without login
 * Validates via opaque doc_key stored in vs_travelers
 *
 * URL: /api/visa-services/serve-approval-doc.php?k=<doc_key>
 */

require_once '../../server/db_connection.php';
require_once '../../server/smb_upload_handler.php';

$docKey = trim($_GET['k'] ?? '');
if (!$docKey || strlen($docKey) < 16) {
    http_response_code(400);
    die('Invalid request');
}

// Find approval doc by doc_key
$approvalDoc = null;
try {
    $stmt = $pdo->query("SELECT vs_travelers FROM visa_services WHERE status != 'cancelled'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $travelers = json_decode($row['vs_travelers'] ?? '[]', true) ?: [];
        foreach ($travelers as $t) {
            if (($t['approval_doc']['doc_key'] ?? '') === $docKey) {
                $approvalDoc = $t['approval_doc'];
                break 2;
            }
        }
    }
} catch (Throwable $e) {
    http_response_code(500);
    die('Server error');
}

if (!$approvalDoc) {
    http_response_code(404);
    die('Document not found');
}

// Serve from SMB
$smbPath  = $approvalDoc['smb_path'] ?? '';
$filename = $approvalDoc['filename'] ?? 'approval_document';
$ext      = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

if (!$smbPath) { http_response_code(404); die('File path not available'); }

$tmpFile = sys_get_temp_dir() . '/vs_approval_' . uniqid() . '.' . $ext;

try {
    $omv = new OMV_SMB_Manager();
    $ok  = $omv->get_file($smbPath, $tmpFile);
    if (!$ok || !file_exists($tmpFile) || filesize($tmpFile) === 0) {
        http_response_code(404);
        die('File not found on server');
    }
} catch (Throwable $e) {
    http_response_code(500);
    die('File retrieval failed');
}

$mimeMap = [
    'pdf'  => 'application/pdf',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
];
$mime = $mimeMap[$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . addslashes($filename) . '"');
header('Content-Length: ' . filesize($tmpFile));
header('Cache-Control: private, max-age=300');

readfile($tmpFile);
@unlink($tmpFile);
exit;