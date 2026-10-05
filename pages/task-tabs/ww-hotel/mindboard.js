/**
 * FILE PATH: /pages/task-tabs/ww-hotel/mindboard.js
 * Tab: Mind Board
 */

window._renderHtMindboard = function() {
    const panel = document.getElementById('ht-panel-mindboard');
    panel.style.cssText = 'display:flex;flex-direction:column;padding:0;';

    panel.innerHTML = `
    <!-- Generate action bar — note select করলে দেখা যায় -->
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

    <!-- Notes list -->
    <div id="ht-notes-list" style="flex:1;overflow-y:auto;padding:12px 14px 12px 34px;display:flex;flex-direction:column;gap:8px;min-height:300px;max-height:calc(100vh - 350px);">
        <div class="text-center py-6 text-gray-300 text-sm"><i class="fas fa-spinner fa-spin"></i></div>
    </div>

    <!-- Input bar -->
    <div style="flex-shrink:0;border-top:1px solid #f1f5f9;background:#fff;padding:10px 12px;">
        <div id="ht-multi-preview" style="display:none;margin-bottom:6px;flex-wrap:wrap;gap:4px;"></div>
        <div class="flex items-end gap-1.5">
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
                style="flex:1;resize:none;border:1.5px solid #e5e7eb;border-radius:20px;padding:8px 14px;font-size:.83rem;outline:none;max-height:80px;overflow-y:auto;"
                onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();htSendNote();}"
                oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,80)+'px'"></textarea>
            <button onclick="htSendNote()" style="width:36px;height:36px;background:#EA580C;border:none;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-paper-plane" style="color:#fff;font-size:.82rem;"></i>
            </button>
        </div>
    </div>`;

    htLoadNotes();
};

// ── Note selection ────────────────────────────────────────────
window._ht.selectedNoteIds = window._ht.selectedNoteIds || new Set();

window._htGenToggleNote = function(sysId, checked) {
    if (checked) window._ht.selectedNoteIds.add(sysId);
    else         window._ht.selectedNoteIds.delete(sysId);
    _htGenUpdateBar();
};

function _htGenUpdateBar() {
    const bar   = document.getElementById('ht-gen-actionbar');
    const count = window._ht.selectedNoteIds.size;
    if (!bar) return;
    if (count > 0) {
        bar.classList.remove('hidden');
        const el = document.getElementById('ht-gen-count');
        if (el) el.textContent = `${count} selected`;
    } else {
        bar.classList.add('hidden');
    }
}

window._htGenClearSelection = function() {
    window._ht.selectedNoteIds.clear();
    document.querySelectorAll('.ht-gen-cb').forEach(cb => cb.checked = false);
    _htGenUpdateBar();
};

// ── Generate ──────────────────────────────────────────────────
window._htGenGenerate = async function(mode) {
    const noteIds = [...window._ht.selectedNoteIds];
    if (!noteIds.length) { htT('error', 'কমপক্ষে একটা note select করুন'); return; }

    _htShowLoadingModal(mode);

    try {
        const apiUrl = window._ht.cfg.api.hotelServices.replace('api/hotel-services/endpoints.php', 'api/hotel-services/extract-from-notes.php');
        const res  = await fetch(apiUrl, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ work_sys_id: window._ht.cfg.workSysId, note_sys_ids: noteIds, action: mode }),
        });
        const json = await res.json();
        if (!json.success) { _htCloseModal(); htT('error', json.message || 'Generate ব্যর্থ'); return; }

        if (mode === 'summary') {
            _htRenderSummaryModal(json.summary_text);
        } else {
            _htRenderQuotationModal(json.quotation, noteIds);
        }
    } catch(e) {
        _htCloseModal();
        htT('error', 'Network error');
        console.error(e);
    }
};

function _htShowLoadingModal(mode) {
    let ov = document.getElementById('ht-gen-modal-ov');
    if (!ov) {
        ov = document.createElement('div');
        ov.id = 'ht-gen-modal-ov';
        ov.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9998;display:flex;align-items:center;justify-content:center;padding:16px;';
        document.body.appendChild(ov);
    }
    const label = mode === 'summary' ? 'Summary' : 'Hotel Quotation';
    ov.innerHTML = `<div style="background:#fff;border-radius:16px;padding:32px;text-align:center;min-width:280px;">
        <i class="fas fa-spinner fa-spin" style="font-size:24px;color:#EA580C;"></i>
        <p style="margin-top:12px;font-size:13px;color:#4B5563;">Generating ${label}…</p>
    </div>`;
    ov.style.display = 'flex';
}

