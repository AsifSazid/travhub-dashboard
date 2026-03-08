<?php
// server/invoice-update.php
session_start();
header('Content-Type: application/json');

require_once '../../server/db_connection.php';

// Function to convert number to words in English (same as in store.php)
function numberToWords($number) {
    $ones = array(
        0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 
        5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
        10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 
        14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen', 
        17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen'
    );

    $tens = array(
        2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty',
        6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety'
    );

    if ($number == 0) {
        return 'Zero Taka Only';
    }

    $parts = explode('.', number_format($number, 2, '.', ''));
    $whole = intval($parts[0]);
    $decimal = isset($parts[1]) ? intval($parts[1]) : 0;

    $result = '';

    // Convert lakhs
    if ($whole >= 100000) {
        $lakhs = floor($whole / 100000);
        $result .= convertBelowThousand($lakhs, $ones, $tens) . ' Lakh';
        $whole %= 100000;
        if ($whole > 0) $result .= ' ';
    }

    // Convert thousands
    if ($whole >= 1000) {
        $thousands = floor($whole / 1000);
        $result .= convertBelowThousand($thousands, $ones, $tens) . ' Thousand';
        $whole %= 1000;
        if ($whole > 0) $result .= ' ';
    }

    // Convert hundreds
    if ($whole > 0) {
        $result .= convertBelowThousand($whole, $ones, $tens);
    }

    $result = trim($result) . ' Taka';

    // Add decimal (poisha)
    if ($decimal > 0) {
        $result .= ' and ' . convertBelowThousand($decimal, $ones, $tens) . ' Poisha';
    } else {
        $result .= ' Only';
    }

    return $result;
}

function convertBelowThousand($number, $ones, $tens) {
    $result = '';

    if ($number >= 100) {
        $hundreds = floor($number / 100);
        $result .= $ones[$hundreds] . ' Hundred';
        $number %= 100;
        if ($number > 0) $result .= ' and ';
    }

    if ($number >= 20) {
        $ten = floor($number / 10);
        $result .= $tens[$ten];
        $number %= 10;
        if ($number > 0) $result .= '-' . $ones[$number];
    } elseif ($number > 0) {
        $result .= $ones[$number];
    }

    return $result;
}

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get all POST data
$invoice_id = $_POST['invoice_id'] ?? '';

// Validation
if (empty($invoice_id)) {
    echo json_encode(['success' => false, 'message' => 'Invoice ID is required']);
    exit();
}

