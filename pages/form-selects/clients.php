<?php
$getAllClientsApi = $ip_port . "api/clients/all-clients.php";
?>
<div class="grid grid-cols-4 gap-4 items-center">
    <div id="clientSearchContainer" class="relative w-full col-span-3">
        <input
            type="text"
            id="clientInput"
            placeholder="Search for a client..."
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:outline-none shadow-sm transition-all"
            autocomplete="off">
    
        <ul id="clientDropdown"
            class="absolute w-full bg-white border border-gray-300 rounded-lg mt-1 max-h-60 overflow-auto shadow-xl hidden z-50">
        </ul>
    </div>
    
    <div class="col-span-1 flex gap-2">
        <a href="./create-client.php" 
           target="_blank" 
           title="Add New Client"
           class="flex items-center justify-center w-full bg-purple-600 hover:bg-purple-700 text-white py-2.5 rounded-lg transition-colors shadow-sm">
            <i class="fas fa-plus"></i>
        </a>
        
        <button type="button" onclick="loadClients()" 
                title="Refresh List"
                class="flex items-center justify-center w-full bg-gray-100 hover:bg-gray-200 text-gray-700 py-2.5 rounded-lg border border-gray-300 transition-all active:scale-95 shadow-sm">
            <i class="fa-solid fa-arrows-rotate"></i>
        </button>
    </div>
</div>
<script>
const GET_ALL_CLIENTS_API = "<?php echo $getAllClientsApi; ?>";

let clientsData = [];
const clientInput = document.getElementById('clientInput');
const clientDropdown = document.getElementById('clientDropdown');
const clientContainer = document.getElementById('clientSearchContainer');

/* Load clients */
function loadClients(){
    fetch(GET_ALL_CLIENTS_API)
        .then(res => res.json())
        .then(data => {
            clientsData = Array.isArray(data.clients) ? data.clients : [];
            // console.log(clientsData);
        })
        .catch(() => clientsData = []);
    
}

/* Typing */
let clientTypingTimer;
clientInput.addEventListener('input', () => {
    clearTimeout(clientTypingTimer);
    clientTypingTimer = setTimeout(() => {
        const value = clientInput.value.toLowerCase().trim();

        const filtered = value === ''
            ? clientsData
            : clientsData.filter(c =>
                c.name?.toLowerCase().includes(value) ||
                c.sys_id?.toLowerCase().includes(value)
            );

        renderClientDropdown(filtered);
        clientDropdown.classList.remove('hidden');
    }, 300);
});

/* Focus */
clientInput.addEventListener('focus', () => {
    renderClientDropdown(clientsData);
    clientDropdown.classList.remove('hidden');
});

function renderClientDropdown(list) {
    clientDropdown.innerHTML = '';

    if (!list.length) {
        clientDropdown.innerHTML =
            `<li class="px-4 py-3 text-center text-gray-500">No clients found</li>`;
        return;
    }

    list.forEach(client => {
        let phone = '';
        try {
            if (client.phone?.startsWith('{')) {
                phone = JSON.parse(client.phone).primary_no ?? '';
            }
        } catch {}

        const li = document.createElement('li');
        li.className =
            "px-4 py-3 cursor-pointer hover:bg-purple-50 border-b last:border-b-0";

        li.innerHTML = `
            <div class="flex items-center">
                <div class="w-8 h-8 bg-purple-600 rounded-full text-white flex items-center justify-center font-semibold">
                    ${client.name?.charAt(0).toUpperCase() ?? 'C'}
                </div>
                <div class="ml-3 flex-1">
                    <div class="font-medium">${client.name}</div>
                    <div class="text-xs text-gray-500 flex gap-2">
                        <span>ID: ${client.sys_id}</span>
                        ${phone ? `<span>📞 ${phone}</span>` : ''}
                    </div>
                </div>
            </div>
        `;

        li.onclick = () => {
            clientInput.value = `${client.sys_id} | ${client.name}`;
            clientDropdown.classList.add('hidden');
        };

        clientDropdown.appendChild(li);
    });
}

/* Outside click */
document.addEventListener('click', e => {
    if (!clientContainer.contains(e.target)) {
        clientDropdown.classList.add('hidden');
    }
});

loadClients();
</script>
