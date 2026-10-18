/**
 * FILE PATH: /pages/task-tabs/ww-visa/status.js
 * Application Status tab — overall visa application status
 * Shows per-traveler VTNR, status, docs, costing summary
 * Task Create button when ALL travelers are ready
 */

/* ── VTNR Generator ─────────────────────────────────────────
 * 6-char alphanumeric (mixed case) from work_sys_id + traveler_sys_id
 * Deterministic — same inputs always produce same VTNR
 * Algorithm: sha256-like using charCode sums in 5-char blocks
 */
function _vsGenerateVTNR(workSysId, travSysId) {
    const raw    = workSysId + '|' + travSysId;
    const chars  = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    // Simple deterministic hash: djb2-style
    let hash = 5381;
    for (let i = 0; i < raw.length; i++) {
        hash = ((hash << 5) + hash) + raw.charCodeAt(i);
        hash = hash & 0x7FFFFFFF; // keep positive 31-bit
    }
    // Generate 6 chars using hash with position seeds
    let result = '';
    for (let i = 0; i < 6; i++) {
        let seed = hash;
        for (let j = 0; j < i + 1; j++) {
            seed = ((seed << 5) + seed) + (i * 31 + j * 17 + raw.charCodeAt(j % raw.length));
            seed = seed & 0x7FFFFFFF;
        }
        result += chars[Math.abs(seed) % chars.length];
    }
    return result;
}

/* ── Status colors ──────────────────────────────────────────── */
const VS_STATUS = {
    pending:   { bg:'#F1F5F9', color:'#64748B', label:'Pending' },
    ready:     { bg:'#DCFCE7', color:'#15803D', label:'Ready' },
    submitted: { bg:'#EEF2FF', color:'#4F46E5', label:'Submitted' },
    approved:  { bg:'#D1FAE5', color:'#065F46', label:'Approved' },
    rejected:  { bg:'#FEE2E2', color:'#B91C1C', label:'Rejected' },
    on_hold:   { bg:'#FEF3C7', color:'#92400E', label:'On Hold' },
};

window._vsStatusChip = function _vsStatusChip(status) {
    const s = VS_STATUS[status] ?? VS_STATUS.pending;
    return `<span style="background:${s.bg};color:${s.color};font-size:.72rem;font-weight:700;
                         padding:3px 10px;border-radius:999px;white-space:nowrap;">● ${s.label}</span>`;
}

