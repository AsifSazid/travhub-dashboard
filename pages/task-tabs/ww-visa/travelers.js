/**
 * FILE PATH: /pages/task-tabs/ww-visa/travelers.js
 * Visa Service Module — "Travelers & Documents" tab
 *
 * Per traveler:
 *  - Profession type selector
 *  - Doc checklist (required docs from master, matched against traveler_documents)
 *  - Upload link for missing docs
 *  - View / Replace existing docs
 *  - Generate Cover Letter (AI)
 *  - Download options: PDF with cover letter / without / ZIP all
 */

window._renderVsTravelers = function() {
    const el = document.getElementById('vs-tab-travelers');
    if (!el) return;

    const data = window._vs.data;
    if (!data?.meta_data?.master_visa_sys_id) {
        el.innerHTML = `<div style="padding:32px;text-align:center;color:#64748B;font-size:.875rem;">
            ⚠️ Please select a visa service first (Visa Info tab).
        </div>`;
        return;
    }

    const travelers = data.vs_travelers ?? [];
    const wTravelers = window._vs.cfg.workTravelers ?? [];

    if (!wTravelers.length) {
        el.innerHTML = `<div style="padding:32px;text-align:center;color:#64748B;font-size:.875rem;">
            No travelers linked to this work yet.
        </div>`;
        return;
    }

    // Build map from vs_travelers: sys_id → entry
    const vsMap = {};
    travelers.forEach(t => { vsMap[t.sys_id] = t; });

    el.innerHTML = `
    <div style="padding:12px 0;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <span style="font-size:.9rem;font-weight:700;color:#0F172A;">${wTravelers.length} Traveler${wTravelers.length>1?'s':''}</span>
            <button onclick="_vsDownloadAll()"
                    style="padding:6px 14px;border-radius:7px;border:1.5px solid #E2E8F0;
                           background:#fff;font-size:.78rem;font-weight:600;cursor:pointer;color:#4F46E5;">
                ⬇ Download All (ZIP)
            </button>
        </div>
        ${wTravelers.map(t => _vsTravelerCard(t, vsMap[t.sys_id])).join('')}
    </div>`;
};

function _vsTravelerCard(t, vsEntry) {
    const profType   = vsEntry?.profession_type ?? 'general';
    const docs       = vsEntry?.docs ?? [];
    const reqDocs    = window._vsRequiredDocs(profType);
    const coverLetter = vsEntry?.cover_letter;

    // Build doc status map: doc name → { doc_sys_id, status }
    const docMap = {};
    docs.forEach(d => { docMap[d.doc] = d; });

    const profOptions = ['general','student','employment','businessman','non_employment']
        .map(p => `<option value="${p}" ${profType===p?'selected':''}>${_vsProfLabel(p)}</option>`)
        .join('');

    const docsHtml = reqDocs.length
        ? reqDocs.map(rd => _vsDocRow(t.sys_id, rd, docMap[rd.doc])).join('')
        : `<div style="color:#94A3B8;font-size:.85rem;padding:8px 0;">No required documents configured for this profession type.</div>`;

    const coverHtml = coverLetter
        ? `<div style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:8px;padding:10px 12px;margin-top:10px;display:flex;align-items:center;justify-content:space-between;gap:8px;">
               <span style="font-size:.8rem;font-weight:600;color:#15803D;">✓ Cover letter generated</span>
               <div style="display:flex;gap:6px;">
                   <button onclick="_vsViewCoverLetter('${_vsEsc(t.sys_id)}')"
                           style="padding:4px 10px;border-radius:6px;border:1px solid #BBF7D0;background:#fff;font-size:.75rem;cursor:pointer;color:#15803D;">View/Edit</button>
                   <button onclick="_vsDownloadTraveler('${_vsEsc(t.sys_id)}','pdf')"
                           style="padding:4px 10px;border-radius:6px;border:1px solid #BBF7D0;background:#fff;font-size:.75rem;cursor:pointer;color:#15803D;">⬇ PDF</button>
               </div>
           </div>`
        : `<button onclick="_vsGenCoverLetter('${_vsEsc(t.sys_id)}')"
                   style="margin-top:10px;padding:6px 14px;border-radius:7px;background:#EEF2FF;
                          border:1.5px solid #C7D2FE;font-size:.78rem;font-weight:600;cursor:pointer;color:#4F46E5;">
               ✨ Generate Cover Letter
           </button>`;

    // Upload progress bar
    const uploaded = reqDocs.filter(rd => docMap[rd.doc]?.status === 'uploaded').length;
    const total    = reqDocs.length;
    const pct      = total > 0 ? Math.round(uploaded/total*100) : 0;

    return `
    <div style="border:1.5px solid #E2E8F0;border-radius:12px;padding:16px;margin-bottom:14px;background:#fff;">
        <!-- Header -->
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
            <div>
                <div style="font-weight:700;color:#0F172A;font-size:.95rem;">${_vsEsc(t.name)}</div>
                <div style="font-size:.75rem;color:#64748B;">${_vsEsc(t.passport_no ?? '—')}</div>
            </div>
            <div style="display:flex;align-items:center;gap:8px;">
                <label style="font-size:.75rem;color:#64748B;font-weight:600;">Profession:</label>
                <select onchange="_vsSetProfession('${_vsEsc(t.sys_id)}', this.value)"
                        style="padding:4px 8px;border:1.5px solid #E2E8F0;border-radius:6px;font-size:.78rem;outline:none;">
                    ${profOptions}
                </select>
            </div>
        </div>

        <!-- Progress -->
        ${total > 0 ? `
        <div style="margin-bottom:12px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                <span style="font-size:.73rem;color:#64748B;font-weight:600;">Documents</span>
                <span style="font-size:.73rem;color:${pct===100?'#15803D':'#64748B'};font-weight:700;">${uploaded}/${total}</span>
            </div>
            <div style="height:5px;background:#E2E8F0;border-radius:999px;overflow:hidden;">
                <div style="height:100%;width:${pct}%;background:${pct===100?'#22C55E':'#6366f1'};border-radius:999px;transition:width .3s;"></div>
            </div>
        </div>` : ''}

        <!-- Doc checklist -->
        <div style="border:1px solid #F1F5F9;border-radius:8px;overflow:hidden;">${docsHtml}</div>

        <!-- Cover letter -->
        ${coverHtml}
    </div>`;
}

