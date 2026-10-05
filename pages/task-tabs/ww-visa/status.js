/**
 * FILE PATH: /pages/task-tabs/ww-visa/status.js
 * Visa Service Module — "Application Status" tab
 *
 * Shows current status, submitted_at, allows:
 *  - "Submit Application" → status: submitted + trigger 2 financial entries
 *  - "Reopen" → back to in_progress
 */

window._renderVsStatus = function() {
    const el = document.getElementById('vs-tab-status');
    if (!el) return;

    const data = window._vs.data;
    const meta = data?.meta_data;

    if (!meta?.master_visa_sys_id) {
        el.innerHTML = `<div style="padding:32px;text-align:center;color:#64748B;font-size:.875rem;">
            ⚠️ Please select a visa service first (Visa Info tab).
        </div>`;
        return;
    }

    const travelers   = data.vs_travelers ?? [];
    const travCount   = travelers.length;
    const b2cTotal    = Number(meta.b2c_price)     * travCount;
    const vendorTotal = Number(meta.purchase_price) * travCount;
    const cur         = meta.currency ?? 'BDT';

    const isSubmitted = data.status === 'submitted';
    const isCompleted = data.status === 'completed';
    const locked      = isSubmitted || isCompleted;

    // Uploaded docs count
    let docsTotal    = 0;
    let docsUploaded = 0;
    travelers.forEach(t => {
        const rd = window._vsRequiredDocs(t.profession_type ?? 'general');
        docsTotal    += rd.length;
        docsUploaded += (t.docs ?? []).filter(d => d.status === 'uploaded').length;
    });

    el.innerHTML = `
    <div style="padding:16px 0;max-width:680px;">

        <!-- Status header -->
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
            ${_vsStatusChip(data.status)}
            ${data.submitted_at
                ? `<span style="font-size:.8rem;color:#64748B;">Submitted: ${new Date(data.submitted_at).toLocaleString('en-BD')}</span>`
                : ''}
        </div>

        <!-- Summary cards -->
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:24px;">
            <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:14px;">
                <div style="font-size:.7rem;font-weight:700;color:#94A3B8;text-transform:uppercase;margin-bottom:4px;">Travelers</div>
                <div style="font-size:1.3rem;font-weight:800;color:#0F172A;">${travCount}</div>
            </div>
            <div style="background:#EEF2FF;border:1px solid #C7D2FE;border-radius:10px;padding:14px;">
                <div style="font-size:.7rem;font-weight:700;color:#6366f1;text-transform:uppercase;margin-bottom:4px;">Client Total (B2C)</div>
                <div style="font-size:1.1rem;font-weight:800;color:#4F46E5;">${b2cTotal.toLocaleString()} ${cur}</div>
                <div style="font-size:.72rem;color:#94A3B8;">${_vsFmt(meta.b2c_price, cur)} × ${travCount}</div>
            </div>
            <div style="background:#FEF3C7;border:1px solid #FDE68A;border-radius:10px;padding:14px;">
                <div style="font-size:.7rem;font-weight:700;color:#D97706;text-transform:uppercase;margin-bottom:4px;">Vendor Cost</div>
                <div style="font-size:1.1rem;font-weight:800;color:#92400E;">${vendorTotal.toLocaleString()} ${cur}</div>
                <div style="font-size:.72rem;color:#94A3B8;">${_vsFmt(meta.purchase_price, cur)} × ${travCount}</div>
            </div>
        </div>

        <!-- Doc readiness -->
        <div style="background:#fff;border:1px solid #E2E8F0;border-radius:10px;padding:16px;margin-bottom:20px;">
            <div style="font-size:.85rem;font-weight:700;color:#0F172A;margin-bottom:10px;">Document Readiness</div>
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="flex:1;height:8px;background:#E2E8F0;border-radius:999px;overflow:hidden;">
                    <div style="height:100%;width:${docsTotal>0?Math.round(docsUploaded/docsTotal*100):0}%;
                                background:${docsUploaded===docsTotal&&docsTotal>0?'#22C55E':'#6366f1'};
                                border-radius:999px;transition:width .3s;"></div>
                </div>
                <div style="font-size:.85rem;font-weight:700;color:${docsUploaded===docsTotal&&docsTotal>0?'#15803D':'#64748B'};">
                    ${docsUploaded}/${docsTotal}
                </div>
            </div>
            ${docsTotal === 0 ? `<div style="font-size:.78rem;color:#94A3B8;margin-top:6px;">Sync docs in the Travelers tab first.</div>` : ''}
        </div>

        <!-- Action area -->
        ${locked
            ? `<div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                   <div style="font-size:.85rem;font-weight:600;color:#15803D;">✓ Application submitted. Financial entries have been recorded.</div>
                   ${data.status !== 'completed'
                       ? `<button onclick="_vsReopen()"
                                  style="padding:7px 14px;border-radius:7px;border:1.5px solid #E2E8F0;
                                         background:#fff;font-size:.78rem;font-weight:600;cursor:pointer;color:#64748B;">
                              ↩ Reopen
                          </button>`
                       : ''}
               </div>`
            : `<div style="border-top:1px solid #E2E8F0;padding-top:20px;">
                   <div style="font-size:.85rem;color:#64748B;margin-bottom:14px;">
                       Submitting will record the following financial entries:
                   </div>
                   <div style="display:flex;flex-direction:column;gap:6px;margin-bottom:18px;">
                       <div style="display:flex;align-items:center;gap:10px;font-size:.85rem;">
                           <span style="background:#DCFCE7;color:#15803D;border-radius:6px;padding:2px 8px;font-size:.73rem;font-weight:700;">CLIENT</span>
                           <span style="color:#0F172A;">Sale — ${meta.master_visa_title}</span>
                           <span style="margin-left:auto;font-weight:700;color:#0F172A;">${b2cTotal.toLocaleString()} ${cur}</span>
                       </div>
                       <div style="display:flex;align-items:center;gap:10px;font-size:.85rem;">
                           <span style="background:#FEF3C7;color:#92400E;border-radius:6px;padding:2px 8px;font-size:.73rem;font-weight:700;">VENDOR</span>
                           <span style="color:#0F172A;">Purchase — ${meta.master_visa_title}</span>
                           <span style="margin-left:auto;font-weight:700;color:#0F172A;">${vendorTotal.toLocaleString()} ${cur}</span>
                       </div>
                   </div>

                   <!-- Vendor account selector (for real-time payment) -->
                   <div style="background:#FEF3C7;border:1px solid #FDE68A;border-radius:8px;padding:12px;margin-bottom:16px;">
                       <div style="font-size:.78rem;font-weight:700;color:#92400E;margin-bottom:8px;">
                           ⚡ Vendor Payment — Real-time from own account
                       </div>
                       <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                           <div>
                               <label style="font-size:.73rem;font-weight:600;color:#64748B;display:block;margin-bottom:4px;">Vendor *</label>
                               <select id="vs-submit-vendor" class="f-input f-select vs-sub-sel"
                                       style="width:100%;padding:7px 10px;font-size:.82rem;border:1.5px solid #E2E8F0;border-radius:7px;outline:none;background:#fff;">
                                   <option value="">Select vendor</option>
                               </select>
                           </div>
                           <div>
                               <label style="font-size:.73rem;font-weight:600;color:#64748B;display:block;margin-bottom:4px;">Pay from Account *</label>
                               <select id="vs-submit-account" class="f-input f-select vs-sub-sel"
                                       style="width:100%;padding:7px 10px;font-size:.82rem;border:1.5px solid #E2E8F0;border-radius:7px;outline:none;background:#fff;">
                                   <option value="">Select account</option>
                               </select>
                           </div>
                       </div>
                   </div>

                   <button onclick="_vsSubmitApplication()"
                           style="padding:10px 24px;background:#4F46E5;color:#fff;font-weight:700;font-size:.9rem;
                                  border:none;border-radius:9px;cursor:pointer;transition:background .15s;"
                           onmouseover="this.style.background='#4338CA'" onmouseout="this.style.background='#4F46E5'">
                       ✈ Submit Application
                   </button>
               </div>`
        }
    </div>`;

    // Populate vendor & account dropdowns
    if (!locked) {
        _vsPopulateSubmitDropdowns();
    }
};

