<?php
// FILE PATH: /pages/generate-umrah-id-card.php
include_once('./authenticate.php');
$ip_port = @file_get_contents('../ippath.txt') ?: 'http://103.104.219.3:898';
$ip_port = rtrim($ip_port, '/') . '/';
$travelerSysId = $_GET['traveler_sys_id'] ?? '';
$groupSysId    = $_GET['group_sys_id']    ?? '';
$all           = !empty($_GET['all']);
$publicUrl     = 'https://dev.travhub.com.bd/pages/umrah-group-public.php';
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
    /* Scale A6 to fit 2 pairs per A4 portrait: each pair = 2 cards side by side */
    /* A4 usable: 194mm wide, 281mm tall */
    /* 1 pair (front+back) = 210mm wide — too wide, so scale down */
    /* We print front and back as separate cards, 2 cards per row */
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
    main { padding-top: 72px; padding-bottom: 48px; }
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
    flex-shrink: 0; white-space: nowrap;
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
    padding: 14px 16px 10px 18px;
    border-bottom: 1px solid #f1f5f9;
    display: flex; align-items: center; justify-content: space-between;
}
.back-title { font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .4px; }
.id-card.member .back-title-accent { color: var(--blue); }
.id-card.leader .back-title-accent { color: var(--gold); }

