<?php
    $getAllAccountsApi = $ip_port . "api/accounts/all-accounts.php";
?>

<div class="relative w-full">
    <div class="flex">
        <input
            type="text"
            id="accountInput"
            placeholder="Search for a account..."
            class="flex-1 px-4 py-2 border border-gray-300 rounded-l-lg focus:ring-2 focus:ring-purple-500 focus:outline-none"
            autocomplete="off">
        <button
            id="accountDropdownToggle"
            class="px-4 py-2 border border-gray-300 border-l-0 rounded-r-lg bg-gray-100 hover:bg-gray-200"
            type="button">
            ▼
        </button>
    </div>
    <ul id="accountDropdown" class="absolute w-full bg-white border border-gray-300 rounded-lg mt-1 max-h-60 overflow-auto hidden z-50">
        <!-- JS will populate options here -->
    </ul>
</div>


<script>
        // All API's for this Page
    const API_URL_FOR_ALL_ACCOUNTS = "<?php echo $getAllAccountsApi; ?>";

    // get account's data
    const accountInput = document.getElementById('accountInput');
    const accountDropdown = document.getElementById('accountDropdown');
    const accountDropdownToggle = document.getElementById('accountDropdownToggle');

    let accountsData = [];

    fetch(API_URL_FOR_ALL_ACCOUNTS)
        .then(res => res.json())
        .then(data => {
            accountsData = data.accounts;
            renderAccountDropdown(accountsData);
        })
        .catch(err => console.error(err));

    function renderAccountDropdown(list) {
        accountDropdown.innerHTML = '';
        list.forEach(account => {

            const li = document.createElement('li');
            li.textContent = `${account.id} | ${account.acc_name}`;
            li.className = "px-4 py-2 cursor-pointer hover:bg-purple-100";
            li.addEventListener('click', () => {
                accountInput.value = li.textContent + ` | ${account.sys_id}`;
                accountDropdown.classList.add('hidden');
            });
            accountDropdown.appendChild(li);
        });
    }

    // Filter on typing
    accountInput.addEventListener('input', () => {
        const value = accountInput.value.toLowerCase();
        const filtered = accountsData.filter(c =>
            `${c.id} | ${c.name} | ${c.phone}`.toLowerCase().includes(value)
        );
        renderAccountDropdown(filtered);
        accountDropdown.classList.remove('hidden');
    });

    // Toggle button click
    accountDropdownToggle.addEventListener('click', () => {
        if (accountDropdown.classList.contains('hidden')) {
            renderAccountDropdown(accountsData);
            accountDropdown.classList.remove('hidden');
        } else {
            accountDropdown.classList.add('hidden');
        }
    });

    // Hide dropdown on outside click
    document.addEventListener('click', (e) => {
        if (!accountInput.contains(e.target) && !accountDropdown.contains(e.target) && !accountDropdownToggle.contains(e.target)) {
            accountDropdown.classList.add('hidden');
        }
    });
</script>