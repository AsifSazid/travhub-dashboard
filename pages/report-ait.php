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
    <title>AIT Recoverable — TravHub</title>
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
                <i class="fas fa-receipt text-amber-600 mr-2"></i>AIT Recoverable
            </h1>
            <p class="text-sm text-gray-500 mt-1">Advance Income Tax deducted from vendor payments</p>
        </div>
        <div class="relative" id="exportDropdownWrap">
            <button onclick="toggleExportMenu()"
                class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm font-medium flex items-center gap-2">
                <i class="fas fa-download"></i> Export <i class="fas fa-chevron-down ml-1 text-xs"></i>
            </button>
            <div id="exportMenu" class="hidden absolute right-0 mt-1 w-40 bg-white border border-gray-200 rounded-xl shadow-lg z-50">
                <button onclick="exportData('excel')" class="w-full text-left px-4 py-2.5 text-sm hover:bg-gray-50 flex items-center gap-2"><i class="fas fa-file-excel text-green-700"></i> Excel</button>
                <button onclick="exportData('pdf')"   class="w-full text-left px-4 py-2.5 text-sm hover:bg-gray-50 flex items-center gap-2"><i class="fas fa-file-pdf text-red-600"></i> PDF</button>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Date From</label>
                <input type="date" id="f-date-from" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-400">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Date To</label>
                <input type="date" id="f-date-to" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-400">
            </div>
            <div class="col-span-2">
                <label class="block text-xs font-medium text-gray-500 mb-1">Vendor</label>
                <select id="f-vendor" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-400">
                    <option value="">All Vendors</option>
                </select>
            </div>
            <div class="col-span-2">
                <label class="block text-xs font-medium text-gray-500 mb-1">Work</label>
                <select id="f-work" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-400">
                    <option value="">All Works</option>
                </select>
            </div>
        </div>
        <div class="flex gap-2 mt-3">
            <button onclick="applyFilters()" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm font-medium"><i class="fas fa-search mr-1"></i> Apply</button>
            <button onclick="clearFilters()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg text-sm font-medium"><i class="fas fa-redo mr-1"></i> Clear</button>
            <div class="ml-auto flex gap-2">
                <button onclick="setView('summary')" id="v-summary" class="px-3 py-2 rounded-lg text-sm font-medium bg-amber-600 text-white">Summary</button>
                <button onclick="setView('detail')"  id="v-detail"  class="px-3 py-2 rounded-lg text-sm font-medium bg-gray-200 text-gray-700">Detail</button>
            </div>
        </div>
    </div>

    <!-- Total badge -->
    <div id="totalBadge" class="hidden mb-4 flex items-center gap-3">
        <div class="bg-amber-50 border border-amber-200 rounded-xl px-5 py-3 flex items-center gap-3">
            <i class="fas fa-receipt text-amber-600 text-lg"></i>
            <div>
                <div class="text-xs text-amber-600 font-medium uppercase">Total AIT Recoverable</div>
                <div class="text-xl font-bold text-amber-700" id="totalAit">৳0.00</div>
            </div>
        </div>
    </div>

    <div id="loadingBar" class="hidden text-center py-12"><i class="fas fa-spinner fa-spin text-2xl text-amber-500"></i></div>

    <!-- Summary View: Vendor wise -->
    <div id="view-summary" class="hidden bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-4 py-3 bg-amber-50 border-b border-amber-100">
            <span class="font-semibold text-amber-700 text-sm"><i class="fas fa-users mr-1.5"></i>Vendor-wise AIT Breakdown</span>
        </div>
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Vendor</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-400 uppercase">Entries</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-400 uppercase">Total AIT</th>
                </tr>
            </thead>
            <tbody id="summary-tbody"></tbody>
        </table>
    </div>

    <!-- Detail View -->
    <div id="view-detail" class="hidden bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
            <span class="font-semibold text-gray-700 text-sm"><i class="fas fa-list mr-1.5"></i>AIT Entries</span>
        </div>
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Vendor</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Work</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Task</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Purpose</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-400 uppercase">Amount</th>
                </tr>
            </thead>
            <tbody id="detail-tbody"></tbody>
        </table>
        <div id="detail-pagination" class="flex items-center justify-between px-4 py-3 border-t border-gray-100 text-xs text-gray-400"></div>
    </div>

</div>
</main>

<script>
const API     = "<?php echo $ip_port; ?>/api/reports/ait/endpoints.php";
let _view     = 'summary';
let _filters  = {};
let _detailPage = 1;

