/**
 * FILE PATH: /pages/task-tabs/ww-package/mindboard.js
 * Tab: Mind Board — Notes + Voice + AI itinerary generation
 */

// ── Render ────────────────────────────────────────────────────
window._renderPkMindboard = function() {
    const panel = document.getElementById('pk-panel-mindboard');
    if (!panel) return;
    panel.style.cssText = 'display:flex;flex-direction:column;padding:0;';

    panel.innerHTML = `
    <!-- Action bar (shown when notes selected) -->
    <div id="pk-gen-bar" class="hidden" style="flex-shrink:0;background:#F0FDF4;border-bottom:1px solid #BBF7D0;padding:8px 14px;display:flex;align-items:center;gap:8px;">
        <span id="pk-gen-count" style="font-size:11px;font-weight:600;color:#166534;">0 selected</span>
        <div style="flex:1;"></div>
        <button data-action="gen-summary" style="padding:5px 10px;font-size:11px;font-weight:600;background:#fff;color:#166534;border:1px solid #BBF7D0;border-radius:8px;cursor:pointer;">
            <i class="fas fa-align-left mr-1"></i>Generate Summary
        </button>
        <button data-action="gen-itinerary" style="padding:5px 10px;font-size:11px;font-weight:600;background:#16a34a;color:#fff;border:none;border-radius:8px;cursor:pointer;">
            <i class="fas fa-suitcase-rolling mr-1"></i>Generate Package Quotation
        </button>
        <button data-action="gen-clear" style="padding:5px 8px;font-size:11px;background:transparent;color:#6B7280;border:none;cursor:pointer;">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- Notes list -->
    <div id="pk-notes-list" style="flex:1;overflow-y:auto;padding:12px 14px 12px 34px;display:flex;flex-direction:column;gap:8px;min-height:300px;max-height:calc(100vh - 350px);">
        <div class="text-center py-6 text-gray-300 text-sm"><i class="fas fa-spinner fa-spin"></i></div>
    </div>

    <!-- Input bar -->
    <div style="flex-shrink:0;border-top:1px solid #f1f5f9;background:#fff;padding:10px 12px;">
        <!-- STT status bar -->
        <div id="pk-stt-bar" style="display:none;margin-bottom:6px;padding:5px 10px;background:#F0FDF4;border-radius:8px;font-size:11px;color:#166534;display:none;align-items:center;gap:6px;">
            <span class="animate-pulse">●</span> <span>Listening… (tap mic to stop)</span>
        </div>
        <div id="pk-multi-preview" style="display:none;margin-bottom:6px;flex-wrap:wrap;gap:4px;"></div>
        <div class="flex items-end gap-1.5">
            <!-- STT voice button -->
            <button id="pk-stt-btn" title="Voice to Text" style="width:32px;height:32px;background:#f0fdf4;border:none;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-microphone-lines" style="color:#16a34a;font-size:.75rem;"></i>
            </button>
            <!-- Audio recorder -->
            <button id="pk-rec-btn" title="Record Audio Note" style="width:32px;height:32px;background:#fdf2f8;border:none;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-microphone" style="color:#db2777;font-size:.75rem;"></i>
            </button>
            <!-- Image upload -->
            <label style="width:32px;height:32px;background:#eff6ff;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0;" title="Images">
                <i class="fas fa-image" style="color:#3b82f6;font-size:.75rem;"></i>
                <input type="file" class="hidden" accept="image/*" multiple data-action="mb-files">
            </label>
            <!-- File upload -->
            <label style="width:32px;height:32px;background:#f3f4f6;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0;" title="Files">
                <i class="fas fa-paperclip" style="color:#6b7280;font-size:.75rem;"></i>
                <input type="file" class="hidden" multiple data-action="mb-files">
            </label>
            <textarea id="pk-note-text" rows="1"
                placeholder="Write a note… (Enter to send)"
                style="flex:1;resize:none;border:1.5px solid #e5e7eb;border-radius:20px;padding:8px 14px;font-size:.83rem;outline:none;max-height:80px;overflow-y:auto;"
                data-action="mb-keydown"></textarea>
            <button data-action="mb-send" style="width:36px;height:36px;background:#16a34a;border:none;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-paper-plane" style="color:#fff;font-size:.82rem;"></i>
            </button>
        </div>
    </div>

    <!-- AI generate modal overlay (shared) -->
    <div id="pk-gen-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9998;align-items:center;justify-content:center;padding:16px;"></div>`;

    // ── Event listeners (delegation + explicit) ───────────────
    panel.addEventListener('click', _pkMbClick);
    panel.addEventListener('change', _pkMbChange);

    const ta = document.getElementById('pk-note-text');
    if (ta) {
        ta.addEventListener('keydown', e => {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); _pkMbSend(); }
        });
        ta.addEventListener('input', () => {
            ta.style.height = 'auto';
            ta.style.height = Math.min(ta.scrollHeight, 80) + 'px';
        });
    }

    _pkMbLoadNotes();
};

