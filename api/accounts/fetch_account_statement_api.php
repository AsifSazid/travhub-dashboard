<?php
// fetch_account_statement_api.php - Fetch account statement (PDO)

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// 1. DB connection (PDO)
require '../../server/db_connection.php'; // provides $pdo

// 2. Get query parameters
$ledger_db_id = $_GET['ledger_db_id'] ?? null;
$from_date   = $_GET['from_date'] ?? null;
$to_date     = $_GET['to_date'] ?? null;

if (!$ledger_db_id) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => 'Missing ledger_db_id parameter'
    ]);
    exit;
}

try {
    /* ---------------- Build Dynamic SQL ---------------- */
    $sql = "
        SELECT * FROM `ac_banking_stmts`
        WHERE `ledger_db_id` = :ledger_db_id
    ";

    $params = [
        ':ledger_db_id' => $ledger_db_id
    ];

    if (!empty($from_date)) {
        $sql .= " AND `date` >= :from_date";
        $params[':from_date'] = $from_date;
    }

    if (!empty($to_date)) {
        $sql .= " AND `date` <= :to_date";
        $params[':to_date'] = $to_date;
    }

    $sql .= " ORDER BY `date` DESC, `id` DESC";

    /* ---------------- Prepare & Execute ---------------- */
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data'    => $data
    ]);

} catch (PDOException $e) {

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Database error',
        'details' => $e->getMessage()
    ]);
}
