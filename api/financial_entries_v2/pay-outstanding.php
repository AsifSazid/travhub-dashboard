<?php
// PATH: /api/financial_entries_v2/pay-outstanding.php
// (v2 -- separate namespace from legacy /api/financial_entries/, used by both
//  the Task Financial tab and (later) the Vendor Ledger page)
//
// Pays down an existing unpaid vendor purchase (created via store.php with
// transaction_mode='non_realtime'), in full or partially. Supports multiple
// partial payments against the same purchase over time.
//
// POST { purchase_group_id, vendor_id, account_id, amount, date, purpose?, ref? }
//
// Creates a NEW transaction group (does not touch the original purchase
// group's rows) with:
//   Row 1: account_head=accounts_payable, debit  (payable reduced)
//   Row 2: account_head=bank_account,     credit (+ac_banking_stmts, money out)
// Both rows carry ref = purchase_group_id, so the due amount for that
// purchase can always be recomputed as:
//   original_purchase_amount - SUM(amount) of all payment groups referencing it
//
// Task-independent: task_id/work_id are optional and only recorded for
// display convenience if the caller has them (e.g. the Task Financial tab
// paying down a purchase that happened within that task's work).

session_start();

require '../../server/db_connection.php';
require_once '../../server/permissions.php';
requireFullAccountingAccess($pdo, true);
require '../../server/uuid_with_system_id_generator.php';
require_once '../../server/sys_id_generator_v2.php';
require '../../server/generate_meta_data.php';
require_once '../../server/finance_helpers.php'; // isInstrumentMethod()

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

    $purchaseGroupId = trim($input['purchase_group_id'] ?? '');
    $vendorId        = trim($input['vendor_id'] ?? '');
    $accountId       = trim($input['account_id'] ?? '');
    $amount          = (float)($input['amount'] ?? 0);
    $date            = $input['date'] ?? date('Y-m-d');
    $purpose         = trim($input['purpose'] ?? '');
    $ref             = $input['ref'] ?? null; // optional user note, separate from the group-link ref
    $workId          = $input['work_id'] ?? null;
    $taskId          = $input['task_id'] ?? null;
    $paymentMethod   = strtolower($input['payment_method'] ?? 'cash');
    $instrumentNo    = $input['instrument_no'] ?? null;

    $errors = [];
    if (!$purchaseGroupId) $errors[] = 'purchase_group_id is required';
    if (!$vendorId)        $errors[] = 'vendor_id is required';
    if (!$accountId)       $errors[] = 'account_id is required';
    if ($amount <= 0)      $errors[] = 'A positive amount is required';
    if ($errors) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]);
        exit;
    }

    $userName = $_SESSION['user_name'] ?? 'system';

    /* ================= Verify the purchase group + compute remaining due ================= */
    $purchaseLegsStmt = $pdo->prepare("SELECT * FROM financial_entries WHERE transaction_group_id = ?");
    $purchaseLegsStmt->execute([$purchaseGroupId]);
    $purchaseLegs = $purchaseLegsStmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$purchaseLegs) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Original purchase transaction not found']);
        exit;
    }

    $purchaseLeg = null;
    foreach ($purchaseLegs as $l) { if ($l['account_head'] === 'purchase') { $purchaseLeg = $l; break; } }
    if (!$purchaseLeg) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'This transaction is not an unpaid vendor purchase']);
        exit;
    }
    $originalAmount = (float)$purchaseLeg['amount'];
    $vendorName     = $purchaseLeg['user_name'];

    // Sum every prior payment that referenced this purchase group (ref = purchase_group_id).
    $paidStmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) FROM financial_entries
        WHERE account_head = 'accounts_payable' AND type = 'debit' AND ref = ?
    ");
    $paidStmt->execute([$purchaseGroupId]);
    $alreadyPaid = (float)$paidStmt->fetchColumn();
    $remainingDue = round($originalAmount - $alreadyPaid, 2);

    if ($remainingDue <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'This purchase is already fully paid']);
        exit;
    }
    if ($amount > $remainingDue + 0.01) { // small float tolerance
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Amount exceeds remaining due (৳{$remainingDue})"]);
        exit;
    }

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

    $finalPurpose = $purpose ?: "Payment against purchase {$purchaseGroupId} — {$vendorName}";

    function _insertFinancialEntry(PDO $pdo, array $f): string
    {
        $ids  = generateV2IDs($pdo, 'financial_entries');
        $meta = buildMetaData(null, $f['user_name_actor']);
        $pdo->prepare("
            INSERT INTO financial_entries (
                uuid, sys_id, transaction_group_id,
                user_sys_id, user_name, user_type, account_head, vendor_type,
                task_sys_id, task_title, work_sys_id, work_title,
                date, purpose, type, related_type,
                is_paid, is_partial, is_discounted,
                amount, qty_rate, ref, meta_data
            ) VALUES (
                :uuid, :sys_id, :group_id,
                :user_sys_id, :user_name, :user_type, :account_head, :vendor_type,
                :task_sys_id, :task_title, :work_sys_id, :work_title,
                :date, :purpose, :type, :related_type,
                :is_paid, :is_partial, 0,
                :amount, NULL, :ref, :meta_data
            )
        ")->execute([
            ':uuid' => $ids['uuid'], ':sys_id' => $ids['sys_id'], ':group_id' => $f['group_id'],
            ':user_sys_id' => $f['user_sys_id'], ':user_name' => $f['user_name'], ':user_type' => $f['user_type'],
            ':account_head' => $f['account_head'], ':vendor_type' => $f['vendor_type'],
            ':task_sys_id' => $f['task_sys_id'], ':task_title' => $f['task_title'],
            ':work_sys_id' => $f['work_sys_id'], ':work_title' => $f['work_title'],
            ':date' => $f['date'], ':purpose' => $f['purpose'], ':type' => $f['type'], ':related_type' => $f['related_type'],
            ':is_paid' => $f['is_paid'], ':is_partial' => $f['is_partial'],
            ':amount' => $f['amount'], ':ref' => $f['ref'], ':meta_data' => $meta,
        ]);
        return $ids['sys_id'];
    }

    $paymentGroupId = generateV2SysId($pdo, 'financial_entries');
    $isFullyPaid    = abs($amount - $remainingDue) < 0.01;

    $baseRow = [
        'task_sys_id' => $taskId, 'task_title' => null,
        'work_sys_id' => $workId, 'work_title' => null,
        'date' => $date, 'purpose' => $finalPurpose,
        'ref' => $purchaseGroupId, // links this payment back to the original purchase
        'user_name_actor' => $userName, 'group_id' => $paymentGroupId,
        'is_paid' => $isFullyPaid ? 1 : 0, 'is_partial' => $isFullyPaid ? 0 : 1,
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
        'message'              => $isFullyPaid ? 'Purchase fully paid' : 'Partial payment recorded',
        'transaction_group_id' => $paymentGroupId,
        'payable_entry_sys_id' => $apEntrySysId,
        'bank_entry_sys_id'    => $bankEntrySysId,
        'remaining_due'        => round($remainingDue - $amount, 2),
        'fully_paid'           => $isFullyPaid,
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'error'   => $e->getMessage()
    ]);
}