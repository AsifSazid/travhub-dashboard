<?php
/**
 * FILE PATH: /api/hotel-services/masterdata-upsert.php
 *
 * Review modal-এ "Approve" করলে এই endpoint call হয়।
 * hotels / room_types / room_rates — upsert করে।
 *
 * POST (JSON):
 *   {
 *     matched_hotel_sys_id:     string | null,   // null = নতুন hotel তৈরি করো
 *     matched_room_type_sys_id: string | null,
 *     hotel:     { name, city, country, star_rating, address, phone, email },
 *     room_type: { room_name, bed_config, max_adults, max_children, size_sqm },
 *     room_rate: { meal_plan, net_cost, sell_price, currency_code, valid_from, valid_to, cancellation_policy },
 *     city_sys_id:    string | null,
 *     country_sys_id: string | null,
 *   }
 *
 * OUTPUT:
 *   { success, hotel_sys_id, room_type_sys_id, room_rate_sys_id, actions: {} }
 */

session_start();
date_default_timezone_set('Asia/Dhaka');
ini_set('display_errors', 0);

require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';
require_once '../../server/id_generator.php';
require_once '../../server/sys_id_generator_v2.php';
require_once '../../server/generate_meta_data.php';
require_once '../../server/json-sync-helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false,'message'=>'POST only']); exit;
}

$in = json_decode(file_get_contents('php://input'), true) ?: [];
$userName = $_SESSION['user_name'] ?? 'system';

$matchedHotelId    = trim($in['matched_hotel_sys_id']     ?? '');
$matchedRoomTypeId = trim($in['matched_room_type_sys_id'] ?? '');
$hotel     = $in['hotel']     ?? [];
$roomType  = $in['room_type'] ?? [];
$roomRate  = $in['room_rate'] ?? [];
$countrySysId = trim($in['country_sys_id'] ?? '');
$citySysId    = trim($in['city_sys_id']    ?? '');

