<?php
session_start();
require_once '../../../server/api_bootstrap.php';
require_once '../../../server/db_connection.php';
require_once '../../../server/generate_meta_data.php';
$in=json_decode(file_get_contents('php://input'),true)?:[];
$sys_id=trim($in['sys_id']??'');
if (!$sys_id){echo json_encode(['success'=>false,'message'=>'sys_id required']);exit;}
try {
    $r=$pdo->prepare("SELECT currency_code,meta_data FROM fx_rates WHERE sys_id=? LIMIT 1"); $r->execute([$sys_id]); $e=$r->fetch();
    if (!$e){echo json_encode(['success'=>false,'message'=>'Not found']);exit;}
    $meta=buildMetaData($e['meta_data'],$_SESSION['user_name']??'system');
    $pdo->prepare("UPDATE fx_rates SET status='deleted',meta_data=? WHERE sys_id=?")->execute([$meta,$sys_id]);
    echo json_encode(['success'=>true,'message'=>"FX rate for {$e['currency_code']} deleted."]);
} catch(Throwable $e){ echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
