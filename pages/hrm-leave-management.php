<?php
// PATH: pages/hrm-leave-management.php
// HR Leave Management — allocations + applications (approve / reject)

include_once('./authenticate.php');
require_once __DIR__ . '/../server/db_connection.php';
require_once __DIR__ . '/../server/permissions.php';

// HR gate
if (!canAccess($pdo, 'hrm_employee_view') && ($_SESSION['user_role'] ?? '') !== '0') {
    header("Location: index.php");
    exit;
}

$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) { $ip_port = "https://dev.travhub.com.bd/"; }
$ip_port  = rtrim($ip_port, '/') . '/';
$API_BASE = rtrim($ip_port, '/');

$currentYear = (int)date('Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Management — TravHub HR</title>
    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png" sizes="16x16">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .hlm-tab-btn { padding:8px 16px; font-size:.85rem; font-weight:500; border-bottom:2px solid transparent; color:#64748b; background:transparent; border-top:none; border-left:none; border-right:none; cursor:pointer; white-space:nowrap; transition:color .15s,border-color .15s; }
        .hlm-tab-btn:hover { color:#1e293b; border-bottom-color:#e2e8f0; }
        .hlm-tab-btn.active { color:#2563eb; border-bottom-color:#2563eb; }
        .hlm-modal-bg { position:fixed; inset:0; background:rgba(0,0,0,.45); display:flex; align-items:center; justify-content:center; z-index:9999; padding:16px; }
        .hlm-modal { background:#fff; border-radius:16px; width:100%; max-width:480px; max-height:90vh; overflow-y:auto; padding:24px; box-shadow:0 20px 60px rgba(0,0,0,.2); }
        .hlm-tbl td, .hlm-tbl th { padding:10px 12px; white-space:nowrap; }
        .hlm-tbl th { font-size:.75rem; font-weight:600; text-transform:uppercase; letter-spacing:.04em; color:#94a3b8; background:#f8fafc; }
        .hlm-tbl tr:not(:last-child) td { border-bottom:1px solid #f1f5f9; }
        .hlm-tbl tr:hover td { background:#f8fafc; }
        .badge { display:inline-flex; align-items:center; padding:2px 10px; border-radius:99px; font-size:.72rem; font-weight:600; }
        .badge-pending  { background:#fef3c7; color:#92400e; }
        .badge-approved { background:#d1fae5; color:#065f46; }
        .badge-rejected { background:#fee2e2; color:#991b1b; }
        .badge-cancelled{ background:#f1f5f9; color:#64748b; }
    </style>
</head>
<body class="bg-gray-50 font-sans">

<?php include '../elements/header.php'; ?>
<?php include '../elements/aside.php'; ?>

<main id="mainContent" class="pt-16 pl-0 lg:pl-64 lg:my-16 transition-all duration-300">
<div class="p-4 md:p-6 max-w-screen-xl mx-auto">

    <!-- Page header -->
    <div class="mb-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-center gap-3">
            <i class="fas fa-umbrella-beach text-2xl text-blue-500"></i>
            <div>
                <h1 class="text-xl font-bold text-gray-800">Leave Management</h1>
                <p class="text-sm text-gray-500">Allocations, applications and approvals</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <label class="text-sm text-gray-500">Year</label>
            <select id="hlmYear" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                <?php for($y = $currentYear+1; $y >= $currentYear-2; $y--): ?>
                <option value="<?= $y ?>" <?= $y===$currentYear?'selected':'' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
    </div>

    <!-- Tab bar -->
    <div class="bg-white rounded-2xl shadow overflow-hidden">
        <div class="border-b border-gray-100 flex overflow-x-auto px-4">
            <button class="hlm-tab-btn active" data-hlmtab="applications">
                <i class="fas fa-list-check mr-1.5"></i>Applications
            </button>
            <button class="hlm-tab-btn" data-hlmtab="allocations">
                <i class="fas fa-sliders mr-1.5"></i>Leave Allocations
            </button>
            <button class="hlm-tab-btn" data-hlmtab="summary">
                <i class="fas fa-chart-bar mr-1.5"></i>Team Summary
            </button>
            <button class="hlm-tab-btn" data-hlmtab="holidays">
                <i class="fas fa-calendar-star mr-1.5"></i>Holidays
            </button>
        </div>

        <!-- ── Applications tab ── -->
        <div id="hlmtab-applications" class="p-5">
            <!-- Filters -->
            <div class="flex flex-wrap gap-3 mb-4">
                <select id="hlmFilterStatus" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <option value="">All Statuses</option>
                    <option value="pending" selected>Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <input type="text" id="hlmFilterSearch" placeholder="Search employee…"
                    class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 flex-1 min-w-[150px]">
                <button id="hlmFilterApply" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                    <i class="fas fa-filter mr-1.5"></i>Filter
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="hlm-tbl w-full text-sm">
                    <thead>
                        <tr>
                            <th class="text-left">Employee</th>
                            <th class="text-left">Leave Type</th>
                            <th class="text-left">From</th>
                            <th class="text-left">To</th>
                            <th class="text-center">Days</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Applied On</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="hlmAppsTbody">
                        <tr><td colspan="8" class="text-center py-8 text-gray-400"><i class="fas fa-spinner fa-spin mr-2"></i>Loading…</td></tr>
                    </tbody>
                </table>
            </div>
            <div id="hlmAppsEmpty" class="hidden text-center py-10 text-gray-400 text-sm">
                <i class="fas fa-inbox text-3xl mb-2 block text-gray-200"></i>No applications found.
            </div>
        </div>

        <!-- ── Allocations tab ── -->
        <div id="hlmtab-allocations" class="p-5" style="display:none">
            <div class="flex flex-wrap gap-3 mb-4">
                <select id="hlmAllocEmp" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 flex-1 min-w-[200px]">
                    <option value="">— Select Employee —</option>
                </select>
                <button id="hlmAllocLoad" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                    <i class="fas fa-eye mr-1.5"></i>Load Balances
                </button>
            </div>

            <div id="hlmAllocTable" class="hidden">
                <p id="hlmAllocEmpName" class="text-sm font-semibold text-gray-700 mb-3"></p>
                <div class="overflow-x-auto">
                    <table class="hlm-tbl w-full text-sm">
                        <thead>
                            <tr>
                                <th class="text-left">Leave Type</th>
                                <th class="text-center">Allocated</th>
                                <th class="text-center">Used</th>
                                <th class="text-center">Pending</th>
                                <th class="text-center">Remaining</th>
                                <th class="text-center">Set Allocation</th>
                            </tr>
                        </thead>
                        <tbody id="hlmAllocTbody"></tbody>
                    </table>
                </div>
            </div>
            <div id="hlmAllocEmpty" class="text-center py-10 text-gray-400 text-sm">
                <i class="fas fa-sliders text-3xl mb-2 block text-gray-200"></i>Select an employee to view and edit leave allocations.
            </div>
        </div>

        <!-- ── Summary tab ── -->
        <div id="hlmtab-summary" class="p-5" style="display:none">
            <p class="text-sm text-gray-500 mb-4">Leave usage summary for all active employees this year.</p>
            <div id="hlmSummaryCards" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                <div class="col-span-full text-center py-8 text-gray-400 text-sm">
                    <i class="fas fa-spinner fa-spin mr-2"></i>Loading…
                </div>
            </div>
        </div>

        <!-- ── Holidays tab ── -->
        <div id="hlmtab-holidays" class="p-5" style="display:none">
            <!-- Year selector + Add button -->
            <div class="flex flex-wrap items-center gap-3 mb-5">
                <select id="hlmHolYear" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                </select>
                <span class="text-sm text-gray-400 flex-1" id="hlmHolCount"></span>
                <button id="hlmHolAddBtn" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition flex items-center gap-1.5">
                    <i class="fas fa-plus"></i> Add Holiday
                </button>
            </div>

            <!-- Add/Edit form (hidden by default) -->
            <div id="hlmHolForm" class="hidden mb-5 p-4 bg-blue-50 rounded-xl border border-blue-100">
                <h4 id="hlmHolFormTitle" class="text-sm font-bold text-gray-700 mb-3">Add Holiday</h4>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                    <div>
                        <label class="text-xs font-semibold text-gray-500 block mb-1">Date <span class="text-red-500">*</span></label>
                        <input type="date" id="hlmHolDate" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="text-xs font-semibold text-gray-500 block mb-1">Title <span class="text-red-500">*</span></label>
                        <input type="text" id="hlmHolTitle" maxlength="200" placeholder="e.g. Eid ul-Fitr" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-gray-500 block mb-1">Type</label>
                        <select id="hlmHolType" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                            <option value="public_holiday">Public Holiday</option>
                            <option value="office_closed">Office Closed</option>
                            <option value="optional">Optional</option>
                        </select>
                    </div>
                </div>
                <input type="hidden" id="hlmHolSysId" value="">
                <div id="hlmHolFormAlert" class="hidden mb-3 p-2 rounded-lg text-sm font-medium"></div>
                <div class="flex gap-2">
                    <button id="hlmHolSave" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition flex items-center gap-1.5">
                        <i class="fas fa-save"></i> Save
                    </button>
                    <button id="hlmHolCancel" class="border border-gray-200 text-gray-500 hover:bg-gray-50 text-sm font-semibold px-4 py-2 rounded-lg transition">
                        Cancel
                    </button>
                </div>
            </div>

            <!-- Holidays table -->
            <div class="overflow-x-auto">
                <table class="hlm-tbl w-full text-sm">
                    <thead>
                        <tr>
                            <th class="text-left">Date</th>
                            <th class="text-left">Day</th>
                            <th class="text-left">Title</th>
                            <th class="text-left">Type</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="hlmHolTbody">
                        <tr><td colspan="5" class="text-center py-8 text-gray-400"><i class="fas fa-spinner fa-spin mr-2"></i>Loading…</td></tr>
                    </tbody>
                </table>
            </div>
            <div id="hlmHolEmpty" class="hidden text-center py-10 text-gray-400 text-sm">
                <i class="fas fa-calendar-times text-3xl mb-2 block text-gray-200"></i>No holidays defined for this year.
            </div>
        </div>

    </div><!-- /card -->

</div>
</main>

<!-- ── Review Modal (approve / reject) ── -->
<div id="hlmReviewModal" class="hlm-modal-bg" style="display:none">
    <div class="hlm-modal">
        <div class="flex items-center justify-between mb-5">
            <h3 id="hlmReviewTitle" class="text-base font-bold text-gray-800"></h3>
            <button id="hlmReviewClose" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <div id="hlmReviewInfo" class="mb-4 p-3 bg-gray-50 rounded-xl text-sm text-gray-700"></div>
        <div id="hlmReviewAlert" class="hidden mb-4 p-3 rounded-lg text-sm font-medium"></div>
        <div class="mb-4">
            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide block mb-1">Note (optional)</label>
            <textarea id="hlmReviewNote" rows="3" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="Reason / remarks…"></textarea>
        </div>
        <div class="flex gap-3">
            <button id="hlmApproveBtn" class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg px-4 py-2.5 text-sm transition flex items-center justify-center gap-2">
                <i class="fas fa-check"></i> Approve
            </button>
            <button id="hlmRejectBtn" class="flex-1 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg px-4 py-2.5 text-sm transition flex items-center justify-center gap-2">
                <i class="fas fa-times"></i> Reject
            </button>
        </div>
    </div>
</div>

<?php include '../elements/floating-menus.php'; ?>
<script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>
<script src="../assets/js/functional/dashboard.js?time=<?php echo time(); ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    const API_BASE = "<?= $API_BASE ?>";
    let currentYear = <?= $currentYear ?>;
    let employees   = [];
    let leaveTypes  = [];
    let currentLaId = null;

    const yearSel = document.getElementById('hlmYear');
    yearSel.addEventListener('change', () => { currentYear = parseInt(yearSel.value); refreshActive(); });

    async function apiPost(endpoint, body) {
        const res = await fetch(API_BASE + endpoint, {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify(body)
        });
        return res.json();
    }

    /* ── Tabs ── */
    const tabBtns  = document.querySelectorAll('.hlm-tab-btn');
    const tabPanes = { applications: document.getElementById('hlmtab-applications'), allocations: document.getElementById('hlmtab-allocations'), summary: document.getElementById('hlmtab-summary'), holidays: document.getElementById('hlmtab-holidays') };

    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            tabBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            Object.values(tabPanes).forEach(p => { if(p) p.style.display='none'; });
            const key = this.getAttribute('data-hlmtab');
            if (tabPanes[key]) tabPanes[key].style.display = 'block';
            if (key === 'allocations' && !employees.length) loadEmployees();
            if (key === 'summary') loadSummary();
            if (key === 'holidays') loadHolidays();
        });
    });

    function refreshActive() {
        const activeTab = document.querySelector('.hlm-tab-btn.active')?.getAttribute('data-hlmtab');
        if (activeTab === 'applications') loadApplications();
        else if (activeTab === 'summary') loadSummary();
    }

    /* ── Applications ── */
    document.getElementById('hlmFilterApply').addEventListener('click', loadApplications);
    document.getElementById('hlmFilterSearch').addEventListener('keydown', e => { if(e.key==='Enter') loadApplications(); });

    async function loadApplications() {
        const tbody  = document.getElementById('hlmAppsTbody');
        const empty  = document.getElementById('hlmAppsEmpty');
        const status = document.getElementById('hlmFilterStatus').value;
        const search = document.getElementById('hlmFilterSearch').value.trim().toLowerCase();

        tbody.innerHTML = '<tr><td colspan="8" class="text-center py-8 text-gray-400"><i class="fas fa-spinner fa-spin mr-2"></i>Loading…</td></tr>';
        empty.classList.add('hidden');

        try {
            const data = await apiPost('/api/leaves/endpoints.php', {action:'list_all', year:currentYear, status});
            if (!data.success) { tbody.innerHTML='<tr><td colspan="8" class="text-center py-6 text-red-500">'+data.message+'</td></tr>'; return; }

            let rows = data.data;
            if (search) rows = rows.filter(r => (r.employee_name||'').toLowerCase().includes(search) || (r.employee_id||'').toLowerCase().includes(search));

            if (!rows.length) { tbody.innerHTML=''; empty.classList.remove('hidden'); return; }

            const BADGE = {pending:'badge-pending',approved:'badge-approved',rejected:'badge-rejected',cancelled:'badge-cancelled'};
            tbody.innerHTML = rows.map(r => `
                <tr>
                    <td>
                        <div class="font-medium text-gray-800">${esc(r.employee_name)}</div>
                        <div class="text-xs text-gray-400">${esc(r.employee_id)}</div>
                    </td>
                    <td>
                        <span class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background:${r.color||'#94a3b8'}"></span>
                            ${esc(r.leave_name)}
                        </span>
                    </td>
                    <td>${r.date_from}</td>
                    <td>${r.date_to}</td>
                    <td class="text-center font-semibold">${r.total_days}${r.bridged_days>0?'<span class="text-blue-400 font-normal text-xs"> +'+r.bridged_days+'b</span>':''}</td>
                    <td class="text-center"><span class="badge ${BADGE[r.status]||'bg-gray-100 text-gray-500'}">${r.status}</span></td>
                    <td class="text-center text-gray-500 text-xs">${r.created_at?.split(' ')[0]||'—'}</td>
                    <td class="text-center">
                        ${r.status==='pending' ? `<button class="text-xs bg-blue-50 hover:bg-blue-100 text-blue-700 font-semibold px-3 py-1.5 rounded-lg transition" onclick="hlmOpenReview('${r.sys_id}','${esc(r.employee_name)}','${esc(r.leave_name)}','${r.date_from}','${r.date_to}',${r.total_days})">Review</button>` : '<span class="text-xs text-gray-400">'+(r.reviewed_by_name||'—')+'</span>'}
                    </td>
                </tr>
            `).join('');
        } catch(_) { tbody.innerHTML='<tr><td colspan="8" class="text-center py-6 text-red-500">Failed to load.</td></tr>'; }
    }

    /* ── Review modal ── */
    const reviewModal = document.getElementById('hlmReviewModal');
    document.getElementById('hlmReviewClose').addEventListener('click', () => { reviewModal.style.display='none'; });
    reviewModal.addEventListener('click', e => { if(e.target===reviewModal) reviewModal.style.display='none'; });

    window.hlmOpenReview = function(sysId, empName, leaveName, from, to, days) {
        currentLaId = sysId;
        document.getElementById('hlmReviewTitle').innerHTML = '<i class="fas fa-clipboard-check text-blue-500 mr-2"></i>Review Leave Application';
        document.getElementById('hlmReviewInfo').innerHTML = `
            <strong>${esc(empName)}</strong> — ${esc(leaveName)}<br>
            <span class="text-gray-500">${from} → ${to} · <strong>${days} working day(s)</strong></span>`;
        document.getElementById('hlmReviewNote').value = '';
        document.getElementById('hlmReviewAlert').classList.add('hidden');
        reviewModal.style.display = 'flex';
    };

    async function doReview(action) {
        const note     = document.getElementById('hlmReviewNote').value.trim();
        const alertEl  = document.getElementById('hlmReviewAlert');
        const showA    = (msg, ok) => {
            alertEl.textContent = msg;
            alertEl.className   = 'mb-4 p-3 rounded-lg text-sm font-medium '+(ok?'bg-green-50 text-green-700 border border-green-200':'bg-red-50 text-red-700 border border-red-200');
            alertEl.classList.remove('hidden');
        };
        const btn = document.getElementById(action==='approve'?'hlmApproveBtn':'hlmRejectBtn');
        btn.disabled = true; btn.innerHTML='<i class="fas fa-spinner fa-spin"></i>';
        try {
            const data = await apiPost('/api/leaves/endpoints.php', {action, sys_id:currentLaId, note});
            if (data.success) {
                showA(data.message, true);
                setTimeout(() => { reviewModal.style.display='none'; loadApplications(); }, 1500);
            } else { showA(data.message||'Failed.', false); }
        } catch(_){ showA('Network error.', false); }
        btn.disabled = false;
        btn.innerHTML = action==='approve' ? '<i class="fas fa-check"></i> Approve' : '<i class="fas fa-times"></i> Reject';
    }

    document.getElementById('hlmApproveBtn').addEventListener('click', () => doReview('approve'));
    document.getElementById('hlmRejectBtn').addEventListener('click',  () => doReview('reject'));

    /* ── Allocations ── */
    async function loadEmployees() {
        try {
            const res  = await fetch(API_BASE + '/api/employees/get-all.php?status=active');
            const data = await res.json();
            employees  = Array.isArray(data.employees) ? data.employees : (data.data || []);
            const sel  = document.getElementById('hlmAllocEmp');
            sel.innerHTML = '<option value="">— Select Employee —</option>'
                + employees.map(e => `<option value="${e.sys_id}">${esc(e.name||e.first_name+' '+e.last_name)} (${e.sys_id})</option>`).join('');
        } catch(_) {}
    }

    async function loadLeaveTypes() {
        if (leaveTypes.length) return;
        try {
            const data = await apiPost('/api/leaves/endpoints.php', {action:'types'});
            if (data.success) leaveTypes = data.data;
        } catch(_) {}
    }

    document.getElementById('hlmAllocLoad').addEventListener('click', async function() {
        const empId = document.getElementById('hlmAllocEmp').value;
        if (!empId) { alert('Please select an employee.'); return; }

        await loadLeaveTypes();
        const emp = employees.find(e => e.sys_id === empId);

        this.disabled = true; this.innerHTML='<i class="fas fa-spinner fa-spin"></i>';
        try {
            const data = await apiPost('/api/leaves/endpoints.php', {action:'balances_emp', emp_id:empId, year:currentYear});
            if (!data.success) { alert(data.message||'Failed.'); return; }

            document.getElementById('hlmAllocEmpName').textContent = (emp?.name||emp?.first_name||'Employee') + ' — ' + currentYear + ' allocations';
            document.getElementById('hlmAllocEmpty').style.display  = 'none';
            document.getElementById('hlmAllocTable').classList.remove('hidden');

            document.getElementById('hlmAllocTbody').innerHTML = data.data.map(b => `
                <tr>
                    <td>
                        <span class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full" style="background:${b.color}"></span>
                            <span class="font-medium">${esc(b.name)}</span>
                            <span class="text-xs text-gray-400">(${b.code})</span>
                        </span>
                    </td>
                    <td class="text-center font-bold text-blue-700">${b.allocated}</td>
                    <td class="text-center text-gray-600">${b.used}</td>
                    <td class="text-center text-yellow-600">${b.pending}</td>
                    <td class="text-center font-semibold ${parseFloat(b.remaining)<0?'text-red-600':'text-green-700'}">${b.remaining}</td>
                    <td class="text-center">
                        <div class="flex items-center justify-center gap-1.5">
                            <input type="number" min="0" max="365" value="${b.allocated}"
                                class="w-16 border border-gray-200 rounded-lg px-2 py-1 text-sm text-center focus:outline-none focus:ring-2 focus:ring-blue-400"
                                id="alloc-${b.leave_type_sys_id}">
                            <button onclick="hlmSaveAlloc('${empId}','${b.leave_type_sys_id}','${esc(b.name)}')"
                                class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition">
                                Save
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
        } catch(_) { alert('Failed to load balances.'); }
        this.disabled = false; this.innerHTML='<i class="fas fa-eye mr-1.5"></i>Load Balances';
    });

    window.hlmSaveAlloc = async function(empId, ltId, ltName) {
        const inp  = document.getElementById('alloc-'+ltId);
        const days = parseInt(inp.value);
        if (isNaN(days) || days < 0) { alert('Invalid value.'); return; }
        inp.disabled = true;
        try {
            const data = await apiPost('/api/leaves/endpoints.php', {
                action:'set_allocation', emp_id:empId, leave_type_sys_id:ltId, year:currentYear, allocated:days
            });
            if (data.success) {
                // Flash green
                inp.style.background='#d1fae5'; setTimeout(()=>{ inp.style.background=''; },1500);
            } else { alert(data.message||'Failed.'); }
        } catch(_){ alert('Network error.'); }
        inp.disabled = false;
    };

    /* ── Summary ── */
    async function loadSummary() {
        const cards = document.getElementById('hlmSummaryCards');
        cards.innerHTML = '<div class="col-span-full text-center py-8 text-gray-400 text-sm"><i class="fas fa-spinner fa-spin mr-2"></i>Loading…</div>';
        try {
            const data = await apiPost('/api/leaves/endpoints.php', {action:'list_all', year:currentYear});
            if (!data.success) { cards.innerHTML='<p class="col-span-full text-center text-red-500 py-4">'+data.message+'</p>'; return; }

            // Aggregate by employee
            const empMap = {};
            data.data.forEach(r => {
                if (!empMap[r.employee_sys_id]) {
                    empMap[r.employee_sys_id] = {name:r.employee_name, id:r.employee_id, approved:0, pending:0, rejected:0};
                }
                if (r.status==='approved')  empMap[r.employee_sys_id].approved += r.total_days;
                if (r.status==='pending')   empMap[r.employee_sys_id].pending  += r.total_days;
                if (r.status==='rejected')  empMap[r.employee_sys_id].rejected += r.total_days;
            });

            const emps = Object.values(empMap).sort((a,b)=>a.name.localeCompare(b.name));
            if (!emps.length) { cards.innerHTML='<p class="col-span-full text-center text-gray-400 py-8">No leave data for this year.</p>'; return; }

            cards.innerHTML = emps.map(e => `
                <div class="bg-white rounded-xl border border-gray-100 p-4 hover:shadow-sm transition">
                    <div class="font-semibold text-sm text-gray-800 truncate mb-2">${esc(e.name)}</div>
                    <div class="text-xs text-gray-400 mb-3">${esc(e.id)}</div>
                    <div class="space-y-1 text-xs">
                        <div class="flex justify-between"><span class="text-green-600">Approved</span><strong>${e.approved}d</strong></div>
                        <div class="flex justify-between"><span class="text-yellow-600">Pending</span><strong>${e.pending}d</strong></div>
                        <div class="flex justify-between"><span class="text-red-400">Rejected</span><strong>${e.rejected}d</strong></div>
                    </div>
                </div>
            `).join('');
        } catch(_){ cards.innerHTML='<p class="col-span-full text-center text-red-500 py-4">Failed to load.</p>'; }
    }

    function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

    /* ══════════════════════════════════════════
       HOLIDAYS
    ══════════════════════════════════════════ */
    const HOL_TYPE_LABEL = { public_holiday:'Public Holiday', office_closed:'Office Closed', optional:'Optional' };
    const HOL_TYPE_CLS   = { public_holiday:'bg-green-100 text-green-700', office_closed:'bg-red-100 text-red-700', optional:'bg-yellow-100 text-yellow-700' };
    const DAY_NAMES = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

    // Populate year selector
    (function() {
        const sel = document.getElementById('hlmHolYear');
        const yr  = new Date().getFullYear();
        for (let y = yr + 1; y >= yr - 2; y--) {
            const opt = document.createElement('option');
            opt.value = y; opt.textContent = y;
            if (y === yr) opt.selected = true;
            sel.appendChild(opt);
        }
        sel.addEventListener('change', loadHolidays);
    })();

    async function loadHolidays() {
        const tbody = document.getElementById('hlmHolTbody');
        const empty = document.getElementById('hlmHolEmpty');
        const year  = parseInt(document.getElementById('hlmHolYear').value);
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-8 text-gray-400"><i class="fas fa-spinner fa-spin mr-2"></i>Loading…</td></tr>';
        empty.classList.add('hidden');
        try {
            const data = await apiPost('/api/holidays/endpoints.php', {action:'list', year});
            if (!data.success) { tbody.innerHTML=`<tr><td colspan="5" class="text-center py-6 text-red-500">${esc(data.message)}</td></tr>`; return; }
            const rows = data.data || [];
            document.getElementById('hlmHolCount').textContent = rows.length ? `${rows.length} holiday${rows.length!==1?'s':''} in ${year}` : '';
            if (!rows.length) { tbody.innerHTML=''; empty.classList.remove('hidden'); return; }
            tbody.innerHTML = rows.map(h => {
                const dow  = new Date(h.holiday_date).getDay();
                const cls  = HOL_TYPE_CLS[h.type] || 'bg-gray-100 text-gray-600';
                const lbl  = HOL_TYPE_LABEL[h.type] || h.type;
                return `<tr>
                    <td class="font-medium">${esc(h.holiday_date)}</td>
                    <td class="text-gray-500">${DAY_NAMES[dow]}</td>
                    <td>${esc(h.title)}</td>
                    <td><span class="px-2 py-0.5 rounded-full text-xs font-semibold ${cls}">${lbl}</span></td>
                    <td class="text-center">
                        <button onclick="hlmHolEdit(${JSON.stringify(h).replace(/"/g,'&quot;')})"
                            class="text-blue-500 hover:text-blue-700 text-xs mr-3"><i class="fas fa-edit"></i> Edit</button>
                        <button onclick="hlmHolDelete('${esc(h.sys_id)}','${esc(h.title)}')"
                            class="text-red-400 hover:text-red-600 text-xs"><i class="fas fa-trash"></i> Delete</button>
                    </td>
                </tr>`;
            }).join('');
        } catch(_) { tbody.innerHTML='<tr><td colspan="5" class="text-center py-6 text-red-500">Failed to load holidays.</td></tr>'; }
    }

    // Show add form
    document.getElementById('hlmHolAddBtn').addEventListener('click', function() {
        document.getElementById('hlmHolFormTitle').textContent = 'Add Holiday';
        document.getElementById('hlmHolDate').value  = '';
        document.getElementById('hlmHolTitle').value = '';
        document.getElementById('hlmHolType').value  = 'public_holiday';
        document.getElementById('hlmHolSysId').value = '';
        document.getElementById('hlmHolFormAlert').classList.add('hidden');
        document.getElementById('hlmHolForm').classList.remove('hidden');
        document.getElementById('hlmHolDate').focus();
    });

    // Cancel
    document.getElementById('hlmHolCancel').addEventListener('click', function() {
        document.getElementById('hlmHolForm').classList.add('hidden');
    });

    // Save (add or update)
    document.getElementById('hlmHolSave').addEventListener('click', async function() {
        const sysId = document.getElementById('hlmHolSysId').value;
        const date  = document.getElementById('hlmHolDate').value.trim();
        const title = document.getElementById('hlmHolTitle').value.trim();
        const type  = document.getElementById('hlmHolType').value;
        const alert = document.getElementById('hlmHolFormAlert');
        if (!date)  { alert.className='mb-3 p-2 rounded-lg text-sm font-medium bg-red-50 text-red-600'; alert.textContent='Date is required.'; alert.classList.remove('hidden'); return; }
        if (!title) { alert.className='mb-3 p-2 rounded-lg text-sm font-medium bg-red-50 text-red-600'; alert.textContent='Title is required.'; alert.classList.remove('hidden'); return; }
        alert.classList.add('hidden');
        this.disabled = true; this.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Saving…';
        try {
            const action = sysId ? 'update' : 'add';
            const body   = sysId ? {action, sys_id:sysId, holiday_date:date, title, type}
                                 : {action, holiday_date:date, title, type};
            const res = await apiPost('/api/holidays/endpoints.php', body);
            if (res.success) {
                document.getElementById('hlmHolForm').classList.add('hidden');
                loadHolidays();
            } else {
                alert.className='mb-3 p-2 rounded-lg text-sm font-medium bg-red-50 text-red-600';
                alert.textContent = res.message || 'Failed to save.';
                alert.classList.remove('hidden');
            }
        } catch(_){ alert.className='mb-3 p-2 rounded-lg text-sm font-medium bg-red-50 text-red-600'; alert.textContent='Network error.'; alert.classList.remove('hidden'); }
        this.disabled = false; this.innerHTML = '<i class="fas fa-save"></i> Save';
    });

    // Edit
    window.hlmHolEdit = function(h) {
        document.getElementById('hlmHolFormTitle').textContent = 'Edit Holiday';
        document.getElementById('hlmHolDate').value  = h.holiday_date || '';
        document.getElementById('hlmHolTitle').value = h.title || '';
        document.getElementById('hlmHolType').value  = h.type  || 'public_holiday';
        document.getElementById('hlmHolSysId').value = h.sys_id || '';
        document.getElementById('hlmHolFormAlert').classList.add('hidden');
        document.getElementById('hlmHolForm').classList.remove('hidden');
        document.getElementById('hlmHolTitle').focus();
    };

    // Delete
    window.hlmHolDelete = async function(sysId, title) {
        if (!confirm(`Delete holiday "${title}"? This cannot be undone.`)) return;
        try {
            const res = await apiPost('/api/holidays/endpoints.php', {action:'delete', sys_id:sysId});
            if (res.success) { loadHolidays(); }
            else { alert(res.message || 'Failed to delete.'); }
        } catch(_){ alert('Network error.'); }
    };

    // Initial load
    loadApplications();

}); // end DOMContentLoaded
</script>
</body>
</html>