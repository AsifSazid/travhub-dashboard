<?php
// PATH: /pages/show-client-ledger.php
//
// Client Ledger — full receivable/sale/refund history for ONE client,
// across every task/work. FRAGMENT (like show-vendor-ledger.php), meant to
// be embedded inside show-clients.php's Accounting tab.
//
// Expects $clientSysId to be set by the including page. Falls back to
// ?client_id= for standalone testing.
if (!isset($clientSysId)) {
    $clientSysId = $_GET['client_id'] ?? '';
}
if (!isset($ip_port)) {
    $ip_port = '';
}

require_once __DIR__ . '/../server/db_connection.php';
require_once __DIR__ . '/../server/permissions.php';
requireFullAccountingAccess($pdo, false);
?>
<div id="clWrap" data-client-id="<?php echo htmlspecialchars($clientSysId); ?>">
    <!-- Summary cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
        <div class="rounded-xl p-4 text-white shadow-sm relative overflow-hidden" style="background:linear-gradient(135deg,#6366f1,#4f46e5);">
            <i class="fas fa-file-invoice-dollar absolute -right-2 -bottom-2 text-6xl text-white/10"></i>
            <p class="text-xs text-indigo-100 font-medium">Outstanding Receivable</p>
            <p class="text-2xl font-bold mt-1" id="cl-receivable">৳0.00</p>
        </div>
        <div class="rounded-xl p-4 text-white shadow-sm relative overflow-hidden" style="background:linear-gradient(135deg,#0ea5e9,#0284c7);">
            <i class="fas fa-arrow-down absolute -right-2 -bottom-2 text-6xl text-white/10"></i>
            <p class="text-xs text-sky-100 font-medium">Total Received</p>
            <p class="text-2xl font-bold mt-1" id="cl-received">৳0.00</p>
        </div>
        <div class="rounded-xl p-4 text-white shadow-sm relative overflow-hidden" style="background:linear-gradient(135deg,#f97316,#ea580c);">
            <i class="fas fa-undo absolute -right-2 -bottom-2 text-6xl text-white/10"></i>
            <p class="text-xs text-orange-100 font-medium">Refund Pending (to client)</p>
            <p class="text-2xl font-bold mt-1" id="cl-refund-pending">৳0.00</p>
        </div>
    </div>

    <div class="flex flex-wrap gap-2 mb-4">
        <button onclick="clOpenGeneralReceive()" class="px-3 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-lg text-xs font-semibold transition"><i class="fas fa-hand-holding-dollar mr-1.5"></i>General Receive</button>
        <button onclick="clOpenAdvance()" class="px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold transition"><i class="fas fa-piggy-bank mr-1.5"></i>Advance</button>
        <button onclick="clOpenDiscount()" class="px-3 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-semibold transition"><i class="fas fa-tags mr-1.5"></i>Discount Given</button>
        <button onclick="clOpenGratuity()" class="px-3 py-2 bg-pink-600 hover:bg-pink-700 text-white rounded-lg text-xs font-semibold transition"><i class="fas fa-gift mr-1.5"></i>Gratuity Received</button>
    </div>

    <!-- Loans -- deliberately a SEPARATE section, never mixed with purchase/sale payable-receivable above -->
    <div class="sc p-4 bg-white rounded-xl border border-gray-100 mb-4">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-gray-800"><i class="fas fa-hand-holding-dollar mr-1.5 text-teal-500"></i>Loans</h3>
            <a href="loans-create.php" class="px-2.5 py-1 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-xs transition"><i class="fas fa-plus mr-1"></i>New Loan</a>
        </div>
        <div class="grid grid-cols-2 gap-3 mb-3">
            <div class="p-3 bg-teal-50 rounded-lg">
                <p class="text-[11px] text-teal-600">This client owes us (borrowed)</p>
                <p class="text-lg font-bold text-teal-700" id="cl-loan-owed-by">—</p>
            </div>
            <div class="p-3 bg-amber-50 rounded-lg">
                <p class="text-[11px] text-amber-600">We owe this client (lent to us)</p>
                <p class="text-lg font-bold text-amber-700" id="cl-loan-owed-to">—</p>
            </div>
        </div>
        <div id="cl-loan-list" class="space-y-1.5 text-xs"></div>
    </div>

    <div class="sc p-4 bg-white rounded-xl border border-gray-100">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-gray-800"><i class="fas fa-history mr-1.5 text-indigo-500"></i>Transaction History</h3>
            <button onclick="clLoadEntries()" class="px-2.5 py-1 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg text-xs transition"><i class="fas fa-redo-alt mr-1"></i>Refresh</button>
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
                <tbody id="cl_tableBody" class="bg-white divide-y divide-gray-100"></tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function() {
    const CLIENT_ID = document.getElementById('clWrap').dataset.clientId;
    const API = {
        ledger:        "<?php echo $ip_port; ?>api/financial_entries_v2/party-ledger.php",
        fileServe:     "<?php echo $ip_port; ?>api/file/serve.php",
        receiveOutstanding: "<?php echo $ip_port; ?>api/financial_entries_v2/receive-outstanding.php",
        receiveOutstandingGeneral: "<?php echo $ip_port; ?>api/financial_entries_v2/receive-outstanding-general.php",
        refundSettleClient: "<?php echo $ip_port; ?>api/financial_entries_v2/refund-settle-client.php",
        storeAdvance:  "<?php echo $ip_port; ?>api/financial_entries_v2/store-advance.php",
        storeDiscount: "<?php echo $ip_port; ?>api/financial_entries_v2/store-discount.php",
        storeGratuity: "<?php echo $ip_port; ?>api/financial_entries_v2/store-gratuity.php",
        allAccounts:   "<?php echo $ip_port; ?>api/accounts/all-trxnable-accounts.php",
    };
    let _clGroups = [], _clAccounts = [];

    function escHtml(s) { return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
    function toast(type, msg) {
        if (typeof window.showToast === 'function') { window.showToast(type, msg); return; }
        alert(msg);
    }

    async function clLoadEntries() {
        if (!CLIENT_ID) { document.getElementById('cl_tableBody').innerHTML = `<tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">No client selected</td></tr>`; return; }
        try {
            const res = await fetch(`${API.ledger}?party_type=client&party_id=${encodeURIComponent(CLIENT_ID)}`);
            const json = await res.json();
            if (!json.success) { toast('error', json.message || 'Load failed'); return; }
            _clGroups = json.groups ?? [];
            clRenderSummary(json.summary ?? {});
            clRenderTable(_clGroups);
        } catch (e) { console.error(e); }
    }

    function clRenderSummary(s) {
        document.getElementById('cl-receivable').textContent = `৳${(s.total_receivable_open ?? 0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2})}`;
        document.getElementById('cl-received').textContent   = `৳${(s.total_deposit ?? 0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2})}`;
        document.getElementById('cl-refund-pending').textContent = `৳${(s.client_refund_pending ?? 0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2})}`;
    }

    const eventBadge = { sale:'bg-indigo-100 text-indigo-700', receive:'bg-sky-100 text-sky-700', client_refund:'bg-rose-100 text-rose-700', client_refund_settle:'bg-orange-100 text-orange-700', other:'bg-gray-100 text-gray-600' };

    function clRenderTable(groups) {
        const body = document.getElementById('cl_tableBody');
        if (!groups.length) { body.innerHTML = `<tr><td colspan="5" class="px-4 py-6 text-center text-gray-400"><i class="fas fa-inbox text-xl mb-2 block"></i>কোনো transaction নেই</td></tr>`; return; }
        body.innerHTML = groups.map((g, gi) => {
            const legs = g.legs || [];
            let fileCount = 0;
            legs.forEach(l => { try { fileCount += (JSON.parse(l.files_json||'[]')||[]).length; } catch(e){} });
            const amount = g.amount ?? 0, due = g.due ?? 0;
            const workTaskLabel = g.task_title || g.work_title || g.task_sys_id || g.work_sys_id || '—';
            return `
            <tr class="hover:bg-gray-50 transition cursor-pointer border-t-2 border-gray-100" onclick="window._clToggle(${gi})">
                <td class="px-3 py-2 whitespace-nowrap text-gray-600"><i class="fas fa-chevron-right text-gray-300 text-[10px] mr-1.5 transition-transform" id="cl-chevron-${gi}"></i>${(g.date||'').slice(0,10)}</td>
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
            <tr id="cl-group-${gi}" class="hidden">
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
                    ${g.receivable ? `
                    <div class="mt-2 p-3 bg-sky-50 border border-sky-200 rounded-lg">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-xs font-semibold text-sky-700"><i class="fas fa-hand-holding-usd mr-1"></i>৳${due.toFixed(2)} বাকি</p>
                            <button onclick="window._clToggleReceive(${gi})" class="text-xs font-semibold text-sky-700 hover:text-sky-900"><i class="fas fa-plus-circle mr-1"></i>Receive Now</button>
                        </div>
                        <div id="cl-receiveform-${gi}" class="hidden space-y-2">
                            <div><label class="block text-[11px] font-medium text-gray-600 mb-1">Own Account</label>
                                <div class="relative" id="cl_receiveAccountWrap${gi}">
                                    <input id="cl_receiveAccountSearch${gi}" placeholder="Search for an account…" class="f-input text-xs" autocomplete="off" oninput="window._clFilterAccount(${gi}, this.value)" onfocus="window._clFilterAccount(${gi}, this.value)">
                                    <ul id="cl_receiveAccountDrop${gi}" class="absolute w-full bg-white border border-gray-200 rounded-lg mt-1 max-h-40 overflow-auto shadow-xl hidden z-50"></ul>
                                </div>
                                <input type="hidden" id="cl_receiveAccountId${gi}">
                            </div>
                            <div><label class="block text-[11px] font-medium text-gray-600 mb-1">Amount ৳</label>
                                <input type="number" step="0.01" min="0.01" max="${due}" id="cl_receiveAmount${gi}" value="${due.toFixed(2)}" class="f-input text-xs"></div>
                            <div><label class="block text-[11px] font-medium text-gray-600 mb-1">Payment Method</label>
                                <select id="cl_receiveMethod${gi}" class="f-input text-xs" onchange="window._clToggleInstrument(${gi})">
                                    <option value="cash">Cash</option><option value="npsb">NPSB</option><option value="rtgs">RTGS</option>
                                    <option value="bftn">BFTN</option><option value="eft">EFT</option><option value="cheque">Cheque</option>
                                </select></div>
                            <div id="cl_receiveInstrumentWrap${gi}" class="hidden">
                                <label class="block text-[11px] font-medium text-gray-600 mb-1">Cheque/Instrument No.</label>
                                <input type="text" id="cl_receiveInstrumentNo${gi}" placeholder="Cheque number" class="f-input text-xs"></div>
                            <div><label class="block text-[11px] font-medium text-gray-600 mb-1">Date</label>
                                <input type="date" id="cl_receiveDate${gi}" value="${new Date().toISOString().slice(0,10)}" class="f-input text-xs"></div>
                            <button onclick="window._clSubmitReceive(${gi}, '${g.transaction_group_id}')" class="w-full py-1.5 bg-sky-600 hover:bg-sky-700 text-white rounded-lg text-xs font-semibold transition"><i class="fas fa-check mr-1"></i>Confirm Receive</button>
                        </div>
                    </div>` : ''}
                    ${g.refund_payable ? `
                    <div class="mt-2 p-3 bg-orange-50 border border-orange-200 rounded-lg">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-xs font-semibold text-orange-700"><i class="fas fa-hourglass-half mr-1"></i>৳${due.toFixed(2)} refund এখনো দেওয়া হয়নি</p>
                            <button onclick="window._clToggleRefundSettle(${gi})" class="text-xs font-semibold text-orange-700 hover:text-orange-900"><i class="fas fa-plus-circle mr-1"></i>Money Paid</button>
                        </div>
                        <div id="cl-refundsettleform-${gi}" class="hidden space-y-2">
                            <div><label class="block text-[11px] font-medium text-gray-600 mb-1">Own Account</label>
                                <div class="relative" id="cl_refundAccountWrap${gi}">
                                    <input id="cl_refundAccountSearch${gi}" placeholder="Search for an account…" class="f-input text-xs" autocomplete="off" oninput="window._clFilterRefundAccount(${gi}, this.value)" onfocus="window._clFilterRefundAccount(${gi}, this.value)">
                                    <ul id="cl_refundAccountDrop${gi}" class="absolute w-full bg-white border border-gray-200 rounded-lg mt-1 max-h-40 overflow-auto shadow-xl hidden z-50"></ul>
                                </div>
                                <input type="hidden" id="cl_refundAccountId${gi}">
                            </div>
                            <div><label class="block text-[11px] font-medium text-gray-600 mb-1">Amount ৳</label>
                                <input type="number" step="0.01" min="0.01" max="${due}" id="cl_refundAmount${gi}" value="${due.toFixed(2)}" class="f-input text-xs"></div>
                            <div><label class="block text-[11px] font-medium text-gray-600 mb-1">Payment Method</label>
                                <select id="cl_refundMethod${gi}" class="f-input text-xs" onchange="window._clToggleRefundInstrument(${gi})">
                                    <option value="cash">Cash</option><option value="npsb">NPSB</option><option value="rtgs">RTGS</option>
                                    <option value="bftn">BFTN</option><option value="eft">EFT</option><option value="cheque">Cheque</option>
                                </select></div>
                            <div id="cl_refundInstrumentWrap${gi}" class="hidden">
                                <label class="block text-[11px] font-medium text-gray-600 mb-1">Cheque/Instrument No.</label>
                                <input type="text" id="cl_refundInstrumentNo${gi}" placeholder="Cheque number" class="f-input text-xs"></div>
                            <div><label class="block text-[11px] font-medium text-gray-600 mb-1">Date</label>
                                <input type="date" id="cl_refundDate${gi}" value="${new Date().toISOString().slice(0,10)}" class="f-input text-xs"></div>
                            <button onclick="window._clSubmitRefundSettle(${gi}, '${g.transaction_group_id}')" class="w-full py-1.5 bg-orange-600 hover:bg-orange-700 text-white rounded-lg text-xs font-semibold transition"><i class="fas fa-check mr-1"></i>Confirm Paid</button>
                        </div>
                    </div>` : ''}
                </td>
            </tr>`;
        }).join('');
    }

    window._clToggle = function(gi) {
        const row = document.getElementById(`cl-group-${gi}`);
        const chevron = document.getElementById(`cl-chevron-${gi}`);
        row.classList.toggle('hidden');
        chevron.style.transform = row.classList.contains('hidden') ? '' : 'rotate(90deg)';
    };
    window._clToggleReceive = function(gi) { document.getElementById(`cl-receiveform-${gi}`).classList.toggle('hidden'); };
    window._clToggleRefundSettle = function(gi) { document.getElementById(`cl-refundsettleform-${gi}`).classList.toggle('hidden'); };

    async function _clLoadAccounts() {
        if (_clAccounts.length) return;
        try { const r = await fetch(API.allAccounts); const j = await r.json(); _clAccounts = j.accounts ?? []; } catch(e) {}
    }
    function _clFilterAccountGeneric(gi, q, dropId, inputId) {
        const dd = document.getElementById(dropId);
        const v = q.toLowerCase().trim();
        const list = v ? _clAccounts.filter(a => (a.acc_name||'').toLowerCase().includes(v)) : _clAccounts.slice(0, 15);
        if (!list.length) { dd.innerHTML = `<li class="px-3 py-2 text-center text-gray-400 text-xs">কিছু পাওয়া যায়নি</li>`; dd.classList.remove('hidden'); return; }
        dd.innerHTML = list.map(a => `<li class="px-3 py-2 cursor-pointer hover:bg-sky-50 border-b last:border-b-0 text-xs text-gray-800" data-id="${a.sys_id}" data-name="${escHtml(a.acc_name||a.sys_id)}">${escHtml(a.acc_name ?? a.sys_id)}</li>`).join('');
        dd.classList.remove('hidden');
        dd.querySelectorAll('li[data-id]').forEach(li => li.addEventListener('click', () => {
            document.getElementById(inputId).value = li.dataset.name;
            document.getElementById(inputId.replace('Search','Id')).value = li.dataset.id;
            dd.classList.add('hidden');
        }));
    }
    window._clFilterAccount = function(gi, q) { _clLoadAccounts().then(() => _clFilterAccountGeneric(gi, q, `cl_receiveAccountDrop${gi}`, `cl_receiveAccountSearch${gi}`)); };
    window._clFilterRefundAccount = function(gi, q) { _clLoadAccounts().then(() => _clFilterAccountGeneric(gi, q, `cl_refundAccountDrop${gi}`, `cl_refundAccountSearch${gi}`)); };

    window._clToggleInstrument = function(gi) {
        const method = document.getElementById(`cl_receiveMethod${gi}`).value;
        document.getElementById(`cl_receiveInstrumentWrap${gi}`).classList.toggle('hidden', method !== 'cheque');
    };
    window._clToggleRefundInstrument = function(gi) {
        const method = document.getElementById(`cl_refundMethod${gi}`).value;
        document.getElementById(`cl_refundInstrumentWrap${gi}`).classList.toggle('hidden', method !== 'cheque');
    };

    window._clSubmitReceive = async function(gi, saleGroupId) {
        const accountId = document.getElementById(`cl_receiveAccountId${gi}`).value;
        const amount = parseFloat(document.getElementById(`cl_receiveAmount${gi}`).value);
        const date = document.getElementById(`cl_receiveDate${gi}`).value;
        const paymentMethod = document.getElementById(`cl_receiveMethod${gi}`).value;
        const instrumentNo = document.getElementById(`cl_receiveInstrumentNo${gi}`)?.value.trim() || '';
        if (!accountId) { toast('error', 'একটা Account সিলেক্ট করুন'); return; }
        if (!amount || amount <= 0) { toast('error', 'সঠিক amount দিন'); return; }
        if (paymentMethod === 'cheque' && !instrumentNo) { toast('error', 'Cheque number দিন'); return; }
        try {
            const res = await fetch(API.receiveOutstanding, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ sale_group_id: saleGroupId, client_id: CLIENT_ID, account_id: accountId, amount, date, payment_method: paymentMethod, instrument_no: instrumentNo || undefined }) });
            const json = await res.json();
            if (json.success) { toast('success', json.message || 'Receive recorded'); clLoadEntries(); }
            else toast('error', json.message || 'Receive failed');
        } catch(e) { toast('error', 'Network error'); }
    };

    window._clSubmitRefundSettle = async function(gi, refundGroupId) {
        const accountId = document.getElementById(`cl_refundAccountId${gi}`).value;
        const amount = parseFloat(document.getElementById(`cl_refundAmount${gi}`).value);
        const date = document.getElementById(`cl_refundDate${gi}`).value;
        const paymentMethod = document.getElementById(`cl_refundMethod${gi}`).value;
        const instrumentNo = document.getElementById(`cl_refundInstrumentNo${gi}`)?.value.trim() || '';
        if (!accountId) { toast('error', 'একটা Account সিলেক্ট করুন'); return; }
        if (!amount || amount <= 0) { toast('error', 'সঠিক amount দিন'); return; }
        if (paymentMethod === 'cheque' && !instrumentNo) { toast('error', 'Cheque number দিন'); return; }
        try {
            const res = await fetch(API.refundSettleClient, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ refund_group_id: refundGroupId, client_id: CLIENT_ID, account_id: accountId, amount, date, payment_method: paymentMethod, instrument_no: instrumentNo || undefined }) });
            const json = await res.json();
            if (json.success) { toast('success', json.message || 'Refund settled'); clLoadEntries(); }
            else toast('error', json.message || 'Settlement failed');
        } catch(e) { toast('error', 'Network error'); }
    };

    // ── General Receive (task-independent) ──────────────────────
    window.clOpenGeneralReceive = function() {
        document.getElementById('cl-generalReceiveModal').classList.remove('hidden');
        document.getElementById('cl_grDate').value = new Date().toISOString().slice(0,10);
        _clLoadAccounts();
    };
    window.clCloseGeneralReceive = function() { document.getElementById('cl-generalReceiveModal').classList.add('hidden'); };
    window.clToggleGeneralReceiveInstrument = function() {
        const method = document.getElementById('cl_grMethod').value;
        document.getElementById('cl_grInstrumentWrap').classList.toggle('hidden', method !== 'cheque');
    };
    window.clFilterGeneralReceiveAccount = function(q) { _clFilterAccountGeneric('gr', q, 'cl_grAccountDrop', 'cl_grAccountSearch'); };
    window.clSubmitGeneralReceive = async function() {
        const accountId = document.getElementById('cl_grAccountId').value;
        const amount = parseFloat(document.getElementById('cl_grAmount').value);
        const date = document.getElementById('cl_grDate').value;
        const paymentMethod = document.getElementById('cl_grMethod').value;
        const instrumentNo = document.getElementById('cl_grInstrumentNo')?.value.trim() || '';
        if (!accountId) { toast('error', 'একটা Account সিলেক্ট করুন'); return; }
        if (!amount || amount <= 0) { toast('error', 'সঠিক amount দিন'); return; }
        if (paymentMethod === 'cheque' && !instrumentNo) { toast('error', 'Cheque number দিন'); return; }
        try {
            const res = await fetch(API.receiveOutstandingGeneral, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ client_id: CLIENT_ID, account_id: accountId, amount, date, payment_method: paymentMethod, instrument_no: instrumentNo || undefined }) });
            const json = await res.json();
            if (json.success) { toast('success', json.message || 'Receive recorded'); clCloseGeneralReceive(); clLoadEntries(); }
            else toast('error', json.message || 'Failed');
        } catch(e) { toast('error', 'Network error'); }
    };

    // ── Advance (task-bound) ──────────────────────────────────────
    window.clOpenAdvance = function() {
        document.getElementById('cl-advanceModal').classList.remove('hidden');
        document.getElementById('cl_advanceDate').value = new Date().toISOString().slice(0,10);
        _clLoadTasks();
        _clLoadAccounts();
    };
    window.clCloseAdvance = function() { document.getElementById('cl-advanceModal').classList.add('hidden'); };
    window.clToggleAdvanceInstrument = function() {
        const method = document.getElementById('cl_advMethod').value;
        document.getElementById('cl_advInstrumentWrap').classList.toggle('hidden', method !== 'cheque');
    };
    window.clFilterAdvanceAccount = function(q) { _clFilterAccountGeneric('adv', q, 'cl_advAccountDrop', 'cl_advAccountSearch'); };
    window.clSubmitAdvance = async function() {
        const taskSel = document.getElementById('cl_advanceTask');
        const taskId = taskSel.value, workId = taskSel.selectedOptions[0]?.dataset.workId || '';
        const accountId = document.getElementById('cl_advAccountId').value;
        const amount = parseFloat(document.getElementById('cl_advanceAmount').value);
        const date = document.getElementById('cl_advanceDate').value;
        const paymentMethod = document.getElementById('cl_advMethod').value;
        const instrumentNo = document.getElementById('cl_advInstrumentNo')?.value.trim() || '';
        if (!taskId) { toast('error', 'একটা Task সিলেক্ট করুন'); return; }
        if (!accountId) { toast('error', 'একটা Account সিলেক্ট করুন'); return; }
        if (!amount || amount <= 0) { toast('error', 'সঠিক amount দিন'); return; }
        if (paymentMethod === 'cheque' && !instrumentNo) { toast('error', 'Cheque number দিন'); return; }
        try {
            const res = await fetch(API.storeAdvance, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ client_id: CLIENT_ID, work_id: workId, task_id: taskId, account_id: accountId, amount, date, payment_method: paymentMethod, instrument_no: instrumentNo || undefined }) });
            const json = await res.json();
            if (json.success) { toast('success', json.message || 'Advance recorded'); clCloseAdvance(); clLoadEntries(); }
            else toast('error', json.message || 'Failed');
        } catch(e) { toast('error', 'Network error'); }
    };

    // ── Discount Given (task-bound) ──────────────────────────────
    window.clOpenDiscount = function() {
        document.getElementById('cl-discountModal').classList.remove('hidden');
        document.getElementById('cl_discountDate').value = new Date().toISOString().slice(0,10);
        _clLoadTasks();
    };
    window.clCloseDiscount = function() { document.getElementById('cl-discountModal').classList.add('hidden'); };
    window.clSubmitDiscount = async function() {
        const taskSel = document.getElementById('cl_discountTask');
        const taskId = taskSel.value, workId = taskSel.selectedOptions[0]?.dataset.workId || '';
        const amount = parseFloat(document.getElementById('cl_discountAmount').value);
        const date = document.getElementById('cl_discountDate').value;
        if (!taskId) { toast('error', 'একটা Task সিলেক্ট করুন'); return; }
        if (!amount || amount <= 0) { toast('error', 'সঠিক amount দিন'); return; }
        try {
            const res = await fetch(API.storeDiscount, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ party_type: 'client', party_id: CLIENT_ID, work_id: workId, task_id: taskId, amount, date }) });
            const json = await res.json();
            if (json.success) { toast('success', json.message || 'Discount recorded'); clCloseDiscount(); clLoadEntries(); }
            else toast('error', json.message || 'Failed');
        } catch(e) { toast('error', 'Network error'); }
    };

    // ── Gratuity Received (task-bound) ────────────────────────────
    window.clOpenGratuity = function() {
        document.getElementById('cl-gratuityModal').classList.remove('hidden');
        document.getElementById('cl_gratuityDate').value = new Date().toISOString().slice(0,10);
        _clLoadTasks();
        _clLoadAccounts();
    };
    window.clCloseGratuity = function() { document.getElementById('cl-gratuityModal').classList.add('hidden'); };
    window.clToggleGratuityInstrument = function() {
        const method = document.getElementById('cl_gratMethod').value;
        document.getElementById('cl_gratInstrumentWrap').classList.toggle('hidden', method !== 'cheque');
    };
    window.clFilterGratuityAccount = function(q) { _clFilterAccountGeneric('grat', q, 'cl_gratAccountDrop', 'cl_gratAccountSearch'); };
    window.clSubmitGratuity = async function() {
        const taskSel = document.getElementById('cl_gratuityTask');
        const taskId = taskSel.value, workId = taskSel.selectedOptions[0]?.dataset.workId || '';
        const accountId = document.getElementById('cl_gratAccountId').value;
        const amount = parseFloat(document.getElementById('cl_gratuityAmount').value);
        const date = document.getElementById('cl_gratuityDate').value;
        const paymentMethod = document.getElementById('cl_gratMethod').value;
        const instrumentNo = document.getElementById('cl_gratInstrumentNo')?.value.trim() || '';
        if (!taskId) { toast('error', 'একটা Task সিলেক্ট করুন'); return; }
        if (!accountId) { toast('error', 'একটা Account সিলেক্ট করুন'); return; }
        if (!amount || amount <= 0) { toast('error', 'সঠিক amount দিন'); return; }
        if (paymentMethod === 'cheque' && !instrumentNo) { toast('error', 'Cheque number দিন'); return; }
        try {
            const res = await fetch(API.storeGratuity, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ party_type: 'client', party_id: CLIENT_ID, work_id: workId, task_id: taskId, account_id: accountId, amount, date, payment_method: paymentMethod, instrument_no: instrumentNo || undefined }) });
            const json = await res.json();
            if (json.success) { toast('success', json.message || 'Gratuity recorded'); clCloseGratuity(); clLoadEntries(); }
            else toast('error', json.message || 'Failed');
        } catch(e) { toast('error', 'Network error'); }
    };

    // Task list for this client (from party-ledger's groups)
    let _clTasksLoaded = false;
    async function _clLoadTasks() {
        if (_clTasksLoaded) return;
        const tasks = [];
        const seen = new Set();
        (_clGroups||[]).forEach(g => {
            if (g.task_sys_id && !seen.has(g.task_sys_id)) {
                seen.add(g.task_sys_id);
                tasks.push({ id: g.task_sys_id, label: g.task_title || g.task_sys_id, workId: g.work_sys_id || '' });
            }
        });
        ['cl_advanceTask', 'cl_discountTask', 'cl_gratuityTask'].forEach(selId => {
            const sel = document.getElementById(selId);
            if (!sel) return;
            sel.innerHTML = '<option value="">Select a task…</option>' +
                tasks.map(t => `<option value="${t.id}" data-work-id="${t.workId}">${t.label}</option>`).join('');
        });
        _clTasksLoaded = true;
    }

    // ── Loans (separate section — see api/loans/list.php?action=party) ──
    async function _clLoadLoans() {
        if (!CLIENT_ID) return;
        try {
            const r = await fetch(`<?php echo $ip_port; ?>api/loans/list.php?action=party&party_type=client&party_id=${encodeURIComponent(CLIENT_ID)}`);
            const j = await r.json();
            if (!j.success) return;

            document.getElementById('cl-loan-owed-by').textContent = fmt(j.summary.total_owed_by_party);
            document.getElementById('cl-loan-owed-to').textContent = fmt(j.summary.total_owed_to_party);

            const allLoans = [...(j.as_lender||[]), ...(j.as_borrower||[])];
            document.getElementById('cl-loan-list').innerHTML = allLoans.length ? allLoans.map(loan => `
                <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg">
                    <span class="text-gray-600">${loan.lender_id === CLIENT_ID ? 'Lent to' : 'Borrowed from'} ${loan.lender_id === CLIENT_ID ? loan.borrower_name : loan.lender_name}</span>
                    <span class="font-semibold ${loan.status==='active'?'text-teal-700':'text-gray-400'}">${fmt(loan.outstanding_balance)} <span class="text-[10px] font-normal">(${loan.status})</span></span>
                </div>`).join('') : '<p class="text-gray-400 text-center py-2">কোনো loan নেই</p>';
        } catch(e) {}
    }

    window.clLoadEntries = clLoadEntries;
    clLoadEntries();
    _clLoadLoans();
})();
</script>

