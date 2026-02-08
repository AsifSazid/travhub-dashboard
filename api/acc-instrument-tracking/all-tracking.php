<?php

require '../../server/db_connection.php';

header('Content-Type: application/json'); // Tell the client this is JSON

try {
    $stmt = $pdo->prepare("
        SELECT * 
        FROM ac_instrument_tracking
        WHERE status != 'cleared'
        ORDER BY id DESC
    ");
    $stmt->execute();
    $tracks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['tracks' => $tracks, 'success' => true]); // Send JSON to the client
} catch (Exception $e) {
    // Return error as JSON too
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
