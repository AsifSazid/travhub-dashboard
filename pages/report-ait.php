<?php
include_once('./authenticate.php');
require_once '../server/db_connection.php';
require_once '../server/permissions.php';
requireFullAccountingAccess($pdo, false);
$ip_port     = @file_get_contents('../ippath.txt') ?: 'http://103.104.219.3:898';;
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
        <div class="flex gap-2">
            <!-- Claim button — shows when entries selected -->
            <button id="claimBtn" onclick="generateClaim()"
                class="hidden px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium">
                <i class="fas fa-file-contract mr-1"></i> Generate Claim (<span id="claimCount">0</span>)
            </button>
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
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                <select id="f-status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-400">
                    <option value="">All</option>
                    <option value="recoverable">Recoverable</option>
                    <option value="recovered">Recovered</option>
                </select>
            </div>
            <div>
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

    <!-- Total badges -->
    <div id="totalBadge" class="hidden mb-4 flex items-center gap-3 flex-wrap">
        <div class="bg-amber-50 border border-amber-200 rounded-xl px-5 py-3 flex items-center gap-3">
            <i class="fas fa-receipt text-amber-600 text-lg"></i>
            <div>
                <div class="text-xs text-amber-600 font-medium uppercase">Total AIT</div>
                <div class="text-xl font-bold text-amber-700" id="totalAit">৳0.00</div>
            </div>
        </div>
        <div class="bg-rose-50 border border-rose-200 rounded-xl px-5 py-3 flex items-center gap-3">
            <i class="fas fa-clock text-rose-500 text-lg"></i>
            <div>
                <div class="text-xs text-rose-500 font-medium uppercase">Recoverable</div>
                <div class="text-xl font-bold text-rose-600" id="totalRecoverable">৳0.00</div>
            </div>
        </div>
        <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-5 py-3 flex items-center gap-3">
            <i class="fas fa-check-circle text-emerald-600 text-lg"></i>
            <div>
                <div class="text-xs text-emerald-600 font-medium uppercase">Recovered</div>
                <div class="text-xl font-bold text-emerald-700" id="totalRecovered">৳0.00</div>
            </div>
        </div>
    </div>

    <div id="loadingBar" class="hidden text-center py-12"><i class="fas fa-spinner fa-spin text-2xl text-amber-500"></i></div>

    <!-- Summary View -->
    <div id="view-summary" class="hidden bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-4 py-3 bg-amber-50 border-b border-amber-100">
            <span class="font-semibold text-amber-700 text-sm"><i class="fas fa-users mr-1.5"></i>Vendor-wise AIT Breakdown</span>
        </div>
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Vendor</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-400 uppercase">Entries</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-400 uppercase">Recoverable</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-400 uppercase">Recovered</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-400 uppercase">Total AIT</th>
            </tr></thead>
            <tbody id="summary-tbody"></tbody>
        </table>
    </div>

    <!-- Detail View -->
    <div id="view-detail" class="hidden bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-4 py-3 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
            <span class="font-semibold text-gray-700 text-sm"><i class="fas fa-list mr-1.5"></i>AIT Entries</span>
            <label class="flex items-center gap-1.5 text-xs text-gray-500 cursor-pointer">
                <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this.checked)"> Select All
            </label>
        </div>
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="px-3 py-3 w-8"></th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Date</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Vendor</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">PNR</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Work</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Purpose</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-400 uppercase">Status</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-400 uppercase">Slip</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-400 uppercase">Amount</th>
            </tr></thead>
            <tbody id="detail-tbody"></tbody>
        </table>
        <div id="detail-pagination" class="flex items-center justify-between px-4 py-3 border-t border-gray-100 text-xs text-gray-400"></div>
    </div>

</div>
</main>

<!-- Claim Modal -->
<div id="claimModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-gray-900"><i class="fas fa-file-contract text-indigo-600 mr-2"></i>AIT Claim Application</h2>
                <button onclick="closeClaimModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times text-xl"></i></button>
            </div>
            <div id="claimContent" class="text-sm text-gray-700 space-y-4"></div>
            <div class="flex gap-2 mt-6 pt-4 border-t border-gray-100">
                <button onclick="printClaim()" class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold">
                    <i class="fas fa-print mr-1"></i> Print / Download
                </button>
                <button onclick="closeClaimModal()" class="flex-1 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-sm font-semibold">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Hidden file input for slip upload -->
<input type="file" id="slipFileInput" class="hidden" accept=".pdf,.jpg,.jpeg,.png" onchange="uploadSlip()">

<script>
const API     = "<?php echo $ip_port; ?>/api/reports/ait/endpoints.php";
const BASE    = "<?php echo rtrim(str_replace('/api','',dirname($ip_port)),'/'); ?>";
let _view     = 'summary';
let _filters  = {};
let _detailPage = 1;
let _selectedIds = new Set();
let _uploadTargetId = null;

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
    _selectedIds.clear(); updateClaimBtn();
    load();
}

