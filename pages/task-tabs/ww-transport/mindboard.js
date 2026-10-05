/**
 * FILE PATH: /pages/task-tabs/ww-transport/mindboard.js
 */

window._renderTsMindboard = function() {
    const panel = document.getElementById('ts-panel-mindboard');
    panel.style.cssText = 'display:flex;flex-direction:column;padding:0;';

    panel.innerHTML = `
    <!-- Action bar -->
    <div id="ts-gen-actionbar" class="hidden" style="flex-shrink:0;background:#F0F9FF;border-bottom:1px solid #BAE6FD;padding:8px 14px;display:flex;align-items:center;gap:8px;">
        <span id="ts-gen-count" style="font-size:11px;font-weight:600;color:#0284C7;">0 selected</span>
        <div style="flex:1;"></div>
        <button onclick="window._tsGenGenerate('summary')" style="padding:5px 10px;font-size:11px;font-weight:600;background:#fff;color:#0284C7;border:1px solid #BAE6FD;border-radius:8px;cursor:pointer;">
            <i class="fas fa-align-left mr-1"></i>Generate Summary
        </button>
        <button onclick="window._tsGenGenerate('quotation')" style="padding:5px 10px;font-size:11px;font-weight:600;background:#0284C7;color:#fff;border:none;border-radius:8px;cursor:pointer;">
            <i class="fas fa-van-shuttle mr-1"></i>Generate Transport Quotation
        </button>
        <button onclick="window._tsGenClear()" style="padding:5px 8px;font-size:11px;background:transparent;color:#6B7280;border:none;cursor:pointer;">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- Notes list -->
    <div id="ts-notes-list" style="flex:1;overflow-y:auto;padding:12px 14px 12px 34px;display:flex;flex-direction:column;gap:8px;min-height:300px;max-height:calc(100vh - 350px);">
        <div class="text-center py-6 text-gray-300 text-sm"><i class="fas fa-spinner fa-spin"></i></div>
    </div>

    <!-- Input bar -->
    <div style="flex-shrink:0;border-top:1px solid #f1f5f9;background:#fff;padding:10px 12px;">
        <div id="ts-multi-preview" style="display:none;margin-bottom:6px;flex-wrap:wrap;gap:4px;"></div>
        <div class="flex items-end gap-1.5">
            <button id="ts-rec-btn" onclick="tsRecToggle()" style="width:32px;height:32px;background:#fdf2f8;border:none;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-microphone" style="color:#db2777;font-size:.75rem;"></i>
            </button>
            <label style="width:32px;height:32px;background:#eff6ff;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0;">
                <i class="fas fa-image" style="color:#3b82f6;font-size:.75rem;"></i>
                <input type="file" class="hidden" accept="image/*" multiple onchange="tsFilesSelected(this)">
            </label>
            <label style="width:32px;height:32px;background:#f3f4f6;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0;">
                <i class="fas fa-paperclip" style="color:#6b7280;font-size:.75rem;"></i>
                <input type="file" class="hidden" multiple onchange="tsFilesSelected(this)">
            </label>
            <textarea id="ts-note-text" rows="1" placeholder="Write a note… (Enter to send)"
                style="flex:1;resize:none;border:1.5px solid #e5e7eb;border-radius:20px;padding:8px 14px;font-size:.83rem;outline:none;max-height:80px;overflow-y:auto;"
                onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();tsSendNote();}"
                oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,80)+'px'"></textarea>
            <button onclick="tsSendNote()" style="width:36px;height:36px;background:#0284C7;border:none;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-paper-plane" style="color:#fff;font-size:.82rem;"></i>
            </button>
        </div>
    </div>`;

    tsLoadNotes();
};

// ── Note selection ────────────────────────────────────────────
window._tsGenToggleNote = function(sysId, checked) {
    if (checked) window._ts.selectedNoteIds.add(sysId);
    else         window._ts.selectedNoteIds.delete(sysId);
    const bar = document.getElementById('ts-gen-actionbar');
    const cnt = document.getElementById('ts-gen-count');
    const n   = window._ts.selectedNoteIds.size;
    if (n > 0) { bar?.classList.remove('hidden'); if (cnt) cnt.textContent = `${n} selected`; }
    else        bar?.classList.add('hidden');
};

