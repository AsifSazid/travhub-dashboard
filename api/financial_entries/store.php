<?php
// PATH: /api/financial_entries/store.php
// Changes:
//   - related_type explicitly set করা হয়েছে (আগে default 1 বসতো সব ক্ষেত্রে)
//   - is_partial column যোগ করা হয়েছে INSERT এ
//   - Logic:
//       client + debit   → related_type=1 (sale)
//       client + credit  → related_type=0 (refund — Refund button থেকে আসছে)
//       vendor + credit  → related_type=2 (purchase)
//       vendor + debit   → related_type=0 (vendor refund)
//       account + credit → related_type=4 (payment from account)
//       account + debit  → related_type=3 (receive to account)
session_start();

require '../../server/db_connection.php';
require '../../server/uuid_with_system_id_generator.php';
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
    $vendorType = isset($input['vendor_type']) ? (int)$input['vendor_type'] : null; // 0=vendor, 1=own account
    $accountId  = $input['account_id'] ?? null;
    $workId     = $input['work_id']    ?? null;
    $taskId     = $input['task_id']    ?? null;
    $ref        = $input['ref']        ?? $accountId ?? null;
    $qtyRate    = $input['qty_rate']    ?? null; // JSON string: {"qty":2,"rate":18000}

    $clientName = $vendorName = $accountName = $taskTitle = $workTitle = null;
    $userType   = '';

    /* ================= LOOKUP ================= */
    if ($workId) {
        $s = $pdo->prepare("SELECT title FROM com_works WHERE sys_id = ?");
        $s->execute([$workId]);
        $workTitle = $s->fetchColumn();
    }

    if ($taskId) {
        $s = $pdo->prepare("SELECT title FROM old_tasks WHERE sys_id = ?");
        $s->execute([$taskId]);
        $taskTitle = $s->fetchColumn();
    }

    if ($clientId) {
        $s = $pdo->prepare("SELECT name FROM clients WHERE sys_id = ?");
        $s->execute([$clientId]);
        $clientName = $s->fetchColumn();
        $userType   = 'client';
        if (!$clientName) throw new Exception('Client not found');
    }

    if ($vendorId) {
        $s = $pdo->prepare("SELECT name FROM vendors WHERE sys_id = ?");
        $s->execute([$vendorId]);
        $vendorName = $s->fetchColumn();
        $userType   = 'vendor';
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
        $userType    = 'account';
    }

    /* ================= DATE FORMAT ================= */
    if ($date) {
        $tz  = new DateTimeZone('Asia/Dhaka');
        $dt  = new DateTime($date, $tz);
        $now = (new DateTime('now', $tz))->format('H:i:s');
        $dt->setTime(...explode(':', $now));
        $date = $dt->format('Y-m-d H:i:s');
    }

    /* ================= DETERMINE related_type ================= */
    // client + debit   = sale       (1)
    // client + credit  = refund     (0) ← Refund button
    // vendor + credit  = purchase   (2)
    // vendor + debit   = refund     (0) ← vendor refund button
    // account + credit = payment    (4) ← paying from account
    // account + debit  = receive    (3) ← receiving to account
    $relatedType = 1; // default fallback

    if ($clientId) {
        $relatedType = ($type === 'debit') ? 1 : 0;
    } elseif ($vendorId) {
        $relatedType = ($type === 'credit') ? 2 : 0;
    } elseif ($accountId) {
        $relatedType = ($type === 'credit') ? 2 : 3; // own account: credit=purchase-like, debit=receive-like
    }

    /* ================= INSERT financial_entries ================= */
    $ids  = generateIDs('financial_entries');
    $meta = buildMetaData(null, $_SESSION['user_name'] ?? 'system');

    $pdo->prepare("
        INSERT INTO financial_entries (
            uuid, sys_id,
            user_sys_id, user_name, user_type, vendor_type,
            task_sys_id, task_title,
            work_sys_id, work_title,
            date, purpose, type, related_type,
            is_paid, is_partial, is_discounted,
            amount, qty_rate, ref, meta_data
        ) VALUES (
            :uuid, :sys_id,
            :user_sys_id, :user_name, :user_type, :vendor_type,
            :task_sys_id, :task_title,
            :work_sys_id, :work_title,
            :date, :purpose, :type, :related_type,
            0, 0, 0,
            :amount, :qty_rate, :ref, :meta_data
        )
    ")->execute([
        ':uuid'         => $ids['uuid'],
        ':sys_id'       => $ids['sys_id'],
        ':user_sys_id'  => $clientId ?? $vendorId ?? $accountId ?? null,
        ':user_name'    => $clientName ?? $vendorName ?? $accountName ?? null,
        ':user_type'    => $userType,
        ':vendor_type'  => $vendorType,
        ':task_sys_id'  => $taskId,
        ':task_title'   => $taskTitle,
        ':work_sys_id'  => $workId,
        ':work_title'   => $workTitle,
        ':date'         => $date,
        ':purpose'      => $purpose,
        ':type'         => $type,
        ':related_type' => $relatedType,
        ':amount'       => $amount,
        ':qty_rate'     => $qtyRate,
        ':ref'          => $ref,
        ':meta_data'    => $meta
    ]);

    $feSysId = $ids['sys_id'];

    /* ================= ac_banking HANDLING (own account only) ================= */
    // vendorType=1 মানে Own Account select করা হয়েছে
    if ($vendorType === 1 && $accountId && $oldBalance !== null) {

        $stmtIds  = generateIDs('ac_banking_stmts');
        $stmtMeta = buildMetaData(null, $_SESSION['user_name'] ?? 'system');

        if ($type === 'credit') {
            // Account থেকে payment দেওয়া হচ্ছে → withdraw
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
                ':uuid'       => $stmtIds['uuid'],
                ':sys_id'     => $stmtIds['sys_id'],
                ':ledger'     => $accountId,
                ':name'       => $accountName,
                ':date'       => $date,
                ':particular' => $purpose,
                ':withdraw'   => $amount,
                ':balance'    => $newBalance,
                ':meta'       => $stmtMeta,
                ':ref'        => $feSysId
            ]);

        } elseif ($type === 'debit') {
            // Account এ টাকা আসছে → deposit
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
                ':uuid'       => $stmtIds['uuid'],
                ':sys_id'     => $stmtIds['sys_id'],
                ':ledger'     => $accountId,
                ':name'       => $accountName,
                ':date'       => $date,
                ':particular' => $purpose,
                ':deposit'    => $amount,
                ':balance'    => $newBalance,
                ':meta'       => $stmtMeta,
                ':ref'        => $feSysId
            ]);
        }
    }

    http_response_code(201);
    echo json_encode([
        'success'        => true,
        'message'        => ucfirst($type) . ' transaction recorded successfully',
        'transaction_id' => $pdo->lastInsertId(),
        'uuid'           => $ids['uuid'],
        'related_type'   => $relatedType
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'error'   => $e->getMessage()
    ]);
}