<?php
// Allow requests from your specific frontend domain
header("Access-Control-Allow-Origin: https://travhub.com.bd");

// Allow specific methods (GET is what you're using)
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

// Allow specific headers if your frontend sends them (like Content-Type or Authorization)
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle Preflight (OPTIONS) requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}

require '../../server/db_connection.php';

header('Content-Type: application/json'); // Tell the client this is JSON

$employeeId = $_GET['employee_id'];

try {
    $stmt = $pdo->prepare("
        SELECT * FROM employees
        WHERE sys_id =?
        ORDER BY id ASC
    ");
    $stmt->execute([$employeeId]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['employee' => $employee,  'success' => true]); // Send JSON to the client
} catch (Exception $e) {
    // Return error as JSON too
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
