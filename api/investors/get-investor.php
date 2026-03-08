<?php

require '../../server/db_connection.php';

header('Content-Type: application/json'); // Tell the client this is JSON

$investorId = $_GET['investor_id'];

try {
    $stmt = $pdo->prepare("
        SELECT * FROM investors
        WHERE sys_id =?
        ORDER BY id ASC
    ");
    $stmt->execute([$investorId]);
    $investor = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['investor' => $investor,  'success' => true]); // Send JSON to the client
} catch (Exception $e) {
    // Return error as JSON too
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
