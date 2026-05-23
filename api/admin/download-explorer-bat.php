<?php
session_start();

require '../../server/smb_config.php';

// if (empty($_SESSION['role']) || $_SESSION['role'] != '0') {
//     http_response_code(403);
//     exit('Forbidden');
// }

$path = $_GET['path'] ?? '';

$path = str_replace(['..', "\0"], '', $path);
$path = trim($path, "/\\");
$path = str_replace('/', '\\', $path);

$fullPath = rtrim(SMB_BASE_PATH, '\\') . '\\' . $path;

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="open_in_explorer.bat"');

echo "@echo off\r\n";
echo "explorer \"" . $fullPath . "\"\r\n";
exit;