/**
 * FILE PATH: /pages/task-tabs/ww-package/confirmation.js
 * Package Confirmation Tab — view / edit confirmed package
 */

// ── Main render ───────────────────────────────────────────────
window._renderPkConfirmation = function() {
    const panel = document.getElementById('pk-tab-confirmation');
    if (!panel) return;

    const conf = window._pk.data?.pk_confirmation ?? null;

    if (!conf) {
        panel.innerHTML = `
        <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;
                    min-height:320px;gap:16px;color:#94a3b8;">
            <svg width="56" height="56" fill="none" stroke="currentColor" stroke-width="1.4"
                 viewBox="0 0 24 24">
                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                      stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <div style="font-size:15px;font-weight:500;">No confirmation yet</div>
            <div style="font-size:13px;color:#64748b;text-align:center;max-width:340px;">
                Go to the <b>Quotation</b> tab, select a quotation and click <b>Confirm Package</b>.
            </div>
        </div>`;
        return;
    }

    const p  = conf.pricing  ?? {};
    const sv = conf.services ?? {};
    const bd = conf.booking_data ?? {};

    const cur = _pke(p.currency ?? 'BDT');

    panel.innerHTML = `
    <div style="padding:20px 0 32px;">

        <!-- ── Header ── -->
        <div style="display:flex;align-items:flex-start;justify-content:space-between;
                    gap:12px;margin-bottom:20px;flex-wrap:wrap;">
            <div>
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <span style="background:#10b981;color:#fff;font-size:11px;font-weight:700;
                                 padding:3px 10px;border-radius:20px;letter-spacing:.5px;">CONFIRMED</span>
                    <span style="font-size:11px;color:#64748b;">${_pke(conf.sys_id ?? '')}</span>
                    <span style="font-size:11px;color:#64748b;">${_pke(conf.confirmed_at ?? '')}</span>
                </div>
                <div style="font-size:19px;font-weight:700;color:#0f172a;margin-top:6px;">
                    ${_pke(conf.title ?? 'Package Confirmation')}
                </div>
                <div style="font-size:13px;color:#64748b;margin-top:3px;">
                    Confirmed by: <b>${_pke(conf.confirmed_by ?? '')}</b>
                </div>
            </div>
            <button data-action="pk-conf-edit"
                    style="background:#6366f1;color:#fff;border:none;border-radius:8px;
                           padding:8px 16px;font-size:13px;font-weight:600;cursor:pointer;
                           display:flex;align-items:center;gap:6px;">
                ✏️ Edit Confirmation
            </button>
        </div>

        <!-- ── Destinations + Duration ── -->
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:20px;">
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px;">
                <div style="font-size:11px;color:#64748b;font-weight:600;text-transform:uppercase;
                             letter-spacing:.5px;margin-bottom:6px;">Destinations</div>
                <div style="font-size:14px;font-weight:600;color:#0f172a;">
                    ${_pkConfDestHtml(conf.destinations ?? [])}
                </div>
            </div>
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px;">
                <div style="font-size:11px;color:#64748b;font-weight:600;text-transform:uppercase;
                             letter-spacing:.5px;margin-bottom:6px;">Duration / PAX</div>
                <div style="font-size:14px;font-weight:600;color:#0f172a;">
                    ${_pke(conf.duration_days ?? 0)} Days &nbsp;·&nbsp; ${_pke(conf.pax ?? 1)} PAX
                </div>
            </div>
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px;">
                <div style="font-size:11px;color:#64748b;font-weight:600;text-transform:uppercase;
                             letter-spacing:.5px;margin-bottom:6px;">Quotation Ref</div>
                <div style="font-size:13px;font-weight:600;color:#0f172a;">
                    ${_pke(conf.quotation_sys_id ?? '—')}
                </div>
            </div>
        </div>

        <!-- ── Itinerary ── -->
        ${conf.itinerary ? `
        <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:10px;
                    padding:16px;margin-bottom:20px;">
            <div style="font-size:12px;color:#0284c7;font-weight:700;text-transform:uppercase;
                         letter-spacing:.5px;margin-bottom:10px;">📋 Itinerary</div>
            <div style="font-size:13px;color:#0f172a;line-height:1.8;white-space:pre-wrap;
                        max-height:260px;overflow-y:auto;">${_pke(conf.itinerary)}</div>
        </div>` : ''}

        <!-- ── Services ── -->
        <div style="margin-bottom:20px;">
            <div style="font-size:13px;font-weight:700;color:#374151;margin-bottom:10px;">
                Services Included
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                ${_pkConfSvcBlock('✈️','Air Ticket', sv.air_ticket)}
                ${_pkConfSvcBlock('🏨','Hotel',      sv.hotel)}
                ${_pkConfSvcBlock('🚌','Transport',  sv.transport)}
                ${_pkConfSvcBlock('📄','Visa',       sv.visa)}
            </div>
            ${(sv.others ?? []).length ? `
            <div style="margin-top:10px;">
                <div style="font-size:12px;color:#64748b;font-weight:600;margin-bottom:6px;">Others</div>
                <div style="display:flex;flex-wrap:wrap;gap:8px;">
                    ${(sv.others).map(o => `
                    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;
                                padding:8px 12px;font-size:12px;">
                        <span style="color:#374151;font-weight:600;">${_pke(o.label ?? '')}</span>
                        <span style="color:#64748b;margin-left:6px;">${cur} ${_pkFmt(o.cost ?? 0)}</span>
                    </div>`).join('')}
                </div>
            </div>` : ''}
        </div>

        <!-- ── Pricing ── -->
        <div style="background:linear-gradient(135deg,#1e293b,#334155);border-radius:12px;
                    padding:20px;margin-bottom:20px;color:#fff;">
            <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;
                         margin-bottom:14px;opacity:.7;">Pricing Summary</div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:14px;">
                ${_pkConfPriceChip('Base Cost',    p.base_cost,    cur, '#94a3b8')}
                ${_pkConfPriceChip('Markup ('+_pke(p.markup_pct??10)+'%)', p.markup_amount, cur, '#fbbf24')}
                ${_pkConfPriceChip('Gross Total',  p.gross_total,  cur, '#34d399')}
            </div>
            <div style="background:rgba(255,255,255,.1);border-radius:8px;padding:12px;
                        display:flex;align-items:center;justify-content:space-between;">
                <div style="font-size:13px;opacity:.8;">Per Person (${_pke(conf.pax??1)} PAX)</div>
                <div style="font-size:22px;font-weight:800;color:#34d399;">
                    ${cur} ${_pkFmt(p.per_pax ?? 0)}
                </div>
            </div>
        </div>

        <!-- ── Booking / Payment ── -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px;">
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px;">
                <div style="font-size:11px;color:#64748b;font-weight:600;text-transform:uppercase;
                             letter-spacing:.5px;margin-bottom:8px;">Client Paid</div>
                <div style="font-size:18px;font-weight:700;color:#0f172a;">
                    ${cur} ${_pkFmt(bd.client_paid ?? 0)}
                </div>
            </div>
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px;">
                <div style="font-size:11px;color:#64748b;font-weight:600;text-transform:uppercase;
                             letter-spacing:.5px;margin-bottom:8px;">Vendor Cost</div>
                <div style="font-size:18px;font-weight:700;color:#0f172a;">
                    ${cur} ${_pkFmt(bd.vendor_cost ?? 0)}
                </div>
            </div>
        </div>
        ${bd.vendor_ref ? `
        <div style="font-size:12px;color:#64748b;margin-bottom:10px;">
            <b>Vendor Ref:</b> ${_pke(bd.vendor_ref)}
        </div>` : ''}
        ${bd.note ? `
        <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;
                    padding:12px;font-size:13px;color:#92400e;margin-bottom:20px;">
            📝 ${_pke(bd.note)}
        </div>` : ''}

        <!-- ── Task link ── -->
        ${conf.task_sys_id ? `
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:14px;
                    display:flex;align-items:center;justify-content:space-between;">
            <div>
                <div style="font-size:12px;color:#15803d;font-weight:700;margin-bottom:2px;">
                    ✅ Task Created
                </div>
                <div style="font-size:13px;color:#166534;">Task ID: <b>${_pke(conf.task_sys_id)}</b></div>
            </div>
        </div>` : ''}

    </div>`;

    panel.addEventListener('click', _pkConfClick);
};

