<?php

require '../../server/db_connection.php';

header('Content-Type: application/json');

$sysId = $_GET['sys_id'] ?? '';

// 1. Basic validation
if (empty($sysId)) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing sys_id parameter'
    ]);
    exit;
}

try {
    // 2. Fetch the specific record
    $stmt = $pdo->prepare("SELECT * FROM air_ticket_quatations WHERE sys_id = ? LIMIT 1");
    $stmt->execute([$sysId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // 3. Handle 404 (Not Found)
    if (!$row) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Document not found'
        ]);
        exit;
    }

    // 4. Process JSON columns
    // We map these to ensure they return as arrays/objects rather than raw strings
    $jsonFields = ['informations', 'quotations', 'form_data', 'meta_data'];
    
    foreach ($jsonFields as $field) {
        if (isset($row[$field])) {
            $decoded = json_decode($row[$field], true);
            // Default to empty array if decoding fails or is null
            $row[$field] = $decoded ?? [];
        }
    }

    // 5. Explicit Type Casting (Optional but recommended)
    // Ensures numeric values aren't returned as strings from the DB
    $row['percentage'] = (float)$row['percentage'];
    $row['ve_fixed_price'] = (float)$row['ve_fixed_price'];

    echo json_encode([
        'success' => true,
        'data' => $row
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal Server Error: ' . $e->getMessage()
    ]);
}