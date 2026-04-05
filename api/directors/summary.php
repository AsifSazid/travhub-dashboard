<?php
// GET /api/directors/summary.php
require_once '../../server/db_connection.php';
jsonHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Method not allowed', 405);
}

try {
    // $pdo variable-ti db_connection.php theke ashche
    $sql = "
        SELECT 
            COUNT(DISTINCT d.id) AS total_directors,
            COALESCE(
                SUM(CASE WHEN t.type='invest' THEN t.amount ELSE 0 END) - 
                SUM(CASE WHEN t.type='withdraw' THEN t.amount ELSE 0 END), 
            0) AS total_investment
        FROM directors d
        LEFT JOIN director_transactions t ON t.director_sys_id = d.id
        WHERE d.status = 'active'
    ";

    // Prepare ebong Execute (Prepared statement style)
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $row = $stmt->fetch(); // Default FETCH_ASSOC dewa ache connection-e

    if (!$row) {
        sendError('No data found', 404);
    }

    $totalInv   = (float)$row['total_investment'];
    $totalDirs  = (int)$row['total_directors'];
    
    // calcOwnership function-ti dhore nichchi upore define kora ache
    $totalOwn   = calcOwnership($totalInv);

    // Tomar sendSuccess helper bebohar kora holo
    sendSuccess([
        'total_directors'  => $totalDirs,
        'total_investment' => $totalInv,
        'total_ownership'  => $totalOwn,
        'base_unit'        => 12000,
        'base_ownership'   => 12.5
    ]);

} catch (Exception $e) {
    // Tomar sendError helper bebohar kora holo
    sendError($e->getMessage(), 500);
}

// Ownership formula: (investment / 12000) * 12.5
function calcOwnership(float $investment): float {
    if ($investment <= 0) return 0.0;
    return round(($investment / 12000) * 12.5, 4);
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