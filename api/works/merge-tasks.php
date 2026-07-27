<?php
/**
 * FILE PATH: /api/works/merge-tasks.php
 *
 * POST { work_sys_id, task_ids: [id1, id2, ...] }
 *
 * Merges multiple tasks into one new task:
 * - Creates new task combining all data
 * - Sets is_merged=1 on source tasks
 * - Returns new task sys_id
 */
ob_start();
session_start();
date_default_timezone_set('Asia/Dhaka');
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';
require_once '../../server/sys_id_generator_v2.php';
require_once '../../server/generate_meta_data.php';

$raw  = file_get_contents('php://input');
$body = json_decode($raw, true) ?? [];

$workSysId = trim($body['work_sys_id'] ?? '');
$taskIds   = array_filter(array_map('trim', $body['task_ids'] ?? []));
$userName  = $_SESSION['user_name'] ?? 'system';

if (!$workSysId || count($taskIds) < 2) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'work_sys_id and at least 2 task_ids required']);
    exit;
}

try {
    // Fetch all source tasks
    $placeholders = implode(',', array_fill(0, count($taskIds), '?'));
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE sys_id IN ({$placeholders}) AND work_sys_id = ?");
    $stmt->execute([...$taskIds, $workSysId]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($tasks) < 2) throw new Exception('Could not find enough tasks to merge');

    // Decode JSON fields
    foreach ($tasks as &$t) {
        foreach (['traveler_id','special_ins','plans','quotation','booking','confirmation','meta_data'] as $col) {
            $t[$col] = $t[$col] ? json_decode($t[$col], true) : [];
        }
    }
    unset($t);

    // Build merged task data
    $first = $tasks[0];
    $allNames = array_map(fn($t) => $t['workname'] ?? '', $tasks);
    $mergedName = implode(' + ', array_filter($allNames)) ?: 'Merged Task';

    // Combine travelers (unique)
    $allTravelers = [];
    foreach ($tasks as $t) {
        foreach ((array)($t['traveler_id'] ?? []) as $tr) {
            if ($tr && !in_array($tr, $allTravelers)) $allTravelers[] = $tr;
        }
    }

    // Get work info for service_work
    $ws = $pdo->prepare("SELECT sys_id FROM service_works WHERE work_sys_id = ? AND service_slug = 'air_ticket' LIMIT 1");
    $ws->execute([$workSysId]);
    $swSysId = $ws->fetchColumn() ?: $first['service_work_sys_id'];

    // Create new merged task
    $newIds  = generateV2IDs($pdo, 'tasks');
    $newMeta = buildMetaData(null, $userName);
    $mergedTaskIds = array_map(fn($t) => $t['sys_id'], $tasks);

    $pdo->prepare("
        INSERT INTO tasks (
            uuid, sys_id, service_work_sys_id, work_sys_id,
            client_sys_id, workname, client_name,
            status, overall_status, service_slug,
            traveler_id, is_merged, meta_data
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'open', 'pending', ?, ?, 0, ?)
    ")->execute([
        $newIds['uuid'], $newIds['sys_id'],
        $swSysId, $workSysId,
        $first['client_sys_id'], $mergedName, $first['client_name'],
        $first['service_slug'] ?? 'air_ticket',
        json_encode($allTravelers),
        $newMeta,
    ]);

    // Mark source tasks as merged
    $updateStmt = $pdo->prepare("UPDATE tasks SET is_merged=1, merged_into=? WHERE sys_id=?");
    foreach ($mergedTaskIds as $id) {
        $updateStmt->execute([$newIds['sys_id'], $id]);
    }

    ob_clean();
    echo json_encode([
        'status'      => 'success',
        'new_task_id' => $newIds['sys_id'],
        'merged_from' => $mergedTaskIds,
    ]);

} catch (Exception $e) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}