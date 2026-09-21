<?php
// PATH: /api/financial_entries_v2/store.php
// (v2 -- separate namespace from legacy /api/financial_entries/, used only by the new Task View)
//
// ============= TRUE DOUBLE-ENTRY (Chart of Accounts) =============
// Every action now creates a *balanced set* of financial_entries rows,
// all sharing one transaction_group_id. Each row carries an account_head
// naming which ledger account it hits:
//   accounts_payable    (AP, liability) -- what we owe vendors
//   accounts_receivable (AR, asset)     -- what clients owe us
//   purchase            (expense)       -- cost of services bought from vendors
//   sales               (revenue)       -- revenue from services sold to clients
//   bank_account        (asset)         -- a specific ac_banking row's balance
//
// Debit/credit follow standard rules: assets/expenses increase on debit,
// decrease on credit; liabilities/revenue increase on credit, decrease on
// debit.
//
// ---- CLIENT SIDE (Sale only) ----
// A sale always creates a receivable; settling it later (a "receive") is a
// SEPARATE call to receive-outstanding.php, mirroring how vendor purchases
// are settled via pay-outstanding.php. This endpoint no longer accepts
// type='credit' for a client_id.
//   Row 1: account_head=sales,               credit  (revenue recognized)
//   Row 2: account_head=accounts_receivable, debit   (client owes more)
//
// ---- VENDOR SIDE ----
// Always requires vendor_id + transaction_mode ('realtime' | 'non_realtime').
// type='credit' (a purchase from the vendor):
//   Row 1: account_head=purchase,            debit   (expense incurred)
//   Row 2: account_head=accounts_payable,    credit  (we owe vendor more)
//   -- realtime only, payment happens in the same action --
//   Row 3: account_head=accounts_payable,    debit   (that payable is cleared)
//   Row 4: account_head=bank_account,        credit  (+ac_banking_stmts, money out)
// type='debit' (a refund FROM the vendor):
//   Row 1: account_head=accounts_payable,    debit   (we owed less already)
//   -- realtime only --
//   Row 2: account_head=bank_account,        debit   (+ac_banking_stmts, money in)
//
// ---- EDITING ----
// Any edit to any field requires a reason + evidence file (enforced in
// update.php, not here) and appends to a rolling 20-entry edit_history JSON
// column rather than a separate table.
session_start();

require '../../server/db_connection.php';
require '../../server/uuid_with_system_id_generator.php';
require_once '../../server/sys_id_generator_v2.php';
require '../../server/generate_meta_data.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

