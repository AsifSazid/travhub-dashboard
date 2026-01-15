<?php
// server/invoice-store.php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '../../db_connection.php';
require __DIR__ . '../../uuid_with_system_id_generator.php';
require __DIR__ . '../../generate_meta_data.php';

header('Content-Type: application/json');

function sanitize($data)
{
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Function to convert number to words in English
function numberToWords($number){
    $ones = array(
        0 => '',
        1 => 'One',
        2 => 'Two',
        3 => 'Three',
        4 => 'Four',
        5 => 'Five',
        6 => 'Six',
        7 => 'Seven',
        8 => 'Eight',
        9 => 'Nine',
        10 => 'Ten',
        11 => 'Eleven',
        12 => 'Twelve',
        13 => 'Thirteen',
        14 => 'Fourteen',
        15 => 'Fifteen',
        16 => 'Sixteen',
        17 => 'Seventeen',
        18 => 'Eighteen',
        19 => 'Nineteen'
    );

    $tens = array(
        2 => 'Twenty',
        3 => 'Thirty',
        4 => 'Forty',
        5 => 'Fifty',
        6 => 'Sixty',
        7 => 'Seventy',
        8 => 'Eighty',
        9 => 'Ninety'
    );

    // Handle zero
    if ($number == 0) {
        return 'Zero Taka Only';
    }

    // Split into whole and decimal parts
    $parts = explode('.', number_format($number, 2, '.', ''));
    $whole = intval($parts[0]);
    $decimal = isset($parts[1]) ? intval($parts[1]) : 0;

    $result = '';

    // Convert whole number part
    if ($whole >= 100000) {
        // Handle lakhs (Bangladeshi numbering system)
        $lakhs = floor($whole / 100000);
        $result .= convertBelowThousand($lakhs, $ones, $tens) . ' Lakh';
        $whole %= 100000;

        if ($whole > 0) {
            $result .= ' ';
        }
    }

    if ($whole >= 1000) {
        $thousands = floor($whole / 1000);
        $result .= convertBelowThousand($thousands, $ones, $tens) . ' Thousand';
        $whole %= 1000;

        if ($whole > 0) {
            $result .= ' ';
        }
    }

    if ($whole > 0) {
        $result .= convertBelowThousand($whole, $ones, $tens);
    }

    // Trim any extra spaces
    $result = trim($result);

    // Add "Taka"
    $result .= ' Taka';

    // Add decimal part
    if ($decimal > 0) {
        $result .= ' and ' . convertBelowThousand($decimal, $ones, $tens) . ' Poisha';
    } else {
        $result .= ' Only';
    }

    return $result;
}

// Helper function to convert numbers below 1000
function convertBelowThousand($number, $ones, $tens)
{
    $result = '';

    if ($number >= 100) {
        $hundreds = floor($number / 100);
        $result .= $ones[$hundreds] . ' Hundred';
        $number %= 100;

        if ($number > 0) {
            $result .= ' and ';
        }
    }

    if ($number >= 20) {
        $ten = floor($number / 10);
        $result .= $tens[$ten];
        $number %= 10;

        if ($number > 0) {
            $result .= '-' . $ones[$number];
        }
    } elseif ($number > 0) {
        $result .= $ones[$number];
    }

    return $result;
}

// Function to get client information from database
function getClientInfo($pdo, $clientTitle) {
    $stmt = $pdo->prepare("SELECT sys_id, title FROM clients WHERE title = ? LIMIT 1");
    $stmt->execute([$clientTitle]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($client) {
        return [
            'client_sys_id' => $client['sys_id'],
            'client_name' => $client['title']
        ];
    }
    
    return [
        'client_sys_id' => '',
        'client_name' => $clientTitle
    ];
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Debug: Log incoming POST data
        error_log('POST data: ' . print_r($_POST, true));
        
        // Start transaction
        $pdo->beginTransaction();

        // 1️⃣ Collect basic invoice data
        $date            = sanitize($_POST['date'] ?? '');
        $client_title    = sanitize($_POST['client_title'] ?? '');
        $client_phone_no = sanitize($_POST['client_phone_no'] ?? '');
        $client_cc       = sanitize($_POST['client_cc'] ?? '');

        $total_amount = (float) ($_POST['total_amount'] ?? 0);
        $paid_amount  = (float) ($_POST['paid_amount'] ?? 0);
        $due_amount   = (float) ($_POST['due_amount'] ?? 0);
        
        // Check required fields
        if (empty($date) || empty($client_title)) {
            throw new Exception("Date and Client Title are required");
        }

        // 2️⃣ Generate invoice number and get client info
        $uuid = generateIDs('invoices');
        $invoice_no = $uuid['sys_id'];
        
        // Get client information from database
        $clientInfo = getClientInfo($pdo, $client_title);
        $client_sys_id = $clientInfo['client_sys_id'];
        $client_name = $clientInfo['client_name'];

        // 3️⃣ Client info JSON
        $client_info = json_encode([
            'title'    => $client_title,
            'phone_no' => $client_phone_no,
            'cc'       => $client_cc
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        // 4️⃣ Work items JSON
        $work_items = [];
        if (!empty($_POST['work_title']) && is_array($_POST['work_title'])) {
            $work_titles = $_POST['work_title'];
            $work_qtys = $_POST['work_qty'] ?? [];
            $work_rates = $_POST['work_rate'] ?? [];
            $work_particulars = $_POST['work_particular'] ?? [];
            $amounts = $_POST['amount'] ?? [];
            
            $itemCount = count($work_titles);
            for ($i = 0; $i < $itemCount; $i++) {
                $work_items[] = [
                    'title'      => sanitize($work_titles[$i] ?? ''),
                    'qty'        => (int) ($work_qtys[$i] ?? 1),
                    'rate'       => (float) ($work_rates[$i] ?? 0),
                    'particular' => sanitize($work_particulars[$i] ?? ''),
                    'amount'     => (float) ($amounts[$i] ?? 0),
                ];
            }
        }
        $work_items_json = json_encode($work_items, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        // 5️⃣ Vendor payment methods JSON
        $vendor_payment_methods = [
            'banks' => [],
            'mfs'   => []
        ];

        // Handle bank data
        if (!empty($_POST['bank'])) {
            $bankData = $_POST['bank'];
            
            // Check if it's a JSON string
            if (is_string($bankData)) {
                $bankData = json_decode($bankData, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log('Bank JSON decode error: ' . json_last_error_msg());
                }
            }
            
            if (is_array($bankData)) {
                foreach ($bankData as $bank) {
                    if (is_array($bank) && !empty(array_filter($bank))) {
                        $vendor_payment_methods['banks'][] = [
                            'title'      => sanitize($bank['vendor_bank'] ?? ''),
                            'account_no' => sanitize($bank['vendor_bank_account'] ?? ''),
                            'branch'     => sanitize($bank['vendor_bank_branch'] ?? ''),
                            'routing_no' => sanitize($bank['vendor_bank_routing'] ?? '')
                        ];
                    }
                }
            }
        }

        // Handle MFS data
        if (!empty($_POST['mfs'])) {
            $mfsData = $_POST['mfs'];
            
            // Check if it's a JSON string
            if (is_string($mfsData)) {
                $mfsData = json_decode($mfsData, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log('MFS JSON decode error: ' . json_last_error_msg());
                }
            }
            
            if (is_array($mfsData)) {
                foreach ($mfsData as $mfs) {
                    if (is_array($mfs) && !empty(array_filter($mfs))) {
                        $accounts = [];
                        if (!empty($mfs['vendor_mfs_account'])) {
                            $accData = $mfs['vendor_mfs_account'];
                            if (is_array($accData)) {
                                foreach ($accData as $acc) {
                                    if (!empty(trim($acc))) {
                                        $accounts[] = sanitize($acc);
                                    }
                                }
                            } elseif (!empty(trim($accData))) {
                                $accounts[] = sanitize($accData);
                            }
                        }

                        $vendor_payment_methods['mfs'][] = [
                            'title'       => sanitize($mfs['vendor_mfs_title'] ?? ''),
                            'mfs_type'    => sanitize($mfs['vendor_mfs_type'] ?? ''),
                            'mfs_account' => $accounts,
                            'note'        => sanitize($mfs['vendor_amount_note'] ?? '')
                        ];
                    }
                }
            }
        }

        $vendor_payment_methods_json = json_encode($vendor_payment_methods, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        // 6️⃣ Amount in words
        $total_amount_in_words = numberToWords($total_amount);

        // 7️⃣ Generate meta data
        $meta_data = generate_meta_data();

        // 8️⃣ Insert invoice with all required columns
        $stmt = $pdo->prepare("
            INSERT INTO invoices (
                uuid, sys_id, date, 
                client_sys_id, client_name, client_info,
                total_amount, paid_amount, due_amount, total_amount_in_words,
                work_items, vendor_payment_methods, meta_data,
                created_at, updated_at
            ) VALUES (
                :uuid, :sys_id, :date,
                :client_sys_id, :client_name, :client_info,
                :total_amount, :paid_amount, :due_amount, :words,
                :work_items, :vendor_methods, :meta_data,
                NOW(), NOW()
            )
        ");

        $stmt->execute([
            ':uuid'                 => $uuid['uuid'],
            ':sys_id'               => $invoice_no,
            ':date'                 => $date,
            ':client_sys_id'        => $client_sys_id,
            ':client_name'          => $client_name,
            ':client_info'          => $client_info,
            ':total_amount'         => $total_amount,
            ':paid_amount'          => $paid_amount,
            ':due_amount'           => $due_amount,
            ':words'                => $total_amount_in_words,
            ':work_items'           => $work_items_json,
            ':vendor_methods'       => $vendor_payment_methods_json,
            ':meta_data'            => $meta_data
        ]);

        $invoice_id = $pdo->lastInsertId();

        // 9️⃣ Update vendor info if needed (optional)
        $vendorJsonPath = __DIR__ . '/../../vendor.json';
        if (file_exists($vendorJsonPath)) {
            $vendorJson = file_get_contents($vendorJsonPath);
            if ($vendorJson) {
                $vendorData = json_decode($vendorJson, true);

                if ($vendorData) {
                    $check = $pdo->prepare(
                        "SELECT id FROM vendor_info WHERE company_name = ? LIMIT 1"
                    );
                    $check->execute([$vendorData['company_name']]);

                    if (!$check->fetch()) {
                        $insertVendor = $pdo->prepare("
                            INSERT INTO vendor_info
                            (company_name, logo, phone, email, address, created_at)
                            VALUES (?, ?, ?, ?, ?, NOW())
                        ");

                        $addressJson = isset($vendorData['address']) ? 
                            json_encode($vendorData['address'], JSON_UNESCAPED_UNICODE) : '{}';
                        
                        $insertVendor->execute([
                            $vendorData['company_name'] ?? '',
                            $vendorData['logo'] ?? '',
                            $vendorData['phone'] ?? '',
                            $vendorData['email'] ?? '',
                            $addressJson
                        ]);
                    }
                }
            }
        }

        // 🔟 Commit transaction
        $pdo->commit();

        // Log successful insertion
        error_log("Invoice created successfully: ID $invoice_id, Invoice No: $invoice_no");

        echo json_encode([
            'success'    => true,
            'message'    => 'Invoice saved successfully!',
            'invoice_id' => (int)$invoice_id,
            'invoice_no' => $invoice_no,
            'data' => [
                'client_info'    => json_decode($client_info, true),
                'client_sys_id'  => $client_sys_id,
                'client_name'    => $client_name,
                'total_amount'   => $total_amount,
                'total_in_words' => $total_amount_in_words,
                'work_items'     => json_decode($work_items_json, true),
                'vendor_methods' => json_decode($vendor_payment_methods_json, true)
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log('Invoice Store Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        error_log('Stack Trace: ' . $e->getTraceAsString());

        echo json_encode([
            'success' => false,
            'message' => 'Error saving invoice: ' . $e->getMessage(),
            'error_details' => [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method. Expected POST.'
    ], JSON_PRETTY_PRINT);
}
?>