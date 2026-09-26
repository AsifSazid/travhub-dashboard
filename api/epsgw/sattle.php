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

    /* ================= Resolve the pending leg into a real bank_account leg =================
       verify-callback.php already created the accounts_receivable leg AND a
       placeholder account_head='gateway_pending' leg (no real ac_banking
       account was known yet at that point). This step does NOT create a new
       financial_entries group -- it converts that pending leg in place into
       the real bank_account leg, now that we know which account the money
       actually landed in. This keeps both legs under the same
       transaction_group_id, so reports/ledgers see one balanced group, not two. */
    $groupId = $gatewayPayment['financial_entries_group_id'];
    if (!$groupId) {
        throw new Exception('No financial_entries_group_id found on this gateway payment -- it may predate this settlement flow; use the manual reconciliation path instead.');
    }

    $pendingStmt = $pdo->prepare("
        SELECT sys_id FROM financial_entries
        WHERE transaction_group_id = ? AND account_head = 'gateway_pending'
        LIMIT 1
    ");
    $pendingStmt->execute([$groupId]);
    $pendingEntrySysId = $pendingStmt->fetchColumn();
    if (!$pendingEntrySysId) {
        throw new Exception('No pending gateway_pending financial_entries leg found for this group -- it may already be settled.');
    }

    $pdo->prepare("
        UPDATE financial_entries
        SET user_sys_id = ?, user_name = ?, account_head = 'bank_account', vendor_type = 1,
            date = ?, purpose = ?
        WHERE sys_id = ?
    ")->execute([$accountId, $accountName, $date, $finalPurpose, $pendingEntrySysId]);

    $bankEntrySysId = $pendingEntrySysId;
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