// ── Delegation handler ────────────────────────────────────────
function _pkMbClick(e) {
    const btn = e.target.closest('[data-action]');
    if (!btn) return;
    const act = btn.dataset.action;
    if (act === 'gen-summary')   _pkMbGenerate('summary');
    if (act === 'gen-itinerary') _pkMbGenerate('itinerary');
    if (act === 'gen-clear')     _pkMbClearSelection();
    if (act === 'mb-send')       _pkMbSend();
    if (act === 'mb-delete')     _pkMbDeleteNote(btn.dataset.id);
    if (act === 'mb-toggle-cb')  _pkMbToggleNote(btn.dataset.id, btn.checked !== false);
    if (act === 'pk-stt-btn' || btn.id === 'pk-stt-btn') _pkMbToggleSTT();
    if (act === 'pk-rec-btn' || btn.id === 'pk-rec-btn') _pkMbRecToggle();
    if (act === 'mb-view-img')   pkViewImg(btn.dataset.url);
}

function _pkMbChange(e) {
    const el = e.target;
    if (el.dataset.action === 'mb-files') _pkMbFilesSelected(el);
    if (el.dataset.action === 'mb-cb')    _pkMbToggleNote(el.dataset.id, el.checked);
}

panel.addEventListener = panel.addEventListener; // no-op to satisfy linter, fn is already set above

// but we need to access "panel" later from event — let's make delegation work on document level for STT btn
document.addEventListener('click', function(e) {
    const btn = e.target.closest('#pk-stt-btn');
    if (btn) _pkMbToggleSTT();
    const rBtn = e.target.closest('#pk-rec-btn');
    if (rBtn) _pkMbRecToggle();
}, true); // capture phase

// ── Note selection ────────────────────────────────────────────
function _pkMbToggleNote(sysId, checked) {
    if (checked) window._pk.selectedNoteIds.add(sysId);
    else         window._pk.selectedNoteIds.delete(sysId);
    _pkMbUpdateBar();
}

function _pkMbUpdateBar() {
    const bar   = document.getElementById('pk-gen-bar');
    const count = window._pk.selectedNoteIds.size;
    if (!bar) return;
    if (count > 0) {
        bar.classList.remove('hidden');
        bar.style.display = 'flex';
        const el = document.getElementById('pk-gen-count');
        if (el) el.textContent = `${count} selected`;
    } else {
        bar.classList.add('hidden');
        bar.style.display = 'none';
    }
}

function _pkMbClearSelection() {
    window._pk.selectedNoteIds.clear();
    document.querySelectorAll('.pk-gen-cb').forEach(cb => cb.checked = false);
    _pkMbUpdateBar();
}