try {
    // প্রথমে দেখি কোন ফর্মেটে ডাটা আসছে
    $work_items = [];
    
    // Option 1: যদি নতুন ফর্মেটে আসে (work_items array)
    if (isset($_POST['work_items']) && is_array($_POST['work_items']) && !empty($_POST['work_items'])) {
        // নতুন ফর্মেট প্রসেস করি
        $work_items_data = $_POST['work_items'];
        
        foreach ($work_items_data as $itemData) {
            if (!is_array($itemData)) continue;
            
            $action = $itemData['action'] ?? 'update';
            $itemId = $itemData['id'] ?? '';
            $title = trim($itemData['title'] ?? '');
            
            // Skip deleted items
            if ($action === 'delete') continue;
            
            // Skip empty titles
            if (empty($title)) continue;
            
            $work_items[] = [
                'id' => $itemId ?: ('item_' . uniqid() . '_' . time()),
                'title' => sanitize($title),
                'qty' => isset($itemData['qty']) ? (float) $itemData['qty'] : 1,
                'rate' => isset($itemData['rate']) ? (float) $itemData['rate'] : 0,
                'particular' => isset($itemData['particular']) ? sanitize($itemData['particular']) : '',
                'amount' => isset($itemData['amount']) ? (float) $itemData['amount'] : 0
            ];
        }
    }
    // Option 2: যদি পুরানো ফর্মেটে আসে (work_title[], work_qty[] ইত্যাদি)
    else if (isset($_POST['work_title']) && is_array($_POST['work_title'])) {
        $work_titles = $_POST['work_title'];
        $work_qtys = isset($_POST['work_qty']) && is_array($_POST['work_qty']) ? $_POST['work_qty'] : [];
        $work_rates = isset($_POST['work_rate']) && is_array($_POST['work_rate']) ? $_POST['work_rate'] : [];
        $work_particulars = isset($_POST['work_particular']) && is_array($_POST['work_particular']) ? $_POST['work_particular'] : [];
        $amounts = isset($_POST['amount']) && is_array($_POST['amount']) ? $_POST['amount'] : [];
        $work_item_ids = isset($_POST['work_item_id']) && is_array($_POST['work_item_id']) ? $_POST['work_item_id'] : [];

        $itemCount = count($work_titles);
        
        for ($i = 0; $i < $itemCount; $i++) {
            $title = isset($work_titles[$i]) ? trim($work_titles[$i]) : '';
            
            // Skip empty items
            if (empty($title)) continue;
            
            $work_items[] = [
                'id' => isset($work_item_ids[$i]) ? $work_item_ids[$i] : ('item_' . uniqid() . '_' . time()),
                'title' => sanitize($title),
                'qty' => isset($work_qtys[$i]) ? (float) $work_qtys[$i] : 1,
                'rate' => isset($work_rates[$i]) ? (float) $work_rates[$i] : 0,
                'particular' => isset($work_particulars[$i]) ? sanitize($work_particulars[$i]) : '',
                'amount' => isset($amounts[$i]) ? (float) $amounts[$i] : 0
            ];
        }
    }
    
    // Prepare client info
    $client_info = [
        'phone_no' => sanitize($_POST['client_phone_no'] ?? ''),
        'cc' => sanitize($_POST['client_cc'] ?? '')
    ];
    
    // Get other form data
    $date = $_POST['date'] ?? date('Y-m-d');
    $total_amount = floatval($_POST['total_amount'] ?? 0);
    $paid_amount = floatval($_POST['paid_amount'] ?? 0);
    $due_amount = floatval($_POST['due_amount'] ?? 0);
    
    // Generate amount in words
    $total_amount_in_words = numberToWords($total_amount);
    
    // Vendor payment methods
    $vendor_payment_data = [];
    if (isset($_POST['vendor_payment_methods']) && $_POST['vendor_payment_methods'] !== 'undefined') {
        if (is_string($_POST['vendor_payment_methods'])) {
            $vendor_payment_data = json_decode($_POST['vendor_payment_methods'], true) ?? [];
        }
    }
    
    // Update invoice - total_amount_in_words ফিল্ডও আপডেট করুন
    $updateStmt = $pdo->prepare("
        UPDATE invoices 
        SET 
            date = ?,
            client_info = ?,
            work_items = ?,
            vendor_payment_methods = ?,
            total_amount = ?,
            paid_amount = ?,
            due_amount = ?,
            total_amount_in_words = ?,
            updated_at = NOW()
        WHERE sys_id = ?
    ");
    
    $result = $updateStmt->execute([
        $date,
        json_encode($client_info, JSON_UNESCAPED_UNICODE),
        json_encode($work_items, JSON_UNESCAPED_UNICODE),
        json_encode($vendor_payment_data, JSON_UNESCAPED_UNICODE),
        $total_amount,
        $paid_amount,
        $due_amount,
        $total_amount_in_words, 
        $invoice_id
    ]);
    
    if ($result) {
        // Update vendor.json if needed
        if (!empty($vendor_payment_data)) {
            $vendor_json_path = __DIR__ . '/invoice-vendor.json';
            $existing_data = ['banks' => [], 'mfs' => []];
            
            if (file_exists($vendor_json_path)) {
                $existing_json = file_get_contents($vendor_json_path);
                $existing_data = json_decode($existing_json, true) ?: ['banks' => [], 'mfs' => []];
            }
            
            if (!empty($vendor_payment_data['banks'])) {
                $existing_data['banks'] = $vendor_payment_data['banks'];
            }
            
            if (!empty($vendor_payment_data['mfs'])) {
                $existing_data['mfs'] = $vendor_payment_data['mfs'];
            }
            
            file_put_contents($vendor_json_path, json_encode($existing_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Invoice updated successfully',
            'invoice_id' => $invoice_id,
            'work_items_count' => count($work_items),
            'total_amount_in_words' => $total_amount_in_words  // ডিবাগিং এর জন্য পাঠানো
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update invoice'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Invoice update error for {$invoice_id}: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}