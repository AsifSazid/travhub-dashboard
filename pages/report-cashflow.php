<?php
include_once('./authenticate.php');
$ip_port = trim(@file_get_contents('../ippath.txt') ?: 'http://103.104.219.3:898', '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cash Flow — TravHub</title>
    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- SheetJS for Excel export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
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
                <i class="fas fa-water text-blue-600 mr-2"></i>Cash Flow
            </h1>
            <p class="text-sm text-gray-500 mt-1">Overall + Account-wise cash movement</p>
        </div>
        <div class="relative" id="exportDropdownWrap">
            <button onclick="toggleExportMenu()"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium flex items-center gap-2">
                <i class="fas fa-download"></i> Export <i class="fas fa-chevron-down ml-1 text-xs"></i>
            </button>
            <div id="exportMenu" class="hidden absolute right-0 mt-1 w-40 bg-white border border-gray-200 rounded-xl shadow-lg z-50">
                <button onclick="exportData('csv')"  class="w-full text-left px-4 py-2.5 text-sm hover:bg-gray-50 flex items-center gap-2">
                    <i class="fas fa-file-csv text-green-600"></i> CSV
                </button>
                <button onclick="exportData('excel')" class="w-full text-left px-4 py-2.5 text-sm hover:bg-gray-50 flex items-center gap-2">
                    <i class="fas fa-file-excel text-green-700"></i> Excel
                </button>
                <button onclick="exportData('pdf')"  class="w-full text-left px-4 py-2.5 text-sm hover:bg-gray-50 flex items-center gap-2">
                    <i class="fas fa-file-pdf text-red-600"></i> PDF
                </button>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Date From</label>
                <input type="date" id="f-date-from" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-400">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Date To</label>
                <input type="date" id="f-date-to" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-400">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Quick Month</label>
                <input type="month" id="f-month" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-400">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Account</label>
                <select id="f-account" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-400">
                    <option value="">All Accounts</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Method</label>
                <select id="f-method" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-400">
                    <option value="">All Methods</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
                <input type="text" id="f-search" placeholder="Account / Description..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-400">
            </div>
        </div>
        <div class="flex gap-2 mt-3">
            <button onclick="applyFilters()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium">
                <i class="fas fa-search mr-1"></i> Apply
            </button>
            <button onclick="clearFilters()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg text-sm font-medium">
                <i class="fas fa-redo mr-1"></i> Clear
            </button>
            <div class="ml-auto flex gap-2">
                <button onclick="setView('summary')" id="v-summary"
                    class="px-3 py-2 rounded-lg text-sm font-medium bg-blue-600 text-white">Summary</button>
                <button onclick="setView('breakdown')" id="v-breakdown"
                    class="px-3 py-2 rounded-lg text-sm font-medium bg-gray-200 text-gray-700">Breakdown</button>
                <button onclick="setView('detail')" id="v-detail"
                    class="px-3 py-2 rounded-lg text-sm font-medium bg-gray-200 text-gray-700">Detail</button>
            </div>
        </div>
    </div>

    <!-- Summary Panel -->
    <div id="panel-summary">
        <!-- Overall cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-xl border-l-4 border-green-500 p-5 shadow-sm">
                <p class="text-xs font-medium text-gray-500">Total Cash In</p>
                <p id="s-in" class="text-3xl font-bold text-green-700 mt-1">—</p>
                <p class="text-xs text-gray-400 mt-1">All Deposits</p>
            </div>
            <div class="bg-white rounded-xl border-l-4 border-red-500 p-5 shadow-sm">
                <p class="text-xs font-medium text-gray-500">Total Cash Out</p>
                <p id="s-out" class="text-3xl font-bold text-red-700 mt-1">—</p>
                <p class="text-xs text-gray-400 mt-1">All Withdrawals</p>
            </div>
            <div class="bg-white rounded-xl border-l-4 border-blue-500 p-5 shadow-sm">
                <p class="text-xs font-medium text-gray-500">Net Cash Flow</p>
                <p id="s-net" class="text-3xl font-bold text-blue-700 mt-1">—</p>
                <p class="text-xs text-gray-400 mt-1">In - Out</p>
            </div>
        </div>

        <!-- Account wise -->
        <div class="bg-white rounded-xl border border-gray-200 mb-6">
            <div class="p-4 border-b">
                <h3 class="font-semibold text-gray-800"><i class="fas fa-university text-blue-500 mr-2"></i>Account Wise</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">Account</th>
                            <th class="px-4 py-3 text-right text-green-600">Cash In</th>
                            <th class="px-4 py-3 text-right text-red-600">Cash Out</th>
                            <th class="px-4 py-3 text-right text-blue-600">Net Flow</th>
                            <th class="px-4 py-3 text-right">Closing Balance</th>
                            <th class="px-4 py-3 text-right">Transactions</th>
                        </tr>
                    </thead>
                    <tbody id="account-tbody" class="divide-y divide-gray-100"></tbody>
                </table>
            </div>
        </div>

        <!-- Method wise -->
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="p-4 border-b">
                <h3 class="font-semibold text-gray-800"><i class="fas fa-money-check-alt text-purple-500 mr-2"></i>Method Wise</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">Method</th>
                            <th class="px-4 py-3 text-right text-green-600">Cash In</th>
                            <th class="px-4 py-3 text-right text-red-600">Cash Out</th>
                            <th class="px-4 py-3 text-right text-blue-600">Net</th>
                            <th class="px-4 py-3 text-right">Count</th>
                        </tr>
                    </thead>
                    <tbody id="method-tbody" class="divide-y divide-gray-100"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Breakdown Panel -->
    <div id="panel-breakdown" class="hidden">
        <div class="bg-white rounded-xl border border-gray-200 mb-4">
            <div class="p-4 border-b flex justify-between items-center">
                <h3 class="font-semibold text-gray-800">Period Breakdown</h3>
                <div class="flex gap-2">
                    <button onclick="setPeriod('monthly')" id="p-monthly"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium bg-blue-600 text-white">Monthly</button>
                    <button onclick="setPeriod('daily')" id="p-daily"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium bg-gray-200 text-gray-700">Daily</button>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">Period</th>
                            <th class="px-4 py-3 text-right text-green-600">Cash In</th>
                            <th class="px-4 py-3 text-right text-red-600">Cash Out</th>
                            <th class="px-4 py-3 text-right text-blue-600">Net Flow</th>
                            <th class="px-4 py-3 text-right">Transactions</th>
                        </tr>
                    </thead>
                    <tbody id="breakdown-tbody" class="divide-y divide-gray-100"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Detail Panel -->
    <div id="panel-detail" class="hidden">
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="p-4 border-b">
                <h3 class="font-semibold text-gray-800">Statement Details</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">Date</th>
                            <th class="px-4 py-3 text-left">Account</th>
                            <th class="px-4 py-3 text-left">Particular</th>
                            <th class="px-4 py-3 text-left">Method</th>
                            <th class="px-4 py-3 text-right text-green-600">Cash In</th>
                            <th class="px-4 py-3 text-right text-red-600">Cash Out</th>
                            <th class="px-4 py-3 text-right">Balance</th>
                        </tr>
                    </thead>
                    <tbody id="detail-tbody" class="divide-y divide-gray-100"></tbody>
                </table>
            </div>
            <div id="detail-pagination" class="p-4 flex justify-between items-center border-t text-sm text-gray-500"></div>
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

<script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>

<script>
const IP   = '<?php echo $ip_port; ?>';
const API  = `${IP}/api/reports/cashflow/endpoints.php`;

let currentView   = 'summary';
let currentPeriod = 'monthly';
let currentPage   = 1;

const fmt = n => '৳' + parseFloat(n || 0).toLocaleString('en-BD', {minimumFractionDigits:2, maximumFractionDigits:2});
const methodBadge = m => {
    const colors = { cash:'bg-green-100 text-green-700', mfs:'bg-blue-100 text-blue-700',
        npsb:'bg-indigo-100 text-indigo-700', cheque:'bg-yellow-100 text-yellow-700',
        'bftn-eft':'bg-purple-100 text-purple-700' };
    const cls = colors[m] || 'bg-gray-100 text-gray-700';
    return `<span class="px-2 py-0.5 rounded text-xs font-medium ${cls}">${(m||'—').toUpperCase()}</span>`;
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
});

