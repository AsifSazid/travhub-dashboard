<?php
// PATH: /api/financial_entries_v2/store-gratuity.php
// (v2 -- task-bound, per user's explicit instruction)
//
// Records a GRATUITY (tip/gift) given TO a vendor or received FROM a
// client, against a specific task. Unlike Discount, this IS real cash
// movement (a tip is actually paid/received), so it has a bank_account leg
// and payment_method, same as Advance.
//
// POST { party_type: 'client'|'vendor', party_id, work_id, task_id, account_id, amount, date, purpose?, payment_method?, instrument_no? }
//
// Gratuity GIVEN to a vendor creates:
//   Row 1: account_head=gratuity,      debit  (expense: value given away)
//   Row 2: account_head=bank_account,  credit (+ac_banking_stmts, money out)
//
// Gratuity RECEIVED from a client creates:
//   Row 1: account_head=gratuity,      credit (income: value received)
//   Row 2: account_head=bank_account,  debit  (+ac_banking_stmts, money in)

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

    $partyType     = strtolower(trim($input['party_type'] ?? '')); // 'client' | 'vendor'
    $partyId       = trim($input['party_id'] ?? '');
    $workId        = trim($input['work_id'] ?? '');
    $taskId        = trim($input['task_id'] ?? '');
    $accountId     = trim($input['account_id'] ?? '');
    $amount        = (float)($input['amount'] ?? 0);
    $date          = $input['date'] ?? date('Y-m-d');
    $purpose       = trim($input['purpose'] ?? '');
    $paymentMethod = strtolower($input['payment_method'] ?? 'cash');
    $instrumentNo  = $input['instrument_no'] ?? null;

    $errors = [];
    if (!in_array($partyType, ['client', 'vendor'], true)) $errors[] = "party_type must be 'client' or 'vendor'";
    if (!$partyId)   $errors[] = 'party_id is required';
    if (!$workId)    $errors[] = 'work_id is required — gratuity must be tied to a task';
    if (!$taskId)    $errors[] = 'task_id is required — gratuity must be tied to a task';
    if (!$accountId) $errors[] = 'account_id is required';
    if ($amount <= 0) $errors[] = 'A positive amount is required';
    if ($errors) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]);
        exit;
    }

    $userName = $_SESSION['user_name'] ?? 'system';

    /* ================= Lookup party name (for display) ================= */
    $partyStmt = $pdo->prepare("
        SELECT user_name FROM financial_entries
        WHERE user_type = ? AND user_sys_id = ? LIMIT 1
    ");
    $partyStmt->execute([$partyType, $partyId]);
    $partyName = $partyStmt->fetchColumn() ?: $partyId;

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

    $isGivenToVendor = ($partyType === 'vendor');
    $finalPurpose = $purpose ?: ($isGivenToVendor ? "Gratuity given to {$partyName}" : "Gratuity received from {$partyName}");

    function _insertFinancialEntry(PDO $pdo, array $f): string
    {
        $ids  = generateV2IDs($pdo, 'financial_entries');
        $meta = buildMetaData(null, $f['user_name_actor']);
        $pdo->prepare("
            INSERT INTO financial_entries (
                uuid, sys_id, transaction_group_id,
                user_sys_id, user_name, user_type, account_head, vendor_type,
                task_sys_id, work_sys_id,
                date, purpose, type, related_type,
                is_paid, is_partial, is_discounted,
                amount, qty_rate, ref, meta_data
            ) VALUES (
                :uuid, :sys_id, :group_id,
                :user_sys_id, :user_name, :user_type, :account_head, :vendor_type,
                :task_sys_id, :work_sys_id,
                :date, :purpose, :type, :related_type,
                1, 0, 0,
                :amount, NULL, :ref, :meta_data
            )
        ")->execute([
            ':uuid' => $ids['uuid'], ':sys_id' => $ids['sys_id'], ':group_id' => $f['group_id'],
            ':user_sys_id' => $f['user_sys_id'], ':user_name' => $f['user_name'], ':user_type' => $f['user_type'],
            ':account_head' => $f['account_head'], ':vendor_type' => $f['vendor_type'],
            ':task_sys_id' => $f['task_sys_id'], ':work_sys_id' => $f['work_sys_id'],
            ':date' => $f['date'], ':purpose' => $f['purpose'], ':type' => $f['type'], ':related_type' => $f['related_type'],
            ':amount' => $f['amount'], ':ref' => $f['ref'], ':meta_data' => $meta,
        ]);
        return $ids['sys_id'];
    }

    $groupId = generateV2SysId($pdo, 'financial_entries');
    $baseRow = [
        'date' => $date, 'purpose' => $finalPurpose, 'ref' => null,
        'task_sys_id' => $taskId, 'work_sys_id' => $workId,
        'user_name_actor' => $userName, 'group_id' => $groupId,
    ];

    if ($isGivenToVendor) {
        $gratuityEntrySysId = _insertFinancialEntry($pdo, array_merge($baseRow, [
            'user_sys_id' => $partyId, 'user_name' => $partyName, 'user_type' => 'vendor',
            'account_head' => 'gratuity', 'vendor_type' => 0,
            'type' => 'debit', 'related_type' => 2, 'amount' => $amount,
        ]));
        $bankEntrySysId = _insertFinancialEntry($pdo, array_merge($baseRow, [
            'user_sys_id' => $accountId, 'user_name' => $accountName, 'user_type' => 'account',
            'account_head' => 'bank_account', 'vendor_type' => 1,
            'type' => 'credit', 'related_type' => 2, 'amount' => $amount,
        ]));
        postBankLegV2($pdo, $accountId, $accountName, $oldBalance, $amount, 'out', $date,
            $finalPurpose, $bankEntrySysId, $userName,
            $paymentMethod, $partyId, $partyName, 'vendor', $instrumentNo);
    } else {
        $gratuityEntrySysId = _insertFinancialEntry($pdo, array_merge($baseRow, [
            'user_sys_id' => $partyId, 'user_name' => $partyName, 'user_type' => 'client',
            'account_head' => 'gratuity', 'vendor_type' => null,
            'type' => 'credit', 'related_type' => 0, 'amount' => $amount,
        ]));
        $bankEntrySysId = _insertFinancialEntry($pdo, array_merge($baseRow, [
            'user_sys_id' => $accountId, 'user_name' => $accountName, 'user_type' => 'account',
            'account_head' => 'bank_account', 'vendor_type' => 1,
            'type' => 'debit', 'related_type' => 1, 'amount' => $amount,
        ]));
        postBankLegV2($pdo, $accountId, $accountName, $oldBalance, $amount, 'in', $date,
            $finalPurpose, $bankEntrySysId, $userName,
            $paymentMethod, $partyId, $partyName, 'client', $instrumentNo);
    }

    http_response_code(201);
    echo json_encode([
        'success'               => true,
        'message'               => 'Gratuity recorded',
        'transaction_group_id'  => $groupId,
        'gratuity_entry_sys_id' => $gratuityEntrySysId,
        'bank_entry_sys_id'     => $bankEntrySysId,
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'error'   => $e->getMessage()
    ]);
}