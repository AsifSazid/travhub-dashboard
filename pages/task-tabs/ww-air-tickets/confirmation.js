/**
 * FILE PATH: /pages/task-tabs/ww-air-tickets/confirmation.js
 * Tab: Confirmation
 */

// ═══════════════════════════════════════════════════════════════
// TAB 4 — CONFIRMATION
// ═══════════════════════════════════════════════════════════════
window._renderConfirmation = function _renderConfirmation() {
    const panel         = document.getElementById('at-panel-confirmation');
    const confirmations = window._at.data?.at_confirmations ?? [];
    const bookings      = window._at.data?.at_bookings      ?? [];

    const activeConfIds = new Set(confirmations.filter(c=>c.status!=='failed'&&c.status!=='cancelled').map(c=>c.booking_sys_id));
    const availableB    = bookings.filter(b => !activeConfIds.has(b.sys_id) && b.status !== 'cancelled');

    panel.innerHTML = `
    <div class="flex gap-4">
        <!-- LEFT: confirmation list -->
        <div style="width:220px;flex-shrink:0;">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Confirmations</span>
                ${availableB.length ? `<button onclick="atOpenAddConf()"
                    class="flex items-center gap-1 px-2.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold transition">
                    <i class="fas fa-plus text-xs"></i>Add
                </button>` : ''}
            </div>
            <div id="at-conf-list" class="space-y-1.5">
                ${confirmations.length
                    ? confirmations.map(c => _confCard(c, bookings)).join('')
                    : `<div class="text-center py-6">
                        <i class="fas fa-check-circle text-3xl text-gray-200 mb-2 block"></i>
                        <p class="text-xs text-gray-300">No confirmations yet.</p>
                        <p class="text-[11px] text-gray-300 mt-1">Move a booking from Booking tab.</p>
                       </div>`}
            </div>
        </div>

        <!-- RIGHT: confirmation detail -->
        <div class="flex-1 min-w-0" id="at-conf-detail">
            <div class="flex items-center justify-center h-40 text-gray-300 text-sm">
                <div class="text-center"><i class="fas fa-check-circle text-3xl mb-2 block opacity-30"></i>Select a confirmation</div>
            </div>
        </div>
    </div>

    <!-- Add Confirmation Modal -->
    <div id="at-add-conf-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.45);backdrop-filter:blur(3px);">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
            <div class="flex items-center justify-between p-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-800 text-sm"><i class="fas fa-check-circle mr-2 text-indigo-500"></i>Add to Confirmation</h3>
                <button onclick="document.getElementById('at-add-conf-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-4">
                <label class="text-xs font-bold text-gray-400 uppercase block mb-2">Select Booking</label>
                <select id="at-add-conf-select" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-400 mb-4">
                    ${availableB.map(b=>`<option value="${_e(b.sys_id)}">${_e(b.sys_id)} — ${_e(b.airline??'')} ${b.pnr?'PNR:'+b.pnr:''} ৳${_fmt(b.total_payable)} [${(b.type??'gds').toUpperCase()}]</option>`).join('')}
                </select>
                <button onclick="atAddConfirmation()"
                    class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold transition">
                    <i class="fas fa-check mr-1.5"></i>Add to Confirmation
                </button>
            </div>
        </div>
    </div>`;

    _gdsInjectPanel(panel, '4');
}

window._confCard = function _confCard(c, bookings) {
    const b       = bookings.find(x => x.sys_id === c.booking_sys_id);
    const sMap    = { pending:'at-pill-tentative', confirmed:'at-pill-confirmed', failed:'at-pill-failed', cancelled:'at-pill-cancelled' };
    const sDot    = { pending:'bg-yellow-400', confirmed:'bg-green-500', failed:'bg-red-400', cancelled:'bg-gray-400' };
    const typePill = b?.type === 'soto'
        ? '<span class="at-pill at-pill-sent" style="font-size:.6rem">SOTO</span>'
        : '<span class="at-pill at-pill-draft" style="font-size:.6rem">GDS</span>';
    return `<div class="at-q-card ${c.sys_id===window._at.activeConfId?'active':''}" onclick="atSelectConf('${_e(c.sys_id)}')">
        <div class="flex items-center justify-between mb-1">
            <div class="flex items-center gap-1.5">
                <div class="w-2 h-2 rounded-full ${sDot[c.status??'pending']??'bg-gray-400'} flex-shrink-0"></div>
                <span class="font-mono text-[10px] text-indigo-500">${_e(c.sys_id)}</span>
            </div>
            ${typePill}
        </div>
        <div class="text-xs font-semibold text-gray-700">${_e(b?.airline??'—')}</div>
        ${b?.pnr ? `<div class="text-[11px] text-gray-400">PNR: ${_e(b.pnr)}</div>` : ''}
        <div class="flex items-center justify-between mt-1">
            <span class="text-[11px] font-bold text-green-600">৳ ${_fmt(b?.total_payable??0)}</span>
            <span class="at-pill ${sMap[c.status??'pending']}" style="font-size:.6rem">${c.status??'pending'}</span>
        </div>
        <div class="text-[10px] text-gray-400 mt-0.5">From: ${_e(c.booking_sys_id)}</div>
    </div>`;
}

window._at.activeConfId = null;

window.atOpenAddConf = function() {
    document.getElementById('at-add-conf-modal')?.classList.remove('hidden');
};

window.atAddConfirmation = async function() {
    const bSysId = document.getElementById('at-add-conf-select')?.value;
    if (!bSysId) return;
    document.getElementById('at-add-conf-modal')?.classList.add('hidden');
    try {
        const json = await window._atApi({ action:'add_to_confirmation', booking_sys_id: bSysId });
        if (json.status === 'success') { atT('success','Added to Confirmation!'); await window._atReload(); _renderConfirmation(); }
        else atT('error', json.message);
    } catch { atT('error','Network error'); }
};

