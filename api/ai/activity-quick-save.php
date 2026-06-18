<?php
/**
 * api/ai/activity-quick-save.php
 * POST { country_sys_id, name, category, duration_hours, currency_code, net_cost, sell_price, price_basis }
 * Saves an AI-suggested item as a real activity in masterdata (with one variant).
 * Returns { success, sys_id, variant_sys_id }
 */
session_start();
require_once '../../server/api_bootstrap.php';
require_once '../../server/db_connection.php';
require_once '../../server/id_generator.php';
require_once '../../server/generate_meta_data.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST only']); exit;
}

$in             = json_decode(file_get_contents('php://input'), true) ?: [];
$country_sys_id = trim($in['country_sys_id'] ?? '');
$name           = trim($in['name']           ?? '');
$category       = trim($in['category']       ?? '');
$duration_hours = (float)($in['duration_hours'] ?? 0);
$currency_code  = strtoupper(trim($in['currency_code'] ?? 'BDT'));
$net_cost       = (float)($in['net_cost']    ?? 0);
$sell_price     = (float)($in['sell_price']  ?? 0);
$price_basis    = in_array($in['price_basis'] ?? '', ['per_pax','per_group','per_vehicle','per_day','per_person'])
                    ? $in['price_basis'] : 'per_pax';

if (!$country_sys_id) {
    echo json_encode(['success' => false, 'message' => 'country_sys_id required']); exit;
}
if (!$name) {
    echo json_encode(['success' => false, 'message' => 'name required']); exit;
}

try {
    // ── Check duplicate name in this country ─────────────────────────
    $dup = $pdo->prepare("SELECT sys_id FROM activities WHERE country_sys_id=? AND name=? AND status='active' LIMIT 1");
    $dup->execute([$country_sys_id, $name]);
    $existing = $dup->fetchColumn();
    if ($existing) {
        echo json_encode(['success' => true, 'sys_id' => $existing, 'variant_sys_id' => '', 'already_exists' => true]);
        exit;
    }

    // ── Fetch country name ────────────────────────────────────────────
    $cRow = $pdo->prepare("SELECT name FROM countries WHERE sys_id=? LIMIT 1");
    $cRow->execute([$country_sys_id]);
    $country_name = $cRow->fetchColumn() ?: '';

    $meta    = buildMetaData(null, $_SESSION['user_name'] ?? 'ai-system');
    $ids     = generateChildIDs($pdo, 'activities', $country_sys_id);
    $act_sid = $ids['sys_id'];
    $act_uuid = $ids['uuid'];

    $pdo->prepare("INSERT INTO activities
        (uuid, sys_id, country_sys_id, country_name, name, category, duration_hours,
         type, status, popularity, meta_data)
        VALUES (?,?,?,?,?,?,?, 'tour', 'active', 3, ?)")
    ->execute([$act_uuid, $act_sid, $country_sys_id, $country_name,
               $name, $category ?: null, $duration_hours, $meta]);

    // ── Create one default variant ────────────────────────────────────
    $vids    = generateChildIDs($pdo, 'activity_variants', $act_sid);
    $var_sid = $vids['sys_id'];
    $var_uuid = $vids['uuid'];

    $pdo->prepare("INSERT INTO activity_variants
        (uuid, sys_id, activity_sys_id, variant_name, currency_code, net_cost, sell_price,
         price_basis, status, meta_data)
        VALUES (?,?,?, 'Standard', ?,?,?, ?,  'active', ?)")
    ->execute([$var_uuid, $var_sid, $act_sid,
               $currency_code, $net_cost, $sell_price ?: $net_cost,
               $price_basis, $meta]);

    echo json_encode([
        'success'     => true,
        'sys_id'      => $act_sid,
        'variant_sys_id' => $var_sid,
        'already_exists' => false,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}