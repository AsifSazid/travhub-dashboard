<?php
// PATH: /api/financial_entries_v2/store-asset.php
// (v2 -- fixed asset purchase, double-entry)
//
// Records a fixed asset purchase (laptop, furniture, machinery, etc.),
// fully double-entry:
//   Row 1: account_head=fixed_asset,      debit  (asset value recognized)
//   Row 2: account_head=bank_account, credit (+ac_banking_stmts, money out
//          of a real transactionable account -- Bank/Cash/Petty Cash)
//
// POST { asset_account_id, payment_account_id, amount, purpose, date, ref? }
//   asset_account_id -- an ac_banking row in category Fixed Assets (classification only, see
//                         sales/Other Expense category — see
//                         api/accounts/expense-asset-accounts.php)
//   payment_account_id -- a transactionable ac_banking row (Bank/Cash/Petty
//                         Cash) that the money actually left

session_start();

require '../../server/db_connection.php';
require_once '../../server/permissions.php';
requireFullAccountingAccess($pdo, true);
require '../../server/uuid_with_system_id_generator.php';
require_once '../../server/sys_id_generator_v2.php';
require '../../server/generate_meta_data.php';
require_once '../../server/finance_helpers.php'; // resolveAccountHeadFromCategory()

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

    $assetAccountId = trim($input['asset_account_id'] ?? '');
    $paymentAccountId = trim($input['payment_account_id'] ?? '');
    $amount           = (float)($input['amount'] ?? 0);
    $purpose          = trim($input['purpose'] ?? '');
    $date             = $input['date'] ?? date('Y-m-d');
    $ref              = $input['ref'] ?? null;
    $paymentMethod    = strtolower($input['payment_method'] ?? 'cash');
    $instrumentNo     = $input['instrument_no'] ?? null;

    $errors = [];
    if (!$assetAccountId) $errors[] = 'asset_account_id is required';
    if (!$paymentAccountId) $errors[] = 'payment_account_id is required';
    if ($amount <= 0)       $errors[] = 'A positive amount is required';
    if (!$purpose)          $errors[] = 'Purpose is required';
    if ($errors) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]);
        exit;
    }

    $userName = $_SESSION['user_name'] ?? 'system';

    /* ================= Lookup + validate the fixed asset (classification) account ================= */
    $astStmt = $pdo->prepare("SELECT acc_name, category FROM ac_banking WHERE sys_id = ?");
    $astStmt->execute([$assetAccountId]);
    $assetAccount = $astStmt->fetch(PDO::FETCH_ASSOC);
    if (!$assetAccount) throw new Exception('Asset account not found');

    $resolvedHead = resolveAccountHeadFromCategory($assetAccount['category']);
    if ($resolvedHead !== 'fixed_asset') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "The selected account is not a Fixed Assets-category account (category: {$assetAccount['category']})"]);
        exit;
    }
    $assetAccountName = $assetAccount['acc_name'];

    /* ================= Lookup + validate the payment (bank/cash) account ================= */
    $payStmt = $pdo->prepare("SELECT acc_name, category, balance FROM ac_banking WHERE sys_id = ?");
    $payStmt->execute([$paymentAccountId]);
    $paymentAccount = $payStmt->fetch(PDO::FETCH_ASSOC);
    if (!$paymentAccount) throw new Exception('Payment account not found');

    if (resolveAccountHeadFromCategory($paymentAccount['category']) !== 'bank_account') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "The selected payment account is not a Bank/Cash-category account (category: {$paymentAccount['category']})"]);
        exit;
    }
    $paymentAccountName = $paymentAccount['acc_name'];
    $oldBalance = (float)$paymentAccount['balance'];

    /* ================= DATE FORMAT ================= */
    $tz  = new DateTimeZone('Asia/Dhaka');
    $dt  = new DateTime($date, $tz);
    $now = (new DateTime('now', $tz))->format('H:i:s');
    $dt->setTime(...explode(':', $now));
    $date = $dt->format('Y-m-d H:i:s');

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
    $baseRow = ['date' => $date, 'purpose' => $purpose, 'ref' => $ref, 'user_name_actor' => $userName, 'group_id' => $groupId];

    // Fixed Asset: debit (asset value recognized)
    $assetEntrySysId = _insertFinancialEntry($pdo, array_merge($baseRow, [
        'user_sys_id' => $assetAccountId, 'user_name' => $assetAccountName, 'user_type' => 'account',
        'account_head' => 'fixed_asset', 'vendor_type' => null,
        'type' => 'debit', 'related_type' => 2, 'amount' => $amount,
    ]));

    // Bank/Cash: credit (money left this account)
    $bankEntrySysId = _insertFinancialEntry($pdo, array_merge($baseRow, [
        'user_sys_id' => $paymentAccountId, 'user_name' => $paymentAccountName, 'user_type' => 'account',
        'account_head' => 'bank_account', 'vendor_type' => 1,
        'type' => 'credit', 'related_type' => 2, 'amount' => $amount,
    ]));

    postBankLegV2($pdo, $paymentAccountId, $paymentAccountName, $oldBalance, $amount, 'out', $date,
        "{$assetAccountName} — {$purpose}", $bankEntrySysId, $userName,
        $paymentMethod, $assetAccountId, $assetAccountName, 'account', $instrumentNo);

    http_response_code(201);
    echo json_encode([
        'success'              => true,
        'message'              => 'Asset purchase recorded successfully',
        'transaction_group_id' => $groupId,
        'asset_entry_sys_id' => $assetEntrySysId,
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