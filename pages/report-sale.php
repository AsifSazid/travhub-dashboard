<?php
include_once('./authenticate.php');
require_once '../server/db_connection.php';
require_once '../server/permissions.php';
requireFullAccountingAccess($pdo, false);
$ip_port = trim(@file_get_contents('../ippath.txt') ?: 'http://103.104.219.3:898', '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sale Report — TravHub</title>
    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
</head>
<body class="bg-gray-50">
<?php include '../elements/header.php'; ?>
<?php include '../elements/aside.php'; ?>
<?php include '../elements/preview-model.php'; ?>

<main id="mainContent" class="pt-16 pl-64 transition-all duration-300">
<div class="p-6">

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-cash-register text-indigo-600 mr-2"></i>Sale Report</h1>
            <p class="text-sm text-gray-500 mt-1">All client sales (net of refunds)</p>
        </div>
        <div class="relative" id="exportDropdownWrap">
            <button onclick="toggleExportMenu()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium flex items-center gap-2">
                <i class="fas fa-download"></i> Export <i class="fas fa-chevron-down ml-1 text-xs"></i>
            </button>
            <div id="exportMenu" class="hidden absolute right-0 mt-1 w-40 bg-white border border-gray-200 rounded-xl shadow-lg z-50">
                <button onclick="exportData('csv')"  class="w-full text-left px-4 py-2.5 text-sm hover:bg-gray-50 flex items-center gap-2"><i class="fas fa-file-csv text-green-600"></i> CSV</button>
                <button onclick="exportData('excel')" class="w-full text-left px-4 py-2.5 text-sm hover:bg-gray-50 flex items-center gap-2"><i class="fas fa-file-excel text-green-700"></i> Excel</button>
                <button onclick="exportData('pdf')"  class="w-full text-left px-4 py-2.5 text-sm hover:bg-gray-50 flex items-center gap-2"><i class="fas fa-file-pdf text-red-600"></i> PDF</button>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
            <div><label class="block text-xs font-medium text-gray-500 mb-1">Date From</label>
                <input type="date" id="f-date-from" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-400"></div>
            <div><label class="block text-xs font-medium text-gray-500 mb-1">Date To</label>
                <input type="date" id="f-date-to" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-400"></div>
            <div><label class="block text-xs font-medium text-gray-500 mb-1">Client</label>
                <select id="f-client" multiple size="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-400"><option value="">All Clients</option></select></div>
            <div><label class="block text-xs font-medium text-gray-500 mb-1">Work</label>
                <select id="f-work" multiple size="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-400"><option value="">All Works</option></select></div>
            <div><label class="block text-xs font-medium text-gray-500 mb-1">Amount Min</label>
                <input type="number" step="0.01" id="f-amount-min" placeholder="0.00" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-400"></div>
            <div><label class="block text-xs font-medium text-gray-500 mb-1">Amount Max</label>
                <input type="number" step="0.01" id="f-amount-max" placeholder="0.00" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-400"></div>
            <div><label class="block text-xs font-medium text-gray-500 mb-1">Paid Status</label>
                <select id="f-is-paid" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-400">
                    <option value="">All</option><option value="1">Paid</option><option value="0">Unpaid</option>
                </select></div>
            <div class="col-span-2"><label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
                <input type="text" id="f-search" placeholder="Purpose / Client / Work..." class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-400"></div>
        </div>
        <div class="flex gap-2 mt-3">
            <button onclick="applyFilters()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium"><i class="fas fa-search mr-1"></i> Apply</button>
            <button onclick="clearFilters()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg text-sm font-medium"><i class="fas fa-redo mr-1"></i> Clear</button>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500">Total Entries</p>
            <p class="text-2xl font-bold text-gray-800 mt-1" id="sum-count">—</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500">Total Sale Amount (net of refunds)</p>
            <p class="text-2xl font-bold text-indigo-600 mt-1" id="sum-amount">—</p>
        </div>
    </div>

    <div id="loadingBar" class="hidden text-center py-8"><i class="fas fa-spinner fa-spin text-2xl text-indigo-500"></i></div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Date</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Client</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Purpose</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Work</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Status</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase text-xs">Amount</th>
                    </tr>
                </thead>
                <tbody id="list-tbody"></tbody>
            </table>
        </div>
        <div id="list-pagination" class="flex items-center justify-between px-4 py-3 border-t border-gray-100 text-xs text-gray-500"></div>
    </div>

</div>
</main>

<script>
const API = "<?php echo $ip_port; ?>/api/reports/sale_v2/endpoints.php";
let currentPage = 1;

function fmt(n) { return '৳' + (parseFloat(n)||0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}); }

