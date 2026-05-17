<?php
session_start();
require '../../server/db_connection.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$userId = $_SESSION['user_id'] ?? '';

if (empty($userId)) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized user',
        'data' => []
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            id,
            uuid,
            sys_id AS slip_id,
            eps_id,
            employee_id,
            employee_name,
            bonus,
            overtime,
            allowances,
            deduction,
            net_payable_salary,
            note,
            payment_date,
            month AS salary_month,
            payment_type,
            payment_components,
            status,
            prepared_info,
            collected_info,
            authorized_info,
            from_account,
            meta_data
        FROM payroll_finals
        WHERE employee_id = ?
        ORDER BY payment_date DESC, id DESC
    ");

    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
        $deduction = json_decode($row['deduction'] ?? '{}', true);
        $row['total_deduction'] = $deduction['total'] ?? 0;
    }

    echo json_encode([
        'success' => true,
        'data' => $rows
    ]);

} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'data' => []
    ]);
}