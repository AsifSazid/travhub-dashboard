<?php
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
$country_sys_id = trim($in['country_sys_id'] ?? '');
$city_sys_id    = trim($in['city_sys_id']    ?? '');
$name           = trim($in['name']           ?? '');
$type           = is_array($in['type'] ?? null) ? $in['type'] : ['tourism'];
$popularity     = max(1, min(5, (int)($in['popularity']  ?? 3)));
$cost_level     = in_array($in['cost_level'] ?? '', ['low','medium','high']) ? $in['cost_level'] : 'medium';
$visa_ease      = in_array($in['visa_ease']  ?? '', ['easy','medium','hard']) ? $in['visa_ease']  : 'medium';

if (!$country_sys_id || !$name) {
    echo json_encode(['success'=>false,'message'=>'country_sys_id and name are required']); exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, cities, meta_data FROM countries WHERE sys_id=? LIMIT 1");
    $stmt->execute([$country_sys_id]);
    $country = $stmt->fetch();
    if (!$country) { echo json_encode(['success'=>false,'message'=>'Country not found']); exit; }

    $cities = json_decode($country['cities'] ?? '[]', true) ?: [];
    $isNew  = empty($city_sys_id);

    if ($isNew) {
        // Generate new city sys_id based on count
        $ids         = generateChildIDs($pdo, 'cities', $country_sys_id);
        $city_sys_id = $ids['sys_id'];
        $cities[]    = [
            'sys_id'         => $city_sys_id,
            'name'           => $name,
            'country_sys_id' => $country_sys_id,
            'type'           => $type,
            'popularity'     => $popularity,
            'cost_level'     => $cost_level,
            'visa_ease'      => $visa_ease,
        ];
        $action = 'created';
    } else {
        $found = false;
        foreach ($cities as &$city) {
            if ($city['sys_id'] === $city_sys_id) {
                $city['name']        = $name;
                $city['type']        = $type;
                $city['popularity']  = $popularity;
                $city['cost_level']  = $cost_level;
                $city['visa_ease']   = $visa_ease;
                $found = true;
                break;
            }
        }
        unset($city);
        if (!$found) { echo json_encode(['success'=>false,'message'=>'City not found']); exit; }
        $action = 'updated';
    }

    $meta = buildMetaData($country['meta_data'], $_SESSION['user_name'] ?? 'system');
    $pdo->prepare("UPDATE countries SET cities=?,meta_data=? WHERE sys_id=?")
        ->execute([json_encode($cities, JSON_UNESCAPED_UNICODE), $meta, $country_sys_id]);

    syncCountriesJson($pdo);
    echo json_encode(['success'=>true,'action'=>$action,'city_sys_id'=>$city_sys_id,
        'message'=>"City '{$name}' {$action}."], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}