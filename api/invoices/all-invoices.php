<?php

session_start();
require '../../server/db_connection.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Load invoices from database
try {
    $stmt = $pdo->query("
        SELECT * FROM invoices
        ORDER BY created_at DESC
    ");

    $invoices = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $client_info = json_decode($row['client_info'], true);
        $work_items = json_decode($row['work_items'], true) ?: [];

        // Calculate status
        $status = 'pending';
        if ($row['due_amount'] == 0) {
            $status = 'paid';
        } elseif ($row['paid_amount'] > 0 && $row['due_amount'] > 0) {
            $status = 'partial';
        }
        
        $meta_data = json_decode($row['meta_data'] ?? '{}', true) ?: [];
        
        $created_by = $meta_data['created_by_date']['user'] ?? 'System';
        
        // Updated By extraction (Last item from array logic)
        $updated_by = 'Not updated';
        if (!empty($meta_data['updated_by_date']) && is_array($meta_data['updated_by_date'])) {
            $last_update = end($meta_data['updated_by_date']);
            $updated_by = $last_update['user'] ?? 'System';
        }

        // Check overdue
        $due_date = new DateTime($row['date']);
        $due_date->modify('+30 days');
        $now = new DateTime();
        
        if ($status !== 'paid' && $now > $due_date) {
            $status = 'overdue';
        }

        $invoices[] = [
            "id" => (int)$row['id'],
            "invoice_no" => $row['sys_id'],
            "client_sys_id" => $row['client_sys_id'] ?? 'Unknown Client System ID',
            "client_name" => $row['client_name'] ?? $client_info['title'] ?? 'Unknown Client',
            "client_email" => $client_info['cc'] ?? '',
            "phone" => $client_info['phone_no'] ?? '',
            "total_amount" => floatval($row['total_amount']),
            "paid_amount" => floatval($row['paid_amount']),
            "due_amount" => floatval($row['due_amount']),
            "created_at" => $row['created_at'],
            "updated_at" => $row['updated_at'],
            "created_by" => $created_by,
            "updated_by" => $updated_by,
            "invoice_date" => $row['date'],
            "due_date" => $due_date->format('Y-m-d'),
            "status" => $status,
            "currency" => "BDT",
            "description" => "Visa Application Services",
            "items" => array_map(function ($item) {
                return [
                    'description' => $item['title'] ?? 'Service',
                    'quantity' => intval($item['qty'] ?? 1),
                    'unit_price' => floatval($item['rate'] ?? 0),
                    'total' => floatval($item['amount'] ?? 0)
                ];
            }, $work_items)
        ];
    }
    
    echo json_encode([
        'success' => true,
        'invoices' => $invoices,
        'total' => count($invoices),
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