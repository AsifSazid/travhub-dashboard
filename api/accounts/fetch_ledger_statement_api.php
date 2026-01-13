<?php
// fatch_laser_statement_api.php - Add laser transaction statement (PDO)

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 1. DB connection (PDO)
require '../../server/db_connection.php'; // provides $pdo
require '../../server/uuid_with_system_id_generator.php';
require '../../server/generate_meta_data.php';

// 2. Read JSON body
$data = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($data)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => 'Invalid request or no data provided'
    ]);
    exit;
}

// 3. Extract & validate input
$account_row_id            = $data['accountId'] ?? null; // FIXED naming
$account_name              = $data['accountName'] ?? null;
$particular                = $data['particular'] ?? '';
$input_amount              = $data['balance'] ?? 0;
$paymentType               = $data['paymentType'] ?? null;
$current_account_balance   = $data['currentAccountBalance'] ?? 0;
$reconciliation_type       = $data['reconciliation_type'] ?? null;

// Time Zone
$transaction_date = $data['transactionDate'] ?? null;

if ($transaction_date) {
    $tz = new DateTimeZone('Asia/Dhaka');

    // API date + current time
    $dateTime = new DateTime($transaction_date, $tz);
    $currentTime = (new DateTime('now', $tz))->format('H:i:s');

    $dateTime->setTime(
        ...explode(':', $currentTime)
    );

    $transaction_date = $dateTime->format('Y-m-d H:i:s');
}

if (
    !$account_row_id ||
    !$account_name ||
    !$transaction_date ||
    !$paymentType
) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => 'Missing required fields'
    ]);
    exit;
}

// Normalize numbers
$amount                  = abs((float)$input_amount);
$current_account_balance = (float)$current_account_balance;

try {
    /* ================= START TRANSACTION ================= */
    $pdo->beginTransaction();

    /* ---------------- Transaction Logic ---------------- */
    $withdraw        = 0.00;
    $deposit         = 0.00;
    $reconciliation  = 0.00;
    $new_balance     = $current_account_balance;

    switch ($paymentType) {

        case 'Withdraw':
            $withdraw    = $amount;
            $new_balance = $current_account_balance - $amount;
            break;

        case 'Deposit':
            $deposit     = $amount;
            $new_balance = $current_account_balance + $amount;
            break;

        case 'Reconciliation':
            // Static behavior (no balance change)
            $reconciliation = $amount;
            if($reconciliation_type == 0){
                $new_balance    = $current_account_balance + $reconciliation;
            }elseif ($reconciliation_type == 1) {
                $new_balance    = $current_account_balance - $reconciliation;
            }
            break;

        default:
            throw new Exception('Invalid payment type specified');
    }

    /* ---------------- Update Account Balance ---------------- */
    // Only for Withdraw & Deposit
    if (in_array($paymentType, ['Withdraw', 'Deposit'], true)) {

        $updateSql = "
            UPDATE ac_banking
            SET balance = :balance
            WHERE sys_id = :account_row_id
        ";

        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([
            ':balance'         => $new_balance,
            ':account_row_id'  => $account_row_id
        ]);
    }

    /* ---------------- Generate IDs & Meta ---------------- */
    $stmtIds = generateIDs('ac_banking_stmts');

    $user = $_SESSION['user_name'] ?? $data['user'] ?? 'system';
    $stmtMeta = buildMetaData(null, $user);

    /* ---------------- Insert Statement ---------------- */
    $insertSql = "
        INSERT INTO ac_banking_stmts
        (
            uuid,
            sys_id,
            ledger_db_id,
            name,
            date,
            particular,
            withdraw,
            deposit,
            balance,
            reconsilation,
            reconsilation_type,
            meta_data
        )
        VALUES
        (
            :uuid,
            :sys_id,
            :ledger_db_id,
            :name,
            :date,
            :particular,
            :withdraw,
            :deposit,
            :balance,
            :reconsilation,
            :reconsilation_type,
            :meta_data
        )
    ";

    $stmt = $pdo->prepare($insertSql);
    $stmt->execute([
        ':uuid'          => $stmtIds['uuid'],
        ':sys_id'        => $stmtIds['sys_id'],
        ':ledger_db_id'  => $account_row_id,
        ':name'          => $account_name,
        ':date'          => $transaction_date,
        ':particular'    => $particular,
        ':withdraw'      => $withdraw,
        ':deposit'       => $deposit,
        ':balance'       => $new_balance, // running balance
        ':reconsilation' => $reconciliation,
        ':reconsilation_type' => $reconciliation_type,
        ':meta_data'     => $stmtMeta
    ]);

    $new_id = $pdo->lastInsertId();

    /* ================= COMMIT ================= */
    $pdo->commit();

    http_response_code(200);
    echo json_encode([
        'success'        => true,
        'message'        => 'Transaction successfully recorded',
        'new_id'         => $new_id,
        'new_balance'    => $new_balance,
        'stmt_sys_id'    => $stmtIds['sys_id']
    ]);

} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Database error',
        'details' => $e->getMessage()
    ]);

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}
