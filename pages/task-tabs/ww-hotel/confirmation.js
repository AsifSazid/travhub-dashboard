/**
 * FILE PATH: /pages/task-tabs/ww-hotel/confirmation.js
 * Tab: Confirmation
 */

window._renderHtConfirmation = function() {
    const panel         = document.getElementById('ht-panel-confirmation');
    const confirmations = window._ht.data?.ht_confirmations ?? [];
    const bookings      = window._ht.data?.ht_bookings      ?? [];

    const activeConfIds = new Set(confirmations.filter(c => !['failed','cancelled'].includes(c.status)).map(c => c.booking_sys_id));
    const availableB    = bookings.filter(b => !activeConfIds.has(b.sys_id) && b.status !== 'cancelled');

    panel.innerHTML = `
    <div class="flex gap-4">
        <!-- LEFT: list -->
        <div style="width:220px;flex-shrink:0;">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Confirmations</span>
                ${availableB.length ? `<button onclick="htOpenAddConf()"
                    class="flex items-center gap-1 px-2.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold transition">
                    <i class="fas fa-plus text-xs"></i>Add
                </button>` : ''}
            </div>
            <div id="ht-conf-list" class="space-y-1.5">
                ${confirmations.length ? confirmations.map(c => _htConfCard(c, bookings)).join('') : `
                    <div class="text-center py-6">
                        <i class="fas fa-check-circle text-3xl text-gray-200 mb-2 block"></i>
                        <p class="text-xs text-gray-300">No confirmations yet.</p>
                        <p class="text-[11px] text-gray-300 mt-1">Send a booking from Booking tab.</p>
                    </div>`}
            </div>
        </div>
        <!-- RIGHT: detail -->
        <div class="flex-1 min-w-0" id="ht-conf-detail">
            <div class="flex items-center justify-center h-40 text-gray-300 text-sm">
                <div class="text-center"><i class="fas fa-check-circle text-3xl mb-2 block opacity-30"></i>Select a confirmation</div>
            </div>
        </div>
    </div>

    <!-- Add Confirmation Modal -->
    <div id="ht-add-conf-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.45);">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
            <div class="flex items-center justify-between p-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-800 text-sm"><i class="fas fa-check-circle mr-2 text-indigo-500"></i>Add to Confirmation</h3>
                <button onclick="document.getElementById('ht-add-conf-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-4">
                <label class="text-xs font-bold text-gray-400 uppercase block mb-2">Select Booking</label>
                <select id="ht-add-conf-select" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-400 mb-4">
                    ${availableB.map(b=>`<option value="${_hte(b.sys_id)}">${_hte(b.sys_id)} — ${_hte(b.hotel_name||'')} ${b.check_in?'('+b.check_in+')':''}</option>`).join('')}
                </select>
                <button onclick="htAddConfirmation()"
                    class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold transition">
                    <i class="fas fa-check mr-1.5"></i>Add to Confirmation
                </button>
            </div>
        </div>
    </div>`;
};

function _htConfCard(c, bookings) {
    const b    = bookings.find(x => x.sys_id === c.booking_sys_id);
    const sMap = { pending:'bg-yellow-50 text-yellow-700', confirmed:'bg-green-50 text-green-700', failed:'bg-red-50 text-red-600', cancelled:'bg-gray-100 text-gray-500' };
    const dMap = { pending:'bg-yellow-400', confirmed:'bg-green-500', failed:'bg-red-400', cancelled:'bg-gray-400' };
    const st   = c.status ?? 'pending';
    return `<div class="ht-conf-card cursor-pointer rounded-xl border border-gray-100 p-3 hover:border-indigo-200 transition ${c.sys_id===window._ht.activeConfId?'border-indigo-400 bg-indigo-50':''}"
        onclick="htSelectConf('${_hte(c.sys_id)}')">
        <div class="flex items-center justify-between mb-1">
            <div class="flex items-center gap-1.5">
                <div class="w-2 h-2 rounded-full ${dMap[st]??'bg-gray-400'} flex-shrink-0"></div>
                <span class="font-mono text-[10px] text-indigo-500">${_hte(c.sys_id)}</span>
            </div>
        </div>
        <div class="text-xs font-semibold text-gray-700">${_hte(b?.hotel_name||'—')}</div>
        <div class="text-[11px] text-gray-400">${_hte(c.check_in||b?.check_in||'—')}</div>
        <div class="flex items-center justify-between mt-1">
            <span class="text-[11px] font-bold text-green-600">${_hte(b?.currency||'BDT')} ${_htFmtN(b?.total_sell||0)}</span>
            <span class="text-[10px] px-1.5 py-0.5 rounded font-semibold ${sMap[st]}">${st}</span>
        </div>
    </div>`;
}

