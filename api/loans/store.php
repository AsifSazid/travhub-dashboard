<?php
// PATH: /api/loans/store.php
//
// Creates a new loan record. Per the user's explicit design:
//   - Loan is a COMPLETELY SEPARATE module from financial_entries_v2 --
//     never touches account_head/double-entry accounting directly.
//   - lender/borrower are generic: each side can be 'company', 'employee',
//     'vendor', or 'client'.
//   - Money only moves (touching ac_banking/ac_banking_stmts) when it
//     ACTUALLY moves -- i.e. at disbursement, and again at each repayment.
//     Simply creating the loan agreement here does NOT move any money.
//   - If a 'company' party is on either side, an account_id must be given
//     so disbursement can debit/credit the right ac_banking account at
//     that moment. If NEITHER side is 'company' (e.g. employee lending to
//     a vendor), no money moves through our own books at all -- this is
//     purely a tracked agreement between two external parties.
//
// POST {
//   lender_type, lender_id, lender_name,
//   borrower_type, borrower_id, borrower_name,
//   principal_amount,
//   interest_rate?, interest_method? ('reducing_balance'|'flat'),
//   repayment_type ('emi'|'free_form'),
//   emi_calculation_mode? ('auto'|'manual') -- required if repayment_type='emi'
//   tenure_months? -- required if repayment_type='emi' and emi_calculation_mode='auto'
//   emi_amount? -- required if repayment_type='emi' and emi_calculation_mode='manual'
//   disbursement_date, disburse_now (bool),
//   account_id? -- required if disburse_now=true AND either party is 'company'
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
require_once __DIR__ . '/loan_calc.php'; // calculateEmi()

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$validPartyTypes = ['company', 'employee', 'vendor', 'client'];

