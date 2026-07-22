<?php
/**
 * FILE PATH: /api/tasks/get-tickets.php
 * 
 * GET ?task_sys_id=THR-A26-TK-0001&conf_sys_id=C-001
 * 
 * Returns all files_json entries for a specific confirmation
 * from the air_tickets table, with extracted_data for each file.
 * Multiple files → multiple pages in task-air.php
 */

require_once '../../server/db_connection.php';
header('Content-Type: application/json');

$taskSysId = $_GET['task_sys_id'] ?? '';
$confSysId = $_GET['conf_sys_id'] ?? '';

if (!$taskSysId || !$confSysId) {
    echo json_encode(['success' => false, 'message' => 'task_sys_id and conf_sys_id required']);
    exit;
}

try {
    // air_tickets table থেকে confirmation data নিই
    $stmt = $pdo->prepare("
        SELECT at_confirmations, meta_data
        FROM air_tickets
        WHERE task_sys_id = ?
        LIMIT 1
    ");
    $stmt->execute([$taskSysId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Air ticket record not found']);
        exit;
    }

    $confirmations = json_decode($row['at_confirmations'] ?? '[]', true) ?? [];
    $conf = null;
    foreach ($confirmations as $c) {
        if (($c['sys_id'] ?? '') === $confSysId) { $conf = $c; break; }
    }

    if (!$conf) {
        echo json_encode(['success' => false, 'message' => 'Confirmation not found']);
        exit;
    }

    $files = $conf['files_json'] ?? [];

    // Task + Work info for header
    $tStmt = $pdo->prepare("
        SELECT t.sys_id, t.workname, t.client_name, t.work_sys_id, t.service_slug,
               JSON_UNQUOTE(JSON_EXTRACT(w.client_info, '$.name')) AS client_name_from_work
        FROM tasks t
        LEFT JOIN works w ON w.sys_id = t.work_sys_id
        WHERE t.sys_id = ?
        LIMIT 1
    ");
    $tStmt->execute([$taskSysId]);
    $task = $tStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success'      => true,
        'task'         => $task,
        'confirmation' => [
            'sys_id'     => $conf['sys_id'] ?? '',
            'status'     => $conf['status'] ?? '',
            'note'       => $conf['note'] ?? '',
            'ticket_nos' => $conf['ticket_nos'] ?? [],
        ],
        'files'        => $files, // array of {name, file_name, smb_token, extracted_data, uploaded_at}
        'file_count'   => count($files),
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}