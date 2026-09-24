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
    <title>Loan Management — TravHub</title>
    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-gray-50">
<?php include '../elements/header.php'; ?>
<?php include '../elements/aside.php'; ?>
<?php include '../elements/preview-model.php'; ?>

<main id="mainContent" class="pt-16 pl-64 transition-all duration-300">
<div class="p-6">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-hand-holding-dollar text-teal-600 mr-2"></i>Loan Management</h1>
            <p class="text-sm text-gray-500 mt-1">Company, employee, vendor, and client loans — tracked separately from purchase/sale accounting</p>
        </div>
        <a href="loans-create.php" class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-sm font-medium flex items-center gap-2"><i class="fas fa-plus"></i> Create Loan</a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
            <div><label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                <select id="f-status" onchange="applyFilters()" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">All</option><option value="active">Active</option><option value="closed">Closed</option><option value="defaulted">Defaulted</option>
                </select></div>
            <div><label class="block text-xs font-medium text-gray-500 mb-1">Party Type</label>
                <select id="f-party-type" onchange="applyFilters()" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">All</option><option value="company">Company</option><option value="employee">Employee</option><option value="vendor">Vendor</option><option value="client">Client</option>
                </select></div>
        </div>
    </div>

    <!-- Summary -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500">Total Loans</p><p class="text-2xl font-bold text-gray-800 mt-1" id="sum-total">—</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500">Active Loans</p><p class="text-2xl font-bold text-gray-800 mt-1" id="sum-active">—</p>
        </div>
        <div class="bg-teal-600 rounded-xl p-4 text-white">
            <p class="text-xs text-teal-100">Total Outstanding</p><p class="text-2xl font-bold mt-1" id="sum-outstanding">—</p>
        </div>
    </div>

    <div id="loadingBar" class="hidden text-center py-8"><i class="fas fa-spinner fa-spin text-2xl text-teal-500"></i></div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Lender → Borrower</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase text-xs">Principal</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase text-xs">Outstanding</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Type</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Status</th>
                    </tr>
                </thead>
                <tbody id="list-tbody"></tbody>
            </table>
        </div>
        <div id="list-pagination" class="flex items-center justify-between px-4 py-3 border-t border-gray-100 text-xs text-gray-500"></div>
    </div>

</div>
</main>

<!-- Loan Detail Modal -->
<div id="detailModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.45);">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[85vh] overflow-y-auto">
        <div class="flex items-center justify-between p-4 border-b border-gray-100 sticky top-0 bg-white">
            <h3 class="font-semibold text-gray-800 text-sm"><i class="fas fa-file-invoice-dollar mr-2 text-teal-600"></i>Loan Detail</h3>
            <button onclick="closeDetail()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-4" id="detail-body"></div>
    </div>
</div>

<script>
const API = "<?php echo $ip_port; ?>/api/loans/list.php";
const API_REPAY = "<?php echo $ip_port; ?>/api/loans/repay.php";
const API_ALL_ACCOUNTS = "<?php echo $ip_port; ?>/api/accounts/all-trxnable-accounts.php";
let currentPage = 1;
let _accounts = [];

function fmt(n) { return '৳' + (parseFloat(n)||0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}); }

function buildParams() {
    const p = new URLSearchParams();
    const status = document.getElementById('f-status').value;
    const partyType = document.getElementById('f-party-type').value;
    if (status) p.set('status', status);
    if (partyType) p.set('party_type', partyType);
    return p.toString();
}

function applyFilters() { currentPage = 1; loadList(); }
function setLoading(b) { document.getElementById('loadingBar').classList.toggle('hidden', !b); }

const statusBadge = { active: 'bg-emerald-100 text-emerald-700', closed: 'bg-gray-100 text-gray-600', defaulted: 'bg-rose-100 text-rose-700' };

