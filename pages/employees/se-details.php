     <!-- Portfolio Content (Initially hidden) -->
    <div id="portfolioContainer" class="hidden">
        <!-- Portfolio Header -->
        <div class="portfolio-header mb-6 p-8 relative">
            <div class="flex flex-col md:flex-row items-center md:items-start">
                <!-- Profile Image -->
                <div class="mb-6 md:mb-0 md:mr-8 relative">
                    <div class="profile-wrapper">
                        <img id="profilePhoto" src="default-avatar.png" alt="Profile" class="profile-image">
                    </div>
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