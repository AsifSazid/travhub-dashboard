<?php
include_once('./authenticate.php');
require_once __DIR__ . '/../server/db_connection.php';
require_once __DIR__ . '/../server/hrm_permissions.php';
requireHrm($pdo, 'hrm_employee_view', false);

$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898";
}

$allEmployee = $ip_port . "api/employees/all-employees.php";

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
                            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Employee Lists</h2>
                        </div>
                        <a href="create-employee.php" class="hidden md:flex w-48 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 text-md rounded-lg shadow-md hover:shadow-lg transition-all duration-300 items-center justify-center">
                            <i class="fas fa-plus-circle mr-3"></i>Add New Employee
                        </a>
                    </div>

                    <div class="overflow-x-auto table-container">
                        <table id="employeeTable" class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sl No</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee ID</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee Name</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone No</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody id="employeeTableBody" class="bg-white divide-y divide-gray-200">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- General Modal -->
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
                    <p>Modal content goes here.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Appointment Letter Type Modal -->
    <div id="apptModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl shadow-2xl w-80 mx-4">
            <div class="p-5">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-base font-semibold text-gray-800">Appointment Letter Type</h3>
                    <button onclick="closeApptModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
                </div>
                <p class="text-sm text-gray-500 mb-4">Select the letter type for <span id="apptEmpName" class="font-semibold text-gray-700"></span></p>
                <div class="space-y-2">
                    <button onclick="openAppointmentLetter('permanent')" class="w-full text-left px-4 py-3 rounded-lg border border-gray-200 hover:bg-blue-50 hover:border-blue-300 text-sm font-medium text-gray-700 transition">
                        <i class="fas fa-user-check mr-2 text-blue-500"></i>Permanent Employee
                    </button>
                    <button onclick="openAppointmentLetter('probationary')" class="w-full text-left px-4 py-3 rounded-lg border border-gray-200 hover:bg-yellow-50 hover:border-yellow-300 text-sm font-medium text-gray-700 transition">
                        <i class="fas fa-user-clock mr-2 text-yellow-500"></i>Probationary Employee
                    </button>
                    <button onclick="openAppointmentLetter('intern')" class="w-full text-left px-4 py-3 rounded-lg border border-gray-200 hover:bg-green-50 hover:border-green-300 text-sm font-medium text-gray-700 transition">
                        <i class="fas fa-user-graduate mr-2 text-green-500"></i>Intern
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Salary Certificate Modal -->
    <div id="salaryModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl shadow-2xl w-96 mx-4">
            <div class="p-5">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-base font-semibold text-gray-800">Salary Certificate</h3>
                    <button onclick="closeSalaryModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
                </div>
                <p class="text-sm text-gray-500 mb-4">For: <span id="salaryEmpName" class="font-semibold text-gray-700"></span></p>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Certificate Period (optional)</label>
                    <p class="text-xs text-gray-400 mb-3">Leave blank to issue as of today's date without a specific period.</p>
                    <div class="flex gap-2">
                        <div class="flex-1">
                            <label class="text-xs text-gray-500 mb-1 block">From</label>
                            <input type="month" id="salaryFrom" max="<?php echo date('Y-m'); ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-400">
                        </div>
                        <div class="flex-1">
                            <label class="text-xs text-gray-500 mb-1 block">To</label>
                            <input type="month" id="salaryTo" max="<?php echo date('Y-m'); ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-400">
                        </div>
                    </div>
                </div>

                <button onclick="openSalaryCertificate()" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-semibold py-2.5 rounded-lg text-sm transition">
                    <i class="fas fa-file-invoice-dollar mr-2"></i>Generate Salary Certificate
                </button>
            </div>
        </div>
    </div>

    <!-- NOC Modal -->
    <div id="nocModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl shadow-2xl w-96 mx-4">
            <div class="p-5">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-base font-semibold text-gray-800">NOC — No Objection Certificate</h3>
                    <button onclick="closeNocModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
                </div>
                <p class="text-sm text-gray-500 mb-4">For: <span id="nocEmpName" class="font-semibold text-gray-700"></span></p>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Purpose / Reason <span class="text-red-500">*</span></label>
                    <select id="nocPurpose" onchange="handleNocPurpose()" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <option value="">— Select purpose —</option>
                        <option value="visa">Visa Application</option>
                        <option value="bank">Bank / Financial Institution</option>
                        <option value="education">Higher Education / Study</option>
                        <option value="travel">Personal / Business Travel</option>
                        <option value="govt">Government / Embassy Purpose</option>
                        <option value="resigned">Employee Has Resigned (Job Ended)</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div id="nocOtherWrap" class="mb-4 hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Specify Reason</label>
                    <input type="text" id="nocOtherText" placeholder="e.g. Medical certificate requirement"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>

                <div id="nocDateWrap" class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date Range (optional)</label>
                    <div class="flex gap-2">
                        <div class="flex-1">
                            <label class="text-xs text-gray-500">From</label>
                            <input type="date" id="nocFrom" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                        </div>
                        <div class="flex-1">
                            <label class="text-xs text-gray-500">To</label>
                            <input type="date" id="nocTo" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Leave blank to omit date range from the certificate.</p>
                </div>

                <div id="nocResignedNote" class="hidden mb-4 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                    <p class="text-xs text-amber-700"><i class="fas fa-info-circle mr-1"></i>Date range is not applicable for resignation NOC — the letter will state the employee's last working date instead.</p>
                </div>

                <button onclick="openNocLetter()" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 rounded-lg text-sm transition">
                    <i class="fas fa-file-alt mr-2"></i>Generate NOC
                </button>
            </div>
        </div>
    </div>

    <!-- Floating Quick Access Tab -->
    <?php include '../elements/floating-menus.php'; ?>

    <script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>

    <script>
        const API_URL_FOR_ALL_CLIENTS = "<?php echo $allEmployee; ?>";

        // Employee
        const tableBody = document.getElementById('employeeTableBody');
        const modalOverlay = document.getElementById('modalOverlay');
        const modalTitle = document.getElementById('modalTitle');
        const modalContent = document.getElementById('modalContent');
        const modalClose = document.getElementById('modalClose');

        let employeesData = [];
        fetch(API_URL_FOR_ALL_CLIENTS)
            .then(res => res.json())
            .then(data => {
                employeesData = data.employees;
                renderDropdown(employeesData);
            })
            .catch(err => console.error(err));

        function renderDropdown(list) {
            // আগের ডাটা মুছে ফেলা
            tableBody.innerHTML = '';
        
            // যদি কোনো employee না থাকে
            if (!list || list.length === 0) {
                const tr = document.createElement('tr');
        
                tr.innerHTML = `
                    <td colspan="8" class="px-6 py-10 text-center text-gray-500">
                        <div class="flex flex-col items-center gap-2">
                            <i class="fas fa-users-slash text-3xl text-gray-400"></i>
                            <p class="text-sm">No Employees Found!</p>
                        </div>
                    </td>
                `;
        
                tableBody.appendChild(tr);
                return;
            }
        
            list.forEach((employee, index) => {
                const phoneObj = JSON.parse(employee.phone || '{}');
                const primaryPhone = phoneObj.primary_no || 'Unknown';
        
                const emailObj = JSON.parse(employee.email || '{}');
                const primaryEmail = emailObj.primary || 'Unknown';
        
                const tr = document.createElement('tr');
                tr.className = "hover:bg-gray-50";
        
                tr.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${index + 1}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                        <a href="show-employees.php?employee_id=${employee.sys_id}" title="Details">
                            ${employee.sys_id || 'No ID'}
                        </a>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                        <a href="show-employees.php?employee_id=${employee.sys_id}" title="Details">
                            ${employee.name || 'No Name'}
                        </a>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">${primaryPhone}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${primaryEmail}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 uppercase">${employee.department_name || 'Unknown'}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 uppercase">${employee.type || 'Unknown'}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <div class="flex items-center gap-3 flex-wrap">
                            <a href="show-employees.php?employee_id=${employee.sys_id}" title="View Profile" class="text-gray-500 hover:text-blue-600">
                                <i class="fas fa-eye"></i>
                            </a>
                            <button onclick='viewFirstCredentials(${JSON.stringify(employee)})' title="Credentials" class="text-gray-500 hover:text-yellow-600">
                                <i class="fa-solid fa-key"></i>
                            </button>
                            <a href="edit-employee.php?sys_id=${employee.sys_id}" title="Edit" class="text-gray-500 hover:text-green-600">
                                <i class="fas fa-pen"></i>
                            </a>
                            <div class="relative inline-block" x-data="undefined">
                                <button onclick="toggleDocMenu('${employee.sys_id}')" title="Documents" class="text-gray-500 hover:text-indigo-600 flex items-center gap-1 text-xs font-medium border border-gray-200 rounded px-2 py-1 hover:border-indigo-300 hover:bg-indigo-50 transition">
                                    <i class="fas fa-file-alt"></i> Docs <i class="fas fa-chevron-down text-[10px]"></i>
                                </button>
                                <div id="doc-menu-${employee.sys_id}" class="hidden absolute right-0 top-8 bg-white border border-gray-200 rounded-lg shadow-lg z-20 w-52 py-1">
                                    <a href="generate-id-card.php?employee_id=${employee.sys_id}" target="_blank"
                                       class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50">
                                        <i class="fas fa-id-card text-blue-500 w-4"></i> ID Card
                                    </a>
                                    <button onclick="openAppointmentLetterAuto('${employee.sys_id}', '${(employee.type || '').replace(/'/g,"\\'")}')"
                                            class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50">
                                        <i class="fas fa-file-signature text-green-500 w-4"></i> Appointment Letter
                                    </button>
                                    <button onclick="openSalaryModal('${employee.sys_id}', '${employee.name?.replace(/'/g,"\\'")}')"
                                            class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50">
                                        <i class="fas fa-money-check-alt text-purple-500 w-4"></i> Salary Certificate
                                    </button>
                                    <button onclick="openNocModal('${employee.sys_id}', '${employee.name?.replace(/'/g,"\\'")}')"
                                            class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50">
                                        <i class="fas fa-stamp text-orange-500 w-4"></i> NOC
                                    </button>
                                </div>
                            </div>
                        </div>
                    </td>
                `;
        
                tableBody.appendChild(tr);
            });
        }
        
        function viewFirstCredentials(employee) {
            modalOverlay.classList.remove('hidden'); // 🔥 THIS
            modalOverlay.classList.add('flex');
        
            const emailObj = JSON.parse(employee.email || '{}');
            const primaryEmail = emailObj.primary || 'N/A';
            
            modalTitle.innerHTML = "First Time Credentials"
        
            modalContent.innerHTML = `
                <div class="space-y-4">
                    <div>
                        <p class="text-sm text-gray-500">Employee ID</p>
                        <p class="text-lg font-semibold text-gray-800">${employee.sys_id}</p>
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

        // ── Doc dropdown ──────────────────────────────────────
        let _openDocMenu = null;
        function toggleDocMenu(sysId) {
            const menu = document.getElementById('doc-menu-' + sysId);
            if (!menu) return;
            const isOpen = !menu.classList.contains('hidden');
            if (_openDocMenu && _openDocMenu !== menu) _openDocMenu.classList.add('hidden');
            menu.classList.toggle('hidden', isOpen);
            _openDocMenu = isOpen ? null : menu;
        }
        document.addEventListener('click', e => {
            if (_openDocMenu && !e.target.closest('[id^="doc-menu-"]') && !e.target.closest('button[onclick^="toggleDocMenu"]')) {
                _openDocMenu.classList.add('hidden');
                _openDocMenu = null;
            }
        });

        // ── Appointment Letter (auto-detect type from employee.type) ──
        const KNOWN_TYPES = ['permanent', 'probationary', 'intern'];
        function openAppointmentLetterAuto(sysId, empType) {
            if (_openDocMenu) { _openDocMenu.classList.add('hidden'); _openDocMenu = null; }
            const type = KNOWN_TYPES.includes(empType.toLowerCase()) ? empType.toLowerCase() : null;
            if (type) {
                window.open(`generate-appointment-letter.php?employee_id=${encodeURIComponent(sysId)}&letter_type=${type}`, '_blank');
            } else {
                // fallback modal if type unknown
                _apptEmpId = sysId;
                document.getElementById('apptEmpName').textContent = sysId;
                document.getElementById('apptModal').classList.remove('hidden');
            }
        }
        let _apptEmpId = '';
        function closeApptModal() { document.getElementById('apptModal').classList.add('hidden'); }
        function openAppointmentLetter(type) {
            closeApptModal();
            window.open(`generate-appointment-letter.php?employee_id=${encodeURIComponent(_apptEmpId)}&letter_type=${type}`, '_blank');
        }

        // ── Salary Certificate Modal ──────────────────────────
        let _salaryEmpId = '';
        function openSalaryModal(sysId, name) {
            if (_openDocMenu) { _openDocMenu.classList.add('hidden'); _openDocMenu = null; }
            _salaryEmpId = sysId;
            document.getElementById('salaryEmpName').textContent = name;
            document.getElementById('salaryFrom').value = '';
            document.getElementById('salaryTo').value = '';
            document.getElementById('salaryModal').classList.remove('hidden');
        }
        function closeSalaryModal() { document.getElementById('salaryModal').classList.add('hidden'); }
        function openSalaryCertificate() {
            const from = document.getElementById('salaryFrom').value;
            const to   = document.getElementById('salaryTo').value;
            const params = new URLSearchParams({ employee_id: _salaryEmpId });
            if (from) params.set('date_from', from);
            if (to)   params.set('date_to', to);
            closeSalaryModal();
            window.open(`generate-salary-certificate.php?${params.toString()}`, '_blank');
        }

        // ── NOC Modal ─────────────────────────────────────────
        let _nocEmpId = '';
        function openNocModal(sysId, name) {
            if (_openDocMenu) { _openDocMenu.classList.add('hidden'); _openDocMenu = null; }
            _nocEmpId = sysId;
            document.getElementById('nocEmpName').textContent = name;
            // reset fields
            document.getElementById('nocPurpose').value = '';
            document.getElementById('nocOtherWrap').classList.add('hidden');
            document.getElementById('nocDateWrap').classList.remove('hidden');
            document.getElementById('nocResignedNote').classList.add('hidden');
            document.getElementById('nocFrom').value = '';
            document.getElementById('nocTo').value = '';
            document.getElementById('nocModal').classList.remove('hidden');
        }
        function closeNocModal() { document.getElementById('nocModal').classList.add('hidden'); }
        function handleNocPurpose() {
            const val = document.getElementById('nocPurpose').value;
            document.getElementById('nocOtherWrap').classList.toggle('hidden', val !== 'other');
            const isResigned = val === 'resigned';
            document.getElementById('nocDateWrap').classList.toggle('hidden', isResigned);
            document.getElementById('nocResignedNote').classList.toggle('hidden', !isResigned);
        }
        function openNocLetter() {
            const purpose = document.getElementById('nocPurpose').value;
            if (!purpose) { alert('Please select a purpose.'); return; }
            const other  = document.getElementById('nocOtherText').value.trim();
            const from   = document.getElementById('nocFrom').value;
            const to     = document.getElementById('nocTo').value;
            const params = new URLSearchParams({ employee_id: _nocEmpId, purpose });
            if (purpose === 'other' && other) params.set('other_reason', other);
            if (purpose !== 'resigned') {
                if (from) params.set('date_from', from);
                if (to)   params.set('date_to', to);
            }
            closeNocModal();
            window.open(`generate-noc.php?${params.toString()}`, '_blank');
        }
    </script>
</body>

</html>