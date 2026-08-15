/**
 * FILE PATH: /pages/task-tabs/ww-air-tickets/booking.js
 * Tab: Booking
 */

// ═══════════════════════════════════════════════════════════════
// TAB 3 — BOOKING
// ═══════════════════════════════════════════════════════════════
window._renderBooking = function _renderBooking() {
    const panel      = document.getElementById('at-panel-booking');
    const bookings   = window._at.data?.at_bookings   ?? [];
    const quotations = window._at.data?.at_quotations ?? [];

    panel.innerHTML = `
    <div class="flex gap-4">
        <!-- LEFT: All quotations as booking-phase list -->
        <div style="width:220px;flex-shrink:0;">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Bookings</span>
                <button onclick="atNewBooking()"
                    class="flex items-center gap-1 px-2.5 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-xs font-semibold transition">
                    <i class="fas fa-plus text-xs"></i>New
                </button>
            </div>
            <div id="at-b-list" class="space-y-1.5">
                ${bookings.length
                    ? bookings.map(_bCard).join('')
                    : '<p class="text-xs text-gray-300 text-center py-4">No bookings yet.<br>Move from Quotation or create New.</p>'}
            </div>
            ${(() => {
                const quotations = window._at.data?.at_quotations ?? [];
                const latestPerBooking = {};
                bookings.forEach(b => {
                    const revisions = quotations.filter(q => q.source_booking === b.sys_id);
                    if (revisions.length) {
                        latestPerBooking[b.sys_id] = revisions[revisions.length - 1];
                    } else {
                        const orig = quotations.find(q => q.status === 'moved_to_booking' && !q.source_booking);
                        if (orig) latestPerBooking[b.sys_id] = orig;
                    }
                });
                const refs = Object.values(latestPerBooking);
                if (!refs.length) return '';
                return `<div class="mt-3 pt-3 border-t border-gray-100">
                    <p class="text-[10px] font-bold text-gray-400 uppercase mb-2">Latest Quotation Refs</p>
                    <div class="space-y-1">
                        ${refs.map(q => `
                        <div class="text-[10px] text-gray-400 flex items-center gap-1.5 py-1 border-b border-gray-50">
                            <span class="font-mono text-indigo-400">${_e(q.sys_id)}</span>
                            <span class="flex-1 truncate text-gray-300">${_e(q.airline||q.title||'')}</span>
                            <span class="text-[9px] font-bold ${q.source_booking?'text-blue-400':'text-green-500'}">${q.source_booking?'Rev':'Orig'}</span>
                        </div>`).join('')}
                    </div>
                </div>`;
            })()}
        </div>

        <!-- RIGHT: booking builder -->
        <div class="flex-1 min-w-0" id="at-b-builder">
            <div class="flex items-center justify-center h-40 text-gray-300 text-sm">
                <div class="text-center"><i class="fas fa-bookmark text-3xl mb-2 block opacity-30"></i>Select or create a booking</div>
            </div>
        </div>
    </div>`;

    _gdsInjectPanel(panel, '3');
}

window._bCard = function _bCard(b) {
    const sMap = { tentative:'at-pill-tentative', confirmed:'at-pill-confirmed', failed:'at-pill-failed', cancelled:'at-pill-cancelled' };
    const sDot = { tentative:'bg-yellow-400', confirmed:'bg-green-500', failed:'bg-red-400', cancelled:'bg-gray-400' };
    const route = (b.segments_json?.[0]?.route ?? b.form_data?.route ?? '')
                + (b.segments_json?.length > 1 ? ' +' + (b.segments_json.length - 1) : '');
    const typePill = b.type === 'soto'
        ? '<span class="at-pill at-pill-sent" style="font-size:.6rem">SOTO</span>'
        : '<span class="at-pill at-pill-draft" style="font-size:.6rem">GDS</span>';
    const isInConf = (window._at.data?.at_confirmations ?? []).some(c => c.booking_sys_id === b.sys_id && c.status !== 'failed' && c.status !== 'cancelled');
    return `<div class="at-q-card ${window._at.activeBSysId===b.sys_id?'active':''}" onclick="atSelectBooking('${_e(b.sys_id)}')">
        <div class="flex items-center justify-between mb-1">
            <div class="flex items-center gap-1.5">
                <div class="w-2 h-2 rounded-full ${sDot[b.status]??'bg-gray-400'} flex-shrink-0"></div>
                <span class="font-mono text-[10px] text-green-600">${_e(b.sys_id)}</span>
            </div>
            <div class="flex items-center gap-1">
                ${typePill}
                ${isInConf ? '<span class="text-[9px] bg-indigo-100 text-indigo-600 px-1.5 py-0.5 rounded font-bold">In Conf</span>' : ''}
            </div>
        </div>
        <div class="text-xs font-semibold text-gray-700 truncate">${_e(b.airline||b.title||'—')}</div>
        ${b.pnr ? `<div class="text-[11px] text-gray-400">PNR: ${_e(b.pnr)}</div>` : ''}
        <div class="text-[11px] text-gray-400 truncate">${_e(route)}</div>
        <div class="flex items-center justify-between mt-1">
            <span class="text-[11px] font-bold text-green-600">৳ ${_fmt(b.total_payable)}</span>
            <span class="at-pill ${sMap[b.status]??'at-pill-tentative'}" style="font-size:.6rem">${b.status}</span>
        </div>
        ${b.quotation_sys_id ? `<div class="text-[10px] text-gray-300 mt-0.5">From: ${_e(b.quotation_sys_id)}</div>` : ''}
    </div>`;
}

