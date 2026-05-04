<?php

require '../../server/db_connection.php';

// header('Content-Type: application/json'); // Tell the client this is JSON

try {
    // JSON column theke date extract korar query
    // $.created_by_date.date holo path jeta apnar JSON key-er sathe milte hobe
    $sql = "
        SELECT *, 
        STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(meta_data, '$.created_by_date.date')), '%d-%m-%Y %H:%i') as extracted_date
        FROM leads 
        WHERE lead_status = ? 
        ORDER BY extracted_date DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['pending']);
    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['leads' => $leads, 'success' => true]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}