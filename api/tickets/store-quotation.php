<?php
session_start();

require '../../server/db_connection.php';
require '../../server/uuid_with_system_id_generator.php';
require '../../server/generate_meta_data.php';

header('Content-Type: application/json');

/**
 * Fetch a single row from air_ticket_quotations by sys_id
 * 
 * @param PDO $pdo Database connection
 * @param string $sysId System ID to look up
 * @return array|null Row data or null if not found
 */
function fetchRow(PDO $pdo, string $sysId): ?array {
    $stmt = $pdo->prepare("SELECT * FROM air_ticket_quotations WHERE sys_id = ? LIMIT 1");
    $stmt->execute([$sysId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ?: null;
}

/**
 * Build information entry for a quotation
 * 
 * @param string $id Quotation ID (sys_id with suffix)
 * @param array $d Request data containing form_snapshot and raw_text
 * @return array Formatted information entry
 */
function buildInfoEntry(string $id, array $d): array {
    $snapshot = $d['form_snapshot'] ?? [];
    return [
        'info_id'          => $id,
        'trip_option'      => $snapshot['trip_option'] ?? null,
        'route'            => $snapshot['route'] ?? null,
        'class'            => $snapshot['class'] ?? null,
        'pax'              => [
            'adult'  => $snapshot['pax_adult'] ?? 0,
            'child'  => $snapshot['pax_child'] ?? 0,
            'infant' => $snapshot['pax_infant'] ?? 0,
        ],
        'segments'         => $snapshot['segments'] ?? [],
        'baggage_1_desc'   => $snapshot['baggage_1_desc'] ?? null,
        'price_1_adult'    => $snapshot['price_1_adult'] ?? 0,
        'price_1_child'    => $snapshot['price_1_child'] ?? 0,
        'price_1_infant'   => $snapshot['price_1_infant'] ?? 0,
        'baggage_2_desc'   => $snapshot['baggage_2_desc'] ?? null,
        'price_2_adult'    => $snapshot['price_2_adult'] ?? 0,
        'price_2_child'    => $snapshot['price_2_child'] ?? 0,
        'price_2_infant'   => $snapshot['price_2_infant'] ?? 0,
        'refundable_status'=> $snapshot['refundable_status'] ?? null,
        'changeable_status'=> $snapshot['changeable_status'] ?? null,
        'notes'            => $snapshot['notes'] ?? null,
        'raw_text'         => $d['raw_text'] ?? null,
        'deleted'          => false,
        'deleted_at'       => null,
    ];
}

/**
 * Build quotation entry for a quotation
 * 
 * @param string $id Quotation ID (sys_id with suffix)
 * @param array $d Request data containing business_text and pricing info
 * @return array Formatted quotation entry
 */
function buildQuotEntry(string $id, array $d): array {
    $meta = json_decode(
        buildMetaData(null, $_SESSION['user_name'] ?? 'system'),
        true
    );
    return [
        'quot_id'        => $id,
        'business_text'  => $d['business_text'] ?? null,
        'price_1_adult'  => $d['biz_price_1_adult'] ?? 0,
        'price_1_child'  => $d['biz_price_1_child'] ?? 0,
        'price_1_infant' => $d['biz_price_1_infant'] ?? 0,
        'price_2_adult'  => $d['biz_price_2_adult'] ?? 0,
        'price_2_child'  => $d['biz_price_2_child'] ?? 0,
        'price_2_infant' => $d['biz_price_2_infant'] ?? 0,
        'percentage'     => $d['percentage'] ?? 0,
        've_fixed_price' => $d['ve_fixed_price'] ?? 0,
        'deleted'        => false,
        'deleted_at'     => null,
        'meta_data'      => $meta,
    ];
}

/**
 * Build form snapshot entry for a quotation
 * 
 * @param string $id Quotation ID (sys_id with suffix)
 * @param array $d Request data containing form_snapshot
 * @return array Formatted form entry
 */
function buildFormEntry(string $id, array $d): array {
    $snapshot = $d['form_snapshot'] ?? [];
    $meta = json_decode(
        buildMetaData(null, $_SESSION['user_name'] ?? 'system'),
        true
    );
    return array_merge(
        ['form_id' => $id],
        $snapshot,
        ['deleted' => false, 'deleted_at' => null, 'meta_data' => $meta]
    );
}

// Get and validate request data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON payload'
    ]);
    exit;
}

$action = $data['action'] ?? null;

if (!$action || !in_array($action, ['create', 'append', 'update', 'update_basic', 'soft_delete'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Valid action is required (create, append, update, update_basic, soft_delete)'
    ]);
    exit;
}

