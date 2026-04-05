<?php
// GET /api/transactions/get.php?director_id=1

require_once '../../server/db_connection.php';
jsonHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') sendError('Method not allowed', 405);

$directorId = (int)($_GET['director_id'] ?? 0);
if (!$directorId) sendError('director_id is required');

$stmt = $pdo->prepare("
    SELECT id, uuid, type, amount, note, created_at
    FROM director_transactions
    WHERE director_sys_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$directorId]);
$transactions = $stmt->fetchAll();

foreach ($transactions as &$tx) {
    $tx['amount'] = (float)$tx['amount'];
}

sendSuccess($transactions);

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