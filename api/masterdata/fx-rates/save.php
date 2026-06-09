<?php
/**
 * Gen-3: fx_rates is X→BDT only
 * Fields: currency_sys_id, currency_code, rate (1 X = ? BDT), buffer_pct, effective_date, source
 */
session_start();
require_once '../../../server/api_bootstrap.php';
require_once '../../../server/db_connection.php';
require_once '../../../server/id_generator.php';
require_once '../../../server/generate_meta_data.php';
if ($_SERVER['REQUEST_METHOD']!=='POST'){echo json_encode(['success'=>false,'message'=>'POST only']);exit;}
$in              = json_decode(file_get_contents('php://input'),true)?:[];
$sys_id          = trim($in['sys_id']          ?? '');
$currency_sys_id = trim($in['currency_sys_id'] ?? '');
$currency_code   = strtoupper(trim($in['currency_code'] ?? ''));
$rate            = (float)($in['rate']          ?? 0);
$buffer_pct      = max(0,(float)($in['buffer_pct'] ?? 0));
$source          = in_array($in['source']??'',['manual','api'])?$in['source']:'manual';
$effective_date  = trim($in['effective_date']   ?? date('Y-m-d'));
if (!$currency_code || $rate <= 0) { echo json_encode(['success'=>false,'message'=>'currency_code and rate > 0 required']); exit; }
if (!$currency_sys_id) {
    $cs=$pdo->prepare("SELECT sys_id FROM currencies WHERE currency_code=? AND status='active' LIMIT 1");
    $cs->execute([$currency_code]);
    $currency_sys_id=$cs->fetchColumn()?:'';
}
try {
    $isNew=empty($sys_id);
    if ($isNew) {
        $ids=generateIDs($pdo,'fx_rates'); $sys_id=$ids['sys_id']; $uuid=$ids['uuid'];
        $meta=buildMetaData(null,$_SESSION['user_name']??'system');
        $pdo->prepare("INSERT INTO fx_rates (uuid,sys_id,currency_sys_id,currency_code,rate,buffer_pct,effective_date,source,status,meta_data) VALUES(?,?,?,?,?,?,?,?,'active',?)")
            ->execute([$uuid,$sys_id,$currency_sys_id,$currency_code,$rate,$buffer_pct,$effective_date,$source,$meta]);
        echo json_encode(['success'=>true,'action'=>'created','sys_id'=>$sys_id,'message'=>"Rate {$currency_code}→BDT created."],JSON_UNESCAPED_UNICODE);
    } else {
        $r=$pdo->prepare("SELECT meta_data FROM fx_rates WHERE sys_id=? LIMIT 1"); $r->execute([$sys_id]); $e=$r->fetch();
        if (!$e){echo json_encode(['success'=>false,'message'=>'Not found']);exit;}
        $meta=buildMetaData($e['meta_data'],$_SESSION['user_name']??'system');
        $pdo->prepare("UPDATE fx_rates SET rate=?,buffer_pct=?,effective_date=?,source=?,meta_data=? WHERE sys_id=?")->execute([$rate,$buffer_pct,$effective_date,$source,$meta,$sys_id]);
        echo json_encode(['success'=>true,'action'=>'updated','sys_id'=>$sys_id],JSON_UNESCAPED_UNICODE);
    }
} catch(Throwable $e){ echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
