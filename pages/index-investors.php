<?php
include_once('./authenticate.php');
$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898";
}

$allInvestor = $ip_port . "api/investors/all-investors.php";

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
                            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Investor Lists</h2>
                        </div>
                        <a href="create-investor.php" class="hidden md:flex w-48 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 text-md rounded-lg shadow-md hover:shadow-lg transition-all duration-300 items-center justify-center">
                            <i class="fas fa-plus-circle mr-3"></i>Add New Investor
                        </a>
                    </div>

                    <div class="overflow-x-auto table-container">
                        <table id="investorTable" class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sl No</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Investor ID</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Investor Name</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone No</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody id="investorTableBody" class="bg-white divide-y divide-gray-200">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Modal Template -->
    <div id="modalOverlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-40 hidden modal-overlay">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4 modal-slide-in">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800" id="modalTitle">Add New Item</h3>
                    <button id="modalClose" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="mb-6" id="modalContent">
                    <!-- Modal content will be inserted here -->
                    <p>Modal content goes here.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Quick Access Tab -->
    <?php include '../elements/floating-menus.php'; ?>

    <script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>

    <script>
        const API_URL_FOR_ALL_INVESTORS = "<?php echo $allInvestor; ?>";

        // Investor
        const tableBody = document.getElementById('investorTableBody');
        const modalOverlay = document.getElementById('modalOverlay');
        const modalTitle = document.getElementById('modalTitle');
        const modalContent = document.getElementById('modalContent');
        const modalClose = document.getElementById('modalClose');

        let investorsData = [];
        fetch(API_URL_FOR_ALL_INVESTORS)
            .then(res => res.json())
            .then(data => {
                investorsData = data.investors;
                renderDropdown(investorsData);
            })
            .catch(err => console.error(err));

        function renderDropdown(list) {
            // আগের ডাটা মুছে ফেলা
            tableBody.innerHTML = '';
        
            // যদি কোনো investor না থাকে
            if (!list || list.length === 0) {
                const tr = document.createElement('tr');
        
                tr.innerHTML = `
                    <td colspan="8" class="px-6 py-10 text-center text-gray-500">
                        <div class="flex flex-col items-center gap-2">
                            <i class="fas fa-users-slash text-3xl text-gray-400"></i>
                            <p class="text-sm">No Investors Found!</p>
                        </div>
                    </td>
                `;
        
                tableBody.appendChild(tr);
                return;
            }
        
            list.forEach((investor, index) => {
                const phoneObj = JSON.parse(investor.phone || '{}');
                const primaryPhone = phoneObj.primary_no || 'Unknown';
        
                const emailObj = JSON.parse(investor.email || '{}');
                const primaryEmail = emailObj.primary || 'Unknown';
        
                const tr = document.createElement('tr');
                tr.className = "hover:bg-gray-50";
        
                tr.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${index + 1}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                        <a href="show-investors.php?investor_id=${investor.sys_id}" title="Details">
                            ${investor.sys_id || 'No ID'}
                        </a>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                        <a href="show-investors.php?investor_id=${investor.sys_id}" title="Details">
                            ${investor.name || 'No Name'}
                        </a>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">${primaryPhone}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${primaryEmail}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 uppercase">${investor.department_name || 'Unknown'}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 uppercase">${investor.type || 'Unknown'}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <a href="show-investors.php?investor_id=${investor.sys_id}" title="Details">
                            <i class="fas fa-eye"></i>
                        </a>
                        <button onclick='viewFirstCredentials(${JSON.stringify(investor)})' title="Details">
                            <i class="fa-solid fa-key ml-3"></i>
                        </button>
                    </td>
                `;
        
                tableBody.appendChild(tr);
            });
        }
        
        function viewFirstCredentials(investor) {
            modalOverlay.classList.remove('hidden'); // 🔥 THIS
            modalOverlay.classList.add('flex');
        
            const emailObj = JSON.parse(investor.email || '{}');
            const primaryEmail = emailObj.primary || 'N/A';
            
            modalTitle.innerHTML = "First Time Credentials"
        
            modalContent.innerHTML = `
                <div class="space-y-4">
                    <div>
                        <p class="text-sm text-gray-500">Investor ID</p>
                        <p class="text-lg font-semibold text-gray-800">${investor.sys_id}</p>
                    </div>
        
                    <div>
                        <p class="text-sm text-gray-500">Email</p>
                        <p class="text-lg font-semibold text-gray-800">${primaryEmail}</p>
                    </div>
        
                    <div class="pt-3 border-t">
                        <p class="text-xs text-red-500">
                            ⚠️ Password security reasons এর জন্য দেখানো হচ্ছে না
                        </p>
                    </div>
                </div>
            `;
        }

        modalClose.addEventListener('click', () => {
            modalOverlay.classList.add('hidden');
            modalOverlay.classList.remove('flex');
        });
    </script>
</body>

</html>