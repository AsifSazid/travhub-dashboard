<?php
// FILE PATH: /api/leads/update.php
// Prevent any accidental output before JSON
ob_start();
session_start();

// Headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, PUT");
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
require '../../server/generate_meta_data.php';

// Get lead ID from query string
$leadId = isset($_GET['lead']) ? trim($_GET['lead']) : '';

if (empty($leadId)) {
    ob_clean();
    echo json_encode(["status" => "error", "message" => "Lead ID is required"]);
    exit;
}

try {
    // First check if lead exists and get current meta_data
    $checkSql = "SELECT meta_data, sys_id FROM leads WHERE sys_id = ? OR uuid = ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$leadId, $leadId]);
    $existingLead = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existingLead) {
        ob_clean();
        echo json_encode(["status" => "error", "message" => "Lead not found"]);
        exit;
    }

    // Build meta_data using your existing function
    $userName = $_SESSION['user_name'] ?? 'system';
    $metaDataJson = buildMetaData(
        $existingLead['meta_data'],  // Pass existing meta_data
        $userName,                    // Current user
        20                            // Max updates (optional)
    );

    // Build update data
    $updateData = [];
    $params = [];

    if (isset($data['serviceCount'])) {
        $updateData[] = "service_count = ?";
        $params[] = $data['serviceCount'];
    }

    if (isset($data['serviceType'])) {
        $updateData[] = "service_type = ?";
        $params[] = json_encode($data['serviceType'], JSON_UNESCAPED_UNICODE);
    }

    if (isset($data['clientInfo'])) {
        $updateData[] = "client_info = ?";
        $params[] = json_encode($data['clientInfo'], JSON_UNESCAPED_UNICODE);
    }

    if (isset($data['serviceData'])) {
        $updateData[] = "service_data = ?";
        $params[] = json_encode($data['serviceData'], JSON_UNESCAPED_UNICODE);
    }

    if (isset($data['leadInfo'])) {
        $updateData[] = "lead_info = ?";
        $params[] = json_encode($data['leadInfo'], JSON_UNESCAPED_UNICODE);
    }

    if (isset($data['leadStatus'])) {
        $updateData[] = "lead_status = ?";
        $params[] = $data['leadStatus'];
    }

    // Update meta_data with new values
    $updateData[] = "meta_data = ?";
    $params[] = $metaDataJson;

    // Build the final SQL query (notice: no created_at/updated_at)
    $sql = "UPDATE leads SET " . implode(", ", $updateData) . " WHERE sys_id = ? OR uuid = ?";
    $params[] = $leadId;
    $params[] = $leadId;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Fetch updated lead data
    $fetchSql = "SELECT 
                    uuid,
                    sys_id,
                    service_count, 
                    service_type, 
                    client_info, 
                    service_data, 
                    lead_info, 
                    lead_status,
                    meta_data
                FROM leads 
                WHERE sys_id = ? OR uuid = ?";
    $fetchStmt = $pdo->prepare($fetchSql);
    $fetchStmt->execute([$leadId, $leadId]);
    $updatedLead = $fetchStmt->fetch(PDO::FETCH_ASSOC);

    // Decode JSON fields for response
    $updatedLead['service_type'] = json_decode($updatedLead['service_type'], true);
    $updatedLead['client_info'] = json_decode($updatedLead['client_info'], true);
    $updatedLead['service_data'] = json_decode($updatedLead['service_data'], true);
    $updatedLead['lead_info'] = json_decode($updatedLead['lead_info'], true);
    $updatedLead['meta_data'] = json_decode($updatedLead['meta_data'], true);

    ob_clean();
    echo json_encode([
        "status" => "success",
        "message" => "Lead updated successfully",
        "data" => $updatedLead
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