function _vsDocRow(travSysId, rd, existing) {
    const uploaded = existing?.status === 'uploaded';
    const icon     = uploaded ? '✅' : '⬜';
    const color    = uploaded ? '#15803D' : '#94A3B8';
    const viewBtn  = uploaded
        ? `<button onclick="_vsViewDoc('${_vsEsc(existing.doc_sys_id)}')"
                   style="padding:3px 8px;border-radius:5px;border:1px solid #E2E8F0;background:#fff;
                          font-size:.72rem;cursor:pointer;color:#64748B;">View</button>
           <button onclick="_vsReplaceDoc('${_vsEsc(travSysId)}', '${_vsEsc(rd.doc)}')"
                   style="padding:3px 8px;border-radius:5px;border:1px solid #E2E8F0;background:#fff;
                          font-size:.72rem;cursor:pointer;color:#64748B;">Replace</button>`
        : `<button onclick="_vsUploadDoc('${_vsEsc(travSysId)}', '${_vsEsc(rd.doc)}')"
                   style="padding:3px 8px;border-radius:5px;border:1.5px solid #6366f1;background:#EEF2FF;
                          font-size:.72rem;cursor:pointer;color:#4F46E5;font-weight:600;">⬆ Upload</button>`;

    return `<div style="display:flex;align-items:center;justify-content:space-between;padding:9px 12px;border-bottom:1px solid #F1F5F9;">
        <div style="display:flex;align-items:center;gap:8px;">
            <span>${icon}</span>
            <span style="font-size:.85rem;color:${color};font-weight:${uploaded?'600':'400'};">${_vsEsc(rd.doc)}</span>
            ${rd.note ? `<span style="font-size:.73rem;color:#CBD5E1;">— ${_vsEsc(rd.note)}</span>` : ''}
        </div>
        <div style="display:flex;gap:5px;">${viewBtn}</div>
    </div>`;
}

// ── Profession change ─────────────────────────────────────
window._vsSetProfession = async function(travSysId, profType) {
    const data      = window._vs.data;
    const travelers = JSON.parse(JSON.stringify(data?.vs_travelers ?? []));

    // Find or create traveler entry
    let tEntry = travelers.find(t => t.sys_id === travSysId);
    if (!tEntry) {
        const workT = (window._vs.cfg.workTravelers ?? []).find(t => t.sys_id === travSysId);
        tEntry = {
            sys_id:          travSysId,
            name:            workT?.name ?? '',
            passport_no:     workT?.passport_no ?? '',
            profession_type: profType,
            docs:            [],
            cover_letter:    null,
            dl_pdf_path:     null,
        };
        travelers.push(tEntry);
    } else {
        tEntry.profession_type = profType;
    }

    const j = await window._vsApi({ action: 'update_travelers', vs_travelers: travelers });
    if (j.status !== 'success') { alert(j.message); return; }
    window._vs.data = j.data;
    window._renderVsTravelers();
};

