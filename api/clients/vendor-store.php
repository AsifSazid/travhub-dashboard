<?php
session_start();
require '../../server/db_connection.php'; // $pdo
require '../../server/generate_meta_data.php';
require '../../server/uuid_with_system_id_generator.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

error_reporting(E_ALL);
ini_set('display_errors', 1);

/* ======================
   Validate Request
   ====================== */
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

$client_id = $data['client_id'];
$action = $data['action'] ?? 'toggle'; // 'toggle', 'enable', 'disable'

try {
    $pdo->beginTransaction();

    /* ======================
       1️⃣ Fetch Client
       ====================== */
    $stmt = $pdo->prepare("
        SELECT id, uuid, client_sys_id, name, email, phone, address, is_vendor
        FROM clients
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute(['id' => $client_id]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        throw new Exception('Client not found');
    }

    /* ======================
       2️⃣ Check if Already Vendor (from both sides)
       ====================== */
    $checkStmt = $pdo->prepare("
        SELECT id, uuid, vendor_sys_id, name, status, is_client
        FROM vendors
        WHERE client_sys_id = :client_sys_id
           OR id = (SELECT vendor_sys_id FROM clients WHERE id = :client_id)
        LIMIT 1
    ");
    $checkStmt->execute([
        'client_sys_id' => $client_id,
        'client_id' => $client_id
    ]);
    
    $existingVendor = $checkStmt->fetch(PDO::FETCH_ASSOC);

    /* ======================
       3️⃣ If Disable/Remove Action
       ====================== */
    if ($action === 'disable' || ($action === 'toggle' && $existingVendor)) {
        
        if (!$existingVendor) {
            throw new Exception('No vendor relation found to disable');
        }
        
        // Only remove the relationship, don't delete the vendor
        $updateClient = $pdo->prepare("
            UPDATE clients
            SET is_vendor = 0
            WHERE id = :client_id
        ");
        $updateClient->execute(['client_id' => $client_id]);
        
        // Mark vendor as inactive but keep record
        $updateVendor = $pdo->prepare("
            UPDATE vendors
            SET is_client = 0,
                status = 'inactive'
            WHERE id = :vendor_id
        ");
        $updateVendor->execute(['vendor_id' => $existingVendor['id']]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Client removed from vendors (but vendor record kept)',
            'is_active' => false
        ]);
        exit;
    }

    /* ======================
       4️⃣ If Already Exists → Sync (Enable)
       ====================== */
    if ($existingVendor) {
        
        // Reactivate the relation
        $updateClient = $pdo->prepare("
            UPDATE clients
            SET is_vendor = 1,
                vendor_sys_id = :vendor_sys_id
            WHERE id = :client_id
        ");
        $updateClient->execute([
            'vendor_sys_id' => $existingVendor['vendor_sys_id'],
            'client_id' => $client_id
        ]);
        
        $updateVendor = $pdo->prepare("
            UPDATE vendors
            SET is_client = 1,
                status = 'active'
            WHERE id = :vendor_id
        ");
        $updateVendor->execute(['vendor_id' => $existingVendor['id']]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Existing vendor-client relation reactivated',
            'is_active' => true,
            'vendor' => [
                'id' => $existingVendor['id'],
                'sys_id' => $existingVendor['vendor_sys_id'],
                'name' => $existingVendor['name']
            ]
        ]);
        exit;
    }

    /* ======================
       5️⃣ Create New Vendor
       ====================== */
    $vendorIDs = generateIDs('vendors');
    
    $vendor_uuid   = $vendorIDs['uuid'];
    $vendor_sys_id = $vendorIDs['sys_id'];
    $vendor_name   = $client['name'];
    
    $metaDataJson = buildMetaData(
        null,
        $_SESSION['user_name'] ?? 'system'
    );
    
    $insertVendor = $pdo->prepare("
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
            client_sys_id,
            meta_data
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
            :client_sys_id,
            :meta_data
        )
    ");
    
    $insertVendor->execute([
        'uuid'           => $vendor_uuid,
        'vendor_sys_id'  => $vendor_sys_id,
        'type'           => 'client',
        'name'           => $vendor_name,
        'email'          => $client['email'],
        'phone'          => $client['phone'],
        'address'        => $client['address'],
        'status'         => 'active',
        'is_client'      => 1,
        'client_sys_id'  => $client_id,
        'meta_data'      => $metaDataJson
    ]);
    
    $vendor_db_id = $pdo->lastInsertId();
    
    /* ======================
       6️⃣ Update Client
       ====================== */
    $updateClient = $pdo->prepare("
        UPDATE clients
        SET is_vendor = 1,
            vendor_sys_id = :vendor_sys_id
        WHERE id = :client_id
    ");
    
    $updateClient->execute([
        'vendor_sys_id' => $vendor_sys_id,
        'client_id'     => $client['id']
    ]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Client converted to vendor successfully',
        'is_active' => true,
        'vendor'  => [
            'id' => $vendor_db_id,
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
?>