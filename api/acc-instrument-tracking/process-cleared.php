<?php
session_start();
require '../../server/db_connection.php';          // $pdo
require '../../server/generate_meta_data.php';
require '../../server/uuid_with_system_id_generator.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['sys_id']) || !isset($data['amount']) || !isset($data['trnx_type'])) {
        throw new Exception('Missing required fields');
    }
    
    $pdo->beginTransaction();
    
    // First get instrument data WITH status check
    $instrumentStmt = $pdo->prepare("
        SELECT meta_data, related_from, related_to, account_name, bank_name, 
               instrument_date, remarks, status, amount 
        FROM ac_instrument_tracking 
        WHERE sys_id = ? AND status != 'cleared'
        FOR UPDATE
    ");
    $instrumentStmt->execute([$data['sys_id']]);
    $instrument = $instrumentStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$instrument) {
        throw new Exception('Instrument not found or already cleared');
    }
    
    $amount = $data['amount'];
    $trnxType = $data['trnx_type'];
    $relatedType = $data['related_type'] ?? 'a2a';
    $transactionDate = $instrument['instrument_date'] ?? date('Y-m-d');
    $particular = "Instrument Cleared: " . ($data['remarks'] ?? $instrument['remarks'] ?? 'Instrument clearance');
    $userName = $_SESSION['user_name'] ?? 'system';
    
    // Build meta data
    $transactionMetaData = buildMetaData(
        $instrument['meta_data'] ?? null,
        $userName
    );
    
    // Extract related_from and related_to
    $relatedFrom = explode('||', $instrument['related_from']);
    $relatedTo = explode('||', $instrument['related_to']);
    
    $fromAccountId = $relatedFrom[0] ?? null;
    $fromAccountName = $relatedFrom[1] ?? null;
    $toAccountId = $relatedTo[0] ?? null;
    $toAccountName = $relatedTo[1] ?? null;
    
    // Check if accounts exist and have sufficient balance BEFORE any updates
    if ($trnxType === 'debit' && $relatedType === 'a2a') {
        // For DEBIT A2A: Check from account balance
        $fromAccStmt = $pdo->prepare("
            SELECT balance, sys_id 
            FROM ac_banking 
            WHERE sys_id = ?
            FOR UPDATE
        ");
        $fromAccStmt->execute([$fromAccountId]);
        $fromAccount = $fromAccStmt->fetch(PDO::FETCH_ASSOC);

        if (!$fromAccount) {
            throw new Exception('From account not found: ' . $fromAccountId);
        }

        if ($fromAccount['balance'] < $amount) {
            throw new Exception('Insufficient balance in from account. Available: ৳' . 
                               number_format($fromAccount['balance'], 2) . 
                               ', Required: ৳' . number_format($amount, 2));
        }
        
        // Check to account exists
        $toAccStmt = $pdo->prepare("SELECT sys_id FROM ac_banking WHERE sys_id = ? FOR UPDATE");
        $toAccStmt->execute([$toAccountId]);
        $toAccount = $toAccStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$toAccount) {
            throw new Exception('To account not found: ' . $toAccountId);
        }
        
    } elseif ($trnxType === 'debit' && $relatedType === 'a2p') {
        // For DEBIT A2P: Check from account balance
        $fromAccStmt = $pdo->prepare("
            SELECT balance, sys_id 
            FROM ac_banking 
            WHERE sys_id = ?
            FOR UPDATE
        ");
        $fromAccStmt->execute([$fromAccountId]);
        $fromAccount = $fromAccStmt->fetch(PDO::FETCH_ASSOC);

        if (!$fromAccount) {
            throw new Exception('From account not found: ' . $fromAccountId);
        }

        if ($fromAccount['balance'] < $amount) {
            throw new Exception('Insufficient balance in from account. Available: ৳' . 
                               number_format($fromAccount['balance'], 2) . 
                               ', Required: ৳' . number_format($amount, 2));
        }
        
    } elseif ($trnxType === 'credit') {
        // For CREDIT: Check from account exists (receiving account)
        $accStmt = $pdo->prepare("SELECT sys_id FROM ac_banking WHERE sys_id = ? FOR UPDATE");
        $accStmt->execute([$fromAccountId]);
        $account = $accStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$account) {
            throw new Exception('Account not found: ' . $fromAccountId);
        }
    }
    
    // Now perform the transactions
    $fromUUIDs = null;
    $stmtUUIDs = null;
    
    if ($trnxType === 'debit') {
        if ($relatedType === 'a2a') {
            // DEBIT and A2A
            
            /* ================= FROM ACCOUNT ================= */
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
            
            $stmtInsert = $pdo->prepare("
                INSERT INTO ac_banking_stmts
                (
                    uuid, sys_id, ledger_db_id, name, date, transfer_type,
                    particular, withdraw, deposit, balance, transfer_method,
                    reconsilation, reconsilation_type, ref, meta_data
                )
                VALUES
                (
                    :uuid, :sys_id, :ledger_db_id, :name, :date, :transfer_type,
                    :particular, :withdraw, :deposit, :balance, :transfer_method,
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
                ':transfer_type'=> $relatedType,
                ':transfer_method' => 'instrument',
                ':ref'          => $toAccountId,
                ':meta_data'    => $transactionMetaData
            ]);

            /* ================= TO ACCOUNT ================= */
            $newToBalance = $toAccount['amount'] + $amount;
        
            $updateStmt->execute([
                ':balance' => $newToBalance,
                ':id'      => $toAccountId
            ]);
        
            /* -------- TO ACCOUNT STATEMENT -------- */
            $toUUIDs = generateIDs('ac_banking_stmts');
        
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
                ':transfer_type'=> $relatedType,
                ':transfer_method' => 'instrument',
                ':ref'          => $fromAccountId,
                ':meta_data'    => $transactionMetaData
            ]);
            
        } elseif ($relatedType === 'a2p') {
            // DEBIT and A2P
            
            /* ================= FROM ACCOUNT ================= */
            $newFromBalance = $fromAccount['balance'] - $amount;

            $updateStmt->execute([
                ':balance' => $newFromBalance,
                ':id'      => $fromAccountId
            ]);

            /* -------- FROM ACCOUNT STATEMENT -------- */
            $fromUUIDs = generateIDs('ac_banking_stmts');

            $stmtInsert = $pdo->prepare("
                INSERT INTO ac_banking_stmts
                (
                    uuid, sys_id, ledger_db_id, name, date, transfer_type,
                    particular, withdraw, deposit, balance, transfer_method,
                    reconsilation, reconsilation_type, ref, meta_data
                )
                VALUES
                (
                    :uuid, :sys_id, :ledger_db_id, :name, :date, :transfer_type,
                    :particular, :withdraw, :deposit, :balance, :transfer_method,
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
                ':transfer_type'=> $relatedType,
                ':transfer_method' => 'instrument',
                ':ref'          => $toAccountId,
                ':meta_data'    => $transactionMetaData
            ]);

            /* ================= TO EMPLOYEE ================= */
            if (!$toAccountId || !$toAccountName) {
                throw new Exception('Employee information not found');
            }
        
            /* -------- TO EMPLOYEE STATEMENT -------- */
            $empSysIds = generateIDs('financial_entries');
        
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
                ':user_sys_id' => $toAccountId,
                ':user_name' => $toAccountName,
                ':user_type' => 'employee',
                ':date' => $transactionDate,
                ':purpose' => $particular,
                ':type' => 'credit',
                ':amount' => $amount,
                ':ref' => 'Petty Cash' . '-' . $fromUUIDs['sys_id'],
                ':meta_data' => $transactionMetaData
            ]);
        }
        
    } elseif ($trnxType === 'credit') {
        // CREDIT transactions - Received
        
        /* ================= BANK LEDGER ENTRY ================= */
        // Get current balance for credit transaction
        $accStmt = $pdo->prepare("SELECT balance FROM ac_banking WHERE sys_id = ?");
        $accStmt->execute([$fromAccountId]);
        $account = $accStmt->fetch(PDO::FETCH_ASSOC);
        
        $currentBalance = $account['balance'];
        $newBalance = $currentBalance + $amount;

        $updateStmt = $pdo->prepare("
            UPDATE ac_banking 
            SET balance = :balance 
            WHERE sys_id = :id
        ");
        $updateStmt->execute([
            ':balance' => $newBalance,
            ':id'      => $fromAccountId
        ]);

        // Insert into bank statements
        $stmtUUIDs = generateIDs('ac_banking_stmts');

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
            ':ledger_db_id' => $fromAccountId,
            ':name' => $fromAccountName,
            ':date' => $transactionDate,
            ':particular' => $particular,
            ':withdraw' => 0,
            ':deposit' => $amount,
            ':balance' => $newBalance,
            ':transfer_method' => 'instrument',
            ':meta_data' => $transactionMetaData
        ]);

        $stmtSysId = $stmtUUIDs['sys_id'];

        /* ================= FINANCIAL ENTRIES ================= */
        $financialUUIDs = generateIDs('financial_entries');

        // Determine if it's client or vendor
        $userType = strpos(strtolower($toAccountName), 'client') !== false ? 'client' : 'vendor';

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
            ':user_sys_id' => $toAccountId,
            ':user_name' => $toAccountName,
            ':user_type' => $userType,
            ':date' => $transactionDate,
            ':purpose' => $particular,
            ':type' => 'credit',
            ':amount' => $amount,
            ':ref' => $stmtSysId,
            ':meta_data' => $transactionMetaData
        ]);
    }
    
    // Update instrument status to cleared AFTER successful transactions
    $updateInstrumentStmt = $pdo->prepare("
        UPDATE ac_instrument_tracking 
        SET 
            status = 'cleared',
            cleared_at = NOW(), 
            cleared_by = :cleared_by,
            cleared_transaction_id = :transaction_id,
            meta_data = :meta_data,
            updated_at = NOW()
        WHERE sys_id = :sys_id
    ");
    
    $transactionId = $fromUUIDs['sys_id'] ?? $stmtUUIDs['sys_id'] ?? null;
    
    $updateResult = $updateInstrumentStmt->execute([
        ':cleared_by' => $userName,
        ':transaction_id' => $transactionId,
        ':meta_data' => $transactionMetaData,
        ':sys_id' => $data['sys_id']
    ]);
    
    if ($updateResult) {
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Financial transactions processed successfully',
            'transaction_id' => $transactionId
        ]);
    } else {
        $pdo->rollBack();
        throw new Exception('Failed to update instrument status');
    }
    
} catch (Exception $e) {
    // Rollback on any error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>