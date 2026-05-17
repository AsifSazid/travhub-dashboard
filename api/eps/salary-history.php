<?php
require '../../server/db_connection.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$epsId = $_GET['eps_id'] ?? '';

if (empty($epsId)) {
    echo json_encode([
        'success' => false,
        'message' => 'EPS ID is required',
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
            eps_salary,
            bonus,
            overtime,
            allowances,
            deduction,
            net_payable_salary,
            note,
            payment_date,
            month AS salary_month,
            status,
            from_account,
            meta_data
        FROM payroll_finals
        WHERE eps_id = ?
        ORDER BY payment_date DESC, id DESC
    ");

    $stmt->execute([$epsId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
        $deduction = json_decode($row['deduction'] ?? '{}', true);
        $row['total_deduction'] = $deduction['total'] ?? 0;

        $row['bonus'] = (float)$row['bonus'];
        $row['overtime'] = (float)$row['overtime'];
        $row['allowances'] = (float)$row['allowances'];
        $row['net_payable_salary'] = (float)$row['net_payable_salary'];
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