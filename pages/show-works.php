<?php
// FILE PATH: /pages/show-works.php
include_once('./authenticate.php');
$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) { $ip_port = "http://103.104.219.3:898"; }
$getWorkApi      = $ip_port . "api/works/get-work.php";
$updateStatusApi = $ip_port . "api/works/update-status.php";
$addTaskApi      = $ip_port . "api/works/add-task.php";
$addServiceApi   = $ip_port . "api/works/add-service.php";
$deptApi         = $ip_port . "api/masterdata/departments/endpoints.php";
$workTravelersApi = $ip_port . "api/works/travelers.php";
$allTravelersApi  = $ip_port . "api/travelers/all-travelers.php";
$workSysId       = $_GET['id'] ?? '';
$deepLinkSw      = $_GET['sw']   ?? '';
$deepLinkTask    = $_GET['task'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Work Details — TravHub</title>
    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png" sizes="16x16">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .badge { display:inline-flex; align-items:center; padding:2px 10px; border-radius:999px; font-size:.72rem; font-weight:600; }
        .badge-open        { background:#fef9c3; color:#854d0e; }
        .badge-in_progress { background:#dbeafe; color:#1e40af; }
        .badge-done        { background:#dcfce7; color:#166534; }
        .badge-cancelled   { background:#fee2e2; color:#991b1b; }

        .svc-tab { padding:10px 18px; border-radius:8px 8px 0 0; border:1px solid #e5e7eb; border-bottom:none; font-size:.82rem; font-weight:600; color:#6b7280; background:#f9fafb; cursor:pointer; transition:all .15s; white-space:nowrap; }
        .svc-tab.active { background:#fff; color:#4f46e5; border-color:#c7d2fe; border-bottom:1px solid #fff; margin-bottom:-1px; }
        .svc-tab:hover:not(.active) { background:#f3f4f6; color:#374151; }

        .task-card { border:1.5px solid #e5e7eb; border-radius:10px; padding:14px; transition:all .15s; cursor:pointer; }
        .task-card.deep-link-highlight { border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.15); animation: deepLinkPulse 1.8s ease-in-out 2; }
        @keyframes deepLinkPulse {
            0%, 100% { background-color: #fff; }
            50%      { background-color: #eef2ff; }
        }
        .task-card:hover { border-color:#c7d2fe; box-shadow:0 4px 12px rgba(99,102,241,.08); }
        .task-card.active-task { border-color:#6366f1; background:#f5f3ff; }

        .pill { display:inline-flex; align-items:center; gap:3px; padding:2px 8px; border-radius:999px; font-size:.68rem; font-weight:600; }
        .pill-open        { background:#fef9c3; color:#854d0e; }
        .pill-in_progress { background:#dbeafe; color:#1e40af; }
        .pill-done        { background:#dcfce7; color:#166534; }
        .pill-cancelled   { background:#fee2e2; color:#991b1b; }

        .modal-bg { background:rgba(0,0,0,.45); backdrop-filter:blur(3px); }

        .kv-row { display:flex; align-items:flex-start; gap:8px; padding:8px 0; border-bottom:1px solid #f3f4f6; }
        .kv-key { min-width:140px; font-size:.75rem; font-weight:600; color:#9ca3af; text-transform:uppercase; letter-spacing:.04em; flex-shrink:0; }
        .kv-val { font-size:.875rem; color:#1f2937; }

        .service-pill { display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:999px; font-size:.68rem; font-weight:600; }
        .pill-air      { background:#e0f2fe; color:#075985; }
        .pill-visa     { background:#ede9fe; color:#5b21b6; }
        .pill-hotel    { background:#fce7f3; color:#9d174d; }
        .pill-tour     { background:#dcfce7; color:#14532d; }
        .pill-umrah    { background:#fef3c7; color:#92400e; }
        .pill-transport{ background:#f0fdf4; color:#166534; }

        #toast { position:fixed; bottom:24px; right:24px; z-index:9999; }
    </style>
</head>
<body class="bg-gray-50 font-sans">

<?php include '../elements/header.php'; ?>
<?php include '../elements/aside.php'; ?>

<main id="mainContent" class="pt-16 pl-64 mt-16 transition-all duration-300">
<div class="p-6">

    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-sm text-gray-400 mb-4">
        <a href="index-works.php" class="hover:text-indigo-600 transition"><i class="fas fa-briefcase mr-1"></i>Works</a>
        <i class="fas fa-chevron-right text-xs"></i>
        <span class="text-gray-700 font-medium" id="breadcrumbId">Loading…</span>
    </div>

    <!-- Loading state -->
    <div id="loadingState" class="text-center py-20 text-gray-400">
        <i class="fas fa-spinner fa-spin text-3xl mb-3 block"></i>Loading work…
    </div>

    <!-- Main content (hidden until loaded) -->
    <div id="mainContent2" class="hidden">

        <!-- Top row: Work title + status + actions -->
        <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
            <div>
                <div class="flex items-center gap-3 mb-1">
                    <h1 class="text-xl font-bold text-gray-800" id="workTitle">—</h1>
                    <span id="workStatusBadge"></span>
                </div>
                <div class="font-mono text-xs text-indigo-500" id="workSysId">—</div>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="openStatusModal()" class="px-4 py-2 bg-white border border-gray-200 hover:border-indigo-300 text-gray-600 rounded-lg text-sm font-medium transition">
                    <i class="fas fa-tag mr-1.5"></i>Status
                </button>
                <a href="index-works.php" class="px-4 py-2 bg-white border border-gray-200 hover:border-gray-300 text-gray-600 rounded-lg text-sm font-medium transition">
                    <i class="fas fa-arrow-left mr-1.5"></i>Back
                </a>
            </div>
        </div>

        <!-- Two-column layout -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- LEFT: Services + Tasks -->
            <div class="lg:col-span-2 space-y-5">

                <!-- Service Tabs -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="flex items-center gap-0 px-5 pt-4 border-b border-gray-100 overflow-x-auto" id="svcTabBar">
                        <!-- tabs rendered by JS -->
                    </div>

                    <!-- Tab content: tasks list -->
                    <div class="p-5" id="svcTabContent">
                        <div class="text-center py-8 text-gray-400"><i class="fas fa-spinner fa-spin"></i></div>
                    </div>
                </div>

                <!-- Work Details (key-value from DB) -->
                <div class="bg-white rounded-xl shadow-sm p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-800"><i class="fas fa-info-circle mr-2 text-indigo-400"></i>Work Details</h3>
                    </div>
                    <div id="workDetails" class="divide-y divide-gray-50"></div>
                </div>

                <!-- Quotation / Booking / Confirmation list -->
                <div class="bg-white rounded-xl shadow-sm p-5">
                    <div class="flex items-center gap-4 mb-4 border-b border-gray-100 pb-3">
                        <button onclick="setPhaseTab('quotation')" id="ptab-quotation" class="phase-tab text-sm font-semibold text-indigo-600 border-b-2 border-indigo-500 pb-1">Quotation</button>
                        <button onclick="setPhaseTab('booking')"   id="ptab-booking"   class="phase-tab text-sm font-medium text-gray-400 pb-1">Booking</button>
                        <button onclick="setPhaseTab('confirmation')" id="ptab-confirmation" class="phase-tab text-sm font-medium text-gray-400 pb-1">Confirmation</button>
                    </div>
                    <div id="phaseContent" class="text-sm text-gray-400 text-center py-6">
                        Select a task to view details.
                    </div>
                </div>

                <!-- Financial Entries -->
                <div class="bg-white rounded-xl shadow-sm p-5">
                    <h3 class="font-semibold text-gray-800 mb-4"><i class="fas fa-receipt mr-2 text-indigo-400"></i>Financial Entries</h3>
                    <div id="financialList" class="text-sm text-gray-400 text-center py-6">No entries yet.</div>
                </div>

            </div>

            <!-- RIGHT: Work Overview sidebar -->
            <div class="space-y-4">

                <!-- Work Overview card -->
                <div class="bg-white rounded-xl shadow-sm p-5">
                    <h3 class="font-semibold text-gray-800 mb-4"><i class="fas fa-chart-bar mr-2 text-indigo-400"></i>Work Overview</h3>
                    <div class="space-y-3" id="overviewPanel">
                        <div class="flex justify-between text-sm"><span class="text-gray-400">Client</span><span class="font-medium text-gray-700" id="ovClient">—</span></div>
                        <div class="flex justify-between text-sm"><span class="text-gray-400">Status</span><span id="ovStatus">—</span></div>
                        <div class="flex justify-between text-sm"><span class="text-gray-400">T. Category</span><span class="font-medium text-gray-700" id="ovCategory">—</span></div>
                        <div class="flex justify-between text-sm"><span class="text-gray-400">Total Tasks</span><span class="font-bold text-gray-800" id="ovTotal">—</span></div>
                        <div class="flex justify-between text-sm"><span class="text-gray-400">Remaining</span><span class="font-bold text-orange-500" id="ovRemaining">—</span></div>
                        <div class="flex justify-between text-sm"><span class="text-gray-400">Completed</span><span class="font-bold text-green-600" id="ovCompleted">—</span></div>
                        <hr class="border-gray-100">
                        <div class="flex justify-between text-sm"><span class="text-gray-400">T. Quotation</span><span class="font-medium text-gray-700" id="ovQuotation">—</span></div>
                        <div class="flex justify-between text-sm"><span class="text-gray-400">T. Booking</span><span class="font-medium text-gray-700" id="ovBooking">—</span></div>
                        <div class="flex justify-between text-sm"><span class="text-gray-400">T. Confirmation</span><span class="font-medium text-gray-700" id="ovConfirmation">—</span></div>
                        <div class="flex justify-between text-sm"><span class="text-gray-400">Confirmed Amt</span><span class="font-bold text-indigo-600" id="ovConfirmedAmt">—</span></div>
                    </div>
                </div>

                <!-- Travelers -->
                <div class="bg-white rounded-xl shadow-sm p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-semibold text-gray-800 text-sm"><i class="fas fa-users mr-2 text-indigo-400"></i>Travelers</h3>
                        <button onclick="openAddTravelerModal()" class="w-6 h-6 bg-indigo-50 text-indigo-600 rounded-full text-xs hover:bg-indigo-100 transition">+</button>
                    </div>
                    <div id="travelersList" class="text-xs text-gray-400 space-y-2">No travelers linked.</div>
                </div>

                <!-- Source Lead -->
                <div class="bg-white rounded-xl shadow-sm p-5">
                    <h3 class="font-semibold text-gray-800 text-sm mb-3"><i class="fas fa-link mr-2 text-indigo-400"></i>Source Lead</h3>
                    <div id="sourceLead" class="text-xs text-gray-500">—</div>
                </div>

            </div>
        </div>
    </div>
</div>
</main>

<!-- ADD TASK MODAL -->
<div id="addTaskModal" class="fixed inset-0 z-50 hidden modal-bg flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between p-5 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800"><i class="fas fa-plus mr-2 text-indigo-500"></i>Add Task</h3>
            <button onclick="closeModal('addTaskModal')" class="text-gray-400 hover:text-gray-700 text-xl"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-5 space-y-4">
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Task Name</label>
                <input type="text" id="newTaskName" placeholder="e.g. Air Ticket - Dhaka to Dubai" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <input type="hidden" id="newTaskSwSysId">
        </div>
        <div class="flex gap-3 p-5 border-t border-gray-100">
            <button onclick="closeModal('addTaskModal')" class="flex-1 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition">Cancel</button>
            <button onclick="confirmAddTask()" class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition">
                <i class="fas fa-plus mr-1"></i>Add Task
            </button>
        </div>
    </div>
</div>

<!-- ADD TRAVELER MODAL -->
<div id="addTravelerModal" class="fixed inset-0 z-50 hidden modal-bg flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-[85vh] flex flex-col">
        <div class="flex items-center justify-between p-5 border-b border-gray-100 flex-shrink-0">
            <h3 class="font-semibold text-gray-800"><i class="fas fa-user-plus mr-2 text-indigo-500"></i>Add Traveler</h3>
            <button onclick="closeModal('addTravelerModal')" class="text-gray-400 hover:text-gray-700 text-xl"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-5 flex-shrink-0">
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" id="travelerSearchInput" placeholder="Search by name, passport, NID, phone…"
                    autocomplete="off"
                    class="w-full pl-9 pr-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none"
                    oninput="filterTravelerSearch()">
            </div>
        </div>
        <div id="travelerSearchResults" class="overflow-y-auto flex-1 px-5 pb-3 space-y-2">
            <div class="text-center py-8 text-gray-400 text-sm"><i class="fas fa-spinner fa-spin"></i> Loading travelers…</div>
        </div>
        <div class="p-5 border-t border-gray-100 flex-shrink-0">
            <a href="create-traveler.php" target="_blank"
                class="flex items-center justify-center gap-2 w-full py-2.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 rounded-lg text-sm font-semibold transition">
                <i class="fas fa-plus"></i>Create New Traveler
            </a>
        </div>
    </div>
</div>

<!-- STATUS MODAL -->
<div id="statusModal" class="fixed inset-0 z-50 hidden modal-bg flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
        <div class="flex items-center justify-between p-5 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-800"><i class="fas fa-tag mr-2 text-indigo-500"></i>Change Work Status</h3>
            <button onclick="closeModal('statusModal')" class="text-gray-400 hover:text-gray-700 text-xl"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-5 grid grid-cols-2 gap-3">
            <button onclick="changeStatus('open')"        class="py-2 rounded-lg bg-yellow-50 text-yellow-700 border border-yellow-200 text-sm font-medium hover:bg-yellow-100 transition">🟡 Open</button>
            <button onclick="changeStatus('in_progress')" class="py-2 rounded-lg bg-blue-50 text-blue-700 border border-blue-200 text-sm font-medium hover:bg-blue-100 transition">🔵 In Progress</button>
            <button onclick="changeStatus('done')"        class="py-2 rounded-lg bg-green-50 text-green-700 border border-green-200 text-sm font-medium hover:bg-green-100 transition">✅ Done</button>
            <button onclick="changeStatus('cancelled')"   class="py-2 rounded-lg bg-red-50 text-red-700 border border-red-200 text-sm font-medium hover:bg-red-100 transition">❌ Cancelled</button>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="toast" class="hidden">
    <div id="toastInner" class="flex items-center gap-3 px-5 py-3 rounded-xl shadow-lg text-white text-sm font-medium">
        <i id="toastIcon" class="fas fa-check-circle text-lg"></i>
        <span id="toastMsg"></span>
    </div>
</div>

<?php include '../elements/floating-menus.php'; ?>
<script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>
<script>
const WORK_SYS_ID = "<?php echo htmlspecialchars($workSysId); ?>";
const DEEP_LINK_SW   = "<?php echo htmlspecialchars($deepLinkSw); ?>";
const DEEP_LINK_TASK = "<?php echo htmlspecialchars($deepLinkTask); ?>";
const API = {
    getWork:    "<?php echo $getWorkApi; ?>",
    status:     "<?php echo $updateStatusApi; ?>",
    addTask:    "<?php echo $addTaskApi; ?>",
    addService: "<?php echo $addServiceApi; ?>",
    depts:      "<?php echo $deptApi; ?>",
    workTravelers: "<?php echo $workTravelersApi; ?>",
    allTravelers:  "<?php echo $allTravelersApi; ?>",
};

let allDepts = [];

async function loadDepts() {
    try {
        const res  = await fetch(API.depts + '?action=all');
        const json = await res.json();
        allDepts   = (json.data ?? []).filter(d => d.is_active == 1);
        // Populate select
        const sel  = document.getElementById('newServiceDept');
        if (sel) {
            sel.innerHTML = '<option value="">No department (assign later)</option>' +
                allDepts.map(d => `<option value="${d.sys_id}">${d.name}</option>`).join('');
        }
    } catch {}
}

const SERVICE_LABELS = {
    air_ticket:   { icon:'fa-plane',           label:'Air Ticket',   cls:'pill-air' },
    visa:         { icon:'fa-passport',         label:'Visa',         cls:'pill-visa' },
    hotel:        { icon:'fa-hotel',            label:'Hotel',        cls:'pill-hotel' },
    tour_package: { icon:'fa-suitcase-rolling', label:'Tour Package', cls:'pill-tour' },
    umrah:        { icon:'fa-kaaba',            label:'Umrah',        cls:'pill-umrah' },
    transport:    { icon:'fa-bus',              label:'Transport',    cls:'pill-transport' },
};

let workData      = null;
let serviceWorks  = [];
let allTasks      = [];
let activeSwId    = null;
let activePhase   = 'quotation';

// ── Load work ────────────────────────────────────────────
async function loadWork() {
    if (!WORK_SYS_ID) {
        document.getElementById('loadingState').innerHTML = '<p class="text-red-400">No work ID provided.</p>';
        return;
    }
    try {
        const res  = await fetch(API.getWork + '?id=' + WORK_SYS_ID);
        const json = await res.json();
        if (json.status !== 'success') throw new Error(json.message);

        workData     = json.work;
        serviceWorks = json.service_works ?? [];
        allTasks     = json.tasks ?? [];

        renderPage();

    } catch(e) {
        document.getElementById('loadingState').innerHTML =
            `<p class="text-red-400"><i class="fas fa-exclamation-circle mr-2"></i>${e.message}</p>`;
    }
}

function renderPage() {
    document.getElementById('loadingState').classList.add('hidden');
    document.getElementById('mainContent2').classList.remove('hidden');

    const ci = safeParse(workData.client_info) ?? {};
    const st = safeParse(workData.service_type) ?? [];
    const services = Array.isArray(st) ? st : [st];

    // Header
    document.getElementById('breadcrumbId').textContent = WORK_SYS_ID;
    document.getElementById('workTitle').textContent    = ci.name ? ci.name + ' — Work' : 'Work Details';
    document.getElementById('workSysId').textContent    = WORK_SYS_ID;
    document.getElementById('workStatusBadge').innerHTML = badgeHtml(workData.work_status);

    // Overview
    document.getElementById('ovClient').textContent    = ci.name ?? '—';
    document.getElementById('ovStatus').innerHTML      = badgeHtml(workData.work_status);
    document.getElementById('ovCategory').textContent  = services.join(', ');
    document.getElementById('ovTotal').textContent     = allTasks.length;
    document.getElementById('ovRemaining').textContent = allTasks.filter(t => t.status !== 'done').length;
    document.getElementById('ovCompleted').textContent = allTasks.filter(t => t.status === 'done').length;

    const totalQ   = allTasks.reduce((s, t) => s + parseFloat(t.total_quotation    || 0), 0);
    const totalB   = allTasks.reduce((s, t) => s + parseFloat(t.total_booking      || 0), 0);
    const totalC   = allTasks.reduce((s, t) => s + parseFloat(t.total_confirmation || 0), 0);
    const totalCA  = allTasks.reduce((s, t) => s + parseFloat(t.confirmed_amount   || 0), 0);
    document.getElementById('ovQuotation').textContent    = totalQ  ? '৳ ' + totalQ.toLocaleString()  : '—';
    document.getElementById('ovBooking').textContent      = totalB  ? '৳ ' + totalB.toLocaleString()  : '—';
    document.getElementById('ovConfirmation').textContent = totalC  ? '৳ ' + totalC.toLocaleString()  : '—';
    document.getElementById('ovConfirmedAmt').textContent = totalCA ? '৳ ' + totalCA.toLocaleString() : '—';

    // Source lead
    document.getElementById('sourceLead').innerHTML = workData.lead_sys_id
        ? `<span class="font-mono text-indigo-500">${workData.lead_sys_id}</span>`
        : '—';

    // Work details key-value
    renderWorkDetails(ci, workData);

    // Service tabs
    renderServiceTabs(serviceWorks, services);

    // Travelers
    loadWorkTravelers();
}

/* ══════════════════════════════════════════════
   TRAVELERS
══════════════════════════════════════════════ */
let linkedTravelers = [];  // travelers currently attached to this work
let allTravelersData = []; // full traveler directory (loaded lazily)

async function loadWorkTravelers() {
    try {
        const res  = await fetch(`${API.workTravelers}?action=list&work_sys_id=${encodeURIComponent(WORK_SYS_ID)}`);
        const json = await res.json();
        linkedTravelers = (json.status === 'success') ? (json.data ?? []) : [];
        renderTravelersList();
    } catch(e) {
        document.getElementById('travelersList').innerHTML =
            `<p class="text-red-400 text-xs"><i class="fas fa-exclamation-circle mr-1"></i>Failed to load travelers</p>`;
    }
}

function renderTravelersList() {
    const wrap = document.getElementById('travelersList');
    if (!linkedTravelers.length) {
        wrap.innerHTML = '<p class="text-gray-400">No travelers linked.</p>';
        return;
    }
    wrap.innerHTML = linkedTravelers.map(t => {
        const initial = (t.name || '?').charAt(0).toUpperCase();
        const sub = [t.passport_no, t.phone].filter(Boolean).join(' · ');
        return `<div class="flex items-center gap-2 group">
            <div class="w-7 h-7 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-xs flex-shrink-0">${initial}</div>
            <div class="flex-1 min-w-0">
                <p class="text-gray-700 font-medium truncate">${esc(t.name)}</p>
                ${sub ? `<p class="text-gray-400 text-[10px] truncate">${esc(sub)}</p>` : ''}
            </div>
            <button onclick="unlinkTraveler('${esc(t.sys_id)}')"
                class="opacity-0 group-hover:opacity-100 transition text-gray-300 hover:text-red-500 text-xs flex-shrink-0" title="Remove">
                <i class="fas fa-times"></i>
            </button>
        </div>`;
    }).join('');
}

async function unlinkTraveler(travelerSysId) {
    if (!confirm('Remove this traveler from the work?')) return;
    try {
        const res  = await fetch(API.workTravelers, {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'unlink', work_sys_id: WORK_SYS_ID, traveler_sys_id: travelerSysId }),
        });
        const json = await res.json();
        if (json.status !== 'success') throw new Error(json.message ?? 'Failed');
        linkedTravelers = linkedTravelers.filter(t => t.sys_id !== travelerSysId);
        renderTravelersList();
    } catch(e) {
        alert('Failed to remove traveler: ' + e.message);
    }
}

async function openAddTravelerModal() {
    document.getElementById('addTravelerModal').classList.remove('hidden');
    document.getElementById('travelerSearchInput').value = '';
    if (!allTravelersData.length) {
        try {
            const res  = await fetch(API.allTravelers);
            const json = await res.json();
            allTravelersData = json.success ? (json.travelers ?? []) : [];
        } catch(e) {
            allTravelersData = [];
        }
    }
    filterTravelerSearch();
}

function filterTravelerSearch() {
    const q = (document.getElementById('travelerSearchInput').value || '').toLowerCase().trim();
    const linkedIds = new Set(linkedTravelers.map(t => t.sys_id));

    let results = allTravelersData;
    if (q) {
        results = results.filter(t =>
            (t.name || '').toLowerCase().includes(q) ||
            (t.passport_no || '').toLowerCase().includes(q) ||
            (t.nid_no || '').toLowerCase().includes(q) ||
            (t.phone || '').toLowerCase().includes(q)
        );
    }
    results = results.slice(0, 50); // cap render

    const wrap = document.getElementById('travelerSearchResults');
    if (!results.length) {
        wrap.innerHTML = '<p class="text-center text-gray-400 text-sm py-6">No travelers found.</p>';
        return;
    }

    wrap.innerHTML = results.map(t => {
        const isLinked = linkedIds.has(t.sys_id);
        const initial  = (t.name || '?').charAt(0).toUpperCase();
        const sub = [t.passport_no, t.phone].filter(Boolean).join(' · ');
        return `<div class="flex items-center gap-3 p-2.5 rounded-lg border border-gray-100 hover:border-indigo-200 transition">
            <div class="w-9 h-9 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-sm flex-shrink-0">${initial}</div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-700 truncate">${esc(t.name)}</p>
                ${sub ? `<p class="text-xs text-gray-400 truncate">${esc(sub)}</p>` : ''}
            </div>
            ${isLinked
                ? `<span class="text-xs text-green-600 font-semibold flex-shrink-0"><i class="fas fa-check mr-1"></i>Added</span>`
                : `<button onclick="linkTraveler('${esc(t.sys_id)}')" class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 rounded-lg text-xs font-semibold transition flex-shrink-0">Add</button>`}
        </div>`;
    }).join('');
}

async function linkTraveler(travelerSysId) {
    try {
        const res  = await fetch(API.workTravelers, {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'link', work_sys_id: WORK_SYS_ID, traveler_sys_id: travelerSysId }),
        });
        const json = await res.json();
        if (json.status !== 'success') throw new Error(json.message ?? 'Failed');
        await loadWorkTravelers();
        filterTravelerSearch(); // refresh "Added" badge in modal
    } catch(e) {
        alert('Failed to add traveler: ' + e.message);
    }
}

function renderWorkDetails(ci, w) {
    const rows = [
        ['Client Name',   ci.name    ?? '—'],
        ['Phone',         ci.phone   ?? '—'],
        ['Email',         ci.email   ?? '—'],
        ['Work Status',   w.work_status],
        ['Assigned To',   w.assigned_to ?? '—'],
        ['Lead Ref',      w.lead_sys_id ?? '—'],
    ];
    document.getElementById('workDetails').innerHTML = rows.map(([k, v]) =>
        `<div class="kv-row"><span class="kv-key">${k}</span><span class="kv-val">${v}</span></div>`
    ).join('');
}

// ── Service Tabs ─────────────────────────────────────────
function renderServiceTabs(sws, services) {
    const bar = document.getElementById('svcTabBar');

    // Build tabs: one per service_work, plus "+" add tab
    let tabHtml = sws.map((sw, i) => {
        const info = SERVICE_LABELS[sw.service_slug] ?? { icon:'fa-circle', label: sw.service_name ?? sw.service_slug, cls:'' };
        return `<button class="svc-tab ${i === 0 ? 'active' : ''}" onclick="switchSvcTab('${sw.sys_id}', this)">
            <i class="fas ${info.icon} mr-1.5"></i>${info.label}
        </button>`;
    }).join('');

    // Add Service button always visible in tab bar
    tabHtml += `<button onclick="openAddServiceModal()" class="svc-tab ml-auto text-indigo-500 hover:text-indigo-700" style="border-color:#c7d2fe;">
        <i class="fas fa-plus mr-1"></i>Add Service
    </button>`;

    bar.innerHTML = tabHtml;

    // Activate tab — prefer deep-linked sw param, fallback to first tab
    if (sws.length > 0) {
        let targetSw = sws[0].sys_id;
        let targetBtn = bar.querySelector('.svc-tab');

        if (DEEP_LINK_SW) {
            const match = sws.find(s => s.sys_id === DEEP_LINK_SW);
            if (match) {
                targetSw = match.sys_id;
                const idx = sws.indexOf(match);
                targetBtn = bar.querySelectorAll('.svc-tab')[idx];
            }
        }

        document.querySelectorAll('.svc-tab').forEach(t => t.classList.remove('active'));
        targetBtn?.classList.add('active');
        activeSwId = targetSw;
        renderTasksForSw(targetSw);

        // Scroll the service card into view if we deep-linked
        if (DEEP_LINK_SW || DEEP_LINK_TASK) {
            setTimeout(() => {
                document.getElementById('svcTabBar')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 200);
        }
    } else {
        document.getElementById('svcTabContent').innerHTML = `<div class="text-center py-8 text-gray-400"><i class="fas fa-info-circle mr-2"></i>No services linked to this work.</div>`;
    }
}

function switchSvcTab(swSysId, btn) {
    document.querySelectorAll('.svc-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    activeSwId = swSysId;
    renderTasksForSw(swSysId);
}

function renderTasksForSw(swSysId) {
    const tasks = allTasks.filter(t => t.service_work_sys_id === swSysId);
    const sw    = serviceWorks.find(s => s.sys_id === swSysId);
    const content = document.getElementById('svcTabContent');

    let html = `<div class="flex items-center justify-between mb-4">
        <h4 class="font-semibold text-gray-700 text-sm">${sw?.service_name ?? 'Tasks'}</h4>
        <button onclick="openAddTaskModal('${swSysId}')" class="flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 rounded-lg text-xs font-semibold transition">
            <i class="fas fa-plus text-xs"></i>Add Task
        </button>
    </div>`;

    if (!tasks.length) {
        html += `<div class="text-center py-8 text-gray-400 bg-gray-50 rounded-lg">
            <i class="fas fa-tasks text-2xl mb-2 block opacity-30"></i>No tasks yet. Click "Add Task" to create one.
        </div>`;
    } else {
        html += `<div class="space-y-3">` + tasks.map(t => {
            const isDeepLinked = DEEP_LINK_TASK && t.sys_id === DEEP_LINK_TASK;
            return `
            <div class="task-card${isDeepLinked ? ' deep-link-highlight' : ''}" data-task-id="${t.sys_id}" onclick="selectTask('${t.sys_id}')">
                <div class="flex items-start justify-between mb-2">
                    <div>
                        <div class="font-semibold text-gray-800 text-sm">${t.workname ?? 'Untitled Task'}</div>
                        <div class="font-mono text-xs text-indigo-400 mt-0.5">${t.sys_id}</div>
                    </div>
                    <span class="pill pill-${t.status ?? 'open'}">${statusLabel(t.status)}</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="text-xs text-gray-400">
                        ${t.assigned_to ? '<i class="fas fa-user mr-1"></i>' + t.assigned_to : 'Unassigned'}
                    </div>
                    <a href="show-tasks.php?id=${t.sys_id}" onclick="event.stopPropagation()" class="text-xs text-indigo-500 hover:text-indigo-700 font-semibold">
                        Open <i class="fas fa-arrow-right ml-1 text-[10px]"></i>
                    </a>
                </div>
                ${t.total_quotation > 0 ? `<div class="mt-2 pt-2 border-t border-gray-100 flex gap-4 text-xs text-gray-400">
                    <span>Q: <b class="text-gray-600">৳${parseFloat(t.total_quotation).toLocaleString()}</b></span>
                    <span>B: <b class="text-gray-600">৳${parseFloat(t.total_booking).toLocaleString()}</b></span>
                    <span>C: <b class="text-gray-600">৳${parseFloat(t.total_confirmation).toLocaleString()}</b></span>
                </div>` : ''}
            </div>`;
        }).join('') + `</div>`;
    }

    content.innerHTML = html;

    // Auto-select + scroll to deep-linked task
    if (DEEP_LINK_TASK) {
        const targetCard = content.querySelector(`.task-card[data-task-id="${DEEP_LINK_TASK}"]`);
        if (targetCard) {
            selectTask(DEEP_LINK_TASK);
            setTimeout(() => {
                targetCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 250);
        }
    }
}

// ── Task selection (phase tabs) ───────────────────────────
function selectTask(taskSysId) {
    document.querySelectorAll('.task-card').forEach(c => c.classList.remove('active-task'));
    const card = document.querySelector(`.task-card[onclick*="${taskSysId}"]`);
    if (card) card.classList.add('active-task');

    const task = allTasks.find(t => t.sys_id === taskSysId);
    if (!task) return;
    renderPhaseContent(task, activePhase);
}

function setPhaseTab(phase) {
    activePhase = phase;
    document.querySelectorAll('.phase-tab').forEach(b => {
        const isActive = b.id === 'ptab-' + phase;
        b.className = `phase-tab text-sm pb-1 ${isActive ? 'font-semibold text-indigo-600 border-b-2 border-indigo-500' : 'font-medium text-gray-400'}`;
    });
    const activeCard = document.querySelector('.task-card.active-task');
    if (activeCard) {
        const sysId = activeCard.getAttribute('onclick').match(/'([^']+)'/)?.[1];
        if (sysId) {
            const task = allTasks.find(t => t.sys_id === sysId);
            if (task) renderPhaseContent(task, phase);
        }
    }
}

function renderPhaseContent(task, phase) {
    const data = safeParse(task[phase]) ?? {};
    const div  = document.getElementById('phaseContent');
    const keys = Object.keys(data);

    if (!keys.length) {
        div.innerHTML = `<div class="text-center py-6 text-gray-400 text-sm"><i class="fas fa-file-alt mr-2 opacity-40"></i>No ${phase} data yet.</div>`;
        return;
    }

    div.innerHTML = `<div class="space-y-2">` + keys.map(k =>
        `<div class="kv-row"><span class="kv-key">${k.replace(/_/g,' ')}</span><span class="kv-val">${data[k] ?? '—'}</span></div>`
    ).join('') + `</div>`;
}

// ── Add Task Modal ────────────────────────────────────────
function openAddTaskModal(swSysId) {
    document.getElementById('newTaskSwSysId').value = swSysId;
    document.getElementById('newTaskName').value    = '';
    document.getElementById('addTaskModal').classList.remove('hidden');
}

let _addTaskBusy = false;

async function confirmAddTask() {
    if (_addTaskBusy) return;   // ← prevent double-submit / 7-row bug
    _addTaskBusy = true;

    const swSysId  = document.getElementById('newTaskSwSysId').value;
    const name     = document.getElementById('newTaskName').value.trim();
    if (!name) { showToast('error', 'Task name is required'); _addTaskBusy = false; return; }

    const ci = safeParse(workData.client_info) ?? {};

    try {
        const res  = await fetch(API.addTask, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                service_work_sys_id: swSysId,
                work_sys_id:         WORK_SYS_ID,
                client_sys_id:       workData.client_sys_id ?? '',
                client_name:         ci.name ?? '',
                workname:            name,
            }),
        });
        const json = await res.json();
        if (json.status === 'success') {
            showToast('success', 'Task created!');
            closeModal('addTaskModal');
            loadWork();
        } else {
            showToast('error', json.message);
        }
    } catch { showToast('error', 'Network error'); }
    finally { _addTaskBusy = false; }
}

// ── Status ────────────────────────────────────────────────
function openStatusModal() { document.getElementById('statusModal').classList.remove('hidden'); }

async function changeStatus(newStatus) {
    closeModal('statusModal');
    try {
        const res  = await fetch(API.status, {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ sys_id: WORK_SYS_ID, status: newStatus }),
        });
        const json = await res.json();
        if (json.status === 'success') { showToast('success', 'Status updated!'); loadWork(); }
        else showToast('error', json.message);
    } catch { showToast('error', 'Network error'); }
}

// ── Helpers ───────────────────────────────────────────────
function closeModal(id) { document.getElementById(id).classList.add('hidden'); }
document.querySelectorAll('.modal-bg').forEach(m => m.addEventListener('click', e => { if (e.target === m) m.classList.add('hidden'); }));

function safeParse(v) { if (!v) return null; if (typeof v === 'object') return v; try { return JSON.parse(v); } catch { return null; } }
function esc(s) { return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

function badgeHtml(status) {
    const map = { open:['badge-open','🟡 Open'], in_progress:['badge-in_progress','🔵 In Progress'], done:['badge-done','✅ Done'], cancelled:['badge-cancelled','❌ Cancelled'] };
    const [cls, label] = map[status] ?? ['', status];
    return `<span class="badge ${cls}">${label}</span>`;
}

function statusLabel(s) {
    return { open:'Open', in_progress:'In Progress', done:'Done', cancelled:'Cancelled' }[s] ?? s;
}

function showToast(type, msg) {
    const inner = document.getElementById('toastInner');
    document.getElementById('toastMsg').textContent = msg;
    inner.className = `flex items-center gap-3 px-5 py-3 rounded-xl shadow-lg text-white text-sm font-medium ${type==='success' ? 'bg-green-600' : 'bg-red-500'}`;
    document.getElementById('toastIcon').className  = `fas ${type==='success' ? 'fa-check-circle' : 'fa-exclamation-circle'} text-lg`;
    document.getElementById('toast').classList.remove('hidden');
    setTimeout(() => document.getElementById('toast').classList.add('hidden'), 3500);
}

loadWork();
loadDepts();

// ── Add Service ───────────────────────────────────────────────
function openAddServiceModal() {
    document.getElementById('newServiceSlug').value = '';
    document.getElementById('newServiceName').value = '';
    document.getElementById('addServiceModal').classList.remove('hidden');
}

async function confirmAddService() {
    const slug = document.getElementById('newServiceSlug').value.trim();
    const name = document.getElementById('newServiceName').value.trim();
    const dept = document.getElementById('newServiceDept').value;
    if (!slug) { showToast('error', 'Service slug is required'); return; }
    if (!name) { showToast('error', 'Service name is required'); return; }
    try {
        const res  = await fetch(API.addService, {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ work_sys_id: WORK_SYS_ID, service_slug: slug, service_name: name, department_sys_id: dept }),
        });
        const json = await res.json();
        if (json.status === 'success') {
            showToast('success', 'Service added!' + (json.notification_sys_id ? ' Department notified ✓' : ''));
            closeModal('addServiceModal');
            loadWork();
        } else { showToast('error', json.message); }
    } catch { showToast('error', 'Network error'); }
}
</script>

<!-- ADD SERVICE MODAL -->
<div id="addServiceModal" class="fixed inset-0 z-50 hidden modal-bg flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between p-5 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800"><i class="fas fa-plus-circle mr-2 text-indigo-500"></i>Add Service to Work</h3>
            <button onclick="closeModal('addServiceModal')" class="text-gray-400 hover:text-gray-700 text-xl"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-5 space-y-4">
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Service Type <span class="text-red-400">*</span></label>
                <select id="newServiceSlug" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                    onchange="document.getElementById('newServiceName').value = this.options[this.selectedIndex].text.replace(/^.. /,'')">
                    <option value="">Select service type…</option>
                    <option value="air_ticket">Air Ticket</option>
                    <option value="visa">Visa</option>
                    <option value="hotel">Hotel</option>
                    <option value="tour_package">Tour Package</option>
                    <option value="umrah">Umrah</option>
                    <option value="transport">Transport</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Display Name <span class="text-red-400">*</span></label>
                <input type="text" id="newServiceName" placeholder="e.g. Air Ticket — DAC to DXB"
                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Assign to Department</label>
                <select id="newServiceDept" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <option value="">No department (assign later)</option>
                </select>
                <p class="text-xs text-gray-400 mt-1"><i class="fas fa-bell mr-1"></i>Department will receive a notification automatically.</p>
            </div>
        </div>
        <div class="flex gap-3 p-5 border-t border-gray-100">
            <button onclick="closeModal('addServiceModal')" class="flex-1 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition">Cancel</button>
            <button onclick="confirmAddService()" class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition">
                <i class="fas fa-plus mr-1"></i>Add Service
            </button>
        </div>
    </div>
</div>

</body>
</html>