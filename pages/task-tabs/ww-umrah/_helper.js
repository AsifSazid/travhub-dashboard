/**
 * FILE PATH: /pages/task-tabs/ww-umrah/_helpers.js
 */
function _ume(s) { return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }
function _umFmt(n) { return Number(n??0).toLocaleString('en-BD'); }

function umT(type, msg) {
    if (window.atT) { window.atT(type, msg); return; }
    const colors = { success:'#10b981', error:'#ef4444', info:'#6366f1' };
    const t = document.createElement('div');
    t.style.cssText = `position:fixed;bottom:20px;right:20px;z-index:99999;padding:10px 16px;border-radius:10px;font-size:13px;font-weight:600;color:#fff;background:${colors[type]||colors.info};box-shadow:0 4px 16px rgba(0,0,0,.15);`;
    t.textContent = msg; document.body.appendChild(t);
    setTimeout(() => t.remove(), 3000);
}

const UM_MEAL_PLANS = { room_only:'Room Only', bb:'B&B', hb:'Half Board', fb:'Full Board', ai:'All Inclusive' };

window._umApi = async function(body) {
    const res = await fetch(window._um.cfg.api.umrahWork, {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ ...body, work_sys_id: window._um.cfg.workSysId }),
    });
    return res.json();
};

window._umReload = async function() {
    // Reload summary
    const r1  = await fetch(`${window._um.cfg.api.umrahWork}?action=summary&work_sys_id=${encodeURIComponent(window._um.cfg.workSysId)}`);
    const j1  = await r1.json();
    if (j1.status === 'success') window._um.summary = j1.data;

    // Reload group if linked
    const gSysId = window._um.summary?.group?.sys_id;
    if (gSysId) {
        const r2 = await fetch(`${window._um.cfg.api.umrahWork}?action=group&group_sys_id=${encodeURIComponent(gSysId)}`);
        const j2 = await r2.json();
        if (j2.status === 'success') window._um.group = j2.data;
    } else {
        window._um.group = null;
    }
};