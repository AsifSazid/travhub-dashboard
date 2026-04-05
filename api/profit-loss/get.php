<?php
// GET /api/profit-loss/get.php?period=monthly  (daily|weekly|monthly|yearly)

require_once '../../server/db_connection.php';
jsonHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') sendError('Method not allowed', 405);

$period = $_GET['period'] ?? 'monthly';
$allowed = ['daily', 'weekly', 'monthly', 'yearly'];
if (!in_array($period, $allowed)) sendError('Invalid period');

// Build grouping expression
$groupExpr = match($period) {
    'daily'   => "DATE_FORMAT(date, '%Y-%m-%d')",
    'weekly'  => "YEARWEEK(date, 1)",
    'monthly' => "DATE_FORMAT(date, '%Y-%m')",
    'yearly'  => "YEAR(date)",
};

$stmt = $pdo->query("
    SELECT
        {$groupExpr} AS period_key,
        SUM(CASE WHEN type='profit' THEN amount ELSE 0 END) AS total_profit,
        SUM(CASE WHEN type='loss'   THEN amount ELSE 0 END) AS total_loss,
        SUM(CASE WHEN type='profit' THEN amount ELSE -amount END) AS net
    FROM profit_loss
    GROUP BY period_key
    ORDER BY period_key ASC
");

$rows = $stmt->fetchAll();
foreach ($rows as &$r) {
    $r['total_profit'] = (float)$r['total_profit'];
    $r['total_loss']   = (float)$r['total_loss'];
    $r['net']          = (float)$r['net'];
}

// Also return raw records
$raw = $pdo->query("
    SELECT id, uuid, type, amount, note, date, created_at
    FROM profit_loss
    ORDER BY date DESC
    LIMIT 100
")->fetchAll();

foreach ($raw as &$r) {
    $r['amount'] = (float)$r['amount'];
}

sendSuccess(['grouped' => $rows, 'records' => $raw]);

// Shared helper — success response
function sendSuccess(mixed $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode(['status' => 'success', 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

// Shared helper — error response
function sendError(string $message, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['status' => 'error', 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonHeaders(): void {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
}