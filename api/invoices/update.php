<?php
// server/invoice-update.php
session_start();
header('Content-Type: application/json');

// Database connection
require_once '../../server/db_connection.php';

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit();
}

// Check authentication
// if (!isset($_SESSION['user_id'])) {
//     http_response_code(401);
//     echo json_encode([
//         'success' => false,
//         'message' => 'Unauthorized access'
//     ]);
//     exit();
// }

// Get POST data
$invoice_id = isset($_POST['invoice_id']) ? $_POST['invoice_id'] : '';
$invoice_no = isset($_POST['invoice_no']) ? $_POST['invoice_no'] : '';
$date = isset($_POST['date']) ? $_POST['date'] : date('Y-m-d');
$client_title = isset($_POST['client_title']) ? trim($_POST['client_title']) : '';
$client_phone_no = isset($_POST['client_phone_no']) ? trim($_POST['client_phone_no']) : '';
$client_cc = isset($_POST['client_cc']) ? trim($_POST['client_cc']) : '';
$total_amount = isset($_POST['total_amount']) ? floatval($_POST['total_amount']) : 0;
$paid_amount = isset($_POST['paid_amount']) ? floatval($_POST['paid_amount']) : 0;
$due_amount = isset($_POST['due_amount']) ? floatval($_POST['due_amount']) : 0;

// Get work items
$work_titles = isset($_POST['work_title']) ? $_POST['work_title'] : [];
$work_qtys = isset($_POST['work_qty']) ? $_POST['work_qty'] : [];
$work_rates = isset($_POST['work_rate']) ? $_POST['work_rate'] : [];
$work_particulars = isset($_POST['work_particular']) ? $_POST['work_particular'] : [];
$amounts = isset($_POST['amount']) ? $_POST['amount'] : [];

// Get vendor payment methods
$vendor_payment_methods = isset($_POST['vendor_payment_methods']) ? $_POST['vendor_payment_methods'] : '';

// Debug: Check received data
error_log("Invoice ID: $invoice_id");
error_log("Invoice No: $invoice_no");
error_log("Client Title: $client_title");
error_log("Work Items Count: " . count($work_titles));

// Validation
if (empty($invoice_id)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invoice ID is required'
    ]);
    exit();
}

if (empty($client_title)) {
    echo json_encode([
        'success' => false,
        'message' => 'Client name is required'
    ]);
    exit();
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Prepare client info
    $client_info = [
        'title' => $client_title,
        'phone_no' => $client_phone_no,
        'cc' => $client_cc
    ];

    // Prepare work items
    $work_items = [];
    $total_calculated = 0;
    
    for ($i = 0; $i < count($work_titles); $i++) {
        if (!empty($work_titles[$i])) {
            $qty = floatval($work_qtys[$i] ?? 0);
            $rate = floatval($work_rates[$i] ?? 0);
            $amount = floatval($amounts[$i] ?? 0);
            
            $work_items[] = [
                'title' => $work_titles[$i],
                'qty' => $qty,
                'rate' => $rate,
                'particular' => $work_particulars[$i] ?? '',
                'amount' => $amount
            ];
            
            $total_calculated += $amount;
        }
    }

    // Parse vendor payment methods
    $vendor_payment_data = [];
    if (!empty($vendor_payment_methods)) {
        $vendor_payment_data = json_decode($vendor_payment_methods, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $vendor_payment_data = [];
        }
    }

    // Check if invoice exists
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE sys_id = :id");
    $checkStmt->execute([':id' => $invoice_id]);
    $invoiceExists = $checkStmt->fetchColumn();

    if ($invoiceExists > 0) {
        // Update existing invoice
        $stmt = $pdo->prepare("
            UPDATE invoices 
            SET 
                client_info = :client_info,
                work_items = :work_items,
                vendor_payment_methods = :vendor_payment_methods,
                total_amount = :total_amount,
                paid_amount = :paid_amount,
                due_amount = :due_amount,
                updated_at = NOW()
            WHERE sys_id = :id
        ");

        $result = $stmt->execute([
            ':id' => $invoice_id,
            ':client_info' => json_encode($client_info, JSON_UNESCAPED_UNICODE),
            ':work_items' => json_encode($work_items, JSON_UNESCAPED_UNICODE),
            ':vendor_payment_methods' => json_encode($vendor_payment_data, JSON_UNESCAPED_UNICODE),
            ':total_amount' => $total_amount,
            ':paid_amount' => $paid_amount,
            ':due_amount' => $due_amount
        ]);

        if ($result) {
            $pdo->commit();
            
            // Also update the vendor.json file with new payment methods
            if (!empty($vendor_payment_data)) {
                $vendor_json_path = __DIR__ . '/../server/invoice-vendor.json';
                if (file_exists($vendor_json_path)) {
                    // Read existing data
                    $existing_data = [];
                    if (filesize($vendor_json_path) > 0) {
                        $existing_json = file_get_contents($vendor_json_path);
                        $existing_data = json_decode($existing_json, true) ?: [];
                    }
                    
                    // Merge with new data (preserve existing, update with new)
                    $merged_data = array_merge($existing_data, $vendor_payment_data);
                    
                    // Save back to file
                    file_put_contents($vendor_json_path, json_encode($merged_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Invoice updated successfully',
                'invoice_id' => $invoice_id,
                'redirect' => 'index.php'
            ]);
        } else {
            $pdo->rollBack();
            echo json_encode([
                'success' => false,
                'message' => 'Failed to update invoice'
            ]);
        }
    } else {
        // Insert new invoice (if needed)
        $stmt = $pdo->prepare("
            INSERT INTO invoices (
                sys_id,
                invoice_no,
                date,
                client_info,
                work_items,
                vendor_payment_methods,
                total_amount,
                paid_amount,
                due_amount,
                created_at,
                updated_at
            ) VALUES (
                :id,
                :invoice_no,
                :date,
                :client_info,
                :work_items,
                :vendor_payment_methods,
                :total_amount,
                :paid_amount,
                :due_amount,
                NOW(),
                NOW()
            )
        ");

        $result = $stmt->execute([
            ':id' => $invoice_id,
            ':invoice_no' => json_encode(['invoice_no' => $invoice_no]),
            ':date' => $date,
            ':client_info' => json_encode($client_info, JSON_UNESCAPED_UNICODE),
            ':work_items' => json_encode($work_items, JSON_UNESCAPED_UNICODE),
            ':vendor_payment_methods' => json_encode($vendor_payment_data, JSON_UNESCAPED_UNICODE),
            ':total_amount' => $total_amount,
            ':paid_amount' => $paid_amount,
            ':due_amount' => $due_amount
        ]);

        if ($result) {
            $pdo->commit();
            echo json_encode([
                'success' => true,
                'message' => 'Invoice created successfully',
                'invoice_id' => $invoice_id,
                'redirect' => 'index.php'
            ]);
        } else {
            $pdo->rollBack();
            echo json_encode([
                'success' => false,
                'message' => 'Failed to create invoice'
            ]);
        }
    }

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Database error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}