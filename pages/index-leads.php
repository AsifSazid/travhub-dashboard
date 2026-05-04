<?php
// FILE PATH: /pages/index-leads.php
include_once('./authenticate.php');
$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898";
}
$getAllLeadsApi    = $ip_port . "api/leads/all-leads.php";
$updateStatusApi  = $ip_port . "api/leads/update-status.php";
$deleteLeadApi    = $ip_port . "api/leads/delete.php";
$moveToWorkApi    = $ip_port . "api/leads/move-to-work.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lead List</title>
    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png" sizes="16x16">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .badge { display: inline-flex; align-items: center; padding: 2px 10px; border-radius: 999px; font-size: .72rem; font-weight: 600; }
        .badge-pending  { background: #fef9c3; color: #854d0e; }
        .badge-active   { background: #dcfce7; color: #166534; }
        .badge-converted{ background: #dbeafe; color: #1e40af; }
        .badge-closed   { background: #fee2e2; color: #991b1b; }
        .badge-hold     { background: #f3e8ff; color: #6b21a8; }
        .action-btn { padding: 5px 9px; border-radius: 6px; font-size: .78rem; transition: all .15s; }
        tr:hover td { background: #f8f7ff; }
        .modal-bg { background: rgba(0,0,0,.45); backdrop-filter: blur(3px); }
        #advSearchPanel { max-height: 0; overflow: hidden; transition: max-height .3s ease; }
        #advSearchPanel.open { max-height: 400px; }
        .service-pill { display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 999px; font-size: .68rem; font-weight: 600; }
        .pill-umrah     { background:#fef3c7; color:#92400e; }
        .pill-transport { background:#f0fdf4; color:#166534; }
        .view-btn.active { background: #4f46e5; color: #fff; }
        .lead-card { transition: all .2s ease; border: 2px solid #f3f4f6; }
        .lead-card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(99,102,241,.1); border-color: #c7d2fe; }
        .pill-visa  { background:#ede9fe; color:#5b21b6; }
        .pill-hotel { background:#fce7f3; color:#9d174d; }
        .pill-air   { background:#e0f2fe; color:#075985; }
        .pill-tour  { background:#dcfce7; color:#14532d; }
    </style>
</head>
<body class="bg-gray-50 font-sans">

<?php include '../elements/header.php'; ?>
<?php include '../elements/aside.php'; ?>

<main id="mainContent" class="pt-16 pl-64 mt-16 transition-all duration-300">
<div class="p-6">

    <!-- Top Bar -->
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-list-check mr-2 text-indigo-500"></i>Lead List</h1>
            <p class="text-sm text-gray-500 mt-0.5">Manage and track all generated leads</p>
        </div>
        <div class="flex items-center gap-2">
            <div class="flex bg-gray-100 rounded-lg p-1">
                <button id="btnList" onclick="setView('list')" class="view-btn active px-3 py-1.5 rounded-md text-sm font-medium transition" title="List view"><i class="fas fa-list"></i></button>
                <button id="btnCard" onclick="setView('card')" class="view-btn px-3 py-1.5 rounded-md text-sm font-medium text-gray-500 transition" title="Card view"><i class="fas fa-th-large"></i></button>
            </div>
            <a href="create-leads.php" class="flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium text-sm transition shadow-sm">
                <i class="fas fa-plus"></i> Generate Lead
            </a>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-5" id="statsRow">
        <div class="bg-white rounded-xl p-4 shadow-sm border-l-4 border-gray-300 cursor-pointer stat-card" data-filter="">
            <div class="text-2xl font-bold text-gray-700" id="statAll">—</div>
            <div class="text-xs text-gray-500 mt-0.5">All Leads</div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border-l-4 border-yellow-400 cursor-pointer stat-card" data-filter="pending">
            <div class="text-2xl font-bold text-yellow-600" id="statPending">—</div>
            <div class="text-xs text-gray-500 mt-0.5">Pending</div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border-l-4 border-green-400 cursor-pointer stat-card" data-filter="active">
            <div class="text-2xl font-bold text-green-600" id="statActive">—</div>
            <div class="text-xs text-gray-500 mt-0.5">Active</div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border-l-4 border-blue-400 cursor-pointer stat-card" data-filter="converted">
            <div class="text-2xl font-bold text-blue-600" id="statConverted">—</div>
            <div class="text-xs text-gray-500 mt-0.5">Converted</div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border-l-4 border-red-400 cursor-pointer stat-card" data-filter="closed">
            <div class="text-2xl font-bold text-red-500" id="statClosed">—</div>
            <div class="text-xs text-gray-500 mt-0.5">Closed</div>
        </div>
    </div>

    <!-- Main Card -->
    <div class="bg-white rounded-xl shadow-sm p-5">

        <!-- Search & Filter Bar -->
        <div class="flex flex-wrap items-center gap-3 mb-4">
            <div class="relative flex-1 min-w-[200px]">
                <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-sm"></i>
                <input type="text" id="searchInput" placeholder="Search by name, ID, phone..."
                    class="pl-9 pr-4 py-2.5 border border-gray-300 rounded-lg text-sm w-full focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <select id="filterStatus" class="px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="active">Active</option>
                <option value="converted">Converted</option>
                <option value="hold">On Hold</option>
                <option value="closed">Closed</option>
            </select>
            <select id="filterService" class="px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <option value="">All Services</option>
                <option value="visa">Visa</option>
                <option value="hotel">Hotel</option>
                <option value="air">Air Ticket</option>
                <option value="tour">Tour Package</option>
                <option value="umrah">Umrah</option>
                <option value="transport">Transport</option>
            </select>
            <button onclick="toggleAdvSearch()" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg text-sm flex items-center gap-2 transition">
                <i class="fas fa-sliders"></i> Advanced
            </button>
            <button onclick="resetFilters()" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg text-sm transition">
                <i class="fas fa-rotate-right"></i>
            </button>
        </div>

        <!-- Advanced Search Panel -->
        <div id="advSearchPanel" class="bg-gray-50 rounded-lg px-5 py-0 mb-3">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 py-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Date From</label>
                    <input type="date" id="advDateFrom" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Date To</label>
                    <input type="date" id="advDateTo" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Lead Source</label>
                    <select id="advSource" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="">Any</option>
                        <option value="walk_in">Walk-in</option>
                        <option value="referral">Referral</option>
                        <option value="facebook">Facebook</option>
                        <option value="website">Website</option>
                        <option value="phone_call">Phone Call</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            </div>
            <div class="pb-3 flex justify-end">
                <button onclick="applyFilters()" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition">
                    Apply Filters
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-10">#</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Lead ID</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Client</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Services</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Created</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="leadsTableBody" class="divide-y divide-gray-50">
                    <tr><td colspan="7" class="text-center py-12 text-gray-400"><i class="fas fa-spinner fa-spin text-2xl"></i></td></tr>
                </tbody>
            </table>
        </div>

        <!-- CARD VIEW -->
        <div id="cardView" class="hidden">
            <div id="leadsCardGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-4"></div>
        </div>

        <!-- Pagination -->
        <div class="flex items-center justify-between mt-5 pt-4 border-t border-gray-100">
            <div class="text-sm text-gray-500" id="paginationInfo">—</div>
            <div class="flex items-center gap-2" id="paginationBtns"></div>
        </div>
    </div>
</div>
</main>

<!-- ═══ SHOW LEAD MODAL ═══ -->
<div id="showModal" class="fixed inset-0 z-50 hidden modal-bg flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto modal-slide-in">
        <div class="flex items-center justify-between p-6 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-800"><i class="fas fa-eye mr-2 text-indigo-500"></i>Lead Details</h3>
            <button onclick="closeModal('showModal')" class="text-gray-400 hover:text-gray-700 text-xl"><i class="fas fa-times"></i></button>
        </div>
        <div id="showModalContent" class="p-6"></div>
    </div>
</div>

<!-- ═══ CHANGE STATUS MODAL ═══ -->
<div id="statusModal" class="fixed inset-0 z-50 hidden modal-bg flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm modal-slide-in">
        <div class="flex items-center justify-between p-5 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-800"><i class="fas fa-tag mr-2 text-indigo-500"></i>Change Status</h3>
            <button onclick="closeModal('statusModal')" class="text-gray-400 hover:text-gray-700 text-xl"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-5">
            <p class="text-sm text-gray-600 mb-4">Select new status for this lead:</p>
            <input type="hidden" id="statusLeadId">
            <div class="grid grid-cols-2 gap-3">
                <button onclick="changeStatus('pending')"   class="status-pick-btn py-2 rounded-lg bg-yellow-50 text-yellow-700 border border-yellow-200 font-medium text-sm hover:bg-yellow-100 transition">⏳ Pending</button>
                <button onclick="changeStatus('active')"    class="status-pick-btn py-2 rounded-lg bg-green-50 text-green-700 border border-green-200 font-medium text-sm hover:bg-green-100 transition">✅ Active</button>
                <button onclick="changeStatus('converted')" class="status-pick-btn py-2 rounded-lg bg-blue-50 text-blue-700 border border-blue-200 font-medium text-sm hover:bg-blue-100 transition">🔄 Converted</button>
                <button onclick="changeStatus('hold')"      class="status-pick-btn py-2 rounded-lg bg-purple-50 text-purple-700 border border-purple-200 font-medium text-sm hover:bg-purple-100 transition">⏸ On Hold</button>
                <button onclick="changeStatus('closed')"    class="status-pick-btn col-span-2 py-2 rounded-lg bg-red-50 text-red-700 border border-red-200 font-medium text-sm hover:bg-red-100 transition">❌ Closed</button>
            </div>
        </div>
    </div>
</div>

<!-- ═══ DELETE CONFIRM MODAL ═══ -->
<div id="deleteModal" class="fixed inset-0 z-50 hidden modal-bg flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm modal-slide-in">
        <div class="p-6 text-center">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-trash-alt text-red-500 text-2xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Delete Lead?</h3>
            <p class="text-sm text-gray-500 mb-6">This action cannot be undone. The lead will be permanently deleted.</p>
            <input type="hidden" id="deleteLeadId">
            <div class="flex gap-3">
                <button onclick="closeModal('deleteModal')" class="flex-1 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium text-sm transition">Cancel</button>
                <button onclick="confirmDelete()" class="flex-1 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium text-sm transition">Yes, Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- ═══ MOVE TO WORK CONFIRM MODAL ═══ -->
<div id="moveModal" class="fixed inset-0 z-50 hidden modal-bg flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm modal-slide-in">
        <div class="p-6 text-center">
            <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-briefcase text-indigo-500 text-2xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Move to Workboard?</h3>
            <p class="text-sm text-gray-500 mb-6">This lead will be converted to a work entry and status will be updated to <b>Converted</b>.</p>
            <input type="hidden" id="moveLeadId">
            <div class="flex gap-3">
                <button onclick="closeModal('moveModal')" class="flex-1 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium text-sm transition">Cancel</button>
                <button onclick="confirmMove()" class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium text-sm transition">
                    <i class="fas fa-arrow-right mr-1"></i> Move
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="toast" class="fixed bottom-6 right-6 z-50 hidden">
    <div id="toastInner" class="flex items-center gap-3 px-5 py-3 rounded-xl shadow-lg text-white text-sm font-medium">
        <i id="toastIcon" class="fas fa-check-circle text-lg"></i>
        <span id="toastMsg"></span>
    </div>
</div>

<!-- Floating Quick Access Tab -->
<?php include '../elements/floating-menus.php'; ?>

<!-- script.js MUST load before page scripts (sidebar/header/accordion JS) -->
<script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>

<script>
const API = {
    all:    "<?php echo $getAllLeadsApi; ?>",
    status: "<?php echo $updateStatusApi; ?>",
    delete: "<?php echo $deleteLeadApi; ?>",
    move:   "<?php echo $moveToWorkApi; ?>",
};

const SERVICE_LABELS = {
    visa:      { icon: 'fa-passport',         label: 'Visa',         cls: 'pill-visa' },
    hotel:     { icon: 'fa-hotel',            label: 'Hotel',        cls: 'pill-hotel' },
    air:       { icon: 'fa-plane',            label: 'Air Ticket',   cls: 'pill-air' },
    tour:      { icon: 'fa-suitcase-rolling', label: 'Tour Package', cls: 'pill-tour' },
    umrah:     { icon: 'fa-kaaba',            label: 'Umrah',        cls: 'pill-umrah' },
    transport: { icon: 'fa-bus',              label: 'Transport',    cls: 'pill-transport' },
};

// ─── State ───────────────────────────────────────────
let allLeads       = [];
let filteredLeads  = [];
let currentPage    = 1;
let currentView    = 'list';
const perPage      = 15;

// ─── Load Leads ───────────────────────────────────────
async function loadLeads() {
    try {
        const res  = await fetch(API.all);
        const json = await res.json();
        allLeads = json.leads ?? [];
        updateStats();
        applyFilters();
    } catch {
        document.getElementById('leadsTableBody').innerHTML =
            `<tr><td colspan="7" class="text-center py-10 text-red-400"><i class="fas fa-exclamation-circle mr-2"></i>Failed to load leads.</td></tr>`;
    }
}

function updateStats() {
    const counts = { pending:0, active:0, converted:0, hold:0, closed:0 };
    allLeads.forEach(l => { if (counts[l.lead_status] !== undefined) counts[l.lead_status]++; });
    document.getElementById('statAll').textContent       = allLeads.length;
    document.getElementById('statPending').textContent   = counts.pending;
    document.getElementById('statActive').textContent    = counts.active;
    document.getElementById('statConverted').textContent = counts.converted;
    document.getElementById('statClosed').textContent    = counts.closed;
}

// ─── Filters ──────────────────────────────────────────
let activeStatFilter = '';

document.querySelectorAll('.stat-card').forEach(card => {
    card.addEventListener('click', () => {
        activeStatFilter = card.dataset.filter;
        document.getElementById('filterStatus').value = activeStatFilter;
        applyFilters();
    });
});

function applyFilters() {
    const search  = document.getElementById('searchInput').value.toLowerCase().trim();
    const status  = document.getElementById('filterStatus').value;
    const service = document.getElementById('filterService').value;
    const dateFrom= document.getElementById('advDateFrom').value;
    const dateTo  = document.getElementById('advDateTo').value;
    const source  = document.getElementById('advSource').value;

    filteredLeads = allLeads.filter(l => {
        const ci = safeParse(l.client_info) ?? {};
        const li = safeParse(l.lead_info) ?? {};
        const st = safeParse(l.service_type) ?? [];

        const matchSearch = !search ||
            (ci.name ?? '').toLowerCase().includes(search) ||
            (ci.phone ?? '').includes(search) ||
            (l.sys_id ?? '').toLowerCase().includes(search);

        const matchStatus  = !status  || l.lead_status === status;
        const matchService = !service || (Array.isArray(st) ? st.includes(service) : false);
        const matchSource  = !source  || (li.source ?? '') === source;

        let matchDate = true;
        if (dateFrom || dateTo) {
            const d = new Date(l.created_at);
            if (dateFrom && d < new Date(dateFrom)) matchDate = false;
            if (dateTo   && d > new Date(dateTo + 'T23:59:59')) matchDate = false;
        }

        return matchSearch && matchStatus && matchService && matchSource && matchDate;
    });

    currentPage = 1;
    render();
}

function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('filterStatus').value = '';
    document.getElementById('filterService').value = '';
    document.getElementById('advDateFrom').value = '';
    document.getElementById('advDateTo').value = '';
    document.getElementById('advSource').value = '';
    activeStatFilter = '';
    applyFilters();
}

document.getElementById('searchInput').addEventListener('input', applyFilters);
document.getElementById('filterStatus').addEventListener('change', applyFilters);
document.getElementById('filterService').addEventListener('change', applyFilters);

// ─── View switcher ────────────────────────────────────
function setView(view) {
    currentView = view;
    const listView = document.getElementById('leadsTableBody').closest('.overflow-x-auto');
    const cardView = document.getElementById('cardView');
    const btnList  = document.getElementById('btnList');
    const btnCard  = document.getElementById('btnCard');
    if (view === 'list') {
        listView.classList.remove('hidden');
        cardView.classList.add('hidden');
        btnList.classList.add('active'); btnList.classList.remove('text-gray-500');
        btnCard.classList.remove('active'); btnCard.classList.add('text-gray-500');
    } else {
        listView.classList.add('hidden');
        cardView.classList.remove('hidden');
        btnCard.classList.add('active'); btnCard.classList.remove('text-gray-500');
        btnList.classList.remove('active'); btnList.classList.add('text-gray-500');
    }
    render();
}

function render() {
    currentView === 'list' ? renderTable() : renderCards();
    renderPagination();
}

// ─── Render Cards ─────────────────────────────────────
function renderCards() {
    const grid  = document.getElementById('leadsCardGrid');
    const start = (currentPage - 1) * perPage;
    const page  = filteredLeads.slice(start, start + perPage);

    if (!page.length) {
        grid.innerHTML = `<div class="col-span-3 text-center py-12 text-gray-400"><i class="fas fa-inbox text-3xl mb-2 block"></i>No leads found.</div>`;
        return;
    }

    grid.innerHTML = page.map(l => {
        const ci = safeParse(l.client_info) ?? {};
        const st = safeParse(l.service_type) ?? [];
        const services = Array.isArray(st) ? st : [st];
        const pillsHtml = services.map(s => {
            const info = SERVICE_LABELS[s] ?? { icon:'fa-circle', label: s, cls:'' };
            return `<span class="service-pill ${info.cls}"><i class="fas ${info.icon}"></i>${info.label}</span>`;
        }).join(' ');

        return `
        <div class="lead-card bg-white rounded-xl p-5">
            <div class="flex items-start justify-between mb-3">
                <span class="font-mono text-xs text-indigo-600 font-semibold bg-indigo-50 px-2 py-1 rounded-md">${l.sys_id}</span>
                ${badgeHtml(l.lead_status)}
            </div>
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center font-bold flex-shrink-0">
                    ${(ci.name?.[0] ?? '?').toUpperCase()}
                </div>
                <div>
                    <div class="font-semibold text-gray-800 text-sm">${ci.name ?? '—'}</div>
                    <div class="text-xs text-gray-400">${ci.phone ?? ''}</div>
                </div>
            </div>
            <div class="flex flex-wrap gap-1 mb-3">${pillsHtml}</div>
            <div class="text-xs text-gray-400 mb-4"><i class="fas fa-calendar mr-1"></i>${formatDate(l.created_at)}</div>
            <div class="flex items-center gap-1 pt-3 border-t border-gray-100 flex-wrap">
                <button onclick="showLead('${l.sys_id}')" class="action-btn bg-gray-100 hover:bg-gray-200 text-gray-600" title="View"><i class="fas fa-eye"></i></button>
                <a href="edit-lead.php?id=${l.sys_id}" class="action-btn bg-yellow-50 hover:bg-yellow-100 text-yellow-700" title="Edit"><i class="fas fa-pencil"></i></a>
                <button onclick="openMoveModal('${l.sys_id}')" class="action-btn bg-indigo-50 hover:bg-indigo-100 text-indigo-700" title="Move to Workboard"><i class="fas fa-briefcase"></i></button>
                <button onclick="openStatusModal('${l.sys_id}')" class="action-btn bg-blue-50 hover:bg-blue-100 text-blue-700" title="Change Status"><i class="fas fa-tag"></i></button>
                <button onclick="openDeleteModal('${l.sys_id}')" class="action-btn bg-red-50 hover:bg-red-100 text-red-600" title="Delete"><i class="fas fa-trash-alt"></i></button>
            </div>
        </div>`;
    }).join('');
}

// ─── Render Table ─────────────────────────────────────
function renderTable() {
    const tbody = document.getElementById('leadsTableBody');
    const start = (currentPage - 1) * perPage;
    const page  = filteredLeads.slice(start, start + perPage);

    if (!page.length) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center py-12 text-gray-400"><i class="fas fa-inbox text-3xl mb-2 block"></i>No leads found.</td></tr>`;
        renderPagination();
        return;
    }

    tbody.innerHTML = page.map((l, idx) => {
        const ci = safeParse(l.client_info) ?? {};
        const st = safeParse(l.service_type) ?? [];
        const services = Array.isArray(st) ? st : [st];
        const pillsHtml = services.map(s => {
            const info = SERVICE_LABELS[s] ?? { icon:'fa-circle', label: s, cls:'' };
            return `<span class="service-pill ${info.cls}"><i class="fas ${info.icon}"></i>${info.label}</span>`;
        }).join(' ');

        const statusBadge = badgeHtml(l.lead_status);
        const createdAt   = formatDate(l.created_at);

        return `
        <tr class="hover:bg-gray-50 transition-colors">
            <td class="px-4 py-3 text-gray-500 text-xs">${start + idx + 1}</td>
            <td class="px-4 py-3 font-mono text-xs text-indigo-600 font-semibold">${l.sys_id}</td>
            <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center font-bold text-sm flex-shrink-0">
                        ${(ci.name?.[0] ?? '?').toUpperCase()}
                    </div>
                    <div>
                        <div class="font-medium text-gray-800 text-sm">${ci.name ?? '—'}</div>
                        <div class="text-xs text-gray-400">${ci.phone ?? ''}</div>
                    </div>
                </div>
            </td>
            <td class="px-4 py-3"><div class="flex flex-wrap gap-1">${pillsHtml}</div></td>
            <td class="px-4 py-3">${statusBadge}</td>
            <td class="px-4 py-3 text-xs text-gray-500">${createdAt}</td>
            <td class="px-4 py-3">
                <div class="flex items-center gap-1 flex-wrap">
                    <button onclick="showLead('${l.sys_id}')" class="action-btn bg-gray-100 hover:bg-gray-200 text-gray-600" title="View">
                        <i class="fas fa-eye"></i>
                    </button>
                    <a href="edit-lead.php?id=${l.sys_id}" class="action-btn bg-yellow-50 hover:bg-yellow-100 text-yellow-700" title="Edit">
                        <i class="fas fa-pencil"></i>
                    </a>
                    <button onclick="openMoveModal('${l.sys_id}')" class="action-btn bg-indigo-50 hover:bg-indigo-100 text-indigo-700" title="Move to Workboard">
                        <i class="fas fa-briefcase"></i>
                    </button>
                    <button onclick="openStatusModal('${l.sys_id}')" class="action-btn bg-blue-50 hover:bg-blue-100 text-blue-700" title="Change Status">
                        <i class="fas fa-tag"></i>
                    </button>
                    <button onclick="openDeleteModal('${l.sys_id}')" class="action-btn bg-red-50 hover:bg-red-100 text-red-600" title="Delete">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');

}

function renderPagination() {
    const total = filteredLeads.length;
    const pages = Math.ceil(total / perPage);
    const start = total ? (currentPage - 1) * perPage + 1 : 0;
    const end   = Math.min(currentPage * perPage, total);

    document.getElementById('paginationInfo').textContent = total
        ? `Showing ${start}–${end} of ${total} leads`
        : 'No results';

    const btns = document.getElementById('paginationBtns');
    btns.innerHTML = '';

    if (pages <= 1) return;

    const addBtn = (label, page, disabled, active) => {
        const btn = document.createElement('button');
        btn.innerHTML = label;
        btn.className = `px-3 py-1.5 rounded-lg text-sm border font-medium transition ${
            active   ? 'bg-indigo-600 text-white border-indigo-600' :
            disabled ? 'bg-gray-50 text-gray-300 border-gray-200 cursor-not-allowed' :
                       'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'}`;
        if (!disabled && !active) btn.onclick = () => { currentPage = page; renderTable(); };
        btns.appendChild(btn);
    };

    addBtn('<i class="fas fa-chevron-left text-xs"></i>', currentPage - 1, currentPage === 1);
    for (let p = 1; p <= pages; p++) {
        if (pages > 7 && p > 2 && p < pages - 1 && Math.abs(p - currentPage) > 1) {
            if (p === 3 || p === pages - 2) { const el = document.createElement('span'); el.textContent = '…'; el.className = 'px-2 text-gray-400'; btns.appendChild(el); }
            continue;
        }
        addBtn(p, p, false, p === currentPage);
    }
    addBtn('<i class="fas fa-chevron-right text-xs"></i>', currentPage + 1, currentPage === pages);
}

// ─── Show Lead Modal ──────────────────────────────────
function showLead(sysId) {
    const lead = allLeads.find(l => l.sys_id === sysId);
    if (!lead) return;

    const ci = safeParse(lead.client_info) ?? {};
    const li = safeParse(lead.lead_info) ?? {};
    const sd = safeParse(lead.service_data) ?? {};
    const st = safeParse(lead.service_type) ?? [];
    const services = Array.isArray(st) ? st : [st];

    let serviceHtml = services.map(svc => {
        const info = SERVICE_LABELS[svc] ?? { icon:'fa-circle', label: svc, cls:'' };
        const svcData = sd[svc] ?? {};
        const rows = Object.entries(svcData).map(([k,v]) =>
            v ? `<div class="flex justify-between py-1.5 border-b border-gray-50 text-sm"><span class="text-gray-500 capitalize">${k.replace(/_/g,' ')}</span><span class="font-medium text-gray-700">${v}</span></div>` : ''
        ).join('');
        return `
        <div class="mb-4">
            <div class="flex items-center gap-2 mb-2">
                <span class="service-pill ${info.cls} text-sm py-1 px-3"><i class="fas ${info.icon} mr-1"></i>${info.label}</span>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">${rows || '<span class="text-sm text-gray-400">No details recorded.</span>'}</div>
        </div>`;
    }).join('');

    document.getElementById('showModalContent').innerHTML = `
        <div class="grid grid-cols-2 gap-4 mb-5">
            <div class="col-span-2 flex items-center gap-3">
                <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-600 font-bold text-xl">
                    ${(ci.name?.[0] ?? '?').toUpperCase()}
                </div>
                <div>
                    <div class="text-lg font-bold text-gray-800">${ci.name ?? '—'}</div>
                    <div class="text-sm text-gray-500">${ci.phone ?? ''} ${ci.email ? '· '+ci.email : ''}</div>
                </div>
                <div class="ml-auto">${badgeHtml(lead.lead_status)}</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <div class="text-xs text-gray-400 uppercase mb-1">Lead ID</div>
                <div class="font-mono font-semibold text-indigo-600">${lead.sys_id}</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <div class="text-xs text-gray-400 uppercase mb-1">Source</div>
                <div class="text-sm font-medium text-gray-700">${li.source ?? '—'}</div>
            </div>
            <div class="col-span-2 bg-gray-50 rounded-lg p-3">
                <div class="text-xs text-gray-400 uppercase mb-1">Notes</div>
                <div class="text-sm text-gray-700">${li.notes || '—'}</div>
            </div>
        </div>
        <h4 class="font-semibold text-gray-700 mb-3 text-sm uppercase tracking-wide">Service Details</h4>
        ${serviceHtml}
        <div class="mt-4 flex justify-between items-center text-xs text-gray-400">
            <span>Created: ${formatDate(lead.created_at)}</span>
            <div class="flex gap-2">
                <a href="edit-lead.php?id=${lead.sys_id}" class="px-4 py-2 bg-yellow-50 hover:bg-yellow-100 text-yellow-700 rounded-lg text-sm font-medium transition">
                    <i class="fas fa-pencil mr-1"></i>Edit
                </a>
                <button onclick="closeModal('showModal'); openMoveModal('${lead.sys_id}')" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition">
                    <i class="fas fa-briefcase mr-1"></i>Move to Work
                </button>
            </div>
        </div>`;

    document.getElementById('showModal').classList.remove('hidden');
}

// ─── Status Modal ─────────────────────────────────────
function openStatusModal(sysId) {
    document.getElementById('statusLeadId').value = sysId;
    document.getElementById('statusModal').classList.remove('hidden');
}

async function changeStatus(newStatus) {
    const sysId = document.getElementById('statusLeadId').value;
    closeModal('statusModal');
    try {
        const res  = await fetch(API.status, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ sys_id: sysId, status: newStatus }),
        });
        const json = await res.json();
        if (json.success || json.status === 'success') {
            showToast('success', 'Status updated successfully.');
            loadLeads();
        } else {
            showToast('error', json.message || 'Failed to update status.');
        }
    } catch {
        showToast('error', 'Network error.');
    }
}

// ─── Delete Modal ─────────────────────────────────────
function openDeleteModal(sysId) {
    document.getElementById('deleteLeadId').value = sysId;
    document.getElementById('deleteModal').classList.remove('hidden');
}

async function confirmDelete() {
    const sysId = document.getElementById('deleteLeadId').value;
    closeModal('deleteModal');
    try {
        const res  = await fetch(API.delete, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ sys_id: sysId }),
        });
        const json = await res.json();
        if (json.success || json.status === 'success') {
            showToast('success', 'Lead deleted.');
            loadLeads();
        } else {
            showToast('error', json.message || 'Failed to delete.');
        }
    } catch {
        showToast('error', 'Network error.');
    }
}

// ─── Move to Work Modal ───────────────────────────────
function openMoveModal(sysId) {
    document.getElementById('moveLeadId').value = sysId;
    document.getElementById('moveModal').classList.remove('hidden');
}

async function confirmMove() {
    const sysId = document.getElementById('moveLeadId').value;
    closeModal('moveModal');
    try {
        const res  = await fetch(API.move, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ sys_id: sysId }),
        });
        const json = await res.json();
        if (json.success || json.status === 'success') {
            showToast('success', 'Lead moved to workboard!');
            setTimeout(() => { window.location.href = 'index-works.php'; }, 1200);
        } else {
            showToast('error', json.message || 'Failed to move.');
        }
    } catch {
        showToast('error', 'Network error.');
    }
}

// ─── Advanced Search Toggle ───────────────────────────
function toggleAdvSearch() {
    document.getElementById('advSearchPanel').classList.toggle('open');
}

// ─── Helpers ──────────────────────────────────────────
function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

document.querySelectorAll('.modal-bg').forEach(m => {
    m.addEventListener('click', e => { if (e.target === m) m.classList.add('hidden'); });
});

function safeParse(v) {
    if (!v) return null;
    if (typeof v === 'object') return v;
    try { return JSON.parse(v); } catch { return null; }
}

function formatDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' });
}

function badgeHtml(status) {
    const map = {
        pending:   ['badge-pending',   '⏳ Pending'],
        active:    ['badge-active',    '✅ Active'],
        converted: ['badge-converted', '🔄 Converted'],
        hold:      ['badge badge-hold','⏸ On Hold'],
        closed:    ['badge-closed',    '❌ Closed'],
    };
    const [cls, label] = map[status] ?? ['', status];
    return `<span class="badge ${cls}">${label}</span>`;
}

function showToast(type, msg) {
    const toast = document.getElementById('toast');
    const inner = document.getElementById('toastInner');
    const icon  = document.getElementById('toastIcon');
    document.getElementById('toastMsg').textContent = msg;
    inner.className = `flex items-center gap-3 px-5 py-3 rounded-xl shadow-lg text-white text-sm font-medium ${type==='success'?'bg-green-600':'bg-red-500'}`;
    icon.className  = `fas ${type==='success'?'fa-check-circle':'fa-exclamation-circle'} text-lg`;
    toast.classList.remove('hidden');
    setTimeout(() => toast.classList.add('hidden'), 3500);
}

loadLeads();
</script>
</body>
</html>