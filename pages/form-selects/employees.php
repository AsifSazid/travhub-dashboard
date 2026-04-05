<?php
    $getAllEmployeesApi = $ip_port . "api/employees/all-employees.php";
?>
    <div id="employeeSearchContainer" class="relative w-full">
        <div class="flex">
            <input
                type="text"
                id="employeeInput"
                placeholder="Search for an employee..."
                class="flex-1 px-4 py-2 border border-gray-300 rounded-l-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 focus:outline-none transition-all duration-200"
                autocomplete="off">
        </div>
        <ul id="employeeDropdown" class="absolute w-full bg-white border border-gray-300 rounded-lg mt-1 max-h-60 overflow-auto shadow-lg hidden z-50"></ul>
    </div>
    
    <script>
        const GET_ALL_EMPLOYEE_API = "<?php echo $getAllEmployeesApi; ?>";
        
        // ভ্যারিয়েবল ডিক্লেয়ারেশন
        let employeesData = []; 
        let isTabKeyPressedEMP = false;
        let selectedEmployeeLi = null;

        const employeeInput = document.getElementById('employeeInput');
        const employeeDropdown = document.getElementById('employeeDropdown');
        const employeeSearchContainer = document.getElementById('employeeSearchContainer');
        
        function loadEmployees() {
            fetch(GET_ALL_EMPLOYEE_API)
                .then(res => res.json())
                .then(data => {
                    if (data.employees && Array.isArray(data.employees)) {
                        employeesData = data.employees;
                    } else {
                        console.error('Invalid employees data format:', data);
                        employeesData = [];
                    }
                })
                .catch(err => {
                    console.error('Error fetching employees:', err);
                    employeesData = [];
                });
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            loadEmployees();
            setupEmployeeSearch();
        });

        function setupEmployeeSearch() {
            if (!employeeInput || !employeeDropdown) return;

            setupOutsideClickHandler();

            // Tab key logic
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Tab') {
                    isTabKeyPressedEMP = true;
                    setTimeout(() => {
                        const activeElement = document.activeElement;
                        if (employeeSearchContainer && !employeeSearchContainer.contains(activeElement)) {
                            employeeDropdown.classList.add('hidden');
                        }
                        isTabKeyPressedEMP = false;
                    }, 10);
                }
            });

            // Input logic
            let employeeTypingTimer;
            employeeInput.addEventListener('input', () => {
                clearTimeout(employeeTypingTimer);
                employeeTypingTimer = setTimeout(() => {
                    const value = employeeInput.value.toLowerCase().trim();

                    if (value === '') {
                        renderDropdown(employeesData);
                        employeeDropdown.classList.remove('hidden');
                        return;
                    }

                    const filtered = employeesData.filter(employee => {
                        const employeeId = employee.sys_id ? employee.sys_id.toString() : '';
                        const employeeName = employee.name || '';
                        return employeeId.toLowerCase().includes(value) || employeeName.toLowerCase().includes(value);
                    });

                    renderDropdown(filtered);
                    employeeDropdown.classList.remove('hidden');
                }, 300);
            });

            // Focus করলে ড্রপডাউন দেখাবে
            employeeInput.addEventListener('focus', () => {
                if (employeesData.length > 0) {
                    renderDropdown(employeesData);
                    employeeDropdown.classList.remove('hidden');
                }
            });
        }

        function renderDropdown(list) {
            employeeDropdown.innerHTML = '';
        
            if (!list || list.length === 0) {
                employeeDropdown.innerHTML = `
                    <div class="px-4 py-3 text-center text-gray-500">
                        <p class="text-sm">No employees found</p>
                    </div>
                `;
                return;
            }
        
            list.forEach(employee => {
                let employeeName = '';
                let employeePhone = '';
                let employeeId = employee.sys_id || 'N/A';
        
                // Name Parsing Logic
                try {
                    if (employee.name) {
                        if (typeof employee.name === 'string' && employee.name.startsWith('{')) {
                            const nameObj = JSON.parse(employee.name);
                            employeeName = nameObj.primary || 'Unnamed Employee';
                        } else {
                            employeeName = employee.name.toString();
                        }
                    } else {
                        employeeName = 'Unnamed Employee';
                    }
        
                    // Phone Parsing Logic (এটি আগে মিস হয়েছিল)
                    if (employee.phone) {
                        if (typeof employee.phone === 'string' && employee.phone.startsWith('{')) {
                            const phoneObj = JSON.parse(employee.phone);
                            employeePhone = phoneObj.primary_no || '';
                        } else {
                            employeePhone = employee.phone.toString();
                        }
                    }
                } catch (error) {
                    console.error('Error parsing employee data:', error);
                    employeeName = 'Error parsing data';
                }
        
                const li = document.createElement('li');
                li.className = "px-4 py-3 cursor-pointer hover:bg-purple-50 border-b border-gray-100 last:border-b-0 transition-colors duration-150";
                
                li.innerHTML = `
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-gradient-to-r from-purple-500 to-purple-600 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                                ${employeeName.charAt(0).toUpperCase()}
                            </div>
                        </div>
                        <div class="ml-3 flex-1">
                            <div class="font-medium text-gray-900">${employeeName}</div>
                            <div class="text-xs text-gray-500 mt-1">
                                <div class="flex items-center">
                                    <span class="bg-purple-100 text-purple-800 px-2 py-0.5 rounded text-xs mr-2">
                                        ID: ${employeeId}
                                    </span>
                                    ${employeePhone ? `
                                        <span class="flex items-center text-gray-500">
                                            <i class="fas fa-phone-alt mr-1" style="font-size: 10px;"></i>
                                            ${employeePhone}
                                        </span>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
        
                li.addEventListener('click', (e) => {
                    e.stopPropagation();
                    employeeInput.value = `${employeeId} | ${employeeName}`;
                    employeeDropdown.classList.add('hidden');
                });
        
                employeeDropdown.appendChild(li);
            });
        }
        
        // আপডেট করা আউটসাইড ক্লিক হ্যান্ডলার
        function setupOutsideClickHandler() {
            document.addEventListener('click', function(e) {
                // চেক করছে ক্লিকটি কি কন্টেইনারের ভেতরে হয়েছে কি না
                if (employeeSearchContainer && !employeeSearchContainer.contains(e.target)) {
                    employeeDropdown.classList.add('hidden');
                }
            });
        }
    </script>