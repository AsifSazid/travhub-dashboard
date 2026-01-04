<?php

require '../../server/db_connection.php';

header('Content-Type: application/json'); // Tell the client this is JSON

$workId = $_GET['work_id'];

try {
    $stmtForWork = $pdo->prepare("
        SELECT * FROM works
        WHERE sys_id =?
        ORDER BY id ASC
    ");
    $stmtForWork->execute([$workId]);
    $work = $stmtForWork->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT * FROM clients
        WHERE sys_id =?
        ORDER BY id ASC
    ");
    $stmt->execute([$work['client_sys_id']]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['client' => $client, 'work' => $work,  'success' => true]); // Send JSON to the client
} catch (Exception $e) {
    // Return error as JSON too
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
