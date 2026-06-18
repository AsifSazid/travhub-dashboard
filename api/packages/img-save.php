<?php
// api/packages/img-save.php
session_start();
require_once '../../server/db_connection.php';

header('Content-Type: application/json; charset=utf-8');

$data      = json_decode(file_get_contents('php://input'), true) ?: [];
$imgUrl    = trim($data['img_url']    ?? '');
$packageId = trim($data['package_id'] ?? '');

if (!$imgUrl || !$packageId) {
    echo json_encode(['success'=>false,'message'=>'Invalid parameters']); exit;
}

// Project root = /var/www/projects/package-claude-v2/
$projectRoot = dirname(__DIR__, 2);

// img_url comes as '/uploads/ai-images/ai-xxx.jpg'
// Convert to absolute filesystem path
$srcPath = $projectRoot . $imgUrl;

if (!file_exists($srcPath)) {
    echo json_encode(['success'=>false,'message'=>'Source file not found: '.$srcPath]); exit;
}

// Destination
$destDir  = $projectRoot . '/uploads/packages/' . $packageId . '/';
if (!is_dir($destDir)) mkdir($destDir, 0775, true);

$destFile    = $destDir . 'cover_img.jpg';
$coverImgUrl = 'uploads/packages/' . $packageId . '/cover_img.jpg';

$sql = "UPDATE packages  SET cover_image =? WHERE sys_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$coverImgUrl, $packageId]);

if (!copy($srcPath, $destFile)) {
    echo json_encode(['success'=>false,'message'=>'Failed to copy image']); exit;
}

// DB update (optional — skip if no PDO here)
if (isset($pdo)) {
    $pdo->prepare("UPDATE packages SET cover_image=? WHERE sys_id=?")
        ->execute([$coverImgUrl, $packageId]);
}

echo json_encode([
    'success'     => true,
    'cover_image' => $coverImgUrl,
    'message'     => 'Cover image saved'
]);