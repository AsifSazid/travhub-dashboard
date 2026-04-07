<?php
// POST /api/directors/create.php
session_start();

require_once '../../server/db_connection.php';
require '../../server/uuid_with_system_id_generator.php';
require '../../server/director_id_generator.php';
require '../../server/generate_meta_data.php';
require_once '../../server/director-calculation.php';
require '../../server/make-dir.php';
jsonHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendError('Method not allowed', 405);

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) sendError('Invalid JSON body');

// ── Validate required fields ──────────────────────
$required = ['name', 'email', 'phone'];
foreach ($required as $field) {
    if (empty(trim($input[$field] ?? ''))) {
        sendError(ucfirst($field) . ' is required');
    }
}

$name    = trim($input['name']);
$email   = trim($input['email']);
$phone   = trim($input['phone']);
$investAmount   = trim($input['investAmount']);
$address_01 = isset($input['address_01']) ? trim($input['address_01']) : null;
$address_02 = isset($input['address_02']) ? trim($input['address_02']) : null;
$address_city = isset($input['address_city']) ? trim($input['address_city']) : null;
$address_zip = isset($input['address_zip']) ? trim($input['address_zip']) : null;
$address_state = isset($input['address_state']) ? trim($input['address_state']) : null;
$address_country = isset($input['address_country']) ? trim($input['address_country']) : null;
$status  = in_array($input['status'] ?? 'active', ['active','inactive','suspended'])
           ? $input['status'] : 'active';
           
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) sendError('Invalid email address');

$db = $pdo;

// Check duplicate email
$chk = $db->prepare("SELECT id FROM directors WHERE email = ? LIMIT 1");
$chk->execute([$email]);
if ($chk->fetch()) sendError('Email already registered');

// Generate IDs
$uuid = generateIDs('directors');
$sys_id = generateDirectorId();

// Create director folder
$cleanSysId = preg_replace('/\s+/u', '', $sys_id);
$cleanFullName = preg_replace('/\s+/u', '', $name);
$directorFolderName = $cleanSysId . '_' . $cleanFullName;

// Create directory structure
$basePath = "../../uploads/directors/";
$directorFolderPath = $basePath . $directorFolderName;

makeDir('directors', $directorFolderName);

// Build meta_data
$meta = buildMetaData(null, $_SESSION['user_name'] ?? 'system');

$phoneData = [
    'primary_no' => $phone
];

$emailData = [
    'primary' => $email
];

$addressData = [
    'address_line_1' => $address_01,
    'address_line_2' => $address_02,
    'city' => $address_city,
    'state' => $address_state,
    'zip_code' => $address_zip,
    'country' => $address_country
];

$stmt = $db->prepare("
    INSERT INTO directors
        (uuid, sys_id, name, email, phone, address, status, meta_data)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->execute([
    $uuid['uuid'], $sys_id, $name, json_encode($emailData), json_encode($phoneData), json_encode($addressData), $status, json_encode($meta)
]);

$newId = (int) $db->lastInsertId();

$dirBalanceSysId = generateIDs('director_balances');

$totalPercentage   = calcOwnership($investAmount);

// Initialise balance cache row
$db->prepare("INSERT INTO director_balances (uuid, sys_id, director_sys_id, total_investment, total_percentage, meta_data) VALUES (?, ?, ?, ?, ?, ?)")
   ->execute([$dirBalanceSysId['uuid'], $dirBalanceSysId['sys_id'], $sys_id, $investAmount, $totalPercentage, $meta]);
   
$dirTranxSysId = generateIDs('director_transactions');
   
$db->prepare("INSERT INTO director_transactions (uuid, sys_id, director_sys_id, type, amount, note, meta_data) VALUES (?, ?, ?, ?, ?, ?, ?)")
    ->execute([$dirTranxSysId['uuid'], $dirTranxSysId['sys_id'], $sys_id, 'invest', $investAmount, 'starting investment', $meta]);

sendSuccess([
    'id'     => $newId,
    'uuid'   => $uuid,
    'sys_id' => $sys_id,
    'name'   => $name,
    'email'  => $email,
    'phone'  => $phone,
    'status' => $status,
    'total_investment'  => 0,
    'ownership_percent' => 0
], 201);
