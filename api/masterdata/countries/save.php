<?php
session_start();
// api/masterdata/countries/save.php
// POST — create or update a country (with its cities embedded)
//
// Body (JSON):
// {
//   "sys_id"        : "THR-26-CNT-01",   ← omit or null = CREATE new
//   "name"          : "Thailand",
//   "code"          : "TH",
//   "currency"      : "Baht",
//   "currency_code" : "THB",
//   "default_rate"  : 3.35,
//   "region"        : "Asia",
//   "cities" : [
//     { "name":"Bangkok", "type":["tourism","business"], "popularity":5, "cost_level":"medium", "visa_ease":"easy" },
//     { "id":"THR-26-CNT-XX-CTS-01", "name":"Bangkok", ... }  ← id present = existing city (keep sys_id)
//   ]
// }
header('Content-Type: application/json');
require_once('../../../server/db_connection.php');
require_once('../../../server/masterdata-id-generator.php');
require_once('../../../server/generate_meta_data.php');
require_once('../../../server/json-sync-helper.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) $input = $_POST;

// ── Validate required fields ──────────────────────────────────────────
$sys_id       = trim($input['sys_id']       ?? '');
$name         = trim($input['name']         ?? '');
$code         = strtoupper(trim($input['code'] ?? ''));
$currency     = trim($input['currency']     ?? '');
$currencyCode = strtoupper(trim($input['currency_code'] ?? ''));
$defaultRate  = floatval($input['default_rate'] ?? 1.0);
$region       = trim($input['region']       ?? '');
$citiesInput  = $input['cities']            ?? [];

if (!$name || !$code || !$currency || !$currencyCode || !$region) {
    echo json_encode(['success' => false, 'message' => 'name, code, currency, currency_code, region are required']);
    exit;
}

try {
    $isNew = empty($sys_id);

    if ($isNew) {
        // ── CREATE ────────────────────────────────────────────────────
        $ids          = generateHierarchyIDs($pdo, 'countries');
        $sys_id       = $ids['sys_id'];
        $uuid         = $ids['uuid'];
        $metaJson     = buildMetaData(null, $_SESSION['user_name'] ?? 'system');

        // Build cities JSON — all cities are new
        $citiesJson = buildCitiesJson($citiesInput, $sys_id, 0, null);

        $stmt = $pdo->prepare("
            INSERT INTO countries
                (uuid, sys_id, name, code, currency, currency_code,
                 default_rate, region, cities, status, meta_data)
            VALUES
                (:uuid, :sys_id, :name, :code, :currency, :currency_code,
                 :default_rate, :region, :cities, 'active', :meta_data)
        ");
        $stmt->execute([
            ':uuid'          => $uuid,
            ':sys_id'        => $sys_id,
            ':name'          => $name,
            ':code'          => $code,
            ':currency'      => $currency,
            ':currency_code' => $currencyCode,
            ':default_rate'  => $defaultRate,
            ':region'        => $region,
            ':cities'        => json_encode($citiesJson, JSON_UNESCAPED_UNICODE),
            ':meta_data'     => $metaJson,
        ]);

        $dbId = (int)$pdo->lastInsertId();

        // Now that we know the real DB id, fix the country_id inside cities JSON
        $citiesJson = buildCitiesJson($citiesInput, $sys_id, $dbId, null);
        $pdo->prepare("UPDATE countries SET cities = ? WHERE id = ?")->execute([
            json_encode($citiesJson, JSON_UNESCAPED_UNICODE),
            $dbId,
        ]);

        exportCountriesToJson($pdo, __DIR__ . '/../../../countries.json');

        echo json_encode([
            'success' => true,
            'action'  => 'created',
            'sys_id'  => $sys_id,
            'message' => "Country '{$name}' created with " . count($citiesJson) . " city/cities.",
        ]);

    } else {
        // ── UPDATE ────────────────────────────────────────────────────
        $chk = $pdo->prepare("SELECT id, meta_data FROM countries WHERE sys_id = ? LIMIT 1");
        $chk->execute([$sys_id]);
        $existing = $chk->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            echo json_encode(['success' => false, 'message' => 'Country not found']);
            exit;
        }

        $metaJson   = buildMetaData($existing['meta_data'], $_SESSION['user_name'] ?? 'system');
        $dbId       = (int)$existing['id'];
        $citiesJson = buildCitiesJson($citiesInput, $sys_id, $dbId, $sys_id);

        $stmt = $pdo->prepare("
            UPDATE countries SET
                name          = :name,
                code          = :code,
                currency      = :currency,
                currency_code = :currency_code,
                default_rate  = :default_rate,
                region        = :region,
                cities        = :cities,
                meta_data     = :meta_data
            WHERE sys_id = :sys_id
        ");
        $stmt->execute([
            ':name'          => $name,
            ':code'          => $code,
            ':currency'      => $currency,
            ':currency_code' => $currencyCode,
            ':default_rate'  => $defaultRate,
            ':region'        => $region,
            ':cities'        => json_encode($citiesJson, JSON_UNESCAPED_UNICODE),
            ':meta_data'     => $metaJson,
            ':sys_id'        => $sys_id,
        ]);

        exportCountriesToJson($pdo, __DIR__ . '/../../../countries.json');

        echo json_encode([
            'success' => true,
            'action'  => 'updated',
            'sys_id'  => $sys_id,
            'message' => "Country '{$name}' updated with " . count($citiesJson) . " city/cities.",
        ]);
    }

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// ── Helper: build cities JSON array ──────────────────────────────────
// Preserves existing city sys_ids, generates new ones for new cities.
function buildCitiesJson(array $citiesInput, string $countrySysId, int $countryDbId, ?string $existingCountrySysId): array
{
    $result = [];
    $newCityPos = 0;  // counter for new city sequence within existing set

    // Count existing cities to know where to start for new ones
    $existingCount = 0;
    foreach ($citiesInput as $city) {
        if (!empty($city['id'])) $existingCount++;
    }

    foreach ($citiesInput as $idx => $city) {
        $cityName   = trim($city['name'] ?? '');
        if (!$cityName) continue;

        if (!empty($city['id'])) {
            // Existing city — keep its sys_id as-is
            $citySysId = $city['id'];
        } else {
            // New city — generate next sys_id position
            $newCityPos++;
            $position  = $existingCount + $newCityPos;
            $citySysId = $countrySysId . '-CTS-' . toBase36($position);
        }

        $result[] = [
            'id'             => $citySysId,
            'name'           => $cityName,
            'country_id'     => $countryDbId,
            'country_sys_id' => $countrySysId,
            'type'           => $city['type']       ?? [],
            'popularity'     => (int)($city['popularity'] ?? 3),
            'cost_level'     => $city['cost_level']  ?? 'medium',
            'visa_ease'      => $city['visa_ease']   ?? 'medium',
        ];
    }

    return $result;
}