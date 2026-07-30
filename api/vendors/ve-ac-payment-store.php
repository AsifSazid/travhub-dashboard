<?php
// PATH: /api/vendors/ve-ac-payment-store.php
// v4 — FIFO payment, single rt=4 entry, no advance on no-selection

session_start();
require '../../server/db_connection.php';
require '../../server/uuid_with_system_id_generator.php';
require '../../server/generate_meta_data.php';
require '../../server/finance_helpers.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method Not Allowed']); exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    http_response_code(400); echo json_encode(['success'=>false,'message'=>'Invalid JSON']); exit;
}

/* ===== INPUT ===== */
$vendorId            = $data['vendorId']            ?? null;
$vendorName          = $data['vendorName']          ?? null;
$amount              = (float)($data['amount']      ?? 0);
$particular          = $data['particular']          ?? '';
$transactionDate     = $data['transactionDate']     ?? date('Y-m-d H:i:s');
$transferMethod      = strtolower($data['transferMethod'] ?? 'cash');
$accountId           = $data['accountId']           ?? '';
$accountName         = $data['accountName']         ?? '';
$isHistorical        = isset($data['isHistorical']) ? (int)$data['isHistorical'] : 0;
$selectedPurchaseIds = $data['selectedPurchaseIds'] ?? [];
$overpaymentAction   = $data['overpayment_action']  ?? 'advance';
$withDiscount        = isset($data['withDiscount']) ? (bool)$data['withDiscount'] : false;
$discountAmount      = (float)($data['discountAmount'] ?? 0);
$discountParticular  = $data['discountParticular']  ?? '';
$useVendorAdvance    = isset($data['use_vendor_advance']) ? (bool)$data['use_vendor_advance'] : false;
$vendorAdvanceAmount = (float)($data['vendor_advance_amount'] ?? 0);

$chequeNo          = $data['chequeNo']          ?? '';
$chequeDate        = $data['chequeDate']        ?? '';
$chequeAccountName = $data['chequeAccountName'] ?? '';
$bankName          = $data['bankName']          ?? '';
$bftnNo            = $data['bftnNo']            ?? '';
$bftnAccountName   = $data['bftnAccountName']   ?? '';
$eftBankName       = $data['eftBankName']       ?? '';
$bftnDate          = $data['bftnDate']          ?? '';

$userName = $_SESSION['user_name'] ?? 'system';

/* ===== VALIDATION ===== */
if ($amount <= 0) {
    http_response_code(400); echo json_encode(['success'=>false,'message'=>'Invalid amount']); exit;
}
if (!$vendorId) {
    http_response_code(400); echo json_encode(['success'=>false,'message'=>'Vendor is required']); exit;
}
$cutoff = new DateTime('2026-02-01', new DateTimeZone('Asia/Dhaka'));
$txnDt  = new DateTime($transactionDate, new DateTimeZone('Asia/Dhaka'));
if ($txnDt >= $cutoff && isInstantMethod($transferMethod) && (!$accountId || !$accountName)) {
    http_response_code(400); echo json_encode(['success'=>false,'message'=>'Account is required']); exit;
}

/* ===== INSTRUMENT ===== */
if (isInstrumentMethod($transferMethod)) {
    try {
        $instIds  = generateIDs('AIT');
        $instMeta = buildMetaData(null, $userName);
        $instAccountName = $transferMethod === 'cheque' ? $chequeAccountName : $bftnAccountName;
        $instBankName    = $transferMethod === 'cheque' ? $bankName          : $eftBankName;
        $instDate        = $transferMethod === 'cheque' ? ($chequeDate ?: date('Y-m-d')) : ($bftnDate ?: date('Y-m-d'));
        $instNo          = $transferMethod === 'cheque' ? $chequeNo : $bftnNo;

        // finance_data — clear API তে purchase entries clear করার জন্য
        $financeData = json_encode([
            'vendor_id'           => $vendorId,
            'vendor_name'         => $vendorName,
            'selected_purchase_ids' => $selectedPurchaseIds,
            'overpayment_action'  => $overpaymentAction,
        ]);

        $pdo->prepare("
            INSERT INTO ac_instrument_tracking
            (uuid, sys_id, instrument_type, trnx_type, instrument_no, payment_to,
             account_name, bank_name, instrument_date, amount, related_type,
             related_from, related_to, status, date, remarks, finance_data, meta_data)
            VALUES (?, ?, ?, 'debit', ?, 'vendor', ?, ?, ?, ?, 'payment', ?, ?, 'pending', ?, ?, ?, ?)
        ")->execute([
            $instIds['uuid'], $instIds['sys_id'],
            strtoupper($transferMethod), $instNo,
            $instAccountName, $instBankName, $instDate,
            $amount,
            $accountId . ' || ' . $accountName,
            $vendorId  . ' || ' . $vendorName,
            date('Y-m-d'), $particular, $financeData, $instMeta
        ]);
        echo json_encode(['success'=>true,'message'=>'Instrument recorded. Pending clearance.','data'=>['sys_id'=>$instIds['sys_id']]]);
    } catch (Throwable $e) {
        echo json_encode(['success'=>false,'message'=>'Instrument store failed: '.$e->getMessage()]);
    }
    exit;
}

