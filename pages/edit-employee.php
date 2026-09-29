<?php

include_once('./authenticate.php');
$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898";
}

$storeEmployeeApi  = $ip_port . "api/employees/store.php";
$updateEmployeeApi = $ip_port . "api/employees/update.php";
$getEmployeeApi    = $ip_port . "api/employees/get-employee.php";

$employeeSysId = $_GET['sys_id'] ?? $_GET['id'] ?? '';
if (!$employeeSysId) {
    die('<div style="font-family:Arial,sans-serif;padding:60px;text-align:center;color:#666;"><h2>Missing employee</h2><p>No sys_id was given to edit.</p></div>');
}

// Load the employee's current data server-side so the form can be
// pre-filled on first render, instead of showing a blank form and filling
// it in with a second JS request after the page has already painted.
require_once __DIR__ . '/../server/db_connection.php';
$empStmt = $pdo->prepare("SELECT * FROM employees WHERE sys_id = ? LIMIT 1");
$empStmt->execute([$employeeSysId]);
$existingEmployee = $empStmt->fetch(PDO::FETCH_ASSOC);
if (!$existingEmployee) {
    die('<div style="font-family:Arial,sans-serif;padding:60px;text-align:center;color:#666;"><h2>Employee not found</h2><p>No employee with sys_id ' . htmlspecialchars($employeeSysId) . '.</p></div>');
}
$existingCompanyInfo = json_decode($existingEmployee['company_related_info'] ?? '{}', true) ?: [];
$existingBasicInfo    = json_decode($existingEmployee['basic_info'] ?? '{}', true) ?: [];
$existingPhone        = json_decode($existingEmployee['phone'] ?? '{}', true) ?: [];
$existingEmail        = json_decode($existingEmployee['email'] ?? '{}', true) ?: [];
$existingAddress      = json_decode($existingEmployee['address'] ?? '{}', true) ?: [];
$existingEmergency    = json_decode($existingEmployee['emergency_contact'] ?? '{}', true) ?: [];