window.htOpenAddConf = function() {
    document.getElementById('ht-add-conf-modal')?.classList.remove('hidden');
};

window.htAddConfirmation = async function() {
    const bSysId = document.getElementById('ht-add-conf-select')?.value;
    if (!bSysId) return;
    document.getElementById('ht-add-conf-modal')?.classList.add('hidden');
    try {
        const json = await window._htApi({ action:'add_to_confirmation', booking_sys_id:bSysId });
        if (json.status === 'success') { htT('success','Added!'); await window._htReload(); _renderHtConfirmation(); }
        else htT('error', json.message);
    } catch(e) { htT('error','Network error'); }
};

window.htSelectConf = function(confId) {
    window._ht.activeConfId = confId;
    const confirmations = window._ht.data?.ht_confirmations ?? [];
    const bookings      = window._ht.data?.ht_bookings      ?? [];
    const c = confirmations.find(x => x.sys_id === confId);
    if (!c) return;
    document.querySelectorAll('.ht-conf-card').forEach(x => x.classList.remove('border-indigo-400','bg-indigo-50'));
    event?.currentTarget?.classList.add('border-indigo-400','bg-indigo-50');
    const b = bookings.find(x => x.sys_id === c.booking_sys_id);
    _htRenderConfDetail(c, b);
};

