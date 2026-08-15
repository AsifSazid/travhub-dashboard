/**
 * FILE PATH: /pages/task-tabs/ww-air-tickets/_helpers.js
 * Shared utility functions — available globally
 */

// ── String / HTML ─────────────────────────────────────────────
function _e(str)  { return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function _fmt(n)  { return Number(n ?? 0).toLocaleString('en-BD'); }
function _fmtN(n) { return Number(n ?? 0).toLocaleString('en-BD'); }

// ── Toast ─────────────────────────────────────────────────────
window.atT = function(type, msg) {
    if (typeof showToast === 'function') { showToast(type, msg); return; }
    console.log(`[${type}] ${msg}`);
};

// ── Date ──────────────────────────────────────────────────────
function _formatDateGDS(dateRaw) {
    if (preg_match_like(dateRaw)) {
        // already DDMMM format
        return dateRaw;
    }
    const m = String(dateRaw || '').match(/(\d{4})-(\d{2})-(\d{2})/);
    if (m) {
        const months = ['','JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'];
        return String(+m[3]).padStart(2,'0') + (months[+m[2]] ?? '');
    }
    return '';
}

function preg_match_like(s) {
    // already in DDMMM format like 21AUG
    return /^\d{2}[A-Z]{3}$/.test(String(s || ''));
}

function _parseSegFromTo(seg) {
    let from = String(seg.from || seg.from_city || '').trim().toUpperCase();
    let to   = String(seg.to   || seg.to_city   || '').trim().toUpperCase();
    if ((!from || !to) && seg.route) {
        const pts = seg.route.split(/\s*[=\-→]+\s*/);
        from = (pts[0] || '').trim().toUpperCase();
        to   = (pts[1] || '').trim().toUpperCase();
    }
    return [from, to];
}

// ── Number format ─────────────────────────────────────────────
function _gdsIsOvernight(seg) {
    return !!(seg.departure && seg.arrival && parseInt(seg.arrival, 10) < parseInt(seg.departure, 10));
}

function _gdsNextDayLabel(dateStr) {
    if (!dateStr) return '';
    const yr = new Date().getFullYear();
    const d  = new Date(Date.parse(`${dateStr.replace(/(\d+)([A-Z]+)/,'$1 $2')} ${yr}`));
    if (isNaN(d)) return '';
    d.setDate(d.getDate() + 1);
    return `${String(d.getDate()).padStart(2,'0')}${d.toLocaleString('en-US',{month:'short'}).toUpperCase()}`;
}

function _gdsTransitTime(prev, curr) {
    if (!prev || !curr) return '';
    const yr   = new Date().getFullYear();
    const parse = (ds, ts) => {
        if (!ds || !ts) return null;
        const hh = ts.substring(0,2), mm = ts.substring(2,4);
        return new Date(Date.parse(`${ds.replace(/(\d+)([A-Z]+)/,'$1 $2')} ${yr} ${hh}:${mm}`));
    };
    let arr  = parse(prev.date, prev.arrival);
    const dep  = parse(prev.date, prev.departure);
    const nextDep = parse(curr.date, curr.departure);
    if (!arr || !dep || !nextDep) return '';
    if (arr <= dep) arr = new Date(arr.getTime() + 86400000);
    let diff = nextDep - arr;
    if (diff < 0) diff += 86400000;
    const totalMin = Math.floor(diff / 60000), h = Math.floor(totalMin / 60), m = totalMin % 60;
    return h === 0 ? `${m} mins` : `${String(h).padStart(2,'0')} ${h > 1 ? 'hrs' : 'hr'} ${m} ${m > 1 ? 'mins' : 'min'}`;
}

function _gdsTo12(time) {
    if (!time) return '';
    const s = String(time).padStart(4,'0');
    const h = parseInt(s.substring(0,2)), m = s.substring(2,4);
    const ampm = h >= 12 ? 'PM' : 'AM', h12 = h > 12 ? h - 12 : h === 0 ? 12 : h;
    return `${String(h12).padStart(2,'0')}:${m} ${ampm}`;
}

function _gdsNormalizeRoute(v) {
    v = String(v || '').trim().toUpperCase();
    if (v.includes('-')) return v;
    if (v.length === 6) return v.substring(0,3) + '-' + v.substring(3);
    return v;
}