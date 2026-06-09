<?php
// POST { sys_id?, country_sys_id, country_name?, 
// name, type, seats, has_luggage, max_luggage? }
session_start();
require_once '../../../server/api_bootstrap.php';
require_once '../../../server/db_connection.php';
require_once '../../../server/id_generator.php';
require_once '../../../server/generate_meta_data.php';
require_once '../../../server/json-sync-helper.php';

if ($_SERVER['REQUEST_METHOD']!=='POST'){echo json_encode(['success'=>false,'message'=>'POST only']);exit;}
$in=json_decode(file_get_contents('php://input'),true)?:[];
$sys_id         = trim($in['sys_id']         ?? '');
$country_sys_id = trim($in['country_sys_id'] ?? '');
$country_name   = trim($in['country_name']   ?? '');
$name           = trim($in['name']           ?? '');
$type           = trim($in['type']           ?? 'sedan');
$seats          = max(1,(int)($in['seats']   ?? 4));
$has_luggage    = !empty($in['has_luggage']) ? 1 : 0;
$max_luggage    = trim($in['max_luggage']    ?? '');

$validTypes=['sedan','van','suv','minibus','microbus','coaster','bus','other'];
if (!in_array($type,$validTypes)) $type='sedan';
if (!$name){echo json_encode(['success'=>false,'message'=>'name required']);exit;}

try {
    $isNew=empty($sys_id);
    if ($isNew) {
        if (!$country_sys_id){echo json_encode(['success'=>false,'message'=>'country_sys_id required for create']);exit;}
        $ids=generateChildIDs($pdo,'cars',$country_sys_id);
        $sys_id=$ids['sys_id']; $uuid=$ids['uuid'];
        $meta=buildMetaData(null,$_SESSION['user_name']??'system');
        $pdo->prepare("INSERT INTO cars (uuid,sys_id,country_sys_id,country_name,name,type,seats,has_luggage,max_luggage,status,meta_data)
            VALUES(:uuid,:sid,:csid,:cname,:name,:type,:seats,:hl,:ml,'active',:meta)")
        ->execute([':uuid'=>$uuid,':sid'=>$sys_id,':csid'=>$country_sys_id,':cname'=>$country_name?:null,
            ':name'=>$name,':type'=>$type,
            ':seats'=>$seats,':hl'=>$has_luggage,':ml'=>$max_luggage?:null,':meta'=>$meta]);
        syncCarsJson($pdo);
        echo json_encode(['success'=>true,'action'=>'created','sys_id'=>$sys_id,'message'=>"Car '{$name}' created."],JSON_UNESCAPED_UNICODE);
    } else {
        $row=$pdo->prepare("SELECT meta_data FROM cars WHERE sys_id=? LIMIT 1");
        $row->execute([$sys_id]); $existing=$row->fetch();
        if (!$existing){echo json_encode(['success'=>false,'message'=>'Not found']);exit;}
        $meta=buildMetaData($existing['meta_data'],$_SESSION['user_name']??'system');
        $pdo->prepare("UPDATE cars SET country_name=:cname,name=:name,type=:type,seats=:seats,has_luggage=:hl,max_luggage=:ml,meta_data=:meta WHERE sys_id=:sid")
        ->execute([':cname'=>$country_name?:null,':name'=>$name,
            ':type'=>$type,':seats'=>$seats,':hl'=>$has_luggage,':ml'=>$max_luggage?:null,':meta'=>$meta,':sid'=>$sys_id]);
        syncCarsJson($pdo);
        echo json_encode(['success'=>true,'action'=>'updated','sys_id'=>$sys_id,'message'=>"Car '{$name}' updated."],JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e){echo json_encode(['success'=>false,'message'=>$e->getMessage()]);}