<?php
include_once('./authenticate.php');
$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898";
}

$employeeId = $_GET['employee_id'];

// $showEmployeeApi = $ip_port . "api/employees/show.php";
$getEmployeeApi = $ip_port . "api/employees/get-employee.php?employee_id=$employeeId";
$getEmployeeFinEntriesApi = $ip_port . "api/financial_entries/fin-entries.php?id=$employeeId";

$API_BASE = "https://dev.travhub.com.bd/";
$getEmployeeApi = $API_BASE . "api/employees/get-employee.php?employee_id=" . urlencode($employeeId);

// Server-side API call (for OG meta)
$response = @file_get_contents($getEmployeeApi);
if ($response) {
    $json = json_decode($response, true);
    if (!empty($json['success']) && !empty($json['employee'])) {
        $emp = $json['employee'];

        $employeeName = $emp['name'] ?? $employeeName;
        $department   = $emp['department_name'] ?? $department;

        $companyInfo  = json_decode($emp['company_related_info'] ?? '{}', true);
        $designation  = $companyInfo['designation'] ?? $designation;

        $imageData = json_decode($emp['image_name'] ?? '[]', true);
        if (!empty($imageData[0]['file_path'])) {
            $ogImage = $API_BASE . "uploads/" . $imageData[0]['file_path'];
        }
    }
}

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Portfolio - <?php echo htmlspecialchars($employeeId ?? 'Employee'); ?></title>
    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png" sizes="16x16">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Custom styles for portfolio */
        .portfolio-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 0.75rem;
            overflow: hidden;
        }
        
        .info-card {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        
        .info-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        .info-card.contact {
            border-left-color: #4299e1;
        }
        
        .info-card.company {
            border-left-color: #48bb78;
        }
        
        .info-card.personal {
            border-left-color: #ed8936;
        }
        
        .info-card.emergency {
            border-left-color: #f56565;
        }
        
        .profile-wrapper {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 5px solid white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            background-color: #f7fafc;
            overflow: hidden;          /* 🔑 must */
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .profile-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scale(0.9);     /* 👈 image zoom only */
            border-radius: 50%;        /* 🔑 add this */
        }
        
        .profile-image:hover {
            transform: scale(1);
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .status-active {
            background-color: #c6f6d5;
            color: #22543d;
        }
        
        .status-inactive {
            background-color: #fed7d7;
            color: #742a2a;
        }
        
        .detail-label {
            font-size: 0.75rem;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .detail-value {
            color: #2d3748;
            font-weight: 500;
            font-size: 0.95rem;
        }
        
        .section-title {
            color: #4a5568;
            font-weight: 700;
            font-size: 1.25rem;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .skill-tag {
            display: inline-block;
            background-color: #ebf4ff;
            color: #4c51bf;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .loading-spinner {
            border: 4px solid rgba(0, 0, 0, 0.1);
            border-radius: 50%;
            border-top: 4px solid #3498db;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 100px auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Tab Styles */
        .tab-button {
            padding: 12px 20px;
            font-weight: 500;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            white-space: nowrap;
        }

        .tab-button:hover {
            background-color: #f8fafc;
            border-color: #e2e8f0;
        }

        .tab-button.active {
            color: #3b82f6;
            border-color: #3b82f6;
            background-color: #eff6ff;
        }

        .tab-button.active::after {
            content: '';
            position: absolute;
            bottom: -3px;
            left: 0;
            width: 100%;
            height: 3px;
            background-color: #3b82f6;
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
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
    <main id="mainContent" class="pt-16 pl-0 lg:pl-64 lg:my-16 transition-all duration-300">
        <div class="p-6">
            <div class="bg-white rounded-lg shadow p-4 flex flex-col h-[400px] md:h-[calc(100vh-8rem)]">
                <!-- Header -->
                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                        <i class="fas fa-user-circle mr-2 text-purple-600"></i>
                        Employee's Profile
                    </h2>
                    <p class="text-sm text-gray-600">Manage traveler information, documents, and related data</p>
                </div>

                <!-- Tabs Navigation -->
                <div class="border-b border-gray-200 mb-6">
                    <div class="flex space-x-1 overflow-x-auto custom-scrollbar">
                        <button class="tab-button flex items-center" data-tab="details">
                            <i class="fa-solid fa-circle-info mr-2"></i>
                            Details & Information
                        </button>
                    
                        <button class="tab-button flex items-center" data-tab="documents">
                            <i class="fa-solid fa-file-lines mr-2"></i>
                            Documents
                        </button>
                    
                        <button class="tab-button flex items-center" data-tab="attendence">
                            <i class="fa-solid fa-calendar-check mr-2"></i>
                            Attendance & Leave Management
                        </button>
                    
                        <button class="tab-button flex items-center" data-tab="payroll">
                            <i class="fa-solid fa-money-check-dollar mr-2"></i>
                            Payroll
                        </button>
                    
                        <button class="tab-button flex items-center" data-tab="work-board">
                            <i class="fa-solid fa-chart-line mr-2"></i>
                            Performance
                        </button>
                    
                        <button class="tab-button flex items-center active" data-tab="accounting">
                            <i class="fa-solid fa-calculator mr-2"></i>
                            Accounting
                        </button>
                    
                        <button class="tab-button flex items-center" data-tab="notifications">
                            <i class="fas fa-bell mr-2"></i>
                            Notifications
                        </button>
                    
                        <button class="tab-button flex items-center" data-tab="credentials">
                            <i class="fa-solid fa-key mr-2"></i>
                            Credentials
                        </button>
                    
                        <button class="tab-button flex items-center" data-tab="explore">
                            <i class="fa-solid fa-compass mr-2"></i>
                            Explore All
                        </button>
                    </div>
                </div>
                
                <!-- Tab Content Area -->
                <div class="flex-1 overflow-y-auto">
                    <!-- Details Tab -->
                    <div id="details" class="tab-content">
                        <!-- Loading State -->
                        <div id="loadingContainer" class="bg-white rounded-lg shadow p-8 text-center">
                            <div class="loading-spinner"></div>
                            <p class="mt-4 text-gray-600">Loading employee information...</p>
                        </div>
                        
                        <?php include('./employees/se-details.php') ?> <!-- sc means show Employee -->
                    </div>

                    <!-- Documents Tab -->
                    <div id="documents" class="tab-content active">
                        <div class="grid grid-cols-2 gap-6 h-full">
                            <div class="col-span-2 justify-center h-full w-full">
                                <div class="text-center">

                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Attendence & Leave Management Tab -->
                    <div id="attendence" class="tab-content">
                        <div class="grid grid-cols-2 gap-6 h-full">
                            <div class="col-span-2 justify-center h-full w-full">
                                <div class="text-center">

                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payroll Tab -->
                    <div id="payroll" class="tab-content">
                        <div class="grid grid-cols-2 gap-6 h-full">
                            <div class="col-span-2 justify-center h-full w-full">
                                <div class="text-center">

                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Performance Tab -->
                    <div id="performance" class="tab-content">
                        <div class="grid grid-cols-2 gap-6 h-full">
                            <div class="col-span-2 justify-center h-full w-full">
                                <div class="text-center">

                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Accounting Tab -->
                    <div id="accounting" class="tab-content active">
                        <div class="grid grid-cols-2 gap-6 h-full">
                            <div class="col-span-2 justify-center h-full w-full">
                                <div class="text-center">
                                    <?php include('./employees/se-accounting.php') ?> <!-- sc means show Employee -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notifications Tab -->
                    <div id="notifications" class="tab-content">
                        <div class="h-full w-full">
                        </div>
                    </div>

                    <!-- Credentials Tab -->
                    <div id="credentials" class="tab-content">
                        <div class="h-full w-full">
                            <?php include('credentials.php') ?> <!-- sc means show Employee -->
                        </div>
                    </div>

                    <!-- Explore All Tab -->
                    <div id="explore" class="tab-content">
                        <div class="h-full w-full">
                            <?php include('./employees/se-explore.php') ?> <!-- sc means show Employee -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Floating Quick Access Tab -->
    <?php include '../elements/floating-menus.php'; ?>

    <script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>
    <script>
        const GET_EMPLOYEE_INFO_API = "<?php echo $getEmployeeApi; ?>";
        
        // Tab switching functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');

            // Function to switch tabs
            function switchTab(tabId) {
                // Remove active class from all tabs
                tabButtons.forEach(button => {
                    button.classList.remove('active');
                });

                tabContents.forEach(content => {
                    content.classList.remove('active');
                });

                // Add active class to clicked tab
                const activeButton = document.querySelector(`[data-tab="${tabId}"]`);
                const activeContent = document.getElementById(tabId);

                if (activeButton && activeContent) {
                    activeButton.classList.add('active');
                    activeContent.classList.add('active');
                }
            }

            // Add click event to tab buttons
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    switchTab(tabId);
                });
            });

            // Initialize first tab as active
            if (tabButtons.length > 0) {
                const firstTabId = tabButtons[5].getAttribute('data-tab');
                switchTab(firstTabId);
            }
        });
        
        document.addEventListener('DOMContentLoaded', function() {
            const portfolioContainer = document.getElementById('portfolioContainer');
            const loadingContainer = document.getElementById('loadingContainer');
            const errorContainer = document.getElementById('errorContainer');
            const errorMessage = document.getElementById('errorMessage');
            const retryButton = document.getElementById('retryButton');
            const printContent = document.getElementById('mainContent');
            
            // Function to parse JSON strings from the API response
            function parseJsonField(field) {
                if (!field) return null;
                try {
                    return JSON.parse(field);
                } catch (e) {
                    console.error('Error parsing JSON field:', e);
                    return null;
                }
            }
            
            // Function to format date
            function formatDate(dateString) {
                if (!dateString) return 'Not available';
                const date = new Date(dateString);
                return date.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
            }
            
            // Function to format phone number
            function formatPhoneNumber(phone) {
                if (!phone) return 'Not available';
                return phone.replace(/(\+\d{3})(\d{3})(\d{4})(\d{3})/, '$1 $2 $3 $4');
            }
            
            // Function to fetch and display employee data
            async function loadEmployeeData() {
                try {
                    // Show loading, hide other containers
                    loadingContainer.classList.remove('hidden');
                    portfolioContainer.classList.add('hidden');
                    errorContainer.classList.add('hidden');
                    
                    // Fetch employee data from API
                    const response = await fetch(GET_EMPLOYEE_INFO_API);
                    const data = await response.json();
                    
                    if (data.success && data.employee) {
                        const employee = data.employee;
                        
                        // Parse JSON fields
                        const emailData = parseJsonField(employee.email) || {};
                        const phoneData = parseJsonField(employee.phone) || {};
                        const addressData = parseJsonField(employee.address) || {};
                        const basicInfoData = parseJsonField(employee.basic_info) || {};
                        const emergencyContactData = parseJsonField(employee.emergency_contact) || {};
                        const companyInfoData = parseJsonField(employee.company_related_info) || {};
                        const metaData = parseJsonField(employee.meta_data) || {};
                        
                        // Set profile photo (if available)
                        const profilePhoto = document.getElementById('profilePhoto');
                        if (employee.image_name) {
                            try {
                                const imageData = JSON.parse(employee.image_name);
                        
                                if (Array.isArray(imageData) && imageData.length > 0 && imageData[0].file_path) {
                                    const imageUrl = `<?php echo $API_BASE; ?>uploads/${imageData[0].file_path}?time=<?php echo time(); ?>`;
                                    profilePhoto.src = imageUrl;
                                }
                            } catch (e) {
                                console.error('Error parsing image data:', e);
                            }
                        }
                        
                        // Employee status
                        const statusBadge = document.getElementById('statusBadge');
                        statusBadge.textContent = employee.status || 'Unknown';
                        statusBadge.className = `status-badge ${employee.status === 'active' ? 'status-active' : 'status-inactive'}`;
                        
                        // Header section
                        document.getElementById('employeeName').textContent = employee.name || 'Unknown';
                        document.getElementById('employeeId').textContent = employee.sys_id || 'EMP-0000000';
                        document.getElementById('employeeDepartment').textContent = employee.department_name || 'Unknown';
                        document.getElementById('employeeJoinDate').textContent = companyInfoData.date_of_join ? formatDate(companyInfoData.date_of_join) : 'Unknown';
                        
                        // Designation
                        const designation = companyInfoData.designation || 'Unknown';
                        document.getElementById('employeeDesignation').textContent = designation;
                        document.getElementById('companyDesignation').textContent = designation;
                        
                        // Contact links in header
                        const emailLink = document.getElementById('employeeEmail');
                        const primaryEmail = emailData.primary || '';
                        if (primaryEmail) {
                            emailLink.href = `mailto:${primaryEmail}`;
                            emailLink.innerHTML = `<i class="fas fa-envelope mr-2"></i><span>${primaryEmail}</span>`;
                        }
                        
                        const phoneLink = document.getElementById('employeePhone');
                        const primaryPhone = phoneData.primary_no || '';
                        if (primaryPhone) {
                            phoneLink.href = `tel:${primaryPhone}`;
                            phoneLink.innerHTML = `<i class="fas fa-phone mr-2"></i><span>${formatPhoneNumber(primaryPhone)}</span>`;
                        }
                        
                        const locationLink = document.getElementById('employeeLocation');
                        const city = addressData.city || '';
                        const country = addressData.country || '';
                        const locationText = city && country ? `${city}, ${country}` : (city || country || 'Unknown');
                        locationLink.innerHTML = `<i class="fas fa-map-marker-alt mr-2"></i><span>${locationText}</span>`;
                        
                        // About section
                        document.getElementById('sysId').textContent = employee.sys_id || 'EMP-0000000';
                        document.getElementById('dateOfBirth').textContent = basicInfoData.date_of_birth ? formatDate(basicInfoData.date_of_birth) : 'Not available';
                        document.getElementById('bloodGroup').textContent = basicInfoData.blood_group ? basicInfoData.blood_group.toUpperCase() : 'Not available';
                        document.getElementById('employeeType').textContent = employee.type ? employee.type.charAt(0).toUpperCase() + employee.type.slice(1) : 'Not available';
                        
                        // Company Information
                        document.getElementById('companyDepartment').textContent = employee.department_name || 'Not available';
                        document.getElementById('employmentType').textContent = companyInfoData.employment_type ? companyInfoData.employment_type.charAt(0).toUpperCase() + companyInfoData.employment_type.slice(1) : 'Not available';
                        document.getElementById('dateOfJoining').textContent = companyInfoData.date_of_join ? formatDate(companyInfoData.date_of_join) : 'Not available';
                        document.getElementById('companyRole').textContent = companyInfoData.company_role || 'Not available';
                        document.getElementById('companyStatus').textContent = companyInfoData.status ? companyInfoData.status.charAt(0).toUpperCase() + companyInfoData.status.slice(1) : 'Not available';
                        
                        // Contact Information
                        const contactEmail = document.getElementById('contactEmail');
                        if (primaryEmail) {
                            contactEmail.href = `mailto:${primaryEmail}`;
                            contactEmail.textContent = primaryEmail;
                        } else {
                            contactEmail.textContent = 'Not available';
                            contactEmail.removeAttribute('href');
                        }
                        
                        const contactPhone = document.getElementById('contactPhone');
                        if (primaryPhone) {
                            contactPhone.href = `tel:${primaryPhone}`;
                            contactPhone.textContent = formatPhoneNumber(primaryPhone);
                        } else {
                            contactPhone.textContent = 'Not available';
                            contactPhone.removeAttribute('href');
                        }
                        
                        // Address
                        const addressLines = [];
                        if (addressData.address_line_1) addressLines.push(addressData.address_line_1);
                        if (addressData.address_line_2) addressLines.push(addressData.address_line_2);
                        if (addressData.city) addressLines.push(addressData.city);
                        if (addressData.state) addressLines.push(addressData.state);
                        if (addressData.zip_code) addressLines.push(addressData.zip_code);
                        if (addressData.country) addressLines.push(addressData.country);
                        
                        document.getElementById('contactAddress').textContent = addressLines.length > 0 ? addressLines.join(', ') : 'Not available';
                        
                        // Emergency Contact
                        document.getElementById('emergencyPerson').textContent = emergencyContactData.person || 'Not available';
                        document.getElementById('emergencyRelation').textContent = emergencyContactData.relation || 'Not available';
                        
                        const emergencyPhone = document.getElementById('emergencyPhone');
                        if (emergencyContactData.phone) {
                            emergencyPhone.href = `tel:${emergencyContactData.phone}`;
                            emergencyPhone.textContent = formatPhoneNumber(emergencyContactData.phone);
                        } else {
                            emergencyPhone.textContent = 'Not available';
                            emergencyPhone.removeAttribute('href');
                        }
                        
                        // Meta data
                        if (metaData.created_by_date) {
                            document.getElementById('createdBy').textContent = metaData.created_by_date.user || 'System';
                            document.getElementById('createdDate').textContent = metaData.created_by_date.date || 'Unknown';
                            document.getElementById('activityDate').textContent = metaData.created_by_date.date || 'Unknown';
                        }
                        
                        // Skills section - you can customize this based on your data
                        const skillsContainer = document.getElementById('skillsContainer');
                        const defaultSkills = ['Software Development', 'Problem Solving', 'Team Collaboration', 'Project Management'];
                        
                        if (employee.department_name) {
                            skillsContainer.innerHTML = '';
                            // Add department-specific skills
                            let skills = [];
                            
                            if (employee.department_name === 'IT') {
                                skills = ['Software Development', 'System Analysis', 'Database Management', 'Web Technologies', 'Problem Solving'];
                            } else if (employee.department_name === 'HR') {
                                skills = ['Recruitment', 'Employee Relations', 'Training & Development', 'HR Policies', 'Communication'];
                            } else if (employee.department_name === 'Finance') {
                                skills = ['Financial Analysis', 'Budgeting', 'Accounting', 'Financial Reporting', 'Auditing'];
                            } else {
                                skills = defaultSkills;
                            }
                            
                            // Add designation-based skill
                            if (designation.toLowerCase().includes('manager')) {
                                skills.push('Leadership', 'Strategic Planning');
                            } else if (designation.toLowerCase().includes('developer')) {
                                skills.push('Coding', 'Software Architecture');
                            }
                            
                            skills.forEach(skill => {
                                const skillTag = document.createElement('div');
                                skillTag.className = 'skill-tag';
                                skillTag.textContent = skill;
                                skillsContainer.appendChild(skillTag);
                            });
                        }
                        
                        // Last updated
                        const lastUpdated = document.getElementById('lastUpdated');
                        if (metaData.updated_by_date && metaData.updated_by_date.length > 0) {
                            const latestUpdate = metaData.updated_by_date[metaData.updated_by_date.length - 1];
                            lastUpdated.textContent = latestUpdate.date || 'Today';
                        } else if (metaData.created_by_date) {
                            lastUpdated.textContent = metaData.created_by_date.date || 'Today';
                        }
                        
                        // Hide loading, show portfolio
                        loadingContainer.classList.add('hidden');
                        portfolioContainer.classList.remove('hidden');
                        
                    } else {
                        throw new Error('Invalid employee data received');
                    }
                } catch (error) {
                    console.error('Error loading employee data:', error);
                    loadingContainer.classList.add('hidden');
                    errorMessage.textContent = `Error: ${error.message}`;
                    errorContainer.classList.remove('hidden');
                }
            }
            
            // Load employee data on page load
            loadEmployeeData();
            
            // Retry button functionality
            retryButton.addEventListener('click', loadEmployeeData);
            
            // Print button functionality
            document.getElementById('printButton').addEventListener('click', function() {
                window.print();
            });
            
            // Edit button functionality (placeholder)
            document.getElementById('editButton').addEventListener('click', function() {
                alert('Edit functionality would open a form to update employee information.');
                // In a real application, this would redirect to an edit page or open a modal
            });
        });
    </script>
</body>

</html>