<?php
/**
 * Store Traveler - Creates traveler with file management and duplicate validation
 */
session_start();
require '../../server/db_connection.php';
require '../../server/uuid_with_system_id_generator.php';
require '../../server/generate_meta_data.php';
require '../../server/make-dir.php';
require '../../server/make-smb-dir.php';
require_once '../../server/live_storage.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit;
}

if (empty($data['full_name'])) {
    echo json_encode(['success' => false, 'message' => 'Full name is required']);
    exit;
}

$forceCreate = $data['force_create'] ?? false;

try {
    if (!$forceCreate) {
        $duplicateCheck = checkForDuplicates($pdo, $data);
        if ($duplicateCheck['has_duplicates']) {
            echo json_encode([
                'success' => false,
                'message' => 'Duplicate traveler found. Please review or force create.',
                'duplicates' => $duplicateCheck['duplicates']
            ]);
            exit;
        }
    }
    
    // Generate IDs
    $uuid = generateIDs('travelers');
    $metaDataJson = buildMetaData(null, $_SESSION['user_name'] ?? 'system');
    
    // Clean sys_id and name - remove ALL spaces
    $cleanSysId = preg_replace('/\s+/u', '', $uuid['sys_id']);
    $cleanFullName = preg_replace('/\s+/u', '', $data['full_name']);
    $travelerFolderName = $cleanSysId . '_' . $cleanFullName;
    
    $SERVER_CUS_PATH = trim(file_get_contents('../../server-name.txt'));
    
    // Create directories
    $server_traveler_path = makeDir('travelers', $travelerFolderName);
    $cloud_traveler_path = makeSMBDir("{$SERVER_CUS_PATH}_travelers", $travelerFolderName);
    
    // Create subdirectories
    $subDirectories = [
        'all_documents', 'passport_identity', 'personal_documents',
        'professional_documents', 'financial_documents', 'travel_history',
        'photos_signature', 'countries_documents', 'nid'
    ];
    
    foreach ($subDirectories as $subDir) {
        $subDirPath = $server_traveler_path . '/' . $subDir;
        makeDir("travelers/{$travelerFolderName}", $subDir);
        
        // Fallback if makeDir fails
        if (!is_dir($subDirPath)) {
            $fullSubPath = '../../travelers/' . $travelerFolderName . '/' . $subDir;
            if (!is_dir($fullSubPath)) {
                mkdir($fullSubPath, 0777, true);
            }
        }
        
        makeSMBDir($cloud_traveler_path, $subDir);
    }
    
    // Handle file movement from tmp
    $documentType = $data['document_type'] ?? null;
    $filePath = $data['file_path'] ?? null;
    $movedFilePath = null;
    $movedCloudPath = null;
    
    if ($filePath && file_exists($filePath) && $documentType) {
        $targetSubDir = ($documentType === 'passport') ? 'passport_identity' : 'nid';
        
        // Clean filename
        $originalFileName = basename($filePath);
        $cleanFileName = preg_replace('/^tmp_[a-f0-9]+_/', '', $originalFileName);
        $cleanFileName = preg_replace('/\s+/u', '', $cleanFileName);
        
        // Build target path
        $targetDir = $server_traveler_path . '/' . $targetSubDir;
        $localTargetPath = $targetDir . '/' . $cleanFileName;
        
        // Ensure target directory exists
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        // Copy file from tmp to traveler folder
        if (file_exists($filePath) && is_readable($filePath)) {
            if (copy($filePath, $localTargetPath)) {
                $movedFilePath = $localTargetPath;
                
                // Delete the original tmp file
                unlink($filePath);
                
                // Copy to SMB/cloud storage
                if (class_exists('OMV_SMB_Manager')) {
                    try {
                        $omv = new OMV_SMB_Manager();
                        $cloudTargetPath = rtrim($cloud_traveler_path, '/') . "/{$targetSubDir}/{$cleanFileName}";
                        
                        $pasteStatus = $omv->paste_file($localTargetPath, $cloudTargetPath);
                        
                        if ($pasteStatus === true) {
                            $movedCloudPath = $cloudTargetPath;
                        }
                    } catch (Exception $e) {
                        // SMB copy failed but local copy succeeded
                    }
                }
            }
        }
    }
    
    // Prepare document info for DB
    $passportInfo = null;
    $nidInfo = null;
    $passportNo = null;
    $nidNo = null;
    
    if (isset($data['extracted_data']) && $data['extracted_data'] && $documentType) {
        $extractedData = $data['extracted_data'];
        
        // Add metadata
        $extractedData['_metadata'] = [
            'saved_at' => date('d-m-Y H:i:s'),
            'page_type' => $extractedData['page_type'] ?? $documentType,
            'source_file' => $movedFilePath ?? $filePath ?? 'unknown',
            'created_by' => $_SESSION['user_name'] ?? 'system'
        ];
        
        $docArray = [$extractedData];
        
        if ($documentType === 'passport') {
            $passportInfo = json_encode($docArray);
            $passportNo = $data['document_number'] ?? ($extractedData['bio_info']['passport_number'] ?? null);
        } else {
            $nidInfo = json_encode($docArray);
            $nidNo = $data['document_number'] ?? ($extractedData['nid_info']['nid_number'] ?? null);
        }
    } else {
        // Manual entry
        if ($documentType === 'passport' && !empty($data['document_number'])) {
            $passportNo = $data['document_number'];
        } elseif ($documentType === 'nid' && !empty($data['document_number'])) {
            $nidNo = $data['document_number'];
        }
    }
    
    // Insert into database
    $stmt = $pdo->prepare("
        INSERT INTO travelers (
            uuid, sys_id, name, date_of_birth,
            phone, email, address, status,
            smb_path, server_path, meta_data,
            passport_no, nid_no, passport_info, nid_info
        ) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $uuid['uuid'],
        $cleanSysId,
        $data['full_name'],
        $data['date_of_birth'] ?? null,
        json_encode(['primary_no' => '', 'secondary_no' => []]),
        json_encode(['primary' => '', 'secondary' => []]),
        json_encode(['address_line_1' => '', 'address_line_2' => '', 'city' => '', 'state' => '', 'zip_code' => '']),
        $data['status'] ?? 'active',
        $cloud_traveler_path,
        $server_traveler_path,
        $metaDataJson,
        $passportNo,
        $nidNo,
        $passportInfo,
        $nidInfo
    ]);
    
    $travelerId = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Traveler created successfully',
        'traveler_id' => $travelerId,
        'sys_id' => $cleanSysId,
        'folder' => $travelerFolderName
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