// ── Doc view ──────────────────────────────────────────────
window._vsViewDoc = function(docSysId) {
    // Open in traveler documents viewer
    window.open(`${window._vs.cfg.api.travelerDocViewer}?doc_sys_id=${encodeURIComponent(docSysId)}`, '_blank');
};

// ── Doc upload / replace ──────────────────────────────────
window._vsUploadDoc = function(travSysId, docName) {
    // Open traveler upload panel — or just link to traveler page with this doc type flagged
    const trav = (window._vs.cfg.workTravelers ?? []).find(t => t.sys_id === travSysId);
    if (!trav) return;
    // Open the traveler's document upload area in a new tab
    window.open(`show-travelers.php?sys_id=${encodeURIComponent(travSysId)}&upload_doc=${encodeURIComponent(docName)}`, '_blank');
};

window._vsReplaceDoc = function(travSysId, docName) {
    window._vsUploadDoc(travSysId, docName);
};

// ── Sync docs from traveler_documents ─────────────────────
// Called by parent or status tab to refresh doc sync
window._vsSyncDocs = async function() {
    const data      = window._vs.data;
    const meta      = data?.meta_data;
    if (!meta) return;

    const wTravelers = window._vs.cfg.workTravelers ?? [];
    let travelers    = JSON.parse(JSON.stringify(data.vs_travelers ?? []));

    // For each work traveler, fetch their traveler_documents and match against required docs
    const travMap = {};
    travelers.forEach(t => { travMap[t.sys_id] = t; });

    await Promise.all(wTravelers.map(async wt => {
        let entry = travMap[wt.sys_id];
        if (!entry) {
            entry = {
                sys_id:          wt.sys_id,
                name:            wt.name ?? '',
                passport_no:     wt.passport_no ?? '',
                profession_type: 'general',
                docs:            [],
                cover_letter:    null,
                dl_pdf_path:     null,
            };
            travelers.push(entry);
            travMap[wt.sys_id] = entry;
        }

        // Fetch documents
        try {
            const res = await fetch(
                `${window._vs.cfg.api.listDocuments}?traveler_sys_id=${encodeURIComponent(wt.sys_id)}&include_data=0`
            );
            const j = await res.json();
            const groups = j.groups ?? {};

            // All docs flat
            const allDocs = Object.values(groups).flat();

            // Match required docs
            const rd = window._vsRequiredDocs(entry.profession_type);
            entry.docs = rd.map((r, i) => {
                const match = allDocs.find(d =>
                    (d.doc_type ?? '').toLowerCase().includes(r.doc.toLowerCase().split(' ')[0].toLowerCase())
                );
                return {
                    seq:        r.seq ?? (i + 1),
                    doc:        r.doc,
                    doc_sys_id: match?.sys_id ?? null,
                    status:     match ? 'uploaded' : 'missing',
                };
            });
        } catch(e) { /* silent — keep existing docs */ }
    }));

    const j = await window._vsApi({ action: 'update_travelers', vs_travelers: travelers });
    if (j.status === 'success') {
        window._vs.data = j.data;
        window._renderVsTravelers();
    }
};

// ── Generate Cover Letter ─────────────────────────────────
window._vsGenCoverLetter = async function(travSysId) {
    const data  = window._vs.data;
    const meta  = data?.meta_data;
    const trav  = (data?.vs_travelers ?? []).find(t => t.sys_id === travSysId);
    if (!trav || !meta) return;

    const el = document.querySelector(`[onclick*="_vsGenCoverLetter('${travSysId}')"]`);
    if (el) { el.textContent = '⏳ Generating…'; el.disabled = true; }

    try {
        const res = await fetch(window._vs.cfg.api.genCoverLetter, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                traveler_sys_id: travSysId,
                traveler_name:   trav.name,
                passport_no:     trav.passport_no,
                profession_type: trav.profession_type,
                country:         meta.country_name,
                visa_type:       meta.visa_type_name,
                visa_category:   meta.visa_category_name,
                duration:        meta.duration_label,
                work_sys_id:     window._vs.cfg.workSysId,
                client_name:     window._vs.cfg.clientName,
            }),
        });
        const j = await res.json();
        if (!j.draft) throw new Error(j.message ?? 'AI error');

        // Store in vs_travelers
        const travelers = JSON.parse(JSON.stringify(data.vs_travelers ?? []));
        const t2 = travelers.find(t => t.sys_id === travSysId);
        if (t2) {
            t2.cover_letter = {
                ai_draft:     j.draft,
                edited:       j.draft,
                generated_at: new Date().toISOString(),
            };
        }
        const upd = await window._vsApi({ action: 'update_cover_letter',
            traveler_sys_id: travSysId,
            cover_letter: t2?.cover_letter
        });
        if (upd.status === 'success') {
            await window._vsReload();
            window._renderVsTravelers();
        }
    } catch(e) {
        alert('Cover letter generation failed: ' + e.message);
        if (el) { el.textContent = '✨ Generate Cover Letter'; el.disabled = false; }
    }
};