function _htCloseModal() {
    document.getElementById('ht-gen-modal-ov')?.remove();
}

// ── Summary modal ─────────────────────────────────────────────
function _htRenderSummaryModal(text) {
    const ov = document.getElementById('ht-gen-modal-ov');
    if (!ov) return;
    ov.innerHTML = `
    <div style="background:#fff;border-radius:16px;width:100%;max-width:620px;max-height:90vh;overflow-y:auto;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #f1f5f9;">
            <h3 style="font-size:15px;font-weight:700;color:#1F2937;margin:0;"><i class="fas fa-align-left mr-2" style="color:#EA580C;"></i>Summary</h3>
            <button onclick="_htCloseModal()" style="background:none;border:none;color:#9CA3AF;cursor:pointer;font-size:16px;"><i class="fas fa-times"></i></button>
        </div>
        <div style="padding:20px;">
            <div contenteditable="true" id="ht-summary-text"
                style="background:#F9FAFB;border:1px solid #E5E7EB;border-radius:10px;padding:12px 14px;font-size:13px;color:#374151;line-height:1.7;min-height:80px;white-space:pre-wrap;">${_hte(text)}</div>
            <button onclick="_htSaveSummary()" style="margin-top:10px;padding:7px 14px;font-size:12px;font-weight:600;background:#EA580C;color:#fff;border:none;border-radius:8px;cursor:pointer;">
                <i class="fas fa-save mr-1"></i>Save in Mind Board
            </button>
        </div>
    </div>`;
}

window._htSaveSummary = async function() {
    const txt = document.getElementById('ht-summary-text')?.innerText.trim();
    if (!txt) { htT('error', 'Summary খালি'); return; }
    try {
        const res  = await fetch(window._ht.cfg.api.notes, {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action:'store', work_sys_id:window._ht.cfg.workSysId, service_slug:'hotel', board:'mindboard', content:'📝 Summary:\n'+txt }),
        });
        const json = await res.json();
        if (json.status === 'success') { htT('success', 'Saved!'); window._htGenClearSelection(); _htCloseModal(); await htLoadNotes(); }
        else htT('error', json.message || 'Save ব্যর্থ');
    } catch(e) { htT('error', 'Network error'); }
};

// ── Quotation result + Masterdata review modal ────────────────
let _htCurrentQuotation = null;
let _htCurrentNoteIds   = [];