// ── Helpers ───────────────────────────────────────────────────
function _pkConfDestHtml(dests) {
    if (!dests.length) return '—';
    return dests.map(d => `${_pke(d.city ?? '')} <span style="color:#64748b;font-size:12px;">${_pke(d.country ?? '')}</span>`).join(', ');
}

function _pkConfSvcBlock(icon, label, svc) {
    if (!svc || (!svc.description && !svc.cost)) return '';
    const conf = window._pk.data?.pk_confirmation ?? {};
    const cur  = _pke((conf.pricing ?? {}).currency ?? 'BDT');
    return `
    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px;">
        <div style="font-size:11px;color:#64748b;font-weight:600;text-transform:uppercase;
                     letter-spacing:.5px;margin-bottom:6px;">${icon} ${label}</div>
        <div style="font-size:12px;color:#374151;margin-bottom:4px;">${_pke(svc.description ?? '')}</div>
        <div style="font-size:13px;font-weight:700;color:#0f172a;">${cur} ${_pkFmt(svc.cost ?? 0)}</div>
    </div>`;
}

function _pkConfPriceChip(label, val, cur, col) {
    return `
    <div>
        <div style="font-size:11px;opacity:.6;margin-bottom:4px;">${label}</div>
        <div style="font-size:15px;font-weight:700;color:${col};">
            ${_pke(cur)} ${_pkFmt(val ?? 0)}
        </div>
    </div>`;
}

