<?php
session_start();

require '../../server/db_connection.php';
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
$accountId = $data['accountId'] ?? '';
$accountName = $data['accountName'] ?? '';
$clientId = $data['clientId'] ?? null;
$clientName = $data['clientName'] ?? null;
$vendorId = $data['vendorId'] ?? null;
$vendorName = $data['vendorName'] ?? null;
$amount = $data['amount'] ?? 0;
$particular = $data['particular'] ?? '';
$transactionDate = $data['transactionDate'] ?? date('Y-m-d H:i:s');
$transferMethod = $data['transferMethod'] ?? 'cash';

// Payment type is always Deposit for received
$paymentType = 'Deposit';

/* ================= VALIDATION ================= */
if (!is_numeric($amount) || $amount <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid amount'
    ]);
    exit;
}

// Check if we have either client or vendor
if (!$clientId && !$vendorId) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Either client or vendor must be selected'
    ]);
    exit;
}

// Check account (mandatory from 2026-02-01)
$cutoffDate = new DateTime('2026-02-01 00:00:00', new DateTimeZone('Asia/Dhaka'));
$txnDateObj = new DateTime($transactionDate, new DateTimeZone('Asia/Dhaka'));

if ($txnDateObj >= $cutoffDate) {
    if (!$accountId || !$accountName) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Account is required from 1 Feb 2026'
        ]);
        exit;
    }
}

/* ================= HANDLE INSTRUMENT (Cheque/BFTN-EFT) ================= */
$instrumentMethods = ['cheque', 'bftn-eft'];
$instrumentId = null;
$instrumentData = null;

if (in_array($transferMethod, $instrumentMethods, true)) {
    /* ================= CALL INSTRUMENT API ================= */
    $instrumentApiUrl = "https://travhub.com.bd/travhub-admin/api/acc-instrument-tracking/store.php";

    /* ================= PREPARE INSTRUMENT DATA ================= */
    $chequeNo = $data['chequeNo'] ?? '';
    $chequeAccountName = $data['chequeAccountName'] ?? '';
    $chequeDate = $data['chequeDate'] ?? '';
    $bankName = $data['bankName'] ?? '';
    $accountNameEFT = $data['bftnAccountName'] ?? '';
    $eftBankName = $data['eftBankName'] ?? '';
    $bftnDate = $data['bftnDate'] ?? '';
    
    // Determine values based on method
    $instrumentAccountName = '';
    $instrumentBankName = '';
    $instrumentDate = '';
    $instrumentNo = '';
    
    if ($transferMethod === 'cheque') {
        $instrumentAccountName = $chequeAccountName;
        $instrumentBankName = $bankName;
        $instrumentDate = $chequeDate ?: date('Y-m-d');
        $instrumentNo = $chequeNo;
    } elseif ($transferMethod === 'bftn-eft') {
        $instrumentAccountName = $accountNameEFT;
        $instrumentBankName = $eftBankName;
        $instrumentDate = $bftnDate ?: date('Y-m-d');
        $instrumentNo = $data['bftnNo'] ?? '';
    }
    
    // Prepare instrument payload
    $instrumentPayload = [
        "instrument_type" => strtoupper($transferMethod),
        "instrument_no"   => $instrumentNo,
        "account_name"    => $instrumentAccountName,
        "bank_name"       => $instrumentBankName,
        "instrument_date" => $instrumentDate,
        "status"          => 'pending',
        "date"            => date('Y-m-d'),
        "remarks"         => $particular,
        "related_type"    => 'received',
        "related_from"    => $clientId ? ($clientId . ' || ' . $clientName) : ($vendorId . ' || ' . $vendorName),
        "related_to"      => $accountId . ' || ' . $accountName,
        "amount"          => $amount
    ];

    // Call instrument API
    $ch = curl_init($instrumentApiUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode($instrumentPayload),
        CURLOPT_TIMEOUT        => 10
    ]);

    $response = curl_exec($ch);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($error) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Instrument API call failed',
            'error'   => $error
        ]);
        exit;
    }

    $result = json_decode($response, true);

    if (!$result || !$result['success']) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Instrument store failed',
            'api_response' => $response
        ]);
        exit;
    }

    $instrumentData = $result['data'];
    $instrumentId = $result['data']['sys_id'] ?? null;

    // If instrument method, we only record instrument, not transaction
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Instrument recorded successfully. Transaction pending.',
        'instrument' => $instrumentData
    ]);
    exit;
}