window.atSelectConf = function(confId) {
    window._at.activeConfId = confId;
    const confirmations = window._at.data?.at_confirmations ?? [];
    const bookings      = window._at.data?.at_bookings      ?? [];
    const c = confirmations.find(x => x.sys_id === confId);
    if (!c) return;
    document.querySelectorAll('#at-conf-list .at-q-card').forEach(x => x.classList.remove('active'));
    event?.currentTarget?.classList.add('active');
    const b = bookings.find(x => x.sys_id === c.booking_sys_id);
    _renderConfDetail(c, b);
    _loadConfTravelers(c.sys_id);
};

window._renderConfDetail = function _renderConfDetail(c, b) {
    const detail = document.getElementById('at-conf-detail');
    if (!detail) return;
    const sMap = { pending:'at-pill-tentative', confirmed:'at-pill-confirmed', failed:'at-pill-failed', cancelled:'at-pill-cancelled' };
    const type = b?.type ?? 'gds';

    const hasTask = (window._at.data?.confirmed_tasks ?? []).some(t => t.confirmation_sys_id === c.sys_id);
    const isPending   = (c.status ?? 'pending') === 'pending';
    const isConfirmed = (c.status ?? 'pending') === 'confirmed';

    detail.innerHTML = `
    <div class="flex items-center justify-between mb-4">
        <div>
            <div class="flex items-center gap-2">
                <h3 class="font-bold text-gray-800 text-sm">Confirmation</h3>
                <span class="at-pill ${type==='soto'?'at-pill-sent':'at-pill-draft'}" style="font-size:.6rem">${type.toUpperCase()}</span>
            </div>
            <div class="text-[11px] text-gray-400 mt-0.5">
                Conf: <span class="font-mono text-indigo-500">${_e(c.sys_id)}</span>
                · Booking: <span class="font-mono text-green-600">${_e(c.booking_sys_id)}</span>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="at-pill ${sMap[c.status??'pending']}" style="font-size:.6rem">${c.status??'pending'}</span>
            ${isPending ? `
                <button onclick="atUpdateConfStatus('${_e(c.sys_id)}','confirmed')"
                    class="px-3 py-1.5 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-semibold rounded-lg transition">
                    ✓ Confirm
                </button>
                <button onclick="atConfirmAndCreateTask('${_e(c.sys_id)}')"
                    class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg transition whitespace-nowrap">
                    ✓ Confirm & Create Task
                </button>
            ` : hasTask ? `
                <span class="text-xs text-emerald-600 font-semibold bg-emerald-50 border border-emerald-200 px-2 py-1 rounded-lg whitespace-nowrap">
                    <i class="fas fa-check-double text-[9px] mr-1"></i>Confirmed · Task Created
                </span>
            ` : `
                <button onclick="atUpdateConfStatus('${_e(c.sys_id)}','pending')"
                    class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs font-semibold rounded-lg transition whitespace-nowrap">
                    ↩ Revert to Pending
                </button>
                <button onclick="atConfirmAndCreateTask('${_e(c.sys_id)}')"
                    class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg transition whitespace-nowrap">
                    + Create Task
                </button>
            `}
        </div>
    </div>

    ${b ? `<div class="bg-green-50 border border-green-100 rounded-xl p-3 mb-4 grid grid-cols-2 gap-3">
        <div><span class="text-[10px] text-gray-400 uppercase">Airline</span><div class="font-semibold text-gray-700 text-sm">${_e(b.airline??'—')}</div></div>
        <div><span class="text-[10px] text-gray-400 uppercase">PNR</span><div class="font-semibold text-gray-700 text-sm">${_e(b.pnr??'—')}</div></div>
        <div><span class="text-[10px] text-gray-400 uppercase">Total</span><div class="font-bold text-green-600 text-sm">৳ ${_fmt(b.total_payable)}</div></div>
        <div><span class="text-[10px] text-gray-400 uppercase">Added At</span><div class="text-gray-500 text-xs">${_e(c.added_at??'—')}</div></div>
    </div>` : ''}

    <div class="mb-3">
        <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Ticket Numbers</label>
        <input id="at-conf-tickets-${_e(c.sys_id)}" value="${_e((c.ticket_nos??[]).join(', '))}"
            placeholder="123-4567890, 123-4567891"
            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-400">
    </div>

    <!-- Travelers table -->
    <div class="mb-4">
        <label class="text-xs font-bold text-gray-500 uppercase block mb-2">Travelers</label>
        <div id="at-conf-travelers-${_e(c.sys_id)}" class="text-xs text-gray-300 text-center py-2">
            <i class="fas fa-spinner fa-spin"></i>
        </div>
    </div>

    <div class="mb-4">
        <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Note</label>
        <textarea id="at-conf-note-${_e(c.sys_id)}" rows="2"
            class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm resize-none focus:outline-none focus:border-indigo-400">${_e(c.note??'')}</textarea>
    </div>

    <div class="mb-4">
        <label class="text-xs font-bold text-gray-500 uppercase block mb-2">Confirmation Files</label>

        <!-- Drop zone + paste area -->
        <div class="flex gap-2 mb-2">
            <!-- Drop / Browse -->
            <div id="at-conf-drop-${_e(c.sys_id)}"
                class="flex-1 border-2 border-dashed border-gray-200 rounded-xl p-4 text-center cursor-pointer hover:border-indigo-300 hover:bg-indigo-50 transition flex flex-col items-center justify-center gap-1"
                ondragover="event.preventDefault();this.classList.add('border-indigo-400','bg-indigo-50')"
                ondragleave="this.classList.remove('border-indigo-400','bg-indigo-50')"
                ondrop="event.preventDefault();this.classList.remove('border-indigo-400','bg-indigo-50');atConfDrop(event,'${_e(c.sys_id)}')"
                onclick="document.getElementById('at-conf-file-${_e(c.sys_id)}').click()">
                <input type="file" id="at-conf-file-${_e(c.sys_id)}" class="hidden" multiple accept=".pdf,.jpg,.jpeg,.png,.webp"
                    onchange="atConfBrowse(this,'${_e(c.sys_id)}')">
                <i class="fas fa-cloud-upload-alt text-2xl text-gray-300"></i>
                <span class="text-xs text-gray-400 font-medium">Drop or Browse</span>
            </div>
            <!-- Paste -->
            <div class="flex-1 border-2 border-dashed border-gray-200 rounded-xl p-3 relative"
                style="min-height:80px;">
                <textarea id="at-conf-paste-${_e(c.sys_id)}"
                    class="w-full h-full text-xs text-gray-400 resize-none outline-none bg-transparent"
                    placeholder="Paste image here (Ctrl+V)"
                    onpaste="atConfPaste(event,'${_e(c.sys_id)}')"></textarea>
            </div>
        </div>

        <!-- Pending preview chips -->
        <div id="at-conf-pending-${_e(c.sys_id)}" class="flex flex-wrap gap-1.5 mb-2"></div>

        <!-- Upload progress -->
        <div id="at-conf-uprog-${_e(c.sys_id)}" class="hidden text-xs text-indigo-600 mb-1">
            <i class="fas fa-spinner fa-spin mr-1"></i><span id="at-conf-uprog-text-${_e(c.sys_id)}">Uploading…</span>
        </div>

        <!-- Upload button — only shows when pending files exist -->
        <button id="at-conf-upload-btn-${_e(c.sys_id)}" onclick="atConfUploadPending('${_e(c.sys_id)}')"
            class="hidden w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold transition mb-3">
            <i class="fas fa-upload mr-1.5"></i>Upload Files
        </button>

        <!-- Existing files -->
        ${(c.files_json??[]).length ? `<div class="space-y-2">
            ${(c.files_json).map((f,fi) => {
                const isImg = /\.(jpg|jpeg|png|webp)$/i.test(f.name??f.file_name??'');
                const fileUrl = f.smb_token ?? '';
                return `<div class="border border-gray-100 rounded-xl overflow-hidden">
                    ${isImg ? `<img src="${_e(fileUrl)}" class="w-full max-h-40 object-contain bg-gray-50 cursor-pointer" onclick="atOpenImage('${_e(fileUrl)}')">` : ''}
                    <div class="flex items-center gap-2 px-3 py-2">
                        <i class="fas fa-${isImg?'image':'file-pdf'} text-gray-400 text-xs flex-shrink-0"></i>
                        <span class="text-xs text-gray-600 flex-1 truncate" title="${_e(f.name??f.file_name??'')}">${_e(f.name??f.file_name??'')}</span>
                        ${f.extracted_data ? `<button onclick="atShowExtracted('${fi}','${_e(c.sys_id)}')" class="text-indigo-400 hover:text-indigo-600 text-xs px-1" title="View extracted data"><i class="fas fa-eye"></i></button>` : ''}
                        <a href="${_e(fileUrl)}" target="_blank" class="text-gray-400 hover:text-indigo-500 text-xs px-1" title="Download"><i class="fas fa-download"></i></a>
                        <button onclick="atDeleteConfFile('${_e(c.sys_id)}',${fi},'${_e(f.name??f.file_name??'')}')" class="text-gray-300 hover:text-red-500 text-xs px-1" title="Delete"><i class="fas fa-trash"></i></button>
                    </div>
                    ${f.extracted_data ? `<div id="at-extracted-${fi}-${_e(c.sys_id)}" class="hidden px-3 pb-2">
                        <pre class="bg-gray-50 rounded-lg p-2 text-[10px] text-gray-600 max-h-32 overflow-auto font-mono whitespace-pre-wrap">${_e(JSON.stringify(f.extracted_data, null, 2))}</pre>
                    </div>` : ''}
                </div>`;
            }).join('')}
            ${(c.files_json??[]).length > 0 ? `
            <div class="flex gap-2 mt-2">
                <button onclick="atViewConfFiles('${_e(c.sys_id)}')"
                    class="flex-1 py-1.5 text-xs font-semibold bg-indigo-50 hover:bg-indigo-100 text-indigo-600 border border-indigo-200 rounded-lg transition">
                    <i class="fas fa-file-alt mr-1"></i>View All Formatted
                </button>
            </div>` : ''}
        </div>` : '<p class="text-[11px] text-gray-300 mt-1 text-center">No files uploaded yet</p>'}

    <div class="flex gap-2 mt-3">
        <button onclick="atSaveConfDetails('${_e(c.sys_id)}')"
            class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition">
            <i class="fas fa-save mr-1.5"></i>Save Details
        </button>
        <a href="../task-air.php?task_id=${encodeURIComponent(window._at.cfg.workSysId)}" target="_blank"
            class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold transition flex items-center gap-1.5">
            <i class="fas fa-file-download text-xs"></i>
        </a>
        ${(c.status === 'failed' || c.status === 'cancelled') && !hasTask ? `
        <button onclick="atRemoveConf('${_e(c.sys_id)}')"
            class="px-3 py-2.5 bg-red-50 hover:bg-red-100 text-red-500 border border-red-200 rounded-lg text-sm transition">
            <i class="fas fa-trash-alt text-xs"></i>
        </button>` : ''}
    </div>`;
}

