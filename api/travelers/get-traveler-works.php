<?php
/**
 * FILE PATH: api/travelers/get-traveler-works.php
 *
 * Work Board tab — এই traveler কোন কোন work এ আছে সেটার list।
 * works.traveler_sys_ids JSON array তে traveler_sys_id থাকলে সেই work দেখাবে।
 *
 * INPUT (GET):
 *   traveler_id — travelers.sys_id
 *
 * OUTPUT (JSON):
 *   success, traveler_id, works[]
 */

require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';

$travelerSysId = trim($_GET['traveler_id'] ?? '');
if (!$travelerSysId) {
    jsonOut(['success' => false, 'message' => 'traveler_id is required']);
}

// works.traveler_sys_ids JSON array তে এই traveler আছে কিনা
$stmt = $pdo->prepare("
    SELECT
        w.sys_id, w.created_at,
        w.client_info,
        w.service_type,
        w.segment_data,
        w.status
    FROM works w
    WHERE JSON_CONTAINS(w.traveler_sys_ids, JSON_QUOTE(?))
    ORDER BY w.created_at DESC
    LIMIT 50
");
$stmt->execute([$travelerSysId]);
$works = $stmt->fetchAll(PDO::FETCH_ASSOC);

$result = [];
foreach ($works as $work) {
    $clientInfo  = json_decode($work['client_info']  ?? '{}', true) ?: [];
    $serviceType = json_decode($work['service_type'] ?? '[]', true) ?: [];

    $result[] = [
        'sys_id'       => $work['sys_id'],
        'status'       => $work['status'],
        'created_at'   => $work['created_at'],
        'client_name'  => $clientInfo['name']  ?? '',
        'client_phone' => $clientInfo['phone'] ?? '',
        'services'     => $serviceType,
        'work_url'     => "/pages/show-works.php?work_id={$work['sys_id']}",
    ];
}

jsonOut([
    'success'     => true,
    'traveler_id' => $travelerSysId,
    'total'       => count($result),
    'works'       => $result,
]);

function jsonOut(array $data): never
{
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}