<?php
/**
 * FILE PATH: api/travelers/doc_store.php
 *
 * Traveler document upload — SMB only (no local permanent storage).
 * Supports: PDF (→ PNG via Imagick), images, pasted text, info file.
 *
 * SMB path: {SERVER_CUS_PATH}_travelers/{sysId}_{Name}/all_documents/
 */

session_start();

require '../../server/db_connection.php';
require '../../server/make-smb-dir.php';
require_once '../../server/live_storage.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '1024M');
set_time_limit(600);

// ---------------- CONFIGURATION ----------------
$SERVER_CUS_PATH = trim(file_get_contents('../../server-name.txt'));

// ---------------- PDF TO PNG → SMB (no local save) ----------------
function convertPdfToPngsToSmb(string $pdfPath, string $originalName, string $cloudDocsPath): array {
    $omv  = new OMV_SMB_Manager();
    $baseName = preg_replace('/[^a-zA-Z0-9]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
    $pages = [];

    try {
        $imagick = new Imagick();
        $imagick->setResolution(150, 150);
        $imagick->readImage($pdfPath);

        foreach ($imagick as $pageNum => $page) {
            $page->setImageFormat('png');
            $page->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);

            $pngName  = time() . '_' . $baseName . '_page_' . ($pageNum + 1) . '.png';
            // Write to system tmp, upload to SMB, delete tmp
            $tmpPng   = sys_get_temp_dir() . '/' . $pngName;
            $page->writeImage($tmpPng);

            if (file_exists($tmpPng)) {
                $dest = rtrim($cloudDocsPath, '/') . '/' . $pngName;
                $status = $omv->paste_file($tmpPng, $dest);
                @unlink($tmpPng);

                if ($status === true) {
                    $pages[] = ['name' => $pngName, 'page' => $pageNum + 1];
                } else {
                    error_log("SMB PDF page upload failed: $dest :: $status");
                }
            }
        }

        $imagick->clear();
        $imagick->destroy();
        return ['success' => true, 'pages' => $pages];

    } catch (Exception $e) {
        error_log("PDF Conversion Error: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// ---------------- SMB FILE UPLOAD ----------------
function fileSaveInSmb(string $localPath, string $cloudFolderPath, string $fileName): bool {
    $omv  = new OMV_SMB_Manager();
    $dest = rtrim($cloudFolderPath, '/') . '/' . $fileName;
    $status = $omv->paste_file($localPath, $dest);
    if ($status !== true) {
        error_log("❌ SMB upload failed: $dest :: $status");
        return false;
    }
    return true;
}

// ---------------- INPUT ----------------
$travelerId   = $_GET['traveler_id']     ?? null;
$infoFileName = $_POST['info_file_name'] ?? null;
$infoDetails  = $_POST['information']    ?? null;
$pastedText   = $_POST['pasted_text']    ?? null;

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

// ---------------- BUILD SMB PATH ----------------
$cleanSysId     = preg_replace('/\s+/u', '', $traveler['sys_id']);
$cleanName      = preg_replace('/\s+/u', '', $traveler['name']);
$travelerFolder = "{$cleanSysId}_{$cleanName}";

$cloudBasePath = "{$SERVER_CUS_PATH}_travelers/{$travelerFolder}";
$cloudDocsPath = makeSMBDir($cloudBasePath, 'all_documents');
$cloudDocsPath = rtrim($cloudDocsPath, '/');

if (str_starts_with($cloudDocsPath, '❌')) {
    error_log("SMB folder creation failed: $cloudDocsPath");
    echo json_encode(['success' => false, 'message' => 'Cloud folder creation failed: ' . $cloudDocsPath]);
    exit;
}

// ---------------- PROCESS ----------------
$uploadedFiles = []; // file names stored on SMB
$conversionLog = [];
$errors        = [];

// Info file
if ($infoFileName && $infoDetails) {
    if (!pathinfo($infoFileName, PATHINFO_EXTENSION)) $infoFileName .= '.txt';
    $safeInfoFileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $infoFileName);
    $tmpInfo = sys_get_temp_dir() . '/' . $safeInfoFileName;
    file_put_contents($tmpInfo, $infoDetails);
    if (fileSaveInSmb($tmpInfo, $cloudDocsPath, $safeInfoFileName)) {
        $uploadedFiles[] = $safeInfoFileName;
    }
    @unlink($tmpInfo);
}

// Uploaded files
if (!empty($_FILES['files']['name'][0])) {
    foreach ($_FILES['files']['name'] as $key => $name) {
        if ($_FILES['files']['error'][$key] !== UPLOAD_ERR_OK) {
            $errors[] = "Upload error: $name";
            continue;
        }

        $ext      = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $tempPath = $_FILES['files']['tmp_name'][$key];

        if ($ext === 'pdf') {
            $result = convertPdfToPngsToSmb($tempPath, $name, $cloudDocsPath);
            if ($result['success']) {
                foreach ($result['pages'] as $pg) {
                    $uploadedFiles[] = $pg['name'];
                    $conversionLog[] = ['original_pdf' => $name, 'converted_to' => $pg['name'], 'page' => $pg['page']];
                }
            } else {
                $errors[] = "PDF conversion failed for $name: " . ($result['error'] ?? '');
            }

        } elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $safeName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
            if (fileSaveInSmb($tempPath, $cloudDocsPath, $safeName)) {
                $uploadedFiles[] = $safeName;
            } else {
                $errors[] = "SMB upload failed: $name";
            }

        } else {
            $errors[] = "Unsupported file type: $name (PDF, JPG, JPEG, PNG, GIF, WEBP only)";
        }
    }
}

// Pasted text
if (!empty($pastedText)) {
    $textFileName = time() . '_pasted_text.txt';
    $tmpText = sys_get_temp_dir() . '/' . $textFileName;
    file_put_contents($tmpText, $pastedText);
    if (fileSaveInSmb($tmpText, $cloudDocsPath, $textFileName)) {
        $uploadedFiles[] = $textFileName;
    }
    @unlink($tmpText);
}

// ---------------- RESPONSE ----------------
$response = [
    'success' => !empty($uploadedFiles),
    'files'   => $uploadedFiles,
    'path'    => "{$cloudBasePath}/all_documents", // SMB path reference (no local path)
    'message' => '',
];

if (!empty($conversionLog)) {
    $response['conversions'] = $conversionLog;
    $response['message'] = count($uploadedFiles) . ' file(s) saved to cloud. ' . count($conversionLog) . ' PDF(s) converted to images.';
} else {
    $response['message'] = count($uploadedFiles) . ' file(s) saved to cloud successfully';
}

if (!empty($errors)) {
    $response['warnings'] = $errors;
    $response['message'] .= ' Warnings: ' . implode(', ', $errors);
}

if (empty($uploadedFiles)) {
    $response['success'] = false;
    $response['message'] = 'No files uploaded. ' . (!empty($errors) ? implode(', ', $errors) : '');
}

echo json_encode($response);