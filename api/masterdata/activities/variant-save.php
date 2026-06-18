<?php
/**
 * api/masterdata/activities/variant-save.php (Gen-3)
 * POST { sys_id?, activity_sys_id*, country_sys_id*, vendor_sys_id?,
 *        variant_name*, location?, min_pax?, max_pax?, age_min?,
 *        season_from?, season_to?, languages?, meeting_point?,
 *        cancellation_policy?, inclusions?, exclusions?, itineraries?,
 *        transport_mode, meal_breakfast, meal_lunch, meal_dinner,
 *        ticket_included, guide_included, guide_language?,
 *        capacity_min?, capacity_max?,
 *        price_basis, currency_code*, net_cost*, markup_type,
 *        markup_value, sell_price*, child_price?,
 *        unstructured_data? }
 */
session_start();
require_once '../../../server/api_bootstrap.php';
require_once '../../../server/db_connection.php';
require_once '../../../server/id_generator.php';
require_once '../../../server/generate_meta_data.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false,'message'=>'POST only']); exit;
}

$in              = json_decode(file_get_contents('php://input'), true) ?: [];
$sys_id          = trim($in['sys_id']          ?? '');
$activity_sys_id = trim($in['activity_sys_id'] ?? '');
$country_sys_id  = trim($in['country_sys_id']  ?? '');
$vendor_sys_id   = trim($in['vendor_sys_id']   ?? '');
$variant_name    = trim($in['variant_name']    ?? '');
$location        = trim($in['location']        ?? '');
$min_pax         = isset($in['min_pax'])   && $in['min_pax']   !== '' ? (int)$in['min_pax']   : null;
$max_pax         = isset($in['max_pax'])   && $in['max_pax']   !== '' ? (int)$in['max_pax']   : null;
$age_min         = isset($in['age_min'])   && $in['age_min']   !== '' ? (int)$in['age_min']   : null;
$season_from     = trim($in['season_from'] ?? '');
$season_to       = trim($in['season_to']   ?? '');
$languages       = trim($in['languages']   ?? '');
$meeting_point   = trim($in['meeting_point']     ?? '');
$cancel_policy   = trim($in['cancellation_policy'] ?? '');
$inclusions      = is_array($in['inclusions']  ?? null) ? $in['inclusions']  : ($in['inclusions'] ?? null);
$exclusions      = is_array($in['exclusions']  ?? null) ? $in['exclusions']  : ($in['exclusions'] ?? null);
$itineraries     = is_array($in['itineraries'] ?? null) ? $in['itineraries'] : [];

// normalise text inclusions/exclusions (might come as newline-string from JS)
if (is_string($inclusions)) $inclusions = array_filter(array_map('trim', explode("\n", $inclusions)));
if (is_string($exclusions)) $exclusions = array_filter(array_map('trim', explode("\n", $exclusions)));

$valid_modes     = ['none','sic','sedan','suv','van','minibus','coach','boat'];
$transport_mode  = in_array($in['transport_mode']??'', $valid_modes) ? $in['transport_mode'] : 'none';
$meal_breakfast  = !empty($in['meal_breakfast'])  ? 1 : 0;
$meal_lunch      = !empty($in['meal_lunch'])      ? 1 : 0;
$meal_dinner     = !empty($in['meal_dinner'])     ? 1 : 0;
$ticket_included = isset($in['ticket_included'])  ? (!empty($in['ticket_included']) ? 1 : 0) : 1;
$guide_included  = !empty($in['guide_included'])  ? 1 : 0;
$guide_language  = trim($in['guide_language']     ?? '');
$capacity_min    = isset($in['capacity_min'])  && $in['capacity_min']  !== '' ? (int)$in['capacity_min']  : null;
$capacity_max    = isset($in['capacity_max'])  && $in['capacity_max']  !== '' ? (int)$in['capacity_max']  : null;
$price_basis     = in_array($in['price_basis']??'',['per_pax','per_group']) ? $in['price_basis'] : 'per_pax';
$currency_code   = strtoupper(trim($in['currency_code'] ?? ''));
$net_cost        = (float)($in['net_cost']    ?? 0);
$markup_type     = in_array($in['markup_type']??'',['percent','fixed']) ? $in['markup_type'] : 'percent';
$markup_value    = (float)($in['markup_value'] ?? 0);
$sell_price      = (float)($in['sell_price']  ?? 0);
$child_price     = isset($in['child_price'])   && $in['child_price'] !== '' ? (float)$in['child_price'] : null;
$unstructured    = is_array($in['unstructured_data'] ?? null) ? $in['unstructured_data'] : [];

if (!$variant_name)    { echo json_encode(['success'=>false,'message'=>'variant_name required']); exit; }
if (!$currency_code)   { echo json_encode(['success'=>false,'message'=>'currency_code required']); exit; }
if ($sell_price <= 0)  { echo json_encode(['success'=>false,'message'=>'sell_price required']);   exit; }

