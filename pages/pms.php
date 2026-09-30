<?php
include_once('./authenticate.php');
require_once __DIR__ . '/../server/db_connection.php';
require_once __DIR__ . '/../server/hrm_permissions.php';
requireHrm($pdo, 'eps_view', false);

$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898/";
}

$allEps = $ip_port . "api/eps/all-eps.php";

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
                            <h2 class="text-2xl font-semibold text-gray-800 mb-4">PMS - Payroll Management System</h2>
                            <p class="text-sm text-gray-600 mb-4">Salary Structure Setup for Individual Eps</p>
                        </div>
                        <button onclick="openAuthModal()" class="hidden md:flex w-56 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm rounded-lg shadow-md hover:shadow-lg transition-all duration-300 items-center justify-center">
                            <i class="fas fa-file-signature mr-2"></i>Disbursement Authorization
                        </button>
                        <a href="create-eps.php" class="hidden md:flex w-48 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 text-md rounded-lg shadow-md hover:shadow-lg transition-all duration-300 items-center justify-center">
                            <i class="fas fa-plus-circle mr-3"></i>Setup New Structure
                        </a>
                    </div>

                    <div class="overflow-x-auto table-container">
                        <table id="epsTable" class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sl No</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Eps ID</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee Name - ID</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Net Salary</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody id="epsTableBody" class="bg-white divide-y divide-gray-200">
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

    <!-- Disbursement Authorization Month Picker Modal -->
    <div id="authModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl shadow-2xl w-80 mx-4 p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-base font-semibold text-gray-800">
                    <i class="fas fa-file-signature text-indigo-600 mr-2"></i>Disbursement Authorization
                </h3>
                <button onclick="closeAuthModal()" class="text-gray-400 hover:text-gray-600 text-lg">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <p class="text-sm text-gray-500 mb-4">কোন মাসের জন্য authorization letter generate করবেন?</p>
            <label class="block text-xs font-medium text-gray-600 mb-1">Salary Month</label>
            <input type="month" id="authMonth" max="<?php echo date('Y-m'); ?>"
                value="<?php echo date('Y-m'); ?>"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 mb-5">
            <div class="flex gap-3">
                <button onclick="closeAuthModal()" class="flex-1 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm rounded-lg transition-colors">
                    Cancel
                </button>
                <button onclick="goToAuthorization()" class="flex-1 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm rounded-lg transition-colors">
                    <i class="fas fa-arrow-right mr-1"></i> Generate
                </button>
            </div>
        </div>
    </div>

    <!-- Floating Quick Access Tab -->
    <?php include '../elements/floating-menus.php'; ?>

    <script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>

    <script>
        const API_ALL_EPS  = "<?php echo $allEps; ?>";
        const API_BASE     = "<?php echo rtrim($ip_port, '/'); ?>";

        // Eps
        const tableBody = document.getElementById('epsTableBody');
        const modalOverlay = document.getElementById('modalOverlay');
        const modalTitle = document.getElementById('modalTitle');
        const modalContent = document.getElementById('modalContent');
        const modalClose = document.getElementById('modalClose');

        let epssData = [];
        fetch(API_ALL_EPS)
            .then(res => res.json())
            .then(data => {
                epssData = data.epsLists;
                renderDropdown(epssData);
            })
            .catch(err => console.error(err));

        function renderDropdown(list) {
            // আগের ডাটা মুছে ফেলা
            tableBody.innerHTML = '';
        
            // যদি কোনো eps না থাকে
            if (!list || list.length === 0) {
                const tr = document.createElement('tr');
        
                tr.innerHTML = `
                    <td colspan="8" class="px-6 py-10 text-center text-gray-500">
                        <div class="flex flex-col items-center gap-2">
                            <i class="fas fa-users-slash text-3xl text-gray-400"></i>
                            <p class="text-sm">No Epss Found!</p>
                        </div>
                    </td>
                `;
        
                tableBody.appendChild(tr);
                return;
            }
        
            list.forEach((eps, index) => {
                const emailObj = JSON.parse(eps.email || '{}');
                const primaryEmail = emailObj.primary || 'Unknown';

                const isActive = (eps.status || 'inactive') === 'active';
                const statusBadge = isActive
                    ? `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                           <span class="w-1.5 h-1.5 rounded-full bg-green-500 mr-1"></span>Active
                       </span>`
                    : `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-500">
                           <span class="w-1.5 h-1.5 rounded-full bg-gray-400 mr-1"></span>Inactive
                       </span>`;

                const toggleTitle  = isActive ? 'Deactivate Structure' : 'Activate Structure';
                const toggleIcon   = isActive
                    ? `<i class="fas fa-toggle-on text-green-500 text-lg"></i>`
                    : `<i class="fas fa-toggle-off text-gray-400 text-lg"></i>`;

                const netSalary = eps.net_salary
                    ? '৳ ' + parseFloat(eps.net_salary).toLocaleString('en-BD', {minimumFractionDigits: 2})
                    : '—';

                const tr = document.createElement('tr');
                tr.className = "hover:bg-gray-50";
                tr.id = `eps-row-${eps.sys_id}`;

                tr.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${index + 1}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                        <a href="show-epss.php?eps_id=${eps.sys_id}" title="Details" class="hover:underline">
                            ${eps.sys_id || 'No ID'}
                        </a>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                        <a href="show-epss.php?eps_id=${eps.sys_id}" title="Details" class="hover:underline">
                            ${eps.employee_name || 'No Name'} &mdash; ${eps.employee_id || 'ID not Found'}
                        </a>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-medium">${netSalary}</td>
                    <td class="px-6 py-4 whitespace-nowrap" id="status-cell-${eps.sys_id}">
                        ${statusBadge}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 flex items-center gap-3">
                        <a href="show-eps.php?eps_id=${eps.sys_id}" title="View Details" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="edit-eps.php?eps_id=${eps.sys_id}" title="Edit Structure" class="text-amber-500 hover:text-amber-700">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button onclick='viewFirstCredentials(${JSON.stringify(eps)})' title="Credentials" class="text-gray-500 hover:text-gray-700">
                            <i class="fa-solid fa-key"></i>
                        </button>
                        <button onclick='toggleEpsStatus("${eps.sys_id}", ${isActive})' title="${toggleTitle}" id="toggle-btn-${eps.sys_id}" class="hover:opacity-75 transition-opacity">
                            ${toggleIcon}
                        </button>
                    </td>
                `;

                tableBody.appendChild(tr);
            });
        }
        
        function viewFirstCredentials(eps) {
            modalOverlay.classList.remove('hidden'); // 🔥 THIS
            modalOverlay.classList.add('flex');
        
            const emailObj = JSON.parse(eps.email || '{}');
            const primaryEmail = emailObj.primary || 'N/A';
            
            modalTitle.innerHTML = "First Time Credentials"
        
            modalContent.innerHTML = `
                <div class="space-y-4">
                    <div>
                        <p class="text-sm text-gray-500">Eps ID</p>
                        <p class="text-lg font-semibold text-gray-800">${eps.sys_id}</p>
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

        async function toggleEpsStatus(epsId, currentlyActive) {
            const btn = document.getElementById(`toggle-btn-${epsId}`);
            const cell = document.getElementById(`status-cell-${epsId}`);
            if (!btn) return;

            // Optimistic UI — disable button while request is in-flight
            btn.disabled = true;
            btn.style.opacity = '0.5';

            try {
                const res = await fetch(API_BASE + '/api/eps/toggle-eps-status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ eps_sys_id: epsId })
                });
                const json = await res.json();

                if (!json.success) {
                    alert(json.message || 'Status update failed.');
                    btn.disabled = false;
                    btn.style.opacity = '1';
                    return;
                }

                // Update badge
                const nowActive = json.new_status === 'active';
                cell.innerHTML = nowActive
                    ? `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                           <span class="w-1.5 h-1.5 rounded-full bg-green-500 mr-1"></span>Active
                       </span>`
                    : `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-500">
                           <span class="w-1.5 h-1.5 rounded-full bg-gray-400 mr-1"></span>Inactive
                       </span>`;

                // Update toggle button
                btn.title    = nowActive ? 'Deactivate Structure' : 'Activate Structure';
                btn.innerHTML = nowActive
                    ? `<i class="fas fa-toggle-on text-green-500 text-lg"></i>`
                    : `<i class="fas fa-toggle-off text-gray-400 text-lg"></i>`;
                btn.onclick  = () => toggleEpsStatus(epsId, nowActive);

            } catch (e) {
                alert('Network error. Please try again.');
            } finally {
                btn.disabled = false;
                btn.style.opacity = '1';
            }
        }

        function openAuthModal() {
            document.getElementById('authModal').classList.remove('hidden');
        }
        function closeAuthModal() {
            document.getElementById('authModal').classList.add('hidden');
        }
        function goToAuthorization() {
            const month = document.getElementById('authMonth').value;
            if (!month) { alert('Please select a month.'); return; }
            window.open('generate-bulk-authorization.php?month=' + encodeURIComponent(month), '_blank');
            closeAuthModal();
        }
    </script>
</body>

</html>