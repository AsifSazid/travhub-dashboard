<?php
session_start();
require_once '../../../server/api_bootstrap.php';
require_once '../../../server/db_connection.php';

$search         = trim($_GET['search']         ?? '');
$country_sys_id = trim($_GET['country_sys_id'] ?? '');
$type           = trim($_GET['type']           ?? '');
$status         = trim($_GET['status']         ?? 'active');
$page           = max(1,(int)($_GET['page']  ?? 1));
$limit          = max(1,min(100,(int)($_GET['limit'] ?? 20)));
$offset         = ($page-1)*$limit;

try {
    $where=[]; $params=[];
    if ($status==='trash')   $where[]="status='deleted'";
    elseif ($status==='all') $where[]="status!='deleted'";
    else                     $where[]="status='active'";
    if ($search!==''){$where[]="name LIKE :s";$params[':s']='%'.$search.'%';}
    if ($country_sys_id!==''){$where[]="country_sys_id=:csid";$params[':csid']=$country_sys_id;}
    if ($type!==''){$where[]="type=:type";$params[':type']=$type;}
    $w=$where?'WHERE '.implode(' AND ',$where):'';

    $cnt=$pdo->prepare("SELECT COUNT(*) FROM cars $w"); $cnt->execute($params);
    $total=(int)$cnt->fetchColumn();

    $stmt=$pdo->prepare("SELECT id,uuid,sys_id,country_sys_id,country_name,
        name,type,seats,has_luggage,max_luggage,status
        FROM cars $w ORDER BY country_sys_id ASC,name ASC LIMIT :lim OFFSET :off");
    foreach ($params as $k=>$v) $stmt->bindValue($k,$v);
    $stmt->bindValue(':lim',$limit,PDO::PARAM_INT);
    $stmt->bindValue(':off',$offset,PDO::PARAM_INT);
    $stmt->execute();
    $rows=$stmt->fetchAll();
    foreach ($rows as &$r){ $r['id']=(int)$r['id']; $r['seats']=(int)$r['seats']; $r['has_luggage']=(bool)$r['has_luggage']; }
    echo json_encode(['success'=>true,'data'=>$rows,
        'pagination'=>['total'=>$total,'page'=>$page,'limit'=>$limit,'total_pages'=>(int)ceil($total/$limit)]],JSON_UNESCAPED_UNICODE);
} catch (Throwable $e){echo json_encode(['success'=>false,'message'=>$e->getMessage()]);}