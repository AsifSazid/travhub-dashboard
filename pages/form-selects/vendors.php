<?php
    $getAllVendorsApi = $ip_port . "api/vendors/all-vendors.php";
?>
    <div id="vendorSearchContainer" class="relative w-full">
        <div class="flex">
            <input
                type="text"
                id="vendorInput"
                placeholder="Search for a vendor..."
                class="flex-1 px-4 py-2 border border-gray-300 rounded-l-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 focus:outline-none transition-all duration-200"
                autocomplete="off">
        </div>
        <ul id="vendorDropdown" class="absolute w-full bg-white border border-gray-300 rounded-lg mt-1 max-h-60 overflow-auto shadow-lg hidden z-50"></ul>
    </div>
    
    <script>
        const GET_ALL_VENDOR_API = "<?php echo $getAllVendorsApi; ?>";
        
        // ভ্যারিয়েবল ডিক্লেয়ারেশন
        let vendorsData = []; 
        let isTabKeyPressed = false;
        let selectedVendorLi = null;

        const vendorInput = document.getElementById('vendorInput');
        const vendorDropdown = document.getElementById('vendorDropdown');
        const vendorSearchContainer = document.getElementById('vendorSearchContainer');
        
        function loadVendors() {
            fetch(GET_ALL_VENDOR_API)
                .then(res => res.json())
                .then(data => {
                    if (data.vendors && Array.isArray(data.vendors)) {
                        vendorsData = data.vendors;
                    } else {
                        console.error('Invalid vendors data format:', data);
                        vendorsData = [];
                    }
                })
                .catch(err => {
                    console.error('Error fetching vendors:', err);
                    vendorsData = [];
                });
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            loadVendors();
            setupVendorSearch();
        });

        function setupVendorSearch() {
            if (!vendorInput || !vendorDropdown) return;

            setupOutsideClickHandler();

            // Tab key logic
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Tab') {
                    isTabKeyPressed = true;
                    setTimeout(() => {
                        const activeElement = document.activeElement;
                        if (vendorSearchContainer && !vendorSearchContainer.contains(activeElement)) {
                            vendorDropdown.classList.add('hidden');
                        }
                        isTabKeyPressed = false;
                    }, 10);
                }
            });

            // Input logic
            let typingTimer;
            vendorInput.addEventListener('input', () => {
                clearTimeout(typingTimer);
                typingTimer = setTimeout(() => {
                    const value = vendorInput.value.toLowerCase().trim();

                    if (value === '') {
                        renderDropdown(vendorsData);
                        vendorDropdown.classList.remove('hidden');
                        return;
                    }

                    const filtered = vendorsData.filter(vendor => {
                        const vendorId = vendor.sys_id ? vendor.sys_id.toString() : '';
                        const vendorName = vendor.name || '';
                        return vendorId.toLowerCase().includes(value) || vendorName.toLowerCase().includes(value);
                    });

                    renderDropdown(filtered);
                    vendorDropdown.classList.remove('hidden');
                }, 300);
            });

            // Focus করলে ড্রপডাউন দেখাবে
            vendorInput.addEventListener('focus', () => {
                if (vendorsData.length > 0) {
                    renderDropdown(vendorsData);
                    vendorDropdown.classList.remove('hidden');
                }
            });
        }

        function renderDropdown(list) {
            vendorDropdown.innerHTML = '';
        
            if (!list || list.length === 0) {
                vendorDropdown.innerHTML = `
                    <div class="px-4 py-3 text-center text-gray-500">
                        <p class="text-sm">No vendors found</p>
                    </div>
                `;
                return;
            }
        
            list.forEach(vendor => {
                let vendorName = '';
                let vendorPhone = '';
                let vendorId = vendor.sys_id || 'N/A';
        
                // Name Parsing Logic
                try {
                    if (vendor.name) {
                        if (typeof vendor.name === 'string' && vendor.name.startsWith('{')) {
                            const nameObj = JSON.parse(vendor.name);
                            vendorName = nameObj.primary || 'Unnamed Vendor';
                        } else {
                            vendorName = vendor.name.toString();
                        }
                    } else {
                        vendorName = 'Unnamed Vendor';
                    }
        
                    // Phone Parsing Logic (এটি আগে মিস হয়েছিল)
                    if (vendor.phone) {
                        if (typeof vendor.phone === 'string' && vendor.phone.startsWith('{')) {
                            const phoneObj = JSON.parse(vendor.phone);
                            vendorPhone = phoneObj.primary_no || '';
                        } else {
                            vendorPhone = vendor.phone.toString();
                        }
                    }
                } catch (error) {
                    console.error('Error parsing vendor data:', error);
                    vendorName = 'Error parsing data';
                }
        
                const li = document.createElement('li');
                li.className = "px-4 py-3 cursor-pointer hover:bg-purple-50 border-b border-gray-100 last:border-b-0 transition-colors duration-150";
                
                li.innerHTML = `
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-gradient-to-r from-purple-500 to-purple-600 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                                ${vendorName.charAt(0).toUpperCase()}
                            </div>
                        </div>
                        <div class="ml-3 flex-1">
                            <div class="font-medium text-gray-900">${vendorName}</div>
                            <div class="text-xs text-gray-500 mt-1">
                                <div class="flex items-center">
                                    <span class="bg-purple-100 text-purple-800 px-2 py-0.5 rounded text-xs mr-2">
                                        ID: ${vendorId}
                                    </span>
                                    ${vendorPhone ? `
                                        <span class="flex items-center text-gray-500">
                                            <i class="fas fa-phone-alt mr-1" style="font-size: 10px;"></i>
                                            ${vendorPhone}
                                        </span>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
        
                li.addEventListener('click', (e) => {
                    e.stopPropagation();
                    vendorInput.value = `${vendorId} | ${vendorName}`;
                    vendorDropdown.classList.add('hidden');
                });
        
                vendorDropdown.appendChild(li);
            });
        }
        
        // আপডেট করা আউটসাইড ক্লিক হ্যান্ডলার
        function setupOutsideClickHandler() {
            document.addEventListener('click', function(e) {
                // চেক করছে ক্লিকটি কি কন্টেইনারের ভেতরে হয়েছে কি না
                if (vendorSearchContainer && !vendorSearchContainer.contains(e.target)) {
                    vendorDropdown.classList.add('hidden');
                }
            });
        }
    </script>