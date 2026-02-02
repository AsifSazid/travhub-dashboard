<?php
require '../../server/db_connection.php';
require '../../server/uuid_with_system_id_generator.php';
require '../../server/generate_meta_data.php';
require '../../server/make-dir.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session for user data
session_start();

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit;
}

// Validate required fields
if (empty($data['eps_id'])) {
    echo json_encode(['success' => false, 'message' => 'EPS ID is required']);
    exit;
}

if (empty($data['from_account'])) {
    echo json_encode(['success' => false, 'message' => 'Payment account is required']);
    exit;
}

try {
    // Begin transaction
    $pdo->beginTransaction();
    
    var_dump($data);
    
    // 9. Insert into payroll_finals table
    $stmt = $pdo->prepare("
        INSERT INTO payroll_finals (
            uuid,
            system_id,
            eps_id,
            employee_id,
            employee_name,
            basic_salary,
            house_rent,
            medical_allowance,
            conveyance,
            bonus,
            overtime,
            deduction,
            gross_salary,
            net_salary,
            note,
            payment_date,
            month,
            status,
            from_account,
            meta_data
        ) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $uuid['uuid'],
        $uuid['sys_id'],
        $data['eps_id'],
        $epsDetails['employee_id'],
        $epsDetails['employee_name'],
        $basic_salary,
        $house_rent,
        $medical_allowance,
        $conveyance,
        $bonus,
        $overtime,
        json_encode($deductions),
        $gross_salary,
        $net_salary,
        $note,
        $payment_date,
        $current_month,
        'pending', // Initial status
        $data['from_account'],
        $metaDataJson
    ]);
    
    $salaryId = $pdo->lastInsertId();
    
    // 10. Create a transaction record for the payment (if needed)
    // You can add transaction recording logic here if required
    
    // 11. Commit transaction
    $pdo->commit();
    
    // Prepare response data
    $response = [
        'success' => true,
        'message' => 'Salary generated successfully',
        'salary_id' => $salaryId,
        'slip_id' => $uuid['sys_id'],
        'data' => [
            'employee_name' => $epsDetails['employee_name'],
            'employee_id' => $epsDetails['employee_id'],
            'month' => $current_month,
            'payment_date' => $payment_date,
            'basic_salary' => $basic_salary,
            'total_allowance' => $total_allowance,
            'gross_salary' => $gross_salary,
            'additions' => $additions,
            'deductions' => $deductions,
            'net_salary' => $net_salary,
            'status' => 'pending',
            'payment_account' => $account['acc_name'] . ' (' . $account['sys_id'] . ')'
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}