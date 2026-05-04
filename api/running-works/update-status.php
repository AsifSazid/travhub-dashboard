<?php
// FILE PATH: /api/works/update-status.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require '../../server/db_connection.php';

$input  = json_decode(file_get_contents('php://input'), true);
$sys_id = $input['sys_id'] ?? null;
$status = $input['status'] ?? null;

$allowed = ['pending', 'in_progress', 'completed', 'on_hold', 'cancelled'];

if (!$sys_id || !$status) {
    echo json_encode(['success' => false, 'message' => 'sys_id and status are required.']);
    exit;
}

if (!in_array($status, $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status value.']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE works SET work_status = ? WHERE sys_id = ?");
    $stmt->execute([$status, $sys_id]);

    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Work not found.']);
        exit;
    }

    echo json_encode(['success' => true, 'status' => 'success', 'message' => 'Status updated.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}