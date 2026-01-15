<?php
$getAllAccountsApi = $ip_port . "api/accounts/all-trxnable-accounts.php";
?>

<div id="accountSearchContainer" class="relative w-full">
    <input
        type="text"
        id="accountInput"
        placeholder="Search for an account..."
        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:outline-none"
        autocomplete="off">

    <ul id="accountDropdown"
        class="absolute w-full bg-white border border-gray-300 rounded-lg mt-1 max-h-60 overflow-auto shadow-lg hidden z-50">
    </ul>
</div>

<script>
const GET_ALL_ACCOUNTS_API = "<?php echo $getAllAccountsApi; ?>";

let accountsData = [];
const accountInput = document.getElementById('accountInput');
const accountDropdown = document.getElementById('accountDropdown');
const accountContainer = document.getElementById('accountSearchContainer');

/* Load accounts */
fetch(GET_ALL_ACCOUNTS_API)
    .then(res => res.json())
    .then(data => {
        accountsData = Array.isArray(data.accounts) ? data.accounts : [];
    })
    .catch(() => accountsData = []);

/* Input typing (debounce) */
let accountTypingTimer;
accountInput.addEventListener('input', () => {
    clearTimeout(accountTypingTimer);
    accountTypingTimer = setTimeout(() => {
        const value = accountInput.value.toLowerCase().trim();

        const filtered = value === ''
            ? accountsData
            : accountsData.filter(acc =>
                (acc.sys_id ?? '').toLowerCase().includes(value) ||
                (acc.acc_name ?? '').toLowerCase().includes(value)
            );

        renderAccountDropdown(filtered);
        accountDropdown.classList.remove('hidden');
    }, 300);
});

/* Focus show */
accountInput.addEventListener('focus', () => {
    renderAccountDropdown(accountsData);
    accountDropdown.classList.remove('hidden');
});

function renderAccountDropdown(list) {
    accountDropdown.innerHTML = '';

    if (!list.length) {
        accountDropdown.innerHTML =
            `<li class="px-4 py-3 text-center text-gray-500">No accounts found</li>`;
        return;
    }

    list.forEach(acc => {
        const li = document.createElement('li');
        li.className =
            "px-4 py-3 cursor-pointer hover:bg-purple-50 border-b last:border-b-0";

        li.innerHTML = `
            <div class="flex items-center">
                <div class="w-8 h-8 bg-purple-600 rounded-full text-white flex items-center justify-center font-semibold">
                    ${acc.acc_name?.charAt(0).toUpperCase() ?? 'A'}
                </div>
                <div class="ml-3">
                    <div class="font-medium">${acc.acc_name}</div>
                    <div class="text-xs text-gray-500">ID: ${acc.sys_id}</div>
                </div>
            </div>
        `;

        li.onclick = () => {
            accountInput.value = `${acc.sys_id} | ${acc.acc_name}`;
            accountDropdown.classList.add('hidden');
        };

        accountDropdown.appendChild(li);
    });
}

/* Outside click */
document.addEventListener('click', e => {
    if (!accountContainer.contains(e.target)) {
        accountDropdown.classList.add('hidden');
    }
});
</script>
