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
    <title>Profit &amp; Loss — TravHub</title>
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
            <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-chart-line text-purple-600 mr-2"></i>Profit &amp; Loss</h1>
            <p class="text-sm text-gray-500 mt-1">Revenue, COGS, and refund-charge profit</p>
        </div>
        <div class="relative" id="exportDropdownWrap">
            <button onclick="toggleExportMenu()" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium flex items-center gap-2">
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
                <input type="date" id="f-date-from" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-purple-400"></div>
            <div><label class="block text-xs font-medium text-gray-500 mb-1">Date To</label>
                <input type="date" id="f-date-to" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-purple-400"></div>
            <div><label class="block text-xs font-medium text-gray-500 mb-1">Client</label>
                <select id="f-client" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-purple-400"><option value="">All Clients</option></select></div>
            <div><label class="block text-xs font-medium text-gray-500 mb-1">Vendor</label>
                <select id="f-vendor" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-purple-400"><option value="">All Vendors</option></select></div>
            <div><label class="block text-xs font-medium text-gray-500 mb-1">Work</label>
                <select id="f-work" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-purple-400"><option value="">All Works</option></select></div>
            <div><label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
                <input type="text" id="f-search" placeholder="Name / Purpose..." class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-purple-400"></div>
        </div>
        <div class="flex gap-2 mt-3">
            <button onclick="applyFilters()" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium"><i class="fas fa-search mr-1"></i> Apply</button>
            <button onclick="clearFilters()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg text-sm font-medium"><i class="fas fa-redo mr-1"></i> Clear</button>
            <div class="ml-auto flex gap-2">
                <button onclick="setView('summary')" id="v-summary" class="px-3 py-2 rounded-lg text-sm font-medium bg-purple-600 text-white">Summary</button>
                <button onclick="setView('breakdown')" id="v-breakdown" class="px-3 py-2 rounded-lg text-sm font-medium bg-gray-200 text-gray-700">Breakdown</button>
                <button onclick="setView('detail')" id="v-detail" class="px-3 py-2 rounded-lg text-sm font-medium bg-gray-200 text-gray-700">Detail</button>
            </div>
        </div>
    </div>

    <div id="loadingBar" class="hidden text-center py-8"><i class="fas fa-spinner fa-spin text-2xl text-purple-500"></i></div>

    <!-- Summary view -->
    <div id="view-summary">
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs text-gray-500">Revenue</p><p class="text-xl font-bold text-indigo-600 mt-1" id="s-revenue">—</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs text-gray-500">COGS</p><p class="text-xl font-bold text-amber-600 mt-1" id="s-cogs">—</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs text-gray-500">Gross Profit</p><p class="text-xl font-bold text-emerald-600 mt-1" id="s-gross">—</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs text-gray-500">Refund Charge Profit</p><p class="text-xl font-bold text-teal-600 mt-1" id="s-refund-profit">—</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs text-gray-500">Office Expense</p><p class="text-xl font-bold text-rose-600 mt-1" id="s-expense">—</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs text-gray-500">Payroll Expense</p><p class="text-xl font-bold text-rose-600 mt-1" id="s-payroll">—</p>
            </div>
        </div>
        <div class="bg-purple-600 rounded-xl p-5 text-white mb-6 flex items-center justify-between">
            <div><p class="text-sm text-purple-100">Net Profit</p><p class="text-3xl font-bold mt-1" id="s-net">—</p></div>
            <div class="text-right"><p class="text-sm text-purple-100">Margin</p><p class="text-2xl font-bold mt-1" id="s-margin">—</p></div>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="p-3 bg-gray-50 border-b text-sm font-semibold text-gray-700">By Client (Sales)</div>
                <table class="min-w-full text-sm"><tbody id="s-client-tbody"></tbody></table>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="p-3 bg-gray-50 border-b text-sm font-semibold text-gray-700">By Vendor (Purchases)</div>
                <table class="min-w-full text-sm"><tbody id="s-vendor-tbody"></tbody></table>
            </div>
        </div>
    </div>

    <!-- Breakdown view -->
    <div id="view-breakdown" class="hidden bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="p-3 bg-gray-50 border-b border-gray-100 flex items-center gap-2">
            <span class="text-xs text-gray-500">Period:</span>
            <select id="b-period" onchange="loadBreakdown()" class="px-2 py-1 border border-gray-300 rounded text-sm">
                <option value="monthly">Monthly</option><option value="daily">Daily</option>
            </select>
        </div>
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Period</th>
                <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase text-xs">Revenue</th>
                <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase text-xs">COGS</th>
                <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase text-xs">Refund Charge Profit</th>
                <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase text-xs">Expense</th>
                <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase text-xs">Payroll</th>
                <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase text-xs">Net Profit</th>
            </tr></thead>
            <tbody id="breakdown-tbody"></tbody>
        </table>
    </div>

    <!-- Detail view -->
    <div id="view-detail" class="hidden bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Date</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Entry Type</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Party</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Purpose</th>
                <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase text-xs">Amount</th>
            </tr></thead>
            <tbody id="detail-tbody"></tbody>
        </table>
        <div id="detail-pagination" class="flex items-center justify-between px-4 py-3 border-t border-gray-100 text-xs text-gray-500"></div>
    </div>

