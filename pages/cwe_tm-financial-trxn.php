<?php
// FILE PATH: pages/cwe_tm-financial-trxn.php
// cwe_tm-financial-trnx.php (Print Fix)
include_once('./authenticate.php');
$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898";
}

$workId = $_GET['work_id'] ?? '';
$taskId = $_GET['task_id'] ?? '';

$getClientsApi = $ip_port . "api/clients/get-client-info.php?work_id=" . urlencode($workId);
$getTaskFinEntriesApi = $ip_port . "api/financial_entries/task-fin-entries.php?task_id=" . urlencode($taskId);
$storeFinancialEntriesApi = $ip_port . "api/financial_entries/store.php";
$getTaskApi = $ip_port . "api/old_tasks/task-details.php?task_id=" . urlencode($taskId);
$uploadFinFileApi = $ip_port . "api/financial_entries/upload-file.php";
$fileExplorerApi  = $ip_port . "api/old_tasks/file-explorer.php?task_id=" . urlencode($taskId) . "&path=files";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Financial Transaction - Task Management</title>
    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png" sizes="16x16">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* ============================================================
           CUSTOM STYLES
           ============================================================ */
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .stat-card {
            transition: all 0.3s ease;
            border-left: 4px solid;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
        }

        .badge-pending {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .badge-completed {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .transaction-row {
            transition: all 0.2s ease;
        }

        .transaction-row:hover {
            background-color: #f8fafc;
        }

        .amount-debit {
            color: #dc2626;
            font-weight: 600;
        }

        .amount-credit {
            color: #059669;
            font-weight: 600;
        }

        .balance-positive {
            color: #059669;
            font-weight: 700;
        }

        .balance-negative {
            color: #dc2626;
            font-weight: 700;
        }

        .balance-neutral {
            color: #6b7280;
            font-weight: 700;
        }

        .type-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }

        .type-debit {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .type-credit {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .floating-action {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 50;
            transition: all 0.3s ease;
        }

        .floating-action:hover {
            transform: scale(1.1);
        }

        .summary-card {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border: 1px solid #e2e8f0;
        }

        .file-chip {
            display: inline-flex;
            align-items: center;
            background-color: #f1f5f9;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
            transition: all 0.2s ease;
        }

        .file-chip:hover {
            background-color: #e2e8f0;
            transform: translateY(-1px);
        }

        .qty-rate-group {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 1px solid #bae6fd;
            border-radius: 0.75rem;
            padding: 0.75rem;
        }

        .qty-rate-group .label-sm {
            font-size: 0.7rem;
            font-weight: 600;
            color: #0369a1;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .calc-field:focus {
            ring: 2px solid #6366f1;
        }
        .calc-result {
            background-color: #f0fdf4;
            border-color: #86efac !important;
        }

        /* ============================================================
           MOBILE RESPONSIVE
           ============================================================ */
        #sidebar {
            transform: translateX(-100%);
            transition: transform 0.3s ease-in-out;
            width: 280px;
            z-index: 1000;
        }

        #sidebar.open {
            transform: translateX(0);
        }

        #sidebarOverlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        #sidebarOverlay.active {
            display: block;
        }

        .mobile-menu-btn {
            display: none;
        }

        @media (min-width: 768px) {
            #sidebar {
                transform: translateX(0) !important;
                width: 256px;
            }
            #sidebarOverlay {
                display: none !important;
            }
            .mobile-menu-btn {
                display: none !important;
            }
            #mainContent {
                margin-left: 256px;
            }
        }

        @media (max-width: 767px) {
            .mobile-menu-btn {
                display: block;
            }
            #mainContent {
                margin-left: 0;
                padding-top: 4rem;
            }
            .stat-card .text-xl {
                font-size: 1.1rem;
            }
            .stat-card .text-2xl {
                font-size: 1.25rem;
            }
        }

        @media (max-width: 640px) {
            .grid-cols-1.md\:grid-cols-2 {
                grid-template-columns: 1fr !important;
            }
            .lg\:grid-cols-3 {
                grid-template-columns: 1fr !important;
            }
            .lg\:col-span-2 {
                grid-column: span 1 !important;
            }
            .p-6 {
                padding: 0.75rem !important;
            }
            .p-5 {
                padding: 0.75rem !important;
            }
            .px-6 {
                padding-left: 0.5rem !important;
                padding-right: 0.5rem !important;
            }
            .py-4 {
                padding-top: 0.5rem !important;
                padding-bottom: 0.5rem !important;
            }
            .text-2xl {
                font-size: 1.25rem !important;
            }
            .text-lg {
                font-size: 1rem !important;
            }
            .gap-6 {
                gap: 0.75rem !important;
            }
            .space-y-6 > * + * {
                margin-top: 0.75rem !important;
            }
            .qty-rate-group .grid-cols-3 {
                grid-template-columns: 1fr 1fr;
            }
            .floating-action {
                bottom: 1rem;
                right: 1rem;
            }
            .floating-action button {
                width: 2.75rem;
                height: 2.75rem;
                font-size: 0.875rem;
            }
            .hide-mobile {
                display: none !important;
            }
            .transaction-mobile-card {
                background: #f8fafc;
                border-radius: 0.75rem;
                padding: 0.75rem;
                margin-bottom: 0.5rem;
                border: 1px solid #e2e8f0;
            }
        }

        @media (max-width: 480px) {
            .qty-rate-group .grid-cols-3 {
                grid-template-columns: 1fr;
            }
            .gap-4 {
                gap: 0.5rem !important;
            }
            .space-y-4 > * + * {
                margin-top: 0.5rem !important;
            }
            .text-sm {
                font-size: 0.75rem !important;
            }
            .text-xs {
                font-size: 0.65rem !important;
            }
            .px-4 {
                padding-left: 0.5rem !important;
                padding-right: 0.5rem !important;
            }
            .py-3 {
                padding-top: 0.5rem !important;
                padding-bottom: 0.5rem !important;
            }
        }

        /* ============================================================
           PRINT STYLES - FIXED
           ============================================================ */
        @media print {
            /* Hide everything except main content */
            header,
            #sidebar,
            #sidebarOverlay,
            .mobile-menu-btn,
            .floating-action,
            .no-print,
            #previewModal,
            #editModal,
            .file-chip,
            .transaction-row button,
            [onclick]:not(.print-header *),
            button:not(.print-header *),
            .badge,
            .no-print {
                display: none !important;
            }

            /* Show main content full width */
            #mainContent {
                margin-left: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }

            /* Print header */
            .print-header {
                display: block !important;
                text-align: center;
                padding: 1rem 0;
                border-bottom: 2px solid #333;
                margin-bottom: 1rem;
            }

            .print-header h1 {
                font-size: 1.5rem;
                font-weight: bold;
                color: #000;
            }

            .print-header .print-meta {
                font-size: 0.75rem;
                color: #666;
                margin-top: 0.25rem;
            }

            /* Remove shadows and backgrounds */
            .bg-white {
                background: white !important;
                box-shadow: none !important;
                border: 1px solid #ddd !important;
            }

            .shadow-lg {
                box-shadow: none !important;
            }

            .rounded-xl {
                border-radius: 0 !important;
            }

            .gradient-bg {
                background: #f0f0f0 !important;
                color: #000 !important;
            }

            .gradient-bg h3 {
                color: #000 !important;
            }

            .gradient-bg p {
                color: #555 !important;
            }

            .gradient-bg .text-white {
                color: #000 !important;
            }

            .gradient-bg .text-blue-100 {
                color: #555 !important;
            }

            .bg-gradient-to-r {
                background: #f0f0f0 !important;
            }

            /* Table styles */
            table {
                width: 100% !important;
                border-collapse: collapse !important;
                font-size: 0.65rem !important;
            }

            table th,
            table td {
                border: 1px solid #ddd !important;
                padding: 0.3rem 0.4rem !important;
                text-align: left !important;
            }

            table th {
                background: #f5f5f5 !important;
                font-weight: bold !important;
                color: #000 !important;
            }

            /* Show all table rows */
            .sm\:table-header-group {
                display: table-header-group !important;
            }

            .sm\:table-row-group {
                display: table-row-group !important;
            }

            .sm\:table-row {
                display: table-row !important;
            }

            .sm\:table-cell {
                display: table-cell !important;
            }

            /* Force table to show all columns */
            .hide-mobile {
                display: table-cell !important;
            }

            /* Amount colors in print */
            .amount-debit,
            .amount-credit {
                color: #000 !important;
            }

            /* Summary cards */
            .summary-card {
                background: #f9f9f9 !important;
                border: 1px solid #ddd !important;
                padding: 0.5rem !important;
            }

            .summary-card .text-2xl {
                font-size: 1.1rem !important;
            }

            /* Print layout */
            .print-grid {
                display: grid !important;
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 0.5rem !important;
            }

            /* Footer */
            .print-footer {
                display: block !important;
                text-align: center;
                font-size: 0.65rem;
                color: #999;
                border-top: 1px solid #ddd;
                padding-top: 0.5rem;
                margin-top: 1rem;
            }

            /* Task meta section */
            #taskMetaSection .stat-card {
                border-left: 3px solid #333 !important;
                background: #f9f9f9 !important;
                padding: 0.5rem !important;
            }

            #taskMetaSection .stat-card p {
                color: #000 !important;
            }

            /* Dark backgrounds in print */
            .bg-blue-50,
            .bg-green-50,
            .bg-purple-50,
            .bg-orange-50 {
                background: #f5f5f5 !important;
            }

            .text-blue-600,
            .text-green-600,
            .text-purple-600,
            .text-orange-600 {
                color: #000 !important;
            }

            /* Hide scrollbars */
            .overflow-x-auto {
                overflow: visible !important;
            }

            /* DO NOT force hidden elements to show */
            .hidden {
                display: none !important;
            }

            /* Only show hidden elements that are specifically for print */
            .print-only {
                display: block !important;
            }

            /* QTY/RATE in print */
            .qty-rate-group {
                background: #f5f5f5 !important;
                border: 1px solid #ddd !important;
            }

            /* Transaction mobile card - hide in print */
            .transaction-mobile-card {
                background: transparent !important;
                border: none !important;
                padding: 0 !important;
            }

            /* Type badge in print */
            .type-badge {
                border: 1px solid #000 !important;
                background: transparent !important;
                color: #000 !important;
            }
        }

        /* Print header - hidden by default */
        .print-header,
        .print-footer,
        .print-user-info {
            display: none;
        }

        /* ============================================================
           SCROLLBAR STYLING
           ============================================================ */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
