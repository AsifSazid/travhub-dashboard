<?php
session_start();
require '../../server/db_connection.php';          // $pdo
require '../../server/generate_meta_data.php';
header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['sys_id']) || !isset($data['amount']) || !isset($data['trnx_type'])) {
        throw new Exception('Missing required fields');
    }
    
    $pdo->beginTransaction();
    
    // Get instrument data using sys_id
    $instrumentStmt = $pdo->prepare("SELECT meta_data, related_from, related_to, account_name, bank_name, instrument_date, remarks FROM ac_instrument_tracking WHERE sys_id = ?");
    $instrumentStmt->execute([$data['sys_id']]);
    $instrument = $instrumentStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$instrument) {
        throw new Exception('Instrument not found');
    }
    
    $amount = $data['amount'];
    $trnxType = $data['trnx_type'];
    $relatedType = $data['related_type'];
    $transactionDate = $instrument['instrument_date'] ?? date('Y-m-d');
    $particular = "Instrument Cleared: " . ($instrument['remarks'] ?? 'Instrument clearance');
    $userName = $_SESSION['user_name'] ?? 'system';
    
    // Build meta data for transaction
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
    
    $fromUUIDs = null;
    $stmtUUIDs = null;
    
    if ($trnxType === 'debit') {
        if ($relatedType === 'a2a') {
            // DEBIT and A2A
            
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
        $accStmt = $pdo->prepare("
            SELECT balance 
            FROM ac_banking 
            WHERE sys_id = ?
            FOR UPDATE
        ");
        $accStmt->execute([$fromAccountId]);
        $account = $accStmt->fetch(PDO::FETCH_ASSOC);

        if (!$account) {
            throw new Exception('Account not found');
        }

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
    
    // Update instrument for cleared status
    $updateInstrumentStmt = $pdo->prepare("
        UPDATE ac_instrument_tracking 
        SET 
            cleared_at = NOW(), 
            cleared_by = :cleared_by,
            cleared_transaction_id = :transaction_id
        WHERE sys_id = :sys_id
    ");
    
    $transactionId = $fromUUIDs['sys_id'] ?? $stmtUUIDs['sys_id'] ?? null;
    
    $updateInstrumentStmt->execute([
        ':cleared_by' => $userName,
        ':transaction_id' => $transactionId,
        ':sys_id' => $data['sys_id']
    ]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Financial transactions processed successfully',
        'transaction_id' => $transactionId
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>