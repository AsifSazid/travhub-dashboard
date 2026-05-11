<?php
/**
 * Update Traveler Information API
 * Handles updating basic info, passport info, and NID info
 */

require '../../server/db_connection.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit;
}

$travelerId = $data['traveler_id'] ?? null;
$category = $data['category'] ?? null;
$updateData = $data['data'] ?? null;

if (!$travelerId || !$category || !$updateData) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    switch ($category) {
        case 'phone':
            updatePhone($pdo, $travelerId, $updateData);
            break;
            
        case 'email':
            updateEmail($pdo, $travelerId, $updateData);
            break;
            
        case 'address':
            updateAddress($pdo, $travelerId, $updateData);
            break;
            
        case 'passport_info':
            updatePassportInfo($pdo, $travelerId, $updateData);
            break;
            
        case 'nid_info':
            updateNidInfo($pdo, $travelerId, $updateData);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid category']);
            exit;
    }
    
    echo json_encode(['success' => true, 'message' => 'Updated successfully']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

/**
 * Update phone information
 */
function updatePhone($pdo, $travelerId, $data) {
    $stmt = $pdo->prepare("SELECT phone FROM travelers WHERE sys_id = ?");
    $stmt->execute([$travelerId]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$current) {
        throw new Exception('Traveler not found');
    }
    
    $phoneData = json_decode($current['phone'] ?? '{}', true) ?: [];
    
    if (isset($data['primary_no'])) {
        $phoneData['primary_no'] = $data['primary_no'];
    }
    
    if (isset($data['secondary_no'])) {
        $phoneData['secondary_no'] = $data['secondary_no'];
    }
    
    $stmt = $pdo->prepare("UPDATE travelers SET phone = ? WHERE sys_id = ?");
    $stmt->execute([json_encode($phoneData), $travelerId]);
}

/**
 * Update email information
 */
function updateEmail($pdo, $travelerId, $data) {
    $stmt = $pdo->prepare("SELECT email FROM travelers WHERE sys_id = ?");
    $stmt->execute([$travelerId]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$current) {
        throw new Exception('Traveler not found');
    }
    
    $emailData = json_decode($current['email'] ?? '{}', true) ?: [];
    
    if (isset($data['primary'])) {
        $emailData['primary'] = $data['primary'];
    }
    
    if (isset($data['secondary'])) {
        $emailData['secondary'] = $data['secondary'];
    }
    
    $stmt = $pdo->prepare("UPDATE travelers SET email = ? WHERE sys_id = ?");
    $stmt->execute([json_encode($emailData), $travelerId]);
}

/**
 * Update address information
 */
function updateAddress($pdo, $travelerId, $data) {
    $stmt = $pdo->prepare("SELECT address FROM travelers WHERE sys_id = ?");
    $stmt->execute([$travelerId]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$current) {
        throw new Exception('Traveler not found');
    }
    
    $addressData = json_decode($current['address'] ?? '{}', true) ?: [];
    
    $addressData = array_merge($addressData, $data);
    
    $stmt = $pdo->prepare("UPDATE travelers SET address = ? WHERE sys_id = ?");
    $stmt->execute([json_encode($addressData), $travelerId]);
}

/**
 * Update passport information
 */
function updatePassportInfo($pdo, $travelerId, $data) {
    $stmt = $pdo->prepare("SELECT passport_info FROM travelers WHERE sys_id = ?");
    $stmt->execute([$travelerId]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$current) {
        throw new Exception('Traveler not found');
    }
    
    $passportInfo = json_decode($current['passport_info'] ?? '[]', true) ?: [];
    
    if (empty($passportInfo)) {
        $passportInfo = [
            [
                'page_type' => 'bio_page',
                'bio_info' => [],
                '_metadata' => [
                    'saved_at' => date('d-m-Y H:i:s'),
                    'created_by' => 'manual_edit'
                ]
            ]
        ];
    }
    
    // Handle emergency contact separately as nested object
    if (isset($data['emergency_contact'])) {
        if (!isset($passportInfo[0]['bio_info']['emergency_contact'])) {
            $passportInfo[0]['bio_info']['emergency_contact'] = [
                'name' => '',
                'relationship' => '',
                'address' => '',
                'telephone' => ''
            ];
        }
        $passportInfo[0]['bio_info']['emergency_contact'] = array_merge(
            $passportInfo[0]['bio_info']['emergency_contact'],
            $data['emergency_contact']
        );
        unset($data['emergency_contact']);
    }
    
    // Update other passport fields
    foreach ($data as $key => $value) {
        $passportInfo[0]['bio_info'][$key] = $value;
    }
    
    // Update _metadata
    $passportInfo[0]['_metadata']['updated_at'] = date('d-m-Y H:i:s');
    
    // Also update passport_no in travelers table if passport_number changed
    if (isset($data['passport_number'])) {
        $stmt2 = $pdo->prepare("UPDATE travelers SET passport_no = ? WHERE sys_id = ?");
        $stmt2->execute([$data['passport_number'], $travelerId]);
    }
    
    $stmt = $pdo->prepare("UPDATE travelers SET passport_info = ? WHERE sys_id = ?");
    $stmt->execute([json_encode($passportInfo), $travelerId]);
}

/**
 * Update NID information
 */
function updateNidInfo($pdo, $travelerId, $data) {
    $stmt = $pdo->prepare("SELECT nid_info FROM travelers WHERE sys_id = ?");
    $stmt->execute([$travelerId]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$current) {
        throw new Exception('Traveler not found');
    }
    
    $nidInfo = json_decode($current['nid_info'] ?? '[]', true) ?: [];
    
    if (empty($nidInfo)) {
        $nidInfo = [
            [
                'page_type' => 'nid_front',
                'nid_info' => [],
                '_metadata' => [
                    'saved_at' => date('d-m-Y H:i:s'),
                    'created_by' => 'manual_edit'
                ]
            ]
        ];
    }
    
    foreach ($data as $key => $value) {
        $nidInfo[0]['nid_info'][$key] = $value;
    }
    
    $nidInfo[0]['_metadata']['updated_at'] = date('d-m-Y H:i:s');
    
    // Also update nid_no in travelers table if nid_number changed
    if (isset($data['nid_number'])) {
        $stmt2 = $pdo->prepare("UPDATE travelers SET nid_no = ? WHERE sys_id = ?");
        $stmt2->execute([$data['nid_number'], $travelerId]);
    }
    
    $stmt = $pdo->prepare("UPDATE travelers SET nid_info = ? WHERE sys_id = ?");
    $stmt->execute([json_encode($nidInfo), $travelerId]);
}
?>