<?php
/**
 * api/masterdata/transport/variant-save.php (Gen-3)
 * POST { sys_id?, service_sys_id, country_sys_id,
 *        variant_name, vehicle_class, capacity_max,
 *        seat_count?, max_luggage_kg?, max_luggage_bags?,
 *        price_basis, transfer_type, meet_and_greet,
 *        currency_code, net_cost, markup_type, markup_value, sell_price,
 *        child_price?, extra_charges? }
 */
session_start();
require_once '../../../server/api_bootstrap.php';
require_once '../../../server/db_connection.php';
require_once '../../../server/id_generator.php';
require_once '../../../server/generate_meta_data.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST only']); exit;
}

$in = json_decode(file_get_contents('php://input'), true) ?: [];

$sys_id          = trim($in['sys_id']          ?? '');
$service_sys_id  = trim($in['service_sys_id']  ?? '');
$country_sys_id  = trim($in['country_sys_id']  ?? '');
$vendor_sys_id   = trim($in['vendor_sys_id']   ?? '');
$variant_name    = trim($in['variant_name']    ?? '');
$vehicle_class   = trim($in['vehicle_class']   ?? 'van');
$capacity_max    = max(1, (int)($in['capacity_max'] ?? 1));
$seat_count      = isset($in['seat_count'])      ? (int)$in['seat_count']      : null;
$max_luggage_kg  = isset($in['max_luggage_kg'])  ? (int)$in['max_luggage_kg']  : null;
$max_luggage_bags= isset($in['max_luggage_bags'])? (int)$in['max_luggage_bags']: null;
$price_basis     = trim($in['price_basis']     ?? 'per_vehicle');
$transfer_type   = in_array($in['transfer_type'] ?? '', ['sic','private']) ? $in['transfer_type'] : 'private';
$meet_and_greet  = (int)(!empty($in['meet_and_greet']));
$currency_code   = strtoupper(trim($in['currency_code'] ?? 'BDT'));
$net_cost        = (float)($in['net_cost']     ?? 0);
$markup_type     = in_array($in['markup_type'] ?? '', ['percent','fixed']) ? $in['markup_type'] : 'percent';
$markup_value    = max(0, (float)($in['markup_value'] ?? 0));
$sell_price      = (float)($in['sell_price']   ?? 0);
$child_price     = isset($in['child_price'])   ? (float)$in['child_price']   : null;
$extra_charges   = is_array($in['extra_charges'] ?? null) ? $in['extra_charges'] : [];

$validClasses = ['sedan','suv','van','minibus','coach','boat','train','other'];
if (!in_array($vehicle_class, $validClasses)) $vehicle_class = 'van';

$validBases = ['per_vehicle','per_person','per_day','per_km','per_hour'];
if (!in_array($price_basis, $validBases)) $price_basis = 'per_vehicle';

if (!$service_sys_id || !$variant_name || !$currency_code) {
    echo json_encode(['success' => false, 'message' => 'service_sys_id, variant_name, currency_code required']); exit;
}

try {
    $isNew = empty($sys_id);

    if ($isNew) {
        $ids    = generateChildIDs($pdo, 'transport_variants', $service_sys_id);
        $sys_id = $ids['sys_id'];
        $uuid   = $ids['uuid'];
        $meta   = buildMetaData(null, $_SESSION['user_name'] ?? 'system');

        $pdo->prepare("
            INSERT INTO transport_variants
                (uuid, sys_id, service_sys_id, country_sys_id, vendor_sys_id,
                 variant_name, vehicle_class, capacity_max,
                 seat_count, max_luggage_kg, max_luggage_bags,
                 price_basis, transfer_type, meet_and_greet,
                 currency_code, net_cost, markup_type, markup_value, sell_price,
                 child_price, extra_charges, status, meta_data)
            VALUES
                (:uuid, :sid, :ssid, :csid, :vsid,
                 :vname, :vc, :cmax,
                 :sc, :mlkg, :mlbags,
                 :pb, :tt, :mg,
                 :cc, :nc, :mt, :mv, :sp,
                 :cp, :ec, 'active', :meta)
        ")->execute([
            ':uuid'    => $uuid,           ':sid'    => $sys_id,
            ':ssid'    => $service_sys_id, ':csid'   => $country_sys_id, ':vsid'   => $vendor_sys_id,
            ':vname'   => $variant_name,   ':vc'     => $vehicle_class,
            ':cmax'    => $capacity_max,
            ':sc'      => $seat_count,     ':mlkg'   => $max_luggage_kg,
            ':mlbags'  => $max_luggage_bags,
            ':pb'      => $price_basis,    ':tt'     => $transfer_type,
            ':mg'      => $meet_and_greet,
            ':cc'      => $currency_code,  ':nc'     => $net_cost,
            ':mt'      => $markup_type,    ':mv'     => $markup_value,
            ':sp'      => $sell_price,
            ':cp'      => $child_price,
            ':ec'      => json_encode($extra_charges, JSON_UNESCAPED_UNICODE),
            ':meta'    => $meta,
        ]);

        echo json_encode(['success' => true, 'action' => 'created', 'sys_id' => $sys_id,
            'message' => "Variant '{$variant_name}' created."], JSON_UNESCAPED_UNICODE);

    } else {
        $row = $pdo->prepare("SELECT meta_data FROM transport_variants WHERE sys_id = ? LIMIT 1");
        $row->execute([$sys_id]);
        $existing = $row->fetch();
        if (!$existing) { echo json_encode(['success' => false, 'message' => 'Variant not found']); exit; }

        $meta = buildMetaData($existing['meta_data'], $_SESSION['user_name'] ?? 'system');

        $pdo->prepare("
            UPDATE transport_variants SET
                vendor_sys_id   = :vsid,
                variant_name    = :vname,
                vehicle_class   = :vc,
                capacity_max    = :cmax,
                seat_count      = :sc,
                max_luggage_kg  = :mlkg,
                max_luggage_bags= :mlbags,
                price_basis     = :pb,
                transfer_type   = :tt,
                meet_and_greet  = :mg,
                currency_code   = :cc,
                net_cost        = :nc,
                markup_type     = :mt,
                markup_value    = :mv,
                sell_price      = :sp,
                child_price     = :cp,
                extra_charges   = :ec,
                meta_data       = :meta
            WHERE sys_id = :sid
        ")->execute([
            ':vsid'    => $vendor_sys_id,
            ':vname'   => $variant_name,   ':vc'     => $vehicle_class,
            ':cmax'    => $capacity_max,
            ':sc'      => $seat_count,     ':mlkg'   => $max_luggage_kg,
            ':mlbags'  => $max_luggage_bags,
            ':pb'      => $price_basis,    ':tt'     => $transfer_type,
            ':mg'      => $meet_and_greet,
            ':cc'      => $currency_code,  ':nc'     => $net_cost,
            ':mt'     => $markup_type,    ':mv'     => $markup_value,
            ':sp'      => $sell_price,
            ':cp'      => $child_price,
            ':ec'      => json_encode($extra_charges, JSON_UNESCAPED_UNICODE),
            ':meta'    => $meta,           ':sid'    => $sys_id,
        ]);

        echo json_encode(['success' => true, 'action' => 'updated', 'sys_id' => $sys_id,
            'message' => "Variant '{$variant_name}' updated."], JSON_UNESCAPED_UNICODE);
    }

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