function efVal($v) { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Employee</title>
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
    
    <!-- Main Content -->
    <main id="mainContent" class="pt-16 pl-0 lg:pl-64 lg:my-16 transition-all duration-300 h-full">
        <div class="p-6">
            <div class="bg-white rounded-lg shadow p-4">
                <!-- Header Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div>
                            <div class="flex items-center mb-2">
                                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                    <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h1 class="text-2xl font-bold text-gray-800">Edit Employee — <?php echo efVal($existingEmployee["name"]); ?></h1>
                                    <p class="text-gray-600 text-sm mt-1">Fill in the details below to add a new employee to the system</p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 min-w-[200px]">
                            <div class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-1">Employee ID</div>
                            <div class="text-lg font-semibold text-gray-800" id="previewId">EMP-XXXXXXX</div>
                        </div>
                    </div>
                </div>

                <!-- Success/Error Messages -->
                <div id="messageContainer" class="hidden my-6 animate-slide-in">
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
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 mt-4">
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
                    <div class="grid grid-cols-1 lg:grid-cols-4">
                    
                        <!-- ================= Column 1: Personal & Contact ================= -->
                        <div class="space-y-6">
                    
                            <!-- Personal Information -->
                            <div class="form-card p-2 lg:p-4">
                                <h2 class="section-title flex items-center mb-4">
                                    <i class="fas fa-user-circle mr-2 text-blue-600"></i>
                                    Personal Information
                                </h2>
                    
                                <div class="grid grid-cols-1 gap-4">
                                    <div>
                                        <label class="form-label mb-1">Profile Photo <span class="text-gray-400 font-normal">(ID Card-এ ব্যবহার হবে)</span></label>
                                        <div class="flex items-center gap-4">
                                            <img id="profilePhotoPreview"
                                                src="<?php echo !empty($existingEmployee['profile_photo']) ? efVal($ip_port . 'storage/' . $existingEmployee['profile_photo']) : "data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22%3E%3Ccircle cx=%2250%22 cy=%2250%22 r=%2250%22 fill=%22%23e5e7eb%22/%3E%3Ccircle cx=%2250%22 cy=%2238%22 r=%2216%22 fill=%22%239ca3af%22/%3E%3Cellipse cx=%2250%22 cy=%2280%22 rx=%2228%22 ry=%2220%22 fill=%22%239ca3af%22/%3E%3C/svg%3E"; ?>"
                                                onerror="this.src='data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22%3E%3Ccircle cx=%2250%22 cy=%2250%22 r=%2250%22 fill=%22%23e5e7eb%22/%3E%3Ccircle cx=%2250%22 cy=%2238%22 r=%2216%22 fill=%22%239ca3af%22/%3E%3Cellipse cx=%2250%22 cy=%2280%22 rx=%2228%22 ry=%2220%22 fill=%22%239ca3af%22/%3E%3C/svg%3E'"
                                                class="w-20 h-20 rounded-full object-cover border-2 border-gray-200">
                                            <div>
                                                <input type="file" id="profilePhotoInput" name="profile_photo" accept="image/jpeg,image/png,image/webp"
                                                    class="text-sm text-gray-600" onchange="previewProfilePhoto(this)">
                                                <p class="text-xs text-gray-400 mt-1">নতুন ছবি না দিলে আগেরটাই থাকবে</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <label for="fullName" class="form-label mb-1">
                                            Full Name <span class="required-star">*</span>
                                        </label>
                                        <div class="relative">
                                            <input type="text" id="fullName" name="full_name" value="<?php echo efVal($existingEmployee["name"]); ?>"
                                                class="w-full px-3 py-2 pl-10 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                placeholder="John Doe" required>
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="fas fa-user text-gray-400"></i>
                                            </div>
                                        </div>
                                    </div>
                    
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label for="dateOfBirth" class="form-label mb-1">
                                                Date of Birth
                                            </label>
                                            <div class="relative">
                                                <input type="date" id="dateOfBirth" name="date_of_birth" value="<?php echo efVal($existingBasicInfo["date_of_birth"] ?? ""); ?>"
                                                    class="w-full px-3 py-2 pl-10 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                    max="<?php echo date('Y-m-d'); ?>">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <i class="fas fa-calendar-alt text-gray-400"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <?php include('./form-selects/blood-groups.php') ?>
                                        </div>
                                    </div>
                                </div>
                    
                                <!-- Contact Information -->
                                <h2 class="section-title flex items-center my-4">
                                    <i class="fas fa-address-book mr-2 text-blue-600"></i>
                                    Contact Information
                                </h2>
                    
                                <div class="grid grid-cols-1 gap-4">
                                    <div>
                                        <label class="form-label mb-1">
                                            Primary Phone <span class="required-star">*</span>
                                        </label>
                                        <div class="relative">
                                            <input type="tel" id="primaryPhone" name="primary_phone" value="<?php echo efVal($existingPhone["primary_no"] ?? ""); ?>"
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
                                            <input type="email" id="primaryEmail" name="primary_email" value="<?php echo efVal($existingEmail["primary"] ?? ""); ?>"
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
                    
                            <div class="form-card p-2 lg:p-4">
                                <h2 class="section-title flex items-center mb-4">
                                    <i class="fas fa-building mr-2 text-blue-600"></i>
                                    Company Information
                                </h2>
                    
                                <div class="grid grid-cols-1 gap-4">
                                    <?php include('./form-selects/departments.php') ?>
                                    
                                    <div>
                                        <label for="designation" class="form-label mb-1">
                                            Designation <span class="required-star">*</span>
                                        </label>
                                        <div class="relative">
                                            <input type="text" id="designation" name="designation" value="<?php echo efVal($existingCompanyInfo["designation"] ?? ""); ?>"
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
                                                placeholder="Describe the employee's role..." required><?php echo efVal($existingCompanyInfo["company_role"] ?? ""); ?></textarea>
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
                                            <input type="date" id="dateOfJoin" name="date_of_join" value="<?php echo efVal($existingCompanyInfo["date_of_join"] ?? ""); ?>"
                                                class="w-full px-3 py-2 pl-10 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="fas fa-calendar-check text-gray-400"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- The fields below are optional here and only needed later for
                                         generating an Appointment Letter or Salary Certificate — they
                                         can be filled in now or added via Edit Employee at any time. -->
                                    <div class="border-t border-gray-100 pt-4">
                                        <p class="text-xs text-gray-400 mb-3">নিচের তথ্যগুলো ঐচ্ছিক — Appointment Letter / Salary Certificate তৈরির সময় লাগবে</p>
                                    </div>

                                    <div>
                                        <label for="fatherName" class="form-label mb-1">Father's Name</label>
                                        <input type="text" id="fatherName" name="father_name" value="<?php echo efVal($existingCompanyInfo["father_name"] ?? ""); ?>"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    </div>

                                    <div>
                                        <label for="motherName" class="form-label mb-1">Mother's Name</label>
                                        <input type="text" id="motherName" name="mother_name" value="<?php echo efVal($existingCompanyInfo["mother_name"] ?? ""); ?>"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    </div>

                                    <div>
                                        <label for="spouseName" class="form-label mb-1">Spouse's Name (if applicable)</label>
                                        <input type="text" id="spouseName" name="spouse_name" value="<?php echo efVal($existingCompanyInfo["spouse_name"] ?? ""); ?>"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    </div>

                                    <div>
                                        <label for="nidNo" class="form-label mb-1">National ID (NID) No.</label>
                                        <input type="text" id="nidNo" name="nid_no" value="<?php echo efVal($existingCompanyInfo["nid_no"] ?? ""); ?>"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    </div>

                                    <div>
                                        <label for="grossSalary" class="form-label mb-1">Gross Monthly Salary (BDT)</label>
                                        <input type="number" step="0.01" min="0" id="grossSalary" name="gross_salary" value="<?php echo efVal($existingCompanyInfo["gross_salary"] ?? ""); ?>"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="50000">
                                        <p class="text-xs text-gray-400 mt-1">Appointment Letter-এ Basic 50% / House Rent 30% / Medical 10% / Conveyance 10% হিসেবে ভাগ হবে</p>
                                    </div>

                                    <div>
                                        <label for="reportingToName" class="form-label mb-1">Reporting To — Name</label>
                                        <input type="text" id="reportingToName" name="reporting_to_name" value="<?php echo efVal($existingCompanyInfo["reporting_to_name"] ?? ""); ?>"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    </div>

                                    <div>
                                        <label for="reportingToDesignation" class="form-label mb-1">Reporting To — Designation</label>
                                        <input type="text" id="reportingToDesignation" name="reporting_to_designation" value="<?php echo efVal($existingCompanyInfo["reporting_to_designation"] ?? ""); ?>"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                </div>
                            </div>
                    
                        </div>
                    
                        <!-- ================= Column 3: Address & Additional ================= -->
                        <div class="space-y-6">
                    
                            <!-- Address -->
                            <div class="form-card p-2 lg:p-4">
                                <h2 class="section-title flex items-center mb-4">
                                    <i class="fas fa-map-marker-alt mr-2 text-blue-600"></i>
                                    Address Information
                                </h2>
                    
                                <div class="grid grid-cols-1 gap-4">
                                    <div>
                                        <label class="form-label mb-1">Address Line 1</label>
                                        <div class="relative">
                                            <input type="text" name="address_line_1" value="<?php echo efVal($existingAddress["address_line_1"] ?? ""); ?>"
                                                class="w-full px-3 py-2 pl-10 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                placeholder="Street address">
                                            <i class="fas fa-road absolute inset-y-0 left-3 flex items-center text-gray-400"></i>
                                        </div>
                                    </div>
                    
                                    <div>
                                        <label class="form-label mb-1">Address Line 2</label>
                                        <div class="relative">
                                            <input type="text" name="address_line_2" value="<?php echo efVal($existingAddress["address_line_2"] ?? ""); ?>"
                                                class="w-full px-3 py-2 pl-10 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                placeholder="Apartment, suite">
                                            <i class="fas fa-home absolute inset-y-0 left-3 flex items-center text-gray-400"></i>
                                        </div>
                                        </div>
                                    </div>
                    
                                    <div class="grid grid-cols-2 gap-3 mt-4">
                                        <div>
                                            <label class="form-label mb-1">City</label>
                                            <div class="relative">
                                                <input type="text" name="city" value="<?php echo efVal($existingAddress["city"] ?? ""); ?>" class="w-full px-3 py-2 pl-10 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                                <i class="fa-solid fa-city absolute inset-y-0 left-3 flex items-center text-gray-400"></i>
                                            </div>
                                        <div class="mt-4">
                                            <label class="form-label mb-1">State</label>
                                            <div class="relative">
                                                <input type="text" name="state" value="<?php echo efVal($existingAddress["state"] ?? ""); ?>" class="w-full px-3 py-2 pl-10 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                                <i class="fa-solid fa-globe absolute inset-y-0 left-3 flex items-center text-gray-400"></i>
                                            </div>
                                        </div>
                                    </div>
                    
                                    <div>
                                        <label class="form-label mb-1">ZIP Code</label>
                                        <div class="relative">
                                            <input type="text" name="zip_code" value="<?php echo efVal($existingAddress["zip_code"] ?? ""); ?>" class="w-full px-3 py-2 pl-10 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                            <i class="fa-solid fa-signs-post absolute inset-y-0 left-3 flex items-center text-gray-400"></i>
                                        </div>
                                    </div>
                                </div>

                                <!-- Additional Contacts -->
                                <h3 class="text-md font-semibold text-gray-700 mt-4 mb-3">
                                    Additional Contacts
                                </h3>
                    
                                <div class="space-y-4">
                                    <div>
                                        <div class="flex items-center justify-between mb-2">
                                            <label class="form-label text-sm block mb-0">
                                                Secondary Phones
                                            </label>
                                            <button type="button" onclick="addSecondaryPhone()"
                                                class="text-sm text-blue-600 hover:text-blue-800 font-medium inline-flex items-center">
                                                <i class="fas fa-plus-circle mr-1"></i> Add Phone
                                            </button>
                                        </div>
                                        
                                        <div id="secondaryPhoneContainer" class="space-y-2"></div>
                                    </div>
                    
                                    <div>
                                        <div class="flex items-center justify-between mb-2">
                                            <label class="form-label text-sm mb-2 block">
                                                Secondary Emails
                                            </label>
                                            <button type="button" onclick="addSecondaryEmail()"
                                                class="mt-2 text-sm text-blue-600 hover:text-blue-800 font-medium inline-flex items-center">
                                                <i class="fas fa-plus-circle mr-1"></i> Add Email
                                            </button>
                                        </div>
                                        <div id="secondaryEmailContainer" class="space-y-2"></div>
                                    </div>
                                </div>
                            </div>
                    
                        </div>
                        
                        <!-- ================= Column 4: Emergency Contact ================= -->
                        <div class="space-y-6">
                    
                            <!-- Address -->
                            <div class="form-card p-2 lg:p-4">
                                <h2 class="section-title flex items-center mb-4">
                                    <i class="fa-solid fa-circle-exclamation mr-2 text-blue-600"></i>
                                    Emergency Contact
                                </h2>
                    
                                <div class="grid grid-cols-1 gap-4">
                                    <div>
                                        <label class="form-label mb-1">Emergency Contact Person</label>
                                        <div class="relative">
                                            <input type="text" name="emergency_contact_person" value="<?php echo efVal($existingEmergency["person"] ?? ""); ?>"
                                                class="w-full px-3 py-2 pl-10 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                placeholder="Emergency Contact Person">
                                            <i class="fas fa-user absolute inset-y-0 left-3 flex items-center text-gray-400"></i>
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="form-label mb-1">Relationship</label>
                                            <div class="relative">
                                                <input type="text" name="relation" value="<?php echo efVal($existingEmergency["relation"] ?? ""); ?>" class="w-full px-3 py-2 pl-10 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                                <i class="fa-solid fa-users absolute inset-y-0 left-3 flex items-center text-gray-400"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="form-label mb-1">Phone No</label>
                                            <div class="relative">
                                                <input type="text" name="emergency_phone" value="<?php echo efVal($existingEmergency["phone"] ?? ""); ?>" class="w-full px-3 py-2 pl-10 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                                <i class="fa-solid fa-phone absolute inset-y-0 left-3 flex items-center text-gray-400"></i>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label class="form-label mb-1">Address Line 1</label>
                                        <div class="relative">
                                            <input type="text" name="emergency_address_line_1"
                                                class="w-full px-3 py-2 pl-10 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                placeholder="Street address">
                                            <i class="fas fa-road absolute inset-y-0 left-3 flex items-center text-gray-400"></i>
                                        </div>
                                    </div>
                    
                                    <div>
                                        <label class="form-label mb-1">Address Line 2</label>
                                        <div class="relative">
                                            <input type="text" name="emergency_address_line_2"
                                                class="w-full px-3 py-2 pl-10 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                placeholder="Apartment, suite">
                                            <i class="fas fa-home absolute inset-y-0 left-3 flex items-center text-gray-400"></i>
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="form-label mb-1">City</label>
                                            <div class="relative">
                                                <input type="text" name="emergency_city" class="w-full px-3 py-2 pl-10 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                                <i class="fa-solid fa-city absolute inset-y-0 left-3 flex items-center text-gray-400"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="form-label mb-1">State</label>
                                            <div class="relative">
                                                <input type="text" name="emergency_state" class="w-full px-3 py-2 pl-10 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                                <i class="fa-solid fa-globe absolute inset-y-0 left-3 flex items-center text-gray-400"></i>
                                            </div>
                                        </div>
                                    </div>
                    
                                    <div>
                                        <label class="form-label mb-1">ZIP Code</label>
                                        <div class="relative">
                                            <input type="text" name="emergency_zip_code" class="w-full px-3 py-2 pl-10 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                            <i class="fa-solid fa-signs-post absolute inset-y-0 left-3 flex items-center text-gray-400"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-span-2 space-y-6">
                            <label class="block text-sm font-medium text-gray-700 my-2">Upload or Paste Your Photo/s</label>
                            <?php include('./form-elements/file-uploader.php') ?>
                        </div>
                    
                    </div>

                    <!-- Hidden fields for department and blood group -->
                    <input type="hidden" id="selectedDepartmentId" name="department_id">
                    <input type="hidden" id="selectedBloodGroupValue" name="blood_group">

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
                                Update Employee
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>

    <script>
        const API_URL_FOR_UPDATE = "<?php echo $updateEmployeeApi; ?>";
        const API_URL_FOR_PHOTO_UPLOAD = "<?php echo $ip_port; ?>api/employees/upload-photo.php";

        function previewProfilePhoto(input) {
            if (!input.files || !input.files[0]) return;
            const reader = new FileReader();
            reader.onload = e => { document.getElementById('profilePhotoPreview').src = e.target.result; };
            reader.readAsDataURL(input.files[0]);
        }
        const EXISTING_SYS_ID = <?php echo json_encode($employeeSysId); ?>;

        console.log(droppedFiles);

        // Initialize date inputs
        document.addEventListener('DOMContentLoaded', function() {
            // Set max date for date of join to today
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('dateOfJoin').max = today;

            // Pre-fill Department and Blood Group -- these two are normally
            // set by picking from a dropdown, whose hidden value field this
            // page also relies on, so set both the visible text and the
            // hidden value directly here rather than re-deriving them from
            // departments.php's own (differently-scoped) id list.
            const existingDeptId   = <?php echo json_encode($existingEmployee['department_id'] ?? ''); ?>;
            const existingDeptName = <?php echo json_encode($existingEmployee['department_name'] ?? ($existingCompanyInfo['department'] ?? '')); ?>;
            if (existingDeptName) {
                const deptInput = document.getElementById('departmentInput');
                const deptHidden = document.getElementById('selectedDepartmentId');
                if (deptInput) deptInput.value = existingDeptName;
                if (deptHidden) deptHidden.value = existingDeptId;
            }

            const existingBloodGroup = <?php echo json_encode($existingBasicInfo['blood_group'] ?? ''); ?>;
            if (existingBloodGroup) {
                const bgInput = document.getElementById('bloodGroupInput');
                const bgHidden = document.getElementById('selectedBloodGroupValue');
                if (bgInput) bgInput.value = existingBloodGroup;
                if (bgHidden) bgHidden.value = existingBloodGroup;
            }
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
            const department = document.getElementById('departmentInput').value.trim();
            const bloodGroup = document.getElementById('bloodGroupInput').value.trim();

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
            if (!bloodGroup) {
                showMessage('Blood group is required', 'error');
                document.getElementById('bloodGroupInput').focus();
                return false;
            }

            return true;
        }

        // Form Submission
        // document.getElementById('employeeForm').addEventListener('submit', async function(e) {
        //     e.preventDefault();
        
        //     if (!validateForm()) {
        //         return;
        //     }
            
        //     // File validation check
        //     if (droppedFiles.length === 0) {
        //         if (!confirm('No files uploaded. Do you want to continue without files?')) {
        //             return;
        //         }
        //     }
        
        //     // Show loading state
        //     const submitBtn = this.querySelector('button[type="submit"]');
        //     const originalText = submitBtn.innerHTML;
        //     submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Adding...';
        //     submitBtn.disabled = true;
        
        //     // Collect form data
        //     const formData = new FormData(this);
            
        //     // Debug: Log all form data
        //     console.log('Form Data:', Object.fromEntries(formData));
            
        //     // Prepare data for API
        //     const data = {
        //         type: formData.get('type') || 'permanent',
        //         full_name: formData.get('full_name'),
        //         status: 'active',
        //         date_of_birth: formData.get('date_of_birth'),
        //         created_by: 'current_user', // Replace with actual user from session
        //         blood_group: formData.get('blood_group') // Add blood group
        //         files_count: droppedFiles.length // Send file count
        //     };
        
        //     // Get department values from hidden fields
        //     const departmentId = document.getElementById('selectedDepartmentId').value;
        //     const departmentName = document.getElementById('departmentInput').value;
            
        //     if (departmentName) {
        //         data.department = departmentName;
        //         if (departmentId) {
        //             data.department_id = departmentId;
        //         }
        //     }
        
        //     // Prepare company_related_info JSON
        //     data.company_related_info = {
        //         designation: formData.get('designation'),
        //         company_role: formData.get('company_role'),
        //         date_of_join: formData.get('date_of_join')
        //     };
        
        //     // Prepare phone information
        //     data.phone = {
        //         primary_no: formData.get('primary_phone')
        //     };
        
        //     const secondaryPhoneTypes = formData.getAll('secondary_phone_type[]');
        //     const secondaryPhoneNumbers = formData.getAll('secondary_phone_number[]');
            
        //     if (secondaryPhoneTypes.length > 0) {
        //         data.phone.secondary_no = secondaryPhoneTypes.map((type, index) => ({
        //             type: type,
        //             number: secondaryPhoneNumbers[index] || ''
        //         }));
        //     }
        
        //     // Prepare email information
        //     data.email = {
        //         primary: formData.get('primary_email')
        //     };
        
        //     const secondaryEmailTypes = formData.getAll('secondary_email_type[]');
        //     const secondaryEmailAddresses = formData.getAll('secondary_email_address[]');
            
        //     if (secondaryEmailTypes.length > 0) {
        //         data.email.secondary = secondaryEmailTypes.map((type, index) => ({
        //             type: type,
        //             address: secondaryEmailAddresses[index] || ''
        //         }));
        //     }
        
        //     // Prepare address information
        //     data.address = {
        //         address_line_1: formData.get('address_line_1') || '',
        //         address_line_2: formData.get('address_line_2') || '',
        //         city: formData.get('city') || '',
        //         state: formData.get('state') || '',
        //         zip_code: formData.get('zip_code') || '',
        //         country: formData.get('country') || ''
        //     };
        
        //     // Prepare emergency contact information
        //     data.emergency_contact = {
        //         person: formData.get('emergency_contact_person') || '',
        //         relation: formData.get('relation') || '',
        //         phone: formData.get('emergency_phone') || '',
        //         address: {
        //             address_line_1: formData.get('emergency_address_line_1') || '',
        //             address_line_2: formData.get('emergency_address_line_2') || '',
        //             city: formData.get('emergency_city') || '',
        //             state: formData.get('emergency_state') || '',
        //             zip_code: formData.get('emergency_zip_code') || ''
        //         }
        //     };
        
        //     console.log('Data to send:', data); // For debugging
        
        //     // Send to server
        //     try {
        //         const uploadFormData = new FormData();
                
        //         // Add all files
        //         if (droppedFiles.length > 0) {
        //             droppedFiles.forEach((file, index) => {
        //                 uploadFormData.append(`files[]`, file);
        //             });
        //         }
                
        //         // Add other data as JSON
        //         uploadFormData.append('employee_data', JSON.stringify(data));
                
        //         const response = await fetch(API_URL_FOR_CLIENT_STORE, {
        //             method: 'POST',
        //             headers: {
        //                 'Content-Type': 'application/json',
        //             },
        //             body: uploadFormData
        //         });
        
        //         const result = await response.json();
        //         console.log('API Response:', result); // For debugging
        
        //         if (result.success) {
        //             showMessage('Employee added successfully!', 'success');
        //             // Reset form after successful submission
        //             setTimeout(() => {
        //                 resetForm();
        //             }, 2000);
        //         } else {
        //             showMessage(result.message || 'Failed to add employee', 'error');
        //         }
        //     } catch (error) {
        //         console.error('Error:', error);
        //         showMessage('Network error: ' + error.message, 'error');
        //     } finally {
        //         // Reset button state
        //         submitBtn.innerHTML = originalText;
        //         submitBtn.disabled = false;
        //     }
        // });
        // create.php এর নিচের অংশে (লাইন ~501) এই ফাংশনটি আপডেট করুন:

// Form Submission
document.getElementById('employeeForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    e.stopPropagation(); // এই লাইনটি যোগ করুন
    
    console.log('Form submission started...'); // Debug log
    
    if (!validateForm()) {
        console.log('Form validation failed');
        return;
    }
    
    // File validation check -- not required on edit; existing files stay as they are.
    
    console.log('Number of files:', droppedFiles.length); // Debug log
    
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Updating...';
    submitBtn.disabled = true;
    
    // Collect form data
    const formData = new FormData(this);
    
    // Prepare data for API
    const data = {
        type: formData.get('type') || 'permanent',
        full_name: formData.get('full_name'),
        status: 'active',
        date_of_birth: formData.get('date_of_birth'),
        created_by: 'current_user',
        blood_group: formData.get('blood_group'),
        files_count: droppedFiles.length
    };
    
    // Get department values
    const departmentId = document.getElementById('selectedDepartmentId').value;
    const departmentName = document.getElementById('departmentInput').value;
    
    if (departmentName) {
        data.department = departmentName;
        if (departmentId) {
            data.department_id = departmentId;
        }
    }
    
    // Prepare company_related_info JSON
    data.company_related_info = {
        designation: formData.get('designation'),
        company_role: formData.get('company_role'),
        date_of_join: formData.get('date_of_join'),
        father_name: formData.get('father_name') || null,
        mother_name: formData.get('mother_name') || null,
        spouse_name: formData.get('spouse_name') || null,
        nid_no: formData.get('nid_no') || null,
        gross_salary: formData.get('gross_salary') || null,
        reporting_to_name: formData.get('reporting_to_name') || null,
        reporting_to_designation: formData.get('reporting_to_designation') || null
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
    
    // Prepare emergency contact information
    data.emergency_contact = {
        person: formData.get('emergency_contact_person') || '',
        relation: formData.get('relation') || '',
        phone: formData.get('emergency_phone') || '',
        address: {
            address_line_1: formData.get('emergency_address_line_1') || '',
            address_line_2: formData.get('emergency_address_line_2') || '',
            city: formData.get('emergency_city') || '',
            state: formData.get('emergency_state') || '',
            zip_code: formData.get('emergency_zip_code') || ''
        }
    };
    
    data.sys_id = EXISTING_SYS_ID;

    console.log('Data to send:', data); // Debug log

    try {
        console.log('Sending request to:', API_URL_FOR_UPDATE);

        const response = await fetch(API_URL_FOR_UPDATE, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });

        console.log('Response status:', response.status);
        
        const result = await response.json();
        console.log('API Response:', result);

        if (result.success) {
            showMessage('Employee updated successfully!', 'success');

            // Only upload a new photo if the user actually picked one --
            // otherwise leave the existing profile_photo untouched.
            const photoFile = document.getElementById('profilePhotoInput').files[0];
            if (photoFile) {
                const photoForm = new FormData();
                photoForm.append('sys_id', EXISTING_SYS_ID);
                photoForm.append('photo', photoFile);
                try {
                    await fetch(API_URL_FOR_PHOTO_UPLOAD, { method: 'POST', body: photoForm });
                } catch (e) {
                    console.error('Profile photo upload failed:', e);
                }
            }
        } else {
            showMessage(result.message || 'Failed to update employee', 'error');
        }
    } catch (error) {
        console.error('Fetch Error:', error);
        showMessage('Network error: ' + error.message, 'error');
    } finally {
        // Reset button state
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        console.log('Form submission process completed');
    }
});
        
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
                document.getElementById('selectedDepartmentId').value = '';
                
                // Reset blood group input
                document.getElementById('bloodGroupInput').value = '';
                document.getElementById('selectedBloodGroupValue').value = '';
                
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