window._tsGenClear = function() {
    window._ts.selectedNoteIds.clear();
    document.querySelectorAll('.ts-gen-cb').forEach(cb => cb.checked = false);
    document.getElementById('ts-gen-actionbar')?.classList.add('hidden');
};

// ── Generate ──────────────────────────────────────────────────
window._tsGenGenerate = async function(mode) {
    const noteIds = [...window._ts.selectedNoteIds];
    if (!noteIds.length) { tsT('error','কমপক্ষে একটা note select করুন'); return; }

    const ov = _tsShowLoadingModal(mode === 'summary' ? 'Summary' : 'Transport Quotation');

    try {
        const apiUrl = window._ts.cfg.api.transportServices.replace('endpoints.php','extract-from-notes.php');
        const res  = await fetch(apiUrl, {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ work_sys_id: window._ts.cfg.workSysId, note_sys_ids: noteIds, action: mode }),
        });
        const json = await res.json();
        if (!json.success) { ov.remove(); tsT('error', json.message||'Generate ব্যর্থ'); return; }

        if (mode === 'summary') _tsRenderSummaryModal(ov, json.summary_text);
        else                    _tsRenderQuotationModal(ov, json.quotation, noteIds);
    } catch(e) { ov.remove(); tsT('error','Network error'); console.error(e); }
};

function _tsShowLoadingModal(label) {
    const ov = document.createElement('div');
    ov.id = 'ts-gen-modal-ov';
    ov.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9998;display:flex;align-items:center;justify-content:center;padding:16px;';
    ov.innerHTML = `<div style="background:#fff;border-radius:16px;padding:32px;text-align:center;min-width:280px;">
        <i class="fas fa-spinner fa-spin" style="font-size:24px;color:#0284C7;"></i>
        <p style="margin-top:12px;font-size:13px;color:#4B5563;">Generating ${_tse(label)}…</p>
    </div>`;
    document.body.appendChild(ov);
    return ov;
}

function _tsRenderSummaryModal(ov, text) {
    ov.innerHTML = `
    <div style="background:#fff;border-radius:16px;width:100%;max-width:620px;max-height:90vh;overflow-y:auto;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #f1f5f9;">
            <h3 style="font-size:15px;font-weight:700;color:#1F2937;margin:0;"><i class="fas fa-align-left mr-2" style="color:#0284C7;"></i>Summary</h3>
            <button onclick="this.closest('[id=ts-gen-modal-ov]')?.remove()" style="background:none;border:none;color:#9CA3AF;cursor:pointer;font-size:16px;"><i class="fas fa-times"></i></button>
        </div>
        <div style="padding:20px;">
            <div contenteditable="true" id="ts-summary-text"
                style="background:#F9FAFB;border:1px solid #E5E7EB;border-radius:10px;padding:12px 14px;font-size:13px;color:#374151;line-height:1.7;min-height:80px;white-space:pre-wrap;">${_tse(text)}</div>
            <button onclick="tsSaveSummary()" style="margin-top:10px;padding:7px 14px;font-size:12px;font-weight:600;background:#0284C7;color:#fff;border:none;border-radius:8px;cursor:pointer;">
                <i class="fas fa-save mr-1"></i>Save in Mind Board
            </button>
        </div>
    </div>`;
}

window.tsSaveSummary = async function() {
    const txt = document.getElementById('ts-summary-text')?.innerText.trim();
    if (!txt) return;
    try {
        await fetch(window._ts.cfg.api.notes, {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ action:'store', work_sys_id:window._ts.cfg.workSysId, service_slug:'transport', board:'mindboard', content:'📝 Summary:\n'+txt }),
        });
        tsT('success','Saved!'); window._tsGenClear();
        document.getElementById('ts-gen-modal-ov')?.remove();
        await tsLoadNotes();
    } catch(e) { tsT('error','Network error'); }
};

// ── Quotation result modal ────────────────────────────────────
let _tsCurrentQuotation = null, _tsCurrentNoteIds = [];

