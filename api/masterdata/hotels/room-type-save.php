<?php
// POST { sys_id?, hotel_sys_id, hotel_name?,
//         room_name, description?, max_adults, max_children,
//         standard_occupancy, bed_config?, size_sqm? }
session_start();
require_once '../../../server/api_bootstrap.php';
require_once '../../../server/db_connection.php';
require_once '../../../server/id_generator.php';
require_once '../../../server/generate_meta_data.php';

if ($_SERVER['REQUEST_METHOD']!=='POST'){echo json_encode(['success'=>false,'message'=>'POST only']);exit;}
$in=json_decode(file_get_contents('php://input'),true)?:[];
$sys_id            =trim($in['sys_id']            ??'');
$hotel_sys_id      =trim($in['hotel_sys_id']      ??'');
$hotel_name        =trim($in['hotel_name']        ??'');
$room_name         =trim($in['room_name']         ??'');
$description       =trim($in['description']       ??'');
$max_adults        =max(1,(int)($in['max_adults']        ??2));
$max_children      =max(0,(int)($in['max_children']      ??0));
$standard_occupancy=max(1,(int)($in['standard_occupancy']??2));
$bed_config        =trim($in['bed_config']        ??'');
$size_sqm          =isset($in['size_sqm'])?(int)$in['size_sqm']:null;

if (!$room_name){echo json_encode(['success'=>false,'message'=>'room_name required']);exit;}
try {
    $isNew=empty($sys_id);
    if ($isNew) {
        if (!$hotel_sys_id){echo json_encode(['success'=>false,'message'=>'hotel_sys_id required']);exit;}
        $ids=generateChildIDs($pdo,'room_types',$hotel_sys_id); $sys_id=$ids['sys_id']; $uuid=$ids['uuid'];
        $meta=buildMetaData(null,$_SESSION['user_name']??'system');
        $pdo->prepare("INSERT INTO room_types (uuid,sys_id,hotel_sys_id,hotel_name,room_name,description,max_adults,max_children,standard_occupancy,bed_config,size_sqm,status,meta_data)
            VALUES(:uuid,:sid,:hsid,:hname,:rname,:desc,:ma,:mc,:so,:bc,:sqm,'active',:meta)")
        ->execute([':uuid'=>$uuid,':sid'=>$sys_id,':hsid'=>$hotel_sys_id,':hname'=>$hotel_name?:null,
            ':rname'=>$room_name,':desc'=>$description?:null,':ma'=>$max_adults,':mc'=>$max_children,
            ':so'=>$standard_occupancy,':bc'=>$bed_config?:null,':sqm'=>$size_sqm,':meta'=>$meta]);
        echo json_encode(['success'=>true,'action'=>'created','sys_id'=>$sys_id,'message'=>"Room type '{$room_name}' created."],JSON_UNESCAPED_UNICODE);
    } else {
        $row=$pdo->prepare("SELECT meta_data FROM room_types WHERE sys_id=? LIMIT 1");
        $row->execute([$sys_id]); $existing=$row->fetch();
        if (!$existing){echo json_encode(['success'=>false,'message'=>'Not found']);exit;}
        $meta=buildMetaData($existing['meta_data'],$_SESSION['user_name']??'system');
        $pdo->prepare("UPDATE room_types SET hotel_name=:hname,room_name=:rname,description=:desc,max_adults=:ma,max_children=:mc,standard_occupancy=:so,bed_config=:bc,size_sqm=:sqm,meta_data=:meta WHERE sys_id=:sid")
        ->execute([':hname'=>$hotel_name?:null,':rname'=>$room_name,':desc'=>$description?:null,
            ':ma'=>$max_adults,':mc'=>$max_children,':so'=>$standard_occupancy,
            ':bc'=>$bed_config?:null,':sqm'=>$size_sqm,':meta'=>$meta,':sid'=>$sys_id]);
        echo json_encode(['success'=>true,'action'=>'updated','sys_id'=>$sys_id],JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e){echo json_encode(['success'=>false,'message'=>$e->getMessage()]);}