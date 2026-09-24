<?php
// PATH: /api/financial_entries_v2/store-discount.php
// (v2 -- task-bound, per user's explicit instruction)
//
// Records a DISCOUNT against a specific task's bill -- either given TO a
// client (reduces what they owe) or received FROM a vendor (reduces what
// we owe them). Unlike Advance/Payment, a discount does NOT move any real
// cash -- it only reduces the outstanding balance on paper, so there is
// no bank_account leg and no payment_method/account_id needed here.
//
// POST { party_type: 'client'|'vendor', party_id, work_id, task_id, amount, date, purpose? }
//
// Client-side discount creates:
//   Row 1: account_head=discount,              debit  (expense-like: value given away)
//   Row 2: account_head=accounts_receivable,   credit (what they owe us drops)
//
// Vendor-side discount creates:
//   Row 1: account_head=accounts_payable,      debit  (what we owe them drops)
//   Row 2: account_head=discount,              credit (income-like: value we didn't have to pay)

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

    $partyType = strtolower(trim($input['party_type'] ?? '')); // 'client' | 'vendor'
    $partyId   = trim($input['party_id'] ?? '');
    $workId    = trim($input['work_id'] ?? '');
    $taskId    = trim($input['task_id'] ?? '');
    $amount    = (float)($input['amount'] ?? 0);
    $date      = $input['date'] ?? date('Y-m-d');
    $purpose   = trim($input['purpose'] ?? '');

    $errors = [];
    if (!in_array($partyType, ['client', 'vendor'], true)) $errors[] = "party_type must be 'client' or 'vendor'";
    if (!$partyId)  $errors[] = 'party_id is required';
    if (!$workId)   $errors[] = 'work_id is required — discount must be tied to a task';
    if (!$taskId)   $errors[] = 'task_id is required — discount must be tied to a task';
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

    /* ================= DATE FORMAT ================= */
    $tz  = new DateTimeZone('Asia/Dhaka');
    $dt  = new DateTime($date, $tz);
    $now = (new DateTime('now', $tz))->format('H:i:s');
    $dt->setTime(...explode(':', $now));
    $date = $dt->format('Y-m-d H:i:s');

    $finalPurpose = $purpose ?: ($partyType === 'client' ? "Discount given to {$partyName}" : "Discount received from {$partyName}");

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
                1, 0, 1,
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

    if ($partyType === 'client') {
        $discountEntrySysId = _insertFinancialEntry($pdo, array_merge($baseRow, [
            'user_sys_id' => $partyId, 'user_name' => $partyName, 'user_type' => 'client',
            'account_head' => 'discount', 'vendor_type' => null,
            'type' => 'debit', 'related_type' => 5, 'amount' => $amount,
        ]));
        $partyEntrySysId = _insertFinancialEntry($pdo, array_merge($baseRow, [
            'user_sys_id' => $partyId, 'user_name' => $partyName, 'user_type' => 'client',
            'account_head' => 'accounts_receivable', 'vendor_type' => null,
            'type' => 'credit', 'related_type' => 0, 'amount' => $amount,
        ]));
    } else {
        $partyEntrySysId = _insertFinancialEntry($pdo, array_merge($baseRow, [
            'user_sys_id' => $partyId, 'user_name' => $partyName, 'user_type' => 'vendor',
            'account_head' => 'accounts_payable', 'vendor_type' => 0,
            'type' => 'debit', 'related_type' => 2, 'amount' => $amount,
        ]));
        $discountEntrySysId = _insertFinancialEntry($pdo, array_merge($baseRow, [
            'user_sys_id' => $partyId, 'user_name' => $partyName, 'user_type' => 'vendor',
            'account_head' => 'discount', 'vendor_type' => 0,
            'type' => 'credit', 'related_type' => 5, 'amount' => $amount,
        ]));
    }

    http_response_code(201);
    echo json_encode([
        'success'               => true,
        'message'               => 'Discount recorded',
        'transaction_group_id'  => $groupId,
        'discount_entry_sys_id' => $discountEntrySysId,
        'party_entry_sys_id'    => $partyEntrySysId,
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'error'   => $e->getMessage()
    ]);
}