function _tsRenderQuotationModal(ov, quotation, noteIds) {
    _tsCurrentQuotation = quotation;
    _tsCurrentNoteIds   = noteIds;
    const legs = quotation.legs || [];
    const curr = quotation.currency || 'BDT';
    const totalNet = legs.reduce((s,l) => s + (+(l.net_rate||0) * +(l.qty||1)), 0);

    ov.innerHTML = `
    <div style="background:#fff;border-radius:16px;width:100%;max-width:700px;max-height:90vh;overflow-y:auto;display:flex;flex-direction:column;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #F1F5F9;flex-shrink:0;">
            <h3 style="font-size:15px;font-weight:700;color:#1F2937;margin:0;"><i class="fas fa-van-shuttle mr-2" style="color:#0284C7;"></i>Generated Transport Quotation</h3>
            <button onclick="document.getElementById('ts-gen-modal-ov')?.remove()" style="background:none;border:none;color:#9CA3AF;cursor:pointer;font-size:16px;"><i class="fas fa-times"></i></button>
        </div>
        <div style="padding:20px;overflow-y:auto;flex:1;">
            <!-- Legs preview -->
            <div style="margin-bottom:16px;">
                <div style="font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;margin-bottom:8px;">Legs (${legs.length})</div>
                ${legs.map((l,i) => `
                <div style="background:#F0F9FF;border:1px solid #BAE6FD;border-radius:10px;padding:10px 12px;margin-bottom:6px;font-size:12px;">
                    <div style="font-weight:700;color:#0369A1;margin-bottom:4px;">Leg ${i+1}: ${_tse(l.from||'?')} → ${_tse(l.to||'?')}</div>
                    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:4px;color:#374151;">
                        <div><span style="color:#9CA3AF;">Date</span><br><b>${_tse(l.date||'—')}</b></div>
                        <div><span style="color:#9CA3AF;">Time</span><br><b>${_tse(l.time||'—')}</b></div>
                        <div><span style="color:#9CA3AF;">Vehicle</span><br><b>${_tse(TS_VEHICLE_CLASSES[l.vehicle_class]||l.vehicle_class||'—')}</b></div>
                        <div><span style="color:#9CA3AF;">Pax</span><br><b>${l.pax||1}</b></div>
                        <div><span style="color:#9CA3AF;">Rate</span><br><b>${curr} ${_tsFmt(l.net_rate||0)}</b></div>
                        <div><span style="color:#9CA3AF;">Qty</span><br><b>${l.qty||1}</b></div>
                        <div><span style="color:#9CA3AF;">Type</span><br><b>${_tse(l.transfer_type||'private')}</b></div>
                        <div><span style="color:#9CA3AF;">Subtotal</span><br><b>${curr} ${_tsFmt((+(l.net_rate||0))*(+(l.qty||1)))}</b></div>
                    </div>
                    ${l.note ? `<div style="margin-top:4px;color:#6B7280;font-size:11px;">${_tse(l.note)}</div>` : ''}
                </div>`).join('')}
                <div style="text-align:right;font-size:13px;font-weight:700;color:#0369A1;margin-top:4px;">Total Net: ${curr} ${_tsFmt(totalNet)}</div>
            </div>

            <button onclick="tsSaveGeneratedQuotation()" style="width:100%;padding:10px;font-size:13px;font-weight:600;background:#0284C7;color:#fff;border:none;border-radius:10px;cursor:pointer;">
                <i class="fas fa-save mr-1.5"></i>Save as Quotation
            </button>
        </div>
    </div>`;
}

window.tsSaveGeneratedQuotation = async function() {
    const q = _tsCurrentQuotation;
    if (!q) return;
    try {
        const json = await window._tsApi({
            action:          'save_quotation',
            currency:        q.currency || 'BDT',
            legs:            q.legs     || [],
            source_note_ids: _tsCurrentNoteIds,
        });
        if (json.status === 'success') {
            tsT('success','Quotation saved!');
            window._tsGenClear();
            document.getElementById('ts-gen-modal-ov')?.remove();
            await window._tsReload();
            const btn = document.querySelector('.ts-tab[data-tab="quotation"]');
            if (btn) window._tsSwitchTab('quotation', btn);
        } else { tsT('error', json.message||'Save ব্যর্থ'); }
    } catch(e) { tsT('error','Network error'); }
};

