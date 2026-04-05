<?php
include_once('./authenticate.php');
$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898";
}

$base_ip_path = trim($ip_port, "/");

$type = $_GET['type'] ?? 'petty_cash';

if (!empty($type)) {
    $formattedType = str_replace('_', ' ', $type);
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php if($formattedType) echo strtoupper($formattedType) ; ?> - Create Form</title>
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

    <!-- Main Content -->
    <main id="mainContent" class="pt-16 pl-0 lg:pl-64 lg:my-16 transition-all duration-300 h-full">
        <div class="p-4 md:p-6 h-full">

            <!-- Petty Cash Container -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <!-- Blue Header -->
                <div class="bg-blue-600 text-white p-4 md:p-6">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center space-y-4 md:space-y-0">
                        <div>
                            <h2 class="text-xl font-semibold flex items-center">
                                <i class="fas fa-money-bill-wave mr-2"></i>
                                <?php if($formattedType) echo strtoupper($formattedType) ; ?> - Create Form
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
                    <!-- Petty Cash Input Section -->
                    <div class="bg-blue-50 p-4 md:p-6 rounded-lg mb-6 border border-blue-200">
                        <h6 class="text-blue-700 font-semibold mb-4 flex items-center text-lg">
                            <i class="fas fa-file-invoice-dollar mr-2"></i> Create New Entry
                        </h6>
                        <form id="pettyCashForm" class="space-y-6">
                            <input type="hidden" id="user_sys_id" value="<?php echo $_SESSION['user_id'] ?? ''; ?>">
                            <input type="hidden" id="user_name" value="<?php echo htmlspecialchars($userName); ?>">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                <!-- Type -->
                                <div class="col-span-1">
                                    <label for="type" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-tags mr-1"></i> Type *
                                    </label>
                                    <select name="type" id="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                                        <option value="conveyance_bill" <?php echo ($type == 'conveyance_bill') ? 'selected' : ''; ?>>Conveyance Bill</option>
                                        <option value="other_bill" <?php echo ($type == 'other_bill') ? 'selected' : ''; ?>>Other Bill</option>
                                        <option value="loan" <?php echo ($type == 'loan') ? 'selected' : ''; ?>>Loan</option>
                                        <option value="petty_cash" <?php echo ($type == 'petty_cash') ? 'selected' : ''; ?>>Petty Cash</option>
                                    </select>
                                </div>
                                
                                <!-- Date -->
                                <div class="col-span-1">
                                    <label for="date" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-calendar-alt mr-1"></i> Date *
                                    </label>
                                    <input type="date" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                        id="date" name="date" required>
                                </div>
                                
                                <!-- Amount -->
                                <div class="col-span-1">
                                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-dollar-sign mr-1"></i> Amount *
                                    </label>
                                    <input type="number" step="0.01" min="0"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                        id="amount" name="amount" required placeholder="0.00">
                                </div>
                                
                                <!-- To User (for loans) -->
                                <div id="to-user-section" class="col-span-1">
                                    <label for="to_user" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-user-check mr-1"></i> From Employee
                                    </label>
                                    <?php include('form-selects/employees.php') ?>
                                </div>
                                
                                <!-- Purpose -->
                                <div class="md:col-span-2 lg:col-span-2">
                                    <label for="purpose" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-bullseye mr-1"></i> Purpose
                                    </label>
                                    <textarea
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" rows="3"
                                        id="purpose" name="purpose" placeholder="Enter purpose of the transaction"></textarea>
                                </div>
                                
                                <!-- Reference -->
                                <div class="md:col-span-2 lg:col-span-2">
                                    <label for="ref" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-link mr-1"></i> Reference
                                    </label>
                                    <textarea
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" rows="3"
                                        id="ref" name="ref" placeholder="Enter any reference numbers, invoice numbers, or related documents"></textarea>
                                </div>
                                
                                <!-- Details -->
                                <div class="md:col-span-2 lg:col-span-4">
                                    <label for="details" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-align-left mr-1"></i> Details *
                                    </label>
                                    <textarea
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" rows="3"
                                        id="details" name="details" required placeholder="Enter detailed description of the transaction"></textarea>
                                </div>
                            </div>
                            
                            <div class="flex justify-end space-x-3 pt-4">
                                <button type="button" onclick="resetPettyCashForm()"
                                    class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 transition-colors">
                                    <i class="fas fa-redo mr-2"></i> Reset
                                </button>
                                <button type="button"
                                    class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors flex items-center"
                                    id="savePettyCashBtn">
                                    <div id="spinner" class="hidden spinner mr-2"></div>
                                    <i class="fas fa-save mr-2"></i>
                                    <span id="saveButtonText">Save Entry</span>
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
            const API_PETTY_CASH_STORE = `${IP_PATH}/api/petty-cash/store.php`;

            /* ================= ELEMENTS ================= */
            const pettyCashForm = document.getElementById('pettyCashForm');
            const savePettyCashBtn = document.getElementById('savePettyCashBtn');
            const spinner = document.getElementById('spinner');
            const saveButtonText = document.getElementById('saveButtonText');

            const typeSelect = document.getElementById('type');
            const toUserSection = document.getElementById('to-user-section');
            const toUserInput = document.getElementById('employeeInput');
            
            const dateInput = document.getElementById('date');
            const amountInput = document.getElementById('amount');
            const purposeTextarea = document.getElementById('purpose');
            const detailsTextarea = document.getElementById('details');
            const refTextarea = document.getElementById('ref');
            
            const userSysIdInput = document.getElementById('user_sys_id');
            const userNameInput = document.getElementById('user_name');
        
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

            function buildDateTime(dateOnly) {
                const now = new Date();
                const time =
                    String(now.getHours()).padStart(2, '0') + ':' +
                    String(now.getMinutes()).padStart(2, '0') + ':' +
                    String(now.getSeconds()).padStart(2, '0');
                return `${dateOnly} ${time}`;
            }

            /* ================= INIT ================= */
            dateInput.value = BD_TIME.getDate();
            dateInput.max = BD_TIME.getDate(); // Can't select future date
        
            // function toggleToUserSection() {
            //     const type = typeSelect.value;
        
            //     if (type === 'loan') {
            //         toUserSection.classList.remove('hidden');
            //     } else {
            //         toUserSection.classList.add('hidden');
            //         if (toUserInput) toUserInput.value = '';
            //     }
            // }
        
            // toggleToUserSection();
            // typeSelect.addEventListener('change', toggleToUserSection);
        
            window.resetPettyCashForm = function () {
                pettyCashForm.reset();
                dateInput.value = todayDate();
                dateInput.max = todayDate();
                // toggleToUserSection();
            };
        
            savePettyCashBtn.addEventListener('click', submitPettyCash);
        
            /* ================= VALIDATION ================= */
            function validateForm() {
                // Validate Date
                if (!dateInput.value) {
                    alert('Please select a date');
                    dateInput.focus();
                    return false;
                }

                // Validate Amount
                const amount = parseFloat(amountInput.value);
                if (!amountInput.value || isNaN(amount) || amount <= 0) {
                    alert('Please enter a valid amount (greater than 0)');
                    amountInput.focus();
                    return false;
                }

                // Validate Details
                if (!detailsTextarea.value.trim()) {
                    alert('Please enter details');
                    detailsTextarea.focus();
                    return false;
                }

                // Validate To User for loan type
                if (typeSelect.value === 'loan') {
                    const toUser = extractIds(toUserInput?.value);
                    if (!toUser || !toUser.sys_id) {
                        alert('Please select an employee for loan');
                        toUserInput?.focus();
                        return false;
                    }
                }

                return true;
            }
        
            /* ================= SUBMIT PETTY CASH ================= */
            async function submitPettyCash() {
                if (!validateForm()) return;

                const type = typeSelect.value;
                const employee = extractIds(employeeInput.value);

                const data = {
                    user_sys_id: userSysIdInput.value || null,
                    user_name: userNameInput.value || null,
                    to_user_sys_id: employee?.sys_id || null,
                    to_user_name: employee?.name || null,
                    date: dateInput.value,
                    purpose: purposeTextarea.value.trim() || null,
                    details: detailsTextarea.value.trim(),
                    type: type,
                    amount: amountInput.value,
                    ref: refTextarea.value.trim() || null,
                    meta_data: JSON.stringify({
                        created_by_date: buildDateTime(dateInput.value),
                        updated_by_date: []
                    })
                };
                
                console.log('Petty Cash data:', data);

                // Disable button and show spinner
                savePettyCashBtn.disabled = true;
                spinner.classList.remove('hidden');
                saveButtonText.textContent = 'Saving...';

                try {
                    const response = await fetch(API_PETTY_CASH_STORE, {
                        method: 'POST',
                        headers: { 
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(data)
                    });

                    const result = await response.json();

                    if (response.ok && result.success) {
                        alert('Petty Cash entry created successfully!');
                        resetPettyCashForm();
                        
                        // Optionally redirect to list page or stay on form
                        setTimeout(() => {
                            window.location.href = 'index-petty.php';
                        }, 1500);
                    } else {
                        alert(result.error || 'Failed to create entry. Please try again.');
                    }

                } catch (error) {
                    console.error('Petty Cash save error:', error);
                    alert('Network error. Please check your connection.');
                } finally {
                    // Re-enable button
                    savePettyCashBtn.disabled = false;
                    spinner.classList.add('hidden');
                    saveButtonText.textContent = 'Save Entry';
                }
            }
        
        });
    </script>

</body>

</html>