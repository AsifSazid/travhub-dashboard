/**
 * FILE PATH: /pages/task-tabs/ww-air-tickets/quotation.js
 * Tab: Quotation
 */

// ═══════════════════════════════════════════════════════════════
// TAB 2 — QUOTATION
// ═══════════════════════════════════════════════════════════════

// ── GDS State ─────────────────────────────────────────────────
// window._at.gdsSegments and window._at.gdsFares already declared above in module scope

// ── GDS HTML shell ────────────────────────────────────────────
window._gdsHtml = function _gdsHtml(data, pfx) {
    return `
    <!-- Raw GDS + Extract -->
    <div class="mb-4">
        <div class="flex items-center justify-between mb-1">
            <label class="text-xs font-bold text-gray-500 uppercase">Raw GDS Text</label>
            <button onclick="atExtractGds('${pfx}')" id="at-${pfx}-extract-btn"
                class="flex items-center gap-1 px-3 py-1.5 bg-slate-800 hover:bg-slate-900 text-white rounded-lg text-xs font-semibold transition">
                <i class="fas fa-wand-magic-sparkles text-xs"></i>Process GDS
            </button>
        </div>
        <textarea id="at-${pfx}-raw" rows="4" placeholder="Paste Amadeus/Sabre/Galileo GDS text here…"
            class="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs font-mono resize-none focus:outline-none focus:border-indigo-400"
        >${_e(data?.raw_input??'')}</textarea>
        <div id="at-${pfx}-extract-err" class="hidden mt-1 text-xs text-red-500"></div>
    </div>

    <!-- Airline + Calculation rates -->
    <div class="grid grid-cols-4 gap-2 mb-4">
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Airline</label>
            <input id="at-${pfx}-airline" value="${_e(data?.airline??'')}" placeholder="Turkish Airlines"
                oninput="atGdsPreview('${pfx}')"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-400">
        </div>
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Commission %</label>
            <input type="number" id="at-${pfx}-comm" value="7" step="0.01" min="0"
                oninput="atGdsCalcAll('${pfx}', true)"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-400">
        </div>
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Govt Tax %</label>
            <input type="number" id="at-${pfx}-govt" value="0.3" step="0.01" min="0"
                oninput="atGdsCalcAll('${pfx}', true)"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-400">
        </div>
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">IATA Charge</label>
            <input type="number" id="at-${pfx}-iata" value="0" min="0"
                oninput="atGdsApplyIata('${pfx}')"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-400">
        </div>
    </div>

    <!-- Live Preview -->
    <div class="mb-4 rounded-xl overflow-hidden border border-gray-200">
        <div class="bg-slate-800 px-4 py-2 flex items-center justify-between">
            <span class="text-white text-xs font-bold">Client Copy Preview</span>
            <button onclick="atGdsCopy('${pfx}')"
                class="text-xs bg-white bg-opacity-10 hover:bg-opacity-20 text-white px-3 py-1 rounded-lg font-semibold transition">
                <i class="fas fa-copy mr-1"></i>Copy
            </button>
        </div>
        <pre id="at-${pfx}-preview"
            class="bg-slate-950 text-slate-100 p-4 min-h-[120px] max-h-[260px] overflow-auto text-xs leading-6 font-mono whitespace-pre-wrap">${_e(data?.copy_text??'')}</pre>
    </div>

    <!-- Segments -->
    <div class="mb-4">
        <div class="flex items-center justify-between mb-2">
            <label class="text-xs font-bold text-gray-500 uppercase">Flight Segments</label>
            <button onclick="atGdsAddSeg('${pfx}')" class="text-xs text-indigo-500 hover:text-indigo-700 font-semibold"><i class="fas fa-plus mr-1"></i>Add Segment</button>
        </div>
        <div class="overflow-x-auto rounded-xl border border-gray-100">
            <div style="min-width:700px">
                <div class="grid gap-2 px-3 py-2 bg-gray-50 text-[10px] font-bold uppercase text-gray-400" style="grid-template-columns:44px 100px 50px 80px 120px 72px 72px 40px">
                    <div>Tag</div><div>Flight</div><div>Cls</div><div>Date</div><div>Route</div><div>Dep</div><div>Arr</div><div></div>
                </div>
                <div id="at-${pfx}-segs-area" class="divide-y divide-gray-50">${_gdsSegsArea(pfx)}</div>
            </div>
        </div>
    </div>

    <!-- Fares -->
    <div class="mb-4">
        <div class="flex items-center justify-between mb-2">
            <label class="text-xs font-bold text-gray-500 uppercase">Passenger Fare Calculation</label>
            <button onclick="atGdsAddFare('${pfx}')" class="text-xs text-indigo-500 hover:text-indigo-700 font-semibold"><i class="fas fa-plus mr-1"></i>Add Fare</button>
        </div>
        <div class="overflow-x-auto rounded-xl border border-gray-100">
            <div style="min-width:1100px">
                <div class="grid gap-2 px-3 py-2 bg-gray-50 text-[10px] font-bold uppercase text-gray-400"
                    style="grid-template-columns:65px 50px 105px 95px 105px 95px 95px 95px 105px 125px 130px 44px">
                    <div>Type</div><div>Pax</div><div>Base</div><div>Taxes</div><div>Gross</div>
                    <div>A(Comm)</div><div>B(Govt)</div><div>IATA</div><div>Net</div><div>Payable</div><div>Total</div><div></div>
                </div>
                <div id="at-${pfx}-fares-area" class="divide-y divide-gray-50">${window._at.gdsFaresArea(pfx)}</div>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="flex gap-2">
        <button onclick="atSaveQ('${pfx}')"
            class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition">
            <i class="fas fa-save mr-1.5"></i>${pfx==='q'&&window._at.activeQSysId ? 'Update' : pfx==='b'&&window._at.activeBSysId ? 'Update Booking' : pfx==='q' ? 'Save Quotation' : 'Save Booking'}
        </button>
        ${pfx==='q'&&window._at.activeQSysId ? `<button onclick="atDeleteQ()" class="px-4 py-2.5 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-lg text-sm"><i class="fas fa-trash-alt"></i></button>` : ''}
        ${pfx==='b'&&window._at.activeBSysId ? (() => {
            const alreadyConf = (window._at.data?.at_confirmations??[]).some(c => c.booking_sys_id === window._at.activeBSysId);
            return alreadyConf
                ? `<button disabled class="px-4 py-2.5 bg-gray-100 text-gray-400 rounded-lg text-sm cursor-not-allowed whitespace-nowrap text-xs font-medium"><i class="fas fa-check-circle mr-1"></i>Already in Confirmation</button>`
                : `<button onclick="atConfirmFromBooking('${window._at.activeBSysId}')" title="Send to Confirmation"
                    class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition whitespace-nowrap">
                    <i class="fas fa-check mr-1.5"></i>Send to Confirmation
                </button>`;
        })() : ''}
        ${pfx==='b'&&window._at.activeBSysId ? `<button onclick="atDeleteB()" class="px-4 py-2.5 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-lg text-sm"><i class="fas fa-trash-alt"></i></button>` : ''}
    </div>`;
}

