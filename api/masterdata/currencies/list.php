<?php
session_start();
require_once '../../../server/api_bootstrap.php';
require_once '../../../server/db_connection.php';
$status=trim($_GET['status']??'active');
try {
    $w = $status==='all' ? "WHERE status!='deleted'" : "WHERE status='".($status==='trash'?'deleted':'active')."'";
    $rows=$pdo->query("SELECT id,uuid,sys_id,currency_code,name,symbol,decimal_places,status FROM currencies $w ORDER BY currency_code ASC")->fetchAll();
    $data=array_map(fn($r)=>array_merge($r,['id'=>(int)$r['id'],'decimal_places'=>(int)$r['decimal_places']]),$rows);
    echo json_encode(['success'=>true,'data'=>$data],JSON_UNESCAPED_UNICODE);
} catch (Throwable $e){echo json_encode(['success'=>false,'message'=>$e->getMessage()]);}