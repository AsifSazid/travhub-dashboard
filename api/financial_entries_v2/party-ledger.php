<?php
// PATH: /api/financial_entries_v2/party-ledger.php
// (v2 -- powers the Vendor/Client Ledger page)
// GET ?party_type=vendor&party_id=<vendor_sys_id>
// GET ?party_type=client&party_id=<client_sys_id>
//
// Same grouping/classification logic as task-fin-entries.php, but scoped to
// ALL financial_entries rows involving one vendor or client, across every
// task/work -- not scoped to a single task. This is what the Vendor/Client
// Ledger page reads from.
//
// The grouping still pulls in every leg of a transaction group even if a
// leg belongs to a different user_type (e.g. a real-time vendor payment's
// bank_account leg, whose user_type is 'account') -- otherwise the ledger
// would show a purchase with no visible payment trail.

require '../../server/db_connection.php';
session_start();
require_once '../../server/permissions.php';
requireFullAccountingAccess($pdo, true);

header('Content-Type: application/json');

$partyType = $_GET['party_type'] ?? ''; // 'vendor' | 'client'
$partyId   = $_GET['party_id']   ?? '';

if (!in_array($partyType, ['vendor', 'client'], true) || !$partyId) {
    echo json_encode(['success' => false, 'message' => 'party_type (vendor|client) and party_id are required']);
    exit;
}

