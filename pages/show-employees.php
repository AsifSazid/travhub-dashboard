<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Profile - TravHub Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 30px;
            position: relative;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .employee-id {
            background-color: rgba(255, 255, 255, 0.15);
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-active {
            background-color: #2ecc71;
            color: white;
        }

        .profile-section {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .profile-photo-container {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            overflow: hidden;
            border: 5px solid rgba(255, 255, 255, 0.2);
            background-color: #f1f1f1;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .profile-photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-photo-placeholder {
            font-size: 60px;
            color: #bbb;
        }

        .profile-info h1 {
            font-size: 32px;
            margin-bottom: 8px;
        }

        .profile-info .designation {
            font-size: 18px;
            opacity: 0.9;
            margin-bottom: 5px;
        }

        .profile-info .department {
            font-size: 16px;
            opacity: 0.8;
            margin-bottom: 15px;
        }

        .contact-info {
            display: flex;
            gap: 20px;
            font-size: 14px;
        }

        .contact-info i {
            margin-right: 8px;
        }

        .content {
            padding: 30px;
        }

        .section-title {
            font-size: 20px;
            color: #1e3c72;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: #2a5298;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .info-card {
            background-color: #f9fafc;
            border-radius: 10px;
            padding: 20px;
            border-left: 4px solid #2a5298;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }

        .info-card:hover {
            transform: translateY(-5px);
        }

        .info-card h3 {
            color: #2a5298;
            margin-bottom: 15px;
            font-size: 18px;
        }

        .info-item {
            margin-bottom: 12px;
            display: flex;
            flex-wrap: wrap;
        }

        .info-label {
            font-weight: 600;
            min-width: 160px;
            color: #555;
            margin-bottom: 3px;
        }

        .info-value {
            color: #333;
            flex: 1;
        }

        .loading {
            text-align: center;
            padding: 40px;
            font-size: 18px;
            color: #666;
        }

        .loading i {
            font-size: 24px;
            margin-bottom: 15px;
            color: #2a5298;
        }

        .error {
            text-align: center;
            padding: 40px;
            color: #e74c3c;
            font-size: 18px;
        }

        .error i {
            font-size: 40px;
            margin-bottom: 15px;
        }

        .meta-info {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            font-size: 14px;
            color: #666;
            margin-top: 30px;
        }

        .meta-item {
            margin-bottom: 8px;
        }

        .btn-edit {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
            transition: background-color 0.3s;
        }

        .btn-edit:hover {
            background-color: #2980b9;
        }

        @media (max-width: 768px) {
            .profile-section {
                flex-direction: column;
                text-align: center;
            }
            
            .contact-info {
                flex-direction: column;
                gap: 10px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .header-top {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-top">
                <div class="employee-id" id="employee-id">Loading...</div>
                <div class="status-badge status-active" id="employee-status">Active</div>
            </div>
            
            <div class="profile-section">
                <div class="profile-photo-container">
                    <div class="profile-photo-placeholder" id="profile-photo-placeholder">
                        <i class="fas fa-user"></i>
                    </div>
                    <img id="profile-photo" class="profile-photo" alt="Profile Photo" style="display: none;">
                </div>
                
                <div class="profile-info">
                    <h1 id="employee-name">Loading...</h1>
                    <div class="designation" id="employee-designation">Software Developer</div>
                    <div class="department" id="employee-department">IT Department</div>
                    
                    <div class="contact-info">
                        <div><i class="fas fa-envelope"></i> <span id="employee-email">travhub.asif@gmail.com</span></div>
                        <div><i class="fas fa-phone"></i> <span id="employee-phone">+8801751906710</span></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="content">
            <div class="loading" id="loading">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Loading employee profile...</p>
            </div>
            
            <div class="error" id="error" style="display: none;">
                <i class="fas fa-exclamation-triangle"></i>
                <p>Error loading employee data. Please try again later.</p>
            </div>
            
            <div id="profile-content" style="display: none;">
                <div class="info-grid">
                    <div class="info-card">
                        <h3><i class="fas fa-user-circle"></i> Basic Information</h3>
                        <div class="info-item">
                            <span class="info-label">Date of Birth:</span>
                            <span class="info-value" id="dob">2000-03-08</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Blood Group:</span>
                            <span class="info-value" id="blood-group">B+</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Employee Type:</span>
                            <span class="info-value" id="employee-type">Permanent</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Date of Joining:</span>
                            <span class="info-value" id="joining-date">2025-10-21</span>
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <h3><i class="fas fa-home"></i> Address</h3>
                        <div class="info-item">
                            <span class="info-label">Address Line 1:</span>
                            <span class="info-value" id="address-line-1">492/C-44, Amaya Road, Borobari</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Address Line 2:</span>
                            <span class="info-value" id="address-line-2">Kanchkura, Uttarkhan</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">City:</span>
                            <span class="info-value" id="city">Dhaka</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">ZIP Code:</span>
                            <span class="info-value" id="zip-code">1230</span>
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <h3><i class="fas fa-users"></i> Emergency Contact</h3>
                        <div class="info-item">
                            <span class="info-label">Contact Person:</span>
                            <span class="info-value" id="emergency-person">Hazi Md. Rofiqul Islam</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Relationship:</span>
                            <span class="info-value" id="emergency-relation">Father</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Phone:</span>
                            <span class="info-value" id="emergency-phone">01714445709</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Emergency Address:</span>
                            <span class="info-value" id="emergency-address">Same as employee address</span>
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <h3><i class="fas fa-building"></i> Company Information</h3>
                        <div class="info-item">
                            <span class="info-label">Department:</span>
                            <span class="info-value" id="company-department">IT</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Designation:</span>
                            <span class="info-value" id="company-designation">Software Developer</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Employment Type:</span>
                            <span class="info-value" id="company-employment-type">Permanent</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Status:</span>
                            <span class="info-value" id="company-status">Active</span>
                        </div>
                    </div>
                </div>
                
                <div class="meta-info">
                    <h3><i class="fas fa-history"></i> Record Information</h3>
                    <div class="info-item">
                        <span class="info-label">Created By:</span>
                        <span class="info-value" id="created-by">system_admin</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Created Date:</span>
                        <span class="info-value" id="created-date">21-01-2026 20:46</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Last Updated:</span>
                        <span class="info-value" id="last-updated">Not updated yet</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Employee ID:</span>
                        <span class="info-value" id="full-employee-id">EMP-5102501</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">UUID:</span>
                        <span class="info-value" id="employee-uuid">874aa904-3618-498b-93cb-28b8b66c7497</span>
                    </div>
                </div>
                
                <button class="btn-edit" id="edit-profile">
                    <i class="fas fa-edit"></i> Edit Profile
                </button>
            </div>
        </div>
    </div>

    <script>
        // API endpoint - replace with your actual API endpoint
        const API_URL = 'https://api.example.com/employees/EMP-5102501';
        // For demo purposes, we'll use the data from your SQL file directly
        const DEMO_DATA = {
            "id": 2,
            "uuid": "874aa904-3618-498b-93cb-28b8b66c7497",
            "sys_id": "EMP-5102501",
            "type": "permanent",
            "name": "Asif M Sazid",
            "email": {"primary": "travhub.asif@gmail.com"},
            "phone": {"primary_no": "+8801751906710"},
            "address": {
                "address_line_1": "492/C-44, Amaya Road, Borobari",
                "address_line_2": "Kanchkura, Uttarkhan",
                "city": "Dhaka",
                "state": "Bangladesh",
                "zip_code": "1230",
                "country": ""
            },
            "basic_info": {
                "date_of_birth": "2000-03-08",
                "blood_group": "b+"
            },
            "emergency_contact": {
                "person": "Hazi Md. Rofiqul Islam",
                "relation": "Father",
                "phone": "01714445709",
                "address": {
                    "address_line_1": "492/C-44, Amaya Road, Borobari",
                    "address_line_2": "Kanchkura, Uttarkhan",
                    "city": "Dhaka",
                    "state": "Bangladesh",
                    "zip_code": "1230"
                }
            },
            "contact_n_communication_details": null,
            "department_id": 5,
            "department_name": "IT",
            "company_related_info": {
                "designation": "Software Developer",
                "company_role": "NA",
                "date_of_join": "2025-10-21",
                "employment_type": "permanent",
                "status": "active",
                "created_at": "2026-01-21 20:46:14",
                "created_by": "system_admin",
                "created_by_id": 0,
                "department": "IT",
                "department_id": "5"
            },
            "previous_job_details": null,
            "status": "active",
            "emp_path": null,
            "image_name": [{
                "original_name": "Cover_Pic-removebg-preview.png",
                "stored_name": "6970e6b6ebf54_20260121_204614.png",
                "file_type": "image/png",
                "file_size": 136881,
                "file_path": "employees/EMP-5102501_AsifMSazid/6970e6b6ebf54_20260121_204614.png",
                "upload_date": "2026-01-21 20:46:14"
            }],
            "profile_photo": null,
            "meta_data": {
                "created_by_date": {
                    "user": "system_admin",
                    "date": "21-01-2026 20:46"
                },
                "updated_by_date": []
            }
        };

        // Function to fetch employee data from API
        async function fetchEmployeeData() {
            try {
                // In a real application, you would fetch from your API
                // const response = await fetch(API_URL);
                // if (!response.ok) throw new Error('Failed to fetch employee data');
                // const data = await response.json();
                
                // For demo purposes, we'll use the demo data with a simulated delay
                return new Promise(resolve => {
                    setTimeout(() => {
                        resolve(DEMO_DATA);
                    }, 1000);
                });
            } catch (error) {
                console.error('Error fetching employee data:', error);
                throw error;
            }
        }

        // Function to display employee data
        function displayEmployeeData(employee) {
            // Hide loading, show content
            document.getElementById('loading').style.display = 'none';
            document.getElementById('profile-content').style.display = 'block';
            
            // Set basic profile information
            document.getElementById('employee-id').textContent = employee.sys_id;
            document.getElementById('employee-status').textContent = employee.status;
            document.getElementById('employee-name').textContent = employee.name;
            document.getElementById('employee-designation').textContent = employee.company_related_info.designation;
            document.getElementById('employee-department').textContent = `${employee.department_name} Department`;
            document.getElementById('employee-email').textContent = employee.email.primary;
            document.getElementById('employee-phone').textContent = employee.phone.primary_no;
            
            // Set basic information
            document.getElementById('dob').textContent = formatDate(employee.basic_info.date_of_birth);
            document.getElementById('blood-group').textContent = employee.basic_info.blood_group.toUpperCase();
            document.getElementById('employee-type').textContent = employee.type.charAt(0).toUpperCase() + employee.type.slice(1);
            document.getElementById('joining-date').textContent = formatDate(employee.company_related_info.date_of_join);
            
            // Set address information
            document.getElementById('address-line-1').textContent = employee.address.address_line_1;
            document.getElementById('address-line-2').textContent = employee.address.address_line_2;
            document.getElementById('city').textContent = employee.address.city;
            document.getElementById('zip-code').textContent = employee.address.zip_code;
            
            // Set emergency contact information
            document.getElementById('emergency-person').textContent = employee.emergency_contact.person;
            document.getElementById('emergency-relation').textContent = employee.emergency_contact.relation;
            document.getElementById('emergency-phone').textContent = employee.emergency_contact.phone;
            document.getElementById('emergency-address').textContent = "Same as employee address";
            
            // Set company information
            document.getElementById('company-department').textContent = employee.department_name;
            document.getElementById('company-designation').textContent = employee.company_related_info.designation;
            document.getElementById('company-employment-type').textContent = employee.company_related_info.employment_type.charAt(0).toUpperCase() + employee.company_related_info.employment_type.slice(1);
            document.getElementById('company-status').textContent = employee.company_related_info.status.charAt(0).toUpperCase() + employee.company_related_info.status.slice(1);
            
            // Set meta information
            document.getElementById('created-by').textContent = employee.meta_data.created_by_date.user;
            document.getElementById('created-date').textContent = employee.meta_data.created_by_date.date;
            document.getElementById('last-updated').textContent = employee.meta_data.updated_by_date.length > 0 ? "Recently updated" : "Not updated yet";
            document.getElementById('full-employee-id').textContent = employee.sys_id;
            document.getElementById('employee-uuid').textContent = employee.uuid;
            
            // Check if profile photo is available
            if (employee.image_name && employee.image_name.length > 0) {
                // In a real application, you would use the actual image URL
                // document.getElementById('profile-photo').src = employee.image_name[0].file_path;
                // document.getElementById('profile-photo').style.display = 'block';
                // document.getElementById('profile-photo-placeholder').style.display = 'none';
                
                // For demo, we'll just show the placeholder
                document.getElementById('profile-photo-placeholder').innerHTML = '<i class="fas fa-user"></i>';
            }
            
            // Update status badge color based on status
            const statusBadge = document.getElementById('employee-status');
            if (employee.status === 'active') {
                statusBadge.className = 'status-badge status-active';
            } else {
                statusBadge.className = 'status-badge status-inactive';
                statusBadge.textContent = 'Inactive';
                statusBadge.style.backgroundColor = '#e74c3c';
            }
        }

        // Helper function to format date
        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
        }

        // Initialize the page
        async function init() {
            try {
                const employeeData = await fetchEmployeeData();
                displayEmployeeData(employeeData);
            } catch (error) {
                document.getElementById('loading').style.display = 'none';
                document.getElementById('error').style.display = 'block';
                console.error('Failed to load employee data:', error);
            }
        }

        // Add event listener for edit button
        document.getElementById('edit-profile').addEventListener('click', function() {
            alert('Edit functionality would open a form to update employee details. This feature would be implemented in a real application.');
        });

        // Initialize the page when DOM is loaded
        document.addEventListener('DOMContentLoaded', init);
    </script>
</body>
</html>