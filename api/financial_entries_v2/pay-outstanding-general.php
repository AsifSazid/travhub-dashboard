<?php
// PATH: /api/financial_entries_v2/pay-outstanding-general.php
// (v2 -- used by the Vendor Ledger page)
//
// Records a GENERAL payment to a vendor, NOT tied to any specific purchase.
// This is for the real-world case where a vendor has several small unpaid
// bills (e.g. ৳50 + ৳20 + ৳30) and the company pays a round amount (e.g.
// ৳70 or ৳60) that doesn't cleanly match any one bill or their sum. Per the
// user's explicit instruction, there is NO FIFO or auto-allocation across
// specific bills here -- this simply reduces the vendor's aggregate
// accounts_payable balance. The user can still settle a SPECIFIC purchase
// via pay-outstanding.php when they know which bill they're paying.
//
// POST { vendor_id, account_id, amount, date, purpose?, payment_method?, instrument_no? }
//
// Creates a NEW transaction group with:
//   Row 1: account_head=accounts_payable, debit  (payable reduced, NO ref
//          to any specific purchase group -- this is the key difference
//          from pay-outstanding.php)
//   Row 2: account_head=bank_account,     credit (+ac_banking_stmts, money out)
//
// Because this entry has no ref to a specific purchase, party-ledger.php's
// balance math (which sums ALL accounts_payable legs for the vendor
// regardless of ref) picks it up correctly without any changes needed there
// -- verified against _classifyGroup's aggregate calculation, which does
// not filter by ref.

session_start();

require '../../server/db_connection.php';
require_once '../../server/permissions.php';
requireFullAccountingAccess($pdo, true);
require '../../server/uuid_with_system_id_generator.php';
require_once '../../server/sys_id_generator_v2.php';
require '../../server/generate_meta_data.php';
require_once '../../server/finance_helpers.php'; // isInstrumentMethod(), postBankLegV2()

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

    $vendorId      = trim($input['vendor_id'] ?? '');
    $accountId     = trim($input['account_id'] ?? '');
    $amount        = (float)($input['amount'] ?? 0);
    $date          = $input['date'] ?? date('Y-m-d');
    $purpose       = trim($input['purpose'] ?? '');
    $paymentMethod = strtolower($input['payment_method'] ?? 'cash');
    $instrumentNo  = $input['instrument_no'] ?? null;

    $errors = [];
    if (!$vendorId)  $errors[] = 'vendor_id is required';
    if (!$accountId) $errors[] = 'account_id is required';
    if ($amount <= 0) $errors[] = 'A positive amount is required';
    if ($errors) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]);
        exit;
    }

    $userName = $_SESSION['user_name'] ?? 'system';

    /* ================= Lookup vendor name (for display) ================= */
    $vendorStmt = $pdo->prepare("
        SELECT user_name FROM financial_entries
        WHERE user_type = 'vendor' AND user_sys_id = ? LIMIT 1
    ");
    $vendorStmt->execute([$vendorId]);
    $vendorName = $vendorStmt->fetchColumn() ?: $vendorId;

    /* ================= Lookup account ================= */
    $accStmt = $pdo->prepare("SELECT acc_name, balance FROM ac_banking WHERE sys_id = ?");
    $accStmt->execute([$accountId]);
    $accountInfo = $accStmt->fetch(PDO::FETCH_ASSOC);
    if (!$accountInfo) throw new Exception('Account not found');
    $accountName = $accountInfo['acc_name'];
    $oldBalance  = (float)$accountInfo['balance'];

    /* ================= DATE FORMAT ================= */
    $tz  = new DateTimeZone('Asia/Dhaka');
    $dt  = new DateTime($date, $tz);
    $now = (new DateTime('now', $tz))->format('H:i:s');
    $dt->setTime(...explode(':', $now));
    $date = $dt->format('Y-m-d H:i:s');

    $finalPurpose = $purpose ?: "General payment to {$vendorName}";

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
        'ref' => null, // deliberately no ref -- this is NOT tied to a specific purchase
        'user_name_actor' => $userName, 'group_id' => $groupId,
    ];

    $apEntrySysId = _insertFinancialEntry($pdo, array_merge($baseRow, [
        'user_sys_id' => $vendorId, 'user_name' => $vendorName, 'user_type' => 'vendor',
        'account_head' => 'accounts_payable', 'vendor_type' => 0,
        'type' => 'debit', 'related_type' => 2, 'amount' => $amount,
    ]));

    $bankEntrySysId = _insertFinancialEntry($pdo, array_merge($baseRow, [
        'user_sys_id' => $accountId, 'user_name' => $accountName, 'user_type' => 'account',
        'account_head' => 'bank_account', 'vendor_type' => 1,
        'type' => 'credit', 'related_type' => 2, 'amount' => $amount,
    ]));

    postBankLegV2($pdo, $accountId, $accountName, $oldBalance, $amount, 'out', $date,
        $finalPurpose, $bankEntrySysId, $userName,
        $paymentMethod, $vendorId, $vendorName, 'vendor', $instrumentNo);

    http_response_code(201);
    echo json_encode([
        'success'              => true,
        'message'              => 'General payment recorded',
        'transaction_group_id' => $groupId,
        'payable_entry_sys_id' => $apEntrySysId,
        'bank_entry_sys_id'    => $bankEntrySysId,
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'error'   => $e->getMessage()
    ]);
}