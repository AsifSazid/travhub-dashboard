<?php
// FILE PATH: /api/leads/move-to-work.php
ob_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();
date_default_timezone_set('Asia/Dhaka');

require '../../server/db_connection.php';
require '../../server/sys_id_generator_v2.php';
require '../../server/generate_meta_data.php';
require '../../server/make-dir.php';
require '../../server/make-smb-dir.php';

$input  = json_decode(file_get_contents('php://input'), true);
$sys_id = $input['sys_id']   ?? null;
$deptMap= $input['dept_map'] ?? [];  // optional: { "air_ticket": "THR-A26-DP-0001" }

if (!$sys_id) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'sys_id is required.']);
    exit;
}

$SERVICE_NAME_MAP = [
    'air_ticket'   => 'Air Ticket',
    'visa'         => 'Visa',
    'hotel'        => 'Hotel',
    'tour_package' => 'Tour Package',
    'umrah'        => 'Umrah',
    'transport'    => 'Transport',
];

try {
    // 1. Fetch lead
    $stmt = $pdo->prepare("SELECT * FROM leads WHERE sys_id = ?");
    $stmt->execute([$sys_id]);
    $lead = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$lead) { ob_clean(); echo json_encode(['success' => false, 'message' => 'Lead not found.']); exit; }
    if ($lead['lead_status'] === 'converted') { ob_clean(); echo json_encode(['success' => false, 'message' => 'Lead already converted.']); exit; }

    $clientInfo  = json_decode($lead['client_info'],  true) ?? [];
    $serviceType = json_decode($lead['service_type'], true) ?? [];
    $serviceData = json_decode($lead['service_data'], true) ?? [];
    $clientName  = $clientInfo['name']   ?? 'Unknown';
    $clientSysId = $clientInfo['sys_id'] ?? null;
    $services    = array_values(array_filter(is_array($serviceType) ? $serviceType : [$serviceType]));
    $userName    = $_SESSION['user_name'] ?? 'system';
    $SERVER_CUS_PATH = trim(@file_get_contents('../../server-name.txt') ?? '');

    // 2. Create ONE work
    $workIds   = generateV2IDs($pdo, 'works');
    $workSysId = $workIds['sys_id'];
    $meta      = buildMetaData(null, $userName);

    if ($clientSysId) {
        $cleanSysId     = preg_replace('/\s+/u', '', $clientSysId);
        $cleanName      = preg_replace('/\s+/u', '', $clientName);
        $clientFolder   = 'clients/' . $cleanSysId . '_' . $cleanName;
        $cloudPath      = $SERVER_CUS_PATH . '_' . $clientFolder;
        $workFolderName = $workSysId . '+' . str_replace(' ', '_', $clientName);
        makeDir($clientFolder, $workFolderName);
        makeSMBDir($cloudPath, $workFolderName);
    }

    $pdo->prepare("
        INSERT INTO works (
            uuid, sys_id, lead_sys_id,
            client_info, service_type, service_count, service_data,
            instruction, special_instruction, lead_info, lead_snapshot,
            work_status, assigned_to, meta_data
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'open', NULL, ?)
    ")->execute([
        $workIds['uuid'], $workSysId, $sys_id,
        $lead['client_info'], $lead['service_type'], count($services), $lead['service_data'],
        $lead['instruction'], $lead['special_instruction'], $lead['lead_info'],
        json_encode($lead, JSON_UNESCAPED_UNICODE),
        $meta,
    ]);

    // 3. Create service_works + notify dept employees
    $createdSW = [];

    foreach ($services as $svcSlug) {
        $swIds    = generateV2IDs($pdo, 'service_works');
        $swMeta   = buildMetaData(null, $userName);
        $svcName  = $SERVICE_NAME_MAP[$svcSlug] ?? ucfirst(str_replace('_', ' ', $svcSlug));
        $deptSysId= $deptMap[$svcSlug] ?? null;

        $pdo->prepare("
            INSERT INTO service_works (uuid, sys_id, work_sys_id, department_sys_id, service_slug, service_name, status, meta_data)
            VALUES (?, ?, ?, ?, ?, ?, 'open', ?)
        ")->execute([$swIds['uuid'], $swIds['sys_id'], $workSysId, $deptSysId, $svcSlug, $svcName, $swMeta]);

        // Notify all employees in this department
        if ($deptSysId) {
            _notifyDeptEmployees($pdo, $deptSysId, $workSysId, $swIds['sys_id'], $svcName, $clientName, $userName);
        }

        $createdSW[] = [
            'sys_id'        => $swIds['sys_id'],
            'service_slug'  => $svcSlug,
            'service_name'  => $svcName,
            'dept_sys_id'   => $deptSysId,
        ];
    }

    // 4. Mark lead converted
    $leadMeta = buildMetaData($lead['meta_data'] ?? null, $userName);
    $pdo->prepare("UPDATE leads SET lead_status='converted', meta_data=? WHERE sys_id=?")
        ->execute([$leadMeta, $sys_id]);

    ob_clean();
    echo json_encode([
        'success'               => true,
        'status'                => 'success',
        'message'               => 'Lead converted to work successfully.',
        'work_sys_id'           => $workSysId,
        'created_service_works' => $createdSW,
    ]);

} catch (PDOException $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Notify ALL employees in a department when a service is assigned to them
 */
function _notifyDeptEmployees(PDO $pdo, string $deptSysId, string $workSysId, string $swSysId, string $svcName, string $clientName, string $userName): void
{
    // Get department integer id first
    $d = $pdo->prepare("SELECT id FROM departments WHERE sys_id=? LIMIT 1");
    $d->execute([$deptSysId]);
    $deptRow = $d->fetch(PDO::FETCH_ASSOC);
    if (!$deptRow) return;

    $deptIntId = $deptRow['id'];

    // Get all active employees in this department
    $emp = $pdo->prepare("SELECT sys_id, name FROM employees WHERE department_id=? AND status='active'");
    $emp->execute([$deptIntId]);
    $employees = $emp->fetchAll(PDO::FETCH_ASSOC);

    if (empty($employees)) {
        // Fallback: dept-level notification (no user target)
        _insertNotif($pdo, $deptSysId, null, $workSysId, null, $swSysId, $svcName, $clientName, $userName);
        return;
    }

    $title = "New Service: {$svcName}";
    $body_ = "Work {$workSysId} has a new '{$svcName}' service for client {$clientName}. Please handle this task.";

    foreach ($employees as $e) {
        _insertNotif($pdo, $deptSysId, $e['sys_id'], $workSysId, null, $swSysId, $title, $body_, $userName);
    }
}

function _insertNotif(PDO $pdo, ?string $deptSysId, ?string $userSysId, string $workSysId, ?string $taskSysId, ?string $swSysId, string $title, string $body_, string $userName): void
{
    $recipientType = $userSysId ? 'user' : 'department';
    $ntIds  = generateV2IDs($pdo, 'notifications');
    $ntMeta = buildMetaData(null, $userName);
    $pdo->prepare("
        INSERT INTO notifications
            (uuid, sys_id, recipient_type, department_sys_id, user_sys_id, type, title, body, work_sys_id, task_sys_id, service_work_sys_id, is_read, meta_data)
        VALUES
            (?, ?, ?, ?, ?, 'service_assigned', ?, ?, ?, ?, ?, 0, ?)
    ")->execute([
        $ntIds['uuid'], $ntIds['sys_id'],
        $recipientType, $deptSysId, $userSysId,
        $title, $body_,
        $workSysId, $taskSysId, $swSysId,
        $ntMeta,
    ]);
}