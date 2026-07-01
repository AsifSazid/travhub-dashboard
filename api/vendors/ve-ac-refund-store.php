<?php
// PATH: /api/vendors/ve-ac-refund-store.php  ← NEW FILE
//
// Receive Refund from Vendor
//   Case A — Non-physical (isPhysical=0):
//       শুধু financial_entries
//       vendor+debit, related_type=0
//
//   Case B — Physical (isPhysical=1):
//       vendor টাকা ফেরত দিচ্ছে bank এ → ac_banking_stmts DEPOSIT
//       (cl-ac-receive-store.php এর মতো, কিন্তু vendor context এ)
//       + financial_entries (vendor+debit, related_type=0)
//
// cl-ac-refund-store.php এর সাথে পার্থক্য:
//   cl-ac-refund-store.php → client+credit (refund to client, bank withdraw)
//   ve-ac-refund-store.php → vendor+debit  (refund from vendor, bank DEPOSIT)
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

$vendorId        = $data['vendorId']        ?? null;
$vendorName      = $data['vendorName']      ?? null;
$amount          = $data['amount']          ?? 0;
$particular      = $data['particular']      ?? '';
$transactionDate = $data['transactionDate'] ?? date('Y-m-d H:i:s');
$isPhysical      = isset($data['isPhysical']) ? (int)$data['isPhysical'] : 0;

$accountId      = $data['accountId']      ?? '';
$accountName    = $data['accountName']    ?? '';
$transferMethod = $data['transferMethod'] ?? 'cash';
$isHistorical   = 0;
$withdraw       = 0;

if (!is_numeric($amount) || $amount <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid amount']);
    exit;
}
if (!$vendorId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Vendor is required']);
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
        /* ===== Physical: Vendor থেকে টাকা আসছে, bank এ DEPOSIT ===== */

        $accStmt = $pdo->prepare("SELECT balance FROM ac_banking WHERE sys_id = ? FOR UPDATE");
        $accStmt->execute([$accountId]);
        $account = $accStmt->fetch(PDO::FETCH_ASSOC);
        if (!$account) throw new Exception('Account not found');
        $currentBalance = (float)$account['balance'];

        $openingStmt = $pdo->prepare("
            SELECT MIN(date) as opening_date FROM ac_banking_stmts
            WHERE ledger_db_id = :aid AND particular = 'Opening Balance'
        ");
        $openingStmt->execute([':aid' => $accountId]);
        $openingDate = $openingStmt->fetch(PDO::FETCH_ASSOC)['opening_date'] ?? null;
        if ($openingDate && $transactionDate < $openingDate) $isHistorical = 1;

        $dateStmt = $pdo->prepare("SELECT DATEDIFF(NOW(), :txn_date) as days_diff");
        $dateStmt->execute([':txn_date' => $transactionDate]);
        $daysDiff = (int)$dateStmt->fetch(PDO::FETCH_ASSOC)['days_diff'];
        if (!$isHistorical && $daysDiff > 10) throw new Exception('Backdated entry limit is 10 days.');

        // Refund from vendor = bank এ DEPOSIT (balance বাড়ে)
        $newBalance = $isHistorical ? $currentBalance : ($currentBalance + $amount);

        if (!$isHistorical) {
            $pdo->prepare("UPDATE ac_banking SET balance = :bal WHERE sys_id = :id")
                ->execute([':bal' => $newBalance, ':id' => $accountId]);
        }

        // financial_entries UUID আগেই generate করি — ac_banking_stmts.ref এ বসাবো
        $feUUIDs = generateIDs('financial_entries');

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
            ':particular' => 'VENDOR REFUND: ' . $particular,
            ':withdraw'   => $withdraw,
            ':deposit'    => $amount,
            ':balance'    => $newBalance,
            ':method'     => $transferMethod,
            ':meta'       => $stmtMeta,
            ':hist'       => $isHistorical,
            ':ref'        => $feUUIDs['sys_id']  // financial_entries sys_id
        ]);
        $stmtSysId = $stmtUUIDs['sys_id'];

        if (!$isHistorical && $daysDiff > 0) {
            recalculateBalances($pdo, $accountId, $transactionDate);
        }
    }

    /* ===== Financial Entry: vendor+debit+related_type=0 (refund) ===== */
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
        (:uuid, :sys_id, :user_sys_id, :user_name, 'vendor',
         :date, :purpose, 'debit', 0, :amount, :ref, :meta)
    ")->execute([
        ':uuid'        => $feUUIDs['uuid'],
        ':sys_id'      => $feUUIDs['sys_id'],
        ':user_sys_id' => $vendorId,
        ':user_name'   => $vendorName,
        ':date'        => $transactionDate,
        ':purpose'     => $particular,
        ':amount'      => $amount,
        ':ref'         => $stmtSysId,
        ':meta'        => $feMeta
    ]);

    $pdo->commit();
    echo json_encode([
        'success'     => true,
        'message'     => $isPhysical ? 'Physical refund received and recorded' : 'Refund (ledger) recorded',
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
        $s = $pdo->prepare("SELECT balance FROM ac_banking_stmts WHERE ledger_db_id = :aid AND particular = 'Opening Balance' LIMIT 1");
        $s->execute([':aid' => $accountId]);
        $prevBalance = (float)$s->fetchColumn();
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