function fmt(n) {
    const v = parseFloat(n) || 0;
    return '৳' + v.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
}

function setView(v) {
    _view = v;
    ['summary','detail'].forEach(x => {
        document.getElementById(`view-${x}`).classList.add('hidden');
        document.getElementById(`v-${x}`).className = 'px-3 py-2 rounded-lg text-sm font-medium ' +
            (x === v ? 'bg-amber-600 text-white' : 'bg-gray-200 text-gray-700');
    });
    document.getElementById(`view-${v}`).classList.remove('hidden');
    load();
}

function buildParams() {
    const p = new URLSearchParams();
    if (_filters.dateFrom) p.set('date_from', _filters.dateFrom);
    if (_filters.dateTo)   p.set('date_to',   _filters.dateTo);
    if (_filters.vendor)   p.set('vendor_id',  _filters.vendor);
    if (_filters.work)     p.set('work_id',    _filters.work);
    return p.toString();
}

function applyFilters() {
    _filters = {
        dateFrom: document.getElementById('f-date-from').value,
        dateTo:   document.getElementById('f-date-to').value,
        vendor:   document.getElementById('f-vendor').value,
        work:     document.getElementById('f-work').value,
    };
    _detailPage = 1;
    load();
}

function clearFilters() {
    ['f-date-from','f-date-to'].forEach(id => document.getElementById(id).value = '');
    ['f-vendor','f-work'].forEach(id => document.getElementById(id).value = '');
    _filters = {};
    _detailPage = 1;
    load();
}

function setLoading(on) {
    document.getElementById('loadingBar').classList.toggle('hidden', !on);
}

async function load() {
    setLoading(true);
    try {
        if (_view === 'summary') await loadSummary();
        else await loadDetail();
    } finally { setLoading(false); }
}

async function loadSummary() {
    const r = await fetch(`${API}?action=summary&${buildParams()}`);
    const d = await r.json();
    if (!d.success) return;

    const total = (d.vendors||[]).reduce((s,v) => s + parseFloat(v.total_ait||0), 0);
    document.getElementById('totalAit').textContent = fmt(total);
    document.getElementById('totalBadge').classList.remove('hidden');

    document.getElementById('summary-tbody').innerHTML = (d.vendors||[]).length
        ? (d.vendors||[]).map(v => `
            <tr class="hover:bg-amber-50 border-t border-gray-50">
                <td class="px-4 py-3 font-medium text-gray-800">${v.vendor_name||'—'}</td>
                <td class="px-4 py-3 text-right text-gray-500">${v.entry_count}</td>
                <td class="px-4 py-3 text-right font-semibold text-amber-700">${fmt(v.total_ait)}</td>
            </tr>`).join('')
        : `<tr><td colspan="3" class="px-4 py-8 text-center text-gray-300">No AIT entries found</td></tr>`;
}

async function loadDetail() {
    const r = await fetch(`${API}?action=detail&page=${_detailPage}&per_page=50&${buildParams()}`);
    const d = await r.json();
    if (!d.success) return;

    const total = (d.rows||[]).reduce((s,row) => s + parseFloat(row.amount||0), 0);
    document.getElementById('totalAit').textContent = fmt(d.grand_total || total);
    document.getElementById('totalBadge').classList.remove('hidden');

    document.getElementById('detail-tbody').innerHTML = (d.rows||[]).length
        ? (d.rows||[]).map(row => `
            <tr class="hover:bg-gray-50 border-t border-gray-50">
                <td class="px-4 py-3 text-gray-500 whitespace-nowrap">${(row.date||'').substring(0,10)}</td>
                <td class="px-4 py-3 font-medium text-gray-800">${row.user_name||'—'}</td>
                <td class="px-4 py-3 text-gray-500 text-xs">${row.work_title||'—'}</td>
                <td class="px-4 py-3 text-gray-500 text-xs">${row.task_title||'—'}</td>
                <td class="px-4 py-3 text-gray-500 text-xs max-w-xs truncate">${row.purpose||'—'}</td>
                <td class="px-4 py-3 text-right font-semibold text-amber-700">${fmt(row.amount)}</td>
            </tr>`).join('')
        : `<tr><td colspan="6" class="px-4 py-8 text-center text-gray-300">No AIT entries found</td></tr>`;

    const pag = document.getElementById('detail-pagination');
    pag.innerHTML = d.total > d.per_page ? `
        <span>${d.total} entries — Page ${d.page} of ${d.pages}</span>
        <div class="flex gap-2">
            ${d.page > 1 ? `<button onclick="goPage(${d.page-1})" class="px-3 py-1 bg-gray-100 rounded hover:bg-gray-200">Prev</button>` : ''}
            ${d.page < d.pages ? `<button onclick="goPage(${d.page+1})" class="px-3 py-1 bg-amber-600 text-white rounded hover:bg-amber-700">Next</button>` : ''}
        </div>` : '';
}

