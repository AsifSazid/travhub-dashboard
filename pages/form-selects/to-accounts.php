<?php
$getAllToAccountsApi = $ip_port . "api/accounts/all-trxnable-accounts.php";
?>

<div id="toAccountSearchContainer" class="relative w-full">
    <input
        type="text"
        id="toAccountInput"
        placeholder="Search for an Account..."
        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:outline-none"
        autocomplete="off">

    <ul id="toAccountDropdown"
        class="absolute w-full bg-white border border-gray-300 rounded-lg mt-1 max-h-60 overflow-auto shadow-lg hidden z-50">
    </ul>
</div>

<script>
const GET_ALL_TO_ACCOUNTS_API = "<?php echo $getAllToAccountsApi; ?>";

let toAccountsData = [];
const toAccountInput = document.getElementById('toAccountInput');
const toAccountDropdown = document.getElementById('toAccountDropdown');
const toAccountContainer = document.getElementById('toAccountSearchContainer');

/* Load toAccounts */
fetch(GET_ALL_TO_ACCOUNTS_API)
    .then(res => res.json())
    .then(data => {
        toAccountsData = Array.isArray(data.accounts) ? data.accounts : [];
    })
    .catch(() => toAccountsData = []);

/* Input typing (debounce) */
let toAccountTypingTimer;
toAccountInput.addEventListener('input', () => {
    clearTimeout(toAccountTypingTimer);
    toAccountTypingTimer = setTimeout(() => {
        const value = toAccountInput.value.toLowerCase().trim();

        const filtered = value === ''
            ? toAccountsData
            : toAccountsData.filter(acc =>
                (acc.sys_id ?? '').toLowerCase().includes(value) ||
                (acc.acc_name ?? '').toLowerCase().includes(value)
            );

        renderToAccountDropdown(filtered);
        toAccountDropdown.classList.remove('hidden');
    }, 300);
});

/* Focus show */
toAccountInput.addEventListener('focus', () => {
    renderToAccountDropdown(toAccountsData);
    toAccountDropdown.classList.remove('hidden');
});

function renderToAccountDropdown(list) {
    toAccountDropdown.innerHTML = '';

    if (!list.length) {
        toAccountDropdown.innerHTML =
            `<li class="px-4 py-3 text-center text-gray-500">No Accounts found</li>`;
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
            toAccountInput.value = `${acc.sys_id} | ${acc.acc_name}`;
            toAccountDropdown.classList.add('hidden');
        };

        toAccountDropdown.appendChild(li);
    });
}

/* Outside click */
document.addEventListener('click', e => {
    if (!toAccountContainer.contains(e.target)) {
        toAccountDropdown.classList.add('hidden');
    }
});
</script>
