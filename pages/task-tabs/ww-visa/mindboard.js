/**
 * FILE PATH: /pages/task-tabs/ww-visa/mindboard.js
 * Visa Service Module — "Mind Board" tab
 *
 * LFDP-21: now shows lead segment summary card (from cfg.countrySegments)
 * and reads mb_notes that move-to-work.php writes at conversion time.
 *
 * Shows:
 *  - Lead segment info card (country, type, applicants from lead)
 *  - Standard paste/note area (auto-populated by move-to-work on conversion)
 *  - AI Visa Readiness Check: compares master required docs vs traveler docs
 *  - Per-traveler readiness summary cards
 */

window._renderVsMindboard = function () {
    const el = document.getElementById('vs-tab-mindboard');
    if (!el) return;

    const data      = window._vs.data;
    const meta      = data?.meta_data;
    const travelers = data?.vs_travelers ?? [];
    const cfg       = window._vs.cfg;

    // Lead segment data comes from two places:
    //  1. cfg.countrySegments — passed from show-works.php at mount time
    //  2. meta.mb_notes — written by move-to-work.php on conversion
    const countrySegs  = cfg?.countrySegments ?? [];
    const hasLeadSegs  = countrySegs.length > 0;

    el.innerHTML = `
    <div style="padding:16px 0;">

        ${hasLeadSegs ? _vsMbLeadSegCard(countrySegs) : ''}

        <!-- Notes / paste area -->
        <div style="background:#F8FAFC;border:1.5px solid #E2E8F0;border-radius:12px;
                    padding:14px;margin-bottom:20px;">
            <div style="font-size:.78rem;font-weight:700;color:#64748B;text-transform:uppercase;
                        letter-spacing:.05em;margin-bottom:10px;">Mind Board Notes</div>
            <textarea id="vs-mb-notes" rows="5" placeholder="Paste requirements, client info, embassy notes…"
                      style="width:100%;border:none;background:transparent;resize:vertical;
                             font-size:.875rem;color:#0F172A;outline:none;line-height:1.7;box-sizing:border-box;"
                      onblur="window._vsSaveMbNotes()">${_vsEsc(meta?.mb_notes ?? '')}</textarea>
            <div style="display:flex;justify-content:flex-end;margin-top:6px;">
                <button onclick="window._vsRunReadinessCheck()"
                        style="padding:7px 16px;background:#4F46E5;color:#fff;font-size:.8rem;
                               font-weight:700;border:none;border-radius:8px;cursor:pointer;">
                    🔍 Run AI Readiness Check
                </button>
            </div>
        </div>

        <!-- Visa not selected notice -->
        ${!meta?.master_visa_sys_id
            ? `<div style="padding:20px;background:#FEF3C7;border:1px solid #FDE68A;border-radius:10px;
                           font-size:.875rem;color:#92400E;">
                   ⚠️ No visa selected yet. Go to <strong>Visa Info</strong> tab to select a visa product first.
               </div>`
            : `<!-- Selected visa summary pill -->
               <div style="display:flex;align-items:center;gap:10px;padding:12px 14px;
                           background:#EEF2FF;border-radius:10px;margin-bottom:18px;">
                   <span style="font-size:1.2rem;">🌍</span>
                   <div>
                       <div style="font-size:.9rem;font-weight:700;color:#0F172A;">${_vsEsc(meta.master_visa_title ?? '')}</div>
                       <div style="font-size:.75rem;color:#64748B;">
                           ${_vsEsc(meta.country_name ?? '')} ·
                           ${_vsEsc(meta.visa_type_name ?? '')} ·
                           ${_vsEsc(meta.visa_category_name ?? '')}
                       </div>
                   </div>
                   <div style="margin-left:auto;">
                       ${window._vsTravStatusChip?.(data.status) ?? ''}
                   </div>
               </div>

               <!-- Readiness section -->
               <div id="vs-mb-readiness">
                   ${travelers.length === 0
                       ? `<div style="padding:24px;text-align:center;color:#94A3B8;font-size:.875rem;">
                              No travelers added yet. Add travelers in the <strong>Traveler Docs</strong> tab.
                          </div>`
                       : _vsMbReadinessHtml(travelers)
                   }
               </div>`
        }
    </div>`;
};