// ── Generate (summary or itinerary) ──────────────────────────
async function _pkMbGenerate(mode) {
    const noteIds = [...window._pk.selectedNoteIds];
    if (!noteIds.length) { pkT('error', 'কমপক্ষে একটা note select করুন'); return; }

    _pkShowGenModal(`<div style="text-align:center;padding:32px;">
        <i class="fas fa-spinner fa-spin" style="font-size:24px;color:#16a34a;"></i>
        <p style="margin-top:12px;font-size:13px;color:#4B5563;">${mode === 'summary' ? 'Generating Summary…' : 'Generating Package Quotation…'}</p>
    </div>`);

    try {
        const apiUrl = window._pk.cfg.api.packageServices.replace('endpoints.php', 'extract-from-notes.php');
        const res  = await fetch(apiUrl, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ work_sys_id: window._pk.cfg.workSysId, note_sys_ids: noteIds, action: mode }),
        });
        const json = await res.json();
        if (!json.success) { _pkCloseGenModal(); pkT('error', json.message || 'Generate ব্যর্থ'); return; }

        if (mode === 'summary') {
            _pkShowSummaryModal(json.summary_text);
        } else {
            _pkShowPackageModal(json.package, noteIds);
        }
    } catch(e) {
        _pkCloseGenModal();
        pkT('error', 'Network error');
    }
}

// ── Modal helpers ─────────────────────────────────────────────
function _pkShowGenModal(html) {
    const ov = document.getElementById('pk-gen-modal');
    if (!ov) return;
    ov.style.display = 'flex';
    ov.innerHTML = html;
}

function _pkCloseGenModal() {
    const ov = document.getElementById('pk-gen-modal');
    if (ov) ov.style.display = 'none';
}

// ── Summary modal ─────────────────────────────────────────────
function _pkShowSummaryModal(text) {
    _pkShowGenModal(`
    <div style="background:#fff;border-radius:16px;width:100%;max-width:620px;max-height:90vh;overflow-y:auto;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #f1f5f9;">
            <h3 style="font-size:15px;font-weight:700;color:#1F2937;margin:0;"><i class="fas fa-align-left mr-2" style="color:#16a34a;"></i>Summary</h3>
            <button onclick="_pkCloseGenModal()" style="background:none;border:none;color:#9CA3AF;cursor:pointer;font-size:16px;"><i class="fas fa-times"></i></button>
        </div>
        <div style="padding:20px;">
            <div contenteditable="true" id="pk-summary-text"
                style="background:#F9FAFB;border:1px solid #E5E7EB;border-radius:10px;padding:12px 14px;font-size:13px;color:#374151;line-height:1.7;min-height:80px;white-space:pre-wrap;">${_pke(text)}</div>
            <button onclick="_pkSaveSummary()" style="margin-top:10px;padding:7px 14px;font-size:12px;font-weight:600;background:#16a34a;color:#fff;border:none;border-radius:8px;cursor:pointer;">
                <i class="fas fa-save mr-1"></i>Save in Mind Board
            </button>
        </div>
    </div>`);
}

window._pkSaveSummary = async function() {
    const txt = document.getElementById('pk-summary-text')?.innerText.trim();
    if (!txt) { pkT('error', 'Summary খালি'); return; }
    try {
        const res  = await fetch(window._pk.cfg.api.notes, {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action:'store', work_sys_id:window._pk.cfg.workSysId, service_slug:'tour_package', board:'mindboard', content:'📝 Summary:\n'+txt }),
        });
        const json = await res.json();
        if (json.status === 'success') { pkT('success', 'Saved!'); _pkMbClearSelection(); _pkCloseGenModal(); await _pkMbLoadNotes(); }
        else pkT('error', json.message || 'Save ব্যর্থ');
    } catch(e) { pkT('error', 'Network error'); }
};

// ── Package quotation modal ───────────────────────────────────
let _pkCurrentPackage = null;
let _pkCurrentNoteIds = [];

