<?php
/**
 * FILE PATH: /api/works/all-works.php
 * TravHub — Works list API
 */

ob_start();
session_start();
date_default_timezone_set('Asia/Dhaka');

require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';

try {
    $sql = "
        SELECT *,
        STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(meta_data, '$.created_by_date.date')), '%d-%m-%Y %H:%i') as extracted_date
        FROM works
        ORDER BY extracted_date DESC
    ";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$r) {
        $r['client_info']         = $r['client_info']         ? json_decode($r['client_info'], true)         : [];
        $r['service_type']        = $r['service_type']        ? json_decode($r['service_type'], true)        : [];
        $r['service_data']        = $r['service_data']        ? json_decode($r['service_data'], true)        : [];
        $r['instruction']         = $r['instruction']         ? json_decode($r['instruction'], true)         : [];
        $r['special_instruction'] = $r['special_instruction'] ? json_decode($r['special_instruction'], true) : [];
        $r['lead_info']           = $r['lead_info']           ? json_decode($r['lead_info'], true)           : [];
        $r['lead_snapshot']       = $r['lead_snapshot']       ? json_decode($r['lead_snapshot'], true)       : [];
        $r['meta_data']           = $r['meta_data']           ? json_decode($r['meta_data'], true)           : [];
    }

    ob_clean();
    echo json_encode(['status' => 'success', 'works' => $rows]);

} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}