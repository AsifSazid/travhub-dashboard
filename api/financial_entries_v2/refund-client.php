<?php
// PATH: /api/financial_entries_v2/refund-client.php
// (v2 -- separate namespace from legacy /api/financial_entries/, used by both
//  the Task Financial tab and (later) the Client Ledger page)
//
// Declares a refund against an existing client sale, whether or not that
// sale has been received yet. Mirrors refund-vendor.php exactly, but the
// obligation direction is reversed: instead of being owed money BY the
// vendor, we now OWE money TO the client (client_refund_payable).
//
// POST { sale_group_id, client_id, refund_amount?, refund_charge?, date, purpose?, ref? }
// Exactly one of refund_amount / refund_charge is required:
//   refund_amount + refund_charge = original_sale_amount (refundable remaining)
//
// Creates a NEW transaction group with:
//   Row 1: account_head=sales,                   debit  (revenue reversed, by refund_amount)
//   Row 2: account_head=refund_charge,           debit  (charge recognized, by refund_charge)
//   Row 3: account_head=accounts_receivable,     credit (only if the sale still had an
//                                                  outstanding receivable balance -- reduces it)
//   Row 4: account_head=client_refund_payable,   credit (what we now OWE the client for
//                                                  this refund, by refund_amount)
//
// client_refund_payable is later cleared by refund-settle-client.php once the
// money actually leaves an account to the client.

session_start();

require '../../server/db_connection.php';
require_once '../../server/permissions.php';
requireFullAccountingAccess($pdo, true);
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

    $saleGroupId    = trim($input['sale_group_id'] ?? '');
    $clientId       = trim($input['client_id'] ?? '');
    $refundAmountIn = isset($input['refund_amount']) && $input['refund_amount'] !== '' ? (float)$input['refund_amount'] : null;
    $refundChargeIn = isset($input['refund_charge']) && $input['refund_charge'] !== '' ? (float)$input['refund_charge'] : null;
    $date           = $input['date'] ?? date('Y-m-d');
    $purpose        = trim($input['purpose'] ?? '');
    $workId         = $input['work_id'] ?? null;
    $taskId         = $input['task_id'] ?? null;

    $errors = [];
    if (!$saleGroupId) $errors[] = 'sale_group_id is required';
    if (!$clientId)    $errors[] = 'client_id is required';
    if ($refundAmountIn === null && $refundChargeIn === null) $errors[] = 'Either refund_amount or refund_charge is required';
    if ($refundAmountIn !== null && $refundAmountIn < 0) $errors[] = 'refund_amount must not be negative';
    if ($refundChargeIn !== null && $refundChargeIn < 0) $errors[] = 'refund_charge must not be negative';
    if ($errors) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]);
        exit;
    }

    $userName = $_SESSION['user_name'] ?? 'system';

    /* ================= Verify the sale group ================= */
    $saleLegsStmt = $pdo->prepare("SELECT * FROM financial_entries WHERE transaction_group_id = ?");
    $saleLegsStmt->execute([$saleGroupId]);
    $saleLegs = $saleLegsStmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$saleLegs) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Original sale transaction not found']);
        exit;
    }
    $salesLeg = null;
    foreach ($saleLegs as $l) { if ($l['account_head'] === 'sales') { $salesLeg = $l; break; } }
    if (!$salesLeg) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'This transaction is not a client sale']);
        exit;
    }
    $originalAmount = (float)$salesLeg['amount'];
    $clientName     = $salesLeg['user_name'];

    // Already-refunded amount against this sale (partial/repeated refunds supported).
    $refundedStmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) FROM financial_entries
        WHERE account_head = 'sales' AND type = 'debit' AND ref = ?
    ");
    $refundedStmt->execute([$saleGroupId]);
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

    /* ================= Current outstanding receivable on this sale ================= */
    $receivedStmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) FROM financial_entries
        WHERE account_head = 'accounts_receivable' AND type = 'credit' AND ref = ?
    ");
    $receivedStmt->execute([$saleGroupId]);
    $alreadyReceived = (float)$receivedStmt->fetchColumn();
    $outstandingReceivable = max(round($originalAmount - $alreadyReceived, 2), 0);
    $receivableReduction = min($refundAmount + $refundCharge, $outstandingReceivable);

    /* ================= DATE FORMAT ================= */
    $tz  = new DateTimeZone('Asia/Dhaka');
    $dt  = new DateTime($date, $tz);
    $now = (new DateTime('now', $tz))->format('H:i:s');
    $dt->setTime(...explode(':', $now));
    $date = $dt->format('Y-m-d H:i:s');

    $finalPurpose = $purpose ?: "Refund to {$clientName} against sale {$saleGroupId}";

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
        'ref' => $saleGroupId,
        'user_name_actor' => $userName, 'group_id' => $refundGroupId,
        'is_partial' => $isPartialRefund ? 1 : 0,
    ];

    $entryIds = [];

    if ($refundAmount > 0) {
        $entryIds['sales_reversal_sys_id'] = _insertFinancialEntry($pdo, array_merge($baseRow, [
            'user_sys_id' => $clientId, 'user_name' => $clientName, 'user_type' => 'client',
            'account_head' => 'sales', 'vendor_type' => null,
            'type' => 'debit', 'related_type' => 1, 'amount' => $refundAmount,
        ]));
    }
    if ($refundCharge > 0) {
        $entryIds['refund_charge_sys_id'] = _insertFinancialEntry($pdo, array_merge($baseRow, [
            'user_sys_id' => $clientId, 'user_name' => $clientName, 'user_type' => 'client',
            'account_head' => 'refund_charge', 'vendor_type' => null,
            'type' => 'debit', 'related_type' => 1, 'amount' => $refundCharge,
        ]));
    }
    if ($receivableReduction > 0) {
        $entryIds['receivable_reduction_sys_id'] = _insertFinancialEntry($pdo, array_merge($baseRow, [
            'user_sys_id' => $clientId, 'user_name' => $clientName, 'user_type' => 'client',
            'account_head' => 'accounts_receivable', 'vendor_type' => null,
            'type' => 'credit', 'related_type' => 0, 'amount' => $receivableReduction,
        ]));
    }
    if ($refundAmount > 0) {
        // What we now OWE the client for this refund -- settled later via
        // refund-settle-client.php once the money actually leaves an account.
        $entryIds['refund_payable_sys_id'] = _insertFinancialEntry($pdo, array_merge($baseRow, [
            'user_sys_id' => $clientId, 'user_name' => $clientName, 'user_type' => 'client',
            'account_head' => 'client_refund_payable', 'vendor_type' => null,
            'type' => 'credit', 'related_type' => 0, 'amount' => $refundAmount,
        ]));
    }

    http_response_code(201);
    echo json_encode([
        'success'              => true,
        'message'              => 'Refund declared',
        'transaction_group_id' => $refundGroupId,
        'refund_amount'        => $refundAmount,
        'refund_charge'        => $refundCharge,
        'pending_payable'      => $refundAmount, // still to be settled once cash leaves
    ] + $entryIds);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'error'   => $e->getMessage()
    ]);
}