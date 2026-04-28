<?php
/**
 * TravHub — Seed: Countries + Cities
 * ════════════════════════════════════
 * Reads api/countries.json (75 countries, 225 cities, 3 per country).
 * Generates hierarchical sys_ids, embeds cities as JSON inside each
 * country row, and inserts into the `countries` table.
 *
 * Run from project root:
 *   php scripts/seed-countries.php              ← seed all
 *   php scripts/seed-countries.php --dry-run    ← preview only
 *   php scripts/seed-countries.php --chunk=10   ← 10 at a time
 *   php scripts/seed-countries.php --chunk=10 --offset=20
 *   php scripts/seed-countries.php --force      ← skip existing, insert new
 *
 * MUST run BEFORE seed-activities.php
 */

declare(strict_types=1);
date_default_timezone_set('Asia/Dhaka');

// ── Bootstrap ─────────────────────────────────────────────────────────
$root = dirname(__DIR__);
require_once $root . '../server/db_connection.php';          // provides $pdo
require_once $root . '../server/masterdata-id-generator.php'; // provides generateHierarchyIDs(), toBase36()
require_once $root . '../server/generate_meta_data.php';     // provides buildMetaData()

// ── CLI args ──────────────────────────────────────────────────────────
$opts   = getopt('', ['chunk:', 'offset:', 'dry-run', 'force']);
$chunk  = isset($opts['chunk'])  ? max(1, (int)$opts['chunk']) : 0;   // 0 = all
$offset = isset($opts['offset']) ? max(0, (int)$opts['offset']) : 0;
$dryRun = array_key_exists('dry-run', $opts);
$force  = array_key_exists('force',   $opts);

// ── Load JSON ─────────────────────────────────────────────────────────
$jsonPath = $root . '/api/countries.json';
if (!file_exists($jsonPath)) die("❌  Not found: {$jsonPath}\n");

$raw       = json_decode(file_get_contents($jsonPath), true);
$countries = $raw['countries'] ?? [];   // 75 items, each has id, name, code, currency, currency_code, default_rate, region
$citiesFlat = $raw['cities']   ?? [];   // 225 items, each has id(1-225), name, country_id, type[], popularity, cost_level, visa_ease

// Group cities by their country_id (the old sequential int from JSON)
$citiesByCountry = [];
foreach ($citiesFlat as $city) {
    $citiesByCountry[(int)$city['country_id']][] = $city;
}

if (empty($countries)) die("❌  No countries in JSON.\n");

// ── Guard: already seeded ─────────────────────────────────────────────
if (!$dryRun && !$force) {
    $existing = (int)$pdo->query("SELECT COUNT(*) FROM countries")->fetchColumn();
    if ($existing > 0) {
        die("⚠️  Table already has {$existing} rows. Use --force to skip existing & insert new.\n");
    }
}

// ── Slice ─────────────────────────────────────────────────────────────
$slice = $chunk > 0
    ? array_slice($countries, $offset, $chunk)
    : array_slice($countries, $offset);

$total = count($slice);

line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
line("  TravHub Country Seed");
line("  JSON     : {$jsonPath}");
line("  Source   : " . count($countries) . " countries, " . count($citiesFlat) . " cities");
line("  Offset   : {$offset} | Chunk: " . ($chunk ?: 'ALL') . " | Processing: {$total}");
line($dryRun ? "  Mode     : DRY RUN — no writes" : "  Mode     : LIVE");
line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

$inserted = 0;
$skipped  = 0;
$failed   = 0;

foreach ($slice as $i => $country) {
    $rowNum = $offset + $i + 1;   // 1-based display number

    try {
        // ── Generate country IDs ──────────────────────────────────────
        $ids          = generateHierarchyIDs($pdo, 'countries');
        $countrySysId = $ids['sys_id'];   // THR-26-CNT-01
        $countryUuid  = $ids['uuid'];

        // ── Build cities JSON ─────────────────────────────────────────
        // Each city gets its own sys_id: {countrySysId}-CTS-{position}
        // We use the JSON array position (1-based) as the city sequence.
        $rawCities  = $citiesByCountry[(int)$country['id']] ?? [];
        $citiesJson = [];
        foreach ($rawCities as $pos => $city) {
            $citySysId    = $countrySysId . '-CTS-' . toBase36($pos + 1);
            $citiesJson[] = [
                'id'             => $citySysId,
                'name'           => $city['name'],
                'country_id'     => $rowNum,        // DB auto-increment id of this country row
                'country_sys_id' => $countrySysId,
                'type'           => $city['type']       ?? [],
                'popularity'     => (int)($city['popularity'] ?? 3),
                'cost_level'     => $city['cost_level']  ?? 'medium',
                'visa_ease'      => $city['visa_ease']   ?? 'medium',
            ];
        }

        $metaData = buildMetaData(null, 'system');

        // ── Dry run: just print ───────────────────────────────────────
        if ($dryRun) {
            line(sprintf("  [%3d] %-28s  %s  (%d cities)",
                $rowNum, $country['name'], $countrySysId, count($citiesJson)));
            foreach ($citiesJson as $c) {
                line(sprintf("         ↳ %-26s %s", $c['name'], $c['id']));
            }
            $inserted++;
            continue;
        }

        // ── Skip if already exists (--force mode) ────────────────────
        $chk = $pdo->prepare("SELECT id FROM countries WHERE code = ? LIMIT 1");
        $chk->execute([$country['code']]);
        if ($chk->fetchColumn()) {
            line("  [SKIP] {$country['name']} ({$country['code']}) already in DB");
            $skipped++;
            continue;
        }

        // ── Insert ───────────────────────────────────────────────────
        $stmt = $pdo->prepare("
            INSERT INTO countries
                (uuid, sys_id, name, code, currency, currency_code,
                 default_rate, region, cities, status, meta_data)
            VALUES
                (:uuid, :sys_id, :name, :code, :currency, :currency_code,
                 :default_rate, :region, :cities, 'active', :meta_data)
        ");
        $stmt->execute([
            ':uuid'          => $countryUuid,
            ':sys_id'        => $countrySysId,
            ':name'          => $country['name'],
            ':code'          => $country['code'],
            ':currency'      => $country['currency'],
            ':currency_code' => $country['currency_code'],
            ':default_rate'  => $country['default_rate'],
            ':region'        => $country['region'],
            ':cities'        => json_encode($citiesJson, JSON_UNESCAPED_UNICODE),
            ':meta_data'     => $metaData,
        ]);

        line(sprintf("  ✅ [%3d] %-28s  %s  (%d cities)",
            $rowNum, $country['name'], $countrySysId, count($citiesJson)));
        $inserted++;

    } catch (Throwable $e) {
        line("  ❌ [{$rowNum}] {$country['name']}: " . $e->getMessage());
        $failed++;
    }
}

line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
line("  Inserted : {$inserted}  |  Skipped: {$skipped}  |  Failed: {$failed} \n");
line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
if (!$dryRun && $inserted > 0) {
    line("  Next → php scripts/seed-activities.php");
}

function line(string $s = ''): void { echo $s . PHP_EOL; }