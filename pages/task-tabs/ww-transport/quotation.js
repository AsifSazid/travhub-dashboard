/**
 * FILE PATH: /pages/task-tabs/ww-transport/quotation.js
 */

// ── Leg state ─────────────────────────────────────────────────
window._ts.legs = [];

window._renderTsQuotation = function() {
    const panel      = document.getElementById('ts-panel-quotation');
    const quotations = window._ts.data?.ts_quotations ?? [];

    panel.innerHTML = `
    <div class="flex gap-4">
        <!-- LEFT: list -->
        <div style="width:220px;flex-shrink:0;">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Quotations</span>
                <button onclick="tsNewQuotation()"
                    class="flex items-center gap-1 px-2.5 py-1.5 bg-sky-600 hover:bg-sky-700 text-white rounded-lg text-xs font-semibold transition">
                    <i class="fas fa-plus text-xs"></i>New
                </button>
            </div>
            <div id="ts-q-list" class="space-y-1.5">
                ${quotations.length ? quotations.map(_tsQCard).join('') : '<p class="text-xs text-gray-300 text-center py-6">No quotations yet.</p>'}
            </div>
        </div>
        <!-- RIGHT: builder -->
        <div class="flex-1 min-w-0" id="ts-q-builder">
            <div class="flex items-center justify-center h-40 text-gray-300 text-sm">
                <div class="text-center"><i class="fas fa-plus-circle text-3xl mb-2 block opacity-30"></i>Click New or select a quotation</div>
            </div>
        </div>
    </div>`;
};

function _tsQCard(q) {
    const legs = q.legs ?? [];
    const route = legs.length ? `${legs[0].from||'?'} → ${legs[legs.length-1].to||'?'}` : '—';
    const stMap = { draft:'bg-gray-100 text-gray-500', sent:'bg-blue-50 text-blue-600', moved_to_confirmation:'bg-green-50 text-green-600', cancelled:'bg-red-50 text-red-500' };
    const dtMap = { draft:'bg-gray-400', sent:'bg-blue-400', moved_to_confirmation:'bg-green-500', cancelled:'bg-red-400' };
    const st = q.status ?? 'draft';
    return `<div class="ts-q-card cursor-pointer rounded-xl border border-gray-100 p-3 hover:border-sky-200 transition ${window._ts.activeQSysId===q.sys_id?'border-sky-400 bg-sky-50':''}"
        onclick="tsSelectQuotation('${_tse(q.sys_id)}')">
        <div class="flex items-center gap-1.5 mb-1">
            <div class="w-2 h-2 rounded-full ${dtMap[st]??'bg-gray-300'} flex-shrink-0"></div>
            <span class="font-mono text-[10px] text-gray-400">${_tse(q.sys_id)}</span>
        </div>
        <div class="text-xs font-semibold text-gray-700 truncate">${_tse(route)}</div>
        <div class="text-[11px] text-gray-400">${legs.length} leg${legs.length!==1?'s':''} · ${legs[0]?.date||'—'}</div>
        <div class="flex items-center justify-between mt-1">
            <span class="text-[11px] font-bold text-sky-600">${_tse(q.currency||'BDT')} ${_tsFmt(q.total_sell||q.total_net||0)}</span>
            <span class="text-[10px] px-1.5 py-0.5 rounded font-semibold ${stMap[st]??'bg-gray-100 text-gray-500'}">${st}</span>
        </div>
        ${q.source_note_ids?.length ? `<div class="text-[9px] text-sky-400 mt-0.5"><i class="fas fa-sticky-note" style="font-size:8px;"></i> From ${q.source_note_ids.length} note${q.source_note_ids.length>1?'s':''}</div>` : ''}
    </div>`;
}

window.tsNewQuotation = function() {
    window._ts.activeQSysId = null;
    window._ts.legs = [_tsBlankLeg(1)];
    _tsRenderQBuilder(null);
};

window.tsSelectQuotation = function(sysId) {
    window._ts.activeQSysId = sysId;
    const q = (window._ts.data?.ts_quotations ?? []).find(x => x.sys_id === sysId);
    if (!q) return;
    window._ts.legs = (q.legs ?? []).map(l => ({...l}));
    document.querySelectorAll('.ts-q-card').forEach(c => c.classList.remove('border-sky-400','bg-sky-50'));
    event?.currentTarget?.classList.add('border-sky-400','bg-sky-50');
    _tsRenderQBuilder(q);
};

