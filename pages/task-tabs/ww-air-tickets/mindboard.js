/**
 * FILE PATH: /pages/task-tabs/ww-air-tickets/mindboard.js
 * Tab: Mindboard
 */

// ═══════════════════════════════════════════════════════════════
// TAB 1 — MIND BOARD
// ═══════════════════════════════════════════════════════════════
window._renderMindboard = function _renderMindboard() {
    const panel = document.getElementById('at-panel-mindboard');
    panel.style.cssText = 'display:flex;flex-direction:column;padding:0;';

    panel.innerHTML = `
    <!-- Chat bubbles (scrollable) -->
    <div id="at-notes-list" style="flex:1;overflow-y:auto;padding:12px 14px;display:flex;flex-direction:column;gap:8px;min-height:300px;max-height:calc(100vh - 350px);">
        <div class="text-center py-6 text-gray-300 text-sm"><i class="fas fa-spinner fa-spin"></i></div>
    </div>

    <!-- Upload progress -->
    <div id="at-upload-prog" class="hidden px-4 py-2 bg-indigo-50 border-t border-indigo-100 text-sm text-indigo-600" style="flex-shrink:0;">
        <i class="fas fa-spinner fa-spin mr-2"></i>Uploading…
    </div>

    <!-- Input bar -->
    <div style="flex-shrink:0;border-top:1px solid #f1f5f9;background:#fff;padding:10px 12px;">
        <!-- STT Panel -->
        <div id="at-stt-panel" style="display:none;margin-bottom:8px;background:#eef2ff;border:1px solid #c7d2fe;border-radius:12px;padding:10px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                <span id="at-stt-status" style="font-size:.75rem;color:#4f46e5;font-weight:600;">Ready</span>
                <select id="at-stt-lang" style="font-size:.72rem;border:1px solid #c7d2fe;border-radius:6px;padding:2px 6px;background:#fff;color:#374151;">
                    <option value="bn-BD">বাংলা</option>
                    <option value="en-US">English</option>
                </select>
            </div>
            <div id="at-stt-preview" contenteditable="true" style="min-height:36px;background:#fff;border:1px solid #e0e7ff;border-radius:8px;padding:6px 10px;font-size:.82rem;color:#374151;margin-bottom:6px;white-space:pre-wrap;"></div>
            <div style="display:flex;gap:6px;margin-bottom:6px;">
                <button id="at-stt-start" onclick="atSttStart()" style="flex:1;padding:5px;font-size:.72rem;background:#4f46e5;color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:600;"><i class="fas fa-play"></i> Start</button>
                <button id="at-stt-pause" onclick="atSttPause()" disabled style="flex:1;padding:5px;font-size:.72rem;background:#f3f4f6;color:#9ca3af;border:none;border-radius:8px;cursor:pointer;font-weight:600;">⏸ Pause</button>
                <button id="at-stt-stop" onclick="atSttStop()" disabled style="flex:1;padding:5px;font-size:.72rem;background:#f3f4f6;color:#9ca3af;border:none;border-radius:8px;cursor:pointer;font-weight:600;"><i class="fas fa-stop"></i> Stop</button>
            </div>
            <button onclick="atSttPush()" style="width:100%;padding:5px;font-size:.72rem;background:#059669;color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:600;"><i class="fas fa-arrow-down"></i> Push to Note</button>
        </div>
        <!-- Multi-file preview -->
        <div id="at-multi-preview" style="display:none;margin-bottom:6px;flex-wrap:wrap;gap:4px;"></div>
        <!-- File preview chip -->
        <div id="at-file-preview" class="hidden mb-2 bg-indigo-50 rounded-lg px-3 py-1.5 text-xs text-indigo-600 flex items-center gap-2">
            <i class="fas fa-paperclip flex-shrink-0"></i>
            <span id="at-file-preview-name" class="flex-1 truncate"></span>
            <button onclick="atClearFile()" class="text-red-400 hover:text-red-600 flex-shrink-0"><i class="fas fa-times"></i></button>
        </div>
        <div class="flex items-end gap-1.5">
            <button onclick="atSttToggle()" title="Voice to Text" style="width:32px;height:32px;background:#eef2ff;border:none;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-keyboard" style="color:#4f46e5;font-size:.75rem;"></i>
            </button>
            <button id="at-rec-btn" onclick="atRecToggle()" title="Record Audio" style="width:32px;height:32px;background:#fdf2f8;border:none;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-microphone" style="color:#db2777;font-size:.75rem;"></i>
            </button>
            <label style="width:32px;height:32px;background:#eff6ff;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0;" title="Images">
                <i class="fas fa-image" style="color:#3b82f6;font-size:.75rem;"></i>
                <input type="file" class="hidden" accept="image/*" multiple onchange="atFilesSelected(this)">
            </label>
            <label style="width:32px;height:32px;background:#f3f4f6;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0;" title="Files">
                <i class="fas fa-paperclip" style="color:#6b7280;font-size:.75rem;"></i>
                <input type="file" class="hidden" multiple onchange="atFilesSelected(this)">
            </label>
            <textarea id="at-note-text" rows="1"
                placeholder="Write a note… (Enter to send)"
                style="flex:1;resize:none;border:1.5px solid #e5e7eb;border-radius:20px;padding:8px 14px;font-size:.83rem;outline:none;max-height:80px;overflow-y:auto;transition:border .15s;"
                onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();atSendNote();}"
                oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,80)+'px'"></textarea>
            <button onclick="atSendNote()" style="width:36px;height:36px;background:#4f46e5;border:none;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-paper-plane" style="color:#fff;font-size:.82rem;"></i>
            </button>
        </div>
    </div>`;

    // ── Setup paste, drag & drop for textarea ──────────────────
    const ta = document.getElementById('at-note-text');
    if (ta) {
        // Paste support for images
        ta.addEventListener('paste', function(e) {
            const items = e.clipboardData?.items;
            if (!items) return;
            for (const item of items) {
                if (item.type.startsWith('image/')) {
                    e.preventDefault();
                    const file = item.getAsFile();
                    window._at.pendingFile = file;
                    document.getElementById('at-file-preview').classList.remove('hidden');
                    document.getElementById('at-file-preview-name').textContent = file.name + ' (pasted)';
                    break;
                }
            }
        });

        // Drag & drop support
        ta.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.borderColor = '#6366f1';
            this.style.borderStyle = 'solid';
        });
        ta.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.style.borderColor = '#e5e7eb';
            this.style.borderStyle = 'solid';
        });
        ta.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.borderColor = '#e5e7eb';
            this.style.borderStyle = 'solid';
            const files = e.dataTransfer.files;
            if (files.length) {
                window._at.pendingFile = files[0];
                document.getElementById('at-file-preview').classList.remove('hidden');
                document.getElementById('at-file-preview-name').textContent = window._at.pendingFile.name;
            }
        });
    }

    // ── Close menus — handled by _attachMenuDelegation IIFE ──
    atLoadNotes();

    // ── GDS Panel: wrap mindboard in flex row ─────────────────
    // ⚠️ এই panel gds-panel.js-এর _gdsInjectPanel() ব্যবহার করে না — নিজের
    // নিজস্ব HTML বানায় (Quotation/Booking/Confirmation থেকে আলাদা কোড path)।
    // তাই GDS/Portal sub-tab bar এখানে আলাদাভাবে বসাতে হচ্ছে
    // (_gdsSubTabsHtml('1') — gds-panel.js-এর একই helper reuse করে)।
    const mbPanel = document.getElementById('at-panel-mindboard');
    if (mbPanel && !mbPanel.querySelector('#at-gds-panel')) {
        const saved = localStorage.getItem('at_gds_width');
        const gdsW  = saved ? saved + 'px' : '340px';
        const wrapper = document.createElement('div');
        wrapper.style.cssText = 'display:flex;height:100%;';
        // Move panel children into left div
        const leftDiv = document.createElement('div');
        leftDiv.style.cssText = 'flex:1;min-width:0;display:flex;flex-direction:column;overflow:hidden;';
        while (mbPanel.firstChild) leftDiv.appendChild(mbPanel.firstChild);
        // Divider
        const divider = document.createElement('div');
        divider.id = 'at-gds-divider';
        divider.style.cssText = 'width:4px;background:#f1f5f9;cursor:col-resize;flex-shrink:0;transition:background .15s;';
        divider.onmouseover = () => divider.style.background = '#6366f1';
        divider.onmouseout  = () => divider.style.background = '#f1f5f9';
        // GDS panel
        const gdsDiv = document.createElement('div');
        gdsDiv.id = 'at-gds-panel-1';
        gdsDiv.style.cssText = `width:${gdsW};flex-shrink:0;background:#12172B;display:flex;flex-direction:column;overflow-y:auto;min-height:300px;max-height:calc(100vh - 350px);;`;
        gdsDiv.innerHTML = `
            <div style="background:#1C2340;padding:11px 14px;border-bottom:1px solid rgba(255,255,255,.08);display:flex;align-items:center;gap:8px;flex-shrink:0;">
                <div style="flex:1;">
                    <div style="color:#fff;font-size:12px;font-weight:700;">GDS Commands</div>
                    <div style="color:#50BC81;font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;">Stage — Research</div>
                </div>
                <button id="at-gds-notes-btn" onclick="_gdsToggleNotes()" style="font-size:10px;font-weight:600;padding:4px 8px;border-radius:2px;border:1px solid rgba(255,255,255,.2);background:transparent;color:rgba(255,255,255,.7);cursor:pointer;">Notes on</button>
            </div>
            ${_gdsSubTabsHtml('1')}
            <div id="at-gds-body-1" style="overflow-y:auto;flex:1;">
                <div style="padding:10px 10px 0;">${_gdsDerivedFactsHtml(_gdsGetDerivedFacts())}</div>
            </div>`;
        const gdsBody = gdsDiv.querySelector('#at-gds-body-1');
        const cmdHtml = window._at.data?.commands?.mindboard
            ? _gdsRenderStoredCommands(window._at.data.commands.mindboard)
            : _gdsCommandsHtml('1', _gdsGetDerivedFacts());
        gdsBody.insertAdjacentHTML('beforeend', cmdHtml);
        wrapper.appendChild(leftDiv);
        wrapper.appendChild(divider);
        wrapper.appendChild(gdsDiv);
        mbPanel.style.cssText = 'display:flex;flex-direction:column;padding:0;overflow:hidden;';
        mbPanel.appendChild(wrapper);
        _gdsInitDivider(divider, gdsDiv);
    }
}

