<?php
/**
 * api/ai/day-suggest.php (Gen-3)
 * Button 3 — AI Suggest items for a day (20% hallucination)
 * POST { package_title, day_title, day_number, country_sys_ids:[], country_names:[], existing_item_names:[] }
 */
session_start();
require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';
require_once '../../server/ai-gemini.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false,'message'=>'POST only']); exit;
}

$in            = json_decode(file_get_contents('php://input'), true) ?: [];
$package_title = trim($in['package_title']  ?? '');
$day_title     = trim($in['day_title']      ?? '');
$day_number    = (int)($in['day_number']    ?? 1);
$cSysIds       = is_array($in['country_sys_ids']??null) ? $in['country_sys_ids'] : [];
$cNames        = is_array($in['country_names']  ??null) ? $in['country_names']   : [];
$existingNames = is_array($in['existing_item_names']??null) ? $in['existing_item_names'] : [];

if (empty($cSysIds)) {
    echo json_encode(['success'=>false,'message'=>'country_sys_ids required']); exit;
}

$ph = implode(',', array_fill(0, count($cSysIds), '?'));

try {
    // ── DB: activities ────────────────────────────────────────────────
    $stmt = $pdo->prepare("
        SELECT sys_id, name, category, city_name, duration_hours
        FROM activities
        WHERE status='active' AND country_sys_id IN ({$ph})
        ORDER BY popularity DESC, usage_count DESC
        LIMIT 20
    ");
    $stmt->execute($cSysIds);
    $dbActs = $stmt->fetchAll();

    // ── DB: transport ─────────────────────────────────────────────────
    $stmt = $pdo->prepare("
        SELECT sys_id, name, from_city_name, to_city_name
        FROM transport_services
        WHERE status='active' AND country_sys_id IN ({$ph})
        ORDER BY usage_count DESC
        LIMIT 10
    ");
    $stmt->execute($cSysIds);
    $dbTrans = $stmt->fetchAll();

    // ── Cheapest variants ─────────────────────────────────────────────
    $actVar = [];
    if (!empty($dbActs)) {
        $ids  = array_column($dbActs, 'sys_id');
        $vph  = implode(',', array_fill(0, count($ids), '?'));
        $vs   = $pdo->prepare("SELECT activity_sys_id, sys_id as v_id, currency_code, net_cost, sell_price, child_price, price_basis FROM activity_variants WHERE activity_sys_id IN ({$vph}) AND status='active' ORDER BY sell_price ASC");
        $vs->execute($ids);
        foreach ($vs->fetchAll() as $v) {
            if (!isset($actVar[$v['activity_sys_id']])) $actVar[$v['activity_sys_id']] = $v;
        }
    }
    $transVar = [];
    if (!empty($dbTrans)) {
        $ids  = array_column($dbTrans, 'sys_id');
        $vph  = implode(',', array_fill(0, count($ids), '?'));
        $vs   = $pdo->prepare("SELECT service_sys_id, sys_id as v_id, vehicle_class, currency_code, net_cost, sell_price, price_basis FROM transport_variants WHERE service_sys_id IN ({$vph}) AND status='active' ORDER BY sell_price ASC");
        $vs->execute($ids);
        foreach ($vs->fetchAll() as $v) {
            if (!isset($transVar[$v['service_sys_id']])) $transVar[$v['service_sys_id']] = $v;
        }
    }

    // ── Build compact DB list for prompt ─────────────────────────────
    $actList   = [];
    foreach ($dbActs as $a) {
        $v = $actVar[$a['sys_id']] ?? null;
        $actList[] = "{$a['sys_id']} | {$a['name']} | {$a['city_name']} | ".($v?"{$v['currency_code']} {$v['net_cost']}":"TBD");
    }
    $transList = [];
    foreach ($dbTrans as $t) {
        $v = $transVar[$t['sys_id']] ?? null;
        $transList[] = "{$t['sys_id']} | {$t['name']} | {$t['from_city_name']}→{$t['to_city_name']} | ".($v?"{$v['currency_code']} {$v['net_cost']}":"TBD");
    }

    $existing = !empty($existingNames) ? 'Skip: '.implode(', ', $existingNames) : '';

    // ── Gemini prompt — compact + explicit token guard ────────────────
    $system = 'You suggest 3-5 travel day items. Use DB IDs when possible. Return ONLY a raw JSON array (no object wrapper). Each element: {"type":"activity|transport","source":"masterdata|ai","source_sys_id":"","name":"","reason":"","currency_code":"","net_cost":0,"sell_price":0,"price_basis":"per_pax"}';

    $user = "Package: {$package_title}\nDay {$day_number}: {$day_title}\nCountry: ".implode(', ',$cNames)."\n{$existing}\n\nACTIVITIES (id|name|city|price):\n".implode("\n",$actList)."\n\nTRANSPORT (id|name|route|price):\n".implode("\n",$transList)."\n\nReturn JSON array of 3-5 suggestions only.";

    $r = geminiCall($system, $user, 3000, 0.3);
    if (!$r['success']) {
        echo json_encode(['success'=>false,'message'=>$r['error']??'AI call failed']); exit;
    }

    // ── Parse — robust truncation recovery ───────────────────────────
    $text = trim(preg_replace(['/^```(json)?\s*/m', '/```\s*$/m'], '', $r['text']));

    // Attempt 1: direct parse (array or object)
    $parsed = json_decode($text, true);
    if (is_array($parsed)) {
        $suggestions = isset($parsed['suggestions']) ? $parsed['suggestions'] : $parsed;
        // filter out non-item keys if object was returned
        if (isset($parsed['suggestions'])) $suggestions = $parsed['suggestions'];
        elseif (array_keys($parsed) !== range(0, count($parsed)-1)) $suggestions = []; // assoc, not list
        else $suggestions = $parsed;
    } else {
        $suggestions = [];
    }

    // Attempt 2: truncated array — add closing bracket and retry
    if (empty($suggestions)) {
        foreach ([']', '}]', '}]}'] as $sfx) {
            $fixed = json_decode($text . $sfx, true);
            if (is_array($fixed)) {
                $suggestions = isset($fixed['suggestions']) ? $fixed['suggestions'] : $fixed;
                if (!empty($suggestions)) break;
            }
        }
    }

    // Attempt 3: extract each complete JSON object using balanced-brace scan
    if (empty($suggestions)) {
        $suggestions = [];
        $len = strlen($text);
        $i   = 0;
        while ($i < $len) {
            // Find opening brace
            $start = strpos($text, '{', $i);
            if ($start === false) break;
            // Walk forward counting braces
            $depth = 0;
            $end   = $start;
            for ($j = $start; $j < $len; $j++) {
                if ($text[$j] === '{') $depth++;
                elseif ($text[$j] === '}') { $depth--; if ($depth === 0) { $end = $j; break; } }
            }
            if ($depth === 0 && $end > $start) {
                $obj = json_decode(substr($text, $start, $end - $start + 1), true);
                if (is_array($obj) && !empty($obj['name'])) {
                    $suggestions[] = $obj;
                }
                $i = $end + 1;
            } else {
                break; // incomplete object — stop
            }
        }
    }

    if (empty($suggestions)) {
        echo json_encode(['success'=>false,'message'=>'AI suggestion failed','raw'=>substr($text,0,400)]);
        exit;
    }

    // ── Enrich from DB variants ───────────────────────────────────────
    $actMap = array_column($dbActs, null, 'sys_id');
    foreach ($suggestions as &$s) {
        $sid = $s['source_sys_id'] ?? '';
        if ($s['type']==='activity' && $sid && isset($actVar[$sid]) && empty($s['net_cost'])) {
            $v = $actVar[$sid];
            $s['variant_sys_id'] = $v['v_id'];
            $s['currency_code']  = $v['currency_code'];
            $s['net_cost']       = (float)$v['net_cost'];
            $s['sell_price']     = (float)$v['sell_price'];
            $s['child_price']    = $v['child_price'] ? (float)$v['child_price'] : null;
            $s['price_basis']    = $v['price_basis'];
        }
        if ($s['type']==='transport' && $sid && isset($transVar[$sid]) && empty($s['net_cost'])) {
            $v = $transVar[$sid];
            $s['variant_sys_id'] = $v['v_id'];
            $s['vehicle_class']  = $v['vehicle_class'];
            $s['currency_code']  = $v['currency_code'];
            $s['net_cost']       = (float)$v['net_cost'];
            $s['sell_price']     = (float)$v['sell_price'];
            $s['price_basis']    = $v['price_basis'];
        }
        // Ensure source field
        if (empty($s['source'])) $s['source'] = $sid ? 'masterdata' : 'ai';
        // description_points default
        if (!isset($s['description_points'])) $s['description_points'] = [];
    }
    unset($s);

    echo json_encode(['success'=>true,'suggestions'=>array_values($suggestions)], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}