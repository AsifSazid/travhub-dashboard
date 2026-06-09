<?php
session_start();
/**
 * api/masterdata/countries/sync.php  (Gen-3)
 * ═══════════════════════════════════════════
 * POST { "action": "import" }  ← JSON → DB  (add missing, update existing)
 * POST { "action": "export" }  ← DB  → JSON (rewrite countries.json)
 *
 * Also accepts JSON body directly for upload-based import:
 * POST { "action": "import", "countries": [...], "cities": [...] }
 *
 * Changes from Gen-2:
 *   • masterdata-id-generator.php → id_generator.php
 *   • generateHierarchyIDs()      → generateChildIDs($pdo, 'countries', 'ROOT')
 *   • JSON path resolved via server-name.txt (same pattern as json_sync.php)
 *   • cities column: 'sys_id' field key (was 'id') for consistency
 */

header('Content-Type: application/json');

require_once '../../../server/db_connection.php';
require_once '../../../server/id_generator.php';
require_once '../../../server/generate_meta_data.php';

$input  = json_decode(file_get_contents('php://input'), true) ?: [];
$action = trim($input['action'] ?? 'import');

// ── Resolve countries.json path (same logic as json_sync.php) ─────────
$serverName = trim(file_get_contents(__DIR__ . '/../../../server-name.txt') ?: '');
$docRoot    = rtrim($_SERVER['DOCUMENT_ROOT'], '/');
$jsonPath   = $serverName
    ? "{$docRoot}/{$serverName}/api/countries.json"
    : "{$docRoot}/api/countries.json";

