<?php
include_once('./authenticate.php');
$ip_port = trim(@file_get_contents('../ippath.txt') ?: 'http://103.104.219.3:898', '/');
$base_url = $ip_port;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Work Coverage — TravHub</title>
    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
</head>
<body class="bg-gray-50">
<?php include '../elements/header.php'; ?>
<?php include '../elements/aside.php'; ?>
<?php include '../elements/preview-model.php'; ?>

<main id="mainContent" class="pt-16 pl-64 transition-all duration-300">
<div class="p-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-project-diagram text-blue-600 mr-2"></i>Work Coverage
            </h1>
            <p class="text-sm text-gray-500 mt-1">কোন Work এ কতটা Task আছে বা নেই</p>
        </div>
        <div class="relative" id="exportDropdownWrap">
            <button onclick="toggleExportMenu()"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium flex items-center gap-2">
                <i class="fas fa-download"></i> Export <i class="fas fa-chevron-down ml-1 text-xs"></i>
            </button>
            <div id="exportMenu" class="hidden absolute right-0 mt-1 w-44 bg-white border border-gray-200 rounded-xl shadow-lg z-50">
                <button onclick="runExport('csv')"   class="w-full text-left px-4 py-2.5 text-sm hover:bg-gray-50 flex items-center gap-2">
                    <i class="fas fa-file-csv text-green-600"></i> CSV
                </button>
                <button onclick="runExport('excel')" class="w-full text-left px-4 py-2.5 text-sm hover:bg-gray-50 flex items-center gap-2">
                    <i class="fas fa-file-excel text-green-700"></i> Excel (.xlsx)
                </button>
                <button onclick="runExport('pdf')"   class="w-full text-left px-4 py-2.5 text-sm hover:bg-gray-50 flex items-center gap-2">
                    <i class="fas fa-file-pdf text-red-600"></i> PDF
                </button>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Date From</label>
                <input type="date" id="f-date-from" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Date To</label>
                <input type="date" id="f-date-to" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Quick Month</label>
                <input type="month" id="f-month" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Client</label>
                <select id="f-client" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">All Clients</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Work Status</label>
                <select id="f-work-status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">All Status</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Owned By</label>
                <select id="f-owned-by" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">All Employees</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Coverage</label>
                <select id="f-coverage" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">All</option>
                    <option value="covered">Has Tasks ✓</option>
                    <option value="not_covered">No Tasks ✗</option>
                </select>
            </div>
        </div>
        <div class="flex gap-2 mt-3 flex-wrap">
            <input type="text" id="f-search" placeholder="Work title / Client / Sys ID..."
                class="px-3 py-2 border border-gray-300 rounded-lg text-sm w-64">
            <button onclick="applyFilters()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium">
                <i class="fas fa-search mr-1"></i> Apply
            </button>
            <button onclick="clearFilters()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg text-sm font-medium">
                <i class="fas fa-redo mr-1"></i> Clear
            </button>
        </div>
        <!-- Active filter bar -->
        <div id="activeFilterBar" class="hidden mt-3 pt-3 border-t flex flex-wrap gap-2 items-center text-xs">
            <span class="text-gray-400">Active filters:</span>
            <div id="filterTags" class="flex flex-wrap gap-1.5"></div>
            <button onclick="clearFilters()" class="text-red-400 hover:text-red-600 ml-1">
                <i class="fas fa-times-circle mr-0.5"></i>Clear all
            </button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border-l-4 border-blue-500 p-4 shadow-sm">
            <p class="text-xs text-gray-500">Total Works</p>
            <p id="s-total" class="text-2xl font-bold text-blue-700 mt-1">—</p>
        </div>
        <div class="bg-white rounded-xl border-l-4 border-green-500 p-4 shadow-sm cursor-pointer hover:shadow-md transition-shadow"
             onclick="setFilter('f-coverage','covered')">
            <p class="text-xs text-gray-500">Has Tasks <span class="text-gray-300">(click to filter)</span></p>
            <p id="s-covered" class="text-2xl font-bold text-green-700 mt-1">—</p>
        </div>
        <div class="bg-white rounded-xl border-l-4 border-red-500 p-4 shadow-sm cursor-pointer hover:shadow-md transition-shadow"
             onclick="setFilter('f-coverage','not_covered')">
            <p class="text-xs text-gray-500">No Tasks <span class="text-gray-300">(click to filter)</span></p>
            <p id="s-not-covered" class="text-2xl font-bold text-red-700 mt-1">—</p>
        </div>
        <div class="bg-white rounded-xl border-l-4 border-purple-500 p-4 shadow-sm">
            <p class="text-xs text-gray-500">Total Tasks</p>
            <p id="s-tasks" class="text-2xl font-bold text-purple-700 mt-1">—</p>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="p-4 border-b flex justify-between items-center">
            <h3 class="font-semibold text-gray-800">
                <i class="fas fa-list text-blue-500 mr-2"></i>Works
            </h3>
            <span id="pageInfo" class="text-xs text-gray-400"></span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">Work</th>
                        <th class="px-4 py-3 text-left">Client</th>
                        <th class="px-4 py-3 text-left">Owned By</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Created</th>
                        <th class="px-4 py-3 text-center">Coverage</th>
                        <th class="px-4 py-3 text-center">Tasks</th>
                        <th class="px-4 py-3 text-center">Done</th>
                        <th class="px-4 py-3 text-center">In Progress</th>
                        <th class="px-4 py-3 text-center">Pending</th>
                        <th class="px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="main-tbody" class="divide-y divide-gray-100">
                    <tr><td colspan="11" class="px-4 py-8 text-center text-gray-400">Loading...</td></tr>
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <div class="p-4 border-t flex justify-between items-center">
            <span id="paginationInfo" class="text-xs text-gray-400"></span>
            <div class="flex gap-2">
                <button id="prevPage" onclick="changePage(-1)"
                    class="px-3 py-1.5 text-sm border border-gray-200 rounded-lg hover:bg-gray-50 disabled:opacity-40">
                    <i class="fas fa-chevron-left"></i> Prev
                </button>
                <button id="nextPage" onclick="changePage(1)"
                    class="px-3 py-1.5 text-sm border border-gray-200 rounded-lg hover:bg-gray-50 disabled:opacity-40">
                    Next <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Loading -->
    <div id="loading" class="hidden fixed inset-0 bg-white/70 flex items-center justify-center z-50">
        <div class="text-center">
            <i class="fas fa-spinner fa-spin text-3xl text-blue-600"></i>
            <p class="mt-2 text-gray-600 text-sm">Loading...</p>
        </div>
    </div>