/* ===== MAIN TRANSACTION ===== */
try {
    $pdo->beginTransaction();

    /* ===== 1. Determine purchase targets =====
     * Selection আছে → selected purchases
     * Selection নেই → সব unpaid purchases FIFO তে
     */
    $hasSelection = !empty($selectedPurchaseIds);

    if (!$hasSelection) {
        // সব unpaid/partial purchases load করি (FIFO)
        $allPurchStmt = $pdo->prepare("
            SELECT sys_id FROM financial_entries
            WHERE user_sys_id = ? AND user_type = 'vendor'
              AND type = 'credit' AND related_type = 2
              AND is_paid = 0
            ORDER BY date ASC, id ASC
        ");
        $allPurchStmt->execute([$vendorId]);
        $selectedPurchaseIds = $allPurchStmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /* ===== 2. Calculate total remaining ===== */
    $totalRemaining = 0;
    if (!empty($selectedPurchaseIds)) {
        $ph = implode(',', array_fill(0, count($selectedPurchaseIds), '?'));
        $pr = $pdo->prepare("SELECT sys_id, amount, is_paid, is_partial, ref FROM financial_entries WHERE sys_id IN ($ph)");
        $pr->execute(array_values($selectedPurchaseIds));
        foreach ($pr->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $totalRemaining += getPurchaseRemaining($pdo, $row);
        }
    }

    /* ===== 3. Vendor Advance consume ===== */
    $advanceUsed = 0;
    if ($useVendorAdvance && $vendorAdvanceAmount > 0 && !empty($selectedPurchaseIds)) {
        $crStmt = $pdo->prepare("SELECT sys_id, amount FROM financial_entries WHERE user_sys_id=? AND user_type='vendor' AND type='debit' AND related_type=6 ORDER BY date ASC, id ASC");
        $crStmt->execute([$vendorId]);
        $advEntries = $crStmt->fetchAll(PDO::FETCH_ASSOC);

        $drStmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM financial_entries WHERE user_sys_id=? AND user_type='vendor' AND type='credit' AND related_type=6");
        $drStmt->execute([$vendorId]);
        $alreadyUsed   = (float)$drStmt->fetchColumn();
        $totalAdvDebit = array_sum(array_column($advEntries, 'amount'));
        $netAvailable  = max(0, $totalAdvDebit - $alreadyUsed);
        $toConsume     = min($vendorAdvanceAmount, $netAvailable);
        $remaining     = $toConsume;

        foreach ($advEntries as $adv) {
            if ($remaining <= 0.009) break;
            $useAmt = min((float)$adv['amount'], $remaining);

            // Advance credit entry (consume)
            $advIds  = generateIDsSafe('financial_entries');
            $advMeta = buildMetaData(null, $userName);
            $pdo->prepare("
                INSERT INTO financial_entries
                (uuid, sys_id, user_sys_id, user_name, user_type,
                 date, purpose, type, related_type,
                 is_paid, is_partial, is_discounted, amount, ref, meta_data)
                VALUES (?, ?, ?, ?, 'vendor', ?, ?, 'credit', 6, 1, 0, 0, ?, ?, ?)
            ")->execute([
                $advIds['uuid'], $advIds['sys_id'],
                $vendorId, $vendorName,
                $transactionDate, 'Advance Used: ' . $particular,
                $useAmt, $adv['sys_id'], $advMeta
            ]);
            $advanceUsed += $useAmt;
            $remaining   -= $useAmt;
        }
        $totalRemaining = max(0, $totalRemaining - $advanceUsed);
    }

    /* ===== 4. Overpayment calculate ===== */
    $cashToClear   = !empty($selectedPurchaseIds) ? min($amount, $totalRemaining) : $amount;
    $overpayAmount = !empty($selectedPurchaseIds) && $amount > $totalRemaining + 0.009
        ? $amount - $totalRemaining : 0;

    /* ===== 5. Bank update ===== */
    $accR = $pdo->prepare("SELECT balance FROM ac_banking WHERE sys_id = ? FOR UPDATE");
    $accR->execute([$accountId]);
    $acc = $accR->fetch(PDO::FETCH_ASSOC);
    if (!$acc) throw new Exception('Account not found');
    $curBal = (float)$acc['balance'];

    $openR = $pdo->prepare("SELECT MIN(date) FROM ac_banking_stmts WHERE ledger_db_id=? AND particular='Opening Balance'");
    $openR->execute([$accountId]);
    $openDate = $openR->fetchColumn();
    if ($openDate && $transactionDate < $openDate) $isHistorical = 1;

    $daysDiff = (int)$pdo->query("SELECT DATEDIFF(NOW(), '{$transactionDate}')")->fetchColumn();
    if (!$isHistorical && $daysDiff > 10) throw new Exception('Backdated entry limit is 10 days.');

    $newBal = $isHistorical ? $curBal : ($curBal - $amount);
    if (!$isHistorical) {
        $pdo->prepare("UPDATE ac_banking SET balance=? WHERE sys_id=?")->execute([$newBal, $accountId]);
    }

    /* ===== 6. ac_banking_stmts ===== */
    $stmtIds  = generateIDsSafe('ac_banking_stmts');
    $stmtMeta = buildMetaData(null, $userName);
    $pdo->prepare("
        INSERT INTO ac_banking_stmts
        (uuid, sys_id, ledger_db_id, name, date, particular,
         withdraw, deposit, balance, transfer_method, related_type, meta_data, is_historical)
        VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, 2, ?, ?)
    ")->execute([
        $stmtIds['uuid'], $stmtIds['sys_id'],
        $accountId, $accountName, $transactionDate, $particular,
        $amount, $newBal, $transferMethod, $stmtMeta, $isHistorical
    ]);
    $stmtSysId = $stmtIds['sys_id'];

    /* ===== 7. FIFO purchase status update =====
     * applyPaymentToPurchases() বাদ দিয়ে manually করছি
     * কারণ Option B: একটাই rt=4 entry চাই
     */
    $appliedAmt = 0;
    if (!empty($selectedPurchaseIds) && $cashToClear > 0.009) {
        $ph = implode(',', array_fill(0, count($selectedPurchaseIds), '?'));
        $purchStmt = $pdo->prepare("SELECT sys_id, amount, is_paid, is_partial, ref FROM financial_entries WHERE sys_id IN ($ph) ORDER BY date ASC, id ASC");
        $purchStmt->execute(array_values($selectedPurchaseIds));
        $purchases = $purchStmt->fetchAll(PDO::FETCH_ASSOC);

        $remaining = $cashToClear;
        foreach ($purchases as $purch) {
            if ($remaining <= 0.009) break;
            if ((int)$purch['is_paid'] === 1) continue;

            $purchRemaining = getPurchaseRemaining($pdo, $purch);
            if ($purchRemaining <= 0.009) {
                $pdo->prepare("UPDATE financial_entries SET is_paid=1, is_partial=0 WHERE sys_id=?")
                    ->execute([$purch['sys_id']]);
                continue;
            }

            $payThis   = min($purchRemaining, $remaining);
            $fullCover = ($payThis >= $purchRemaining - 0.009);

            if ($fullCover) {
                $pdo->prepare("UPDATE financial_entries SET is_paid=1, is_partial=0 WHERE sys_id=?")
                    ->execute([$purch['sys_id']]);
            } else {
                $pdo->prepare("UPDATE financial_entries SET is_partial=1 WHERE sys_id=?")
                    ->execute([$purch['sys_id']]);
            }

            $appliedAmt += $payThis;
            $remaining  -= $payThis;
        }
    }

    /* ===== 8. Single rt=4 payment entry ===== */
    $payIds  = generateIDsSafe('financial_entries');
    $payMeta = buildMetaData(null, $userName);
    $payRef  = !empty($selectedPurchaseIds) ? json_encode(array_values($selectedPurchaseIds)) : $stmtSysId;
    $isPartial = !empty($selectedPurchaseIds) && ($cashToClear < $totalRemaining - 0.009);

    $pdo->prepare("
        INSERT INTO financial_entries
        (uuid, sys_id, user_sys_id, user_name, user_type,
         date, purpose, type, related_type,
         is_paid, is_partial, is_discounted, amount, ref, meta_data)
        VALUES (?, ?, ?, ?, 'vendor', ?, ?, 'debit', 4, 1, ?, 0, ?, ?, ?)
    ")->execute([
        $payIds['uuid'], $payIds['sys_id'],
        $vendorId, $vendorName,
        $transactionDate, $particular,
        $isPartial ? 1 : 0,
        $amount,  // full amount (bank statement এর সাথে match)
        $payRef,
        $payMeta
    ]);

    // ac_banking_stmts.ref update
    $pdo->prepare("UPDATE ac_banking_stmts SET ref=? WHERE sys_id=?")
        ->execute([$payIds['sys_id'], $stmtSysId]);

    /* ===== 9. Discount ===== */
    $discountSysId = null;
    if ($withDiscount && $discountAmount > 0 && !empty($selectedPurchaseIds)) {
        $discountSysId = insertDiscountEntry(
            $pdo, $discountAmount, 'vendor',
            $vendorId, $vendorName, $transactionDate,
            $discountParticular ?: ('Discount: ' . $particular),
            $selectedPurchaseIds, $userName
        );
    }

    /* ===== 10. Overpayment ===== */
    $overpayEntrySysId = null;
    if ($overpayAmount > 0.009) {
        $overpayEntrySysId = handleOverpayment(
            $pdo, $overpaymentAction, $overpayAmount,
            'vendor', $vendorId, $vendorName,
            $transactionDate, $stmtSysId, $userName
        );
    }

    /* ===== 11. Backdated recalc ===== */
    if (!$isHistorical && $daysDiff > 0) {
        recalculateBalances($pdo, $accountId, $transactionDate);
    }

    $pdo->commit();

    // Remaining due calculate
    $totalDue = 0;
    if (!empty($selectedPurchaseIds)) {
        $ph2 = implode(',', array_fill(0, count($selectedPurchaseIds), '?'));
        $remStmt = $pdo->prepare("SELECT sys_id, amount, is_paid, is_partial, ref FROM financial_entries WHERE sys_id IN ($ph2)");
        $remStmt->execute(array_values($selectedPurchaseIds));
        foreach ($remStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $totalDue += getPurchaseRemaining($pdo, $row);
        }
    }

    $message = $isHistorical
        ? 'Historical entry recorded'
        : ($totalDue > 0.009
            ? "Payment recorded. Remaining due: ৳" . number_format($totalDue, 2)
            : 'Payment recorded successfully — all selected purchases cleared');

    echo json_encode([
        'success'           => true,
        'message'           => $message,
        'is_historical'     => $isHistorical,
        'advance_used'      => $advanceUsed,
        'overpayment_amount'=> $overpayAmount,
        'remaining_due'     => $totalDue,
        'data' => [
            'bank_stmt_id'      => $stmtSysId,
            'payment_entry_id'  => $payIds['sys_id'],
            'payment_entry_ids' => [$payIds['sys_id']],
            'discount_entry_id' => $discountSysId,
            'overpay_entry_id'  => $overpayEntrySysId,
            'new_balance'       => $newBal,
            'applied_amount'    => $appliedAmt,
        ]
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function recalculateBalances($pdo, $accountId, $fromDate) {
    $p = $pdo->prepare("SELECT balance FROM ac_banking_stmts WHERE ledger_db_id=? AND date<? AND is_historical=0 ORDER BY date DESC,sys_id DESC LIMIT 1");
    $p->execute([$accountId, $fromDate]);
    $prev = (float)$p->fetchColumn();
    if (!$prev) {
        $s = $pdo->prepare("SELECT balance FROM ac_banking_stmts WHERE ledger_db_id=? AND particular='Opening Balance' LIMIT 1");
        $s->execute([$accountId]); $prev = (float)$s->fetchColumn();
    }
    $t = $pdo->prepare("SELECT sys_id,withdraw,deposit FROM ac_banking_stmts WHERE ledger_db_id=? AND date>=? AND is_historical=0 ORDER BY date ASC,sys_id ASC");
    $t->execute([$accountId, $fromDate]);
    $run = $prev;
    $u   = $pdo->prepare("UPDATE ac_banking_stmts SET balance=? WHERE sys_id=?");
    foreach ($t->fetchAll(PDO::FETCH_ASSOC) as $r) {
        if ($r['withdraw']>0) $run -= $r['withdraw'];
        elseif ($r['deposit']>0) $run += $r['deposit'];
        $u->execute([$run, $r['sys_id']]);
    }
    $pdo->prepare("UPDATE ac_banking SET balance=? WHERE sys_id=?")->execute([$run, $accountId]);
}