function _pkShowPackageModal(pkg, noteIds) {
    _pkCurrentPackage = pkg;
    _pkCurrentNoteIds = noteIds;
    const p   = pkg;
    const pr  = p.pricing ?? {};
    const svc = p.services ?? {};

    const destStr = (p.destinations ?? []).map(d => [d.country, d.city].filter(Boolean).join(', ')).join(' · ');

    _pkShowGenModal(`
    <div style="background:#fff;border-radius:16px;width:100%;max-width:720px;max-height:90vh;overflow-y:auto;display:flex;flex-direction:column;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #F1F5F9;flex-shrink:0;">
            <h3 style="font-size:15px;font-weight:700;color:#1F2937;margin:0;"><i class="fas fa-suitcase-rolling mr-2" style="color:#16a34a;"></i>Generated Package Quotation</h3>
            <button onclick="_pkCloseGenModal()" style="background:none;border:none;color:#9CA3AF;cursor:pointer;font-size:16px;"><i class="fas fa-times"></i></button>
        </div>
        <div style="padding:20px;overflow-y:auto;flex:1;">
            <!-- Title + Destination -->
            <div style="margin-bottom:14px;">
                <div style="font-size:15px;font-weight:700;color:#1F2937;">${_pke(p.title)}</div>
                ${destStr ? `<div style="font-size:12px;color:#6B7280;margin-top:2px;"><i class="fas fa-map-marker-alt mr-1 text-green-500"></i>${_pke(destStr)}</div>` : ''}
                <div style="font-size:11px;color:#9CA3AF;margin-top:2px;">${p.duration_days || 0} Days · ${p.pax || 1} Pax</div>
            </div>

            <!-- Itinerary (editable) -->
            <div style="margin-bottom:14px;">
                <label style="font-size:11px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.04em;">Itinerary <span style="font-weight:400;color:#9CA3AF;">(editable)</span></label>
                <textarea id="pk-modal-itinerary" rows="8"
                    style="width:100%;margin-top:4px;padding:10px 12px;font-size:12px;color:#374151;border:1px solid #E5E7EB;border-radius:10px;resize:vertical;line-height:1.7;background:#F9FAFB;">${_pke(p.itinerary)}</textarea>
                <button onclick="_pkPolishItinerary()" style="margin-top:4px;padding:4px 10px;font-size:11px;font-weight:600;background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;border-radius:6px;cursor:pointer;">
                    <i class="fas fa-magic mr-1"></i>AI Polish
                </button>
            </div>

            <!-- Service costs -->
            <div style="background:#F9FAFB;border:1px solid #E5E7EB;border-radius:12px;padding:14px;margin-bottom:14px;font-size:12px;">
                <div style="font-size:11px;font-weight:700;color:#374151;text-transform:uppercase;margin-bottom:10px;">Service Costs</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                    ${_pkSvcRow('Air Ticket',  svc.air_ticket?.description  || '—', pr.at_cost,        pr.currency)}
                    ${_pkSvcRow('Hotel',        svc.hotel?.description       || '—', pr.hotel_cost,     pr.currency)}
                    ${_pkSvcRow('Transport',    svc.transport?.description   || '—', pr.transport_cost, pr.currency)}
                    ${_pkSvcRow('Visa',         svc.visa?.description        || '—', pr.visa_cost,      pr.currency)}
                    ${(svc.others||[]).map(o => _pkSvcRow(o.label||'Other','', o.cost, pr.currency)).join('')}
                </div>
            </div>

            <!-- Pricing summary -->
            <div style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:12px;padding:14px;margin-bottom:14px;font-size:12px;">
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-bottom:8px;">
                    <div><div style="color:#9CA3AF;font-size:10px;text-transform:uppercase;">Base Cost</div><div style="font-weight:700;color:#374151;">${_pke(pr.currency)} ${_pkFmt(pr.base_cost)}</div></div>
                    <div><div style="color:#9CA3AF;font-size:10px;text-transform:uppercase;">Markup ${pr.markup_pct ?? 10}%</div><div style="font-weight:700;color:#374151;">+ ${_pkFmt(pr.markup_amount)}</div></div>
                    <div><div style="color:#9CA3AF;font-size:10px;text-transform:uppercase;">Gross Total</div><div style="font-weight:700;color:#16a34a;font-size:14px;">${_pke(pr.currency)} ${_pkFmt(pr.gross_total)}</div></div>
                </div>
                <div style="border-top:1px solid #BBF7D0;padding-top:8px;font-weight:700;color:#166534;font-size:13px;">
                    Per Pax: ${_pke(pr.currency)} ${_pkFmt(pr.per_pax)}
                    <span style="font-size:11px;font-weight:400;color:#6B7280;"> (${p.pax || 1} pax)</span>
                </div>
            </div>

            <button onclick="_pkSaveAsQuotation()" style="width:100%;padding:11px;font-size:13px;font-weight:600;background:#16a34a;color:#fff;border:none;border-radius:10px;cursor:pointer;">
                <i class="fas fa-save mr-1.5"></i>Save as Quotation
            </button>
        </div>
    </div>`);
}

