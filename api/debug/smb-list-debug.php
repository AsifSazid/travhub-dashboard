<?php
session_start();
require_once '../../server/live_storage.php';

header('Content-Type: application/json');

$path = $_GET['path'] ?? '';
if (!$path) { echo json_encode(['error' => 'path param required']); exit; }

$omv   = new OMV_SMB_Manager();
$host  = env('SMB_HOST',     '103.104.219.3');
$user  = env('SMB_USER',     'travhub');
$pass  = env('SMB_PASSWORD', 'travhub@2025');
$share = env('SMB_SHARE',    'travhub');

// Test 1: raw ls "path"
$cmd1 = "smbclient //{$host}/{$share} -U {$user}%{$pass} -c 'ls \"{$path}\"' 2>&1";
exec($cmd1, $out1, $ret1);

// Test 2: raw ls "path/*"
$cmd2 = "smbclient //{$host}/{$share} -U {$user}%{$pass} -c 'ls \"{$path}/*\"' 2>&1";
exec($cmd2, $out2, $ret2);

// Test 3: raw ls "path/" 
$cmd3 = "smbclient //{$host}/{$share} -U {$user}%{$pass} -c 'ls \"{$path}/\"' 2>&1";
exec($cmd3, $out3, $ret3);

// Test 4: list_directory method
$listResult = method_exists($omv, 'list_directory') ? $omv->list_directory($path) : 'no method';

echo json_encode([
    'path'              => $path,
    'ls_path'           => ['rc' => $ret1, 'out' => $out1],
    'ls_path_wildcard'  => ['rc' => $ret2, 'out' => $out2],
    'ls_path_slash'     => ['rc' => $ret3, 'out' => $out3],
    'list_directory'    => $listResult,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);