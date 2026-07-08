/**
 * FILE PATH: /pages/task-tabs/tt-air-ticket.js
 * Air Ticket Task Module — loaded dynamically by show-tasks.php
 *
 * Export: initAirTicketTab(config)
 * config = { taskSysId, workSysId, leadSysId, clientName, workname, atData, api }
 *
 * Renders into: #at-tab-mount
 * 5 tabs: Mind Board | Quotation | Booking | Confirmation | Financial
 */

// ═══════════════════════════════════════════════════════════════
// MODULE SCOPE — IIFE to avoid global pollution
// ═══════════════════════════════════════════════════════════════
(function () {

// ── Module state ──────────────────────────────────────────────
let _cfg       = {};
let _atData    = null;
let _activeTab = 'mindboard';
let _activeQSysId = null;
let _activeBSysId = null;
let _gdsSegments  = [];
let _gdsFares     = [];

// ── Styles (injected once) ────────────────────────────────────
const AT_STYLES='<style id="at-styles">.at-tab-bar{display:flex;gap:0;overflow-x:auto;border-bottom:2px solid #e5e7eb;margin-bottom:0;background:#fafafa;padding:0 8px}.at-tab{padding:12px 20px;font-size:.82rem;font-weight:600;color:#6b7280;border-bottom:2px solid transparent;margin-bottom:-2px;cursor:pointer;white-space:nowrap;transition:all .15s ease;background:transparent;position:relative}.at-tab.active{color:#4f46e5;border-bottom-color:#4f46e5}.at-tab:hover:not(.active){color:#374151;background:#f3f4f6;border-radius:8px 8px 0 0}.at-panel{display:none;animation:atFadeIn .2s ease}.at-panel.active{display:block}.at-note-bubble{border-radius:14px;padding:10px 14px;margin-bottom:6px;word-break:break-word;position:relative;box-shadow:0 1px 3px rgba(0,0,0,.04);transition:all .15s ease}.at-note-bubble:hover{box-shadow:0 2px 8px rgba(0,0,0,.06)}.at-note-text{background:#f3f4f6;border:1px solid #e5e7eb}.at-note-image{background:#fff;border:1px solid #e5e7eb;padding:6px;border-radius:12px}.at-note-audio{background:#f5f3ff;border:1px solid #ede9fe}.at-note-video{background:#0f172a;border:1px solid #1e293b;padding:4px;border-radius:12px}.at-note-file{background:#f0fdf4;border:1px solid #bbf7d0;display:flex;align-items:center;gap:10px;padding:8px 14px;border-radius:10px}.at-menu-wrapper{position:relative;z-index:100;flex-shrink:0}.at-menu-btn{color:#9ca3af;padding:4px 8px;border-radius:6px;cursor:pointer;transition:all .15s ease;background:transparent;border:none;font-size:14px;line-height:1;display:flex;align-items:center;justify-content:center;width:28px;height:28px}.at-menu-btn:hover{background:#f3f4f6;color:#4f46e5}.at-menu-dropdown{position:fixed;background:#fff!important;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,.15),0 2px 8px rgba(0,0,0,.05);border:1px solid #f1f5f9;min-width:180px;padding:8px 0;z-index:999999!important;display:none;pointer-events:auto;backdrop-filter:none!important;-webkit-backdrop-filter:none!important;opacity:1!important}.at-menu-dropdown.show{display:block}.at-menu-item{display:flex;align-items:center;gap:10px;padding:8px 16px;font-size:.78rem;font-weight:500;color:#374151;cursor:pointer;transition:all .1s ease;border:none;background:none;width:100%;text-align:left;border-radius:0}.at-menu-item:first-child{border-radius:12px 12px 0 0}.at-menu-item:last-child{border-radius:0 0 12px 12px}.at-menu-item:hover{background:#f8fafc}.at-menu-item.danger{color:#dc2626}.at-menu-item.danger:hover{background:#fef2f2;color:#dc2626}.at-menu-item i{width:16px;font-size:.75rem;color:#94a3b8;flex-shrink:0}.at-menu-item.danger i{color:#f87171}.at-menu-item.danger:hover i{color:#dc2626}@keyframes atMenuPop{from{opacity:0;transform:scale(.92) translateY(6px)}to{opacity:1;transform:scale(1) translateY(0)}}@keyframes atFadeIn{from{opacity:0;transform:translateY(4px)}to{opacity:1;transform:translateY(0)}}@keyframes atBounce{0%,100%{transform:translateY(0)}50%{transform:translateY(-4px)}}.at-upload-zone{border:2px dashed #d1d5db;border-radius:12px;padding:24px 20px;text-align:center;cursor:pointer;transition:all .2s ease;background:#fafafa}.at-upload-zone.dragover,.at-upload-zone:hover{border-color:#6366f1;background:#f5f3ff}.at-file-card{border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;background:#fff}.at-ai-dot{width:6px;height:6px;border-radius:50%;background:#6366f1;animation:atBounce .8s infinite;display:inline-block}.at-ai-dot:nth-child(2){animation-delay:.15s}.at-ai-dot:nth-child(3){animation-delay:.3s}.at-pill{display:inline-flex;align-items:center;padding:2px 10px;border-radius:999px;font-size:.68rem;font-weight:700;letter-spacing:.3px}.at-pill-draft{background:#f3f4f6;color:#374151}.at-pill-sent{background:#dbeafe;color:#1e40af}.at-pill-moved{background:#dcfce7;color:#166534}.at-pill-superseded{background:#f3f4f6;color:#9ca3af;text-decoration:line-through}.at-pill-cancelled,.at-pill-failed{background:#fee2e2;color:#991b1b}.at-pill-tentative{background:#fef9c3;color:#854d0e}.at-pill-confirmed{background:#dcfce7;color:#166534}.at-pill-refunded{background:#f3e8ff;color:#6b21a8}.at-q-card{border:1.5px solid #e5e7eb;border-radius:10px;padding:12px 14px;cursor:pointer;transition:all .15s ease;background:#fafafa;margin-bottom:8px}.at-q-card:hover{border-color:#a5b4fc;background:#fff;box-shadow:0 2px 8px rgba(0,0,0,.04)}.at-q-card.active{border-color:#6366f1;background:#eef2ff;box-shadow:0 2px 12px rgba(99,102,241,.12)}.at-q-card.moved{opacity:.6}.gds-th{font-size:.72rem;font-weight:700;color:#6b7280;text-transform:uppercase;padding:6px 8px;background:#f9fafb;border-bottom:1px solid #e5e7eb;white-space:nowrap}.gds-td{font-size:.82rem;padding:6px 8px;border-bottom:1px solid #f3f4f6}.gds-input{width:100%;padding:4px 6px;border:1px solid #e5e7eb;border-radius:6px;font-size:.8rem;background:#fff;outline:0;transition:all .15s ease}.gds-input:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.1)}.gds-input:read-only{background:#f9fafb;cursor:default}.at-notes-list::-webkit-scrollbar{width:4px}.at-notes-list::-webkit-scrollbar-track{background:transparent}.at-notes-list::-webkit-scrollbar-thumb{background:#d1d5db;border-radius:10px}.at-notes-list::-webkit-scrollbar-thumb:hover{background:#9ca3af}@media (max-width:640px){.at-tab{padding:10px 14px;font-size:.75rem}.at-menu-dropdown{min-width:140px;right:-8px}.at-note-bubble{max-width:92%!important}}</style>';

// ═══════════════════════════════════════════════════════════════
// INIT
// ═══════════════════════════════════════════════════════════════
window.initAirTicketTab = async function (config) {
    _cfg    = config;
    _atData = config.atData;

    if (!document.getElementById('at-styles')) {
        document.head.insertAdjacentHTML('beforeend', AT_STYLES);
    }

    const mount = document.getElementById('at-tab-mount');
    if (!mount) return;

    mount.innerHTML = `
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="at-tab-bar px-4 pt-3">
            <button class="at-tab active" data-tab="mindboard"><i class="fas fa-brain mr-1.5"></i>Mind Board</button>
            <button class="at-tab" data-tab="quotation"><i class="fas fa-file-invoice mr-1.5"></i>Quotation</button>
            <button class="at-tab" data-tab="booking"><i class="fas fa-bookmark mr-1.5"></i>Booking</button>
            <button class="at-tab" data-tab="confirmation"><i class="fas fa-check-circle mr-1.5"></i>Confirmation</button>
            <button class="at-tab" data-tab="financial"><i class="fas fa-receipt mr-1.5"></i>Financial</button>
        </div>
        <div id="at-panel-mindboard"    class="at-panel active p-5"></div>
        <div id="at-panel-quotation"    class="at-panel p-5"></div>
        <div id="at-panel-booking"      class="at-panel p-5"></div>
        <div id="at-panel-confirmation" class="at-panel p-5"></div>
        <div id="at-panel-financial"    class="at-panel p-5"></div>
    </div>`;

    mount.querySelectorAll('.at-tab').forEach(btn => {
        btn.addEventListener('click', () => _switchTab(btn.dataset.tab, btn));
    });

    if (!_atData) await _initRecord();

    _switchTab('mindboard', mount.querySelector('.at-tab[data-tab="mindboard"]'));
};

// ── Tab switcher ──────────────────────────────────────────────
function _switchTab(name, btn) {
    _activeTab = name;
    document.querySelectorAll('.at-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.at-panel').forEach(p => {
        p.classList.remove('active');
        p.style.cssText = '';
    });
    btn?.classList.add('active');
    document.getElementById('at-panel-' + name)?.classList.add('active');
    switch (name) {
        case 'mindboard':    _renderMindboard();    break;
        case 'quotation':    _renderQuotation();    break;
        case 'booking':      _renderBooking();      break;
        case 'confirmation': _renderConfirmation(); break;
        case 'financial':    _renderFinancial();    break;
    }
}

// ── Init / Reload ─────────────────────────────────────────────
async function _initRecord() {
    try {
        const res  = await fetch(_cfg.api.airTickets, {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ action:'init', task_sys_id:_cfg.taskSysId, work_sys_id:_cfg.workSysId, lead_sys_id:_cfg.leadSysId }),
        });
        const json = await res.json();
        if (json.status === 'success') _atData = { at_quotations:[], at_bookings:[], at_confirmation:null };
    } catch(e) { console.error('AT init:', e); }
}

async function _reload() {
    try {
        const res  = await fetch(`${_cfg.api.airTickets}?action=get&task_sys_id=${encodeURIComponent(_cfg.taskSysId)}`);
        const json = await res.json();
        if (json.status === 'success' && json.data) _atData = json.data;
    } catch(e) { console.error('AT reload:', e); }
}

async function _api(body) {
    const res = await fetch(_cfg.api.airTickets, {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ ...body, task_sys_id:_cfg.taskSysId }),
    });
    return await res.json();
}

