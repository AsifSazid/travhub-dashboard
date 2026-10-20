<?php
// FILE PATH: /pages/show-umrah-group.php
include_once('./authenticate.php');
$ip_port = @file_get_contents('../ippath.txt') ?: 'http://103.104.219.3:898';
$ip_port = rtrim($ip_port, '/') . '/';
$groupSysId = $_GET['sys_id'] ?? '';
if (!$groupSysId) { header('Location: index-umrah-groups.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Umrah Group — TravHub</title>
<link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.tab-btn{padding:8px 16px;border-radius:10px;font-size:.82rem;font-weight:600;color:#6b7280;cursor:pointer;transition:all .15s;border:none;background:transparent}
.tab-btn.active{background:#7c3aed;color:#fff}
.fi{width:100%;padding:8px 12px;border:1.5px solid #e5e7eb;border-radius:9px;font-size:.83rem;color:#111827;outline:none;transition:border .15s;background:#fff}
.fi:focus{border-color:#7c3aed;box-shadow:0 0 0 3px rgba(124,58,237,.08)}
.lbl{display:block;font-size:.72rem;font-weight:600;color:#374151;margin-bottom:3px}
.tab-pane{display:none}.tab-pane.active{display:block}
.traveler-row{background:#fff;border:1.5px solid #e5e7eb;border-radius:12px;padding:14px 16px;display:flex;align-items:center;gap:12px;transition:all .15s}
.traveler-row:hover{border-color:#a78bfa;box-shadow:0 2px 8px rgba(124,58,237,.08)}
.avatar{width:38px;height:38px;border-radius:50%;object-fit:cover;border:2px solid #ede9fe;flex-shrink:0;background:#f5f3ff;display:flex;align-items:center;justify-content:center;color:#7c3aed;font-weight:700;font-size:.85rem}
.leader-badge{display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:999px;background:#fef3c7;color:#92400e;font-size:.68rem;font-weight:700}
.hotel-chip{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:10px 14px;font-size:.8rem}
.itin-item{display:flex;gap:12px;padding:12px 0;border-bottom:1px solid #f3f4f6}
.itin-dot{width:10px;height:10px;border-radius:50%;background:#7c3aed;margin-top:5px;flex-shrink:0}
.modal-bg{position:fixed;inset:0;background:rgba(0,0,0,.5);backdrop-filter:blur(4px);z-index:50;display:flex;align-items:center;justify-content:center;padding:16px}
.modal-box{background:#fff;border-radius:20px;width:100%;max-width:600px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.15)}
/* Flight UI */
.flight-block{background:#fff;border:1.5px solid #e5e7eb;border-radius:14px;overflow:hidden;margin-bottom:10px}
.flight-header{display:flex;align-items:center;gap:8px;padding:9px 14px;background:#f5f3ff;border-bottom:1px solid #ede9fe}
.leg-row{display:flex;align-items:center;gap:8px;padding:8px 14px;border-bottom:1px solid #f9fafb;flex-wrap:wrap}
.leg-row:last-child{border-bottom:none}
#toast{position:fixed;bottom:24px;right:24px;z-index:9999;padding:11px 17px;border-radius:11px;color:#fff;font-size:.83rem;font-weight:600;display:flex;align-items:center;gap:8px;transform:translateY(60px);opacity:0;transition:all .3s;pointer-events:none}
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

<div id="pageHeader" class="mb-5 flex items-start justify-between">
    <div>
        <h1 class="text-xl font-bold text-gray-800" id="groupTitle">Loading…</h1>
        <div class="flex items-center gap-2 mt-1">
            <span class="text-xs font-mono text-gray-400" id="groupSysId"><?= htmlspecialchars($groupSysId) ?></span>
            <span id="groupStatusBadge"></span>
        </div>
    </div>
    <div class="flex gap-2">
        <button onclick="openAddTravelerModal()"
            class="flex items-center gap-2 px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold rounded-xl transition">
            <i class="fas fa-user-plus"></i>Add Traveler
        </button>
        <button onclick="openLeaderModal()"
            class="flex items-center gap-2 px-4 py-2 border border-violet-300 text-violet-600 hover:bg-violet-50 text-sm font-semibold rounded-xl transition">
            <i class="fas fa-star"></i>Group Leaders
        </button>
        <a href="index-umrah-groups.php" class="flex items-center gap-2 px-4 py-2 border border-gray-200 text-gray-600 text-sm font-semibold rounded-xl hover:bg-gray-50 transition">
            <i class="fas fa-arrow-left"></i>Back
        </a>
    </div>
</div>

<div class="flex gap-1 mb-5 bg-white border border-gray-100 rounded-xl p-1.5 w-fit">
    <button class="tab-btn active" onclick="switchTab('travelers')">
        <i class="fas fa-users mr-1.5"></i>Travelers <span id="travelerCount" class="ml-1 bg-violet-100 text-violet-700 text-[10px] font-bold px-1.5 py-0.5 rounded-full">0</span>
    </button>
    <button class="tab-btn" onclick="switchTab('flights')"><i class="fas fa-plane mr-1.5"></i>Flights</button>
    <button class="tab-btn" onclick="switchTab('hotels')"><i class="fas fa-hotel mr-1.5"></i>Hotels</button>
    <button class="tab-btn" onclick="switchTab('itinerary')"><i class="fas fa-list-alt mr-1.5"></i>Itinerary</button>
</div>

<!-- Travelers tab -->
<div class="tab-pane active" id="tab-travelers">
    <div class="bg-white border border-gray-100 rounded-xl p-4 mb-4 flex items-center justify-between">
        <div>
            <p class="text-sm font-semibold text-gray-700">Group Authorization Letter</p>
            <p class="text-xs text-gray-400 mt-0.5">Select travelers below, then upload one collective letter covering all selected</p>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="toggleSelectAll()" class="text-xs text-violet-600 font-semibold hover:underline" id="selectAllBtn">Select All</button>
            <label class="flex items-center gap-2 px-3 py-2 bg-indigo-600 text-white text-xs font-semibold rounded-xl cursor-pointer hover:bg-indigo-700 transition">
                <i class="fas fa-file-signature"></i>Upload Group Letter
                <input type="file" id="groupLetterFile" class="hidden" accept=".pdf,.jpg,.jpeg,.png" onchange="uploadNoc('group_letter')">
            </label>
        </div>
    </div>
    <div class="flex justify-end mb-3">
        <button onclick="generateAllIdCards()"
            class="flex items-center gap-2 px-4 py-2 border border-indigo-200 text-indigo-600 text-sm font-semibold rounded-xl hover:bg-indigo-50 transition">
            <i class="fas fa-id-card"></i>Generate ID Cards
        </button>
    </div>
    <div id="travelersList" class="space-y-3">
        <div class="text-center py-8 text-gray-400"><i class="fas fa-spinner fa-spin"></i></div>
    </div>
</div>

<input type="file" id="nocFileHidden" class="hidden" accept=".pdf,.jpg,.jpeg,.png"
    style="position:absolute;left:-9999px;opacity:0;pointer-events:none">

<!-- Flights tab -->
<div class="tab-pane" id="tab-flights">
    <div class="flex justify-end mb-3">
        <button onclick="toggleEdit('flights')" id="editBtn-flights"
            class="flex items-center gap-2 px-3 py-1.5 text-xs font-semibold border border-violet-200 text-violet-600 rounded-lg hover:bg-violet-50 transition">
            <i class="fas fa-pen mr-1"></i>Edit Flights
        </button>
    </div>
    <!-- Ticket upload zone -->
    <div class="bg-violet-50 border border-dashed border-violet-300 rounded-xl p-4 mb-4">
        <div class="flex items-center gap-3 mb-2">
            <i class="fas fa-ticket-alt text-violet-500"></i>
            <span class="text-sm font-semibold text-violet-700">Upload Airline Ticket</span>
            <span class="text-xs text-gray-400">AI will extract PNR, passengers and match with group travelers</span>
        </div>
        <label class="inline-flex items-center gap-2 px-4 py-2 bg-violet-600 text-white text-xs font-semibold rounded-lg cursor-pointer hover:bg-violet-700 transition">
            <i class="fas fa-upload"></i>Choose Ticket (PDF / JPG / PNG)
            <input type="file" id="ticketFileInput" class="hidden" accept=".pdf,.jpg,.jpeg,.png,.webp"
                   onchange="handleTicketUpload(this)">
        </label>
    </div>

    <!-- Ticket extraction result panel (hidden until upload) -->
    <div id="ticketExtractPanel" class="hidden mb-4"></div>

    <!-- View -->
    <div id="flightsContent" class="space-y-3"></div>
    <!-- Edit -->
    <div id="flightsEdit" class="hidden space-y-4">
        <div id="flightsEditContainer" class="space-y-4"></div>
        <button type="button" onclick="addFlight()"
            class="flex items-center gap-2 px-3 py-2 border border-dashed border-violet-300 text-violet-600 text-xs font-semibold rounded-lg hover:bg-violet-50 transition w-full justify-center">
            <i class="fas fa-plus"></i>Add Flight
        </button>
        <div class="flex gap-2 justify-end mt-3">
            <button onclick="toggleEdit('flights')" class="px-4 py-2 text-xs font-semibold text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition">Cancel</button>
            <button onclick="saveFlights()" class="px-4 py-2 text-xs font-semibold bg-violet-600 text-white rounded-lg hover:bg-violet-700 transition">
                <i class="fas fa-check mr-1"></i>Save Flights
            </button>
        </div>
    </div>
</div>

<!-- Hotels tab -->
<div class="tab-pane" id="tab-hotels">
    <div class="flex justify-end mb-3">
        <button onclick="toggleEdit('hotels')" id="editBtn-hotels"
            class="flex items-center gap-2 px-3 py-1.5 text-xs font-semibold border border-violet-200 text-violet-600 rounded-lg hover:bg-violet-50 transition">
            <i class="fas fa-pen mr-1"></i>Edit Hotels
        </button>
    </div>
    <div id="hotelsContent" class="grid grid-cols-2 gap-4"></div>
    <div id="hotelsEdit" class="hidden bg-white border border-gray-100 rounded-xl p-5 space-y-5">
        <div>
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-3">Makkah</p>
            <div class="grid grid-cols-3 gap-3">
                <div><label class="lbl">Hotel Name</label><input class="fi" type="text" id="edit_makkah_name" placeholder="Hotel name"></div>
                <div><label class="lbl">Check-in</label><input class="fi" type="date" id="edit_makkah_checkin"></div>
                <div><label class="lbl">Check-out</label><input class="fi" type="date" id="edit_makkah_checkout"></div>
            </div>
            <div class="mt-2"><label class="lbl">Address</label><input class="fi" type="text" id="edit_makkah_address" placeholder="Hotel address, Makkah"></div>
        </div>
        <div class="border-t border-gray-100 pt-4">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-3">Madinah</p>
            <div class="grid grid-cols-3 gap-3">
                <div><label class="lbl">Hotel Name</label><input class="fi" type="text" id="edit_madinah_name" placeholder="Hotel name"></div>
                <div><label class="lbl">Check-in</label><input class="fi" type="date" id="edit_madinah_checkin"></div>
                <div><label class="lbl">Check-out</label><input class="fi" type="date" id="edit_madinah_checkout"></div>
            </div>
            <div class="mt-2"><label class="lbl">Address</label><input class="fi" type="text" id="edit_madinah_address" placeholder="Hotel address, Madinah"></div>
        </div>
        <div class="flex gap-2 justify-end pt-2">
            <button onclick="toggleEdit('hotels')" class="px-4 py-2 text-xs font-semibold text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition">Cancel</button>
            <button onclick="saveHotels()" class="px-4 py-2 text-xs font-semibold bg-violet-600 text-white rounded-lg hover:bg-violet-700 transition">
                <i class="fas fa-check mr-1"></i>Save Hotels
            </button>
        </div>
    </div>
</div>

<!-- Itinerary tab -->
<div class="tab-pane" id="tab-itinerary">
    <div class="flex justify-end mb-3">
        <button onclick="toggleEdit('itinerary')" id="editBtn-itinerary"
            class="flex items-center gap-2 px-3 py-1.5 text-xs font-semibold border border-violet-200 text-violet-600 rounded-lg hover:bg-violet-50 transition">
            <i class="fas fa-pen mr-1"></i>Edit Itinerary
        </button>
    </div>
    <div id="itinContent" class="bg-white border border-gray-100 rounded-xl p-5"></div>
    <div id="itineraryEdit" class="hidden space-y-3">
        <div id="itinEditContainer"></div>
        <button type="button" onclick="addEditItin()"
            class="flex items-center gap-2 px-3 py-2 border border-dashed border-violet-300 text-violet-600 text-xs font-semibold rounded-lg hover:bg-violet-50 transition w-full justify-center">
            <i class="fas fa-plus"></i>Add Item
        </button>
        <div class="flex gap-2 justify-end mt-3">
            <button onclick="toggleEdit('itinerary')" class="px-4 py-2 text-xs font-semibold text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition">Cancel</button>
            <button onclick="saveItinerary()" class="px-4 py-2 text-xs font-semibold bg-violet-600 text-white rounded-lg hover:bg-violet-700 transition">
                <i class="fas fa-check mr-1"></i>Save Itinerary
            </button>
        </div>
    </div>
</div>

</div>
</main>

<!-- Add Traveler Modal -->
<div id="addTravelerModal" class="modal-bg hidden">
<div class="modal-box">
    <div class="p-5 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-bold text-gray-800">Add Traveler</h3>
        <button onclick="closeModal('addTravelerModal')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
    </div>
    <div id="methodSelect" class="p-6 space-y-3">
        <p class="text-sm text-gray-500 mb-4">How would you like to add this traveler?</p>
        <button onclick="setAddMethod('scan')"
            class="w-full flex items-center gap-4 p-4 border-2 border-gray-200 hover:border-violet-400 rounded-xl transition">
            <div class="w-10 h-10 bg-violet-100 rounded-xl flex items-center justify-center text-violet-600 flex-shrink-0">
                <i class="fas fa-passport text-lg"></i>
            </div>
            <div class="text-left">
                <p class="font-semibold text-gray-800 text-sm">Scan Passport</p>
                <p class="text-xs text-gray-400">Upload passport image — AI will extract details automatically</p>
            </div>
        </button>
        <button onclick="setAddMethod('manual')"
            class="w-full flex items-center gap-4 p-4 border-2 border-gray-200 hover:border-violet-400 rounded-xl transition">
            <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center text-indigo-600 flex-shrink-0">
                <i class="fas fa-keyboard text-lg"></i>
            </div>
            <div class="text-left">
                <p class="font-semibold text-gray-800 text-sm">Enter Manually</p>
                <p class="text-xs text-gray-400">Type in the traveler's details</p>
            </div>
        </button>
        <div class="border-t border-gray-100 pt-3">
            <button onclick="setAddMethod('existing')"
                class="w-full flex items-center gap-4 p-4 border-2 border-gray-200 hover:border-violet-400 rounded-xl transition">
                <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center text-green-600 flex-shrink-0">
                    <i class="fas fa-search text-lg"></i>
                </div>
                <div class="text-left">
                    <p class="font-semibold text-gray-800 text-sm">Link Existing Traveler</p>
                    <p class="text-xs text-gray-400">Search and add a traveler already in the system</p>
                </div>
            </button>
        </div>
    </div>
    <div id="scanMethod" class="hidden p-6">
        <button onclick="setAddMethod(null)" class="text-xs text-violet-600 font-semibold mb-4 hover:underline"><i class="fas fa-arrow-left mr-1"></i>Back</button>
        <div id="scanUploadZone" class="border-2 border-dashed border-violet-200 rounded-xl p-8 text-center cursor-pointer hover:border-violet-400 hover:bg-violet-50 transition mb-4" onclick="document.getElementById('passportFile').click()">
            <i class="fas fa-cloud-upload-alt text-3xl text-violet-300 mb-2 block"></i>
            <p class="text-sm text-gray-600 font-medium">Click to upload passport image</p>
            <p class="text-xs text-gray-400 mt-1">JPG, PNG, PDF, WebP</p>
        </div>
        <input type="file" id="passportFile" class="hidden" accept=".jpg,.jpeg,.png,.pdf,.webp" onchange="extractPassport(this)">
        <div id="scanLoading" class="hidden text-center py-4">
            <i class="fas fa-spinner fa-spin text-violet-500 text-xl mb-2 block"></i>
            <p class="text-sm text-gray-500">Extracting passport data…</p>
        </div>
        <div id="scanForm" class="hidden"></div>
    </div>
    <div id="manualMethod" class="hidden p-6">
        <button onclick="setAddMethod(null)" class="text-xs text-violet-600 font-semibold mb-4 hover:underline"><i class="fas fa-arrow-left mr-1"></i>Back</button>
        <div id="travelerFormFields"></div>
    </div>
    <div id="existingMethod" class="hidden p-6">
        <button onclick="setAddMethod(null)" class="text-xs text-violet-600 font-semibold mb-4 hover:underline"><i class="fas fa-arrow-left mr-1"></i>Back</button>
        <div class="mb-3">
            <input type="text" id="existingSearch" class="fi" placeholder="Search by name or passport number…" oninput="searchExistingTraveler(this.value)">
        </div>
        <div id="existingResults" class="space-y-2 max-h-64 overflow-y-auto"></div>
    </div>
</div>
</div>

<!-- Group Leader Modal -->
<div id="leaderModal" class="modal-bg hidden">
<div class="modal-box">
    <div class="p-5 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-bold text-gray-800">Group Leaders <span class="text-xs font-normal text-gray-400 ml-2">Select 1–3 leaders</span></h3>
        <button onclick="closeModal('leaderModal')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
    </div>
    <div class="p-5">
        <div id="leaderList" class="space-y-2 max-h-72 overflow-y-auto mb-4"></div>
        <button onclick="saveLeaders()" class="w-full py-2.5 bg-violet-600 hover:bg-violet-700 text-white font-bold rounded-xl transition text-sm">
            <i class="fas fa-check mr-2"></i>Save Leaders
        </button>
    </div>
</div>
</div>

<div id="toast"><i id="toast-i" class="fas fa-check-circle"></i><span id="toast-m"></span></div>

<script>
const GROUP_SYS_ID       = "<?= htmlspecialchars($groupSysId) ?>";
const API                = "<?= $ip_port ?>api/umrah-groups/endpoints.php";
const TRAVELER_API       = "<?= $ip_port ?>api/travelers/";
const EXTRACT_API        = "<?= $ip_port ?>api/travelers/extract-document.php";
const EXTRACT_TICKET_API = "<?= $ip_port ?>api/umrah-groups/extract-ticket.php";
const STORE_TRAVELER_API = "<?= $ip_port ?>api/travelers/store.php";

let groupData = null;
let travelers  = [];
let selectedTravelers = new Set();

// ── Load group ────────────────────────────────────────────────
async function loadGroup() {
    try {
        const res = await fetch(`${API}?action=get&sys_id=${encodeURIComponent(GROUP_SYS_ID)}`);
        const j = await res.json();
        if (j.status !== 'success') throw new Error(j.message);
        groupData = j.data;
        renderHeader();
        renderFlights();
        renderHotels();
        renderItinerary();
        await loadTravelers();
    } catch(e) {
        document.getElementById('groupTitle').textContent = 'Error: ' + e.message;
    }
}

function renderHeader() {
    document.getElementById('groupTitle').textContent = groupData.group_name;
    const statusColors = {active:'bg-green-100 text-green-700',draft:'bg-gray-100 text-gray-600',completed:'bg-blue-100 text-blue-700'};
    const cls = statusColors[groupData.status] ?? 'bg-gray-100 text-gray-600';
    document.getElementById('groupStatusBadge').innerHTML =
        `<span class="text-xs font-bold px-2 py-0.5 rounded-full ${cls}">${groupData.status ?? 'draft'}</span>`;
}

// ── Travelers ─────────────────────────────────────────────────
async function loadTravelers() {
    const res = await fetch(`${API}?action=list_travelers&group_sys_id=${encodeURIComponent(GROUP_SYS_ID)}`);
    const j = await res.json();
    travelers = j.data ?? [];
    document.getElementById('travelerCount').textContent = travelers.length;
    renderTravelers();
}

function renderTravelers() {
    const el = document.getElementById('travelersList');
    if (!travelers.length) {
        el.innerHTML = `<div class="text-center py-12 text-gray-400">
            <i class="fas fa-users text-4xl mb-3 block opacity-20"></i>
            <p class="font-semibold">No travelers yet</p>
            <p class="text-xs mt-1">Click "Add Traveler" to get started</p>
        </div>`;
        return;
    }
    el.innerHTML = travelers.map(t => {
        const isLeader   = t.is_leader == 1;
        const isSelected = selectedTravelers.has(t.traveler_sys_id);
        return `<div class="traveler-row">
            <input type="checkbox" class="traveler-checkbox w-4 h-4 accent-violet-600 flex-shrink-0"
                data-sys="${esc(t.traveler_sys_id)}" ${isSelected?'checked':''}
                onchange="toggleTravelerSelect('${esc(t.traveler_sys_id)}', this.checked)">
            <div class="avatar">${(t.traveler_name||'?')[0].toUpperCase()}</div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <p class="font-semibold text-sm text-gray-800 truncate">${esc(t.traveler_name)}</p>
                    ${isLeader ? '<span class="leader-badge"><i class="fas fa-star text-[9px]"></i>Leader</span>' : ''}
                    ${getFlightBadge(t.traveler_sys_id)}
                </div>
                <p class="text-xs text-gray-400 mt-0.5">
                    ${t.passport_no ? '<i class="fas fa-passport mr-1 text-gray-300"></i>' + esc(t.passport_no) : ''}
                    <span id="phone_view_${esc(t.traveler_sys_id)}" class="inline-flex items-center gap-1">
                        ${t.roaming_phone
                            ? '&nbsp;<i class="fas fa-phone text-gray-300 text-[10px]"></i><span class="text-gray-400"> ' + esc(t.roaming_phone) + '</span>'
                            : ''}
                        <button onclick="showPhoneEdit('${esc(t.traveler_sys_id)}','${esc(t.roaming_phone||'')}')"
                            class="ml-1 text-violet-400 hover:text-violet-600 text-[10px]">
                            <i class="fas fa-${t.roaming_phone?'pen':'plus'}"></i>${t.roaming_phone?'':' Add phone'}
                        </button>
                    </span>
                    <span id="phone_edit_${esc(t.traveler_sys_id)}" class="hidden inline-flex items-center gap-1 ml-1">
                        <input id="phone_input_${esc(t.traveler_sys_id)}" type="tel"
                            class="border border-violet-300 rounded-lg px-2 py-0.5 text-xs text-gray-700 outline-none focus:border-violet-500 w-36"
                            placeholder="+966 5X XXX XXXX" value="${esc(t.roaming_phone||'')}">
                        <button onclick="saveRoamingPhone('${esc(t.traveler_sys_id)}')"
                            class="text-xs px-2 py-0.5 bg-violet-600 text-white rounded-lg font-semibold hover:bg-violet-700">
                            <i class="fas fa-check"></i>
                        </button>
                        <button onclick="cancelPhoneEdit('${esc(t.traveler_sys_id)}')"
                            class="text-xs px-2 py-0.5 text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </span>
                </p>
            </div>
            <div class="flex gap-2 flex-shrink-0">
                <button onclick="triggerNocUpload('${esc(t.traveler_sys_id)}')"
                    class="text-xs px-3 py-1.5 border border-violet-200 text-violet-600 rounded-lg hover:bg-violet-50 transition font-semibold flex items-center gap-1">
                    <i class="fas fa-file-alt"></i>NOC
                </button>
                <button onclick="generateIdCard('${esc(t.traveler_sys_id)}')"
                    class="text-xs px-3 py-1.5 border border-indigo-200 text-indigo-600 rounded-lg hover:bg-indigo-50 transition font-semibold">
                    <i class="fas fa-id-card mr-1"></i>ID Card
                </button>
                <button onclick="removeTraveler('${esc(t.traveler_sys_id)}')"
                    class="text-xs px-2 py-1.5 text-red-400 hover:text-red-600 transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>`;
    }).join('');
}

function toggleTravelerSelect(sysId, checked) {
    if (checked) selectedTravelers.add(sysId); else selectedTravelers.delete(sysId);
}
function toggleSelectAll() {
    const allSelected = selectedTravelers.size === travelers.length;
    if (allSelected) selectedTravelers.clear();
    else travelers.forEach(t => selectedTravelers.add(t.traveler_sys_id));
    renderTravelers();
    document.getElementById('selectAllBtn').textContent = selectedTravelers.size === travelers.length ? 'Deselect All' : 'Select All';
}

async function removeTraveler(sysId) {
    if (!confirm('Remove this traveler from the group?')) return;
    const res = await fetch(API, { method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ action:'remove_traveler', group_sys_id: GROUP_SYS_ID, traveler_sys_id: sysId }) });
    const j = await res.json();
    if (j.status === 'success') { toast('success','Removed'); await loadTravelers(); }
    else toast('error', j.message);
}

// ══════════════════════════════════════════════════════════════
// TICKET EXTRACTION
// ══════════════════════════════════════════════════════════════

async function handleTicketUpload(input) {
    const file = input.files[0]; if (!file) return;
    const panel = document.getElementById('ticketExtractPanel');
    panel.classList.remove('hidden');
    panel.innerHTML = `
    <div class="bg-white border border-violet-200 rounded-xl p-5 text-center">
        <div class="inline-block w-8 h-8 border-3 border-violet-200 border-t-violet-600 rounded-full animate-spin mb-3"
             style="border-width:3px;animation:spin 1s linear infinite;"></div>
        <p class="text-sm text-gray-500 font-medium">Extracting ticket data…</p>
        <p class="text-xs text-gray-400 mt-1">AI is reading PNR, passengers and flight details</p>
    </div>`;

    const fd = new FormData();
    fd.append('file', file);
    fd.append('group_sys_id', GROUP_SYS_ID);

    try {
        const res  = await fetch(EXTRACT_TICKET_API, { method:'POST', body: fd });
        const json = await res.json();
        input.value = '';
        if (!json.success) { panel.innerHTML = `<div class="bg-red-50 border border-red-200 rounded-xl p-4 text-sm text-red-600">⚠️ ${esc(json.message)}</div>`; return; }
        renderTicketExtractResult(json);
    } catch(e) {
        panel.innerHTML = `<div class="bg-red-50 border border-red-200 rounded-xl p-4 text-sm text-red-600">⚠️ Network error: ${esc(e.message)}</div>`;
        input.value = '';
    }
}

function renderTicketExtractResult(json) {
    const panel   = document.getElementById('ticketExtractPanel');
    const ticket  = json.ticket;
    const matched = json.matched ?? [];
    const unmatched_ticket = json.unmatched_ticket_names ?? [];
    const unmatched_travelers = json.unmatched_travelers ?? [];

    const matchRows = matched.map(m => `
    <div class="flex items-center gap-3 py-2 border-b border-gray-50 last:border-0">
        <span class="text-sm flex-shrink-0">
            ${m.match === 'exact' ? '✅' : m.match === 'fuzzy' ? '🔶' : '❌'}
        </span>
        <div class="flex-1 min-w-0">
            <span class="text-xs font-mono text-gray-600">${esc(m.ticket_name)}</span>
            ${m.traveler_name
                ? `<span class="text-gray-400 text-xs mx-1">→</span>
                   <span class="text-xs font-semibold text-gray-800">${esc(m.traveler_name)}</span>
                   ${m.match === 'fuzzy' ? '<span class="text-[10px] text-amber-600 ml-1">(fuzzy)</span>' : ''}`
                : `<span class="text-xs text-red-500 ml-1">not in group</span>`}
        </div>
    </div>`).join('');

    const unmatchedTravRows = unmatched_travelers.map(t => `
    <div class="flex items-center gap-2 py-1">
        <span>⚠️</span>
        <span class="text-xs text-gray-600">${esc(t.name)}</span>
        <span class="text-[10px] text-gray-400 ml-1">— not on this ticket</span>
    </div>`).join('');

    const legsHtml = (ticket.legs||[]).map((l,i) => `
    <span class="text-xs bg-violet-100 text-violet-700 px-2 py-0.5 rounded font-semibold">
        Leg ${i+1}: ${esc(l.from||'?')} → ${esc(l.to||'?')} ${l.dep_date?'· '+esc(l.dep_date):''}
    </span>`).join('');

    // Build passengers array for saving
    const passengersToSave = matched.map(m => ({
        ticket_name:     m.ticket_name,
        traveler_sys_id: m.traveler_sys_id,
        traveler_name:   m.traveler_name,
        match:           m.match,
    }));

    // Store for confirm button
    window._pendingTicket = { ticket, passengers: passengersToSave };

    panel.innerHTML = `
    <div class="bg-white border border-violet-200 rounded-xl overflow-hidden">
        <!-- Ticket header -->
        <div class="bg-violet-50 border-b border-violet-100 px-4 py-3 flex items-center gap-3 flex-wrap">
            <i class="fas fa-ticket-alt text-violet-500"></i>
            <span class="font-bold text-violet-800 text-sm">${esc(ticket.airline||'Airline')}</span>
            ${ticket.pnr ? `<span class="font-mono text-xs bg-white border border-violet-300 text-violet-700 px-2 py-0.5 rounded">PNR: ${esc(ticket.pnr)}</span>` : ''}
            ${ticket.flight_number ? `<span class="text-xs text-gray-500">${esc(ticket.flight_number)}</span>` : ''}
            ${ticket.class ? `<span class="text-xs text-gray-500 capitalize">${esc(ticket.class)}</span>` : ''}
            <div class="flex gap-1 flex-wrap">${legsHtml}</div>
        </div>

        <!-- Match results -->
        <div class="px-4 py-3">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">
                Passenger Match (${matched.filter(m=>m.match!=='none').length}/${matched.length} matched)
            </p>
            ${matchRows}
            ${unmatchedTravRows ? `
            <div class="mt-2 pt-2 border-t border-gray-100">
                <p class="text-xs font-bold text-gray-400 mb-1">Group travelers not on this ticket:</p>
                ${unmatchedTravRows}
            </div>` : ''}
        </div>

        <!-- Actions -->
        <div class="px-4 pb-4 flex gap-2">
            <button onclick="confirmTicketAsFlight()"
                class="flex-1 py-2 bg-violet-600 hover:bg-violet-700 text-white text-xs font-bold rounded-lg transition">
                <i class="fas fa-check mr-1"></i>Save as New Flight
            </button>
            <button onclick="document.getElementById('ticketExtractPanel').classList.add('hidden')"
                class="px-4 py-2 border border-gray-200 text-gray-500 text-xs font-semibold rounded-lg hover:bg-gray-50 transition">
                Discard
            </button>
        </div>
    </div>`;
}

async function confirmTicketAsFlight() {
    const t = window._pendingTicket;
    if (!t) return;

    const newFlight = {
        flight_no:  (groupData.segments ?? []).length + 1,
        type:       t.ticket.legs?.length > 1 ? 'multi_city' : 'one_way',
        airline:    t.ticket.airline    || '',
        pnr:        t.ticket.pnr        || '',
        class:      (t.ticket.class     || 'Economy').toLowerCase(),
        legs:       (t.ticket.legs      || []).map(l => ({
            from:     l.from || '', to: l.to || '',
            dep_date: l.dep_date || '', dep_time: l.dep_time || '',
            arr_date: l.arr_date || '', arr_time: l.arr_time || '',
        })),
        passengers: t.passengers,
    };

    const segments = [...(groupData.segments ?? []), newFlight];
    await doUpdate({ segments });
    groupData.segments = segments;
    window._pendingTicket = null;
    document.getElementById('ticketExtractPanel').classList.add('hidden');
    document.getElementById('ticketFileInput').value = '';
    renderFlights();
    renderTravelers();  // refresh to show flight badges
    toast('success', `Flight saved — PNR: ${newFlight.pnr || 'N/A'}`);
}

// ── Per-traveler flight badge lookup ──────────────────────────
function getFlightBadge(travelerSysId) {
    const segs     = groupData.segments ?? [];
    const flights  = segs.filter(s => s.legs);
    const found    = [];

    for (const f of flights) {
        const match = (f.passengers ?? []).find(p => p.traveler_sys_id === travelerSysId);
        if (!match) continue;

        // Build route from first leg
        const leg      = f.legs?.[0] ?? {};
        const route    = (leg.from && leg.to) ? `${esc(leg.from)}→${esc(leg.to)}` : '';
        const depDate  = leg.dep_date
            ? new Date(leg.dep_date + 'T00:00:00').toLocaleDateString('en-BD',{day:'2-digit',month:'short'})
            : '';

        found.push({
            pnr:      f.pnr     || '—',
            airline:  f.airline || '',
            route,
            depDate,
            match:    match.match,
        });
    }

    if (!found.length) {
        const hasFlights = segs.some(s => s.pnr);
        return hasFlights
            ? `<span class="text-[10px] text-amber-600 font-semibold ml-2">⚠️ No flight</span>`
            : '';
    }

    return found.map(f => `
        <span class="inline-flex items-center gap-1 text-[10px] px-2 py-0.5 rounded-full font-semibold ml-1
            ${f.match==='exact' ? 'bg-violet-100 text-violet-700' : 'bg-amber-100 text-amber-700'}">
            ✈️
            ${f.airline ? esc(f.airline)+' · ' : ''}
            ${esc(f.pnr)}
            ${f.route   ? ' · '+f.route   : ''}
            ${f.depDate ? ' · '+f.depDate : ''}
        </span>`
    ).join('');
}

// ══════════════════════════════════════════════════════════════
// FLIGHTS — render + edit
// ══════════════════════════════════════════════════════════════

function renderFlights() {
    const segs = groupData.segments ?? [];
    const el   = document.getElementById('flightsContent');
    if (!segs.length) { el.innerHTML = '<p class="text-sm text-gray-400 py-4">No flights added yet.</p>'; return; }

    // Backward compat: flat segment (no legs array) → wrap as single-leg flight
    const flights = segs.map((s, i) => s.legs
        ? s
        : {
            flight_no: i + 1,
            type:    s.type    || 'one_way',
            airline: s.airline || '',
            class:   s.class   || 'economy',
            pnr:     s.pnr     || '',
            legs: [{
                from:     s.from_airport   || '',
                to:       s.to_airport     || '',
                dep_date: s.departure_date || '',
                dep_time: s.departure_time || '',
                arr_date: s.arrival_date   || '',
                arr_time: s.arrival_time   || '',
            }]
        }
    );

    el.innerHTML = flights.map((f, fi) => `
    <div class="flight-block">
        <div class="flight-header">
            <div class="w-7 h-7 bg-violet-600 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="fas fa-plane text-white text-xs"></i>
            </div>
            <span class="text-xs font-bold text-violet-700">Flight ${fi + 1}</span>
            ${f.airline ? `<span class="text-xs text-gray-600 font-semibold">${esc(f.airline)}</span>` : ''}
            ${f.pnr ? `<span class="text-xs font-mono bg-white border border-violet-200 text-violet-600 px-2 py-0.5 rounded">${esc(f.pnr)}</span>` : ''}
            <span class="ml-auto text-xs font-semibold text-violet-500 bg-white border border-violet-100 px-2 py-0.5 rounded capitalize">
                ${(f.type||'one_way').replace('_',' ')}
            </span>
            <span class="text-xs text-gray-400 capitalize">${esc(f.class||'economy')}</span>
            <button onclick="deleteFlight(${fi})" title="Remove flight"
                class="text-red-400 hover:text-red-600 text-xs ml-1 flex-shrink-0">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        <div>
            ${(f.legs||[]).map((l, li) => `
            <div class="leg-row">
                <span class="text-[10px] font-bold text-gray-400 w-10 flex-shrink-0">Leg ${li+1}</span>
                <span class="font-bold text-gray-800 text-sm">${esc(l.from)}</span>
                <i class="fas fa-arrow-right text-gray-300 text-xs"></i>
                <span class="font-bold text-gray-800 text-sm">${esc(l.to)}</span>
                <span class="text-xs text-gray-500 ml-2">${esc(l.dep_date)}${l.dep_time?' '+esc(l.dep_time):''}</span>
                ${l.arr_date ? `<i class="fas fa-arrow-right text-gray-200 text-xs mx-1"></i><span class="text-xs text-gray-400">${esc(l.arr_date)}${l.arr_time?' '+esc(l.arr_time):''}</span>` : ''}
            </div>`).join('')}
            ${(f.passengers||[]).length ? `
            <div class="px-4 py-2 bg-gray-50 border-t border-gray-100">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide mb-1.5">Passengers (${f.passengers.length})</p>
                <div class="flex flex-wrap gap-1.5">
                    ${f.passengers.map(p => `
                    <span class="inline-flex items-center gap-1 text-[10px] px-2 py-0.5 rounded-full font-semibold
                        ${p.match==='exact'?'bg-green-100 text-green-700':p.match==='fuzzy'?'bg-amber-100 text-amber-700':'bg-red-100 text-red-600'}">
                        ${p.match==='exact'?'✅':p.match==='fuzzy'?'🔶':'❌'} ${esc(p.traveler_name||p.ticket_name)}
                    </span>`).join('')}
                </div>
            </div>` : ''}
        </div>
    </div>`).join('');
}

async function deleteFlight(displayIndex) {
    if (!confirm(`Flight ${displayIndex + 1} মুছবেন?`)) return;
    const segs = groupData.segments ?? [];
    // displayIndex is 0-based from the rendered map
    segs.splice(displayIndex, 1);
    // Re-number flight_no
    segs.forEach((s, i) => { if (s.flight_no !== undefined) s.flight_no = i + 1; });
    await doUpdate({ segments: segs });
    groupData.segments = segs;
    renderFlights();
    renderTravelers();
    toast('success', 'Flight removed');
}

// ── Flights edit ───────────────────────────────────────────────
let flightEditCount = 0;

function prefillFlights() {
    flightEditCount = 0;
    document.getElementById('flightsEditContainer').innerHTML = '';
    const segs = groupData.segments ?? [];
    if (!segs.length) { addFlight(); return; }
    segs.forEach(s => {
        if (!s.legs) {
            // backward compat wrap
            addFlight({
                type:    s.type    || 'one_way',
                airline: s.airline || '',
                class:   s.class   || 'economy',
                pnr:     s.pnr     || '',
                legs: [{ from: s.from_airport||'', to: s.to_airport||'',
                         dep_date: s.departure_date||'', dep_time: s.departure_time||'',
                         arr_date: s.arrival_date||'',   arr_time: s.arrival_time||'' }]
            });
        } else {
            addFlight(s);
        }
    });
}

function addFlight(data = {}) {
    const fi   = ++flightEditCount;
    const type = data.type || 'one_way';
    const c    = document.createElement('div');
    c.id        = 'eflight_' + fi;
    c.className = 'border border-gray-200 rounded-xl overflow-hidden bg-white';
    c.innerHTML = `
    <div class="flex items-center gap-2 flex-wrap px-4 py-2.5 bg-violet-50 border-b border-violet-100">
        <span class="text-xs font-bold text-violet-700 flex-shrink-0">Flight ${fi}</span>
        <select class="efl-type text-xs border border-violet-200 rounded-lg px-2 py-1 bg-white text-violet-700 font-semibold focus:outline-none"
                onchange="onFlightTypeChange(${fi})">
            <option value="one_way"    ${type==='one_way'   ?'selected':''}>One Way</option>
            <option value="round"      ${type==='round'     ?'selected':''}>Round Trip</option>
            <option value="multi_city" ${type==='multi_city'?'selected':''}>Multi City</option>
        </select>
        <input class="efl-airline fi text-xs" style="width:130px;padding:5px 8px;" placeholder="Airline" value="${esc(data.airline||'')}">
        <input class="efl-pnr fi text-xs font-mono" style="width:100px;padding:5px 8px;" placeholder="PNR" value="${esc(data.pnr||'')}">
        <select class="efl-class text-xs border border-gray-200 rounded-lg px-2 py-1 bg-white focus:outline-none">
            <option value="economy"  ${(data.class||'economy')==='economy' ?'selected':''}>Economy</option>
            <option value="business" ${data.class==='business'?'selected':''}>Business</option>
            <option value="first"    ${data.class==='first'   ?'selected':''}>First</option>
        </select>
        <button type="button" onclick="document.getElementById('eflight_${fi}').remove()"
            class="ml-auto text-red-400 hover:text-red-600 text-xs"><i class="fas fa-times"></i></button>
    </div>
    <div class="efl-legs divide-y divide-gray-100 px-4 py-1"></div>
    <div class="efl-add-leg-wrap px-4 pb-2 ${type==='multi_city'?'':'hidden'}">
        <button type="button" onclick="addLeg(${fi})"
            class="text-xs text-violet-500 font-semibold hover:underline mt-1">
            <i class="fas fa-plus mr-1"></i>Add Leg
        </button>
    </div>`;
    document.getElementById('flightsEditContainer').appendChild(c);

    const legs = data.legs || [];
    if (type === 'round') {
        addLeg(fi, legs[0] || {}); addLeg(fi, legs[1] || {});
    } else if (type === 'multi_city') {
        if (legs.length) legs.forEach(l => addLeg(fi, l));
        else { addLeg(fi); addLeg(fi); }
    } else {
        addLeg(fi, legs[0] || {});
    }
}

function onFlightTypeChange(fi) {
    const c    = document.getElementById('eflight_' + fi);
    const type = c.querySelector('.efl-type').value;
    const addW = c.querySelector('.efl-add-leg-wrap');
    if (addW) addW.classList.toggle('hidden', type !== 'multi_city');
    const legsEl   = c.querySelector('.efl-legs');
    legsEl.innerHTML = '';
    c._legCount     = 0;
    if (type === 'round') { addLeg(fi); addLeg(fi); }
    else { addLeg(fi); if (type === 'multi_city') addLeg(fi); }
}

function addLeg(fi, data = {}) {
    const c = document.getElementById('eflight_' + fi);
    if (!c) return;
    const legsEl = c.querySelector('.efl-legs');
    c._legCount  = (c._legCount || 0) + 1;
    const li     = c._legCount;
    const leg    = document.createElement('div');
    leg.className = 'eleg py-2';
    leg.innerHTML = `
    <div class="flex items-center gap-2 flex-wrap">
        <span class="text-[10px] text-gray-400 font-bold w-8 flex-shrink-0">Leg ${li}</span>
        <input class="eleg-from fi text-xs" style="width:54px;padding:5px 8px;" placeholder="From" value="${esc(data.from||data.from_airport||'')}">
        <i class="fas fa-arrow-right text-gray-300 text-xs"></i>
        <input class="eleg-to fi text-xs" style="width:54px;padding:5px 8px;" placeholder="To" value="${esc(data.to||data.to_airport||'')}">
        <input class="eleg-dep-date fi text-xs" style="width:120px;padding:5px 8px;" type="date" value="${esc(data.dep_date||data.departure_date||'')}">
        <input class="eleg-dep-time fi text-xs" style="width:86px;padding:5px 8px;" type="time" value="${esc(data.dep_time||data.departure_time||'')}">
        <span class="text-gray-300 text-xs">→</span>
        <input class="eleg-arr-date fi text-xs" style="width:120px;padding:5px 8px;" type="date" value="${esc(data.arr_date||data.arrival_date||'')}">
        <input class="eleg-arr-time fi text-xs" style="width:86px;padding:5px 8px;" type="time" value="${esc(data.arr_time||data.arrival_time||'')}">
        ${li > 1 ? `<button type="button" onclick="this.closest('.eleg').remove()"
            class="text-red-400 text-xs ml-auto"><i class="fas fa-times"></i></button>` : ''}
    </div>`;
    legsEl.appendChild(leg);
}

async function saveFlights() {
    const flights = [...document.querySelectorAll('[id^="eflight_"]')].map((c, fi) => {
        const legs = [...c.querySelectorAll('.eleg')].map(l => ({
            from:     l.querySelector('.eleg-from')?.value.trim().toUpperCase() || '',
            to:       l.querySelector('.eleg-to')?.value.trim().toUpperCase()   || '',
            dep_date: l.querySelector('.eleg-dep-date')?.value || '',
            dep_time: l.querySelector('.eleg-dep-time')?.value || '',
            arr_date: l.querySelector('.eleg-arr-date')?.value || '',
            arr_time: l.querySelector('.eleg-arr-time')?.value || '',
        }));
        return {
            flight_no: fi + 1,
            type:    c.querySelector('.efl-type')?.value    || 'one_way',
            airline: c.querySelector('.efl-airline')?.value.trim() || '',
            pnr:     c.querySelector('.efl-pnr')?.value.trim()     || '',
            class:   c.querySelector('.efl-class')?.value   || 'economy',
            legs,
        };
    });
    await doUpdate({ segments: flights });
    groupData.segments = flights;
    renderFlights();
    toggleEdit('flights');
}

// ── Hotels ─────────────────────────────────────────────────────
function renderHotels() {
    const h  = groupData.hotels ?? {};
    const el = document.getElementById('hotelsContent');
    const makeCard = (city, data) => !data?.name ? '' : `
        <div class="hotel-chip">
            <p class="text-xs font-bold text-green-700 mb-2 uppercase tracking-wide">${city}</p>
            <p class="font-semibold text-gray-800">${esc(data.name)}</p>
            ${data.address ? `<p class="text-xs text-gray-400 mt-0.5"><i class="fas fa-map-marker-alt mr-1"></i>${esc(data.address)}</p>` : ''}
            <p class="text-xs text-gray-500 mt-1">Check-in: ${esc(data.checkin||'—')} · Check-out: ${esc(data.checkout||'—')}</p>
        </div>`;
    el.innerHTML = makeCard('Makkah', h.makkah) + makeCard('Madinah', h.madinah) ||
        '<p class="col-span-2 text-sm text-gray-400">No hotels added.</p>';
}

function prefillHotels() {
    const h = groupData.hotels ?? {};
    document.getElementById('edit_makkah_name').value      = h.makkah?.name     || '';
    document.getElementById('edit_makkah_checkin').value   = h.makkah?.checkin  || '';
    document.getElementById('edit_makkah_checkout').value  = h.makkah?.checkout || '';
    document.getElementById('edit_makkah_address').value   = h.makkah?.address  || '';
    document.getElementById('edit_madinah_name').value     = h.madinah?.name    || '';
    document.getElementById('edit_madinah_checkin').value  = h.madinah?.checkin  || '';
    document.getElementById('edit_madinah_checkout').value = h.madinah?.checkout || '';
    document.getElementById('edit_madinah_address').value  = h.madinah?.address  || '';
}

async function saveHotels() {
    const hotels = {
        makkah:  { name: document.getElementById('edit_makkah_name').value.trim(),  checkin: document.getElementById('edit_makkah_checkin').value,  checkout: document.getElementById('edit_makkah_checkout').value,  address: document.getElementById('edit_makkah_address').value.trim()  },
        madinah: { name: document.getElementById('edit_madinah_name').value.trim(), checkin: document.getElementById('edit_madinah_checkin').value, checkout: document.getElementById('edit_madinah_checkout').value, address: document.getElementById('edit_madinah_address').value.trim() },
    };
    await doUpdate({ hotels });
    groupData.hotels = hotels;
    renderHotels();
    toggleEdit('hotels');
}

// ── Itinerary ──────────────────────────────────────────────────
function renderItinerary() {
    const items = groupData.itinerary ?? [];
    const el    = document.getElementById('itinContent');
    if (!items.length) { el.innerHTML = '<p class="text-sm text-gray-400">No itinerary added.</p>'; return; }
    el.innerHTML = items.map(it => `
        <div class="itin-item">
            <div class="itin-dot"></div>
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-1">
                    <span class="text-xs font-bold text-violet-600">${esc(it.date)} ${it.time ? '· ' + esc(it.time) : ''}</span>
                    <span class="font-semibold text-gray-800 text-sm">${esc(it.title)}</span>
                </div>
                ${it.description ? `<div class="text-sm text-gray-500">${esc(it.description.replace(/<[^>]+>/g,'').trim())}</div>` : ''}
            </div>
        </div>`).join('');
}

let itinEditCount = 0;
function prefillItinerary() {
    itinEditCount = 0;
    document.getElementById('itinEditContainer').innerHTML = '';
    const items = groupData.itinerary ?? [];
    if (items.length) items.forEach(it => addEditItin(it)); else addEditItin();
}

function addEditItin(data = {}) {
    const i = ++itinEditCount;
    const c = document.createElement('div');
    c.id = 'eitin_' + i;
    c.className = 'bg-white border border-gray-200 rounded-xl overflow-hidden';
    // Strip HTML tags for plain text display in description
    const plainDesc = (data.description || '').replace(/<[^>]+>/g, '').trim();
    c.innerHTML = `
        <div class="flex items-center gap-3 px-4 py-2.5 bg-gray-50 border-b border-gray-100">
            <span class="text-xs font-bold text-violet-600 flex-shrink-0">Item ${i}</span>
            <input class="eitin-title flex-1 border-none outline-none bg-transparent text-sm font-semibold text-gray-800 placeholder-gray-300"
                   type="text" placeholder="e.g. Arrive Madinah, Hotel check-in…"
                   value="${esc(data.title||'')}">
            <input class="eitin-date border border-gray-200 rounded-lg px-2 py-1 text-xs text-gray-600 outline-none focus:border-violet-300"
                   type="date" style="width:120px" value="${esc(data.date||'')}">
            <input class="eitin-time border border-gray-200 rounded-lg px-2 py-1 text-xs text-gray-600 outline-none focus:border-violet-300"
                   type="time" style="width:90px" value="${esc(data.time||'')}">
            <button type="button" onclick="document.getElementById('eitin_${i}').remove()"
                class="text-red-400 hover:text-red-600 text-xs flex-shrink-0 ml-1">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="px-4 py-2.5">
            <textarea class="eitin-desc w-full text-sm text-gray-600 outline-none resize-none bg-transparent placeholder-gray-300"
                      rows="2" placeholder="Description (optional)…">${esc(plainDesc)}</textarea>
        </div>`;
    document.getElementById('itinEditContainer').appendChild(c);
}

async function saveItinerary() {
    const itinerary = [...document.querySelectorAll('[id^="eitin_"]')].map((c, i) => ({
        sys_id:      'IT-' + String(i+1).padStart(3,'0'),
        date:        c.querySelector('.eitin-date')?.value       || '',
        time:        c.querySelector('.eitin-time')?.value       || '',
        title:       c.querySelector('.eitin-title')?.value.trim() || '',
        description: c.querySelector('.eitin-desc')?.value.trim() || '',
    }));
    await doUpdate({ itinerary });
    groupData.itinerary = itinerary;
    renderItinerary();
    toggleEdit('itinerary');
}

// ── Edit toggle ────────────────────────────────────────────────
function toggleEdit(section) {
    const viewMap = { flights:'flightsContent', hotels:'hotelsContent', itinerary:'itinContent' };
    const editMap = { flights:'flightsEdit',    hotels:'hotelsEdit',    itinerary:'itineraryEdit' };
    const btnMap  = { flights:'editBtn-flights', hotels:'editBtn-hotels', itinerary:'editBtn-itinerary' };
    const viewEl  = document.getElementById(viewMap[section]);
    const editEl  = document.getElementById(editMap[section]);
    const btn     = document.getElementById(btnMap[section]);
    const isEditing = !editEl.classList.contains('hidden');
    if (isEditing) {
        editEl.classList.add('hidden'); viewEl.classList.remove('hidden');
        btn.innerHTML = '<i class="fas fa-pen mr-1"></i>Edit ' + section.charAt(0).toUpperCase() + section.slice(1);
    } else {
        editEl.classList.remove('hidden'); viewEl.classList.add('hidden');
        btn.innerHTML = '<i class="fas fa-times mr-1"></i>Cancel';
        if (section === 'flights')   prefillFlights();
        if (section === 'hotels')    prefillHotels();
        if (section === 'itinerary') prefillItinerary();
    }
}

// ── Shared update ──────────────────────────────────────────────
async function doUpdate(payload) {
    try {
        const res = await fetch(API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'update', sys_id: GROUP_SYS_ID, ...payload }),
        });
        const j = await res.json();
        if (j.status === 'success') toast('success', 'Saved!');
        else toast('error', j.message || 'Save failed');
    } catch(e) { toast('error', 'Network error: ' + e.message); }
}

// ── Tabs ───────────────────────────────────────────────────────
function switchTab(name) {
    document.querySelectorAll('.tab-btn').forEach((b,i) => {
        const names = ['travelers','flights','hotels','itinerary'];
        b.classList.toggle('active', names[i] === name);
    });
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.toggle('active', p.id === 'tab-' + name));
}

// ── Add Traveler Modal ─────────────────────────────────────────
function openAddTravelerModal() { document.getElementById('addTravelerModal').classList.remove('hidden'); setAddMethod(null); }
function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

function setAddMethod(method) {
    ['methodSelect','scanMethod','manualMethod','existingMethod'].forEach(id =>
        document.getElementById(id).classList.add('hidden'));
    if (!method) { document.getElementById('methodSelect').classList.remove('hidden'); return; }
    document.getElementById(method + 'Method').classList.remove('hidden');
    if (method === 'manual') renderTravelerFormFields(null, 'manualMethod');
}

function renderTravelerFormFields(data, containerId) {
    const d = data ?? {};
    document.getElementById(containerId === 'scanMethod' ? 'scanForm' : 'travelerFormFields').innerHTML = `
        <div class="space-y-3">
            <div class="grid grid-cols-2 gap-3">
                <div><label class="lbl">Salutation</label>
                    <select class="fi" id="tf_salutation">
                        <option value="">—</option>
                        ${['Mr','Mrs','Ms','Dr','Haji','Hajjah'].map(s => `<option ${d.salutation===s?'selected':''}>${s}</option>`).join('')}
                    </select>
                </div>
                <div><label class="lbl">Gender</label>
                    <select class="fi" id="tf_gender">
                        <option value="">—</option>
                        <option value="male"   ${d.gender==='male'  ?'selected':''}>Male</option>
                        <option value="female" ${d.gender==='female'?'selected':''}>Female</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="lbl">Given Name <span class="text-red-400">*</span></label><input class="fi" type="text" id="tf_given" placeholder="Given name" value="${esc(d.given_name??'')}"></div>
                <div><label class="lbl">Surname</label><input class="fi" type="text" id="tf_surname" placeholder="Surname" value="${esc(d.surname??'')}"></div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="lbl">Date of Birth</label><input class="fi" type="text" id="tf_dob" placeholder="DD-MM-YYYY" value="${esc(d.date_of_birth??'')}"></div>
                <div><label class="lbl">Passport Number</label><input class="fi" type="text" id="tf_passport" placeholder="Passport No" value="${esc(d.passport_no??'')}"></div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="lbl">Passport Expiry</label><input class="fi" type="text" id="tf_expiry" placeholder="DD-MM-YYYY" value="${esc(d.expiry_date??'')}"></div>
                <div><label class="lbl">Father's Name</label><input class="fi" type="text" id="tf_father" placeholder="Father's name" value="${esc(d.father_name??'')}"></div>
            </div>
            <div><label class="lbl">Emergency Contact</label><input class="fi" type="text" id="tf_emergency" placeholder="Name · Phone" value="${esc(d.emergency_contact??'')}"></div>
            <div><label class="lbl">Roaming Phone (Saudi)</label><input class="fi" type="tel" id="tf_roaming" placeholder="+966 5X XXX XXXX" value="${esc(d.roaming_phone??'')}"></div>
            <button type="button" onclick="saveTravelerForm()" class="w-full py-2.5 bg-violet-600 hover:bg-violet-700 text-white font-bold rounded-xl transition text-sm mt-2">
                <i class="fas fa-user-plus mr-2"></i>Add to Group
            </button>
        </div>`;
    if (containerId === 'scanMethod') document.getElementById('scanForm').classList.remove('hidden');
}

async function extractPassport(input) {
    const file = input.files[0]; if (!file) return;
    document.getElementById('scanUploadZone').classList.add('hidden');
    document.getElementById('scanLoading').classList.remove('hidden');
    try {
        const fd = new FormData(); fd.append('file', file); fd.append('document_type', 'passport');
        const res = await fetch(EXTRACT_API, { method:'POST', body: fd });
        const j   = await res.json();
        document.getElementById('scanLoading').classList.add('hidden');
        if (j.success) {
            // display_data = simplified, bio_info = full details
            const bio = j.full_extracted_data?.bio_info ?? j.full_extracted_data ?? {};
            const dis = j.data ?? {};

            // Store for passing to store.php
            window._scanFilePath      = j.file_path      ?? null;
            window._scanDocType       = j.document_type  ?? 'passport';
            window._scanExtractedData = j.full_extracted_data ?? j.data ?? null;
            window._scanDocNumber     = bio.passport_number ?? dis.document_number ?? null;

            const sexRaw = (bio.sex ?? bio.gender ?? '').toLowerCase();
            renderTravelerFormFields({
                given_name:        bio.given_names   ?? bio.given_name   ?? '',
                surname:           bio.surname        ?? bio.last_name    ?? '',
                salutation:        bio.salutation     ?? '',
                gender:            sexRaw === 'm' || sexRaw === 'male'   ? 'male'
                                 : sexRaw === 'f' || sexRaw === 'female' ? 'female' : '',
                date_of_birth:     bio.date_of_birth  ?? dis.date_of_birth ?? '',
                passport_no:       bio.passport_number ?? dis.document_number ?? '',
                expiry_date:       bio.expiry_date     ?? bio.date_of_expiry  ?? '',
                father_name:       bio.father_name     ?? '',
                emergency_contact: bio.emergency_contact ?? '',
            }, 'scanMethod');
        } else {
            document.getElementById('scanUploadZone').classList.remove('hidden');
            toast('error', j.message || 'Extraction failed — try manual entry');
        }
    } catch(e) {
        document.getElementById('scanLoading').classList.add('hidden');
        document.getElementById('scanUploadZone').classList.remove('hidden');
        toast('error', 'Network error during extraction');
    }
}

async function saveTravelerForm() {
    const given = document.getElementById('tf_given')?.value.trim();
    if (!given) { toast('error','Given name is required'); return; }
    const fullName = [document.getElementById('tf_salutation')?.value, given, document.getElementById('tf_surname')?.value.trim()].filter(Boolean).join(' ');
    const travelerData = {
        full_name:    fullName,
        passport_no:  document.getElementById('tf_passport')?.value.trim(),
        date_of_birth: document.getElementById('tf_dob')?.value.trim(),
        personal_info: { gender: document.getElementById('tf_gender')?.value, salutation: document.getElementById('tf_salutation')?.value },
        passport_info: { given_name: given, surname: document.getElementById('tf_surname')?.value.trim(), expiry_date: document.getElementById('tf_expiry')?.value.trim() },
        family_info:   { father_name: document.getElementById('tf_father')?.value.trim(), emergency_contact: document.getElementById('tf_emergency')?.value.trim() },
    };
    const roaming = document.getElementById('tf_roaming')?.value.trim();
    try {
        // Attach passport scan data if available
        if (window._scanFilePath)      travelerData.file_path      = window._scanFilePath;
        if (window._scanDocType)       travelerData.document_type  = window._scanDocType;
        if (window._scanExtractedData) travelerData.extracted_data = window._scanExtractedData;
        if (window._scanDocNumber)     travelerData.document_number= window._scanDocNumber;

        const r1 = await fetch(STORE_TRAVELER_API, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(travelerData) });
        const j1 = await r1.json();
        if (!j1.success) { toast('error', j1.message || 'Failed to create traveler'); return; }
        const r2 = await fetch(API, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ action:'add_traveler', group_sys_id: GROUP_SYS_ID, traveler_sys_id: j1.sys_id, roaming_phone: roaming }) });
        const j2 = await r2.json();
        if (j2.status !== 'success') { toast('error', j2.message || 'Failed to link traveler'); return; }
        // Clear scan state
        window._scanFilePath = window._scanDocType = window._scanExtractedData = window._scanDocNumber = null;
        toast('success','Traveler added!'); closeModal('addTravelerModal'); await loadTravelers();
    } catch(e) { toast('error', 'Network error: ' + e.message); }
}

let _searchTimer;
async function searchExistingTraveler(q) {
    clearTimeout(_searchTimer);
    if (q.length < 2) { document.getElementById('existingResults').innerHTML = ''; return; }
    _searchTimer = setTimeout(async () => {
        const res = await fetch(`${TRAVELER_API}all-travelers.php?search=${encodeURIComponent(q)}&limit=10`);
        const j   = await res.json();
        const list = j.travelers ?? j.data ?? [];
        document.getElementById('existingResults').innerHTML = list.length
            ? list.map(t => `<div class="flex items-center gap-3 p-3 border border-gray-100 rounded-xl hover:border-violet-300 cursor-pointer transition" onclick="linkExisting('${esc(t.sys_id)}')">
                <div class="w-8 h-8 bg-violet-100 rounded-full flex items-center justify-center text-violet-600 text-sm font-bold">${(t.name||'?')[0]}</div>
                <div><p class="text-sm font-semibold text-gray-800">${esc(t.name)}</p><p class="text-xs text-gray-400">${esc(t.passport_no||t.sys_id)}</p></div>
            </div>`).join('')
            : '<p class="text-sm text-gray-400 text-center py-4">No travelers found</p>';
    }, 300);
}

async function linkExisting(sysId) {
    const roaming = prompt('Roaming phone number for Saudi Arabia (optional):') ?? '';
    const res = await fetch(API, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ action:'add_traveler', group_sys_id: GROUP_SYS_ID, traveler_sys_id: sysId, roaming_phone: roaming }) });
    const j = await res.json();
    if (j.status === 'success') { toast('success','Linked!'); closeModal('addTravelerModal'); await loadTravelers(); }
    else toast('error', j.message);
}

// ── NOC ────────────────────────────────────────────────────────
let _nocTargetSysId = null;
function triggerNocUpload(travelerSysId) {
    _nocTargetSysId = travelerSysId;
    const inp = document.getElementById('nocFileHidden');
    inp.value = '';
    inp.onchange = () => uploadIndividualNoc(inp, _nocTargetSysId);
    inp.click();
}

function showPhoneEdit(sysId, currentVal) {
    document.getElementById('phone_view_' + sysId)?.classList.add('hidden');
    const editEl = document.getElementById('phone_edit_' + sysId);
    editEl?.classList.remove('hidden');
    const inp = document.getElementById('phone_input_' + sysId);
    if (inp) { inp.value = currentVal; inp.focus(); inp.select(); }
    inp?.addEventListener('keydown', function handler(e) {
        if (e.key === 'Enter')  { saveRoamingPhone(sysId); inp.removeEventListener('keydown', handler); }
        if (e.key === 'Escape') { cancelPhoneEdit(sysId);  inp.removeEventListener('keydown', handler); }
    });
}
function cancelPhoneEdit(sysId) {
    document.getElementById('phone_view_' + sysId)?.classList.remove('hidden');
    document.getElementById('phone_edit_' + sysId)?.classList.add('hidden');
}
async function saveRoamingPhone(sysId) {
    const phone = document.getElementById('phone_input_' + sysId)?.value.trim() ?? '';
    try {
        const res = await fetch(API, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ action:'update_roaming_phone', group_sys_id: GROUP_SYS_ID, traveler_sys_id: sysId, roaming_phone: phone }) });
        const j = await res.json();
        if (j.status === 'success') { toast('success','Phone saved'); await loadTravelers(); }
        else toast('error', j.message || 'Failed');
    } catch(e) { toast('error','Network error'); }
}

async function uploadIndividualNoc(input, travelerSysId) {
    const file = input.files[0]; if (!file) return;
    const fd = new FormData();
    fd.append('file', file); fd.append('group_sys_id', GROUP_SYS_ID);
    fd.append('traveler_sys_ids', JSON.stringify([travelerSysId])); fd.append('doc_type','noc');
    const res = await fetch(`${API}?action=upload_noc`, { method:'POST', body: fd });
    const j   = await res.json();
    if (j.status === 'success') toast('success','NOC uploaded'); else toast('error', j.message||'Upload failed');
    input.value = '';
}

async function uploadNoc(type = 'group_letter') {
    const fileInput = document.getElementById('groupLetterFile');
    const file = fileInput?.files[0]; if (!file) return;
    if (!selectedTravelers.size) { toast('error','Select at least one traveler first'); fileInput.value=''; return; }
    const fd = new FormData();
    fd.append('file', file); fd.append('group_sys_id', GROUP_SYS_ID);
    fd.append('traveler_sys_ids', JSON.stringify([...selectedTravelers])); fd.append('doc_type', type);
    const res = await fetch(`${API}?action=upload_noc`, { method:'POST', body: fd });
    const j   = await res.json();
    if (j.status === 'success') toast('success','Group Authorization Letter uploaded for ' + selectedTravelers.size + ' traveler(s)');
    else toast('error', j.message||'Upload failed');
    fileInput.value = '';
}

// ── Leaders ────────────────────────────────────────────────────
function openLeaderModal() {
    document.getElementById('leaderModal').classList.remove('hidden');
    const currentLeaders = new Set(travelers.filter(t => t.is_leader==1).map(t => t.traveler_sys_id));
    document.getElementById('leaderList').innerHTML = travelers.length
        ? travelers.map(t => `<label class="flex items-center gap-3 p-3 border border-gray-100 rounded-xl hover:border-violet-200 cursor-pointer transition">
            <input type="checkbox" class="leader-check w-4 h-4 accent-violet-600" value="${esc(t.traveler_sys_id)}" ${currentLeaders.has(t.traveler_sys_id)?'checked':''}>
            <div><p class="text-sm font-semibold text-gray-800">${esc(t.traveler_name)}</p>
            <p class="text-xs text-gray-400">${t.roaming_phone?'📱 '+esc(t.roaming_phone):'No roaming phone'}</p></div>
        </label>`).join('')
        : '<p class="text-sm text-gray-400 text-center py-4">No travelers in group yet</p>';
}
async function saveLeaders() {
    const checked = [...document.querySelectorAll('.leader-check:checked')].map(c => c.value);
    if (checked.length > 3) { toast('error','Maximum 3 leaders allowed'); return; }
    const res = await fetch(API, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ action:'set_leaders', group_sys_id: GROUP_SYS_ID, leader_sys_ids: checked }) });
    const j   = await res.json();
    if (j.status === 'success') { toast('success','Leaders saved!'); closeModal('leaderModal'); await loadTravelers(); }
    else toast('error', j.message);
}

// ── ID Cards ───────────────────────────────────────────────────
function generateIdCard(sysId) {
    window.open(`generate-umrah-id-card.php?traveler_sys_id=${encodeURIComponent(sysId)}&group_sys_id=${encodeURIComponent(GROUP_SYS_ID)}`, '_blank');
}
function generateAllIdCards() {
    if (!travelers.length) { toast('error','No travelers in group'); return; }
    window.open(`generate-umrah-id-card.php?group_sys_id=${encodeURIComponent(GROUP_SYS_ID)}&all=1`, '_blank');
}

function esc(s){return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
let _tt;
function toast(t,m){const el=document.getElementById('toast');document.getElementById('toast-i').className='fas '+(t==='success'?'fa-check-circle':'fa-exclamation-circle');document.getElementById('toast-m').textContent=m;el.className='show '+t;clearTimeout(_tt);_tt=setTimeout(()=>{el.className=t;},3200);}

loadGroup();
</script>
</body>
</html>