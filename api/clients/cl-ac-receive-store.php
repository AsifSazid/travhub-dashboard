<?php
// PATH: /api/clients/cl-ac-receive-store.php
// Major changes:
//   - Selected sale entries এর বিপরীতে payment link হচ্ছে (Option C)
//   - is_paid, is_partial, is_discounted logic যোগ হয়েছে
//   - ref column এ JSON array
//   - 3 case: full payment, partial, discount-to-close
//   - Discount entry same transaction এ insert হচ্ছে
session_start();

require '../../server/db_connection.php';
require '../../server/uuid_with_system_id_generator.php';
require '../../server/generate_meta_data.php';

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

/* ================= INPUT ================= */
$clientId        = $data['clientId']        ?? null;
$clientName      = $data['clientName']      ?? null;
$amount          = $data['amount']          ?? 0;
$particular      = $data['particular']      ?? '';
$transactionDate = $data['transactionDate'] ?? date('Y-m-d H:i:s');
$transferMethod  = $data['transferMethod']  ?? 'cash';
$accountId       = $data['accountId']       ?? '';
$accountName     = $data['accountName']     ?? '';
$isHistorical    = isset($data['isHistorical']) ? (int)$data['isHistorical'] : 0;

// Selected sale sys_ids — কোন sale গুলোর বিপরীতে payment আসছে
$selectedSaleIds    = $data['selectedSaleIds']    ?? [];

// Discount-to-close
$withDiscount       = isset($data['withDiscount']) ? (bool)$data['withDiscount'] : false;
$discountAmount     = $data['discountAmount']     ?? 0;
$discountParticular = $data['discountParticular'] ?? '';

// Cheque/BFTN
$chequeNo          = $data['chequeNo']          ?? '';
$chequeDate        = $data['chequeDate']        ?? '';
$chequeAccountName = $data['chequeAccountName'] ?? '';
$bankName          = $data['bankName']          ?? '';
$accountNameEFT    = $data['bftnAccountName']   ?? '';
$eftBankName       = $data['eftBankName']       ?? '';
$bftnDate          = $data['bftnDate']          ?? '';

/* ================= VALIDATION ================= */
if (!is_numeric($amount) || $amount <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid amount']);
    exit;
}
if (!$clientId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Client is required']);
    exit;
}
if ($withDiscount && (!is_numeric($discountAmount) || $discountAmount <= 0)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid discount amount']);
    exit;
}

$cutoffDate = new DateTime('2026-02-01 00:00:00', new DateTimeZone('Asia/Dhaka'));
$txnDateObj = new DateTime($transactionDate, new DateTimeZone('Asia/Dhaka'));
if ($txnDateObj >= $cutoffDate && (!$accountId || !$accountName)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Account is required from 1 Feb 2026']);
    exit;
}

/* ================= INSTRUMENT HANDLING ================= */
$instrumentMethods = ['cheque', 'bftn-eft'];
if (in_array($transferMethod, $instrumentMethods, true)) {
    $instrumentApiUrl = "https://travhub.com.bd/travhub-admin/api/acc-instrument-tracking/store.php";

    if ($transferMethod === 'cheque') {
        $instAccName  = $chequeAccountName;
        $instBankName = $bankName;
        $instDate     = $chequeDate ?: date('Y-m-d');
        $instNo       = $chequeNo;
    } else {
        $instAccName  = $accountNameEFT;
        $instBankName = $eftBankName;
        $instDate     = $bftnDate ?: date('Y-m-d');
        $instNo       = $data['bftnNo'] ?? '';
    }

    $instrumentPayload = [
        "instrument_type" => strtoupper($transferMethod),
        "instrument_no"   => $instNo,
        "account_name"    => $instAccName,
        "bank_name"       => $instBankName,
        "instrument_date" => $instDate,
        "status"          => 'pending',
        "date"            => date('Y-m-d'),
        "remarks"         => $particular,
        "related_type"    => 'received',
        "related_from"    => $clientId . ' || ' . $clientName,
        "related_to"      => $accountId . ' || ' . $accountName,
        "amount"          => $amount
    ];

    $ch = curl_init($instrumentApiUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode($instrumentPayload),
        CURLOPT_TIMEOUT        => 10
    ]);
    $response = curl_exec($ch);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($error) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Instrument API failed', 'error' => $error]);
        exit;
    }
    $result = json_decode($response, true);
    if (!$result || !$result['success']) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Instrument store failed']);
        exit;
    }
    echo json_encode([
        'success'    => true,
        'message'    => 'Instrument recorded. Transaction pending clearance.',
        'instrument' => $result['data']
    ]);
    exit;
}

