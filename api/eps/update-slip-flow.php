<?php
// PATH: /api/eps/update-slip-flow.php
//
// ============= REDESIGNED WORKFLOW (per user's real-world process) =============
// prepared -> authorized -> collected -> verified -> paid
//
//   prepared   generate-salary.php created the slip. No money has moved.
//   authorized An Authorization Letter was sent to the bank, instructing it
//              to credit the employee. Still no money moved from OUR side --
//              this just means the bank has been asked to act.
//   collected  The EMPLOYEE confirms money landed in their account (the bank
//              acted on the letter).
//   verified   The COMPANY (accounts/HR) verifies the employee's claim is
//              correct before treating it as final.
//   paid       DISBURSEMENT. Only NOW does the company's own account
//              actually get debited -- this is the only step that touches
//              ac_banking / ac_banking_stmts / financial_entries. Everything
//              before this is just workflow tracking; the money movement
//              intentionally happens last, once verified.
//
// This matches the real sequence: the company doesn't debit its own books
// until it has independently verified the bank actually paid the employee.

session_start();

require '../../server/db_connection.php';
require '../../server/uuid_with_system_id_generator.php';
require_once '../../server/sys_id_generator_v2.php';
require '../../server/generate_meta_data.php';
require_once '../../server/finance_helpers.php'; // isInstrumentMethod(), postBankLegV2()

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
$paymentMethod = strtolower($data['payment_method'] ?? 'cash'); // only relevant for the disburse action
$instrumentNo  = $data['instrument_no'] ?? null;

if (empty($slipId) || empty($action)) {
    echo json_encode([
        'success' => false,
        'message' => 'Slip ID and action are required'
    ]);
    exit;
}

