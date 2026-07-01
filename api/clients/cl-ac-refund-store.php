<?php
// PATH: /api/clients/cl-ac-refund-store.php  ← NEW FILE
//
// Refund to Client
//   Case A — Non-physical (isPhysical=0):
//       শুধু financial_entries insert করে
//       related_type=0, type=credit, user_type=client
//
//   Case B — Physical (isPhysical=1):
//       ac_banking_stmts এ withdraw entry (cl-ac-receive-store.php এর REVERSE)
//       + financial_entries insert
//       related_type=0, type=credit, user_type=client
//
// ve-ac-payment-store.php এর সাথে পার্থক্য:
//   ve-ac-payment-store.php → vendor+debit (related_type=4, bank withdraw)
//   cl-ac-refund-store.php  → client+credit (related_type=0, bank withdraw in physical case)
session_start();

require '../../server/db_connection.php';
require '../../server/uuid_with_system_id_generator.php';
require '../../server/generate_meta_data.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

$clientId        = $data['clientId']        ?? null;
$clientName      = $data['clientName']      ?? null;
$amount          = $data['amount']          ?? 0;
$particular      = $data['particular']      ?? '';
$transactionDate = $data['transactionDate'] ?? date('Y-m-d H:i:s');
$isPhysical      = isset($data['isPhysical']) ? (int)$data['isPhysical'] : 0;

// Physical refund এর জন্য
$accountId      = $data['accountId']      ?? '';
$accountName    = $data['accountName']    ?? '';
$transferMethod = $data['transferMethod'] ?? 'cash';
$isHistorical   = 0;
$deposit        = 0;

