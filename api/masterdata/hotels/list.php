<?php
session_start();
require_once '../../../server/api_bootstrap.php';
require_once '../../../server/db_connection.php';
$search=trim($_GET['search']??''); $country_sys_id=trim($_GET['country_sys_id']??'');
$city_sys_id=trim($_GET['city_sys_id']??''); $status=trim($_GET['status']??'active');
$page=max(1,(int)($_GET['page']??1)); $limit=max(1,min(100,(int)($_GET['limit']??20))); $offset=($page-1)*$limit;
try {
    $where=[]; $params=[];
    if ($status==='trash')   $where[]="status='deleted'";
    elseif ($status==='all') $where[]="status!='deleted'";
    else                     $where[]="status='active'";
    if ($search!==''){$where[]="(name LIKE :s OR search_terms LIKE :s2)";$params[':s']=$params[':s2']='%'.$search.'%';}
    if ($country_sys_id!==''){$where[]="country_sys_id=:csid";$params[':csid']=$country_sys_id;}
    if ($city_sys_id!==''){$where[]="city_sys_id=:cid";$params[':cid']=$city_sys_id;}
    $w=$where?'WHERE '.implode(' AND ',$where):'';
    $cnt=$pdo->prepare("SELECT COUNT(*) FROM hotels $w"); $cnt->execute($params); $total=(int)$cnt->fetchColumn();
    $stmt=$pdo->prepare("SELECT id,uuid,sys_id,country_sys_id,country_name,city_sys_id,city_name,
        name,star_rating,address,phone,email,check_in_time,check_out_time,
        for_umrah,usage_count,status,images FROM hotels $w ORDER BY country_sys_id ASC,name ASC LIMIT :lim OFFSET :off");
    foreach ($params as $k=>$v) $stmt->bindValue($k,$v);
    $stmt->bindValue(':lim',$limit,PDO::PARAM_INT); $stmt->bindValue(':off',$offset,PDO::PARAM_INT);
    $stmt->execute(); $rows=$stmt->fetchAll();
    $data=[];
    foreach ($rows as $r) {
        $images=json_decode($r['images']??'[]',true)?:[];
        $data[]=['id'=>(int)$r['id'],'uuid'=>$r['uuid'],'sys_id'=>$r['sys_id'],
            'country_sys_id'=>$r['country_sys_id'],'country_name'=>$r['country_name'],
            'city_sys_id'=>$r['city_sys_id'],'city_name'=>$r['city_name'],
            'name'=>$r['name'],
            'star_rating'=>$r['star_rating']?(int)$r['star_rating']:null,
            'address'=>$r['address'],'phone'=>$r['phone'],'email'=>$r['email'],
            'check_in_time'=>$r['check_in_time'],'check_out_time'=>$r['check_out_time'],
            'for_umrah'=>(int)$r['for_umrah'],
            'usage_count'=>(int)$r['usage_count'],'thumb'=>$images[0]['url']??null,
            'status'=>$r['status']];
    }
    echo json_encode(['success'=>true,'data'=>$data,'pagination'=>['total'=>$total,'page'=>$page,'limit'=>$limit,'total_pages'=>(int)ceil($total/$limit)]],JSON_UNESCAPED_UNICODE);
} catch (Throwable $e){echo json_encode(['success'=>false,'message'=>$e->getMessage()]);}