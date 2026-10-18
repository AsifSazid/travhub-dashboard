/**
 * FILE PATH: /pages/task-tabs/ww-visa/travelers.js
 * Visa Service Module — Traveler Docs tab
 * Layer 2: Traveler pills | Layer 3: Documents / Applicant Info / Cover Letter / Export / Status
 */

/* ── Layer 2 entry ────────────────────────────────────────── */
window._renderVsTravelers = async function () {
    await window._vsGetWorkTravelers();
    window._buildTravelersShell();
};

/* ── Refresh (called from show-works right sidebar) ────────── */
window._vsRefreshTravelers = async function () {
    window._vs.cfg._workTravelersLive = null;
    await window._vsGetWorkTravelers();
    if (window._vs.activeTab === 'travelers') window._buildTravelersShell();
    window._vsUpdateTravelerBadge?.();
};

/* ── Load work travelers from API ──────────────────────────── */
window._vsGetWorkTravelers = async function () {
    if (window._vs.cfg._workTravelersLive) return window._vs.cfg._workTravelersLive;
    try {
        const j = await fetch(
            `${window._vs.cfg.api.workTravelers}?action=list&work_sys_id=${encodeURIComponent(window._vs.cfg.workSysId)}`
        ).then(r => r.json());
        window._vs.cfg._workTravelersLive = j.data ?? [];
    } catch (e) {
        window._vs.cfg._workTravelersLive = [];
    }
    return window._vs.cfg._workTravelersLive;
};

/* ── Traveler ready check ───────────────────────────────────── */
window._vsTravelerReady = function (vsEntry) {
    if (!vsEntry?.docs?.length) return false;
    const req = vsEntry.docs.filter(d => d.required !== false);
    return req.length > 0 && req.every(d => d.status === 'uploaded');
};

/* ── Traveler status chip ───────────────────────────────────── */
window._vsTravStatusChip = function (status) {
    const map = {
        pending:   { bg:'#F1F5F9', c:'#64748B', label:'Pending' },
        ready:     { bg:'#DCFCE7', c:'#15803D', label:'Ready' },
        submitted: { bg:'#EEF2FF', c:'#4F46E5', label:'Submitted' },
        approved:  { bg:'#D1FAE5', c:'#065F46', label:'Approved' },
        rejected:  { bg:'#FEE2E2', c:'#B91C1C', label:'Rejected' },
        on_hold:   { bg:'#FEF3C7', c:'#92400E', label:'On Hold' },
    };
    const s = map[status] ?? { bg:'#F1F5F9', c:'#64748B', label: status ?? 'Pending' };
    return `<span style="background:${s.bg};color:${s.c};font-size:.72rem;font-weight:700;
                         padding:3px 9px;border-radius:999px;">● ${s.label}</span>`;
};

/* ══════════════════════════════════════════════════════════════
   LAYER 3 — Documents
══════════════════════════════════════════════════════════════ */
window._renderVsDocs = function (travSysId) {
    const el = document.getElementById('vs-sub-panel-docs');
    if (!el) return;

    const data   = window._vs.data;
    const vsT    = (data?.vs_travelers ?? []).find(t => t.sys_id === travSysId) ?? {};
    const docs   = vsT.docs ?? [];
    const rdAll  = window._vsRequiredDocs(vsT.profession_type ?? 'general');

    // Group docs by base requirement name (strip " (2)", " (3)" suffixes)
    const docsByReq = {};
    docs.forEach(d => {
        const base = d.doc.replace(/\s*\(\d+\)$/, '').trim();
        if (!docsByReq[base]) docsByReq[base] = [];
        docsByReq[base].push(d);
    });

    const uploadedReqs = rdAll.filter(r => (docsByReq[r.doc]??[]).some(d=>d.status==='uploaded')).length;
    const total = rdAll.length;
    const pct   = total > 0 ? Math.round(uploadedReqs / total * 100) : 0;

    const profOptions = ['general','student','employment','businessman','non_employment']
        .map(p => `<option value="${p}" ${(vsT.profession_type??'general')===p?'selected':''}>${_vsProfLabel(p)}</option>`)
        .join('');

    el.innerHTML = `
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;flex-wrap:wrap;">
        <label style="font-size:.78rem;font-weight:700;color:#64748B;">Profession Type:</label>
        <select onchange="window._vsSetProfession('${_vsEsc(travSysId)}',this.value)"
                style="padding:5px 10px;border:1.5px solid #E2E8F0;border-radius:7px;font-size:.82rem;outline:none;">
            ${profOptions}
        </select>
        <button onclick="window._vsSyncDocsForTraveler('${_vsEsc(travSysId)}')"
                style="margin-left:auto;padding:5px 12px;border-radius:7px;border:1.5px solid #E2E8F0;
                       background:#fff;font-size:.75rem;font-weight:600;cursor:pointer;color:#64748B;">
            🔄 Sync from Profile
        </button>
    </div>

    ${total > 0 ? `
    <div style="margin-bottom:14px;">
        <div style="display:flex;justify-content:space-between;margin-bottom:5px;">
            <span style="font-size:.78rem;font-weight:700;color:#475569;">Document Checklist</span>
            <span style="font-size:.78rem;font-weight:700;color:${pct===100?'#15803D':'#64748B'};">${uploadedReqs}/${total} uploaded</span>
        </div>
        <div style="height:6px;background:#E2E8F0;border-radius:999px;overflow:hidden;">
            <div style="height:100%;width:${pct}%;background:${pct===100?'#22C55E':'#6366f1'};border-radius:999px;transition:width .3s;"></div>
        </div>
    </div>` : ''}

    ${rdAll.length
        ? `<div style="border:1px solid #F1F5F9;border-radius:10px;overflow:hidden;">
               ${rdAll.map(rd => _vsDocRowGroup(travSysId, rd, docsByReq[rd.doc] ?? [])).join('')}
           </div>`
        : `<div style="padding:20px;text-align:center;color:#94A3B8;font-size:.85rem;">
               No required documents configured. Check master visa data.
           </div>`}`;
};

