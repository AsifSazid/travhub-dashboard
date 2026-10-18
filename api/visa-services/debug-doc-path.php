<?php
/**
 * FILE PATH: /api/visa-services/debug-doc-path.php
 * TEMPORARY DEBUG — delete after use
 * Usage: ?doc_sys_id=THR-A26-DC-000B&filename=current_passport_bio_page_p1.png
 */
require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';
header('Content-Type: application/json');

$docSysId  = $_GET['doc_sys_id'] ?? '';
$filename  = $_GET['filename']   ?? '';

$st = $pdo->prepare("SELECT server_path, smb_folder, sys_id FROM traveler_documents WHERE sys_id = ? LIMIT 1");
$st->execute([$docSysId]);
$row = $st->fetch(PDO::FETCH_ASSOC);

$fullPath  = $row ? rtrim($row['server_path'], '/') . '/' . $filename : null;
$exists    = $fullPath ? file_exists($fullPath) : false;

echo json_encode([
    'doc_sys_id'  => $docSysId,
    'filename'    => $filename,
    'server_path' => $row['server_path'] ?? null,
    'full_path'   => $fullPath,
    'file_exists' => $exists,
    'php_user'    => get_current_user(),
    'doc_found'   => !!$row,
], JSON_PRETTY_PRINT);