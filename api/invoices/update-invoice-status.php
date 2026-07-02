<?php
// api/invoices/update-invoice-status.php
// Changes from original:
//   - শুধু invoices table update নয়, linked financial_entries এও is_paid update হবে
//   - Partial payment support
//   - Overpayment → advance entry (related_type=6)
//   - Client advance balance check
//   - invoice status: 0=pending, 1=paid, 2=partial, 4=overdue

session_start();
header('Content-Type: application/json');
require_once '../../server/db_connection.php';
require_once '../../server/uuid_with_system_id_generator.php';
require_once '../../server/generate_meta_data.php';

$input      = json_decode(file_get_contents("php://input"), true);
$invoice_id = $input['invoice_id']   ?? '';
$action     = $input['action']       ?? 'full'; // 'full' | 'partial' | 'check_advance'
$paidAmount = isset($input['paid_amount']) ? (float)$input['paid_amount'] : null;
$useAdvance = isset($input['use_advance']) ? (bool)$input['use_advance'] : false;
$particular = $input['particular']   ?? 'Invoice Payment';
$user_name  = $_SESSION['user_name'] ?? 'system';

if (empty($invoice_id)) {
    echo json_encode(['success' => false, 'message' => 'Invoice ID is required']);
    exit();
}

try {
    /* ===== 1. Invoice fetch ===== */
    $stmt = $pdo->prepare("
        SELECT * FROM invoices WHERE sys_id = ?
    ");
    $stmt->execute([$invoice_id]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$invoice) {
        echo json_encode(['success' => false, 'message' => 'Invoice not found']);
        exit();
    }

    $totalAmount    = (float)$invoice['total_amount'];
    $alreadyPaid    = (float)$invoice['paid_amount'];
    $dueAmount      = (float)$invoice['due_amount'];
    $clientSysId    = $invoice['client_sys_id'];
    $clientName     = $invoice['client_name'];
    $feIds          = json_decode($invoice['financial_entry_ids'] ?? '[]', true) ?: [];

    /* ===== 2. Check advance (action=check_advance) ===== */
    if ($action === 'check_advance') {
        $advStmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0) as advance_balance
            FROM financial_entries
            WHERE user_sys_id = ?
            AND user_type = 'client'
            AND type = 'credit'
            AND related_type = 6
            AND is_paid = 0
        ");
        $advStmt->execute([$clientSysId]);
        $advanceBalance = (float)$advStmt->fetchColumn();

        echo json_encode([
            'success'          => true,
            'advance_balance'  => $advanceBalance,
            'due_amount'       => $dueAmount,
            'net_due'          => max(0, $dueAmount - $advanceBalance),
            'can_use_advance'  => $advanceBalance > 0
        ]);
        exit();
    }

    $pdo->beginTransaction();

    /* ===== 3. Advance balance calculation ===== */
    $advanceUsed = 0;
    if ($useAdvance) {
        $advStmt = $pdo->prepare("
            SELECT sys_id, amount FROM financial_entries
            WHERE user_sys_id = ?
            AND user_type = 'client'
            AND type = 'credit'
            AND related_type = 6
            AND is_paid = 0
            ORDER BY date ASC
        ");
        $advStmt->execute([$clientSysId]);
        $advanceEntries = $advStmt->fetchAll(PDO::FETCH_ASSOC);

        $remaining = $paidAmount ?? $dueAmount;
        foreach ($advanceEntries as $adv) {
            if ($remaining <= 0) break;
            $useAmt = min((float)$adv['amount'], $remaining);
            $advanceUsed += $useAmt;
            $remaining   -= $useAmt;

            // Advance entry mark করি
            $pdo->prepare("UPDATE financial_entries SET is_paid = 1 WHERE sys_id = ?")
                ->execute([$adv['sys_id']]);
        }
    }

    /* ===== 4. Total coverage calculation ===== */
    $payNow      = ($paidAmount !== null) ? $paidAmount : $dueAmount;
    $totalNowPaid= $alreadyPaid + $payNow + $advanceUsed;
    $newDue      = max(0, $totalAmount - $totalNowPaid);

    // Status determine
    $overpayment = 0;
    if ($totalNowPaid >= $totalAmount - 0.01) {
        $newStatus   = 1; // paid
        $overpayment = max(0, $totalNowPaid - $totalAmount);
    } elseif ($totalNowPaid > 0) {
        $newStatus = 2; // partial
    } else {
        $newStatus = 0; // pending
    }

    /* ===== 5. Update financial_entries is_paid ===== */
    if (!empty($feIds) && $newStatus === 1) {
        // Full paid — সব linked entries is_paid=1
        $ph = implode(',', array_fill(0, count($feIds), '?'));
        $pdo->prepare("
            UPDATE financial_entries
            SET is_paid = 1
            WHERE sys_id IN ($ph)
        ")->execute($feIds);

    } elseif (!empty($feIds) && $newStatus === 2) {
        // Partial — oldest-first match করে is_partial=1
        $feStmt = $pdo->prepare("
            SELECT sys_id, amount FROM financial_entries
            WHERE sys_id IN (" . implode(',', array_fill(0, count($feIds), '?')) . ")
            ORDER BY date ASC, id ASC
        ");
        $feStmt->execute($feIds);
        $feEntries = $feStmt->fetchAll(PDO::FETCH_ASSOC);

        $remaining = $totalNowPaid;
        foreach ($feEntries as $fe) {
            $amt = (float)$fe['amount'];
            if ($remaining >= $amt - 0.01) {
                // This entry fully covered
                $pdo->prepare("UPDATE financial_entries SET is_paid=1, is_partial=0 WHERE sys_id=?")
                    ->execute([$fe['sys_id']]);
                $remaining -= $amt;
            } elseif ($remaining > 0) {
                // Partially covered
                $pdo->prepare("UPDATE financial_entries SET is_partial=1 WHERE sys_id=?")
                    ->execute([$fe['sys_id']]);
                $remaining = 0;
            }
        }
    }

    /* ===== 6. Overpayment → Advance entry (related_type=6) ===== */
    $advanceSysId = null;
    if ($overpayment > 0.01) {
        $advUUIDs = generateIDs('financial_entries');
        $advMeta  = buildMetaData(null, $user_name);
        $pdo->prepare("
            INSERT INTO financial_entries
            (uuid, sys_id, user_sys_id, user_name, user_type,
             date, purpose, type, related_type,
             is_paid, is_partial, is_discounted,
             amount, ref, meta_data)
            VALUES
            (?, ?, ?, ?, 'client',
             NOW(), ?, 'credit', 6,
             0, 0, 0,
             ?, ?, ?)
        ")->execute([
            $advUUIDs['uuid'],
            $advUUIDs['sys_id'],
            $clientSysId,
            $clientName,
            'Advance from Invoice: ' . $invoice_id,
            $overpayment,
            $invoice_id,
            $advMeta
        ]);
        $advanceSysId = $advUUIDs['sys_id'];
    }

    /* ===== 7. Update invoices table ===== */
    $metaArray = json_decode($invoice['meta_data'] ?? '{}', true) ?: [];
    if (!isset($metaArray['updated_by_date'])) $metaArray['updated_by_date'] = [];
    array_unshift($metaArray['updated_by_date'], [
        'user' => $user_name,
        'date' => date('d-m-Y H:i'),
        'action' => $newStatus === 1 ? 'Marked as Paid' : 'Partial Payment'
    ]);

    $pdo->prepare("
        UPDATE invoices
        SET paid_amount = ?,
            due_amount  = ?,
            status      = ?,
            meta_data   = ?,
            updated_at  = NOW()
        WHERE sys_id = ?
    ")->execute([
        $totalNowPaid,
        $newDue,
        $newStatus,
        json_encode($metaArray, JSON_PRETTY_PRINT),
        $invoice_id
    ]);

    $pdo->commit();

    echo json_encode([
        'success'          => true,
        'message'          => $newStatus === 1
            ? ($advanceSysId ? 'Paid with advance balance recorded' : 'Invoice marked as paid')
            : 'Partial payment recorded',
        'status'           => $newStatus,
        'total_paid'       => $totalNowPaid,
        'due_amount'       => $newDue,
        'advance_used'     => $advanceUsed,
        'overpayment'      => $overpayment,
        'advance_entry_id' => $advanceSysId
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Invoice update error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}