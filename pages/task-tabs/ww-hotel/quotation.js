/**
 * FILE PATH: /pages/task-tabs/ww-hotel/quotation.js
 * Tab: Quotation
 */

window._renderHtQuotation = function() {
    const panel      = document.getElementById('ht-panel-quotation');
    const quotations = window._ht.data?.ht_quotations ?? [];
    const segs       = window._ht.leadSegments ?? [];
    const activeSeg  = window._ht.activeSegFilter;

    // Filter quotations by active segment
    const filteredQ  = activeSeg
        ? quotations.filter(q => q.city_seg_id === activeSeg || !q.city_seg_id)
        : quotations;

    // Segment filter bar — only show if lead has segments with city data
    const segBarHtml = segs.length > 0 ? `
    <div style="margin-bottom:12px;display:flex;flex-wrap:wrap;gap:6px;align-items:center;">
        <span style="font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;">City:</span>
        <button onclick="htSetSegFilter(null)"
            style="padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;cursor:pointer;border:1.5px solid ${!activeSeg ? '#EA580C' : '#E5E7EB'};background:${!activeSeg ? '#FFF7ED' : '#fff'};color:${!activeSeg ? '#EA580C' : '#6B7280'};">
            All
        </button>
        ${segs.map(s => `
        <button onclick="htSetSegFilter('${_hte(s.city_sys_id || s.city_name)}')"
            style="padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;cursor:pointer;border:1.5px solid ${activeSeg === (s.city_sys_id || s.city_name) ? '#EA580C' : '#E5E7EB'};background:${activeSeg === (s.city_sys_id || s.city_name) ? '#FFF7ED' : '#fff'};color:${activeSeg === (s.city_sys_id || s.city_name) ? '#EA580C' : '#6B7280'};">
            <i class="fas fa-hotel" style="font-size:9px;margin-right:3px;"></i>${_hte(s.city_name || s.hotel_name || 'Unknown')}
        </button>`).join('')}
    </div>` : '';

    panel.innerHTML = `
    ${segBarHtml}
    <div class="flex gap-4">
        <!-- LEFT: list -->
        <div style="width:220px;flex-shrink:0;">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Quotations</span>
                <button onclick="htNewQuotation()"
                    class="flex items-center gap-1 px-2.5 py-1.5 bg-orange-600 hover:bg-orange-700 text-white rounded-lg text-xs font-semibold transition">
                    <i class="fas fa-plus text-xs"></i>New
                </button>
            </div>
            <div id="ht-q-list" class="space-y-1.5">
                ${filteredQ.length ? filteredQ.map(_htQCard).join('') : '<p class="text-xs text-gray-300 text-center py-6">No quotations yet.</p>'}
            </div>
            <div id="ht-q-move-multi" class="hidden mt-3">
                <div class="text-xs text-gray-500 mb-1.5 text-center" id="ht-q-sel-count">0 selected</div>
                <button onclick="htMoveSelectedToBooking()"
                    class="w-full py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-xs font-semibold transition">
                    <i class="fas fa-arrow-right mr-1"></i>Move Selected to Booking
                </button>
            </div>
        </div>
        <!-- RIGHT: builder -->
        <div class="flex-1 min-w-0" id="ht-q-builder">
            <div class="flex items-center justify-center h-40 text-gray-300 text-sm">
                <div class="text-center">
                    <i class="fas fa-plus-circle text-3xl mb-2 block opacity-30"></i>
                    Click New or select a quotation
                </div>
            </div>
        </div>
    </div>`;
};

