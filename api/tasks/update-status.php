<?php
/**
 * FILE PATH: /api/tasks/update-status.php
 * POST { sys_id, status, overall_status? }
 */

ob_start();
session_start();
date_default_timezone_set('Asia/Dhaka');

require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';
require_once '../../server/generate_meta_data.php';

$body  = json_decode(file_get_contents('php://input'), true) ?? [];
$sysId = $body['sys_id'] ?? '';
$status= $body['status'] ?? '';
$overallStatus = $body['overall_status'] ?? null;

$allowed = ['open','in_progress','done','cancelled','on_hold'];

try {
    if (!$sysId)                      throw new Exception('sys_id is required');
    if (!in_array($status, $allowed)) throw new Exception('Invalid status');

    $s = $pdo->prepare("SELECT meta_data FROM tasks WHERE sys_id = ?");
    $s->execute([$sysId]);
    $existing = $s->fetchColumn();
    if ($existing === false) throw new Exception('Task not found');

    $meta = buildMetaData($existing, $_SESSION['user_name'] ?? 'system');

    if ($overallStatus) {
        $pdo->prepare("UPDATE tasks SET status=?, overall_status=?, meta_data=? WHERE sys_id=?")
            ->execute([$status, $overallStatus, $meta, $sysId]);
    } else {
        $pdo->prepare("UPDATE tasks SET status=?, meta_data=? WHERE sys_id=?")
            ->execute([$status, $meta, $sysId]);
    }

    ob_clean();
    echo json_encode(['status' => 'success', 'message' => 'Status updated']);

} catch (Exception $e) {
    ob_clean(); http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}