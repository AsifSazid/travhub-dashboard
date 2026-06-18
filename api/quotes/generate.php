<?php
/**
 * api/quotes/generate.php (Gen-3)
 * POST { package_sys_id, markup_type?, markup_value?,
 *        service_fee?, discount?, rounding_rule?, valid_until? }
 * Creates a frozen quote from current package_items.
 */
session_start();
require_once '../../../server/api_bootstrap.php';
require_once '../../../server/db_connection.php';
require_once '../../../server/id_generator.php';
require_once '../../../server/generate_meta_data.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false,'message'=>'POST only']); exit;
}

$in             = json_decode(file_get_contents('php://input'), true) ?: [];
$package_sys_id = trim($in['package_sys_id'] ?? '');
$markup_type    = in_array($in['markup_type'] ?? '', ['percent','fixed']) ? $in['markup_type'] : 'percent';
$markup_value   = max(0, (float)($in['markup_value'] ?? 0));
$service_fee    = max(0, (float)($in['service_fee']  ?? 0));
$discount       = max(0, (float)($in['discount']     ?? 0));
$rounding_rule  = trim($in['rounding_rule'] ?? '');
$valid_until    = trim($in['valid_until']   ?? '');

if (!$package_sys_id) {
    echo json_encode(['success'=>false,'message'=>'package_sys_id required']); exit;
}

