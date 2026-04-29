<?php
session_start();
// api/masterdata/cars/save.php
// POST — create or update a car
//
// Body (JSON):
// {
//   "sys_id"         : "THR-26-CNT-01-CAR-01",  ← omit/null = CREATE
//   "country_sys_id" : "THR-26-CNT-01",           ← required for CREATE
//   "name"           : "Toyota Hiace Van",
//   "type"           : "van",
//   "seats"          : 7,
//   "has_luggage"    : true,
//   "max_luggage"    : "20kg"
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
$type           = trim($input['type']           ?? 'sedan');
$seats          = max(1, (int)($input['seats']  ?? 4));
$has_luggage    = !empty($input['has_luggage']) ? 1 : 0;
$max_luggage    = trim($input['max_luggage']    ?? '');

$validTypes = ['sedan','van','suv','minibus','microbus','coaster','bus','other'];
if (!in_array($type, $validTypes)) $type = 'sedan';

if (!$name) {
    echo json_encode(['success' => false, 'message' => 'name is required']);
    exit;
}

try {
    $isNew = empty($sys_id);

    if ($isNew) {
        if (!$country_sys_id) {
            echo json_encode(['success' => false, 'message' => 'country_sys_id is required to create a car']);
            exit;
        }

        $ids      = generateHierarchyIDs($pdo, 'cars', $country_sys_id);
        $sys_id   = $ids['sys_id'];
        $uuid     = $ids['uuid'];
        $metaJson = buildMetaData(null, $_SESSION['user_name'] ?? 'system');

        $pdo->prepare("
            INSERT INTO cars
                (uuid, sys_id, country_sys_id, name, type, seats, has_luggage, max_luggage, status, meta_data)
            VALUES
                (:uuid, :sys_id, :country_sys_id, :name, :type, :seats, :has_luggage, :max_luggage, 'active', :meta_data)
        ")->execute([
            ':uuid'           => $uuid,
            ':sys_id'         => $sys_id,
            ':country_sys_id' => $country_sys_id,
            ':name'           => $name,
            ':type'           => $type,
            ':seats'          => $seats,
            ':has_luggage'    => $has_luggage,
            ':max_luggage'    => $max_luggage ?: null,
            ':meta_data'      => $metaJson,
        ]);

        echo json_encode([
            'success' => true,
            'action'  => 'created',
            'sys_id'  => $sys_id,
            'message' => "Car '{$name}' created.",
        ]);

    } else {
        $chk = $pdo->prepare("SELECT id, meta_data FROM cars WHERE sys_id = ? LIMIT 1");
        $chk->execute([$sys_id]);
        $existing = $chk->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            echo json_encode(['success' => false, 'message' => 'Car not found']);
            exit;
        }

        $metaJson = buildMetaData($existing['meta_data'], $_SESSION['user_name'] ?? 'system');

        $pdo->prepare("
            UPDATE cars SET
                name = :name, type = :type, seats = :seats,
                has_luggage = :has_luggage, max_luggage = :max_luggage,
                meta_data = :meta_data
            WHERE sys_id = :sys_id
        ")->execute([
            ':name'        => $name,
            ':type'        => $type,
            ':seats'       => $seats,
            ':has_luggage' => $has_luggage,
            ':max_luggage' => $max_luggage ?: null,
            ':meta_data'   => $metaJson,
            ':sys_id'      => $sys_id,
        ]);

        echo json_encode([
            'success' => true,
            'action'  => 'updated',
            'sys_id'  => $sys_id,
            'message' => "Car '{$name}' updated.",
        ]);
    }

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}