function _tsBlankLeg(n) {
    return { leg_no:n, from:'', to:'', date:'', time:'', vehicle_class:'van', transfer_type:'private', pax:1, qty:1, price_basis:'per_vehicle', currency:'BDT', net_rate:0, markup_pct:0, sell_rate:0, vendor_sys_id:'', vendor_name:'', note:'' };
}

function _tsRenderQBuilder(q) {
    const builder = document.getElementById('ts-q-builder');
    if (!builder) return;
    const curr = q?.currency || 'BDT';

    builder.innerHTML = `
    <!-- Currency + Markup global -->
    <div class="grid grid-cols-3 gap-3 mb-4">
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Currency</label>
            <input id="ts-q-currency" value="${_tse(curr)}" placeholder="BDT"
                oninput="tsUpdateTotals()"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-sky-400">
        </div>
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Global Markup %</label>
            <input type="number" id="ts-q-markup" value="${q?.markup_pct||0}" min="0" step="0.01"
                oninput="tsApplyGlobalMarkup()"
                class="w-full px-3 py-2 border border-emerald-200 bg-emerald-50 rounded-lg text-sm focus:outline-none focus:border-emerald-400">
        </div>
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Note</label>
            <input id="ts-q-note" value="${_tse(q?.note||'')}" placeholder="General note"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-sky-400">
        </div>
    </div>

    <!-- Legs -->
    <div class="flex items-center justify-between mb-2">
        <label class="text-xs font-bold text-gray-500 uppercase">Legs</label>
        <button onclick="tsAddLeg()" class="text-xs text-sky-600 hover:text-sky-800 font-semibold">
            <i class="fas fa-plus mr-1"></i>Add Leg
        </button>
    </div>
    <div id="ts-legs-area" class="space-y-3 mb-4">${_tsLegsHtml()}</div>

    <!-- Totals -->
    <div class="grid grid-cols-2 gap-3 mb-4 rounded-xl border border-gray-100 p-3 bg-gray-50">
        <div><div class="text-[10px] text-gray-400 uppercase">Total Net</div><div class="font-bold text-gray-700 text-sm" id="ts-total-net">${curr} 0</div></div>
        <div><div class="text-[10px] text-gray-400 uppercase">Total Sell (Client)</div><div class="font-bold text-emerald-600 text-sm" id="ts-total-sell">${curr} 0</div></div>
    </div>

    <!-- Actions -->
    <div class="flex gap-2">
        <button onclick="tsSaveQuotation()"
            class="flex-1 py-2.5 bg-sky-600 hover:bg-sky-700 text-white rounded-lg text-sm font-semibold transition">
            <i class="fas fa-save mr-1.5"></i>${window._ts.activeQSysId ? 'Update' : 'Save Quotation'}
        </button>
        ${window._ts.activeQSysId ? `
            <button onclick="tsSendToConfirmation('${_tse(window._ts.activeQSysId)}')" title="Send to Confirmation"
                class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition whitespace-nowrap">
                <i class="fas fa-check mr-1.5"></i>Send to Confirmation
            </button>
            <button onclick="tsDeleteQuotation()" class="px-4 py-2.5 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-lg text-sm">
                <i class="fas fa-trash-alt"></i>
            </button>` : ''}
    </div>`;

    tsUpdateTotals();
}

function _tsLegsHtml() {
    if (!window._ts.legs.length) return '<p class="text-xs text-gray-300 text-center py-4">No legs yet. Click Add Leg.</p>';
    return window._ts.legs.map((l, i) => _tsLegRow(l, i)).join('');
}