async function loadFilters() {
    const r = await fetch(`${API}?action=filters`);
    const d = await r.json();
    if (!d.success) return;

    const accSel = document.getElementById('f-account');
    accSel.innerHTML = '<option value="">All Accounts</option>';
    (d.accounts||[]).forEach(a => {
        const o = document.createElement('option');
        o.value = a.id;
        o.textContent = `${a.name} (৳${parseFloat(a.balance||0).toFixed(2)})`;
        accSel.appendChild(o);
    });

    const methSel = document.getElementById('f-method');
    methSel.innerHTML = '<option value="">All Methods</option>';
    (d.methods||[]).forEach(m => {
        const o = document.createElement('option');
        o.value = m.method; o.textContent = (m.method||'').toUpperCase();
        methSel.appendChild(o);
    });
}

function buildParams(extra = {}) {
    const p = new URLSearchParams();
    const from    = document.getElementById('f-date-from').value;
    const to      = document.getElementById('f-date-to').value;
    const account = document.getElementById('f-account').value;
    const method  = document.getElementById('f-method').value;
    const search  = document.getElementById('f-search').value;
    if (from)    p.set('date_from', from);
    if (to)      p.set('date_to', to);
    if (account) p.set('account_id', account);
    if (method)  p.set('transfer_method', method);
    if (search)  p.set('search', search);
    Object.entries(extra).forEach(([k,v]) => p.set(k,v));
    return p.toString();
}