try {
    // Load package
    $pkgStmt = $pdo->prepare("SELECT * FROM packages WHERE sys_id=? AND status!='deleted' LIMIT 1");
    $pkgStmt->execute([$package_sys_id]);
    $pkg = $pkgStmt->fetch();
    if (!$pkg) { echo json_encode(['success'=>false,'message'=>'Package not found']); exit; }

    $sellCcy  = $pkg['sell_currency_code'] ?: 'BDT';
    $totalPax = max(1, (int)$pkg['adults'] + (int)$pkg['children']);

    // Load all active items
    $iStmt = $pdo->prepare("
        SELECT * FROM package_items WHERE package_sys_id=? AND status='active'
    ");
    $iStmt->execute([$package_sys_id]);
    $items = $iStmt->fetchAll();

    if (empty($items)) {
        echo json_encode(['success'=>false,'message'=>'No items to quote — add items first']); exit;
    }

    // Load latest FX rates
    $fxStmt = $pdo->query("
        SELECT currency_code, rate, buffer_pct
        FROM fx_rates WHERE status='active'
        GROUP BY currency_code
        HAVING effective_date = MAX(effective_date)
    ");
    $fxRates = [];
    foreach ($fxStmt->fetchAll() as $fx) {
        $fxRates[$fx['currency_code']] = [
            'rate'       => (float)$fx['rate'],
            'buffer_pct' => (float)$fx['buffer_pct'],
        ];
    }

    $sellRate = 1.0;
    if ($sellCcy !== 'BDT' && isset($fxRates[$sellCcy])) {
        $sellRate = $fxRates[$sellCcy]['rate'];
    }

    // Snapshot the FX rates used
    $fxSnapshot  = [];
    $subtotalCost = 0.0;
    $subtotalSell = 0.0;
    $quoteLines   = [];

    foreach ($items as $item) {
        $itemCcy  = $item['currency_code'];
        $itemRate = 1.0;

        if ($itemCcy !== 'BDT' && isset($fxRates[$itemCcy])) {
            $buf      = $fxRates[$itemCcy]['buffer_pct'] / 100;
            $itemRate = $fxRates[$itemCcy]['rate'] * (1 + $buf);

            // Add to snapshot
            if (!isset($fxSnapshot[$itemCcy])) {
                $fxSnapshot[$itemCcy] = [
                    'rate'             => $fxRates[$itemCcy]['rate'],
                    'buffer_pct'       => $fxRates[$itemCcy]['buffer_pct'],
                    'rate_with_buffer' => round($itemRate, 8),
                ];
            }
        }

        $qty        = max(1, (int)$item['qty']);
        $nights     = max(1, (int)($item['nights'] ?: 1));
        $multiplier = ($item['item_type'] === 'hotel') ? $nights : 1;

        $costBdt = (float)$item['net_cost']   * $qty * $multiplier * $itemRate;
        $sellBdt = (float)$item['sell_price'] * $qty * $multiplier * $itemRate;

        $costInSell = ($sellRate > 0) ? $costBdt / $sellRate : $costBdt;
        $sellInSell = ($sellRate > 0) ? $sellBdt / $sellRate : $sellBdt;

        $subtotalCost += $costInSell;
        $subtotalSell += $sellInSell;

        $quoteLines[] = [
            'item_sys_id'        => $item['sys_id'],
            'description'        => $item['title_snapshot'],
            'qty'                => $qty,
            'source_currency_code'=> $itemCcy,
            'source_cost'        => (float)$item['net_cost'],
            'source_sell'        => (float)$item['sell_price'],
            'fx_rate'            => round($itemRate, 8),
            'cost_quote_ccy'     => round($costInSell, 2),
            'sell_quote_ccy'     => round($sellInSell, 2),
        ];
    }

    // Markup on subtotal_sell
    $markupAmount = ($markup_type === 'percent')
        ? $subtotalSell * ($markup_value / 100)
        : $markup_value;

    $grandTotal   = $subtotalSell + $markupAmount + $service_fee - $discount;

    // Rounding
    if ($rounding_rule === 'nearest_100') {
        $grandTotal = round($grandTotal / 100) * 100;
    } elseif ($rounding_rule === 'nearest_50') {
        $grandTotal = round($grandTotal / 50) * 50;
    } elseif ($rounding_rule === 'ceil_100') {
        $grandTotal = ceil($grandTotal / 100) * 100;
    }

    $perPerson   = ($totalPax > 0) ? $grandTotal / $totalPax : $grandTotal;
    $margin      = $grandTotal - $subtotalCost;
    $marginPct   = ($grandTotal > 0) ? round(($margin / $grandTotal) * 100, 2) : 0;

    // Get next version number
    $verStmt = $pdo->prepare("SELECT MAX(version) FROM quotes WHERE package_sys_id=?");
    $verStmt->execute([$package_sys_id]);
    $nextVersion = ((int)$verStmt->fetchColumn()) + 1;

    // Supersede previous draft quotes
    $pdo->prepare("
        UPDATE quotes SET quote_status='superseded'
        WHERE package_sys_id=? AND quote_status IN ('draft','sent')
    ")->execute([$package_sys_id]);

    // Create quote
    $qIds    = generateIDs($pdo, 'quotes');
    $q_sys_id = $qIds['sys_id'];
    $q_uuid   = $qIds['uuid'];
    $meta    = buildMetaData(null, $_SESSION['user_name'] ?? 'system');

    $pdo->prepare("
        INSERT INTO quotes
            (uuid, sys_id, package_sys_id, version, quote_currency_code,
             fx_snapshot, subtotal_cost, subtotal_sell,
             markup_type, markup_value, markup_amount,
             service_fee, discount, grand_total, per_person,
             rounding_rule, margin_amount, margin_pct,
             valid_until, quote_status, status, meta_data)
        VALUES
            (:uuid,:sid,:psid,:ver,:ccy,
             :fx,:scost,:ssell,
             :mt,:mv,:ma,
             :sf,:disc,:gt,:pp,
             :rr,:margin,:mpct,
             :vu,'draft','active',:meta)
    ")->execute([
        ':uuid'   => $q_uuid,            ':sid'   => $q_sys_id,
        ':psid'   => $package_sys_id,    ':ver'   => $nextVersion,
        ':ccy'    => $sellCcy,
        ':fx'     => json_encode($fxSnapshot, JSON_UNESCAPED_UNICODE),
        ':scost'  => round($subtotalCost, 2),
        ':ssell'  => round($subtotalSell, 2),
        ':mt'     => $markup_type,       ':mv'    => $markup_value,
        ':ma'     => round($markupAmount, 2),
        ':sf'     => $service_fee,       ':disc'  => $discount,
        ':gt'     => round($grandTotal, 2),
        ':pp'     => round($perPerson, 2),
        ':rr'     => $rounding_rule ?: null,
        ':margin' => round($margin, 2),  ':mpct'  => $marginPct,
        ':vu'     => $valid_until ?: null,
        ':meta'   => $meta,
    ]);

    // Insert quote_lines
    $lineStmt = $pdo->prepare("
        INSERT INTO quote_lines
            (uuid, sys_id, quote_sys_id, item_sys_id, description, qty,
             source_currency_code, source_cost, source_sell,
             fx_rate, cost_quote_ccy, sell_quote_ccy, status, meta_data)
        VALUES (:uuid,:sid,:qsid,:isid,:desc,:qty,:ccy,:sc,:ss,:fx,:cq,:sq,'active',:meta)
    ");

    foreach ($quoteLines as $ql) {
        $lIds = generateChildIDs($pdo, 'quote_lines', $q_sys_id);
        $lMeta = buildMetaData(null, $_SESSION['user_name'] ?? 'system');
        $lineStmt->execute([
            ':uuid' => $lIds['uuid'],       ':sid'  => $lIds['sys_id'],
            ':qsid' => $q_sys_id,           ':isid' => $ql['item_sys_id'],
            ':desc' => $ql['description'],  ':qty'  => $ql['qty'],
            ':ccy'  => $ql['source_currency_code'],
            ':sc'   => $ql['source_cost'],  ':ss'   => $ql['source_sell'],
            ':fx'   => $ql['fx_rate'],
            ':cq'   => $ql['cost_quote_ccy'],
            ':sq'   => $ql['sell_quote_ccy'],
            ':meta' => $lMeta,
        ]);
    }

    // Update package active_quote_sys_id
    $pdo->prepare("UPDATE packages SET active_quote_sys_id=? WHERE sys_id=?")
        ->execute([$q_sys_id, $package_sys_id]);

    echo json_encode([
        'success'       => true,
        'action'        => 'generated',
        'sys_id'        => $q_sys_id,
        'version'       => $nextVersion,
        'grand_total'   => round($grandTotal, 2),
        'per_person'    => round($perPerson, 2),
        'sell_currency' => $sellCcy,
        'margin_pct'    => $marginPct,
        'message'       => "Quote v{$nextVersion} generated.",
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
