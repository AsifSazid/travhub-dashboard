/**
 * FILE PATH: /pages/task-tabs/ww-package/quotation.js
 * Tab: Quotation
 *
 * Features:
 *  - List all pk_quotations
 *  - Create new manually OR from confirmed AT+Hotel data
 *  - Voice STT into itinerary field
 *  - AI Polish / AI Restructure for manually typed itinerary
 *  - Pricing calculator (AT+Hotel+Transport+Visa+Others → Markup → Gross → Per Pax)
 *  - Edit / Delete quotation
 */

// ── Render ────────────────────────────────────────────────────
window._renderPkQuotation = async function() {
    const panel = document.getElementById('pk-panel-quotation');
    if (!panel) return;

    const quotations = window._pk.data?.pk_quotations ?? [];
    const confSysId  = window._pk.data?.pk_confirmation?.quotation_sys_id ?? null;

    panel.innerHTML = `
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
        <h3 style="font-size:14px;font-weight:700;color:#1F2937;margin:0;">Package Quotations</h3>
        <button id="pk-new-q-btn" style="padding:6px 12px;font-size:12px;font-weight:600;background:#16a34a;color:#fff;border:none;border-radius:8px;cursor:pointer;">
            <i class="fas fa-plus mr-1"></i>New Quotation
        </button>
    </div>

    <!-- Quotation list -->
    <div id="pk-q-list">
        ${quotations.length === 0
            ? `<div class="text-center py-10 text-gray-300 text-sm"><i class="fas fa-file-invoice text-3xl block mb-2 opacity-20"></i>No quotations yet.<br><small>Use Mindboard to generate, or create manually.</small></div>`
            : quotations.map(q => _pkQCard(q, confSysId)).join('')
        }
    </div>

    <!-- New / Edit Quotation Form (hidden by default) -->
    <div id="pk-q-form-wrap" style="display:none;margin-top:20px;background:#fff;border:1.5px solid #E5E7EB;border-radius:14px;padding:16px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
            <span id="pk-q-form-title" style="font-size:13px;font-weight:700;color:#1F2937;">New Quotation</span>
            <button data-action="q-form-close" style="background:none;border:none;color:#9CA3AF;cursor:pointer;font-size:14px;"><i class="fas fa-times"></i></button>
        </div>

        <!-- Pull from confirmed services -->
        <div id="pk-pull-bar" style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:10px;padding:10px 12px;margin-bottom:14px;display:flex;align-items:center;gap:8px;">
            <i class="fas fa-link text-blue-500" style="font-size:11px;"></i>
            <span style="font-size:11px;color:#1E40AF;flex:1;">Pull confirmed Air Ticket + Hotel data</span>
            <button id="pk-pull-btn" style="padding:4px 10px;font-size:11px;font-weight:600;background:#3b82f6;color:#fff;border:none;border-radius:6px;cursor:pointer;">
                <i class="fas fa-download mr-1"></i>Pull
            </button>
        </div>

        <input type="hidden" id="pk-q-edit-id" value="">

        <!-- Title -->
        <div style="margin-bottom:10px;">
            <label style="font-size:11px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Title *</label>
            <input id="pk-q-title" type="text" placeholder="e.g. Dubai 5N6D — Mar 2026"
                style="width:100%;padding:8px 12px;font-size:12px;border:1.5px solid #E5E7EB;border-radius:8px;outline:none;">
        </div>

        <!-- Destinations + Duration + Pax (row) -->
        <div style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:8px;margin-bottom:10px;">
            <div>
                <label style="font-size:11px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Destinations</label>
                <input id="pk-q-destinations" type="text" placeholder="e.g. Dubai, Bangkok"
                    style="width:100%;padding:8px 12px;font-size:12px;border:1.5px solid #E5E7EB;border-radius:8px;outline:none;">
            </div>
            <div>
                <label style="font-size:11px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Duration (Days)</label>
                <input id="pk-q-days" type="number" min="1" value="5"
                    style="width:100%;padding:8px 12px;font-size:12px;border:1.5px solid #E5E7EB;border-radius:8px;outline:none;">
            </div>
            <div>
                <label style="font-size:11px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Pax</label>
                <input id="pk-q-pax" type="number" min="1" value="1" oninput="_pkQCalc()"
                    style="width:100%;padding:8px 12px;font-size:12px;border:1.5px solid #E5E7EB;border-radius:8px;outline:none;">
            </div>
        </div>

        <!-- Itinerary -->
        <div style="margin-bottom:10px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                <label style="font-size:11px;font-weight:600;color:#374151;">Itinerary</label>
                <div style="display:flex;gap:6px;">
                    <button id="pk-q-stt-btn" title="Voice to text" style="padding:3px 8px;font-size:10px;font-weight:600;background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;border-radius:6px;cursor:pointer;">
                        <i class="fas fa-microphone-lines mr-1"></i>Voice
                    </button>
                    <button id="pk-q-polish-btn" title="AI Polish" style="padding:3px 8px;font-size:10px;font-weight:600;background:#eff6ff;color:#3b82f6;border:1px solid #bfdbfe;border-radius:6px;cursor:pointer;">
                        <i class="fas fa-magic mr-1"></i>AI Polish
                    </button>
                </div>
            </div>
            <textarea id="pk-q-itinerary" rows="7"
                placeholder="Day 1: Arrival…&#10;Day 2: …"
                style="width:100%;padding:10px 12px;font-size:12px;color:#374151;border:1.5px solid #E5E7EB;border-radius:8px;resize:vertical;line-height:1.7;"></textarea>
        </div>

        <!-- Service descriptions -->
        <div style="margin-bottom:12px;">
            <div style="font-size:11px;font-weight:700;color:#374151;text-transform:uppercase;margin-bottom:8px;">Services &amp; Costs</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;">
                ${_pkSvcInput('at',        'Air Ticket',  'fa-plane',          '#6366f1')}
                ${_pkSvcInput('hotel',     'Hotel',       'fa-hotel',          '#ec4899')}
                ${_pkSvcInput('transport', 'Transport',   'fa-van-shuttle',    '#14b8a6')}
                ${_pkSvcInput('visa',      'Visa',        'fa-passport',       '#8b5cf6')}
            </div>
            <!-- Others -->
            <div id="pk-others-list" style="margin-top:6px;display:flex;flex-direction:column;gap:4px;"></div>
            <button data-action="add-other" style="margin-top:6px;padding:4px 10px;font-size:11px;font-weight:600;background:#f9fafb;color:#6B7280;border:1px dashed #D1D5DB;border-radius:6px;cursor:pointer;">
                <i class="fas fa-plus mr-1"></i>Add Other Cost
            </button>
        </div>

        <!-- Markup + Pricing summary -->
        <div style="background:#F9FAFB;border:1px solid #E5E7EB;border-radius:10px;padding:12px;margin-bottom:14px;">
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;align-items:end;margin-bottom:10px;">
                <div>
                    <label style="font-size:10px;font-weight:600;color:#374151;display:block;margin-bottom:3px;">Markup %</label>
                    <input id="pk-q-markup" type="number" min="0" value="10" oninput="_pkQCalc()"
                        style="width:100%;padding:7px 10px;font-size:12px;border:1.5px solid #E5E7EB;border-radius:7px;outline:none;">
                </div>
                <div>
                    <label style="font-size:10px;font-weight:600;color:#374151;display:block;margin-bottom:3px;">Currency</label>
                    <select id="pk-q-currency" onchange="_pkQCalc()"
                        style="width:100%;padding:7px 10px;font-size:12px;border:1.5px solid #E5E7EB;border-radius:7px;background:#fff;outline:none;">
                        <option value="BDT">BDT</option>
                        <option value="USD">USD</option>
                        <option value="EUR">EUR</option>
                        <option value="AED">AED</option>
                        <option value="SAR">SAR</option>
                    </select>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:10px;color:#9CA3AF;">Gross Total</div>
                    <div id="pk-q-gross" style="font-size:18px;font-weight:800;color:#16a34a;">— BDT</div>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:6px;font-size:11px;">
                <div><div style="color:#9CA3AF;">Base</div><div id="pk-q-base" style="font-weight:600;color:#374151;">—</div></div>
                <div><div style="color:#9CA3AF;">Markup</div><div id="pk-q-mkamt" style="font-weight:600;color:#374151;">—</div></div>
                <div><div style="color:#9CA3AF;">Per Pax</div><div id="pk-q-perpax" style="font-weight:600;color:#16a34a;">—</div></div>
                <div><div style="color:#9CA3AF;">Pax</div><div id="pk-q-pax-disp" style="font-weight:600;color:#374151;">1</div></div>
            </div>
        </div>

        <button id="pk-q-save-btn" style="width:100%;padding:10px;font-size:13px;font-weight:600;background:#16a34a;color:#fff;border:none;border-radius:10px;cursor:pointer;">
            <i class="fas fa-save mr-1.5"></i>Save Quotation
        </button>
    </div>`;

    // ── Event listeners ───────────────────────────────────────
    panel.addEventListener('click', _pkQClick);
    panel.addEventListener('input', _pkQInput);

    // STT + Polish buttons
    document.getElementById('pk-q-stt-btn')?.addEventListener('click', _pkQToggleSTT);
    document.getElementById('pk-q-polish-btn')?.addEventListener('click', _pkQPolish);
    document.getElementById('pk-q-save-btn')?.addEventListener('click', _pkQSave);
    document.getElementById('pk-new-q-btn')?.addEventListener('click', _pkQOpenForm);
    document.getElementById('pk-pull-btn')?.addEventListener('click', _pkQPullServices);

    _pkQCalc();
};