function buildParams() {
    const p = new URLSearchParams();
    const df = document.getElementById('f-date-from').value;
    const dt = document.getElementById('f-date-to').value;
    const amin = document.getElementById('f-amount-min').value;
    const amax = document.getElementById('f-amount-max').value;
    const isPaid = document.getElementById('f-is-paid').value;
    const search = document.getElementById('f-search').value;
    if (df) p.set('date_from', df);
    if (dt) p.set('date_to', dt);
    if (amin) p.set('amount_min', amin);
    if (amax) p.set('amount_max', amax);
    if (isPaid !== '') p.set('is_paid', isPaid);
    if (search) p.set('search', search);
    [...document.getElementById('f-client').selectedOptions].forEach(o => { if (o.value) p.append('client[]', o.value); });
    [...document.getElementById('f-work').selectedOptions].forEach(o => { if (o.value) p.append('work[]', o.value); });
    return p.toString();
}

function applyFilters() { currentPage = 1; loadList(); }
function clearFilters() {
    ['f-date-from','f-date-to','f-amount-min','f-amount-max','f-search'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('f-is-paid').value = '';
    document.getElementById('f-client').selectedIndex = -1;
    document.getElementById('f-work').selectedIndex = -1;
    applyFilters();
}
function setLoading(b) { document.getElementById('loadingBar').classList.toggle('hidden', !b); }

async function loadFilterOptions() {
    try {
        const r = await fetch(`${API}?action=filters`);
        const d = await r.json();
        if (!d.success) return;
        const cSel = document.getElementById('f-client');
        (d.clients||[]).forEach(v => cSel.innerHTML += `<option value="${c.user_sys_id}">${c.user_name||v.user_sys_id}</option>`);
        const wSel = document.getElementById('f-work');
        (d.works||[]).forEach(w => wSel.innerHTML += `<option value="${w.work_sys_id}">${w.work_title||w.work_sys_id}</option>`);
    } catch(e) {}
}

async function loadList() {
    setLoading(true);
    try {
        const r = await fetch(`${API}?action=list&page=${currentPage}&${buildParams()}`);
        const d = await r.json();
        if (!d.success) return;

        document.getElementById('sum-count').textContent = d.summary.total_count;
        document.getElementById('sum-amount').textContent = fmt(d.summary.total_amount);

        const tb = document.getElementById('list-tbody');
        tb.innerHTML = (d.rows||[]).map(row => `
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">${(row.date||'').substring(0,10)}</td>
                <td class="px-4 py-3 font-medium text-gray-800">${row.user_name||row.user_sys_id}</td>
                <td class="px-4 py-3 text-gray-700 max-w-xs truncate">${row.purpose||'—'}</td>
                <td class="px-4 py-3 text-gray-500">${row.work_title||row.work_sys_id||'—'}</td>
                <td class="px-4 py-3">${row.is_paid==1 ? '<span class="text-xs px-2 py-0.5 bg-green-100 text-green-700 rounded-full">Paid</span>' : '<span class="text-xs px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full">Unpaid</span>'}</td>
                <td class="px-4 py-3 text-right font-medium ${row.type==='debit'?'text-rose-500':'text-indigo-700'}">${row.type==='debit'?'-':''}${fmt(row.amount)}</td>
            </tr>`).join('') || '<tr><td colspan="6" class="px-4 py-6 text-center text-gray-400">No sales found</td></tr>';

        const pag = document.getElementById('list-pagination');
        pag.innerHTML = `<span>Page ${d.page} of ${d.pages} (${d.total} entries)</span>
            <div class="flex gap-2">
                ${d.page > 1 ? `<button onclick="goPage(${d.page-1})" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">Prev</button>` : ''}
                ${d.page < d.pages ? `<button onclick="goPage(${d.page+1})" class="px-3 py-1 bg-indigo-600 text-white rounded hover:bg-indigo-700">Next</button>` : ''}
            </div>`;
    } finally { setLoading(false); }
}
function goPage(p) { currentPage = p; loadList(); }

function toggleExportMenu() { document.getElementById('exportMenu').classList.toggle('hidden'); }
document.addEventListener('click', e => {
    if (!document.getElementById('exportDropdownWrap')?.contains(e.target)) document.getElementById('exportMenu')?.classList.add('hidden');
});

async function exportData(type = 'csv') {
    document.getElementById('exportMenu')?.classList.add('hidden');
    setLoading(true);
    try {
        const r = await fetch(`${API}?action=export&${buildParams()}`);
        const d = await r.json();
        if (!d.success) { alert('Export failed'); return; }
        const dateSuffix = new Date().toISOString().split('T')[0];
        const filename = `sale-report-${dateSuffix}`;
        const rows = [
            ['Sale Report', '', '', '', '', ''],
            ['Total Entries', d.summary.total_count, '', '', '', ''],
            ['Total Amount', d.summary.total_amount, '', '', '', ''],
            ['', '', '', '', '', ''],
            ['Date', 'Client', 'Purpose', 'Work', 'Status', 'Amount'],
            ...(d.rows||[]).map(row => [(row.date||'').substring(0,10), row.user_name||row.user_sys_id, row.purpose||'', row.work_title||row.work_sys_id||'', row.is_paid==1?'Paid':'Unpaid', parseFloat(row.amount)]),
        ];
        const colWidths = [{wch:12},{wch:22},{wch:30},{wch:20},{wch:10},{wch:14}];
        const boldCells = ['A1','A5'];

        if (type === 'csv') {
            const csv = rows.map(r => r.map(c => `"${c}"`).join(',')).join('\n');
            dlFile('data:text/csv;charset=utf-8,' + encodeURIComponent(csv), filename + '.csv');
        } else if (type === 'excel') {
            const ws = XLSX.utils.aoa_to_sheet(rows);
            ws['!cols'] = colWidths;
            boldCells.forEach(cell => { if (ws[cell]) ws[cell].s = { font: { bold: true } }; });
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Sale');
            XLSX.writeFile(wb, filename + '.xlsx');
        } else if (type === 'pdf') {
            exportPDF('Sale Report', rows, filename);
        }
    } finally { setLoading(false); }
}

function dlFile(href, filename) { const a = document.createElement('a'); a.href = href; a.download = filename; a.click(); }
function exportPDF(title, rows, filename) {
    const win = window.open('', '_blank');
    const html = `<html><head><title>${title}</title><style>
        body{font-family:Arial,sans-serif;padding:20px;} h1{font-size:18px;}
        table{width:100%;border-collapse:collapse;margin-top:10px;} td,th{border:1px solid #ddd;padding:6px 8px;font-size:12px;text-align:left;}
        tr:first-child td{font-weight:bold;background:#f3f4f6;}
    </style></head><body>
    <h1>${title}</h1>
    <table>${rows.map(r => `<tr>${r.map(c => `<td>${c}</td>`).join('')}</tr>`).join('')}</table>
    <script>window.onload=()=>window.print()<\/script>
    </body></html>`;
    win.document.write(html);
    win.document.close();
}

document.addEventListener('DOMContentLoaded', () => { loadFilterOptions(); loadList(); });
</script>
<script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>
</body>
</html>