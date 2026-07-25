<?php
/**
 * FILE PATH: /api/works/assign.php
 * POST { sys_id, assigned_to: {name, sys_id} | null }
 *
 * Assigns or unassigns an employee to a work.
 * Sends notification with link to show-works.php.
 */
ob_start();
session_start();
date_default_timezone_set('Asia/Dhaka');
header('Content-Type: application/json');
ini_set('display_errors', 0);

require '../../server/db_connection.php';
require '../../server/generate_meta_data.php';
require_once '../../server/sys_id_generator_v2.php';

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) { ob_clean(); echo json_encode(['status'=>'error','message'=>'Invalid JSON']); exit; }

$sysId      = trim($data['sys_id'] ?? '');
$assignedTo = $data['assigned_to'] ?? null; // {name, sys_id} or null
$userName   = $_SESSION['user_name'] ?? 'system';

if (!$sysId) { ob_clean(); echo json_encode(['status'=>'error','message'=>'sys_id is required']); exit; }

try {
    $s = $pdo->prepare("SELECT meta_data, client_info, assigned_to FROM works WHERE sys_id = ? LIMIT 1");
    $s->execute([$sysId]);
    $work = $s->fetch(PDO::FETCH_ASSOC);
    if (!$work) { ob_clean(); echo json_encode(['status'=>'error','message'=>'Work not found']); exit; }

    $ci         = json_decode($work['client_info'], true) ?? [];
    $clientName = $ci['name'] ?? 'Unknown';
    $oldAssigned = json_decode($work['assigned_to'], true);

    $meta           = buildMetaData($work['meta_data'], $userName);
    $assignedToJson = $assignedTo ? json_encode($assignedTo, JSON_UNESCAPED_UNICODE) : null;

    $pdo->prepare("UPDATE works SET assigned_to = ?, meta_data = ? WHERE sys_id = ?")
        ->execute([$assignedToJson, $meta, $sysId]);

    $link = "show-works.php?id={$sysId}";

    if ($assignedTo && !empty($assignedTo['sys_id'])) {
        $ntIds  = generateV2IDs($pdo, 'notifications');
        $ntMeta = buildMetaData(null, $userName);
        $pdo->prepare("
            INSERT INTO notifications
                (uuid, sys_id, recipient_type, department_sys_id, user_sys_id, type, title, body, work_sys_id, link, is_read, meta_data)
            VALUES (?, ?, 'user', NULL, ?, 'service_assigned', ?, ?, ?, ?, 0, ?)
        ")->execute([
            $ntIds['uuid'], $ntIds['sys_id'],
            $assignedTo['sys_id'],
            'Work Assigned to You',
            "Work {$sysId} — client: {$clientName} has been assigned to you.",
            $sysId,
            $link,
            $ntMeta,
        ]);

    } elseif (!$assignedTo && !empty($oldAssigned['sys_id'])) {
        $ntIds  = generateV2IDs($pdo, 'notifications');
        $ntMeta = buildMetaData(null, $userName);
        $pdo->prepare("
            INSERT INTO notifications
                (uuid, sys_id, recipient_type, department_sys_id, user_sys_id, type, title, body, work_sys_id, link, is_read, meta_data)
            VALUES (?, ?, 'user', NULL, ?, 'service_assigned', ?, ?, ?, NULL, 0, ?)
        ")->execute([
            $ntIds['uuid'], $ntIds['sys_id'],
            $oldAssigned['sys_id'],
            'Work Unassigned',
            "You have been removed from work {$sysId} — client: {$clientName}.",
            $sysId,
            $ntMeta,
        ]);
    }

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