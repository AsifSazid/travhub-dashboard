<?php
// ============================================================
// TravHub Gen-3 — Employee Credentials API
// POST /api/credentials/endpoints.php
// Body: { action, ...params }
//
// PRIVACY: Only the logged-in employee can see/edit their own
// credentials. HR and Super Admin have ZERO access here.
// Passwords are AES-256 encrypted before storage.
// ============================================================

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require '../../server/db_connection.php';

// ── Auth ──────────────────────────────────────────────────
if (empty($_SESSION['user_email'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthenticated']);
    exit;
}
$myId = $_SESSION['user_id'] ?? '';

// ── Encryption helpers ────────────────────────────────────
// Key derived from APP_CRED_KEY env var (set in server config / .env)
// Never log or expose the raw key.
define('CRED_ENC_KEY', hash('sha256', getenv('APP_CRED_KEY') ?: 'travhub-default-key-change-in-production', true));
define('CRED_CIPHER',  'aes-256-cbc');

function encryptPassword(string $plain): string {
    $iv  = random_bytes(16);
    $enc = openssl_encrypt($plain, CRED_CIPHER, CRED_ENC_KEY, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $enc);
}

function decryptPassword(string $stored): string {
    $data = base64_decode($stored);
    if (strlen($data) < 17) return '';
    $iv  = substr($data, 0, 16);
    $enc = substr($data, 16);
    return openssl_decrypt($enc, CRED_CIPHER, CRED_ENC_KEY, OPENSSL_RAW_DATA, $iv) ?: '';
}

// ── Input ─────────────────────────────────────────────────
$raw    = file_get_contents('php://input');
$body   = json_decode($raw, true) ?? [];
$action = trim($body['action'] ?? $_GET['action'] ?? '');

// ── Helpers ───────────────────────────────────────────────
function jsonOk(array $data): void { echo json_encode(['success'=>true] + $data); exit; }
function jsonErr(string $msg, int $code=400): void {
    http_response_code($code);
    echo json_encode(['success'=>false,'message'=>$msg]);
    exit;
}

// ── Router ────────────────────────────────────────────────
switch ($action) {

    // ── LIST (no passwords returned) ──────────────────────
    case 'list': {
        $stmt = $pdo->prepare(
            "SELECT sys_id, title, url, username, notes, created_at, updated_at
             FROM employee_credentials
             WHERE employee_sys_id = ?
             ORDER BY title"
        );
        $stmt->execute([$myId]);
        jsonOk(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    // ── GET ONE (with decrypted password) ─────────────────
    case 'get': {
        $sysId = trim($body['sys_id'] ?? '');
        if (!$sysId) jsonErr('sys_id is required.');

        $stmt = $pdo->prepare(
            "SELECT * FROM employee_credentials WHERE sys_id=? AND employee_sys_id=?"
        );
        $stmt->execute([$sysId, $myId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) jsonErr('Not found.', 404);

        $row['password'] = $row['password_enc'] ? decryptPassword($row['password_enc']) : '';
        unset($row['password_enc']);
        jsonOk(['data' => $row]);
    }

    // ── ADD ────────────────────────────────────────────────
    case 'add': {
        $title    = trim($body['title']    ?? '');
        $url      = trim($body['url']      ?? '') ?: null;
        $username = trim($body['username'] ?? '') ?: null;
        $password = $body['password'] ?? '';
        $notes    = trim($body['notes']    ?? '') ?: null;

        if (!$title) jsonErr('title is required.');

        $enc   = $password !== '' ? encryptPassword($password) : null;
        $sysId = 'CRED-' . strtoupper(uniqid());

        $pdo->prepare(
            "INSERT INTO employee_credentials
                (sys_id, employee_sys_id, title, url, username, password_enc, notes)
             VALUES (?,?,?,?,?,?,?)"
        )->execute([$sysId, $myId, $title, $url, $username, $enc, $notes]);

        jsonOk(['message' => 'Credential saved.', 'sys_id' => $sysId]);
    }

    // ── UPDATE ────────────────────────────────────────────
    case 'update': {
        $sysId    = trim($body['sys_id']   ?? '');
        $title    = trim($body['title']    ?? '');
        $url      = trim($body['url']      ?? '') ?: null;
        $username = trim($body['username'] ?? '') ?: null;
        $notes    = trim($body['notes']    ?? '') ?: null;

        if (!$sysId) jsonErr('sys_id is required.');
        if (!$title) jsonErr('title is required.');

        // Verify ownership
        $stmt = $pdo->prepare("SELECT sys_id, password_enc FROM employee_credentials WHERE sys_id=? AND employee_sys_id=?");
        $stmt->execute([$sysId, $myId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) jsonErr('Not found.', 404);

        // Only update password if provided
        $enc = $row['password_enc'];
        if (isset($body['password']) && $body['password'] !== '') {
            $enc = encryptPassword($body['password']);
        }

        $pdo->prepare(
            "UPDATE employee_credentials
             SET title=?, url=?, username=?, password_enc=?, notes=?
             WHERE sys_id=? AND employee_sys_id=?"
        )->execute([$title, $url, $username, $enc, $notes, $sysId, $myId]);

        jsonOk(['message' => 'Credential updated.']);
    }

    // ── DELETE ────────────────────────────────────────────
    case 'delete': {
        $sysId = trim($body['sys_id'] ?? '');
        if (!$sysId) jsonErr('sys_id is required.');

        $stmt = $pdo->prepare(
            "DELETE FROM employee_credentials WHERE sys_id=? AND employee_sys_id=?"
        );
        $stmt->execute([$sysId, $myId]);
        if ($stmt->rowCount() === 0) jsonErr('Not found.', 404);

        jsonOk(['message' => 'Credential deleted.']);
    }

    default:
        jsonErr("Unknown action: $action");
}