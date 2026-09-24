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
    <title>Receive Report — TravHub</title>
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
            <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-hand-holding-usd text-green-600 mr-2"></i>Receive Report</h1>
            <p class="text-sm text-gray-500 mt-1">All cash received into your accounts</p>
        </div>
        <div class="relative" id="exportDropdownWrap">
            <button onclick="toggleExportMenu()" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium flex items-center gap-2">
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
                <input type="date" id="f-date-from" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-400"></div>
            <div><label class="block text-xs font-medium text-gray-500 mb-1">Date To</label>
                <input type="date" id="f-date-to" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-400"></div>
            <div><label class="block text-xs font-medium text-gray-500 mb-1">Quick Month</label>
                <input type="month" id="f-month" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-400"></div>
            <div><label class="block text-xs font-medium text-gray-500 mb-1">Account</label>
                <select id="f-account" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-400"><option value="">All Accounts</option></select></div>
            <div><label class="block text-xs font-medium text-gray-500 mb-1">Account Head</label>
                <select id="f-account-head" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-400">
                    <option value="">All Types</option>
                    <option value="accounts_receivable">Client Receive</option>
                    <option value="client_refund_payable">Client Refund</option>
                    <option value="bank_account">Transfer</option>
                </select></div>
            <div><label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
                <input type="text" id="f-search" placeholder="Account / Description..." class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-400"></div>
        </div>
        <div class="flex gap-2 mt-3">
            <button onclick="applyFilters()" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium"><i class="fas fa-search mr-1"></i> Apply</button>
            <button onclick="clearFilters()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg text-sm font-medium"><i class="fas fa-redo mr-1"></i> Clear</button>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500">Total Receives</p>
            <p class="text-2xl font-bold text-gray-800 mt-1" id="sum-count">—</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500">Total Received</p>
            <p class="text-2xl font-bold text-green-600 mt-1" id="sum-amount">—</p>
        </div>
    </div>

    <div id="loadingBar" class="hidden text-center py-8"><i class="fas fa-spinner fa-spin text-2xl text-green-500"></i></div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Date</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Account</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Received From</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Type</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Particular</th>
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
const API = "<?php echo $ip_port; ?>/api/reports/cashflow_v2/endpoints.php";
let currentPage = 1;

function fmt(n) { return '৳' + (parseFloat(n)||0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}); }

function accountHeadBadge(head) {
    const map = {
        sales: ['Client Sale', 'bg-indigo-100 text-indigo-700'],
        accounts_receivable: ['Client Receive', 'bg-sky-100 text-sky-700'],
        vendor_refund_receivable: ['Vendor Refund', 'bg-teal-100 text-teal-700'],
        bank_account: ['Transfer', 'bg-gray-100 text-gray-600'],
    };
    const [label, cls] = map[head] || ['Other', 'bg-gray-100 text-gray-600'];
    return `<span class="text-xs px-2 py-0.5 rounded-full ${cls}">${label}</span>`;
}

function buildParams() {
    const p = new URLSearchParams();
    const df = document.getElementById('f-date-from').value;
    const dt = document.getElementById('f-date-to').value;
    const month = document.getElementById('f-month').value;
    const account = document.getElementById('f-account').value;
    const head = document.getElementById('f-account-head').value;
    const search = document.getElementById('f-search').value;
    if (month) {
        const [y,m] = month.split('-');
        const last = new Date(y, m, 0).getDate();
        p.set('date_from', `${y}-${m}-01`);
        p.set('date_to', `${y}-${m}-${last}`);
    } else {
        if (df) p.set('date_from', df);
        if (dt) p.set('date_to', dt);
    }
    if (account) p.set('account_id', account);
    if (head) p.set('account_head', head);
    if (search) p.set('search', search);
    return p.toString();
}

