/**
 * FILE PATH: /pages/task-tabs/ww-transport/confirmation.js
 */

window._renderTsConfirmation = function() {
    const panel         = document.getElementById('ts-panel-confirmation');
    const confirmations = window._ts.data?.ts_confirmations ?? [];
    const quotations    = window._ts.data?.ts_quotations    ?? [];
    const activeConfIds = new Set(confirmations.filter(c=>!['failed','cancelled'].includes(c.status)).map(c=>c.quotation_sys_id));
    const availableQ    = quotations.filter(q => !activeConfIds.has(q.sys_id) && q.status !== 'cancelled');

    panel.innerHTML = `
    <div class="flex gap-4">
        <div style="width:220px;flex-shrink:0;">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Confirmations</span>
                ${availableQ.length ? `<button onclick="tsOpenAddConf()"
                    class="flex items-center gap-1 px-2.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold transition">
                    <i class="fas fa-plus text-xs"></i>Add
                </button>` : ''}
            </div>
            <div id="ts-conf-list" class="space-y-1.5">
                ${confirmations.length ? confirmations.map(c => _tsConfCard(c)).join('') : `
                    <div class="text-center py-6">
                        <i class="fas fa-check-circle text-3xl text-gray-200 mb-2 block"></i>
                        <p class="text-xs text-gray-300">No confirmations yet.</p>
                        <p class="text-[11px] text-gray-300 mt-1">Send a quotation from Quotation tab.</p>
                    </div>`}
            </div>
        </div>
        <div class="flex-1 min-w-0" id="ts-conf-detail">
            <div class="flex items-center justify-center h-40 text-gray-300 text-sm">
                <div class="text-center"><i class="fas fa-check-circle text-3xl mb-2 block opacity-30"></i>Select a confirmation</div>
            </div>
        </div>
    </div>

    <!-- Add modal -->
    <div id="ts-add-conf-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.45);">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
            <div class="flex items-center justify-between p-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-800 text-sm"><i class="fas fa-check-circle mr-2 text-indigo-500"></i>Add to Confirmation</h3>
                <button onclick="document.getElementById('ts-add-conf-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-4">
                <label class="text-xs font-bold text-gray-400 uppercase block mb-2">Select Quotation</label>
                <select id="ts-add-conf-select" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-400 mb-4">
                    ${availableQ.map(q=>{
                        const legs=q.legs??[];
                        const route=legs.length?`${legs[0].from||'?'} → ${legs[legs.length-1].to||'?'}`:'—';
                        return `<option value="${_tse(q.sys_id)}">${_tse(q.sys_id)} — ${_tse(route)} (${legs.length}L)</option>`;
                    }).join('')}
                </select>
                <button onclick="tsAddConfirmation()"
                    class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold transition">
                    <i class="fas fa-check mr-1.5"></i>Add to Confirmation
                </button>
            </div>
        </div>
    </div>`;
};

function _tsConfCard(c) {
    const legs = c.legs ?? [];
    const route = legs.length ? `${legs[0].from||'?'} → ${legs[legs.length-1].to||'?'}` : '—';
    const sMap = { pending:'bg-yellow-50 text-yellow-700', confirmed:'bg-green-50 text-green-700', failed:'bg-red-50 text-red-600', cancelled:'bg-gray-100 text-gray-500' };
    const dMap = { pending:'bg-yellow-400', confirmed:'bg-green-500', failed:'bg-red-400', cancelled:'bg-gray-400' };
    const st   = c.status ?? 'pending';
    return `<div class="ts-conf-card cursor-pointer rounded-xl border border-gray-100 p-3 hover:border-indigo-200 transition ${c.sys_id===window._ts.activeConfId?'border-indigo-400 bg-indigo-50':''}"
        onclick="tsSelectConf('${_tse(c.sys_id)}')">
        <div class="flex items-center gap-1.5 mb-1">
            <div class="w-2 h-2 rounded-full ${dMap[st]??'bg-gray-400'} flex-shrink-0"></div>
            <span class="font-mono text-[10px] text-indigo-500">${_tse(c.sys_id)}</span>
        </div>
        <div class="text-xs font-semibold text-gray-700 truncate">${_tse(route)}</div>
        <div class="text-[11px] text-gray-400">${legs.length} leg${legs.length!==1?'s':''} · ${legs[0]?.date||'—'}</div>
        <div class="flex items-center justify-between mt-1">
            <span class="text-[11px] font-bold text-sky-600">${_tse(c.currency||'BDT')} ${_tsFmt(c.total_sell||c.total_net||0)}</span>
            <span class="text-[10px] px-1.5 py-0.5 rounded font-semibold ${sMap[st]}">${st}</span>
        </div>
    </div>`;
}