<!-- General Receive Modal -->
<div id="cl-generalReceiveModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.45);">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between p-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800 text-sm"><i class="fas fa-hand-holding-dollar mr-2 text-sky-600"></i>General Receive</h3>
            <button onclick="clCloseGeneralReceive()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-4 space-y-3">
            <p class="text-xs text-gray-400">নির্দিষ্ট কোনো bill না ধরে, সরাসরি client-এর মোট receivable কমাবে।</p>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Own Account</label>
                <div class="relative">
                    <input id="cl_grAccountSearch" placeholder="Search for an account…" class="f-input text-xs" autocomplete="off" oninput="clFilterGeneralReceiveAccount(this.value)" onfocus="clFilterGeneralReceiveAccount(this.value)">
                    <ul id="cl_grAccountDrop" class="absolute w-full bg-white border border-gray-200 rounded-lg mt-1 max-h-40 overflow-auto shadow-xl hidden z-50"></ul>
                </div>
                <input type="hidden" id="cl_grAccountId">
            </div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Amount ৳</label>
                <input type="number" step="0.01" min="0.01" id="cl_grAmount" class="f-input text-xs"></div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Payment Method</label>
                <select id="cl_grMethod" onchange="clToggleGeneralReceiveInstrument()" class="f-input text-xs">
                    <option value="cash">Cash</option><option value="npsb">NPSB</option><option value="rtgs">RTGS</option>
                    <option value="bftn">BFTN</option><option value="eft">EFT</option><option value="cheque">Cheque</option>
                </select></div>
            <div id="cl_grInstrumentWrap" class="hidden">
                <label class="block text-xs font-medium text-gray-700 mb-1">Cheque/Instrument No.</label>
                <input type="text" id="cl_grInstrumentNo" class="f-input text-xs"></div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Date</label>
                <input type="date" id="cl_grDate" value="" class="f-input text-xs"></div>
            <button onclick="clSubmitGeneralReceive()" class="w-full py-2.5 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-sm font-semibold transition"><i class="fas fa-check mr-1.5"></i>Confirm Receive</button>
        </div>
    </div>
