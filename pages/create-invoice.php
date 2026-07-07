<?php
// pages/create-invoice.php

include_once('./authenticate.php');
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
        /* Form Container */
        .form-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
            padding: 40px;
            margin: 20px 0;
        }
        
        /* Invoice Number Display */
        .invoice-no-display {
            background: linear-gradient(135deg, #f0f9ff 0%, #e6f7ff 100%);
            border: 2px solid #0ea5e9;
            border-radius: 16px;
            padding: 32px;
            margin-bottom: 40px;
            text-align: center;
        }
        
        .invoice-no-label {
            font-size: 14px;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .invoice-no-value {
            font-size: 36px;
            font-weight: 700;
            color: #0ea5e9;
            margin: 15px 0 25px 0;
        }
        
        /* Form Cards */
        .form-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            padding: 32px;
            margin-bottom: 32px;
        }
        
        .form-card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border-color: #cbd5e1;
        }
        
        /* Form Groups */
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            color: #374151;
            font-weight: 500;
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            font-size: 15px;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            border: 1.5px solid #d1d5db;
            border-radius: 10px;
            padding: 14px 16px;
            font-size: 15px;
            transition: all 0.2s ease;
            width: 100%;
            background: #fff;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.15);
            outline: none;
        }
        
        /* Textarea specific */
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        /* Buttons */
        .btn-primary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            font-weight: 600;
            padding: 14px 28px;
            border-radius: 10px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 15px;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            font-weight: 600;
            padding: 14px 32px;
            border-radius: 10px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 15px;
        }
        
        .btn-success:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            font-weight: 600;
            padding: 14px 32px;
            border-radius: 10px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 15px;
        }
        
        .btn-danger:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.3);
        }
        
        .btn-outline {
            background: white;
            color: #374151;
            font-weight: 600;
            padding: 14px 28px;
            border-radius: 10px;
            border: 2px solid #d1d5db;
            transition: all 0.3s ease;
            cursor: pointer;
            font-size: 15px;
        }
        
        .btn-outline:hover {
            border-color: #10b981;
            color: #10b981;
            background: #f0fdfa;
        }
        
        /* Work Item Cards */
        .item-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            transition: all 0.2s ease;
        }
        
        .item-card:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }
        
        /* Amount Display */
        .amount-display {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 20px;
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-weight: 600;
            color: #374151;
            font-size: 16px;
        }
        
        .amount-display span {
            font-size: 18px;
            color: #059669;
        }
        
        /* Total Grid */
        .total-grid {
            display: grid;
            grid-template-columns: repeat(1, 1fr);
            gap: 24px;
        }
        
        @media (min-width: 768px) {
            .total-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        .total-item {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 28px;
            text-align: center;
        }
        
        .total-label {
            font-size: 14px;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .total-value {
            font-size: 36px;
            font-weight: 700;
            color: #059669;
            margin-top: 10px;
        }
        
        .total-value.due {
            color: #ef4444;
        }
        
        /* Readonly Fields */
        .readonly-field {
            background: #f8fafc;
            border-radius: 12px;
            padding: 28px;
        }
        
        .readonly-label {
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .readonly-value {
            font-size: 17px;
            color: #1f2937;
            font-weight: 500;
            margin-top: 5px;
        }
        
        /* Bank/MFS Cards */
        .bank-card,
        .mfs-card {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 28px;
            margin-bottom: 24px;
        }
        
        .bank-card {
            border-left: 4px solid #3b82f6;
        }
        
        .mfs-card {
            border-left: 4px solid #8b5cf6;
        }
        
        /* MFS Account Items */
        .mfs-account-item {
            display: flex;
            gap: 12px;
            margin-bottom: 12px;
        }
        
        /* Info Note */
        .info-note {
            background: linear-gradient(135deg, #fef3c7 0%, #fef9c3 100%);
            border: 2px solid #fbbf24;
            border-radius: 12px;
            padding: 28px;
            margin: 40px 0;
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }
        
        .info-note i {
            color: #d97706;
            font-size: 24px;
            flex-shrink: 0;
        }
        
        .info-note div {
            color: #92400e;
            line-height: 1.6;
        }
        
        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            padding: 20px;
        }
        
        .modal {
            background: white;
            border-radius: 16px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow: auto;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        
        .modal-header {
            padding: 28px 32px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            font-size: 20px;
            font-weight: 700;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            color: #6b7280;
            cursor: pointer;
            transition: color 0.2s;
        }
        
        .modal-close:hover {
            color: #374151;
        }
        
        .modal-body {
            padding: 32px;
        }
        
        .modal-footer {
            padding: 28px 32px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
        
        /* Section Header */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 40px 0 25px;
            padding-bottom: 16px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .section-header div {
            font-size: 20px;
            font-weight: 700;
            color: #374151;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-small {
            padding: 10px 20px;
            font-size: 14px;
        }
        
        /* Footer Actions */
        .footer-actions {
            background: white;
            border-radius: 16px;
            padding: 40px;
            margin-top: 50px;
            border-top: 3px solid #e5e7eb;
        }
        
        /* Grid gap adjustments */
        .gap-4 {
            gap: 24px !important;
        }
        
        .gap-6 {
            gap: 32px !important;
        }
        
        /* ========== DUAL MODE WORK ITEM STYLES ========== */
        /* Mode Toggle Styles */
        .mode-toggle-container {
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .mode-btn {
            transition: all 0.2s ease;
            cursor: pointer;
            font-weight: 500;
            border: 2px solid;
        }
        
        .mode-btn:not(.active):hover {
            background-color: #f8fafc;
            border-color: #cbd5e1;
        }
        
        /* System Work Styles */
        .system-work-container {
            animation: fadeIn 0.3s ease-out;
        }
        
        .client-info-display {
            border-left: 4px solid #3b82f6;
        }
        
        .task-group-card {
            transition: all 0.2s ease;
            border-left: 4px solid #8b5cf6;
            background: white;
        }
        
        .task-group-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .generated-item {
            border-left: 4px solid #10b981;
            animation: slideIn 0.2s ease-out;
            background: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .generated-item:hover {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
        }
        
        /* Task Checkboxes */
        .task-checkbox-container {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.2s;
        }
        
        .task-checkbox-container:hover {
            background: #f3f4f6;
        }
        
        .task-checkbox-container input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin-right: 10px;
            cursor: pointer;
        }
        
        /* Animations */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-10px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .form-container {
                padding: 24px;
                margin: 10px 0;
            }
            
            .form-card {
                padding: 24px;
                margin-bottom: 24px;
            }
            
            .invoice-no-display {
                padding: 24px;
                margin-bottom: 30px;
            }
            
            .invoice-no-value {
                font-size: 28px;
            }
            
            .total-item {
                padding: 20px;
            }
            
            .total-value {
                font-size: 28px;
            }
            
            .readonly-field {
                padding: 20px;
            }
            
            .bank-card,
            .mfs-card {
                padding: 20px;
                margin-bottom: 20px;
            }
            
            .info-note {
                padding: 20px;
                margin: 30px 0;
                flex-direction: column;
                text-align: center;
            }
            
            .mfs-account-item {
                flex-direction: column;
            }
            
            .modal {
                width: 95%;
                padding: 0;
            }
            
            .modal-body {
                padding: 24px;
            }
            
            .modal-header {
                padding: 20px 24px;
            }
            
            .modal-footer {
                padding: 20px 24px;
            }
            
            .footer-actions {
                padding: 24px;
                margin-top: 30px;
            }
            
            .form-group input,
            .form-group textarea,
            .form-group select {
                padding: 12px 14px;
            }
            
            .mode-toggle-container .flex {
                flex-direction: column;
                gap: 12px;
            }
            
            .mode-btn {
                width: 100%;
                text-align: center;
                justify-content: center;
            }
            
            .task-checkbox-container {
                padding: 10px;
            }
        }
        
        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .form-card,
        .item-card,
        .modal {
            animation: fadeIn 0.3s ease-out;
        }
        
        /* Scrollbar Styling */
        .modal::-webkit-scrollbar {
            width: 8px;
        }
        
        .modal::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        .modal::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }
        
        .modal::-webkit-scrollbar-thumb:hover {
            background: #a1a1a1;
        }
        
        /* Input groups inside grids */
        .grid .form-group:last-child {
            margin-bottom: 0;
        }
        
        /* Remove button spacing */
        .amount-display button {
            margin-left: 16px;
        }
        
        /* System mode specific inputs */
        input[data-generated="true"] {
            background-color: #f8fafc;
        }
        
        input[data-generated="true"]:focus {
            background-color: white;
        }
        
        .task-checkbox {
            accent-color: #10b981;
        }
        
        /* Add this to your CSS styles */
        .client-change-warning {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                opacity: 1;
            }
            50% {
                opacity: 0.7;
            }
            100% {
                opacity: 1;
            }
        }
        
        .selected-client-name {
            transition: color 0.3s ease;
        }
        
        /* System work select enabled/disabled states */
        .system-work-select:disabled {
            background-color: #f3f4f6;
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        .system-work-select:not(:disabled) {
            background-color: white;
            cursor: pointer;
        }
        
        /* Client search styles */
        #clientSearchContainer {
            position: relative;
        }
        
        #clientDropdown {
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
        }
        
        .client-option {
            cursor: pointer;
            padding: 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .client-option:hover {
            background-color: #f3f4f6;
        }
        
        .client-option.active {
            background-color: #e5e7eb;
        }
        
        /* Hidden input for form submission */
        .hidden-client-input {
            position: absolute;
            opacity: 0;
            height: 0;
            width: 0;
        }
        
        /* Add to your CSS styles */
        .generated-input {
            transition: all 0.3s ease;
        }
        
        .generated-input:disabled {
            background-color: #f3f4f6;
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .input-submission-active {
            border-color: #10b981;
            background-color: #f0fdfa;
        }
        
        .input-submission-inactive {
            opacity: 0.5;
            background-color: #f9fafb;
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
            <div class="mb-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Create New Invoice</h1>
                        <p class="text-gray-600 mt-2">Create professional invoices for visa applications</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="index-invoice.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2.5 px-5 rounded-lg transition duration-300 flex items-center gap-2">
                            <i class="fas fa-arrow-left"></i> Back to Invoices
                        </a>
                    </div>
                </div>
            </div>

            <!-- Invoice Form -->
            <div class="max-w-6xl mx-auto">
                <div class="form-container">
                    <form id="invoiceForm" method="POST" action="./server/invoice-store.php" enctype="multipart/form-data">

                        <!-- Auto Generated Invoice Number -->
                        <div class="invoice-no-display">
                            <div class="invoice-no-label">Invoice Number</div>
                            <div class="invoice-no-value" id="invoiceNoDisplay">Generating...</div>
                            <input type="hidden" name="invoice_no" id="invoiceNoInput" required>
                        </div>

                        <!-- Client Information -->
                        <div class="form-card">
                            <h3 class="text-lg font-semibold text-gray-800 mb-6 flex items-center gap-2">
                                <i class="fas fa-user-tie"></i> Client Information
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-4">
                                <!-- Client Search Component -->
                                <div class="form-group text-left">
                                    <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                                        <i class="fas fa-user"></i> Client Name *
                                    </label>
                                    <!-- Hidden input for form submission -->
                                    <input type="hidden" name="client_title" id="selectedClientId" value="" required>
                                    <input type="hidden" id="selectedClientName" value="">
                                    
                                    <div class="grid grid-cols-4 gap-4 items-center">
                                        <div id="clientSearchContainer" class="relative w-full col-span-3">
                                            <input
                                                type="text"
                                                id="clientInput"
                                                placeholder="Search for a client..."
                                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:outline-none shadow-sm transition-all"
                                                autocomplete="off">
                                        
                                            <ul id="clientDropdown"
                                                class="absolute w-full bg-white border border-gray-300 rounded-lg mt-1 max-h-60 overflow-auto shadow-xl hidden z-50">
                                            </ul>
                                        </div>
                                        
                                        <div class="col-span-1 flex gap-2">
                                            <a href="./create-client.php" 
                                               target="_blank" 
                                               title="Add New Client"
                                               class="flex items-center justify-center w-full bg-purple-600 hover:bg-purple-700 text-white py-2.5 rounded-lg transition-colors shadow-sm">
                                                <i class="fas fa-plus"></i>
                                            </a>
                                            
                                            <button type="button" onclick="loadClients()" 
                                                    title="Refresh List"
                                                    class="flex items-center justify-center w-full bg-gray-100 hover:bg-gray-200 text-gray-700 py-2.5 rounded-lg border border-gray-300 transition-all active:scale-95 shadow-sm">
                                                <i class="fa-solid fa-arrows-rotate"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                                        <i class="fas fa-calendar-alt"></i> Invoice Date
                                    </label>
                                    <input type="date" name="date" id="invoiceDate" 
                                           class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all"
                                           value="<?php echo date('Y-m-d'); ?>" required
                                           onchange="generateInvoiceNumber()">
                                </div>
                                <div class="form-group">
                                    <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                                        <i class="fas fa-phone"></i> Phone Number
                                    </label>
                                    <input type="text" name="client_phone_no" 
                                           class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all"
                                           placeholder="01XXXXXXXXX">
                                </div>
                                <div class="form-group">
                                    <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                                        <i class="fas fa-envelope"></i> Email / CC
                                    </label>
                                    <input type="text" name="client_cc" 
                                           class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all"
                                           placeholder="example@email.com">
                                </div>
                            </div>
                        </div>

                        <!-- Work Items -->
                        <div class="form-card">
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                                <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                                    <i class="fas fa-tasks"></i> Work Items
                                </h3>
                                <button type="button" class="btn-outline py-2.5 px-4 flex items-center gap-2"
                                        onclick="addWorkItem()">
                                    <i class="fas fa-plus-circle"></i> Add Work Item
                                </button>
                            </div>
                            
                            <div id="work_items"></div>
                            
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                                <div></div>
                                <button type="button" class="btn-outline py-2.5 px-4 flex items-center gap-2"
                                        onclick="addWorkItem()">
                                    <i class="fas fa-plus-circle"></i> Add Work Item
                                </button>
                            </div>
                        </div>

                        <!-- Advance Balance Banner — client select হলে সবসময় দেখাবে -->
                        <div id="advanceBanner" class="form-card hidden bg-indigo-50 border border-indigo-200 rounded-xl p-5">

                            <!-- Header -->
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-9 h-9 bg-indigo-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-piggy-bank text-indigo-600"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-semibold text-indigo-800">Client Advance Balance</p>
                                    <p class="text-xs text-indigo-500">
                                        Available: <strong id="advanceBalanceDisplay">৳0.00</strong>
                                    </p>
                                </div>
                            </div>

                            <!-- Balance = 0: Add advance button -->
                            <div id="advNoBalanceSection">
                                <button type="button" onclick="openAddAdvanceForm()"
                                    class="w-full py-2 px-4 border-2 border-dashed border-indigo-300 rounded-lg text-sm text-indigo-600 hover:bg-indigo-100 transition-colors flex items-center justify-center gap-2">
                                    <i class="fas fa-plus-circle"></i> Advance Balance Add করুন
                                </button>
                            </div>

                            <!-- Balance > 0: Use advance section -->
                            <div id="advHasBalanceSection" class="hidden">
                                <label class="flex items-center gap-2 cursor-pointer mb-3">
                                    <input type="checkbox" id="useAdvanceCheck" class="w-4 h-4 text-indigo-600 rounded">
                                    <span class="text-sm font-semibold text-indigo-700">Invoice এ Advance Use করবো</span>
                                </label>

                                <!-- Amount + summary — checkbox check হলে দেখাবে -->
                                <div id="advanceUseSection" class="hidden border-t border-indigo-200 pt-3">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                        <div>
                                            <label class="block text-xs font-medium text-indigo-700 mb-1">
                                                Use Amount <span class="text-indigo-400">(max: <span id="advanceMaxDisplay">৳0</span>)</span>
                                            </label>
                                            <input type="number" id="advanceUseAmount" step="0.01" min="0"
                                                placeholder="0.00"
                                                class="w-full px-3 py-2 border border-indigo-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-400 bg-white font-semibold">
                                        </div>
                                        <div class="flex flex-col justify-center bg-white rounded-lg border border-indigo-100 p-3 text-center">
                                            <p class="text-xs text-gray-400">Invoice Total</p>
                                            <p class="text-base font-bold text-gray-800" id="advTotalDisplay">৳0.00</p>
                                            <p class="text-xs text-indigo-600 mt-1">Used: <span id="advUsedDisplay">৳0.00</span></p>
                                        </div>
                                        <div class="flex flex-col justify-center bg-green-50 rounded-lg border border-green-200 p-3 text-center">
                                            <p class="text-xs text-gray-400">Remaining Due</p>
                                            <p class="text-xl font-bold text-green-700" id="advNetDueDisplay">৳0.00</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Add Advance Inline Form — button click হলে দেখাবে -->
                            <div id="addAdvanceForm" class="hidden border-t border-indigo-200 pt-3 mt-3">
                                <p class="text-xs font-semibold text-indigo-700 mb-2">
                                    <i class="fas fa-plus-circle mr-1"></i> Advance Balance Add
                                </p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs text-gray-600 mb-1">Amount ৳</label>
                                        <input type="number" id="advAddAmount" step="0.01" min="0.01"
                                            placeholder="0.00"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-400">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-600 mb-1">Date</label>
                                        <input type="date" id="advAddDate"
                                            value="<?php echo date('Y-m-d'); ?>"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-400">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-600 mb-1">Deposit Account</label>
                                        <select id="advAddAccount"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-400">
                                            <option value="">-- Select Account --</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-600 mb-1">Particular</label>
                                        <input type="text" id="advAddParticular"
                                            placeholder="Advance payment note..."
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-400">
                                    </div>
                                </div>
                                <div class="flex gap-2 mt-3">
                                    <button type="button" onclick="saveAdvanceBalance()"
                                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg flex items-center gap-2">
                                        <i class="fas fa-save"></i> Save Advance
                                    </button>
                                    <button type="button" onclick="closeAddAdvanceForm()"
                                        class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm rounded-lg">
                                        Cancel
                                    </button>
                                </div>
                            </div>

                            <input type="hidden" name="use_advance" id="useAdvanceHidden" value="0">
                            <input type="hidden" name="advance_amount" id="advanceAmountInput" value="0">
                        </div>

                        <!-- Total Calculation -->
                        <div class="form-card">
                            <h3 class="text-lg font-semibold text-gray-800 mb-6 flex items-center gap-2">
                                <i class="fas fa-calculator"></i> Total Calculation
                            </h3>
                            
                            <div class="total-grid">
                                <div class="total-item">
                                    <div class="total-label">
                                        <i class="fas fa-receipt"></i> Total Amount
                                    </div>
                                    <div class="total-value" id="total_amount_display">0.00</div>
                                    <input type="hidden" name="total_amount" id="total_amount" value="0">
                                </div>
                                <div class="total-item">
                                    <div class="total-label">
                                        <i class="fas fa-money-bill-wave"></i> Paid Amount
                                    </div>
                                    <input type="number" name="paid_amount" id="paid_amount" 
                                           class="w-full border border-gray-300 rounded-lg px-4 py-3 text-center text-2xl font-bold focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all"
                                           value="0" min="0" oninput="calculateDue()">
                                </div>
                                <div class="total-item">
                                    <div class="total-label">
                                        <i class="fas fa-clock"></i> Due Amount
                                    </div>
                                    <div class="total-value due" id="due_amount_display">0.00</div>
                                    <input type="hidden" name="due_amount" id="due_amount" value="0">
                                </div>
                            </div>
                        </div>

                        <!-- Vendor Information -->
                        <div class="form-card">
                            <h3 class="text-lg font-semibold text-gray-800 mb-6 flex items-center gap-2">
                                <i class="fas fa-building"></i> Vendor Information
                            </h3>
                            
                            <div class="readonly-field">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
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
                                <div class="mt-6">
                                    <div class="readonly-label">Address</div>
                                    <div class="readonly-value" id="vendor_address">Loading...</div>
                                </div>
                            </div>
                        </div>

                        <!-- Bank/MFS Information -->
                        <div class="form-card">
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                                <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                                    <i class="fas fa-university"></i> Bank / MFS Information
                                </h3>
                                <button type="button" class="btn-primary py-2.5 px-4 flex items-center gap-2"
                                        onclick="openBankMfsModal()">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                            </div>
                            
                            <div id="bank_mfs_display" class="bank-mfs-container space-y-6">
                                <!-- Bank and MFS info will be loaded here -->
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
                        <div class="footer-actions">
                            <div class="flex flex-col sm:flex-row justify-between items-center gap-6">
                                <button type="button" class="btn-danger py-3 px-6 flex items-center gap-2"
                                        onclick="clearForm()">
                                    <i class="fas fa-trash-alt"></i> Clear Form
                                </button>
                                <button type="submit" class="btn-success py-3 px-8 flex items-center gap-2">
                                    <i class="fas fa-save"></i> Save Invoice
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- Bank/MFS Edit Modal -->
    <div id="bankMfsModal" class="modal-overlay hidden">
        <div class="modal">
            <div class="modal-header">
                <h3 class="flex items-center gap-2">
                    <i class="fas fa-edit"></i> Edit Bank / MFS Information
                </h3>
                <button type="button" class="modal-close" onclick="closeBankMfsModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <!-- Bank Information -->
                <div class="section-header">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-university"></i> Bank Information
                    </div>
                    <button type="button" class="btn-primary btn-small flex items-center gap-2"
                            onclick="addBankField()">
                        <i class="fas fa-plus"></i> Add Bank
                    </button>
                </div>
                <div id="bank_fields" class="space-y-6">
                    <!-- Bank fields will be added here -->
                </div>

                <!-- MFS Information -->
                <div class="section-header">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-mobile-alt"></i> MFS Information
                    </div>
                    <button type="button" class="btn-primary btn-small flex items-center gap-2"
                            onclick="addMfsField()">
                        <i class="fas fa-plus"></i> Add MFS
                    </button>
                </div>
                <div id="mfs_fields" class="space-y-6">
                    <!-- MFS fields will be added here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-outline py-2.5 px-5 flex items-center gap-2"
                        onclick="closeBankMfsModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="btn-primary py-2.5 px-6 flex items-center gap-2"
                        onclick="saveBankMfsData()">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </div>
    </div>

    <!-- Task Group Template (Hidden) -->
    <template id="taskGroupTemplate">
        <div class="task-group-card border rounded-lg p-4 bg-gray-50">
            <div class="flex justify-between items-center mb-4">
                <h5 class="font-medium text-gray-700">
                    <i class="fas fa-tasks mr-2"></i>
                    <span class="task-group-title">Task Group</span>
                    <span class="group-number">#1</span>
                </h5>
                <button type="button" 
                        class="remove-task-group-btn text-red-600 hover:text-red-800 text-sm">
                    <i class="fas fa-times"></i> Remove Group
                </button>
            </div>
            
            <div class="task-selection-container">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Select Tasks (multiple allowed)
                </label>
                <div class="task-checkboxes space-y-2">
                    <!-- Task checkboxes will be populated here -->
                </div>
            </div>
        </div>
    </template>

    <?php include '../elements/floating-menus.php'; ?>

    <script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>
    
    <script>
        const IP_PATH = '<?php echo htmlspecialchars($base_ip_path); ?>';
        const STORAGE_KEY = 'invoice_create_draft';
        const BANK_MFS_KEY = 'bank_mfs_data';
        const API_INVOICE_STORE = `${IP_PATH}/api/invoices/store.php`;
        const FETCH_API_ALL_WORK = `${IP_PATH}/api/com_works/client-works.php`;
        const FETCH_API_ALL_TASK = `${IP_PATH}/api/old_tasks/tasks-for-work.php`;
        const GET_ALL_CLIENTS_API = `${IP_PATH}/api/clients/all-clients.php`;
        const GET_FINANCIAL_STATEMENT_API = `${IP_PATH}/api/financial_entries/client-task-statement.php`;
        const INDEX_INVOICE = `${IP_PATH}/pages/index-invoice.php`;
    
        // Global state management for work items
        const workItemStates = new Map();
        
        // ========== GLOBAL CLIENT STATE MANAGEMENT ==========
        let globalClientState = {
            clientId: null,
            clientName: 'Not selected',
            clientData: null,
            onChangeCallbacks: []
        };

        // Function to update global client state
        function updateGlobalClientState(clientId, clientName, clientData = null) {
            globalClientState.clientId = clientId;
            globalClientState.clientName = clientName || 'Not selected';
            globalClientState.clientData = clientData;
            
            // Update form inputs
            document.getElementById('selectedClientId').value = clientId;
            document.getElementById('selectedClientName').value = clientName;
            
            // Update display
            const displayElement = document.getElementById('selectedClientDisplay');
            if (displayElement) {
                if (clientId) {
                    displayElement.innerHTML = `<span class="font-medium text-green-600">${clientName}</span> (ID: ${clientId})`;
                } else {
                    displayElement.textContent = 'No client selected';
                }
            }
            
            // console.log('Global client state updated:', globalClientState);
            
            // Notify all registered callbacks
            globalClientState.onChangeCallbacks.forEach(callback => {
                if (typeof callback === 'function') {
                    callback(clientId, clientName, clientData);
                }
            });
        }

        // Function to register for client change notifications
        function registerForClientChanges(callback) {
            if (typeof callback === 'function') {
                globalClientState.onChangeCallbacks.push(callback);
            }
            // Return unregister function
            return () => {
                const index = globalClientState.onChangeCallbacks.indexOf(callback);
                if (index > -1) {
                    globalClientState.onChangeCallbacks.splice(index, 1);
                }
            };
        }
    
        /* ========== ADVANCE BALANCE SYSTEM ========== */
        let advanceBalance = 0;

        // Client select হলে call হবে — balance থাকলেও না থাকলেও banner দেখাবে
        async function checkClientAdvance(clientId) {
            const banner = document.getElementById('advanceBanner');
            if (!clientId) {
                if (banner) banner.classList.add('hidden');
                advanceBalance = 0;
                return;
            }

            try {
                // Balance = SUM(credit rt=6) - SUM(debit rt=6)
                const [crRes, drRes] = await Promise.all([
                    fetch(`${IP_PATH}/api/financial_entries/fin-entries.php?id=${clientId}&type=credit&related_type=6`),
                    fetch(`${IP_PATH}/api/financial_entries/fin-entries.php?id=${clientId}&type=debit&related_type=6`)
                ]);
                const crData = await crRes.json();
                const drData = await drRes.json();
                if (!crData.success) return;

                const totalIn  = (crData.finStmts || []).reduce((s,e) => s + (parseFloat(e.amount)||0), 0);
                const totalOut = (drData.finStmts || []).reduce((s,e) => s + (parseFloat(e.amount)||0), 0);
                advanceBalance = Math.max(0, totalIn - totalOut);

                // Balance যাই হোক — banner দেখাবে
                if (banner) banner.classList.remove('hidden');
                renderAdvanceBannerState();
                loadAdvanceAccounts(); // account dropdown load

            } catch(e) {
                console.error('Advance check failed:', e);
            }
        }

        // Balance অনুযায়ী banner state render করে
        function renderAdvanceBannerState() {
            const balEl        = document.getElementById('advanceBalanceDisplay');
            const noBalSection = document.getElementById('advNoBalanceSection');
            const hasBalSection= document.getElementById('advHasBalanceSection');

            if (balEl) balEl.textContent = '৳' + advanceBalance.toFixed(2);

            if (advanceBalance > 0.01) {
                // Balance আছে — use section দেখাবে
                if (noBalSection)  noBalSection.classList.add('hidden');
                if (hasBalSection) hasBalSection.classList.remove('hidden');
                updateAdvanceDisplay();
            } else {
                // Balance নেই — add button দেখাবে
                if (noBalSection)  noBalSection.classList.remove('hidden');
                if (hasBalSection) hasBalSection.classList.add('hidden');
                // Checkbox uncheck করি
                const cb = document.getElementById('useAdvanceCheck');
                if (cb) cb.checked = false;
                document.getElementById('advanceUseSection')?.classList.add('hidden');
                // Hidden fields reset
                document.getElementById('useAdvanceHidden').value  = '0';
                document.getElementById('advanceAmountInput').value = '0';
                const paidEl = document.getElementById('paid_amount');
                if (paidEl) paidEl.value = '0';
                if (typeof calculateDue === 'function') calculateDue();
            }
        }

        // Advance use amount / checkbox change হলে call হয়
        function updateAdvanceDisplay() {
            const totalAmt  = parseFloat(document.getElementById('total_amount')?.value) || 0;
            // max = advance balance (total এর বেশিও দিতে পারবে — overpayment allowed)
            const maxUse    = advanceBalance;
            const useAdv    = document.getElementById('useAdvanceCheck')?.checked;
            const useInputEl= document.getElementById('advanceUseAmount');
            let   useInput  = parseFloat(useInputEl?.value) || 0;
            // Enforce max = advanceBalance only
            if (useInput > maxUse) {
                useInput = maxUse;
                if (useInputEl) useInputEl.value = maxUse.toFixed(2);
            }
            const advUsed  = useAdv ? (useInput > 0 ? useInput : maxUse) : 0;
            // Due = total - advUsed, কিন্তু 0 এর নিচে যাবে না
            // Overpayment হলে due = 0, invoice fully paid
            const netDue   = Math.max(0, totalAmt - advUsed);
            const overpaid = Math.max(0, advUsed - totalAmt);

            // Display update
            const maxEl      = document.getElementById('advanceMaxDisplay');
            const totalDisp  = document.getElementById('advTotalDisplay');
            const usedDisp   = document.getElementById('advUsedDisplay');
            const netDueDisp = document.getElementById('advNetDueDisplay');

            if (maxEl)      maxEl.textContent      = '৳' + maxUse.toFixed(2);
            if (totalDisp)  totalDisp.textContent  = '৳' + totalAmt.toFixed(2);
            if (usedDisp)   usedDisp.textContent   = '৳' + advUsed.toFixed(2);
            if (netDueDisp) {
                if (overpaid > 0.01) {
                    netDueDisp.textContent = '৳0.00';
                    netDueDisp.title       = `Overpaid ৳${overpaid.toFixed(2)} — advance থেকে কাটা হবে`;
                } else {
                    netDueDisp.textContent = '৳' + netDue.toFixed(2);
                    netDueDisp.title       = '';
                }
            }
            // Overpaid info
            let overpaidEl = document.getElementById('advOverpaidInfo');
            if (!overpaidEl) {
                overpaidEl = document.createElement('p');
                overpaidEl.id        = 'advOverpaidInfo';
                overpaidEl.className = 'text-xs text-orange-600 mt-2 font-medium';
                document.getElementById('advanceUseSection')?.appendChild(overpaidEl);
            }
            overpaidEl.textContent = overpaid > 0.01
                ? `⚠ ৳${overpaid.toFixed(2)} বেশি advance থেকে কাটা হবে — invoice fully paid, বাড়তি টাকা Baksheesh হিসেবে record হবে`
                : '';

            // Hidden fields
            const hiddenUse = document.getElementById('useAdvanceHidden');
            const advInput  = document.getElementById('advanceAmountInput');
            const paidEl    = document.getElementById('paid_amount');
            const dueEl     = document.getElementById('due_amount');

            if (useAdv && advUsed > 0) {
                if (hiddenUse) hiddenUse.value = '1';
                if (advInput)  advInput.value  = advUsed.toFixed(2);
                if (paidEl)    paidEl.value    = advUsed.toFixed(2);
                if (dueEl)     dueEl.value     = netDue.toFixed(2);
                // due display সরাসরি update
                const dueDisplay = document.getElementById('due_amount_display');
                if (dueDisplay) {
                    dueDisplay.innerText = netDue.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
                    dueDisplay.style.color = netDue <= 0 ? '#16a34a' : '';
                }
            } else {
                if (hiddenUse) hiddenUse.value = '0';
                if (advInput)  advInput.value  = '0';
                if (paidEl)    paidEl.value    = '0';
                if (typeof calculateDue === 'function') calculateDue();
            }
        }

        /* ===== Add Advance Form ===== */
        function openAddAdvanceForm() {
            document.getElementById('addAdvanceForm')?.classList.remove('hidden');
        }

        function closeAddAdvanceForm() {
            document.getElementById('addAdvanceForm')?.classList.add('hidden');
            document.getElementById('advAddAmount').value     = '';
            document.getElementById('advAddParticular').value = '';
        }

        async function loadAdvanceAccounts() {
            const sel = document.getElementById('advAddAccount');
            if (!sel || sel.options.length > 1) return; // already loaded
            try {
                const r    = await fetch(`${IP_PATH}/api/accounts/all-accounts.php`);
                const data = await r.json();
                (data.accounts || []).forEach(acc => {
                    const opt = document.createElement('option');
                    opt.value       = acc.sys_id;
                    opt.textContent = acc.acc_name;
                    sel.appendChild(opt);
                });
            } catch(e) { console.error('Account load failed:', e); }
        }

        async function saveAdvanceBalance() {
            const amount     = parseFloat(document.getElementById('advAddAmount')?.value);
            const date       = document.getElementById('advAddDate')?.value;
            const accountSel = document.getElementById('advAddAccount');
            const accountId  = accountSel?.value;
            const accountName= accountSel?.options[accountSel.selectedIndex]?.text || '';
            const particular = document.getElementById('advAddParticular')?.value || 'Advance Payment';
            const clientId   = globalClientState?.clientId;
            const clientName = globalClientState?.clientName || '';

            if (!amount || amount <= 0) { alert('Amount দিন'); return; }
            if (!clientId) { alert('Client select করুন'); return; }
            if (!accountId) { alert('Deposit Account select করুন'); return; }

            const btn = document.querySelector('#addAdvanceForm button');
            if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Saving...'; }

            try {
                // cl-ac-receive-store.php এ post করবো — no sale selection = advance
                const payload = {
                    clientId        : clientId,
                    clientName      : clientName,
                    amount          : amount,
                    transactionDate : date + ' ' + new Date().toTimeString().slice(0,8),
                    particular      : particular,
                    transferMethod  : 'Cash',
                    selectedSaleIds : [],   // empty = advance (rt=6)
                    withDiscount    : false,
                    discountAmount  : 0,
                    isHistorical    : 0,
                    accountId       : accountId,
                    accountName     : accountName,
                };

                const r    = await fetch(`${IP_PATH}/api/clients/cl-ac-receive-store.php`, {
                    method : 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body   : JSON.stringify(payload)
                });
                const data = await r.json();

                if (data.success) {
                    closeAddAdvanceForm();
                    // Balance reload
                    await checkClientAdvance(clientId);
                } else {
                    alert('Error: ' + (data.message || 'Failed'));
                }
            } catch(e) {
                alert('Network error');
                console.error(e);
            } finally {
                if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-save mr-1"></i> Save Advance'; }
            }
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', () => {
            // Checkbox toggle
            document.getElementById('useAdvanceCheck')?.addEventListener('change', function() {
                const section = document.getElementById('advanceUseSection');
                if (section) section.classList.toggle('hidden', !this.checked);
                if (this.checked) {
                    // Default = max usable
                    const totalAmt = parseFloat(document.getElementById('total_amount')?.value) || 0;
                    const maxUse   = Math.min(advanceBalance, totalAmt);
                    const input    = document.getElementById('advanceUseAmount');
                    if (input && !input.value) input.value = maxUse.toFixed(2);
                }
                updateAdvanceDisplay();
            });

            // Amount input live update
            document.getElementById('advanceUseAmount')?.addEventListener('input', () => {
                updateAdvanceDisplay();
            });
        });

        // Client select হলে advance check
        registerForClientChanges((clientId) => {
            checkClientAdvance(clientId);
        });

        /* ========== 1. FETCH INVOICE NUMBER FROM API ========== */
        async function fetchInvoiceNumberFromAPI() {
            try {
                document.getElementById('invoiceNoDisplay').textContent = 'Loading from API...';
                document.getElementById('invoiceNoDisplay').style.color = '#6b7280';
    
                const response = await fetch('../api/invoices/get-invoice-no.php');
                
                if (!response.ok) {
                    throw new Error(`API request failed: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.success && data.invoice_no) {
                    document.getElementById('invoiceNoDisplay').textContent = data.invoice_no;
                    document.getElementById('invoiceNoDisplay').style.color = '#10b981';
                    document.getElementById('invoiceNoInput').value = data.invoice_no;
                    
                    return data.invoice_no;
                } else {
                    throw new Error(data.message || 'Invalid response from API');
                }
            } catch (error) {
                console.error('Error fetching invoice number from API:', error);
                
                document.getElementById('invoiceNoDisplay').textContent = 'Error loading from API';
                document.getElementById('invoiceNoDisplay').style.color = '#f87171';
                document.getElementById('invoiceNoInput').value = 'ERROR-API';
                
                setTimeout(fetchInvoiceNumberFromAPI, 3000);
                
                return null;
            }
        }
    
        /* ========== 2. LOAD VENDOR DATA FROM JSON ========== */
        let vendorData = null;
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
    
                // Load bank/mfs data
                loadBankMfsData();
    
            } catch (error) {
                console.error('Error loading vendor data:', error);
                alert('Error loading vendor data.');
            }
        }
    
        /* ========== 3. BANK/MFS DATA MANAGEMENT ========== */
        function loadBankMfsData() {
            const savedData = localStorage.getItem(BANK_MFS_KEY);
        
            if (savedData) {
                displayBankMfsData(JSON.parse(savedData));
            } else if (vendorData) {
                const bankMfsData = {
                    banks: vendorData.banks || [],
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
        }
    
        // function storeBankMfsInForm(data) {
        //     // প্রথমে সব পুরোনো fields রিমুভ করুন
        //     document.querySelectorAll('[data-bank-mfs-field]').forEach(field => field.remove());
            
        //     const form = document.getElementById('invoiceForm');
            
        //     // Debug: দেখুন কি ডেটা আসছে
        //     console.log('Storing Bank/MFS data:', data);
            
        //     // Clear any existing bank/mfs inputs
        //     document.querySelectorAll('input[name^="bank["], input[name^="mfs["]').forEach(input => input.remove());
            
        //     // Bank data প্রসেস করুন
        //     if (data.banks && Array.isArray(data.banks)) {
        //         data.banks.forEach((bank, index) => {
        //             if (bank && typeof bank === 'object') {
        //                 // প্রতিটি বাঙ্কের জন্য আলাদা hidden inputs তৈরি করুন
        //                 Object.keys(bank).forEach(key => {
        //                     if (bank[key]) {
        //                         const input = document.createElement('input');
        //                         input.type = 'hidden';
        //                         input.name = `bank[${index}][${key}]`;
        //                         input.value = bank[key];
        //                         input.setAttribute('data-bank-mfs-field', 'true');
        //                         form.appendChild(input);
        //                     }
        //                 });
        //             }
        //         });
        //     }
            
        //     // MFS data প্রসেস করুন
        //     if (data.mfs && Array.isArray(data.mfs)) {
        //         data.mfs.forEach((mfs, index) => {
        //             if (mfs && typeof mfs === 'object') {
        //                 // Regular MFS fields
        //                 Object.keys(mfs).forEach(key => {
        //                     if (key === 'vendor_mfs_account') {
        //                         // Handle MFS account array
        //                         if (Array.isArray(mfs[key])) {
        //                             mfs[key].forEach((account, accIndex) => {
        //                                 if (account && account.trim() !== '') {
        //                                     const input = document.createElement('input');
        //                                     input.type = 'hidden';
        //                                     input.name = `mfs[${index}][vendor_mfs_account][]`;
        //                                     input.value = account;
        //                                     input.setAttribute('data-bank-mfs-field', 'true');
        //                                     form.appendChild(input);
        //                                 }
        //                             });
        //                         } else if (mfs[key] && mfs[key].trim() !== '') {
        //                             const input = document.createElement('input');
        //                             input.type = 'hidden';
        //                             input.name = `mfs[${index}][vendor_mfs_account][]`;
        //                             input.value = mfs[key];
        //                             input.setAttribute('data-bank-mfs-field', 'true');
        //                             form.appendChild(input);
        //                         }
        //                     } else if (mfs[key] && mfs[key].trim() !== '') {
        //                         // Other MFS fields
        //                         const input = document.createElement('input');
        //                         input.type = 'hidden';
        //                         input.name = `mfs[${index}][${key}]`;
        //                         input.value = mfs[key];
        //                         input.setAttribute('data-bank-mfs-field', 'true');
        //                         form.appendChild(input);
        //                     }
        //                 });
        //             }
        //         });
        //     }
            
        //     console.log('Added bank/mfs hidden inputs to form');
        // }
    
        /* ========== CUSTOM WORK ITEM CALCULATION ========== */
        function updateCustomWorkItemCalculation(workItemElement) {
            const qty = parseFloat(workItemElement.querySelector('.work-qty').value) || 0;
            const rate = parseFloat(workItemElement.querySelector('.work-rate').value) || 0;
            const amount = qty * rate;
            
            const amountText = workItemElement.querySelector('.amount_text');
            const amountInput = workItemElement.querySelector('.amount-input');
            
            if (amountText) {
                amountText.textContent = amount.toFixed(2);
            }
            
            if (amountInput) {
                amountInput.value = amount.toFixed(2);
            }
            
            calculateTotal();
        }
        
        /* ========== CLIENT SEARCH COMPONENT ========== */
        let clientsData = [];
        const clientInput = document.getElementById('clientInput');
        const clientDropdown = document.getElementById('clientDropdown');
        const clientContainer = document.getElementById('clientSearchContainer');

        /* Load clients */
        function loadClients(){
            fetch(GET_ALL_CLIENTS_API)
                .then(res => res.json())
                .then(data => {
                    clientsData = Array.isArray(data.clients) ? data.clients : [];
                    // console.log(clientsData);
                })
                .catch(() => clientsData = []);
            
        }
        
        loadClients();

        /* Typing */
        let clientTypingTimer;
        clientInput.addEventListener('input', () => {
            clearTimeout(clientTypingTimer);
            clientTypingTimer = setTimeout(() => {
                const value = clientInput.value.toLowerCase().trim();

                const filtered = value === ''
                    ? clientsData
                    : clientsData.filter(c =>
                        c.name?.toLowerCase().includes(value) ||
                        c.sys_id?.toLowerCase().includes(value)
                    );

                renderClientDropdown(filtered);
                clientDropdown.classList.remove('hidden');
            }, 300);
        });

        /* Focus */
        clientInput.addEventListener('focus', () => {
            renderClientDropdown(clientsData);
            clientDropdown.classList.remove('hidden');
        });

        function renderClientDropdown(list) {
            clientDropdown.innerHTML = '';

            if (!list.length) {
                clientDropdown.innerHTML =
                    `<li class="px-4 py-3 text-center text-gray-500">No clients found</li>`;
                return;
            }

            list.forEach(client => {
                let phone = '';
                try {
                    if (client.phone?.startsWith('{')) {
                        phone = JSON.parse(client.phone).primary_no ?? '';
                    }
                } catch {}

                const li = document.createElement('li');
                li.className =
                    "px-4 py-3 cursor-pointer hover:bg-purple-50 border-b last:border-b-0 client-option";

                li.innerHTML = `
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-purple-600 rounded-full text-white flex items-center justify-center font-semibold">
                            ${client.name?.charAt(0).toUpperCase() ?? 'C'}
                        </div>
                        <div class="ml-3 flex-1">
                            <div class="font-medium">${client.name}</div>
                            <div class="text-xs text-gray-500 flex gap-2">
                                <span>ID: ${client.sys_id}</span>
                                ${phone ? `<span>📞 ${phone}</span>` : ''}
                            </div>
                        </div>
                    </div>
                `;

                li.onclick = () => {
                    selectClient(client);
                };

                clientDropdown.appendChild(li);
            });
        }

        function selectClient(client) {
            // Update search input
            clientInput.value = `${client.sys_id} | ${client.name}`;
            
            // Update global state
            updateGlobalClientState(client.sys_id, client.name, client);
            
            // Hide dropdown
            clientDropdown.classList.add('hidden');
            
            // console.log('Client selected:', client);
        }

        /* Outside click [Tarek Vai told me to stop this!] */
        // document.addEventListener('click', e => {
        //     if (!clientContainer.contains(e.target)) {
        //         clientDropdown.classList.add('hidden');
        //     }
        // });

        /* ========== UPDATED SYSTEM WORK ITEM CLIENT INFO UPDATE ========== */
        function updateSystemWorkItemClientInfo(workItemElement) {
            const systemForm = workItemElement.querySelector('.system-work-container');
            if (!systemForm) return;
            
            const clientNameSpan = systemForm.querySelector('.selected-client-name');
            const warning = systemForm.querySelector('.client-change-warning');
            const workSelect = systemForm.querySelector('.system-work-select');
            
            if (globalClientState.clientId) {
                // Client is selected
                clientNameSpan.textContent = globalClientState.clientName;
                clientNameSpan.style.color = '#3b82f6';
                
                if (warning) warning.style.display = 'none';
                
                // Enable and populate work select
                if (workSelect) {
                    workSelect.disabled = false;
                    
                    // If already populated, don't reset
                    if (workSelect.innerHTML.includes('Loading') || 
                        workSelect.innerHTML.includes('Select a Client')) {
                        workSelect.innerHTML = '<option value="">Loading works...</option>';
                        fetchWorksForClient(globalClientState.clientId, systemForm);
                    }
                }
            } else {
                // No client selected
                clientNameSpan.textContent = 'Please select a client first';
                clientNameSpan.style.color = '#ef4444';
                
                if (warning) warning.style.display = 'block';
                
                if (workSelect) {
                    workSelect.innerHTML = '<option value="">-- Select a Client First --</option>';
                    workSelect.disabled = true;
                }
            }
        }
        
        /* ========== UPDATED WORK ITEM INITIALIZATION ========== */
        function initializeWorkItemEventListeners(workItemElement) {
            const customBtn = workItemElement.querySelector('.custom-mode-btn');
            const systemBtn = workItemElement.querySelector('.system-mode-btn');
            const customForm = workItemElement.querySelector('.custom-form-container');
            const systemForm = workItemElement.querySelector('.system-work-container');
            const removeBtn = workItemElement.querySelector('.remove-work-btn');
            
            // Set initial state
            const state = {
                mode: 'custom',
                clientId: null,
                selectedWork: null,
                availableTasks: [],
                taskGroups: new Map(),
                updateClientInfo: null
            };
            
            const workItemId = 'workitem_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            workItemStates.set(workItemId, state);
            workItemElement.dataset.workItemId = workItemId;
            
            // Custom mode inputs event listeners
            const qtyInput = workItemElement.querySelector('.work-qty');
            const rateInput = workItemElement.querySelector('.work-rate');
            
            qtyInput.addEventListener('input', function() {
                updateCustomWorkItemCalculation(workItemElement);
            });
            
            rateInput.addEventListener('input', function() {
                updateCustomWorkItemCalculation(workItemElement);
            });
            
            // Mode toggle - CUSTOM MODE
            customBtn.addEventListener('click', function() {
                // Switch to custom mode UI
                customBtn.classList.add('bg-green-600', 'text-white', 'border-green-600');
                customBtn.classList.remove('bg-white', 'text-gray-700', 'border-gray-300', 'hover:bg-gray-50');
                
                systemBtn.classList.remove('bg-green-600', 'text-white', 'border-green-600');
                systemBtn.classList.add('bg-white', 'text-gray-700', 'border-gray-300', 'hover:bg-gray-50');
                
                // Show custom form, hide system form
                customForm.style.display = 'block';
                systemForm.style.display = 'none';
                
                // ENABLE custom form inputs for submission
                customForm.querySelectorAll('input, textarea, select').forEach(input => {
                    input.disabled = false;
                    // Ensure they have names for form submission
                    if (input.hasAttribute('data-original-name')) {
                        input.name = input.getAttribute('data-original-name');
                        input.removeAttribute('data-original-name');
                    }
                });
                
                // DISABLE/REMOVE system form inputs from submission
                const generatedItems = systemForm.querySelectorAll('.generated-item input, .generated-item textarea, .generated-item select');
                generatedItems.forEach(input => {
                    input.disabled = true;
                    // Store original name and remove from submission
                    if (input.name && !input.hasAttribute('data-original-name')) {
                        input.setAttribute('data-original-name', input.name);
                    }
                    input.removeAttribute('name'); // Remove name so it's not submitted
                });
                
                // Clear system generated items display (optional)
                const generatedContainer = systemForm.querySelector('.generated-items-list');
                if (generatedContainer && state.mode === 'system') {
                    generatedContainer.innerHTML = `
                        <div class="text-center py-6 text-gray-500">
                            <i class="fas fa-inbox text-3xl mb-3"></i>
                            <p>No tasks selected yet. Select tasks above to generate work items.</p>
                        </div>
                    `;
                }
                
                // Clear system work selection
                const workSelect = systemForm.querySelector('.system-work-select');
                if (workSelect) {
                    workSelect.value = '';
                    workSelect.innerHTML = '<option value="">-- Select a Work --</option>';
                }
                
                // Clear task groups
                const groupsContainer = systemForm.querySelector('.task-groups-container');
                if (groupsContainer) {
                    groupsContainer.innerHTML = `
                        <div class="text-center py-4 text-gray-500">
                            <i class="fas fa-info-circle text-lg mb-2"></i>
                            <p>Select a client and then select a work to see tasks</p>
                        </div>
                    `;
                }
                
                // Clear task groups state
                state.taskGroups.clear();
                
                state.mode = 'custom';
                
                // Recalculate total (since we removed system items)
                calculateTotal();
            });
            
            // Mode toggle - SYSTEM MODE
            systemBtn.addEventListener('click', function() {
                // Switch to system mode UI
                systemBtn.classList.add('bg-green-600', 'text-white', 'border-green-600');
                systemBtn.classList.remove('bg-white', 'text-gray-700', 'border-gray-300', 'hover:bg-gray-50');
                
                customBtn.classList.remove('bg-green-600', 'text-white', 'border-green-600');
                customBtn.classList.add('bg-white', 'text-gray-700', 'border-gray-300', 'hover:bg-gray-50');
                
                // Show system form, hide custom form
                customForm.style.display = 'none';
                systemForm.style.display = 'block';
                
                // DISABLE/REMOVE custom form inputs from submission
                customForm.querySelectorAll('input, textarea, select').forEach(input => {
                    input.disabled = true;
                    // Store original name and remove from submission
                    if (input.name && !input.hasAttribute('data-original-name')) {
                        input.setAttribute('data-original-name', input.name);
                    }
                    input.removeAttribute('name'); // Remove name so it's not submitted
                });
                
                // ENABLE system-generated inputs for submission
                const generatedItems = systemForm.querySelectorAll('.generated-item input, .generated-item textarea, .generated-item select');
                generatedItems.forEach(input => {
                    input.disabled = false;
                    // Restore original name if stored
                    if (input.hasAttribute('data-original-name')) {
                        input.name = input.getAttribute('data-original-name');
                        input.removeAttribute('data-original-name');
                    }
                });
                
                state.mode = 'system';
                
                // Immediately update client info when switching to system mode
                updateSystemWorkItemClientInfo(workItemElement);
            });
            
            // Create a callback function for this work item
            const clientUpdateCallback = () => {
                if (state.mode === 'system') {
                    updateSystemWorkItemClientInfo(workItemElement);
                }
            };
            
            // Register for client changes
            const unregister = registerForClientChanges(clientUpdateCallback);
            state.updateClientInfo = clientUpdateCallback;
            state.unregister = unregister;
            
            // Remove button - cleanup
            removeBtn.addEventListener('click', function() {
                if (confirm('Are you sure you want to remove this work item?')) {
                    // Unregister from client changes
                    if (state.unregister) {
                        state.unregister();
                    }
                    
                    workItemStates.delete(workItemId);
                    workItemElement.remove();
                    calculateTotal();
                }
            });
            
            // Add task group button
            const addGroupBtn = workItemElement.querySelector('.add-task-group-btn');
            if (addGroupBtn) {
                addGroupBtn.addEventListener('click', function() {
                    if (state.selectedWork && state.availableTasks.length > 0) {
                        addTaskGroup(workItemElement, state.availableTasks);
                    } else {
                        alert('Please select a work first');
                    }
                });
            }
            
            return workItemId;
        }
    
        /* ========== 6. FETCH WORKS FOR CLIENT ========== */
        async function fetchWorksForClient(clientId, systemContainer) {
            try {
                const workSelect = systemContainer.querySelector('.system-work-select');
                if (!workSelect) return;
                
                // If no client ID, disable and return
                if (!clientId) {
                    workSelect.innerHTML = '<option value="">-- Select a Client First --</option>';
                    workSelect.disabled = true;
                    return;
                }
                
                workSelect.innerHTML = '<option value="">Loading works...</option>';
                workSelect.disabled = true;
                
                const response = await fetch(`${FETCH_API_ALL_WORK}?client_id=${clientId}`);
                const data = await response.json();
                
                if (data.success && data.works) {
                    workSelect.innerHTML = '<option value="">-- Select a Work --</option>';
                    data.works.forEach(work => {
                        const option = document.createElement('option');
                        option.value = work.sys_id;
                        option.textContent = work.title || work.sys_id;
                        option.dataset.workData = JSON.stringify(work);
                        workSelect.appendChild(option);
                    });
                    workSelect.disabled = false;
                    
                    // Remove existing event listeners first
                    const newWorkSelect = workSelect.cloneNode(true);
                    workSelect.parentNode.replaceChild(newWorkSelect, workSelect);
                    
                    // Add new event listener
                    newWorkSelect.addEventListener('change', function() {
                        const workItemElement = systemContainer.closest('.item-card');
                        const workId = this.value;
                        const workData = this.options[this.selectedIndex]?.dataset.workData;
                        
                        if (workId) {
                            handleWorkSelection(workId, workItemElement, workData);
                        } else {
                            // Clear if no work selected
                            const workItemId = workItemElement.dataset.workItemId;
                            const state = workItemStates.get(workItemId);
                            if (state) {
                                state.selectedWork = null;
                                state.availableTasks = [];
                                state.taskGroups.clear();
                                clearGeneratedWorkItems(workItemElement);
                                
                                // Reset task groups container
                                const groupsContainer = systemContainer.querySelector('.task-groups-container');
                                if (groupsContainer) {
                                    groupsContainer.innerHTML = '<div class="text-center py-4 text-gray-500"><i class="fas fa-info-circle text-lg mb-2"></i><p>Select a work to see tasks</p></div>';
                                }
                            }
                        }
                    });
                } else {
                    workSelect.innerHTML = '<option value="">No works found</option>';
                    workSelect.disabled = false;
                }
            } catch (error) {
                console.error('Error fetching works:', error);
                const workSelect = systemContainer.querySelector('.system-work-select');
                if (workSelect) {
                    workSelect.innerHTML = '<option value="">Error loading works</option>';
                    workSelect.disabled = false;
                }
            }
        }
    
        /* ========== 7. HANDLE WORK SELECTION ========== */
        async function handleWorkSelection(workId, workItemElement, workDataJson) {
            const workItemId = workItemElement.dataset.workItemId;
            const state = workItemStates.get(workItemId);
            const systemContainer = workItemElement.querySelector('.system-work-container');
            
            if (!workId) {
                // Clear everything
                state.selectedWork = null;
                state.availableTasks = [];
                state.taskGroups.clear();
                clearGeneratedWorkItems(workItemElement);
                return;
            }
            
            // Parse work data
            let workData = {};
            try {
                workData = workDataJson ? JSON.parse(workDataJson) : {};
            } catch (e) {
                console.error('Error parsing work data:', e);
                workData = {};
            }
            
            state.selectedWork = {
                id: workId,
                data: workData
            };
            
            // console.log('Work selected:', state.selectedWork);
            
            // Hide client change warning
            const warning = systemContainer.querySelector('.client-change-warning');
            if (warning) {
                warning.style.display = 'none';
            }
            
            // Clear existing task groups
            const groupsContainer = systemContainer.querySelector('.task-groups-container');
            if (groupsContainer) {
                groupsContainer.innerHTML = '<div class="text-center py-4 text-gray-500"><i class="fas fa-spinner fa-spin text-lg mb-2"></i><p>Loading tasks...</p></div>';
            }
            
            // Fetch tasks for this work
            state.availableTasks = await fetchTasksForWork(workId);
            // console.log('Available tasks:', state.availableTasks);
            
            // Clear task groups state
            state.taskGroups.clear();
            
            // Clear groups container
            if (groupsContainer) {
                groupsContainer.innerHTML = '';
            }
            
            // Add initial task group if tasks found
            if (state.availableTasks && state.availableTasks.length > 0) {
                addTaskGroup(workItemElement, state.availableTasks);
            } else {
                // Show message if no tasks
                if (groupsContainer) {
                    groupsContainer.innerHTML = '<p class="text-gray-500 text-center py-4">No tasks found for this work.</p>';
                }
            }
            
            // Clear any previously generated items
            clearGeneratedWorkItems(workItemElement);
        }
    
        /* ========== 8. FETCH TASKS FOR WORK ========== */
        async function fetchTasksForWork(workId) {
            try {
                const response = await fetch(`${FETCH_API_ALL_TASK}?work_id=${workId}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();

                if (data.success && data.tasks && Array.isArray(data.tasks)) {
                    return data.tasks;
                } else {
                    console.warn('No tasks found or invalid response:', data);
                    return [];
                }
            } catch (error) {
                console.error('Error fetching tasks:', error);
                return [];
            }
        }
    
        /* ========== 9. TASK GROUP MANAGEMENT ========== */
        async function loadFinancialData(taskId) {
            try {
                // sale entries: debit, related_type=1 — সব আনি, is_invoiced badge দেখাবে
                const url = `${GET_FINANCIAL_STATEMENT_API}?id=${globalClientState.clientId}&task_id=${taskId}&type=debit&related_type=1`;
                const response = await fetch(url);
                const data = await response.json();

                if (data.success && data.finStmts && Array.isArray(data.finStmts)) {
                    // শুধু type=debit, related_type=1 enforce করি — is_invoiced সব আসবে
                    return data.finStmts.filter(e =>
                        (e.type || '').toLowerCase() === 'debit' &&
                        parseInt(e.related_type) === 1
                    );
                }
                return [];
            } catch (error) {
                console.error('Error loading financial data:', error);
                return [];
            }
        }
        
        function addTaskGroup(workItemElement, tasks) {
            const workItemId = workItemElement.dataset.workItemId;
            const state = workItemStates.get(workItemId);
            const systemContainer = workItemElement.querySelector('.system-work-container');
            const groupsContainer = systemContainer.querySelector('.task-groups-container');
            
            const groupId = 'group_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            const groupNumber = groupsContainer.children.length + 1;
            
            // Create task group from template
            const template = document.getElementById('taskGroupTemplate');
            const groupElement = template.content.cloneNode(true);
            
            // Update template content
            const groupCard = groupElement.querySelector('.task-group-card');
            groupCard.dataset.groupId = groupId;
            
            const groupNumberSpan = groupElement.querySelector('.group-number');
            groupNumberSpan.textContent = `#${groupNumber}`;
            
            // Populate task checkboxes
            const checkboxesContainer = groupElement.querySelector('.task-checkboxes');
            tasks.forEach(task => {
                const checkboxDiv = document.createElement('div');
                checkboxDiv.className = 'task-checkbox-container';
                
                // প্রথমে বেসিক HTML সেট করুন
                checkboxDiv.innerHTML = `
                    <input type="checkbox"
                           id="task_${task.sys_id}_${groupId}"
                           value="${task.sys_id}"
                           class="task-checkbox mr-2"
                           data-task-data='${JSON.stringify(task)}'>
                    <label for="task_${task.sys_id}_${groupId}" class="text-sm cursor-pointer flex-1">
                        <div class="flex items-center">
                            <span class="font-medium">${task.title || 'No Title'}</span>
                        </div>
                        <div id="fin-info-${task.sys_id}-${groupId}" class="financial-info mt-1 text-xs text-gray-600">
                            <div class="text-gray-500 italic">Loading financial data...</div>
                        </div>
                    </label>
                `;
                
                checkboxesContainer.appendChild(checkboxDiv);
                
                // ফিনান্সিয়াল ডেটা লোড করুন
                loadFinancialData(task.sys_id).then(financialStatements => {
                    const finInfoDiv = document.getElementById(`fin-info-${task.sys_id}-${groupId}`);
                    
                    if (!financialStatements || financialStatements.length === 0) {
                        finInfoDiv.innerHTML = `<div class="text-gray-400">No financial statements found</div>`;
                        return;
                    }
                    
                    // একাধিক ফিনান্সিয়াল স্টেটমেন্ট ডিসপ্লে করুন
                    let html = '';
                    
                    // মোট পরিমাণ গণনা করুন - আপনার নির্দেশ অনুযায়ী
                    const totalAmount = financialStatements.reduce((sum, stmt) => {
                        const amount = parseFloat(stmt.amount) || 0;
                        if (stmt.type === 'credit') {
                            return sum - amount; // credit হলে বিয়োগ
                        } else if (stmt.type === 'debit') {
                            return sum + amount; // debit হলে যোগ
                        } else {
                            return sum + amount; // ডিফল্ট
                        }
                    }, 0);
                    
                    // Sale entries — readonly preview (select করা যাবে না)
                    // Task checkbox select হলে সব entries একসাথে invoice এ যাবে
                    const saleTotal = financialStatements.reduce((s,e) => s+(parseFloat(e.amount)||0), 0);

                    financialStatements.forEach((stmt, index) => {
                        const amount = parseFloat(stmt.amount) || 0;
                        let qtyRate = null;
                        try { 
                            qtyRate = stmt.qty_rate ? (typeof stmt.qty_rate === 'string' ? JSON.parse(stmt.qty_rate) : stmt.qty_rate) : null; 
                        } catch(e) {}
                        
                        const qty  = qtyRate?.qty  || 1;
                        const rate = qtyRate?.rate || amount;
                        const isInvoiced = parseInt(stmt.is_invoiced || 0) === 1;
                        const isPaid = parseInt(stmt.is_paid || 0) === 1;
                    
                        // 1. Determine status configuration dynamically
                        let cardClasses = 'bg-green-50 border-green-400';
                        let textClasses = 'text-green-700';
                        let badgeHtml = '';
                        let hasBadge = false;
                    
                        if (isPaid && isInvoiced) {
                            // ১. দুটিই সত্য হলে এই লাল কার্ডটি আগে চেক হবে
                            cardClasses = 'bg-red-50 border-red-300';
                            textClasses = 'text-red-600';
                            badgeHtml = `<span class="absolute top-1 right-1 text-xs bg-red-600 text-white px-2 py-0.5 rounded-bl-md rounded-tr-md font-medium">Invoiced & Paid</span>`;
                            hasBadge = true;
                        } else if (isPaid) {
                            // ২. শুধুমাত্র পেইড হলে কমলা কার্ড
                            cardClasses = 'bg-orange-50 border-orange-300';
                            textClasses = 'text-orange-600';
                            badgeHtml = `<span class="absolute top-1 right-1 text-xs bg-orange-600 text-white px-1.5 py-0.5 rounded-bl-md rounded-tr-md font-medium">Paid</span>`;
                            hasBadge = true;
                        } else if (isInvoiced) {
                            // ৩. শুধুমাত্র ইনভয়েসড হলে বেগুনি কার্ড
                            cardClasses = 'bg-purple-50 border-purple-300';
                            textClasses = 'text-purple-600';
                            badgeHtml = `<span class="absolute top-1 right-1 text-xs bg-purple-600 text-white px-1.5 py-0.5 rounded-bl-md rounded-tr-md font-medium">Invoiced</span>`;
                            hasBadge = true;
                        } else {
                            // ৪. কোনোটিই না হলে (ডিফল্ট সবুজ কার্ড)
                            cardClasses = 'bg-green-50 border-green-400';
                            textClasses = 'text-green-700';
                            badgeHtml = '';
                            hasBadge = false;
                        }
                    
                        // 2. Append HTML clean and safely
                        html += `
                            <div class="financial-item mb-1 p-2 rounded border-l-2 relative ${cardClasses}">
                                ${badgeHtml}
                                <div class="flex justify-between items-center mb-0.5 ${hasBadge ? 'pr-28' : ''}">
                                    <span class="text-xs font-medium ${textClasses}">Sale #${index + 1}</span>
                                    <span class="${textClasses} font-semibold text-sm">৳${amount.toFixed(2)}</span>
                                </div>
                                <div class="text-xs text-gray-600">${stmt.purpose || 'N/A'}</div>
                                <div class="flex gap-3 text-xs text-gray-400 mt-0.5">
                                    <span>QTY: <b class="text-gray-600">${qty}</b></span>
                                    <span>Rate: <b class="text-gray-600">৳${parseFloat(rate).toFixed(2)}</b></span>
                                    <span>${stmt.sys_id || ''}</span>
                                </div>
                            </div>
                        `;
                    });

                    html += `
                        <div class="mt-1 pt-1 border-t border-gray-200">
                            <div class="flex justify-between text-xs font-semibold text-green-700">
                                <span>${financialStatements.length} sale entry(s)</span>
                                <span>৳${saleTotal.toFixed(2)}</span>
                            </div>
                        </div>
                    `;

                    finInfoDiv.innerHTML = html;

                    // Task data update — task select হলে সব entries একসাথে যাবে
                    if (financialStatements.length > 0) {
                        // is_invoiced=1 entries skip — শুধু uninvoiced entries invoice এ যাবে
                        const uninvoicedEntries = financialStatements.filter(s => parseInt(s.is_invoiced || 0) === 0);
                        task.saleEntries = uninvoicedEntries.map(s => {
                            let qr = null;
                            try { qr = s.qty_rate ? (typeof s.qty_rate==='string'?JSON.parse(s.qty_rate):s.qty_rate) : null; } catch(e) {}
                            return {
                                sys_id  : s.sys_id,
                                purpose : s.purpose || '',
                                amount  : parseFloat(s.amount) || 0,
                                qty     : qr?.qty  || 1,
                                rate    : qr?.rate || parseFloat(s.amount) || 0,
                            };
                        });
                        task.totalAmount = uninvoicedEntries.reduce((s,e) => s+(parseFloat(e.amount)||0), 0);
                        task.financialStatements = financialStatements; // সব (badge এর জন্য)

                        const checkbox   = checkboxDiv.querySelector('.task-checkbox');
                        checkbox.dataset.taskData = JSON.stringify(task);

                        // সব entries invoiced হলে checkbox disable করি
                        const allInvoiced = financialStatements.every(s => parseInt(s.is_invoiced || 0) === 1);
                        if (allInvoiced) {
                            checkbox.disabled = true;
                            checkbox.closest('.task-checkbox-container').style.opacity = '0.5';
                            checkbox.closest('.task-checkbox-container').style.cursor = 'not-allowed';
                        }
                    }
                });

                checkboxDiv.querySelector('.task-checkbox')
                    .addEventListener('change', () => {
                        handleTaskSelection(workItemId, groupId);
                    });
            });
            
            // Add remove button handler
            groupElement.querySelector('.remove-task-group-btn').addEventListener('click', function() {
                removeTaskGroup(workItemElement, groupId);
            });
            
            // Store empty array for this group's selected tasks
            state.taskGroups.set(groupId, []);
            
            // Append to container
            groupsContainer.appendChild(groupCard);
            
            return groupId;
        }
    
        function removeTaskGroup(workItemElement, groupId) {
            const workItemId = workItemElement.dataset.workItemId;
            const state = workItemStates.get(workItemId);
            
            if (confirm('Are you sure you want to remove this task group?')) {
                // Remove from state
                state.taskGroups.delete(groupId);
                
                // Remove from DOM
                const groupElement = workItemElement.querySelector(`[data-group-id="${groupId}"]`);
                groupElement?.remove();
                
                // Regenerate work items
                generateWorkItemsFromTasks(workItemElement);
                
                // Renumber groups
                renumberTaskGroups(workItemElement);
            }
        }
    
        function renumberTaskGroups(workItemElement) {
            const groups = workItemElement.querySelectorAll('.task-group-card');
            groups.forEach((group, index) => {
                const numberSpan = group.querySelector('.group-number');
                if (numberSpan) {
                    numberSpan.textContent = `#${index + 1}`;
                }
            });
        }
    
        /* ========== 10. TASK SELECTION HANDLER ========== */
        function handleTaskSelection(workItemId, groupId) {
            const workItemElement = document.querySelector(`[data-work-item-id="${workItemId}"]`);
            const state = workItemStates.get(workItemId);
            
            // Get selected tasks for this group
            const groupElement = workItemElement.querySelector(`[data-group-id="${groupId}"]`);
            const checkboxes = groupElement.querySelectorAll('.task-checkbox:checked');
            
            const selectedTasks = Array.from(checkboxes).map(cb => ({
                id: cb.value,
                data: JSON.parse(cb.dataset.taskData || '{}')
            }));
            
            // Update state
            state.taskGroups.set(groupId, selectedTasks);
            
            // Generate work items
            generateWorkItemsFromTasks(workItemElement);
        }
    
        /* ========== 11. GENERATE WORK ITEMS FROM TASKS ========== */
        /* ========== MODIFIED: generateWorkItemsFromTasks ========== */
        /* ========== MODIFIED: generateWorkItemsFromTasks ========== */
        // এখন প্রতিটা sale entry → আলাদা invoice item
        // fe_sys_id[] hidden field হিসেবে submit হবে → store.php তে financial_entry_ids save হবে
        function generateWorkItemsFromTasks(workItemElement) {
            const workItemId = workItemElement.dataset.workItemId;
            const state = workItemStates.get(workItemId);
            const systemContainer = workItemElement.querySelector('.system-work-container');
            const generatedContainer = systemContainer.querySelector('.generated-items-list');

            generatedContainer.innerHTML = '';

            // সব selected tasks collect
            const allTasks = [];
            let globalIndex = 0;
            state.taskGroups.forEach((tasks, groupId) => {
                tasks.forEach(task => {
                    allTasks.push({ ...task, groupId, globalIndex: globalIndex++ });
                });
            });

            if (allTasks.length === 0) {
                generatedContainer.innerHTML = `
                    <div class="text-center py-6 text-gray-500">
                        <i class="fas fa-inbox text-3xl mb-3"></i>
                        <p>No tasks selected. Select tasks above to generate work items.</p>
                    </div>`;
                return;
            }

            // প্রতিটা task এর প্রতিটা saleEntry → আলাদা invoice item
            allTasks.forEach(task => {
                const taskData    = task.data;
                const saleEntries = taskData.saleEntries || [];

                if (saleEntries.length === 0) {
                    // sale entry নেই এমন task — skip (shouldn't happen with new filter)
                    return;
                }

                // Task header
                const headerDiv = document.createElement('div');
                headerDiv.className = 'text-xs font-semibold text-gray-500 mb-1 mt-2 px-1';
                headerDiv.textContent = `Task: ${taskData.title || ''}`;
                generatedContainer.appendChild(headerDiv);

                saleEntries.forEach((entry, idx) => {
                    const itemDiv = document.createElement('div');
                    itemDiv.className = 'generated-item bg-white border border-green-200 rounded-lg p-4 mb-3';
                    itemDiv.innerHTML = `
                        <div class="flex justify-between items-center mb-3">
                            <span class="text-xs font-medium text-green-700 bg-green-50 px-2 py-1 rounded">
                                Sale #${idx + 1} · ${entry.sys_id}
                            </span>
                            <span class="text-green-700 font-bold text-lg">৳<span class="total-amount">${entry.amount.toFixed(2)}</span></span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
                            <div class="md:col-span-3">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Title</label>
                                <input type="text" name="work_title[]"
                                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                                    value="${entry.purpose}">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">QTY</label>
                                <input type="number" name="work_qty[]"
                                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm work-qty-gen"
                                    value="${entry.qty}" min="0.01" step="0.01">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Rate</label>
                                <input type="number" name="work_rate[]"
                                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm work-rate-gen"
                                    value="${entry.rate}" step="0.01">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Amount</label>
                                <input type="number" name="amount[]"
                                    class="w-full border border-green-300 bg-green-50 rounded px-3 py-2 text-sm font-semibold text-green-700 work-amount-gen"
                                    value="${entry.amount.toFixed(2)}" step="0.01" readonly>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Particular</label>
                            <textarea name="work_particular[]" rows="2"
                                class="w-full border border-gray-300 rounded px-3 py-2 text-sm">${entry.purpose}</textarea>
                        </div>
                        <!-- Hidden: financial_entry sys_id — store.php এ financial_entry_ids তে যাবে -->
                        <input type="hidden" name="fe_sys_id[]" value="${entry.sys_id}">
                    `;

                    generatedContainer.appendChild(itemDiv);

                    // qty × rate = amount live calc
                    const qtyInput    = itemDiv.querySelector('.work-qty-gen');
                    const rateInput   = itemDiv.querySelector('.work-rate-gen');
                    const amountInput = itemDiv.querySelector('.work-amount-gen');
                    const totalSpan   = itemDiv.querySelector('.total-amount');

                    const recalc = () => {
                        const qty    = parseFloat(qtyInput.value)  || 0;
                        const rate   = parseFloat(rateInput.value) || 0;
                        const amount = (qty * rate).toFixed(2);
                        amountInput.value     = amount;
                        if (totalSpan) totalSpan.textContent = amount;
                        calculateTotal();
                    };

                    qtyInput.addEventListener('input', recalc);
                    rateInput.addEventListener('input', recalc);
                });
            });

            calculateTotal();
        }
    
        /* ========== 12. CLEAR GENERATED ITEMS ========== */
        function clearGeneratedWorkItems(workItemElement) {
            const systemContainer = workItemElement.querySelector('.system-work-container');
            if (!systemContainer) return;
            
            const generatedContainer = systemContainer.querySelector('.generated-items-list');
            if (generatedContainer) {
                generatedContainer.innerHTML = '';
            }
        }
        
        /* ========== NEW: Clean up form inputs before submission ========== */
        function cleanupFormInputsBeforeSubmit() {
            // Find all work items
            document.querySelectorAll('.item-card').forEach(itemCard => {
                const workItemId = itemCard.dataset.workItemId;
                const state = workItemStates.get(workItemId);
                
                if (!state) return;
                
                if (state.mode === 'custom') {
                    // Enable custom inputs, disable system inputs
                    const customInputs = itemCard.querySelectorAll('.custom-form-container input, .custom-form-container textarea, .custom-form-container select');
                    const systemInputs = itemCard.querySelectorAll('.generated-input');
                    
                    customInputs.forEach(input => {
                        input.disabled = false;
                        if (input.hasAttribute('data-original-name')) {
                            input.name = input.getAttribute('data-original-name');
                        }
                    });
                    
                    systemInputs.forEach(input => {
                        input.disabled = true;
                        if (input.name) {
                            input.setAttribute('data-original-name', input.name);
                            input.removeAttribute('name');
                        }
                    });
                    
                } else if (state.mode === 'system') {
                    // Enable system inputs, disable custom inputs
                    const customInputs = itemCard.querySelectorAll('.custom-form-container input, .custom-form-container textarea, .custom-form-container select');
                    const systemInputs = itemCard.querySelectorAll('.generated-input');
                    
                    customInputs.forEach(input => {
                        input.disabled = true;
                        if (input.name) {
                            input.setAttribute('data-original-name', input.name);
                            input.removeAttribute('name');
                        }
                    });
                    
                    systemInputs.forEach(input => {
                        input.disabled = false;
                        if (input.hasAttribute('data-original-name')) {
                            input.name = input.getAttribute('data-original-name');
                        }
                    });
                }
            });
        }
    
        /* ========== 14. MODIFIED ADD WORK ITEM FUNCTION ========== */
        function addWorkItem(data = {}) {
            const div = document.createElement('div');
            div.className = 'item-card mb-4';
            div.innerHTML = `
                <!-- Mode Toggle Section -->
                <div class="mode-toggle-container mb-6">
                    <div class="flex items-center justify-between">
                        <label class="text-sm font-medium text-gray-700">Work Item Mode:</label>
                        <div class="flex gap-4">
                            <button type="button" 
                                    class="mode-btn custom-mode-btn px-4 py-2 rounded-lg border bg-green-600 text-white border-green-600">
                                <i class="fas fa-pencil-alt mr-2"></i> Custom Work
                            </button>
                            <button type="button" 
                                    class="mode-btn system-mode-btn px-4 py-2 rounded-lg border bg-white text-gray-700 border-gray-300 hover:bg-gray-50">
                                <i class="fas fa-database mr-2"></i> System Work
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Custom Form Container -->
                <div class="custom-form-container">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-4">
                        <div class="form-group">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-heading mr-2"></i> Title
                            </label>
                            <input type="text" name="work_title[]" class="work-title w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" 
                                   value="${data.work_title||''}" placeholder="Work title">
                        </div>
                        <div class="form-group">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-box mr-2"></i> Quantity
                            </label>
                            <input type="number" name="work_qty[]" class="work-qty w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" 
                                   value="${data.work_qty||1}" min="1" placeholder="0">
                        </div>
                        <div class="form-group">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-tag mr-2"></i> Rate (per unit)
                            </label>
                            <input type="number" name="work_rate[]" class="work-rate w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" 
                                   value="${data.work_rate||0}" placeholder="0.00">
                        </div>
                    </div>
                    <div class="form-group mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-align-left mr-2"></i> Details
                        </label>
                        <textarea name="work_particular[]" class="work-particular w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" 
                                  placeholder="Work details..." rows="3">${data.work_particular||''}</textarea>
                    </div>
                    <div class="amount-display">
                        <span>Total: ৳ <span class="amount_text">0.00</span></span>
                        <input type="hidden" name="amount[]" class="amount-input" value="0">
                    </div>
                </div>
                
                <!-- System Work Container -->
                <div class="system-work-container" style="display: none;">
                    <!-- Client Info Display -->
                    <div class="client-info-display bg-blue-50 p-4 rounded-lg mb-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-semibold text-gray-700">Selected Client:</h4>
                                <p class="selected-client-name text-blue-600">${globalClientState.clientName}</p>
                            </div>
                            <div class="client-change-warning text-sm text-red-600" style="${globalClientState.clientId ? 'display: none;' : 'display: block;'}">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                <span>Please select a client first</span>
                            </div>
                        </div>
                    </div>
        
                    <!-- Work Selection -->
                    <div class="form-group mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-briefcase mr-2"></i> Select Work
                        </label>
                        <select class="system-work-select w-full border border-gray-300 rounded-lg px-3 py-2" ${globalClientState.clientId ? '' : 'disabled'}>
                            <option value="">${globalClientState.clientId ? '-- Select a Work --' : '-- Select a Client First --'}</option>
                        </select>
                    </div>
        
                    <!-- Task Groups Container -->
                    <div class="task-groups-container mb-6">
                        <div class="text-center py-4 text-gray-500">
                            <i class="fas fa-info-circle text-lg mb-2"></i>
                            <p>Select a client and then select a work to see tasks</p>
                        </div>
                    </div>
        
                    <!-- Add Task Group Button -->
                    <div class="mb-6">
                        <button type="button" 
                                class="add-task-group-btn btn-outline py-2 px-4">
                            <i class="fas fa-layer-group mr-2"></i> Add Task Group for This Work
                        </button>
                    </div>
        
                    <!-- Generated Work Items Preview -->
                    <div class="generated-items-container border-t pt-6">
                        <h4 class="font-semibold text-gray-700 mb-4">
                            <i class="fas fa-list-check mr-2"></i> Generated Work Items
                        </h4>
                        <div class="generated-items-list">
                            <div class="text-center py-6 text-gray-500">
                                <i class="fas fa-inbox text-3xl mb-3"></i>
                                <p>No tasks selected yet. Select tasks above to generate work items.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Remove Button -->
                <div class="mt-4 pt-4 border-t">
                    <button type="button" class="remove-work-btn bg-red-600 hover:bg-red-700 text-white font-medium py-1.5 px-3 rounded flex items-center ml-auto">
                        <i class="fas fa-trash mr-1"></i> Remove Work Item
                    </button>
                </div>
            `;
            
            document.getElementById('work_items').appendChild(div);
            
            // Initialize event listeners for this work item
            const workItemId = initializeWorkItemEventListeners(div);
            
            // ✅ **NEW: Immediately update system mode with current client**
            const systemContainer = div.querySelector('.system-work-container');
            if (systemContainer && globalClientState.clientId) {
                // Update client info display
                updateSystemWorkItemClientInfo(div);
                
                // Fetch works for this client
                setTimeout(() => {
                    fetchWorksForClient(globalClientState.clientId, systemContainer);
                }, 100);
            }
            
            // Initialize calculation for custom mode
            updateCustomWorkItemCalculation(div);
            
            return div;
        }
    
        /* ========== 15. EXISTING CALCULATION FUNCTIONS ========== */
        function removeWorkItem(btn) {
            // This function is replaced by event listener in initializeWorkItemEventListeners
            const workItemElement = btn.closest('.item-card');
            const workItemId = workItemElement.dataset.workItemId;
            
            if (confirm('Are you sure you want to remove this work item?')) {
                workItemStates.delete(workItemId);
                workItemElement.remove();
                calculateTotal();
            }
        }
    
        function calcAmount(el) {
            // This function is replaced by updateCustomWorkItemCalculation
            const workItemElement = el.closest('.item-card');
            updateCustomWorkItemCalculation(workItemElement);
        }
    
        function calculateTotal() {
            let total = 0;
            
            // Calculate from custom mode work items
            document.querySelectorAll('.custom-form-container [name="amount[]"]').forEach(i => {
                const container = i.closest('.custom-form-container');
                if (container && container.style.display !== 'none') {
                    total += parseFloat(i.value) || 0;
                }
            });
            
            // Calculate from system mode generated items
            document.querySelectorAll('.generated-item [name="amount[]"]').forEach(i => {
                total += parseFloat(i.value) || 0;
            });
    
            document.getElementById('total_amount_display').innerText = total.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            document.getElementById('total_amount').value = total.toFixed(2);
            calculateDue();
            // Advance display update — total change হলে net due recalculate
            if (typeof updateAdvanceDisplay === 'function') updateAdvanceDisplay();
        }
    
        function calculateDue() {
            const total = parseFloat(document.getElementById('total_amount').value) || 0;
            const paid = parseFloat(document.getElementById('paid_amount').value) || 0;
            
            // Math.max(0, ...) সরিয়ে শুধু বিয়োগফল রাখুন
            const due = total - paid; 
        
            document.getElementById('due_amount_display').innerText = due.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            document.getElementById('due_amount').value = due.toFixed(2);
            
            // ডিউ যদি মাইনাস হয় তবে লাল রঙের বদলে অন্য কালার দিতে পারেন (ঐচ্ছিক)
            if (due < 0) {
                document.getElementById('due_amount_display').classList.remove('due');
                document.getElementById('due_amount_display').style.color = '#3b82f6'; // Blue for advance
            } else {
                document.getElementById('due_amount_display').style.color = '';
                document.getElementById('due_amount_display').classList.add('due');
            }
        }
    
        /* ========== 16. FORM MANAGEMENT ========== */
        function saveDraft() {
            const data = new FormData(document.getElementById('invoiceForm'));
            const obj = {};
            data.forEach((v, k) => {
                if (obj[k]) {
                    if (!Array.isArray(obj[k])) obj[k] = [obj[k]];
                    obj[k].push(v);
                } else obj[k] = v;
            });
            
            // Add client data to draft
            obj.client_id = globalClientState.clientId;
            obj.client_name = globalClientState.clientName;
            
            localStorage.setItem(STORAGE_KEY, JSON.stringify(obj));
        }
    
        function loadDraft() {
            const raw = localStorage.getItem(STORAGE_KEY);
            if (!raw) return;
            const data = JSON.parse(raw);
    
            // Load client data if exists
            if (data.client_id) {
                updateGlobalClientState(data.client_id, data.client_name);
                if (data.client_id && document.getElementById('clientInput')) {
                    document.getElementById('clientInput').value = `${data.client_id} | ${data.client_name}`;
                }
            }
    
            // Load simple fields
            Object.keys(data).forEach(k => {
                if (!k.includes('[]') && !k.startsWith('bank[') && !k.startsWith('mfs[') && k !== 'client_id' && k !== 'client_name') {
                    const el = document.querySelector(`[name="${k}"]`);
                    if (el) el.value = data[k];
                }
            });
    
            // Note: System work mode data cannot be restored from draft
            // because it depends on API calls and dynamic state
            // Only custom work items will be restored
    
            // Load custom work items
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
                document.getElementById('clientInput').value = '';
                document.getElementById('selectedClientDisplay').textContent = 'No client selected';
                
                // Clear global client state
                updateGlobalClientState(null, null);
                
                // Clear all work item states
                workItemStates.clear();
                
                // Add initial work item
                addWorkItem();
                
                // Fetch new invoice number after clearing form
                fetchInvoiceNumberFromAPI();
                calculateTotal();
            }
        }
    
        /* ========== 17. BANK/MFS MODAL FUNCTIONS ========== */
        function openBankMfsModal() {
            const savedData = localStorage.getItem(BANK_MFS_KEY);
            const currentData = savedData ? JSON.parse(savedData) : {
                banks: vendorData?.banks || [],
                mfs: vendorData?.mfs || []
            };
    
            populateModalFields(currentData);
            document.getElementById('bankMfsModal').style.display = 'flex';
        }
    
        function closeBankMfsModal() {
            document.getElementById('bankMfsModal').style.display = 'none';
        }
    
        function populateModalFields(data) {
            document.getElementById('bank_fields').innerHTML = '';
            document.getElementById('mfs_fields').innerHTML = '';
    
            if (data.banks && data.banks.length > 0) {
                data.banks.forEach((bank, index) => {
                    addBankField(bank, index);
                });
            } else {
                addBankField();
            }
    
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
                const bankFields = document.querySelectorAll('#bank_fields .item-card');
                bankFields.forEach((field, index) => {
                    field.querySelector('h4').innerHTML = `<i class="fas fa-university text-green-600 mr-2"></i> Bank ${index + 1}`;
                });
            }
        }
    
        function removeMfsField(button) {
            if (confirm('Are you sure you want to remove this MFS information?')) {
                button.closest('.item-card').remove();
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
    
            const mfs = [];
            document.querySelectorAll('#mfs_fields .item-card').forEach(card => {
                const mfsItem = {};
    
                card.querySelectorAll('.mfs-field').forEach(input => {
                    mfsItem[input.dataset.field] = input.value;
                });
    
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
    
            localStorage.setItem(BANK_MFS_KEY, JSON.stringify(bankMfsData));
            displayBankMfsData(bankMfsData);
    
            // Save to server (AJAX call)
            fetch('api/server/save-bank-mfs.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(bankMfsData)
                })
                .then(response => response.json())
                .then(result => {
                    // console.log('Server save result:', result);
                })
                .catch(error => {
                    console.error('Error saving to server:', error);
                });
    
            closeBankMfsModal();
            alert('Bank/MFS information saved successfully!');
        }
    
        /* ========== 18. FORM SUBMISSION HANDLER ========== */
        
        document.getElementById('invoiceForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // ========== ADD THIS: Clean up inputs before submission ==========
            cleanupFormInputsBeforeSubmit();
            
            // Validate client selection
            if (!globalClientState.clientId) {
                alert('Please select a client first.');
                document.getElementById('clientInput').focus();
                return;
            }
        
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            submitBtn.disabled = true;
        
            try {
                // First, get fresh invoice number from API
                const invoiceNo = await fetchInvoiceNumberFromAPI();
                
                if (!invoiceNo || invoiceNo === 'ERROR-API') {
                    alert('Cannot get invoice number from API. Please try again.');
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                    return;
                }
        
                // Update invoice number input
                document.getElementById('invoiceNoInput').value = invoiceNo;
                
                // Get bank/mfs data from localStorage
                const bankMfsData = JSON.parse(localStorage.getItem('bank_mfs_data') || '{"banks":[],"mfs":[]}');
                
                // Create FormData from the form
                const formData = new FormData(this);
                
                // MANUALLY ADD BANK/MFS DATA TO FORM DATA
                // Add bank data
                if (bankMfsData.banks && Array.isArray(bankMfsData.banks)) {
                    bankMfsData.banks.forEach((bank, bankIndex) => {
                        if (bank && typeof bank === 'object') {
                            if (bank.vendor_bank) {
                                formData.append(`bank[${bankIndex}][vendor_bank]`, bank.vendor_bank);
                            }
                            if (bank.vendor_bank_account) {
                                formData.append(`bank[${bankIndex}][vendor_bank_account]`, bank.vendor_bank_account);
                            }
                            if (bank.vendor_bank_branch) {
                                formData.append(`bank[${bankIndex}][vendor_bank_branch]`, bank.vendor_bank_branch);
                            }
                            if (bank.vendor_bank_routing) {
                                formData.append(`bank[${bankIndex}][vendor_bank_routing]`, bank.vendor_bank_routing);
                            }
                        }
                    });
                }
                
                // Add MFS data
                if (bankMfsData.mfs && Array.isArray(bankMfsData.mfs)) {
                    bankMfsData.mfs.forEach((mfs, mfsIndex) => {
                        if (mfs && typeof mfs === 'object') {
                            if (mfs.vendor_mfs_title) {
                                formData.append(`mfs[${mfsIndex}][vendor_mfs_title]`, mfs.vendor_mfs_title);
                            }
                            if (mfs.vendor_mfs_type) {
                                formData.append(`mfs[${mfsIndex}][vendor_mfs_type]`, mfs.vendor_mfs_type);
                            }
                            if (mfs.vendor_amount_note) {
                                formData.append(`mfs[${mfsIndex}][vendor_amount_note]`, mfs.vendor_amount_note);
                            }
                            
                            // Handle MFS accounts
                            if (mfs.vendor_mfs_account) {
                                const accounts = Array.isArray(mfs.vendor_mfs_account) 
                                    ? mfs.vendor_mfs_account 
                                    : [mfs.vendor_mfs_account];
                                
                                accounts.forEach((account, accIndex) => {
                                    if (account) {
                                        formData.append(`mfs[${mfsIndex}][vendor_mfs_account][]`, account);
                                    }
                                });
                            }
                        }
                    });
                }
                
                // Debug: Check what data is being sent
                // console.log('FormData entries before sending:');
                // console.log('Bank/MFS Data from localStorage:', bankMfsData);
                
                // for (let pair of formData.entries()) {
                //     console.log(pair[0] + ': ' + pair[1]);
                // }
        
                // Send form data via AJAX
                const response = await fetch(API_INVOICE_STORE, {
                    method: 'POST',
                    body: formData
                });
        
                const result = await response.json();
        
                if (result.success) {
                    // Show success message
                    alert(result.message);
        
                    // Clear localStorage
                    localStorage.removeItem('invoice_create_draft');
        
                    // Reset form manually — this.reset() dynamic elements ভাঙে
                    const workItemsEl = document.getElementById('work_items');
                    if (workItemsEl) workItemsEl.innerHTML = '';
                    const clientInputEl = document.getElementById('clientInput');
                    if (clientInputEl) clientInputEl.value = '';
                    const clientDisplayEl = document.getElementById('selectedClientDisplay');
                    if (clientDisplayEl) clientDisplayEl.textContent = 'No client selected';

                    // Clear global client state
                    updateGlobalClientState(null, null);
                    
                    // Clear all work item states
                    workItemStates.clear();
                    
                    // Add initial work item
                    addWorkItem();
                    calculateTotal();
                    
                    // Print invoice
                    printInvoice(result.invoice_no);
                    window.location.href = INDEX_INVOICE;
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
            localStorage.removeItem('invoice_create_draft');
            location.reload();
        }
    
        /* ========== 19. INITIALIZATION ========== */
        document.addEventListener('DOMContentLoaded', async function() {

            // Fetch invoice number from API first
            await fetchInvoiceNumberFromAPI();
            
            // Load vendor data
            await loadVendorData();

            // Add initial work item
            addWorkItem();

            // Load draft if exists
            // loadDraft();

            // Auto-save on input
            document.addEventListener('input', () => {
                setTimeout(saveDraft, 100);
            });

            // Close modal on overlay click
            const bankModal = document.getElementById('bankMfsModal');
            if (bankModal) {
                bankModal.addEventListener('click', (e) => {
                    if (e.target === bankModal) {
                        closeBankMfsModal();
                    }
                });
            }
            
            // console.log('Global client state:', globalClientState);
        });
    
        // Calculate on page load
        window.onload = calculateTotal;
    </script>
</body>
</html>