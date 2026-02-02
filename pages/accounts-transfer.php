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
        // API URL from PHP variable
        const storeEpsApi = "<?php echo $storeEpsApi; ?>";
        
        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Set today's date as default for effective date
            const today = new Date().toISOString().split('T')[0];
            document.querySelector('input[name="effective_date"]').value = today;
            
            // Add form submit event listener
            document.getElementById('epsForm').addEventListener('submit', handleFormSubmit);
            
            // Initial calculation
            calculateSalary();
            
            // Add input listeners for salary calculation
            document.querySelectorAll('input[type="number"]').forEach(input => {
                input.addEventListener('input', calculateSalary);
            });
            
            console.log('EPS Form initialized');
        });
        
        // Function to extract ID and Name from input value (Transfer ফর্মের মতো)
        function extractIds(value) {
            if (!value) return null;
            const parts = value.split('|').map(v => v.trim());
            return {
                sys_id: parts[0] || null,
                name: parts[1] || null,
            };
        }
        
        // Calculate salary function
        function calculateSalary() {
            // Get earning values
            const basicSalary = parseFloat(document.querySelector('input[name="basic_salary"]').value) || 0;
            const houseRent = parseFloat(document.querySelector('input[name="house_rent"]').value) || 0;
            const medicalAllowance = parseFloat(document.querySelector('input[name="medical_allowance"]').value) || 0;
            const conveyance = parseFloat(document.querySelector('input[name="conveyance"]').value) || 0;
            
            // Get deduction values
            const pfDeduction = parseFloat(document.querySelector('input[name="pf_deduction"]').value) || 0;
            const taxDeduction = parseFloat(document.querySelector('input[name="tax_deduction"]').value) || 0;
            const otherDeduction = parseFloat(document.querySelector('input[name="other_deduction"]').value) || 0;
            
            // Calculate totals
            const grossEarnings = basicSalary + houseRent + medicalAllowance + conveyance;
            const totalDeductions = pfDeduction + taxDeduction + otherDeduction;
            const netSalary = grossEarnings - totalDeductions;
            
            // Update display
            document.getElementById('gross_display').textContent = `৳ ${grossEarnings.toFixed(2)}`;
            document.getElementById('deduction_display').textContent = `৳ ${totalDeductions.toFixed(2)}`;
            document.getElementById('net_display').textContent = `৳ ${netSalary.toFixed(2)}`;
        }
        
        // Reset form function
        function resetForm() {
            // Reset employee input
            const employeeInput = document.getElementById('employeeInput');
            if (employeeInput) {
                employeeInput.value = '';
            }
            
            // Reset other inputs
            document.querySelector('input[name="basic_salary"]').value = '';
            document.querySelector('input[name="house_rent"]').value = '';
            document.querySelector('input[name="medical_allowance"]').value = '';
            document.querySelector('input[name="conveyance"]').value = '';
            document.querySelector('input[name="pf_deduction"]').value = '';
            document.querySelector('input[name="tax_deduction"]').value = '';
            document.querySelector('input[name="other_deduction"]').value = '';
            
            // Reset effective date to today
            const today = new Date().toISOString().split('T')[0];
            document.querySelector('input[name="effective_date"]').value = today;
            
            // Reset salary display
            document.getElementById('gross_display').textContent = '৳ 0.00';
            document.getElementById('deduction_display').textContent = '৳ 0.00';
            document.getElementById('net_display').textContent = '৳ 0.00';
            
            // Hide messages
            hideMessages();
        }
        
        // Form validation function
        function validateForm() {
            let isValid = true;
            
            // Check employee input
            const employeeInput = document.getElementById('employeeInput');
            const employeeData = extractIds(employeeInput?.value);
            
            if (!employeeData || !employeeData.sys_id) {
                showMessage('error', 'Please select an employee from the dropdown');
                if (employeeInput) {
                    employeeInput.style.borderColor = '#ef4444';
                    employeeInput.focus();
                }
                isValid = false;
            } else {
                if (employeeInput) employeeInput.style.borderColor = '#d1d5db';
            }
            
            // Check basic salary
            const basicSalaryInput = document.querySelector('input[name="basic_salary"]');
            const basicSalary = parseFloat(basicSalaryInput.value);
            
            if (!basicSalaryInput.value || isNaN(basicSalary) || basicSalary <= 0) {
                showMessage('error', 'Basic salary is required and must be greater than 0');
                basicSalaryInput.style.borderColor = '#ef4444';
                isValid = false;
            } else {
                basicSalaryInput.style.borderColor = '#d1d5db';
            }
            
            // Check effective date
            const effectiveDateInput = document.querySelector('input[name="effective_date"]');
            if (!effectiveDateInput.value) {
                showMessage('error', 'Effective date is required');
                effectiveDateInput.style.borderColor = '#ef4444';
                isValid = false;
            } else {
                effectiveDateInput.style.borderColor = '#d1d5db';
            }
            
            return isValid;
        }
        
        // Form submission handler - Transfer ফর্মের মতো করে
        async function handleFormSubmit(event) {
            event.preventDefault();
            
            // Hide any existing messages
            hideMessages();
            
            // Validate form
            if (!validateForm()) {
                return;
            }
            
            // Get employee data using the same method as Transfer form
            const employeeInput = document.getElementById('employeeInput');
            const employeeData = extractIds(employeeInput?.value);
            
            if (!employeeData || !employeeData.sys_id) {
                showMessage('error', 'Please select a valid employee from the dropdown');
                return;
            }
            
            // Get other form values
            const basicSalaryInput = document.querySelector('input[name="basic_salary"]');
            const effectiveDateInput = document.querySelector('input[name="effective_date"]');
            const houseRentInput = document.querySelector('input[name="house_rent"]');
            const medicalAllowanceInput = document.querySelector('input[name="medical_allowance"]');
            const conveyanceInput = document.querySelector('input[name="conveyance"]');
            const pfDeductionInput = document.querySelector('input[name="pf_deduction"]');
            const taxDeductionInput = document.querySelector('input[name="tax_deduction"]');
            const otherDeductionInput = document.querySelector('input[name="other_deduction"]');
            
            // Prepare data object - Transfer ফর্মের structure অনুযায়ী
            const data = {
                employee_id: employeeData.sys_id,
                employee_name: employeeData.name || '',
                effective_date: effectiveDateInput.value,
                basic_salary: parseFloat(basicSalaryInput.value) || 0,
                house_rent: parseFloat(houseRentInput.value) || 0,
                medical_allowance: parseFloat(medicalAllowanceInput.value) || 0,
                conveyance: parseFloat(conveyanceInput.value) || 0,
                pf_deduction: parseFloat(pfDeductionInput.value) || 0,
                tax_deduction: parseFloat(taxDeductionInput.value) || 0,
                other_deduction: parseFloat(otherDeductionInput.value) || 0,
                status: 'active'
            };
            
            console.log('Submitting EPS data:', data);
            
            // Show loading state
            const submitBtn = event.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Saving EPS Structure...';
            submitBtn.disabled = true;
            
            try {
                // Call API using JavaScript
                const response = await fetch(storeEpsApi, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showMessage('success', result.message || 'EPS Structure saved successfully!');
                    
                    // Show salary summary in success message
                    if (result.salary_summary) {
                        const summary = result.salary_summary;
                        const summaryText = `<br>Gross: ৳${summary.gross_salary.toFixed(2)} | Net: ৳${summary.net_salary.toFixed(2)}`;
                        document.getElementById('successText').innerHTML += summaryText;
                    }
                    
                    // Reset form after successful submission
                    setTimeout(() => resetForm(), 3000);
                    
                } else {
                    showMessage('error', result.message || 'Failed to save EPS structure.');
                }
                
            } catch (error) {
                console.error('Error:', error);
                showMessage('error', 'Network error. Please check your connection.');
            } finally {
                // Restore button state
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        }
        
        // Show message function
        function showMessage(type, text) {
            const container = document.getElementById('messageContainer');
            const successDiv = document.getElementById('successMessage');
            const errorDiv = document.getElementById('errorMessage');
            const successText = document.getElementById('successText');
            const errorText = document.getElementById('errorText');
            
            // Hide all first
            container.classList.add('hidden');
            successDiv.classList.add('hidden');
            errorDiv.classList.add('hidden');
            
            // Show appropriate message
            if (type === 'success') {
                successText.textContent = text;
                successDiv.classList.remove('hidden');
                container.classList.remove('hidden');
                
                // Auto-hide after 5 seconds
                setTimeout(() => {
                    container.classList.add('hidden');
                }, 5000);
            } else if (type === 'error') {
                errorText.textContent = text;
                errorDiv.classList.remove('hidden');
                container.classList.remove('hidden');
            }
        }
        
        // Hide messages function
        function hideMessages() {
            document.getElementById('messageContainer').classList.add('hidden');
            document.getElementById('successMessage').classList.add('hidden');
            document.getElementById('errorMessage').classList.add('hidden');
        }
    </script>

</body>

</html>