/* _vsDocRowGroup: renders one requirement row with ALL matched docs + all pages */
function _vsDocRowGroup(travSysId, rd, matchedDocs) {
    const isOpt     = rd.required === false;
    const hasAny    = matchedDocs.some(d => d.status === 'uploaded');
    const color     = hasAny ? '#15803D' : '#64748B';
    const rowId     = `vs-doc-acc-${travSysId.replace(/\W/g,'_')}-${rd.doc.replace(/\W/g,'_')}`;

    // Collect ALL pages across all matched docs
    const allPages = matchedDocs.flatMap((d, di) => {
        const pages = d.pages ?? [];
        return pages.map((p, pi) => ({
            url:      p.url ?? null,
            filename: p.filename ?? null,
            docLabel: matchedDocs.length > 1 ? `Doc ${di+1} / Page ${pi+1}` : `Page ${pi+1}`,
            docSysId: d.doc_sys_id,
            docName:  d.doc,
            pageIdx:  pi,
            ageWarn:  pi === 0 ? d.age_warning : null,
        }));
    });

    const totalPages = allPages.length;
    const firstDoc   = matchedDocs[0];

    // Age warning — from first doc
    const ageW    = firstDoc?.age_warning;
    const ageChip = ageW ? `<span style="font-size:.68rem;font-weight:700;padding:2px 7px;border-radius:999px;
        background:${ageW==='Expired'?'#FEE2E2':ageW.startsWith('Expires')?'#FEF3C7':'#F1F5F9'};
        color:${ageW==='Expired'?'#B91C1C':ageW.startsWith('Expires')?'#92400E':'#64748B'};">${_vsEsc(ageW)}</span>` : '';

    const docNum  = firstDoc?.doc_number
        ? `<span style="font-size:.7rem;color:#94A3B8;">#${_vsEsc(firstDoc.doc_number)}</span>` : '';

    const docCountChip = matchedDocs.length > 1
        ? `<span style="font-size:.7rem;font-weight:700;color:#4F46E5;background:#EEF2FF;padding:2px 7px;border-radius:999px;">${matchedDocs.length} docs</span>`
        : '';

    // Toggle button — shows total pages
    const toggleBtn = hasAny && totalPages > 0 ? `
        <button onclick="(function(){const e=document.getElementById('${rowId}');
            e.style.display=e.style.display==='none'?'block':'none';})()"
            style="padding:3px 8px;border-radius:5px;border:1px solid #E2E8F0;background:#fff;
                   font-size:.72rem;cursor:pointer;color:#64748B;">
            ${totalPages}p ▾
        </button>` : '';

    // Action buttons
    const removeBtn = `<button onclick="window._vsRemoveDoc('${_vsEsc(travSysId)}','${_vsEsc(rd.doc)}')"
                   style="padding:3px 9px;border-radius:5px;border:1px solid #FCA5A5;background:#FFF1F2;
                          font-size:.72rem;cursor:pointer;color:#EF4444;font-weight:600;">Remove</button>`;

    const actionBtns = hasAny
        ? `<button onclick="window._vsReplaceDoc('${_vsEsc(travSysId)}','${_vsEsc(rd.doc)}')"
                   style="padding:3px 9px;border-radius:5px;border:1px solid #E2E8F0;background:#fff;
                          font-size:.72rem;cursor:pointer;color:#64748B;">Replace</button>
           ${removeBtn}`
        : `<button onclick="window._vsUploadDoc('${_vsEsc(travSysId)}','${_vsEsc(rd.doc)}')"
                   style="padding:3px 9px;border-radius:5px;border:1.5px solid #6366f1;background:#EEF2FF;
                          font-size:.72rem;cursor:pointer;color:#4F46E5;font-weight:600;">Upload</button>
           ${removeBtn}`;

    // Pages accordion panel
    const pagesHtml = hasAny && totalPages > 0 ? `
        <div id="${rowId}" style="display:none;background:#F8FAFC;border-top:1px solid #F1F5F9;
                                  padding:8px 14px 10px 44px;">
            <div style="display:flex;flex-wrap:wrap;gap:8px;">
                ${allPages.map(p => `
                    <div style="display:flex;align-items:center;gap:5px;padding:4px 10px;
                                background:#fff;border:1px solid #E2E8F0;border-radius:6px;">
                        ${p.url
                            ? `<a href="${_vsEsc(p.url)}" target="_blank"
                                  style="font-size:.75rem;color:#4F46E5;text-decoration:none;display:flex;align-items:center;gap:4px;">
                                   📄 ${_vsEsc(p.docLabel)}
                                   ${p.ageWarn ? `<span style="color:#B91C1C;font-size:.65rem;">(${_vsEsc(p.ageWarn)})</span>` : ''}
                               </a>`
                            : `<span style="font-size:.75rem;color:#94A3B8;">📄 ${_vsEsc(p.docLabel)}</span>`
                        }
                        <button onclick="window._vsRemoveDocPage('${_vsEsc(travSysId)}','${_vsEsc(rd.doc)}','${_vsEsc(p.docSysId??'')}',${p.pageIdx})"
                                title="Remove this file from visa checklist"
                                style="padding:1px 6px;border-radius:4px;border:1px solid #FCA5A5;
                                       background:#FFF1F2;font-size:.68rem;cursor:pointer;color:#EF4444;">✕</button>
                    </div>`
                ).join('')}
            </div>
        </div>` : '';

    return `<div style="border-bottom:1px solid #F1F5F9;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;">
            <div style="display:flex;align-items:center;gap:9px;flex:1;min-width:0;">
                <span style="font-size:1rem;flex-shrink:0;">${hasAny ? '✅' : '⬜'}</span>
                <div style="min-width:0;">
                    <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                        <span style="font-size:.85rem;color:${color};font-weight:${hasAny?'600':'400'};">${_vsEsc(rd.doc)}</span>
                        ${isOpt ? `<span style="font-size:.7rem;color:#94A3B8;">(optional)</span>` : ''}
                        ${docCountChip}${docNum}${ageChip}
                    </div>
                    ${rd.note ? `<div style="font-size:.72rem;color:#CBD5E1;">${_vsEsc(rd.note)}</div>` : ''}
                </div>
            </div>
            <div style="display:flex;gap:5px;flex-shrink:0;">${toggleBtn}${actionBtns}</div>
        </div>
        ${pagesHtml}
    </div>`;
}

