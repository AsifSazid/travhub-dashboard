<?php
$getAllVendorsApi = $ip_port . "api/vendors/all-vendors.php";
?>
<div id="editVendorSearchContainer" class="relative w-full">
    <div class="flex">
        <input
            type="text"
            id="editVendorInput"
            placeholder="Search for a vendor..."
            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 focus:outline-none transition-all duration-200"
            autocomplete="off">
    </div>
    <ul id="editVendorDropdown" class="absolute w-full bg-white border border-gray-300 rounded-lg mt-1 max-h-60 overflow-auto shadow-lg hidden z-50"></ul>
</div>

<script>
// Make functions globally available
window.editVendorsData = [];
window.editVendorsLoaded = false;

function loadEditVendors() {
    const api = "<?php echo $getAllVendorsApi; ?>";
    
    fetch(api)
        .then(res => res.json())
        .then(data => {
            if (data.vendors && Array.isArray(data.vendors)) {
                window.editVendorsData = data.vendors;
                window.editVendorsLoaded = true;
                // console.log('Vendors loaded:', window.editVendorsData.length);
            } else {
                window.editVendorsData = [];
            }
        })
        .catch(err => {
            console.error('Error fetching vendors:', err);
            window.editVendorsData = [];
        });
}

function setupEditVendorSearch(initialValue = null) {
    // console.log('Setting up edit vendor search with initial value:', initialValue);
    
    const vendorInput = document.getElementById('editVendorInput');
    const vendorDropdown = document.getElementById('editVendorDropdown');
    const vendorContainer = document.getElementById('editVendorSearchContainer');
    
    if (!vendorInput || !vendorDropdown) {
        console.error('Vendor input or dropdown not found');
        return;
    }
    
    // Clear any existing listeners by cloning and replacing
    const newVendorInput = vendorInput.cloneNode(true);
    vendorInput.parentNode.replaceChild(newVendorInput, vendorInput);
    const newVendorDropdown = vendorDropdown.cloneNode(false);
    vendorDropdown.parentNode.replaceChild(newVendorDropdown, vendorDropdown);
    
    // Get new references
    const finalVendorInput = document.getElementById('editVendorInput');
    const finalVendorDropdown = document.getElementById('editVendorDropdown');
    const finalVendorContainer = document.getElementById('editVendorSearchContainer');
    
    // Set initial value if provided
    if (initialValue) {
        finalVendorInput.value = initialValue;
    }
    
    // Remove existing click handler
    document.removeEventListener('click', window.editVendorOutsideClickHandler);
    
    // Outside click handler
    window.editVendorOutsideClickHandler = function(e) {
        if (finalVendorContainer && !finalVendorContainer.contains(e.target)) {
            finalVendorDropdown.classList.add('hidden');
        }
    };
    document.addEventListener('click', window.editVendorOutsideClickHandler);
    
    // Input typing
    let typingTimer;
    finalVendorInput.addEventListener('input', () => {
        clearTimeout(typingTimer);
        typingTimer = setTimeout(() => {
            const value = finalVendorInput.value.toLowerCase().trim();
            
            console.log('Filtering vendors with:', value);
            
            if (!window.editVendorsData || window.editVendorsData.length === 0) {
                console.log('No vendors data available');
                return;
            }
            
            let filtered;
            if (value === '') {
                filtered = window.editVendorsData;
            } else {
                filtered = window.editVendorsData.filter(vendor => {
                    const vendorId = vendor.sys_id ? vendor.sys_id.toString() : '';
                    const vendorName = vendor.name ? vendor.name.toString().toLowerCase() : '';
                    return vendorId.toLowerCase().includes(value) || 
                           vendorName.includes(value);
                });
            }
            
            renderEditVendorDropdown(filtered, finalVendorInput, finalVendorDropdown);
            finalVendorDropdown.classList.remove('hidden');
        }, 300);
    });
    
    // Focus handler
    finalVendorInput.addEventListener('focus', () => {
        console.log('Vendor input focused');
        if (window.editVendorsData && window.editVendorsData.length > 0) {
            renderEditVendorDropdown(window.editVendorsData, finalVendorInput, finalVendorDropdown);
            finalVendorDropdown.classList.remove('hidden');
        } else {
            console.log('No vendors data to display');
        }
    });
    
}

function renderEditVendorDropdown(list, vendorInput, vendorDropdown) {
    console.log(list);
    
    if (!vendorDropdown) {
        console.error('Vendor dropdown not found');
        return;
    }
    
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
        let vendorName = 'Unknown Vendor';
        let vendorPhone = '';
        let vendorId = vendor.sys_id || 'N/A';
        
        try {
            if (vendor.name) {
                if (typeof vendor.name === 'string' && vendor.name.trim().startsWith('{')) {
                    try {
                        const nameObj = JSON.parse(vendor.name);
                        vendorName = nameObj.primary || 'Unnamed Vendor';
                    } catch {
                        vendorName = vendor.name;
                    }
                } else {
                    vendorName = vendor.name.toString();
                }
            }
            
            if (vendor.phone) {
                if (typeof vendor.phone === 'string' && vendor.phone.trim().startsWith('{')) {
                    try {
                        const phoneObj = JSON.parse(vendor.phone);
                        vendorPhone = phoneObj.primary_no || '';
                    } catch {
                        vendorPhone = vendor.phone;
                    }
                } else {
                    vendorPhone = vendor.phone.toString();
                }
            }
        } catch (error) {
            console.error('Error parsing vendor data:', error);
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

// Auto-load vendors when script loads
document.addEventListener('DOMContentLoaded', function() {
    // console.log('Vendors edit script loaded');
    loadEditVendors();
});
</script>