</div>

<!-- Advance Modal -->
<div id="cl-advanceModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.45);">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between p-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800 text-sm"><i class="fas fa-piggy-bank mr-2 text-indigo-600"></i>Advance Received</h3>
            <button onclick="clCloseAdvance()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-4 space-y-3">
            <p class="text-xs text-gray-400">Advance অবশ্যই একটা Task-এর সাথে যুক্ত করতে হবে।</p>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Task <span class="text-red-500">*</span></label>
                <select id="cl_advanceTask" class="f-input text-xs"><option value="">Select a task…</option></select></div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Own Account</label>
                <div class="relative">
                    <input id="cl_advAccountSearch" placeholder="Search for an account…" class="f-input text-xs" autocomplete="off" oninput="clFilterAdvanceAccount(this.value)" onfocus="clFilterAdvanceAccount(this.value)">
                    <ul id="cl_advAccountDrop" class="absolute w-full bg-white border border-gray-200 rounded-lg mt-1 max-h-40 overflow-auto shadow-xl hidden z-50"></ul>
                </div>
                <input type="hidden" id="cl_advAccountId">
            </div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Amount ৳</label>
                <input type="number" step="0.01" min="0.01" id="cl_advanceAmount" class="f-input text-xs"></div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Payment Method</label>
                <select id="cl_advMethod" onchange="clToggleAdvanceInstrument()" class="f-input text-xs">
                    <option value="cash">Cash</option><option value="npsb">NPSB</option><option value="rtgs">RTGS</option>
                    <option value="bftn">BFTN</option><option value="eft">EFT</option><option value="cheque">Cheque</option>
                </select></div>
            <div id="cl_advInstrumentWrap" class="hidden">
                <label class="block text-xs font-medium text-gray-700 mb-1">Cheque/Instrument No.</label>
                <input type="text" id="cl_advInstrumentNo" class="f-input text-xs"></div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Date</label>
                <input type="date" id="cl_advanceDate" value="" class="f-input text-xs"></div>
            <button onclick="clSubmitAdvance()" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold transition"><i class="fas fa-check mr-1.5"></i>Record Advance</button>
        </div>
    </div>
