<?php

require '../../server/db_connection.php';

header('Content-Type: application/json'); // Tell the client this is JSON

try {
    $stmt = $pdo->prepare("
        SELECT * FROM employees
        ORDER BY id ASC
    ");
    $stmt->execute();
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['employees' => $employees, 'success' => true]); // Send JSON to the client
} catch (Exception $e) {
    // Return error as JSON too
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