.back-section { padding: 12px 16px 8px 18px; }
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
    display: flex; gap: 10px; align-items: flex-start;
    padding: 5px 0; border-bottom: 1px solid #f8fafc;
}
.itin-row:last-child { border-bottom: none; }
.itin-date { font-size: 8px; font-weight: 700; color: #64748b; flex-shrink: 0; width: 100px; line-height: 1.3; }
.itin-title { font-size: 8.5px; font-weight: 600; color: #1e293b; flex: 1; line-height: 1.3; }
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
const API='<?php echo $ip_port; ?>api/umrah-groups/endpoints.php';
const PUBLIC_URL='<?php echo $publicUrl; ?>';
const GROUP_SYS_ID='<?php echo htmlspecialchars($groupSysId,ENT_QUOTES); ?>';
const T_SYS_ID='<?php echo htmlspecialchars($travelerSysId,ENT_QUOTES); ?>';
const ALL=<?php echo $all?'true':'false'; ?>;
const LOGO='<?php echo $logoB64; ?>';
const {jsPDF}=window.jspdf;
let rendered=[], groupData=null;

function esc(s){const d=document.createElement('div');d.textContent=String(s??'');return d.innerHTML;}
function san(s){return String(s||'').replace(/[^a-zA-Z0-9_ -]/g,'').trim().replace(/\s+/g,'_');}
function fmtDate(s){
    if(!s)return '—';
    try{const d=new Date(s);if(!isNaN(d))return d.toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'});}catch(e){}
    return s;
}

// Corner Islamic motif function — pos: 'tl','tr','bl','br'
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
    // Front: top-right corner + bottom-left corner
    // Back:  bottom-right corner + top-left corner
    const corners = isBack
        ? [ cornerMotif(326,14,90) + cornerMotif(14,466,270) ]   // back: tr rotated + bl rotated
        : [ cornerMotif(14,14,0)   + cornerMotif(326,466,180) ];  // front: tl + br

    return `<svg viewBox="0 0 340 480" xmlns="http://www.w3.org/2000/svg" width="340" height="480">
  <defs>
    <style>.wm { font-family: 'Amiri', serif; }</style>
  </defs>
  <!-- Corner motifs -->
  ${corners[0]}
  <!-- Thin decorative lines flanking text -->
  <line x1="30" y1="228" x2="155" y2="228" stroke="currentColor" stroke-width=".7" opacity=".35"/>
  <line x1="185" y1="228" x2="310" y2="228" stroke="currentColor" stroke-width=".7" opacity=".35"/>
  <polygon points="170,221 174,228 170,235 166,228" fill="currentColor" opacity=".45"/>
  <line x1="30" y1="310" x2="155" y2="310" stroke="currentColor" stroke-width=".7" opacity=".35"/>
  <line x1="185" y1="310" x2="310" y2="310" stroke="currentColor" stroke-width=".7" opacity=".35"/>
  <polygon points="170,303 174,310 170,317 166,310" fill="currentColor" opacity=".45"/>
  <!-- عُمْرَةٌ مَبَارَكَة — single line, large -->
  <text class="wm" x="170" y="275" text-anchor="middle" dominant-baseline="middle"
    font-size="62" font-weight="700" fill="currentColor">
    \u0639\u064F\u0645\u0652\u0631\u064E\u0629\u064C \u0645\u064E\u0628\u064E\u0627\u0631\u064E\u0643\u064E\u0629
  </text>
</svg>`;
}

function buildFront(t, gName, isLeader, qrVal){
    const cls=isLeader?'leader':'member';
    const qrClr=isLeader?'b45309':'3b82f6';
    const logo=LOGO?'<img src="'+LOGO+'" alt="">':'<i class="fas fa-plane" style="color:#3b82f6;font-size:1.4rem"></i>';
    const badge=isLeader?'Group Leader':'Umrah';
    const segs=groupData?.segments??[];
    const arrival=segs.length?fmtDate(segs[0]?.arrival_date):'—';
    const ret=segs.length?fmtDate(segs[segs.length-1]?.departure_date):'—';
    const h=groupData?.hotels??{};

    const hotelHtml=[
        h.makkah?.name?'<div class="hotel-item"><div class="hotel-name">Makkah: '+esc(h.makkah.name)+'</div>'+(h.makkah.address?'<div class="hotel-date">'+esc(h.makkah.address)+'</div>':'')+(h.makkah.checkin?'<div class="hotel-date">'+esc(h.makkah.checkin)+(h.makkah.checkout?' \u2192 '+esc(h.makkah.checkout):'')+'</div>':'')+'</div>':'',
        h.madinah?.name?'<div class="hotel-item"><div class="hotel-name">Madinah: '+esc(h.madinah.name)+'</div>'+(h.madinah.address?'<div class="hotel-date">'+esc(h.madinah.address)+'</div>':'')+(h.madinah.checkin?'<div class="hotel-date">'+esc(h.madinah.checkin)+(h.madinah.checkout?' \u2192 '+esc(h.madinah.checkout):'')+'</div>':'')+'</div>':'',
    ].filter(Boolean).join('');

    return '<div class="id-card '+cls+'" id="front_'+t.traveler_sys_id+'">'
        +'<div class="side-strip"></div>'
        // Watermark (front = tl+br corners)
        +'<div class="card-watermark" style="color:'+(isLeader?'#d97706':'#3b82f6')+'">'+makeWatermark(false)+'</div>'
        // Header
        +'<div class="card-header" style="padding-left:22px">'
            +'<div class="logo-wrap">'+logo+'</div>'
            +'<div class="header-info">'
                +'<div class="traveler-name">'+esc(t.traveler_name)+'</div>'
                +'<div class="type-badge">'+badge+'</div>'
            +'</div>'
            +'<div class="header-org">'
                +'TravHub Global Ltd.'
                +'<div class="arabic-text">\u0628\u0637\u0627\u0642\u0629 \u0627\u0644\u0639\u0645\u0631\u0629</div>'
            +'</div>'
        +'</div>'
        +(isLeader?'<div class="leader-badge-strip">Group Leader</div>':'')
        +'<div class="card-body" style="padding-left:22px">'
            +'<div class="info-row"><span class="info-lbl">Passport</span><span class="info-val">'+esc(t.passport_no||'—')+'</span></div>'
            +'<div class="info-row"><span class="info-lbl">Date of Birth</span><span class="info-val">'+esc(t.date_of_birth||'—')+'</span></div>'
            +(t.roaming_phone?'<div class="info-row"><span class="info-lbl">Phone (KSA)</span><span class="info-val">'+esc(t.roaming_phone)+'</span></div>':'')
        +'</div>'
        +'<div class="card-divider"></div>'
        // Arrival / Return dates
        +'<div class="date-section" style="padding-left:22px">'
            +'<div class="date-item"><div class="date-lbl">Arrival to KSA</div><div class="date-val">'+esc(arrival)+'</div></div>'
            +'<div class="date-item"><div class="date-lbl">Return</div><div class="date-val">'+esc(ret)+'</div></div>'
        +'</div>'
        // Accommodation — no icons
        +(hotelHtml?'<div class="card-divider"></div><div class="back-section" style="padding:8px 12px 6px 22px"><div class="back-sec-title" style="margin-bottom:5px">Accommodation</div>'+hotelHtml+'</div>':'')
        // QR at bottom
        +'<div class="qr-section" style="padding-left:22px">'
            +'<div class="qr-img-wrap">'
                +'<img class="qr-img" src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&margin=4&ecc=H&color='+qrClr+'&data='+encodeURIComponent(qrVal)+'" alt="QR">'
                +(LOGO?'<div class="qr-logo-over"><img src="'+LOGO+'" alt=""></div>':'')
            +'</div>'
            +'<div class="qr-info">'
                +'<div class="qr-scan-txt">Scan for full info</div>'
                +'<div class="qr-footer-name" style="margin-top:4px">'+esc(gName.length>30?gName.substring(0,28)+'\u2026':gName)+'</div>'
            +'</div>'
        +'</div>'
    +'</div>';
}

function buildBack(t, gName, isLeader){
    const cls=isLeader?'leader':'member';
    const itin=(groupData?.itinerary??[]).slice(0,10);

    const itinRows=itin.map(it=>{
        const d=(it.date?fmtDate(it.date):'')+(it.time?' \u00b7 '+it.time:'');
        return '<div class="itin-row"><span class="itin-date">'+esc(d)+'</span><span class="itin-title">'+esc(it.title||'')+'</span></div>';
    }).join('');

    return '<div class="id-card '+cls+'" id="back_'+t.traveler_sys_id+'">'
        +'<div class="side-strip"></div>'
        // Watermark (back = tr+bl corners)
        +'<div class="card-watermark" style="color:'+(isLeader?'#d97706':'#3b82f6')+'">'+makeWatermark(true)+'</div>'
        +'<div class="back-header" style="padding-left:22px">'
            +'<div class="back-title">Itinerary</div>'
            +'<div style="font-size:8px;color:#94a3b8;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:140px">'+esc(t.traveler_name)+'</div>'
        +'</div>'
        +(itinRows
            ?'<div class="back-section" style="padding-left:22px">'+itinRows+'</div>'
            :'<div style="padding:30px 22px;font-size:9px;color:#94a3b8">No itinerary added yet.</div>'
        )
        +'<div class="back-footer"><span class="back-footer-txt">TravHub Global Limited</span></div>'
    +'</div>';
}

async function loadAndRender(){
    try{
        const [tRes,gRes]=await Promise.all([
            fetch(API+'?action=list_travelers&group_sys_id='+encodeURIComponent(GROUP_SYS_ID)),
            fetch(API+'?action=get&sys_id='+encodeURIComponent(GROUP_SYS_ID)),
        ]);
        const tJson=await tRes.json();
        const gJson=await gRes.json();
        groupData=gJson.data??null;
        const all=tJson.data??[];
        const list=ALL?all:all.filter(t=>!T_SYS_ID||t.traveler_sys_id===T_SYS_ID);
        const gName=tJson.group_name??groupData?.group_name??'';
        document.getElementById('subtitle').textContent=list.length+' card(s) \u2014 '+gName;
        const sorted=[...list].sort((a,b)=>(b.is_leader||0)-(a.is_leader||0));
        const wrap=document.getElementById('cardsWrap');
        wrap.innerHTML='';
        for(let i=0;i<sorted.length;i++){
            const t=sorted[i];
            const isLeader=t.is_leader==1;
            const qrVal=PUBLIC_URL+'?t='+encodeURIComponent(t.traveler_sys_id)+'&g='+encodeURIComponent(GROUP_SYS_ID);
            const block=document.createElement('div');
            block.className='person-block';
            block.innerHTML=
                '<div class="person-label">'+esc(t.traveler_name)+(isLeader?' \u2605':'')+'</div>'
                +'<div class="card-pair" id="pair_'+i+'">'
                    +'<div><div class="card-wrap-label">Front</div>'+buildFront(t,gName,isLeader,qrVal)+'</div>'
                    +'<div><div class="card-wrap-label">Back</div>'+buildBack(t,gName,isLeader)+'</div>'
                +'</div>'
                +'<div class="per-card-actions no-print">'
                    +'<button class="pac-btn" style="background:#2563eb;color:#fff" onclick="dlImg('+i+',\''+esc(t.traveler_name)+'\')"><i class="fas fa-image"></i>Image</button>'
                    +'<button class="pac-btn" style="background:#dc2626;color:#fff" onclick="dlPDF('+i+',\''+esc(t.traveler_name)+'\')"><i class="fas fa-file-pdf"></i>PDF</button>'
                +'</div>';
            wrap.appendChild(block);
            rendered.push({i,name:t.traveler_name,sysId:t.traveler_sys_id});
        }
    }catch(e){
        document.getElementById('cardsWrap').innerHTML='<p style="color:#ef4444;padding:40px">'+esc(e.message)+'</p>';
    }
}

// ── Print: 2 persons per A4 page, each row = front + back side by side ──
function doPrint(){
    const pp=document.getElementById('printPages');
    pp.innerHTML='';
    // Each print-row = front of person A + back of person A
    // We stack 2 persons per page
    for(let i=0;i<rendered.length;i+=2){
        const page=document.createElement('div');
        page.style.cssText='page-break-after:always;break-after:page;padding:0 4mm';
        // Person 1
        const r1=document.getElementById('front_'+rendered[i].sysId);
        const b1=document.getElementById('back_' +rendered[i].sysId);
        const row1=document.createElement('div');
        row1.className='print-row';
        if(r1)row1.appendChild(r1.cloneNode(true));
        if(b1)row1.appendChild(b1.cloneNode(true));
        page.appendChild(row1);
        // Person 2
        if(i+1<rendered.length){
            const r2=document.getElementById('front_'+rendered[i+1].sysId);
            const b2=document.getElementById('back_' +rendered[i+1].sysId);
            const row2=document.createElement('div');
            row2.className='print-row';
            if(r2)row2.appendChild(r2.cloneNode(true));
            if(b2)row2.appendChild(b2.cloneNode(true));
            page.appendChild(row2);
        }
        pp.appendChild(page);
    }
    window.print();
    setTimeout(()=>{pp.innerHTML='';},3000);
}

// ── Download ──────────────────────────────────────────────────
async function getPairCanvas(i){
    return html2canvas(document.getElementById('pair_'+i),{scale:2,useCORS:true,allowTaint:true,backgroundColor:'#f1f5f9',logging:false});
}
async function getFrontCanvas(sysId){
    return html2canvas(document.getElementById('front_'+sysId),{scale:2,useCORS:true,allowTaint:true,backgroundColor:null,logging:false});
}
async function getBackCanvas(sysId){
    return html2canvas(document.getElementById('back_'+sysId),{scale:2,useCORS:true,allowTaint:true,backgroundColor:null,logging:false});
}
async function dlImg(i,name){
    const cv=await getPairCanvas(i);
    const a=document.createElement('a');
    a.download=san(name)+'_umrah_id.png';
    a.href=cv.toDataURL('image/png');
    a.click();
}
async function downloadAllImages(){
    for(const r of rendered){await dlImg(r.i,r.name);await new Promise(x=>setTimeout(x,400));}
}
async function dlPDF(i,name){
    const r=rendered[i];
    // A6 portrait per card, 2 pages (front + back)
    const pdf=new jsPDF({orientation:'portrait',unit:'mm',format:'a6'});
    const fCv=await getFrontCanvas(r.sysId);
    pdf.addImage(fCv.toDataURL('image/png'),'PNG',0,0,105,148);
    pdf.addPage('a6','portrait');
    const bCv=await getBackCanvas(r.sysId);
    pdf.addImage(bCv.toDataURL('image/png'),'PNG',0,0,105,148);
    pdf.save(san(name)+'_umrah_id.pdf');
}
async function downloadAllPDF(){
    if(!rendered.length)return;
    // A4 portrait: 2 persons per page
    // Each person = front (left) + back (right) side by side
    // 2 persons stacked = 2 rows per A4
    const pdf=new jsPDF({orientation:'portrait',unit:'mm',format:'a4'});
    const cardW=88,cardH=124,marginX=8,marginY=8,gapX=6,gapY=8;
    for(let idx=0;idx<rendered.length;idx++){
        const r=rendered[idx];
        if(idx>0&&idx%2===0)pdf.addPage('a4','portrait');
        const row=idx%2;
        const y=marginY+row*(cardH+gapY);
        const fCv=await getFrontCanvas(r.sysId);
        const bCv=await getBackCanvas(r.sysId);
        pdf.addImage(fCv.toDataURL('image/png'),'PNG',marginX,y,cardW,cardH);
        pdf.addImage(bCv.toDataURL('image/png'),'PNG',marginX+cardW+gapX,y,cardW,cardH);
    }
    pdf.save('umrah_id_cards.pdf');
}

loadAndRender();
</script>
</body>
</html>