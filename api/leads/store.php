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

date_default_timezone_set('Asia/Dhaka');
$now = date('d-m-Y H:i:s');

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


// var_dump($data);
// die;
try {
    $uuid = generateIDs('leads');
    
    $metaDataJson = buildMetaData(
        null,
        $_SESSION['user_name'] ?? 'system'
    );

    // Prepare SQL
    $sql = "INSERT INTO leads (
                uuid,
                sys_id,
                service_count, 
                service_type, 
                client_info, 
                service_data, 
                lead_info, 
                lead_status,
                meta_data,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

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
        $now
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