function checkForDuplicates($pdo, $data) {
    $fullName = trim($data['full_name'] ?? '');
    $documentNumber = trim($data['document_number'] ?? '');
    $documentType = $data['document_type'] ?? '';
    $dateOfBirth = trim($data['date_of_birth'] ?? '');
    
    $duplicates = [];
    
    if (empty($fullName) && empty($documentNumber)) {
        return ['has_duplicates' => false, 'duplicates' => []];
    }
    
    if (!empty($fullName) && !empty($documentNumber)) {
        $column = ($documentType === 'passport') ? 'passport_no' : 'nid_no';
        $stmt = $pdo->prepare("SELECT sys_id, name FROM travelers WHERE name = ? AND {$column} = ?");
        $stmt->execute([$fullName, $documentNumber]);
        if ($stmt->fetch()) {
            $duplicates[] = ['type' => 'name_document_match'];
        }
    }
    
    if (!empty($fullName) && !empty($documentNumber) && !empty($dateOfBirth)) {
        $column = ($documentType === 'passport') ? 'passport_no' : 'nid_no';
        $stmt = $pdo->prepare("SELECT sys_id, name FROM travelers WHERE name = ? AND {$column} = ? AND date_of_birth = ?");
        $stmt->execute([$fullName, $documentNumber, $dateOfBirth]);
        if ($stmt->fetch()) {
            $duplicates[] = ['type' => 'full_match'];
        }
    }
    
    if (!empty($fullName) && !empty($dateOfBirth)) {
        $stmt = $pdo->prepare("SELECT sys_id, name FROM travelers WHERE name = ? AND date_of_birth = ?");
        $stmt->execute([$fullName, $dateOfBirth]);
        if ($stmt->fetch()) {
            $duplicates[] = ['type' => 'name_dob_match'];
        }
    }
    
    return [
        'has_duplicates' => !empty($duplicates),
        'duplicates' => $duplicates
    ];
}
?>