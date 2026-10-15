/**
 * FILE PATH: /pages/task-tabs/ww-visa/status.js
 * Visa Service Module — "Application Status" tab (Layer 1)
 *
 * Shows overall status across ALL travelers:
 *  - Per-traveler status row
 *  - Financial summary (client + vendor totals)
 *  - Submit Application action
 */

window._renderVsStatus = function () {
    const el = document.getElementById('vs-tab-status');
    if (!el) return;

    const data      = window._vs.data;
    const meta      = data?.meta_data;
    const travelers = data?.vs_travelers ?? [];

    if (!meta?.master_visa_sys_id) {
        el.innerHTML = `<div style="padding:32px;text-align:center;color:#64748B;font-size:.875rem;">
            ⚠️ Please select a visa service first (Visa Info tab).
        </div>`;
        return;
    }

    const travCount   = travelers.length;
    const b2cTotal    = Number(meta.b2c_price ?? 0)      * travCount;
    const vendorTotal = Number(meta.purchase_price ?? 0) * travCount;
    const cur         = meta.currency ?? 'BDT';

    const isSubmitted = data.status === 'submitted';
    const isCompleted = data.status === 'completed';
    const locked      = isSubmitted || isCompleted;

    // Overall doc readiness
    let docsTotal = 0, docsUploaded = 0;
    travelers.forEach(t => {
        const rd = window._vsRequiredDocs(t.profession_type ?? 'general');
        docsTotal    += rd.length;
        docsUploaded += (t.docs ?? []).filter(d => d.status === 'uploaded').length;
    });
    const readyCount = travelers.filter(t => window._vsTravelerReady(t)).length;

    el.innerHTML = `
    <div style="padding:16px 0;max-width:720px;">

        <!-- Overall status header -->
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:22px;">
            ${_vsStatusChip(data.status)}
            ${data.submitted_at
                ? `<span style="font-size:.8rem;color:#64748B;">
                       Submitted: ${new Date(data.submitted_at).toLocaleString('en-BD')}
                   </span>`
                : ''}
            <div style="margin-left:auto;font-size:.82rem;color:#64748B;font-weight:600;">
                ${readyCount}/${travCount} travelers ready
            </div>
        </div>

        <!-- Per-traveler status table -->
        ${travCount > 0 ? `
        <div style="margin-bottom:22px;">
            <div style="font-size:.78rem;font-weight:700;color:#475569;text-transform:uppercase;
                        letter-spacing:.04em;margin-bottom:10px;">Traveler Status</div>
            <div style="border:1px solid #E2E8F0;border-radius:10px;overflow:hidden;">
                <div style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr;
                            padding:8px 14px;background:#F8FAFC;
                            font-size:.72rem;font-weight:700;color:#94A3B8;text-transform:uppercase;">
                    <span>Traveler</span><span>Docs</span><span>Cover Letter</span><span>Status</span>
                </div>
                ${travelers.map(t => {
                    const docs     = t.docs ?? [];
                    const total    = docs.length;
                    const uploaded = docs.filter(d => d.status === 'uploaded').length;
                    const hasCL    = !!t.cover_letter;
                    const pct      = total > 0 ? Math.round(uploaded/total*100) : 0;

                    return `<div style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr;
                                        padding:10px 14px;border-top:1px solid #F1F5F9;
                                        align-items:center;">
                        <div>
                            <div style="font-size:.85rem;font-weight:600;color:#0F172A;">${_vsEsc(t.name)}</div>
                            <div style="font-size:.72rem;color:#94A3B8;">${_vsEsc(t.passport_no ?? '—')}</div>
                        </div>
                        <div>
                            <div style="font-size:.75rem;font-weight:700;color:${pct===100?'#15803D':'#64748B'};">
                                ${uploaded}/${total}
                            </div>
                            <div style="height:4px;width:60px;background:#E2E8F0;border-radius:999px;overflow:hidden;margin-top:3px;">
                                <div style="height:100%;width:${pct}%;background:${pct===100?'#22C55E':'#6366f1'};border-radius:999px;"></div>
                            </div>
                        </div>
                        <div style="font-size:.82rem;">
                            ${hasCL ? '<span style="color:#15803D;">✓</span>' : '<span style="color:#CBD5E1;">—</span>'}
                        </div>
                        <div>
                            ${window._vsTravStatusChip(t.app_status ?? 'pending')}
                        </div>
                    </div>`;
                }).join('')}
            </div>
        </div>` : `
        <div style="padding:20px;text-align:center;color:#94A3B8;font-size:.85rem;margin-bottom:20px;">
            No travelers added yet.
        </div>`}

        <!-- Financial summary -->
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:22px;">
            <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:14px;">
                <div style="font-size:.7rem;font-weight:700;color:#94A3B8;text-transform:uppercase;margin-bottom:4px;">Travelers</div>
                <div style="font-size:1.4rem;font-weight:800;color:#0F172A;">${travCount}</div>
            </div>
            <div style="background:#EEF2FF;border:1px solid #C7D2FE;border-radius:10px;padding:14px;">
                <div style="font-size:.7rem;font-weight:700;color:#6366f1;text-transform:uppercase;margin-bottom:4px;">Client Total</div>
                <div style="font-size:1rem;font-weight:800;color:#4F46E5;">${b2cTotal.toLocaleString()} ${cur}</div>
                <div style="font-size:.72rem;color:#94A3B8;">${Number(meta.b2c_price).toLocaleString()} × ${travCount}</div>
            </div>
            <div style="background:#FEF3C7;border:1px solid #FDE68A;border-radius:10px;padding:14px;">
                <div style="font-size:.7rem;font-weight:700;color:#D97706;text-transform:uppercase;margin-bottom:4px;">Vendor Cost</div>
                <div style="font-size:1rem;font-weight:800;color:#92400E;">${vendorTotal.toLocaleString()} ${cur}</div>
                <div style="font-size:.72rem;color:#94A3B8;">${Number(meta.purchase_price).toLocaleString()} × ${travCount}</div>
            </div>
        </div>

        <!-- Doc readiness bar -->
        <div style="background:#fff;border:1px solid #E2E8F0;border-radius:10px;padding:14px;margin-bottom:20px;">
            <div style="font-size:.82rem;font-weight:700;color:#0F172A;margin-bottom:10px;">Overall Document Readiness</div>
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
            ${docsTotal === 0
                ? `<div style="font-size:.75rem;color:#94A3B8;margin-top:6px;">Sync documents in Traveler Docs tab first.</div>`
                : ''}
        </div>

        <!-- Action area -->
        ${locked
            ? `<div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                   <div style="font-size:.85rem;font-weight:600;color:#15803D;">
                       ✓ Application submitted. Financial entries recorded.
                   </div>
                   <button onclick="window._vsDownloadDocPackage()"
                           style="padding:7px 14px;border-radius:7px;background:#0F172A;color:#fff;
                                  border:none;font-size:.78rem;font-weight:600;cursor:pointer;">
                       📦 Download Full Package
                   </button>
                   ${data.status !== 'completed'
                       ? `<button onclick="window._vsReopen()"
                                  style="padding:7px 14px;border-radius:7px;border:1.5px solid #E2E8F0;
                                         background:#fff;font-size:.78rem;font-weight:600;cursor:pointer;color:#64748B;">
                              ↩ Reopen
                          </button>`
                       : ''}
               </div>`
            : `<div style="border-top:1px solid #E2E8F0;padding-top:20px;">
                   <div style="font-size:.82rem;color:#64748B;margin-bottom:14px;">
                       Submitting will record financial entries for ${travCount} traveler${travCount!==1?'s':''}.
                   </div>

                   <!-- Vendor + account selectors -->
                   <div style="background:#FEF3C7;border:1px solid #FDE68A;border-radius:10px;
                               padding:14px;margin-bottom:18px;">
                       <div style="font-size:.78rem;font-weight:700;color:#92400E;margin-bottom:10px;">
                           ⚡ Vendor Payment Details
                       </div>
                       <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                           <div>
                               <label style="font-size:.73rem;font-weight:600;color:#64748B;display:block;margin-bottom:4px;">Vendor *</label>
                               <select id="vs-submit-vendor"
                                       style="width:100%;padding:7px 10px;font-size:.82rem;border:1.5px solid #E2E8F0;
                                              border-radius:7px;outline:none;background:#fff;">
                                   <option value="">Select vendor</option>
                               </select>
                           </div>
                           <div>
                               <label style="font-size:.73rem;font-weight:600;color:#64748B;display:block;margin-bottom:4px;">Pay from Account *</label>
                               <select id="vs-submit-account"
                                       style="width:100%;padding:7px 10px;font-size:.82rem;border:1.5px solid #E2E8F0;
                                              border-radius:7px;outline:none;background:#fff;">
                                   <option value="">Select account</option>
                               </select>
                           </div>
                       </div>
                   </div>

                   <button onclick="window._vsSubmitApplication()"
                           style="padding:10px 28px;background:#4F46E5;color:#fff;font-weight:700;
                                  font-size:.9rem;border:none;border-radius:9px;cursor:pointer;">
                       ✈ Submit Application
                   </button>
               </div>`
        }
    </div>`;

    if (!locked) _vsPopulateSubmitDropdowns();
};