async function loadList() {
    setLoading(true);
    try {
        const r = await fetch(`${API}?action=list&page=${currentPage}&${buildParams()}`);
        const j = await r.json();
        if (!j.success) return;

        document.getElementById('sum-total').textContent = j.summary.total_loans;
        document.getElementById('sum-active').textContent = j.summary.active_loans;
        document.getElementById('sum-outstanding').textContent = fmt(j.summary.total_outstanding);

        document.getElementById('list-tbody').innerHTML = (j.rows||[]).map(loan => `
            <tr class="hover:bg-gray-50 cursor-pointer" onclick="openDetail('${loan.sys_id}')">
                <td class="px-4 py-3">
                    <div class="font-medium text-gray-800">${loan.lender_name} <i class="fas fa-arrow-right text-gray-300 text-xs mx-1"></i> ${loan.borrower_name}</div>
                    <div class="text-xs text-gray-400">${loan.lender_type} → ${loan.borrower_type}</div>
                </td>
                <td class="px-4 py-3 text-right">${fmt(loan.principal_amount)}</td>
                <td class="px-4 py-3 text-right font-semibold text-teal-700">${fmt(loan.outstanding_balance)}</td>
                <td class="px-4 py-3 text-gray-500 text-xs">${loan.repayment_type === 'emi' ? `EMI (${loan.tenure_months||'-'} mo)` : 'Free-form'}${loan.interest_rate > 0 ? ` @ ${loan.interest_rate}%` : ' (interest-free)'}</td>
                <td class="px-4 py-3"><span class="text-xs px-2 py-0.5 rounded-full ${statusBadge[loan.status]||''}">${loan.status}</span></td>
            </tr>`).join('') || '<tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">No loans found</td></tr>';

        const pag = document.getElementById('list-pagination');
        pag.innerHTML = `<span>Page ${j.page} of ${j.pages} (${j.total} loans)</span>
            <div class="flex gap-2">
                ${j.page > 1 ? `<button onclick="goPage(${j.page-1})" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">Prev</button>` : ''}
                ${j.page < j.pages ? `<button onclick="goPage(${j.page+1})" class="px-3 py-1 bg-teal-600 text-white rounded hover:bg-teal-700">Next</button>` : ''}
            </div>`;
    } finally { setLoading(false); }
}
function goPage(p) { currentPage = p; loadList(); }

async function openDetail(loanSysId) {
    document.getElementById('detailModal').classList.remove('hidden');
    document.getElementById('detail-body').innerHTML = '<div class="text-center py-8 text-gray-400"><i class="fas fa-spinner fa-spin"></i></div>';
    try {
        const r = await fetch(`${API}?action=detail&loan_sys_id=${encodeURIComponent(loanSysId)}`);
        const j = await r.json();
        if (!j.success) return;
        renderDetail(j.loan, j.installments, j.repayments);
    } catch(e) { document.getElementById('detail-body').innerHTML = '<p class="text-red-500 text-sm">Failed to load</p>'; }
}
function closeDetail() { document.getElementById('detailModal').classList.add('hidden'); }

function renderDetail(loan, installments, repayments) {
    const isCompanyInvolved = loan.lender_type === 'company' || loan.borrower_type === 'company';
    let html = `
        <div class="grid grid-cols-2 gap-3 mb-4 text-sm">
            <div><span class="text-gray-500">Lender:</span> <span class="font-medium">${loan.lender_name}</span> <span class="text-xs text-gray-400">(${loan.lender_type})</span></div>
            <div><span class="text-gray-500">Borrower:</span> <span class="font-medium">${loan.borrower_name}</span> <span class="text-xs text-gray-400">(${loan.borrower_type})</span></div>
            <div><span class="text-gray-500">Principal:</span> <span class="font-medium">${fmt(loan.principal_amount)}</span></div>
            <div><span class="text-gray-500">Outstanding:</span> <span class="font-semibold text-teal-700">${fmt(loan.outstanding_balance)}</span></div>
            <div><span class="text-gray-500">Interest:</span> <span class="font-medium">${loan.interest_rate > 0 ? loan.interest_rate + '% (' + loan.interest_method + ')' : 'Interest-free'}</span></div>
            <div><span class="text-gray-500">Status:</span> <span class="text-xs px-2 py-0.5 rounded-full ${statusBadge[loan.status]||''}">${loan.status}</span></div>
        </div>`;

    if (loan.repayment_type === 'emi' && installments.length) {
        html += `<h4 class="text-xs font-semibold text-gray-500 uppercase mb-2 mt-4">Installments</h4>
        <table class="w-full text-xs border border-gray-200 rounded-lg overflow-hidden mb-4">
            <thead class="bg-gray-50"><tr><th class="px-2 py-1.5 text-left">#</th><th class="px-2 py-1.5 text-left">Due</th><th class="px-2 py-1.5 text-right">Amount</th><th class="px-2 py-1.5 text-left">Status</th><th class="px-2 py-1.5"></th></tr></thead>
            <tbody>${installments.map(inst => `
                <tr class="border-t border-gray-100">
                    <td class="px-2 py-1.5">${inst.installment_no}</td>
                    <td class="px-2 py-1.5">${inst.due_date}</td>
                    <td class="px-2 py-1.5 text-right">${fmt(inst.total_due)}</td>
                    <td class="px-2 py-1.5"><span class="text-xs px-1.5 py-0.5 rounded ${inst.status==='paid'?'bg-emerald-100 text-emerald-700':'bg-amber-100 text-amber-700'}">${inst.status}</span></td>
                    <td class="px-2 py-1.5">${inst.status!=='paid' && loan.status==='active' ? `<button onclick="openRepayForm('${loan.sys_id}','${inst.sys_id}',${inst.total_due},${isCompanyInvolved})" class="text-teal-600 hover:underline text-xs">Pay</button>` : ''}</td>
                </tr>`).join('')}</tbody>
        </table>`;
    }

    if (loan.status === 'active') {
        html += `<button onclick="openRepayForm('${loan.sys_id}', '', ${loan.outstanding_balance}, ${isCompanyInvolved})" class="w-full py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-xs font-semibold transition mb-3"><i class="fas fa-plus mr-1"></i>Free-form Repayment</button>`;
    }

    html += `<div id="repay-form-area"></div>`;

    if (repayments.length) {
        html += `<h4 class="text-xs font-semibold text-gray-500 uppercase mb-2 mt-4">Repayment History</h4>
        <table class="w-full text-xs border border-gray-200 rounded-lg overflow-hidden">
            <thead class="bg-gray-50"><tr><th class="px-2 py-1.5 text-left">Date</th><th class="px-2 py-1.5 text-right">Amount</th><th class="px-2 py-1.5 text-left">Method</th></tr></thead>
            <tbody>${repayments.map(rp => `
                <tr class="border-t border-gray-100">
                    <td class="px-2 py-1.5">${(rp.date||'').substring(0,10)}</td>
                    <td class="px-2 py-1.5 text-right">${fmt(rp.amount)}</td>
                    <td class="px-2 py-1.5 uppercase">${rp.payment_method||'—'}</td>
                </tr>`).join('')}</tbody>
        </table>`;
    }

    document.getElementById('detail-body').innerHTML = html;
}

async function loadAccounts() {
    if (_accounts.length) return;
    try { const r = await fetch(API_ALL_ACCOUNTS); const j = await r.json(); _accounts = j.accounts ?? []; } catch(e) {}
}

function openRepayForm(loanSysId, installmentSysId, dueAmount, isCompanyInvolved) {
    const area = document.getElementById('repay-form-area');
    loadAccounts();
    area.innerHTML = `
        <div class="p-3 bg-teal-50 border border-teal-200 rounded-lg mb-3 space-y-2">
            <p class="text-xs font-semibold text-teal-700">Record Repayment</p>
            ${isCompanyInvolved ? `
            <div>
                <label class="block text-[11px] font-medium text-gray-600 mb-1">Own Account</label>
                <div class="relative">
                    <input id="rp_accountSearch" placeholder="Search…" class="f-input text-xs" autocomplete="off" oninput="rpFilterAccount(this.value)" onfocus="rpFilterAccount(this.value)">
                    <ul id="rp_accountDrop" class="absolute w-full bg-white border border-gray-200 rounded-lg mt-1 max-h-32 overflow-auto shadow-xl hidden z-50"></ul>
                </div>
                <input type="hidden" id="rp_accountId">
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-600 mb-1">Payment Method</label>
                <select id="rp_method" onchange="rpToggleInstrument()" class="f-input text-xs">
                    <option value="cash">Cash</option><option value="npsb">NPSB</option><option value="rtgs">RTGS</option>
                    <option value="bftn">BFTN</option><option value="eft">EFT</option><option value="cheque">Cheque</option>
                </select>
            </div>
            <div id="rp_instrumentWrap" class="hidden">
                <label class="block text-[11px] font-medium text-gray-600 mb-1">Cheque/Instrument No.</label>
                <input type="text" id="rp_instrumentNo" class="f-input text-xs">
            </div>` : '<p class="text-[11px] text-gray-400">এই লোনে কোনো company account জড়িত নেই, শুধু রেকর্ড রাখা হবে।</p>'}
            <div>
                <label class="block text-[11px] font-medium text-gray-600 mb-1">Amount ৳</label>
                <input type="number" step="0.01" min="0.01" max="${dueAmount}" id="rp_amount" value="${dueAmount}" class="f-input text-xs">
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-600 mb-1">Date</label>
                <input type="date" id="rp_date" value="${new Date().toISOString().slice(0,10)}" class="f-input text-xs">
            </div>
            <button onclick="submitRepay('${loanSysId}', '${installmentSysId}', ${isCompanyInvolved})" class="w-full py-1.5 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-xs font-semibold transition">Confirm Repayment</button>
        </div>`;
}

function rpToggleInstrument() {
    document.getElementById('rp_instrumentWrap').classList.toggle('hidden', document.getElementById('rp_method').value !== 'cheque');
}
function rpFilterAccount(q) {
    const dd = document.getElementById('rp_accountDrop');
    const v = q.toLowerCase().trim();
    const list = v ? _accounts.filter(a => (a.acc_name||'').toLowerCase().includes(v)) : _accounts.slice(0, 15);
    if (!list.length) { dd.innerHTML = `<li class="px-3 py-2 text-center text-gray-400 text-xs">কিছু পাওয়া যায়নি</li>`; dd.classList.remove('hidden'); return; }
    dd.innerHTML = list.map(a => `<li class="px-3 py-2 cursor-pointer hover:bg-teal-50 border-b last:border-b-0 text-xs" data-id="${a.sys_id}" data-name="${(a.acc_name||'').replace(/"/g,'&quot;')}">${a.acc_name}</li>`).join('');
    dd.classList.remove('hidden');
    dd.querySelectorAll('li[data-id]').forEach(li => li.addEventListener('click', () => {
        document.getElementById('rp_accountSearch').value = li.dataset.name;
        document.getElementById('rp_accountId').value = li.dataset.id;
        dd.classList.add('hidden');
    }));
}

async function submitRepay(loanSysId, installmentSysId, isCompanyInvolved) {
    const amount = parseFloat(document.getElementById('rp_amount').value);
    const date = document.getElementById('rp_date').value;
    if (!amount || amount <= 0) { alert('সঠিক amount দিন'); return; }

    const payload = { loan_sys_id: loanSysId, installment_sys_id: installmentSysId || undefined, amount, date };
    if (isCompanyInvolved) {
        const accountId = document.getElementById('rp_accountId').value;
        if (!accountId) { alert('একটা Account সিলেক্ট করুন'); return; }
        payload.account_id = accountId;
        payload.payment_method = document.getElementById('rp_method').value;
        const instrumentNo = document.getElementById('rp_instrumentNo')?.value.trim();
        if (payload.payment_method === 'cheque' && !instrumentNo) { alert('Cheque number দিন'); return; }
        payload.instrument_no = instrumentNo || undefined;
    }

    try {
        const res = await fetch(API_REPAY, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(payload) });
        const json = await res.json();
        if (json.success) { alert(json.message); openDetail(loanSysId); loadList(); }
        else alert(json.message || 'Failed');
    } catch(e) { alert('Network error'); }
}

document.addEventListener('DOMContentLoaded', loadList);
</script>
</body>
</html>