window.atNewBooking = function() {
    window._at.activeBSysId = null; window._at.gdsSegments = []; window._at.gdsFares = [];
    _renderBBuilder(null);
    _loadBookingTravelers();
};

window.atSelectBooking = function(sysId) {
    window._at.activeBSysId = sysId;
    const b = (window._at.data?.at_bookings ?? []).find(x => x.sys_id === sysId);
    if (!b) return;
    document.querySelectorAll('#at-b-list .at-q-card').forEach(c => c.classList.remove('active'));
    event?.currentTarget?.classList.add('active');
    window._at.gdsSegments = b.segments_json ?? [];
    window._at.gdsFares    = b.pricing_json  ?? [];
    _renderBBuilder(b);
    if (b.type === 'gds' || !b.type) setTimeout(() => _recalcAllFares('b'), 50);
    _loadBookingTravelers();
};

window._renderBBuilder = function _renderBBuilder(b) {
    const builder = document.getElementById('at-b-builder');
    if (!builder) return;
    const type = b?.type ?? 'gds';
    builder.innerHTML = `
    ${!b ? `<div class="flex items-center gap-3 mb-4">
        <span class="text-xs font-bold text-gray-400 uppercase">Type:</span>
        <label class="flex items-center gap-1.5 cursor-pointer">
            <input type="radio" name="at-b-type-sel" value="gds" checked onchange="atBTypeChange('gds')"><span class="text-sm">GDS</span>
        </label>
        <label class="flex items-center gap-1.5 cursor-pointer">
            <input type="radio" name="at-b-type-sel" value="soto" onchange="atBTypeChange('soto')"><span class="text-sm">SOTO</span>
        </label>
    </div>` : `<div class="flex items-center gap-2 mb-4">
        <span class="text-xs font-bold text-gray-400 uppercase">Type:</span>
        <span class="at-pill ${type==='soto'?'at-pill-sent':'at-pill-draft'}">${type.toUpperCase()}</span>
        <span class="text-xs text-gray-400 font-mono ml-auto">${_e(b.sys_id)}</span>
    </div>`}

    <div class="grid grid-cols-2 gap-3 mb-4">
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">PNR / Ref No</label>
            <input id="at-b-pnr" value="${_e(b?.pnr??'')}" placeholder="e.g. ABC123"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-400">
        </div>
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Airline</label>
            <input id="at-b-airline" value="${_e(b?.airline??'')}" placeholder="e.g. Biman"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-400">
        </div>
    </div>
    <div class="mb-4">
        <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Ticket Numbers</label>
        <input id="at-b-tickets" value="${_e((b?.ticket_nos??[]).join(', '))}" placeholder="123-4567890, 123-4567891"
            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-400">
    </div>

    <!-- Travelers table -->
    <div class="mb-4">
        <label class="text-xs font-bold text-gray-500 uppercase block mb-2">Travelers</label>
        <div id="at-b-travelers" class="text-xs text-gray-300 text-center py-2">
            <i class="fas fa-spinner fa-spin"></i>
        </div>
    </div>

    <div id="at-b-body">${type === 'soto' ? _sotoHtml(b) : _gdsHtml(b, 'b')}</div>
`;}

