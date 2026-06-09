<?php
/** GET ?currency_code=THB — X→BDT rates only (Gen-3) */
session_start();
require_once '../../../server/api_bootstrap.php';
require_once '../../../server/db_connection.php';
$code=strtoupper(trim($_GET['currency_code']??''));
$date=trim($_GET['date']??'');
try {
    $where=["status='active'"]; $params=[];
    if ($code){$where[]='currency_code=:cc';$params[':cc']=$code;}
    if ($date){$where[]='effective_date=:dt';$params[':dt']=$date;}
    $w='WHERE '.implode(' AND ',$where);
    $stmt=$pdo->prepare("SELECT sys_id,currency_sys_id,currency_code,rate,buffer_pct,effective_date,source FROM fx_rates $w ORDER BY effective_date DESC");
    $stmt->execute($params);
    $rows=$stmt->fetchAll();
    foreach ($rows as &$r){ $r['rate']=(float)$r['rate']; $r['buffer_pct']=(float)$r['buffer_pct']; $r['rate_with_buffer']=round($r['rate']*(1+$r['buffer_pct']/100),8); }
    echo json_encode(['success'=>true,'data'=>$rows,'note'=>'All rates X→BDT'],JSON_UNESCAPED_UNICODE);
} catch(Throwable $e){ echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