/* ── Lead segment card ───────────────────────────────────────── */
function _vsMbLeadSegCard(segs) {
    if (!segs.length) return '';

    const rows = segs.map(s => {
        const country  = _vsEsc(s.country_name     ?? '—');
        const type     = _vsEsc(s.visa_type_name    ?? s.category_name ?? '—');
        const price    = s.b2c_price ? `BDT ${parseFloat(s.b2c_price).toLocaleString()}` : '—';
        const applicants = (s.applicants ?? []).map(a => _vsEsc(a.name || '?')).filter(Boolean);
        return `<div style="border-left:3px solid #6366f1;padding:8px 10px 8px 12px;
                             background:#FAFAFE;border-radius:0 8px 8px 0;margin-bottom:6px;">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:6px;">
                <div>
                    <span style="font-size:.85rem;font-weight:800;color:#0F172A;">🌍 ${country}</span>
                    <span style="font-size:.75rem;color:#6366f1;margin-left:8px;">${type}</span>
                </div>
                <span style="font-size:.78rem;font-weight:700;color:#059669;">${price}</span>
            </div>
            ${applicants.length ? `
            <div style="margin-top:4px;font-size:.73rem;color:#64748B;">
                👥 ${applicants.join(' · ')}
            </div>` : ''}
        </div>`;
    }).join('');

    return `
    <div style="background:#EEF2FF;border:1.5px solid #C7D2FE;border-radius:12px;
                padding:14px;margin-bottom:18px;">
        <div style="font-size:.75rem;font-weight:800;color:#4338CA;text-transform:uppercase;
                    letter-spacing:.05em;margin-bottom:10px;">📋 Lead Segments</div>
        ${rows}
        ${segs.length > 1 ? `
        <div style="margin-top:6px;font-size:.72rem;color:#6366f1;font-weight:600;">
            ${segs.length} visa segment${segs.length > 1 ? 's' : ''} from this lead
        </div>` : ''}
    </div>`;
}

/* ── Readiness cards ─────────────────────────────────────────── */
function _vsMbReadinessHtml(travelers) {
    if (!travelers.length) return '';

    const allReady = travelers.every(t => window._vsTravelerReady(t));

    return `
    <div style="font-size:.8rem;font-weight:700;color:#475569;text-transform:uppercase;
                letter-spacing:.04em;margin-bottom:12px;">Traveler Readiness</div>

    ${allReady
        ? `<div style="padding:14px 16px;background:#DCFCE7;border:1px solid #BBF7D0;border-radius:10px;
                       font-size:.875rem;font-weight:700;color:#15803D;margin-bottom:16px;">
               ✅ All travelers are ready for submission!
           </div>`
        : ''}

    <div style="display:flex;flex-direction:column;gap:10px;">
        ${travelers.map(t => _vsMbTravelerCard(t)).join('')}
    </div>`;
}

