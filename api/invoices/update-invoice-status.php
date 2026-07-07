<?php
// api/invoices/update-invoice-status.php — v4 FIXED
// Fix: applyPaymentToSales() এ cash আর advance আলাদাভাবে apply করা হচ্ছে

session_start();
header('Content-Type: application/json');
require_once '../../server/db_connection.php';
require_once '../../server/uuid_with_system_id_generator.php';
require_once '../../server/generate_meta_data.php';
require_once '../../server/finance_helpers.php';

$input      = json_decode(file_get_contents("php://input"), true);
$invoice_id = $input['invoice_id']       ?? '';
$action     = $input['action']           ?? 'check_advance';
$user_name  = $_SESSION['user_name']     ?? 'system';

if (empty($invoice_id)) {
    echo json_encode(['success' => false, 'message' => 'Invoice ID is required']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM invoices WHERE sys_id = ?");
    $stmt->execute([$invoice_id]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$invoice) {
        echo json_encode(['success' => false, 'message' => 'Invoice not found']);
        exit;
    }

    $totalAmount   = (float) $invoice['total_amount'];
    $alreadyPaid   = (float) $invoice['paid_amount'];
    $dueAmount     = (float) $invoice['due_amount'];
    $clientSysId   = $invoice['client_sys_id'];
    $clientName    = $invoice['client_name'];
    $feIds         = json_decode($invoice['financial_entry_ids'] ?? '[]', true) ?: [];

    /* ============================================================
       ACTION: check_advance
       ============================================================ */
    if ($action === 'check_advance') {
        $cr = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM financial_entries WHERE user_sys_id = ? AND user_type = 'client' AND type = 'credit' AND related_type = 6");
        $cr->execute([$clientSysId]);
        $totalIn = (float) $cr->fetchColumn();

        $dr = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM financial_entries WHERE user_sys_id = ? AND user_type = 'client' AND type = 'debit' AND related_type = 6");
        $dr->execute([$clientSysId]);
        $totalOut = (float) $dr->fetchColumn();

        echo json_encode([
            'success'         => true,
            'advance_balance' => max(0, $totalIn - $totalOut),
            'due_amount'      => $dueAmount
        ]);
        exit;
    }

    /* ============================================================
       ACTION: pay
       ============================================================ */
    if ($action === 'pay') {
        $cashAmount         = (float) ($input['cash_amount'] ?? 0);
        $useAdvance         = (int) ($input['use_advance'] ?? 0);
        $advanceAmount      = (float) ($input['advance_amount'] ?? 0);
        $accountId          = $input['account_id'] ?? '';
        $accountName        = $input['account_name'] ?? '';
        $date               = $input['date'] ?? date('Y-m-d H:i:s');
        $particular         = $input['particular'] ?? 'Invoice Payment';
        $transferMethod     = strtolower($input['transfer_method'] ?? 'cash');
        $overpayAction      = $input['overpayment_action'] ?? 'advance';
        $withDiscount       = isset($input['with_discount']) ? (bool) $input['with_discount'] : false;
        $discountAmount     = (float) ($input['discount_amount'] ?? 0);
        $discountParticular = $input['discount_particular'] ?? '';
        $chequeNo           = $input['chequeNo'] ?? '';
        $chequeDate         = $input['chequeDate'] ?? '';
        $chequeAccountName  = $input['chequeAccountName'] ?? '';
        $bankName           = $input['bankName'] ?? '';
        $bftnNo             = $input['bftnNo'] ?? '';
        $bftnAccountName    = $input['bftnAccountName'] ?? '';
        $eftBankName        = $input['eftBankName'] ?? '';
        $bftnDate           = $input['bftnDate'] ?? '';

        $totalPayment = $cashAmount + ($useAdvance ? $advanceAmount : 0) + ($withDiscount ? $discountAmount : 0);
        if ($totalPayment <= 0) {
            echo json_encode(['success' => false, 'message' => 'Amount is required']);
            exit;
        }

        /* ===== INSTRUMENT HANDLING ===== */
        if (isInstrumentMethod($transferMethod)) {
            $instAccountName = $transferMethod === 'cheque' ? $chequeAccountName : $bftnAccountName;
            $instBankName    = $transferMethod === 'cheque' ? $bankName : $eftBankName;
            $instDate        = $transferMethod === 'cheque' ? ($chequeDate ?: date('Y-m-d')) : ($bftnDate ?: date('Y-m-d'));
            $instNo          = $transferMethod === 'cheque' ? $chequeNo : $bftnNo;

            $financeData = json_encode([
                'client_id'          => $clientSysId,
                'client_name'        => $clientName,
                'selected_sale_ids'  => $feIds,
                'invoice_id'         => $invoice_id,
                'overpayment_action' => $overpayAction,
            ]);

            try {
                $instIds  = generateIDs('AIT');
                $instMeta = buildMetaData(null, $user_name);
                $pdo->prepare("
                    INSERT INTO ac_instrument_tracking
                    (uuid, sys_id, instrument_type, trnx_type, instrument_no, payment_to,
                     account_name, bank_name, instrument_date, amount, related_type,
                     related_from, related_to, status, date, remarks, finance_data, meta_data)
                    VALUES (?, ?, ?, 'credit', ?, 'client', ?, ?, ?, ?, 'received', ?, ?, 'pending', ?, ?, ?, ?)
                ")->execute([
                    $instIds['uuid'], $instIds['sys_id'], strtoupper($transferMethod), $instNo,
                    $instAccountName, $instBankName, $instDate, $cashAmount,
                    $clientSysId . ' || ' . $clientName, $accountId . ' || ' . $accountName,
                    date('Y-m-d'), $particular, $financeData, $instMeta
                ]);
                echo json_encode(['success' => true, 'message' => 'Instrument recorded. Payment pending clearance.', 'instrument' => ['sys_id' => $instIds['sys_id'], 'status' => 'pending']]);
            } catch (Throwable $e) {
                echo json_encode(['success' => false, 'message' => 'Instrument store failed: ' . $e->getMessage()]);
            }
            exit;
        }

        if ($cashAmount > 0 && empty($accountId)) {
            echo json_encode(['success' => false, 'message' => 'Account is required']);
            exit;
        }

        $pdo->beginTransaction();

        $advanceUsed   = 0;
        $cashReceived  = 0;
        $discountUsed  = 0;
        $overpayAmount = 0;

        /* ===== STEP 1: ADVANCE ===== */
        if ($useAdvance && $advanceAmount > 0) {
            $crStmt = $pdo->prepare("SELECT sys_id, amount FROM financial_entries WHERE user_sys_id = ? AND user_type = 'client' AND type = 'credit' AND related_type = 6 ORDER BY date ASC, id ASC");
            $crStmt->execute([$clientSysId]);
            $advEntries = $crStmt->fetchAll(PDO::FETCH_ASSOC);

            $drStmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM financial_entries WHERE user_sys_id = ? AND user_type = 'client' AND type = 'debit' AND related_type = 6");
            $drStmt->execute([$clientSysId]);
            $alreadyUsed    = (float) $drStmt->fetchColumn();
            $totalAdvCredit = array_sum(array_column($advEntries, 'amount'));
            $netAvailable   = max(0, $totalAdvCredit - $alreadyUsed);

            $toConsume = min(min($advanceAmount, $dueAmount), $netAvailable);
            $remaining = $toConsume;

            foreach ($advEntries as $adv) {
                if ($remaining <= 0.009) break;
                $useAmt = min((float) $adv['amount'], $remaining);

                // Credit (advance balance কমে)
                $ids  = generateIDsSafe('financial_entries');
                $meta = buildMetaData(null, $user_name);
                $pdo->prepare("INSERT INTO financial_entries (uuid,sys_id,user_sys_id,user_name,user_type,date,purpose,type,related_type,is_paid,is_partial,is_discounted,amount,ref,meta_data) VALUES(?,?,?,?,'client',?,?,'credit',3,1,0,0,?,?,?)")
                    ->execute([$ids['uuid'],$ids['sys_id'],$clientSysId,$clientName,$date,'Advance Consumed: Invoice '.$invoice_id,$useAmt,$adv['sys_id'],$meta]);

                // Debit (advance usage record)
                $ids2  = generateIDsSafe('financial_entries');
                $meta2 = buildMetaData(null, $user_name);
                $pdo->prepare("INSERT INTO financial_entries (uuid,sys_id,user_sys_id,user_name,user_type,date,purpose,type,related_type,is_paid,is_partial,is_discounted,amount,ref,meta_data) VALUES(?,?,?,?,'client',?,?,'debit',6,1,0,0,?,?,?)")
                    ->execute([$ids2['uuid'],$ids2['sys_id'],$clientSysId,$clientName,$date,'Advance Applied: Invoice '.$invoice_id,$useAmt,$adv['sys_id'],$meta2]);

                $advanceUsed += $useAmt;
                $remaining   -= $useAmt;
            }
        }

        /* ===== STEP 2: DISCOUNT ===== */
        if ($withDiscount && $discountAmount > 0) {
            $discountUsed = min($discountAmount, $dueAmount - $advanceUsed);
            if ($discountUsed > 0) {
                insertDiscountEntry($pdo, $discountUsed, 'client', $clientSysId, $clientName, $date,
                    $discountParticular ?: 'Discount: Invoice '.$invoice_id, $feIds, $user_name);
            }
        }

        /* ===== STEP 3: CASH ===== */
        $remainingDue   = $dueAmount - $advanceUsed - $discountUsed;
        $cashApplicable = min($cashAmount, max(0, $remainingDue));
        $overpayAmount  = max(0, $cashAmount - $cashApplicable);
        $cashReceived   = $cashApplicable;

        if ($cashReceived > 0) {
            $accStmt = $pdo->prepare("SELECT balance FROM ac_banking WHERE sys_id = ?");
            $accStmt->execute([$accountId]);
            $accRow = $accStmt->fetch(PDO::FETCH_ASSOC);

            if (!$accRow) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Account not found']);
                exit;
            }

            $newBalance = (float) $accRow['balance'] + $cashReceived;
            $pdo->prepare("UPDATE ac_banking SET balance = ? WHERE sys_id = ?")->execute([$newBalance, $accountId]);

            $sIds  = generateIDsSafe('ac_banking_stmts');
            $sMeta = buildMetaData(null, $user_name);
            $pdo->prepare("INSERT INTO ac_banking_stmts (uuid,sys_id,ledger_db_id,name,date,particular,withdraw,deposit,balance,transfer_method,related_type,meta_data) VALUES(?,?,?,?,?,?,0,?,?,?,1,?)")
                ->execute([$sIds['uuid'],$sIds['sys_id'],$accountId,$accountName,$date,$particular,$cashReceived,$newBalance,$transferMethod,$sMeta]);
        }

        /* ===== STEP 4: APPLY CASH TO SALES =====
         * শুধু cashReceived এর জন্য applyPaymentToSales() call।
         * Advance এর জন্য invoice create সময় already rt=3 entry আছে
         * ("Received via Advance") — Mark as Paid এ advance শুধু
         * rt=6 debit/credit করে, নতুন rt=3 entry দরকার নেই।
         *
         * getSaleRemaining() এ rt=3 count হয় — invoice create এর
         * advance entry (rt=3) already covered, তাই cash apply হলে
         * remaining = due_amount - cash = correct।
         */
        if (!empty($feIds) && $cashReceived > 0) {
            applyPaymentToSales($pdo, $feIds, $cashReceived, $clientSysId, $clientName, $date, $particular, $user_name);
        }

        // Advance used → sale paid status update
        // Cash apply হওয়ার পর remaining check করে is_paid/is_partial set করি
        if (!empty($feIds) && $advanceUsed > 0) {
            $ph2 = implode(',', array_fill(0, count($feIds), '?'));
            $salesStmt = $pdo->prepare("SELECT sys_id, amount, is_paid, is_partial, ref FROM financial_entries WHERE sys_id IN ($ph2)");
            $salesStmt->execute(array_values($feIds));
            foreach ($salesStmt->fetchAll(PDO::FETCH_ASSOC) as $salRow) {
                if ((int)$salRow['is_paid'] === 1) continue;
                // remaining = amount - rt=3 covered - advance used (approximation)
                $remaining = getSaleRemaining($pdo, $salRow);
                if ($remaining <= $advanceUsed + 0.009) {
                    // Advance এ fully covered
                    $pdo->prepare("UPDATE financial_entries SET is_paid=1, is_partial=0 WHERE sys_id=?")
                        ->execute([$salRow['sys_id']]);
                } else {
                    $pdo->prepare("UPDATE financial_entries SET is_partial=1 WHERE sys_id=?")
                        ->execute([$salRow['sys_id']]);
                }
            }
        }

        /* ===== STEP 5: OVERPAYMENT ===== */
        $overpayEntrySysId = null;
        if ($overpayAmount > 0.009) {
            $overpayEntrySysId = handleOverpayment($pdo, $overpayAction, $overpayAmount, 'client', $clientSysId, $clientName, $date, $invoice_id, $user_name);
        }

        /* ===== STEP 6: UPDATE INVOICE ===== */
        $totalNowPaid = $alreadyPaid + $advanceUsed + $cashReceived + $discountUsed;
        $newDue       = max(0, $totalAmount - $totalNowPaid);
        $newStatus    = $totalNowPaid >= $totalAmount - 0.009 ? 1 : ($totalNowPaid > 0 ? 2 : 0);

        $metaArray = json_decode($invoice['meta_data'] ?? '{}', true) ?: [];
        if (!isset($metaArray['updated_by_date'])) $metaArray['updated_by_date'] = [];
        array_unshift($metaArray['updated_by_date'], [
            'user'             => $user_name,
            'date'             => date('d-m-Y H:i'),
            'action'           => $newStatus === 1 ? 'Marked as Paid' : 'Partial Payment',
            'advance_used'     => $advanceUsed,
            'cash_received'    => $cashReceived,
            'discount_applied' => $discountUsed
        ]);

        $pdo->prepare("UPDATE invoices SET paid_amount=?,due_amount=?,status=?,meta_data=?,updated_at=NOW() WHERE sys_id=?")
            ->execute([$totalNowPaid, $newDue, $newStatus, json_encode($metaArray), $invoice_id]);

        $pdo->commit();

        echo json_encode([
            'success'       => true,
            'message'       => $newStatus === 1 ? 'Invoice marked as paid' : 'Partial payment recorded',
            'status'        => $newStatus,
            'total_paid'    => $totalNowPaid,
            'due_amount'    => $newDue,
            'advance_used'  => $advanceUsed,
            'cash_received' => $cashReceived,
            'discount'      => $discountUsed,
            'overpayment'   => $overpayAmount,
            'overpay_entry' => $overpayEntrySysId,
            'breakdown'     => [
                'already_paid'     => $alreadyPaid,
                'advance_used'     => $advanceUsed,
                'cash_received'    => $cashReceived,
                'discount_applied' => $discountUsed,
                'total_coverage'   => $advanceUsed + $cashReceived + $discountUsed
            ]
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action']);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log("Invoice update error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>