<?php
session_start();
header('Content-Type: application/json');

require_once '../../server/db_connection.php';

$input = json_decode(file_get_contents("php://input"), true);

$invoice_id = $input['invoice_id'] ?? '';
$user_id    = $_SESSION['user_id'] ?? '';
$user_name  = $_SESSION['user_name'] ?? '';

if (empty($invoice_id)) {
    echo json_encode(['success' => false, 'message' => 'Invoice ID is required']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT meta_data, total_amount 
        FROM invoices 
        WHERE sys_id = ?
    ");
    $stmt->execute([$invoice_id]);
    $invoiceData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$invoiceData) {
        echo json_encode(['success' => false, 'message' => 'Invoice not found']);
        exit();
    }

    $metaArray = json_decode($invoiceData['meta_data'], true);

    if (!is_array($metaArray)) {
        $metaArray = [
            'created_by_date' => [
                'user' => 'Unknown',
                'date' => date('d-m-Y H:i')
            ],
            'updated_by_date' => []
        ];
    }

    if (!isset($metaArray['updated_by_date'])) {
        $metaArray['updated_by_date'] = [];
    }

    array_unshift($metaArray['updated_by_date'], [
        'user' => $user_name . '||' . $user_id,
        'date' => date('d-m-Y H:i')
    ]);

    $meta_data = json_encode($metaArray, JSON_PRETTY_PRINT);

    $updateStmt = $pdo->prepare("
        UPDATE invoices 
        SET paid_amount = ?, due_amount = ?, status =?, meta_data = ?
        WHERE sys_id = ?
    ");

    $result = $updateStmt->execute([
        $invoiceData['total_amount'],
        0, 
        1,
        $meta_data,
        $invoice_id
    ]);

    echo json_encode([
        'success' => $result,
        'message' => $result ? 'Invoice updated successfully' : 'Failed to update invoice'
    ]);

} catch (Exception $e) {
    error_log("Invoice update error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