// ── Booking travelers table ───────────────────────────────────
window._loadBookingTravelers = async function _loadBookingTravelers() {
    const el = document.getElementById('at-b-travelers');
    if (!el) return;
    if (!window._at.cfg.workSysId) { el.innerHTML = '<p class="text-gray-300 text-xs">No work ID</p>'; return; }

    try {
        const res  = await fetch(`${window._at.cfg.api.workTravelers}?action=list&work_sys_id=${encodeURIComponent(window._at.cfg.workSysId)}`);
        const json = await res.json();
        const travelers = json.status === 'success' ? (json.data ?? []) : [];

        if (!travelers.length) {
            el.innerHTML = '<p class="text-gray-300 text-xs text-center py-1">No travelers linked</p>';
            return;
        }

        const cell = (v) => `<td class="px-2 py-1.5 cursor-pointer hover:bg-indigo-50 transition select-all"
            onclick="atBCopyCell('${_e(String(v))}')" title="Click to copy">${_e(String(v))}</td>`;

        el.innerHTML = `<div class="overflow-x-auto rounded-lg border border-gray-100">
            <table class="w-full text-xs">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 text-[10px] uppercase">
                        <th class="px-2 py-1.5 text-left font-bold">Name</th>
                        <th class="px-2 py-1.5 text-center font-bold">Given</th>
                        <th class="px-2 py-1.5 text-center font-bold">Surname</th>
                        <th class="px-2 py-1.5 text-center font-bold">PP No</th>
                        <th class="px-2 py-1.5 text-center font-bold">Expiry</th>
                        <th class="px-2 py-1.5 text-center font-bold">DOB</th>
                        <th class="px-2 py-1.5 text-center font-bold">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    ${travelers.map(t => {
                        const pi = t.passport_info ?? '[]';
                        const pArr = Array.isArray(pi) ? pi : (typeof pi === 'string' ? JSON.parse(pi || '[]') : []);
                        const bio  = pArr.find?.(p => p.page_type === 'bio_page')?.bio_info ?? {};
                        const name    = t.name ?? '—';
                        const given   = bio.given_names ?? bio.given_name ?? '—';
                        const surname = bio.surname ?? '—';
                        const ppNo    = bio.passport_number ?? bio.passport_no ?? t.passport_no ?? '—';
                        const expiry  = bio.date_of_expiry ?? '—';
                        const dob     = bio.date_of_birth ?? t.date_of_birth ?? '—';
                        return `<tr class="hover:bg-gray-50 text-gray-800">
                            <td class="px-2 py-1.5 cursor-pointer hover:bg-indigo-50 transition font-medium" onclick="atBCopyCell('${_e(String(name))}')" title="Copy">${_e(String(name))}</td>
                            <td class="px-2 py-1.5 cursor-pointer hover:bg-indigo-50 transition text-center" onclick="atBCopyCell('${_e(String(given))}')" title="Copy">${_e(String(given))}</td>
                            <td class="px-2 py-1.5 cursor-pointer hover:bg-indigo-50 transition text-center" onclick="atBCopyCell('${_e(String(surname))}')" title="Copy">${_e(String(surname))}</td>
                            <td class="px-2 py-1.5 cursor-pointer hover:bg-indigo-50 transition text-center font-mono" onclick="atBCopyCell('${_e(String(ppNo))}')" title="Copy">${_e(String(ppNo))}</td>
                            <td class="px-2 py-1.5 cursor-pointer hover:bg-indigo-50 transition text-center" onclick="atBCopyCell('${_e(String(expiry))}')" title="Copy">${_e(String(expiry))}</td>
                            <td class="px-2 py-1.5 cursor-pointer hover:bg-indigo-50 transition text-center" onclick="atBCopyCell('${_e(String(dob))}')" title="Copy">${_e(String(dob))}</td>
                            <td class="px-2 py-1.5 text-center">
                                <button onclick="atBUnlinkTraveler('${_e(t.sys_id)}','${_e(name)}')"
                                    class="text-red-300 hover:text-red-500 transition" title="Remove">
                                    <i class="fas fa-times text-[10px]"></i>
                                </button>
                            </td>
                        </tr>`;
                    }).join('')}
                </tbody>
            </table>
        </div>`;
    } catch(e) {
        el.innerHTML = '<p class="text-red-300 text-xs text-center">Load failed</p>';
        console.error(e);
    }
}

window.atBCopyCell = function(text) {
    window.focus();
    setTimeout(() => {
        navigator.clipboard.writeText(text).then(() => atT('success', 'Copied!'));
    }, 50);
};

window.atBUnlinkTraveler = async function(travelerSysId, name) {
    if (!confirm(`Remove ${name}?`)) return;
    try {
        const r = await fetch(window._at.cfg.api.workTravelers, {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({action:'unlink', work_sys_id:window._at.cfg.workSysId, traveler_sys_id:travelerSysId})
        });
        const j = await r.json();
        if (j.status === 'success') { atT('success', `${name} removed`); _loadBookingTravelers(); }
        else atT('error', j.message ?? 'Failed');
    } catch { atT('error', 'Network error'); }
};

window.atBTypeChange = function(type) {
    const body = document.getElementById('at-b-body');
    if (body) body.innerHTML = type === 'soto' ? _sotoHtml(null) : _gdsHtml(null, 'b');
};

window.atDeleteB = async function() {
    if (!window._at.activeBSysId || !confirm('Delete booking?')) return;
    try {
        const json = await window._atApi({ action:'delete_booking', booking_sys_id:window._at.activeBSysId });
        if (json.status==='success') { atT('success','Deleted'); await window._atReload(); window._at.activeBSysId=null; _renderBooking(); }
        else atT('error', json.message);
    } catch { atT('error','Network error'); }
};

window.atMoveToConfirmation = async function(bSysId) {
    if (!confirm('Move this booking to Confirmation?')) return;
    try {
        const json = await window._atApi({ action:'add_to_confirmation', booking_sys_id: bSysId });
        if (json.status === 'success') {
            atT('success','Moved to Confirmation!');
            await window._atReload();
            const btn = document.querySelector('.at-tab[data-tab="confirmation"]');
            if (btn) window._atSwitchTab('confirmation', btn);
        } else { atT('error', json.message); }
    } catch { atT('error','Network error'); }
};