/* Keep old _vsDocRow for backward compat */
function _vsDocRow(travSysId, rd, existing) {
    const uploaded = existing?.status === 'uploaded';
    const color    = uploaded ? '#15803D' : '#64748B';
    const isOpt    = rd.required === false;

    const actionBtns = uploaded
        ? `<button onclick="window._vsViewDoc('${_vsEsc(existing.doc_sys_id)}','${_vsEsc(travSysId)}','${_vsEsc(existing.file_name??'')}')"
                   style="padding:3px 9px;border-radius:5px;border:1px solid #E2E8F0;background:#fff;
                          font-size:.72rem;cursor:pointer;color:#64748B;">View</button>
           <button onclick="window._vsReplaceDoc('${_vsEsc(travSysId)}','${_vsEsc(rd.doc)}')"
                   style="padding:3px 9px;border-radius:5px;border:1px solid #E2E8F0;background:#fff;
                          font-size:.72rem;cursor:pointer;color:#64748B;">Replace</button>`
        : `<button onclick="window._vsUploadDoc('${_vsEsc(travSysId)}','${_vsEsc(rd.doc)}')"
                   style="padding:3px 9px;border-radius:5px;border:1.5px solid #6366f1;background:#EEF2FF;
                          font-size:.72rem;cursor:pointer;color:#4F46E5;font-weight:600;">Upload</button>`;

    // Age warning chip
    const ageW    = existing?.age_warning;
    const ageChip = ageW ? `<span style="font-size:.68rem;font-weight:700;padding:2px 7px;border-radius:999px;
        background:${ageW==='Expired'?'#FEE2E2':ageW.startsWith('Expires')?'#FEF3C7':'#F1F5F9'};
        color:${ageW==='Expired'?'#B91C1C':ageW.startsWith('Expires')?'#92400E':'#64748B'};">${_vsEsc(ageW)}</span>` : '';

    const docNum  = existing?.doc_number
        ? `<span style="font-size:.7rem;color:#94A3B8;">#${_vsEsc(existing.doc_number)}</span>` : '';

    // Pages accordion — only when uploaded and has multiple pages
    const pages    = existing?.pages ?? [];
    const pageCount= pages.length;
    const rowId    = `vs-doc-acc-${travSysId}-${_vsEsc(rd.doc).replace(/\W/g,'_')}`;

    const pagesHtml = uploaded && pageCount > 0 ? `
        <div id="${rowId}" style="display:none;background:#F8FAFC;border-top:1px solid #F1F5F9;
                                  padding:8px 14px 10px 44px;">
            <div style="display:flex;flex-wrap:wrap;gap:8px;">
                ${pages.map((p, pi) => p.url
                    ? `<a href="${_vsEsc(p.url)}" target="_blank"
                          style="display:flex;align-items:center;gap:5px;padding:4px 10px;
                                 background:#fff;border:1px solid #E2E8F0;border-radius:6px;
                                 font-size:.75rem;color:#4F46E5;text-decoration:none;">
                           📄 Page ${pi+1}${pageCount===1?' ('+_vsEsc(p.filename??'')+')'  :''}
                       </a>`
                    : `<span style="padding:4px 10px;background:#F1F5F9;border-radius:6px;
                                   font-size:.75rem;color:#94A3B8;">Page ${pi+1}</span>`
                ).join('')}
            </div>
        </div>` : '';

    const toggleBtn = uploaded && pageCount > 0 ? `
        <button onclick="(function(){const e=document.getElementById('${rowId}');
            e.style.display=e.style.display==='none'?'block':'none';})()"
            style="padding:3px 8px;border-radius:5px;border:1px solid #E2E8F0;background:#fff;
                   font-size:.72rem;cursor:pointer;color:#64748B;">
            ${pageCount}p ▾
        </button>` : '';

    return `<div style="border-bottom:1px solid #F1F5F9;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;">
            <div style="display:flex;align-items:center;gap:9px;flex:1;min-width:0;">
                <span style="font-size:1rem;flex-shrink:0;">${uploaded ? '✅' : '⬜'}</span>
                <div style="min-width:0;">
                    <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                        <span style="font-size:.85rem;color:${color};font-weight:${uploaded?'600':'400'};">${_vsEsc(rd.doc)}</span>
                        ${isOpt ? `<span style="font-size:.7rem;color:#94A3B8;">(optional)</span>` : ''}
                        ${docNum}${ageChip}
                    </div>
                    ${rd.note ? `<div style="font-size:.72rem;color:#CBD5E1;">${_vsEsc(rd.note)}</div>` : ''}
                </div>
            </div>
            <div style="display:flex;gap:5px;flex-shrink:0;">${toggleBtn}${actionBtns}</div>
        </div>
        ${pagesHtml}
    </div>`;
}

/* ══════════════════════════════════════════════════════════════
   LAYER 3 — Applicant Info (auto-fill from traveler profile)
══════════════════════════════════════════════════════════════ */
window._renderVsAppInfo = function (travSysId) {
    _currentTravSysId = travSysId;
    const el = document.getElementById('vs-sub-panel-appinfo');
    if (!el) return;

    const wT    = (window._vs.cfg?._workTravelersLive ?? []).find(t => t.sys_id === travSysId) ?? {};
    const vsT   = (window._vs.data?.vs_travelers ?? []).find(t => t.sys_id === travSysId) ?? {};
    const ai    = vsT.app_info ?? {};
    const v     = (key, fallback='') => ai[key] || fallback;

    // Parse all JSON fields
    const phone   = _vsParseJson(wT.phone   ?? '{}');
    const email   = _vsParseJson(wT.email   ?? '{}');
    const address = _vsParseJson(wT.address ?? '{}');
    const pInfo   = _vsParsePassportBio(wT.passport_info);
    const perInfo = _vsParseJson(wT.personal_info  ?? '{}');
    const empInfo = _vsParseJson(wT.employment_info ?? '{}');
    const famInfo = _vsParseJson(wT.family_info     ?? '{}');
    const eduInfo = _vsParseJson(wT.educational_info ?? '{}');
    const credInfo= _vsParseJson(wT.credentials    ?? '{}');

    // Field values — saved app_info first, then traveler profile
    const fields = {
        surname:          v('surname',         pInfo.surname ?? ''),
        given_name:       v('given_name',       pInfo.given_names ?? wT.name ?? ''),
        passport_no:      v('passport_no',      wT.passport_no ?? pInfo.passport_number ?? ''),
        dob:              v('dob',              wT.date_of_birth ?? ''),
        place_of_birth:   v('place_of_birth',   perInfo.place_of_birth ?? ''),
        country_of_birth: v('country_of_birth', pInfo.country_code ?? ''),
        nationality:      v('nationality',      pInfo.nationality ?? ''),
        passport_expiry:  v('passport_expiry',  pInfo.date_of_expiry ?? ''),
        passport_issue:   v('passport_issue',   pInfo.date_of_issue ?? ''),
        issuing_authority:v('issuing_authority',pInfo.issuing_authority ?? ''),
        gender:           v('gender',           perInfo.gender ?? ''),
        marital_status:   v('marital_status',   perInfo.marital_status ?? ''),
        religion:         v('religion',         perInfo.religion ?? ''),
        mobile:           v('mobile',           phone.primary_no ?? ''),
        email:            v('email',            email.primary ?? ''),
        father_name:      v('father_name',      famInfo.father_name ?? ''),
        mother_name:      v('mother_name',      famInfo.mother_name ?? ''),
        spouse_name:      v('spouse_name',      famInfo.spouse_name ?? ''),
        occupation:       v('occupation',       empInfo.jobTitle ?? ''),
        employer_name:    v('employer_name',    empInfo.employer ?? credInfo.employer ?? ''),
        employer_address: v('employer_address', empInfo.employerAddress ?? ''),
        monthly_income:   v('monthly_income',   empInfo.monthlyIncome ?? ''),
        present_address:  v('present_address',  address.present ?? ''),
        permanent_address:v('permanent_address',address.permanent ?? ''),
        education:        v('education',        eduInfo.level ?? ''),
    };

    const section = (title) => `
        <div style="font-size:.75rem;font-weight:800;color:#4F46E5;text-transform:uppercase;
                    letter-spacing:.06em;margin:18px 0 10px;padding-bottom:5px;
                    border-bottom:2px solid #EEF2FF;">${title}</div>`;

    el.innerHTML = `
    <div style="max-width:700px;">

        ${section('Passport Information')}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:4px;">
            ${_vsAiField('Surname',            'surname',           fields.surname)}
            ${_vsAiField('Given Name(s)',       'given_name',        fields.given_name)}
            ${_vsAiField('Passport No',         'passport_no',       fields.passport_no)}
            ${_vsAiField('Nationality',         'nationality',       fields.nationality)}
            ${_vsAiField('Date of Birth',       'dob',               fields.dob,          'date')}
            ${_vsAiField('Place of Birth',      'place_of_birth',    fields.place_of_birth)}
            ${_vsAiField('Country of Birth',    'country_of_birth',  fields.country_of_birth)}
            ${_vsAiField('Passport Issue Date', 'passport_issue',    fields.passport_issue, 'date')}
            ${_vsAiField('Passport Expiry',     'passport_expiry',   fields.passport_expiry,'date')}
            ${_vsAiField('Issuing Authority',   'issuing_authority', fields.issuing_authority)}
        </div>

        ${section('Personal Information')}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:4px;">
            ${_vsAiField('Gender',         'gender',         fields.gender)}
            ${_vsAiField('Marital Status', 'marital_status', fields.marital_status)}
            ${_vsAiField('Religion',       'religion',       fields.religion)}
            ${_vsAiField('Mobile',         'mobile',         fields.mobile)}
            ${_vsAiField('Email',          'email',          fields.email, 'email')}
        </div>

        ${section('Family Information')}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:4px;">
            ${_vsAiField("Father's Name", 'father_name', fields.father_name)}
            ${_vsAiField("Mother's Name", 'mother_name', fields.mother_name)}
            ${_vsAiField("Spouse's Name", 'spouse_name', fields.spouse_name)}
        </div>

        ${section('Employment Information')}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:4px;">
            ${_vsAiField('Occupation',        'occupation',       fields.occupation)}
            ${_vsAiField('Employer Name',     'employer_name',    fields.employer_name)}
            ${_vsAiField('Monthly Income',    'monthly_income',   fields.monthly_income)}
            ${_vsAiField('Education Level',   'education',        fields.education)}
        </div>
        <div style="margin-bottom:10px;">
            <label style="font-size:.73rem;font-weight:600;color:#64748B;display:block;margin-bottom:4px;">Employer Address</label>
            <div style="display:flex;align-items:stretch;border:1.5px solid #E2E8F0;border-radius:7px;overflow:hidden;">
                <textarea id="vs-ai-employer_address" rows="2"
                      style="flex:1;padding:7px 10px;border:none;font-size:.85rem;
                             outline:none;resize:none;box-sizing:border-box;">${_vsEsc(fields.employer_address)}</textarea>
                <button onclick="_vsAiCopy('vs-ai-employer_address')" title="Copy"
                        style="padding:0 10px;border:none;border-left:1px solid #E2E8F0;
                               background:#F8FAFC;cursor:pointer;color:#94A3B8;font-size:.8rem;">⎘</button>
            </div>
        </div>

        ${section('Address')}
        <div style="margin-bottom:10px;">
            <label style="font-size:.73rem;font-weight:600;color:#64748B;display:block;margin-bottom:4px;">Present Address</label>
            <div style="display:flex;align-items:stretch;border:1.5px solid #E2E8F0;border-radius:7px;overflow:hidden;">
                <textarea id="vs-ai-present_address" rows="2"
                      style="flex:1;padding:7px 10px;border:none;font-size:.85rem;
                             outline:none;resize:none;box-sizing:border-box;">${_vsEsc(fields.present_address)}</textarea>
                <button onclick="_vsAiCopy('vs-ai-present_address')" title="Copy"
                        style="padding:0 10px;border:none;border-left:1px solid #E2E8F0;
                               background:#F8FAFC;cursor:pointer;color:#94A3B8;font-size:.8rem;">⎘</button>
            </div>
        </div>
        <div style="margin-bottom:16px;">
            <label style="font-size:.73rem;font-weight:600;color:#64748B;display:block;margin-bottom:4px;">Permanent Address</label>
            <div style="display:flex;align-items:stretch;border:1.5px solid #E2E8F0;border-radius:7px;overflow:hidden;">
                <textarea id="vs-ai-permanent_address" rows="2"
                      style="flex:1;padding:7px 10px;border:none;font-size:.85rem;
                             outline:none;resize:none;box-sizing:border-box;">${_vsEsc(fields.permanent_address)}</textarea>
                <button onclick="_vsAiCopy('vs-ai-permanent_address')" title="Copy"
                        style="padding:0 10px;border:none;border-left:1px solid #E2E8F0;
                               background:#F8FAFC;cursor:pointer;color:#94A3B8;font-size:.8rem;">⎘</button>
            </div>
        </div>

        ${section('Travel History')}
        <div id="vs-ai-travel-history" style="margin-bottom:16px;">
            <div style="padding:12px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;
                        font-size:.82rem;color:#94A3B8;">Loading travel history…</div>
        </div>

        <button onclick="window._vsSaveToTraveler('${_vsEsc(travSysId)}')"
                style="padding:8px 20px;background:#0F172A;color:#fff;font-weight:700;
                       font-size:.85rem;border:none;border-radius:8px;cursor:pointer;">
            Save to Traveler Profile
        </button>
        <div style="font-size:.72rem;color:#94A3B8;margin-top:6px;">
            Field changes auto-save to this visa. "Save to Traveler Profile" updates the traveler record.
        </div>
    </div>`;

    // Load travel history async
    _vsLoadTravelHistory(travSysId);
};

