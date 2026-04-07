<?php
// GET /api/dividends/history.php

require_once '../../server/db_connection.php';
jsonHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') sendError('Method not allowed', 405);

$dividends = $pdo->query("
SELECT 
    d.id, d.sys_id, d.uuid, d.total_profit, d.note, d.created_at,
        COUNT(dd.id) AS director_count,
        SUM(dd.amount) AS total_distributed
    FROM director_dividends d
    LEFT JOIN dividend_details dd 
        ON dd.dividend_sys_id = d.sys_id
    GROUP BY d.id
    ORDER BY d.created_at DESC
    LIMIT 50
")->fetchAll();

// var_dump($dividends);
// die;

foreach ($dividends as &$div) {
    $div['total_profit']      = (float)$div['total_profit'];
    $div['total_distributed'] = (float)($div['total_distributed'] ?? 0);
    $div['director_count']    = (int)$div['director_count'];
}

sendSuccess($dividends);

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