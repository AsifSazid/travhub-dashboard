<?php
/**
 * FILE PATH: api/master-data/portal-links.php
 *
 * Master Data — Portal Links। একটা portal (e.g. "Thailand e-Visa") এর
 * অধীনে একাধিক credential (account) থাকতে পারে, প্রতিটার নিজস্ব
 * visibility (is_hide + access_user)।
 *
 * Password সবসময় AES-256-GCM দিয়ে DB-তে encrypted থাকে (credential_crypto.php,
 * traveler credentials-এর একই key/method), শুধু viewable হলেই response-এ
 * decrypt হয়ে যায়।
 *
 * Permission logic (কোনো column না, প্রতিবার compute করা হয়):
 *   - portal-এর credentials-এ unique 'created_by' এর সংখ্যা ১ হলে, সেই
 *     একজনই portal-টা edit/delete করতে পারবে।
 *   - ১-এর বেশি (মানে একাধিক employee credential যোগ করেছে) হলে, শুধু
 *     admin (role='0') পারবে।
 *   - যে কেউ নতুন portal তৈরি বা existing portal-এ নতুন credential
 *     (নিজের) যোগ করতে পারবে — এতে কোনো restriction নেই।
 *
 * GET    ?sys_id=... (single) বা কিছু না দিলে সব portal, ?portal_type=... দিয়ে filter
 * POST   { action:'create_portal', portal_name, portal_url, portal_type, credential:{...} }
 * POST   { action:'add_credential', portal_sys_id, credential:{...} }
 * POST   { action:'update_credential', portal_sys_id, cred_id, credential:{...} }
 * POST   { action:'delete_credential', portal_sys_id, cred_id }
 * DELETE ?sys_id=... (পুরো portal মুছে দেয়)
 *
 * credential object (client → server): { user_name, password, is_hide, access_user: [] }
 */

session_start();
require '../../server/db_connection.php';
require '../../server/sys_id_generator_v2.php';
require '../../server/generate_meta_data.php';
require '../../server/credential_crypto.php';
require_once '../../pages/authenticate.php';

header('Content-Type: application/json');
ini_set('display_errors', 0);

