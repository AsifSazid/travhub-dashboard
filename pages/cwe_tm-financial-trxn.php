<?php
include_once('./authenticate.php');
$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898";
}

$workId = $_GET['work_id'];
$taskId = $_GET['task_id'];

$getClientsApi = $ip_port . "api/clients/get-client-info.php?work_id=$workId";
$getTaskFinEntriesApi = $ip_port . "api/financial_entries/task-fin-entries.php?task_id=$taskId";
$storeFinancialEntriesApi = $ip_port . "api/financial_entries/store.php";
$getTaskApi = $ip_port . "api/tasks/task-details.php?task_id=$taskId";

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Transaction - Task Management</title>
    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png" sizes="16x16">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
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

    <main id="mainContent" class="pt-16 pl-64 transition-all duration-300">
        <!-- Task Overview Section -->
        <div class="p-6">
            <div class="bg-white rounded-xl shadow-lg p-6 mb-6 border border-gray-100">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Financial Transaction Management</h1>
                        <p class="text-gray-600 mt-1">Manage all financial transactions for this task</p>
                    </div>
                    <div class="mt-4 lg:mt-0 flex space-x-3">
                        <a href="task-entry.php?work_id=<?php echo $workId; ?>"
                            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors flex items-center">
                            <i class="fas fa-arrow-left mr-2"></i> Back to Tasks
                        </a>
                        <button onclick="printPage()"
                            class="px-4 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg transition-colors flex items-center">
                            <i class="fas fa-print mr-2"></i> Print Report
                        </button>
                        <a href="create-vendor.php"
                            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors flex items-center">
                            <i class="fas fa-plus mr-2"></i> Create a New Vendor
                        </a>
                    </div>
                </div>

                <!-- Task Meta Data Section -->
                <div id="taskMetaSection" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <!-- Loading skeleton -->
                    <div class="animate-pulse">
                        <div class="h-24 bg-gray-200 rounded-lg"></div>
                    </div>
                </div>

                <!-- File Attachments -->
                <div id="fileAttachments" class="mb-6">
                    <!-- Files will be loaded here -->
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="p-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column: Transaction Entry -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Transaction Entry Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Debit Card -->
                    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                        <div class="gradient-bg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-white">Client Deposit (Debit)</h3>
                                    <p class="text-blue-100 text-sm">Record client payments</p>
                                </div>
                                <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                                    <i class="fas fa-arrow-down text-white text-xl"></i>
                                </div>
                            </div>
                        </div>

                        <div class="p-5">
                            <div id="clientWorkInfo" class="mb-4 p-3 bg-blue-50 rounded-lg border border-blue-100">
                                <div class="flex items-center">
                                    <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                                    <span class="text-sm text-blue-700">Loading client information...</span>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        <i class="fas fa-bullseye mr-1"></i> Purpose
                                    </label>
                                    <textarea type="text"
                                        id="client_purpose"
                                        placeholder="e.g., Initial Payment, Final Payment, Extra Service" rows="6"
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"></textarea>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        <i class="fa-solid fa-highlighter mr-1"></i> Note
                                    </label>
                                    <textarea type="text"
                                        id="client_note"
                                        placeholder="e.g., Any types of Note that can help you future..." rows="2"
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"></textarea>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        <i class="fas fa-money-bill-wave mr-1"></i> Amount
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">৳</span>
                                        <input type="number"
                                            step="0.01"
                                            min="0.01"
                                            id="client_amount"
                                            placeholder="0.00"
                                            class="w-full pl-8 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        <i class="far fa-calendar mr-1"></i> Date
                                    </label>
                                    <input type="date"
                                        id="client_date"
                                        value="<?php echo date('Y-m-d'); ?>"
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200">
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <button onclick="refundTransaction('credit')"
                                        class="w-full mt-2 py-3 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-semibold rounded-lg transition-all duration-300 transform hover:-translate-y-0.5 hover:shadow-lg flex items-center justify-center">
                                        <i class="fas fa-plus-circle mr-2"></i> Refund
                                    </button>
                                    <button onclick="recordTransaction('debit')"
                                        class="w-full mt-2 py-3 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white font-semibold rounded-lg transition-all duration-300 transform hover:-translate-y-0.5 hover:shadow-lg flex items-center justify-center">
                                        <i class="fas fa-plus-circle mr-2"></i> Record Debit
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Credit Card -->
                    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                        <div class="bg-gradient-to-r from-green-500 to-emerald-600 p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-white">Vendor Payment (Credit)</h3>
                                    <p class="text-green-100 text-sm">Record vendor expenses</p>
                                </div>
                                <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                                    <i class="fas fa-arrow-up text-white text-xl"></i>
                                </div>
                            </div>
                        </div>

                        <div class="p-5">
                            <div class="mb-4">
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-3">
                                        <i class="fa-solid fa-shapes mr-1"></i> Select Type
                                    </label>
                                    
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <input type="radio" id="type-vendor" name="account_type" value="vendor" class="hidden peer" checked required>
                                            <label for="type-vendor" class="flex flex-col items-center justify-center p-4 text-gray-500 bg-white border-2 border-gray-200 rounded-lg cursor-pointer peer-checked:border-blue-600 peer-checked:text-blue-600 peer-checked:bg-blue-50 hover:bg-gray-50 transition-all">
                                                <i class="fa-solid fa-shop mb-2 text-2xl"></i>
                                                <span class="text-sm font-semibold">Vendor</span>
                                            </label>
                                        </div>
                                
                                        <div>
                                            <input type="radio" id="type-own" name="account_type" value="own" class="hidden peer">
                                            <label for="type-own" class="flex flex-col items-center justify-center p-4 text-gray-500 bg-white border-2 border-gray-200 rounded-lg cursor-pointer peer-checked:border-blue-600 peer-checked:text-blue-600 peer-checked:bg-blue-50 hover:bg-gray-50 transition-all">
                                                <i class="fa-solid fa-user mb-2 text-2xl"></i>
                                                <span class="text-sm font-semibold">Own Account</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Vendor section - visible by default -->
                            <div id="vendorSection" class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    <i class="fas fa-building mr-1"></i> Vendor
                                </label>
                                <?php include('form-selects/vendors.php') ?>
                            </div>
                            
                            <!-- Own Account section - hidden by default -->
                            <div id="accountSection" class="mb-4 hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    <i class="fas fa-building mr-1"></i> Own Account
                                </label>
                                <?php include('form-selects/accounts.php') ?>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        <i class="fas fa-bullseye mr-1"></i> Purpose
                                    </label>
                                    <textarea type="text"
                                        id="vendor_purpose"
                                        placeholder="e.g., Hotel Booking, Air Ticket, Service Fee" rows="6"
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200"></textarea>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        <i class="fas fa-money-bill-wave mr-1"></i> Amount
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">৳</span>
                                        <input type="number"
                                            step="0.01"
                                            min="0.01"
                                            id="vendor_amount"
                                            placeholder="0.00"
                                            class="w-full pl-8 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        <i class="far fa-calendar mr-1"></i> Date
                                    </label>
                                    <input type="date"
                                        id="vendor_date"
                                        value="<?php echo date('Y-m-d'); ?>"
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200">
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <button onclick="refundTransaction('debit')"
                                        class="w-full mt-2 py-3 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white font-semibold rounded-lg transition-all duration-300 transform hover:-translate-y-0.5 hover:shadow-lg flex items-center justify-center">
                                        <i class="fas fa-plus-circle mr-2"></i> Refund
                                    </button>
                                    <button onclick="recordTransaction('credit')"
                                        class="w-full mt-2 py-3 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-semibold rounded-lg transition-all duration-300 transform hover:-translate-y-0.5 hover:shadow-lg flex items-center justify-center">
                                        <i class="fas fa-plus-circle mr-2"></i> Record Credit
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Financial Summary -->
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">
                            <i class="fas fa-chart-line mr-2 text-blue-500"></i> Financial Summary
                        </h3>
                        <button onclick="reloadFinancialTable()"
                            class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors text-sm flex items-center">
                            <i class="fas fa-redo-alt mr-1"></i> Refresh
                        </button>
                    </div>

                    <div id="financialSummary" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <!-- Summary will be loaded here -->
                    </div>

                    <!-- Transactions Table -->
                    <div>
                        <div class="overflow-x-auto rounded-lg border border-gray-200">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Note</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client/Vendor</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="finTableBody" class="bg-white divide-y divide-gray-200">
                                    <!-- Transactions will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Stats and Info -->
            <div class="space-y-6">
                <!-- Task Info Card -->
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-5">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-info-circle mr-2 text-blue-500"></i> Task Information
                    </h3>

                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Task ID:</span>
                            <span class="text-sm font-semibold text-gray-900" id="taskIdDisplay"></span>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Task Name:</span>
                            <span class="text-sm font-semibold text-gray-900" id="taskNameDisplay"></span>
                        </div>

                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Category:</span>
                            <span class="text-sm font-semibold" id="taskCategory"></span>
                        </div>

                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Created:</span>
                            <span class="text-sm text-gray-900" id="taskCreated"></span>
                        </div>

                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Last Updated:</span>
                            <span class="text-sm text-gray-900" id="taskUpdated"></span>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-5">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-chart-bar mr-2 text-purple-500"></i> Quick Statistics
                    </h3>

                    <div class="space-y-4">
                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-sm text-gray-600">Total Transactions</span>
                                <span class="text-sm font-semibold text-gray-900" id="totalTransactions">0</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div id="transactionProgress" class="bg-blue-600 h-2 rounded-full" style="width: 0%"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-sm text-gray-600">Debit vs Credit Ratio</span>
                                <span class="text-sm font-semibold text-gray-900" id="debitCreditRatio">0:0</span>
                            </div>
                            <div class="flex h-2 rounded-full overflow-hidden">
                                <div id="debitBar" class="bg-red-500" style="width: 50%"></div>
                                <div id="creditBar" class="bg-green-500" style="width: 50%"></div>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-gray-100">
                            <div class="text-center">
                                <div class="text-2xl font-bold mb-1" id="netBalance">৳ 0.00</div>
                                <div class="text-sm text-gray-600">Net Balance</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-5">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-history mr-2 text-orange-500"></i> Recent Activity
                    </h3>

                    <div id="recentActivity" class="space-y-3">
                        <!-- Activity items will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Edit Transaction Modal -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-lg bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900" id="editModalTitle">Edit Transaction</h3>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form id="editTransactionForm" onsubmit="updateTransaction(event)">
                <input type="hidden" id="edit_transaction_id">
                <input type="hidden" id="edit_original_type">
                <input type="hidden" id="edit_vendor_type" value="">
                
                <!-- Transaction Type Display -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Transaction Type</label>
                    <div id="edit_type_display" class="px-4 py-2 bg-gray-100 rounded-lg text-gray-700 font-medium"></div>
                </div>
                
                <!-- Vendor/Account Selection Container (for credits only) -->
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
                    
                    <!-- Vendor Search Container (will be populated dynamically) -->
                    <div id="edit_vendor_container">
                        <?php include('form-selects/vendors-edit.php') ?>
                    </div>
                    
                    
                    <!-- Account Search Container (will be populated dynamically) -->
                    <div id="edit_account_container" class="hidden">
                        <?php include('form-selects/accounts-edit.php') ?>
                    </div>
                </div>
                
                <!-- Purpose -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Purpose</label>
                    <textarea id="edit_purpose" rows="3" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        required></textarea>
                </div>
                
                <!-- Amount -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">৳</span>
                        <input type="number" id="edit_amount" step="0.01" min="0.01"
                            class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            required>
                    </div>
                </div>
                
                <!-- Date -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                    <input type="datetime-local" id="edit_date"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        required>
                </div>
                
                <!-- Buttons -->
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeEditModal()"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                        <i class="fas fa-save mr-2"></i> Update Transaction
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Floating Quick Access Tab -->
    <?php include '../elements/floating-menus.php'; ?>

    <!-- Floating Action Button -->
    <div class="floating-action">
        <button onclick="scrollToTop()"
            class="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-full shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center">
            <i class="fas fa-arrow-up"></i>
        </button>
    </div>

    <script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>

    <script>
        const GET_CLIENT_API = "<?php echo $getClientsApi; ?>";
        const FINANCIAL_ENTRIES_STORE_API = "<?php echo $storeFinancialEntriesApi; ?>";
        const UPDATE_FINANCIAL_ENTRY_API = "<?php echo $ip_port; ?>api/financial_entries/update.php";
        const GET_FINANCIAL_STATEMENT_API = "<?php echo $getTaskFinEntriesApi; ?>";
        const GET_TASK_API = "<?php echo $getTaskApi; ?>";

        const WORK_ID = "<?php echo $workId; ?>";
        const TASK_ID = "<?php echo $taskId; ?>";

        let allTransactions = [];
        let currentClientId = null;
        let clientName = null;
        let task = null;
        let WORK_TITLE = '';
        let TASK_TITLE = '';

        // DOM Elements
        const taskMetaSection = document.getElementById('taskMetaSection');
        const fileAttachments = document.getElementById('fileAttachments');
        const financialSummary = document.getElementById('financialSummary');
        const recentActivity = document.getElementById('recentActivity');

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadTaskMetaData();
            loadClientData();
            loadFinancialData();
            setupEventListeners();
        });

        // Load Task Meta Data
        async function loadTaskMetaData() {
            try {
                const response = await fetch(GET_TASK_API);
                const data = await response.json();

                if (data.success && data.task) {
                    task = data.task;
                    const category = task.category;
                    
                    WORK_TITLE = task.work_title;
                    TASK_TITLE = task.title;
                    let text = '';
                    let raw = '';
                    let container = document.getElementById("client_purpose");
                    
                    if(category == 1){
                        raw = JSON.parse(task.air_ticket_info);
                        let purposeData = JSON.parse(raw[0]);
                        let purposes = purposeData.purpose;
                        
                        // Join korar somoy <br> er jaygay \n bebohar koro
                        text = purposes.map(p => {
                            return `${p.route}\n${p.passengers.join(", ")}\n${p.travel_date}\n${p.others.join(", ")}`;
                        }).join("\n\n");
                    }
                    
                    if(category == 2){
                        raw = JSON.parse(task.hotel_info);
                        let parseData = JSON.parse(raw[0]);

                        text = `${parseData.hotel_name},\n${parseData.hotel_city}, ${parseData.hotel_country}\n${parseData.guest_names.join(" | ")}\nC/In: ${parseData.check_in_date} | C/Out: ${parseData.check_out_date} | ${parseData.no_of_nights} Nights\n${parseData.room_info} | ${parseData.meal_plan} | ${parseData.total_rooms} Room('s)`;
                    }
                    
                    // Input field ba textarea hole .value use korte hoy, .innerHTML noy
                    container.value = text;

                    // Update task info card
                    document.getElementById('taskIdDisplay').textContent = task.sys_id || 'N/A';
                    document.getElementById('taskNameDisplay').textContent = task.title || 'N/A';

                    // Category
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
                    document.getElementById('taskCategory').classList.add(`text-${categoryColor}-600`);

                    // Meta data
                    const meta = task.meta_data ? JSON.parse(task.meta_data) : {};
                    const created = meta.created_by_date || {};
                    const updatedArray = meta.updated_by_date || [];
                    const lastUpdate = updatedArray.length > 0 ? updatedArray[updatedArray.length - 1] : null;

                    document.getElementById('taskCreated').textContent = created.date || 'N/A';
                    document.getElementById('taskUpdated').textContent = lastUpdate ? lastUpdate.date : 'N/A';

                    // Update task meta section
                    updateTaskMetaSection(task, meta);

                    // Load files
                    loadTaskFiles(task);
                }
            } catch (error) {
                console.error('Error loading task data:', error);
            }
        }

        function updateTaskMetaSection(task, meta) {
            const created = meta.created_by_date || {};
            const updatedArray = meta.updated_by_date || [];
            const lastUpdate = updatedArray.length > 0 ? updatedArray[updatedArray.length - 1] : null;

            taskMetaSection.innerHTML = `
                <div class="stat-card bg-gradient-to-r from-blue-50 to-blue-100 border-blue-200 p-4 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-blue-600 font-medium">Task ID</p>
                            <p class="text-xl font-bold text-blue-900">${task.sys_id || 'N/A'}</p>
                        </div>
                        <i class="fas fa-hashtag text-blue-400 text-2xl"></i>
                    </div>
                </div>
                
                <div class="stat-card bg-gradient-to-r from-green-50 to-green-100 border-green-200 p-4 rounded-lg">
                    <div class="items-center justify-between">
                        <div>
                            <p class="text-sm text-green-600 font-medium">Work Title</p>
                            <div class="relative group">
                                <p class="text-lg font-semibold text-green-900 truncate">
                                    ${task.work_title || 'N/A'}
                                </p>
                            
                                <div class="absolute left-0 top-full mt-1 hidden group-hover:block 
                                            bg-black text-white text-xs px-2 py-1 rounded z-10">
                                    ${task.work_title || 'N/A'}
                                </div>
                            </div>
                        </div>
                        <i class="fas fa-briefcase text-green-400 text-2xl"></i>
                    </div>
                </div>
                
                <div class="stat-card bg-gradient-to-r from-purple-50 to-purple-100 border-purple-200 p-4 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-purple-600 font-medium">Created By</p>
                            <p class="text-lg font-semibold text-purple-900 capitalize">${created.user || 'System'}</p>
                            <p class="text-xs text-purple-600">${created.date || ''}</p>
                        </div>
                        <i class="fas fa-user-plus text-purple-400 text-2xl"></i>
                    </div>
                </div>
                
                <div class="stat-card bg-gradient-to-r from-orange-50 to-orange-100 border-orange-200 p-4 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-orange-600 font-medium">${lastUpdate ? 'Last Updated' : 'No Updates'}</p>
                            ${lastUpdate ? `
                                <p class="text-lg font-semibold text-orange-900 capitalize">${lastUpdate.user}</p>
                                <p class="text-xs text-orange-600">${lastUpdate.date}</p>
                            ` : `
                                <p class="text-lg font-semibold text-orange-900">-</p>
                            `}
                        </div>
                        <i class="fas fa-sync-alt text-orange-400 text-2xl"></i>
                    </div>
                </div>
            `;
        }
        
        function loadTaskFiles(task) {
            
            if (!task || !clientName || !currentClientId) {
                console.warn('Task files skipped: client data not ready');
                return;
            }
    
            try {
                const files = task.all_file_name ? JSON.parse(task.all_file_name) : [];

                if (files.length > 0) {
                    const filesHTML = files.map((file) => {
                        const fileName = file.split('/').pop();
                        const fileExt = fileName.split('.').pop().toLowerCase();
                        let icon = 'fas fa-file';
                        let color = 'gray';

                        if (['pdf'].includes(fileExt)) {
                            icon = 'fas fa-file-pdf';
                            color = 'red';
                        } else if (['doc', 'docx'].includes(fileExt)) {
                            icon = 'fas fa-file-word';
                            color = 'blue';
                        } else if (['xls', 'xlsx'].includes(fileExt)) {
                            icon = 'fas fa-file-excel';
                            color = 'green';
                        } else if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExt)) {
                            icon = 'fas fa-file-image';
                            color = 'purple';
                        } else if (['zip', 'rar', '7z'].includes(fileExt)) {
                            icon = 'fas fa-file-archive';
                            color = 'yellow';
                        } else if (['txt', 'csv', 'json'].includes(fileExt)) {
                            icon = 'fas fa-file-alt';
                            color = 'indigo';
                        }
                        
                        // Escape special characters
                        const cleanClientName = clientName.replace(/\s+/g, '');
                        const cleanWorkTitle  = WORK_TITLE.replace(/\s+/g, '_');
                        // const cleanTaskTitle  = TASK_TITLE.replace(/\s+/g, '_');
                        const cleanTaskTitle  = TASK_TITLE;
                        

                        const safeFilePath =
                            `/storage/clients/${currentClientId}_${cleanClientName}/${WORK_ID}+${cleanWorkTitle}/tasks/${TASK_ID}+${cleanTaskTitle}/` +
                            file.replace(/'/g, "\\'").replace(/"/g, '\\"');
                            
                        return `
                            <a class="file-chip cursor-pointer hover:shadow-sm" 
                                 href="${safeFilePath}" target="_blank"
                                 title="${fileName}">
                                <i class="${icon} text-${color}-500 mr-1"></i>
                                <span class="truncate max-w-[150px]">${fileName}</span>
                            </a>
                            <button class="file-chip cursor-pointer hover:shadow-sm" >
                                <i class="fas fa-plus mr-1"></i>
                                <span class="truncate max-w-[150px]">Add New File</span>
                            </button>
                        `;
                    }).join('');

                    fileAttachments.innerHTML = `
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-sm font-medium text-gray-700">
                                <i class="fas fa-paperclip mr-1"></i> Attached Files (${files.length})
                            </h4>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            ${filesHTML}
                        </div>
                    `;
                } else {
                    fileAttachments.innerHTML = `
                <div class="text-center py-4 text-gray-500">
                    <i class="fas fa-file text-3xl mb-2"></i>
                    <p>No files attached to this task</p>
                </div>
            `;
                }
            } catch (error) {
                console.error('Error loading files:', error);
                fileAttachments.innerHTML = `
            <div class="text-center py-4 text-red-500">
                <i class="fas fa-exclamation-circle text-3xl mb-2"></i>
                <p>Error loading files</p>
            </div>
        `;
            }
        }
        
        // Load Client Data
        async function loadClientData() {
            try {
                const response = await fetch(GET_CLIENT_API);
                const data = await response.json();

                if (data.success) {
                    const client = data.client;
                    const work = data.work;

                    currentClientId = client.sys_id;

                    clientName = `${client.name}`;
                    const workName = work.title;

                    document.getElementById('clientWorkInfo').innerHTML = `
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-semibold mr-3">
                                ${clientName.charAt(0)}
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900">${clientName}</div>
                                    <div class="text-sm text-gray-600">
                                        ${client.email ? JSON.parse(client.email).primary || 'No email' : 'No email'} | 
                                        ${client.phone ? JSON.parse(client.phone).primary_no || 'No phone' : 'No phone'}
                                    </div>                                
                                    <div class="text-xs text-gray-500 mt-1">
                                    Work: <span class="font-medium">${workName}</span>
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
        
        function extractIds(value) {
            if (!value || typeof value !== 'string') return null;
            
            // Split by pipe and trim
            const parts = value.split('|').map(v => v.trim());
            
            // Ensure we have at least 2 parts (ID and Name)
            if (parts.length < 2) {
                // Try to extract ID from the beginning if format is different
                const idMatch = value.match(/^(\d+)/);
                if (idMatch) {
                    return {
                        sys_id: idMatch[1],
                        name: value.substring(idMatch[1].length).trim()
                    };
                }
                return null;
            }
            
            return {
                sys_id: parts[0] || null,
                name: parts[1] || null,
            };
        }
        
        // Toggle between vendor and account sections
        function setupTypeToggle() {
            const vendorRadio = document.getElementById('type-vendor');
            const ownRadio = document.getElementById('type-own');
            const vendorSection = document.getElementById('vendorSection');
            const accountSection = document.getElementById('accountSection');
            
            // Set initial state
            if (vendorRadio.checked) {
                vendorSection.classList.remove('hidden');
                accountSection.classList.add('hidden');
            } else {
                vendorSection.classList.add('hidden');
                accountSection.classList.remove('hidden');
            }
            
            // Add event listeners for radio buttons
            vendorRadio.addEventListener('change', function() {
                if (this.checked) {
                    vendorSection.classList.remove('hidden');
                    accountSection.classList.add('hidden');
                }
            });
            
            ownRadio.addEventListener('change', function() {
                if (this.checked) {
                    vendorSection.classList.add('hidden');
                    accountSection.classList.remove('hidden');
                }
            });
        }
        
        function buildDateTime(dateOnly) {
            const now = new Date();
        
            const time =
                String(now.getHours()).padStart(2, '0') + ':' +
                String(now.getMinutes()).padStart(2, '0') + ':' +
                String(now.getSeconds()).padStart(2, '0');
        
            return `${dateOnly} ${time}`;
        }

        // Record Transaction
        async function recordTransaction(type) {
            try {
                const workId = WORK_ID;
                const taskId = TASK_ID;

                if (type === 'debit') {
                    const purpose = document.getElementById('client_purpose').value.trim();
                    const amount = parseFloat(document.getElementById('client_amount').value);
                    const date = document.getElementById('client_date').value;
                    const ref = document.getElementById('client_note').value;

                    if (!currentClientId) {
                        showNotification('Client ID not found', 'error');
                        return;
                    }

                    if (!purpose || !amount || amount <= 0) {
                        showNotification('Please enter valid purpose and amount', 'error');
                        return;
                    }

                    const transactionData = {
                        type: 'debit',
                        amount: amount,
                        purpose: purpose,
                        client_id: currentClientId,
                        work_id: workId,
                        task_id: taskId,
                        date: buildDateTime(date),
                        ref: ref
                    };

                    await saveTransaction(transactionData, 'Debit');

                    // Clear form
                    document.getElementById('client_purpose').value = '';
                    document.getElementById('client_amount').value = '';

                } else if (type === 'credit') {
                    const purpose = document.getElementById('vendor_purpose').value.trim();
                    const amount = parseFloat(document.getElementById('vendor_amount').value);
                    const date = document.getElementById('vendor_date').value;
                    const vendorType = document.querySelector('input[name="account_type"]:checked').value;
                    
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

                    const transactionData = {
                        type: 'credit',
                        amount: amount,
                        purpose: purpose,
                        vendor_type: vendorType === 'vendor' ? 0 : 1, // 0 for Vendor, 1 for Own Account
                        work_id: workId,
                        task_id: taskId,
                        date: buildDateTime(date)
                    };

                    // Add vendor_id or account_id based on type
                    if (vendorType === 'vendor' && vendorId) {
                        transactionData.vendor_id = vendorId;
                    } else if (vendorType === 'own' && accountId) {
                        transactionData.account_id = accountId;
                    }
                    
                    console.log(transactionData);

                    await saveTransaction(transactionData, 'Credit');

                    // Clear form
                    document.getElementById('vendor_purpose').value = '';
                    document.getElementById('vendor_amount').value = '';
                    document.getElementById('vendorInput').value = '';
                    document.getElementById('accountInput').value = '';
                }

            } catch (error) {
                console.error('Error recording transaction:', error);
                showNotification('An error occurred while recording the transaction', 'error');
            }
        }
        
        async function refundTransaction(type) {
            try {
                const workId = WORK_ID;
                const taskId = TASK_ID;

                if (type === 'credit') {
                    const purpose = document.getElementById('client_purpose').value.trim();
                    const amount = parseFloat(document.getElementById('client_amount').value);
                    const date = document.getElementById('client_date').value;
                    const ref = document.getElementById('client_note').value;

                    if (!currentClientId) {
                        showNotification('Client ID not found', 'error');
                        return;
                    }

                    if (!purpose || !amount || amount <= 0) {
                        showNotification('Please enter valid purpose and amount', 'error');
                        return;
                    }

                    const transactionData = {
                        type: 'credit',
                        amount: amount,
                        purpose: purpose,
                        client_id: currentClientId,
                        work_id: workId,
                        task_id: taskId,
                        date: buildDateTime(date),
                        ref: ref
                    };

                    await saveTransaction(transactionData, 'Credit');

                    // Clear form
                    document.getElementById('client_purpose').value = '';
                    document.getElementById('client_amount').value = '';

                } else if (type === 'debit') {
                    const purpose = document.getElementById('vendor_purpose').value.trim();
                    const amount = parseFloat(document.getElementById('vendor_amount').value);
                    const date = document.getElementById('vendor_date').value;
                    const vendorType = document.querySelector('input[name="account_type"]:checked').value;
                    
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

                    const transactionData = {
                        type: 'debit',
                        amount: amount,
                        purpose: purpose,
                        vendor_type: vendorType === 'vendor' ? 0 : 1, // 0 for Vendor, 1 for Own Account
                        work_id: workId,
                        task_id: taskId,
                        date: buildDateTime(date)
                    };

                    // Add vendor_id or account_id based on type
                    if (vendorType === 'vendor' && vendorId) {
                        transactionData.vendor_id = vendorId;
                    } else if (vendorType === 'own' && accountId) {
                        transactionData.account_id = accountId;
                    }
                    
                    console.log(transactionData);

                    await saveTransaction(transactionData, 'Debit');

                    // Clear form
                    document.getElementById('vendor_purpose').value = '';
                    document.getElementById('vendor_amount').value = '';
                    document.getElementById('vendorInput').value = '';
                    document.getElementById('accountInput').value = '';
                }

            } catch (error) {
                console.error('Error recording transaction:', error);
                showNotification('An error occurred while recording the transaction', 'error');
            }
        }

        async function saveTransaction(data, type) {
                // console.log(data);
            try {
                const response = await fetch(FINANCIAL_ENTRIES_STORE_API, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    showNotification(`${type} transaction recorded successfully!`, 'success');

                    // Add to recent activity
                    addRecentActivity(data);

                    // Reload financial data
                    loadFinancialData();
                } else {
                    showNotification('Error: ' + (result.message || 'Unknown error'), 'error');
                }
            } catch (error) {
                console.error('Error saving transaction:', error);
                showNotification('Network error occurred', 'error');
            }
        }

        // Load Financial Data
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

            if (transactions.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-wallet text-3xl mb-2"></i>
                            <p class="text-lg">No transactions yet</p>
                            <p class="text-sm mt-1">Record your first transaction above</p>
                        </td>
                    </tr>
                `;
                return;
            }

            tableBody.innerHTML = transactions.map(transaction => {
                const type = (transaction.type || '').toLowerCase();
                const isDebit = type === 'debit';
                const isCredit = type === 'credit';

                const typeBadge = isDebit ?
                    '<span class="type-badge type-debit">DEBIT</span>' :
                    isCredit ?
                    '<span class="type-badge type-credit">CREDIT</span>' :
                    '<span class="type-badge">UNKNOWN</span>';

                const amountClass = isDebit ? 'amount-debit' : 'amount-credit';
                const amountPrefix = isDebit ? '+' : '-';

                return `
                    <tr class="transaction-row">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">${transaction.date || 'N/A'}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">${transaction.purpose || 'No Data'}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900">${transaction.ref || 'Unknown'}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900"> ${transaction.user_name || 'N/A'}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">${typeBadge}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-semibold ${amountClass}">
                                ${amountPrefix} ৳${parseFloat(transaction.amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <button onclick="editTransaction(${transaction.id})" 
                                    class="px-3 py-1 bg-blue-50 hover:bg-blue-100 text-blue-600 rounded-lg mr-2">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteTransaction(${transaction.id})" 
                                    class="px-3 py-1 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function updateFinancialSummary(transactions) {
            const totalDebit = transactions
                .filter(t => t.type.toLowerCase() === 'debit')
                .reduce((sum, t) => sum + parseFloat(t.amount || 0), 0);

            const totalCredit = transactions
                .filter(t => t.type.toLowerCase() === 'credit')
                .reduce((sum, t) => sum + parseFloat(t.amount || 0), 0);

            const netBalance = totalDebit - totalCredit;
            const balanceClass = netBalance > 0 ? 'balance-positive' :
                netBalance < 0 ? 'balance-negative' : 'balance-neutral';

            financialSummary.innerHTML = `
                <div class="summary-card p-4 rounded-lg">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <p class="text-sm text-gray-600">Total Debit</p>
                            <p class="text-2xl font-bold text-green-600">৳${totalDebit.toLocaleString('en-US', {minimumFractionDigits: 2})}</p>
                        </div>
                        <i class="fas fa-arrow-down text-green-500 text-xl"></i>
                    </div>
                </div>
                
                <div class="summary-card p-4 rounded-lg">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <p class="text-sm text-gray-600">Total Credit</p>
                            <p class="text-2xl font-bold text-red-600">৳${totalCredit.toLocaleString('en-US', {minimumFractionDigits: 2})}</p>
                        </div>
                        <i class="fas fa-arrow-up text-red-500 text-xl"></i>
                    </div>
                </div>
                
                <div class="summary-card p-4 rounded-lg">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <p class="text-sm text-gray-600">Net Balance</p>
                            <p class="text-2xl font-bold ${balanceClass}">৳${Math.abs(netBalance).toLocaleString('en-US', {minimumFractionDigits: 2})}</p>
                            <p class="text-xs ${balanceClass} mt-1">${netBalance >= 0 ? 'Profit' : 'Loss'}</p>
                        </div>
                        <i class="fas fa-balance-scale ${netBalance >= 0 ? 'text-green-500' : 'text-red-500'} text-xl"></i>
                    </div>
                </div>
            `;
        }

        function updateQuickStats(transactions) {
            document.getElementById('totalTransactions').textContent = transactions.length;

            // Update transaction progress (max 10 transactions for 100%)
            const progress = Math.min((transactions.length / 10) * 100, 100);
            document.getElementById('transactionProgress').style.width = `${progress}%`;

            // Debit vs Credit ratio
            const debitCount = transactions.filter(t => t.type.toLowerCase() === 'debit').length;
            const creditCount = transactions.filter(t => t.type.toLowerCase() === 'credit').length;
            document.getElementById('debitCreditRatio').textContent = `${debitCount}:${creditCount}`;

            // Update bars
            const total = debitCount + creditCount || 1;
            document.getElementById('debitBar').style.width = `${(debitCount / total) * 100}%`;
            document.getElementById('creditBar').style.width = `${(creditCount / total) * 100}%`;

            // Net balance
            const totalDebit = transactions
                .filter(t => t.type.toLowerCase() === 'debit')
                .reduce((sum, t) => sum + parseFloat(t.amount || 0), 0);

            const totalCredit = transactions
                .filter(t => t.type.toLowerCase() === 'credit')
                .reduce((sum, t) => sum + parseFloat(t.amount || 0), 0);

            const netBalance = totalDebit - totalCredit;
            document.getElementById('netBalance').textContent = `৳${netBalance.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
            document.getElementById('netBalance').className = `text-2xl font-bold mb-1 ${netBalance > 0 ? 'text-green-600' : netBalance < 0 ? 'text-red-600' : 'text-gray-600'}`;
        }

        function addRecentActivity(transaction) {
            const now = new Date();
            const timeString = now.toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit'
            });
            const type = transaction.type.toUpperCase();

            const activityItem = document.createElement('div');
            activityItem.className = 'flex items-start space-x-3 p-2 hover:bg-gray-50 rounded-lg';
            activityItem.innerHTML = `
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 rounded-full ${transaction.type === 'debit' ? 'bg-green-100' : 'bg-red-100'} flex items-center justify-center">
                        <i class="fas fa-${transaction.type === 'debit' ? 'plus' : 'minus'} ${transaction.type === 'debit' ? 'text-green-600' : 'text-red-600'}"></i>
                    </div>
                </div>
                <div class="flex-1">
                    <div class="text-sm font-medium text-gray-900">${type} Recorded</div>
                    <div class="text-xs text-gray-500">${transaction.purpose}</div>
                    <div class="text-xs text-gray-400 mt-1">৳${transaction.amount} • ${timeString}</div>
                </div>
            `;

            // Add to top of recent activity
            if (recentActivity.firstChild) {
                recentActivity.insertBefore(activityItem, recentActivity.firstChild);
            } else {
                recentActivity.appendChild(activityItem);
            }

            // Limit to 5 items
            const items = recentActivity.querySelectorAll('div.flex.items-start');
            if (items.length > 5) {
                items[items.length - 1].remove();
            }
        }

        function showNotification(message, type = 'info') {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg transform transition-all duration-300 ${
                type === 'success' ? 'bg-green-500 text-white' :
                type === 'error' ? 'bg-red-500 text-white' :
                'bg-blue-500 text-white'
            }`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} mr-2"></i>
                    <span>${message}</span>
                </div>
            `;

            document.body.appendChild(notification);

            // Remove after 3 seconds
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        function setupEventListeners() {
            // Enter key support for forms
            document.getElementById('client_amount').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') recordTransaction('debit');
            });

            document.getElementById('vendor_amount').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') recordTransaction('credit');
            });
            
            // Setup type toggle
            setupTypeToggle();
        }

        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        function printPage() {
            window.print();
        }

        function previewFile(filePath) {
            // Implement file preview logic here
            alert('File preview would show: ' + filePath.split('/').pop());
            // You can implement modal preview for PDFs/images
        }

        // function editTransaction(id) {
        //     // Implement edit functionality
        //     showNotification('Edit functionality coming soon', 'info');
        // }
        
        
        // Edit Transaction
        async function editTransaction(id) {
            try {
                // Find the transaction
                const transaction = allTransactions.find(t => t.id == id);
                if (!transaction) {
                    showNotification('Transaction not found', 'error');
                    return;
                }
        
                // Populate modal with transaction data
                document.getElementById('edit_transaction_id').value = transaction.id;
                document.getElementById('edit_original_type').value = transaction.type;
                document.getElementById('edit_purpose').value = transaction.purpose || '';
                document.getElementById('edit_amount').value = transaction.amount;
                
                // Format date for datetime-local input
                if (transaction.date) {
                    const dateStr = transaction.date.replace(' ', 'T');
                    document.getElementById('edit_date').value = dateStr.substring(0, 16);
                }
                
                // Set type display
                const typeDisplay = document.getElementById('edit_type_display');
                const type = transaction.type.toLowerCase();
                typeDisplay.innerHTML = type === 'debit' ? 
                    '<span class="text-green-600 font-semibold">DEBIT (Client Deposit)</span>' : 
                    '<span class="text-red-600 font-semibold">CREDIT (Vendor Payment)</span>';
                
                // Handle vendor/account selection for credit transactions
                const selectionContainer = document.getElementById('edit_selection_container');
                if (type === 'credit') {
                    selectionContainer.classList.remove('hidden');
                    
                    // Determine if it's vendor or own account
                    const isVendor = transaction.vendor_type == 0;
                    document.getElementById('edit_vendor_type').value = isVendor ? 'vendor' : 'own';
                    
                    // Set radio button
                    const vendorRadio = document.querySelector('input[name="edit_account_type"][value="vendor"]');
                    const ownRadio = document.querySelector('input[name="edit_account_type"][value="own"]');
                    
                    if (isVendor) {
                        vendorRadio.checked = true;
                        ownRadio.checked = false;
                    } else {
                        vendorRadio.checked = false;
                        ownRadio.checked = true;
                    }
                    
                    // Load the appropriate container
                    await loadEditSelectionContainers();
                    
                    // Set the initial value
                    if (isVendor && transaction.vendor_id) {
                        const vendorName = transaction.vendor_name || 'Unknown Vendor';
                        const vendorInput = document.getElementById('editVendorInput');
                        if (vendorInput) {
                            vendorInput.value = transaction.vendor_id + ' | ' + vendorName;
                        }
                    } else if (!isVendor && transaction.account_id) {
                        const accountName = transaction.account_name || 'Unknown Account';
                        const accountInput = document.getElementById('editAccountInput');
                        if (accountInput) {
                            accountInput.value = transaction.account_id + ' | ' + accountName;
                        }
                    }
                    
                    toggleEditSelection();
                } else {
                    selectionContainer.classList.add('hidden');
                }
                
                // Show modal
                document.getElementById('editModal').classList.remove('hidden');
                
            } catch (error) {
                console.error('Error loading transaction for edit:', error);
                showNotification('Error loading transaction details', 'error');
            }
        }
        
        // Load edit selection containers
        async function loadEditSelectionContainers() {
            const vendorContainer = document.getElementById('edit_vendor_container');
            const accountContainer = document.getElementById('edit_account_container');
            
            // Load vendor component if not already loaded
            if (vendorContainer.children.length === 0) {
                const vendorResponse = await fetch('form-selects/vendors-edit.php');
                const vendorHtml = await vendorResponse.text();
                vendorContainer.innerHTML = vendorHtml;
                
                // Load vendors data
                if (typeof loadEditVendors === 'function') {
                    loadEditVendors();
                }
            }
            
            // Load account component if not already loaded
            if (accountContainer.children.length === 0) {
                const accountResponse = await fetch('form-selects/accounts-edit.php');
                const accountHtml = await accountResponse.text();
                accountContainer.innerHTML = accountHtml;
                
                // Load accounts data
                if (typeof loadEditAccounts === 'function') {
                    loadEditAccounts();
                }
            }
        }
        
        // Toggle between vendor and account selection
        function toggleEditSelection() {
            const type = document.querySelector('input[name="edit_account_type"]:checked')?.value;
            const vendorContainer = document.getElementById('edit_vendor_container');
            const accountContainer = document.getElementById('edit_account_container');
            
            if (type === 'vendor') {
                vendorContainer.classList.remove('hidden');
                accountContainer.classList.add('hidden');
                
                // Setup vendor search if not already setup
                if (typeof setupEditVendorSearch === 'function') {
                    setTimeout(() => setupEditVendorSearch(), 100);
                }
            } else if (type === 'own') {
                vendorContainer.classList.add('hidden');
                accountContainer.classList.remove('hidden');
                
                // Setup account search if not already setup
                if (typeof setupEditAccountSearch === 'function') {
                    setTimeout(() => setupEditAccountSearch(), 100);
                }
            }
        }
        
        // Update transaction
        async function updateTransaction(event) {
            event.preventDefault();
            
            try {
                const transactionId = document.getElementById('edit_transaction_id').value;
                const originalType = document.getElementById('edit_original_type').value;
                const purpose = document.getElementById('edit_purpose').value.trim();
                const amount = parseFloat(document.getElementById('edit_amount').value);
                const dateTime = document.getElementById('edit_date').value.replace('T', ' ');
                
                if (!purpose || !amount || amount <= 0) {
                    showNotification('Please enter valid purpose and amount', 'error');
                    return;
                }
                
                // Build update data
                const updateData = {
                    id: transactionId,
                    purpose: purpose,
                    amount: amount,
                    date: dateTime
                };
                
                // Add vendor/account info for credit transactions
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
                
                // Send update request
                const response = await fetch(UPDATE_FINANCIAL_ENTRY_API, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(updateData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('Transaction updated successfully!', 'success');
                    closeEditModal();
                    loadFinancialData(); // Reload the table
                } else {
                    showNotification('Error: ' + (result.message || 'Failed to update transaction'), 'error');
                }
                
            } catch (error) {
                console.error('Error updating transaction:', error);
                showNotification('Network error occurred', 'error');
            }
        }
    
    // Close edit modal
    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
        document.getElementById('editTransactionForm').reset();
    }
        
    function deleteTransaction(id) {
        if (confirm('Are you sure you want to delete this transaction?')) {
            // Implement delete functionality
            showNotification('Delete functionality coming soon', 'info');
        }
    }

    // Reload function
    function reloadFinancialTable() {
        loadFinancialData();
        showNotification('Data refreshed', 'success');
    }
    </script>
</body>

</html>