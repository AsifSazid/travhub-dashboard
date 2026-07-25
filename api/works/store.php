<?php
/**
 * FILE PATH: /api/works/store.php
 * Create a new work directly — no lead needed (walk-in, phone order)
 *
 * POST {
 *   client_sys_id, client_name, client_info: {},
 *   services: ["air_ticket", "hotel"],
 *   dept_map: { "air_ticket": "THR-A26-DP-0001" },  (optional)
 *   traveler_ids: [],
 *   instruction, special_instruction
 * }
 *
 * On service with dept → notifies ALL employees of that dept
 */

ob_start();
session_start();
date_default_timezone_set('Asia/Dhaka');

require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';
require_once '../../server/sys_id_generator_v2.php';
require_once '../../server/generate_meta_data.php';
require_once '../../server/make-dir.php';
require_once '../../server/make-smb-dir.php';

$body = json_decode(file_get_contents('php://input'), true) ?? [];

$SVC_NAMES = [
    'air_ticket'   => 'Air Ticket',
    'visa'         => 'Visa',
    'hotel'        => 'Hotel',
    'tour_package' => 'Tour Package',
    'umrah'        => 'Umrah',
    'transport'    => 'Transport',
];

try {
    $clientSysId = $body['client_sys_id'] ?? null;
    $clientName  = trim($body['client_name'] ?? '');
    $clientInfo  = $body['client_info'] ?? [];
    $services    = array_values(array_filter(array_map('trim', (array)($body['services'] ?? []))));
    $deptMap     = $body['dept_map']     ?? [];
    $travelerIds = $body['traveler_ids'] ?? [];
    $instruction = $body['instruction']  ?? '';
    $specialIns  = $body['special_instruction'] ?? '';
    $segmentType = $body['segment_type'] ?? null;
    $segmentData = $body['segment_data'] ?? null;

    if (!$clientName)    throw new Exception('client_name is required');
    if (empty($services)) throw new Exception('At least one service is required');

    $validSegTypes = ['one_way', 'round_trip', 'multi_city'];
    if (!in_array($segmentType, $validSegTypes, true)) $segmentType = null;

    $clientInfo['sys_id'] = $clientSysId;
    $clientInfo['name']   = $clientName;

    $userName        = $_SESSION['user_name'] ?? 'system';
    $SERVER_CUS_PATH = trim(@file_get_contents('../../server-name.txt') ?? '');
    $meta            = buildMetaData(null, $userName);

    // 1. Create work
    $workIds   = generateV2IDs($pdo, 'works');
    $workSysId = $workIds['sys_id'];

    if ($clientSysId) {
        $cleanSysId     = preg_replace('/\s+/u', '', $clientSysId);
        $cleanName      = preg_replace('/\s+/u', '', $clientName);
        $clientFolder   = 'clients/' . $cleanSysId . '_' . $cleanName;
        $cloudPath      = $SERVER_CUS_PATH . '_' . $clientFolder;
        $workFolderName = $workSysId . '+' . str_replace(' ', '_', $clientName);
        makeDir($clientFolder, $workFolderName);
        makeSMBDir($cloudPath, $workFolderName);
    }

    // Build special_instruction JSON array
    $specInsArr = is_array($specialIns) ? $specialIns : ($specialIns ? [$specialIns] : []);

    $pdo->prepare("
        INSERT INTO works (
            uuid, sys_id, lead_sys_id,
            client_info, service_type, service_count,
            segment_type, segment_data,
            service_data,
            instruction, special_instruction, lead_info, lead_snapshot,
            work_status, assigned_to, meta_data
        ) VALUES (?, ?, NULL, ?, ?, ?, ?, ?, '{}', ?, ?, '{}', NULL, 'open', NULL, ?)
    ")->execute([
        $workIds['uuid'], $workSysId,
        json_encode($clientInfo, JSON_UNESCAPED_UNICODE),
        json_encode($services, JSON_UNESCAPED_UNICODE),
        count($services),
        $segmentType,
        $segmentData ? json_encode($segmentData, JSON_UNESCAPED_UNICODE) : null,
        json_encode(['text' => $instruction], JSON_UNESCAPED_UNICODE),
        json_encode($specInsArr, JSON_UNESCAPED_UNICODE),
        $meta,
    ]);

    // 2. Create service_works + notify dept employees
    $createdSW = [];

    foreach ($services as $svcSlug) {
        $swIds    = generateV2IDs($pdo, 'service_works');
        $swMeta   = buildMetaData(null, $userName);
        $svcName  = $SVC_NAMES[$svcSlug] ?? ucfirst(str_replace('_', ' ', $svcSlug));
        $deptSysId= $deptMap[$svcSlug] ?? null;

        $pdo->prepare("
            INSERT INTO service_works (uuid, sys_id, work_sys_id, department_sys_id, service_slug, service_name, status, meta_data)
            VALUES (?, ?, ?, ?, ?, ?, 'open', ?)
        ")->execute([$swIds['uuid'], $swIds['sys_id'], $workSysId, $deptSysId, $svcSlug, $svcName, $swMeta]);

        // Notify dept employees
        if ($deptSysId) {
            _notifyDeptEmployees($pdo, $deptSysId, $workSysId, $swIds['sys_id'], $svcName, $clientName, $userName);
        }

        $createdSW[] = [
            'sys_id'       => $swIds['sys_id'],
            'service_slug' => $svcSlug,
            'service_name' => $svcName,
            'dept_sys_id'  => $deptSysId,
        ];
    }

    ob_clean();
    echo json_encode([
        'status'                => 'success',
        'message'               => 'Work created successfully.',
        'work_sys_id'           => $workSysId,
        'created_service_works' => $createdSW,
    ]);

} catch (Exception $e) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

