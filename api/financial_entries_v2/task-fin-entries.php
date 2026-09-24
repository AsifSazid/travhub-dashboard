<?php
// (v2 -- separate namespace from legacy /api/financial_entries/, used only by the new Task View)
// PATH: /api/financial_entries_v2/task-fin-entries.php
// GET ?task_id=THR-...-TK-0001
//
// Returns financial_entries rows for a task, GROUPED by transaction_group_id
// (each group is one economic event -- a sale, a purchase, a real-time
// vendor payment spanning 4 rows, etc.), plus a summary computed from
// account_head balances rather than raw user_type/type guessing.
//
// account_head balance rules (standard double-entry):
//   accounts_receivable (asset)     debit increases, credit decreases
//   accounts_payable    (liability) credit increases, debit decreases
//   sales                (revenue)  credit increases, debit decreases
//   purchase             (expense)  debit increases, credit decreases
//   bank_account          (asset)   debit increases, credit decreases
//
// Summary shown to the Financial tab:
//   total_deposit        = net accounts_receivable movement from sales+deposits
//                           (i.e. how much of what clients owed has come in)
//   total_vendor_payment = net bank_account credit tied to vendor payments
//                           (i.e. how much cash has actually left for vendors)
//   total_payable_open   = current outstanding accounts_payable balance
//                           (non-real-time purchases not yet paid)
//   total_receivable_open= current outstanding accounts_receivable balance
//   balance              = money-in-hand proxy: total_deposit - total_vendor_payment

require '../../server/db_connection.php';
session_start();
require_once '../../server/permissions.php';
requireFullAccountingAccess($pdo, true);

header('Content-Type: application/json');

$taskId = $_GET['task_id'] ?? '';

