<?php
/**
 * FILE PATH: /server/epsgw_gateway.php
 * (epsgw = EPS Gateway, deliberately distinct from api/eps/ which is the
 *  unrelated Employee Payroll System module — same three-letter initials,
 *  completely different feature, kept in separate namespaces so nobody
 *  confuses "EPS" the payment gateway with "EPS" the payroll system.)
 *
 * EPS (Easy Payment System, eps.com.bd) merchant API client.
 * Based on "EPS Merchant API Integration Guide V0.5" (Jan 2024).
 *
 * ⚠️ PLACEHOLDER CREDENTIALS — replace every EPS_* constant below with the
 * real values from your EPS merchant panel before going live. Sandbox and
 * production have different base URLs (see EPS_BASE_URL).
 *
 * Flow:
 *   1. epsgwGetToken()      -> Bearer JWT, cached in ac_gateway_tokens until expiry
 *   2. epsgwInitializePayment($data) -> RedirectURL to send the client's browser to
 *   3. epsgwVerifyTransaction($merchantTransactionId) -> poll for the real status
 *      (successUrl/failUrl/cancelUrl are browser redirects only -- EPS's
 *      guide documents no server-to-server webhook, so epsgwVerifyTransaction()
 *      MUST be called server-side after the browser returns to successUrl;
 *      never trust the redirect's query string alone, since a client could
 *      forge it)
 */

// ================= PLACEHOLDER CREDENTIALS — REPLACE BEFORE USE =================
define('EPS_BASE_URL', 'https://sandboxpgapi.eps.com.bd/v1'); // sandbox; use https://pgapi.eps.com.bd/v1 for production
define('EPS_USERNAME', 'YOUR_EPS_USERNAME_HERE');
define('EPS_PASSWORD', 'YOUR_EPS_PASSWORD_HERE');
define('EPS_MERCHANT_ID', 'YOUR_MERCHANT_ID_HERE');
define('EPS_STORE_ID', 'YOUR_STORE_ID_HERE');
// Hash keys — EPS gives one hash key per API; the guide's sample shows a
// DIFFERENT key for GetToken vs the other two calls. Confirm with EPS
// whether InitializeEPS and CheckMerchantTransactionStatus share one key
// or need their own — placeholder assumes they share one for now.
define('EPS_HASH_KEY_GETTOKEN', 'YOUR_GETTOKEN_HASH_KEY_HERE');
define('EPS_HASH_KEY_TRANSACTION', 'YOUR_TRANSACTION_HASH_KEY_HERE');
// ================================================================================

/**
 * HMAC-SHA512 hash, per EPS's documented mechanism:
 *   1. Encode Hash Key using UTF8
 *   2. Create HMACSHA512 using encoded data
 *   3. Compute hash using that hmac and the given value
 *   4. Return Base64 string
 */
function epsgwComputeHash(string $value, string $hashKey): string
{
    $rawHmac = hash_hmac('sha512', $value, $hashKey, true);
    return base64_encode($rawHmac);
}

/**
 * Generic cURL POST/GET helper for EPS calls.
 */
function epsgwHttpRequest(string $method, string $url, array $headers, ?array $body = null): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 30,
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($error) {
        throw new Exception("EPS request failed: {$error}");
    }

    $decoded = json_decode($response, true);
    if ($decoded === null) {
        throw new Exception("EPS returned non-JSON response (HTTP {$httpCode}): " . substr($response, 0, 500));
    }

    return ['http_code' => $httpCode, 'body' => $decoded];
}

/**
 * Step 1 — GetToken. Caches the token in ac_gateway_tokens (see migration
 * checklist) so we don't re-authenticate on every single payment request;
 * re-fetches once the cached token's expireDate has passed.
 */
function epsgwGetToken(PDO $pdo): string
{
    // Check cache first
    $stmt = $pdo->prepare("SELECT token, expire_date FROM ac_gateway_tokens WHERE gateway = 'eps' ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $cached = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cached && strtotime($cached['expire_date']) > time() + 60) { // 60s safety margin
        return $cached['token'];
    }

    $hash = epsgwComputeHash(EPS_USERNAME, EPS_HASH_KEY_GETTOKEN);

    $result = epsgwHttpRequest('POST', EPS_BASE_URL . '/Auth/GetToken', [
        'Content-Type: application/json',
        'x-hash: ' . $hash,
    ], [
        'userName' => EPS_USERNAME,
        'password' => EPS_PASSWORD,
    ]);

    $body = $result['body'];
    if (!empty($body['errorMessage']) || empty($body['token'])) {
        throw new Exception('EPS GetToken failed: ' . ($body['errorMessage'] ?? 'unknown error'));
    }

    $pdo->prepare("INSERT INTO ac_gateway_tokens (gateway, token, expire_date, created_at) VALUES ('eps', ?, ?, NOW())")
        ->execute([$body['token'], $body['expireDate']]);

    return $body['token'];
}

