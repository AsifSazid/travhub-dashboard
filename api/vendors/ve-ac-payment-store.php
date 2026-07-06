<?php
// PATH: /api/vendors/ve-ac-payment-store.php
// v3 — finance_helpers.php use করছে
//   - NPSB → instant
//   - overpayment_action: 'advance' | 'baksheesh'
//   - use_vendor_advance + vendor_advance_amount
//   - applyPaymentToPurchases() — purchase-wise payment entries (FIFO)
//   - Discount support (rt=5)
//   - Cheque/BFTN-EFT → instrument only

session_start();
require '../../server/db_connection.php';
require '../../server/uuid_with_system_id_generator.php';
require '../../server/generate_meta_data.php';
require '../../server/finance_helpers.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

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
$amount              = $data['amount']              ?? 0;
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

// Cheque
$chequeNo            = $data['chequeNo']            ?? '';
$chequeDate          = $data['chequeDate']          ?? '';
$chequeAccountName   = $data['chequeAccountName']   ?? '';
$bankName            = $data['bankName']            ?? '';
// BFTN
$bftnNo              = $data['bftnNo']              ?? '';
$bftnAccountName     = $data['bftnAccountName']     ?? '';
$eftBankName         = $data['eftBankName']         ?? '';
$bftnDate            = $data['bftnDate']            ?? '';

$userName = $_SESSION['user_name'] ?? 'system';

/* ===== VALIDATION ===== */
if (!is_numeric($amount) || $amount <= 0) {
    http_response_code(400); echo json_encode(['success'=>false,'message'=>'Invalid amount']); exit;
}
if (!$vendorId) {
    http_response_code(400); echo json_encode(['success'=>false,'message'=>'Vendor is required']); exit;
}
$cutoff = new DateTime('2026-02-01', new DateTimeZone('Asia/Dhaka'));
$txnDt  = new DateTime($transactionDate, new DateTimeZone('Asia/Dhaka'));
if ($txnDt >= $cutoff && isInstantMethod($transferMethod) && (!$accountId || !$accountName)) {
    http_response_code(400); echo json_encode(['success'=>false,'message'=>'Account is required from 1 Feb 2026']); exit;
}

/* ===== INSTRUMENT ===== */
if (isInstrumentMethod($transferMethod)) {
    $instUrl = "https://travhub.com.bd/travhub-admin/api/acc-instrument-tracking/store.php";
    $instPayload = [
        "instrument_type" => strtoupper($transferMethod),
        "instrument_no"   => $transferMethod === 'cheque' ? $chequeNo : $bftnNo,
        "account_name"    => $transferMethod === 'cheque' ? $chequeAccountName : $bftnAccountName,
        "bank_name"       => $transferMethod === 'cheque' ? $bankName : $eftBankName,
        "instrument_date" => $transferMethod === 'cheque' ? ($chequeDate ?: date('Y-m-d')) : ($bftnDate ?: date('Y-m-d')),
        "status"          => 'pending',
        "date"            => date('Y-m-d'),
        "remarks"         => $particular,
        "related_type"    => 'payment',
        "related_from"    => $accountId . ' || ' . $accountName,
        "related_to"      => $vendorId . ' || ' . $vendorName,
        "amount"          => $amount
    ];
    $ch = curl_init($instUrl);
    curl_setopt_array($ch, [CURLOPT_POST=>true, CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_HTTPHEADER=>['Content-Type: application/json'],
        CURLOPT_POSTFIELDS=>json_encode($instPayload), CURLOPT_TIMEOUT=>10]);
    $resp = curl_exec($ch); $err = curl_error($ch); curl_close($ch);
    if ($err) { http_response_code(500); echo json_encode(['success'=>false,'message'=>'Instrument API failed']); exit; }
    $res = json_decode($resp, true);
    if (!$res || !$res['success']) { http_response_code(500); echo json_encode(['success'=>false,'message'=>'Instrument store failed']); exit; }
    echo json_encode(['success'=>true,'message'=>'Instrument recorded. Pending clearance.','instrument'=>$res['data']]);
    exit;
}

