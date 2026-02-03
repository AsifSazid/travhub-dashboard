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

/* ================= INPUT ================= */
$epsId = $data["eps_id"] ?? '';
$bonus = $data["bonus"] ?? '';
$overtime = $data["overtime"] ?? '';
$otherAllowances = $data["other_allowances"] ?? '';
$pf = $data["pf"] ?? '';
$loan = $data["loan"] ?? '';
$tax = $data["tax"] ?? '';
$fromAccountId = $data["from_account"] ?? '';
$monthOfSalary = $data["month_of_salary"] ?? '';
$note = $data["note"] ?? '';
$paymentDate = $data["date"] ?? date('Y-m-d h:m:s');

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

if (empty($monthOfSalary)) {
    echo json_encode(['success' => false, 'message' => 'Salary of Month is required']);
    exit;
}

try {
    // 1. Begin transaction
    $pdo->beginTransaction();
    
    // 2. collect data from eps structeure table
    /* ================= GET EPS STRUCTURE FROM HERE ================= */
    $epsStmt = $pdo->prepare("
        SELECT * 
        FROM eps_structures 
        WHERE sys_id = ?
        FOR UPDATE
    ");
    $epsStmt->execute([$epsId]);
    $epsData = $epsStmt->fetch(PDO::FETCH_ASSOC);

    if (!$epsData) {
        throw new Exception('EPS - Employee Payroll Structure not found!');
    }
    
    $empId = $epsData['employee_id'];
    $empName = $epsData['employee_name'];
    
    $epsGrossSalary = $epsData['gross_salary'];
    $epsBasicSalary = $epsData['basic_salary'];
    $epsHouseRent = $epsData['house_rent'];
    $epsMedicalAllowance = $epsData['medical_allowance'];
    $epsConveyance = $epsData['conveyance'];
    $epsAllowance = $epsData['allowance'];
    $epsPfDeduct = $epsData['pf_deduction'];
    $epsTaxDeduct = $epsData['tax_deduction'];
    $epsOtherDeduct = $epsData['other_deduction'];
    $epsTotalDeduct = $epsData['total_deductions'];
    
    $epsNetSalary = $epsData['net_salary'];
    
    // 3. Calculation part
    $grossSalary = $epsNetSalary + $bonus + $overtime + $otherAllowances;
    $totalDeducttion = $pf + $loan + $tax;
    $netPayableSalary = $grossSalary - $totalDeducttion;
    
    // 4. JSON Data Ready
    $epsSalary = json_encode([
        'gross_salary'      =>  $epsGrossSalary,
        'basic_salary'      =>  $epsBasicSalary,
        'house_rent'        =>  $epsHouseRent,
        'medical_allowance' =>  $epsMedicalAllowance,
        'conveyance'        =>  $epsConveyance,
        'allowance'         =>  $epsAllowance,
        'pf_deduction'      =>  $epsPfDeduct,
        'tax_deduction'     =>  $epsTaxDeduct,
        'other_deduction'   =>  $epsOtherDeduct,
        'total_deductions'  =>  $epsTotalDeduct
    ]);
    
    $deductions = json_encode([
        'provident'     => $pf,
        'office_loan'   => $loan,
        'tax'           => $tax
    ]);
    
    // 5. Generate uuid, sys_id and meta_data for Payroll
    $uuid = generateIDs('payroll_finals');
    $metaDataJson = buildMetaData(
        null,
        $_SESSION['user_name'] ?? 'system'
    );

    // 6. Insert into payroll_finals table
    $stmt = $pdo->prepare("
        INSERT INTO payroll_finals (
            uuid,
            sys_id,
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
            month,
            status,
            from_account,
            meta_data
        ) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $uuid['uuid'],
        $uuid['sys_id'],
        $epsId,
        $empId,
        $empName,
        $epsSalary,
        $bonus,
        $overtime,
        $otherAllowances,
        $deductions,
        $netPayableSalary,
        $note,
        $paymentDate,
        $monthOfSalary,
        'pending', // Initial status
        $fromAccountId,
        $metaDataJson
    ]);
    
    $salaryId = $pdo->lastInsertId();
    
    // 7. Create a transaction record for the payment (if needed)
    /* ================= FROM ACCOUNT ================= */
    $fromAccStmt = $pdo->prepare("
        SELECT balance, acc_name
        FROM ac_banking 
        WHERE sys_id = ?
        FOR UPDATE
    ");
    $fromAccStmt->execute([$fromAccountId]);
    $fromAccount = $fromAccStmt->fetch(PDO::FETCH_ASSOC);

    if (!$fromAccount) {
        throw new Exception('Account not found');
    }

    if ($fromAccount['balance'] < $netPayableSalary) {
        throw new Exception('Insufficient balance');
    }

    $fromAccountName = $fromAccount['acc_name'];
    $newFromBalance = $fromAccount['balance'] - $netPayableSalary;

    $updateStmt = $pdo->prepare("
        UPDATE ac_banking 
        SET balance = :balance 
        WHERE sys_id = :id
    ");
    $updateStmt->execute([
        ':balance' => $newFromBalance,
        ':id'      => $fromAccountId
    ]);

    /* -------- FROM ACCOUNT STATEMENT -------- */
    $fromUUIDs = generateIDs('ac_banking_stmts');
    $fromMeta  = buildMetaData(null, $_SESSION['user_name'] ?? 'system');

    $stmtInsert = $pdo->prepare("
        INSERT INTO ac_banking_stmts
        (
            uuid, sys_id, ledger_db_id, name, date,
            particular, withdraw, deposit, balance, ref, meta_data
        )
        VALUES
        (
            :uuid, :sys_id, :ledger_db_id, :name, :date,
            :particular, :withdraw, :deposit, :balance,
            :ref, :meta_data
        )
    ");

    $stmtInsert->execute([
        ':uuid'         => $fromUUIDs['uuid'],
        ':sys_id'       => $fromUUIDs['sys_id'],
        ':ledger_db_id' => $fromAccountId,
        ':name'         => $fromAccountName,
        ':date'         => $paymentDate,
        ':particular'   => 'Salary of ' . $empName . ' for ' . $monthOfSalary,
        ':withdraw'     => $netPayableSalary,
        ':deposit'      => 0,
        ':balance'      => $newFromBalance,
        ':ref'          => $uuid['sys_id'],
        ':meta_data'    => $fromMeta
    ]);
    
    
    // 8. Commit transaction
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