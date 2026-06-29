<?php
/**
 * FILE PATH: /api/works/update-status.php
 * POST { sys_id, status }
 * status: open | in_progress | done | cancelled
 */

ob_start();
session_start();
date_default_timezone_set('Asia/Dhaka');

require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';
require_once '../../server/generate_meta_data.php';

$body  = json_decode(file_get_contents('php://input'), true) ?? [];
$sysId = $body['sys_id'] ?? '';
$status = $body['status'] ?? '';

$allowed = ['open', 'in_progress', 'done', 'cancelled'];

try {
    if (!$sysId)                     throw new Exception('sys_id is required');
    if (!in_array($status, $allowed)) throw new Exception('Invalid status');

    $s = $pdo->prepare("SELECT meta_data FROM works WHERE sys_id = ?");
    $s->execute([$sysId]);
    $existing = $s->fetchColumn();
    if ($existing === false) throw new Exception('Work not found');

    $meta = buildMetaData($existing, $_SESSION['user_name'] ?? 'system');
    $pdo->prepare("UPDATE works SET work_status=?, meta_data=? WHERE sys_id=?")
        ->execute([$status, $meta, $sysId]);

    ob_clean();
    echo json_encode(['status' => 'success', 'message' => 'Status updated']);

} catch (Exception $e) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}