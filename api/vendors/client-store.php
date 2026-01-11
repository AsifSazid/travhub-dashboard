<?php
require '../../server/db_connection.php'; // $pdo
require '../../server/uuid_with_system_id_generator.php';

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
    /* ======================
       🔐 Start Transaction
       ====================== */
    $pdo->beginTransaction();

    /* ======================
       1️⃣ Fetch Client
       ====================== */
    $stmt = $pdo->prepare("
        SELECT *
        FROM clients
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute(['id' => $client_id]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        throw new Exception('Client not found');
    }

    if ((int)$client['is_vendor'] === 1) {
        $pdo->rollBack();
        echo json_encode([
            'success' => true,
            'message' => 'Client already vendor'
        ]);
        exit;
    }

    /* ======================
       2️⃣ Check Existing Vendor (SYNC)
       ====================== */
    $vendorSearchStmt = $pdo->prepare("
        SELECT *
        FROM vendors
        WHERE client_id = :id
        LIMIT 1
    ");
    $vendorSearchStmt->execute(['id' => $client_id]);
    $foundedVendor = $vendorSearchStmt->fetch(PDO::FETCH_ASSOC);

    if ($foundedVendor) {

        // 🔁 Sync client with existing vendor
        $stmt = $pdo->prepare("
            UPDATE clients
            SET
                is_vendor      = 1,
                vendor_id      = :vendor_id,
                vendor_uuid    = :vendor_uuid,
                vendor_sys_id  = :vendor_sys_id,
                vendor_name    = :vendor_name
            WHERE id = :client_id
        ");

        $stmt->execute([
            'vendor_id'     => $foundedVendor['id'],
            'vendor_uuid'   => $foundedVendor['uuid'],
            'vendor_sys_id' => $foundedVendor['vendor_sys_id'],
            'vendor_name'   => $foundedVendor['name'],
            'client_id'     => $client_id
        ]);

        $pdo->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Client and vendor synchronization complete'
        ]);
        exit;
    }

    /* ======================
       3️⃣ Generate Vendor IDs
       ====================== */
    $vendorIDs = generateIDs('vendors');

    $vendor_uuid   = $vendorIDs['uuid'];
    $vendor_sys_id = $vendorIDs['sys_id'];
    $vendor_name   = $client['name'];

    /* ======================
       4️⃣ Insert Vendor
       ====================== */
    $stmt = $pdo->prepare("
        INSERT INTO vendors (
            uuid,
            vendor_sys_id,
            type,
            name,
            email,
            phone,
            address,
            status,
            is_client,
            client_id,
            client_uuid,
            client_sys_id,
            client_name,
            created_by,
            updated_by
        )
        VALUES (
            :uuid,
            :vendor_sys_id,
            :type,
            :name,
            :email,
            :phone,
            :address,
            :status,
            :is_client,
            :client_id,
            :client_uuid,
            :client_sys_id,
            :client_name,
            :created_by,
            :updated_by
        )
    ");

    $stmt->execute([
        'uuid'           => $vendor_uuid,
        'vendor_sys_id'  => $vendor_sys_id,
        'type'           => 'client',
        'name'           => $vendor_name,
        'email'          => $client['email'],
        'phone'          => $client['phone'],
        'address'        => $client['address'],
        'status'         => 'active',
        'is_client'      => 1,
        'client_id'      => $client['id'],
        'client_uuid'    => $client['uuid'],
        'client_sys_id'  => $client['client_sys_id'],
        'client_name'    => $client['name'],
        'created_by'     => 'system',
        'updated_by'     => 'system'
    ]);

    $vendor_db_id = $pdo->lastInsertId();

    /* ======================
       5️⃣ Update Client with Vendor Info
       ====================== */
    $stmt = $pdo->prepare("
        UPDATE clients
        SET
            is_vendor      = 1,
            vendor_id      = :vendor_id,
            vendor_uuid    = :vendor_uuid,
            vendor_sys_id  = :vendor_sys_id,
            vendor_name    = :vendor_name
        WHERE id = :client_id
    ");

    $stmt->execute([
        'vendor_id'     => $vendor_db_id,
        'vendor_uuid'   => $vendor_uuid,
        'vendor_sys_id' => $vendor_sys_id,
        'vendor_name'   => $vendor_name,
        'client_id'     => $client_id
    ]);

    /* ======================
       ✅ Commit
       ====================== */
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Client converted to vendor successfully',
        'vendor'  => [
            'id'     => $vendor_db_id,
            'uuid'   => $vendor_uuid,
            'sys_id' => $vendor_sys_id,
            'name'   => $vendor_name
        ]
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