// ═══════════════════════════════════════════════════════════════
// TAB 1 — MIND BOARD
// ═══════════════════════════════════════════════════════════════
function _renderMindboard() {
    const panel = document.getElementById('at-panel-mindboard');
    panel.style.cssText = 'display:flex;flex-direction:column;padding:0;';

    panel.innerHTML = `
    <!-- Chat bubbles (scrollable) -->
    <div id="at-notes-list" style="flex:1;overflow-y:auto;padding:12px 14px;display:flex;flex-direction:column;gap:8px;min-height:300px;max-height:calc(100vh - 320px);">
        <div class="text-center py-6 text-gray-300 text-sm"><i class="fas fa-spinner fa-spin"></i></div>
    </div>

    <!-- Upload progress -->
    <div id="at-upload-prog" class="hidden px-4 py-2 bg-indigo-50 border-t border-indigo-100 text-sm text-indigo-600" style="flex-shrink:0;">
        <i class="fas fa-spinner fa-spin mr-2"></i>Uploading…
    </div>

    <!-- Input bar -->
    <div style="flex-shrink:0;border-top:1px solid #f1f5f9;background:#fff;padding:10px 12px;">
        <!-- File preview chip -->
        <div id="at-file-preview" class="hidden mb-2 bg-indigo-50 rounded-lg px-3 py-1.5 text-xs text-indigo-600 flex items-center gap-2">
            <i class="fas fa-paperclip flex-shrink-0"></i>
            <span id="at-file-preview-name" class="flex-1 truncate"></span>
            <button onclick="atClearFile()" class="text-red-400 hover:text-red-600 flex-shrink-0"><i class="fas fa-times"></i></button>
        </div>
        <div class="flex items-end gap-2">
            <label class="w-8 h-8 bg-blue-50 hover:bg-blue-100 rounded-full flex items-center justify-center cursor-pointer flex-shrink-0 transition" title="Image">
                <i class="fas fa-image text-blue-500 text-xs"></i>
                <input type="file" class="hidden" accept="image/*" onchange="atFileSelected(this)">
            </label>
            <label class="w-8 h-8 bg-purple-50 hover:bg-purple-100 rounded-full flex items-center justify-center cursor-pointer flex-shrink-0 transition" title="Audio">
                <i class="fas fa-microphone text-purple-500 text-xs"></i>
                <input type="file" class="hidden" accept="audio/*" onchange="atFileSelected(this)">
            </label>
            <label class="w-8 h-8 bg-gray-100 hover:bg-gray-200 rounded-full flex items-center justify-center cursor-pointer flex-shrink-0 transition" title="File">
                <i class="fas fa-paperclip text-gray-500 text-xs"></i>
                <input type="file" class="hidden" onchange="atFileSelected(this)">
            </label>
            <textarea id="at-note-text" rows="1"
                placeholder="Write a note… (Enter to send, Shift+Enter for new line) | Paste image (Ctrl+V) | Drop files"
                style="flex:1;resize:none;border:1.5px solid #e5e7eb;border-radius:20px;padding:8px 14px;font-size:.83rem;outline:none;max-height:80px;overflow-y:auto;transition:border .15s;"
                onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();atAddTextNote();}"
                oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,80)+'px'"></textarea>
            <button onclick="atAddTextNote()"
                class="w-9 h-9 bg-indigo-600 hover:bg-indigo-700 text-white rounded-full flex items-center justify-center flex-shrink-0 transition">
                <i class="fas fa-paper-plane text-sm"></i>
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
                    _atPendingFile = file;
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
                _atPendingFile = files[0];
                document.getElementById('at-file-preview').classList.remove('hidden');
                document.getElementById('at-file-preview-name').textContent = _atPendingFile.name;
            }
        });
    }

    // ── Close menus on outside click ──────────────────────────
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.at-menu-dropdown') && !e.target.closest('.at-menu-btn')) {
            document.querySelectorAll('.at-menu-dropdown').forEach(menu => {
                menu.classList.remove('show');
            });
        }
    });

    atLoadNotes();
}

// ── File select handler ──────────────────────────────────────
let _atPendingFile = null;
window.atFileSelected = function(input) {
    if (!input.files[0]) return;
    _atPendingFile = input.files[0];
    document.getElementById('at-file-preview').classList.remove('hidden');
    document.getElementById('at-file-preview-name').textContent = _atPendingFile.name;
    input.value = '';
};
window.atClearFile = function() {
    _atPendingFile = null;
    document.getElementById('at-file-preview').classList.add('hidden');
};

// ── Load notes ────────────────────────────────────────────────
window.atLoadNotes = async function() {
    try {
        const res  = await fetch(`${_cfg.api.notes}?action=list&task_sys_id=${encodeURIComponent(_cfg.taskSysId)}`);
        const json = await res.json();
        const notes = json.status === 'success' ? (json.data ?? []) : [];
        _renderNotes(notes);
    } catch { _renderNotes([]); }
};

function _renderNotes(notes) {
    const list = document.getElementById('at-notes-list');
    if (!list) return;
    if (!notes.length) {
        list.innerHTML = '<div class="text-center py-8 text-gray-300 text-xs">No notes yet. Write something below.</div>';
        return;
    }
    list.innerHTML = notes.map(n => _noteBubble(n)).join('');
    setTimeout(() => { list.scrollTop = list.scrollHeight; }, 50);
}

// ── Note Bubble with Three-Dot Menu ──────────────────────────
function _noteBubble(n) {
    const dateStr = n.meta_data?.created_by_date?.date ?? '';
    const sysId = _e(n.sys_id);
    const content = n.content ?? '';
    const fileUrl = n.file_url ?? '';
    const fileName = n.file_name ?? '';

    // ── Menu HTML ──────────────────────────────────────────────
    function menuHTML(showCopy = true, showDownload = false) {
        let items = '';
        if (showCopy) {
            if (n.note_type === 'text') {
                // Text → copy text content
                const encodedText = encodeURIComponent(content);
                items += `<button onclick="atCopyText('${encodedText}')" class="at-menu-item">
                    <i class="fas fa-copy"></i> Copy Text
                </button>`;
            } else if (n.note_type === 'image') {
                // Image → copy actual image to clipboard
                items += `<button onclick="atCopyImage('${fileUrl}')" class="at-menu-item">
                    <i class="fas fa-image"></i> Copy Image
                </button>`;
            } else {
                // Others → copy link
                const encodedUrl = encodeURIComponent(fileUrl);
                items += `<button onclick="atCopyText('${encodedUrl}')" class="at-menu-item">
                    <i class="fas fa-link"></i> Copy Link
                </button>`;
            }
        }
        if (showDownload) {
            items += `<a href="${fileUrl}" download="${_e(fileName)}" class="at-menu-item">
                <i class="fas fa-download"></i> Download
            </a>`;
        }
        items += `<button onclick="atDeleteNote('${sysId}')" class="at-menu-item danger">
            <i class="fas fa-trash"></i> Delete
        </button>`;
        return `<div class="at-menu-dropdown" id="at-menu-${sysId}">${items}</div>`;
    }

    const toggleMenu = `data-menu-toggle="${sysId}"`;

    // ── Text Note ──────────────────────────────────────────────
    if (n.note_type === 'text') {
        // Calculate approximate width based on content length
        const contentLength = content.length;
        let minWidth = '120px';
        let maxWidth = '85%';
        
        // Small text (less than 20 chars) - give minimum width
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
                <div style="position:relative;z-index:100;">
                    <button ${toggleMenu} class="at-menu-btn" style="position:relative;z-index:101;"><i class="fas fa-ellipsis-v"></i></button>
                    ${menuHTML(true, false)}
                </div>
            </div>
        </div>`;
    }

    // ── Image Note ─────────────────────────────────────────────
    if (n.note_type === 'image') {
        return `<div class="at-note-bubble at-note-image" style="align-self:flex-start;max-width:780px;position:relative;">
            <img src="${fileUrl}" loading="lazy" onclick="atViewImg('${fileUrl}')"
                style="width:100%;max-height:280px;object-fit:contain;border-radius:8px;cursor:zoom-in;display:block;background:#f9fafb;">
            ${content ? `<div class="text-xs text-gray-500 mt-1 px-1">${_e(content)}</div>` : ''}
            <div class="flex items-center justify-between mt-1">
                <span class="text-[10px] text-gray-300">${_e(dateStr)}</span>
                <div style="position:relative;z-index:100;">
                    <button ${toggleMenu} class="at-menu-btn" style="position:relative;z-index:101;"><i class="fas fa-ellipsis-v"></i></button>
                    ${menuHTML(true, true)}
                </div>
            </div>
        </div>`;
    }

    // ── Audio Note ─────────────────────────────────────────────
    if (n.note_type === 'audio') {
        return `<div class="at-note-bubble at-note-audio" style="align-self:flex-start;max-width:85%;position:relative;">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="fas fa-microphone text-purple-400 text-xs"></i>
                    <span class="text-xs text-gray-600">${_e(fileName)}</span>
                </div>
                <div style="position:relative;z-index:100;">
                    <button ${toggleMenu} class="at-menu-btn" style="position:relative;z-index:101;"><i class="fas fa-ellipsis-v"></i></button>
                    ${menuHTML(true, true)}
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
                ${menuHTML(true, true)}
            </div>
            <div class="text-[10px] text-gray-300 mt-0.5">${_e(dateStr)}</div>
        </div>`;
    }

    // ── File Note ──────────────────────────────────────────────
    return `<div class="at-note-bubble at-note-file" style="align-self:flex-start;display:flex;align-items:center;gap:8px;padding:8px 12px;position:relative;min-width:200px;max-width:400px;">
        <i class="fas fa-paperclip text-green-500 flex-shrink-0"></i>
        <a href="${fileUrl}" target="_blank" class="text-sm text-green-700 hover:underline truncate" style="max-width:180px;">${_e(fileName)}</a>
        <span class="text-[10px] text-gray-300 ml-auto">${_e(dateStr)}</span>
        <div style="position:relative;z-index:100;">
            <button ${toggleMenu} class="at-menu-btn" style="position:relative;z-index:101;"><i class="fas fa-ellipsis-v"></i></button>
            ${menuHTML(true, true)}
        </div>
    </div>`;
}

// ── Copy Text Helper ──────────────────────────────────────────
window.atCopyText = function(encodedText) {
    try {
        const text = decodeURIComponent(encodedText);
        navigator.clipboard.writeText(text).then(() => {
            atT('success', 'Copied!');
        }).catch(() => {
            // Fallback method
            const textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            atT('success', 'Copied!');
        });
    } catch(e) {
        atT('error', 'Copy failed');
        console.error(e);
    }
};

