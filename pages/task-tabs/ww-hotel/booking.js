/**
 * FILE PATH: /pages/task-tabs/ww-hotel/booking.js
 * Tab: Booking
 */

window._renderHtBooking = function() {
    const panel    = document.getElementById('ht-panel-booking');
    const bookings = window._ht.data?.ht_bookings ?? [];

    panel.innerHTML = `
    <div class="flex gap-4">
        <!-- LEFT: list -->
        <div style="width:220px;flex-shrink:0;">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Bookings</span>
                <button onclick="htNewBooking()"
                    class="flex items-center gap-1 px-2.5 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-xs font-semibold transition">
                    <i class="fas fa-plus text-xs"></i>New
                </button>
            </div>
            <div id="ht-b-list" class="space-y-1.5">
                ${bookings.length ? bookings.map(_htBCard).join('') : '<p class="text-xs text-gray-300 text-center py-4">No bookings yet.<br>Move from Quotation or create New.</p>'}
            </div>
        </div>
        <!-- RIGHT: builder -->
        <div class="flex-1 min-w-0" id="ht-b-builder">
            <div class="flex items-center justify-center h-40 text-gray-300 text-sm">
                <div class="text-center"><i class="fas fa-bookmark text-3xl mb-2 block opacity-30"></i>Select or create a booking</div>
            </div>
        </div>
    </div>`;
};

function _htBCard(b) {
    const sMap = { tentative:'bg-yellow-50 text-yellow-700', confirmed:'bg-green-50 text-green-700', failed:'bg-red-50 text-red-600', cancelled:'bg-gray-100 text-gray-500' };
    const dMap = { tentative:'bg-yellow-400', confirmed:'bg-green-500', failed:'bg-red-400', cancelled:'bg-gray-400' };
    const st   = b.status ?? 'tentative';
    const inConf = (window._ht.data?.ht_confirmations ?? []).some(c => c.booking_sys_id === b.sys_id && !['failed','cancelled'].includes(c.status));
    return `<div class="ht-b-card cursor-pointer rounded-xl border border-gray-100 p-3 hover:border-green-200 transition ${window._ht.activeBSysId===b.sys_id?'border-green-400 bg-green-50':''}"
        onclick="htSelectBooking('${_hte(b.sys_id)}')">
        <div class="flex items-center justify-between mb-1">
            <div class="flex items-center gap-1.5">
                <div class="w-2 h-2 rounded-full ${dMap[st]??'bg-gray-400'} flex-shrink-0"></div>
                <span class="font-mono text-[10px] text-green-600">${_hte(b.sys_id)}</span>
            </div>
            ${inConf ? '<span class="text-[9px] bg-indigo-100 text-indigo-600 px-1.5 py-0.5 rounded font-bold">In Conf</span>' : ''}
        </div>
        <div class="text-xs font-semibold text-gray-700 truncate">${_hte(b.hotel_name||'—')}</div>
        ${b.booking_ref ? `<div class="text-[11px] text-gray-400">Ref: ${_hte(b.booking_ref)}</div>` : ''}
        <div class="text-[11px] text-gray-400">${_hte(b.check_in||'')} ${b.nights?'('+b.nights+'N)':''}</div>
        <div class="flex items-center justify-between mt-1">
            <span class="text-[11px] font-bold text-green-600">${_hte(b.currency||'BDT')} ${_htFmtN(b.total_sell||b.total_net||0)}</span>
            <span class="text-[10px] px-1.5 py-0.5 rounded font-semibold ${sMap[st]??'bg-gray-100 text-gray-500'}">${st}</span>
        </div>
    </div>`;
}

window.htNewBooking = function() {
    window._ht.activeBSysId = null;
    window._ht.activeQSysId = null;
    _htRenderBBuilder(null);
    _htLoadBTravelers();
};

window.htSelectBooking = function(sysId) {
    window._ht.activeBSysId = sysId;
    window._ht.activeQSysId = null;
    const b = (window._ht.data?.ht_bookings ?? []).find(x => x.sys_id === sysId);
    if (!b) return;
    document.querySelectorAll('.ht-b-card').forEach(c => c.classList.remove('border-green-400','bg-green-50'));
    event?.currentTarget?.classList.add('border-green-400','bg-green-50');
    _htRenderBBuilder(b);
    _htLoadBTravelers();
};

