<?php
// api/packages/update.php
header('Content-Type: application/json');
// include_once('../../authenticate.php');
require_once('../../server/db_connection.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success'=>false,'message'=>'Method not allowed']); exit; }

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$uuid  = trim($input['uuid'] ?? '');
if (empty($uuid)) { echo json_encode(['success'=>false,'message'=>'UUID required']); exit; }

try {
    $pdo  = getDBConnection();
    $sets = []; $params = [':uuid' => $uuid];
    $allowed = ['title','description','rating','completion_status','status'];
    foreach ($allowed as $f) {
        if (isset($input[$f])) { $sets[] = "$f = :$f"; $params[":$f"] = $input[$f]; }
    }
    if (empty($sets)) { echo json_encode(['success'=>true,'message'=>'Nothing to update']); exit; }
    $pdo->prepare("UPDATE packages SET ".implode(',',$sets)." WHERE uuid = :uuid")->execute($params);
    echo json_encode(['success'=>true,'message'=>'Updated successfully']);
} catch (Exception $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }