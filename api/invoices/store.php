<?php
// api/invoices/store.php
session_start();

// Enhanced error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/invoice_store_errors.log');

// Log incoming request
$logData = "[" . date('Y-m-d H:i:s') . "] INVOICE STORE REQUEST START\n" .
    "========================================\n" .
    "Method: " . $_SERVER['REQUEST_METHOD'] . "\n" .
    "Content Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'Not set') . "\n" .
    "POST Data:\n";
    
foreach ($_POST as $key => $value) {
    if (is_array($value)) {
        $logData .= "$key: " . print_r($value, true) . "\n";
    } else {
        $logData .= "$key: " . substr($value, 0, 100) . "...\n";
    }
}
$logData .= "========================================\n\n";

// Include required files
require '../../server/db_connection.php';
require '../../server/uuid_with_system_id_generator.php';
require '../../server/generate_meta_data.php';

header('Content-Type: application/json');

// Allow CORS if needed
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

function sanitize($data)
{
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Function to convert number to words in English
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

// Get client information
function getClientInfo($pdo, $clientIdentifier) {
    // Try to find by sys_id first
    $stmt = $pdo->prepare("SELECT sys_id, name FROM clients WHERE sys_id = ? LIMIT 1");
    $stmt->execute([$clientIdentifier]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($client) {
        return [
            'client_sys_id' => $client['sys_id'],
            'client_name' => $client['name']
        ];
    }
    
    // Try by title if not found by sys_id
    $stmt = $pdo->prepare("SELECT sys_id, name FROM clients WHERE name = ? LIMIT 1");
    $stmt->execute([$clientIdentifier]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($client) {
        return [
            'client_sys_id' => $client['sys_id'],
            'client_name' => $client['name']
        ];
    }
    
    // Return as is if not found
    return [
        'client_sys_id' => $clientIdentifier,
        'client_name' => $clientIdentifier
    ];
}

// Function to process bank/mfs data from form array format
function processFormBankMfsData() {
    $result = ['banks' => [], 'mfs' => []];
    
    // Process Bank Data
    $bankIndex = 0;
    while (isset($_POST["bank[$bankIndex][vendor_bank]"])) {
        $bank = [
            'vendor_bank' => $_POST["bank[$bankIndex][vendor_bank]"] ?? '',
            'vendor_bank_account' => $_POST["bank[$bankIndex][vendor_bank_account]"] ?? '',
            'vendor_bank_branch' => $_POST["bank[$bankIndex][vendor_bank_branch]"] ?? '',
            'vendor_bank_routing' => $_POST["bank[$bankIndex][vendor_bank_routing]"] ?? ''
        ];
        
        // Only add if at least one field has value
        if (!empty(array_filter(array_values($bank)))) {
            $result['banks'][] = $bank;
        }
        $bankIndex++;
    }
    
    // Also check for JSON format bank data
    if (isset($_POST['bank']) && is_string($_POST['bank'])) {
        $jsonData = json_decode($_POST['bank'], true);
        if (is_array($jsonData)) {
            foreach ($jsonData as $bank) {
                if (!empty(array_filter(array_values($bank)))) {
                    $result['banks'][] = $bank;
                }
            }
        }
    }
    
    // Process MFS Data
    $mfsIndex = 0;
    while (isset($_POST["mfs[$mfsIndex][vendor_mfs_title]"])) {
        $mfs = [
            'vendor_mfs_title' => $_POST["mfs[$mfsIndex][vendor_mfs_title]"] ?? '',
            'vendor_mfs_type' => $_POST["mfs[$mfsIndex][vendor_mfs_type]"] ?? '',
            'vendor_amount_note' => $_POST["mfs[$mfsIndex][vendor_amount_note]"] ?? '',
            'vendor_mfs_account' => []
        ];
        
        // Process MFS Accounts (they come as array)
        $accountIndex = 0;
        while (isset($_POST["mfs[$mfsIndex][vendor_mfs_account][$accountIndex]"])) {
            $account = $_POST["mfs[$mfsIndex][vendor_mfs_account][$accountIndex]"] ?? '';
            if (!empty(trim($account))) {
                $mfs['vendor_mfs_account'][] = $account;
            }
            $accountIndex++;
        }
        
        // Also check for single account
        if (isset($_POST["mfs[$mfsIndex][vendor_mfs_account]"]) && 
            is_string($_POST["mfs[$mfsIndex][vendor_mfs_account]"]) &&
            !empty(trim($_POST["mfs[$mfsIndex][vendor_mfs_account]"]))) {
            $mfs['vendor_mfs_account'][] = $_POST["mfs[$mfsIndex][vendor_mfs_account]"];
        }
        
        // Only add if at least one field has value
        if (!empty(array_filter(array_values($mfs)))) {
            $result['mfs'][] = $mfs;
        }
        $mfsIndex++;
    }
    
    // Also check for JSON format mfs data
    if (isset($_POST['mfs']) && is_string($_POST['mfs'])) {
        $jsonData = json_decode($_POST['mfs'], true);
        if (is_array($jsonData)) {
            foreach ($jsonData as $mfs) {
                if (!empty(array_filter(array_values($mfs)))) {
                    $result['mfs'][] = $mfs;
                }
            }
        }
    }
    
    return $result;
}

// Process POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start transaction
        $pdo->beginTransaction();

        // ==================== BASIC INVOICE DATA ====================
        $date = isset($_POST['date']) ? sanitize($_POST['date']) : date('Y-m-d');
        $client_title = isset($_POST['client_title']) ? sanitize($_POST['client_title']) : '';
        $client_phone_no = isset($_POST['client_phone_no']) ? sanitize($_POST['client_phone_no']) : '';
        $client_cc = isset($_POST['client_cc']) ? sanitize($_POST['client_cc']) : '';

        $total_amount = isset($_POST['total_amount']) ? (float) $_POST['total_amount'] : 0;
        $paid_amount = isset($_POST['paid_amount']) ? (float) $_POST['paid_amount'] : 0;
        $due_amount = isset($_POST['due_amount']) ? (float) $_POST['due_amount'] : 0;

        // Validate required fields
        if (empty($date)) {
            throw new Exception("Invoice date is required");
        }
        if (empty($client_title)) {
            throw new Exception("Client is required");
        }

        // ==================== GENERATE INVOICE NUMBER ====================
        $uuid = generateIDs('invoices');
        $invoice_no = $uuid['sys_id'];

        // ==================== GET CLIENT INFO ====================
        $clientInfo = getClientInfo($pdo, $client_title);
        $client_sys_id = $clientInfo['client_sys_id'];
        $client_name = $clientInfo['client_name'];

        // ==================== CLIENT INFO JSON ====================
        $client_info = json_encode([
            'title' => $client_title,
            'phone_no' => $client_phone_no,
            'cc' => $client_cc
        ], JSON_UNESCAPED_UNICODE);

        // ==================== WORK ITEMS ====================
        $work_items = [];
        
        // Check if work_title exists and is array
        if (isset($_POST['work_title']) && is_array($_POST['work_title'])) {
            $work_titles = $_POST['work_title'];
            $work_qtys = isset($_POST['work_qty']) && is_array($_POST['work_qty']) ? $_POST['work_qty'] : [];
            $work_rates = isset($_POST['work_rate']) && is_array($_POST['work_rate']) ? $_POST['work_rate'] : [];
            $work_particulars = isset($_POST['work_particular']) && is_array($_POST['work_particular']) ? $_POST['work_particular'] : [];
            $amounts = isset($_POST['amount']) && is_array($_POST['amount']) ? $_POST['amount'] : [];

            $itemCount = count($work_titles);
            
            for ($i = 0; $i < $itemCount; $i++) {
                $work_items[] = [
                    'title' => isset($work_titles[$i]) ? sanitize($work_titles[$i]) : '',
                    'qty' => isset($work_qtys[$i]) ? (int) $work_qtys[$i] : 1,
                    'rate' => isset($work_rates[$i]) ? (float) $work_rates[$i] : 0,
                    'particular' => isset($work_particulars[$i]) ? sanitize($work_particulars[$i]) : '',
                    'amount' => isset($amounts[$i]) ? (float) $amounts[$i] : 0
                ];
            }
        }
        
        $work_items_json = json_encode($work_items, JSON_UNESCAPED_UNICODE);

        // ==================== VENDOR PAYMENT METHODS ====================
        $vendor_payment_methods = processFormBankMfsData();

        $vendor_payment_methods_json = json_encode($vendor_payment_methods, JSON_UNESCAPED_UNICODE);

        // ==================== AMOUNT IN WORDS ====================
        $total_amount_in_words = numberToWords($total_amount);

        // ==================== META DATA ====================
        $meta_data = buildMetaData(null, $_SESSION['user_name'] ?? 'system');

        // ==================== INSERT INTO DATABASE ====================
        $stmt = $pdo->prepare("
            INSERT INTO invoices (
                uuid, sys_id, date, 
                client_sys_id, client_name, client_info,
                total_amount, paid_amount, due_amount, 
                total_amount_in_words, work_items, 
                vendor_payment_methods, status, meta_data,
                created_at, updated_at
            ) VALUES (
                :uuid, :sys_id, :date,
                :client_sys_id, :client_name, :client_info,
                :total_amount, :paid_amount, :due_amount,
                :words, :work_items, :vendor_methods, 
                :status, :meta_data,
                NOW(), NOW()
            )
        ");

        $result = $stmt->execute([
            ':uuid' => $uuid['uuid'],
            ':sys_id' => $invoice_no,
            ':date' => $date,
            ':client_sys_id' => $client_sys_id,
            ':client_name' => $client_name,
            ':client_info' => $client_info,
            ':total_amount' => $total_amount,
            ':paid_amount' => $paid_amount,
            ':due_amount' => $due_amount,
            ':words' => $total_amount_in_words,
            ':work_items' => $work_items_json,
            ':vendor_methods' => $vendor_payment_methods_json,
            ':status' => 0,
            ':meta_data' => $meta_data
        ]);

        if (!$result) {
            throw new Exception("Database insertion failed: " . implode(', ', $stmt->errorInfo()));
        }

        $invoice_id = $pdo->lastInsertId();

        // ==================== UPDATE VENDOR INFO (OPTIONAL) ====================
        $vendorJsonPath = __DIR__ . '/../../vendor.json';
        if (file_exists($vendorJsonPath)) {
            try {
                $vendorData = json_decode(file_get_contents($vendorJsonPath), true);
                if ($vendorData) {
                    $checkStmt = $pdo->prepare(
                        "SELECT id FROM vendor_info WHERE company_name = ? LIMIT 1"
                    );
                    $checkStmt->execute([$vendorData['company_name'] ?? '']);
                    
                    if (!$checkStmt->fetch()) {
                        $insertStmt = $pdo->prepare("
                            INSERT INTO vendor_info
                            (company_name, logo, phone, email, address, created_at)
                            VALUES (?, ?, ?, ?, ?, NOW())
                        ");
                        
                        $addressJson = isset($vendorData['address']) ? 
                            json_encode($vendorData['address'], JSON_UNESCAPED_UNICODE) : '{}';
                        
                        $insertStmt->execute([
                            $vendorData['company_name'] ?? '',
                            $vendorData['logo'] ?? '',
                            $vendorData['phone'] ?? '',
                            $vendorData['email'] ?? '',
                            $addressJson
                        ]);
                    }
                }
            } catch (Exception $e) {
                // Log but don't fail invoice creation
                error_log("Vendor info update failed: " . $e->getMessage());
            }
        }

        // Commit transaction
        $pdo->commit();

        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Invoice saved successfully!',
            'invoice_id' => (int) $invoice_id,
            'invoice_no' => $invoice_no,
            'data' => [
                'client_sys_id' => $client_sys_id,
                'client_name' => $client_name,
                'total_amount' => $total_amount,
                'total_in_words' => $total_amount_in_words,
                'work_items_count' => count($work_items),
                'banks_count' => count($vendor_payment_methods['banks']),
                'mfs_count' => count($vendor_payment_methods['mfs'])
            ]
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        // Log error
        $errorMessage = "Invoice Store Error: " . $e->getMessage() . 
                       " in " . $e->getFile() . ":" . $e->getLine();
        
        error_log($errorMessage);


        // Return error response
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error saving invoice: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
} else {
    // Invalid request method
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Please use POST.'
    ], JSON_UNESCAPED_UNICODE);
}
?>