<?php
// server/finance_helpers.php
// Common finance functions — receive/payment/invoice তিন জায়গাতেই use হবে
// Requires: db_connection.php, uuid_with_system_id_generator.php, generate_meta_data.php already loaded

/**
 * FIFO তে sale entries clear করে + sale-wise আলাদা receive entries insert করে
 *
 * @param PDO    $pdo
 * @param array  $saleIds     linked sale entry sys_ids (FIFO order এ sort হবে date দিয়ে)
 * @param float  $paidAmount  মোট payment (sale clear করার জন্য available)
 * @param string $clientId
 * @param string $clientName
 * @param string $date
 * @param string $particular
 * @param string $userName    session user
 * @return array ['applied' => float, 'receive_entry_ids' => [], 'details' => []]
 */
function applyPaymentToSales($pdo, $saleIds, $paidAmount, $clientId, $clientName, $date, $particular, $userName = 'system') {
    if (empty($saleIds) || $paidAmount <= 0) {
        return ['applied' => 0, 'receive_entry_ids' => [], 'details' => []];
    }

    // Sale entries FIFO (date ASC) — remaining amount সহ
    $ph = implode(',', array_fill(0, count($saleIds), '?'));
    $stmt = $pdo->prepare("
        SELECT sys_id, amount, is_paid, is_partial, ref
        FROM financial_entries
        WHERE sys_id IN ($ph)
        AND user_sys_id = ?
        AND user_type = 'client'
        AND type = 'debit'
        AND related_type = 1
        ORDER BY date ASC, id ASC
    ");
    $stmt->execute([...array_values($saleIds), $clientId]);
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $remaining     = $paidAmount;
    $applied       = 0;
    $rcvEntryIds   = [];
    $details       = [];

    foreach ($sales as $sale) {
        if ($remaining <= 0.009) break;
        if ((int)$sale['is_paid'] === 1) continue; // already fully paid

        // এই sale এর remaining বের করি — আগের receives/discounts বাদ
        $saleRemaining = getSaleRemaining($pdo, $sale);
        if ($saleRemaining <= 0.009) {
            // technically fully covered — flag ঠিক করি
            $pdo->prepare("UPDATE financial_entries SET is_paid=1, is_partial=0 WHERE sys_id=?")
                ->execute([$sale['sys_id']]);
            continue;
        }

        $payThis = min($saleRemaining, $remaining);
        $fullCover = ($payThis >= $saleRemaining - 0.009);

        // Sale-wise receive entry insert (rt=3)
        $ids  = generateIDs('financial_entries');
        $meta = buildMetaData(null, $userName);
        $pdo->prepare("
            INSERT INTO financial_entries
            (uuid, sys_id, user_sys_id, user_name, user_type,
             date, purpose, type, related_type,
             is_paid, is_partial, is_discounted, amount, ref, meta_data)
            VALUES (?, ?, ?, ?, 'client', ?, ?, 'credit', 3, ?, ?, 0, ?, ?, ?)
        ")->execute([
            $ids['uuid'], $ids['sys_id'],
            $clientId, $clientName,
            $date, $particular,
            $fullCover ? 1 : 1,     // receive entry নিজে সবসময় is_paid=1 (bank এ টাকা এসেছে)
            $fullCover ? 0 : 1,     // partial cover হলে receive entry is_partial=1
            $payThis,
            $sale['sys_id'],        // ref = এই sale এর sys_id
            $meta
        ]);
        $rcvEntryIds[] = $ids['sys_id'];

        // Sale entry ref update — receive sys_id append (JSON array)
        appendRefToEntry($pdo, $sale['sys_id'], $ids['sys_id'], $sale['ref'] ?? null);

        // Sale entry flags update
        if ($fullCover) {
            $pdo->prepare("UPDATE financial_entries SET is_paid=1, is_partial=0 WHERE sys_id=?")
                ->execute([$sale['sys_id']]);
        } else {
            $pdo->prepare("UPDATE financial_entries SET is_partial=1 WHERE sys_id=?")
                ->execute([$sale['sys_id']]);
        }

        $details[] = [
            'sale_sys_id'    => $sale['sys_id'],
            'paid'           => $payThis,
            'fully_covered'  => $fullCover,
            'receive_sys_id' => $ids['sys_id'],
        ];

        $applied   += $payThis;
        $remaining -= $payThis;
    }

    return ['applied' => $applied, 'receive_entry_ids' => $rcvEntryIds, 'details' => $details];
}

