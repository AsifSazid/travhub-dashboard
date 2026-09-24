<?php
// PATH: /api/epsgw/settle.php
// (epsgw = EPS Gateway -- distinct from api/eps/, the unrelated payroll module)
//
// Manually confirms that a gateway payment's money has actually landed in
// the company's bank account, and ONLY NOW posts it to accounting. Per the
// user's explicit instruction: gateway settlement takes 1-3 business days
// after the client's payment is confirmed, so this is a deliberate, separate
// admin action -- never automatic -- exactly like verifying a cheque/BFTN
// instrument has cleared elsewhere in this system.
//
// POST { gateway_payment_sys_id, account_id, payment_method?, settlement_date? }
//   account_id -- the ac_banking account the EPS payout actually landed in
//                 (a dedicated "EPS Gateway" or general bank account --
//                 must be a transactionable Bank/Cash-category account)
//
// On success, calls the same accounting logic as
// receive-outstanding-general.php (a general, non-task-specific receivable
// reduction), because a gateway payment against an invoice is not tied to
// one specific sale/task the way pay-outstanding.php's targeted settlement
// is -- invoices can cover single or multiple tasks, per the user.

session_start();

require '../../server/db_connection.php';
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

    $gatewayPaymentId = trim($input['gateway_payment_sys_id'] ?? '');
    $accountId         = trim($input['account_id'] ?? '');
    $settlementDate    = $input['settlement_date'] ?? date('Y-m-d');

    $errors = [];
    if (!$gatewayPaymentId) $errors[] = 'gateway_payment_sys_id is required';
    if (!$accountId)        $errors[] = 'account_id is required';
    if ($errors) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]);
        exit;
    }

    $userName = $_SESSION['user_name'] ?? 'system';

    /* ================= Lookup + validate the gateway payment ================= */
    $gpStmt = $pdo->prepare("SELECT * FROM gateway_payments WHERE sys_id = ?");
    $gpStmt->execute([$gatewayPaymentId]);
    $gatewayPayment = $gpStmt->fetch(PDO::FETCH_ASSOC);

    if (!$gatewayPayment) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Gateway payment not found']);
        exit;
    }
    if ($gatewayPayment['status'] !== 'pending_settlement') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "This payment is not pending settlement (current status: {$gatewayPayment['status']})"]);
        exit;
    }

    $clientId = $gatewayPayment['client_sys_id'];
    $amount   = (float)$gatewayPayment['amount'];

    /* ================= Lookup client name (for display) ================= */
    $clientStmt = $pdo->prepare("SELECT title FROM clients WHERE sys_id = ?");
    $clientStmt->execute([$clientId]);
    $clientName = $clientStmt->fetchColumn() ?: $clientId;

    /* ================= Lookup account ================= */
    $accStmt = $pdo->prepare("SELECT acc_name, balance FROM ac_banking WHERE sys_id = ?");
    $accStmt->execute([$accountId]);
    $accountInfo = $accStmt->fetch(PDO::FETCH_ASSOC);
    if (!$accountInfo) throw new Exception('Account not found');
    $accountName = $accountInfo['acc_name'];
    $oldBalance  = (float)$accountInfo['balance'];

    /* ================= DATE FORMAT ================= */
    $tz  = new DateTimeZone('Asia/Dhaka');
    $dt  = new DateTime($settlementDate, $tz);
    $now = (new DateTime('now', $tz))->format('H:i:s');
    $dt->setTime(...explode(':', $now));
    $date = $dt->format('Y-m-d H:i:s');

    $finalPurpose = "EPS Gateway settlement — Invoice {$gatewayPayment['invoice_sys_id']} ({$clientName})";

    function _insertFinancialEntry(PDO $pdo, array $f): string
    {
        $ids  = generateV2IDs($pdo, 'financial_entries');
        $meta = buildMetaData(null, $f['user_name_actor']);
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
            ':uuid' => $ids['uuid'], ':sys_id' => $ids['sys_id'], ':group_id' => $f['group_id'],
            ':user_sys_id' => $f['user_sys_id'], ':user_name' => $f['user_name'], ':user_type' => $f['user_type'],
            ':account_head' => $f['account_head'], ':vendor_type' => $f['vendor_type'],
            ':date' => $f['date'], ':purpose' => $f['purpose'], ':type' => $f['type'], ':related_type' => $f['related_type'],
            ':amount' => $f['amount'], ':ref' => $f['ref'], ':meta_data' => $meta,
        ]);
        return $ids['sys_id'];
    }

    $groupId = generateV2SysId($pdo, 'financial_entries');
    $baseRow = [
        'date' => $date, 'purpose' => $finalPurpose,
        'ref' => $gatewayPaymentId, // links back to the gateway_payments row, not a specific sale
        'user_name_actor' => $userName, 'group_id' => $groupId,
    ];

    $arEntrySysId = _insertFinancialEntry($pdo, array_merge($baseRow, [
        'user_sys_id' => $clientId, 'user_name' => $clientName, 'user_type' => 'client',
        'account_head' => 'accounts_receivable', 'vendor_type' => null,
        'type' => 'credit', 'related_type' => 0, 'amount' => $amount,
    ]));

    $bankEntrySysId = _insertFinancialEntry($pdo, array_merge($baseRow, [
        'user_sys_id' => $accountId, 'user_name' => $accountName, 'user_type' => 'account',
        'account_head' => 'bank_account', 'vendor_type' => 1,
        'type' => 'debit', 'related_type' => 1, 'amount' => $amount,
    ]));

    // Gateway settlements are always instant from our side -- the money is
    // already confirmed to have landed, so this always uses 'gateway' as an
    // instant-equivalent method (never an instrument/hold state).
    postBankLegV2($pdo, $accountId, $accountName, $oldBalance, $amount, 'in', $date,
        $finalPurpose, $bankEntrySysId, $userName,
        'gateway', $clientId, $clientName, 'client', null);

    $pdo->prepare("
        UPDATE gateway_payments
        SET status = 'settled', settled_at = NOW(), settled_by = ?, settlement_account_id = ?, financial_entries_group_id = ?
        WHERE sys_id = ?
    ")->execute([$userName, $accountId, $groupId, $gatewayPaymentId]);

    http_response_code(201);
    echo json_encode([
        'success'              => true,
        'message'              => 'Gateway payment settled successfully',
        'transaction_group_id' => $groupId,
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'error'   => $e->getMessage()
    ]);
}