try {
    /* ================= START TRANSACTION ================= */
    $pdo->beginTransaction();

    /* ================= 1. BANK LEDGER ENTRY ================= */
    // Fetch current account balance
    $accStmt = $pdo->prepare("
        SELECT balance 
        FROM ac_banking 
        WHERE sys_id = ?
        FOR UPDATE
    ");
    $accStmt->execute([$accountId]);
    $account = $accStmt->fetch(PDO::FETCH_ASSOC);

    if (!$account) {
        throw new Exception('Account not found');
    }

    $currentBalance = $account['balance'];
    $newBalance = $currentBalance + $amount;

    // Update account balance
    $updateStmt = $pdo->prepare("
        UPDATE ac_banking 
        SET balance = :balance 
        WHERE sys_id = :id
    ");
    $updateStmt->execute([
        ':balance' => $newBalance,
        ':id'      => $accountId
    ]);

    // Insert into bank statements
    $stmtUUIDs = generateIDs('ac_banking_stmts');
    $stmtMeta = buildMetaData(null, $_SESSION['user_name'] ?? 'system');

    $stmtInsert = $pdo->prepare("
        INSERT INTO ac_banking_stmts
        (
            uuid, sys_id, ledger_db_id, name, date, particular,
            withdraw, deposit, balance, transfer_method, meta_data
        )
        VALUES
        (
            :uuid, :sys_id, :ledger_db_id, :name, :date, :particular,
            :withdraw, :deposit, :balance, :transfer_method, :meta_data
        )
    ");

    $stmtInsert->execute([
        ':uuid' => $stmtUUIDs['uuid'],
        ':sys_id' => $stmtUUIDs['sys_id'],
        ':ledger_db_id' => $accountId,
        ':name' => $accountName,
        ':date' => $transactionDate,
        ':particular' => $particular,
        ':withdraw' => 0,
        ':deposit' => $amount,
        ':balance' => $newBalance,
        ':transfer_method' => $transferMethod,
        ':meta_data' => $stmtMeta
    ]);

    $stmtSysId = $stmtUUIDs['sys_id'];

    /* ================= 2. FINANCIAL ENTRIES ================= */
    $financialUUIDs = generateIDs('financial_entries');
    $financialMeta = buildMetaData(null, $_SESSION['user_name'] ?? 'system');

    $userSysId = $clientId ?? $vendorId;
    $userName = $clientName ?? $vendorName;
    $userType = $clientId ? 'client' : 'vendor';

    $financialStmt = $pdo->prepare("
        INSERT INTO financial_entries (
            uuid, sys_id,
            user_sys_id, user_name, user_type,
            date, purpose, type, amount, ref,
            meta_data
        ) VALUES (
            :uuid, :sys_id,
            :user_sys_id, :user_name, :user_type,
            :date, :purpose, :type, :amount, :ref,
            :meta_data
        )
    ");

    $financialStmt->execute([
        ':uuid' => $financialUUIDs['uuid'],
        ':sys_id' => $financialUUIDs['sys_id'],
        ':user_sys_id' => $userSysId,
        ':user_name' => $userName,
        ':user_type' => $userType,
        ':date' => $transactionDate,
        ':purpose' => $particular,
        ':type' => 'credit',
        ':amount' => $amount,
        ':ref' => $stmtSysId,
        ':meta_data' => $financialMeta
    ]);

    /* ================= COMMIT ================= */
    $pdo->commit();

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Received transaction recorded successfully',
        'data' => [
            'bank_stmt_id' => $stmtSysId,
            'financial_entry_id' => $financialUUIDs['sys_id'],
            'new_balance' => $newBalance
        ]
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