// ── Segment rows ──────────────────────────────────────────────
window._gdsSegsArea = function _gdsSegsArea(pfx) {
    if (!window._at.gdsSegments.length) return `<div class="text-center py-4 text-gray-300 text-xs">No segments. Process GDS or Add.</div>`;
    return window._at.gdsSegments.map((s,i) => `
    <div class="grid gap-2 px-3 py-2 items-center" style="grid-template-columns:44px 100px 50px 80px 120px 72px 72px 40px">
        <span class="text-xs font-bold text-gray-400">${_e(s.tag||'D'+(i+1))}</span>
        <input class="gds-input at-seg-field" data-pfx="${pfx}" data-idx="${i}" data-key="flight" value="${_e(s.flight??'')}">
        <input class="gds-input at-seg-field" data-pfx="${pfx}" data-idx="${i}" data-key="class" value="${_e(s.class??'')}">
        <input class="gds-input at-seg-field" data-pfx="${pfx}" data-idx="${i}" data-key="date" value="${_e(s.date??'')}">
        <input class="gds-input at-seg-field" data-pfx="${pfx}" data-idx="${i}" data-key="route" value="${_e(s.route??'')}">
        <input class="gds-input at-seg-field" data-pfx="${pfx}" data-idx="${i}" data-key="departure" value="${_e(s.departure??'')}">
        <input class="gds-input at-seg-field" data-pfx="${pfx}" data-idx="${i}" data-key="arrival" value="${_e(s.arrival??'')}">
        <button class="at-seg-rm text-red-400 hover:text-red-600 text-xs" data-pfx="${pfx}" data-idx="${i}"><i class="fas fa-trash"></i></button>
    </div>`).join('');
}

// ── Fare rows ─────────────────────────────────────────────────
function _gdsFaresArea(pfx) {
    if (!window._at.gdsFares.length) return `<div class="text-center py-4 text-gray-300 text-xs">No fares. Process GDS or Add.</div>`;
    return window._at.gdsFares.map((f,i) => `
    <div class="grid gap-2 px-3 py-2 items-center" style="grid-template-columns:65px 50px 105px 95px 105px 95px 95px 95px 105px 125px 130px 44px">
        <input class="gds-input at-fare-field" data-pfx="${pfx}" data-idx="${i}" data-key="type" value="${_e(f.type??'ADT')}">
        <input class="gds-input at-fare-field" data-pfx="${pfx}" data-idx="${i}" data-key="pax" data-numeric="1" value="${+(f.pax??1)}">
        <input class="gds-input at-fare-field" data-pfx="${pfx}" data-idx="${i}" data-key="base_fare" data-numeric="1" value="${+(f.base_fare??0)}">
        <input class="gds-input at-fare-field" data-pfx="${pfx}" data-idx="${i}" data-key="taxes" data-numeric="1" value="${+(f.taxes??0)}">
        <input class="gds-input at-fare-field" data-pfx="${pfx}" data-idx="${i}" data-key="gross_fare" data-numeric="1" value="${+(f.gross_fare??0)}">
        <input class="gds-input bg-gray-50" readonly data-fare-ro="commission_a" data-pfx="${pfx}" data-idx="${i}" value="${+(f.commission_a??0)}">
        <input class="gds-input bg-gray-50" readonly data-fare-ro="govt_tax_b" data-pfx="${pfx}" data-idx="${i}" value="${+(f.govt_tax_b??0)}">
        <input class="gds-input at-fare-field" data-pfx="${pfx}" data-idx="${i}" data-key="iata_charge" data-numeric="1" value="${+(f.iata_charge??0)}">
        <input class="gds-input bg-gray-50 font-semibold" readonly data-fare-ro="net_fare" data-pfx="${pfx}" data-idx="${i}" value="${+(f.net_fare??0)}">
        <input class="gds-input border-2 border-green-400 font-bold text-green-700 at-fare-payable" data-pfx="${pfx}" data-idx="${i}" value="${+(f.payable??0)}">
        <input class="gds-input bg-green-50 font-bold text-green-700" readonly data-fare-ro="total_payable" data-pfx="${pfx}" data-idx="${i}" value="BDT ${_fmtN(+(f.total_payable??0))}/-">
        <button class="at-fare-rm text-red-400 hover:text-red-600 text-xs" data-pfx="${pfx}" data-idx="${i}"><i class="fas fa-trash"></i></button>
    </div>`).join('');
}

// ── Event delegation ──────────────────────────────────────────
(function _attachGdsDelegation() {
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('at-seg-field')) {
            const idx = +e.target.dataset.idx, key = e.target.dataset.key, pfx = e.target.dataset.pfx;
            window._at.gdsSegments[idx][key] = key==='route' ? _gdsNormalizeRoute(e.target.value) : e.target.value;
            atGdsPreview(pfx); return;
        }
        if (e.target.classList.contains('at-fare-payable')) {
            const idx = +e.target.dataset.idx, pfx = e.target.dataset.pfx;
            window._at.gdsFares[idx].payable        = +(e.target.value||0);
            window._at.gdsFares[idx].payable_edited = true;
            window._at.gdsFares[idx].total_payable  = window._at.gdsFares[idx].payable * (+(window._at.gdsFares[idx].pax||1));
            _gdsUpdateRo(pfx, idx); atGdsPreview(pfx); return;
        }
        if (e.target.classList.contains('at-fare-field') && e.target.dataset.key) {
            const idx = +e.target.dataset.idx, key = e.target.dataset.key, pfx = e.target.dataset.pfx;
            window._at.gdsFares[idx][key] = e.target.dataset.numeric==='1' ? +(e.target.value||0) : e.target.value;
            if (['base_fare','gross_fare','iata_charge'].includes(key)) {
                if (key!=='pax') window._at.gdsFares[idx].payable_edited = false;
                _gdsCalcFare(pfx, idx, false);
                _gdsUpdateRo(pfx, idx);
                if (!window._at.gdsFares[idx].payable_edited) {
                    const payEl = document.querySelector(`.at-fare-payable[data-pfx="${pfx}"][data-idx="${idx}"]`);
                    if (payEl && document.activeElement !== payEl) payEl.value = window._at.gdsFares[idx].payable;
                }
            }
            atGdsPreview(pfx); return;
        }
    });
    document.addEventListener('click', function(e) {
        const rmSeg = e.target.closest('.at-seg-rm');
        if (rmSeg) {
            const idx=+rmSeg.dataset.idx, pfx=rmSeg.dataset.pfx;
            window._at.gdsSegments.splice(idx,1);
            const a=document.getElementById(`at-${pfx}-segs-area`); if(a) a.innerHTML=_gdsSegsArea(pfx);
            atGdsPreview(pfx); return;
        }
        const rmFare = e.target.closest('.at-fare-rm');
        if (rmFare) {
            const idx=+rmFare.dataset.idx, pfx=rmFare.dataset.pfx;
            window._at.gdsFares.splice(idx,1);
            const a=document.getElementById(`at-${pfx}-fares-area`); if(a) a.innerHTML=window._at.gdsFaresArea(pfx);
            atGdsPreview(pfx); return;
        }
    });
})();

// ── Fare calculation ──────────────────────────────────────────
function _gdsCalcFare(pfx, idx, forceReset) {
    const f    = window._at.gdsFares[idx]; if (!f) return;
    const comm = +(document.getElementById(`at-${pfx}-comm`)?.value ?? 7) / 100;
    const govt = +(document.getElementById(`at-${pfx}-govt`)?.value ?? 0.3) / 100;
    const base  = +(f.base_fare  || 0);
    const gross = +(f.gross_fare || 0);
    const iata  = +(f.iata_charge || 0);
    f.commission_a = Math.round(base  * comm);
    f.govt_tax_b   = Math.round(gross * govt);
    f.net_fare     = Math.max(0, Math.round(gross - f.commission_a + f.govt_tax_b + iata));
    if (forceReset || !f.payable_edited || +(f.payable||0) === 0) {
        f.payable        = Math.max(0, Math.round((gross + f.net_fare) / 2));
        f.payable_edited = false;
    }
    f.total_payable = +(f.payable||0) * +(f.pax||1);
}

function _gdsUpdateRo(pfx, idx) {
    const f = window._at.gdsFares[idx]; if (!f) return;
    const sel = (ro) => document.querySelector(`[data-fare-ro="${ro}"][data-pfx="${pfx}"][data-idx="${idx}"]`);
    const ca=sel('commission_a'); if(ca) ca.value=f.commission_a;
    const gt=sel('govt_tax_b');   if(gt) gt.value=f.govt_tax_b;
    const nf=sel('net_fare');     if(nf) nf.value=f.net_fare;
    const tp=sel('total_payable'); if(tp) tp.value=`BDT ${_fmtN(f.total_payable)}/-`;
}

