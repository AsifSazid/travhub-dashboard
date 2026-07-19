<?php
// FILE PATH: api/old_tasks/delete.php

require '../../server/db_connection.php';
require '../../server/live_storage.php';
require '../../server/safe_folder_name.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$data    = json_decode(file_get_contents('php://input'), true);
$task_id = $data['task_id'] ?? null;

if (!$task_id) {
    echo json_encode(['success' => false, 'message' => 'Task ID is required']);
    exit;
}

try {
    // Get task details
    $stmt = $pdo->prepare("
        SELECT t.sys_id, t.work_sys_id, t.all_file_name,
               w.client_sys_id, c.name AS client_name
        FROM old_tasks t
        JOIN com_works w ON w.sys_id = t.work_sys_id
        LEFT JOIN clients c ON c.sys_id = w.client_sys_id
        WHERE t.sys_id = ? LIMIT 1
    ");
    $stmt->execute([$task_id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$task) {
        echo json_encode(['success' => false, 'message' => 'Task not found']);
        exit;
    }

    // Block if financial entries exist
    $stmt = $pdo->prepare("SELECT sys_id FROM financial_entries WHERE task_sys_id = ? LIMIT 1");
    $stmt->execute([$task_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Can\'t delete — financial transactions exist for this task']);
        exit;
    }

    // ── Delete SMB folder ────────────────────────────────────
    $prefix = trim(@file_get_contents(__DIR__ . '/../../server-name.txt') ?? '');

    $words    = preg_split('/[\s_]+/', trim($task['client_name'] ?? 'Unknown'));
    $camel    = implode('', array_map('ucfirst', $words));
    $camel    = preg_replace('/[^A-Za-z0-9\-]/', '', $camel);
    $clFolder = ($task['client_sys_id'] ?? 'UNKNOWN') . '_' . $camel;

    $wFolder  = safeFolderName($task['work_sys_id']);
    $tFolder  = safeFolderName($task['sys_id']);
    $smbTask  = $prefix . '_clients/' . $clFolder . '/' . $wFolder . '/' . $tFolder;

    $omv = new OMV_SMB_Manager();
    $r   = $omv->delete_directory($smbTask);
    if ($r !== true) {
        error_log("[task delete] SMB delete failed: $smbTask → $r");
        // Don't block DB delete — log and continue
    }

    // ── Delete from DB ───────────────────────────────────────
    $stmt = $pdo->prepare("DELETE FROM old_tasks WHERE sys_id = ?");
    $stmt->execute([$task_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Task deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Task not found or already deleted']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}