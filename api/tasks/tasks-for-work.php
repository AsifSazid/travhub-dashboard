<?php

require '../../server/db_connection.php';

header('Content-Type: application/json'); // Tell the client this is JSON

$workId = $_GET['work_id'];

try {
    $stmt = $pdo->prepare("
        SELECT * FROM tasks
        WHERE work_sys_id =?
        ORDER BY id ASC
    ");
    $stmt->execute([$workId]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['tasks' => $tasks, 'success' => true]); // Send JSON to the client
} catch (Exception $e) {
    // Return error as JSON too
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
