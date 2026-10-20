<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate');

$dbFile = __DIR__ . '/../../server/db_connection.php';
if (!file_exists($dbFile)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'valid' => false, 'message' => 'Database connection missing.']);
    exit;
}
require_once $dbFile;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'valid' => false, 'message' => 'Invalid request method.']);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

$employeeId = '';
if (is_array($data) && !empty($data['employee_id'])) {
    $employeeId = trim((string)$data['employee_id']);
} elseif (!empty($_POST['employee_id'])) {
    $employeeId = trim((string)$_POST['employee_id']);
}

if ($employeeId === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'valid' => false, 'message' => 'Employee ID is required.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE sys_id = :sys_id LIMIT 1");
    $stmt->execute(['sys_id' => $employeeId]);
    $employee = $stmt->fetch();

    if (!$employee) {
        echo json_encode(['success' => false, 'valid' => false, 'message' => 'Invalid Employee ID.']);
        exit;
    }

    if (isset($employee['status']) && strtolower((string)$employee['status']) === 'inactive') {
        echo json_encode(['success' => false, 'valid' => false, 'message' => 'Account is inactive.']);
        exit;
    }

    // ⭐ এই সেশন ভেরিয়েবলগুলো সেট হচ্ছে
    $_SESSION['new_system_verified'] = true;
    $_SESSION['employee_sys_id']     = $employee['sys_id'];
    $_SESSION['employee_name']       = $employee['name'] ?? '';

    echo json_encode([
        'success' => true,
        'valid'   => true,
        'message' => 'Verified successfully.'
    ]);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'valid' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
}