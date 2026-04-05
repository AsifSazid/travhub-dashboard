<?php

include_once('./authenticate.php');
$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898";
}

$storeEpsApi = $ip_port . "api/eps/store.php";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Employee Payroll System</title>
    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png" sizes="16x16">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/sortablejs@1.14.0/Sortable.min.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Custom animations */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-slide-in {
            animation: slideIn 0.3s ease-out;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #a1a1a1;
        }
        
        /* Preview Modal */
        .preview-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .preview-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            padding: 20px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        /* Improved form styling */
        .form-section {
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        }
        
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        .required-star {
            color: #ef4444;
            margin-left: 0.25rem;
        }
        
        .form-input {
            width: 100%;
            padding: 0.625rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            transition: all 0.2s;
        }
        
        .form-input:focus {
            outline: none;
            ring-width: 2px;
            ring-color: #3b82f6;
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
        }
        
        .btn-primary {
            padding: 0.625rem 1.25rem;
            background-color: #3b82f6;
            color: white;
            font-weight: 500;
            border-radius: 0.5rem;
            transition: all 0.2s;
        }
        
        .btn-primary:hover {
            background-color: #2563eb;
        }
        
        .btn-primary:focus {
            outline: none;
            ring-width: 2px;
            ring-color: #3b82f6;
            ring-offset: 2px;
        }
        
        .btn-secondary {
            padding: 0.625rem 1.25rem;
            border: 1px solid #d1d5db;
            color: #374151;
            font-weight: 500;
            border-radius: 0.5rem;
            transition: all 0.2s;
        }
        
        .btn-secondary:hover {
            background-color: #f9fafb;
        }
        
        .section-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .input-group {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .form-card {
            background: white;
            border-radius: 0.5rem;
            border: 1px solid #e5e7eb;
            padding: 1.25rem;
        }
        
        /* Main content adjustment */
        @media (max-width: 768px) {
            main#mainContent {
                padding-left: 0 !important;
                padding-top: 4rem !important;
            }
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
        <div class="p-4 md:p-6">
            <div class="bg-white rounded-lg shadow p-4 md:p-6">
                <!-- Header Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 mb-6">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div>
                            <div class="flex items-center mb-2">
                                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                    <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h1 class="text-xl md:text-2xl font-bold text-gray-800">Add New EPS - Employee Payroll Structure</h1>
                                    <p class="text-gray-600 text-sm mt-1">Fill in the details below to add a new EPS to the system</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Success/Error Messages -->
                <div id="messageContainer" class="hidden mb-6 animate-slide-in">
                    <div id="successMessage" class="hidden bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span id="successText"></span>
                        </div>
                    </div>
                    <div id="errorMessage" class="hidden bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <span id="errorText"></span>
                        </div>
                    </div>
                </div>

                <!-- Employee Form -->
                <form id="epsForm" class="space-y-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="space-y-6">
                            <div class="form-card">
                                <h3 class="section-title"><i class="fas fa-id-badge mr-2 text-blue-600"></i>Basic Assignment</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="form-label mb-3">Select Employee <span class="required-star">*</span></label>
                                        <?php include('form-selects/employees.php') ?>
                                        <input type="hidden" id="selectedEmployeeId" name="selected_employee_id">
                                        <input type="hidden" id="selectedEmployeeName" name="selected_employee_name">
                                    </div>
                                    <div>
                                        <label class="form-label">Effective Date <span class="required-star">*</span></label>
                                        <input type="date" name="effective_date" class="form-input" required>
                                    </div>
                                </div>
                            </div>
                    
                            <div class="form-card border-l-4 border-l-green-500">
                                <h3 class="section-title text-green-700"><i class="fas fa-plus-circle mr-2"></i>Monthly Earnings</h3>
                                <div class="space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="form-label">Basic Salary <span class="required-star">*</span></label>
                                            <div class="relative">
                                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">৳</span>
                                                <input type="number" name="basic_salary" class="form-input pl-8" placeholder="0.00" required min="0" step="0.01" oninput="calculateSalary()">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="form-label">House Rent</label>
                                            <div class="relative">
                                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">৳</span>
                                                <input type="number" name="house_rent" class="form-input pl-8" placeholder="0.00" min="0" step="0.01" oninput="calculateSalary()">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="form-label">Medical Allowance</label>
                                            <div class="relative">
                                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">৳</span>
                                                <input type="number" name="medical_allowance" class="form-input pl-8" placeholder="0.00" min="0" step="0.01" oninput="calculateSalary()">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="form-label">Conveyance</label>
                                            <div class="relative">
                                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">৳</span>
                                                <input type="number" name="conveyance" class="form-input pl-8" placeholder="0.00" min="0" step="0.01" oninput="calculateSalary()">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    
                        <div class="space-y-6">
                            <div class="form-card border-l-4 border-l-red-500">
                                <h3 class="section-title text-red-700"><i class="fas fa-minus-circle mr-2"></i>Monthly Deductions</h3>
                                <div class="space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="form-label">Provident Fund (PF)</label>
                                            <div class="relative">
                                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">৳</span>
                                                <input type="number" name="pf_deduction" class="form-input pl-8" placeholder="0.00" min="0" step="0.01" oninput="calculateSalary()">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="form-label">Professional Tax</label>
                                            <div class="relative">
                                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">৳</span>
                                                <input type="number" name="tax_deduction" class="form-input pl-8" placeholder="0.00" min="0" step="0.01" oninput="calculateSalary()">
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="form-label">Other Deductions (Insurance/Loan)</label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">৳</span>
                                            <input type="number" name="other_deduction" class="form-input pl-8" placeholder="0.00" min="0" step="0.01" oninput="calculateSalary()">
                                        </div>
                                    </div>
                                </div>
                            </div>
                    
                            <div class="bg-blue-900 rounded-xl p-6 text-white shadow-lg">
                                <h3 class="text-lg font-semibold mb-4 flex items-center">
                                    <i class="fas fa-calculator mr-2"></i> Salary Summary Preview
                                </h3>
                                <div class="space-y-3">
                                    <div class="flex justify-between text-blue-100">
                                        <span>Gross Earnings:</span>
                                        <span id="gross_display">৳ 0.00</span>
                                    </div>
                                    <div class="flex justify-between text-red-300 border-b border-blue-800 pb-2">
                                        <span>Total Deductions:</span>
                                        <span id="deduction_display">৳ 0.00</span>
                                    </div>
                                    <div class="flex justify-between text-xl font-bold pt-2">
                                        <span>Net Take-Home:</span>
                                        <span id="net_display" class="text-green-400">৳ 0.00</span>
                                    </div>
                                </div>
                                <p class="mt-4 text-xs text-blue-200 italic">* This is a real-time calculation based on your inputs above.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="space-y-4 sm:space-y-0 pt-6 border-t flex flex-col sm:flex-row justify-between items-center gap-4">
                        <div class="text-sm text-gray-500">
                            <i class="fas fa-info-circle mr-1"></i>
                            Fields marked with <span class="required-star">*</span> are required
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <button type="button" onclick="resetForm()"
                                class="px-4 sm:px-6 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200">
                                <i class="fas fa-redo mr-2"></i>
                                Reset
                            </button>
                            <button type="submit"
                                class="px-4 sm:px-6 py-2 border border-transparent rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                                <i class="fas fa-plus mr-2"></i>
                                Save Structure
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>

    <script>
        const storeEpsApi = "<?php echo $storeEpsApi; ?>";
        
        /* ================= EMPLOYEE SYNC (IMPORTANT) ================= */
        function syncEmployeeFromInput() {
            const input = document.getElementById('employeeInput');
            const hiddenId = document.getElementById('selectedEmployeeId');
            const hiddenName = document.getElementById('selectedEmployeeName');
        
            if (!input || !hiddenId || !hiddenName) return;
        
            const val = input.value.trim();
        
            if (!val.includes('|')) {
                hiddenId.value = '';
                hiddenName.value = '';
                return;
            }
        
            const parts = val.split('|');
        
            hiddenId.value = parts[0].trim();
            hiddenName.value = parts.slice(1).join('|').trim();
        }
        
        /* 🔥 catch ALL cases (typing + dropdown click) */
        document.addEventListener('input', e => {
            if (e.target.id === 'employeeInput') {
                syncEmployeeFromInput();
            }
        });
        
        document.addEventListener('blur', e => {
            if (e.target.id === 'employeeInput') {
                setTimeout(syncEmployeeFromInput, 50);
            }
        }, true);
        
        /* ================= SALARY CALC ================= */
        function calculateSalary() {
            const get = n => parseFloat(document.querySelector(`[name="${n}"]`)?.value) || 0;
        
            const gross =
                get('basic_salary') +
                get('house_rent') +
                get('medical_allowance') +
                get('conveyance');
        
            const deduction =
                get('pf_deduction') +
                get('tax_deduction') +
                get('other_deduction');
        
            document.getElementById('gross_display').textContent = `৳ ${gross.toFixed(2)}`;
            document.getElementById('deduction_display').textContent = `৳ ${deduction.toFixed(2)}`;
            document.getElementById('net_display').textContent = `৳ ${(gross - deduction).toFixed(2)}`;
        }
        
        /* ================= RESET ================= */
        function resetForm() {
            document.getElementById('epsForm').reset();
            document.getElementById('selectedEmployeeId').value = '';
            document.getElementById('selectedEmployeeName').value = '';
            calculateSalary();
            hideMessages();
        }
        
        /* ================= SUBMIT ================= */
        document.getElementById('epsForm').addEventListener('submit', async e => {
            e.preventDefault();
            hideMessages();
        
            syncEmployeeFromInput(); // 🔥 FINAL GUARANTEE
        
            const employeeId = document.getElementById('selectedEmployeeId').value;
            const employeeName = document.getElementById('selectedEmployeeName').value;
        
            if (!employeeId) {
                showMessage('error', 'Please select an employee from the dropdown list');
                document.getElementById('employeeInput').focus();
                return;
            }
        
            const data = {
                employee_id: employeeId,
                employee_name: employeeName,
                effective_date: document.querySelector('[name="effective_date"]').value,
                basic_salary: +document.querySelector('[name="basic_salary"]').value,
                house_rent: +document.querySelector('[name="house_rent"]').value || 0,
                medical_allowance: +document.querySelector('[name="medical_allowance"]').value || 0,
                conveyance: +document.querySelector('[name="conveyance"]').value || 0,
                pf_deduction: +document.querySelector('[name="pf_deduction"]').value || 0,
                tax_deduction: +document.querySelector('[name="tax_deduction"]').value || 0,
                other_deduction: +document.querySelector('[name="other_deduction"]').value || 0,
                status: 'active'
            };
        
            try {
                const res = await fetch(storeEpsApi, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
        
                const json = await res.json();
        
                if (json.success) {
                    showMessage('success', json.message || 'EPS saved successfully');
                    setTimeout(resetForm, 1500);
                } else {
                    showMessage('error', json.message || 'Failed to save EPS');
                }
            } catch {
                showMessage('error', 'Network error');
            }
        });
        
        /* ================= MESSAGE ================= */
        function showMessage(type, msg) {
            document.getElementById('messageContainer').classList.remove('hidden');
            document.getElementById(type === 'success' ? 'successMessage' : 'errorMessage').classList.remove('hidden');
            document.getElementById(type === 'success' ? 'successText' : 'errorText').textContent = msg;
        }
        
        function hideMessages() {
            document.getElementById('messageContainer').classList.add('hidden');
            document.getElementById('successMessage').classList.add('hidden');
            document.getElementById('errorMessage').classList.add('hidden');
        }
    </script>

</body>
</html>