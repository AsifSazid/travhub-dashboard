<?php
// api/utilities/activities.php
// GET ?city_sys_id=THR-26-CNT-01-CTS-01
// GET ?country_sys_id=THR-26-CNT-01     ← all activities for a country
// Returns activities for the given city or country (for package builder suggestions)
header('Content-Type: application/json');
include_once('../authenticate.php');
require_once('../server/db_connection.php');

$citySysId    = trim($_GET['city_sys_id']    ?? '');
$countrySysId = trim($_GET['country_sys_id'] ?? '');
$cityId       = (int)($_GET['city_id'] ?? 0);  // legacy JSON int

try {
    $where  = ["status = 'active'"];
    $params = [];

    if ($citySysId !== '') {
        $where[]              = "city_sys_id = :city_sys_id";
        $params[':city_sys_id'] = $citySysId;

    } elseif ($countrySysId !== '') {
        $where[]                   = "country_sys_id = :country_sys_id";
        $params[':country_sys_id'] = $countrySysId;

    } elseif ($cityId > 0) {
        // Legacy: resolve JSON city_id to sys_id by counting through DB cities
        $cityMap    = [];
        $globalCity = 0;
        $dbCountries = $pdo->query("
            SELECT cities FROM countries WHERE status='active' ORDER BY id ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($dbCountries as $c) {
            foreach (json_decode($c['cities'] ?? '[]', true) ?: [] as $city) {
                $globalCity++;
                $cityMap[$globalCity] = $city['id'];
            }
        }
        $resolvedSysId = $cityMap[$cityId] ?? null;
        if ($resolvedSysId) {
            $where[]              = "city_sys_id = :city_sys_id";
            $params[':city_sys_id'] = $resolvedSysId;
        }
    }

    $whereSQL = 'WHERE ' . implode(' AND ', $where);

    $stmt = $pdo->prepare("
        SELECT sys_id, city_sys_id, country_sys_id, name, type, price_range, duration_hours, popularity
        FROM activities
        $whereSQL
        ORDER BY popularity DESC, name ASC
    ");
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($rows) || !empty($params)) {
        $data = array_map(fn($r) => [
            'sys_id'         => $r['sys_id'],
            'city_sys_id'    => $r['city_sys_id'],
            'country_sys_id' => $r['country_sys_id'],
            'name'           => $r['name'],
            'type'           => $r['type'],
            'price_range'    => $r['price_range'],
            'duration_hours' => (float)$r['duration_hours'],
            'popularity'     => (int)$r['popularity'],
        ], $rows);

        echo json_encode(['success' => true, 'source' => 'db', 'data' => $data]);
        exit;
    }

    // ── JSON fallback ─────────────────────────────────────────────────
    $jsonFile = __DIR__ . '/../activities.json';
    if (!file_exists($jsonFile)) {
        echo json_encode(['success' => true, 'source' => 'json', 'data' => []]);
        exit;
    }
    $raw  = json_decode(file_get_contents($jsonFile), true);
    $acts = $raw['activities'] ?? [];

    if ($cityId > 0) {
        $acts = array_values(array_filter($acts, fn($a) => (int)$a['city_id'] === $cityId));
    }

    echo json_encode(['success' => true, 'source' => 'json', 'data' => $acts]);

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}