function _tsLegRow(l, i) {
    return `<div class="ts-leg-row rounded-xl border border-sky-100 p-3 bg-sky-50" data-leg="${i}">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-bold text-sky-700">Leg ${i+1}</span>
            ${window._ts.legs.length > 1 ? `<button onclick="tsRemoveLeg(${i})" class="text-red-400 hover:text-red-600 text-xs"><i class="fas fa-times"></i></button>` : ''}
        </div>
        <div class="grid grid-cols-2 gap-2 mb-2">
            <div>
                <label class="text-[10px] text-gray-500 uppercase block mb-0.5">From</label>
                <input class="ts-leg-field w-full px-2 py-1.5 border border-gray-200 rounded-lg text-xs focus:outline-none focus:border-sky-400"
                    data-idx="${i}" data-key="from" value="${_tse(l.from||'')}" placeholder="Airport / Hotel / Place">
            </div>
            <div>
                <label class="text-[10px] text-gray-500 uppercase block mb-0.5">To</label>
                <input class="ts-leg-field w-full px-2 py-1.5 border border-gray-200 rounded-lg text-xs focus:outline-none focus:border-sky-400"
                    data-idx="${i}" data-key="to" value="${_tse(l.to||'')}" placeholder="Airport / Hotel / Place">
            </div>
        </div>
        <div class="grid grid-cols-4 gap-2 mb-2">
            <div>
                <label class="text-[10px] text-gray-500 uppercase block mb-0.5">Date</label>
                <input type="date" class="ts-leg-field w-full px-2 py-1.5 border border-gray-200 rounded-lg text-xs focus:outline-none focus:border-sky-400"
                    data-idx="${i}" data-key="date" value="${_tse(l.date||'')}">
            </div>
            <div>
                <label class="text-[10px] text-gray-500 uppercase block mb-0.5">Time</label>
                <input type="time" class="ts-leg-field w-full px-2 py-1.5 border border-gray-200 rounded-lg text-xs focus:outline-none focus:border-sky-400"
                    data-idx="${i}" data-key="time" value="${_tse(l.time||'')}">
            </div>
            <div>
                <label class="text-[10px] text-gray-500 uppercase block mb-0.5">Vehicle</label>
                <select class="ts-leg-field w-full px-2 py-1.5 border border-gray-200 rounded-lg text-xs focus:outline-none focus:border-sky-400"
                    data-idx="${i}" data-key="vehicle_class">
                    ${Object.entries(TS_VEHICLE_CLASSES).map(([k,v])=>`<option value="${k}" ${l.vehicle_class===k?'selected':''}>${v}</option>`).join('')}
                </select>
            </div>
            <div>
                <label class="text-[10px] text-gray-500 uppercase block mb-0.5">Type</label>
                <select class="ts-leg-field w-full px-2 py-1.5 border border-gray-200 rounded-lg text-xs focus:outline-none focus:border-sky-400"
                    data-idx="${i}" data-key="transfer_type">
                    <option value="private" ${l.transfer_type==='private'?'selected':''}>Private</option>
                    <option value="sic" ${l.transfer_type==='sic'?'selected':''}>SIC (Shared)</option>
                </select>
            </div>
        </div>
        <div class="grid grid-cols-4 gap-2 mb-2">
            <div>
                <label class="text-[10px] text-gray-500 uppercase block mb-0.5">Pax</label>
                <input type="number" class="ts-leg-field w-full px-2 py-1.5 border border-gray-200 rounded-lg text-xs focus:outline-none focus:border-sky-400"
                    data-idx="${i}" data-key="pax" data-numeric="1" value="${l.pax||1}" min="1">
            </div>
            <div>
                <label class="text-[10px] text-gray-500 uppercase block mb-0.5">Qty (vehicles)</label>
                <input type="number" class="ts-leg-field w-full px-2 py-1.5 border border-gray-200 rounded-lg text-xs focus:outline-none focus:border-sky-400"
                    data-idx="${i}" data-key="qty" data-numeric="1" value="${l.qty||1}" min="1" oninput="tsUpdateTotals()">
            </div>
            <div>
                <label class="text-[10px] text-gray-500 uppercase block mb-0.5">Net Rate</label>
                <input type="number" class="ts-leg-field w-full px-2 py-1.5 border border-gray-200 rounded-lg text-xs focus:outline-none focus:border-sky-400"
                    data-idx="${i}" data-key="net_rate" data-numeric="1" value="${l.net_rate||0}" min="0" step="0.01" oninput="tsCalcLegSell(${i})">
            </div>
            <div>
                <label class="text-[10px] text-emerald-600 uppercase block mb-0.5">Sell Rate</label>
                <input type="number" class="ts-leg-sell w-full px-2 py-1.5 border border-emerald-300 bg-emerald-50 rounded-lg text-xs focus:outline-none focus:border-emerald-500 font-bold text-emerald-700"
                    data-idx="${i}" value="${l.sell_rate||0}" min="0" step="0.01" oninput="tsUpdateTotals()">
            </div>
        </div>
        <div>
            <label class="text-[10px] text-gray-500 uppercase block mb-0.5">Note (optional)</label>
            <input class="ts-leg-field w-full px-2 py-1.5 border border-gray-200 rounded-lg text-xs focus:outline-none focus:border-sky-400"
                data-idx="${i}" data-key="note" value="${_tse(l.note||'')}" placeholder="Meet & greet, luggage limit, etc.">
        </div>
    </div>`;
}

