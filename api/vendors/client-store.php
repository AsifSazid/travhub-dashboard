<?php
/**
 * client-store.php
 * Called from: Vendor List toggle
 * Purpose: Toggle a vendor's client relationship (enable / disable)
 *
 * Input  : { vendor_id, action }   action = 'toggle' | 'enable' | 'disable'
 * Link   : clients.vendor_sys_id  ↔  vendors.sys_id
 *          vendors.client_sys_id  ↔  clients.sys_id
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
$vendor_id = $data['vendor_id'] ?? null;
$action    = $data['action']    ?? 'toggle'; // 'toggle' | 'enable' | 'disable'

if (empty($vendor_id)) {
    echo json_encode(['success' => false, 'message' => 'vendor_id required']);
    exit;
}

try {
    $pdo->beginTransaction();

    /* ── 1. Fetch Vendor ──────────────────────────────────────────────────── */
    $stmt = $pdo->prepare("
        SELECT id, uuid, sys_id, name, email, phone, address, is_client, client_sys_id
        FROM vendors
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute(['id' => $vendor_id]);
    $vendor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vendor) {
        throw new Exception('Vendor not found');
    }

    /* ── 2. Find existing client linked to this vendor ────────────────────── */
    // clients.vendor_sys_id stores vendors.sys_id  (the correct FK)
    $stmt = $pdo->prepare("
        SELECT id, uuid, sys_id, name, is_vendor, vendor_sys_id
        FROM clients
        WHERE vendor_sys_id = :vendor_sys_id
        LIMIT 1
    ");
    $stmt->execute(['vendor_sys_id' => $vendor['sys_id']]);
    $existingClient = $stmt->fetch(PDO::FETCH_ASSOC);

    /* ── 3. Determine intent ──────────────────────────────────────────────── */
    $shouldDisable = $action === 'disable'
        || ($action === 'toggle' && (int)$vendor['is_client'] === 1);

    /* ── 4. DISABLE ──────────────────────────────────────────────────────── */
    if ($shouldDisable) {
        if (!$existingClient) {
            // Edge-case: flag was on but no client row — just clear the flag
            $pdo->prepare("UPDATE vendors SET is_client = 0, client_sys_id = NULL WHERE id = :id")
                ->execute(['id' => $vendor_id]);
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Client flag cleared (no client row found)', 'is_active' => false]);
            exit;
        }

        $pdo->prepare("UPDATE vendors SET is_client = 0, client_sys_id = NULL WHERE id = :id")
            ->execute(['id' => $vendor_id]);

        $pdo->prepare("UPDATE clients SET is_vendor = 0, vendor_sys_id = NULL WHERE id = :id")
            ->execute(['id' => $existingClient['id']]);

        $pdo->commit();
        echo json_encode([
            'success'   => true,
            'message'   => 'Vendor removed from clients (client record kept)',
            'is_active' => false
        ]);
        exit;
    }

    /* ── 5. ENABLE — client row already exists, just reactivate ─────────── */
    if ($existingClient) {
        $pdo->prepare("
            UPDATE vendors
            SET is_client = 1, client_sys_id = :client_sys_id
            WHERE id = :id
        ")->execute(['client_sys_id' => $existingClient['sys_id'], 'id' => $vendor_id]);

        $pdo->prepare("
            UPDATE clients
            SET is_vendor = 1, vendor_sys_id = :vendor_sys_id
            WHERE id = :id
        ")->execute(['vendor_sys_id' => $vendor['sys_id'], 'id' => $existingClient['id']]);

        $pdo->commit();
        echo json_encode([
            'success'   => true,
            'message'   => 'Existing client–vendor relation reactivated',
            'is_active' => true,
            'client'    => [
                'id'     => $existingClient['id'],
                'sys_id' => $existingClient['sys_id'],
                'name'   => $existingClient['name']
            ]
        ]);
        exit;
    }

    /* ── 6. ENABLE — no client row yet, create one ───────────────────────── */
    $clientIDs     = generateIDs('clients');
    $client_uuid   = $clientIDs['uuid'];
    $client_sys_id = $clientIDs['sys_id'];
    $metaDataJson  = buildMetaData(null, $_SESSION['user_name'] ?? 'system');

    $pdo->prepare("
        INSERT INTO clients (
            uuid, sys_id, type,
            name, email, phone, address,
            is_vendor, vendor_sys_id,
            meta_data
        ) VALUES (
            :uuid, :sys_id, :type,
            :name, :email, :phone, :address,
            :is_vendor, :vendor_sys_id,
            :meta_data
        )
    ")->execute([
        'uuid'           => $client_uuid,
        'sys_id'         => $client_sys_id,
        'type'           => 'vendor',
        'name'           => $vendor['name'],
        'email'          => $vendor['email'],
        'phone'          => $vendor['phone'],
        'address'        => $vendor['address'],
        'is_vendor'      => 1,
        'vendor_sys_id'  => $vendor['sys_id'],   // ← correct FK
        'meta_data'      => $metaDataJson
    ]);

    $client_db_id = $pdo->lastInsertId();

    $pdo->prepare("
        UPDATE vendors
        SET is_client = 1, client_sys_id = :client_sys_id
        WHERE id = :id
    ")->execute(['client_sys_id' => $client_sys_id, 'id' => $vendor_id]);

    $pdo->commit();
    echo json_encode([
        'success'   => true,
        'message'   => 'Vendor converted to client successfully',
        'is_active' => true,
        'client'    => [
            'id'     => $client_db_id,
            'sys_id' => $client_sys_id,
            'name'   => $vendor['name']
        ]
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>