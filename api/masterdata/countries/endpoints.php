<?php
/**
 * FILE PATH: /api/masterdata/countries/endpoints.php
 *
 * GET ?action=for_work                          → countries available for lead/work
 * GET ?action=cities&country=SYS_ID             → cities of a country
 * GET ?action=hotels&country=X&city=Y&q=search  → hotels filtered by country/city
 * GET ?action=all                               → all active countries
 */

require '../../../server/db_connection.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$action  = $_GET['action']  ?? 'for_work';
$country = $_GET['country'] ?? '';

try {
    switch ($action) {

        /* ── Countries for lead/work creation ──────────────────── */
        case 'for_work':
            // Use for_work column if it exists, fallback to all active
            try {
                $stmt = $pdo->prepare("
                    SELECT sys_id, name, code, region, currency, currency_code
                    FROM countries
                    WHERE for_work = 1 AND status = 'active'
                    ORDER BY name ASC
                ");
                $stmt->execute();
            } catch (\Throwable $e) {
                // for_work column doesn't exist yet — fallback to all active
                $stmt = $pdo->prepare("
                    SELECT sys_id, name, code, region, currency, currency_code
                    FROM countries
                    WHERE status = 'active'
                    ORDER BY name ASC
                ");
                $stmt->execute();
            }
            echo json_encode(['status' => 'ok', 'data' => $stmt->fetchAll(\PDO::FETCH_ASSOC)]);
            break;

        /* ── Cities of a specific country ──────────────────────── */
        case 'cities':
            if (!$country) {
                echo json_encode(['status' => 'ok', 'data' => []]);
                break;
            }
            $stmt = $pdo->prepare("SELECT cities FROM countries WHERE sys_id = ? LIMIT 1");
            $stmt->execute([$country]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            $cities = [];
            if ($row && !empty($row['cities'])) {
                $raw = json_decode($row['cities'], true);
                if (is_array($raw)) {
                    foreach ($raw as $item) {
                        // cities is a JSON array of objects: [{sys_id, name, ...}]
                        if (is_array($item) && isset($item['name'])) {
                            $cities[] = [
                                'sys_id' => $item['sys_id'] ?? $item['name'],
                                'name'   => $item['name'],
                            ];
                        }
                    }
                }
            }

            usort($cities, fn($a, $b) => strcmp($a['name'], $b['name']));
            echo json_encode(['status' => 'ok', 'data' => $cities]);
            break;

        /* ── Hotels filtered by country and/or city ─────────────── */
        case 'hotels':
            $city  = $_GET['city']  ?? '';
            $q     = trim($_GET['q'] ?? '');
            $limit = min((int)($_GET['limit'] ?? 20), 50);

            $where  = ["h.status = 'active'"];
            $params = [];

            if ($country) {
                $where[]  = 'h.country_sys_id = ?';
                $params[] = $country;
            }
            if ($city) {
                $where[]  = 'h.city_sys_id = ?';
                $params[] = $city;
            }
            if ($q) {
                $where[]  = '(h.name LIKE ? OR h.search_terms LIKE ?)';
                $like     = "%{$q}%";
                $params[] = $like;
                $params[] = $like;
            }

            $whereSQL = implode(' AND ', $where);
            $stmt = $pdo->prepare("
                SELECT sys_id, name, city_name, country_name, star_rating, address
                FROM hotels h
                WHERE {$whereSQL}
                ORDER BY usage_count DESC, name ASC
                LIMIT {$limit}
            ");
            $stmt->execute($params);
            echo json_encode(['status' => 'ok', 'data' => $stmt->fetchAll(\PDO::FETCH_ASSOC)]);
            break;

        /* ── All active countries ───────────────────────────────── */
        case 'all':
        default:
            $stmt = $pdo->prepare("
                SELECT sys_id, name, code, region, currency, currency_code
                FROM countries
                WHERE status = 'active'
                ORDER BY name ASC
            ");
            $stmt->execute();
            echo json_encode(['status' => 'ok', 'data' => $stmt->fetchAll(\PDO::FETCH_ASSOC)]);
    }

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}