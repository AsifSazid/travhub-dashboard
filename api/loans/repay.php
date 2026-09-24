<?php
// PATH: /api/loans/repay.php
//
// Records a loan repayment -- either against a specific EMI installment, or
// as a free-form payment (per the user: both modes must be supported).
// This is the ONLY point (besides disbursement) where a loan touches
// ac_banking/ac_banking_stmts, and ONLY if a 'company' party is on either
// side of the loan.
//
// POST {
//   loan_sys_id,
//   installment_sys_id?  -- required if paying a specific EMI installment;
//                            omit for a free-form payment
//   amount, date,
//   account_id?           -- required if a 'company' party is involved
//   payment_method?, instrument_no?
// }

session_start();

require '../../server/db_connection.php';
require_once '../../server/permissions.php';
requireFullAccountingAccess($pdo, true);
require '../../server/uuid_with_system_id_generator.php';
require_once '../../server/sys_id_generator_v2.php';
require '../../server/generate_meta_data.php';
require_once '../../server/finance_helpers.php'; // postBankLegV2()

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $loanSysId        = trim($input['loan_sys_id'] ?? '');
    $installmentSysId = trim($input['installment_sys_id'] ?? '');
    $amount           = (float)($input['amount'] ?? 0);
    $date             = $input['date'] ?? date('Y-m-d');
    $accountId        = trim($input['account_id'] ?? '');
    $paymentMethod    = strtolower($input['payment_method'] ?? 'cash');
    $instrumentNo     = $input['instrument_no'] ?? null;

    $errors = [];
    if (!$loanSysId) $errors[] = 'loan_sys_id is required';
    if ($amount <= 0) $errors[] = 'A positive amount is required';
    if ($errors) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]);
        exit;
    }

    $userName = $_SESSION['user_name'] ?? 'system';

    $pdo->beginTransaction();

    /* ================= Lookup + lock the loan ================= */
    $loanStmt = $pdo->prepare("SELECT * FROM loans WHERE sys_id = ? FOR UPDATE");
    $loanStmt->execute([$loanSysId]);
    $loan = $loanStmt->fetch(PDO::FETCH_ASSOC);
    if (!$loan) throw new Exception('Loan not found');
    if ($loan['status'] !== 'active') throw new Exception("This loan is not active (status: {$loan['status']})");

    $outstandingBalance = (float)$loan['outstanding_balance'];
    if ($amount > $outstandingBalance + 0.01) {
        throw new Exception("Amount exceeds outstanding balance (৳{$outstandingBalance})");
    }

    $isCompanyInvolved = ($loan['lender_type'] === 'company' || $loan['borrower_type'] === 'company');
    $companyIsLender   = ($loan['lender_type'] === 'company'); // company lending => repayment flows TO us ('in'); company borrowing => repayment flows FROM us ('out')

    if ($isCompanyInvolved && !$accountId) {
        throw new Exception('account_id is required, since a company party is involved in this loan');
    }

    /* ================= If repaying a specific installment, validate it ================= */
    $installment = null;
    if ($installmentSysId) {
        $instStmt = $pdo->prepare("SELECT * FROM loan_installments WHERE sys_id = ? AND loan_sys_id = ? FOR UPDATE");
        $instStmt->execute([$installmentSysId, $loanSysId]);
        $installment = $instStmt->fetch(PDO::FETCH_ASSOC);
        if (!$installment) throw new Exception('Installment not found for this loan');
        if ($installment['status'] === 'paid') throw new Exception('This installment has already been paid');
    }

    /* ================= Lookup account, if money moves through us ================= */
    $accountName = null;
    $oldBalance  = null;
    if ($isCompanyInvolved) {
        $accStmt = $pdo->prepare("SELECT acc_name, balance FROM ac_banking WHERE sys_id = ?");
        $accStmt->execute([$accountId]);
        $accountInfo = $accStmt->fetch(PDO::FETCH_ASSOC);
        if (!$accountInfo) throw new Exception('Account not found');
        $accountName = $accountInfo['acc_name'];
        $oldBalance  = (float)$accountInfo['balance'];
    }

    /* ================= DATE FORMAT ================= */
    $tz = new DateTimeZone('Asia/Dhaka');
    $dt = new DateTime($date, $tz);
    $now = (new DateTime('now', $tz))->format('H:i:s');
    $dt->setTime(...explode(':', $now));
    $dateFormatted = $dt->format('Y-m-d H:i:s');

    /* ================= Record the repayment ================= */
    $ids = generateV2IDs($pdo, 'loan_repayments');
    $meta = buildMetaData(null, $userName);

    $pdo->prepare("
        INSERT INTO loan_repayments
        (uuid, sys_id, loan_sys_id, installment_sys_id, amount, date, account_id, payment_method, instrument_no, meta_data)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ")->execute([
        $ids['uuid'], $ids['sys_id'], $loanSysId, $installmentSysId ?: null,
        $amount, $dateFormatted, $accountId ?: null, $paymentMethod, $instrumentNo, $meta,
    ]);
    $repaymentSysId = $ids['sys_id'];

    /* ================= Mark installment paid, if applicable ================= */
    if ($installment) {
        $isFullyPaid = abs($amount - (float)$installment['total_due']) < 0.01;
        $pdo->prepare("UPDATE loan_installments SET status = ?, paid_date = ?, paid_amount = ? WHERE sys_id = ?")
            ->execute([$isFullyPaid ? 'paid' : 'pending', $dateFormatted, $amount, $installmentSysId]);
    }

    /* ================= Update loan's outstanding balance ================= */
    $newOutstanding = round($outstandingBalance - $amount, 2);
    $newLoanStatus  = $newOutstanding <= 0.01 ? 'closed' : 'active';
    $pdo->prepare("UPDATE loans SET outstanding_balance = ?, status = ? WHERE sys_id = ?")
        ->execute([max($newOutstanding, 0), $newLoanStatus, $loanSysId]);

    /* ================= Move money, only if a company party is involved ================= */
    if ($isCompanyInvolved) {
        $direction = $companyIsLender ? 'in' : 'out'; // company lending => repayment comes back to us; company borrowing => we pay it out
        $counterpartyType = $companyIsLender ? $loan['borrower_type'] : $loan['lender_type'];
        $counterpartyId   = $companyIsLender ? $loan['borrower_id']   : $loan['lender_id'];
        $counterpartyName = $companyIsLender ? $loan['borrower_name'] : $loan['lender_name'];

        postBankLegV2($pdo, $accountId, $accountName, $oldBalance, $amount, $direction, $dateFormatted,
            "Loan repayment — {$loanSysId}" . ($installmentSysId ? " (installment {$installment['installment_no']})" : ' (free-form)'),
            $repaymentSysId, $userName,
            $paymentMethod, $counterpartyId, $counterpartyName, $counterpartyType, $instrumentNo);
    }

    $pdo->commit();

    http_response_code(201);
    echo json_encode([
        'success'            => true,
        'message'            => $newLoanStatus === 'closed' ? 'Repayment recorded — loan fully closed' : 'Repayment recorded',
        'repayment_sys_id'   => $repaymentSysId,
        'new_outstanding_balance' => max($newOutstanding, 0),
        'loan_status'        => $newLoanStatus,
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'error'   => $e->getMessage()
    ]);
}