/**
 * FILE PATH: /pages/task-tabs/ww-visa/status.js
 * Application Status tab — overall visa application status
 *
 * LFDP-21 changes:
 *   - _vsCreateTask() replaced with self-contained modal (transport pattern)
 *   - Vendor payment + client sale entries after task creation
 *   - Loads vendor/account lists from cfg.api.allVendors / allAccounts
 */

/* ── VTNR Generator — JS side ───────────────────────────────── */
function _vsGenerateVTNR(workSysId, travSysId) {
    const str = workSysId + '::' + travSysId;
    let h = 5381;
    for (let i = 0; i < str.length; i++) {
        h = ((h << 5) + h) ^ (str.charCodeAt(i) + (i * 31));
        h = h >>> 0; // keep 32-bit unsigned
    }
    const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let result  = '';
    let seed    = h;
    for (let p = 0; p < 6; p++) {
        seed   = ((seed * 1664525 + 1013904223) + (p * 6364136223846793005)) >>> 0;
        result += chars[seed % chars.length];
    }
    return result;
}

/* ── Render Application Status tab ─────────────────────────── */
window._renderVsAppStatus = function () {
    const el = document.getElementById('vs-panel-status');
    if (!el) return;

    const data     = window._vs.data;
    const meta     = data?.meta_data ?? {};
    const travelers = data?.vs_travelers ?? [];
    const workSysId = window._vs.cfg?.workSysId ?? '';

    // Per-traveler counts
    const total     = travelers.length;
    const ready     = travelers.filter(t => t.app_status === 'ready').length;
    const submitted = travelers.filter(t => t.app_status === 'submitted').length;
    const approved  = travelers.filter(t => t.app_status === 'approved').length;
    const rejected  = travelers.filter(t => t.app_status === 'rejected').length;
    const pending   = travelers.filter(t => !t.app_status || t.app_status === 'pending').length;

    const allReady = total > 0 && (ready + submitted + approved) >= total;

    // Costing
    const clientPrice   = parseFloat(meta.b2c_price      ?? 0);
    const purchasePrice = parseFloat(meta.purchase_price  ?? 0);
    const totalClient   = clientPrice   * total;
    const totalPurchase = purchasePrice * total;
    const profit        = totalClient - totalPurchase;

    // Check if a task was already created
    const taskCreated = !!(data?.task_sys_id);

    el.innerHTML = `
    <div style="padding:16px 0;">

        <!-- VTNR + progress bar header -->
        <div style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;
                    padding:18px 20px;margin-bottom:16px;">
            <div style="display:flex;align-items:center;justify-content:space-between;
                        flex-wrap:wrap;gap:12px;margin-bottom:14px;">
                <div style="font-size:.85rem;font-weight:800;color:#0F172A;">
                    Application Status Overview
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    ${_vsStatBadge('Total',     total,     '#EEF2FF','#4F46E5')}
                    ${_vsStatBadge('Pending',   pending,   '#FEF3C7','#92400E')}
                    ${_vsStatBadge('Ready',     ready,     '#DCFCE7','#15803D')}
                    ${_vsStatBadge('Submitted', submitted, '#EEF2FF','#4338CA')}
                    ${_vsStatBadge('Approved',  approved,  '#D1FAE5','#065F46')}
                    ${rejected ? _vsStatBadge('Rejected', rejected, '#FEE2E2','#B91C1C') : ''}
                </div>
            </div>

            <!-- Progress bar -->
            ${total > 0 ? `
            <div style="height:6px;background:#F1F5F9;border-radius:999px;overflow:hidden;">
                <div style="height:100%;width:${Math.round((ready+submitted+approved)/total*100)}%;
                            background:#6366f1;border-radius:999px;transition:width .4s;"></div>
            </div>
            <div style="font-size:.72rem;color:#94A3B8;margin-top:6px;">
                ${ready+submitted+approved} / ${total} traveler(s) cleared for submission
            </div>` : `
            <div style="font-size:.85rem;color:#94A3B8;">
                No travelers added yet.
            </div>`}
        </div>

        <!-- Per-traveler rows -->
        ${travelers.length > 0 ? `
        <div style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;
                    margin-bottom:16px;overflow:hidden;">
            <!-- Table header -->
            <div style="display:grid;grid-template-columns:2fr 1.2fr 1fr 1fr 1fr;
                        gap:12px;padding:10px 16px;background:#F8FAFC;
                        font-size:.72rem;font-weight:700;color:#64748B;text-transform:uppercase;
                        letter-spacing:.04em;border-bottom:1px solid #E2E8F0;">
                <div>Traveler</div>
                <div>VTNR</div>
                <div>Docs</div>
                <div>Cover Letter</div>
                <div>Status</div>
            </div>
            ${travelers.map(t => _vsAppStatusRow(t, workSysId)).join('')}
        </div>` : ''}

        <!-- Costing summary -->
        ${meta.b2c_price && meta.purchase_price ? `
        <div style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;
                    padding:18px 20px;margin-bottom:16px;">
            <div style="font-size:.85rem;font-weight:800;color:#0F172A;margin-bottom:14px;">Costing Summary</div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                ${_vsCostBox('Client Total',    'BDT ' + totalClient.toLocaleString(),   '#EEF2FF', '#4F46E5')}
                ${_vsCostBox('Purchase Total',  'BDT ' + totalPurchase.toLocaleString(), '#F1F5F9', '#475569')}
                ${_vsCostBox('Profit',          'BDT ' + profit.toLocaleString(),
                    profit>=0?'#DCFCE7':'#FEE2E2', profit>=0?'#15803D':'#B91C1C')}
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
                        ${taskCreated ? 'Task Created' : 'Create Task'}
                    </div>
                    <div style="font-size:.78rem;color:#64748B;">
                        ${taskCreated
                            ? `✅ Task <span style="font-family:monospace;color:#4F46E5;">${_vsEsc(data.task_sys_id)}</span> linked to this service.`
                            : allReady
                                ? 'All travelers are ready. You can now create the submission task.'
                                : `Waiting for ${total - (ready+submitted+approved)} more traveler(s) to be marked Ready.`}
                    </div>
                </div>
                ${taskCreated
                    ? `<span style="padding:8px 18px;background:#DCFCE7;border:1px solid #BBF7D0;
                                    border-radius:9px;font-size:.8rem;font-weight:700;color:#15803D;">
                           ✓ Task Linked
                       </span>`
                    : `<button onclick="window._vsOpenCreateTaskModal()"
                              ${allReady ? '' : 'disabled'}
                              style="padding:10px 24px;border-radius:9px;font-size:.875rem;font-weight:700;
                                     cursor:${allReady?'pointer':'not-allowed'};border:none;
                                     background:${allReady?'#4F46E5':'#E2E8F0'};
                                     color:${allReady?'#fff':'#94A3B8'};">
                           + Create Task
                       </button>`}
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
                <div style="font-size:.85rem;font-weight:700;color:#0F172A;">${_vsEsc(t.name)}</div>
                <div style="font-size:.72rem;color:#94A3B8;">${_vsEsc(t.passport_no ?? '—')}</div>
            </div>
            <div style="font-family:monospace;font-size:.78rem;font-weight:700;color:#4F46E5;
                        background:#EEF2FF;padding:3px 8px;border-radius:5px;display:inline-block;">
                ${_vsEsc(vtnr)}
            </div>
            <div style="font-size:.8rem;color:${uploaded===total&&total>0?'#15803D':'#64748B'};font-weight:700;">
                ${uploaded}/${total}
            </div>
            <div style="font-size:.8rem;">
                ${hasCL
                    ? `<span style="color:#15803D;">✉️ Ready</span>`
                    : `<span style="color:#94A3B8;">✉️ —</span>`}
            </div>
            <div>
                ${window._vsTravStatusChip?.(status) ?? ''}
                ${approvalHtml}
            </div>
        </div>
    </div>`;
}

function _vsStatBadge(label, count, bg, color) {
    return `<span style="background:${bg};color:${color};font-size:.72rem;font-weight:700;
                         padding:4px 10px;border-radius:999px;">
                ${label}: ${count}
            </span>`;
}

function _vsCostBox(label, value, bg, color) {
    return `<div style="background:${bg};padding:14px;border-radius:10px;text-align:center;">
        <div style="font-size:1rem;font-weight:800;color:${color};">${_vsEsc(value)}</div>
        <div style="font-size:.72rem;font-weight:600;color:${color};opacity:.8;margin-top:3px;">${label}</div>
    </div>`;
}

/* ── Create Task Modal ──────────────────────────────────────── */
window._vsOpenCreateTaskModal = function () {
    const data  = window._vs.data;
    const meta  = data?.meta_data ?? {};
    const trav  = data?.vs_travelers ?? [];
    const cfg   = window._vs.cfg;

    // Pre-calculated amounts
    const travCount     = trav.length;
    const clientPrice   = parseFloat(meta.b2c_price      ?? 0);
    const purchasePrice = parseFloat(meta.purchase_price  ?? 0);
    const totalClient   = clientPrice   * travCount;
    const totalPurchase = purchasePrice * travCount;
    const countryName   = meta.country_name ?? '';
    const visaTitle     = meta.master_visa_title ?? `${countryName} Visa`;

    // Store for submit handler
    window._vsTaskClientTotal   = totalClient;
    window._vsTaskPurchaseTotal = totalPurchase;
    window._vsTaskTravCount     = travCount;

    document.getElementById('vsCreateTaskModal')?.remove();

    const modal = document.createElement('div');
    modal.id    = 'vsCreateTaskModal';
    modal.style.cssText = 'position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;padding:16px;background:rgba(0,0,0,.45);';
    modal.innerHTML = `
    <div style="background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.25);
                width:100%;max-width:440px;max-height:92vh;overflow-y:auto;">
        <div style="display:flex;align-items:center;justify-content:space-between;
                    padding:16px 20px;border-bottom:1px solid #F1F5F9;">
            <div style="font-size:.95rem;font-weight:800;color:#0F172A;">
                🗂️ Create Visa Task
            </div>
            <button onclick="document.getElementById('vsCreateTaskModal').remove()"
                    style="width:28px;height:28px;background:#F1F5F9;border:none;border-radius:8px;
                           cursor:pointer;font-size:1rem;color:#64748B;display:flex;align-items:center;
                           justify-content:center;">✕</button>
        </div>

        <div style="padding:16px 20px;display:flex;flex-direction:column;gap:14px;">
            <div style="padding:12px 14px;background:#EEF2FF;border-radius:10px;font-size:.82rem;color:#3730A3;">
                <strong>${_vsEsc(visaTitle)}</strong> · ${travCount} traveler${travCount !== 1 ? 's' : ''}
                <br><span style="color:#6366F1;font-size:.75rem;">
                    Task তৈরির পরে vendor payment ও client sale entry রেকর্ড হবে।
                </span>
            </div>

            <!-- Client Sale Entry -->
            ${totalClient > 0 ? `
            <div style="background:#ECFDF5;border:1px solid #A7F3D0;border-radius:12px;padding:14px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                    <span style="font-size:.75rem;font-weight:800;color:#065F46;text-transform:uppercase;letter-spacing:.04em;">
                        Client Sale Entry
                    </span>
                    <span style="font-size:.8rem;font-weight:800;color:#059669;">
                        BDT ${totalClient.toLocaleString()}
                    </span>
                </div>
                <div style="font-size:.75rem;color:#047857;margin-bottom:8px;">
                    ${travCount} × BDT ${clientPrice.toLocaleString()} = BDT ${totalClient.toLocaleString()}
                </div>
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                    <input type="checkbox" id="vsIncludeClientEntry" checked
                           style="width:14px;height:14px;accent-color:#059669;">
                    <span style="font-size:.78rem;font-weight:700;color:#065F46;">Include Client Sale Entry</span>
                </label>
            </div>` : ''}

            <!-- Vendor (embassy / agent) -->
            <div>
                <label style="display:block;font-size:.78rem;font-weight:700;color:#374151;margin-bottom:6px;">
                    Vendor (Embassy / Agent)
                </label>
                <div style="position:relative;" id="vsVendorWrap">
                    <input id="vsVendorSearch" placeholder="Search vendor…" autocomplete="off"
                           oninput="window._vsFilterVendors(this.value)"
                           onfocus="window._vsFilterVendors(this.value)"
                           style="width:100%;padding:9px 12px;border:1.5px solid #E2E8F0;border-radius:9px;
                                  font-size:.85rem;outline:none;box-sizing:border-box;">
                    <ul id="vsVendorDrop"
                        style="display:none;position:absolute;width:100%;background:#fff;
                               border:1px solid #E2E8F0;border-radius:9px;margin-top:4px;
                               max-height:180px;overflow-y:auto;box-shadow:0 8px 24px rgba(0,0,0,.1);
                               z-index:50;padding:0;list-style:none;"></ul>
                </div>
                <input type="hidden" id="vsVendorId">
            </div>

            <!-- Transaction mode -->
            <div>
                <label style="display:block;font-size:.78rem;font-weight:700;color:#374151;margin-bottom:6px;">
                    Transaction Type
                </label>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                    <label id="vsModeRTLbl"
                           style="display:flex;align-items:center;justify-content:center;gap:6px;
                                  padding:9px;border:2px solid #10B981;background:#ECFDF5;
                                  color:#065F46;border-radius:10px;cursor:pointer;font-size:.78rem;font-weight:700;">
                        <input type="radio" name="vs_txn_mode" value="realtime" checked
                               onchange="window._vsToggleTxnMode()" style="display:none;">
                        ⚡ Real-time
                    </label>
                    <label id="vsModeNRLbl"
                           style="display:flex;align-items:center;justify-content:center;gap:6px;
                                  padding:9px;border:2px solid #E2E8F0;background:#fff;
                                  color:#64748B;border-radius:10px;cursor:pointer;font-size:.78rem;font-weight:700;">
                        <input type="radio" name="vs_txn_mode" value="non_realtime"
                               onchange="window._vsToggleTxnMode()" style="display:none;">
                        🕐 Non-real-time
                    </label>
                </div>
            </div>

            <!-- Own Account (real-time only) -->
            <div id="vsAccountSection">
                <label style="display:block;font-size:.78rem;font-weight:700;color:#374151;margin-bottom:6px;">
                    Own Account
                </label>
                <div style="position:relative;" id="vsAccountWrap">
                    <input id="vsAccountSearch" placeholder="Search account…" autocomplete="off"
                           oninput="window._vsFilterAccounts(this.value)"
                           onfocus="window._vsFilterAccounts(this.value)"
                           style="width:100%;padding:9px 12px;border:1.5px solid #E2E8F0;border-radius:9px;
                                  font-size:.85rem;outline:none;box-sizing:border-box;">
                    <ul id="vsAccountDrop"
                        style="display:none;position:absolute;width:100%;background:#fff;
                               border:1px solid #E2E8F0;border-radius:9px;margin-top:4px;
                               max-height:180px;overflow-y:auto;box-shadow:0 8px 24px rgba(0,0,0,.1);
                               z-index:50;padding:0;list-style:none;"></ul>
                </div>
                <input type="hidden" id="vsAccountId">
            </div>

            <!-- Purpose -->
            <div>
                <label style="display:block;font-size:.78rem;font-weight:700;color:#374151;margin-bottom:6px;">
                    Purpose
                </label>
                <textarea id="vsPurpose" rows="2"
                          style="width:100%;padding:9px 12px;border:1.5px solid #E2E8F0;border-radius:9px;
                                 font-size:.85rem;outline:none;resize:vertical;box-sizing:border-box;">Visa Processing — ${_vsEsc(visaTitle)}</textarea>
            </div>

            <!-- QTY / Rate / Amount -->
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;">
                <div>
                    <label style="display:block;font-size:.72rem;color:#64748B;margin-bottom:4px;">QTY</label>
                    <input type="number" id="vsQty" placeholder="0"
                           style="width:100%;padding:8px;border:1.5px solid #E2E8F0;border-radius:8px;
                                  font-size:.82rem;outline:none;box-sizing:border-box;" class="vs-calc">
                </div>
                <div>
                    <label style="display:block;font-size:.72rem;color:#64748B;margin-bottom:4px;">Rate</label>
                    <input type="number" id="vsRate" placeholder="0.00"
                           style="width:100%;padding:8px;border:1.5px solid #E2E8F0;border-radius:8px;
                                  font-size:.82rem;outline:none;box-sizing:border-box;" class="vs-calc">
                </div>
                <div>
                    <label style="display:block;font-size:.72rem;color:#64748B;margin-bottom:4px;">Amount</label>
                    <input type="number" id="vsAmount" value="${totalPurchase || ''}" placeholder="0.00"
                           style="width:100%;padding:8px;border:1.5px solid #E2E8F0;border-radius:8px;
                                  font-size:.82rem;outline:none;box-sizing:border-box;" class="vs-calc">
                </div>
            </div>

            <!-- Buttons -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:4px;">
                <button onclick="window._vsSkipAndCreate()"
                        style="padding:11px;background:#F1F5F9;border:none;border-radius:10px;
                               cursor:pointer;font-size:.85rem;font-weight:700;color:#475569;">
                    Skip Payment
                </button>
                <button onclick="window._vsConfirmWithPayment()"
                        style="padding:11px;background:#4F46E5;border:none;border-radius:10px;
                               cursor:pointer;font-size:.9rem;font-weight:800;color:#fff;">
                    ✓ Create & Pay
                </button>
            </div>
        </div>
    </div>`;

    document.body.appendChild(modal);
    _vsSetupCalc();
    _vsLoadVendorsAccounts();

    // Close on backdrop click
    modal.addEventListener('click', e => { if (e.target === modal) modal.remove(); });
};

/* ── Vendor / Account loaders ────────────────────────────────── */
let _vsVendors = [], _vsAccounts = [];
async function _vsLoadVendorsAccounts() {
    try {
        const [vr, ar] = await Promise.all([
            fetch(window._vs.cfg.api.allVendors),
            fetch(window._vs.cfg.api.allAccounts),
        ]);
        _vsVendors  = (await vr.json()).vendors  ?? [];
        _vsAccounts = (await ar.json()).accounts ?? [];
    } catch (e) { /* silently skip */ }
}

window._vsFilterVendors  = q => _vsFilterDropdown('vendor',  q);
window._vsFilterAccounts = q => _vsFilterDropdown('account', q);

function _vsFilterDropdown(kind, q) {
    const isV  = kind === 'vendor';
    const dd   = document.getElementById(isV ? 'vsVendorDrop' : 'vsAccountDrop');
    const src  = isV ? _vsVendors : _vsAccounts;
    const nk   = isV ? 'name' : 'acc_name';
    const v    = (q || '').toLowerCase().trim();
    const list = v ? src.filter(x => (x[nk] || '').toLowerCase().includes(v)) : src.slice(0, 15);

    if (!list.length) {
        dd.innerHTML = `<li style="padding:12px;text-align:center;color:#94A3B8;font-size:.8rem;">Not found</li>`;
        dd.style.display = 'block'; return;
    }
    dd.innerHTML = list.map(x =>
        `<li onclick="window._vsSelectItem('${kind}','${_vsEsc(x.sys_id)}','${_vsEsc((x[nk]||x.sys_id).replace(/'/g,"\\'"))}')"
             style="padding:9px 12px;cursor:pointer;font-size:.85rem;color:#0F172A;"
             onmouseover="this.style.background='#EEF2FF'" onmouseout="this.style.background=''">
            ${_vsEsc(x[nk] ?? x.sys_id)}
         </li>`
    ).join('');
    dd.style.display = 'block';
}

window._vsSelectItem = function (kind, sysId, name) {
    const isV = kind === 'vendor';
    document.getElementById(isV ? 'vsVendorSearch' : 'vsAccountSearch').value = name;
    document.getElementById(isV ? 'vsVendorId'     : 'vsAccountId').value     = sysId;
    document.getElementById(isV ? 'vsVendorDrop'   : 'vsAccountDrop').style.display = 'none';
};

document.addEventListener('click', e => {
    ['vsVendorWrap', 'vsAccountWrap'].forEach(wId => {
        const w = document.getElementById(wId);
        if (w && !w.contains(e.target)) {
            const ddId = wId === 'vsVendorWrap' ? 'vsVendorDrop' : 'vsAccountDrop';
            const dd = document.getElementById(ddId);
            if (dd) dd.style.display = 'none';
        }
    });
});

/* ── Transaction mode toggle ─────────────────────────────────── */
window._vsToggleTxnMode = function () {
    const isRT = document.querySelector('input[name="vs_txn_mode"]:checked')?.value === 'realtime';
    const sec  = document.getElementById('vsAccountSection');
    const rtLbl= document.getElementById('vsModeRTLbl');
    const nrLbl= document.getElementById('vsModeNRLbl');
    if (sec)   sec.style.display = isRT ? '' : 'none';
    if (rtLbl) rtLbl.style.cssText = rtLbl.style.cssText.replace(/border:[^;]+;|background:[^;]+;|color:[^;]+;/g,'')
        + (isRT
            ? 'border:2px solid #10B981;background:#ECFDF5;color:#065F46;'
            : 'border:2px solid #E2E8F0;background:#fff;color:#64748B;');
    if (nrLbl) nrLbl.style.cssText = nrLbl.style.cssText.replace(/border:[^;]+;|background:[^;]+;|color:[^;]+;/g,'')
        + (!isRT
            ? 'border:2px solid #10B981;background:#ECFDF5;color:#065F46;'
            : 'border:2px solid #E2E8F0;background:#fff;color:#64748B;');
};

/* ── QTY/Rate/Amount calculator ──────────────────────────────── */
function _vsSetupCalc() {
    const q = document.getElementById('vsQty');
    const r = document.getElementById('vsRate');
    const a = document.getElementById('vsAmount');
    if (!q || !r || !a) return;
    let last = null;
    const calc = () => {
        const qv = parseFloat(q.value) || null;
        const rv = parseFloat(r.value) || null;
        const av = parseFloat(a.value) || null;
        if (last !== 'amount' && qv && rv) a.value = (qv * rv).toFixed(2);
        else if (last !== 'rate' && qv && av) r.value = (av / qv).toFixed(2);
        else if (last !== 'qty' && rv && av)  q.value = (av / rv).toFixed(2);
    };
    q.addEventListener('input', () => { last = 'qty';    calc(); });
    r.addEventListener('input', () => { last = 'rate';   calc(); });
    a.addEventListener('input', () => { last = 'amount'; calc(); });
}

/* ── Skip (create task without payment) ─────────────────────── */
window._vsSkipAndCreate = function () {
    document.getElementById('vsCreateTaskModal')?.remove();
    _vsDoCreateTask(null, false);
};

/* ── Confirm with payment ────────────────────────────────────── */
window._vsConfirmWithPayment = async function () {
    const txnMode  = document.querySelector('input[name="vs_txn_mode"]:checked')?.value;
    const amount   = parseFloat(document.getElementById('vsAmount')?.value);
    const qty      = parseFloat(document.getElementById('vsQty')?.value)   || null;
    const rate     = parseFloat(document.getElementById('vsRate')?.value)   || null;
    const purpose  = document.getElementById('vsPurpose')?.value?.trim();
    const vendorId = document.getElementById('vsVendorId')?.value;
    const accountId= document.getElementById('vsAccountId')?.value;
    const includeClient = document.getElementById('vsIncludeClientEntry')?.checked ?? true;

    if (amount && amount > 0) {
        if (!purpose)                               { _vsToast('error', 'Purpose দিন'); return; }
        if (!vendorId)                              { _vsToast('error', 'Vendor সিলেক্ট করুন'); return; }
        if (txnMode === 'realtime' && !accountId)   { _vsToast('error', 'Account সিলেক্ট করুন'); return; }
    }

    const payment = (amount && amount > 0)
        ? { amount, purpose, txnMode, vendorId,
            accountId: txnMode === 'realtime' ? accountId : null,
            qtyRate: (qty || rate) ? JSON.stringify({ qty: qty || 0, rate: rate || 0 }) : null }
        : null;

    document.getElementById('vsCreateTaskModal')?.remove();
    await _vsDoCreateTask(payment, includeClient);
};

/* ── Core: POST create_visa_task + financial entries ─────────── */
async function _vsDoCreateTask(payment, includeClient) {
    if (!confirm('Task তৈরি করবেন?')) return;

    try {
        const json = await window._vsApi({ action: 'create_visa_task' });

        if (json.status !== 'success') {
            _vsToast('error', json.message ?? 'Task creation failed');
            return;
        }

        const taskSysId = json.task_sys_id;
        const workSysId = window._vs.cfg.workSysId;
        _vsToast('success', taskSysId ? `Task created: ${taskSysId}` : 'Task created!');

        // ── Vendor payment entry ──
        if (payment && taskSysId) {
            try {
                await fetch(window._vs.cfg.api.saveFinancial, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        type:             'credit',
                        vendor_id:        payment.vendorId,
                        amount:           payment.amount,
                        qty_rate:         payment.qtyRate,
                        purpose:          payment.purpose,
                        transaction_mode: payment.txnMode,
                        account_id:       payment.accountId,
                        work_id:          workSysId,
                        task_id:          taskSysId,
                        date:             new Date().toISOString().slice(0, 10),
                    }),
                });
            } catch (e) { console.error('[visa] vendor payment:', e); }
        }

        // ── Client sale entry ──
        const clientSysId   = window._vs.cfg.clientSysId ?? null;
        const totalClient   = window._vsTaskClientTotal ?? 0;
        const travCount     = window._vsTaskTravCount   ?? 0;
        const clientPrice   = travCount > 0 ? totalClient / travCount : 0;
        const meta          = window._vs.data?.meta_data ?? {};
        const visaTitle     = meta.master_visa_title ?? (meta.country_name ?? '') + ' Visa';

        if (includeClient && taskSysId && totalClient > 0 && clientSysId) {
            try {
                await fetch(window._vs.cfg.api.saveFinancial, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        type:     'debit',
                        client_id: clientSysId,
                        amount:   totalClient,
                        qty_rate: JSON.stringify({ qty: travCount, rate: clientPrice }),
                        purpose:  `Visa — ${visaTitle}`,
                        work_id:  workSysId,
                        task_id:  taskSysId,
                        date:     new Date().toISOString().slice(0, 10),
                    }),
                });
            } catch (e) { console.error('[visa] client sale entry:', e); }
        } else if (includeClient && !clientSysId) {
            _vsToast('error', 'Client ID missing — client entry skipped');
        }

        // Reload and re-render
        await window._vsReload();
        window._renderVsAppStatus();
        if (typeof window.reloadConfirmedTasks === 'function') window.reloadConfirmedTasks();

    } catch (e) {
        _vsToast('error', 'Network error');
        console.error('[visa] create task:', e);
    }
}

/* ── Toast helper ────────────────────────────────────────────── */
function _vsToast(type, msg) {
    if (typeof window.vsT === 'function') { window.vsT(type, msg); return; }
    // Fallback: simple inline toast
    const t = document.createElement('div');
    t.style.cssText = `position:fixed;bottom:24px;right:24px;z-index:99999;padding:10px 18px;
                        border-radius:10px;font-size:.85rem;font-weight:700;
                        background:${type==='success'?'#22C55E':'#EF4444'};color:#fff;
                        box-shadow:0 4px 16px rgba(0,0,0,.2);`;
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3000);
}