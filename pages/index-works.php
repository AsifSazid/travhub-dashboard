<?php
// FILE PATH: /pages/index-works.php
include_once('./authenticate.php');
$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898";
}
$getAllWorksApi   = $ip_port . "api/running-works/all-works.php";
$deleteWorkApi   = $ip_port . "api/running-works/delete.php";
$updateStatusApi = $ip_port . "api/running-works/update-status.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Work List</title>
    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png" sizes="16x16">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .badge { display: inline-flex; align-items: center; padding: 2px 10px; border-radius: 999px; font-size: .72rem; font-weight: 600; }
        .badge-pending    { background: #fef9c3; color: #854d0e; }
        .badge-in_progress{ background: #dbeafe; color: #1e40af; }
        .badge-completed  { background: #dcfce7; color: #166534; }
        .badge-on_hold    { background: #f3e8ff; color: #6b21a8; }
        .badge-cancelled  { background: #fee2e2; color: #991b1b; }
        .action-btn { padding: 5px 9px; border-radius: 6px; font-size: .78rem; transition: all .15s; }
        tr:hover td { background: #f8f7ff; }
        .modal-bg { background: rgba(0,0,0,.45); backdrop-filter: blur(3px); }
        #advSearchPanel { max-height: 0; overflow: hidden; transition: max-height .3s ease; }
        #advSearchPanel.open { max-height: 300px; }
        .work-card { transition: all .2s ease; }
        .work-card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(99,102,241,.1); border-color: #6366f1; }
        .view-btn.active { background: #4f46e5; color: #fff; }
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
            <p class="text-sm text-gray-500 mt-0.5">Track and manage all active work entries</p>
        </div>
        <div class="flex items-center gap-2">
            <!-- View Toggle -->
            <div class="flex bg-gray-100 rounded-lg p-1">
                <button id="btnList" class="view-btn active px-3 py-1.5 rounded-md text-sm font-medium transition" onclick="setView('list')">
                    <i class="fas fa-list"></i>
                </button>
                <button id="btnCard" class="view-btn px-3 py-1.5 rounded-md text-sm font-medium text-gray-500 transition" onclick="setView('card')">
                    <i class="fas fa-th-large"></i>
                </button>
            </div>
            <a href="create-work.php" class="flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium text-sm transition shadow-sm">
                <i class="fas fa-plus"></i> New Work
            </a>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-5">
        <div class="bg-white rounded-xl p-4 shadow-sm border-l-4 border-gray-300">
            <div class="text-2xl font-bold text-gray-700" id="statAll">—</div>
            <div class="text-xs text-gray-500 mt-0.5">Total Works</div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border-l-4 border-yellow-400">
            <div class="text-2xl font-bold text-yellow-600" id="statPending">—</div>
            <div class="text-xs text-gray-500 mt-0.5">Pending</div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border-l-4 border-blue-400">
            <div class="text-2xl font-bold text-blue-600" id="statInProg">—</div>
            <div class="text-xs text-gray-500 mt-0.5">In Progress</div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border-l-4 border-green-400">
            <div class="text-2xl font-bold text-green-600" id="statCompleted">—</div>
            <div class="text-xs text-gray-500 mt-0.5">Completed</div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border-l-4 border-red-400">
            <div class="text-2xl font-bold text-red-500" id="statCancelled">—</div>
            <div class="text-xs text-gray-500 mt-0.5">Cancelled</div>
        </div>
    </div>

    <!-- Main Card -->
    <div class="bg-white rounded-xl shadow-sm p-5">

        <!-- Search & Filter Bar -->
        <div class="flex flex-wrap items-center gap-3 mb-4">
            <div class="relative flex-1 min-w-[200px]">
                <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-sm"></i>
                <input type="text" id="searchInput" placeholder="Search by client, title, work ID..."
                    class="pl-9 pr-4 py-2.5 border border-gray-300 rounded-lg text-sm w-full focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <select id="filterStatus" class="px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="in_progress">In Progress</option>
                <option value="completed">Completed</option>
                <option value="on_hold">On Hold</option>
                <option value="cancelled">Cancelled</option>
            </select>
            <button onclick="toggleAdvSearch()" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg text-sm flex items-center gap-2 transition">
                <i class="fas fa-sliders"></i> Advanced
            </button>
            <button onclick="resetFilters()" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg text-sm transition" title="Reset">
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
                    <label class="block text-xs font-medium text-gray-600 mb-1">Owned By</label>
                    <input type="text" id="advOwner" placeholder="Employee name..."
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
            </div>
            <div class="pb-3 flex justify-end">
                <button onclick="applyFilters()" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition">Apply</button>
            </div>
        </div>

        <!-- LIST VIEW -->
        <div id="listView">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase w-10">#</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Work ID</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Client</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Title</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Owned By</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Created</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="worksTableBody" class="divide-y divide-gray-50">
                        <tr><td colspan="8" class="text-center py-12 text-gray-400"><i class="fas fa-spinner fa-spin text-2xl"></i></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- CARD VIEW -->
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

<!-- ═══ STATUS MODAL ═══ -->
<div id="statusModal" class="fixed inset-0 z-50 hidden modal-bg flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm modal-slide-in">
        <div class="flex items-center justify-between p-5 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-800"><i class="fas fa-tag mr-2 text-indigo-500"></i>Change Status</h3>
            <button onclick="closeModal('statusModal')" class="text-gray-400 hover:text-gray-700 text-xl"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-5">
            <input type="hidden" id="statusWorkId">
            <div class="grid grid-cols-2 gap-3">
                <button onclick="changeStatus('pending')"     class="py-2 rounded-lg bg-yellow-50 text-yellow-700 border border-yellow-200 font-medium text-sm hover:bg-yellow-100 transition">⏳ Pending</button>
                <button onclick="changeStatus('in_progress')" class="py-2 rounded-lg bg-blue-50 text-blue-700 border border-blue-200 font-medium text-sm hover:bg-blue-100 transition">🔵 In Progress</button>
                <button onclick="changeStatus('completed')"   class="py-2 rounded-lg bg-green-50 text-green-700 border border-green-200 font-medium text-sm hover:bg-green-100 transition">✅ Completed</button>
                <button onclick="changeStatus('on_hold')"     class="py-2 rounded-lg bg-purple-50 text-purple-700 border border-purple-200 font-medium text-sm hover:bg-purple-100 transition">⏸ On Hold</button>
                <button onclick="changeStatus('cancelled')"   class="col-span-2 py-2 rounded-lg bg-red-50 text-red-700 border border-red-200 font-medium text-sm hover:bg-red-100 transition">❌ Cancelled</button>
            </div>
        </div>
    </div>
</div>

<!-- ═══ DELETE MODAL ═══ -->
<div id="deleteModal" class="fixed inset-0 z-50 hidden modal-bg flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm modal-slide-in">
        <div class="p-6 text-center">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-trash-alt text-red-500 text-2xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Delete Work?</h3>
            <p class="text-sm text-gray-500 mb-6">This will permanently delete the work entry. All tasks must be deleted first.</p>
            <input type="hidden" id="deleteWorkId">
            <div class="flex gap-3">
                <button onclick="closeModal('deleteModal')" class="flex-1 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium text-sm transition">Cancel</button>
                <button onclick="confirmDelete()" class="flex-1 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium text-sm transition">Yes, Delete</button>
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
    all:    "<?php echo $getAllWorksApi; ?>",
    delete: "<?php echo $deleteWorkApi; ?>",
    status: "<?php echo $updateStatusApi; ?>",
};

let allWorks      = [];
let filteredWorks = [];
let currentPage   = 1;
let currentView   = 'list';
const perPage     = 15;

async function loadWorks() {
    try {
        const res  = await fetch(API.all);
        const json = await res.json();
        allWorks = json.works ?? [];
        updateStats();
        applyFilters();
    } catch {
        document.getElementById('worksTableBody').innerHTML =
            `<tr><td colspan="8" class="text-center py-10 text-red-400"><i class="fas fa-exclamation-circle mr-2"></i>Failed to load works.</td></tr>`;
    }
}

function updateStats() {
    const c = { pending:0, in_progress:0, completed:0, on_hold:0, cancelled:0 };
    allWorks.forEach(w => { if (c[w.work_status ?? 'pending'] !== undefined) c[w.work_status ?? 'pending']++; });
    document.getElementById('statAll').textContent       = allWorks.length;
    document.getElementById('statPending').textContent   = c.pending;
    document.getElementById('statInProg').textContent    = c.in_progress;
    document.getElementById('statCompleted').textContent = c.completed;
    document.getElementById('statCancelled').textContent = c.cancelled;
}

function applyFilters() {
    const search  = document.getElementById('searchInput').value.toLowerCase().trim();
    const status  = document.getElementById('filterStatus').value;
    const dateFrom= document.getElementById('advDateFrom').value;
    const dateTo  = document.getElementById('advDateTo').value;
    const owner   = document.getElementById('advOwner').value.toLowerCase().trim();

    filteredWorks = allWorks.filter(w => {
        const matchSearch = !search ||
            (w.client_name ?? '').toLowerCase().includes(search) ||
            (w.title ?? '').toLowerCase().includes(search) ||
            (w.sys_id ?? '').toLowerCase().includes(search) ||
            (w.client_sys_id ?? '').toLowerCase().includes(search);

        const matchStatus = !status || (w.work_status ?? 'pending') === status;
        const matchOwner  = !owner  || (w.owned_by ?? '').toLowerCase().includes(owner);

        let matchDate = true;
        if (dateFrom || dateTo) {
            const d = w.created_at ? new Date(w.created_at) : null;
            if (d) {
                if (dateFrom && d < new Date(dateFrom)) matchDate = false;
                if (dateTo   && d > new Date(dateTo + 'T23:59:59')) matchDate = false;
            }
        }

        return matchSearch && matchStatus && matchOwner && matchDate;
    });

    currentPage = 1;
    render();
}

function resetFilters() {
    ['searchInput','filterStatus','advDateFrom','advDateTo','advOwner'].forEach(id => {
        document.getElementById(id).value = '';
    });
    applyFilters();
}

document.getElementById('searchInput').addEventListener('input', applyFilters);
document.getElementById('filterStatus').addEventListener('change', applyFilters);

function render() {
    currentView === 'list' ? renderList() : renderCards();
    renderPagination();
}

function renderList() {
    const tbody = document.getElementById('worksTableBody');
    const start = (currentPage - 1) * perPage;
    const page  = filteredWorks.slice(start, start + perPage);

    if (!page.length) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-12 text-gray-400"><i class="fas fa-inbox text-3xl mb-2 block"></i>No works found.</td></tr>`;
        return;
    }

    tbody.innerHTML = page.map((w, idx) => {
        const ownedBy = extractOwnerName(w.owned_by);
        return `
        <tr class="hover:bg-gray-50 transition-colors">
            <td class="px-4 py-3 text-gray-500 text-xs">${start+idx+1}</td>
            <td class="px-4 py-3 font-mono text-xs text-indigo-600 font-semibold">${w.sys_id}</td>
            <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center font-bold text-sm flex-shrink-0">
                        ${(w.client_name?.[0]??'?').toUpperCase()}
                    </div>
                    <div>
                        <div class="font-medium text-gray-800 text-sm">${w.client_name ?? '—'}</div>
                        <div class="text-xs text-gray-400">${w.client_sys_id ?? ''}</div>
                    </div>
                </div>
            </td>
            <td class="px-4 py-3 text-gray-800 font-medium text-sm max-w-[180px] truncate" title="${w.title??''}">${w.title ?? '—'}</td>
            <td class="px-4 py-3 text-sm text-gray-600">${ownedBy}</td>
            <td class="px-4 py-3">${badgeHtml(w.work_status ?? 'pending')}</td>
            <td class="px-4 py-3 text-xs text-gray-500">${formatDate(w.created_at)}</td>
            <td class="px-4 py-3">
                <div class="flex items-center gap-1 flex-wrap">
                    <a href="te-work-folder.php?work_id=${w.sys_id}" class="action-btn bg-indigo-50 hover:bg-indigo-100 text-indigo-700" title="Open Workboard">
                        <i class="fas fa-briefcase"></i>
                    </a>
                    <a href="edit-work.php?id=${w.sys_id}" class="action-btn bg-yellow-50 hover:bg-yellow-100 text-yellow-700" title="Edit">
                        <i class="fas fa-pencil"></i>
                    </a>
                    <button onclick="openStatusModal('${w.sys_id}')" class="action-btn bg-blue-50 hover:bg-blue-100 text-blue-700" title="Change Status">
                        <i class="fas fa-tag"></i>
                    </button>
                    <button onclick="openDeleteModal('${w.sys_id}')" class="action-btn bg-red-50 hover:bg-red-100 text-red-600" title="Delete">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

function renderCards() {
    const grid  = document.getElementById('worksCardGrid');
    const start = (currentPage - 1) * perPage;
    const page  = filteredWorks.slice(start, start + perPage);

    if (!page.length) {
        grid.innerHTML = `<div class="col-span-3 text-center py-12 text-gray-400"><i class="fas fa-inbox text-3xl mb-2 block"></i>No works found.</div>`;
        return;
    }

    grid.innerHTML = page.map(w => {
        const ownedBy = extractOwnerName(w.owned_by);
        return `
        <div class="work-card border-2 border-gray-100 rounded-xl p-5 bg-white cursor-pointer">
            <div class="flex items-start justify-between mb-3">
                <span class="font-mono text-xs text-indigo-600 font-semibold bg-indigo-50 px-2 py-1 rounded-md">${w.sys_id}</span>
                ${badgeHtml(w.work_status ?? 'pending')}
            </div>
            <h3 class="font-semibold text-gray-800 mb-1 text-sm leading-snug">${w.title ?? '—'}</h3>
            <div class="flex items-center gap-2 mb-3">
                <div class="w-6 h-6 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center font-bold text-xs">${(w.client_name?.[0]??'?').toUpperCase()}</div>
                <span class="text-xs text-gray-600">${w.client_name ?? '—'}</span>
            </div>
            <div class="text-xs text-gray-400 mb-4">
                <i class="fas fa-user mr-1"></i>${ownedBy} &nbsp;·&nbsp;
                <i class="fas fa-calendar mr-1"></i>${formatDate(w.created_at)}
            </div>
            <div class="flex items-center gap-2 pt-3 border-t border-gray-100">
                <a href="te-work-folder.php?work_id=${w.sys_id}" class="flex-1 text-center py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-medium transition">
                    <i class="fas fa-briefcase mr-1"></i>Open
                </a>
                <a href="edit-work.php?id=${w.sys_id}" class="px-3 py-1.5 bg-yellow-50 hover:bg-yellow-100 text-yellow-700 rounded-lg text-xs transition" title="Edit">
                    <i class="fas fa-pencil"></i>
                </a>
                <button onclick="openStatusModal('${w.sys_id}')" class="px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg text-xs transition" title="Status">
                    <i class="fas fa-tag"></i>
                </button>
                <button onclick="openDeleteModal('${w.sys_id}')" class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg text-xs transition" title="Delete">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
        </div>`;
    }).join('');
}

function setView(view) {
    currentView = view;
    if (view === 'list') {
        document.getElementById('listView').classList.remove('hidden');
        document.getElementById('cardView').classList.add('hidden');
        document.getElementById('btnList').classList.add('active');
        document.getElementById('btnCard').classList.remove('active');
        document.getElementById('btnCard').classList.add('text-gray-500');
    } else {
        document.getElementById('listView').classList.add('hidden');
        document.getElementById('cardView').classList.remove('hidden');
        document.getElementById('btnCard').classList.add('active');
        document.getElementById('btnList').classList.remove('active');
        document.getElementById('btnList').classList.add('text-gray-500');
    }
    render();
}

function renderPagination() {
    const total = filteredWorks.length;
    const pages = Math.ceil(total / perPage);
    const start = total ? (currentPage - 1) * perPage + 1 : 0;
    const end   = Math.min(currentPage * perPage, total);

    document.getElementById('paginationInfo').textContent = total
        ? `Showing ${start}–${end} of ${total} works` : 'No results';

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
        if (!disabled && !active) btn.onclick = () => { currentPage = page; render(); };
        btns.appendChild(btn);
    };

    addBtn('<i class="fas fa-chevron-left text-xs"></i>', currentPage-1, currentPage===1);
    for (let p = 1; p <= pages; p++) {
        if (pages > 7 && p > 2 && p < pages-1 && Math.abs(p-currentPage) > 1) {
            if (p===3||p===pages-2) { const el=document.createElement('span'); el.textContent='…'; el.className='px-2 text-gray-400'; btns.appendChild(el); }
            continue;
        }
        addBtn(p, p, false, p===currentPage);
    }
    addBtn('<i class="fas fa-chevron-right text-xs"></i>', currentPage+1, currentPage===pages);
}

// ─── Status Modal ─────────────────────────────────────
function openStatusModal(sysId) {
    document.getElementById('statusWorkId').value = sysId;
    document.getElementById('statusModal').classList.remove('hidden');
}

async function changeStatus(newStatus) {
    const sysId = document.getElementById('statusWorkId').value;
    closeModal('statusModal');
    try {
        const res  = await fetch(API.status, {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ sys_id: sysId, status: newStatus }),
        });
        const json = await res.json();
        if (json.success || json.status === 'success') {
            showToast('success', 'Status updated.');
            loadWorks();
        } else showToast('error', json.message || 'Failed.');
    } catch { showToast('error', 'Network error.'); }
}

// ─── Delete Modal ─────────────────────────────────────
function openDeleteModal(sysId) {
    document.getElementById('deleteWorkId').value = sysId;
    document.getElementById('deleteModal').classList.remove('hidden');
}

async function confirmDelete() {
    const sysId = document.getElementById('deleteWorkId').value;
    closeModal('deleteModal');
    try {
        const res  = await fetch(API.delete, {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ work_id: sysId }),
        });
        const json = await res.json();
        if (json.success) {
            showToast('success', 'Work deleted.');
            loadWorks();
        } else showToast('error', json.message || 'Failed to delete.');
    } catch { showToast('error', 'Network error.'); }
}

function toggleAdvSearch() { document.getElementById('advSearchPanel').classList.toggle('open'); }
function closeModal(id)    { document.getElementById(id).classList.add('hidden'); }

document.querySelectorAll('.modal-bg').forEach(m => {
    m.addEventListener('click', e => { if (e.target === m) m.classList.add('hidden'); });
});

// ─── Helpers ──────────────────────────────────────────
function extractOwnerName(raw) {
    if (!raw) return '—';
    // format: "SYS_ID|Name" or just name
    const parts = raw.split('|');
    return parts.length > 1 ? parts[1].trim() : raw.trim();
}

function formatDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' });
}

function badgeHtml(status) {
    const map = {
        pending:     ['badge-pending',    '⏳ Pending'],
        in_progress: ['badge-in_progress','🔵 In Progress'],
        completed:   ['badge-completed',  '✅ Completed'],
        on_hold:     ['badge-on_hold',    '⏸ On Hold'],
        cancelled:   ['badge-cancelled',  '❌ Cancelled'],
    };
    const [cls, label] = map[status] ?? ['', status];
    return `<span class="badge ${cls}">${label}</span>`;
}

function showToast(type, msg) {
    const toast = document.getElementById('toast');
    const inner = document.getElementById('toastInner');
    document.getElementById('toastMsg').textContent = msg;
    inner.className = `flex items-center gap-3 px-5 py-3 rounded-xl shadow-lg text-white text-sm font-medium ${type==='success'?'bg-green-600':'bg-red-500'}`;
    document.getElementById('toastIcon').className = `fas ${type==='success'?'fa-check-circle':'fa-exclamation-circle'} text-lg`;
    toast.classList.remove('hidden');
    setTimeout(() => toast.classList.add('hidden'), 3500);
}

loadWorks();
</script>
</body>
</html>