window.atGdsCalcAll = function(pfx, forceReset) {
    window._at.gdsFares.forEach((_, i) => {
        _gdsCalcFare(pfx, i, forceReset);
        _gdsUpdateRo(pfx, i);
        if (forceReset) {
            const payEl = document.querySelector(`.at-fare-payable[data-pfx="${pfx}"][data-idx="${i}"]`);
            if (payEl) payEl.value = window._at.gdsFares[i].payable;
        }
    });
    atGdsPreview(pfx);
};

window.atGdsApplyIata = function(pfx) {
    const iata = +(document.getElementById(`at-${pfx}-iata`)?.value ?? 0);
    window._at.gdsFares.forEach((f, i) => {
        f.iata_charge=iata; f.payable_edited=false;
        _gdsCalcFare(pfx, i, false); _gdsUpdateRo(pfx, i);
        const iataEl = document.querySelector(`.at-fare-field[data-pfx="${pfx}"][data-idx="${i}"][data-key="iata_charge"]`);
        if (iataEl) iataEl.value = iata;
        const payEl = document.querySelector(`.at-fare-payable[data-pfx="${pfx}"][data-idx="${i}"]`);
        if (payEl) payEl.value = f.payable;
    });
    atGdsPreview(pfx);
};

window.atGdsAddSeg = function(pfx) {
    const n = window._at.gdsSegments.length;
    window._at.gdsSegments.push({ flight:'', class:'', date:'', route:'', departure:'', arrival:'', tag:`D${n+1}`, airline_name:'' });
    const a=document.getElementById(`at-${pfx}-segs-area`); if(a) a.innerHTML=_gdsSegsArea(pfx);
    atGdsPreview(pfx);
};
window.atGdsAddFare = function(pfx) {
    const iata = +(document.getElementById(`at-${pfx}-iata`)?.value ?? 0);
    window._at.gdsFares.push({ type:'ADT', pax:1, base_fare:0, taxes:0, gross_fare:0, commission_a:0, govt_tax_b:0, iata_charge:iata, net_fare:0, payable:0, payable_edited:false, total_payable:0 });
    const a=document.getElementById(`at-${pfx}-fares-area`); if(a) a.innerHTML=window._at.gdsFaresArea(pfx);
};

function _recalcAllFares(pfx) {
    window._at.gdsFares.forEach((_, i) => { _gdsCalcFare(pfx, i, false); _gdsUpdateRo(pfx, i); });
    window._at.gdsFares.forEach((f, i) => {
        const payEl = document.querySelector(`.at-fare-payable[data-pfx="${pfx}"][data-idx="${i}"]`);
        if (payEl) payEl.value = f.payable;
    });
    atGdsPreview(pfx);
}

function _gdsNormalizeRoute(v) {
    v = String(v||'').trim().toUpperCase();
    if (v.includes('-')) return v;
    if (v.length===6) return v.substring(0,3)+'-'+v.substring(3);
    return v;
}

// ── GDS Extract ───────────────────────────────────────────────
window.atExtractGds = async function(pfx) {
    const raw   = document.getElementById(`at-${pfx}-raw`)?.value.trim();
    const errEl = document.getElementById(`at-${pfx}-extract-err`);
    if (!raw) { if(errEl){errEl.textContent='Paste GDS text first';errEl.classList.remove('hidden');} return; }
    if (errEl) errEl.classList.add('hidden');
    const btn = document.getElementById(`at-${pfx}-extract-btn`);
    if (btn) { btn.disabled=true; btn.innerHTML='<i class="fas fa-spinner fa-spin mr-1"></i>Processing…'; }
    try {
        const apiUrl = window._at.cfg.api.airTickets.replace('api/air-tickets/endpoints.php','api/ticket-calculation/extract-gds.php');
        const res    = await fetch(apiUrl, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({raw_gds:raw}) });
        const json   = await res.json();
        if (json.success && json.data) {
            const d = json.data;
            const aEl = document.getElementById(`at-${pfx}-airline`);
            if (aEl && d.airline) aEl.value = d.airline;
            window._at.gdsSegments = _gdsNormalizeSegs(d.segments ?? []);
            window._at.gdsFares    = _gdsNormalizeFares(d.fares ?? []);
            window._at.gdsFares.forEach((_, i) => _gdsCalcFare(pfx, i, false));
            const sA=document.getElementById(`at-${pfx}-segs-area`);  if(sA) sA.innerHTML=_gdsSegsArea(pfx);
            const fA=document.getElementById(`at-${pfx}-fares-area`); if(fA) fA.innerHTML=window._at.gdsFaresArea(pfx);
            window._at.gdsFares.forEach((f, i) => {
                _gdsUpdateRo(pfx, i);
                const payEl=document.querySelector(`.at-fare-payable[data-pfx="${pfx}"][data-idx="${i}"]`);
                if (payEl) payEl.value = f.payable;
            });
            atGdsPreview(pfx);
            atT('success','Extracted!');
        } else {
            if (errEl) { errEl.textContent=json.message??'Extraction failed'; errEl.classList.remove('hidden'); }
            atT('error', json.message ?? 'Failed');
        }
    } catch { atT('error','Network error'); }
    if (btn) { btn.disabled=false; btn.innerHTML='<i class="fas fa-wand-magic-sparkles text-xs"></i>Process GDS'; }
};

function _gdsNormalizeSegs(segs) {
    if (!Array.isArray(segs)) return [];
    return segs.filter(s=>String(s.flight||'').toUpperCase()!=='ARNK').map((s,i) => ({
        line:s.line||i+1, flight:s.flight||'', class:s.class||'', date:s.date||'',
        route:_gdsNormalizeRoute(s.route||''), departure:s.departure||'', arrival:s.arrival||'',
        tag:s.tag||`D${i+1}`, airline_name:s.airline_name||'',
    }));
}
function _gdsNormalizeFares(fares) {
    if (!Array.isArray(fares)||!fares.length) return [{type:'ADT',pax:1,base_fare:0,taxes:0,gross_fare:0,commission_a:0,govt_tax_b:0,iata_charge:0,net_fare:0,payable:0,payable_edited:false,total_payable:0}];
    return fares.map(f => ({
        type:f.type||'ADT', pax:+(f.pax||1), base_fare:+(f.base_fare||0), taxes:+(f.taxes||0),
        gross_fare:+(f.gross_fare||0), commission_a:+(f.commission_a||0), govt_tax_b:+(f.govt_tax_b||0),
        iata_charge:+(f.iata_charge||0), net_fare:+(f.net_fare||0), payable:+(f.payable||0),
        payable_edited:!!(f.payable_edited), total_payable:+(f.total_payable||0),
    }));
}

// ── Preview ────────────────────────────────────────────────────
window.atGdsPreview = function(pfx) {
    const el = document.getElementById(`at-${pfx}-preview`);
    if (el) el.textContent = _gdsGenerateCopy(pfx);
};

