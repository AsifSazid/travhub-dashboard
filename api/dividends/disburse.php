<?php
// POST /api/dividends/disburse.php
// Saves the dividend and its per-director details

require_once '../../server/db_connection.php';
require_once '../../server/director-calculation.php';
require '../../server/uuid_with_system_id_generator.php';
require '../../server/generate_meta_data.php';
jsonHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendError('Method not allowed', 405);

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) sendError('Invalid JSON body');

$totalProfit = (float)($input['total_profit'] ?? 0);
$note        = trim($input['note'] ?? '');
if ($totalProfit <= 0) sendError('total_profit must be greater than 0');

$db = $pdo;

$directors = $db->query("
    SELECT
        d.id, d.name, d.sys_id,
        COALESCE(
            SUM(CASE WHEN t.type='invest'   THEN t.amount ELSE 0 END) -
            SUM(CASE WHEN t.type='withdraw' THEN t.amount ELSE 0 END),
        0) AS total_investment
    FROM directors d
    LEFT JOIN director_transactions t ON t.director_sys_id = d.id
    WHERE d.status = 'active'
    GROUP BY d.id
")->fetchAll();

if (!$directors) sendError('No active directors found', 404);

$db->beginTransaction();
try {
    // Generate IDs
    $devidendIds = generateIDs('director_devidends');
    
    // Build meta_data
    $meta = buildMetaData(null, $_SESSION['user_name'] ?? 'system');
    $db->prepare("
        INSERT INTO director_devidends (uuid, sys_id, total_profit, note, meta_data) VALUES (?, ?, ?, ?, ?)
    ")->execute([$devidendIds['uuid'], $devidendIds['sys_id'], $totalProfit, $note, json_encode($meta)]);

    $detailStmt = $db->prepare("
        INSERT INTO dividend_details
            (uuid, sys_id, dividend_sys_id, director_sys_id, director_name, investment, ownership_percent, amount)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $breakdown = [];
    foreach ($directors as $dir) {
        // generateIDs
        $devidendDetailsIds = generateIDs('director_devidend_details');
        $inv = (float)$dir['total_investment'];
        $own = calcOwnership($inv);
        $amt = round(($own / 100) * $totalProfit, 2);
        $detailStmt->execute([$devidendDetailsIds['uuid'], $devidendDetailsIds['sys_id'], $devidendIds['sys_id'], $dir['sys_id'], $dir['name'], $inv, $own, $amt]);
        $breakdown[] = [
            'director_id'       => (int)$dir['id'],
            'director_name'     => $dir['name'],
            'investment'        => $inv,
            'ownership_percent' => $own,
            'dividend_amount'   => $amt
        ];
    }

    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    sendError('Disburse failed: ' . $e->getMessage(), 500);
}

sendSuccess([
    'dividend_id'  => $devidendIds['sys_id'],
    'dividend_uuid'=> $devidendIds['uuid'],
    'total_profit' => $totalProfit,
    'breakdown'    => $breakdown
], 201);