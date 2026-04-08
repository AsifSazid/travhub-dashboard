<?php
require '../../server/db_connection.php';
require '../../server/make-dir.php';
require '../../server/make-smb-dir.php';
require_once '../../server/live_storage.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

error_reporting(E_ALL);
ini_set('display_errors', 1);

// ---------------- CONFIGURATION ----------------
$SERVER_CUS_PATH = trim(file_get_contents('../../server-name.txt'));

// ---------------- FILE SAVE IN SMB ----------------
function fileSaveinSMB($localFilePath, $cloudFolderPath, $fileName) {
    $omv = new OMV_SMB_Manager();
    $destPath = rtrim($cloudFolderPath, '/') . '/' . $fileName;

    error_log("📁 SMB Dest: "  . $destPath);
    error_log("📄 SMB Local: " . $localFilePath);

    $paste_status = $omv->paste_file($localFilePath, $destPath);
    if ($paste_status !== true) {
        error_log("❌ SMB Error: " . $paste_status);
    }
}

// ---------------- GET DATA ----------------
$travelerId   = $_GET['traveler_id']     ?? null;
$infoFileName = $_POST['info_file_name'] ?? null;
$infoDetails  = $_POST['information']    ?? null;
$pastedText   = $_POST['pasted_text']    ?? null;

// ---------------- VALIDATION ----------------
if (!$travelerId) {
    echo json_encode(['success' => false, 'message' => 'traveler_id missing']);
    exit;
}

// ---------------- GET TRAVELER ----------------
$stmt = $pdo->prepare("SELECT sys_id, name FROM travelers WHERE sys_id = ?");
$stmt->execute([$travelerId]);
$traveler = $stmt->fetch(PDO::FETCH_ASSOC);

if (empty($traveler['sys_id']) || empty($traveler['name'])) {
    echo json_encode(['success' => false, 'message' => 'Traveler not found']);
    exit;
}

// ---------------- BUILD FOLDER PATH ----------------
$cleanSysId     = preg_replace('/\s+/u', '', $traveler['sys_id']);
$cleanName      = preg_replace('/\s+/u', '', $traveler['name']);
$travelerFolder = "{$cleanSysId}_{$cleanName}";

// Server Storage
$taskDirectory = makeDir("travelers/{$travelerFolder}", "all_documents");

// Cloud Storage — শুধু folder তৈরি
$cloudBasePath = "{$SERVER_CUS_PATH}_travelers/{$travelerFolder}";
$cloudDocsPath = makeSMBDir($cloudBasePath, 'all_documents');
$cloudDocsPath = rtrim($cloudDocsPath, '/');

if (str_starts_with($cloudDocsPath, '❌')) {
    error_log("SMB folder creation failed: " . $cloudDocsPath);
    echo json_encode(['success' => false, 'message' => 'Cloud folder creation failed: ' . $cloudDocsPath]);
    exit;
}

// ---------------- FILE UPLOAD ----------------
$uploadedFiles = [];

// ---------------- SAVE INFO FILE IF PROVIDED ----------------
if ($infoFileName && $infoDetails) {
    $fileExtension = pathinfo($infoFileName, PATHINFO_EXTENSION);
    if (empty($fileExtension)) {
        $infoFileName .= '.txt';
    }

    $safeInfoFileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $infoFileName);
    $infoFilePath     = $taskDirectory . '/' . $safeInfoFileName;

    file_put_contents($infoFilePath, $infoDetails);
    $uploadedFiles[] = $infoFilePath;

    fileSaveinSMB($infoFilePath, $cloudDocsPath, $safeInfoFileName);

    error_log("📝 Info file created: " . $infoFilePath);
}

// ---------------- UPLOADED FILES ----------------
if (!empty($_FILES['files']['name'][0])) {
    foreach ($_FILES['files']['name'] as $key => $name) {
        if ($_FILES['files']['error'][$key] !== UPLOAD_ERR_OK) continue;

        $safeName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
        $target   = $taskDirectory . '/' . $safeName;

        if (move_uploaded_file($_FILES['files']['tmp_name'][$key], $target)) {
            $uploadedFiles[] = $target;
            fileSaveinSMB($target, $cloudDocsPath, $safeName);
        }
    }
}

// ---------------- SAVE PASTED TEXT ----------------
if (!empty($pastedText)) {
    $textFileName = time() . '_pasted_text.txt';
    $textFilePath = $taskDirectory . '/' . $textFileName;
    file_put_contents($textFilePath, $pastedText);
    $uploadedFiles[] = $textFilePath;
    fileSaveinSMB($textFilePath, $cloudDocsPath, $textFileName);
}

// ---------------- RESPONSE ----------------
if (empty($uploadedFiles)) {
    echo json_encode(['success' => false, 'message' => 'No files uploaded or created']);
    exit;
}

$savedNames = array_map('basename', $uploadedFiles);

echo json_encode([
    'success' => true,
    'message' => count($uploadedFiles) . ' file(s) saved successfully',
    'files'   => $savedNames,
    'path'    => $taskDirectory,
]);