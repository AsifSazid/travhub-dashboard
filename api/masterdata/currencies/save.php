<?php
session_start();
require_once '../../../server/api_bootstrap.php';
require_once '../../../server/db_connection.php';
require_once '../../../server/id_generator.php';
require_once '../../../server/generate_meta_data.php';
require_once '../../../server/json-sync-helper.php';

if ($_SERVER['REQUEST_METHOD']!=='POST'){echo json_encode(['success'=>false,'message'=>'POST only']);exit;}
$in=json_decode(file_get_contents('php://input'),true)?:[];
$sys_id        = trim($in['sys_id']        ?? '');
$currency_code = strtoupper(trim($in['currency_code'] ?? ''));
$name          = trim($in['name']          ?? '');
$symbol        = trim($in['symbol']        ?? '');
$decimal_places= max(0,min(4,(int)($in['decimal_places']??2)));
if (!$currency_code||!$name){echo json_encode(['success'=>false,'message'=>'currency_code and name required']);exit;}
try {
    $isNew=empty($sys_id);
    if ($isNew) {
        $ids=generateIDs($pdo,'currencies'); $sys_id=$ids['sys_id']; $uuid=$ids['uuid'];
        $meta=buildMetaData(null,$_SESSION['user_name']??'system');
        $pdo->prepare("INSERT INTO currencies (uuid,sys_id,currency_code,name,symbol,decimal_places,status,meta_data) VALUES(:uuid,:sid,:code,:name,:sym,:dp,'active',:meta)")
            ->execute([':uuid'=>$uuid,':sid'=>$sys_id,':code'=>$currency_code,':name'=>$name,':sym'=>$symbol?:null,':dp'=>$decimal_places,':meta'=>$meta]);
        syncCurrenciesJson($pdo);
        echo json_encode(['success'=>true,'action'=>'created','sys_id'=>$sys_id,'message'=>"Currency '{$currency_code}' created."],JSON_UNESCAPED_UNICODE);
    } else {
        $row=$pdo->prepare("SELECT meta_data FROM currencies WHERE sys_id=? LIMIT 1");
        $row->execute([$sys_id]); $existing=$row->fetch();
        if (!$existing){echo json_encode(['success'=>false,'message'=>'Not found']);exit;}
        $meta=buildMetaData($existing['meta_data'],$_SESSION['user_name']??'system');
        $pdo->prepare("UPDATE currencies SET currency_code=:code,name=:name,symbol=:sym,decimal_places=:dp,meta_data=:meta WHERE sys_id=:sid")
            ->execute([':code'=>$currency_code,':name'=>$name,':sym'=>$symbol?:null,':dp'=>$decimal_places,':meta'=>$meta,':sid'=>$sys_id]);
        syncCurrenciesJson($pdo);
        echo json_encode(['success'=>true,'action'=>'updated','sys_id'=>$sys_id],JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e){echo json_encode(['success'=>false,'message'=>$e->getMessage()]);}