function _pkSvcRow(label, desc, cost, currency) {
    if (!cost && cost !== 0) return '';
    return `<div style="padding:6px 8px;background:#fff;border:1px solid #E5E7EB;border-radius:8px;">
        <div style="font-size:10px;font-weight:600;color:#374151;">${_pke(label)}</div>
        ${desc ? `<div style="font-size:10px;color:#9CA3AF;">${_pke(desc)}</div>` : ''}
        <div style="font-size:12px;font-weight:700;color:#16a34a;margin-top:2px;">${_pke(currency||'BDT')} ${_pkFmt(cost)}</div>
    </div>`;
}

window._pkPolishItinerary = async function() {
    const ta = document.getElementById('pk-modal-itinerary');
    if (!ta || !ta.value.trim()) return;
    const btn = document.querySelector('[onclick="_pkPolishItinerary()"]');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Polishing…'; }
    const polished = await window._pkPolishText(ta.value, 'package');
    ta.value = polished;
    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-magic mr-1"></i>AI Polish'; }
};

window._pkSaveAsQuotation = async function() {
    const p   = _pkCurrentPackage;
    if (!p) { pkT('error', 'Package data নেই'); return; }

    const itinerary = document.getElementById('pk-modal-itinerary')?.value.trim() || p.itinerary;

    try {
        const json = await window._pkApi({
            action:          'save_quotation',
            title:           p.title,
            itinerary:       itinerary,
            destinations:    p.destinations    ?? [],
            duration_days:   p.duration_days   ?? 0,
            pax:             p.pax             ?? 1,
            services:        p.services        ?? {},
            pricing:         p.pricing         ?? {},
            source_note_ids: _pkCurrentNoteIds,
        });
        if (json.status === 'success') {
            pkT('success', 'Quotation saved! Switch to Quotation tab.');
            _pkMbClearSelection();
            _pkCloseGenModal();
            await window._pkReload();
        } else {
            pkT('error', json.message || 'Save ব্যর্থ');
        }
    } catch(e) { pkT('error', 'Network error'); }
};

// ── Notes load + render ───────────────────────────────────────
window._pkMbLoadNotes = async function() {
    try {
        const url  = `${window._pk.cfg.api.notes}?action=list&work_sys_id=${encodeURIComponent(window._pk.cfg.workSysId)}&service_slug=tour_package&board=mindboard`;
        const res  = await fetch(url);
        const json = await res.json();
        const notes = json.status === 'success' ? (json.data ?? []) : [];
        window._pk.currentNotes = notes;
        _pkMbRenderNotes(notes);
    } catch(e) { _pkMbRenderNotes([]); }
};

function _pkMbRenderNotes(notes) {
    const list = document.getElementById('pk-notes-list');
    if (!list) return;
    if (!notes.length) {
        list.innerHTML = '<div class="text-center py-8 text-gray-300 text-xs">No notes yet. Write something below or use voice.</div>';
        return;
    }
    list.innerHTML = notes.map(_pkNoteBubble).join('');
    setTimeout(() => { list.scrollTop = list.scrollHeight; }, 50);
}

