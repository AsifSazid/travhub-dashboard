<?php
session_start();

require '../../server/db_connection.php';
require '../../server/generate_meta_data.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

error_reporting(E_ALL);
ini_set('display_errors', 1);

function validateUpdateInput(array $data): array
{
    $errors = [];
    if (!isset($data['id']) || empty($data['id'])) {
        $errors[] = 'Transaction ID is required';
    }
    if (!isset($data['amount']) || !is_numeric($data['amount']) || $data['amount'] <= 0) {
        $errors[] = 'Valid positive amount is required';
    }
    if (empty(trim($data['purpose'] ?? ''))) {
        $errors[] = 'Purpose is required';
    }
    if (empty(trim($data['date'] ?? ''))) {
        $errors[] = 'Date is required';
    }
    return $errors;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    $errors = validateUpdateInput($input);
    if ($errors) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]);
        exit;
    }

    $transactionId = $input['id'];
    $amount = (float) $input['amount'];
    $purpose = trim($input['purpose']);
    $date = $input['date'];
    
    $newVendorId = $input['vendor_id'] ?? null;
    $newAccountId = $input['account_id'] ?? null;
    $newVendorType = $input['vendor_type'] ?? null;

    // Get original transaction
    $stmt = $pdo->prepare("SELECT * FROM financial_entries WHERE id = ? OR sys_id = ?");
    $stmt->execute([$transactionId, $transactionId]);
    $originalTransaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$originalTransaction) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Transaction not found']);
        exit;
    }

    $originalAmount = (float) $originalTransaction['amount'];
    $originalVendorType = $originalTransaction['vendor_type'];
    $originalUserSysId = $originalTransaction['user_sys_id'];
    $originalType = $originalTransaction['type'];

    // ============= ac_banking HANDLING =============
    
    // CASE 1: Original transaction was from an account (vendor_type = 1)
    if ($originalVendorType == 1 && $originalTransaction['user_type'] === 'account') {
        $originalAccountId = $originalUserSysId;
        
        // Get original account balance
        $stmt = $pdo->prepare("SELECT balance FROM ac_banking WHERE sys_id = ?");
        $stmt->execute([$originalAccountId]);
        $originalAccount = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($originalAccount) {
            $originalBalance = (float) $originalAccount['balance'];
            
            // Reverse the original transaction effect
            if ($originalType === 'credit') {
                // Original was credit (withdraw), so ADD BACK the amount
                $balanceAfterReverse = $originalBalance + $originalAmount;
            } else {
                // Original was debit (deposit), so SUBTRACT the amount
                $balanceAfterReverse = $originalBalance - $originalAmount;
            }
            
            // Update original account balance (reverse effect)
            $updateStmt = $pdo->prepare("UPDATE ac_banking SET balance = ? WHERE sys_id = ?");
            $updateStmt->execute([$balanceAfterReverse, $originalAccountId]);
            
            // Delete or mark banking statement as deleted
            $stmt = $pdo->prepare("UPDATE ac_banking_stmts SET status = 0 WHERE ref = ? OR ref = ?");
            $stmt->execute([$originalTransaction['sys_id'], $originalTransaction['id']]);
        }
    }

    // CASE 2: New transaction is from an account
    $newAccountId = null;
    $newAccountName = null;
    
    if ($newVendorType == 1 && isset($input['account_id'])) {
        $newAccountId = $input['account_id'];
        
        // Get new account details
        $stmt = $pdo->prepare("SELECT acc_name, balance FROM ac_banking WHERE sys_id = ?");
        $stmt->execute([$newAccountId]);
        $newAccount = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($newAccount) {
            $newAccountName = $newAccount['acc_name'];
            $newAccountBalance = (float) $newAccount['balance'];
            
            // Apply new transaction effect
            if ($originalType === 'credit') {
                // Credit transaction (withdraw)
                $newBalance = $newAccountBalance - $amount;
            } else {
                // Debit transaction (deposit)
                $newBalance = $newAccountBalance + $amount;
            }
            
            // Update new account balance
            $updateStmt = $pdo->prepare("UPDATE ac_banking SET balance = ? WHERE sys_id = ?");
            $updateStmt->execute([$newBalance, $newAccountId]);
            
            // Insert new banking statement
            $ids = generateIDs('ac_banking_stmts');
            $user = $_SESSION['user_name'] ?? 'system';
            $stmtMeta = buildMetaData(null, $user);
            
            if ($originalType === 'credit') {
                $insertSql = "
                    INSERT INTO ac_banking_stmts
                    (uuid, sys_id, ledger_db_id, name, date, particular, withdraw, balance, meta_data, ref)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ";
                $stmt = $pdo->prepare($insertSql);
                $stmt->execute([
                    $ids['uuid'], $ids['sys_id'], $newAccountId, $newAccountName,
                    $date, $purpose, $amount, $newBalance, $stmtMeta, $originalTransaction['sys_id']
                ]);
            } else {
                $insertSql = "
                    INSERT INTO ac_banking_stmts
                    (uuid, sys_id, ledger_db_id, name, date, particular, deposit, balance, meta_data, ref)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ";
                $stmt = $pdo->prepare($insertSql);
                $stmt->execute([
                    $ids['uuid'], $ids['sys_id'], $newAccountId, $newAccountName,
                    $date, $purpose, $amount, $newBalance, $stmtMeta, $originalTransaction['sys_id']
                ]);
            }
        }
    }
    
    // CASE 3: Transaction is from a vendor (vendor_type = 0)
    $newVendorName = null;
    $newVendorId = null;
    
    if ($newVendorType == 0 && isset($input['vendor_id'])) {
        $newVendorId = $input['vendor_id'];
        
        $stmt = $pdo->prepare("SELECT name FROM vendors WHERE sys_id = ?");
        $stmt->execute([$newVendorId]);
        $newVendorName = $stmt->fetchColumn();
    }

    // Determine final user_sys_id and user_name
    if ($newVendorType == 1 && $newAccountId) {
        $userSysId = $newAccountId;
        $userName = $newAccountName;
        $userType = 'account';
    } elseif ($newVendorType == 0 && $newVendorId) {
        $userSysId = $newVendorId;
        $userName = $newVendorName;
        $userType = 'vendor';
    } else {
        // No change in vendor/account
        $userSysId = $originalUserSysId;
        $userName = $originalTransaction['user_name'];
        $userType = $originalTransaction['user_type'];
    }

    // Update meta data
    $metaData = json_decode($originalTransaction['meta_data'], true) ?? [];
    $updatedMeta = buildMetaData($metaData, $_SESSION['user_name'] ?? 'system');
    $updatedMeta['updated_at'] = date('Y-m-d H:i:s');
    $updatedMeta['updated_by'] = $_SESSION['user_name'] ?? 'system';

    // Update financial entry
    $updateSql = "
        UPDATE financial_entries 
        SET 
            purpose = :purpose,
            amount = :amount,
            date = :date,
            user_sys_id = :user_sys_id,
            user_name = :user_name,
            user_type = :user_type,
            vendor_type = :vendor_type,
            meta_data = :meta_data
        WHERE id = :id OR sys_id = :sys_id
    ";

    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([
        ':purpose' => $purpose,
        ':amount' => $amount,
        ':date' => $date,
        ':user_sys_id' => $userSysId,
        ':user_name' => $userName,
        ':user_type' => $userType,
        ':vendor_type' => $newVendorType ?? $originalVendorType,
        ':meta_data' => json_encode($updatedMeta),
        ':id' => is_numeric($transactionId) ? $transactionId : 0,
        ':sys_id' => $transactionId
    ]);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Transaction updated successfully',
        'transaction_id' => $transactionId,
        'account_updated' => $newAccountId ? true : false
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'error' => $e->getMessage()
    ]);
}
?>