function buildParams() {
    const p = new URLSearchParams();
    if (_filters.dateFrom) p.set('date_from', _filters.dateFrom);
    if (_filters.dateTo)   p.set('date_to',   _filters.dateTo);
    if (_filters.vendor)   p.set('vendor_id',  _filters.vendor);
    if (_filters.work)     p.set('work_id',    _filters.work);
    if (_filters.status)   p.set('status',     _filters.status);
    return p.toString();
}

function applyFilters() {
    _filters = {
        dateFrom: document.getElementById('f-date-from').value,
        dateTo:   document.getElementById('f-date-to').value,
        vendor:   document.getElementById('f-vendor').value,
        work:     document.getElementById('f-work').value,
        status:   document.getElementById('f-status').value,
    };
    _detailPage = 1; _selectedIds.clear(); updateClaimBtn(); load();
}

function clearFilters() {
    ['f-date-from','f-date-to'].forEach(id => document.getElementById(id).value = '');
    ['f-vendor','f-work','f-status'].forEach(id => document.getElementById(id).value = '');
    _filters = {}; _detailPage = 1; _selectedIds.clear(); updateClaimBtn(); load();
}

function setLoading(on) { document.getElementById('loadingBar').classList.toggle('hidden', !on); }

async function load() {
    setLoading(true);
    try { _view === 'summary' ? await loadSummary() : await loadDetail(); }
    finally { setLoading(false); }
}

async function loadSummary() {
    const r = await fetch(`${API}?action=summary&${buildParams()}`);
    const d = await r.json(); if (!d.success) return;

    const totalAit   = (d.vendors||[]).reduce((s,v) => s+parseFloat(v.total_ait||0), 0);
    const totalRec   = (d.vendors||[]).reduce((s,v) => s+parseFloat(v.recoverable||0), 0);
    const totalRecvd = (d.vendors||[]).reduce((s,v) => s+parseFloat(v.recovered||0), 0);

    document.getElementById('totalAit').textContent        = fmt(totalAit);
    document.getElementById('totalRecoverable').textContent= fmt(totalRec);
    document.getElementById('totalRecovered').textContent  = fmt(totalRecvd);
    document.getElementById('totalBadge').classList.remove('hidden');

    document.getElementById('summary-tbody').innerHTML = (d.vendors||[]).length
        ? d.vendors.map(v => `
            <tr class="hover:bg-amber-50 border-t border-gray-50">
                <td class="px-4 py-3 font-medium text-gray-800">${v.vendor_name||'—'}</td>
                <td class="px-4 py-3 text-right text-gray-500">${v.entry_count}</td>
                <td class="px-4 py-3 text-right text-rose-600 font-medium">${fmt(v.recoverable)}</td>
                <td class="px-4 py-3 text-right text-emerald-600 font-medium">${fmt(v.recovered)}</td>
                <td class="px-4 py-3 text-right font-bold text-amber-700">${fmt(v.total_ait)}</td>
            </tr>`).join('')
        : `<tr><td colspan="5" class="px-4 py-8 text-center text-gray-300">No AIT entries</td></tr>`;
}

async function loadDetail() {
    const r = await fetch(`${API}?action=detail&page=${_detailPage}&per_page=50&${buildParams()}`);
    const d = await r.json(); if (!d.success) return;

    document.getElementById('totalAit').textContent = fmt(d.grand_total);
    document.getElementById('totalBadge').classList.remove('hidden');

    document.getElementById('detail-tbody').innerHTML = (d.rows||[]).length
        ? d.rows.map(row => {
            const isRecovered = row.ait_status === 'recovered';
            const statusBadge = isRecovered
                ? `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700"><i class="fas fa-check-circle"></i> Recovered</span>`
                : `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-600"><i class="fas fa-clock"></i> Recoverable</span>`;
            const slipCell = isRecovered
                ? `<a href="../${row.ait_slip_file}" target="_blank" class="text-indigo-600 hover:underline text-xs"><i class="fas fa-file-alt mr-1"></i>View</a>`
                : `<button onclick="triggerSlipUpload('${row.sys_id}')" class="text-xs px-2 py-1 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 rounded-lg"><i class="fas fa-upload mr-1"></i>Upload</button>`;
            const checked = _selectedIds.has(row.sys_id) ? 'checked' : '';
            return `<tr class="hover:bg-gray-50 border-t border-gray-50">
                <td class="px-3 py-3"><input type="checkbox" ${checked} onchange="toggleSelect('${row.sys_id}',this.checked)" class="w-3.5 h-3.5 accent-indigo-600"></td>
                <td class="px-4 py-3 text-gray-500 whitespace-nowrap text-xs">${(row.date||'').substring(0,10)}</td>
                <td class="px-4 py-3 font-medium text-gray-800">${row.user_name||'—'}</td>
                <td class="px-4 py-3 font-mono text-xs text-indigo-700 font-semibold">${row.pnr||'—'}</td>
                <td class="px-4 py-3 text-gray-500 text-xs">${row.work_title||'—'}</td>
                <td class="px-4 py-3 text-gray-500 text-xs max-w-xs truncate">${row.purpose||'—'}</td>
                <td class="px-4 py-3 text-center">${statusBadge}</td>
                <td class="px-4 py-3 text-center">${slipCell}</td>
                <td class="px-4 py-3 text-right font-semibold text-amber-700">${fmt(row.amount)}</td>
            </tr>`;
        }).join('')
        : `<tr><td colspan="9" class="px-4 py-8 text-center text-gray-300">No AIT entries found</td></tr>`;

    const pag = document.getElementById('detail-pagination');
    pag.innerHTML = d.total > d.per_page ? `
        <span>${d.total} entries — Page ${d.page} of ${d.pages}</span>
        <div class="flex gap-2">
            ${d.page>1 ? `<button onclick="goPage(${d.page-1})" class="px-3 py-1 bg-gray-100 rounded hover:bg-gray-200">Prev</button>` : ''}
            ${d.page<d.pages ? `<button onclick="goPage(${d.page+1})" class="px-3 py-1 bg-amber-600 text-white rounded hover:bg-amber-700">Next</button>` : ''}
        </div>` : '';
}

