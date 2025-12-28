<?php
require '../../server/db_connection.php'; // $pdo
require '../../server/uuid_generator.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['client_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Client ID required'
    ]);
    exit;
}

$client_id = (int) $data['client_id'];

try {
    // 🔐 Start Transaction
    $pdo->beginTransaction();

    /* ======================
       1️⃣ Fetch Client
       ====================== */
    $stmt = $pdo->prepare("
        SELECT id, uuid, given_name, sur_name, is_vendor
        FROM clients
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute(['id' => $client_id]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        throw new Exception('Client not found');
    }

    if ($client['is_vendor'] == 1) {
        $pdo->rollBack();
        echo json_encode([
            'success' => true,
            'message' => 'Client already vendor'
        ]);
        exit;
    }

    /* ======================
       2️⃣ Insert Vendor
       ====================== */
    $vendor_uuid = generateUUID();
    $vendor_id   = 'VND-' . time();
    $client_name = trim($client['given_name'] . ' ' . $client['sur_name']);
    $type        = 'client';

    $stmt = $pdo->prepare("
        INSERT INTO vendors 
        (uuid, client_id, client_uuid, client_name, vendor_id, type)
        VALUES
        (:uuid, :client_id, :client_uuid, :client_name, :vendor_id, :type)
    ");

    $stmt->execute([
        'uuid'        => $vendor_uuid,
        'client_id'   => $client_id,
        'client_uuid' => $client['uuid'],
        'client_name' => $client_name,
        'vendor_id'   => $vendor_id,
        'type'        => $type
    ]);

    /* ======================
       3️⃣ Update Client
       ====================== */
    $stmt = $pdo->prepare("
        UPDATE clients
        SET is_vendor = 1
        WHERE id = :id
    ");
    $stmt->execute(['id' => $client_id]);

    // ✅ Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Vendor created successfully'
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
