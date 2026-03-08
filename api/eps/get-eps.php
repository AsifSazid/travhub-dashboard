<?php

require '../../server/db_connection.php';

header('Content-Type: application/json'); // Tell the client this is JSON

$epsId = $_GET['eps_id'];

try {
    $stmt = $pdo->prepare("
        SELECT * FROM eps_structures
        WHERE sys_id =?
        ORDER BY id ASC
    ");
    $stmt->execute([$epsId]);
    $eps = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['epsDetails' => $eps, 'success' => true]); // Send JSON to the client
} catch (Exception $e) {
    // Return error as JSON too
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
