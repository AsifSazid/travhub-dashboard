<?php
$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898";
}

$workId = $_GET['work_id'];
$taskId = $_GET['task_id'];

$getClientsApi = $ip_port . "api/clients/get-client.php?work_id=$workId";
$getAllVendorsApi = $ip_port . "api/vendors/all-vendors.php";
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
                                    <input type="text"
                                        id="client_purpose"
                                        placeholder="e.g., Initial Payment, Final Payment, Extra Service"
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200">
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

                                <button onclick="recordTransaction('debit')"
                                    class="w-full mt-2 py-3 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white font-semibold rounded-lg transition-all duration-300 transform hover:-translate-y-0.5 hover:shadow-lg flex items-center justify-center">
                                    <i class="fas fa-plus-circle mr-2"></i> Record Debit
                                </button>
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
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    <i class="fas fa-building mr-1"></i> Vendor
                                </label>
                                <div class="relative w-full">
                                    <div class="flex">
                                        <input
                                            type="text"
                                            id="vendorInput"
                                            placeholder="Search for a vendor..."
                                            class="flex-1 px-4 py-2 border border-gray-300 rounded-l-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 focus:outline-none transition-all duration-200"
                                            autocomplete="off">
                                    </div>
                                    <ul id="vendorDropdown" class="absolute w-full bg-white border border-gray-300 rounded-lg mt-1 max-h-60 overflow-auto shadow-lg hidden z-50">
                                        <!-- JS will populate options here -->
                                    </ul>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        <i class="fas fa-bullseye mr-1"></i> Purpose
                                    </label>
                                    <input type="text"
                                        id="vendor_purpose"
                                        placeholder="e.g., Hotel Booking, Air Ticket, Service Fee"
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200">
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

                                <button onclick="recordTransaction('credit')"
                                    class="w-full mt-2 py-3 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-semibold rounded-lg transition-all duration-300 transform hover:-translate-y-0.5 hover:shadow-lg flex items-center justify-center">
                                    <i class="fas fa-plus-circle mr-2"></i> Record Credit
                                </button>
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

    <!-- Floating Quick Access Tab -->
    <?php include '../elements/floating-menus.php'; ?>

    <!-- Floating Action Button -->
    <div class="floating-action">
        <button onclick="scrollToTop()"
            class="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-full shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center">
            <i class="fas fa-arrow-up"></i>
        </button>
    </div>

    <script src="../assets/js/script.js"></script>

    <script>
        const GET_CLIENT_API = "<?php echo $getClientsApi; ?>";
        const GET_ALL_VENDOR_API = "<?php echo $getAllVendorsApi; ?>";
        const FINANCIAL_ENTRIES_STORE_API = "<?php echo $storeFinancialEntriesApi; ?>";
        const GET_FINANCIAL_STATEMENT_API = "<?php echo $getTaskFinEntriesApi; ?>";
        const GET_TASK_API = "<?php echo $getTaskApi; ?>";

        const WORK_ID = "<?php echo $workId; ?>";
        const TASK_ID = "<?php echo $taskId; ?>";

        let vendorsData = [];
        let allTransactions = [];
        let currentClientId = null;
        let selectedVendorLi = null;

        // DOM Elements
        const vendorInput = document.getElementById('vendorInput');
        const vendorDropdown = document.getElementById('vendorDropdown');
        const taskMetaSection = document.getElementById('taskMetaSection');
        const fileAttachments = document.getElementById('fileAttachments');
        const financialSummary = document.getElementById('financialSummary');
        const recentActivity = document.getElementById('recentActivity');

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadTaskMetaData();
            loadClientData();
            loadVendors();
            loadFinancialData();
            setupEventListeners();
        });

        // Load Task Meta Data
        async function loadTaskMetaData() {
            try {
                const response = await fetch(GET_TASK_API);
                const data = await response.json();

                if (data.success && data.task) {
                    const task = data.task;

                    // Update task info card
                    document.getElementById('taskIdDisplay').textContent = task.sys_id || 'N/A';

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
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-green-600 font-medium">Work Title</p>
                            <p class="text-lg font-semibold text-green-900 truncate">${task.work_title || 'N/A'}</p>
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
                        const safeFilePath = file.replace(/'/g, "\\'").replace(/"/g, '\\"');

                        return `
                    <div class="file-chip cursor-pointer hover:shadow-sm" 
                         onclick="previewFile('${safeFilePath}')" 
                         title="${fileName}">
                        <i class="${icon} text-${color}-500 mr-1"></i>
                        <span class="truncate max-w-[150px]">${fileName}</span>
                    </div>
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

                    const clientName = `${client.name}`;
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
            } catch (error) {
                console.error('Error loading client data:', error);
            }
        }

        // Load Vendors
        function loadVendors() {
            fetch(GET_ALL_VENDOR_API)
                .then(res => res.json())
                .then(data => {

                    if (data.vendors && Array.isArray(data.vendors)) {
                        vendorsData = data.vendors;

                    } else {
                        console.error('Invalid vendors data format:', data);
                        vendorsData = [];
                    }
                })
                .catch(err => {
                    console.error('Error fetching vendors:', err);
                    vendorsData = [];
                });
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadVendors();

            // Setup event listeners
            setupVendorSearch();
        });

        function setupVendorSearch() {
            if (!vendorInput || !vendorDropdown) {
                console.error('Vendor search elements not found');
                return;
            }

            setupOutsideClickHandler();

            // Track tab key press
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Tab') {
                    isTabKeyPressed = true;
                    // Add small delay to allow focus to move
                    setTimeout(() => {
                        const activeElement = document.activeElement;
                        const vendorContainer = document.querySelector('.vendor-search-container') ||
                            vendorInput.closest('.relative.w-full');

                        // If focus moved outside vendor container, hide dropdown
                        if (vendorContainer && !vendorContainer.contains(activeElement)) {
                            vendorDropdown.classList.add('hidden');
                        }
                        isTabKeyPressed = false;
                    }, 10);
                }
            });

            // Input typing with debounce
            let typingTimer;
            vendorInput.addEventListener('input', () => {
                clearTimeout(typingTimer);
                typingTimer = setTimeout(() => {
                    const value = vendorInput.value.toLowerCase().trim();

                    if (value === '') {
                        // Show all vendors when empty
                        renderDropdown(vendorsData);
                        vendorDropdown.classList.remove('hidden');
                        return;
                    }

                    const filtered = vendorsData.filter(vendor => {
                        // Get vendor properties safely
                        const vendorId = vendor.id ? vendor.id.toString() : '';
                        const vendorName = vendor.name || '';
                        const vendorPhone = vendor.phone || '';

                        // Check if any property contains the search term
                        return vendorId.toLowerCase().includes(value) ||
                            vendorName.toLowerCase().includes(value) ||
                            vendorPhone.toString().toLowerCase().includes(value);
                    });

                    renderDropdown(filtered);
                    vendorDropdown.classList.remove('hidden');
                }, 300);
            });

            // Enter key to select first item
            vendorInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && vendorDropdown.children.length > 0) {
                    e.preventDefault();
                    const firstItem = vendorDropdown.children[0];
                    if (firstItem) {
                        firstItem.click();
                    }
                }

                // Escape to close dropdown
                if (e.key === 'Escape') {
                    vendorDropdown.classList.add('hidden');
                }
            });

            // Focus to show all vendors
            vendorInput.addEventListener('focus', () => {
                if (vendorsData.length > 0) {
                    renderDropdown(vendorsData);
                    vendorDropdown.classList.remove('hidden');
                }
            });
        }

        function renderDropdown(list) {
            vendorDropdown.innerHTML = '';

            if (!list || list.length === 0) {
                const li = document.createElement('li');
                li.innerHTML = `
                    <div class="px-4 py-3 text-center text-gray-500">
                        <i class="fas fa-search text-gray-400 mb-1"></i>
                        <p class="text-sm">No vendors found</p>
                        <p class="text-xs text-gray-400 mt-1">Try a different search term</p>
                    </div>
                `;
                vendorDropdown.appendChild(li);
                return;
            }

            list.forEach(vendor => {
                // Parse vendor data
                let vendorName = '';
                let vendorPhone = '';
                let vendorId = vendor.sys_id || 'N/A';

                try {
                    if (vendor.name) {
                        if (typeof vendor.name === 'string' && vendor.name.startsWith('{')) {
                            const nameObj = JSON.parse(vendor.name);
                            vendorName = nameObj.primary || 'Unnamed Vendor';
                        } else {
                            vendorName = vendor.name.toString();
                        }
                    } else {
                        vendorName = 'Unnamed Vendor';
                    }

                    // Check if phone is JSON string
                    if (vendor.phone) {
                        if (typeof vendor.phone === 'string' && vendor.phone.startsWith('{')) {
                            const phoneObj = JSON.parse(vendor.phone);
                            vendorPhone = phoneObj.primary_no || '';
                        } else {
                            vendorPhone = vendor.phone.toString();
                        }
                    }
                } catch (error) {
                    console.error('Error parsing vendor data:', error);
                    vendorName = 'Error parsing data';
                }

                const li = document.createElement('li');
                li.className = "px-4 py-3 cursor-pointer hover:bg-purple-50 border-b border-gray-100 last:border-b-0 transition-colors duration-150";
                li.innerHTML = `
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-gradient-to-r from-purple-500 to-purple-600 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                                ${vendorName.charAt(0).toUpperCase()}
                            </div>
                        </div>
                        <div class="ml-3 flex-1">
                            <div class="font-medium text-gray-900">${vendorName}</div>
                            <div class="text-xs text-gray-500 mt-1">
                                <div class="flex items-center">
                                    <span class="bg-purple-100 text-purple-800 px-2 py-0.5 rounded text-xs mr-2">
                                        ID: ${vendorId}
                                    </span>
                                    ${vendorPhone ? `<span class="flex items-center"><i class="fas fa-phone mr-1 text-xs"></i>${vendorPhone}</span>` : ''}
                                </div>
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="fas fa-check text-purple-500 text-sm opacity-0 group-hover:opacity-100"></i>
                        </div>
                    </div>
                `;

                li.addEventListener('click', (e) => {
                    e.stopPropagation(); // Prevent event bubbling
                    vendorInput.value = `${vendorId} | ${vendorName}`;
                    vendorDropdown.classList.add('hidden');
                    li.setAttribute('data-sys-id', vendor.sys_id);

                    // Highlight selected item
                    const allItems = vendorDropdown.querySelectorAll('li');
                    allItems.forEach(item => item.classList.remove('bg-purple-100'));
                    li.classList.add('bg-purple-100');

                    selectedVendorLi = li;
                });

                // Add hover effect
                li.addEventListener('mouseenter', () => {
                    li.classList.add('bg-purple-50');
                });

                li.addEventListener('mouseleave', () => {
                    li.classList.remove('bg-purple-50');
                });

                vendorDropdown.appendChild(li);
            });
        }

        // Improved outside click handler
        function setupOutsideClickHandler() {
            // Single global click handler
            document.addEventListener('click', function(e) {
                // Check if click is outside vendor search area
                const vendorSearchArea = document.querySelector('.relative.w-full');
                const isClickInside = vendorSearchArea && vendorSearchArea.contains(e.target);

                if (!isClickInside && !vendorDropdown.classList.contains('hidden')) {
                    vendorDropdown.classList.add('hidden');
                }
            });
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
                        date: date || new Date().toISOString().split('T')[0]
                    };

                    await saveTransaction(transactionData, 'Debit');

                    // Clear form
                    // document.getElementById('client_purpose').value = '';
                    // document.getElementById('client_amount').value = '';

                } else if (type === 'credit') {
                    const purpose = document.getElementById('vendor_purpose').value.trim();
                    const amount = parseFloat(document.getElementById('vendor_amount').value);
                    const date = document.getElementById('vendor_date').value;
                    const vendorInputValue = document.getElementById('vendorInput').value;

                    const vendorId = selectedVendorLi.getAttribute('data-sys-id'); // or selectedVendorLi.dataset.sysId

                    if (!vendorId) {
                        showNotification('Please select a valid vendor', 'error');
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
                        vendor_id: vendorId,
                        work_id: workId,
                        task_id: taskId,
                        date: date || new Date().toISOString().split('T')[0]
                    };

                    await saveTransaction(transactionData, 'Credit');

                    // Clear form
                    // document.getElementById('vendor_purpose').value = '';
                    // document.getElementById('vendor_amount').value = '';
                    // document.getElementById('vendorInput').value = '';
                }

            } catch (error) {
                console.error('Error recording transaction:', error);
                showNotification('An error occurred while recording the transaction', 'error');
            }
        }

        async function saveTransaction(data, type) {
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
                            <div class="text-sm text-gray-900">${transaction.client_name || transaction.vendor_name || 'Unknown'}</div>
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

        function editTransaction(id) {
            // Implement edit functionality
            showNotification('Edit functionality coming soon', 'info');
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