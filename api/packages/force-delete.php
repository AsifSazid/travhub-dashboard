<?php
header('Content-Type: application/json');
// include_once('../../authenticate.php');
require_once('../../server/db_connection.php');

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
if ($_SERVER['REQUEST_METHOD'] === 'GET') $input = $_GET;
$uuid = trim($input['uuid'] ?? '');
if (empty($uuid)) { echo json_encode(['success'=>false,'message'=>'UUID required']); exit; }

try {
    $pdo  = getDBConnection();
    $stmt = $pdo->prepare("SELECT image, sys_id FROM packages WHERE uuid=:uuid LIMIT 1");
    $stmt->execute([':uuid'=>$uuid]);
    $row  = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        // Remove image file if exists
        if (!empty($row['image'])) {
            $imgPath = '../../' . ltrim($row['image'], '/');
            if (file_exists($imgPath)) @unlink($imgPath);
        }
        // Remove package directory
        $dir = '../../uploads/packages/' . $row['sys_id'];
        if (is_dir($dir)) {
            array_map('unlink', glob("$dir/*.*"));
            @rmdir($dir);
        }
    }

    $pdo->prepare("DELETE FROM packages WHERE uuid=:uuid")->execute([':uuid'=>$uuid]);
    echo json_encode(['success'=>true,'message'=>'Package permanently deleted']);
} catch (Exception $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }