<?php
include_once('./authenticate.php');
$ip_port = trim(@file_get_contents('../ippath.txt') ?: 'http://103.104.219.3:898', '/');

// Only a super-admin may even load this page. (string) cast because role can
// come back from mysqli as int 0 or string '0'.
if ((string)($_SESSION['role'] ?? '') !== '0') {
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
        <p class="text-sm text-gray-500 mt-1">Choose exactly what each person can do in accounting. Click a name to open their list.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500">Employees</p><p class="text-2xl font-bold text-gray-800 mt-1" id="sum-total">—</p>
        </div>
        <div class="bg-indigo-600 rounded-xl p-4 text-white">
            <p class="text-xs text-indigo-100">With master access (everything)</p><p class="text-2xl font-bold mt-1" id="sum-master">—</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <label class="text-xs text-gray-500 block mb-1">Filter by department</label>
            <select id="f-department" onchange="render()" class="w-full text-sm border-none p-0 focus:ring-0">
                <option value="">All departments</option>
            </select>
        </div>
    </div>

    <div class="mb-6 p-3 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-800">
        <strong>Master access</strong> lets a person do everything in accounting, including on every task. Turn it on for accounts staff.
        For anyone else, tick only the specific things they need. Super-admins (role 0) can always do everything and are not affected by these switches.
    </div>

    <div id="loadingBar" class="hidden text-center py-8"><i class="fas fa-spinner fa-spin text-2xl text-indigo-500"></i></div>
    <div id="dept-groups" class="space-y-4"></div>

</div>
</main>

<script>
const API_LIST   = "<?php echo $ip_port; ?>/api/permissions/list.php";
const API_TOGGLE = "<?php echo $ip_port; ?>/api/permissions/toggle.php";

let CATALOG = {};          // group -> { key: label }
let TOTAL_KEYS = 0;
let BY_DEPT = {};
const OPEN = new Set();    // employee ids whose panel is expanded (kept across reloads)

function esc(s) { return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
function setLoading(b) { document.getElementById('loadingBar').classList.toggle('hidden', !b); }
const allKeys = () => Object.values(CATALOG).flatMap(g => Object.keys(g));

async function loadPermissions() {
    setLoading(true);
    try {
        const r = await fetch(API_LIST);
        const j = await r.json();
        if (!j.success) { alert(j.message || 'Failed to load'); return; }

        CATALOG = j.catalog; TOTAL_KEYS = j.total_keys; BY_DEPT = j.by_department || {};
        document.getElementById('sum-total').textContent = j.total_count;
        document.getElementById('sum-master').textContent = j.master_count;

        const sel = document.getElementById('f-department');
        const keep = sel.value;
        sel.innerHTML = '<option value="">All departments</option>' +
            Object.keys(BY_DEPT).map(d => `<option value="${esc(d)}">${esc(d)}</option>`).join('');
        sel.value = keep;
        render();
    } finally { setLoading(false); }
}

function panelHtml(emp) {
    const has = new Set(emp.permissions);
    const groups = Object.entries(CATALOG).map(([group, items]) => `
        <div class="mb-3">
            <p class="text-[11px] font-semibold text-gray-500 uppercase mb-1.5">${esc(group)}</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-1">
                ${Object.entries(items).map(([key, label]) => `
                    <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                        <input type="checkbox" class="rounded border-gray-300 text-indigo-600"
                               data-emp="${esc(emp.sys_id)}" data-key="${esc(key)}" ${has.has(key) ? 'checked' : ''}
                               onchange="onKeyChange(this)">
                        <span>${esc(label)}</span>
                    </label>`).join('')}
            </div>
        </div>`).join('');

    return `
        <div class="px-4 pb-4 pt-1 bg-gray-50 border-t border-gray-100 ${emp.has_master ? 'opacity-60' : ''}">
            ${emp.has_master ? '<p class="text-xs text-indigo-700 mb-3 pt-2"><i class="fas fa-circle-info mr-1"></i>Master access is on, so this person can already do everything. The boxes below only matter if you turn master access off.</p>' : '<div class="pt-2"></div>'}
            ${groups}
            <div class="flex gap-2 pt-1">
                <button onclick="setAll('${esc(emp.sys_id)}', true)"  class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-medium">Tick all</button>
                <button onclick="setAll('${esc(emp.sys_id)}', false)" class="px-3 py-1.5 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg text-xs font-medium">Clear all</button>
            </div>
        </div>`;
}

function employeeHtml(emp) {
    const open = OPEN.has(emp.sys_id);
    const badge = emp.has_master
        ? '<span class="text-xs px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700">Master access</span>'
        : `<span class="text-xs px-2 py-0.5 rounded-full ${emp.granted_count ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'}">${emp.granted_count} / ${TOTAL_KEYS}</span>`;
    return `
        <div class="border-b border-gray-100 last:border-b-0">
            <div class="flex items-center justify-between px-4 py-3">
                <div class="flex-1 cursor-pointer" onclick="toggleOpen('${esc(emp.sys_id)}')">
                    <p class="text-sm font-medium text-gray-800"><i class="fas fa-chevron-${open ? 'down' : 'right'} text-[10px] text-gray-400 mr-2"></i>${esc(emp.name)}</p>
                    <p class="text-xs text-gray-400 ml-4">${esc(emp.sys_id)}</p>
                </div>
                <div class="flex items-center gap-4">
                    ${badge}
                    <label class="flex items-center gap-2 text-xs text-gray-600 cursor-pointer" title="Everything in accounting, on every task">
                        Master
                        <span class="relative inline-flex items-center">
                            <input type="checkbox" class="sr-only peer" data-emp="${esc(emp.sys_id)}" data-key="full_accounting_access" ${emp.has_master ? 'checked' : ''} onchange="onKeyChange(this)">
                            <div class="w-9 h-5 bg-gray-200 rounded-full peer peer-checked:bg-indigo-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-full"></div>
                        </span>
                    </label>
                </div>
            </div>
            ${open ? panelHtml(emp) : ''}
        </div>`;
}

function render() {
    const filter = document.getElementById('f-department').value;
    const depts = filter ? { [filter]: BY_DEPT[filter] || [] } : BY_DEPT;
    document.getElementById('dept-groups').innerHTML = Object.entries(depts).map(([dept, emps]) => `
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="p-3 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-700">${esc(dept)}</h3>
                <span class="text-xs text-gray-400">${emps.length} employee${emps.length === 1 ? '' : 's'}</span>
            </div>
            ${emps.map(employeeHtml).join('')}
        </div>`).join('') || '<p class="text-center text-gray-400 py-8">কোনো employee পাওয়া যায়নি</p>';
}

function toggleOpen(id) { OPEN.has(id) ? OPEN.delete(id) : OPEN.add(id); render(); }

async function save(empId, keys, grant) {
    const res = await fetch(API_TOGGLE, {
        method: 'POST', headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ employee_sys_id: empId, permission_keys: keys, grant }),
    });
    return res.json();
}

async function onKeyChange(el) {
    el.disabled = true;
    try {
        const json = await save(el.dataset.emp, [el.dataset.key], el.checked);
        if (!json.success) { alert(json.message || 'Failed'); el.checked = !el.checked; }
        else await loadPermissions();
    } catch (e) { alert('Network error'); el.checked = !el.checked; }
    finally { el.disabled = false; }
}

async function setAll(empId, grant) {
    if (!grant && !confirm('Clear every specific permission for this person?')) return;
    try {
        const json = await save(empId, allKeys(), grant);
        if (!json.success) { alert(json.message || 'Failed'); return; }
        await loadPermissions();
    } catch (e) { alert('Network error'); }
}

document.addEventListener('DOMContentLoaded', loadPermissions);
</script>
</body>
</html>