/**
 * Step 2 — InitializeEPS. Returns ['transaction_id' => ..., 'redirect_url' => ...].
 *
 * $data expects (all the fields EPS's guide marks Mandatory):
 *   customer_order_id, merchant_transaction_id (unique, >=10 digits),
 *   total_amount, success_url, fail_url, cancel_url,
 *   customer_name, customer_email, customer_address, customer_city,
 *   customer_state, customer_postcode, customer_country, customer_phone,
 *   product_name
 */
function epsgwInitializePayment(PDO $pdo, array $data): array
{
    $token = epsgwGetToken($pdo);
    $hash  = epsgwComputeHash($data['merchant_transaction_id'], EPS_HASH_KEY_TRANSACTION);

    $body = [
        'storeId'               => EPS_STORE_ID,
        'CustomerOrderId'       => $data['customer_order_id'],
        'merchantTransactionId' => $data['merchant_transaction_id'],
        'transactionTypeId'     => 1, // 1 = Web (per EPS's Transaction Type ID table)
        'financialEntityId'     => 0, // 0 = let the customer choose their bank/wallet on EPS's page
        'transitionStatusId'    => 0,
        'totalAmount'           => $data['total_amount'],
        'ipAddress'             => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        'version'               => '1',
        'successUrl'            => $data['success_url'],
        'failUrl'               => $data['fail_url'],
        'cancelUrl'             => $data['cancel_url'],
        'customerName'          => $data['customer_name'],
        'customerEmail'         => $data['customer_email'],
        'customerAddress'       => $data['customer_address'],
        'customerAddress2'      => $data['customer_address'],
        'customerCity'          => $data['customer_city'],
        'customerState'         => $data['customer_state'] ?? $data['customer_city'],
        'customerPostcode'      => $data['customer_postcode'] ?? '0000',
        'customerCountry'       => $data['customer_country'] ?? 'BD',
        'customerPhone'         => $data['customer_phone'],
        'shipmentName'          => $data['customer_name'],
        'shipmentAddress'       => $data['customer_address'],
        'shipmentAddress2'      => $data['customer_address'],
        'shipmentCity'          => $data['customer_city'],
        'shipmentState'         => $data['customer_state'] ?? $data['customer_city'],
        'shipmentPostcode'      => $data['customer_postcode'] ?? '0000',
        'shipmentCountry'       => $data['customer_country'] ?? 'BD',
        'valueA'                => $data['value_a'] ?? '',
        'valueB'                => $data['value_b'] ?? '',
        'valueC'                => $data['value_c'] ?? '',
        'valueD'                => $data['value_d'] ?? '',
        'shippingMethod'        => 'NO',
        'noOfItem'              => '1',
        'productName'           => $data['product_name'],
        'productProfile'        => 'general',
        'productCategory'       => 'Invoice Payment',
        'ProductList'           => [[
            'ProductName'     => $data['product_name'],
            'NoOfItem'        => '1',
            'ProductProfile'  => 'general',
            'ProductCategory' => 'Invoice Payment',
            'ProductPrice'    => (string)$data['total_amount'],
        ]],
    ];

    $result = epsgwHttpRequest('POST', EPS_BASE_URL . '/EPSEngine/InitializeEPS', [
        'Content-Type: application/json',
        'x-hash: ' . $hash,
        'Authorization: Bearer ' . $token,
    ], $body);

    $respBody = $result['body'];
    if (!empty($respBody['ErrorMessage']) || empty($respBody['RedirectURL'])) {
        throw new Exception('EPS InitializeEPS failed: ' . ($respBody['ErrorMessage'] ?? 'unknown error'));
    }

    return [
        'transaction_id' => $respBody['TransactionId'],
        'redirect_url'   => $respBody['RedirectURL'],
    ];
}

/**
 * Step 3 — Verify Transaction. This is the ONLY source of truth for whether
 * a payment actually succeeded — never trust the successUrl redirect alone.
 * Returns the full decoded response (Status, TotalAmount, EpsTransactionId,
 * FinancialEntity, etc.) so the caller can decide what to record.
 */
function epsgwVerifyTransaction(PDO $pdo, string $merchantTransactionId): array
{
    $token = epsgwGetToken($pdo);
    $hash  = epsgwComputeHash($merchantTransactionId, EPS_HASH_KEY_TRANSACTION);

    $url = EPS_BASE_URL . '/EPSEngine/CheckMerchantTransactionStatus?merchantTransactionId=' . urlencode($merchantTransactionId);

    $result = epsgwHttpRequest('GET', $url, [
        'Content-Type: application/json',
        'x-hash: ' . $hash,
        'Authorization: Bearer ' . $token,
    ]);

    return $result['body'];
}