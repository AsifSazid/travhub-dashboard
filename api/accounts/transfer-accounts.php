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
$transactionDate = $data['transactionDate'] ?? date('Y-m-d h:m:s');
$methodStatus = ''; 

/* ================= VALIDATION ================= */
$allowedTypes = ['a2a', 'a2p'];

if (!in_array($transferType, $allowedTypes, true)) {
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

if ($transferMethod === 'cheque')
{
    $methodStatus = json_encode([
        "trnx_ref"     => "",
        "trnx_date"   => "",
        "trnx_status" => "waiting2submit"
    ]);
    // waiting2submit = Waiting to Submit;
    // submitted2bank = Submitted to Bank;
    // cleared = Bank Cleared the Cheque;
    // bounced = Cheque Bounced;
}

if ($transferMethod === 'bftn-eft') {
    $methodStatus = json_encode([
        "trnx_id"     => "",
        "trnx_date"   => "",
        "trnx_status" => "pending"
    ]);
    // pending = trnx kora hoiche kintu ekhono kono action hoy nai 
    // successful = trnx hoiche and trnx id pawya geche 
    // unsuccessful = trnx successful hoy nai
}

$instrumentMethods = ['cheque', 'bftn-eft'];

if (in_array($transferMethod, $instrumentMethods, true)) {

    /* ================= CALL INSTRUMENT API ================= */
    $instrumentApiUrl = "https://travhub.com.bd/travhub-admin/api/acc-instrument-tracking/store.php";

    /* ================= INSTRUMENT DATA ================= */
    $chequeNo = $data['chequeNo'] ?? $data['instrument_no'] ?? '';
    $chequeAccountName = $data['chequeAccountName'] ?? '';
    $chequeDate = $data['chequeDate'] ?? '';
    $bankName = $data['bankName'] ?? '';
    $accountName = $data['accountName'] ?? '';
    $eftBankName = $data['eftBankName'] ?? '';
    $bftnDate = $data['bftnDate'] ?? '';
    
    // Determine account name based on method
    $instrumentAccountName = '';
    $instrumentBankName = '';
    $instrumentDate = '';
    
    if ($transferMethod === 'cheque') {
        $instrumentAccountName = $chequeAccountName;
        $instrumentBankName = $bankName;
        $instrumentDate = $chequeDate ?: date('Y-m-d');
        $instrumentNo = $chequeNo;
    } elseif ($transferMethod === 'bftn-eft') {
        $instrumentAccountName = $accountName;
        $instrumentBankName = $eftBankName;
        $instrumentDate = $bftnDate ?: date('Y-m-d');
        $instrumentNo = ''; // BFTN/EFT এর জন্য আলাদা নম্বর থাকতে পারে
    }
    
    // Then update instrument payload:
    $instrumentPayload = [
        "instrument_type" => strtoupper($transferMethod),
        "instrument_no"   => $instrumentNo,
        "account_name"    => $instrumentAccountName,
        "bank_name"       => $instrumentBankName,
        "instrument_date" => $instrumentDate,
        "status"          => 'pending',
        "date"            => date('Y-m-d'),
        "remarks"         => $particular,
        "related_to"      => $transferType === 'a2a' ? $toAccountName : $employeeName,
        "amount"          => $amount // Add amount to instrument
    ];

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

    /* ================= NO TRANSACTION ENTRY ================= */
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Instrument recorded successfully. Transaction pending.',
        'instrument' => $result['data']
    ]);
    exit; // 🔴 VERY IMPORTANT
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
    $fromUUIDs = generateIDs('ac_banking_stmts');
    $fromMeta  = buildMetaData(null, $_SESSION['user_name'] ?? 'system');
    $ref = $toAccountId ?? $employeeId;

    $stmtInsert = $pdo->prepare("
        INSERT INTO ac_banking_stmts
        (
            uuid, sys_id, ledger_db_id, name, date, transfer_type,
            particular, withdraw, deposit, balance, transfer_method,  method_status,
            reconsilation, reconsilation_type, ref, meta_data
        )
        VALUES
        (
            :uuid, :sys_id, :ledger_db_id, :name, :date, :transfer_type,
            :particular, :withdraw, :deposit, :balance, :transfer_method, :method_status,
            0, 0, :ref, :meta_data
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
        ':transfer_type'=> $transferType ?? null,
        ':transfer_method'      => $transferMethod ?? null,
        ':method_status'=> $methodStatus,
        ':ref'          => $ref,
        ':meta_data'    => $fromMeta
    ]);

    /* ================= TO ACCOUNT ================= */
    if($transferType === 'a2a'){
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
        $toUUIDs = generateIDs('ac_banking_stmts');
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
            ':transfer_type'=> $transferType ?? null,
            ':transfer_method'      => $transferMethod ?? null,
            ':method_status'=> $methodStatus,
            ':ref'          => $fromAccountId,
            ':meta_data'    => $toMeta
        ]);
    }
    
    
    /* ================= TO EMPLOYEE ================= */
    if ($transferType === 'a2p') {
        
        if (!$employeeId || !$employeeName) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Employee Not Found'
            ]);
            exit;
        }
    
        /* -------- TO EMPLOYEE STATEMENT -------- */
        $empSysIds = generateIDs('financial_entries');
        $employeeMetaDataJson = buildMetaData(null, $_SESSION['user_name'] ?? 'system');
    
        $stmt = $pdo->prepare("
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
    
        $stmt->execute([
            ':uuid' => $empSysIds['uuid'],
            ':sys_id' => $empSysIds['sys_id'],
            ':user_sys_id' => $employeeId ?? null,
            ':user_name' => $employeeName ?? null,
            ':user_type' => 'employee',
            ':date' => $transactionDate,
            ':purpose' => $particular,
            ':type' => 'credit',
            ':amount' => $amount,
            ':ref' => 'Petty Cash' . '-' . $fromUUIDs['sys_id'],
            ':meta_data' => $employeeMetaDataJson
        ]);
    }

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
