<?php

require '../../server/db_connection.php';

header('Content-Type: application/json'); // Tell the client this is JSON

$directorId = $_GET['director_id'];

try {
    $stmt = $pdo->prepare("
        SELECT * FROM directors
        WHERE sys_id =?
        ORDER BY id ASC
    ");
    $stmt->execute([$directorId]);
    $director = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['director' => $director,  'success' => true]); // Send JSON to the client
} catch (Exception $e) {
    // Return error as JSON too
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
