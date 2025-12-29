<?php
$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898";
}

$workId = $_GET['work_id'];
$taskId = $_GET['task_id'];

$getClientsApi = $ip_port . "api/clients/get-client.php?work_id=$workId";
$getAllVendorsApi = $ip_port . "api/vendors/all-vendors.php";
$getTaskFinEntriesApi = $ip_port . "api/financial_entries/task-fin-entries.php?task_id=$taskId";
$storeFinancialEntriesApi = $ip_port . "api/financial_entries/store.php";

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Work Entry</title>
    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png" sizes="16x16">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/sortablejs@1.14.0/Sortable.min.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .search-result-item {
            transition: all 0.2s ease;
        }

        .search-result-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .drag-drop-area {
            border: 2px dashed #d1d5db;
            transition: all 0.3s ease;
        }

        .drag-drop-area.dragover {
            border-color: #3b82f6;
            background-color: rgba(59, 130, 246, 0.05);
        }

        .file-item {
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .draggable-item {
            cursor: move;
            user-select: none;
        }

        .draggable-item.dragging {
            opacity: 0.5;
        }

        /* Scrollbar styling */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Preview Modal */
        .preview-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            animation: fadeIn 0.3s ease;
        }

        .preview-content {
            background-color: white;
            margin: 5% auto;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
            animation: slideUp 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
    <!-- Add to the <head> section for FilePond -->
    <link href="https://unpkg.com/filepond@^4/dist/filepond.css" rel="stylesheet">
    <link href="https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css" rel="stylesheet">
    <script src="https://unpkg.com/filepond@^4/dist/filepond.js"></script>
    <script src="https://unpkg.com/filepond-plugin-file-validate-type/dist/filepond-plugin-file-validate-type.js"></script>
    <script src="https://unpkg.com/filepond-plugin-file-validate-size/dist/filepond-plugin-file-validate-size.js"></script>
    <script src="https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.js"></script>
</head>

<body class="bg-gray-50 font-sans">
    <!-- Top Navigation -->
    <?php include '../elements/header.php'; ?>

    <!-- Sidebar -->
    <?php include '../elements/aside.php'; ?>

    <!-- Preview Modal -->
    <div id="previewModal" class="preview-modal">
        <div class="preview-content">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800" id="previewTitle">File Preview</h3>
                <button onclick="closePreview()" class="text-gray-500 hover:text-gray-700 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="modalPreviewContent" class="p-4">
                <!-- Preview content will be loaded here -->
            </div>
        </div>
    </div>

    <main id="mainContent" class="pt-16 pl-64 transition-all duration-300">
        <div class="p-6">
            <div class="col-span-6 bg-white rounded-lg shadow p-4 flex flex-col">
                <h3 class="text-2xl font-bold text-gray-900 text-center border-b pb-4 mb-4">
                    Financial Transaction Management
                </h3>

                <div class="grid grid-cols-6 gap-4">
                    <!-- Left Column: Client Search -->
                    <div class="col-span-3 bg-white rounded-lg shadow p-4 flex flex-col">
                        <!-- Header -->
                        <div class="mb-4">
                            <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                                <i class="fas fa-user-friends mr-2 text-primary-600"></i>
                                Client/traveller Search
                            </h2>
                            <p class="text-sm text-gray-600">Search clients by various criteria</p>
                        </div>

                        <!-- Search Form -->
                        <div class="mb-4">
                            <div id="clientWorkInfo" class="flex space-x-2"></div>

                            <div class="flex space-x-2 my-4">
                                <div class="w-2/3">
                                    <label for="client_purpose" class="block text-sm font-medium text-gray-700">Purpose</label>
                                    <input type="text" id="client_purpose" name="client_purpose" required="" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 p-2" placeholder="e.g., Initial Payment">
                                </div>

                                <div class="w-1/3">
                                    <label for="client_amount" class="block text-sm font-medium text-gray-700">Amount (Deposit)</label>
                                    <input type="number" step="0.01" min="0.01" id="client_amount" name="client_amount" required="" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 p-2" placeholder="0.00">
                                </div>
                            </div>

                            <input type="hidden" name="type" value="debit">

                            <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-150 ease-in-out">
                                Debit
                            </button>
                        </div>
                    </div>

                    <!-- Right Column: Vendor Search -->
                    <div class="col-span-3 bg-white rounded-lg shadow p-4 flex flex-col">
                        <!-- Header -->
                        <div class="mb-4">
                            <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                                <i class="fas fa-building mr-2 text-green-600"></i>
                                Vendor Search
                            </h2>
                            <p class="text-sm text-gray-600">Search vendors by various criteria</p>
                        </div>

                        <!-- Search Form -->
                        <div class="mb-4">
                            <div class="flex space-x-2">
                                <div class="relative w-full">
                                    <div class="flex">
                                        <input
                                            type="text"
                                            id="vendorInput"
                                            placeholder="Search for a vendor..."
                                            class="flex-1 px-4 py-2 border border-gray-300 rounded-l-lg focus:ring-2 focus:ring-purple-500 focus:outline-none"
                                            autocomplete="off">
                                        <button
                                            id="dropdownToggle"
                                            class="px-4 py-2 border border-gray-300 border-l-0 rounded-r-lg bg-gray-100 hover:bg-gray-200"
                                            type="button">
                                            ▼
                                        </button>
                                    </div>
                                    <ul id="vendorDropdown" class="absolute w-full bg-white border border-gray-300 rounded-lg mt-1 max-h-60 overflow-auto hidden z-50">
                                        <!-- JS will populate options here -->
                                    </ul>
                                </div>

                            </div>

                            <div class="flex space-x-2 my-4">
                                <div class="w-2/3">
                                    <label for="vendor_purpose" class="block text-sm font-medium text-gray-700">Purpose</label>
                                    <input type="text" id="vendor_purpose" name="vendor_purpose" required="" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 p-2" placeholder="e.g., Service Fee">
                                </div>

                                <div class="w-1/3">
                                    <label for="vendor_amount" class="block text-sm font-medium text-gray-700">Amount (Withdrawal)</label>
                                    <input type="number" step="0.01" min="0.01" id="vendor_amount" name="vendor_amount" required="" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 p-2" placeholder="0.00">
                                </div>
                            </div>

                            <input type="hidden" name="type" value="credit">

                            <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition duration-150 ease-in-out">
                                Credit
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-6">
            <div class="col-span-6 bg-white rounded-lg shadow p-4 flex flex-col">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">Financial Transactions</h2>

                <div class="overflow-x-auto table-container">
                    <table id="finTable" class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client/Vendor Name</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            </tr>
                        </thead>
                        <tbody id="finTableBody" class="bg-white divide-y divide-gray-200">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <?php include '../elements/floating-menus.php'; ?>

    <script src="../assets/js/script.js"></script>


    <script>
        const GET_CLIENT_API = "<?php echo $getClientsApi; ?>";
        const GET_ALL_VENDOR_API = "<?php echo $getAllVendorsApi; ?>";
        const FINANCIAL_ENTRIES_STORE_API = "<?php echo $storeFinancialEntriesApi; ?>";
        const GET_FINANCIAL_STATEMENT_API = "<?php echo $getTaskFinEntriesApi; ?>";

        const vendorInput = document.getElementById('vendorInput');
        const vendorDropdown = document.getElementById('vendorDropdown');
        const dropdownToggle = document.getElementById('dropdownToggle');

        let vendorsData = [];
        fetch(GET_ALL_VENDOR_API)
            .then(res => res.json())
            .then(data => {
                vendorsData = data.vendors;
                renderDropdown(vendorsData);
            })
            .catch(err => console.error(err));

        function renderDropdown(list) {
            vendorDropdown.innerHTML = '';
            list.forEach(vendor => {

                const li = document.createElement('li');
                li.textContent = `${vendor.id} | ${vendor.client_name}`;
                li.className = "px-4 py-2 cursor-pointer hover:bg-purple-100";
                li.addEventListener('click', () => {
                    vendorInput.value = li.textContent;
                    vendorDropdown.classList.add('hidden');
                });
                vendorDropdown.appendChild(li);
            });
        }

        // Filter on typing
        vendorInput.addEventListener('input', () => {
            const value = vendorInput.value.toLowerCase();
            const filtered = vendorsData.filter(c =>
                `${c.id} | ${c.name} | ${c.phone}`.toLowerCase().includes(value)
            );
            renderDropdown(filtered);
            vendorDropdown.classList.remove('hidden');
        });

        // Toggle button click
        dropdownToggle.addEventListener('click', () => {
            if (vendorDropdown.classList.contains('hidden')) {
                renderDropdown(vendorsData);
                vendorDropdown.classList.remove('hidden');
            } else {
                vendorDropdown.classList.add('hidden');
            }
        });

        // Hide dropdown on outside click
        document.addEventListener('click', (e) => {
            if (!vendorInput.contains(e.target) && !vendorDropdown.contains(e.target) && !dropdownToggle.contains(e.target)) {
                vendorDropdown.classList.add('hidden');
            }
        });


        // store financial entries
        // Debit button click handler
        document.querySelector('.bg-red-600').addEventListener('click', async function() {
            await recordTransaction('debit');
        });

        // Credit button click handler
        document.querySelector('.bg-green-600').addEventListener('click', async function() {
            await recordTransaction('credit');
        });

        async function recordTransaction(type) {
            try {
                // Get common data
                const workId = <?php echo json_encode($workId); ?>;
                const taskId = <?php echo json_encode($taskId); ?>;

                if (type === 'debit') {
                    // Get client data
                    const clientPurpose = document.getElementById('client_purpose').value.trim();
                    const clientAmount = parseFloat(document.getElementById('client_amount').value);

                    // You need to get client_id from somewhere
                    // Assuming you have it in a hidden field or from the API response
                    const clientId = getClientId(); // You need to implement this

                    if (!clientId) {
                        alert('Client ID not found');
                        return;
                    }

                    if (!clientPurpose || !clientAmount || clientAmount <= 0) {
                        alert('Please enter valid purpose and amount for debit');
                        return;
                    }

                    const transactionData = {
                        type: 'debit',
                        amount: clientAmount,
                        purpose: clientPurpose,
                        client_id: clientId,
                        work_id: workId,
                        task_id: taskId,
                        date: new Date().toISOString().split('T')[0]
                    };

                    const response = await fetch(FINANCIAL_ENTRIES_STORE_API, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(transactionData)
                    });

                    const result = await response.json();

                    if (result.success) {
                        alert('Debit transaction recorded successfully!');
                        // Clear form
                        document.getElementById('client_purpose').value = '';
                        document.getElementById('client_amount').value = '';

                        reloadFinancialTable();
                    } else {
                        alert('Error: ' + result.message);
                    }

                } else if (type === 'credit') {
                    // Get vendor data
                    const vendorPurpose = document.getElementById('vendor_purpose').value.trim();
                    const vendorAmount = parseFloat(document.getElementById('vendor_amount').value);
                    const vendorInput = document.getElementById('vendorInput').value;

                    // Extract vendor ID from input (format: "ID | Name")
                    const vendorId = extractVendorId(vendorInput);

                    if (!vendorId) {
                        alert('Please select a valid vendor');
                        return;
                    }

                    if (!vendorPurpose || !vendorAmount || vendorAmount <= 0) {
                        alert('Please enter valid purpose and amount for credit');
                        return;
                    }

                    const transactionData = {
                        type: 'credit',
                        amount: vendorAmount,
                        purpose: vendorPurpose,
                        vendor_id: vendorId,
                        work_id: workId,
                        task_id: taskId,
                        date: new Date().toISOString().split('T')[0]
                    };

                    const response = await fetch(FINANCIAL_ENTRIES_STORE_API, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(transactionData)
                    });

                    const result = await response.json();

                    if (result.success) {
                        alert('Credit transaction recorded successfully!');
                        // Clear form
                        document.getElementById('vendor_purpose').value = '';
                        document.getElementById('vendor_amount').value = '';
                        document.getElementById('vendorInput').value = '';

                        reloadFinancialTable();
                    } else {
                        alert('Error: ' + result.message);
                    }
                }

            } catch (error) {
                console.error('Error recording transaction:', error);
                alert('An error occurred while recording the transaction');
            }
        }

        function extractVendorId(vendorString) {
            // Vendor string format: "ID | Name"
            const match = vendorString.match(/^(\d+)\s*\|\s*/);
            return match ? parseInt(match[1]) : null;
        }

        function getClientId() {
            // You need to implement this based on how you store client_id
            // Options:
            // 1. From a hidden input field
            // 2. From the API response when page loads
            // 3. Extract from URL or session

            // Example: If you have a hidden field
            const hiddenField = document.getElementById('client_id');
            if (hiddenField) {
                return hiddenField.value;
            }

            // Or from the client data you fetched earlier
            // You'll need to store it when you fetch client data
            return window.currentClientId || null;
        }

        // Financial Transaction Against Task
        function reloadFinancialTable() {
            fetch(GET_FINANCIAL_STATEMENT_API)
                .then(res => res.json())
                .then(data => {
                    if (!data.success) return;

                    const finStmts = data.finStmts;
                    renderFinTable(finStmts);
                })
        }

        const tableBody = document.getElementById('finTableBody');

        function renderFinTable(list) {
            // আগের ডাটা মুছে ফেলা
            tableBody.innerHTML = '';

            list.forEach(finSingleEntry => {
                const tr = document.createElement('tr');
                tr.className = "hover:bg-gray-50";

                // type normalize
                const type = (finSingleEntry.type || '').toLowerCase();

                let typeBadge = `
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                            UNKNOWN
                        </span>
                    `;

                if (type === 'debit') {
                    typeBadge = `
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                                DEBIT
                            </span>
                        `;
                } else if (type === 'credit') {
                    typeBadge = `
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                                CREDIT
                            </span>
                        `;
                }

                tr.innerHTML = `
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            ${finSingleEntry.date || 'N/A'}
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            ${finSingleEntry.purpose || 'No Data Found'}
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            ${finSingleEntry.client_name || finSingleEntry.vendor_name || 'Unknown'}
                        </td>
                        
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                        ${typeBadge}
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            ${finSingleEntry.amount || '-'}
                        </td>
                    `;

                finTableBody.appendChild(tr);
            });

        }

        reloadFinancialTable();

        // Update the client data fetch to store client_id
        fetch(GET_CLIENT_API)
            .then(res => res.json())
            .then(data => {
                if (!data.success) return;

                const client = data.client;
                const work = data.work;

                // Store client ID globally or in a hidden field
                window.currentClientId = client.id;

                // Or create a hidden field
                if (!document.getElementById('client_id')) {
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.id = 'client_id';
                    hiddenInput.value = client.id;
                    document.body.appendChild(hiddenInput);
                }

                const clientName = `${client.given_name} ${client.sur_name}`;
                const workName = work.title;

                document.getElementById('clientWorkInfo').innerHTML = `
                <h3 class="text-xl font-semibold text-gray-800">
                    ${clientName}
                </h3>
                <h3 class="text-gray-400">/ </h3>
                <h3 class="text-lg text-gray-600">
                     Work Title: ${workName}
                </h3>
            `;
            })
            .catch(err => console.error('Error fetching data:', err));
    </script>
</body>

</html>