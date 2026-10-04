<?php
// FILE PATH: /pages/umrah-group-public.php  (OR root: /umrah-group-public.php)
// PUBLIC page — no login required — shown when QR code is scanned
// URL: /umrah-group-public.php?t={traveler_sys_id}&g={group_sys_id}

// No authenticate.php here — public access
$ip_port = @file_get_contents('../ippath.txt') ?: 'http://103.104.219.3:898';
$ip_port = rtrim($ip_port, '/') . '/';

$travelerSysId = $_GET['t'] ?? '';
$groupSysId    = $_GET['g'] ?? '';

if (!$travelerSysId || !$groupSysId) {
    http_response_code(404);
    echo '<p style="font-family:sans-serif;padding:60px;text-align:center;color:#666">Invalid QR code.</p>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Umrah Group Info</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
body{font-family:'Inter',sans-serif;background:#f8f6ff;min-height:100vh}
.hero{background:linear-gradient(135deg,#1a0a2e 0%,#2d1654 60%,#1a0a2e 100%);padding:32px 20px;text-align:center;position:relative;overflow:hidden}
.hero::before{content:'';position:absolute;inset:0;background-image:repeating-linear-gradient(45deg,rgba(255,255,255,.02) 0,rgba(255,255,255,.02) 1px,transparent 1px,transparent 16px),repeating-linear-gradient(-45deg,rgba(255,255,255,.02) 0,rgba(255,255,255,.02) 1px,transparent 1px,transparent 16px)}
.gold{color:#c9a227}
.card{background:#fff;border-radius:16px;padding:20px;margin:0 16px 16px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid #f0eef8}
.info-row{display:flex;align-items:flex-start;gap:10px;padding:8px 0;border-bottom:1px solid #f9f8ff}
.info-row:last-child{border-bottom:none}
.info-label{font-size:.72rem;font-weight:600;color:#8b7fc7;width:110px;flex-shrink:0;text-transform:uppercase;letter-spacing:.3px;padding-top:2px}
.info-value{font-size:.88rem;color:#1e1b4b;font-weight:500}
.section-title{font-size:.72rem;font-weight:700;color:#7c3aed;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;display:flex;align-items:center;gap-6px}
.seg-item{background:#f5f3ff;border-radius:10px;padding:12px 14px;margin-bottom:8px}
.itin-dot{width:8px;height:8px;border-radius:50%;background:#7c3aed;margin-top:5px;flex-shrink:0}
.leader-card{display:flex;align-items:center;gap:10px;padding:10px;background:#fffbeb;border:1px solid #fde68a;border-radius:10px;margin-bottom:8px}
</style>
</head>
<body>

<!-- Hero -->
<div class="hero">
    <div style="font-family:'Amiri',serif;font-size:1.1rem;color:rgba(201,162,39,.7);margin-bottom:8px">بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيم</div>
    <div style="font-size:2rem;margin-bottom:6px">🕋</div>
    <h1 style="font-family:'Amiri',serif;font-size:1.5rem;color:#fff;font-weight:700" id="groupName">Loading…</h1>
    <p style="font-size:.8rem;color:rgba(255,255,255,.5);margin-top:4px">Umrah Group Information</p>
</div>

<!-- Traveler info -->
<div style="padding:16px 0 0">

    <!-- Traveler card -->
    <div class="card">
        <p class="section-title"><i class="fas fa-user" style="color:#7c3aed;margin-right:6px"></i>Traveler</p>
        <div id="travelerInfo"><div class="text-center text-gray-400 text-sm py-4"><i class="fas fa-spinner fa-spin"></i></div></div>
    </div>

    <!-- Documents -->
    <div class="card">
        <p class="section-title"><i class="fas fa-file-alt" style="color:#7c3aed;margin-right:6px"></i>Documents</p>
        <div id="docsInfo"></div>
    </div>

    <!-- Group Leaders -->
    <div class="card" id="leadersCard" style="display:none">
        <p class="section-title"><i class="fas fa-star" style="color:#d97706;margin-right:6px"></i>Group Leaders</p>
        <div id="leadersInfo"></div>
    </div>

    <!-- Flights -->
    <div class="card" id="flightsCard" style="display:none">
        <p class="section-title"><i class="fas fa-plane" style="color:#7c3aed;margin-right:6px"></i>Flight Segments</p>
        <div id="flightsInfo"></div>
    </div>

    <!-- Hotels -->
    <div class="card" id="hotelsCard" style="display:none">
        <p class="section-title"><i class="fas fa-hotel" style="color:#7c3aed;margin-right:6px"></i>Hotels</p>
        <div id="hotelsInfo"></div>
    </div>

    <!-- Itinerary -->
    <div class="card" id="itinCard" style="display:none">
        <p class="section-title"><i class="fas fa-list-alt" style="color:#7c3aed;margin-right:6px"></i>Itinerary</p>
        <div id="itinInfo"></div>
    </div>

    <p style="text-align:center;font-size:.7rem;color:#9ca3af;padding:16px">
        Powered by TravHub Global Limited
    </p>
</div>

<script>
const API = "<?= $ip_port ?>api/umrah-groups/endpoints.php";
const T   = "<?= htmlspecialchars($travelerSysId) ?>";
const G   = "<?= htmlspecialchars($groupSysId) ?>";

function esc(s){const d=document.createElement('div');d.textContent=String(s??'');return d.innerHTML;}

async function load() {
    try {
        const res = await fetch(`${API}?action=public_info&traveler_sys_id=${encodeURIComponent(T)}&group_sys_id=${encodeURIComponent(G)}`);
        const j = await res.json();
        if (j.status !== 'success') throw new Error(j.message);

        const { traveler, group, leaders, nocs } = j.data;

        // Group name
        document.getElementById('groupName').textContent = group.group_name ?? 'Umrah Group';

        // Traveler info
        document.getElementById('travelerInfo').innerHTML = [
            ['Name',           traveler.traveler_name],
            ['Passport No',    traveler.passport_no],
            ['Date of Birth',  traveler.date_of_birth],
            ['Phone (Roaming)',traveler.roaming_phone],
        ].filter(([,v]) => v).map(([l,v]) =>
            `<div class="info-row"><span class="info-label">${l}</span><span class="info-value">${esc(v)}</span></div>`
        ).join('') || '<p class="text-sm text-gray-400">No info available</p>';

        // Documents
        const docLinks = [];
        if (traveler.passport_file_url) docLinks.push(`<a href="${esc(traveler.passport_file_url)}" target="_blank" class="flex items-center gap-2 p-3 bg-violet-50 rounded-xl text-sm font-semibold text-violet-700 hover:bg-violet-100 transition"><i class="fas fa-passport"></i>View Passport</a>`);
        nocs?.forEach((noc, i) => { if (noc.url) docLinks.push(`<a href="${esc(noc.url)}" target="_blank" class="flex items-center gap-2 p-3 bg-amber-50 rounded-xl text-sm font-semibold text-amber-700 hover:bg-amber-100 transition"><i class="fas fa-file-alt"></i>NOC ${nocs.length > 1 ? i+1 : ''}</a>`); });
        document.getElementById('docsInfo').innerHTML = docLinks.length ? `<div class="space-y-2">${docLinks.join('')}</div>` : '<p class="text-sm text-gray-400">No documents available</p>';

        // Leaders
        if (leaders?.length) {
            document.getElementById('leadersCard').style.display = 'block';
            document.getElementById('leadersInfo').innerHTML = leaders.map(l =>
                `<div class="leader-card">
                    <div style="width:34px;height:34px;border-radius:50%;background:#fde68a;display:flex;align-items:center;justify-content:center;font-weight:700;color:#92400e;flex-shrink:0">${(l.name||'?')[0]}</div>
                    <div>
                        <p style="font-size:.85rem;font-weight:700;color:#1e1b4b">${esc(l.name)}</p>
                        ${l.roaming_phone ? `<p style="font-size:.75rem;color:#6b7280">📱 ${esc(l.roaming_phone)}</p>` : ''}
                    </div>
                </div>`
            ).join('');
        }

        // Flights
        const segs = group.segments ?? [];
        if (segs.length) {
            document.getElementById('flightsCard').style.display = 'block';
            document.getElementById('flightsInfo').innerHTML = segs.map(s =>
                `<div class="seg-item">
                    <p style="font-weight:700;font-size:.88rem;color:#1e1b4b">${esc(s.title || s.from_airport + ' → ' + s.to_airport)}</p>
                    <p style="font-size:.75rem;color:#6b7280;margin-top:3px">${esc(s.airline||'')} · ${esc(s.class||'Economy')}</p>
                    <p style="font-size:.75rem;color:#7c3aed;margin-top:3px">✈ ${esc(s.departure_date)} ${esc(s.departure_time)} → ${esc(s.arrival_date)} ${esc(s.arrival_time)}</p>
                </div>`
            ).join('');
        }

        // Hotels
        const h = group.hotels ?? {};
        const hotelHtml = [
            h.makkah?.name ? `<div style="margin-bottom:8px"><p style="font-size:.7rem;font-weight:700;color:#059669;text-transform:uppercase;letter-spacing:.3px;margin-bottom:4px">Makkah</p><p style="font-size:.85rem;font-weight:600;color:#1e1b4b">${esc(h.makkah.name)}</p><p style="font-size:.75rem;color:#6b7280">${esc(h.makkah.checkin)} → ${esc(h.makkah.checkout)}</p></div>` : '',
            h.madinah?.name ? `<div><p style="font-size:.7rem;font-weight:700;color:#059669;text-transform:uppercase;letter-spacing:.3px;margin-bottom:4px">Madinah</p><p style="font-size:.85rem;font-weight:600;color:#1e1b4b">${esc(h.madinah.name)}</p><p style="font-size:.75rem;color:#6b7280">${esc(h.madinah.checkin)} → ${esc(h.madinah.checkout)}</p></div>` : '',
        ].filter(Boolean).join('');
        if (hotelHtml) { document.getElementById('hotelsCard').style.display='block'; document.getElementById('hotelsInfo').innerHTML = hotelHtml; }

        // Itinerary
        const itin = group.itinerary ?? [];
        if (itin.length) {
            document.getElementById('itinCard').style.display = 'block';
            document.getElementById('itinInfo').innerHTML = itin.map(it =>
                `<div style="display:flex;gap:10px;padding:10px 0;border-bottom:1px solid #f3f4f6">
                    <div class="itin-dot"></div>
                    <div>
                        <p style="font-size:.72rem;font-weight:700;color:#7c3aed">${esc(it.date)}${it.time?' · '+esc(it.time):''}</p>
                        <p style="font-size:.88rem;font-weight:600;color:#1e1b4b;margin-top:2px">${esc(it.title)}</p>
                        ${it.description ? `<div style="font-size:.8rem;color:#6b7280;margin-top:4px">${it.description}</div>` : ''}
                    </div>
                </div>`
            ).join('');
        }

    } catch(e) {
        document.getElementById('groupName').textContent = 'Error';
        document.getElementById('travelerInfo').innerHTML = `<p class="text-sm text-red-500">${esc(e.message)}</p>`;
    }
}

load();
</script>
</body>
</html>