/* ── Render Application Status tab ─────────────────────────── */
window._renderVsAppStatus = function () {
    const el = document.getElementById('vs-panel-status');
    if (!el) return;

    const data      = window._vs.data;
    const travelers = data?.vs_travelers ?? [];
    const meta      = data?.meta_data ?? {};
    const workSysId = window._vs.cfg.workSysId;

    if (!travelers.length) {
        el.innerHTML = `<div style="padding:32px;text-align:center;color:#94A3B8;font-size:.875rem;">
            No travelers linked yet. Add travelers from the Traveler Docs tab.
        </div>`;
        return;
    }

    // Compute overall stats
    const total       = travelers.length;
    const ready       = travelers.filter(t => t.app_status === 'ready').length;
    const submitted   = travelers.filter(t => t.app_status === 'submitted').length;
    const approved    = travelers.filter(t => t.app_status === 'approved').length;
    const rejected    = travelers.filter(t => t.app_status === 'rejected').length;
    const allReady    = travelers.every(t =>
        ['ready','submitted','approved'].includes(t.app_status ?? 'pending')
    );
    const pct = Math.round((ready + submitted + approved) / total * 100);

    // Costing
    const clientPrice   = parseFloat(meta.client_price   ?? meta.selling_price ?? 0);
    const purchasePrice = parseFloat(meta.purchase_price ?? meta.buying_price  ?? 0);
    const totalClient   = clientPrice * total;
    const totalPurchase = purchasePrice * total;
    const profit        = totalClient - totalPurchase;

    el.innerHTML = `
    <div style="max-width:860px;">

        <!-- Overall progress -->
        <div style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;
                    padding:18px 20px;margin-bottom:18px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                <div style="font-size:.95rem;font-weight:800;color:#0F172A;">Application Progress</div>
                <div style="font-size:.82rem;font-weight:700;color:${pct===100?'#15803D':'#64748B'};">
                    ${ready + submitted + approved} / ${total} Ready
                </div>
            </div>
            <div style="height:8px;background:#E2E8F0;border-radius:999px;overflow:hidden;margin-bottom:14px;">
                <div style="height:100%;width:${pct}%;background:${pct===100?'#22C55E':'#6366f1'};
                            border-radius:999px;transition:width .3s;"></div>
            </div>
            <div style="display:flex;gap:16px;flex-wrap:wrap;">
                ${_vsStatBadge('Pending',   travelers.filter(t=>!t.app_status||t.app_status==='pending').length,   '#F1F5F9','#64748B')}
                ${_vsStatBadge('Ready',     ready,     '#DCFCE7','#15803D')}
                ${_vsStatBadge('Submitted', submitted, '#EEF2FF','#4F46E5')}
                ${_vsStatBadge('Approved',  approved,  '#D1FAE5','#065F46')}
                ${rejected ? _vsStatBadge('Rejected', rejected, '#FEE2E2','#B91C1C') : ''}
            </div>
        </div>

        <!-- Per-traveler rows -->
        <div style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;
                    overflow:visible;margin-bottom:18px;">
            <div style="padding:12px 16px;background:#F8FAFC;border-bottom:1px solid #E2E8F0;
                        display:grid;grid-template-columns:2fr 1.2fr 1fr 1fr 1fr;gap:12px;
                        font-size:.72rem;font-weight:700;color:#94A3B8;text-transform:uppercase;letter-spacing:.05em;">
                <div>Traveler</div>
                <div>VTNR</div>
                <div>Docs</div>
                <div>Cover Letter</div>
                <div>Status</div>
            </div>
            ${travelers.map(t => _vsAppStatusRow(t, workSysId)).join('')}
        </div>

        <!-- Costing summary (show when all ready) -->
        ${allReady && clientPrice > 0 ? `
        <div style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;
                    padding:18px 20px;margin-bottom:18px;">
            <div style="font-size:.85rem;font-weight:800;color:#0F172A;margin-bottom:14px;">Costing Summary</div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                ${_vsCostBox('Client Total', 'BDT ' + totalClient.toLocaleString(), '#EEF2FF', '#4F46E5')}
                ${_vsCostBox('Purchase Total', 'BDT ' + totalPurchase.toLocaleString(), '#F1F5F9', '#475569')}
                ${_vsCostBox('Profit', 'BDT ' + profit.toLocaleString(), profit>=0?'#DCFCE7':'#FEE2E2', profit>=0?'#15803D':'#B91C1C')}
            </div>
            <div style="margin-top:10px;font-size:.75rem;color:#94A3B8;">
                ${total} traveler(s) × BDT ${clientPrice.toLocaleString()} client price
            </div>
        </div>` : ''}

        <!-- Task Create button -->
        <div style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:18px 20px;">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
                <div>
                    <div style="font-size:.9rem;font-weight:700;color:#0F172A;margin-bottom:4px;">
                        Create Task
                    </div>
                    <div style="font-size:.78rem;color:#64748B;">
                        ${allReady
                            ? 'All travelers are ready. You can now create the submission task.'
                            : `Waiting for ${total - (ready+submitted+approved)} more traveler(s) to be marked Ready.`}
                    </div>
                </div>
                <button onclick="window._vsCreateTask()"
                        ${allReady ? '' : 'disabled'}
                        style="padding:10px 24px;border-radius:9px;font-size:.875rem;font-weight:700;
                               cursor:${allReady?'pointer':'not-allowed'};border:none;
                               background:${allReady?'#4F46E5':'#E2E8F0'};
                               color:${allReady?'#fff':'#94A3B8'};">
                    + Create Task
                </button>
            </div>
        </div>

    </div>`;
};