// ── View/edit cover letter ────────────────────────────────
window._vsViewCoverLetter = function(travSysId) {
    const trav = (window._vs.data?.vs_travelers ?? []).find(t => t.sys_id === travSysId);
    if (!trav?.cover_letter) return;

    // Show modal
    const existing = document.getElementById('vs-cover-modal');
    if (existing) existing.remove();

    const modal = document.createElement('div');
    modal.id = 'vs-cover-modal';
    modal.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:200;display:flex;align-items:center;justify-content:center;padding:20px;';
    modal.innerHTML = `
    <div style="background:#fff;border-radius:16px;width:100%;max-width:680px;max-height:90vh;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.2);">
        <div style="padding:16px 20px;border-bottom:1px solid #E2E8F0;display:flex;justify-content:space-between;align-items:center;">
            <div style="font-weight:700;font-size:.95rem;">Cover Letter — ${_vsEsc(trav.name)}</div>
            <button onclick="document.getElementById('vs-cover-modal').remove()"
                    style="background:none;border:none;cursor:pointer;font-size:1.1rem;color:#64748B;">✕</button>
        </div>
        <div style="flex:1;overflow-y:auto;padding:20px;">
            <div contenteditable="true" id="vs-cover-editor"
                 style="min-height:300px;font-size:.875rem;line-height:1.8;color:#0F172A;outline:none;
                        border:1.5px solid #E2E8F0;border-radius:8px;padding:14px;">${trav.cover_letter.edited ?? trav.cover_letter.ai_draft}</div>
        </div>
        <div style="padding:14px 20px;border-top:1px solid #E2E8F0;display:flex;gap:8px;justify-content:flex-end;">
            <button onclick="document.getElementById('vs-cover-modal').remove()"
                    style="padding:7px 14px;border-radius:7px;border:1px solid #E2E8F0;background:#fff;
                           font-size:.83rem;font-weight:600;cursor:pointer;color:#64748B;">Close</button>
            <button onclick="_vsSaveCoverEdit('${_vsEsc(travSysId)}')"
                    style="padding:7px 14px;border-radius:7px;background:#4F46E5;color:#fff;
                           font-size:.83rem;font-weight:600;border:none;cursor:pointer;">Save</button>
            <button onclick="_vsDownloadTraveler('${_vsEsc(travSysId)}','pdf')"
                    style="padding:7px 14px;border-radius:7px;background:#EEF2FF;color:#4F46E5;
                           font-size:.83rem;font-weight:600;border:1.5px solid #C7D2FE;cursor:pointer;">⬇ PDF</button>
        </div>
    </div>`;
    document.body.appendChild(modal);
};

window._vsSaveCoverEdit = async function(travSysId) {
    const editor = document.getElementById('vs-cover-editor');
    if (!editor) return;
    const edited = editor.innerHTML;
    const trav   = (window._vs.data?.vs_travelers ?? []).find(t => t.sys_id === travSysId);
    if (!trav?.cover_letter) return;

    const cl = { ...trav.cover_letter, edited };
    const j  = await window._vsApi({ action: 'update_cover_letter',
        traveler_sys_id: travSysId, cover_letter: cl });
    if (j.status !== 'success') { alert(j.message); return; }
    await window._vsReload();
    document.getElementById('vs-cover-modal')?.remove();
    window._renderVsTravelers();
};

// ── Download ──────────────────────────────────────────────
window._vsDownloadTraveler = function(travSysId, format) {
    const url = `${window._vs.cfg.api.genDocPackage}?traveler_sys_id=${encodeURIComponent(travSysId)}&format=${format}&work_sys_id=${encodeURIComponent(window._vs.cfg.workSysId)}`;
    window.open(url, '_blank');
};

window._vsDownloadAll = function() {
    const url = `${window._vs.cfg.api.genDocPackage}?all=1&work_sys_id=${encodeURIComponent(window._vs.cfg.workSysId)}`;
    window.open(url, '_blank');
};