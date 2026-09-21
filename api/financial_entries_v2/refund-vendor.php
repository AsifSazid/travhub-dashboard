<?php
// PATH: /api/financial_entries_v2/refund-vendor.php
// (v2 -- separate namespace from legacy /api/financial_entries/, used by both
//  the Task Financial tab and (later) the Vendor Ledger page)
//
// Declares a refund against an existing vendor purchase, whether or not that
// purchase has been paid yet. This is DECLARE only -- the actual bank money
// movement is a separate, later step (see refund-settle-vendor.php), because
// a vendor's refund can take days/weeks to actually land in the bank account.
//
// POST { purchase_group_id, vendor_id, refund_amount?, refund_charge?, date, purpose?, ref? }
// Exactly one of refund_amount / refund_charge is required -- the other is
// computed from the purchase's original amount:
//   refund_amount + refund_charge = original_purchase_amount
//
// Creates a NEW transaction group with:
//   Row 1: account_head=purchase,                  credit (expense reversed, by refund_amount)
//   Row 2: account_head=refund_charge,              credit (charge recognized, by refund_charge)
//   Row 3: account_head=accounts_payable,           debit  (only if the purchase still had an
//                                                     outstanding payable balance -- reduces it)
//   Row 4: account_head=vendor_refund_receivable,   debit  (what we're now owed by the vendor
//                                                     for this refund, by refund_amount)
// Row 3 is skipped (or reduced) if the purchase's payable balance is smaller
// than refund_amount -- see the payable-adjustment logic below.
//
// The vendor_refund_receivable balance is later cleared by
// refund-settle-vendor.php once the money actually arrives in an account.

session_start();

