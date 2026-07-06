<?php
session_start();

// Bangladesh time zone
date_default_timezone_set('Asia/Dhaka');

require '../../server/db_connection.php';
require '../../server/generate_meta_data.php';
require '../../server/uuid_with_system_id_generator.php';
require '../../server/finance_helpers.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $requiredFields = ['sys_id', 'amount', 'trnx_type', 'instrument_type'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field])) {
            throw new Exception("Missing required field: {$field}");
        }
    }
    
    $sysId = $data['sys_id'];
    $amount = (float) $data['amount'];
    $trnxType = $data['trnx_type']; // 'credit' or 'debit'
    $instrumentType = $data['instrument_type']; // 'cheque', 'pay_order', 'tt', 'dd', etc.
    $relatedType = $data['related_type'] ?? 'a2a'; // 'a2a', 'a2p', 'client', 'vendor'
    $remarks = $data['remarks'] ?? '';
    
    $pdo->beginTransaction();
    
    // Get instrument data with status check
    $instrumentStmt = $pdo->prepare("
        SELECT meta_data, related_from, related_to, account_name, bank_name, payment_to,
               instrument_date, remarks, status, amount, finance_data
        FROM ac_instrument_tracking 
        WHERE sys_id = ? AND status != 'cleared'
        FOR UPDATE
    ");
    $instrumentStmt->execute([$sysId]);
    $instrument = $instrumentStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$instrument) {
        throw new Exception('Instrument not found or already cleared');
    }
    
    // Use current date/time for transaction
    $transactionDate = date('Y-m-d H:i:s');
    $particular = "Instrument Cleared: " . ($remarks ?: $instrument['remarks'] ?? 'Instrument clearance');
    $userName = $_SESSION['user_name'] ?? 'system';
    $paymentTo = $instrument['payment_to'];
    
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
    
    // Initialize variables for transaction IDs
    $transactionId = null;
    $fromUUIDs = null;
    $stmtUUIDs = null;
    
    // var_dump($relatedType);
    // die;
    
    // ============ LOGIC-01: DEBIT TRANSACTIONS ============
    if ($trnxType === 'debit') {
        
        // === a2a (Account to Account) ===
        if ($relatedType === 'a2a') {
            // Check from account balance
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
            
            // if ($fromAccount['balance'] < $amount) {
            //     throw new Exception('Insufficient balance in from account. Available: ৳' . 
            //                       number_format($fromAccount['balance'], 2) . 
            //                       ', Required: ৳' . number_format($amount, 2));
            // }
            
            // Check to account exists
            $toAccStmt = $pdo->prepare("SELECT balance, sys_id FROM ac_banking WHERE sys_id = ? FOR UPDATE");
            $toAccStmt->execute([$toAccountId]);
            $toAccount = $toAccStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$toAccount) {
                throw new Exception('To account not found: ' . $toAccountId);
            }
            
            // ========== FROM ACCOUNT ==========
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
            
            // FROM ACCOUNT STATEMENT
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
                ':transfer_method' => $instrumentType,
                ':ref'          => $toAccountId,
                ':meta_data'    => $transactionMetaData
            ]);
            
            $transactionId = $fromUUIDs['sys_id'];
            
            // ========== TO ACCOUNT ==========
            $newToBalance = $toAccount['balance'] + $amount;
            
            $updateStmt->execute([
                ':balance' => $newToBalance,
                ':id'      => $toAccountId
            ]);
            
            // TO ACCOUNT STATEMENT
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
                ':transfer_method' => $instrumentType,
                ':ref'          => $fromAccountId,
                ':meta_data'    => $transactionMetaData
            ]);
            
        }
        // === a2p (Account to Person/Employee) ===
        elseif ($relatedType === 'a2p') {
            // Check from account balance
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
            
            // if ($fromAccount['balance'] < $amount) {
            //     throw new Exception('Insufficient balance in from account. Available: ৳' . 
            //                       number_format($fromAccount['balance'], 2) . 
            //                       ', Required: ৳' . number_format($amount, 2));
            // }
            
            // ========== FROM ACCOUNT ==========
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
            
            // FROM ACCOUNT STATEMENT
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
                ':transfer_method' => $instrumentType,
                ':ref'          => $toAccountId,
                ':meta_data'    => $transactionMetaData
            ]);
            
            $transactionId = $fromUUIDs['sys_id'];
            
            // ========== TO EMPLOYEE ==========
            if (!$toAccountId || !$toAccountName) {
                throw new Exception('Employee information not found');
            }
            
            // EMPLOYEE FINANCIAL ENTRY
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
                ':type' => 'credit', // Employee receives money
                ':amount' => $amount,
                ':ref' => 'Petty Cash - ' . $fromUUIDs['sys_id'],
                ':meta_data' => $transactionMetaData
            ]);
            
        }
        // === Payment to Client/Vendor (LOGIC-03) ===
        elseif (in_array($relatedType, ['client', 'vendor'])) {
            // First: Financial entry (LOGIC-03: payment to client/vendor)
            $financialUUIDs = generateIDs('financial_entries');
            
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
                ':user_type' => $relatedType, // 'client' or 'vendor'
                ':date' => $transactionDate,
                ':purpose' => $particular,
                ':type' => 'debit', // Payment made to client/vendor
                ':amount' => $amount,
                ':ref' => 'Instrument Payment',
                ':meta_data' => $transactionMetaData
            ]);
            
            $transactionId = $financialUUIDs['sys_id'];
            
            // Second: Bank account update (from account)
            $fromAccStmt = $pdo->prepare("
                SELECT balance, sys_id 
                FROM ac_banking 
                WHERE sys_id = ?
                FOR UPDATE
            ");
            $fromAccStmt->execute([$fromAccountId]);
            $fromAccount = $fromAccStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$fromAccount) {
                throw new Exception('Bank account not found: ' . $fromAccountId);
            }
            
            // if ($fromAccount['balance'] < $amount) {
            //     throw new Exception('Insufficient balance in bank account. Available: ৳' . 
            //                       number_format($fromAccount['balance'], 2) . 
            //                       ', Required: ৳' . number_format($amount, 2));
            // }
            
            // Update bank balance
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
            
            // Bank statement entry
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
                ':withdraw' => $amount,
                ':deposit' => 0,
                ':balance' => $newFromBalance,
                ':transfer_method' => $instrumentType,
                ':meta_data' => $transactionMetaData
            ]);
        }
        // === Payment
        elseif ($relatedType === 'payment') {
            // First: Financial entry (LOGIC-03: payment to client/vendor)
            $financialUUIDs = generateIDs('financial_entries');
            
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
                ':user_type' => $paymentTo, // 'client' or 'vendor'
                ':date' => $transactionDate,
                ':purpose' => $particular,
                ':type' => 'debit', // Payment made to client/vendor
                ':amount' => $amount,
                ':ref' => 'Instrument Payment',
                ':meta_data' => $transactionMetaData
            ]);
            
            $transactionId = $financialUUIDs['sys_id'];
            
            // Second: Bank account update (from account)
            $fromAccStmt = $pdo->prepare("
                SELECT balance, sys_id 
                FROM ac_banking 
                WHERE sys_id = ?
                FOR UPDATE
            ");
            $fromAccStmt->execute([$fromAccountId]);
            $fromAccount = $fromAccStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$fromAccount) {
                throw new Exception('Bank account not found: ' . $fromAccountId);
            }
            
            // if ($fromAccount['balance'] < $amount) {
            //     throw new Exception('Insufficient balance in bank account. Available: ৳' . 
            //                       number_format($fromAccount['balance'], 2) . 
            //                       ', Required: ৳' . number_format($amount, 2));
            // }
            
            // Update bank balance
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
            
            // Bank statement entry
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
                ':withdraw' => $amount,
                ':deposit' => 0,
                ':balance' => $newFromBalance,
                ':transfer_method' => $instrumentType,
                ':meta_data' => $transactionMetaData
            ]);
        }
        
    }
    // ============ LOGIC-02: CREDIT TRANSACTIONS (Receive from Client/Vendor) ============
    elseif ($trnxType === 'credit') {

        // 1. Bank account update (to account — money deposit)
        $toAccStmt = $pdo->prepare("SELECT balance, sys_id FROM ac_banking WHERE sys_id = ? FOR UPDATE");
        $toAccStmt->execute([$toAccountId]);
        $bankAccount = $toAccStmt->fetch(PDO::FETCH_ASSOC);
        if (!$bankAccount) throw new Exception('Bank account not found: ' . $toAccountId);

        $newBalance = $bankAccount['balance'] + $amount;
        $pdo->prepare("UPDATE ac_banking SET balance = ? WHERE sys_id = ?")->execute([$newBalance, $toAccountId]);

        // 2. Bank statement insert
        $stmtUUIDs = generateIDs('ac_banking_stmts');
        $pdo->prepare("
            INSERT INTO ac_banking_stmts
            (uuid, sys_id, ledger_db_id, name, date, particular,
             withdraw, deposit, balance, transfer_method, meta_data)
            VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?)
        ")->execute([
            $stmtUUIDs['uuid'], $stmtUUIDs['sys_id'],
            $toAccountId, $toAccountName, $transactionDate, $particular,
            $amount, $newBalance, $instrumentType, $transactionMetaData
        ]);
        $transactionId = $stmtUUIDs['sys_id'];

        // 3. Client receive — sale entries clear করি
        if ($paymentTo === 'client') {
            // finance_data column থেকে নিই
            $financeData = json_decode($instrument['finance_data'] ?? '{}', true) ?: [];
            $clientId        = trim($fromAccountId);
            $clientName      = trim($fromAccountName);
            $selectedSaleIds = $financeData['selected_sale_ids'] ?? [];
            $invoiceId       = $financeData['invoice_id']        ?? null;
            $overpayAction   = $financeData['overpayment_action'] ?? 'advance';

            // Invoice mode → sale ids নিই
            if ($invoiceId && empty($selectedSaleIds)) {
                $invR = $pdo->prepare("SELECT financial_entry_ids FROM invoices WHERE sys_id = ?");
                $invR->execute([$invoiceId]);
                $inv = $invR->fetch(PDO::FETCH_ASSOC);
                if ($inv && !empty($inv['financial_entry_ids'])) {
                    $selectedSaleIds = json_decode($inv['financial_entry_ids'], true) ?: [];
                }
            }

            if (!empty($selectedSaleIds)) {
                // Total remaining calculate
                $totalRemaining = 0;
                $ph  = implode(',', array_fill(0, count($selectedSaleIds), '?'));
                $sr  = $pdo->prepare("SELECT sys_id, amount, is_paid, is_partial, ref FROM financial_entries WHERE sys_id IN ($ph)");
                $sr->execute(array_values($selectedSaleIds));
                foreach ($sr->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $totalRemaining += getSaleRemaining($pdo, $row);
                }

                // Sale-wise receive entries via helper (FIFO)
                $clearAmt  = min($amount, $totalRemaining);
                $rcvResult = applyPaymentToSales(
                    $pdo, $selectedSaleIds, $clearAmt,
                    $clientId, $clientName, $transactionDate, $particular, $userName
                );

                // Overpayment check
                $overpayAmt = max(0, $amount - $totalRemaining);
                if ($overpayAmt > 0.009) {
                    handleOverpayment(
                        $pdo, $overpayAction, $overpayAmt,
                        'client', $clientId, $clientName,
                        $transactionDate, $sysId, $userName
                    );
                }

                // transactionId = first receive entry sys_id
                if (!empty($rcvResult['receive_entry_ids'])) {
                    $transactionId = $rcvResult['receive_entry_ids'][0];
                }

            } else {
                // No sale selection → Advance (rt=6)
                $advIds  = generateIDsSafe('financial_entries');
                $advMeta = buildMetaData(null, $userName);
                $pdo->prepare("
                    INSERT INTO financial_entries
                    (uuid, sys_id, user_sys_id, user_name, user_type,
                     date, purpose, type, related_type,
                     is_paid, is_partial, is_discounted, amount, ref, meta_data)
                    VALUES (?, ?, ?, ?, 'client', ?, ?, 'credit', 6, 1, 0, 0, ?, ?, ?)
                ")->execute([
                    $advIds['uuid'], $advIds['sys_id'],
                    $clientId, $clientName,
                    $transactionDate, 'Advance (Instrument Cleared): ' . $particular,
                    $amount, $sysId, $advMeta
                ]);
                $transactionId = $advIds['sys_id'];
            }

        } else {
            // Non-client (vendor বা other) — general financial entry
            $financialUUIDs = generateIDs('financial_entries');
            $pdo->prepare("
                INSERT INTO financial_entries (
                    uuid, sys_id, user_sys_id, user_name, user_type,
                    date, purpose, type, amount, ref, meta_data
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'credit', ?, ?, ?)
            ")->execute([
                $financialUUIDs['uuid'], $financialUUIDs['sys_id'],
                $fromAccountId, $fromAccountName, $paymentTo,
                $transactionDate, $particular,
                $amount, 'Instrument Cleared: ' . $sysId, $transactionMetaData
            ]);
            $transactionId = $financialUUIDs['sys_id'];
        }
    }
    else {
        throw new Exception('Invalid transaction type');
    }
    
    // Update instrument status to cleared
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
    
    $updateResult = $updateInstrumentStmt->execute([
        ':cleared_by' => $userName,
        ':transaction_id' => $transactionId,
        ':meta_data' => $transactionMetaData,
        ':sys_id' => $sysId
    ]);
    
    if ($updateResult) {
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Financial transactions processed successfully',
            'transaction_id' => $transactionId,
            'transaction_date' => $transactionDate
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