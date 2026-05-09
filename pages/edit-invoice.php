<?php
include_once('./authenticate.php');
// Get IP path
$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898";
}
$base_ip_path = trim($ip_port, "/");

$invoice_id = $_GET['invoice'];

$getInvoice = $ip_port . "api/invoices/edit-invoice.php?invoice=$invoice_id";
$updateInvoiceApi = $ip_port . "api/invoices/update.php?invoice=$invoice_id";
$vendor_json_path = './../server/invoice-vendor.json';

// Fetch invoice data from API
$ch = curl_init($getInvoice);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code == 200 && !empty($response)) {
    $data = json_decode($response, true);
    
    // Check if JSON decode was successful
    if (json_last_error() === JSON_ERROR_NONE && isset($data['success']) && $data['success'] === true) {
        // Extract data from response
        $invoice = $data['data']['invoice'] ?? [];
        $client_info = $data['data']['client_info'] ?? [];
        $work_items = $data['data']['work_items'] ?? [];
        $vendor_data = $data['data']['vendor_data'] ?? [];
        $vendor_payment_methods = $data['data']['vendor_payment_methods'] ?? [];
        
        // শুধুমাত্র API থেকে ডাটা না পেলে JSON ফাইল থেকে নেবে
        if (empty($vendor_payment_methods) && file_exists($vendor_json_path)) {
            $vendor_json = file_get_contents($vendor_json_path);
            $vendor_payment_methods = json_decode($vendor_json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $vendor_payment_methods = ['banks' => [], 'mfs' => []];
            }
        }
        
        // ডিফল্ট ভ্যালু সেট করুন
        $vendor_payment_methods = array_merge(
            ['banks' => [], 'mfs' => []],
            $vendor_payment_methods
        );
        
        // Set invoice number properly
        $invoice_no = is_array($invoice['invoice_no']) ? 
                     (isset($invoice['invoice_no']['invoice_no']) ? $invoice['invoice_no']['invoice_no'] : 'N/A') : 
                     $invoice['invoice_no'];
        $banks = $vendor_payment_methods['banks'] ?? [];
    } else {
        die("Failed to decode API response. JSON error: " . json_last_error_msg());
    }
} else {
    ?>
    <!DOCTYPE html>
    <html lang="bn">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Error - Invoice Not Found</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            .error-container {
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            }
            .error-card {
                background: white;
                padding: 40px;
                border-radius: 12px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                text-align: center;
                max-width: 500px;
                width: 90%;
            }
            .error-icon {
                font-size: 64px;
                color: #f59e0b;
                margin-bottom: 20px;
            }
            .error-title {
                color: #064e3b;
                font-size: 24px;
                margin-bottom: 15px;
            }
            .error-message {
                color: #6b7280;
                margin-bottom: 25px;
                line-height: 1.6;
            }
            .error-actions {
                display: flex;
                gap: 15px;
                justify-content: center;
            }
            .btn {
                padding: 12px 24px;
                border-radius: 8px;
                text-decoration: none;
                font-weight: 600;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                transition: all 0.3s ease;
            }
            .btn-primary {
                background: #10b981;
                color: white;
            }
            .btn-primary:hover {
                background: #059669;
            }
            .btn-outline {
                background: transparent;
                color: #10b981;
                border: 2px solid #10b981;
            }
            .btn-outline:hover {
                background: #10b981;
                color: white;
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-card">
                <div class="error-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h1 class="error-title">Invoice Not Found</h1>
                <p class="error-message">
                    <?php
                    if ($http_code == 404) {
                        echo "The invoice you're looking for doesn't exist or has been deleted.";
                    } elseif ($http_code == 500) {
                        echo "Server error occurred. Please try again later.";
                    } elseif ($http_code == 0) {
                        echo "Unable to connect to the server. Please check your internet connection.";
                    } else {
                        echo "Sorry, we couldn't load the invoice data. Error Code: $http_code";
                    }
                    ?>
                </p>
                <div class="error-actions">
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-home"></i> Back to Home
                    </a>
                    <a href="javascript:history.back()" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Go Back
                    </a>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
        exit();
    }
?>

<!DOCTYPE html>
<html lang="bn">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accounting - Edit Invoice</title>
    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png" sizes="16x16">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root {
            --primary: #10b981;
            --secondary: #059669;
            --success: #34d399;
            --danger: #f87171;
            --warning: #fbbf24;
            --light: #f0fdf4;
            --dark: #064e3b;
            --gray: #6b7280;
            --border: #d1fae5;
            --shadow: 0 4px 12px rgba(16, 185, 129, 0.1);
            --radius: 8px;
            --transition: all 0.3s ease;
        }

        .container {
            margin: 70px 0px;
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            border: 1px solid var(--border);
        }

        .header {
            background: linear-gradient(to right, #f59e0b, #fbbf24);
            color: white;
            padding: 25px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 28px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header h1 i {
            font-size: 32px;
        }

        .header-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 14px;
            backdrop-filter: blur(10px);
        }

        .form-container {
            padding: 30px;
        }

        .section-header {
            background: linear-gradient(to right, var(--light), #dcfce7);
            padding: 15px 20px;
            margin: 30px 0 20px;
            border-left: 5px solid var(--primary);
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-weight: 600;
            color: var(--dark);
            font-size: 16px;
        }

        .section-header i {
            font-size: 18px;
            color: var(--primary);
        }

        .edit-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 6px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: var(--transition);
        }

        .edit-btn:hover {
            background: var(--secondary);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
            font-size: 14px;
        }

        .form-label i {
            margin-right: 8px;
            color: var(--primary);
            width: 20px;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border);
            border-radius: var(--radius);
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            transition: var(--transition);
            background: white;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .invoice-no-display {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            padding: 20px;
            border-radius: var(--radius);
            border: 2px solid #f59e0b;
            margin-bottom: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(245, 158, 11, 0.1);
        }

        .invoice-no-label {
            font-size: 13px;
            color: var(--gray);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .invoice-no-value {
            font-family: 'Courier New', monospace;
            font-size: 28px;
            font-weight: 700;
            color: #d97706;
            letter-spacing: 1.5px;
            margin: 10px 0;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
        }

        .readonly-field {
            background: #f0fdf4;
            padding: 15px;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            margin-bottom: 15px;
        }

        .readonly-label {
            font-size: 12px;
            color: var(--gray);
            margin-bottom: 5px;
        }

        .readonly-value {
            font-weight: 600;
            font-size: 15px;
            color: var(--dark);
        }

        .bank-mfs-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }

        .bank-card,
        .mfs-card {
            background: var(--light);
            border-radius: var(--radius);
            padding: 20px;
            border-left: 4px solid var(--primary);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .mfs-card {
            border-left-color: var(--success);
        }

        .item-card {
            background: white;
            border-radius: var(--radius);
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid var(--success);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: var(--transition);
        }

        .item-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .amount-display {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary);
            text-align: right;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 2px dashed var(--border);
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: var(--radius);
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn i {
            font-size: 16px;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--secondary);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(16, 185, 129, 0.3);
        }

        .btn-warning {
            background: #f59e0b;
            color: white;
        }

        .btn-warning:hover {
            background: #d97706;
            transform: translateY(-2px);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #10b981;
            transform: translateY(-2px);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #ef4444;
        }

        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }

        .btn-small {
            padding: 8px 16px;
            font-size: 13px;
        }

        .total-section {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            padding: 25px;
            border-radius: var(--radius);
            margin: 30px 0;
        }

        .total-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .total-item {
            text-align: center;
            padding: 20px;
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .total-label {
            font-size: 14px;
            color: var(--gray);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .total-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary);
        }

        .total-value.due {
            color: var(--danger);
        }

        .total-value.paid {
            color: var(--success);
        }

        .footer-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 40px;
            padding-top: 25px;
            border-top: 2px solid var(--border);
        }

        .info-note {
            background: #fef3c7;
            color: #92400e;
            padding: 15px;
            border-radius: var(--radius);
            margin-top: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-left: 4px solid #f59e0b;
        }

        .info-note i {
            font-size: 18px;
            color: #f59e0b;
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            padding: 20px;
        }

        .modal {
            background: white;
            border-radius: var(--radius);
            width: 100%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            padding: 20px;
            border-radius: var(--radius) var(--radius) 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-close {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            transition: var(--transition);
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .modal-body {
            padding: 25px;
        }

        .modal-footer {
            padding: 20px 25px;
            background: #f0fdf4;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            border-radius: 0 0 var(--radius) var(--radius);
        }

        .mfs-account-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .remove-account {
            background: var(--danger);
            color: white;
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .add-account {
            margin-top: 10px;
        }

        @media (max-width: 768px) {

            .form-grid,
            .total-grid,
            .bank-mfs-container {
                grid-template-columns: 1fr;
            }

            .footer-actions {
                flex-direction: column;
                gap: 15px;
            }

            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .invoice-no-value {
                font-size: 22px;
            }

            .modal {
                max-height: 95vh;
            }
        }
    </style>
</head>

<body class="bg-gray-50 font-sans">
    <!-- Top Navigation -->
    <?php include '../elements/header.php'; ?>

    <!-- Sidebar -->
    <?php include '../elements/aside.php'; ?>

    <!-- Main Content -->
    <main id="mainContent" class="pt-16 pl-0 lg:pl-64 transition-all duration-300 h-full">
        <div class="p-4 md:p-6 h-full">
            <div class="container w-full">
                <div class="header">
                    <h1><i class="fas fa-edit"></i> Edit Invoice</h1>
                    <div class="header-badge">
                        <i class="fas fa-exclamation-triangle"></i> Editing Mode
                    </div>
                </div>
        
                <div class="form-container">
                    <form id="invoiceForm" method="POST" action="server/invoice-update.php" enctype="multipart/form-data">
                        <!-- Hidden invoice ID -->
                        <input type="hidden" name="invoice_id" value="<?php echo $invoice_id; ?>">
                        <input type="hidden" name="invoice_no" value="<?php echo htmlspecialchars($invoice_no); ?>">
        
                        <!-- Invoice Number Display -->
                        <div class="invoice-no-display">
                            <div class="invoice-no-label">Invoice Number</div>
        
                            <div class="invoice-no-value"><?php echo htmlspecialchars($invoice_no); ?></div>
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-calendar-alt"></i> Invoice Date</label>
                                <input type="date" name="date" class="form-control" 
                                       value="<?php echo htmlspecialchars($invoice['date'] ?? date('Y-m-d')); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-history"></i> Created Date: 
                                    <?php echo htmlspecialchars($invoice['created_at'] ?? 'N/A'); ?>
                                </label>
                            </div>
                        </div>
        
                        <!-- Client Information -->
                        <div class="section-header">
                            <i class="fas fa-user-tie"></i> Client Information
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-user"></i> Client Name</label>
                                <div class="readonly-field">
                                    <div class="readonly-label">Client Information (System Generated)</div>
                                    <div class="readonly-value">
                                        <?php 
                                        $client_name_display = $invoice['client_name'] ?? $client_info['title'] ?? 'N/A';
                                        echo htmlspecialchars($client_name_display);
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-phone"></i> Phone Number</label>
                                <input type="text" name="client_phone_no" class="form-control"
                                    value="<?php echo htmlspecialchars($client_info['phone_no'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-envelope"></i> Email / CC</label>
                                <input type="text" name="client_cc" class="form-control"
                                    value="<?php echo htmlspecialchars($client_info['cc'] ?? ''); ?>">
                            </div>
                        </div>
        
                        <!-- Work Items -->
                        <div class="section-header">
                            <i class="fas fa-tasks"></i> Work Items
                        </div>
                        <div id="work_items">
                            <?php 
                            $work_item_count = 0;
                            if (!empty($work_items) && is_array($work_items)):
                                foreach ($work_items as $index => $item):
                                    $work_item_count++;
                            ?>
                                    <div class="item-card" data-index="<?php echo $index; ?>">
                                        <input type="hidden" name="work_items[<?php echo $index; ?>][id]" 
                                               value="<?php echo htmlspecialchars($item['id'] ?? ''); ?>">
                                        <input type="hidden" name="work_items[<?php echo $index; ?>][action]" value="update">
                                        <div class="form-grid">
                                            <div class="form-group">
                                                <label class="form-label"><i class="fas fa-heading"></i> Title</label>
                                                <input type="text" name="work_items[<?php echo $index; ?>][title]" class="form-control"
                                                    value="<?php echo htmlspecialchars($item['title'] ?? ''); ?>" placeholder="Work title">
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label"><i class="fas fa-box"></i> Quantity</label>
                                                <input type="number" name="work_items[<?php echo $index; ?>][qty]" class="form-control"
                                                    value="<?php echo htmlspecialchars($item['qty'] ?? 1); ?>" min="1" oninput="calcAmount(this)" placeholder="0">
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label"><i class="fas fa-tag"></i> Rate (per unit)</label>
                                                <input type="number" name="work_items[<?php echo $index; ?>][rate]" class="form-control"
                                                    value="<?php echo htmlspecialchars($item['rate'] ?? 0); ?>" oninput="calcAmount(this)" placeholder="0.00">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label"><i class="fas fa-align-left"></i> Details</label>
                                            <textarea name="work_items[<?php echo $index; ?>][particular]" class="form-control" placeholder="Work details..."><?php echo htmlspecialchars($item['particular'] ?? ''); ?></textarea>
                                        </div>
                                        <div class="amount-display">
                                            <span>Total: ৳ <span class="amount_text">
                                                <?php
                                                $item_qty = floatval($item['qty'] ?? 0);
                                                $item_rate = floatval($item['rate'] ?? 0);
                                                echo number_format($item_qty * $item_rate, 2);
                                                ?>
                                            </span></span>
                                            <input type="hidden" name="work_items[<?php echo $index; ?>][amount]" value="<?php echo $item_qty * $item_rate; ?>">
                                            <button type="button" class="btn btn-danger btn-small" onclick="removeWorkItem(this, <?php echo $index; ?>)">
                                                <i class="fas fa-trash"></i> Remove
                                            </button>
                                        </div>
                                    </div>
                            <?php 
                                endforeach;
                            else: 
                                $work_item_count = 0;
                            ?>
                                <!-- শুধুমাত্র যখন কোন item নেই তখন default item দেখাবে -->
                                <div class="item-card" data-index="0">
                                    <input type="hidden" name="work_items[0][id]" value="">
                                    <input type="hidden" name="work_items[0][action]" value="new">
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label class="form-label"><i class="fas fa-heading"></i> Title</label>
                                            <input type="text" name="work_items[0][title]" class="form-control" placeholder="Work title">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label"><i class="fas fa-box"></i> Quantity</label>
                                            <input type="number" name="work_items[0][qty]" class="form-control" value="1" min="1" oninput="calcAmount(this)" placeholder="0">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label"><i class="fas fa-tag"></i> Rate (per unit)</label>
                                            <input type="number" name="work_items[0][rate]" class="form-control" value="0" oninput="calcAmount(this)" placeholder="0.00">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label"><i class="fas fa-align-left"></i> Details</label>
                                        <textarea name="work_items[0][particular]" class="form-control" placeholder="Work details..."></textarea>
                                    </div>
                                    <div class="amount-display">
                                        <span>Total: ৳ <span class="amount_text">0.00</span></span>
                                        <input type="hidden" name="work_items[0][amount]" value="0">
                                        <button type="button" class="btn btn-danger btn-small" onclick="removeWorkItem(this, 0)">
                                            <i class="fas fa-trash"></i> Remove
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
        
                        <button type="button" class="btn btn-outline" onclick="addNewWorkItem()">
                            <i class="fas fa-plus-circle"></i> Add Work Item
                        </button>
        
                        <!-- Total Calculation -->
                        <div class="section-header">
                            <i class="fas fa-calculator"></i> Total Calculation
                        </div>
                        <div class="total-section">
                            <div class="total-grid">
                                <div class="total-item">
                                    <div class="total-label"><i class="fas fa-receipt"></i> Total Amount</div>
                                    <div class="total-value" id="total_amount_display">
                                        ৳ <?php echo number_format($invoice['total_amount'] ?? 0, 2); ?>
                                    </div>
                                    <input type="hidden" name="total_amount" id="total_amount" value="<?php echo $invoice['total_amount'] ?? 0; ?>">
                                </div>
                                <div class="total-item">
                                    <div class="total-label"><i class="fas fa-money-bill-wave"></i> Paid Amount</div>
                                    <input type="number" name="paid_amount" id="paid_amount" class="form-control"
                                        value="<?php echo $invoice['paid_amount'] ?? 0; ?>" min="0" oninput="calculateDue()"
                                        style="text-align: center; font-size: 18px; font-weight: bold;">
                                </div>
                                <div class="total-item">
                                    <div class="total-label"><i class="fas fa-clock"></i> Due Amount</div>
                                    <div class="total-value due" id="due_amount_display">
                                        ৳ <?php echo number_format($invoice['due_amount'] ?? 0, 2); ?>
                                    </div>
                                    <input type="hidden" name="due_amount" id="due_amount" value="<?php echo $invoice['due_amount'] ?? 0; ?>">
                                </div>
                            </div>
                        </div>
        
                        <!-- Vendor Information (Readonly from JSON) -->
                        <?php if (!empty($vendor_data)): ?>
                        <div class="section-header">
                            <div><i class="fas fa-building"></i> Vendor Information (From JSON)</div>
                        </div>
                        <div class="readonly-field">
                            <div class="form-grid">
                                <?php if (isset($vendor_data['company_name'])): ?>
                                <div>
                                    <div class="readonly-label">Company Name</div>
                                    <div class="readonly-value" id="vendor_company_name">
                                        <?php echo htmlspecialchars($vendor_data['company_name']); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <?php if (isset($vendor_data['phone'])): ?>
                                <div>
                                    <div class="readonly-label">Phone</div>
                                    <div class="readonly-value" id="vendor_phone">
                                        <?php echo htmlspecialchars($vendor_data['phone']); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <?php if (isset($vendor_data['email'])): ?>
                                <div>
                                    <div class="readonly-label">Email</div>
                                    <div class="readonly-value" id="vendor_email">
                                        <?php echo htmlspecialchars($vendor_data['email']); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php if (isset($vendor_data['address'])): ?>
                            <div style="margin-top: 15px;">
                                <div class="readonly-label">Address</div>
                                <div class="readonly-value" id="vendor_address">
                                    <?php
                                    $address = $vendor_data['address'] ?? [];
                                    echo htmlspecialchars(
                                        ($address['line1'] ?? '') . ', ' .
                                        ($address['line2'] ?? '') . ', ' .
                                        ($address['city'] ?? '') . '-' .
                                        ($address['postcode'] ?? '') . ', ' .
                                        ($address['country'] ?? '')
                                    );
                                    ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
        
                        <!-- Bank/MFS Information (Editable) -->
                        <div class="section-header">
                            <div><i class="fas fa-university"></i> Bank / MFS Information</div>
                            <button type="button" class="edit-btn" onclick="openBankMfsModal()">
                                <i class="fas fa-edit" style="color:white"></i> Edit
                            </button>
                        </div>
                        <div id="bank_mfs_display">
                            <div class="bank-mfs-container">
                                <?php
                                // Display bank information
                                if (!empty($banks) && is_array($banks)):
                                    foreach ($banks as $index => $bank):
                                ?>
                                        <div class="bank-card">
                                            <h4 style="margin-bottom: 15px; color: var(--primary);">
                                                <i class="fas fa-university"></i> Bank <?php echo $index + 1; ?>
                                            </h4>
                                            <div class="form-grid">
                                                <div>
                                                    <div class="readonly-label">Bank Name</div>
                                                    <div class="readonly-value"><?php echo htmlspecialchars($bank['title'] ?? 'N/A'); ?></div>
                                                </div>
                                                <div>
                                                    <div class="readonly-label">Account Number</div>
                                                    <div class="readonly-value"><?php echo htmlspecialchars($bank['account_no'] ?? 'N/A'); ?></div>
                                                </div>
                                            </div>
                                            <div class="form-grid">
                                                <div>
                                                    <div class="readonly-label">Branch</div>
                                                    <div class="readonly-value"><?php echo htmlspecialchars($bank['branch'] ?? 'N/A'); ?></div>
                                                </div>
                                                <div>
                                                    <div class="readonly-label">Routing Number</div>
                                                    <div class="readonly-value"><?php echo htmlspecialchars($bank['routing_no'] ?? 'N/A'); ?></div>
                                                </div>
                                            </div>
                                        </div>
                                <?php endforeach;
                                else: ?>
                                    <div class="info-note">
                                        <i class="fas fa-info-circle"></i>
                                        No bank information available. Click Edit to add.
                                    </div>
                                <?php endif; ?>
        
                                <?php
                                // Display MFS information
                                $mfs_list = $vendor_payment_methods['mfs'] ?? [];
                                if (!empty($mfs_list) && is_array($mfs_list)):
                                    foreach ($mfs_list as $index => $mfs):
                                ?>
                                        <div class="mfs-card">
                                            <h4 style="margin-bottom: 15px; color: var(--success);">
                                                <i class="fas fa-mobile-alt"></i> <?php echo htmlspecialchars($mfs['title'] ?? 'MFS'); ?> <?php echo $index + 1; ?>
                                            </h4>
                                            <div class="form-grid">
                                                <div>
                                                    <div class="readonly-label">Service</div>
                                                    <div class="readonly-value"><?php echo htmlspecialchars($mfs['title'] ?? 'N/A'); ?></div>
                                                </div>
                                                <div>
                                                    <div class="readonly-label">Type</div>
                                                    <div class="readonly-value"><?php echo htmlspecialchars($mfs['mfs_type'] ?? 'N/A'); ?></div>
                                                </div>
                                            </div>
                                            <div style="margin-top: 15px;">
                                                <div class="readonly-label">Account Number</div>
                                                <?php
                                                $accounts = $mfs['mfs_account'] ?? [];
                                                if (is_array($accounts) && !empty($accounts)):
                                                    foreach ($accounts as $account): ?>
                                                        <div class="readonly-value"><?php echo htmlspecialchars($account); ?></div>
                                                    <?php endforeach;
                                                else: ?>
                                                    <div class="readonly-value"><?php echo htmlspecialchars($mfs['mfs_account'] ?? 'N/A'); ?></div>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($mfs['note'])): ?>
                                                <div style="margin-top: 15px;">
                                                    <div class="readonly-label">Special Note</div>
                                                    <div class="readonly-value"><?php echo htmlspecialchars($mfs['note']); ?></div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                <?php endforeach;
                                else: ?>
                                    <div class="info-note">
                                        <i class="fas fa-info-circle"></i>
                                        No MFS information available. Click Edit to add.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
        
                        <!-- Information Note -->
                        <div class="info-note">
                            <i class="fas fa-exclamation-circle"></i>
                            <div>
                                <strong>Note:</strong> You are editing an existing invoice. Changes will update the invoice directly.
                                Bank/MFS information can be edited using the Edit button.
                            </div>
                        </div>
        
                        <!-- Form Actions -->
                        <div class="footer-actions">
                            <a href="index.php" class="btn btn-danger">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <div>
                                <a href="print-invoice.php?id=<?php echo $invoice_id; ?>" class="btn btn-outline" target="_blank">
                                    <i class="fas fa-eye"></i> View Invoice
                                </a>
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-save"></i> Update Invoice
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    
        <!-- Bank/MFS Edit Modal -->
        <div id="bankMfsModal" class="modal-overlay" style="display: none;">
            <div class="modal">
                <div class="modal-header">
                    <h3><i class="fas fa-edit"></i> Edit Bank / MFS Information</h3>
                    <button type="button" class="modal-close" onclick="closeBankMfsModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Bank Information -->
                    <div class="section-header" style="margin-top: 0;">
                        <div><i class="fas fa-university"></i> Bank Information</div>
                        <button type="button" class="edit-btn btn-small" onclick="addBankField()">
                            <i class="fas fa-plus"></i> Add Bank
                        </button>
                    </div>
                    <div id="bank_fields">
                        <?php
                        if (!empty($banks) && is_array($banks)):
                            foreach ($banks as $index => $bank):
                        ?>
                                <div class="item-card">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                        <h4 style="color: var(--primary);">
                                            <i class="fas fa-university"></i> Bank <?php echo $index + 1; ?>
                                        </h4>
                                        <button type="button" class="btn btn-danger btn-small" onclick="removeBankField(this)">
                                            <i class="fas fa-trash"></i> Remove
                                        </button>
                                    </div>
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label class="form-label">Bank Name</label>
                                            <input type="text" class="form-control bank-field" data-field="title"
                                                value="<?php echo htmlspecialchars($bank['title'] ?? ''); ?>" placeholder="DBBL, BRAC Bank, etc.">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Account Number</label>
                                            <input type="text" class="form-control bank-field" data-field="account_no"
                                                value="<?php echo htmlspecialchars($bank['account_no'] ?? ''); ?>" placeholder="2021100019475">
                                        </div>
                                    </div>
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label class="form-label">Branch</label>
                                            <input type="text" class="form-control bank-field" data-field="branch"
                                                value="<?php echo htmlspecialchars($bank['branch'] ?? ''); ?>" placeholder="Ashkona (Dhaka North)">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Routing Number</label>
                                            <input type="text" class="form-control bank-field" data-field="routing_no"
                                                value="<?php echo htmlspecialchars($bank['routing_no'] ?? ''); ?>" placeholder="090260205">
                                        </div>
                                    </div>
                                </div>
                        <?php endforeach;
                        endif; ?>
                    </div>

                    <!-- MFS Information -->
                    <div class="section-header">
                        <div><i class="fas fa-mobile-alt"></i> MFS Information</div>
                        <button type="button" class="edit-btn btn-small" onclick="addMfsField()">
                            <i class="fas fa-plus"></i> Add MFS
                        </button>
                    </div>
                    <div id="mfs_fields">
                        <?php
                        $mfs_list = $vendor_payment_methods['mfs'] ?? [];
                        if (!empty($mfs_list) && is_array($mfs_list)):
                            foreach ($mfs_list as $index => $mfs):
                        ?>
                                <div class="item-card">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                        <h4 style="color: var(--success);">
                                            <i class="fas fa-mobile-alt"></i> MFS <?php echo $index + 1; ?>
                                        </h4>
                                        <button type="button" class="btn btn-danger btn-small" onclick="removeMfsField(this)">
                                            <i class="fas fa-trash"></i> Remove
                                        </button>
                                    </div>
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label class="form-label">Service Name</label>
                                            <input type="text" class="form-control mfs-field" data-field="title"
                                                value="<?php echo htmlspecialchars($mfs['title'] ?? ''); ?>" placeholder="bkash, Nagad, Rocket">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Type</label>
                                            <input type="text" class="form-control mfs-field" data-field="mfs_type"
                                                value="<?php echo htmlspecialchars($mfs['mfs_type'] ?? ''); ?>" placeholder="Personal, Merchant">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Account Number</label>
                                        <?php
                                        $accounts = $mfs['mfs_account'] ?? [];
                                        if (is_array($accounts)):
                                            foreach ($accounts as $accIndex => $account): ?>
                                                <div class="mfs-account-item">
                                                    <input type="text" class="form-control mfs-account-field"
                                                        value="<?php echo htmlspecialchars($account); ?>" placeholder="01XXXXXXXXX">
                                                    <?php if ($accIndex > 0): ?>
                                                        <button type="button" class="remove-account" onclick="removeMfsAccount(this)">
                                                            <i class="fas fa-minus"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach;
                                        else: ?>
                                            <div class="mfs-account-item">
                                                <input type="text" class="form-control mfs-account-field"
                                                    value="<?php echo htmlspecialchars($mfs['mfs_account'] ?? ''); ?>" placeholder="01XXXXXXXXX">
                                            </div>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-outline btn-small add-account" onclick="addMfsAccount(this)">
                                            <i class="fas fa-plus"></i> Add Another Number
                                        </button>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Special Note</label>
                                        <input type="text" class="form-control mfs-field" data-field="note"
                                            value="<?php echo htmlspecialchars($mfs['note'] ?? ''); ?>" placeholder="Special instructions...">
                                    </div>
                                </div>
                        <?php endforeach;
                        endif; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeBankMfsModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-primary" onclick="saveBankMfsData()">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </div>
        </div>    
    </main>
    
    <?php include '../elements/floating-menus.php'; ?>

    <script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>

    <script>
        const BANK_MFS_KEY = 'bank_mfs_data_edit_<?php echo $invoice_id; ?>';
        const GET_INVOICE = '<?php echo $getInvoice; ?>';
        const UPDATE_INVOICE_API = '<?php echo $base_ip_path . "/api/invoices/update.php?invoice=" . $invoice_id; ?>';

        let bankMfsData = {
            banks: <?php echo json_encode($vendor_payment_methods['banks'] ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
            mfs: <?php echo json_encode($vendor_payment_methods['mfs'] ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>
        };

        let workItemCounter = <?php echo $work_item_count; ?>;
        let deletedItems = [];

        /* ---------------- Work Items Functions ---------------- */
        function addNewWorkItem() {
            workItemCounter++;
            const workItemsDiv = document.getElementById('work_items');
            
            const div = document.createElement('div');
            div.className = 'item-card';
            div.dataset.index = workItemCounter;
            
            div.innerHTML = `
                <input type="hidden" name="work_items[${workItemCounter}][id]" value="">
                <input type="hidden" name="work_items[${workItemCounter}][action]" value="new">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-heading"></i> Title</label>
                        <input type="text" name="work_items[${workItemCounter}][title]" class="form-control" placeholder="Work title">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-box"></i> Quantity</label>
                        <input type="number" name="work_items[${workItemCounter}][qty]" class="form-control" value="1" min="1" oninput="calcAmount(this)" placeholder="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-tag"></i> Rate (per unit)</label>
                        <input type="number" name="work_items[${workItemCounter}][rate]" class="form-control" value="0" oninput="calcAmount(this)" placeholder="0.00">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-align-left"></i> Details</label>
                    <textarea name="work_items[${workItemCounter}][particular]" class="form-control" placeholder="Work details..."></textarea>
                </div>
                <div class="amount-display">
                    <span>Total: ৳ <span class="amount_text">0.00</span></span>
                    <input type="hidden" name="work_items[${workItemCounter}][amount]" value="0">
                    <button type="button" class="btn btn-danger btn-small" onclick="removeWorkItem(this, ${workItemCounter})">
                        <i class="fas fa-trash"></i> Remove
                    </button>
                </div>
            `;
            workItemsDiv.appendChild(div);
        }

        function removeWorkItem(btn, itemIndex) {
            if (confirm('Are you sure you want to remove this work item?')) {
                const card = btn.closest('.item-card');
                const actionInput = card.querySelector('input[name^="work_items["][name$="[action]"]');
                const itemId = card.querySelector('input[name^="work_items["][name$="[id]"]').value;
                
                if (actionInput.value === 'update' && itemId) {
                    // এক্সিস্টিং আইটেম ডিলিট করার জন্য
                    actionInput.value = 'delete';
                    deletedItems.push(itemId);
                    card.style.display = 'none'; // শুধু hide করছি
                } else {
                    // নতুন আইটেম ডিলিট করা
                    card.remove();
                }
                calculateTotal();
            }
        }

        /* ---------------- Calculations ---------------- */
        function calcAmount(el) {
            const box = el.closest('.item-card');
            const qtyInput = box.querySelector('input[name^="work_items["][name$="[qty]"]');
            const rateInput = box.querySelector('input[name^="work_items["][name$="[rate]"]');
            const amountInput = box.querySelector('input[name^="work_items["][name$="[amount]"]');
            
            const qty = parseFloat(qtyInput.value) || 0;
            const rate = parseFloat(rateInput.value) || 0;
            const amount = qty * rate;
            
            box.querySelector('.amount_text').innerText = amount.toFixed(2);
            amountInput.value = amount.toFixed(2);
            calculateTotal();
        }

        function calculateTotal() {
            let total = 0;
            document.querySelectorAll('#work_items .item-card').forEach(card => {
                if (card.style.display !== 'none') {
                    const amountInput = card.querySelector('input[name^="work_items["][name$="[amount]"]');
                    // সরাসরি ভ্যালু যোগ হবে (ভ্যালু মাইনাস থাকলে স্বয়ংক্রিয়ভাবে বিয়োগ হয়ে যাবে)
                    total += parseFloat(amountInput.value) || 0;
                }
            });
        
            document.getElementById('total_amount_display').innerText = '৳ ' + total.toFixed(2);
            document.getElementById('total_amount').value = total.toFixed(2);
            calculateDue();
        }

        function calculateDue() {
            const total = parseFloat(document.getElementById('total_amount').value) || 0;
            const paid = parseFloat(document.getElementById('paid_amount').value) || 0;
            
            // Math.max(0, ...) সরিয়ে দিন
            const due = total - paid; 
        
            document.getElementById('due_amount_display').innerText = '৳ ' + due.toFixed(2);
            document.getElementById('due_amount').value = due.toFixed(2);
            
            // ঐচ্ছিক: ডিউ নেগেটিভ হলে কালার পরিবর্তন (Red এর বদলে Blue)
            const display = document.getElementById('due_amount_display');
            if (due < 0) {
                display.style.color = '#3b82f6'; // Blue color for advance
            } else if (due > 0) {
                display.style.color = 'var(--danger)';
            } else {
                display.style.color = 'var(--primary)';
            }
        }

        /* ---------------- Bank/MFS Modal Functions ---------------- */
        function openBankMfsModal() {
            if (document.getElementById('bank_fields').children.length === 0) {
                populateModalFields(bankMfsData);
            }
            document.getElementById('bankMfsModal').style.display = 'flex';
        }

        function closeBankMfsModal() {
            document.getElementById('bankMfsModal').style.display = 'none';
        }

        function addBankField(bankData = {}, index = null) {
            const fieldsDiv = document.getElementById('bank_fields');
            const bankIndex = index !== null ? index : fieldsDiv.children.length;

            const bankField = document.createElement('div');
            bankField.className = 'item-card';
            bankField.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h4 style="color: var(--primary);">
                        <i class="fas fa-university"></i> Bank ${bankIndex + 1}
                    </h4>
                    <button type="button" class="btn btn-danger btn-small" onclick="removeBankField(this)">
                        <i class="fas fa-trash"></i> Remove
                    </button>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Bank Name</label>
                        <input type="text" class="form-control bank-field" data-field="title" 
                               value="${bankData.title || ''}" placeholder="DBBL, BRAC Bank, etc.">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Account Number</label>
                        <input type="text" class="form-control bank-field" data-field="account_no" 
                               value="${bankData.account_no || ''}" placeholder="2021100019475">
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Branch</label>
                        <input type="text" class="form-control bank-field" data-field="branch" 
                               value="${bankData.branch || ''}" placeholder="Ashkona (Dhaka North)">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Routing Number</label>
                        <input type="text" class="form-control bank-field" data-field="routing_no" 
                               value="${bankData.routing_no || ''}" placeholder="090260205">
                    </div>
                </div>
            `;
            fieldsDiv.appendChild(bankField);
        }

        function addMfsField(mfsData = {}, index = null) {
            const fieldsDiv = document.getElementById('mfs_fields');
            const mfsIndex = index !== null ? index : fieldsDiv.children.length;

            const mfsField = document.createElement('div');
            mfsField.className = 'item-card';
            mfsField.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h4 style="color: var(--success);">
                        <i class="fas fa-mobile-alt"></i> MFS ${mfsIndex + 1}
                    </h4>
                    <button type="button" class="btn btn-danger btn-small" onclick="removeMfsField(this)">
                        <i class="fas fa-trash"></i> Remove
                    </button>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Service Name</label>
                        <input type="text" class="form-control mfs-field" data-field="title" 
                               value="${mfsData.title || ''}" placeholder="bkash, Nagad, Rocket">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Type</label>
                        <input type="text" class="form-control mfs-field" data-field="mfs_type" 
                               value="${mfsData.mfs_type || ''}" placeholder="Personal, Merchant">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Account Number</label>
                    <div class="mfs-account-item">
                        <input type="text" class="form-control mfs-account-field" 
                               value="${mfsData.mfs_account || ''}" placeholder="01XXXXXXXXX">
                    </div>
                    <button type="button" class="btn btn-outline btn-small add-account" onclick="addMfsAccount(this)">
                        <i class="fas fa-plus"></i> Add Another Number
                    </button>
                </div>
                <div class="form-group">
                    <label class="form-label">Special Note</label>
                    <input type="text" class="form-control mfs-field" data-field="note" 
                           value="${mfsData.note || ''}" placeholder="Special instructions...">
                </div>
            `;
            fieldsDiv.appendChild(mfsField);
        }

        function removeBankField(button) {
            if (confirm('Are you sure you want to remove this bank information?')) {
                const card = button.closest('.item-card');
                card.remove();

                // Renumber remaining banks
                const bankFields = document.querySelectorAll('#bank_fields .item-card');
                bankFields.forEach((field, index) => {
                    field.querySelector('h4').innerHTML = `<i class="fas fa-university"></i> Bank ${index + 1}`;
                });
            }
        }

        function removeMfsField(button) {
            if (confirm('Are you sure you want to remove this MFS information?')) {
                const card = button.closest('.item-card');
                card.remove();

                // Renumber remaining MFS
                const mfsFields = document.querySelectorAll('#mfs_fields .item-card');
                mfsFields.forEach((field, index) => {
                    field.querySelector('h4').innerHTML = `<i class="fas fa-mobile-alt"></i> MFS ${index + 1}`;
                });
            }
        }

        function addMfsAccount(button) {
            const container = button.previousElementSibling;
            const newAccount = document.createElement('div');
            newAccount.className = 'mfs-account-item';
            newAccount.innerHTML = `
                <input type="text" class="form-control mfs-account-field" placeholder="01XXXXXXXXX">
                <button type="button" class="remove-account" onclick="removeMfsAccount(this)">
                    <i class="fas fa-minus"></i>
                </button>
            `;
            container.appendChild(newAccount);
        }

        function removeMfsAccount(button) {
            button.closest('.mfs-account-item').remove();
        }

        function saveBankMfsData() {
            // Collect bank data
            const banks = [];
            document.querySelectorAll('#bank_fields .item-card').forEach(card => {
                const bank = {};
                card.querySelectorAll('.bank-field').forEach(input => {
                    bank[input.dataset.field] = input.value;
                });
                if (Object.values(bank).some(value => value.trim() !== '')) {
                    banks.push(bank);
                }
            });

            // Collect MFS data
            const mfs = [];
            document.querySelectorAll('#mfs_fields .item-card').forEach(card => {
                const mfsItem = {};

                // Get regular fields
                card.querySelectorAll('.mfs-field').forEach(input => {
                    mfsItem[input.dataset.field] = input.value;
                });

                // Get account numbers
                const accounts = [];
                card.querySelectorAll('.mfs-account-field').forEach(input => {
                    if (input.value.trim() !== '') {
                        accounts.push(input.value.trim());
                    }
                });
                mfsItem.mfs_account = accounts;

                if (Object.values(mfsItem).some(value =>
                        (Array.isArray(value) && value.length > 0) ||
                        (!Array.isArray(value) && value.trim() !== '')
                    )) {
                    mfs.push(mfsItem);
                }
            });

            // Update bankMfsData
            bankMfsData = {
                banks,
                mfs
            };

            // Save to localStorage
            localStorage.setItem(BANK_MFS_KEY, JSON.stringify(bankMfsData));

            // Close modal
            closeBankMfsModal();

            alert('Bank/MFS information saved! Changes will be applied when you update the invoice.');
        }

        /* ---------------- Form Submission ---------------- */
        document.getElementById('invoiceForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
            submitBtn.disabled = true;

            // Get bank/mfs data from localStorage or use initial data
            const savedData = localStorage.getItem(BANK_MFS_KEY);
            let bankMfsData = savedData ? JSON.parse(savedData) : window.bankMfsData;

            // Create FormData object
            const formData = new FormData(this);
            
            // Add vendor payment methods as JSON string
            formData.append('vendor_payment_methods', JSON.stringify(bankMfsData));
            
            // Add deleted items
            if (deletedItems.length > 0) {
                formData.append('deleted_work_items', JSON.stringify(deletedItems));
            }

            try {
                const response = await fetch(UPDATE_INVOICE_API, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // showSuccessNotification('Invoice updated successfully!');
                    setTimeout(() => {
                        window.location.href = 'index-invoice.php';
                    }, 1500);
                } else {
                    // showErrorNotification(result.message || 'Update failed');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Server error. Please try again.');
            } finally {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });

        /* ---------------- Initialize ---------------- */
        document.addEventListener('DOMContentLoaded', function() {
            // Calculate totals on load
            calculateTotal();
            calculateDue();

            // Auto-calculate on input changes
            document.querySelectorAll('#work_items .item-card').forEach(card => {
                if (card.style.display !== 'none') {
                    const qtyInput = card.querySelector('input[name^="work_items["][name$="[qty]"]');
                    const rateInput = card.querySelector('input[name^="work_items["][name$="[rate]"]');
                    
                    if (qtyInput) qtyInput.addEventListener('input', function() { calcAmount(this); });
                    if (rateInput) rateInput.addEventListener('input', function() { calcAmount(this); });
                }
            });

            // Close modal on overlay click
            const modal = document.getElementById('bankMfsModal');
            if (modal) {
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) {
                        closeBankMfsModal();
                    }
                });
            }

            // Load bank/mfs data from localStorage if exists
            const savedData = localStorage.getItem(BANK_MFS_KEY);
            if (savedData) {
                bankMfsData = JSON.parse(savedData);
            }
        });
    </script>

</body>

</html>