require '../../server/db_connection.php';
require '../../server/uuid_with_system_id_generator.php';
require_once '../../server/sys_id_generator_v2.php';
require '../../server/generate_meta_data.php';

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
    $refundAmountIn  = isset($input['refund_amount']) && $input['refund_amount'] !== '' ? (float)$input['refund_amount'] : null;
    $refundChargeIn  = isset($input['refund_charge']) && $input['refund_charge'] !== '' ? (float)$input['refund_charge'] : null;
    $date            = $input['date'] ?? date('Y-m-d');
    $purpose         = trim($input['purpose'] ?? '');
    $ref             = $input['ref'] ?? null;
    $workId          = $input['work_id'] ?? null;
    $taskId          = $input['task_id'] ?? null;

    $errors = [];
    if (!$purchaseGroupId) $errors[] = 'purchase_group_id is required';
    if (!$vendorId)        $errors[] = 'vendor_id is required';
    if ($refundAmountIn === null && $refundChargeIn === null) $errors[] = 'Either refund_amount or refund_charge is required';
    if ($refundAmountIn !== null && $refundAmountIn < 0) $errors[] = 'refund_amount must not be negative';
    if ($refundChargeIn !== null && $refundChargeIn < 0) $errors[] = 'refund_charge must not be negative';
    if ($errors) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]);
        exit;
    }

    $userName = $_SESSION['user_name'] ?? 'system';

    /* ================= Verify the purchase group ================= */
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
        echo json_encode(['success' => false, 'message' => 'This transaction is not a vendor purchase']);
        exit;
    }
    $originalAmount = (float)$purchaseLeg['amount'];
    $vendorName     = $purchaseLeg['user_name'];

    // Already-refunded amount against this purchase (so refunds can be partial/repeated too).
    $refundedStmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) FROM financial_entries
        WHERE account_head = 'purchase' AND type = 'credit' AND ref = ?
    ");
    $refundedStmt->execute([$purchaseGroupId]);
    $alreadyRefunded = (float)$refundedStmt->fetchColumn();
    $refundableRemaining = round($originalAmount - $alreadyRefunded, 2);

    /* ================= Resolve refund_amount / refund_charge pair ================= */
    if ($refundAmountIn !== null) {
        $refundAmount = $refundAmountIn;
        $refundCharge = round($refundableRemaining - $refundAmount, 2);
    } else {
        $refundCharge = $refundChargeIn;
        $refundAmount = round($refundableRemaining - $refundCharge, 2);
    }
    if ($refundAmount < 0 || $refundCharge < 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "refund_amount + refund_charge cannot exceed the refundable remaining (৳{$refundableRemaining})"]);
        exit;
    }
    if ($refundAmount == 0 && $refundCharge == 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Nothing to refund']);
        exit;
    }

    /* ================= Current outstanding payable on this purchase ================= */
    $paidStmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) FROM financial_entries
        WHERE account_head = 'accounts_payable' AND type = 'debit' AND ref = ?
    ");
    $paidStmt->execute([$purchaseGroupId]);
    $alreadyPaid = (float)$paidStmt->fetchColumn();
    $outstandingPayable = max(round($originalAmount - $alreadyPaid, 2), 0);
    // How much of this refund's amount+charge should reduce the still-open payable
    // (can't reduce more than what's actually outstanding).
    $payableReduction = min($refundAmount + $refundCharge, $outstandingPayable);

    /* ================= DATE FORMAT ================= */
    $tz  = new DateTimeZone('Asia/Dhaka');
    $dt  = new DateTime($date, $tz);
    $now = (new DateTime('now', $tz))->format('H:i:s');
    $dt->setTime(...explode(':', $now));
    $date = $dt->format('Y-m-d H:i:s');

    $finalPurpose = $purpose ?: "Refund from {$vendorName} against purchase {$purchaseGroupId}";

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
                0, :is_partial, 0,
                :amount, NULL, :ref, :meta_data
            )
        ")->execute([
            ':uuid' => $ids['uuid'], ':sys_id' => $ids['sys_id'], ':group_id' => $f['group_id'],
            ':user_sys_id' => $f['user_sys_id'], ':user_name' => $f['user_name'], ':user_type' => $f['user_type'],
            ':account_head' => $f['account_head'], ':vendor_type' => $f['vendor_type'],
            ':task_sys_id' => $f['task_sys_id'], ':task_title' => $f['task_title'],
            ':work_sys_id' => $f['work_sys_id'], ':work_title' => $f['work_title'],
            ':date' => $f['date'], ':purpose' => $f['purpose'], ':type' => $f['type'], ':related_type' => $f['related_type'],
            ':is_partial' => $f['is_partial'],
            ':amount' => $f['amount'], ':ref' => $f['ref'], ':meta_data' => $meta,
        ]);
        return $ids['sys_id'];
    }

    $refundGroupId = generateV2SysId($pdo, 'financial_entries');
    $isPartialRefund = $refundableRemaining - ($refundAmount + $refundCharge) > 0.01;

    $baseRow = [
        'task_sys_id' => $taskId, 'task_title' => null,
        'work_sys_id' => $workId, 'work_title' => null,
        'date' => $date, 'purpose' => $finalPurpose,
        'ref' => $purchaseGroupId,
        'user_name_actor' => $userName, 'group_id' => $refundGroupId,
        'is_partial' => $isPartialRefund ? 1 : 0,
    ];

    $entryIds = [];

    if ($refundAmount > 0) {
        $entryIds['purchase_reversal_sys_id'] = _insertFinancialEntry($pdo, array_merge($baseRow, [
            'user_sys_id' => $vendorId, 'user_name' => $vendorName, 'user_type' => 'vendor',
            'account_head' => 'purchase', 'vendor_type' => 0,
            'type' => 'credit', 'related_type' => 2, 'amount' => $refundAmount,
        ]));
    }
    if ($refundCharge > 0) {
        $entryIds['refund_charge_sys_id'] = _insertFinancialEntry($pdo, array_merge($baseRow, [
            'user_sys_id' => $vendorId, 'user_name' => $vendorName, 'user_type' => 'vendor',
            'account_head' => 'refund_charge', 'vendor_type' => 0,
            'type' => 'credit', 'related_type' => 2, 'amount' => $refundCharge,
        ]));
    }
    if ($payableReduction > 0) {
        $entryIds['payable_reduction_sys_id'] = _insertFinancialEntry($pdo, array_merge($baseRow, [
            'user_sys_id' => $vendorId, 'user_name' => $vendorName, 'user_type' => 'vendor',
            'account_head' => 'accounts_payable', 'vendor_type' => 0,
            'type' => 'debit', 'related_type' => 2, 'amount' => $payableReduction,
        ]));
    }
    if ($refundAmount > 0) {
        // What we're now owed BY the vendor for this refund -- settled later
        // via refund-settle-vendor.php once the money actually arrives.
        $entryIds['refund_receivable_sys_id'] = _insertFinancialEntry($pdo, array_merge($baseRow, [
            'user_sys_id' => $vendorId, 'user_name' => $vendorName, 'user_type' => 'vendor',
            'account_head' => 'vendor_refund_receivable', 'vendor_type' => 0,
            'type' => 'debit', 'related_type' => 2, 'amount' => $refundAmount,
        ]));
    }

    http_response_code(201);
    echo json_encode([
        'success'              => true,
        'message'              => 'Refund declared',
        'transaction_group_id' => $refundGroupId,
        'refund_amount'        => $refundAmount,
        'refund_charge'        => $refundCharge,
        'pending_receivable'   => $refundAmount, // still to be settled once cash arrives
    ] + $entryIds);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'error'   => $e->getMessage()
    ]);
}