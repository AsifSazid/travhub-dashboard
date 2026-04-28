<?php
session_start();
// api/utilities/countries.php
// GET — returns all active countries with currency + default_rate
// Used by: package-builder, package-calculation, any dropdown
// Source: DB (primary) → countries.json (fallback if DB empty)
header('Content-Type: application/json');
require_once('../../server/db_connection.php');

try {
    $rows = $pdo->query("
        SELECT id, sys_id, name, code, currency, currency_code, default_rate, region, cities
        FROM countries
        WHERE status = 'active'
        ORDER BY name ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($rows)) {
        // ── DB source ────────────────────────────────────────────────
        $data = array_map(fn($r) => [
            'id'            => (int)$r['id'],
            'sys_id'        => $r['sys_id'],
            'name'          => $r['name'],
            'code'          => $r['code'],
            'currency'      => $r['currency'],
            'currency_code' => $r['currency_code'],
            'default_rate'  => (float)$r['default_rate'],
            'region'        => $r['region'],
            'cities' => json_decode($r['cities'] ?? '[]', true)
        ], $rows);

        echo json_encode(['success' => true, 'source' => 'db', 'data' => $data]);

    } else {
        // ── JSON fallback ─────────────────────────────────────────────
        $jsonFile = __DIR__ . '/../countries.json';
        if (!file_exists($jsonFile)) {
            echo json_encode(['success' => false, 'message' => 'No countries in DB and countries.json not found']);
            exit;
        }
        $raw  = json_decode(file_get_contents($jsonFile), true);
        $data = $raw['countries'] ?? [];

        echo json_encode(['success' => true, 'source' => 'json', 'data' => $data]);
    }

} catch (Throwable $e) {
    // DB error — fall back to JSON silently
    $jsonFile = __DIR__ . '/../countries.json';
    if (file_exists($jsonFile)) {
        $raw  = json_decode(file_get_contents($jsonFile), true);
        echo json_encode(['success' => true, 'source' => 'json_fallback', 'data' => $raw['countries'] ?? []]);
    } else {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}