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
    <title>Pending Gateway Settlements — TravHub</title>
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

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-building-columns text-indigo-600 mr-2"></i>Pending Gateway Settlements</h1>
        <p class="text-sm text-gray-500 mt-1">Client payments confirmed by EPS. Mark each one settled once the money has actually landed in your bank account.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500">Awaiting Settlement</p><p class="text-2xl font-bold text-gray-800 mt-1" id="sum-pending-count">—</p>
        </div>
        <div class="bg-indigo-600 rounded-xl p-4 text-white">
            <p class="text-xs text-indigo-100">Amount Awaiting</p><p class="text-2xl font-bold mt-1" id="sum-pending-amount">—</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <label class="text-xs text-gray-500 block mb-1">Show</label>
            <select id="f-status" onchange="loadList()" class="w-full text-sm border-none p-0 focus:ring-0">
                <option value="pending_settlement">Pending settlement</option>
                <option value="settled">Settled</option>
                <option value="all">Both</option>
            </select>
        </div>
    </div>

    <div id="loadingBar" class="hidden text-center py-8"><i class="fas fa-spinner fa-spin text-2xl text-indigo-500"></i></div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Invoice / Client</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase text-xs">Amount</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">EPS Txn</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Paid On</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody id="list-tbody"></tbody>
            </table>
        </div>
    </div>

</div>
</main>

<!-- Settle Modal -->
<div id="settleModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.45);">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between p-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800 text-sm"><i class="fas fa-check-double mr-2 text-indigo-600"></i>Mark as Settled</h3>
            <button onclick="closeSettle()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-4 space-y-3">
            <input type="hidden" id="s-gp-id">
            <div class="p-3 bg-indigo-50 rounded-lg text-xs text-indigo-700" id="s-summary"></div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Bank account the money landed in</label>
                <div class="relative">
                    <input id="s-account-search" placeholder="Search for an account…" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" autocomplete="off" oninput="filterAccount(this.value)" onfocus="filterAccount(this.value)">
                    <ul id="s-account-drop" class="absolute w-full bg-white border border-gray-200 rounded-lg mt-1 max-h-40 overflow-auto shadow-xl hidden z-50"></ul>
                </div>
                <input type="hidden" id="s-account-id">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Settlement Date</label>
                <input type="date" id="s-date" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <button onclick="submitSettle()" id="s-btn" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold transition">
                <i class="fas fa-check mr-1.5"></i>Confirm Settlement
            </button>
        </div>
    </div>
</div>

<script>
const API_LIST = "<?php echo $ip_port; ?>/api/epsgw/pending-settlements.php";
const API_SETTLE = "<?php echo $ip_port; ?>/api/epsgw/settle.php";
const API_ALL_ACCOUNTS = "<?php echo $ip_port; ?>/api/accounts/all-trxnable-accounts.php";
let _accounts = [];

