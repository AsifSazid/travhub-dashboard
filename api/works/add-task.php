<?php
/**
 * FILE PATH: /api/works/add-task.php
 * POST { service_work_sys_id, work_sys_id, client_sys_id, client_name, workname }
 * Creates a new task row under a service_work
 */

ob_start();
session_start();
date_default_timezone_set('Asia/Dhaka');

require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';
require_once '../../server/sys_id_generator_v2.php';
require_once '../../server/generate_meta_data.php';

$body = json_decode(file_get_contents('php://input'), true) ?? [];

try {
    $swSysId     = $body['service_work_sys_id'] ?? '';
    $workSysId   = $body['work_sys_id']         ?? '';
    $clientSysId = $body['client_sys_id']        ?? '';
    $clientName  = $body['client_name']          ?? '';
    $workname    = $body['workname']             ?? '';

    if (!$swSysId)   throw new Exception('service_work_sys_id is required');
    if (!$workSysId) throw new Exception('work_sys_id is required');

    $ids  = generateV2IDs($pdo, 'tasks');
    $meta = buildMetaData(null, $_SESSION['user_name'] ?? 'system');

    $stmt = $pdo->prepare("
        INSERT INTO tasks
            (uuid, sys_id, service_work_sys_id, work_sys_id, client_sys_id, client_name, workname, status, overall_status, meta_data)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, 'open', 'pending', ?)
    ");
    $stmt->execute([
        $ids['uuid'],
        $ids['sys_id'],
        $swSysId,
        $workSysId,
        $clientSysId ?: null,
        $clientName  ?: null,
        $workname    ?: null,
        $meta,
    ]);

    ob_clean();
    echo json_encode([
        'status'  => 'success',
        'message' => 'Task created',
        'sys_id'  => $ids['sys_id'],
    ]);

} catch (Exception $e) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}