</div>
</main>

<script>
const IP       = '<?php echo $ip_port; ?>';
const BASE_URL = '<?php echo $base_url; ?>';
const API      = `${IP}/api/reports/work-coverage/endpoints.php`;
// Work/Task page URLs
const WORK_URL = `${BASE_URL}/pages/task-entry.php`;
const TASK_URL = `${BASE_URL}/pages/cwe_tm-financial-trxn.php`;

let state = { page: 1, per_page: 25, pages: 1 };

const statusBadge = (s) => {
    if (!s || s === 'null') return '<span class="px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-400">—</span>';
    const map = {
        completed:   'bg-green-100 text-green-700',
        in_progress: 'bg-blue-100 text-blue-700',
        inprogress:  'bg-blue-100 text-blue-700',
        pending:     'bg-orange-100 text-orange-700',
    };
    const cls = map[s.toLowerCase()] || 'bg-gray-100 text-gray-600';
    return `<span class="px-2 py-0.5 rounded text-xs font-medium ${cls}">${s}</span>`;
};

document.addEventListener('DOMContentLoaded', () => {
    loadFilters();
    loadData();

    document.getElementById('f-month').addEventListener('change', function() {
        if (!this.value) return;
        const [y, m] = this.value.split('-');
        const last   = new Date(y, m, 0).getDate();
        document.getElementById('f-date-from').value = `${y}-${m}-01`;
        document.getElementById('f-date-to').value   = `${y}-${m}-${String(last).padStart(2,'0')}`;
    });

    document.getElementById('f-search').addEventListener('keydown', e => {
        if (e.key === 'Enter') applyFilters();
    });

    document.addEventListener('click', e => {
        if (!document.getElementById('exportDropdownWrap')?.contains(e.target))
            document.getElementById('exportMenu')?.classList.add('hidden');
    });
});

