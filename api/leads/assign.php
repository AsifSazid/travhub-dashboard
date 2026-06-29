<?php
/**
 * FILE PATH: /api/leads/assign.php
 * POST { sys_id, assigned_to: {name, sys_id} | null }
 */
ob_start();
session_start();
date_default_timezone_set('Asia/Dhaka');
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
ini_set('display_errors', 0);

require '../../server/db_connection.php';
require '../../server/generate_meta_data.php';

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) { ob_clean(); echo json_encode(['status'=>'error','message'=>'Invalid JSON']); exit; }

$sysId     = trim($data['sys_id'] ?? '');
$assignedTo = $data['assigned_to'] ?? null; // {name, sys_id} or null
$userName  = $_SESSION['user_name'] ?? 'system';

if (!$sysId) { ob_clean(); echo json_encode(['status'=>'error','message'=>'sys_id is required']); exit; }

try {
    $s = $pdo->prepare("SELECT meta_data FROM leads WHERE sys_id = ? AND deleted_at IS NULL LIMIT 1");
    $s->execute([$sysId]);
    $existingMeta = $s->fetchColumn();
    if ($existingMeta === false) { ob_clean(); echo json_encode(['status'=>'error','message'=>'Lead not found']); exit; }

    // ── Notification ──────────────────────────────────────────
    // Fetch lead client name + old assignee for notification
    $leadStmt = $pdo->prepare("SELECT client_info, assigned_to FROM leads WHERE sys_id = ? LIMIT 1");
    $leadStmt->execute([$sysId]);
    $leadRow    = $leadStmt->fetch(PDO::FETCH_ASSOC);
    $clientInfo = $leadRow ? json_decode($leadRow['client_info'], true) : [];
    $clientName = $clientInfo['name'] ?? 'Unknown Client';
    $oldAssigned = $leadRow ? json_decode($leadRow['assigned_to'], true) : null;

    $meta           = buildMetaData($existingMeta, $userName);
    $assignedToJson = $assignedTo ? json_encode($assignedTo, JSON_UNESCAPED_UNICODE) : null;

    $stmt = $pdo->prepare("UPDATE leads SET assigned_to = ?, meta_data = ? WHERE sys_id = ?");
    $stmt->execute([$assignedToJson, $meta, $sysId]);

    require_once '../../server/sys_id_generator_v2.php';

    if ($assignedTo && !empty($assignedTo['sys_id'])) {
        // ── New assignment notification ──
        $empStmt = $pdo->prepare("SELECT department_sys_id FROM employees WHERE sys_id = ? LIMIT 1");
        $empStmt->execute([$assignedTo['sys_id']]);
        $empRow = $empStmt->fetch(PDO::FETCH_ASSOC);
        $deptId = $empRow['department_sys_id'] ?? null;

        $ntIds  = generateV2IDs($pdo, 'notifications');
        $ntMeta = buildMetaData(null, $userName);

        $pdo->prepare("
            INSERT INTO notifications
                (uuid, sys_id, recipient_type, department_sys_id, user_sys_id, type, title, body, work_sys_id, task_sys_id, service_work_sys_id, is_read, meta_data)
            VALUES (?, ?, 'user', ?, ?, 'lead_assigned', ?, ?, NULL, NULL, NULL, 0, ?)
        ")->execute([
            $ntIds['uuid'], $ntIds['sys_id'],
            $deptId, $assignedTo['sys_id'],
            'Lead Assigned to You',
            "You have been assigned to lead {$sysId} — client: {$clientName}",
            $ntMeta,
        ]);

    } elseif (!$assignedTo && !empty($oldAssigned['sys_id'])) {
        // ── Unassign notification → notify the person who was removed ──
        $empStmt = $pdo->prepare("SELECT department_sys_id FROM employees WHERE sys_id = ? LIMIT 1");
        $empStmt->execute([$oldAssigned['sys_id']]);
        $empRow = $empStmt->fetch(PDO::FETCH_ASSOC);
        $deptId = $empRow['department_sys_id'] ?? null;

        $ntIds  = generateV2IDs($pdo, 'notifications');
        $ntMeta = buildMetaData(null, $userName);

        $pdo->prepare("
            INSERT INTO notifications
                (uuid, sys_id, recipient_type, department_sys_id, user_sys_id, type, title, body, work_sys_id, task_sys_id, service_work_sys_id, is_read, meta_data)
            VALUES (?, ?, 'user', ?, ?, 'lead_unassigned', ?, ?, NULL, NULL, NULL, 0, ?)
        ")->execute([
            $ntIds['uuid'], $ntIds['sys_id'],
            $deptId, $oldAssigned['sys_id'],
            'Lead Unassigned',
            "You have been removed from lead {$sysId} — client: {$clientName}",
            $ntMeta,
        ]);
    }
    // ─────────────────────────────────────────────────────────

    ob_clean();
    echo json_encode([
        'status'      => 'success',
        'message'     => $assignedTo ? "Assigned to {$assignedTo['name']}" : 'Assignee removed',
        'assigned_to' => $assignedTo,
    ]);
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}