/**
 * FIFO তে purchase entries clear করে + purchase-wise আলাদা payment entries insert করে
 * (vendor side mirror)
 */
function applyPaymentToPurchases($pdo, $purchaseIds, $paidAmount, $vendorId, $vendorName, $date, $particular, $userName = 'system') {
    if (empty($purchaseIds) || $paidAmount <= 0) {
        return ['applied' => 0, 'payment_entry_ids' => [], 'details' => []];
    }

    $ph = implode(',', array_fill(0, count($purchaseIds), '?'));
    $stmt = $pdo->prepare("
        SELECT sys_id, amount, is_paid, is_partial, ref
        FROM financial_entries
        WHERE sys_id IN ($ph)
        AND user_sys_id = ?
        AND user_type = 'vendor'
        AND type = 'credit'
        AND related_type = 2
        ORDER BY date ASC, id ASC
    ");
    $stmt->execute([...array_values($purchaseIds), $vendorId]);
    $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $remaining   = $paidAmount;
    $applied     = 0;
    $payEntryIds = [];
    $details     = [];

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

        // Purchase-wise payment entry insert (rt=4)
        $ids  = generateIDs('financial_entries');
        $meta = buildMetaData(null, $userName);
        $pdo->prepare("
            INSERT INTO financial_entries
            (uuid, sys_id, user_sys_id, user_name, user_type,
             date, purpose, type, related_type,
             is_paid, is_partial, is_discounted, amount, ref, meta_data)
            VALUES (?, ?, ?, ?, 'vendor', ?, ?, 'debit', 4, ?, ?, 0, ?, ?, ?)
        ")->execute([
            $ids['uuid'], $ids['sys_id'],
            $vendorId, $vendorName,
            $date, $particular,
            1,
            $fullCover ? 0 : 1,
            $payThis,
            $purch['sys_id'],
            $meta
        ]);
        $payEntryIds[] = $ids['sys_id'];

        appendRefToEntry($pdo, $purch['sys_id'], $ids['sys_id'], $purch['ref'] ?? null);

        if ($fullCover) {
            $pdo->prepare("UPDATE financial_entries SET is_paid=1, is_partial=0 WHERE sys_id=?")
                ->execute([$purch['sys_id']]);
        } else {
            $pdo->prepare("UPDATE financial_entries SET is_partial=1 WHERE sys_id=?")
                ->execute([$purch['sys_id']]);
        }

        $details[] = [
            'purchase_sys_id' => $purch['sys_id'],
            'paid'            => $payThis,
            'fully_covered'   => $fullCover,
            'payment_sys_id'  => $ids['sys_id'],
        ];

        $applied   += $payThis;
        $remaining -= $payThis;
    }

    return ['applied' => $applied, 'payment_entry_ids' => $payEntryIds, 'details' => $details];
}

/**
 * Sale entry এর remaining amount বের করে
 * remaining = amount - SUM(linked receives rt=3) - SUM(linked discounts rt=5)
 */
