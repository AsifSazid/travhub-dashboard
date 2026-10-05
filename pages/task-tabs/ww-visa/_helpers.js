/**
 * FILE PATH: /pages/task-tabs/ww-visa/_helpers.js
 * Visa Service Module — utility functions
 */

// ── API call shorthand ────────────────────────────────────
window._vsApi = async function(payload) {
    const url = window._vs.cfg.api.visaServices;
    const qs  = payload.action?.startsWith('get') || payload.action === 'search_master'
                ? `?action=${payload.action}` + (payload.work_sys_id ? `&work_sys_id=${payload.work_sys_id}` : '')
                              + (payload.sys_id ? `&sys_id=${payload.sys_id}` : '')
                : '';
    if (qs) {
        const res = await fetch(url + qs);
        return res.json();
    }
    const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ...payload, work_sys_id: window._vs.cfg.workSysId }),
    });
    return res.json();
};

// ── Reload full data row ──────────────────────────────────
window._vsReload = async function() {
    const j = await fetch(
        `${window._vs.cfg.api.visaServices}?action=get&work_sys_id=${encodeURIComponent(window._vs.cfg.workSysId)}`
    ).then(r => r.json());
    if (j.status === 'success') window._vs.data = j.data;
    return window._vs.data;
};

// ── Format currency ───────────────────────────────────────
window._vsFmt = function(n, cur) {
    if (!n && n !== 0) return '—';
    return Number(n).toLocaleString() + ' ' + (cur ?? 'BDT');
};

// ── Safe HTML escape ──────────────────────────────────────
window._vsEsc = function(str) {
    return String(str ?? '')
        .replace(/&/g,'&amp;').replace(/"/g,'&quot;')
        .replace(/</g,'&lt;').replace(/>/g,'&gt;');
};

// ── Profession label ──────────────────────────────────────
window._vsProfLabel = function(key) {
    const map = {
        general: 'General', student: 'Student',
        employment: 'Employment', businessman: 'Businessman',
        non_employment: 'Non-Employment',
    };
    return map[key] ?? key;
};

// ── Required docs for a traveler (merge general + profession-specific) ──
window._vsRequiredDocs = function(profType) {
    const rd = window._vs.data?.meta_data?.required_documents ?? {};
    const general   = Array.isArray(rd.general)    ? rd.general    : [];
    const profDocs  = Array.isArray(rd[profType])  ? rd[profType]  : [];
    // Merge, de-dup by doc name
    const seen = new Set();
    const merged = [];
    [...general, ...profDocs].forEach(d => {
        if (!seen.has(d.doc)) { seen.add(d.doc); merged.push(d); }
    });
    return merged;
};

// ── Status chip HTML ──────────────────────────────────────
window._vsStatusChip = function(status) {
    const map = {
        pending:     { bg:'#FEF3C7', c:'#92400E', label:'Pending' },
        in_progress: { bg:'#EFF6FF', c:'#1D4ED8', label:'In Progress' },
        submitted:   { bg:'#DCFCE7', c:'#15803D', label:'Submitted' },
        completed:   { bg:'#D1FAE5', c:'#065F46', label:'Completed' },
        cancelled:   { bg:'#FEE2E2', c:'#B91C1C', label:'Cancelled' },
    };
    const s = map[status] ?? { bg:'#F1F5F9', c:'#64748B', label: status };
    return `<span style="background:${s.bg};color:${s.c};font-size:.75rem;font-weight:700;
                         padding:3px 10px;border-radius:999px;">● ${s.label}</span>`;
};