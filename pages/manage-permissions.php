<?php
include_once('./authenticate.php');
$ip_port = trim(@file_get_contents('../ippath.txt') ?: 'http://103.104.219.3:898', '/');

// Only a super-admin may even load this page.
if (($_SESSION['role'] ?? null) !== '0') {
    http_response_code(403);
    die('<div style="font-family:Arial,sans-serif;padding:60px;text-align:center;color:#666;">
            <h2 style="color:#dc2626;">Access Restricted</h2>
            <p>Only a super-admin can manage permissions.</p>
        </div>');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Permissions — TravHub</title>
    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-gray-50">
<?php include '../elements/header.php'; ?>
<?php include '../elements/aside.php'; ?>
<?php include '../elements/preview-model.php'; ?>

<main id="mainContent" class="pt-16 pl-64 transition-all duration-300">
<div class="p-6">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-user-shield text-indigo-600 mr-2"></i>Manage Permissions</h1>
        <p class="text-sm text-gray-500 mt-1">Full Accounting Access — grant or revoke per employee, independent of department or designation</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500">Total Employees</p><p class="text-2xl font-bold text-gray-800 mt-1" id="sum-total">—</p>
        </div>
        <div class="bg-indigo-600 rounded-xl p-4 text-white">
            <p class="text-xs text-indigo-100">With Full Accounting Access</p><p class="text-2xl font-bold mt-1" id="sum-granted">—</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <label class="text-xs text-gray-500 block mb-1">Filter by Department</label>
            <select id="f-department" onchange="renderList()" class="w-full text-sm border-none p-0 focus:ring-0">
                <option value="">All Departments</option>
            </select>
        </div>
    </div>

    <div id="loadingBar" class="hidden text-center py-8"><i class="fas fa-spinner fa-spin text-2xl text-indigo-500"></i></div>

    <div id="dept-groups" class="space-y-4"></div>

</div>
</main>

<script>
const API_LIST = "<?php echo $ip_port; ?>/api/permissions/list.php?permission_key=full_accounting_access";
const API_TOGGLE = "<?php echo $ip_port; ?>/api/permissions/toggle.php";
let _byDepartment = {};
let _allEmployees = [];

function setLoading(b) { document.getElementById('loadingBar').classList.toggle('hidden', !b); }

async function loadPermissions() {
    setLoading(true);
    try {
        const r = await fetch(API_LIST);
        const j = await r.json();
        if (!j.success) { alert(j.message || 'Failed to load'); return; }

        _byDepartment = j.by_department || {};
        _allEmployees = j.employees || [];

        document.getElementById('sum-total').textContent = j.total_count;
        document.getElementById('sum-granted').textContent = j.granted_count;

        const deptSelect = document.getElementById('f-department');
        deptSelect.innerHTML = '<option value="">All Departments</option>' +
            Object.keys(_byDepartment).map(d => `<option value="${d}">${d}</option>`).join('');

        renderList();
    } finally { setLoading(false); }
}

function renderList() {
    const filterDept = document.getElementById('f-department').value;
    const departments = filterDept ? { [filterDept]: _byDepartment[filterDept] || [] } : _byDepartment;

    const container = document.getElementById('dept-groups');
    container.innerHTML = Object.entries(departments).map(([dept, employees]) => `
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="p-3 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-700">${dept}</h3>
                <span class="text-xs text-gray-400">${employees.filter(e=>e.has_access).length} / ${employees.length} have access</span>
            </div>
            <div class="divide-y divide-gray-100">
                ${employees.map(emp => `
                    <div class="flex items-center justify-between px-4 py-3">
                        <div>
                            <p class="text-sm font-medium text-gray-800">${emp.name}</p>
                            <p class="text-xs text-gray-400">${emp.sys_id}${emp.has_access && emp.granted_by ? ` · granted by ${emp.granted_by}` : ''}</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only peer" ${emp.has_access ? 'checked' : ''} onchange="togglePermission('${emp.sys_id}', this.checked, this)">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>
                `).join('')}
            </div>
        </div>
    `).join('') || '<p class="text-center text-gray-400 py-8">কোনো employee পাওয়া যায়নি</p>';
}

async function togglePermission(employeeSysId, grant, checkboxEl) {
    checkboxEl.disabled = true;
    try {
        const res = await fetch(API_TOGGLE, {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ employee_sys_id: employeeSysId, permission_key: 'full_accounting_access', grant }),
        });
        const json = await res.json();
        if (!json.success) {
            alert(json.message || 'Failed');
            checkboxEl.checked = !grant; // revert on failure
        } else {
            loadPermissions(); // refresh counts
        }
    } catch(e) {
        alert('Network error');
        checkboxEl.checked = !grant;
    } finally {
        checkboxEl.disabled = false;
    }
}

document.addEventListener('DOMContentLoaded', loadPermissions);
</script>
</body>
</html>