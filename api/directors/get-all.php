<?php
// GET /api/directors/get-all.php
// Returns all directors with computed investment & ownership

require_once '../../server/db_connection.php';
jsonHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Method not allowed', 405);
}

try {
    // $pdo variable-ti db_connection.php theke ashche
    $sql = "
        SELECT
            d.id,
            d.uuid,
            d.sys_id,
            d.name,
            d.email,
            d.phone,
            d.address,
            d.basic_info,
            d.emergency_contact,
            d.status,
            d.profile_photo,
            COALESCE(
                SUM(CASE WHEN t.type = 'invest' THEN t.amount ELSE 0 END) -
                SUM(CASE WHEN t.type = 'withdraw' THEN t.amount ELSE 0 END),
                0
            ) AS total_investment
        FROM directors d
        LEFT JOIN director_transactions t ON t.director_sys_id = d.id
        GROUP BY d.id
        ORDER BY d.name ASC
    ";

    // Prepare ebong execute
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    // Fetch all results
    $directors = $stmt->fetchAll();

    // Data processing (Loop)
    foreach ($directors as &$dir) {
        $dir['total_investment'] = (float) $dir['total_investment'];
        
        // calcOwnership function-ti dhore nichchi available ache
        $dir['ownership_percent'] = calcOwnership($dir['total_investment']);
        
        // Decode JSON fields (Short-hand condition)
        $dir['basic_info'] = !empty($dir['basic_info']) ? json_decode($dir['basic_info'], true) : null;
        $dir['emergency_contact'] = !empty($dir['emergency_contact']) ? json_decode($dir['emergency_contact'], true) : null;
    }

    // Success response using your helper
    sendSuccess($directors);

} catch (Exception $e) {
    // Error response using your helper
    sendError("Database Error: " . $e->getMessage(), 500);
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