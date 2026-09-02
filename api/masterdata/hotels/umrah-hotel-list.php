<?php
// api/masterdata/hotels/umrah-hotel-list.php
// Returns for_umrah=1 hotels with room_types and rates nested
session_start();
header('Access-Control-Allow-Origin: *');
require_once '../../../server/api_bootstrap.php';
require_once '../../../server/db_connection.php';

$search         = trim($_GET['search']         ?? '');
$country_sys_id = trim($_GET['country_sys_id'] ?? '');
$city_sys_id    = trim($_GET['city_sys_id']    ?? '');
$status         = trim($_GET['status']         ?? 'active');
$page           = max(1, (int)($_GET['page']   ?? 1));
$limit          = max(1, min(100, (int)($_GET['limit'] ?? 20)));
$offset         = ($page - 1) * $limit;

try {
    $where  = ['for_umrah = 1'];
    $params = [];

    if ($status === 'trash') {
        $where[] = "status = 'deleted'";
    } elseif ($status === 'all') {
        $where[] = "status != 'deleted'";
    } else {
        $where[] = "status = 'active'";
    }

    if ($search !== '') {
        $where[]           = "(name LIKE :s OR search_terms LIKE :s2)";
        $params[':s']      = '%' . $search . '%';
        $params[':s2']     = '%' . $search . '%';
    }
    if ($country_sys_id !== '') {
        $where[]            = "country_sys_id = :cid";
        $params[':cid']     = $country_sys_id;
    }
    if ($city_sys_id !== '') {
        $where[]            = "city_sys_id = :ctid";
        $params[':ctid']    = $city_sys_id;
    }

    $w = 'WHERE ' . implode(' AND ', $where);

    // Total count
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM hotels $w");
    $cnt->execute($params);
    $total = (int)$cnt->fetchColumn();

    // Hotels
    $stmt = $pdo->prepare("
        SELECT id, uuid, sys_id,
               country_sys_id, country_name, city_sys_id, city_name,
               name, star_rating, address, phone, email,
               check_in_time, check_out_time,
               for_umrah, usage_count, status, images
        FROM hotels $w
        ORDER BY country_sys_id ASC, name ASC
        LIMIT :lim OFFSET :off
    ");
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$hotels) {
        echo json_encode(['success' => true, 'data' => [], 'pagination' => [
            'total' => 0, 'page' => $page, 'limit' => $limit, 'total_pages' => 0
        ]]);
        exit;
    }

    // Collect hotel sys_ids for batch fetching
    $hotelSysIds   = array_column($hotels, 'sys_id');
    $inPlaceholders = implode(',', array_fill(0, count($hotelSysIds), '?'));

    // ── Batch fetch room types ────────────────────────────
    $rtStmt = $pdo->prepare("
        SELECT sys_id, hotel_sys_id, room_name, bed_config,
               max_adults, max_children, size_sqm, description, status
        FROM room_types
        WHERE hotel_sys_id IN ($inPlaceholders)
          AND status = 'active'
        ORDER BY hotel_sys_id, room_name
    ");
    $rtStmt->execute($hotelSysIds);
    $allRoomTypes = $rtStmt->fetchAll(PDO::FETCH_ASSOC);

    // ── Batch fetch room rates ────────────────────────────
    $roomTypeSysIds = array_column($allRoomTypes, 'sys_id');
    $allRates       = [];
    if ($roomTypeSysIds) {
        $inRt    = implode(',', array_fill(0, count($roomTypeSysIds), '?'));
        $rrStmt  = $pdo->prepare("
            SELECT sys_id, room_type_sys_id, hotel_sys_id,
                   meal_plan, currency_code,
                   net_cost, sell_price, markup_type, markup_value,
                   valid_from, valid_to, occupancy_basis, status
            FROM room_rates
            WHERE room_type_sys_id IN ($inRt)
              AND status = 'active'
            ORDER BY room_type_sys_id, valid_from
        ");
        $rrStmt->execute($roomTypeSysIds);
        $allRates = $rrStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── Group rates by room_type_sys_id ───────────────────
    $ratesByRoomType = [];
    foreach ($allRates as $rate) {
        $ratesByRoomType[$rate['room_type_sys_id']][] = [
            'sys_id'          => $rate['sys_id'],
            'meal_plan'       => $rate['meal_plan'],
            'currency_code'   => $rate['currency_code'],
            'net_cost'        => (float)$rate['net_cost'],
            'sell_price'      => (float)$rate['sell_price'],
            'markup_type'     => $rate['markup_type'],
            'markup_value'    => (float)$rate['markup_value'],
            'valid_from'      => $rate['valid_from'],
            'valid_to'        => $rate['valid_to'],
            'occupancy_basis' => $rate['occupancy_basis'],
        ];
    }

    // ── Group room types by hotel_sys_id ──────────────────
    $roomTypesByHotel = [];
    foreach ($allRoomTypes as $rt) {
        $roomTypesByHotel[$rt['hotel_sys_id']][] = [
            'sys_id'      => $rt['sys_id'],
            'room_name'   => $rt['room_name'],
            'bed_config'  => $rt['bed_config'],
            'max_adults'  => (int)$rt['max_adults'],
            'max_children'=> (int)$rt['max_children'],
            'size_sqm'    => $rt['size_sqm'] ? (float)$rt['size_sqm'] : null,
            'description' => $rt['description'],
            'rates'       => $ratesByRoomType[$rt['sys_id']] ?? [],
        ];
    }

    // ── Build final response ──────────────────────────────
    $data = [];
    foreach ($hotels as $h) {
        $images = json_decode($h['images'] ?? '[]', true) ?: [];
        $data[] = [
            'sys_id'         => $h['sys_id'],
            'uuid'           => $h['uuid'],
            'country_sys_id' => $h['country_sys_id'],
            'country_name'   => $h['country_name'],
            'city_sys_id'    => $h['city_sys_id'],
            'city_name'      => $h['city_name'],
            'name'           => $h['name'],
            'star_rating'    => $h['star_rating'] ? (int)$h['star_rating'] : null,
            'address'        => $h['address'],
            'phone'          => $h['phone'],
            'email'          => $h['email'],
            'check_in_time'  => $h['check_in_time'],
            'check_out_time' => $h['check_out_time'],
            'for_umrah'      => (int)$h['for_umrah'],
            'thumb'          => $images[0]['url'] ?? null,
            'status'         => $h['status'],
            'room_types'     => $roomTypesByHotel[$h['sys_id']] ?? [],
        ];
    }

    echo json_encode([
        'success'    => true,
        'data'       => $data,
        'pagination' => [
            'total'       => $total,
            'page'        => $page,
            'limit'       => $limit,
            'total_pages' => (int)ceil($total / $limit),
        ],
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}