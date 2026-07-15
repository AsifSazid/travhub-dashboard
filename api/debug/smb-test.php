<?php
session_start();
require_once '../../server/live_storage.php';
require_once '../../server/safe_folder_name.php';
require_once '../../server/make-smb-dir.php';

$omv    = new OMV_SMB_Manager();
$prefix = trim(file_get_contents(__DIR__ . '/../../server-name.txt'));
$cl     = 'THR-CL-26-00K001_MajorMehediHasan';
$w      = 'THR-A26-WK-0001';
$t      = 'THR-A26-TK-0002';
$base   = $prefix . '_clients';

echo "<pre>\n";

// Test create_folder directly and see raw output
echo "=== Direct create_folder test ===\n";
$r = $omv->create_folder($base . '/' . $cl . '/' . $w);
echo "create_folder('{$base}/{$cl}/{$w}') = ";
var_dump($r);
echo "\n";

// Now try paste a test file
$testFile = tempnam(sys_get_temp_dir(), 'smbtest_');
file_put_contents($testFile, 'test content ' . time());

$remotePath = $base . '/' . $cl . '/' . $w . '/' . $t . '/notes/test_debug.txt';
echo "=== paste_file test ===\n";
echo "local:  {$testFile}\n";
echo "remote: {$remotePath}\n";
$pr = $omv->paste_file($testFile, $remotePath);
echo "Result: ";
var_dump($pr);
unlink($testFile);

echo "\n=== DONE ===\n</pre>";