function setLoading(v) { document.getElementById('loading').classList.toggle('hidden', !v); }

async function loadData() {
    setLoading(true);
    if (currentView === 'summary')   await loadSummary();
    if (currentView === 'breakdown') await loadBreakdown();
    if (currentView === 'detail')    await loadDetail();
    setLoading(false);
}

async function loadSummary() {
    const r = await fetch(`${API}?action=summary&${buildParams()}`);
    const d = await r.json();
    if (!d.success) return;
    const o = d.overall;
    document.getElementById('s-in').textContent  = fmt(o.total_in);
    document.getElementById('s-out').textContent = fmt(o.total_out);
    const netEl = document.getElementById('s-net');
    netEl.textContent = fmt(o.net_flow);
    netEl.className   = `text-3xl font-bold mt-1 ${o.net_flow >= 0 ? 'text-blue-700' : 'text-red-700'}`;

    // Account table
    const atb = document.getElementById('account-tbody');
    atb.innerHTML = (d.accounts||[]).map(a => {
        const netCls = a.net_flow >= 0 ? 'text-blue-700 font-semibold' : 'text-red-700 font-semibold';
        return `<tr class="hover:bg-gray-50">
            <td class="px-4 py-3 font-medium text-gray-800">${a.account_name}</td>
            <td class="px-4 py-3 text-right text-green-700">${fmt(a.cash_in)}</td>
            <td class="px-4 py-3 text-right text-red-700">${fmt(a.cash_out)}</td>
            <td class="px-4 py-3 text-right ${netCls}">${fmt(a.net_flow)}</td>
            <td class="px-4 py-3 text-right text-gray-700">${fmt(a.closing_balance)}</td>
            <td class="px-4 py-3 text-right text-gray-500">${a.transaction_count}</td>
        </tr>`;
    }).join('') || '<tr><td colspan="6" class="px-4 py-6 text-center text-gray-400">No data</td></tr>';

    // Method table
    const mtb = document.getElementById('method-tbody');
    mtb.innerHTML = (d.methods||[]).map(m => {
        const netCls = m.net_flow >= 0 ? 'text-blue-700 font-semibold' : 'text-red-700 font-semibold';
        return `<tr class="hover:bg-gray-50">
            <td class="px-4 py-3">${methodBadge(m.transfer_method)}</td>
            <td class="px-4 py-3 text-right text-green-700">${fmt(m.cash_in)}</td>
            <td class="px-4 py-3 text-right text-red-700">${fmt(m.cash_out)}</td>
            <td class="px-4 py-3 text-right ${netCls}">${fmt(m.net_flow)}</td>
            <td class="px-4 py-3 text-right text-gray-500">${m.count}</td>
        </tr>`;
    }).join('') || '<tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">No data</td></tr>';
}

