<?php
$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898";
}

$base_ip_path = trim($ip_port, "/");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accounting - Account Statement</title>
    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png" sizes="16x16">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Spinner animation */
        .spinner {
            border: 4px solid rgba(0, 0, 0, 0.1);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border-left-color: #3b82f6;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Scrollbar styling */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #555;
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

            <!-- Account Statement Container -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <!-- Blue Header -->
                <div class="bg-blue-600 text-white p-4 md:p-6">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center space-y-4 md:space-y-0">
                        <div>
                            <h2 class="text-xl font-semibold flex items-center">
                                <i class="fas fa-file-invoice-dollar mr-2"></i>
                                Received Amount
                            </h2>
                        </div>
                        <div class="flex items-center space-x-3">
                            <button onclick="window.history.back()" 
                                class="px-4 py-2 bg-blue-700 hover:bg-blue-800 text-white rounded-lg flex items-center transition-colors">
                                <i class="fas fa-arrow-left mr-2"></i> Back
                            </button>
                            <button onclick="window.print()" 
                                class="px-4 py-2 bg-white text-blue-600 hover:bg-gray-100 rounded-lg flex items-center transition-colors">
                                <i class="fas fa-print mr-2"></i> Print
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Content Area -->
                <div class="p-4 md:p-6">
                    <!-- Transaction Input Section -->
                    <div class="bg-blue-50 p-4 md:p-6 rounded-lg mb-6 border border-blue-200">
                        <h6 class="text-blue-700 font-semibold mb-4 flex items-center text-lg">
                            <i class="fas fa-plus-circle mr-2"></i> Add New Transaction
                        </h6>
                        <form id="transactionForm" class="space-y-6">
                            <input type="hidden" id="accountId" name="accountId">
                            <input type="hidden" id="accountName" name="accountName">
                            <input type="hidden" id="currentAccountBalance" name="currentAccountBalance">

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                <input id="paymentType" name="paymentType" value="Deposit" hidden />
                                
                                <div>
                                    <label for="transactionDate" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fa-solid fa-user"></i> Client Name
                                    </label>
                                   <?php include('form-selects/clients.php') ?>
                                </div>
                                
                                <div>
                                    <label for="transactionDate" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fa-solid fa-receipt"></i> Account Name
                                    </label>
                                   <?php include('form-selects/accounts.php') ?>
                                </div>
                                
                                <div>
                                    <label for="transactionDate" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-calendar-alt mr-1"></i> Date
                                    </label>
                                    <input type="date"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                        id="transactionDate" name="transactionDate" required>
                                </div>
                                
                                <div>
                                    <label for="balance" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-dollar-sign mr-1"></i> Amount
                                    </label>
                                    <input type="number" step="0.01"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                        id="balance" name="balance" required placeholder="0.00">
                                </div>
                                
                                <div class="md:col-span-2 lg:col-span-4">
                                    <label for="particular" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-file-alt mr-1"></i> Particular
                                    </label>
                                    <textarea
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" rows="5"
                                        id="particular" name="particular" placeholder="Enter transaction description"></textarea>
                                </div>
                            </div>
                            
                            <div class="flex justify-end space-x-3 pt-4">
                                <button type="button" onclick="resetTransactionForm()"
                                    class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 transition-colors">
                                    <i class="fas fa-redo mr-2"></i> Reset
                                </button>
                                <button type="button"
                                    class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors flex items-center"
                                    id="saveTransactionBtn">
                                    <span id="spinner" class="hidden spinner-border spinner-border-sm mr-2" role="status" aria-hidden="true"></span>
                                    <i class="fas fa-save mr-2"></i>
                                    <span id="saveButtonText">Save Transaction</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Statement Filters and Actions -->
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 bg-gray-50 p-4 rounded-lg border border-gray-200 space-y-4 md:space-y-0">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center space-y-3 sm:space-y-0 sm:space-x-4">
                            <h3 class="text-lg font-semibold text-gray-700 flex items-center">
                                <i class="fas fa-filter mr-2"></i> Filter Statement
                            </h3>
                            <div class="flex flex-col sm:flex-row items-start sm:items-center space-y-2 sm:space-y-0 sm:space-x-3">
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm text-gray-600 font-medium">From:</span>
                                    <input type="date"
                                        class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                                        id="fromDateFilter">
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm text-gray-600 font-medium">To:</span>
                                    <input type="date"
                                        class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                                        id="toDateFilter">
                                </div>
                                <button class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition-colors flex items-center"
                                    id="searchStatementBtn">
                                    <i class="fas fa-search mr-2"></i> Search
                                </button>
                            </div>
                        </div>
                        <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700 transition-colors flex items-center"
                            id="downloadCsvBtn">
                            <i class="fas fa-download mr-2"></i> Download CSV
                        </button>
                    </div>

                    <!-- Statement Loading -->
                    <div id="statementLoader" class="flex flex-col items-center justify-center py-12 hidden">
                        <div class="spinner" style="border-top-color: #3b82f6;"></div>
                        <p class="mt-4 text-gray-600 font-medium">Loading statement data...</p>
                        <p class="text-gray-500 text-sm mt-2">Please wait while we fetch your transaction history</p>
                    </div>

                    <!-- Statement Table -->
                    <div id="statementTableContainer" class="hidden">
                        <div class="overflow-x-auto rounded-lg border border-gray-200">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Particular</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Withdraw</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Deposit</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Balance</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Reconciliation</th>
                                    </tr>
                                </thead>
                                <tbody id="statementTableBody" class="bg-white divide-y divide-gray-200">
                                    <!-- Statement rows will be inserted here -->
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Summary Footer -->
                        <div class="mt-4 bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <div class="flex justify-between items-center">
                                <div class="text-gray-600">
                                    <span id="totalTransactions">0</span> transactions found
                                </div>
                                <div class="flex space-x-6">
                                    <div class="text-center">
                                        <p class="text-sm text-gray-500">Total Withdraw</p>
                                        <p id="totalWithdraw" class="text-lg font-semibold text-red-600">$0.00</p>
                                    </div>
                                    <div class="text-center">
                                        <p class="text-sm text-gray-500">Total Deposit</p>
                                        <p id="totalDeposit" class="text-lg font-semibold text-green-600">$0.00</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- No Data Message -->
                    <div id="noStatementData" class="hidden text-center py-12 bg-gray-50 rounded-lg border border-gray-200">
                        <i class="fas fa-file-alt text-4xl text-gray-400 mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-600 mb-2">No transactions found</h3>
                        <p class="text-gray-500 mb-6">There are no transactions for this account in the selected period.</p>
                        <button onclick="resetFilters()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-redo mr-2"></i> Reset Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include '../elements/floating-menus.php'; ?>

    <script src="../assets/js/script.js"></script>
    <script>
        // Set today's date as default for transaction date
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('transactionDate').value = today;
            
            // Set default date range (last 30 days)
            const fromDate = new Date();
            fromDate.setDate(fromDate.getDate() - 30);
            document.getElementById('fromDateFilter').value = fromDate.toISOString().split('T')[0];
            document.getElementById('toDateFilter').value = today;
            
            // Load account data from URL parameters or localStorage
            loadAccountData();
        });

        // Load account data from URL parameters
        function loadAccountData() {
            const urlParams = new URLSearchParams(window.location.search);
            const accountId = urlParams.get('accountId');
            const accountName = urlParams.get('accountName');
            const accountBalance = urlParams.get('balance');
            const accountType = urlParams.get('type');

            if (accountName) {
                document.getElementById('statementAccountName').textContent = accountName;
                document.title = `Statement - ${accountName}`;
            }

            if (accountBalance) {
                document.getElementById('currentBalanceDisplay').textContent = `$${parseFloat(accountBalance).toFixed(2)}`;
            }

            if (accountType) {
                const badge = document.getElementById('accountTypeBadge');
                const text = document.getElementById('accountTypeText');
                text.innerHTML = accountType;
                
                // Set badge color based on account type
                if (accountType.toLowerCase().includes('savings')) {
                    badge.className = 'inline-block px-4 py-2 rounded-full text-sm font-medium mt-1 bg-green-100 text-green-800';
                } else if (accountType.toLowerCase().includes('current')) {
                    badge.className = 'inline-block px-4 py-2 rounded-full text-sm font-medium mt-1 bg-blue-100 text-blue-800';
                } else {
                    badge.className = 'inline-block px-4 py-2 rounded-full text-sm font-medium mt-1 bg-purple-100 text-purple-800';
                }
            }

            // Set hidden fields
            if (accountId) document.getElementById('accountId').value = accountId;
            if (accountName) document.getElementById('accountName').value = accountName;
            if (accountBalance) document.getElementById('currentAccountBalance').value = accountBalance;

            // Load statement data
            loadStatementData();
        }

        // Show/hide reconciliation field based on payment type
        document.getElementById('paymentType').addEventListener('change', function(e) {
            const reconWrapper = document.getElementById('reconciliationWrapper');
            if (e.target.value === 'Reconciliation') {
                reconWrapper.classList.remove('hidden');
            } else {
                reconWrapper.classList.add('hidden');
            }
        });

        // Reset transaction form
        function resetTransactionForm() {
            document.getElementById('transactionForm').reset();
            document.getElementById('transactionDate').value = new Date().toISOString().split('T')[0];
            document.getElementById('reconciliationWrapper').classList.add('hidden');
        }

        // Reset filters
        function resetFilters() {
            const fromDate = new Date();
            fromDate.setDate(fromDate.getDate() - 30);
            document.getElementById('fromDateFilter').value = fromDate.toISOString().split('T')[0];
            document.getElementById('toDateFilter').value = new Date().toISOString().split('T')[0];
            loadStatementData();
        }

        // Load statement data
        function loadStatementData() {
            // Show loader
            document.getElementById('statementLoader').classList.remove('hidden');
            document.getElementById('statementTableContainer').classList.add('hidden');
            document.getElementById('noStatementData').classList.add('hidden');

            // Simulate API call
            setTimeout(() => {
                // Hide loader
                document.getElementById('statementLoader').classList.add('hidden');
                
                // For demo purposes, show no data message
                // In real implementation, populate the table with data
                document.getElementById('noStatementData').classList.remove('hidden');
                
                // Update summary
                document.getElementById('totalTransactions').textContent = '0';
                document.getElementById('totalWithdraw').textContent = '$0.00';
                document.getElementById('totalDeposit').textContent = '$0.00';
            }, 1500);
        }

        // Search statement
        document.getElementById('searchStatementBtn').addEventListener('click', loadStatementData);

        // Save transaction
        document.getElementById('saveTransactionBtn').addEventListener('click', function() {
            // Add save transaction logic here
            alert('Transaction saved successfully!');
            resetTransactionForm();
        });

        // Download CSV
        document.getElementById('downloadCsvBtn').addEventListener('click', function() {
            alert('CSV download functionality would be implemented here');
        });
    </script>

</body>

</html>