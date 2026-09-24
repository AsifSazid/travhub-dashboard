<?php
// PATH: /pages/show-vendor-ledger.php
//
// Vendor Ledger — full payable/purchase/refund history for ONE vendor,
// across every task/work. This is a FRAGMENT meant to be embedded inside
// show-vendors.php's Accounting tab (via include or an ajax-loaded partial),
// not a standalone page with its own <html>/<head> -- matches the existing
// convention set by pages/sv-accounting.php.
//
// Expects $vendorSysId to be set by the including page. Falls back to
// ?vendor_id= for standalone testing.
if (!isset($vendorSysId)) {
    $vendorSysId = $_GET['vendor_id'] ?? '';
}
if (!isset($ip_port)) {
    $ip_port = ''; // same-origin relative paths when embedded; override if needed
}

// Full accounting access check — this fragment can be included from
// show-vendors.php, but is also directly reachable via ?vendor_id= for
// standalone testing, so it checks for itself rather than trusting the
// including page did.
require_once __DIR__ . '/../server/db_connection.php';
require_once __DIR__ . '/../server/permissions.php';
requireFullAccountingAccess($pdo, false);
?>
<div id="vlWrap" data-vendor-id="<?php echo htmlspecialchars($vendorSysId); ?>">
    <!-- Summary cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
        <div class="rounded-xl p-4 text-white shadow-sm relative overflow-hidden" style="background:linear-gradient(135deg,#f59e0b,#d97706);">
            <i class="fas fa-file-invoice-dollar absolute -right-2 -bottom-2 text-6xl text-white/10"></i>
            <p class="text-xs text-amber-100 font-medium">Outstanding Payable</p>
            <p class="text-2xl font-bold mt-1" id="vl-payable">৳0.00</p>
        </div>
        <div class="rounded-xl p-4 text-white shadow-sm relative overflow-hidden" style="background:linear-gradient(135deg,#10b981,#059669);">
            <i class="fas fa-arrow-up absolute -right-2 -bottom-2 text-6xl text-white/10"></i>
            <p class="text-xs text-emerald-100 font-medium">Total Paid</p>
            <p class="text-2xl font-bold mt-1" id="vl-paid">৳0.00</p>
        </div>
        <div class="rounded-xl p-4 text-white shadow-sm relative overflow-hidden" style="background:linear-gradient(135deg,#14b8a6,#0d9488);">
            <i class="fas fa-undo absolute -right-2 -bottom-2 text-6xl text-white/10"></i>
            <p class="text-xs text-teal-100 font-medium">Refund Pending (from vendor)</p>
            <p class="text-2xl font-bold mt-1" id="vl-refund-pending">৳0.00</p>
        </div>
    </div>

    <div class="flex flex-wrap gap-2 mb-4">
        <button onclick="vlOpenGeneralPay()" class="px-3 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-semibold transition"><i class="fas fa-money-bill-wave mr-1.5"></i>General Payment</button>
        <button onclick="vlOpenDiscount()" class="px-3 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-semibold transition"><i class="fas fa-tags mr-1.5"></i>Discount Received</button>
        <button onclick="vlOpenGratuity()" class="px-3 py-2 bg-pink-600 hover:bg-pink-700 text-white rounded-lg text-xs font-semibold transition"><i class="fas fa-gift mr-1.5"></i>Gratuity Given</button>
    </div>

    <!-- Loans -- deliberately a SEPARATE section, never mixed with purchase/sale payable-receivable above -->
    <div class="sc p-4 bg-white rounded-xl border border-gray-100 mb-4">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-gray-800"><i class="fas fa-hand-holding-dollar mr-1.5 text-teal-500"></i>Loans</h3>
            <a href="loans-create.php" class="px-2.5 py-1 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-xs transition"><i class="fas fa-plus mr-1"></i>New Loan</a>
        </div>
        <div class="grid grid-cols-2 gap-3 mb-3">
            <div class="p-3 bg-teal-50 rounded-lg">
                <p class="text-[11px] text-teal-600">This vendor owes us (borrowed)</p>
                <p class="text-lg font-bold text-teal-700" id="vl-loan-owed-by">—</p>
            </div>
            <div class="p-3 bg-amber-50 rounded-lg">
                <p class="text-[11px] text-amber-600">We owe this vendor (lent to us)</p>
                <p class="text-lg font-bold text-amber-700" id="vl-loan-owed-to">—</p>
            </div>
        </div>
        <div id="vl-loan-list" class="space-y-1.5 text-xs"></div>
    </div>

    <div class="sc p-4 bg-white rounded-xl border border-gray-100">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-gray-800"><i class="fas fa-history mr-1.5 text-amber-500"></i>Transaction History</h3>
            <button onclick="vlLoadEntries()" class="px-2.5 py-1 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg text-xs transition"><i class="fas fa-redo-alt mr-1"></i>Refresh</button>
        </div>
        <div class="overflow-x-auto rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200 text-xs">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 uppercase">Type / Purpose</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 uppercase hidden md:table-cell">Work / Task</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 uppercase">Files</th>
                    </tr>
                </thead>
                <tbody id="vl_tableBody" class="bg-white divide-y divide-gray-100"></tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function() {
    const VENDOR_ID = document.getElementById('vlWrap').dataset.vendorId;
    const API = {
        ledger:        "<?php echo $ip_port; ?>api/financial_entries_v2/party-ledger.php",
        fileServe:     "<?php echo $ip_port; ?>api/file/serve.php",
        payOutstanding:"<?php echo $ip_port; ?>api/financial_entries_v2/pay-outstanding.php",
        payOutstandingGeneral: "<?php echo $ip_port; ?>api/financial_entries_v2/pay-outstanding-general.php",
        refundSettleVendor: "<?php echo $ip_port; ?>api/financial_entries_v2/refund-settle-vendor.php",
        storeDiscount: "<?php echo $ip_port; ?>api/financial_entries_v2/store-discount.php",
        storeGratuity: "<?php echo $ip_port; ?>api/financial_entries_v2/store-gratuity.php",
        allAccounts:   "<?php echo $ip_port; ?>api/accounts/all-trxnable-accounts.php",
    };
    let _vlGroups = [], _vlAccounts = [];

    function escHtml(s) { return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
    function toast(type, msg) {
        // Reuse the host page's toast if it exists, else a minimal fallback.
        if (typeof window.showToast === 'function') { window.showToast(type, msg); return; }
        alert(msg);
    }

    async function vlLoadEntries() {
        if (!VENDOR_ID) { document.getElementById('vl_tableBody').innerHTML = `<tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">No vendor selected</td></tr>`; return; }
        try {
            const res = await fetch(`${API.ledger}?party_type=vendor&party_id=${encodeURIComponent(VENDOR_ID)}`);
            const json = await res.json();
            if (!json.success) { toast('error', json.message || 'Load failed'); return; }
            _vlGroups = json.groups ?? [];
            vlRenderSummary(json.summary ?? {});
            vlRenderTable(_vlGroups);
        } catch (e) { console.error(e); }
    }

    function vlRenderSummary(s) {
        document.getElementById('vl-payable').textContent = `৳${(s.total_payable_open ?? 0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2})}`;
        document.getElementById('vl-paid').textContent     = `৳${(s.total_vendor_payment ?? 0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2})}`;
        document.getElementById('vl-refund-pending').textContent = `৳${(s.vendor_refund_pending ?? 0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2})}`;
    }

    const eventBadge = { purchase:'bg-amber-100 text-amber-700', payment:'bg-emerald-100 text-emerald-700', vendor_refund:'bg-rose-100 text-rose-700', vendor_refund_settle:'bg-teal-100 text-teal-700', other:'bg-gray-100 text-gray-600' };

    function vlRenderTable(groups) {
        const body = document.getElementById('vl_tableBody');
        if (!groups.length) { body.innerHTML = `<tr><td colspan="5" class="px-4 py-6 text-center text-gray-400"><i class="fas fa-inbox text-xl mb-2 block"></i>কোনো transaction নেই</td></tr>`; return; }
        body.innerHTML = groups.map((g, gi) => {
            const legs = g.legs || [];
            let fileCount = 0;
            legs.forEach(l => { try { fileCount += (JSON.parse(l.files_json||'[]')||[]).length; } catch(e){} });
            const amount = g.amount ?? 0, due = g.due ?? 0;
            const workTaskLabel = g.task_title || g.work_title || g.task_sys_id || g.work_sys_id || '—';
            return `
            <tr class="hover:bg-gray-50 transition cursor-pointer border-t-2 border-gray-100" onclick="window._vlToggle(${gi})">
                <td class="px-3 py-2 whitespace-nowrap text-gray-600"><i class="fas fa-chevron-right text-gray-300 text-[10px] mr-1.5 transition-transform" id="vl-chevron-${gi}"></i>${(g.date||'').slice(0,10)}</td>
                <td class="px-3 py-2">
                    <span class="px-1.5 py-0.5 rounded ${eventBadge[g.event_type]||'bg-gray-100 text-gray-600'} text-[10px] font-semibold">${escHtml(g.event_label||'Transaction')}</span>
                    <span class="text-gray-800 ml-1.5">${escHtml(g.purpose||'—')}</span>
                </td>
                <td class="px-3 py-2 text-gray-500 hidden md:table-cell">${escHtml(workTaskLabel)}</td>
                <td class="px-3 py-2 text-right">
                    <div class="font-semibold text-gray-700">৳${amount.toFixed(2)}</div>
                    ${due > 0 ? `<div class="text-[11px] text-rose-500 font-medium">৳${due.toFixed(2)} due</div>` : ''}
                </td>
                <td class="px-3 py-2">${fileCount ? `<i class="fas fa-paperclip text-indigo-400"></i> ${fileCount}` : '—'}</td>
            </tr>
            <tr id="vl-group-${gi}" class="hidden">
                <td colspan="5" class="px-3 pb-3 bg-gray-50">
                    <table class="w-full text-xs border border-gray-200 rounded-lg overflow-hidden bg-white">
                        <thead class="bg-gray-100"><tr>
                            <th class="px-2 py-1.5 text-left font-medium text-gray-500">Account Head</th>
                            <th class="px-2 py-1.5 text-left font-medium text-gray-500">Who</th>
                            <th class="px-2 py-1.5 text-left font-medium text-gray-500">Type</th>
                            <th class="px-2 py-1.5 text-right font-medium text-gray-500">Amount</th>
                            <th class="px-2 py-1.5 text-left font-medium text-gray-500">Files</th>
                        </tr></thead>
                        <tbody>
                            ${legs.map(l => {
                                const isDebit = (l.type||'').toLowerCase() === 'debit';
                                let n = 0; try { n = (JSON.parse(l.files_json||'[]')||[]).length; } catch(e){}
                                return `<tr class="border-t border-gray-100">
                                    <td class="px-2 py-1.5"><span class="px-1.5 py-0.5 rounded bg-gray-100 text-gray-600 text-[10px] font-semibold">${escHtml(l.account_head||'—')}</span></td>
                                    <td class="px-2 py-1.5 text-gray-700">${escHtml(l.user_name||'—')}</td>
                                    <td class="px-2 py-1.5"><span class="text-[10px] font-bold ${isDebit?'text-green-600':'text-red-600'}">${isDebit?'DEBIT':'CREDIT'}</span></td>
                                    <td class="px-2 py-1.5 text-right font-medium">৳${parseFloat(l.amount||0).toFixed(2)}</td>
                                    <td class="px-2 py-1.5">${n ? `<a href="${API.fileServe}?fin_id=${l.sys_id}" target="_blank" class="text-indigo-500 hover:underline" onclick="event.stopPropagation()"><i class="fas fa-paperclip"></i> ${n}</a>` : '—'}</td>
                                </tr>`;
                            }).join('')}
                        </tbody>
                    </table>
                    ${g.payable ? `
                    <div class="mt-2 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-xs font-semibold text-amber-700"><i class="fas fa-hand-holding-usd mr-1"></i>৳${due.toFixed(2)} বাকি</p>
                            <button onclick="window._vlTogglePay(${gi})" class="text-xs font-semibold text-amber-700 hover:text-amber-900"><i class="fas fa-plus-circle mr-1"></i>Pay Now</button>
                        </div>
                        <div id="vl-payform-${gi}" class="hidden space-y-2">
                            <div><label class="block text-[11px] font-medium text-gray-600 mb-1">Own Account</label>
                                <div class="relative" id="vl_payAccountWrap${gi}">
                                    <input id="vl_payAccountSearch${gi}" placeholder="Search for an account…" class="f-input text-xs" autocomplete="off" oninput="window._vlFilterAccount(${gi}, this.value)" onfocus="window._vlFilterAccount(${gi}, this.value)">
                                    <ul id="vl_payAccountDrop${gi}" class="absolute w-full bg-white border border-gray-200 rounded-lg mt-1 max-h-40 overflow-auto shadow-xl hidden z-50"></ul>
                                </div>
                                <input type="hidden" id="vl_payAccountId${gi}">
                            </div>
                            <div><label class="block text-[11px] font-medium text-gray-600 mb-1">Amount ৳</label>
                                <input type="number" step="0.01" min="0.01" max="${due}" id="vl_payAmount${gi}" value="${due.toFixed(2)}" class="f-input text-xs"></div>
                            <div><label class="block text-[11px] font-medium text-gray-600 mb-1">Payment Method</label>
                                <select id="vl_payMethod${gi}" class="f-input text-xs" onchange="window._vlToggleInstrument(${gi})">
                                    <option value="cash">Cash</option><option value="npsb">NPSB</option><option value="rtgs">RTGS</option>
                                    <option value="bftn">BFTN</option><option value="eft">EFT</option><option value="cheque">Cheque</option>
                                </select></div>
                            <div id="vl_payInstrumentWrap${gi}" class="hidden">
                                <label class="block text-[11px] font-medium text-gray-600 mb-1">Cheque/Instrument No.</label>
                                <input type="text" id="vl_payInstrumentNo${gi}" placeholder="Cheque number" class="f-input text-xs"></div>
                            <div><label class="block text-[11px] font-medium text-gray-600 mb-1">Date</label>
                                <input type="date" id="vl_payDate${gi}" value="${new Date().toISOString().slice(0,10)}" class="f-input text-xs"></div>
                            <button onclick="window._vlSubmitPay(${gi}, '${g.transaction_group_id}')" class="w-full py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-semibold transition"><i class="fas fa-check mr-1"></i>Confirm Payment</button>
                        </div>
                    </div>` : ''}
                    ${g.refund_receivable ? `
                    <div class="mt-2 p-3 bg-teal-50 border border-teal-200 rounded-lg">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-xs font-semibold text-teal-700"><i class="fas fa-hourglass-half mr-1"></i>৳${due.toFixed(2)} refund এখনো আসেনি</p>
                            <button onclick="window._vlToggleRefundSettle(${gi})" class="text-xs font-semibold text-teal-700 hover:text-teal-900"><i class="fas fa-plus-circle mr-1"></i>Money Received</button>
                        </div>
                        <div id="vl-refundsettleform-${gi}" class="hidden space-y-2">
                            <div><label class="block text-[11px] font-medium text-gray-600 mb-1">Own Account</label>
                                <div class="relative" id="vl_refundAccountWrap${gi}">
                                    <input id="vl_refundAccountSearch${gi}" placeholder="Search for an account…" class="f-input text-xs" autocomplete="off" oninput="window._vlFilterRefundAccount(${gi}, this.value)" onfocus="window._vlFilterRefundAccount(${gi}, this.value)">
                                    <ul id="vl_refundAccountDrop${gi}" class="absolute w-full bg-white border border-gray-200 rounded-lg mt-1 max-h-40 overflow-auto shadow-xl hidden z-50"></ul>
                                </div>
                                <input type="hidden" id="vl_refundAccountId${gi}">
                            </div>
                            <div><label class="block text-[11px] font-medium text-gray-600 mb-1">Amount ৳</label>
                                <input type="number" step="0.01" min="0.01" max="${due}" id="vl_refundAmount${gi}" value="${due.toFixed(2)}" class="f-input text-xs"></div>
                            <div><label class="block text-[11px] font-medium text-gray-600 mb-1">Payment Method</label>
                                <select id="vl_refundMethod${gi}" class="f-input text-xs" onchange="window._vlToggleRefundInstrument(${gi})">
                                    <option value="cash">Cash</option><option value="npsb">NPSB</option><option value="rtgs">RTGS</option>
                                    <option value="bftn">BFTN</option><option value="eft">EFT</option><option value="cheque">Cheque</option>
                                </select></div>
                            <div id="vl_refundInstrumentWrap${gi}" class="hidden">
                                <label class="block text-[11px] font-medium text-gray-600 mb-1">Cheque/Instrument No.</label>
                                <input type="text" id="vl_refundInstrumentNo${gi}" placeholder="Cheque number" class="f-input text-xs"></div>
                            <div><label class="block text-[11px] font-medium text-gray-600 mb-1">Date</label>
                                <input type="date" id="vl_refundDate${gi}" value="${new Date().toISOString().slice(0,10)}" class="f-input text-xs"></div>
                            <button onclick="window._vlSubmitRefundSettle(${gi}, '${g.transaction_group_id}')" class="w-full py-1.5 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-xs font-semibold transition"><i class="fas fa-check mr-1"></i>Confirm Received</button>
                        </div>
                    </div>` : ''}
                </td>
            </tr>`;
        }).join('');
    }

    window._vlToggle = function(gi) {
        const row = document.getElementById(`vl-group-${gi}`);
        const chevron = document.getElementById(`vl-chevron-${gi}`);
        row.classList.toggle('hidden');
        chevron.style.transform = row.classList.contains('hidden') ? '' : 'rotate(90deg)';
    };
    window._vlTogglePay = function(gi) { document.getElementById(`vl-payform-${gi}`).classList.toggle('hidden'); };
    window._vlToggleRefundSettle = function(gi) { document.getElementById(`vl-refundsettleform-${gi}`).classList.toggle('hidden'); };

    async function _vlLoadAccounts() {
        if (_vlAccounts.length) return;
        try { const r = await fetch(API.allAccounts); const j = await r.json(); _vlAccounts = j.accounts ?? []; } catch(e) {}
    }
    function _vlFilterAccountGeneric(gi, q, dropId, inputId) {
        const dd = document.getElementById(dropId);
        const v = q.toLowerCase().trim();
        const list = v ? _vlAccounts.filter(a => (a.acc_name||'').toLowerCase().includes(v)) : _vlAccounts.slice(0, 15);
        if (!list.length) { dd.innerHTML = `<li class="px-3 py-2 text-center text-gray-400 text-xs">কিছু পাওয়া যায়নি</li>`; dd.classList.remove('hidden'); return; }
        dd.innerHTML = list.map(a => `<li class="px-3 py-2 cursor-pointer hover:bg-amber-50 border-b last:border-b-0 text-xs text-gray-800" data-id="${a.sys_id}" data-name="${escHtml(a.acc_name||a.sys_id)}">${escHtml(a.acc_name ?? a.sys_id)}</li>`).join('');
        dd.classList.remove('hidden');
        dd.querySelectorAll('li[data-id]').forEach(li => li.addEventListener('click', () => {
            document.getElementById(inputId).value = li.dataset.name;
            document.getElementById(inputId.replace('Search','Id')).value = li.dataset.id;
            dd.classList.add('hidden');
        }));
    }
    window._vlFilterAccount = function(gi, q) { _vlLoadAccounts().then(() => _vlFilterAccountGeneric(gi, q, `vl_payAccountDrop${gi}`, `vl_payAccountSearch${gi}`)); };
    window._vlFilterRefundAccount = function(gi, q) { _vlLoadAccounts().then(() => _vlFilterAccountGeneric(gi, q, `vl_refundAccountDrop${gi}`, `vl_refundAccountSearch${gi}`)); };

    window._vlToggleInstrument = function(gi) {
        const method = document.getElementById(`vl_payMethod${gi}`).value;
        document.getElementById(`vl_payInstrumentWrap${gi}`).classList.toggle('hidden', method !== 'cheque');
    };
    window._vlToggleRefundInstrument = function(gi) {
        const method = document.getElementById(`vl_refundMethod${gi}`).value;
        document.getElementById(`vl_refundInstrumentWrap${gi}`).classList.toggle('hidden', method !== 'cheque');
    };

    window._vlSubmitPay = async function(gi, purchaseGroupId) {
        const accountId = document.getElementById(`vl_payAccountId${gi}`).value;
        const amount = parseFloat(document.getElementById(`vl_payAmount${gi}`).value);
        const date = document.getElementById(`vl_payDate${gi}`).value;
        const paymentMethod = document.getElementById(`vl_payMethod${gi}`).value;
        const instrumentNo = document.getElementById(`vl_payInstrumentNo${gi}`)?.value.trim() || '';
        if (!accountId) { toast('error', 'একটা Account সিলেক্ট করুন'); return; }
        if (!amount || amount <= 0) { toast('error', 'সঠিক amount দিন'); return; }
        if (paymentMethod === 'cheque' && !instrumentNo) { toast('error', 'Cheque number দিন'); return; }
        try {
            const res = await fetch(API.payOutstanding, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ purchase_group_id: purchaseGroupId, vendor_id: VENDOR_ID, account_id: accountId, amount, date, payment_method: paymentMethod, instrument_no: instrumentNo || undefined }) });
            const json = await res.json();
            if (json.success) { toast('success', json.message || 'Payment recorded'); vlLoadEntries(); }
            else toast('error', json.message || 'Payment failed');
        } catch(e) { toast('error', 'Network error'); }
    };

    window._vlSubmitRefundSettle = async function(gi, refundGroupId) {
        const accountId = document.getElementById(`vl_refundAccountId${gi}`).value;
        const amount = parseFloat(document.getElementById(`vl_refundAmount${gi}`).value);
        const date = document.getElementById(`vl_refundDate${gi}`).value;
        const paymentMethod = document.getElementById(`vl_refundMethod${gi}`).value;
        const instrumentNo = document.getElementById(`vl_refundInstrumentNo${gi}`)?.value.trim() || '';
        if (!accountId) { toast('error', 'একটা Account সিলেক্ট করুন'); return; }
        if (!amount || amount <= 0) { toast('error', 'সঠিক amount দিন'); return; }
        if (paymentMethod === 'cheque' && !instrumentNo) { toast('error', 'Cheque number দিন'); return; }
        try {
            const res = await fetch(API.refundSettleVendor, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ refund_group_id: refundGroupId, vendor_id: VENDOR_ID, account_id: accountId, amount, date, payment_method: paymentMethod, instrument_no: instrumentNo || undefined }) });
            const json = await res.json();
            if (json.success) { toast('success', json.message || 'Refund settled'); vlLoadEntries(); }
            else toast('error', json.message || 'Settlement failed');
        } catch(e) { toast('error', 'Network error'); }
    };

    // ── General Payment (task-independent) ──────────────────────
    window.vlOpenGeneralPay = function() {
        document.getElementById('vl-generalPayModal').classList.remove('hidden');
        document.getElementById('vl_gpDate').value = new Date().toISOString().slice(0,10);
        _vlLoadAccounts();
    };
    window.vlCloseGeneralPay = function() { document.getElementById('vl-generalPayModal').classList.add('hidden'); };
    window.vlToggleGeneralPayInstrument = function() {
        const method = document.getElementById('vl_gpMethod').value;
        document.getElementById('vl_gpInstrumentWrap').classList.toggle('hidden', method !== 'cheque');
    };
    window.vlFilterGeneralPayAccount = function(q) { _vlFilterAccountGeneric('gp', q, 'vl_gpAccountDrop', 'vl_gpAccountSearch'); };
    window.vlSubmitGeneralPay = async function() {
        const accountId = document.getElementById('vl_gpAccountId').value;
        const amount = parseFloat(document.getElementById('vl_gpAmount').value);
        const date = document.getElementById('vl_gpDate').value;
        const paymentMethod = document.getElementById('vl_gpMethod').value;
        const instrumentNo = document.getElementById('vl_gpInstrumentNo')?.value.trim() || '';
        if (!accountId) { toast('error', 'একটা Account সিলেক্ট করুন'); return; }
        if (!amount || amount <= 0) { toast('error', 'সঠিক amount দিন'); return; }
        if (paymentMethod === 'cheque' && !instrumentNo) { toast('error', 'Cheque number দিন'); return; }
        try {
            const res = await fetch(API.payOutstandingGeneral, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ vendor_id: VENDOR_ID, account_id: accountId, amount, date, payment_method: paymentMethod, instrument_no: instrumentNo || undefined }) });
            const json = await res.json();
            if (json.success) { toast('success', json.message || 'Payment recorded'); vlCloseGeneralPay(); vlLoadEntries(); }
            else toast('error', json.message || 'Payment failed');
        } catch(e) { toast('error', 'Network error'); }
    };

    // ── Discount Received (task-bound) ──────────────────────────
    window.vlOpenDiscount = function() {
        document.getElementById('vl-discountModal').classList.remove('hidden');
        document.getElementById('vl_discountDate').value = new Date().toISOString().slice(0,10);
        _vlLoadTasks();
    };
    window.vlCloseDiscount = function() { document.getElementById('vl-discountModal').classList.add('hidden'); };
    window.vlSubmitDiscount = async function() {
        const taskSel = document.getElementById('vl_discountTask');
        const taskId = taskSel.value, workId = taskSel.selectedOptions[0]?.dataset.workId || '';
        const amount = parseFloat(document.getElementById('vl_discountAmount').value);
        const date = document.getElementById('vl_discountDate').value;
        if (!taskId) { toast('error', 'একটা Task সিলেক্ট করুন'); return; }
        if (!amount || amount <= 0) { toast('error', 'সঠিক amount দিন'); return; }
        try {
            const res = await fetch(API.storeDiscount, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ party_type: 'vendor', party_id: VENDOR_ID, work_id: workId, task_id: taskId, amount, date }) });
            const json = await res.json();
            if (json.success) { toast('success', json.message || 'Discount recorded'); vlCloseDiscount(); vlLoadEntries(); }
            else toast('error', json.message || 'Failed');
        } catch(e) { toast('error', 'Network error'); }
    };

    // ── Gratuity Given (task-bound) ──────────────────────────────
    window.vlOpenGratuity = function() {
        document.getElementById('vl-gratuityModal').classList.remove('hidden');
        document.getElementById('vl_gratuityDate').value = new Date().toISOString().slice(0,10);
        _vlLoadTasks();
        _vlLoadAccounts();
    };
    window.vlCloseGratuity = function() { document.getElementById('vl-gratuityModal').classList.add('hidden'); };
    window.vlToggleGratuityInstrument = function() {
        const method = document.getElementById('vl_gratMethod').value;
        document.getElementById('vl_gratInstrumentWrap').classList.toggle('hidden', method !== 'cheque');
    };
    window.vlFilterGratuityAccount = function(q) { _vlFilterAccountGeneric('grat', q, 'vl_gratAccountDrop', 'vl_gratAccountSearch'); };
    window.vlSubmitGratuity = async function() {
        const taskSel = document.getElementById('vl_gratuityTask');
        const taskId = taskSel.value, workId = taskSel.selectedOptions[0]?.dataset.workId || '';
        const accountId = document.getElementById('vl_gratAccountId').value;
        const amount = parseFloat(document.getElementById('vl_gratuityAmount').value);
        const date = document.getElementById('vl_gratuityDate').value;
        const paymentMethod = document.getElementById('vl_gratMethod').value;
        const instrumentNo = document.getElementById('vl_gratInstrumentNo')?.value.trim() || '';
        if (!taskId) { toast('error', 'একটা Task সিলেক্ট করুন'); return; }
        if (!accountId) { toast('error', 'একটা Account সিলেক্ট করুন'); return; }
        if (!amount || amount <= 0) { toast('error', 'সঠিক amount দিন'); return; }
        if (paymentMethod === 'cheque' && !instrumentNo) { toast('error', 'Cheque number দিন'); return; }
        try {
            const res = await fetch(API.storeGratuity, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ party_type: 'vendor', party_id: VENDOR_ID, work_id: workId, task_id: taskId, account_id: accountId, amount, date, payment_method: paymentMethod, instrument_no: instrumentNo || undefined }) });
            const json = await res.json();
            if (json.success) { toast('success', json.message || 'Gratuity recorded'); vlCloseGratuity(); vlLoadEntries(); }
            else toast('error', json.message || 'Failed');
        } catch(e) { toast('error', 'Network error'); }
    };

    // Task list for this vendor (from party-ledger's groups — every unique task_id/task_title pair seen)
    let _vlTasksLoaded = false;
    async function _vlLoadTasks() {
        if (_vlTasksLoaded) return;
        const tasks = [];
        const seen = new Set();
        (_vlGroups||[]).forEach(g => {
            if (g.task_sys_id && !seen.has(g.task_sys_id)) {
                seen.add(g.task_sys_id);
                tasks.push({ id: g.task_sys_id, label: g.task_title || g.task_sys_id, workId: g.work_sys_id || '' });
            }
        });
        ['vl_discountTask', 'vl_gratuityTask'].forEach(selId => {
            const sel = document.getElementById(selId);
            if (!sel) return;
            sel.innerHTML = '<option value="">Select a task…</option>' +
                tasks.map(t => `<option value="${t.id}" data-work-id="${t.workId}">${t.label}</option>`).join('');
        });
        _vlTasksLoaded = true;
    }

    // ── Loans (separate section — see api/loans/list.php?action=party) ──
    async function _vlLoadLoans() {
        if (!VENDOR_ID) return;
        try {
            const r = await fetch(`<?php echo $ip_port; ?>api/loans/list.php?action=party&party_type=vendor&party_id=${encodeURIComponent(VENDOR_ID)}`);
            const j = await r.json();
            if (!j.success) return;

            document.getElementById('vl-loan-owed-by').textContent = fmt(j.summary.total_owed_by_party);
            document.getElementById('vl-loan-owed-to').textContent = fmt(j.summary.total_owed_to_party);

            const allLoans = [...(j.as_lender||[]), ...(j.as_borrower||[])];
            document.getElementById('vl-loan-list').innerHTML = allLoans.length ? allLoans.map(loan => `
                <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg">
                    <span class="text-gray-600">${loan.lender_id === VENDOR_ID ? 'Lent to' : 'Borrowed from'} ${loan.lender_id === VENDOR_ID ? loan.borrower_name : loan.lender_name}</span>
                    <span class="font-semibold ${loan.status==='active'?'text-teal-700':'text-gray-400'}">${fmt(loan.outstanding_balance)} <span class="text-[10px] font-normal">(${loan.status})</span></span>
                </div>`).join('') : '<p class="text-gray-400 text-center py-2">কোনো loan নেই</p>';
        } catch(e) {}
    }

    window.vlLoadEntries = vlLoadEntries;
    vlLoadEntries();
    _vlLoadLoans();
})();
</script>

