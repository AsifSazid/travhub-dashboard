<?php
    $getAllEmployeesApi = $ip_port . "api/employees/all-employees.php";
?>
    <div id="secondEmployeeSearchContainer" class="relative w-full">
        <div class="flex">
            <input
                type="text"
                id="secondEmployeeInput"
                placeholder="Search for an Employee..."
                class="flex-1 px-4 py-2 border border-gray-300 rounded-l-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 focus:outline-none transition-all duration-200"
                autocomplete="off">
        </div>
        <ul id="secondEmployeeDropdown" class="absolute w-full bg-white border border-gray-300 rounded-lg mt-1 max-h-60 overflow-auto shadow-lg hidden z-50"></ul>
    </div>
    
    <script>
        const GET_SECOND_EMPLOYEE_API = "<?php echo $getAllEmployeesApi; ?>";
        
        // ভ্যারিয়েবল ডিক্লেয়ারেশন
        let secondEmployeesData = []; 
        let isTabKeyPressedSecEMP = false;
        let selectedSecondEmployeeLi = null;

        const secondEmployeeInput = document.getElementById('secondEmployeeInput');
        const secondEmployeeDropdown = document.getElementById('secondEmployeeDropdown');
        const secondEmployeeSearchContainer = document.getElementById('secondEmployeeSearchContainer');
        
        function loadSecondEmployees() {
            fetch(GET_SECOND_EMPLOYEE_API)
                .then(res => res.json())
                .then(data => {
                    if (data.employees && Array.isArray(data.employees)) {
                        secondEmployeesData = data.employees;
                    } else {
                        console.error('Invalid secondEmployees data format:', data);
                        secondEmployeesData = [];
                    }
                })
                .catch(err => {
                    console.error('Error fetching secondEmployees:', err);
                    secondEmployeesData = [];
                });
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            loadSecondEmployees();
            setupSecondEmployeeSearch();
        });

        function setupSecondEmployeeSearch() {
            if (!secondEmployeeInput || !secondEmployeeDropdown) return;

            setupOutsideClickHandler();

            // Tab key logic
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Tab') {
                    isTabKeyPressedSecEMP = true;
                    setTimeout(() => {
                        const activeElement = document.activeElement;
                        if (secondEmployeeSearchContainer && !secondEmployeeSearchContainer.contains(activeElement)) {
                            secondEmployeeDropdown.classList.add('hidden');
                        }
                        isTabKeyPressedSecEMP = false;
                    }, 10);
                }
            });

            // Input logic
            let secondEmployeeTypingTimer;
            secondEmployeeInput.addEventListener('input', () => {
                clearTimeout(secondEmployeeTypingTimer);
                secondEmployeeTypingTimer = setTimeout(() => {
                    const value = secondEmployeeInput.value.toLowerCase().trim();

                    if (value === '') {
                        renderDropdown(secondEmployeesData);
                        secondEmployeeDropdown.classList.remove('hidden');
                        return;
                    }

                    const filtered = secondEmployeesData.filter(secondEmployee => {
                        const secondEmployeeId = secondEmployee.sys_id ? secondEmployee.sys_id.toString() : '';
                        const secondEmployeeName = secondEmployee.name || '';
                        return secondEmployeeId.toLowerCase().includes(value) || secondEmployeeName.toLowerCase().includes(value);
                    });

                    renderDropdown(filtered);
                    secondEmployeeDropdown.classList.remove('hidden');
                }, 300);
            });

            // Focus করলে ড্রপডাউন দেখাবে
            secondEmployeeInput.addEventListener('focus', () => {
                if (secondEmployeesData.length > 0) {
                    renderDropdown(secondEmployeesData);
                    secondEmployeeDropdown.classList.remove('hidden');
                }
            });
        }

        function renderDropdown(list) {
            secondEmployeeDropdown.innerHTML = '';
        
            if (!list || list.length === 0) {
                secondEmployeeDropdown.innerHTML = `
                    <div class="px-4 py-3 text-center text-gray-500">
                        <p class="text-sm">No secondEmployees found</p>
                    </div>
                `;
                return;
            }
        
            list.forEach(secondEmployee => {
                let secondEmployeeName = '';
                let secondEmployeePhone = '';
                let secondEmployeeId = secondEmployee.sys_id || 'N/A';
        
                // Name Parsing Logic
                try {
                    if (secondEmployee.name) {
                        if (typeof secondEmployee.name === 'string' && secondEmployee.name.startsWith('{')) {
                            const nameObj = JSON.parse(secondEmployee.name);
                            secondEmployeeName = nameObj.primary || 'Unnamed Employee';
                        } else {
                            secondEmployeeName = secondEmployee.name.toString();
                        }
                    } else {
                        secondEmployeeName = 'Unnamed Employee';
                    }
        
                    // Phone Parsing Logic (এটি আগে মিস হয়েছিল)
                    if (secondEmployee.phone) {
                        if (typeof secondEmployee.phone === 'string' && secondEmployee.phone.startsWith('{')) {
                            const phoneObj = JSON.parse(secondEmployee.phone);
                            secondEmployeePhone = phoneObj.primary_no || '';
                        } else {
                            secondEmployeePhone = secondEmployee.phone.toString();
                        }
                    }
                } catch (error) {
                    console.error('Error parsing secondEmployee data:', error);
                    secondEmployeeName = 'Error parsing data';
                }
        
                const li = document.createElement('li');
                li.className = "px-4 py-3 cursor-pointer hover:bg-purple-50 border-b border-gray-100 last:border-b-0 transition-colors duration-150";
                
                li.innerHTML = `
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-gradient-to-r from-purple-500 to-purple-600 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                                ${secondEmployeeName.charAt(0).toUpperCase()}
                            </div>
                        </div>
                        <div class="ml-3 flex-1">
                            <div class="font-medium text-gray-900">${secondEmployeeName}</div>
                            <div class="text-xs text-gray-500 mt-1">
                                <div class="flex items-center">
                                    <span class="bg-purple-100 text-purple-800 px-2 py-0.5 rounded text-xs mr-2">
                                        ID: ${secondEmployeeId}
                                    </span>
                                    ${secondEmployeePhone ? `
                                        <span class="flex items-center text-gray-500">
                                            <i class="fas fa-phone-alt mr-1" style="font-size: 10px;"></i>
                                            ${secondEmployeePhone}
                                        </span>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
        
                li.addEventListener('click', (e) => {
                    e.stopPropagation();
                    secondEmployeeInput.value = `${secondEmployeeId} | ${secondEmployeeName}`;
                    secondEmployeeDropdown.classList.add('hidden');
                });
        
                secondEmployeeDropdown.appendChild(li);
            });
        }
        
        // আপডেট করা আউটসাইড ক্লিক হ্যান্ডলার
        function setupOutsideClickHandler() {
            document.addEventListener('click', function(e) {
                // চেক করছে ক্লিকটি কি কন্টেইনারের ভেতরে হয়েছে কি না
                if (secondEmployeeSearchContainer && !secondEmployeeSearchContainer.contains(e.target)) {
                    secondEmployeeDropdown.classList.add('hidden');
                }
            });
        }
    </script>