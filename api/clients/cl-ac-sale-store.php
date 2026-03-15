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
$clientId = $data['clientId'] ?? null;
$clientName = $data['clientName'] ?? null;
$vendorId = $data['vendorId'] ?? null;
$vendorName = $data['vendorName'] ?? null;
$amount = $data['amount'] ?? 0;
$particular = $data['particular'] ?? '';
$transactionDate = $data['transactionDate'] ?? date('Y-m-d H:i:s');

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

try {
    /* ================= START TRANSACTION ================= */
    $pdo->beginTransaction();

    /* ================= 1. INSERT INTO FINANCIAL ENTRIES ================= */
    $financialUUIDs = generateIDs('financial_entries');
    $financialMeta = buildMetaData(null, $_SESSION['user_name'] ?? 'system');

    $userSysId = $clientId ?? $vendorId;
    $userName = $clientName ?? $vendorName;
    $userType = $clientId ? 'client' : 'vendor';

    // financial_entries এ is_historical ব্যবহার করা হচ্ছে না
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
        ':type' => 'debit',
        ':amount' => $amount,
        ':ref' => $stmtSysId,
        ':meta_data' => $financialMeta
    ]);

    /* ================= COMMIT ================= */
    $pdo->commit();

    // Fetch the inserted record for receipt
    $itemData = [
        'uuid' => $stmtUUIDs['uuid'],
        'sys_id' => $stmtUUIDs['sys_id'],
        'date' => $transactionDate,
        'particular' => $particular,
    ];

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Payment transaction recorded successfully',
        'data' => [
            'bank_stmt_id' => $stmtSysId,
            'financial_entry_id' => $financialUUIDs['sys_id'],
            'new_balance' => $newBalance
        ], 
        'item' => $itemData
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

?>