// ── Click delegation ──────────────────────────────────────────
function _pkConfClick(e) {
    const btn = e.target.closest('[data-action]');
    if (!btn) return;
    const action = btn.dataset.action;
    if (action === 'pk-conf-edit') _pkConfOpenEdit();
}

// ── Edit modal ────────────────────────────────────────────────
function _pkConfOpenEdit() {
    const conf = window._pk.data?.pk_confirmation ?? {};
    const bd   = conf.booking_data ?? {};
    const p    = conf.pricing ?? {};
    const cur  = _pke(p.currency ?? 'BDT');

    const over = document.createElement('div');
    over.id = 'pk-conf-edit-modal';
    over.style.cssText = `position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:99990;
                           display:flex;align-items:center;justify-content:center;padding:16px;`;
    over.innerHTML = `
    <div style="background:#fff;border-radius:16px;width:100%;max-width:640px;
                max-height:90vh;overflow-y:auto;padding:24px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
            <div style="font-size:17px;font-weight:700;color:#0f172a;">Edit Confirmation</div>
            <button data-close-conf-modal
                    style="background:none;border:none;font-size:20px;cursor:pointer;color:#64748b;">✕</button>
        </div>

        <!-- Title -->
        <div style="margin-bottom:14px;">
            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">
                Package Title
            </label>
            <input id="pk-ce-title" type="text" value="${_pke(conf.title ?? '')}"
                   style="width:100%;box-sizing:border-box;padding:9px 12px;border:1.5px solid #e2e8f0;
                          border-radius:8px;font-size:13px;outline:none;">
        </div>

        <!-- Duration / PAX -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
            <div>
                <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">
                    Duration (days)
                </label>
                <input id="pk-ce-days" type="number" min="1" value="${_pke(conf.duration_days ?? 0)}"
                       style="width:100%;box-sizing:border-box;padding:9px 12px;border:1.5px solid #e2e8f0;
                              border-radius:8px;font-size:13px;outline:none;">
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">
                    PAX
                </label>
                <input id="pk-ce-pax" type="number" min="1" value="${_pke(conf.pax ?? 1)}"
                       style="width:100%;box-sizing:border-box;padding:9px 12px;border:1.5px solid #e2e8f0;
                              border-radius:8px;font-size:13px;outline:none;">
            </div>
        </div>

        <!-- Itinerary -->
        <div style="margin-bottom:14px;">
            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">
                Itinerary
            </label>
            <textarea id="pk-ce-itinerary" rows="6"
                      style="width:100%;box-sizing:border-box;padding:9px 12px;border:1.5px solid #e2e8f0;
                             border-radius:8px;font-size:13px;outline:none;resize:vertical;">${_pke(conf.itinerary ?? '')}</textarea>
        </div>

        <!-- Payment -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
            <div>
                <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">
                    Client Paid (${cur})
                </label>
                <input id="pk-ce-client-paid" type="number" min="0" value="${_pke(bd.client_paid ?? 0)}"
                       style="width:100%;box-sizing:border-box;padding:9px 12px;border:1.5px solid #e2e8f0;
                              border-radius:8px;font-size:13px;outline:none;">
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">
                    Vendor Cost (${cur})
                </label>
                <input id="pk-ce-vendor-cost" type="number" min="0" value="${_pke(bd.vendor_cost ?? 0)}"
                       style="width:100%;box-sizing:border-box;padding:9px 12px;border:1.5px solid #e2e8f0;
                              border-radius:8px;font-size:13px;outline:none;">
            </div>
        </div>

        <!-- Vendor Ref / Note -->
        <div style="margin-bottom:14px;">
            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">
                Vendor Reference
            </label>
            <input id="pk-ce-vendor-ref" type="text" value="${_pke(bd.vendor_ref ?? '')}"
                   style="width:100%;box-sizing:border-box;padding:9px 12px;border:1.5px solid #e2e8f0;
                          border-radius:8px;font-size:13px;outline:none;">
        </div>
        <div style="margin-bottom:20px;">
            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">
                Note
            </label>
            <textarea id="pk-ce-note" rows="3"
                      style="width:100%;box-sizing:border-box;padding:9px 12px;border:1.5px solid #e2e8f0;
                             border-radius:8px;font-size:13px;outline:none;resize:vertical;">${_pke(bd.note ?? '')}</textarea>
        </div>

        <!-- Actions -->
        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button data-close-conf-modal
                    style="padding:9px 20px;border:1.5px solid #e2e8f0;border-radius:8px;
                           font-size:13px;font-weight:600;cursor:pointer;background:#fff;color:#374151;">
                Cancel
            </button>
            <button id="pk-ce-save-btn"
                    style="padding:9px 24px;background:#10b981;color:#fff;border:none;
                           border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">
                💾 Save Changes
            </button>
        </div>
    </div>`;

    over.querySelectorAll('[data-close-conf-modal]').forEach(b =>
        b.addEventListener('click', () => over.remove()));
    over.addEventListener('click', e => { if (e.target === over) over.remove(); });
    document.getElementById('pk-ce-save-btn')?.addEventListener('click', () => _pkConfSave(over));
    document.body.appendChild(over);
}

