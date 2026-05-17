<?php
session_start();

require '../../server/db_connection.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$slipId = $data['slip_id'] ?? '';
$action = $data['action'] ?? '';

if (empty($slipId) || empty($action)) {
    echo json_encode([
        'success' => false,
        'message' => 'Slip ID and action are required'
    ]);
    exit;
}

$allowedActions = ['collect', 'authorize'];

if (!in_array($action, $allowedActions)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid action'
    ]);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT *
        FROM payroll_finals
        WHERE sys_id = ?
        LIMIT 1
        FOR UPDATE
    ");
    $stmt->execute([$slipId]);
    $salary = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$salary) {
        throw new Exception('Salary slip not found');
    }

    $currentStatus = $salary['status'] ?? 'prepared';

    $userInfo = json_encode([
        'user_name' => $_SESSION['user_name'] ?? 'system',
        'designation' => $_SESSION['designation'] ?? '',
        'user_id' => $_SESSION['user_id'] ?? '',
        'date' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);

    if ($action === 'collect') {
        if ($currentStatus !== 'prepared') {
            throw new Exception('Only prepared salary can be collected');
        }

        $update = $pdo->prepare("
            UPDATE payroll_finals
            SET 
                status = 'collected',
                collected_info = ?,
                status = 'collected'
            WHERE sys_id = ?
        ");
        $update->execute([$userInfo, $slipId]);
    }

    if ($action === 'authorize') {
        if ($currentStatus !== 'collected') {
            throw new Exception('Salary must be collected before authorization');
        }

        $update = $pdo->prepare("
            UPDATE payroll_finals
            SET 
                status = 'authorized',
                authorized_info = ?,
                status = 'paid'
            WHERE sys_id = ?
        ");
        $update->execute([$userInfo, $slipId]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Salary flow updated successfully'
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}