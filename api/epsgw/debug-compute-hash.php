<?php
// PATH: /api/epsgw/debug-compute-hash.php
// (epsgw = EPS Gateway)
//
// TEMPORARY DIAGNOSTIC ONLY — delete this file once GetToken is working.
// Computes the x-hash for GetToken using the actual configured
// EPS_USERNAME + EPS_HASH_KEY_GETTOKEN from epsgw_gateway.php, so you can
// copy the result directly into Postman's x-hash header without ever
// needing to share the raw hash key with anyone else.

require_once '../../server/epsgw_gateway.php';

header('Content-Type: text/plain');

echo "Username being hashed: " . EPS_USERNAME . "\n\n";
$hash = epsgwComputeHash(EPS_USERNAME, EPS_HASH_KEY_GETTOKEN);
echo "x-hash to paste into Postman:\n{$hash}\n";