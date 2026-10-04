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
.fi{width:100%;padding:7px 11px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:.83rem;color:#111827;outline:none;transition:border .15s;background:#fff}
.fi:focus{border-color:#7c3aed;box-shadow:0 0 0 3px rgba(124,58,237,.08)}
.lbl{display:block;font-size:.72rem;font-weight:600;color:#374151;margin-bottom:3px}
.tab-pane{display:none}.tab-pane.active{display:block}
.traveler-row{background:#fff;border:1.5px solid #e5e7eb;border-radius:12px;padding:14px 16px;display:flex;align-items:center;gap:12px;transition:all .15s}
.traveler-row:hover{border-color:#a78bfa;box-shadow:0 2px 8px rgba(124,58,237,.08)}
.avatar{width:38px;height:38px;border-radius:50%;object-fit:cover;border:2px solid #ede9fe;flex-shrink:0;background:#f5f3ff;display:flex;align-items:center;justify-content:center;color:#7c3aed;font-weight:700;font-size:.85rem}
.leader-badge{display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:999px;background:#fef3c7;color:#92400e;font-size:.68rem;font-weight:700}
.seg-chip{background:#f5f3ff;border:1px solid #ede9fe;border-radius:8px;padding:10px 14px;font-size:.8rem}
.hotel-chip{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:10px 14px;font-size:.8rem}
.itin-item{display:flex;gap:12px;padding:12px 0;border-bottom:1px solid #f3f4f6}
.itin-dot{width:10px;height:10px;border-radius:50%;background:#7c3aed;margin-top:5px;flex-shrink:0}
/* Modal */
.modal-bg{position:fixed;inset:0;background:rgba(0,0,0,.5);backdrop-filter:blur(4px);z-index:50;display:flex;align-items:center;justify-content:center;padding:16px}
.modal-box{background:#fff;border-radius:20px;width:100%;max-width:600px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.15)}
.fi{width:100%;padding:8px 12px;border:1.5px solid #e5e7eb;border-radius:9px;font-size:.85rem;color:#111827;outline:none;transition:border .15s;background:#fff}
.fi:focus{border-color:#7c3aed;box-shadow:0 0 0 3px rgba(124,58,237,.1)}
.lbl{display:block;font-size:.78rem;font-weight:600;color:#374151;margin-bottom:4px}
#toast{position:fixed;bottom:24px;right:24px;z-index:9999;padding:11px 17px;border-radius:11px;color:#fff;font-size:.83rem;font-weight:600;display:flex;align-items:center;gap:8px;transform:translateY(60px);opacity:0;transition:all .3s;pointer-events:none}
#toast.show{transform:translateY(0);opacity:1}
#toast.success{background:#059669}
#toast.error{background:#dc2626}
</style>
</head>
<body class="bg-gray-50 font-sans">
<?php include '../elements/header.php'; ?>
<?php include '../elements/aside.php'; ?>

<main id="mainContent" class="pt-20 pl-64 transition-all duration-300 min-h-screen">
<div class="p-5">

<!-- Header skeleton while loading -->
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

<!-- Tabs -->
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
    <!-- Group Authorization Letter (collective) -->
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

    <!-- ID Card generate for all -->
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

<!-- Hidden file input for individual NOC (outside traveler list to avoid browser UI issues) -->
<input type="file" id="nocFileHidden" class="hidden" accept=".pdf,.jpg,.jpeg,.png"
    style="position:absolute;left:-9999px;opacity:0;pointer-events:none">

<!-- Flights tab -->
<div class="tab-pane" id="tab-flights">
    <div class="flex justify-end mb-3">
        <button onclick="toggleEdit('flights')" id="editBtn-flights"
            class="flex items-center gap-2 px-3 py-1.5 text-xs font-semibold border border-violet-200 text-violet-600 rounded-lg hover:bg-violet-50 transition">
            <i class="fas fa-pen"></i>Edit Flights
        </button>
    </div>
    <!-- View -->
    <div id="flightsContent" class="space-y-3"></div>
    <!-- Edit form (hidden by default) -->
    <div id="flightsEdit" class="hidden space-y-3">
        <div id="segmentsEditContainer"></div>
        <button type="button" onclick="addEditSegment()"
            class="flex items-center gap-2 px-3 py-2 border border-dashed border-violet-300 text-violet-600 text-xs font-semibold rounded-lg hover:bg-violet-50 transition w-full justify-center">
            <i class="fas fa-plus"></i>Add Segment
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
            <i class="fas fa-pen"></i>Edit Hotels
        </button>
    </div>
    <!-- View -->
    <div id="hotelsContent" class="grid grid-cols-2 gap-4"></div>
    <!-- Edit form -->
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
            <i class="fas fa-pen"></i>Edit Itinerary
        </button>
    </div>
    <!-- View -->
    <div id="itinContent" class="bg-white border border-gray-100 rounded-xl p-5"></div>
    <!-- Edit form -->
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

<!-- ══ Add Traveler Modal ══ -->
<div id="addTravelerModal" class="modal-bg hidden">
<div class="modal-box">
    <div class="p-5 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-bold text-gray-800">Add Traveler</h3>
        <button onclick="closeModal('addTravelerModal')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
    </div>
    <!-- Method select -->
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

    <!-- Scan method -->
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
        <div id="scanForm" class="hidden">
            <!-- filled by JS after extraction -->
        </div>
    </div>

    <!-- Manual method -->
    <div id="manualMethod" class="hidden p-6">
        <button onclick="setAddMethod(null)" class="text-xs text-violet-600 font-semibold mb-4 hover:underline"><i class="fas fa-arrow-left mr-1"></i>Back</button>
        <div id="travelerFormFields"></div>
    </div>

    <!-- Existing method -->
    <div id="existingMethod" class="hidden p-6">
        <button onclick="setAddMethod(null)" class="text-xs text-violet-600 font-semibold mb-4 hover:underline"><i class="fas fa-arrow-left mr-1"></i>Back</button>
        <div class="mb-3">
            <input type="text" id="existingSearch" class="fi" placeholder="Search by name or passport number…" oninput="searchExistingTraveler(this.value)">
        </div>
        <div id="existingResults" class="space-y-2 max-h-64 overflow-y-auto"></div>
    </div>
</div>
</div>

<!-- ══ Group Leader Modal ══ -->
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
const GROUP_SYS_ID = "<?= htmlspecialchars($groupSysId) ?>";
const API = "<?= $ip_port ?>api/umrah-groups/endpoints.php";
const TRAVELER_API = "<?= $ip_port ?>api/travelers/";
const EXTRACT_API  = "<?= $ip_port ?>api/travelers/extract-document.php";
const STORE_TRAVELER_API = "<?= $ip_port ?>api/travelers/store.php";

let groupData = null;
let travelers  = [];
let selectedTravelers = new Set();

// ── Load group ───────────────────────────────────────────────
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
        const isLeader = t.is_leader == 1;
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
                <!-- Individual NOC upload — hidden file input, triggered via JS -->
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
    if (allSelected) { selectedTravelers.clear(); }
    else { travelers.forEach(t => selectedTravelers.add(t.traveler_sys_id)); }
    renderTravelers();
    document.getElementById('selectAllBtn').textContent = selectedTravelers.size === travelers.length ? 'Deselect All' : 'Select All';
}

// ── Remove traveler ───────────────────────────────────────────
async function removeTraveler(sysId) {
    if (!confirm('Remove this traveler from the group?')) return;
    const res = await fetch(API, { method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ action:'remove_traveler', group_sys_id: GROUP_SYS_ID, traveler_sys_id: sysId }) });
    const j = await res.json();
    if (j.status === 'success') { toast('success','Removed'); await loadTravelers(); }
    else toast('error', j.message);
}

// ── Flights render ────────────────────────────────────────────
function renderFlights() {
    const segs = groupData.segments ?? [];
    const el = document.getElementById('flightsContent');
    if (!segs.length) { el.innerHTML = '<p class="text-sm text-gray-400">No flight segments added.</p>'; return; }
    el.innerHTML = segs.map(s => `
        <div class="seg-chip flex items-start gap-4">
            <div class="w-10 h-10 bg-violet-100 rounded-xl flex items-center justify-center text-violet-600 flex-shrink-0">
                <i class="fas fa-plane text-sm"></i>
            </div>
            <div class="flex-1">
                <p class="font-bold text-gray-800">${esc(s.title || s.from_airport + ' → ' + s.to_airport)}</p>
                <p class="text-gray-500 mt-1">${esc(s.from_airport)} → ${esc(s.to_airport)} · ${esc(s.airline||'')} · ${esc(s.class||'economy')}</p>
                <p class="text-gray-400 text-xs mt-1">
                    Dep: <strong>${esc(s.departure_date)} ${esc(s.departure_time)}</strong>
                    &nbsp;·&nbsp;
                    Arr: <strong>${esc(s.arrival_date)} ${esc(s.arrival_time)}</strong>
                </p>
            </div>
            <span class="text-xs font-semibold text-violet-500 bg-violet-50 px-2 py-1 rounded-lg capitalize">${esc((s.type||'').replace('_',' '))}</span>
        </div>`).join('');
}

// ── Hotels render ─────────────────────────────────────────────
function renderHotels() {
    const h = groupData.hotels ?? {};
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

// ── Itinerary render ──────────────────────────────────────────
function renderItinerary() {
    const items = groupData.itinerary ?? [];
    const el = document.getElementById('itinContent');
    if (!items.length) { el.innerHTML = '<p class="text-sm text-gray-400">No itinerary added.</p>'; return; }
    el.innerHTML = items.map(it => `
        <div class="itin-item">
            <div class="itin-dot"></div>
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-1">
                    <span class="text-xs font-bold text-violet-600">${esc(it.date)} ${it.time ? '· ' + esc(it.time) : ''}</span>
                    <span class="font-semibold text-gray-800 text-sm">${esc(it.title)}</span>
                </div>
                ${it.description ? `<div class="text-sm text-gray-600 prose max-w-none">${it.description}</div>` : ''}
            </div>
        </div>`).join('');
}

// ── Edit toggle ───────────────────────────────────────────────
function toggleEdit(section) {
    const viewMap  = { flights:'flightsContent', hotels:'hotelsContent', itinerary:'itinContent' };
    const editMap  = { flights:'flightsEdit', hotels:'hotelsEdit', itinerary:'itineraryEdit' };
    const btnMap   = { flights:'editBtn-flights', hotels:'editBtn-hotels', itinerary:'editBtn-itinerary' };

    const viewEl = document.getElementById(viewMap[section]);
    const editEl = document.getElementById(editMap[section]);
    const btn    = document.getElementById(btnMap[section]);
    const isEditing = !editEl.classList.contains('hidden');

    if (isEditing) {
        // Cancel — back to view
        editEl.classList.add('hidden');
        viewEl.classList.remove('hidden');
        btn.innerHTML = '<i class="fas fa-pen mr-1"></i>Edit ' + section.charAt(0).toUpperCase() + section.slice(1);
    } else {
        // Open edit — prefill
        editEl.classList.remove('hidden');
        viewEl.classList.add('hidden');
        btn.innerHTML = '<i class="fas fa-times mr-1"></i>Cancel';
        if (section === 'flights')   prefillFlights();
        if (section === 'hotels')    prefillHotels();
        if (section === 'itinerary') prefillItinerary();
    }
}

// ── FLIGHTS edit ──────────────────────────────────────────────
let segEditCount = 0;

function prefillFlights() {
    segEditCount = 0;
    document.getElementById('segmentsEditContainer').innerHTML = '';
    const segs = groupData.segments ?? [];
    if (segs.length) segs.forEach(s => addEditSegment(s));
    else addEditSegment();
}

function addEditSegment(data = {}) {
    const i = ++segEditCount;
    const c = document.createElement('div');
    c.id = 'eseg_' + i;
    c.className = 'bg-gray-50 border border-gray-200 rounded-xl p-4';
    c.innerHTML = `
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs font-bold text-violet-600">Segment ${i}</p>
            <button type="button" onclick="document.getElementById('eseg_${i}').remove()"
                class="text-red-400 hover:text-red-600 text-xs"><i class="fas fa-times"></i></button>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div><label class="lbl">Route Title</label><input class="fi eseg-title" type="text" placeholder="e.g. DAC → MED" value="${esc(data.title||'')}"></div>
            <div><label class="lbl">Type</label>
                <select class="fi eseg-type">
                    <option value="one_way" ${data.type==='one_way'?'selected':''}>One Way</option>
                    <option value="round"   ${data.type==='round'  ?'selected':''}>Round Trip</option>
                    <option value="multi_city" ${data.type==='multi_city'?'selected':''}>Multi City</option>
                </select>
            </div>
            <div><label class="lbl">From Airport</label><input class="fi eseg-from" type="text" placeholder="DAC" value="${esc(data.from_airport||'')}"></div>
            <div><label class="lbl">To Airport</label><input class="fi eseg-to" type="text" placeholder="MED" value="${esc(data.to_airport||'')}"></div>
            <div><label class="lbl">Departure Date</label><input class="fi eseg-dep-date" type="date" value="${esc(data.departure_date||'')}"></div>
            <div><label class="lbl">Departure Time</label><input class="fi eseg-dep-time" type="time" value="${esc(data.departure_time||'')}"></div>
            <div><label class="lbl">Arrival Date</label><input class="fi eseg-arr-date" type="date" value="${esc(data.arrival_date||'')}"></div>
            <div><label class="lbl">Arrival Time</label><input class="fi eseg-arr-time" type="time" value="${esc(data.arrival_time||'')}"></div>
            <div><label class="lbl">Airline</label><input class="fi eseg-airline" type="text" placeholder="Biman Bangladesh" value="${esc(data.airline||'')}"></div>
            <div><label class="lbl">Class</label>
                <select class="fi eseg-class">
                    <option value="economy"  ${data.class==='economy' ?'selected':''}>Economy</option>
                    <option value="business" ${data.class==='business'?'selected':''}>Business</option>
                    <option value="first"    ${data.class==='first'   ?'selected':''}>First</option>
                </select>
            </div>
        </div>`;
    document.getElementById('segmentsEditContainer').appendChild(c);
}

async function saveFlights() {
    const segments = [...document.querySelectorAll('[id^="eseg_"]')].map((c, i) => ({
        sys_id:         'SEG-' + String(i+1).padStart(3,'0'),
        title:          c.querySelector('.eseg-title')?.value.trim() || '',
        type:           c.querySelector('.eseg-type')?.value || 'one_way',
        from_airport:   c.querySelector('.eseg-from')?.value.trim().toUpperCase() || '',
        to_airport:     c.querySelector('.eseg-to')?.value.trim().toUpperCase() || '',
        departure_date: c.querySelector('.eseg-dep-date')?.value || '',
        departure_time: c.querySelector('.eseg-dep-time')?.value || '',
        arrival_date:   c.querySelector('.eseg-arr-date')?.value || '',
        arrival_time:   c.querySelector('.eseg-arr-time')?.value || '',
        airline:        c.querySelector('.eseg-airline')?.value.trim() || '',
        class:          c.querySelector('.eseg-class')?.value || 'economy',
    }));
    await doUpdate({ segments });
    groupData.segments = segments;
    renderFlights();
    toggleEdit('flights');
}

// ── HOTELS edit ───────────────────────────────────────────────
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

// ── ITINERARY edit ────────────────────────────────────────────
let itinEditCount = 0;

function prefillItinerary() {
    itinEditCount = 0;
    document.getElementById('itinEditContainer').innerHTML = '';
    const items = groupData.itinerary ?? [];
    if (items.length) items.forEach(it => addEditItin(it));
    else addEditItin();
}

function addEditItin(data = {}) {
    const i = ++itinEditCount;
    const c = document.createElement('div');
    c.id = 'eitin_' + i;
    c.className = 'bg-gray-50 border border-gray-200 rounded-xl p-4';
    c.innerHTML = `
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs font-bold text-violet-600">Item ${i}</p>
            <button type="button" onclick="document.getElementById('eitin_${i}').remove()"
                class="text-red-400 hover:text-red-600 text-xs"><i class="fas fa-times"></i></button>
        </div>
        <div class="grid grid-cols-2 gap-3 mb-3">
            <div><label class="lbl">Date</label><input class="fi eitin-date" type="date" value="${esc(data.date||'')}"></div>
            <div><label class="lbl">Time</label><input class="fi eitin-time" type="time" value="${esc(data.time||'')}"></div>
        </div>
        <div class="mb-3"><label class="lbl">Title</label><input class="fi eitin-title" type="text" placeholder="e.g. Arrive Madinah" value="${esc(data.title||'')}"></div>
        <div><label class="lbl">Description</label><textarea class="fi eitin-desc" rows="2" placeholder="Details...">${esc(data.description||'')}</textarea></div>`;
    document.getElementById('itinEditContainer').appendChild(c);
}

async function saveItinerary() {
    const itinerary = [...document.querySelectorAll('[id^="eitin_"]')].map((c, i) => ({
        sys_id:      'IT-' + String(i+1).padStart(3,'0'),
        date:        c.querySelector('.eitin-date')?.value || '',
        time:        c.querySelector('.eitin-time')?.value || '',
        title:       c.querySelector('.eitin-title')?.value.trim() || '',
        description: c.querySelector('.eitin-desc')?.value || '',
    }));
    await doUpdate({ itinerary });
    groupData.itinerary = itinerary;
    renderItinerary();
    toggleEdit('itinerary');
}

// ── Shared update API call ────────────────────────────────────
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

// ── Tabs ──────────────────────────────────────────────────────
function switchTab(name) {
    document.querySelectorAll('.tab-btn').forEach((b,i) => {
        const names = ['travelers','flights','hotels','itinerary'];
        b.classList.toggle('active', names[i] === name);
    });
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.toggle('active', p.id === 'tab-' + name));
}

// ── Add Traveler Modal ────────────────────────────────────────
function openAddTravelerModal() { document.getElementById('addTravelerModal').classList.remove('hidden'); setAddMethod(null); }
function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

function setAddMethod(method) {
    ['methodSelect','scanMethod','manualMethod','existingMethod'].forEach(id =>
        document.getElementById(id).classList.add('hidden'));
    if (!method) { document.getElementById('methodSelect').classList.remove('hidden'); return; }
    document.getElementById(method + 'Method').classList.remove('hidden');
    if (method === 'manual') renderTravelerFormFields(null, 'manualMethod');
}

// ── Traveler form fields (shared by manual + scan) ────────────
function renderTravelerFormFields(data, containerId) {
    const d = data ?? {};
    document.getElementById(containerId === 'scanMethod' ? 'scanForm' : 'travelerFormFields').innerHTML = `
        <div class="space-y-3">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="lbl">Salutation</label>
                    <select class="fi" id="tf_salutation">
                        <option value="">—</option>
                        ${['Mr','Mrs','Ms','Dr','Haji','Hajjah'].map(s => `<option ${d.salutation===s?'selected':''}>${s}</option>`).join('')}
                    </select>
                </div>
                <div><label class="lbl">Gender</label>
                    <select class="fi" id="tf_gender">
                        <option value="">—</option>
                        <option value="male" ${d.gender==='male'?'selected':''}>Male</option>
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

// ── Passport extraction ───────────────────────────────────────
async function extractPassport(input) {
    const file = input.files[0]; if (!file) return;
    document.getElementById('scanUploadZone').classList.add('hidden');
    document.getElementById('scanLoading').classList.remove('hidden');
    try {
        const fd = new FormData(); fd.append('file', file); fd.append('document_type', 'passport');
        const res = await fetch(EXTRACT_API, { method:'POST', body: fd });
        const j = await res.json();
        document.getElementById('scanLoading').classList.add('hidden');
        if (j.success && j.data) {
            const d = j.data;
            renderTravelerFormFields({
                given_name:       d.given_name ?? d.first_name ?? '',
                surname:          d.surname ?? d.last_name ?? '',
                salutation:       d.salutation ?? '',
                gender:           d.sex?.toLowerCase() === 'm' ? 'male' : d.sex?.toLowerCase() === 'f' ? 'female' : '',
                date_of_birth:    d.date_of_birth ?? '',
                passport_no:      d.passport_number ?? d.passport_no ?? '',
                expiry_date:      d.expiry_date ?? d.date_of_expiry ?? '',
                father_name:      d.father_name ?? '',
                emergency_contact: d.emergency_contact ?? '',
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

// ── Save traveler form ────────────────────────────────────────
async function saveTravelerForm() {
    const given = document.getElementById('tf_given')?.value.trim();
    if (!given) { toast('error','Given name is required'); return; }

    const fullName = [
        document.getElementById('tf_salutation')?.value,
        given,
        document.getElementById('tf_surname')?.value.trim()
    ].filter(Boolean).join(' ');

    const travelerData = {
        full_name:    fullName,
        passport_no:  document.getElementById('tf_passport')?.value.trim(),
        date_of_birth: document.getElementById('tf_dob')?.value.trim(),
        personal_info: {
            gender:     document.getElementById('tf_gender')?.value,
            salutation: document.getElementById('tf_salutation')?.value,
        },
        passport_info: {
            given_name:  given,
            surname:     document.getElementById('tf_surname')?.value.trim(),
            expiry_date: document.getElementById('tf_expiry')?.value.trim(),
        },
        family_info: {
            father_name:      document.getElementById('tf_father')?.value.trim(),
            emergency_contact: document.getElementById('tf_emergency')?.value.trim(),
        },
    };

    const roaming = document.getElementById('tf_roaming')?.value.trim();

    try {
        // 1. Create traveler in system
        const r1 = await fetch(STORE_TRAVELER_API, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(travelerData) });
        const j1 = await r1.json();
        if (!j1.success) { toast('error', j1.message || 'Failed to create traveler'); return; }

        // 2. Link to group
        const r2 = await fetch(API, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({
            action: 'add_traveler',
            group_sys_id: GROUP_SYS_ID,
            traveler_sys_id: j1.sys_id,
            roaming_phone: roaming,
        })});
        const j2 = await r2.json();
        if (j2.status !== 'success') { toast('error', j2.message || 'Failed to link traveler'); return; }

        toast('success', 'Traveler added!');
        closeModal('addTravelerModal');
        await loadTravelers();
    } catch(e) { toast('error', 'Network error: ' + e.message); }
}

// ── Link existing traveler ────────────────────────────────────
let _searchTimer;
async function searchExistingTraveler(q) {
    clearTimeout(_searchTimer);
    if (q.length < 2) { document.getElementById('existingResults').innerHTML = ''; return; }
    _searchTimer = setTimeout(async () => {
        const res = await fetch(`${TRAVELER_API}all-travelers.php?search=${encodeURIComponent(q)}&limit=10`);
        const j = await res.json();
        const list = j.travelers ?? j.data ?? [];
        document.getElementById('existingResults').innerHTML = list.length
            ? list.map(t => `<div class="flex items-center gap-3 p-3 border border-gray-100 rounded-xl hover:border-violet-300 cursor-pointer transition" onclick="linkExisting('${esc(t.sys_id)}')">
                <div class="w-8 h-8 bg-violet-100 rounded-full flex items-center justify-center text-violet-600 text-sm font-bold">${(t.name||'?')[0]}</div>
                <div>
                    <p class="text-sm font-semibold text-gray-800">${esc(t.name)}</p>
                    <p class="text-xs text-gray-400">${esc(t.passport_no || t.sys_id)}</p>
                </div>
            </div>`).join('')
            : '<p class="text-sm text-gray-400 text-center py-4">No travelers found</p>';
    }, 300);
}

async function linkExisting(sysId) {
    const roaming = prompt('Roaming phone number for Saudi Arabia (optional):') ?? '';
    const res = await fetch(API, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({
        action: 'add_traveler', group_sys_id: GROUP_SYS_ID, traveler_sys_id: sysId, roaming_phone: roaming,
    })});
    const j = await res.json();
    if (j.status === 'success') { toast('success','Linked!'); closeModal('addTravelerModal'); await loadTravelers(); }
    else toast('error', j.message);
}

// ── NOC upload ────────────────────────────────────────────────
// Trigger hidden file input for individual NOC
let _nocTargetSysId = null;
function triggerNocUpload(travelerSysId) {
    _nocTargetSysId = travelerSysId;
    const inp = document.getElementById('nocFileHidden');
    inp.value = '';
    inp.onchange = () => uploadIndividualNoc(inp, _nocTargetSysId);
    inp.click();
}

// Inline phone edit
function showPhoneEdit(sysId, currentVal) {
    document.getElementById('phone_view_' + sysId)?.classList.add('hidden');
    const editEl = document.getElementById('phone_edit_' + sysId);
    editEl?.classList.remove('hidden');
    const inp = document.getElementById('phone_input_' + sysId);
    if (inp) { inp.value = currentVal; inp.focus(); inp.select(); }
    // Save on Enter
    inp?.addEventListener('keydown', function handler(e) {
        if (e.key === 'Enter') { saveRoamingPhone(sysId); inp.removeEventListener('keydown', handler); }
        if (e.key === 'Escape') { cancelPhoneEdit(sysId); inp.removeEventListener('keydown', handler); }
    });
}
function cancelPhoneEdit(sysId) {
    document.getElementById('phone_view_' + sysId)?.classList.remove('hidden');
    document.getElementById('phone_edit_' + sysId)?.classList.add('hidden');
}
async function saveRoamingPhone(sysId) {
    const phone = document.getElementById('phone_input_' + sysId)?.value.trim() ?? '';
    try {
        const res = await fetch(API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'update_roaming_phone',
                group_sys_id: GROUP_SYS_ID,
                traveler_sys_id: sysId,
                roaming_phone: phone,
            }),
        });
        const j = await res.json();
        if (j.status === 'success') { toast('success', 'Phone saved'); await loadTravelers(); }
        else toast('error', j.message || 'Failed');
    } catch(e) { toast('error', 'Network error'); }
}

// Individual NOC — per traveler card
async function uploadIndividualNoc(input, travelerSysId) {
    const file = input.files[0];
    if (!file) return;
    const fd = new FormData();
    fd.append('file', file);
    fd.append('group_sys_id', GROUP_SYS_ID);
    fd.append('traveler_sys_ids', JSON.stringify([travelerSysId]));
    fd.append('doc_type', 'noc');
    const res = await fetch(`${API}?action=upload_noc`, { method:'POST', body: fd });
    const j = await res.json();
    if (j.status === 'success') toast('success', 'NOC uploaded');
    else toast('error', j.message || 'Upload failed');
    input.value = '';
}

// Group Authorization Letter — collective, uses selected travelers
async function uploadNoc(type = 'group_letter') {
    const fileInput = document.getElementById('groupLetterFile');
    const file = fileInput?.files[0];
    if (!file) return;
    if (!selectedTravelers.size) {
        toast('error', 'Select at least one traveler first');
        fileInput.value = '';
        return;
    }
    const fd = new FormData();
    fd.append('file', file);
    fd.append('group_sys_id', GROUP_SYS_ID);
    fd.append('traveler_sys_ids', JSON.stringify([...selectedTravelers]));
    fd.append('doc_type', type);
    const res = await fetch(`${API}?action=upload_noc`, { method:'POST', body: fd });
    const j = await res.json();
    if (j.status === 'success') {
        toast('success', 'Group Authorization Letter uploaded for ' + selectedTravelers.size + ' traveler(s)');
    } else {
        toast('error', j.message || 'Upload failed');
    }
    fileInput.value = '';
}

// ── Group Leaders ─────────────────────────────────────────────
function openLeaderModal() {
    document.getElementById('leaderModal').classList.remove('hidden');
    const currentLeaders = new Set(travelers.filter(t => t.is_leader == 1).map(t => t.traveler_sys_id));
    document.getElementById('leaderList').innerHTML = travelers.length
        ? travelers.map(t => `<label class="flex items-center gap-3 p-3 border border-gray-100 rounded-xl hover:border-violet-200 cursor-pointer transition">
            <input type="checkbox" class="leader-check w-4 h-4 accent-violet-600" value="${esc(t.traveler_sys_id)}" ${currentLeaders.has(t.traveler_sys_id)?'checked':''}>
            <div>
                <p class="text-sm font-semibold text-gray-800">${esc(t.traveler_name)}</p>
                <p class="text-xs text-gray-400">${t.roaming_phone ? '📱 '+esc(t.roaming_phone) : 'No roaming phone'}</p>
            </div>
        </label>`).join('')
        : '<p class="text-sm text-gray-400 text-center py-4">No travelers in group yet</p>';
}

async function saveLeaders() {
    const checked = [...document.querySelectorAll('.leader-check:checked')].map(c => c.value);
    if (checked.length > 3) { toast('error','Maximum 3 leaders allowed'); return; }
    const res = await fetch(API, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({
        action: 'set_leaders', group_sys_id: GROUP_SYS_ID, leader_sys_ids: checked,
    })});
    const j = await res.json();
    if (j.status === 'success') { toast('success','Leaders saved!'); closeModal('leaderModal'); await loadTravelers(); }
    else toast('error', j.message);
}

// ── ID Card ───────────────────────────────────────────────────
function generateIdCard(sysId) {
    window.open(`generate-umrah-id-card.php?traveler_sys_id=${encodeURIComponent(sysId)}&group_sys_id=${encodeURIComponent(GROUP_SYS_ID)}`, '_blank');
}
function generateAllIdCards() {
    const ids = travelers.map(t => t.traveler_sys_id);
    if (!ids.length) { toast('error','No travelers in group'); return; }
    window.open(`generate-umrah-id-card.php?group_sys_id=${encodeURIComponent(GROUP_SYS_ID)}&all=1`, '_blank');
}

function esc(s) { return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
let _tt;
function toast(t,m){const el=document.getElementById('toast');document.getElementById('toast-i').className='fas '+(t==='success'?'fa-check-circle':'fa-exclamation-circle');document.getElementById('toast-m').textContent=m;el.className='show '+t;clearTimeout(_tt);_tt=setTimeout(()=>{el.className=t;},3200);}

loadGroup();
</script>
</body>
</html>