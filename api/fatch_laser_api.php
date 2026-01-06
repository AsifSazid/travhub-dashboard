<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require '../server/db_connection.php'; // must provide $pdo (PDO)

try {

    /* ---------------- SQL ---------------- */
    $sql = "
        SELECT 
            id,
            `Main Type`,
            Category,
            Name,
            Transactionable,
            Description,
            balance
        FROM ac_banking
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data'    => $data ?: []
    ]);

} catch (PDOException $e) {

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Query failed',
        'message' => $e->getMessage()
    ]);
}
