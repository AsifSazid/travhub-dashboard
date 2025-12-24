<div id="generalTab" class="tab-content">
    <div class="flex items-start gap-4 flex-wrap">
        <!-- Left content -->
        <div class="flex-1 min-w-0">
            <h2 class="text-lg font-semibold text-gray-800 mb-1">
                General Information for Completed Work Entry
            </h2>
            <p class="text-sm text-gray-600 mb-4">Please fill up the form</p>
        </div>

        <!-- Button only on md and above -->
        <a href="#" class="hidden md:flex w-48 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 text-md rounded-lg shadow-md hover:shadow-lg transition-all duration-300 items-center justify-center">
            <i class="fas fa-plus-circle mr-3"></i>Add New Client
        </a>
    </div>



    <form action="">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="col-span-3 block text-sm font-medium text-gray-700 my-2">Search Client</label>

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
                    <ul id="clientDropdown" class="absolute w-full bg-white border border-gray-300 rounded-lg mt-1 max-h-60 overflow-auto hidden z-50">
                        <!-- JS will populate options here -->
                    </ul>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 my-2">Work Title</label>
                <input name="work_title" placeholder="Write a Work Title" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
        </div>

        <button type="submit" class="flex-1 px-4 py-2 mt-4 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-md hover:shadow-lg transition-all duration-300 flex items-center justify-center">Submit</button>
    </form>

    <hr class="my-6">

    <div class="col-span-6 bg-white rounded-lg shadow p-4 flex flex-col">
        <h2 class="text-2xl font-semibold text-gray-800 mb-4">Transaction Ledger</h2>

        <div class="overflow-x-auto table-container">
            <table id="ledgerTable" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client (ID)</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor (ID)</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dir</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider text-green-600">Deposit (IN)</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider text-red-600">Withdraw (OUT)</th>
                    </tr>
                </thead>
                <tbody id="ledgerTableBody" class="bg-white divide-y divide-gray-200">
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">2025-11-29 02:06:58</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">Best Western Plus Pearl Creek Hotel</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Client: Rony Maldives (13)</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">-</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Dubai Hotel 7th to 10th August/BestWesternPlusPearlCreekHotel_Dubai_7Aug-10Aug_ShahidulIslam.pdf</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-green-600">
                            19200.00
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-red-600">
                            0.00
                        </td>
                    </tr>
                </tbody>
                <tfoot id="ledgerTableFoot">
                    <tr class="bg-gray-100 font-bold">
                        <td colspan="4" class="px-6 py-4 text-right text-base text-gray-900">Total:</td>
                        <td class="px-6 py-4 text-right text-base text-gray-900"></td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-base text-green-700">
                            19200.00 </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-base text-red-700">
                            0.00 </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<script>
    // All API's for this Page
    const API_URL_FOR_ALL_CLIENTS = "<?php echo $getAllClientsApi; ?>";
    const API_URL_FOR_WORK_STORE = "<?php echo $storeWorkApi; ?>";


    // get client's data
    const clientInput = document.getElementById('clientInput');
    const clientDropdown = document.getElementById('clientDropdown');
    const dropdownToggle = document.getElementById('dropdownToggle');

    let clientsData = [];
    fetch(API_URL_FOR_ALL_CLIENTS)
        .then(res => res.json())
        .then(data => {
            clientsData = data.clients;
            renderDropdown(clientsData);
        })
        .catch(err => console.error(err));

    function renderDropdown(list) {
        clientDropdown.innerHTML = '';
        list.forEach(client => {
            const li = document.createElement('li');
            li.textContent = `${client.id} | ${client.name} | ${client.phone}`;
            li.className = "px-4 py-2 cursor-pointer hover:bg-purple-100";
            li.addEventListener('click', () => {
                clientInput.value = li.textContent;
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
        renderDropdown(filtered);
        clientDropdown.classList.remove('hidden');
    });

    // Toggle button click
    dropdownToggle.addEventListener('click', () => {
        if (clientDropdown.classList.contains('hidden')) {
            renderDropdown(clientsData);
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


    // store work

    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        e.preventDefault(); // page reload prevent

        // Get form values
        const clientValue = document.getElementById('clientInput').value;
        const workTitle = form.querySelector('input[name="work_title"]').value;

        // Prepare payload
        const payload = {
            client: clientValue,
            work_title: workTitle
        };

        // Send data to API
        fetch(API_URL_FOR_WORK_STORE, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                console.log('API Response:', data);

                if (data.success) {
                    alert('Work saved successfully!');
                    form.reset(); // optional: reset form
                } else {
                    alert('Error: ' + (data.message || 'Something went wrong'));
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alert('Network or server error');
            });
    });
</script>