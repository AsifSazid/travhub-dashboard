<?php
/**
 * TravHub — Seed: Activities
 * ════════════════════════════
 * Reads api/activities.json (456 activities across 225 cities).
 * Maps each activity's city_id (old JSON int 1-225) to the real
 * city sys_id stored in the countries.cities JSON column in DB.
 * Inserts city-by-city so it is safe to run in chunks.
 *
 * Run from project root:
 *   php scripts/seed-activities.php                    ← all cities
 *   php scripts/seed-activities.php --dry-run          ← preview
 *   php scripts/seed-activities.php --chunk=20         ← 20 cities at a time
 *   php scripts/seed-activities.php --chunk=20 --offset=40
 *   php scripts/seed-activities.php --force            ← skip existing, insert new
 *
 * REQUIRES: seed-countries.php must have been run first.
 */

declare(strict_types=1);
date_default_timezone_set('Asia/Dhaka');

// ── Bootstrap ─────────────────────────────────────────────────────────
$root = dirname(__DIR__);
require_once $root . '/server/db_connection.php';
require_once $root . '/server/masterdata-id-generator.php';
require_once $root . '/server/generate_meta_data.php';

// ── CLI args ──────────────────────────────────────────────────────────
$opts       = getopt('', ['chunk:', 'offset:', 'dry-run', 'force']);
$chunk      = isset($opts['chunk'])  ? max(1, (int)$opts['chunk']) : 0;
$offset     = isset($opts['offset']) ? max(0, (int)$opts['offset']) : 0;
$dryRun     = array_key_exists('dry-run', $opts);
$force      = array_key_exists('force',   $opts);

// ── Load activities JSON ──────────────────────────────────────────────
$actPath = $root . '/api/activities.json';
if (!file_exists($actPath)) die("❌  Not found: {$actPath}\n");

$actData    = json_decode(file_get_contents($actPath), true);
$activities = $actData['activities'] ?? [];
if (empty($activities)) die("❌  No activities in JSON.\n");

// Group by city_id (the old JSON int 1-225)
$byCity = [];
foreach ($activities as $act) {
    $byCity[(int)$act['city_id']][] = $act;
}

// ── Build city_id → sys_id map from the countries table ──────────────
// countries.cities is a JSON column with all city objects for each country.
// We read them in insertion order (id ASC) and assign sequential
// global city numbers 1, 2, 3… that match the original JSON city ids.
line("  Building city map from DB…");

$cityMap    = [];   // [old_json_city_id => ['city_sys_id' => …, 'country_sys_id' => …, 'name' => …]]
$globalCity = 0;    // increments 1–225 matching the JSON city id order

$rows = $pdo->query("SELECT id, sys_id, cities FROM countries WHERE status = 'active' ORDER BY id ASC")
             ->fetchAll(PDO::FETCH_ASSOC);

if (empty($rows)) die("❌  No countries in DB. Run seed-countries.php first.\n");

foreach ($rows as $country) {
    $cities = json_decode($country['cities'] ?? '[]', true) ?: [];
    foreach ($cities as $city) {
        $globalCity++;
        $cityMap[$globalCity] = [
            'city_sys_id'    => $city['id'],          // THR-26-CNT-01-CTS-01
            'country_sys_id' => $country['sys_id'],   // THR-26-CNT-01
            'name'           => $city['name'],
        ];
    }
}

line("  Mapped {$globalCity} cities.");
if ($globalCity === 0) die("❌  City map is empty — check countries.cities column.\n");

// ── Guard ─────────────────────────────────────────────────────────────
if (!$dryRun && !$force) {
    $existing = (int)$pdo->query("SELECT COUNT(*) FROM activities")->fetchColumn();
    if ($existing > 0) {
        die("⚠️  Table already has {$existing} rows. Use --force to skip existing & insert new.\n");
    }
}

// ── Decide which city IDs to process ─────────────────────────────────
$allCityIds = array_keys($byCity);
sort($allCityIds);

$slice = $chunk > 0
    ? array_slice($allCityIds, $offset, $chunk)
    : array_slice($allCityIds, $offset);

$totalCities = count($slice);
$totalActs   = 0;
foreach ($slice as $cid) $totalActs += count($byCity[$cid] ?? []);