function _vsAppStatusRow(t, workSysId) {
    const vtnr      = _vsGenerateVTNR(workSysId, t.sys_id);
    const docs      = t.docs ?? [];
    const uploaded  = docs.filter(d => d.status === 'uploaded').length;
    const total     = docs.filter(d => d.required !== false).length;
    const hasCL     = !!t.cover_letter;
    const status    = t.app_status ?? 'pending';
    const approvalDoc = t.approval_doc ?? null;

    // Approval doc section — show when approved or to upload
    const approvalHtml = status === 'approved' || status === 'submitted'
        ? `<div style="margin-top:6px;display:flex;align-items:center;gap:6px;">
            ${approvalDoc
                ? `<a href="${_vsEsc(approvalDoc.serve_url ?? '#')}" target="_blank"
                      style="font-size:.72rem;color:#4F46E5;text-decoration:none;
                             padding:3px 8px;background:#EEF2FF;border-radius:5px;font-weight:600;">
                      📄 Download Approval
                   </a>`
                : `<label style="font-size:.72rem;color:#64748B;cursor:pointer;
                                 padding:3px 8px;background:#F1F5F9;border-radius:5px;
                                 font-weight:600;border:1px solid #E2E8F0;">
                      📎 Upload Approval
                      <input type="file" accept=".pdf,.jpg,.jpeg,.png"
                             style="display:none;"
                             onchange="window._vsUploadApprovalDoc('${_vsEsc(t.sys_id)}',this)">
                   </label>`
            }
           </div>`
        : '';

    return `<div style="border-bottom:1px solid #F1F5F9;">
        <div style="display:grid;grid-template-columns:2fr 1.2fr 1fr 1fr 1fr;gap:12px;
                    align-items:center;padding:12px 16px;">
            <div>
                <div style="font-size:.875rem;font-weight:700;color:#0F172A;">${_vsEsc(t.name ?? t.sys_id)}</div>
                <div style="font-size:.72rem;color:#94A3B8;">${_vsEsc(t.passport_no ?? '')}</div>
                ${approvalHtml}
            </div>
            <div>
                <code style="background:#F1F5F9;padding:3px 8px;border-radius:5px;
                             font-size:.8rem;font-weight:700;color:#4F46E5;
                             letter-spacing:.05em;">${vtnr}</code>
                <button onclick="navigator.clipboard.writeText('${vtnr}').then(()=>_vsToast('VTNR copied ✓'))"
                        title="Copy VTNR"
                        style="margin-left:6px;padding:2px 6px;border:1px solid #E2E8F0;
                               border-radius:4px;background:#fff;cursor:pointer;
                               font-size:.7rem;color:#94A3B8;">📋</button>
            </div>
            <div>
                <span style="font-size:.82rem;font-weight:600;
                             color:${uploaded===total&&total>0?'#15803D':'#64748B'};">
                    ${uploaded}/${total}
                </span>
                <span style="font-size:.72rem;color:#94A3B8;"> docs</span>
            </div>
            <div>
                <span style="font-size:.82rem;">${hasCL ? '✅' : '⬜'}</span>
                <span style="font-size:.72rem;color:#94A3B8;margin-left:4px;">${hasCL?'Ready':'None'}</span>
            </div>
            <div>${_vsStatusChip(status)}</div>
        </div>
    </div>`;
}

/* ── Upload approval document ───────────────────────────────── */
window._vsUploadApprovalDoc = async function (travSysId, input) {
    const file = input.files?.[0];
    if (!file) return;

    const label = input.closest('label');
    if (label) label.textContent = 'Uploading…';

    const fd = new FormData();
    fd.append('work_sys_id',     window._vs.cfg.workSysId);
    fd.append('traveler_sys_id', travSysId);
    fd.append('file',            file);

    try {
        const apiUrl = window._vs.cfg.api.uploadApprovalDoc
            ?? (window._vs.cfg.api.visaServices.replace('endpoints.php', 'upload-approval-doc.php'));

        const res = await fetch(apiUrl, { method: 'POST', body: fd });
        const j   = await res.json();

        if (j.status !== 'success') {
            alert('Upload failed: ' + (j.message ?? 'Unknown error'));
            if (label) label.textContent = '📎 Upload Approval';
            return;
        }

        window._vs.data = j.data;
        _vsToast('Approval document uploaded ✓');
        window._renderVsAppStatus();
    } catch (e) {
        alert('Upload error: ' + e.message);
        if (label) label.textContent = '📎 Upload Approval';
    }
};

function _vsStatBadge(label, count, bg, color) {
    return `<div style="background:${bg};padding:6px 14px;border-radius:8px;text-align:center;">
        <div style="font-size:1.1rem;font-weight:800;color:${color};">${count}</div>
        <div style="font-size:.7rem;font-weight:600;color:${color};opacity:.8;">${label}</div>
    </div>`;
}

function _vsCostBox(label, value, bg, color) {
    return `<div style="background:${bg};padding:14px;border-radius:10px;text-align:center;">
        <div style="font-size:1rem;font-weight:800;color:${color};">${_vsEsc(value)}</div>
        <div style="font-size:.72rem;font-weight:600;color:${color};opacity:.8;margin-top:3px;">${label}</div>
    </div>`;
}

/* ── Create Task ────────────────────────────────────────────── */
window._vsCreateTask = async function () {
    const data    = window._vs.data;
    const meta    = data?.meta_data ?? {};
    const trav    = data?.vs_travelers ?? [];
    const workId  = window._vs.cfg.workSysId;

    // Open task create modal with pre-filled visa info
    if (typeof window.openTaskCreateModal === 'function') {
        window.openTaskCreateModal({
            work_sys_id:   workId,
            task_type:     'visa_submission',
            subject:       `Visa Submission — ${meta.country_name ?? ''} (${trav.length} traveler${trav.length>1?'s':''})`,
            vendor_hint:   (meta.country_name ?? '') + ' Embassy',
            amount:        parseFloat(meta.purchase_price ?? 0) * trav.length,
        });
    } else {
        // Fallback — show task URL
        const params = new URLSearchParams({
            work_sys_id: workId,
            subject:     `Visa Submission — ${meta.country_name ?? ''}`,
            prefill:     '1',
        });
        window.open(`create-task.php?${params}`, '_blank');
    }
};