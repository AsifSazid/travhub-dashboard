<?php

require '../../server/db_connection.php';

header('Content-Type: application/json'); // Tell the client this is JSON

$clientId = $_GET['client_id'];
$taskId = $_GET['task_id'];

try {
    $stmt = $pdo->prepare("
        SELECT purpose, amount FROM financial_entries
        WHERE client_sys_id = ? AND task_sys_id = ?
        ORDER BY id DESC
    ");
    $stmt->execute([$clientId, $taskId]);
    $finStmts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['stmt' => $finStmts, 'success' => true]); // Send JSON to the client
} catch (Exception $e) {
    // Return error as JSON too
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