function _pkNoteBubble(n) {
    const dateStr = n.meta_data?.created_by_date?.date ?? '';
    const sysId   = _pke(n.sys_id);
    const content = n.content ?? '';
    const fileUrl = n.serve_url ?? n.file_url ?? '';
    const isSel   = window._pk.selectedNoteIds.has(n.sys_id);

    const delBtn = `<button data-action="mb-delete" data-id="${sysId}" style="background:none;border:none;color:#f87171;cursor:pointer;font-size:.7rem;padding:2px 6px;"><i class="fas fa-trash"></i></button>`;

    if (n.note_type === 'text') {
        const len  = content.length;
        const minW = len < 20 ? 'min(25%,320px)' : len < 50 ? 'min(45%,480px)' : 'min(65%,640px)';
        return `<div style="align-self:flex-start;min-width:${minW};max-width:85%;position:relative;">
            <input type="checkbox" class="pk-gen-cb" data-action="mb-cb" data-id="${sysId}"
                ${isSel?'checked':''} style="position:absolute;top:6px;left:-22px;width:16px;height:16px;cursor:pointer;accent-color:#16a34a;">
            <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:10px 12px;">
                <div style="font-size:.83rem;color:#374151;white-space:pre-line;word-wrap:break-word;">${_pke(content)}</div>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-top:6px;">
                    <span style="font-size:10px;color:#d1d5db;">${_pke(dateStr)}</span>
                    ${delBtn}
                </div>
            </div>
        </div>`;
    }

    if (n.note_type === 'image') {
        return `<div style="align-self:flex-start;max-width:780px;position:relative;">
            <input type="checkbox" class="pk-gen-cb" data-action="mb-cb" data-id="${sysId}"
                ${isSel?'checked':''} style="position:absolute;top:6px;left:-22px;width:16px;height:16px;cursor:pointer;accent-color:#16a34a;z-index:2;">
            <img src="${fileUrl}" loading="lazy" data-action="mb-view-img" data-url="${fileUrl}"
                style="width:100%;max-height:280px;object-fit:contain;border-radius:8px;cursor:zoom-in;display:block;background:#f9fafb;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-top:4px;">
                <span style="font-size:10px;color:#d1d5db;">${_pke(dateStr)}</span>
                ${delBtn}
            </div>
        </div>`;
    }

    if (n.note_type === 'audio') {
        return `<div style="align-self:flex-start;min-width:300px;max-width:500px;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:10px 12px;">
            <audio controls class="w-full mt-1" style="height:32px;" src="${fileUrl}"></audio>
            <div style="display:flex;justify-content:space-between;margin-top:4px;">
                <span style="font-size:10px;color:#d1d5db;">${_pke(dateStr)}</span>
                ${delBtn}
            </div>
        </div>`;
    }

    return `<div style="align-self:flex-start;background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:8px 12px;min-width:200px;max-width:400px;display:flex;align-items:center;gap:8px;">
        <i class="fas fa-paperclip text-green-500 flex-shrink-0"></i>
        <a href="${fileUrl}" target="_blank" style="font-size:.8rem;color:#059669;flex:1;overflow:hidden;text-overflow:ellipsis;">${_pke(n.file_name||'File')}</a>
        ${delBtn}
    </div>`;
}

// ── Note CRUD ─────────────────────────────────────────────────
async function _pkMbSend() {
    if (window._pk.pendingFiles.length > 0) {
        const files = [...window._pk.pendingFiles];
        window._pk.pendingFiles = [];
        const mp = document.getElementById('pk-multi-preview');
        if (mp) { mp.style.display='none'; mp.innerHTML=''; }
        for (const file of files) {
            const fd = new FormData();
            fd.append('action','upload'); fd.append('work_sys_id', window._pk.cfg.workSysId);
            fd.append('service_slug','tour_package'); fd.append('board','mindboard'); fd.append('file', file);
            try { await fetch(window._pk.cfg.api.notes, { method:'POST', body:fd }); } catch(e) {}
        }
        await _pkMbLoadNotes(); return;
    }
    const ta = document.getElementById('pk-note-text');
    const content = ta?.value.trim();
    if (!content) return;
    try {
        const res  = await fetch(window._pk.cfg.api.notes, {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ action:'store', work_sys_id:window._pk.cfg.workSysId, service_slug:'tour_package', board:'mindboard', content }),
        });
        const json = await res.json();
        if (json.status === 'success') { if (ta) { ta.value=''; ta.style.height='auto'; } await _pkMbLoadNotes(); }
        else pkT('error', json.message || 'Failed');
    } catch(e) { pkT('error', 'Network error'); }
}