function _gdsGenerateCopy(pfx) {
    if (!window._at.gdsSegments.length) return 'No flight segments yet.';
    const grouped={};
    window._at.gdsSegments.forEach(s=>{ const t=s.tag||'D1'; if(!grouped[t]) grouped[t]=[]; grouped[t].push(s); });
    const sortedTags = Object.keys(grouped).sort();
    const routeParts = sortedTags.map(tag=>{
        const g=grouped[tag];
        const first=(g[0].route||'').split('-')[0]?.trim()??'';
        const last=(g[g.length-1].route||'').split('-')[1]?.trim()??'';
        return first&&last?`${first} -> ${last}`:'';
    }).filter(Boolean);
    let tripType='One Way';
    if (window._at.gdsSegments.length>=2) {
        const fO=(window._at.gdsSegments[0].route||'').split('-')[0]?.trim()??'';
        const lD=(window._at.gdsSegments[window._at.gdsSegments.length-1].route||'').split('-')[1]?.trim()??'';
        if (fO&&lD) tripType=fO===lD?'Round Trip':'Multi-City';
    }
    const totalPax = window._at.gdsFares.reduce((s,f)=>s+(+(f.pax||1)),0);
    const airlineName = document.getElementById(`at-${pfx}-airline`)?.value.trim()||'';
    let out=`*${tripType} Ticket*\nRoute: ${routeParts.join(' | ')}\n${totalPax} ${totalPax>1?'Passengers':'Passenger'}\n\n*Flight Info:*\n`;
    sortedTags.forEach((tag,ti)=>{
        const tagSegs=grouped[tag];
        const isReturn=tag.startsWith('R');
        const airLine=tagSegs[0].airline_name||airlineName||'AIRLINE';
        out+=`*${isReturn?'Return':'Depart'} | ${airLine.toUpperCase()}*\n`;
        tagSegs.forEach((seg,si)=>{
            if (si>0) {
                const transit=_gdsTransitTime(tagSegs[si-1],seg);
                out+=`*TRANSIT at ${(seg.route||'').split('-')[0]?.trim()??''}${transit?` (${transit})`:''} *\n`;
            }
            const overnight=_gdsIsOvernight(seg);
            const nextDay=overnight?`-${_gdsNextDayLabel(seg.date)}`:'';
            const aps=(seg.route||'').split('-');
            out+=`*${seg.date||''}* | ${seg.flight||''}\n`;
            out+=`${aps[0]?.trim()??''} ${seg.departure||''} (${_gdsTo12(seg.departure)}) - ${aps[1]?.trim()??''} ${seg.arrival||''} (${_gdsTo12(seg.arrival)})${nextDay}\n`;
        });
        if (ti<sortedTags.length-1) out+='\n';
    });
    out+='\n*Price:*\n';
    let grand=0;
    window._at.gdsFares.forEach(f=>{
        const gross=+(f.gross_fare||0), payable=+(f.payable||0), pax=+(f.pax||1);
        grand+=payable*pax;
        const tl=f.type==='ADT'?'Adult':f.type==='CHD'||f.type==='CNN'?'Child':f.type==='INF'?'Infant':f.type;
        out+=`- ${tl}: Gross BDT ${_fmtN(gross)} per person\n- *Payable: BDT ${_fmtN(payable)}/- per person.*\n`;
    });
    out+=`*Total payable: ${_fmtN(grand)}/-*`;
    return out;
}

window.atGdsCopy = function(pfx) {
    navigator.clipboard.writeText(_gdsGenerateCopy(pfx)).then(()=>atT('success','Copied!')).catch(()=>atT('error','Copy failed'));
};

// ── Time/overnight helpers ────────────────────────────────────
function _gdsIsOvernight(seg) { return !!(seg.departure&&seg.arrival&&parseInt(seg.arrival,10)<parseInt(seg.departure,10)); }
function _gdsNextDayLabel(dateStr) {
    if (!dateStr) return '';
    const yr=new Date().getFullYear();
    const d=new Date(Date.parse(`${dateStr.replace(/(\d+)([A-Z]+)/,'$1 $2')} ${yr}`));
    if (isNaN(d)) return '';
    d.setDate(d.getDate()+1);
    return `${String(d.getDate()).padStart(2,'0')}${d.toLocaleString('en-US',{month:'short'}).toUpperCase()}`;
}
function _gdsTransitTime(prev,curr) {
    if (!prev||!curr) return '';
    const yr=new Date().getFullYear();
    const parse=(ds,ts)=>{ if(!ds||!ts) return null; const hh=ts.substring(0,2),mm=ts.substring(2,4); return new Date(Date.parse(`${ds.replace(/(\d+)([A-Z]+)/,'$1 $2')} ${yr} ${hh}:${mm}`)); };
    let arr=parse(prev.date,prev.arrival); const dep=parse(prev.date,prev.departure); const nextDep=parse(curr.date,curr.departure);
    if (!arr||!dep||!nextDep) return '';
    if (arr<=dep) arr=new Date(arr.getTime()+86400000);
    let diff=nextDep-arr; if(diff<0) diff+=86400000;
    const totalMin=Math.floor(diff/60000), h=Math.floor(totalMin/60), m=totalMin%60;
    return h===0?`${m} mins`:`${String(h).padStart(2,'0')} ${h>1?'hrs':'hr'} ${m} ${m>1?'mins':'min'}`;
}
function _gdsTo12(time) {
    if (!time) return '';
    const s=String(time).padStart(4,'0'), h=parseInt(s.substring(0,2)), m=s.substring(2,4);
    const ampm=h>=12?'PM':'AM', h12=h>12?h-12:h===0?12:h;
    return `${String(h12).padStart(2,'0')}:${m} ${ampm}`;
}

// ── Aliases for save compatibility ────────────────────────────
window.atGenCopy = function(pfx) { atGdsPreview(pfx); };
window.atAddSeg  = window.atGdsAddSeg;
window.atRmSeg   = function(i,pfx){ window._at.gdsSegments.splice(i,1); const a=document.getElementById(`at-${pfx}-segs-area`); if(a) a.innerHTML=_gdsSegsArea(pfx); atGdsPreview(pfx); };
window.atAddFare = window.atGdsAddFare;
window.atRmFare  = function(i,pfx){ window._at.gdsFares.splice(i,1); const a=document.getElementById(`at-${pfx}-fares-area`); if(a) a.innerHTML=window._at.gdsFaresArea(pfx); atGdsPreview(pfx); };

