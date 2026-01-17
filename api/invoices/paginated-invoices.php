<?php

session_start();
require '../../server/db_connection.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Get query parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$status = isset($_GET['status']) ? $_GET['status'] : null;
$search = isset($_GET['search']) ? $_GET['search'] : null;

// Calculate offset
$offset = ($page - 1) * $limit;

// Build query
$whereClauses = [];
$params = [];

if ($status) {
    $whereClauses[] = "status = :status";
    $params[':status'] = $status;
}

if ($search) {
    $whereClauses[] = "(client_info LIKE :search OR sys_id LIKE :search)";
    $params[':search'] = "%$search%";
}

$whereSQL = '';
if (!empty($whereClauses)) {
    $whereSQL = 'WHERE ' . implode(' AND ', $whereClauses);
}

try {
    // Get total count
    $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM invoices $whereSQL");
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $totalResult = $countStmt->fetch(PDO::FETCH_ASSOC);
    $total = $totalResult['total'];
    
    // Get paginated data
    $stmt = $pdo->prepare("
        SELECT * FROM invoices 
        $whereSQL 
        ORDER BY created_at DESC 
        LIMIT :limit OFFSET :offset
    ");
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $invoices = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Process each invoice (same logic as before)
        $client_info = json_decode($row['client_info'], true);
        $work_items = json_decode($row['work_items'], true) ?: [];
        
        $status = 'pending';
        if ($row['due_amount'] == 0) {
            $status = 'paid';
        } elseif ($row['paid_amount'] > 0 && $row['due_amount'] > 0) {
            $status = 'partial';
        }
        
        $due_date = new DateTime($row['date']);
        $due_date->modify('+30 days');
        $now = new DateTime();
        
        if ($status !== 'paid' && $now > $due_date) {
            $status = 'overdue';
        }
        
        $invoices[] = [
            "id" => (int)$row['id'],
            "invoice_no" => $row['sys_id'],
            "client_name" => $client_info['title'] ?? 'Unknown Client',
            "client_email" => $client_info['cc'] ?? '',
            "phone" => $client_info['phone_no'] ?? '',
            "total_amount" => floatval($row['total_amount']),
            "paid_amount" => floatval($row['paid_amount']),
            "due_amount" => floatval($row['due_amount']),
            "created_at" => $row['created_at'],
            "due_date" => $due_date->format('Y-m-d'),
            "status" => $status,
            "currency" => "BDT"
        ];
    }
    
    echo json_encode([
        'success' => true,
        'invoices' => $invoices,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'invoices' => []
    ]);
}

?>