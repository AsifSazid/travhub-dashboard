/**
 * FILE PATH: /pages/task-tabs/ww-umrah/documents.js
 * Tab: Documents — NOC (per traveler) + Group Auth Letter
 */

window._renderUmDocuments = function() {
    const panel   = document.getElementById('um-panel-documents');
    const group   = window._um.group;
    const gData   = group?.group;
    const members = group?.members ?? [];
    const nocs    = group?.nocs    ?? [];

    if (!gData) {
        panel.innerHTML = `<div class="text-center py-12 text-gray-300">
            <i class="fas fa-users text-3xl mb-2 block"></i>
            <p class="text-sm">Create a group first (Travelers tab)</p>
        </div>`;
        return;
    }

    // Separate NOC types
    const individualNocs = nocs.filter(n => (n.doc_type ?? 'noc') === 'noc');
    const authLetters    = nocs.filter(n => n.doc_type === 'auth_letter');

    // Map traveler_sys_id → their NOC
    const nocByTraveler = {};
    individualNocs.forEach(n => {
        const ids = n.traveler_sys_ids ? JSON.parse(n.traveler_sys_ids) : [];
        if (ids.length === 1) nocByTraveler[ids[0]] = n;
    });

    panel.innerHTML = `
    <!-- Group Auth Letter -->
    <div class="mb-6">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-bold text-gray-700"><i class="fas fa-file-contract mr-2 text-purple-500"></i>Group Authorization Letter</h3>
        </div>
        ${authLetters.length ? `
        <div class="space-y-2 mb-3">
            ${authLetters.map(n => `
            <div class="flex items-center gap-3 p-3 bg-purple-50 border border-purple-100 rounded-xl">
                <i class="fas fa-file-pdf text-red-500 flex-shrink-0"></i>
                <span class="text-sm text-gray-700 flex-1 truncate">${_ume(n.file_name||'Auth Letter')}</span>
                <span class="text-[10px] text-gray-400">${_ume(n.uploaded_at?.slice(0,10)||'')}</span>
                ${n.smb_path ? `<a href="${_ume(window._um.cfg.api.umrahWork.replace('endpoints.php','serve.php')+'?path='+encodeURIComponent(n.smb_path))}" target="_blank" class="text-purple-600 hover:text-purple-800 text-xs"><i class="fas fa-download"></i></a>` : ''}
            </div>`).join('')}
        </div>` : '<p class="text-xs text-gray-300 mb-3">No auth letter uploaded yet.</p>'}

        <!-- Upload Auth Letter -->
        <div class="p-4 border-2 border-dashed border-purple-200 rounded-xl">
            <div class="text-xs font-semibold text-gray-500 uppercase mb-2">Select travelers for Auth Letter:</div>
            <div class="space-y-1.5 mb-3 max-h-36 overflow-y-auto">
                ${members.map(m => `
                <label class="flex items-center gap-2 cursor-pointer text-sm">
                    <input type="checkbox" class="um-auth-cb w-3.5 h-3.5 accent-purple-600" value="${_ume(m.traveler_id)}">
                    <span>${_ume(m.name||'Unknown')}</span>
                    ${m.is_leader ? '<span class="text-[10px] bg-amber-100 text-amber-700 px-1.5 rounded font-semibold">Leader</span>' : ''}
                </label>`).join('')}
            </div>
            <label class="flex items-center justify-center gap-2 w-full py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-semibold cursor-pointer transition">
                <i class="fas fa-upload"></i>Upload Auth Letter
                <input type="file" class="hidden" accept=".pdf,.jpg,.jpeg,.png" onchange="umUploadAuthLetter(this)">
            </label>
        </div>
    </div>

    <!-- Individual NOCs -->
    <div>
        <h3 class="text-sm font-bold text-gray-700 mb-3"><i class="fas fa-file-shield mr-2 text-indigo-500"></i>Individual NOCs</h3>
        <div class="space-y-2">
            ${members.map(m => {
                const noc = nocByTraveler[m.traveler_id];
                return `<div class="flex items-center gap-3 p-3 bg-white border border-gray-100 rounded-xl hover:border-indigo-200 transition">
                    <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-xs flex-shrink-0">${_ume((m.name||'?')[0])}</div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-gray-800">${_ume(m.name||'Unknown')}</div>
                        <div class="text-[11px] text-gray-400">${_ume(m.passport_no||'—')}</div>
                    </div>
                    ${noc ? `
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] bg-green-100 text-green-700 px-2 py-0.5 rounded font-semibold"><i class="fas fa-check mr-1"></i>NOC</span>
                        <span class="text-[10px] text-gray-400">${_ume(noc.uploaded_at?.slice(0,10)||'')}</span>
                    </div>` : ''}
                    <label class="flex items-center gap-1.5 px-3 py-1.5 border border-indigo-200 hover:border-indigo-400 rounded-lg cursor-pointer text-xs font-semibold text-indigo-600 transition">
                        <i class="fas fa-upload text-[10px]"></i>${noc ? 'Update' : 'Upload NOC'}
                        <input type="file" class="hidden" accept=".pdf,.jpg,.jpeg,.png" data-traveler-id="${_ume(m.traveler_id)}" onchange="umUploadNoc(this)">
                    </label>
                </div>`;
            }).join('')}
        </div>
    </div>`;
};

// ── Upload Individual NOC ─────────────────────────────────────
window.umUploadNoc = async function(input) {
    const tSysId = input.dataset.travelerId;
    const gSysId = window._um.summary?.group?.sys_id;
    if (!input.files[0] || !tSysId || !gSysId) return;

    const label = input.closest('label');
    if (label) label.innerHTML = '<i class="fas fa-spinner fa-spin text-[10px]"></i>Uploading…';

    const fd = new FormData();
    fd.append('action',           'upload_noc');
    fd.append('group_sys_id',     gSysId);
    fd.append('traveler_sys_ids', JSON.stringify([tSysId]));
    fd.append('doc_type',         'noc');
    fd.append('work_sys_id',      window._um.cfg.workSysId);
    fd.append('file',             input.files[0]);

    try {
        const res  = await fetch(window._um.cfg.api.umrahGroups, { method:'POST', body:fd });
        const json = await res.json();
        if (json.status === 'success') { umT('success','NOC uploaded!'); await window._umReload(); _renderUmDocuments(); }
        else umT('error', json.message||'Upload failed');
    } catch(e) { umT('error','Network error'); }
};

// ── Upload Group Auth Letter ──────────────────────────────────
window.umUploadAuthLetter = async function(input) {
    const gSysId   = window._um.summary?.group?.sys_id;
    const selected = [...document.querySelectorAll('.um-auth-cb:checked')].map(cb => cb.value);
    if (!input.files[0] || !gSysId) return;
    if (!selected.length) { umT('error','কমপক্ষে একজন traveler select করুন'); input.value=''; return; }

    const fd = new FormData();
    fd.append('action',           'upload_noc');
    fd.append('group_sys_id',     gSysId);
    fd.append('traveler_sys_ids', JSON.stringify(selected));
    fd.append('doc_type',         'auth_letter');
    fd.append('work_sys_id',      window._um.cfg.workSysId);
    fd.append('file',             input.files[0]);

    try {
        const res  = await fetch(window._um.cfg.api.umrahGroups, { method:'POST', body:fd });
        const json = await res.json();
        if (json.status === 'success') { umT('success','Auth Letter uploaded!'); await window._umReload(); _renderUmDocuments(); }
        else umT('error', json.message||'Upload failed');
    } catch(e) { umT('error','Network error'); }
};