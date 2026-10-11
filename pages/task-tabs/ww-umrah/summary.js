/**
 * FILE PATH: /pages/task-tabs/ww-umrah/summary.js
 * Tab: Trip Summary — read-only view of confirmed service data
 */

window._renderUmSummary = function() {
    const panel   = document.getElementById('um-panel-summary');
    const summary = window._um.summary;

    if (!summary) {
        panel.innerHTML = '<div class="text-center py-12 text-gray-300"><i class="fas fa-spinner fa-spin text-2xl"></i></div>';
        return;
    }

    const flights   = summary.flights   ?? [];
    const hotels    = summary.hotels    ?? [];
    const itinerary = summary.itinerary ?? [];

    panel.innerHTML = `
    <div class="space-y-5">

        <!-- Flights -->
        <div>
            <div class="flex items-center gap-2 mb-3">
                <i class="fas fa-plane text-indigo-500"></i>
                <h3 class="text-sm font-bold text-gray-700">Flights</h3>
                <span class="text-[10px] bg-indigo-50 text-indigo-600 px-2 py-0.5 rounded font-semibold">${flights.length} confirmed</span>
                ${!flights.length ? `<span class="text-[10px] text-gray-400 ml-auto">No confirmed air tickets yet</span>` : ''}
            </div>
            ${flights.map(f => {
                const segs = f.segments ?? [];
                return `<div class="bg-indigo-50 border border-indigo-100 rounded-xl p-3 mb-2">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-indigo-800">${_ume(f.airline||'—')}</span>
                        ${f.pnr ? `<span class="text-[10px] bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded font-mono">PNR: ${_ume(f.pnr)}</span>` : ''}
                    </div>
                    ${segs.length ? `<div class="space-y-1">
                        ${segs.map((s,i) => `<div class="flex items-center gap-2 text-xs text-gray-600">
                            <span class="text-[10px] text-indigo-400 font-mono w-4">${i+1}</span>
                            <span class="font-semibold">${_ume(s.flight||s.flight_no||'')}</span>
                            <span>${_ume(s.route||s.route_code||'')}</span>
                            <span class="text-gray-400">${_ume(s.date||'')} ${_ume(s.departure||s.dep_time||'')}</span>
                            <i class="fas fa-arrow-right text-[9px] text-gray-300"></i>
                            <span class="text-gray-400">${_ume(s.arrival||s.arr_time||'')}</span>
                        </div>`).join('')}
                    </div>` : '<p class="text-xs text-gray-400">No segment data</p>'}
                    ${f.ticket_nos?.length ? `<div class="mt-2 text-[10px] text-gray-400">Tickets: ${f.ticket_nos.join(', ')}</div>` : ''}
                </div>`;
            }).join('')}
        </div>

        <!-- Hotels -->
        <div>
            <div class="flex items-center gap-2 mb-3">
                <i class="fas fa-hotel text-orange-500"></i>
                <h3 class="text-sm font-bold text-gray-700">Hotels</h3>
                <span class="text-[10px] bg-orange-50 text-orange-600 px-2 py-0.5 rounded font-semibold">${hotels.length} confirmed</span>
                ${!hotels.length ? `<span class="text-[10px] text-gray-400 ml-auto">No confirmed hotels yet</span>` : ''}
            </div>
            ${hotels.map(h => `
            <div class="bg-orange-50 border border-orange-100 rounded-xl p-3 mb-2">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-bold text-orange-800">${_ume(h.hotel_name||'—')} ${h.star_rating ? '<span style="color:#F59E0B;">'+'★'.repeat(h.star_rating)+'</span>' : ''}</span>
                    <span class="text-[10px] text-orange-600">${_ume(h.city||'')}${h.city&&h.country?', ':' '}${_ume(h.country||'')}</span>
                </div>
                <div class="grid grid-cols-3 gap-2 text-xs text-gray-600">
                    <div><span class="text-gray-400">Check-in</span><br><b>${_ume(h.check_in||'—')}</b></div>
                    <div><span class="text-gray-400">Check-out</span><br><b>${_ume(h.check_out||'—')}</b></div>
                    <div><span class="text-gray-400">Nights</span><br><b>${h.nights||'—'}</b></div>
                    <div><span class="text-gray-400">Room</span><br><b>${_ume(h.room_type||'—')}</b></div>
                    <div><span class="text-gray-400">Meal</span><br><b>${_ume(UM_MEAL_PLANS[h.meal_plan]||h.meal_plan||'—')}</b></div>
                    <div><span class="text-gray-400">Rooms</span><br><b>${h.rooms||1}</b></div>
                </div>
                ${h.booking_ref ? `<div class="mt-1 text-[10px] text-gray-400">Booking Ref: ${_ume(h.booking_ref)}</div>` : ''}
            </div>`).join('')}
        </div>

        <!-- Itinerary -->
        <div>
            <div class="flex items-center gap-2 mb-3">
                <i class="fas fa-map-marked-alt text-green-500"></i>
                <h3 class="text-sm font-bold text-gray-700">Itinerary</h3>
                ${!itinerary.length ? `<span class="text-[10px] text-gray-400 ml-auto">No itinerary from Package service yet</span>` : ''}
            </div>
            ${itinerary.length ? `
            <div class="space-y-2">
                ${itinerary.map((item,i) => `
                <div class="flex gap-3 p-2">
                    <div class="flex flex-col items-center">
                        <div class="w-2 h-2 rounded-full bg-green-500 mt-1 flex-shrink-0"></div>
                        ${i < itinerary.length-1 ? '<div class="w-px flex-1 bg-green-200 mt-1"></div>' : ''}
                    </div>
                    <div class="flex-1 pb-2">
                        <div class="text-xs font-semibold text-gray-800">${_ume(item.title||item.day_title||'')}</div>
                        <div class="text-[11px] text-gray-400">${_ume(item.date||'')} ${_ume(item.time||'')}</div>
                        ${item.description ? `<div class="text-[11px] text-gray-500 mt-0.5">${_ume(item.description)}</div>` : ''}
                    </div>
                </div>`).join('')}
            </div>` : ''}
        </div>

        ${!flights.length && !hotels.length && !itinerary.length ? `
        <div class="text-center py-12 text-gray-300">
            <i class="fas fa-list-check text-4xl mb-3 block"></i>
            <p class="text-sm font-medium">No confirmed services yet</p>
            <p class="text-xs mt-1">Confirm Air Ticket, Hotel, or Package tasks to see data here.</p>
        </div>` : ''}

        <!-- Financial Entry -->
        <div style="margin-top:16px;padding:14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <div style="font-size:13px;font-weight:700;color:#374151;">Financial Entry</div>
                <button onclick="umOpenFinancialModal()"
                    style="padding:8px 16px;background:#10b981;color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;">
                    <i class="fas fa-coins mr-1"></i>Add Entry
                </button>
            </div>
        </div>

    </div>`;
};