function goPage(p) { _detailPage = p; loadDetail(); }

function toggleExportMenu() {
    document.getElementById('exportMenu').classList.toggle('hidden');
}

function exportData(type) {
    document.getElementById('exportMenu').classList.add('hidden');
    if (type === 'excel') {
        fetch(`${API}?action=detail&per_page=9999&${buildParams()}`)
            .then(r => r.json()).then(d => {
                const rows = [
                    ['Date','Vendor','Work','Task','Purpose','Amount (BDT)'],
                    ...(d.rows||[]).map(r => [
                        (r.date||'').substring(0,10),
                        r.user_name||'', r.work_title||'',
                        r.task_title||'', r.purpose||'',
                        parseFloat(r.amount||0)
                    ]),
                    [],
                    ['','','','','Total AIT:', parseFloat(d.grand_total||0)],
                ];
                const ws = XLSX.utils.aoa_to_sheet(rows);
                ws['!cols'] = [{wch:12},{wch:25},{wch:20},{wch:15},{wch:40},{wch:15}];
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, 'AIT Recoverable');
                XLSX.writeFile(wb, `ait-recoverable-${new Date().toISOString().slice(0,10)}.xlsx`);
            });
    } else {
        fetch(`${API}?action=detail&per_page=9999&${buildParams()}`)
            .then(r => r.json()).then(d => {
                const win = window.open('', '_blank');
                win.document.write(`<html><head><title>AIT Recoverable</title><style>
                    body{font-family:Arial;padding:24px;} table{width:100%;border-collapse:collapse;}
                    th{background:#f59e0b;color:#fff;padding:8px;text-align:left;font-size:12px;}
                    td{padding:6px 8px;border-bottom:1px solid #e5e7eb;font-size:12px;}
                    .total{font-weight:bold;background:#fef3c7;}
                </style></head><body>
                <h2>AIT Recoverable Report</h2>
                <table><thead><tr>
                    <th>Date</th><th>Vendor</th><th>Work</th><th>Task</th><th>Purpose</th><th>Amount</th>
                </tr></thead><tbody>
                ${(d.rows||[]).map(r => `<tr>
                    <td>${(r.date||'').substring(0,10)}</td>
                    <td>${r.user_name||'—'}</td>
                    <td>${r.work_title||'—'}</td>
                    <td>${r.task_title||'—'}</td>
                    <td>${r.purpose||'—'}</td>
                    <td style="text-align:right">৳${parseFloat(r.amount||0).toLocaleString('en-US',{minimumFractionDigits:2})}</td>
                </tr>`).join('')}
                <tr class="total"><td colspan="5" style="text-align:right">Total AIT Recoverable:</td>
                <td style="text-align:right">৳${parseFloat(d.grand_total||0).toLocaleString('en-US',{minimumFractionDigits:2})}</td></tr>
                </tbody></table>
                <script>window.onload=()=>window.print()<\/script>
                </body></html>`);
                win.document.close();
            });
    }
}

// Load vendor + work dropdowns
async function loadDropdowns() {
    try {
        const [vRes, wRes] = await Promise.all([
            fetch(`<?php echo $ip_port; ?>/api/vendors/list.php?limit=999`),
            fetch(`<?php echo $ip_port; ?>/api/works/list.php?limit=999`),
        ]);
        const vData = await vRes.json();
        const wData = await wRes.json();
        const vSel  = document.getElementById('f-vendor');
        const wSel  = document.getElementById('f-work');
        (vData.vendors||vData.data||[]).forEach(v => {
            vSel.innerHTML += `<option value="${v.sys_id}">${v.name}</option>`;
        });
        (wData.works||wData.data||[]).forEach(w => {
            wSel.innerHTML += `<option value="${w.sys_id}">${w.work_name||w.sys_id}</option>`;
        });
    } catch(e) { console.warn('Dropdown load failed:', e); }
}

document.addEventListener('click', e => {
    if (!e.target.closest('#exportDropdownWrap')) document.getElementById('exportMenu')?.classList.add('hidden');
});

document.addEventListener('DOMContentLoaded', () => {
    loadDropdowns();
    setView('summary');
});
</script>
<script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>
</body>
</html>