// ── Leg event delegation ──────────────────────────────────────
document.addEventListener('input', function(e) {
    const el = e.target;
    if (el.classList.contains('ts-leg-field')) {
        const idx = +el.dataset.idx, key = el.dataset.key;
        if (idx >= 0 && key && window._ts.legs[idx] !== undefined) {
            window._ts.legs[idx][key] = el.dataset.numeric === '1' ? +(el.value||0) : el.value;
        }
    }
});

window.tsAddLeg = function() {
    window._ts.legs.push(_tsBlankLeg(window._ts.legs.length + 1));
    const area = document.getElementById('ts-legs-area');
    if (area) area.innerHTML = _tsLegsHtml();
    tsUpdateTotals();
};

window.tsRemoveLeg = function(i) {
    window._ts.legs.splice(i, 1);
    window._ts.legs.forEach((l, idx) => l.leg_no = idx + 1);
    const area = document.getElementById('ts-legs-area');
    if (area) area.innerHTML = _tsLegsHtml();
    tsUpdateTotals();
};

window.tsCalcLegSell = function(i) {
    const markup = +(document.getElementById('ts-q-markup')?.value || 0);
    const net    = +(window._ts.legs[i]?.net_rate || 0);
    const sell   = markup > 0 ? Math.round(net * (1 + markup / 100)) : net;
    const sellEl = document.querySelector(`.ts-leg-sell[data-idx="${i}"]`);
    if (sellEl) { sellEl.value = sell; }
    if (window._ts.legs[i]) window._ts.legs[i].sell_rate = sell;
    tsUpdateTotals();
};

window.tsApplyGlobalMarkup = function() {
    const markup = +(document.getElementById('ts-q-markup')?.value || 0);
    window._ts.legs.forEach((l, i) => {
        const sell = markup > 0 ? Math.round((l.net_rate||0) * (1 + markup/100)) : (l.net_rate||0);
        l.sell_rate = sell;
        const sellEl = document.querySelector(`.ts-leg-sell[data-idx="${i}"]`);
        if (sellEl) sellEl.value = sell;
    });
    tsUpdateTotals();
};

window.tsUpdateTotals = function() {
    const curr = document.getElementById('ts-q-currency')?.value || 'BDT';
    // Re-read sell rates from DOM
    document.querySelectorAll('.ts-leg-sell').forEach(el => {
        const idx = +el.dataset.idx;
        if (window._ts.legs[idx]) window._ts.legs[idx].sell_rate = +(el.value||0);
    });
    const totalNet  = window._ts.legs.reduce((s,l) => s + (+(l.net_rate||0)) * (+(l.qty||1)), 0);
    const totalSell = window._ts.legs.reduce((s,l) => s + (+(l.sell_rate||0)) * (+(l.qty||1)), 0);
    const tn = document.getElementById('ts-total-net');
    const ts = document.getElementById('ts-total-sell');
    if (tn) tn.textContent = `${curr} ${_tsFmt(totalNet)}`;
    if (ts) ts.textContent = `${curr} ${_tsFmt(totalSell)}`;
};