async function loadFilters() {
    const r = await fetch(`${API}?action=filters`);
    const d = await r.json();
    if (!d.success) return;

    const clientSel = document.getElementById('f-client');
    (d.clients||[]).forEach(c => {
        const o = document.createElement('option');
        o.value = c.id; o.textContent = c.name; clientSel.appendChild(o);
    });

    const statusSel = document.getElementById('f-work-status');
    (d.statuses||[]).forEach(s => {
        const o = document.createElement('option');
        o.value = s; o.textContent = s; statusSel.appendChild(o);
    });

    const empSel = document.getElementById('f-owned-by');
    (d.employees||[]).forEach(e => {
        const o = document.createElement('option');
        o.value = e.id; o.textContent = `${e.name} (${e.id})`; empSel.appendChild(o);
    });
}

function buildParams(extra = {}) {
    const p = new URLSearchParams();
    const from     = document.getElementById('f-date-from').value;
    const to       = document.getElementById('f-date-to').value;
    const client   = document.getElementById('f-client').value;
    const wstatus  = document.getElementById('f-work-status').value;
    const ownedBy  = document.getElementById('f-owned-by').value;
    const coverage = document.getElementById('f-coverage').value;
    const search   = document.getElementById('f-search').value;
    if (from)     p.set('date_from', from);
    if (to)       p.set('date_to', to);
    if (client)   p.set('client_id', client);
    if (wstatus)  p.set('work_status', wstatus);
    if (ownedBy)  p.set('owned_by', ownedBy);
    if (coverage) p.set('coverage', coverage);
    if (search)   p.set('search', search);
    Object.entries(extra).forEach(([k,v]) => p.set(k,v));
    return p.toString();
}

function setLoading(v) { document.getElementById('loading').classList.toggle('hidden', !v); }

async function loadData() {
    setLoading(true);
    try {
        const r = await fetch(`${API}?action=list&page=${state.page}&per_page=${state.per_page}&${buildParams()}`);
        const d = await r.json();
        if (!d.success) return;
        renderSummary(d.summary);
        renderTable(d.rows);
        state.pages = d.pages || 1;
        document.getElementById('paginationInfo').textContent =
            `Page ${d.page} of ${d.pages} — ${d.total} works`;
        document.getElementById('prevPage').disabled = state.page <= 1;
        document.getElementById('nextPage').disabled = state.page >= state.pages;
        updateFilterBar();
    } finally {
        setLoading(false);
    }
}

function renderSummary(s) {
    if (!s) return;
    document.getElementById('s-total').textContent       = s.total_works || 0;
    document.getElementById('s-covered').textContent     = s.covered || 0;
    document.getElementById('s-not-covered').textContent = s.not_covered || 0;
    document.getElementById('s-tasks').textContent       = s.total_tasks || 0;
}