try {
    $actions = [];

    // ── 1. Hotel upsert ───────────────────────────────────────
    if ($matchedHotelId) {
        // Update existing hotel
        $row = $pdo->prepare("SELECT meta_data FROM hotels WHERE sys_id=? LIMIT 1");
        $row->execute([$matchedHotelId]); $existing = $row->fetch();
        if ($existing) {
            $meta = buildMetaData($existing['meta_data'], $userName);
            $fields = [];
            $params = [];
            if (!empty($hotel['city']))        { $fields[] = 'city_name=?';    $params[] = $hotel['city']; }
            if (!empty($hotel['country']))     { $fields[] = 'country_name=?'; $params[] = $hotel['country']; }
            if (!empty($hotel['star_rating'])) { $fields[] = 'star_rating=?';  $params[] = (int)$hotel['star_rating']; }
            if (!empty($hotel['address']))     { $fields[] = 'address=?';      $params[] = $hotel['address']; }
            if (!empty($hotel['phone']))       { $fields[] = 'phone=?';        $params[] = $hotel['phone']; }
            if (!empty($hotel['email']))       { $fields[] = 'email=?';        $params[] = $hotel['email']; }
            if ($fields) {
                $fields[]  = 'meta_data=?';
                $params[]  = $meta;
                $params[]  = $matchedHotelId;
                $pdo->prepare("UPDATE hotels SET " . implode(',', $fields) . " WHERE sys_id=?")->execute($params);
            }
            $hotelSysId = $matchedHotelId;
            $actions['hotel'] = 'updated';
        } else {
            $matchedHotelId = ''; // not found, fall through to create
        }
    }

    if (!$matchedHotelId) {
        // Create new hotel
        $name = trim($hotel['name'] ?? '');
        if (!$name) { echo json_encode(['success'=>false,'message'=>'hotel.name required']); exit; }
        $ids  = generateIDs($pdo, 'hotels');
        $meta = buildMetaData(null, $userName);
        $pdo->prepare("
            INSERT INTO hotels (uuid, sys_id, country_sys_id, country_name, city_sys_id, city_name,
                name, star_rating, address, phone, email, status, meta_data)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?)
        ")->execute([
            $ids['uuid'], $ids['sys_id'],
            $countrySysId ?: null, $hotel['country'] ?? null,
            $citySysId    ?: null, $hotel['city']    ?? null,
            $name,
            !empty($hotel['star_rating']) ? (int)$hotel['star_rating'] : null,
            $hotel['address'] ?? null,
            $hotel['phone']   ?? null,
            $hotel['email']   ?? null,
            $meta,
        ]);
        $hotelSysId = $ids['sys_id'];
        if (function_exists('syncHotelsJson')) syncHotelsJson($pdo);
        $actions['hotel'] = 'created';
    }

    // ── 2. Room type upsert ───────────────────────────────────
    $roomName = trim($roomType['room_name'] ?? '');
    $roomTypeSysId = null;

    if ($roomName) {
        if ($matchedRoomTypeId) {
            $row = $pdo->prepare("SELECT meta_data FROM room_types WHERE sys_id=? LIMIT 1");
            $row->execute([$matchedRoomTypeId]); $existing = $row->fetch();
            if ($existing) {
                $meta = buildMetaData($existing['meta_data'], $userName);
                $fields = []; $params = [];
                if (!empty($roomType['bed_config']))   { $fields[] = 'bed_config=?';          $params[] = $roomType['bed_config']; }
                if (!empty($roomType['max_adults']))   { $fields[] = 'max_adults=?';           $params[] = (int)$roomType['max_adults']; }
                if (isset($roomType['max_children']))  { $fields[] = 'max_children=?';         $params[] = (int)$roomType['max_children']; }
                if (!empty($roomType['size_sqm']))     { $fields[] = 'size_sqm=?';             $params[] = (int)$roomType['size_sqm']; }
                $fields[] = 'meta_data=?'; $params[] = $meta; $params[] = $matchedRoomTypeId;
                if ($fields) $pdo->prepare("UPDATE room_types SET ".implode(',', $fields)." WHERE sys_id=?")->execute($params);
                $roomTypeSysId = $matchedRoomTypeId;
                $actions['room_type'] = 'updated';
            }
        }

        if (!$roomTypeSysId) {
            // Create new room type under this hotel
            $ids  = generateChildIDs($pdo, 'room_types', $hotelSysId);
            $meta = buildMetaData(null, $userName);
            $pdo->prepare("
                INSERT INTO room_types (uuid, sys_id, hotel_sys_id, hotel_name, room_name,
                    bed_config, max_adults, max_children, standard_occupancy, size_sqm, status, meta_data)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?)
            ")->execute([
                $ids['uuid'], $ids['sys_id'],
                $hotelSysId,
                $hotel['name'] ?? '',
                $roomName,
                $roomType['bed_config']   ?? null,
                (int)($roomType['max_adults']   ?? 2),
                (int)($roomType['max_children'] ?? 0),
                (int)($roomType['max_adults']   ?? 2),
                !empty($roomType['size_sqm']) ? (int)$roomType['size_sqm'] : null,
                $meta,
            ]);
            $roomTypeSysId = $ids['sys_id'];
            $actions['room_type'] = 'created';
        }
    }

    // ── 3. Room rate insert (always new — rates are date-ranged) ──
    $roomRateSysId = null;
    $netCost = (float)($roomRate['net_cost'] ?? 0);

    if ($roomTypeSysId && $netCost > 0 && !empty($roomRate['valid_from'])) {
        $ids  = generateChildIDs($pdo, 'room_rates', $roomTypeSysId);
        $meta = buildMetaData(null, $userName);
        $pdo->prepare("
            INSERT INTO room_rates (uuid, sys_id, room_type_sys_id, hotel_sys_id, meal_plan,
                valid_from, valid_to, currency_code, net_cost, markup_type, markup_value,
                sell_price, cancellation_policy, status, meta_data)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'percent', ?, ?, ?, 'active', ?)
        ")->execute([
            $ids['uuid'], $ids['sys_id'],
            $roomTypeSysId, $hotelSysId,
            $roomRate['meal_plan']    ?? 'bb',
            $roomRate['valid_from']   ?? date('Y-m-d'),
            $roomRate['valid_to']     ?? date('Y-m-d', strtotime('+30 days')),
            strtoupper($roomRate['currency_code'] ?? 'BDT'),
            $netCost,
            0, // markup_value — 0, sell_price explicitly given
            (float)($roomRate['sell_price'] ?? 0),
            $roomRate['cancellation_policy'] ?? null,
            $meta,
        ]);
        $roomRateSysId = $ids['sys_id'];
        $actions['room_rate'] = 'created';
    }

    echo json_encode([
        'success'           => true,
        'hotel_sys_id'      => $hotelSysId      ?? null,
        'room_type_sys_id'  => $roomTypeSysId   ?? null,
        'room_rate_sys_id'  => $roomRateSysId   ?? null,
        'actions'           => $actions,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}