<?php
/**
 * FILE PATH: /api/tasks/get-task.php
 * GET ?id=THR-A26-TK-0001
 *
 * Returns:
 *   task        → full task row (JSON cols decoded)
 *   work        → parent work (client_info, service_type decoded)
 *   entries     → financial_entries for this task
 *   at_data     → air_tickets row if service_slug = 'air_ticket' (null otherwise)
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
    // ── 1. Task ───────────────────────────────────────────────
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE sys_id = ? LIMIT 1");
    $stmt->execute([$sysId]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$task) {
        ob_clean(); http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Task not found']); exit;
    }

    $jsonCols = ['traveler_id', 'performed_by', 'special_ins', 'plans', 'quotation', 'booking', 'confirmation', 'meta_data'];
    foreach ($jsonCols as $c) {
        $task[$c] = $task[$c] ? json_decode($task[$c], true) : [];
    }

    // ── 2. Parent work ────────────────────────────────────────
    $stmt2 = $pdo->prepare("
        SELECT sys_id, client_info, service_type, work_status, lead_sys_id
        FROM works WHERE sys_id = ? LIMIT 1
    ");
    $stmt2->execute([$task['work_sys_id']]);
    $work = $stmt2->fetch(PDO::FETCH_ASSOC);
    if ($work) {
        $work['client_info']  = $work['client_info']  ? json_decode($work['client_info'],  true) : [];
        $work['service_type'] = $work['service_type'] ? json_decode($work['service_type'], true) : [];
    }

    // ── 3. Financial entries ──────────────────────────────────
    $stmt3 = $pdo->prepare("
        SELECT * FROM financial_entries WHERE task_sys_id = ?
        ORDER BY id DESC
    ");
    $stmt3->execute([$sysId]);
    $entries = $stmt3->fetchAll(PDO::FETCH_ASSOC);
    foreach ($entries as &$e) {
        $e['meta_data'] = $e['meta_data'] ? json_decode($e['meta_data'], true) : [];
    }
    unset($e);

    // ── 4. Service-specific data ──────────────────────────────
    $serviceSlug = $task['service_slug'] ?? null;
    $atData      = null;

    if ($serviceSlug === 'air_ticket') {
        $stmt4 = $pdo->prepare("SELECT * FROM air_tickets WHERE task_sys_id = ? LIMIT 1");
        $stmt4->execute([$sysId]);
        $atRow = $stmt4->fetch(PDO::FETCH_ASSOC);

        if ($atRow) {
            foreach (['at_quotations', 'at_bookings', 'at_confirmations'] as $col) {
                $atRow[$col] = (isset($atRow[$col]) && $atRow[$col]) ? json_decode($atRow[$col], true) : [];
            }
            // Legacy migration
            if (empty($atRow['at_confirmations']) && isset($atRow['at_confirmation']) && $atRow['at_confirmation']) {
                $single = json_decode($atRow['at_confirmation'], true);
                if (is_array($single) && !empty($single)) {
                    $atRow['at_confirmations'] = [array_merge(['sys_id'=>'C-001','added_at'=>'legacy'], $single)];
                }
            }
            $atRow['meta_data'] = (isset($atRow['meta_data']) && $atRow['meta_data']) ? json_decode($atRow['meta_data'], true) : [];
            $atData = $atRow;
        }
        // null হলে frontend init call করবে
    }

    ob_clean();
    echo json_encode([
        'status'  => 'success',
        'task'    => $task,
        'work'    => $work,
        'entries' => $entries,
        'at_data' => $atData,   // air_ticket হলে data, অন্য service হলে null
    ]);

} catch (Exception $e) {
    ob_clean(); http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}