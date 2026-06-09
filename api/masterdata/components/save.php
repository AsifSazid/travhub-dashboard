<?php
// POST { sys_id?, name, search_terms?, category, description? }
session_start();
require_once '../../../server/api_bootstrap.php';
require_once '../../../server/db_connection.php';
require_once '../../../server/id_generator.php';
require_once '../../../server/generate_meta_data.php';
require_once '../../../server/json-sync-helper.php';

if ($_SERVER['REQUEST_METHOD']!=='POST'){echo json_encode(['success'=>false,'message'=>'POST only']);exit;}
$in=json_decode(file_get_contents('php://input'),true)?:[];
$sys_id         =trim($in['sys_id']         ??'');
$name           =trim($in['name']           ??'');
$search_terms   =trim($in['search_terms']   ??'');
$category       =trim($in['category']       ??'misc');
$description    =trim($in['description']    ??'');

$validCats=['insurance','visa','guide','sim','tip','porterage','meal','ticket','fee','misc'];
if (!in_array($category,$validCats)) $category='misc';
if (!$name){echo json_encode(['success'=>false,'message'=>'name required']);exit;}

try {
    $isNew=empty($sys_id);
    if ($isNew) {
        $ids=generateIDs($pdo,'components'); $sys_id=$ids['sys_id']; $uuid=$ids['uuid'];
        $meta=buildMetaData(null,$_SESSION['user_name']??'system');
        $pdo->prepare("INSERT INTO components (uuid,sys_id,name,search_terms,category,description,status,meta_data)
            VALUES(:uuid,:sid,:name,:st,:cat,:desc,'active',:meta)")
        ->execute([':uuid'=>$uuid,':sid'=>$sys_id,
            ':name'=>$name,':st'=>$search_terms?:null,':cat'=>$category,
            ':desc'=>$description?:null,':meta'=>$meta]);
        syncComponentsJson($pdo);
        echo json_encode(['success'=>true,'action'=>'created','sys_id'=>$sys_id,'message'=>"Component '{$name}' created."],JSON_UNESCAPED_UNICODE);
    } else {
        $row=$pdo->prepare("SELECT meta_data FROM components WHERE sys_id=? LIMIT 1");
        $row->execute([$sys_id]); $existing=$row->fetch();
        if (!$existing){echo json_encode(['success'=>false,'message'=>'Not found']);exit;}
        $meta=buildMetaData($existing['meta_data'],$_SESSION['user_name']??'system');
        $pdo->prepare("UPDATE components SET name=:name,search_terms=:st,category=:cat,description=:desc,meta_data=:meta WHERE sys_id=:sid")
        ->execute([':name'=>$name,':st'=>$search_terms?:null,
            ':cat'=>$category,':desc'=>$description?:null,':meta'=>$meta,':sid'=>$sys_id]);
        syncComponentsJson($pdo);
        echo json_encode(['success'=>true,'action'=>'updated','sys_id'=>$sys_id],JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e){echo json_encode(['success'=>false,'message'=>$e->getMessage()]);}