function goPage(p) { _detailPage = p; loadDetail(); }

// ── Selection ─────────────────────────────────────────────────
function toggleSelect(id, checked) {
    checked ? _selectedIds.add(id) : _selectedIds.delete(id);
    updateClaimBtn();
}
function toggleSelectAll(checked) {
    document.querySelectorAll('#detail-tbody input[type=checkbox]').forEach(cb => {
        cb.checked = checked;
        const id = cb.closest('tr')?.querySelector('input[type=checkbox]')?.getAttribute('onchange')?.match(/'([^']+)'/)?.[1];
        if (id) checked ? _selectedIds.add(id) : _selectedIds.delete(id);
    });
    // simpler approach
    document.querySelectorAll('#detail-tbody tr').forEach(tr => {
        const cb = tr.querySelector('input[type=checkbox]');
        if (!cb) return;
        cb.checked = checked;
        const onChange = cb.getAttribute('onchange');
        const match = onChange?.match(/toggleSelect\('([^']+)'/);
        if (match) checked ? _selectedIds.add(match[1]) : _selectedIds.delete(match[1]);
    });
    updateClaimBtn();
}
function updateClaimBtn() {
    const btn = document.getElementById('claimBtn');
    const cnt = document.getElementById('claimCount');
    cnt.textContent = _selectedIds.size;
    btn.classList.toggle('hidden', _selectedIds.size === 0);
}

// ── Slip Upload ───────────────────────────────────────────────
function triggerSlipUpload(entryId) {
    _uploadTargetId = entryId;
    document.getElementById('slipFileInput').click();
}
async function uploadSlip() {
    const file = document.getElementById('slipFileInput').files[0];
    if (!file || !_uploadTargetId) return;
    const fd = new FormData();
    fd.append('slip', file);
    fd.append('entry_sys_id', _uploadTargetId);
    try {
        const r = await fetch(`${API}?action=upload_slip`, {method:'POST', body:fd});
        const d = await r.json();
        if (d.success) { alert('Slip uploaded — status changed to Recovered'); loadDetail(); }
        else alert('Upload failed: ' + d.message);
    } catch(e) { alert('Upload error: ' + e.message); }
    document.getElementById('slipFileInput').value = '';
    _uploadTargetId = null;
}

