<?php
session_start();

require '../../server/db_connection.php';
require '../../server/uuid_with_system_id_generator.php';
require '../../server/generate_meta_data.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

error_reporting(E_ALL);
ini_set('display_errors', 1);

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit;
}

$epsId = trim($data['eps_id'] ?? '');
$fromAccountId = trim($data['from_account'] ?? '');
$monthOfSalary = trim($data['month_of_salary'] ?? '');
$note = trim($data['note'] ?? '');
$paymentDate = !empty($data['date']) ? $data['date'] : date('Y-m-d H:i:s');

$paymentType = $data['payment_type'] ?? 'salary';
$includeBaseSalary = !empty($data['include_base_salary']);

$bonus = (float)($data['bonus'] ?? 0);
$overtime = (float)($data['overtime'] ?? 0);
$otherAllowances = (float)($data['other_allowances'] ?? 0);
$pf = (float)($data['pf'] ?? 0);
$loan = (float)($data['loan'] ?? 0);
$tax = (float)($data['tax'] ?? 0);

if ($epsId === '') {
    echo json_encode(['success' => false, 'message' => 'EPS ID is required']);
    exit;
}

if ($fromAccountId === '') {
    echo json_encode(['success' => false, 'message' => 'Payment account is required']);
    exit;
}

if ($monthOfSalary === '') {
    echo json_encode(['success' => false, 'message' => 'Payment month/reference month is required']);
    exit;
}

