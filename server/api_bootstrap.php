<?php
/**
 * api_bootstrap.php  —  Shared API header & error setup (HARDENING)
 * =================================================================
 * Drop-in replacement for the repeated block found at the top of the API files:
 *
 *     header('Content-Type: application/json');
 *     header('Access-Control-Allow-Origin: *');
 *     error_reporting(E_ALL);
 *     ini_set('display_errors', 1);
 *
 * Instead, each API file should do:
 *
 *     require_once __DIR__ . '/../../server/api_bootstrap.php';
 *
 * What this does:
 *   - Sets Content-Type: application/json
 *   - Restricts CORS to ALLOWED_ORIGINS from .env (no more wildcard *)
 *   - In production (APP_ENV=production): logs errors, hides them from output
 *   - In development: shows errors on screen
 *
 * It is intentionally lightweight and safe to require from any api/ file.
 */

require_once __DIR__ . '/env.php';

// ---- JSON content type ----
header('Content-Type: application/json');

// ---- CORS: restrict to known origins ----
$allowed = array_filter(array_map('trim', explode(',', (string)env('ALLOWED_ORIGINS', ''))));
$origin  = $_SERVER['HTTP_ORIGIN'] ?? '';

if ($origin !== '' && in_array($origin, $allowed, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
    header('Access-Control-Allow-Credentials: true');
} elseif (!empty($allowed)) {
    // Same-origin / server-to-server calls have no Origin header; that's fine.
    // For disallowed cross-origin requests we simply omit the ACAO header.
    header('Access-Control-Allow-Origin: ' . $allowed[0]);
    header('Vary: Origin');
}

header('Access-Control-Allow-Methods: GET, POST, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Short-circuit CORS preflight
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ---- Error handling by environment ----
if (env('APP_ENV', 'production') === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);       // still log everything
    ini_set('display_errors', '0'); // but never show errors in the JSON response
    ini_set('log_errors', '1');
}