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
    <title>Accounting - Account Laser Records</title>
    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png" sizes="16x16">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .account-card {
            transition: all 0.3s ease;
            border-left: 4px solid #3b82f6;
            cursor: pointer;
        }

        .account-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            border-left-color: #2563eb;
        }

        .account-card.deposit {
            border-left-color: #10b981;
        }

        .account-card.withdraw {
            border-left-color: #ef4444;
        }

        .account-card.neutral {
            border-left-color: #6b7280;
        }

        .balance-badge {
            font-size: 1.1rem;
            font-weight: 600;
            padding: 0.4rem 1rem;
        }

        .category-tag {
            font-size: 0.75rem;
            padding: 0.2rem 0.6rem;
            background: #e0f2fe;
            color: #0369a1;
            border-radius: 20px;
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal-content {
            background: white;
            border-radius: 0.5rem;
            width: 90%;
            max-width: 1200px;
            max-height: 90vh;
            overflow-y: auto;
            transform: translateY(20px);
            transition: transform 0.3s ease;
        }

        .modal-overlay.active .modal-content {
            transform: translateY(0);
        }

        .spinner {
            width: 3rem;
            height: 3rem;
            border: 3px solid #e5e7eb;
            border-top-color: #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
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
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800 flex items-center">
                            <i class="fas fa-journal-whills text-blue-600 mr-3"></i>
                            Account Laser Records
                        </h2>
                        <p class="text-gray-600 mt-1">Click on any account card to view statement and add transactions</p>
                    </div>
                    <a href="./create-accounts.php"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200">
                        <i class="fas fa-plus-circle mr-2"></i>
                        Add New Account
                    </a>
                </div>

                <!-- Loading State -->
                <div id="loader" class="flex flex-col items-center justify-center min-h-[300px]">
                    <div class="spinner"></div>
                    <p class="mt-3 text-gray-600">Fetching data from <?php echo htmlspecialchars($base_ip_path); ?>...</p>
                </div>

                <!-- Error State -->
                <div id="error-message" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Failed to fetch data from <span id="api-error-url"></span>. Please check the network or API endpoint.
                    </div>
                </div>

                <!-- Cards Grid -->
                <div id="cardsContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 hidden">
                    <!-- Account cards will be dynamically inserted here -->
                </div>

                <!-- No Data State -->
                <div id="no-data-message" class="hidden text-center py-12">
                    <div class="mb-6">
                        <i class="fas fa-inbox text-gray-400 text-5xl mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-500 mb-2">No accounts found</h3>
                        <p class="text-gray-400 mb-6">No account laser records available at the moment.</p>
                        <a href="./create-accounts.php"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200">
                            <i class="fas fa-plus-circle mr-2"></i>
                            Create Your First Account
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Statement Modal -->
    <div id="statementModal" class="modal-overlay">
        <div class="modal-content">
            <div class="bg-blue-600 text-white p-4 rounded-t-lg flex justify-between items-center">
                <h5 class="text-lg font-semibold flex items-center">
                    <i class="fas fa-file-invoice-dollar mr-2"></i>
                    Statement for <span id="statementAccountName" class="ml-1"></span>
                </h5>
                <button onclick="closeStatementModal()" class="text-white hover:text-gray-200 text-xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="p-4">
                <!-- Current Balance Display -->
                <div class="bg-gray-50 p-4 rounded-lg mb-4">
                    <div class="flex justify-between items-center">
                        <div>
                            <h6 class="text-sm text-gray-500">Current Balance</h6>
                            <h3 id="currentBalanceDisplay" class="text-2xl font-bold text-gray-800">$0.00</h3>
                        </div>
                        <div class="text-right">
                            <h6 class="text-sm text-gray-500">Account Type</h6>
                            <span id="accountTypeBadge" class="inline-block px-3 py-1 rounded-full text-sm font-medium">
                                <span id="accountTypeText">Loading...</span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Transaction Input Section -->
                <div class="bg-blue-50 p-4 rounded-lg mb-6 border border-blue-200">
                    <h6 class="text-blue-700 font-semibold mb-3 flex items-center">
                        <i class="fas fa-plus-circle mr-2"></i> Add New Transaction
                    </h6>
                    <form id="transactionForm" class="space-y-4">
                        <input type="hidden" id="accountId" name="accountId">
                        <input type="hidden" id="accountName" name="accountName">
                        <input type="hidden" id="currentAccountBalance" name="currentAccountBalance">

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label for="paymentType" class="block text-sm font-medium text-gray-700 mb-1">Payment Type</label>
                                <select class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    id="paymentType" name="paymentType" required>
                                    <option value="">Select Type</option>
                                    <option value="Deposit">Deposit</option>
                                    <option value="Withdraw">Withdraw</option>
                                    <option value="Reconciliation">Reconciliation</option>
                                </select>
                            </div>
                            <!-- Reconciliation Type (Hidden initially) -->
                            <div id="reconciliationWrapper" class="hidden">
                                <label for="reconciliation_type" class="block text-sm font-medium text-gray-700 mb-1">
                                    Reconciliation Type
                                </label>
                                <select
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    id="reconciliation_type" name="reconciliation_type"
                                >
                                    <option value="">Select</option>
                                    <option value="0">Add</option>
                                    <option value="1">Deduct</option>
                                </select>
                            </div>
                            <div>
                                <label for="transactionDate" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                                <input type="date"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    id="transactionDate" name="transactionDate" required>
                            </div>
                            <div>
                                <label for="balance" class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                                <input type="number" step="0.01"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    id="balance" name="balance" required placeholder="0.00">
                            </div>
                            <div>
                                <label for="particular" class="block text-sm font-medium text-gray-700 mb-1">Particular</label>
                                <input type="text"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    id="particular" name="particular" placeholder="Transaction description">
                            </div>
                        </div>
                        <div class="text-right">
                            <button type="button"
                                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors"
                                id="saveTransactionBtn">
                                <span id="spinner" class="hidden spinner-border spinner-border-sm mr-2" role="status" aria-hidden="true"></span>
                                <span id="saveButtonText">Save Transaction</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Statement Filters and Actions -->
                <div class="flex flex-wrap justify-between items-center mb-4 bg-gray-50 p-3 rounded-lg">
                    <div class="flex flex-wrap items-center space-x-3">
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-600">From:</span>
                            <input type="date"
                                class="px-3 py-1 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                id="fromDateFilter">
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-600">To:</span>
                            <input type="date"
                                class="px-3 py-1 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                id="toDateFilter">
                        </div>
                        <button class="px-4 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700 transition-colors"
                            id="searchStatementBtn">
                            <i class="fas fa-search mr-1"></i> Search
                        </button>
                    </div>
                    <button class="px-4 py-1 bg-indigo-600 text-white rounded text-sm hover:bg-indigo-700 transition-colors flex items-center"
                        id="downloadCsvBtn">
                        <i class="fas fa-download mr-1"></i> Download CSV
                    </button>
                </div>

                <!-- Statement Loading -->
                <div id="statementLoader" class="flex flex-col items-center justify-center py-8 hidden">
                    <div class="spinner" style="border-top-color: #3b82f6;"></div>
                    <p class="mt-3 text-gray-600">Loading statement data...</p>
                </div>

                <!-- Statement Table -->
                <div id="statementTableContainer" class="hidden h-80 overflow-x-auto overflow-y-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Particular</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Withdraw</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deposit</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reconciliation</th>
                            </tr>
                        </thead>
                        <tbody id="statementTableBody" class="bg-white divide-y divide-gray-200">
                            <!-- Statement rows will be inserted here -->
                        </tbody>
                    </table>
                    <p id="noStatementData" class="hidden text-center text-gray-500 py-8">
                        <i class="fas fa-file-alt text-3xl mb-3 block"></i>
                        No transactions found for this account.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <?php include '../elements/floating-menus.php'; ?>

    <script src="../assets/js/script.js"></script>

    <script>
        const statementModal = document.getElementById('statementModal');
        function closeStatementModal() {
            statementModal.classList.remove('active');
            document.body.style.overflow = 'auto';
        }
        document.getElementById('paymentType').addEventListener('change', function () {
            const reconciliationWrapper = document.getElementById('reconciliationWrapper');
            const reconciliationSelect  = document.getElementById('reconciliation_type');
        
            if (this.value === 'Reconciliation') {
                reconciliationWrapper.classList.remove('hidden');
                reconciliationSelect.setAttribute('required', 'required');
            } else {
                reconciliationWrapper.classList.add('hidden');
                reconciliationSelect.removeAttribute('required');
                reconciliationSelect.value = '';
            }
        });
        document.addEventListener('DOMContentLoaded', function() {
            const IP_PATH = '<?php echo htmlspecialchars($base_ip_path); ?>';
            const API_FETCH_URL = `${IP_PATH}/api/ledgers/fetch_ledger_api.php`;
            const API_POST_URL = `${IP_PATH}/api/accounts/fetch_ledger_statement_api.php`;
            const API_STATEMENT_URL = `${IP_PATH}/api/accounts/fetch_account_statement_api.php`;

            // UI Elements
            const cardsContainer = document.getElementById('cardsContainer');
            const loader = document.getElementById('loader');
            const errorMessage = document.getElementById('error-message');
            const noDataMessage = document.getElementById('no-data-message');

            // Statement Modal Elements
            const statementTableBody = document.getElementById('statementTableBody');
            const statementLoader = document.getElementById('statementLoader');
            const statementTableContainer = document.getElementById('statementTableContainer');
            const noStatementData = document.getElementById('noStatementData');
            const saveTransactionBtn = document.getElementById('saveTransactionBtn');
            const transactionForm = document.getElementById('transactionForm');
            const downloadCsvBtn = document.getElementById('downloadCsvBtn');
            const searchStatementBtn = document.getElementById('searchStatementBtn');
            const fromDateFilter = document.getElementById('fromDateFilter');
            const toDateFilter = document.getElementById('toDateFilter');
            const spinner = document.getElementById('spinner');
            const saveButtonText = document.getElementById('saveButtonText');
            const currentBalanceDisplay = document.getElementById('currentBalanceDisplay');
            const accountTypeBadge = document.getElementById('accountTypeBadge');
            const accountTypeText = document.getElementById('accountTypeText');

            let currentStatementData = [];
            let currentAccountId = null;
            let currentAccountName = null;
            let currentAccountBalance = null;
            
            let displayIndex = 0;           // how many rows have been shown
            const pageSize = 10;             // rows per "page"
            const maxRows = 100;            // max rows to show


            // Modal Functions
            function openStatementModal() {
                statementModal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }

            // Close modal when clicking outside [Tarek Vai told me to stop this!]
            // statementModal.addEventListener('click', function(e) {
            //     if (e.target === statementModal) {
            //         closeStatementModal();
            //     }
            // });

            // Close modal with Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && statementModal.classList.contains('active')) {
                    closeStatementModal();
                }
            });

            // --- 1. FETCH AND DISPLAY ACCOUNT CARDS ---
            async function fetchAccounts() {
                try {
                    const response = await fetch(API_FETCH_URL);
                    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                    const result = await response.json();

                    if (result.success && Array.isArray(result.data)) {
                        displayAccountCards(result.data);
                    } else {
                        showNoData();
                    }
                } catch (error) {
                    console.error('Fetch error:', error);
                    showError();
                } finally {
                    loader.classList.add('hidden');
                }
            }

            function displayAccountCards(accounts) {
                cardsContainer.innerHTML = '';
                if (accounts.length === 0) {
                    showNoData();
                    return;
                }

                cardsContainer.classList.remove('hidden');

                accounts.forEach(account => {
                    const card = createAccountCard(account);
                    cardsContainer.appendChild(card);
                });
            }

            function createAccountCard(account) {
                const card = document.createElement('div');
                card.className = 'account-card bg-white rounded-lg shadow p-5 hover:shadow-lg transition-all duration-300';

                // Determine card color based on transaction type
                const transactionable = account.is_transactionable?.toLowerCase();
                if (transactionable === 'deposit') card.classList.add('deposit');
                else if (transactionable === 'withdraw') card.classList.add('withdraw');
                else card.classList.add('neutral');

                // Format balance
                const balance = parseFloat(account.balance || 0);

                const formattedBalance =
                    (balance < 0 ? '(৳ ' : '৳ ') +
                    Math.abs(balance).toLocaleString('en-BD', {
                        minimumFractionDigits: 2
                    }) +
                    (balance < 0 ? ')' : '');

                // Determine balance color
                let balanceColor = 'bg-gray-200 text-gray-800';
                if (balance > 0) balanceColor = 'bg-green-100 text-green-800';
                else if (balance < 0) balanceColor = 'bg-red-100 text-red-800';

                card.innerHTML = `
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <span class="category-tag">${account.category || 'Uncategorized'}</span>
                        </div>
                        <div class="text-right">
                            <span class="text-xs text-gray-500">${account['main_type'] || 'General'}</span>
                        </div>
                    </div>
                    
                    <h3 class="text-lg font-semibold text-gray-800 mb-2 truncate" title="${account.acc_name}">
                        ${account.acc_name}
                    </h3>
                    
                    <div class="mb-4">
                        <p class="text-sm text-gray-600 line-clamp-2" title="${account.description || 'No description'}">
                            ${account.description || 'No description provided'}
                        </p>
                    </div>
                    
                    <div class="flex justify-between items-center mt-4 pt-4 border-t border-gray-100">
                        <div>
                            <div class="text-xs text-gray-500 mb-1">Balance</div>
                            <div class="balance-badge ${balanceColor} rounded-full inline-block">
                                ${formattedBalance}
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-gray-500 mb-1">Type</div>
                            <span class="text-sm font-medium uppercase ${getTransactionTypeColor(account.is_transactionable)}">
                                ${account.is_transactionable || 'N/A'}
                            </span>
                        </div>
                    </div>
                    
                    <div class="mt-4 text-center">
                        <button class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center justify-center w-full">
                            <i class="fas fa-eye mr-2"></i>
                            View Statement
                        </button>
                    </div>
                `;

                // Add click event to open modal
                card.addEventListener('click', (e) => {
                    if (!e.target.closest('button')) {
                        openStatementModalForAccount(account.sys_id, account.acc_name, account.balance, account.is_transactionable);
                    }
                });

                // View button click
                const viewBtn = card.querySelector('button');
                viewBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    openStatementModalForAccount(account.sys_id, account.acc_name, account.balance, account.is_transactionable);
                });

                return card;
            }

            function getTransactionTypeColor(type) {
                if (!type) return 'text-gray-600';
                switch (type.toLowerCase()) {
                    case 'deposit':
                        return 'text-green-600';
                    case 'withdraw':
                        return 'text-red-600';
                    default:
                        return 'text-blue-600';
                }
            }

            // --- 2. STATEMENT MODAL FUNCTIONS ---
            function openStatementModalForAccount(accountId, accountName, accountBalance, transactionType = 'General') {
                currentAccountId = accountId;
                currentAccountName = accountName;
                currentAccountBalance = accountBalance;

                // Update modal header
                document.getElementById('statementAccountName').textContent = accountName;

                // Update current balance display
                const amount = parseFloat(accountBalance || 0);
                
                const formattedBalance =
                    (amount < 0 ? '(৳ ' : '৳ ') +
                    Math.abs(amount).toLocaleString('en-BD', {
                        minimumFractionDigits: 2
                    }) +
                    (amount < 0 ? ')' : '');
                currentBalanceDisplay.textContent = formattedBalance;

                // Update account type badge
                accountTypeText.textContent = transactionType || 'General';
                accountTypeBadge.className = `inline-block px-3 py-1 rounded-full text-sm font-medium uppercase ${getBadgeColor(transactionType)}`;

                // Prepare transaction form
                prepareTransactionForm(accountId, accountName, accountBalance);

                // Reset filters
                fromDateFilter.value = '';
                toDateFilter.value = '';

                // Show modal and fetch statement
                openStatementModal();
                fetchStatement(accountId);
            }

            function getBadgeColor(type) {
                if (!type) return 'bg-gray-100 text-gray-800';
                switch (type.toLowerCase()) {
                    case 'deposit':
                        return 'bg-green-100 text-green-800';
                    case 'withdraw':
                        return 'bg-red-100 text-red-800';
                    default:
                        return 'bg-blue-100 text-blue-800';
                }
            }

            function prepareTransactionForm(id, name, currentBalance) {
                document.getElementById('accountId').value = id;
                document.getElementById('accountName').value = name;
                document.getElementById('currentAccountBalance').value = currentBalance;

                // Set default date to today
                document.getElementById('transactionDate').valueAsDate = new Date();

                // Reset form fields
                document.getElementById('paymentType').value = '';
                document.getElementById('balance').value = '';
                document.getElementById('particular').value = '';

                saveTransactionBtn.disabled = false;
                saveButtonText.textContent = 'Save Transaction';
            }

            // --- 3. TRANSACTION SUBMISSION ---
            saveTransactionBtn.addEventListener('click', submitTransaction);

            async function submitTransaction() {
                if (!transactionForm.checkValidity()) {
                    transactionForm.reportValidity();
                    return;
                }

                const formData = new FormData(transactionForm);
                const postData = Object.fromEntries(formData.entries());
                
                console.log(postData);

                saveTransactionBtn.disabled = true;
                spinner.classList.remove('hidden');
                saveButtonText.textContent = ' Saving...';
                
                try {
                    const response = await fetch(API_POST_URL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(postData)
                    });

                    const result = await response.json();

                    if (response.ok && result.success) {
                        alert(`Success! Transaction saved for ${postData.accountName}.`);
                        
                        currentAccountBalance = result.new_balance;

                        // Refresh statement and reset form
                        prepareTransactionForm(currentAccountId, currentAccountName, currentAccountBalance);
                        fetchStatement(currentAccountId);

                        // Also refresh the account cards to update balances
                        setTimeout(fetchAccounts, 500);
                        
                        const newBalance = parseFloat(result.new_balance || 0);
                
                        const formattedNewBalance =
                            (newBalance < 0 ? '(৳ ' : '৳ ') +
                            Math.abs(newBalance).toLocaleString('en-BD', {
                                minimumFractionDigits: 2
                            }) +
                            (newBalance < 0 ? ')' : '');
                        
                        
                        currentBalanceDisplay.innerHTML = formattedNewBalance;

                    } else {
                        const errorMsg = result.error || 'Unknown error occurred on the server.';
                        alert(`Failed to save transaction: ${errorMsg}`);
                    }

                } catch (error) {
                    console.error('Submission error:', error);
                    alert('Network error or server connection failed. See console for details.');
                } finally {
                    saveTransactionBtn.disabled = false;
                    spinner.classList.add('hidden');
                    saveButtonText.textContent = 'Save Transaction';
                }
            }

            // --- 4. STATEMENT FETCHING AND DISPLAY ---
            async function fetchStatement(accountId, fromDate = null, toDate = null) {
                statementLoader.classList.remove('hidden');
                statementTableBody.innerHTML = '';
                statementTableContainer.classList.add('hidden');
                noStatementData.classList.add('hidden');
                downloadCsvBtn.disabled = true;

                let url = `${API_STATEMENT_URL}?ledger_db_id=${accountId}`;
                if (fromDate) url += `&from_date=${fromDate}`;
                if (toDate) url += `&to_date=${toDate}`;

                try {
                    const response = await fetch(url);
                    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

                    const result = await response.json();

                    if (result.success && Array.isArray(result.data)) {
                        displayStatement(result.data);
                    } else {
                        showNoStatementData();
                    }

                } catch (error) {
                    console.error('Statement fetch error:', error);
                    showStatementError();
                } finally {
                    statementLoader.classList.add('hidden');
                }
            }

            // function displayStatement(data) {
            //     currentStatementData = data;

            //     if (data.length === 0) {
            //         showNoStatementData();
            //         return;
            //     }

            //     data.forEach(item => {
            //         const row = document.createElement('tr');
            //         row.innerHTML = `
            //             <td class="px-4 py-3 text-sm text-gray-900">${item.date || ''}</td>
            //             <td class="px-4 py-3 text-sm text-gray-900">${item.particular || ''}</td>
            //             <td class="px-4 py-3 text-sm text-red-600 font-medium">${formatCurrency(item.withdraw)}</td>
            //             <td class="px-4 py-3 text-sm text-green-600 font-medium">${formatCurrency(item.deposit)}</td>
            //             <td class="px-4 py-3 text-sm text-gray-900 font-semibold">${formatCurrency(item.balance)}</td>
            //             <td class="px-4 py-3 text-sm text-blue-600">${formatCurrency(item.reconsilation)}</td>
            //         `;
            //         statementTableBody.appendChild(row);
            //     });
            
            //     statementTableContainer.classList.remove('hidden');
            //     downloadCsvBtn.disabled = false;
            // }
            
            
            function displayStatement(data) {
                currentStatementData = data;
                displayIndex = 0; // reset index
            
                // clear old table
                statementTableBody.innerHTML = '';
            
                if (data.length === 0) {
                    showNoStatementData();
                    return;
                }
            
                // initially load first page
                loadNextRows();
            
                statementTableContainer.classList.remove('hidden');
                downloadCsvBtn.disabled = false;
            
                // attach scroll listener for lazy loading
                statementTableContainer.removeEventListener('scroll', handleScroll);
                statementTableContainer.addEventListener('scroll', handleScroll);
            }
            
            function loadNextRows() {
                // calculate how many rows we can show
                const remaining = currentStatementData.length - displayIndex;
                const rowsToLoad = Math.min(pageSize, remaining, maxRows - displayIndex);
            
                for (let i = displayIndex; i < displayIndex + rowsToLoad; i++) {
                    const item = currentStatementData[i];
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td class="px-4 py-3 text-sm text-gray-900">${item.date || ''}</td>
                        <td class="px-4 py-3 text-sm text-gray-900">${item.particular || ''}</td>
                        <td class="px-4 py-3 text-sm text-red-600 font-medium">${formatCurrency(item.withdraw)}</td>
                        <td class="px-4 py-3 text-sm text-green-600 font-medium">${formatCurrency(item.deposit)}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 font-semibold">${formatCurrency(item.balance)}</td>
                        <td class="px-4 py-3 text-sm ${
                            item.reconsilation_type === 0
                                ? 'text-green-600 font-semibold'
                                : item.reconsilation_type === 1
                                ? 'text-red-600 font-bold'
                                : 'text-blue-600'
                        }">
                        ${
                            item.reconsilation_type === 0
                                ? '+'
                                : item.reconsilation_type === 1
                                ? '-'
                                : ''
                        }
                        ${formatCurrency(item.reconsilation)}
                        </td>              
                    `;
                    statementTableBody.appendChild(row);
                }
            
                displayIndex += rowsToLoad;
            }
            
            let isLoading = false; // prevent multiple loads at the same time

            function handleScroll() {
                const container = statementTableContainer;
            
                if (container.scrollTop + container.clientHeight >= container.scrollHeight - 5) {
                    if (!isLoading && displayIndex < currentStatementData.length && displayIndex < maxRows) {
                        isLoading = true;
                        setTimeout(() => {
                            loadNextRows();
                            isLoading = false;
                        }, 200); // 200ms delay, adjust for smoothness
                    }
                }
            }

            // --- 5. HELPER FUNCTIONS ---
            function formatCurrency(value) {
                const num = parseFloat(value || 0);
                return num.toLocaleString('en-BD', {
                    style: 'currency',
                    currency: 'BDT'
                });
            }

            function showStatementError() {
                statementTableBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-red-600">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Failed to load statement data.
                        </td>
                    </tr>
                `;
                statementTableContainer.classList.remove('hidden');
                downloadCsvBtn.disabled = true;
            }

            function showNoStatementData() {
                noStatementData.classList.remove('hidden');
                statementTableContainer.classList.remove('hidden');
                downloadCsvBtn.disabled = true;
            }

            function showError() {
                errorMessage.classList.remove('hidden');
                cardsContainer.classList.add('hidden');
            }

            function showNoData() {
                noDataMessage.classList.remove('hidden');
                cardsContainer.classList.add('hidden');
            }

            // --- 6. EVENT LISTENERS ---
            searchStatementBtn.addEventListener('click', function() {
                const fromDate = fromDateFilter.value;
                const toDate = toDateFilter.value;
                
                if (fromDate && toDate && fromDate > toDate) {
                    alert('From date, To date এর পরে হতে পারবে না');
                    return;
                }
    
                fetchStatement(currentAccountId, fromDate, toDate);
            });

            downloadCsvBtn.addEventListener('click', function() {
                if (currentStatementData.length === 0) {
                    alert('No data to download.');
                    return;
                }

                const headers = ['Date', 'Particular', 'Withdraw', 'Deposit', 'Balance', 'Reconciliation'];
                const rows = currentStatementData.map(item => [
                    item.date,
                    item.particular || '',
                    parseFloat(item.withdraw || 0).toFixed(2),
                    parseFloat(item.deposit || 0).toFixed(2),
                    parseFloat(item.balance || 0).toFixed(2),
                    parseFloat(item.reconsilation || 0).toFixed(2)
                ]);

                let csvContent = headers.join(',') + '\n';
                rows.forEach(row => {
                    csvContent += row.join(',') + '\n';
                });

                const blob = new Blob([csvContent], {
                    type: 'text/csv;charset=utf-8;'
                });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.setAttribute('href', url);
                link.setAttribute('download', `Statement_${currentAccountName}_${new Date().toISOString().slice(0,10)}.csv`);
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });

            // Initialize
            fetchAccounts();
        });
    </script>
</body>

</html>