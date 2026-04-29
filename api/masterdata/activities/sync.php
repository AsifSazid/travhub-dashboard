<?php
session_start();
// api/masterdata/activities/sync.php
// POST { "action": "export" }  ← DB → activities.json
// POST { "action": "import" }  ← activities.json → DB
//
// Import supports TWO JSON formats:
//   Old format: { city_id: 3, name: "..." }
//               → city_id mapped to city_sys_id + country_sys_id via countries table
//   New format: { country_sys_id: "THR-26-CNT-01", name: "..." }
//               → country_sys_id used directly
header('Content-Type: application/json');
require_once('../../../server/db_connection.php');
require_once('../../../server/masterdata-id-generator.php');
require_once('../../../server/generate_meta_data.php');

$input  = json_decode(file_get_contents('php://input'), true) ?: [];
$action = trim($input['action'] ?? 'export');

$actJsonPath = __DIR__ . '/../../activities.json';

try {

    if ($action === 'export') {
        // ── EXPORT: DB → activities.json ──────────────────────────────
        $rows = $pdo->query("
            SELECT sys_id, country_sys_id, name, type, location,
                   start_time, end_time, duration_hours, popularity,
                   pickup_from_city, dropoff_city, itineraries,
                   inclusions, exclusions, transfers
            FROM activities
            WHERE status = 'active'
            ORDER BY country_sys_id ASC, name ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'sys_id'           => $row['sys_id'],
                'country_sys_id'   => $row['country_sys_id'],
                'name'             => $row['name'],
                'type'             => $row['type'],
                'location'         => $row['location'],
                'start_time'       => $row['start_time'],
                'end_time'         => $row['end_time'],
                'duration_hours'   => (float)($row['duration_hours'] ?? 0),
                'popularity'       => (int)($row['popularity'] ?? 3),
                'pickup_from_city' => json_decode($row['pickup_from_city'] ?? '[]', true) ?: [],
                'dropoff_city'     => json_decode($row['dropoff_city']     ?? '[]', true) ?: [],
                'itineraries'      => json_decode($row['itineraries']      ?? '[]', true) ?: [],
                'inclusions'       => json_decode($row['inclusions']       ?? '[]', true) ?: [],
                'exclusions'       => json_decode($row['exclusions']       ?? '[]', true) ?: [],
                'transfers'        => json_decode($row['transfers']        ?? '[]', true) ?: [],
            ];
        }

        $tmp = $actJsonPath . '.tmp';
        file_put_contents($tmp, json_encode(['activities' => $out], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        rename($tmp, $actJsonPath);

        echo json_encode([
            'success'    => true,
            'action'     => 'export',
            'activities' => count($out),
            'message'    => 'activities.json updated from DB.',
        ]);

    } else {
        // ── IMPORT: activities.json → DB ──────────────────────────────
        if (!file_exists($actJsonPath)) {
            echo json_encode(['success' => false, 'message' => 'activities.json not found']);
            exit;
        }

        // Build city_id (integer) → { city_sys_id, country_sys_id } map
        // Used for OLD format JSON that has city_id instead of country_sys_id
        $cityMap = [];
        $globalCity = 0;
        $dbCountries = $pdo->query("
            SELECT sys_id, cities FROM countries WHERE status = 'active' ORDER BY id ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($dbCountries as $c) {
            foreach (json_decode($c['cities'] ?? '[]', true) ?: [] as $city) {
                $globalCity++;
                $cityMap[$globalCity] = [
                    'city_sys_id'    => $city['id'],
                    'country_sys_id' => $c['sys_id'],
                ];
            }
        }

        $actData    = json_decode(file_get_contents($actJsonPath), true);
        $activities = $actData['activities'] ?? [];

        $inserted = 0; $updated = 0; $skipped = 0;
        $log = [];

        foreach ($activities as $act) {
            $name = trim($act['name'] ?? '');
            if (!$name) {
                $log[] = "SKIP (missing name): " . json_encode($act);
                $skipped++;
                continue;
            }

            // ── Resolve country_sys_id ────────────────────────────────
            // New format: country_sys_id present directly in JSON
            // Old format: city_id (int) present — map via countries table
            $countrySysId = trim($act['country_sys_id'] ?? '');

            if (!$countrySysId && isset($act['city_id'])) {
                $cityInfo     = $cityMap[(int)$act['city_id']] ?? null;
                if ($cityInfo) {
                    $countrySysId = $cityInfo['country_sys_id'];
                }
            }

            if (!$countrySysId) {
                $log[] = "SKIP (cannot resolve country): {$name}";
                $skipped++;
                continue;
            }

            // ── Check existing (unique key: name + country) ───────────
            $chk = $pdo->prepare("SELECT id, sys_id, meta_data FROM activities WHERE country_sys_id = ? AND name = ? LIMIT 1");
            $chk->execute([$countrySysId, $name]);
            $existing = $chk->fetch(PDO::FETCH_ASSOC);

            $fields = [
                ':type'             => in_array($act['type'] ?? '', ['tour','transfer','both']) ? $act['type'] : 'tour',
                ':location'         => $act['location']       ?? null,
                ':start_time'       => $act['start_time']     ?? null,
                ':end_time'         => $act['end_time']       ?? null,
                ':duration_hours'   => (float)($act['duration_hours'] ?? 0),
                ':popularity'       => (int)($act['popularity']       ?? 3),
                ':pickup_from_city' => json_encode($act['pickup_from_city'] ?? [], JSON_UNESCAPED_UNICODE),
                ':dropoff_city'     => json_encode($act['dropoff_city']     ?? [], JSON_UNESCAPED_UNICODE),
                ':itineraries'      => json_encode($act['itineraries']      ?? [], JSON_UNESCAPED_UNICODE),
                ':inclusions'       => json_encode($act['inclusions']       ?? [], JSON_UNESCAPED_UNICODE),
                ':exclusions'       => json_encode($act['exclusions']       ?? [], JSON_UNESCAPED_UNICODE),
                ':transfers'        => json_encode($act['transfers']        ?? [], JSON_UNESCAPED_UNICODE),
            ];

            if ($existing) {
                $metaJson = buildMetaData($existing['meta_data'], 'system');
                $pdo->prepare("
                    UPDATE activities SET
                        type = :type, location = :location,
                        start_time = :start_time, end_time = :end_time,
                        duration_hours = :duration_hours, popularity = :popularity,
                        pickup_from_city = :pickup_from_city, dropoff_city = :dropoff_city,
                        itineraries = :itineraries, inclusions = :inclusions,
                        exclusions = :exclusions, transfers = :transfers,
                        meta_data = :meta_data
                    WHERE id = :id
                ")->execute(array_merge($fields, [':meta_data' => $metaJson, ':id' => $existing['id']]));
                $updated++;
                $log[] = "UPDATED: {$name} ({$existing['sys_id']})";

            } else {
                $ids      = generateHierarchyIDs($pdo, 'activities', $countrySysId);
                $metaJson = buildMetaData(null, 'system');
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
                ")->execute(array_merge($fields, [
                    ':uuid'           => $ids['uuid'],
                    ':sys_id'         => $ids['sys_id'],
                    ':country_sys_id' => $countrySysId,
                    ':name'           => $name,
                    ':meta_data'      => $metaJson,
                ]));
                $inserted++;
                $log[] = "INSERTED: {$name} → {$ids['sys_id']}";
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
        ]);
    }

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}