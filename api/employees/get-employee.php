
<?php

/**
 * CORS Configuration
 */

$allowedOrigins = [
    'https://travhub.com.bd',
    'https://dev.travhub.com.bd',
    'https://admin.travhub.com.bd'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: {$origin}");
    header("Vary: Origin");
}

header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle Preflight Request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Allow GET only
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

header('Content-Type: application/json');

require '../../server/db_connection.php';

$employeeId = $_GET['employee_id'] ?? '';

if (empty($employeeId)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Employee ID is required'
    ]);
    exit;
}

try {

    $stmt = $pdo->prepare("
        SELECT * FROM employees
        WHERE sys_id = ?
        ORDER BY id ASC
    ");

    $stmt->execute([$employeeId]);

    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'employee' => $employee,
        'success' => true
    ]);

} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);

    exit;
}