function _vsLoadTravelHistory(travSysId) {
    const el = document.getElementById('vs-ai-travel-history');
    if (!el) return;

    // Pull directly from travelers.travel_history column (already loaded in _workTravelersLive)
    const wT = (window._vs.cfg?._workTravelersLive ?? []).find(t => t.sys_id === travSysId) ?? {};
    const history = _vsParseJson(wT.travel_history ?? 'null');

    if (!history || (Array.isArray(history) && !history.length) ||
        (typeof history === 'object' && !Array.isArray(history) && !Object.keys(history).length)) {
        el.innerHTML = `<div style="padding:12px;background:#F8FAFC;border:1px solid #E2E8F0;
            border-radius:8px;font-size:.82rem;color:#94A3B8;">
            No travel history recorded in traveler profile.
        </div>`;
        return;
    }

    // Normalize — could be array or object
    const entries = Array.isArray(history) ? history : Object.values(history);

    el.innerHTML = `<div style="border:1px solid #E2E8F0;border-radius:10px;overflow:hidden;">
        ${entries.map((h, i) => {
            const country  = h.country ?? h.country_name ?? h.destination ?? '—';
            const purpose  = h.purpose ?? h.visa_type ?? '';
            const from     = h.from ?? h.date_from ?? h.arrival ?? '';
            const to       = h.to   ?? h.date_to   ?? h.departure ?? '';
            const duration = h.duration ?? '';
            return `<div style="display:flex;align-items:center;gap:12px;padding:10px 14px;
                                border-bottom:1px solid #F1F5F9;">
                <span style="font-size:.85rem;font-weight:700;color:#0F172A;min-width:120px;">
                    ${_vsEsc(country)}
                </span>
                <span style="font-size:.78rem;color:#64748B;">
                    ${from ? _vsEsc(from) : ''}${to ? ' → ' + _vsEsc(to) : ''}
                    ${duration ? ' (' + _vsEsc(duration) + ')' : ''}
                </span>
                ${purpose ? `<span style="margin-left:auto;font-size:.72rem;font-weight:600;
                    padding:2px 8px;border-radius:999px;background:#EEF2FF;color:#4F46E5;">
                    ${_vsEsc(purpose)}</span>` : ''}
            </div>`;
        }).join('')}
    </div>`;
}

/* Field with copy button + auto-save on blur */
function _vsAiField(label, key, val, type='text') {
    const id  = 'vs-ai-' + key;
    const lbl = label.replace(/'/g, "\'");
    return `<div>
        <label style="font-size:.73rem;font-weight:600;color:#64748B;display:block;margin-bottom:4px;">${label}</label>
        <div style="display:flex;border:1.5px solid #E2E8F0;border-radius:7px;overflow:hidden;">
            <input id="${id}" type="${type}" value="${_vsEsc(val)}"
                   onblur="window._vsAiAutoSave(_currentTravSysId)"
                   style="flex:1;padding:7px 10px;border:none;font-size:.85rem;
                          outline:none;box-sizing:border-box;" />
            <button onclick="_vsAiCopy('${id}','${lbl}')" title="Copy ${label}"
                    style="padding:0 10px;border:none;border-left:1px solid #E2E8F0;
                           background:#F8FAFC;cursor:pointer;color:#94A3B8;font-size:.85rem;">📋</button>
        </div>
    </div>`;
}

/* Track current traveler for auto-save */
let _currentTravSysId = null;

/* Copy to clipboard with toast */
function _vsAiCopy(inputId, label) {
    const el = document.getElementById(inputId);
    if (!el || !el.value) return;
    navigator.clipboard.writeText(el.value).then(() => {
        const btn = el.nextElementSibling;
        if (btn) { btn.textContent = '✓'; btn.style.color = '#15803D'; }
        setTimeout(() => { if (btn) { btn.textContent = '⎘'; btn.style.color = '#94A3B8'; } }, 1500);
        _vsToast((label || 'Value') + ' copied ✓');
    });
}

/* _vsAppField replaced by _vsAiField */

/* Auto-save on blur — saves to visa app_info only */
window._vsAiAutoSave = async function (travSysId) {
    if (!travSysId) return;
    await window._vsSaveAppInfo(travSysId, true); // silent=true
};

window._vsSaveAppInfo = async function (travSysId, silent=false) {
    const fields = [
        'surname','given_name','passport_no','nationality','dob','place_of_birth',
        'country_of_birth','passport_issue','passport_expiry','issuing_authority',
        'gender','marital_status','religion','mobile','email',
        'father_name','mother_name','spouse_name',
        'occupation','employer_name','employer_address','monthly_income','education',
        'present_address','permanent_address',
    ];
    const info = {};
    fields.forEach(f => {
        const el = document.getElementById('vs-ai-' + f);
        if (el) info[f] = el.value;
    });
    const travelers = JSON.parse(JSON.stringify(window._vs.data?.vs_travelers ?? []));
    const t = travelers.find(t => t.sys_id === travSysId);
    if (t) t.app_info = info;
    const j = await window._vsApi({ action: 'update_travelers', vs_travelers: travelers });
    if (j.status !== 'success') { alert(j.message); return; }
    window._vs.data = j.data;

    if (!silent) _vsToast('Info saved ✓');
};

/* Save to Traveler Profile — updates traveler DB record with audit note */
window._vsSaveToTraveler = async function (travSysId) {
    const info = {};
    const fields = [
        'surname','given_name','passport_no','nationality','dob','place_of_birth',
        'country_of_birth','passport_issue','passport_expiry','issuing_authority',
        'gender','marital_status','religion','mobile','email',
        'father_name','mother_name','spouse_name',
        'occupation','employer_name','employer_address','monthly_income','education',
        'present_address','permanent_address',
    ];
    fields.forEach(f => {
        const el = document.getElementById('vs-ai-' + f);
        if (el) info[f] = el.value;
    });

    // First save to visa app_info
    await window._vsSaveAppInfo(travSysId, true);

    // Then push to traveler profile
    try {
        const wT = (window._vs.cfg?._workTravelersLive ?? []).find(t => t.sys_id === travSysId) ?? {};
        const addr = _vsParseJson(wT.address ?? '{}');

        const res = await fetch(window._vs.cfg.api.updateTraveler, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action:     'update_basic',
                sys_id:     travSysId,
                audit_note: `Changed via Visa ${window._vs.cfg.workSysId} on ${new Date().toLocaleDateString('en-BD')}`,
                address: JSON.stringify({ ...addr,
                    present:   info.present_address  || addr.present  || '',
                    permanent: info.permanent_address || addr.permanent || '',
                }),
                phone: JSON.stringify({ primary_no: info.mobile, secondary_no: [] }),
                email: JSON.stringify({ primary: info.email, secondary: [] }),
                personal_info: JSON.stringify({
                    gender: info.gender, marital_status: info.marital_status,
                    religion: info.religion, place_of_birth: info.place_of_birth,
                }),
                family_info: JSON.stringify({
                    father_name: info.father_name,
                    mother_name: info.mother_name,
                    spouse_name: info.spouse_name,
                }),
                employment_info: JSON.stringify({
                    jobTitle: info.occupation, employer: info.employer_name,
                    employerAddress: info.employer_address, monthlyIncome: info.monthly_income,
                }),
            }),
        });
        const j = await res.json();
        if (j.status !== 'success') throw new Error(j.message);

        // Refresh live cache
        window._vs.cfg._workTravelersLive = null;
        await window._vsGetWorkTravelers();

        _vsToast('Traveler profile updated ✓');
    } catch (e) {
        alert('Failed to update traveler profile: ' + e.message);
    }
};

/* JSON parse helpers */
function _vsParseJson(str) {
    if (!str || typeof str !== 'string') return str || {};
    try { return JSON.parse(str); } catch (e) { return {}; }
}
function _vsParsePassportBio(passportInfoStr) {
    try {
        const arr = JSON.parse(passportInfoStr || 'null');
        if (!arr) return {};
        const pages = Array.isArray(arr) ? arr : [arr];
        const bio = pages.find(p => p.page_type === 'bio_page') ?? pages[0];
        return bio?.bio_info ?? {};
    } catch (e) { return {}; }
}

