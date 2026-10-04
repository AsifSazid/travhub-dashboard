<?php
// FILE PATH: /pages/index-umrah-groups.php
include_once('./authenticate.php');
$ip_port = @file_get_contents('../ippath.txt') ?: 'http://103.104.219.3:898';
$ip_port = rtrim($ip_port, '/') . '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Umrah Groups — TravHub</title>
<link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.group-card{background:#fff;border:1.5px solid #e5e7eb;border-radius:16px;padding:20px;transition:all .2s;cursor:pointer}
.group-card:hover{border-color:#a78bfa;box-shadow:0 4px 20px rgba(139,92,246,.1);transform:translateY(-1px)}
.status-badge{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:999px;font-size:.72rem;font-weight:700}
.status-active{background:#d1fae5;color:#065f46}
.status-draft{background:#f3f4f6;color:#374151}
.status-completed{background:#dbeafe;color:#1e40af}
#toast{position:fixed;bottom:24px;right:24px;z-index:9999;padding:11px 18px;border-radius:11px;color:#fff;font-size:.83rem;font-weight:600;display:flex;align-items:center;gap:8px;transform:translateY(60px);opacity:0;transition:all .3s;pointer-events:none}
#toast.show{transform:translateY(0);opacity:1}
#toast.success{background:#059669}
#toast.error{background:#dc2626}
</style>
</head>
<body class="bg-gray-50 font-sans">
<?php include '../elements/header.php'; ?>
<?php include '../elements/aside.php'; ?>
<?php include '../elements/preview-model.php'; ?>

<main id="mainContent" class="pt-20 pl-64 transition-all duration-300 min-h-screen">
<div class="p-5">

<!-- Header -->
<div class="flex items-center justify-between mb-5">
    <div>
        <h1 class="text-xl font-bold text-gray-800">
            <i class="fas fa-mosque mr-2 text-violet-500"></i>Umrah Groups
        </h1>
        <p class="text-xs text-gray-400 mt-0.5">Manage Umrah traveler groups, flights, hotels and itinerary</p>
    </div>
    <a href="create-umrah-group.php" class="flex items-center gap-2 px-4 py-2.5 bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold rounded-xl transition">
        <i class="fas fa-plus"></i>New Group
    </a>
</div>

<!-- Stats row -->
<div class="grid grid-cols-4 gap-4 mb-5" id="statsRow">
    <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
        <div class="text-2xl font-bold text-violet-600" id="statTotal">—</div>
        <div class="text-xs text-gray-400 mt-1">Total Groups</div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
        <div class="text-2xl font-bold text-green-600" id="statActive">—</div>
        <div class="text-xs text-gray-400 mt-1">Active</div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
        <div class="text-2xl font-bold text-blue-600" id="statTravelers">—</div>
        <div class="text-xs text-gray-400 mt-1">Total Travelers</div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
        <div class="text-2xl font-bold text-amber-600" id="statUpcoming">—</div>
        <div class="text-xs text-gray-400 mt-1">Upcoming Departures</div>
    </div>
</div>

<!-- Search + filter -->
<div class="flex gap-3 mb-4">
    <div class="relative flex-1 max-w-sm">
        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
        <input type="text" id="searchInput" placeholder="Search group name…"
            class="w-full pl-8 pr-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-violet-400"
            oninput="applyFilter()">
    </div>
    <select id="statusFilter" onchange="applyFilter()"
        class="px-3 py-2 border border-gray-200 rounded-xl text-sm text-gray-600 focus:outline-none focus:border-violet-400">
        <option value="">All Status</option>
        <option value="active">Active</option>
        <option value="draft">Draft</option>
        <option value="completed">Completed</option>
    </select>
</div>

<!-- Groups grid -->
<div id="groupsGrid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
    <div class="col-span-3 text-center py-12 text-gray-400">
        <i class="fas fa-spinner fa-spin text-2xl mb-2 block"></i>
        <p class="text-sm">Loading groups…</p>
    </div>
</div>

</div>
</main>

<div id="toast"><i id="toast-i" class="fas fa-check-circle"></i><span id="toast-m"></span></div>

<script>
const API = "<?= $ip_port ?>api/umrah-groups/endpoints.php";
let allGroups = [];

async function loadGroups() {
    try {
        const res = await fetch(API + '?action=list');
        const j = await res.json();
        if (!j.status === 'success') throw new Error(j.message);
        allGroups = j.data ?? [];
        updateStats();
        renderGrid(allGroups);
    } catch(e) {
        document.getElementById('groupsGrid').innerHTML =
            `<div class="col-span-3 text-center py-12 text-red-400 text-sm"><i class="fas fa-exclamation-circle mr-2"></i>${e.message}</div>`;
    }
}

function updateStats() {
    const active = allGroups.filter(g => g.status === 'active').length;
    const travelers = allGroups.reduce((s, g) => s + (parseInt(g.traveler_count) || 0), 0);
    const today = new Date().toISOString().split('T')[0];
    const upcoming = allGroups.filter(g => {
        const segs = g.segments ? (typeof g.segments === 'string' ? JSON.parse(g.segments) : g.segments) : [];
        return segs.some(s => s.departure_date >= today);
    }).length;
    document.getElementById('statTotal').textContent     = allGroups.length;
    document.getElementById('statActive').textContent    = active;
    document.getElementById('statTravelers').textContent = travelers;
    document.getElementById('statUpcoming').textContent  = upcoming;
}

function applyFilter() {
    const q      = document.getElementById('searchInput').value.toLowerCase();
    const status = document.getElementById('statusFilter').value;
    const filtered = allGroups.filter(g =>
        (!q || g.group_name.toLowerCase().includes(q)) &&
        (!status || g.status === status)
    );
    renderGrid(filtered);
}

function renderGrid(groups) {
    const grid = document.getElementById('groupsGrid');
    if (!groups.length) {
        grid.innerHTML = `<div class="col-span-3 text-center py-16 text-gray-400">
            <i class="fas fa-mosque text-4xl mb-3 block opacity-20"></i>
            <p class="font-semibold">No groups found</p>
            <a href="create-umrah-group.php" class="mt-2 inline-block text-sm text-violet-500 font-semibold hover:underline">Create first group →</a>
        </div>`;
        return;
    }
    grid.innerHTML = groups.map(g => {
        const segs = g.segments ? (typeof g.segments === 'string' ? JSON.parse(g.segments) : g.segments) : [];
        const firstDep = segs[0]?.departure_date ?? null;
        const statusCls = {active:'status-active',draft:'status-draft',completed:'status-completed'}[g.status] ?? 'status-draft';
        return `<div class="group-card" onclick="location.href='show-umrah-group.php?sys_id=${g.sys_id}'">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <h3 class="font-bold text-gray-800 text-sm">${esc(g.group_name)}</h3>
                    <p class="text-xs text-gray-400 font-mono mt-0.5">${esc(g.sys_id)}</p>
                </div>
                <span class="status-badge ${statusCls}">${g.status ?? 'draft'}</span>
            </div>
            ${firstDep ? `<div class="flex items-center gap-2 text-xs text-gray-500 mb-2">
                <i class="fas fa-plane-departure text-violet-400"></i>
                First departure: <span class="font-semibold">${firstDep}</span>
            </div>` : ''}
            ${segs.length ? `<div class="flex items-center gap-2 text-xs text-gray-500 mb-2">
                <i class="fas fa-route text-violet-400"></i>
                ${segs.map(s => esc(s.title ?? (s.from_airport + ' → ' + s.to_airport))).join(' · ')}
            </div>` : ''}
            <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-50">
                <span class="text-xs text-gray-400">
                    <i class="fas fa-users mr-1 text-violet-300"></i>
                    ${parseInt(g.traveler_count) || 0} traveler${parseInt(g.traveler_count) !== 1 ? 's' : ''}
                </span>
                <span class="text-xs text-indigo-500 font-semibold">View →</span>
            </div>
        </div>`;
    }).join('');
}

function esc(s) { return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

loadGroups();
</script>
</body>
</html>