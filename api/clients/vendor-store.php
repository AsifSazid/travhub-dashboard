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

if (empty($data['vendor_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Vendor ID required'
    ]);
    exit;
}

$vendor_id = (int) $data['vendor_id'];

try {
    // 🔐 Start Transaction
    $pdo->beginTransaction();

    /* ======================
       1️⃣ Fetch Vendor
       ====================== */
    $stmt = $pdo->prepare("
        SELECT *
        FROM vendors
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute(['id' => $vendor_id]);
    $vendor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vendor) {
        throw new Exception('Vendor not found');
    }

    if ((int)$vendor['is_client'] === 1) {
        $pdo->rollBack();
        echo json_encode([
            'success' => true,
            'message' => 'Vendor already client'
        ]);
        exit;
    }


    $clientSearchStmt = $pdo->prepare("
        SELECT *
        FROM clients
        WHERE vendor_id = :id
        LIMIT 1
    ");
    $clientSearchStmt->execute(['id' => $vendor_id]);
    $foundedClient = $clientSearchStmt->fetch(PDO::FETCH_ASSOC);
    if ($foundedClient) {
        $stmt = $pdo->prepare("
            UPDATE vendors
            SET
                is_client      = 1,
                client_id      = :client_id,
                client_uuid    = :client_uuid,
                client_sys_id  = :client_sys_id,
                client_name    = :client_name
            WHERE id = :vendor_id
        ");

        $stmt->execute([
            'client_id'     => $foundedClient['id'],
            'client_uuid'   => $foundedClient['uuid'],
            'client_sys_id' => $foundedClient['client_sys_id'],
            'client_name'   => $foundedClient['name'],
            'vendor_id'     => $vendor_id
        ]);

        $pdo->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Vendor and client Synchronization Complete'
        ]);
        exit;
    }

    /* ======================
       2️⃣ Generate Client IDs
       ====================== */
    $clientIDs = generateIDs('clients');

    $client_uuid   = $clientIDs['uuid'];
    $client_sys_id = $clientIDs['sys_id'];
    $client_name   = $vendor['name'];

    /* ======================
       3️⃣ Insert Client
       ====================== */
    $stmt = $pdo->prepare("
        INSERT INTO clients (
            uuid,
            client_sys_id,
            type,
            name,
            email,
            phone,
            address,
            status,
            is_vendor,
            vendor_id,
            vendor_uuid,
            vendor_sys_id,
            vendor_name,
            created_by,
            updated_by
        )
        VALUES (
            :uuid,
            :client_sys_id,
            :type,
            :name,
            :email,
            :phone,
            :address,
            :status,
            :is_vendor,
            :vendor_id,
            :vendor_uuid,
            :vendor_sys_id,
            :vendor_name,
            :created_by,
            :updated_by
        )
    ");

    $stmt->execute([
        'uuid'           => $client_uuid,
        'client_sys_id'  => $client_sys_id,
        'type'           => 'vendor',
        'name'           => $client_name,
        'email'          => $vendor['email'],
        'phone'          => $vendor['phone'],
        'address'        => $vendor['address'],
        'status'         => 'active',
        'is_vendor'      => '1',
        'vendor_id'      => $vendor['id'],
        'vendor_uuid'    => $vendor['uuid'],
        'vendor_sys_id'  => $vendor['vendor_sys_id'],
        'vendor_name'    => $vendor['name'],
        'created_by'     => 'system',
        'updated_by'     => 'system'
    ]);

    $client_db_id = $pdo->lastInsertId();

    /* ======================
       4️⃣ Update Vendor with Client Info
       ====================== */
    $stmt = $pdo->prepare("
        UPDATE vendors
        SET
            is_client      = 1,
            client_id      = :client_id,
            client_uuid    = :client_uuid,
            client_sys_id  = :client_sys_id,
            client_name    = :client_name
        WHERE id = :vendor_id
    ");

    $stmt->execute([
        'client_id'     => $client_db_id,
        'client_uuid'   => $client_uuid,
        'client_sys_id' => $client_sys_id,
        'client_name'   => $client_name,
        'vendor_id'     => $vendor_id
    ]);

    // ✅ Commit
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Vendor converted to client successfully',
        'client'  => [
            'id'       => $client_db_id,
            'uuid'     => $client_uuid,
            'sys_id'   => $client_sys_id,
            'name'     => $client_name
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

