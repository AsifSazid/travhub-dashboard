<?php
/**
 * FILE PATH: api/travelers/credentials.php
 *
 * Traveler credentials (portal logins) — নিজস্ব `travelers.credentials`
 * (LONGTEXT/JSON) কলামে সেভ হয়, others_info-তে না (আলাদা রাখা হয়েছে যাতে
 * password এনক্রিপশন logic অন্য info-section-এর সাথে না জড়ায়)।
 *
 * password ছাড়া বাকি field (portal, url, username, notes, updated_at)
 * plaintext, শুধু password AES-256-GCM দিয়ে এনক্রিপ্ট হয়ে DB-তে যায়।
 * GET-এ response পাঠানোর আগে decrypt করে plaintext password ফেরত দেওয়া হয়।
 *
 * GET  ?traveler_id=THR-TR-...          → { success, credentials: [...] } (decrypted)
 * POST { traveler_id, credentials: [...] } → পুরো list overwrite করে সেভ (encrypted)
 */

session_start();
require '../../server/db_connection.php';
require '../../server/credential_crypto.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $travelerId = trim($_GET['traveler_id'] ?? '');
        if (!$travelerId) jsonOut(['success' => false, 'message' => 'traveler_id প্রয়োজন']);

        $stmt = $pdo->prepare("SELECT credentials FROM travelers WHERE sys_id = ? LIMIT 1");
        $stmt->execute([$travelerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) jsonOut(['success' => false, 'message' => 'Traveler পাওয়া যায়নি']);

        $list = json_decode($row['credentials'] ?? '[]', true) ?: [];

        // response পাঠানোর আগে প্রতিটা password decrypt করো
        foreach ($list as &$c) {
            $c['password'] = credDecrypt($c['password'] ?? '');
        }
        unset($c);

        jsonOut(['success' => true, 'credentials' => $list]);

    } elseif ($method === 'POST') {
        $body        = json_decode(file_get_contents('php://input'), true) ?: [];
        $travelerId  = trim($body['traveler_id'] ?? '');
        $credentials = $body['credentials'] ?? null;

        if (!$travelerId) jsonOut(['success' => false, 'message' => 'traveler_id প্রয়োজন']);
        if (!is_array($credentials)) jsonOut(['success' => false, 'message' => 'credentials array হিসেবে দিতে হবে']);

        $stmt = $pdo->prepare("SELECT sys_id FROM travelers WHERE sys_id = ? LIMIT 1");
        $stmt->execute([$travelerId]);
        if (!$stmt->fetch()) jsonOut(['success' => false, 'message' => 'Traveler পাওয়া যায়নি']);

        // সেভ করার আগে প্রতিটা password এনক্রিপ্ট করো — plaintext কখনো DB-তে
        // যাবে না। খালি চিহ্নিত করার জন্য: শুধু non-empty password-ই এনক্রিপ্ট
        // হয়, খালি স্ট্রিং খালিই থাকে।
        $sanitized = [];
        foreach ($credentials as $c) {
            $sanitized[] = [
                'portal'     => trim((string)($c['portal']     ?? '')),
                'url'        => trim((string)($c['url']        ?? '')),
                'username'   => trim((string)($c['username']   ?? '')),
                'password'   => credEncrypt((string)($c['password'] ?? '')),
                'notes'      => trim((string)($c['notes']      ?? '')),
                'updated_at' => (string)($c['updated_at'] ?? date('d/m/Y, H:i:s')),
            ];
        }

        $pdo->prepare("UPDATE travelers SET credentials = ? WHERE sys_id = ?")
            ->execute([json_encode($sanitized, JSON_UNESCAPED_UNICODE), $travelerId]);

        jsonOut(['success' => true, 'message' => 'Credentials সেভ হয়েছে']);

    } else {
        jsonOut(['success' => false, 'message' => 'Unsupported method']);
    }
} catch (Throwable $e) {
    error_log('[credentials.php] ' . $e->getMessage());
    jsonOut(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

function jsonOut(array $d): never { echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }