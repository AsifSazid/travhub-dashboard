/**
 * FILE PATH: /pages/task-tabs/ww-hotel/mindboard.js
 * Tab: Mindboard — Hotel Service
 * Pattern: Exact port of ww-air-tickets/mindboard.js
 * Prefix: ht_ (was at_)
 */

// ═══════════════════════════════════════════════════════════════
// TAB 1 — MIND BOARD
// ═══════════════════════════════════════════════════════════════
window._renderHtMindboard = function _renderHtMindboard() {
    const panel = document.getElementById('ht-panel-mindboard');
    if (!panel) return;
    panel.style.display = '';
    panel.style.cssText = 'display:flex;flex-direction:column;padding:0;overflow:hidden;';

    panel.innerHTML = `
    <!-- Generate action bar -->
    <div id="ht-gen-actionbar" class="hidden" style="flex-shrink:0;background:#FFF7ED;border-bottom:1px solid #FED7AA;padding:8px 14px;display:flex;align-items:center;gap:8px;">
        <span id="ht-gen-count" style="font-size:11px;font-weight:600;color:#C2410C;">0 selected</span>
        <div style="flex:1;"></div>
        <button onclick="window._htGenGenerate('summary')" style="padding:5px 10px;font-size:11px;font-weight:600;background:#fff;color:#C2410C;border:1px solid #FED7AA;border-radius:8px;cursor:pointer;">
            <i class="fas fa-align-left mr-1"></i>Generate Summary
        </button>
        <button onclick="window._htGenGenerate('quotation')" style="padding:5px 10px;font-size:11px;font-weight:600;background:#EA580C;color:#fff;border:none;border-radius:8px;cursor:pointer;">
            <i class="fas fa-hotel mr-1"></i>Generate Hotel Quotation
        </button>
        <button onclick="window._htGenClearSelection()" style="padding:5px 8px;font-size:11px;background:transparent;color:#6B7280;border:none;cursor:pointer;">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- Chat bubbles -->
    <div id="ht-notes-list" style="flex:1;overflow-y:auto;padding:12px 14px 12px 34px;display:flex;flex-direction:column;gap:8px;min-height:300px;max-height:calc(100vh - 350px);">
        <div class="text-center py-6 text-gray-300 text-sm"><i class="fas fa-spinner fa-spin"></i></div>
    </div>

    <!-- Upload progress -->
    <div id="ht-upload-prog" class="hidden px-4 py-2 bg-orange-50 border-t border-orange-100 text-sm text-orange-600" style="flex-shrink:0;">
        <i class="fas fa-spinner fa-spin mr-2"></i>Uploading…
    </div>

    <!-- Input bar -->
    <div style="flex-shrink:0;border-top:1px solid #f1f5f9;background:#fff;padding:10px 12px;">
        <!-- STT Panel -->
        <div id="ht-stt-panel" style="display:none;margin-bottom:8px;background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;padding:10px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                <span id="ht-stt-status" style="font-size:.75rem;color:#EA580C;font-weight:600;">Ready</span>
                <select id="ht-stt-lang" style="font-size:.72rem;border:1px solid #fed7aa;border-radius:6px;padding:2px 6px;background:#fff;color:#374151;">
                    <option value="bn-BD">বাংলা</option>
                    <option value="en-US">English</option>
                </select>
            </div>
            <div id="ht-stt-preview" contenteditable="true" style="min-height:36px;background:#fff;border:1px solid #fed7aa;border-radius:8px;padding:6px 10px;font-size:.82rem;color:#374151;margin-bottom:6px;white-space:pre-wrap;"></div>
            <div style="display:flex;gap:6px;margin-bottom:6px;">
                <button id="ht-stt-start" onclick="htSttStart()" style="flex:1;padding:5px;font-size:.72rem;background:#EA580C;color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:600;"><i class="fas fa-play"></i> Start</button>
                <button id="ht-stt-pause" onclick="htSttPause()" disabled style="flex:1;padding:5px;font-size:.72rem;background:#f3f4f6;color:#9ca3af;border:none;border-radius:8px;cursor:pointer;font-weight:600;">⏸ Pause</button>
                <button id="ht-stt-stop" onclick="htSttStop()" disabled style="flex:1;padding:5px;font-size:.72rem;background:#f3f4f6;color:#9ca3af;border:none;border-radius:8px;cursor:pointer;font-weight:600;"><i class="fas fa-stop"></i> Stop</button>
            </div>
            <button onclick="htSttPush()" style="width:100%;padding:5px;font-size:.72rem;background:#059669;color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:600;"><i class="fas fa-arrow-down"></i> Push to Note</button>
        </div>
        <!-- Multi-file preview -->
        <div id="ht-multi-preview" style="display:none;margin-bottom:6px;flex-wrap:wrap;gap:4px;"></div>
        <!-- File preview chip -->
        <div id="ht-file-preview" class="hidden mb-2 bg-orange-50 rounded-lg px-3 py-1.5 text-xs text-orange-600 flex items-center gap-2">
            <i class="fas fa-paperclip flex-shrink-0"></i>
            <span id="ht-file-preview-name" class="flex-1 truncate"></span>
            <button onclick="htClearFile()" class="text-red-400 hover:text-red-600 flex-shrink-0"><i class="fas fa-times"></i></button>
        </div>
        <div class="flex items-end gap-1.5">
            <button onclick="htSttToggle()" title="Voice to Text" style="width:32px;height:32px;background:#fff7ed;border:none;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-keyboard" style="color:#EA580C;font-size:.75rem;"></i>
            </button>
            <button id="ht-rec-btn" onclick="htRecToggle()" title="Record Audio" style="width:32px;height:32px;background:#fdf2f8;border:none;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-microphone" style="color:#db2777;font-size:.75rem;"></i>
            </button>
            <label style="width:32px;height:32px;background:#eff6ff;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0;" title="Images">
                <i class="fas fa-image" style="color:#3b82f6;font-size:.75rem;"></i>
                <input type="file" class="hidden" accept="image/*" multiple onchange="htFilesSelected(this)">
            </label>
            <label style="width:32px;height:32px;background:#f3f4f6;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0;" title="Files">
                <i class="fas fa-paperclip" style="color:#6b7280;font-size:.75rem;"></i>
                <input type="file" class="hidden" multiple onchange="htFilesSelected(this)">
            </label>
            <textarea id="ht-note-text" rows="1"
                placeholder="Write a note… (Enter to send)"
                style="flex:1;resize:none;border:1.5px solid #e5e7eb;border-radius:20px;padding:8px 14px;font-size:.83rem;outline:none;max-height:80px;overflow-y:auto;transition:border .15s;"
                onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();htSendNote();}"
                oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,80)+'px'"></textarea>
            <button onclick="htSendNote()" style="width:36px;height:36px;background:#EA580C;border:none;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-paper-plane" style="color:#fff;font-size:.82rem;"></i>
            </button>
        </div>
    </div>`;

    // Paste + drag & drop
    const ta = document.getElementById('ht-note-text');
    if (ta) {
        ta.addEventListener('paste', function(e) {
            const items = e.clipboardData?.items;
            if (!items) return;
            for (const item of items) {
                if (item.type.startsWith('image/')) {
                    e.preventDefault();
                    const file = item.getAsFile();
                    window._ht.pendingFile = file;
                    document.getElementById('ht-file-preview').classList.remove('hidden');
                    document.getElementById('ht-file-preview-name').textContent = file.name + ' (pasted)';
                    break;
                }
            }
        });
        ta.addEventListener('dragover', function(e) { e.preventDefault(); this.style.borderColor='#EA580C'; });
        ta.addEventListener('dragleave', function(e) { e.preventDefault(); this.style.borderColor='#e5e7eb'; });
        ta.addEventListener('drop', function(e) {
            e.preventDefault(); this.style.borderColor='#e5e7eb';
            const files = e.dataTransfer.files;
            if (files.length) {
                window._ht.pendingFile = files[0];
                document.getElementById('ht-file-preview').classList.remove('hidden');
                document.getElementById('ht-file-preview-name').textContent = files[0].name;
            }
        });
    }

    htLoadNotes();

    // Portal panel (right side)
    if (!panel.querySelector('#ht-portal-panel')) {
        const saved   = localStorage.getItem('ht_portal_width');
        const portalW = saved ? saved + 'px' : '300px';
        const wrapper = document.createElement('div');
        wrapper.style.cssText = 'display:flex;height:100%;flex:1;min-height:0;';
        const leftDiv = document.createElement('div');
        leftDiv.id = 'ht-mindboard-left';
        leftDiv.style.cssText = 'flex:1;min-width:0;display:flex;flex-direction:column;overflow:hidden;';
        const _p = document.getElementById('ht-panel-mindboard');
        while (_p && _p.firstChild) leftDiv.appendChild(_p.firstChild);
        const divider = document.createElement('div');
        divider.style.cssText = 'width:4px;background:#f1f5f9;cursor:col-resize;flex-shrink:0;transition:background .15s;';
        divider.onmouseover = () => divider.style.background = '#EA580C';
        divider.onmouseout  = () => divider.style.background = '#f1f5f9';
        const portalDiv = document.createElement('div');
        portalDiv.id = 'ht-portal-panel';
        portalDiv.style.cssText = `width:${portalW};flex-shrink:0;background:#12172B;display:flex;flex-direction:column;overflow:hidden;`;
        portalDiv.innerHTML = `
            <div style="background:#1C2340;padding:11px 14px;border-bottom:1px solid rgba(255,255,255,.08);flex-shrink:0;">
                <div style="color:#fff;font-size:12px;font-weight:700;">Hotel Portals</div>
                <div style="color:#F97316;font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;">PORTAL LINKS</div>
            </div>
            <div id="ht-portal-body" style="overflow-y:auto;flex:1;padding:8px;">
                <div style="padding:8px;color:#8A93A8;font-size:12px;"><i class="fas fa-spinner fa-spin mr-1"></i> Loading…</div>
            </div>`;
        wrapper.appendChild(leftDiv);
        wrapper.appendChild(divider);
        wrapper.appendChild(portalDiv);
        const _pp = document.getElementById('ht-panel-mindboard');
        if (_pp) _pp.appendChild(wrapper);
        _htLoadPortals();
        _htInitPortalDivider(divider, portalDiv);
    }
};

// ── File handlers ─────────────────────────────────────────────
window._ht.pendingFile  = null;
window._ht.pendingFiles = [];

window.htFilesSelected = function(input) {
    if (!input.files.length) return;
    window._ht.pendingFiles = Array.from(input.files);
    const preview = document.getElementById('ht-multi-preview');
    preview.style.display = 'flex';
    preview.innerHTML = window._ht.pendingFiles.map(f =>
        `<span style="display:inline-flex;align-items:center;gap:4px;background:#f3f4f6;border-radius:20px;padding:2px 8px;font-size:.72rem;color:#374151;"><i class="fas fa-file" style="font-size:.65rem;"></i>${f.name.length>20?f.name.slice(0,18)+'…':f.name}</span>`
    ).join('');
    input.value = '';
};

window.htClearFile = function() {
    window._ht.pendingFiles = [];
    document.getElementById('ht-file-preview').classList.add('hidden');
    const mp = document.getElementById('ht-multi-preview');
    if (mp) { mp.style.display = 'none'; mp.innerHTML = ''; }
};

window.htSendNote = async function() {
    // pendingFiles = multi-file select, pendingFile = single paste/drop
    if (window._ht.pendingFiles.length > 0) {
        const files = [...window._ht.pendingFiles];
        htClearFile();
        for (const file of files) {
            const fd = new FormData();
            fd.append('action', 'upload');
            fd.append('work_sys_id', window._ht.cfg.workSysId);
            fd.append('service_slug', 'hotel');
            fd.append('board', 'mindboard');
            fd.append('file', file);
            try { await (await fetch(window._ht.cfg.api.notes, { method:'POST', body:fd })).json(); }
            catch(e) { console.error('Upload error:', e); }
        }
        await htLoadNotes();
        return;
    }

    // Single pasted/dropped file
    if (window._ht.pendingFile) {
        const prog = document.getElementById('ht-upload-prog');
        prog?.classList.remove('hidden');
        const ta      = document.getElementById('ht-note-text');
        const caption = ta?.value.trim() || '';
        const fd = new FormData();
        fd.append('action', 'upload');
        fd.append('work_sys_id', window._ht.cfg.workSysId);
        fd.append('service_slug', 'hotel');
        fd.append('board', 'mindboard');
        fd.append('content', caption);
        if (window._ht.pendingFile.type === 'application/pdf') fd.append('convert_pdf', 'true');
        fd.append('file', window._ht.pendingFile);
        try {
            const json = await (await fetch(window._ht.cfg.api.notes, { method:'POST', body:fd })).json();
            if (json.status === 'success') {
                if (ta) { ta.value = ''; ta.style.height = 'auto'; }
                htClearFile();
                await htLoadNotes();
            } else { htT('error', json.message || 'Upload failed'); }
        } catch(e) { htT('error', 'Upload failed'); console.error(e); }
        prog?.classList.add('hidden');
        return;
    }

    htAddTextNote();
};