function renderTable(rows) {
    const tb = document.getElementById('main-tbody');
    if (!rows.length) {
        tb.innerHTML = '<tr><td colspan="11" class="px-4 py-8 text-center text-gray-400">No works found</td></tr>';
        return;
    }

    tb.innerHTML = rows.map(w => {
        const covBadge = w.is_covered
            ? '<span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700"><i class="fas fa-check mr-0.5"></i>Covered</span>'
            : '<span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700"><i class="fas fa-times mr-0.5"></i>No Tasks</span>';

        // Task badges — clickable
        const taskBadges = w.tasks.map(t => `
            <a href="${TASK_URL}?work_id=${w.work_sys_id}&task_id=${t.sys_id}" target="_blank"
               title="${t.title||t.sys_id} · ${t.status||'—'} · ${t.performed_by||'—'}"
               class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs border border-gray-200 hover:border-blue-400 hover:bg-blue-50 transition-colors cursor-pointer">
                <i class="fas fa-tasks text-gray-400 text-xs"></i>
                ${t.title ? t.title.substring(0,20)+(t.title.length>20?'…':'') : t.sys_id}
                ${statusBadge(t.status)}
            </a>`).join(' ');

        const ownedParts = (w.owned_by||'').split('|');
        const ownedName  = ownedParts.length > 1 ? ownedParts[1].trim() : w.owned_by||'—';

        return `<tr class="hover:bg-gray-50 transition-colors">
            <td class="px-4 py-3">
                <a href="${WORK_URL}?work_id=${w.work_sys_id}" target="_blank"
                   class="font-medium text-blue-700 hover:text-blue-900 hover:underline flex items-center gap-1">
                    <i class="fas fa-external-link-alt text-xs text-gray-400"></i>
                    ${w.work_title||w.work_sys_id}
                </a>
                <div class="text-xs text-gray-400 font-mono mt-0.5">${w.work_sys_id}</div>
            </td>
            <td class="px-4 py-3 text-gray-700">${w.client_name||'—'}</td>
            <td class="px-4 py-3 text-xs text-gray-600">${ownedName}</td>
            <td class="px-4 py-3">${statusBadge(w.work_status)}</td>
            <td class="px-4 py-3 text-xs text-gray-400">${(w.created_date||'—').substring(0,10)}</td>
            <td class="px-4 py-3 text-center">${covBadge}</td>
            <td class="px-4 py-3 text-center">
                ${w.task_count > 0
                    ? `<span class="font-bold text-blue-700">${w.task_count}</span>`
                    : '<span class="text-gray-300">0</span>'}
            </td>
            <td class="px-4 py-3 text-center text-green-700 font-medium">${w.tasks_done||0}</td>
            <td class="px-4 py-3 text-center text-blue-600">${w.tasks_inprogress||0}</td>
            <td class="px-4 py-3 text-center text-orange-500">${w.tasks_pending||0}</td>
            <td class="px-4 py-3">
                <div class="flex flex-wrap gap-1 max-w-xs">
                    ${taskBadges || '<span class="text-xs text-gray-300">No tasks</span>'}
                </div>
            </td>
        </tr>`;
    }).join('');
}

function changePage(dir) {
    state.page = Math.max(1, Math.min(state.pages, state.page + dir));
    loadData();
}

function setFilter(id, value) {
    document.getElementById(id).value = value;
    state.page = 1;
    loadData();
}

function applyFilters() { state.page = 1; loadData(); }

function clearFilters() {
    ['f-date-from','f-date-to','f-month','f-search'].forEach(id => document.getElementById(id).value = '');
    ['f-client','f-work-status','f-owned-by','f-coverage'].forEach(id => document.getElementById(id).value = '');
    state.page = 1;
    loadData();
}

function getActiveFilters() {
    const filters = [];
    const from     = document.getElementById('f-date-from')?.value;
    const to       = document.getElementById('f-date-to')?.value;
    const month    = document.getElementById('f-month')?.value;
    const search   = document.getElementById('f-search')?.value;
    const client   = document.getElementById('f-client');
    const wstatus  = document.getElementById('f-work-status');
    const ownedBy  = document.getElementById('f-owned-by');
    const coverage = document.getElementById('f-coverage');
    if (from)            filters.push({ label: 'From', value: from });
    if (to)              filters.push({ label: 'To', value: to });
    if (month)           filters.push({ label: 'Month', value: month });
    if (search)          filters.push({ label: 'Search', value: search });
    if (client?.value)   filters.push({ label: 'Client', value: client.options[client.selectedIndex]?.text });
    if (wstatus?.value)  filters.push({ label: 'Status', value: wstatus.value });
    if (ownedBy?.value)  filters.push({ label: 'Owned By', value: ownedBy.options[ownedBy.selectedIndex]?.text });
    if (coverage?.value) filters.push({ label: 'Coverage', value: coverage.options[coverage.selectedIndex]?.text });
    return filters;
}

