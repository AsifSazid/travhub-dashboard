<?php
// FILE PATH: /pages/generate-umrah-id-card.php
include_once('./authenticate.php');
$ip_port = @file_get_contents('../ippath.txt') ?: 'http://103.104.219.3:898';
$ip_port = rtrim($ip_port, '/') . '/';
$travelerSysId = $_GET['traveler_sys_id'] ?? '';
$groupSysId    = $_GET['group_sys_id']    ?? '';
$all           = !empty($_GET['all']);
$publicUrl     = $ip_port.'pages/umrah-group-public.php';
$logoPath      = __DIR__ . '/../assets/images/logo/round-logo.png';
$logoB64       = file_exists($logoPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath)) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Umrah ID Cards</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

/* Portrait card: ~90x127mm @ 96dpi = 340x480px */
:root {
    --cw: 340px;
    --ch: 480px;
    --blue: #3b82f6;
    --gold: #d97706;
    --dark: #1e293b;
}

/* ══ Emergency Contact Bar ══ */
.ec-bar {
    position: fixed; top: 52px; left: 0; right: 0; z-index: 99;
    background: #0f172a;
    border-bottom: 1px solid #1e293b;
    display: flex; align-items: center; gap: 10px;
    padding: 8px 20px;
    flex-wrap: wrap;
}
.ec-bar label { font-size: .7rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .4px; white-space: nowrap; }
.ec-input {
    background: #1e293b; border: 1px solid #334155; border-radius: 7px;
    color: #f1f5f9; font-size: .78rem; padding: 5px 10px;
    font-family: 'Inter', sans-serif; outline: none; width: 200px;
    transition: border-color .15s;
}
.ec-input:focus { border-color: #3b82f6; }
.ec-input::placeholder { color: #475569; }
.ec-apply {
    padding: 5px 14px; border-radius: 7px; font-size: .75rem; font-weight: 700;
    cursor: pointer; border: none; font-family: 'Inter', sans-serif;
    background: #3b82f6; color: #fff; white-space: nowrap;
    display: flex; align-items: center; gap: 5px;
}
.ec-apply:hover { background: #2563eb; }
.ec-hint { font-size: .68rem; color: #475569; }

/* ══ PRINT ══ */
@media print {
    * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    html, body { margin: 0; padding: 0; background: #fff; }
    .screen-only, .no-print { display: none !important; }
    @page { size: A4 portrait; margin: 8mm; }
    #printPages { display: block !important; }
    .print-row {
        display: flex;
        flex-direction: row;
        justify-content: center;
        gap: 6mm;
        margin-bottom: 6mm;
        page-break-inside: avoid;
    }
    .print-card {
        width: 88mm;
        height: 124mm;
        flex-shrink: 0;
        border-radius: 6px;
        overflow: hidden;
    }
}

/* ══ SCREEN ══ */
@media screen {
    body { background: #f1f5f9; font-family: 'Inter', sans-serif; }
    #printPages { display: none; }

    .topbar {
        position: fixed; top: 0; left: 0; right: 0; height: 52px;
        background: #1e293b;
        display: flex; align-items: center; justify-content: space-between;
        padding: 0 20px; z-index: 100; box-shadow: 0 2px 10px rgba(0,0,0,.3);
    }
    .topbar-title { color: #f1f5f9; font-size: .9rem; font-weight: 700; display: flex; align-items: center; gap: 8px; }
    .topbar-title i { color: var(--blue); }
    .topbar-sub { color: #64748b; font-size: .7rem; margin-top: 1px; }
    .legend { display: flex; gap: 14px; font-size: .72rem; color: #64748b; }
    .legend span { display: flex; align-items: center; gap: 4px; }
    .dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
    .tbtn {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 7px 14px; border-radius: 8px; font-size: .78rem;
        font-weight: 600; cursor: pointer; border: none;
        font-family: 'Inter', sans-serif; transition: all .15s;
    }
    main { padding-top: 108px; padding-bottom: 48px; }
    .cards-wrap { display: flex; flex-direction: column; gap: 40px; align-items: center; padding: 24px 16px; }
    .person-block { display: flex; flex-direction: column; align-items: center; gap: 10px; }
    .person-label { font-size: .75rem; color: #64748b; font-weight: 600; text-align: center; letter-spacing: .3px; text-transform: uppercase; }
    .card-pair { display: flex; gap: 16px; align-items: flex-start; }
    .card-wrap-label { text-align: center; font-size: .65rem; color: #94a3b8; font-weight: 600; margin-bottom: 4px; text-transform: uppercase; letter-spacing: .4px; }
    .per-card-actions { display: flex; gap: 8px; }
    .pac-btn { padding: 6px 14px; border-radius: 8px; font-size: .73rem; font-weight: 600; cursor: pointer; border: none; font-family: 'Inter', sans-serif; display: flex; align-items: center; gap: 5px; }
}

/* ══ CARD BASE ══ */
.id-card {
    width: var(--cw);
    height: var(--ch);
    background: #ffffff;
    border-radius: 12px;
    overflow: hidden;
    position: relative;
    font-family: 'Inter', sans-serif;
    box-shadow: 0 4px 24px rgba(0,0,0,.12);
    flex-shrink: 0;
}

/* ── Color accent: left border strip ── */
.id-card .side-strip {
    position: absolute;
    left: 0; top: 0; bottom: 0;
    width: 6px;
    z-index: 2;
}
.id-card.member .side-strip { background: linear-gradient(180deg, var(--blue), #6366f1); }
.id-card.leader .side-strip { background: linear-gradient(180deg, var(--gold), #b45309); }

/* ── Top header area ── */
.card-header {
    padding: 12px 12px 10px 16px;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    gap: 10px;
}
.logo-wrap {
    width: 44px; height: 44px; border-radius: 50%;
    overflow: hidden; flex-shrink: 0;
    border: 2px solid #e2e8f0;
    display: flex; align-items: center; justify-content: center;
    background: #f8fafc;
}
.logo-wrap img { width: 100%; height: 100%; object-fit: cover; }
.header-info { flex: 1; min-width: 0; }
.traveler-name {
    font-size: 14px; font-weight: 700; color: #0f172a; line-height: 1.2;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.id-card.leader .traveler-name { color: #0f172a; }
.type-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 9px; border-radius: 999px;
    font-size: 8px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .4px;
    margin-top: 4px;
}
.id-card.member .type-badge { background: #eff6ff; color: var(--blue); border: 1px solid #bfdbfe; }
.id-card.leader .type-badge { background: #fffbeb; color: var(--gold); border: 1px solid #fde68a; }
.header-org {
    text-align: right;
    font-size: 7px; font-weight: 600; color: #94a3b8;
    text-transform: uppercase; letter-spacing: .3px; line-height: 1.4;
    flex-shrink: 0;
}
.arabic-text {
    font-family: 'Amiri', serif; font-size: 10px; line-height: 1.2;
    text-align: right; margin-top: 2px; white-space: nowrap;
}
.id-card.member .arabic-text { color: var(--blue); }
.id-card.leader .arabic-text { color: var(--gold); }

/* Watermark */
.card-watermark {
    position: absolute; inset: 0; z-index: 0; pointer-events: none;
    display: flex; align-items: center; justify-content: center;
    overflow: hidden; opacity: .09;
}

/* ── Info rows ── */
.card-body { padding: 10px 12px 10px 16px; }
.info-row {
    display: flex; align-items: baseline; gap: 0;
    padding: 5px 0;
    border-bottom: 1px solid #f8fafc;
}
.info-row:last-child { border-bottom: none; }
.info-lbl {
    font-size: 8px; font-weight: 700; color: #94a3b8;
    text-transform: uppercase; letter-spacing: .4px;
    width: 70px; flex-shrink: 0;
}
.info-val { font-size: 10.5px; font-weight: 600; color: #1e293b; flex: 1; }
.id-card.member .info-val.accent { color: var(--blue); }
.id-card.leader .info-val.accent { color: var(--gold); }

/* ── Divider ── */
.card-divider { height: 1px; background: #f1f5f9; margin: 0 16px 0 18px; }

/* ── Date section ── */
.date-section { padding: 8px 12px 8px 16px; display: flex; gap: 20px; }
.date-item { display: flex; flex-direction: column; gap: 2px; }
.date-lbl { font-size: 7.5px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .4px; }
.id-card.member .date-val { font-size: 10px; font-weight: 700; color: var(--blue); }
.id-card.leader .date-val { font-size: 10px; font-weight: 700; color: var(--gold); }

/* ── QR section (bottom) ── */
.qr-section {
    position: absolute; bottom: 0; left: 0; right: 0;
    background: #f8fafc;
    border-top: 1px solid #f1f5f9;
    display: flex; align-items: center; gap: 10px;
    padding: 8px 12px 8px 16px;
}
/* QR logo overlay — screen only, hidden during canvas capture */
.qr-img-wrap { position: relative; flex-shrink: 0; }
.qr-img-wrap img.qr-img { width: 68px; height: 68px; border-radius: 5px; display: block; }
.qr-logo-over {
    position: absolute; top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    width: 20px; height: 20px; border-radius: 50%;
    background: #fff; border: 2px solid #fff;
    display: flex; align-items: center; justify-content: center;
    overflow: hidden;
}
.qr-logo-over img { width: 16px; height: 16px; object-fit: contain; }
.qr-info { flex: 1; }
.qr-scan-txt { font-size: 8px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .4px; }
.qr-footer-name { font-size: 9px; font-weight: 600; color: #64748b; margin-top: 4px; }
.id-card.member .qr-accent { color: var(--blue); }
.id-card.leader .qr-accent { color: var(--gold); }

/* ── Leader star ── */
.leader-badge-strip {
    display: none;
    background: #fffbeb;
    border-top: 1px solid #fde68a;
    padding: 5px 18px;
    font-size: 8px; font-weight: 700; color: var(--gold);
    text-transform: uppercase; letter-spacing: .4px;
    align-items: center; gap: 5px;
}
.id-card.leader .leader-badge-strip { display: flex; }

/* ══ BACK CARD ══ */
.back-header {
    padding: 8px 16px 6px 18px;
    border-bottom: 1px solid #f1f5f9;
    display: flex; align-items: center; justify-content: space-between;
}
.back-title { font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .4px; }
.id-card.member .back-title-accent { color: var(--blue); }
.id-card.leader .back-title-accent { color: var(--gold); }

.back-section { padding: 6px 16px 4px 18px; }
.back-sec-title {
    font-size: 8px; font-weight: 800; text-transform: uppercase; letter-spacing: .5px;
    margin-bottom: 8px; display: flex; align-items: center; gap: 5px;
}
.id-card.member .back-sec-title { color: var(--blue); }
.id-card.leader .back-sec-title { color: var(--gold); }

.hotel-item { margin-bottom: 9px; }
.hotel-name { font-size: 10px; font-weight: 700; color: #1e293b; }
.hotel-date { font-size: 8.5px; color: #64748b; font-weight: 500; margin-top: 2px; }

.itin-row {
    display: flex; gap: 6px; align-items: flex-start;
    padding: 3px 0; border-bottom: 1px solid #f8fafc;
}
.itin-row:last-child { border-bottom: none; }
.itin-date { font-size: 7px; font-weight: 700; color: #64748b; flex-shrink: 0; width: 80px; line-height: 1.3; }
.itin-title { font-size: 7.5px; font-weight: 600; color: #1e293b; flex: 1; line-height: 1.3; }
.id-card.member .itin-date { color: var(--blue); }
.id-card.leader .itin-date { color: var(--gold); }

.back-footer {
    position: absolute; bottom: 0; left: 0; right: 0;
    height: 28px;
    display: flex; align-items: center; justify-content: center;
    border-top: 1px solid #f1f5f9;
}
.back-footer-txt { font-size: 8px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .5px; }
.id-card.member .back-footer-accent { color: var(--blue); }
.id-card.leader .back-footer-accent { color: var(--gold); }
.back-separator { height: 1px; background: #f1f5f9; margin: 0 16px 0 18px; }
</style>
</head>
<body>

<!-- Topbar (screen only) -->
<div class="topbar screen-only">
    <div>
        <div class="topbar-title"><i class="fas fa-id-card"></i>Umrah ID Cards</div>
        <div class="topbar-sub" id="subtitle">Loading...</div>
    </div>
    <div style="display:flex;align-items:center;gap:16px">
        <div class="legend">
            <span><span class="dot" style="background:var(--blue)"></span>Member</span>
            <span><span class="dot" style="background:var(--gold)"></span>Leader</span>
        </div>
        <div style="display:flex;gap:8px">
            <button onclick="doPrint()" class="tbtn" style="background:#334155;color:#e2e8f0"><i class="fas fa-print"></i>Print</button>
            <button onclick="downloadAllPDF()" class="tbtn" style="background:#dc2626;color:#fff"><i class="fas fa-file-pdf"></i>PDF</button>
            <button onclick="downloadAllImages()" class="tbtn" style="background:#2563eb;color:#fff"><i class="fas fa-image"></i>Images</button>
            <a href="javascript:history.back()" class="tbtn" style="background:transparent;color:#94a3b8;border:1px solid #334155"><i class="fas fa-arrow-left"></i>Back</a>
        </div>
    </div>
</div>

<!-- Emergency Contact Bar (screen only) -->
<div class="ec-bar screen-only">
    <i class="fas fa-phone-alt" style="color:#ef4444;font-size:.8rem;flex-shrink:0"></i>
    <label>Emergency Contact</label>
    <input class="ec-input" id="ecName"  type="text" placeholder="Name (e.g. Mr. Rahman)" maxlength="60">
    <input class="ec-input" id="ecPhone" type="text" placeholder="Phone (e.g. +8801XXXXXXXX)" maxlength="30">
    <button class="ec-apply" onclick="applyEmergencyContact()"><i class="fas fa-check"></i>Apply to all cards</button>
    <span class="ec-hint">Enter and click Apply — all cards update instantly</span>
</div>

<!-- Print pages (hidden on screen) -->
<div id="printPages"></div>

<main>
<div class="cards-wrap screen-only" id="cardsWrap">
    <div style="color:#94a3b8;font-size:.85rem;padding:40px;text-align:center">
        <i class="fas fa-spinner fa-spin" style="margin-right:8px"></i>Loading...
    </div>
</div>
</main>

<script>
const API        = '<?php echo $ip_port; ?>api/umrah-groups/endpoints.php';
const PUBLIC_URL = '<?php echo $publicUrl; ?>';
const GROUP_SYS_ID = '<?php echo htmlspecialchars($groupSysId, ENT_QUOTES); ?>';
const T_SYS_ID   = '<?php echo htmlspecialchars($travelerSysId, ENT_QUOTES); ?>';
const ALL        = <?php echo $all ? 'true' : 'false'; ?>;
const LOGO       = '<?php echo $logoB64; ?>';
const { jsPDF }  = window.jspdf;

let rendered         = [];
let groupData        = null;
let emergencyContact = { name: '', phone: '' }; // set via UI bar

// Pre-loaded logo Image object (reused across all canvas draws)
let logoImgEl = null;
if (LOGO) {
    logoImgEl = new Image();
    logoImgEl.src = LOGO;
}

/* ── helpers ── */
function esc(s) { const d = document.createElement('div'); d.textContent = String(s ?? ''); return d.innerHTML; }
function san(s) { return String(s || '').replace(/[^a-zA-Z0-9_ -]/g, '').trim().replace(/\s+/g, '_'); }
function fmtDate(s) {
    if (!s) return '—';
    try { const d = new Date(s); if (!isNaN(d)) return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }); } catch (e) {}
    return s;
}

/* ── Islamic corner motif (SVG watermark) ── */
function cornerMotif(cx, cy, rot) {
    return `<g transform="rotate(${rot} ${cx} ${cy})">
      <line x1="${cx}" y1="${cy}" x2="${cx+32}" y2="${cy}" stroke="currentColor" stroke-width="1.2" opacity=".6"/>
      <line x1="${cx}" y1="${cy}" x2="${cx}" y2="${cy+32}" stroke="currentColor" stroke-width="1.2" opacity=".6"/>
      <line x1="${cx+8}" y1="${cy}" x2="${cx+8}" y2="${cy+8}" stroke="currentColor" stroke-width=".8" opacity=".35"/>
      <line x1="${cx}" y1="${cy+8}" x2="${cx+8}" y2="${cy+8}" stroke="currentColor" stroke-width=".8" opacity=".35"/>
      <polygon points="${cx+4},${cy-5} ${cx+8},${cy} ${cx+4},${cy+5} ${cx},${cy}" fill="currentColor" opacity=".55"/>
    </g>`;
}

function makeWatermark(isBack) {
    const corners = isBack
        ? [cornerMotif(326, 14, 90) + cornerMotif(14, 466, 270)]
        : [cornerMotif(14, 14, 0)   + cornerMotif(326, 466, 180)];
    // Arabic text rendered as pure SVG path shapes via foreignObject
    // so html2canvas doesn't need the Amiri font loaded inside the SVG context
    return `<svg viewBox="0 0 340 480" xmlns="http://www.w3.org/2000/svg" width="340" height="480">
  <defs>
    <style>
      @import url('https://fonts.googleapis.com/css2?family=Amiri:wght@700&display=swap');
      .wm { font-family: 'Amiri', 'Arial Unicode MS', serif; }
    </style>
  </defs>
  ${corners[0]}
  <line x1="30" y1="228" x2="155" y2="228" stroke="currentColor" stroke-width=".7" opacity=".35"/>
  <line x1="185" y1="228" x2="310" y2="228" stroke="currentColor" stroke-width=".7" opacity=".35"/>
  <polygon points="170,221 174,228 170,235 166,228" fill="currentColor" opacity=".45"/>
  <line x1="30" y1="310" x2="155" y2="310" stroke="currentColor" stroke-width=".7" opacity=".35"/>
  <line x1="185" y1="310" x2="310" y2="310" stroke="currentColor" stroke-width=".7" opacity=".35"/>
  <polygon points="170,303 174,310 170,317 166,310" fill="currentColor" opacity=".45"/>
  <text class="wm" x="170" y="275" text-anchor="middle" dominant-baseline="middle"
    font-size="62" font-weight="700" fill="currentColor"
    direction="rtl" unicode-bidi="bidi-override">
    \u0639\u064F\u0645\u0652\u0631\u064E\u0629\u064C \u0645\u064E\u0628\u064E\u0627\u0631\u064E\u0643\u064E\u0629
  </text>
</svg>`;
}

/* ── Extract leader phone from travelers list ── */
function getLeaderPhone() {
    if (!window._travelersList) return '';
    const leader = window._travelersList.find(t => t.is_leader == 1);
    return leader?.roaming_phone ?? '';
}

/* ── Get emergency contact from UI input ── */
function getEmergencyContact() {
    if (!emergencyContact.phone) return null;
    return {
        emergency_contact_name : emergencyContact.name,
        emergency_contact_phone: emergencyContact.phone,
    };
}

/* ── Apply emergency contact to all cards ── */
function applyEmergencyContact() {
    const name  = document.getElementById('ecName').value.trim();
    const phone = document.getElementById('ecPhone').value.trim();
    if (!phone) {
        document.getElementById('ecPhone').focus();
        document.getElementById('ecPhone').style.borderColor = '#ef4444';
        setTimeout(() => document.getElementById('ecPhone').style.borderColor = '', 1500);
        return;
    }
    emergencyContact = { name, phone };
    // Re-render all cards with new emergency contact
    reRenderAllCards();
}

/* ── Re-render all cards (called after emergency contact update) ── */
function reRenderAllCards() {
    const wrap = document.getElementById('cardsWrap');
    // Find all existing person-blocks and rebuild card HTML inside pair divs
    for (const r of rendered) {
        const t        = window._travelersList.find(t => t.traveler_sys_id === r.sysId);
        const isLeader = t?.is_leader == 1;
        const gName    = document.getElementById('subtitle').textContent.split(' — ')[1] ?? '';
        const qrVal    = PUBLIC_URL + '?t=' + encodeURIComponent(r.sysId) + '&g=' + encodeURIComponent(GROUP_SYS_ID);

        const frontWrap = document.querySelector('#pair_' + r.i + ' > div:first-child');
        const backWrap  = document.querySelector('#pair_' + r.i + ' > div:last-child');
        if (frontWrap) frontWrap.innerHTML = '<div class="card-wrap-label">Front</div>' + buildFront(t, gName, isLeader, qrVal);
        if (backWrap)  backWrap.innerHTML  = '<div class="card-wrap-label">Back</div>'  + buildBack(t, gName, isLeader);
    }
}

/* ── Traveler-specific flights ── */
function getTravelerFlights(travSysId) {
    const segs = groupData?.segments ?? [];
    return segs.filter(f =>
        f.legs && (f.passengers ?? []).some(p => p.traveler_sys_id === travSysId)
    );
}

function buildFlightHtml(flights, accentColor) {
    if (!flights.length) return '';
    let html = '';
    flights.forEach((f, fi) => {
        const legs = f.legs ?? [];
        const legsHtml = legs.map(l => {
            const dep = l.dep_date ? fmtDate(l.dep_date) + (l.dep_time ? ' ' + l.dep_time : '') : '—';
            const arr = l.arr_date ? fmtDate(l.arr_date) + (l.arr_time ? ' ' + l.arr_time : '') : '';
            return `<div style="font-size:8px;color:#475569;padding:1px 0;">
                <span style="font-weight:700;color:${accentColor}">${esc(l.from||'?')}→${esc(l.to||'?')}</span>
                <span style="margin-left:4px">${esc(dep)}${arr ? ' → ' + esc(arr) : ''}</span>
            </div>`;
        }).join('');
        html += `<div style="margin-bottom:${fi < flights.length - 1 ? '6' : '0'}px">
            <div style="font-size:8px;font-weight:700;color:#374151;margin-bottom:2px">
                ${esc(f.airline || 'Flight')}
                ${f.pnr ? `<span style="font-family:monospace;color:${accentColor};margin-left:4px">${esc(f.pnr)}</span>` : ''}
                <span style="font-weight:400;color:#94a3b8;margin-left:4px;text-transform:capitalize">${esc(f.class || '')}</span>
            </div>
            ${legsHtml}
        </div>`;
    });
    return html;
}

/* ── Card HTML builders ── */
function buildFront(t, gName, isLeader, qrVal) {
    const cls         = isLeader ? 'leader' : 'member';
    const qrClr       = isLeader ? 'b45309' : '3b82f6';
    const accentColor = isLeader ? '#d97706' : '#3b82f6';
    const logo        = LOGO ? '<img src="' + LOGO + '" alt="">' : '<i class="fas fa-plane" style="color:#3b82f6;font-size:1.4rem"></i>';
    const badge       = isLeader ? 'Group Leader' : 'Umrah';
    const h              = groupData?.hotels ?? {};
    const myFlights      = getTravelerFlights(t.traveler_sys_id);
    const flightHtml     = buildFlightHtml(myFlights, accentColor);
    const leaderPhone  = getLeaderPhone();
    const emergencyCtx = getEmergencyContact();

    const hotelHtml = [
        h.makkah?.name
            ? '<div class="hotel-item"><div class="hotel-name">Makkah: ' + esc(h.makkah.name) + '</div>'
              + (h.makkah.address ? '<div class="hotel-date">' + esc(h.makkah.address) + '</div>' : '')
              + (h.makkah.checkin ? '<div class="hotel-date">' + esc(h.makkah.checkin) + (h.makkah.checkout ? ' \u2192 ' + esc(h.makkah.checkout) : '') + '</div>' : '')
              + '</div>' : '',
        h.madinah?.name
            ? '<div class="hotel-item"><div class="hotel-name">Madinah: ' + esc(h.madinah.name) + '</div>'
              + (h.madinah.address ? '<div class="hotel-date">' + esc(h.madinah.address) + '</div>' : '')
              + (h.madinah.checkin ? '<div class="hotel-date">' + esc(h.madinah.checkin) + (h.madinah.checkout ? ' \u2192 ' + esc(h.madinah.checkout) : '') + '</div>' : '')
              + '</div>' : '',
    ].filter(Boolean).join('');

    // QR logo overlay only shown on screen; hidden during canvas capture
    const qrLogoHtml = LOGO
        ? `<div class="qr-logo-over"><img src="${LOGO}" alt=""></div>`
        : '';

    return '<div class="id-card ' + cls + '" id="front_' + t.traveler_sys_id + '">'
        + '<div class="side-strip"></div>'
        + '<div class="card-watermark" style="color:' + (isLeader ? '#d97706' : '#3b82f6') + '">' + makeWatermark(false) + '</div>'
        + '<div class="card-header" style="padding-left:22px">'
            + '<div class="logo-wrap">' + logo + '</div>'
            + '<div class="header-info">'
                + '<div class="traveler-name">' + esc(t.traveler_name) + '</div>'
                + '<div class="type-badge">' + badge + '</div>'
            + '</div>'
            + '<div class="header-org">'
                + '<img src="../assets/images/umrah/traveler-info.png" alt="" style="height:32px;width:auto;display:block;margin-left:auto">'
            + '</div>'
        + '</div>'
        + (isLeader ? '<div class="leader-badge-strip">Group Leader</div>' : '')
        + '<div class="card-body" style="padding-left:22px;padding-bottom:4px">'
            + '<div class="info-row"><span class="info-lbl">Passport</span><span class="info-val accent">' + esc(t.passport_no || '—') + '</span></div>'
            + '<div class="info-row"><span class="info-lbl">D.O.B</span><span class="info-val">' + esc(t.date_of_birth || '—') + '</span></div>'
            // Phone (KSA) + Leader side by side in one row
            + (t.roaming_phone || leaderPhone
                ? '<div class="info-row" style="gap:0">'
                    + (t.roaming_phone
                        ? '<span class="info-lbl">Phone (KSA)</span>'
                          + '<span class="info-val" style="font-size:9.5px">' + esc(t.roaming_phone) + '</span>'
                        : '')
                    + (leaderPhone && leaderPhone !== t.roaming_phone
                        ? '<span style="font-size:7px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px;flex-shrink:0;margin-left:' + (t.roaming_phone ? '6px' : '0') + '">Leader</span>'
                          + '<span style="font-size:9.5px;font-weight:700;color:' + accentColor + ';margin-left:3px;flex-shrink:0">' + esc(leaderPhone) + '</span>'
                        : '')
                  + '</div>'
                : '')
        + '</div>'
        + (flightHtml
            ? '<div class="card-divider"></div><div class="back-section" style="padding:5px 12px 5px 22px">'
                + '<div class="back-sec-title" style="margin-bottom:3px">&#9992; Flight</div>'
                + flightHtml
              + '</div>'
            : '')
        + (hotelHtml
            ? '<div class="card-divider"></div><div class="back-section" style="padding:5px 12px 4px 22px">'
                + '<div class="back-sec-title" style="margin-bottom:3px">&#127968; Hotels</div>'
                + hotelHtml
              + '</div>'
            : '')
        + '<div class="qr-section" style="padding-left:22px">'
            + '<div class="qr-img-wrap">'
                + '<img class="qr-img" src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&margin=4&ecc=H&color=' + qrClr + '&data=' + encodeURIComponent(qrVal) + '" alt="QR" crossorigin="anonymous">'
                + qrLogoHtml
            + '</div>'
            + '<div class="qr-info">'
                + '<div class="qr-scan-txt">Scan for full info</div>'
                + '<div class="qr-footer-name" style="margin-top:4px">' + esc(gName.length > 30 ? gName.substring(0, 28) + '\u2026' : gName) + '</div>'
            + '</div>'
        + '</div>'
    + '</div>';
}

function buildBack(t, gName, isLeader) {
    const cls          = isLeader ? 'leader' : 'member';
    const accentColor  = isLeader ? '#d97706' : '#3b82f6';
    const itin         = (groupData?.itinerary ?? []).slice(0, 16);
    const emergencyCtx = getEmergencyContact();

    const itinRows = itin.map(it => {
        const d = (it.date ? fmtDate(it.date) : '') + (it.time ? ' \u00b7 ' + it.time : '');
        return '<div class="itin-row"><span class="itin-date">' + esc(d) + '</span><span class="itin-title">' + esc(it.title || '') + '</span></div>';
    }).join('');

    const emergencyHtml = emergencyCtx
        ? '<div style="margin:0 16px 0 22px;padding:6px 0;border-top:1px solid #f1f5f9;display:flex;align-items:center;gap:6px">'
            + '<span style="font-size:7.5px;font-weight:700;color:#ef4444;text-transform:uppercase;letter-spacing:.4px;flex-shrink:0">&#9888; Emergency</span>'
            + '<span style="font-size:8.5px;font-weight:600;color:#475569;flex-shrink:0">'
                + (emergencyCtx.emergency_contact_name ? esc(emergencyCtx.emergency_contact_name) + ' · ' : '')
            + '</span>'
            + '<span style="font-size:9px;font-weight:700;color:' + accentColor + '">' + esc(emergencyCtx.emergency_contact_phone) + '</span>'
          + '</div>'
        : '';

    return '<div class="id-card ' + cls + '" id="back_' + t.traveler_sys_id + '">'
        + '<div class="side-strip"></div>'
        + '<div class="card-watermark" style="color:' + (isLeader ? '#d97706' : '#3b82f6') + '">' + makeWatermark(true) + '</div>'
        + '<div class="back-header" style="padding-left:22px">'
            + '<div class="back-title">Itinerary</div>'
            + '<div style="font-size:8px;color:#94a3b8;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:140px">' + esc(t.traveler_name) + '</div>'
        + '</div>'
        + (itinRows
            ? '<div class="back-section" style="padding-left:22px">' + itinRows + '</div>'
            : '<div style="padding:30px 22px;font-size:9px;color:#94a3b8">No itinerary added yet.</div>')
        + emergencyHtml
        + '<div class="back-footer"><span class="back-footer-txt">TravHub Global Limited</span></div>'
    + '</div>';
}

/* ── Load & render all cards ── */
async function loadAndRender() {
    try {
        const [tRes, gRes] = await Promise.all([
            fetch(API + '?action=list_travelers&group_sys_id=' + encodeURIComponent(GROUP_SYS_ID)),
            fetch(API + '?action=get&sys_id=' + encodeURIComponent(GROUP_SYS_ID)),
        ]);
        const tJson = await tRes.json();
        const gJson = await gRes.json();
        groupData   = gJson.data ?? null;
        const all   = tJson.data ?? [];
        window._travelersList = all;
        const list  = ALL ? all : all.filter(t => !T_SYS_ID || t.traveler_sys_id === T_SYS_ID);
        const gName = tJson.group_name ?? groupData?.group_name ?? '';

        document.getElementById('subtitle').textContent = list.length + ' card(s) — ' + gName;

        const sorted = [...list].sort((a, b) => (b.is_leader || 0) - (a.is_leader || 0));
        const wrap   = document.getElementById('cardsWrap');
        wrap.innerHTML = '';

        for (let i = 0; i < sorted.length; i++) {
            const t        = sorted[i];
            const isLeader = t.is_leader == 1;
            const qrVal    = PUBLIC_URL + '?t=' + encodeURIComponent(t.traveler_sys_id) + '&g=' + encodeURIComponent(GROUP_SYS_ID);

            const block = document.createElement('div');
            block.className = 'person-block';
            block.innerHTML =
                '<div class="person-label">' + esc(t.traveler_name) + (isLeader ? ' \u2605' : '') + '</div>'
                + '<div class="card-pair" id="pair_' + i + '">'
                    + '<div><div class="card-wrap-label">Front</div>' + buildFront(t, gName, isLeader, qrVal) + '</div>'
                    + '<div><div class="card-wrap-label">Back</div>'  + buildBack(t, gName, isLeader) + '</div>'
                + '</div>'
                + '<div class="per-card-actions no-print">'
                    + '<button class="pac-btn" style="background:#2563eb;color:#fff" onclick="dlImg(' + i + ',\'' + esc(t.traveler_name) + '\')"><i class="fas fa-image"></i>Image</button>'
                    + '<button class="pac-btn" style="background:#dc2626;color:#fff" onclick="dlPDF(' + i + ',\'' + esc(t.traveler_name) + '\')"><i class="fas fa-file-pdf"></i>PDF</button>'
                + '</div>';
            wrap.appendChild(block);
            rendered.push({ i, name: t.traveler_name, sysId: t.traveler_sys_id });
        }
    } catch (e) {
        document.getElementById('cardsWrap').innerHTML = '<p style="color:#ef4444;padding:40px">' + esc(e.message) + '</p>';
    }
}

/* ════════════════════════════════════════════
   CANVAS HELPERS — QR logo fix
   ════════════════════════════════════════════ */

/**
 * Wait for all web fonts (Amiri, Inter) to be ready.
 * Called once before the first capture; subsequent calls resolve instantly.
 */
let _fontsReady = null;
function waitForFonts() {
    if (_fontsReady) return _fontsReady;
    _fontsReady = document.fonts.ready.then(() => {
        // Force Arabic glyphs to be rasterized by the browser
        // before html2canvas touches the DOM
        const probe = document.createElement('span');
        probe.style.cssText = 'font-family:"Amiri",serif;font-size:14px;position:fixed;top:-200px;opacity:0';
        probe.textContent   = '\u0628\u0637\u0627\u0642\u0629 \u0645\u0639\u0644\u0648\u0645\u0627\u062a \u0627\u0644\u0645\u0633\u0627\u0641\u0631';
        document.body.appendChild(probe);
        // One rAF to let the browser paint the probe element
        return new Promise(res => requestAnimationFrame(() => {
            document.body.removeChild(probe);
            res();
        }));
    });
    return _fontsReady;
}

/**
 * Wait for a single <img> to finish loading.
 */
function waitForImg(img) {
    if (!img) return Promise.resolve();
    if (img.complete && img.naturalWidth > 0) return Promise.resolve();
    return new Promise(res => {
        img.onload  = res;
        img.onerror = res;
    });
}

/**
 * Draw the TravHub logo in the centre of the QR code onto an existing canvas.
 * Uses the pre-loaded logoImgEl so we don't reload the base64 string each time.
 */
function drawLogoOnQR(canvas, cardEl) {
    if (!LOGO || !logoImgEl) return;
    const qrImg = cardEl.querySelector('.qr-img');
    if (!qrImg) return;

    const ctx      = canvas.getContext('2d');
    const scale    = canvas.width / cardEl.offsetWidth;   // respect html2canvas scale
    const cardRect = cardEl.getBoundingClientRect();
    const qrRect   = qrImg.getBoundingClientRect();

    const cx = (qrRect.left - cardRect.left + qrRect.width  / 2) * scale;
    const cy = (qrRect.top  - cardRect.top  + qrRect.height / 2) * scale;
    const r  = 13 * scale;

    // White background circle
    ctx.beginPath();
    ctx.arc(cx, cy, r + 3, 0, Math.PI * 2);
    ctx.fillStyle = '#ffffff';
    ctx.fill();

    // Logo clipped to circle
    ctx.save();
    ctx.beginPath();
    ctx.arc(cx, cy, r, 0, Math.PI * 2);
    ctx.clip();
    ctx.drawImage(logoImgEl, cx - r, cy - r, r * 2, r * 2);
    ctx.restore();
}

/**
 * Redraw the Arabic subtitle text in the card header onto the canvas.
 * html2canvas sometimes mangles RTL Arabic glyphs even with font loaded,
 * so we erase the mangled area and redraw using the browser's Canvas2D
 * Arabic shaping engine (which is always correct).
 */
function drawArabicHeader(canvas, cardEl, accentColor) {
    const arabicEl = cardEl.querySelector('.arabic-text');
    if (!arabicEl) return;

    const ctx      = canvas.getContext('2d');
    const scale    = canvas.width / cardEl.offsetWidth;
    const cardRect = cardEl.getBoundingClientRect();
    const aRect    = arabicEl.getBoundingClientRect();

    const x = (aRect.right  - cardRect.left) * scale;   // right-aligned
    const y = (aRect.top    - cardRect.top  + aRect.height * 0.5) * scale;
    const w = aRect.width  * scale;
    const h = aRect.height * scale + 2;

    // Erase the mangled text area with white
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(x - w, y - h / 2, w + 2, h + 2);

    // Redraw correctly — Canvas2D uses OS Arabic shaping
    ctx.save();
    ctx.font         = `700 ${Math.round(10 * scale)}px Amiri, serif`;
    ctx.fillStyle    = accentColor;
    ctx.textAlign    = 'right';
    ctx.textBaseline = 'middle';
    ctx.direction    = 'rtl';
    ctx.fillText('\u0628\u0637\u0627\u0642\u0629 \u0645\u0639\u0644\u0648\u0645\u0627\u062a \u0627\u0644\u0645\u0633\u0627\u0641\u0631', x, y);
    ctx.restore();
}

/**
 * Capture the front card as a canvas with logo correctly drawn on the QR.
 */
async function getFrontCanvas(sysId, accentColor) {
    accentColor = accentColor || '#3b82f6';
    const el    = document.getElementById('front_' + sysId);
    const qrImg = el.querySelector('.qr-img');

    // 1. Wait for ALL images in the card (QR, calligraphy, logo etc.)
    const allImgs = [...el.querySelectorAll('img')];
    await Promise.all([
        waitForFonts(),
        waitForImg(logoImgEl),
        ...allImgs.map(waitForImg),
    ]);

    // 2. Hide QR CSS overlay — html2canvas cannot reliably position it
    const logoOver = el.querySelector('.qr-logo-over');
    if (logoOver) logoOver.style.visibility = 'hidden';

    // 3. Capture — allowTaint:true so local/relative images are included
    const cv = await html2canvas(el, {
        scale          : 2,
        useCORS        : true,
        allowTaint     : true,
        backgroundColor: null,
        logging        : false,
        imageTimeout   : 15000,
    });

    // 4. Restore overlay
    if (logoOver) logoOver.style.visibility = '';

    // 5. Draw QR logo manually at correct position
    drawLogoOnQR(cv, el);

    return cv;
}

/**
 * Capture the back card as a canvas.
 */
async function getBackCanvas(sysId) {
    const el = document.getElementById('back_' + sysId);
    await waitForFonts();
    return html2canvas(el, {
        scale          : 2,
        useCORS        : true,
        allowTaint     : false,
        backgroundColor: null,
        logging        : false,
    });
}

/**
 * Capture the front+back pair side-by-side (used for Image download).
 */
async function getPairCanvas(i) {
    const pairEl = document.getElementById('pair_' + i);
    // Hide all qr-logo-over in this pair during capture
    const overlays = pairEl.querySelectorAll('.qr-logo-over');
    overlays.forEach(o => o.style.visibility = 'hidden');

    const allImgs = [...pairEl.querySelectorAll('img')];
    await Promise.all([waitForFonts(), waitForImg(logoImgEl), ...allImgs.map(waitForImg)]);

    const cv = await html2canvas(pairEl, {
        scale          : 2,
        useCORS        : true,
        allowTaint     : true,
        backgroundColor: '#f1f5f9',
        logging        : false,
        imageTimeout   : 15000,
    });

    overlays.forEach(o => o.style.visibility = '');

    // Draw logo on front card within the pair canvas
    const r = rendered[i];
    if (r) {
        const frontEl = document.getElementById('front_' + r.sysId);
        if (frontEl) drawLogoOnQR(cv, frontEl);
    }

    return cv;
}

/* ════════════════════════════════════════════
   PRINT
   ════════════════════════════════════════════ */
async function doPrint() {
    // Wait for all QR images to load before printing
    const allQRs = document.querySelectorAll('.qr-img');
    await Promise.all([...allQRs].map(waitForImg));

    const pp = document.getElementById('printPages');
    pp.innerHTML = '';

    for (let i = 0; i < rendered.length; i += 2) {
        const page = document.createElement('div');
        page.style.cssText = 'page-break-after:always;break-after:page;padding:0 4mm';

        // Person 1
        const r1 = document.getElementById('front_' + rendered[i].sysId);
        const b1 = document.getElementById('back_'  + rendered[i].sysId);
        const row1 = document.createElement('div');
        row1.className = 'print-row';
        if (r1) row1.appendChild(r1.cloneNode(true));
        if (b1) row1.appendChild(b1.cloneNode(true));
        page.appendChild(row1);

        // Person 2
        if (i + 1 < rendered.length) {
            const r2 = document.getElementById('front_' + rendered[i + 1].sysId);
            const b2 = document.getElementById('back_'  + rendered[i + 1].sysId);
            const row2 = document.createElement('div');
            row2.className = 'print-row';
            if (r2) row2.appendChild(r2.cloneNode(true));
            if (b2) row2.appendChild(b2.cloneNode(true));
            page.appendChild(row2);
        }

        pp.appendChild(page);
    }

    window.print();
    setTimeout(() => { pp.innerHTML = ''; }, 3000);
}

/* ════════════════════════════════════════════
   DOWNLOAD — individual
   ════════════════════════════════════════════ */
async function dlImg(i, name) {
    const cv = await getPairCanvas(i);
    const a  = document.createElement('a');
    a.download = san(name) + '_umrah_id.png';
    a.href     = cv.toDataURL('image/png');
    a.click();
}

/* ════════════════════════════════════════════
   DOWNLOAD — individual PDF (A6, front+back)
   ════════════════════════════════════════════ */
async function dlPDF(i, name) {
    const r   = rendered[i];
    const t   = window._travelersList.find(t => t.traveler_sys_id === r.sysId);
    const ac  = t?.is_leader == 1 ? '#d97706' : '#3b82f6';
    const pdf = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a6' });

    const fCv = await getFrontCanvas(r.sysId, ac);
    pdf.addImage(fCv.toDataURL('image/png'), 'PNG', 0, 0, 105, 148);

    pdf.addPage('a6', 'portrait');
    const bCv = await getBackCanvas(r.sysId);
    pdf.addImage(bCv.toDataURL('image/png'), 'PNG', 0, 0, 105, 148);

    pdf.save(san(name) + '_umrah_id.pdf');
}

/* ════════════════════════════════════════════
   DOWNLOAD — bulk PDF (A4, 2 persons/page)
   ════════════════════════════════════════════ */
async function downloadAllPDF() {
    if (!rendered.length) return;
    const pdf   = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
    const cardW = 88, cardH = 124, marginX = 8, marginY = 8, gapX = 6, gapY = 8;

    for (let idx = 0; idx < rendered.length; idx++) {
        const r    = rendered[idx];
        if (idx > 0 && idx % 2 === 0) pdf.addPage('a4', 'portrait');
        const row  = idx % 2;
        const y    = marginY + row * (cardH + gapY);
        const trav = window._travelersList.find(t => t.traveler_sys_id === r.sysId);
        const ac   = trav?.is_leader == 1 ? '#d97706' : '#3b82f6';
        const fCv  = await getFrontCanvas(r.sysId, ac);
        const bCv  = await getBackCanvas(r.sysId);
        pdf.addImage(fCv.toDataURL('image/png'), 'PNG', marginX,              y, cardW, cardH);
        pdf.addImage(bCv.toDataURL('image/png'), 'PNG', marginX + cardW + gapX, y, cardW, cardH);
    }
    pdf.save('umrah_id_cards.pdf');
}

/* ════════════════════════════════════════════
   DOWNLOAD — images
   ════════════════════════════════════════════ */
async function downloadAllImages() {
    for (const r of rendered) {
        await dlImg(r.i, r.name);
        await new Promise(x => setTimeout(x, 400));
    }
}

/* ── Enter key on emergency contact inputs ── */
document.getElementById('ecName') .addEventListener('keydown', e => { if (e.key === 'Enter') applyEmergencyContact(); });
document.getElementById('ecPhone').addEventListener('keydown', e => { if (e.key === 'Enter') applyEmergencyContact(); });

/* ── boot ── */
loadAndRender();
</script>
</body>
</html>