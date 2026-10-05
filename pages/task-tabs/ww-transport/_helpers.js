/**
 * FILE PATH: /pages/task-tabs/ww-transport/_helpers.js
 */

function _tse(s) { return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }
function _tsFmt(n) { return Number(n??0).toLocaleString('en-BD'); }

function tsT(type, msg) {
    if (window.atT) { window.atT(type, msg); return; }
    const colors = { success:'#10b981', error:'#ef4444', info:'#6366f1' };
    const t = document.createElement('div');
    t.style.cssText = `position:fixed;bottom:20px;right:20px;z-index:99999;padding:10px 16px;border-radius:10px;font-size:13px;font-weight:600;color:#fff;background:${colors[type]||colors.info};box-shadow:0 4px 16px rgba(0,0,0,.15);`;
    t.textContent = msg; document.body.appendChild(t);
    setTimeout(() => t.remove(), 3000);
}

const TS_VEHICLE_CLASSES = {
    sedan:   'Sedan / Car',
    suv:     'SUV',
    van:     'Van',
    minibus: 'Microbus / Minibus',
    coach:   'Coach / Bus',
    other:   'Other',
};

const TS_PRICE_BASIS = {
    per_vehicle: 'Per Vehicle',
    per_person:  'Per Person',
    per_day:     'Per Day',
};

window._tsApi = async function(body) {
    const res = await fetch(window._ts.cfg.api.transportServices, {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ...body, work_sys_id: window._ts.cfg.workSysId }),
    });
    return res.json();
};

window._tsReload = async function() {
    const url  = `${window._ts.cfg.api.transportServices}?action=get&work_sys_id=${encodeURIComponent(window._ts.cfg.workSysId)}`;
    const res  = await fetch(url);
    const json = await res.json();
    if (json.status === 'success') window._ts.data = json.data;
};