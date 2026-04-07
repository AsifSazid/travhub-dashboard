<?php
// POST /api/dividends/disburse.php
session_start();

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

// 1. Fetch data directly from director_balances for accuracy
$query = "
    SELECT 
        d.id, d.name, d.sys_id, 
        b.total_investment, 
        b.total_percentage 
    FROM directors d
    JOIN director_balances b ON d.sys_id = b.director_sys_id COLLATE utf8mb4_unicode_ci
    WHERE d.status = 'active'
";

$directors = $db->query($query)->fetchAll();

if (!$directors) sendError('No active directors found', 404);

$db->beginTransaction();

try {
    // Generate Main Dividend ID
    $dividendIds = generateIDs('director_dividends');
    $meta = buildMetaData(null, $_SESSION['user_name'] ?? 'system');

    // 2. Insert into main dividends table
    $stmtMain = $db->prepare("
        INSERT INTO director_dividends (uuid, sys_id, total_profit, note, meta_data) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmtMain->execute([
        $dividendIds['uuid'], 
        $dividendIds['sys_id'], 
        $totalProfit, 
        $note, 
        json_encode($meta)
    ]);

    // 3. Prepare detail statement
    $detailStmt = $db->prepare("
        INSERT INTO dividend_details 
            (uuid, sys_id, dividend_sys_id, director_sys_id, director_name, investment, ownership_percent, amount, meta_data) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $breakdown = [];
    foreach ($directors as $dir) {
        // ID generation for each detail row
        $detailIds = generateIDs('director_dividend_details');
        
        $inv = (float)$dir['total_investment'];
        $own = (float)$dir['total_percentage']; // Using pre-calculated percentage from DB
        
        // Final dividend calculation
        $amt = round(($own / 100) * $totalProfit, 2);

        $detailStmt->execute([
            $detailIds['uuid'], 
            $detailIds['sys_id'], 
            $dividendIds['sys_id'], 
            $dir['sys_id'], 
            $dir['name'], 
            $inv, 
            $own, 
            $amt,
            json_encode($meta)
        ]);

        $breakdown[] = [
            'director_sys_id'   => $dir['sys_id'],
            'director_name'     => $dir['name'],
            'investment'        => $inv,
            'ownership_percent' => $own,
            'dividend_amount'   => $amt
        ];
    }

    $db->commit();

    sendSuccess([
        'dividend_sys_id' => $dividendIds['sys_id'],
        'total_profit'    => $totalProfit,
        'breakdown'       => $breakdown
    ], 201);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    sendError('Disburse failed: ' . $e->getMessage(), 500);
}