// ── SOTO HTML ─────────────────────────────────────────────────
window._sotoHtml = function _sotoHtml(q) {
    const fd = q?.form_data ?? {};
    return `
    <!-- Screenshot / Text Extract -->
    <div class="mb-4 border border-gray-100 rounded-xl p-4 bg-gray-50">
        <div class="flex items-center justify-between mb-2">
            <label class="text-xs font-bold text-gray-500 uppercase">Extract from Screenshot or Text</label>
            <button onclick="atSotoExtract()"
                class="flex items-center gap-1 px-3 py-1.5 bg-green-700 hover:bg-green-800 text-white rounded-lg text-xs font-semibold transition">
                <i class="fas fa-magic text-xs"></i>Extract & Fill
            </button>
        </div>
        <div id="at-soto-img-zone"
            class="border-2 border-dashed border-gray-200 rounded-lg p-3 text-center cursor-pointer hover:border-indigo-400 transition mb-2"
            onclick="document.getElementById('at-soto-file-inp').click()"
            ondragover="event.preventDefault();this.classList.add('border-indigo-400')"
            ondragleave="this.classList.remove('border-indigo-400')"
            ondrop="event.preventDefault();this.classList.remove('border-indigo-400');atSotoFileDrop(event)">
            <input type="file" id="at-soto-file-inp" class="hidden" accept="image/*" onchange="atSotoFileSelect(this)">
            <i class="fas fa-image text-gray-300 text-xl mb-1 block"></i>
            <p class="text-xs text-gray-400">Drop screenshot / paste (Ctrl+V) or click to browse</p>
            <img id="at-soto-preview" class="hidden max-h-28 mx-auto mt-2 rounded-lg">
        </div>
        <div class="flex items-center gap-2 my-2">
            <div class="flex-1 h-px bg-gray-200"></div>
            <span class="text-[10px] text-gray-400 uppercase">or type text</span>
            <div class="flex-1 h-px bg-gray-200"></div>
        </div>
        <textarea id="at-soto-text" rows="3" placeholder="Paste quotation text here…"
            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-xs resize-none focus:outline-none focus:border-indigo-400"></textarea>
        <div id="at-soto-extract-prog" class="hidden mt-2 text-xs text-green-600"><i class="fas fa-spinner fa-spin mr-1"></i>Extracting…</div>
    </div>

    <!-- Trip type & Route -->
    <div class="grid grid-cols-2 gap-3 mb-4">
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Trip Type</label>
            <select id="at-soto-trip" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-400">
                ${['One Way','Round Trip','Multi City'].map(t=>`<option ${(fd.trip_option??'One Way')===t?'selected':''}>${t}</option>`).join('')}
            </select>
        </div>
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Route</label>
            <input id="at-soto-route" value="${_e(fd.route??'')}" placeholder="DAC - DXB"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-400">
        </div>
    </div>

    <!-- Class + PAX -->
    <div class="grid grid-cols-2 gap-3 mb-4">
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Class</label>
            <div class="flex gap-3 pt-2">
                <label class="flex items-center gap-1.5 text-sm cursor-pointer"><input type="radio" name="at-soto-class" value="Economy" ${(fd.class??'Economy')==='Economy'?'checked':''}> Economy</label>
                <label class="flex items-center gap-1.5 text-sm cursor-pointer"><input type="radio" name="at-soto-class" value="Business" ${fd.class==='Business'?'checked':''}> Business</label>
            </div>
        </div>
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">PAX</label>
            <div class="flex gap-2">
                <div class="flex-1"><label class="text-[10px] text-gray-400">Adult</label><input id="at-soto-adult" type="number" min="0" value="${fd.pax_adult??1}" oninput="atSotoGenCopy()" class="w-full px-2 py-1.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-400"></div>
                <div class="flex-1"><label class="text-[10px] text-gray-400">Child</label><input id="at-soto-child" type="number" min="0" value="${fd.pax_child??0}" class="w-full px-2 py-1.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-400"></div>
                <div class="flex-1"><label class="text-[10px] text-gray-400">Infant</label><input id="at-soto-infant" type="number" min="0" value="${fd.pax_infant??0}" class="w-full px-2 py-1.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-400"></div>
            </div>
        </div>
    </div>

    <!-- Pricing -->
    <div class="space-y-3 mb-4">
        ${[0,1].map(i => `<div class="border border-gray-100 rounded-xl p-3 bg-gray-50">
            <input id="at-soto-bag-${i}" placeholder="Baggage option…" value="${_e(fd.prices?.[i]?.desc??'')}"
                oninput="atSotoGenCopy()"
                class="w-full px-3 py-1.5 border border-gray-200 rounded-lg text-xs mb-2 focus:outline-none focus:border-indigo-400">
            <div class="grid grid-cols-3 gap-2">
                <div><label class="text-[10px] text-gray-400 uppercase">Adult ৳</label>
                    <input type="number" id="at-soto-p${i}-adult" value="${fd.prices?.[i]?.adult??''}" oninput="atSotoGenCopy()" class="w-full px-2 py-1.5 border border-gray-200 rounded-lg text-xs focus:outline-none focus:border-indigo-400"></div>
                <div><label class="text-[10px] text-gray-400 uppercase">Child ৳</label>
                    <input type="number" id="at-soto-p${i}-child" value="${fd.prices?.[i]?.child??''}" oninput="atSotoGenCopy()" class="w-full px-2 py-1.5 border border-gray-200 rounded-lg text-xs focus:outline-none focus:border-indigo-400"></div>
                <div><label class="text-[10px] text-gray-400 uppercase">Infant ৳</label>
                    <input type="number" id="at-soto-p${i}-infant" value="${fd.prices?.[i]?.infant??''}" oninput="atSotoGenCopy()" class="w-full px-2 py-1.5 border border-gray-200 rounded-lg text-xs focus:outline-none focus:border-indigo-400"></div>
            </div>
        </div>`).join('')}
    </div>

    <!-- Refundable / Changeable -->
    <div class="grid grid-cols-2 gap-3 mb-4">
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Refundable</label>
            <div class="flex gap-3 pt-1">
                <label class="flex items-center gap-1.5 text-sm cursor-pointer"><input type="radio" name="at-soto-refund" value="Refundable" ${(fd.refundable??'Refundable')==='Refundable'?'checked':''}> Yes</label>
                <label class="flex items-center gap-1.5 text-sm cursor-pointer"><input type="radio" name="at-soto-refund" value="Non Refundable" ${fd.refundable==='Non Refundable'?'checked':''}> No</label>
            </div>
        </div>
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Changeable</label>
            <div class="flex gap-3 pt-1">
                <label class="flex items-center gap-1.5 text-sm cursor-pointer"><input type="radio" name="at-soto-change" value="Changeable" ${(fd.changeable??'Changeable')==='Changeable'?'checked':''}> Yes</label>
                <label class="flex items-center gap-1.5 text-sm cursor-pointer"><input type="radio" name="at-soto-change" value="Not Changeable" ${fd.changeable==='Not Changeable'?'checked':''}> No</label>
            </div>
        </div>
    </div>

    <!-- Business Markup -->
    <div class="border border-indigo-100 rounded-xl p-3 bg-indigo-50 mb-4">
        <label class="text-xs font-bold text-indigo-600 uppercase block mb-2">Business Markup</label>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="text-[10px] text-gray-500 uppercase">Markup %</label>
                <input type="number" id="at-soto-pct" value="${fd.percentage??0}" min="0" step="0.01" oninput="atSotoGenCopy()"
                    class="w-full px-3 py-2 border border-indigo-200 rounded-lg text-sm focus:outline-none focus:border-indigo-400 bg-white">
            </div>
            <div>
                <label class="text-[10px] text-gray-500 uppercase">Fixed Add (৳)</label>
                <input type="number" id="at-soto-fixed" value="${fd.ve_fixed_price??0}" min="0" step="0.01" oninput="atSotoGenCopy()"
                    class="w-full px-3 py-2 border border-indigo-200 rounded-lg text-sm focus:outline-none focus:border-indigo-400 bg-white">
            </div>
        </div>
        <p class="text-[10px] text-indigo-400 mt-1">Formula: final = (base × (1 + %/100)) + fixed</p>
    </div>

    <!-- Formatted Output -->
    <div class="mb-4">
        <div class="flex items-center justify-between mb-1">
            <label class="text-xs font-bold text-gray-500 uppercase">Formatted Output</label>
            <div class="flex gap-2">
                <button onclick="atSotoGenCopy()" class="text-xs text-indigo-500 hover:text-indigo-700 font-semibold"><i class="fas fa-sync-alt mr-1"></i>Generate</button>
                <button onclick="navigator.clipboard.writeText(document.getElementById('at-soto-raw-out').value);atT('success','Copied!')" class="text-xs text-gray-500 hover:text-gray-700 font-semibold"><i class="fas fa-copy mr-1"></i>Raw</button>
                <button onclick="navigator.clipboard.writeText(document.getElementById('at-soto-biz-out').value);atT('success','Copied!')" class="text-xs text-green-600 hover:text-green-700 font-semibold"><i class="fas fa-copy mr-1"></i>Business</button>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-2">
            <div>
                <label class="text-[10px] text-gray-400 uppercase mb-1 block">Raw (original prices)</label>
                <textarea id="at-soto-raw-out" rows="6" readonly
                    class="w-full px-3 py-2 border border-gray-100 bg-gray-50 rounded-xl text-xs font-mono resize-none"
                    placeholder="Click Generate…">${_e(fd.raw_text??'')}</textarea>
            </div>
            <div>
                <label class="text-[10px] text-green-600 uppercase mb-1 block">Business (with markup — to send)</label>
                <textarea id="at-soto-biz-out" rows="6" readonly
                    class="w-full px-3 py-2 border border-green-100 bg-green-50 rounded-xl text-xs font-mono resize-none"
                    placeholder="Click Generate…">${_e(fd.business_text??'')}</textarea>
            </div>
        </div>
    </div>

    <!-- Notes -->
    <div class="mb-4" id="at-soto-notes-wrap">
        <div class="flex items-center justify-between mb-1">
            <label class="text-xs font-bold text-gray-500 uppercase">Notes</label>
            <button onclick="atSotoAddNote()" class="text-xs text-indigo-500 hover:text-indigo-700 font-semibold"><i class="fas fa-plus mr-1"></i>Add</button>
        </div>
        <div id="at-soto-notes-list" class="space-y-1.5">
            ${(fd.notes??[]).map((n,i)=>`<div class="flex gap-2 items-center">
                <input value="${_e(n)}" oninput="atSotoNoteChange(${i},this.value)" class="flex-1 px-3 py-1.5 border border-gray-200 rounded-lg text-xs focus:outline-none focus:border-indigo-400">
                <button onclick="atSotoRmNote(${i})" class="text-red-400 hover:text-red-600 text-xs w-6 h-6 flex items-center justify-center"><i class="fas fa-times"></i></button>
            </div>`).join('')}
        </div>
    </div>

    <button onclick="atSaveQ('soto')" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition">
        <i class="fas fa-save mr-1.5"></i>${window._at.activeQSysId ? 'Update' : 'Save'} Quotation
    </button>`;
}