</div>

<!-- Discount Given Modal -->
<div id="cl-discountModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.45);">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between p-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800 text-sm"><i class="fas fa-tags mr-2 text-purple-600"></i>Discount Given</h3>
            <button onclick="clCloseDiscount()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-4 space-y-3">
            <p class="text-xs text-gray-400">Discount অবশ্যই একটা Task-এর সাথে যুক্ত করতে হবে। এতে কোনো টাকা movement হয় না।</p>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Task <span class="text-red-500">*</span></label>
                <select id="cl_discountTask" class="f-input text-xs"><option value="">Select a task…</option></select></div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Amount ৳</label>
                <input type="number" step="0.01" min="0.01" id="cl_discountAmount" class="f-input text-xs"></div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Date</label>
                <input type="date" id="cl_discountDate" value="" class="f-input text-xs"></div>
            <button onclick="clSubmitDiscount()" class="w-full py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-sm font-semibold transition"><i class="fas fa-check mr-1.5"></i>Record Discount</button>
        </div>
    </div>
</div>

<!-- Gratuity Received Modal -->
<div id="cl-gratuityModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.45);">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between p-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800 text-sm"><i class="fas fa-gift mr-2 text-pink-600"></i>Gratuity Received</h3>
            <button onclick="clCloseGratuity()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-4 space-y-3">
            <p class="text-xs text-gray-400">Gratuity অবশ্যই একটা Task-এর সাথে যুক্ত করতে হবে। এটা প্রকৃত টাকা movement।</p>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Task <span class="text-red-500">*</span></label>
                <select id="cl_gratuityTask" class="f-input text-xs"><option value="">Select a task…</option></select></div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Own Account</label>
                <div class="relative">
                    <input id="cl_gratAccountSearch" placeholder="Search for an account…" class="f-input text-xs" autocomplete="off" oninput="clFilterGratuityAccount(this.value)" onfocus="clFilterGratuityAccount(this.value)">
                    <ul id="cl_gratAccountDrop" class="absolute w-full bg-white border border-gray-200 rounded-lg mt-1 max-h-40 overflow-auto shadow-xl hidden z-50"></ul>
                </div>
                <input type="hidden" id="cl_gratAccountId">
            </div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Amount ৳</label>
                <input type="number" step="0.01" min="0.01" id="cl_gratuityAmount" class="f-input text-xs"></div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Payment Method</label>
                <select id="cl_gratMethod" onchange="clToggleGratuityInstrument()" class="f-input text-xs">
                    <option value="cash">Cash</option><option value="npsb">NPSB</option><option value="rtgs">RTGS</option>
                    <option value="bftn">BFTN</option><option value="eft">EFT</option><option value="cheque">Cheque</option>
                </select></div>
            <div id="cl_gratInstrumentWrap" class="hidden">
                <label class="block text-xs font-medium text-gray-700 mb-1">Cheque/Instrument No.</label>
                <input type="text" id="cl_gratInstrumentNo" class="f-input text-xs"></div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Date</label>
                <input type="date" id="cl_gratuityDate" value="" class="f-input text-xs"></div>
            <button onclick="clSubmitGratuity()" class="w-full py-2.5 bg-pink-600 hover:bg-pink-700 text-white rounded-xl text-sm font-semibold transition"><i class="fas fa-check mr-1.5"></i>Record Gratuity</button>
        </div>
    </div>
</div>