function _htRenderQuotationModal(quotation, noteIds) {
    _htCurrentQuotation = quotation;
    _htCurrentNoteIds   = noteIds;
    const ov = document.getElementById('ht-gen-modal-ov');
    if (!ov) return;

    const q   = quotation;
    const ms  = q.masterdata_suggestion;
    const nights = q.nights || _htCalcNights(q.check_in, q.check_out);
    const total  = Math.round((q.sell_rate || q.net_rate || 0) * nights * (q.rooms || 1));

    // Masterdata review section
    const mdHtml = ms ? `
    <div style="background:#FFF7ED;border:1px solid #FED7AA;border-radius:12px;padding:14px;margin-bottom:16px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
            <span style="font-size:12px;font-weight:700;color:#C2410C;"><i class="fas fa-database mr-1"></i>Hotel Masterdata</span>
            ${ms.matched_hotel_sys_id
                ? `<span style="font-size:10px;background:#D1FAE5;color:#065F46;padding:2px 8px;border-radius:10px;font-weight:600;">✓ Matched: ${_hte(ms.matched_hotel_data?.name || '')}</span>`
                : `<span style="font-size:10px;background:#FEE2E2;color:#991B1B;padding:2px 8px;border-radius:10px;font-weight:600;">New hotel</span>`}
        </div>
        <div style="font-size:11px;color:#78350F;margin-bottom:8px;">
            AI extracted new data. Review and approve to update your hotel database:
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;font-size:11px;margin-bottom:10px;">
            <div><b>Hotel:</b> ${_hte(ms.hotel.name)} ${ms.hotel.star_rating ? `(${ms.hotel.star_rating}★)` : ''}</div>
            <div><b>City:</b> ${_hte(ms.hotel.city)}, ${_hte(ms.hotel.country)}</div>
            <div><b>Room:</b> ${_hte(ms.room_type.room_name)} — ${_hte(ms.room_type.bed_config)}</div>
            <div><b>Rate:</b> ${_hte(ms.room_rate.currency_code)} ${_htFmtN(ms.room_rate.net_cost)} net / ${_htFmtN(ms.room_rate.sell_price)} sell</div>
            <div><b>Meal:</b> ${HT_MEAL_PLANS[ms.room_rate.meal_plan] || ms.room_rate.meal_plan}</div>
            <div><b>Valid:</b> ${_hte(ms.room_rate.valid_from)} → ${_hte(ms.room_rate.valid_to)}</div>
        </div>
        <div style="display:flex;gap:8px;">
            <button onclick="_htApproveMasterdata()" style="flex:1;padding:7px;font-size:11px;font-weight:600;background:#EA580C;color:#fff;border:none;border-radius:8px;cursor:pointer;">
                <i class="fas fa-check mr-1"></i>Approve & Update Database
            </button>
            <button onclick="_htSkipMasterdata()" style="padding:7px 12px;font-size:11px;font-weight:600;background:#F3F4F6;color:#6B7280;border:none;border-radius:8px;cursor:pointer;">
                Skip
            </button>
        </div>
    </div>` : '';

    ov.innerHTML = `
    <div style="background:#fff;border-radius:16px;width:100%;max-width:680px;max-height:90vh;overflow-y:auto;display:flex;flex-direction:column;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #F1F5F9;flex-shrink:0;">
            <h3 style="font-size:15px;font-weight:700;color:#1F2937;margin:0;">
                <i class="fas fa-hotel mr-2" style="color:#EA580C;"></i>Generated Hotel Quotation
            </h3>
            <button onclick="_htCloseModal()" style="background:none;border:none;color:#9CA3AF;cursor:pointer;font-size:16px;"><i class="fas fa-times"></i></button>
        </div>
        <div style="padding:20px;overflow-y:auto;flex:1;">
            ${mdHtml}

            <!-- Quotation preview -->
            <div style="background:#F9FAFB;border:1px solid #E5E7EB;border-radius:12px;padding:14px;font-size:12px;">
                <div style="font-size:13px;font-weight:700;color:#1F2937;margin-bottom:10px;">${_hte(q.hotel_name || '—')} ${q.star_rating ? `<span style="color:#F59E0B;">${'★'.repeat(q.star_rating)}</span>` : ''}</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-bottom:10px;">
                    <div><span style="color:#9CA3AF;">City</span><br><b>${_hte(q.city)}, ${_hte(q.country)}</b></div>
                    <div><span style="color:#9CA3AF;">Check-in / Check-out</span><br><b>${_hte(q.check_in)} → ${_hte(q.check_out)}</b> (${nights} nights)</div>
                    <div><span style="color:#9CA3AF;">Room</span><br><b>${_hte(q.room_type)} — ${_hte(q.bed_config)}</b></div>
                    <div><span style="color:#9CA3AF;">Meal Plan</span><br><b>${HT_MEAL_PLANS[q.meal_plan] || q.meal_plan}</b></div>
                    <div><span style="color:#9CA3AF;">Rooms</span><br><b>${q.rooms || 1}</b></div>
                    <div><span style="color:#9CA3AF;">Max Occupancy</span><br><b>${q.max_adults || 2} Adults, ${q.max_children || 0} Children</b></div>
                    ${q.size_sqm ? `<div><span style="color:#9CA3AF;">Room Size</span><br><b>${q.size_sqm} sqm</b></div>` : ''}
                </div>
                <div style="background:#fff;border:1px solid #E5E7EB;border-radius:8px;padding:10px;display:grid;grid-template-columns:1fr 1fr 1fr;gap:6px;">
                    <div><div style="color:#9CA3AF;font-size:10px;text-transform:uppercase;">Net Rate/night</div><div style="font-weight:700;color:#374151;">${_hte(q.currency)} ${_htFmtN(q.net_rate)}</div></div>
                    <div><div style="color:#9CA3AF;font-size:10px;text-transform:uppercase;">Sell Rate/night</div><div style="font-weight:700;color:#EA580C;">${_hte(q.currency)} ${_htFmtN(q.sell_rate || q.net_rate)}</div></div>
                    <div><div style="color:#9CA3AF;font-size:10px;text-transform:uppercase;">Total Sell</div><div style="font-weight:700;color:#065F46;">${_hte(q.currency)} ${_htFmtN(total)}</div></div>
                </div>
                ${(q.notes||[]).length ? `<div style="margin-top:8px;color:#6B7280;font-size:11px;">${q.notes.map(n=>`<div>• ${_hte(n)}</div>`).join('')}</div>` : ''}
            </div>

            <button onclick="_htSaveQuotation()" style="margin-top:14px;width:100%;padding:10px;font-size:13px;font-weight:600;background:#EA580C;color:#fff;border:none;border-radius:10px;cursor:pointer;">
                <i class="fas fa-save mr-1.5"></i>Save as Quotation
            </button>
        </div>
    </div>`;
}

