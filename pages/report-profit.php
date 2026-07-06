<?php
include_once('./authenticate.php');
$ip_port = trim(@file_get_contents('../ippath.txt') ?: 'http://103.104.219.3:898', '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profit & Loss — TravHub</title>
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

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-chart-line text-green-600 mr-2"></i>Profit & Loss
            </h1>
            <p class="text-sm text-gray-500 mt-1">Revenue, Cost, Discount, Net Profit</p>
        </div>
        <div class="relative" id="exportDropdownWrap">
            <button onclick="toggleExportMenu()"
                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium flex items-center gap-2">
                <i class="fas fa-download"></i> Export <i class="fas fa-chevron-down ml-1 text-xs"></i>
            </button>
            <div id="exportMenu" class="hidden absolute right-0 mt-1 w-40 bg-white border border-gray-200 rounded-xl shadow-lg z-50">
                <button onclick="exportData('csv')"   class="w-full text-left px-4 py-2.5 text-sm hover:bg-gray-50 flex items-center gap-2">
                    <i class="fas fa-file-csv text-green-600"></i> CSV
                </button>
                <button onclick="exportData('excel')" class="w-full text-left px-4 py-2.5 text-sm hover:bg-gray-50 flex items-center gap-2">
                    <i class="fas fa-file-excel text-green-700"></i> Excel
                </button>
                <button onclick="exportData('pdf')"   class="w-full text-left px-4 py-2.5 text-sm hover:bg-gray-50 flex items-center gap-2">
                    <i class="fas fa-file-pdf text-red-600"></i> PDF
                </button>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
            <!-- Date From -->
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Date From</label>
                <input type="date" id="f-date-from" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-400">
            </div>
            <!-- Date To -->
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Date To</label>
                <input type="date" id="f-date-to" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-400">
            </div>
            <!-- Quick Month -->
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Quick Month</label>
                <input type="month" id="f-month" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-400">
            </div>
            <!-- Client -->
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Client</label>
                <select id="f-client" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-400">
                    <option value="">All Clients</option>
                </select>
            </div>
            <!-- Vendor -->
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Vendor</label>
                <select id="f-vendor" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-400">
                    <option value="">All Vendors</option>
                </select>
            </div>
            <!-- Work -->
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Work</label>
                <select id="f-work" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-400">
                    <option value="">All Works</option>
                </select>
            </div>
            <!-- Task -->
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Task</label>
                <select id="f-task" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-400">
                    <option value="">All Tasks</option>
                </select>
            </div>
        </div>
        <div class="flex gap-2 mt-3">
            <button onclick="applyFilters()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium">
                <i class="fas fa-search mr-1"></i> Apply
            </button>
            <button onclick="clearFilters()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg text-sm font-medium">
                <i class="fas fa-redo mr-1"></i> Clear
            </button>
            <!-- View toggle -->
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

    <!-- Summary Cards -->
    <div id="panel-summary">
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
            <div class="bg-white rounded-xl border-l-4 border-blue-500 p-4 shadow-sm">
                <p class="text-xs font-medium text-gray-500">Revenue</p>
                <p id="s-revenue" class="text-2xl font-bold text-blue-700 mt-1">—</p>
                <p class="text-xs text-gray-400 mt-1">Total Sale</p>
            </div>
            <div class="bg-white rounded-xl border-l-4 border-red-500 p-4 shadow-sm">
                <p class="text-xs font-medium text-gray-500">COGS</p>
                <p id="s-cogs" class="text-2xl font-bold text-red-700 mt-1">—</p>
                <p class="text-xs text-gray-400 mt-1">Cost of Goods Sold</p>
            </div>
            <div class="bg-white rounded-xl border-l-4 border-green-500 p-4 shadow-sm">
                <p class="text-xs font-medium text-gray-500">Gross Profit</p>
                <p id="s-gross" class="text-2xl font-bold text-green-700 mt-1">—</p>
                <p class="text-xs text-gray-400 mt-1">Revenue - COGS</p>
            </div>
            <div class="bg-white rounded-xl border-l-4 border-orange-500 p-4 shadow-sm">
                <p class="text-xs font-medium text-gray-500">Discount</p>
                <p id="s-discount" class="text-2xl font-bold text-orange-700 mt-1">—</p>
                <p class="text-xs text-gray-400 mt-1">Total Discount Given</p>
            </div>
            <div class="bg-white rounded-xl border-l-4 border-indigo-500 p-4 shadow-sm">
                <p class="text-xs font-medium text-gray-500">Net Profit</p>
                <p id="s-net" class="text-2xl font-bold text-indigo-700 mt-1">—</p>
                <p id="s-margin" class="text-xs text-gray-400 mt-1">Margin: —</p>
            </div>
        </div>

        <!-- Client + Vendor tables -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Client wise -->
            <div class="bg-white rounded-xl border border-gray-200">
                <div class="p-4 border-b">
                    <h3 class="font-semibold text-gray-800"><i class="fas fa-users text-blue-500 mr-2"></i>Client Wise</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                            <tr>
                                <th class="px-4 py-3 text-left">Client</th>
                                <th class="px-4 py-3 text-right">Sale</th>
                                <th class="px-4 py-3 text-right">Receive</th>
                                <th class="px-4 py-3 text-right">Discount</th>
                            </tr>
                        </thead>
                        <tbody id="client-tbody" class="divide-y divide-gray-100"></tbody>
                    </table>
                </div>
            </div>
            <!-- Vendor wise -->
            <div class="bg-white rounded-xl border border-gray-200">
                <div class="p-4 border-b">
                    <h3 class="font-semibold text-gray-800"><i class="fas fa-building text-red-500 mr-2"></i>Vendor Wise</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                            <tr>
                                <th class="px-4 py-3 text-left">Vendor</th>
                                <th class="px-4 py-3 text-right">Purchase</th>
                                <th class="px-4 py-3 text-right">Payment</th>
                                <th class="px-4 py-3 text-right">Discount</th>
                            </tr>
                        </thead>
                        <tbody id="vendor-tbody" class="divide-y divide-gray-100"></tbody>
                    </table>
                </div>
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
                            <th class="px-4 py-3 text-right text-blue-600">Revenue</th>
                            <th class="px-4 py-3 text-right text-red-600">COGS</th>
                            <th class="px-4 py-3 text-right text-orange-600">Discount</th>
                            <th class="px-4 py-3 text-right text-green-600">Net Profit</th>
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
                <h3 class="font-semibold text-gray-800">Entry Details</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">Date</th>
                            <th class="px-4 py-3 text-left">Type</th>
                            <th class="px-4 py-3 text-left">Name</th>
                            <th class="px-4 py-3 text-left">Purpose</th>
                            <th class="px-4 py-3 text-left">Work/Task</th>
                            <th class="px-4 py-3 text-right">Amount</th>
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
const API  = `${IP}/api/reports/profit/endpoints.php`;

let currentView   = 'summary';
let currentPeriod = 'monthly';
let currentPage   = 1;

const fmt = n => '৳' + parseFloat(n || 0).toLocaleString('en-BD', {minimumFractionDigits:2, maximumFractionDigits:2});

document.addEventListener('DOMContentLoaded', () => {
    loadFilters();
    loadData();
    // Month quick select
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
    populateSelect('f-client', d.clients, 'All Clients');
    populateSelect('f-vendor', d.vendors, 'All Vendors');
    populateSelect('f-work',   d.works,   'All Works');
    populateSelect('f-task',   d.tasks,   'All Tasks');
}

function populateSelect(id, items, placeholder) {
    const sel = document.getElementById(id);
    sel.innerHTML = `<option value="">${placeholder}</option>`;
    (items||[]).forEach(i => {
        const o = document.createElement('option');
        o.value = i.id; o.textContent = i.name;
        sel.appendChild(o);
    });
}

function buildParams(extra = {}) {
    const p = new URLSearchParams();
    const from = document.getElementById('f-date-from').value;
    const to   = document.getElementById('f-date-to').value;
    const client = document.getElementById('f-client').value;
    const vendor = document.getElementById('f-vendor').value;
    const work   = document.getElementById('f-work').value;
    const task   = document.getElementById('f-task').value;
    if (from)   p.set('date_from', from);
    if (to)     p.set('date_to', to);
    if (client) p.set('client_id', client);
    if (vendor) p.set('vendor_id', vendor);
    if (work)   p.set('work_id', work);
    if (task)   p.set('task_id', task);
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
    const s = d.summary;
    document.getElementById('s-revenue').textContent  = fmt(s.revenue);
    document.getElementById('s-cogs').textContent     = fmt(s.cogs);
    document.getElementById('s-gross').textContent    = fmt(s.gross_profit);
    document.getElementById('s-discount').textContent = fmt(s.discount);
    const netEl = document.getElementById('s-net');
    netEl.textContent = fmt(s.net_profit);
    netEl.className   = `text-2xl font-bold mt-1 ${s.net_profit >= 0 ? 'text-indigo-700' : 'text-red-700'}`;
    document.getElementById('s-margin').textContent   = `Margin: ${s.margin_pct}%`;

    // Client table
    const ctb = document.getElementById('client-tbody');
    ctb.innerHTML = (d.clients||[]).map(c => `
        <tr class="hover:bg-gray-50">
            <td class="px-4 py-3 font-medium text-gray-800">${c.user_name}</td>
            <td class="px-4 py-3 text-right text-blue-700">${fmt(c.sale)}</td>
            <td class="px-4 py-3 text-right text-green-700">${fmt(c.receive)}</td>
            <td class="px-4 py-3 text-right text-orange-700">${fmt(c.discount)}</td>
        </tr>`).join('') || '<tr><td colspan="4" class="px-4 py-6 text-center text-gray-400">No data</td></tr>';

    // Vendor table
    const vtb = document.getElementById('vendor-tbody');
    vtb.innerHTML = (d.vendors||[]).map(v => `
        <tr class="hover:bg-gray-50">
            <td class="px-4 py-3 font-medium text-gray-800">${v.user_name}</td>
            <td class="px-4 py-3 text-right text-red-700">${fmt(v.purchase)}</td>
            <td class="px-4 py-3 text-right text-green-700">${fmt(v.payment)}</td>
            <td class="px-4 py-3 text-right text-orange-700">${fmt(v.discount)}</td>
        </tr>`).join('') || '<tr><td colspan="4" class="px-4 py-6 text-center text-gray-400">No data</td></tr>';
}

async function loadBreakdown() {
    const r = await fetch(`${API}?action=breakdown&period=${currentPeriod}&${buildParams()}`);
    const d = await r.json();
    if (!d.success) return;
    const tb = document.getElementById('breakdown-tbody');
    tb.innerHTML = (d.rows||[]).map(row => {
        const profitCls = row.net_profit >= 0 ? 'text-green-700 font-semibold' : 'text-red-700 font-semibold';
        return `<tr class="hover:bg-gray-50">
            <td class="px-4 py-3 font-medium text-gray-700">${row.period}</td>
            <td class="px-4 py-3 text-right text-blue-700">${fmt(row.revenue)}</td>
            <td class="px-4 py-3 text-right text-red-700">${fmt(row.cogs)}</td>
            <td class="px-4 py-3 text-right text-orange-700">${fmt(row.discount)}</td>
            <td class="px-4 py-3 text-right ${profitCls}">${fmt(row.net_profit)}</td>
        </tr>`;
    }).join('') || '<tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">No data</td></tr>';
}

async function loadDetail() {
    const r = await fetch(`${API}?action=detail&page=${currentPage}&per_page=50&${buildParams()}`);
    const d = await r.json();
    if (!d.success) return;

    const rtColors = { 1:'bg-blue-100 text-blue-700', 2:'bg-red-100 text-red-700', 5:'bg-orange-100 text-orange-700' };
    const rtLabels = { 1:'Sale', 2:'Purchase', 5:'Discount' };

    const tb = document.getElementById('detail-tbody');
    tb.innerHTML = (d.rows||[]).map(row => `
        <tr class="hover:bg-gray-50">
            <td class="px-4 py-3 text-gray-600 whitespace-nowrap">${(row.date||'').substring(0,10)}</td>
            <td class="px-4 py-3">
                <span class="px-2 py-0.5 rounded text-xs font-medium ${rtColors[row.related_type]||'bg-gray-100 text-gray-700'}">
                    ${rtLabels[row.related_type]||'—'}
                </span>
            </td>
            <td class="px-4 py-3 text-gray-800 font-medium">${row.user_name||'—'}</td>
            <td class="px-4 py-3 text-gray-600 max-w-xs truncate">${row.purpose||'—'}</td>
            <td class="px-4 py-3 text-xs text-gray-400">${row.work_title||'—'}${row.task_title?' / '+row.task_title:''}</td>
            <td class="px-4 py-3 text-right font-semibold ${row.related_type==1?'text-blue-700':row.related_type==2?'text-red-700':'text-orange-700'}">${fmt(row.amount)}</td>
        </tr>`).join('') || '<tr><td colspan="6" class="px-4 py-6 text-center text-gray-400">No data</td></tr>';

    // Pagination
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
    ['f-date-from','f-date-to','f-month'].forEach(id => document.getElementById(id).value = '');
    ['f-client','f-vendor','f-work','f-task'].forEach(id => document.getElementById(id).value = '');
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
        const s = d.summary;

        const filename = `profit-loss-${new Date().toISOString().split('T')[0]}`;
        const rows = [
            ['Profit & Loss Report', '', ''],
            ['', '', ''],
            ['Summary', '', ''],
            ['Revenue (Total Sale)', parseFloat(s.revenue), ''],
            ['COGS (Total Purchase)', parseFloat(s.cogs), ''],
            ['Gross Profit', parseFloat(s.gross_profit), ''],
            ['Discount Given', parseFloat(s.discount), ''],
            ['Net Profit', parseFloat(s.net_profit), ''],
            ['Profit Margin', s.margin_pct + '%', ''],
            ['', '', ''],
            ['Client Wise', '', ''],
            ['Client', 'Sale', 'Receive', 'Discount'],
            ...(d.clients||[]).map(c => [c.user_name, parseFloat(c.sale), parseFloat(c.receive), parseFloat(c.discount)]),
            ['', '', '', ''],
            ['Vendor Wise', '', '', ''],
            ['Vendor', 'Purchase', 'Payment', 'Discount'],
            ...(d.vendors||[]).map(v => [v.user_name, parseFloat(v.purchase), parseFloat(v.payment), parseFloat(v.discount)]),
        ];

        if (type === 'csv') {
            const csv = rows.map(r => r.map(c => `"${c}"`).join(',')).join('\n');
            dlFile('data:text/csv;charset=utf-8,' + encodeURIComponent(csv), filename + '.csv');

        } else if (type === 'excel') {
            const ws = XLSX.utils.aoa_to_sheet(rows);
            ws['!cols'] = [{wch:35},{wch:20},{wch:20},{wch:20}];
            // Bold summary rows
            ['A1','A3','A11','A15'].forEach(cell => {
                if (ws[cell]) ws[cell].s = { font: { bold: true } };
            });
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Profit & Loss');
            XLSX.writeFile(wb, filename + '.xlsx');

        } else if (type === 'pdf') {
            exportPDF(s, d.clients||[], d.vendors||[], filename);
        }
    } finally {
        setLoading(false);
    }
}

function dlFile(href, filename) {
    const a = document.createElement('a');
    a.href = href; a.download = filename; a.click();
}

function exportPDF(s, clients, vendors, filename) {
    const win = window.open('', '_blank');
    const netColor = s.net_profit >= 0 ? '#059669' : '#dc2626';
    const clientRows = clients.map(c => `<tr>
        <td>${c.user_name}</td>
        <td class="text-right">${parseFloat(c.sale).toFixed(2)}</td>
        <td class="text-right">${parseFloat(c.receive).toFixed(2)}</td>
        <td class="text-right">${parseFloat(c.discount).toFixed(2)}</td>
    </tr>`).join('');
    const vendorRows = vendors.map(v => `<tr>
        <td>${v.user_name}</td>
        <td class="text-right">${parseFloat(v.purchase).toFixed(2)}</td>
        <td class="text-right">${parseFloat(v.payment).toFixed(2)}</td>
        <td class="text-right">${parseFloat(v.discount).toFixed(2)}</td>
    </tr>`).join('');

    const html = `<!DOCTYPE html><html><head><meta charset="UTF-8">
    <title>Profit & Loss Report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; color: #111; }
        h2 { color: #166534; margin-bottom: 4px; }
        .meta { color: #6b7280; font-size: 11px; margin-bottom: 16px; }
        .summary-grid { display: grid; grid-template-columns: repeat(5,1fr); gap: 12px; margin-bottom: 24px; }
        .card { background: #f3f4f6; border-radius: 8px; padding: 12px; }
        .card .label { font-size: 10px; color: #6b7280; margin-bottom: 4px; }
        .card .value { font-size: 16px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        th { background: #166534; color: white; padding: 8px 10px; text-align: left; font-size: 11px; }
        td { padding: 6px 10px; border-bottom: 1px solid #e5e7eb; font-size: 11px; }
        tr:nth-child(even) td { background: #f9fafb; }
        .text-right { text-align: right; }
        h3 { margin: 16px 0 8px; font-size: 13px; color: #374151; }
        @media print { button { display: none; } }
    </style></head><body>
    <h2>Profit & Loss Report</h2>
    <p class="meta">Generated: ${new Date().toLocaleString()}</p>
    <div class="summary-grid">
        <div class="card"><div class="label">Revenue</div><div class="value" style="color:#1d4ed8">${parseFloat(s.revenue).toFixed(2)}</div></div>
        <div class="card"><div class="label">COGS</div><div class="value" style="color:#dc2626">${parseFloat(s.cogs).toFixed(2)}</div></div>
        <div class="card"><div class="label">Gross Profit</div><div class="value" style="color:#059669">${parseFloat(s.gross_profit).toFixed(2)}</div></div>
        <div class="card"><div class="label">Discount</div><div class="value" style="color:#d97706">${parseFloat(s.discount).toFixed(2)}</div></div>
        <div class="card"><div class="label">Net Profit</div><div class="value" style="color:${netColor}">${parseFloat(s.net_profit).toFixed(2)} (${s.margin_pct}%)</div></div>
    </div>
    <h3>Client Wise</h3>
    <table>
        <thead><tr><th>Client</th><th>Sale</th><th>Receive</th><th>Discount</th></tr></thead>
        <tbody>${clientRows||'<tr><td colspan="4" style="text-align:center;color:#9ca3af">No data</td></tr>'}</tbody>
    </table>
    <h3>Vendor Wise</h3>
    <table>
        <thead><tr><th>Vendor</th><th>Purchase</th><th>Payment</th><th>Discount</th></tr></thead>
        <tbody>${vendorRows||'<tr><td colspan="4" style="text-align:center;color:#9ca3af">No data</td></tr>'}</tbody>
    </table>
    <button onclick="window.print()" style="padding:8px 20px;background:#166534;color:white;border:none;border-radius:6px;cursor:pointer;font-size:13px;">
        🖨️ Print / Save as PDF
    </button>
    </body></html>`;
    win.document.write(html);
    win.document.close();
}
</script>
</body>
</html>