$allowedActions = ['authorize', 'collect', 'verify', 'disburse'];

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

    if ($action === 'authorize') {
        if ($currentStatus !== 'prepared') {
            throw new Exception('Only a prepared salary can be authorized');
        }

        // NOTE: authorized_info column already exists from before. No money
        // moves here -- this only records that an Authorization Letter was
        // sent to the bank.
        $update = $pdo->prepare("
            UPDATE payroll_finals
            SET 
                status = 'authorized',
                authorized_info = ?
            WHERE sys_id = ?
        ");
        $update->execute([$userInfo, $slipId]);
    }

    if ($action === 'collect') {
        if ($currentStatus !== 'authorized') {
            throw new Exception('Salary must be authorized before it can be marked as collected');
        }

        // The EMPLOYEE is confirming money landed in their account. Still
        // no movement on the company's own books.
        $update = $pdo->prepare("
            UPDATE payroll_finals
            SET 
                status = 'collected',
                collected_info = ?
            WHERE sys_id = ?
        ");
        $update->execute([$userInfo, $slipId]);
    }

    if ($action === 'verify') {
        if ($currentStatus !== 'collected') {
            throw new Exception('Salary must be collected before it can be verified');
        }

        // NOTE: verified_info is a new column -- verify payroll_finals has
        // it before deploying this (ALTER TABLE payroll_finals ADD COLUMN
        // verified_info TEXT NULL if it doesn't exist yet).
        $update = $pdo->prepare("
            UPDATE payroll_finals
            SET 
                status = 'verified',
                verified_info = ?
            WHERE sys_id = ?
        ");
        $update->execute([$userInfo, $slipId]);
    }

    if ($action === 'disburse') {
        if ($currentStatus !== 'verified') {
            throw new Exception('Salary must be verified before disbursement');
        }

        // ================= DISBURSEMENT — the only step that moves money =================
        $fromAccountId     = $salary['from_account'];
        $netPayableSalary  = (float)$salary['net_payable_salary'];
        $empName           = $salary['employee_name'];
        $empId             = $salary['employee_id'];
        $monthOfSalary     = $salary['month'];
        $paymentType       = $salary['payment_type'] ?? 'salary';
        $paymentDate       = date('Y-m-d H:i:s'); // disbursement happens now, not the originally-planned payment_date
        $userName          = $_SESSION['user_name'] ?? 'system';
        $particularType    = ucfirst(str_replace('_', ' ', $paymentType));

        $accStmt = $pdo->prepare("SELECT acc_name, balance FROM ac_banking WHERE sys_id = ? FOR UPDATE");
        $accStmt->execute([$fromAccountId]);
        $accountInfo = $accStmt->fetch(PDO::FETCH_ASSOC);
        if (!$accountInfo) throw new Exception('Payment account not found');

        $fromAccountName = $accountInfo['acc_name'];
        $currentBalance  = (float)$accountInfo['balance'];

        postBankLegV2($pdo, $fromAccountId, $fromAccountName, $currentBalance, $netPayableSalary, 'out', $paymentDate,
            $particularType . ' payment of ' . $empName . ' for ' . $monthOfSalary, $slipId, $userName,
            $paymentMethod, $empId, $empName, 'account', $instrumentNo);

        // ── financial_entries double-entry mirror (Phase 4 integration) ──
        function _insertPayrollFinancialEntry(PDO $pdo, array $f): string
        {
            $ids2 = generateV2IDs($pdo, 'financial_entries');
            $meta2 = buildMetaData(null, $f['user_name_actor']);
            $pdo->prepare("
                INSERT INTO financial_entries (
                    uuid, sys_id, transaction_group_id,
                    user_sys_id, user_name, user_type, account_head, vendor_type,
                    date, purpose, type, related_type,
                    is_paid, is_partial, is_discounted,
                    amount, qty_rate, ref, meta_data
                ) VALUES (
                    :uuid, :sys_id, :group_id,
                    :user_sys_id, :user_name, :user_type, :account_head, :vendor_type,
                    :date, :purpose, :type, :related_type,
                    1, 0, 0,
                    :amount, NULL, :ref, :meta_data
                )
            ")->execute([
                ':uuid' => $ids2['uuid'], ':sys_id' => $ids2['sys_id'], ':group_id' => $f['group_id'],
                ':user_sys_id' => $f['user_sys_id'], ':user_name' => $f['user_name'], ':user_type' => $f['user_type'],
                ':account_head' => $f['account_head'], ':vendor_type' => $f['vendor_type'],
                ':date' => $f['date'], ':purpose' => $f['purpose'], ':type' => $f['type'], ':related_type' => $f['related_type'],
                ':amount' => $f['amount'], ':ref' => $f['ref'], ':meta_data' => $meta2,
            ]);
            return $ids2['sys_id'];
        }

        $payrollGroupId = generateV2SysId($pdo, 'financial_entries');
        $payrollPurpose = $particularType . ' — ' . $empName . ' (' . $monthOfSalary . ')';
        $payrollBaseRow = ['date' => $paymentDate, 'purpose' => $payrollPurpose, 'ref' => $slipId, 'user_name_actor' => $userName, 'group_id' => $payrollGroupId];

        _insertPayrollFinancialEntry($pdo, array_merge($payrollBaseRow, [
            'user_sys_id' => $empId, 'user_name' => $empName, 'user_type' => 'account',
            'account_head' => 'payroll_expense', 'vendor_type' => null,
            'type' => 'debit', 'related_type' => 2, 'amount' => $netPayableSalary,
        ]));
        _insertPayrollFinancialEntry($pdo, array_merge($payrollBaseRow, [
            'user_sys_id' => $fromAccountId, 'user_name' => $fromAccountName, 'user_type' => 'account',
            'account_head' => 'bank_account', 'vendor_type' => 1,
            'type' => 'credit', 'related_type' => 2, 'amount' => $netPayableSalary,
        ]));

        // NOTE: disbursed_info is a new column -- verify payroll_finals has
        // it before deploying this (ALTER TABLE payroll_finals ADD COLUMN
        // disbursed_info TEXT NULL if it doesn't exist yet).
        $update = $pdo->prepare("
            UPDATE payroll_finals
            SET 
                status = 'paid',
                disbursed_info = ?
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