<?php
// PATH: /api/clients/cl-ac-discount-store.php  ← NEW FILE
//
// Discount to Client
//   → financial_entries: user_type=client, type=credit, related_type=5, is_discounted=1
//   → কোনো bank movement নাই (ac_banking_stmts touch হয় না)
//   → শুধু client এর outstanding কমবে
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

$clientId        = $data['clientId']        ?? null;
$clientName      = $data['clientName']      ?? null;
$amount          = $data['amount']          ?? 0;
$particular      = $data['particular']      ?? '';
$transactionDate = $data['transactionDate'] ?? date('Y-m-d H:i:s');

if (!is_numeric($amount) || $amount <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid amount']);
    exit;
}
if (!$clientId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Client is required']);
    exit;
}

try {
    $pdo->beginTransaction();

    $feUUIDs = generateIDs('financial_entries');
    $feMeta  = buildMetaData(null, $_SESSION['user_name'] ?? 'system');

    // related_type=5 (discount), is_discounted=1
    $pdo->prepare("
        INSERT INTO financial_entries
        (uuid, sys_id, user_sys_id, user_name, user_type,
         date, purpose, type, related_type, is_discounted, amount, ref, meta_data)
        VALUES
        (:uuid, :sys_id, :user_sys_id, :user_name, 'client',
         :date, :purpose, 'credit', 5, 1, :amount, '', :meta)
    ")->execute([
        ':uuid'        => $feUUIDs['uuid'],
        ':sys_id'      => $feUUIDs['sys_id'],
        ':user_sys_id' => $clientId,
        ':user_name'   => $clientName,
        ':date'        => $transactionDate,
        ':purpose'     => $particular,
        ':amount'      => $amount,
        ':meta'        => $feMeta
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Discount recorded successfully',
        'data'    => ['financial_entry_id' => $feUUIDs['sys_id']]
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>