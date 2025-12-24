<?php

require '../../server/db_connection.php';

header('Content-Type: application/json'); // Tell the client this is JSON

try {
    $stmt = $pdo->prepare("
        SELECT * FROM leads 
        WHERE lead_status = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute(['pending']);
    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['leads' => $leads, 'success' => true]); // Send JSON to the client
} catch (Exception $e) {
    // Return error as JSON too
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
