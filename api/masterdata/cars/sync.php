<?php
session_start();
// api/masterdata/cars/sync.php
// POST { "action": "export" }  ← DB → cars.json
// POST { "action": "import" }  ← cars.json → DB
//
// Import supports TWO JSON formats:
//   Old/Simple format: { id:1, country_id:1, name:"Toyota Hiace", car_type:"van", seat:7, is_luggage:"yes", max_luggage:"20kg" }
//                      → country_id (integer sequence from countries table) mapped to country_sys_id
//   New format:        { country_sys_id:"THR-26-CNT-01", name:"Toyota Hiace", type:"van", seats:7, ... }
//                      → country_sys_id used directly
//
// Unique key for insert vs update: name + country_sys_id
header('Content-Type: application/json');
require_once('../../../server/db_connection.php');
require_once('../../../server/masterdata-id-generator.php');
require_once('../../../server/generate_meta_data.php');

$input  = json_decode(file_get_contents('php://input'), true) ?: [];
$action = trim($input['action'] ?? 'export');

$carsJsonPath = __DIR__ . '/../../cars.json';

try {

    if ($action === 'export') {
        // ── EXPORT: DB → cars.json ────────────────────────────────────
        $rows = $pdo->query("
            SELECT sys_id, country_sys_id, name, type, seats, has_luggage, max_luggage
            FROM cars
            WHERE status = 'active'
            ORDER BY country_sys_id ASC, name ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Build country_sys_id → sequential int map (for human-friendly JSON)
        $countrySeqMap = [];
        $seq = 0;
        $dbCountries = $pdo->query("SELECT sys_id FROM countries WHERE status='active' ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($dbCountries as $c) {
            $seq++;
            $countrySeqMap[$c['sys_id']] = $seq;
        }

        $out = [];
        foreach ($rows as $i => $row) {
            $out[] = [
                'id'           => $i + 1,
                'country_id'   => $countrySeqMap[$row['country_sys_id']] ?? 0,
                'name'         => $row['name'],
                'car_type'     => $row['type'],
                'seat'         => (int)$row['seats'],
                'is_luggage'   => $row['has_luggage'] ? 'yes' : 'no',
                'max_luggage'  => $row['max_luggage'] ?? '',
            ];
        }

        $tmp = $carsJsonPath . '.tmp';
        file_put_contents($tmp, json_encode(['cars' => $out], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        rename($tmp, $carsJsonPath);

        echo json_encode([
            'success' => true,
            'action'  => 'export',
            'cars'    => count($out),
            'message' => 'cars.json updated from DB.',
        ]);

    } else {
        // ── IMPORT: cars.json → DB ────────────────────────────────────
        if (!file_exists($carsJsonPath)) {
            echo json_encode(['success' => false, 'message' => 'cars.json not found']);
            exit;
        }

        // Build country_id (int sequence) → country_sys_id map
        // Matches the same sequence used during export
        $countryMap = [];
        $seq = 0;
        $dbCountries = $pdo->query("SELECT sys_id FROM countries WHERE status='active' ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
        if (empty($dbCountries)) {
            echo json_encode(['success' => false, 'message' => 'No countries in DB. Run countries sync first.']);
            exit;
        }
        foreach ($dbCountries as $c) {
            $seq++;
            $countryMap[$seq] = $c['sys_id'];
        }

        $validTypes = ['sedan','van','suv','minibus','microbus','coaster','bus','other'];

        $carsData = json_decode(file_get_contents($carsJsonPath), true);
        $cars     = $carsData['cars'] ?? [];

        $inserted = 0; $updated = 0; $skipped = 0;
        $log = [];

        foreach ($cars as $car) {
            $name = trim($car['name'] ?? '');
            if (!$name) {
                $log[] = "SKIP (missing name): " . json_encode($car);
                $skipped++;
                continue;
            }

            // ── Resolve country_sys_id ────────────────────────────────
            // New format: country_sys_id present directly
            // Old format: country_id (int) → mapped via sequence
            $countrySysId = trim($car['country_sys_id'] ?? '');

            if (!$countrySysId && isset($car['country_id'])) {
                $countrySysId = $countryMap[(int)$car['country_id']] ?? '';
            }

            if (!$countrySysId) {
                $log[] = "SKIP (cannot resolve country): {$name}";
                $skipped++;
                continue;
            }

            // ── Normalize fields ─────────────────────────────────────
            // Support both old field names (car_type/seat/is_luggage) and new (type/seats/has_luggage)
            $type       = trim($car['car_type'] ?? $car['type'] ?? 'sedan');
            if (!in_array($type, $validTypes)) $type = 'other';

            $seats      = (int)($car['seat'] ?? $car['seats'] ?? 4);
            $hasLuggage = isset($car['is_luggage'])
                ? ($car['is_luggage'] === 'yes' ? 1 : 0)
                : (!empty($car['has_luggage']) ? 1 : 0);
            $maxLuggage = trim($car['max_luggage'] ?? '') ?: null;

            // ── Check existing (unique key: name + country_sys_id) ────
            $chk = $pdo->prepare("SELECT id, sys_id, meta_data FROM cars WHERE country_sys_id = ? AND name = ? LIMIT 1");
            $chk->execute([$countrySysId, $name]);
            $existing = $chk->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $metaJson = buildMetaData($existing['meta_data'], 'system');
                $pdo->prepare("
                    UPDATE cars SET
                        type = :type, seats = :seats,
                        has_luggage = :has_luggage, max_luggage = :max_luggage,
                        meta_data = :meta_data
                    WHERE id = :id
                ")->execute([
                    ':type'        => $type,
                    ':seats'       => $seats,
                    ':has_luggage' => $hasLuggage,
                    ':max_luggage' => $maxLuggage,
                    ':meta_data'   => $metaJson,
                    ':id'          => $existing['id'],
                ]);
                $updated++;
                $log[] = "UPDATED: {$name} ({$existing['sys_id']})";

            } else {
                $ids      = generateHierarchyIDs($pdo, 'cars', $countrySysId);
                $metaJson = buildMetaData(null, 'system');
                $pdo->prepare("
                    INSERT INTO cars
                        (uuid, sys_id, country_sys_id, name, type, seats, has_luggage, max_luggage, status, meta_data)
                    VALUES
                        (:uuid, :sys_id, :country_sys_id, :name, :type, :seats, :has_luggage, :max_luggage, 'active', :meta_data)
                ")->execute([
                    ':uuid'           => $ids['uuid'],
                    ':sys_id'         => $ids['sys_id'],
                    ':country_sys_id' => $countrySysId,
                    ':name'           => $name,
                    ':type'           => $type,
                    ':seats'          => $seats,
                    ':has_luggage'    => $hasLuggage,
                    ':max_luggage'    => $maxLuggage,
                    ':meta_data'      => $metaJson,
                ]);
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