// ── Claim Application ─────────────────────────────────────────
async function generateClaim() {
    if (_selectedIds.size === 0) return;
    const r = await fetch(`${API}?action=generate_claim`, {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({entry_ids: [..._selectedIds]}),
    });
    const d = await r.json(); if (!d.success) { alert('Failed'); return; }

    const today = new Date().toLocaleDateString('en-BD', {day:'2-digit',month:'long',year:'numeric'});
    const rows  = (d.entries||[]).map((e,i) => `
        <tr>
            <td style="padding:6px 8px;border-bottom:1px solid #e5e7eb">${i+1}</td>
            <td style="padding:6px 8px;border-bottom:1px solid #e5e7eb;font-family:monospace;font-weight:600;color:#4f46e5">${e.pnr||'—'}</td>
            <td style="padding:6px 8px;border-bottom:1px solid #e5e7eb">${(e.date||'').substring(0,10)}</td>
            <td style="padding:6px 8px;border-bottom:1px solid #e5e7eb">${e.work_title||'—'}</td>
            <td style="padding:6px 8px;border-bottom:1px solid #e5e7eb;text-align:right;font-weight:600">৳${parseFloat(e.amount||0).toLocaleString('en-US',{minimumFractionDigits:2})}</td>
        </tr>`).join('');

    document.getElementById('claimContent').innerHTML = `
        <div id="printArea" style="font-family:Arial,sans-serif;font-size:13px;color:#111;">
            <p style="text-align:right;color:#6b7280">${today}</p>
            <p><strong>To</strong><br>${d.vendor_name||'Vendor'}</p>
            <br>
            <p><strong>Subject: Request for AIT Tax Slip</strong></p>
            <br>
            <p>Dear Sir/Madam,</p>
            <p style="margin-top:8px">We wish to inform you that we have deducted Advance Income Tax (AIT) from our payments to you for the following air ticket transactions. We hereby request you to kindly provide us with the corresponding tax deposit slips so that we may file our tax records accordingly.</p>
            <br>
            <table style="width:100%;border-collapse:collapse;font-size:12px;">
                <thead>
                    <tr style="background:#f59e0b;color:#fff;">
                        <th style="padding:8px;text-align:left">#</th>
                        <th style="padding:8px;text-align:left">PNR</th>
                        <th style="padding:8px;text-align:left">Date</th>
                        <th style="padding:8px;text-align:left">Work</th>
                        <th style="padding:8px;text-align:right">AIT Amount</th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
                <tfoot>
                    <tr style="background:#fef3c7;font-weight:700;">
                        <td colspan="4" style="padding:8px;text-align:right">Total AIT Claim:</td>
                        <td style="padding:8px;text-align:right">৳${parseFloat(d.total_ait||0).toLocaleString('en-US',{minimumFractionDigits:2})}</td>
                    </tr>
                </tfoot>
            </table>
            <br>
            <p>We trust you will attend to this matter at your earliest convenience.</p>
            <br>
            <p>Thanking you,<br><strong>TravHub Global Limited</strong></p>
        </div>`;
    document.getElementById('claimModal').classList.remove('hidden');
}

function closeClaimModal() { document.getElementById('claimModal').classList.add('hidden'); }

function printClaim() {
    const content = document.getElementById('printArea').innerHTML;
    const win = window.open('', '_blank');
    win.document.write(`<html><head><title>AIT Claim Application</title>
        <style>body{font-family:Arial;padding:40px;max-width:750px;margin:0 auto;} @media print{body{padding:20px;}}</style>
        </head><body>${content}<script>window.onload=()=>window.print()<\/script></body></html>`);
    win.document.close();
}

function toggleExportMenu() { document.getElementById('exportMenu').classList.toggle('hidden'); }

function exportData(type) {
    document.getElementById('exportMenu').classList.add('hidden');
    fetch(`${API}?action=detail&per_page=9999&${buildParams()}`).then(r=>r.json()).then(d => {
        if (type === 'excel') {
            const rows = [
                ['Date','Vendor','PNR','Work','Purpose','Status','Amount (BDT)'],
                ...(d.rows||[]).map(r=>[
                    (r.date||'').substring(0,10), r.user_name||'', r.pnr||'',
                    r.work_title||'', r.purpose||'', r.ait_status||'recoverable', parseFloat(r.amount||0)
                ]),
                [],['','','','','','Total:', parseFloat(d.grand_total||0)],
            ];
            const ws = XLSX.utils.aoa_to_sheet(rows);
            ws['!cols'] = [{wch:12},{wch:25},{wch:12},{wch:20},{wch:40},{wch:12},{wch:15}];
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'AIT Recoverable');
            XLSX.writeFile(wb, `ait-recoverable-${new Date().toISOString().slice(0,10)}.xlsx`);
        }
    });
}

async function loadDropdowns() {
    try {
        const [vRes, wRes] = await Promise.all([
            fetch(`<?php echo $ip_port; ?>/api/vendors/all-vendors.php`),
            fetch(`<?php echo $ip_port; ?>/api/works/all-works.php`),
        ]);
        const [vData, wData] = await Promise.all([vRes.json(), wRes.json()]);
        const vSel = document.getElementById('f-vendor');
        const wSel = document.getElementById('f-work');
        (vData.vendors||[]).forEach(v => vSel.innerHTML += `<option value="${v.sys_id}">${v.name}</option>`);
        (wData.works||[]).forEach(w => wSel.innerHTML += `<option value="${w.sys_id}">${w.work_name||w.sys_id}</option>`);
    } catch(e) { console.warn('Dropdown load failed', e); }
}

document.addEventListener('click', e => {
    if (!e.target.closest('#exportDropdownWrap')) document.getElementById('exportMenu')?.classList.add('hidden');
});

document.addEventListener('DOMContentLoaded', () => { loadDropdowns(); setView('summary'); });
</script>
<script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>
</body>
</html>