async function _vsPopulateSubmitDropdowns() {
    const vSel = document.getElementById('vs-submit-vendor');
    const aSel = document.getElementById('vs-submit-account');
    if (!vSel || !aSel) return;

    // Vendors
    try {
        const res = await fetch(window._vs.cfg.api.allVendors);
        const j   = await res.json();
        const vendors = j.data ?? j ?? [];
        vSel.innerHTML = '<option value="">Select vendor</option>' +
            vendors.map(v => `<option value="${v.sys_id}">${v.name ?? v.vendor_name}</option>`).join('');
    } catch(e) { /* silent */ }

    // Accounts
    try {
        const res = await fetch(window._vs.cfg.api.allAccounts);
        const j   = await res.json();
        const accs = j.data ?? j ?? [];
        aSel.innerHTML = '<option value="">Select account</option>' +
            accs.map(a => `<option value="${a.sys_id}">${a.name ?? a.account_name} (${a.balance ? Number(a.balance).toLocaleString() : '—'})</option>`).join('');
    } catch(e) { /* silent */ }
}

// ── Submit application ────────────────────────────────────
window._vsSubmitApplication = async function() {
    const vendorSysId  = document.getElementById('vs-submit-vendor')?.value;
    const accountSysId = document.getElementById('vs-submit-account')?.value;

    if (!vendorSysId)  return alert('Please select a vendor');
    if (!accountSysId) return alert('Please select a payment account');

    if (!confirm('Submit application and record financial entries?')) return;

    const data     = window._vs.data;
    const meta     = data?.meta_data;
    const travCount= (data?.vs_travelers ?? []).length;
    const b2cTotal = Number(meta?.b2c_price ?? 0) * travCount;
    const vendTotal= Number(meta?.purchase_price ?? 0) * travCount;
    const cur      = meta?.currency ?? 'BDT';
    const purpose  = `Visa — ${meta?.master_visa_title ?? ''}`;
    const cfg      = window._vs.cfg;

    // 1. Mark submitted
    const j = await window._vsApi({ action: 'submit_application' });
    if (j.status !== 'success') { alert(j.message); return; }
    window._vs.data = j.data;

    // 2. Client entry (sale / receivable) — no account_id
    if (b2cTotal > 0 && cfg.clientSysId) {
        try {
            await fetch(cfg.api.saveFinancial, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    type:        'credit',
                    amount:      b2cTotal,
                    purpose,
                    client_id:   cfg.clientSysId,
                    work_id:     cfg.workSysId,
                    date:        new Date().toISOString().split('T')[0],
                    qty_rate:    JSON.stringify({ qty: travCount, rate: meta.b2c_price }),
                    ref:         data.sys_id,
                }),
            });
        } catch(e) { console.error('[Visa] Client fin entry failed:', e); }
    }

    // 3. Vendor entry (purchase — real-time payment from own account)
    if (vendTotal > 0 && vendorSysId) {
        try {
            await fetch(cfg.api.saveFinancial, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    type:             'credit',
                    amount:           vendTotal,
                    purpose,
                    vendor_id:        vendorSysId,
                    account_id:       accountSysId,
                    transaction_mode: 'realtime',
                    work_id:          cfg.workSysId,
                    date:             new Date().toISOString().split('T')[0],
                    qty_rate:         JSON.stringify({ qty: travCount, rate: meta.purchase_price }),
                    ref:              data.sys_id,
                }),
            });
        } catch(e) { console.error('[Visa] Vendor fin entry failed:', e); }
    }

    // Re-render
    await window._vsReload();
    window._renderVsStatus();

    // Update badge
    const badge = document.getElementById('vs-submitted-badge');
    if (badge) badge.style.display = 'block';
};

// ── Reopen ────────────────────────────────────────────────
window._vsReopen = async function() {
    if (!confirm('Reopen application? Financial entries already recorded will remain.')) return;
    const j = await window._vsApi({ action: 'reopen' });
    if (j.status !== 'success') { alert(j.message); return; }
    await window._vsReload();
    window._renderVsStatus();
    const badge = document.getElementById('vs-submitted-badge');
    if (badge) badge.style.display = 'none';
};