</head>

<body class="bg-gray-50 font-sans antialiased">
    <!-- ============================================================
    SIDEBAR OVERLAY (Mobile)
    ============================================================ -->
    <div id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- ============================================================
    HEADER
    ============================================================ -->
    <?php include '../elements/header.php'; ?>

    <!-- ============================================================
    SIDEBAR
    ============================================================ -->
    <?php include '../elements/aside.php'; ?>

    <!-- ============================================================
    MODALS
    ============================================================ -->
    <?php include '../elements/preview-model.php'; ?>

    <!-- Edit Modal -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl max-w-md w-full max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center p-4 border-b">
                <h3 class="text-lg font-semibold text-gray-900">Edit Transaction</h3>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-4">
                <form id="editTransactionForm" onsubmit="updateTransaction(event)">
                    <input type="hidden" id="edit_transaction_id">
                    <input type="hidden" id="edit_original_type">
                    <input type="hidden" id="edit_vendor_type" value="">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Transaction Type</label>
                        <div id="edit_type_display" class="px-4 py-2 bg-gray-100 rounded-lg text-gray-700 font-medium"></div>
                    </div>
                    
                    <div id="edit_selection_container" class="mb-4 hidden">
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select Type</label>
                            <div class="flex space-x-4">
                                <label class="inline-flex items-center">
                                    <input type="radio" name="edit_account_type" value="vendor" class="form-radio text-blue-600" onchange="toggleEditSelection()">
                                    <span class="ml-2">Vendor</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" name="edit_account_type" value="own" class="form-radio text-blue-600" onchange="toggleEditSelection()">
                                    <span class="ml-2">Own Account</span>
                                </label>
                            </div>
                        </div>
                        <div id="edit_vendor_container">
                            <?php include('form-selects/vendors-edit.php') ?>
                        </div>
                        <div id="edit_account_container" class="hidden">
                            <?php include('form-selects/accounts-edit.php') ?>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Purpose</label>
                        <textarea id="edit_purpose" rows="3" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            required></textarea>
                    </div>
                    
                    <div class="mb-4 qty-rate-group">
                        <p class="text-xs text-blue-600 font-medium mb-2">
                            <i class="fas fa-calculator mr-1"></i>
                            যেকোনো দুইটা দিলে তৃতীয়টা auto calculate হবে
                        </p>
                        <div class="grid grid-cols-3 gap-2">
                            <div>
                                <label class="block label-sm mb-1">QTY</label>
                                <input type="number" step="0.01" min="0" id="edit_qty" placeholder="0"
                                    class="w-full px-2 py-2 border border-gray-300 rounded-lg text-sm calc-field">
                            </div>
                            <div>
                                <label class="block label-sm mb-1">Rate</label>
                                <input type="number" step="0.01" min="0" id="edit_rate" placeholder="0.00"
                                    class="w-full px-2 py-2 border border-gray-300 rounded-lg text-sm calc-field">
                            </div>
                            <div>
                                <label class="block label-sm mb-1">Amount ৳</label>
                                <input type="number" step="0.01" min="0" id="edit_amount" placeholder="0.00"
                                    class="w-full px-2 py-2 border border-gray-300 rounded-lg text-sm calc-field" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                        <input type="datetime-local" id="edit_date"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeEditModal()"
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                            <i class="fas fa-save mr-2"></i> Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ============================================================
    MAIN CONTENT
    ============================================================ -->
    <main id="mainContent" class="pt-16 transition-all duration-300">
        <!-- Print Header -->
        <div class="print-header">
            <h1>Financial Transaction Report</h1>
            <div class="print-meta">
                <span>Generated: <?php echo date('Y-m-d H:i:s'); ?></span>
                <span class="mx-2">|</span>
                <span>User: <?php echo $_SESSION['user_name'] ?? 'System'; ?></span>
                <span class="mx-2">|</span>
                <span>Work ID: <?php echo $workId; ?></span>
                <span class="mx-2">|</span>
                <span>Task ID: <?php echo $taskId; ?></span>
            </div>
        </div>

        <!-- ============================================================
        TASK OVERVIEW
        ============================================================ -->
        <div class="p-4 sm:p-6 no-print">
            <div class="bg-white rounded-xl shadow-lg p-4 sm:p-6 mb-4 sm:mb-6 border border-gray-100">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 sm:mb-6">
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Financial Transaction Management</h1>
                        <p class="text-sm sm:text-base text-gray-600 mt-1">Manage all financial transactions for this task</p>
                    </div>
                    <div class="mt-3 sm:mt-0 flex flex-wrap gap-2">
                        <a href="task-entry.php?work_id=<?php echo $workId; ?>"
                            class="px-3 sm:px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors flex items-center text-sm">
                            <i class="fas fa-arrow-left mr-2"></i> <span class="hidden xs:inline">Back</span>
                        </a>
                        <button onclick="printPage()"
                            class="px-3 sm:px-4 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg transition-colors flex items-center text-sm">
                            <i class="fas fa-print mr-2"></i> <span class="hidden xs:inline">Print</span>
                        </button>
                        <label
                            class="px-3 sm:px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors flex items-center text-sm cursor-pointer" onclick="window.print()">
                            <i class="fas fa-print mr-2"></i> <span class="hidden xs:inline">Print</span>
                        </label>
                    </div>
                </div>

                <div id="taskMetaSection" class="grid grid-cols-1 xs:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-4 sm:mb-6">
                    <div class="animate-pulse"><div class="h-20 sm:h-24 bg-gray-200 rounded-lg"></div></div>
                </div>

                <!-- Section 1: Task Files (file-explorer) -->
                <div class="mb-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-medium text-gray-500 flex items-center gap-1">
                            <i class="fas fa-folder-open text-yellow-500"></i> Task Files
                        </span>
                        <label class="cursor-pointer px-2 py-1 bg-yellow-50 hover:bg-yellow-100 text-yellow-700 rounded-lg text-xs flex items-center gap-1 border border-yellow-200 transition-colors">
                            <i class="fas fa-plus text-xs"></i> Add
                            <input type="file" id="s1FileInput" multiple class="hidden"
                                onchange="s1UploadFiles(this.files)">
                        </label>
                    </div>
                    <div id="s1FileChips" class="flex flex-wrap gap-1.5 min-h-[28px]">
                        <span class="text-xs text-gray-400 italic">No files yet</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================
        MAIN GRID
        ============================================================ -->
        <div class="p-4 sm:p-6 grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
            <!-- Left Column -->
            <div class="lg:col-span-2 space-y-4 sm:space-y-6">
                <!-- Transaction Cards - Hidden in Print -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6 no-print">
                    
                    <!-- DEBIT CARD -->
                    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                        <div class="gradient-bg p-3 sm:p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-base sm:text-lg font-semibold text-white">Client Deposit</h3>
                                    <p class="text-blue-100 text-xs sm:text-sm">Record client payments</p>
                                </div>
                                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-white/20 rounded-full flex items-center justify-center">
                                    <i class="fas fa-arrow-down text-white text-lg sm:text-xl"></i>
                                </div>
                            </div>
                        </div>
                        <div class="p-3 sm:p-5 space-y-3 sm:space-y-4">
                            <div id="clientWorkInfo" class="p-3 bg-blue-50 rounded-lg border border-blue-100">
                                <div class="flex items-center">
                                    <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                                    <span class="text-xs sm:text-sm text-blue-700">Loading...</span>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                    <i class="fas fa-bullseye mr-1"></i> Purpose
                                </label>
                                <textarea id="client_purpose" rows="4"
                                    placeholder="e.g., Initial Payment, Final Payment"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                            </div>

                            <div>
                                <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                    <i class="fa-solid fa-highlighter mr-1"></i> Note
                                </label>
                                <textarea id="client_note" rows="2"
                                    placeholder="Any notes..."
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                            </div>

                            <div class="qty-rate-group">
                                <p class="text-xs text-blue-600 font-medium mb-2">
                                    <i class="fas fa-calculator mr-1"></i> যেকোনো দুইটা দিলে তৃতীয়টা auto হবে
                                </p>
                                <div class="grid grid-cols-3 gap-1 sm:gap-2">
                                    <div>
                                        <label class="block label-sm mb-1">QTY</label>
                                        <input type="number" step="0.01" min="0" id="client_qty" placeholder="0"
                                            class="w-full px-2 py-1.5 sm:py-2 border border-gray-300 rounded-lg text-xs sm:text-sm calc-field">
                                    </div>
                                    <div>
                                        <label class="block label-sm mb-1">Rate</label>
                                        <input type="number" step="0.01" min="0" id="client_rate" placeholder="0.00"
                                            class="w-full px-2 py-1.5 sm:py-2 border border-gray-300 rounded-lg text-xs sm:text-sm calc-field">
                                    </div>
                                    <div>
                                        <label class="block label-sm mb-1">Amount ৳</label>
                                        <input type="number" step="0.01" min="0" id="client_amount" placeholder="0.00"
                                            class="w-full px-2 py-1.5 sm:py-2 border border-gray-300 rounded-lg text-xs sm:text-sm calc-field">
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                    <i class="far fa-calendar mr-1"></i> Date
                                </label>
                                <input type="date" id="client_date" value="<?php echo date('Y-m-d'); ?>"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>

                            <div>
                                <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                    <i class="fas fa-paperclip mr-1"></i> Attach Files <span class="text-gray-400 font-normal">(optional)</span>
                                </label>
                                <label class="flex items-center gap-2 px-3 py-2 border border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition-colors">
                                    <i class="fas fa-cloud-upload-alt text-gray-400 text-sm"></i>
                                    <span id="clientFileLabel" class="text-xs text-gray-500 truncate">Browse or drop files</span>
                                    <input type="file" id="clientFiles" multiple class="hidden"
                                        onchange="document.getElementById('clientFileLabel').textContent = this.files.length > 1 ? this.files.length+' files' : (this.files[0]?.name || 'Browse or drop files')">
                                </label>
                            </div>

                            <div class="grid grid-cols-2 gap-2 sm:gap-4">
                                <button onclick="refundTransaction('credit')"
                                    class="w-full py-2 sm:py-3 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-semibold rounded-lg transition-all text-xs sm:text-sm flex items-center justify-center">
                                    <i class="fas fa-undo mr-1 sm:mr-2"></i> Refund
                                </button>
                                <button onclick="recordTransaction('debit')"
                                    class="w-full py-2 sm:py-3 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white font-semibold rounded-lg transition-all text-xs sm:text-sm flex items-center justify-center">
                                    <i class="fas fa-plus mr-1 sm:mr-2"></i> Debit
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- CREDIT CARD -->
                    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                        <div class="bg-gradient-to-r from-green-500 to-emerald-600 p-3 sm:p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-base sm:text-lg font-semibold text-white">Vendor Payment</h3>
                                    <p class="text-green-100 text-xs sm:text-sm">Record vendor expenses</p>
                                </div>
                                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-white/20 rounded-full flex items-center justify-center">
                                    <i class="fas fa-arrow-up text-white text-lg sm:text-xl"></i>
                                </div>
                            </div>
                        </div>
                        <div class="p-3 sm:p-5 space-y-3 sm:space-y-4">
                            <div>
                                <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">
                                    <i class="fa-solid fa-shapes mr-1"></i> Select Type
                                </label>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <input type="radio" id="type-vendor" name="account_type" value="vendor" class="hidden peer" checked>
                                        <label for="type-vendor" class="flex flex-col items-center justify-center p-3 text-gray-500 bg-white border-2 border-gray-200 rounded-lg cursor-pointer peer-checked:border-blue-600 peer-checked:text-blue-600 peer-checked:bg-blue-50 hover:bg-gray-50 transition-all">
                                            <i class="fa-solid fa-shop mb-1 text-xl"></i>
                                            <span class="text-xs font-semibold">Vendor</span>
                                        </label>
                                    </div>
                                    <div>
                                        <input type="radio" id="type-own" name="account_type" value="own" class="hidden peer">
                                        <label for="type-own" class="flex flex-col items-center justify-center p-3 text-gray-500 bg-white border-2 border-gray-200 rounded-lg cursor-pointer peer-checked:border-blue-600 peer-checked:text-blue-600 peer-checked:bg-blue-50 hover:bg-gray-50 transition-all">
                                            <i class="fa-solid fa-user mb-1 text-xl"></i>
                                            <span class="text-xs font-semibold">Own</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div id="vendorSection">
                                <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                    <i class="fas fa-building mr-1"></i> Vendor
                                </label>
                                <?php include('form-selects/vendors.php') ?>
                            </div>

                            <div id="accountSection" class="hidden">
                                <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                    <i class="fas fa-building mr-1"></i> Own Account
                                </label>
                                <?php include('form-selects/accounts.php') ?>
                            </div>

                            <div>
                                <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                    <i class="fas fa-bullseye mr-1"></i> Purpose
                                </label>
                                <textarea id="vendor_purpose" rows="4"
                                    placeholder="e.g., Hotel Booking, Air Ticket"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"></textarea>
                            </div>

                            <div class="qty-rate-group" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-color: #86efac;">
                                <p class="text-xs text-emerald-600 font-medium mb-2">
                                    <i class="fas fa-calculator mr-1"></i> যেকোনো দুইটা দিলে তৃতীয়টা auto হবে
                                </p>
                                <div class="grid grid-cols-3 gap-1 sm:gap-2">
                                    <div>
                                        <label class="block label-sm mb-1" style="color: #065f46;">QTY</label>
                                        <input type="number" step="0.01" min="0" id="vendor_qty" placeholder="0"
                                            class="w-full px-2 py-1.5 sm:py-2 border border-gray-300 rounded-lg text-xs sm:text-sm calc-field">
                                    </div>
                                    <div>
                                        <label class="block label-sm mb-1" style="color: #065f46;">Rate</label>
                                        <input type="number" step="0.01" min="0" id="vendor_rate" placeholder="0.00"
                                            class="w-full px-2 py-1.5 sm:py-2 border border-gray-300 rounded-lg text-xs sm:text-sm calc-field">
                                    </div>
                                    <div>
                                        <label class="block label-sm mb-1" style="color: #065f46;">Amount ৳</label>
                                        <input type="number" step="0.01" min="0" id="vendor_amount" placeholder="0.00"
                                            class="w-full px-2 py-1.5 sm:py-2 border border-gray-300 rounded-lg text-xs sm:text-sm calc-field">
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                    <i class="far fa-calendar mr-1"></i> Date
                                </label>
                                <input type="date" id="vendor_date" value="<?php echo date('Y-m-d'); ?>"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                            </div>

                            <div>
                                <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                    <i class="fas fa-paperclip mr-1"></i> Attach Files <span class="text-gray-400 font-normal">(optional)</span>
                                </label>
                                <label class="flex items-center gap-2 px-3 py-2 border border-dashed border-green-300 rounded-lg cursor-pointer hover:border-green-400 hover:bg-green-50 transition-colors">
                                    <i class="fas fa-cloud-upload-alt text-gray-400 text-sm"></i>
                                    <span id="vendorFileLabel" class="text-xs text-gray-500 truncate">Browse or drop files</span>
                                    <input type="file" id="vendorFiles" multiple class="hidden"
                                        onchange="document.getElementById('vendorFileLabel').textContent = this.files.length > 1 ? this.files.length+' files' : (this.files[0]?.name || 'Browse or drop files')">
                                </label>
                            </div>

                            <div class="grid grid-cols-2 gap-2 sm:gap-4">
                                <button onclick="refundTransaction('debit')"
                                    class="w-full py-2 sm:py-3 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white font-semibold rounded-lg transition-all text-xs sm:text-sm flex items-center justify-center">
                                    <i class="fas fa-undo mr-1 sm:mr-2"></i> Refund
                                </button>
                                <button onclick="recordTransaction('credit')"
                                    class="w-full py-2 sm:py-3 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-semibold rounded-lg transition-all text-xs sm:text-sm flex items-center justify-center">
                                    <i class="fas fa-plus mr-1 sm:mr-2"></i> Credit
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ==================== FINANCIAL SUMMARY ==================== -->
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-4 sm:p-5">
                    <div class="flex flex-wrap items-center justify-between mb-3 sm:mb-4 no-print">
                        <h3 class="text-base sm:text-lg font-semibold text-gray-900">
                            <i class="fas fa-chart-line mr-2 text-blue-500"></i> Financial Summary
                        </h3>
                        <button onclick="reloadFinancialTable()"
                            class="px-2 sm:px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors text-xs sm:text-sm flex items-center">
                            <i class="fas fa-redo-alt mr-1"></i> <span class="hidden xs:inline">Refresh</span>
                        </button>
                    </div>

                    <!-- Print-only summary title -->
                    <div class="print-only hidden text-center font-bold text-lg mb-4">
                        Financial Summary
                    </div>

                    <div id="financialSummary" class="grid grid-cols-1 xs:grid-cols-3 gap-3 sm:gap-4 mb-4 sm:mb-6"></div>

                    <!-- Transactions Table -->
                    <div class="overflow-x-auto rounded-lg border border-gray-200 -mx-1">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 sm:table-header-group">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Purpose</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase hide-mobile">Note</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase hide-mobile">Client/Vendor</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase hide-mobile">QTY</th>
                                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase hide-mobile">Rate</th>
                                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase no-print">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="finTableBody" class="bg-white divide-y divide-gray-200"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ==================== RIGHT COLUMN ==================== -->
            <div class="space-y-4 sm:space-y-6">
                <!-- Task Info -->
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-4 sm:p-5">
                    <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-3 sm:mb-4 flex items-center">
                        <i class="fas fa-info-circle mr-2 text-blue-500"></i> Task Information
                    </h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Task ID:</span>
                            <span class="font-semibold text-gray-900" id="taskIdDisplay"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Task Name:</span>
                            <span class="font-semibold text-gray-900" id="taskNameDisplay"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Category:</span>
                            <span class="font-semibold" id="taskCategory"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Created:</span>
                            <span class="text-gray-900" id="taskCreated"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Updated:</span>
                            <span class="text-gray-900" id="taskUpdated"></span>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-4 sm:p-5">
                    <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-3 sm:mb-4 flex items-center">
                        <i class="fas fa-chart-bar mr-2 text-purple-500"></i> Statistics
                    </h3>
                    <div class="space-y-3">
                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-xs sm:text-sm text-gray-600">Total Transactions</span>
                                <span class="text-xs sm:text-sm font-semibold" id="totalTransactions">0</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div id="transactionProgress" class="bg-blue-600 h-2 rounded-full" style="width: 0%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-xs sm:text-sm text-gray-600">Debit vs Credit</span>
                                <span class="text-xs sm:text-sm font-semibold" id="debitCreditRatio">0:0</span>
                            </div>
                            <div class="flex h-2 rounded-full overflow-hidden">
                                <div id="debitBar" class="bg-red-500" style="width: 50%"></div>
                                <div id="creditBar" class="bg-green-500" style="width: 50%"></div>
                            </div>
                        </div>
                        <div class="pt-2 border-t">
                            <div class="text-center">
                                <div class="text-xl sm:text-2xl font-bold mb-1" id="netBalance">৳ 0.00</div>
                                <div class="text-xs text-gray-600">Net Balance</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-4 sm:p-5 no-print">
                    <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-3 sm:mb-4 flex items-center">
                        <i class="fas fa-history mr-2 text-orange-500"></i> Recent Activity
                    </h3>
                    <div id="recentActivity" class="space-y-2"></div>
                </div>

                <!-- Section 2: Financial Stmts Evidence -->
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-4 sm:p-5 no-print">
                    <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-3 flex items-center justify-between">
                        <span><i class="fas fa-receipt mr-2 text-indigo-500"></i> Financial Stmts Evidence</span>
                        <button onclick="_renderFinancialFileChips()" class="text-xs text-gray-400 hover:text-gray-600"><i class="fas fa-redo-alt"></i></button>
                    </h3>
                    <div id="financialFileChips" class="flex flex-wrap gap-1.5">
                        <span class="text-xs text-gray-400 italic">No files attached yet</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Print Footer -->
        <div class="print-footer">
            <p>Generated from Task Management System | <?php echo date('Y-m-d H:i:s'); ?></p>
            <p>Printed by: <?php echo $_SESSION['user_name'] ?? 'System'; ?></p>
        </div>
    </main>

    <!-- ============================================================
    FLOATING ACTION
    ============================================================ -->
    <div class="floating-action no-print">
        <button onclick="scrollToTop()"
            class="w-11 h-11 sm:w-12 sm:h-12 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-full shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center">
            <i class="fas fa-arrow-up text-sm sm:text-base"></i>
        </button>
    </div>

    <!-- ============================================================
    SCRIPTS
    ============================================================ -->
    <script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>

    <script>
        // ============================================================
        // CONFIGURATION
        // ============================================================
        const GET_CLIENT_API = "<?php echo $getClientsApi; ?>";
        const FINANCIAL_ENTRIES_STORE_API = "<?php echo $storeFinancialEntriesApi; ?>";
        const UPDATE_FINANCIAL_ENTRY_API = "<?php echo $ip_port; ?>api/financial_entries/update.php";
        const GET_FINANCIAL_STATEMENT_API = "<?php echo $getTaskFinEntriesApi; ?>";
        const GET_TASK_API = "<?php echo $getTaskApi; ?>";
        const UPLOAD_FIN_FILE_API = "<?php echo $uploadFinFileApi; ?>";
        const FILE_SERVE_BASE     = "<?php echo $ip_port; ?>api/file/serve.php";
        const FILE_EXPLORER_API   = "<?php echo $fileExplorerApi; ?>";

        const WORK_ID = "<?php echo $workId; ?>";
        const TASK_ID = "<?php echo $taskId; ?>";

        let allTransactions = [];
        let currentClientId = null;
        let clientName = null;
        let task = null;
        let WORK_TITLE = '';
        let TASK_TITLE = '';

        // ============================================================
        // SIDEBAR TOGGLE
        // ============================================================
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
            document.body.style.overflow = sidebar.classList.contains('open') ? 'hidden' : '';
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const sidebar = document.getElementById('sidebar');
                if (sidebar.classList.contains('open')) {
                    toggleSidebar();
                }
            }
        });

        window.addEventListener('resize', function() {
            if (window.innerWidth >= 768) {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('sidebarOverlay');
                sidebar.classList.remove('open');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        });

        // ============================================================
        // PRINT FUNCTION
        // ============================================================
        function printPage() {
            window.print();
        }

        // ============================================================
        // QTY/RATE/AMOUNT AUTO-CALC
        // ============================================================
        function setupQtyRateCalc() {
            setupCalcGroup('client_qty', 'client_rate', 'client_amount');
            setupCalcGroup('vendor_qty', 'vendor_rate', 'vendor_amount');
            setupCalcGroup('edit_qty', 'edit_rate', 'edit_amount');
        }

        function setupCalcGroup(qtyId, rateId, amountId) {
            const qtyEl = document.getElementById(qtyId);
            const rateEl = document.getElementById(rateId);
            const amountEl = document.getElementById(amountId);

            if (!qtyEl || !rateEl || !amountEl) return;

            let lastEdited = null;

            function smartCalc() {
                const qty = parseFloat(qtyEl.value) || null;
                const rate = parseFloat(rateEl.value) || null;
                const amount = parseFloat(amountEl.value) || null;

                [qtyEl, rateEl, amountEl].forEach(el => el.classList.remove('calc-result'));

                if (lastEdited !== 'amount' && qty !== null && rate !== null && qty > 0 && rate > 0) {
                    amountEl.value = (qty * rate).toFixed(2);
                    amountEl.classList.add('calc-result');
                } else if (lastEdited !== 'rate' && qty !== null && amount !== null && qty > 0 && amount > 0) {
                    rateEl.value = (amount / qty).toFixed(2);
                    rateEl.classList.add('calc-result');
                } else if (lastEdited !== 'qty' && rate !== null && amount !== null && rate > 0 && amount > 0) {
                    qtyEl.value = (amount / rate).toFixed(2);
                    qtyEl.classList.add('calc-result');
                }
            }

            qtyEl.addEventListener('input', () => { lastEdited = 'qty'; smartCalc(); });
            rateEl.addEventListener('input', () => { lastEdited = 'rate'; smartCalc(); });
            amountEl.addEventListener('input', () => { lastEdited = 'amount'; smartCalc(); });
        }

        function buildQtyRate(qtyId, rateId) {
            const qty = parseFloat(document.getElementById(qtyId)?.value) || null;
            const rate = parseFloat(document.getElementById(rateId)?.value) || null;
            if (!qty && !rate) return null;
            return JSON.stringify({ qty: qty || 0, rate: rate || 0 });
        }

        // ============================================================
        // TASK META DATA
        // ============================================================
        async function loadTaskMetaData() {
            try {
                const response = await fetch(GET_TASK_API);
                const data = await response.json();

                if (data.success && data.task) {
                    task = data.task;
                    const category = task.category;
                    
                    WORK_TITLE = task.work_title || '';
                    TASK_TITLE = task.title || '';
                    let text = '';
                    let container = document.getElementById("client_purpose");
                    
                    try {
                        if (category == 1 && task.air_ticket_info) {
                            const raw = JSON.parse(task.air_ticket_info);
                            if (raw && raw.length > 0 && raw[0]) {
                                const purposeData = JSON.parse(raw[0]);
                                if (purposeData?.purpose?.length) {
                                    text = purposeData.purpose.map(p => {
                                        const passengers = p.passengers?.join(", ") || '';
                                        const others = p.others?.join(", ") || '';
                                        return `${p.route || ''}\n${passengers}\n${p.travel_date || ''}\n${others}`;
                                    }).join("\n\n");
                                }
                            }
                        }
                        
                        if (category == 2 && task.hotel_info) {
                            const raw = JSON.parse(task.hotel_info);
                            if (raw && raw.length > 0 && raw[0]) {
                                const parseData = JSON.parse(raw[0]);
                                if (parseData) {
                                    const guests = parseData.guest_names?.join(" | ") || '';
                                    text = `${parseData.hotel_name || ''},\n${parseData.hotel_city || ''}, ${parseData.hotel_country || ''}\n${guests}\nC/In: ${parseData.check_in_date || ''} | C/Out: ${parseData.check_out_date || ''} | ${parseData.no_of_nights || 0} Nights\n${parseData.room_info || ''} | ${parseData.meal_plan || ''} | ${parseData.total_rooms || 0} Room('s)`;
                                }
                            }
                        }
                    } catch (parseError) {
                        console.warn('Error parsing task details:', parseError);
                    }
                    
                    container.value = text || 'No task details available';

                    document.getElementById('taskIdDisplay').textContent = task.sys_id || 'N/A';
                    document.getElementById('taskNameDisplay').textContent = task.title || 'N/A';

                    let categoryText = 'Unknown';
                    let categoryColor = 'gray';
                    if (task.category == 1) {
                        categoryText = 'Air Ticket Issue';
                        categoryColor = 'blue';
                    } else if (task.category == 2) {
                        categoryText = 'Hotel Booking';
                        categoryColor = 'green';
                    }
                    document.getElementById('taskCategory').textContent = categoryText;
                    document.getElementById('taskCategory').className = `text-sm font-semibold text-${categoryColor}-600`;

                    const meta = task.meta_data ? JSON.parse(task.meta_data) : {};
                    const created = meta.created_by_date || {};
                    const updatedArray = meta.updated_by_date || [];
                    const lastUpdate = updatedArray.length > 0 ? updatedArray[updatedArray.length - 1] : null;

                    document.getElementById('taskCreated').textContent = created.date || 'N/A';
                    document.getElementById('taskUpdated').textContent = lastUpdate ? lastUpdate.date : 'N/A';

                    updateTaskMetaSection(task, meta);
                    loadTaskFiles(task);
                }
            } catch (error) {
                console.error('Error loading task data:', error);
                document.getElementById('client_purpose').value = 'Error loading task data';
            }
        }

        function updateTaskMetaSection(task, meta) {
            const created = meta.created_by_date || {};
            const updatedArray = meta.updated_by_date || [];
            const lastUpdate = updatedArray.length > 0 ? updatedArray[updatedArray.length - 1] : null;

            taskMetaSection.innerHTML = `
                <div class="stat-card bg-gradient-to-r from-blue-50 to-blue-100 border-blue-200 p-3 sm:p-4 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-blue-600 font-medium">Task ID</p>
                            <p class="text-base sm:text-xl font-bold text-blue-900">${task.sys_id || 'N/A'}</p>
                        </div>
                        <i class="fas fa-hashtag text-blue-400 text-lg sm:text-2xl"></i>
                    </div>
                </div>
                <div class="stat-card bg-gradient-to-r from-green-50 to-green-100 border-green-200 p-3 sm:p-4 rounded-lg">
                    <div>
                        <p class="text-xs text-green-600 font-medium">Work Title</p>
                        <p class="text-sm sm:text-lg font-semibold text-green-900 truncate">${task.work_title || 'N/A'}</p>
                    </div>
                </div>
                <div class="stat-card bg-gradient-to-r from-purple-50 to-purple-100 border-purple-200 p-3 sm:p-4 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-purple-600 font-medium">Created By</p>
                            <p class="text-sm sm:text-lg font-semibold text-purple-900 capitalize">${created.user || 'System'}</p>
                            <p class="text-xs text-purple-600">${created.date || ''}</p>
                        </div>
                        <i class="fas fa-user-plus text-purple-400 text-lg sm:text-2xl"></i>
                    </div>
                </div>
                <div class="stat-card bg-gradient-to-r from-orange-50 to-orange-100 border-orange-200 p-3 sm:p-4 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-orange-600 font-medium">${lastUpdate ? 'Last Updated' : 'No Updates'}</p>
                            ${lastUpdate ? `
                                <p class="text-sm sm:text-lg font-semibold text-orange-900 capitalize">${lastUpdate.user}</p>
                                <p class="text-xs text-orange-600">${lastUpdate.date}</p>
                            ` : `
                                <p class="text-sm sm:text-lg font-semibold text-orange-900">-</p>
                            `}
                        </div>
                        <i class="fas fa-sync-alt text-orange-400 text-lg sm:text-2xl"></i>
                    </div>
                </div>
            `;
        }

        function loadTaskFiles(task) {
        }

        // ── Section 1: Task Files (file-explorer) ────────────────
        async function s1LoadFiles() {
            const container = document.getElementById('s1FileChips');
            if (!container) return;

            try {
                const res  = await fetch(FILE_EXPLORER_API + '&action=list');
                const data = await res.json();
                const files = (data.contents || []).filter(i => i.type === 'file');

                if (!data.success || files.length === 0) {
                    container.innerHTML = '<span class="text-xs text-gray-400 italic">No files yet</span>';
                    return;
                }

                container.innerHTML = files.map(f => {
                    const ext  = f.name.split('.').pop().toLowerCase();
                    const icon = ['jpg','jpeg','png','gif','webp'].includes(ext) ? 'fa-file-image text-purple-500'
                               : ext === 'pdf' ? 'fa-file-pdf text-red-500'
                               : ['doc','docx'].includes(ext) ? 'fa-file-word text-blue-500'
                               : 'fa-file text-gray-500';
                    // smb_token is already a full serve URL — don't wrap it again
                    const url  = f.smb_token || '#';
                    return `<a href="${url}" target="_blank"
                        class="file-chip" title="${f.name}">
                        <i class="fas ${icon} mr-1 text-xs"></i>
                        <span class="truncate max-w-[120px]">${f.name}</span>
                    </a>`;
                }).join('');
            } catch(e) {
                container.innerHTML = '<span class="text-xs text-red-400">Error loading files</span>';
            }
        }

        async function s1UploadFiles(files) {
            if (!files || files.length === 0) return;
            showNotification('Uploading...', 'info');
            const fd = new FormData();
            for (const f of files) fd.append('files[]', f);
            try {
                const url  = `<?php echo $ip_port; ?>api/old_tasks/upload-file.php?task_id=${encodeURIComponent(TASK_ID)}`;
                const res  = await fetch(url, { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    showNotification('File uploaded!', 'success');
                    s1LoadFiles();
                } else {
                    showNotification('Upload failed: ' + (data.message || ''), 'error');
                }
            } catch(e) {
                showNotification('Upload error', 'error');
            } finally {
                document.getElementById('s1FileInput').value = '';
            }
        }

        // ── Section 2: Financial entry files ─────────────────────
        async function _renderFinancialFileChips() {
            const container = document.getElementById('financialFileChips');
            if (!container) return;

            try {
                const res  = await fetch(GET_FINANCIAL_STATEMENT_API);
                const data = await res.json();
                if (!data.success) return;

                const withFiles = (data.finStmts || []).filter(t => {
                    try { return JSON.parse(t.files_json || '[]').length > 0; } catch { return false; }
                });

                if (withFiles.length === 0) {
                    container.innerHTML = '<span class="text-xs text-gray-400 italic">No files attached yet</span>';
                    return;
                }

                let chips = '';
                withFiles.forEach(t => {
                    let files = [];
                    try { files = JSON.parse(t.files_json || '[]'); } catch {}
                    files.forEach((fname, i) => {
                        const ext  = fname.split('.').pop().toLowerCase();
                        const icon = ['jpg','jpeg','png','gif','webp'].includes(ext) ? 'fa-file-image text-purple-400'
                                   : ext === 'pdf' ? 'fa-file-pdf text-red-400'
                                   : 'fa-file-invoice text-indigo-400';
                        const url  = `${FILE_SERVE_BASE}?fin_id=${t.sys_id}&page=${i}`;
                        const label = t.purpose ? `${t.purpose} #${i+1}` : fname;
                        chips += `<a href="${url}" target="_blank" class="file-chip" title="${label}">
                            <i class="fas ${icon} mr-1 text-xs"></i>
                            <span class="truncate max-w-[110px]">${label}</span>
                        </a>`;
                    });
                });

                container.innerHTML = chips;
            } catch(e) {
                container.innerHTML = '<span class="text-xs text-red-400">Error loading</span>';
            }
        }

        // ============================================================
        // CLIENT DATA
        // ============================================================
        async function loadClientData() {
            try {
                const response = await fetch(GET_CLIENT_API);
                const data = await response.json();

                if (data.success) {
                    const client = data.client;
                    const work = data.work;

                    currentClientId = client.sys_id;
                    clientName = client.name;

                    document.getElementById('clientWorkInfo').innerHTML = `
                        <div class="flex items-center">
                            <div class="w-8 h-8 sm:w-10 sm:h-10 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-semibold mr-2 sm:mr-3 text-sm">
                                ${clientName.charAt(0)}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="font-semibold text-gray-900 text-sm sm:text-base truncate">${clientName}</div>
                                <div class="text-xs sm:text-sm text-gray-600 truncate">
                                    ${client.email ? JSON.parse(client.email).primary || 'No email' : 'No email'}
                                </div>
                                <div class="text-xs text-gray-500 mt-0.5">
                                    Work: <span class="font-medium">${work.title}</span>
                                </div>
                            </div>
                        </div>
                    `;
                }
                
                if (task) {
                    loadTaskFiles(task);
                }
            } catch (error) {
                console.error('Error loading client data:', error);
            }
        }

        // ============================================================
        // HELPERS
        // ============================================================
        function extractIds(value) {
            if (!value || typeof value !== 'string') return null;
            const parts = value.split('|').map(v => v.trim());
            if (parts.length < 2) return null;
            return { sys_id: parts[0] || null, name: parts[1] || null };
        }

        function setupTypeToggle() {
            const vendorRadio = document.getElementById('type-vendor');
            const ownRadio = document.getElementById('type-own');
            const vendorSection = document.getElementById('vendorSection');
            const accountSection = document.getElementById('accountSection');
            
            function toggle() {
                const isVendor = vendorRadio.checked;
                vendorSection.classList.toggle('hidden', !isVendor);
                accountSection.classList.toggle('hidden', isVendor);
            }
            
            toggle();
            vendorRadio.addEventListener('change', toggle);
            ownRadio.addEventListener('change', toggle);
        }

        function buildDateTime(dateOnly) {
            const now = new Date();
            const time = String(now.getHours()).padStart(2,'0') + ':' +
                         String(now.getMinutes()).padStart(2,'0') + ':' +
                         String(now.getSeconds()).padStart(2,'0');
            return `${dateOnly} ${time}`;
        }

        // ============================================================
        // RECORD / REFUND TRANSACTIONS
        // ============================================================
        async function recordTransaction(type) {
            try {
                if (type === 'debit') {
                    const purpose = document.getElementById('client_purpose').value.trim();
                    const amount = parseFloat(document.getElementById('client_amount').value);
                    const date = document.getElementById('client_date').value;
                    const ref = document.getElementById('client_note').value;
                    const qtyRate = buildQtyRate('client_qty', 'client_rate');

                    if (!currentClientId) {
                        showNotification('Client ID not found', 'error');
                        return;
                    }
                    if (!purpose || !amount || amount <= 0) {
                        showNotification('Please enter valid purpose and amount', 'error');
                        return;
                    }

                    // ref তে vendor/account info যোগ ("Name || SysID")
                    let saleFromRef = ref || '';
                    const vendorTypeForRef = document.querySelector('input[name="account_type"]:checked')?.value;
                    if (vendorTypeForRef === 'vendor') {
                        const vVal = document.getElementById('vendorInput')?.value;
                        if (vVal) {
                            const vd = extractIds(vVal);
                            if (vd?.sys_id) saleFromRef = `${vd.name || ''} || ${vd.sys_id}`;
                        }
                    } else if (vendorTypeForRef === 'own') {
                        const aVal = document.getElementById('accountInput')?.value;
                        if (aVal) {
                            const ad = extractIds(aVal);
                            if (ad?.sys_id) saleFromRef = `${ad.name || ''} || ${ad.sys_id}`;
                        }
                    }
                    await saveTransaction({
                        type: 'debit',
                        amount: amount,
                        purpose: purpose,
                        client_id: currentClientId,
                        work_id: WORK_ID,
                        task_id: TASK_ID,
                        date: buildDateTime(date),
                        ref: saleFromRef,
                        qty_rate: qtyRate
                    }, 'Debit');

                    document.getElementById('client_purpose').value = '';
                    document.getElementById('client_amount').value = '';
                    document.getElementById('client_qty').value = '';
                    document.getElementById('client_rate').value = '';

                } else if (type === 'credit') {
                    const purpose = document.getElementById('vendor_purpose').value.trim();
                    const amount = parseFloat(document.getElementById('vendor_amount').value);
                    const date = document.getElementById('vendor_date').value;
                    const vendorType = document.querySelector('input[name="account_type"]:checked').value;
                    const qtyRate = buildQtyRate('vendor_qty', 'vendor_rate');
                    
                    let vendorId = null;
                    let accountId = null;
                    
                    if (vendorType === 'vendor') {
                        const vendorValue = document.getElementById('vendorInput').value;
                        if (!vendorValue) {
                            showNotification('Please select a vendor', 'error');
                            return;
                        }
                        const vendorData = extractIds(vendorValue);
                        if (!vendorData || !vendorData.sys_id) {
                            showNotification('Invalid vendor selected', 'error');
                            return;
                        }
                        vendorId = vendorData.sys_id;
                    } else if (vendorType === 'own') {
                        const accountValue = document.getElementById('accountInput').value;
                        if (!accountValue) {
                            showNotification('Please select an account', 'error');
                            return;
                        }
                        const accountData = extractIds(accountValue);
                        if (!accountData || !accountData.sys_id) {
                            showNotification('Invalid account selected', 'error');
                            return;
                        }
                        accountId = accountData.sys_id;
                    }

                    if (!purpose || !amount || amount <= 0) {
                        showNotification('Please enter valid purpose and amount', 'error');
                        return;
                    }

                    // ref তে client info যোগ ("Name || SysID")
                    const clientRefForPurchase = clientName
                        ? `${clientName} || ${currentClientId}`
                        : currentClientId;

                    const transactionData = {
                        type: 'credit',
                        amount: amount,
                        purpose: purpose,
                        vendor_type: vendorType === 'vendor' ? 0 : 1,
                        work_id: WORK_ID,
                        task_id: TASK_ID,
                        date: buildDateTime(date),
                        qty_rate: qtyRate,
                        ref: clientRefForPurchase
                    };

                    if (vendorType === 'vendor' && vendorId) {
                        transactionData.vendor_id = vendorId;
                    } else if (vendorType === 'own' && accountId) {
                        transactionData.account_id = accountId;
                    }

                    await saveTransaction(transactionData, 'Credit');

                    document.getElementById('vendor_purpose').value = '';
                    document.getElementById('vendor_amount').value = '';
                    document.getElementById('vendor_qty').value = '';
                    document.getElementById('vendor_rate').value = '';
                    document.getElementById('vendorInput').value = '';
                    document.getElementById('accountInput').value = '';
                }

            } catch (error) {
                console.error('Error recording transaction:', error);
                showNotification('An error occurred', 'error');
            }
        }

        async function refundTransaction(type) {
            try {
                if (type === 'credit') {
                    const purpose = document.getElementById('client_purpose').value.trim();
                    const amount = parseFloat(document.getElementById('client_amount').value);
                    const date = document.getElementById('client_date').value;
                    const ref = document.getElementById('client_note').value;
                    const qtyRate = buildQtyRate('client_qty', 'client_rate');

                    if (!currentClientId || !purpose || !amount || amount <= 0) {
                        showNotification('Please enter valid purpose and amount', 'error');
                        return;
                    }

                    // Refund ref — vendor/account info
                    let refundFromRef = ref || '';
                    const vTypeRefund = document.querySelector('input[name="account_type"]:checked')?.value;
                    if (vTypeRefund === 'vendor') {
                        const vVal = document.getElementById('vendorInput')?.value;
                        if (vVal) { const vd = extractIds(vVal); if (vd?.sys_id) refundFromRef = `${vd.name||''} || ${vd.sys_id}`; }
                    } else if (vTypeRefund === 'own') {
                        const aVal = document.getElementById('accountInput')?.value;
                        if (aVal) { const ad = extractIds(aVal); if (ad?.sys_id) refundFromRef = `${ad.name||''} || ${ad.sys_id}`; }
                    }
                    await saveTransaction({
                        type: 'credit',
                        amount: amount,
                        purpose: purpose,
                        client_id: currentClientId,
                        work_id: WORK_ID,
                        task_id: TASK_ID,
                        date: buildDateTime(date),
                        ref: refundFromRef,
                        qty_rate: qtyRate
                    }, 'Credit (Refund)');

                    document.getElementById('client_purpose').value = '';
                    document.getElementById('client_amount').value = '';
                    document.getElementById('client_qty').value = '';
                    document.getElementById('client_rate').value = '';

                } else if (type === 'debit') {
                    const purpose = document.getElementById('vendor_purpose').value.trim();
                    const amount = parseFloat(document.getElementById('vendor_amount').value);
                    const date = document.getElementById('vendor_date').value;
                    const vendorType = document.querySelector('input[name="account_type"]:checked').value;
                    const qtyRate = buildQtyRate('vendor_qty', 'vendor_rate');
                    
                    let vendorId = null;
                    let accountId = null;
                    
                    if (vendorType === 'vendor') {
                        const vendorValue = document.getElementById('vendorInput').value;
                        if (!vendorValue) {
                            showNotification('Please select a vendor', 'error');
                            return;
                        }
                        const vendorData = extractIds(vendorValue);
                        if (!vendorData || !vendorData.sys_id) {
                            showNotification('Invalid vendor selected', 'error');
                            return;
                        }
                        vendorId = vendorData.sys_id;
                    } else if (vendorType === 'own') {
                        const accountValue = document.getElementById('accountInput').value;
                        if (!accountValue) {
                            showNotification('Please select an account', 'error');
                            return;
                        }
                        const accountData = extractIds(accountValue);
                        if (!accountData || !accountData.sys_id) {
                            showNotification('Invalid account selected', 'error');
                            return;
                        }
                        accountId = accountData.sys_id;
                    }

                    if (!purpose || !amount || amount <= 0) {
                        showNotification('Please enter valid purpose and amount', 'error');
                        return;
                    }

                    // Refund ref — client info
                    const clientRefForRefund = clientName
                        ? `${clientName} || ${currentClientId}`
                        : currentClientId;

                    const transactionData = {
                        type: 'debit',
                        amount: amount,
                        purpose: purpose,
                        vendor_type: vendorType === 'vendor' ? 0 : 1,
                        work_id: WORK_ID,
                        task_id: TASK_ID,
                        date: buildDateTime(date),
                        qty_rate: qtyRate,
                        ref: clientRefForRefund
                    };

                    if (vendorType === 'vendor' && vendorId) {
                        transactionData.vendor_id = vendorId;
                    } else if (vendorType === 'own' && accountId) {
                        transactionData.account_id = accountId;
                    }

                    await saveTransaction(transactionData, 'Debit (Refund)');

                    document.getElementById('vendor_purpose').value = '';
                    document.getElementById('vendor_amount').value = '';
                    document.getElementById('vendor_qty').value = '';
                    document.getElementById('vendor_rate').value = '';
                    document.getElementById('vendorInput').value = '';
                    document.getElementById('accountInput').value = '';
                }

            } catch (error) {
                console.error('Error recording refund:', error);
                showNotification('An error occurred', 'error');
            }
        }

        async function saveTransaction(data, type) {
            try {
                const response = await fetch(FINANCIAL_ENTRIES_STORE_API, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    showNotification(`${type} transaction recorded successfully!`, 'success');
                    addRecentActivity(data);

                    // File upload if any
                    const fileInputId = data.type === 'debit' ? 'clientFiles' : 'vendorFiles';
                    const labelId     = data.type === 'debit' ? 'clientFileLabel' : 'vendorFileLabel';
                    const fileInput   = document.getElementById(fileInputId);
                    const entrySysId  = result.sys_id;

                    if (entrySysId && fileInput?.files?.length > 0) {
                        await uploadFinEntryFile(fileInput.files, entrySysId, data.type);
                        fileInput.value = '';
                        document.getElementById(labelId).textContent = 'Browse or drop files';
                    }

                    loadFinancialData();
                    _renderFinancialFileChips();
                } else {
                    showNotification('Error: ' + (result.message || 'Unknown error'), 'error');
                }
            } catch (error) {
                console.error('Error saving transaction:', error);
                showNotification('Network error occurred', 'error');
            }
        }

        // ============================================================
        // FINANCIAL DATA - FIXED FOR PRINT
        // ============================================================
        async function loadFinancialData() {
            try {
                const response = await fetch(GET_FINANCIAL_STATEMENT_API);
                const data = await response.json();

                if (data.success) {
                    allTransactions = data.finStmts || [];
                    renderFinTable(allTransactions);
                    updateFinancialSummary(allTransactions);
                    updateQuickStats(allTransactions);
                }
            } catch (error) {
                console.error('Error loading financial data:', error);
            }
        }

        function renderFinTable(transactions) {
            const tableBody = document.getElementById('finTableBody');
            const isMobile = window.innerWidth < 640 && !window.matchMedia('print').matches;

            if (transactions.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="9" class="px-4 py-6 text-center text-gray-500">
                            <i class="fas fa-wallet text-2xl mb-2 block"></i>
                            <p>No transactions yet</p>
                        </td>
                    </tr>
                `;
                return;
            }

            // Desktop view - always show full table
            tableBody.innerHTML = transactions.map(t => {
                const type = (t.type || '').toLowerCase();
                const isDebit = type === 'debit';
                const typeBadge = isDebit ? 
                    '<span class="type-badge type-debit">DEBIT</span>' : 
                    '<span class="type-badge type-credit">CREDIT</span>';
                const amtClass = isDebit ? 'amount-debit' : 'amount-credit';
                const amtPrefix = isDebit ? '+' : '-';

                let qtyCell = '—', rateCell = '—';
                if (t.qty_rate) {
                    try {
                        const qr = typeof t.qty_rate === 'string' ? JSON.parse(t.qty_rate) : t.qty_rate;
                        if (qr.qty) qtyCell = parseFloat(qr.qty).toFixed(2);
                        if (qr.rate) rateCell = '৳' + parseFloat(qr.rate).toFixed(2);
                    } catch(e) {}
                }

                return `
                    <tr class="transaction-row">
                        <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-900">${t.date || 'N/A'}</td>
                        <td class="px-3 py-3 text-sm font-medium text-gray-900">${t.purpose || 'No Data'}</td>
                        <td class="px-3 py-3 text-sm text-gray-500 hide-mobile">${t.ref || '—'}</td>
                        <td class="px-3 py-3 text-sm text-gray-900 hide-mobile">${t.user_name || 'N/A'}</td>
                        <td class="px-3 py-3 whitespace-nowrap">${typeBadge}</td>
                        <td class="px-3 py-3 whitespace-nowrap text-sm text-right text-gray-600 hide-mobile">${qtyCell}</td>
                        <td class="px-3 py-3 whitespace-nowrap text-sm text-right text-gray-600 hide-mobile">${rateCell}</td>
                        <td class="px-3 py-3 whitespace-nowrap text-sm text-right font-semibold ${amtClass}">
                            ${amtPrefix} ৳${parseFloat(t.amount||0).toFixed(2)}
                        </td>
                        <td class="px-3 py-3 whitespace-nowrap text-sm no-print">
                            <button onclick="editTransaction(${t.id})" class="px-2 py-1 bg-blue-50 hover:bg-blue-100 text-blue-600 rounded-lg mr-1">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteTransaction(${t.id})" class="px-2 py-1 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg mr-1">
                                <i class="fas fa-trash"></i>
                            </button>
                            <label class="px-2 py-1 bg-orange-50 hover:bg-orange-100 text-orange-600 rounded-lg cursor-pointer inline-flex items-center" title="Attach file">
                                <i class="fas fa-paperclip"></i>
                                <input type="file" multiple class="hidden" onchange="uploadFinEntryFile(this.files, '${t.sys_id}', '${t.type}', this)">
                            </label>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function updateFinancialSummary(transactions) {
            const totalDebit = transactions.filter(t => t.type.toLowerCase() === 'debit')
                .reduce((s,t) => s + parseFloat(t.amount||0), 0);
            const totalCredit = transactions.filter(t => t.type.toLowerCase() === 'credit')
                .reduce((s,t) => s + parseFloat(t.amount||0), 0);
            const netBalance = totalDebit - totalCredit;
            const balClass = netBalance > 0 ? 'balance-positive' : netBalance < 0 ? 'balance-negative' : 'balance-neutral';

            financialSummary.innerHTML = `
                <div class="summary-card p-3 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-600">Total Debit</p>
                            <p class="text-base sm:text-xl font-bold text-green-600">৳${totalDebit.toFixed(2)}</p>
                        </div>
                        <i class="fas fa-arrow-down text-green-500 text-lg"></i>
                    </div>
                </div>
                <div class="summary-card p-3 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-600">Total Credit</p>
                            <p class="text-base sm:text-xl font-bold text-red-600">৳${totalCredit.toFixed(2)}</p>
                        </div>
                        <i class="fas fa-arrow-up text-red-500 text-lg"></i>
                    </div>
                </div>
                <div class="summary-card p-3 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-600">Net Balance</p>
                            <p class="text-base sm:text-xl font-bold ${balClass}">৳${Math.abs(netBalance).toFixed(2)}</p>
                            <p class="text-xs ${balClass} mt-0.5">${netBalance >= 0 ? 'Profit' : 'Loss'}</p>
                        </div>
                        <i class="fas fa-balance-scale ${netBalance>=0?'text-green-500':'text-red-500'} text-lg"></i>
                    </div>
                </div>
            `;
        }

        function updateQuickStats(transactions) {
            document.getElementById('totalTransactions').textContent = transactions.length;
            document.getElementById('transactionProgress').style.width = `${Math.min((transactions.length/10)*100,100)}%`;
            
            const dc = transactions.filter(t => t.type.toLowerCase() === 'debit').length;
            const cc = transactions.filter(t => t.type.toLowerCase() === 'credit').length;
            document.getElementById('debitCreditRatio').textContent = `${dc}:${cc}`;
            
            const total = dc + cc || 1;
            document.getElementById('debitBar').style.width = `${(dc/total)*100}%`;
            document.getElementById('creditBar').style.width = `${(cc/total)*100}%`;
            
            const td = transactions.filter(t => t.type.toLowerCase() === 'debit')
                .reduce((s,t) => s + parseFloat(t.amount||0), 0);
            const tc = transactions.filter(t => t.type.toLowerCase() === 'credit')
                .reduce((s,t) => s + parseFloat(t.amount||0), 0);
            const nb = td - tc;
            
            document.getElementById('netBalance').textContent = `৳${nb.toFixed(2)}`;
            document.getElementById('netBalance').className = `text-xl sm:text-2xl font-bold mb-1 ${nb>0?'text-green-600':nb<0?'text-red-600':'text-gray-600'}`;
        }

        function addRecentActivity(transaction) {
            const now = new Date();
            const timeString = now.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
            
            const activityItem = document.createElement('div');
            activityItem.className = 'flex items-start space-x-2 p-2 hover:bg-gray-50 rounded-lg';
            activityItem.innerHTML = `
                <div class="flex-shrink-0">
                    <div class="w-6 h-6 sm:w-8 sm:h-8 rounded-full ${transaction.type==='debit'?'bg-green-100':'bg-red-100'} flex items-center justify-center">
                        <i class="fas fa-${transaction.type==='debit'?'plus':'minus'} ${transaction.type==='debit'?'text-green-600':'text-red-600'} text-xs sm:text-sm"></i>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-xs sm:text-sm font-medium text-gray-900">${transaction.type.toUpperCase()} Recorded</div>
                    <div class="text-xs text-gray-500 truncate">${transaction.purpose}</div>
                    <div class="text-xs text-gray-400 mt-0.5">৳${transaction.amount} • ${timeString}</div>
                </div>
            `;

            if (recentActivity.firstChild) {
                recentActivity.insertBefore(activityItem, recentActivity.firstChild);
            } else {
                recentActivity.appendChild(activityItem);
            }

            const items = recentActivity.querySelectorAll('div.flex.items-start');
            if (items.length > 5) {
                items[items.length-1].remove();
            }
        }

        // ============================================================
        // NOTIFICATION
        // ============================================================
        function showNotification(message, type = 'info') {
            const colors = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                info: 'bg-blue-500'
            };
            const icons = {
                success: 'fa-check-circle',
                error: 'fa-exclamation-circle',
                info: 'fa-info-circle'
            };

            const notification = document.createElement('div');
            notification.className = `fixed top-20 right-2 sm:right-4 z-50 px-3 sm:px-6 py-2 sm:py-3 rounded-lg shadow-lg transform transition-all duration-300 ${colors[type] || colors.info} text-white text-xs sm:text-sm max-w-[90vw] sm:max-w-md`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas ${icons[type] || icons.info} mr-2"></i>
                    <span>${message}</span>
                </div>
            `;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // ============================================================
        // EVENT LISTENERS
        // ============================================================
        function setupEventListeners() {
            document.getElementById('client_amount').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') recordTransaction('debit');
            });
            document.getElementById('vendor_amount').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') recordTransaction('credit');
            });
            setupTypeToggle();
        }

        // ============================================================
        // EDIT TRANSACTION
        // ============================================================
        async function editTransaction(id) {
            try {
                const transaction = allTransactions.find(t => t.id == id);
                if (!transaction) {
                    showNotification('Transaction not found', 'error');
                    return;
                }

                document.getElementById('edit_transaction_id').value = transaction.id;
                document.getElementById('edit_original_type').value = transaction.type;
                document.getElementById('edit_purpose').value = transaction.purpose || '';
                document.getElementById('edit_amount').value = transaction.amount;
                
                document.getElementById('edit_qty').value = '';
                document.getElementById('edit_rate').value = '';
                if (transaction.qty_rate) {
                    try {
                        const qr = typeof transaction.qty_rate === 'string' 
                            ? JSON.parse(transaction.qty_rate) 
                            : transaction.qty_rate;
                        if (qr.qty) document.getElementById('edit_qty').value = qr.qty;
                        if (qr.rate) document.getElementById('edit_rate').value = qr.rate;
                    } catch(e) {}
                }
                
                if (transaction.date) {
                    const dateStr = transaction.date.replace(' ', 'T');
                    document.getElementById('edit_date').value = dateStr.substring(0, 16);
                }
                
                const typeDisplay = document.getElementById('edit_type_display');
                const type = transaction.type.toLowerCase();
                typeDisplay.innerHTML = type === 'debit' ? 
                    '<span class="text-green-600 font-semibold">DEBIT (Client Deposit)</span>' : 
                    '<span class="text-red-600 font-semibold">CREDIT (Vendor Payment)</span>';
                
                const selectionContainer = document.getElementById('edit_selection_container');
                if (type === 'credit') {
                    selectionContainer.classList.remove('hidden');
                    
                    const isVendor = transaction.vendor_type == 0;
                    document.getElementById('edit_vendor_type').value = isVendor ? 'vendor' : 'own';
                    
                    document.querySelector('input[name="edit_account_type"][value="vendor"]').checked = isVendor;
                    document.querySelector('input[name="edit_account_type"][value="own"]').checked = !isVendor;
                    
                    await loadEditSelectionContainers();
                    toggleEditSelection();
                } else {
                    selectionContainer.classList.add('hidden');
                }
                
                document.getElementById('editModal').classList.remove('hidden');
                
            } catch (error) {
                console.error('Error loading transaction for edit:', error);
                showNotification('Error loading transaction details', 'error');
            }
        }

        async function loadEditSelectionContainers() {
            const vc = document.getElementById('edit_vendor_container');
            const ac = document.getElementById('edit_account_container');
            
            if (vc.children.length === 0) {
                const r = await fetch('form-selects/vendors-edit.php');
                vc.innerHTML = await r.text();
                if (typeof loadEditVendors === 'function') loadEditVendors();
            }
            
            if (ac.children.length === 0) {
                const r = await fetch('form-selects/accounts-edit.php');
                ac.innerHTML = await r.text();
                if (typeof loadEditAccounts === 'function') loadEditAccounts();
            }
        }

        function toggleEditSelection() {
            const type = document.querySelector('input[name="edit_account_type"]:checked')?.value;
            document.getElementById('edit_vendor_container').classList.toggle('hidden', type !== 'vendor');
            document.getElementById('edit_account_container').classList.toggle('hidden', type !== 'own');
            
            if (type === 'vendor' && typeof setupEditVendorSearch === 'function') {
                setTimeout(() => setupEditVendorSearch(), 100);
            }
            if (type === 'own' && typeof setupEditAccountSearch === 'function') {
                setTimeout(() => setupEditAccountSearch(), 100);
            }
        }

        async function updateTransaction(event) {
            event.preventDefault();
            
            try {
                const transactionId = document.getElementById('edit_transaction_id').value;
                const originalType = document.getElementById('edit_original_type').value;
                const purpose = document.getElementById('edit_purpose').value.trim();
                const amount = parseFloat(document.getElementById('edit_amount').value);
                const dateTime = document.getElementById('edit_date').value.replace('T', ' ');
                const qtyRate = buildQtyRate('edit_qty', 'edit_rate');
                
                if (!purpose || !amount || amount <= 0) {
                    showNotification('Please enter valid purpose and amount', 'error');
                    return;
                }
                
                const updateData = {
                    id: transactionId,
                    purpose: purpose,
                    amount: amount,
                    date: dateTime,
                    qty_rate: qtyRate
                };
                
                if (originalType.toLowerCase() === 'credit') {
                    const accountType = document.querySelector('input[name="edit_account_type"]:checked')?.value;
                    
                    if (accountType === 'vendor') {
                        const vendorInput = document.getElementById('editVendorInput')?.value;
                        if (!vendorInput) {
                            showNotification('Please select a vendor', 'error');
                            return;
                        }
                        const vendorData = extractIds(vendorInput);
                        if (vendorData && vendorData.sys_id) {
                            updateData.vendor_id = vendorData.sys_id;
                            updateData.vendor_type = 0;
                        }
                    } else if (accountType === 'own') {
                        const accountInput = document.getElementById('editAccountInput')?.value;
                        if (!accountInput) {
                            showNotification('Please select an account', 'error');
                            return;
                        }
                        const accountData = extractIds(accountInput);
                        if (accountData && accountData.sys_id) {
                            updateData.account_id = accountData.sys_id;
                            updateData.vendor_type = 1;
                        }
                    }
                }
                
                const response = await fetch(UPDATE_FINANCIAL_ENTRY_API, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(updateData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('Transaction updated successfully!', 'success');
                    closeEditModal();
                    loadFinancialData();
                } else {
                    showNotification('Error: ' + (result.message || 'Failed to update'), 'error');
                }
                
            } catch (error) {
                console.error('Error updating transaction:', error);
                showNotification('Network error occurred', 'error');
            }
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
            document.getElementById('editTransactionForm').reset();
        }

        function deleteTransaction(id) {
            if (confirm('Are you sure you want to delete this transaction?')) {
                showNotification('Delete functionality coming soon', 'info');
            }
        }

        // ============================================================
        // MISC FUNCTIONS
        // ============================================================
        function reloadFinancialTable() {
            loadFinancialData();
            showNotification('Data refreshed', 'success');
        }

        function scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function closePreview() {
            document.getElementById('previewModal').classList.add('hidden');
        }

        // ── Upload financial entry file (table 📎 + form submit) ──
        async function uploadFinEntryFile(files, entrySysId, entryType, inputEl) {
            if (!files || files.length === 0 || !entrySysId) return;

            const entityType = entryType.toLowerCase() === 'debit' ? 'receive' : 'payment';
            const fd = new FormData();
            fd.append('entity_type', entityType);
            fd.append('entity_id', entrySysId);
            fd.append('work_sys_id', WORK_ID);
            fd.append('task_sys_id', TASK_ID);
            for (const f of files) fd.append('files[]', f);

            try {
                const res  = await fetch(UPLOAD_FIN_FILE_API, { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    showNotification('File attached!', 'success');
                    _renderFinancialFileChips();
                } else {
                    showNotification('Attach failed: ' + (data.message || ''), 'error');
                }
            } catch (e) {
                showNotification('Upload error', 'error');
            } finally {
                if (inputEl) inputEl.value = ''; // Clear so same file can be re-selected
            }
        }
        // ============================================================
        document.addEventListener('DOMContentLoaded', function() {
            loadTaskMetaData();
            loadClientData();
            loadFinancialData();
            setupEventListeners();
            setupQtyRateCalc();
            s1LoadFiles();
            _renderFinancialFileChips();
        });
    </script>
</body>
</html>