function applyFilters() { currentPage = 1; loadList(); }
function clearFilters() {
    ['f-date-from','f-date-to','f-month','f-search'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('f-account').value = '';
    document.getElementById('f-account-head').value = '';
    applyFilters();
}
function setLoading(b) { document.getElementById('loadingBar').classList.toggle('hidden', !b); }

async function loadFilterOptions() {
    try {
        const r = await fetch(`${API}?action=filters`);
        const d = await r.json();
        if (!d.success) return;
        const aSel = document.getElementById('f-account');
        (d.accounts||[]).forEach(a => aSel.innerHTML += `<option value="${a.id}">${a.name}</option>`);
    } catch(e) {}
}

async function loadList() {
    setLoading(true);
    try {
        // Receives are cash_in rows within action=detail; filtered client-side
        // per page (same underlying data as Cash Flow's Detail view).
        const r = await fetch(`${API}?action=detail&page=${currentPage}&per_page=50&${buildParams()}`);
        const d = await r.json();
        if (!d.success) return;

        const rows = (d.rows||[]).filter(row => parseFloat(row.cash_in) > 0);
        document.getElementById('sum-count').textContent = rows.length;
        document.getElementById('sum-amount').textContent = fmt(rows.reduce((s,r)=>s+parseFloat(r.cash_in||0),0));

        document.getElementById('list-tbody').innerHTML = rows.map(row => `
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">${(row.date||'').substring(0,10)}</td>
                <td class="px-4 py-3 font-medium text-gray-800">${row.account_name||'—'}</td>
                <td class="px-4 py-3 text-gray-700">${row.source_user_name||'—'}</td>
                <td class="px-4 py-3">${accountHeadBadge(row.source_account_head)}</td>
                <td class="px-4 py-3 text-gray-600 max-w-xs truncate">${row.particular||'—'}</td>
                <td class="px-4 py-3 text-right text-green-700 font-medium">${fmt(row.cash_in)}</td>
            </tr>`).join('') || '<tr><td colspan="6" class="px-4 py-6 text-center text-gray-400">No receives found</td></tr>';

        const pag = document.getElementById('list-pagination');
        pag.innerHTML = `<span>Page ${d.page} of ${d.pages}</span>
            <div class="flex gap-2">
                ${d.page > 1 ? `<button onclick="goPage(${d.page-1})" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">Prev</button>` : ''}
                ${d.page < d.pages ? `<button onclick="goPage(${d.page+1})" class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700">Next</button>` : ''}
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
        const dateSuffix = new Date().toISOString().split('T')[0];
        const filename = `receive-report-${dateSuffix}`;
        const allRows = [];
        let page = 1, pages = 1;
        do {
            const r = await fetch(`${API}?action=detail&page=${page}&per_page=500&${buildParams()}`);
            const d = await r.json();
            if (!d.success) { alert('Export failed'); return; }
            allRows.push(...(d.rows||[]).filter(row => parseFloat(row.cash_in) > 0));
            pages = d.pages || 1;
            page++;
        } while (page <= pages);

        const rows = [
            ['Receive Report', '', '', '', ''],
            ['Date', 'Account', 'Received From', 'Particular', 'Amount'],
            ...allRows.map(row => [(row.date||'').substring(0,10), row.account_name||'', row.source_user_name||'', row.particular||'', parseFloat(row.cash_in)]),
        ];
        const colWidths = [{wch:12},{wch:22},{wch:20},{wch:30},{wch:14}];
        const boldCells = ['A1','A2'];

        if (type === 'csv') {
            const csv = rows.map(r => r.map(c => `"${c}"`).join(',')).join('\n');
            dlFile('data:text/csv;charset=utf-8,' + encodeURIComponent(csv), filename + '.csv');
        } else if (type === 'excel') {
            const ws = XLSX.utils.aoa_to_sheet(rows);
            ws['!cols'] = colWidths;
            boldCells.forEach(cell => { if (ws[cell]) ws[cell].s = { font: { bold: true } }; });
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Receive');
            XLSX.writeFile(wb, filename + '.xlsx');
        } else if (type === 'pdf') {
            exportPDF('Receive Report', rows, filename);
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