/* ══════════════════════════════════════════════════════════════
   LAYER 3 — Cover Letter
══════════════════════════════════════════════════════════════ */
window._renderVsCoverLetter = function (travSysId) {
    const el = document.getElementById('vs-sub-panel-coverletter');
    if (!el) return;
    const vsT = (window._vs.data?.vs_travelers ?? []).find(t => t.sys_id === travSysId) ?? {};
    const cl  = vsT.cover_letter;
    const editorId = `vs-quill-${travSysId.replace(/[^a-z0-9]/gi,'_')}`;

    el.innerHTML = `<div style="max-width:760px;">
        ${cl ? `
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;flex-wrap:wrap;">
            <span style="font-size:.85rem;font-weight:700;color:#15803D;">✉️ Cover Letter Ready</span>
            <div style="margin-left:auto;display:flex;gap:8px;">
                <button onclick="window._vsRegenCoverLetter('${_vsEsc(travSysId)}')"
                        style="padding:6px 12px;border-radius:7px;border:1.5px solid #E2E8F0;
                               background:#fff;font-size:.78rem;font-weight:600;cursor:pointer;color:#64748B;">
                    Regenerate
                </button>
                <button onclick="window._vsCopyCoverLetter('${editorId}')"
                        style="padding:6px 12px;border-radius:7px;border:1.5px solid #E2E8F0;
                               background:#fff;font-size:.78rem;font-weight:600;cursor:pointer;color:#64748B;">
                    📋 Copy
                </button>
                <button onclick="window._vsDownloadTraveler('${_vsEsc(travSysId)}','pdf')"
                        style="padding:6px 12px;border-radius:7px;background:#EEF2FF;
                               border:1.5px solid #C7D2FE;font-size:.78rem;font-weight:600;
                               cursor:pointer;color:#4F46E5;">
                    Download PDF
                </button>
            </div>
        </div>
        <!-- Quill editor container -->
        <div style="border:1.5px solid #E2E8F0;border-radius:10px;overflow:hidden;background:#fff;">
            <div id="${editorId}" style="min-height:400px;font-size:.9rem;line-height:1.9;"></div>
        </div>
        <div style="margin-top:6px;font-size:.72rem;color:#94A3B8;">
            Rich text editor · Changes auto-save on blur
        </div>` : `
        <div style="padding:32px;text-align:center;border:2px dashed #E2E8F0;border-radius:12px;">
            <div style="font-size:2rem;margin-bottom:12px;">✉️</div>
            <div style="font-size:.9rem;font-weight:700;color:#0F172A;margin-bottom:6px;">No Cover Letter Yet</div>
            <div style="font-size:.8rem;color:#64748B;margin-bottom:20px;">
                Generate an AI-powered cover letter for ${_vsEsc(vsT.name ?? 'this traveler')}
            </div>
            <button onclick="window._vsGenCoverLetter('${_vsEsc(travSysId)}')"
                    id="vs-cl-gen-btn-${_vsEsc(travSysId)}"
                    style="padding:10px 24px;background:#4F46E5;color:#fff;font-weight:700;
                           font-size:.875rem;border:none;border-radius:9px;cursor:pointer;">
                Generate with AI
            </button>
        </div>`}
    </div>`;

    if (cl) {
        _vsInitQuill(editorId, cl.edited ?? cl.ai_draft ?? '', travSysId);
    }
};

/* Init Quill editor */
function _vsInitQuill(editorId, content, travSysId) {
    // Load Quill if not already loaded
    if (typeof Quill === 'undefined') {
        // Load Quill CSS
        if (!document.getElementById('quill-css')) {
            const link = document.createElement('link');
            link.id   = 'quill-css';
            link.rel  = 'stylesheet';
            link.href = 'https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css';
            document.head.appendChild(link);
        }
        // Load Quill JS
        const script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.min.js';
        script.onload = () => _vsInitQuillInstance(editorId, content, travSysId);
        document.head.appendChild(script);
    } else {
        _vsInitQuillInstance(editorId, content, travSysId);
    }
}

function _vsInitQuillInstance(editorId, content, travSysId) {
    const container = document.getElementById(editorId);
    if (!container) return;

    const quill = new Quill('#' + editorId, {
        theme: 'snow',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                [{ 'size': ['small', false, 'large'] }],
                [{ 'align': [] }],
                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                ['clean'],
            ]
        },
        placeholder: 'Cover letter content…',
    });

    // Set content — convert plain text newlines to proper Quill content
    if (content && !content.includes('<')) {
        // Plain text — convert newlines to paragraphs
        const lines = content.split('\n');
        const delta = lines.reduce((acc, line) => {
            acc.ops.push({ insert: line + '\n' });
            return acc;
        }, { ops: [] });
        quill.setContents(delta);
    } else {
        quill.clipboard.dangerouslyPasteHTML(content);
    }

    // Auto-save on text change (debounced)
    let saveTimer = null;
    quill.on('text-change', () => {
        clearTimeout(saveTimer);
        saveTimer = setTimeout(() => {
            const html = quill.root.innerHTML;
            window._vsSaveCoverEditHtml(travSysId, html);
        }, 1500);
    });

    // Store quill instance
    window._vsQuillInstances = window._vsQuillInstances ?? {};
    window._vsQuillInstances[editorId] = quill;
}

/* Copy cover letter text */
window._vsCopyCoverLetter = function(editorId) {
    const q = window._vsQuillInstances?.[editorId];
    const text = q ? q.getText() : '';
    if (!text.trim()) return;
    navigator.clipboard.writeText(text).then(() => _vsToast('Cover letter copied ✓'));
};

/* Save HTML from Quill */
window._vsSaveCoverEditHtml = async function(travSysId, html) {
    const travelers = JSON.parse(JSON.stringify(window._vs.data?.vs_travelers ?? []));
    const t = travelers.find(t => t.sys_id === travSysId);
    if (!t?.cover_letter) return;
    t.cover_letter.edited = html;
    const j = await window._vsApi({ action: 'update_travelers', vs_travelers: travelers });
    if (j.status === 'success') window._vs.data = j.data;
};

window._vsGenCoverLetter = async function (travSysId) {
    const btn = document.getElementById('vs-cl-gen-btn-' + travSysId);
    if (btn) { btn.textContent = 'Generating…'; btn.disabled = true; }

    const data = window._vs.data;
    const meta = data?.meta_data ?? {};
    const trav = (data?.vs_travelers ?? []).find(t => t.sys_id === travSysId) ?? {};
    const wT   = (window._vs.cfg?._workTravelersLive ?? []).find(t => t.sys_id === travSysId) ?? {};

    try {
        const res = await fetch(window._vs.cfg.api.genCoverLetter, {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                traveler_sys_id: travSysId,
                traveler_name:   trav.name ?? wT.name,
                passport_no:     trav.passport_no ?? wT.passport_no,
                profession_type: trav.profession_type ?? 'general',
                country:         meta.country_name,
                visa_type:       meta.visa_type_name,
                visa_category:   meta.visa_category_name,
                duration:        meta.duration_label,
                work_sys_id:     window._vs.cfg.workSysId,
                client_name:     window._vs.cfg.clientName,
                app_info:        trav.app_info ?? {},  // occupation, employer_name etc.
            }),
        });
        const j = await res.json();
        if (!j.draft) throw new Error(j.message ?? 'AI error');

        const travelers = JSON.parse(JSON.stringify(data.vs_travelers ?? []));
        const t2 = travelers.find(t => t.sys_id === travSysId);
        // Convert plain text newlines to <br> for contenteditable display
        const draftHtml = j.draft.replace(/\n/g, '<br>');
        if (t2) t2.cover_letter = { ai_draft: draftHtml, edited: draftHtml, generated_at: new Date().toISOString() };
        const upd = await window._vsApi({ action: 'update_travelers', vs_travelers: travelers });
        if (upd.status === 'success') {
            window._vs.data = upd.data;
            window._renderVsCoverLetter(travSysId);
        }
    } catch (e) {
        alert('Cover letter generation failed: ' + e.message);
        if (btn) { btn.textContent = 'Generate with AI'; btn.disabled = false; }
    }
};

window._vsRegenCoverLetter = function (travSysId) {
    const travelers = JSON.parse(JSON.stringify(window._vs.data?.vs_travelers ?? []));
    const t = travelers.find(t => t.sys_id === travSysId);
    if (t) t.cover_letter = null;
    window._vsApi({ action: 'update_travelers', vs_travelers: travelers }).then(j => {
        if (j.status === 'success') {
            window._vs.data = j.data;
            window._renderVsCoverLetter(travSysId);
            setTimeout(() => window._vsGenCoverLetter(travSysId), 100);
        }
    });
};

window._vsSaveCoverEdit = async function (travSysId) {
    // Legacy — now handled by Quill auto-save (_vsSaveCoverEditHtml)
};

