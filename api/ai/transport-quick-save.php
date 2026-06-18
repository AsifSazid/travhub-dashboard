<?php
/**
 * api/ai/transport-quick-save.php
 * POST { country_sys_id, name, transport_type, from_city, to_city,
 *        direction, vehicle_class, duration_typical,
 *        currency_code, net_cost, sell_price, price_basis }
 * Saves an AI-suggested transport into masterdata (transport_services + variant).
 * Returns { success, sys_id, variant_sys_id, already_exists }
 */
session_start();
require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';
require_once '../../server/id_generator.php';
require_once '../../server/generate_meta_data.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false,'message'=>'POST only']); exit;
}

$in              = json_decode(file_get_contents('php://input'), true) ?: [];
$country_sys_id  = trim($in['country_sys_id']  ?? '');
$name            = trim($in['name']             ?? '');
$transport_type  = in_array($in['transport_type']??'',['airport_transfer','intercity','ferry','shuttle','car_hire','other'])
                    ? $in['transport_type'] : 'intercity';
$from_city       = trim($in['from_city']        ?? '');
$to_city         = trim($in['to_city']          ?? '');
$direction       = in_array($in['direction']??'',['one_way','return','multi']) ? $in['direction'] : 'one_way';
$vehicle_class   = in_array($in['vehicle_class']??'',['sedan','suv','van','minibus','coach','boat','train','other'])
                    ? $in['vehicle_class'] : 'van';
$duration_typical = trim($in['duration_typical'] ?? '');
$currency_code   = strtoupper(trim($in['currency_code'] ?? 'BDT'));
$net_cost        = (float)($in['net_cost']       ?? 0);
$sell_price      = (float)($in['sell_price']     ?? 0);
$price_basis     = in_array($in['price_basis']??'',['per_pax','per_group','per_vehicle','per_day','per_person'])
                    ? $in['price_basis'] : 'per_vehicle';

if (!$country_sys_id) { echo json_encode(['success'=>false,'message'=>'country_sys_id required']); exit; }
if (!$name)           { echo json_encode(['success'=>false,'message'=>'name required']); exit; }

try {
    // Check duplicate by name in same country
    $dup = $pdo->prepare("SELECT sys_id FROM transport_services WHERE country_sys_id=? AND name=? AND status='active' LIMIT 1");
    $dup->execute([$country_sys_id, $name]);
    $existing = $dup->fetchColumn();
    if ($existing) {
        echo json_encode(['success'=>true,'sys_id'=>$existing,'variant_sys_id'=>'','already_exists'=>true]);
        exit;
    }

    // Fetch country name
    $cRow = $pdo->prepare("SELECT name FROM countries WHERE sys_id=? LIMIT 1");
    $cRow->execute([$country_sys_id]);
    $country_name = $cRow->fetchColumn() ?: '';

    $meta    = buildMetaData(null, $_SESSION['user_name'] ?? 'ai-system');
    $ids     = generateIDs($pdo, 'transport_services');
    $svc_sid  = $ids['sys_id'];
    $svc_uuid = $ids['uuid'];

    $pdo->prepare("INSERT INTO transport_services
        (uuid,sys_id,country_sys_id,country_name,name,type,
         from_city_name,to_city_name,direction,duration_typical,
         status,meta_data)
        VALUES (?,?,?,?,?,?,?,?,?,?,'active',?)")
    ->execute([$svc_uuid,$svc_sid,$country_sys_id,$country_name,$name,$transport_type,
               $from_city?:null,$to_city?:null,$direction,$duration_typical?:null,$meta]);

    // Create one default variant
    $vids     = generateChildIDs($pdo,'transport_variants',$svc_sid);
    $var_sid  = $vids['sys_id'];
    $var_uuid = $vids['uuid'];

    $pdo->prepare("INSERT INTO transport_variants
        (uuid,sys_id,service_sys_id,vehicle_class,currency_code,
         net_cost,sell_price,price_basis,status,meta_data)
        VALUES (?,?,?,?,?,?,?,?,'active',?)")
    ->execute([$var_uuid,$var_sid,$svc_sid,$vehicle_class,$currency_code,
               $net_cost,$sell_price?:$net_cost,$price_basis,$meta]);

    echo json_encode([
        'success'      => true,
        'sys_id'       => $svc_sid,
        'variant_sys_id' => $var_sid,
        'already_exists' => false,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}