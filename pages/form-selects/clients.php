<?php
    $getAllClientsApi = $ip_port . "api/clients/all-clients.php";
?>

<div class="relative w-full">
    <div class="flex">
        <input
            type="text"
            id="clientInput"
            placeholder="Search for a client..."
            class="flex-1 px-4 py-2 border border-gray-300 rounded-l-lg focus:ring-2 focus:ring-purple-500 focus:outline-none"
            autocomplete="off">
        <button
            id="dropdownToggle"
            class="px-4 py-2 border border-gray-300 border-l-0 rounded-r-lg bg-gray-100 hover:bg-gray-200"
            type="button">
            ▼
        </button>
    </div>
    <ul id="clientDropdown" class="absolute w-full bg-white border border-gray-300 rounded-lg mt-1 max-h-60 overflow-auto hidden z-1000">
        <!-- JS will populate options here -->
    </ul>
</div>


<script>
        // All API's for this Page
    const API_URL_FOR_ALL_CLIENTS = "<?php echo $getAllClientsApi; ?>";

    // get client's data
    const clientInput = document.getElementById('clientInput');
    const clientDropdown = document.getElementById('clientDropdown');
    const dropdownToggle = document.getElementById('dropdownToggle');

    let clientsData = [];

    fetch(API_URL_FOR_ALL_CLIENTS)
        .then(res => res.json())
        .then(data => {
            clientsData = data.clients;
            renderClientDropdown(clientsData);
        })
        .catch(err => console.error(err));

    function renderClientDropdown(list) {
        clientDropdown.innerHTML = '';
        list.forEach(client => {
            // phone ke parse koro
            const phoneObj = JSON.parse(client.phone);
            const primaryPhone = phoneObj.primary_no;

            const li = document.createElement('li');
            li.textContent = `${client.id} | ${client.name} | ${primaryPhone}`;
            li.className = "px-4 py-2 cursor-pointer hover:bg-purple-100";
            li.addEventListener('click', () => {
                clientInput.value = li.textContent + ` | ${client.sys_id}`;
                clientDropdown.classList.add('hidden');
            });
            clientDropdown.appendChild(li);
        });
    }

    // Filter on typing
    clientInput.addEventListener('input', () => {
        const value = clientInput.value.toLowerCase();
        const filtered = clientsData.filter(c =>
            `${c.id} | ${c.name} | ${c.phone}`.toLowerCase().includes(value)
        );
        renderClientDropdown(filtered);
        clientDropdown.classList.remove('hidden');
    });

    // Toggle button click
    dropdownToggle.addEventListener('click', () => {
        if (clientDropdown.classList.contains('hidden')) {
            renderClientDropdown(clientsData);
            clientDropdown.classList.remove('hidden');
        } else {
            clientDropdown.classList.add('hidden');
        }
    });

    // Hide dropdown on outside click
    document.addEventListener('click', (e) => {
        if (!clientInput.contains(e.target) && !clientDropdown.contains(e.target) && !dropdownToggle.contains(e.target)) {
            clientDropdown.classList.add('hidden');
        }
    });
</script>