<?php
require '../../server/db_connection.php'; // <-- PDO connection
require '../../server/uuid_with_system_id_generator.php';
require '../../server/generate_meta_data.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

error_reporting(E_ALL);
ini_set('display_errors', 1);

function validateInput($data)
{
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
    $type    = $input['type'];
    $amount  = (float)$input['amount'];
    $purpose = trim($input['purpose']);
    $workId  = $input['work_id'];
    $taskId  = $input['task_id'];
    $date    = $input['date'] ?? date('Y-m-d');

    $clientId = $clientUuid = $clientName = null;
    $vendorId = $vendorUuid = $vendorName = null;
    $taskTitle = $workTitle = null;

    // ------------------ Work ------------------
    $stmt = $pdo->prepare("SELECT title FROM works WHERE sys_id = ?");
    $stmt->execute([$workId]);
    $work = $stmt->fetch(PDO::FETCH_ASSOC);
    $workTitle = $work['title'] ?? null;

    // ------------------ Task ------------------
    $stmt = $pdo->prepare("SELECT title FROM tasks WHERE sys_id = ?");
    $stmt->execute([$taskId]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$task) {
        throw new Exception("Task not found");
    }

    $taskTitle = $task['title'];

    // ------------------ Debit (Client) ------------------
    if ($type === 'debit') {
        $clientId = $input['client_id'];

        $stmt = $pdo->prepare("
            SELECT c.uuid, c.name
            FROM clients c
            INNER JOIN works w ON c.sys_id = w.client_sys_id
            WHERE w.sys_id = ? AND c.sys_id = ?
        ");
        $stmt->execute([$workId, $clientId]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$client) {
            // fallback: just get client by sys_id
            $stmt = $pdo->prepare("SELECT uuid, name FROM clients WHERE sys_id = ?");
            $stmt->execute([$clientId]);
            $client = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$client) {
                throw new Exception("Client not found");
            }
        }

        $clientUuid = $client['uuid'];
        $clientName = $client['name'];
    }

    // ------------------ Credit (Vendor) ------------------
    if ($type === 'credit') {
        $vendorId = $input['vendor_id'];

        $stmt = $pdo->prepare("SELECT uuid, name FROM vendors WHERE sys_id = ?");
        $stmt->execute([$vendorId]);
        $vendor = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$vendor) {
            throw new Exception("Vendor not found");
        }

        $vendorUuid = $vendor['uuid'];
        $vendorName = $vendor['name'];
    }

    // ------------------ Insert ------------------
    $ids = generateIDs('financial_entries');
    $uuid = $ids['uuid']; // ensure it's a string

    $metaDataJson = buildMetaData(
        null,
        $_SESSION['user_name'] ?? 'system'
    );

    $stmt = $pdo->prepare("
        INSERT INTO financial_entries (
            uuid, sys_id,
            client_sys_id, client_name,
            vendor_sys_id, vendor_name,
            task_sys_id, task_title,
            work_sys_id, work_title,
            date, purpose, type, amount,
            meta_data
        ) VALUES (
            :uuid, :sys_id,
            :client_sys_id, :client_name,
            :vendor_sys_id, :vendor_name,
            :task_sys_id, :task_title,
            :work_sys_id, :work_title,
            :date, :purpose, :type, :amount,
            :meta_data
        )
    ");

    $stmt->execute([
        ':uuid' => $uuid,
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
        ':meta_data' => $metaDataJson
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
