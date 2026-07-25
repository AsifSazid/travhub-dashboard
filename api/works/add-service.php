<?php
/**
 * FILE PATH: /api/works/add-service.php
 * POST { work_sys_id, service_slug, service_name, department_sys_id }
 *
 * 1. Inserts a row into service_works
 * 2. Fires a notification to the assigned department
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
    $workSysId    = $body['work_sys_id']      ?? '';
    $serviceSlug  = $body['service_slug']     ?? '';
    $serviceName  = $body['service_name']     ?? '';
    $deptSysId    = $body['department_sys_id'] ?? '';

    if (!$workSysId)   throw new Exception('work_sys_id is required');
    if (!$serviceSlug) throw new Exception('service_slug is required');
    if (!$serviceName) throw new Exception('service_name is required');

    // 1. Fetch work to get client info
    $ws = $pdo->prepare("SELECT client_info FROM works WHERE sys_id = ? LIMIT 1");
    $ws->execute([$workSysId]);
    $work = $ws->fetch(PDO::FETCH_ASSOC);
    if (!$work) throw new Exception('Work not found');

    $ci         = json_decode($work['client_info'], true) ?? [];
    $clientName = $ci['name'] ?? '—';

    $userName = $_SESSION['user_name'] ?? 'system';
    $meta     = buildMetaData(null, $userName);

    // 2. Insert service_work
    $swIds = generateV2IDs($pdo, 'service_works');
    $pdo->prepare("
        INSERT INTO service_works (uuid, sys_id, work_sys_id, department_sys_id, service_slug, service_name, status, meta_data)
        VALUES (?, ?, ?, ?, ?, ?, 'open', ?)
    ")->execute([
        $swIds['uuid'],
        $swIds['sys_id'],
        $workSysId,
        $deptSysId ?: null,
        $serviceSlug,
        $serviceName,
        $meta,
    ]);

    // 3. Fire notification to department (if department assigned)
    $notifSysId = null;
    if ($deptSysId) {
        $deptRow = $pdo->prepare("SELECT name FROM departments WHERE sys_id = ? LIMIT 1");
        $deptRow->execute([$deptSysId]);
        $dept = $deptRow->fetch(PDO::FETCH_ASSOC);
        $deptName = $dept['name'] ?? $deptSysId;

        $ntIds   = generateV2IDs($pdo, 'notifications');
        $ntMeta  = buildMetaData(null, $userName);
        $link    = "show-works.php?id={$workSysId}";
        $title   = "New Service: {$serviceName}";
        $body_msg= "Work {$workSysId} has a new '{$serviceName}' service. Client: {$clientName}";

        $pdo->prepare("
            INSERT INTO notifications
                (uuid, sys_id, recipient_type, department_sys_id, type, title, body, work_sys_id, service_work_sys_id, link, is_read, meta_data)
            VALUES
                (?, ?, 'department', ?, 'service_assigned', ?, ?, ?, ?, ?, 0, ?)
        ")->execute([
            $ntIds['uuid'],
            $ntIds['sys_id'],
            $deptSysId,
            $title,
            $body_msg,
            $workSysId,
            $swIds['sys_id'],
            $link,
            $ntMeta,
        ]);

        $notifSysId = $ntIds['sys_id'];
    }

    ob_clean();
    echo json_encode([
        'status'              => 'success',
        'message'             => 'Service added to work',
        'service_work_sys_id' => $swIds['sys_id'],
        'notification_sys_id' => $notifSysId,
    ]);

} catch (Exception $e) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}