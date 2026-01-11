<?php
$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898";
}

$storeAllClientApi = $ip_port . "api/clients/all-clients.php";
$storeVendorApi = $ip_port . "api/vendors/client-store.php";
$storeDeleteApi = $ip_port . "api/vendors/delete-vendor.php";

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

    <!-- Main Content -->
    <main id="mainContent" class="pt-16 pl-64 transition-all duration-300">
        <div class="p-6">
            <div class="grid grid-cols-6 gap-4">
                <div class="col-span-12 bg-white rounded-lg shadow p-4">
                    <div class="flex items-start gap-4 flex-wrap mb-4">
                        <div class="flex-1 min-w-0">
                            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Traveller Lists</h2>
                        </div>
                        <a href="create-client.php" class="hidden md:flex w-48 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 text-md rounded-lg shadow-md hover:shadow-lg transition-all duration-300 items-center justify-center">
                            <i class="fas fa-plus-circle mr-3"></i>Add New Client
                        </a>
                    </div>

                    <div class="overflow-x-auto table-container">
                        <table id="clientTable" class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sl No</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client Name</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone No</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Is Vendor</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody id="clientTableBody" class="bg-white divide-y divide-gray-200">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Floating Quick Access Tab -->
    <?php include '../elements/floating-menus.php'; ?>

    <script src="../assets/js/script.js"></script>

    <script>
        const API_URL_FOR_ALL_CLIENTS = "<?php echo $storeAllClientApi; ?>";
        const API_URL_FOR_VENDOR_STORE = "<?php echo $storeVendorApi; ?>";
        const API_URL_FOR_VENDOR_DELETE = "<?php echo $storeDeleteApi; ?>";

        // Client
        const tableBody = document.getElementById('clientTableBody');

        let clientsData = [];
        fetch(API_URL_FOR_ALL_CLIENTS)
            .then(res => res.json())
            .then(data => {
                clientsData = data.clients;
                renderDropdown(clientsData);
            })
            .catch(err => console.error(err));

        function renderDropdown(list) {
            // আগের ডাটা মুছে ফেলা
            tableBody.innerHTML = '';

            list.forEach((client, index) => {
                const phoneObj = JSON.parse(client.phone);
                const primaryPhone = phoneObj.primary_no;

                const emailObj = JSON.parse(client.email);
                const primaryEmail = phoneObj.primary;

                const tr = document.createElement('tr');
                tr.className = "hover:bg-gray-50";

                // টেবিল রো (Row) এর ভেতরে কলামগুলো তৈরি করা
                tr.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${index+1}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                    <a href="show-clients.php?client_id=${client.id}" title="Details">
                        ${client.given_name || 'No Title'} ${client.sur_name}
                    </a>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">${primaryPhone || 'Unknown'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${primaryEmail || 'Unknown'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <label class="inline-flex items-center cursor-pointer">
                        <input 
                            type="checkbox"
                            class="sr-only peer"
                            ${client.is_vendor == 1 ? 'checked' : ''}
                            onchange="toggleVendor(${client.id}, this)"
                        >
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer 
                            peer-checked:bg-green-600 
                            after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                            after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all
                            peer-checked:after:translate-x-full relative">
                        </div>
                    </label>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <a href="show-clients.php?client_id=${client.id}" title="Details">
                        <i class="fas fa-eye"></i>
                    </a>
                </td>
            `;

                tableBody.appendChild(tr);
            });
        }

        function toggleVendor(clientId, checkbox) {
            const url = checkbox.checked ?
                API_URL_FOR_VENDOR_STORE :
                API_URL_FOR_VENDOR_DELETE;

            fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        client_id: clientId
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert('Vendor added successfully');
                    } else {
                        alert('Failed to add vendor');
                        checkbox.checked = false;
                    }
                })
                .catch(err => {
                    console.error(err);
                    checkbox.checked = false;
                    alert('Something went wrong');
                });
        }
    </script>
</body>

</html>