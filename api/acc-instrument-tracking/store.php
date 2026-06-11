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
$instrumentType = strtoupper($data['instrument_type'] ?? '');
$instrumentNo = $data['instrument_no'] ?? '';
$accountName = $data['account_name'] ?? '';
$bankName = $data['bank_name'] ?? '';
$status = $data['status'] ?? 'pending';
$date = $data['date'] ?? date('Y-m-d');

// Validate required fields
$errors = [];

if (empty($instrumentType)) {
    $errors[] = 'instrument_type is required';
}

if (empty($accountName)) {
    $errors[] = 'account_name is required';
}

if (empty($bankName)) {
    $errors[] = 'bank_name is required';
}

if ($instrumentType === 'CHEQUE' && empty($instrumentNo)) {
    $errors[] = 'instrument_no is required for CHEQUE';
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
   SYSTEM GENERATED DATA
===================================================== */
$ids       = generateIDs('AIT');
$uuid      = $ids['uuid'];
$sys_id    = $ids['sys_id'];
$meta_data = buildMetaData(null, $_SESSION['user_name'] ?? 'system');

/* =====================================================
   OPTIONAL FIELDS WITH DEFAULTS
===================================================== */
$instrumentDate = $data['instrument_date'] ?? $data['date'] ?? date('Y-m-d');
$amount = $data['amount'] ?? 0.00;
$relatedType = $data['related_type'] ?? '';
$relatedFrom = $data['related_from'] ?? '';
$relatedTo = $data['related_to'] ?? '';
$remarks = $data['remarks'] ?? '';
$clearingDate = $data['clearing_date'] ?? null;
$paymentTo = $data['payment_to'] ?? null;
$trnxType = '';

if($relatedType == 'a2a' || $relatedType == 'a2p'){
    $trnxType = 'debit';
}elseif($relatedType == 'received'){
    $trnxType = 'credit';
}elseif($relatedType == 'payment'){
    $trnxType = 'debit';
}


var_dump($trnxType, $relatedType, $data);
die;

/* =====================================================
   INSERT DATA
===================================================== */
try {
    $sql = "
    INSERT INTO ac_instrument_tracking (
        uuid, sys_id, instrument_type, trnx_type, instrument_no, payment_to,
        account_name, bank_name, instrument_date,
        amount, related_type, related_from, related_to, status, date,
        clearing_date, remarks, meta_data
    ) VALUES (
        :uuid, :sys_id, :instrument_type, :trnx_type, :instrument_no, :payment_to,
        :account_name, :bank_name, :instrument_date,
        :amount, :related_type, :related_from, :related_to, :status, :date,
        :clearing_date, :remarks, :meta_data
    )
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':uuid'            => $uuid,
        ':sys_id'          => $sys_id,
        ':instrument_type' => $instrumentType,
        ':trnx_type'       => $trnxType,
        ':instrument_no'   => $instrumentNo,
        ':payment_to'      => $paymentTo,
        ':account_name'    => $accountName,
        ':bank_name'       => $bankName,
        ':instrument_date' => $instrumentDate,
        ':amount'          => $amount,
        ':related_type'    => $relatedType,
        ':related_from'    => $relatedFrom,
        ':related_to'      => $relatedTo,
        ':status'          => $status,
        ':date'            => $date,
        ':clearing_date'   => $clearingDate,
        ':remarks'         => $remarks,
        ':meta_data'       => json_encode($meta_data),
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Instrument tracking data stored successfully',
        'data' => [
            'uuid'      => $uuid,
            'sys_id'    => $sys_id,
            'instrument_type' => $instrumentType,
            'instrument_no'   => $instrumentNo,
            'account_name'    => $accountName,
            'bank_name'       => $bankName,
            'instrument_date' => $instrumentDate,
            'amount'          => $amount,
            'status'          => $status
        ]
    ]);

} catch (PDOException $e) {
    // Check for duplicate entry
    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        echo json_encode([
            'success' => false,
            'message' => 'Instrument already exists'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}