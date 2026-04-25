<?php
header('Content-Type: application/json');
// include_once('../../authenticate.php');
require_once('../../server/db_connection.php');

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$uuid  = trim($input['uuid'] ?? '');
if (empty($uuid)) { echo json_encode(['success'=>false,'message'=>'UUID required']); exit; }

try {
    $pdo->prepare("UPDATE packages SET status='active', deleted_at=NULL WHERE uuid=:uuid")->execute([':uuid'=>$uuid]);
    echo json_encode(['success'=>true,'message'=>'Package restored']);
} catch (Exception $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }