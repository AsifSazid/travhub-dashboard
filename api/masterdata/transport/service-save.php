<?php
// POST { sys_id?, country_sys_id, country_name?,
// name, search_terms?, type, from_city_sys_id?, from_city_name?,
// to_city_sys_id?, to_city_name?, direction, description?,
// distance_km?, duration_typical? }
session_start();
require_once '../../../server/api_bootstrap.php';
require_once '../../../server/db_connection.php';
require_once '../../../server/id_generator.php';
require_once '../../../server/generate_meta_data.php';
require_once '../../../server/json-sync-helper.php';

if ($_SERVER['REQUEST_METHOD']!=='POST'){echo json_encode(['success'=>false,'message'=>'POST only']);exit;}
$in=json_decode(file_get_contents('php://input'),true)?:[];
$sys_id         =trim($in['sys_id']         ??'');
$country_sys_id =trim($in['country_sys_id'] ??'');
$country_name   =trim($in['country_name']   ??'');
$name           =trim($in['name']           ??'');
$search_terms   =trim($in['search_terms']   ??'');
$type           =trim($in['type']           ??'airport_transfer');
$from_city_sys_id=trim($in['from_city_sys_id']??'');
$from_city_name =trim($in['from_city_name'] ??'');
$to_city_sys_id =trim($in['to_city_sys_id'] ??'');
$to_city_name   =trim($in['to_city_name']   ??'');
$direction      =in_array($in['direction']??'',['one_way','return'])?$in['direction']:'one_way';
$description    =trim($in['description']    ??'');
$distance_km    =isset($in['distance_km'])?(float)$in['distance_km']:null;
$duration_typical=trim($in['duration_typical']??'');

$validTypes=['airport_transfer','intercity','ferry','shuttle','car_hire','other'];
if (!in_array($type,$validTypes)) $type='airport_transfer';
if (!$name){echo json_encode(['success'=>false,'message'=>'name required']);exit;}

try {
    $isNew=empty($sys_id);
    if ($isNew) {
        if (!$country_sys_id){echo json_encode(['success'=>false,'message'=>'country_sys_id required']);exit;}
        $ids=generateChildIDs($pdo,'transport_services',$country_sys_id);
        $sys_id=$ids['sys_id']; $uuid=$ids['uuid'];
        $meta=buildMetaData(null,$_SESSION['user_name']??'system');
        $pdo->prepare("INSERT INTO transport_services (uuid,sys_id,country_sys_id,country_name,name,search_terms,type,from_city_sys_id,from_city_name,to_city_sys_id,to_city_name,direction,description,distance_km,duration_typical,status,meta_data)
            VALUES(:uuid,:sid,:csid,:cname,:name,:st,:type,:fcsid,:fcname,:tcsid,:tcname,:dir,:desc,:dkm,:dt,'active',:meta)")
        ->execute([':uuid'=>$uuid,':sid'=>$sys_id,':csid'=>$country_sys_id,':cname'=>$country_name?:null,
            ':name'=>$name,':st'=>$search_terms?:null,':type'=>$type,
            ':fcsid'=>$from_city_sys_id?:null,':fcname'=>$from_city_name?:null,
            ':tcsid'=>$to_city_sys_id?:null,':tcname'=>$to_city_name?:null,
            ':dir'=>$direction,':desc'=>$description?:null,':dkm'=>$distance_km,
            ':dt'=>$duration_typical?:null,':meta'=>$meta]);
        syncTransportJson($pdo);
        echo json_encode(['success'=>true,'action'=>'created','sys_id'=>$sys_id,'message'=>"Transport '{$name}' created."],JSON_UNESCAPED_UNICODE);
    } else {
        $row=$pdo->prepare("SELECT meta_data FROM transport_services WHERE sys_id=? LIMIT 1");
        $row->execute([$sys_id]); $existing=$row->fetch();
        if (!$existing){echo json_encode(['success'=>false,'message'=>'Not found']);exit;}
        $meta=buildMetaData($existing['meta_data'],$_SESSION['user_name']??'system');
        $pdo->prepare("UPDATE transport_services SET country_name=:cname,name=:name,search_terms=:st,type=:type,from_city_sys_id=:fcsid,from_city_name=:fcname,to_city_sys_id=:tcsid,to_city_name=:tcname,direction=:dir,description=:desc,distance_km=:dkm,duration_typical=:dt,meta_data=:meta WHERE sys_id=:sid")
        ->execute([':cname'=>$country_name?:null,
            ':name'=>$name,':st'=>$search_terms?:null,':type'=>$type,
            ':fcsid'=>$from_city_sys_id?:null,':fcname'=>$from_city_name?:null,
            ':tcsid'=>$to_city_sys_id?:null,':tcname'=>$to_city_name?:null,
            ':dir'=>$direction,':desc'=>$description?:null,':dkm'=>$distance_km,
            ':dt'=>$duration_typical?:null,':meta'=>$meta,':sid'=>$sys_id]);
        syncTransportJson($pdo);
        echo json_encode(['success'=>true,'action'=>'updated','sys_id'=>$sys_id],JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e){echo json_encode(['success'=>false,'message'=>$e->getMessage()]);}