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
        document.addEventListener('DOMContentLoaded', function() {
        const IP_PATH = '<?php echo htmlspecialchars($base_ip_path); ?>';
        const API_POST_URL = `${IP_PATH}/api/accounts/fetch_ledger_statement_api.php`;
        const FINANCIAL_ENTRIES_STORE_API = `${IP_PATH}/api/financial-entries/store.php'; ?>`;
    
        // UI Elements
        const transactionForm = document.getElementById('transactionForm');
        const saveTransactionBtn = document.getElementById('saveTransactionBtn');
        const spinner = document.getElementById('spinner');
        const saveButtonText = document.getElementById('saveButtonText');
        const clientSelect = document.getElementById('client_id');
        const accountSelect = document.getElementById('account_name');
        const transactionDate = document.getElementById('transactionDate');
        const amountInput = document.getElementById('balance');
        const particularTextarea = document.getElementById('particular');
        
        // Variables to store first API response
        let firstApiResult = null;
        
        // Set today's date as default
        const today = new Date().toISOString().split('T')[0];
        transactionDate.value = today;
    
        // Reset form function
        window.resetTransactionForm = function() {
            transactionForm.reset();
            transactionDate.value = today;
            particularTextarea.value = '';
            // Clear the form draft from localStorage
            localStorage.removeItem('receivedFormDraft');
        };
    
        // Submit transaction function
        saveTransactionBtn.addEventListener('click', submitTransaction);
    
        async function submitTransaction() {
            // Validate form
            if (!validateForm()) {
                return;
            }
    
            // Get form data
            const formData = {
                accountId: document.getElementById('accountId').value,
                accountName: document.getElementById('accountName').value,
                currentAccountBalance: document.getElementById('currentAccountBalance').value,
                paymentType: document.getElementById('paymentType').value,
                client_id: clientSelect.value,
                account_name: accountSelect.value,
                transactionDate: transactionDate.value,
                balance: amountInput.value,
                particular: particularTextarea.value
            };
    
            // Check if account is selected from dropdown
            if (!formData.account_name) {
                alert('Please select an account from the dropdown');
                accountSelect.focus();
                return;
            }
    
            // Check if client is selected
            if (!formData.client_id) {
                alert('Please select a client');
                clientSelect.focus();
                return;
            }
    
            // Show loading state
            saveTransactionBtn.disabled = true;
            spinner.classList.remove('hidden');
            saveButtonText.textContent = 'Processing...';
    
            try {
                // First API call - Ledger statement
                const firstResponse = await fetch(API_POST_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });
    
                firstApiResult = await firstResponse.json();
    
                if (firstResponse.ok && firstApiResult.success) {
                    // First API success - now call second API
                    await callSecondAPI(formData, firstApiResult);
                    
                } else {
                    // First API error handling
                    const errorMsg = firstApiResult.error || 'Unknown error occurred on the server.';
                    showErrorMessage(`Failed to save transaction: ${errorMsg}`);
                }
    
            } catch (error) {
                console.error('Submission error:', error);
                showErrorMessage('Network error or server connection failed. Please check your connection and try again.');
            } finally {
                // Reset button state
                saveTransactionBtn.disabled = false;
                spinner.classList.add('hidden');
                saveButtonText.textContent = 'Received Amount';
            }
        }
    
        // Call second API function
        async function callSecondAPI(formData, firstResult) {
            try {
                // Prepare data for second API
                const financialEntryData = {
                    type: 'credit', // Since this is "Received Amount"
                    amount: parseFloat(formData.balance),
                    purpose: formData.particular,
                    client_id: formData.client_id,
                    ref: firstResult.sys_id || firstResult.transaction_id || firstResult.id || 'N/A',
                    date: formData.transactionDate || new Date().toISOString().split('T')[0],
                    // Additional data if needed
                    account_name: formData.account_name,
                    payment_type: formData.paymentType
                };
    
                const secondResponse = await fetch(FINANCIAL_ENTRIES_STORE_API, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(financialEntryData)
                });
    
                const secondResult = await secondResponse.json();
    
                if (secondResult.success) {
                    // Both APIs successful
                    showSuccessMessage(`Success! Amount received and saved for ${formData.account_name}. Financial entry recorded successfully.`);
                    
                    // Reset form after successful submission
                    resetTransactionForm();
                    
                    // If there's a new balance from first API, update the hidden field
                    if (firstResult.new_balance) {
                        document.getElementById('currentAccountBalance').value = firstResult.new_balance;
                    }
                    
                    // Optional: Reload financial data or add to recent activity
                    loadFinancialData();
                    addRecentActivity(financialEntryData);
                    
                } else {
                    // Second API failed but first succeeded
                    showWarningMessage(`Transaction saved but financial entry failed: ${secondResult.message || 'Unknown error'}`);
                    
                    // Still reset form since first API succeeded
                    resetTransactionForm();
                    
                    // If there's a new balance from first API, update the hidden field
                    if (firstResult.new_balance) {
                        document.getElementById('currentAccountBalance').value = firstResult.new_balance;
                    }
                }
    
            } catch (error) {
                console.error('Second API error:', error);
                showWarningMessage('Transaction saved but financial entry network error occurred.');
                
                // Still reset form since first API succeeded
                resetTransactionForm();
                
                // If there's a new balance from first API, update the hidden field
                if (firstResult.new_balance) {
                    document.getElementById('currentAccountBalance').value = firstResult.new_balance;
                }
            }
        }
    
        // Form validation function
        function validateForm() {
            // Check required fields
            if (!clientSelect.value) {
                alert('Please select a client');
                clientSelect.focus();
                return false;
            }
    
            if (!accountSelect.value) {
                alert('Please select an account');
                accountSelect.focus();
                return false;
            }
    
            if (!transactionDate.value) {
                alert('Please select a date');
                transactionDate.focus();
                return false;
            }
    
            if (!amountInput.value || parseFloat(amountInput.value) <= 0) {
                alert('Please enter a valid amount (greater than 0)');
                amountInput.focus();
                return false;
            }
    
            if (!particularTextarea.value.trim()) {
                alert('Please enter transaction details');
                particularTextarea.focus();
                return false;
            }
    
            return true;
        }
    
        // Success message function
        function showSuccessMessage(message) {
            createNotification(message, 'success');
        }
    
        // Error message function
        function showErrorMessage(message) {
            createNotification(message, 'error');
        }
    
        // Warning message function (for partial success)
        function showWarningMessage(message) {
            createNotification(message, 'warning');
        }
    
        // Create beautiful notification
        function createNotification(message, type = 'success') {
            // Remove existing notification if any
            const existingNotification = document.getElementById('form-notification');
            if (existingNotification) {
                existingNotification.remove();
            }
    
            // Set icon and colors based on type
            let icon, bgColor, textColor, borderColor, title;
            
            switch(type) {
                case 'success':
                    icon = 'fa-check-circle';
                    bgColor = 'bg-green-100';
                    textColor = 'text-green-800';
                    borderColor = 'border-green-200';
                    title = 'Success!';
                    break;
                case 'error':
                    icon = 'fa-exclamation-circle';
                    bgColor = 'bg-red-100';
                    textColor = 'text-red-800';
                    borderColor = 'border-red-200';
                    title = 'Error!';
                    break;
                case 'warning':
                    icon = 'fa-exclamation-triangle';
                    bgColor = 'bg-yellow-100';
                    textColor = 'text-yellow-800';
                    borderColor = 'border-yellow-200';
                    title = 'Warning!';
                    break;
                default:
                    icon = 'fa-info-circle';
                    bgColor = 'bg-blue-100';
                    textColor = 'text-blue-800';
                    borderColor = 'border-blue-200';
                    title = 'Info';
            }
    
            // Create notification element
            const notification = document.createElement('div');
            notification.id = 'form-notification';
            notification.className = `fixed top-4 right-4 z-50 px-6 py-4 rounded-lg shadow-lg transform transition-all duration-300 ${bgColor} ${textColor} ${borderColor} border`;
            
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas ${icon} mr-3 text-lg"></i>
                    <div class="flex-1">
                        <p class="font-medium">${title}</p>
                        <p class="text-sm mt-1">${message}</p>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" 
                        class="ml-4 ${textColor} hover:opacity-75">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
    
            // Add to body
            document.body.appendChild(notification);
    
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.classList.add('removing');
                    setTimeout(() => {
                        if (notification.parentElement) {
                            notification.remove();
                        }
                    }, 300);
                }
            }, 5000);
        }
    
        // Load financial data function (you need to implement this)
        function loadFinancialData() {
            console.log('Loading financial data...');
            // Implement your financial data loading logic here
            // Example: fetch latest transactions, update dashboard, etc.
        }
    
        // Add to recent activity function (you need to implement this)
        function addRecentActivity(data) {
            console.log('Adding to recent activity:', data);
            // Implement your recent activity logic here
            // Example: add to activity log, update sidebar, etc.
        }
    
        // Optional: Add account dropdown change event to set hidden fields
        if (accountSelect) {
            accountSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const accountId = selectedOption.getAttribute('data-id');
                const accountBalance = selectedOption.getAttribute('data-balance');
                
                if (accountId) {
                    document.getElementById('accountId').value = accountId;
                    document.getElementById('accountName').value = this.value;
                    
                    if (accountBalance) {
                        document.getElementById('currentAccountBalance').value = accountBalance;
                    }
                }
            });
        }
    
        // Optional: Add client dropdown change event
        if (clientSelect) {
            clientSelect.addEventListener('change', function() {
                // You can add additional logic here if needed
                // For example, fetch client details or pre-fill fields
            });
        }
    
        // Optional: Auto-save draft on form change
        let formDraftTimer;
        transactionForm.addEventListener('input', function() {
            clearTimeout(formDraftTimer);
            formDraftTimer = setTimeout(() => {
                saveFormDraft();
            }, 1000);
        });
    
        function saveFormDraft() {
            const formData = {
                client_id: clientSelect.value,
                account_name: accountSelect.value,
                transactionDate: transactionDate.value,
                balance: amountInput.value,
                particular: particularTextarea.value
            };
            
            // Save to localStorage
            localStorage.setItem('receivedFormDraft', JSON.stringify(formData));
        }
    
        // Load draft on page load
        function loadFormDraft() {
            const draft = localStorage.getItem('receivedFormDraft');
            if (draft) {
                try {
                    const data = JSON.parse(draft);
                    if (data.client_id) clientSelect.value = data.client_id;
                    if (data.account_name) accountSelect.value = data.account_name;
                    if (data.transactionDate) transactionDate.value = data.transactionDate;
                    if (data.balance) amountInput.value = data.balance;
                    if (data.particular) particularTextarea.value = data.particular;
                    
                    // Ask user if they want to restore draft
                    if (confirm('Found a previously saved draft. Do you want to restore it?')) {
                        // Already restored above
                    } else {
                        // Clear the draft
                        localStorage.removeItem('receivedFormDraft');
                    }
                } catch (e) {
                    console.error('Error loading draft:', e);
                }
            }
        }
    
        // Load draft on page load
        loadFormDraft();
    });
    </script>

</body>

</html>