/* ══════════════════════════════════════════════════════════════
   LAYER 3 — Export
══════════════════════════════════════════════════════════════ */
window._renderVsExport = function (travSysId) {
    const el = document.getElementById('vs-sub-panel-export');
    if (!el) return;
    const vsT      = (window._vs.data?.vs_travelers ?? []).find(t => t.sys_id === travSysId) ?? {};
    const docs     = vsT.docs ?? [];
    const uploaded = docs.filter(d => d.status === 'uploaded').length;
    const hasCL    = !!vsT.cover_letter;

    el.innerHTML = `
    <div style="max-width:520px;">
        <div style="font-size:.78rem;font-weight:700;color:#475569;text-transform:uppercase;
                    letter-spacing:.04em;margin-bottom:16px;">Export Documents</div>
        <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:14px;margin-bottom:20px;">
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;text-align:center;">
                <div>
                    <div style="font-size:1.4rem;font-weight:800;color:#4F46E5;">${uploaded}</div>
                    <div style="font-size:.72rem;color:#64748B;">Docs Uploaded</div>
                </div>
                <div>
                    <div style="font-size:1.4rem;font-weight:800;color:${hasCL?'#15803D':'#94A3B8'};">${hasCL?'✓':'—'}</div>
                    <div style="font-size:.72rem;color:#64748B;">Cover Letter</div>
                </div>
                <div>
                    <div style="font-size:1.4rem;font-weight:800;color:#0F172A;">${uploaded + (hasCL?1:0)}</div>
                    <div style="font-size:.72rem;color:#64748B;">Total Files</div>
                </div>
            </div>
        </div>
        <div style="font-size:.78rem;color:#64748B;margin-bottom:16px;padding:10px 12px;
                    background:#FAFBFF;border-left:3px solid #6366f1;border-radius:0 8px 8px 0;">
            ZIP order: Cover Letter → Passport → Photos → Bank Statement → Other Documents
        </div>
        <div style="display:flex;flex-direction:column;gap:10px;">
            <button onclick="window._vsDownloadTraveler('${_vsEsc(travSysId)}','zip')"
                    style="padding:12px 20px;background:#0F172A;color:#fff;font-weight:700;
                           font-size:.875rem;border:none;border-radius:9px;cursor:pointer;">
                Download All as ZIP
            </button>
            <button onclick="window._vsDownloadTraveler('${_vsEsc(travSysId)}','pdf')"
                    style="padding:12px 20px;background:#EEF2FF;color:#4F46E5;font-weight:700;
                           font-size:.875rem;border:1.5px solid #C7D2FE;border-radius:9px;cursor:pointer;">
                Download as PDF ${hasCL ? '(with Cover Letter)' : '(no Cover Letter)'}
            </button>
        </div>
        ${!hasCL ? `
        <div style="margin-top:12px;padding:10px 12px;background:#FEF3C7;border-radius:8px;font-size:.78rem;color:#92400E;">
            Cover letter not generated yet.
            <span onclick="window._vsSwitchTravSub('coverletter',null,'${_vsEsc(travSysId)}')"
                  style="cursor:pointer;text-decoration:underline;font-weight:600;">Generate now →</span>
        </div>` : ''}
    </div>`;
};