function _htQCard(q) {
    const sMap = { draft:'bg-gray-100 text-gray-500', sent:'bg-blue-50 text-blue-600', moved_to_booking:'bg-green-50 text-green-600', cancelled:'bg-red-50 text-red-500' };
    const dMap = { draft:'bg-gray-400', sent:'bg-blue-400', moved_to_booking:'bg-green-500', cancelled:'bg-red-400' };
    const st   = q.status ?? 'draft';
    const sel  = window._ht.qSelectedIds?.has(q.sys_id);
    return `<div class="ht-q-card cursor-pointer rounded-xl border border-gray-100 p-3 hover:border-orange-200 transition ${window._ht.activeQSysId===q.sys_id?'border-orange-400 bg-orange-50':''}"
        onclick="htSelectQuotation('${_hte(q.sys_id)}')">
        <div class="flex items-center justify-between mb-1">
            <div class="flex items-center gap-1.5">
                <div class="w-2 h-2 rounded-full ${dMap[st]??'bg-gray-300'} flex-shrink-0"></div>
                <span class="font-mono text-[10px] text-gray-400">${_hte(q.sys_id)}</span>
            </div>
            <input type="checkbox" class="w-3.5 h-3.5 cursor-pointer accent-orange-600" ${sel?'checked':''}
                onclick="event.stopPropagation();htToggleQSel('${_hte(q.sys_id)}',this)">
        </div>
        <div class="text-xs font-semibold text-gray-700 truncate">${_hte(q.hotel_name||'—')} ${q.star_rating?'<span style="color:#F59E0B;">'+('★'.repeat(q.star_rating))+'</span>':''}</div>
        <div class="text-[11px] text-gray-400">${_hte(q.city||'')}${q.city&&q.check_in?' · ':' '}${_hte(q.check_in||'')} ${q.nights?'('+q.nights+'N)':''}</div>
        ${q.city_seg_id ? `<div class="text-[10px] text-orange-400 mt-0.5"><i class="fas fa-map-marker-alt" style="font-size:8px;"></i> ${_hte(q.city_seg_id)}</div>` : ''}
        <div class="flex items-center justify-between mt-1">
            <span class="text-[11px] font-bold text-orange-600">${_hte(q.currency||'BDT')} ${_htFmtN(q.total_sell||q.total_net||0)}</span>
            <span class="text-[10px] px-1.5 py-0.5 rounded font-semibold ${sMap[st]??'bg-gray-100 text-gray-500'}">${st}</span>
        </div>
    </div>`;
}

window.htToggleQSel = function(sysId, cb) {
    if (!window._ht.qSelectedIds) window._ht.qSelectedIds = new Set();
    if (cb.checked) window._ht.qSelectedIds.add(sysId);
    else            window._ht.qSelectedIds.delete(sysId);
    const btn = document.getElementById('ht-q-move-multi');
    const cnt = document.getElementById('ht-q-sel-count');
    if (window._ht.qSelectedIds.size > 0) {
        btn?.classList.remove('hidden');
        if (cnt) cnt.textContent = `${window._ht.qSelectedIds.size} selected`;
    } else {
        btn?.classList.add('hidden');
    }
};

// ── Segment filter ───────────────────────────────────────────
window.htSetSegFilter = function(segId) {
    window._ht.activeSegFilter = segId || null;
    _renderHtQuotation();
};

// ── Auto-fill city/hotel/dates from selected segment ─────────
window.htAutoFillFromSeg = function(sel) {
    const opt = sel?.options[sel.selectedIndex];
    if (!opt || !opt.value) return;
    const cityInp    = document.getElementById('ht-q-city');
    const hotelInp   = document.getElementById('ht-q-hotel-name');
    const checkinInp = document.getElementById('ht-q-checkin');
    const checkoutInp= document.getElementById('ht-q-checkout');
    if (cityInp    && !cityInp.value    && opt.dataset.city)     cityInp.value    = opt.dataset.city;
    if (hotelInp   && !hotelInp.value   && opt.dataset.hotel)    hotelInp.value   = opt.dataset.hotel;
    if (checkinInp && !checkinInp.value && opt.dataset.checkin)  checkinInp.value = opt.dataset.checkin;
    if (checkoutInp&& !checkoutInp.value&& opt.dataset.checkout) checkoutInp.value= opt.dataset.checkout;
    if (checkinInp?.value && checkoutInp?.value) htCalcNightsAuto();
};

