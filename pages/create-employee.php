<?php

include_once('./authenticate.php');
$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898";
}

$storeEmployeeApi = $ip_port . "api/employees/store.php";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Employee</title>
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
        
        /* Improved form styling */
        .form-section {
            @apply bg-white rounded-xl border border-gray-200 shadow-sm;
        }
        
        .form-label {
            @apply block text-sm font-medium text-gray-700 mb-2;
        }
        
        .required-star {
            @apply text-red-500 ml-1;
        }
        
        .form-input {
            @apply w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200;
        }
        
        .btn-primary {
            @apply px-5 py-2.5 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200;
        }
        
        .btn-secondary {
            @apply px-5 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-200;
        }
        
        .section-title {
            @apply text-lg font-semibold text-gray-800 mb-4 pb-3 border-b border-gray-200;
        }
        
        .input-group {
            @apply space-y-4;
        }
        
        .form-card {
            @apply bg-white rounded-lg border border-gray-200 p-5;
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
    <main id="mainContent" class="pt-16 pl-64 transition-all duration-300">
        <div class="p-6">
            <div class="grid grid-cols-6 gap-4">
                <div class="col-span-6 bg-white rounded-lg shadow p-4">
                    <!-- Header Card -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                            <div>
                                <div class="flex items-center mb-2">
                                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                        <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h1 class="text-2xl font-bold text-gray-800">Add New Employee</h1>
                                        <p class="text-gray-600 text-sm mt-1">Fill in the details below to add a new employee to the system</p>
                                    </div>
                                </div>
                            </div>
                            <!--<div class="bg-gray-50 p-4 rounded-lg border border-gray-200 min-w-[200px]">-->
                            <!--    <div class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-1">Employee ID</div>-->
                            <!--    <div class="text-lg font-semibold text-gray-800" id="previewId">EMP-XXXXXXX</div>-->
                            <!--</div>-->
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
                    <form id="employeeForm" class="space-y-6">
                        <!-- Employee Type Selection -->
                        <div class="form-section p-5">
                            <h2 class="section-title">Employment Type</h2>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                                <?php
                                $employeeTypes = [
                                    'permanent' => ['label' => 'Permanent', 'icon' => 'fas fa-user-tie', 'color' => 'green'],
                                    'commission-agent' => ['label' => 'Commission Agent', 'icon' => 'fas fa-file-contract', 'color' => 'blue'],
                                    'part-time' => ['label' => 'Part Time', 'icon' => 'fas fa-clock', 'color' => 'purple'],
                                    'provisional' => ['label' => 'Provisional', 'icon' => 'fas fa-hourglass-half', 'color' => 'yellow'],
                                    'intern' => ['label' => 'Intern', 'icon' => 'fas fa-graduation-cap', 'color' => 'indigo']
                                ];
                                
                                foreach ($employeeTypes as $value => $info):
                                ?>
                                <label class="cursor-pointer">
                                    <input type="radio" name="type" value="<?php echo $value; ?>" 
                                        class="sr-only peer" <?php echo $value === 'permanent' ? 'checked' : ''; ?>>
                                    <div class="p-4 border border-gray-300 rounded-lg bg-white peer-checked:border-blue-500 peer-checked:bg-blue-50 transition-all duration-200 hover:border-gray-400">
                                        <div class="flex flex-col items-center text-center">
                                            <div class="w-10 h-10 rounded-full bg-<?php echo $info['color']; ?>-100 flex items-center justify-center mb-2">
                                                <i class="<?php echo $info['icon']; ?> text-<?php echo $info['color']; ?>-600 text-lg"></i>
                                            </div>
                                            <span class="font-medium text-gray-800 text-sm"><?php echo $info['label']; ?></span>
                                        </div>
                                    </div>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
    
                        <!-- Main Form Content -->
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        
                            <!-- ================= Column 1: Personal & Contact ================= -->
                            <div class="space-y-6">
                        
                                <!-- Personal Information -->
                                <div class="form-card p-5 lg:p-6">
                                    <h2 class="section-title flex items-center mb-4">
                                        <i class="fas fa-user-circle mr-2 text-blue-600"></i>
                                        Personal Information
                                    </h2>
                        
                                    <div class="grid grid-cols-1 gap-4">
                                        <div>
                                            <label for="fullName" class="form-label mb-1">
                                                Full Name <span class="required-star">*</span>
                                            </label>
                                            <div class="relative">
                                                <input type="text" id="fullName" name="full_name"
                                                    class="w-full px-3 py-2 pl-10 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                    placeholder="John Doe" required>
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <i class="fas fa-user text-gray-400"></i>
                                                </div>
                                            </div>
                                        </div>
                        
                                        <div>
                                            <label for="dateOfBirth" class="form-label mb-1">
                                                Date of Birth
                                            </label>
                                            <div class="relative">
                                                <input type="date" id="dateOfBirth" name="date_of_birth"
                                                    class="w-full px-3 py-2 pl-10 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                    max="<?php echo date('Y-m-d'); ?>">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <i class="fas fa-calendar-alt text-gray-400"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                        
                                <!-- Contact Information -->
                                <div class="form-card p-5 lg:p-6">
                                    <h2 class="section-title flex items-center mb-4">
                                        <i class="fas fa-address-book mr-2 text-blue-600"></i>
                                        Contact Information
                                    </h2>
                        
                                    <div class="grid grid-cols-1 gap-4">
                                        <div>
                                            <label class="form-label mb-1">
                                                Primary Phone <span class="required-star">*</span>
                                            </label>
                                            <div class="relative">
                                                <input type="tel" id="primaryPhone" name="primary_phone"
                                                    class="w-full px-3 py-2 pl-10 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                    placeholder="+1 (555) 123-4567" required>
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <i class="fas fa-phone text-gray-400"></i>
                                                </div>
                                            </div>
                                        </div>
                        
                                        <div>
                                            <label class="form-label mb-1">
                                                Primary Email <span class="required-star">*</span>
                                            </label>
                                            <div class="relative">
                                                <input type="email" id="primaryEmail" name="primary_email"
                                                    class="w-full px-3 py-2 pl-10 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                    placeholder="john.doe@company.com" required>
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <i class="fas fa-envelope text-gray-400"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                        
                            </div>
                        
                            <!-- ================= Column 2: Company ================= -->
                            <div class="space-y-6">
                        
                                <div class="form-card p-5 lg:p-6">
                                    <h2 class="section-title flex items-center mb-4">
                                        <i class="fas fa-building mr-2 text-blue-600"></i>
                                        Company Information
                                    </h2>
                        
                                    <div class="grid grid-cols-1 gap-4">
                                        <div>
                                            <label for="designation" class="form-label mb-1">
                                                Department <span class="required-star">*</span>
                                            </label>
                                            <div id="departmentSearchContainer" class="relative w-full">
                                                <input
                                                    type="text"
                                                    id="departmentInput"
                                                    placeholder="Search for a department..."
                                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:outline-none"
                                                    autocomplete="off">
                                            
                                                <ul id="departmentDropdown"
                                                    class="absolute w-full bg-white border border-gray-300 rounded-lg mt-1 max-h-60 overflow-auto shadow-lg hidden z-50">
                                                </ul>
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <label for="designation" class="form-label mb-1">
                                                Designation <span class="required-star">*</span>
                                            </label>
                                            <div class="relative">
                                                <input type="text" id="designation" name="designation"
                                                    class="w-full px-3 py-2 pl-10 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                    placeholder="Software Engineer" required>
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <i class="fas fa-briefcase text-gray-400"></i>
                                                </div>
                                            </div>
                                        </div>
                        
                                        <div>
                                            <label for="companyRole" class="form-label mb-1">
                                                Company Role <span class="required-star">*</span>
                                            </label>
                                            <div class="relative">
                                                <textarea id="companyRole" name="company_role" rows="3"
                                                    class="w-full px-3 py-2 pl-10 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"
                                                    placeholder="Describe the employee's role..." required></textarea>
                                                <div class="absolute top-3 left-3">
                                                    <i class="fas fa-tasks text-gray-400"></i>
                                                </div>
                                            </div>
                                        </div>
                        
                                        <div>
                                            <label for="dateOfJoin" class="form-label mb-1">
                                                Date of Join <span class="required-star">*</span>
                                            </label>
                                            <div class="relative">
                                                <input type="date" id="dateOfJoin" name="date_of_join"
                                                    class="w-full px-3 py-2 pl-10 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <i class="fas fa-calendar-check text-gray-400"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                        
                            </div>
                        
                            <!-- ================= Column 3: Address & Additional ================= -->
                            <div class="space-y-6">
                        
                                <!-- Address -->
                                <div class="form-card p-5 lg:p-6">
                                    <h2 class="section-title flex items-center mb-4">
                                        <i class="fas fa-map-marker-alt mr-2 text-blue-600"></i>
                                        Address Information
                                    </h2>
                        
                                    <div class="grid grid-cols-1 gap-4">
                                        <div>
                                            <label class="form-label mb-1">Address Line 1</label>
                                            <div class="relative">
                                                <input type="text" name="address_line_1"
                                                    class="w-full px-3 py-2 pl-10 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                    placeholder="Street address">
                                                <i class="fas fa-road absolute inset-y-0 left-3 flex items-center text-gray-400"></i>
                                            </div>
                                        </div>
                        
                                        <div>
                                            <label class="form-label mb-1">Address Line 2</label>
                                            <div class="relative">
                                                <input type="text" name="address_line_2"
                                                    class="w-full px-3 py-2 pl-10 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                    placeholder="Apartment, suite">
                                                <i class="fas fa-home absolute inset-y-0 left-3 flex items-center text-gray-400"></i>
                                            </div>
                                        </div>
                        
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label class="form-label mb-1">City</label>
                                                <input type="text" name="city" class="form-input">
                                            </div>
                                            <div>
                                                <label class="form-label mb-1">State</label>
                                                <input type="text" name="state" class="form-input">
                                            </div>
                                        </div>
                        
                                        <div>
                                            <label class="form-label mb-1">ZIP Code</label>
                                            <input type="text" name="zip_code" class="form-input">
                                        </div>
                                    </div>
                                </div>
                        
                                <!-- Additional Contacts -->
                                <div class="form-card p-5 lg:p-6">
                                    <h3 class="text-md font-semibold text-gray-700 mb-3">
                                        Additional Contacts
                                    </h3>
                        
                                    <div class="space-y-4">
                                        <div>
                                            <label class="form-label text-sm mb-2 block">
                                                Secondary Phones
                                            </label>
                                            <div id="secondaryPhoneContainer" class="space-y-2"></div>
                                            <button type="button"
                                                class="mt-2 text-sm text-blue-600 hover:text-blue-800 font-medium inline-flex items-center">
                                                <i class="fas fa-plus-circle mr-1"></i> Add Phone
                                            </button>
                                        </div>
                        
                                        <div>
                                            <label class="form-label text-sm mb-2 block">
                                                Secondary Emails
                                            </label>
                                            <div id="secondaryEmailContainer" class="space-y-2"></div>
                                            <button type="button"
                                                class="mt-2 text-sm text-blue-600 hover:text-blue-800 font-medium inline-flex items-center">
                                                <i class="fas fa-plus-circle mr-1"></i> Add Email
                                            </button>
                                        </div>
                                    </div>
                                </div>
                        
                            </div>
                        
                        </div>

    
                        <!-- Form Actions -->
                        <div class="space-x-3 pt-6 border-t flex flex-col sm:flex-row justify-between items-center gap-4">
                            <div class="text-sm text-gray-500">
                                <i class="fas fa-info-circle mr-1"></i>
                                Fields marked with <span class="required-star">*</span> are required
                            </div>
                            <div class="flex space-x-3">
                                <button type="button" onclick="resetForm()"
                                    class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                                    <i class="fas fa-redo mr-2"></i>
                                    Reset
                                </button>
                                <button type="submit"
                                    class="px-6 py-2 border border-transparent rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <i class="fas fa-user-plus mr-2"></i>
                                    Add Employee
                                </button>
                            </div>
                        </div>
                        <!--<div class="form-section p-5 mt-6">-->
                        <!--    <div class="flex flex-col sm:flex-row justify-between items-center gap-4">-->
                        <!--    </div>-->
                        <!--</div>-->
                    </form>
                </div>
            </div>
        </div>
    </main>
    
    <script src="../assets/js/script.js"></script>

    <script>
        let departmentData = [
            { id: 1, name: 'Management' },
            { id: 2, name: 'Visa' },
            { id: 3, name: 'Package' },
            { id: 4, name: 'Ticket' },
            { id: 5, name: 'IT' },
            { id: 6, name: 'Student' },
            { id: 7, name: 'Medical' },
            { id: 8, name: 'Account' }
        ];
        const departmentInput = document.getElementById('departmentInput');
        const departmentDropdown = document.getElementById('departmentDropdown');
        const departmentContainer = document.getElementById('departmentSearchContainer');
        
        /* Typing */
        let departmentTypingTimer;
        departmentInput.addEventListener('input', () => {
            clearTimeout(departmentTypingTimer);
            departmentTypingTimer = setTimeout(() => {
                const value = departmentInput.value.toLowerCase().trim();
        
                const filtered = value === ''
                    ? departmentData
                    : departmentData.filter(d =>
                        d.name?.toLowerCase().includes(value) ||
                        d.sys_id?.toLowerCase().includes(value)
                    );
        
                renderDepartmentDropdown(filtered);
                departmentDropdown.classList.remove('hidden');
            }, 300);
        });
        
        /* Focus */
        departmentInput.addEventListener('focus', () => {
            renderDepartmentDropdown(departmentData);
            departmentDropdown.classList.remove('hidden');
        });
        
        function renderDepartmentDropdown(list) {
            departmentDropdown.innerHTML = '';
        
            if (!list.length) {
                departmentDropdown.innerHTML =
                    `<li class="px-4 py-3 text-center text-gray-500">No clients found</li>`;
                return;
            }
        
            list.forEach(department => {
                console.log(department);
                const li = document.createElement('li');
                li.className =
                    "px-4 py-3 cursor-pointer hover:bg-purple-50 border-b last:border-b-0";
        
                li.innerHTML = `
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-purple-600 rounded-full text-white flex items-center justify-center font-semibold">
                            ${department.name?.charAt(0).toUpperCase() ?? 'C'}
                        </div>
                        <div class="ml-3 flex-1">
                            <div class="font-medium">${department.name}</div>
                        </div>
                    </div>
                `;
        
                li.onclick = () => {
                    departmentInput.value = `${department.id} | ${department.name}`;
                    departmentDropdown.classList.add('hidden');
                };
        
                departmentDropdown.appendChild(li);
            });
        }
        
        /* Outside click */
        document.addEventListener('click', e => {
            if (!departmentContainer.contains(e.target)) {
                departmentDropdown.classList.add('hidden');
            }
        });


        const API_URL_FOR_CLIENT_STORE = "<?php echo $storeEmployeeApi; ?>";

        // Initialize date inputs
        document.addEventListener('DOMContentLoaded', function() {
            // Set max date for date of join to today
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('dateOfJoin').max = today;
        });

        // Secondary Phone Management
        function addSecondaryPhone() {
            const container = document.getElementById('secondaryPhoneContainer');
            const div = document.createElement('div');
            div.className = 'flex items-center gap-2 animate-slide-in';
            div.innerHTML = `
                <select name="secondary_phone_type[]" 
                    class="w-1/3 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="mobile">Mobile</option>
                    <option value="home">Home</option>
                    <option value="work">Work</option>
                    <option value="other">Other</option>
                </select>
                <div class="flex-grow relative">
                    <input type="tel" name="secondary_phone_number[]"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                        placeholder="Phone number">
                </div>
                <button type="button" onclick="removeSecondaryPhone(this)" 
                    class="p-2 text-red-500 hover:text-red-700 rounded-lg">
                    <i class="fas fa-times"></i>
                </button>
            `;
            container.appendChild(div);
        }

        function removeSecondaryPhone(button) {
            button.closest('.flex.items-center').remove();
        }

        // Secondary Email Management
        function addSecondaryEmail() {
            const container = document.getElementById('secondaryEmailContainer');
            const div = document.createElement('div');
            div.className = 'flex items-center gap-2 animate-slide-in';
            div.innerHTML = `
                <select name="secondary_email_type[]" 
                    class="w-1/3 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="work">Work</option>
                    <option value="personal">Personal</option>
                    <option value="other">Other</option>
                </select>
                <div class="flex-grow relative">
                    <input type="email" name="secondary_email_address[]"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                        placeholder="Email address">
                </div>
                <button type="button" onclick="removeSecondaryEmail(this)" 
                    class="p-2 text-red-500 hover:text-red-700 rounded-lg">
                    <i class="fas fa-times"></i>
                </button>
            `;
            container.appendChild(div);
        }

        function removeSecondaryEmail(button) {
            button.closest('.flex.items-center').remove();
        }

        // Form Validation
        function validateForm() {
            const fullName = document.getElementById('fullName').value.trim();
            const primaryPhone = document.getElementById('primaryPhone').value.trim();
            const primaryEmail = document.getElementById('primaryEmail').value.trim();
            const designation = document.getElementById('designation').value.trim();
            const companyRole = document.getElementById('companyRole').value.trim();
            const dateOfJoin = document.getElementById('dateOfJoin').value;

            if (!fullName) {
                showMessage('Full name is required', 'error');
                document.getElementById('fullName').focus();
                return false;
            }
            if (!primaryPhone) {
                showMessage('Primary phone is required', 'error');
                document.getElementById('primaryPhone').focus();
                return false;
            }
            if (!primaryEmail) {
                showMessage('Primary email is required', 'error');
                document.getElementById('primaryEmail').focus();
                return false;
            }
            if (!designation) {
                showMessage('Designation is required', 'error');
                document.getElementById('designation').focus();
                return false;
            }
            if (!companyRole) {
                showMessage('Company role is required', 'error');
                document.getElementById('companyRole').focus();
                return false;
            }
            if (!dateOfJoin) {
                showMessage('Date of join is required', 'error');
                document.getElementById('dateOfJoin').focus();
                return false;
            }

            return true;
        }

        // Form Submission
        document.getElementById('employeeForm').addEventListener('submit', async function(e) {
            e.preventDefault();
        
            if (!validateForm()) {
                return;
            }
        
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Adding...';
            submitBtn.disabled = true;
        
            // Collect form data
            const formData = new FormData(this);
            
            // Prepare data for API
            const data = {
                type: formData.get('type') || 'permanent',
                full_name: formData.get('full_name'),
                status: 'active',
                date_of_birth: formData.get('date_of_birth'),
                created_by: 'current_user' // Replace with actual user from session
            };
        
            // Get department value
            const departmentInput = document.getElementById('departmentInput').value;
            if (departmentInput) {
                data.department = departmentInput;
            }
        
            // Prepare company_related_info JSON
            data.company_related_info = {
                designation: formData.get('designation'),
                company_role: formData.get('company_role'),
                date_of_join: formData.get('date_of_join')
            };
        
            // Prepare phone information
            data.phone = {
                primary_no: formData.get('primary_phone')
            };
        
            const secondaryPhoneTypes = formData.getAll('secondary_phone_type[]');
            const secondaryPhoneNumbers = formData.getAll('secondary_phone_number[]');
            
            if (secondaryPhoneTypes.length > 0) {
                data.phone.secondary_no = secondaryPhoneTypes.map((type, index) => ({
                    type: type,
                    number: secondaryPhoneNumbers[index] || ''
                }));
            }
        
            // Prepare email information
            data.email = {
                primary: formData.get('primary_email')
            };
        
            const secondaryEmailTypes = formData.getAll('secondary_email_type[]');
            const secondaryEmailAddresses = formData.getAll('secondary_email_address[]');
            
            if (secondaryEmailTypes.length > 0) {
                data.email.secondary = secondaryEmailTypes.map((type, index) => ({
                    type: type,
                    address: secondaryEmailAddresses[index] || ''
                }));
            }
        
            // Prepare address information
            data.address = {
                address_line_1: formData.get('address_line_1') || '',
                address_line_2: formData.get('address_line_2') || '',
                city: formData.get('city') || '',
                state: formData.get('state') || '',
                zip_code: formData.get('zip_code') || '',
                country: formData.get('country') || ''
            };
        
            console.log('Data to send:', data); // For debugging
        
            // Send to server
            try {
                const response = await fetch(API_URL_FOR_CLIENT_STORE, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });
        
                const result = await response.json();
                console.log('Response:', result); // For debugging
        
                if (result.success) {
                    showMessage('Employee added successfully!', 'success');
                    // Reset form after successful submission
                    setTimeout(() => {
                        resetForm();
                    }, 2000);
                } else {
                    showMessage(result.message || 'Failed to add employee', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage('Network error: ' + error.message, 'error');
            } finally {
                // Reset button state
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });
        
        // Form Validation function-এ department validation যোগ করুন
        function validateForm() {
            const fullName = document.getElementById('fullName').value.trim();
            const primaryPhone = document.getElementById('primaryPhone').value.trim();
            const primaryEmail = document.getElementById('primaryEmail').value.trim();
            const designation = document.getElementById('designation').value.trim();
            const companyRole = document.getElementById('companyRole').value.trim();
            const dateOfJoin = document.getElementById('dateOfJoin').value;
            const department = document.getElementById('departmentInput').value.trim();
        
            if (!fullName) {
                showMessage('Full name is required', 'error');
                document.getElementById('fullName').focus();
                return false;
            }
            if (!primaryPhone) {
                showMessage('Primary phone is required', 'error');
                document.getElementById('primaryPhone').focus();
                return false;
            }
            if (!primaryEmail) {
                showMessage('Primary email is required', 'error');
                document.getElementById('primaryEmail').focus();
                return false;
            }
            if (!department) {
                showMessage('Department is required', 'error');
                document.getElementById('departmentInput').focus();
                return false;
            }
            if (!designation) {
                showMessage('Designation is required', 'error');
                document.getElementById('designation').focus();
                return false;
            }
            if (!companyRole) {
                showMessage('Company role is required', 'error');
                document.getElementById('companyRole').focus();
                return false;
            }
            if (!dateOfJoin) {
                showMessage('Date of join is required', 'error');
                document.getElementById('dateOfJoin').focus();
                return false;
            }
        
            return true;
        }

        // Show Messages
        function showMessage(message, type) {
            const container = document.getElementById('messageContainer');
            const successDiv = document.getElementById('successMessage');
            const errorDiv = document.getElementById('errorMessage');

            container.classList.remove('hidden');

            if (type === 'success') {
                successDiv.classList.remove('hidden');
                errorDiv.classList.add('hidden');
                document.getElementById('successText').textContent = message;
                
                // Auto-hide success after 5 seconds
                setTimeout(() => {
                    container.classList.add('hidden');
                }, 5000);
            } else {
                errorDiv.classList.remove('hidden');
                successDiv.classList.add('hidden');
                document.getElementById('errorText').textContent = message;
                
                // Auto-hide error after 8 seconds
                setTimeout(() => {
                    container.classList.add('hidden');
                }, 8000);
            }
        }

        // Form Reset
        function resetForm() {
            if (confirm('Are you sure you want to reset the form? All entered data will be lost.')) {
                document.getElementById('employeeForm').reset();
                
                // Reset secondary phone and email inputs
                document.getElementById('secondaryPhoneContainer').innerHTML = '';
                document.getElementById('secondaryEmailContainer').innerHTML = '';
                
                // Reset department input
                document.getElementById('departmentInput').value = '';
                
                // Set default employee type to permanent
                document.querySelector('input[name="type"][value="permanent"]').checked = true;
                
                // Set date inputs to empty
                document.getElementById('dateOfJoin').value = '';
                document.getElementById('dateOfBirth').value = '';
                
                showMessage('Form has been reset', 'success');
            }
        }
    </script>
</body>
</html>