function validateInput(array $data): array
{
    $errors = [];
    if (!isset($data['type']) || !in_array($data['type'], ['credit', 'debit'], true)) {
        $errors[] = 'Valid type (credit/debit) is required';
    }
    if (!isset($data['amount']) || !is_numeric($data['amount']) || $data['amount'] <= 0) {
        $errors[] = 'Valid positive amount is required';
    }
    if (empty(trim($data['purpose'] ?? ''))) {
        $errors[] = 'Purpose is required';
    }
    if (empty($data['client_id']) && empty($data['vendor_id'])) {
        $errors[] = 'Either client_id or vendor_id is required';
    }
    if (!empty($data['vendor_id'])) {
        $mode = $data['transaction_mode'] ?? null;
        if (!in_array($mode, ['realtime', 'non_realtime'], true)) {
            $errors[] = 'transaction_mode (realtime/non_realtime) is required for a vendor payment';
        }
        if ($mode === 'realtime' && empty($data['account_id'])) {
            $errors[] = 'account_id is required for a real-time vendor payment';
        }
    }
    return $errors;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $errors = validateInput($input);
    if ($errors) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]);
        exit;
    }

    /* ================= INPUT ================= */
    $type       = $input['type'];
    $amount     = (float)$input['amount'];
    $purpose    = trim($input['purpose']);
    $date       = $input['date'] ?? date('Y-m-d');
    $clientId   = $input['client_id']  ?? null;
    $vendorId   = $input['vendor_id']  ?? null;
    $accountId  = $input['account_id'] ?? null; // client refund via account, or vendor realtime payment
    $txnMode    = $input['transaction_mode'] ?? null; // vendor-side only
    $workId     = $input['work_id']    ?? null;
    $taskId     = $input['task_id']    ?? null;
    $ref        = $input['ref']        ?? null;
    $qtyRate    = $input['qty_rate']   ?? null; // JSON string: {"qty":2,"rate":18000}

    $userName = $_SESSION['user_name'] ?? 'system';

    $clientName = $vendorName = $accountName = $taskTitle = $workTitle = null;

    /* ================= LOOKUP (shared) ================= */
    if ($workId) {
        $s = $pdo->prepare("SELECT client_info FROM works WHERE sys_id = ?");
        $s->execute([$workId]);
        $ci = json_decode($s->fetchColumn() ?: '{}', true) ?? [];
        $workTitle = $ci['name'] ?? null;
    }

    if ($taskId) {
        $s = $pdo->prepare("SELECT workname FROM tasks WHERE sys_id = ?");
        $s->execute([$taskId]);
        $taskTitle = $s->fetchColumn();
    }

    if ($clientId) {
        $s = $pdo->prepare("SELECT name FROM clients WHERE sys_id = ?");
        $s->execute([$clientId]);
        $clientName = $s->fetchColumn();
        if (!$clientName) throw new Exception('Client not found');
    }

    if ($vendorId) {
        $s = $pdo->prepare("SELECT name FROM vendors WHERE sys_id = ?");
        $s->execute([$vendorId]);
        $vendorName = $s->fetchColumn();
        if (!$vendorName) throw new Exception('Vendor not found');
    }

    $oldBalance = null;
    if ($accountId) {
        $s = $pdo->prepare("SELECT acc_name, balance FROM ac_banking WHERE sys_id = ?");
        $s->execute([$accountId]);
        $accountInfo = $s->fetch(PDO::FETCH_ASSOC);
        if (!$accountInfo) throw new Exception('Account not found');
        $accountName = $accountInfo['acc_name'];
        $oldBalance  = (float)$accountInfo['balance'];
    }

    /* ================= DATE FORMAT ================= */
    if ($date) {
        $tz  = new DateTimeZone('Asia/Dhaka');
        $dt  = new DateTime($date, $tz);
        $now = (new DateTime('now', $tz))->format('H:i:s');
        $dt->setTime(...explode(':', $now));
        $date = $dt->format('Y-m-d H:i:s');
    }

    /* ================= Helper: insert one financial_entries row ================= */
    function _insertFinancialEntry(PDO $pdo, array $f): string
    {
        $ids  = generateV2IDs($pdo, 'financial_entries');
        $meta = buildMetaData(null, $f['user_name_actor']);

        $pdo->prepare("
            INSERT INTO financial_entries (
                uuid, sys_id, transaction_group_id,
                user_sys_id, user_name, user_type, account_head, vendor_type,
                task_sys_id, task_title,
                work_sys_id, work_title,
                date, purpose, type, related_type,
                is_paid, is_partial, is_discounted,
                amount, qty_rate, ref, meta_data
            ) VALUES (
                :uuid, :sys_id, :group_id,
                :user_sys_id, :user_name, :user_type, :account_head, :vendor_type,
                :task_sys_id, :task_title,
                :work_sys_id, :work_title,
                :date, :purpose, :type, :related_type,
                0, 0, 0,
                :amount, :qty_rate, :ref, :meta_data
            )
        ")->execute([
            ':uuid'         => $ids['uuid'],
            ':sys_id'       => $ids['sys_id'],
            ':group_id'     => $f['group_id'],
            ':user_sys_id'  => $f['user_sys_id'],
            ':user_name'    => $f['user_name'],
            ':user_type'    => $f['user_type'],
            ':account_head' => $f['account_head'],
            ':vendor_type'  => $f['vendor_type'],
            ':task_sys_id'  => $f['task_sys_id'],
            ':task_title'   => $f['task_title'],
            ':work_sys_id'  => $f['work_sys_id'],
            ':work_title'   => $f['work_title'],
            ':date'         => $f['date'],
            ':purpose'      => $f['purpose'],
            ':type'         => $f['type'],
            ':related_type' => $f['related_type'],
            ':amount'       => $f['amount'],
            ':qty_rate'     => $f['qty_rate'],
            ':ref'          => $f['ref'],
            ':meta_data'    => $meta,
        ]);

        return $ids['sys_id'];
    }

    /* ================= Helper: bank leg (ac_banking + ac_banking_stmts) ================= */
    // $direction: 'out' (money leaves the account) or 'in' (money enters it)
    function _postBankLeg(PDO $pdo, string $accountId, string $accountName, float $oldBalance, float $amount, string $direction, string $date, string $particular, string $refEntrySysId, string $userName): void
    {
        $stmtIds  = generateV2IDs($pdo, 'ac_banking_stmts');
        $stmtMeta = buildMetaData(null, $userName);

        if ($direction === 'out') {
            $newBalance = $oldBalance - $amount;
            $pdo->prepare("UPDATE ac_banking SET balance = :bal WHERE sys_id = :id")
                ->execute([':bal' => $newBalance, ':id' => $accountId]);
            $pdo->prepare("
                INSERT INTO ac_banking_stmts
                (uuid, sys_id, ledger_db_id, name, date, particular,
                 withdraw, deposit, balance, related_type, meta_data, ref)
                VALUES
                (:uuid, :sys_id, :ledger, :name, :date, :particular,
                 :withdraw, 0, :balance, 2, :meta, :ref)
            ")->execute([
                ':uuid' => $stmtIds['uuid'], ':sys_id' => $stmtIds['sys_id'],
                ':ledger' => $accountId, ':name' => $accountName, ':date' => $date,
                ':particular' => $particular, ':withdraw' => $amount, ':balance' => $newBalance,
                ':meta' => $stmtMeta, ':ref' => $refEntrySysId,
            ]);
        } else {
            $newBalance = $oldBalance + $amount;
            $pdo->prepare("UPDATE ac_banking SET balance = :bal WHERE sys_id = :id")
                ->execute([':bal' => $newBalance, ':id' => $accountId]);
            $pdo->prepare("
                INSERT INTO ac_banking_stmts
                (uuid, sys_id, ledger_db_id, name, date, particular,
                 withdraw, deposit, balance, related_type, meta_data, ref)
                VALUES
                (:uuid, :sys_id, :ledger, :name, :date, :particular,
                 0, :deposit, :balance, 1, :meta, :ref)
            ")->execute([
                ':uuid' => $stmtIds['uuid'], ':sys_id' => $stmtIds['sys_id'],
                ':ledger' => $accountId, ':name' => $accountName, ':date' => $date,
                ':particular' => $particular, ':deposit' => $amount, ':balance' => $newBalance,
                ':meta' => $stmtMeta, ':ref' => $refEntrySysId,
            ]);
        }
    }

    $groupId = generateV2SysId($pdo, 'financial_entries'); // this call's shared transaction_group_id
    $entrySysIds = [];

    $baseRow = [
        'task_sys_id'     => $taskId,
        'task_title'      => $taskTitle,
        'work_sys_id'     => $workId,
        'work_title'      => $workTitle,
        'date'            => $date,
        'purpose'         => $purpose,
        'qty_rate'        => $qtyRate,
        'ref'             => $ref,
        'user_name_actor' => $userName,
        'group_id'        => $groupId,
    ];

    if ($vendorId) {
        /* ================= VENDOR SIDE ================= */
        if ($type === 'credit') {
            // Purchase: expense (debit) + payable increases (credit)
            $entrySysIds['purchase_entry_sys_id'] = _insertFinancialEntry($pdo, array_merge($baseRow, [
                'user_sys_id' => $vendorId, 'user_name' => $vendorName, 'user_type' => 'vendor',
                'account_head' => 'purchase', 'vendor_type' => 0,
                'type' => 'debit', 'related_type' => 2, 'amount' => $amount,
            ]));
            $apEntrySysId = _insertFinancialEntry($pdo, array_merge($baseRow, [
                'user_sys_id' => $vendorId, 'user_name' => $vendorName, 'user_type' => 'vendor',
                'account_head' => 'accounts_payable', 'vendor_type' => 0,
                'type' => 'credit', 'related_type' => 2, 'amount' => $amount,
            ]));
            $entrySysIds['payable_entry_sys_id'] = $apEntrySysId;

            if ($txnMode === 'realtime') {
                // That payable is cleared immediately (debit AP) + money leaves the bank (credit)
                $entrySysIds['payable_cleared_entry_sys_id'] = _insertFinancialEntry($pdo, array_merge($baseRow, [
                    'user_sys_id' => $vendorId, 'user_name' => $vendorName, 'user_type' => 'vendor',
                    'account_head' => 'accounts_payable', 'vendor_type' => 0,
                    'type' => 'debit', 'related_type' => 2, 'amount' => $amount,
                ]));
                $bankEntrySysId = _insertFinancialEntry($pdo, array_merge($baseRow, [
                    'user_sys_id' => $accountId, 'user_name' => $accountName, 'user_type' => 'account',
                    'account_head' => 'bank_account', 'vendor_type' => 1,
                    'type' => 'credit', 'related_type' => 2, 'amount' => $amount,
                ]));
                $entrySysIds['bank_entry_sys_id'] = $bankEntrySysId;
                _postBankLeg($pdo, $accountId, $accountName, $oldBalance, $amount, 'out', $date,
                    "Payment to {$vendorName} — {$purpose}", $bankEntrySysId, $userName);
            }
        } else {
            // Vendor refund: payable decreases (debit)
            $entrySysIds['payable_entry_sys_id'] = _insertFinancialEntry($pdo, array_merge($baseRow, [
                'user_sys_id' => $vendorId, 'user_name' => $vendorName, 'user_type' => 'vendor',
                'account_head' => 'accounts_payable', 'vendor_type' => 0,
                'type' => 'debit', 'related_type' => 0, 'amount' => $amount,
            ]));
            if ($txnMode === 'realtime') {
                $bankEntrySysId = _insertFinancialEntry($pdo, array_merge($baseRow, [
                    'user_sys_id' => $accountId, 'user_name' => $accountName, 'user_type' => 'account',
                    'account_head' => 'bank_account', 'vendor_type' => 1,
                    'type' => 'debit', 'related_type' => 3, 'amount' => $amount,
                ]));
                $entrySysIds['bank_entry_sys_id'] = $bankEntrySysId;
                _postBankLeg($pdo, $accountId, $accountName, $oldBalance, $amount, 'in', $date,
                    "Refund from {$vendorName} — {$purpose}", $bankEntrySysId, $userName);
            }
        }

    } else {
        /* ================= CLIENT SIDE (Sale only) ================= */
        // A sale always creates a receivable; it is settled later via
        // receive-outstanding.php (the client-side mirror of vendor's
        // pay-outstanding.php), never as part of this same call.
        if ($type !== 'debit') {
            throw new Exception('Client-side store.php only handles sales (type=debit). Use receive-outstanding.php to record a receive.');
        }
        // Sale: revenue (credit) + receivable increases (debit)
        $entrySysIds['sales_entry_sys_id'] = _insertFinancialEntry($pdo, array_merge($baseRow, [
            'user_sys_id' => $clientId, 'user_name' => $clientName, 'user_type' => 'client',
            'account_head' => 'sales', 'vendor_type' => null,
            'type' => 'credit', 'related_type' => 1, 'amount' => $amount,
        ]));
        $entrySysIds['receivable_entry_sys_id'] = _insertFinancialEntry($pdo, array_merge($baseRow, [
            'user_sys_id' => $clientId, 'user_name' => $clientName, 'user_type' => 'client',
            'account_head' => 'accounts_receivable', 'vendor_type' => null,
            'type' => 'debit', 'related_type' => 1, 'amount' => $amount,
        ]));
    }

    http_response_code(201);
    echo json_encode(array_merge([
        'success'              => true,
        'message'              => ucfirst($type) . ' transaction recorded successfully',
        'transaction_group_id' => $groupId,
        // Kept for backward-compat with callers reading a single sys_id --
        // the primary entry of the group (purchase or sales row).
        'sys_id'  => $entrySysIds['purchase_entry_sys_id'] ?? $entrySysIds['sales_entry_sys_id'] ?? $entrySysIds['receivable_entry_sys_id'] ?? null,
    ], $entrySysIds));

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'error'   => $e->getMessage()
    ]);
}