function fmt(n) { return '৳' + (parseFloat(n)||0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}); }
function esc(s) { return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

async function loadList() {
    document.getElementById('loadingBar').classList.remove('hidden');
    try {
        const status = document.getElementById('f-status').value;
        const r = await fetch(`${API_LIST}?status=${encodeURIComponent(status)}`);
        const j = await r.json();
        if (!j.success) { alert(j.message || 'Failed to load'); return; }

        document.getElementById('sum-pending-count').textContent = j.summary.pending_count || 0;
        document.getElementById('sum-pending-amount').textContent = fmt(j.summary.pending_amount || 0);

        document.getElementById('list-tbody').innerHTML = (j.rows || []).map(row => `
            <tr class="border-t border-gray-100">
                <td class="px-4 py-3">
                    <div class="font-medium text-gray-800">${esc(row.invoice_sys_id)}</div>
                    <div class="text-xs text-gray-400">${esc(row.client_name || row.client_sys_id)}</div>
                </td>
                <td class="px-4 py-3 text-right font-semibold text-gray-800">${fmt(row.amount)}</td>
                <td class="px-4 py-3 text-xs text-gray-500">${esc(row.eps_transaction_id || row.merchant_transaction_id)}</td>
                <td class="px-4 py-3 text-xs text-gray-500">${esc((row.confirmed_at || '').substring(0,16))}</td>
                <td class="px-4 py-3"><span class="text-xs px-2 py-0.5 rounded-full ${row.status === 'settled' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'}">${row.status === 'settled' ? 'Settled' : 'Pending'}</span>${row.status === 'settled' && row.settled_by ? `<div class="text-[10px] text-gray-400 mt-0.5">by ${esc(row.settled_by)}</div>` : ''}</td>
                <td class="px-4 py-3 text-right">${row.status === 'pending_settlement' ? `<button onclick='openSettle(${JSON.stringify({id: row.sys_id, invoice: row.invoice_sys_id, client: row.client_name || row.client_sys_id, amount: row.amount})})' class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-medium">Mark Settled</button>` : ''}</td>
            </tr>`).join('') || '<tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">কিছু পাওয়া যায়নি</td></tr>';
    } finally { document.getElementById('loadingBar').classList.add('hidden'); }
}

async function loadAccounts() {
    if (_accounts.length) return;
    try { const r = await fetch(API_ALL_ACCOUNTS); const j = await r.json(); _accounts = j.accounts ?? []; } catch(e) {}
}

function openSettle(info) {
    loadAccounts();
    document.getElementById('s-gp-id').value = info.id;
    document.getElementById('s-summary').textContent = `Invoice ${info.invoice} — ${info.client} — ${fmt(info.amount)}`;
    document.getElementById('s-account-search').value = '';
    document.getElementById('s-account-id').value = '';
    document.getElementById('s-date').value = new Date().toISOString().slice(0,10);
    document.getElementById('settleModal').classList.remove('hidden');
}
function closeSettle() { document.getElementById('settleModal').classList.add('hidden'); }

async function filterAccount(q) {
    await loadAccounts();
    const dd = document.getElementById('s-account-drop');
    const v = q.toLowerCase().trim();
    const list = v ? _accounts.filter(a => (a.acc_name||'').toLowerCase().includes(v)) : _accounts.slice(0, 15);
    if (!list.length) { dd.innerHTML = `<li class="px-3 py-2 text-center text-gray-400 text-xs">কিছু পাওয়া যায়নি</li>`; dd.classList.remove('hidden'); return; }
    dd.innerHTML = list.map(a => `<li class="px-3 py-2 cursor-pointer hover:bg-indigo-50 border-b last:border-b-0 text-xs" data-id="${esc(a.sys_id)}" data-name="${esc(a.acc_name)}">${esc(a.acc_name)}</li>`).join('');
    dd.classList.remove('hidden');
    dd.querySelectorAll('li[data-id]').forEach(li => li.addEventListener('click', () => {
        document.getElementById('s-account-search').value = li.dataset.name;
        document.getElementById('s-account-id').value = li.dataset.id;
        dd.classList.add('hidden');
    }));
}

async function submitSettle() {
    const gpId = document.getElementById('s-gp-id').value;
    const accountId = document.getElementById('s-account-id').value;
    const date = document.getElementById('s-date').value;
    if (!accountId) { alert('একটা Account সিলেক্ট করুন'); return; }
    if (!confirm('নিশ্চিত? এতে ব্যাংক account-এর balance বেড়ে যাবে এবং এটা আর ফেরানো যাবে না।')) return;

    const btn = document.getElementById('s-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5"></i>Settling…';
    try {
        const res = await fetch(API_SETTLE, {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ gateway_payment_sys_id: gpId, account_id: accountId, settlement_date: date }),
        });
        const json = await res.json();
        if (json.success) { closeSettle(); loadList(); }
        else alert(json.message || (json.errors ? json.errors.join('\n') : 'Failed'));
    } catch(e) { alert('Network error'); }
    finally { btn.disabled = false; btn.innerHTML = '<i class="fas fa-check mr-1.5"></i>Confirm Settlement'; }
}

document.addEventListener('DOMContentLoaded', loadList);
</script>
</body>
</html>