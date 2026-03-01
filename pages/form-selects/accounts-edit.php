<?php
$getAllAccountsApi = $ip_port . "api/accounts/all-trxnable-accounts.php";
?>

<div id="editAccountSearchContainer" class="relative w-full">
    <input
        type="text"
        id="editAccountInput"
        placeholder="Search for an account..."
        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:outline-none"
        autocomplete="off">

    <ul id="editAccountDropdown"
        class="absolute w-full bg-white border border-gray-300 rounded-lg mt-1 max-h-60 overflow-auto shadow-lg hidden z-50">
    </ul>
</div>

<script>
window.editAccountsData = [];
window.editAccountsLoaded = false;

function loadEditAccounts() {
    const api = "<?php echo $getAllAccountsApi; ?>";

    fetch(api)
        .then(res => res.json())
        .then(data => {
            // console.log('Accounts data received:', data);
            window.editAccountsData = Array.isArray(data.accounts) ? data.accounts : [];
            window.editAccountsLoaded = true;
            // console.log('Accounts loaded:', window.editAccountsData.length);
        })
        .catch(err => {
            console.error('Error fetching accounts:', err);
            window.editAccountsData = [];
        });
}

function setupEditAccountSearch(initialValue = null) {
    // console.log('Setting up edit account search with initial value:', initialValue);
    
    const accountInput = document.getElementById('editAccountInput');
    const accountDropdown = document.getElementById('editAccountDropdown');
    const accountContainer = document.getElementById('editAccountSearchContainer');
    
    if (!accountInput || !accountDropdown) {
        console.error('Account input or dropdown not found');
        return;
    }
    
    // Clear any existing listeners by cloning and replacing
    const newAccountInput = accountInput.cloneNode(true);
    accountInput.parentNode.replaceChild(newAccountInput, accountInput);
    const newAccountDropdown = accountDropdown.cloneNode(false);
    accountDropdown.parentNode.replaceChild(newAccountDropdown, accountDropdown);
    
    // Get new references
    const finalAccountInput = document.getElementById('editAccountInput');
    const finalAccountDropdown = document.getElementById('editAccountDropdown');
    const finalAccountContainer = document.getElementById('editAccountSearchContainer');
    
    // Set initial value if provided
    if (initialValue) {
        finalAccountInput.value = initialValue;
    }
    
    // Remove existing click handler
    document.removeEventListener('click', window.editAccountOutsideClickHandler);
    
    // Outside click handler
    window.editAccountOutsideClickHandler = function(e) {
        if (finalAccountContainer && !finalAccountContainer.contains(e.target)) {
            finalAccountDropdown.classList.add('hidden');
        }
    };
    document.addEventListener('click', window.editAccountOutsideClickHandler);
    
    // Input typing
    let typingTimer;
    finalAccountInput.addEventListener('input', () => {
        clearTimeout(typingTimer);
        typingTimer = setTimeout(() => {
            const value = finalAccountInput.value.toLowerCase().trim();
            
            console.log('Filtering accounts with:', value);
            
            if (!window.editAccountsData || window.editAccountsData.length === 0) {
                console.log('No accounts data available');
                return;
            }
            
            const filtered = value === ''
                ? window.editAccountsData
                : window.editAccountsData.filter(acc => {
                    const accId = (acc.sys_id || '').toLowerCase();
                    const accName = (acc.acc_name || '').toLowerCase();
                    return accId.includes(value) || accName.includes(value);
                });
            
            renderEditAccountDropdown(filtered, finalAccountInput, finalAccountDropdown);
            finalAccountDropdown.classList.remove('hidden');
        }, 300);
    });
    
    // Focus show
    finalAccountInput.addEventListener('focus', () => {
        console.log('Account input focused');
        if (window.editAccountsData && window.editAccountsData.length > 0) {
            renderEditAccountDropdown(window.editAccountsData, finalAccountInput, finalAccountDropdown);
            finalAccountDropdown.classList.remove('hidden');
        }
    });
    
}

function renderEditAccountDropdown(list, accountInput, accountDropdown) {
    if (!accountDropdown) {
        console.error('Account dropdown not found');
        return;
    }
    
    accountDropdown.innerHTML = '';

    if (!list || list.length === 0) {
        accountDropdown.innerHTML = 
            '<li class="px-4 py-3 text-center text-gray-500">No accounts found</li>';
        return;
    }

    list.forEach(acc => {
        const li = document.createElement('li');
        li.className = "px-4 py-3 cursor-pointer hover:bg-purple-50 border-b last:border-b-0";

        li.innerHTML = `
            <div class="flex items-center">
                <div class="w-8 h-8 bg-purple-600 rounded-full text-white flex items-center justify-center font-semibold">
                    ${(acc.acc_name || 'A').charAt(0).toUpperCase()}
                </div>
                <div class="ml-3">
                    <div class="font-medium">${acc.acc_name || 'Unknown'}</div>
                    <div class="text-xs text-gray-500">ID: ${acc.sys_id || 'N/A'}</div>
                </div>
            </div>
        `;

        li.onclick = () => {
            accountInput.value = `${acc.sys_id} | ${acc.acc_name || 'Unknown'}`;
            accountDropdown.classList.add('hidden');
        };

        accountDropdown.appendChild(li);
    });
}

// Auto-load accounts when script loads
document.addEventListener('DOMContentLoaded', function() {
    // console.log('Accounts edit script loaded');
    loadEditAccounts();
});
</script>