<?php
session_start();
// api/masterdata/activities/save.php
// POST — create or update an activity
//
// Body (JSON):
// {
//   "sys_id"         : "THR-26-CNT-01-CTS-01-ACT-01",  ← omit/null = CREATE
//   "city_sys_id"    : "THR-26-CNT-01-CTS-01",          ← required for CREATE
//   "country_sys_id" : "THR-26-CNT-01",                  ← required for CREATE
//   "name"           : "Boat Safari",
//   "type"           : "adventure",
//   "price_range"    : "medium",
//   "duration_hours" : 3,
//   "popularity"     : 4
// }
header('Content-Type: application/json');
require_once('../../../server/db_connection.php');
require_once('../../../server/masterdata-id-generator.php');
require_once('../../../server/generate_meta_data.php');
require_once('../../../server/json-sync-helper.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false,'message'=>'Method not allowed']); exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];

$sys_id         = trim($input['sys_id']         ?? '');
$city_sys_id    = trim($input['city_sys_id']    ?? '');
$country_sys_id = trim($input['country_sys_id'] ?? '');
$name           = trim($input['name']           ?? '');
$type           = trim($input['type']           ?? 'tourism');
$price_range    = trim($input['price_range']    ?? 'medium');
$duration       = max(0.5, (float)($input['duration_hours'] ?? 1));
$popularity     = max(1, min(5, (int)($input['popularity']  ?? 3)));

if (!$name) {
    echo json_encode(['success'=>false,'message'=>'name is required']); exit;
}

$validPrices = ['free','low','medium','high'];
if (!in_array($price_range, $validPrices)) $price_range = 'medium';

try {
    $isNew = empty($sys_id);

    if ($isNew) {
        if (!$city_sys_id || !$country_sys_id) {
            echo json_encode(['success'=>false,'message'=>'city_sys_id and country_sys_id are required to create an activity']);
            exit;
        }
        $ids      = generateHierarchyIDs($pdo, 'activities', $city_sys_id);
        $sys_id   = $ids['sys_id'];
        $uuid     = $ids['uuid'];
        $metaJson = buildMetaData(null, $_SESSION['user_name'] ?? 'system');

        $pdo->prepare("
            INSERT INTO activities
                (uuid, sys_id, city_sys_id, country_sys_id, name, type,
                 price_range, duration_hours, popularity, status, meta_data)
            VALUES
                (:uuid, :sys_id, :city_sys_id, :country_sys_id, :name, :type,
                 :price_range, :duration_hours, :popularity, 'active', :meta_data)
        ")->execute([
            ':uuid'           => $uuid,
            ':sys_id'         => $sys_id,
            ':city_sys_id'    => $city_sys_id,
            ':country_sys_id' => $country_sys_id,
            ':name'           => $name,
            ':type'           => $type,
            ':price_range'    => $price_range,
            ':duration_hours' => $duration,
            ':popularity'     => $popularity,
            ':meta_data'      => $metaJson,
        ]);

        exportActivitiesToJson($pdo, __DIR__ . '/../../../activities.json');
        echo json_encode(['success'=>true,'action'=>'created','sys_id'=>$sys_id,
            'message'=>"Activity '{$name}' created."]);

    } else {
        $chk = $pdo->prepare("SELECT id, meta_data FROM activities WHERE sys_id = ? LIMIT 1");
        $chk->execute([$sys_id]);
        $existing = $chk->fetch(PDO::FETCH_ASSOC);
        if (!$existing) { echo json_encode(['success'=>false,'message'=>'Activity not found']); exit; }

        $metaJson = buildMetaData($existing['meta_data'], $_SESSION['user_name'] ?? 'system');

        $pdo->prepare("
            UPDATE activities SET
                name = :name, type = :type, price_range = :price_range,
                duration_hours = :duration_hours, popularity = :popularity,
                meta_data = :meta_data
            WHERE sys_id = :sys_id
        ")->execute([
            ':name'           => $name,
            ':type'           => $type,
            ':price_range'    => $price_range,
            ':duration_hours' => $duration,
            ':popularity'     => $popularity,
            ':meta_data'      => $metaJson,
            ':sys_id'         => $sys_id,
        ]);

        exportActivitiesToJson($pdo, __DIR__ . '/../../../activities.json');
        echo json_encode(['success'=>true,'action'=>'updated','sys_id'=>$sys_id,
            'message'=>"Activity '{$name}' updated."]);
    }

} catch (Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}