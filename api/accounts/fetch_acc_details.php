<?php

require '../../server/db_connection.php';

header('Content-Type: application/json'); 

$accId = $_GET['acc_id'];

try {
    $stmt = $pdo->prepare("
        SELECT * FROM ac_banking
        WHERE sys_id =?
        ORDER BY id ASC
    ");
    $stmt->execute([$accId]);
    $accInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['accInfo' => $accInfo, 'success' => true]); // Send JSON to the client
} catch (Exception $e) {
    // Return error as JSON too
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
