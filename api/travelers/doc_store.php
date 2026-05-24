<?php
session_start();

require '../../server/db_connection.php';
require '../../server/make-dir.php';
require '../../server/make-smb-dir.php';
require_once '../../server/live_storage.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Increase limits for PDF processing
ini_set('memory_limit', '1024M');
set_time_limit(600);

// ---------------- CONFIGURATION ----------------
$SERVER_CUS_PATH = trim(file_get_contents('../../server-name.txt'));

// ---------------- PDF TO PNG CONVERSION FUNCTION ----------------
function convertPdfToPngs($pdfPath, $originalName, $taskDirectory, $cloudDocsPath) {
    $convertedFiles = [];
    $baseName = pathinfo($originalName, PATHINFO_FILENAME);
    // Clean base name for filesystem
    $cleanBaseName = preg_replace('/[^a-zA-Z0-9]/', '_', $baseName);
    
    try {
        $imagick = new Imagick();
        $imagick->setResolution(150, 150);
        $imagick->readImage($pdfPath);
        
        foreach ($imagick as $pageNum => $page) {
            $page->setImageFormat('png');
            $page->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);
            
            $pngName = time() . '_' . $cleanBaseName . "_page_" . ($pageNum + 1) . ".png";
            $localPngPath = $taskDirectory . '/' . $pngName;
            
            // Save PNG locally
            $page->writeImage($localPngPath);
            
            if (file_exists($localPngPath)) {
                $convertedFiles[] = [
                    'path' => $localPngPath,
                    'name' => $pngName,
                    'page' => $pageNum + 1
                ];
                
                // Upload to SMB
                fileSaveinSMB($localPngPath, $cloudDocsPath, $pngName);
            }
        }
        
        $imagick->clear();
        $imagick->destroy();
        
        return ['success' => true, 'files' => $convertedFiles];
        
    } catch (Exception $e) {
        error_log("PDF Conversion Error: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// ---------------- FILE SAVE IN SMB ----------------
function fileSaveinSMB($localFilePath, $cloudFolderPath, $fileName) {
    $omv = new OMV_SMB_Manager();
    $destPath = rtrim($cloudFolderPath, '/') . '/' . $fileName;

    error_log("📁 SMB Dest: " . $destPath);
    error_log("📄 SMB Local: " . $localFilePath);

    $paste_status = $omv->paste_file($localFilePath, $destPath);
    if ($paste_status !== true) {
        error_log("❌ SMB Error: " . $paste_status);
        return false;
    }
    return true;
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

// Cloud Storage
$cloudBasePath = "{$SERVER_CUS_PATH}_travelers/{$travelerFolder}";
$cloudDocsPath = makeSMBDir($cloudBasePath, 'all_documents');
$cloudDocsPath = rtrim($cloudDocsPath, '/');

if (str_starts_with($cloudDocsPath, '❌')) {
    error_log("SMB folder creation failed: " . $cloudDocsPath);
    echo json_encode(['success' => false, 'message' => 'Cloud folder creation failed: ' . $cloudDocsPath]);
    exit;
}

// ---------------- TRACK UPLOADS ----------------
$uploadedFiles = [];
$conversionLog = []; // Track PDF conversions
$errors = [];

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

// ---------------- PROCESS UPLOADED FILES ----------------
if (!empty($_FILES['files']['name'][0])) {
    foreach ($_FILES['files']['name'] as $key => $name) {
        if ($_FILES['files']['error'][$key] !== UPLOAD_ERR_OK) {
            $errors[] = "Error uploading: $name";
            continue;
        }
        
        $fileExtension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $tempPath = $_FILES['files']['tmp_name'][$key];
        
        // --- PDF CONVERSION ---
        if ($fileExtension === 'pdf') {
            error_log("📄 Converting PDF: " . $name);
            
            $conversionResult = convertPdfToPngs($tempPath, $name, $taskDirectory, $cloudDocsPath);
            
            if ($conversionResult['success']) {
                foreach ($conversionResult['files'] as $pngFile) {
                    $uploadedFiles[] = $pngFile['path'];
                    $conversionLog[] = [
                        'original_pdf' => $name,
                        'converted_to' => $pngFile['name'],
                        'page' => $pngFile['page']
                    ];
                }
                error_log("✅ PDF converted to " . count($conversionResult['files']) . " PNGs");
            } else {
                $errors[] = "PDF conversion failed for $name: " . $conversionResult['error'];
                error_log("❌ PDF conversion failed: " . $conversionResult['error']);
            }
        } 
        // --- IMAGE FILES (keep as-is) ---
        elseif (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $safeName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
            $target = $taskDirectory . '/' . $safeName;
            
            if (move_uploaded_file($tempPath, $target)) {
                $uploadedFiles[] = $target;
                fileSaveinSMB($target, $cloudDocsPath, $safeName);
                error_log("✅ Image uploaded: " . $safeName);
            } else {
                $errors[] = "Failed to move uploaded file: $name";
            }
        }
        // --- OTHER FILE TYPES (reject) ---
        else {
            $errors[] = "Unsupported file type: $name (Only PDF, JPG, JPEG, PNG allowed)";
            error_log("❌ Rejected file type: " . $fileExtension);
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
    error_log("📝 Pasted text saved: " . $textFileName);
}

// ---------------- RESPONSE ----------------
$savedNames = array_map('basename', $uploadedFiles);

$response = [
    'success' => !empty($uploadedFiles),
    'message' => '',
    'files' => $savedNames,
    'path' => $taskDirectory,
];

// Add conversion details for frontend feedback
if (!empty($conversionLog)) {
    $response['conversions'] = $conversionLog;
    $response['message'] = count($uploadedFiles) . ' file(s) saved. ' . count($conversionLog) . ' PDF(s) converted to images.';
} else {
    $response['message'] = count($uploadedFiles) . ' file(s) saved successfully';
}

if (!empty($errors)) {
    $response['warnings'] = $errors;
    $response['message'] .= ' However, some errors occurred: ' . implode(', ', $errors);
}

if (empty($uploadedFiles)) {
    $response['success'] = false;
    $response['message'] = 'No files uploaded or created. ' . (!empty($errors) ? implode(', ', $errors) : '');
}

echo json_encode($response);