function _pkMbFilesSelected(input) {
    if (!input.files.length) return;
    window._pk.pendingFiles = Array.from(input.files);
    const mp = document.getElementById('pk-multi-preview');
    if (mp) {
        mp.style.display = 'flex';
        mp.innerHTML = window._pk.pendingFiles.map(f =>
            `<span style="display:inline-flex;align-items:center;gap:4px;background:#F0FDF4;border-radius:20px;padding:2px 8px;font-size:.72rem;color:#16a34a;"><i class="fas fa-file" style="font-size:.65rem;"></i>${f.name.length>20?f.name.slice(0,18)+'…':f.name}</span>`
        ).join('');
    }
    input.value = '';
}

async function _pkMbDeleteNote(sysId) {
    if (!confirm('Delete this note?')) return;
    try {
        const res  = await fetch(window._pk.cfg.api.notes, {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ action:'delete', note_sys_id:sysId, work_sys_id:window._pk.cfg.workSysId }),
        });
        const json = await res.json();
        if (json.status === 'success') { pkT('success','Deleted'); await _pkMbLoadNotes(); }
        else pkT('error', json.message || 'Failed');
    } catch(e) { pkT('error', 'Network error'); }
}

// ── Voice audio recording ─────────────────────────────────────
async function _pkMbRecToggle() {
    if (window._pk.recording) { window._pk.recorder?.stop(); return; }
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        window._pk.recChunks = [];
        window._pk.recorder  = new MediaRecorder(stream);
        window._pk.recorder.ondataavailable = e => { if (e.data.size > 0) window._pk.recChunks.push(e.data); };
        window._pk.recorder.onstop = async () => {
            stream.getTracks().forEach(t => t.stop());
            const blob = new Blob(window._pk.recChunks, { type:'audio/webm' });
            const btn  = document.getElementById('pk-rec-btn');
            if (btn) { btn.style.background='#fdf2f8'; btn.innerHTML='<i class="fas fa-microphone" style="color:#db2777;font-size:.75rem;"></i>'; }
            window._pk.recording = false;
            const fd = new FormData();
            fd.append('action','upload'); fd.append('work_sys_id', window._pk.cfg.workSysId);
            fd.append('service_slug','tour_package'); fd.append('board','mindboard');
            fd.append('file', blob, 'voice_'+Date.now()+'.webm');
            try {
                const res  = await fetch(window._pk.cfg.api.notes, { method:'POST', body:fd });
                const json = await res.json();
                if (json.status === 'success') await _pkMbLoadNotes();
            } catch(e) {}
        };
        window._pk.recorder.start();
        window._pk.recording = true;
        const btn = document.getElementById('pk-rec-btn');
        if (btn) { btn.style.background='#fee2e2'; btn.innerHTML='<i class="fas fa-stop" style="color:#dc2626;font-size:.75rem;"></i>'; }
    } catch(e) { pkT('error','Microphone access denied'); }
}

// ── STT (Speech-to-Text for note textarea) ────────────────────
function _pkMbToggleSTT() {
    const ta  = document.getElementById('pk-note-text');
    const btn = document.getElementById('pk-stt-btn');
    const bar = document.getElementById('pk-stt-bar');

    if (window._pk.sttActive) {
        window._pkStopSTT();
        if (btn) { btn.style.background='#f0fdf4'; btn.innerHTML='<i class="fas fa-microphone-lines" style="color:#16a34a;font-size:.75rem;"></i>'; }
        if (bar) bar.style.display = 'none';
        return;
    }

    window._pkStartSTT('pk-note-text');
    if (btn) { btn.style.background='#dcfce7'; btn.innerHTML='<i class="fas fa-microphone-lines" style="color:#15803d;font-size:.75rem;"></i>'; }
    if (bar) { bar.style.display = 'flex'; }
}