</div>
</main>

<script>
const API = "<?php echo $ip_port; ?>/api/reports/profit_v2/endpoints.php";
let currentView = 'summary';
let currentPeriod = 'monthly';
let currentPage = 1;

function fmt(n) { return '৳' + (parseFloat(n)||0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}); }

function buildParams() {
    const p = new URLSearchParams();
    const df = document.getElementById('f-date-from').value;
    const dt = document.getElementById('f-date-to').value;
    const client = document.getElementById('f-client').value;
    const vendor = document.getElementById('f-vendor').value;
    const work = document.getElementById('f-work').value;
    const search = document.getElementById('f-search').value;
    if (df) p.set('date_from', df);
    if (dt) p.set('date_to', dt);
    if (client) p.set('client_id', client);
    if (vendor) p.set('vendor_id', vendor);
    if (work) p.set('work_id', work);
    if (search) p.set('search', search);
    return p.toString();
}

function setView(v) {
    currentView = v;
    ['summary','breakdown','detail'].forEach(x => {
        document.getElementById(`view-${x}`).classList.toggle('hidden', x !== v);
        document.getElementById(`v-${x}`).className = `px-3 py-2 rounded-lg text-sm font-medium ${x===v?'bg-purple-600 text-white':'bg-gray-200 text-gray-700'}`;
    });
    if (v === 'summary') loadSummary();
    if (v === 'breakdown') loadBreakdown();
    if (v === 'detail') { currentPage = 1; loadDetail(); }
}

