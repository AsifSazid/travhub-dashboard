<?php
// FILE PATH: /pages/index-works.php
include_once('./authenticate.php');
$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) { $ip_port = "http://103.104.219.3:898"; }
$getAllWorksApi    = $ip_port . "api/works/all-works.php";
$updateStatusApi  = $ip_port . "api/works/update-status.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Work List — TravHub</title>
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

        .action-btn { padding:5px 9px; border-radius:6px; font-size:.78rem; transition:all .15s; }
        tr:hover td { background:#f8f7ff; }
        .modal-bg { background:rgba(0,0,0,.45); backdrop-filter:blur(3px); }

        .service-pill { display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:999px; font-size:.68rem; font-weight:600; }
        .pill-air      { background:#e0f2fe; color:#075985; }
        .pill-visa     { background:#ede9fe; color:#5b21b6; }
        .pill-hotel    { background:#fce7f3; color:#9d174d; }
        .pill-tour     { background:#dcfce7; color:#14532d; }
        .pill-umrah    { background:#fef3c7; color:#92400e; }
        .pill-transport{ background:#f0fdf4; color:#166534; }

        .work-card { transition:all .2s ease; border:2px solid #f3f4f6; }
        .work-card:hover { transform:translateY(-2px); box-shadow:0 8px 25px rgba(99,102,241,.1); border-color:#c7d2fe; }

        .view-btn.active { background:#4f46e5; color:#fff; }
        #advSearchPanel { max-height:0; overflow:hidden; transition:max-height .3s ease; }
        #advSearchPanel.open { max-height:300px; }
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
            <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-briefcase mr-2 text-indigo-500"></i>Work List</h1>
            <p class="text-sm text-gray-500 mt-0.5">Track and manage all confirmed works</p>
        </div>
        <div class="flex items-center gap-2">
            <div class="flex bg-gray-100 rounded-lg p-1">
                <button id="btnList" onclick="setView('list')" class="view-btn active px-3 py-1.5 rounded-md text-sm font-medium transition" title="List view"><i class="fas fa-list"></i></button>
                <button id="btnCard" onclick="setView('card')" class="view-btn px-3 py-1.5 rounded-md text-sm font-medium text-gray-500 transition" title="Card view"><i class="fas fa-th-large"></i></button>
            </div>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-5">
        <div class="bg-white rounded-xl p-4 shadow-sm border-l-4 border-gray-300 cursor-pointer stat-card" data-filter="">
            <div class="text-2xl font-bold text-gray-700" id="statAll">—</div>
            <div class="text-xs text-gray-500 mt-0.5">All Works</div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border-l-4 border-yellow-400 cursor-pointer stat-card" data-filter="open">
            <div class="text-2xl font-bold text-yellow-600" id="statOpen">—</div>
            <div class="text-xs text-gray-500 mt-0.5">Open</div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border-l-4 border-blue-400 cursor-pointer stat-card" data-filter="in_progress">
            <div class="text-2xl font-bold text-blue-600" id="statProgress">—</div>
            <div class="text-xs text-gray-500 mt-0.5">In Progress</div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border-l-4 border-green-400 cursor-pointer stat-card" data-filter="done">
            <div class="text-2xl font-bold text-green-600" id="statDone">—</div>
            <div class="text-xs text-gray-500 mt-0.5">Done</div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border-l-4 border-red-400 cursor-pointer stat-card" data-filter="cancelled">
            <div class="text-2xl font-bold text-red-500" id="statCancelled">—</div>
            <div class="text-xs text-gray-500 mt-0.5">Cancelled</div>
        </div>
    </div>

    <!-- Main Card -->
    <div class="bg-white rounded-xl shadow-sm p-5">

        <!-- Search & Filter -->
        <div class="flex flex-wrap items-center gap-3 mb-4">
            <div class="relative flex-1 min-w-[200px]">
                <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-sm"></i>
                <input type="text" id="searchInput" placeholder="Search by client, ID…"
                    class="pl-9 pr-4 py-2.5 border border-gray-300 rounded-lg text-sm w-full focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <select id="filterStatus" class="px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <option value="">All Status</option>
                <option value="open">Open</option>
                <option value="in_progress">In Progress</option>
                <option value="done">Done</option>
                <option value="cancelled">Cancelled</option>
            </select>
            <select id="filterService" class="px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <option value="">All Services</option>
                <option value="air_ticket">Air Ticket</option>
                <option value="visa">Visa</option>
                <option value="hotel">Hotel</option>
                <option value="tour_package">Tour Package</option>
                <option value="umrah">Umrah</option>
                <option value="transport">Transport</option>
            </select>
            <button onclick="resetFilters()" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg text-sm transition">
                <i class="fas fa-rotate-right"></i>
            </button>
        </div>

        <!-- Table View -->
        <div id="tableView" class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase w-10">#</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Work ID</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Client</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Services</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Created</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody id="worksTableBody" class="divide-y divide-gray-50">
                    <tr><td colspan="7" class="text-center py-12 text-gray-400"><i class="fas fa-spinner fa-spin text-2xl"></i></td></tr>
                </tbody>
            </table>
        </div>

        <!-- Card View -->
        <div id="cardView" class="hidden">
            <div id="worksCardGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"></div>
        </div>

        <!-- Pagination -->
        <div class="flex items-center justify-between mt-5 pt-4 border-t border-gray-100">
            <div class="text-sm text-gray-500" id="paginationInfo">—</div>
            <div class="flex items-center gap-2" id="paginationBtns"></div>
        </div>
    </div>
</div>
</main>

<!-- STATUS MODAL -->
<div id="statusModal" class="fixed inset-0 z-50 hidden modal-bg flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
        <div class="flex items-center justify-between p-5 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-800"><i class="fas fa-tag mr-2 text-indigo-500"></i>Change Status</h3>
            <button onclick="closeModal('statusModal')" class="text-gray-400 hover:text-gray-700 text-xl"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-5">
            <input type="hidden" id="statusWorkId">
            <div class="grid grid-cols-2 gap-3">
                <button onclick="changeStatus('open')"        class="py-2 rounded-lg bg-yellow-50 text-yellow-700 border border-yellow-200 font-medium text-sm hover:bg-yellow-100 transition">🟡 Open</button>
                <button onclick="changeStatus('in_progress')" class="py-2 rounded-lg bg-blue-50 text-blue-700 border border-blue-200 font-medium text-sm hover:bg-blue-100 transition">🔵 In Progress</button>
                <button onclick="changeStatus('done')"        class="py-2 rounded-lg bg-green-50 text-green-700 border border-green-200 font-medium text-sm hover:bg-green-100 transition">✅ Done</button>
                <button onclick="changeStatus('cancelled')"   class="py-2 rounded-lg bg-red-50 text-red-700 border border-red-200 font-medium text-sm hover:bg-red-100 transition">❌ Cancelled</button>
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

<?php include '../elements/floating-menus.php'; ?>
<script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>
<script>
const API = {
    all:    "<?php echo $getAllWorksApi; ?>",
    status: "<?php echo $updateStatusApi; ?>",
};

const SERVICE_LABELS = {
    air_ticket:   { icon:'fa-plane',            label:'Air Ticket',   cls:'pill-air' },
    visa:         { icon:'fa-passport',          label:'Visa',         cls:'pill-visa' },
    hotel:        { icon:'fa-hotel',             label:'Hotel',        cls:'pill-hotel' },
    tour_package: { icon:'fa-suitcase-rolling',  label:'Tour Package', cls:'pill-tour' },
    umrah:        { icon:'fa-kaaba',             label:'Umrah',        cls:'pill-umrah' },
    transport:    { icon:'fa-bus',               label:'Transport',    cls:'pill-transport' },
};

let allWorks      = [];
let filteredWorks = [];
let currentPage   = 1;
let currentView   = 'list';
const perPage     = 15;

// ── Load ──────────────────────────────────────────────────
async function loadWorks() {
    try {
        const res  = await fetch(API.all);
        const json = await res.json();
        allWorks = json.works ?? [];
        updateStats();
        applyFilters();
    } catch {
        document.getElementById('worksTableBody').innerHTML =
            `<tr><td colspan="7" class="text-center py-10 text-red-400"><i class="fas fa-exclamation-circle mr-2"></i>Failed to load works.</td></tr>`;
    }
}

function updateStats() {
    const counts = { open:0, in_progress:0, done:0, cancelled:0 };
    allWorks.forEach(w => { if (counts[w.work_status] !== undefined) counts[w.work_status]++; });
    document.getElementById('statAll').textContent       = allWorks.length;
    document.getElementById('statOpen').textContent      = counts.open;
    document.getElementById('statProgress').textContent  = counts.in_progress;
    document.getElementById('statDone').textContent      = counts.done;
    document.getElementById('statCancelled').textContent = counts.cancelled;
}

// ── Filters ───────────────────────────────────────────────
document.querySelectorAll('.stat-card').forEach(card => {
    card.addEventListener('click', () => {
        document.getElementById('filterStatus').value = card.dataset.filter;
        applyFilters();
    });
});

function applyFilters() {
    const search  = document.getElementById('searchInput').value.toLowerCase().trim();
    const status  = document.getElementById('filterStatus').value;
    const service = document.getElementById('filterService').value;

    filteredWorks = allWorks.filter(w => {
        const ci = safeParse(w.client_info) ?? {};
        const st = safeParse(w.service_type) ?? [];
        const matchSearch  = !search || (ci.name ?? '').toLowerCase().includes(search) || (w.sys_id ?? '').toLowerCase().includes(search);
        const matchStatus  = !status  || w.work_status === status;
        const matchService = !service || (Array.isArray(st) ? st.includes(service) : false);
        return matchSearch && matchStatus && matchService;
    });

    currentPage = 1;
    render();
}

function resetFilters() {
    document.getElementById('searchInput').value  = '';
    document.getElementById('filterStatus').value = '';
    document.getElementById('filterService').value= '';
    applyFilters();
}

document.getElementById('searchInput').addEventListener('input', applyFilters);
document.getElementById('filterStatus').addEventListener('change', applyFilters);
document.getElementById('filterService').addEventListener('change', applyFilters);

// ── View ──────────────────────────────────────────────────
function setView(view) {
    currentView = view;
    const tbl  = document.getElementById('tableView');
    const crd  = document.getElementById('cardView');
    const btnL = document.getElementById('btnList');
    const btnC = document.getElementById('btnCard');
    if (view === 'list') {
        tbl.classList.remove('hidden'); crd.classList.add('hidden');
        btnL.classList.add('active');   btnC.classList.remove('active'); btnC.classList.add('text-gray-500');
    } else {
        tbl.classList.add('hidden');    crd.classList.remove('hidden');
        btnC.classList.add('active');   btnL.classList.remove('active'); btnL.classList.add('text-gray-500');
    }
    render();
}

function render() { currentView === 'list' ? renderTable() : renderCards(); renderPagination(); }

// ── Render Table ─────────────────────────────────────────
function renderTable() {
    const tbody = document.getElementById('worksTableBody');
    const start = (currentPage - 1) * perPage;
    const page  = filteredWorks.slice(start, start + perPage);

    if (!page.length) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center py-12 text-gray-400"><i class="fas fa-inbox text-3xl mb-2 block"></i>No works found.</td></tr>`;
        return;
    }

    tbody.innerHTML = page.map((w, idx) => {
        const ci = safeParse(w.client_info) ?? {};
        const st = safeParse(w.service_type) ?? [];
        const services = Array.isArray(st) ? st : [st];
        const pillsHtml = services.map(s => {
            const info = SERVICE_LABELS[s] ?? { icon:'fa-circle', label:s, cls:'' };
            return `<span class="service-pill ${info.cls}"><i class="fas ${info.icon}"></i>${info.label}</span>`;
        }).join(' ');

        return `
        <tr class="hover:bg-gray-50 transition-colors">
            <td class="px-4 py-3 text-gray-500 text-xs">${start + idx + 1}</td>
            <td class="px-4 py-3 font-mono text-xs text-indigo-600 font-semibold">${w.sys_id}</td>
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
            <td class="px-4 py-3">${badgeHtml(w.work_status)}</td>
            <td class="px-4 py-3 text-xs text-gray-500">${formatDate(w.extracted_date)}</td>
            <td class="px-4 py-3">
                <div class="flex items-center gap-1">
                    <a href="show-works.php?id=${w.sys_id}" class="action-btn bg-indigo-50 hover:bg-indigo-100 text-indigo-700" title="Open Work">
                        <i class="fas fa-arrow-right"></i>
                    </a>
                    <button onclick="openStatusModal('${w.sys_id}')" class="action-btn bg-blue-50 hover:bg-blue-100 text-blue-700" title="Change Status">
                        <i class="fas fa-tag"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

// ── Render Cards ─────────────────────────────────────────
function renderCards() {
    const grid  = document.getElementById('worksCardGrid');
    const start = (currentPage - 1) * perPage;
    const page  = filteredWorks.slice(start, start + perPage);

    if (!page.length) {
        grid.innerHTML = `<div class="col-span-3 text-center py-12 text-gray-400"><i class="fas fa-inbox text-3xl mb-2 block"></i>No works found.</div>`;
        return;
    }

    grid.innerHTML = page.map(w => {
        const ci = safeParse(w.client_info) ?? {};
        const st = safeParse(w.service_type) ?? [];
        const services = Array.isArray(st) ? st : [st];
        const pillsHtml = services.map(s => {
            const info = SERVICE_LABELS[s] ?? { icon:'fa-circle', label:s, cls:'' };
            return `<span class="service-pill ${info.cls}"><i class="fas ${info.icon}"></i>${info.label}</span>`;
        }).join(' ');

        return `
        <div class="work-card bg-white rounded-xl p-5">
            <div class="flex items-start justify-between mb-3">
                <span class="font-mono text-xs text-indigo-600 font-semibold bg-indigo-50 px-2 py-1 rounded-md">${w.sys_id}</span>
                ${badgeHtml(w.work_status)}
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
            <div class="text-xs text-gray-400 mb-4"><i class="fas fa-calendar mr-1"></i>${formatDate(w.extracted_date)}</div>
            <div class="flex items-center gap-2 pt-3 border-t border-gray-100">
                <a href="show-works.php?id=${w.sys_id}" class="flex-1 text-center py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold transition">
                    <i class="fas fa-arrow-right mr-1"></i>Open Work
                </a>
                <button onclick="openStatusModal('${w.sys_id}')" class="action-btn bg-blue-50 hover:bg-blue-100 text-blue-700" title="Change Status">
                    <i class="fas fa-tag"></i>
                </button>
            </div>
        </div>`;
    }).join('');
}

// ── Pagination ────────────────────────────────────────────
function renderPagination() {
    const total = filteredWorks.length;
    const pages = Math.ceil(total / perPage);
    const start = total ? (currentPage - 1) * perPage + 1 : 0;
    const end   = Math.min(currentPage * perPage, total);
    document.getElementById('paginationInfo').textContent = total ? `Showing ${start}–${end} of ${total} works` : 'No results';

    const btns = document.getElementById('paginationBtns');
    btns.innerHTML = '';
    if (pages <= 1) return;

    const addBtn = (label, page, disabled, active) => {
        const btn = document.createElement('button');
        btn.innerHTML = label;
        btn.className = `px-3 py-1.5 rounded-lg text-sm border font-medium transition ${active ? 'bg-indigo-600 text-white border-indigo-600' : disabled ? 'bg-gray-50 text-gray-300 border-gray-200 cursor-not-allowed' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'}`;
        if (!disabled && !active) btn.onclick = () => { currentPage = page; render(); };
        btns.appendChild(btn);
    };

    addBtn('<i class="fas fa-chevron-left text-xs"></i>', currentPage - 1, currentPage === 1);
    for (let p = 1; p <= pages; p++) addBtn(p, p, false, p === currentPage);
    addBtn('<i class="fas fa-chevron-right text-xs"></i>', currentPage + 1, currentPage === pages);
}

// ── Status Modal ──────────────────────────────────────────
function openStatusModal(sysId) {
    document.getElementById('statusWorkId').value = sysId;
    document.getElementById('statusModal').classList.remove('hidden');
}

async function changeStatus(newStatus) {
    const sysId = document.getElementById('statusWorkId').value;
    closeModal('statusModal');
    try {
        const res  = await fetch(API.status, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({ sys_id:sysId, status:newStatus }) });
        const json = await res.json();
        if (json.status === 'success') { showToast('success', 'Status updated!'); loadWorks(); }
        else showToast('error', json.message || 'Failed.');
    } catch { showToast('error', 'Network error.'); }
}

// ── Helpers ───────────────────────────────────────────────
function closeModal(id) { document.getElementById(id).classList.add('hidden'); }
document.querySelectorAll('.modal-bg').forEach(m => m.addEventListener('click', e => { if (e.target === m) m.classList.add('hidden'); }));

function safeParse(v) { if (!v) return null; if (typeof v === 'object') return v; try { return JSON.parse(v); } catch { return null; } }
function formatDate(d) { if (!d) return '—'; return new Date(d).toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' }); }

function badgeHtml(status) {
    const map = {
        open:        ['badge-open',        '🟡 Open'],
        in_progress: ['badge-in_progress', '🔵 In Progress'],
        done:        ['badge-done',        '✅ Done'],
        cancelled:   ['badge-cancelled',   '❌ Cancelled'],
    };
    const [cls, label] = map[status] ?? ['', status];
    return `<span class="badge ${cls}">${label}</span>`;
}

function showToast(type, msg) {
    const toast = document.getElementById('toast');
    const inner = document.getElementById('toastInner');
    document.getElementById('toastMsg').textContent = msg;
    inner.className = `flex items-center gap-3 px-5 py-3 rounded-xl shadow-lg text-white text-sm font-medium ${type==='success' ? 'bg-green-600' : 'bg-red-500'}`;
    document.getElementById('toastIcon').className  = `fas ${type==='success' ? 'fa-check-circle' : 'fa-exclamation-circle'} text-lg`;
    toast.classList.remove('hidden');
    setTimeout(() => toast.classList.add('hidden'), 3500);
}

loadWorks();
</script>
</body>
</html>