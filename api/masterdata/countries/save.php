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

$in            = json_decode(file_get_contents('php://input'), true) ?: [];
$sys_id        = trim($in['sys_id']        ?? '');
$name          = trim($in['name']          ?? '');
$code          = strtoupper(trim($in['code'] ?? ''));
$currency      = trim($in['currency']      ?? '');
$currency_code = strtoupper(trim($in['currency_code'] ?? ''));
$default_rate  = max(0, (float)($in['default_rate']   ?? 1));
$region        = trim($in['region']        ?? '');
$for_package   = isset($in['for_package']) ? (int)(bool)$in['for_package'] : 0;

if (!$name || !$code || !$currency || !$currency_code || !$region) {
    echo json_encode(['success'=>false,'message'=>'name, code, currency, currency_code, region are required']); exit;
}

try {
    $isNew = empty($sys_id);

    if ($isNew) {
        // Gen-3 fix: use 'ROOT' as parent for countries
        $ids    = generateChildIDs($pdo, 'countries', 'ROOT');
        $sys_id = $ids['sys_id'];
        $uuid   = $ids['uuid'];
        $meta   = buildMetaData(null, $_SESSION['user_name'] ?? 'system');

        $pdo->prepare("
            INSERT INTO countries
                (uuid, sys_id, name, code, currency, currency_code,
                 default_rate, region, for_package, cities, status, meta_data)
            VALUES
                (:uuid, :sys_id, :name, :code, :currency, :ccy,
                 :rate, :region, :fp, '[]', 'active', :meta)
        ")->execute([
            ':uuid'   => $uuid,       ':sys_id'  => $sys_id,
            ':name'   => $name,       ':code'    => $code,
            ':currency'=> $currency,  ':ccy'     => $currency_code,
            ':rate'   => $default_rate, ':region' => $region,
            ':fp'     => $for_package, ':meta'   => $meta,
        ]);

        syncCountriesJson($pdo);
        echo json_encode([
            'success' => true, 'action' => 'created', 'sys_id' => $sys_id,
            'message' => "Country '{$name}' created.",
        ], JSON_UNESCAPED_UNICODE);

    } else {
        $row = $pdo->prepare("SELECT meta_data FROM countries WHERE sys_id = ? LIMIT 1");
        $row->execute([$sys_id]); $existing = $row->fetch();
        if (!$existing) { echo json_encode(['success'=>false,'message'=>'Not found']); exit; }

        $meta = buildMetaData($existing['meta_data'], $_SESSION['user_name'] ?? 'system');

        $pdo->prepare("
            UPDATE countries SET
                name = :name, code = :code, currency = :currency,
                currency_code = :ccy, default_rate = :rate,
                region = :region, for_package = :fp, meta_data = :meta
            WHERE sys_id = :sys_id
        ")->execute([
            ':name'   => $name,        ':code'    => $code,
            ':currency'=> $currency,   ':ccy'     => $currency_code,
            ':rate'   => $default_rate, ':region' => $region,
            ':fp'     => $for_package, ':meta'   => $meta,
            ':sys_id' => $sys_id,
        ]);

        syncCountriesJson($pdo);
        echo json_encode([
            'success' => true, 'action' => 'updated', 'sys_id' => $sys_id,
            'message' => "Country '{$name}' updated.",
        ], JSON_UNESCAPED_UNICODE);
    }

// } catch (Throwable $e) {
//     echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
// }

} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine(),
    ]);
}