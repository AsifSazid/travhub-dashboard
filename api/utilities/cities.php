<?php
// api/cities.php
header('Content-Type: application/json');
// include_once('../authenticate.php');

$country_id = intval($_GET['country_id'] ?? 0);

$jsonFile = '../countries.json';
if (!file_exists($jsonFile)) {
    echo json_encode(['success'=>false,'message'=>'countries.json not found']);
    exit;
}
$data   = json_decode(file_get_contents($jsonFile), true);
$cities = $data['cities'] ?? [];

if ($country_id > 0) {
    $cities = array_values(array_filter($cities, fn($c) => $c['country_id'] === $country_id));
}

echo json_encode(['success'=>true,'data'=>$cities]);