// ── SOTO helpers ──────────────────────────────────────────────
window._at.sotoNotes = [];
window._at.sotoFile = null;

window.atSotoFileSelect = function(input) {
    if (!input.files[0]) return;
    window._at.sotoFile = input.files[0];
    const preview = document.getElementById('at-soto-preview');
    if (preview) { preview.src = URL.createObjectURL(window._at.sotoFile); preview.classList.remove('hidden'); }
    document.getElementById('at-soto-img-zone')?.classList.add('border-green-400','bg-green-50');
};

window.atSotoFileDrop = function(e) {
    const f = e.dataTransfer.files[0];
    if (f && f.type.startsWith('image/')) {
        window._at.sotoFile = f;
        const preview = document.getElementById('at-soto-preview');
        if (preview) { preview.src = URL.createObjectURL(f); preview.classList.remove('hidden'); }
    }
};

// Ctrl+V paste support for SOTO
document.addEventListener('paste', function(e) {
    if (_activeTab !== 'quotation') return;
    const typeEl = document.querySelector('input[name="at-q-type"]:checked');
    if (typeEl?.value !== 'soto') return;
    const item = [...(e.clipboardData?.items??[])].find(i => i.type.startsWith('image/'));
    if (item) { 
        window._at.sotoFile = item.getAsFile(); 
        const preview = document.getElementById('at-soto-preview'); 
        if(preview){ 
            preview.src=URL.createObjectURL(window._at.sotoFile); 
            preview.classList.remove('hidden'); 
        } 
    }
});

window.atSotoExtract = async function() {
    const text = document.getElementById('at-soto-text')?.value.trim();
    if (!window._at.sotoFile && !text) { atT('error','Upload a screenshot or paste text first'); return; }
    const prog = document.getElementById('at-soto-extract-prog');
    prog?.classList.remove('hidden');
    const fd = new FormData();
    if (window._at.sotoFile) { fd.append('mode','image'); fd.append('screenshot', window._at.sotoFile); }
    else           { fd.append('mode','text');  fd.append('text_input', text); }
    try {
        const apiUrl = window._at.cfg.api.airTickets.replace('api/air-tickets/endpoints.php','api/tickets/ss-extraction.php');
        const res    = await fetch(apiUrl, { method:'POST', body:fd });
        const json   = await res.json();
        if (json.success && json.data) {
            _sotoFillForm(json.data);
            atT('success','Extracted & filled!');
        } else { atT('error', json.message ?? 'Extraction failed'); }
    } catch { atT('error','Network error'); }
    prog?.classList.add('hidden');
};

function _sotoFillForm(data) {
    const trip = document.getElementById('at-soto-trip');
    if (trip && data.trip_option) trip.value = data.trip_option;
    const route = document.getElementById('at-soto-route');
    if (route && data.route) route.value = data.route;
    if (data.class) {
        const r = document.querySelector(`input[name="at-soto-class"][value="${data.class}"]`);
        if (r) r.checked = true;
    }
    if (data.pax_adult  !== undefined) { const e=document.getElementById('at-soto-adult');  if(e) e.value=data.pax_adult; }
    if (data.pax_child  !== undefined) { const e=document.getElementById('at-soto-child');  if(e) e.value=data.pax_child; }
    if (data.pax_infant !== undefined) { const e=document.getElementById('at-soto-infant'); if(e) e.value=data.pax_infant; }
    atSotoGenCopy();
}

window.atSotoGenCopy = function() {
    const trip    = document.getElementById('at-soto-trip')?.value ?? 'One Way';
    const route   = document.getElementById('at-soto-route')?.value ?? '';
    const cls     = document.querySelector('input[name="at-soto-class"]:checked')?.value ?? 'Economy';
    const adult   = +(document.getElementById('at-soto-adult')?.value??0);
    const child   = +(document.getElementById('at-soto-child')?.value??0);
    const infant  = +(document.getElementById('at-soto-infant')?.value??0);
    const refund  = document.querySelector('input[name="at-soto-refund"]:checked')?.value ?? '';
    const change  = document.querySelector('input[name="at-soto-change"]:checked')?.value ?? '';
    const pct     = +(document.getElementById('at-soto-pct')?.value??0);
    const fixed   = +(document.getElementById('at-soto-fixed')?.value??0);
    const label   = trip==='One Way'?'One Way Ticket':trip==='Round Trip'?'Round Ticket':'Multi City Ticket';

    const paxParts = [adult>0?`${adult} Adult`:'', child>0?`${child} Child`:'', infant>0?`${infant} Infant`:''].filter(Boolean).join(', ');

    const applyMarkup = (price) => { const b=+price; if(!b||b<=0) return ''; return Math.round(b + b*pct/100 + fixed); };

    let raw='', biz='';
    raw += `*${label}*\nRoute: ${route} ${paxParts}\n${cls}\n\n*Price:*\n`;
    biz += `*${label}*\nRoute: ${route} ${paxParts}\n${cls}\n\n*Price:*\n`;

    [0,1].forEach(i => {
        const bag   = document.getElementById(`at-soto-bag-${i}`)?.value ?? '';
        const pa    = +(document.getElementById(`at-soto-p${i}-adult`)?.value??0);
        const pc    = +(document.getElementById(`at-soto-p${i}-child`)?.value??0);
        const pi    = +(document.getElementById(`at-soto-p${i}-infant`)?.value??0);
        if (!pa && !pc && !pi) return;
        const rawParts = [pa>0?`Adult ${pa.toLocaleString('en-BD')}`:'', pc>0?`Child ${pc.toLocaleString('en-BD')}`:'', pi>0?`Infant ${pi.toLocaleString('en-BD')}`:''].filter(Boolean).join(' | ');
        const bizPa = applyMarkup(pa), bizPc = applyMarkup(pc), bizPi = applyMarkup(pi);
        const bizParts = [bizPa?`Adult ${Number(bizPa).toLocaleString('en-BD')}`:'', bizPc?`Child ${Number(bizPc).toLocaleString('en-BD')}`:'', bizPi?`Infant ${Number(bizPi).toLocaleString('en-BD')}`:''].filter(Boolean).join(' | ');
        raw += `• ${bag}: BDT -> ${rawParts}\n`;
        biz += `• ${bag}: BDT -> ${bizParts}\n`;
    });

    raw += `\n${refund} | ${change}`;
    biz += `\n${refund} | ${change}`;

    const rawEl = document.getElementById('at-soto-raw-out');
    const bizEl = document.getElementById('at-soto-biz-out');
    if (rawEl) rawEl.value = raw;
    if (bizEl) bizEl.value = biz;
};