// ── Quotation card ────────────────────────────────────────────
function _pkQCard(q, confSysId) {
    const pr      = q.pricing ?? {};
    const isConf  = confSysId === q.sys_id;
    const destStr = (q.destinations ?? []).map(d => [d.country, d.city].filter(Boolean).join(', ')).filter(Boolean).join(' · ');

    return `<div style="background:#fff;border:1.5px solid ${isConf?'#86efac':'#E5E7EB'};border-radius:12px;padding:14px;margin-bottom:10px;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;">
            <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">
                    <span style="font-size:10px;font-weight:700;color:#9CA3AF;background:#F3F4F6;padding:2px 8px;border-radius:10px;">${_pke(q.sys_id)}</span>
                    ${isConf ? `<span style="font-size:10px;font-weight:700;color:#16a34a;background:#DCFCE7;padding:2px 8px;border-radius:10px;"><i class="fas fa-check-circle mr-1"></i>Confirmed</span>` : ''}
                </div>
                <div style="font-size:13px;font-weight:700;color:#1F2937;margin-bottom:2px;">${_pke(q.title || '(Untitled)')}</div>
                ${destStr ? `<div style="font-size:11px;color:#6B7280;"><i class="fas fa-map-marker-alt mr-1 text-green-500"></i>${_pke(destStr)}</div>` : ''}
                <div style="font-size:11px;color:#9CA3AF;margin-top:2px;">${q.duration_days||0} Days · ${q.pax||1} Pax</div>
            </div>
            <div style="text-align:right;flex-shrink:0;">
                <div style="font-size:11px;color:#9CA3AF;">Gross Total</div>
                <div style="font-size:15px;font-weight:800;color:#16a34a;">${_pke(pr.currency||'BDT')} ${_pkFmt(pr.gross_total)}</div>
                <div style="font-size:10px;color:#6B7280;">Per Pax: ${_pkFmt(pr.per_pax)}</div>
            </div>
        </div>

        ${q.itinerary ? `<div style="margin-top:8px;background:#F9FAFB;border-radius:8px;padding:8px 10px;font-size:11px;color:#374151;white-space:pre-line;max-height:60px;overflow:hidden;position:relative;">
            ${_pke(q.itinerary.slice(0, 200))}${q.itinerary.length > 200 ? '…' : ''}
        </div>` : ''}

        <div style="display:flex;gap:6px;margin-top:10px;">
            ${!isConf ? `<button data-action="q-confirm" data-id="${_pke(q.sys_id)}" style="flex:1;padding:7px;font-size:12px;font-weight:600;background:#16a34a;color:#fff;border:none;border-radius:8px;cursor:pointer;">
                <i class="fas fa-check mr-1"></i>Confirm &amp; Create Task
            </button>` : ''}
            <button data-action="q-edit" data-id="${_pke(q.sys_id)}" style="padding:7px 12px;font-size:12px;font-weight:600;background:#F3F4F6;color:#374151;border:none;border-radius:8px;cursor:pointer;">
                <i class="fas fa-edit mr-1"></i>Edit
            </button>
            ${!isConf ? `<button data-action="q-delete" data-id="${_pke(q.sys_id)}" style="padding:7px 10px;font-size:12px;background:#FEF2F2;color:#EF4444;border:none;border-radius:8px;cursor:pointer;">
                <i class="fas fa-trash"></i>
            </button>` : ''}
        </div>
    </div>`;
}