// ── Approve masterdata ────────────────────────────────────────
window._htApproveMasterdata = async function() {
    const ms = _htCurrentQuotation?.masterdata_suggestion;
    if (!ms) return;
    const btn = document.querySelector('button[onclick="_htApproveMasterdata()"]');
    if (btn) { btn.disabled = true; btn.textContent = 'Saving…'; }

    try {
        const apiUrl = window._ht.cfg.api.hotelServices.replace('endpoints.php', 'masterdata-upsert.php');
        const res    = await fetch(apiUrl, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({
                matched_hotel_sys_id:     ms.matched_hotel_sys_id,
                matched_room_type_sys_id: ms.matched_room_type_sys_id,
                hotel:     ms.hotel,
                room_type: ms.room_type,
                room_rate: ms.room_rate,
            }),
        });
        const json = await res.json();
        if (json.success) {
            // Update quotation with matched hotel_sys_id
            if (json.hotel_sys_id) _htCurrentQuotation.hotel_sys_id = json.hotel_sys_id;
            if (json.room_type_sys_id) _htCurrentQuotation.room_type_sys_id = json.room_type_sys_id;
            htT('success', 'Hotel database updated!');
            // Hide masterdata section
            const mdSection = document.querySelector('[onclick="_htApproveMasterdata()"]')?.closest('div[style*="FFF7ED"]');
            if (mdSection) mdSection.innerHTML = `<div style="padding:8px;font-size:11px;font-weight:600;color:#065F46;"><i class="fas fa-check-circle mr-1"></i>Database updated (${Object.entries(json.actions).map(([k,v])=>`${k}: ${v}`).join(', ')})</div>`;
        } else {
            htT('error', json.message || 'Update failed');
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-check mr-1"></i>Approve & Update Database'; }
        }
    } catch(e) {
        htT('error', 'Network error');
        if (btn) { btn.disabled = false; }
    }
};

window._htSkipMasterdata = function() {
    const mdSection = document.querySelector('[onclick="_htSkipMasterdata()"]')?.closest('div[style*="FFF7ED"]');
    if (mdSection) mdSection.style.display = 'none';
};

// ── Save quotation ────────────────────────────────────────────
window._htSaveQuotation = async function() {
    const q = _htCurrentQuotation;
    if (!q) { htT('error', 'Quotation data নেই'); return; }
    const nights = q.nights || _htCalcNights(q.check_in, q.check_out);
    const rooms  = q.rooms  || 1;
    try {
        const json = await window._htApi({
            action:           'save_quotation',
            hotel_sys_id:     q.hotel_sys_id     || '',
            hotel_name:       q.hotel_name        || '',
            city:             q.city              || '',
            country:          q.country           || '',
            star_rating:      q.star_rating       || 0,
            check_in:         q.check_in          || '',
            check_out:        q.check_out         || '',
            nights:           nights,
            room_type:        q.room_type         || '',
            room_type_sys_id: q.room_type_sys_id  || '',
            bed_config:       q.bed_config        || '',
            size_sqm:         q.size_sqm          || null,
            max_occupancy:    q.max_adults        || 2,
            meal_plan:        q.meal_plan         || 'bb',
            rooms:            rooms,
            currency:         q.currency          || 'BDT',
            net_rate:         q.net_rate          || 0,
            markup_pct:       0,
            sell_rate:        q.sell_rate         || q.net_rate || 0,
            total_net:        (q.net_rate  || 0) * nights * rooms,
            total_sell:       (q.sell_rate || q.net_rate || 0) * nights * rooms,
            note:             (q.notes || []).join('; '),
            source_note_ids:  _htCurrentNoteIds,
        });
        if (json.status === 'success') {
            htT('success', 'Quotation saved!');
            window._htGenClearSelection();
            _htCloseModal();
            await window._htReload();
        } else {
            htT('error', json.message || 'Save ব্যর্থ');
        }
    } catch(e) { htT('error', 'Network error'); }
};

