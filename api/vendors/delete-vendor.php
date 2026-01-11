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
       1️⃣ Check Vendor Exists
       ====================== */
    $stmt = $pdo->prepare("
        SELECT id 
        FROM vendors 
        WHERE client_id = :client_id
        LIMIT 1
    ");
    $stmt->execute(['client_id' => $client_id]);
    $vendor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vendor) {
        throw new Exception('Vendor not found');
    }

    /* ======================
       2️⃣ Delete Vendor
       ====================== */
    $stmt = $pdo->prepare("
        DELETE FROM vendors 
        WHERE client_id = :client_id
    ");
    $stmt->execute(['client_id' => $client_id]);

    /* ======================
       3️⃣ Update Client
       ====================== */
    $stmt = $pdo->prepare("
        UPDATE clients
        SET is_vendor = 0
        WHERE id = :id
    ");
    $stmt->execute(['id' => $client_id]);

    // ✅ Commit
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Vendor removed successfully'
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