// ── Voice Recording ──────────────────────────────────────────
window._ht.recorder = null; window._ht.recChunks = []; window._ht.recording = false;
window.htRecToggle = async function() {
    if (window._ht.recording) { window._ht.recorder?.stop(); return; }
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        window._ht.recChunks = [];
        window._ht.recorder  = new MediaRecorder(stream);
        window._ht.recorder.ondataavailable = e => { if (e.data.size > 0) window._ht.recChunks.push(e.data); };
        window._ht.recorder.onstop = async () => {
            stream.getTracks().forEach(t => t.stop());
            const blob = new Blob(window._ht.recChunks, { type: 'audio/webm' });
            const btn  = document.getElementById('ht-rec-btn');
            if (btn) { btn.style.background='#fdf2f8'; btn.innerHTML='<i class="fas fa-microphone" style="color:#db2777;font-size:.75rem;"></i>'; }
            window._ht.recording = false;
            const fd = new FormData();
            fd.append('action','upload'); fd.append('work_sys_id',window._ht.cfg.workSysId);
            fd.append('service_slug','hotel'); fd.append('board','mindboard');
            fd.append('file', blob, 'voice_'+Date.now()+'.webm');
            try {
                const j = await (await fetch(window._ht.cfg.api.notes, { method:'POST', body:fd })).json();
                if (j.status === 'success') await htLoadNotes();
            } catch(e) { console.error('Voice upload error:', e); }
        };
        window._ht.recorder.start();
        window._ht.recording = true;
        const btn = document.getElementById('ht-rec-btn');
        if (btn) { btn.style.background='#fee2e2'; btn.innerHTML='<i class="fas fa-stop" style="color:#dc2626;font-size:.75rem;"></i>'; }
    } catch(e) { alert('Microphone access denied'); }
};

// ── STT ──────────────────────────────────────────────────────
window._ht.sttRec = null; window._ht.sttFinal = ''; window._ht.sttActive = false; window._ht.sttPaused = false;
window.htSttToggle = function() { const p=document.getElementById('ht-stt-panel'); if(p) p.style.display=p.style.display==='none'?'block':'none'; };
window.htSttStart = function() {
    if (!('webkitSpeechRecognition' in window || 'SpeechRecognition' in window)) { alert('Use Chrome for voice input'); return; }
    if (!window._ht.sttRec) {
        const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
        window._ht.sttRec = new SR();
        window._ht.sttRec.continuous = true; window._ht.sttRec.interimResults = true;
        window._ht.sttRec.onresult = e => {
            let interim = '';
            for (let i=e.resultIndex; i<e.results.length; i++) {
                if (e.results[i].isFinal) window._ht.sttFinal += e.results[i][0].transcript+' ';
                else interim += e.results[i][0].transcript;
            }
            const el = document.getElementById('ht-stt-preview'); if (el) el.innerText = window._ht.sttFinal+interim;
        };
        window._ht.sttRec.onend = () => { if (window._ht.sttActive && !window._ht.sttPaused) { window._ht.sttRec.lang=document.getElementById('ht-stt-lang')?.value||'bn-BD'; window._ht.sttRec.start(); } };
    }
    window._ht.sttFinal = ''; window._ht.sttActive = true; window._ht.sttPaused = false;
    window._ht.sttRec.lang = document.getElementById('ht-stt-lang')?.value||'bn-BD';
    window._ht.sttRec.start();
    const el = document.getElementById('ht-stt-preview'); if (el) el.innerText='';
    document.getElementById('ht-stt-start')?.setAttribute('disabled',true);
    document.getElementById('ht-stt-pause')?.removeAttribute('disabled');
    document.getElementById('ht-stt-stop')?.removeAttribute('disabled');
    const s=document.getElementById('ht-stt-status'); if(s) s.textContent='🔴 Recording...';
};
window.htSttPause = function() {
    const btn=document.getElementById('ht-stt-pause'); const s=document.getElementById('ht-stt-status');
    if (!window._ht.sttPaused) { window._ht.sttPaused=true; window._ht.sttRec?.stop(); if(btn) btn.textContent='▶ Resume'; if(s) s.textContent='⏸ Paused'; }
    else { window._ht.sttPaused=false; window._ht.sttRec.lang=document.getElementById('ht-stt-lang')?.value||'bn-BD'; window._ht.sttRec?.start(); if(btn) btn.textContent='⏸ Pause'; if(s) s.textContent='🔴 Recording...'; }
};
window.htSttStop = function() {
    window._ht.sttActive=false; window._ht.sttPaused=false; window._ht.sttRec?.stop();
    document.getElementById('ht-stt-start')?.removeAttribute('disabled');
    document.getElementById('ht-stt-pause')?.setAttribute('disabled',true);
    document.getElementById('ht-stt-stop')?.setAttribute('disabled',true);
    const btn=document.getElementById('ht-stt-pause'); if(btn) btn.textContent='⏸ Pause';
    const s=document.getElementById('ht-stt-status'); if(s) s.textContent='✅ Done';
};
window.htSttPush = function() {
    const txt=(window._ht.sttFinal||document.getElementById('ht-stt-preview')?.innerText||'').trim();
    if (!txt) return;
    const ta=document.getElementById('ht-note-text'); if(ta) ta.value=(ta.value?ta.value+' ':'')+txt;
    document.getElementById('ht-stt-panel').style.display='none'; window._ht.sttFinal='';
};

// ── Load notes ────────────────────────────────────────────────
window.htLoadNotes = async function() {
    try {
        const url = `${window._ht.cfg.api.notes}?action=list&work_sys_id=${encodeURIComponent(window._ht.cfg.workSysId)}&service_slug=hotel&board=mindboard`;
        const json = await (await fetch(url)).json();
        const notes = json.status==='success' ? (json.data??[]) : [];
        window._ht.currentNotes = notes;
        _htRenderNotes(notes);
    } catch(e) { console.error('[htLoadNotes]',e); _htRenderNotes([]); }
};

function _htRenderNotes(notes) {
    const list = document.getElementById('ht-notes-list');
    if (!list) return;
    if (!notes.length) { list.innerHTML='<div class="text-center py-8 text-gray-300 text-xs">No notes yet. Write something below.</div>'; return; }
    list.innerHTML = notes.map(n => { try { return _htNoteBubble(n); } catch(e) { return ''; } }).join('');
    setTimeout(() => { list.scrollTop = list.scrollHeight; }, 50);
}

// ── Selected notes ────────────────────────────────────────────
window._ht.selectedNoteIds = window._ht.selectedNoteIds || new Set();

window._htGenToggleNote = function(sysId, checked) {
    if (checked) window._ht.selectedNoteIds.add(sysId);
    else window._ht.selectedNoteIds.delete(sysId);
    _htGenUpdateBar();
};

function _htGenUpdateBar() {
    const bar = document.getElementById('ht-gen-actionbar');
    if (!bar) return;
    const count = window._ht.selectedNoteIds.size;
    if (count > 0) { bar.classList.remove('hidden'); const el=document.getElementById('ht-gen-count'); if(el) el.textContent=`${count} selected`; }
    else { bar.classList.add('hidden'); }
}

