/**
 * FILE PATH: /pages/task-tabs/ww-package/_helpers.js
 * Shared utility functions for the Package module
 */

// ── XSS-safe escape ───────────────────────────────────────────
function _pke(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

// ── Number formatting ─────────────────────────────────────────
function _pkFmt(n)  { return Number(n ?? 0).toLocaleString('en-BD'); }
function _pkFmtN(n) { return Number(n ?? 0).toLocaleString('en-BD'); }

// ── Toast notification ────────────────────────────────────────
function pkT(type, msg) {
    if (window.atT) { window.atT(type, msg); return; }
    const colors = { success:'#10b981', error:'#ef4444', info:'#6366f1', warn:'#f59e0b' };
    const t = document.createElement('div');
    t.style.cssText = `position:fixed;bottom:20px;right:20px;z-index:99999;padding:10px 16px;border-radius:10px;font-size:13px;font-weight:600;color:#fff;background:${colors[type]||colors.info};box-shadow:0 4px 16px rgba(0,0,0,.15);`;
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3500);
}

// ── Package API helper ────────────────────────────────────────
window._pkApi = async function(body) {
    const res = await fetch(window._pk.cfg.api.packageServices, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ ...body, work_sys_id: window._pk.cfg.workSysId }),
    });
    return res.json();
};

// ── Reload full package data ──────────────────────────────────
window._pkReload = async function() {
    const url = `${window._pk.cfg.api.packageServices}?action=get&work_sys_id=${encodeURIComponent(window._pk.cfg.workSysId)}`;
    const res  = await fetch(url);
    const json = await res.json();
    if (json.status === 'success') window._pk.data = json.data;
};

// ── Price calculator ──────────────────────────────────────────
function _pkCalcPricing(p) {
    const atC   = +(p.at_cost        ?? 0);
    const htC   = +(p.hotel_cost     ?? 0);
    const tsC   = +(p.transport_cost ?? 0);
    const viC   = +(p.visa_cost      ?? 0);
    const otC   = +(p.others_cost    ?? 0);
    const mkPct = +(p.markup_pct     ?? 10);
    const pax   = Math.max(1, +(p.pax ?? 1));

    const base   = atC + htC + tsC + viC + otC;
    const mkAmt  = Math.round(base * mkPct / 100);
    const gross  = base + mkAmt;
    const perPax = pax > 0 ? Math.round(gross / pax) : gross;

    return {
        at_cost:        atC,
        hotel_cost:     htC,
        transport_cost: tsC,
        visa_cost:      viC,
        others_cost:    otC,
        base_cost:      base,
        markup_pct:     mkPct,
        markup_amount:  mkAmt,
        gross_total:    gross,
        per_pax:        perPax,
        currency:       p.currency ?? 'BDT',
    };
}

// ── STT helper (Web Speech API) ────────────────────────────────
window._pkStartSTT = function(targetTextareaId) {
    if (window._pk.sttActive) {
        window._pk.sttRec?.abort();
        window._pk.sttActive = false;
        window._pk.sttFinal  = '';
        return;
    }
    const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SR) { pkT('error','Voice not supported in this browser'); return; }

    const ta = document.getElementById(targetTextareaId);
    if (!ta) return;

    const rec = new SR();
    rec.lang         = 'bn-BD';
    rec.continuous   = true;
    rec.interimResults = true;

    window._pk.sttRec    = rec;
    window._pk.sttFinal  = ta.value;
    window._pk.sttActive = true;

    rec.onresult = (e) => {
        let interim = '';
        for (let i = e.resultIndex; i < e.results.length; i++) {
            if (e.results[i].isFinal) window._pk.sttFinal += e.results[i][0].transcript;
            else interim += e.results[i][0].transcript;
        }
        ta.value = window._pk.sttFinal + interim;
        ta.style.height = 'auto';
        ta.style.height = Math.min(ta.scrollHeight, 200) + 'px';
    };

    rec.onerror = () => { window._pk.sttActive = false; };
    rec.onend   = () => {
        if (window._pk.sttActive) rec.start(); // keep going
    };
    rec.start();
};

window._pkStopSTT = function() {
    window._pk.sttRec?.abort();
    window._pk.sttActive = false;
    window._pk.sttFinal  = '';
};

// ── AI polish via lead-speech-polish ─────────────────────────
window._pkPolishText = async function(rawText, serviceType) {
    try {
        const apiBase = window._pk.cfg.api.packageServices.replace('api/package-services/endpoints.php', '');
        const res  = await fetch(`${apiBase}api/ai/lead-speech-polish.php`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ raw_text: rawText, service_type: serviceType }),
        });
        const json = await res.json();
        return json.polished_text ?? rawText;
    } catch(e) {
        return rawText;
    }
};

// ── Image lightbox ────────────────────────────────────────────
window.pkViewImg = function(url) {
    const ov = document.createElement('div');
    ov.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:99999;display:flex;align-items:center;justify-content:center;cursor:zoom-out;';
    ov.innerHTML = `<img src="${url}" style="max-width:90vw;max-height:90vh;border-radius:8px;">`;
    ov.onclick = () => ov.remove();
    document.body.appendChild(ov);
};