$currentUserId = $_SESSION['user_id'] ?? '';
$currentUser   = $_SESSION['user_name'] ?? 'system';
$isAdmin       = (($_SESSION['role'] ?? null) == '0');

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        handleGet($pdo, $currentUserId, $isAdmin);
    } elseif ($method === 'POST') {
        handlePost($pdo, $currentUserId, $currentUser, $isAdmin);
    } elseif ($method === 'DELETE') {
        handleDelete($pdo, $isAdmin);
    } else {
        jsonOut(['success' => false, 'message' => 'Unsupported method']);
    }
} catch (Throwable $e) {
    error_log('[portal-links] ' . $e->getMessage());
    jsonOut(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

// ════════════════════════════════════════════════════════════════════════════
// GET — list বা single portal, visibility-filtered + decrypted
// ════════════════════════════════════════════════════════════════════════════
function handleGet(PDO $pdo, string $currentUserId, bool $isAdmin): void
{
    $sysId = trim($_GET['sys_id'] ?? '');
    $type  = trim($_GET['portal_type'] ?? '');

    if ($sysId) {
        $stmt = $pdo->prepare("SELECT * FROM portal_links WHERE sys_id = ? LIMIT 1");
        $stmt->execute([$sysId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) jsonOut(['success' => false, 'message' => 'Portal পাওয়া যায়নি']);

        jsonOut(['success' => true, 'portal' => shapePortal($row, $currentUserId, $isAdmin)]);
    }

    $sql = "SELECT * FROM portal_links";
    $params = [];
    if ($type) {
        $sql .= " WHERE portal_type = ?";
        $params[] = $type;
    }
    $sql .= " ORDER BY id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $portals = array_map(fn($r) => shapePortal($r, $currentUserId, $isAdmin), $rows);
    jsonOut(['success' => true, 'portals' => $portals]);
}

/**
 * DB row কে API response shape এ রূপান্তর করে — visibility-check অনুযায়ী
 * password decrypt/hide করে, can_edit flag যোগ করে।
 */
function shapePortal(array $row, string $currentUserId, bool $isAdmin): array
{
    $credentials = json_decode($row['credentials'] ?? '[]', true) ?: [];
    $creators    = [];
    $shaped      = [];

    foreach ($credentials as $c) {
        $creatorId = $c['created_by'] ?? '';
        if ($creatorId) $creators[$creatorId] = true;

        $accessList = $c['access_user'] ?? [];
        $canView = $isAdmin
            || empty($c['is_hide'])
            || $creatorId === $currentUserId
            || in_array($currentUserId, $accessList, true);

        $shaped[] = [
            'cred_id'         => $c['cred_id'] ?? '',
            'user_name'       => $c['user_name'] ?? '',
            'password'        => $canView ? credDecrypt($c['password'] ?? '') : null, // null = hidden, blank string আলাদা জিনিস
            'is_hide'         => !empty($c['is_hide']),
            'access_user'     => $accessList,
            'created_by'      => $creatorId,
            'created_by_name' => $c['created_by_name'] ?? '',
            'created_at'      => $c['created_at'] ?? '',
            'can_view'        => $canView,
            'is_mine'         => $creatorId === $currentUserId,
        ];
    }

    // Permission logic: unique creator সংখ্যা ১ হলে সেই একজনই portal-level
    // edit/delete পারবে (নিজের credential-ই থাকা অবস্থায়), নাহলে শুধু admin
    $uniqueCreators = array_keys($creators);
    $canManagePortal = $isAdmin
        || (count($uniqueCreators) === 1 && $uniqueCreators[0] === $currentUserId)
        || count($uniqueCreators) === 0; // কোনো credential-ই নেই এখনো — যে কেউ manage করতে পারবে

    return [
        'sys_id'      => $row['sys_id'],
        'portal_name' => $row['portal_name'],
        'portal_url'  => $row['portal_url'],
        'portal_type' => $row['portal_type'],
        'credentials' => $shaped,
        'can_manage'  => $canManagePortal, // পুরো portal delete / non-owned-credential edit
    ];
}

// ════════════════════════════════════════════════════════════════════════════
// POST — create portal / add / update / delete credential
// ════════════════════════════════════════════════════════════════════════════
function handlePost(PDO $pdo, string $currentUserId, string $currentUser, bool $isAdmin): void
{
    $body   = json_decode(file_get_contents('php://input'), true) ?: [];
    $action = $body['action'] ?? '';

    if ($action === 'create_portal') {
        $name = trim($body['portal_name'] ?? '');
        $url  = trim($body['portal_url']  ?? '');
        $type = trim($body['portal_type'] ?? 'other');
        $cred = $body['credential'] ?? null;

        if (!$name) jsonOut(['success' => false, 'message' => 'Portal name প্রয়োজন']);

        $validTypes = ['air_ticket','hotel','package','visa','umrah','transport','other'];
        if (!in_array($type, $validTypes, true)) $type = 'other';

        $credentials = [];
        if ($cred && !empty($cred['user_name'])) {
            $credentials[] = buildCredentialEntry($cred, $currentUserId, $currentUser);
        }

        $v2 = generateV2IDs($pdo, 'portal_links');
        $metaJson = buildMetaData(null, $currentUser);

        $pdo->prepare("
            INSERT INTO portal_links (uuid, sys_id, portal_name, portal_url, portal_type, credentials, meta_data)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $v2['uuid'], $v2['sys_id'], $name, $url, $type,
            json_encode($credentials, JSON_UNESCAPED_UNICODE), $metaJson,
        ]);

        jsonOut(['success' => true, 'portal_sys_id' => $v2['sys_id']]);

    } elseif ($action === 'add_credential') {
        $portalSysId = trim($body['portal_sys_id'] ?? '');
        $cred        = $body['credential'] ?? null;

        if (!$portalSysId || empty($cred['user_name'])) {
            jsonOut(['success' => false, 'message' => 'portal_sys_id ও credential.user_name প্রয়োজন']);
        }

        [$portal, $credentials] = fetchPortalAndCredentials($pdo, $portalSysId);

        $credentials[] = buildCredentialEntry($cred, $currentUserId, $currentUser);
        savePortalCredentials($pdo, $portal, $credentials, $currentUser);

        jsonOut(['success' => true]);

    } elseif ($action === 'update_credential') {
        $portalSysId = trim($body['portal_sys_id'] ?? '');
        $credId      = trim($body['cred_id'] ?? '');
        $newData     = $body['credential'] ?? null;

        if (!$portalSysId || !$credId || !$newData) {
            jsonOut(['success' => false, 'message' => 'portal_sys_id, cred_id, credential প্রয়োজন']);
        }

        [$portal, $credentials] = fetchPortalAndCredentials($pdo, $portalSysId);

        $found = false;
        foreach ($credentials as &$c) {
            if (($c['cred_id'] ?? '') !== $credId) continue;
            $found = true;

            // শুধু নিজের credential নিজে edit করতে পারবে, নাহলে admin লাগবে
            if (!$isAdmin && ($c['created_by'] ?? '') !== $currentUserId) {
                jsonOut(['success' => false, 'message' => 'শুধু নিজের যোগ করা credential edit করা যাবে (বা admin প্রয়োজন)']);
            }

            if (isset($newData['user_name'])) $c['user_name'] = trim($newData['user_name']);
            if (array_key_exists('password', $newData) && $newData['password'] !== '') {
                $c['password'] = credEncrypt($newData['password']); // খালি পাঠালে পুরনো password-ই থাকবে
            }
            if (isset($newData['is_hide']))     $c['is_hide']     = (bool)$newData['is_hide'];
            if (isset($newData['access_user'])) $c['access_user'] = array_values((array)$newData['access_user']);
        }
        unset($c);

        if (!$found) jsonOut(['success' => false, 'message' => 'এই credential পাওয়া যায়নি']);

        savePortalCredentials($pdo, $portal, $credentials, $currentUser);
        jsonOut(['success' => true]);

    } elseif ($action === 'delete_credential') {
        $portalSysId = trim($body['portal_sys_id'] ?? '');
        $credId      = trim($body['cred_id'] ?? '');

        if (!$portalSysId || !$credId) jsonOut(['success' => false, 'message' => 'portal_sys_id ও cred_id প্রয়োজন']);

        [$portal, $credentials] = fetchPortalAndCredentials($pdo, $portalSysId);

        $target = null;
        foreach ($credentials as $c) {
            if (($c['cred_id'] ?? '') === $credId) { $target = $c; break; }
        }
        if (!$target) jsonOut(['success' => false, 'message' => 'এই credential পাওয়া যায়নি']);

        if (!$isAdmin && ($target['created_by'] ?? '') !== $currentUserId) {
            jsonOut(['success' => false, 'message' => 'শুধু নিজের যোগ করা credential ডিলিট করা যাবে (বা admin প্রয়োজন)']);
        }

        $credentials = array_values(array_filter($credentials, fn($c) => ($c['cred_id'] ?? '') !== $credId));
        savePortalCredentials($pdo, $portal, $credentials, $currentUser);
        jsonOut(['success' => true]);

    } elseif ($action === 'update_portal') {
        $portalSysId = trim($body['portal_sys_id'] ?? '');
        [$portal, $credentials] = fetchPortalAndCredentials($pdo, $portalSysId);

        if (!canManagePortal($credentials, $currentUserId, $isAdmin)) {
            jsonOut(['success' => false, 'message' => 'এই portal edit করার অনুমতি নেই (একাধিক employee-র credential আছে — শুধু admin পারবে)']);
        }

        $name = trim($body['portal_name'] ?? $portal['portal_name']);
        $url  = trim($body['portal_url']  ?? ($portal['portal_url'] ?? ''));
        $type = trim($body['portal_type'] ?? $portal['portal_type']);

        $meta = buildMetaData($portal['meta_data'], $currentUser);
        $pdo->prepare("UPDATE portal_links SET portal_name = ?, portal_url = ?, portal_type = ?, meta_data = ? WHERE sys_id = ?")
            ->execute([$name, $url, $type, $meta, $portalSysId]);

        jsonOut(['success' => true]);

    } else {
        jsonOut(['success' => false, 'message' => 'অবৈধ action']);
    }
}

function buildCredentialEntry(array $cred, string $userId, string $userName): array
{
    return [
        'cred_id'         => bin2hex(random_bytes(8)),
        'user_name'       => trim($cred['user_name'] ?? ''),
        'password'        => credEncrypt($cred['password'] ?? ''),
        'is_hide'         => !empty($cred['is_hide']),
        // ⚠️ যে creator নিজে তৈরি করছে, সে auto-included থাকবে access_user-এ
        // (is_hide=true হলেও নিজে অবশ্যই দেখতে পাবে) — কিন্তু shapePortal()
        // এ creatorId === currentUserId চেক already এটা cover করে, তাই এখানে
        // duplicate করার দরকার নেই, শুধু client-পাঠানো list-ই রাখা হচ্ছে
        'access_user'     => array_values((array)($cred['access_user'] ?? [])),
        'created_by'      => $userId,
        'created_by_name' => $userName,
        'created_at'      => date('d-m-Y H:i'),
    ];
}

function fetchPortalAndCredentials(PDO $pdo, string $portalSysId): array
{
    $stmt = $pdo->prepare("SELECT * FROM portal_links WHERE sys_id = ? LIMIT 1");
    $stmt->execute([$portalSysId]);
    $portal = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$portal) jsonOut(['success' => false, 'message' => 'Portal পাওয়া যায়নি']);

    $credentials = json_decode($portal['credentials'] ?? '[]', true) ?: [];
    return [$portal, $credentials];
}

function savePortalCredentials(PDO $pdo, array $portal, array $credentials, string $currentUser): void
{
    $meta = buildMetaData($portal['meta_data'], $currentUser);
    $pdo->prepare("UPDATE portal_links SET credentials = ?, meta_data = ? WHERE sys_id = ?")
        ->execute([json_encode($credentials, JSON_UNESCAPED_UNICODE), $meta, $portal['sys_id']]);
}

function canManagePortal(array $credentials, string $currentUserId, bool $isAdmin): bool
{
    if ($isAdmin) return true;
    $creators = [];
    foreach ($credentials as $c) {
        if (!empty($c['created_by'])) $creators[$c['created_by']] = true;
    }
    $unique = array_keys($creators);
    return count($unique) === 0 || (count($unique) === 1 && $unique[0] === $currentUserId);
}

// ════════════════════════════════════════════════════════════════════════════
// DELETE — পুরো portal (permission-checked)
// ════════════════════════════════════════════════════════════════════════════
function handleDelete(PDO $pdo, bool $isAdmin): void
{
    global $currentUserId;

    $sysId = trim($_GET['sys_id'] ?? '');
    if (!$sysId) jsonOut(['success' => false, 'message' => 'sys_id প্রয়োজন']);

    $stmt = $pdo->prepare("SELECT credentials FROM portal_links WHERE sys_id = ? LIMIT 1");
    $stmt->execute([$sysId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) jsonOut(['success' => false, 'message' => 'Portal পাওয়া যায়নি']);

    $credentials = json_decode($row['credentials'] ?? '[]', true) ?: [];
    if (!canManagePortal($credentials, $currentUserId, $isAdmin)) {
        jsonOut(['success' => false, 'message' => 'এই portal ডিলিট করার অনুমতি নেই (একাধিক employee-র credential আছে — শুধু admin পারবে)']);
    }

    $pdo->prepare("DELETE FROM portal_links WHERE sys_id = ?")->execute([$sysId]);
    jsonOut(['success' => true]);
}

function jsonOut(array $d): never
{
    echo json_encode($d, JSON_UNESCAPED_UNICODE);
    exit;
}