window._htGenClearSelection = function() {
    window._ht.selectedNoteIds.clear();
    document.querySelectorAll('.ht-gen-select-cb').forEach(cb => cb.checked=false);
    _htGenUpdateBar();
};

// ── Note Bubble ───────────────────────────────────────────────
function _htNoteBubble(n) {
    const dateStr  = n.meta_data?.created_by_date?.date ?? '';
    const creator  = n.meta_data?.created_by_date?.user ?? n.created_by ?? '';
    const canDel   = !creator || creator === (window.CURRENT_USER ?? '');
    const sysId    = _hte(n.sys_id);
    const content  = n.content ?? '';
    const fileUrl  = n.serve_url ?? n.file_url ?? '';
    const fileName = n.file_name ?? '';

    function _htUsedBadgeHtml() {
        const usedIn = n.meta_data?.used_in_quotations ?? [];
        if (!usedIn.length) return '';
        return `<span style="display:inline-block;margin-top:4px;padding:1px 6px;font-size:9px;font-weight:700;background:#FFF7ED;color:#C2410C;border-radius:10px;">
            <i class="fas fa-hotel" style="font-size:8px;"></i> Used in ${usedIn.map(_hte).join(', ')}
        </span>`;
    }

    function menuHTML() {
        const iS = 'display:flex;align-items:center;gap:10px;padding:8px 16px;font-size:.78rem;font-weight:500;color:#374151;cursor:pointer;border:none;background:none;width:100%;text-align:left;';
        const dS = 'display:flex;align-items:center;gap:10px;padding:8px 16px;font-size:.78rem;font-weight:500;color:#dc2626;cursor:pointer;border:none;background:none;width:100%;text-align:left;';
        let items = '';
        if (n.note_type === 'text') {
            const enc = encodeURIComponent(content);
            items += `<button onclick="htCopyText('${enc}')" style="${iS}"><i class="fas fa-copy" style="width:16px;font-size:.75rem;color:#94a3b8;"></i>Copy</button>`;
            if (canDel) items += `<button onclick="htEditNoteStart('${sysId}')" style="${iS}"><i class="fas fa-pen" style="width:16px;font-size:.75rem;color:#94a3b8;"></i>Edit</button>`;
        } else if (n.note_type === 'image') {
            items += `<button onclick="htCopyImage('${fileUrl}')" style="${iS}"><i class="fas fa-copy" style="width:16px;font-size:.75rem;color:#94a3b8;"></i>Copy Image</button>`;
            items += `<button onclick="htShareFile('${fileUrl}','${_hte(fileName)}','image/jpeg')" style="${iS}"><i class="fas fa-share-alt" style="width:16px;font-size:.75rem;color:#94a3b8;"></i>Share</button>`;
        } else if (n.note_type === 'audio') {
            items += `<button onclick="htShareFile('${fileUrl}','${_hte(fileName)}','audio/webm')" style="${iS}"><i class="fas fa-share-alt" style="width:16px;font-size:.75rem;color:#94a3b8;"></i>Share</button>`;
        } else if (fileUrl) {
            items += `<button onclick="htShareFile('${fileUrl}','${_hte(fileName)}','')" style="${iS}"><i class="fas fa-share-alt" style="width:16px;font-size:.75rem;color:#94a3b8;"></i>Share</button>`;
        }
        if (canDel) items += `<button onclick="htDeleteNote('${sysId}')" style="${dS}"><i class="fas fa-trash" style="width:16px;font-size:.75rem;color:#f87171;"></i>Delete</button>`;
        return `<div class="tmss-menu-dropdown" id="tmss-menu-${sysId}" style="display:none;">${items}</div>`;
    }

    const toggleMenu = `data-menu-toggle="${sysId}"`;

    // Text note
    if (n.note_type === 'text') {
        const len = content.length;
        const minWidth = len<20?'min(25%,320px)':len<50?'min(45%,480px)':'min(65%,640px)';
        return `<div class="tmss-note-bubble" id="ht-note-bubble-${sysId}" style="align-self:flex-start;min-width:${minWidth};max-width:85%;position:relative;">
            <input type="checkbox" class="ht-gen-select-cb" data-note-id="${sysId}"
                onchange="window._htGenToggleNote('${sysId}',this.checked)"
                ${window._ht.selectedNoteIds.has(n.sys_id)?'checked':''}
                style="position:absolute;top:6px;left:-22px;width:16px;height:16px;cursor:pointer;accent-color:#EA580C;">
            <div id="ht-note-view-${sysId}">
                <div style="font-size:.83rem;color:#374151;white-space:pre-line;word-wrap:break-word;">${_hte(content)}</div>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-top:6px;">
                    <span style="font-size:10px;color:#d1d5db;">${_hte(dateStr)}</span>
                    <div style="position:relative;">
                        <button ${toggleMenu} class="tmss-menu-btn"><i class="fas fa-ellipsis-v"></i></button>
                        ${menuHTML()}
                    </div>
                </div>
                ${_htUsedBadgeHtml()}
            </div>
        </div>`;
    }

    // Image note
    if (n.note_type === 'image') {
        return `<div class="tmss-note-bubble" style="align-self:flex-start;max-width:780px;position:relative;">
            <input type="checkbox" class="ht-gen-select-cb" data-note-id="${sysId}"
                onchange="window._htGenToggleNote('${sysId}',this.checked)"
                ${window._ht.selectedNoteIds.has(n.sys_id)?'checked':''}
                style="position:absolute;top:6px;left:-22px;width:16px;height:16px;cursor:pointer;accent-color:#EA580C;z-index:2;">
            <img src="${fileUrl}" loading="lazy" onclick="htViewImg('${fileUrl}')"
                style="width:100%;max-height:280px;object-fit:contain;border-radius:8px;cursor:zoom-in;display:block;background:#f9fafb;">
            ${content?`<div style="font-size:.75rem;color:#6b7280;margin-top:4px;padding:0 2px;">${_hte(content)}</div>`:''}
            <div style="display:flex;align-items:center;justify-content:space-between;margin-top:4px;">
                <span style="font-size:10px;color:#d1d5db;">${_hte(dateStr)}</span>
                <div style="position:relative;">
                    <button ${toggleMenu} class="tmss-menu-btn"><i class="fas fa-ellipsis-v"></i></button>
                    ${menuHTML()}
                </div>
            </div>
            ${_htUsedBadgeHtml()}
        </div>`;
    }

    // Audio note
    if (n.note_type === 'audio') {
        return `<div class="tmss-note-bubble" style="align-self:flex-start;min-width:380px;max-width:720px;position:relative;">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-microphone" style="color:#a855f7;font-size:.75rem;"></i>
                    <span style="font-size:.75rem;color:#6b7280;">${_hte(fileName)}</span>
                </div>
                <div style="position:relative;">
                    <button ${toggleMenu} class="tmss-menu-btn"><i class="fas fa-ellipsis-v"></i></button>
                    ${menuHTML()}
                </div>
            </div>
            <audio controls style="width:100%;height:32px;margin-top:6px;" src="${fileUrl}"></audio>
            <div style="font-size:10px;color:#d1d5db;margin-top:4px;">${_hte(dateStr)}</div>
        </div>`;
    }

    // Video note
    if (n.note_type === 'video') {
        return `<div class="tmss-note-bubble" style="align-self:flex-start;max-width:780px;position:relative;">
            <div style="position:relative;">
                <video controls style="max-height:160px;width:100%;border-radius:8px;" src="${fileUrl}"></video>
                <button ${toggleMenu} class="tmss-menu-btn" style="position:absolute;top:8px;right:8px;background:rgba(0,0,0,.5);color:white;border-radius:50%;width:28px;height:28px;display:flex;align-items:center;justify-content:center;border:none;z-index:102;">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                ${menuHTML()}
            </div>
            <div style="font-size:10px;color:#d1d5db;margin-top:4px;">${_hte(dateStr)}</div>
        </div>`;
    }

    // File note
    return `<div class="tmss-note-bubble" style="align-self:flex-start;display:flex;align-items:center;gap:8px;padding:8px 12px;position:relative;min-width:200px;max-width:400px;">
        <i class="fas fa-paperclip" style="color:#10b981;flex-shrink:0;"></i>
        <a href="${fileUrl}" target="_blank" style="font-size:.8rem;color:#059669;flex:1;overflow:hidden;text-overflow:ellipsis;">${_hte(fileName)}</a>
        <span style="font-size:10px;color:#d1d5db;margin-left:auto;">${_hte(dateStr)}</span>
        <div style="position:relative;">
            <button ${toggleMenu} class="tmss-menu-btn"><i class="fas fa-ellipsis-v"></i></button>
            ${menuHTML()}
        </div>
    </div>`;
}