async function loadBreakdown() {
    const r = await fetch(`${API}?action=breakdown&period=${currentPeriod}&${buildParams()}`);
    const d = await r.json();
    if (!d.success) return;
    const tb = document.getElementById('breakdown-tbody');
    tb.innerHTML = (d.rows||[]).map(row => {
        const netCls = row.net_flow >= 0 ? 'text-blue-700 font-semibold' : 'text-red-700 font-semibold';
        return `<tr class="hover:bg-gray-50">
            <td class="px-4 py-3 font-medium text-gray-700">${row.period}</td>
            <td class="px-4 py-3 text-right text-green-700">${fmt(row.cash_in)}</td>
            <td class="px-4 py-3 text-right text-red-700">${fmt(row.cash_out)}</td>
            <td class="px-4 py-3 text-right ${netCls}">${fmt(row.net_flow)}</td>
            <td class="px-4 py-3 text-right text-gray-500">${row.transaction_count}</td>
        </tr>`;
    }).join('') || '<tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">No data</td></tr>';
}

async function loadDetail() {
    const r = await fetch(`${API}?action=detail&page=${currentPage}&per_page=50&${buildParams()}`);
    const d = await r.json();
    if (!d.success) return;

    const tb = document.getElementById('detail-tbody');
    tb.innerHTML = (d.rows||[]).map(row => `
        <tr class="hover:bg-gray-50">
            <td class="px-4 py-3 text-gray-600 whitespace-nowrap">${(row.date||'').substring(0,10)}</td>
            <td class="px-4 py-3 font-medium text-gray-800">${row.account_name||'—'}</td>
            <td class="px-4 py-3 text-gray-600 max-w-xs truncate">${row.particular||'—'}</td>
            <td class="px-4 py-3">${methodBadge(row.transfer_method)}</td>
            <td class="px-4 py-3 text-right text-green-700 font-medium">
                ${row.cash_in > 0 ? fmt(row.cash_in) : '<span class="text-gray-300">—</span>'}</td>
            <td class="px-4 py-3 text-right text-red-700 font-medium">
                ${row.cash_out > 0 ? fmt(row.cash_out) : '<span class="text-gray-300">—</span>'}</td>
            <td class="px-4 py-3 text-right text-gray-700">${fmt(row.balance)}</td>
        </tr>`).join('') || '<tr><td colspan="7" class="px-4 py-6 text-center text-gray-400">No data</td></tr>';

    const pag = document.getElementById('detail-pagination');
    pag.innerHTML = `
        <span>Showing page ${d.page} of ${d.pages} (${d.total} entries)</span>
        <div class="flex gap-2">
            ${d.page > 1 ? `<button onclick="goPage(${d.page-1})" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">Prev</button>` : ''}
            ${d.page < d.pages ? `<button onclick="goPage(${d.page+1})" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">Next</button>` : ''}
        </div>`;
}

function goPage(p) { currentPage = p; loadDetail(); }

function setView(v) {
    currentView = v;
    ['summary','breakdown','detail'].forEach(name => {
        document.getElementById(`panel-${name}`).classList.toggle('hidden', name !== v);
        const btn = document.getElementById(`v-${name}`);
        btn.className = name === v
            ? 'px-3 py-2 rounded-lg text-sm font-medium bg-blue-600 text-white'
            : 'px-3 py-2 rounded-lg text-sm font-medium bg-gray-200 text-gray-700';
    });
    currentPage = 1;
    loadData();
}

function setPeriod(p) {
    currentPeriod = p;
    ['monthly','daily'].forEach(name => {
        const btn = document.getElementById(`p-${name}`);
        btn.className = name === p
            ? 'px-3 py-1.5 rounded-lg text-xs font-medium bg-blue-600 text-white'
            : 'px-3 py-1.5 rounded-lg text-xs font-medium bg-gray-200 text-gray-700';
    });
    loadBreakdown();
}

function applyFilters() { currentPage = 1; loadData(); }

function clearFilters() {
    ['f-date-from','f-date-to','f-month','f-search'].forEach(id => document.getElementById(id).value = '');
    ['f-account','f-method'].forEach(id => document.getElementById(id).value = '');
    currentPage = 1;
    loadData();
}

function toggleExportMenu() {
    document.getElementById('exportMenu').classList.toggle('hidden');
}
document.addEventListener('click', e => {
    if (!document.getElementById('exportDropdownWrap')?.contains(e.target)) {
        document.getElementById('exportMenu')?.classList.add('hidden');
    }
});

async function exportData(type = 'csv') {
    document.getElementById('exportMenu')?.classList.add('hidden');
    setLoading(true);
    try {
        const r = await fetch(`${API}?action=export&${buildParams()}`);
        const d = await r.json();
        if (!d.success) { alert('Export failed'); return; }

        const filename = `cashflow-${new Date().toISOString().split('T')[0]}`;
        const rows = [
            ['Cash Flow Report', '', '', '', ''],
            ['Total Cash In', d.overall.total_in, '', '', ''],
            ['Total Cash Out', d.overall.total_out, '', '', ''],
            ['Net Cash Flow', d.overall.net_flow, '', '', ''],
            ['', '', '', '', ''],
            ['Account', 'Cash In', 'Cash Out', 'Net Flow', 'Closing Balance'],
            ...(d.accounts||[]).map(a => [a.account_name, parseFloat(a.cash_in), parseFloat(a.cash_out), parseFloat(a.net_flow), parseFloat(a.closing_balance)]),
            ['', '', '', '', ''],
            ['Method', 'Cash In', 'Cash Out', 'Net Flow', 'Count'],
            ...(d.methods||[]).map(m => [(m.transfer_method||'').toUpperCase(), parseFloat(m.cash_in), parseFloat(m.cash_out), parseFloat(m.net_flow), m.count]),
        ];

        if (type === 'csv') {
            const csv = rows.map(r => r.map(c => `"${c}"`).join(',')).join('\n');
            dlFile('data:text/csv;charset=utf-8,' + encodeURIComponent(csv), filename + '.csv');

        } else if (type === 'excel') {
            const ws = XLSX.utils.aoa_to_sheet(rows);
            // Column widths
            ws['!cols'] = [{wch:30},{wch:18},{wch:18},{wch:18},{wch:20}];
            // Bold header rows
            ['A1','A6','A9'].forEach(cell => {
                if (ws[cell]) ws[cell].s = { font: { bold: true } };
            });
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Cash Flow');
            XLSX.writeFile(wb, filename + '.xlsx');

        } else if (type === 'pdf') {
            exportPDF('Cash Flow Report', rows, filename);
        }
    } finally {
        setLoading(false);
    }
}

function dlFile(href, filename) {
    const a = document.createElement('a');
    a.href = href; a.download = filename; a.click();
}

function exportPDF(title, rows, filename) {
    const win = window.open('', '_blank');
    const tableRows = rows.slice(5).filter(r => r.join('').trim());
    const headerRow = tableRows.shift() || [];
    const html = `<!DOCTYPE html><html><head><meta charset="UTF-8">
    <title>${title}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
        h2 { color: #1e40af; margin-bottom: 4px; }
        .meta { color: #6b7280; font-size: 11px; margin-bottom: 16px; }
        .summary { display: flex; gap: 16px; margin-bottom: 20px; }
        .card { background: #f3f4f6; border-radius: 8px; padding: 12px 20px; min-width: 150px; }
        .card .label { font-size: 11px; color: #6b7280; }
        .card .value { font-size: 18px; font-weight: bold; color: #1e40af; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th { background: #1e40af; color: white; padding: 8px 12px; text-align: left; font-size: 11px; }
        td { padding: 7px 12px; border-bottom: 1px solid #e5e7eb; font-size: 11px; }
        tr:nth-child(even) td { background: #f9fafb; }
        .text-right { text-align: right; }
        .positive { color: #059669; } .negative { color: #dc2626; }
        @media print { button { display: none; } }
    </style></head><body>
    <h2>${title}</h2>
    <p class="meta">Generated: ${new Date().toLocaleString()}</p>
    <div class="summary">
        <div class="card"><div class="label">Cash In</div><div class="value" style="color:#059669">${rows[1][1]}</div></div>
        <div class="card"><div class="label">Cash Out</div><div class="value" style="color:#dc2626">${rows[2][1]}</div></div>
        <div class="card"><div class="label">Net Flow</div><div class="value">${rows[3][1]}</div></div>
    </div>
    <table>
        <thead><tr>${headerRow.map(h=>`<th>${h}</th>`).join('')}</tr></thead>
        <tbody>${tableRows.map(r=>`<tr>${r.map((c,i)=>`<td class="${i>0?'text-right':''}">${c}</td>`).join('')}</tr>`).join('')}</tbody>
    </table>
    <br><button onclick="window.print()" style="padding:8px 20px;background:#1e40af;color:white;border:none;border-radius:6px;cursor:pointer;font-size:13px;">
        🖨️ Print / Save as PDF
    </button>
    </body></html>`;
    win.document.write(html);
    win.document.close();
}
</script>
</body>
</html>