// ══════════════════════════════════════════════════════
// UMRAH FINANCIAL ENTRY MODAL
// ══════════════════════════════════════════════════════
window.umOpenFinancialModal = function() {
    const cfg = window._um.cfg;
    const clientSysId = cfg.clientSysId || '';
    const ov = document.createElement('div');
    ov.id = 'um-fin-modal';
    ov.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;display:flex;align-items:center;justify-content:center;padding:16px;';
    ov.innerHTML = `
    <div style="background:#fff;border-radius:16px;padding:24px;width:100%;max-width:480px;max-height:90vh;overflow-y:auto;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
            <div style="font-size:16px;font-weight:700;color:#0f172a;">Umrah Financial Entry</div>
            <button onclick="document.getElementById('um-fin-modal').remove()" style="background:none;border:none;font-size:20px;cursor:pointer;color:#64748b;">✕</button>
        </div>
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px;margin-bottom:12px;">
            <div style="font-size:12px;font-weight:700;color:#374151;margin-bottom:10px;">Vendor Payment</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:8px;">
                <div>
                    <label style="font-size:11px;color:#64748b;display:block;margin-bottom:4px;">Vendor</label>
                    <select id="um-fin-vendor" style="width:100%;padding:8px;border:1px solid #e2e8f0;border-radius:6px;font-size:13px;">
                        <option value="">Select vendor…</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:11px;color:#64748b;display:block;margin-bottom:4px;">Amount (BDT)</label>
                    <input id="um-fin-vendor-amt" type="number" placeholder="0" style="width:100%;padding:8px;border:1px solid #e2e8f0;border-radius:6px;font-size:13px;">
                </div>
            </div>
            <div>
                <label style="font-size:11px;color:#64748b;display:block;margin-bottom:4px;">Account</label>
                <select id="um-fin-vendor-acc" style="width:100%;padding:8px;border:1px solid #e2e8f0;border-radius:6px;font-size:13px;">
                    <option value="">Select account…</option>
                </select>
            </div>
        </div>
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px;margin-bottom:16px;">
            <div style="font-size:12px;font-weight:700;color:#374151;margin-bottom:10px;">Client Sale</div>
            <input id="um-fin-client-amt" type="number" placeholder="Client amount (BDT)" style="width:100%;padding:8px;border:1px solid #e2e8f0;border-radius:6px;font-size:13px;">
        </div>
        <button onclick="umSubmitFinancial()"
            style="width:100%;padding:12px;background:#10b981;color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;">
            <i class="fas fa-save mr-2"></i>Save Financial Entry
        </button>
    </div>`;
    document.body.appendChild(ov);

    // Load dropdowns
    Promise.all([fetch(cfg.api.allVendors||''), fetch(cfg.api.allAccounts||'')]).then(([vr,ar]) =>
        Promise.all([vr.json(), ar.json()])
    ).then(([vj,aj]) => {
        const vSel = document.getElementById('um-fin-vendor');
        const aSel = document.getElementById('um-fin-vendor-acc');
        (vj.vendors??[]).forEach(v => { const o=document.createElement('option'); o.value=v.sys_id; o.textContent=v.name; vSel?.appendChild(o); });
        (aj.accounts??[]).forEach(a => { const o=document.createElement('option'); o.value=a.sys_id; o.textContent=a.acc_name; aSel?.appendChild(o); });
    }).catch(e => console.error('Umrah fin dropdowns', e));
};