function getSaleRemaining($pdo, $sale) {
    $amount = (float)$sale['amount'];
    $sysId  = $sale['sys_id'];

    try {
        $rcvStmt = $pdo->prepare("
            SELECT sys_id, amount, ref FROM financial_entries
            WHERE type = 'credit' AND related_type IN (3, 5)
              AND (ref = ? OR JSON_CONTAINS(ref, JSON_QUOTE(?)))
            ORDER BY date ASC, id ASC
        ");
        $rcvStmt->execute([$sysId, $sysId]);
        $rcvEntries = $rcvStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $rcvStmt = $pdo->prepare("
            SELECT sys_id, amount, ref FROM financial_entries
            WHERE type = 'credit' AND related_type IN (3, 5) AND ref = ?
            ORDER BY date ASC, id ASC
        ");
        $rcvStmt->execute([$sysId]);
        $rcvEntries = $rcvStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $totalCovered = 0.0;

    foreach ($rcvEntries as $rcv) {
        $rcvAmount  = (float)$rcv['amount'];
        $refDecoded = json_decode($rcv['ref'], true);

        if (is_array($refDecoded) && count($refDecoded) > 1) {
            // Multiple sales linked — FIFO তে distribute
            $refIds = array_values($refDecoded);
            $ph     = implode(',', array_fill(0, count($refIds), '?'));
            try {
                $rsStmt = $pdo->prepare("
                    SELECT sys_id, amount FROM financial_entries
                    WHERE sys_id IN ($ph) AND type = 'debit' AND related_type = 1
                    ORDER BY date ASC, id ASC
                ");
                $rsStmt->execute($refIds);
                $refSales = $rsStmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $totalCovered += $rcvAmount / count($refIds);
                continue;
            }
            $rem = $rcvAmount;
            foreach ($refSales as $rs) {
                if ($rem <= 0.009) break;
                $share = min((float)$rs['amount'], $rem);
                if ($rs['sys_id'] === $sysId) { $totalCovered += $share; break; }
                $rem -= $share;
            }
        } else {
            $totalCovered += $rcvAmount;
        }
    }

    return max(0, $amount - $totalCovered);
}

/**
 * Purchase entry এর remaining amount
 * Fix: একটা rt=4 entry তে multiple purchases linked থাকলে FIFO তে distribute করে
 */
function getPurchaseRemaining($pdo, $purch) {
    $amount = (float)$purch['amount'];
    $sysId  = $purch['sys_id'];

    try {
        $payStmt = $pdo->prepare("
            SELECT sys_id, amount, ref FROM financial_entries
            WHERE type = 'debit' AND related_type IN (4, 5)
              AND (ref = ? OR JSON_CONTAINS(ref, JSON_QUOTE(?)))
            ORDER BY date ASC, id ASC
        ");
        $payStmt->execute([$sysId, $sysId]);
        $payEntries = $payStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $payStmt = $pdo->prepare("
            SELECT sys_id, amount, ref FROM financial_entries
            WHERE type = 'debit' AND related_type IN (4, 5) AND ref = ?
            ORDER BY date ASC, id ASC
        ");
        $payStmt->execute([$sysId]);
        $payEntries = $payStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $totalCovered = 0.0;

    foreach ($payEntries as $pay) {
        $payAmount  = (float)$pay['amount'];
        $refDecoded = json_decode($pay['ref'], true);

        if (is_array($refDecoded) && count($refDecoded) > 1) {
            // Multiple purchases — FIFO তে distribute
            $refIds = array_values($refDecoded);
            $ph     = implode(',', array_fill(0, count($refIds), '?'));
            try {
                $rpStmt = $pdo->prepare("
                    SELECT sys_id, amount FROM financial_entries
                    WHERE sys_id IN ($ph) AND type = 'credit' AND related_type = 2
                    ORDER BY date ASC, id ASC
                ");
                $rpStmt->execute($refIds);
                $refPurchases = $rpStmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $totalCovered += $payAmount / count($refIds);
                continue;
            }
            $rem = $payAmount;
            foreach ($refPurchases as $rp) {
                if ($rem <= 0.009) break;
                $share = min((float)$rp['amount'], $rem);
                if ($rp['sys_id'] === $sysId) { $totalCovered += $share; break; }
                $rem -= $share;
            }
        } else {
            $totalCovered += $payAmount;
        }
    }

    return max(0, $amount - $totalCovered);
}

/**
 * Entry এর ref field এ নতুন sys_id append করে (JSON array হিসেবে)
 */
function appendRefToEntry($pdo, $entrySysId, $newRefId, $currentRef = null) {
    $refs = [];
    if (!empty($currentRef)) {
        $decoded = json_decode($currentRef, true);
        if (is_array($decoded)) {
            $refs = $decoded;
        } else {
            $refs = [$currentRef]; // plain string ছিল
        }
    }
    if (!in_array($newRefId, $refs)) {
        $refs[] = $newRefId;
    }
    $pdo->prepare("UPDATE financial_entries SET ref = ? WHERE sys_id = ?")
        ->execute([json_encode($refs), $entrySysId]);
}

/**
 * Overpayment handle — user এর choice অনুযায়ী Advance (rt=6) বা Baksheesh (rt=7)
 *
 * @param string $action   'advance' | 'baksheesh'
 * @param string $userType 'client' | 'vendor'
 * @param string $refId    কিসের against (invoice sys_id / receive sys_id)
 * @return string|null inserted entry sys_id
 */
function handleOverpayment($pdo, $action, $overpayAmount, $userType, $userSysId, $userName2, $date, $refId, $sessionUser = 'system') {
    if ($overpayAmount <= 0.009) return null;

    $ids  = generateIDs('financial_entries');
    $meta = buildMetaData(null, $sessionUser);

    if ($action === 'baksheesh') {
        // Baksheesh (rt=7)
        // Client: credit (আমরা পেলাম) | Vendor: debit (আমরা দিলাম)
        $type    = $userType === 'client' ? 'credit' : 'debit';
        $purpose = 'Baksheesh: ' . $refId;
        $rt      = 7;
    } else {
        // Advance (rt=6)
        // Client: credit (client এর advance) | Vendor: debit (vendor কে advance)
        $type    = $userType === 'client' ? 'credit' : 'debit';
        $purpose = ($userType === 'client' ? 'Advance: Overpayment from ' : 'Advance to Vendor: Overpayment from ') . $refId;
        $rt      = 6;
    }

    $pdo->prepare("
        INSERT INTO financial_entries
        (uuid, sys_id, user_sys_id, user_name, user_type,
         date, purpose, type, related_type,
         is_paid, is_partial, is_discounted, amount, ref, meta_data)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 0, 0, ?, ?, ?)
    ")->execute([
        $ids['uuid'], $ids['sys_id'],
        $userSysId, $userName2, $userType,
        $date, $purpose, $type, $rt,
        $overpayAmount,
        $refId,
        $meta
    ]);

    return $ids['sys_id'];
}

/**
 * Discount entry insert (rt=5)
 * Client: credit (sale কমলো) | Vendor: debit (purchase কমলো)
 */
function insertDiscountEntry($pdo, $discountAmount, $userType, $userSysId, $userName2, $date, $particular, $refIds, $sessionUser = 'system') {
    if ($discountAmount <= 0.009) return null;

    $ids  = generateIDs('financial_entries');
    $meta = buildMetaData(null, $sessionUser);
    $type = $userType === 'client' ? 'credit' : 'debit';
    $ref  = is_array($refIds) ? json_encode($refIds) : $refIds;

    $pdo->prepare("
        INSERT INTO financial_entries
        (uuid, sys_id, user_sys_id, user_name, user_type,
         date, purpose, type, related_type,
         is_paid, is_partial, is_discounted, amount, ref, meta_data)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 5, 1, 0, 1, ?, ?, ?)
    ")->execute([
        $ids['uuid'], $ids['sys_id'],
        $userSysId, $userName2, $userType,
        $date, 'Discount: ' . $particular, $type,
        $discountAmount,
        $ref,
        $meta
    ]);

    return $ids['sys_id'];
}

/**
 * FILE PATH: /server/finance_helpers.php (appended section)
 *
 * ── v2 double-entry helper: ac_banking.category -> account_head ──
 * Used by financial_entries_v2/store-expense.php and store-asset.php to
 * resolve which account_head a given ac_banking row represents, based on
 * its Chart of Accounts category (set in pages/create-accounts.php).
 *
 * Bank/Cash/MFS categories map to 'bank_account' (transactionable=yes,
 * balance-tracked, usable as a payment source). Expense/Fixed Asset/Equity
 * categories are classification-only accounts (transactionable=no, no
 * balance tracking of their own -- their "value" is derived by summing
 * financial_entries, the same way accounts_payable/accounts_receivable are).
 */
/**
 * Payment-method classification, used by postBankLegV2() to decide whether
 * to move ac_banking.balance immediately (instant) or hold it pending
 * clearance in ac_instrument_tracking (instrument).
 *
 * NOTE: these were referenced by postBankLegV2() throughout this session
 * without actually being defined anywhere in the codebase -- every
 * payment-method-aware endpoint (store.php, pay-outstanding.php,
 * receive-outstanding.php, refund-settle-*.php, store-expense.php,
 * store-asset.php, update-slip-flow.php's disburse step, and this file's
 * own settle.php) would have fatally errored the first time a real payment
 * came through. Defined here to fix that.
 */
function isInstantMethod(string $method): bool
{
    return in_array(strtolower($method), ['cash', 'npsb', 'rtgs', 'eft', 'gateway'], true);
}

function isInstrumentMethod(string $method): bool
{
    return in_array(strtolower($method), ['cheque', 'bftn'], true);
}

/**
 * ── v2 shared: instrument-aware bank leg posting ──────────────────
 * Used by every financial_entries_v2 endpoint that moves real money
 * (store.php, pay-outstanding.php, receive-outstanding.php,
 * refund-settle-vendor.php, refund-settle-client.php, store-expense.php,
 * store-asset.php, and the payroll disburse step). Centralizing this here
 * means the instant-vs-instrument branching logic exists in exactly one
 * place.
 *
 * $direction: 'out' (money leaves the account) or 'in' (money enters it)
 * $paymentMethod: 'cash' | 'npsb' | 'rtgs' | 'bftn' | 'eft' | 'cheque'
 *
 * For instant methods, updates ac_banking.balance + ac_banking_stmts
 * immediately, exactly as before payment-method tracking existed.
 * For instrument methods (cheque/bftn/eft), does NOT touch ac_banking.
 * balance yet -- creates a pending row in ac_instrument_tracking instead;
 * api/acc-instrument-tracking/process-cleared.php (pre-existing module)
 * applies the balance change once the instrument actually clears.
 */
function postBankLegV2(PDO $pdo, string $accountId, string $accountName, float $oldBalance, float $amount, string $direction, string $date, string $particular, string $refEntrySysId, string $userName, string $paymentMethod = 'cash', ?string $counterpartyId = null, ?string $counterpartyName = null, ?string $counterpartyType = null, ?string $instrumentNo = null, ?string $bankName = null): void
{
    if (isInstrumentMethod($paymentMethod)) {
        createPendingInstrumentV2($pdo, [
            'instrument_type' => $paymentMethod, 'instrument_no' => $instrumentNo,
            'account_id' => $accountId, 'account_name' => $accountName,
            'bank_name' => $bankName ?: $accountName,
            'amount' => $amount, 'date' => $date, 'remarks' => $particular,
            'direction' => $direction,
            'counterparty_id' => $counterpartyId, 'counterparty_name' => $counterpartyName,
            'counterparty_type' => $counterpartyType,
            'ref_entry_sys_id' => $refEntrySysId, 'user_name' => $userName,
        ]);
        return; // balance/stmts deferred to process-cleared.php
    }

    $stmtIds  = generateV2IDs($pdo, 'ac_banking_stmts');
    $stmtMeta = buildMetaData(null, $userName);

    if ($direction === 'out') {
        $newBalance = $oldBalance - $amount;
        $pdo->prepare("UPDATE ac_banking SET balance = :bal WHERE sys_id = :id")
            ->execute([':bal' => $newBalance, ':id' => $accountId]);
        $pdo->prepare("
            INSERT INTO ac_banking_stmts
            (uuid, sys_id, ledger_db_id, name, date, particular,
             withdraw, deposit, balance, related_type, meta_data, ref, transfer_method)
            VALUES
            (:uuid, :sys_id, :ledger, :name, :date, :particular,
             :withdraw, 0, :balance, 2, :meta, :ref, :method)
        ")->execute([
            ':uuid' => $stmtIds['uuid'], ':sys_id' => $stmtIds['sys_id'],
            ':ledger' => $accountId, ':name' => $accountName, ':date' => $date,
            ':particular' => $particular, ':withdraw' => $amount, ':balance' => $newBalance,
            ':meta' => $stmtMeta, ':ref' => $refEntrySysId, ':method' => $paymentMethod,
        ]);
    } else {
        $newBalance = $oldBalance + $amount;
        $pdo->prepare("UPDATE ac_banking SET balance = :bal WHERE sys_id = :id")
            ->execute([':bal' => $newBalance, ':id' => $accountId]);
        $pdo->prepare("
            INSERT INTO ac_banking_stmts
            (uuid, sys_id, ledger_db_id, name, date, particular,
             withdraw, deposit, balance, related_type, meta_data, ref, transfer_method)
            VALUES
            (:uuid, :sys_id, :ledger, :name, :date, :particular,
             0, :deposit, :balance, 1, :meta, :ref, :method)
        ")->execute([
            ':uuid' => $stmtIds['uuid'], ':sys_id' => $stmtIds['sys_id'],
            ':ledger' => $accountId, ':name' => $accountName, ':date' => $date,
            ':particular' => $particular, ':deposit' => $amount, ':balance' => $newBalance,
            ':meta' => $stmtMeta, ':ref' => $refEntrySysId, ':method' => $paymentMethod,
        ]);
    }
}

/**
 * Creates a pending ac_instrument_tracking row for a held payment method,
 * matching the exact schema/format used by
 * api/acc-instrument-tracking/store.php so process-cleared.php can pick it
 * up normally. related_from/related_to use that module's "sys_id||name"
 * pipe format.
 */
function createPendingInstrumentV2(PDO $pdo, array $p): string
{
    $ids  = generateV2IDs($pdo, 'ac_instrument_tracking');
    $meta = buildMetaData(null, $p['user_name']);

    $isOut = $p['direction'] === 'out';
    $relatedFrom = $isOut ? "{$p['account_id']}||{$p['account_name']}" : "{$p['counterparty_id']}||{$p['counterparty_name']}";
    $relatedTo   = $isOut ? "{$p['counterparty_id']}||{$p['counterparty_name']}" : "{$p['account_id']}||{$p['account_name']}";
    $relatedType = $isOut ? 'a2p' : 'received';

    $pdo->prepare("
        INSERT INTO ac_instrument_tracking (
            uuid, sys_id, instrument_type, trnx_type, instrument_no, payment_to,
            account_name, bank_name, instrument_date,
            amount, related_type, related_from, related_to, status, date,
            remarks, meta_data
        ) VALUES (
            :uuid, :sys_id, :instrument_type, :trnx_type, :instrument_no, :payment_to,
            :account_name, :bank_name, :instrument_date,
            :amount, :related_type, :related_from, :related_to, 'pending', :date,
            :remarks, :meta_data
        )
    ")->execute([
        ':uuid' => $ids['uuid'], ':sys_id' => $ids['sys_id'],
        ':instrument_type' => strtoupper($p['instrument_type']), ':trnx_type' => $isOut ? 'debit' : 'credit',
        ':instrument_no' => $p['instrument_no'], ':payment_to' => $p['counterparty_type'],
        ':account_name' => $p['account_name'], ':bank_name' => $p['bank_name'],
        ':instrument_date' => $p['date'], ':amount' => $p['amount'],
        ':related_type' => $relatedType, ':related_from' => $relatedFrom, ':related_to' => $relatedTo,
        ':date' => $p['date'], ':remarks' => $p['remarks'], ':meta_data' => json_encode($meta),
    ]);

    return $ids['sys_id'];
}
{
    $bankLike = ['Bank Account', 'Cash in Hand', 'MFS - Mobile Financial Services',
                 'Current Assets', 'Accounts Receivable/Debtors', 'Non-current Assets'];
    $expenseLike = ['Expenses', 'Cost of sales', 'Other Expense'];
    $assetLike = ['Fixed Assets'];
    $equityLike = ['Equity'];

    if (in_array($category, $expenseLike, true)) return 'expense';
    if (in_array($category, $assetLike, true))   return 'fixed_asset';
    if (in_array($category, $equityLike, true))  return 'equity';
    if (in_array($category, $bankLike, true))    return 'bank_account';

    // Unrecognized/legacy category (e.g. rows created before categories
    // existed) -- default to bank_account, the original assumption every
    // ac_banking row made before this mapping was introduced.
    return 'bank_account';
}