function _notifyDeptEmployees(PDO $pdo, string $deptSysId, string $workSysId, string $swSysId, string $svcName, string $clientName, string $userName): void
{
    // Get department integer id
    $d = $pdo->prepare("SELECT id FROM departments WHERE sys_id=? LIMIT 1");
    $d->execute([$deptSysId]);
    $deptRow = $d->fetch(PDO::FETCH_ASSOC);
    if (!$deptRow) return;

    $deptIntId = $deptRow['id'];

    // All active employees in dept
    $emp = $pdo->prepare("SELECT sys_id FROM employees WHERE department_id=? AND (status='active' OR status IS NULL)");
    $emp->execute([$deptIntId]);
    $employees = $emp->fetchAll(PDO::FETCH_COLUMN);

    $title = "New Work: {$svcName}";
    $body_ = "A new '{$svcName}' work (Work #{$workSysId}) has been created for client {$clientName}. Please check and create a task.";

    if (empty($employees)) {
        // Dept-level fallback
        $ntIds  = generateV2IDs($pdo, 'notifications');
        $ntMeta = buildMetaData(null, $userName);
        $pdo->prepare("
            INSERT INTO notifications (uuid, sys_id, recipient_type, department_sys_id, user_sys_id, type, title, body, work_sys_id, service_work_sys_id, is_read, meta_data)
            VALUES (?, ?, 'department', ?, NULL, 'service_assigned', ?, ?, ?, ?, 0, ?)
        ")->execute([$ntIds['uuid'], $ntIds['sys_id'], $deptSysId, $title, $body_, $workSysId, $swSysId, $ntMeta]);
        return;
    }

    foreach ($employees as $empSysId) {
        $ntIds  = generateV2IDs($pdo, 'notifications');
        $ntMeta = buildMetaData(null, $userName);
        $pdo->prepare("
            INSERT INTO notifications (uuid, sys_id, recipient_type, department_sys_id, user_sys_id, type, title, body, work_sys_id, service_work_sys_id, is_read, meta_data)
            VALUES (?, ?, 'user', ?, ?, 'service_assigned', ?, ?, ?, ?, 0, ?)
        ")->execute([$ntIds['uuid'], $ntIds['sys_id'], $deptSysId, $empSysId, $title, $body_, $workSysId, $swSysId, $ntMeta]);
    }
}