// SOTO notes helpers
window.atSotoAddNote = function() {
    window._at.sotoNotes.push('');
    const list = document.getElementById('at-soto-notes-list');
    if (!list) return;
    const i = window._at.sotoNotes.length - 1;
    const div = document.createElement('div');
    div.className = 'flex gap-2 items-center';
    div.id = `at-soto-note-${i}`;
    div.innerHTML = `<input value="" oninput="atSotoNoteChange(${i},this.value)" class="flex-1 px-3 py-1.5 border border-gray-200 rounded-lg text-xs focus:outline-none focus:border-indigo-400"><button onclick="atSotoRmNote(${i})" class="text-red-400 hover:text-red-600 text-xs w-6 h-6 flex items-center justify-center"><i class="fas fa-times"></i></button>`;
    list.appendChild(div);
};
window.atSotoNoteChange = function(i, val) { window._at.sotoNotes[i] = val; };
window.atSotoRmNote = function(i) { window._at.sotoNotes.splice(i,1); document.getElementById(`at-soto-note-${i}`)?.remove(); };

// Save quotation (GDS or SOTO)
window.atSaveQ = async function(pfx) {
    const typeEl = document.querySelector('input[name="at-q-type"]:checked');
    const qType  = typeEl?.value ?? 'gds';
    let body = { type: qType };

    if (qType === 'gds' || pfx === 'b') {
        atGenCopy(pfx === 'b' ? 'b' : 'q');
        body.airline       = document.getElementById(`at-${pfx==='b'?'b':'q'}-airline`)?.value ?? '';
        body.segments_json = window._at.gdsSegments;
        body.pricing_json  = window._at.gdsFares;
        body.raw_input     = document.getElementById(`at-${pfx==='b'?'b':'q'}-raw`)?.value ?? '';
        body.copy_text     = document.getElementById(`at-${pfx==='b'?'b':'q'}-copy`)?.value ?? '';
        body.gross_fare    = window._at.gdsFares.reduce((s,f) => s+(f.gross_fare??0)*(f.pax??1), 0);
        body.net_fare      = window._at.gdsFares.reduce((s,f) => s+(f.net_fare??0)*(f.pax??1), 0);
        body.total_payable = window._at.gdsFares.reduce((s,f) => s+(f.total_payable??0), 0);
    } else {
        atSotoGenCopy();
        const trip   = document.getElementById('at-soto-trip')?.value ?? '';
        const cls    = document.querySelector('input[name="at-soto-class"]:checked')?.value ?? '';
        const refund = document.querySelector('input[name="at-soto-refund"]:checked')?.value ?? '';
        const change = document.querySelector('input[name="at-soto-change"]:checked')?.value ?? '';
        const adult  = +(document.getElementById('at-soto-adult')?.value ?? 1);
        const child  = +(document.getElementById('at-soto-child')?.value ?? 0);
        const infant = +(document.getElementById('at-soto-infant')?.value ?? 0);
        const pct    = +(document.getElementById('at-soto-pct')?.value ?? 0);
        const fixed  = +(document.getElementById('at-soto-fixed')?.value ?? 0);
        const prices = [0,1].map(i => ({
            desc:   document.getElementById(`at-soto-bag-${i}`)?.value ?? '',
            adult:  +(document.getElementById(`at-soto-p${i}-adult`)?.value ?? 0),
            child:  +(document.getElementById(`at-soto-p${i}-child`)?.value ?? 0),
            infant: +(document.getElementById(`at-soto-p${i}-infant`)?.value ?? 0),
        }));
        const rawText = document.getElementById('at-soto-raw-out')?.value ?? '';
        const bizText = document.getElementById('at-soto-biz-out')?.value ?? '';

        body.title      = `${trip} — ${document.getElementById('at-soto-route')?.value ?? ''}`;
        body.copy_text  = bizText;
        body.raw_input  = rawText;
        body.form_data  = {
            trip_option: trip, class: cls,
            route: document.getElementById('at-soto-route')?.value ?? '',
            pax_adult: adult, pax_child: child, pax_infant: infant,
            refundable: refund, changeable: change,
            prices, percentage: pct, ve_fixed_price: fixed,
            raw_text: rawText, business_text: bizText,
            notes: window._at.sotoNotes.filter(Boolean),
        };
        const applyM = (p) => Math.round((+p || 0) * (1 + pct/100) + fixed);
        body.total_payable = applyM(prices[0].adult) * adult;
        body.gross_fare    = prices[0].adult * adult;
    }

    if (pfx === 'b') {
        body.pnr        = document.getElementById('at-b-pnr')?.value ?? '';
        body.ticket_nos = (document.getElementById('at-b-tickets')?.value ?? '').split(',').map(t=>t.trim()).filter(Boolean);
        if (window._at.activeBSysId) { body.action='update_booking'; body.booking_sys_id=window._at.activeBSysId; }
        else body.action = 'save_booking';
    } else {
        if (window._at.activeQSysId) { body.action='update_quotation'; body.quotation_sys_id=window._at.activeQSysId; }
        else body.action = 'save_quotation';
    }

    try {
        const json = await window._atApi(body);
        if (json.status === 'success') {
            atT('success', pfx==='b' ? (window._at.activeBSysId?'Updated!':'Booking saved!') : (window._at.activeQSysId?'Updated!':'Saved!'));
            if (pfx==='b') window._at.activeBSysId = json.booking_sys_id ?? window._at.activeBSysId;
            await window._atReload();
            pfx==='b' ? _renderBooking() : _renderQuotation();
        } else { atT('error', json.message); }
    } catch { atT('error','Network error'); }
};

window.atDeleteQ = async function() {
    if (!window._at.activeQSysId || !confirm('Delete quotation?')) return;
    try {
        const json = await window._atApi({ action:'delete_quotation', quotation_sys_id:window._at.activeQSysId });
        if (json.status==='success') { atT('success','Deleted'); await window._atReload(); window._at.activeQSysId=null; _renderQuotation(); }
        else atT('error', json.message);
    } catch { atT('error','Network error'); }
};

window.atMoveToBooking = async function() {
    if (!window._at.activeQSysId || !confirm('Move to Booking?')) return;
    try {
        const json = await window._atApi({ action:'move_to_booking', quotation_sys_id:window._at.activeQSysId });
        if (json.status==='success') {
            atT('success','Moved to Booking!');
            await window._atReload(); window._at.activeQSysId=null; _renderQuotation();
            const btn = document.querySelector('.at-tab[data-tab="booking"]');
            if (btn) window._atSwitchTab('booking', btn);
        } else { atT('error', json.message); }
    } catch { atT('error','Network error'); }
};

// ═══════════════════════════════════════════════════════════════
// TAB 2 — QUOTATION (continued)
// ═══════════════════════════════════════════════════════════════
window._at.qSelectedIds = new Set();

window._renderQuotation = function _renderQuotation() {
    const panel      = document.getElementById('at-panel-quotation');
    const quotations = window._at.data?.at_quotations ?? [];
    panel.innerHTML = `
    <div class="flex gap-4">
        <!-- LEFT: unified quotation list -->
        <div style="width:220px;flex-shrink:0;">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Quotations</span>
                <div class="flex gap-1.5">
                    <button onclick="atNewQuotation()"
                        class="flex items-center gap-1 px-2.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold transition">
                        <i class="fas fa-plus text-xs"></i>New
                    </button>
                </div>
            </div>
            <div id="at-q-list" class="space-y-1.5">
                ${quotations.length ? quotations.map(_qCard).join('') : '<p class="text-xs text-gray-300 text-center py-6">No quotations yet.</p>'}
            </div>
            <div id="at-q-move-multi" class="hidden mt-3">
                <div class="text-xs text-gray-500 mb-1.5 text-center" id="at-q-sel-count">0 selected</div>
                <button onclick="atMoveSelectedToBooking()"
                    class="w-full py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-xs font-semibold transition">
                    <i class="fas fa-arrow-right mr-1"></i>Move Selected to Booking
                </button>
            </div>
        </div>

        <!-- RIGHT: builder -->
        <div class="flex-1 min-w-0" id="at-q-builder">
            <div class="flex items-center justify-center h-40 text-gray-300 text-sm">
                <div class="text-center">
                    <i class="fas fa-plus-circle text-3xl mb-2 block opacity-30"></i>
                    Click New or select a quotation
                </div>
            </div>
        </div>
    </div>`;

    _gdsInjectPanel(panel, '2');
}

