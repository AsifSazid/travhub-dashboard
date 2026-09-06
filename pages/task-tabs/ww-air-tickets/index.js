/**
 * FILE PATH: /pages/task-tabs/ww-air-tickets/index.js
 * Entry point — IIFE wrapper, init, tab switching, API helpers
 * Load LAST after: _state.js, _helpers.js, gds-panel.js,
 *                  mindboard.js, quotation.js, booking.js, confirmation.js
 */

(function () {

// ── CSS (injected once) ───────────────────────────────────────
const AT_STYLES = `<style id="at-styles">
.at-tab-bar { display:flex; gap:0; border-bottom:2px solid #e5e7eb; margin-bottom:0; background:#fafafa; padding:0 8px; }
.at-tab { padding:12px 20px; font-size:.82rem; font-weight:600; color:#6b7280; border-bottom:2px solid transparent; margin-bottom:-2px; cursor:pointer; white-space:nowrap; transition:all .15s ease; background:transparent; position:relative; }
.at-tab.active { color:#4f46e5; border-bottom-color:#4f46e5; }
.at-tab:hover:not(.active) { color:#374151; background:#f3f4f6; border-radius:8px 8px 0 0; }
.at-panel { display:none; animation:atFadeIn .2s ease; }
.at-panel.active { display:block; }
.at-note-bubble { border-radius:14px; padding:10px 14px; margin-bottom:6px; word-break:break-word; position:relative; box-shadow:0 1px 3px rgba(0,0,0,.04); transition:all .15s ease; }
.at-note-bubble:hover { box-shadow:0 2px 8px rgba(0,0,0,.06); }
.at-note-text  { background:#f3f4f6; border:1px solid #e5e7eb; }
.at-note-image { background:#ffffff; border:1px solid #e5e7eb; padding:6px; border-radius:12px; }
.at-note-audio { background:#f5f3ff; border:1px solid #ede9fe; }
.at-note-video { background:#0f172a; border:1px solid #1e293b; padding:4px; border-radius:12px; }
.at-note-file  { background:#f0fdf4; border:1px solid #bbf7d0; display:flex; align-items:center; gap:10px; padding:8px 14px; border-radius:10px; }
.at-menu-wrapper { position:relative; z-index:100; flex-shrink:0; }
.at-menu-btn { color:#9ca3af; padding:4px 8px; border-radius:6px; cursor:pointer; transition:all .15s ease; background:transparent; border:none; font-size:14px; line-height:1; display:flex; align-items:center; justify-content:center; width:28px; height:28px; }
.at-menu-btn:hover { background:#f3f4f6; color:#4f46e5; }
.at-menu-dropdown { position:absolute; right:0; bottom:calc(100% + 8px); background:#ffffff; border-radius:12px; box-shadow:0 10px 40px rgba(0,0,0,.15),0 2px 8px rgba(0,0,0,.05); border:1px solid #f1f5f9; min-width:160px; padding:6px 0; z-index:99999; display:none; }
.at-menu-dropdown.show { display:block; }
.at-menu-item { display:flex; align-items:center; gap:10px; padding:8px 16px; font-size:.78rem; font-weight:500; color:#374151; cursor:pointer; transition:all .1s ease; border:none; background:none; width:100%; text-align:left; }
.at-menu-item:hover { background:#f8fafc; }
.at-menu-item.danger { color:#dc2626; }
.at-menu-item.danger:hover { background:#fef2f2; }
.at-upload-zone { border:2px dashed #d1d5db; border-radius:12px; padding:24px 20px; text-align:center; cursor:pointer; transition:all .2s ease; background:#fafafa; }
.at-upload-zone:hover, .at-upload-zone.dragover { border-color:#6366f1; background:#f5f3ff; }
.at-file-card { border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; background:#ffffff; }
.at-ai-dot { width:6px; height:6px; border-radius:50%; background:#6366f1; animation:atBounce .8s infinite; display:inline-block; }
.at-ai-dot:nth-child(2) { animation-delay:.15s; }
.at-ai-dot:nth-child(3) { animation-delay:.3s; }
.at-pill { display:inline-flex; align-items:center; padding:2px 10px; border-radius:999px; font-size:.68rem; font-weight:700; letter-spacing:0.3px; }
.at-pill-draft       { background:#f3f4f6; color:#374151; }
.at-pill-sent        { background:#dbeafe; color:#1e40af; }
.at-pill-moved       { background:#dcfce7; color:#166534; }
.at-pill-superseded  { background:#f3f4f6; color:#9ca3af; text-decoration:line-through; }
.at-pill-cancelled   { background:#fee2e2; color:#991b1b; }
.at-pill-tentative   { background:#fef9c3; color:#854d0e; }
.at-pill-confirmed   { background:#dcfce7; color:#166534; }
.at-pill-failed      { background:#fee2e2; color:#991b1b; }
.at-pill-refunded    { background:#f3e8ff; color:#6b21a8; }
.at-q-card { border:1.5px solid #e5e7eb; border-radius:10px; padding:12px 14px; cursor:pointer; transition:all .15s ease; background:#fafafa; margin-bottom:8px; }
.at-q-card:hover  { border-color:#a5b4fc; background:#ffffff; box-shadow:0 2px 8px rgba(0,0,0,.04); }
.at-q-card.active { border-color:#6366f1; background:#eef2ff; box-shadow:0 2px 12px rgba(99,102,241,.12); }
.gds-th { font-size:.72rem; font-weight:700; color:#6b7280; text-transform:uppercase; padding:6px 8px; background:#f9fafb; border-bottom:1px solid #e5e7eb; white-space:nowrap; }
.gds-td { font-size:.82rem; padding:6px 8px; border-bottom:1px solid #f3f4f6; }
.gds-input { width:100%; padding:4px 6px; border:1px solid #e5e7eb; border-radius:6px; font-size:.8rem; background:#ffffff; outline:none; transition:all .15s ease; }
.gds-input:focus { border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.1); }
.gds-input:read-only { background:#f9fafb; cursor:default; }
.at-notes-list::-webkit-scrollbar { width:4px; }
.at-notes-list::-webkit-scrollbar-thumb { background:#d1d5db; border-radius:10px; }
@keyframes atMenuPop  { from { opacity:0; transform:scale(0.92) translateY(6px); } to { opacity:1; transform:scale(1) translateY(0); } }
@keyframes atFadeIn   { from { opacity:0; transform:translateY(4px); } to { opacity:1; transform:translateY(0); } }
@keyframes atBounce   { 0%, 100% { transform:translateY(0); } 50% { transform:translateY(-4px); } }
@media (max-width:640px) { .at-tab { padding:10px 14px; font-size:.75rem; } }
</style>`;

// ── Init ──────────────────────────────────────────────────────
window.initWorkAirTicketTab = async function(config) {
    window._at.cfg    = config;
    window._at.data   = config.atData;
    window.CURRENT_USER = config.currentUser ?? window.CURRENT_USER ?? '';

    if (!document.getElementById('at-styles')) {
        document.head.insertAdjacentHTML('beforeend', AT_STYLES);
    }

    const mount = document.getElementById('at-tab-mount');
    if (!mount) return;

    mount.innerHTML = `
    <div style="display:flex;flex-direction:column;">
        <div class="at-tab-bar px-4 pt-2" id="at-inner-tab-bar" style="position:sticky;top:42px;z-index:19;background:#fafafa;flex-shrink:0;white-space:nowrap;border-top:1px solid #f1f5f9;">
            <button class="at-tab active" data-tab="mindboard"><i class="fas fa-brain mr-1.5"></i>Mind Board</button>
            <button class="at-tab" data-tab="quotation"><i class="fas fa-file-invoice mr-1.5"></i>Quotation</button>
            <button class="at-tab" data-tab="booking"><i class="fas fa-bookmark mr-1.5"></i>Booking</button>
            <button class="at-tab" data-tab="confirmation"><i class="fas fa-check-circle mr-1.5"></i>Confirmation</button>
        </div>
        <div id="at-panel-mindboard"    class="at-panel active p-5"></div>
        <div id="at-panel-quotation"    class="at-panel p-5"></div>
        <div id="at-panel-booking"      class="at-panel p-5"></div>
        <div id="at-panel-confirmation" class="at-panel p-5"></div>
    </div>`;

    mount.querySelectorAll('.at-tab').forEach(btn => {
        btn.addEventListener('click', () => _switchTab(btn.dataset.tab, btn));
    });

    if (!window._at.data) await _initRecord();

    _switchTab('mindboard', mount.querySelector('.at-tab[data-tab="mindboard"]'));
};

// ── Tab switcher ──────────────────────────────────────────────
function _switchTab(name, btn) {
    window._at.activeTab = name;
    document.querySelectorAll('.at-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.at-panel').forEach(p => { p.classList.remove('active'); p.style.cssText = ''; });
    btn?.classList.add('active');
    document.getElementById('at-panel-' + name)?.classList.add('active');
    switch (name) {
        case 'mindboard':    window._renderMindboard();    break;
        case 'quotation':    window._renderQuotation();    break;
        case 'booking':      window._renderBooking();      break;
        case 'confirmation': window._renderConfirmation(); break;
    }
}

// ── Init record ───────────────────────────────────────────────
async function _initRecord() {
    try {
        const cfg = window._at.cfg;
        const res = await fetch(cfg.api.airTickets, {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action:'init', work_sys_id:cfg.workSysId, lead_sys_id:cfg.leadSysId }),
        });
        const json = await res.json();
        if (json.status === 'success') window._at.data = { at_quotations:[], at_bookings:[], at_confirmation:null };
    } catch(e) { console.error('AT init:', e); }
}