if (!$taskId) {
    echo json_encode(['success' => false, 'message' => 'task_id is required']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT * FROM financial_entries
        WHERE task_sys_id = ?
        ORDER BY id DESC
    ");
    $stmt->execute([$taskId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ---- Group rows by transaction_group_id (fallback to sys_id for any
    // pre-migration row that never got backfilled) ----
    $groups = []; // group_id => [ 'legs' => [...rows], '_order' => first-seen index ]
    foreach ($rows as $i => $row) {
        $gid = $row['transaction_group_id'] ?: $row['sys_id'];
        if (!isset($groups[$gid])) {
            $groups[$gid] = ['legs' => [], '_order' => $i];
        }
        $groups[$gid]['legs'][] = $row;
    }
    // Preserve original (newest-first) ordering by first-seen row
    uasort($groups, fn($a, $b) => $a['_order'] <=> $b['_order']);

    // ---- Balances (whole-task) ----
    $ar = $ap = $sales = $purchase = $bankOut = $bankIn = 0.0;
    $refundChargeIn = $refundChargeOut = $vendorRefundPending = $clientRefundPending = 0.0;
    $fileCount = 0;

    foreach ($rows as $row) {
        $amount = (float)($row['amount'] ?? 0);
        $type   = $row['type'] ?? '';
        $head   = $row['account_head'] ?? '';
        $sign   = ($type === 'debit') ? 1 : -1; // debit increases asset/expense-style heads

        switch ($head) {
            case 'accounts_receivable': $ar       += $sign * $amount; break;
            case 'accounts_payable':    $ap       += (-$sign) * $amount; break; // liability: credit increases
            case 'sales':               $sales    += (-$sign) * $amount; break; // revenue: credit increases
            case 'purchase':            $purchase += $sign * $amount; break;
            case 'bank_account':
                if ($type === 'credit') $bankOut += $amount; // money left this account
                else                    $bankIn  += $amount; // money entered this account
                break;
            case 'refund_charge':
                // Vendor-side refund_charge is credit (we lose it); client-side
                // refund_charge is debit (we keep it) -- net them by sign.
                if ($row['user_type'] === 'vendor') $refundChargeOut += $amount;
                else                                $refundChargeIn  += $amount;
                break;
            case 'vendor_refund_receivable': $vendorRefundPending += $sign * $amount; break; // debit increases, credit (settled) decreases
            case 'client_refund_payable':    $clientRefundPending += (-$sign) * $amount; break; // credit increases, debit (settled) decreases
        }

        $files = json_decode($row['files_json'] ?? '[]', true);
        if (is_array($files)) $fileCount += count($files);
    }

    // "Deposit received" proxy: sales recognized minus what's still outstanding as AR
    // gives how much of the client side has actually been settled/received.
    $totalSales      = $sales;
    $totalReceivable = round($ar, 2);      // current AR balance (outstanding)
    $totalDeposit    = round($totalSales - $totalReceivable, 2); // received so far
    $totalPayable    = round($ap, 2);      // current AP balance (outstanding, unpaid purchases)
    $totalVendorPayment = round($bankOut, 2); // cash actually paid out to vendors
    $netRefundChargeProfit = round($refundChargeIn - $refundChargeOut, 2); // client-side charge kept minus vendor-side charge lost
    $vendorRefundPendingRound = round($vendorRefundPending, 2); // owed TO us by vendors, not yet received
    $clientRefundPendingRound = round($clientRefundPending, 2); // owed BY us to clients, not yet paid

    // ---- Classify each group's business event type from its legs ----
    // (used by the UI so it can show "Purchase", "Payment", "Sale", "Receive"
    // etc. instead of raw account_head/debit-credit rows on the collapsed view)
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

        // ---- Refund DECLARE (has refund_charge or a pending refund head alongside purchase/sales) ----
        if ($hasRefundCharge || $hasVendorRefundReceivable || $hasClientRefundPayable) {
            $isVendorSide = $hasVendorRefundReceivable || in_array('purchase', $heads, true);
            if ($isVendorSide) {
                $recvLeg = array_values(array_filter($legs, fn($l) => $l['account_head'] === 'vendor_refund_receivable'))[0] ?? null;
                $pendingAmount = $recvLeg ? (float)$recvLeg['amount'] : 0;
                $settledStmt = $pdo->prepare("
                    SELECT COALESCE(SUM(amount), 0) FROM financial_entries
                    WHERE account_head = 'vendor_refund_receivable' AND type = 'credit' AND ref = ?
                ");
                $settledStmt->execute([$groupId]);
                $settled = (float)$settledStmt->fetchColumn();
                $pendingDue = round($pendingAmount - $settled, 2);
                return [
                    'type' => 'vendor_refund',
                    'label' => $pendingAmount <= 0 ? 'Vendor Refund' : ($pendingDue <= 0 ? 'Vendor Refund (Received)' : ($settled > 0 ? 'Vendor Refund (Partially Received)' : 'Vendor Refund (Pending)')),
                    'amount' => $amount, 'due' => max($pendingDue, 0),
                    'payable' => false, 'refund_receivable' => $pendingDue > 0,
                ];
            } else {
                $payLeg = array_values(array_filter($legs, fn($l) => $l['account_head'] === 'client_refund_payable'))[0] ?? null;
                $pendingAmount = $payLeg ? (float)$payLeg['amount'] : 0;
                $settledStmt = $pdo->prepare("
                    SELECT COALESCE(SUM(amount), 0) FROM financial_entries
                    WHERE account_head = 'client_refund_payable' AND type = 'debit' AND ref = ?
                ");
                $settledStmt->execute([$groupId]);
                $settled = (float)$settledStmt->fetchColumn();
                $pendingDue = round($pendingAmount - $settled, 2);
                return [
                    'type' => 'client_refund',
                    'label' => $pendingAmount <= 0 ? 'Client Refund' : ($pendingDue <= 0 ? 'Client Refund (Paid)' : ($settled > 0 ? 'Client Refund (Partially Paid)' : 'Client Refund (Pending)')),
                    'amount' => $amount, 'due' => max($pendingDue, 0),
                    'payable' => false, 'refund_payable' => $pendingDue > 0,
                ];
            }
        }
        // ---- Refund SETTLE (standalone vendor_refund_receivable/client_refund_payable leg, no purchase/sales) ----
        if (in_array('vendor_refund_receivable', $heads, true) && !$hasPurchase) {
            return ['type' => 'vendor_refund_settle', 'label' => 'Refund Received', 'amount' => $amount, 'due' => 0, 'payable' => false];
        }
        if (in_array('client_refund_payable', $heads, true) && !$hasSales) {
            return ['type' => 'client_refund_settle', 'label' => 'Refund Paid', 'amount' => $amount, 'due' => 0, 'payable' => false];
        }

        if ($hasPurchase) {
            $paidNow = count($apLegs) > 1; // credit + debit pair means cleared in this same group (realtime)
            if ($paidNow) {
                return ['type' => 'purchase', 'label' => 'Purchase (Paid)', 'amount' => $amount, 'paid' => $amount, 'due' => 0, 'payable' => false];
            }
            // Non-realtime: check for any later payments referencing this group.
            $paidStmt = $pdo->prepare("
                SELECT COALESCE(SUM(amount), 0) FROM financial_entries
                WHERE account_head = 'accounts_payable' AND type = 'debit' AND ref = ?
            ");
            $paidStmt->execute([$groupId]);
            $alreadyPaid = (float)$paidStmt->fetchColumn();
            $due = round($amount - $alreadyPaid, 2);
            return [
                'type' => 'purchase',
                'label' => $due <= 0 ? 'Purchase (Paid)' : ($alreadyPaid > 0 ? 'Purchase (Partially Paid)' : 'Purchase (Unpaid)'),
                'amount' => $amount, 'paid' => $alreadyPaid, 'due' => max($due, 0),
                'payable' => $due > 0, // whether "Pay Now" should be offered
            ];
        }
        if ($hasSales) {
            // Check for any later receives referencing this group.
            $receivedStmt = $pdo->prepare("
                SELECT COALESCE(SUM(amount), 0) FROM financial_entries
                WHERE account_head = 'accounts_receivable' AND type = 'credit' AND ref = ?
            ");
            $receivedStmt->execute([$groupId]);
            $alreadyReceived = (float)$receivedStmt->fetchColumn();
            $due = round($amount - $alreadyReceived, 2);
            return [
                'type' => 'sale',
                'label' => $due <= 0 ? 'Sale (Received)' : ($alreadyReceived > 0 ? 'Sale (Partially Received)' : 'Sale (Unreceived)'),
                'amount' => $amount, 'received' => $alreadyReceived, 'due' => max($due, 0),
                'payable' => false, 'receivable' => $due > 0,
            ];
        }
        if (!empty($apLegs) && !$hasPurchase) {
            // Standalone AP debit (+bank) = paying down an earlier unpaid purchase
            return ['type' => 'payment', 'label' => 'Vendor Payment', 'amount' => $amount, 'paid' => $amount, 'due' => 0, 'payable' => false];
        }
        if (!empty($arLegs) && !$hasSales) {
            $isRefund = ($arLegs[0]['type'] ?? '') === 'debit';
            return ['type' => $isRefund ? 'refund' : 'receive', 'label' => $isRefund ? 'Client Refund' : 'Client Receive', 'amount' => $amount, 'received' => $isRefund ? 0 : $amount, 'due' => 0, 'payable' => false];
        }
        return ['type' => 'other', 'label' => 'Transaction', 'amount' => $amount, 'due' => 0, 'payable' => false];
    }

    echo json_encode([
        'success' => true,
        // Grouped for the UI: each item is one transaction with its legs.
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
        // Flat list kept for any caller still expecting the old shape.
        'finStmts' => $rows,
        'summary'  => [
            'total_deposit'         => $totalDeposit,
            'total_vendor_payment'  => $totalVendorPayment,
            'total_payable_open'    => $totalPayable,
            'total_receivable_open' => $totalReceivable,
            'net_refund_charge_profit' => $netRefundChargeProfit,
            'vendor_refund_pending'    => $vendorRefundPendingRound,
            'client_refund_pending'    => $clientRefundPendingRound,
            'balance'               => round($totalDeposit - $totalVendorPayment, 2),
            'entry_count'           => count($rows),
            'group_count'           => count($groups),
            'file_count'            => $fileCount,
        ],
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}