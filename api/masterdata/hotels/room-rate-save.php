<?php
// POST { sys_id?, room_type_sys_id, hotel_sys_id, supplier_sys_id?,
//         meal_plan, occupancy_basis, valid_from, valid_to,
//         currency_code, net_cost, markup_type, markup_value, sell_price,
//         tax_basis?, tax_service_pct?, tax_vat_pct?,
//         cancellation_policy? }
session_start();
require_once '../../../server/api_bootstrap.php';
require_once '../../../server/db_connection.php';
require_once '../../../server/id_generator.php';
require_once '../../../server/generate_meta_data.php';

if ($_SERVER['REQUEST_METHOD']!=='POST'){echo json_encode(['success'=>false,'message'=>'POST only']);exit;}
$in=json_decode(file_get_contents('php://input'),true)?:[];
$sys_id           =trim($in['sys_id']           ??'');
$room_type_sys_id =trim($in['room_type_sys_id'] ??'');
$hotel_sys_id     =trim($in['hotel_sys_id']     ??'');
$meal_plan        =trim($in['meal_plan']         ??'bb');
$occupancy_basis  =trim($in['occupancy_basis']   ??'per_room');
$valid_from       =trim($in['valid_from']        ??'');
$valid_to         =trim($in['valid_to']          ??'');
$currency_code    =strtoupper(trim($in['currency_code']??'BDT'));
$net_cost         =(float)($in['net_cost']        ??0);
$markup_type      =in_array($in['markup_type']??'',['percent','fixed'])?$in['markup_type']:'percent';
$markup_value     =max(0,(float)($in['markup_value'] ??0));
$sell_price       =(float)($in['sell_price']      ??0);
$tax_basis        =in_array($in['tax_basis']??'',['inclusive','plus_plus'])?$in['tax_basis']:'plus_plus';
$tax_service_pct  =isset($in['tax_service_pct'])?(float)$in['tax_service_pct']:null;
$tax_vat_pct      =isset($in['tax_vat_pct'])?(float)$in['tax_vat_pct']:null;
$cancellation_policy=trim($in['cancellation_policy']??'');

if (!$room_type_sys_id||!$valid_from||!$valid_to||$net_cost<=0){
    echo json_encode(['success'=>false,'message'=>'room_type_sys_id, valid_from, valid_to, net_cost required']); exit;
}
try {
    $isNew=empty($sys_id);
    if ($isNew) {
        $ids=generateChildIDs($pdo,'room_rates',$room_type_sys_id); $sys_id=$ids['sys_id']; $uuid=$ids['uuid'];
        $meta=buildMetaData(null,$_SESSION['user_name']??'system');
        $pdo->prepare("INSERT INTO room_rates (uuid,sys_id,room_type_sys_id,hotel_sys_id,meal_plan,occupancy_basis,valid_from,valid_to,currency_code,net_cost,markup_type,markup_value,sell_price,tax_basis,tax_service_pct,tax_vat_pct,cancellation_policy,status,meta_data)
            VALUES(:uuid,:sid,:rtsid,:hsid,:mp,:ob,:vf,:vt,:cc,:nc,:mt,:mv,:sp,:tb,:tsp,:tvp,:cp,'active',:meta)")
        ->execute([':uuid'=>$uuid,':sid'=>$sys_id,':rtsid'=>$room_type_sys_id,':hsid'=>$hotel_sys_id,
            ':mp'=>$meal_plan,':ob'=>$occupancy_basis,
            ':vf'=>$valid_from,':vt'=>$valid_to,':cc'=>$currency_code,':nc'=>$net_cost,
            ':mt'=>$markup_type,':mv'=>$markup_value,':sp'=>$sell_price,':tb'=>$tax_basis,
            ':tsp'=>$tax_service_pct,':tvp'=>$tax_vat_pct,
            ':cp'=>$cancellation_policy?:null,':meta'=>$meta]);
        echo json_encode(['success'=>true,'action'=>'created','sys_id'=>$sys_id],JSON_UNESCAPED_UNICODE);
    } else {
        $row=$pdo->prepare("SELECT meta_data FROM room_rates WHERE sys_id=? LIMIT 1");
        $row->execute([$sys_id]); $existing=$row->fetch();
        if (!$existing){echo json_encode(['success'=>false,'message'=>'Not found']);exit;}
        $meta=buildMetaData($existing['meta_data'],$_SESSION['user_name']??'system');
        $pdo->prepare("UPDATE room_rates SET meal_plan=:mp,occupancy_basis=:ob,valid_from=:vf,valid_to=:vt,currency_code=:cc,net_cost=:nc,markup_type=:mt,markup_value=:mv,sell_price=:sp,tax_basis=:tb,tax_service_pct=:tsp,tax_vat_pct=:tvp,cancellation_policy=:cp,meta_data=:meta WHERE sys_id=:sid")
        ->execute([':mp'=>$meal_plan,':ob'=>$occupancy_basis,
            ':vf'=>$valid_from,':vt'=>$valid_to,':cc'=>$currency_code,':nc'=>$net_cost,
            ':mt'=>$markup_type,':mv'=>$markup_value,':sp'=>$sell_price,':tb'=>$tax_basis,
            ':tsp'=>$tax_service_pct,':tvp'=>$tax_vat_pct,
            ':cp'=>$cancellation_policy?:null,':meta'=>$meta,':sid'=>$sys_id]);
        echo json_encode(['success'=>true,'action'=>'updated','sys_id'=>$sys_id],JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e){echo json_encode(['success'=>false,'message'=>$e->getMessage()]);}