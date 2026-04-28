<?php
session_start();
// api/masterdata/activities/sync.php
// POST { "action": "import" }  ← activities.json → DB
// POST { "action": "export" }  ← DB → activities.json
header('Content-Type: application/json');
require_once('../../../server/db_connection.php');
require_once('../../../server/masterdata-id-generator.php');
require_once('../../../server/generate_meta_data.php');

$input  = json_decode(file_get_contents('php://input'), true) ?: [];
$action = trim($input['action'] ?? 'import');

$actJsonPath     = __DIR__ . '/../../activities.json';
$countryJsonPath = __DIR__ . '/../../countries.json';

try {

    if ($action === 'export') {
        // ── EXPORT: DB → activities.json ──────────────────────────────
        $rows = $pdo->query("
            SELECT sys_id, city_sys_id, country_sys_id, name, type, price_range, duration_hours, popularity
            FROM activities
            WHERE status = 'active'
            ORDER BY country_sys_id ASC, city_sys_id ASC, id ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Build a city_sys_id → sequential int map so the JSON keeps integer city_ids
        $cityIdMap = [];
        $counter   = 0;
        $allCountries = $pdo->query("
            SELECT sys_id, cities FROM countries WHERE status='active' ORDER BY id ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($allCountries as $c) {
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
            echo json_encode(['success'=>false,'message'=>'activities.json not found']); exit;
        }
        if (!file_exists($countryJsonPath)) {
            echo json_encode(['success'=>false,'message'=>'countries.json not found (needed for city mapping)']); exit;
        }

        // Build city_id (JSON int) → city_sys_id map from DB
        $cityMap = [];
        $globalCity = 0;
        $dbCountries = $pdo->query("
            SELECT sys_id, cities FROM countries WHERE status='active' ORDER BY id ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
        if (empty($dbCountries)) {
            echo json_encode(['success'=>false,'message'=>'No countries in DB. Run countries sync first.']); exit;
        }
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
            $cityInfo = $cityMap[(int)$act['city_id']] ?? null;
            if (!$cityInfo) {
                $log[]    = "SKIP (no city mapping): city_id {$act['city_id']} — {$act['name']}";
                $skipped++;
                continue;
            }

            $citySysId    = $cityInfo['city_sys_id'];
            $countrySysId = $cityInfo['country_sys_id'];

            // Check if activity already exists by name + city (name is unique per city)
            $chk = $pdo->prepare("SELECT id, sys_id, meta_data FROM activities WHERE city_sys_id = ? AND name = ? LIMIT 1");
            $chk->execute([$citySysId, $act['name']]);
            $existing = $chk->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                // Update fields
                $metaJson = buildMetaData($existing['meta_data'], 'system');
                $pdo->prepare("
                    UPDATE activities SET
                        type=:type, price_range=:price_range,
                        duration_hours=:dur, popularity=:pop, meta_data=:meta
                    WHERE id=:id
                ")->execute([
                    ':type'  => $act['type'],
                    ':price_range' => $act['price_range'],
                    ':dur'   => (float)$act['duration_hours'],
                    ':pop'   => (int)$act['popularity'],
                    ':meta'  => $metaJson,
                    ':id'    => $existing['id'],
                ]);
                $updated++;
                $log[] = "UPDATED: {$act['name']} ({$existing['sys_id']})";

            } else {
                // Insert new
                $ids      = generateHierarchyIDs($pdo, 'activities', $citySysId);
                $metaJson = buildMetaData(null, 'system');
                $pdo->prepare("
                    INSERT INTO activities
                        (uuid, sys_id, city_sys_id, country_sys_id, name, type,
                         price_range, duration_hours, popularity, status, meta_data)
                    VALUES
                        (:uuid,:sys_id,:city_sys_id,:country_sys_id,:name,:type,
                         :price_range,:dur,:pop,'active',:meta)
                ")->execute([
                    ':uuid'           => $ids['uuid'],
                    ':sys_id'         => $ids['sys_id'],
                    ':city_sys_id'    => $citySysId,
                    ':country_sys_id' => $countrySysId,
                    ':name'           => $act['name'],
                    ':type'           => $act['type'],
                    ':price_range'    => $act['price_range'],
                    ':dur'            => (float)$act['duration_hours'],
                    ':pop'            => (int)$act['popularity'],
                    ':meta'           => $metaJson,
                ]);
                $inserted++;
                $log[] = "INSERTED: {$act['name']} → {$ids['sys_id']}";
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
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}