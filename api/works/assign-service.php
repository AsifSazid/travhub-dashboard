<?php
/**
 * FILE PATH: /api/works/assign-service.php
 * POST { service_work_sys_id, assigned_to: {name, sys_id} | null }
 *
 * Assigns an employee to a specific service within a Work (a service_works row).
 * This is the "who actually works on this service" assignment — separate from
 * the Work Lead (works.assigned_to), who owns the whole Work and decides this.
 *
 * Follows the same convention as api/leads/assign.php / api/works/assign-lead.php.
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

$swSysId    = trim($data['service_work_sys_id'] ?? '');
$assignedTo = $data['assigned_to'] ?? null; // {name, sys_id} or null
$userName   = $_SESSION['user_name'] ?? 'system';

if (!$swSysId) { ob_clean(); echo json_encode(['status'=>'error','message'=>'service_work_sys_id is required']); exit; }

try {
    $s = $pdo->prepare("
        SELECT sw.meta_data, sw.assigned_to, sw.work_sys_id, sw.service_name, w.client_info
        FROM service_works sw
        LEFT JOIN works w ON w.sys_id = sw.work_sys_id
        WHERE sw.sys_id = ? LIMIT 1
    ");
    $s->execute([$swSysId]);
    $sw = $s->fetch(PDO::FETCH_ASSOC);
    if (!$sw) { ob_clean(); echo json_encode(['status'=>'error','message'=>'Service work not found']); exit; }

    $ci         = $sw['client_info'] ? (json_decode($sw['client_info'], true) ?? []) : [];
    $clientName = $ci['name'] ?? 'Unknown Client';
    $oldAssigned = $sw['assigned_to'] ? json_decode($sw['assigned_to'], true) : null;
    // NOTE: assigned_to on service_works is stored as VARCHAR sys_id + a separate
    // assigned_to_name column (not JSON) — matches the migration in
    // migrations/2026-09-16_service_works_assign.sql
    $oldAssignedSysId = $sw['assigned_to'] ?? null;

    $meta = buildMetaData($sw['meta_data'], $userName);

    $newSysId = $assignedTo['sys_id'] ?? null;
    $newName  = $assignedTo['name']   ?? null;

    $pdo->prepare("UPDATE service_works SET assigned_to = ?, assigned_to_name = ?, meta_data = ? WHERE sys_id = ?")
        ->execute([$newSysId, $newName, $meta, $swSysId]);

    // ── Notification ──────────────────────────────────────────
    if ($assignedTo && !empty($assignedTo['sys_id'])) {
        $link = "show-works.php?id={$sw['work_sys_id']}&sw={$swSysId}";

        $ntIds  = generateV2IDs($pdo, 'notifications');
        $ntMeta = buildMetaData(null, $userName);

        $pdo->prepare("
            INSERT INTO notifications
                (uuid, sys_id, recipient_type, department_sys_id, user_sys_id, type, title, body, work_sys_id, task_sys_id, service_work_sys_id, link, is_read, meta_data)
            VALUES (?, ?, 'user', NULL, ?, 'service_assigned', ?, ?, ?, NULL, ?, ?, 0, ?)
        ")->execute([
            $ntIds['uuid'], $ntIds['sys_id'],
            $assignedTo['sys_id'],
            'Service Assigned: ' . $sw['service_name'],
            "You have been assigned the {$sw['service_name']} service on work {$sw['work_sys_id']} — client: {$clientName}.",
            $sw['work_sys_id'],
            $swSysId,
            $link,
            $ntMeta,
        ]);

    } elseif (!$assignedTo && $oldAssignedSysId) {
        $ntIds  = generateV2IDs($pdo, 'notifications');
        $ntMeta = buildMetaData(null, $userName);

        $pdo->prepare("
            INSERT INTO notifications
                (uuid, sys_id, recipient_type, department_sys_id, user_sys_id, type, title, body, work_sys_id, task_sys_id, service_work_sys_id, link, is_read, meta_data)
            VALUES (?, ?, 'user', NULL, ?, 'service_unassigned', ?, ?, ?, NULL, ?, NULL, 0, ?)
        ")->execute([
            $ntIds['uuid'], $ntIds['sys_id'],
            $oldAssignedSysId,
            'Service Unassigned',
            "You have been removed from the {$sw['service_name']} service on work {$sw['work_sys_id']}.",
            $sw['work_sys_id'],
            $swSysId,
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