window.tsOpenAddConf = function() { document.getElementById('ts-add-conf-modal')?.classList.remove('hidden'); };

window.tsAddConfirmation = async function() {
    const qSysId = document.getElementById('ts-add-conf-select')?.value;
    if (!qSysId) return;
    document.getElementById('ts-add-conf-modal')?.classList.add('hidden');
    try {
        const json = await window._tsApi({ action:'add_to_confirmation', quotation_sys_id:qSysId });
        if (json.status==='success') { tsT('success','Added!'); await window._tsReload(); _renderTsConfirmation(); }
        else tsT('error', json.message);
    } catch(e) { tsT('error','Network error'); }
};

window.tsSelectConf = function(confId) {
    window._ts.activeConfId = confId;
    const c = (window._ts.data?.ts_confirmations??[]).find(x=>x.sys_id===confId);
    if (!c) return;
    document.querySelectorAll('.ts-conf-card').forEach(x => x.classList.remove('border-indigo-400','bg-indigo-50'));
    event?.currentTarget?.classList.add('border-indigo-400','bg-indigo-50');
    _tsRenderConfDetail(c);
};

function _tsRenderConfDetail(c) {
    const detail = document.getElementById('ts-conf-detail');
    if (!detail) return;
    const legs     = c.legs ?? [];
    const isPending= (c.status??'pending') === 'pending';
    const isConf   = (c.status??'pending') === 'confirmed';
    const hasTask  = (window._ts.data?.confirmed_tasks??[]).some(t=>t.confirmation_sys_id===c.sys_id);
    const canDel   = !(isConf && hasTask);

    detail.innerHTML = `
    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="font-bold text-gray-800 text-sm">Confirmation</h3>
            <div class="text-[11px] text-gray-400 mt-0.5">
                Conf: <span class="font-mono text-indigo-500">${_tse(c.sys_id)}</span>
                · Quot: <span class="font-mono text-sky-600">${_tse(c.quotation_sys_id)}</span>
            </div>
        </div>
        <div class="flex items-center gap-2">
            ${isPending ? `
                <button onclick="tsUpdateConfStatus('${_tse(c.sys_id)}','confirmed')"
                    class="px-3 py-1.5 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-semibold rounded-lg transition">✓ Confirm</button>
                <button onclick="tsConfirmAndCreateTask('${_tse(c.sys_id)}')"
                    class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg transition whitespace-nowrap">✓ Confirm & Create Task</button>
            ` : hasTask ? `
                <span class="text-xs text-emerald-600 font-semibold bg-emerald-50 border border-emerald-200 px-2 py-1 rounded-lg whitespace-nowrap"><i class="fas fa-check-double text-[9px] mr-1"></i>Confirmed · Task Created</span>
            ` : `
                <button onclick="tsUpdateConfStatus('${_tse(c.sys_id)}','pending')"
                    class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs font-semibold rounded-lg transition">↩ Revert</button>
                <button onclick="tsConfirmAndCreateTask('${_tse(c.sys_id)}')"
                    class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg transition whitespace-nowrap">+ Create Task</button>
            `}
            ${canDel ? `<button onclick="tsRemoveConf('${_tse(c.sys_id)}')" class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 text-xs rounded-lg"><i class="fas fa-trash"></i></button>` : ''}
        </div>
    </div>

    <!-- Financials summary -->
    <div class="grid grid-cols-2 gap-3 mb-4 p-3 bg-sky-50 rounded-xl border border-sky-100 text-xs">
        <div><span class="text-gray-400 uppercase text-[10px]">Total Net</span><div class="font-bold text-gray-700">${_tse(c.currency||'BDT')} ${_tsFmt(c.total_net||0)}</div></div>
        <div><span class="text-gray-400 uppercase text-[10px]">Total Sell</span><div class="font-bold text-emerald-600">${_tse(c.currency||'BDT')} ${_tsFmt(c.total_sell||0)}</div></div>
    </div>

    <!-- Per-leg vendor assignment -->
    <div class="mb-4">
        <div class="text-xs font-bold text-gray-500 uppercase mb-2">Legs — Vendor Assignment</div>
        <div id="ts-conf-legs" class="space-y-2">
            ${legs.map((l,i) => `
            <div class="rounded-xl border border-gray-100 p-3 bg-gray-50 text-xs">
                <div class="font-semibold text-gray-700 mb-2">
                    Leg ${l.leg_no||i+1}: <span class="text-sky-600">${_tse(l.from||'?')} → ${_tse(l.to||'?')}</span>
                    <span class="ml-2 text-gray-400">${_tse(l.date||'')} ${_tse(l.time||'')} · ${_tse(TS_VEHICLE_CLASSES[l.vehicle_class]||l.vehicle_class||'')} × ${l.qty||1}</span>
                </div>
                <div class="flex items-center gap-2">
                    <input class="ts-vendor-name flex-1 px-2 py-1.5 border border-gray-200 rounded-lg focus:outline-none focus:border-sky-400"
                        data-leg="${i}" placeholder="Vendor name (optional)"
                        value="${_tse(l.vendor_name||'')}">
                    <span class="text-gray-400 font-bold">${_tse(c.currency||'BDT')} ${_tsFmt((+(l.net_rate||0))*(+(l.qty||1)))}</span>
                </div>
            </div>`).join('')}
        </div>
        <button onclick="tsSaveConfVendors('${_tse(c.sys_id)}')"
            class="mt-2 w-full py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg text-xs font-semibold transition">
            <i class="fas fa-save mr-1"></i>Save Vendor Names
        </button>
    </div>

    <div class="mb-3">
        <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Note</label>
        <textarea id="ts-conf-note-${_tse(c.sys_id)}" rows="2"
            class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm resize-none focus:outline-none focus:border-indigo-400">${_tse(c.note||'')}</textarea>
    </div>
    <div class="flex gap-2">
        <button onclick="tsSaveConfNote('${_tse(c.sys_id)}')"
            class="flex-1 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition">
            <i class="fas fa-save mr-1"></i>Save Note
        </button>
    </div>`;
}

window.tsSaveConfVendors = async function(confId) {
    const c = (window._ts.data?.ts_confirmations??[]).find(x=>x.sys_id===confId);
    if (!c) return;
    const legs = [...(c.legs??[])];
    document.querySelectorAll('.ts-vendor-name').forEach(el => {
        const idx = +el.dataset.leg;
        if (legs[idx]) legs[idx].vendor_name = el.value.trim();
    });
    try {
        const json = await window._tsApi({ action:'update_confirmation', conf_sys_id:confId, legs });
        if (json.status==='success') { tsT('success','Vendors saved!'); await window._tsReload(); }
        else tsT('error', json.message);
    } catch(e) { tsT('error','Network error'); }
};

window.tsSaveConfNote = async function(confId) {
    const note = document.getElementById(`ts-conf-note-${confId}`)?.value ?? '';
    try {
        const json = await window._tsApi({ action:'update_confirmation', conf_sys_id:confId, note });
        if (json.status==='success') { tsT('success','Saved!'); await window._tsReload(); _renderTsConfirmation(); }
        else tsT('error', json.message);
    } catch(e) { tsT('error','Network error'); }
};

window.tsUpdateConfStatus = async function(confId, status) {
    try {
        const json = await window._tsApi({ action:'update_conf_status', conf_sys_id:confId, status });
        if (json.status==='success') { tsT('success','Updated'); await window._tsReload(); _renderTsConfirmation(); }
        else tsT('error', json.message);
    } catch(e) { tsT('error','Network error'); }
};

window.tsRemoveConf = async function(confId) {
    if (!confirm('Remove?')) return;
    try {
        const json = await window._tsApi({ action:'remove_confirmation', conf_sys_id:confId });
        if (json.status==='success') { tsT('success','Removed'); await window._tsReload(); window._ts.activeConfId=null; _renderTsConfirmation(); }
        else tsT('error', json.message);
    } catch(e) { tsT('error','Network error'); }
};

// ── Task Create Modal ─────────────────────────────────────────
window.tsConfirmAndCreateTask = function(confId) {
    const c = (window._ts.data?.ts_confirmations??[]).find(x=>x.sys_id===confId);
    _tsOpenTaskModal(confId, c);
};

function _tsOpenTaskModal(confId, conf) {
    document.getElementById('tsConfirmTaskModal')?.remove();

    const legs       = conf?.legs ?? [];
    const curr       = conf?.currency || 'BDT';
    const markupPct  = +(conf?.markup_pct ?? 0);
    const applyMarkup= v => markupPct > 0 ? Math.round(+(v||0)*(1+markupPct/100)) : +(v||0);

    // Client rows — per leg
    const clientRows = legs.map((l,i) => {
        const net  = +(l.net_rate||0);
        const sell = +(l.sell_rate||0) || applyMarkup(net);
        const qty  = +(l.qty||1);
        return { label:`Leg ${l.leg_no||i+1}: ${l.from||'?'} → ${l.to||'?'}`, qty, perUnit:sell, total:sell*qty };
    }).filter(r => r.total > 0);
    const clientTotal = clientRows.reduce((s,r) => s+r.total, 0);
    window._tsClientRows  = clientRows;
    window._tsClientTotal = clientTotal;

    // Suggested vendor amount = total net
    const totalNet = +(conf?.total_net||0);

    const clientHtml = clientRows.length ? `
    <div class="border border-emerald-100 rounded-xl p-3 bg-emerald-50">
        <div class="flex items-center justify-between mb-2">
            <label class="text-xs font-bold text-emerald-700 uppercase">Client Sale Entry</label>
            ${markupPct>0?`<span class="text-[10px] text-emerald-600 bg-emerald-100 px-2 py-0.5 rounded-full font-semibold">+${markupPct}% markup</span>`:'<span class="text-[10px] text-gray-400">No markup</span>'}
        </div>
        <table class="w-full text-xs mb-2">
            <tbody>${clientRows.map(r=>`<tr>
                <td class="py-0.5 text-gray-700">${_tse(r.label)}</td>
                <td class="py-0.5 text-right font-bold text-emerald-700">${curr} ${_tsFmt(r.total)}</td>
            </tr>`).join('')}</tbody>
            <tfoot><tr class="border-t-2 border-emerald-200">
                <td class="pt-1 text-right font-bold text-emerald-700 text-xs" colspan="1">Total:</td>
                <td class="pt-1 text-right font-bold text-emerald-700">${curr} ${_tsFmt(clientTotal)}</td>
            </tr></tfoot>
        </table>
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" id="tsIncludeClientEntry" checked class="w-3.5 h-3.5 accent-emerald-600">
            <span class="text-xs font-semibold text-emerald-700">Include Client Sale Entry</span>
        </label>
    </div>` : '';

    const modal = document.createElement('div');
    modal.id = 'tsConfirmTaskModal';
    modal.className = 'fixed inset-0 z-[80] flex items-center justify-center p-4';
    modal.style.background = 'rgba(0,0,0,.45)';
    modal.innerHTML = `
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800 text-sm"><i class="fas fa-check-circle mr-2 text-indigo-500"></i>Confirm & Create Task</h3>
            <button onclick="document.getElementById('tsConfirmTaskModal').remove()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-4 space-y-3">
            <p class="text-xs text-gray-400">Task তৈরির সাথে সাথে vendor payment ও client sale entry রেকর্ড হবে।</p>
            ${clientHtml}
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Vendor</label>
                <div class="relative" id="tsVendorWrap">
                    <input id="tsVendorSearch" placeholder="Search vendor…" autocomplete="off"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-400"
                        oninput="_tsFilterVendors(this.value)" onfocus="_tsFilterVendors(this.value)">
                    <ul id="tsVendorDrop" class="absolute w-full bg-white border border-gray-200 rounded-lg mt-1 max-h-44 overflow-auto shadow-xl hidden z-50"></ul>
                </div>
                <input type="hidden" id="tsVendorId">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1.5">Transaction Type</label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="flex items-center justify-center gap-1.5 p-2 border-2 border-emerald-500 bg-emerald-50 text-emerald-700 rounded-lg cursor-pointer text-xs font-semibold" id="tsModeRTLbl">
                        <input type="radio" name="ts_txn_mode" value="realtime" class="hidden" checked onchange="_tsToggleTxnMode()"><i class="fa-solid fa-bolt"></i>Real-time</label>
                    <label class="flex items-center justify-center gap-1.5 p-2 border-2 border-gray-200 text-gray-500 rounded-lg cursor-pointer text-xs font-semibold" id="tsModeNRLbl">
                        <input type="radio" name="ts_txn_mode" value="non_realtime" class="hidden" onchange="_tsToggleTxnMode()"><i class="fa-solid fa-clock-rotate-left"></i>Non-real-time</label>
                </div>
            </div>
            <div id="tsAccountSection">
                <label class="block text-xs font-medium text-gray-700 mb-1">Own Account</label>
                <div class="relative" id="tsAccountWrap">
                    <input id="tsAccountSearch" placeholder="Search account…" autocomplete="off"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-400"
                        oninput="_tsFilterAccounts(this.value)" onfocus="_tsFilterAccounts(this.value)">
                    <ul id="tsAccountDrop" class="absolute w-full bg-white border border-gray-200 rounded-lg mt-1 max-h-44 overflow-auto shadow-xl hidden z-50"></ul>
                </div>
                <input type="hidden" id="tsAccountId">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Purpose</label>
                <textarea id="tsPurpose" rows="2" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-400">Transport Payment — ${_tse((legs[0]?.from||'?')+' → '+(legs[legs.length-1]?.to||'?'))}</textarea>
            </div>
            <div class="grid grid-cols-3 gap-2">
                <div><label class="block text-[10px] text-gray-500 mb-0.5">QTY</label><input type="number" id="tsQty" class="w-full px-2 py-2 border border-gray-200 rounded-lg text-xs focus:outline-none ts-calc" placeholder="0"></div>
                <div><label class="block text-[10px] text-gray-500 mb-0.5">Rate</label><input type="number" id="tsRate" class="w-full px-2 py-2 border border-gray-200 rounded-lg text-xs focus:outline-none ts-calc" placeholder="0.00"></div>
                <div><label class="block text-[10px] text-gray-500 mb-0.5">Amount</label><input type="number" id="tsAmount" value="${_tse(String(totalNet))}" class="w-full px-2 py-2 border border-gray-200 rounded-lg text-xs focus:outline-none ts-calc"></div>
            </div>
            <div class="flex gap-2 pt-1">
                <button onclick="_tsSkipAndCreate('${_tse(confId)}')" class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg text-xs font-semibold">Skip Payment</button>
                <button onclick="_tsConfirmWithPayment('${_tse(confId)}')" class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold">
                    <i class="fas fa-check mr-1.5"></i>Confirm & Pay
                </button>
            </div>
        </div>
    </div>`;
    document.body.appendChild(modal);
    _tsSetupCalc();
    _tsLoadVendorsAccounts();
}

let _tsVendors = [], _tsAccounts = [];
async function _tsLoadVendorsAccounts() {
    try {
        const [vr,ar] = await Promise.all([fetch(window._ts.cfg.api.allVendors), fetch(window._ts.cfg.api.allAccounts)]);
        _tsVendors  = (await vr.json()).vendors  ?? [];
        _tsAccounts = (await ar.json()).accounts ?? [];
    } catch(e) {}
}

window._tsFilterVendors = function(q) { _tsFilter('vendor', q); };
window._tsFilterAccounts= function(q) { _tsFilter('account', q); };

function _tsFilter(kind, q) {
    const isV = kind==='vendor';
    const dd  = document.getElementById(isV?'tsVendorDrop':'tsAccountDrop');
    const src = isV?_tsVendors:_tsAccounts;
    const nk  = isV?'name':'acc_name';
    const v   = q.toLowerCase().trim();
    const list= v?src.filter(x=>(x[nk]||'').toLowerCase().includes(v)):src.slice(0,15);
    if (!list.length) { dd.innerHTML=`<li class="px-4 py-3 text-center text-gray-400 text-xs">Not found</li>`; dd.classList.remove('hidden'); return; }
    dd.innerHTML = list.map(x=>`<li class="px-3 py-2 cursor-pointer hover:bg-indigo-50 text-sm text-gray-800"
        onclick="_tsSelectItem('${kind}','${x.sys_id}','${_tse(x[nk]||x.sys_id).replace(/'/g,"\\'")}')">${_tse(x[nk]??x.sys_id)}</li>`).join('');
    dd.classList.remove('hidden');
}
window._tsSelectItem = function(kind, sysId, name) {
    const isV = kind==='vendor';
    document.getElementById(isV?'tsVendorSearch':'tsAccountSearch').value = name;
    document.getElementById(isV?'tsVendorId':'tsAccountId').value = sysId;
    document.getElementById(isV?'tsVendorDrop':'tsAccountDrop').classList.add('hidden');
};
document.addEventListener('click', e => {
    ['tsVendorWrap','tsAccountWrap'].forEach(wId => {
        const w = document.getElementById(wId);
        if (w && !w.contains(e.target)) document.getElementById(wId==='tsVendorWrap'?'tsVendorDrop':'tsAccountDrop')?.classList.add('hidden');
    });
});
window._tsToggleTxnMode = function() {
    const isRT = document.querySelector('input[name="ts_txn_mode"]:checked')?.value==='realtime';
    document.getElementById('tsAccountSection').classList.toggle('hidden', !isRT);
    document.getElementById('tsModeRTLbl').className  = 'flex items-center justify-center gap-1.5 p-2 border-2 rounded-lg cursor-pointer text-xs font-semibold '+(isRT ?'border-emerald-500 bg-emerald-50 text-emerald-700':'border-gray-200 text-gray-500');
    document.getElementById('tsModeNRLbl').className  = 'flex items-center justify-center gap-1.5 p-2 border-2 rounded-lg cursor-pointer text-xs font-semibold '+(!isRT?'border-emerald-500 bg-emerald-50 text-emerald-700':'border-gray-200 text-gray-500');
};
function _tsSetupCalc() {
    const q=document.getElementById('tsQty'),r=document.getElementById('tsRate'),a=document.getElementById('tsAmount');
    if (!q||!r||!a) return;
    let last=null;
    const calc=()=>{ const qv=parseFloat(q.value)||null,rv=parseFloat(r.value)||null,av=parseFloat(a.value)||null; if(last!=='amount'&&qv&&rv)a.value=(qv*rv).toFixed(2); else if(last!=='rate'&&qv&&av)r.value=(av/qv).toFixed(2); else if(last!=='qty'&&rv&&av)q.value=(av/rv).toFixed(2); };
    q.addEventListener('input',()=>{last='qty';  calc();});
    r.addEventListener('input',()=>{last='rate'; calc();});
    a.addEventListener('input',()=>{last='amount';calc();});
}