function _vsMbTravelerCard(t) {
    const docs        = t.docs ?? [];
    const total       = docs.length;
    const uploaded    = docs.filter(d => d.status === 'uploaded').length;
    const missing     = docs.filter(d => d.status === 'missing');
    const pct         = total > 0 ? Math.round(uploaded / total * 100) : 0;
    const ready       = window._vsTravelerReady(t);
    const hasCover    = !!(t.cover_letter?.edited || t.cover_letter?.ai_draft);
    const travStatus  = t.app_status ?? 'pending';

    return `
    <div style="border:1.5px solid ${ready?'#BBF7D0':'#E2E8F0'};border-radius:10px;
                padding:14px;background:${ready?'#F0FDF4':'#fff'};">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
            <div>
                <span style="font-size:.9rem;font-weight:700;color:#0F172A;">${_vsEsc(t.name)}</span>
                <span style="font-size:.75rem;color:#94A3B8;margin-left:8px;">${_vsEsc(t.passport_no ?? '—')}</span>
            </div>
            <div style="display:flex;align-items:center;gap:8px;">
                ${_vsTravStatusChip(travStatus)}
                <button onclick="window._vsSwitchTab('travelers',document.querySelector('.vs-tab-btn[data-tab=travelers]'));setTimeout(()=>window._vsSwitchTraveler('${_vsEsc(t.sys_id)}',document.querySelector('[data-trav-id=${_vsEsc(t.sys_id)}]')),100)"
                        style="padding:4px 10px;border-radius:6px;border:1px solid #E2E8F0;
                               background:#fff;font-size:.73rem;cursor:pointer;color:#4F46E5;font-weight:600;">
                    Open →
                </button>
            </div>
        </div>

        <!-- Doc progress bar -->
        ${total > 0 ? `
        <div style="margin-bottom:8px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                <span style="font-size:.73rem;color:#64748B;">Documents</span>
                <span style="font-size:.73rem;font-weight:700;color:${pct===100?'#15803D':'#64748B'};">${uploaded}/${total}</span>
            </div>
            <div style="height:5px;background:#E2E8F0;border-radius:999px;overflow:hidden;">
                <div style="height:100%;width:${pct}%;background:${pct===100?'#22C55E':'#6366f1'};
                            border-radius:999px;transition:width .3s;"></div>
            </div>
        </div>` : `<div style="font-size:.78rem;color:#94A3B8;margin-bottom:8px;">No documents synced yet.</div>`}

        <!-- Missing docs list -->
        ${missing.length
            ? `<div style="margin-top:6px;">
                   ${missing.slice(0,3).map(d =>
                       `<span style="display:inline-block;margin:2px 4px 2px 0;padding:2px 8px;
                                    background:#FEE2E2;color:#B91C1C;border-radius:5px;font-size:.73rem;">
                           ❌ ${_vsEsc(d.doc)}
                        </span>`
                   ).join('')}
                   ${missing.length > 3
                       ? `<span style="font-size:.73rem;color:#94A3B8;">+${missing.length-3} more</span>`
                       : ''}
               </div>`
            : ''}

        <!-- Cover letter status -->
        <div style="margin-top:8px;font-size:.75rem;color:${hasCover?'#15803D':'#94A3B8'};">
            ${hasCover ? '✉️ Cover letter ready' : '✉️ Cover letter not generated'}
        </div>
    </div>`;
}

/* ── Per-traveler status chip ────────────────────────────────── */
window._vsTravStatusChip = function (status) {
    const map = {
        pending:   { bg:'#F1F5F9', c:'#64748B', label:'Pending' },
        ready:     { bg:'#DCFCE7', c:'#15803D', label:'Ready' },
        submitted: { bg:'#EEF2FF', c:'#4F46E5', label:'Submitted' },
        approved:  { bg:'#D1FAE5', c:'#065F46', label:'Approved' },
        rejected:  { bg:'#FEE2E2', c:'#B91C1C', label:'Rejected' },
        on_hold:   { bg:'#FEF3C7', c:'#92400E', label:'On Hold' },
    };
    const s = map[status] ?? { bg:'#F1F5F9', c:'#64748B', label: status };
    return `<span style="background:${s.bg};color:${s.c};font-size:.72rem;font-weight:700;
                         padding:3px 9px;border-radius:999px;">● ${s.label}</span>`;
};

/* ── Save mind board notes ───────────────────────────────────── */
window._vsSaveMbNotes = async function () {
    const notes = document.getElementById('vs-mb-notes')?.value ?? '';
    const meta  = window._vs.data?.meta_data ?? {};
    if (meta.mb_notes === notes) return; // no change

    await window._vsApi({ action: 'update_mb_notes', mb_notes: notes });
    if (window._vs.data?.meta_data) window._vs.data.meta_data.mb_notes = notes;
};

/* ── AI Readiness Check ──────────────────────────────────────── */
window._vsRunReadinessCheck = async function () {
    const meta      = window._vs.data?.meta_data;
    const travelers = window._vs.data?.vs_travelers ?? [];
    if (!meta?.master_visa_sys_id) {
        alert('Please select a visa service first (Visa Info tab).');
        return;
    }

    const rdEl = document.getElementById('vs-mb-readiness');
    if (rdEl) rdEl.innerHTML = `
        <div style="padding:20px;text-align:center;color:#64748B;font-size:.875rem;">
            <div style="display:inline-block;width:22px;height:22px;border:3px solid #E2E8F0;
                        border-top-color:#6366f1;border-radius:50%;
                        animation:vs-spin .7s linear infinite;margin-bottom:8px;"></div>
            <div>Syncing docs & checking readiness…</div>
        </div>`;

    await window._vsSyncDocs();

    const updatedTravelers = window._vs.data?.vs_travelers ?? [];
    if (rdEl) rdEl.innerHTML = _vsMbReadinessHtml(updatedTravelers);

    window._vsUpdateTravelerBadge?.();
};