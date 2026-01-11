<?php

require '../../server/db_connection.php';

header('Content-Type: application/json'); // Tell the client this is JSON

$clientId = $_GET['client_id'];

try {
    $stmt = $pdo->prepare("
        SELECT * FROM works
        WHERE client_id =?
        ORDER BY id DESC
    ");
    $stmt->execute([$clientId]);
    $works = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['works' => $works, 'success' => true]); // Send JSON to the client
} catch (Exception $e) {
    // Return error as JSON too
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