// ── Notes load + render ───────────────────────────────────────
window.htLoadNotes = async function() {
    try {
        const url  = `${window._ht.cfg.api.notes}?action=list&work_sys_id=${encodeURIComponent(window._ht.cfg.workSysId)}&service_slug=hotel&board=mindboard`;
        const res  = await fetch(url);
        const json = await res.json();
        const notes = json.status === 'success' ? (json.data ?? []) : [];
        window._ht.currentNotes = notes;
        _htRenderNotes(notes);
    } catch(e) { _htRenderNotes([]); }
};

function _htRenderNotes(notes) {
    const list = document.getElementById('ht-notes-list');
    if (!list) return;
    if (!notes.length) {
        list.innerHTML = '<div class="text-center py-8 text-gray-300 text-xs">No notes yet. Write something below.</div>';
        return;
    }
    list.innerHTML = notes.map(_htNoteBubble).join('');
    setTimeout(() => { list.scrollTop = list.scrollHeight; }, 50);
}

function _htNoteBubble(n) {
    const dateStr = n.meta_data?.created_by_date?.date ?? '';
    const sysId   = _hte(n.sys_id);
    const content = n.content ?? '';
    const fileUrl = n.serve_url ?? n.file_url ?? '';
    const isSelected = window._ht.selectedNoteIds.has(n.sys_id);

    const delBtn = `<button onclick="htDeleteNote('${sysId}')" style="background:none;border:none;color:#f87171;cursor:pointer;font-size:.7rem;padding:2px 6px;"><i class="fas fa-trash"></i></button>`;

    if (n.note_type === 'text') {
        const len = content.length;
        const minW = len < 20 ? 'min(25%,320px)' : len < 50 ? 'min(45%,480px)' : 'min(65%,640px)';
        return `<div style="align-self:flex-start;min-width:${minW};max-width:85%;position:relative;">
            <input type="checkbox" class="ht-gen-cb" onchange="window._htGenToggleNote('${sysId}',this.checked)"
                ${isSelected?'checked':''} style="position:absolute;top:6px;left:-22px;width:16px;height:16px;cursor:pointer;accent-color:#EA580C;">
            <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:10px 12px;">
                <div style="font-size:.83rem;color:#374151;white-space:pre-line;word-wrap:break-word;">${_hte(content)}</div>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-top:6px;">
                    <span style="font-size:10px;color:#d1d5db;">${_hte(dateStr)}</span>
                    ${delBtn}
                </div>
            </div>
        </div>`;
    }

    if (n.note_type === 'image') {
        return `<div style="align-self:flex-start;max-width:780px;position:relative;">
            <input type="checkbox" class="ht-gen-cb" onchange="window._htGenToggleNote('${sysId}',this.checked)"
                ${isSelected?'checked':''} style="position:absolute;top:6px;left:-22px;width:16px;height:16px;cursor:pointer;accent-color:#EA580C;z-index:2;">
            <img src="${fileUrl}" loading="lazy" onclick="htViewImg('${fileUrl}')"
                style="width:100%;max-height:280px;object-fit:contain;border-radius:8px;cursor:zoom-in;display:block;background:#f9fafb;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-top:4px;">
                <span style="font-size:10px;color:#d1d5db;">${_hte(dateStr)}</span>
                ${delBtn}
            </div>
        </div>`;
    }

    if (n.note_type === 'audio') {
        return `<div style="align-self:flex-start;min-width:300px;max-width:500px;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:10px 12px;">
            <audio controls class="w-full mt-1" style="height:32px;" src="${fileUrl}"></audio>
            <div style="display:flex;justify-content:space-between;margin-top:4px;">
                <span style="font-size:10px;color:#d1d5db;">${_hte(dateStr)}</span>
                ${delBtn}
            </div>
        </div>`;
    }

    return `<div style="align-self:flex-start;background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:8px 12px;min-width:200px;max-width:400px;display:flex;align-items:center;gap:8px;">
        <i class="fas fa-paperclip text-green-500 flex-shrink-0"></i>
        <a href="${fileUrl}" target="_blank" style="font-size:.8rem;color:#059669;flex:1;overflow:hidden;text-overflow:ellipsis;">${_hte(n.file_name||'File')}</a>
        ${delBtn}
    </div>`;
}