window.atShowExtracted = function(fi, confSysId) {
    const url = `task-air.php?task_sys_id=${encodeURIComponent(window._at.cfg.workSysId)}&conf_sys_id=${encodeURIComponent(confSysId)}&file_index=${fi}`;
    window.open(url, '_blank');
};

window.atOpenImage = function(url) {
    const ov = document.createElement('div');
    ov.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:99999;display:flex;align-items:center;justify-content:center;cursor:zoom-out;';
    ov.innerHTML = `<img src="${url}" style="max-width:90vw;max-height:90vh;border-radius:8px;">`;
    ov.onclick = () => ov.remove();
    document.body.appendChild(ov);
};

// ── Confirmation travelers table ──────────────────────────────
window._loadConfTravelers = async function _loadConfTravelers(confSysId) {
    const el = document.getElementById(`at-conf-travelers-${confSysId}`);
    if (!el || !window._at.cfg.workSysId) return;
    try {
        const res  = await fetch(`${window._at.cfg.api.workTravelers}?action=list&work_sys_id=${encodeURIComponent(window._at.cfg.workSysId)}`);
        const json = await res.json();
        const travelers = json.status === 'success' ? (json.data ?? []) : [];

        if (!travelers.length) {
            el.innerHTML = '<p class="text-gray-300 text-xs text-center py-1">No travelers linked</p>';
            return;
        }

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
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    ${travelers.map(t => {
                        const pi   = t.passport_info ?? '[]';
                        const pArr = Array.isArray(pi) ? pi : (typeof pi === 'string' ? JSON.parse(pi || '[]') : []);
                        const bio  = pArr.find?.(p => p.page_type === 'bio_page')?.bio_info ?? {};
                        const name    = t.name ?? '—';
                        const given   = bio.given_names ?? bio.given_name ?? '—';
                        const surname = bio.surname ?? '—';
                        const ppNo    = bio.passport_number ?? bio.passport_no ?? t.passport_no ?? '—';
                        const expiry  = bio.date_of_expiry ?? '—';
                        const dob     = bio.date_of_birth ?? t.date_of_birth ?? '—';
                        return `<tr class="hover:bg-gray-50 text-gray-800">
                            <td class="px-2 py-1.5 cursor-pointer hover:bg-indigo-50 font-medium" onclick="atBCopyCell('${_e(String(name))}')">${_e(String(name))}</td>
                            <td class="px-2 py-1.5 cursor-pointer hover:bg-indigo-50 text-center" onclick="atBCopyCell('${_e(String(given))}')">${_e(String(given))}</td>
                            <td class="px-2 py-1.5 cursor-pointer hover:bg-indigo-50 text-center" onclick="atBCopyCell('${_e(String(surname))}')">${_e(String(surname))}</td>
                            <td class="px-2 py-1.5 cursor-pointer hover:bg-indigo-50 text-center font-mono" onclick="atBCopyCell('${_e(String(ppNo))}')">${_e(String(ppNo))}</td>
                            <td class="px-2 py-1.5 cursor-pointer hover:bg-indigo-50 text-center" onclick="atBCopyCell('${_e(String(expiry))}')">${_e(String(expiry))}</td>
                            <td class="px-2 py-1.5 cursor-pointer hover:bg-indigo-50 text-center" onclick="atBCopyCell('${_e(String(dob))}')">${_e(String(dob))}</td>
                        </tr>`;
                    }).join('')}
                </tbody>
            </table>
        </div>`;
    } catch(e) {
        el.innerHTML = '<p class="text-red-300 text-xs text-center">Load failed</p>';
    }
}

// ── Conf file pending state ───────────────────────────────────
window._at.confPending = {}; // confSysId → File[]

window._confAddFiles = function _confAddFiles(confSysId, files) {
    if (!window._at.confPending[confSysId]) window._at.confPending[confSysId] = [];
    for (const f of files) window._at.confPending[confSysId].push(f);
    _confRenderPending(confSysId);
}

window._confRenderPending = function _confRenderPending(confSysId) {
    const wrap = document.getElementById(`at-conf-pending-${confSysId}`);
    const btn  = document.getElementById(`at-conf-upload-btn-${confSysId}`);
    const files = window._at.confPending[confSysId] ?? [];
    if (!wrap) return;
    if (!files.length) {
        wrap.innerHTML = '';
        btn?.classList.add('hidden');
        return;
    }
    btn?.classList.remove('hidden');
    wrap.innerHTML = files.map((f, i) => {
        const icon = f.type.startsWith('image/') ? 'fa-image text-blue-400'
                   : f.type === 'application/pdf' ? 'fa-file-pdf text-red-400'
                   : 'fa-paperclip text-gray-400';
        const kb = (f.size / 1024).toFixed(0);
        return `<span class="inline-flex items-center gap-1 bg-indigo-50 text-indigo-700 rounded-full px-2 py-0.5 text-[11px] font-medium">
            <i class="fas ${icon} text-[10px]"></i>
            <span class="max-w-[100px] truncate" title="${_e(f.name)}">${_e(f.name)}</span>
            <span class="text-indigo-400">${kb}k</span>
            <button onclick="_confRemovePending('${confSysId}',${i})"
                class="text-red-400 hover:text-red-600 ml-0.5"><i class="fas fa-times text-[9px]"></i></button>
        </span>`;
    }).join('');
}

window._confRemovePending = function(confSysId, idx) {
    if (window._at.confPending[confSysId]) window._at.confPending[confSysId].splice(idx, 1);
    _confRenderPending(confSysId);
};

window.atConfBrowse = function(input, confSysId) {
    if (!input.files.length) return;
    _confAddFiles(confSysId, Array.from(input.files));
    input.value = '';
};

window.atConfDrop = function(e, confSysId) {
    if (!e.dataTransfer.files.length) return;
    _confAddFiles(confSysId, Array.from(e.dataTransfer.files));
};

window.atConfPaste = function(e, confSysId) {
    const items = e.clipboardData?.items ?? [];
    for (const item of items) {
        if (item.type.startsWith('image/')) {
            e.preventDefault();
            const file = item.getAsFile();
            _confAddFiles(confSysId, [file]);
            break;
        }
    }
};

window.atConfUploadPending = async function(confSysId) {
    const files = window._at.confPending[confSysId] ?? [];
    if (!files.length) return;

    const prog     = document.getElementById(`at-conf-uprog-${confSysId}`);
    const progText = document.getElementById(`at-conf-uprog-text-${confSysId}`);
    const btn      = document.getElementById(`at-conf-upload-btn-${confSysId}`);
    if (prog) prog.classList.remove('hidden');
    if (btn)  btn.disabled = true;

    let uploaded = 0;
    for (const file of files) {
        if (progText) progText.textContent = `Uploading ${file.name}… (${++uploaded}/${files.length})`;
        const fd = new FormData();
        fd.append('action', 'upload_conf_file');
        fd.append('conf_sys_id', confSysId);
        fd.append('work_sys_id', window._at.cfg.workSysId);
        fd.append('file', file);
        try {
            const res  = await fetch(window._at.cfg.api.airTickets, {method:'POST', body:fd});
            const json = await res.json();
            if (json.status !== 'success') {
                atT('error', `${file.name}: ${json.message ?? 'Failed'}`);
            } else if (json.ai_error) {
                console.warn('AI extract failed:', json.ai_error);
            }
        } catch { atT('error', `${file.name}: Upload failed`); }
    }

    window._at.confPending[confSysId] = [];
    if (prog) prog.classList.add('hidden');
    if (btn)  btn.disabled = false;
    atT('success', `${files.length} file${files.length>1?'s':''} uploaded`);
    await window._atReload();
    // conf re-render
    const updatedConf = (window._at.data?.at_confirmations ?? []).find(c => c.sys_id === confSysId);
    if (updatedConf) {
        const b = (window._at.data?.at_bookings ?? []).find(bk => bk.sys_id === updatedConf.booking_sys_id);
        _renderConfDetail(updatedConf, b ?? null);
        _loadConfTravelers(confSysId);
    }
};

// ── Drag & drop (old handler kept for compat) ─────────────────
window.atConfFileDrop = window.atConfDrop;

// ── Delete conf file ──────────────────────────────────────────
window.atDeleteConfFile = async function(confSysId, fileIndex, fileName) {
    if (!confirm(`Delete "${fileName}"?`)) return;
    try {
        const json = await window._atApi({ action:'delete_conf_file', conf_sys_id:confSysId, file_index:fileIndex });
        if (json.status === 'success') {
            atT('success', 'File deleted');
            await window._atReload();
            const c = (window._at.data?.at_confirmations ?? []).find(x => x.sys_id === confSysId);
            const b = (window._at.data?.at_bookings ?? []).find(bk => bk.sys_id === c?.booking_sys_id);
            if (c) { _renderConfDetail(c, b ?? null); _loadConfTravelers(confSysId); }
        } else { atT('error', json.message ?? 'Delete failed'); }
    } catch { atT('error', 'Network error'); }
};

// ── View conf files formatted (task-air.php style) ────────────
window.atViewConfFiles = function(confSysId) {
    const c = (window._at.data?.at_confirmations ?? []).find(x => x.sys_id === confSysId);
    if (!c) return;
    const files = c.files_json ?? [];
    if (!files.length) return;
    // নতুন window এ open করি
    const url = `task-air.php?task_sys_id=${encodeURIComponent(window._at.cfg.workSysId)}&conf_sys_id=${encodeURIComponent(confSysId)}`;
    window.open(url, '_blank');
};

window.atConfirmFromBooking = async function(bSysId) {
    if (!confirm('Send this booking to Confirmation?')) return;
    try {
        const json = await window._atApi({ action:'add_to_confirmation', booking_sys_id: bSysId });
        if (json.status === 'success') {
            atT('success', 'Sent to Confirmation!');
            await window._atReload();
            const btn = document.querySelector('.at-tab[data-tab="confirmation"]');
            if (btn) window._atSwitchTab('confirmation', btn);
        } else atT('error', json.message ?? 'Failed');
    } catch { atT('error', 'Network error'); }
};

window.atUpdateConfStatus = async function(confId, status) {
    if (status === 'pending') {
        const hasTask = (window._at.data?.confirmed_tasks ?? []).some(t => t.confirmation_sys_id === confId);
        if (hasTask) {
            atT('error', 'Cannot revert — a task has already been created');
            return;
        }
    }
    try {
        const json = await window._atApi({ action:'update_confirmation_status', conf_sys_id: confId, status });
        if (json.status==='success') { atT('success','Status updated'); await window._atReload(); _renderConfirmation(); }
        else atT('error', json.message);
    } catch { atT('error','Network error'); }
};

window.atConfirmAndCreateTask = async function(confId) {
    if (!confirm('Confirm this booking and create a task?')) return;
    try {
        const json = await window._atApi({ action:'confirm_and_create_task', conf_sys_id: confId });
        if (json.status === 'success') {
            atT('success', json.auto_task_id ? `Confirmed! Task: ${json.auto_task_id}` : 'Confirmed!');
            await window._atReload();
            _renderConfirmation();
            // Reload confirmed tasks in show-works.php
            if (typeof window.reloadConfirmedTasks === 'function') window.reloadConfirmedTasks();
        } else atT('error', json.message ?? 'Failed');
    } catch { atT('error','Network error'); }
};

window.atSaveConfDetails = async function(confId) {
    const tickets = (document.getElementById(`at-conf-tickets-${confId}`)?.value??'').split(',').map(t=>t.trim()).filter(Boolean);
    const note    = document.getElementById(`at-conf-note-${confId}`)?.value ?? '';
    const conf    = (window._at.data?.at_confirmations??[]).find(c=>c.sys_id===confId);
    try {
        const json = await window._atApi({ action:'update_confirmation', conf_sys_id:confId, ticket_nos:tickets, note, files_json:conf?.files_json??[] });
        if (json.status==='success') { atT('success','Saved!'); await window._atReload(); _renderConfirmation(); }
        else atT('error', json.message);
    } catch { atT('error','Network error'); }
};

window.atUploadConfFile = async function(input, confId) {
    if (!input.files.length) return;
    const prog = document.getElementById(`at-conf-uprog-${confId}`);
    prog?.classList.remove('hidden');
    const conf = (window._at.data?.at_confirmations??[]).find(c=>c.sys_id===confId);
    for (const file of input.files) {
        const fd = new FormData();
        fd.append('action','upload'); fd.append('task_sys_id',window._at.cfg.workSysId); fd.append('work_sys_id',window._at.cfg.workSysId); fd.append('file', file);
        try {
            const res  = await fetch(window._at.cfg.api.notes, { method:'POST', body:fd });
            const json = await res.json();
            if (json.status==='success') {
                const files = [...(conf?.files_json??[])];
                const newFile = { name:file.name, path:json.file_url, type:json.note_type, uploaded_at:new Date().toLocaleString() };
                if (json.note_type === 'image') {
                    try {
                        // Try to extract text from image
                    } catch {}
                }
                files.push(newFile);
                await window._atApi({ action:'update_confirmation', conf_sys_id:confId, ticket_nos:conf?.ticket_nos??[], note:conf?.note??'', files_json:files });
                await window._atReload();
            } else { atT('error', json.message); }
        } catch { atT('error','Upload failed'); }
    }
    prog?.classList.add('hidden');
    input.value = '';
    _renderConfirmation();
    if (window._at.activeConfId) {
        const c = (window._at.data?.at_confirmations??[]).find(x=>x.sys_id===window._at.activeConfId);
        const b = (window._at.data?.at_bookings??[]).find(x=>x.sys_id===c?.booking_sys_id);
        if (c) _renderConfDetail(c, b);
    }
};

window.atRemoveConf = async function(confId) {
    if (!confirm('Remove this confirmation entry?')) return;
    try {
        const json = await window._atApi({ action:'remove_confirmation', conf_sys_id:confId });
        if (json.status==='success') { atT('success','Removed'); await window._atReload(); window._at.activeConfId=null; _renderConfirmation(); }
        else atT('error', json.message);
    } catch { atT('error','Network error'); }
};

// ═══════════════════════════════════════════════════════════════
// TAB 5 — FINANCIAL
// ═══════════════════════════════════════════════════════════════
function _renderFinancial() {
    const panel = document.getElementById('at-panel-financial');
    panel.innerHTML = `
    <div class="grid grid-cols-3 gap-3 mb-5">
        <div class="rounded-xl p-4 text-center border-l-4 border-l-green-500 bg-white border border-gray-100 shadow-sm">
            <p class="text-xs text-gray-400 mb-1">Client Deposit</p>
            <p class="text-xl font-bold text-green-600" id="at-fe-credit">৳ 0</p>
        </div>
        <div class="rounded-xl p-4 text-center border-l-4 border-l-red-500 bg-white border border-gray-100 shadow-sm">
            <p class="text-xs text-gray-400 mb-1">Vendor Payment</p>
            <p class="text-xl font-bold text-red-500" id="at-fe-debit">৳ 0</p>
        </div>
        <div class="rounded-xl p-4 text-center border-l-4 border-l-indigo-500 bg-white border border-gray-100 shadow-sm">
            <p class="text-xs text-gray-400 mb-1">Net Balance</p>
            <p class="text-xl font-bold text-indigo-600" id="at-fe-balance">৳ 0</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
        <!-- Client Deposit -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-4 py-3 flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-white text-sm">Client Deposit</h3>
                    <p class="text-indigo-100 text-xs">Record client payments received</p>
                </div>
                <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                    <i class="fas fa-arrow-down text-white"></i>
                </div>
            </div>
            <div class="p-4 space-y-3">
                <div>
                    <label class="text-xs font-semibold text-gray-600 block mb-1">Purpose</label>
                    <textarea id="at-fe-cl-purpose" rows="2" placeholder="e.g. Initial Payment, Final Payment"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm resize-none focus:outline-none focus:border-indigo-400"></textarea>
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-600 block mb-1">Note</label>
                    <input id="at-fe-cl-note" placeholder="Optional note"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-400">
                </div>
                <div class="bg-blue-50 border border-blue-100 rounded-xl p-3">
                    <p class="text-xs text-blue-600 font-medium mb-2"><i class="fas fa-calculator mr-1"></i>Any two → third auto-calculates</p>
                    <div class="grid grid-cols-3 gap-2">
                        <div><label class="text-[10px] text-blue-600 font-bold uppercase block mb-1">QTY</label>
                            <input type="number" step="0.01" min="0" id="at-fe-cl-qty" placeholder="0"
                                oninput="atFeCalc('cl')"
                                class="w-full px-2 py-1.5 border border-gray-200 rounded-lg text-xs focus:outline-none focus:border-indigo-400"></div>
                        <div><label class="text-[10px] text-blue-600 font-bold uppercase block mb-1">Rate</label>
                            <input type="number" step="0.01" min="0" id="at-fe-cl-rate" placeholder="0.00"
                                oninput="atFeCalc('cl')"
                                class="w-full px-2 py-1.5 border border-gray-200 rounded-lg text-xs focus:outline-none focus:border-indigo-400"></div>
                        <div><label class="text-[10px] text-blue-600 font-bold uppercase block mb-1">Amount ৳</label>
                            <input type="number" step="0.01" min="0" id="at-fe-cl-amount" placeholder="0.00"
                                oninput="atFeCalc('cl')"
                                class="w-full px-2 py-1.5 border border-green-300 rounded-lg text-xs focus:outline-none focus:border-green-500 bg-green-50 font-bold"></div>
                    </div>
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-600 block mb-1">Date</label>
                    <input type="date" id="at-fe-cl-date" value="${new Date().toISOString().split('T')[0]}"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-400">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <button onclick="atAddFE('refund')"
                        class="py-2.5 bg-green-500 hover:bg-green-600 text-white rounded-lg text-xs font-bold transition">
                        <i class="fas fa-undo mr-1"></i>Refund
                    </button>
                    <button onclick="atAddFE('debit')"
                        class="py-2.5 bg-red-500 hover:bg-red-600 text-white rounded-lg text-xs font-bold transition">
                        <i class="fas fa-plus mr-1"></i>Debit
                    </button>
                </div>
            </div>
        </div>

        <!-- Vendor Payment -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-4 py-3 flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-white text-sm">Vendor Payment</h3>
                    <p class="text-green-100 text-xs">Record payments made to vendors</p>
                </div>
                <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                    <i class="fas fa-arrow-up text-white"></i>
                </div>
            </div>
            <div class="p-4 space-y-3">
                <div>
                    <label class="text-xs font-semibold text-gray-600 block mb-1">Vendor / Account</label>
                    <input id="at-fe-vn-vendor" placeholder="Vendor name or account"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-green-400">
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-600 block mb-1">Purpose</label>
                    <textarea id="at-fe-vn-purpose" rows="2" placeholder="e.g. Air Ticket Payment, Hotel Booking"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm resize-none focus:outline-none focus:border-green-400"></textarea>
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-600 block mb-1">Note</label>
                    <input id="at-fe-vn-note" placeholder="Optional note"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-green-400">
                </div>
                <div class="bg-green-50 border border-green-100 rounded-xl p-3">
                    <p class="text-xs text-green-600 font-medium mb-2"><i class="fas fa-calculator mr-1"></i>Any two → third auto-calculates</p>
                    <div class="grid grid-cols-3 gap-2">
                        <div><label class="text-[10px] text-green-600 font-bold uppercase block mb-1">QTY</label>
                            <input type="number" step="0.01" min="0" id="at-fe-vn-qty" placeholder="0"
                                oninput="atFeCalc('vn')"
                                class="w-full px-2 py-1.5 border border-gray-200 rounded-lg text-xs focus:outline-none focus:border-green-400"></div>
                        <div><label class="text-[10px] text-green-600 font-bold uppercase block mb-1">Rate</label>
                            <input type="number" step="0.01" min="0" id="at-fe-vn-rate" placeholder="0.00"
                                oninput="atFeCalc('vn')"
                                class="w-full px-2 py-1.5 border border-gray-200 rounded-lg text-xs focus:outline-none focus:border-green-400"></div>
                        <div><label class="text-[10px] text-green-600 font-bold uppercase block mb-1">Amount ৳</label>
                            <input type="number" step="0.01" min="0" id="at-fe-vn-amount" placeholder="0.00"
                                oninput="atFeCalc('vn')"
                                class="w-full px-2 py-1.5 border border-green-300 rounded-lg text-xs focus:outline-none focus:border-green-500 bg-green-50 font-bold"></div>
                    </div>
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-600 block mb-1">Date</label>
                    <input type="date" id="at-fe-vn-date" value="${new Date().toISOString().split('T')[0]}"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-green-400">
                </div>
                <button onclick="atAddFE('credit')"
                    class="w-full py-2.5 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white rounded-lg text-xs font-bold transition">
                    <i class="fas fa-plus mr-1"></i>Add Credit (Vendor Payment)
                </button>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-800 text-sm">Transaction History</h3>
            <button onclick="atLoadFE()" class="text-xs text-gray-400 hover:text-gray-600"><i class="fas fa-sync-alt mr-1"></i>Refresh</button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-[10px] uppercase">
                        <th class="px-4 py-2.5 text-left font-bold">Type</th>
                        <th class="px-4 py-2.5 text-left font-bold">Purpose / Vendor</th>
                        <th class="px-4 py-2.5 text-left font-bold">Date</th>
                        <th class="px-4 py-2.5 text-right font-bold">Amount</th>
                        <th class="px-4 py-2.5 text-center font-bold">Action</th>
                    </tr>
                </thead>
                <tbody id="at-fe-tbody">
                    <tr><td colspan="5" class="text-center py-8 text-gray-300"><i class="fas fa-spinner fa-spin text-lg"></i></td></tr>
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-100 flex items-center justify-between bg-gray-50">
            <span class="text-xs text-gray-400">Running Balance</span>
            <span class="font-bold text-sm" id="at-fe-running">৳ 0</span>
        </div>
    </div>`;

    atLoadFE();
}

window.atFeCalc = function(pfx) {
    const qty  = +(document.getElementById(`at-fe-${pfx}-qty`)?.value  || 0);
    const rate = +(document.getElementById(`at-fe-${pfx}-rate`)?.value || 0);
    const amt  = +(document.getElementById(`at-fe-${pfx}-amount`)?.value || 0);
    const qEl  = document.getElementById(`at-fe-${pfx}-qty`);
    const rEl  = document.getElementById(`at-fe-${pfx}-rate`);
    const aEl  = document.getElementById(`at-fe-${pfx}-amount`);
    if (qty && rate && !amt) { if (aEl) aEl.value = (qty * rate).toFixed(2); }
    else if (qty && amt && !rate) { if (rEl) rEl.value = qty ? (amt / qty).toFixed(2) : ''; }
    else if (rate && amt && !qty) { if (qEl) qEl.value = rate ? (amt / rate).toFixed(2) : ''; }
};

window.atLoadFE = async function() {
    try {
        const res  = await fetch(`${window._at.cfg.api.taskFinEntries}?task_id=${encodeURIComponent(window._at.cfg.workSysId)}`);
        const json = await res.json();
        _renderFE(json.entries ?? json.data ?? []);
    } catch { _renderFE([]); }
};

function _renderFE(entries) {
    let credit=0, debit=0;
    entries.forEach(e => { if (e.type==='credit'||e.type==='refund') credit+=+e.amount; else debit+=+e.amount; });
    const balance = credit - debit;
    const cel=document.getElementById('at-fe-credit'), del=document.getElementById('at-fe-debit'), bel=document.getElementById('at-fe-balance'), rel=document.getElementById('at-fe-running');
    if (cel) cel.textContent='৳ '+_fmtN(credit);
    if (del) del.textContent='৳ '+_fmtN(debit);
    if (bel) { bel.textContent=(balance>=0?'৳ ':'-৳ ')+_fmtN(Math.abs(balance)); bel.style.color=balance>=0?'#4f46e5':'#dc2626'; }
    if (rel) { rel.textContent=(balance>=0?'৳ ':'-৳ ')+_fmtN(Math.abs(balance)); rel.style.color=balance>=0?'#059669':'#dc2626'; }

    const tbody=document.getElementById('at-fe-tbody');
    if (!tbody) return;
    if (!entries.length) { tbody.innerHTML=`<tr><td colspan="5" class="text-center py-8 text-gray-300 text-xs">No transactions yet</td></tr>`; return; }

    tbody.innerHTML = entries.map(e => {
        const isCredit = e.type==='credit'||e.type==='refund';
        const typeLabel = e.type==='debit'?'Debit':e.type==='credit'?'Credit':'Refund';
        const typeCls   = isCredit?'bg-green-100 text-green-700':'bg-red-100 text-red-700';
        const amtCls    = isCredit?'text-green-600 font-bold':'text-red-500 font-bold';
        const prefix    = isCredit?'+':'-';
        return `<tr class="border-b border-gray-50 hover:bg-gray-50 transition">
            <td class="px-4 py-3">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold ${typeCls}">${typeLabel}</span>
            </td>
            <td class="px-4 py-3">
                <div class="font-medium text-gray-800">${_e(e.description??e.purpose??'—')}</div>
                ${e.note ? `<div class="text-[10px] text-gray-400">${_e(e.note)}</div>` : ''}
                ${e.vendor_name ? `<div class="text-[10px] text-gray-400"><i class="fas fa-building mr-1"></i>${_e(e.vendor_name)}</div>` : ''}
            </td>
            <td class="px-4 py-3 text-gray-500 whitespace-nowrap">${_e(e.entry_date??e.created_at??'')}</td>
            <td class="px-4 py-3 text-right ${amtCls}">${prefix}৳ ${_fmtN(+e.amount)}</td>
            <td class="px-4 py-3 text-center">
                <button onclick="atDeleteFE('${_e(e.sys_id)}')"
                    class="w-7 h-7 bg-gray-100 hover:bg-red-50 hover:text-red-500 text-gray-400 rounded-lg inline-flex items-center justify-center transition">
                    <i class="fas fa-trash-alt text-xs"></i>
                </button>
            </td>
        </tr>`;
    }).join('');
}

window.atAddFE = async function(type) {
    const isCl   = (type === 'debit' || type === 'refund');
    const pfx    = isCl ? 'cl' : 'vn';
    const amount = +(document.getElementById(`at-fe-${pfx}-amount`)?.value ?? 0);
    const desc   = document.getElementById(isCl?'at-fe-cl-purpose':'at-fe-vn-purpose')?.value.trim();
    const note   = document.getElementById(isCl?'at-fe-cl-note':'at-fe-vn-note')?.value.trim();
    const date   = document.getElementById(`at-fe-${pfx}-date`)?.value;
    const vendor = isCl ? null : document.getElementById('at-fe-vn-vendor')?.value.trim();
    const qty    = +(document.getElementById(`at-fe-${pfx}-qty`)?.value ?? 0) || null;
    const rate   = +(document.getElementById(`at-fe-${pfx}-rate`)?.value ?? 0) || null;

    if (!amount || amount <= 0) { atT('error','Amount must be > 0'); return; }
    try {
        const res  = await fetch(window._at.cfg.api.saveFinancial, {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ task_sys_id:window._at.cfg.workSysId, work_sys_id:window._at.cfg.workSysId, type, amount, description:desc, note, vendor_name:vendor, entry_date:date, qty, rate }),
        });
        const json = await res.json();
        if (json.status === 'success') {
            atT('success','Entry added!');
            [`at-fe-${pfx}-qty`,`at-fe-${pfx}-rate`,`at-fe-${pfx}-amount`].forEach(id => { const el=document.getElementById(id); if(el) el.value=''; });
            if(isCl) { document.getElementById('at-fe-cl-purpose').value=''; document.getElementById('at-fe-cl-note').value=''; }
            else      { document.getElementById('at-fe-vn-purpose').value=''; document.getElementById('at-fe-vn-note').value=''; document.getElementById('at-fe-vn-vendor').value=''; }
            atLoadFE();
        } else { atT('error', json.message); }
    } catch { atT('error','Network error'); }
};

window.atDeleteFE = async function(sysId) {
    if (!confirm('Delete this transaction?')) return;
    try {
        const res  = await fetch(window._at.cfg.api.saveFinancial, {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ action:'delete', sys_id:sysId }),
        });
        const json = await res.json();
        if (json.status==='success') { atT('success','Deleted'); atLoadFE(); }
        else atT('error', json.message);
    } catch { atT('error','Network error'); }
};