function _htRenderBBuilder(b) {
    const builder = document.getElementById('ht-b-builder');
    if (!builder) return;
    const alreadyConf = b ? (window._ht.data?.ht_confirmations ?? []).some(c => c.booking_sys_id === b.sys_id && !['failed','cancelled'].includes(c.status)) : false;

    builder.innerHTML = `
    ${b ? `<div class="flex items-center gap-2 mb-4">
        <span class="text-xs font-bold text-gray-400 uppercase">Booking:</span>
        <span class="font-mono text-xs text-green-600">${_hte(b.sys_id)}</span>
        <span class="ml-auto text-[10px] px-2 py-0.5 rounded bg-yellow-50 text-yellow-700 font-semibold">${b.status}</span>
    </div>` : ''}

    <!-- Hotel info (read from quotation, shown as info) -->
    <div class="grid grid-cols-2 gap-3 mb-3 p-3 bg-gray-50 rounded-xl border border-gray-100 text-xs">
        <div><span class="text-gray-400">Hotel:</span> <b>${_hte(b?.hotel_name||'—')}</b> ${b?.star_rating?'<span style="color:#F59E0B;">'+('★'.repeat(b.star_rating))+'</span>':''}</div>
        <div><span class="text-gray-400">City:</span> <b>${_hte(b?.city||'—')}</b></div>
        <div><span class="text-gray-400">Check-in:</span> <b>${_hte(b?.check_in||'—')}</b></div>
        <div><span class="text-gray-400">Check-out:</span> <b>${_hte(b?.check_out||'—')}</b> (${b?.nights||0} nights)</div>
        <div><span class="text-gray-400">Room:</span> <b>${_hte(b?.room_type||'—')}</b> — ${_hte(b?.bed_config||'')}</div>
        <div><span class="text-gray-400">Meal:</span> <b>${HT_MEAL_PLANS[b?.meal_plan]||b?.meal_plan||'—'}</b></div>
        <div><span class="text-gray-400">Rooms:</span> <b>${b?.rooms||1}</b></div>
        <div><span class="text-gray-400">Total Sell:</span> <b class="text-emerald-600">${_hte(b?.currency||'BDT')} ${_htFmtN(b?.total_sell||0)}</b></div>
    </div>

    <!-- Booking-specific fields -->
    <div class="grid grid-cols-3 gap-3 mb-3">
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Booking Ref</label>
            <input id="ht-b-ref" value="${_hte(b?.booking_ref||'')}" placeholder="Hotel confirmation ref"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-green-400">
        </div>
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">PCN</label>
            <input id="ht-b-pcn" value="${_hte(b?.pcn||'')}" placeholder="Property conf. no."
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-green-400">
        </div>
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">HCN</label>
            <input id="ht-b-hcn" value="${_hte(b?.hcn||'')}" placeholder="Hotel voucher no."
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-green-400">
        </div>
    </div>

    <!-- Travelers -->
    <div class="mb-3">
        <label class="text-xs font-bold text-gray-500 uppercase block mb-2">Guests (Travelers)</label>
        <div id="ht-b-travelers" class="text-xs text-gray-300 text-center py-2">
            <i class="fas fa-spinner fa-spin"></i>
        </div>
    </div>

    <div class="mb-3">
        <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Note</label>
        <textarea id="ht-b-note" rows="2" placeholder="Special requests, dietary needs, etc."
            class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm resize-none focus:outline-none focus:border-green-400">${_hte(b?.note||'')}</textarea>
    </div>

    <div class="flex gap-2">
        <button onclick="htSaveBooking()"
            class="flex-1 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-semibold transition">
            <i class="fas fa-save mr-1.5"></i>${window._ht.activeBSysId ? 'Update Booking' : 'Save Booking'}
        </button>
        ${window._ht.activeBSysId ? `
            ${alreadyConf
                ? `<button disabled class="px-4 py-2.5 bg-gray-100 text-gray-400 rounded-lg text-sm cursor-not-allowed whitespace-nowrap text-xs"><i class="fas fa-check-circle mr-1"></i>In Confirmation</button>`
                : `<button onclick="htSendToConfirmation('${_hte(b.sys_id)}')"
                    class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition whitespace-nowrap">
                    <i class="fas fa-check mr-1.5"></i>Send to Confirmation
                </button>`}
            <button onclick="htDeleteBooking()" class="px-4 py-2.5 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-lg text-sm">
                <i class="fas fa-trash-alt"></i>
            </button>` : ''}
    </div>`;
}

