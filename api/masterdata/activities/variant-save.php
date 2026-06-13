<?php
// api/masterdata/activities/variant-save.php (Gen-3)
// POST { sys_id?, activity_sys_id, country_sys_id,
//         variant_name, transport_mode, meal_breakfast, meal_lunch, meal_dinner,
//         ticket_included, guide_included, guide_language?,
//         inclusions?, exclusions?, capacity_min?, capacity_max?,
//         price_basis, currency_code, net_cost, markup_type,
//         markup_value, sell_price, child_price? }
session_start();
require_once '../../../server/api_bootstrap.php';
require_once '../../../server/db_connection.php';
require_once '../../../server/id_generator.php';
require_once '../../../server/generate_meta_data.php';

if ($_SERVER['REQUEST_METHOD']!=='POST'){echo json_encode(['success'=>false,'message'=>'POST only']);exit;}
$in=json_decode(file_get_contents('php://input'),true)?:[];
$sys_id         =trim($in['sys_id']          ??'');
$activity_sys_id=trim($in['activity_sys_id'] ??'');
$country_sys_id =trim($in['country_sys_id']  ??'');
$variant_name   =trim($in['variant_name']    ??'');
$transport_mode =trim($in['transport_mode']  ??'none');
$meal_breakfast =(int)(!empty($in['meal_breakfast']));
$meal_lunch     =(int)(!empty($in['meal_lunch']));
$meal_dinner    =(int)(!empty($in['meal_dinner']));
$ticket_included=(int)(!empty($in['ticket_included']));
$guide_included =(int)(!empty($in['guide_included']));
$guide_language =trim($in['guide_language']  ??'');
$inclusions     =trim($in['inclusions']      ??'');
$exclusions     =trim($in['exclusions']      ??'');
$capacity_min   =isset($in['capacity_min'])?(int)$in['capacity_min']:null;
$capacity_max   =isset($in['capacity_max'])?(int)$in['capacity_max']:null;
$price_basis    =in_array($in['price_basis']??'',['per_pax','per_group'])?$in['price_basis']:'per_pax';
$currency_code  =strtoupper(trim($in['currency_code']??'BDT'));
$net_cost       =(float)($in['net_cost']     ??0);
$markup_type    =in_array($in['markup_type']??'',['percent','fixed'])?$in['markup_type']:'percent';
$markup_value   =max(0,(float)($in['markup_value']??0));
$sell_price     =(float)($in['sell_price']   ??0);
$child_price    =isset($in['child_price'])?(float)$in['child_price']:null;

$validModes=['none','sic','sedan','suv','van','minibus','coach','boat'];
if (!in_array($transport_mode,$validModes)) $transport_mode='none';
if (!$activity_sys_id||!$variant_name||$net_cost<=0){
    echo json_encode(['success'=>false,'message'=>'activity_sys_id, variant_name, net_cost required']); exit;
}
try {
    $isNew=empty($sys_id);
    if ($isNew) {
        $ids=generateChildIDs($pdo,'activity_variants',$activity_sys_id);
        $sys_id=$ids['sys_id']; $uuid=$ids['uuid'];
        $meta=buildMetaData(null,$_SESSION['user_name']??'system');
        $pdo->prepare("INSERT INTO activity_variants (uuid,sys_id,activity_sys_id,country_sys_id,variant_name,transport_mode,meal_breakfast,meal_lunch,meal_dinner,ticket_included,guide_included,guide_language,inclusions,exclusions,capacity_min,capacity_max,price_basis,currency_code,net_cost,markup_type,markup_value,sell_price,child_price,status,meta_data)
            VALUES(:uuid,:sid,:asid,:csid,:vname,:tm,:mb,:ml,:md,:ti,:gi,:gl,:inc,:exc,:cmin,:cmax,:pb,:cc,:nc,:mt,:mv,:sp,:cp,'active',:meta)")
        ->execute([':uuid'=>$uuid,':sid'=>$sys_id,':asid'=>$activity_sys_id,':csid'=>$country_sys_id,
            ':vname'=>$variant_name,':tm'=>$transport_mode,
            ':mb'=>$meal_breakfast,':ml'=>$meal_lunch,':md'=>$meal_dinner,
            ':ti'=>$ticket_included,':gi'=>$guide_included,':gl'=>$guide_language?:null,
            ':inc'=>$inclusions?:null,':exc'=>$exclusions?:null,
            ':cmin'=>$capacity_min,':cmax'=>$capacity_max,':pb'=>$price_basis,
            ':cc'=>$currency_code,':nc'=>$net_cost,':mt'=>$markup_type,
            ':mv'=>$markup_value,':sp'=>$sell_price,':cp'=>$child_price,':meta'=>$meta]);
        echo json_encode(['success'=>true,'action'=>'created','sys_id'=>$sys_id],JSON_UNESCAPED_UNICODE);
    } else {
        $row=$pdo->prepare("SELECT meta_data FROM activity_variants WHERE sys_id=? LIMIT 1");
        $row->execute([$sys_id]); $existing=$row->fetch();
        if (!$existing){echo json_encode(['success'=>false,'message'=>'Not found']);exit;}
        $meta=buildMetaData($existing['meta_data'],$_SESSION['user_name']??'system');
        $pdo->prepare("UPDATE activity_variants SET variant_name=:vname,transport_mode=:tm,meal_breakfast=:mb,meal_lunch=:ml,meal_dinner=:md,ticket_included=:ti,guide_included=:gi,guide_language=:gl,inclusions=:inc,exclusions=:exc,capacity_min=:cmin,capacity_max=:cmax,price_basis=:pb,currency_code=:cc,net_cost=:nc,markup_type=:mt,markup_value=:mv,sell_price=:sp,child_price=:cp,meta_data=:meta WHERE sys_id=:sid")
        ->execute([':vname'=>$variant_name,':tm'=>$transport_mode,
            ':mb'=>$meal_breakfast,':ml'=>$meal_lunch,':md'=>$meal_dinner,
            ':ti'=>$ticket_included,':gi'=>$guide_included,':gl'=>$guide_language?:null,
            ':inc'=>$inclusions?:null,':exc'=>$exclusions?:null,
            ':cmin'=>$capacity_min,':cmax'=>$capacity_max,':pb'=>$price_basis,
            ':cc'=>$currency_code,':nc'=>$net_cost,':mt'=>$markup_type,
            ':mv'=>$markup_value,':sp'=>$sell_price,':cp'=>$child_price,
            ':meta'=>$meta,':sid'=>$sys_id]);
        echo json_encode(['success'=>true,'action'=>'updated','sys_id'=>$sys_id],JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e){echo json_encode(['success'=>false,'message'=>$e->getMessage()]);}