<?php
// Prevent any accidental output before JSON
ob_start();

// Headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Suppress PHP warnings/notices (production-ready)
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Get raw JSON input
$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!$data) {
    ob_clean();
    echo json_encode(["status" => "error", "message" => "Invalid JSON"]);
    exit;
}

// DB connection
require 'db_connection.php';

// UUID generator
require 'uuid_generator.php';

try {
    $uuid = generateUUID();

    // Prepare SQL
    $sql = "INSERT INTO leads (
                uuid,
                service_count, 
                service_type, 
                client_info, 
                service_data, 
                lead_info, 
                lead_status,
                metadata
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);

    // Ensure all arrays/objects are properly json_encoded
    $stmt->execute([
        $uuid,
        isset($data['serviceCount']) ? $data['serviceCount'] : null,
        isset($data['serviceType']) ? json_encode($data['serviceType'], JSON_UNESCAPED_UNICODE) : null,
        isset($data['clientInfo']) ? json_encode($data['clientInfo'], JSON_UNESCAPED_UNICODE) : null,
        isset($data['serviceData']) ? json_encode($data['serviceData'], JSON_UNESCAPED_UNICODE) : null,
        isset($data['leadInfo']) ? json_encode($data['leadInfo'], JSON_UNESCAPED_UNICODE) : null,
        'pending',
        isset($data['metadata']) ? json_encode($data['metadata'], JSON_UNESCAPED_UNICODE) : null
    ]);

    // Clean output buffer before sending JSON
    ob_clean();
    echo json_encode([
        "status" => "success",
        "message" => "Data saved successfully",
        "leadId" => $uuid
    ]);
    exit;
} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
    exit;
}