try {
    $pdo->beginTransaction();

    $epsStmt = $pdo->prepare("
        SELECT * 
        FROM eps_structures 
        WHERE sys_id = ?
        LIMIT 1
        FOR UPDATE
    ");
    $epsStmt->execute([$epsId]);
    $epsData = $epsStmt->fetch(PDO::FETCH_ASSOC);

    if (!$epsData) {
        throw new Exception('EPS - Employee Payroll Structure not found!');
    }

    $empId = $epsData['employee_id'];
    $empName = $epsData['employee_name'];

    $epsGrossSalary = (float)$epsData['gross_salary'];
    $epsBasicSalary = (float)$epsData['basic_salary'];
    $epsHouseRent = (float)$epsData['house_rent'];
    $epsMedicalAllowance = (float)$epsData['medical_allowance'];
    $epsConveyance = (float)$epsData['conveyance'];
    $epsAllowance = (float)$epsData['allowance'];
    $epsPfDeduct = (float)$epsData['pf_deduction'];
    $epsTaxDeduct = (float)$epsData['tax_deduction'];
    $epsOtherDeduct = (float)$epsData['other_deduction'];
    $epsTotalDeduct = (float)$epsData['total_deductions'];
    $epsNetSalary = (float)$epsData['net_salary'];

    $baseSalaryPayable = $includeBaseSalary ? $epsNetSalary : 0;

    $grossSalary = $baseSalaryPayable + $bonus + $overtime + $otherAllowances;
    $totalDeduction = $pf + $loan + $tax;
    $netPayableSalary = $grossSalary - $totalDeduction;

    if ($netPayableSalary <= 0) {
        throw new Exception('Net payable amount must be greater than 0');
    }

    $fromAccStmt = $pdo->prepare("
        SELECT sys_id, balance, acc_name
        FROM ac_banking 
        WHERE sys_id = ?
        LIMIT 1
        FOR UPDATE
    ");
    $fromAccStmt->execute([$fromAccountId]);
    $fromAccount = $fromAccStmt->fetch(PDO::FETCH_ASSOC);

    if (!$fromAccount) {
        throw new Exception('Payment account not found');
    }

    $currentBalance = (float)$fromAccount['balance'];

    if ($currentBalance < $netPayableSalary) {
        throw new Exception('Insufficient balance');
    }

    $fromAccountName = $fromAccount['acc_name'];
    $newFromBalance = $currentBalance - $netPayableSalary;

    $epsSalary = json_encode([
        'gross_salary' => $epsGrossSalary,
        'basic_salary' => $epsBasicSalary,
        'house_rent' => $epsHouseRent,
        'medical_allowance' => $epsMedicalAllowance,
        'conveyance' => $epsConveyance,
        'allowance' => $epsAllowance,
        'pf_deduction' => $epsPfDeduct,
        'tax_deduction' => $epsTaxDeduct,
        'other_deduction' => $epsOtherDeduct,
        'total_deductions' => $epsTotalDeduct,
        'net_salary' => $epsNetSalary
    ], JSON_UNESCAPED_UNICODE);

    $paymentComponents = json_encode([
        'payment_type' => $paymentType,
        'include_base_salary' => $includeBaseSalary ? 1 : 0,
        'base_salary' => $baseSalaryPayable,
        'bonus' => $bonus,
        'overtime' => $overtime,
        'other_allowances' => $otherAllowances,
        'pf' => $pf,
        'loan' => $loan,
        'tax' => $tax,
        'gross_amount' => $grossSalary,
        'total_deduction' => $totalDeduction,
        'net_payable' => $netPayableSalary
    ], JSON_UNESCAPED_UNICODE);

    $deductions = json_encode([
        'provident' => $pf,
        'office_loan' => $loan,
        'tax' => $tax,
        'total' => $totalDeduction
    ], JSON_UNESCAPED_UNICODE);

    $userName = $_SESSION['user_name'] ?? 'system';
    $designation = $_SESSION['designation'] ?? '';
    $userId = $_SESSION['user_id'] ?? '';

    $preparedInfo = json_encode([
        'user_name' => $userName,
        'designation' => $designation,
        'user_id' => $userId,
        'date' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);

    $ids = generateIDs('payroll_finals');
    $metaDataJson = buildMetaData(null, $userName);

    $payrollStmt = $pdo->prepare("
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
            payment_type,
            payment_components,
            status,
            prepared_info,
            from_account,
            meta_data
        ) VALUES (
            :uuid,
            :sys_id,
            :eps_id,
            :employee_id,
            :employee_name,
            :eps_salary,
            :bonus,
            :overtime,
            :allowances,
            :deduction,
            :net_payable_salary,
            :note,
            :payment_date,
            :month,
            :payment_type,
            :payment_components,
            :status,
            :prepared_info,
            :from_account,
            :meta_data
        )
    ");

    $payrollStmt->execute([
        ':uuid' => $ids['uuid'],
        ':sys_id' => $ids['sys_id'],
        ':eps_id' => $epsId,
        ':employee_id' => $empId,
        ':employee_name' => $empName,
        ':eps_salary' => $epsSalary,
        ':bonus' => $bonus,
        ':overtime' => $overtime,
        ':allowances' => $otherAllowances,
        ':deduction' => $deductions,
        ':net_payable_salary' => $netPayableSalary,
        ':note' => $note,
        ':payment_date' => $paymentDate,
        ':month' => $monthOfSalary,
        ':payment_type' => $paymentType,
        ':payment_components' => $paymentComponents,
        ':status' => 'prepared',
        ':prepared_info' => $preparedInfo,
        ':from_account' => $fromAccountId,
        ':meta_data' => $metaDataJson
    ]);

    $salaryId = $pdo->lastInsertId();

    $updateBankStmt = $pdo->prepare("
        UPDATE ac_banking 
        SET balance = :balance 
        WHERE sys_id = :sys_id
    ");
    $updateBankStmt->execute([
        ':balance' => $newFromBalance,
        ':sys_id' => $fromAccountId
    ]);

    if ($updateBankStmt->rowCount() < 1) {
        throw new Exception('Failed to update bank balance');
    }

    $stmtIds = generateIDs('ac_banking_stmts');
    $stmtMeta = buildMetaData(null, $userName);

    $particularType = ucfirst(str_replace('_', ' ', $paymentType));

    $bankStmt = $pdo->prepare("
        INSERT INTO ac_banking_stmts (
            uuid,
            sys_id,
            ledger_db_id,
            name,
            date,
            particular,
            withdraw,
            deposit,
            balance,
            ref,
            meta_data
        ) VALUES (
            :uuid,
            :sys_id,
            :ledger_db_id,
            :name,
            :date,
            :particular,
            :withdraw,
            :deposit,
            :balance,
            :ref,
            :meta_data
        )
    ");

    $bankStmt->execute([
        ':uuid' => $stmtIds['uuid'],
        ':sys_id' => $stmtIds['sys_id'],
        ':ledger_db_id' => $fromAccountId,
        ':name' => $fromAccountName,
        ':date' => $paymentDate,
        ':particular' => $particularType . ' payment of ' . $empName . ' for ' . $monthOfSalary,
        ':withdraw' => $netPayableSalary,
        ':deposit' => 0,
        ':balance' => $newFromBalance,
        ':ref' => $ids['sys_id'],
        ':meta_data' => $stmtMeta
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Payment generated successfully',
        'salary_id' => $salaryId,
        'slip_id' => $ids['sys_id'],
        'data' => [
            'employee_name' => $empName,
            'employee_id' => $empId,
            'payment_type' => $paymentType,
            'include_base_salary' => $includeBaseSalary,
            'month' => $monthOfSalary,
            'payment_date' => $paymentDate,
            'base_salary' => $baseSalaryPayable,
            'gross_amount' => $grossSalary,
            'total_deduction' => $totalDeduction,
            'net_payable_salary' => $netPayableSalary,
            'payment_account' => $fromAccountName . ' (' . $fromAccountId . ')',
            'previous_balance' => $currentBalance,
            'new_balance' => $newFromBalance,
            'status' => 'prepared'
        ]
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}