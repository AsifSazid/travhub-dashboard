<?php
// api/countries.php
header('Content-Type: application/json');
// include_once('../authenticate.php');

$jsonFile = '../countries.json';
if (!file_exists($jsonFile)) {
    echo json_encode(['success'=>false,'message'=>'countries.json not found']);
    exit;
}
$data = json_decode(file_get_contents($jsonFile), true);
echo json_encode(['success'=>true,'data'=>$data['countries'] ?? []]);