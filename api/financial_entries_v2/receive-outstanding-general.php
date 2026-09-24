<?php
// PATH: /api/financial_entries_v2/receive-outstanding-general.php
// (v2 -- used by the Client Ledger page)
//
// Records a GENERAL receive from a client, NOT tied to any specific sale.
// Mirrors pay-outstanding-general.php exactly, but for the receivable side.
// No FIFO or auto-allocation across specific bills, per the user's explicit
// instruction -- this simply reduces the client's aggregate
// accounts_receivable balance.
//
// POST { client_id, account_id, amount, date, purpose?, payment_method?, instrument_no? }
//
// Creates a NEW transaction group with:
//   Row 1: account_head=accounts_receivable, credit (receivable reduced, NO
//          ref to any specific sale group)
//   Row 2: account_head=bank_account,        debit  (+ac_banking_stmts, money in)

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

    $clientId      = trim($input['client_id'] ?? '');
    $accountId     = trim($input['account_id'] ?? '');
    $amount        = (float)($input['amount'] ?? 0);
    $date          = $input['date'] ?? date('Y-m-d');
    $purpose       = trim($input['purpose'] ?? '');
    $paymentMethod = strtolower($input['payment_method'] ?? 'cash');
    $instrumentNo  = $input['instrument_no'] ?? null;

    $errors = [];
    if (!$clientId)  $errors[] = 'client_id is required';
    if (!$accountId) $errors[] = 'account_id is required';
    if ($amount <= 0) $errors[] = 'A positive amount is required';
    if ($errors) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]);
        exit;
    }

    $userName = $_SESSION['user_name'] ?? 'system';

    /* ================= Lookup client name (for display) ================= */
    $clientStmt = $pdo->prepare("
        SELECT user_name FROM financial_entries
        WHERE user_type = 'client' AND user_sys_id = ? LIMIT 1
    ");
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
    $dt  = new DateTime($date, $tz);
    $now = (new DateTime('now', $tz))->format('H:i:s');
    $dt->setTime(...explode(':', $now));
    $date = $dt->format('Y-m-d H:i:s');

    $finalPurpose = $purpose ?: "General receive from {$clientName}";

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
        'ref' => null, // deliberately no ref -- this is NOT tied to a specific sale
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

    postBankLegV2($pdo, $accountId, $accountName, $oldBalance, $amount, 'in', $date,
        $finalPurpose, $bankEntrySysId, $userName,
        $paymentMethod, $clientId, $clientName, 'client', $instrumentNo);

    http_response_code(201);
    echo json_encode([
        'success'                => true,
        'message'                => 'General receive recorded',
        'transaction_group_id'   => $groupId,
        'receivable_entry_sys_id' => $arEntrySysId,
        'bank_entry_sys_id'      => $bankEntrySysId,
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'error'   => $e->getMessage()
    ]);
}