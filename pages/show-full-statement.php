<?php
include_once('./authenticate.php');
$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898";
}
$base_ip_path = trim($ip_port, "/");

// Get account ID from URL
$acc_id = isset($_GET['acc_id']) ? $_GET['acc_id'] : '';

if (empty($acc_id)) {
    die("Account ID is required!");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Full Statement - Account Laser</title>
    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png" sizes="16x16">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .print-hide {
            display: block;
        }
        
        @media print {
            .print-hide {
                display: none !important;
            }
            
            .print-show {
                display: block !important;
            }
            
            .no-break {
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body class="bg-gray-100">
    <div class="container mx-auto p-4 md:p-6">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow p-6 mb-6 print-hide">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-file-invoice-dollar text-blue-600 mr-2"></i>
                        Full Account Statement
                    </h1>
                    <p class="text-gray-600 mt-1" id="accountInfo">Loading account information...</p>
                </div>
                <div class="flex gap-3">
                    <button onclick="window.print()" 
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg flex items-center gap-2">
                        <i class="fas fa-print"></i> Print
                    </button>
                    <button onclick="window.close()" 
                            class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg flex items-center gap-2">
                        <i class="fas fa-times"></i> Close
                    </button>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                    <input type="date" id="fromDate" class="w-full p-2 border border-gray-300 rounded">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                    <input type="date" id="toDate" class="w-full p-2 border border-gray-300 rounded">
                </div>
                <div class="md:col-span-2 flex items-end gap-2">
                    <button onclick="filterStatement()" 
                            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded flex items-center gap-2">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <button onclick="resetFilter()" 
                            class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded">
                        Reset
                    </button>
                </div>
            </div>
        </div>

        <!-- Statement Container -->
        <div class="bg-white rounded-lg shadow">
            <!-- Loading -->
            <div id="loader" class="flex flex-col items-center justify-center p-12">
                <div class="spinner w-12 h-12 border-4 border-gray-200 border-t-blue-600 rounded-full animate-spin"></div>
                <p class="mt-4 text-gray-600">Loading statement data...</p>
            </div>

            <!-- Error -->
            <div id="errorMessage" class="hidden p-8 text-center">
                <i class="fas fa-exclamation-triangle text-red-500 text-4xl mb-4"></i>
                <h3 class="text-xl font-semibold text-red-600 mb-2">Failed to load data</h3>
                <p class="text-gray-600" id="errorText"></p>
            </div>

            <!-- Statement Content -->
            <div id="statementContent" class="hidden">
                <!-- Account Summary -->
                <div class="p-6 border-b border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="text-center p-4 bg-blue-50 rounded-lg">
                            <h3 class="text-sm font-medium text-blue-800 mb-1">Account Name</h3>
                            <p class="text-xl font-bold text-gray-800" id="fullAccountName">-</p>
                        </div>
                        <div class="text-center p-4 bg-green-50 rounded-lg">
                            <h3 class="text-sm font-medium text-green-800 mb-1">Current Balance</h3>
                            <p class="text-2xl font-bold text-green-700" id="fullCurrentBalance">৳ 0.00</p>
                        </div>
                        <div class="text-center p-4 bg-purple-50 rounded-lg">
                            <h3 class="text-sm font-medium text-purple-800 mb-1">Total Transactions</h3>
                            <p class="text-2xl font-bold text-purple-700" id="totalTransactions">0</p>
                        </div>
                    </div>
                </div>

                <!-- Statement Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Particular</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Withdraw</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deposit</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reconciliation</th>
                            </tr>
                        </thead>
                        <tbody id="statementTableBody" class="bg-white divide-y divide-gray-200">
                            <!-- Data will be loaded here -->
                        </tbody>
                    </table>
                    
                    <!-- No Data -->
                    <div id="noDataMessage" class="hidden p-12 text-center">
                        <i class="fas fa-file-alt text-gray-400 text-5xl mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-500 mb-2">No transactions found</h3>
                        <p class="text-gray-400">No transactions available for the selected period.</p>
                    </div>
                </div>

                <!-- Summary -->
                <div class="p-6 bg-gray-50 border-t border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="text-center">
                            <h4 class="text-sm font-medium text-gray-600 mb-1">Total Deposit</h4>
                            <p class="text-xl font-bold text-green-600" id="totalDeposit">৳ 0.00</p>
                        </div>
                        <div class="text-center">
                            <h4 class="text-sm font-medium text-gray-600 mb-1">Total Withdraw</h4>
                            <p class="text-xl font-bold text-red-600" id="totalWithdraw">৳ 0.00</p>
                        </div>
                        <div class="text-center">
                            <h4 class="text-sm font-medium text-gray-600 mb-1">Net Change</h4>
                            <p class="text-xl font-bold text-blue-600" id="netChange">৳ 0.00</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Print Header (Hidden during normal view) -->
        <div class="hidden print-show">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold mt-4">Account Statement</h1>
                <p class="text-gray-600" id="printAccountInfo"></p>
                <p class="text-gray-600" id="printDateRange"></p>
                <p class="text-sm italic font-semibold">This is system Generated Statement. No Signature Required..!</p>
                <p class="text-gray-600">Printed on: <?php echo date('F j, Y h:i A'); ?></p>
            </div>
        </div>
    </div>

    <script>
        const API_STATEMENT_URL = '<?php echo htmlspecialchars($base_ip_path); ?>/api/accounts/fetch_account_statement_api.php';
        const API_FETCH_URL = '<?php echo htmlspecialchars($base_ip_path); ?>/api/ledgers/fetch_ledger_api.php';
        const ACC_ID = '<?php echo htmlspecialchars($acc_id); ?>';

        document.addEventListener('DOMContentLoaded', function() {
            // Set default dates
            const today = new Date().toISOString().split('T')[0];
            const firstDay = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0];
            
            document.getElementById('fromDate').value = firstDay;
            document.getElementById('toDate').value = today;
            
            // Load account info and statement
            loadAccountInfo();
            loadStatement();
        });

        async function loadAccountInfo() {
            try {
                const response = await fetch(`${API_FETCH_URL}`);
                const result = await response.json();
                
                if (result.success && Array.isArray(result.data)) {
                    const account = result.data.find(acc => acc.sys_id == ACC_ID);
                    if (account) {
                        document.getElementById('accountInfo').textContent = 
                            `${account.acc_name} (${account.category || 'General'})`;
                        document.getElementById('fullAccountName').textContent = account.acc_name;
                        document.getElementById('printAccountInfo').textContent = 
                            `${account.acc_name} | ${account.category || 'General'}`;
                        document.getElementById('printAccountInfo').classList.add('uppercase');
                    }
                }
            } catch (error) {
                console.error('Error loading account info:', error);
            }
        }

        async function loadStatement(fromDate = null, toDate = null) {
            const loader = document.getElementById('loader');
            const statementContent = document.getElementById('statementContent');
            const errorMessage = document.getElementById('errorMessage');
            const noDataMessage = document.getElementById('noDataMessage');
            
            loader.classList.remove('hidden');
            statementContent.classList.add('hidden');
            errorMessage.classList.add('hidden');
            noDataMessage.classList.add('hidden');

            let url = `${API_STATEMENT_URL}?ledger_db_id=${ACC_ID}`;
            if (fromDate) url += `&from_date=${fromDate}`;
            if (toDate) url += `&to_date=${toDate}`;

            try {
                const response = await fetch(url);
                const result = await response.json();

                if (result.success && Array.isArray(result.data)) {
                    displayStatement(result.data);
                } else {
                    showNoData();
                }
            } catch (error) {
                showError('Failed to load statement data: ' + error.message);
            } finally {
                loader.classList.add('hidden');
            }
        }

        function displayStatement(data) {
            const tableBody = document.getElementById('statementTableBody');
            const statementContent = document.getElementById('statementContent');
            const noDataMessage = document.getElementById('noDataMessage');
            
            if (data.length === 0) {
                showNoData();
                return;
            }

            // Calculate totals
            let totalDeposit = 0;
            let totalWithdraw = 0;
            let currentBalance = 0;

            // Clear table
            tableBody.innerHTML = '';

            // Add rows
            data.forEach(item => {
                const deposit = parseFloat(item.deposit || 0);
                const withdraw = parseFloat(item.withdraw || 0);
                const balance = parseFloat(item.balance || 0);
                const reconciliation = parseFloat(item.reconsilation || 0);

                totalDeposit += deposit;
                totalWithdraw += withdraw;
                currentBalance = balance; // Last item's balance is current balance

                const row = document.createElement('tr');
                row.className = 'no-break';
                row.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.date || ''}</td>
                    <td class="px-6 py-4 text-sm text-gray-900">${item.particular || ''}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 uppercase">${item.transfer_method || ''}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 font-medium">${formatCurrency(withdraw)}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 font-medium">${formatCurrency(deposit)}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-semibold">${formatCurrency(balance)}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm ${
                        item.reconsilation_type === 1 ? 'text-green-600 font-semibold' :
                        item.reconsilation_type === 2 ? 'text-red-600 font-bold' :
                        'text-blue-600'
                    }">
                        ${item.reconsilation_type === 1 ? '+' : item.reconsilation_type === 2 ? '-' : ''}
                        ${formatCurrency(reconciliation)}
                    </td>
                `;
                tableBody.appendChild(row);
            });

            // Update summary
            document.getElementById('fullCurrentBalance').textContent = formatCurrency(currentBalance);
            document.getElementById('totalTransactions').textContent = data.length;
            document.getElementById('totalDeposit').textContent = formatCurrency(totalDeposit);
            document.getElementById('totalWithdraw').textContent = formatCurrency(totalWithdraw);
            document.getElementById('netChange').textContent = formatCurrency(totalDeposit - totalWithdraw);

            // Show content
            statementContent.classList.remove('hidden');
        }

        function formatCurrency(value) {
            const num = parseFloat(value || 0);
            return '৳ ' + num.toLocaleString('en-BD', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function filterStatement() {
            const fromDate = document.getElementById('fromDate').value;
            const toDate = document.getElementById('toDate').value;
            
            if (fromDate && toDate && fromDate > toDate) {
                alert('From date, To date এর পরে হতে পারবে না');
                return;
            }
            
            // Update print header
            let dateRange = '';
            if (fromDate || toDate) {
                dateRange = `Period: ${fromDate || 'Start'} to ${toDate || 'End'}`;
            } else {
                dateRange = 'All Transactions';
            }
            document.getElementById('printDateRange').textContent = dateRange;
            
            loadStatement(fromDate, toDate);
        }

        function resetFilter() {
            const today = new Date().toISOString().split('T')[0];
            const firstDay = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0];
            
            document.getElementById('fromDate').value = firstDay;
            document.getElementById('toDate').value = today;
            document.getElementById('printDateRange').textContent = `Period: ${firstDay} to ${today}`;
            
            loadStatement(firstDay, today);
        }

        function showNoData() {
            const statementContent = document.getElementById('statementContent');
            const noDataMessage = document.getElementById('noDataMessage');
            
            statementContent.classList.add('hidden');
            noDataMessage.classList.remove('hidden');
        }

        function showError(message) {
            const errorMessage = document.getElementById('errorMessage');
            const errorText = document.getElementById('errorText');
            
            errorText.textContent = message;
            errorMessage.classList.remove('hidden');
        }
    </script>
</body>

</html>