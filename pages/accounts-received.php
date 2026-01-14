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

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                                <input id="paymentType" name="paymentType" value="Deposit" hidden />
                                
                                <div>
                                    <label for="transactionDate" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fa-solid fa-user"></i> Select Type
                                    </label>
                                   <select name="select_type" id="select_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                                       <option value="client" selected>Client</option>
                                       <option value="vendor">Vendor</option>
                                   </select>
                                </div>
                                
                                <!-- Client -->
                                <div id="client-section">
                                    <label for="transactionDate" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fa-solid fa-user"></i> Client Name
                                    </label>
                                   <?php include('form-selects/clients.php') ?>
                                </div>
                                
                                <!--Vendor-->
                                <div id="vendor-section" class="hidden">
                                    <label for="transactionDate" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fa-solid fa-user"></i> Vendor Name
                                    </label>
                                   <?php include('form-selects/vendors.php') ?>
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
                                
                                <div class="md:col-span-2 lg:col-span-5">
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
                                    <span id="saveButtonText">Received Amount</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include '../elements/floating-menus.php'; ?>

    <script src="../assets/js/script.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function () {
        
            /* ================= CONFIG ================= */
            const IP_PATH = '<?php echo htmlspecialchars($base_ip_path); ?>';
            const API_POST_URL = `${IP_PATH}/api/ledgers/store_ledger_statement.php`;
            const FINANCIAL_ENTRIES_STORE_API = `${IP_PATH}/api/financial_entries/store.php`;
            const FETCH_ACCOUNT = `${IP_PATH}/api/accounts/fetch_acc_details.php`;
        
            /* ================= ELEMENTS ================= */
            const transactionForm = document.getElementById('transactionForm');
            const saveTransactionBtn = document.getElementById('saveTransactionBtn');
            const spinner = document.getElementById('spinner');
            const saveButtonText = document.getElementById('saveButtonText');
        
            const selectType = document.getElementById('select_type');
            const clientSection = document.getElementById('client-section');
            const vendorSection = document.getElementById('vendor-section');
        
            const clientInput = document.getElementById('clientInput');
            const vendorInput = document.getElementById('vendorInput');
            const accountInput = document.getElementById('accountInput');
        
            const transactionDate = document.getElementById('transactionDate');
            const amountInput = document.getElementById('balance');
            const particularTextarea = document.getElementById('particular');
        
            /* ================= UTILS ================= */
            function extractIds(value) {
                if (!value) return null;
                const parts = value.split('|').map(v => v.trim());
                return {
                    id: parts[0] || null,
                    name: parts[1] || null,
                    sys_id: parts[parts.length - 1] || null
                };
            }
        
            function todayDate() {
                return new Date().toISOString().split('T')[0];
            }
        
            /* ================= INIT ================= */
            transactionDate.value = todayDate();
        
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
        
            togglePartySection();
            selectType.addEventListener('change', togglePartySection);
        
            window.resetTransactionForm = function () {
                transactionForm.reset();
                transactionDate.value = todayDate();
                togglePartySection();
            };
        
            saveTransactionBtn.addEventListener('click', submitTransaction);
        
            /* ================= VALIDATION ================= */
            function validateForm() {
        
                const type = selectType.value;
                const client = extractIds(clientInput?.value);
                const vendor = extractIds(vendorInput?.value);
                const account = extractIds(accountInput.value);
        
                if (!account || !account.sys_id) {
                    alert('Please select an account');
                    return false;
                }
        
                if (type === 'client' && (!client || !client.sys_id)) {
                    alert('Please select a client');
                    return false;
                }
        
                if (type === 'vendor' && (!vendor || !vendor.sys_id)) {
                    alert('Please select a vendor');
                    return false;
                }
        
                if (!transactionDate.value) {
                    alert('Please select a date');
                    return false;
                }
        
                if (!amountInput.value || parseFloat(amountInput.value) <= 0) {
                    alert('Please enter a valid amount');
                    return false;
                }
        
                if (!particularTextarea.value.trim()) {
                    alert('Please enter particulars');
                    return false;
                }
        
                return true;
            }
        
            /* ================= FETCH ACCOUNT ================= */
            async function fetchAccountInfo(acc_id) {
                try {
                    const res = await fetch(`${FETCH_ACCOUNT}?acc_id=${acc_id}`);
                    const json = await res.json();
                    return json.accInfo || null;
                } catch (e) {
                    console.error(e);
                    return null;
                }
            }
        
            /* ================= SUBMIT ================= */
            async function submitTransaction() {
        
                if (!validateForm()) return;
        
                const type = selectType.value;
                const client = extractIds(clientInput?.value);
                const vendor = extractIds(vendorInput?.value);
                const account = extractIds(accountInput.value);
        
                const accountInfo = await fetchAccountInfo(account.sys_id);
                if (!accountInfo) {
                    alert('Account info fetch failed');
                    return;
                }
        
                const data = {
                    accountId: account.sys_id,
                    accountName: account.name,
                    particular: particularTextarea.value,
                    balance: parseFloat(amountInput.value),
                    paymentType: 'Deposit',
                    currentAccountBalance: parseFloat(accountInfo.balance || 0),
                    transactionDate: transactionDate.value,
                    client_id: type === 'client' ? client.sys_id : null,
                    client_name: type === 'client' ? client.name : null,
                    vendor_id: type === 'vendor' ? vendor.sys_id : null,
                    vendor_name: type === 'vendor' ? vendor.name : null
                };
        
                saveTransactionBtn.disabled = true;
                spinner.classList.remove('hidden');
                saveButtonText.textContent = 'Processing...';
        
                try {
                    const res = await fetch(API_POST_URL, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data)
                    });
        
                    const result = await res.json();
        
                    if (res.ok && result.success) {
                        await callSecondAPI(data, result);
                    } else {
                        alert(result.error || 'Ledger save failed');
                    }
        
                } catch (e) {
                    console.error(e);
                    alert('Network error');
                } finally {
                    saveTransactionBtn.disabled = false;
                    spinner.classList.add('hidden');
                    saveButtonText.textContent = 'Received Amount';
                }
            }
        
            /* ================= SECOND API ================= */
            async function callSecondAPI(data, firstResult) {
        
                const payload = {
                    type: 'credit',
                    amount: data.balance,
                    purpose: data.particular,
                    client_id: data.client_id,
                    vendor_id: data.vendor_id,
                    ref: firstResult.stmt_sys_id,
                    date: data.transactionDate
                };
        
                try {
                    const res = await fetch(FINANCIAL_ENTRIES_STORE_API, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
        
                    const result = await res.json();
        
                    if (result.success) {
                        alert('Transaction saved successfully');
                        resetTransactionForm();
                    } else {
                        alert('Ledger saved but financial entry failed');
                    }
        
                } catch (e) {
                    console.error(e);
                    alert('Ledger saved, financial entry error');
                }
            }
        
        });
    </script>

</body>

</html>