// ── Service input row ─────────────────────────────────────────
function _pkSvcInput(key, label, icon, color) {
    return `<div style="background:#F9FAFB;border:1px solid #E5E7EB;border-radius:10px;padding:10px;">
        <div style="font-size:10px;font-weight:700;color:#374151;margin-bottom:6px;"><i class="fas ${icon} mr-1" style="color:${color};"></i>${label}</div>
        <input type="text" id="pk-svc-${key}-desc" placeholder="Description" data-svc="${key}"
            style="width:100%;padding:5px 8px;font-size:11px;border:1px solid #E5E7EB;border-radius:6px;margin-bottom:4px;outline:none;">
        <input type="number" id="pk-svc-${key}-cost" placeholder="Cost" min="0" value="0" data-svc="${key}" oninput="_pkQCalc()"
            style="width:100%;padding:5px 8px;font-size:11px;border:1px solid #E5E7EB;border-radius:6px;outline:none;">
    </div>`;
}

// ── Click delegation ──────────────────────────────────────────
async function _pkQClick(e) {
    const btn = e.target.closest('[data-action]');
    if (!btn) return;
    const act = btn.dataset.action;

    if (act === 'q-form-close') {
        document.getElementById('pk-q-form-wrap').style.display = 'none';
        document.getElementById('pk-q-edit-id').value = '';
    }
    if (act === 'q-edit')    _pkQEdit(btn.dataset.id);
    if (act === 'q-delete')  _pkQDelete(btn.dataset.id);
    if (act === 'q-confirm') _pkQConfirmModal(btn.dataset.id);
    if (act === 'add-other') _pkQAddOtherRow();
    if (act === 'rm-other')  btn.closest('.pk-other-row').remove(), _pkQCalc();
}

