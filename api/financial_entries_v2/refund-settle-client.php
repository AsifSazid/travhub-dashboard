<?php
// PATH: /api/financial_entries_v2/refund-settle-client.php
// (v2 -- separate namespace from legacy /api/financial_entries/, used by both
//  the Task Financial tab and (later) the Client Ledger page)
//
// Settles a previously-declared client refund once the money actually
// leaves an account to the client. Mirrors refund-settle-vendor.php exactly.
// Supports partial settlement.
//
// POST { refund_group_id, client_id, account_id, amount, date, purpose?, ref? }
//
// Creates a NEW transaction group with:
//   Row 1: account_head=client_refund_payable, debit  (pending payable cleared)
//   Row 2: account_head=bank_account,          credit (+ac_banking_stmts, money out)

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

    $refundGroupId = trim($input['refund_group_id'] ?? '');
    $clientId      = trim($input['client_id'] ?? '');
    $accountId     = trim($input['account_id'] ?? '');
    $amount        = (float)($input['amount'] ?? 0);
    $date          = $input['date'] ?? date('Y-m-d');
    $purpose       = trim($input['purpose'] ?? '');
    $workId        = $input['work_id'] ?? null;
    $taskId        = $input['task_id'] ?? null;
    $paymentMethod = strtolower($input['payment_method'] ?? 'cash');
    $instrumentNo  = $input['instrument_no'] ?? null;

    $errors = [];
    if (!$refundGroupId) $errors[] = 'refund_group_id is required';
    if (!$clientId)      $errors[] = 'client_id is required';
    if (!$accountId)     $errors[] = 'account_id is required';
    if ($amount <= 0)    $errors[] = 'A positive amount is required';
    if ($errors) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]);
        exit;
    }

    $userName = $_SESSION['user_name'] ?? 'system';

    /* ================= Verify the refund group + compute remaining pending ================= */
    $refundLegsStmt = $pdo->prepare("SELECT * FROM financial_entries WHERE transaction_group_id = ?");
    $refundLegsStmt->execute([$refundGroupId]);
    $refundLegs = $refundLegsStmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$refundLegs) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Original refund declaration not found']);
        exit;
    }
    $payableLeg = null;
    foreach ($refundLegs as $l) { if ($l['account_head'] === 'client_refund_payable') { $payableLeg = $l; break; } }
    if (!$payableLeg) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'This transaction is not a pending client refund']);
        exit;
    }
    $originalAmount = (float)$payableLeg['amount'];
    $clientName     = $payableLeg['user_name'];

    $settledStmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) FROM financial_entries
        WHERE account_head = 'client_refund_payable' AND type = 'debit' AND ref = ?
    ");
    $settledStmt->execute([$refundGroupId]);
    $alreadySettled = (float)$settledStmt->fetchColumn();
    $remainingPending = round($originalAmount - $alreadySettled, 2);

    if ($remainingPending <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'This refund has already been fully settled']);
        exit;
    }
    if ($amount > $remainingPending + 0.01) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Amount exceeds remaining pending (৳{$remainingPending})"]);
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

    $finalPurpose = $purpose ?: "Refund paid to {$clientName}";

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

    $settleGroupId = generateV2SysId($pdo, 'financial_entries');
    $isFullySettled = abs($amount - $remainingPending) < 0.01;

    $baseRow = [
        'task_sys_id' => $taskId, 'task_title' => null,
        'work_sys_id' => $workId, 'work_title' => null,
        'date' => $date, 'purpose' => $finalPurpose,
        'ref' => $refundGroupId,
        'user_name_actor' => $userName, 'group_id' => $settleGroupId,
        'is_paid' => $isFullySettled ? 1 : 0, 'is_partial' => $isFullySettled ? 0 : 1,
    ];

    $payableClearSysId = _insertFinancialEntry($pdo, array_merge($baseRow, [
        'user_sys_id' => $clientId, 'user_name' => $clientName, 'user_type' => 'client',
        'account_head' => 'client_refund_payable', 'vendor_type' => null,
        'type' => 'debit', 'related_type' => 0, 'amount' => $amount,
    ]));

    $bankEntrySysId = _insertFinancialEntry($pdo, array_merge($baseRow, [
        'user_sys_id' => $accountId, 'user_name' => $accountName, 'user_type' => 'account',
        'account_head' => 'bank_account', 'vendor_type' => 1,
        'type' => 'credit', 'related_type' => 2, 'amount' => $amount,
    ]));

    postBankLegV2($pdo, $accountId, $accountName, $oldBalance, $amount, 'out', $date,
        $finalPurpose, $bankEntrySysId, $userName,
        $paymentMethod, $clientId, $clientName, 'client', $instrumentNo);

    http_response_code(201);
    echo json_encode([
        'success'              => true,
        'message'              => $isFullySettled ? 'Refund fully paid' : 'Partial refund payment recorded',
        'transaction_group_id' => $settleGroupId,
        'remaining_pending'    => round($remainingPending - $amount, 2),
        'fully_settled'        => $isFullySettled,
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'error'   => $e->getMessage()
    ]);
}