<!-- General Payment Modal -->
<div id="vl-generalPayModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.45);">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between p-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800 text-sm"><i class="fas fa-money-bill-wave mr-2 text-amber-600"></i>General Payment</h3>
            <button onclick="vlCloseGeneralPay()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-4 space-y-3">
            <p class="text-xs text-gray-400">নির্দিষ্ট কোনো bill না ধরে, সরাসরি vendor-এর মোট payable কমাবে।</p>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Own Account</label>
                <div class="relative">
                    <input id="vl_gpAccountSearch" placeholder="Search for an account…" class="f-input text-xs" autocomplete="off" oninput="vlFilterGeneralPayAccount(this.value)" onfocus="vlFilterGeneralPayAccount(this.value)">
                    <ul id="vl_gpAccountDrop" class="absolute w-full bg-white border border-gray-200 rounded-lg mt-1 max-h-40 overflow-auto shadow-xl hidden z-50"></ul>
                </div>
                <input type="hidden" id="vl_gpAccountId">
            </div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Amount ৳</label>
                <input type="number" step="0.01" min="0.01" id="vl_gpAmount" class="f-input text-xs"></div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Payment Method</label>
                <select id="vl_gpMethod" onchange="vlToggleGeneralPayInstrument()" class="f-input text-xs">
                    <option value="cash">Cash</option><option value="npsb">NPSB</option><option value="rtgs">RTGS</option>
                    <option value="bftn">BFTN</option><option value="eft">EFT</option><option value="cheque">Cheque</option>
                </select></div>
            <div id="vl_gpInstrumentWrap" class="hidden">
                <label class="block text-xs font-medium text-gray-700 mb-1">Cheque/Instrument No.</label>
                <input type="text" id="vl_gpInstrumentNo" class="f-input text-xs"></div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Date</label>
                <input type="date" id="vl_gpDate" value="" class="f-input text-xs"></div>
            <button onclick="vlSubmitGeneralPay()" class="w-full py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-sm font-semibold transition"><i class="fas fa-check mr-1.5"></i>Confirm Payment</button>
        </div>
    </div>