window._htLoadBTravelers = async function() {
    const el = document.getElementById('ht-b-travelers');
    if (!el) return;
    try {
        const res  = await fetch(`${window._ht.cfg.api.workTravelers}?action=list&work_sys_id=${encodeURIComponent(window._ht.cfg.workSysId)}`);
        const json = await res.json();
        const travelers = json.status === 'success' ? (json.data ?? []) : [];
        if (!travelers.length) { el.innerHTML = '<p class="text-gray-300 text-xs text-center py-1">No travelers linked</p>'; return; }

        el.innerHTML = `<div class="overflow-x-auto rounded-lg border border-gray-100">
            <table class="w-full text-xs">
                <thead><tr class="bg-gray-50 text-gray-500 text-[10px] uppercase">
                    <th class="px-2 py-1.5 text-left">Name</th>
                    <th class="px-2 py-1.5 text-center">Passport</th>
                    <th class="px-2 py-1.5 text-center">Expiry</th>
                    <th class="px-2 py-1.5 text-center">DOB</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-50">
                    ${travelers.map(t => {
                        const pi  = t.passport_info ?? '[]';
                        const pArr= Array.isArray(pi) ? pi : (typeof pi === 'string' ? JSON.parse(pi||'[]') : []);
                        const bio = pArr.find?.(p => p.page_type === 'bio_page')?.bio_info ?? {};
                        const ppNo   = bio.passport_number ?? bio.passport_no ?? t.passport_no ?? '—';
                        const expiry = bio.date_of_expiry ?? '—';
                        const dob    = bio.date_of_birth  ?? t.date_of_birth ?? '—';
                        const copyCell = (v) => `onclick="navigator.clipboard.writeText('${_hte(v)}');htT('success','Copied!')"`;
                        return `<tr class="hover:bg-gray-50 text-gray-700">
                            <td class="px-2 py-1.5 font-medium cursor-pointer hover:bg-indigo-50" ${copyCell(t.name??'')}>${_hte(t.name??'—')}</td>
                            <td class="px-2 py-1.5 text-center font-mono cursor-pointer hover:bg-indigo-50" ${copyCell(ppNo)}>${_hte(ppNo)}</td>
                            <td class="px-2 py-1.5 text-center cursor-pointer hover:bg-indigo-50" ${copyCell(expiry)}>${_hte(expiry)}</td>
                            <td class="px-2 py-1.5 text-center cursor-pointer hover:bg-indigo-50" ${copyCell(dob)}>${_hte(dob)}</td>
                        </tr>`;
                    }).join('')}
                </tbody>
            </table>
        </div>`;
    } catch(e) { el.innerHTML = '<p class="text-red-300 text-xs text-center">Load failed</p>'; }
};

window.htSaveBooking = async function() {
    const b = (window._ht.data?.ht_bookings ?? []).find(x => x.sys_id === window._ht.activeBSysId) || {};
    try {
        const json = await window._htApi({
            action:          window._ht.activeBSysId ? 'save_booking' : 'save_booking',
            booking_sys_id:  window._ht.activeBSysId || '',
            // Carry over quotation fields
            hotel_sys_id:    b.hotel_sys_id  || '',
            hotel_name:      b.hotel_name    || '',
            city:            b.city          || '',
            country:         b.country       || '',
            star_rating:     b.star_rating   || 0,
            check_in:        b.check_in      || '',
            check_out:       b.check_out     || '',
            nights:          b.nights        || 0,
            room_type:       b.room_type     || '',
            bed_config:      b.bed_config    || '',
            meal_plan:       b.meal_plan     || 'bb',
            rooms:           b.rooms         || 1,
            currency:        b.currency      || 'BDT',
            net_rate:        b.net_rate      || 0,
            markup_pct:      b.markup_pct    || 0,
            sell_rate:       b.sell_rate     || 0,
            total_net:       b.total_net     || 0,
            total_sell:      b.total_sell    || 0,
            quotation_sys_id:b.quotation_sys_id || '',
            // Booking-specific
            booking_ref:     document.getElementById('ht-b-ref')?.value || '',
            pcn:             document.getElementById('ht-b-pcn')?.value || '',
            hcn:             document.getElementById('ht-b-hcn')?.value || '',
            note:            document.getElementById('ht-b-note')?.value || '',
        });
        if (json.status === 'success') {
            htT('success', window._ht.activeBSysId ? 'Updated!' : 'Booking saved!');
            if (!window._ht.activeBSysId) window._ht.activeBSysId = json.booking_sys_id;
            await window._htReload();
            _renderHtBooking();
        } else { htT('error', json.message); }
    } catch(e) { htT('error','Network error'); }
};

window.htDeleteBooking = async function() {
    if (!window._ht.activeBSysId || !confirm('Delete booking?')) return;
    try {
        const json = await window._htApi({ action:'delete_booking', booking_sys_id:window._ht.activeBSysId });
        if (json.status === 'success') { htT('success','Deleted'); await window._htReload(); window._ht.activeBSysId=null; _renderHtBooking(); }
        else htT('error', json.message);
    } catch(e) { htT('error','Network error'); }
};

window.htSendToConfirmation = async function(bSysId) {
    if (!confirm('Send to Confirmation?')) return;
    try {
        const json = await window._htApi({ action:'add_to_confirmation', booking_sys_id:bSysId });
        if (json.status === 'success') {
            htT('success','Sent to Confirmation!');
            await window._htReload();
            const btn = document.querySelector('.ht-tab[data-tab="confirmation"]');
            if (btn) window._htSwitchTab('confirmation', btn);
        } else { htT('error', json.message); }
    } catch(e) { htT('error','Network error'); }
};