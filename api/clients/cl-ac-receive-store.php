<?php
// PATH: /api/clients/cl-ac-receive-store.php
// v3 — finance_helpers.php use করছে
//   - NPSB → instant
//   - overpayment_action: 'advance' | 'baksheesh'
//   - applyPaymentToSales() — sale-wise receive entries (FIFO)
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
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

/* ===== INPUT ===== */
$clientId          = $data['clientId']          ?? null;
$clientName        = $data['clientName']        ?? null;
$amount            = $data['amount']            ?? 0;
$particular        = $data['particular']        ?? '';
$transactionDate   = $data['transactionDate']   ?? date('Y-m-d H:i:s');
$transferMethod    = strtolower($data['transferMethod'] ?? 'cash');
$accountId         = $data['accountId']         ?? '';
$accountName       = $data['accountName']       ?? '';
$isHistorical      = isset($data['isHistorical']) ? (int)$data['isHistorical'] : 0;
$selectedSaleIds   = $data['selectedSaleIds']   ?? [];
$overpaymentAction = $data['overpayment_action'] ?? 'advance';
$withDiscount      = isset($data['withDiscount']) ? (bool)$data['withDiscount'] : false;
$discountAmount    = (float)($data['discountAmount'] ?? 0);
$discountParticular= $data['discountParticular'] ?? '';
$invoiceId         = $data['invoice_id']        ?? null;

// Cheque
$chequeNo          = $data['chequeNo']          ?? '';
$chequeDate        = $data['chequeDate']        ?? '';
$chequeAccountName = $data['chequeAccountName'] ?? '';
$bankName          = $data['bankName']          ?? '';
// BFTN
$bftnNo            = $data['bftnNo']            ?? '';
$bftnAccountName   = $data['bftnAccountName']   ?? '';
$eftBankName       = $data['eftBankName']       ?? '';
$bftnDate          = $data['bftnDate']          ?? '';

$userName = $_SESSION['user_name'] ?? 'system';

/* ===== VALIDATION ===== */
if (!is_numeric($amount) || $amount <= 0) {
    http_response_code(400); echo json_encode(['success'=>false,'message'=>'Invalid amount']); exit;
}
if (!$clientId) {
    http_response_code(400); echo json_encode(['success'=>false,'message'=>'Client is required']); exit;
}
$cutoff = new DateTime('2026-02-01', new DateTimeZone('Asia/Dhaka'));
$txnDt  = new DateTime($transactionDate, new DateTimeZone('Asia/Dhaka'));
if ($txnDt >= $cutoff && isInstantMethod($transferMethod) && (!$accountId || !$accountName)) {
    http_response_code(400); echo json_encode(['success'=>false,'message'=>'Account is required from 1 Feb 2026']); exit;
}

/* ===== INSTRUMENT ===== */
if (isInstrumentMethod($transferMethod)) {
    // finance_data — remarks এ JSON embed করি (clear API তে parse করবো)
    $financeData = json_encode([
        'client_id'         => $clientId,
        'client_name'       => $clientName,
        'selected_sale_ids' => $selectedSaleIds,
        'invoice_id'        => $invoiceId,
        'overpayment_action'=> $overpaymentAction,
    ]);

    // Instrument details
    $instAccountName = $transferMethod === 'cheque' ? $chequeAccountName : $bftnAccountName;
    $instBankName    = $transferMethod === 'cheque' ? $bankName          : $eftBankName;
    $instDate        = $transferMethod === 'cheque' ? ($chequeDate ?: date('Y-m-d')) : ($bftnDate ?: date('Y-m-d'));
    $instNo          = $transferMethod === 'cheque' ? $chequeNo          : $bftnNo;

    try {
        $instIds  = generateIDs('AIT');
        $instMeta = buildMetaData(null, $userName);

        $pdo->prepare("
            INSERT INTO ac_instrument_tracking
            (uuid, sys_id, instrument_type, trnx_type, instrument_no, payment_to,
             account_name, bank_name, instrument_date,
             amount, related_type, related_from, related_to,
             status, date, remarks, finance_data, meta_data)
            VALUES
            (?, ?, ?, 'credit', ?, 'client',
             ?, ?, ?,
             ?, 'received', ?, ?,
             'pending', ?, ?, ?, ?)
        ")->execute([
            $instIds['uuid'],
            $instIds['sys_id'],
            strtoupper($transferMethod),
            $instNo,
            $instAccountName,
            $instBankName,
            $instDate,
            $amount,
            $clientId . ' || ' . ($clientName ?: $clientId),
            $accountId . ' || ' . ($accountName ?: $accountId),
            date('Y-m-d'),
            $particular,
            $financeData,
            $instMeta
        ]);

        echo json_encode([
            'success'    => true,
            'message'    => 'Instrument recorded. Pending clearance.',
            'instrument' => [
                'sys_id'          => $instIds['sys_id'],
                'instrument_type' => strtoupper($transferMethod),
                'instrument_no'   => $instNo,
                'amount'          => $amount,
                'status'          => 'pending'
            ]
        ]);

    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Instrument store failed: ' . $e->getMessage()]);
    }
    exit;
}

