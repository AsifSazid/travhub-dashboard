<?php
require '../../server/db_connection.php';
require '../../server/uuid_with_system_id_generator.php';
require '../../server/generate_meta_data.php';


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
if (empty($data['name'])) {
    echo json_encode(['success' => false, 'message' => 'Vendor name is required']);
    exit;
}

if (empty($data['phone']) || empty($data['email'])) {
    echo json_encode(['success' => false, 'message' => 'At least one phone number and email is required']);
    exit;
}

$uuid = generateIDs('vendors');
$metaDataJson = buildMetaData(
    null,
    $_SESSION['user_name'] ?? 'system'
);

try {
    // Generate UUID & SYS ID for vendor

    // Prepare SQL
    $stmt = $pdo->prepare("
        INSERT INTO vendors (
            uuid,
            sys_id,
            type,
            name,
            email,
            phone,
            address,
            status,
            meta_data
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    // Execute
    $stmt->execute([
        $uuid['uuid'],
        $uuid['sys_id'],
        $data['type'] ?? 'local agent',
        $data['name'],
        json_encode($data['email']),
        json_encode($data['phone']),
        json_encode($data['address'] ?? null),
        $data['status'] ?? 'active',
        $metaDataJson
    ]);

    $vendorId = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Vendor added successfully',
        'vendor_id' => $vendorId,
        'vendor_uuid' => $uuid
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
