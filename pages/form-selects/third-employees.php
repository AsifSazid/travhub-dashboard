<?php
    $getAllEmployeesApi = $ip_port . "api/employees/all-employees.php";
?>
    <div id="thirdEmployeeSearchContainer" class="relative w-full">
        <div class="flex">
            <input
                type="text"
                id="thirdEmployeeInput"
                placeholder="Search for an Employee..."
                class="flex-1 px-4 py-2 border border-gray-300 rounded-l-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 focus:outline-none transition-all duration-200"
                autocomplete="off">
        </div>
        <ul id="thirdEmployeeDropdown" class="absolute w-full bg-white border border-gray-300 rounded-lg mt-1 max-h-60 overflow-auto shadow-lg hidden z-50"></ul>
    </div>
    
    <script>
        const GET_THIRD_EMPLOYEE_API = "<?php echo $getAllEmployeesApi; ?>";
        
        // ভ্যারিয়েবল ডিক্লেয়ারেশন
        let thirdEmployeesData = []; 
        let isTabKeyPressedThrEMP = false;
        let selectedThirdEmployeeLi = null;

        const thirdEmployeeInput = document.getElementById('thirdEmployeeInput');
        const thirdEmployeeDropdown = document.getElementById('thirdEmployeeDropdown');
        const thirdEmployeeSearchContainer = document.getElementById('thirdEmployeeSearchContainer');
        
        function loadThirdEmployees() {
            fetch(GET_THIRD_EMPLOYEE_API)
                .then(res => res.json())
                .then(data => {
                    if (data.employees && Array.isArray(data.employees)) {
                        thirdEmployeesData = data.employees;
                    } else {
                        console.error('Invalid thirdEmployees data format:', data);
                        thirdEmployeesData = [];
                    }
                })
                .catch(err => {
                    console.error('Error fetching thirdEmployees:', err);
                    thirdEmployeesData = [];
                });
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            loadThirdEmployees();
            setupThirdEmployeeSearch();
        });

        function setupThirdEmployeeSearch() {
            if (!thirdEmployeeInput || !thirdEmployeeDropdown) return;

            setupOutsideClickHandler();

            // Tab key logic
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Tab') {
                    isTabKeyPressedThrEMP = true;
                    setTimeout(() => {
                        const activeElement = document.activeElement;
                        if (thirdEmployeeSearchContainer && !thirdEmployeeSearchContainer.contains(activeElement)) {
                            thirdEmployeeDropdown.classList.add('hidden');
                        }
                        isTabKeyPressedThrEMP = false;
                    }, 10);
                }
            });

            // Input logic
            let thirdEmployeeTypingTimer;
            thirdEmployeeInput.addEventListener('input', () => {
                clearTimeout(thirdEmployeeTypingTimer);
                thirdEmployeeTypingTimer = setTimeout(() => {
                    const value = thirdEmployeeInput.value.toLowerCase().trim();

                    if (value === '') {
                        renderDropdown(thirdEmployeesData);
                        thirdEmployeeDropdown.classList.remove('hidden');
                        return;
                    }

                    const filtered = thirdEmployeesData.filter(thirdEmployee => {
                        const thirdEmployeeId = thirdEmployee.sys_id ? thirdEmployee.sys_id.toString() : '';
                        const thirdEmployeeName = thirdEmployee.name || '';
                        return thirdEmployeeId.toLowerCase().includes(value) || thirdEmployeeName.toLowerCase().includes(value);
                    });

                    renderDropdown(filtered);
                    thirdEmployeeDropdown.classList.remove('hidden');
                }, 300);
            });

            // Focus করলে ড্রপডাউন দেখাবে
            thirdEmployeeInput.addEventListener('focus', () => {
                if (thirdEmployeesData.length > 0) {
                    renderDropdown(thirdEmployeesData);
                    thirdEmployeeDropdown.classList.remove('hidden');
                }
            });
        }

        function renderDropdown(list) {
            thirdEmployeeDropdown.innerHTML = '';
        
            if (!list || list.length === 0) {
                thirdEmployeeDropdown.innerHTML = `
                    <div class="px-4 py-3 text-center text-gray-500">
                        <p class="text-sm">No thirdEmployees found</p>
                    </div>
                `;
                return;
            }
        
            list.forEach(thirdEmployee => {
                let thirdEmployeeName = '';
                let thirdEmployeePhone = '';
                let thirdEmployeeId = thirdEmployee.sys_id || 'N/A';
        
                // Name Parsing Logic
                try {
                    if (thirdEmployee.name) {
                        if (typeof thirdEmployee.name === 'string' && thirdEmployee.name.startsWith('{')) {
                            const nameObj = JSON.parse(thirdEmployee.name);
                            thirdEmployeeName = nameObj.primary || 'Unnamed Employee';
                        } else {
                            thirdEmployeeName = thirdEmployee.name.toString();
                        }
                    } else {
                        thirdEmployeeName = 'Unnamed Employee';
                    }
        
                    // Phone Parsing Logic (এটি আগে মিস হয়েছিল)
                    if (thirdEmployee.phone) {
                        if (typeof thirdEmployee.phone === 'string' && thirdEmployee.phone.startsWith('{')) {
                            const phoneObj = JSON.parse(thirdEmployee.phone);
                            thirdEmployeePhone = phoneObj.primary_no || '';
                        } else {
                            thirdEmployeePhone = thirdEmployee.phone.toString();
                        }
                    }
                } catch (error) {
                    console.error('Error parsing thirdEmployee data:', error);
                    thirdEmployeeName = 'Error parsing data';
                }
        
                const li = document.createElement('li');
                li.className = "px-4 py-3 cursor-pointer hover:bg-purple-50 border-b border-gray-100 last:border-b-0 transition-colors duration-150";
                
                li.innerHTML = `
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-gradient-to-r from-purple-500 to-purple-600 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                                ${thirdEmployeeName.charAt(0).toUpperCase()}
                            </div>
                        </div>
                        <div class="ml-3 flex-1">
                            <div class="font-medium text-gray-900">${thirdEmployeeName}</div>
                            <div class="text-xs text-gray-500 mt-1">
                                <div class="flex items-center">
                                    <span class="bg-purple-100 text-purple-800 px-2 py-0.5 rounded text-xs mr-2">
                                        ID: ${thirdEmployeeId}
                                    </span>
                                    ${thirdEmployeePhone ? `
                                        <span class="flex items-center text-gray-500">
                                            <i class="fas fa-phone-alt mr-1" style="font-size: 10px;"></i>
                                            ${thirdEmployeePhone}
                                        </span>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
        
                li.addEventListener('click', (e) => {
                    e.stopPropagation();
                    thirdEmployeeInput.value = `${thirdEmployeeId} | ${thirdEmployeeName}`;
                    thirdEmployeeDropdown.classList.add('hidden');
                });
        
                thirdEmployeeDropdown.appendChild(li);
            });
        }
        
        // আপডেট করা আউটসাইড ক্লিক হ্যান্ডলার
        function setupOutsideClickHandler() {
            document.addEventListener('click', function(e) {
                // চেক করছে ক্লিকটি কি কন্টেইনারের ভেতরে হয়েছে কি না
                if (thirdEmployeeSearchContainer && !thirdEmployeeSearchContainer.contains(e.target)) {
                    thirdEmployeeDropdown.classList.add('hidden');
                }
            });
        }
    </script>