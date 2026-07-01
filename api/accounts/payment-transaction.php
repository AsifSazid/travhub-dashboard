<?php
session_start();

require '../../server/db_connection.php';
require '../../server/uuid_with_system_id_generator.php';
require '../../server/generate_meta_data.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

$ip_port = @file_get_contents('../../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898";
}

$base_ip_path = trim($ip_port, "/");

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
$paymentTo = $data['payTo'] ?? '';
$accountId = $data['accountId'] ?? '';
$accountName = $data['accountName'] ?? '';
$clientId = $data['clientId'] ?? null;
$clientName = $data['clientName'] ?? null;
$vendorId = $data['vendorId'] ?? null;
$vendorName = $data['vendorName'] ?? null;
$amount = $data['amount'] ?? 0;
$particular = $data['particular'] ?? '';
$relatedTypeForStmt = $data['related_type_for_stmt'] ?? '';
$relatedTypeForFinEn = $data['related_type_for_finen'] ?? '';
$transactionDate = $data['transactionDate'] ?? date('Y-m-d H:i:s');
$transferMethod = $data['transferMethod'] ?? 'cash';
$isHistorical = isset($data['isHistorical']) ? (int)$data['isHistorical'] : 0;

$deposit = 0;

// Payment type is always Withdraw for payment
$paymentType = 'Withdraw';

// Cheque/BFTN details
$chequeNo = $data['chequeNo'] ?? '';
$chequeDate = $data['chequeDate'] ?? '';
$chequeAccountName = $data['chequeAccountName'] ?? '';
$bankName = $data['bankName'] ?? '';
$accountNameEFT = $data['bftnAccountName'] ?? '';
$eftBankName = $data['eftBankName'] ?? '';
$bftnDate = $data['bftnDate'] ?? '';

/* ================= BASIC VALIDATION ================= */
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
$tz = new DateTimeZone('Asia/Dhaka');
$txnDateObj = new DateTime($transactionDate, $tz);

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
    $instrumentApiUrl = $base_ip_path . '/api/acc-instrument-tracking/store.php';
    
    /* ================= PREPARE INSTRUMENT DATA ================= */
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
        "payment_to"      => $paymentTo,
        "account_name"    => $instrumentAccountName,
        "bank_name"       => $instrumentBankName,
        "instrument_date" => $instrumentDate,
        "status"          => 'pending',
        "date"            => date('Y-m-d'),
        "remarks"         => $particular,
        "related_type"    => 'payment',
        "related_from"    => $accountId . ' || ' . $accountName,
        "related_to"      => $clientId ? ($clientId . ' || ' . $clientName) : ($vendorId . ' || ' . $vendorName),
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
        'message' => 'Instrument recorded successfully. Payment pending clearance.',
        'instrument' => $instrumentData
    ]);
    exit;
}

