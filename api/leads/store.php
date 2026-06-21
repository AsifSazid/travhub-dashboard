<?php
// FILE PATH: /api/leads/store.php
// Prevent any accidental output before JSON
ob_start();
session_start();

// Headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Suppress PHP warnings/notices (production-ready)
ini_set('display_errors', 0);
error_reporting(E_ALL);

date_default_timezone_set('Asia/Dhaka');

// Get raw JSON input
$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!$data) {
    ob_clean();
    echo json_encode(["status" => "error", "message" => "Invalid JSON"]);
    exit;
}

// DB connection
require '../../server/db_connection.php';

// UUID generator
require '../../server/uuid_with_system_id_generator.php';
require '../../server/generate_meta_data.php';

try {
    $uuid = generateIDs('leads');
    
    // Build meta_data using your function for new record
    $userName = $_SESSION['user_name'] ?? 'system';
    $metaDataJson = buildMetaData(
        null,      // No existing meta_data for new record
        $userName, // Current user
        20         // Max updates (optional)
    );

    // Prepare SQL (notice: no created_at/updated_at columns)
    $sql = "INSERT INTO leads (
                uuid,
                sys_id,
                service_count, 
                service_type, 
                client_info, 
                service_data, 
                lead_info, 
                lead_status,
                meta_data
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);

    // Ensure all arrays/objects are properly json_encoded
    $stmt->execute([
        $uuid['uuid'],
        $uuid['sys_id'],
        isset($data['serviceCount']) ? $data['serviceCount'] : null,
        isset($data['serviceType']) ? json_encode($data['serviceType'], JSON_UNESCAPED_UNICODE) : null,
        isset($data['clientInfo']) ? json_encode($data['clientInfo'], JSON_UNESCAPED_UNICODE) : null,
        isset($data['serviceData']) ? json_encode($data['serviceData'], JSON_UNESCAPED_UNICODE) : null,
        isset($data['leadInfo']) ? json_encode($data['leadInfo'], JSON_UNESCAPED_UNICODE) : null,
        'pending',
        $metaDataJson,
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
?>