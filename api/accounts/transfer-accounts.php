<?php
session_start();

require '../../server/db_connection.php'; // $pdo
require '../../server/uuid_with_system_id_generator.php';
require '../../server/generate_meta_data.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

/* ================= METHOD CHECK ================= */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method Not Allowed'
    ]);
    exit;
}

/* ================= READ JSON ================= */
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON'
    ]);
    exit;
}

/* ================= INPUT ================= */
$fromAccountId   = $data['fromAccountId']   ?? '';
$fromAccountName = $data['fromAccountName'] ?? '';
$toAccountId     = $data['toAccountId']     ?? '';
$toAccountName   = $data['toAccountName']   ?? '';
$employeeId      = $data['employeeId']      ?? '';
$employeeName    = $data['employeeName']    ?? '';
$transferType    = $data['transferType']    ?? '';
$transferMethod  = $data['transferMethod']  ?? '';
$transferMethodStatus = $data['transferMethodStatus'] ?? '';
$amount          = $data['amount']          ?? 0;
$particular      = $data['particular']      ?? '';
$transactionDate = $data['transactionDate'] ?? date('Y-m-d');

/* ================= VALIDATION ================= */
if ($transferType !== 'a2a') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid transfer type'
    ]);
    exit;
}

if (!is_numeric($amount) || $amount <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid amount'
    ]);
    exit;
}

try {
    /* ================= START TRANSACTION ================= */
    $pdo->beginTransaction();

    /* ================= FROM ACCOUNT ================= */
    $fromAccStmt = $pdo->prepare("
        SELECT balance 
        FROM ac_banking 
        WHERE sys_id = ?
        FOR UPDATE
    ");
    $fromAccStmt->execute([$fromAccountId]);
    $fromAccount = $fromAccStmt->fetch(PDO::FETCH_ASSOC);

    if (!$fromAccount) {
        throw new Exception('From account not found');
    }

    if ($fromAccount['balance'] < $amount) {
        throw new Exception('Insufficient balance');
    }

    $newFromBalance = $fromAccount['balance'] - $amount;

    $updateStmt = $pdo->prepare("
        UPDATE ac_banking 
        SET balance = :balance 
        WHERE sys_id = :id
    ");
    $updateStmt->execute([
        ':balance' => $newFromBalance,
        ':id'      => $fromAccountId
    ]);

    /* -------- FROM ACCOUNT STATEMENT -------- */
    $fromUUIDs = generateIDs('ac_banking');
    $fromMeta  = buildMetaData(null, $_SESSION['user_name'] ?? 'system');

    $stmtInsert = $pdo->prepare("
        INSERT INTO ac_banking_stmts
        (
            uuid, sys_id, ledger_db_id, name, date,
            particular, withdraw, deposit, balance,
            reconsilation, reconsilation_type, meta_data
        )
        VALUES
        (
            :uuid, :sys_id, :ledger_db_id, :name, :date,
            :particular, :withdraw, :deposit, :balance,
            0, 0, :meta_data
        )
    ");

    $stmtInsert->execute([
        ':uuid'         => $fromUUIDs['uuid'],
        ':sys_id'       => $fromUUIDs['sys_id'],
        ':ledger_db_id' => $fromAccountId,
        ':name'         => $fromAccountName,
        ':date'         => $transactionDate,
        ':particular'   => $particular,
        ':withdraw'     => $amount,
        ':deposit'      => 0,
        ':balance'      => $newFromBalance,
        ':meta_data'    => $fromMeta
    ]);
    
    /* ================= COMMIT ================= */
    $pdo->commit();
    
    /* ================= START TRANSACTION ================= */
    $pdo->beginTransaction();

    /* ================= TO ACCOUNT ================= */
    $toAccStmt = $pdo->prepare("
        SELECT balance 
        FROM ac_banking 
        WHERE sys_id = ?
        FOR UPDATE
    ");
    $toAccStmt->execute([$toAccountId]);
    $toAccount = $toAccStmt->fetch(PDO::FETCH_ASSOC);

    if (!$toAccount) {
        throw new Exception('To account not found');
    }

    $newToBalance = $toAccount['balance'] + $amount;

    $updateStmt->execute([
        ':balance' => $newToBalance,
        ':id'      => $toAccountId
    ]);

    /* -------- TO ACCOUNT STATEMENT -------- */
    $toUUIDs = generateIDs('ac_banking');
    $toMeta  = buildMetaData(null, $_SESSION['user_name'] ?? 'system');

    $stmtInsert->execute([
        ':uuid'         => $toUUIDs['uuid'],
        ':sys_id'       => $toUUIDs['sys_id'],
        ':ledger_db_id' => $toAccountId,
        ':name'         => $toAccountName,
        ':date'         => $transactionDate,
        ':particular'   => $particular,
        ':withdraw'     => 0,
        ':deposit'      => $amount,
        ':balance'      => $newToBalance,
        ':meta_data'    => $toMeta
    ]);

    /* ================= COMMIT ================= */
    $pdo->commit();

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Account to account transfer successful'
    ]);

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
