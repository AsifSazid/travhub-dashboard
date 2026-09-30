<?php

session_start();
require '../../server/db_connection.php';
require_once '../../server/hrm_permissions.php';
requireHrm($pdo, 'eps_view');

header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("
        SELECT * FROM eps_structures
        ORDER BY id ASC
    ");
    $stmt->execute();
    $epsLists = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['epsLists' => $epsLists, 'success' => true]); // Send JSON to the client
} catch (Exception $e) {
    // Return error as JSON too
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}