<?php
// FILE PATH: /api/leads/delete.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require '../../server/db_connection.php';

$input  = json_decode(file_get_contents('php://input'), true);
$sys_id = $input['sys_id'] ?? null;

if (!$sys_id) {
    echo json_encode(['success' => false, 'message' => 'sys_id is required.']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM leads WHERE sys_id = ?");
    $stmt->execute([$sys_id]);

    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Lead not found.']);
        exit;
    }

    echo json_encode(['success' => true, 'status' => 'success', 'message' => 'Lead deleted successfully.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}