// ── Copy Text ─────────────────────────────────────────────────
window.htCopyText = function(encodedText) {
    try {
        const text = decodeURIComponent(encodedText);
        window.focus();
        setTimeout(() => {
            navigator.clipboard.writeText(text).then(() => htT('success','Copied!')).catch(() => {
                const ta=document.createElement('textarea'); ta.value=text; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); document.body.removeChild(ta); htT('success','Copied!');
            });
        }, 100);
    } catch(e) { htT('error','Copy failed'); }
};

// ── Copy Image ────────────────────────────────────────────────
window.htCopyImage = async function(url) {
    try {
        const res=await fetch(url,{credentials:'include'}); const blob=await res.blob();
        let finalBlob=blob;
        if (!blob.type.includes('png')) {
            const img=new Image(); img.src=URL.createObjectURL(blob);
            await new Promise(r=>{img.onload=r;});
            const canvas=document.createElement('canvas'); canvas.width=img.naturalWidth; canvas.height=img.naturalHeight;
            canvas.getContext('2d').drawImage(img,0,0);
            finalBlob=await new Promise(r=>canvas.toBlob(r,'image/png'));
            URL.revokeObjectURL(img.src);
        }
        window.focus(); await new Promise(r=>setTimeout(r,100));
        await navigator.clipboard.write([new ClipboardItem({'image/png':finalBlob})]);
        htT('success','Image copied!');
    } catch(e) { htT('error','Copy failed'); console.error(e); }
};

// ── Share File ────────────────────────────────────────────────
window.htShareFile = async function(url, fileName, mimeType) {
    try {
        const res=await fetch(url,{credentials:'include'});
        if (!res.ok) { htT('error','File not available'); return; }
        const blob=await res.blob();
        const file=new File([blob],fileName,{type:mimeType||blob.type});
        if (navigator.canShare&&navigator.canShare({files:[file]})) { await navigator.share({files:[file],title:fileName}); }
        else {
            const a=document.createElement('a'); a.href=URL.createObjectURL(blob); a.download=fileName;
            document.body.appendChild(a); a.click(); document.body.removeChild(a); URL.revokeObjectURL(a.href);
            htT('success','Downloaded!');
        }
    } catch(e) { if(e.name!=='AbortError') { htT('error','Share failed'); console.error(e); } }
};

// ── View Image Fullscreen ─────────────────────────────────────
window.htViewImg = function(url) {
    const ov=document.createElement('div');
    ov.style.cssText='position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:99999;display:flex;align-items:center;justify-content:center;cursor:zoom-out;';
    ov.innerHTML=`<img src="${url}" style="max-width:90vw;max-height:90vh;border-radius:8px;">`;
    ov.onclick=()=>ov.remove(); document.body.appendChild(ov);
};

// ── Edit Note ─────────────────────────────────────────────────
window.htEditNoteStart = function(sysId) {
    const note = window._ht.currentNotes?.find(n=>n.sys_id===sysId);
    if (!note) return;
    const creator = note.meta_data?.created_by_date?.user ?? note.created_by ?? '';
    if (creator && creator !== (window.CURRENT_USER??'')) { htT('error','Permission denied — শুধু creator edit করতে পারবে'); return; }
    const viewEl = document.getElementById(`ht-note-view-${sysId}`);
    if (!viewEl) return;
    const original = note.content ?? '';
    viewEl.innerHTML = `
        <textarea id="ht-edit-ta-${sysId}" rows="3"
            style="width:100%;border:1.5px solid #EA580C;border-radius:10px;padding:8px 10px;font-size:.85rem;color:#374151;resize:vertical;outline:none;font-family:inherit;">${_hte(original)}</textarea>
        <div style="display:flex;align-items:center;justify-content:flex-end;gap:6px;margin-top:6px;">
            <button onclick="htEditNoteCancel('${sysId}')" style="padding:5px 10px;font-size:.72rem;font-weight:600;background:#F3F4F6;color:#374151;border:none;border-radius:8px;cursor:pointer;">Cancel</button>
            <button onclick="htEditNoteSave('${sysId}')" style="padding:5px 10px;font-size:.72rem;font-weight:600;background:#EA580C;color:#fff;border:none;border-radius:8px;cursor:pointer;">Save</button>
        </div>`;
    const ta = document.getElementById(`ht-edit-ta-${sysId}`);
    if (ta) { ta.focus(); ta.setSelectionRange(ta.value.length, ta.value.length); }
};

window.htEditNoteCancel = function() { _htRenderNotes(window._ht.currentNotes); };

window.htEditNoteSave = async function(sysId) {
    const ta = document.getElementById(`ht-edit-ta-${sysId}`);
    const newContent = ta?.value.trim();
    if (!newContent) { htT('error','Note খালি রাখা যাবে না'); return; }
    try {
        const res = await fetch(window._ht.cfg.api.notes, {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ action:'update', note_sys_id:sysId, content:newContent }),
        });
        const json = await res.json();
        if (json.status==='success') { htT('success','Note updated!'); await htLoadNotes(); }
        else htT('error', json.message||'Update ব্যর্থ');
    } catch(e) { htT('error','Network error'); }
};

