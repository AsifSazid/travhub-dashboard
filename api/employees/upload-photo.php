<?php
// PATH: /api/employees/upload-photo.php
//
// Uploads/replaces one employee's Profile Photo -- the large circular photo
// used on the ID Card (and potentially elsewhere later), kept dedicated and
// separate from `image_name` (which holds the general uploaded-documents
// list from create-employee.php's file-drop and has no way to say which of
// those, if any, is a profile picture).
//
// POST multipart/form-data: { sys_id, photo (file) }
// Stores the file under storage/employees/{sys_id}_{name}/profile/ and
// records its relative path in employees.profile_photo (new column -- see
// migration checklist).

require '../../server/db_connection.php';
require_once '../../server/make-dir.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $sysId = trim($_POST['sys_id'] ?? '');
    if (!$sysId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'sys_id is required']);
        exit;
    }
    if (empty($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No photo file received']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT name FROM employees WHERE sys_id = ? LIMIT 1");
    $stmt->execute([$sysId]);
    $employeeName = $stmt->fetchColumn();
    if ($employeeName === false) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
        exit;
    }

    $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Photo must be JPG, PNG, or WEBP']);
        exit;
    }
    if ($_FILES['photo']['size'] > 5 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Photo must be under 5MB']);
        exit;
    }

    $cleanSysId   = preg_replace('/\s+/u', '', $sysId);
    $cleanName    = preg_replace('/\s+/u', '', $employeeName);
    $folderName   = "{$cleanSysId}_{$cleanName}/profile";
    $folderPath   = makeDir('employees', $folderName);

    $fileName   = 'profile_photo.' . $ext;
    $destination = $folderPath . '/' . $fileName;

    if (!move_uploaded_file($_FILES['photo']['tmp_name'], $destination)) {
        throw new Exception('Failed to save uploaded photo');
    }

    $relativePath = "employees/{$cleanSysId}_{$cleanName}/profile/{$fileName}";

    $pdo->prepare("UPDATE employees SET profile_photo = ? WHERE sys_id = ?")
        ->execute([$relativePath, $sysId]);

    echo json_encode(['success' => true, 'message' => 'Profile photo updated', 'path' => $relativePath]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error', 'error' => $e->getMessage()]);
}