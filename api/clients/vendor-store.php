<?php
/**
 * vendor-store.php
 * Called from: Client List toggle
 * Purpose: Toggle a client's vendor relationship (enable / disable)
 *
 * Input  : { client_id, action }   action = 'toggle' | 'enable' | 'disable'
 * Link   : vendors.client_sys_id  ↔  clients.sys_id
 *          clients.vendor_sys_id  ↔  vendors.sys_id
 */

session_start();
require '../../server/db_connection.php';
require '../../server/generate_meta_data.php';
require '../../server/uuid_with_system_id_generator.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

/* ── Validate Request ─────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data      = json_decode(file_get_contents('php://input'), true);
$client_id = $data['client_id'] ?? null;
$action    = $data['action']    ?? 'toggle'; // 'toggle' | 'enable' | 'disable'

if (empty($client_id)) {
    echo json_encode(['success' => false, 'message' => 'client_id required']);
    exit;
}

try {
    $pdo->beginTransaction();

    /* ── 1. Fetch Client ──────────────────────────────────────────────────── */
    $stmt = $pdo->prepare("
        SELECT id, uuid, sys_id, name, email, phone, address, is_vendor, vendor_sys_id
        FROM clients
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute(['id' => $client_id]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        throw new Exception('Client not found');
    }

    /* ── 2. Find existing vendor linked to this client ────────────────────── */
    // vendors.client_sys_id stores clients.sys_id  (the correct FK)
    $stmt = $pdo->prepare("
        SELECT id, uuid, sys_id, vendor_sys_id, name, status, is_client
        FROM vendors
        WHERE client_sys_id = :client_sys_id
        LIMIT 1
    ");
    $stmt->execute(['client_sys_id' => $client['sys_id']]);
    $existingVendor = $stmt->fetch(PDO::FETCH_ASSOC);

    /* ── 3. Determine intent ──────────────────────────────────────────────── */
    $shouldDisable = $action === 'disable'
        || ($action === 'toggle' && (int)$client['is_vendor'] === 1);

    /* ── 4. DISABLE ──────────────────────────────────────────────────────── */
    if ($shouldDisable) {
        if (!$existingVendor) {
            // Edge-case: flag was on but no vendor row — just clear the flag
            $pdo->prepare("UPDATE clients SET is_vendor = 0, vendor_sys_id = NULL WHERE id = :id")
                ->execute(['id' => $client_id]);
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Vendor flag cleared (no vendor row found)', 'is_active' => false]);
            exit;
        }

        $pdo->prepare("UPDATE clients SET is_vendor = 0, vendor_sys_id = NULL WHERE id = :id")
            ->execute(['id' => $client_id]);

        $pdo->prepare("UPDATE vendors SET is_client = 0, status = 'inactive' WHERE id = :id")
            ->execute(['id' => $existingVendor['id']]);

        $pdo->commit();
        echo json_encode([
            'success'   => true,
            'message'   => 'Client removed from vendors (vendor record kept)',
            'is_active' => false
        ]);
        exit;
    }

    /* ── 5. ENABLE — vendor row already exists, just reactivate ─────────── */
    if ($existingVendor) {
        $pdo->prepare("
            UPDATE clients
            SET is_vendor = 1, vendor_sys_id = :vendor_sys_id
            WHERE id = :id
        ")->execute(['vendor_sys_id' => $existingVendor['sys_id'], 'id' => $client_id]);

        $pdo->prepare("
            UPDATE vendors
            SET is_client = 1, status = 'active'
            WHERE id = :id
        ")->execute(['id' => $existingVendor['id']]);

        $pdo->commit();
        echo json_encode([
            'success'   => true,
            'message'   => 'Existing vendor–client relation reactivated',
            'is_active' => true,
            'vendor'    => [
                'id'     => $existingVendor['id'],
                'sys_id' => $existingVendor['sys_id'],
                'name'   => $existingVendor['name']
            ]
        ]);
        exit;
    }

    /* ── 6. ENABLE — no vendor row yet, create one ───────────────────────── */
    $vendorIDs     = generateIDs('vendors');
    $vendor_uuid   = $vendorIDs['uuid'];
    $vendor_sys_id = $vendorIDs['sys_id'];
    $metaDataJson  = buildMetaData(null, $_SESSION['user_name'] ?? 'system');

    $pdo->prepare("
        INSERT INTO vendors (
            uuid, sys_id, type,
            name, email, phone, address,
            status, is_client, client_sys_id,
            meta_data
        ) VALUES (
            :uuid, :sys_id, :type,
            :name, :email, :phone, :address,
            :status, :is_client, :client_sys_id,
            :meta_data
        )
    ")->execute([
        'uuid'           => $vendor_uuid,
        'sys_id'         => $vendor_sys_id,
        'type'           => 'client',
        'name'           => $client['name'],
        'email'          => $client['email'],
        'phone'          => $client['phone'],
        'address'        => $client['address'],
        'status'         => 'active',
        'is_client'      => 1,
        'client_sys_id'  => $client['sys_id'],   // ← correct FK
        'meta_data'      => $metaDataJson
    ]);

    $vendor_db_id = $pdo->lastInsertId();

    $pdo->prepare("
        UPDATE clients
        SET is_vendor = 1, vendor_sys_id = :vendor_sys_id
        WHERE id = :id
    ")->execute(['vendor_sys_id' => $vendor_sys_id, 'id' => $client_id]);

    $pdo->commit();
    echo json_encode([
        'success'   => true,
        'message'   => 'Client converted to vendor successfully',
        'is_active' => true,
        'vendor'    => [
            'id'     => $vendor_db_id,
            'sys_id' => $vendor_sys_id,
            'name'   => $client['name']
        ]
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>