// ── Note CRUD ─────────────────────────────────────────────────
window.htSendNote = async function() {
    if (window._ht.pendingFiles.length > 0) {
        const files = [...window._ht.pendingFiles];
        window._ht.pendingFiles = [];
        const mp = document.getElementById('ht-multi-preview');
        if (mp) { mp.style.display='none'; mp.innerHTML=''; }
        for (const file of files) {
            const fd = new FormData();
            fd.append('action','upload'); fd.append('work_sys_id', window._ht.cfg.workSysId);
            fd.append('service_slug','hotel'); fd.append('board','mindboard'); fd.append('file', file);
            try { await fetch(window._ht.cfg.api.notes, { method:'POST', body:fd }); } catch(e) {}
        }
        await htLoadNotes(); return;
    }
    const ta = document.getElementById('ht-note-text');
    const content = ta?.value.trim();
    if (!content) return;
    try {
        const res  = await fetch(window._ht.cfg.api.notes, {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ action:'store', work_sys_id:window._ht.cfg.workSysId, service_slug:'hotel', board:'mindboard', content }),
        });
        const json = await res.json();
        if (json.status === 'success') { if (ta) { ta.value=''; ta.style.height='auto'; } await htLoadNotes(); }
        else htT('error', json.message || 'Failed');
    } catch(e) { htT('error', 'Network error'); }
};

window.htFilesSelected = function(input) {
    if (!input.files.length) return;
    window._ht.pendingFiles = Array.from(input.files);
    const mp = document.getElementById('ht-multi-preview');
    mp.style.display = 'flex';
    mp.innerHTML = window._ht.pendingFiles.map(f =>
        `<span style="display:inline-flex;align-items:center;gap:4px;background:#FFF7ED;border-radius:20px;padding:2px 8px;font-size:.72rem;color:#C2410C;"><i class="fas fa-file" style="font-size:.65rem;"></i>${f.name.length>20?f.name.slice(0,18)+'…':f.name}</span>`
    ).join('');
    input.value = '';
};

window.htDeleteNote = async function(sysId) {
    if (!confirm('Delete this note?')) return;
    try {
        const res  = await fetch(window._ht.cfg.api.notes, {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ action:'delete', note_sys_id:sysId, work_sys_id:window._ht.cfg.workSysId }),
        });
        const json = await res.json();
        if (json.status === 'success') { htT('success','Deleted'); await htLoadNotes(); }
        else htT('error', json.message || 'Failed');
    } catch(e) { htT('error', 'Network error'); }
};

window.htViewImg = function(url) {
    const ov = document.createElement('div');
    ov.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:99999;display:flex;align-items:center;justify-content:center;cursor:zoom-out;';
    ov.innerHTML = `<img src="${url}" style="max-width:90vw;max-height:90vh;border-radius:8px;">`;
    ov.onclick = () => ov.remove();
    document.body.appendChild(ov);
};

// ── Voice recording ───────────────────────────────────────────
window.htRecToggle = async function() {
    if (window._ht.recording) { window._ht.recorder?.stop(); return; }
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        window._ht.recChunks = [];
        window._ht.recorder  = new MediaRecorder(stream);
        window._ht.recorder.ondataavailable = e => { if (e.data.size > 0) window._ht.recChunks.push(e.data); };
        window._ht.recorder.onstop = async () => {
            stream.getTracks().forEach(t => t.stop());
            const blob = new Blob(window._ht.recChunks, { type:'audio/webm' });
            const btn  = document.getElementById('ht-rec-btn');
            if (btn) { btn.style.background='#fdf2f8'; btn.innerHTML='<i class="fas fa-microphone" style="color:#db2777;font-size:.75rem;"></i>'; }
            window._ht.recording = false;
            const fd = new FormData();
            fd.append('action','upload'); fd.append('work_sys_id', window._ht.cfg.workSysId);
            fd.append('service_slug','hotel'); fd.append('board','mindboard');
            fd.append('file', blob, 'voice_'+Date.now()+'.webm');
            try {
                const res  = await fetch(window._ht.cfg.api.notes, { method:'POST', body:fd });
                const json = await res.json();
                if (json.status === 'success') await htLoadNotes();
            } catch(e) {}
        };
        window._ht.recorder.start();
        window._ht.recording = true;
        const btn = document.getElementById('ht-rec-btn');
        if (btn) { btn.style.background='#fee2e2'; btn.innerHTML='<i class="fas fa-stop" style="color:#dc2626;font-size:.75rem;"></i>'; }
    } catch(e) { htT('error','Microphone access denied'); }
};