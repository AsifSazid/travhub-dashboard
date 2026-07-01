<?php
// PATH: /api/vendors/ve-ac-purchase-store.php
// Changes:
//   - related_type=2 explicitly যোগ করা হয়েছে (আগে missing ছিল, default 1 বসতো — ভুল ছিল)
//   - amount field এখন purchasePrice থেকে নেওয়া হচ্ছে (backward compat: amount ও চলবে)
//   - এই file শুধু vendor entry করে (vendor + credit = purchase)
session_start();

require '../../server/db_connection.php';
require '../../server/uuid_with_system_id_generator.php';
require '../../server/generate_meta_data.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

$vendorId        = $data['vendorId']        ?? null;
$vendorName      = $data['vendorName']      ?? null;
// purchasePrice → backward compat: amount ও accept করে
$amount          = $data['purchasePrice']   ?? $data['amount'] ?? 0;
$particular      = $data['particular']      ?? '';
$transactionDate = $data['transactionDate'] ?? date('Y-m-d H:i:s');

if (!is_numeric($amount) || $amount <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid purchase price / amount']);
    exit;
}
if (!$vendorId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Vendor is required']);
    exit;
}

try {
    $pdo->beginTransaction();

    $feUUIDs = generateIDs('financial_entries');
    $feMeta  = buildMetaData(null, $_SESSION['user_name'] ?? 'system');

    // related_type=2 (vendor + credit = purchase)
    $pdo->prepare("
        INSERT INTO financial_entries
        (uuid, sys_id, user_sys_id, user_name, user_type,
         date, purpose, type, related_type, amount, ref, meta_data)
        VALUES
        (:uuid, :sys_id, :user_sys_id, :user_name, 'vendor',
         :date, :purpose, 'credit', 2, :amount, :ref, :meta)
    ")->execute([
        ':uuid'        => $feUUIDs['uuid'],
        ':sys_id'      => $feUUIDs['sys_id'],
        ':user_sys_id' => $vendorId,
        ':user_name'   => $vendorName,
        ':date'        => $transactionDate,
        ':purpose'     => $particular,
        ':amount'      => $amount,
        ':ref'         => '',
        ':meta'        => $feMeta
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Purchase recorded successfully',
        'data'    => ['financial_entry_id' => $feUUIDs['sys_id']]
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>