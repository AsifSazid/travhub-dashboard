<?php
// PATH: /api/epsgw/debug-gettoken.php
// (epsgw = EPS Gateway)
//
// TEMPORARY DIAGNOSTIC ONLY — delete this file once GetToken is working.
// Calls EPS's GetToken directly and dumps the raw response (HTTP code +
// full body), bypassing epsgwGetToken()'s exception-wrapping so the actual
// EPS errorMessage/errorCode (or raw non-JSON body, if EPS returned HTML/
// plain text instead) is visible for diagnosis.

require_once '../../server/epsgw_gateway.php';

header('Content-Type: text/plain');

echo "Base URL: " . EPS_BASE_URL . "\n";
echo "Username: " . EPS_USERNAME . "\n";
echo "Password set: " . (EPS_PASSWORD !== 'YOUR_EPS_PASSWORD_HERE' ? 'yes' : 'NO -- still placeholder!') . "\n";
echo "GetToken hash key set: " . (EPS_HASH_KEY_GETTOKEN !== 'YOUR_GETTOKEN_HASH_KEY_HERE' ? 'yes' : 'NO -- still placeholder!') . "\n\n";

$hash = epsgwComputeHash(EPS_USERNAME, EPS_HASH_KEY_GETTOKEN);
echo "Computed x-hash: {$hash}\n\n";

$ch = curl_init(EPS_BASE_URL . '/Auth/GetToken');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST  => 'POST',
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'x-hash: ' . $hash],
    CURLOPT_POSTFIELDS     => json_encode(['userName' => EPS_USERNAME, 'password' => EPS_PASSWORD]),
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_SSL_VERIFYPEER => true,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "HTTP Status: {$httpCode}\n";
echo "cURL error (if any): " . ($curlError ?: '(none)') . "\n\n";
echo "Raw response body:\n";
echo "----------------------------------------\n";
echo $response . "\n";
echo "----------------------------------------\n";