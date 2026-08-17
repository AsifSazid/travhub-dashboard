<?php
/**
 * FILE PATH: api/travelers/debug-traveler-path.php
 * TEMPORARY DEBUG FILE — live করার আগে DELETE করো
 *
 * Traveler এর server_path, NAS path সব check করে
 * Browser এ open করো:
 * /api/travelers/debug-traveler-path.php?traveler_id=THR-TR-26-XXXXX
 */

require_once '../../server/db_connection.php';
header('Content-Type: application/json');

$id = trim($_GET['traveler_id'] ?? '');
if (!$id) { echo json_encode(['error' => 'traveler_id required']); exit; }

$stmt = $pdo->prepare("SELECT sys_id, name, server_path, smb_path FROM travelers WHERE sys_id = ? LIMIT 1");
$stmt->execute([$id]);
$t = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$t) { echo json_encode(['error' => 'Not found']); exit; }

$docRoot       = rtrim(preg_replace('/\s+/u', '', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
$serverCusPath = trim(@file_get_contents(__DIR__ . '/../../server-name.txt') ?? 'dev');
$cleanSysId    = preg_replace('/\s+/u', '', $t['sys_id']);
$cleanName     = preg_replace('/\s+/u', '', $t['name']);
$folder        = "{$cleanSysId}_{$cleanName}";

$serverPathFromDb  = $t['server_path'];
$fallbackPath      = "{$docRoot}/{$serverCusPath}/storage/travelers/{$folder}";
$passportFolder    = ($serverPathFromDb ?: $fallbackPath) . '/passport_identity';

echo json_encode([
    'traveler'            => ['sys_id' => $t['sys_id'], 'name' => $t['name']],
    'DOCUMENT_ROOT'       => $docRoot,
    'server_name_txt'     => $serverCusPath,
    'server_path_in_db'   => $serverPathFromDb,
    'server_path_exists'  => $serverPathFromDb ? is_dir($serverPathFromDb) : false,
    'fallback_path'       => $fallbackPath,
    'fallback_path_exists'=> is_dir($fallbackPath),
    'passport_folder'     => $passportFolder,
    'passport_folder_exists' => is_dir($passportFolder),
    'smb_path'            => $t['smb_path'],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);