</div>

<!-- Discount Received Modal -->
<div id="vl-discountModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.45);">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between p-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800 text-sm"><i class="fas fa-tags mr-2 text-purple-600"></i>Discount Received</h3>
            <button onclick="vlCloseDiscount()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-4 space-y-3">
            <p class="text-xs text-gray-400">Discount অবশ্যই একটা Task-এর সাথে যুক্ত করতে হবে। এতে কোনো টাকা movement হয় না।</p>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Task <span class="text-red-500">*</span></label>
                <select id="vl_discountTask" class="f-input text-xs"><option value="">Select a task…</option></select></div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Amount ৳</label>
                <input type="number" step="0.01" min="0.01" id="vl_discountAmount" class="f-input text-xs"></div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Date</label>
                <input type="date" id="vl_discountDate" value="" class="f-input text-xs"></div>
            <button onclick="vlSubmitDiscount()" class="w-full py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-sm font-semibold transition"><i class="fas fa-check mr-1.5"></i>Record Discount</button>
        </div>
    </div>
</div>

<!-- Gratuity Given Modal -->
<div id="vl-gratuityModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.45);">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between p-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800 text-sm"><i class="fas fa-gift mr-2 text-pink-600"></i>Gratuity Given</h3>
            <button onclick="vlCloseGratuity()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-4 space-y-3">
            <p class="text-xs text-gray-400">Gratuity অবশ্যই একটা Task-এর সাথে যুক্ত করতে হবে। এটা প্রকৃত টাকা movement।</p>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Task <span class="text-red-500">*</span></label>
                <select id="vl_gratuityTask" class="f-input text-xs"><option value="">Select a task…</option></select></div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Own Account</label>
                <div class="relative">
                    <input id="vl_gratAccountSearch" placeholder="Search for an account…" class="f-input text-xs" autocomplete="off" oninput="vlFilterGratuityAccount(this.value)" onfocus="vlFilterGratuityAccount(this.value)">
                    <ul id="vl_gratAccountDrop" class="absolute w-full bg-white border border-gray-200 rounded-lg mt-1 max-h-40 overflow-auto shadow-xl hidden z-50"></ul>
                </div>
                <input type="hidden" id="vl_gratAccountId"></div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Amount ৳</label>
                <input type="number" step="0.01" min="0.01" id="vl_gratuityAmount" class="f-input text-xs"></div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Payment Method</label>
                <select id="vl_gratMethod" onchange="vlToggleGratuityInstrument()" class="f-input text-xs">
                    <option value="cash">Cash</option><option value="npsb">NPSB</option><option value="rtgs">RTGS</option>
                    <option value="bftn">BFTN</option><option value="eft">EFT</option><option value="cheque">Cheque</option>
                </select></div>
            <div id="vl_gratInstrumentWrap" class="hidden">
                <label class="block text-xs font-medium text-gray-700 mb-1">Cheque/Instrument No.</label>
                <input type="text" id="vl_gratInstrumentNo" class="f-input text-xs"></div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Date</label>
                <input type="date" id="vl_gratuityDate" value="" class="f-input text-xs"></div>
            <button onclick="vlSubmitGratuity()" class="w-full py-2.5 bg-pink-600 hover:bg-pink-700 text-white rounded-xl text-sm font-semibold transition"><i class="fas fa-check mr-1.5"></i>Record Gratuity</button>
        </div>
    </div>
</div>