// ── Delete Note ───────────────────────────────────────────────
window.htDeleteNote = async function(sysId) {
    const note = window._ht.currentNotes?.find(n=>n.sys_id===sysId);
    const creator = note?.meta_data?.created_by_date?.user ?? note?.created_by ?? '';
    if (creator && creator !== (window.CURRENT_USER??'')) { htT('error','Permission denied — only the creator can delete'); return; }
    if (!confirm('Delete this note?')) return;
    try {
        const res = await fetch(window._ht.cfg.api.notes, {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ action:'delete', note_sys_id:sysId, work_sys_id:window._ht.cfg.workSysId }),
        });
        const json = await res.json();
        if (json.status==='success') { htT('success','Deleted'); await htLoadNotes(); }
        else htT('error', json.message||'Delete failed');
    } catch(e) { htT('error','Delete failed'); }
};

// ── Add Text Note ─────────────────────────────────────────────
window.htAddTextNote = async function() {
    const ta = document.getElementById('ht-note-text');
    const content = ta?.value.trim();
    if (!content) return;
    try {
        const res = await fetch(window._ht.cfg.api.notes, {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ action:'store', work_sys_id:window._ht.cfg.workSysId, service_slug:'hotel', board:'mindboard', content }),
        });
        const json = await res.json();
        if (json.status==='success') { if(ta){ta.value='';ta.style.height='auto';} await htLoadNotes(); }
        else htT('error', json.message||'Network error');
    } catch(e) { htT('error','Network error'); }
};

// ═══════════════════════════════════════════════════════════════
// GENERATE SUMMARY / QUOTATION FROM SELECTED NOTES
// ═══════════════════════════════════════════════════════════════
window._htGenGenerate = async function(mode) {
    const noteIds = [...window._ht.selectedNoteIds];
    if (!noteIds.length) { htT('error','কমপক্ষে একটা note select করুন'); return; }
    _htShowLoadingModal(mode);
    try {
        const genUrl = window._ht.cfg.api.hotelServices.replace('endpoints.php','generate-from-notes.php');
        const res = await fetch(genUrl, {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ work_sys_id:window._ht.cfg.workSysId, note_sys_ids:noteIds, action:mode }),
        });
        const json = await res.json();
        if (!json.success) { _htCloseModal(); htT('error', json.message||'Generate ব্যর্থ'); return; }
        _htRenderResultModal(mode, json);
    } catch(e) { _htCloseModal(); htT('error','Network error'); }
};

function _htShowLoadingModal(mode) {
    let ov = document.getElementById('ht-gen-modal-overlay');
    if (!ov) { ov=document.createElement('div'); ov.id='ht-gen-modal-overlay'; ov.style.cssText='position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9998;display:flex;align-items:center;justify-content:center;padding:16px;'; document.body.appendChild(ov); }
    const label = mode==='summary'?'Summary':'Hotel Quotation';
    ov.innerHTML=`<div style="background:#fff;border-radius:16px;padding:32px;text-align:center;min-width:280px;"><i class="fas fa-spinner fa-spin" style="font-size:24px;color:#EA580C;"></i><p style="margin-top:12px;font-size:13px;color:#4B5563;">Generating ${label}…</p></div>`;
    ov.style.display='flex';
}

function _htCloseModal() { const ov=document.getElementById('ht-gen-modal-overlay'); if(ov) ov.remove(); }

function _htRenderResultModal(mode, data) {
    const ov = document.getElementById('ht-gen-modal-overlay');
    if (!ov) return;
    // Store for save function
    if (data.quotation) window._htLastGeneratedQuotation = data.quotation;
    ov.innerHTML = `
    <div style="background:#fff;border-radius:16px;width:100%;max-width:720px;max-height:90vh;overflow-y:auto;display:flex;flex-direction:column;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #F1F5F9;flex-shrink:0;">
            <h3 style="font-size:15px;font-weight:700;color:#1F2937;margin:0;">
                <i class="fas fa-wand-magic-sparkles mr-2" style="color:#EA580C;"></i>Generated ${mode==='summary'?'Summary':'Hotel Quotation'}
            </h3>
            <button onclick="_htCloseModal()" style="background:none;border:none;color:#9CA3AF;cursor:pointer;font-size:16px;"><i class="fas fa-times"></i></button>
        </div>
        <div style="padding:20px;overflow-y:auto;flex:1;">
            ${mode==='summary' ? _htSummarySectionHtml(data.summary_text) : ''}
            ${mode==='quotation' ? _htQuotationSectionHtml(data.quotation, data) : ''}
        </div>
    </div>`;
}

function _htSummarySectionHtml(summaryText) {
    return `<div>
        <label style="font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;display:block;margin-bottom:6px;">Summary</label>
        <div id="ht-gen-summary-text" contenteditable="true"
            style="background:#F9FAFB;border:1px solid #E5E7EB;border-radius:10px;padding:12px 14px;font-size:13px;color:#374151;line-height:1.7;min-height:80px;white-space:pre-wrap;">${_hte(summaryText||'')}</div>
        <div style="display:flex;gap:8px;margin-top:10px;">
            <button onclick="_htSaveSummary()" style="padding:7px 14px;font-size:12px;font-weight:600;background:#fff;color:#EA580C;border:1px solid #FED7AA;border-radius:8px;cursor:pointer;">
                <i class="fas fa-save mr-1"></i>Save in Mindboard
            </button>
            <button onclick="_htGenGenerate('quotation')" style="padding:7px 14px;font-size:12px;font-weight:600;background:#EA580C;color:#fff;border:none;border-radius:8px;cursor:pointer;">
                <i class="fas fa-hotel mr-1"></i>Generate Hotel Quotation
            </button>
        </div>
    </div>`;
}