// ── File select handler ──────────────────────────────────────
window._at.pendingFile = null;
// Multi-file selection
window._at.pendingFiles = [];
window.atFilesSelected = function(input) {
    if (!input.files.length) return;
    window._at.pendingFiles = Array.from(input.files);
    const preview = document.getElementById('at-multi-preview');
    preview.style.display = 'flex';
    preview.innerHTML = window._at.pendingFiles.map(f =>
        `<span style="display:inline-flex;align-items:center;gap:4px;background:#f3f4f6;border-radius:20px;padding:2px 8px;font-size:.72rem;color:#374151;"><i class="fas fa-file" style="font-size:.65rem;"></i>${f.name.length>20?f.name.slice(0,18)+'…':f.name}</span>`
    ).join('');
    input.value = '';
};

// Legacy single-file support
window.atFileSelected = function(input) {
    if (!input.files[0]) return;
    atFilesSelected(input);
};

window.atClearFile = function() {
    window._at.pendingFiles = [];
    document.getElementById('at-file-preview').classList.add('hidden');
    const mp = document.getElementById('at-multi-preview');
    if (mp) { mp.style.display = 'none'; mp.innerHTML = ''; }
};

// atSendNote — unified send (text or files)
window.atSendNote = async function() {
    if (window._at.pendingFiles.length > 0) {
        // Upload all pending files
        const files = [...window._at.pendingFiles];
        atClearFile();
        for (const file of files) {
            const fd = new FormData();
            fd.append('action', 'upload');
            fd.append('work_sys_id', window._at.cfg.workSysId);
            fd.append('service_slug', window._at.cfg.serviceSlug ?? 'air_ticket');
            fd.append('board', 'mindboard');
            fd.append('file', file);
            try {
                const res = await fetch(window._at.cfg.api.notes, { method: 'POST', body: fd });
                await res.json();
            } catch(e) { console.error('File upload error:', e); }
        }
        await atLoadNotes();
        return;
    }
    // Text note
    atAddTextNote();
};

// ── Voice Recording ──────────────────────────────────────────
window._at.recorder = null, window._at.recChunks = [], window._at.recording = false;
window.atRecToggle = async function() {
    if (window._at.recording) {
        window._at.recorder?.stop();
        return;
    }
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        window._at.recChunks = [];
        window._at.recorder = new MediaRecorder(stream);
        window._at.recorder.ondataavailable = e => { if (e.data.size > 0) window._at.recChunks.push(e.data); };
        window._at.recorder.onstop = async () => {
            stream.getTracks().forEach(t => t.stop());
            const blob = new Blob(window._at.recChunks, { type: 'audio/webm' });
            const btn = document.getElementById('at-rec-btn');
            if (btn) { btn.style.background = '#fdf2f8'; btn.innerHTML = '<i class="fas fa-microphone" style="color:#db2777;font-size:.75rem;"></i>'; }
            window._at.recording = false;
            // Upload
            const fd = new FormData();
            fd.append('action', 'upload');
            fd.append('work_sys_id', window._at.cfg.workSysId);
            fd.append('service_slug', window._at.cfg.serviceSlug ?? 'air_ticket');
            fd.append('board', 'mindboard');
            fd.append('file', blob, 'voice_' + Date.now() + '.webm');
            try {
                const res = await fetch(window._at.cfg.api.notes, { method: 'POST', body: fd });
                const j = await res.json();
                if (j.status === 'success') await atLoadNotes();
            } catch(e) { console.error('Voice upload error:', e); }
        };
        window._at.recorder.start();
        window._at.recording = true;
        const btn = document.getElementById('at-rec-btn');
        if (btn) { btn.style.background = '#fee2e2'; btn.innerHTML = '<i class="fas fa-stop" style="color:#dc2626;font-size:.75rem;"></i>'; }
    } catch(e) {
        console.error('Mic error:', e);
        alert('Microphone access denied');
    }
};

// ── STT Module ───────────────────────────────────────────────
window._at.sttRec = null, window._at.sttFinal = '', window._at.sttActive = false, window._at.sttPaused = false;
window.atSttToggle = function() {
    const p = document.getElementById('at-stt-panel');
    if (p) p.style.display = p.style.display === 'none' ? 'block' : 'none';
};
window.atSttStart = function() {
    if (!('webkitSpeechRecognition' in window || 'SpeechRecognition' in window)) { alert('Use Chrome for voice input'); return; }
    if (!window._at.sttRec) {
        const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
        window._at.sttRec = new SR();
        window._at.sttRec.continuous = true; window._at.sttRec.interimResults = true;
        window._at.sttRec.onresult = e => {
            let interim = '';
            for (let i = e.resultIndex; i < e.results.length; i++) {
                if (e.results[i].isFinal) window._at.sttFinal += e.results[i][0].transcript + ' ';
                else interim += e.results[i][0].transcript;
            }
            const el = document.getElementById('at-stt-preview');
            if (el) el.innerText = window._at.sttFinal + interim;
        };
        window._at.sttRec.onend = () => { if (window._at.sttActive && !window._at.sttPaused) { window._at.sttRec.lang = document.getElementById('at-stt-lang')?.value || 'bn-BD'; window._at.sttRec.start(); } };
    }
    window._at.sttFinal = ''; window._at.sttActive = true; window._at.sttPaused = false;
    window._at.sttRec.lang = document.getElementById('at-stt-lang')?.value || 'bn-BD';
    window._at.sttRec.start();
    const el = document.getElementById('at-stt-preview'); if (el) el.innerText = '';
    document.getElementById('at-stt-start')?.setAttribute('disabled', true);
    document.getElementById('at-stt-pause')?.removeAttribute('disabled');
    document.getElementById('at-stt-stop')?.removeAttribute('disabled');
    const s = document.getElementById('at-stt-status'); if (s) s.textContent = '🔴 Recording...';
};
window.atSttPause = function() {
    const btn = document.getElementById('at-stt-pause');
    const s = document.getElementById('at-stt-status');
    if (!window._at.sttPaused) {
        window._at.sttPaused = true; window._at.sttRec?.stop();
        if (btn) btn.textContent = '▶ Resume';
        if (s) s.textContent = '⏸ Paused';
    } else {
        window._at.sttPaused = false; window._at.sttRec.lang = document.getElementById('at-stt-lang')?.value || 'bn-BD'; window._at.sttRec?.start();
        if (btn) btn.textContent = '⏸ Pause';
        if (s) s.textContent = '🔴 Recording...';
    }
};
window.atSttStop = function() {
    window._at.sttActive = false; window._at.sttPaused = false; window._at.sttRec?.stop();
    document.getElementById('at-stt-start')?.removeAttribute('disabled');
    document.getElementById('at-stt-pause')?.setAttribute('disabled', true);
    document.getElementById('at-stt-stop')?.setAttribute('disabled', true);
    const btn = document.getElementById('at-stt-pause'); if (btn) btn.textContent = '⏸ Pause';
    const s = document.getElementById('at-stt-status'); if (s) s.textContent = '✅ Done';
};
window.atSttPush = function() {
    const txt = (window._at.sttFinal || document.getElementById('at-stt-preview')?.innerText || '').trim();
    if (!txt) return;
    const ta = document.getElementById('at-note-text');
    if (ta) ta.value = (ta.value ? ta.value + ' ' : '') + txt;
    document.getElementById('at-stt-panel').style.display = 'none';
    window._at.sttFinal = '';
};

// ── Load notes ────────────────────────────────────────────────
window.atLoadNotes = async function() {
    try {
        const slug = window._at.cfg.serviceSlug ?? 'air_ticket';
        const url  = `${window._at.cfg.api.notes}?action=list&work_sys_id=${encodeURIComponent(window._at.cfg.workSysId)}&service_slug=${encodeURIComponent(slug)}&board=mindboard`;
        const res  = await fetch(url);
        const json = await res.json();
        const notes = json.status === 'success' ? (json.data ?? []) : [];
        window._at.currentNotes = notes;
        _renderNotes(notes);
    } catch(e) {
        console.error('[atLoadNotes] error:', e);
        _renderNotes([]);
    }
};

window._renderNotes = function _renderNotes(notes) {
    const list = document.getElementById('at-notes-list');
    if (!list) return;
    if (!notes.length) {
        list.innerHTML = '<div class="text-center py-8 text-gray-300 text-xs">No notes yet. Write something below.</div>';
        return;
    }
    const html = notes.map(n => {
        try { return _noteBubble(n); }
        catch(e) { console.error('[_noteBubble] error for', n.sys_id, e); return ''; }
    }).join('');
    list.innerHTML = html;
    setTimeout(() => { list.scrollTop = list.scrollHeight; }, 50);
}

// ── Note Bubble with Three-Dot Menu ──────────────────────────
window._noteBubble = function _noteBubble(n) {
    const dateStr  = n.meta_data?.created_by_date?.date ?? '';
    const creator  = n.meta_data?.created_by_date?.user ?? n.created_by ?? '';
    const canDel   = !creator || creator === (window.CURRENT_USER ?? '');
    const sysId    = _e(n.sys_id);
    const content  = n.content ?? '';
    const fileUrl  = n.serve_url ?? n.file_url ?? '';
    const fileName = n.file_name ?? '';

    // ── Menu HTML ──────────────────────────────────────────────
    function menuHTML() {
        const itemStyle   = 'display:flex;align-items:center;gap:10px;padding:8px 16px;font-size:.78rem;font-weight:500;color:#374151;cursor:pointer;border:none;background:none;width:100%;text-align:left;';
        const dangerStyle = 'display:flex;align-items:center;gap:10px;padding:8px 16px;font-size:.78rem;font-weight:500;color:#dc2626;cursor:pointer;border:none;background:none;width:100%;text-align:left;';
        let items = '';

        if (n.note_type === 'text') {
            const enc = encodeURIComponent(content);
            items += `<button onclick="atCopyText('${enc}')" style="${itemStyle}"><i class="fas fa-copy" style="width:16px;font-size:.75rem;color:#94a3b8;"></i>Copy</button>`;

        } else if (n.note_type === 'image') {
            items += `<button onclick="atCopyImage('${fileUrl}')" style="${itemStyle}"><i class="fas fa-copy" style="width:16px;font-size:.75rem;color:#94a3b8;"></i>Copy Image</button>`;
            items += `<button onclick="atShareFile('${fileUrl}','${_e(fileName)}','image/jpeg')" style="${itemStyle}"><i class="fas fa-share-alt" style="width:16px;font-size:.75rem;color:#94a3b8;"></i>Share</button>`;

        } else if (n.note_type === 'pdf_images') {
            // Share button আলাদাভাবে bubble এ আছে — 3-dot এ শুধু Delete

        } else if (n.note_type === 'audio') {
            items += `<button onclick="atShareFile('${fileUrl}','${_e(fileName)}','audio/webm')" style="${itemStyle}"><i class="fas fa-share-alt" style="width:16px;font-size:.75rem;color:#94a3b8;"></i>Share</button>`;

        } else {
            items += `<button onclick="atShareFile('${fileUrl}','${_e(fileName)}','')" style="${itemStyle}"><i class="fas fa-share-alt" style="width:16px;font-size:.75rem;color:#94a3b8;"></i>Share</button>`;
        }

        if (canDel) items += `<button onclick="atDeleteNote('${sysId}')" style="${dangerStyle}"><i class="fas fa-trash" style="width:16px;font-size:.75rem;color:#f87171;"></i>Delete</button>`;
        return `<div class="at-menu-dropdown" id="at-menu-${sysId}">${items}</div>`;
    }

    const toggleMenu = `data-menu-toggle="${sysId}"`;

    // ── Text Note ──────────────────────────────────────────────
    if (n.note_type === 'text') {
        // Calculate approximate width based on content length
        const contentLength = content.length;
        let minWidth = '120px';
        let maxWidth = '85%';
        
        if (contentLength < 20) {
            minWidth = '320px';
        } else if (contentLength < 50) {
            minWidth = '480px';
        } else {
            minWidth = '640px';
        }
        
        return `<div class="at-note-bubble at-note-text" style="align-self:flex-start;min-width:${minWidth};max-width:${maxWidth};">
            <div class="text-sm text-gray-700 whitespace-pre-line" style="word-wrap:break-word;">${_e(content)}</div>
            <div class="flex items-center justify-between mt-1.5">
                <span class="text-[10px] text-gray-300">${_e(dateStr)}</span>
                <div style="position:relative;">
                    <button ${toggleMenu} class="at-menu-btn"><i class="fas fa-ellipsis-v"></i></button>
                    ${menuHTML()}
                </div>
            </div>
        </div>`;
    }

    // ── Image Note ─────────────────────────────────────────────
    if (n.note_type === 'pdf_images') {
        const pagesRaw = n.pages_json ?? [];
        const pages = Array.isArray(pagesRaw) ? pagesRaw : (typeof pagesRaw === 'string' ? JSON.parse(pagesRaw) : []);
        const serveBase = window._at.cfg.api.fileServe
            ?? window._at.cfg.api.notes.replace('api/works/notes.php', 'api/file/serve.php')
                             .replace('api/tasks/notes.php', 'api/file/serve.php');

        const pagesHtml = pages.map((pg, i) => {
            const pgUrl = `${serveBase}?note_id=${encodeURIComponent(n.sys_id)}&page=${i}`;
            return `<div style="position:relative;margin-bottom:6px;border-radius:8px;overflow:hidden;background:#f9fafb;">
                <img src="${pgUrl}" loading="lazy" onclick="atViewImg('${pgUrl}')"
                    style="width:100%;display:block;cursor:zoom-in;border-radius:8px;">
                <!-- per-page actions -->
                <div style="position:absolute;top:6px;right:6px;display:flex;gap:4px;">
                    <button onclick="atCopyPageImage('${pgUrl}')" title="Copy"
                        style="width:26px;height:26px;background:rgba(255,255,255,.9);border:none;border-radius:6px;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 1px 4px rgba(0,0,0,.15);">
                        <i class="fas fa-copy" style="font-size:.65rem;color:#4f46e5;"></i>
                    </button>
                    <button onclick="atDeletePage('${_e(n.sys_id)}',${i})" title="Delete page"
                        style="width:26px;height:26px;background:rgba(255,255,255,.9);border:none;border-radius:6px;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 1px 4px rgba(0,0,0,.15);">
                        <i class="fas fa-trash" style="font-size:.65rem;color:#dc2626;"></i>
                    </button>
                </div>
                <div style="position:absolute;bottom:4px;left:6px;font-size:.6rem;color:rgba(0,0,0,.4);font-weight:600;">P${i+1}</div>
            </div>`;
        }).join('');

        return `<div class="at-note-bubble at-note-image" style="position:relative;align-self:flex-start;max-width:780px;" id="pdf-note-${_e(n.sys_id)}">
            <div class="flex items-center justify-between mb-1.5">
                <div class="text-[10px] text-gray-400">
                    <i class="fas fa-file-pdf text-red-400 mr-1"></i>
                    ${_e(n.file_name??'')} (${pages.length} page${pages.length!==1?'s':''})
                </div>
                <div style="display:flex;align-items:center;gap:4px;">
                    <button onclick="atSharePdfPages('${_e(n.sys_id)}')" title="Share as PDF"
                        style="padding:3px 8px;font-size:.68rem;font-weight:600;background:#eef2ff;color:#4f46e5;border:1px solid #c7d2fe;border-radius:6px;cursor:pointer;display:flex;align-items:center;gap:4px;">
                        <i class="fas fa-share-alt" style="font-size:.6rem;"></i>Share
                    </button>
                    <div style="position:relative;">
                        <button ${toggleMenu} class="at-menu-btn"><i class="fas fa-ellipsis-v"></i></button>
                        ${menuHTML()}
                    </div>
                </div>
            </div>
            ${pagesHtml}
            ${n.content ? `<div class="text-xs text-gray-500 mt-1 px-1">${_e(n.content)}</div>` : ''}
            <div class="mb-time mt-1">${_e(dateStr)}</div>
        </div>`;
    }
    if (n.note_type === 'image') {
        return `<div class="at-note-bubble at-note-image" style="align-self:flex-start;max-width:780px;position:relative;">
            <img src="${fileUrl}" loading="lazy" onclick="atViewImg('${fileUrl}')"
                style="width:100%;max-height:280px;object-fit:contain;border-radius:8px;cursor:zoom-in;display:block;background:#f9fafb;">
            ${content ? `<div class="text-xs text-gray-500 mt-1 px-1">${_e(content)}</div>` : ''}
            <div class="flex items-center justify-between mt-1">
                <span class="text-[10px] text-gray-300">${_e(dateStr)}</span>
                <div style="position:relative;">
                    <button ${toggleMenu} class="at-menu-btn"><i class="fas fa-ellipsis-v"></i></button>
                    ${menuHTML()}
                </div>
            </div>
        </div>`;
    }

    // ── Audio Note ─────────────────────────────────────────────
    if (n.note_type === 'audio') {
        return `<div class="at-note-bubble at-note-audio" style="align-self:flex-start;min-width:380px;max-width:720px;position:relative;">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="fas fa-microphone text-purple-400 text-xs"></i>
                    <span class="text-xs text-gray-600">${_e(fileName)}</span>
                </div>
                <div style="position:relative;">
                    <button ${toggleMenu} class="at-menu-btn"><i class="fas fa-ellipsis-v"></i></button>
                    ${menuHTML()}
                </div>
            </div>
            <audio controls class="w-full mt-1" style="height:32px;" src="${fileUrl}"></audio>
            <div class="text-[10px] text-gray-300 mt-1">${_e(dateStr)}</div>
        </div>`;
    }

    // ── Video Note ─────────────────────────────────────────────
    if (n.note_type === 'video') {
        return `<div class="at-note-bubble at-note-audio" style="align-self:flex-start;max-width:780px;position:relative;">
            <div style="position:relative;">
                <video controls style="max-height:160px;width:100%;border-radius:8px;" src="${fileUrl}"></video>
                <button ${toggleMenu} class="at-menu-btn" style="position:absolute;top:8px;right:8px;background:rgba(0,0,0,.5);color:white;border-radius:50%;width:28px;height:28px;display:flex;align-items:center;justify-content:center;border:none;z-index:102;">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                ${menuHTML()}
            </div>
            <div class="text-[10px] text-gray-300 mt-0.5">${_e(dateStr)}</div>
        </div>`;
    }

    // ── File Note ──────────────────────────────────────────────
    return `<div class="at-note-bubble at-note-file" style="align-self:flex-start;display:flex;align-items:center;gap:8px;padding:8px 12px;position:relative;min-width:200px;max-width:400px;">
        <i class="fas fa-paperclip text-green-500 flex-shrink-0"></i>
        <a href="${fileUrl}" target="_blank" class="text-sm text-green-700 hover:underline truncate" style="max-width:180px;">${_e(fileName)}</a>
        <span class="text-[10px] text-gray-300 ml-auto">${_e(dateStr)}</span>
        <div style="position:relative;">
            <button ${toggleMenu} class="at-menu-btn"><i class="fas fa-ellipsis-v"></i></button>
            ${menuHTML()}
        </div>
    </div>`;
}

// ── Copy Text Helper ──────────────────────────────────────────
window.atCopyText = function(encodedText) {
    try {
        const text = decodeURIComponent(encodedText);
        window.focus();
        setTimeout(() => {
            navigator.clipboard.writeText(text).then(() => {
                atT('success', 'Copied!');
            }).catch(() => {
                const ta = document.createElement('textarea');
                ta.value = text;
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
                atT('success', 'Copied!');
            });
        }, 100);
    } catch(e) {
        atT('error', 'Copy failed');
        console.error(e);
    }
};

// ── Copy Image to Clipboard ───────────────────────────────────
window.atCopyImage = async function(url) {
    try {
        const res  = await fetch(url, { credentials: 'include' });
        const blob = await res.blob();
        let finalBlob = blob;
        if (!blob.type.includes('png')) {
            const img    = new Image();
            img.src      = URL.createObjectURL(blob);
            await new Promise(r => { img.onload = r; });
            const canvas = document.createElement('canvas');
            canvas.width  = img.naturalWidth;
            canvas.height = img.naturalHeight;
            canvas.getContext('2d').drawImage(img, 0, 0);
            finalBlob = await new Promise(r => canvas.toBlob(r, 'image/png'));
            URL.revokeObjectURL(img.src);
        }
        // floating menu click এ focus চলে যায় — window focus হওয়ার পর copy করি
        window.focus();
        await new Promise(r => setTimeout(r, 100));
        await navigator.clipboard.write([
            new ClipboardItem({ 'image/png': finalBlob })
        ]);
        atT('success', 'Image copied!');
    } catch(e) {
        if (e.name === 'NotAllowedError') {
            atT('error', 'Copy failed — please click on the page first');
        } else {
            atT('error', 'Copy failed');
        }
        console.error('Image copy failed:', e);
    }
};

// ── Delete single PDF page ────────────────────────────────────
window.atDeletePage = async function(noteSysId, pageIndex) {
    if (!confirm(`Delete page ${pageIndex + 1}?`)) return;
    try {
        const res  = await fetch(window._at.cfg.api.notes, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete_page', note_sys_id: noteSysId, page_index: pageIndex }),
        });
        const json = await res.json();
        if (json.status === 'success') {
            atT('success', 'Page deleted');
            await atLoadNotes();
        } else {
            atT('error', json.message || 'Delete failed');
        }
    } catch(e) {
        atT('error', 'Delete failed');
        console.error(e);
    }
};

// ── Copy single page image ────────────────────────────────────
window.atCopyPageImage = async function(pgUrl) {
    try {
        const res  = await fetch(pgUrl, { credentials: 'include' });
        const blob = await res.blob();
        window.focus();
        await new Promise(r => setTimeout(r, 100));
        await navigator.clipboard.write([new ClipboardItem({ 'image/png': blob })]);
        atT('success', 'Page copied!');
    } catch(e) {
        atT('error', 'Copy failed');
        console.error(e);
    }
};

// ── Share PDF pages (server has rebuilt PDF on any page delete) ─
window.atSharePdfPages = async function(noteSysId) {
    const serveBase = window._at.cfg.api.fileServe ?? window._at.cfg.api.notes.replace('api/tasks/notes.php', 'api/file/serve.php');
    const pdfUrl    = `${serveBase}?note_id=${encodeURIComponent(noteSysId)}&dl=1`;
    // notes থেকে file_name নিই
    try {
        const res  = await fetch(`${window._at.cfg.api.notes}?action=list&work_sys_id=${encodeURIComponent(window._at.cfg.workSysId)}`);
        const json = await res.json();
        const note = json?.data?.find(n => n.sys_id === noteSysId);
        const fileName = note?.file_name ?? 'document.pdf';
        await atShareFile(pdfUrl, fileName, 'application/pdf');
    } catch(e) {
        await atShareFile(pdfUrl, 'document.pdf', 'application/pdf');
    }
};

// ── Share File (Web Share API) ────────────────────────────────
window.atShareFile = async function(url, fileName, mimeType) {
    try {
        const res  = await fetch(url, { credentials: 'include' });
        if (!res.ok) { atT('error', 'File not available'); return; }
        const blob = await res.blob();
        const file = new File([blob], fileName, { type: mimeType || blob.type });

        if (navigator.canShare && navigator.canShare({ files: [file] })) {
            await navigator.share({ files: [file], title: fileName });
        } else {
            // Desktop বা unsupported browser → download
            const a = document.createElement('a');
            a.href     = URL.createObjectURL(blob);
            a.download = fileName;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(a.href);
            atT('success', 'Downloaded!');
        }
    } catch(e) {
        if (e.name !== 'AbortError') {
            atT('error', 'Share failed');
            console.error(e);
        }
    }
};
(function _attachMenuDelegation() {
    // Guard: tab switch এ script reload হলে duplicate listener হবে না
    if (window._atMenuDelegationAttached) return;
    window._atMenuDelegationAttached = true;

    let _activeMenuId = null;

    function _closeMenu() {
        const existing = document.getElementById('at-floating-menu');
        if (existing) existing.remove();
        _activeMenuId = null;
    }

    document.addEventListener('click', function(e) {
        // Floating menu এর ভেতরে click → item action চলুক, তারপর close
        if (e.target.closest('#at-floating-menu')) {
            setTimeout(_closeMenu, 120);
            return;
        }

        const btn = e.target.closest('[data-menu-toggle]');
        if (btn) {
            e.stopPropagation();
            const sysId = btn.getAttribute('data-menu-toggle');
            if (_activeMenuId === sysId) { _closeMenu(); return; }
            _closeMenu();

            let sourceMenu = document.getElementById(`at-menu-${sysId}`);
            if (!sourceMenu) {
                sourceMenu = btn.parentElement?.querySelector('.at-menu-dropdown') ?? null;
            }
            if (!sourceMenu) { console.warn('Menu not found:', sysId); return; }

            const floating = document.createElement('div');
            floating.id = 'at-floating-menu';
            floating.innerHTML = sourceMenu.innerHTML;
            floating.style.cssText = 'position:fixed;z-index:99999;background:#fff;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,.15),0 2px 8px rgba(0,0,0,.05);border:1px solid #f1f5f9;min-width:160px;padding:6px 0;visibility:hidden;top:0;left:0;';
            document.body.appendChild(floating);
            _activeMenuId = sysId;

            setTimeout(() => {
                const cx = e.clientX;
                const cy = e.clientY;
                const h  = floating.offsetHeight || 100;
                const w  = floating.offsetWidth  || 160;

                const top  = (cy - h - 8 > 4) ? (cy - h - 8) : (cy + 8);
                let   left = cx - w;
                if (left < 4) left = 4;
                if (left + w > window.innerWidth - 4) left = window.innerWidth - w - 4;

                floating.style.top        = top + 'px';
                floating.style.left       = left + 'px';
                floating.style.visibility = 'visible';
            }, 50);
            return;
            return;
        }

        // Click outside (কোনো btn বা menu না) → close
        if (_activeMenuId) _closeMenu();

    }); // bubble phase (no capture)

    // Close on any scroll
    window.addEventListener('scroll', _closeMenu, true);

})();



// ── Delete Note ───────────────────────────────────────────────
window.atDeleteNote = async function(sysId) {
    const note    = window._at.currentNotes.find(n => n.sys_id === sysId);
    const creator = note?.meta_data?.created_by_date?.user ?? note?.created_by ?? '';
    if (creator && creator !== (window.CURRENT_USER ?? '')) {
        atT('error', 'Permission denied — only the creator can delete this note');
        return;
    }
    if (!confirm('Delete this note?')) return;
    try {
        const res = await fetch(window._at.cfg.api.notes, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                action: 'delete', 
                note_sys_id: sysId,
                work_sys_id: window._at.cfg.workSysId 
            })
        });
        const json = await res.json();
        if (json.status === 'success') {
            atT('success', 'Deleted');
            await atLoadNotes();
        } else {
            atT('error', json.message || 'Delete failed');
        }
    } catch(e) {
        atT('error', 'Delete failed');
        console.error(e);
    }
};

// ── View Image Fullscreen ────────────────────────────────────
window.atViewImg = function(url) {
    const ov = document.createElement('div');
    ov.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:99999;display:flex;align-items:center;justify-content:center;cursor:zoom-out;';
    ov.innerHTML = `<img src="${url}" style="max-width:90vw;max-height:90vh;border-radius:8px;">`;
    ov.onclick = () => ov.remove();
    document.body.appendChild(ov);
};

// ── Add Text Note / Upload ──────────────────────────────────
window.atAddTextNote = async function() {
    const ta      = document.getElementById('at-note-text');
    const content = ta?.value.trim();

    if (window._at.pendingFile) {
        const prog = document.getElementById('at-upload-prog');
        prog?.classList.remove('hidden');
        const fd = new FormData();
        fd.append('action', 'upload');
        fd.append('work_sys_id', window._at.cfg.workSysId);
        fd.append('service_slug', window._at.cfg.serviceSlug ?? 'air_ticket');
        fd.append('board', 'mindboard');
        fd.append('content', content);
        
        // ── PDF Conversion ──────────────────────────────────
        if (window._at.pendingFile.type === 'application/pdf') {
            fd.append('convert_pdf', 'true');
        }
        
        fd.append('file', window._at.pendingFile);
        try {
            const res  = await fetch(window._at.cfg.api.notes, { method: 'POST', body: fd });
            const json = await res.json();
            if (json.status === 'success') {
                if (ta) { ta.value = ''; ta.style.height = 'auto'; }
                atClearFile();
                await atLoadNotes();
            } else {
                atT('error', json.message || 'Upload failed');
            }
        } catch(e) {
            atT('error', 'Upload failed');
            console.error(e);
        }
        prog?.classList.add('hidden');
        return;
    }

    if (!content) return;
    try {
        const res  = await fetch(window._at.cfg.api.notes, {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ action:'store', work_sys_id:window._at.cfg.workSysId, service_slug:window._at.cfg.serviceSlug??'air_ticket', board:'mindboard', content }),
        });
        const json = await res.json();
        if (json.status === 'success') {
            if (ta) { ta.value = ''; ta.style.height = 'auto'; }
            await atLoadNotes();
        } else {
            atT('error', json.message || 'Network error');
        }
    } catch(e) {
        atT('error', 'Network error');
        console.error(e);
    }
};