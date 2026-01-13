<?php
session_start();
require '../server/db_connection.php';

// Get IP path
$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898";
}
$base_ip_path = trim($ip_port, "/");
?>

<!DOCTYPE html>
<html lang="bn">

<head>
    <meta charset="UTF-8">
    <title>Create Invoice - Accounting</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f9fafb;
            color: #1f2937;
            line-height: 1.6;
        }

        .form-card {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }
        
        .item-row {
            transition: all 0.3s ease;
        }
        
        .item-row:hover {
            background: #f9fafb;
        }

        /* Invoice Number Display */
        .invoice-no-display {
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
            padding: 20px;
            border-radius: var(--radius);
            border: 2px solid var(--primary);
            margin-bottom: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(16, 185, 129, 0.1);
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
            color: var(--primary);
            letter-spacing: 1.5px;
            margin: 10px 0;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
        }

        .invoice-format-info {
            font-size: 12px;
            color: var(--gray);
            margin-top: 8px;
            text-align: center;
            font-style: italic;
        }

        .readonly-field {
            background: #f9fafb;
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

        .info-note {
            background: #d1fae5;
            color: var(--dark);
            padding: 15px;
            border-radius: var(--radius);
            margin-top: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-left: 4px solid var(--primary);
        }

        .info-note i {
            font-size: 18px;
            color: var(--primary);
        }

        /* Modal Styles */
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
    
    <!-- Preview Modal -->
    <div id="previewModal" class="preview-modal">
        <div class="preview-content">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800" id="previewTitle">File Preview</h3>
                <button onclick="closePreview()" class="text-gray-500 hover:text-gray-700 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="modalPreviewContent" class="p-4">
                <!-- Preview content will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main id="mainContent" class="pt-16 pl-0 lg:pl-64 transition-all duration-300 h-full">
        <div class="p-4 md:p-6 h-full">
            <!-- Header -->
            <div class="mb-6">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Create New Invoice</h1>
                        <p class="text-gray-600 mt-2">Create professional invoices for visa applications</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="accounting-invoices.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2.5 px-5 rounded-lg transition duration-300 flex items-center">
                            <i class="fas fa-arrow-left mr-2"></i> Back to Invoices
                        </a>
                    </div>
                </div>
            </div>

            <!-- Invoice Form -->
            <div class="container" style="max-width: 1200px; margin: 0 auto;">
                <div class="form-container" style="padding: 20px;">
                    <form id="invoiceForm" method="POST" action="./server/invoice-store.php" enctype="multipart/form-data">

                        <!-- Auto Generated Invoice Number / স্বয়ংক্রিয় ইনভয়েস নম্বর -->
                        <div class="invoice-no-display">
                            <div class="invoice-no-label">Invoice Number</div>
                            <div class="invoice-no-value" id="invoiceNoDisplay">Generating...</div>
                            <input type="hidden" name="invoice_no" id="invoiceNoInput" required>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4" style="margin-top: 20px;">
                                <div class="form-group">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-calendar-alt mr-2"></i> Invoice Date
                                    </label>
                                    <input type="date" name="date" id="invoiceDate" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                        value="<?php echo date('Y-m-d'); ?>" required
                                        onchange="generateInvoiceNumber()">
                                </div>
                                <div class="form-group">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-redo mr-2"></i> Regenerate
                                    </label>
                                    <button type="button" class="btn-outline w-full py-2.5"
                                        onclick="generateInvoiceNumber()">
                                        <i class="fas fa-sync-alt"></i> Generate New Number
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Client Information -->
                        <div class="form-card p-6 mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                                <i class="fas fa-user-tie mr-2"></i> Client Information
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div class="form-group">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-user mr-2"></i> Client Name *
                                    </label>
                                    <input type="text" name="client_title" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" 
                                           placeholder="Enter client name" required>
                                </div>
                                <div class="form-group">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-phone mr-2"></i> Phone Number
                                    </label>
                                    <input type="text" name="client_phone_no" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" 
                                           placeholder="01XXXXXXXXX">
                                </div>
                                <div class="form-group">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-envelope mr-2"></i> Email / CC
                                    </label>
                                    <input type="text" name="client_cc" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" 
                                           placeholder="example@email.com">
                                </div>
                            </div>
                        </div>

                        <!-- Work Items -->
                        <div class="form-card p-6 mb-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold text-gray-800">
                                    <i class="fas fa-tasks mr-2"></i> Work Items
                                </h3>
                                <button type="button" class="btn-outline py-2" onclick="addWorkItem()">
                                    <i class="fas fa-plus-circle"></i> Add Work Item
                                </button>
                            </div>
                            
                            <div id="work_items"></div>
                        </div>

                        <!-- Total Calculation -->
                        <div class="form-card p-6 mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                                <i class="fas fa-calculator mr-2"></i> Total Calculation
                            </h3>
                            
                            <div class="total-grid">
                                <div class="total-item">
                                    <div class="total-label"><i class="fas fa-receipt"></i> Total Amount</div>
                                    <div class="total-value" id="total_amount_display">0.00</div>
                                    <input type="hidden" name="total_amount" id="total_amount" value="0">
                                </div>
                                <div class="total-item">
                                    <div class="total-label"><i class="fas fa-money-bill-wave"></i> Paid Amount</div>
                                    <input type="number" name="paid_amount" id="paid_amount" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-center text-lg font-bold focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                        value="0" min="0" oninput="calculateDue()">
                                </div>
                                <div class="total-item">
                                    <div class="total-label"><i class="fas fa-clock"></i> Due Amount</div>
                                    <div class="total-value due" id="due_amount_display">0.00</div>
                                    <input type="hidden" name="due_amount" id="due_amount" value="0">
                                </div>
                            </div>
                        </div>

                        <!-- Vendor Information (Readonly from JSON) -->
                        <div class="form-card p-6 mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                                <i class="fas fa-building mr-2"></i> Vendor Information (From JSON)
                            </h3>
                            
                            <div class="readonly-field">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <div class="readonly-label">Company Name</div>
                                        <div class="readonly-value" id="vendor_company_name">Loading...</div>
                                    </div>
                                    <div>
                                        <div class="readonly-label">Phone</div>
                                        <div class="readonly-value" id="vendor_phone">Loading...</div>
                                    </div>
                                    <div>
                                        <div class="readonly-label">Email</div>
                                        <div class="readonly-value" id="vendor_email">Loading...</div>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <div class="readonly-label">Address</div>
                                    <div class="readonly-value" id="vendor_address">Loading...</div>
                                </div>
                            </div>
                        </div>

                        <!-- Bank/MFS Information (Editable) -->
                        <div class="form-card p-6 mb-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold text-gray-800">
                                    <i class="fas fa-university mr-2"></i> Bank / MFS Information
                                </h3>
                                <button type="button" class="btn-primary py-2" onclick="openBankMfsModal()">
                                    <i class="fas fa-edit mr-2"></i> Edit
                                </button>
                            </div>
                            
                            <div id="bank_mfs_display">
                                <div class="bank-mfs-container">
                                    <!-- Bank and MFS info will be loaded here -->
                                </div>
                            </div>
                        </div>

                        <!-- Information Note -->
                        <div class="info-note">
                            <i class="fas fa-lightbulb"></i>
                            <div>
                                <strong>Note:</strong> Vendor information (logo, name, phone) will be automatically displayed from JSON file.
                                Only client information, work details and payment information need to be entered here.
                                Bank/MFS information can be edited using the Edit button.
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="footer-actions" style="display: flex; justify-content: space-between; align-items: center; margin-top: 40px; padding-top: 25px; border-top: 2px solid var(--border);">
                            <button type="button" class="btn-danger py-3" onclick="clearForm()">
                                <i class="fas fa-trash-alt"></i> Clear Form
                            </button>
                            <button type="submit" class="btn-success py-3 px-6">
                                <i class="fas fa-save"></i> Save Invoice
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

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
                    <button type="button" class="btn-primary btn-small" onclick="addBankField()">
                        <i class="fas fa-plus"></i> Add Bank
                    </button>
                </div>
                <div id="bank_fields">
                    <!-- Bank fields will be added here -->
                </div>

                <!-- MFS Information -->
                <div class="section-header">
                    <div><i class="fas fa-mobile-alt"></i> MFS Information</div>
                    <button type="button" class="btn-primary btn-small" onclick="addMfsField()">
                        <i class="fas fa-plus"></i> Add MFS
                    </button>
                </div>
                <div id="mfs_fields">
                    <!-- MFS fields will be added here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-outline" onclick="closeBankMfsModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="btn-primary" onclick="saveBankMfsData()">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </div>
    </div>

    <?php include '../elements/floating-menus.php'; ?>

    <script src="../assets/js/script.js"></script>
    <script>
        const STORAGE_KEY = 'invoice_create_draft';
        const BANK_MFS_KEY = 'bank_mfs_data';
        let workIndex = 0;
        let vendorData = null;

        // Month abbreviations for invoice format
        const monthAbbr = {
            '01': 'JAN',
            '02': 'FEB',
            '03': 'MAR',
            '04': 'APR',
            '05': 'MAY',
            '06': 'JUN',
            '07': 'JUL',
            '08': 'AUG',
            '09': 'SEP',
            '10': 'OCT',
            '11': 'NOV',
            '12': 'DEC'
        };

        /* ---------------- Generate Invoice Number ---------------- */
        async function generateInvoiceNumber() {
            try {
                const dateInput = document.getElementById('invoiceDate').value;
                const dateObj = new Date(dateInput);

                // Get components from date
                const year = dateObj.getFullYear().toString().slice(-2);
                const month = String(dateObj.getMonth() + 1).padStart(2, '0');
                const day = String(dateObj.getDate()).padStart(2, '0');
                const monthAbbrText = monthAbbr[month];

                // Get next serial number from server
                const serialNumber = await getNextSerialNumber(year, month);

                // Format: TIF-MON-YY-XXXX-DD
                const invoiceNo = `TIF-${monthAbbrText}-${year}-${serialNumber}-${day}`;

                // Update display and input field
                document.getElementById('invoiceNoDisplay').textContent = invoiceNo;
                document.getElementById('invoiceNoDisplay').style.color = '#10b981';
                document.getElementById('invoiceNoInput').value = invoiceNo;

                // Save to localStorage
                saveDraft();

                return invoiceNo;

            } catch (error) {
                console.error('Error generating invoice number:', error);
                document.getElementById('invoiceNoDisplay').textContent = 'ERROR - Check Date';
                document.getElementById('invoiceNoDisplay').style.color = '#f87171';
                document.getElementById('invoiceNoInput').value = 'ERROR';
                return null;
            }
        }

        /* ---------------- Get Next Serial Number from Server ---------------- */
        async function getNextSerialNumber(year, month) {
            try {
                console.log(`Requesting serial for year: ${year}, month: ${month}`);

                // Send request to server to get next serial
                const response = await fetch(`server/get-next-serial.php?year=${year}&month=${month}`);

                if (!response.ok) {
                    console.error('Server response not OK:', response.status);
                    throw new Error(`Failed to fetch serial number: ${response.status}`);
                }

                const data = await response.json();
                console.log('Server response:', data);

                if (data.success) {
                    // Format serial as 4-digit with leading zeros
                    const serial = String(data.nextSerial).padStart(4, '0');
                    console.log(`Formatted serial: ${serial}`);
                    return serial;
                } else {
                    console.error('Server reported error:', data.message);
                    throw new Error(data.message || 'Failed to get serial number');
                }

            } catch (error) {
                console.error('Error getting serial number:', error);

                // Fallback: Generate from localStorage if server fails
                const fallback = await getFallbackSerialNumber(year, month);
                console.log(`Using fallback serial: ${fallback}`);
                return fallback;
            }
        }

        /* ---------------- Fallback Serial Number Generation ---------------- */
        async function getFallbackSerialNumber(year, month) {
            const storageKey = `invoice_serial_${year}_${month}`;
            const today = new Date().toISOString().split('T')[0];

            // Check if we have stored serial for this month
            const storedData = localStorage.getItem(storageKey);

            if (storedData) {
                const data = JSON.parse(storedData);

                // If it's the same day, use same serial
                if (data.date === today) {
                    return String(data.serial).padStart(4, '0');
                } else {
                    // New day, increment serial
                    const newSerial = data.serial + 1;
                    localStorage.setItem(storageKey, JSON.stringify({
                        date: today,
                        serial: newSerial
                    }));
                    return String(newSerial).padStart(4, '0');
                }
            } else {
                // First invoice of the month
                localStorage.setItem(storageKey, JSON.stringify({
                    date: today,
                    serial: 1
                }));
                return '0001';
            }
        }

        /* ---------------- Load Vendor Data from JSON ---------------- */
        async function loadVendorData() {
            try {
                const response = await fetch('../server/invoice-vendor.json');
                vendorData = await response.json();

                // Display vendor info
                document.getElementById('vendor_company_name').textContent = vendorData.company_name;
                document.getElementById('vendor_phone').textContent = vendorData.phone;
                document.getElementById('vendor_email').textContent = vendorData.email;

                // Format address
                const address = vendorData.address;
                const addressText = `${address.line1}, ${address.line2}, ${address.city}-${address.postcode}, ${address.country}`;
                document.getElementById('vendor_address').textContent = addressText;

                // Load bank/mfs data (from localStorage if exists, otherwise from JSON)
                loadBankMfsData();

            } catch (error) {
                console.error('Error loading vendor data:', error);
                alert('Error loading vendor data.');
            }
        }

        /* ---------------- Bank/MFS Data Management ---------------- */
        function loadBankMfsData() {
            // Try to load from localStorage first
            const savedData = localStorage.getItem(BANK_MFS_KEY);

            if (savedData) {
                displayBankMfsData(JSON.parse(savedData));
            } else if (vendorData) {
                // Use data from JSON
                const bankMfsData = {
                    banks: vendorData.bank || [],
                    mfs: vendorData.mfs || []
                };
                displayBankMfsData(bankMfsData);
            }
        }

        function displayBankMfsData(data) {
            const container = document.querySelector('.bank-mfs-container');
            container.innerHTML = '';

            // Display banks
            if (data.banks && data.banks.length > 0) {
                data.banks.forEach((bank, index) => {
                    const bankCard = document.createElement('div');
                    bankCard.className = 'bank-card';
                    bankCard.innerHTML = `
                        <h4 style="margin-bottom: 15px; color: var(--primary);">
                            <i class="fas fa-university"></i> Bank ${index + 1}
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <div class="readonly-label">Bank Name</div>
                                <div class="readonly-value">${bank.vendor_bank || 'N/A'}</div>
                            </div>
                            <div>
                                <div class="readonly-label">Account Number</div>
                                <div class="readonly-value">${bank.vendor_bank_account || 'N/A'}</div>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                            <div>
                                <div class="readonly-label">Branch</div>
                                <div class="readonly-value">${bank.vendor_bank_branch || 'N/A'}</div>
                            </div>
                            <div>
                                <div class="readonly-label">Routing Number</div>
                                <div class="readonly-value">${bank.vendor_bank_routing || 'N/A'}</div>
                            </div>
                        </div>
                    `;
                    container.appendChild(bankCard);
                });
            }

            // Display MFS
            if (data.mfs && data.mfs.length > 0) {
                data.mfs.forEach((mfs, index) => {
                    const mfsCard = document.createElement('div');
                    mfsCard.className = 'mfs-card';

                    let accountsHtml = '';
                    if (Array.isArray(mfs.vendor_mfs_account)) {
                        mfs.vendor_mfs_account.forEach(account => {
                            accountsHtml += `<div class="readonly-value">${account}</div>`;
                        });
                    } else {
                        accountsHtml = `<div class="readonly-value">${mfs.vendor_mfs_account || 'N/A'}</div>`;
                    }

                    mfsCard.innerHTML = `
                        <h4 style="margin-bottom: 15px; color: var(--success);">
                            <i class="fas fa-mobile-alt"></i> ${mfs.vendor_mfs_title || 'MFS'} ${index + 1}
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <div class="readonly-label">Service</div>
                                <div class="readonly-value">${mfs.vendor_mfs_title || 'N/A'}</div>
                            </div>
                            <div>
                                <div class="readonly-label">Type</div>
                                <div class="readonly-value">${mfs.vendor_mfs_type || 'N/A'}</div>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="readonly-label">Account Number</div>
                            ${accountsHtml}
                        </div>
                    `;
                    container.appendChild(mfsCard);
                });
            }

            // Store in hidden fields for form submission
            storeBankMfsInForm(data);
        }

        function storeBankMfsInForm(data) {
            // Remove existing hidden fields
            document.querySelectorAll('[data-bank-mfs-field]').forEach(field => field.remove());

            // Add new hidden fields
            const form = document.getElementById('invoiceForm');

            if (data.banks) {
                data.banks.forEach((bank, index) => {
                    Object.keys(bank).forEach(key => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = `bank[${index}][${key}]`;
                        input.value = bank[key];
                        input.setAttribute('data-bank-mfs-field', 'true');
                        form.appendChild(input);
                    });
                });
            }

            if (data.mfs) {
                data.mfs.forEach((mfs, index) => {
                    Object.keys(mfs).forEach(key => {
                        if (Array.isArray(mfs[key])) {
                            mfs[key].forEach((value, i) => {
                                const input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = `mfs[${index}][${key}][]`;
                                input.value = value;
                                input.setAttribute('data-bank-mfs-field', 'true');
                                form.appendChild(input);
                            });
                        } else {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = `mfs[${index}][${key}]`;
                            input.value = mfs[key];
                            input.setAttribute('data-bank-mfs-field', 'true');
                            form.appendChild(input);
                        }
                    });
                });
            }
        }

        /* ---------------- Modal Functions ---------------- */
        function openBankMfsModal() {
            // Load current data into modal
            const savedData = localStorage.getItem(BANK_MFS_KEY);
            const currentData = savedData ? JSON.parse(savedData) : {
                banks: vendorData?.bank || [],
                mfs: vendorData?.mfs || []
            };

            populateModalFields(currentData);
            document.getElementById('bankMfsModal').style.display = 'flex';
        }

        function closeBankMfsModal() {
            document.getElementById('bankMfsModal').style.display = 'none';
        }

        function populateModalFields(data) {
            // Clear existing fields
            document.getElementById('bank_fields').innerHTML = '';
            document.getElementById('mfs_fields').innerHTML = '';

            // Add bank fields
            if (data.banks && data.banks.length > 0) {
                data.banks.forEach((bank, index) => {
                    addBankField(bank, index);
                });
            } else {
                addBankField();
            }

            // Add MFS fields
            if (data.mfs && data.mfs.length > 0) {
                data.mfs.forEach((mfs, index) => {
                    addMfsField(mfs, index);
                });
            } else {
                addMfsField();
            }
        }

        function addBankField(bankData = {}, index = null) {
            const fieldsDiv = document.getElementById('bank_fields');
            const bankIndex = index !== null ? index : fieldsDiv.children.length;

            const bankField = document.createElement('div');
            bankField.className = 'item-card';
            bankField.innerHTML = `
                <div class="flex justify-between items-center mb-4">
                    <h4 class="text-gray-800 font-semibold">
                        <i class="fas fa-university text-green-600 mr-2"></i> Bank ${bankIndex + 1}
                    </h4>
                    <button type="button" class="bg-red-600 hover:bg-red-700 text-white font-medium py-1.5 px-3 rounded flex items-center" onclick="removeBankField(this)">
                        <i class="fas fa-trash mr-1"></i> Remove
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Bank Name</label>
                        <input type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 bank-field" data-field="vendor_bank" 
                               value="${bankData.vendor_bank || ''}" placeholder="DBBL, BRAC Bank, etc.">
                    </div>
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Account Number</label>
                        <input type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 bank-field" data-field="vendor_bank_account" 
                               value="${bankData.vendor_bank_account || ''}" placeholder="2021100019475">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Branch</label>
                        <input type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 bank-field" data-field="vendor_bank_branch" 
                               value="${bankData.vendor_bank_branch || ''}" placeholder="Ashkona (Dhaka North)">
                    </div>
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Routing Number</label>
                        <input type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 bank-field" data-field="vendor_bank_routing" 
                               value="${bankData.vendor_bank_routing || ''}" placeholder="090260205">
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

            // Create accounts HTML
            let accountsHtml = '';
            const accounts = Array.isArray(mfsData.vendor_mfs_account) ?
                mfsData.vendor_mfs_account :
                (mfsData.vendor_mfs_account ? [mfsData.vendor_mfs_account] : ['']);

            accounts.forEach((account, accIndex) => {
                accountsHtml += `
                    <div class="mfs-account-item">
                        <input type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 mfs-account-field" 
                               value="${account}" placeholder="01XXXXXXXXX">
                        ${accIndex > 0 ? `
                            <button type="button" class="bg-red-600 hover:bg-red-700 text-white w-8 h-8 rounded flex items-center justify-center" onclick="removeMfsAccount(this)">
                                <i class="fas fa-minus"></i>
                            </button>
                        ` : ''}
                    </div>
                `;
            });

            mfsField.innerHTML = `
                <div class="flex justify-between items-center mb-4">
                    <h4 class="text-gray-800 font-semibold">
                        <i class="fas fa-mobile-alt text-green-600 mr-2"></i> MFS ${mfsIndex + 1}
                    </h4>
                    <button type="button" class="bg-red-600 hover:bg-red-700 text-white font-medium py-1.5 px-3 rounded flex items-center" onclick="removeMfsField(this)">
                        <i class="fas fa-trash mr-1"></i> Remove
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Service Name</label>
                        <input type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 mfs-field" data-field="vendor_mfs_title" 
                               value="${mfsData.vendor_mfs_title || ''}" placeholder="bkash, Nagad, Rocket">
                    </div>
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                        <input type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 mfs-field" data-field="vendor_mfs_type" 
                               value="${mfsData.vendor_mfs_type || ''}" placeholder="Personal, Merchant">
                    </div>
                </div>
                <div class="form-group mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Account Number</label>
                    ${accountsHtml}
                    <button type="button" class="btn-outline btn-small mt-2" onclick="addMfsAccount(this)">
                        <i class="fas fa-plus"></i> Add Another Number
                    </button>
                </div>
                <div class="form-group mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Special Note</label>
                    <input type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 mfs-field" data-field="vendor_amount_note" 
                           value="${mfsData.vendor_amount_note || ''}" placeholder="Special instructions...">
                </div>
            `;
            fieldsDiv.appendChild(mfsField);
        }

        function removeBankField(button) {
            if (confirm('Are you sure you want to remove this bank information?')) {
                button.closest('.item-card').remove();
                // Renumber remaining banks
                const bankFields = document.querySelectorAll('#bank_fields .item-card');
                bankFields.forEach((field, index) => {
                    field.querySelector('h4').innerHTML = `<i class="fas fa-university text-green-600 mr-2"></i> Bank ${index + 1}`;
                });
            }
        }

        function removeMfsField(button) {
            if (confirm('Are you sure you want to remove this MFS information?')) {
                button.closest('.item-card').remove();
                // Renumber remaining MFS
                const mfsFields = document.querySelectorAll('#mfs_fields .item-card');
                mfsFields.forEach((field, index) => {
                    field.querySelector('h4').innerHTML = `<i class="fas fa-mobile-alt text-green-600 mr-2"></i> MFS ${index + 1}`;
                });
            }
        }

        function addMfsAccount(button) {
            const container = button.previousElementSibling;
            const newAccount = document.createElement('div');
            newAccount.className = 'mfs-account-item';
            newAccount.innerHTML = `
                <input type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 mfs-account-field" placeholder="01XXXXXXXXX">
                <button type="button" class="bg-red-600 hover:bg-red-700 text-white w-8 h-8 rounded flex items-center justify-center" onclick="removeMfsAccount(this)">
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
                mfsItem.vendor_mfs_account = accounts;

                if (Object.values(mfsItem).some(value =>
                        (Array.isArray(value) && value.length > 0) ||
                        (!Array.isArray(value) && value.trim() !== '')
                    )) {
                    mfs.push(mfsItem);
                }
            });

            const bankMfsData = {
                banks,
                mfs
            };

            // Save to localStorage
            localStorage.setItem(BANK_MFS_KEY, JSON.stringify(bankMfsData));

            // Update display
            displayBankMfsData(bankMfsData);

            // Save to server (AJAX call)
            saveToServer(bankMfsData);

            // Close modal
            closeBankMfsModal();

            alert('Bank/MFS information saved successfully!');
        }

        function saveToServer(data) {
            // AJAX request to save to server
            fetch('server/save-bank-mfs.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(result => {
                    console.log('Server save result:', result);
                })
                .catch(error => {
                    console.error('Error saving to server:', error);
                });
        }

        /* ---------------- Work Items Functions ---------------- */
        function addWorkItem(data = {}) {
            const div = document.createElement('div');
            div.className = 'item-card mb-4';
            div.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-heading mr-2"></i> Title
                        </label>
                        <input type="text" name="work_title[]" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" 
                               value="${data.work_title||''}" placeholder="Work title">
                    </div>
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-box mr-2"></i> Quantity
                        </label>
                        <input type="number" name="work_qty[]" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" 
                               value="${data.work_qty||1}" min="1" oninput="calcAmount(this)" placeholder="0">
                    </div>
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-tag mr-2"></i> Rate (per unit)
                        </label>
                        <input type="number" name="work_rate[]" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" 
                               value="${data.work_rate||0}" min="0" step="0.01" oninput="calcAmount(this)" placeholder="0.00">
                    </div>
                </div>
                <div class="form-group mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-align-left mr-2"></i> Details
                    </label>
                    <textarea name="work_particular[]" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" 
                              placeholder="Work details...">${data.work_particular||''}</textarea>
                </div>
                <div class="amount-display">
                    <span>Total: ৳ <span class="amount_text">0.00</span></span>
                    <input type="hidden" name="amount[]" value="0">
                    <button type="button" class="bg-red-600 hover:bg-red-700 text-white font-medium py-1.5 px-3 rounded flex items-center ml-4" onclick="removeWorkItem(this)">
                        <i class="fas fa-trash mr-1"></i> Remove
                    </button>
                </div>
            `;
            document.getElementById('work_items').appendChild(div);
            calculateTotal();
        }

        function removeWorkItem(btn) {
            if (confirm('Are you sure you want to remove this work item?')) {
                btn.closest('.item-card').remove();
                calculateTotal();
            }
        }

        /* ---------------- Calculations ---------------- */
        function calcAmount(el) {
            const box = el.closest('.item-card');
            const qty = parseFloat(box.querySelector('[name="work_qty[]"]').value) || 0;
            const rate = parseFloat(box.querySelector('[name="work_rate[]"]').value) || 0;
            const amount = qty * rate;
            box.querySelector('.amount_text').innerText = amount.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            box.querySelector('[name="amount[]"]').value = amount.toFixed(2);
            calculateTotal();
        }

        function calculateTotal() {
            let total = 0;
            document.querySelectorAll('[name="amount[]"]').forEach(i => {
                total += parseFloat(i.value) || 0;
            });

            document.getElementById('total_amount_display').innerText = total.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            document.getElementById('total_amount').value = total.toFixed(2);
            calculateDue();
        }

        function calculateDue() {
            const total = parseFloat(document.getElementById('total_amount').value) || 0;
            const paid = parseFloat(document.getElementById('paid_amount').value) || 0;
            const due = Math.max(0, total - paid);

            document.getElementById('due_amount_display').innerText = due.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            document.getElementById('due_amount').value = due.toFixed(2);
        }

        /* ---------------- Form Management ---------------- */
        function saveDraft() {
            const data = new FormData(document.getElementById('invoiceForm'));
            const obj = {};
            data.forEach((v, k) => {
                if (obj[k]) {
                    if (!Array.isArray(obj[k])) obj[k] = [obj[k]];
                    obj[k].push(v);
                } else obj[k] = v;
            });
            localStorage.setItem(STORAGE_KEY, JSON.stringify(obj));
        }

        function loadDraft() {
            const raw = localStorage.getItem(STORAGE_KEY);
            if (!raw) return;
            const data = JSON.parse(raw);

            // Load simple fields
            Object.keys(data).forEach(k => {
                if (!k.includes('[]') && !k.startsWith('bank[') && !k.startsWith('mfs[')) {
                    const el = document.querySelector(`[name="${k}"]`);
                    if (el) el.value = data[k];
                }
            });

            // Load work items
            if (data['work_title[]']) {
                const workItems = [];
                const titles = Array.isArray(data['work_title[]']) ? data['work_title[]'] : [data['work_title[]']];
                const qtys = Array.isArray(data['work_qty[]']) ? data['work_qty[]'] : [data['work_qty[]']];
                const rates = Array.isArray(data['work_rate[]']) ? data['work_rate[]'] : [data['work_rate[]']];
                const particulars = Array.isArray(data['work_particular[]']) ? data['work_particular[]'] : [data['work_particular[]']];

                for (let i = 0; i < titles.length; i++) {
                    workItems.push({
                        work_title: titles[i] || '',
                        work_qty: qtys[i] || 1,
                        work_rate: rates[i] || 0,
                        work_particular: particulars[i] || ''
                    });
                }

                document.getElementById('work_items').innerHTML = '';
                workItems.forEach(item => addWorkItem(item));
            }

            calculateTotal();
        }

        function clearForm() {
            if (confirm('Are you sure you want to clear the entire form? (Bank/MFS information will remain intact)')) {
                localStorage.removeItem(STORAGE_KEY);
                document.getElementById('invoiceForm').reset();
                document.getElementById('work_items').innerHTML = '';
                addWorkItem();
                generateInvoiceNumber();
                calculateTotal();
            }
        }

        /* ---------------- Initialize ---------------- */
        document.addEventListener('DOMContentLoaded', async () => {
            // Load vendor data
            await loadVendorData();

            // Add initial work item
            addWorkItem();

            // Generate initial invoice number
            await generateInvoiceNumber();

            // Load draft if exists
            loadDraft();

            // Auto-save on input
            document.addEventListener('input', () => {
                setTimeout(saveDraft, 100);
            });

            // Close modal on overlay click
            document.getElementById('bankMfsModal').addEventListener('click', (e) => {
                if (e.target === document.getElementById('bankMfsModal')) {
                    closeBankMfsModal();
                }
            });
        });

        /* ---------------- Form Submission ---------------- */
        // Form submission with AJAX - UPDATED VERSION
        document.getElementById('invoiceForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            submitBtn.disabled = true;

            try {
                // Collect all form data
                const formData = {
                    invoice_no: document.getElementById('invoiceNoInput').value,
                    date: document.querySelector('[name="date"]').value,
                    client_title: document.querySelector('[name="client_title"]').value,
                    client_phone_no: document.querySelector('[name="client_phone_no"]').value || '',
                    client_cc: document.querySelector('[name="client_cc"]').value || '',
                    total_amount: document.getElementById('total_amount').value,
                    paid_amount: document.getElementById('paid_amount').value,
                    due_amount: document.getElementById('due_amount').value,
                    work_title: [],
                    work_qty: [],
                    work_rate: [],
                    work_particular: [],
                    amount: []
                };

                // Collect work items
                document.querySelectorAll('[name="work_title[]"]').forEach((input, index) => {
                    formData.work_title.push(input.value);
                    formData.work_qty.push(document.querySelectorAll('[name="work_qty[]"]')[index].value);
                    formData.work_rate.push(document.querySelectorAll('[name="work_rate[]"]')[index].value);
                    formData.work_particular.push(document.querySelectorAll('[name="work_particular[]"]')[index].value);
                    formData.amount.push(document.querySelectorAll('[name="amount[]"]')[index].value);
                });

                // Collect bank and MFS data from localStorage
                const bankMfsData = JSON.parse(localStorage.getItem('bank_mfs_data') || '{"banks":[],"mfs":[]}');

                // Convert bank data to the format PHP expects
                if (bankMfsData.banks && bankMfsData.banks.length > 0) {
                    formData.bank = bankMfsData.banks.map((bank, index) => ({
                        vendor_bank: bank.vendor_bank || '',
                        vendor_bank_account: bank.vendor_bank_account || '',
                        vendor_bank_branch: bank.vendor_bank_branch || '',
                        vendor_bank_routing: bank.vendor_bank_routing || ''
                    }));
                } else {
                    formData.bank = [];
                }

                // Convert MFS data to the format PHP expects
                if (bankMfsData.mfs && bankMfsData.mfs.length > 0) {
                    formData.mfs = bankMfsData.mfs.map((mfs, index) => {
                        const mfsItem = {
                            vendor_mfs_title: mfs.vendor_mfs_title || '',
                            vendor_mfs_type: mfs.vendor_mfs_type || '',
                            vendor_amount_note: mfs.vendor_amount_note || ''
                        };

                        // Handle accounts array
                        if (Array.isArray(mfs.vendor_mfs_account)) {
                            mfsItem.vendor_mfs_account = mfs.vendor_mfs_account;
                        } else if (mfs.vendor_mfs_account) {
                            mfsItem.vendor_mfs_account = [mfs.vendor_mfs_account];
                        } else {
                            mfsItem.vendor_mfs_account = [''];
                        }

                        return mfsItem;
                    });
                } else {
                    formData.mfs = [];
                }

                console.log('Sending form data:', formData);

                // Send form data via AJAX using FormData for proper array handling
                const postData = new FormData();

                // Add simple fields
                Object.keys(formData).forEach(key => {
                    if (Array.isArray(formData[key])) {
                        // Handle arrays (work items)
                        if (key === 'bank' || key === 'mfs') {
                            // JSON encode bank and mfs arrays
                            postData.append(key, JSON.stringify(formData[key]));
                        } else if (key.startsWith('work_') || key === 'amount') {
                            // Handle work item arrays individually
                            formData[key].forEach((value, index) => {
                                postData.append(`${key}[]`, value);
                            });
                        }
                    } else {
                        // Handle simple values
                        postData.append(key, formData[key]);
                    }
                });

                // Send form data via AJAX
                const response = await fetch('server/invoice-store.php', {
                    method: 'POST',
                    body: postData
                });

                const result = await response.json();

                if (result.success) {
                    // Show success message
                    alert(result.message);

                    // Clear localStorage
                    localStorage.removeItem('invoice_create_draft');

                    // Reset form
                    this.reset();
                    document.getElementById('work_items').innerHTML = '';
                    addWorkItem();
                    calculateTotal();

                    // Generate new invoice number
                    await generateInvoiceNumber();

                    console.log('Invoice saved:', result);

                    // Optionally show a success modal or redirect
                    showSuccessModal(result.invoice_no, result.invoice_id);

                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Server error. Please try again.');
            } finally {
                // Restore button state
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });

        function showSuccessModal(invoiceNo, invoiceId) {
            // Create success modal
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 1000;
            `;

            modal.innerHTML = `
                <div class="bg-white p-8 rounded-lg shadow-xl max-w-md w-full mx-4">
                    <div class="text-center">
                        <div class="text-green-500 text-5xl mb-4">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800 mb-2">Invoice Created Successfully!</h2>
                        <p class="text-gray-600 mb-3">Invoice Number:</p>
                        <h3 class="text-green-600 text-xl font-bold mb-6">${invoiceNo}</h3>
                        <div class="flex flex-col sm:flex-row gap-3 justify-center">
                            <button onclick="printInvoice(${invoiceId})" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2.5 px-5 rounded-lg flex items-center justify-center gap-2">
                                <i class="fas fa-print"></i> Print Invoice
                            </button>
                            <button onclick="createNewInvoice()" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-5 rounded-lg flex items-center justify-center gap-2">
                                <i class="fas fa-plus"></i> Create New
                            </button>
                            <button onclick="this.closest('div[style*=\"position: fixed\"]').remove()" class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2.5 px-5 rounded-lg">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);
        }

        function printInvoice(invoiceId) {
            window.open(`print-invoice.php?id=${invoiceId}`, '_blank');
        }

        function createNewInvoice() {
            // Clear form and reload page
            localStorage.removeItem('invoice_create_draft');
            location.reload();
        }

        // Calculate on page load
        window.onload = calculateTotal;
    </script>
</body>
</html>