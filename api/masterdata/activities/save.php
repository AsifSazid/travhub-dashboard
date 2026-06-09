<?php
/**
 * api/masterdata/activities/save.php (Gen-3)
 * POST { sys_id?, country_sys_id, country_name?, city_sys_id?, city_name?,
 *         vendor_sys_id?, name, search_terms?, type, category?, location?,
 *         short_description?, long_description?, highlights?,
 *         start_time?, end_time?, duration_hours, duration_typical?,
 *         operating_days?, min_pax?, max_pax?, age_min?, languages?,
 *         meeting_point?, booking_lead_days?, cancellation_policy?,
 *         popularity?, pickup_from_city?, dropoff_city?,
 *         itineraries?, inclusions?, exclusions?, images? }
 */
session_start();
require_once '../../../server/api_bootstrap.php';
require_once '../../../server/db_connection.php';
require_once '../../../server/id_generator.php';
require_once '../../../server/generate_meta_data.php';
require_once '../../../server/json-sync-helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false,'message'=>'POST only']); exit;
}

$in             = json_decode(file_get_contents('php://input'), true) ?: [];
$sys_id         = trim($in['sys_id']         ?? '');
$country_sys_id = trim($in['country_sys_id'] ?? '');
$country_name   = trim($in['country_name']   ?? '');
$city_sys_id    = trim($in['city_sys_id']    ?? '');
$city_name      = trim($in['city_name']      ?? '');
$vendor_sys_id  = trim($in['vendor_sys_id']  ?? '');
$name           = trim($in['name']           ?? '');
$search_terms   = trim($in['search_terms']   ?? '');
$type           = in_array($in['type']??'',['tour','transfer','both']) ? $in['type'] : 'tour';
$category       = trim($in['category']       ?? '');
$location       = trim($in['location']       ?? '');
$short_desc     = trim($in['short_description'] ?? '');
$long_desc      = trim($in['long_description']  ?? '');
$highlights     = trim($in['highlights']     ?? '');
$start_time     = trim($in['start_time']     ?? '');
$end_time       = trim($in['end_time']       ?? '');
$duration_hours = max(0, (float)($in['duration_hours'] ?? 0));
$duration_typical = trim($in['duration_typical'] ?? '');
$operating_days = trim($in['operating_days'] ?? '');
$min_pax        = isset($in['min_pax']) ? (int)$in['min_pax'] : null;
$max_pax        = isset($in['max_pax']) ? (int)$in['max_pax'] : null;
$age_min        = isset($in['age_min']) ? (int)$in['age_min'] : null;
$languages      = trim($in['languages']      ?? '');
$meeting_point  = trim($in['meeting_point']  ?? '');
$booking_lead   = isset($in['booking_lead_days']) ? (int)$in['booking_lead_days'] : null;
$cancel_policy  = trim($in['cancellation_policy'] ?? '');
$popularity     = max(1, min(5, (int)($in['popularity'] ?? 3)));
$pickup_from_city = is_array($in['pickup_from_city'] ?? null) ? $in['pickup_from_city'] : [];
$dropoff_city   = is_array($in['dropoff_city']   ?? null) ? $in['dropoff_city']   : [];
$itineraries    = is_array($in['itineraries']    ?? null) ? $in['itineraries']    : [];
$inclusions     = is_array($in['inclusions']     ?? null) ? $in['inclusions']     : [];
$exclusions     = is_array($in['exclusions']     ?? null) ? $in['exclusions']     : [];
$images         = is_array($in['images']         ?? null) ? $in['images']         : [];

if (!$name) { echo json_encode(['success'=>false,'message'=>'name required']); exit; }

