<?php
require '../../server/db_connection.php';
require '../../server/uuid_with_system_id_generator.php';

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
    $uuid = generateIDs('clients');
    
    // Prepare SQL
    $stmt = $pdo->prepare("
        INSERT INTO clients (
            uuid,
            client_sys_id, 
            type, 
            name, 
            phone, 
            email, 
            address, 
            status, 
            created_by, 
            updated_by
        ) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    // Execute
    $stmt->execute([
        $uuid['uuid'],
        $uuid['sys_id'],
        $data['type'] ?? 'individual',
        $data['full_name'],
        json_encode($data['phone']),
        json_encode($data['email']),
        json_encode($data['address']),
        $data['status'] ?? 'active',
        $data['created_by'] ?? 'system',
        $data['created_by'] ?? 'system'
    ]);
    
    $clientId = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Client added successfully',
        'client_id' => $clientId,
        'client_uuid' => $uuid
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>