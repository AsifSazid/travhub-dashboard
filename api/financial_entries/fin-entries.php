<?php

require '../../server/db_connection.php';

header('Content-Type: application/json'); // Tell the client this is JSON

$userId = $_GET['id'];

try {
    $stmt = $pdo->prepare("
        SELECT * FROM financial_entries
        WHERE user_sys_id =?
        ORDER BY id DESC
    ");
    $stmt->execute([$userId]);
    $finStmts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['finStmts' => $finStmts, 'success' => true]); // Send JSON to the client
} catch (Exception $e) {
    // Return error as JSON too
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
