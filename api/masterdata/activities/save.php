<?php
session_start();
// api/masterdata/activities/save.php
// POST — create or update an activity
//
// Body (JSON):
// {
//   "sys_id"          : "THR-26-CNT-01-ACT-01",  ← omit/null = CREATE
//   "country_sys_id"  : "THR-26-CNT-01",           ← required for CREATE
//   "name"            : "Bangkok City Tour",
//   "type"            : "tour",                     ← tour | transfer | both
//   "location"        : "Suvarnabhumi Airport",
//   "start_time"      : "08:00",
//   "end_time"        : "17:00",
//   "duration_hours"  : 8,
//   "popularity"      : 4,
//   "pickup_from_city": [ { country_sys_id, country_name, city_sys_id, city_name } ],
//   "dropoff_city"    : [ { ... } ],
//   "itineraries"     : [ { title, description, icon? } ],
//   "inclusions"      : [ { title, description, icon? } ],
//   "exclusions"      : [ { title, description, icon? } ],
//   "transfers"       : [ { title, type, notes?, pricing: [ { car_sys_id, car_name, price_adult?, price_child?, price_full? } ] } ]
// }
header('Content-Type: application/json');
require_once('../../../server/db_connection.php');
require_once('../../../server/masterdata-id-generator.php');
require_once('../../../server/generate_meta_data.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];

$sys_id         = trim($input['sys_id']         ?? '');
$country_sys_id = trim($input['country_sys_id'] ?? '');
$name           = trim($input['name']           ?? '');
$type           = trim($input['type']           ?? 'tour');
$location       = trim($input['location']       ?? '');
$start_time     = trim($input['start_time']     ?? '');
$end_time       = trim($input['end_time']       ?? '');
$duration_hours = max(0, (float)($input['duration_hours'] ?? 0));
$popularity     = max(1, min(5, (int)($input['popularity'] ?? 3)));

$validTypes = ['tour', 'transfer', 'both'];
if (!in_array($type, $validTypes)) $type = 'tour';

if (!$name) {
    echo json_encode(['success' => false, 'message' => 'name is required']);
    exit;
}

// Sanitize + encode JSON fields
function sanitizeListItems(mixed $raw): string {
    if (!is_array($raw)) return '[]';
    $clean = array_values(array_filter($raw, fn($i) => is_array($i)));
    return json_encode($clean, JSON_UNESCAPED_UNICODE);
}

function sanitizeCityList(mixed $raw): string {
    if (!is_array($raw)) return '[]';
    $clean = [];
    foreach ($raw as $item) {
        if (!is_array($item)) continue;
        $clean[] = [
            'country_sys_id' => trim($item['country_sys_id'] ?? ''),
            'country_name'   => trim($item['country_name']   ?? ''),
            'city_sys_id'    => trim($item['city_sys_id']    ?? ''),
            'city_name'      => trim($item['city_name']      ?? ''),
        ];
    }
    return json_encode(array_values($clean), JSON_UNESCAPED_UNICODE);
}

function sanitizeTransfers(mixed $raw): string {
    if (!is_array($raw)) return '[]';
    $clean = [];
    foreach ($raw as $t) {
        if (!is_array($t)) continue;
        $pricing = [];
        foreach ($t['pricing'] ?? [] as $p) {
            if (!is_array($p)) continue;
            $pricing[] = [
                'car_sys_id'  => trim($p['car_sys_id']  ?? ''),
                'car_name'    => trim($p['car_name']     ?? ''),
                'price_adult' => isset($p['price_adult']) ? (float)$p['price_adult'] : null,
                'price_child' => isset($p['price_child']) ? (float)$p['price_child'] : null,
                'price_full'  => isset($p['price_full'])  ? (float)$p['price_full']  : null,
            ];
        }
        $clean[] = [
            'title'   => trim($t['title']  ?? ''),
            'type'    => in_array($t['type'] ?? '', ['sic','private']) ? $t['type'] : 'sic',
            'notes'   => trim($t['notes']  ?? ''),
            'pricing' => $pricing,
        ];
    }
    return json_encode(array_values($clean), JSON_UNESCAPED_UNICODE);
}

$pickupJson     = sanitizeCityList($input['pickup_from_city'] ?? []);
$dropoffJson    = sanitizeCityList($input['dropoff_city']     ?? []);
$itineriesJson  = sanitizeListItems($input['itineraries']     ?? []);
$inclusionsJson = sanitizeListItems($input['inclusions']      ?? []);
$exclusionsJson = sanitizeListItems($input['exclusions']      ?? []);
$transfersJson  = sanitizeTransfers($input['transfers']       ?? []);

try {
    $isNew = empty($sys_id);

    if ($isNew) {
        if (!$country_sys_id) {
            echo json_encode(['success' => false, 'message' => 'country_sys_id is required to create an activity']);
            exit;
        }

        $ids      = generateHierarchyIDs($pdo, 'activities', $country_sys_id);
        $sys_id   = $ids['sys_id'];
        $uuid     = $ids['uuid'];
        $metaJson = buildMetaData(null, $_SESSION['user_name'] ?? 'system');

        $pdo->prepare("
            INSERT INTO activities
                (uuid, sys_id, country_sys_id, name, type, location,
                 start_time, end_time, duration_hours, popularity,
                 pickup_from_city, dropoff_city, itineraries,
                 inclusions, exclusions, transfers, status, meta_data)
            VALUES
                (:uuid, :sys_id, :country_sys_id, :name, :type, :location,
                 :start_time, :end_time, :duration_hours, :popularity,
                 :pickup_from_city, :dropoff_city, :itineraries,
                 :inclusions, :exclusions, :transfers, 'active', :meta_data)
        ")->execute([
            ':uuid'             => $uuid,
            ':sys_id'           => $sys_id,
            ':country_sys_id'   => $country_sys_id,
            ':name'             => $name,
            ':type'             => $type,
            ':location'         => $location ?: null,
            ':start_time'       => $start_time ?: null,
            ':end_time'         => $end_time   ?: null,
            ':duration_hours'   => $duration_hours,
            ':popularity'       => $popularity,
            ':pickup_from_city' => $pickupJson,
            ':dropoff_city'     => $dropoffJson,
            ':itineraries'      => $itineriesJson,
            ':inclusions'       => $inclusionsJson,
            ':exclusions'       => $exclusionsJson,
            ':transfers'        => $transfersJson,
            ':meta_data'        => $metaJson,
        ]);

        echo json_encode([
            'success' => true,
            'action'  => 'created',
            'sys_id'  => $sys_id,
            'message' => "Activity '{$name}' created.",
        ]);

    } else {
        $chk = $pdo->prepare("SELECT id, meta_data FROM activities WHERE sys_id = ? LIMIT 1");
        $chk->execute([$sys_id]);
        $existing = $chk->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            echo json_encode(['success' => false, 'message' => 'Activity not found']);
            exit;
        }

        $metaJson = buildMetaData($existing['meta_data'], $_SESSION['user_name'] ?? 'system');

        $pdo->prepare("
            UPDATE activities SET
                name = :name, type = :type, location = :location,
                start_time = :start_time, end_time = :end_time,
                duration_hours = :duration_hours, popularity = :popularity,
                pickup_from_city = :pickup_from_city, dropoff_city = :dropoff_city,
                itineraries = :itineraries, inclusions = :inclusions,
                exclusions = :exclusions, transfers = :transfers,
                meta_data = :meta_data
            WHERE sys_id = :sys_id
        ")->execute([
            ':name'             => $name,
            ':type'             => $type,
            ':location'         => $location ?: null,
            ':start_time'       => $start_time ?: null,
            ':end_time'         => $end_time   ?: null,
            ':duration_hours'   => $duration_hours,
            ':popularity'       => $popularity,
            ':pickup_from_city' => $pickupJson,
            ':dropoff_city'     => $dropoffJson,
            ':itineraries'      => $itineriesJson,
            ':inclusions'       => $inclusionsJson,
            ':exclusions'       => $exclusionsJson,
            ':transfers'        => $transfersJson,
            ':meta_data'        => $metaJson,
            ':sys_id'           => $sys_id,
        ]);

        echo json_encode([
            'success' => true,
            'action'  => 'updated',
            'sys_id'  => $sys_id,
            'message' => "Activity '{$name}' updated.",
        ]);
    }

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}