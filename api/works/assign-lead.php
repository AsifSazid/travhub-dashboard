<?php
/**
 * FILE PATH: /api/works/assign-lead.php
 * POST { work_sys_id, assigned_to: {name, sys_id} | null }
 *
 * "Work Lead" — the employee who owns full responsibility for this Work
 * (decides which employee gets which service). Uses works.assigned_to,
 * which already exists on the table but was always inserted as NULL.
 *
 * Follows the same convention as api/leads/assign.php.
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
require_once '../../server/sys_id_generator_v2.php';

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) { ob_clean(); echo json_encode(['status'=>'error','message'=>'Invalid JSON']); exit; }

$workSysId  = trim($data['work_sys_id'] ?? '');
$assignedTo = $data['assigned_to'] ?? null; // {name, sys_id} or null
$userName   = $_SESSION['user_name'] ?? 'system';

if (!$workSysId) { ob_clean(); echo json_encode(['status'=>'error','message'=>'work_sys_id is required']); exit; }

try {
    $s = $pdo->prepare("SELECT meta_data, assigned_to, client_info FROM works WHERE sys_id = ? LIMIT 1");
    $s->execute([$workSysId]);
    $work = $s->fetch(PDO::FETCH_ASSOC);
    if (!$work) { ob_clean(); echo json_encode(['status'=>'error','message'=>'Work not found']); exit; }

    $ci         = $work['client_info'] ? (json_decode($work['client_info'], true) ?? []) : [];
    $clientName = $ci['name'] ?? 'Unknown Client';
    $oldAssigned = $work['assigned_to'] ? json_decode($work['assigned_to'], true) : null;

    $meta           = buildMetaData($work['meta_data'], $userName);
    $assignedToJson = $assignedTo ? json_encode($assignedTo, JSON_UNESCAPED_UNICODE) : null;

    $pdo->prepare("UPDATE works SET assigned_to = ?, meta_data = ? WHERE sys_id = ?")
        ->execute([$assignedToJson, $meta, $workSysId]);

    // ── Notification ──────────────────────────────────────────
    if ($assignedTo && !empty($assignedTo['sys_id'])) {
        $link = "show-works.php?id={$workSysId}";

        $ntIds  = generateV2IDs($pdo, 'notifications');
        $ntMeta = buildMetaData(null, $userName);

        $pdo->prepare("
            INSERT INTO notifications
                (uuid, sys_id, recipient_type, department_sys_id, user_sys_id, type, title, body, work_sys_id, task_sys_id, service_work_sys_id, link, is_read, meta_data)
            VALUES (?, ?, 'user', NULL, ?, 'work_lead_assigned', ?, ?, ?, NULL, NULL, ?, 0, ?)
        ")->execute([
            $ntIds['uuid'], $ntIds['sys_id'],
            $assignedTo['sys_id'],
            'Work Lead Assigned: ' . $clientName,
            "You have been made the Work Lead for {$workSysId} — client: {$clientName}.",
            $workSysId,
            $link,
            $ntMeta,
        ]);

    } elseif (!$assignedTo && !empty($oldAssigned['sys_id'])) {
        $ntIds  = generateV2IDs($pdo, 'notifications');
        $ntMeta = buildMetaData(null, $userName);

        $pdo->prepare("
            INSERT INTO notifications
                (uuid, sys_id, recipient_type, department_sys_id, user_sys_id, type, title, body, work_sys_id, task_sys_id, service_work_sys_id, link, is_read, meta_data)
            VALUES (?, ?, 'user', NULL, ?, 'work_lead_unassigned', ?, ?, ?, NULL, NULL, NULL, 0, ?)
        ")->execute([
            $ntIds['uuid'], $ntIds['sys_id'],
            $oldAssigned['sys_id'],
            'Work Lead Removed',
            "You have been removed as Work Lead for {$workSysId} — client: {$clientName}.",
            $workSysId,
            $ntMeta,
        ]);
    }
    // ─────────────────────────────────────────────────────────

    ob_clean();
    echo json_encode([
        'status'      => 'success',
        'message'     => $assignedTo ? "Assigned to {$assignedTo['name']}" : 'Work Lead removed',
        'assigned_to' => $assignedTo,
    ]);
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}