async function _tsSkipAndCreate(confId) {
    document.getElementById('tsConfirmTaskModal')?.remove();
    await _tsDoConfirm(confId, null, false);
}
async function _tsConfirmWithPayment(confId) {
    const txnMode   = document.querySelector('input[name="ts_txn_mode"]:checked')?.value;
    const amount    = parseFloat(document.getElementById('tsAmount')?.value);
    const qty       = parseFloat(document.getElementById('tsQty')?.value)||null;
    const rate      = parseFloat(document.getElementById('tsRate')?.value)||null;
    const purpose   = document.getElementById('tsPurpose')?.value.trim();
    const vendorId  = document.getElementById('tsVendorId')?.value;
    const accountId = document.getElementById('tsAccountId')?.value;
    const includeClient = document.getElementById('tsIncludeClientEntry')?.checked ?? true;

    if (amount&&amount>0) {
        if (!purpose)  { tsT('error','Purpose দিন'); return; }
        if (!vendorId) { tsT('error','Vendor সিলেক্ট করুন'); return; }
        if (txnMode==='realtime'&&!accountId) { tsT('error','Account সিলেক্ট করুন'); return; }
    }
    const payment = (amount&&amount>0)?{ amount, purpose, txnMode, vendorId, accountId:txnMode==='realtime'?accountId:null, qtyRate:qty||rate?JSON.stringify({qty:qty||0,rate:rate||0}):null }:null;
    document.getElementById('tsConfirmTaskModal')?.remove();
    await _tsDoConfirm(confId, payment, includeClient);
}

