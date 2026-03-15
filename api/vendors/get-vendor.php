<?php

require '../../server/db_connection.php';

header('Content-Type: application/json'); // Tell the client this is JSON

$vid = $_GET['vid'];

try {
    $stmt = $pdo->prepare("
        SELECT * FROM vendors
        WHERE sys_id =?
        ORDER BY id ASC
    ");
    $stmt->execute([$vid]);
    $vendor = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['vendor' => $vendor, 'success' => true]); // Send JSON to the client
} catch (Exception $e) {
    // Return error as JSON too
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