// encode JSON fields
$inc_json  = $inclusions  ? json_encode(array_values((array)$inclusions), JSON_UNESCAPED_UNICODE)  : null;
$exc_json  = $exclusions  ? json_encode(array_values((array)$exclusions), JSON_UNESCAPED_UNICODE)  : null;
$itin_json = $itineraries ? json_encode($itineraries, JSON_UNESCAPED_UNICODE) : null;
$usd_json  = $unstructured ? json_encode($unstructured, JSON_UNESCAPED_UNICODE) : null;

try {
    $isNew = empty($sys_id);
    if ($isNew) {
        if (!$activity_sys_id) { echo json_encode(['success'=>false,'message'=>'activity_sys_id required']); exit; }
        if (!$country_sys_id)  { echo json_encode(['success'=>false,'message'=>'country_sys_id required']);  exit; }
        $ids    = generateChildIDs($pdo, 'activity_variants', $activity_sys_id);
        $sys_id = $ids['sys_id'];
        $uuid   = $ids['uuid'];
        $meta   = buildMetaData(null, $_SESSION['user_name'] ?? 'system');
        $pdo->prepare("INSERT INTO activity_variants
            (uuid,sys_id,activity_sys_id,country_sys_id,vendor_sys_id,
             variant_name,location,min_pax,max_pax,age_min,
             season_from,season_to,languages,meeting_point,cancellation_policy,
             inclusions,exclusions,itineraries,
             transport_mode,meal_breakfast,meal_lunch,meal_dinner,
             ticket_included,guide_included,guide_language,
             capacity_min,capacity_max,
             price_basis,currency_code,net_cost,
             markup_type,markup_value,sell_price,child_price,
             unstructured_data,status,meta_data)
            VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,\x27active\x27,?)")
        ->execute([
            $uuid,$sys_id,$activity_sys_id,$country_sys_id,$vendor_sys_id?:null,
            $variant_name,$location?:null,$min_pax,$max_pax,$age_min,
            $season_from?:null,$season_to?:null,$languages?:null,$meeting_point?:null,$cancel_policy?:null,
            $inc_json,$exc_json,$itin_json,
            $transport_mode,$meal_breakfast,$meal_lunch,$meal_dinner,
            $ticket_included,$guide_included,$guide_language?:null,
            $capacity_min,$capacity_max,
            $price_basis,$currency_code,$net_cost,
            $markup_type,$markup_value,$sell_price,$child_price,
            $usd_json,$meta,
        ]);
        echo json_encode(['success'=>true,'action'=>'created','sys_id'=>$sys_id,
            'message'=>"Variant '{$variant_name}' created."],JSON_UNESCAPED_UNICODE);
    } else {
        $row = $pdo->prepare("SELECT meta_data FROM activity_variants WHERE sys_id=? LIMIT 1");
        $row->execute([$sys_id]);
        $existing = $row->fetch();
        if (!$existing) { echo json_encode(['success'=>false,'message'=>'Variant not found']); exit; }
        $meta = buildMetaData($existing['meta_data'], $_SESSION['user_name'] ?? 'system');
        $pdo->prepare("UPDATE activity_variants SET
            vendor_sys_id=?,variant_name=?,location=?,
            min_pax=?,max_pax=?,age_min=?,
            season_from=?,season_to=?,languages=?,meeting_point=?,cancellation_policy=?,
            inclusions=?,exclusions=?,itineraries=?,
            transport_mode=?,meal_breakfast=?,meal_lunch=?,meal_dinner=?,
            ticket_included=?,guide_included=?,guide_language=?,
            capacity_min=?,capacity_max=?,
            price_basis=?,currency_code=?,net_cost=?,
            markup_type=?,markup_value=?,sell_price=?,child_price=?,
            unstructured_data=?,meta_data=?
            WHERE sys_id=?")
        ->execute([
            $vendor_sys_id?:null,$variant_name,$location?:null,
            $min_pax,$max_pax,$age_min,
            $season_from?:null,$season_to?:null,$languages?:null,$meeting_point?:null,$cancel_policy?:null,
            $inc_json,$exc_json,$itin_json,
            $transport_mode,$meal_breakfast,$meal_lunch,$meal_dinner,
            $ticket_included,$guide_included,$guide_language?:null,
            $capacity_min,$capacity_max,
            $price_basis,$currency_code,$net_cost,
            $markup_type,$markup_value,$sell_price,$child_price,
            $usd_json,$meta,$sys_id,
        ]);
        echo json_encode(['success'=>true,'action'=>'updated','sys_id'=>$sys_id,
            'message'=>"Variant '{$variant_name}' updated."],JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}