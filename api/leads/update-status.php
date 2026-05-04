<?php
// FILE PATH: /api/leads/update-status.php
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$sys_id = $input['sys_id'] ?? null;
$status = $input['status'] ?? null;

$allowed = ['pending', 'active', 'converted', 'hold', 'closed'];

if (!$sys_id || !$status) {
    echo json_encode(['success' => false, 'message' => 'sys_id and status are required.']);
    exit;
}

if (!in_array($status, $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status value.']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE leads SET lead_status = ? WHERE sys_id = ?");
    $stmt->execute([$status, $sys_id]);

    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Lead not found.']);
        exit;
    }

    echo json_encode(['success' => true, 'status' => 'success', 'message' => 'Status updated successfully.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}