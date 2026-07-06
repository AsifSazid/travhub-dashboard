<?php
// server/finance_helpers.php
// Common finance functions — receive/payment/invoice তিন জায়গাতেই use হবে
// Requires: db_connection.php, uuid_with_system_id_generator.php, generate_meta_data.php already loaded

/**
 * Transaction এর ভেতরে loop এ safe ID generate করার জন্য
 * generateIDs() DB থেকে last row দেখে — uncommitted transaction এ
 * same sys_id বারবার আসতে পারে।
 * এই function প্রথম call এ DB থেকে নেয়, পরের call এ in-memory increment করে।
 */
function generateIDsSafe(string $tag): array {
    static $lastSysIds = [];

    if (!isset($lastSysIds[$tag])) {
        // প্রথমবার — generateIDs() দিয়ে নিই
        $ids = generateIDs($tag);
        $lastSysIds[$tag] = $ids['sys_id'];
        return $ids;
    }

    // পরেরবার — last sys_id থেকে manually increment
    $lastSysId = $lastSysIds[$tag];
    $parts     = explode('-', $lastSysId);

    // Format: THR-FE-26-00K003
    $company   = $parts[0]; // THR
    $short     = $parts[1]; // FE
    $year      = $parts[2]; // 26
    $blockSerial = explode('K', $parts[3]);
    $block     = $blockSerial[0]; // 00
    $serial    = $blockSerial[1]; // 003

    if ((int)$serial >= 999) {
        $block  = str_pad((int)$block + 1, 2, '0', STR_PAD_LEFT);
        $serial = '001';
    } else {
        $serial = str_pad((int)$serial + 1, 3, '0', STR_PAD_LEFT);
    }

    $newSysId = "{$company}-{$short}-{$year}-{$block}K{$serial}";
    $lastSysIds[$tag] = $newSysId;

    return [
        'uuid'   => uuidV4(),
        'sys_id' => $newSysId,
    ];
}

/**
 * FIFO তে sale entries clear করে + sale-wise আলাদা receive entries insert করে
 */
function applyPaymentToSales($pdo, $saleIds, $paidAmount, $clientId, $clientName, $date, $particular, $userName = 'system') {
    if (empty($saleIds) || $paidAmount <= 0) {
        return ['applied' => 0, 'receive_entry_ids' => [], 'details' => []];
    }

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

    $remaining   = $paidAmount;
    $applied     = 0;
    $rcvEntryIds = [];
    $details     = [];

    foreach ($sales as $sale) {
        if ($remaining <= 0.009) break;
        if ((int)$sale['is_paid'] === 1) continue;

        $saleRemaining = getSaleRemaining($pdo, $sale);
        if ($saleRemaining <= 0.009) {
            $pdo->prepare("UPDATE financial_entries SET is_paid=1, is_partial=0 WHERE sys_id=?")
                ->execute([$sale['sys_id']]);
            continue;
        }

        $payThis   = min($saleRemaining, $remaining);
        $fullCover = ($payThis >= $saleRemaining - 0.009);

        // Safe ID generate — loop এ duplicate হবে না
        $ids  = generateIDsSafe('financial_entries');
        $meta = buildMetaData(null, $userName);

        $pdo->prepare("
            INSERT INTO financial_entries
            (uuid, sys_id, user_sys_id, user_name, user_type,
             date, purpose, type, related_type,
             is_paid, is_partial, is_discounted, amount, ref, meta_data)
            VALUES (?, ?, ?, ?, 'client', ?, ?, 'credit', 3, 1, ?, 0, ?, ?, ?)
        ")->execute([
            $ids['uuid'], $ids['sys_id'],
            $clientId, $clientName,
            $date, $particular,
            $fullCover ? 0 : 1,
            $payThis,
            $sale['sys_id'],
            $meta
        ]);
        $rcvEntryIds[] = $ids['sys_id'];

        appendRefToEntry($pdo, $sale['sys_id'], $ids['sys_id'], $sale['ref'] ?? null);

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

        $ids  = generateIDsSafe('financial_entries');
        $meta = buildMetaData(null, $userName);

        $pdo->prepare("
            INSERT INTO financial_entries
            (uuid, sys_id, user_sys_id, user_name, user_type,
             date, purpose, type, related_type,
             is_paid, is_partial, is_discounted, amount, ref, meta_data)
            VALUES (?, ?, ?, ?, 'vendor', ?, ?, 'debit', 4, 1, ?, 0, ?, ?, ?)
        ")->execute([
            $ids['uuid'], $ids['sys_id'],
            $vendorId, $vendorName,
            $date, $particular,
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
 * Sale entry এর remaining amount
 * remaining = amount - SUM(linked receives rt=3) - SUM(linked discounts rt=5)
 */
function getSaleRemaining($pdo, $sale) {
    $amount = (float)$sale['amount'];
    $sysId  = $sale['sys_id'];

    try {
        $rcv = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0) FROM financial_entries
            WHERE type = 'credit' AND related_type IN (3, 5)
            AND (ref = ? OR JSON_CONTAINS(ref, JSON_QUOTE(?)))
        ");
        $rcv->execute([$sysId, $sysId]);
        $covered = (float)$rcv->fetchColumn();
    } catch (Exception $e) {
        $rcv2 = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0) FROM financial_entries
            WHERE type = 'credit' AND related_type IN (3, 5) AND ref = ?
        ");
        $rcv2->execute([$sysId]);
        $covered = (float)$rcv2->fetchColumn();
    }

    return max(0, $amount - $covered);
}

