<?php
// api/utilities/cities.php
// GET ?country_sys_id=THR-26-CNT-01   ← preferred (DB)
// GET ?country_id=1                   ← legacy JSON int id (still supported)
// GET ?code=TH                        ← by country code
// Returns cities for the given country
header('Content-Type: application/json');
include_once('../authenticate.php');
require_once('../server/db_connection.php');

$countrySysId = trim($_GET['country_sys_id'] ?? '');
$countryCode  = strtoupper(trim($_GET['code'] ?? ''));
$countryId    = (int)($_GET['country_id'] ?? 0);  // legacy

try {
    // ── Resolve which country to fetch ───────────────────────────────
    $where  = "status = 'active'";
    $param  = null;

    if ($countrySysId !== '') {
        $where = "sys_id = ? AND status = 'active'";
        $param = $countrySysId;
    } elseif ($countryCode !== '') {
        $where = "code = ? AND status = 'active'";
        $param = $countryCode;
    } elseif ($countryId > 0) {
        $where = "id = ? AND status = 'active'";
        $param = $countryId;
    }

    $stmt = $pdo->prepare("SELECT cities FROM countries WHERE {$where} LIMIT 1");
    $stmt->execute($param ? [$param] : []);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $cities = json_decode($row['cities'] ?? '[]', true) ?: [];
        echo json_encode(['success' => true, 'source' => 'db', 'data' => $cities]);
        exit;
    }

    // ── JSON fallback ─────────────────────────────────────────────────
    $jsonFile = __DIR__ . '/../countries.json';
    if (!file_exists($jsonFile)) {
        echo json_encode(['success' => true, 'source' => 'json', 'data' => []]);
        exit;
    }

    $raw        = json_decode(file_get_contents($jsonFile), true);
    $allCities  = $raw['cities'] ?? [];

    if ($countryId > 0) {
        $filtered = array_values(array_filter($allCities, fn($c) => (int)$c['country_id'] === $countryId));
    } elseif ($countryCode !== '') {
        // Find country_id from JSON by code
        $matched = array_filter($raw['countries'] ?? [], fn($c) => strtoupper($c['code']) === $countryCode);
        $jsonCountryId = $matched ? (int)array_values($matched)[0]['id'] : 0;
        $filtered = $jsonCountryId
            ? array_values(array_filter($allCities, fn($c) => (int)$c['country_id'] === $jsonCountryId))
            : [];
    } else {
        $filtered = $allCities;
    }

    echo json_encode(['success' => true, 'source' => 'json', 'data' => $filtered]);

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}