try {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $lenderType   = strtolower(trim($input['lender_type'] ?? ''));
    $lenderId     = trim($input['lender_id'] ?? '');
    $lenderName   = trim($input['lender_name'] ?? '');
    $borrowerType = strtolower(trim($input['borrower_type'] ?? ''));
    $borrowerId   = trim($input['borrower_id'] ?? '');
    $borrowerName = trim($input['borrower_name'] ?? '');

    $principal       = (float)($input['principal_amount'] ?? 0);
    $interestRate    = isset($input['interest_rate']) && $input['interest_rate'] !== '' ? (float)$input['interest_rate'] : null;
    $interestMethod  = $input['interest_method'] ?? null; // 'reducing_balance' | 'flat' | null
    $repaymentType   = strtolower(trim($input['repayment_type'] ?? '')); // 'emi' | 'free_form'
    $emiMode         = $input['emi_calculation_mode'] ?? null; // 'auto' | 'manual'
    $tenureMonths    = isset($input['tenure_months']) ? (int)$input['tenure_months'] : null;
    $manualEmiAmount = isset($input['emi_amount']) ? (float)$input['emi_amount'] : null;
    $disbursementDate = $input['disbursement_date'] ?? date('Y-m-d');
    $disburseNow      = !empty($input['disburse_now']);
    $accountId        = trim($input['account_id'] ?? '');
    $paymentMethod    = strtolower($input['payment_method'] ?? 'cash');
    $instrumentNo     = $input['instrument_no'] ?? null;

    /* ================= Validation ================= */
    $errors = [];
    if (!in_array($lenderType, $validPartyTypes, true))   $errors[] = "lender_type must be one of: " . implode(', ', $validPartyTypes);
    if (!in_array($borrowerType, $validPartyTypes, true)) $errors[] = "borrower_type must be one of: " . implode(', ', $validPartyTypes);
    if ($lenderType === $borrowerType && $lenderId === $borrowerId) $errors[] = 'Lender and borrower cannot be the same party';
    if (!$lenderId)   $errors[] = 'lender_id is required';
    if (!$borrowerId) $errors[] = 'borrower_id is required';
    if ($principal <= 0) $errors[] = 'principal_amount must be positive';
    if (!in_array($repaymentType, ['emi', 'free_form'], true)) $errors[] = "repayment_type must be 'emi' or 'free_form'";

    $isCompanyInvolved = ($lenderType === 'company' || $borrowerType === 'company');
    $companyIsLender   = ($lenderType === 'company'); // true => money leaves us; false (borrowerType='company') => money comes to us

    if ($repaymentType === 'emi') {
        if (!in_array($emiMode, ['auto', 'manual'], true)) $errors[] = "emi_calculation_mode must be 'auto' or 'manual' when repayment_type is 'emi'";
        if ($emiMode === 'auto' && (!$tenureMonths || $tenureMonths <= 0)) $errors[] = 'tenure_months is required for auto EMI calculation';
        if ($emiMode === 'manual' && (!$manualEmiAmount || $manualEmiAmount <= 0)) $errors[] = 'emi_amount is required for manual EMI mode';
    }
    if ($interestRate !== null && $interestRate > 0 && !in_array($interestMethod, ['reducing_balance', 'flat'], true)) {
        $errors[] = "interest_method must be 'reducing_balance' or 'flat' when interest_rate is set";
    }
    if ($disburseNow && $isCompanyInvolved && !$accountId) {
        $errors[] = 'account_id is required to disburse now, since a company party is involved';
    }

    if ($errors) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]);
        exit;
    }

    $userName = $_SESSION['user_name'] ?? 'system';

    /* ================= EMI calculation (auto mode) ================= */
    $emiAmount = $manualEmiAmount;
    $emiSchedule = [];
    if ($repaymentType === 'emi') {
        if ($emiMode === 'auto') {
            $calc = calculateEmi($principal, $interestRate ?? 0, $tenureMonths, $interestMethod ?? 'reducing_balance');
            $emiAmount   = $calc['emi_amount'];
            $emiSchedule = $calc['schedule']; // [[installment_no, principal_component, interest_component, total_due], ...]
        } else {
            // Manual EMI amount given directly -- still build a schedule so
            // loan_installments has rows to track against, using simple
            // equal-principal-chunks with the manual total treated as a flat
            // per-installment figure (interest split is only meaningful in
            // auto mode where we control the math end-to-end).
            $tenureForManual = $tenureMonths ?: (int)ceil($principal / $emiAmount);
            for ($i = 1; $i <= $tenureForManual; $i++) {
                $emiSchedule[] = ['installment_no' => $i, 'principal_component' => null, 'interest_component' => null, 'total_due' => $emiAmount];
            }
        }
    }

    /* ================= Lookup account (if disbursing now) ================= */
    $accountName = null;
    $oldBalance  = null;
    if ($disburseNow && $isCompanyInvolved) {
        $accStmt = $pdo->prepare("SELECT acc_name, balance FROM ac_banking WHERE sys_id = ?");
        $accStmt->execute([$accountId]);
        $accountInfo = $accStmt->fetch(PDO::FETCH_ASSOC);
        if (!$accountInfo) throw new Exception('Account not found');
        $accountName = $accountInfo['acc_name'];
        $oldBalance  = (float)$accountInfo['balance'];
    }

    /* ================= DATE FORMAT ================= */
    $tz = new DateTimeZone('Asia/Dhaka');
    $dt = new DateTime($disbursementDate, $tz);
    $now = (new DateTime('now', $tz))->format('H:i:s');
    $dt->setTime(...explode(':', $now));
    $disbursementDateFormatted = $dt->format('Y-m-d H:i:s');

    /* ================= Insert loan ================= */
    $ids = generateV2IDs($pdo, 'loans');
    $meta = buildMetaData(null, $userName);

    $pdo->beginTransaction();

    $pdo->prepare("
        INSERT INTO loans (
            uuid, sys_id,
            lender_type, lender_id, lender_name,
            borrower_type, borrower_id, borrower_name,
            principal_amount, interest_rate, interest_method,
            repayment_type, emi_calculation_mode, tenure_months, emi_amount,
            disbursement_date, disbursement_account_id, disbursed,
            status, outstanding_balance, meta_data, created_at
        ) VALUES (
            :uuid, :sys_id,
            :lender_type, :lender_id, :lender_name,
            :borrower_type, :borrower_id, :borrower_name,
            :principal, :interest_rate, :interest_method,
            :repayment_type, :emi_mode, :tenure, :emi_amount,
            :disb_date, :account_id, :disbursed,
            'active', :outstanding, :meta_data, NOW()
        )
    ")->execute([
        ':uuid' => $ids['uuid'], ':sys_id' => $ids['sys_id'],
        ':lender_type' => $lenderType, ':lender_id' => $lenderId, ':lender_name' => $lenderName,
        ':borrower_type' => $borrowerType, ':borrower_id' => $borrowerId, ':borrower_name' => $borrowerName,
        ':principal' => $principal, ':interest_rate' => $interestRate, ':interest_method' => $interestMethod,
        ':repayment_type' => $repaymentType, ':emi_mode' => $emiMode, ':tenure' => $tenureMonths, ':emi_amount' => $emiAmount,
        ':disb_date' => $disbursementDateFormatted, ':account_id' => $disburseNow ? $accountId : null, ':disbursed' => $disburseNow ? 1 : 0,
        ':outstanding' => $principal, ':meta_data' => $meta,
    ]);
    $loanSysId = $ids['sys_id'];

    /* ================= Insert EMI schedule rows, if any ================= */
    if ($repaymentType === 'emi' && !empty($emiSchedule)) {
        $dueDate = clone $dt;
        foreach ($emiSchedule as $row) {
            $dueDate->modify('+1 month');
            $instIds = generateV2IDs($pdo, 'loan_installments');
            $pdo->prepare("
                INSERT INTO loan_installments
                (uuid, sys_id, loan_sys_id, installment_no, due_date, principal_component, interest_component, total_due, status, meta_data)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)
            ")->execute([
                $instIds['uuid'], $instIds['sys_id'], $loanSysId, $row['installment_no'],
                $dueDate->format('Y-m-d'), $row['principal_component'], $row['interest_component'], $row['total_due'],
                buildMetaData(null, $userName),
            ]);
        }
    }

    /* ================= Disburse now, if requested ================= */
    if ($disburseNow && $isCompanyInvolved) {
        $direction = $companyIsLender ? 'out' : 'in'; // company lending => money leaves us; company borrowing => money enters us
        $counterpartyType = $companyIsLender ? $borrowerType : $lenderType;
        $counterpartyId   = $companyIsLender ? $borrowerId : $lenderId;
        $counterpartyName = $companyIsLender ? $borrowerName : $lenderName;

        postBankLegV2($pdo, $accountId, $accountName, $oldBalance, $principal, $direction, $disbursementDateFormatted,
            "Loan disbursement — {$loanSysId}", $loanSysId, $userName,
            $paymentMethod, $counterpartyId, $counterpartyName, $counterpartyType, $instrumentNo);
    }

    $pdo->commit();

    http_response_code(201);
    echo json_encode([
        'success'      => true,
        'message'      => 'Loan created' . ($disburseNow ? ' and disbursed' : ' (not yet disbursed)'),
        'loan_sys_id'  => $loanSysId,
        'emi_amount'   => $emiAmount,
        'installment_count' => count($emiSchedule),
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