<?php
// FILE PATH: /pages/create-umrah-group.php
include_once('./authenticate.php');
$ip_port = @file_get_contents('../ippath.txt') ?: 'http://103.104.219.3:898';
$ip_port = rtrim($ip_port, '/') . '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Umrah Group — TravHub</title>
<link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<!-- TinyMCE CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js"></script>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.fi{width:100%;padding:8px 12px;border:1.5px solid #e5e7eb;border-radius:9px;font-size:.85rem;color:#111827;outline:none;transition:border .15s;background:#fff}
.fi:focus{border-color:#7c3aed;box-shadow:0 0 0 3px rgba(124,58,237,.1)}
.fi.err{border-color:#ef4444}
.lbl{display:block;font-size:.78rem;font-weight:600;color:#374151;margin-bottom:4px}
.req{color:#ef4444;margin-left:2px}
.card{background:#fff;border:1.5px solid #e5e7eb;border-radius:16px;padding:20px}
.seg-card{background:#faf9ff;border:1.5px solid #ede9fe;border-radius:12px;padding:16px;margin-bottom:12px}
.itin-card{background:#faf9ff;border:1.5px solid #ede9fe;border-radius:12px;padding:16px;margin-bottom:12px}
.btn-add{display:flex;align-items:center;gap:6px;padding:7px 14px;border:1.5px dashed #a78bfa;border-radius:9px;color:#7c3aed;font-size:.8rem;font-weight:600;cursor:pointer;background:transparent;transition:all .15s;width:100%}
.btn-add:hover{background:#f5f3ff;border-color:#7c3aed}
.section-head{font-size:.95rem;font-weight:700;color:#1e1b4b;margin-bottom:14px;display:flex;align-items:center;gap-8px}
.fcols2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.fcols3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px}
#toast{position:fixed;bottom:24px;right:24px;z-index:9999;padding:11px 17px;border-radius:11px;color:#fff;font-size:.83rem;font-weight:600;display:flex;align-items:center;gap:8px;transform:translateY(60px);opacity:0;transition:all .3s;pointer-events:none}
#toast.show{transform:translateY(0);opacity:1}
#toast.success{background:#059669}
#toast.error{background:#dc2626}
</style>
</head>
<body class="bg-gray-100 font-sans">
<?php include '../elements/header.php'; ?>
<?php include '../elements/aside.php'; ?>
<?php include '../elements/preview-model.php'; ?>

<main id="mainContent" class="pt-20 pl-64 transition-all duration-300">
<div class="p-5">

<div class="mb-4 flex items-center justify-between">
    <div>
        <h1 class="text-lg font-bold text-gray-800"><i class="fas fa-plus mr-2 text-violet-500"></i>New Umrah Group</h1>
        <p class="text-xs text-gray-400 mt-0.5">Create a new group with flight segments, hotels and itinerary</p>
    </div>
    <a href="index-umrah-groups.php" class="text-xs text-violet-600 hover:underline font-semibold"><i class="fas fa-arrow-left mr-1"></i>Back to Groups</a>
</div>

<form id="groupForm" novalidate>
<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

    <!-- Left: main info + segments + hotels -->
    <div class="xl:col-span-2 space-y-5">

        <!-- Basic Info -->
        <div class="card">
            <p class="section-head"><i class="fas fa-kaaba mr-2 text-violet-400"></i>Group Information</p>
            <div class="space-y-3">
                <div>
                    <label class="lbl">Group Name <span class="req">*</span></label>
                    <input class="fi" type="text" id="groupName" placeholder="e.g. Umrah Group — November 2026">
                </div>
                <div class="fcols2">
                    <div>
                        <label class="lbl">Status</label>
                        <select class="fi" id="groupStatus">
                            <option value="draft">Draft</option>
                            <option value="active" selected>Active</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                    <div>
                        <label class="lbl">Linked Work (optional)</label>
                        <input class="fi" type="text" id="linkedWork" placeholder="Work sys_id">
                    </div>
                </div>
            </div>
        </div>

        <!-- Flight Segments -->
        <div class="card">
            <p class="section-head"><i class="fas fa-plane mr-2 text-violet-400"></i>Flight Segments</p>
            <div id="segmentsContainer"></div>
            <button type="button" class="btn-add mt-2" onclick="addSegment()">
                <i class="fas fa-plus"></i>Add Flight Segment
            </button>
        </div>

        <!-- Hotels -->
        <div class="card">
            <p class="section-head"><i class="fas fa-hotel mr-2 text-violet-400"></i>Hotels</p>
            <div class="space-y-4">
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-2">Makkah</p>
                    <div class="fcols3">
                        <div><label class="lbl">Hotel Name</label><input class="fi" type="text" id="makkahHotel" placeholder="Hotel name"></div>
                        <div><label class="lbl">Check-in</label><input class="fi" type="date" id="makkahCheckin"></div>
                        <div><label class="lbl">Check-out</label><input class="fi" type="date" id="makkahCheckout"></div>
                    </div>
                </div>
                <div class="border-t border-gray-100 pt-4">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-2">Madinah</p>
                    <div class="fcols3">
                        <div><label class="lbl">Hotel Name</label><input class="fi" type="text" id="madinahHotel" placeholder="Hotel name"></div>
                        <div><label class="lbl">Check-in</label><input class="fi" type="date" id="madinahCheckin"></div>
                        <div><label class="lbl">Check-out</label><input class="fi" type="date" id="madinahCheckout"></div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Right: Itinerary -->
    <div class="space-y-5">
        <div class="card">
            <p class="section-head"><i class="fas fa-list-alt mr-2 text-violet-400"></i>Itinerary</p>
            <div id="itinContainer"></div>
            <button type="button" class="btn-add mt-2" onclick="addItinerary()">
                <i class="fas fa-plus"></i>Add Itinerary Item
            </button>
        </div>

        <!-- Submit -->
        <button type="submit" id="submitBtn"
            class="w-full py-3 bg-violet-600 hover:bg-violet-700 text-white font-bold rounded-xl transition text-sm flex items-center justify-center gap-2">
            <i class="fas fa-check"></i>Create Group
        </button>
        <a href="index-umrah-groups.php"
            class="w-full py-3 border border-gray-200 text-gray-600 font-semibold rounded-xl text-sm flex items-center justify-center gap-2 hover:bg-gray-50 transition">
            Cancel
        </a>
    </div>

</div>
</form>
</div>
</main>

<div id="toast"><i id="toast-i" class="fas fa-check-circle"></i><span id="toast-m"></span></div>

<script>
const API = "<?= $ip_port ?>api/umrah-groups/endpoints.php";
let segCount = 0, itinCount = 0;

// ── Segments ─────────────────────────────────────────────────
function addSegment(data = {}) {
    const i = ++segCount;
    const c = document.createElement('div');
    c.className = 'seg-card'; c.id = 'seg_' + i;
    c.innerHTML = `
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs font-bold text-violet-600">Segment ${i}</p>
            <button type="button" onclick="document.getElementById('seg_${i}').remove()" class="text-red-400 hover:text-red-600 text-xs"><i class="fas fa-times"></i></button>
        </div>
        <div class="space-y-2">
            <div class="fcols2">
                <div><label class="lbl">Route Title</label><input class="fi seg-title" type="text" placeholder="e.g. DAC → MED" value="${esc(data.title??'')}"></div>
                <div><label class="lbl">Type</label>
                    <select class="fi seg-type">
                        <option value="one_way" ${data.type==='one_way'?'selected':''}>One Way</option>
                        <option value="round" ${data.type==='round'?'selected':''}>Round Trip</option>
                        <option value="multi_city" ${data.type==='multi_city'?'selected':''}>Multi City</option>
                    </select>
                </div>
            </div>
            <div class="fcols2">
                <div><label class="lbl">From Airport</label><input class="fi seg-from" type="text" placeholder="DAC" maxlength="5" value="${esc(data.from_airport??'')}"></div>
                <div><label class="lbl">To Airport</label><input class="fi seg-to" type="text" placeholder="MED" maxlength="5" value="${esc(data.to_airport??'')}"></div>
            </div>
            <div class="fcols2">
                <div><label class="lbl">Departure Date</label><input class="fi seg-dep-date" type="date" value="${esc(data.departure_date??'')}"></div>
                <div><label class="lbl">Departure Time</label><input class="fi seg-dep-time" type="time" value="${esc(data.departure_time??'')}"></div>
            </div>
            <div class="fcols2">
                <div><label class="lbl">Arrival Date</label><input class="fi seg-arr-date" type="date" value="${esc(data.arrival_date??'')}"></div>
                <div><label class="lbl">Arrival Time</label><input class="fi seg-arr-time" type="time" value="${esc(data.arrival_time??'')}"></div>
            </div>
            <div class="fcols2">
                <div><label class="lbl">Airline</label><input class="fi seg-airline" type="text" placeholder="e.g. Biman Bangladesh" value="${esc(data.airline??'')}"></div>
                <div><label class="lbl">Class</label>
                    <select class="fi seg-class">
                        <option value="economy" ${data.class==='economy'?'selected':''}>Economy</option>
                        <option value="business" ${data.class==='business'?'selected':''}>Business</option>
                        <option value="first" ${data.class==='first'?'selected':''}>First</option>
                    </select>
                </div>
            </div>
        </div>`;
    document.getElementById('segmentsContainer').appendChild(c);
}

function collectSegments() {
    return [...document.querySelectorAll('.seg-card')].map((c, i) => ({
        sys_id:         'SEG-' + String(i+1).padStart(3,'0'),
        title:          c.querySelector('.seg-title')?.value.trim() ?? '',
        type:           c.querySelector('.seg-type')?.value ?? 'one_way',
        from_airport:   c.querySelector('.seg-from')?.value.trim().toUpperCase() ?? '',
        to_airport:     c.querySelector('.seg-to')?.value.trim().toUpperCase() ?? '',
        departure_date: c.querySelector('.seg-dep-date')?.value ?? '',
        departure_time: c.querySelector('.seg-dep-time')?.value ?? '',
        arrival_date:   c.querySelector('.seg-arr-date')?.value ?? '',
        arrival_time:   c.querySelector('.seg-arr-time')?.value ?? '',
        airline:        c.querySelector('.seg-airline')?.value.trim() ?? '',
        class:          c.querySelector('.seg-class')?.value ?? 'economy',
    }));
}

// ── Itinerary ─────────────────────────────────────────────────
function addItinerary(data = {}) {
    const i = ++itinCount;
    const edId = 'itin_ed_' + i;
    const c = document.createElement('div');
    c.className = 'itin-card'; c.id = 'itin_' + i;
    c.innerHTML = `
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs font-bold text-violet-600">Item ${i}</p>
            <button type="button" onclick="removeItin(${i})" class="text-red-400 hover:text-red-600 text-xs"><i class="fas fa-times"></i></button>
        </div>
        <div class="space-y-2">
            <div class="fcols2">
                <div><label class="lbl">Date</label><input class="fi itin-date" type="date" value="${esc(data.date??'')}"></div>
                <div><label class="lbl">Time</label><input class="fi itin-time" type="time" value="${esc(data.time??'')}"></div>
            </div>
            <div><label class="lbl">Title</label><input class="fi itin-title" type="text" placeholder="e.g. Arrive Madinah" value="${esc(data.title??'')}"></div>
            <div>
                <label class="lbl">Description</label>
                <textarea id="${edId}" class="itin-desc fi" rows="3" placeholder="Details…">${esc(data.description??'')}</textarea>
            </div>
        </div>`;
    document.getElementById('itinContainer').appendChild(c);
    // Init TinyMCE on the textarea
    tinymce.init({
        selector: '#' + edId,
        height: 120,
        menubar: false,
        plugins: 'lists link',
        toolbar: 'bold italic | bullist numlist | link',
        content_style: 'body{font-family:sans-serif;font-size:13px}',
        statusbar: false,
    });
}
function removeItin(i) {
    tinymce.get('itin_ed_' + i)?.remove();
    document.getElementById('itin_' + i)?.remove();
}

function collectItinerary() {
    return [...document.querySelectorAll('.itin-card')].map((c, i) => {
        const edId = 'itin_ed_' + (i+1);
        const desc = tinymce.get(edId)?.getContent() ?? c.querySelector('.itin-desc')?.value ?? '';
        return {
            sys_id:      'IT-' + String(i+1).padStart(3,'0'),
            date:        c.querySelector('.itin-date')?.value ?? '',
            time:        c.querySelector('.itin-time')?.value ?? '',
            title:       c.querySelector('.itin-title')?.value.trim() ?? '',
            description: desc,
        };
    });
}

// ── Submit ────────────────────────────────────────────────────
document.getElementById('groupForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const name = document.getElementById('groupName').value.trim();
    if (!name) { toast('error','Group name is required'); document.getElementById('groupName').classList.add('err'); return; }
    document.getElementById('groupName').classList.remove('err');

    const btn = document.getElementById('submitBtn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Creating…'; btn.disabled = true;

    const data = {
        action:      'create',
        group_name:  name,
        status:      document.getElementById('groupStatus').value,
        linked_work_id: document.getElementById('linkedWork').value.trim() || null,
        segments:    collectSegments(),
        hotels: {
            makkah:  { name: document.getElementById('makkahHotel').value.trim(),  checkin: document.getElementById('makkahCheckin').value,  checkout: document.getElementById('makkahCheckout').value },
            madinah: { name: document.getElementById('madinahHotel').value.trim(), checkin: document.getElementById('madinahCheckin').value, checkout: document.getElementById('madinahCheckout').value },
        },
        itinerary: collectItinerary(),
    };

    try {
        const res = await fetch(API, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(data) });
        const j = await res.json();
        if (j.status === 'success') {
            toast('success', 'Group created! ' + j.sys_id);
            setTimeout(() => location.href = 'show-umrah-group.php?sys_id=' + j.sys_id, 1200);
        } else { toast('error', j.message || 'Failed'); }
    } catch(err) { toast('error', 'Network error: ' + err.message); }
    finally { btn.innerHTML = '<i class="fas fa-check mr-1"></i>Create Group'; btn.disabled = false; }
});

function esc(s) { return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
let _tt;
function toast(t,m){const el=document.getElementById('toast');document.getElementById('toast-i').className='fas '+(t==='success'?'fa-check-circle':'fa-exclamation-circle');document.getElementById('toast-m').textContent=m;el.className='show '+t;clearTimeout(_tt);_tt=setTimeout(()=>{el.className=t;},3200);}

// Add first segment by default
addSegment();
</script>
</body>
</html>