async function _tsDoConfirm(confId, payment, includeClient=false) {
    if (!confirm('Confirm and create task?')) return;
    try {
        const json = await window._tsApi({ action:'confirm_and_create_task', conf_sys_id:confId });
        if (json.status==='success') {
            tsT('success', json.auto_task_id ? `Confirmed! Task: ${json.auto_task_id}` : 'Confirmed!');

            // Vendor payment
            if (payment && json.task_created && json.auto_task_id) {
                try {
                    await fetch(window._ts.cfg.api.saveFinancial, {
                        method:'POST', headers:{'Content-Type':'application/json'},
                        body: JSON.stringify({ type:'credit', vendor_id:payment.vendorId, amount:payment.amount, qty_rate:payment.qtyRate, purpose:payment.purpose, transaction_mode:payment.txnMode, account_id:payment.accountId, work_id:json.work_sys_id, task_id:json.auto_task_id, date:new Date().toISOString().slice(0,10) }),
                    });
                } catch(e) { console.error('[transport] vendor payment:', e); }
            }

            // Client sale entries per leg
            const clientRows  = window._tsClientRows ?? [];
            const clientSysId = window._ts.cfg.clientSysId ?? window._ts.cfg.workClientSysId ?? null;
            if (!clientSysId && includeClient && clientRows.length) {
                tsT('error', 'Client ID missing — client entry skipped. Check config.');
            }
            if (includeClient && json.task_created && json.auto_task_id && clientRows.length && clientSysId) {
                for (const row of clientRows) {
                    try {
                        await fetch(window._ts.cfg.api.saveFinancial, {
                            method:'POST', headers:{'Content-Type':'application/json'},
                            body: JSON.stringify({ type:'debit', client_id:clientSysId, amount:row.total, qty_rate:JSON.stringify({qty:row.qty,rate:row.perUnit}), purpose:`Transport — ${row.label}`, work_id:json.work_sys_id, task_id:json.auto_task_id, date:new Date().toISOString().slice(0,10) }),
                        });
                    } catch(e) { console.error('[transport] client entry:', e); }
                }
            }
            window._tsClientRows=[]; window._tsClientTotal=0;

            await window._tsReload();
            _renderTsConfirmation();
            if (typeof window.reloadConfirmedTasks==='function') window.reloadConfirmedTasks();
        } else tsT('error', json.message??'Failed');
    } catch(e) { tsT('error','Network error'); }
}