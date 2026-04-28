<?php
/**
 * TravHub — JSON Sync Helper
 * server/json-sync-helper.php
 *
 * Call after any successful DB write to keep the JSON files in sync.
 * Requires $pdo to already be available in scope.
 *
 * Usage:
 *   require_once('../../../server/json-sync-helper.php');
 *   // ... do DB insert/update ...
 *   exportCountriesToJson($pdo, __DIR__ . '/../../../countries.json');
 *   exportActivitiesToJson($pdo, __DIR__ . '/../../../activities.json');
 */

// ── Export countries table → countries.json ──────────────────────────
function exportCountriesToJson(PDO $pdo, string $jsonPath): bool
{
    try {
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

        // Atomic write
        $tmp = $jsonPath . '.tmp';
        file_put_contents($tmp, $json);
        return rename($tmp, $jsonPath);

    } catch (Throwable $e) {
        error_log('exportCountriesToJson failed: ' . $e->getMessage());
        return false;
    }
}

// ── Export activities table → activities.json ────────────────────────
function exportActivitiesToJson(PDO $pdo, string $jsonPath): bool
{
    try {
        $rows = $pdo->query("
            SELECT sys_id, city_sys_id, country_sys_id, name, type, price_range, duration_hours, popularity
            FROM activities
            WHERE status = 'active'
            ORDER BY country_sys_id ASC, city_sys_id ASC, id ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Build city_sys_id → sequential int map
        $cityIdMap  = [];
        $counter    = 0;
        $dbCountries = $pdo->query("
            SELECT cities FROM countries WHERE status = 'active' ORDER BY id ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($dbCountries as $c) {
            foreach (json_decode($c['cities'] ?? '[]', true) ?: [] as $city) {
                $counter++;
                $cityIdMap[$city['id']] = $counter;
            }
        }

        $out = [];
        foreach ($rows as $i => $row) {
            $out[] = [
                'id'             => $i + 1,
                'city_id'        => $cityIdMap[$row['city_sys_id']] ?? 0,
                'name'           => $row['name'],
                'type'           => $row['type'],
                'price_range'    => $row['price_range'],
                'duration_hours' => (float)$row['duration_hours'],
                'popularity'     => (int)$row['popularity'],
            ];
        }

        $json = json_encode(
            ['activities' => $out],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );

        $tmp = $jsonPath . '.tmp';
        file_put_contents($tmp, $json);
        return rename($tmp, $jsonPath);

    } catch (Throwable $e) {
        error_log('exportActivitiesToJson failed: ' . $e->getMessage());
        return false;
    }
}