// ── Notes CRUD ────────────────────────────────────────────────
window.tsLoadNotes = async function() {
    try {
        const url  = `${window._ts.cfg.api.notes}?action=list&work_sys_id=${encodeURIComponent(window._ts.cfg.workSysId)}&service_slug=transport&board=mindboard`;
        const json = await (await fetch(url)).json();
        const notes = json.status === 'success' ? (json.data ?? []) : [];
        window._ts.currentNotes = notes;
        _tsRenderNotes(notes);
    } catch(e) { _tsRenderNotes([]); }
};

function _tsRenderNotes(notes) {
    const list = document.getElementById('ts-notes-list');
    if (!list) return;
    if (!notes.length) { list.innerHTML = '<div class="text-center py-8 text-gray-300 text-xs">No notes yet.</div>'; return; }
    list.innerHTML = notes.map(_tsNoteBubble).join('');
    setTimeout(() => { list.scrollTop = list.scrollHeight; }, 50);
}

function _tsNoteBubble(n) {
    const sysId   = _tse(n.sys_id);
    const content = n.content ?? '';
    const fileUrl = n.serve_url ?? n.file_url ?? '';
    const dateStr = n.meta_data?.created_by_date?.date ?? '';
    const isSel   = window._ts.selectedNoteIds.has(n.sys_id);
    const delBtn  = `<button onclick="tsDeleteNote('${sysId}')" style="background:none;border:none;color:#f87171;cursor:pointer;font-size:.7rem;padding:2px 6px;"><i class="fas fa-trash"></i></button>`;

    if (n.note_type === 'text') {
        const len = content.length;
        const minW = len<20?'min(25%,320px)':len<50?'min(45%,480px)':'min(65%,640px)';
        return `<div style="align-self:flex-start;min-width:${minW};max-width:85%;position:relative;">
            <input type="checkbox" class="ts-gen-cb" onchange="window._tsGenToggleNote('${sysId}',this.checked)"
                ${isSel?'checked':''} style="position:absolute;top:6px;left:-22px;width:16px;height:16px;cursor:pointer;accent-color:#0284C7;">
            <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:10px 12px;">
                <div style="font-size:.83rem;color:#374151;white-space:pre-line;word-wrap:break-word;">${_tse(content)}</div>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-top:6px;">
                    <span style="font-size:10px;color:#d1d5db;">${_tse(dateStr)}</span>${delBtn}
                </div>
            </div>
        </div>`;
    }
    if (n.note_type === 'image') {
        return `<div style="align-self:flex-start;max-width:780px;position:relative;">
            <input type="checkbox" class="ts-gen-cb" onchange="window._tsGenToggleNote('${sysId}',this.checked)"
                ${isSel?'checked':''} style="position:absolute;top:6px;left:-22px;width:16px;height:16px;cursor:pointer;accent-color:#0284C7;z-index:2;">
            <img src="${fileUrl}" loading="lazy" onclick="tsViewImg('${fileUrl}')"
                style="width:100%;max-height:280px;object-fit:contain;border-radius:8px;cursor:zoom-in;display:block;background:#f9fafb;">
            <div style="display:flex;justify-content:space-between;margin-top:4px;">
                <span style="font-size:10px;color:#d1d5db;">${_tse(dateStr)}</span>${delBtn}
            </div>
        </div>`;
    }
    if (n.note_type === 'audio') {
        return `<div style="align-self:flex-start;min-width:300px;max-width:500px;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:10px 12px;">
            <audio controls style="width:100%;height:32px;" src="${fileUrl}"></audio>
            <div style="display:flex;justify-content:space-between;margin-top:4px;">
                <span style="font-size:10px;color:#d1d5db;">${_tse(dateStr)}</span>${delBtn}
            </div>
        </div>`;
    }
    return `<div style="align-self:flex-start;background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:8px 12px;min-width:200px;max-width:400px;display:flex;align-items:center;gap:8px;">
        <i class="fas fa-paperclip text-sky-500 flex-shrink-0"></i>
        <a href="${fileUrl}" target="_blank" style="font-size:.8rem;color:#0284C7;flex:1;overflow:hidden;text-overflow:ellipsis;">${_tse(n.file_name||'File')}</a>
        ${delBtn}
    </div>`;
}

