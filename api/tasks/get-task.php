<?php
/**
 * FILE PATH: /api/tasks/get-task.php
 * GET ?id=THR-A26-TK-0001
 * Returns full task + parent work + financial entries
 */

ob_start();
session_start();
date_default_timezone_set('Asia/Dhaka');

require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';

$sysId = $_GET['id'] ?? '';
if (!$sysId) {
    ob_clean(); http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'id is required']); exit;
}

try {
    // 1. Task
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE sys_id = ? LIMIT 1");
    $stmt->execute([$sysId]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$task) { ob_clean(); http_response_code(404); echo json_encode(['status'=>'error','message'=>'Task not found']); exit; }

    $jsonCols = ['traveler_id','performed_by','special_ins','plans','quotation','booking','confirmation','meta_data'];
    foreach ($jsonCols as $c) { $task[$c] = $task[$c] ? json_decode($task[$c], true) : []; }

    // 2. Parent work
    $stmt2 = $pdo->prepare("SELECT sys_id, client_info, service_type, work_status, lead_sys_id FROM works WHERE sys_id = ? LIMIT 1");
    $stmt2->execute([$task['work_sys_id']]);
    $work = $stmt2->fetch(PDO::FETCH_ASSOC);
    if ($work) {
        $work['client_info']  = $work['client_info']  ? json_decode($work['client_info'],  true) : [];
        $work['service_type'] = $work['service_type'] ? json_decode($work['service_type'], true) : [];
    }

    // 3. Financial entries for this task
    $stmt3 = $pdo->prepare("
        SELECT * FROM financial_entries WHERE task_sys_id = ?
        ORDER BY id DESC
    ");
    $stmt3->execute([$sysId]);
    $entries = $stmt3->fetchAll(PDO::FETCH_ASSOC);
    foreach ($entries as &$e) { $e['meta_data'] = $e['meta_data'] ? json_decode($e['meta_data'], true) : []; }

    ob_clean();
    echo json_encode([
        'status'   => 'success',
        'task'     => $task,
        'work'     => $work,
        'entries'  => $entries,
    ]);

} catch (Exception $e) {
    ob_clean(); http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}