function _htRenderConfDetail(c, b) {
    const detail = document.getElementById('ht-conf-detail');
    if (!detail) return;
    const confirmedTasks = window._ht.data?.confirmed_tasks ?? [];
    const hasTask     = confirmedTasks.some(t => t.confirmation_sys_id === c.sys_id);
    const isPending   = (c.status ?? 'pending') === 'pending';
    const isConfirmed = (c.status ?? 'pending') === 'confirmed';
    const canDelete   = !(isConfirmed && hasTask);

    detail.innerHTML = `
    <div class="flex items-center justify-between mb-4">
        <div>
            <div class="flex items-center gap-2">
                <h3 class="font-bold text-gray-800 text-sm">Confirmation</h3>
            </div>
            <div class="text-[11px] text-gray-400 mt-0.5">
                Conf: <span class="font-mono text-indigo-500">${_hte(c.sys_id)}</span>
                · Booking: <span class="font-mono text-green-600">${_hte(c.booking_sys_id)}</span>
            </div>
        </div>
        <div class="flex items-center gap-2">
            ${isPending ? `
                <button onclick="htUpdateConfStatus('${_hte(c.sys_id)}','confirmed')"
                    class="px-3 py-1.5 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-semibold rounded-lg transition">
                    ✓ Confirm
                </button>
                <button onclick="htConfirmAndCreateTask('${_hte(c.sys_id)}')"
                    class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg transition whitespace-nowrap">
                    ✓ Confirm & Create Task
                </button>
            ` : hasTask ? `
                <span class="text-xs text-emerald-600 font-semibold bg-emerald-50 border border-emerald-200 px-2 py-1 rounded-lg whitespace-nowrap">
                    <i class="fas fa-check-double text-[9px] mr-1"></i>Confirmed · Task Created
                </span>
            ` : `
                <button onclick="htUpdateConfStatus('${_hte(c.sys_id)}','pending')"
                    class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs font-semibold rounded-lg transition">
                    ↩ Revert
                </button>
                <button onclick="htConfirmAndCreateTask('${_hte(c.sys_id)}')"
                    class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg transition whitespace-nowrap">
                    + Create Task
                </button>
            `}
            ${canDelete ? `<button onclick="htRemoveConf('${_hte(c.sys_id)}')" class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 text-xs rounded-lg"><i class="fas fa-trash"></i></button>` : ''}
        </div>
    </div>

    ${b ? `<div class="bg-green-50 border border-green-100 rounded-xl p-3 mb-4 grid grid-cols-2 gap-3 text-xs">
        <div><span class="text-gray-400 uppercase text-[10px]">Hotel</span><div class="font-semibold text-gray-700">${_hte(b.hotel_name||'—')} ${b.star_rating?'<span style="color:#F59E0B;">'+('★'.repeat(b.star_rating))+'</span>':''}</div></div>
        <div><span class="text-gray-400 uppercase text-[10px]">Booking Ref</span><div class="font-semibold text-gray-700">${_hte(b.booking_ref||b.pcn||'—')}</div></div>
        <div><span class="text-gray-400 uppercase text-[10px]">Stay</span><div class="font-semibold text-gray-700">${_hte(b.check_in||'—')} → ${_hte(b.check_out||'—')} (${b.nights||0}N)</div></div>
        <div><span class="text-gray-400 uppercase text-[10px]">Total Sell</span><div class="font-bold text-emerald-600">${_hte(b.currency||'BDT')} ${_htFmtN(b.total_sell||0)}</div></div>
    </div>` : ''}

    <div class="mb-3">
        <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Note</label>
        <textarea id="ht-conf-note-${_hte(c.sys_id)}" rows="2"
            class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm resize-none focus:outline-none focus:border-indigo-400">${_hte(c.note||'')}</textarea>
    </div>
    <div class="flex gap-2">
        <button onclick="htSaveConfDetails('${_hte(c.sys_id)}')"
            class="flex-1 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition">
            <i class="fas fa-save mr-1"></i>Save Details
        </button>
    </div>`;
}

window.htUpdateConfStatus = async function(confId, status) {
    try {
        const json = await window._htApi({ action:'update_conf_status', conf_sys_id:confId, status });
        if (json.status==='success') { htT('success','Status updated'); await window._htReload(); _renderHtConfirmation(); }
        else htT('error', json.message);
    } catch(e) { htT('error','Network error'); }
};

window.htSaveConfDetails = async function(confId) {
    const note = document.getElementById(`ht-conf-note-${confId}`)?.value ?? '';
    try {
        const json = await window._htApi({ action:'update_confirmation', conf_sys_id:confId, note });
        if (json.status==='success') { htT('success','Saved!'); await window._htReload(); _renderHtConfirmation(); }
        else htT('error', json.message);
    } catch(e) { htT('error','Network error'); }
};

window.htRemoveConf = async function(confId) {
    if (!confirm('Remove confirmation?')) return;
    try {
        const json = await window._htApi({ action:'remove_confirmation', conf_sys_id:confId });
        if (json.status==='success') { htT('success','Removed'); await window._htReload(); window._ht.activeConfId=null; _renderHtConfirmation(); }
        else htT('error', json.message);
    } catch(e) { htT('error','Network error'); }
};

// ── Confirm & Create Task modal ───────────────────────────────
window.htConfirmAndCreateTask = function(confId) {
    const c = (window._ht.data?.ht_confirmations ?? []).find(x => x.sys_id === confId);
    const b = (window._ht.data?.ht_bookings      ?? []).find(x => x.sys_id === c?.booking_sys_id);
    _htOpenTaskModal(confId, b);
};

function _htOpenTaskModal(confId, booking) {
    document.getElementById('htConfirmTaskModal')?.remove();

    const suggestedPurpose = booking
        ? `Hotel Payment — ${booking.hotel_name||''} ${booking.check_in?'('+booking.check_in+')':''}`.trim()
        : 'Hotel Payment';
    const suggestedAmount = booking?.total_net ?? '';

    // ── Client pricing rows (vendor net + markup = sell) ──────
    const markupPct   = +(booking?.markup_pct ?? 0);
    const applyMarkup = v => markupPct > 0 ? Math.round(+(v||0) * (1 + markupPct / 100)) : +(v||0);
    const nights = booking?.nights || 1;
    const rooms  = booking?.rooms  || 1;
    const net    = +(booking?.net_rate  || 0);
    const sell   = +(booking?.sell_rate || applyMarkup(net));
    const curr   = booking?.currency || 'BDT';

    const clientRows = [];
    if (sell > 0 && nights > 0 && rooms > 0) {
        clientRows.push({ label:`Hotel (${rooms} room${rooms>1?'s':''} × ${nights} nights)`, pax:rooms, perPax:sell*nights, total:sell*nights*rooms });
    }
    const clientTotal = clientRows.reduce((s, r) => s + r.total, 0);
    window._htClientRows  = clientRows;
    window._htClientTotal = clientTotal;

    const clientHtml = clientRows.length ? `
    <div class="border border-emerald-100 rounded-xl p-3 bg-emerald-50">
        <div class="flex items-center justify-between mb-2">
            <label class="text-xs font-bold text-emerald-700 uppercase">Client Sale Entry</label>
            ${markupPct > 0 ? `<span class="text-[10px] text-emerald-600 bg-emerald-100 px-2 py-0.5 rounded-full font-semibold">+${markupPct}% markup</span>` : '<span class="text-[10px] text-gray-400">No markup</span>'}
        </div>
        <table class="w-full text-xs mb-2">
            <tbody>
                ${clientRows.map(r=>`<tr><td class="py-1 font-medium text-gray-700">${_hte(r.label)}</td><td class="py-1 text-right font-bold text-emerald-700">${curr} ${_htFmtN(r.total)}</td></tr>`).join('')}
            </tbody>
            <tfoot><tr class="border-t-2 border-emerald-200"><td class="pt-1 text-right font-bold text-emerald-700 text-xs" colspan="1">Client Total:</td><td class="pt-1 text-right font-bold text-emerald-700">${curr} ${_htFmtN(clientTotal)}</td></tr></tfoot>
        </table>
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" id="htIncludeClientEntry" checked class="w-3.5 h-3.5 accent-emerald-600">
            <span class="text-xs font-semibold text-emerald-700">Include Client Sale Entry</span>
        </label>
    </div>` : '';

    const modal = document.createElement('div');
    modal.id = 'htConfirmTaskModal';
    modal.className = 'fixed inset-0 z-[80] flex items-center justify-center p-4';
    modal.style.background = 'rgba(0,0,0,.45)';
    modal.innerHTML = `
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800 text-sm"><i class="fas fa-check-circle mr-2 text-indigo-500"></i>Confirm & Create Task</h3>
            <button onclick="document.getElementById('htConfirmTaskModal').remove()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-4 space-y-3">
            <p class="text-xs text-gray-400">Task তৈরির সাথে সাথে vendor payment ও client sale entry রেকর্ড হবে।</p>

            ${clientHtml}

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Vendor</label>
                <div class="relative" id="htVendorWrap">
                    <input id="htVendorSearch" placeholder="Search vendor…" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-400" autocomplete="off"
                        oninput="_htFilterList('vendor',this.value)" onfocus="_htFilterList('vendor',this.value)">
                    <ul id="htVendorDrop" class="absolute w-full bg-white border border-gray-200 rounded-lg mt-1 max-h-44 overflow-auto shadow-xl hidden z-50"></ul>
                </div>
                <input type="hidden" id="htVendorId">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1.5">Transaction Type</label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="flex items-center justify-center gap-1.5 p-2 border-2 border-emerald-500 bg-emerald-50 text-emerald-700 rounded-lg cursor-pointer text-xs font-semibold" id="htModeRealtimeLbl">
                        <input type="radio" name="ht_txn_mode" value="realtime" class="hidden" checked onchange="_htToggleMode()"><i class="fa-solid fa-bolt"></i>Real-time</label>
                    <label class="flex items-center justify-center gap-1.5 p-2 border-2 border-gray-200 text-gray-500 rounded-lg cursor-pointer text-xs font-semibold" id="htModeNonRealtimeLbl">
                        <input type="radio" name="ht_txn_mode" value="non_realtime" class="hidden" onchange="_htToggleMode()"><i class="fa-solid fa-clock-rotate-left"></i>Non-real-time</label>
                </div>
            </div>
            <div id="htAccountSection">
                <label class="block text-xs font-medium text-gray-700 mb-1">Own Account</label>
                <div class="relative" id="htAccountWrap">
                    <input id="htAccountSearch" placeholder="Search account…" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-400" autocomplete="off"
                        oninput="_htFilterList('account',this.value)" onfocus="_htFilterList('account',this.value)">
                    <ul id="htAccountDrop" class="absolute w-full bg-white border border-gray-200 rounded-lg mt-1 max-h-44 overflow-auto shadow-xl hidden z-50"></ul>
                </div>
                <input type="hidden" id="htAccountId">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Purpose</label>
                <textarea id="htPurpose" rows="2" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-400">${_hte(suggestedPurpose)}</textarea>
            </div>
            <div class="grid grid-cols-3 gap-2">
                <div><label class="block text-[10px] text-gray-500 mb-0.5">QTY</label><input type="number" id="htQty" class="w-full px-2 py-2 border border-gray-200 rounded-lg text-xs focus:outline-none ht-calc" placeholder="0"></div>
                <div><label class="block text-[10px] text-gray-500 mb-0.5">Rate</label><input type="number" id="htRate" class="w-full px-2 py-2 border border-gray-200 rounded-lg text-xs focus:outline-none ht-calc" placeholder="0.00"></div>
                <div><label class="block text-[10px] text-gray-500 mb-0.5">Amount</label><input type="number" id="htAmount" value="${_hte(String(suggestedAmount))}" class="w-full px-2 py-2 border border-gray-200 rounded-lg text-xs focus:outline-none ht-calc"></div>
            </div>
            <div class="flex gap-2 pt-1">
                <button onclick="_htSkipAndCreate('${_hte(confId)}')" class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg text-xs font-semibold transition">Skip Payment</button>
                <button onclick="_htConfirmWithPayment('${_hte(confId)}')" class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold transition">
                    <i class="fas fa-check mr-1.5"></i>Confirm & Pay
                </button>
            </div>
        </div>
    </div>`;
    document.body.appendChild(modal);
    _htSetupCalc();
    _htLoadVendorsAccounts();
}

let _htVendors = [], _htAccounts = [];

async function _htLoadVendorsAccounts() {
    try {
        const [vRes, aRes] = await Promise.all([fetch(window._ht.cfg.api.allVendors), fetch(window._ht.cfg.api.allAccounts)]);
        const vJson = await vRes.json(), aJson = await aRes.json();
        _htVendors  = vJson.vendors  ?? [];
        _htAccounts = aJson.accounts ?? [];
    } catch(e) { console.error('[hotel] vendor/account load:', e); }
}

window._htFilterList = function(kind, q) {
    const isV  = kind === 'vendor';
    const dd   = document.getElementById(isV ? 'htVendorDrop' : 'htAccountDrop');
    const src  = isV ? _htVendors : _htAccounts;
    const nk   = isV ? 'name' : 'acc_name';
    const v    = q.toLowerCase().trim();
    const list = v ? src.filter(x => (x[nk]||'').toLowerCase().includes(v)) : src.slice(0, 15);
    if (!list.length) { dd.innerHTML = `<li class="px-4 py-3 text-center text-gray-400 text-xs">Not found</li>`; dd.classList.remove('hidden'); return; }
    dd.innerHTML = list.map(x => `<li class="px-3 py-2 cursor-pointer hover:bg-indigo-50 text-sm text-gray-800"
        onclick="_htSelectItem('${kind}','${x.sys_id}','${_hte(x[nk]||x.sys_id).replace(/'/g,"\\'")}')">${_hte(x[nk]??x.sys_id)}</li>`).join('');
    dd.classList.remove('hidden');
};