window.tsSendNote = async function() {
    if (window._ts.pendingFiles.length > 0) {
        const files = [...window._ts.pendingFiles]; window._ts.pendingFiles = [];
        const mp = document.getElementById('ts-multi-preview'); if (mp) { mp.style.display='none'; mp.innerHTML=''; }
        for (const f of files) {
            const fd = new FormData();
            fd.append('action','upload'); fd.append('work_sys_id',window._ts.cfg.workSysId);
            fd.append('service_slug','transport'); fd.append('board','mindboard'); fd.append('file',f);
            try { await fetch(window._ts.cfg.api.notes, { method:'POST', body:fd }); } catch(e) {}
        }
        await tsLoadNotes(); return;
    }
    const ta = document.getElementById('ts-note-text');
    const content = ta?.value.trim(); if (!content) return;
    try {
        const json = await (await fetch(window._ts.cfg.api.notes, {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ action:'store', work_sys_id:window._ts.cfg.workSysId, service_slug:'transport', board:'mindboard', content }),
        })).json();
        if (json.status === 'success') { if (ta) { ta.value=''; ta.style.height='auto'; } await tsLoadNotes(); }
        else tsT('error', json.message||'Failed');
    } catch(e) { tsT('error','Network error'); }
};

window.tsFilesSelected = function(input) {
    if (!input.files.length) return;
    window._ts.pendingFiles = Array.from(input.files);
    const mp = document.getElementById('ts-multi-preview');
    mp.style.display = 'flex';
    mp.innerHTML = window._ts.pendingFiles.map(f =>
        `<span style="display:inline-flex;align-items:center;gap:4px;background:#F0F9FF;border-radius:20px;padding:2px 8px;font-size:.72rem;color:#0284C7;">${_tse(f.name.length>20?f.name.slice(0,18)+'…':f.name)}</span>`
    ).join(''); input.value = '';
};

window.tsDeleteNote = async function(sysId) {
    if (!confirm('Delete?')) return;
    try {
        const json = await (await fetch(window._ts.cfg.api.notes, {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ action:'delete', note_sys_id:sysId, work_sys_id:window._ts.cfg.workSysId }),
        })).json();
        if (json.status==='success') { tsT('success','Deleted'); await tsLoadNotes(); }
        else tsT('error', json.message||'Failed');
    } catch(e) { tsT('error','Network error'); }
};

window.tsViewImg = function(url) {
    const ov = document.createElement('div');
    ov.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:99999;display:flex;align-items:center;justify-content:center;cursor:zoom-out;';
    ov.innerHTML = `<img src="${url}" style="max-width:90vw;max-height:90vh;border-radius:8px;">`;
    ov.onclick = () => ov.remove();
    document.body.appendChild(ov);
};

window.tsRecToggle = async function() {
    if (window._ts.recording) { window._ts.recorder?.stop(); return; }
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio:true });
        window._ts.recChunks = [];
        window._ts.recorder  = new MediaRecorder(stream);
        window._ts.recorder.ondataavailable = e => { if (e.data.size>0) window._ts.recChunks.push(e.data); };
        window._ts.recorder.onstop = async () => {
            stream.getTracks().forEach(t=>t.stop());
            const blob = new Blob(window._ts.recChunks, {type:'audio/webm'});
            const btn  = document.getElementById('ts-rec-btn');
            if (btn) { btn.style.background='#fdf2f8'; btn.innerHTML='<i class="fas fa-microphone" style="color:#db2777;font-size:.75rem;"></i>'; }
            window._ts.recording = false;
            const fd = new FormData();
            fd.append('action','upload'); fd.append('work_sys_id',window._ts.cfg.workSysId);
            fd.append('service_slug','transport'); fd.append('board','mindboard');
            fd.append('file',blob,'voice_'+Date.now()+'.webm');
            try { const j = await (await fetch(window._ts.cfg.api.notes,{method:'POST',body:fd})).json(); if (j.status==='success') await tsLoadNotes(); } catch(e) {}
        };
        window._ts.recorder.start(); window._ts.recording = true;
        const btn = document.getElementById('ts-rec-btn');
        if (btn) { btn.style.background='#fee2e2'; btn.innerHTML='<i class="fas fa-stop" style="color:#dc2626;font-size:.75rem;"></i>'; }
    } catch(e) { tsT('error','Microphone access denied'); }
};