function _pkQInput(e) {
    if (e.target.matches('[data-svc]') || e.target.id === 'pk-q-markup' || e.target.id === 'pk-q-pax') {
        _pkQCalc();
    }
}

// ── Form open ─────────────────────────────────────────────────
function _pkQOpenForm() {
    const wrap = document.getElementById('pk-q-form-wrap');
    wrap.style.display = 'block';
    wrap.scrollIntoView({ behavior:'smooth', block:'start' });
    // Reset
    document.getElementById('pk-q-edit-id').value = '';
    document.getElementById('pk-q-form-title').textContent = 'New Quotation';
    document.getElementById('pk-q-title').value       = '';
    document.getElementById('pk-q-destinations').value = '';
    document.getElementById('pk-q-days').value        = '5';
    document.getElementById('pk-q-pax').value         = '1';
    document.getElementById('pk-q-itinerary').value   = '';
    document.getElementById('pk-q-markup').value      = '10';
    ['at','hotel','transport','visa'].forEach(k => {
        document.getElementById(`pk-svc-${k}-desc`).value = '';
        document.getElementById(`pk-svc-${k}-cost`).value = '0';
    });
    document.getElementById('pk-others-list').innerHTML = '';
    _pkQCalc();
}

// ── Pull from confirmed AT + Hotel ────────────────────────────
async function _pkQPullServices() {
    const btn = document.getElementById('pk-pull-btn');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Pulling…'; }
    try {
        const url  = `${window._pk.cfg.api.packageServices}?action=get_confirmed_services&work_sys_id=${encodeURIComponent(window._pk.cfg.workSysId)}`;
        const res  = await fetch(url);
        const json = await res.json();
        if (json.status !== 'success') { pkT('error', 'Failed to fetch services'); return; }

        const svcs = json.data;
        let title = '';

        // AT
        if (svcs.air_tickets.length > 0) {
            const at = svcs.air_tickets[0];
            document.getElementById('pk-svc-at-desc').value = `${at.label} (${at.airline}, PNR: ${at.pnr})`.trim();
            document.getElementById('pk-svc-at-cost').value = at.cost || 0;
            if (at.depart) title += at.depart.slice(0,7) + ' ';
        }
        // Hotel
        if (svcs.hotels.length > 0) {
            const ht = svcs.hotels[0];
            const desc = [ht.hotel_name, ht.city, `${ht.nights}N`, ht.meal_plan].filter(Boolean).join(' · ');
            document.getElementById('pk-svc-hotel-desc').value = desc;
            document.getElementById('pk-svc-hotel-cost').value = ht.cost || 0;
            if (ht.hotel_name) title = ht.hotel_name + (title ? ' — ' + title : '');
            // Auto duration
            if (ht.nights) document.getElementById('pk-q-days').value = ht.nights + 1;
        }

        if (title) document.getElementById('pk-q-title').value = title.trim();
        _pkQCalc();
        pkT('success', 'Services pulled from confirmed data!');
    } catch(e) { pkT('error', 'Network error'); }
    finally { if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-download mr-1"></i>Pull'; } }
}

// ── Calc pricing ──────────────────────────────────────────────
function _pkQCalc() {
    const atC   = parseFloat(document.getElementById('pk-svc-at-cost')?.value        ?? 0) || 0;
    const htC   = parseFloat(document.getElementById('pk-svc-hotel-cost')?.value     ?? 0) || 0;
    const tsC   = parseFloat(document.getElementById('pk-svc-transport-cost')?.value ?? 0) || 0;
    const viC   = parseFloat(document.getElementById('pk-svc-visa-cost')?.value      ?? 0) || 0;
    const pax   = Math.max(1, parseInt(document.getElementById('pk-q-pax')?.value    ?? 1) || 1);
    const mkPct = parseFloat(document.getElementById('pk-q-markup')?.value           ?? 10) || 0;
    const cur   = document.getElementById('pk-q-currency')?.value ?? 'BDT';

    let othersC = 0;
    document.querySelectorAll('.pk-other-cost').forEach(inp => { othersC += parseFloat(inp.value) || 0; });

    const base  = atC + htC + tsC + viC + othersC;
    const mkAmt = Math.round(base * mkPct / 100);
    const gross = base + mkAmt;
    const per   = pax > 0 ? Math.round(gross / pax) : gross;

    const f = n => n.toLocaleString('en-BD');
    const el = id => document.getElementById(id);
    if (el('pk-q-base'))    el('pk-q-base').textContent   = `${cur} ${f(base)}`;
    if (el('pk-q-mkamt'))   el('pk-q-mkamt').textContent  = `+ ${f(mkAmt)}`;
    if (el('pk-q-gross'))   el('pk-q-gross').textContent  = `${cur} ${f(gross)}`;
    if (el('pk-q-perpax'))  el('pk-q-perpax').textContent = `${cur} ${f(per)}`;
    if (el('pk-q-pax-disp')) el('pk-q-pax-disp').textContent = pax;
}

// ── Others row ────────────────────────────────────────────────
function _pkQAddOtherRow() {
    const list = document.getElementById('pk-others-list');
    const div  = document.createElement('div');
    div.className = 'pk-other-row';
    div.style.cssText = 'display:grid;grid-template-columns:2fr 1fr auto;gap:6px;align-items:center;';
    div.innerHTML = `
        <input type="text" placeholder="Label (e.g. Insurance)" class="pk-other-label"
            style="padding:5px 8px;font-size:11px;border:1px solid #E5E7EB;border-radius:6px;outline:none;">
        <input type="number" min="0" placeholder="Cost" class="pk-other-cost" oninput="_pkQCalc()"
            style="padding:5px 8px;font-size:11px;border:1px solid #E5E7EB;border-radius:6px;outline:none;">
        <button data-action="rm-other" style="width:28px;height:28px;background:#FEF2F2;color:#EF4444;border:none;border-radius:6px;cursor:pointer;font-size:11px;"><i class="fas fa-times"></i></button>`;
    list.appendChild(div);
}

// ── STT for quotation itinerary ───────────────────────────────
function _pkQToggleSTT() {
    const btn = document.getElementById('pk-q-stt-btn');
    if (window._pk.sttActive && window._pk.sttTarget === 'quotation') {
        window._pkStopSTT();
        if (btn) { btn.style.background='#f0fdf4'; btn.style.color='#16a34a'; btn.innerHTML='<i class="fas fa-microphone-lines mr-1"></i>Voice'; }
    } else {
        window._pk.sttTarget = 'quotation';
        window._pkStartSTT('pk-q-itinerary');
        if (btn) { btn.style.background='#dcfce7'; btn.style.color='#15803d'; btn.innerHTML='<i class="fas fa-stop mr-1"></i>Stop'; }
    }
}

// ── AI Polish ─────────────────────────────────────────────────
async function _pkQPolish() {
    const ta  = document.getElementById('pk-q-itinerary');
    const btn = document.getElementById('pk-q-polish-btn');
    if (!ta || !ta.value.trim()) { pkT('warn','Itinerary is empty'); return; }
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Polishing…'; }
    const polished = await window._pkPolishText(ta.value, 'package');
    ta.value = polished;
    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-magic mr-1"></i>AI Polish'; }
}

// ── Edit quotation ────────────────────────────────────────────
function _pkQEdit(qSysId) {
    const q = (window._pk.data?.pk_quotations ?? []).find(x => x.sys_id === qSysId);
    if (!q) { pkT('error','Quotation not found'); return; }

    const pr  = q.pricing  ?? {};
    const svc = q.services ?? {};

    document.getElementById('pk-q-edit-id').value        = qSysId;
    document.getElementById('pk-q-form-title').textContent = `Edit ${qSysId}`;
    document.getElementById('pk-q-title').value           = q.title        ?? '';
    document.getElementById('pk-q-days').value            = q.duration_days ?? 5;
    document.getElementById('pk-q-pax').value             = q.pax           ?? 1;
    document.getElementById('pk-q-itinerary').value       = q.itinerary     ?? '';
    document.getElementById('pk-q-markup').value          = pr.markup_pct   ?? 10;
    document.getElementById('pk-q-currency').value        = pr.currency     ?? 'BDT';

    const destStr = (q.destinations ?? []).map(d => [d.country, d.city].filter(Boolean).join(',')).join(', ');
    document.getElementById('pk-q-destinations').value    = destStr;

    ['at','hotel','transport','visa'].forEach(k => {
        const svcKey = k === 'at' ? 'air_ticket' : k;
        document.getElementById(`pk-svc-${k}-desc`).value = svc[svcKey]?.description ?? '';
        document.getElementById(`pk-svc-${k}-cost`).value = pr[k === 'at' ? 'at_cost' : `${k}_cost`] ?? 0;
    });

    // Others
    const othersList = document.getElementById('pk-others-list');
    othersList.innerHTML = '';
    (svc.others ?? []).forEach(o => {
        _pkQAddOtherRow();
        const rows = othersList.querySelectorAll('.pk-other-row');
        const last = rows[rows.length - 1];
        if (last) {
            last.querySelector('.pk-other-label').value = o.label ?? '';
            last.querySelector('.pk-other-cost').value  = o.cost  ?? 0;
        }
    });

    document.getElementById('pk-q-form-wrap').style.display = 'block';
    document.getElementById('pk-q-form-wrap').scrollIntoView({ behavior:'smooth', block:'start' });
    _pkQCalc();
}

// ── Save quotation ────────────────────────────────────────────
async function _pkQSave() {
    const title    = document.getElementById('pk-q-title')?.value.trim();
    if (!title) { pkT('error', 'Title required'); return; }

    const editId   = document.getElementById('pk-q-edit-id')?.value ?? '';
    const cur      = document.getElementById('pk-q-currency')?.value ?? 'BDT';
    const mkPct    = parseFloat(document.getElementById('pk-q-markup')?.value) || 10;
    const pax      = parseInt(document.getElementById('pk-q-pax')?.value) || 1;

    const atC  = parseFloat(document.getElementById('pk-svc-at-cost')?.value)        || 0;
    const htC  = parseFloat(document.getElementById('pk-svc-hotel-cost')?.value)     || 0;
    const tsC  = parseFloat(document.getElementById('pk-svc-transport-cost')?.value) || 0;
    const viC  = parseFloat(document.getElementById('pk-svc-visa-cost')?.value)      || 0;

    let othersC = 0;
    const othersArr = [];
    document.querySelectorAll('.pk-other-row').forEach(row => {
        const label = row.querySelector('.pk-other-label')?.value.trim() ?? '';
        const cost  = parseFloat(row.querySelector('.pk-other-cost')?.value) || 0;
        othersArr.push({ label, cost });
        othersC += cost;
    });

    const base  = atC + htC + tsC + viC + othersC;
    const mkAmt = Math.round(base * mkPct / 100);
    const gross = base + mkAmt;
    const per   = pax > 0 ? Math.round(gross / pax) : gross;

    // Parse destinations
    const destStr = document.getElementById('pk-q-destinations')?.value.trim() ?? '';
    const destinations = destStr.split(',').map(s => s.trim()).filter(Boolean).map(s => {
        const parts = s.split(/[,/]/).map(x => x.trim());
        return { country: parts[0] ?? s, city: parts[1] ?? '' };
    });

    const payload = {
        action:        editId ? 'update_quotation' : 'save_quotation',
        title,
        itinerary:     document.getElementById('pk-q-itinerary')?.value.trim() ?? '',
        destinations,
        duration_days: parseInt(document.getElementById('pk-q-days')?.value) || 0,
        pax,
        services: {
            air_ticket:  { description: document.getElementById('pk-svc-at-desc')?.value.trim(),        cost: atC },
            hotel:       { description: document.getElementById('pk-svc-hotel-desc')?.value.trim(),     cost: htC },
            transport:   { description: document.getElementById('pk-svc-transport-desc')?.value.trim(), cost: tsC },
            visa:        { description: document.getElementById('pk-svc-visa-desc')?.value.trim(),      cost: viC },
            others:      othersArr,
        },
        pricing: {
            at_cost: atC, hotel_cost: htC, transport_cost: tsC, visa_cost: viC, others_cost: othersC,
            base_cost: base, markup_pct: mkPct, markup_amount: mkAmt,
            gross_total: gross, per_pax: per, currency: cur, pax,
        },
    };
    if (editId) payload.q_sys_id = editId;

    const btn = document.getElementById('pk-q-save-btn');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5"></i>Saving…'; }

    try {
        const json = await window._pkApi(payload);
        if (json.status === 'success') {
            pkT('success', editId ? 'Quotation updated!' : 'Quotation saved!');
            document.getElementById('pk-q-form-wrap').style.display = 'none';
            await window._pkReload();
            window._renderPkQuotation();
        } else {
            pkT('error', json.message || 'Save ব্যর্থ');
        }
    } catch(e) { pkT('error', 'Network error'); }
    finally { if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-save mr-1.5"></i>Save Quotation'; } }
}

// ── Delete quotation ──────────────────────────────────────────
async function _pkQDelete(qSysId) {
    if (!confirm(`Delete ${qSysId}?`)) return;
    try {
        const json = await window._pkApi({ action:'delete_quotation', q_sys_id: qSysId });
        if (json.status === 'success') {
            pkT('success', 'Deleted');
            await window._pkReload();
            window._renderPkQuotation();
        } else { pkT('error', json.message || 'Delete ব্যর্থ'); }
    } catch(e) { pkT('error', 'Network error'); }
}

// ── Confirm modal ─────────────────────────────────────────────
function _pkQConfirmModal(qSysId) {
    const q  = (window._pk.data?.pk_quotations ?? []).find(x => x.sys_id === qSysId);
    if (!q) return;
    const pr = q.pricing ?? {};

    // Simple modal overlay
    const ov = document.createElement('div');
    ov.id    = 'pk-conf-modal';
    ov.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9998;display:flex;align-items:center;justify-content:center;padding:16px;';
    ov.innerHTML = `
    <div style="background:#fff;border-radius:16px;width:100%;max-width:540px;max-height:90vh;overflow-y:auto;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid #F1F5F9;">
            <span style="font-size:14px;font-weight:700;color:#1F2937;"><i class="fas fa-check-circle mr-2 text-green-500"></i>Confirm Package</span>
            <button onclick="document.getElementById('pk-conf-modal').remove()" style="background:none;border:none;color:#9CA3AF;cursor:pointer;font-size:15px;"><i class="fas fa-times"></i></button>
        </div>
        <div style="padding:18px;">
            <div style="font-size:13px;font-weight:600;color:#1F2937;margin-bottom:4px;">${_pke(q.title)}</div>
            <div style="font-size:11px;color:#6B7280;margin-bottom:14px;">${q.duration_days||0} Days · ${q.pax||1} Pax · ${pr.currency||'BDT'} ${_pkFmt(pr.gross_total)}</div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px;">
                <div>
                    <label style="font-size:10px;font-weight:600;color:#374151;display:block;margin-bottom:3px;">Client Paid Amount</label>
                    <input id="pk-cf-client-paid" type="number" min="0" placeholder="0"
                        style="width:100%;padding:7px 10px;font-size:12px;border:1.5px solid #E5E7EB;border-radius:7px;outline:none;">
                </div>
                <div>
                    <label style="font-size:10px;font-weight:600;color:#374151;display:block;margin-bottom:3px;">Vendor Cost</label>
                    <input id="pk-cf-vendor-cost" type="number" min="0" placeholder="0"
                        style="width:100%;padding:7px 10px;font-size:12px;border:1.5px solid #E5E7EB;border-radius:7px;outline:none;">
                </div>
                <div>
                    <label style="font-size:10px;font-weight:600;color:#374151;display:block;margin-bottom:3px;">Vendor Ref / PO</label>
                    <input id="pk-cf-vendor-ref" type="text" placeholder="Optional"
                        style="width:100%;padding:7px 10px;font-size:12px;border:1.5px solid #E5E7EB;border-radius:7px;outline:none;">
                </div>
                <div>
                    <label style="font-size:10px;font-weight:600;color:#374151;display:block;margin-bottom:3px;">Note</label>
                    <input id="pk-cf-note" type="text" placeholder="Optional"
                        style="width:100%;padding:7px 10px;font-size:12px;border:1.5px solid #E5E7EB;border-radius:7px;outline:none;">
                </div>
            </div>

            <div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;padding:10px 12px;font-size:11px;color:#92400E;margin-bottom:14px;">
                <i class="fas fa-info-circle mr-1"></i>
                Confirming will create a <b>Task</b> for financial processing. Make sure pricing is correct.
            </div>

            <button id="pk-cf-submit" style="width:100%;padding:10px;font-size:13px;font-weight:600;background:#16a34a;color:#fff;border:none;border-radius:10px;cursor:pointer;">
                <i class="fas fa-check mr-1.5"></i>Confirm &amp; Create Task
            </button>
        </div>
    </div>`;
    document.body.appendChild(ov);

    document.getElementById('pk-cf-submit').addEventListener('click', async () => {
        const btn = document.getElementById('pk-cf-submit');
        btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5"></i>Confirming…';
        try {
            const json = await window._pkApi({
                action:       'confirm_and_create_task',
                q_sys_id:     qSysId,
                client_paid:  parseFloat(document.getElementById('pk-cf-client-paid')?.value) || 0,
                vendor_cost:  parseFloat(document.getElementById('pk-cf-vendor-cost')?.value) || 0,
                vendor_ref:   document.getElementById('pk-cf-vendor-ref')?.value.trim() || '',
                note:         document.getElementById('pk-cf-note')?.value.trim() || '',
            });
            if (json.status === 'success') {
                pkT('success', `Confirmed! Task: ${json.task_sys_id}`);
                ov.remove();
                await window._pkReload();
                window._pkSwitchTab('confirmation', null);
            } else { pkT('error', json.message || 'Confirm ব্যর্থ'); btn.disabled = false; btn.innerHTML = '<i class="fas fa-check mr-1.5"></i>Confirm & Create Task'; }
        } catch(e) { pkT('error', 'Network error'); btn.disabled = false; }
    });
}