try {
    /* ================= START TRANSACTION ================= */
    $pdo->beginTransaction();

    /* ================= 1. FETCH ACCOUNT BALANCE ================= */
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

    /* ================= 2. CHECK OPENING BALANCE DATE ================= */
    try {
        $openingQuery = "
            SELECT MIN(date) as opening_date 
            FROM ac_banking_stmts 
            WHERE ledger_db_id = :account_id 
            AND particular = 'Opening Balance'
        ";
        $openingStmt = $pdo->prepare($openingQuery);
        $openingStmt->execute([':account_id' => $accountId]);
        $openingDate = $openingStmt->fetch(PDO::FETCH_ASSOC)['opening_date'];
        
        // If transaction date is before opening date, mark as historical
        if ($openingDate && $transactionDate < $openingDate) {
            $isHistorical = 1;
        }
    } catch (PDOException $e) {
        // Continue with provided isHistorical value
    }

    /* ================= 3. CHECK 5-DAY BACKDATED LIMIT ================= */
    $maxBackdatedDays = 10;
    $dateCheckQuery = "SELECT DATEDIFF(NOW(), :transaction_date) as days_diff";
    $dateStmt = $pdo->prepare($dateCheckQuery);
    $dateStmt->execute([':transaction_date' => $transactionDate]);
    $daysDiff = $dateStmt->fetch(PDO::FETCH_ASSOC)['days_diff'];

    // Only check 5-day limit if not historical (opening balance er age)
    if (!$isHistorical && $daysDiff > $maxBackdatedDays) {
        throw new Exception('You can make backdated entries up to 10 days.');
    }

    /* ================= 4. CALCULATE NEW BALANCE ================= */
    // For historical entries, balance doesn't change
    if ($isHistorical) {
        $newBalance = $currentBalance;
    } else {
        // Check sufficient balance for non-historical
        // THAT SHOULD BE REMOVED COMMENT TO UNCOMMENT
        // if ($currentBalance < $amount) {
        //     throw new Exception('Insufficient balance in account');
        // }
        $newBalance = $currentBalance - $amount;
    }

    /* ================= 5. UPDATE ACCOUNT BALANCE (ONLY IF NOT HISTORICAL) ================= */
    if (!$isHistorical) {
        $updateStmt = $pdo->prepare("
            UPDATE ac_banking 
            SET balance = :balance 
            WHERE sys_id = :id
        ");
        $updateStmt->execute([
            ':balance' => $newBalance,
            ':id'      => $accountId
        ]);
    }

    /* ================= 6. INSERT INTO BANK STATEMENTS ================= */
    $stmtUUIDs = generateIDs('ac_banking_stmts');
    $stmtMeta = buildMetaData(null, $_SESSION['user_name'] ?? 'system');

    $stmtInsert = $pdo->prepare("
        INSERT INTO ac_banking_stmts
        (
            uuid, sys_id, ledger_db_id, name, date, particular, related_type,
            withdraw, deposit, balance, transfer_method, meta_data, is_historical
        )
        VALUES
        (
            :uuid, :sys_id, :ledger_db_id, :name, :date, :particular, :related_type,
            :withdraw, :deposit, :balance, :transfer_method, :meta_data, :is_historical
        )
    ");

    $stmtInsert->execute([
        ':uuid' => $stmtUUIDs['uuid'],
        ':sys_id' => $stmtUUIDs['sys_id'],
        ':ledger_db_id' => $accountId,
        ':name' => $accountName,
        ':date' => $transactionDate,
        ':particular' => $particular,
        ':related_type' => $relatedTypeForStmt,
        ':withdraw' => $amount,
        ':deposit' => $deposit,
        ':balance' => $newBalance,
        ':transfer_method' => $transferMethod,
        ':meta_data' => $stmtMeta,
        ':is_historical' => $isHistorical
    ]);

    $stmtSysId = $stmtUUIDs['sys_id'];

    /* ================= 7. INSERT INTO FINANCIAL ENTRIES ================= */
    $financialUUIDs = generateIDs('financial_entries');
    $financialMeta = buildMetaData(null, $_SESSION['user_name'] ?? 'system');

    $userSysId = $clientId ?? $vendorId;
    $userName = $clientName ?? $vendorName;
    $userType = $clientId ? 'client' : 'vendor';

    // financial_entries এ is_historical ব্যবহার করা হচ্ছে না
    $financialStmt = $pdo->prepare("
        INSERT INTO financial_entries (
            uuid, sys_id,
            user_sys_id, user_name, user_type, related_type,
            date, purpose, type, amount, ref,
            meta_data
        ) VALUES (
            :uuid, :sys_id,
            :user_sys_id, :user_name, :user_type, :related_type,
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
        ':related_type' => $relatedTypeForFinEn,
        ':type' => 'debit',
        ':amount' => $amount,
        ':ref' => $stmtSysId,
        ':meta_data' => $financialMeta
    ]);

    /* ================= 8. CHECK FOR BACKDATED RECALCULATION ================= */
    $recalculated = false;
    $recalculatedDate = null;
    
    if (!$isHistorical && ($daysDiff > 0 || $transactionDate < date('Y-m-d'))) {
        // Call recalculate function
        $recalcResult = recalculateBalances($pdo, $accountId, $transactionDate);
        $recalculated = true;
        $recalculatedDate = $transactionDate;
        $newBalance = $recalcResult['final_balance'];
    }

    /* ================= COMMIT ================= */
    $pdo->commit();

    // Fetch the inserted record for receipt
    $itemData = [
        'uuid' => $stmtUUIDs['uuid'],
        'sys_id' => $stmtUUIDs['sys_id'],
        'ledger_db_id' => $accountId,
        'name' => $accountName,
        'date' => $transactionDate,
        'particular' => $particular,
        'withdraw' => $amount,
        'deposit' => $deposit,
        'balance' => $newBalance,
        'transfer_method' => $transferMethod,
        'is_historical' => $isHistorical
    ];

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => $isHistorical ? 'ঐতিহাসিক এন্ট্রি সংরক্ষিত হয়েছে' : 'Payment transaction recorded successfully',
        'data' => [
            'bank_stmt_id' => $stmtSysId,
            'financial_entry_id' => $financialUUIDs['sys_id'],
            'new_balance' => $newBalance
        ], 
        'item' => $itemData,
        'is_historical' => $isHistorical,
        'recalculated' => $recalculated,
        'recalculated_date' => $recalculatedDate
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

/**
 * নির্দিষ্ট তারিখ থেকে পরবর্তী সব ট্রানজেকশন রি-ক্যালকুলেট করার ফাংশন
 */
function recalculateBalances($pdo, $accountId, $fromDate) {
    // ঐ তারিখের আগের শেষ ব্যালেন্স বের করা (শুধু non-historical)
    $prevBalanceQuery = "
        SELECT balance FROM ac_banking_stmts 
        WHERE ledger_db_id = :account_id 
        AND date < :from_date 
        AND is_historical = 0
        ORDER BY date DESC, sys_id DESC 
        LIMIT 1
    ";
    $prevStmt = $pdo->prepare($prevBalanceQuery);
    $prevStmt->execute([
        ':account_id' => $accountId,
        ':from_date' => $fromDate
    ]);
    $prevBalance = (float)$prevStmt->fetchColumn();
    
    if (!$prevBalance) {
        // আগের কোনো ব্যালেন্স না থাকলে, Opening Balance থেকে শুরু
        $openingQuery = "
            SELECT balance FROM ac_banking_stmts 
            WHERE ledger_db_id = :account_id 
            AND particular = 'Opening Balance'
            LIMIT 1
        ";
        $openingStmt = $pdo->prepare($openingQuery);
        $openingStmt->execute([':account_id' => $accountId]);
        $prevBalance = (float)$openingStmt->fetchColumn();
    }
    
    // ঐ তারিখ থেকে পরবর্তী সব ট্রানজেকশন নেওয়া (ক্রমানুসারে) - শুধু non-historical
    $transactionsQuery = "
        SELECT sys_id, date, withdraw, deposit
        FROM ac_banking_stmts 
        WHERE ledger_db_id = :account_id 
        AND date >= :from_date 
        AND is_historical = 0
        ORDER BY date ASC, sys_id ASC
    ";
    $transStmt = $pdo->prepare($transactionsQuery);
    $transStmt->execute([
        ':account_id' => $accountId,
        ':from_date' => $fromDate
    ]);
    $transactions = $transStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $runningBalance = $prevBalance;
    
    // প্রতিটি ট্রানজেকশনের ব্যালেন্স পুনরায় ক্যালকুলেট
    foreach ($transactions as $trans) {
        if ($trans['withdraw'] > 0) {
            $runningBalance -= $trans['withdraw'];
        } elseif ($trans['deposit'] > 0) {
            $runningBalance += $trans['deposit'];
        }
        
        // আপডেট ব্যালেন্স
        $updateSql = "UPDATE ac_banking_stmts SET balance = :balance WHERE sys_id = :sys_id";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([
            ':balance' => $runningBalance,
            ':sys_id' => $trans['sys_id']
        ]);
    }
    
    // মূল অ্যাকাউন্টের ব্যালেন্স আপডেট
    $updateAccountSql = "UPDATE ac_banking SET balance = :balance WHERE sys_id = :account_id";
    $updateAccStmt = $pdo->prepare($updateAccountSql);
    $updateAccStmt->execute([
        ':balance' => $runningBalance,
        ':account_id' => $accountId
    ]);
    
    return [
        'final_balance' => $runningBalance
    ];
}
?>