function updateFilterBar() {
    const filters = getActiveFilters();
    const bar  = document.getElementById('activeFilterBar');
    const tags = document.getElementById('filterTags');
    if (!filters.length) { bar.classList.add('hidden'); tags.innerHTML = ''; return; }
    bar.classList.remove('hidden');
    tags.innerHTML = filters.map(f =>
        `<span class="px-2 py-0.5 bg-blue-50 text-blue-700 border border-blue-200 rounded-full">
            <span class="font-medium">${f.label}:</span> ${f.value}
        </span>`).join('');
}

function getFilterSummaryText() {
    const f = getActiveFilters();
    return f.length ? 'Filters: ' + f.map(x => `${x.label}: ${x.value}`).join(' | ') : 'No filters applied';
}

function toggleExportMenu() {
    document.getElementById('exportMenu').classList.toggle('hidden');
}

async function runExport(type) {
    document.getElementById('exportMenu').classList.add('hidden');
    setLoading(true);
    try {
        const r = await fetch(`${API}?action=export&${buildParams()}`);
        const d = await r.json();
        if (!d.success) { alert('Export failed'); return; }

        const filterText = getFilterSummaryText();
        const filename   = `work-coverage-${new Date().toISOString().split('T')[0]}`;
        const headers    = ['Work Sys ID', 'Work Title', 'Client', 'Owned By', 'Status', 'Created',
                            'Coverage', 'Total Tasks', 'Done', 'In Progress', 'Pending', 'Task List'];
        const rows = d.rows.map(w => [
            w.work_sys_id,
            w.work_title||'',
            w.client_name||'',
            w.owned_by||'',
            w.work_status||'',
            (w.created_date||'').substring(0,10),
            w.is_covered ? 'Covered' : 'No Tasks',
            w.task_count,
            w.tasks_done,
            w.tasks_inprogress,
            w.tasks_pending,
            w.tasks.map(t => `${t.title||t.sys_id} (${t.status||'—'})`).join(', '),
        ]);

        if (type === 'csv') {
            const csv = [filterText, '', headers.join(','),
                ...rows.map(r => r.map(c => `"${String(c).replace(/"/g,'""')}"`).join(','))
            ].join('\n');
            dlFile('data:text/csv;charset=utf-8,' + encodeURIComponent(csv), filename + '.csv');

        } else if (type === 'excel') {
            const ws = XLSX.utils.aoa_to_sheet([[filterText], [''], headers, ...rows]);
            ws['!cols'] = [{wch:18},{wch:30},{wch:20},{wch:20},{wch:12},{wch:12},
                           {wch:12},{wch:12},{wch:8},{wch:12},{wch:10},{wch:50}];
            if (ws['A1']) ws['A1'].s = { font: { italic: true, color: { rgb: '6B7280' } } };
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Work Coverage');
            XLSX.writeFile(wb, filename + '.xlsx');

        } else if (type === 'pdf') {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF({ orientation: 'landscape' });
            doc.setFontSize(14); doc.text('Work Coverage Report', 14, 15);
            doc.setFontSize(8); doc.setTextColor(107,114,128);
            doc.text(filterText, 14, 21);
            doc.setTextColor(0,0,0);
            doc.autoTable({
                startY: 26,
                head: [['Work', 'Client', 'Owned By', 'Status', 'Coverage', 'Tasks', 'Done', 'In Progress', 'Pending']],
                body: rows.map(r => [r[1],r[2],r[3],r[4],r[6],r[7],r[8],r[9],r[10]]),
                styles: { fontSize: 7 },
                headStyles: { fillColor: [37,99,235] },
            });
            doc.save(filename + '.pdf');
        }
    } finally {
        setLoading(false);
    }
}

function dlFile(href, filename) {
    const a = document.createElement('a');
    a.href = href; a.download = filename; a.click();
}
</script>
</body>
</html>