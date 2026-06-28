<?php
/**
 * FILE PATH: /api/leads/store.php
 * TravHub — Lead Store / Update API
 *
 * POST (no ?lead)    → create new lead
 * POST ?lead=SYS_ID  → update existing lead
 */

ob_start();
session_start();
date_default_timezone_set('Asia/Dhaka');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);

require '../../server/db_connection.php';
require '../../server/sys_id_generator_v2.php';
require '../../server/generate_meta_data.php';

$input = file_get_contents('php://input');
$data  = json_decode($input, true);

if (!$data) {
    ob_clean();
    echo json_encode(['status'=>'error','message'=>'Invalid JSON input']);
    exit;
}

$leadSysId  = $_GET['lead'] ?? '';
$isUpdate   = !empty($leadSysId);
$userName   = $_SESSION['user_name'] ?? 'system';

// ── Validate ────────────────────────────────────────────────
$clientInfo = $data['client_info'] ?? [];
$name       = trim($clientInfo['name'] ?? '');
if (!$name) {
    ob_clean();
    echo json_encode(['status'=>'error','message'=>'Client name is required']);
    exit;
}

$serviceType  = $data['service_type']  ?? [];
$serviceCount = $data['service_count'] ?? count($serviceType);
$serviceData  = $data['service_data']  ?? [];
$leadInfo     = $data['lead_info']     ?? [];
$instruction         = $data['instruction']         ?? [];
$specialInstruction  = $data['special_instruction'] ?? [];
// assigned_to can be {name, sys_id} object or null
$assignedToRaw = $data['assigned_to'] ?? null;
$assignedTo    = is_array($assignedToRaw)
    ? json_encode($assignedToRaw, JSON_UNESCAPED_UNICODE)
    : $assignedToRaw;

try {
    if ($isUpdate) {
        // ── UPDATE ─────────────────────────────────────────
        $s = $pdo->prepare("SELECT meta_data FROM leads WHERE sys_id = ?");
        $s->execute([$leadSysId]);
        $existingMeta = $s->fetchColumn();
        if ($existingMeta === false) {
            ob_clean();
            echo json_encode(['status'=>'error','message'=>'Lead not found']);
            exit;
        }

        $meta = buildMetaData($existingMeta, $userName);

        $stmt = $pdo->prepare("
            UPDATE leads SET
                client_info          = ?,
                service_type         = ?,
                service_count        = ?,
                service_data         = ?,
                instruction          = ?,
                special_instruction  = ?,
                lead_info            = ?,
                assigned_to          = ?,
                meta_data            = ?
            WHERE sys_id = ?
        ");
        $stmt->execute([
            json_encode($clientInfo,        JSON_UNESCAPED_UNICODE),
            json_encode($serviceType,       JSON_UNESCAPED_UNICODE),
            $serviceCount,
            json_encode($serviceData,       JSON_UNESCAPED_UNICODE),
            json_encode($instruction,       JSON_UNESCAPED_UNICODE),
            json_encode($specialInstruction,JSON_UNESCAPED_UNICODE),
            json_encode($leadInfo,          JSON_UNESCAPED_UNICODE),
            $assignedTo,
            $meta,
            $leadSysId,
        ]);

        ob_clean();
        echo json_encode(['status'=>'success','message'=>'Lead updated successfully','sys_id'=>$leadSysId]);

    } else {
        // ── CREATE ─────────────────────────────────────────
        $ids  = generateV2IDs($pdo, 'leads');
        $meta = buildMetaData(null, $userName);

        $stmt = $pdo->prepare("
            INSERT INTO leads (
                uuid, sys_id,
                client_info, service_type, service_count, service_data,
                instruction, special_instruction,
                lead_info, lead_status, assigned_to, meta_data
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?)
        ");
        $stmt->execute([
            $ids['uuid'],
            $ids['sys_id'],
            json_encode($clientInfo,         JSON_UNESCAPED_UNICODE),
            json_encode($serviceType,        JSON_UNESCAPED_UNICODE),
            $serviceCount,
            json_encode($serviceData,        JSON_UNESCAPED_UNICODE),
            json_encode($instruction,        JSON_UNESCAPED_UNICODE),
            json_encode($specialInstruction, JSON_UNESCAPED_UNICODE),
            json_encode($leadInfo,           JSON_UNESCAPED_UNICODE),
            $assignedTo,
            $meta,
        ]);

        ob_clean();
        echo json_encode([
            'status'  => 'success',
            'message' => 'Lead created successfully',
            'sys_id'  => $ids['sys_id'],
            'uuid'    => $ids['uuid'],
        ]);
    }

} catch (Exception $e) {
    ob_clean();
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}