function _qPhaseInfo(q) {
    if (q.source_booking) {
        return { label: 'Revision', pill: 'at-pill-sent', dot: 'bg-blue-400' };
    }
    if (q.note?.includes('Auto-created from booking tab') || q.note?.includes('Direct')) {
        return { label: 'Direct', pill: 'at-pill-draft', dot: 'bg-teal-400' };
    }
    const sMap = {
        draft:            { label: 'Draft',      pill: 'at-pill-draft',   dot: 'bg-gray-400' },
        sent:             { label: 'Sent',       pill: 'at-pill-sent',    dot: 'bg-blue-400' },
        moved_to_booking: { label: '→ Booking', pill: 'at-pill-moved',   dot: 'bg-green-500' },
        cancelled:        { label: 'Cancelled',  pill: 'at-pill-cancelled', dot: 'bg-red-400' },
    };
    return sMap[q.status] ?? { label: q.status, pill: 'at-pill-draft', dot: 'bg-gray-300' };
}

window._qCard = function _qCard(q) {
    const phase     = _qPhaseInfo(q);
    const quotations = window._at.data?.at_quotations ?? [];
    const route     = (q.segments_json?.[0]?.route ?? q.form_data?.route ?? '')
                    + (q.segments_json?.length > 1 ? ' +' + (q.segments_json.length - 1) : '');
    const typePill  = q.type === 'soto'
        ? '<span class="at-pill at-pill-sent" style="font-size:.6rem">SOTO</span>'
        : '<span class="at-pill at-pill-draft" style="font-size:.6rem">GDS</span>';

    let isMovable = false;
    if (q.status === 'draft' || q.status === 'sent') {
        isMovable = true;
    } else if (q.source_booking) {
        isMovable = true;
    } else if (q.status === 'moved_to_booking') {
        const bookings = window._at.data?.at_bookings ?? [];
        const myBooking = bookings.find(b => b.quotation_sys_id === q.sys_id);
        if (myBooking) {
            const hasRevision = quotations.some(r => r.source_booking === myBooking.sys_id);
            isMovable = hasRevision;
        }
    }

    const isSelected = window._at.qSelectedIds.has(q.sys_id);
    const dimmed     = q.status === 'cancelled' ? 'opacity-40' : '';

    return `<div class="at-q-card ${window._at.activeQSysId===q.sys_id?'active':''} ${dimmed}" onclick="atSelectQuotation('${_e(q.sys_id)}')">
        <div class="flex items-center justify-between mb-1">
            <div class="flex items-center gap-1.5">
                <div class="w-2 h-2 rounded-full ${phase.dot} flex-shrink-0"></div>
                <span class="font-mono text-[10px] text-gray-400">${_e(q.sys_id)}</span>
            </div>
            <div class="flex items-center gap-1">
                ${typePill}
                ${isMovable ? `<input type="checkbox" class="at-q-chk w-3.5 h-3.5 rounded cursor-pointer" data-qid="${_e(q.sys_id)}"
                    ${isSelected?'checked':''} onclick="event.stopPropagation();atToggleQSel('${_e(q.sys_id)}',this)">` : ''}
            </div>
        </div>
        <div class="text-xs font-semibold text-gray-700 truncate">${_e(q.airline||q.title||'—')}</div>
        <div class="text-[11px] text-gray-400">${_e(route)}</div>
        <div class="flex items-center justify-between mt-1">
            <span class="text-[11px] font-bold text-indigo-600">৳ ${_fmt(q.total_payable)}</span>
            <span class="at-pill ${phase.pill}" style="font-size:.6rem">${phase.label}</span>
        </div>
        ${q.note ? `<div class="text-[10px] text-gray-300 mt-0.5 truncate" title="${_e(q.note)}">${_e(q.note)}</div>` : ''}
    </div>`;
}

window.atToggleQSel = function(sysId, cb) {
    if (cb.checked) window._at.qSelectedIds.add(sysId);
    else            window._at.qSelectedIds.delete(sysId);
    const btn = document.getElementById('at-q-move-multi');
    const cnt = document.getElementById('at-q-sel-count');
    if (window._at.qSelectedIds.size > 0) {
        btn?.classList.remove('hidden');
        if (cnt) cnt.textContent = `${window._at.qSelectedIds.size} selected`;
    } else {
        btn?.classList.add('hidden');
    }
};

window.atNewQuotation = function() {
    window._at.activeQSysId = null; window._at.gdsSegments = []; window._at.gdsFares = [];
    _renderQBuilder(null);
    window._at.qSelectedIds.clear();
    document.getElementById('at-q-move-multi')?.classList.add('hidden');
};

window.atSelectQuotation = function(sysId) {
    window._at.activeQSysId = sysId;
    const q = (window._at.data?.at_quotations ?? []).find(x => x.sys_id === sysId);
    if (!q) return;
    document.querySelectorAll('#at-q-list .at-q-card').forEach(c => c.classList.remove('active'));
    event?.currentTarget?.classList.add('active');
    window._at.gdsSegments = q.segments_json ?? [];
    window._at.gdsFares    = q.pricing_json  ?? [];
    _renderQBuilder(q);
    if (q.type === 'gds' || !q.type) setTimeout(() => _recalcAllFares('q'), 50);
};

window._renderQBuilder = function _renderQBuilder(q) {
    const builder = document.getElementById('at-q-builder');
    if (!builder) return;
    const type = q?.type ?? 'gds';
    builder.innerHTML = `
    <div class="flex items-center gap-3 mb-4">
        <span class="text-xs font-bold text-gray-400 uppercase">Type:</span>
        <label class="flex items-center gap-1.5 cursor-pointer">
            <input type="radio" name="at-q-type" value="gds" ${type==='gds'?'checked':''} onchange="atQTypeChange('gds')">
            <span class="text-sm">GDS</span>
        </label>
        <label class="flex items-center gap-1.5 cursor-pointer">
            <input type="radio" name="at-q-type" value="soto" ${type==='soto'?'checked':''} onchange="atQTypeChange('soto')">
            <span class="text-sm">SOTO</span>
        </label>
    </div>
    <div id="at-q-body">${type === 'gds' ? _gdsHtml(q, 'q') : _sotoHtml(q)}</div>`;
}

window.atQTypeChange = function(type) {
    const body = document.getElementById('at-q-body');
    if (body) body.innerHTML = type === 'gds' ? _gdsHtml(null, 'q') : _sotoHtml(null);
};

window.atMoveSelectedToBooking = async function() {
    if (window._at.qSelectedIds.size === 0) return;
    const ids = [...window._at.qSelectedIds];
    if (!confirm(`Move ${ids.length} quotation(s) to Booking?`)) return;
    let successCount = 0;
    for (const qSysId of ids) {
        try {
            const json = await window._atApi({ action: 'move_to_booking', quotation_sys_id: qSysId });
            if (json.status === 'success') successCount++;
        } catch {}
    }
    window._at.qSelectedIds.clear();
    await window._atReload();
    _renderQuotation();
    atT('success', `${successCount} quotation(s) moved to Booking!`);
    if (successCount > 0) {
        const btn = document.querySelector('.at-tab[data-tab="booking"]');
        if (btn) window._atSwitchTab('booking', btn);
    }
};