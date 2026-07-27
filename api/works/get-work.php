<?php
/**
 * FILE PATH: /api/works/get-work.php
 * TravHub — Single work + its service_works + tasks
 * GET ?id=THR-A26-WK-0001
 */

ob_start();
session_start();
date_default_timezone_set('Asia/Dhaka');

require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';

$sysId = $_GET['id'] ?? '';
if (!$sysId) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'id is required']);
    exit;
}

try {
    // 1. Fetch work
    $stmt = $pdo->prepare("SELECT * FROM works WHERE sys_id = ? LIMIT 1");
    $stmt->execute([$sysId]);
    $work = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$work) {
        ob_clean();
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Work not found']);
        exit;
    }

    // Decode JSON fields
    foreach (['client_info','service_type','service_data','segment_data','instruction','special_instruction','lead_info','lead_snapshot','meta_data'] as $col) {
        $work[$col] = $work[$col] ? json_decode($work[$col], true) : [];
    }

    // 2. Fetch service_works for this work
    $stmt2 = $pdo->prepare("
        SELECT sw.*, d.name as dept_name, d.slug as dept_slug
        FROM service_works sw
        LEFT JOIN departments d ON d.sys_id = sw.department_sys_id
        WHERE sw.work_sys_id = ?
        ORDER BY sw.id ASC
    ");
    $stmt2->execute([$sysId]);
    $serviceWorks = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    foreach ($serviceWorks as &$sw) {
        $sw['meta_data'] = $sw['meta_data'] ? json_decode($sw['meta_data'], true) : [];
    }

    // 3. Fetch tasks for this work
    $stmt3 = $pdo->prepare("
        SELECT t.*,
        STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(t.meta_data, '$.created_by_date.date')), '%d-%m-%Y %H:%i') as extracted_date
        FROM tasks t
        WHERE t.work_sys_id = ?
        ORDER BY t.id ASC
    ");
    $stmt3->execute([$sysId]);
    $tasks = $stmt3->fetchAll(PDO::FETCH_ASSOC);
    foreach ($tasks as &$t) {
        foreach (['traveler_id','performed_by','special_ins','plans','quotation','booking','confirmation','meta_data'] as $col) {
            $t[$col] = $t[$col] ? json_decode($t[$col], true) : [];
        }
    }

    // 4. Fetch air_tickets for this work (Phase 3 — work_sys_id based)
    $stmtAt = $pdo->prepare("SELECT * FROM air_tickets WHERE work_sys_id = ? LIMIT 1");
    $stmtAt->execute([$sysId]);
    $atRow = $stmtAt->fetch(PDO::FETCH_ASSOC);
    $airTicketData = null;
    if ($atRow) {
        foreach (['at_quotations','at_bookings','at_confirmations','meta_data'] as $col) {
            $atRow[$col] = ($atRow[$col] ?? null) ? json_decode($atRow[$col], true) : [];
        }
        // Legacy at_confirmation (single) → at_confirmations array
        if (!empty($atRow['at_confirmation']) && empty($atRow['at_confirmations'])) {
            $single = is_string($atRow['at_confirmation']) ? json_decode($atRow['at_confirmation'], true) : $atRow['at_confirmation'];
            if (is_array($single) && !empty($single)) {
                $atRow['at_confirmations'] = [array_merge(['sys_id'=>'C-001','added_at'=>'legacy'], $single)];
            }
        }
        $airTicketData = $atRow;
    }

    // 5. Work summary stats
    $totalTasks     = count($tasks);
    $completedTasks = count(array_filter($tasks, fn($t) => $t['status'] === 'done'));

    // Confirmed tasks (auto-created, not merged)
    $confirmedTasks = array_values(array_filter($tasks, fn($t) => !empty($t['confirmation_sys_id']) && empty($t['is_merged'])));

    ob_clean();
    echo json_encode([
        'status'          => 'success',
        'work'            => $work,
        'service_works'   => $serviceWorks,
        'tasks'           => $tasks,
        'confirmed_tasks' => $confirmedTasks,
        'air_ticket_data' => $airTicketData,
        'stats'           => [
            'total_tasks'     => $totalTasks,
            'completed_tasks' => $completedTasks,
            'remaining_tasks' => $totalTasks - $completedTasks,
        ],
    ]);

} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}