/* ══════════════════════════════════════════════════════════════
   LAYER 3 — Per-traveler Status
══════════════════════════════════════════════════════════════ */
window._renderVsTravStatus = function (travSysId) {
    const el = document.getElementById('vs-sub-panel-travstatus');
    if (!el) return;
    const vsT    = (window._vs.data?.vs_travelers ?? []).find(t => t.sys_id === travSysId) ?? {};
    const status = vsT.app_status ?? 'pending';
    const notes  = vsT.status_notes ?? '';

    const statuses = [
        { key:'pending',   label:'Pending'   },
        { key:'ready',     label:'Ready'     },
        { key:'submitted', label:'Submitted' },
        { key:'approved',  label:'Approved'  },
        { key:'rejected',  label:'Rejected'  },
        { key:'on_hold',   label:'On Hold'   },
    ];

    el.innerHTML = `
    <div style="max-width:480px;">
        <div style="font-size:.78rem;font-weight:700;color:#475569;text-transform:uppercase;
                    letter-spacing:.04em;margin-bottom:14px;">Individual Status</div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-bottom:18px;">
            ${statuses.map(s => {
                const active = status === s.key;
                return `<button
                    onclick="window._vsSetTravStatus('${_vsEsc(travSysId)}','${s.key}')"
                    style="padding:10px 8px;border-radius:9px;font-size:.82rem;font-weight:${active?'700':'500'};
                           cursor:pointer;border:2px solid ${active?'#4F46E5':'#E2E8F0'};
                           background:${active?'#EEF2FF':'#fff'};color:${active?'#4F46E5':'#475569'};">
                    ${s.label}
                </button>`;
            }).join('')}
        </div>
        <div style="margin-bottom:14px;">
            <label style="font-size:.75rem;font-weight:600;color:#64748B;display:block;margin-bottom:4px;">Status Notes</label>
            <textarea id="vs-ts-notes-${_vsEsc(travSysId)}" rows="3"
                      placeholder="Embassy reference, rejection reason, etc."
                      style="width:100%;padding:8px 10px;border:1.5px solid #E2E8F0;border-radius:8px;
                             font-size:.85rem;outline:none;resize:none;box-sizing:border-box;">${_vsEsc(notes)}</textarea>
        </div>
        <button onclick="window._vsSaveTravStatusNotes('${_vsEsc(travSysId)}')"
                style="padding:7px 18px;background:#4F46E5;color:#fff;font-weight:700;
                       font-size:.82rem;border:none;border-radius:8px;cursor:pointer;">
            Save Notes
        </button>

        ${(status === 'approved' || status === 'submitted') ? `
        <div style="margin-top:20px;padding:16px;background:#F8FAFC;border:1px solid #E2E8F0;
                    border-radius:10px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                <div style="font-size:.78rem;font-weight:700;color:#475569;">Approval Document</div>
                ${vsT.approval_doc?.locked_at ? (() => {
                    const lockedAt  = new Date(vsT.approval_doc.locked_at);
                    const isLocked  = lockedAt && Date.now() > lockedAt.getTime();
                    const hoursLeft = Math.max(0, Math.ceil((lockedAt - Date.now()) / 3600000));
                    return isLocked
                        ? '<span style="font-size:.7rem;background:#FEE2E2;color:#B91C1C;padding:2px 8px;border-radius:999px;font-weight:700;">🔒 Locked</span>'
                        : '<span style="font-size:.7rem;background:#FEF3C7;color:#92400E;padding:2px 8px;border-radius:999px;font-weight:700;">⏳ Locks in ' + hoursLeft + 'h</span>';
                })() : ''}
            </div>
            ${vsT.approval_doc ? `
                <div style="display:flex;align-items:center;gap:10px;padding:10px 14px;
                            background:#D1FAE5;border-radius:8px;margin-bottom:10px;">
                    <span style="font-size:1.2rem;">📄</span>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:.82rem;font-weight:700;color:#065F46;
                                    white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            ${_vsEsc(vsT.approval_doc.filename ?? 'Approval Document')}
                        </div>
                        <div style="font-size:.72rem;color:#6EE7B7;">
                            ${_vsEsc(vsT.approval_doc.uploaded_at?.split(' ')[0] ?? '')}
                        </div>
                    </div>
                    <a href="${_vsEsc(vsT.approval_doc.serve_url ?? '#')}" target="_blank"
                       style="padding:5px 10px;background:#fff;border:1px solid #6EE7B7;
                              border-radius:6px;font-size:.72rem;font-weight:700;color:#065F46;
                              text-decoration:none;flex-shrink:0;">View ↗</a>
                    ${(() => {
                        const lockedAt = vsT.approval_doc.locked_at ? new Date(vsT.approval_doc.locked_at) : null;
                        const isLocked = lockedAt && Date.now() > lockedAt.getTime();
                        const tid = _vsEsc(travSysId);
                        return !isLocked
                            ? `<button onclick="window._vsRemoveApprovalDoc('${tid}')"
                                style="padding:5px 10px;background:#FFF1F2;border:1px solid #FCA5A5;
                                       border-radius:6px;font-size:.72rem;font-weight:700;
                                       color:#EF4444;cursor:pointer;flex-shrink:0;">Remove</button>`
                            : '';
                    })()}
                </div>` : ''}
            ${(() => {
                const lockedAt = vsT.approval_doc?.locked_at ? new Date(vsT.approval_doc.locked_at) : null;
                const isLocked = lockedAt && Date.now() > lockedAt.getTime();
                const tid = _vsEsc(travSysId);
                return !isLocked
                    ? `<label style="display:flex;align-items:center;gap:8px;padding:9px 14px;
                              background:#fff;border:1.5px dashed #E2E8F0;border-radius:8px;
                              cursor:pointer;font-size:.82rem;color:#64748B;font-weight:600;">
                          <span style="font-size:1rem;">📎</span>
                          ${vsT.approval_doc ? 'Replace' : 'Upload Approval Document'}
                          <input type="file" accept=".pdf,.jpg,.jpeg,.png" style="display:none;"
                                 onchange="window._vsUploadApprovalDoc('${tid}', this)">
                       </label>`
                    : '';
            })()}
        </div>` : ''}
        ${vsT.status_history?.length ? `
        <div style="margin-top:20px;border-top:1px solid #F1F5F9;padding-top:14px;">
            <div style="font-size:.75rem;font-weight:700;color:#475569;margin-bottom:10px;">History</div>
            ${vsT.status_history.slice(-5).reverse().map(h => `
                <div style="display:flex;align-items:center;gap:8px;padding:6px 0;
                            border-bottom:1px solid #F8FAFC;font-size:.78rem;color:#64748B;">
                    ${window._vsTravStatusChip(h.status)}
                    <span style="margin-left:auto;color:#94A3B8;font-size:.72rem;">${_vsEsc(h.at??'')}</span>
                </div>`).join('')}
        </div>` : ''}
    </div>`;
};

window._vsSetTravStatus = async function (travSysId, newStatus) {
    const travelers = JSON.parse(JSON.stringify(window._vs.data?.vs_travelers ?? []));
    const t = travelers.find(t => t.sys_id === travSysId);
    if (!t) return;
    const prev = t.app_status;
    t.app_status = newStatus;
    if (!Array.isArray(t.status_history)) t.status_history = [];
    t.status_history.push({ status: newStatus, from: prev, at: new Date().toLocaleString('en-BD') });
    const j = await window._vsApi({ action: 'update_travelers', vs_travelers: travelers });
    if (j.status !== 'success') { alert(j.message); return; }
    window._vs.data = j.data;
    // Re-render ONLY travstatus sub-panel (not the whole tab)
    window._renderVsTravStatus(travSysId);
};

window._vsSaveTravStatusNotes = async function (travSysId) {
    const notes     = document.getElementById('vs-ts-notes-' + travSysId)?.value ?? '';
    const travelers = JSON.parse(JSON.stringify(window._vs.data?.vs_travelers ?? []));
    const t = travelers.find(t => t.sys_id === travSysId);
    if (t) t.status_notes = notes;
    const j = await window._vsApi({ action: 'update_travelers', vs_travelers: travelers });
    if (j.status === 'success') { window._vs.data = j.data; _vsToast('Notes saved ✓'); }
};

/* ══════════════════════════════════════════════════════════════
   Shared helpers
══════════════════════════════════════════════════════════════ */
window._vsSetProfession = async function (travSysId, profType) {
    const travelers = JSON.parse(JSON.stringify(window._vs.data?.vs_travelers ?? []));
    let t = travelers.find(t => t.sys_id === travSysId);
    const wT = (window._vs.cfg?._workTravelersLive ?? []).find(t => t.sys_id === travSysId) ?? {};
    if (!t) {
        t = { sys_id:travSysId, name:wT.name??'', passport_no:wT.passport_no??'',
              profession_type:profType, docs:[], cover_letter:null, app_status:'pending' };
        travelers.push(t);
    } else {
        t.profession_type = profType;
    }
    const j = await window._vsApi({ action:'update_travelers', vs_travelers:travelers });
    if (j.status === 'success') { window._vs.data = j.data; window._renderVsDocs(travSysId); }
};

window._vsViewDoc = function (docSysId, travSysId, fileName) {
    if (!docSysId) return alert('Document ID not found. Try syncing again.');
    if (!fileName) {
        // No filename stored — open traveler page to view doc
        window.open(`show-travelers.php?traveler_id=${encodeURIComponent(travSysId)}`, '_blank');
        return;
    }
    const url = `/api/file/serve.php?doc_id=${encodeURIComponent(docSysId)}&file=${encodeURIComponent(fileName)}`;
    window.open(url, '_blank');
};

window._vsUploadDoc = function (travSysId, docName) {
    window.open(`show-travelers.php?traveler_id=${encodeURIComponent(travSysId)}&upload_doc=${encodeURIComponent(docName)}`, '_blank');
};

window._vsReplaceDoc = function (travSysId, docName) {
    window._vsUploadDoc(travSysId, docName);
};

window._vsDownloadTraveler = function (travSysId, format) {
    const url = `${window._vs.cfg.api.genDocPackage}?trav_sys_id=${encodeURIComponent(travSysId)}&format=${format}&work_sys_id=${encodeURIComponent(window._vs.cfg.workSysId)}`;
    window.open(url, '_blank');
};

window._vsRemoveDoc = async function (travSysId, docName) {
    if (!confirm(`Remove "${docName}" from this visa checklist?`)) return;
    const travelers = JSON.parse(JSON.stringify(window._vs.data?.vs_travelers ?? []));
    const t = travelers.find(t => t.sys_id === travSysId);
    if (!t) return;
    // Remove all entries matching this base doc name (including "Doc (2)", "Doc (3)")
    t.docs = (t.docs ?? []).filter(d => {
        const base = d.doc.replace(/\s*\(\d+\)$/, '').trim();
        return base !== docName;
    });
    const j = await window._vsApi({ action: 'update_travelers', vs_travelers: travelers });
    if (j.status !== 'success') { alert(j.message); return; }
    window._vs.data = j.data;
    const openIds2 = [...document.querySelectorAll('[id^="vs-doc-acc-"]')]
        .filter(el => el.style.display !== 'none').map(el => el.id);
    window._renderVsDocs(travSysId);
    openIds2.forEach(id => { const el = document.getElementById(id); if (el) el.style.display = 'block'; });
    window._vsUpdateTravelerBadge?.();
};

/* Remove one specific page/file from a doc entry in visa checklist */
window._vsRemoveDocPage = async function (travSysId, docName, docSysId, pageIdx) {
    if (!confirm(`Remove this file from the visa checklist?`)) return;
    const travelers = JSON.parse(JSON.stringify(window._vs.data?.vs_travelers ?? []));
    const t = travelers.find(t => t.sys_id === travSysId);
    if (!t) return;

    t.docs = (t.docs ?? []).map(d => {
        const base = d.doc.replace(/\s*\(\d+\)$/, '').trim();
        if (base !== docName && d.doc !== docName) return d;
        if (docSysId && d.doc_sys_id !== docSysId) return d;
        // Remove this page from pages array
        const newPages = (d.pages ?? []).filter((_, i) => i !== pageIdx);
        if (!newPages.length) return null; // no pages left → remove whole doc entry
        return { ...d, pages: newPages, file_name: newPages[0]?.filename ?? null };
    }).filter(Boolean);

    const j = await window._vsApi({ action: 'update_travelers', vs_travelers: travelers });
    if (j.status !== 'success') { alert(j.message); return; }
    window._vs.data = j.data;
    // Re-render but preserve open accordions
    const openIds = [...document.querySelectorAll('[id^="vs-doc-acc-"]')]
        .filter(el => el.style.display !== 'none').map(el => el.id);
    window._renderVsDocs(travSysId);
    openIds.forEach(id => { const el = document.getElementById(id); if (el) el.style.display = 'block'; });
    window._vsUpdateTravelerBadge?.();
};

/* Unlink traveler from visa (and from work) */
window._vsUnlinkTraveler = async function (travSysId) {
    if (!confirm('Remove this traveler from the visa service?')) return;

    // Step 1: unlink from work
    try {
        await fetch(window._vs.cfg.api.workTravelers, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action:          'unlink',
                work_sys_id:     window._vs.cfg.workSysId,
                traveler_sys_id: travSysId,
            }),
        });
    } catch (e) { /* continue even if unlink fails */ }

    // Step 2: remove from visa vs_travelers
    const travelers = JSON.parse(JSON.stringify(window._vs.data?.vs_travelers ?? []));
    const updated = travelers.filter(t => t.sys_id !== travSysId);
    const j = await window._vsApi({ action: 'update_travelers', vs_travelers: updated });
    if (j.status !== 'success') { alert(j.message); return; }
    window._vs.data = j.data;

    // Refresh cache and rebuild
    window._vs.cfg._workTravelersLive = null;
    try {
        const fresh = await fetch(
            `${window._vs.cfg.api.workTravelers}?action=list&work_sys_id=${encodeURIComponent(window._vs.cfg.workSysId)}`
        ).then(r => r.json());
        window._vs.cfg._workTravelersLive = fresh.data ?? [];
    } catch (e) { window._vs.cfg._workTravelersLive = []; }

    window._buildTravelersShell();
    window._vsUpdateTravelerBadge?.();
};

window._vsSyncDocsForTraveler = async function (travSysId) {
    const btn = event?.target;
    if (btn) { btn.textContent = 'Syncing…'; btn.disabled = true; }
    await window._vsSyncDocs(travSysId);
    window._renderVsDocs(travSysId);
    window._vsUpdateTravelerBadge?.();
    if (btn) { btn.textContent = '🔄 Sync from Profile'; btn.disabled = false; }
};

window._vsSyncDocs = async function (singleTravSysId) {
    const data       = window._vs.data;
    const meta       = data?.meta_data;
    if (!meta) return;
    const wTravelers = window._vs.cfg?._workTravelersLive ?? [];
    const targets    = singleTravSysId ? wTravelers.filter(t => t.sys_id === singleTravSysId) : wTravelers;
    let travelers    = JSON.parse(JSON.stringify(data.vs_travelers ?? []));
    const travMap    = {};
    travelers.forEach(t => { travMap[t.sys_id] = t; });

    await Promise.all(targets.map(async wt => {
        let entry = travMap[wt.sys_id];
        if (!entry) {
            entry = { sys_id:wt.sys_id, name:wt.name??'', passport_no:wt.passport_no??'',
                      profession_type:'general', docs:[], cover_letter:null, app_status:'pending' };
            travelers.push(entry);
            travMap[wt.sys_id] = entry;
        }
        try {
            const res = await fetch(
                `${window._vs.cfg.api.listDocuments}?traveler_sys_id=${encodeURIComponent(wt.sys_id)}&include_data=0`
            );
            const j      = await res.json();
            const groups = j.groups ?? {};

            // Explicit doc name → doc_type key mapping (from traveler_documents.doc_type)
            // DOC_TYPE_MAP: doc requirement name → exact doc_type key in traveler_documents
            // Only used when master data doc_type_key is NOT set
            // Keys must match actual doc_type values in DB
            const DOC_TYPE_MAP = {
                'passport':               'passport',
                'previous passport':      'previous_passport',   // may not exist in DB
                'noc':                    'noc',                 // may not exist in DB
                'visiting card':          'visiting_card',       // may not exist in DB
                'office id card':         'employment_letter',   // stored as employment_letter with EMP-ID doc_number
                'bank statement':         'bank_statement',
                'bank solvency':          'bank_solvency',       // may not exist in DB
                'trade license':          'trade_license',
                'tin certificate':        'tin_certificate',
                'student id':             'student_id',
                'enrollment certificate': 'enrollment_certificate',
                'photo':                  'photo',
                'employment letter':      'employment_letter',
                'salary certificate':     'salary_certificate',
                'income certificate':     'income_certificate',
                'signature':              'signature',
                'medical report':         'medical_report',
            };

            // For doc types where multiple requirements share the same doc_type key,
            // use doc_number pattern to pick the right one
            const DOC_SUBTYPE_FILTER = {
                'office id card': (doc) => doc.doc_number && doc.doc_number.toUpperCase().startsWith('EMP'),
                'employment letter': (doc) => !doc.doc_number || !doc.doc_number.toUpperCase().startsWith('EMP'),
            };

            const _getMatches = (docName, docTypeKey) => {
                // Priority 1: master data doc_type_key → exact match
                // If key exists in groups, use it. If not, fall through to map.
                if (docTypeKey && groups[docTypeKey]?.length) return groups[docTypeKey];

                const norm = docName.toLowerCase().trim();

                // Priority 2: curated map → exact match
                const mappedKey = DOC_TYPE_MAP[norm];
                if (mappedKey && groups[mappedKey]?.length) {
                    // Apply subtype filter if defined (e.g. Office ID vs Employment Letter)
                    const filter = DOC_SUBTYPE_FILTER[norm];
                    if (filter) {
                        const filtered = groups[mappedKey].filter(filter);
                        if (filtered.length) return filtered;
                        return []; // filter applied but nothing matched
                    }
                    return groups[mappedKey];
                }

                // Priority 3: auto-key ("Bank Statement" → "bank_statement") → exact match
                const autoKey = norm.replace(/\s+/g,'_').replace(/[^a-z_]/g,'');
                if (groups[autoKey]?.length) return groups[autoKey];

                // No match → show as missing
                return [];
            };

            const rd = window._vsRequiredDocs(entry.profession_type);
            entry.docs = rd.flatMap((r, i) => {
                // r.doc_type_key = optional explicit key set in master data
                const matches = _getMatches(r.doc, r.doc_type_key ?? null);

                if (!matches.length) {
                    // No document found for this requirement
                    return [{ seq: r.seq ?? (i+1), doc: r.doc, required: r.required !== false,
                              doc_sys_id: null, file_name: null, status: 'missing',
                              expiry_date: null, issued_date: null, doc_number: null }];
                }

                // One entry per matched document (e.g. 3 bank statements = 3 rows)
                return matches.map((match, mi) => {
                    // Store ALL pages (for accordion UI)
                    const pages      = (match.pages ?? []).map(p => ({
                        filename: p.filename ?? null,
                        url:      p.url      ?? null,
                    }));
                    const firstPage  = pages[0];
                    const fileName   = firstPage?.filename ?? null;

                    // Age/validity info
                    const expiryDate = match.expiry_date ?? null;
                    const issuedDate = match.issue_date  ?? null;
                    const docNumber  = match.doc_number  ?? null;

                    // Compute age warning
                    let ageWarning = null;
                    if (expiryDate) {
                        const daysLeft = Math.floor((new Date(expiryDate) - Date.now()) / 86400000);
                        if (daysLeft < 0)       ageWarning = 'Expired';
                        else if (daysLeft < 30)  ageWarning = `Expires in ${daysLeft}d`;
                    } else if (issuedDate) {
                        const days = Math.floor((Date.now() - new Date(issuedDate)) / 86400000);
                        if (days > 90) ageWarning = `${days}d old`;
                    }

                    return {
                        seq:         (r.seq ?? (i+1)) + (mi > 0 ? `_${mi}` : ''),
                        doc:         mi === 0 ? r.doc : `${r.doc} (${mi+1})`,
                        required:    r.required !== false,
                        doc_sys_id:  match.sys_id ?? null,
                        file_name:   fileName,
                        pages:       pages,           // ← full pages array
                        status:      'uploaded',
                        expiry_date: expiryDate,
                        issued_date: issuedDate,
                        doc_number:  docNumber,
                        age_warning: ageWarning,
                    };
                });
            });

            // Auto-set ready if all required docs uploaded
            const reqDocs = entry.docs.filter(d => d.required !== false);
            if (reqDocs.length && reqDocs.every(d => d.status === 'uploaded')) {
                if (entry.app_status === 'pending') entry.app_status = 'ready';
            }
        } catch (e) { /* silent */ }
    }));

    const j = await window._vsApi({ action:'update_travelers', vs_travelers:travelers });
    if (j.status === 'success') window._vs.data = j.data;
};

function _vsToast(msg) {
    const t = document.createElement('div');
    t.style.cssText = 'position:fixed;bottom:24px;right:24px;background:#0F172A;color:#fff;padding:10px 18px;border-radius:9px;font-size:.82rem;font-weight:600;z-index:9999;box-shadow:0 4px 20px rgba(0,0,0,.2);';
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 2500);
}


/* ── Upload approval document ───────────────────────────────── */
window._vsUploadApprovalDoc = async function (travSysId, input) {
    const file = input.files?.[0];
    if (!file) return;

    const label = input.closest('label');
    const origText = label?.textContent?.trim() ?? '';
    if (label) label.textContent = 'Uploading…';

    const fd = new FormData();
    fd.append('work_sys_id',     window._vs.cfg.workSysId);
    fd.append('traveler_sys_id', travSysId);
    fd.append('file',            file);

    try {
        const base   = window._vs.cfg.api.visaServices ?? '';
        const apiUrl = base.replace('endpoints.php', 'upload-approval-doc.php');
        const res = await fetch(apiUrl, { method: 'POST', body: fd });
        const j   = await res.json();

        if (j.status !== 'success') {
            alert('Upload failed: ' + (j.message ?? 'Unknown error'));
            if (label) label.textContent = origText;
            return;
        }

        window._vs.data = j.data;
        window._vs.activeTraveler = travSysId; // ensure correct traveler is active
        _vsToast('Approval document uploaded ✓');
        // Force full sub-tab re-render
        const subEl = document.getElementById('vs-sub-panel-travstatus');
        if (subEl) subEl.innerHTML = '';
        window._renderVsTravStatus(travSysId);
        window._renderVsAppStatus?.();
    } catch (e) {
        alert('Upload error: ' + e.message);
        if (label) label.textContent = origText;
    }
};

/* ── Remove approval document (only within 24h) ─────────── */
window._vsRemoveApprovalDoc = async function (travSysId) {
    if (!confirm('Remove this approval document?')) return;
    const travelers = JSON.parse(JSON.stringify(window._vs.data?.vs_travelers ?? []));
    const t = travelers.find(t => t.sys_id === travSysId);
    if (!t) return;
    t.approval_doc = null;
    const j = await window._vsApi({ action: 'update_travelers', vs_travelers: travelers });
    if (j.status !== 'success') { alert(j.message); return; }
    window._vs.data = j.data;
    _vsToast('Approval document removed');
    window._renderVsTravStatus(travSysId);
    window._renderVsAppStatus?.();
};