function _htQuotationSectionHtml(q, data) {
    if (!q) return '<p style="color:#9CA3AF;font-size:12px;">No quotation data</p>';
    return `<div>
        <label style="font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;display:block;margin-bottom:10px;">Hotel Quotation</label>
        <div style="background:#F9FAFB;border:1px solid #E5E7EB;border-radius:10px;padding:14px;font-size:13px;">
            ${q.hotel_name?`<div style="margin-bottom:6px;"><b>Hotel:</b> ${_hte(q.hotel_name)} ${q.star_rating?'★'.repeat(+q.star_rating):''}</div>`:''}
            ${q.city?`<div style="margin-bottom:6px;"><b>City:</b> ${_hte(q.city)}${q.country?', '+_hte(q.country):''}</div>`:''}
            ${q.check_in?`<div style="margin-bottom:6px;"><b>Check-in:</b> ${_hte(q.check_in)} → <b>Check-out:</b> ${_hte(q.check_out||'')} (${q.nights||0} nights)</div>`:''}
            ${q.room_type?`<div style="margin-bottom:6px;"><b>Room:</b> ${_hte(q.room_type)} × ${q.rooms||1}</div>`:''}
            ${q.meal_plan?`<div style="margin-bottom:6px;"><b>Meal Plan:</b> ${_hte(q.meal_plan)}</div>`:''}
            ${q.sell_rate?`<div style="margin-bottom:6px;font-weight:700;color:#EA580C;"><b>Rate:</b> ${_hte(q.currency||'BDT')} ${(+q.sell_rate).toLocaleString('en-BD')} / night</div>`:''}
        </div>
        ${data.masterdata_upsert ? `
        <div style="margin-top:10px;padding:8px 12px;border-radius:8px;font-size:11px;${
            data.masterdata_upsert.success
                ? 'background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;'
                : 'background:#fef2f2;border:1px solid #fecaca;color:#dc2626;'
        }">
            ${data.masterdata_upsert.success ? `
                <i class="fas fa-check-circle mr-1"></i>
                <b>Master Data:</b>
                Hotel ${data.masterdata_upsert.actions?.hotel || '—'},
                Room ${data.masterdata_upsert.actions?.room_type || '—'}
                ${data.masterdata_upsert.actions?.room_rate ? ', Rate ' + data.masterdata_upsert.actions.room_rate : ''}
            ` : `
                <i class="fas fa-exclamation-circle mr-1"></i>
                Master data upsert failed: ${_hte(data.masterdata_upsert.message || 'Unknown error')}
            `}
        </div>` : ''}

        <button onclick="_htSaveGeneratedQuotation()" style="margin-top:12px;width:100%;padding:10px;font-size:13px;font-weight:700;background:#EA580C;color:#fff;border:none;border-radius:10px;cursor:pointer;">
            <i class="fas fa-save mr-2"></i>Save as Quotation
        </button>
    </div>`;
}

window._htSaveSummary = async function() {
    const el = document.getElementById('ht-gen-summary-text');
    const text = el?.innerText.trim();
    if (!text) { htT('error','Summary খালি'); return; }
    try {
        const res = await fetch(window._ht.cfg.api.notes, {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ action:'store', work_sys_id:window._ht.cfg.workSysId, service_slug:'hotel', board:'mindboard', content:'📝 Summary:\n'+text }),
        });
        const json = await res.json();
        if (json.status==='success') { htT('success','Summary saved!'); window._htGenClearSelection(); _htCloseModal(); await htLoadNotes(); }
        else htT('error', json.message||'Save ব্যর্থ');
    } catch(e) { htT('error','Network error'); }
};

window._htSaveGeneratedQuotation = async function() {
    // Read the generated quotation data from the modal
    const q = window._htLastGeneratedQuotation;
    if (!q) { htT('error', 'No quotation data to save'); return; }

    const btn = document.querySelector('[onclick="_htSaveGeneratedQuotation()"]');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving…'; }

    try {
        const body = {
            action:           'save_quotation',
            quotation_sys_id: '',
            work_sys_id:      window._ht.cfg.workSysId || '',
            city_seg_id:      '',
            hotel_name:       q.hotel_name      || '',
            city:             q.city            || '',
            country:          q.country         || '',
            star_rating:      q.star_rating      || 0,
            check_in:         q.check_in        || '',
            check_out:        q.check_out       || '',
            nights:           q.nights          || 0,
            room_type:        q.room_type       || '',
            bed_config:       q.bed_config      || '',
            meal_plan:        q.meal_plan       || 'bb',
            rooms:            q.rooms           || 1,
            currency:         q.currency        || 'BDT',
            net_rate:         q.net_rate        || 0,
            sell_rate:        q.sell_rate       || 0,
            total_net:        Math.round((+(q.net_rate||0))  * (+(q.nights||1)) * (+(q.rooms||1))),
            total_sell:       Math.round((+(q.sell_rate||0)) * (+(q.nights||1)) * (+(q.rooms||1))),
            size_sqm:         q.size_sqm ?? null,
            max_occupancy:    q.max_adults || 2,
            note:             (q.notes || []).join('\n'),
            copy_text:        _htBuildCopyFromGenerated(q),
            source:           'ai_generated',
            source_note_ids:  [...(window._ht.selectedNoteIds || [])],
        };

        const res  = await fetch(window._ht.cfg.api.hotelServices, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body),
        });
        const json = await res.json();

        if (json.status === 'success' || json.success) {
            htT('success', 'Quotation saved!');
            window._htGenClearSelection();
            _htCloseModal();
            await window._htReload();
            // Switch to Quotation tab so user sees the new entry
            const qTab = document.querySelector('.ht-tab[data-tab="quotation"]');
            window._htSwitchTab('quotation', qTab);
        } else {
            htT('error', json.message || 'Save failed');
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-save mr-2"></i>Save as Quotation'; }
        }
    } catch(e) {
        htT('error', 'Network error');
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-save mr-2"></i>Save as Quotation'; }
    }
};

function _htBuildCopyFromGenerated(q) {
    const fmt = n => (+n||0).toLocaleString('en-BD');
    const mealLabels = { ro:'Room Only', bb:'Bed & Breakfast', hb:'Half Board', fb:'Full Board', ai:'All Inclusive' };
    const lines = ['*Hotel Quotation*'];
    if (q.hotel_name) lines.push('🏨 *' + q.hotel_name + '*' + (q.star_rating ? ' ' + '★'.repeat(+q.star_rating) : ''));
    const loc = [q.city, q.country].filter(Boolean).join(', ');
    if (loc) lines.push('📍 ' + loc);
    if (q.check_in) lines.push('📅 Check-in: *' + q.check_in + '*' + (q.check_out ? '  →  Check-out: *' + q.check_out + '*' : ''));
    if (q.nights)   lines.push('🌙 Duration: *' + q.nights + ' Night' + (q.nights > 1 ? 's' : '') + '*');
    if (q.rooms)    lines.push('🛏 Rooms: *' + q.rooms + '*' + (q.room_type ? ' — ' + q.room_type : ''));
    const meal = mealLabels[q.meal_plan] || q.meal_plan || '';
    if (meal) lines.push('🍽 Meal Plan: *' + meal + '*');
    lines.push('');
    lines.push('*Price:*');
    if (q.sell_rate) lines.push('- Per Room/Night: ' + (q.currency||'BDT') + ' ' + fmt(q.sell_rate));
    const total = Math.round((q.sell_rate||0) * (q.nights||1) * (q.rooms||1));
    if (total) lines.push('- Total: *' + (q.currency||'BDT') + ' ' + fmt(total) + '*');
    return lines.join('\n');
}

