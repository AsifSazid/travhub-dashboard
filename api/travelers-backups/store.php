<?php
require '../../server/db_connection.php';
require '../../server/uuid_with_system_id_generator.php';
require '../../server/generate_meta_data.php';
require '../../server/make-dir.php';
require '../../server/make-smb-dir.php';


header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit;
}

// Validate required fields
if (empty($data['full_name'])) {
    echo json_encode(['success' => false, 'message' => 'Full name is required']);
    exit;
}

if (empty($data['phone']) || empty($data['email'])) {
    echo json_encode(['success' => false, 'message' => 'At least one phone number and email is required']);
    exit;
}

try {
    // Generate UUID
    $uuid = generateIDs('travelers');
    $metaDataJson = buildMetaData(
        null,
        $_SESSION['user_name'] ?? 'system'
    );
    
    // make directory
    // Clean folder name parts
    $cleanSysId = preg_replace('/\s+/u', '', $uuid['sys_id']);
    $cleanFullName = preg_replace('/\s+/u', '', $data['full_name']);
    
    // Make folder name
    $travelerFolderName = $cleanSysId . '_' . $cleanFullName;
    
    $SERVER_CUS_PATH = trim(file_get_contents('../../server-name.txt')); // Server Naming 
    $server_traveler_path = makeDir('travelers', $travelerFolderName);
    $cloud_traveler_path = makeSMBDir("{$SERVER_CUS_PATH}_travelers", $travelerFolderName);
    
    
    // server
    makeDir("travelers/{$travelerFolderName}", 'all_documents');
    makeDir("travelers/{$travelerFolderName}", 'passport_identity');
    makeDir("travelers/{$travelerFolderName}", 'personal_documents');
    makeDir("travelers/{$travelerFolderName}", 'professional_documents');
    makeDir("travelers/{$travelerFolderName}", 'financial_documents');
    makeDir("travelers/{$travelerFolderName}", 'travel_history');
    makeDir("travelers/{$travelerFolderName}", 'photos_signature');
    makeDir("travelers/{$travelerFolderName}", 'country_tie_documents');
    makeDir("travelers/{$travelerFolderName}", 'nid');
    // makeDir("travelers/{$travelerFolderName}", 'all_documents');
    // makeDir("travelers/{$travelerFolderName}", 'office_documents');
    // makeDir("travelers/{$travelerFolderName}", 'others');
    // makeDir("travelers/{$travelerFolderName}", 'passports');
    
    //cloud server    
    makeSMBDir($cloud_traveler_path, 'all_documents');
    makeSMBDir($cloud_traveler_path, 'passport_identity');
    makeSMBDir($cloud_traveler_path, 'personal_documents');
    makeSMBDir($cloud_traveler_path, 'professional_documents');
    makeSMBDir($cloud_traveler_path, 'financial_documents');
    makeSMBDir($cloud_traveler_path, 'travel_history');
    makeSMBDir($cloud_traveler_path, 'photos_signature');
    makeSMBDir($cloud_traveler_path, 'country_tie_documents');
    makeSMBDir($cloud_traveler_path, 'nid');
    // makeSMBDir($cloud_traveler_path, 'all_documents');
    // makeSMBDir($cloud_traveler_path, 'office_documents');
    // makeSMBDir($cloud_traveler_path, 'others');
    // makeSMBDir($cloud_traveler_path, 'passports');
    
    // Prepare SQL
    $stmt = $pdo->prepare("
        INSERT INTO travelers (
            uuid,
            sys_id, 
            name, 
            phone, 
            email, 
            address, 
            status, 
            smb_path, 
            server_path, 
            meta_data
        ) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    // Execute
    $stmt->execute([
        $uuid['uuid'],
        $uuid['sys_id'],
        $data['full_name'],
        json_encode($data['phone']),
        json_encode($data['email']),
        json_encode($data['address']),
        $data['status'] ?? 'active',
        $cloud_traveler_path,
        $server_traveler_path,
        $metaDataJson
    ]);

    $travelerId = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Traveler added successfully',
        'traveler_id' => $travelerId,
        'traveler_uuid' => $uuid
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
