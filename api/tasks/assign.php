<?php
/**
 * FILE PATH: /api/tasks/assign.php
 * POST { sys_id, assigned_to, performed_by?: [], holding_on?, traveler_id?: [] }
 *
 * Notification:
 *  - Notify the ASSIGNED employee only (user_sys_id = their sys_id)
 *  - Also notify their department (if dept found via employees.department_id → departments)
 *  - NO broadcast to all depts
 */

ob_start();
session_start();
date_default_timezone_set('Asia/Dhaka');

require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';
require_once '../../server/sys_id_generator_v2.php';
require_once '../../server/generate_meta_data.php';

$body  = json_decode(file_get_contents('php://input'), true) ?? [];
$sysId = $body['sys_id'] ?? '';

try {
    if (!$sysId) throw new Exception('sys_id is required');

    // 1. Fetch task
    $s = $pdo->prepare("SELECT * FROM tasks WHERE sys_id = ? LIMIT 1");
    $s->execute([$sysId]);
    $task = $s->fetch(PDO::FETCH_ASSOC);
    if (!$task) throw new Exception('Task not found: ' . $sysId);

    $assignedTo  = $body['assigned_to']  ?? null;
    $performedBy = isset($body['performed_by']) ? json_encode($body['performed_by'], JSON_UNESCAPED_UNICODE) : null;
    $holdingOn   = isset($body['holding_on'])   ? json_encode($body['holding_on'],   JSON_UNESCAPED_UNICODE) : null;
    $travelerIds = isset($body['traveler_id'])   ? json_encode($body['traveler_id'],  JSON_UNESCAPED_UNICODE) : null;
    $userName    = $_SESSION['user_name'] ?? 'system';
    $meta        = buildMetaData($task['meta_data'] ?? null, $userName);

    // 2. Update task
    if ($travelerIds !== null) {
        $pdo->prepare("UPDATE tasks SET assigned_to=?, performed_by=?, holding_on=?, traveler_id=?, meta_data=? WHERE sys_id=?")
            ->execute([$assignedTo, $performedBy, $holdingOn, $travelerIds, $meta, $sysId]);
    } else {
        $pdo->prepare("UPDATE tasks SET assigned_to=?, performed_by=?, holding_on=?, meta_data=? WHERE sys_id=?")
            ->execute([$assignedTo, $performedBy, $holdingOn, $meta, $sysId]);
    }

    // 3. Notification — only if someone is assigned
    $notifCreated = [];

    if ($assignedTo) {
        $workSysId = $task['work_sys_id'] ?? '';
        $title = "Task Assigned to You";
        $body_ = "Task \"{$task['workname']}\" ({$sysId}) has been assigned to you."
               . " Client: " . ($task['client_name'] ?? '—')
               . ". Work: {$workSysId}.";

        // a) Find employee by sys_id to get their department
        $emp = $pdo->prepare("SELECT sys_id, department_id, department_name FROM employees WHERE sys_id = ? LIMIT 1");
        $emp->execute([$assignedTo]);
        $empRow = $emp->fetch(PDO::FETCH_ASSOC);

        // b) Resolve department_sys_id from employees.department_id (integer)
        //    departments table may use id (int) or sys_id (string)
        $deptSysId = null;
        if ($empRow && $empRow['department_id']) {
            // Try by id first (int FK)
            $deptRow = $pdo->prepare("SELECT sys_id FROM departments WHERE id = ? LIMIT 1");
            $deptRow->execute([$empRow['department_id']]);
            $deptData = $deptRow->fetch(PDO::FETCH_ASSOC);
            $deptSysId = $deptData['sys_id'] ?? null;
        }

        // c) Delete any previous task_assigned notification for this task
        //    (prevents duplicate entries when re-assigning)
        $pdo->prepare("DELETE FROM notifications WHERE task_sys_id = ? AND type = 'task_assigned'")->execute([$sysId]);

        // d) Insert notification for the assigned employee (user_sys_id)
        $ntIds  = generateV2IDs($pdo, 'notifications');
        $ntMeta = buildMetaData(null, $userName);
        $pdo->prepare("
            INSERT INTO notifications
                (uuid, sys_id, recipient_type, department_sys_id, user_sys_id, type, title, body, work_sys_id, task_sys_id, is_read, meta_data)
            VALUES
                (?, ?, 'user', ?, ?, 'task_assigned', ?, ?, ?, ?, 0, ?)
        ")->execute([
            $ntIds['uuid'], $ntIds['sys_id'],
            $deptSysId,      // also tag dept for dept-level filtering
            $assignedTo,     // user_sys_id = employee's sys_id
            $title, $body_,
            $workSysId, $sysId, $ntMeta,
        ]);
        $notifCreated[] = $ntIds['sys_id'];
    }

    ob_clean();
    echo json_encode([
        'status'           => 'success',
        'message'          => 'Task assignment updated',
        'notifications'    => $notifCreated,
    ]);

} catch (Exception $e) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}