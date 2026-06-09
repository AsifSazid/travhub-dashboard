<?php
include_once('./authenticate.php');
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
    <title>Accounting - Account Ledger Records</title>
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
        
        .historical-badge {
            background: #fef3c7;
            color: #92400e;
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: 9999px;
            display: inline-block;
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
                            Account Ledger Records
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
                        <p class="text-gray-400 mb-6">No account ledger records available at the moment.</p>
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
                <a id="showFullStmt" href="#" target="_blank">
                    <h5 class="text-lg font-semibold flex items-center">
                        <i class="fas fa-external-link-alt mr-2"></i>
                        Show Full Statement for <span id="statementAccountName" class="ml-1"></span>
                    </h5>
                </a>
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
                            <h3 id="currentBalanceDisplay" class="text-2xl font-bold text-gray-800">৳ 0.00</h3>
                        </div>
                        <div class="text-right">
                            <h6 class="text-sm text-gray-500">Account Type</h6>
                            <span id="accountTypeBadge">
                                <span id="accountTypeText">Loading...</span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Transaction Input Section -->
                <div id="trxnSection" class="bg-blue-50 p-4 rounded-lg mb-6 border border-blue-200">
                    <h6 class="text-blue-700 font-semibold mb-3 flex items-center">
                        <i class="fas fa-plus-circle mr-2"></i> Add New Transaction
                    </h6>
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 mb-4 text-sm text-yellow-700">
                        <p><i class="fas fa-info-circle mr-2"></i> <strong>নিয়মসমূহ:</strong></p>
                        <ul class="list-disc list-inside ml-4 mt-1">
                            <!--<li>সর্বোচ্চ ৫ দিন পর্যন্ত ব্যাকডেটেড entry করা যাবে</li>-->
                            <li>Backdated entries can be made up to a maximum of 8 days.</li>
                            <li><span class="font-semibold">Opening Balance এর আগের তারিখের entry শুধু সংরক্ষিত হবে (ব্যালেন্সে যোগ হবে না)</span></li>
                            <li>ব্যাকডেটেড entry করলে পরবর্তী সকল entry স্বয়ংক্রিয়ভাবে পুনরায় ক্যালকুলেট হবে</li>
                        </ul>
                    </div>
                    
                    <!-- নতুন: Opening Balance Information -->
                    <div id="openingDateInfo" class="bg-purple-50 border-l-4 border-purple-400 p-2 mb-3 text-sm text-purple-700 hidden">
                        <i class="fas fa-calendar-alt mr-2"></i>
                        <span id="openingDateText"></span>
                    </div>
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
                                    <option value="1">Add</option>
                                    <option value="2">Deduct</option>
                                </select>
                            </div>
                            <div>
                                <label for="transactionDate" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                                <input type="date"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    id="transactionDate" name="transactionDate" required>
                                <p id="dateWarning" class="text-xs text-red-500 mt-1 hidden"></p>
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
                    <div class="flex flex-wrap items-center space-x-3">
                        <button class="px-4 py-1 bg-indigo-600 text-white rounded text-sm hover:bg-indigo-700 transition-colors flex items-center"
                            id="downloadCsvBtn">
                            <i class="fas fa-download mr-1"></i> Download CSV
                        </button>
                        <button class="px-4 py-1 bg-green-600 text-white rounded text-sm hover:bg-green-700 transition-colors flex items-center"
                            id="downloadXlBtn">
                            <i class="fas fa-file-excel mr-1"></i> Download XL
                        </button>
                    </div>
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
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Withdraw</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deposit</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reconciliation</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
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

    <script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>

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
            const API_POST_URL = `${IP_PATH}/api/ledgers/store_ledger_statement.php`;
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
            const downloadXlBtn = document.getElementById('downloadXlBtn');
            const searchStatementBtn = document.getElementById('searchStatementBtn');
            const fromDateFilter = document.getElementById('fromDateFilter');
            const toDateFilter = document.getElementById('toDateFilter');
            const spinner = document.getElementById('spinner');
            const saveButtonText = document.getElementById('saveButtonText');
            const currentBalanceDisplay = document.getElementById('currentBalanceDisplay');
            const accountTypeBadge = document.getElementById('accountTypeBadge');
            const accountTypeText = document.getElementById('accountTypeText');
            const trxnSection = document.getElementById('trxnSection');
            const dateWarning = document.getElementById('dateWarning');
            const transactionDate = document.getElementById('transactionDate');

            let currentStatementData = [];
            let currentAccountId = null;
            let currentAccountName = null;
            let currentAccountBalance = null;
            let openingDate = null;
            
            let displayIndex = 0;
            const pageSize = 10;
            const maxRows = 100;

            // ফাংশন: সর্বোচ্চ ৫ দিন আগের তারিখ পর্যন্ত সেট করা (শুধু নরমাল entryর জন্য)
            function setMaxBackdatedDate() {
                const today = new Date();
                
                // সর্বোচ্চ ৫ দিন আগের তারিখ
                const maxBackdated = new Date(today);
                maxBackdated.setDate(today.getDate() - 8);
                // maxBackdated.setDate(today.getDate() - 5);
                
                const minDate = maxBackdated.toISOString().split('T')[0];
                const maxDate = today.toISOString().split('T')[0];
                
                // এই min/max শুধু UI তে দেখানোর জন্য, কিন্তু আমরা JavaScript এ ওভাররাইড করব
                transactionDate.setAttribute('min', minDate);
                transactionDate.setAttribute('max', maxDate);
            }
            
            setMaxBackdatedDate();

            // ফাংশন: Opening Balance এর তারিখ বের করা
            // fetchOpeningDate ফাংশন আপডেট
            async function fetchOpeningDate(accountId) {
                try {
                    const response = await fetch(`${API_STATEMENT_URL}?ledger_db_id=${accountId}&opening_only=1`);
                    const result = await response.json();
                    if (result.success && result.data && result.data.length > 0) {
                        openingDate = result.data[0].date;
                        
                        // Opening Date Info দেখান
                        const openingDateInfo = document.getElementById('openingDateInfo');
                        const openingDateText = document.getElementById('openingDateText');
                        
                        const dateObj = new Date(openingDate);
                        const formattedDate = dateObj.toLocaleDateString('bn-BD', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        });
                        
                        openingDateText.innerHTML = `এই অ্যাকাউন্টের Opening Balance: <strong>${formattedDate}</strong>। এর আগের তারিখে entry করলে তা শুধু সংরক্ষিত হবে, ব্যালেন্সে যোগ হবে না।`;
                        openingDateInfo.classList.remove('hidden');
                        
                        // Opening Balance এর আগের তারিখ entry করার অনুমতি দিতে
                        transactionDate.removeAttribute('min');
                        
                        const today = new Date();
                        const maxDate = today.toISOString().split('T')[0];
                        transactionDate.setAttribute('max', maxDate);
                    } else {
                        openingDate = null;
                        document.getElementById('openingDateInfo').classList.add('hidden');
                        setMaxBackdatedDate();
                    }
                } catch (error) {
                    console.error('Error fetching opening date:', error);
                    openingDate = null;
                    document.getElementById('openingDateInfo').classList.add('hidden');
                    setMaxBackdatedDate();
                }
            }

            // ফাংশন: তারিখ ভ্যালিডেশন
            function validateTransactionDate(selectedDate) {
                const selected = new Date(selectedDate);
                const today = new Date();
                
                selected.setHours(0, 0, 0, 0);
                today.setHours(0, 0, 0, 0);
                
                const diffTime = today - selected;
                const diffDays = diffTime / (1000 * 60 * 60 * 24);
                
                // নিয়ম ১: Opening Balance এর আগের তারিখ চেক
                if (openingDate && selectedDate < openingDate) {
                    return {
                        valid: true,
                        warning: 'আপনি Opening Balance এর আগের তারিখে entry করছেন। এই entry শুধু সংরক্ষিত হবে, ব্যালেন্স ক্যালকুলেশনে যোগ হবে না।',
                        isHistorical: true
                    };
                }
                
                // নিয়ম ২: ৫ দিনের বেশি ব্যাকডেটেড চেক (শুধু নন-হিস্টোরিক্যাল entryর জন্য)
                // if (diffDays > 5) {
                if (diffDays > 8) {
                    return {
                        valid: false,
                        message: 'You can make backdated entries up to a maximum of 8 (EIGHT) days. Entry older than this is not possible.'
                        // message: 'আপনি সর্বোচ্চ ৫ দিন পর্যন্ত ব্যাকডেটেড entry করতে পারবেন। এর বেশি পুরনো তারিখে entry সম্ভব নয়।'
                    };
                }
                
                return { valid: true, isHistorical: false };
            }

            // Date input এ পরিবর্তন হলে ওয়ার্নিং দেখানো
            transactionDate.addEventListener('change', function() {
                const validation = validateTransactionDate(this.value);
                if (!validation.valid) {
                    dateWarning.textContent = validation.message;
                    dateWarning.classList.remove('hidden');
                    dateWarning.classList.add('text-red-500');
                    saveTransactionBtn.disabled = true; // বাটন ডিজেবল করে দিন
                } else if (validation.warning) {
                    dateWarning.textContent = validation.warning;
                    dateWarning.classList.remove('hidden');
                    dateWarning.classList.remove('text-red-500');
                    dateWarning.classList.add('text-yellow-600');
                    saveTransactionBtn.disabled = false; // বাটন সক্রিয় রাখুন
                } else {
                    dateWarning.classList.add('hidden');
                    saveTransactionBtn.disabled = false;
                }
            });

            // Modal Functions
            function openStatementModal() {
                statementModal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }

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

                const transactionable = account.is_transactionable?.toLowerCase();
                if (transactionable === 'deposit') card.classList.add('deposit');
                else if (transactionable === 'withdraw') card.classList.add('withdraw');
                else card.classList.add('neutral');

                const balance = parseFloat(account.balance || 0);
                const formattedBalance = (balance < 0 ? '(৳ ' : '৳ ') +
                    Math.abs(balance).toLocaleString('en-BD', { minimumFractionDigits: 2 }) +
                    (balance < 0 ? ')' : '');

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

                card.addEventListener('click', (e) => {
                    if (!e.target.closest('button')) {
                        openStatementModalForAccount(account.sys_id, account.acc_name, account.balance, account.is_transactionable);
                    }
                });

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
            async function openStatementModalForAccount(accountId, accountName, accountBalance, transactionType = 'General') {
                currentAccountId = accountId;
                currentAccountName = accountName;
                currentAccountBalance = accountBalance;
            
                // Opening Balance এর তারিখ লোড (এটা এখন async)
                await fetchOpeningDate(accountId);
            
                document.getElementById('statementAccountName').textContent = accountName;
                
                const showFullStmtLink = document.getElementById('showFullStmt');
                showFullStmtLink.href = `show-full-statement.php?acc_id=${accountId}`;
                showFullStmtLink.target = "_blank";
            
                const amount = parseFloat(accountBalance || 0);
                const formattedBalance = (amount < 0 ? '(৳ ' : '৳ ') +
                    Math.abs(amount).toLocaleString('en-BD', { minimumFractionDigits: 2 }) +
                    (amount < 0 ? ')' : '');
                currentBalanceDisplay.textContent = formattedBalance;
            
                accountTypeText.textContent = transactionType || 'General';
                accountTypeBadge.className = `inline-block mt-2 px-3 py-1 rounded-full text-sm font-medium uppercase ${getBadgeColor(transactionType)}`;
            
                if(transactionType == 'yes') {
                    prepareTransactionForm(accountId, accountName, accountBalance);
                } else {
                    trxnSection.classList.add('hidden');
                }
            
                fromDateFilter.value = '';
                toDateFilter.value = '';
            
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
                document.getElementById('transactionDate').valueAsDate = new Date();
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

                const selectedDate = document.getElementById('transactionDate').value;
                const validation = validateTransactionDate(selectedDate);
                
                if (!validation.valid) {
                    alert(validation.message);
                    return;
                }
                
                if (validation.warning) {
                    if (!confirm(validation.warning + '\n\nতবুও কি এগিয়ে যেতে চান?')) {
                        return;
                    }
                }

                const formData = new FormData(transactionForm);
                const postData = Object.fromEntries(formData.entries());
                
                // *** IMPORTANT: reconciliation_type ঠিক করে পাঠানো ***
                if (postData.paymentType !== 'Reconciliation') {
                    // Deposit বা Withdraw হলে reconciliation_type বাদ দিন
                    delete postData.reconciliation_type;
                } else {
                    // Reconciliation হলে numeric value নিশ্চিত করুন
                    if (postData.reconciliation_type === '') {
                        alert('দয়া করে Reconciliation Type নির্বাচন করুন');
                        return;
                    }
                    postData.reconciliation_type = parseInt(postData.reconciliation_type);
                }
                
                // is_historical ফ্ল্যাগ যোগ করা
                if (validation.isHistorical) {
                    postData.is_historical = '1';
                }

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
                        let message = `✅ ট্রানজেকশন সফল হয়েছে!`;
                        if (result.is_historical) {
                            message = `🟡 ঐতিহাসিক entry সংরক্ষিত হয়েছে (Opening Balance এর আগের তারিখ)। এই entry ব্যালেন্স ক্যালকুলেশনে যোগ হবে না।`;
                        } else if (result.recalculated) {
                            message = `🔄 ব্যাকডেটেড entry সফল হয়েছে। ${result.recalculated_date} থেকে পরবর্তী সকল entry পুনরায় ক্যালকুলেট করা হয়েছে।`;
                        }
                        
                        alert(message);
                        
                        currentAccountBalance = result.new_balance;
                        prepareTransactionForm(currentAccountId, currentAccountName, currentAccountBalance);
                        fetchStatement(currentAccountId);
                        setTimeout(fetchAccounts, 500);
                        
                        const newBalance = parseFloat(result.new_balance || 0);
                        const formattedNewBalance = (newBalance < 0 ? '(৳ ' : '৳ ') +
                            Math.abs(newBalance).toLocaleString('en-BD', { minimumFractionDigits: 2 }) +
                            (newBalance < 0 ? ')' : '');
                        
                        currentBalanceDisplay.innerHTML = formattedNewBalance;

                    } else {
                        const errorMsg = result.message || result.error || 'Unknown error occurred on the server.';
                        alert(`❌ ট্রানজেকশন ব্যর্থ হয়েছে: ${errorMsg}`);
                    }

                } catch (error) {
                    console.error('Submission error:', error);
                    alert('❌ নেটওয়ার্ক ত্রুটি বা সার্ভার সংযোগ ব্যর্থ হয়েছে।');
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
                downloadXlBtn.disabled = true;

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
            
            function displayStatement(data) {
                currentStatementData = data; // এটা এখন DESC order এ আছে
                
                // data already in DESC order from API, so no need to sort
                
                displayIndex = 0;
                statementTableBody.innerHTML = '';
            
                if (data.length === 0) {
                    showNoStatementData();
                    return;
                }
            
                loadNextRows();
                statementTableContainer.classList.remove('hidden');
                downloadCsvBtn.disabled = false;
                downloadXlBtn.disabled = false;
            
                statementTableContainer.removeEventListener('scroll', handleScroll);
                statementTableContainer.addEventListener('scroll', handleScroll);
            }
            
            window.printReceipt = function(itemString) {
                try {
                    const item = JSON.parse(decodeURIComponent(itemString));
                    const printWindow = window.open('', '_blank', 'width=350,height=600');
                    
                    const receiptContent = `
                        <html>
                        <head>
                            <title>Receipt - ${item.sys_id}</title>
                            <style>
                                @page { margin: 0; }
                                body { 
                                    font-family: 'Poppins', sans-serif;
                                    width: 58mm; 
                                    padding: 2mm; 
                                    margin: 0; 
                                    font-size: 14px;
                                    color: #000;
                                    font-weight: 600;
                                }
                                .logo-container { text-align: center; margin-bottom: 5px; }
                                .logo-container img { width: 20mm; height: auto; filter: grayscale(100%) contrast(150%); }
                                .receipt-container { width: 100%; }
                                .header { text-align: center; margin-bottom: 8px; }
                                .brand { font-size: 20px; font-weight: 900; margin: 0; text-transform: uppercase; }
                                .separator { border-top: 2px dashed #000; margin: 5px 0; }
                                .row { display: flex; justify-content: space-between; margin: 4px 0; line-height: 1.1; }
                                .row span:first-child { text-align: left; }
                                .row span:last-child { text-align: right; font-weight: 900; }
                                .total-row { font-size: 16px; margin-top: 8px; border-top: 2px solid #000; padding-top: 5px; }
                                .footer { text-align: center; margin-top: 15px; font-size: 11px; font-weight: normal; }
                                .historical-badge { background: #fef3c7; color: #92400e; padding: 2px 5px; border-radius: 3px; font-size: 10px; }
                                @media print {
                                    body { width: 58mm; -webkit-print-color-adjust: exact; }
                                }
                            </style>
                        </head>
                        <body>
                            <div class="receipt-container">
                                <div class="logo-container">
                                    <img src="../assets/images/logo/round-logo.png" alt="TravHub Global Limited">
                                </div>
                                
                                <div class="header">
                                    <p class="brand">TravHub Global Limited</p>
                                    <small>OFFICIAL RECEIPT</small>
                                </div>
                                
                                <div class="separator"></div>
                                
                                <div class="row"><span>DATE:</span> <span>${item.date}</span></div>
                                <div class="row"><span>SYS ID:</span> <span>${item.sys_id}</span></div>
                                <div class="row"><span>NAME:</span> <span>${item.name.toUpperCase()}</span></div>
                                
                                ${item.is_historical == 1 ? '<div class="row"><span class="historical-badge">ঐতিহাসিক entry (ক্যালকুলেশনের বাইরে)</span></div>' : ''}
                                
                                <div class="separator"></div>
                                
                                <div class="row">
                                    <span>PARTICULAR:</span> 
                                    <span>
                                        ${item.particular && item.particular.length > 16 
                                            ? item.particular.substring(0, 8) + '...' + item.particular.slice(-5) 
                                            : item.particular || ''}
                                    </span>
                                </div>
                                <div class="row"><span>METHOD:</span> <span>${item.transfer_method || 'CASH'}</span></div>
                                
                                <div class="row total-row">
                                    <span>TOTAL AMOUNT:</span> 
                                    <span>
                                        ${item.deposit > 0 ? item.deposit : item.withdraw} TK
                                    </span>
                                </div>
                                
                                <div class="separator"></div>
                                
                                <div class="footer">
                                    <strong>THANK YOU!</strong><br>
                                    ${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                                </div>
                            </div>
                            <script>
                                window.onload = function() { 
                                    window.print(); 
                                    setTimeout(function() { window.close(); }, 500); 
                                }
                            <\/script>
                        </body>
                        </html>
                    `;
                    printWindow.document.write(receiptContent);
                    printWindow.document.close();
                } catch (e) {
                    console.error("Error parsing item data", e);
                }
            };
            
            function loadNextRows() {
                const remaining = currentStatementData.length - displayIndex;
                const rowsToLoad = Math.min(pageSize, remaining, maxRows - displayIndex);
            
                for (let i = displayIndex; i < displayIndex + rowsToLoad; i++) {
                    const item = currentStatementData[i];
                    const itemData = encodeURIComponent(JSON.stringify(item));
                    const row = document.createElement('tr');
                    
                    let typeBadge = '';
                    if (item.is_historical == 1) {
                        typeBadge = '<span class="historical-badge">ঐতিহাসিক</span>';
                    } else {
                        typeBadge = '<span class="text-xs text-gray-500">বর্তমান</span>';
                    }
                    
                    row.innerHTML = `
                        <td class="px-4 py-3 text-sm text-gray-900">${item.date || ''}</td>
                        <td class="px-4 py-3 text-sm text-gray-900">${item.particular || ''}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 uppercase">${item.transfer_method || ''}</td>
                        <td class="px-4 py-3 text-sm text-red-600 font-medium">${formatCurrency(item.withdraw)}</td>
                        <td class="px-4 py-3 text-sm text-green-600 font-medium">${formatCurrency(item.deposit)}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 font-semibold">${formatCurrency(item.balance)}</td>
                        <td class="px-4 py-3 text-sm ${
                            item.reconsilation_type === 1
                                ? 'text-green-600 font-semibold'
                                : item.reconsilation_type === 2
                                ? 'text-red-600 font-bold'
                                : 'text-blue-600'
                        }">
                            ${item.reconsilation_type === 1 ? '+' : item.reconsilation_type === 2 ? '-' : ''}
                            ${formatCurrency(item.reconsilation)}
                        </td>   
                        <td class="px-4 py-3 text-sm">${typeBadge}</td>
                        <td class="px-4 py-3 text-sm">
                            <button onclick="printReceipt('${itemData}')" class="text-blue-600 hover:text-blue-800">
                                <i class="fa-solid fa-print"></i>
                            </button>
                        </td>
                    `;
                    statementTableBody.appendChild(row);
                }
            
                displayIndex += rowsToLoad;
            }
            
            let isLoading = false;

            function handleScroll() {
                const container = statementTableContainer;
            
                if (container.scrollTop + container.clientHeight >= container.scrollHeight - 5) {
                    if (!isLoading && displayIndex < currentStatementData.length && displayIndex < maxRows) {
                        isLoading = true;
                        setTimeout(() => {
                            loadNextRows();
                            isLoading = false;
                        }, 200);
                    }
                }
            }

            // --- 5. HELPER FUNCTIONS ---
            function formatCurrency(value) {
                const num = parseFloat(value || 0);
                return '৳ ' + num.toLocaleString('en-BD', { minimumFractionDigits: 2 });
            }

            function showStatementError() {
                statementTableBody.innerHTML = `
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-red-600">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Failed to load statement data.
                        </td>
                    </tr>
                `;
                statementTableContainer.classList.remove('hidden');
                downloadCsvBtn.disabled = true;
                downloadXlBtn.disabled = true;
            }

            function showNoStatementData() {
                noStatementData.classList.remove('hidden');
                statementTableContainer.classList.remove('hidden');
                downloadCsvBtn.disabled = true;
                downloadXlBtn.disabled = true;
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
                    alert('"থেকে" তারিখ " পর্যন্ত" তারিখের পরে হতে পারবে না');
                    return;
                }
    
                fetchStatement(currentAccountId, fromDate, toDate);
            });

            downloadCsvBtn.addEventListener('click', function() {
                if (currentStatementData.length === 0) {
                    alert('No data to download.');
                    return;
                }

                const headers = ['Date', 'Particular', 'Withdraw', 'Deposit', 'Balance', 'Reconciliation', 'Type'];
                const rows = currentStatementData.map(item => [
                    item.date,
                    item.particular || '',
                    parseFloat(item.withdraw || 0).toFixed(2),
                    parseFloat(item.deposit || 0).toFixed(2),
                    parseFloat(item.balance || 0).toFixed(2),
                    parseFloat(item.reconsilation || 0).toFixed(2),
                    item.is_historical == 1 ? 'Historical' : 'Current'
                ]);

                let csvContent = headers.join(',') + '\n';
                rows.forEach(row => {
                    csvContent += row.join(',') + '\n';
                });

                const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.setAttribute('href', url);
                link.setAttribute('download', `Statement_${currentAccountName}_${new Date().toISOString().slice(0,10)}.csv`);
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });
            
            downloadXlBtn.addEventListener('click', function() {
                if (currentStatementData.length === 0) {
                    alert('No data to download.');
                    return;
                }
            
                const rows = currentStatementData.map(item => `
                    <tr>
                        <td>${item.date || ''}</td>
                        <td>${item.particular || ''}</td>
                        <td>${item.transfer_method || ''}</td>
                        <td>${parseFloat(item.withdraw || 0).toFixed(2)}</td>
                        <td>${parseFloat(item.deposit || 0).toFixed(2)}</td>
                        <td>${parseFloat(item.balance || 0).toFixed(2)}</td>
                        <td>${parseFloat(item.reconsilation || 0).toFixed(2)}</td>
                        <td>${item.is_historical == 1 ? 'Historical' : 'Current'}</td>
                    </tr>
                `).join('');
            
                const excelContent = `
                    <html>
                    <head><meta charset="UTF-8"></head>
                    <body>
                        <table border="1">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Particular</th>
                                    <th>Method</th>
                                    <th>Withdraw</th>
                                    <th>Deposit</th>
                                    <th>Balance</th>
                                    <th>Reconciliation</th>
                                    <th>Type</th>
                                </tr>
                            </thead>
                            <tbody>${rows}</tbody>
                        </table>
                    </body>
                    </html>
                `;
            
                const blob = new Blob([excelContent], {
                    type: 'application/vnd.ms-excel;charset=utf-8;'
                });
            
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `Statement_${currentAccountName}_${new Date().toISOString().slice(0,10)}.xls`;
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