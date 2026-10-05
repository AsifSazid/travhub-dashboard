/**
 * FILE PATH: /pages/task-tabs/ww-hotel/_helpers.js
 * Shared utility functions for the hotel module
 */

function _hte(s) { // XSS-safe escape
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}
function _htFmt(n)  { return Number(n ?? 0).toLocaleString('en-BD'); }
function _htFmtN(n) { return Number(n ?? 0).toLocaleString('en-BD'); }

function htT(type, msg) {
    if (window.atT) { window.atT(type, msg); return; }
    const colors = { success:'#10b981', error:'#ef4444', info:'#6366f1' };
    const t = document.createElement('div');
    t.style.cssText = `position:fixed;bottom:20px;right:20px;z-index:99999;padding:10px 16px;border-radius:10px;font-size:13px;font-weight:600;color:#fff;background:${colors[type]||colors.info};box-shadow:0 4px 16px rgba(0,0,0,.15);`;
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3000);
}

// Meal plan labels
const HT_MEAL_PLANS = {
    room_only: 'Room Only',
    bb:        'Bed & Breakfast',
    hb:        'Half Board',
    fb:        'Full Board',
    ai:        'All Inclusive',
};

// Star rating display
function _htStars(n) {
    const full = Math.min(5, Math.max(0, parseInt(n) || 0));
    return full ? '★'.repeat(full) + '☆'.repeat(5 - full) : '—';
}

// Calc nights from check_in / check_out
function _htCalcNights(checkIn, checkOut) {
    if (!checkIn || !checkOut) return 0;
    const d1 = new Date(checkIn), d2 = new Date(checkOut);
    if (isNaN(d1) || isNaN(d2)) return 0;
    return Math.max(0, Math.round((d2 - d1) / 86400000));
}

// Calc sell rate from net + markup%
function _htCalcSell(net, markupPct) {
    const n = +(net || 0), m = +(markupPct || 0);
    return m > 0 ? Math.round(n * (1 + m / 100)) : n;
}

// Totals
function _htTotals(q) {
    const nights = q.nights || _htCalcNights(q.check_in, q.check_out) || 1;
    const rooms  = q.rooms  || 1;
    return {
        total_net:  Math.round((q.net_rate  || 0) * nights * rooms),
        total_sell: Math.round((q.sell_rate || 0) * nights * rooms),
    };
}

// Hotel API call helper
window._htApi = async function(body) {
    const res  = await fetch(window._ht.cfg.api.hotelServices, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ ...body, work_sys_id: window._ht.cfg.workSysId }),
    });
    return res.json();
};

// Reload full hotel data
window._htReload = async function() {
    const url = `${window._ht.cfg.api.hotelServices}?action=get&work_sys_id=${encodeURIComponent(window._ht.cfg.workSysId)}`;
    const res  = await fetch(url);
    const json = await res.json();
    if (json.status === 'success') window._ht.data = json.data;
};