window.umSubmitFinancial = async function() {
    const cfg       = window._um.cfg;
    const vendorId  = document.getElementById('um-fin-vendor')?.value;
    const vendorAmt = +(document.getElementById('um-fin-vendor-amt')?.value || 0);
    const accId     = document.getElementById('um-fin-vendor-acc')?.value;
    const clientAmt = +(document.getElementById('um-fin-client-amt')?.value || 0);
    const clientSysId = cfg.clientSysId || '';

    const entries = [];
    if (vendorId && vendorAmt > 0 && accId)
        entries.push({ type:'vendor_payment', vendor_id:vendorId, account_id:accId, amount:vendorAmt, currency:'BDT', work_sys_id:cfg.workSysId, service_slug:'umrah' });
    if (clientAmt > 0 && clientSysId)
        entries.push({ type:'client_sale', client_id:clientSysId, amount:clientAmt, currency:'BDT', work_sys_id:cfg.workSysId, service_slug:'umrah' });
    if (!entries.length) { alert('কমপক্ষে একটা entry দিন'); return; }

    try {
        for (const entry of entries) {
            const res = await fetch(cfg.api.saveFinancial, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(entry) });
            const j = await res.json();
            if (!j.success) { alert(j.message||'Entry failed'); return; }
        }
        alert('Financial entries saved!');
        document.getElementById('um-fin-modal')?.remove();
    } catch(e) { alert('Network error'); }
};