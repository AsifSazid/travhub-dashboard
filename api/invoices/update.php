<?php
// server/invoice-update.php
session_start();
header('Content-Type: application/json');

require_once '../../server/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get all POST data
$invoice_id = $_POST['invoice_id'] ?? '';
$work_item_ids = $_POST['work_item_id'] ?? [];
$work_titles = $_POST['work_title'] ?? [];
$work_qtys = $_POST['work_qty'] ?? [];
$work_rates = $_POST['work_rate'] ?? [];
$work_particulars = $_POST['work_particular'] ?? [];
$amounts = $_POST['amount'] ?? [];

// Validation
if (empty($invoice_id)) {
    echo json_encode(['success' => false, 'message' => 'Invoice ID is required']);
    exit();
}

try {
    $pdo->beginTransaction();
    
    // First, get existing work items from database
    $getExistingStmt = $pdo->prepare("SELECT work_items FROM invoices WHERE sys_id = ?");
    $getExistingStmt->execute([$invoice_id]);
    $existingInvoice = $getExistingStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existingInvoice) {
        throw new Exception("Invoice not found");
    }
    
    $existingWorkItems = json_decode($existingInvoice['work_items'], true) ?? [];
    
    // Process work items
    $updatedWorkItems = [];
    $newItems = [];
    $existingIds = [];
    
    for ($i = 0; $i < count($work_titles); $i++) {
        $work_item_id = $work_item_ids[$i] ?? '';
        $title = trim($work_titles[$i] ?? '');
        
        // Skip empty items
        if (empty($title)) {
            continue;
        }
        
        $workItemData = [
            'title' => $title,
            'qty' => floatval($work_qtys[$i] ?? 0),
            'rate' => floatval($work_rates[$i] ?? 0),
            'particular' => $work_particulars[$i] ?? '',
            'amount' => floatval($amounts[$i] ?? 0)
        ];
        
        // If work item has ID, it's an existing item
        if (!empty($work_item_id)) {
            $workItemData['id'] = $work_item_id;
            $updatedWorkItems[] = $workItemData;
            $existingIds[] = $work_item_id;
        } else {
            // New item - generate new ID
            $workItemData['id'] = 'item_' . uniqid() . '_' . time();
            $newItems[] = $workItemData;
        }
    }
    
    // Merge existing, updated and new items
    // Keep existing items that were not in the form (not deleted)
    $finalWorkItems = [];
    
    foreach ($existingWorkItems as $existingItem) {
        $existingId = $existingItem['id'] ?? '';
        
        // If this item was in the form (updated), use updated version
        if (in_array($existingId, $existingIds)) {
            foreach ($updatedWorkItems as $updatedItem) {
                if (($updatedItem['id'] ?? '') === $existingId) {
                    $finalWorkItems[] = $updatedItem;
                    break;
                }
            }
        } else {
            // Item was not in form - keep as is (not deleted)
            $finalWorkItems[] = $existingItem;
        }
    }
    
    // Add new items
    foreach ($newItems as $newItem) {
        $finalWorkItems[] = $newItem;
    }
    
    // Prepare other data
    $client_info = [
        'title' => $_POST['client_title'] ?? '',
        'phone_no' => $_POST['client_phone_no'] ?? '',
        'cc' => $_POST['client_cc'] ?? ''
    ];
    
    $total_amount = floatval($_POST['total_amount'] ?? 0);
    $paid_amount = floatval($_POST['paid_amount'] ?? 0);
    $due_amount = floatval($_POST['due_amount'] ?? 0);
    
    // Vendor payment methods
    $vendor_payment_data = [];
    if (!empty($_POST['vendor_payment_methods'])) {
        $vendor_payment_data = json_decode($_POST['vendor_payment_methods'], true);
    }
    
    // Update invoice
    $updateStmt = $pdo->prepare("
        UPDATE invoices 
        SET 
            client_info = ?,
            work_items = ?,
            vendor_payment_methods = ?,
            total_amount = ?,
            paid_amount = ?,
            due_amount = ?,
            updated_at = NOW()
        WHERE sys_id = ?
    ");
    
    $result = $updateStmt->execute([
        json_encode($client_info, JSON_UNESCAPED_UNICODE),
        json_encode($finalWorkItems, JSON_UNESCAPED_UNICODE),
        json_encode($vendor_payment_data, JSON_UNESCAPED_UNICODE),
        $total_amount,
        $paid_amount,
        $due_amount,
        $invoice_id
    ]);
    
    if ($result) {
        $pdo->commit();
        
        // Update vendor.json if needed
        if (!empty($vendor_payment_data)) {
            $vendor_json_path = __DIR__ . '/../server/invoice-vendor.json';
            if (file_exists($vendor_json_path)) {
                $existing_data = [];
                if (filesize($vendor_json_path) > 0) {
                    $existing_json = file_get_contents($vendor_json_path);
                    $existing_data = json_decode($existing_json, true) ?: [];
                }
                
                $merged_data = array_merge($existing_data, $vendor_payment_data);
                file_put_contents($vendor_json_path, json_encode($merged_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Invoice updated successfully',
            'invoice_id' => $invoice_id,
            'work_items_count' => count($finalWorkItems),
            'redirect' => 'index-invoice.php'
        ]);
    } else {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update invoice'
        ]);
    }
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}