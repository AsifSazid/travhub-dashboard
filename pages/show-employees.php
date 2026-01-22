<?php
include_once('./authenticate.php');
$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898";
}

$employeeId = $_GET['employee_id'];


// $showEmployeeApi = $ip_port . "api/employees/show.php";
$getEmployeeApi = $ip_port . "api/employees/get-employee.php?employee_id=$employeeId";

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
        
        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 5px solid white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            object-fit: cover;
            background-color: #f7fafc;
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
    <main id="mainContent" class="pl-64 transition-all duration-300">
        <div class="p-6">
            <!-- Loading State -->
            <div id="loadingContainer" class="bg-white rounded-lg shadow p-8 text-center">
                <div class="loading-spinner"></div>
                <p class="mt-4 text-gray-600">Loading employee information...</p>
            </div>
            
            <!-- Portfolio Content (Initially hidden) -->
            <div id="portfolioContainer" class="hidden">
                <!-- Portfolio Header -->
                <div class="portfolio-header mb-6 p-8 relative">
                    <div class="flex flex-col md:flex-row items-center md:items-start">
                        <!-- Profile Image -->
                        <div class="mb-6 md:mb-0 md:mr-8 relative">
                            <img id="profilePhoto" src="../assets/images/placeholder-profile.png" alt="Profile" class="profile-image">
                            <div id="statusBadge" class="status-badge status-active mt-4"></div>
                        </div>
                        
                        <!-- Employee Info -->
                        <div class="flex-1 text-center md:text-left">
                            <h1 id="employeeName" class="text-3xl md:text-4xl font-bold mb-2">Employee Name</h1>
                            <h2 id="employeeDesignation" class="text-xl md:text-2xl text-blue-100 mb-3">Designation</h2>
                            <div class="flex flex-wrap justify-center md:justify-start gap-4 text-blue-100 mb-6">
                                <div class="flex items-center">
                                    <i class="fas fa-id-card mr-2"></i>
                                    <span id="employeeId">EMP-0000000</span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-building mr-2"></i>
                                    <span id="employeeDepartment">Department</span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-calendar-alt mr-2"></i>
                                    <span id="employeeJoinDate">Join Date</span>
                                </div>
                            </div>
                            
                            <!-- Contact Info -->
                            <div class="flex flex-wrap justify-center md:justify-start gap-4">
                                <a id="employeeEmail" href="#" class="flex items-center bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg transition">
                                    <i class="fas fa-envelope mr-2"></i>
                                    <span>Email</span>
                                </a>
                                <a id="employeePhone" href="#" class="flex items-center bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg transition">
                                    <i class="fas fa-phone mr-2"></i>
                                    <span>Phone</span>
                                </a>
                                <a id="employeeLocation" href="#" class="flex items-center bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg transition">
                                    <i class="fas fa-map-marker-alt mr-2"></i>
                                    <span>Location</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Main Content Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left Column -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- About / Bio Section -->
                        <div class="info-card personal p-6">
                            <h3 class="section-title">About</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <div class="detail-label">Employee ID</div>
                                    <div id="sysId" class="detail-value text-lg font-bold">EMP-0000000</div>
                                </div>
                                <div>
                                    <div class="detail-label">Date of Birth</div>
                                    <div id="dateOfBirth" class="detail-value">Not available</div>
                                </div>
                                <div>
                                    <div class="detail-label">Blood Group</div>
                                    <div id="bloodGroup" class="detail-value">Not available</div>
                                </div>
                                <div>
                                    <div class="detail-label">Employee Type</div>
                                    <div id="employeeType" class="detail-value">Not available</div>
                                </div>
                            </div>
                            <div class="mt-6">
                                <div class="detail-label">Additional Information</div>
                                <p id="additionalInfo" class="detail-value text-gray-600">No additional information available.</p>
                            </div>
                        </div>
                        
                        <!-- Company Information -->
                        <div class="info-card company p-6">
                            <h3 class="section-title">Company Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <div class="detail-label">Department</div>
                                    <div id="companyDepartment" class="detail-value text-lg font-semibold">Department</div>
                                </div>
                                <div>
                                    <div class="detail-label">Designation</div>
                                    <div id="companyDesignation" class="detail-value text-lg font-semibold">Designation</div>
                                </div>
                                <div>
                                    <div class="detail-label">Employment Type</div>
                                    <div id="employmentType" class="detail-value">Not available</div>
                                </div>
                                <div>
                                    <div class="detail-label">Date of Joining</div>
                                    <div id="dateOfJoining" class="detail-value">Not available</div>
                                </div>
                                <div>
                                    <div class="detail-label">Company Role</div>
                                    <div id="companyRole" class="detail-value">Not available</div>
                                </div>
                                <div>
                                    <div class="detail-label">Status</div>
                                    <div id="companyStatus" class="detail-value">Not available</div>
                                </div>
                            </div>
                            <div class="mt-6">
                                <div class="detail-label">Created Information</div>
                                <div class="flex items-center mt-2">
                                    <div class="bg-blue-50 p-3 rounded-lg">
                                        <i class="fas fa-user-circle text-blue-500 text-lg"></i>
                                    </div>
                                    <div class="ml-4">
                                        <div class="detail-value" id="createdBy">System Admin</div>
                                        <div class="text-sm text-gray-500" id="createdDate">21-01-2026 20:46</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Skills / Expertise Section -->
                        <div class="info-card p-6">
                            <h3 class="section-title">Skills & Expertise</h3>
                            <div id="skillsContainer">
                                <!-- Skills will be dynamically added here -->
                                <div class="text-center py-8 text-gray-500">
                                    <i class="fas fa-code text-4xl mb-3 text-gray-300"></i>
                                    <p>Skills information not available</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right Column -->
                    <div class="space-y-6">
                        <!-- Contact Information -->
                        <div class="info-card contact p-6">
                            <h3 class="section-title">Contact Information</h3>
                            <div class="space-y-4">
                                <div class="flex items-center">
                                    <div class="bg-blue-100 p-3 rounded-lg">
                                        <i class="fas fa-envelope text-blue-600"></i>
                                    </div>
                                    <div class="ml-4">
                                        <div class="detail-label">Email</div>
                                        <a id="contactEmail" href="#" class="detail-value hover:text-blue-600 transition">email@example.com</a>
                                    </div>
                                </div>
                                
                                <div class="flex items-center">
                                    <div class="bg-blue-100 p-3 rounded-lg">
                                        <i class="fas fa-phone text-blue-600"></i>
                                    </div>
                                    <div class="ml-4">
                                        <div class="detail-label">Phone</div>
                                        <a id="contactPhone" href="#" class="detail-value hover:text-blue-600 transition">+880 0000 000000</a>
                                    </div>
                                </div>
                                
                                <div class="flex items-center">
                                    <div class="bg-blue-100 p-3 rounded-lg">
                                        <i class="fas fa-map-marker-alt text-blue-600"></i>
                                    </div>
                                    <div class="ml-4">
                                        <div class="detail-label">Address</div>
                                        <div id="contactAddress" class="detail-value">Address not available</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Emergency Contact -->
                        <div class="info-card emergency p-6">
                            <h3 class="section-title">Emergency Contact</h3>
                            <div class="space-y-4">
                                <div class="flex items-center">
                                    <div class="bg-red-100 p-3 rounded-lg">
                                        <i class="fas fa-user text-red-600"></i>
                                    </div>
                                    <div class="ml-4">
                                        <div class="detail-label">Contact Person</div>
                                        <div id="emergencyPerson" class="detail-value">Not available</div>
                                    </div>
                                </div>
                                
                                <div class="flex items-center">
                                    <div class="bg-red-100 p-3 rounded-lg">
                                        <i class="fas fa-users text-red-600"></i>
                                    </div>
                                    <div class="ml-4">
                                        <div class="detail-label">Relationship</div>
                                        <div id="emergencyRelation" class="detail-value">Not available</div>
                                    </div>
                                </div>
                                
                                <div class="flex items-center">
                                    <div class="bg-red-100 p-3 rounded-lg">
                                        <i class="fas fa-phone text-red-600"></i>
                                    </div>
                                    <div class="ml-4">
                                        <div class="detail-label">Emergency Phone</div>
                                        <a id="emergencyPhone" href="#" class="detail-value hover:text-red-600 transition">Not available</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Recent Activity / Notes -->
                        <div class="info-card p-6">
                            <h3 class="section-title">Recent Activity</h3>
                            <div class="space-y-4">
                                <div class="flex items-start">
                                    <div class="bg-green-100 p-2 rounded-full mt-1">
                                        <i class="fas fa-user-plus text-green-600 text-sm"></i>
                                    </div>
                                    <div class="ml-4">
                                        <div class="detail-value">Employee Created</div>
                                        <div class="text-sm text-gray-500" id="activityDate">21-01-2026 20:46</div>
                                    </div>
                                </div>
                                
                                <div class="flex items-start">
                                    <div class="bg-purple-100 p-2 rounded-full mt-1">
                                        <i class="fas fa-id-card text-purple-600 text-sm"></i>
                                    </div>
                                    <div class="ml-4">
                                        <div class="detail-value">Profile Information Updated</div>
                                        <div class="text-sm text-gray-500">No recent updates</div>
                                    </div>
                                </div>
                                
                                <div class="text-center pt-4">
                                    <button class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                        <i class="fas fa-history mr-1"></i> View Full Activity Log
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Footer Action Buttons -->
                <div class="mt-8 flex justify-between items-center">
                    <div class="text-sm text-gray-500">
                        Last updated: <span id="lastUpdated">Today</span>
                    </div>
                    <div class="flex space-x-4">
                        <button id="printButton" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium transition">
                            <i class="fas fa-print mr-2"></i> Print Profile
                        </button>
                        <button id="editButton" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition">
                            <i class="fas fa-edit mr-2"></i> Edit Information
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Error State -->
            <div id="errorContainer" class="bg-white rounded-lg shadow p-8 text-center hidden">
                <div class="bg-red-100 p-4 rounded-full inline-block">
                    <i class="fas fa-exclamation-triangle text-red-600 text-3xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mt-4">Error Loading Employee Data</h3>
                <p id="errorMessage" class="text-gray-600 mt-2">Could not load employee information. Please try again.</p>
                <button id="retryButton" class="mt-6 px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition">
                    <i class="fas fa-redo mr-2"></i> Retry Loading
                </button>
            </div>
        </div>
    </main>

    <!-- Floating Quick Access Tab -->
    <?php include '../elements/floating-menus.php'; ?>

    <script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>
    <script>
        const GET_EMPLOYEE_INFO_API = "<?php echo $getEmployeeApi; ?>";
        
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
                                if (imageData.length > 0 && imageData[0].file_path) {
                                    // Construct full image URL - adjust based on your actual image path
                                    const imageUrl = `<?php echo $ip_port; ?>/${imageData[0].file_path}`;
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