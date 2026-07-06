<?php
// PATH: api/finance/upload-file.php
// Finance file upload — receive/payment/invoice documents
// POST: multipart/form-data
//   entity_type: 'receive' | 'payment' | 'invoice'
//   entity_id:   sys_id (receive/payment/invoice)
//   entity_name: client/vendor name (folder name এ যাবে)
//   files[]:     uploaded files

session_start();
require '../../server/db_connection.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

/* ===== INPUT ===== */
$entityType = $_POST['entity_type'] ?? ''; // receive | payment | invoice
$entityId   = $_POST['entity_id']   ?? ''; // sys_id
$entityName = $_POST['entity_name'] ?? ''; // client/vendor name

if (!$entityType || !$entityId || !$entityName) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'entity_type, entity_id, entity_name required']);
    exit;
}

if (!in_array($entityType, ['receive', 'payment', 'invoice'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid entity_type']);
    exit;
}

if (empty($_FILES['files'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No files uploaded']);
    exit;
}

/* ===== PATH BUILD ===== */
// Sanitize name for folder
$safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $entityName);
$safeName = strtolower(trim($safeName, '_'));

// Base path — project root থেকে storage/
$basePath = dirname(__DIR__, 3) . '/storage/accounts/' . $safeName . '/' . $entityType;

if (!is_dir($basePath)) {
    if (!mkdir($basePath, 0775, true)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create directory']);
        exit;
    }
}

/* ===== FILE UPLOAD ===== */
$uploaded  = [];
$errors    = [];
$suffixes  = ['', '_a', '_b', '_c', '_d', '_e', '_f', '_g', '_h', '_i', '_j'];

// Normalize $_FILES['files'] array
$files = [];
if (is_array($_FILES['files']['name'])) {
    $count = count($_FILES['files']['name']);
    for ($i = 0; $i < $count; $i++) {
        $files[] = [
            'name'     => $_FILES['files']['name'][$i],
            'type'     => $_FILES['files']['type'][$i],
            'tmp_name' => $_FILES['files']['tmp_name'][$i],
            'error'    => $_FILES['files']['error'][$i],
            'size'     => $_FILES['files']['size'][$i],
        ];
    }
} else {
    $files[] = $_FILES['files'];
}

foreach ($files as $idx => $file) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "File #{$idx}: Upload error code " . $file['error'];
        continue;
    }

    // Extension
    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed  = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'doc', 'docx', 'xls', 'xlsx'];
    if (!in_array($ext, $allowed, true)) {
        $errors[] = "File #{$idx}: Extension .{$ext} not allowed";
        continue;
    }

    // Max 10MB
    if ($file['size'] > 10 * 1024 * 1024) {
        $errors[] = "File #{$idx}: File too large (max 10MB)";
        continue;
    }

    // Filename: entity_id + suffix + ext
    // First file: entity_id.ext
    // Additional: entity_id_a.ext, entity_id_b.ext...
    $suffix   = $idx === 0 ? '' : ($suffixes[$idx] ?? '_' . $idx);
    $filename = $entityId . $suffix . '.' . $ext;
    $destPath = $basePath . '/' . $filename;

    // File already exists → find next available suffix
    if (file_exists($destPath)) {
        foreach ($suffixes as $s) {
            $alt = $basePath . '/' . $entityId . $s . '.' . $ext;
            if (!file_exists($alt)) {
                $filename = $entityId . $s . '.' . $ext;
                $destPath = $alt;
                break;
            }
        }
    }

    if (move_uploaded_file($file['tmp_name'], $destPath)) {
        $uploaded[] = [
            'original_name' => $file['name'],
            'saved_name'    => $filename,
            'path'          => 'storage/accounts/' . $safeName . '/' . $entityType . '/' . $filename,
            'size'          => $file['size'],
            'ext'           => $ext,
        ];
    } else {
        $errors[] = "File #{$idx}: Failed to save file";
    }
}

echo json_encode([
    'success'  => count($uploaded) > 0,
    'uploaded' => $uploaded,
    'errors'   => $errors,
    'message'  => count($uploaded) . ' file(s) uploaded' . (count($errors) ? ', ' . count($errors) . ' failed' : ''),
]);