<?php
session_start();
// api/masterdata/countries/sync.php
// POST { "action": "import" }  ← JSON → DB  (add missing, update existing)
// POST { "action": "export" }  ← DB  → JSON (rewrite countries.json)
header('Content-Type: application/json');
require_once('../../../server/db_connection.php');
require_once('../../../server/masterdata-id-generator.php');
require_once('../../../server/generate_meta_data.php');

$input  = json_decode(file_get_contents('php://input'), true) ?: [];
$action = trim($input['action'] ?? 'import');

$jsonPath = __DIR__ . '/../../countries.json';

try {
    if ($action === 'export') {
        // ── EXPORT: DB → JSON ─────────────────────────────────────────
        $rows = $pdo->query("
            SELECT id, sys_id, name, code, currency, currency_code, default_rate, region, cities
            FROM countries
            WHERE status = 'active'
            ORDER BY name ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $countriesOut = [];
        $citiesOut    = [];
        $cityCounter  = 1;

        foreach ($rows as $i => $row) {
            $countriesOut[] = [
                'id'            => $i + 1,
                'name'          => $row['name'],
                'code'          => $row['code'],
                'currency'      => $row['currency'],
                'currency_code' => $row['currency_code'],
                'default_rate'  => (float)$row['default_rate'],
                'region'        => $row['region'],
            ];

            $cities = json_decode($row['cities'] ?? '[]', true) ?: [];
            foreach ($cities as $city) {
                $citiesOut[] = [
                    'id'         => $cityCounter++,
                    'name'       => $city['name'],
                    'country_id' => $i + 1,
                    'type'       => $city['type']       ?? [],
                    'popularity' => $city['popularity'] ?? 3,
                    'cost_level' => $city['cost_level'] ?? 'medium',
                    'visa_ease'  => $city['visa_ease']  ?? 'medium',
                ];
            }
        }

        $json = json_encode(
            ['countries' => $countriesOut, 'cities' => $citiesOut],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );

        // Atomic write: temp file → rename
        $tmp = $jsonPath . '.tmp';
        file_put_contents($tmp, $json);
        rename($tmp, $jsonPath);

        echo json_encode([
            'success'   => true,
            'action'    => 'export',
            'countries' => count($countriesOut),
            'cities'    => count($citiesOut),
            'message'   => 'countries.json updated from DB.',
        ]);

    } else {
        // ── IMPORT: JSON → DB ─────────────────────────────────────────
        if (!file_exists($jsonPath)) {
            echo json_encode(['success' => false, 'message' => 'countries.json not found']);
            exit;
        }

        $raw       = json_decode(file_get_contents($jsonPath), true);
        $countries = $raw['countries'] ?? [];
        $citiesFlat = $raw['cities']   ?? [];

        // Group cities by country_id (old JSON int)
        $citiesByCountry = [];
        foreach ($citiesFlat as $city) {
            $citiesByCountry[(int)$city['country_id']][] = $city;
        }

        $inserted = 0;
        $updated  = 0;
        $skipped  = 0;
        $log      = [];

        foreach ($countries as $idx => $country) {
            $code = strtoupper(trim($country['code']));

            // Check by code (unique business key)
            $chk = $pdo->prepare("SELECT id, sys_id, meta_data FROM countries WHERE code = ? LIMIT 1");
            $chk->execute([$code]);
            $existing = $chk->fetch(PDO::FETCH_ASSOC);

            $rawCities  = $citiesByCountry[$country['id']] ?? [];

            if ($existing) {
                // Country exists — update fields + cities
                $countrySysId = $existing['sys_id'];
                $dbId         = (int)$existing['id'];
                $metaJson     = buildMetaData($existing['meta_data'], 'system');

                $citiesJson = buildCitiesForSync($rawCities, $countrySysId, $dbId);

                $pdo->prepare("
                    UPDATE countries SET
                        name = :name, currency = :currency, currency_code = :currency_code,
                        default_rate = :default_rate, region = :region,
                        cities = :cities, meta_data = :meta_data
                    WHERE code = :code
                ")->execute([
                    ':name'          => $country['name'],
                    ':currency'      => $country['currency'],
                    ':currency_code' => $country['currency_code'],
                    ':default_rate'  => $country['default_rate'],
                    ':region'        => $country['region'],
                    ':cities'        => json_encode($citiesJson, JSON_UNESCAPED_UNICODE),
                    ':meta_data'     => $metaJson,
                    ':code'          => $code,
                ]);
                $updated++;
                $log[] = "UPDATED: {$country['name']} ({$code})";

            } else {
                // New country — insert
                $ids          = generateHierarchyIDs($pdo, 'countries');
                $countrySysId = $ids['sys_id'];
                $uuid         = $ids['uuid'];
                $metaJson     = buildMetaData(null, 'system');

                // First insert to get DB id
                $pdo->prepare("
                    INSERT INTO countries
                        (uuid, sys_id, name, code, currency, currency_code,
                         default_rate, region, cities, status, meta_data)
                    VALUES
                        (:uuid, :sys_id, :name, :code, :currency, :currency_code,
                         :default_rate, :region, '[]', 'active', :meta_data)
                ")->execute([
                    ':uuid'          => $uuid,
                    ':sys_id'        => $countrySysId,
                    ':name'          => $country['name'],
                    ':code'          => $code,
                    ':currency'      => $country['currency'],
                    ':currency_code' => $country['currency_code'],
                    ':default_rate'  => $country['default_rate'],
                    ':region'        => $country['region'],
                    ':meta_data'     => $metaJson,
                ]);
                $dbId = (int)$pdo->lastInsertId();

                // Now build cities with correct DB id
                $citiesJson = buildCitiesForSync($rawCities, $countrySysId, $dbId);
                $pdo->prepare("UPDATE countries SET cities = ? WHERE id = ?")
                    ->execute([json_encode($citiesJson, JSON_UNESCAPED_UNICODE), $dbId]);

                $inserted++;
                $log[] = "INSERTED: {$country['name']} ({$code}) → {$countrySysId}";
            }
        }
        
        echo json_encode([
            'success'  => true,
            'action'   => 'import',
            'inserted' => $inserted,
            'updated'  => $updated,
            'skipped'  => $skipped,
            'log'      => $log,
            'message'  => "Sync complete. {$inserted} inserted, {$updated} updated.",
        ]);
    }

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// ── Helper ────────────────────────────────────────────────────────────
function buildCitiesForSync(array $rawCities, string $countrySysId, int $countryDbId): array
{
    $result = [];
    foreach ($rawCities as $pos => $city) {
        $citySysId = $countrySysId . '-CTS-' . toBase36($pos + 1);
        $result[]  = [
            'id'             => $citySysId,
            'name'           => $city['name'],
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