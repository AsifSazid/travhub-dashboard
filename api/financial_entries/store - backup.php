<?php
session_start();

require '../../server/db_connection.php';
require '../../server/uuid_with_system_id_generator.php';
require '../../server/generate_meta_data.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

error_reporting(E_ALL);
ini_set('display_errors', 1);

// ------------------ Validator ------------------
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

// ------------------ Method Guard ------------------
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
        echo json_encode([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $errors
        ]);
        exit;
    }

    // ------------------ Extract ------------------
    $type    = $input['type'];
    $amount  = (float) $input['amount'];
    $purpose = trim($input['purpose']);
    $date    = $input['date'] ?? date('Y-m-d');
    $ref     = $input['ref'] ?? null;

    $clientId = $input['client_id'] ?? null;
    $vendorId = $input['vendor_id'] ?? null;
    $vendorType = $input['vendor_type'] ?? null;
    $accountId = $input['account_id'] ?? null;
    $workId   = $input['work_id'] ?? null;
    $taskId   = $input['task_id'] ?? null;

    $clientName = $vendorName = $taskTitle = $workTitle = null;

    // ------------------ Work ------------------
    if ($workId) {
        $stmt = $pdo->prepare("SELECT title FROM works WHERE sys_id = ?");
        $stmt->execute([$workId]);
        $workTitle = $stmt->fetchColumn();
    }

    // ------------------ Task ------------------
    if ($taskId) {
        $stmt = $pdo->prepare("SELECT title FROM tasks WHERE sys_id = ?");
        $stmt->execute([$taskId]);
        $taskTitle = $stmt->fetchColumn();
    }

    // ------------------ Client ------------------
    if ($clientId) {
        $stmt = $pdo->prepare("SELECT name FROM clients WHERE sys_id = ?");
        $stmt->execute([$clientId]);
        $clientName = $stmt->fetchColumn();

        if (!$clientName) {
            throw new Exception('Client not found');
        }
    }

    // ------------------ Vendor ------------------
    if ($vendorId) {
        $stmt = $pdo->prepare("SELECT name FROM vendors WHERE sys_id = ?");
        $stmt->execute([$vendorId]);
        $vendorName = $stmt->fetchColumn();

        if (!$vendorName) {
            throw new Exception('Vendor not found');
        }
    }

    // ------------------ Account ------------------
    if ($accountId) {
        $stmt = $pdo->prepare("SELECT acc_name, balance FROM ac_banking WHERE sys_id = ?");
        $stmt->execute([$accountId]);
        $accountInfo = $stmt->fetch();
        $accountName = $accountInfo['acc_name'];
        $oldBalance = $accountInfo['balance'];
        
        if (!$accountInfo) {
            throw new Exception('Vendor not found');
        }
    }

    // ------------------ Insert ------------------
    $ids = generateIDs('financial_entries');
    $metaDataJson = buildMetaData(null, $_SESSION['user_name'] ?? 'system');

    $stmt = $pdo->prepare("
        INSERT INTO financial_entries (
            uuid, sys_id,
            client_sys_id, client_name,
            vendor_sys_id, vendor_name, vendor_type,
            task_sys_id, task_title,
            work_sys_id, work_title,
            date, purpose, type, amount, ref,
            meta_data
        ) VALUES (
            :uuid, :sys_id,
            :client_sys_id, :client_name,
            :vendor_sys_id, :vendor_name, :vendor_type,
            :task_sys_id, :task_title,
            :work_sys_id, :work_title,
            :date, :purpose, :type, :amount, :ref,
            :meta_data
        )
    ");

    $stmt->execute([
        ':uuid' => $ids['uuid'],
        ':sys_id' => $ids['sys_id'],
        ':client_sys_id' => $clientId,
        ':client_name' => $clientName,
        ':vendor_sys_id' => $vendorId ?? $accountId ?? null,
        ':vendor_name' => $vendorName ?? $accountName ?? null,
        ':vendor_type' => $vendorType ?? null,
        ':task_sys_id' => $taskId,
        ':task_title' => $taskTitle,
        ':work_sys_id' => $workId,
        ':work_title' => $workTitle,
        ':date' => $date,
        ':purpose' => $purpose,
        ':type' => $type,
        ':amount' => $amount,
        ':ref' => $ref,
        ':meta_data' => $metaDataJson
    ]);
    
    if($vendorType == 1 && $accountId)
    {
        /* ---------------- Generate IDs & Meta ---------------- */
        $stmtIds = generateIDs('ac_banking_stmts');
    
        $user = $_SESSION['user_name'] ?? $data['user'] ?? 'system';
        $stmtMeta = buildMetaData(null, $user);

        if($type == 'credit'){
            $withdraw = $amount;
            $newBalance = $oldBalance - $withdraw;
            $updateSql = "
                UPDATE ac_banking
                SET balance = :balance
                WHERE sys_id = :account_row_id
            ";
    
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([
                ':balance'         => $newBalance,
                ':account_row_id'  => $accountId
            ]);
            
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
                    balance,
                    meta_data,
                    ref
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
                    :balance,
                    :meta_data,
                    :ref
                )
            ";
        
            $stmt = $pdo->prepare($insertSql);
            $stmt->execute([
                ':uuid'          => $stmtIds['uuid'],
                ':sys_id'        => $stmtIds['sys_id'],
                ':ledger_db_id'  => $accountId,
                ':name'          => $accountName,
                ':date'          => $date,
                ':particular'    => $purpose,
                ':withdraw'      => $withdraw,
                ':balance'       => $newBalance, // running balance
                ':meta_data'     => $stmtMeta,
                ':ref'     => $ids['sys_id'],
            ]);
            
            
        }else if($type == 'debit'){
            $deposit = $amount;
            $newBalance = $oldBalance + $deposit;
            $updateSql = "
                UPDATE ac_banking
                SET balance = :balance
                WHERE sys_id = :account_row_id
            ";
    
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([
                ':balance'         => $newBalance,
                ':account_row_id'  => $accountId
            ]);
            
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
                    deposit,
                    balance,
                    meta_data,
                    ref
                )
                VALUES
                (
                    :uuid,
                    :sys_id,
                    :ledger_db_id,
                    :name,
                    :date,
                    :particular,
                    :deposit,
                    :balance,
                    :meta_data,
                    :ref
                )
            ";
        
            $stmt = $pdo->prepare($insertSql);
            $stmt->execute([
                ':uuid'          => $stmtIds['uuid'],
                ':sys_id'        => $stmtIds['sys_id'],
                ':ledger_db_id'  => $accountId,
                ':name'          => $accountName,
                ':date'          => $date,
                ':particular'    => $purpose,
                ':deposit'      => $deposit,
                ':balance'       => $newBalance, // running balance
                ':meta_data'     => $stmtMeta,
                ':ref'     => $ids['sys_id'],
            ]);
            
            
        }else{
            echo json_encode([
                'success' => false,
                'message' => 'Server error',
                'error' => "Balance Problem!"
            ]);
        }
    }

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => ucfirst($type) . ' transaction recorded successfully',
        'transaction_id' => $pdo->lastInsertId(),
        'uuid' => $ids['uuid']
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'error' => $e->getMessage()
    ]);
}