// ── Copy Image to Clipboard ───────────────────────────────────
window.atCopyImage = async function(url) {
    try {
        const res  = await fetch(url);
        const blob = await res.blob();
        // Ensure it's png for clipboard API
        let finalBlob = blob;
        if (!blob.type.includes('png')) {
            // Convert to png via canvas
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
        await navigator.clipboard.write([
            new ClipboardItem({ 'image/png': finalBlob })
        ]);
        atT('success', 'Image copied!');
    } catch(e) {
        // Fallback: copy URL
        navigator.clipboard.writeText(url).then(() => atT('success', 'Image URL copied!'));
        console.error('Image copy failed:', e);
    }
};

// ── Event delegation for data-menu-toggle ─────────────────────
(function _attachMenuDelegation() {
    // Single floating dropdown reused for all menus
    let _activeMenuId = null;

    function _closeMenu() {
        const existing = document.getElementById('at-floating-menu');
        if (existing) existing.remove();
        _activeMenuId = null;
    }

    document.addEventListener('click', function(e) {
        const btn = e.target.closest('[data-menu-toggle]');
        if (btn) {
            e.stopPropagation();
            const sysId = btn.getAttribute('data-menu-toggle');

            // Toggle: if same menu open, close it
            if (_activeMenuId === sysId) { _closeMenu(); return; }
            _closeMenu();

            // Find the dropdown HTML inside the bubble
            const sourceMenu = document.getElementById(`at-menu-${sysId}`);
            if (!sourceMenu) return;

            // Clone into body-level floating div
            const floating = document.createElement('div');
            floating.id        = 'at-floating-menu';
            floating.className = 'at-menu-dropdown show';
            floating.innerHTML = sourceMenu.innerHTML;
            floating.style.cssText = 'position:fixed;z-index:99999;display:block;';

            // Position
            const rect       = btn.getBoundingClientRect();
            const spaceBelow = window.innerHeight - rect.bottom;
            const menuH      = floating.offsetHeight || 130;
            floating.style.right  = (window.innerWidth - rect.right) + 'px';
            floating.style.left   = 'auto';
            floating.style.top    = (spaceBelow < menuH
                ? Math.max(4, rect.top - menuH - 4)
                : rect.bottom + 4) + 'px';

            document.body.appendChild(floating);
            _activeMenuId = sysId;

            // Recalc position after render (height now known)
            requestAnimationFrame(() => {
                const h      = floating.offsetHeight;
                const sb     = window.innerHeight - rect.bottom;
                floating.style.top = (sb < h
                    ? Math.max(4, rect.top - h - 4)
                    : rect.bottom + 4) + 'px';
            });
            return;
        }

        // Click inside floating menu — allow but close after action
        if (e.target.closest('#at-floating-menu')) {
            // Let the action run, then close
            setTimeout(_closeMenu, 80);
            return;
        }

        // Click outside → close
        _closeMenu();

    }, true); // capture

    // Close on any scroll
    window.addEventListener('scroll', _closeMenu, true);

})();



// ── Delete Note ───────────────────────────────────────────────
window.atDeleteNote = async function(sysId) {
    if (!confirm('Delete this note?')) return;
    try {
        const res = await fetch(_cfg.api.notes, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                action: 'delete', 
                note_sys_id: sysId,
                task_sys_id: _cfg.taskSysId 
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

    if (_atPendingFile) {
        const prog = document.getElementById('at-upload-prog');
        prog?.classList.remove('hidden');
        const fd = new FormData();
        fd.append('action', 'upload');
        fd.append('task_sys_id', _cfg.taskSysId);
        fd.append('work_sys_id', _cfg.workSysId);
        fd.append('content', content);
        
        // ── PDF Conversion ──────────────────────────────────
        if (_atPendingFile.type === 'application/pdf') {
            fd.append('convert_pdf', 'true');
        }
        
        fd.append('file', _atPendingFile);
        try {
            const res  = await fetch(_cfg.api.notes, { method: 'POST', body: fd });
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
        const res  = await fetch(_cfg.api.notes, {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ action:'store', task_sys_id:_cfg.taskSysId, work_sys_id:_cfg.workSysId, content }),
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

// ═══════════════════════════════════════════════════════════════
// TAB 2 — QUOTATION
// ═══════════════════════════════════════════════════════════════

// ── GDS State ─────────────────────────────────────────────────
// _gdsSegments and _gdsFares already declared above in module scope

// ── GDS HTML shell ────────────────────────────────────────────
function _gdsHtml(data, pfx) {
    return `
    <!-- Raw GDS + Extract -->
    <div class="mb-4">
        <div class="flex items-center justify-between mb-1">
            <label class="text-xs font-bold text-gray-500 uppercase">Raw GDS Text</label>
            <button onclick="atExtractGds('${pfx}')" id="at-${pfx}-extract-btn"
                class="flex items-center gap-1 px-3 py-1.5 bg-slate-800 hover:bg-slate-900 text-white rounded-lg text-xs font-semibold transition">
                <i class="fas fa-wand-magic-sparkles text-xs"></i>Process GDS
            </button>
        </div>
        <textarea id="at-${pfx}-raw" rows="4" placeholder="Paste Amadeus/Sabre/Galileo GDS text here…"
            class="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs font-mono resize-none focus:outline-none focus:border-indigo-400"
        >${_e(data?.raw_input??'')}</textarea>
        <div id="at-${pfx}-extract-err" class="hidden mt-1 text-xs text-red-500"></div>
    </div>

    <!-- Airline + Calculation rates -->
    <div class="grid grid-cols-4 gap-2 mb-4">
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Airline</label>
            <input id="at-${pfx}-airline" value="${_e(data?.airline??'')}" placeholder="Turkish Airlines"
                oninput="atGdsPreview('${pfx}')"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-400">
        </div>
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Commission %</label>
            <input type="number" id="at-${pfx}-comm" value="7" step="0.01" min="0"
                oninput="atGdsCalcAll('${pfx}', true)"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-400">
        </div>
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Govt Tax %</label>
            <input type="number" id="at-${pfx}-govt" value="0.3" step="0.01" min="0"
                oninput="atGdsCalcAll('${pfx}', true)"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-400">
        </div>
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">IATA Charge</label>
            <input type="number" id="at-${pfx}-iata" value="0" min="0"
                oninput="atGdsApplyIata('${pfx}')"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-400">
        </div>
    </div>

    <!-- Live Preview -->
    <div class="mb-4 rounded-xl overflow-hidden border border-gray-200">
        <div class="bg-slate-800 px-4 py-2 flex items-center justify-between">
            <span class="text-white text-xs font-bold">Client Copy Preview</span>
            <button onclick="atGdsCopy('${pfx}')"
                class="text-xs bg-white bg-opacity-10 hover:bg-opacity-20 text-white px-3 py-1 rounded-lg font-semibold transition">
                <i class="fas fa-copy mr-1"></i>Copy
            </button>
        </div>
        <pre id="at-${pfx}-preview"
            class="bg-slate-950 text-slate-100 p-4 min-h-[120px] max-h-[260px] overflow-auto text-xs leading-6 font-mono whitespace-pre-wrap">${_e(data?.copy_text??'')}</pre>
    </div>

    <!-- Segments -->
    <div class="mb-4">
        <div class="flex items-center justify-between mb-2">
            <label class="text-xs font-bold text-gray-500 uppercase">Flight Segments</label>
            <button onclick="atGdsAddSeg('${pfx}')" class="text-xs text-indigo-500 hover:text-indigo-700 font-semibold"><i class="fas fa-plus mr-1"></i>Add Segment</button>
        </div>
        <div class="overflow-x-auto rounded-xl border border-gray-100">
            <div style="min-width:700px">
                <div class="grid gap-2 px-3 py-2 bg-gray-50 text-[10px] font-bold uppercase text-gray-400" style="grid-template-columns:44px 100px 50px 80px 120px 72px 72px 40px">
                    <div>Tag</div><div>Flight</div><div>Cls</div><div>Date</div><div>Route</div><div>Dep</div><div>Arr</div><div></div>
                </div>
                <div id="at-${pfx}-segs-area" class="divide-y divide-gray-50">${_gdsSegsArea(pfx)}</div>
            </div>
        </div>
    </div>

    <!-- Fares -->
    <div class="mb-4">
        <div class="flex items-center justify-between mb-2">
            <label class="text-xs font-bold text-gray-500 uppercase">Passenger Fare Calculation</label>
            <button onclick="atGdsAddFare('${pfx}')" class="text-xs text-indigo-500 hover:text-indigo-700 font-semibold"><i class="fas fa-plus mr-1"></i>Add Fare</button>
        </div>
        <div class="overflow-x-auto rounded-xl border border-gray-100">
            <div style="min-width:1100px">
                <div class="grid gap-2 px-3 py-2 bg-gray-50 text-[10px] font-bold uppercase text-gray-400"
                    style="grid-template-columns:65px 50px 105px 95px 105px 95px 95px 95px 105px 125px 130px 44px">
                    <div>Type</div><div>Pax</div><div>Base</div><div>Taxes</div><div>Gross</div>
                    <div>A(Comm)</div><div>B(Govt)</div><div>IATA</div><div>Net</div><div>Payable</div><div>Total</div><div></div>
                </div>
                <div id="at-${pfx}-fares-area" class="divide-y divide-gray-50">${_gdsFaresArea(pfx)}</div>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="flex gap-2">
        <button onclick="atSaveQ('${pfx}')"
            class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition">
            <i class="fas fa-save mr-1.5"></i>${pfx==='q'&&_activeQSysId ? 'Update' : pfx==='b'&&_activeBSysId ? 'Update Booking' : pfx==='q' ? 'Save Quotation' : 'Save Booking'}
        </button>
        ${pfx==='q'&&_activeQSysId ? `<button onclick="atDeleteQ()" class="px-4 py-2.5 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-lg text-sm"><i class="fas fa-trash-alt"></i></button>` : ''}
        ${pfx==='b'&&_activeBSysId ? `
        <button onclick="atConfirmFromBooking('${_activeBSysId}')" title="Set as Confirmation"
            class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm"><i class="fas fa-check"></i></button>
        <button onclick="atDeleteB()" class="px-4 py-2.5 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-lg text-sm"><i class="fas fa-trash-alt"></i></button>` : ''}
    </div>`;
}

// ── Segment rows ──────────────────────────────────────────────
function _gdsSegsArea(pfx) {
    if (!_gdsSegments.length) return `<div class="text-center py-4 text-gray-300 text-xs">No segments. Process GDS or Add.</div>`;
    return _gdsSegments.map((s,i) => `
    <div class="grid gap-2 px-3 py-2 items-center" style="grid-template-columns:44px 100px 50px 80px 120px 72px 72px 40px">
        <span class="text-xs font-bold text-gray-400">${_e(s.tag||'D'+(i+1))}</span>
        <input class="gds-input at-seg-field" data-pfx="${pfx}" data-idx="${i}" data-key="flight" value="${_e(s.flight??'')}">
        <input class="gds-input at-seg-field" data-pfx="${pfx}" data-idx="${i}" data-key="class" value="${_e(s.class??'')}">
        <input class="gds-input at-seg-field" data-pfx="${pfx}" data-idx="${i}" data-key="date" value="${_e(s.date??'')}">
        <input class="gds-input at-seg-field" data-pfx="${pfx}" data-idx="${i}" data-key="route" value="${_e(s.route??'')}">
        <input class="gds-input at-seg-field" data-pfx="${pfx}" data-idx="${i}" data-key="departure" value="${_e(s.departure??'')}">
        <input class="gds-input at-seg-field" data-pfx="${pfx}" data-idx="${i}" data-key="arrival" value="${_e(s.arrival??'')}">
        <button class="at-seg-rm text-red-400 hover:text-red-600 text-xs" data-pfx="${pfx}" data-idx="${i}"><i class="fas fa-trash"></i></button>
    </div>`).join('');
}

// ── Fare rows ─────────────────────────────────────────────────
function _gdsFaresArea(pfx) {
    if (!_gdsFares.length) return `<div class="text-center py-4 text-gray-300 text-xs">No fares. Process GDS or Add.</div>`;
    return _gdsFares.map((f,i) => `
    <div class="grid gap-2 px-3 py-2 items-center" style="grid-template-columns:65px 50px 105px 95px 105px 95px 95px 95px 105px 125px 130px 44px">
        <input class="gds-input at-fare-field" data-pfx="${pfx}" data-idx="${i}" data-key="type" value="${_e(f.type??'ADT')}">
        <input class="gds-input at-fare-field" data-pfx="${pfx}" data-idx="${i}" data-key="pax" data-numeric="1" value="${+(f.pax??1)}">
        <input class="gds-input at-fare-field" data-pfx="${pfx}" data-idx="${i}" data-key="base_fare" data-numeric="1" value="${+(f.base_fare??0)}">
        <input class="gds-input at-fare-field" data-pfx="${pfx}" data-idx="${i}" data-key="taxes" data-numeric="1" value="${+(f.taxes??0)}">
        <input class="gds-input at-fare-field" data-pfx="${pfx}" data-idx="${i}" data-key="gross_fare" data-numeric="1" value="${+(f.gross_fare??0)}">
        <input class="gds-input bg-gray-50" readonly data-fare-ro="commission_a" data-pfx="${pfx}" data-idx="${i}" value="${+(f.commission_a??0)}">
        <input class="gds-input bg-gray-50" readonly data-fare-ro="govt_tax_b" data-pfx="${pfx}" data-idx="${i}" value="${+(f.govt_tax_b??0)}">
        <input class="gds-input at-fare-field" data-pfx="${pfx}" data-idx="${i}" data-key="iata_charge" data-numeric="1" value="${+(f.iata_charge??0)}">
        <input class="gds-input bg-gray-50 font-semibold" readonly data-fare-ro="net_fare" data-pfx="${pfx}" data-idx="${i}" value="${+(f.net_fare??0)}">
        <input class="gds-input border-2 border-green-400 font-bold text-green-700 at-fare-payable" data-pfx="${pfx}" data-idx="${i}" value="${+(f.payable??0)}">
        <input class="gds-input bg-green-50 font-bold text-green-700" readonly data-fare-ro="total_payable" data-pfx="${pfx}" data-idx="${i}" value="BDT ${_fmtN(+(f.total_payable??0))}/-">
        <button class="at-fare-rm text-red-400 hover:text-red-600 text-xs" data-pfx="${pfx}" data-idx="${i}"><i class="fas fa-trash"></i></button>
    </div>`).join('');
}

// ── Event delegation ──────────────────────────────────────────
(function _attachGdsDelegation() {
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('at-seg-field')) {
            const idx = +e.target.dataset.idx, key = e.target.dataset.key, pfx = e.target.dataset.pfx;
            _gdsSegments[idx][key] = key==='route' ? _gdsNormalizeRoute(e.target.value) : e.target.value;
            atGdsPreview(pfx); return;
        }
        if (e.target.classList.contains('at-fare-payable')) {
            const idx = +e.target.dataset.idx, pfx = e.target.dataset.pfx;
            _gdsFares[idx].payable        = +(e.target.value||0);
            _gdsFares[idx].payable_edited = true;
            _gdsFares[idx].total_payable  = _gdsFares[idx].payable * (+(_gdsFares[idx].pax||1));
            _gdsUpdateRo(pfx, idx); atGdsPreview(pfx); return;
        }
        if (e.target.classList.contains('at-fare-field') && e.target.dataset.key) {
            const idx = +e.target.dataset.idx, key = e.target.dataset.key, pfx = e.target.dataset.pfx;
            _gdsFares[idx][key] = e.target.dataset.numeric==='1' ? +(e.target.value||0) : e.target.value;
            if (['base_fare','gross_fare','iata_charge'].includes(key)) {
                if (key!=='pax') _gdsFares[idx].payable_edited = false;
                _gdsCalcFare(pfx, idx, false);
                _gdsUpdateRo(pfx, idx);
                if (!_gdsFares[idx].payable_edited) {
                    const payEl = document.querySelector(`.at-fare-payable[data-pfx="${pfx}"][data-idx="${idx}"]`);
                    if (payEl && document.activeElement !== payEl) payEl.value = _gdsFares[idx].payable;
                }
            }
            atGdsPreview(pfx); return;
        }
    });
    document.addEventListener('click', function(e) {
        const rmSeg = e.target.closest('.at-seg-rm');
        if (rmSeg) {
            const idx=+rmSeg.dataset.idx, pfx=rmSeg.dataset.pfx;
            _gdsSegments.splice(idx,1);
            const a=document.getElementById(`at-${pfx}-segs-area`); if(a) a.innerHTML=_gdsSegsArea(pfx);
            atGdsPreview(pfx); return;
        }
        const rmFare = e.target.closest('.at-fare-rm');
        if (rmFare) {
            const idx=+rmFare.dataset.idx, pfx=rmFare.dataset.pfx;
            _gdsFares.splice(idx,1);
            const a=document.getElementById(`at-${pfx}-fares-area`); if(a) a.innerHTML=_gdsFaresArea(pfx);
            atGdsPreview(pfx); return;
        }
    });
})();

// ── Fare calculation ──────────────────────────────────────────
function _gdsCalcFare(pfx, idx, forceReset) {
    const f    = _gdsFares[idx]; if (!f) return;
    const comm = +(document.getElementById(`at-${pfx}-comm`)?.value ?? 7) / 100;
    const govt = +(document.getElementById(`at-${pfx}-govt`)?.value ?? 0.3) / 100;
    const base  = +(f.base_fare  || 0);
    const gross = +(f.gross_fare || 0);
    const iata  = +(f.iata_charge || 0);
    f.commission_a = Math.round(base  * comm);
    f.govt_tax_b   = Math.round(gross * govt);
    f.net_fare     = Math.max(0, Math.round(gross - f.commission_a + f.govt_tax_b + iata));
    if (forceReset || !f.payable_edited || +(f.payable||0) === 0) {
        f.payable        = Math.max(0, Math.round((gross + f.net_fare) / 2));
        f.payable_edited = false;
    }
    f.total_payable = +(f.payable||0) * +(f.pax||1);
}

function _gdsUpdateRo(pfx, idx) {
    const f = _gdsFares[idx]; if (!f) return;
    const sel = (ro) => document.querySelector(`[data-fare-ro="${ro}"][data-pfx="${pfx}"][data-idx="${idx}"]`);
    const ca=sel('commission_a'); if(ca) ca.value=f.commission_a;
    const gt=sel('govt_tax_b');   if(gt) gt.value=f.govt_tax_b;
    const nf=sel('net_fare');     if(nf) nf.value=f.net_fare;
    const tp=sel('total_payable'); if(tp) tp.value=`BDT ${_fmtN(f.total_payable)}/-`;
}

window.atGdsCalcAll = function(pfx, forceReset) {
    _gdsFares.forEach((_, i) => {
        _gdsCalcFare(pfx, i, forceReset);
        _gdsUpdateRo(pfx, i);
        if (forceReset) {
            const payEl = document.querySelector(`.at-fare-payable[data-pfx="${pfx}"][data-idx="${i}"]`);
            if (payEl) payEl.value = _gdsFares[i].payable;
        }
    });
    atGdsPreview(pfx);
};

window.atGdsApplyIata = function(pfx) {
    const iata = +(document.getElementById(`at-${pfx}-iata`)?.value ?? 0);
    _gdsFares.forEach((f, i) => {
        f.iata_charge=iata; f.payable_edited=false;
        _gdsCalcFare(pfx, i, false); _gdsUpdateRo(pfx, i);
        const iataEl = document.querySelector(`.at-fare-field[data-pfx="${pfx}"][data-idx="${i}"][data-key="iata_charge"]`);
        if (iataEl) iataEl.value = iata;
        const payEl = document.querySelector(`.at-fare-payable[data-pfx="${pfx}"][data-idx="${i}"]`);
        if (payEl) payEl.value = f.payable;
    });
    atGdsPreview(pfx);
};

window.atGdsAddSeg = function(pfx) {
    const n = _gdsSegments.length;
    _gdsSegments.push({ flight:'', class:'', date:'', route:'', departure:'', arrival:'', tag:`D${n+1}`, airline_name:'' });
    const a=document.getElementById(`at-${pfx}-segs-area`); if(a) a.innerHTML=_gdsSegsArea(pfx);
    atGdsPreview(pfx);
};
window.atGdsAddFare = function(pfx) {
    const iata = +(document.getElementById(`at-${pfx}-iata`)?.value ?? 0);
    _gdsFares.push({ type:'ADT', pax:1, base_fare:0, taxes:0, gross_fare:0, commission_a:0, govt_tax_b:0, iata_charge:iata, net_fare:0, payable:0, payable_edited:false, total_payable:0 });
    const a=document.getElementById(`at-${pfx}-fares-area`); if(a) a.innerHTML=_gdsFaresArea(pfx);
};

function _recalcAllFares(pfx) {
    _gdsFares.forEach((_, i) => { _gdsCalcFare(pfx, i, false); _gdsUpdateRo(pfx, i); });
    _gdsFares.forEach((f, i) => {
        const payEl = document.querySelector(`.at-fare-payable[data-pfx="${pfx}"][data-idx="${i}"]`);
        if (payEl) payEl.value = f.payable;
    });
    atGdsPreview(pfx);
}

function _gdsNormalizeRoute(v) {
    v = String(v||'').trim().toUpperCase();
    if (v.includes('-')) return v;
    if (v.length===6) return v.substring(0,3)+'-'+v.substring(3);
    return v;
}

// ── GDS Extract ───────────────────────────────────────────────
window.atExtractGds = async function(pfx) {
    const raw   = document.getElementById(`at-${pfx}-raw`)?.value.trim();
    const errEl = document.getElementById(`at-${pfx}-extract-err`);
    if (!raw) { if(errEl){errEl.textContent='Paste GDS text first';errEl.classList.remove('hidden');} return; }
    if (errEl) errEl.classList.add('hidden');
    const btn = document.getElementById(`at-${pfx}-extract-btn`);
    if (btn) { btn.disabled=true; btn.innerHTML='<i class="fas fa-spinner fa-spin mr-1"></i>Processing…'; }
    try {
        const apiUrl = _cfg.api.airTickets.replace('api/air-tickets/endpoints.php','api/ticket-calculation/extract-gds.php');
        const res    = await fetch(apiUrl, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({raw_gds:raw}) });
        const json   = await res.json();
        if (json.success && json.data) {
            const d = json.data;
            const aEl = document.getElementById(`at-${pfx}-airline`);
            if (aEl && d.airline) aEl.value = d.airline;
            _gdsSegments = _gdsNormalizeSegs(d.segments ?? []);
            _gdsFares    = _gdsNormalizeFares(d.fares ?? []);
            _gdsFares.forEach((_, i) => _gdsCalcFare(pfx, i, false));
            const sA=document.getElementById(`at-${pfx}-segs-area`);  if(sA) sA.innerHTML=_gdsSegsArea(pfx);
            const fA=document.getElementById(`at-${pfx}-fares-area`); if(fA) fA.innerHTML=_gdsFaresArea(pfx);
            _gdsFares.forEach((f, i) => {
                _gdsUpdateRo(pfx, i);
                const payEl=document.querySelector(`.at-fare-payable[data-pfx="${pfx}"][data-idx="${i}"]`);
                if (payEl) payEl.value = f.payable;
            });
            atGdsPreview(pfx);
            atT('success','Extracted!');
        } else {
            if (errEl) { errEl.textContent=json.message??'Extraction failed'; errEl.classList.remove('hidden'); }
            atT('error', json.message ?? 'Failed');
        }
    } catch { atT('error','Network error'); }
    if (btn) { btn.disabled=false; btn.innerHTML='<i class="fas fa-wand-magic-sparkles text-xs"></i>Process GDS'; }
};

function _gdsNormalizeSegs(segs) {
    if (!Array.isArray(segs)) return [];
    return segs.filter(s=>String(s.flight||'').toUpperCase()!=='ARNK').map((s,i) => ({
        line:s.line||i+1, flight:s.flight||'', class:s.class||'', date:s.date||'',
        route:_gdsNormalizeRoute(s.route||''), departure:s.departure||'', arrival:s.arrival||'',
        tag:s.tag||`D${i+1}`, airline_name:s.airline_name||'',
    }));
}
function _gdsNormalizeFares(fares) {
    if (!Array.isArray(fares)||!fares.length) return [{type:'ADT',pax:1,base_fare:0,taxes:0,gross_fare:0,commission_a:0,govt_tax_b:0,iata_charge:0,net_fare:0,payable:0,payable_edited:false,total_payable:0}];
    return fares.map(f => ({
        type:f.type||'ADT', pax:+(f.pax||1), base_fare:+(f.base_fare||0), taxes:+(f.taxes||0),
        gross_fare:+(f.gross_fare||0), commission_a:+(f.commission_a||0), govt_tax_b:+(f.govt_tax_b||0),
        iata_charge:+(f.iata_charge||0), net_fare:+(f.net_fare||0), payable:+(f.payable||0),
        payable_edited:!!(f.payable_edited), total_payable:+(f.total_payable||0),
    }));
}

// ── Preview ────────────────────────────────────────────────────
window.atGdsPreview = function(pfx) {
    const el = document.getElementById(`at-${pfx}-preview`);
    if (el) el.textContent = _gdsGenerateCopy(pfx);
};

function _gdsGenerateCopy(pfx) {
    if (!_gdsSegments.length) return 'No flight segments yet.';
    const grouped={};
    _gdsSegments.forEach(s=>{ const t=s.tag||'D1'; if(!grouped[t]) grouped[t]=[]; grouped[t].push(s); });
    const sortedTags = Object.keys(grouped).sort();
    const routeParts = sortedTags.map(tag=>{
        const g=grouped[tag];
        const first=(g[0].route||'').split('-')[0]?.trim()??'';
        const last=(g[g.length-1].route||'').split('-')[1]?.trim()??'';
        return first&&last?`${first} -> ${last}`:'';
    }).filter(Boolean);
    let tripType='One Way';
    if (_gdsSegments.length>=2) {
        const fO=(_gdsSegments[0].route||'').split('-')[0]?.trim()??'';
        const lD=(_gdsSegments[_gdsSegments.length-1].route||'').split('-')[1]?.trim()??'';
        if (fO&&lD) tripType=fO===lD?'Round Trip':'Multi-City';
    }
    const totalPax = _gdsFares.reduce((s,f)=>s+(+(f.pax||1)),0);
    const airlineName = document.getElementById(`at-${pfx}-airline`)?.value.trim()||'';
    let out=`*${tripType} Ticket*\nRoute: ${routeParts.join(' | ')}\n${totalPax} ${totalPax>1?'Passengers':'Passenger'}\n\n*Flight Info:*\n`;
    sortedTags.forEach((tag,ti)=>{
        const tagSegs=grouped[tag];
        const isReturn=tag.startsWith('R');
        const airLine=tagSegs[0].airline_name||airlineName||'AIRLINE';
        out+=`*${isReturn?'Return':'Depart'} | ${airLine.toUpperCase()}*\n`;
        tagSegs.forEach((seg,si)=>{
            if (si>0) {
                const transit=_gdsTransitTime(tagSegs[si-1],seg);
                out+=`*TRANSIT at ${(seg.route||'').split('-')[0]?.trim()??''}${transit?` (${transit})`:''} *\n`;
            }
            const overnight=_gdsIsOvernight(seg);
            const nextDay=overnight?`-${_gdsNextDayLabel(seg.date)}`:'';
            const aps=(seg.route||'').split('-');
            out+=`*${seg.date||''}* | ${seg.flight||''}\n`;
            out+=`${aps[0]?.trim()??''} ${seg.departure||''} (${_gdsTo12(seg.departure)}) - ${aps[1]?.trim()??''} ${seg.arrival||''} (${_gdsTo12(seg.arrival)})${nextDay}\n`;
        });
        if (ti<sortedTags.length-1) out+='\n';
    });
    out+='\n*Price:*\n';
    let grand=0;
    _gdsFares.forEach(f=>{
        const gross=+(f.gross_fare||0), payable=+(f.payable||0), pax=+(f.pax||1);
        grand+=payable*pax;
        const tl=f.type==='ADT'?'Adult':f.type==='CHD'||f.type==='CNN'?'Child':f.type==='INF'?'Infant':f.type;
        out+=`- ${tl}: Gross BDT ${_fmtN(gross)} per person\n- *Payable: BDT ${_fmtN(payable)}/- per person.*\n`;
    });
    out+=`*Total payable: ${_fmtN(grand)}/-*`;
    return out;
}

window.atGdsCopy = function(pfx) {
    navigator.clipboard.writeText(_gdsGenerateCopy(pfx)).then(()=>atT('success','Copied!')).catch(()=>atT('error','Copy failed'));
};

// ── Time/overnight helpers ────────────────────────────────────
function _gdsIsOvernight(seg) { return !!(seg.departure&&seg.arrival&&parseInt(seg.arrival,10)<parseInt(seg.departure,10)); }
function _gdsNextDayLabel(dateStr) {
    if (!dateStr) return '';
    const yr=new Date().getFullYear();
    const d=new Date(Date.parse(`${dateStr.replace(/(\d+)([A-Z]+)/,'$1 $2')} ${yr}`));
    if (isNaN(d)) return '';
    d.setDate(d.getDate()+1);
    return `${String(d.getDate()).padStart(2,'0')}${d.toLocaleString('en-US',{month:'short'}).toUpperCase()}`;
}
function _gdsTransitTime(prev,curr) {
    if (!prev||!curr) return '';
    const yr=new Date().getFullYear();
    const parse=(ds,ts)=>{ if(!ds||!ts) return null; const hh=ts.substring(0,2),mm=ts.substring(2,4); return new Date(Date.parse(`${ds.replace(/(\d+)([A-Z]+)/,'$1 $2')} ${yr} ${hh}:${mm}`)); };
    let arr=parse(prev.date,prev.arrival); const dep=parse(prev.date,prev.departure); const nextDep=parse(curr.date,curr.departure);
    if (!arr||!dep||!nextDep) return '';
    if (arr<=dep) arr=new Date(arr.getTime()+86400000);
    let diff=nextDep-arr; if(diff<0) diff+=86400000;
    const totalMin=Math.floor(diff/60000), h=Math.floor(totalMin/60), m=totalMin%60;
    return h===0?`${m} mins`:`${String(h).padStart(2,'0')} ${h>1?'hrs':'hr'} ${m} ${m>1?'mins':'min'}`;
}
function _gdsTo12(time) {
    if (!time) return '';
    const s=String(time).padStart(4,'0'), h=parseInt(s.substring(0,2)), m=s.substring(2,4);
    const ampm=h>=12?'PM':'AM', h12=h>12?h-12:h===0?12:h;
    return `${String(h12).padStart(2,'0')}:${m} ${ampm}`;
}

// ── Aliases for save compatibility ────────────────────────────
window.atGenCopy = function(pfx) { atGdsPreview(pfx); };
window.atAddSeg  = window.atGdsAddSeg;
window.atRmSeg   = function(i,pfx){ _gdsSegments.splice(i,1); const a=document.getElementById(`at-${pfx}-segs-area`); if(a) a.innerHTML=_gdsSegsArea(pfx); atGdsPreview(pfx); };
window.atAddFare = window.atGdsAddFare;
window.atRmFare  = function(i,pfx){ _gdsFares.splice(i,1); const a=document.getElementById(`at-${pfx}-fares-area`); if(a) a.innerHTML=_gdsFaresArea(pfx); atGdsPreview(pfx); };

// ── SOTO HTML ─────────────────────────────────────────────────
function _sotoHtml(q) {
    const fd = q?.form_data ?? {};
    return `
    <!-- Screenshot / Text Extract -->
    <div class="mb-4 border border-gray-100 rounded-xl p-4 bg-gray-50">
        <div class="flex items-center justify-between mb-2">
            <label class="text-xs font-bold text-gray-500 uppercase">Extract from Screenshot or Text</label>
            <button onclick="atSotoExtract()"
                class="flex items-center gap-1 px-3 py-1.5 bg-green-700 hover:bg-green-800 text-white rounded-lg text-xs font-semibold transition">
                <i class="fas fa-magic text-xs"></i>Extract & Fill
            </button>
        </div>
        <div id="at-soto-img-zone"
            class="border-2 border-dashed border-gray-200 rounded-lg p-3 text-center cursor-pointer hover:border-indigo-400 transition mb-2"
            onclick="document.getElementById('at-soto-file-inp').click()"
            ondragover="event.preventDefault();this.classList.add('border-indigo-400')"
            ondragleave="this.classList.remove('border-indigo-400')"
            ondrop="event.preventDefault();this.classList.remove('border-indigo-400');atSotoFileDrop(event)">
            <input type="file" id="at-soto-file-inp" class="hidden" accept="image/*" onchange="atSotoFileSelect(this)">
            <i class="fas fa-image text-gray-300 text-xl mb-1 block"></i>
            <p class="text-xs text-gray-400">Drop screenshot / paste (Ctrl+V) or click to browse</p>
            <img id="at-soto-preview" class="hidden max-h-28 mx-auto mt-2 rounded-lg">
        </div>
        <div class="flex items-center gap-2 my-2">
            <div class="flex-1 h-px bg-gray-200"></div>
            <span class="text-[10px] text-gray-400 uppercase">or type text</span>
            <div class="flex-1 h-px bg-gray-200"></div>
        </div>
        <textarea id="at-soto-text" rows="3" placeholder="Paste quotation text here…"
            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-xs resize-none focus:outline-none focus:border-indigo-400"></textarea>
        <div id="at-soto-extract-prog" class="hidden mt-2 text-xs text-green-600"><i class="fas fa-spinner fa-spin mr-1"></i>Extracting…</div>
    </div>

    <!-- Trip type & Route -->
    <div class="grid grid-cols-2 gap-3 mb-4">
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Trip Type</label>
            <select id="at-soto-trip" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-400">
                ${['One Way','Round Trip','Multi City'].map(t=>`<option ${(fd.trip_option??'One Way')===t?'selected':''}>${t}</option>`).join('')}
            </select>
        </div>
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Route</label>
            <input id="at-soto-route" value="${_e(fd.route??'')}" placeholder="DAC - DXB"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-400">
        </div>
    </div>

    <!-- Class + PAX -->
    <div class="grid grid-cols-2 gap-3 mb-4">
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Class</label>
            <div class="flex gap-3 pt-2">
                <label class="flex items-center gap-1.5 text-sm cursor-pointer"><input type="radio" name="at-soto-class" value="Economy" ${(fd.class??'Economy')==='Economy'?'checked':''}> Economy</label>
                <label class="flex items-center gap-1.5 text-sm cursor-pointer"><input type="radio" name="at-soto-class" value="Business" ${fd.class==='Business'?'checked':''}> Business</label>
            </div>
        </div>
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">PAX</label>
            <div class="flex gap-2">
                <div class="flex-1"><label class="text-[10px] text-gray-400">Adult</label><input id="at-soto-adult" type="number" min="0" value="${fd.pax_adult??1}" oninput="atSotoGenCopy()" class="w-full px-2 py-1.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-400"></div>
                <div class="flex-1"><label class="text-[10px] text-gray-400">Child</label><input id="at-soto-child" type="number" min="0" value="${fd.pax_child??0}" class="w-full px-2 py-1.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-400"></div>
                <div class="flex-1"><label class="text-[10px] text-gray-400">Infant</label><input id="at-soto-infant" type="number" min="0" value="${fd.pax_infant??0}" class="w-full px-2 py-1.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-400"></div>
            </div>
        </div>
    </div>

    <!-- Pricing -->
    <div class="space-y-3 mb-4">
        ${[0,1].map(i => `<div class="border border-gray-100 rounded-xl p-3 bg-gray-50">
            <input id="at-soto-bag-${i}" placeholder="Baggage option…" value="${_e(fd.prices?.[i]?.desc??'')}"
                oninput="atSotoGenCopy()"
                class="w-full px-3 py-1.5 border border-gray-200 rounded-lg text-xs mb-2 focus:outline-none focus:border-indigo-400">
            <div class="grid grid-cols-3 gap-2">
                <div><label class="text-[10px] text-gray-400 uppercase">Adult ৳</label>
                    <input type="number" id="at-soto-p${i}-adult" value="${fd.prices?.[i]?.adult??''}" oninput="atSotoGenCopy()" class="w-full px-2 py-1.5 border border-gray-200 rounded-lg text-xs focus:outline-none focus:border-indigo-400"></div>
                <div><label class="text-[10px] text-gray-400 uppercase">Child ৳</label>
                    <input type="number" id="at-soto-p${i}-child" value="${fd.prices?.[i]?.child??''}" oninput="atSotoGenCopy()" class="w-full px-2 py-1.5 border border-gray-200 rounded-lg text-xs focus:outline-none focus:border-indigo-400"></div>
                <div><label class="text-[10px] text-gray-400 uppercase">Infant ৳</label>
                    <input type="number" id="at-soto-p${i}-infant" value="${fd.prices?.[i]?.infant??''}" oninput="atSotoGenCopy()" class="w-full px-2 py-1.5 border border-gray-200 rounded-lg text-xs focus:outline-none focus:border-indigo-400"></div>
            </div>
        </div>`).join('')}
    </div>

    <!-- Refundable / Changeable -->
    <div class="grid grid-cols-2 gap-3 mb-4">
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Refundable</label>
            <div class="flex gap-3 pt-1">
                <label class="flex items-center gap-1.5 text-sm cursor-pointer"><input type="radio" name="at-soto-refund" value="Refundable" ${(fd.refundable??'Refundable')==='Refundable'?'checked':''}> Yes</label>
                <label class="flex items-center gap-1.5 text-sm cursor-pointer"><input type="radio" name="at-soto-refund" value="Non Refundable" ${fd.refundable==='Non Refundable'?'checked':''}> No</label>
            </div>
        </div>
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Changeable</label>
            <div class="flex gap-3 pt-1">
                <label class="flex items-center gap-1.5 text-sm cursor-pointer"><input type="radio" name="at-soto-change" value="Changeable" ${(fd.changeable??'Changeable')==='Changeable'?'checked':''}> Yes</label>
                <label class="flex items-center gap-1.5 text-sm cursor-pointer"><input type="radio" name="at-soto-change" value="Not Changeable" ${fd.changeable==='Not Changeable'?'checked':''}> No</label>
            </div>
        </div>
    </div>

    <!-- Business Markup -->
    <div class="border border-indigo-100 rounded-xl p-3 bg-indigo-50 mb-4">
        <label class="text-xs font-bold text-indigo-600 uppercase block mb-2">Business Markup</label>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="text-[10px] text-gray-500 uppercase">Markup %</label>
                <input type="number" id="at-soto-pct" value="${fd.percentage??0}" min="0" step="0.01" oninput="atSotoGenCopy()"
                    class="w-full px-3 py-2 border border-indigo-200 rounded-lg text-sm focus:outline-none focus:border-indigo-400 bg-white">
            </div>
            <div>
                <label class="text-[10px] text-gray-500 uppercase">Fixed Add (৳)</label>
                <input type="number" id="at-soto-fixed" value="${fd.ve_fixed_price??0}" min="0" step="0.01" oninput="atSotoGenCopy()"
                    class="w-full px-3 py-2 border border-indigo-200 rounded-lg text-sm focus:outline-none focus:border-indigo-400 bg-white">
            </div>
        </div>
        <p class="text-[10px] text-indigo-400 mt-1">Formula: final = (base × (1 + %/100)) + fixed</p>
    </div>

    <!-- Formatted Output -->
    <div class="mb-4">
        <div class="flex items-center justify-between mb-1">
            <label class="text-xs font-bold text-gray-500 uppercase">Formatted Output</label>
            <div class="flex gap-2">
                <button onclick="atSotoGenCopy()" class="text-xs text-indigo-500 hover:text-indigo-700 font-semibold"><i class="fas fa-sync-alt mr-1"></i>Generate</button>
                <button onclick="navigator.clipboard.writeText(document.getElementById('at-soto-raw-out').value);atT('success','Copied!')" class="text-xs text-gray-500 hover:text-gray-700 font-semibold"><i class="fas fa-copy mr-1"></i>Raw</button>
                <button onclick="navigator.clipboard.writeText(document.getElementById('at-soto-biz-out').value);atT('success','Copied!')" class="text-xs text-green-600 hover:text-green-700 font-semibold"><i class="fas fa-copy mr-1"></i>Business</button>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-2">
            <div>
                <label class="text-[10px] text-gray-400 uppercase mb-1 block">Raw (original prices)</label>
                <textarea id="at-soto-raw-out" rows="6" readonly
                    class="w-full px-3 py-2 border border-gray-100 bg-gray-50 rounded-xl text-xs font-mono resize-none"
                    placeholder="Click Generate…">${_e(fd.raw_text??'')}</textarea>
            </div>
            <div>
                <label class="text-[10px] text-green-600 uppercase mb-1 block">Business (with markup — to send)</label>
                <textarea id="at-soto-biz-out" rows="6" readonly
                    class="w-full px-3 py-2 border border-green-100 bg-green-50 rounded-xl text-xs font-mono resize-none"
                    placeholder="Click Generate…">${_e(fd.business_text??'')}</textarea>
            </div>
        </div>
    </div>

    <!-- Notes -->
    <div class="mb-4" id="at-soto-notes-wrap">
        <div class="flex items-center justify-between mb-1">
            <label class="text-xs font-bold text-gray-500 uppercase">Notes</label>
            <button onclick="atSotoAddNote()" class="text-xs text-indigo-500 hover:text-indigo-700 font-semibold"><i class="fas fa-plus mr-1"></i>Add</button>
        </div>
        <div id="at-soto-notes-list" class="space-y-1.5">
            ${(fd.notes??[]).map((n,i)=>`<div class="flex gap-2 items-center">
                <input value="${_e(n)}" oninput="atSotoNoteChange(${i},this.value)" class="flex-1 px-3 py-1.5 border border-gray-200 rounded-lg text-xs focus:outline-none focus:border-indigo-400">
                <button onclick="atSotoRmNote(${i})" class="text-red-400 hover:text-red-600 text-xs w-6 h-6 flex items-center justify-center"><i class="fas fa-times"></i></button>
            </div>`).join('')}
        </div>
    </div>

    <button onclick="atSaveQ('soto')" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition">
        <i class="fas fa-save mr-1.5"></i>${_activeQSysId ? 'Update' : 'Save'} Quotation
    </button>`;
}

// ── SOTO helpers ──────────────────────────────────────────────
let _sotoNotes = [];
let _sotoFile = null;

window.atSotoFileSelect = function(input) {
    if (!input.files[0]) return;
    _sotoFile = input.files[0];
    const preview = document.getElementById('at-soto-preview');
    if (preview) { preview.src = URL.createObjectURL(_sotoFile); preview.classList.remove('hidden'); }
    document.getElementById('at-soto-img-zone')?.classList.add('border-green-400','bg-green-50');
};

window.atSotoFileDrop = function(e) {
    const f = e.dataTransfer.files[0];
    if (f && f.type.startsWith('image/')) {
        _sotoFile = f;
        const preview = document.getElementById('at-soto-preview');
        if (preview) { preview.src = URL.createObjectURL(f); preview.classList.remove('hidden'); }
    }
};

// Ctrl+V paste support for SOTO
document.addEventListener('paste', function(e) {
    if (_activeTab !== 'quotation') return;
    const typeEl = document.querySelector('input[name="at-q-type"]:checked');
    if (typeEl?.value !== 'soto') return;
    const item = [...(e.clipboardData?.items??[])].find(i => i.type.startsWith('image/'));
    if (item) { 
        _sotoFile = item.getAsFile(); 
        const preview = document.getElementById('at-soto-preview'); 
        if(preview){ 
            preview.src=URL.createObjectURL(_sotoFile); 
            preview.classList.remove('hidden'); 
        } 
    }
});

window.atSotoExtract = async function() {
    const text = document.getElementById('at-soto-text')?.value.trim();
    if (!_sotoFile && !text) { atT('error','Upload a screenshot or paste text first'); return; }
    const prog = document.getElementById('at-soto-extract-prog');
    prog?.classList.remove('hidden');
    const fd = new FormData();
    if (_sotoFile) { fd.append('mode','image'); fd.append('screenshot', _sotoFile); }
    else           { fd.append('mode','text');  fd.append('text_input', text); }
    try {
        const apiUrl = _cfg.api.airTickets.replace('api/air-tickets/endpoints.php','api/tickets/ss-extraction.php');
        const res    = await fetch(apiUrl, { method:'POST', body:fd });
        const json   = await res.json();
        if (json.success && json.data) {
            _sotoFillForm(json.data);
            atT('success','Extracted & filled!');
        } else { atT('error', json.message ?? 'Extraction failed'); }
    } catch { atT('error','Network error'); }
    prog?.classList.add('hidden');
};

function _sotoFillForm(data) {
    const trip = document.getElementById('at-soto-trip');
    if (trip && data.trip_option) trip.value = data.trip_option;
    const route = document.getElementById('at-soto-route');
    if (route && data.route) route.value = data.route;
    if (data.class) {
        const r = document.querySelector(`input[name="at-soto-class"][value="${data.class}"]`);
        if (r) r.checked = true;
    }
    if (data.pax_adult  !== undefined) { const e=document.getElementById('at-soto-adult');  if(e) e.value=data.pax_adult; }
    if (data.pax_child  !== undefined) { const e=document.getElementById('at-soto-child');  if(e) e.value=data.pax_child; }
    if (data.pax_infant !== undefined) { const e=document.getElementById('at-soto-infant'); if(e) e.value=data.pax_infant; }
    atSotoGenCopy();
}

window.atSotoGenCopy = function() {
    const trip    = document.getElementById('at-soto-trip')?.value ?? 'One Way';
    const route   = document.getElementById('at-soto-route')?.value ?? '';
    const cls     = document.querySelector('input[name="at-soto-class"]:checked')?.value ?? 'Economy';
    const adult   = +(document.getElementById('at-soto-adult')?.value??0);
    const child   = +(document.getElementById('at-soto-child')?.value??0);
    const infant  = +(document.getElementById('at-soto-infant')?.value??0);
    const refund  = document.querySelector('input[name="at-soto-refund"]:checked')?.value ?? '';
    const change  = document.querySelector('input[name="at-soto-change"]:checked')?.value ?? '';
    const pct     = +(document.getElementById('at-soto-pct')?.value??0);
    const fixed   = +(document.getElementById('at-soto-fixed')?.value??0);
    const label   = trip==='One Way'?'One Way Ticket':trip==='Round Trip'?'Round Ticket':'Multi City Ticket';

    const paxParts = [adult>0?`${adult} Adult`:'', child>0?`${child} Child`:'', infant>0?`${infant} Infant`:''].filter(Boolean).join(', ');

    const applyMarkup = (price) => { const b=+price; if(!b||b<=0) return ''; return Math.round(b + b*pct/100 + fixed); };

    let raw='', biz='';
    raw += `*${label}*\nRoute: ${route} ${paxParts}\n${cls}\n\n*Price:*\n`;
    biz += `*${label}*\nRoute: ${route} ${paxParts}\n${cls}\n\n*Price:*\n`;

    [0,1].forEach(i => {
        const bag   = document.getElementById(`at-soto-bag-${i}`)?.value ?? '';
        const pa    = +(document.getElementById(`at-soto-p${i}-adult`)?.value??0);
        const pc    = +(document.getElementById(`at-soto-p${i}-child`)?.value??0);
        const pi    = +(document.getElementById(`at-soto-p${i}-infant`)?.value??0);
        if (!pa && !pc && !pi) return;
        const rawParts = [pa>0?`Adult ${pa.toLocaleString('en-BD')}`:'', pc>0?`Child ${pc.toLocaleString('en-BD')}`:'', pi>0?`Infant ${pi.toLocaleString('en-BD')}`:''].filter(Boolean).join(' | ');
        const bizPa = applyMarkup(pa), bizPc = applyMarkup(pc), bizPi = applyMarkup(pi);
        const bizParts = [bizPa?`Adult ${Number(bizPa).toLocaleString('en-BD')}`:'', bizPc?`Child ${Number(bizPc).toLocaleString('en-BD')}`:'', bizPi?`Infant ${Number(bizPi).toLocaleString('en-BD')}`:''].filter(Boolean).join(' | ');
        raw += `• ${bag}: BDT -> ${rawParts}\n`;
        biz += `• ${bag}: BDT -> ${bizParts}\n`;
    });

    raw += `\n${refund} | ${change}`;
    biz += `\n${refund} | ${change}`;

    const rawEl = document.getElementById('at-soto-raw-out');
    const bizEl = document.getElementById('at-soto-biz-out');
    if (rawEl) rawEl.value = raw;
    if (bizEl) bizEl.value = biz;
};

// SOTO notes helpers
window.atSotoAddNote = function() {
    _sotoNotes.push('');
    const list = document.getElementById('at-soto-notes-list');
    if (!list) return;
    const i = _sotoNotes.length - 1;
    const div = document.createElement('div');
    div.className = 'flex gap-2 items-center';
    div.id = `at-soto-note-${i}`;
    div.innerHTML = `<input value="" oninput="atSotoNoteChange(${i},this.value)" class="flex-1 px-3 py-1.5 border border-gray-200 rounded-lg text-xs focus:outline-none focus:border-indigo-400"><button onclick="atSotoRmNote(${i})" class="text-red-400 hover:text-red-600 text-xs w-6 h-6 flex items-center justify-center"><i class="fas fa-times"></i></button>`;
    list.appendChild(div);
};
window.atSotoNoteChange = function(i, val) { _sotoNotes[i] = val; };
window.atSotoRmNote = function(i) { _sotoNotes.splice(i,1); document.getElementById(`at-soto-note-${i}`)?.remove(); };

// Save quotation (GDS or SOTO)
window.atSaveQ = async function(pfx) {
    const typeEl = document.querySelector('input[name="at-q-type"]:checked');
    const qType  = typeEl?.value ?? 'gds';
    let body = { type: qType };

    if (qType === 'gds' || pfx === 'b') {
        atGenCopy(pfx === 'b' ? 'b' : 'q');
        body.airline       = document.getElementById(`at-${pfx==='b'?'b':'q'}-airline`)?.value ?? '';
        body.segments_json = _gdsSegments;
        body.pricing_json  = _gdsFares;
        body.raw_input     = document.getElementById(`at-${pfx==='b'?'b':'q'}-raw`)?.value ?? '';
        body.copy_text     = document.getElementById(`at-${pfx==='b'?'b':'q'}-copy`)?.value ?? '';
        body.gross_fare    = _gdsFares.reduce((s,f) => s+(f.gross_fare??0)*(f.pax??1), 0);
        body.net_fare      = _gdsFares.reduce((s,f) => s+(f.net_fare??0)*(f.pax??1), 0);
        body.total_payable = _gdsFares.reduce((s,f) => s+(f.total_payable??0), 0);
    } else {
        atSotoGenCopy();
        const trip   = document.getElementById('at-soto-trip')?.value ?? '';
        const cls    = document.querySelector('input[name="at-soto-class"]:checked')?.value ?? '';
        const refund = document.querySelector('input[name="at-soto-refund"]:checked')?.value ?? '';
        const change = document.querySelector('input[name="at-soto-change"]:checked')?.value ?? '';
        const adult  = +(document.getElementById('at-soto-adult')?.value ?? 1);
        const child  = +(document.getElementById('at-soto-child')?.value ?? 0);
        const infant = +(document.getElementById('at-soto-infant')?.value ?? 0);
        const pct    = +(document.getElementById('at-soto-pct')?.value ?? 0);
        const fixed  = +(document.getElementById('at-soto-fixed')?.value ?? 0);
        const prices = [0,1].map(i => ({
            desc:   document.getElementById(`at-soto-bag-${i}`)?.value ?? '',
            adult:  +(document.getElementById(`at-soto-p${i}-adult`)?.value ?? 0),
            child:  +(document.getElementById(`at-soto-p${i}-child`)?.value ?? 0),
            infant: +(document.getElementById(`at-soto-p${i}-infant`)?.value ?? 0),
        }));
        const rawText = document.getElementById('at-soto-raw-out')?.value ?? '';
        const bizText = document.getElementById('at-soto-biz-out')?.value ?? '';

        body.title      = `${trip} — ${document.getElementById('at-soto-route')?.value ?? ''}`;
        body.copy_text  = bizText;
        body.raw_input  = rawText;
        body.form_data  = {
            trip_option: trip, class: cls,
            route: document.getElementById('at-soto-route')?.value ?? '',
            pax_adult: adult, pax_child: child, pax_infant: infant,
            refundable: refund, changeable: change,
            prices, percentage: pct, ve_fixed_price: fixed,
            raw_text: rawText, business_text: bizText,
            notes: _sotoNotes.filter(Boolean),
        };
        const applyM = (p) => Math.round((+p || 0) * (1 + pct/100) + fixed);
        body.total_payable = applyM(prices[0].adult) * adult;
        body.gross_fare    = prices[0].adult * adult;
    }

    if (pfx === 'b') {
        body.pnr        = document.getElementById('at-b-pnr')?.value ?? '';
        body.ticket_nos = (document.getElementById('at-b-tickets')?.value ?? '').split(',').map(t=>t.trim()).filter(Boolean);
        if (_activeBSysId) { body.action='update_booking'; body.booking_sys_id=_activeBSysId; }
        else body.action = 'save_booking';
    } else {
        if (_activeQSysId) { body.action='update_quotation'; body.quotation_sys_id=_activeQSysId; }
        else body.action = 'save_quotation';
    }

    try {
        const json = await _api(body);
        if (json.status === 'success') {
            atT('success', pfx==='b' ? (_activeBSysId?'Updated!':'Booking saved!') : (_activeQSysId?'Updated!':'Saved!'));
            if (pfx==='b') _activeBSysId = json.booking_sys_id ?? _activeBSysId;
            await _reload();
            pfx==='b' ? _renderBooking() : _renderQuotation();
        } else { atT('error', json.message); }
    } catch { atT('error','Network error'); }
};

window.atDeleteQ = async function() {
    if (!_activeQSysId || !confirm('Delete quotation?')) return;
    try {
        const json = await _api({ action:'delete_quotation', quotation_sys_id:_activeQSysId });
        if (json.status==='success') { atT('success','Deleted'); await _reload(); _activeQSysId=null; _renderQuotation(); }
        else atT('error', json.message);
    } catch { atT('error','Network error'); }
};

window.atMoveToBooking = async function() {
    if (!_activeQSysId || !confirm('Move to Booking?')) return;
    try {
        const json = await _api({ action:'move_to_booking', quotation_sys_id:_activeQSysId });
        if (json.status==='success') {
            atT('success','Moved to Booking!');
            await _reload(); _activeQSysId=null; _renderQuotation();
            const btn = document.querySelector('.at-tab[data-tab="booking"]');
            if (btn) _switchTab('booking', btn);
        } else { atT('error', json.message); }
    } catch { atT('error','Network error'); }
};

// ═══════════════════════════════════════════════════════════════
// TAB 2 — QUOTATION (continued)
// ═══════════════════════════════════════════════════════════════
let _qSelectedIds = new Set();

function _renderQuotation() {
    const panel      = document.getElementById('at-panel-quotation');
    const quotations = _atData?.at_quotations ?? [];
    panel.innerHTML = `
    <div class="flex gap-4">
        <!-- LEFT: unified quotation list -->
        <div style="width:220px;flex-shrink:0;">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Quotations</span>
                <div class="flex gap-1.5">
                    <button onclick="atNewQuotation()"
                        class="flex items-center gap-1 px-2.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold transition">
                        <i class="fas fa-plus text-xs"></i>New
                    </button>
                </div>
            </div>
            <div id="at-q-list" class="space-y-1.5">
                ${quotations.length ? quotations.map(_qCard).join('') : '<p class="text-xs text-gray-300 text-center py-6">No quotations yet.</p>'}
            </div>
            <div id="at-q-move-multi" class="hidden mt-3">
                <div class="text-xs text-gray-500 mb-1.5 text-center" id="at-q-sel-count">0 selected</div>
                <button onclick="atMoveSelectedToBooking()"
                    class="w-full py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-xs font-semibold transition">
                    <i class="fas fa-arrow-right mr-1"></i>Move Selected to Booking
                </button>
            </div>
        </div>

        <!-- RIGHT: builder -->
        <div class="flex-1 min-w-0" id="at-q-builder">
            <div class="flex items-center justify-center h-40 text-gray-300 text-sm">
                <div class="text-center">
                    <i class="fas fa-plus-circle text-3xl mb-2 block opacity-30"></i>
                    Click New or select a quotation
                </div>
            </div>
        </div>
    </div>`;
}

function _qPhaseInfo(q) {
    if (q.source_booking) {
        return { label: 'Revision', pill: 'at-pill-sent', dot: 'bg-blue-400' };
    }
    if (q.note?.includes('Auto-created from booking tab') || q.note?.includes('Direct')) {
        return { label: 'Direct', pill: 'at-pill-draft', dot: 'bg-teal-400' };
    }
    const sMap = {
        draft:            { label: 'Draft',      pill: 'at-pill-draft',   dot: 'bg-gray-400' },
        sent:             { label: 'Sent',       pill: 'at-pill-sent',    dot: 'bg-blue-400' },
        moved_to_booking: { label: '→ Booking', pill: 'at-pill-moved',   dot: 'bg-green-500' },
        cancelled:        { label: 'Cancelled',  pill: 'at-pill-cancelled', dot: 'bg-red-400' },
    };
    return sMap[q.status] ?? { label: q.status, pill: 'at-pill-draft', dot: 'bg-gray-300' };
}

function _qCard(q) {
    const phase     = _qPhaseInfo(q);
    const quotations = _atData?.at_quotations ?? [];
    const route     = (q.segments_json?.[0]?.route ?? q.form_data?.route ?? '')
                    + (q.segments_json?.length > 1 ? ' +' + (q.segments_json.length - 1) : '');
    const typePill  = q.type === 'soto'
        ? '<span class="at-pill at-pill-sent" style="font-size:.6rem">SOTO</span>'
        : '<span class="at-pill at-pill-draft" style="font-size:.6rem">GDS</span>';

    let isMovable = false;
    if (q.status === 'draft' || q.status === 'sent') {
        isMovable = true;
    } else if (q.source_booking) {
        isMovable = true;
    } else if (q.status === 'moved_to_booking') {
        const bookings = _atData?.at_bookings ?? [];
        const myBooking = bookings.find(b => b.quotation_sys_id === q.sys_id);
        if (myBooking) {
            const hasRevision = quotations.some(r => r.source_booking === myBooking.sys_id);
            isMovable = hasRevision;
        }
    }

    const isSelected = _qSelectedIds.has(q.sys_id);
    const dimmed     = q.status === 'cancelled' ? 'opacity-40' : '';

    return `<div class="at-q-card ${_activeQSysId===q.sys_id?'active':''} ${dimmed}" onclick="atSelectQuotation('${_e(q.sys_id)}')">
        <div class="flex items-center justify-between mb-1">
            <div class="flex items-center gap-1.5">
                <div class="w-2 h-2 rounded-full ${phase.dot} flex-shrink-0"></div>
                <span class="font-mono text-[10px] text-gray-400">${_e(q.sys_id)}</span>
            </div>
            <div class="flex items-center gap-1">
                ${typePill}
                ${isMovable ? `<input type="checkbox" class="at-q-chk w-3.5 h-3.5 rounded cursor-pointer" data-qid="${_e(q.sys_id)}"
                    ${isSelected?'checked':''} onclick="event.stopPropagation();atToggleQSel('${_e(q.sys_id)}',this)">` : ''}
            </div>
        </div>
        <div class="text-xs font-semibold text-gray-700 truncate">${_e(q.airline||q.title||'—')}</div>
        <div class="text-[11px] text-gray-400">${_e(route)}</div>
        <div class="flex items-center justify-between mt-1">
            <span class="text-[11px] font-bold text-indigo-600">৳ ${_fmt(q.total_payable)}</span>
            <span class="at-pill ${phase.pill}" style="font-size:.6rem">${phase.label}</span>
        </div>
        ${q.note ? `<div class="text-[10px] text-gray-300 mt-0.5 truncate" title="${_e(q.note)}">${_e(q.note)}</div>` : ''}
    </div>`;
}

window.atToggleQSel = function(sysId, cb) {
    if (cb.checked) _qSelectedIds.add(sysId);
    else            _qSelectedIds.delete(sysId);
    const btn = document.getElementById('at-q-move-multi');
    const cnt = document.getElementById('at-q-sel-count');
    if (_qSelectedIds.size > 0) {
        btn?.classList.remove('hidden');
        if (cnt) cnt.textContent = `${_qSelectedIds.size} selected`;
    } else {
        btn?.classList.add('hidden');
    }
};

window.atNewQuotation = function() {
    _activeQSysId = null; _gdsSegments = []; _gdsFares = [];
    _renderQBuilder(null);
    _qSelectedIds.clear();
    document.getElementById('at-q-move-multi')?.classList.add('hidden');
};

window.atSelectQuotation = function(sysId) {
    _activeQSysId = sysId;
    const q = (_atData?.at_quotations ?? []).find(x => x.sys_id === sysId);
    if (!q) return;
    document.querySelectorAll('#at-q-list .at-q-card').forEach(c => c.classList.remove('active'));
    event?.currentTarget?.classList.add('active');
    _gdsSegments = q.segments_json ?? [];
    _gdsFares    = q.pricing_json  ?? [];
    _renderQBuilder(q);
    if (q.type === 'gds' || !q.type) setTimeout(() => _recalcAllFares('q'), 50);
};

function _renderQBuilder(q) {
    const builder = document.getElementById('at-q-builder');
    if (!builder) return;
    const type = q?.type ?? 'gds';
    builder.innerHTML = `
    <div class="flex items-center gap-3 mb-4">
        <span class="text-xs font-bold text-gray-400 uppercase">Type:</span>
        <label class="flex items-center gap-1.5 cursor-pointer">
            <input type="radio" name="at-q-type" value="gds" ${type==='gds'?'checked':''} onchange="atQTypeChange('gds')">
            <span class="text-sm">GDS</span>
        </label>
        <label class="flex items-center gap-1.5 cursor-pointer">
            <input type="radio" name="at-q-type" value="soto" ${type==='soto'?'checked':''} onchange="atQTypeChange('soto')">
            <span class="text-sm">SOTO</span>
        </label>
    </div>
    <div id="at-q-body">${type === 'gds' ? _gdsHtml(q, 'q') : _sotoHtml(q)}</div>`;
}

window.atQTypeChange = function(type) {
    const body = document.getElementById('at-q-body');
    if (body) body.innerHTML = type === 'gds' ? _gdsHtml(null, 'q') : _sotoHtml(null);
};

window.atMoveSelectedToBooking = async function() {
    if (_qSelectedIds.size === 0) return;
    const ids = [..._qSelectedIds];
    if (!confirm(`Move ${ids.length} quotation(s) to Booking?`)) return;
    let successCount = 0;
    for (const qSysId of ids) {
        try {
            const json = await _api({ action: 'move_to_booking', quotation_sys_id: qSysId });
            if (json.status === 'success') successCount++;
        } catch {}
    }
    _qSelectedIds.clear();
    await _reload();
    _renderQuotation();
    atT('success', `${successCount} quotation(s) moved to Booking!`);
    if (successCount > 0) {
        const btn = document.querySelector('.at-tab[data-tab="booking"]');
        if (btn) _switchTab('booking', btn);
    }
};

// ═══════════════════════════════════════════════════════════════
// TAB 3 — BOOKING
// ═══════════════════════════════════════════════════════════════
function _renderBooking() {
    const panel      = document.getElementById('at-panel-booking');
    const bookings   = _atData?.at_bookings   ?? [];
    const quotations = _atData?.at_quotations ?? [];

    panel.innerHTML = `
    <div class="flex gap-4">
        <!-- LEFT: All quotations as booking-phase list -->
        <div style="width:220px;flex-shrink:0;">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Bookings</span>
                <button onclick="atNewBooking()"
                    class="flex items-center gap-1 px-2.5 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-xs font-semibold transition">
                    <i class="fas fa-plus text-xs"></i>New
                </button>
            </div>
            <div id="at-b-list" class="space-y-1.5">
                ${bookings.length
                    ? bookings.map(_bCard).join('')
                    : '<p class="text-xs text-gray-300 text-center py-4">No bookings yet.<br>Move from Quotation or create New.</p>'}
            </div>
            ${(() => {
                const quotations = _atData?.at_quotations ?? [];
                const latestPerBooking = {};
                bookings.forEach(b => {
                    const revisions = quotations.filter(q => q.source_booking === b.sys_id);
                    if (revisions.length) {
                        latestPerBooking[b.sys_id] = revisions[revisions.length - 1];
                    } else {
                        const orig = quotations.find(q => q.status === 'moved_to_booking' && !q.source_booking);
                        if (orig) latestPerBooking[b.sys_id] = orig;
                    }
                });
                const refs = Object.values(latestPerBooking);
                if (!refs.length) return '';
                return `<div class="mt-3 pt-3 border-t border-gray-100">
                    <p class="text-[10px] font-bold text-gray-400 uppercase mb-2">Latest Quotation Refs</p>
                    <div class="space-y-1">
                        ${refs.map(q => `
                        <div class="text-[10px] text-gray-400 flex items-center gap-1.5 py-1 border-b border-gray-50">
                            <span class="font-mono text-indigo-400">${_e(q.sys_id)}</span>
                            <span class="flex-1 truncate text-gray-300">${_e(q.airline||q.title||'')}</span>
                            <span class="text-[9px] font-bold ${q.source_booking?'text-blue-400':'text-green-500'}">${q.source_booking?'Rev':'Orig'}</span>
                        </div>`).join('')}
                    </div>
                </div>`;
            })()}
        </div>

        <!-- RIGHT: booking builder -->
        <div class="flex-1 min-w-0" id="at-b-builder">
            <div class="flex items-center justify-center h-40 text-gray-300 text-sm">
                <div class="text-center"><i class="fas fa-bookmark text-3xl mb-2 block opacity-30"></i>Select or create a booking</div>
            </div>
        </div>
    </div>`;
}

function _bCard(b) {
    const sMap = { tentative:'at-pill-tentative', confirmed:'at-pill-confirmed', failed:'at-pill-failed', cancelled:'at-pill-cancelled' };
    const sDot = { tentative:'bg-yellow-400', confirmed:'bg-green-500', failed:'bg-red-400', cancelled:'bg-gray-400' };
    const route = (b.segments_json?.[0]?.route ?? b.form_data?.route ?? '')
                + (b.segments_json?.length > 1 ? ' +' + (b.segments_json.length - 1) : '');
    const typePill = b.type === 'soto'
        ? '<span class="at-pill at-pill-sent" style="font-size:.6rem">SOTO</span>'
        : '<span class="at-pill at-pill-draft" style="font-size:.6rem">GDS</span>';
    const isInConf = (_atData?.at_confirmations ?? []).some(c => c.booking_sys_id === b.sys_id && c.status !== 'failed' && c.status !== 'cancelled');
    return `<div class="at-q-card ${_activeBSysId===b.sys_id?'active':''}" onclick="atSelectBooking('${_e(b.sys_id)}')">
        <div class="flex items-center justify-between mb-1">
            <div class="flex items-center gap-1.5">
                <div class="w-2 h-2 rounded-full ${sDot[b.status]??'bg-gray-400'} flex-shrink-0"></div>
                <span class="font-mono text-[10px] text-green-600">${_e(b.sys_id)}</span>
            </div>
            <div class="flex items-center gap-1">
                ${typePill}
                ${isInConf ? '<span class="text-[9px] bg-indigo-100 text-indigo-600 px-1.5 py-0.5 rounded font-bold">In Conf</span>' : ''}
            </div>
        </div>
        <div class="text-xs font-semibold text-gray-700 truncate">${_e(b.airline||b.title||'—')}</div>
        ${b.pnr ? `<div class="text-[11px] text-gray-400">PNR: ${_e(b.pnr)}</div>` : ''}
        <div class="text-[11px] text-gray-400 truncate">${_e(route)}</div>
        <div class="flex items-center justify-between mt-1">
            <span class="text-[11px] font-bold text-green-600">৳ ${_fmt(b.total_payable)}</span>
            <span class="at-pill ${sMap[b.status]??'at-pill-tentative'}" style="font-size:.6rem">${b.status}</span>
        </div>
        ${b.quotation_sys_id ? `<div class="text-[10px] text-gray-300 mt-0.5">From: ${_e(b.quotation_sys_id)}</div>` : ''}
    </div>`;
}

window.atNewBooking = function() {
    _activeBSysId = null; _gdsSegments = []; _gdsFares = [];
    _renderBBuilder(null);
};

window.atSelectBooking = function(sysId) {
    _activeBSysId = sysId;
    const b = (_atData?.at_bookings ?? []).find(x => x.sys_id === sysId);
    if (!b) return;
    document.querySelectorAll('#at-b-list .at-q-card').forEach(c => c.classList.remove('active'));
    event?.currentTarget?.classList.add('active');
    _gdsSegments = b.segments_json ?? [];
    _gdsFares    = b.pricing_json  ?? [];
    _renderBBuilder(b);
    if (b.type === 'gds' || !b.type) setTimeout(() => _recalcAllFares('b'), 50);
};

function _renderBBuilder(b) {
    const builder = document.getElementById('at-b-builder');
    if (!builder) return;
    const type = b?.type ?? 'gds';
    builder.innerHTML = `
    ${!b ? `<div class="flex items-center gap-3 mb-4">
        <span class="text-xs font-bold text-gray-400 uppercase">Type:</span>
        <label class="flex items-center gap-1.5 cursor-pointer">
            <input type="radio" name="at-b-type-sel" value="gds" checked onchange="atBTypeChange('gds')"><span class="text-sm">GDS</span>
        </label>
        <label class="flex items-center gap-1.5 cursor-pointer">
            <input type="radio" name="at-b-type-sel" value="soto" onchange="atBTypeChange('soto')"><span class="text-sm">SOTO</span>
        </label>
    </div>` : `<div class="flex items-center gap-2 mb-4">
        <span class="text-xs font-bold text-gray-400 uppercase">Type:</span>
        <span class="at-pill ${type==='soto'?'at-pill-sent':'at-pill-draft'}">${type.toUpperCase()}</span>
        <span class="text-xs text-gray-400 font-mono ml-auto">${_e(b.sys_id)}</span>
    </div>`}

    <div class="grid grid-cols-2 gap-3 mb-4">
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">PNR / Ref No</label>
            <input id="at-b-pnr" value="${_e(b?.pnr??'')}" placeholder="e.g. ABC123"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-400">
        </div>
        <div>
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Airline</label>
            <input id="at-b-airline" value="${_e(b?.airline??'')}" placeholder="e.g. Biman"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-400">
        </div>
    </div>
    <div class="mb-4">
        <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Ticket Numbers</label>
        <input id="at-b-tickets" value="${_e((b?.ticket_nos??[]).join(', '))}" placeholder="123-4567890, 123-4567891"
            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-400">
    </div>

    <div id="at-b-body">${type === 'soto' ? _sotoHtml(b) : _gdsHtml(b, 'b')}</div>

    ${b && !(_atData?.at_confirmations??[]).some(c=>c.booking_sys_id===b.sys_id&&c.status!=='failed'&&c.status!=='cancelled') ? `
    <div class="my-3">
        <button onclick="atMoveToConfirmation('${_e(b.sys_id)}')"
            class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold transition flex items-center justify-center gap-2">
            <i class="fas fa-check-circle"></i>Move to Confirmation
        </button>
    </div>` : b ? '<div class="my-3 text-center text-xs text-indigo-500 bg-indigo-50 py-2 rounded-lg border border-indigo-100">Already in Confirmation</div>' : ''}`;
}

window.atBTypeChange = function(type) {
    const body = document.getElementById('at-b-body');
    if (body) body.innerHTML = type === 'soto' ? _sotoHtml(null) : _gdsHtml(null, 'b');
};

window.atDeleteB = async function() {
    if (!_activeBSysId || !confirm('Delete booking?')) return;
    try {
        const json = await _api({ action:'delete_booking', booking_sys_id:_activeBSysId });
        if (json.status==='success') { atT('success','Deleted'); await _reload(); _activeBSysId=null; _renderBooking(); }
        else atT('error', json.message);
    } catch { atT('error','Network error'); }
};

window.atMoveToConfirmation = async function(bSysId) {
    if (!confirm('Move this booking to Confirmation?')) return;
    try {
        const json = await _api({ action:'add_to_confirmation', booking_sys_id: bSysId });
        if (json.status === 'success') {
            atT('success','Moved to Confirmation!');
            await _reload();
            const btn = document.querySelector('.at-tab[data-tab="confirmation"]');
            if (btn) _switchTab('confirmation', btn);
        } else { atT('error', json.message); }
    } catch { atT('error','Network error'); }
};

// ═══════════════════════════════════════════════════════════════
// TAB 4 — CONFIRMATION
// ═══════════════════════════════════════════════════════════════
function _renderConfirmation() {
    const panel         = document.getElementById('at-panel-confirmation');
    const confirmations = _atData?.at_confirmations ?? [];
    const bookings      = _atData?.at_bookings      ?? [];

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
}

function _confCard(c, bookings) {
    const b       = bookings.find(x => x.sys_id === c.booking_sys_id);
    const sMap    = { pending:'at-pill-tentative', confirmed:'at-pill-confirmed', failed:'at-pill-failed', cancelled:'at-pill-cancelled' };
    const sDot    = { pending:'bg-yellow-400', confirmed:'bg-green-500', failed:'bg-red-400', cancelled:'bg-gray-400' };
    const typePill = b?.type === 'soto'
        ? '<span class="at-pill at-pill-sent" style="font-size:.6rem">SOTO</span>'
        : '<span class="at-pill at-pill-draft" style="font-size:.6rem">GDS</span>';
    return `<div class="at-q-card ${c.sys_id===_activeConfId?'active':''}" onclick="atSelectConf('${_e(c.sys_id)}')">
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

let _activeConfId = null;

window.atOpenAddConf = function() {
    document.getElementById('at-add-conf-modal')?.classList.remove('hidden');
};

window.atAddConfirmation = async function() {
    const bSysId = document.getElementById('at-add-conf-select')?.value;
    if (!bSysId) return;
    document.getElementById('at-add-conf-modal')?.classList.add('hidden');
    try {
        const json = await _api({ action:'add_to_confirmation', booking_sys_id: bSysId });
        if (json.status === 'success') { atT('success','Added to Confirmation!'); await _reload(); _renderConfirmation(); }
        else atT('error', json.message);
    } catch { atT('error','Network error'); }
};

window.atSelectConf = function(confId) {
    _activeConfId = confId;
    const confirmations = _atData?.at_confirmations ?? [];
    const bookings      = _atData?.at_bookings      ?? [];
    const c = confirmations.find(x => x.sys_id === confId);
    if (!c) return;
    document.querySelectorAll('#at-conf-list .at-q-card').forEach(x => x.classList.remove('active'));
    event?.currentTarget?.classList.add('active');
    const b = bookings.find(x => x.sys_id === c.booking_sys_id);
    _renderConfDetail(c, b);
};

function _renderConfDetail(c, b) {
    const detail = document.getElementById('at-conf-detail');
    if (!detail) return;
    const sMap = { pending:'at-pill-tentative', confirmed:'at-pill-confirmed', failed:'at-pill-failed', cancelled:'at-pill-cancelled' };
    const type = b?.type ?? 'gds';

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
            <select onchange="atUpdateConfStatus('${_e(c.sys_id)}',this.value)"
                class="text-xs border border-gray-200 rounded-lg px-2 py-1.5 focus:outline-none focus:border-indigo-400">
                ${['pending','confirmed','failed','cancelled'].map(s=>`<option ${(c.status??'pending')===s?'selected':''}>${s}</option>`).join('')}
            </select>
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

    <div class="mb-4">
        <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Note</label>
        <textarea id="at-conf-note-${_e(c.sys_id)}" rows="2"
            class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm resize-none focus:outline-none focus:border-indigo-400">${_e(c.note??'')}</textarea>
    </div>

    <div class="mb-4">
        <label class="text-xs font-bold text-gray-500 uppercase block mb-2">Confirmation Files</label>
        <div class="border-2 border-dashed border-gray-200 rounded-xl p-4 text-center cursor-pointer hover:border-indigo-400 transition"
            onclick="document.getElementById('at-conf-file-${_e(c.sys_id)}').click()">
            <input type="file" id="at-conf-file-${_e(c.sys_id)}" class="hidden" multiple accept=".pdf,.jpg,.jpeg,.png"
                onchange="atUploadConfFile(this,'${_e(c.sys_id)}')">
            <i class="fas fa-cloud-upload-alt text-xl text-gray-300 mb-1 block"></i>
            <p class="text-xs text-gray-400">Upload ticket files — images shown inline, PDF stored</p>
        </div>
        <div id="at-conf-uprog-${_e(c.sys_id)}" class="hidden mt-1 text-xs text-indigo-600">
            <i class="fas fa-spinner fa-spin mr-1"></i>Uploading…
        </div>
        ${(c.files_json??[]).length ? `<div class="mt-3 space-y-2">
            ${(c.files_json).map((f,fi) => {
                const isImg = /\.(jpg|jpeg|png|webp)$/i.test(f.name??'');
                return `<div class="border border-gray-100 rounded-xl overflow-hidden">
                    ${isImg ? `<img src="${_e(f.path)}" class="w-full max-h-40 object-contain bg-gray-50 cursor-pointer" onclick="atOpenImage('${_e(f.path)}')">` : ''}
                    <div class="flex items-center gap-2 px-3 py-2">
                        <i class="fas fa-${isImg?'image':'file-alt'} text-gray-400 text-xs flex-shrink-0"></i>
                        <span class="text-xs text-gray-600 flex-1 truncate">${_e(f.name)}</span>
                        ${f.extracted_text ? `<button onclick="atShowExtracted('${fi}','${_e(c.sys_id)}')" class="text-xs text-indigo-500 hover:text-indigo-700 font-semibold px-1.5" title="Show extracted data"><i class="fas fa-eye"></i></button>` : ''}
                        <a href="${_e(f.path)}" download class="text-xs text-indigo-500 hover:text-indigo-700"><i class="fas fa-download"></i></a>
                    </div>
                    ${f.extracted_text ? `<div id="at-extracted-${fi}-${_e(c.sys_id)}" class="hidden px-3 pb-2">
                        <pre class="bg-gray-50 rounded-lg p-2 text-[10px] text-gray-600 max-h-32 overflow-auto font-mono whitespace-pre-wrap">${_e(f.extracted_text)}</pre>
                    </div>` : ''}
                </div>`;
            }).join('')}
        </div>` : '<p class="text-[11px] text-gray-300 mt-2 text-center">No files uploaded yet</p>'}
    </div>

    <div class="flex gap-2">
        <button onclick="atSaveConfDetails('${_e(c.sys_id)}')"
            class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition">
            <i class="fas fa-save mr-1.5"></i>Save Details
        </button>
        <a href="../task-air.php?task_id=${encodeURIComponent(_cfg.taskSysId)}" target="_blank"
            class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold transition flex items-center gap-1.5">
            <i class="fas fa-file-download text-xs"></i>Download
        </a>
        ${c.status === 'failed' || c.status === 'cancelled' ? `
        <button onclick="atRemoveConf('${_e(c.sys_id)}')"
            class="px-4 py-2.5 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-lg text-sm font-semibold transition">
            <i class="fas fa-trash-alt"></i>
        </button>` : ''}
    </div>`;
}

window.atShowExtracted = function(fi, confId) {
    const el = document.getElementById(`at-extracted-${fi}-${confId}`);
    if (el) el.classList.toggle('hidden');
};

window.atOpenImage = function(url) {
    const ov = document.createElement('div');
    ov.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:99999;display:flex;align-items:center;justify-content:center;cursor:zoom-out;';
    ov.innerHTML = `<img src="${url}" style="max-width:90vw;max-height:90vh;border-radius:8px;">`;
    ov.onclick = () => ov.remove();
    document.body.appendChild(ov);
};

window.atUpdateConfStatus = async function(confId, status) {
    try {
        const json = await _api({ action:'update_confirmation_status', conf_sys_id: confId, status });
        if (json.status==='success') { atT('success','Status updated'); await _reload(); _renderConfirmation(); }
        else atT('error', json.message);
    } catch { atT('error','Network error'); }
};

window.atSaveConfDetails = async function(confId) {
    const tickets = (document.getElementById(`at-conf-tickets-${confId}`)?.value??'').split(',').map(t=>t.trim()).filter(Boolean);
    const note    = document.getElementById(`at-conf-note-${confId}`)?.value ?? '';
    const conf    = (_atData?.at_confirmations??[]).find(c=>c.sys_id===confId);
    try {
        const json = await _api({ action:'update_confirmation', conf_sys_id:confId, ticket_nos:tickets, note, files_json:conf?.files_json??[] });
        if (json.status==='success') { atT('success','Saved!'); await _reload(); _renderConfirmation(); }
        else atT('error', json.message);
    } catch { atT('error','Network error'); }
};

window.atUploadConfFile = async function(input, confId) {
    if (!input.files.length) return;
    const prog = document.getElementById(`at-conf-uprog-${confId}`);
    prog?.classList.remove('hidden');
    const conf = (_atData?.at_confirmations??[]).find(c=>c.sys_id===confId);
    for (const file of input.files) {
        const fd = new FormData();
        fd.append('action','upload'); fd.append('task_sys_id',_cfg.taskSysId); fd.append('work_sys_id',_cfg.workSysId); fd.append('file', file);
        try {
            const res  = await fetch(_cfg.api.notes, { method:'POST', body:fd });
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
                await _api({ action:'update_confirmation', conf_sys_id:confId, ticket_nos:conf?.ticket_nos??[], note:conf?.note??'', files_json:files });
                await _reload();
            } else { atT('error', json.message); }
        } catch { atT('error','Upload failed'); }
    }
    prog?.classList.add('hidden');
    input.value = '';
    _renderConfirmation();
    if (_activeConfId) {
        const c = (_atData?.at_confirmations??[]).find(x=>x.sys_id===_activeConfId);
        const b = (_atData?.at_bookings??[]).find(x=>x.sys_id===c?.booking_sys_id);
        if (c) _renderConfDetail(c, b);
    }
};

window.atRemoveConf = async function(confId) {
    if (!confirm('Remove this confirmation entry?')) return;
    try {
        const json = await _api({ action:'remove_confirmation', conf_sys_id:confId });
        if (json.status==='success') { atT('success','Removed'); await _reload(); _activeConfId=null; _renderConfirmation(); }
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
        const res  = await fetch(`${_cfg.api.taskFinEntries}?task_id=${encodeURIComponent(_cfg.taskSysId)}`);
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
        const res  = await fetch(_cfg.api.saveFinancial, {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ task_sys_id:_cfg.taskSysId, work_sys_id:_cfg.workSysId, type, amount, description:desc, note, vendor_name:vendor, entry_date:date, qty, rate }),
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
        const res  = await fetch(_cfg.api.saveFinancial, {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ action:'delete', sys_id:sysId }),
        });
        const json = await res.json();
        if (json.status==='success') { atT('success','Deleted'); atLoadFE(); }
        else atT('error', json.message);
    } catch { atT('error','Network error'); }
};

// ═══════════════════════════════════════════════════════════════
// UTILITY FUNCTIONS
// ═══════════════════════════════════════════════════════════════
function _e(str)  { return String(str??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function _fmt(n)  { return Number(n??0).toLocaleString('en-BD'); }
function _fmtN(n) { return Number(n??0).toLocaleString('en-BD'); }

window.atT = function(type, msg) {
    if (typeof showToast === 'function') { showToast(type, msg); return; }
    console.log(`[${type}] ${msg}`);
};

})(); // end IIFE