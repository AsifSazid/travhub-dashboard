<?php
session_start();
require_once '../../../server/api_bootstrap.php';
require_once '../../../server/db_connection.php';
require_once '../../../server/generate_meta_data.php';
require_once '../../../server/json-sync-helper.php';

$in             = json_decode(file_get_contents('php://input'), true) ?: [];
$country_sys_id = trim($in['country_sys_id'] ?? '');
$city_sys_id    = trim($in['city_sys_id']    ?? '');
if (!$country_sys_id || !$city_sys_id) {
    echo json_encode(['success'=>false,'message'=>'country_sys_id and city_sys_id required']); exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, cities, meta_data FROM countries WHERE sys_id=? LIMIT 1");
    $stmt->execute([$country_sys_id]);
    $country = $stmt->fetch();
    if (!$country) { echo json_encode(['success'=>false,'message'=>'Country not found']); exit; }

    $cities  = json_decode($country['cities'] ?? '[]', true) ?: [];
    $before  = count($cities);
    $cities  = array_values(array_filter($cities, fn($c) => $c['sys_id'] !== $city_sys_id));

    if (count($cities) === $before) {
        echo json_encode(['success'=>false,'message'=>'City not found']); exit;
    }

    $meta = buildMetaData($country['meta_data'], $_SESSION['user_name'] ?? 'system');
    $pdo->prepare("UPDATE countries SET cities=?,meta_data=? WHERE sys_id=?")
        ->execute([json_encode($cities, JSON_UNESCAPED_UNICODE), $meta, $country_sys_id]);

    syncCountriesJson($pdo);
    echo json_encode(['success'=>true,'message'=>'City deleted.']);
} catch (Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}