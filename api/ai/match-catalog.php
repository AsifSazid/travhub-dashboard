<?php
/**
 * api/ai/match-catalog.php (Gen-3)
 * POST { label, type, country_sys_id?, city_sys_id?, limit? }
 * Searches masterdata catalog using FULLTEXT + LIKE fallback.
 * Returns top N candidates from the matching catalog table.
 *
 * type: activity → activities + activity_variants
 *       transport → transport_services + transport_variants
 *       hotel     → hotels + room_types
 *       component → components + component_variants
 */
session_start();
require_once '../../../server/api_bootstrap.php';
require_once '../../../server/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false,'message'=>'POST only']); exit;
}

$in             = json_decode(file_get_contents('php://input'), true) ?: [];
$label          = trim($in['label']          ?? '');
$type           = trim($in['type']           ?? '');
$country_sys_id = trim($in['country_sys_id'] ?? '');
$city_sys_id    = trim($in['city_sys_id']    ?? '');
$maxResults     = min(10, max(1, (int)($in['limit'] ?? 5)));

if (!$label || !$type) {
    echo json_encode(['success'=>false,'message'=>'label and type required']); exit;
}

try {
    $results = [];

    // ── ACTIVITY ───────────────────────────────────────────────────
    if ($type === 'activity') {
        $where  = ["a.status = 'active'", "a.is_package_override = 0"];
        $params = [];

        if ($country_sys_id) { $where[] = "a.country_sys_id = :csid"; $params[':csid'] = $country_sys_id; }
        if ($city_sys_id)    { $where[] = "a.city_sys_id = :ctysid";  $params[':ctysid'] = $city_sys_id; }

        $w = 'WHERE ' . implode(' AND ', $where);

        // Try FULLTEXT first
        try {
            $ftParams = array_merge($params, [':q' => $label]);
            $stmt = $pdo->prepare("
                SELECT a.sys_id, a.name, a.city_name, a.country_name,
                       a.duration_hours, a.type, a.category,
                       MATCH(a.name, a.search_terms) AGAINST(:q IN NATURAL LANGUAGE MODE) AS score
                FROM activities a {$w}
                  AND MATCH(a.name, a.search_terms) AGAINST(:q IN NATURAL LANGUAGE MODE)
                ORDER BY score DESC LIMIT :lim
            ");
            foreach ($ftParams as $k => $v) $stmt->bindValue($k, $v);
            $stmt->bindValue(':lim', $maxResults, PDO::PARAM_INT);
            $stmt->execute();
            $acts = $stmt->fetchAll();
        } catch (Throwable $e) {
            $acts = [];
        }

        // Fallback to LIKE if FULLTEXT returned nothing
        if (empty($acts)) {
            $likeParams = array_merge($params, [':lk' => "%{$label}%"]);
            $stmt = $pdo->prepare("
                SELECT a.sys_id, a.name, a.city_name, a.country_name,
                       a.duration_hours, a.type, a.category, 0 AS score
                FROM activities a {$w} AND (a.name LIKE :lk OR a.search_terms LIKE :lk)
                ORDER BY a.usage_count DESC, a.popularity DESC LIMIT :lim
            ");
            foreach ($likeParams as $k => $v) $stmt->bindValue($k, $v);
            $stmt->bindValue(':lim', $maxResults, PDO::PARAM_INT);
            $stmt->execute();
            $acts = $stmt->fetchAll();
        }

        // For each activity: load variants + tags, apply tag bonus scoring
        // Tag bonus: label words matched against activity tags boost relevance
        $labelWords = array_filter(explode(' ', strtolower(preg_replace('/[^a-z0-9 ]/i', ' ', $label))));

        foreach ($acts as &$act) {
            // Variants
            $vStmt = $pdo->prepare("
                SELECT sys_id, variant_name, currency_code, net_cost, sell_price,
                       price_basis, transport_mode
                FROM activity_variants
                WHERE activity_sys_id = ? AND status = 'active'
                ORDER BY sell_price ASC LIMIT 3
            ");
            $vStmt->execute([$act['sys_id']]);
            $act['variants'] = $vStmt->fetchAll();

            // Tags
            $tStmt = $pdo->prepare("
                SELECT tag FROM activity_tags
                WHERE activity_sys_id = ? AND status = 'active'
                ORDER BY tag ASC
            ");
            $tStmt->execute([$act['sys_id']]);
            $act['tags'] = array_column($tStmt->fetchAll(), 'tag');

            // Tag bonus — +1 for each tag that matches a label word
            $tagBonus = 0;
            foreach ($act['tags'] as $tag) {
                foreach ($labelWords as $word) {
                    if (strlen($word) >= 3 && (str_contains($tag, $word) || str_contains($word, $tag))) {
                        $tagBonus++;
                    }
                }
            }
            $act['score'] = ((float)($act['score'] ?? 0)) + ($tagBonus * 0.5);

            $results[] = $act;
        }
        unset($act);

        // Re-sort by final score
        usort($results, fn($a, $b) => $b['score'] <=> $a['score']);
    }

    // ── TRANSPORT ──────────────────────────────────────────────────
    elseif ($type === 'transport') {
        $where  = ["ts.status = 'active'"];
        $params = [];
        if ($country_sys_id) { $where[] = "ts.country_sys_id = :csid"; $params[':csid'] = $country_sys_id; }
        $w = 'WHERE ' . implode(' AND ', $where);

        try {
            $ftParams = array_merge($params, [':q' => $label]);
            $stmt = $pdo->prepare("
                SELECT ts.sys_id, ts.name, ts.type, ts.from_city_name, ts.to_city_name,
                       ts.direction, ts.duration_typical, ts.country_name,
                       MATCH(ts.name, ts.search_terms) AGAINST(:q IN NATURAL LANGUAGE MODE) AS score
                FROM transport_services ts {$w}
                  AND MATCH(ts.name, ts.search_terms) AGAINST(:q IN NATURAL LANGUAGE MODE)
                ORDER BY score DESC LIMIT :lim
            ");
            foreach ($ftParams as $k => $v) $stmt->bindValue($k, $v);
            $stmt->bindValue(':lim', $maxResults, PDO::PARAM_INT);
            $stmt->execute();
            $svcs = $stmt->fetchAll();
        } catch (Throwable $e) {
            $svcs = [];
        }

        if (empty($svcs)) {
            $likeParams = array_merge($params, [':lk' => "%{$label}%"]);
            $stmt = $pdo->prepare("
                SELECT ts.sys_id, ts.name, ts.type, ts.from_city_name, ts.to_city_name,
                       ts.direction, ts.duration_typical, ts.country_name, 0 AS score
                FROM transport_services ts {$w}
                  AND (ts.name LIKE :lk OR ts.search_terms LIKE :lk)
                ORDER BY ts.usage_count DESC LIMIT :lim
            ");
            foreach ($likeParams as $k => $v) $stmt->bindValue($k, $v);
            $stmt->bindValue(':lim', $maxResults, PDO::PARAM_INT);
            $stmt->execute();
            $svcs = $stmt->fetchAll();
        }

        foreach ($svcs as $svc) {
            $vStmt = $pdo->prepare("
                SELECT sys_id, variant_name, vehicle_class, capacity_max,
                       seat_count, max_luggage_kg, max_luggage_bags,
                       currency_code, net_cost, sell_price, price_basis, transfer_type
                FROM transport_variants
                WHERE service_sys_id = ? AND status = 'active'
                ORDER BY sell_price ASC LIMIT 3
            ");
            $vStmt->execute([$svc['sys_id']]);
            $svc['variants'] = $vStmt->fetchAll();
            $results[]       = $svc;
        }
    }

    // ── HOTEL ──────────────────────────────────────────────────────
    elseif ($type === 'hotel') {
        $where  = ["h.status = 'active'"];
        $params = [];
        if ($country_sys_id) { $where[] = "h.country_sys_id = :csid"; $params[':csid'] = $country_sys_id; }
        if ($city_sys_id)    { $where[] = "h.city_sys_id = :ctysid";  $params[':ctysid'] = $city_sys_id; }
        $w = 'WHERE ' . implode(' AND ', $where);

        try {
            $ftParams = array_merge($params, [':q' => $label]);
            $stmt = $pdo->prepare("
                SELECT h.sys_id, h.name, h.city_name, h.country_name, h.star_rating,
                       h.check_in_time, h.check_out_time,
                       MATCH(h.name, h.search_terms) AGAINST(:q IN NATURAL LANGUAGE MODE) AS score
                FROM hotels h {$w}
                  AND MATCH(h.name, h.search_terms) AGAINST(:q IN NATURAL LANGUAGE MODE)
                ORDER BY score DESC LIMIT :lim
            ");
            foreach ($ftParams as $k => $v) $stmt->bindValue($k, $v);
            $stmt->bindValue(':lim', $maxResults, PDO::PARAM_INT);
            $stmt->execute();
            $hotels = $stmt->fetchAll();
        } catch (Throwable $e) {
            $hotels = [];
        }

        if (empty($hotels)) {
            $likeParams = array_merge($params, [':lk' => "%{$label}%"]);
            $stmt = $pdo->prepare("
                SELECT h.sys_id, h.name, h.city_name, h.country_name, h.star_rating,
                       h.check_in_time, h.check_out_time, 0 AS score
                FROM hotels h {$w} AND (h.name LIKE :lk OR h.search_terms LIKE :lk)
                ORDER BY h.usage_count DESC LIMIT :lim
            ");
            foreach ($likeParams as $k => $v) $stmt->bindValue($k, $v);
            $stmt->bindValue(':lim', $maxResults, PDO::PARAM_INT);
            $stmt->execute();
            $hotels = $stmt->fetchAll();
        }

        foreach ($hotels as $hotel) {
            $rtStmt = $pdo->prepare("
                SELECT rt.sys_id, rt.room_name, rt.max_adults,
                       rr.meal_plan, rr.currency_code, rr.net_cost, rr.sell_price, rr.occupancy_basis
                FROM room_types rt
                LEFT JOIN room_rates rr ON rr.room_type_sys_id = rt.sys_id AND rr.status='active'
                WHERE rt.hotel_sys_id = ? AND rt.status = 'active'
                ORDER BY rr.sell_price ASC LIMIT 5
            ");
            $rtStmt->execute([$hotel['sys_id']]);
            $hotel['room_types'] = $rtStmt->fetchAll();
            $results[]           = $hotel;
        }
    }

    // ── COMPONENT ──────────────────────────────────────────────────
    elseif ($type === 'component') {
        try {
            $stmt = $pdo->prepare("
                SELECT c.sys_id, c.name, c.category, c.description,
                       MATCH(c.name, c.search_terms) AGAINST(:q IN NATURAL LANGUAGE MODE) AS score
                FROM components c
                WHERE c.status = 'active'
                  AND MATCH(c.name, c.search_terms) AGAINST(:q IN NATURAL LANGUAGE MODE)
                ORDER BY score DESC LIMIT :lim
            ");
            $stmt->bindValue(':q', $label);
            $stmt->bindValue(':lim', $maxResults, PDO::PARAM_INT);
            $stmt->execute();
            $comps = $stmt->fetchAll();
        } catch (Throwable $e) {
            $comps = [];
        }

        if (empty($comps)) {
            $stmt = $pdo->prepare("
                SELECT c.sys_id, c.name, c.category, c.description, 0 AS score
                FROM components c
                WHERE c.status = 'active' AND (c.name LIKE :lk OR c.search_terms LIKE :lk)
                ORDER BY c.usage_count DESC LIMIT :lim
            ");
            $stmt->bindValue(':lk', "%{$label}%");
            $stmt->bindValue(':lim', $maxResults, PDO::PARAM_INT);
            $stmt->execute();
            $comps = $stmt->fetchAll();
        }

        foreach ($comps as $comp) {
            $vStmt = $pdo->prepare("
                SELECT sys_id, variant_name, unit_basis, currency_code, net_cost, sell_price
                FROM component_variants WHERE component_sys_id=? AND status='active'
                ORDER BY sell_price ASC LIMIT 3
            ");
            $vStmt->execute([$comp['sys_id']]);
            $comp['variants'] = $vStmt->fetchAll();
            $results[]        = $comp;
        }
    }

    echo json_encode([
        'success' => true,
        'type'    => $type,
        'label'   => $label,
        'count'   => count($results),
        'data'    => $results,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}