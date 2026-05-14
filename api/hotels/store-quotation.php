<?php
session_start();

require '../../server/db_connection.php';
require '../../server/uuid_with_system_id_generator.php';
require '../../server/generate_meta_data.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['informations'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Raw information is required'
    ]);
    exit;
}

if (empty($data['quotations'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Business quotation is required'
    ]);
    exit;
}

$id = $data['id'] ?? null;
$uuid = $data['uuid'] ?? null;
$rawClient = $data['client'] ?? null;

if (!$rawClient) {
    echo json_encode(['success' => false, 'message' => 'Client missing']);
    exit;
}

$parts = explode('|', $rawClient);
$clientSysID = trim($parts[0]);

$title = $data['title'];
$informations = $data['informations'];
$quotations = $data['quotations'];
$percentage = $data['percentage'] ?? 0;
$formData = $data['form_data'] ?? [];

$metaData = buildMetaData(
    null,
    $_SESSION['user_name'] ?? 'system'
);

try {

    if (!empty($id) || !empty($uuid)) {
        $checkSql = "
            SELECT id, uuid, sys_id 
            FROM hotel_quatations 
            WHERE id = :id OR uuid = :uuid
            LIMIT 1
        ";

        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([
            ':id' => $id,
            ':uuid' => $uuid
        ]);

        $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $stmt = $pdo->prepare("
                UPDATE hotel_quotations
                SET 
                    informations = :informations,
                    quotations = :quotations,
                    percentage = :percentage,
                    form_data = :form_data,
                    meta_data = :meta_data
                WHERE id = :id
            ");

            $stmt->execute([
                ':informations' => $informations,
                ':quotations' => $quotations,
                ':percentage' => $percentage,
                ':form_data' => json_encode($formData, JSON_UNESCAPED_UNICODE),
                ':meta_data' => json_encode($metaData, JSON_UNESCAPED_UNICODE),
                ':id' => $existing['id']
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Quotation updated successfully',
                'id' => $existing['id'],
                'uuid' => $existing['uuid'],
                'sys_id' => $existing['sys_id'],
                'action' => 'updated'
            ]);
            exit;
        }
    }

    $ids = generateIDs('hotel_quotations');

    $stmt = $pdo->prepare("
        INSERT INTO hotel_quotations 
        (
            uuid, 
            sys_id, 
            client_sys_id,
            title,
            informations, 
            quotations, 
            percentage, 
            form_data,
            meta_data
        )
        VALUES 
        (
            :uuid, 
            :sys_id, 
            :client_sys_id,
            :title,
            :informations, 
            :quotations, 
            :percentage, 
            :form_data,
            :meta_data
        )
    ");

    $stmt->execute([
        ':uuid' => $ids['uuid'],
        ':sys_id' => $ids['sys_id'],
        ':client_sys_id' => $clientSysID,
        ':title' => $title,
        ':informations' => $informations,
        ':quotations' => $quotations,
        ':percentage' => $percentage,
        ':form_data' => json_encode($formData, JSON_UNESCAPED_UNICODE),
        ':meta_data' => json_encode($metaData, JSON_UNESCAPED_UNICODE)
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Quotation saved successfully',
        'id' => $pdo->lastInsertId(),
        'uuid' => $ids['uuid'],
        'sys_id' => $ids['sys_id'],
        'action' => 'created'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}