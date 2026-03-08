<?php
// api/invoices/edit-invoice.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database connection
require_once '../../server/db_connection.php';

// Check if invoice ID is provided
if (!isset($_GET['invoice']) || empty($_GET['invoice'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invoice ID is required'
    ]);
    exit();
}

$invoice_id = $_GET['invoice'];

try {
    // Fetch invoice data
    $stmt = $pdo->prepare("SELECT * FROM invoices WHERE sys_id = :id");
    $stmt->execute([':id' => $invoice_id]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$invoice) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Invoice not found'
        ]);
        exit();
    }

    // Decode JSON fields from invoices table
    $invoice_no = $invoice['sys_id'] ?? '[]';
    $client_info = json_decode($invoice['client_info'] ?? '[]', true);
    $work_items = json_decode($invoice['work_items'] ?? '[]', true);
    
    // var_dump($invoice_no);
    
    $vendor_payment_methods = json_decode($invoice['vendor_payment_methods'] ?? '[]', true);
    
    // Additional invoice data
    $invoice_data = [
        'id' => $invoice['sys_id'],
        'invoice_no' => $invoice_no,
        'client_name' => $invoice['client_name'],
        'date' => $invoice['date'],
        'total_amount' => floatval($invoice['total_amount']),
        'paid_amount' => floatval($invoice['paid_amount']),
        'due_amount' => floatval($invoice['due_amount']),
        'created_at' => $invoice['created_at'],
        'updated_at' => $invoice['updated_at']
    ];

    // Load vendor data from JSON file
    $vendor_json_path = __DIR__ . '/../../../server/vendor.json';
    $vendor_data = [];
    if (file_exists($vendor_json_path)) {
        $vendor_json = file_get_contents($vendor_json_path);
        $vendor_data = json_decode($vendor_json, true);
    }

    // Prepare response data
    $response = [
        'success' => true,
        'message' => 'Invoice data retrieved successfully',
        'data' => [
            'invoice' => $invoice_data,
            'client_info' => $client_info,
            'work_items' => $work_items,
            'vendor_data' => $vendor_data,
            'vendor_payment_methods' => $vendor_payment_methods
        ]
    ];

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
    exit();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
    exit();
}