/* ── Populate vendor/account dropdowns ──────────────────────── */
async function _vsPopulateSubmitDropdowns() {
    const vSel = document.getElementById('vs-submit-vendor');
    const aSel = document.getElementById('vs-submit-account');
    if (!vSel || !aSel) return;

    try {
        const j = await fetch(window._vs.cfg.api.allVendors).then(r => r.json());
        const vendors = j.data ?? j ?? [];
        vSel.innerHTML = '<option value="">Select vendor</option>' +
            vendors.map(v => `<option value="${v.sys_id}">${_vsEsc(v.name ?? v.vendor_name)}</option>`).join('');
    } catch (e) { /* silent */ }

    try {
        const j = await fetch(window._vs.cfg.api.allAccounts).then(r => r.json());
        const accs = j.data ?? j ?? [];
        aSel.innerHTML = '<option value="">Select account</option>' +
            accs.map(a => `<option value="${a.sys_id}">${_vsEsc(a.name ?? a.account_name)} (${a.balance ? Number(a.balance).toLocaleString() : '—'})</option>`).join('');
    } catch (e) { /* silent */ }
}

/* ── Submit ─────────────────────────────────────────────────── */
window._vsSubmitApplication = async function () {
    const vendorSysId  = document.getElementById('vs-submit-vendor')?.value;
    const accountSysId = document.getElementById('vs-submit-account')?.value;

    if (!vendorSysId)  return alert('Please select a vendor');
    if (!accountSysId) return alert('Please select a payment account');
    if (!confirm('Submit application and record financial entries?')) return;

    const data      = window._vs.data;
    const meta      = data?.meta_data ?? {};
    const travCount = (data?.vs_travelers ?? []).length;
    const b2cTotal  = Number(meta.b2c_price ?? 0) * travCount;
    const vendTotal = Number(meta.purchase_price ?? 0) * travCount;
    const cur       = meta.currency ?? 'BDT';
    const purpose   = `Visa — ${meta.master_visa_title ?? ''}`;
    const cfg       = window._vs.cfg;

    const j = await window._vsApi({ action: 'submit_application' });
    if (j.status !== 'success') { alert(j.message); return; }
    window._vs.data = j.data;

    // Client sale entry
    if (b2cTotal > 0 && cfg.clientSysId) {
        try {
            await fetch(cfg.api.saveFinancial, {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    type: 'credit', amount: b2cTotal, purpose,
                    client_id: cfg.clientSysId, work_id: cfg.workSysId,
                    date: new Date().toISOString().split('T')[0],
                    qty_rate: JSON.stringify({ qty: travCount, rate: meta.b2c_price }),
                    ref: data.sys_id,
                }),
            });
        } catch (e) { console.error('[Visa] Client entry failed:', e); }
    }

    // Vendor purchase entry
    if (vendTotal > 0 && vendorSysId) {
        try {
            await fetch(cfg.api.saveFinancial, {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    type: 'credit', amount: vendTotal, purpose,
                    vendor_id: vendorSysId, account_id: accountSysId,
                    transaction_mode: 'realtime', work_id: cfg.workSysId,
                    date: new Date().toISOString().split('T')[0],
                    qty_rate: JSON.stringify({ qty: travCount, rate: meta.purchase_price }),
                    ref: data.sys_id,
                }),
            });
        } catch (e) { console.error('[Visa] Vendor entry failed:', e); }
    }

    await window._vsReload();
    window._renderVsStatus();
    const badge = document.getElementById('vs-submitted-badge');
    if (badge) badge.style.display = 'flex';
};

