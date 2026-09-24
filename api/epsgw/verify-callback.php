<?php
// PATH: /api/epsgw/verify-callback.php
// (epsgw = EPS Gateway -- distinct from api/eps/, the unrelated payroll module)
//
// The browser lands here after EPS redirects back (successUrl/failUrl/
// cancelUrl in initiate.php all point here, distinguished by ?result=).
// This endpoint NEVER trusts that query string alone -- EPS's guide
// documents no server-to-server webhook, so this calls
// epsgwVerifyTransaction() itself to get the real, authoritative status
// before showing the client anything or touching the database.
//
// GET { invoice_id, token, result }
//   result is only a UX hint for which EPS button the client clicked
//   (success/fail/cancel) -- the actual truth comes from verify below.
//
// On confirmed success:
//   - invoices.status is updated to reflect payment (client sees "Paid"
//     immediately)
//   - a gateway_payments row is marked 'pending_settlement' -- NOT yet
//     posted to accounts_payable/ac_banking. Per the user's explicit
//     instruction, the real money can take 1-3 business days to actually
//     land in the company's bank account, so accounting settlement is a
//     SEPARATE manual step (see settle.php), exactly like how cheque/BFTN
//     instruments are held pending clearance elsewhere in this system.
//
// Renders a simple, self-contained HTML result page (this is a browser
// redirect target, not an API consumed by JS).

session_start();

require '../../server/db_connection.php';
require_once '../../server/epsgw_gateway.php';

$invoiceId = $_GET['invoice_id'] ?? '';
$token     = $_GET['token'] ?? '';

function renderResultPage(string $title, string $message, bool $success): void
{
    $color = $success ? '#059669' : '#dc2626';
    $icon  = $success ? '&#10003;' : '&#10007;';
    header('Content-Type: text/html; charset=utf-8');
    echo <<<HTML
    <!DOCTYPE html>
    <html><head><meta charset="UTF-8"><title>{$title}</title>
    <style>
        body { font-family: Arial, sans-serif; background:#f8fafc; display:flex; align-items:center; justify-content:center; height:100vh; margin:0; }
        .card { background:#fff; padding:40px; border-radius:16px; box-shadow:0 4px 20px rgba(0,0,0,.08); text-align:center; max-width:400px; }
        .icon { font-size:48px; color:{$color}; margin-bottom:16px; }
        h1 { font-size:20px; color:#111; margin:0 0 8px; }
        p { color:#666; font-size:14px; }
    </style></head>
    <body><div class="card">
        <div class="icon">{$icon}</div>
        <h1>{$title}</h1>
        <p>{$message}</p>
    </div></body></html>
    HTML;
    exit;
}

if (!$invoiceId || !$token) {
    renderResultPage('Invalid Link', 'This payment link is missing required information.', false);
}

try {
    $stmt = $pdo->prepare("SELECT * FROM invoices WHERE sys_id = ? AND public_token = ?");
    $stmt->execute([$invoiceId, $token]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$invoice) {
        renderResultPage('Invalid Link', 'This payment link is not valid.', false);
    }

    /* ================= Find the most recent pending attempt for this invoice ================= */
    $gpStmt = $pdo->prepare("
        SELECT * FROM gateway_payments
        WHERE invoice_sys_id = ? AND status = 'initiated'
        ORDER BY id DESC LIMIT 1
    ");
    $gpStmt->execute([$invoiceId]);
    $gatewayPayment = $gpStmt->fetch(PDO::FETCH_ASSOC);

    if (!$gatewayPayment) {
        renderResultPage('No Pending Payment', 'No pending payment attempt was found for this invoice.', false);
    }

    /* ================= THE authoritative check -- never trust $_GET['result'] ================= */
    $verification = epsgwVerifyTransaction($pdo, $gatewayPayment['merchant_transaction_id']);

    $isSuccess = strtolower($verification['Status'] ?? '') === 'success';

    if (!$isSuccess) {
        $pdo->prepare("UPDATE gateway_payments SET status = 'failed', gateway_response = ? WHERE sys_id = ?")
            ->execute([json_encode($verification), $gatewayPayment['sys_id']]);
        renderResultPage('Payment Not Completed', 'Your payment could not be confirmed. Please try again or contact us if you were charged.', false);
    }

    $confirmedAmount = (float)($verification['TotalAmount'] ?? 0);

    // Sanity check: the confirmed amount should match what we asked for.
    // If EPS somehow confirms a different amount, don't silently trust it --
    // flag for manual review rather than recording a mismatched figure.
    $expectedAmount = (float)$gatewayPayment['amount'];
    if (abs($confirmedAmount - $expectedAmount) > 0.01) {
        $pdo->prepare("UPDATE gateway_payments SET status = 'amount_mismatch', gateway_response = ? WHERE sys_id = ?")
            ->execute([json_encode($verification), $gatewayPayment['sys_id']]);
        renderResultPage('Payment Needs Review', 'Your payment was received but the amount did not match exactly. Our team will review and confirm shortly.', true);
    }

    $pdo->beginTransaction();

    /* ================= Mark this gateway payment as pending settlement ================= */
    // Deliberately NOT touching ac_banking/financial_entries here -- see
    // settle.php for that step, done manually once the money actually lands.
    $pdo->prepare("
        UPDATE gateway_payments
        SET status = 'pending_settlement', eps_transaction_id = ?, gateway_response = ?, confirmed_at = NOW()
        WHERE sys_id = ?
    ")->execute([
        $verification['EpsTransactionId'] ?? $gatewayPayment['eps_transaction_id'],
        json_encode($verification),
        $gatewayPayment['sys_id'],
    ]);

    /* ================= Update the invoice's own paid/due status ================= */
    // This is what makes the client see "Paid" immediately, independent of
    // when the money is actually settled into ac_banking.
    $totalAmount   = (float)$invoice['total_amount'];
    $alreadyPaid   = (float)$invoice['paid_amount'];
    $newPaidAmount = $alreadyPaid + $confirmedAmount;
    $newDueAmount  = max($totalAmount - $newPaidAmount, 0);
    $newStatus     = $newDueAmount <= 0.009 ? 1 : ($newPaidAmount > 0 ? 2 : 0); // 1=paid, 2=partial, 0=unpaid -- matches update-invoice-status.php's convention

    $pdo->prepare("UPDATE invoices SET paid_amount = ?, due_amount = ?, status = ?, updated_at = NOW() WHERE sys_id = ?")
        ->execute([$newPaidAmount, $newDueAmount, $newStatus, $invoiceId]);

    $pdo->commit();

    renderResultPage(
        'Payment Successful',
        "Thank you! Your payment of ৳" . number_format($confirmedAmount, 2) . " has been received and your invoice is now " . ($newStatus === 1 ? 'fully paid' : 'partially paid') . ".",
        true
    );

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    renderResultPage('Error', 'Something went wrong while confirming your payment. Please contact us with your payment details.', false);
}