window._htSelectItem = function(kind, sysId, name) {
    const isV = kind === 'vendor';
    document.getElementById(isV ? 'htVendorSearch' : 'htAccountSearch').value = name;
    document.getElementById(isV ? 'htVendorId'     : 'htAccountId').value     = sysId;
    document.getElementById(isV ? 'htVendorDrop'   : 'htAccountDrop').classList.add('hidden');
};

document.addEventListener('click', e => {
    ['htVendorWrap','htAccountWrap'].forEach(wId => {
        const w = document.getElementById(wId);
        if (w && !w.contains(e.target)) document.getElementById(wId==='htVendorWrap'?'htVendorDrop':'htAccountDrop')?.classList.add('hidden');
    });
});

window._htToggleMode = function() {
    const isRT = document.querySelector('input[name="ht_txn_mode"]:checked')?.value === 'realtime';
    document.getElementById('htAccountSection').classList.toggle('hidden', !isRT);
    document.getElementById('htModeRealtimeLbl').className    = 'flex items-center justify-center gap-1.5 p-2 border-2 rounded-lg cursor-pointer text-xs font-semibold ' + (isRT  ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-gray-200 text-gray-500');
    document.getElementById('htModeNonRealtimeLbl').className = 'flex items-center justify-center gap-1.5 p-2 border-2 rounded-lg cursor-pointer text-xs font-semibold ' + (!isRT ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-gray-200 text-gray-500');
};

function _htSetupCalc() {
    const q = document.getElementById('htQty'), r = document.getElementById('htRate'), a = document.getElementById('htAmount');
    if (!q||!r||!a) return;
    let last = null;
    const calc = () => {
        const qv=parseFloat(q.value)||null, rv=parseFloat(r.value)||null, av=parseFloat(a.value)||null;
        if (last!=='amount' && qv && rv) a.value=(qv*rv).toFixed(2);
        else if (last!=='rate' && qv && av) r.value=(av/qv).toFixed(2);
        else if (last!=='qty'  && rv && av) q.value=(av/rv).toFixed(2);
    };
    q.addEventListener('input', ()=>{last='qty';  calc();});
    r.addEventListener('input', ()=>{last='rate'; calc();});
    a.addEventListener('input', ()=>{last='amount';calc();});
}

async function _htSkipAndCreate(confId) {
    document.getElementById('htConfirmTaskModal')?.remove();
    await _htDoConfirm(confId, null, false);
}

async function _htConfirmWithPayment(confId) {
    const txnMode  = document.querySelector('input[name="ht_txn_mode"]:checked')?.value;
    const amount   = parseFloat(document.getElementById('htAmount')?.value);
    const qty      = parseFloat(document.getElementById('htQty')?.value)  || null;
    const rate     = parseFloat(document.getElementById('htRate')?.value) || null;
    const purpose  = document.getElementById('htPurpose')?.value.trim();
    const vendorId = document.getElementById('htVendorId')?.value;
    const accountId= document.getElementById('htAccountId')?.value;
    const includeClient = document.getElementById('htIncludeClientEntry')?.checked ?? true;

    if (amount && amount > 0) {
        if (!purpose)  { htT('error','Purpose দিন'); return; }
        if (!vendorId) { htT('error','Vendor সিলেক্ট করুন'); return; }
        if (txnMode === 'realtime' && !accountId) { htT('error','Account সিলেক্ট করুন'); return; }
    }

    const payment = (amount && amount > 0) ? {
        amount, purpose, txnMode, vendorId,
        qtyRate:   qty || rate ? JSON.stringify({qty:qty||0, rate:rate||0}) : null,
        accountId: txnMode === 'realtime' ? accountId : null,
    } : null;

    document.getElementById('htConfirmTaskModal')?.remove();
    await _htDoConfirm(confId, payment, includeClient);
}

async function _htDoConfirm(confId, payment, includeClient = false) {
    if (!confirm('Confirm this booking and create a task?')) return;
    try {
        const json = await window._htApi({ action:'confirm_and_create_task', conf_sys_id:confId });
        if (json.status === 'success') {
            htT('success', json.auto_task_id ? `Confirmed! Task: ${json.auto_task_id}` : 'Confirmed!');

            // Vendor payment entry
            if (payment && json.task_created && json.auto_task_id) {
                try {
                    await fetch(window._ht.cfg.api.saveFinancial, {
                        method:'POST', headers:{'Content-Type':'application/json'},
                        body: JSON.stringify({
                            type: 'credit', // vendor purchase
                            vendor_id:        payment.vendorId,
                            amount:           payment.amount,
                            qty_rate:         payment.qtyRate,
                            purpose:          payment.purpose,
                            transaction_mode: payment.txnMode,
                            account_id:       payment.accountId,
                            work_id:          json.work_sys_id,
                            task_id:          json.auto_task_id,
                            date:             new Date().toISOString().slice(0,10),
                        }),
                    });
                } catch(e) { console.error('[hotel] vendor payment failed:', e); }
            }

            // Client sale entries
            const clientRows  = window._htClientRows ?? [];
            const clientSysId = window._ht.cfg.clientSysId ?? window._ht.cfg.workClientSysId ?? null;
            if (!clientSysId && includeClient && clientRows.length) {
                htT('error', 'Client ID missing — client entry skipped. Check config.');
            }
            if (includeClient && json.task_created && json.auto_task_id && clientRows.length && clientSysId) {
                for (const row of clientRows) {
                    try {
                        await fetch(window._ht.cfg.api.saveFinancial, {
                            method:'POST', headers:{'Content-Type':'application/json'},
                            body: JSON.stringify({
                                type:      'debit', // client sale → AR
                                client_id: clientSysId,
                                amount:    row.total,
                                qty_rate:  JSON.stringify({ qty: row.pax, rate: row.perPax }),
                                purpose:   `Hotel — ${row.label}`,
                                work_id:   json.work_sys_id,
                                task_id:   json.auto_task_id,
                                date:      new Date().toISOString().slice(0,10),
                            }),
                        });
                    } catch(e) { console.error('[hotel] client entry failed:', e); }
                }
            }
            window._htClientRows  = [];
            window._htClientTotal = 0;

            await window._htReload();
            _renderHtConfirmation();
            if (typeof window.reloadConfirmedTasks === 'function') window.reloadConfirmedTasks();
        } else { htT('error', json.message ?? 'Failed'); }
    } catch(e) { htT('error','Network error'); }
}