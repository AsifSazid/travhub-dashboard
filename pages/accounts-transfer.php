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
    <title>Accounting - Transfer</title>
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
                                <i class="fas fa-exchange-alt mr-2"></i>
                                Transfer
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
                            <i class="fas fa-exchange-alt mr-2"></i> Make Transfer
                        </h6>
                        <form id="transactionForm" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div class="col-span-1">
                                    <label for="select_type" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-random mr-1"></i> Transfer Type
                                    </label>
                                   <select name="select_type" id="select_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                       <option value="a2a" selected>Account to Account</option>
                                       <option value="a2p">Account to Person</option>
                                   </select>
                                </div>
                                
                                <!-- From Account -->
                                <div id="from-account-section" class="col-span-1">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-wallet mr-1"></i> From Account
                                    </label>
                                   <?php include('form-selects/accounts.php') ?>
                                </div>
                                
                                <!-- To Employee -->
                                <div id="employee-section" class="hidden col-span-1">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-user-tie mr-1"></i> To Employee
                                    </label>
                                   <?php include('form-selects/employees.php') ?>
                                </div>
                                
                                <!-- To Account -->
                                <div id="to-account-section" class="col-span-1">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-wallet mr-1"></i> To Account
                                    </label>
                                   <?php include('form-selects/to-accounts.php') ?>
                                </div>
                                
                                <!-- Transfer Method -->
                                <div class="col-span-1">
                                    <label for="transfer_method" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-money-check-alt mr-1"></i> Transfer Method
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
                                
                                <!-- Particular -->
                                <div class="md:col-span-2 lg:col-span-3">
                                    <label for="particular" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-file-alt mr-1"></i> Particular
                                    </label>
                                    <textarea
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" rows="3"
                                        id="particular" name="particular" placeholder="Enter transfer description"></textarea>
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
                                    <span id="saveButtonText">Transfer Amount</span>
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
            const API_TRANSFER_STORE = `${IP_PATH}/api/accounts/transfer-accounts.php`;

            /* ================= ELEMENTS ================= */
            const transactionForm = document.getElementById('transactionForm');
            const saveTransactionBtn = document.getElementById('saveTransactionBtn');
            const spinner = document.getElementById('spinner');
            const saveButtonText = document.getElementById('saveButtonText');

            const selectType = document.getElementById('select_type');
            const employeeSection = document.getElementById('employee-section');
            const toAccountSection = document.getElementById('to-account-section');
            
            const fromAccountInput = document.getElementById('accountInput');
            const toAccountInput = document.getElementById('toAccountInput');
            const employeeInput = document.getElementById('employeeInput');
            
            const transferMethod = document.getElementById('transfer_method');
            const transactionDate = document.getElementById('transactionDate');
            const amountInput = document.getElementById('balance');
            const particularTextarea = document.getElementById('particular');
        
            /* ================= UTILS ================= */
            function extractIds(value) {
                if (!value) return null;
                const parts = value.split('|').map(v => v.trim());
                return {
                    sys_id: parts[0] || null,
                    name: parts[1] || null,
                };
            }
        
            function todayDate() {
                return new Date().toISOString().split('T')[0];
            }

            /* ================= INIT ================= */
            transactionDate.value = todayDate();
            transactionDate.max = todayDate(); // Can't select future date
        
            function toggleTransferSections() {
                const type = selectType.value;
        
                if (type === 'a2p') {
                    employeeSection.classList.remove('hidden');
                    toAccountSection.classList.add('hidden');
                    if (toAccountInput) toAccountInput.value = '';
                } else {
                    toAccountSection.classList.remove('hidden');
                    employeeSection.classList.add('hidden');
                    if (employeeInput) employeeInput.value = '';
                }
            }
        
            toggleTransferSections();
            selectType.addEventListener('change', toggleTransferSections);
        
            window.resetTransactionForm = function () {
                transactionForm.reset();
                transactionDate.value = todayDate();
                transactionDate.max = todayDate();
                toggleTransferSections();
            };
        
            saveTransactionBtn.addEventListener('click', submitTransfer);
        
            /* ================= VALIDATION ================= */
            function validateForm() {
                const type = selectType.value;
                const fromAccount = extractIds(fromAccountInput?.value);
                const toAccount = extractIds(toAccountInput?.value);
                const employee = extractIds(employeeInput?.value);
        
                // Validate From Account
                if (!fromAccount || !fromAccount.sys_id) {
                    alert('Please select From Account');
                    fromAccountInput?.focus();
                    return false;
                }
        
                // Validate To based on type
                if (type === 'a2a') {
                    if (!toAccount || !toAccount.sys_id) {
                        alert('Please select To Account');
                        toAccountInput?.focus();
                        return false;
                    }
                    
                    // Check if same account
                    if (fromAccount.sys_id === toAccount.sys_id) {
                        alert('From Account and To Account cannot be the same');
                        return false;
                    }
                } else if (type === 'a2p') {
                    if (!employee || !employee.sys_id) {
                        alert('Please select Employee');
                        employeeInput?.focus();
                        return false;
                    }
                }
        
                // Validate Date
                if (!transactionDate.value) {
                    alert('Please select a date');
                    transactionDate.focus();
                    return false;
                }
        
                // Validate Amount
                const amount = parseFloat(amountInput.value);
                if (!amountInput.value || isNaN(amount) || amount <= 0) {
                    alert('Please enter a valid amount (greater than 0)');
                    amountInput.focus();
                    return false;
                }
        
                // Validate Particular
                if (!particularTextarea.value.trim()) {
                    alert('Please enter transfer particulars');
                    particularTextarea.focus();
                    return false;
                }
        
                return true;
            }
        
            /* ================= SUBMIT TRANSFER ================= */
            async function submitTransfer() {
                if (!validateForm()) return;
        
                const type = selectType.value;
                const fromAccount = extractIds(accountInput.value);
                const toAccount = type === 'a2a' ? extractIds(toAccountInput.value) : null;
                const employee = type === 'a2p' ? extractIds(employeeInput.value) : null;
        
                const data = {
                    fromAccountId: fromAccount?.sys_id || null,
                    fromAccountName: fromAccount?.name || null,
                    toAccountId: toAccount?.sys_id || null,
                    toAccountName: toAccount?.name || null,
                    employeeId: employee?.sys_id || null,
                    employeeName: employee?.name || null,
                    transferType: type,
                    transferMethod: transferMethod.value,
                    amount: amountInput.value,
                    particular: particularTextarea.value.trim(),
                    transactionDate: transactionDate.value
                };
                
                console.log(data);
        
                // Disable button and show spinner
                saveTransactionBtn.disabled = true;
                spinner.classList.remove('hidden');
                saveButtonText.textContent = 'Processing...';
        
                try {
                    const response = await fetch(API_TRANSFER_STORE, {
                        method: 'POST',
                        headers: { 
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(data)
                    });
        
                    const result = await response.json();
        
                    if (response.ok && result.success) {
                        alert('Transfer completed successfully!');
                        resetTransactionForm();
                        
                        // Reload page or update UI as needed
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        alert(result.error || 'Transfer failed. Please try again.');
                    }
        
                } catch (error) {
                    console.error('Transfer error:', error);
                    alert('Network error. Please check your connection.');
                } finally {
                    // Re-enable button
                    saveTransactionBtn.disabled = false;
                    spinner.classList.add('hidden');
                    saveButtonText.textContent = 'Transfer Amount';
                }
            }
        
        });
    </script>

</body>

</html>