<?php
/**
 * api/ai/day-extract.php (Gen-3)
 * Button 2 — Extract structured day from raw text (10% hallucination)
 * POST { raw_text, day_title, day_number, country_sys_ids:[], country_names:[] }
 */
session_start();
require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';
require_once '../../server/ai-gemini.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false,'message'=>'POST only']); exit;
}

$in            = json_decode(file_get_contents('php://input'), true) ?: [];
$raw_text      = trim($in['raw_text']    ?? '');
$day_title     = trim($in['day_title']   ?? '');
$day_number    = (int)($in['day_number'] ?? 1);
$cSysIds       = is_array($in['country_sys_ids']??null) ? $in['country_sys_ids'] : [];
$cNames        = is_array($in['country_names']  ??null) ? $in['country_names']   : [];

if (!$raw_text || empty($cSysIds)) {
    echo json_encode(['success'=>false,'message'=>'raw_text and country_sys_ids required']); exit;
}

$ph = implode(',', array_fill(0, count($cSysIds), '?'));

try {
    // ── STEP 1: Keywords from text ────────────────────────────────────
    $kwRes = geminiJSON(
        'Extract travel keywords from itinerary text. Return ONLY JSON.',
        "Day: {$day_title}\nText: {$raw_text}\n\nReturn: {\"activity_keywords\":[...],\"transport_keywords\":[]}"
    , 300);
    $kw      = $kwRes['success'] ? $kwRes['data'] : [];
    $actKw   = array_merge($kw['activity_keywords'] ?? [], array_filter(explode(' ', $day_title), fn($w) => strlen($w) >= 4));
    $transKw = $kw['transport_keywords'] ?? [];

    // ── STEP 2: DB search — activities ───────────────────────────────
    $dbActs = [];
    $actKw  = array_values(array_unique(array_filter($actKw)));
    if (!empty($actKw)) {
        $likeClauses = implode(' OR ', array_fill(0, count($actKw), '(name LIKE ? OR search_terms LIKE ?)'));
        $likeParams  = [];
        foreach ($actKw as $k) { $likeParams[] = "%{$k}%"; $likeParams[] = "%{$k}%"; }
        $stmt = $pdo->prepare("SELECT sys_id, name, type, category, city_name, duration_hours, short_description FROM activities WHERE status='active' AND country_sys_id IN ({$ph}) AND ({$likeClauses}) LIMIT 15");
        $stmt->execute(array_merge($cSysIds, $likeParams));
        $dbActs = $stmt->fetchAll();
    }
    if (empty($dbActs)) {
        $stmt = $pdo->prepare("SELECT sys_id, name, type, category, city_name, duration_hours, short_description FROM activities WHERE status='active' AND country_sys_id IN ({$ph}) ORDER BY usage_count DESC LIMIT 10");
        $stmt->execute($cSysIds);
        $dbActs = $stmt->fetchAll();
    }

    // ── STEP 2: DB search — transport ────────────────────────────────
    $dbTrans = [];
    $transKw = array_values(array_unique(array_filter($transKw, fn($k) => strlen($k) >= 3)));
    if (!empty($transKw)) {
        $likeClauses = implode(' OR ', array_fill(0, count($transKw), 'name LIKE ?'));
        $likeParams  = array_map(fn($k) => "%{$k}%", $transKw);
        $stmt = $pdo->prepare("SELECT sys_id, name, type, from_city_name, to_city_name, direction, duration_typical FROM transport_services WHERE status='active' AND country_sys_id IN ({$ph}) AND ({$likeClauses}) LIMIT 10");
        $stmt->execute(array_merge($cSysIds, $likeParams));
        $dbTrans = $stmt->fetchAll();
    }

    // ── Load variants ─────────────────────────────────────────────────
    $actVariants = [];
    if (!empty($dbActs)) {
        $ids   = array_column($dbActs, 'sys_id');
        $vph   = implode(',', array_fill(0, count($ids), '?'));
        $vStmt = $pdo->prepare("SELECT activity_sys_id, sys_id, variant_name, currency_code, net_cost, sell_price, child_price, price_basis FROM activity_variants WHERE activity_sys_id IN ({$vph}) AND status='active' ORDER BY sell_price ASC");
        $vStmt->execute($ids);
        foreach ($vStmt->fetchAll() as $v) {
            if (!isset($actVariants[$v['activity_sys_id']])) $actVariants[$v['activity_sys_id']] = $v;
        }
    }
    $transVariants = [];
    if (!empty($dbTrans)) {
        $ids   = array_column($dbTrans, 'sys_id');
        $vph   = implode(',', array_fill(0, count($ids), '?'));
        $vStmt = $pdo->prepare("SELECT service_sys_id, sys_id, vehicle_class, currency_code, net_cost, sell_price, price_basis FROM transport_variants WHERE service_sys_id IN ({$vph}) AND status='active' ORDER BY sell_price ASC");
        $vStmt->execute($ids);
        foreach ($vStmt->fetchAll() as $v) {
            if (!isset($transVariants[$v['service_sys_id']])) $transVariants[$v['service_sys_id']] = $v;
        }
    }

    // ── STEP 3: Gemini maps text → structured items ───────────────────
    $dbSummary = "ACTIVITIES IN DB:\n";
    foreach ($dbActs as $a) {
        $v = $actVariants[$a['sys_id']] ?? null;
        $dbSummary .= "- [{$a['sys_id']}|".($v?$v['sys_id']:'')."] {$a['name']} | {$a['city_name']} | {$a['duration_hours']}h".($v?" | Net:{$v['currency_code']} {$v['net_cost']} Sell:{$v['sell_price']}":'')."\n";
    }
    $dbSummary .= "\nTRANSPORT IN DB:\n";
    foreach ($dbTrans as $t) {
        $v = $transVariants[$t['sys_id']] ?? null;
        $dbSummary .= "- [{$t['sys_id']}|".($v?$v['sys_id']:'')."] {$t['name']} | {$t['from_city_name']}→{$t['to_city_name']}".($v?" | Net:{$v['currency_code']} {$v['net_cost']}":'')."\n";
    }

    $system = 'Extract travel items from day text. Match to DB IDs. Return ONLY raw JSON object. Keys: day_number,title,meal_breakfast,meal_lunch,meal_dinner,items[]. Each item: {type,source,source_sys_id,name,category,duration_hours,currency_code,net_cost,sell_price,price_basis}';
    $user   = "Day {$day_number}: {$day_title}\nText: {$raw_text}\n\n{$dbSummary}\n\nReturn JSON object with items array.";
    $r      = geminiCall($system, $user, 2000, 0.2);

    if (!$r['success']) {
        echo json_encode(['success'=>false,'message'=>$r['error']??'AI call failed']); exit;
    }

    $text = trim(preg_replace(['/^```(json)?\s*/m','/```\s*$/m'], '', $r['text']));

    // Attempt direct parse
    $day = json_decode($text, true);

    // Attempt: truncated JSON — try to close it
    if (!is_array($day) || !isset($day['items'])) {
        // Add closing braces to fix truncation
        foreach ([']}', '}]}', ']}}'] as $suffix) {
            $fixed = json_decode($text.$suffix, true);
            if (is_array($fixed) && isset($fixed['items'])) { $day = $fixed; break; }
        }
    }

    // Fallback: extract items via regex
    if (!is_array($day) || !isset($day['items'])) {
        preg_match_all('/\{[^{}]*"name"[^{}]*\}/s', $text, $matches);
        $items = [];
        foreach ($matches[0] as $m) {
            $obj = json_decode($m, true);
            if (is_array($obj) && !empty($obj['name'])) $items[] = $obj;
        }
        $day = ['day_number'=>$day_number,'title'=>$day_title,'meal_breakfast'=>false,'meal_lunch'=>false,'meal_dinner'=>false,'items'=>$items];
    }

    if (empty($day['items'])) {
        echo json_encode(['success'=>false,'message'=>'AI extraction failed — no items returned','raw'=>substr($text,0,200)]); exit;
    }
    $actMap = array_column($dbActs, null, 'sys_id');

    foreach ($day['items'] as &$item) {
        if ($item['type']==='activity' && ($item['source']??'')==='masterdata' && !empty($item['source_sys_id'])) {
            $a = $actMap[$item['source_sys_id']] ?? null;
            if ($a && !empty($a['short_description'])) {
                $pts = json_decode($a['short_description'], true);
                if (is_array($pts)) $item['description_points'] = $pts;
            }
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
        if ($item['type']==='transport' && ($item['source']??'')==='masterdata' && !empty($item['source_sys_id'])) {
            if (empty($item['net_cost']) && isset($transVariants[$item['source_sys_id']])) {
                $v = $transVariants[$item['source_sys_id']];
                $item['variant_sys_id'] = $v['sys_id'];
                $item['vehicle_class']  = $v['vehicle_class'];
                $item['currency_code']  = $v['currency_code'];
                $item['net_cost']       = (float)$v['net_cost'];
                $item['sell_price']     = (float)$v['sell_price'];
                $item['price_basis']    = $v['price_basis'];
            }
        }
    }
    unset($item);

    echo json_encode(['success'=>true,'data'=>$day], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}