/* ── Reopen ─────────────────────────────────────────────────── */
window._vsReopen = async function () {
    if (!confirm('Reopen application? Financial entries already recorded will remain.')) return;
    const j = await window._vsApi({ action: 'reopen' });
    if (j.status !== 'success') { alert(j.message); return; }
    await window._vsReload();
    window._renderVsStatus();
    const badge = document.getElementById('vs-submitted-badge');
    if (badge) badge.style.display = 'none';
};

/* ── Download full doc package ──────────────────────────────── */
window._vsDownloadDocPackage = async function () {
    const cfg = window._vs.cfg;
    const btn = event?.target;
    if (btn) { btn.disabled = true; btn.innerHTML = '⏳ Generating…'; }

    try {
        const res = await fetch(cfg.api.genDocPackage, {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ work_sys_id: cfg.workSysId }),
        });
        if (!res.ok) throw new Error('Server error ' + res.status);

        const ct = res.headers.get('Content-Type') ?? '';
        if (ct.includes('application/json')) {
            const j = await res.json();
            alert(j.message || 'Failed to generate package');
            return;
        }

        const blob = await res.blob();
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href = url; a.download = `visa-docs-${cfg.workSysId}.zip`;
        document.body.appendChild(a); a.click();
        setTimeout(() => { URL.revokeObjectURL(url); a.remove(); }, 1000);
    } catch (e) {
        alert('Download failed: ' + e.message);
    } finally {
        if (btn) { btn.disabled = false; btn.innerHTML = '📦 Download Full Package'; }
    }
};