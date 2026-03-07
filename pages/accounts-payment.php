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
    <title>Accounting - Payments</title>
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
        
        .hidden {
            display: none !important;
        }
    </style>
</head>

<body class="bg-gray-50 font-sans">
    <!-- Top Navigation -->
    <?php include '../elements/header.php'; ?>

    <!-- Sidebar -->
    <?php include '../elements/aside.php'; ?>
    
    <!-- Main Content -->
    <main id="mainContent" class="pt-16 pl-0 lg:pl-64 lg:my-16 transition-all duration-300 h-full">
        <div class="p-4 md:p-6 h-full">

            <!-- Account Statement Container -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <!-- Blue Header -->
                <div class="bg-blue-600 text-white p-4 md:p-6">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center space-y-4 md:space-y-0">
                        <div>
                            <h2 class="text-xl font-semibold flex items-center">
                                <i class="fas fa-file-invoice-dollar mr-2"></i>
                                Payment
                            </h2>
                        </div>
                        <div class="flex items-center space-x-3">
                            <button onclick="window.history.back()" 
                                class="px-4 py-2 bg-blue-700 hover:bg-blue-800 text-white rounded-lg flex items-center transition-colors">
                                <i class="fas fa-arrow-left mr-2"></i> Back
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Content Area -->
                <div class="p-4 md:p-6">
                    <!-- Transaction Input Section -->
                    <div class="bg-blue-50 p-4 md:p-6 rounded-lg mb-6 border border-blue-200">
                        <h6 class="text-blue-700 font-semibold mb-4 flex items-center text-lg">
                            <i class="fas fa-minus-circle mr-2"></i> Make Payment
                        </h6>
                        
                        <!-- Rules Notice -->
                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 mb-4 text-sm text-yellow-700">
                            <p><i class="fas fa-info-circle mr-2"></i> <strong>নিয়মসমূহ:</strong></p>
                            <ul class="list-disc list-inside ml-4 mt-1">
                                <!--<li>সর্বোচ্চ ৫ দিন পর্যন্ত ব্যাকডেটেড entry করা যাবে</li>-->
                                <li>Backdated entries can be made up to a maximum of 8 days.</li>
                                <li>Opening Balance এর আগের তারিখের entry শুধু সংরক্ষিত হবে (ব্যালেন্সে যোগ হবে না)</li>
                                <li>ব্যাকডেটেড entry করলে পরবর্তী সকল entry স্বয়ংক্রিয়ভাবে পুনরায় ক্যালকুলেট হবে</li>
                            </ul>
                        </div>
                        
                        <!-- Opening Date Info -->
                        <div id="openingDateInfo" class="bg-purple-50 border-l-4 border-purple-400 p-2 mb-3 text-sm text-purple-700 hidden">
                            <i class="fas fa-calendar-alt mr-2"></i>
                            <span id="openingDateText"></span>
                        </div>
                        
                        <!-- Date Warning -->
                        <p id="dateWarning" class="text-xs text-red-500 mt-1 mb-2 hidden"></p>
                        
                        <form id="transactionForm" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div class="col-span-1">
                                    <label for="select_type" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fa-solid fa-user"></i> Payment To
                                    </label>
                                   <select name="select_type" id="select_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                       <option value="client" selected>Client</option>
                                       <option value="vendor">Vendor</option>
                                   </select>
                                </div>
                                
                                <!-- Client -->
                                <div id="client-section" class="col-span-1">
                                    <label for="clientInput" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fa-solid fa-user"></i> Client Name
                                    </label>
                                   <?php include('form-selects/clients.php') ?>
                                </div>
                                
                                <!--Vendor-->
                                <div id="vendor-section" class="hidden col-span-1">
                                    <label for="vendorInput" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fa-solid fa-user"></i> Vendor Name
                                    </label>
                                   <?php include('form-selects/vendors.php') ?>
                                </div>
                                
                                <!-- From Account -->
                                <div class="col-span-1">
                                    <label for="accountInput" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-wallet mr-1"></i> From Account
                                    </label>
                                   <?php include('form-selects/accounts.php') ?>
                                </div>
                                
                                <!-- Transfer Method -->
                                <div class="col-span-1">
                                    <label for="transfer_method" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-money-check-alt mr-1"></i> Payment Method
                                    </label>
                                   <select name="transfer_method" id="transfer_method" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                       <option value="cash" selected>Cash</option>
                                       <option value="cheque">Cheque</option>
                                       <option value="npsb-rtgs">NPSB/RTGS</option>
                                       <option value="bftn-eft">BFTN/EFT</option>
                                   </select>
                                </div>
                                
                                <!-- Date -->
                                <div class="col-span-1">
                                    <label for="transactionDate" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-calendar-alt mr-1"></i> Date
                                    </label>
                                    <input type="date"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                        id="transactionDate" name="transactionDate" required>
                                </div>
                                
                                <!-- Amount -->
                                <div class="col-span-1">
                                    <label for="balance" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-dollar-sign mr-1"></i> Amount
                                    </label>
                                    <input type="number" step="0.01" min="0"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                        id="balance" name="balance" required placeholder="0.00">
                                </div>
                                
                                <!-- Cheque Details Section (Hidden by default) -->
                                <div id="cheque-details-section" class="hidden md:col-span-2 lg:col-span-3 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    <div class="col-span-1">
                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-4">
                                            <div class="col-span-1">
                                                <label for="cheque_no" class="block text-sm font-medium text-gray-700 mb-2">
                                                    <i class="fas fa-file-invoice mr-1"></i> Cheque No
                                                </label>
                                                <input type="text"
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                                    id="cheque_no" name="cheque_no" placeholder="Enter cheque number">
                                            </div>
                                            
                                            <div class="col-span-1">
                                                <label for="cheque_date" class="block text-sm font-medium text-gray-700 mb-2">
                                                    <i class="fas fa-calendar-day mr-1"></i> Cheque Date
                                                </label>
                                                <input type="date"
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                                    id="cheque_date" name="cheque_date">
                                            </div>
                                        </div>
                                    </div>
                                
                                    <div class="col-span-1">
                                        <label for="cheque_account_name" class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-user-circle mr-1"></i> Account Name
                                        </label>
                                        <input type="text"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                            id="cheque_account_name" name="cheque_account_name" placeholder="Enter account name">
                                    </div>
                                    
                                    <div class="col-span-1">
                                        <label for="bank_name" class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-university mr-1"></i> Bank Name
                                        </label>
                                        <input type="text"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                            id="bank_name" name="bank_name" placeholder="Enter bank name">
                                    </div>
                                </div>
                                
                                <!-- BFTN/EFT Details Section (Hidden by default) -->
                                <div id="bftn-details-section" class="hidden md:col-span-2 lg:col-span-3 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    <div class="col-span-1">
                                        <label for="account_name" class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-user-circle mr-1"></i> Account Name
                                        </label>
                                        <input type="text"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                            id="account_name" name="account_name" placeholder="Enter account name">
                                    </div>
                                    
                                    <div class="col-span-1">
                                        <label for="eft_bank_name" class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-university mr-1"></i> Bank Name
                                        </label>
                                        <input type="text"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                            id="eft_bank_name" name="eft_bank_name" placeholder="Enter bank name">
                                    </div>
                                    
                                    <div class="col-span-1">
                                        <label for="bftn_date" class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-calendar-check mr-1"></i> Transaction Date
                                        </label>
                                        <input type="date"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                            id="bftn_date" name="bftn_date">
                                    </div>
                                </div>
                                
                                <!-- Particular -->
                                <div class="md:col-span-2 lg:col-span-3">
                                    <label for="particular" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-file-alt mr-1"></i> Particular
                                    </label>
                                    <textarea
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" rows="3"
                                        id="particular" name="particular" placeholder="Enter payment description"></textarea>
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
                                    <div id="spinner" class="hidden spinner mr-2"></div>
                                    <i class="fas fa-paper-plane mr-2"></i>
                                    <span id="saveButtonText">Make Payment</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include '../elements/floating-menus.php'; ?>

    <script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function () {
        
            /* ================= CONFIG ================= */
            const IP_PATH = '<?php echo htmlspecialchars($base_ip_path); ?>';
            const API_PAYMENT_TRANSACTION = `${IP_PATH}/api/accounts/payment-transaction.php`;
            const FETCH_ACCOUNT = `${IP_PATH}/api/accounts/fetch_acc_details.php`;
            const FETCH_STATEMENT = `${IP_PATH}/api/accounts/fetch_account_statement_api.php`;

            /* ================= ELEMENTS ================= */
            const transactionForm = document.getElementById('transactionForm');
            const saveTransactionBtn = document.getElementById('saveTransactionBtn');
            const spinner = document.getElementById('spinner');
            const saveButtonText = document.getElementById('saveButtonText');

            const selectType = document.getElementById('select_type');
            const clientSection = document.getElementById('client-section');
            const vendorSection = document.getElementById('vendor-section');
            const transferMethod = document.getElementById('transfer_method');
            const chequeDetailsSection = document.getElementById('cheque-details-section');
            const bftnDetailsSection = document.getElementById('bftn-details-section');
            
            const clientInput = document.getElementById('clientInput');
            const vendorInput = document.getElementById('vendorInput');
            const accountInput = document.getElementById('accountInput');
            
            const transactionDate = document.getElementById('transactionDate');
            const amountInput = document.getElementById('balance');
            const particularTextarea = document.getElementById('particular');
            
            // Cheque fields
            const chequeNoInput = document.getElementById('cheque_no');
            const chequeDateInput = document.getElementById('cheque_date');
            const chequeAccountNameInput = document.getElementById('cheque_account_name');
            const bankNameInput = document.getElementById('bank_name');
            
            // BFTN/EFT fields
            const accountNameInput = document.getElementById('account_name');
            const eftBankNameInput = document.getElementById('eft_bank_name');
            const bftnDateInput = document.getElementById('bftn_date');
            
            // Warning elements
            const dateWarning = document.getElementById('dateWarning');
            const openingDateInfo = document.getElementById('openingDateInfo');
            const openingDateText = document.getElementById('openingDateText');
            
            /* ================= STATE ================= */
            let openingDate = null;
            let selectedAccountId = null;
        
            /* ================= UTILS ================= */
            const ACCOUNT_MANDATORY_FROM = new Date('2026-02-01');
            
            function extractIds(value) {
                if (!value) return null;
                const parts = value.split('|').map(v => v.trim());
                return {
                    sys_id: parts[0] || null,
                    name: parts[1] || null,
                };
            }
            
            function buildDateTime(dateOnly) {
                const now = new Date();
                const bangladeshOffset = 6 * 60;
                const localOffset = now.getTimezoneOffset();
                const bangladeshTime = new Date(now.getTime() + 
                    (localOffset + bangladeshOffset) * 60 * 1000);
                
                const time = bangladeshTime.toTimeString().split(' ')[0];
                
                return `${dateOnly} ${time}`;
            }
            
            // ফাংশন: সর্বোচ্চ ৫ দিন আগের তারিখ পর্যন্ত সেট করা
            function setMaxBackdatedDate() {
                const today = new Date();
                const maxBackdated = new Date(today);
                maxBackdated.setDate(today.getDate() - 8);
                // maxBackdated.setDate(today.getDate() - 5);
                
                const minDate = maxBackdated.toISOString().split('T')[0];
                const maxDate = today.toISOString().split('T')[0];
                
                transactionDate.setAttribute('min', minDate);
                transactionDate.setAttribute('max', maxDate);
            }
            
            // ফাংশন: Opening Balance এর তারিখ বের করা
            async function fetchOpeningDate(accountId) {
                alert(accountId)
                if (!accountId) {
                    openingDate = null;
                    openingDateInfo.classList.add('hidden');
                    setMaxBackdatedDate(); // No account -> 5 days limit
                    return;
                }
                
                try {
                    const response = await fetch(`${FETCH_STATEMENT}?ledger_db_id=${accountId}&opening_only=1`);
                    const result = await response.json();
                    
                    if (result.success && result.data && result.data.length > 0) {
                        openingDate = result.data[0].date;
                        
                        const dateObj = new Date(openingDate);
                        const formattedDate = dateObj.toLocaleDateString('bn-BD', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        });
                        
                        openingDateText.innerHTML = `এই অ্যাকাউন্টের Opening Balance: <strong>${formattedDate}</strong>। এর আগের তারিখে entry করলে তা শুধু সংরক্ষিত হবে, ব্যালেন্স ক্যালকুলেশনে যোগ হবে না।`;
                        openingDateInfo.classList.remove('hidden');
                        
                        // Opening Balance এর আগের তারিখ entry করার অনুমতি দিতে
                        transactionDate.removeAttribute('min');
                        
                        const today = new Date();
                        const maxDate = today.toISOString().split('T')[0];
                        transactionDate.setAttribute('max', maxDate);
                    } else {
                        openingDate = null;
                        openingDateInfo.classList.add('hidden');
                        setMaxBackdatedDate(); // No opening balance -> 5 days limit
                    }
                } catch (error) {
                    console.error('Error fetching opening date:', error);
                    openingDate = null;
                    openingDateInfo.classList.add('hidden');
                    setMaxBackdatedDate(); // Error -> 5 days limit
                }
                
                console.log(openingDate)
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

            /* ================= INIT ================= */
            transactionDate.value = BD_TIME.getDate();
            transactionDate.max = BD_TIME.getDate();
            
            // Set max dates for cheque and bftn date
            chequeDateInput.min = BD_TIME.getDate();
            bftnDateInput.min = BD_TIME.getDate();
            
            /* ================= ACCOUNT SELECTION HANDLER ================= */
            // This will catch both manual typing and dropdown selection
            function setupAccountHandler() {
                const accountInput = document.getElementById('accountInput');
                
                if (!accountInput) return;
                
                // Handle input changes (including from dropdown)
                accountInput.addEventListener('input', function() {
                    // Clear any pending timer
                    if (this.accountTimer) {
                        clearTimeout(this.accountTimer);
                    }
                    
                    // Set timer to process after typing stops
                    this.accountTimer = setTimeout(() => {
                        processAccountSelection(this.value);
                    }, 500);
                });
                
                // Handle direct changes (when dropdown selects)
                accountInput.addEventListener('change', function() {
                    processAccountSelection(this.value);
                });
                
                // Also handle blur to catch final value
                accountInput.addEventListener('blur', function() {
                    processAccountSelection(this.value);
                });
            }
            
            function processAccountSelection(value) {
                console.log('Processing account:', value);
                
                const account = extractIds(value);
                
                if (account && account.sys_id) {
                    selectedAccountId = account.sys_id;
                    
                    // Set hidden fields
                    if (document.getElementById('accountId')) {
                        document.getElementById('accountId').value = account.sys_id;
                    }
                    if (document.getElementById('accountName')) {
                        document.getElementById('accountName').value = account.name;
                    }
                    
                    // Fetch opening date
                    fetchOpeningDate(account.sys_id);
                    
                    // Fetch account balance
                    fetchAccountInfo(account.sys_id).then(accInfo => {
                        if (accInfo && document.getElementById('currentAccountBalance')) {
                            document.getElementById('currentAccountBalance').value = accInfo.balance;
                            console.log('Current balance:', accInfo.balance);
                        }
                    });
                } else {
                    openingDate = null;
                    if (document.getElementById('openingDateInfo')) {
                        document.getElementById('openingDateInfo').classList.add('hidden');
                    }
                    setMaxBackdatedDate();
                }
            }
            
            // Call this after DOM is loaded
            setupAccountHandler();
            
            // Initial opening date fetch when account changes
            accountInput.addEventListener('change', function() {
                console.log('action');
                const account = extractIds(accountInput.value);
                if (account && account.sys_id) {
                    selectedAccountId = account.sys_id;
                    // Fetch opening date
                    fetchOpeningDate(account.sys_id);
                    
                    // Fetch account balance (for future use)
                    fetchAccountInfo(account.sys_id).then(accInfo => {
                        if (accInfo) {
                            console.log('Current balance:', accInfo.balance);
                        }
                    });
                } else {
                    openingDate = null;
                    openingDateInfo.classList.add('hidden');
                    setMaxBackdatedDate();
                }
            });
            
            // Date input এ পরিবর্তন হলে ওয়ার্নিং দেখানো
            transactionDate.addEventListener('change', function() {
                const validation = validateTransactionDate(this.value);
                if (!validation.valid) {
                    dateWarning.textContent = validation.message;
                    dateWarning.classList.remove('hidden');
                    dateWarning.classList.add('text-red-500');
                    saveTransactionBtn.disabled = true;
                } else if (validation.warning) {
                    dateWarning.textContent = validation.warning;
                    dateWarning.classList.remove('hidden');
                    dateWarning.classList.remove('text-red-500');
                    dateWarning.classList.add('text-yellow-600');
                    saveTransactionBtn.disabled = false;
                } else {
                    dateWarning.classList.add('hidden');
                    saveTransactionBtn.disabled = false;
                }
            });
        
            function togglePartySection() {
                const type = selectType.value;
        
                if (type === 'vendor') {
                    vendorSection.classList.remove('hidden');
                    clientSection.classList.add('hidden');
                    if (clientInput) clientInput.value = '';
                } else {
                    clientSection.classList.remove('hidden');
                    vendorSection.classList.add('hidden');
                    if (vendorInput) vendorInput.value = '';
                }
            }
            
            function togglePaymentDetails() {
                const method = transferMethod.value;
                
                // Hide all sections first
                if(chequeDetailsSection) chequeDetailsSection.classList.add('hidden');
                if(bftnDetailsSection) bftnDetailsSection.classList.add('hidden');
                
                // Clear all fields if needed
                if(chequeNoInput) chequeNoInput.value = '';
                if(chequeDateInput) chequeDateInput.value = '';
                if(chequeAccountNameInput) chequeAccountNameInput.value = '';
                if(bankNameInput) bankNameInput.value = '';
                if(accountNameInput) accountNameInput.value = '';
                if(eftBankNameInput) eftBankNameInput.value = '';
                if(bftnDateInput) bftnDateInput.value = '';
                
                // Show relevant section
                if (method === 'cheque' && chequeDetailsSection) {
                    chequeDetailsSection.classList.remove('hidden');
                } else if (method === 'bftn-eft' && bftnDetailsSection) {
                    bftnDetailsSection.classList.remove('hidden');
                }
            }
        
            togglePartySection();
            togglePaymentDetails();
            
            selectType.addEventListener('change', togglePartySection);
            transferMethod.addEventListener('change', togglePaymentDetails);
        
            window.resetTransactionForm = function () {
                transactionForm.reset();
                transactionDate.value = BD_TIME.getDate();
                transactionDate.max = BD_TIME.getDate();
                if(chequeDateInput) chequeDateInput.min = BD_TIME.getDate();
                if(bftnDateInput) bftnDateInput.min = BD_TIME.getDate();
                togglePartySection();
                togglePaymentDetails();
                dateWarning.classList.add('hidden');
                saveTransactionBtn.disabled = false;
            };
        
            /* ================= VALIDATION ================= */
            function validateForm() {
                const type = selectType.value;
                const method = transferMethod.value;
                const client = extractIds(clientInput?.value);
                const vendor = extractIds(vendorInput?.value);
                const account = extractIds(accountInput.value);
        
                // Validate From Account
                if (!account || !account.sys_id) {
                    alert('Please select From Account');
                    accountInput?.focus();
                    return false;
                }
        
                // Validate To based on type
                if (type === 'client') {
                    if (!client || !client.sys_id) {
                        alert('Please select Client');
                        clientInput?.focus();
                        return false;
                    }
                } else if (type === 'vendor') {
                    if (!vendor || !vendor.sys_id) {
                        alert('Please select Vendor');
                        vendorInput?.focus();
                        return false;
                    }
                }
        
                // Validate Date
                if (!transactionDate.value) {
                    alert('Please select a date');
                    transactionDate.focus();
                    return false;
                }
                
                // Date validation
                const dateValidation = validateTransactionDate(transactionDate.value);
                if (!dateValidation.valid) {
                    alert(dateValidation.message);
                    return false;
                }
                
                if (dateValidation.warning) {
                    if (!confirm(dateValidation.warning + '\n\nতবুও কি এগিয়ে যেতে চান?')) {
                        return false;
                    }
                }
        
                // Validate Amount
                const amount = parseFloat(amountInput.value);
                if (!amountInput.value || isNaN(amount) || amount <= 0) {
                    alert('Please enter a valid amount (greater than 0)');
                    amountInput.focus();
                    return false;
                }
                
                // Validate Cheque details if method is cheque
                if (method === 'cheque') {
                    if (!chequeNoInput.value.trim()) {
                        alert('Please enter Cheque Number');
                        chequeNoInput.focus();
                        return false;
                    }
                    
                    if (!chequeDateInput.value) {
                        alert('Please select Cheque Date');
                        chequeDateInput.focus();
                        return false;
                    }
                    
                    if (!chequeAccountNameInput.value.trim()) {
                        alert('Please enter Account Name for cheque');
                        chequeAccountNameInput.focus();
                        return false;
                    }
                    
                    if (!bankNameInput.value.trim()) {
                        alert('Please enter Bank Name');
                        bankNameInput.focus();
                        return false;
                    }
                }
                
                // Validate BFTN/EFT details if method is bftn-eft
                if (method === 'bftn-eft') {
                    if (!accountNameInput.value.trim()) {
                        alert('Please enter Account Name');
                        accountNameInput.focus();
                        return false;
                    }
                    
                    if (!eftBankNameInput.value.trim()) {
                        alert('Please enter Bank Name');
                        eftBankNameInput.focus();
                        return false;
                    }
                    
                    if (!bftnDateInput.value) {
                        alert('Please select Transaction Date');
                        bftnDateInput.focus();
                        return false;
                    }
                }
        
                // Validate Particular
                if (!particularTextarea.value.trim()) {
                    alert('Please enter payment particulars');
                    particularTextarea.focus();
                    return false;
                }
        
                return true;
            }
            
            // Updated Receipt Printing Logic for 58mm POS Printers
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
                                <div class="row"><span>FROM:</span> <span>${item.name.toUpperCase()}</span></div>
                                
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
                                        ${item.withdraw > 0 ? item.withdraw : item.deposit} TK
                                    </span>
                                </div>
                                
                                <div class="separator"></div>
                                
                                <div class="footer">
                                    <strong>WE PAID OUR PAYMENT! <br> THANK YOU!!</strong><br>
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
        
            /* ================= SUBMIT PAYMENT ================= */
            async function submitPayment() {
                if (!validateForm()) return;
        
                const type = selectType.value;
                const method = transferMethod.value;
                const client = extractIds(clientInput?.value);
                const vendor = extractIds(vendorInput?.value);
                const account = extractIds(accountInput.value);
                
                // Check if this is historical (opening balance er age)
                const dateValidation = validateTransactionDate(transactionDate.value);
        
                const data = {
                    accountId: account?.sys_id || null,
                    accountName: account?.name || null,
                    clientId: type === 'client' ? client?.sys_id : null,
                    clientName: type === 'client' ? client?.name : null,
                    vendorId: type === 'vendor' ? vendor?.sys_id : null,
                    vendorName: type === 'vendor' ? vendor?.name : null,
                    amount: amountInput.value,
                    particular: particularTextarea.value.trim(),
                    transactionDate: buildDateTime(transactionDate.value),
                    transferMethod: method,
                    chequeNo: method === 'cheque' ? chequeNoInput.value.trim() : null,
                    chequeDate: method === 'cheque' ? chequeDateInput.value : null,
                    chequeAccountName: method === 'cheque' ? chequeAccountNameInput.value : null,
                    bankName: method === 'cheque' ? bankNameInput.value.trim() : null,
                    bftnAccountName: method === 'bftn-eft' ? accountNameInput.value.trim() : null,
                    eftBankName: method === 'bftn-eft' ? eftBankNameInput.value.trim() : null,
                    bftnDate: method === 'bftn-eft' ? bftnDateInput.value : null,
                    isHistorical: dateValidation.isHistorical ? 1 : 0  // Send historical flag
                };
                
                console.log('Payment data:', data);
        
                // Disable button and show spinner
                saveTransactionBtn.disabled = true;
                spinner.classList.remove('hidden');
                saveButtonText.textContent = 'Processing...';
        
                try {
                    const response = await fetch(API_PAYMENT_TRANSACTION, {
                        method: 'POST',
                        headers: { 
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(data)
                    });
        
                    const result = await response.json();
        
                    if (response.ok && result.success) {
                        if (result.instrument) {
                            alert('Instrument recorded successfully. Payment pending clearance.');
                        } else {
                            let message = 'Payment completed successfully!';
                            if (result.is_historical) {
                                message = 'ঐতিহাসিক entry সংরক্ষিত হয়েছে (Opening Balance এর আগের তারিখ)। এই entry ব্যালেন্স ক্যালকুলেশনে যোগ হবে না।';
                            } else if (result.recalculated) {
                                message = `ব্যাকডেটেড entry সফল হয়েছে। ${result.recalculated_date} থেকে পরবর্তী সকল entry পুনরায় ক্যালকুলেট করা হয়েছে।`;
                            }
                            alert(message);
                        }
                        resetTransactionForm();
                        
                        if (result.item) {
                            const itemData = encodeURIComponent(JSON.stringify(result.item));
                            printReceipt(itemData);
                        }
                    } else {
                        alert(result.error || result.message || 'Payment failed.');
                    }
        
                } catch (error) {
                    console.error('Payment error:', error);
                    alert('Network error. Please check your connection.');
                } finally {
                    // Re-enable button
                    saveTransactionBtn.disabled = false;
                    spinner.classList.add('hidden');
                    saveButtonText.textContent = 'Make Payment';
                }
            }
            
            saveTransactionBtn.addEventListener('click', submitPayment);
        
        });
    </script>

</body>

</html>