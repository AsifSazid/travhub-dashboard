<?php

require '../../server/db_connection.php';

header('Content-Type: application/json'); // Tell the client this is JSON

$taskId = $_GET['task_id'];

try {
    $stmt = $pdo->prepare("
        SELECT hotel_info FROM old_tasks
        WHERE sys_id =?
        ORDER BY id ASC
    ");
    $stmt->execute([$taskId]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['hotel_json' => $task,  'success' => true]); // Send JSON to the client
} catch (Exception $e) {
    // Return error as JSON too
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