// ═══════════════════════════════════════════════════════════════
// HOTEL PORTAL PANEL
// ═══════════════════════════════════════════════════════════════
window._htPassVisible   = {};
window._htPortalsCache  = [];
window._htExpandedPortal = null;

async function _htLoadPortals() {
    const body = document.getElementById('ht-portal-body');
    if (!body) return;
    try {
        const res  = await fetch('/api/masterdata/portal-links.php?portal_type=hotel', { credentials:'include' });
        const json = await res.json();
        if (!json.success) { body.innerHTML=`<div style="padding:12px;color:#E8817A;font-size:12px;">Error loading portals</div>`; return; }
        const portals = json.portals ?? [];
        window._htPortalsCache = portals;
        if (!portals.length) {
            body.innerHTML=`<div style="padding:12px;color:#8A93A8;font-size:12px;">No Hotel portals yet.<br><a href="../pages/portal-links.php" target="_blank" style="color:#F97316;">Add one in Master Data →</a></div>`;
            return;
        }
        _htRenderPortalGrid(body);
    } catch(e) { const b=document.getElementById('ht-portal-body'); if(b) b.innerHTML='<div style="padding:12px;color:#E8817A;font-size:12px;">Network error</div>'; }
}

function _htRenderPortalGrid(container) {
    const portals = window._htPortalsCache;
    container.innerHTML = `<div style="padding:8px;display:grid;grid-template-columns:repeat(2,1fr);gap:6px;">
        ${portals.map(p => window._htExpandedPortal===p.sys_id
            ? `<div style="grid-column:1/-1;">${_htPortalCardExpanded(p)}</div>`
            : _htPortalCardCollapsed(p)
        ).join('')}
    </div>`;
}

function _hte(s) { return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }

function _htPortalCardCollapsed(p) {
    const creds=p.credentials??[]; const mine=creds.find(c=>c.is_mine)||creds[0]; const url=p.portal_url?_hte(p.portal_url):'';
    return `<div style="background:#1C2340;border:1px solid rgba(255,255,255,.08);border-radius:6px;padding:10px 12px;cursor:pointer;"
        onclick="_htExpandPortal('${_hte(p.sys_id)}')"
        onmouseover="this.style.borderColor='rgba(249,115,22,.4)'"
        onmouseout="this.style.borderColor='rgba(255,255,255,.08)'">
        <div style="display:flex;align-items:center;gap:6px;margin-bottom:${mine?'6px':'0'};">
            <span style="color:#fff;font-size:12px;font-weight:700;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${_hte(p.portal_name)}</span>
            ${url?`<a href="${url}" target="_blank" onclick="event.stopPropagation()" style="color:#F97316;font-size:10px;flex-shrink:0;"><i class="fas fa-external-link-alt"></i></a>`:''}
        </div>
        ${mine?`<div style="color:#5E6883;font-size:10px;font-family:monospace;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${_hte(mine.user_name||'')}</div>`:''}
    </div>`;
}

function _htPortalCardExpanded(p) {
    const creds=p.credentials??[]; const url=p.portal_url?_hte(p.portal_url):'';
    const rows=(creds.filter(c=>c.can_view!==false)).map(c=>{
        const key=p.sys_id+'_'+(c.cred_id||c.user_name); const shown=!!window._htPassVisible[key];
        return `<div style="display:flex;flex-direction:column;gap:2px;padding:8px 0;border-top:1px solid rgba(255,255,255,.06);">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
                <span style="color:#8A93A8;font-size:10px;text-transform:uppercase;letter-spacing:.05em;flex-shrink:0;">User ID</span>
                <span style="color:#E8ECF5;font-size:12px;font-family:monospace;cursor:pointer;"
                    onclick="event.stopPropagation();navigator.clipboard?.writeText('${_hte(c.user_name||'')}');this.style.color='#F97316';setTimeout(()=>this.style.color='#E8ECF5',800);">${_hte(c.user_name||'—')}</span>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
                <span style="color:#8A93A8;font-size:10px;text-transform:uppercase;letter-spacing:.05em;flex-shrink:0;">Password</span>
                <div style="display:flex;align-items:center;gap:6px;">
                    <span style="color:#E8ECF5;font-size:12px;font-family:monospace;cursor:pointer;"
                        onclick="event.stopPropagation();navigator.clipboard?.writeText('${_hte(c.password||'')}');this.style.color='#F97316';setTimeout(()=>this.style.color='#E8ECF5',800);">${shown?_hte(c.password||'—'):'••••••••'}</span>
                    <button onclick="event.stopPropagation();_htTogglePass('${_hte(p.sys_id)}','${_hte(c.cred_id||c.user_name)}')"
                        style="background:none;border:none;color:#5E6883;cursor:pointer;padding:2px 4px;font-size:11px;">
                        <i class="fas ${shown?'fa-eye-slash':'fa-eye'}"></i>
                    </button>
                </div>
            </div>
        </div>`;
    }).join('')||`<div style="padding:8px 0;color:#5E6883;font-size:11px;">No credentials</div>`;
    return `<div style="background:#1C2340;border:1px solid rgba(249,115,22,.4);border-radius:6px;padding:12px 14px;">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
            <span style="color:#fff;font-size:13px;font-weight:700;flex:1;">${_hte(p.portal_name)}</span>
            ${url?`<a href="${url}" target="_blank" style="color:#F97316;font-size:11px;"><i class="fas fa-external-link-alt"></i></a>`:''}
            <button onclick="event.stopPropagation();_htCollapsePortal()" style="background:none;border:none;color:#5E6883;cursor:pointer;font-size:11px;padding:2px 6px;"><i class="fas fa-compress-alt"></i></button>
        </div>${rows}</div>`;
}

window._htExpandPortal   = s  => { window._htExpandedPortal=s;    const b=document.getElementById('ht-portal-body'); if(b) _htRenderPortalGrid(b); };
window._htCollapsePortal = () => { window._htExpandedPortal=null; const b=document.getElementById('ht-portal-body'); if(b) _htRenderPortalGrid(b); };
window._htTogglePass     = (pid,cid) => { const k=pid+'_'+cid; window._htPassVisible[k]=!window._htPassVisible[k]; const b=document.getElementById('ht-portal-body'); if(b) _htRenderPortalGrid(b); };

function _htInitPortalDivider(divider, panel) {
    let drag=false, sx=0, sw=0;
    divider.addEventListener('mousedown', e=>{drag=true;sx=e.clientX;sw=panel.offsetWidth;document.body.style.cursor='col-resize';document.body.style.userSelect='none';});
    document.addEventListener('mousemove', e=>{if(!drag)return;const nw=Math.max(200,Math.min(500,sw-(e.clientX-sx)));panel.style.width=nw+'px';localStorage.setItem('ht_portal_width',nw);});
    document.addEventListener('mouseup', ()=>{if(!drag)return;drag=false;document.body.style.cursor='';document.body.style.userSelect='';});
}