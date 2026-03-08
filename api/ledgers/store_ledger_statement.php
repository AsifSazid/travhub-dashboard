<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$cutoffDate = new DateTime('2026-02-01 00:00:00', new DateTimeZone('Asia/Dhaka'));

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
$account_row_id            = $data['accountId'] ?? null;
$account_name              = $data['accountName'] ?? null;
$particular                = $data['particular'] ?? '';
$input_amount              = $data['balance'] ?? 0;
$paymentType               = $data['paymentType'] ?? null;
$current_account_balance   = $data['currentAccountBalance'] ?? 0;
$isHistorical              = isset($data['is_historical']) ? (int)$data['is_historical'] : 0;

// *** IMPORTANT: reconciliation_type properly handle করুন ***
$reconciliation_type = null; // Default null
if (isset($data['reconciliation_type']) && $data['reconciliation_type'] !== '') {
    $reconciliation_type = (int)$data['reconciliation_type'];
}

// Time Zone
$transaction_date = $data['transactionDate'] ?? null;
$txnDateObj = new DateTime($transaction_date, new DateTimeZone('Asia/Dhaka'));

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

if (!$transaction_date || !$paymentType) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => 'Missing required fields'
    ]);
    exit;
}

// After 1 Feb 2026 → accountId & accountName required
if ($txnDateObj >= $cutoffDate) {
    if (!$account_row_id || !$account_name) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error'   => 'accountId and accountName are required from 1 Feb 2026'
        ]);
        exit;
    }
}

// Normalize numbers
$amount                  = abs((float)$input_amount);
$current_account_balance = (float)$current_account_balance;

try {
    /* ================= START TRANSACTION ================= */
    $pdo->beginTransaction();

    /* ---------------- নিয়ম ১: Opening Balance এর আগের তারিখ চেক ---------------- */
    if (!$isHistorical) {
        $openingQuery = "
            SELECT MIN(date) as opening_date 
            FROM ac_banking_stmts 
            WHERE ledger_db_id = :account_id 
            AND particular = 'Opening Balance'
        ";
        $openingStmt = $pdo->prepare($openingQuery);
        $openingStmt->execute([':account_id' => $account_row_id]);
        $openingDate = $openingStmt->fetch(PDO::FETCH_ASSOC)['opening_date'];
        
        if ($openingDate && $transaction_date < $openingDate) {
            $isHistorical = 1;
        }
    }
    
    /* ---------------- নিয়ম ২: ৫ দিনের বেশি ব্যাকডেটেড চেক ---------------- */
    $maxBackdatedDays = 5;
    $dateCheckQuery = "SELECT DATEDIFF(NOW(), :transaction_date) as days_diff";
    $dateStmt = $pdo->prepare($dateCheckQuery);
    $dateStmt->execute([':transaction_date' => $transaction_date]);
    $daysDiff = $dateStmt->fetch(PDO::FETCH_ASSOC)['days_diff'];
    
    if ($daysDiff > $maxBackdatedDays && !$isHistorical) {
        throw new Exception('আপনি সর্বোচ্চ ৫ দিন পর্যন্ত ব্যাকডেটেড entry করতে পারবেন।');
    }

    /* ---------------- Transaction Logic ---------------- */
    $withdraw        = 0.00;
    $deposit         = 0.00;
    $reconciliation  = 0.00;
    $new_balance     = $current_account_balance;

    switch ($paymentType) {

        case 'Withdraw':
            $withdraw    = $amount;
            if (!$isHistorical) {
                $new_balance = $current_account_balance - $amount;
            }
            break;

        case 'Deposit':
            $deposit     = $amount;
            if (!$isHistorical) {
                $new_balance = $current_account_balance + $amount;
            }
            break;

        case 'Reconciliation':
            $reconciliation = $amount;
            if ($reconciliation_type === 1) { // Add
                if (!$isHistorical) {
                    $new_balance = $current_account_balance + $amount;
                }
            } elseif ($reconciliation_type === 2) { // Deduct
                if (!$isHistorical) {
                    $new_balance = $current_account_balance - $amount;
                }
            }
            break;

        default:
            throw new Exception('Invalid payment type specified');
    }

    /* ---------------- Update Account Balance ---------------- */
    // Only for non-historical entries
    if (!$isHistorical && in_array($paymentType, ['Withdraw', 'Deposit'], true)) {

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

    $user = $_SESSION['user_name'] ?? 'system';
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
            meta_data,
            is_historical
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
            :meta_data,
            :is_historical
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
        ':balance'       => $new_balance,
        ':reconsilation' => $reconciliation,
        ':reconsilation_type' => $reconciliation_type, // Now this will be null for non-reconciliation
        ':meta_data'     => $stmtMeta,
        ':is_historical' => $isHistorical
    ]);

    $new_id = $pdo->lastInsertId();
    
    /* ---------------- নিয়ম ৩: ব্যাকডেটেড entry হলে রি-ক্যালকুলেশন ---------------- */
    $recalculated = false;
    $recalculatedDate = null;
    
    if (!$isHistorical && ($daysDiff > 0 || $transaction_date < date('Y-m-d'))) {
        // এই তারিখ থেকে পরবর্তী সব ট্রানজেকশন রি-ক্যালকুলেট
        $result = recalculateBalances($pdo, $account_row_id, $transaction_date);
        $recalculated = true;
        $recalculatedDate = $transaction_date;
        $finalBalance = $result['final_balance'];
    } elseif (!$isHistorical) {
        $finalBalance = $new_balance;
    } else {
        $finalBalance = $current_account_balance;
    }

    /* ================= COMMIT ================= */
    $pdo->commit();

    http_response_code(200);
    echo json_encode([
        'success'        => true,
        'message'        => $isHistorical ? 'ঐতিহাসিক entry সংরক্ষিত হয়েছে' : 'ট্রানজেকশন সফল হয়েছে',
        'new_id'         => $new_id,
        'new_balance'    => $finalBalance,
        'stmt_sys_id'    => $stmtIds['sys_id'],
        'is_historical'  => $isHistorical,
        'recalculated'   => $recalculated,
        'recalculated_date' => $recalculatedDate
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
        SELECT sys_id, date, withdraw, deposit, reconsilation, reconsilation_type
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
        } elseif ($trans['reconsilation'] > 0) {
            if ($trans['reconsilation_type'] == 1) {
                $runningBalance += $trans['reconsilation'];
            } elseif ($trans['reconsilation_type'] == 2) {
                $runningBalance -= $trans['reconsilation'];
            }
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