// ── Reload ────────────────────────────────────────────────────
async function _reload() {
    const cfg = window._at.cfg;
    try {
        const res  = await fetch(`${cfg.api.airTickets}?action=get&work_sys_id=${encodeURIComponent(cfg.workSysId)}`);
        const json = await res.json();
        if (json.status === 'success' && json.data) window._at.data = json.data;
    } catch(e) { console.error('AT reload:', e); }
    try {
        const gwUrl = cfg.api.airTickets.replace('api/air-tickets/endpoints.php','api/works/get-work.php');
        const r2    = await fetch(`${gwUrl}?id=${encodeURIComponent(cfg.workSysId)}`);
        const j2    = await r2.json();
        if (j2.status === 'success') {
            if (!window._at.data) window._at.data = {};
            window._at.data.confirmed_tasks = j2.confirmed_tasks ?? [];
        }
    } catch {}
}

// ── API helper ────────────────────────────────────────────────
async function _api(body) {
    const cfg = window._at.cfg;
    const res = await fetch(cfg.api.airTickets, {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ...body, work_sys_id: cfg.workSysId }),
    });
    return await res.json();
}

// ── Expose internally used functions to tab files ─────────────
// Tab files are separate scripts but need _reload, _api, _switchTab
// We attach them to window so they're accessible
window._atReload    = _reload;
window._atApi       = _api;
window._atSwitchTab = _switchTab;

})(); // end IIFE