if (!is_numeric($amount) || $amount <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid amount']);
    exit;
}
if (!$clientId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Client is required']);
    exit;
}
if ($isPhysical && (!$accountId || !$accountName)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Account is required for physical refund']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmtSysId = '';

    if ($isPhysical) {
        /* ===== Physical Refund: Bank থেকে withdraw ===== */

        // 1. Fetch account
        $accStmt = $pdo->prepare("SELECT balance FROM ac_banking WHERE sys_id = ? FOR UPDATE");
        $accStmt->execute([$accountId]);
        $account = $accStmt->fetch(PDO::FETCH_ASSOC);
        if (!$account) throw new Exception('Account not found');
        $currentBalance = (float)$account['balance'];

        // 2. Opening balance check
        $openingStmt = $pdo->prepare("
            SELECT MIN(date) as opening_date FROM ac_banking_stmts
            WHERE ledger_db_id = :aid AND particular = 'Opening Balance'
        ");
        $openingStmt->execute([':aid' => $accountId]);
        $openingDate = $openingStmt->fetch(PDO::FETCH_ASSOC)['opening_date'] ?? null;
        if ($openingDate && $transactionDate < $openingDate) $isHistorical = 1;

        // 3. Backdated check
        $dateStmt = $pdo->prepare("SELECT DATEDIFF(NOW(), :txn_date) as days_diff");
        $dateStmt->execute([':txn_date' => $transactionDate]);
        $daysDiff = (int)$dateStmt->fetch(PDO::FETCH_ASSOC)['days_diff'];
        if (!$isHistorical && $daysDiff > 10) throw new Exception('Backdated entry limit is 10 days.');

        // 4. Balance check — refund withdraw করে তাই sufficient balance লাগবে
        if (!$isHistorical && $currentBalance < $amount) {
            throw new Exception('Insufficient balance in account for this refund');
        }

        // 5. New balance
        $newBalance = $isHistorical ? $currentBalance : ($currentBalance - $amount);

        // 6. Update account
        if (!$isHistorical) {
            $pdo->prepare("UPDATE ac_banking SET balance = :bal WHERE sys_id = :id")
                ->execute([':bal' => $newBalance, ':id' => $accountId]);
        }

        // financial_entries UUID আগেই generate করি — ac_banking_stmts.ref এ বসাবো
        $feUUIDs = generateIDs('financial_entries');

        // 7. Insert ac_banking_stmts (withdraw, related_type=1 for refund outgoing)
        $stmtUUIDs = generateIDs('ac_banking_stmts');
        $stmtMeta  = buildMetaData(null, $_SESSION['user_name'] ?? 'system');
        $pdo->prepare("
            INSERT INTO ac_banking_stmts
            (uuid, sys_id, ledger_db_id, name, date, particular,
             withdraw, deposit, balance, transfer_method, related_type, meta_data, is_historical, ref)
            VALUES
            (:uuid, :sys_id, :ledger, :name, :date, :particular,
             :withdraw, :deposit, :balance, :method, 1, :meta, :hist, :ref)
        ")->execute([
            ':uuid'       => $stmtUUIDs['uuid'],
            ':sys_id'     => $stmtUUIDs['sys_id'],
            ':ledger'     => $accountId,
            ':name'       => $accountName,
            ':date'       => $transactionDate,
            ':particular' => 'REFUND: ' . $particular,
            ':withdraw'   => $amount,
            ':deposit'    => $deposit,
            ':balance'    => $newBalance,
            ':method'     => $transferMethod,
            ':meta'       => $stmtMeta,
            ':hist'       => $isHistorical,
            ':ref'        => $feUUIDs['sys_id']  // financial_entries sys_id
        ]);
        $stmtSysId = $stmtUUIDs['sys_id'];

        // 8. Backdated recalculation
        if (!$isHistorical && $daysDiff > 0) {
            recalculateBalances($pdo, $accountId, $transactionDate);
        }
    }

    /* ===== Financial Entry: client + credit + related_type=0 (refund) ===== */
    // UUID already generated above (inside isPhysical block)
    // Non-physical হলে এখানে generate করি
    if (!$isPhysical) {
        $feUUIDs = generateIDs('financial_entries');
    }
    $feMeta  = buildMetaData(null, $_SESSION['user_name'] ?? 'system');

    $pdo->prepare("
        INSERT INTO financial_entries
        (uuid, sys_id, user_sys_id, user_name, user_type,
         date, purpose, type, related_type, amount, ref, meta_data)
        VALUES
        (:uuid, :sys_id, :user_sys_id, :user_name, 'client',
         :date, :purpose, 'credit', 0, :amount, :ref, :meta)
    ")->execute([
        ':uuid'        => $feUUIDs['uuid'],
        ':sys_id'      => $feUUIDs['sys_id'],
        ':user_sys_id' => $clientId,
        ':user_name'   => $clientName,
        ':date'        => $transactionDate,
        ':purpose'     => $particular,
        ':amount'      => $amount,
        ':ref'         => $stmtSysId,
        ':meta'        => $feMeta
    ]);

    $pdo->commit();

    echo json_encode([
        'success'     => true,
        'message'     => $isPhysical ? 'Physical refund recorded successfully' : 'Refund (ledger adjustment) recorded',
        'is_physical' => $isPhysical,
        'data'        => [
            'financial_entry_id' => $feUUIDs['sys_id'],
            'bank_stmt_id'       => $stmtSysId ?: null
        ]
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function recalculateBalances($pdo, $accountId, $fromDate) {
    $prevStmt = $pdo->prepare("
        SELECT balance FROM ac_banking_stmts
        WHERE ledger_db_id = :aid AND date < :from AND is_historical = 0
        ORDER BY date DESC, sys_id DESC LIMIT 1
    ");
    $prevStmt->execute([':aid' => $accountId, ':from' => $fromDate]);
    $prevBalance = (float)$prevStmt->fetchColumn();

    if (!$prevBalance) {
        $openingStmt = $pdo->prepare("
            SELECT balance FROM ac_banking_stmts
            WHERE ledger_db_id = :aid AND particular = 'Opening Balance' LIMIT 1
        ");
        $openingStmt->execute([':aid' => $accountId]);
        $prevBalance = (float)$openingStmt->fetchColumn();
    }

    $transStmt = $pdo->prepare("
        SELECT sys_id, withdraw, deposit FROM ac_banking_stmts
        WHERE ledger_db_id = :aid AND date >= :from AND is_historical = 0
        ORDER BY date ASC, sys_id ASC
    ");
    $transStmt->execute([':aid' => $accountId, ':from' => $fromDate]);

    $running = $prevBalance;
    $upStmt  = $pdo->prepare("UPDATE ac_banking_stmts SET balance = :bal WHERE sys_id = :sid");
    foreach ($transStmt->fetchAll(PDO::FETCH_ASSOC) as $t) {
        if ($t['withdraw'] > 0)    $running -= $t['withdraw'];
        elseif ($t['deposit'] > 0) $running += $t['deposit'];
        $upStmt->execute([':bal' => $running, ':sid' => $t['sys_id']]);
    }

    $pdo->prepare("UPDATE ac_banking SET balance = :bal WHERE sys_id = :aid")
        ->execute([':bal' => $running, ':aid' => $accountId]);
}
?>