try {
    // ---- 1. Find every transaction_group_id this party appears in ----
    $groupIdStmt = $pdo->prepare("
        SELECT DISTINCT transaction_group_id, sys_id
        FROM financial_entries
        WHERE user_type = ? AND user_sys_id = ?
    ");
    $groupIdStmt->execute([$partyType, $partyId]);
    $groupRows = $groupIdStmt->fetchAll(PDO::FETCH_ASSOC);

    $groupIds = [];
    foreach ($groupRows as $r) {
        $groupIds[] = $r['transaction_group_id'] ?: $r['sys_id'];
    }
    $groupIds = array_values(array_unique($groupIds));

    if (!$groupIds) {
        echo json_encode(['success' => true, 'groups' => [], 'finStmts' => [], 'summary' => [
            'total_deposit' => 0, 'total_vendor_payment' => 0, 'total_payable_open' => 0,
            'total_receivable_open' => 0, 'net_refund_charge_profit' => 0,
            'vendor_refund_pending' => 0, 'client_refund_pending' => 0, 'balance' => 0,
            'entry_count' => 0, 'group_count' => 0, 'file_count' => 0,
        ]]);
        exit;
    }

    // ---- 2. Pull EVERY leg of every one of those groups (including legs
    //         belonging to a different user_type, e.g. the bank_account leg
    //         of a vendor payment) ----
    $placeholders = implode(',', array_fill(0, count($groupIds), '?'));
    $stmt = $pdo->prepare("
        SELECT * FROM financial_entries
        WHERE transaction_group_id IN ($placeholders) OR sys_id IN ($placeholders)
        ORDER BY id DESC
    ");
    $stmt->execute(array_merge($groupIds, $groupIds));
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ---- Group rows by transaction_group_id (same as task-fin-entries.php) ----
    $groups = [];
    foreach ($rows as $i => $row) {
        $gid = $row['transaction_group_id'] ?: $row['sys_id'];
        if (!isset($groups[$gid])) {
            $groups[$gid] = ['legs' => [], '_order' => $i];
        }
        $groups[$gid]['legs'][] = $row;
    }
    uasort($groups, fn($a, $b) => $a['_order'] <=> $b['_order']);

    // ---- Balances (this party only -- only count legs where user_type/user_sys_id matches) ----
    $ar = $ap = $sales = $purchase = $bankOut = $bankIn = 0.0;
    $refundChargeIn = $refundChargeOut = $vendorRefundPending = $clientRefundPending = 0.0;
    $advanceReceived = $discountGiven = $discountReceived = $gratuityGiven = $gratuityReceived = 0.0;
    $fileCount = 0;

    foreach ($rows as $row) {
        // Only fold this row into the party's own balances if it's actually
        // that party's own leg (not e.g. the bank_account leg pulled in for context).
        if ($row['user_type'] !== $partyType || $row['user_sys_id'] !== $partyId) continue;

        $amount = (float)($row['amount'] ?? 0);
        $type   = $row['type'] ?? '';
        $head   = $row['account_head'] ?? '';
        $sign   = ($type === 'debit') ? 1 : -1;

        switch ($head) {
            case 'accounts_receivable': $ar       += $sign * $amount; break;
            case 'accounts_payable':    $ap       += (-$sign) * $amount; break;
            case 'sales':               $sales    += (-$sign) * $amount; break;
            case 'purchase':            $purchase += $sign * $amount; break;
            case 'advance_received':    $advanceReceived += $amount; break;
            case 'discount':
                if ($row['user_type'] === 'vendor') $discountReceived += $amount;
                else                                $discountGiven    += $amount;
                break;
            case 'gratuity':
                if ($row['user_type'] === 'vendor') $gratuityGiven    += $amount;
                else                                $gratuityReceived += $amount;
                break;
            case 'refund_charge':
                if ($row['user_type'] === 'vendor') $refundChargeOut += $amount;
                else                                $refundChargeIn  += $amount;
                break;
            case 'vendor_refund_receivable': $vendorRefundPending += $sign * $amount; break;
            case 'client_refund_payable':    $clientRefundPending += (-$sign) * $amount; break;
        }

        $files = json_decode($row['files_json'] ?? '[]', true);
        if (is_array($files)) $fileCount += count($files);
    }
    // bank_account legs are never this party's own leg (user_type='account'
    // always), so total_vendor_payment/deposit come from the linked groups instead.
    foreach ($rows as $row) {
        if (($row['account_head'] ?? '') === 'bank_account') {
            $amount = (float)($row['amount'] ?? 0);
            if ($row['type'] === 'credit') $bankOut += $amount; else $bankIn += $amount;
        }
    }

    $totalReceivable = round($ar, 2);
    $totalDeposit    = round($sales - $totalReceivable, 2);
    $totalPayable    = round($ap, 2);
    $totalVendorPayment = round($bankOut, 2);
    $netRefundChargeProfit = round($refundChargeIn - $refundChargeOut, 2);
    $vendorRefundPendingRound = round($vendorRefundPending, 2);
    $clientRefundPendingRound = round($clientRefundPending, 2);

    // ---- Classification (identical logic to task-fin-entries.php) ----
    function _classifyGroup(PDO $pdo, array $legs): array
    {
        $heads = array_column($legs, 'account_head');
        $hasPurchase = in_array('purchase', $heads, true);
        $hasSales    = in_array('sales', $heads, true);
        $hasRefundCharge = in_array('refund_charge', $heads, true);
        $hasVendorRefundReceivable = in_array('vendor_refund_receivable', $heads, true);
        $hasClientRefundPayable    = in_array('client_refund_payable', $heads, true);
        $apLegs = array_values(array_filter($legs, fn($l) => $l['account_head'] === 'accounts_payable'));
        $arLegs = array_values(array_filter($legs, fn($l) => $l['account_head'] === 'accounts_receivable'));

        $amount = (float)($legs[0]['amount'] ?? 0);
        $groupId = $legs[0]['transaction_group_id'] ?: $legs[0]['sys_id'];

        if ($hasRefundCharge || $hasVendorRefundReceivable || $hasClientRefundPayable) {
            $isVendorSide = $hasVendorRefundReceivable || in_array('purchase', $heads, true);
            if ($isVendorSide) {
                $recvLeg = array_values(array_filter($legs, fn($l) => $l['account_head'] === 'vendor_refund_receivable'))[0] ?? null;
                $pendingAmount = $recvLeg ? (float)$recvLeg['amount'] : 0;
                $settledStmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM financial_entries WHERE account_head = 'vendor_refund_receivable' AND type = 'credit' AND ref = ?");
                $settledStmt->execute([$groupId]);
                $settled = (float)$settledStmt->fetchColumn();
                $pendingDue = round($pendingAmount - $settled, 2);
                return ['type' => 'vendor_refund', 'label' => $pendingAmount <= 0 ? 'Vendor Refund' : ($pendingDue <= 0 ? 'Vendor Refund (Received)' : ($settled > 0 ? 'Vendor Refund (Partially Received)' : 'Vendor Refund (Pending)')), 'amount' => $amount, 'due' => max($pendingDue, 0), 'payable' => false, 'refund_receivable' => $pendingDue > 0];
            } else {
                $payLeg = array_values(array_filter($legs, fn($l) => $l['account_head'] === 'client_refund_payable'))[0] ?? null;
                $pendingAmount = $payLeg ? (float)$payLeg['amount'] : 0;
                $settledStmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM financial_entries WHERE account_head = 'client_refund_payable' AND type = 'debit' AND ref = ?");
                $settledStmt->execute([$groupId]);
                $settled = (float)$settledStmt->fetchColumn();
                $pendingDue = round($pendingAmount - $settled, 2);
                return ['type' => 'client_refund', 'label' => $pendingAmount <= 0 ? 'Client Refund' : ($pendingDue <= 0 ? 'Client Refund (Paid)' : ($settled > 0 ? 'Client Refund (Partially Paid)' : 'Client Refund (Pending)')), 'amount' => $amount, 'due' => max($pendingDue, 0), 'payable' => false, 'refund_payable' => $pendingDue > 0];
            }
        }
        if (in_array('vendor_refund_receivable', $heads, true) && !$hasPurchase) {
            return ['type' => 'vendor_refund_settle', 'label' => 'Refund Received', 'amount' => $amount, 'due' => 0, 'payable' => false];
        }
        if (in_array('client_refund_payable', $heads, true) && !$hasSales) {
            return ['type' => 'client_refund_settle', 'label' => 'Refund Paid', 'amount' => $amount, 'due' => 0, 'payable' => false];
        }
        if ($hasPurchase) {
            $paidNow = count($apLegs) > 1;
            if ($paidNow) {
                return ['type' => 'purchase', 'label' => 'Purchase (Paid)', 'amount' => $amount, 'paid' => $amount, 'due' => 0, 'payable' => false];
            }
            $paidStmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM financial_entries WHERE account_head = 'accounts_payable' AND type = 'debit' AND ref = ?");
            $paidStmt->execute([$groupId]);
            $alreadyPaid = (float)$paidStmt->fetchColumn();
            $due = round($amount - $alreadyPaid, 2);
            return ['type' => 'purchase', 'label' => $due <= 0 ? 'Purchase (Paid)' : ($alreadyPaid > 0 ? 'Purchase (Partially Paid)' : 'Purchase (Unpaid)'), 'amount' => $amount, 'paid' => $alreadyPaid, 'due' => max($due, 0), 'payable' => $due > 0];
        }
        if ($hasSales) {
            $receivedStmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM financial_entries WHERE account_head = 'accounts_receivable' AND type = 'credit' AND ref = ?");
            $receivedStmt->execute([$groupId]);
            $alreadyReceived = (float)$receivedStmt->fetchColumn();
            $due = round($amount - $alreadyReceived, 2);
            return ['type' => 'sale', 'label' => $due <= 0 ? 'Sale (Received)' : ($alreadyReceived > 0 ? 'Sale (Partially Received)' : 'Sale (Unreceived)'), 'amount' => $amount, 'received' => $alreadyReceived, 'due' => max($due, 0), 'payable' => false, 'receivable' => $due > 0];
        }
        if (!empty($apLegs) && !$hasPurchase) {
            $isGeneral = empty($apLegs[0]['ref']);
            return ['type' => 'payment', 'label' => $isGeneral ? 'Vendor Payment (General)' : 'Vendor Payment', 'amount' => $amount, 'paid' => $amount, 'due' => 0, 'payable' => false];
        }
        if (!empty($arLegs) && !$hasSales) {
            $isRefund = ($arLegs[0]['type'] ?? '') === 'debit';
            $isGeneral = empty($arLegs[0]['ref']);
            return ['type' => $isRefund ? 'refund' : 'receive', 'label' => $isRefund ? 'Client Refund' : ($isGeneral ? 'Client Receive (General)' : 'Client Receive'), 'amount' => $amount, 'received' => $isRefund ? 0 : $amount, 'due' => 0, 'payable' => false];
        }
        if (in_array('advance_received', $heads, true)) {
            return ['type' => 'advance', 'label' => 'Advance Received', 'amount' => $amount, 'due' => 0, 'payable' => false];
        }
        if (in_array('discount', $heads, true)) {
            $discountLeg = $legs[0];
            $isVendorSide = $discountLeg['user_type'] === 'vendor';
            return ['type' => 'discount', 'label' => $isVendorSide ? 'Discount Received' : 'Discount Given', 'amount' => $amount, 'due' => 0, 'payable' => false];
        }
        if (in_array('gratuity', $heads, true)) {
            $gratuityLeg = array_values(array_filter($legs, fn($l) => $l['account_head'] === 'gratuity'))[0] ?? $legs[0];
            $isVendorSide = $gratuityLeg['user_type'] === 'vendor';
            return ['type' => 'gratuity', 'label' => $isVendorSide ? 'Gratuity Given' : 'Gratuity Received', 'amount' => $amount, 'due' => 0, 'payable' => false];
        }
        return ['type' => 'other', 'label' => 'Transaction', 'amount' => $amount, 'due' => 0, 'payable' => false];
    }

    echo json_encode([
        'success' => true,
        'groups' => array_values(array_map(function ($g) use ($pdo) {
            $legs = $g['legs'];
            $first = $legs[0];
            $classification = _classifyGroup($pdo, $legs);
            $vendorLeg = array_values(array_filter($legs, fn($l)=>$l['user_type']==='vendor'))[0] ?? null;
            $clientLeg = array_values(array_filter($legs, fn($l)=>$l['user_type']==='client'))[0] ?? null;
            return [
                'transaction_group_id' => $first['transaction_group_id'] ?: $first['sys_id'],
                'date'    => $first['date'],
                'purpose' => $first['purpose'],
                'ref'     => $first['ref'],
                'work_sys_id' => $first['work_sys_id'],
                'work_title'  => $first['work_title'],
                'task_sys_id' => $first['task_sys_id'],
                'task_title'  => $first['task_title'],
                'who'     => $vendorLeg['user_name'] ?? $clientLeg['user_name'] ?? $first['user_name'],
                'event_type'  => $classification['type'],
                'event_label' => $classification['label'],
                'amount'      => $classification['amount'],
                'due'         => $classification['due'],
                'payable'     => $classification['payable'] ?? false,
                'receivable'  => $classification['receivable'] ?? false,
                'refund_receivable' => $classification['refund_receivable'] ?? false,
                'refund_payable'    => $classification['refund_payable'] ?? false,
                'vendor_id'   => (($classification['payable'] ?? false) || ($classification['refund_receivable'] ?? false)) ? ($vendorLeg['user_sys_id'] ?? null) : null,
                'client_id'   => (($classification['receivable'] ?? false) || ($classification['refund_payable'] ?? false)) ? ($clientLeg['user_sys_id'] ?? null) : null,
                'legs'    => $legs,
            ];
        }, $groups)),
        'finStmts' => $rows,
        'summary'  => [
            'total_deposit'         => $totalDeposit,
            'total_vendor_payment'  => $totalVendorPayment,
            'total_payable_open'    => $totalPayable,
            'total_receivable_open' => $totalReceivable,
            'net_refund_charge_profit' => $netRefundChargeProfit,
            'vendor_refund_pending'    => $vendorRefundPendingRound,
            'client_refund_pending'    => $clientRefundPendingRound,
            'total_advance_received'   => round($advanceReceived, 2),
            'total_discount_given'     => round($discountGiven, 2),
            'total_discount_received'  => round($discountReceived, 2),
            'total_gratuity_given'     => round($gratuityGiven, 2),
            'total_gratuity_received'  => round($gratuityReceived, 2),
            'balance'               => round($totalDeposit - $totalVendorPayment, 2),
            'entry_count'           => count($rows),
            'group_count'           => count($groups),
            'file_count'            => $fileCount,
        ],
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}