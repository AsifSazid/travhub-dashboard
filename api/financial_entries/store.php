<?php
session_start();

require '../../server/db_connection.php';
require '../../server/uuid_with_system_id_generator.php';
require '../../server/generate_meta_data.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

error_reporting(E_ALL);
ini_set('display_errors', 1);

// ------------------ Validator ------------------
function validateInput(array $data): array
{
    $errors = [];

    if (!isset($data['type']) || !in_array($data['type'], ['credit', 'debit'], true)) {
        $errors[] = 'Valid type (credit/debit) is required';
    }

    if (!isset($data['amount']) || !is_numeric($data['amount']) || $data['amount'] <= 0) {
        $errors[] = 'Valid positive amount is required';
    }

    if (empty(trim($data['purpose'] ?? ''))) {
        $errors[] = 'Purpose is required';
    }

    return $errors;
}

// ------------------ Method Guard ------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $errors = validateInput($input);
    if ($errors) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $errors
        ]);
        exit;
    }

    // ------------------ Extract ------------------
    $type    = $input['type'];
    $amount  = (float) $input['amount'];
    $purpose = trim($input['purpose']);
    $date    = $input['date'] ?? date('Y-m-d');
    $ref     = $input['ref'] ?? null;

    $clientId = $input['client_id'] ?? null;
    $vendorId = $input['vendor_id'] ?? null;
    $workId   = $input['work_id'] ?? null;
    $taskId   = $input['task_id'] ?? null;

    $clientName = $vendorName = $taskTitle = $workTitle = null;

    // ------------------ Work ------------------
    if ($workId) {
        $stmt = $pdo->prepare("SELECT title FROM works WHERE sys_id = ?");
        $stmt->execute([$workId]);
        $workTitle = $stmt->fetchColumn();
    }

    // ------------------ Task ------------------
    if ($taskId) {
        $stmt = $pdo->prepare("SELECT title FROM tasks WHERE sys_id = ?");
        $stmt->execute([$taskId]);
        $taskTitle = $stmt->fetchColumn();
    }

    // ------------------ Client ------------------
    if ($clientId) {
        $stmt = $pdo->prepare("SELECT name FROM clients WHERE sys_id = ?");
        $stmt->execute([$clientId]);
        $clientName = $stmt->fetchColumn();

        if (!$clientName) {
            throw new Exception('Client not found');
        }
    }

    // ------------------ Vendor ------------------
    if ($vendorId) {
        $stmt = $pdo->prepare("SELECT name FROM vendors WHERE sys_id = ?");
        $stmt->execute([$vendorId]);
        $vendorName = $stmt->fetchColumn();

        if (!$vendorName) {
            throw new Exception('Vendor not found');
        }
    }

    // ------------------ Insert ------------------
    $ids = generateIDs('financial_entries');
    $metaDataJson = buildMetaData(null, $_SESSION['user_name'] ?? 'system');

    $stmt = $pdo->prepare("
        INSERT INTO financial_entries (
            uuid, sys_id,
            client_sys_id, client_name,
            vendor_sys_id, vendor_name,
            task_sys_id, task_title,
            work_sys_id, work_title,
            date, purpose, type, amount, ref,
            meta_data
        ) VALUES (
            :uuid, :sys_id,
            :client_sys_id, :client_name,
            :vendor_sys_id, :vendor_name,
            :task_sys_id, :task_title,
            :work_sys_id, :work_title,
            :date, :purpose, :type, :amount, :ref,
            :meta_data
        )
    ");

    $stmt->execute([
        ':uuid' => $ids['uuid'],
        ':sys_id' => $ids['sys_id'],
        ':client_sys_id' => $clientId,
        ':client_name' => $clientName,
        ':vendor_sys_id' => $vendorId,
        ':vendor_name' => $vendorName,
        ':task_sys_id' => $taskId,
        ':task_title' => $taskTitle,
        ':work_sys_id' => $workId,
        ':work_title' => $workTitle,
        ':date' => $date,
        ':purpose' => $purpose,
        ':type' => $type,
        ':amount' => $amount,
        ':ref' => $ref,
        ':meta_data' => $metaDataJson
    ]);

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => ucfirst($type) . ' transaction recorded successfully',
        'transaction_id' => $pdo->lastInsertId(),
        'uuid' => $ids['uuid']
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'error' => $e->getMessage()
    ]);
}