/* ===== MAIN TRANSACTION ===== */
try {
    $pdo->beginTransaction();

    $receiveAmount = (float)$amount;

    /* 1. Sale ids থেকে remaining calculate */
    $totalRemaining = 0;
    $hasSelection   = !empty($selectedSaleIds);
    $isAdvance      = !$hasSelection && !$invoiceId;

    // Invoice mode → sale ids নিই
    if ($invoiceId && !$hasSelection) {
        $invR = $pdo->prepare("SELECT financial_entry_ids FROM invoices WHERE sys_id = ?");
        $invR->execute([$invoiceId]);
        $inv = $invR->fetch(PDO::FETCH_ASSOC);
        if ($inv && !empty($inv['financial_entry_ids'])) {
            $selectedSaleIds = json_decode($inv['financial_entry_ids'], true) ?: [];
            $hasSelection = !empty($selectedSaleIds);
            $isAdvance    = false;
        }
    }

    if ($hasSelection) {
        $ph = implode(',', array_fill(0, count($selectedSaleIds), '?'));
        $sr = $pdo->prepare("SELECT sys_id, amount, is_paid, is_partial, ref FROM financial_entries WHERE sys_id IN ($ph)");
        $sr->execute(array_values($selectedSaleIds));
        foreach ($sr->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $totalRemaining += getSaleRemaining($pdo, $row);
        }
    }

    $overpayAmount = ($hasSelection && $receiveAmount > $totalRemaining + 0.009)
        ? $receiveAmount - $totalRemaining : 0;

    /* 2. Bank update */
    $accR = $pdo->prepare("SELECT balance FROM ac_banking WHERE sys_id = ? FOR UPDATE");
    $accR->execute([$accountId]);
    $acc = $accR->fetch(PDO::FETCH_ASSOC);
    if (!$acc) throw new Exception('Account not found');
    $curBal = (float)$acc['balance'];

    $openR = $pdo->prepare("SELECT MIN(date) FROM ac_banking_stmts WHERE ledger_db_id = ? AND particular = 'Opening Balance'");
    $openR->execute([$accountId]);
    $openDate = $openR->fetchColumn();
    if ($openDate && $transactionDate < $openDate) $isHistorical = 1;

    $daysDiff = (int)$pdo->query("SELECT DATEDIFF(NOW(), '{$transactionDate}')")->fetchColumn();
    if (!$isHistorical && $daysDiff > 10) throw new Exception('Backdated entry limit is 10 days.');

    $newBal = $isHistorical ? $curBal : ($curBal + $receiveAmount);
    if (!$isHistorical) {
        $pdo->prepare("UPDATE ac_banking SET balance = ? WHERE sys_id = ?")->execute([$newBal, $accountId]);
    }

    /* 3. ac_banking_stmts — ref column এ পরে receive sys_id UPDATE করবো */
    $stmtIds  = generateIDs('ac_banking_stmts');
    $stmtMeta = buildMetaData(null, $userName);
    $pdo->prepare("
        INSERT INTO ac_banking_stmts
        (uuid, sys_id, ledger_db_id, name, date, particular,
         withdraw, deposit, balance, transfer_method, related_type, meta_data, is_historical)
        VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, ?, 1, ?, ?)
    ")->execute([
        $stmtIds['uuid'], $stmtIds['sys_id'],
        $accountId, $accountName, $transactionDate, $particular,
        $receiveAmount, $newBal, $transferMethod, $stmtMeta, $isHistorical
    ]);
    $stmtSysId = $stmtIds['sys_id'];

    /* 4. Sale-wise receive entries via helper (FIFO, per-sale) */
    // applyPaymentToSales আগে call করি — generateIDsSafe এখান থেকেই শুরু হবে
    $rcvEntryIds = [];
    $appliedAmt  = 0;
    if ($hasSelection && !empty($selectedSaleIds)) {
        $clearAmt = min($receiveAmount, $totalRemaining);
        $res = applyPaymentToSales(
            $pdo, $selectedSaleIds, $clearAmt,
            $clientId, $clientName, $transactionDate, $particular, $userName
        );
        $rcvEntryIds = $res['receive_entry_ids'];
        $appliedAmt  = $res['applied'];
    }

    // Main receive entry — শুধু invoice mode এ (no sale selection) একটা summary entry
    // hasSelection থাকলে applyPaymentToSales() ই sale-wise entries করে দিয়েছে — duplicate হবে না
    if (!$isAdvance && !$hasSelection && $invoiceId) {
        $mainRcvIds = generateIDsSafe('financial_entries');
        $rcvMeta2   = buildMetaData(null, $userName);
        $pdo->prepare("
            INSERT INTO financial_entries
            (uuid, sys_id, user_sys_id, user_name, user_type,
             date, purpose, type, related_type,
             is_paid, is_partial, is_discounted, amount, ref, meta_data)
            VALUES (?, ?, ?, ?, 'client', ?, ?, 'credit', 3, 1, 0, 0, ?, ?, ?)
        ")->execute([
            $mainRcvIds['uuid'], $mainRcvIds['sys_id'],
            $clientId, $clientName,
            $transactionDate, $particular,
            min($receiveAmount, $totalRemaining),
            $invoiceId,
            $rcvMeta2
        ]);
        $rcvEntryIds[] = $mainRcvIds['sys_id'];

        // ac_banking_stmts.ref update
        $pdo->prepare("UPDATE ac_banking_stmts SET ref = ? WHERE sys_id = ?")
            ->execute([$mainRcvIds['sys_id'], $stmtSysId]);
    } elseif (!$isAdvance && !empty($rcvEntryIds)) {
        // ac_banking_stmts.ref এ প্রথম receive entry sys_id বসাই
        $pdo->prepare("UPDATE ac_banking_stmts SET ref = ? WHERE sys_id = ?")
            ->execute([$rcvEntryIds[0], $stmtSysId]);
    }

    /* 5. Discount */
    $discountSysId = null;
    if ($withDiscount && $discountAmount > 0 && $hasSelection) {
        $discountSysId = insertDiscountEntry(
            $pdo, $discountAmount, 'client',
            $clientId, $clientName, $transactionDate,
            $discountParticular ?: ('Discount: ' . $particular),
            $selectedSaleIds, $userName
        );
    }

    /* 6. Advance (no selection) */
    $advanceSysId = null;
    if ($isAdvance) {
        $advIds  = generateIDs('financial_entries');
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
            $transactionDate, 'Advance: ' . $particular,
            $receiveAmount, $stmtSysId, $advMeta
        ]);
        $advanceSysId = $advIds['sys_id'];
    }

    /* 7. Overpayment → Advance or Baksheesh */
    $overpayEntrySysId = null;
    if ($overpayAmount > 0.009) {
        $refId = $invoiceId ?? ($stmtSysId);
        $overpayEntrySysId = handleOverpayment(
            $pdo, $overpaymentAction, $overpayAmount,
            'client', $clientId, $clientName,
            $transactionDate, $refId, $userName
        );
    }

    /* 8. Backdated recalc */
    if (!$isHistorical && $daysDiff > 0) {
        recalculateBalances($pdo, $accountId, $transactionDate);
    }

    $pdo->commit();

    echo json_encode([
        'success'            => true,
        'message'            => $isHistorical ? 'Historical entry recorded'
            : ($isAdvance ? 'Advance recorded (৳' . number_format($receiveAmount, 2) . ')'
                : ($overpayAmount > 0.009
                    ? 'Payment received. Overpayment ৳' . number_format($overpayAmount, 2) . ' → ' . ucfirst($overpaymentAction)
                    : 'Payment received successfully')),
        'is_historical'      => $isHistorical,
        'is_advance'         => $isAdvance,
        'overpayment_amount' => $overpayAmount,
        'overpayment_action' => $overpaymentAction,
        'data' => [
            'bank_stmt_id'      => $stmtSysId,
            'receive_entry_ids' => $rcvEntryIds,
            'discount_entry_id' => $discountSysId,
            'advance_entry_id'  => $advanceSysId,
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