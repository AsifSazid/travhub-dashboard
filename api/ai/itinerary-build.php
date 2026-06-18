<?php
/**
 * api/ai/itinerary-build.php (Gen-3)
 * Button 1 — Build Full Itinerary from prompt
 * POST { prompt, package_type, duration, adults, countries: [{sys_id,name}] }
 */
session_start();
require_once '../../../server/api_bootstrap.php';
require_once '../../../server/db_connection.php';
require_once '../../../server/ai-gemini.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false,'message'=>'POST only']); exit;
}

$in           = json_decode(file_get_contents('php://input'), true) ?: [];
$prompt       = trim($in['prompt']       ?? '');
$package_type = trim($in['package_type'] ?? 'custom');
$duration     = (int)($in['duration']    ?? 5);
$adults       = (int)($in['adults']      ?? 2);
$countries    = is_array($in['countries'] ?? null) ? $in['countries'] : [];

if (!$prompt || empty($countries)) {
    echo json_encode(['success'=>false,'message'=>'prompt and countries required']); exit;
}

$countrySysIds = array_column($countries, 'sys_id');
$countryNames  = array_column($countries, 'name');
$ph            = implode(',', array_fill(0, count($countrySysIds), '?'));

try {
    // ── STEP 1: Keywords ─────────────────────────────────────────────
    $kwRes = geminiJSON(
        'Extract travel search keywords from a package description. Return ONLY JSON, no prose.',
        "Package: {$duration}-day {$package_type} in ".implode(', ',$countryNames)."\nPrompt: {$prompt}\n\nReturn: {\"activity_keywords\":[...],\"transport_keywords\":[...],\"day_themes\":[...]}"
    , 600);
    $kw          = $kwRes['success'] ? $kwRes['data'] : [];
    $actKw       = $kw['activity_keywords']  ?? [];
    $transKw     = $kw['transport_keywords'] ?? [];
    $dayThemes   = $kw['day_themes']         ?? [];

    // ── STEP 2: DB Search — activities (no variant JOIN) ─────────────
    $dbActs = [];
    if (!empty($actKw)) {
        $likeTerms   = array_map(fn($k) => "%{$k}%", $actKw);
        $likeClauses = implode(' OR ', array_fill(0, count($likeTerms), '(name LIKE ? OR search_terms LIKE ? OR category LIKE ?)'));
        $likeParams  = [];
        foreach ($likeTerms as $t) { $likeParams[] = $t; $likeParams[] = $t; $likeParams[] = $t; }
        $stmt = $pdo->prepare("
            SELECT sys_id, name, type, category, city_name, country_name, duration_hours, short_description
            FROM activities
            WHERE status='active' AND country_sys_id IN ({$ph}) AND ({$likeClauses})
            LIMIT 30
        ");
        $stmt->execute(array_merge($countrySysIds, $likeParams));
        $dbActs = $stmt->fetchAll();
    }
    if (empty($dbActs)) {
        $stmt = $pdo->prepare("SELECT sys_id, name, type, category, city_name, country_name, duration_hours, short_description FROM activities WHERE status='active' AND country_sys_id IN ({$ph}) ORDER BY usage_count DESC LIMIT 20");
        $stmt->execute($countrySysIds);
        $dbActs = $stmt->fetchAll();
    }

    // ── STEP 2: DB Search — transport (no variant JOIN) ──────────────
    $dbTrans = [];
    if (!empty($transKw)) {
        $likeTerms   = array_map(fn($k) => "%{$k}%", $transKw);
        $likeClauses = implode(' OR ', array_fill(0, count($likeTerms), 'name LIKE ?'));
        $stmt = $pdo->prepare("
            SELECT sys_id, name, type, from_city_name, to_city_name, direction, duration_typical, country_name
            FROM transport_services
            WHERE status='active' AND country_sys_id IN ({$ph}) AND ({$likeClauses})
            LIMIT 20
        ");
        $stmt->execute(array_merge($countrySysIds, $likeTerms));
        $dbTrans = $stmt->fetchAll();
    }
    if (empty($dbTrans)) {
        $stmt = $pdo->prepare("SELECT sys_id, name, type, from_city_name, to_city_name, direction, duration_typical, country_name FROM transport_services WHERE status='active' AND country_sys_id IN ({$ph}) ORDER BY usage_count DESC LIMIT 15");
        $stmt->execute($countrySysIds);
        $dbTrans = $stmt->fetchAll();
    }

    // ── Load cheapest variants per activity ───────────────────────────
    $actVariants = [];
    if (!empty($dbActs)) {
        $actIds = array_column($dbActs, 'sys_id');
        $vph    = implode(',', array_fill(0, count($actIds), '?'));
        $vStmt  = $pdo->prepare("SELECT activity_sys_id, sys_id, variant_name, currency_code, net_cost, sell_price, child_price, price_basis FROM activity_variants WHERE activity_sys_id IN ({$vph}) AND status='active' ORDER BY sell_price ASC");
        $vStmt->execute($actIds);
        foreach ($vStmt->fetchAll() as $v) {
            if (!isset($actVariants[$v['activity_sys_id']])) $actVariants[$v['activity_sys_id']] = $v;
        }
    }

    // ── Load cheapest variants per transport ──────────────────────────
    $transVariants = [];
    if (!empty($dbTrans)) {
        $transIds = array_column($dbTrans, 'sys_id');
        $vph      = implode(',', array_fill(0, count($transIds), '?'));
        $vStmt    = $pdo->prepare("SELECT service_sys_id, sys_id, variant_name, vehicle_class, capacity_max, currency_code, net_cost, sell_price, price_basis FROM transport_variants WHERE service_sys_id IN ({$vph}) AND status='active' ORDER BY sell_price ASC");
        $vStmt->execute($transIds);
        foreach ($vStmt->fetchAll() as $v) {
            if (!isset($transVariants[$v['service_sys_id']])) $transVariants[$v['service_sys_id']] = $v;
        }
    }

    // ── STEP 3: Build DB summary for Gemini ──────────────────────────
    $dbSummary = "AVAILABLE ACTIVITIES:\n";
    foreach ($dbActs as $a) {
        $v = $actVariants[$a['sys_id']] ?? null;
        $price = $v ? "Net:{$v['currency_code']} {$v['net_cost']} Sell:{$v['sell_price']}" : 'price:TBD';
        $dbSummary .= "- [{$a['sys_id']}|".($v?$v['sys_id']:'')."] {$a['name']} | {$a['city_name']}, {$a['country_name']} | {$a['duration_hours']}h | {$price}\n";
    }
    $dbSummary .= "\nAVAILABLE TRANSPORT:\n";
    foreach ($dbTrans as $t) {
        $v = $transVariants[$t['sys_id']] ?? null;
        $price = $v ? "Net:{$v['currency_code']} {$v['net_cost']}" : 'price:TBD';
        $dbSummary .= "- [{$t['sys_id']}|".($v?$v['sys_id']:'')."] {$t['name']} | {$t['from_city_name']}→{$t['to_city_name']} | ".($v?$v['vehicle_class']:'')." | {$price}\n";
    }

    $dtStr    = !empty($dayThemes) ? implode(', ', $dayThemes) : 'distribute logically';
    $buildSys = 'You are a travel itinerary builder. Use database items (source:"masterdata"). Add up to 5% estimated items (source:"ai"). Return ONLY a raw JSON object with a "days" array. Keep description_points as empty array [].';
    $buildUsr = "Build {$duration}-day itinerary for {$adults} adults in {$countryNames[0]}.\nPackage: {$package_type}\nRequest: {$prompt}\nDay themes: {$dtStr}\n\n{$dbSummary}\n\nReturn: {\"days\":[{\"day_number\":1,\"title\":\"...\",\"city_name\":\"\",\"meal_breakfast\":false,\"meal_lunch\":false,\"meal_dinner\":false,\"items\":[{\"type\":\"activity\",\"source\":\"masterdata\",\"source_sys_id\":\"\",\"variant_sys_id\":\"\",\"name\":\"\",\"category\":\"\",\"duration_hours\":0,\"currency_code\":\"\",\"net_cost\":0,\"sell_price\":0,\"price_basis\":\"per_pax\",\"description_points\":[]}]}]}";

    $br = geminiCall($buildSys, $buildUsr, 6000, 0.3);
    if (!$br['success']) {
        echo json_encode(['success'=>false,'message'=>$br['error']??'AI call failed']); exit;
    }

    $bText = trim(preg_replace(['/^```(json)?\s*/m','/```\s*$/m'], '', $br['text']));
    $built = json_decode($bText, true);

    // Try to fix truncated JSON
    if (!is_array($built) || !isset($built['days'])) {
        foreach ([']}', '}]}', ']}}}', ']}}}}'] as $sfx) {
            $fixed = json_decode($bText.$sfx, true);
            if (is_array($fixed) && !empty($fixed['days'])) { $built = $fixed; break; }
        }
    }

    if (!is_array($built) || empty($built['days'])) {
        echo json_encode(['success'=>false,'message'=>'AI failed to build itinerary','raw'=>substr($bText,0,300)]); exit;
    }

    // ── Enrich description_points from DB ────────────────────────────
    $actMap = array_column($dbActs, null, 'sys_id');
    $days   = $built['days'];
    foreach ($days as &$day) {
        foreach ($day['items'] as &$item) {
            if ($item['type']==='activity' && ($item['source']??'') === 'masterdata' && !empty($item['source_sys_id'])) {
                $a = $actMap[$item['source_sys_id']] ?? null;
                if ($a && !empty($a['short_description'])) {
                    $pts = json_decode($a['short_description'], true);
                    if (is_array($pts)) $item['description_points'] = $pts;
                }
                // Fill pricing from variant if AI left it empty
                if (empty($item['net_cost']) && isset($actVariants[$item['source_sys_id']])) {
                    $v = $actVariants[$item['source_sys_id']];
                    $item['variant_sys_id'] = $v['sys_id'];
                    $item['currency_code']  = $v['currency_code'];
                    $item['net_cost']       = (float)$v['net_cost'];
                    $item['sell_price']     = (float)$v['sell_price'];
                    $item['child_price']    = $v['child_price'] ? (float)$v['child_price'] : null;
                    $item['price_basis']    = $v['price_basis'];
                }
            }
        }
        unset($item);
    }
    unset($day);

    echo json_encode(['success'=>true,'days'=>$days,'meta'=>['activities_found'=>count($dbActs),'transport_found'=>count($dbTrans)]], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}