/* ================= MAIN TRANSACTION ================= */
try {
    $pdo->beginTransaction();

    /* ===== 1. Selected sale entries fetch ===== */
    $selectedSaleData     = [];
    $totalSaleAmount      = 0;
    $totalRemainingAmount = 0;

    if (!empty($selectedSaleIds)) {
        $placeholders = implode(',', array_fill(0, count($selectedSaleIds), '?'));
        $saleStmt = $pdo->prepare("
            SELECT sys_id, amount, is_paid, is_partial, is_discounted, ref
            FROM financial_entries
            WHERE sys_id IN ($placeholders)
            AND user_sys_id = ?
            AND user_type = 'client'
            AND type = 'debit'
            AND related_type = 1
        ");
        $saleStmt->execute([...$selectedSaleIds, $clientId]);
        $selectedSaleData = $saleStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($selectedSaleData as $sale) {
            $totalSaleAmount += (float)$sale['amount'];

            // Remaining = amount - previously received/discounted
            $remaining = (float)$sale['amount'];
            if (!empty($sale['ref'])) {
                $refIds = json_decode($sale['ref'], true);
                if (is_array($refIds) && !empty($refIds)) {
                    $ph = implode(',', array_fill(0, count($refIds), '?'));
                    $prevRcv = $pdo->prepare("
                        SELECT COALESCE(SUM(amount), 0) FROM financial_entries
                        WHERE sys_id IN ($ph) AND type = 'credit' AND related_type = 3
                    ");
                    $prevRcv->execute($refIds);
                    $remaining -= (float)$prevRcv->fetchColumn();
                    $prevDisc = $pdo->prepare("
                        SELECT COALESCE(SUM(amount), 0) FROM financial_entries
                        WHERE sys_id IN ($ph) AND type = 'credit' AND related_type = 5
                    ");
                    $prevDisc->execute($refIds);
                    $remaining -= (float)$prevDisc->fetchColumn();
                }
            }
            $totalRemainingAmount += max(0, $remaining);
        }
    }

    /* ===== 2. Payment type determine ===== */
    $receiveAmount = (float)$amount;
    $discountAmt   = $withDiscount ? (float)$discountAmount : 0;
    $totalCoverage = $receiveAmount + $discountAmt;

    $hasSelection  = !empty($selectedSaleData);

    // Sale select না করলে → pure advance (related_type=6)
    $isAdvance     = !$hasSelection;

    // remaining amount দিয়ে compare — overpayment হলেও full payment mark হবে
    $isFullPayment = !$hasSelection || ($totalCoverage >= $totalRemainingAmount - 0.01);
    $isPartial     = $hasSelection && !$isFullPayment && !$withDiscount;
    $isDiscounted  = $withDiscount && $hasSelection;

    $receivePaid       = ($isFullPayment || $isDiscounted) ? 1 : 0;
    $receivePartial    = $isPartial ? 1 : 0;
    $receiveDiscounted = $isDiscounted ? 1 : 0;

    // Overpayment — sale select করে বেশি দিলে advance entry হবে (related_type=6)
    $overpaymentAmount = 0;
    if ($hasSelection && $receiveAmount > $totalRemainingAmount + 0.01) {
        $overpaymentAmount = $receiveAmount - $totalRemainingAmount;
    }

    /* ===== 3. Bank: balance update ===== */
    $accStmt = $pdo->prepare("SELECT balance FROM ac_banking WHERE sys_id = ? FOR UPDATE");
    $accStmt->execute([$accountId]);
    $account = $accStmt->fetch(PDO::FETCH_ASSOC);
    if (!$account) throw new Exception('Account not found');
    $currentBalance = (float)$account['balance'];

    $openingStmt = $pdo->prepare("
        SELECT MIN(date) as opening_date FROM ac_banking_stmts
        WHERE ledger_db_id = :aid AND particular = 'Opening Balance'
    ");
    $openingStmt->execute([':aid' => $accountId]);
    $openingDate = $openingStmt->fetch(PDO::FETCH_ASSOC)['opening_date'] ?? null;
    if ($openingDate && $transactionDate < $openingDate) $isHistorical = 1;

    $dateStmt = $pdo->prepare("SELECT DATEDIFF(NOW(), :txn_date) as days_diff");
    $dateStmt->execute([':txn_date' => $transactionDate]);
    $daysDiff = (int)$dateStmt->fetch(PDO::FETCH_ASSOC)['days_diff'];
    if (!$isHistorical && $daysDiff > 10) throw new Exception('Backdated entry limit is 10 days.');

    $newBalance = $isHistorical ? $currentBalance : ($currentBalance + $receiveAmount);

    if (!$isHistorical) {
        $pdo->prepare("UPDATE ac_banking SET balance = :bal WHERE sys_id = :id")
            ->execute([':bal' => $newBalance, ':id' => $accountId]);
    }

    // financial_entries UUID আগেই generate করি — ac_banking_stmts.ref এ বসাবো
    $receiveUUIDs = generateIDs('financial_entries');

    // ac_banking_stmts
    $stmtUUIDs = generateIDs('ac_banking_stmts');
    $stmtMeta  = buildMetaData(null, $_SESSION['user_name'] ?? 'system');
    $pdo->prepare("
        INSERT INTO ac_banking_stmts
        (uuid, sys_id, ledger_db_id, name, date, particular,
         withdraw, deposit, balance, transfer_method, related_type, meta_data, is_historical, ref)
        VALUES
        (:uuid, :sys_id, :ledger, :name, :date, :particular,
         0, :deposit, :balance, :method, 1, :meta, :hist, :ref)
    ")->execute([
        ':uuid'       => $stmtUUIDs['uuid'],
        ':sys_id'     => $stmtUUIDs['sys_id'],
        ':ledger'     => $accountId,
        ':name'       => $accountName,
        ':date'       => $transactionDate,
        ':particular' => $particular,
        ':deposit'    => $receiveAmount,
        ':balance'    => $newBalance,
        ':method'     => $transferMethod,
        ':meta'       => $stmtMeta,
        ':hist'       => $isHistorical,
        ':ref'        => $receiveUUIDs['sys_id']  // financial_entries sys_id
    ]);
    $stmtSysId = $stmtUUIDs['sys_id'];

    /* ===== 4. financial_entries: receive row ===== */
    // UUID already generated above
    $receiveMeta     = buildMetaData(null, $_SESSION['user_name'] ?? 'system');
    $receiveRef      = !empty($selectedSaleIds) ? json_encode($selectedSaleIds) : $stmtSysId;
    // Advance হলে related_type=6, regular receive হলে related_type=3
    $receiveRelatedType = $isAdvance ? 6 : 3;
    $receivePurpose  = $isAdvance ? ('Advance: ' . $particular) : $particular;

    // Receive entry amount = sale amount পর্যন্তই (overpayment বাদ)
    // Bank এ full amount যাবে, কিন্তু financial_entries এ শুধু sale clear amount
    $receiveEntryAmount = $hasSelection
        ? min($receiveAmount, $totalRemainingAmount)  // sale এর remaining পর্যন্ত
        : $receiveAmount;                              // advance/general = full amount

    $pdo->prepare("
        INSERT INTO financial_entries
        (uuid, sys_id, user_sys_id, user_name, user_type,
         date, purpose, type, related_type,
         is_paid, is_partial, is_discounted,
         amount, ref, meta_data)
        VALUES
        (:uuid, :sys_id, :user_sys_id, :user_name, 'client',
         :date, :purpose, 'credit', :related_type,
         :is_paid, :is_partial, :is_discounted,
         :amount, :ref, :meta)
    ")->execute([
        ':uuid'          => $receiveUUIDs['uuid'],
        ':sys_id'        => $receiveUUIDs['sys_id'],
        ':user_sys_id'   => $clientId,
        ':user_name'     => $clientName,
        ':date'          => $transactionDate,
        ':purpose'       => $receivePurpose,
        ':related_type'  => $receiveRelatedType,
        ':is_paid'       => $receivePaid,
        ':is_partial'    => $receivePartial,
        ':is_discounted' => $receiveDiscounted,
        ':amount'        => $receiveEntryAmount,  // sale amount পর্যন্তই
        ':ref'           => $receiveRef,
        ':meta'          => $receiveMeta
    ]);
    $receiveSysId = $receiveUUIDs['sys_id'];

    /* ===== 5. Discount entry (withDiscount=true হলে) ===== */
    $discountSysId = null;
    if ($isDiscounted && $discountAmt > 0) {
        $discountUUIDs = generateIDs('financial_entries');
        $discountMeta  = buildMetaData(null, $_SESSION['user_name'] ?? 'system');
        $discountRef   = json_encode([...$selectedSaleIds, $receiveSysId]);

        $pdo->prepare("
            INSERT INTO financial_entries
            (uuid, sys_id, user_sys_id, user_name, user_type,
             date, purpose, type, related_type,
             is_paid, is_partial, is_discounted,
             amount, ref, meta_data)
            VALUES
            (:uuid, :sys_id, :user_sys_id, :user_name, 'client',
             :date, :purpose, 'credit', 5,
             1, 0, 1,
             :amount, :ref, :meta)
        ")->execute([
            ':uuid'        => $discountUUIDs['uuid'],
            ':sys_id'      => $discountUUIDs['sys_id'],
            ':user_sys_id' => $clientId,
            ':user_name'   => $clientName,
            ':date'        => $transactionDate,
            ':purpose'     => $discountParticular ?: ('Discount: ' . $particular),
            ':amount'      => $discountAmt,
            ':ref'         => $discountRef,
            ':meta'        => $discountMeta
        ]);
        $discountSysId = $discountUUIDs['sys_id'];
    }

    /* ===== 6. Sale entries update ===== */
    if (!empty($selectedSaleData)) {
        $saleRefArray = [$receiveSysId];
        if ($discountSysId) $saleRefArray[] = $discountSysId;
        $saleRef = json_encode($saleRefArray);

        $updateSaleStmt = $pdo->prepare("
            UPDATE financial_entries
            SET is_paid       = :is_paid,
                is_partial    = :is_partial,
                is_discounted = :is_discounted,
                ref           = :ref
            WHERE sys_id = :sys_id
        ");

        foreach ($selectedSaleData as $sale) {
            $updateSaleStmt->execute([
                ':is_paid'       => $receivePaid,
                ':is_partial'    => $receivePartial,
                ':is_discounted' => $receiveDiscounted,
                ':ref'           => $saleRef,
                ':sys_id'        => $sale['sys_id']
            ]);
        }
    }

    /* ===== 7. Receive ref update — discount id যোগ ===== */
    if ($discountSysId) {
        $updatedReceiveRef = json_encode([...$selectedSaleIds, $discountSysId]);
        $pdo->prepare("UPDATE financial_entries SET ref = :ref WHERE sys_id = :sid")
            ->execute([':ref' => $updatedReceiveRef, ':sid' => $receiveSysId]);
    }

    /* ===== 8. Overpayment → Advance entry (related_type=6) ===== */
    $advanceSysId = null;
    if ($hasSelection && $overpaymentAmount > 0.01) {
        $advUUIDs = generateIDs('financial_entries');
        $advMeta  = buildMetaData(null, $_SESSION['user_name'] ?? 'system');
        $pdo->prepare("
            INSERT INTO financial_entries
            (uuid, sys_id, user_sys_id, user_name, user_type,
             date, purpose, type, related_type,
             is_paid, is_partial, is_discounted,
             amount, ref, meta_data)
            VALUES
            (:uuid, :sys_id, :user_sys_id, :user_name, 'client',
             :date, :purpose, 'credit', 6,
             0, 0, 0,
             :amount, :ref, :meta)
        ")->execute([
            ':uuid'        => $advUUIDs['uuid'],
            ':sys_id'      => $advUUIDs['sys_id'],
            ':user_sys_id' => $clientId,
            ':user_name'   => $clientName,
            ':date'        => $transactionDate,
            ':purpose'     => 'Advance: ' . $particular,
            ':amount'      => $overpaymentAmount,
            ':ref'         => $receiveSysId,
            ':meta'        => $advMeta
        ]);
        $advanceSysId = $advUUIDs['sys_id'];
    }

    /* ===== 9. Backdated recalculation ===== */
    if (!$isHistorical && $daysDiff > 0) {
        recalculateBalances($pdo, $accountId, $transactionDate);
    }

    $pdo->commit();

    echo json_encode([
        'success'            => true,
        'message'            => $isHistorical
            ? 'ঐতিহাসিক entry সংরক্ষিত হয়েছে'
            : ($isAdvance ? 'Advance recorded successfully (৳' . number_format($receiveAmount, 2) . ')'
                : ($isPartial ? 'Partial payment recorded'
                    : ($advanceSysId ? 'Payment received with advance (' . number_format($overpaymentAmount, 2) . ' BDT)'
                        : 'Payment received successfully'))),
        'is_historical'      => $isHistorical,
        'is_advance'         => $isAdvance,
        'is_partial'         => $isPartial,
        'is_discounted'      => $isDiscounted,
        'overpayment'        => $overpaymentAmount > 0.01,
        'overpayment_amount' => $overpaymentAmount,
        'data' => [
            'bank_stmt_id'      => $stmtSysId,
            'receive_entry_id'  => $receiveSysId,
            'discount_entry_id' => $discountSysId,
            'advance_entry_id'  => $advanceSysId,
            'new_balance'       => $newBalance
        ]
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function recalculateBalances($pdo, $accountId, $fromDate) {
    $prevStmt = $pdo->prepare("
        SELECT balance FROM ac_banking_stmts
        WHERE ledger_db_id = :aid AND date < :from AND is_historical = 0
        ORDER BY date DESC, sys_id DESC LIMIT 1
    ");
    $prevStmt->execute([':aid' => $accountId, ':from' => $fromDate]);
    $prevBalance = (float)$prevStmt->fetchColumn();

    if (!$prevBalance) {
        $s = $pdo->prepare("SELECT balance FROM ac_banking_stmts WHERE ledger_db_id = :aid AND particular = 'Opening Balance' LIMIT 1");
        $s->execute([':aid' => $accountId]);
        $prevBalance = (float)$s->fetchColumn();
    }

    $transStmt = $pdo->prepare("
        SELECT sys_id, withdraw, deposit FROM ac_banking_stmts
        WHERE ledger_db_id = :aid AND date >= :from AND is_historical = 0
        ORDER BY date ASC, sys_id ASC
    ");
    $transStmt->execute([':aid' => $accountId, ':from' => $fromDate]);

    $running = $prevBalance;
    $upStmt  = $pdo->prepare("UPDATE ac_banking_stmts SET balance = :bal WHERE sys_id = :sid");
    foreach ($transStmt->fetchAll(PDO::FETCH_ASSOC) as $t) {
        if ($t['withdraw'] > 0)    $running -= $t['withdraw'];
        elseif ($t['deposit'] > 0) $running += $t['deposit'];
        $upStmt->execute([':bal' => $running, ':sid' => $t['sys_id']]);
    }

    $pdo->prepare("UPDATE ac_banking SET balance = :bal WHERE sys_id = :aid")
        ->execute([':bal' => $running, ':aid' => $accountId]);
}