try {
    // ── ACTION: create ────────────────────────────────
    if ($action === 'create') {
        // Validate required fields
        if (empty($data['client_sys_id']) || empty($data['title']) || empty($data['form_snapshot'])) {
            echo json_encode([
                'success' => false,
                'message' => 'client_sys_id, title, and form_snapshot are required for create action'
            ]);
            exit;
        }

        $ids = generateIDs('air_ticket_quotations');
        $sysId = $ids['sys_id']; // e.g., THR-AQ-26-00K005

        $quotId = $sysId . '-01';

        $information = buildInfoEntry($quotId, $data);
        $quotation   = buildQuotEntry($quotId, $data);
        $formEntry   = buildFormEntry($quotId, $data);

        $meta = buildMetaData(null, $_SESSION['user_name'] ?? 'system');

        $stmt = $pdo->prepare("
            INSERT INTO air_ticket_quotations
            (uuid, sys_id, client_sys_id, title, informations, quotations,
             form_data, percentage, ve_fixed_price, meta_data)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $ids['uuid'], 
            $sysId,
            $data['client_sys_id'], 
            $data['title'],
            json_encode([$information], JSON_UNESCAPED_UNICODE),
            json_encode([$quotation], JSON_UNESCAPED_UNICODE),
            json_encode([$formEntry], JSON_UNESCAPED_UNICODE),
            $data['percentage'] ?? 0, 
            $data['ve_fixed_price'] ?? 0,
            json_encode($meta, JSON_UNESCAPED_UNICODE)
        ]);

        echo json_encode([
            'success' => true, 
            'action' => 'created',
            'sys_id' => $sysId, 
            'quotation_id' => $quotId,
            'message' => 'Quotation created successfully'
        ]);
        exit;
    }

    // ── ACTION: append ────────────────────────────────
    if ($action === 'append') {
        if (empty($data['sys_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'sys_id is required for append action'
            ]);
            exit;
        }

        $row = fetchRow($pdo, $data['sys_id']);
        
        if (!$row) {
            echo json_encode([
                'success' => false,
                'message' => 'Document not found with the provided sys_id'
            ]);
            exit;
        }

        $infos  = json_decode($row['informations'], true) ?? [];
        $quots  = json_decode($row['quotations'], true) ?? [];
        $forms  = json_decode($row['form_data'], true) ?? [];

        // Count non-deleted entries to determine next number
        $count = 0;
        foreach ($infos as $info) {
            if (!($info['deleted'] ?? false)) {
                $count++;
            }
        }
        
        $nextNumber = str_pad($count + 1, 2, '0', STR_PAD_LEFT);
        $quotId = $data['sys_id'] . '-' . $nextNumber;

        $infos[] = buildInfoEntry($quotId, $data);
        $quots[] = buildQuotEntry($quotId, $data);
        $forms[] = buildFormEntry($quotId, $data);

        $meta = buildMetaData($row['meta_data'], $_SESSION['user_name'] ?? 'system');

        $pdo->prepare("
            UPDATE air_ticket_quotations
            SET informations=?, quotations=?, form_data=?,
                percentage=?, ve_fixed_price=?, meta_data=?
            WHERE sys_id=?
        ")->execute([
            json_encode($infos, JSON_UNESCAPED_UNICODE), 
            json_encode($quots, JSON_UNESCAPED_UNICODE), 
            json_encode($forms, JSON_UNESCAPED_UNICODE),
            $data['percentage'] ?? $row['percentage'], 
            $data['ve_fixed_price'] ?? $row['ve_fixed_price'],
            json_encode($meta, JSON_UNESCAPED_UNICODE), 
            $data['sys_id']
        ]);

        echo json_encode([
            'success' => true, 
            'action' => 'appended',
            'quotation_id' => $quotId,
            'message' => 'Quotation added successfully'
        ]);
        exit;
    }

    // ── ACTION: update ────────────────────────────────
    if ($action === 'update') {
        if (empty($data['sys_id']) || empty($data['quotation_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'sys_id and quotation_id are required for update action'
            ]);
            exit;
        }

        $row = fetchRow($pdo, $data['sys_id']);
        
        if (!$row) {
            echo json_encode([
                'success' => false,
                'message' => 'Document not found with the provided sys_id'
            ]);
            exit;
        }

        $qId = $data['quotation_id'];

        $infos = json_decode($row['informations'], true) ?? [];
        $quots = json_decode($row['quotations'], true) ?? [];
        $forms = json_decode($row['form_data'], true) ?? [];

        $found = false;

        // Update information entry
        foreach ($infos as &$item) {
            if (isset($item['info_id']) && $item['info_id'] === $qId) {
                $item = buildInfoEntry($qId, $data);
                $found = true;
                break;
            }
        }

        // Update quotation entry
        foreach ($quots as &$item) {
            if (isset($item['quot_id']) && $item['quot_id'] === $qId) {
                $existingMeta = isset($item['meta_data']) ? json_encode($item['meta_data']) : null;
                $newMeta = json_decode(
                    buildMetaData($existingMeta, $_SESSION['user_name'] ?? 'system'),
                    true
                );
                $item = buildQuotEntry($qId, $data);
                $item['meta_data'] = $newMeta;
                $found = true;
                break;
            }
        }

        // Update form entry
        foreach ($forms as &$item) {
            if (isset($item['form_id']) && $item['form_id'] === $qId) {
                $existingMeta = isset($item['meta_data']) ? json_encode($item['meta_data']) : null;
                $newMeta = json_decode(
                    buildMetaData($existingMeta, $_SESSION['user_name'] ?? 'system'),
                    true
                );
                $item = buildFormEntry($qId, $data);
                $item['meta_data'] = $newMeta;
                $found = true;
                break;
            }
        }

        if (!$found) {
            echo json_encode([
                'success' => false,
                'message' => 'Quotation not found with the provided quotation_id'
            ]);
            exit;
        }

        $docMeta = buildMetaData($row['meta_data'], $_SESSION['user_name'] ?? 'system');

        $pdo->prepare("
            UPDATE air_ticket_quotations
            SET informations=?, quotations=?, form_data=?,
                percentage=?, ve_fixed_price=?, meta_data=?
            WHERE sys_id=?
        ")->execute([
            json_encode($infos, JSON_UNESCAPED_UNICODE), 
            json_encode($quots, JSON_UNESCAPED_UNICODE), 
            json_encode($forms, JSON_UNESCAPED_UNICODE),
            $data['percentage'] ?? $row['percentage'], 
            $data['ve_fixed_price'] ?? $row['ve_fixed_price'],
            json_encode($docMeta, JSON_UNESCAPED_UNICODE), 
            $data['sys_id']
        ]);

        echo json_encode([
            'success' => true, 
            'action' => 'updated',
            'quotation_id' => $qId,
            'message' => 'Quotation updated successfully'
        ]);
        exit;
    }

    // ── ACTION: update_basic ──────────────────────────
    if ($action === 'update_basic') {
        if (empty($data['sys_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'sys_id is required for update_basic action'
            ]);
            exit;
        }

        $row = fetchRow($pdo, $data['sys_id']);
        
        if (!$row) {
            echo json_encode([
                'success' => false,
                'message' => 'Document not found with the provided sys_id'
            ]);
            exit;
        }

        $docMeta = buildMetaData($row['meta_data'], $_SESSION['user_name'] ?? 'system');

        $updates = [];
        $params = [];

        if (isset($data['title'])) {
            $updates[] = "title = ?";
            $params[] = $data['title'];
        }

        if (isset($data['client_sys_id'])) {
            $updates[] = "client_sys_id = ?";
            $params[] = $data['client_sys_id'];
        }

        if (empty($updates)) {
            echo json_encode([
                'success' => false,
                'message' => 'No fields to update (title or client_sys_id required)'
            ]);
            exit;
        }

        $updates[] = "meta_data = ?";
        $params[] = json_encode($docMeta, JSON_UNESCAPED_UNICODE);
        $params[] = $data['sys_id'];

        $sql = "UPDATE air_ticket_quotations SET " . implode(", ", $updates) . " WHERE sys_id = ?";
        $pdo->prepare($sql)->execute($params);

        echo json_encode([
            'success' => true, 
            'action' => 'updated_basic',
            'message' => 'Basic information updated successfully'
        ]);
        exit;
    }

    // ── ACTION: soft_delete ───────────────────────────
    if ($action === 'soft_delete') {
        if (empty($data['sys_id']) || empty($data['quotation_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'sys_id and quotation_id are required for soft_delete action'
            ]);
            exit;
        }

        $row = fetchRow($pdo, $data['sys_id']);
        
        if (!$row) {
            echo json_encode([
                'success' => false,
                'message' => 'Document not found with the provided sys_id'
            ]);
            exit;
        }

        $qId = $data['quotation_id'];

        date_default_timezone_set('Asia/Dhaka');
        $now = date('Y-m-d H:i:s');

        $found = false;
        $columns = ['informations' => 'info_id', 'quotations' => 'quot_id', 'form_data' => 'form_id'];

        foreach ($columns as $col => $key) {
            $arr = json_decode($row[$col], true) ?? [];
            foreach ($arr as &$item) {
                if (isset($item[$key]) && $item[$key] === $qId) {
                    $item['deleted'] = true;
                    $item['deleted_at'] = $now;
                    $found = true;
                    break;
                }
            }
            $row[$col] = json_encode($arr, JSON_UNESCAPED_UNICODE);
        }

        if (!$found) {
            echo json_encode([
                'success' => false,
                'message' => 'Quotation not found with the provided quotation_id'
            ]);
            exit;
        }

        $pdo->prepare("
            UPDATE air_ticket_quotations
            SET informations=?, quotations=?, form_data=?
            WHERE sys_id=?
        ")->execute([
            $row['informations'], 
            $row['quotations'],
            $row['form_data'], 
            $data['sys_id']
        ]);

        echo json_encode([
            'success' => true, 
            'action' => 'soft_deleted',
            'quotation_id' => $qId,
            'message' => 'Quotation deleted successfully'
        ]);
        exit;
    }

} catch (Exception $e) {
    error_log("Air Ticket Quotation Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
    exit;
}