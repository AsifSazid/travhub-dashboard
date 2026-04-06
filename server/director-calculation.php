<?php

$basePrice = @file_get_contents('../../base-price.txt');
$cleanBasePrice = str_replace(',', '', $basePrice);

// Ownership formula: (investment / $cleanBasePrice) * 12.5
function calcOwnership(float $investment): float {
    global $cleanBasePrice;
    if ($investment <= 0) return 0.0;
    return round(($investment / $cleanBasePrice) * 12.5, 4);
}

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