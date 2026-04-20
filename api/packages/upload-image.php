<?php
header('Content-Type: application/json');
require_once('../../server/db_connection.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { 
    echo json_encode(['success'=>false,'message'=>'Method not allowed']); 
    exit; 
}

$uuid = trim($_POST['uuid'] ?? '');
if (empty($uuid)) { 
    echo json_encode(['success'=>false,'message'=>'UUID required']); 
    exit; 
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success'=>false,'message'=>'No file uploaded or upload error']);
    exit;
}

$file    = $_FILES['image'];
$allowed = ['image/jpeg','image/png','image/webp','image/gif'];
if (!in_array($file['type'], $allowed)) {
    echo json_encode(['success'=>false,'message'=>'Invalid file type. Allowed: JPG, PNG, WEBP, GIF']);
    exit;
}
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success'=>false,'message'=>'File too large. Max 5MB']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT sys_id, image FROM packages WHERE uuid=:uuid LIMIT 1");
    $stmt->execute([':uuid'=>$uuid]);
    $row  = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) { 
        echo json_encode(['success'=>false,'message'=>'Package not found']); 
        exit; 
    }

    $uploadDir = '../../uploads/packages/' . $row['sys_id'] . '/';
    
    // Create directory with proper permissions if it doesn't exist
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0775, true)) {
            echo json_encode(['success'=>false,'message'=>'Failed to create directory']);
            exit;
        }
        // Set proper ownership (optional - may require root access)
        // chown($uploadDir, 'www-data'); // Uncomment and adjust user as needed
    }
    
    // Check if directory is writable
    if (!is_writable($uploadDir)) {
        echo json_encode(['success'=>false,'message'=>'Upload directory is not writable']);
        exit;
    }

    // Remove old image
    if (!empty($row['image'])) {
        $old = '../../' . ltrim($row['image'], '/');
        if (file_exists($old)) {
            @unlink($old);
        }
    }

    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = 'cover_' . time() . '.' . $ext;
    $destPath = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        echo json_encode(['success'=>false,'message'=>'Failed to save file']);
        exit;
    }
    
    // Set proper permissions on uploaded file
    chmod($destPath, 0644);

    $relativePath = 'uploads/packages/' . $row['sys_id'] . '/' . $filename;
    $pdo->prepare("UPDATE packages SET image=:image WHERE uuid=:uuid")->execute([':image'=>$relativePath, ':uuid'=>$uuid]);

    echo json_encode(['success'=>true,'image'=>$relativePath,'message'=>'Image uploaded']);
} catch (Exception $e) { 
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]); 
}