function applyFilters() {
    if (currentView === 'summary')   loadSummary();
    if (currentView === 'breakdown') loadBreakdown();
    if (currentView === 'detail')    { currentPage = 1; loadDetail(); }
}
function clearFilters() {
    ['f-date-from','f-date-to','f-search'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('f-client').value = '';
    document.getElementById('f-vendor').value = '';
    document.getElementById('f-work').value = '';
    applyFilters();
}
function setLoading(b) { document.getElementById('loadingBar').classList.toggle('hidden', !b); }

async function loadFilterOptions() {
    try {
        const r = await fetch(`${API}?action=filters`);
        const d = await r.json();
        if (!d.success) return;
        const cSel = document.getElementById('f-client'), vSel = document.getElementById('f-vendor'), wSel = document.getElementById('f-work');
        (d.clients||[]).forEach(c => cSel.innerHTML += `<option value="${c.id}">${c.name||c.id}</option>`);
        (d.vendors||[]).forEach(v => vSel.innerHTML += `<option value="${v.id}">${v.name||v.id}</option>`);
        (d.works||[]).forEach(w => wSel.innerHTML += `<option value="${w.id}">${w.name||w.id}</option>`);
    } catch(e) {}
}

async function loadSummary() {
    setLoading(true);
    try {
        const r = await fetch(`${API}?action=summary&${buildParams()}`);
        const d = await r.json();
        if (!d.success) return;
        const s = d.summary;
        document.getElementById('s-revenue').textContent = fmt(s.revenue);
        document.getElementById('s-cogs').textContent = fmt(s.cogs);
        document.getElementById('s-gross').textContent = fmt(s.gross_profit);
        document.getElementById('s-refund-profit').textContent = fmt(s.refund_charge_profit);
        document.getElementById('s-expense').textContent = fmt(s.total_expense);
        document.getElementById('s-payroll').textContent = fmt(s.total_payroll_expense);
        document.getElementById('s-net').textContent = fmt(s.net_profit);
        document.getElementById('s-margin').textContent = s.margin_pct + '%';

        document.getElementById('s-client-tbody').innerHTML = (d.clients||[]).map(c => `
            <tr class="border-t border-gray-100"><td class="px-4 py-2 text-gray-700">${c.user_name||c.user_sys_id}</td>
            <td class="px-4 py-2 text-right font-medium text-indigo-600">${fmt(c.sale)}</td></tr>`).join('') || '<tr><td class="px-4 py-4 text-center text-gray-400">No data</td></tr>';

        document.getElementById('s-vendor-tbody').innerHTML = (d.vendors||[]).map(v => `
            <tr class="border-t border-gray-100"><td class="px-4 py-2 text-gray-700">${v.user_name||v.user_sys_id}</td>
            <td class="px-4 py-2 text-right font-medium text-amber-600">${fmt(v.purchase)}</td></tr>`).join('') || '<tr><td class="px-4 py-4 text-center text-gray-400">No data</td></tr>';
    } finally { setLoading(false); }
}

async function loadBreakdown() {
    currentPeriod = document.getElementById('b-period').value;
    setLoading(true);
    try {
        const r = await fetch(`${API}?action=breakdown&period=${currentPeriod}&${buildParams()}`);
        const d = await r.json();
        if (!d.success) return;
        document.getElementById('breakdown-tbody').innerHTML = (d.rows||[]).map(row => `
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-medium text-gray-700">${row.period}</td>
                <td class="px-4 py-3 text-right text-indigo-600">${fmt(row.revenue)}</td>
                <td class="px-4 py-3 text-right text-amber-600">${fmt(row.cogs)}</td>
                <td class="px-4 py-3 text-right text-teal-600">${fmt(row.refund_charge_profit)}</td>
                <td class="px-4 py-3 text-right text-rose-600">${fmt(row.expense)}</td>
                <td class="px-4 py-3 text-right text-rose-600">${fmt(row.payroll_expense)}</td>
                <td class="px-4 py-3 text-right font-semibold ${row.net_profit>=0?'text-emerald-700':'text-rose-600'}">${fmt(row.net_profit)}</td>
            </tr>`).join('') || '<tr><td colspan="7" class="px-4 py-6 text-center text-gray-400">No data</td></tr>';
    } finally { setLoading(false); }
}

async function loadDetail() {
    setLoading(true);
    try {
        const r = await fetch(`${API}?action=detail&page=${currentPage}&per_page=50&${buildParams()}`);
        const d = await r.json();
        if (!d.success) return;
        document.getElementById('detail-tbody').innerHTML = (d.rows||[]).map(row => `
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">${(row.date||'').substring(0,10)}</td>
                <td class="px-4 py-3"><span class="text-xs px-2 py-0.5 rounded-full ${row.entry_type==='Sale'?'bg-indigo-100 text-indigo-700':row.entry_type==='Purchase'?'bg-amber-100 text-amber-700':'bg-teal-100 text-teal-700'}">${row.entry_type}</span></td>
                <td class="px-4 py-3 text-gray-700">${row.user_name||row.user_sys_id||'—'}</td>
                <td class="px-4 py-3 text-gray-600 max-w-xs truncate">${row.purpose||'—'}</td>
                <td class="px-4 py-3 text-right font-medium">${fmt(row.amount)}</td>
            </tr>`).join('') || '<tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">No data</td></tr>';

        const pag = document.getElementById('detail-pagination');
        pag.innerHTML = `<span>Page ${d.page} of ${d.pages} (${d.total} entries)</span>
            <div class="flex gap-2">
                ${d.page > 1 ? `<button onclick="goPage(${d.page-1})" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">Prev</button>` : ''}
                ${d.page < d.pages ? `<button onclick="goPage(${d.page+1})" class="px-3 py-1 bg-purple-600 text-white rounded hover:bg-purple-700">Next</button>` : ''}
            </div>`;
    } finally { setLoading(false); }
}
function goPage(p) { currentPage = p; loadDetail(); }

function toggleExportMenu() { document.getElementById('exportMenu').classList.toggle('hidden'); }
document.addEventListener('click', e => {
    if (!document.getElementById('exportDropdownWrap')?.contains(e.target)) document.getElementById('exportMenu')?.classList.add('hidden');
});

async function exportData(type = 'csv') {
    document.getElementById('exportMenu')?.classList.add('hidden');
    setLoading(true);
    try {
        let filename, rows, colWidths, boldCells;
        const dateSuffix = new Date().toISOString().split('T')[0];

        if (currentView === 'summary') {
            const r = await fetch(`${API}?action=summary&${buildParams()}`);
            const d = await r.json();
            if (!d.success) { alert('Export failed'); return; }
            const s = d.summary;
            filename = `profit-summary-${dateSuffix}`;
            rows = [
                ['Profit & Loss — Summary', '', ''],
                ['Revenue', s.revenue, ''],
                ['COGS', s.cogs, ''],
                ['Gross Profit', s.gross_profit, ''],
                ['Refund Charge (Client)', s.refund_charge_client, ''],
                ['Refund Charge (Vendor)', s.refund_charge_vendor, ''],
                ['Refund Charge Profit', s.refund_charge_profit, ''],
                ['Office Expense', s.total_expense, ''],
                ['Payroll Expense', s.total_payroll_expense, ''],
                ['Net Profit', s.net_profit, ''],
                ['Margin %', s.margin_pct, ''],
                ['', '', ''],
                ['Client', 'Sale', ''],
                ...(d.clients||[]).map(c => [c.user_name||c.user_sys_id, parseFloat(c.sale), '']),
                ['', '', ''],
                ['Vendor', 'Purchase', ''],
                ...(d.vendors||[]).map(v => [v.user_name||v.user_sys_id, parseFloat(v.purchase), '']),
            ];
            colWidths = [{wch:28},{wch:18},{wch:12}];
            boldCells = ['A1','A13','A16'];

        } else if (currentView === 'breakdown') {
            const r = await fetch(`${API}?action=breakdown&period=${currentPeriod}&${buildParams()}`);
            const d = await r.json();
            if (!d.success) { alert('Export failed'); return; }
            filename = `profit-breakdown-${currentPeriod}-${dateSuffix}`;
            rows = [
                ['Profit & Loss — Breakdown', '', '', '', '', '', ''],
                ['Period', 'Revenue', 'COGS', 'Refund Charge Profit', 'Expense', 'Payroll', 'Net Profit'],
                ...(d.rows||[]).map(row => [row.period, parseFloat(row.revenue), parseFloat(row.cogs), parseFloat(row.refund_charge_profit), parseFloat(row.expense), parseFloat(row.payroll_expense), parseFloat(row.net_profit)]),
            ];
            colWidths = [{wch:14},{wch:18},{wch:18},{wch:20},{wch:14},{wch:14},{wch:18}];
            boldCells = ['A1','A2'];

        } else {
            filename = `profit-detail-${dateSuffix}`;
            const allRows = [];
            let page = 1, pages = 1;
            do {
                const r = await fetch(`${API}?action=detail&page=${page}&per_page=500&${buildParams()}`);
                const d = await r.json();
                if (!d.success) { alert('Export failed'); return; }
                allRows.push(...(d.rows||[]));
                pages = d.pages || 1;
                page++;
            } while (page <= pages);
            rows = [
                ['Profit & Loss — Detail', '', '', '', ''],
                ['Date', 'Entry Type', 'Party', 'Purpose', 'Amount'],
                ...allRows.map(row => [(row.date||'').substring(0,10), row.entry_type, row.user_name||row.user_sys_id||'', row.purpose||'', parseFloat(row.amount)]),
            ];
            colWidths = [{wch:12},{wch:14},{wch:22},{wch:30},{wch:14}];
            boldCells = ['A1','A2'];
        }

        if (type === 'csv') {
            const csv = rows.map(r => r.map(c => `"${c}"`).join(',')).join('\n');
            dlFile('data:text/csv;charset=utf-8,' + encodeURIComponent(csv), filename + '.csv');
        } else if (type === 'excel') {
            const ws = XLSX.utils.aoa_to_sheet(rows);
            ws['!cols'] = colWidths;
            boldCells.forEach(cell => { if (ws[cell]) ws[cell].s = { font: { bold: true } }; });
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Profit');
            XLSX.writeFile(wb, filename + '.xlsx');
        } else if (type === 'pdf') {
            exportPDF('Profit & Loss Report', rows, filename);
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

document.addEventListener('DOMContentLoaded', () => { loadFilterOptions(); loadSummary(); });
</script>
<script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>
</body>
</html>