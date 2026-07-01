<?php
// api/accounts/fetch_account_statement_api.php
require '../../server/db_connection.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

$ledger_db_id = $_GET['ledger_db_id'] ?? '';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
$opening_only = isset($_GET['opening_only']) ? (int)$_GET['opening_only'] : 0;

if (empty($ledger_db_id)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'ledger_db_id is required'
    ]);
    exit;
}

try {
    $sql = "SELECT * FROM ac_banking_stmts WHERE ledger_db_id = :ledger_db_id";
    $params = [':ledger_db_id' => $ledger_db_id];
    
    if ($opening_only) {
        $sql .= " AND particular = 'Opening Balance'";
    }
    
    if (!empty($from_date) && !empty($to_date)) {
        $sql .= " AND DATE(date) BETWEEN :from_date AND :to_date";
        $params[':from_date'] = $from_date;
        $params[':to_date'] = $to_date;
    } elseif (!empty($from_date)) {
        $sql .= " AND DATE(date) >= :from_date";
        $params[':from_date'] = $from_date;
    } elseif (!empty($to_date)) {
        $sql .= " AND DATE(date) <= :to_date";
        $params[':to_date'] = $to_date;
    }
    
    // *** এখানে পরিবর্তন: DESC ব্যবহার করা হয়েছে ***
    $sql .= " ORDER BY date DESC, sys_id DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // is_historical ফিল্ড না থাকলে ডিফল্ট 0 সেট করুন
    foreach ($data as &$row) {
        if (!isset($row['is_historical'])) {
            $row['is_historical'] = 0;
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error',
        'error' => $e->getMessage()
    ]);
}
?>