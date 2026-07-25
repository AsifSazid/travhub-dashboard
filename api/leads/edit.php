<?php
// FILE PATH: /api/leads/edit.php
// Prevent any accidental output before JSON
ob_start();
session_start();

// Headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Suppress PHP warnings/notices (production-ready)
ini_set('display_errors', 0);
error_reporting(E_ALL);

// DB connection
require '../../server/db_connection.php';

// Get lead ID from query string
$leadId = isset($_GET['lead']) ? trim($_GET['lead']) : '';

if (empty($leadId)) {
    ob_clean();
    echo json_encode(["status" => "error", "message" => "Lead ID is required"]);
    exit;
}

try {
    // Query to fetch lead data
    $sql = "SELECT 
                uuid,
                sys_id,
                service_count, 
                service_type, 
                client_info, 
                service_data, 
                lead_info, 
                lead_status,
                assigned_to,
                meta_data
            FROM leads 
            WHERE sys_id = ? OR uuid = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$leadId, $leadId]);
    $lead = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$lead) {
        ob_clean();
        echo json_encode(["status" => "error", "message" => "Lead not found"]);
        exit;
    }

    // Decode JSON fields
    $lead['service_type'] = json_decode($lead['service_type'], true);
    $lead['client_info']  = json_decode($lead['client_info'],  true);
    $lead['service_data'] = json_decode($lead['service_data'], true);
    $lead['lead_info']    = json_decode($lead['lead_info'],    true);
    $lead['assigned_to']  = json_decode($lead['assigned_to'],  true);
    $lead['meta_data']    = json_decode($lead['meta_data'],    true);

    // Clean output buffer before sending JSON
    ob_clean();
    echo json_encode([
        "status" => "success",
        "message" => "Lead fetched successfully",
        "data" => $lead
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