line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
line("  TravHub Activity Seed");
line("  JSON     : {$actPath}");
line("  Source   : " . count($activities) . " activities across " . count($byCity) . " cities");
line("  Offset   : {$offset} | Chunk: " . ($chunk ?: 'ALL') . " | Processing: {$totalCities} cities / {$totalActs} activities");
line($dryRun ? "  Mode     : DRY RUN — no writes" : "  Mode     : LIVE");
line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

$insertedTotal = 0;
$skippedTotal  = 0;
$failedTotal   = 0;

foreach ($slice as $jsonCityId) {
    $cityActivities = $byCity[$jsonCityId] ?? [];
    $cityInfo       = $cityMap[$jsonCityId] ?? null;

    if (!$cityInfo) {
        line("  ⚠️  city_id {$jsonCityId} has no DB mapping — skipping " . count($cityActivities) . " activities");
        $skippedTotal += count($cityActivities);
        continue;
    }

    $citySysId    = $cityInfo['city_sys_id'];
    $countrySysId = $cityInfo['country_sys_id'];
    $cityName     = $cityInfo['name'];

    line("");
    line("  📍 city_id {$jsonCityId} — {$cityName} ({$citySysId})");

    $cityInserted = 0;
    $citySkipped  = 0;

    foreach ($cityActivities as $act) {
        try {
            // Generate activity sys_id using the generator
            // (it queries DB for the last ACT under this city sys_id and increments)
            $ids      = generateHierarchyIDs($pdo, 'activities', $citySysId);
            $actSysId = $ids['sys_id'];   // THR-26-CNT-01-CTS-01-ACT-01
            $actUuid  = $ids['uuid'];
            $metaData = buildMetaData(null, 'system');

            if ($dryRun) {
                line(sprintf("     ↳ %-40s  %s  [%s | %s | %sh | pop:%d]",
                    $act['name'], $actSysId,
                    $act['type'], $act['price_range'],
                    $act['duration_hours'], $act['popularity']
                ));
                $cityInserted++;
                continue;
            }

            // Skip if already exists (--force mode)
            $chk = $pdo->prepare("SELECT id FROM activities WHERE sys_id = ? LIMIT 1");
            $chk->execute([$actSysId]);
            if ($chk->fetchColumn()) {
                $citySkipped++;
                continue;
            }

            $stmt = $pdo->prepare("
                INSERT INTO activities
                    (uuid, sys_id, city_sys_id, country_sys_id,
                     name, type, price_range, duration_hours, popularity,
                     status, meta_data)
                VALUES
                    (:uuid, :sys_id, :city_sys_id, :country_sys_id,
                     :name, :type, :price_range, :duration_hours, :popularity,
                     'active', :meta_data)
            ");
            $stmt->execute([
                ':uuid'           => $actUuid,
                ':sys_id'         => $actSysId,
                ':city_sys_id'    => $citySysId,
                ':country_sys_id' => $countrySysId,
                ':name'           => $act['name'],
                ':type'           => $act['type'],
                ':price_range'    => $act['price_range'],
                ':duration_hours' => (float)$act['duration_hours'],
                ':popularity'     => (int)$act['popularity'],
                ':meta_data'      => $metaData,
            ]);

            line(sprintf("     ✅ %-40s  %s", $act['name'], $actSysId));
            $cityInserted++;

        } catch (Throwable $e) {
            line("     ❌ {$act['name']}: " . $e->getMessage());
            $failedTotal++;
        }
    }

    line(sprintf("     → %d inserted, %d skipped", $cityInserted, $citySkipped));
    $insertedTotal += $cityInserted;
    $skippedTotal  += $citySkipped;
}

line("");
line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
line("  Inserted : {$insertedTotal}  |  Skipped: {$skippedTotal}  |  Failed: {$failedTotal}");
line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
if (!$dryRun && $insertedTotal > 0) {
    line("  Verify with:");
    line("    SELECT COUNT(*) FROM activities;          -- should be 456");
    line("    SELECT city_sys_id, COUNT(*) FROM activities GROUP BY city_sys_id LIMIT 5;");
}

function line(string $s = ''): void { echo $s . PHP_EOL; }