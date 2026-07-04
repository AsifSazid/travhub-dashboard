<?php
// api/invoices/update-invoice-status.php
session_start();
header('Content-Type: application/json');
require_once '../../server/db_connection.php';
require_once '../../server/uuid_with_system_id_generator.php';
require_once '../../server/generate_meta_data.php';

$input      = json_decode(file_get_contents("php://input"), true);
$invoice_id = $input['invoice_id'] ?? '';
$action     = $input['action']     ?? 'check_advance';
$user_name  = $_SESSION['user_name'] ?? 'system';

if (empty($invoice_id)) {
    echo json_encode(['success' => false, 'message' => 'Invoice ID is required']);
    exit;
}

try {
    /* ===== Fetch Invoice ===== */
    $stmt = $pdo->prepare("SELECT * FROM invoices WHERE sys_id = ?");
    $stmt->execute([$invoice_id]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$invoice) {
        echo json_encode(['success' => false, 'message' => 'Invoice not found']);
        exit;
    }

    $totalAmount  = (float)$invoice['total_amount'];
    $alreadyPaid  = (float)$invoice['paid_amount'];
    $dueAmount    = (float)$invoice['due_amount'];
    $clientSysId  = $invoice['client_sys_id'];
    $clientName   = $invoice['client_name'];
    $feIds        = json_decode($invoice['financial_entry_ids'] ?? '[]', true) ?: [];

    /* ===== action=check_advance ===== */
    if ($action === 'check_advance') {
        // Balance = SUM(credit rt=6) - SUM(debit rt=6)
        $crStmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0) FROM financial_entries
            WHERE user_sys_id = ? AND user_type = 'client'
            AND type = 'credit' AND related_type = 6
        ");
        $crStmt->execute([$clientSysId]);
        $totalIn = (float)$crStmt->fetchColumn();

        $drStmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0) FROM financial_entries
            WHERE user_sys_id = ? AND user_type = 'client'
            AND type = 'debit' AND related_type = 6
        ");
        $drStmt->execute([$clientSysId]);
        $totalOut = (float)$drStmt->fetchColumn();

        $advanceBalance = max(0, $totalIn - $totalOut);

        echo json_encode([
            'success'         => true,
            'advance_balance' => $advanceBalance,
            'due_amount'      => $dueAmount,
        ]);
        exit;
    }

    /* ===== action=pay ===== */
    if ($action === 'pay') {
        $cashAmount    = (float)($input['cash_amount']    ?? 0);
        $useAdvance    = (int)($input['use_advance']      ?? 0);
        $advanceAmount = (float)($input['advance_amount'] ?? 0);
        $accountId     = $input['account_id']   ?? '';
        $accountName   = $input['account_name'] ?? '';
        $date          = $input['date']          ?? date('Y-m-d H:i:s');
        $particular    = $input['particular']    ?? 'Invoice Payment';

        // Validation
        $totalPayment = $cashAmount + ($useAdvance ? $advanceAmount : 0);
        if ($totalPayment <= 0) {
            echo json_encode(['success' => false, 'message' => 'Amount is required']);
            exit;
        }
        if ($cashAmount > 0 && empty($accountId)) {
            echo json_encode(['success' => false, 'message' => 'Account is required']);
            exit;
        }

        $pdo->beginTransaction();

        $advanceUsed   = 0;
        $cashReceived  = 0;
        $baksheeshAmt  = 0;

        /* ===== 1. Advance Consume ===== */
        if ($useAdvance && $advanceAmount > 0) {
            // Net available advance
            $crStmt = $pdo->prepare("
                SELECT sys_id, amount FROM financial_entries
                WHERE user_sys_id = ? AND user_type = 'client'
                AND type = 'credit' AND related_type = 6
                ORDER BY date ASC, id ASC
            ");
            $crStmt->execute([$clientSysId]);
            $advEntries = $crStmt->fetchAll(PDO::FETCH_ASSOC);

            $drStmt = $pdo->prepare("
                SELECT COALESCE(SUM(amount), 0) FROM financial_entries
                WHERE user_sys_id = ? AND user_type = 'client'
                AND type = 'debit' AND related_type = 6
            ");
            $drStmt->execute([$clientSysId]);
            $alreadyUsed    = (float)$drStmt->fetchColumn();
            $totalAdvCredit = array_sum(array_column($advEntries, 'amount'));
            $netAvailable   = max(0, $totalAdvCredit - $alreadyUsed);

            $toConsume = min($advanceAmount, $netAvailable);
            $remaining = $toConsume;

            foreach ($advEntries as $adv) {
                if ($remaining <= 0) break;
                $useAmt = min((float)$adv['amount'], $remaining);

                // Advance used debit entry
                $advIds  = generateIDs('financial_entries');
                $advMeta = buildMetaData(null, $user_name);
                $pdo->prepare("
                    INSERT INTO financial_entries
                    (uuid, sys_id, user_sys_id, user_name, user_type,
                     date, purpose, type, related_type,
                     is_paid, is_partial, is_discounted, amount, ref, meta_data)
                    VALUES (?, ?, ?, ?, 'client', ?, ?, 'debit', 6, 1, 0, 0, ?, ?, ?)
                ")->execute([
                    $advIds['uuid'], $advIds['sys_id'],
                    $clientSysId, $clientName,
                    $date,
                    'Advance Used: Invoice ' . $invoice_id,
                    $useAmt,
                    $adv['sys_id'],
                    $advMeta
                ]);

                $advanceUsed += $useAmt;
                $remaining   -= $useAmt;
            }

            // Advance consume এর বিপরীতে receive entry (rt=3) insert
            // এটা outstanding এ credit হিসেবে দেখাবে
            if ($advanceUsed > 0.01) {
                $rcvIds  = generateIDs('financial_entries');
                $rcvMeta = buildMetaData(null, $user_name);
                $rcvRef  = !empty($feIds) ? json_encode($feIds) : $invoice_id;
                $pdo->prepare("
                    INSERT INTO financial_entries
                    (uuid, sys_id, user_sys_id, user_name, user_type,
                     date, purpose, type, related_type,
                     is_paid, is_partial, is_discounted, amount, ref, meta_data)
                    VALUES (?, ?, ?, ?, 'client', ?, ?, 'credit', 3, 1, 0, 0, ?, ?, ?)
                ")->execute([
                    $rcvIds['uuid'], $rcvIds['sys_id'],
                    $clientSysId, $clientName,
                    $date,
                    'Received via Advance: Invoice ' . $invoice_id,
                    $advanceUsed,
                    $rcvRef,
                    $rcvMeta
                ]);
            }
        }

        /* ===== 2. Cash Receive ===== */
        if ($cashAmount > 0) {
            // Net cash applicable to invoice (exclude overpayment)
            $cashApplicable = min($cashAmount, $dueAmount - $advanceUsed);
            $cashApplicable = max(0, $cashApplicable);
            $overpayment    = $cashAmount - $cashApplicable;

            // ac_banking: balance update
            $accStmt = $pdo->prepare("SELECT balance FROM ac_banking WHERE sys_id = ?");
            $accStmt->execute([$accountId]);
            $accRow = $accStmt->fetch(PDO::FETCH_ASSOC);

            if (!$accRow) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Account not found']);
                exit;
            }

            $oldBalance = (float)$accRow['balance'];
            $newBalance = $oldBalance + $cashAmount; // full cash deposit

            $pdo->prepare("UPDATE ac_banking SET balance = ? WHERE sys_id = ?")
                ->execute([$newBalance, $accountId]);

            // financial_entries: receive (rt=3)
            $rcvIds  = generateIDs('financial_entries');
            $rcvMeta = buildMetaData(null, $user_name);
            $rcvRef  = !empty($feIds) ? json_encode($feIds) : $invoice_id;
            $pdo->prepare("
                INSERT INTO financial_entries
                (uuid, sys_id, user_sys_id, user_name, user_type,
                 date, purpose, type, related_type,
                 is_paid, is_partial, is_discounted, amount, ref, meta_data)
                VALUES (?, ?, ?, ?, 'client', ?, ?, 'credit', 3, 1, 0, 0, ?, ?, ?)
            ")->execute([
                $rcvIds['uuid'], $rcvIds['sys_id'],
                $clientSysId, $clientName,
                $date, $particular,
                $cashApplicable > 0 ? $cashApplicable : $cashAmount,
                $rcvRef, $rcvMeta
            ]);

            // ac_banking_stmts
            $stmtIds  = generateIDs('ac_banking_stmts');
            $stmtMeta = buildMetaData(null, $user_name);
            $pdo->prepare("
                INSERT INTO ac_banking_stmts
                (uuid, sys_id, ledger_db_id, name, date, particular,
                 withdraw, deposit, balance, transfer_method, related_type, meta_data, ref)
                VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, 'Cash', 1, ?, ?)
            ")->execute([
                $stmtIds['uuid'], $stmtIds['sys_id'],
                $accountId, $accountName,
                $date, $particular,
                $cashAmount, $newBalance,
                $stmtMeta, $rcvIds['sys_id']
            ]);

            $cashReceived = $cashApplicable;

            // Baksheesh (overpayment)
            if ($overpayment > 0.01) {
                $bkIds  = generateIDs('financial_entries');
                $bkMeta = buildMetaData(null, $user_name);
                $pdo->prepare("
                    INSERT INTO financial_entries
                    (uuid, sys_id, user_sys_id, user_name, user_type,
                     date, purpose, type, related_type,
                     is_paid, is_partial, is_discounted, amount, ref, meta_data)
                    VALUES (?, ?, ?, ?, 'client', ?, ?, 'credit', 7, 1, 0, 0, ?, ?, ?)
                ")->execute([
                    $bkIds['uuid'], $bkIds['sys_id'],
                    $clientSysId, $clientName,
                    $date,
                    'Baksheesh from Invoice: ' . $invoice_id,
                    $overpayment,
                    $invoice_id,
                    $bkMeta
                ]);
                $baksheeshAmt = $overpayment;
            }
        }

        /* ===== 3. Update Invoice ===== */
        $totalNowPaid = $alreadyPaid + $advanceUsed + $cashReceived;
        $newDue       = max(0, $totalAmount - $totalNowPaid);

        if ($totalNowPaid >= $totalAmount - 0.01) {
            $newStatus = 1; // paid
        } elseif ($totalNowPaid > 0) {
            $newStatus = 2; // partial
        } else {
            $newStatus = 0;
        }

        /* ===== 4. Update financial_entries is_paid ===== */
        if (!empty($feIds) && $newStatus === 1) {
            $ph = implode(',', array_fill(0, count($feIds), '?'));
            $pdo->prepare("UPDATE financial_entries SET is_paid=1 WHERE sys_id IN ($ph)")
                ->execute($feIds);
        } elseif (!empty($feIds) && $newStatus === 2) {
            // Partial — oldest first
            $feStmt = $pdo->prepare("
                SELECT sys_id, amount FROM financial_entries
                WHERE sys_id IN (" . implode(',', array_fill(0, count($feIds), '?')) . ")
                ORDER BY date ASC, id ASC
            ");
            $feStmt->execute($feIds);
            $feEntries  = $feStmt->fetchAll(PDO::FETCH_ASSOC);
            $remaining  = $totalNowPaid;
            foreach ($feEntries as $fe) {
                $amt = (float)$fe['amount'];
                if ($remaining >= $amt - 0.01) {
                    $pdo->prepare("UPDATE financial_entries SET is_paid=1, is_partial=0 WHERE sys_id=?")
                        ->execute([$fe['sys_id']]);
                    $remaining -= $amt;
                } elseif ($remaining > 0) {
                    $pdo->prepare("UPDATE financial_entries SET is_partial=1 WHERE sys_id=?")
                        ->execute([$fe['sys_id']]);
                    $remaining = 0;
                }
            }
        }

        /* ===== 5. Update invoices table ===== */
        $metaArray = json_decode($invoice['meta_data'] ?? '{}', true) ?: [];
        if (!isset($metaArray['updated_by_date'])) $metaArray['updated_by_date'] = [];
        array_unshift($metaArray['updated_by_date'], [
            'user'   => $user_name,
            'date'   => date('d-m-Y H:i'),
            'action' => $newStatus === 1 ? 'Marked as Paid' : 'Partial Payment',
            'amount' => $totalNowPaid
        ]);

        $pdo->prepare("
            UPDATE invoices
            SET paid_amount = ?, due_amount = ?, status = ?, meta_data = ?, updated_at = NOW()
            WHERE sys_id = ?
        ")->execute([
            $totalNowPaid, $newDue, $newStatus,
            json_encode($metaArray, JSON_PRETTY_PRINT),
            $invoice_id
        ]);

        $pdo->commit();

        echo json_encode([
            'success'       => true,
            'message'       => $newStatus === 1 ? 'Invoice marked as paid' : 'Partial payment recorded',
            'status'        => $newStatus,
            'total_paid'    => $totalNowPaid,
            'due_amount'    => $newDue,
            'advance_used'  => $advanceUsed,
            'cash_received' => $cashReceived,
            'baksheesh'     => $baksheeshAmt,
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Invoice update error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}