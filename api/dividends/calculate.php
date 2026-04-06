<?php
// POST /api/dividends/calculate.php
session_start();

require_once '../../server/db_connection.php';
require_once '../../server/director-calculation.php';

jsonHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed', 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    sendError('Invalid JSON body');
}

$totalProfit = (float)($input['total_profit'] ?? 0);
if ($totalProfit <= 0) {
    sendError('Total profit must be greater than 0');
}

$db = $pdo;

/**
 * 1. Transaction table join korar dorkar nei.
 * 2. Sorasori director_balances theke data nichi.
 */
$query = "
    SELECT 
        d.sys_id, 
        d.name, 
        b.total_investment, 
        b.total_percentage 
    FROM directors d
    JOIN director_balances b ON d.sys_id = b.director_sys_id COLLATE utf8mb4_unicode_ci
    WHERE d.status = 'active'
    ORDER BY d.name ASC
";

$directors = $db->query($query)->fetchAll();

$breakdown = [];
$totalCalculatedDividend = 0;

foreach ($directors as $dir) {
    $inv  = (float)$dir['total_investment'];
    $own  = (float)$dir['total_percentage']; // Balances table theke nichi
    
    // Dividend calculate: (Percentage / 100) * Total Profit
    $div = round(($own / 100) * $totalProfit, 2);
    $totalCalculatedDividend += $div;

    $breakdown[] = [
        'director_sys_id'   => $dir['sys_id'],
        'director_name'     => $dir['name'],
        'investment'        => $inv,
        'ownership_percent' => $own,
        'dividend_amount'   => $div
    ];
}

sendSuccess([
    'total_profit'           => $totalProfit,
    'total_calculated_dividend' => round($totalCalculatedDividend, 2),
    'breakdown'              => $breakdown
]);