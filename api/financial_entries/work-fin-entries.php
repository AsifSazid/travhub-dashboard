<?php

require '../../server/db_connection.php';

header('Content-Type: application/json'); // Tell the client this is JSON

$workId = $_GET['work_id'];

try {
    $stmt = $pdo->prepare("
        SELECT * FROM financial_entries
        WHERE work_sys_id =?
        ORDER BY id DESC
    ");
    $stmt->execute([$workId]);
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
