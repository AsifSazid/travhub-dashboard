<?php
// PATH: /api/epsgw/initiate.php
// (epsgw = EPS Gateway -- distinct from api/eps/, the unrelated payroll module)
//
// Starts an EPS payment session for a given invoice. Called from the public
// pay-invoice.php page when the client clicks "Pay Now".
//
// GET or POST { invoice_id, token }
//   invoice_id -- invoices.sys_id
//   token      -- the invoice's public_token (see migration checklist --
//                 invoices needs a new public_token column); this replaces
//                 login for the client, so it must be verified before doing
//                 anything else here
//
// On success, redirects the browser straight to EPS's RedirectURL (302),
// rather than returning JSON -- this endpoint IS the "Pay Now" link target,
// not an AJAX call.

session_start();

require '../../server/db_connection.php';
require_once '../../server/sys_id_generator_v2.php';
require_once '../../server/epsgw_gateway.php';

$invoiceId = $_REQUEST['invoice_id'] ?? '';
$token     = $_REQUEST['token'] ?? '';

if (!$invoiceId || !$token) {
    http_response_code(400);
    echo 'Missing invoice_id or token';
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM invoices WHERE sys_id = ? AND public_token = ?");
    $stmt->execute([$invoiceId, $token]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$invoice) {
        http_response_code(403);
        echo 'Invalid invoice link';
        exit;
    }

    $dueAmount = (float)($invoice['due_amount'] ?? 0);
    if ($dueAmount <= 0) {
        http_response_code(400);
        echo 'This invoice has no outstanding due amount';
        exit;
    }

    /* ================= Lookup client contact info ================= */
    // invoices.client_info is a JSON snapshot taken at invoice creation time
    // (confirmed via print-invoice.php) -- prefer that since it reflects
    // what was true when the invoice was issued; fall back to a fresh
    // lookup on the clients table (whose phone/address are themselves JSON)
    // if client_info is missing or incomplete.
    $clientInfo = json_decode($invoice['client_info'] ?? '{}', true) ?: [];

    $clientStmt = $pdo->prepare("SELECT * FROM clients WHERE sys_id = ?");
    $clientStmt->execute([$invoice['client_sys_id']]);
    $clientDb = $clientStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $clientDbPhone   = json_decode($clientDb['phone'] ?? '{}', true) ?: [];
    $clientDbAddress = json_decode($clientDb['address'] ?? '{}', true) ?: [];

    $clientName  = $clientInfo['name'] ?? $clientDb['title'] ?? 'Customer';
    $clientEmail = $clientInfo['email'] ?? $clientDb['cc'] ?? 'no-reply@travhubglobal.com'; // EPS requires an email; placeholder if genuinely missing
    $clientPhone = $clientInfo['phone'] ?? ($clientDbPhone['number'] ?? $clientDbPhone[0] ?? null) ?? '01700000000'; // placeholder if missing -- EPS requires a phone
    $clientCity  = $clientDbAddress['city'] ?? 'Dhaka';
    $clientAddr  = $clientInfo['address'] ?? ($clientDbAddress['line1'] ?? $clientDbAddress['full'] ?? null) ?? 'N/A';

    /* ================= Build a unique merchant transaction id ================= */
    // EPS requires this to be unique per attempt (>=10 digits) -- using a
    // timestamp + random suffix so retried payments on the same invoice get
    // a fresh id each time.
    $merchantTransactionId = date('YmdHis') . random_int(1000, 9999);

    // PLACEHOLDER: adjust these URLs to your actual domain once deployed.
    $baseUrl    = 'https://dev.travhub.com.bd/api/epsgw';
    $successUrl = "{$baseUrl}/verify-callback.php?invoice_id=" . urlencode($invoiceId) . "&token=" . urlencode($token) . "&result=success";
    $failUrl    = "{$baseUrl}/verify-callback.php?invoice_id=" . urlencode($invoiceId) . "&token=" . urlencode($token) . "&result=fail";
    $cancelUrl  = "{$baseUrl}/verify-callback.php?invoice_id=" . urlencode($invoiceId) . "&token=" . urlencode($token) . "&result=cancel";

    $result = epsgwInitializePayment($pdo, [
        'customer_order_id'       => $invoice['sys_id'],
        'merchant_transaction_id' => $merchantTransactionId,
        'total_amount'            => $dueAmount,
        'success_url'             => $successUrl,
        'fail_url'                => $failUrl,
        'cancel_url'              => $cancelUrl,
        'customer_name'           => $clientName,
        'customer_email'          => $clientEmail,
        'customer_address'        => $clientAddr,
        'customer_city'           => $clientCity,
        'customer_phone'          => $clientPhone,
        'product_name'            => 'Invoice ' . $invoice['sys_id'],
        'value_a'                 => $invoiceId, // carried through so verify-callback.php can re-derive context if needed
    ]);

    /* ================= Record this attempt (pending) ================= */
    // New table, see migration checklist: gateway_payments
    $ids  = generateV2IDs($pdo, 'gateway_payments');
    $pdo->prepare("
        INSERT INTO gateway_payments
        (uuid, sys_id, invoice_sys_id, client_sys_id, gateway, merchant_transaction_id,
         eps_transaction_id, amount, status, created_at)
        VALUES (?, ?, ?, ?, 'eps', ?, ?, ?, 'initiated', NOW())
    ")->execute([
        $ids['uuid'], $ids['sys_id'], $invoiceId, $invoice['client_sys_id'],
        $merchantTransactionId, $result['transaction_id'], $dueAmount,
    ]);

    header('Location: ' . $result['redirect_url']);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo 'Payment initiation failed: ' . htmlspecialchars($e->getMessage());
}