// ── Save updated confirmation ─────────────────────────────────
async function _pkConfSave(modal) {
    const conf  = window._pk.data?.pk_confirmation ?? {};
    const bd    = conf.booking_data ?? {};

    const updated = {
        ...conf,
        title:         document.getElementById('pk-ce-title')?.value.trim()     ?? conf.title,
        duration_days: +(document.getElementById('pk-ce-days')?.value  ?? conf.duration_days),
        pax:           +(document.getElementById('pk-ce-pax')?.value   ?? conf.pax),
        itinerary:     document.getElementById('pk-ce-itinerary')?.value ?? conf.itinerary,
        booking_data:  {
            ...bd,
            client_paid: +(document.getElementById('pk-ce-client-paid')?.value ?? bd.client_paid),
            vendor_cost: +(document.getElementById('pk-ce-vendor-cost')?.value ?? bd.vendor_cost),
            vendor_ref:  document.getElementById('pk-ce-vendor-ref')?.value.trim() ?? bd.vendor_ref,
            note:        document.getElementById('pk-ce-note')?.value.trim()       ?? bd.note,
        },
    };

    const btn = document.getElementById('pk-ce-save-btn');
    if (btn) { btn.disabled = true; btn.textContent = 'Saving…'; }

    try {
        const res = await window._pkApi({
            action:       'update_confirmation',
            confirmation: updated,
        });
        if (res.status !== 'success') throw new Error(res.message ?? 'Save failed');
        await window._pkReload();
        modal.remove();
        pkT('success', 'Confirmation updated');
        window._renderPkConfirmation();
    } catch(e) {
        pkT('error', e.message);
        if (btn) { btn.disabled = false; btn.textContent = '💾 Save Changes'; }
    }
}