/* ===== MAIN TRANSACTION ===== */
try {
    $pdo->beginTransaction();

    $paymentAmount = (float)$amount;

    /* 1. Purchase remaining calculate */
    $totalRemaining  = 0;
    $hasSelection    = !empty($selectedPurchaseIds);
    $isAdvance       = !$hasSelection;

    if ($hasSelection) {
        $ph = implode(',', array_fill(0, count($selectedPurchaseIds), '?'));
        $pr = $pdo->prepare("SELECT sys_id, amount, is_paid, is_partial, ref FROM financial_entries WHERE sys_id IN ($ph)");
        $pr->execute(array_values($selectedPurchaseIds));
        foreach ($pr->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $totalRemaining += getPurchaseRemaining($pdo, $row);
        }
    }

    /* 2. Vendor Advance consume */
    $advanceUsed    = 0;
    $advUsedEntryId = null;
    if ($useVendorAdvance && $vendorAdvanceAmount > 0 && $hasSelection) {
        // Net available advance
        $crStmt = $pdo->prepare("SELECT sys_id, amount FROM financial_entries WHERE user_sys_id=? AND user_type='vendor' AND type='debit' AND related_type=6 ORDER BY date ASC, id ASC");
        $crStmt->execute([$vendorId]);
        $advEntries = $crStmt->fetchAll(PDO::FETCH_ASSOC);

        $drStmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM financial_entries WHERE user_sys_id=? AND user_type='vendor' AND type='credit' AND related_type=6");
        $drStmt->execute([$vendorId]);
        $alreadyUsed    = (float)$drStmt->fetchColumn();
        $totalAdvDebit  = array_sum(array_column($advEntries, 'amount'));
        $netAvailable   = max(0, $totalAdvDebit - $alreadyUsed);
        $toConsume      = min($vendorAdvanceAmount, $netAvailable);
        $remaining      = $toConsume;

        foreach ($advEntries as $adv) {
            if ($remaining <= 0.009) break;
            $useAmt = min((float)$adv['amount'], $remaining);
            // Advance used credit entry (rt=6) — vendor advance consume
            $advIds  = generateIDs('financial_entries');
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
                $transactionDate, 'Advance Used: Purchase payment',
                $useAmt, $adv['sys_id'], $advMeta
            ]);
            $advanceUsed += $useAmt;
            $remaining   -= $useAmt;
        }

        // Advance used এর effective payment — purchase entries এ apply করি
        if ($advanceUsed > 0.009) {
            $advRes = applyPaymentToPurchases(
                $pdo, $selectedPurchaseIds, $advanceUsed,
                $vendorId, $vendorName, $transactionDate,
                'Vendor Advance Used: ' . $particular, $userName
            );
            // totalRemaining কমাই
            $totalRemaining = max(0, $totalRemaining - $advanceUsed);
        }
    }

    /* 3. Overpayment */
    $cashPayment   = $paymentAmount; // bank থেকে যাচ্ছে
    $clearWithCash = $hasSelection ? min($cashPayment, $totalRemaining) : $cashPayment;
    $overpayAmount = ($hasSelection && $cashPayment > $totalRemaining + 0.009)
        ? $cashPayment - $totalRemaining : 0;

    /* 4. Bank update */
    $accR = $pdo->prepare("SELECT balance FROM ac_banking WHERE sys_id = ? FOR UPDATE");
    $accR->execute([$accountId]);
    $acc = $accR->fetch(PDO::FETCH_ASSOC);
    if (!$acc) throw new Exception('Account not found');
    $curBal = (float)$acc['balance'];

    if (!$isHistorical && $curBal < $paymentAmount) throw new Exception('Insufficient balance');

    $openR = $pdo->prepare("SELECT MIN(date) FROM ac_banking_stmts WHERE ledger_db_id=? AND particular='Opening Balance'");
    $openR->execute([$accountId]);
    $openDate = $openR->fetchColumn();
    if ($openDate && $transactionDate < $openDate) $isHistorical = 1;

    $daysDiff = (int)$pdo->query("SELECT DATEDIFF(NOW(), '{$transactionDate}')")->fetchColumn();
    if (!$isHistorical && $daysDiff > 10) throw new Exception('Backdated entry limit is 10 days.');

    $newBal = $isHistorical ? $curBal : ($curBal - $paymentAmount);
    if (!$isHistorical) {
        $pdo->prepare("UPDATE ac_banking SET balance=? WHERE sys_id=?")->execute([$newBal, $accountId]);
    }

    // financial_entries UUID আগেই generate — ac_banking_stmts.ref এ বসাবো
    $mainPayIds = generateIDs('financial_entries');

    /* 5. ac_banking_stmts */
    $stmtIds  = generateIDs('ac_banking_stmts');
    $stmtMeta = buildMetaData(null, $userName);
    $pdo->prepare("
        INSERT INTO ac_banking_stmts
        (uuid, sys_id, ledger_db_id, name, date, particular,
         withdraw, deposit, balance, transfer_method, related_type, meta_data, is_historical, ref)
        VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, 2, ?, ?, ?)
    ")->execute([
        $stmtIds['uuid'], $stmtIds['sys_id'],
        $accountId, $accountName, $transactionDate, $particular,
        $paymentAmount, $newBal, $transferMethod, $stmtMeta, $isHistorical,
        $mainPayIds['sys_id']
    ]);
    $stmtSysId = $stmtIds['sys_id'];

    /* 6. Purchase-wise payment entries via helper (FIFO, per-purchase) */
    $payEntryIds = [];
    $appliedAmt  = 0;
    if ($hasSelection && $clearWithCash > 0.009) {
        $res = applyPaymentToPurchases(
            $pdo, $selectedPurchaseIds, $clearWithCash,
            $vendorId, $vendorName, $transactionDate, $particular, $userName
        );
        $payEntryIds = $res['payment_entry_ids'];
        $appliedAmt  = $res['applied'];
    }

    // Main summary payment entry (rt=4) — ac_banking_stmts.ref এ এটার sys_id বসানো হয়েছে
    if (!$isAdvance) {
        $isPartial  = $hasSelection && ($paymentAmount < $totalRemaining - 0.009);
        $payEntAmt  = $hasSelection ? min($paymentAmount, $totalRemaining) : $paymentAmount;
        $payRef     = !empty($selectedPurchaseIds) ? json_encode(array_values($selectedPurchaseIds)) : $stmtSysId;
        $payMeta2   = buildMetaData(null, $userName);
        $pdo->prepare("
            INSERT INTO financial_entries
            (uuid, sys_id, user_sys_id, user_name, user_type,
             date, purpose, type, related_type,
             is_paid, is_partial, is_discounted, amount, ref, meta_data)
            VALUES (?, ?, ?, ?, 'vendor', ?, ?, 'debit', 4, 1, ?, 0, ?, ?, ?)
        ")->execute([
            $mainPayIds['uuid'], $mainPayIds['sys_id'],
            $vendorId, $vendorName,
            $transactionDate, $particular,
            $isPartial ? 1 : 0,
            $payEntAmt,
            $payRef,
            $payMeta2
        ]);
        $payEntryIds[] = $mainPayIds['sys_id'];
    }

    /* 7. Discount */
    $discountSysId = null;
    if ($withDiscount && $discountAmount > 0 && $hasSelection) {
        $discountSysId = insertDiscountEntry(
            $pdo, $discountAmount, 'vendor',
            $vendorId, $vendorName, $transactionDate,
            $discountParticular ?: ('Discount: ' . $particular),
            $selectedPurchaseIds, $userName
        );
    }

    /* 8. Advance to Vendor (no selection) */
    $advanceSysId = null;
    if ($isAdvance) {
        $advIds  = generateIDs('financial_entries');
        $advMeta = buildMetaData(null, $userName);
        $pdo->prepare("
            INSERT INTO financial_entries
            (uuid, sys_id, user_sys_id, user_name, user_type,
             date, purpose, type, related_type,
             is_paid, is_partial, is_discounted, amount, ref, meta_data)
            VALUES (?, ?, ?, ?, 'vendor', ?, ?, 'debit', 6, 1, 0, 0, ?, ?, ?)
        ")->execute([
            $advIds['uuid'], $advIds['sys_id'],
            $vendorId, $vendorName,
            $transactionDate, 'Advance to Vendor: ' . $particular,
            $paymentAmount, $stmtSysId, $advMeta
        ]);
        $advanceSysId = $advIds['sys_id'];
    }

    /* 9. Overpayment → Advance or Baksheesh */
    $overpayEntrySysId = null;
    if ($overpayAmount > 0.009) {
        $overpayEntrySysId = handleOverpayment(
            $pdo, $overpaymentAction, $overpayAmount,
            'vendor', $vendorId, $vendorName,
            $transactionDate, $stmtSysId, $userName
        );
    }

    /* 10. Backdated recalc */
    if (!$isHistorical && $daysDiff > 0) {
        recalculateBalances($pdo, $accountId, $transactionDate);
    }

    $pdo->commit();

    echo json_encode([
        'success'            => true,
        'message'            => $isHistorical ? 'Historical entry recorded'
            : ($isAdvance ? 'Advance to vendor recorded (৳' . number_format($paymentAmount, 2) . ')'
                : ($overpayAmount > 0.009
                    ? 'Payment recorded. Overpayment ৳' . number_format($overpayAmount, 2) . ' → ' . ucfirst($overpaymentAction)
                    : 'Payment recorded successfully')),
        'is_historical'      => $isHistorical,
        'is_advance'         => $isAdvance,
        'advance_used'       => $advanceUsed,
        'overpayment_amount' => $overpayAmount,
        'overpayment_action' => $overpaymentAction,
        'data' => [
            'bank_stmt_id'       => $stmtSysId,
            'payment_entry_ids'  => $payEntryIds,
            'discount_entry_id'  => $discountSysId,
            'advance_entry_id'   => $advanceSysId,
            'overpay_entry_id'   => $overpayEntrySysId,
            'new_balance'        => $newBal,
            'applied_amount'     => $appliedAmt,
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