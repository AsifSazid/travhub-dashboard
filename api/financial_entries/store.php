<?php
require '../../server/db_connection.php'; // <-- PDO connection
require '../../server/uuid_generator.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

error_reporting(E_ALL);
ini_set('display_errors', 1);

function validateInput($data) {
    $errors = [];

    if (empty($data['type']) || !in_array($data['type'], ['credit', 'debit'])) {
        $errors[] = "Valid type (credit/debit) is required";
    }

    if (empty($data['amount']) || !is_numeric($data['amount']) || $data['amount'] <= 0) {
        $errors[] = "Valid positive amount is required";
    }

    if (empty($data['purpose'])) {
        $errors[] = "Purpose is required";
    }

    if (empty($data['work_id'])) {
        $errors[] = "Work ID is required";
    }

    if (empty($data['task_id'])) {
        $errors[] = "Task ID is required";
    }

    if ($data['type'] === 'debit' && empty($data['client_id'])) {
        $errors[] = "Client ID is required for debit transactions";
    }

    if ($data['type'] === 'credit' && empty($data['vendor_id'])) {
        $errors[] = "Vendor ID is required for credit transactions";
    }

    return $errors;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    $errors = validateInput($input);
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $errors
        ]);
        exit;
    }

    // ------------------ Extract ------------------
    $type   = $input['type'];
    $amount = (float)$input['amount'];
    $purpose = trim($input['purpose']);
    $workId = (int)$input['work_id'];
    $taskId = (int)$input['task_id'];
    $date   = $input['date'] ?? date('Y-m-d');

    $clientId = $clientUuid = $clientName = null;
    $vendorId = $vendorUuid = $vendorName = null;
    $taskUuid = $taskTitle = null;

    // ------------------ Work ------------------
    $stmt = $pdo->prepare("SELECT uuid, title FROM works WHERE id = ?");
    $stmt->execute([$workId]);
    $work = $stmt->fetch(PDO::FETCH_ASSOC);

    // ------------------ Task ------------------
    $stmt = $pdo->prepare("SELECT uuid, title FROM tasks WHERE id = ?");
    $stmt->execute([$taskId]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$task) {
        throw new Exception("Task not found");
    }

    $workUuid  = $work['uuid'];
    $workTitle = $work['title'];

    $taskUuid  = $task['uuid'];
    $taskTitle = $task['title'];

    // ------------------ Debit (Client) ------------------
    if ($type === 'debit') {
        $clientId = (int)$input['client_id'];

        $stmt = $pdo->prepare("
            SELECT c.uuid, c.given_name, c.sur_name
            FROM clients c
            INNER JOIN works w ON c.id = w.client_id
            WHERE w.id = ? AND c.id = ?
        ");
        $stmt->execute([$workId, $clientId]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$client) {
            $stmt = $pdo->prepare("SELECT uuid, given_name, sur_name FROM clients WHERE id = ?");
            $stmt->execute([$clientId]);
            $client = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$client) {
                throw new Exception("Client not found");
            }
        }

        $clientUuid = $client['uuid'];
        $clientName = $client['given_name'] . ' ' . $client['sur_name'];
    }

    // ------------------ Credit (Vendor) ------------------
    if ($type === 'credit') {
        $vendorId = (int)$input['vendor_id'];

        $stmt = $pdo->prepare("SELECT uuid, client_name FROM vendors WHERE id = ?");
        $stmt->execute([$vendorId]);
        $vendor = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$vendor) {
            throw new Exception("Vendor not found");
        }

        $vendorUuid = $vendor['uuid'];
        $vendorName = $vendor['client_name'];
    }

    // ------------------ Insert ------------------
    $uuid = generateUUID();
    $createdBy = $input['created_by'] ?? 'system';
    $updatedBy = $input['updated_by'] ?? 'system';

    $stmt = $pdo->prepare("
        INSERT INTO financial_entries (
            uuid,
            client_id, client_uuid, client_name,
            vendor_id, vendor_uuid, vendor_name,
            task_id, task_uuid, task_title,
            work_id, work_uuid, work_title,
            date, purpose, type, amount,
            created_by, updated_by
        ) VALUES (
            :uuid,
            :client_id, :client_uuid, :client_name,
            :vendor_id, :vendor_uuid, :vendor_name,
            :task_id, :task_uuid, :task_title,
            :work_id, :work_uuid, :work_title,
            :date, :purpose, :type, :amount,
            :created_by, :updated_by
        )
    ");

    $stmt->execute([
        ':uuid' => $uuid,
        ':client_id' => $clientId,
        ':client_uuid' => $clientUuid,
        ':client_name' => $clientName,
        ':vendor_id' => $vendorId,
        ':vendor_uuid' => $vendorUuid,
        ':vendor_name' => $vendorName,
        ':task_id' => $taskId,
        ':task_uuid' => $taskUuid,
        ':task_title' => $taskTitle,
        ':work_id' => $workId,
        ':work_uuid' => $workUuid,
        ':work_title' => $workTitle,
        ':date' => $date,
        ':purpose' => $purpose,
        ':type' => $type,
        ':amount' => $amount,
        ':created_by' => $createdBy,
        ':updated_by' => $updatedBy
    ]);

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => ucfirst($type) . ' transaction recorded successfully',
        'transaction_id' => $pdo->lastInsertId(),
        'uuid' => $uuid
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'error' => $e->getMessage()
    ]);
}
