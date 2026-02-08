<?php
session_start();

require '../../server/db_connection.php';          // $pdo
require '../../server/generate_meta_data.php';
require '../../server/uuid_with_system_id_generator.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

error_reporting(E_ALL);
ini_set('display_errors', 1);

/* =====================================================
   READ INPUT
===================================================== */
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON payload'
    ]);
    exit;
}

/* =====================================================
   VALIDATION
===================================================== */
$uuid = $data['uuid'] ?? '';
$sys_id = $data['sys_id'] ?? '';
$user_sys_id = $data['user_sys_id'] ?? null;
$user_name = $data['user_name'] ?? null;
$to_user_sys_id = $data['to_user_sys_id'] ?? null;
$to_user_name = $data['to_user_name'] ?? null;
$date = $data['date'] ?? date('Y-m-d');
$purpose = $data['purpose'] ?? null;
$details = $data['details'] ?? '';
$type = strtolower($data['type'] ?? 'petty_cash');
$amount = $data['amount'] ?? 0.00;
$ref = $data['ref'] ?? null;
$meta_data = $data['meta_data'] ?? '';

// Validate required fields
$errors = [];

if (empty($details)) {
    $errors[] = 'details is required';
}

if (!in_array($type, ['conveyance_bill', 'other_bill', 'loan', 'petty_cash'])) {
    $errors[] = 'Invalid type. Must be one of: conveyance_bill, other_bill, loan, petty_cash';
}

if (empty($date)) {
    $errors[] = 'date is required';
}

if (!is_numeric($amount) || floatval($amount) <= 0) {
    $errors[] = 'amount must be a positive number';
}

// For loan type, validate to_user fields
if ($type === 'loan' && (empty($to_user_sys_id) || empty($to_user_name))) {
    $errors[] = 'to_user_sys_id and to_user_name are required for loan type';
}

if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'message' => 'Validation failed',
        'errors' => $errors
    ]);
    exit;
}

/* =====================================================
   SYSTEM GENERATED DATA (if not provided from frontend)
===================================================== */
$ids = generateIDs('petty_cash'); // PC = Petty Cash prefix
$uuid = $ids['uuid'];
$sys_id = $ids['sys_id'];

// If meta_data is not provided from frontend, generate it
// if (empty($meta_data)) {
    $meta_data = buildMetaData(null, $_SESSION['user_name'] ?? 'system');
// } else {
//     // If meta_data is provided as JSON string, decode it
//     $decoded_meta = json_decode($meta_data, true);
//     if ($decoded_meta !== null) {
//         $meta_data = $decoded_meta;
//     }
//     // Update meta_data with current user info
//     $meta_data = updateMetaData($meta_data, $_SESSION['user_name'] ?? 'system');
// }

// Ensure meta_data is properly encoded for database
if (is_array($meta_data)) {
    $meta_data_json = json_encode($meta_data);
} else {
    $meta_data_json = $meta_data;
}

/* =====================================================
   INSERT DATA
===================================================== */
try {
    $sql = "
    INSERT INTO petty_cashes (
        uuid, sys_id, user_sys_id, user_name, 
        to_user_sys_id, to_user_name, date, purpose, 
        details, type, status, amount, ref, meta_data
    ) VALUES (
        :uuid, :sys_id, :user_sys_id, :user_name,
        :to_user_sys_id, :to_user_name, :date, :purpose,
        :details, :type, :status, :amount, :ref, :meta_data
    )
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':uuid'           => $uuid,
        ':sys_id'         => $sys_id,
        ':user_sys_id'    => $user_sys_id,
        ':user_name'      => $user_name,
        ':to_user_sys_id' => $to_user_sys_id,
        ':to_user_name'   => $to_user_name,
        ':date'           => $date,
        ':purpose'        => $purpose,
        ':details'        => $details,
        ':type'           => $type,
        ':status'           => 'pending',
        ':amount'         => floatval($amount),
        ':ref'            => $ref,
        ':meta_data'      => $meta_data_json,
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Petty cash entry created successfully',
        'data' => [
            'uuid'          => $uuid,
            'sys_id'        => $sys_id,
            'type'          => $type,
            'date'          => $date,
            'amount'        => $amount,
            'user_name'     => $user_name,
            'to_user_name'  => $to_user_name,
            'purpose'       => $purpose
        ]
    ]);

} catch (PDOException $e) {
    // Check for duplicate entry
    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        if (strpos($e->getMessage(), 'uuid') !== false) {
            echo json_encode([
                'success' => false,
                'message' => 'Entry with this UUID already exists'
            ]);
        } elseif (strpos($e->getMessage(), 'sys_id') !== false) {
            echo json_encode([
                'success' => false,
                'message' => 'Entry with this System ID already exists'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Duplicate entry found'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage(),
            'error_details' => $e->getMessage()
        ]);
    }
}