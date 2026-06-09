<?php
// POST { sys_id?, component_sys_id, supplier_sys_id?,
//         variant_name, unit_basis, currency_code,
//         net_cost, markup_type, markup_value, sell_price, attributes? }
session_start();
require_once '../../../server/api_bootstrap.php';
require_once '../../../server/db_connection.php';
require_once '../../../server/id_generator.php';
require_once '../../../server/generate_meta_data.php';

if ($_SERVER['REQUEST_METHOD']!=='POST'){echo json_encode(['success'=>false,'message'=>'POST only']);exit;}
$in=json_decode(file_get_contents('php://input'),true)?:[];
$sys_id           =trim($in['sys_id']           ??'');
$component_sys_id =trim($in['component_sys_id'] ??'');
$supplier_sys_id  =trim($in['supplier_sys_id']  ??'');
$variant_name     =trim($in['variant_name']      ??'');
$unit_basis       =trim($in['unit_basis']         ??'per_pax');
$currency_code    =strtoupper(trim($in['currency_code']??'BDT'));
$net_cost         =(float)($in['net_cost']        ??0);
$markup_type      =in_array($in['markup_type']??'',['percent','fixed'])?$in['markup_type']:'percent';
$markup_value     =max(0,(float)($in['markup_value']??0));
$sell_price       =(float)($in['sell_price']      ??0);
$attributes       =is_array($in['attributes']??null)?$in['attributes']:[];

$validBases=['per_pax','per_group','per_day','flat'];
if (!in_array($unit_basis,$validBases)) $unit_basis='per_pax';
if (!$component_sys_id||!$variant_name||$net_cost<=0){
    echo json_encode(['success'=>false,'message'=>'component_sys_id, variant_name, net_cost required']); exit;
}
try {
    $isNew=empty($sys_id);
    if ($isNew) {
        $ids=generateChildIDs($pdo,'component_variants',$component_sys_id);
        $sys_id=$ids['sys_id']; $uuid=$ids['uuid'];
        $meta=buildMetaData(null,$_SESSION['user_name']??'system');
        $pdo->prepare("INSERT INTO component_variants (uuid,sys_id,component_sys_id,supplier_sys_id,variant_name,unit_basis,currency_code,net_cost,markup_type,markup_value,sell_price,attributes,status,meta_data)
            VALUES(:uuid,:sid,:csid,:ssid,:vname,:ub,:cc,:nc,:mt,:mv,:sp,:attr,'active',:meta)")
        ->execute([':uuid'=>$uuid,':sid'=>$sys_id,':csid'=>$component_sys_id,
            ':ssid'=>$supplier_sys_id?:null,':vname'=>$variant_name,':ub'=>$unit_basis,
            ':cc'=>$currency_code,':nc'=>$net_cost,':mt'=>$markup_type,
            ':mv'=>$markup_value,':sp'=>$sell_price,
            ':attr'=>json_encode($attributes,JSON_UNESCAPED_UNICODE),':meta'=>$meta]);
        echo json_encode(['success'=>true,'action'=>'created','sys_id'=>$sys_id],JSON_UNESCAPED_UNICODE);
    } else {
        $row=$pdo->prepare("SELECT meta_data FROM component_variants WHERE sys_id=? LIMIT 1");
        $row->execute([$sys_id]); $existing=$row->fetch();
        if (!$existing){echo json_encode(['success'=>false,'message'=>'Not found']);exit;}
        $meta=buildMetaData($existing['meta_data'],$_SESSION['user_name']??'system');
        $pdo->prepare("UPDATE component_variants SET supplier_sys_id=:ssid,variant_name=:vname,unit_basis=:ub,currency_code=:cc,net_cost=:nc,markup_type=:mt,markup_value=:mv,sell_price=:sp,attributes=:attr,meta_data=:meta WHERE sys_id=:sid")
        ->execute([':ssid'=>$supplier_sys_id?:null,':vname'=>$variant_name,':ub'=>$unit_basis,
            ':cc'=>$currency_code,':nc'=>$net_cost,':mt'=>$markup_type,
            ':mv'=>$markup_value,':sp'=>$sell_price,
            ':attr'=>json_encode($attributes,JSON_UNESCAPED_UNICODE),':meta'=>$meta,':sid'=>$sys_id]);
        echo json_encode(['success'=>true,'action'=>'updated','sys_id'=>$sys_id],JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e){echo json_encode(['success'=>false,'message'=>$e->getMessage()]);}