try {

    // ══════════════════════════════════════════════════════════════════
    // EXPORT: DB → JSON
    // ══════════════════════════════════════════════════════════════════
    if ($action === 'export') {

        $rows = $pdo->query("
            SELECT id, sys_id, name, code, currency, currency_code,
                   default_rate, region, cities
            FROM   countries
            WHERE  status = 'active'
            ORDER  BY name ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $countriesOut = [];
        $citiesOut    = [];
        $cityCounter  = 1;

        foreach ($rows as $i => $row) {
            $countriesOut[] = [
                'id'            => $i + 1,
                'sys_id'        => $row['sys_id'],
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
                    'sys_id'     => $city['sys_id']     ?? ($row['sys_id'] . '-CTS-01'),
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

        // Ensure directory exists
        $dir = dirname($jsonPath);
        if (!is_dir($dir)) mkdir($dir, 0775, true);

        // Atomic write
        $tmp = $jsonPath . '.tmp';
        if (file_put_contents($tmp, $json) === false) {
            echo json_encode(['success' => false, 'message' => "Cannot write to: {$jsonPath}"]); exit;
        }
        rename($tmp, $jsonPath);

        echo json_encode([
            'success'   => true,
            'action'    => 'export',
            'countries' => count($countriesOut),
            'cities'    => count($citiesOut),
            'path'      => $jsonPath,
            'message'   => 'countries.json updated from DB.',
        ], JSON_UNESCAPED_UNICODE);

    // ══════════════════════════════════════════════════════════════════
    // IMPORT: JSON → DB
    // ══════════════════════════════════════════════════════════════════
    } else {

        // Source 1: body payload (upload from browser)
        if (!empty($input['countries'])) {
            $countries  = $input['countries'];
            $citiesFlat = $input['cities'] ?? [];

        // Source 2: server-side countries.json file
        } else {
            if (!file_exists($jsonPath)) {
                echo json_encode([
                    'success' => false,
                    'message' => "countries.json not found at: {$jsonPath}. "
                               . "Pass countries array in body or place file at that path.",
                ]); exit;
            }
            $raw        = json_decode(file_get_contents($jsonPath), true) ?: [];
            $countries  = $raw['countries'] ?? [];
            $citiesFlat = $raw['cities']    ?? [];
        }

        if (empty($countries)) {
            echo json_encode(['success' => false, 'message' => 'No countries data to import']); exit;
        }

        // Group cities by country_id (numeric index in JSON)
        $citiesByCountry = [];
        foreach ($citiesFlat as $city) {
            $cid = (int)($city['country_id'] ?? 0);
            if ($cid) $citiesByCountry[$cid][] = $city;
        }

        $inserted = 0;
        $updated  = 0;
        $skipped  = 0;
        $log      = [];

        foreach ($countries as $country) {
            $name          = trim($country['name']          ?? '');
            $code          = strtoupper(trim($country['code'] ?? ''));
            $currency      = trim($country['currency']      ?? '');
            $currency_code = strtoupper(trim($country['currency_code'] ?? ''));
            $default_rate  = max(0, (float)($country['default_rate'] ?? 1));
            $region        = trim($country['region']        ?? '');

            // Skip invalid rows
            if (!$name || !$code) { $skipped++; continue; }

            // Cities for this country (keyed by numeric id in JSON)
            $jsonCountryId = (int)($country['id'] ?? 0);
            $rawCities     = $citiesByCountry[$jsonCountryId] ?? [];

            // ── Check if country already exists (by code) ─────────────
            $chk = $pdo->prepare("SELECT id, sys_id, meta_data FROM countries WHERE code = ? LIMIT 1");
            $chk->execute([$code]);
            $existing = $chk->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                // ── UPDATE ────────────────────────────────────────────
                $countrySysId = $existing['sys_id'];
                $dbId         = (int)$existing['id'];
                $meta         = buildMetaData($existing['meta_data'], 'system');
                $citiesJson   = buildCitiesForSync($rawCities, $countrySysId, $dbId);

                $pdo->prepare("
                    UPDATE countries SET
                        name          = :name,
                        currency      = :currency,
                        currency_code = :currency_code,
                        default_rate  = :default_rate,
                        region        = :region,
                        cities        = :cities,
                        meta_data     = :meta
                    WHERE code = :code
                ")->execute([
                    ':name'          => $name,
                    ':currency'      => $currency,
                    ':currency_code' => $currency_code,
                    ':default_rate'  => $default_rate,
                    ':region'        => $region,
                    ':cities'        => json_encode($citiesJson, JSON_UNESCAPED_UNICODE),
                    ':meta'          => $meta,
                    ':code'          => $code,
                ]);

                $updated++;
                $log[] = "UPDATED: {$name} ({$code}) → {$countrySysId}";

            } else {
                // ── INSERT ────────────────────────────────────────────
                // Gen-3 fix: generateChildIDs with 'ROOT' as parent
                $ids          = generateChildIDs($pdo, 'countries', 'ROOT');
                $countrySysId = $ids['sys_id'];
                $uuid         = $ids['uuid'];
                $meta         = buildMetaData(null, 'system');

                // Insert first (need lastInsertId for city back-reference)
                $pdo->prepare("
                    INSERT INTO countries
                        (uuid, sys_id, name, code, currency, currency_code,
                         default_rate, region, cities, status, meta_data)
                    VALUES
                        (:uuid, :sys_id, :name, :code, :currency, :currency_code,
                         :default_rate, :region, '[]', 'active', :meta)
                ")->execute([
                    ':uuid'          => $uuid,
                    ':sys_id'        => $countrySysId,
                    ':name'          => $name,
                    ':code'          => $code,
                    ':currency'      => $currency,
                    ':currency_code' => $currency_code,
                    ':default_rate'  => $default_rate,
                    ':region'        => $region,
                    ':meta'          => $meta,
                ]);

                $dbId       = (int)$pdo->lastInsertId();
                $citiesJson = buildCitiesForSync($rawCities, $countrySysId, $dbId);

                // Update cities column now we have the real DB id
                $pdo->prepare("UPDATE countries SET cities = ? WHERE id = ?")
                    ->execute([json_encode($citiesJson, JSON_UNESCAPED_UNICODE), $dbId]);

                $inserted++;
                $log[] = "INSERTED: {$name} ({$code}) → {$countrySysId}";
            }
        }

        echo json_encode([
            'success'  => true,
            'action'   => 'import',
            'inserted' => $inserted,
            'updated'  => $updated,
            'skipped'  => $skipped,
            'log'      => $log,
            'message'  => "Sync complete. {$inserted} inserted, {$updated} updated, {$skipped} skipped.",
        ], JSON_UNESCAPED_UNICODE);
    }

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// ── Helper: build cities array for the cities JSON column ─────────────
/**
 * Gen-3: city key is 'sys_id' (not 'id').
 * sys_id format: {country_sys_id}-CTS-{base36_pos}
 * e.g. THR-26-CNT-01-CTS-01
 */
function buildCitiesForSync(array $rawCities, string $countrySysId, int $countryDbId): array
{
    $result = [];
    foreach ($rawCities as $pos => $city) {
        $citySysId = $countrySysId . '-CTS-' . toBase36($pos + 1);
        $result[]  = [
            'sys_id'         => $citySysId,
            'name'           => trim($city['name'] ?? ''),
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