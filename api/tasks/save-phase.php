<?php
/**
 * FILE PATH: /api/tasks/save-phase.php
 * POST { sys_id, phase: 'quotation'|'booking'|'confirmation', form_data: {}, raw_text, markup_text, total_amount }
 * Saves one phase (quotation/booking/confirmation) JSON + updates total column
 */

ob_start();
session_start();
date_default_timezone_set('Asia/Dhaka');

require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';
require_once '../../server/generate_meta_data.php';

$body  = json_decode(file_get_contents('php://input'), true) ?? [];
$sysId = $body['sys_id'] ?? '';
$phase = $body['phase']  ?? '';

$allowedPhases = ['quotation', 'booking', 'confirmation'];

try {
    if (!$sysId)                         throw new Exception('sys_id is required');
    if (!in_array($phase, $allowedPhases)) throw new Exception('Invalid phase');

    $s = $pdo->prepare("SELECT meta_data FROM tasks WHERE sys_id = ?");
    $s->execute([$sysId]);
    $existing = $s->fetchColumn();
    if ($existing === false) throw new Exception('Task not found');

    // Build phase JSON
    $phaseData = [
        'form_data'   => $body['form_data']   ?? [],
        'raw_text'    => $body['raw_text']    ?? '',
        'markup_text' => $body['markup_text'] ?? '',
        'total_amount'=> (float)($body['total_amount'] ?? 0),
        'saved_at'    => date('d-m-Y H:i'),
        'saved_by'    => $_SESSION['user_name'] ?? 'system',
    ];
    $phaseJson = json_encode($phaseData, JSON_UNESCAPED_UNICODE);

    // Column map
    $colMap = [
        'quotation'    => ['quotation',    'total_quotation'],
        'booking'      => ['booking',      'total_booking'],
        'confirmation' => ['confirmation', 'total_confirmation'],
    ];
    [$phaseCol, $totalCol] = $colMap[$phase];

    $total = (float)($body['total_amount'] ?? 0);
    $meta  = buildMetaData($existing, $_SESSION['user_name'] ?? 'system');

    $pdo->prepare("UPDATE tasks SET {$phaseCol}=?, {$totalCol}=?, meta_data=? WHERE sys_id=?")
        ->execute([$phaseJson, $total, $meta, $sysId]);

    ob_clean();
    echo json_encode(['status' => 'success', 'message' => ucfirst($phase) . ' saved']);

} catch (Exception $e) {
    ob_clean(); http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}