try {
    $isNew = empty($sys_id);
    if ($isNew) {
        if (!$country_sys_id) { echo json_encode(['success'=>false,'message'=>'country_sys_id required']); exit; }
        $ids    = generateChildIDs($pdo, 'activities', $country_sys_id);
        $sys_id = $ids['sys_id']; $uuid = $ids['uuid'];
        $meta   = buildMetaData(null, $_SESSION['user_name'] ?? 'system');
        $pdo->prepare("INSERT INTO activities
            (uuid,sys_id,country_sys_id,country_name,city_sys_id,city_name,vendor_sys_id,
             name,search_terms,type,category,location,short_description,long_description,
             highlights,start_time,end_time,duration_hours,duration_typical,operating_days,
             min_pax,max_pax,age_min,languages,meeting_point,booking_lead_days,
             cancellation_policy,popularity,pickup_from_city,dropoff_city,
             itineraries,inclusions,exclusions,images,status,meta_data)
            VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'active',?)")
        ->execute([$uuid,$sys_id,$country_sys_id,$country_name?:null,$city_sys_id?:null,
            $city_name?:null,$vendor_sys_id?:null,$name,$search_terms?:null,$type,
            $category?:null,$location?:null,$short_desc?:null,$long_desc?:null,
            $highlights?:null,$start_time?:null,$end_time?:null,$duration_hours,
            $duration_typical?:null,$operating_days?:null,$min_pax,$max_pax,$age_min,
            $languages?:null,$meeting_point?:null,$booking_lead,$cancel_policy?:null,$popularity,
            json_encode($pickup_from_city,JSON_UNESCAPED_UNICODE),
            json_encode($dropoff_city,JSON_UNESCAPED_UNICODE),
            json_encode($itineraries,JSON_UNESCAPED_UNICODE),
            json_encode($inclusions,JSON_UNESCAPED_UNICODE),
            json_encode($exclusions,JSON_UNESCAPED_UNICODE),
            json_encode($images,JSON_UNESCAPED_UNICODE),$meta]);
        syncActivitiesJson($pdo);
        echo json_encode(['success'=>true,'action'=>'created','sys_id'=>$sys_id,'message'=>"Activity '{$name}' created."],JSON_UNESCAPED_UNICODE);
    } else {
        $row = $pdo->prepare("SELECT meta_data FROM activities WHERE sys_id=? LIMIT 1");
        $row->execute([$sys_id]); $existing = $row->fetch();
        if (!$existing) { echo json_encode(['success'=>false,'message'=>'Not found']); exit; }
        $meta = buildMetaData($existing['meta_data'], $_SESSION['user_name'] ?? 'system');
        $pdo->prepare("UPDATE activities SET
            country_name=?,city_sys_id=?,city_name=?,vendor_sys_id=?,name=?,search_terms=?,
            type=?,category=?,location=?,short_description=?,long_description=?,highlights=?,
            start_time=?,end_time=?,duration_hours=?,duration_typical=?,operating_days=?,
            min_pax=?,max_pax=?,age_min=?,languages=?,meeting_point=?,booking_lead_days=?,
            cancellation_policy=?,popularity=?,pickup_from_city=?,dropoff_city=?,
            itineraries=?,inclusions=?,exclusions=?,images=?,meta_data=?
            WHERE sys_id=?")
        ->execute([$country_name?:null,$city_sys_id?:null,$city_name?:null,$vendor_sys_id?:null,
            $name,$search_terms?:null,$type,$category?:null,$location?:null,
            $short_desc?:null,$long_desc?:null,$highlights?:null,
            $start_time?:null,$end_time?:null,$duration_hours,$duration_typical?:null,
            $operating_days?:null,$min_pax,$max_pax,$age_min,$languages?:null,
            $meeting_point?:null,$booking_lead,$cancel_policy?:null,$popularity,
            json_encode($pickup_from_city,JSON_UNESCAPED_UNICODE),
            json_encode($dropoff_city,JSON_UNESCAPED_UNICODE),
            json_encode($itineraries,JSON_UNESCAPED_UNICODE),
            json_encode($inclusions,JSON_UNESCAPED_UNICODE),
            json_encode($exclusions,JSON_UNESCAPED_UNICODE),
            json_encode($images,JSON_UNESCAPED_UNICODE),$meta,$sys_id]);
        syncActivitiesJson($pdo);
        echo json_encode(['success'=>true,'action'=>'updated','sys_id'=>$sys_id,'message'=>"Activity '{$name}' updated."],JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
