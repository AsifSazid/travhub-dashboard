<?php
// PATH: pages/hrm-attendance.php
// HR Attendance — daily overview, monthly report, manual mark

include_once('./authenticate.php');
require_once __DIR__ . '/../server/db_connection.php';
require_once __DIR__ . '/../server/permissions.php';

if (!canAccess($pdo, 'hrm_employee_view') && ($_SESSION['user_role'] ?? '') !== '0') {
    header("Location: index.php");
    exit;
}

$ip_port  = @file_get_contents('../ippath.txt');
if (empty($ip_port)) { $ip_port = "https://dev.travhub.com.bd/"; }
$ip_port  = rtrim($ip_port, '/') . '/';
$API_BASE = rtrim($ip_port, '/');

$today       = date('Y-m-d');
$currentYear = (int)date('Y');
$currentMon  = (int)date('m');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance — TravHub HR</title>
    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png" sizes="16x16">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .hat-tab-btn { padding:8px 16px; font-size:.85rem; font-weight:500; border-bottom:2px solid transparent; color:#64748b; background:transparent; border-top:none; border-left:none; border-right:none; cursor:pointer; white-space:nowrap; transition:color .15s,border-color .15s; }
        .hat-tab-btn:hover { color:#1e293b; border-bottom-color:#e2e8f0; }
        .hat-tab-btn.active { color:#2563eb; border-bottom-color:#2563eb; }
        .hat-tbl td, .hat-tbl th { padding:10px 12px; white-space:nowrap; }
        .hat-tbl th { font-size:.72rem; font-weight:600; text-transform:uppercase; letter-spacing:.04em; color:#94a3b8; background:#f8fafc; }
        .hat-tbl tr:not(:last-child) td { border-bottom:1px solid #f1f5f9; }
        .hat-tbl tr:hover td { background:#f8fafc; }
        .badge { display:inline-flex; align-items:center; padding:2px 8px; border-radius:99px; font-size:.72rem; font-weight:600; }
        .st-present   { background:#d1fae5; color:#065f46; }
        .st-absent    { background:#fee2e2; color:#991b1b; }
        .st-late      { background:#fef3c7; color:#92400e; }
        .st-half_day  { background:#e0e7ff; color:#3730a3; }
        .st-on_leave  { background:#fce7f3; color:#9d174d; }
        .st-weekend   { background:#f8fafc; color:#94a3b8; }
        .st-holiday   { background:#f0fdf4; color:#166534; }
        .st-not_marked{ background:#fafafa; color:#64748b; border:1px dashed #e2e8f0; }
        .hat-modal-bg { position:fixed; inset:0; background:rgba(0,0,0,.45); display:flex; align-items:center; justify-content:center; z-index:9999; padding:16px; }
        .hat-modal { background:#fff; border-radius:16px; width:100%; max-width:420px; max-height:90vh; overflow-y:auto; padding:24px; box-shadow:0 20px 60px rgba(0,0,0,.2); }
        /* Month calendar for employee view */
        .hat-cal-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:2px; }
        .hat-cal-day  { aspect-ratio:1; border-radius:6px; display:flex; flex-direction:column; align-items:center; justify-content:center; font-size:.7rem; font-weight:600; }
    </style>
</head>
<body class="bg-gray-50 font-sans">

<?php include '../elements/header.php'; ?>
<?php include '../elements/aside.php'; ?>

<main id="mainContent" class="pt-16 pl-0 lg:pl-64 lg:my-16 transition-all duration-300">
<div class="p-4 md:p-6 max-w-screen-xl mx-auto">

    <!-- Page header -->
    <div class="mb-5 flex items-center gap-3">
        <i class="fas fa-calendar-check text-2xl text-blue-500"></i>
        <div>
            <h1 class="text-xl font-bold text-gray-800">Attendance Management</h1>
            <p class="text-sm text-gray-500">Daily overview, monthly reports, manual marking</p>
        </div>
    </div>

    <!-- Tab bar -->
    <div class="bg-white rounded-2xl shadow overflow-hidden">
        <div class="border-b border-gray-100 flex overflow-x-auto px-4">
            <button class="hat-tab-btn active" data-hattab="daily">
                <i class="fas fa-calendar-day mr-1.5"></i>Daily Overview
            </button>
            <button class="hat-tab-btn" data-hattab="monthly">
                <i class="fas fa-calendar-alt mr-1.5"></i>Monthly Report
            </button>
            <button class="hat-tab-btn" data-hattab="mark">
                <i class="fas fa-pen mr-1.5"></i>Manual Mark
            </button>
        </div>

        <!-- ── Daily Overview ── -->
        <div id="hattab-daily" class="p-5">
            <div class="flex flex-wrap gap-3 mb-4 items-center">
                <input type="date" id="hatDailyDate" value="<?= $today ?>"
                    class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                <button id="hatDailyLoad" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                    <i class="fas fa-eye mr-1.5"></i>Load
                </button>
                <div id="hatDailyMeta" class="text-sm text-gray-500"></div>
            </div>

            <!-- Summary chips -->
            <div id="hatDailySummary" class="flex flex-wrap gap-2 mb-4"></div>

            <div class="overflow-x-auto">
                <table class="hat-tbl w-full text-sm">
                    <thead>
                        <tr>
                            <th class="text-left">Employee</th>
                            <th class="text-left">Designation</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Check In</th>
                            <th class="text-center">Check Out</th>
                            <th class="text-left">Note</th>
                        </tr>
                    </thead>
                    <tbody id="hatDailyTbody">
                        <tr><td colspan="6" class="text-center py-8 text-gray-400"><i class="fas fa-spinner fa-spin mr-2"></i>Loading…</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ── Monthly Report ── -->
        <div id="hattab-monthly" class="p-5" style="display:none">
            <div class="flex flex-wrap gap-3 mb-4 items-center">
                <select id="hatMonEmp" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 flex-1 min-w-[200px]">
                    <option value="">— Select Employee —</option>
                </select>
                <select id="hatMonYear" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <?php for($y=$currentYear+1;$y>=$currentYear-2;$y--): ?>
                    <option value="<?= $y ?>" <?= $y===$currentYear?'selected':'' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
                <select id="hatMonMonth" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <?php
                    $mNames=['January','February','March','April','May','June','July','August','September','October','November','December'];
                    for($m=1;$m<=12;$m++): ?>
                    <option value="<?= $m ?>" <?= $m===$currentMon?'selected':'' ?>><?= $mNames[$m-1] ?></option>
                    <?php endfor; ?>
                </select>
                <button id="hatMonLoad" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                    <i class="fas fa-chart-bar mr-1.5"></i>Generate
                </button>
            </div>

            <div id="hatMonReport" class="hidden">
                <div id="hatMonSummary" class="grid grid-cols-3 sm:grid-cols-6 gap-3 mb-5"></div>
                <!-- Calendar grid -->
                <div class="bg-gray-50 rounded-xl p-4 mb-4">
                    <p id="hatMonCalTitle" class="text-sm font-semibold text-gray-700 mb-3"></p>
                    <div class="hat-cal-grid mb-2">
                        <?php foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d): ?>
                        <div class="text-center text-xs font-semibold text-gray-400 py-1"><?= $d ?></div>
                        <?php endforeach; ?>
                    </div>
                    <div class="hat-cal-grid" id="hatMonCalGrid"></div>
                </div>
            </div>
            <div id="hatMonEmpty" class="text-center py-10 text-gray-400 text-sm">
                <i class="fas fa-calendar-alt text-3xl mb-2 block text-gray-200"></i>Select an employee and period, then click Generate.
            </div>
        </div>

        <!-- ── Manual Mark ── -->
        <div id="hattab-mark" class="p-5" style="display:none">
            <div id="hatMarkAlert" class="hidden mb-4 p-3 rounded-lg text-sm font-medium"></div>

            <div class="max-w-lg space-y-4">
                <div>
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide block mb-1">Employee</label>
                    <select id="hatMarkEmp" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <option value="">— Select Employee —</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide block mb-1">Date</label>
                    <input type="date" id="hatMarkDate" value="<?= $today ?>"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide block mb-1">Status</label>
                    <select id="hatMarkStatus" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <option value="present">Present</option>
                        <option value="absent">Absent</option>
                        <option value="late">Late</option>
                        <option value="half_day">Half Day</option>
                        <option value="on_leave">On Leave</option>
                        <option value="holiday">Holiday</option>
                        <option value="weekend">Weekend</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide block mb-1">Check In <span class="text-gray-400 normal-case">(optional)</span></label>
                        <input type="time" id="hatMarkIn" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide block mb-1">Check Out <span class="text-gray-400 normal-case">(optional)</span></label>
                        <input type="time" id="hatMarkOut" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide block mb-1">Note <span class="text-gray-400 normal-case">(optional)</span></label>
                    <input type="text" id="hatMarkNote" placeholder="Reason, remark…"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
                <button id="hatMarkSaveBtn" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg px-4 py-2.5 text-sm transition flex items-center justify-center gap-2">
                    <i class="fas fa-save"></i> Mark Attendance
                </button>
            </div>
        </div>

    </div><!-- /card -->

</div>
</main>

<?php include '../elements/floating-menus.php'; ?>
<script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>
<script src="../assets/js/functional/dashboard.js?time=<?php echo time(); ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    const API_BASE   = "<?= $API_BASE ?>";
    const MONTH_NAMES= ['January','February','March','April','May','June','July','August','September','October','November','December'];
    let employees    = [];
    let empLoaded    = false;

    async function apiPost(endpoint, body) {
        const res = await fetch(API_BASE + endpoint, {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify(body)
        });
        return res.json();
    }

    function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

    /* ── Tabs ── */
    const tabBtns  = document.querySelectorAll('.hat-tab-btn');
    const tabPanes = { daily: document.getElementById('hattab-daily'), monthly: document.getElementById('hattab-monthly'), mark: document.getElementById('hattab-mark') };

    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            tabBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            Object.values(tabPanes).forEach(p => { p.style.display='none'; });
            const key = this.getAttribute('data-hattab');
            tabPanes[key].style.display = 'block';
            if ((key==='monthly'||key==='mark') && !empLoaded) loadEmployees();
        });
    });

    /* ── Employees ── */
    async function loadEmployees() {
        if (empLoaded) return;
        empLoaded = true;
        try {
            const res  = await fetch(API_BASE + '/api/employees/get-all.php?status=active');
            const data = await res.json();
            employees  = Array.isArray(data.employees) ? data.employees : (data.data||[]);
            const html = '<option value="">— Select Employee —</option>'
                + employees.map(e=>`<option value="${e.sys_id}">${esc(e.name||e.first_name+' '+e.last_name)} (${e.emp_id})</option>`).join('');
            document.getElementById('hatMonEmp').innerHTML  = html;
            document.getElementById('hatMarkEmp').innerHTML = html;
        } catch(_) {}
    }

    /* ── Daily Overview ── */
    const STATUS_CLS = {present:'st-present',absent:'st-absent',late:'st-late',half_day:'st-half_day',on_leave:'st-on_leave',weekend:'st-weekend',holiday:'st-holiday',not_marked:'st-not_marked'};

    document.getElementById('hatDailyLoad').addEventListener('click', loadDaily);

    async function loadDaily() {
        const date   = document.getElementById('hatDailyDate').value;
        const tbody  = document.getElementById('hatDailyTbody');
        const meta   = document.getElementById('hatDailyMeta');
        const sumEl  = document.getElementById('hatDailySummary');
        if (!date) { alert('Select a date.'); return; }

        tbody.innerHTML='<tr><td colspan="6" class="text-center py-8 text-gray-400"><i class="fas fa-spinner fa-spin mr-2"></i>Loading…</td></tr>';
        sumEl.innerHTML='';
        meta.textContent='';

        try {
            const data = await apiPost('/api/attendance/endpoints.php', {action:'daily_all', date});
            if (!data.success) { tbody.innerHTML='<tr><td colspan="6" class="text-center py-6 text-red-500">'+data.message+'</td></tr>'; return; }

            meta.innerHTML = data.is_weekend ? '🌴 Weekend' : (data.is_holiday ? '🎉 Holiday' : '');

            // Summary
            const counts = {};
            data.data.forEach(r => { const s = r.att_status||'not_marked'; counts[s]=(counts[s]||0)+1; });
            sumEl.innerHTML = Object.entries(counts).map(([s,c]) =>
                `<span class="badge ${STATUS_CLS[s]||'st-not_marked'}">${c} ${s.replace('_',' ')}</span>`
            ).join('');

            tbody.innerHTML = data.data.map(r => `
                <tr>
                    <td>
                        <div class="font-medium text-gray-800">${esc(r.name)}</div>
                        <div class="text-xs text-gray-400">${esc(r.emp_id)}</div>
                    </td>
                    <td class="text-gray-600 text-sm">${esc(r.designation||'—')}</td>
                    <td class="text-center">
                        <span class="badge ${STATUS_CLS[r.att_status]||'st-not_marked'}">${(r.att_status||'not marked').replace('_',' ')}</span>
                    </td>
                    <td class="text-center font-mono text-sm text-gray-700">${r.check_in||'—'}</td>
                    <td class="text-center font-mono text-sm text-gray-700">${r.check_out||'—'}</td>
                    <td class="text-gray-500 text-xs max-w-xs truncate">${esc(r.note||'')}</td>
                </tr>
            `).join('');
        } catch(_){ tbody.innerHTML='<tr><td colspan="6" class="text-center py-6 text-red-500">Failed to load.</td></tr>'; }
    }

    /* ── Monthly Report ── */
    const STATUS_MAP_CAL = {
        present:   {cls:'st-present',  code:'P'},
        absent:    {cls:'st-absent',   code:'A'},
        late:      {cls:'st-late',     code:'L'},
        half_day:  {cls:'st-half_day', code:'H'},
        on_leave:  {cls:'st-on_leave', code:'OL'},
        weekend:   {cls:'st-weekend',  code:''},
        holiday:   {cls:'st-holiday',  code:'HO'},
        future:    {cls:'',            code:''},
        not_marked:{cls:'st-not_marked',code:'?'},
    };

    document.getElementById('hatMonLoad').addEventListener('click', loadMonthly);

    async function loadMonthly() {
        const empId = document.getElementById('hatMonEmp').value;
        const year  = parseInt(document.getElementById('hatMonYear').value);
        const month = parseInt(document.getElementById('hatMonMonth').value);
        const repEl = document.getElementById('hatMonReport');
        const empty = document.getElementById('hatMonEmpty');

        if (!empId) { alert('Select an employee.'); return; }

        repEl.classList.add('hidden'); empty.style.display='block';
        empty.innerHTML = '<i class="fas fa-spinner fa-spin text-2xl mb-2 block text-gray-300"></i>Generating…';

        try {
            const data = await apiPost('/api/attendance/endpoints.php', {action:'month_all', emp_id:empId, year, month});
            if (!data.success) { empty.innerHTML='<p class="text-red-500">'+data.message+'</p>'; return; }

            // Summary
            const s = data.summary || {};
            const sumItems = [
                {k:'present',  l:'Present',  cls:'text-green-600',  bg:'bg-green-50'},
                {k:'absent',   l:'Absent',   cls:'text-red-600',    bg:'bg-red-50'},
                {k:'late',     l:'Late',     cls:'text-yellow-600', bg:'bg-yellow-50'},
                {k:'half_day', l:'Half Day', cls:'text-indigo-600', bg:'bg-indigo-50'},
                {k:'on_leave', l:'On Leave', cls:'text-pink-600',   bg:'bg-pink-50'},
                {k:'not_marked',l:'Unknown', cls:'text-gray-400',   bg:'bg-gray-50'},
            ];
            document.getElementById('hatMonSummary').innerHTML = sumItems.map(si =>
                `<div class="${si.bg} rounded-xl p-3 text-center">
                    <div class="text-2xl font-bold ${si.cls}">${s[si.k]||0}</div>
                    <div class="text-xs text-gray-500 mt-0.5">${si.l}</div>
                </div>`
            ).join('');

            // Calendar
            document.getElementById('hatMonCalTitle').textContent = MONTH_NAMES[month-1] + ' ' + year + (data.employee ? ' — ' + data.employee.name : '');
            const firstDow = new Date(year, month-1, 1).getDay();
            let html = '';
            for(let i=0;i<firstDow;i++) html+='<div class="hat-cal-day" style="background:transparent"></div>';
            data.data.forEach(d => {
                const st = STATUS_MAP_CAL[d.att_status] || {cls:'',code:'?'};
                html += `<div class="hat-cal-day ${st.cls}" title="${d.att_status} · ${d.date}">
                    <span style="font-size:.78rem;font-weight:700;line-height:1">${d.day}</span>
                    ${st.code?`<span style="font-size:.55rem;margin-top:1px;opacity:.8">${st.code}</span>`:''}
                </div>`;
            });
            document.getElementById('hatMonCalGrid').innerHTML = html;

            empty.style.display='none'; repEl.classList.remove('hidden');
        } catch(_){ empty.innerHTML='<p class="text-red-500">Failed to generate report.</p>'; }
    }

    /* ── Manual Mark ── */
    document.getElementById('hatMarkSaveBtn').addEventListener('click', async function() {
        const alertEl = document.getElementById('hatMarkAlert');
        const showA   = (msg, ok) => {
            alertEl.textContent = msg;
            alertEl.className   = 'mb-4 p-3 rounded-lg text-sm font-medium '+(ok?'bg-green-50 text-green-700 border border-green-200':'bg-red-50 text-red-700 border border-red-200');
            alertEl.classList.remove('hidden');
        };

        const empId   = document.getElementById('hatMarkEmp').value;
        const date    = document.getElementById('hatMarkDate').value;
        const status  = document.getElementById('hatMarkStatus').value;
        const checkIn = document.getElementById('hatMarkIn').value  || null;
        const checkOut= document.getElementById('hatMarkOut').value || null;
        const note    = document.getElementById('hatMarkNote').value.trim() || null;

        if (!empId) { showA('Please select an employee.', false); return; }
        if (!date)  { showA('Please select a date.', false); return; }

        this.disabled = true; this.innerHTML='<i class="fas fa-spinner fa-spin"></i> Saving…';
        try {
            const data = await apiPost('/api/attendance/endpoints.php', {
                action:'mark', emp_id:empId, date, status,
                check_in:checkIn, check_out:checkOut, note
            });
            if (data.success) {
                showA('✓ Attendance marked: ' + status + ' on ' + date, true);
                document.getElementById('hatMarkIn').value='';
                document.getElementById('hatMarkOut').value='';
                document.getElementById('hatMarkNote').value='';
            } else { showA(data.message||'Failed.', false); }
        } catch(_){ showA('Network error.', false); }
        this.disabled = false; this.innerHTML='<i class="fas fa-save"></i> Mark Attendance';
    });

    // Initial load
    loadDaily();

}); // end DOMContentLoaded
</script>
</body>
</html>