// ── Save / Delete / Send ──────────────────────────────────────
window.tsSaveQuotation = async function() {
    // Sync legs from DOM before saving
    document.querySelectorAll('.ts-leg-field').forEach(el => {
        const idx = +el.dataset.idx, key = el.dataset.key;
        if (window._ts.legs[idx] !== undefined && key)
            window._ts.legs[idx][key] = el.dataset.numeric==='1' ? +(el.value||0) : el.value;
    });
    document.querySelectorAll('.ts-leg-sell').forEach(el => {
        const idx = +el.dataset.idx;
        if (window._ts.legs[idx]) window._ts.legs[idx].sell_rate = +(el.value||0);
    });
    const markup = +(document.getElementById('ts-q-markup')?.value||0);
    window._ts.legs.forEach(l => l.markup_pct = markup);
    const currency = document.getElementById('ts-q-currency')?.value || 'BDT';
    const note     = document.getElementById('ts-q-note')?.value || '';

    if (!window._ts.legs.length) { tsT('error','কমপক্ষে একটা leg দিন'); return; }

    try {
        const json = await window._tsApi({
            action:          'save_quotation',
            quotation_sys_id:window._ts.activeQSysId || '',
            currency, markup_pct: markup, note,
            legs: window._ts.legs,
        });
        if (json.status === 'success') {
            htSaved: tsT('success', window._ts.activeQSysId ? 'Updated!' : 'Quotation saved!');
            if (!window._ts.activeQSysId) window._ts.activeQSysId = json.quotation_sys_id;
            await window._tsReload();
            _renderTsQuotation();
            window._ts.activeQSysId && tsSelectQuotation(window._ts.activeQSysId);
        } else { tsT('error', json.message||'Save ব্যর্থ'); }
    } catch(e) { tsT('error','Network error'); }
};
// small label fix — avoids JS parse issue with label
window.tsSaveQuotation = async function() {
    document.querySelectorAll('.ts-leg-field').forEach(el => {
        const idx = +el.dataset.idx, key = el.dataset.key;
        if (window._ts.legs[idx] !== undefined && key)
            window._ts.legs[idx][key] = el.dataset.numeric==='1' ? +(el.value||0) : el.value;
    });
    document.querySelectorAll('.ts-leg-sell').forEach(el => {
        const idx = +el.dataset.idx;
        if (window._ts.legs[idx]) window._ts.legs[idx].sell_rate = +(el.value||0);
    });
    const markup   = +(document.getElementById('ts-q-markup')?.value||0);
    const currency = document.getElementById('ts-q-currency')?.value || 'BDT';
    const note     = document.getElementById('ts-q-note')?.value || '';
    window._ts.legs.forEach(l => l.markup_pct = markup);

    if (!window._ts.legs.length) { tsT('error','কমপক্ষে একটা leg দিন'); return; }
    try {
        const json = await window._tsApi({ action:'save_quotation', quotation_sys_id:window._ts.activeQSysId||'', currency, markup_pct:markup, note, legs:window._ts.legs });
        if (json.status === 'success') {
            tsT('success', window._ts.activeQSysId ? 'Updated!' : 'Saved!');
            if (!window._ts.activeQSysId) window._ts.activeQSysId = json.quotation_sys_id;
            await window._tsReload(); _renderTsQuotation();
            window._ts.activeQSysId && tsSelectQuotation(window._ts.activeQSysId);
        } else tsT('error', json.message);
    } catch(e) { tsT('error','Network error'); }
};

window.tsDeleteQuotation = async function() {
    if (!window._ts.activeQSysId || !confirm('Delete quotation?')) return;
    try {
        const json = await window._tsApi({ action:'delete_quotation', quotation_sys_id:window._ts.activeQSysId });
        if (json.status==='success') { tsT('success','Deleted'); await window._tsReload(); window._ts.activeQSysId=null; _renderTsQuotation(); }
        else tsT('error', json.message);
    } catch(e) { tsT('error','Network error'); }
};

window.tsSendToConfirmation = async function(qSysId) {
    if (!confirm('Send to Confirmation?')) return;
    try {
        const json = await window._tsApi({ action:'add_to_confirmation', quotation_sys_id:qSysId });
        if (json.status==='success') {
            tsT('success','Sent to Confirmation!');
            await window._tsReload();
            const btn = document.querySelector('.ts-tab[data-tab="confirmation"]');
            if (btn) window._tsSwitchTab('confirmation', btn);
        } else tsT('error', json.message);
    } catch(e) { tsT('error','Network error'); }
};