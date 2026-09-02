<?php
// api/masterdata/hotels/save.php
session_start();
require_once '../../../server/api_bootstrap.php';
require_once '../../../server/db_connection.php';
require_once '../../../server/id_generator.php';
require_once '../../../server/generate_meta_data.php';
require_once '../../../server/json-sync-helper.php';
if ($_SERVER['REQUEST_METHOD']!=='POST'){echo json_encode(['success'=>false,'message'=>'POST only']);exit;}
$in             = json_decode(file_get_contents('php://input'),true)?:[];
$sys_id         = trim($in['sys_id']         ?? '');
$country_sys_id = trim($in['country_sys_id'] ?? '');
$country_name   = trim($in['country_name']   ?? '');
$city_sys_id    = trim($in['city_sys_id']    ?? '');
$city_name      = trim($in['city_name']      ?? '');
$name           = trim($in['name']           ?? '');
$search_terms   = trim($in['search_terms']   ?? '');
$star_rating    = isset($in['star_rating'])?max(1,min(5,(int)$in['star_rating'])):null;
$address        = trim($in['address']        ?? '');
$phone          = trim($in['phone']          ?? '');
$email          = trim($in['email']          ?? '');
$description    = trim($in['description']    ?? '');
$check_in_time  = trim($in['check_in_time']  ?? '');
$check_out_time = trim($in['check_out_time'] ?? '');
$amenities      = is_array($in['amenities']??null)?$in['amenities']:[];
$images         = is_array($in['images']??null)?$in['images']:[];
$for_umrah      = isset($in['for_umrah']) ? (int)(bool)$in['for_umrah'] : 0;
if (!$name && empty($in['_patch'])){echo json_encode(['success'=>false,'message'=>'name required']);exit;}

// ── Umrah-only patch ──────────────────────────────────
if (($in['_patch'] ?? '') === 'umrah') {
    if (!$sys_id) { echo json_encode(['success'=>false,'message'=>'sys_id required']); exit; }
    $r = $pdo->prepare("SELECT meta_data FROM hotels WHERE sys_id=? LIMIT 1");
    $r->execute([$sys_id]); $e = $r->fetch();
    if (!$e) { echo json_encode(['success'=>false,'message'=>'Not found']); exit; }
    $meta = buildMetaData($e['meta_data'], $_SESSION['user_name']??'system');
    $pdo->prepare("UPDATE hotels SET for_umrah=?, meta_data=? WHERE sys_id=?")
        ->execute([$for_umrah, $meta, $sys_id]);
    echo json_encode(['success'=>true,'message'=>'Updated']);
    exit;
}
try {
    $isNew=empty($sys_id);
    if ($isNew) {
        if (!$country_sys_id){echo json_encode(['success'=>false,'message'=>'country_sys_id required']);exit;}
        $ids=generateIDs($pdo,'hotels'); $sys_id=$ids['sys_id']; $uuid=$ids['uuid'];
        $meta=buildMetaData(null,$_SESSION['user_name']??'system');
$pdo->prepare("INSERT INTO hotels (uuid,sys_id,country_sys_id,country_name,city_sys_id,city_name,name,search_terms,star_rating,address,phone,email,description,amenities,images,check_in_time,check_out_time,for_umrah,status,meta_data) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'active',?)")
            ->execute([$uuid,$sys_id,$country_sys_id,$country_name?:null,$city_sys_id?:null,$city_name?:null,$name,$search_terms?:null,$star_rating,$address?:null,$phone?:null,$email?:null,$description?:null,json_encode($amenities,JSON_UNESCAPED_UNICODE),json_encode($images,JSON_UNESCAPED_UNICODE),$check_in_time?:null,$check_out_time?:null,$for_umrah,$meta]);
        syncHotelsJson($pdo);
        echo json_encode(['success'=>true,'action'=>'created','sys_id'=>$sys_id,'message'=>"Hotel '{$name}' created."],JSON_UNESCAPED_UNICODE);
    } else {
        $r=$pdo->prepare("SELECT meta_data FROM hotels WHERE sys_id=? LIMIT 1"); $r->execute([$sys_id]); $e=$r->fetch();
        if (!$e){echo json_encode(['success'=>false,'message'=>'Not found']);exit;}
        $meta=buildMetaData($e['meta_data'],$_SESSION['user_name']??'system');
        $pdo->prepare("UPDATE hotels SET country_name=?,city_sys_id=?,city_name=?,name=?,search_terms=?,star_rating=?,address=?,phone=?,email=?,description=?,amenities=?,images=?,check_in_time=?,check_out_time=?,for_umrah=?,meta_data=? WHERE sys_id=?")
            ->execute([$country_name?:null,$city_sys_id?:null,$city_name?:null,$name,$search_terms?:null,$star_rating,$address?:null,$phone?:null,$email?:null,$description?:null,json_encode($amenities,JSON_UNESCAPED_UNICODE),json_encode($images,JSON_UNESCAPED_UNICODE),$check_in_time?:null,$check_out_time?:null,$for_umrah,$meta,$sys_id]);
        syncHotelsJson($pdo);
        echo json_encode(['success'=>true,'action'=>'updated','sys_id'=>$sys_id,'message'=>"Hotel '{$name}' updated."],JSON_UNESCAPED_UNICODE);
    }
} catch(Throwable $e){ echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }