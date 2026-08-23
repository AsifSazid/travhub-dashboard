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
require_once '../../server/sys_id_generator_v2.php';

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
        'professional_documents', 'financial_documents', 'travel_history', 'travel_documents',
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
        
        // Systematic filename — Smart Upload (commit-documents.php)-এর একই convention:
        // {stem}_p1.{ext}, যেমন current_passport_bio_page_p1.jpg / nid_p1.jpg
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) ?: 'jpg';
        $stem = ($documentType === 'passport') ? 'current_passport_bio_page' : 'nid';
        $cleanFileName = "{$stem}_p1.{$ext}";
        
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

    // ── traveler_documents row (initial passport/NID scan) ──────────────────
    // শুধু SMB upload সফল হলেই row বসাবো — যাতে Documents tab এ orphan/broken
    // entry না দেখায় (commit-documents.php এর একই discipline অনুসরণ করে)
    $initialDocSysId = null;
    if ($movedCloudPath && $documentType) {
        $docNumberForInsert = ($documentType === 'passport') ? $passportNo : $nidNo;
        $initialDocSysId = insertInitialTravelerDocument(
            $pdo,
            $cleanSysId,
            $documentType,
            $targetSubDir,
            $docNumberForInsert,
            $extractedData ?? [],
            $cleanFileName ?? null,
            $movedFilePath,
            $_SESSION['user_name'] ?? 'system'
        );
    }

    // Extra fields from create form
    $phoneVal = $data['phone']
        ? json_encode(['primary_no' => $data['phone'], 'secondary_no' => []])
        : json_encode(['primary_no' => '', 'secondary_no' => []]);

    $emailVal = $data['email']
        ? json_encode(['primary' => $data['email'], 'secondary' => []])
        : json_encode(['primary' => '', 'secondary' => []]);

    // passport bio_info থেকে auto-populate
    $bio = [];
    if (!empty($extractedData)) {
        $bio = $extractedData['bio_info'] ?? $extractedData ?? [];
    }
    if (empty($bio) && !empty($docArray)) {
        $bio = $docArray[0]['bio_info'] ?? [];
    }

    $presentAddr   = $data['address']['present']   ?? '';
    $permanentAddr = $data['address']['permanent']  ?? ($bio['permanent_address'] ?? '');
    $addressVal    = json_encode(['present' => $presentAddr, 'permanent' => $permanentAddr]);

    $personalData = $data['personal_info'] ?? [];
    if (empty($personalData['gender']) && !empty($bio['sex'])) {
        $sex = strtolower($bio['sex']);
        $personalData['gender'] = $sex === 'm' ? 'male' : ($sex === 'f' ? 'female' : $sex);
    }
    if (empty($personalData['place_of_birth']) && !empty($bio['place_of_birth'])) {
        $personalData['place_of_birth'] = $bio['place_of_birth'];
    }
    $personalInfo = !empty($personalData) ? json_encode($personalData) : null;

    $familyData = $data['family_info'] ?? [];
    if (empty($familyData['father_name']) && !empty($bio['father_name'])) {
        $familyData['father_name'] = $bio['father_name'];
    }
    if (empty($familyData['mother_name']) && !empty($bio['mother_name'])) {
        $familyData['mother_name'] = $bio['mother_name'];
    }
    if (empty($familyData['spouse_name']) && !empty($bio['spouse_name'])) {
        $familyData['spouse_name'] = $bio['spouse_name'];
    }
    if (empty($familyData['emergency_contact']) && !empty($bio['emergency_contact'])) {
        $ec = $bio['emergency_contact'];
        $familyData['emergency_contact'] = is_array($ec)
            ? ($ec['name'] ?? '') . ' — ' . ($ec['telephone'] ?? '')
            : (string)$ec;
    }
    $familyInfo = !empty($familyData) ? json_encode($familyData) : null;

    // Insert into database
    $stmt = $pdo->prepare("
        INSERT INTO travelers (
            uuid, sys_id, name, date_of_birth,
            phone, email, address, status,
            smb_path, server_path, meta_data,
            passport_no, nid_no, passport_info, nid_info,
            personal_info, family_info
        ) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $uuid['uuid'],
        $cleanSysId,
        $data['full_name'],
        $data['date_of_birth'] ?? null,
        $phoneVal,
        $emailVal,
        $addressVal,
        $data['status'] ?? 'active',
        $cloud_traveler_path,
        $server_traveler_path,
        $metaDataJson,
        $passportNo,
        $nidNo,
        $passportInfo,
        $nidInfo,
        $personalInfo,
        $familyInfo,
    ]);
    
    $travelerId = $pdo->lastInsertId();

    // work_sys_id দেওয়া থাকলে automatically link করো
    if (!empty($data['work_sys_id'])) {
        $workSysId = $data['work_sys_id'];
        $wStmt = $pdo->prepare("SELECT traveler_sys_ids FROM works WHERE sys_id = ? LIMIT 1");
        $wStmt->execute([$workSysId]);
        $work = $wStmt->fetch(PDO::FETCH_ASSOC);
        if ($work) {
            $refs = json_decode($work['traveler_sys_ids'] ?? '[]', true) ?? [];
            // Already linked না থাকলে add করো
            $alreadyLinked = array_filter($refs, fn($r) => ($r['traveler_sys_id'] ?? '') === $cleanSysId);
            if (empty($alreadyLinked)) {
                $refs[] = ['traveler_sys_id' => $cleanSysId, 'name' => $data['full_name']];
                $pdo->prepare("UPDATE works SET traveler_sys_ids = ? WHERE sys_id = ?")
                    ->execute([json_encode($refs), $workSysId]);
            }
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Traveler created successfully',
        'traveler_id' => $cleanSysId,
        'sys_id' => $cleanSysId,
        'folder' => $travelerFolderName,
        'initial_document_sys_id' => $initialDocSysId
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

/**
 * insertInitialTravelerDocument — traveler create সময় passport/NID scan এর জন্য
 * traveler_documents এ একটা row বসায়, ঠিক যেভাবে commit-documents.php Smart Upload
 * এর সময় করে (একই টেবিল, একই smb_folder convention)।
 */
function insertInitialTravelerDocument(
    PDO $pdo,
    string $travelerSysId,
    string $documentType,      // 'passport' | 'nid'
    string $smbFolder,         // 'passport_identity' | 'nid'
    ?string $docNumber,
    array  $extractedData,
    ?string $filename,
    ?string $serverFilePath,
    string $createdBy
): ?string {
    try {
        $v2    = generateV2IDs($pdo, 'traveler_documents');
        $uuid  = $v2['uuid'];
        $sysId = $v2['sys_id'];

        $now = date('Y-m-d H:i:s');
        $metaData = json_encode([
            'created_by_date' => ['by' => $createdBy, 'date' => $now],
            'updated_by_date' => [],
        ]);

        $summary = $extractedData['summary'] ?? null;
        $docData = $extractedData ? json_encode($extractedData, JSON_UNESCAPED_UNICODE) : null;

        $filenameStem = $filename
            ? preg_replace('/\.[^.]+$/', '', $filename)
            : null;

        $stmt = $pdo->prepare("
            INSERT INTO traveler_documents (
                uuid, sys_id, traveler_id, batch_id,
                doc_type, doc_number,
                passport_status, summary, doc_data,
                confidence, needs_review, classification_mode,
                original_filename, suggested_filename_stem,
                smb_folder, server_path,
                page_count, pages,
                is_primary, status, meta_data
            ) VALUES (
                ?, ?, ?, NULL,
                ?, ?,
                ?, ?, ?,
                ?, 0, 'manual',
                ?, ?,
                ?, ?,
                1, NULL,
                1, 'active', ?
            )
        ");

        $stmt->execute([
            $uuid, $sysId, $travelerSysId,
            $documentType, $docNumber ?: null,
            $documentType === 'passport' ? 'current' : null,
            $summary, $docData,
            1.0,
            $filename, $filenameStem,
            $smbFolder, $serverFilePath,
            $metaData,
        ]);

        return $sysId;
    } catch (Throwable $e) {
        error_log('[store.php] insertInitialTravelerDocument failed: ' . $e->getMessage());
        return null;
    }
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