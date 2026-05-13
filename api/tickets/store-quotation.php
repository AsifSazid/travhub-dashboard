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

$informations = $data['informations'];
$quotations = $data['quotations'];
$percentage = $data['percentage'] ?? 0;
$veFixedPrice = $data['ve_fixed_price'] ?? 0;
$formData = $data['form_data'] ?? [];

$metaData = buildMetaData(
    null,
    $_SESSION['user_name'] ?? 'system'
);

try {

    if (!empty($id) || !empty($uuid)) {
        $checkSql = "
            SELECT id, uuid, sys_id 
            FROM air_ticket_quotations 
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
                UPDATE air_ticket_quotations
                SET 
                    informations = :informations,
                    quotations = :quotations,
                    percentage = :percentage,
                    ve_fixed_price = :ve_fixed_price,
                    meta_data = :meta_data
                WHERE id = :id
            ");

            $stmt->execute([
                ':informations' => $informations,
                ':quotations' => $quotations,
                ':percentage' => $percentage,
                ':ve_fixed_price' => $veFixedPrice,
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

    $ids = generateIDs('air_ticket_quotations');

    $stmt = $pdo->prepare("
        INSERT INTO air_ticket_quotations 
        (
            uuid, 
            sys_id, 
            informations, 
            quotations, 
            percentage, 
            ve_fixed_price,
            meta_data
        )
        VALUES 
        (
            :uuid, 
            :sys_id, 
            :informations, 
            :quotations, 
            :percentage, 
            :ve_fixed_price,
            :meta_data
        )
    ");

    $stmt->execute([
        ':uuid' => $ids['uuid'],
        ':sys_id' => $ids['sys_id'],
        ':informations' => $informations,
        ':quotations' => $quotations,
        ':percentage' => $percentage,
        ':ve_fixed_price' => $veFixedPrice,
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