window.htNewQuotation = function() {
    window._ht.activeQSysId = null;
    window._ht.activeBSysId = null;
    _htRenderQBuilder(null);
};

window.htSelectQuotation = function(sysId) {
    window._ht.activeQSysId = sysId;
    window._ht.activeBSysId = null;
    const q = (window._ht.data?.ht_quotations ?? []).find(x => x.sys_id === sysId);
    if (!q) return;
    document.querySelectorAll('.ht-q-card').forEach(c => c.classList.remove('border-orange-400','bg-orange-50'));
    event?.currentTarget?.classList.add('border-orange-400','bg-orange-50');
    _htRenderQBuilder(q);
};

function _htRenderQBuilder(q) {
    const builder = document.getElementById('ht-q-builder');
    if (!builder) return;
    const nights = q?.nights || _htCalcNights(q?.check_in, q?.check_out) || 0;
    const rooms  = q?.rooms  || 1;
    const sellRate = q?.sell_rate || _htCalcSell(q?.net_rate, q?.markup_pct) || 0;

    builder.innerHTML = `
    <div class="grid grid-cols-2 gap-3 mb-3">
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Hotel Name</label>
            <input id="ht-q-hotel-name" value="${_hte(q?.hotel_name||'')}" placeholder="e.g. Marriott Riyadh"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-orange-400">
        </div>
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Star Rating</label>
            <div class="flex gap-2 pt-1.5">
                ${[1,2,3,4,5].map(s=>`<label class="cursor-pointer">
                    <input type="radio" name="ht-q-star" value="${s}" ${(q?.star_rating||0)===s?'checked':''} class="hidden">
                    <span class="text-lg ${(q?.star_rating||0)>=s?'text-amber-400':'text-gray-200'} hover:text-amber-400"
                        onclick="htStarClick(${s})">★</span>
                </label>`).join('')}
            </div>
        </div>
    </div>

    <!-- City segment tag — links quotation to a lead segment -->
    ${(() => {
        const segs = window._ht.leadSegments ?? [];
        if (!segs.length) return '';
        const cur = q?.city_seg_id ?? '';
        return `<div class="mb-3">
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Link to City <span class="text-gray-300 font-normal normal-case">(from lead)</span></label>
            <select id="ht-q-seg-id" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-orange-400"
                onchange="htAutoFillFromSeg(this)">
                <option value="">— No segment link —</option>
                ${segs.map(s => {
                    const segId = s.city_sys_id || s.city_name;
                    const label = [s.city_name, s.hotel_name].filter(Boolean).join(' / ');
                    return \`<option value="\${_hte(segId)}" \${cur===segId?'selected':''}
                        data-city="\${_hte(s.city_name)}" data-hotel="\${_hte(s.hotel_name)}"
                        data-checkin="\${_hte(s.check_in)}" data-checkout="\${_hte(s.check_out)}">\${_hte(label)}</option>\`;
                }).join('')}
            </select>
        </div>`;
    })()}

    <div class="grid grid-cols-3 gap-3 mb-3">
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">City</label>
            <input id="ht-q-city" value="${_hte(q?.city||'')}" placeholder="e.g. Riyadh"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-orange-400">
        </div>
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Country</label>
            <input id="ht-q-country" value="${_hte(q?.country||'')}" placeholder="e.g. Saudi Arabia"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-orange-400">
        </div>
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Currency</label>
            <input id="ht-q-currency" value="${_hte(q?.currency||'BDT')}" placeholder="BDT"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-orange-400">
        </div>
    </div>

    <div class="grid grid-cols-3 gap-3 mb-3">
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Check-in</label>
            <input type="date" id="ht-q-checkin" value="${_hte(q?.check_in||'')}"
                oninput="htCalcNightsAuto()"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-orange-400">
        </div>
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Check-out</label>
            <input type="date" id="ht-q-checkout" value="${_hte(q?.check_out||'')}"
                oninput="htCalcNightsAuto()"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-orange-400">
        </div>
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Nights</label>
            <input type="number" id="ht-q-nights" value="${nights}" min="1"
                oninput="htCalcTotals()"
                class="w-full px-3 py-2 border border-orange-200 bg-orange-50 rounded-lg text-sm focus:outline-none focus:border-orange-400 font-bold">
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 mb-3">
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Room Type</label>
            <input id="ht-q-room-type" value="${_hte(q?.room_type||'')}" placeholder="e.g. Deluxe King"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-orange-400">
        </div>
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Bed Config</label>
            <select id="ht-q-bed-config" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-orange-400">
                ${['King Bed','Queen Bed','Double Bed','Twin Beds','Single Bed'].map(b=>`<option ${q?.bed_config===b?'selected':''}>${b}</option>`).join('')}
                <option ${!['King Bed','Queen Bed','Double Bed','Twin Beds','Single Bed'].includes(q?.bed_config||'')?'selected':''} value="${_hte(q?.bed_config||'')}">Other</option>
            </select>
        </div>
    </div>

    <div class="grid grid-cols-4 gap-3 mb-3">
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Room Size (sqm)</label>
            <input type="number" id="ht-q-size" value="${q?.size_sqm||''}" placeholder="e.g. 35"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-orange-400">
        </div>
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Max Adults</label>
            <input type="number" id="ht-q-max-adults" value="${q?.max_occupancy||2}" min="1"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-orange-400">
        </div>
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Rooms</label>
            <input type="number" id="ht-q-rooms" value="${rooms}" min="1"
                oninput="htCalcTotals()"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-orange-400">
        </div>
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Meal Plan</label>
            <select id="ht-q-meal-plan" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-orange-400">
                ${Object.entries(HT_MEAL_PLANS).map(([k,v])=>`<option value="${k}" ${q?.meal_plan===k?'selected':''}>${v}</option>`).join('')}
            </select>
        </div>
    </div>

    <!-- Pricing -->
    <div class="grid grid-cols-3 gap-3 mb-3">
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Net Rate / Night</label>
            <input type="number" id="ht-q-net-rate" value="${q?.net_rate||0}" min="0" step="0.01"
                oninput="htCalcTotals()"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-orange-400">
        </div>
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Markup % <span class="text-gray-300 font-normal normal-case">(client)</span></label>
            <input type="number" id="ht-q-markup" value="${q?.markup_pct||0}" min="0" step="0.01"
                oninput="htCalcTotals()"
                class="w-full px-3 py-2 border border-emerald-200 bg-emerald-50 rounded-lg text-sm focus:outline-none focus:border-emerald-400">
        </div>
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Sell Rate / Night</label>
            <input type="number" id="ht-q-sell-rate" value="${sellRate}" min="0" step="0.01"
                oninput="htCalcTotals()"
                class="w-full px-3 py-2 border border-emerald-300 bg-emerald-50 rounded-lg text-sm focus:outline-none focus:border-emerald-500 font-bold text-emerald-700">
        </div>
    </div>

    <!-- Totals -->
    <div class="grid grid-cols-2 gap-3 mb-3 rounded-xl border border-gray-100 p-3 bg-gray-50">
        <div>
            <div class="text-[10px] text-gray-400 uppercase">Total Net</div>
            <div class="font-bold text-gray-700 text-sm" id="ht-q-total-net">${_hte(q?.currency||'BDT')} ${_htFmtN(q?.total_net||0)}</div>
        </div>
        <div>
            <div class="text-[10px] text-gray-400 uppercase">Total Sell (Client)</div>
            <div class="font-bold text-emerald-600 text-sm" id="ht-q-total-sell">${_hte(q?.currency||'BDT')} ${_htFmtN(q?.total_sell||0)}</div>
        </div>
    </div>

    <div class="mb-3">
        <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Note</label>
        <textarea id="ht-q-note" rows="2" placeholder="Cancellation policy, inclusions, etc."
            class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm resize-none focus:outline-none focus:border-orange-400">${_hte(q?.note||'')}</textarea>
    </div>

    <!-- Actions -->
    <div class="flex gap-2">
        <button onclick="htSaveQuotation()"
            class="flex-1 py-2.5 bg-orange-600 hover:bg-orange-700 text-white rounded-lg text-sm font-semibold transition">
            <i class="fas fa-save mr-1.5"></i>${window._ht.activeQSysId ? 'Update' : 'Save Quotation'}
        </button>
        ${window._ht.activeQSysId ? `
            <button onclick="htMoveToBooking()" title="Send to Booking"
                class="px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-semibold transition whitespace-nowrap">
                <i class="fas fa-arrow-right mr-1.5"></i>Send to Booking
            </button>
            <button onclick="htDeleteQuotation()" class="px-4 py-2.5 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-lg text-sm">
                <i class="fas fa-trash-alt"></i>
            </button>` : ''}
    </div>`;
}

// ── Calc helpers ──────────────────────────────────────────────
window.htStarClick = function(n) {
    document.querySelector(`input[name="ht-q-star"][value="${n}"]`).checked = true;
    document.querySelectorAll('span[onclick^="htStarClick"]').forEach((s, i) => {
        s.className = `text-lg ${i < n ? 'text-amber-400' : 'text-gray-200'} hover:text-amber-400`;
    });
};

window.htCalcNightsAuto = function() {
    const ci = document.getElementById('ht-q-checkin')?.value;
    const co = document.getElementById('ht-q-checkout')?.value;
    const n  = _htCalcNights(ci, co);
    if (n > 0) { const ni = document.getElementById('ht-q-nights'); if (ni) ni.value = n; }
    htCalcTotals();
};

window.htCalcTotals = function() {
    const net    = +(document.getElementById('ht-q-net-rate')?.value  || 0);
    const markup = +(document.getElementById('ht-q-markup')?.value    || 0);
    const nights = +(document.getElementById('ht-q-nights')?.value    || 1);
    const rooms  = +(document.getElementById('ht-q-rooms')?.value     || 1);
    const curr   = document.getElementById('ht-q-currency')?.value || 'BDT';

    // If sell_rate manually edited, use it; otherwise derive from net + markup
    const sellEl   = document.getElementById('ht-q-sell-rate');
    const sellManual = sellEl ? +(sellEl.value || 0) : 0;
    const sell     = sellManual > 0 ? sellManual : _htCalcSell(net, markup);
    if (sellEl && sellManual === 0 && markup > 0) sellEl.value = sell;

    const totalNet  = Math.round(net  * nights * rooms);
    const totalSell = Math.round(sell * nights * rooms);

    const tn = document.getElementById('ht-q-total-net');
    const ts = document.getElementById('ht-q-total-sell');
    if (tn) tn.textContent = `${curr} ${_htFmtN(totalNet)}`;
    if (ts) ts.textContent = `${curr} ${_htFmtN(totalSell)}`;
};

// ── Save / Delete / Move ──────────────────────────────────────
window.htSaveQuotation = async function() {
    const starEl   = document.querySelector('input[name="ht-q-star"]:checked');
    const nights   = +(document.getElementById('ht-q-nights')?.value    || 0);
    const rooms    = +(document.getElementById('ht-q-rooms')?.value     || 1);
    const netRate  = +(document.getElementById('ht-q-net-rate')?.value  || 0);
    const markup   = +(document.getElementById('ht-q-markup')?.value    || 0);
    const sellRate = +(document.getElementById('ht-q-sell-rate')?.value || _htCalcSell(netRate, markup));
    const currency = document.getElementById('ht-q-currency')?.value || 'BDT';

    const segEl  = document.getElementById('ht-q-seg-id');
    const citySegId = segEl?.value || '';
    // Auto-fill city from segment if city field is empty
    if (citySegId && segEl) {
        const opt = segEl.options[segEl.selectedIndex];
        const cityInp = document.getElementById('ht-q-city');
        if (cityInp && !cityInp.value && opt?.dataset.city) cityInp.value = opt.dataset.city;
    }
    const body = {
        action:           window._ht.activeQSysId ? 'save_quotation' : 'save_quotation',
        quotation_sys_id: window._ht.activeQSysId || '',
        city_seg_id:      citySegId,
        hotel_name:       document.getElementById('ht-q-hotel-name')?.value || '',
        city:             document.getElementById('ht-q-city')?.value       || '',
        country:          document.getElementById('ht-q-country')?.value    || '',
        star_rating:      starEl ? +starEl.value : 0,
        check_in:         document.getElementById('ht-q-checkin')?.value    || '',
        check_out:        document.getElementById('ht-q-checkout')?.value   || '',
        nights,
        room_type:        document.getElementById('ht-q-room-type')?.value  || '',
        bed_config:       document.getElementById('ht-q-bed-config')?.value || '',
        size_sqm:         +(document.getElementById('ht-q-size')?.value||0) || null,
        max_occupancy:    +(document.getElementById('ht-q-max-adults')?.value||2),
        meal_plan:        document.getElementById('ht-q-meal-plan')?.value  || 'bb',
        rooms,
        currency,
        net_rate:         netRate,
        markup_pct:       markup,
        sell_rate:        sellRate,
        total_net:        Math.round(netRate  * nights * rooms),
        total_sell:       Math.round(sellRate * nights * rooms),
        note:             document.getElementById('ht-q-note')?.value || '',
    };

    try {
        const json = await window._htApi(body);
        if (json.status === 'success') {
            htT('success', window._ht.activeQSysId ? 'Updated!' : 'Quotation saved!');
            if (!window._ht.activeQSysId) window._ht.activeQSysId = json.quotation_sys_id;
            await window._htReload();
            _renderHtQuotation();
            window._ht.activeQSysId && htSelectQuotation(window._ht.activeQSysId);
        } else { htT('error', json.message || 'Save ব্যর্থ'); }
    } catch(e) { htT('error', 'Network error'); }
};

window.htDeleteQuotation = async function() {
    if (!window._ht.activeQSysId || !confirm('Delete quotation?')) return;
    try {
        const json = await window._htApi({ action:'delete_quotation', quotation_sys_id:window._ht.activeQSysId });
        if (json.status === 'success') { htT('success','Deleted'); await window._htReload(); window._ht.activeQSysId=null; _renderHtQuotation(); }
        else htT('error', json.message);
    } catch(e) { htT('error','Network error'); }
};

window.htMoveToBooking = async function() {
    if (!window._ht.activeQSysId || !confirm('Move to Booking?')) return;
    try {
        const json = await window._htApi({ action:'move_to_booking', quotation_sys_id:window._ht.activeQSysId });
        if (json.status === 'success') {
            htT('success','Moved to Booking!');
            await window._htReload();
            window._ht.activeQSysId = null;
            _renderHtQuotation();
            const btn = document.querySelector('.ht-tab[data-tab="booking"]');
            if (btn) window._htSwitchTab('booking', btn);
        } else { htT('error', json.message); }
    } catch(e) { htT('error','Network error'); }
};

window.htMoveSelectedToBooking = async function() {
    const ids = [...(window._ht.qSelectedIds ?? [])];
    if (!ids.length || !confirm(`Move ${ids.length} quotation(s) to Booking?`)) return;
    let ok = 0;
    for (const id of ids) {
        try {
            const j = await window._htApi({ action:'move_to_booking', quotation_sys_id:id });
            if (j.status === 'success') ok++;
        } catch(e) {}
    }
    window._ht.qSelectedIds.clear();
    await window._htReload();
    _renderHtQuotation();
    if (ok > 0) {
        htT('success', `${ok} quotation(s) moved!`);
        const btn = document.querySelector('.ht-tab[data-tab="booking"]');
        if (btn) window._htSwitchTab('booking', btn);
    }
};