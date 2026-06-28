<?php
/**
 * FILE PATH: /api/leads/update.php
 * Thin wrapper — delegates to store.php logic for consistency.
 * Accepts same payload as store.php, updates lead by ?lead=SYS_ID
 *
 * POST ?lead=SYS_ID { client_info, service_type, service_data, lead_info, ... }
 */

ob_start();
session_start();
date_default_timezone_set('Asia/Dhaka');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);

require '../../server/db_connection.php';
require '../../server/generate_meta_data.php';

$input = file_get_contents('php://input');
$data  = json_decode($input, true);

if (!$data) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
    exit;
}

$leadId   = trim($_GET['lead'] ?? '');
$userName = $_SESSION['user_name'] ?? 'system';

if (!$leadId) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Lead ID required']);
    exit;
}

try {
    // Fetch existing meta
    $s = $pdo->prepare("SELECT meta_data FROM leads WHERE sys_id = ? LIMIT 1");
    $s->execute([$leadId]);
    $existingMeta = $s->fetchColumn();
    if ($existingMeta === false) {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Lead not found']);
        exit;
    }

    $meta = buildMetaData($existingMeta, $userName);

    // Build update fields — accept both snake_case and camelCase keys
    $clientInfo         = $data['client_info']         ?? $data['clientInfo']         ?? null;
    $serviceType        = $data['service_type']        ?? $data['serviceType']        ?? null;
    $serviceCount       = $data['service_count']       ?? $data['serviceCount']       ?? null;
    $serviceData        = $data['service_data']        ?? $data['serviceData']        ?? null;
    $leadInfo           = $data['lead_info']           ?? $data['leadInfo']           ?? null;
    $leadStatus         = $data['lead_status']         ?? $data['leadStatus']         ?? null;
    $instruction        = $data['instruction']         ?? [];
    $specialInstruction = $data['special_instruction'] ?? $data['specialInstruction'] ?? [];
    $assignedTo         = $data['assigned_to']         ?? $data['assignedTo']         ?? null;

    $sets   = ['meta_data = ?'];
    $params = [$meta];

    if ($clientInfo         !== null) { $sets[] = 'client_info = ?';          $params[] = json_encode($clientInfo,         JSON_UNESCAPED_UNICODE); }
    if ($serviceType        !== null) { $sets[] = 'service_type = ?';         $params[] = json_encode($serviceType,        JSON_UNESCAPED_UNICODE); }
    if ($serviceCount       !== null) { $sets[] = 'service_count = ?';        $params[] = $serviceCount; }
    if ($serviceData        !== null) { $sets[] = 'service_data = ?';         $params[] = json_encode($serviceData,        JSON_UNESCAPED_UNICODE); }
    if ($leadInfo           !== null) { $sets[] = 'lead_info = ?';            $params[] = json_encode($leadInfo,           JSON_UNESCAPED_UNICODE); }
    if ($leadStatus         !== null) { $sets[] = 'lead_status = ?';          $params[] = $leadStatus; }
    if (!empty($instruction))         { $sets[] = 'instruction = ?';          $params[] = json_encode($instruction,        JSON_UNESCAPED_UNICODE); }
    if ($specialInstruction !== null) { $sets[] = 'special_instruction = ?';  $params[] = json_encode($specialInstruction, JSON_UNESCAPED_UNICODE); }
    if (array_key_exists('assigned_to', $data) || array_key_exists('assignedTo', $data)) {
        $sets[]   = 'assigned_to = ?';
        $params[] = $assignedTo;
    }

    $params[] = $leadId;
    $pdo->prepare("UPDATE leads SET " . implode(', ', $sets) . " WHERE sys_id = ?")
        ->execute($params);

    ob_clean();
    echo json_encode(['status' => 'success', 'message' => 'Lead updated', 'sys_id' => $leadId]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}