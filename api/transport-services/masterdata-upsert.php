<?php
/**
 * FILE PATH: /api/transport-services/masterdata-upsert.php
 * Upserts transport route into transport_services masterdata table.
 * Called from ww-transport/quotation.js on Send to Confirmation.
 * POST body: { legs: [{from, to, transfer_type, note}], country_name, country_sys_id? }
 */
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
ob_start();
session_start();
require_once __DIR__ . '/../../server/api_bootstrap.php';
require_once __DIR__ . '/../../server/db_connection.php';
require_once __DIR__ . '/../../server/id_generator.php';
require_once __DIR__ . '/../../server/generate_meta_data.php';

$in            = json_decode(file_get_contents('php://input'), true) ?? [];
$legs          = $in['legs']          ?? [];
$countryName   = trim($in['country_name']   ?? '');
$countrySysId  = trim($in['country_sys_id'] ?? '');
$userName      = $_SESSION['user_name'] ?? 'system';

if (empty($legs)) {
    ob_clean(); echo json_encode(['success'=>false,'message'=>'No legs']); exit;
}

try {
    // ── Auto-resolve country_sys_id ────────────────────────
    if (!$countrySysId && $countryName) {
        $prefix = substr(trim($countryName), 0, 3);
        $cStmt  = $pdo->prepare("SELECT sys_id FROM countries WHERE name LIKE ? LIMIT 1");
        $cStmt->execute([$prefix . '%']);
        $cRow = $cStmt->fetch(PDO::FETCH_ASSOC);
        if ($cRow) $countrySysId = $cRow['sys_id'];
    }

    if (!$countrySysId) {
        ob_clean(); echo json_encode(['success'=>false,'message'=>'Country not found: ' . $countryName]); exit;
    }

    $upserted = [];
    foreach ($legs as $leg) {
        $from = trim($leg['from'] ?? '');
        $to   = trim($leg['to']   ?? '');
        if (!$from || !$to) continue;

        $name = $from . ' → ' . $to;
        $type = ($leg['transfer_type'] ?? '') === 'sic' ? 'shuttle' : 'airport_transfer';

        // Check if route already exists
        $check = $pdo->prepare("SELECT sys_id FROM transport_services WHERE from_city_name=? AND to_city_name=? AND country_sys_id=? LIMIT 1");
        $check->execute([$from, $to, $countrySysId]);
        $existing = $check->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $upserted[] = ['action'=>'existing','sys_id'=>$existing['sys_id'],'name'=>$name];
            continue;
        }

        // Insert new route
        $ids  = generateChildIDs($pdo, 'transport_services', $countrySysId);
        $meta = buildMetaData(null, $userName);
        $pdo->prepare("
            INSERT INTO transport_services
                (uuid, sys_id, country_sys_id, name, type, from_city_name, to_city_name, direction, status, meta_data)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'one_way', 'active', ?)
        ")->execute([
            $ids['uuid'], $ids['sys_id'], $countrySysId,
            $name, $type, $from, $to, $meta
        ]);

        $upserted[] = ['action'=>'created','sys_id'=>$ids['sys_id'],'name'=>$name];
    }

    ob_clean();
    echo json_encode(['success'=>true,'upserted'=>$upserted]);

} catch (Throwable $e) {
    ob_clean();
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}