/**
 * Purchase entry এর remaining amount
 */
function getPurchaseRemaining($pdo, $purch) {
    $amount = (float)$purch['amount'];
    $sysId  = $purch['sys_id'];

    try {
        $pay = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0) FROM financial_entries
            WHERE type = 'debit' AND related_type IN (4, 5)
            AND (ref = ? OR JSON_CONTAINS(ref, JSON_QUOTE(?)))
        ");
        $pay->execute([$sysId, $sysId]);
        $covered = (float)$pay->fetchColumn();
    } catch (Exception $e) {
        $pay2 = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0) FROM financial_entries
            WHERE type = 'debit' AND related_type IN (4, 5) AND ref = ?
        ");
        $pay2->execute([$sysId]);
        $covered = (float)$pay2->fetchColumn();
    }

    return max(0, $amount - $covered);
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
            $refs = [$currentRef];
        }
    }
    if (!in_array($newRefId, $refs)) {
        $refs[] = $newRefId;
    }
    $pdo->prepare("UPDATE financial_entries SET ref = ? WHERE sys_id = ?")
        ->execute([json_encode($refs), $entrySysId]);
}

/**
 * Overpayment handle — Advance (rt=6) বা Baksheesh (rt=7)
 */
function handleOverpayment($pdo, $action, $overpayAmount, $userType, $userSysId, $userName2, $date, $refId, $sessionUser = 'system') {
    if ($overpayAmount <= 0.009) return null;

    $ids  = generateIDsSafe('financial_entries');
    $meta = buildMetaData(null, $sessionUser);

    $type    = $userType === 'client' ? 'credit' : 'debit';
    $rt      = $action === 'baksheesh' ? 7 : 6;
    $purpose = $action === 'baksheesh'
        ? 'Baksheesh: ' . $refId
        : ($userType === 'client' ? 'Advance: Overpayment from ' : 'Advance to Vendor: Overpayment from ') . $refId;

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
        $overpayAmount, $refId, $meta
    ]);

    return $ids['sys_id'];
}

/**
 * Discount entry insert (rt=5)
 */
function insertDiscountEntry($pdo, $discountAmount, $userType, $userSysId, $userName2, $date, $particular, $refIds, $sessionUser = 'system') {
    if ($discountAmount <= 0.009) return null;

    $ids  = generateIDsSafe('financial_entries');
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
        $discountAmount, $ref, $meta
    ]);

    return $ids['sys_id'];
}

/**
 * Instant methods: cash, mfs, npsb → bank update হয়
 */
function isInstantMethod($method) {
    return in_array(strtolower($method), ['cash', 'mfs', 'npsb', 'online'], true);
}

/**
 * Instrument methods: cheque, bftn-eft → pending
 */
function isInstrumentMethod($method) {
    return in_array(strtolower($method), ['cheque', 'bftn-eft'], true);
}