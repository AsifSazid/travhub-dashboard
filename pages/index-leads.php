<?php
// FILE PATH: /pages/index-leads.php
include_once('./authenticate.php');
$ip_port        = @file_get_contents('../ippath.txt') ?: 'http://103.104.219.3:898';
$getAllLeadsApi  = $ip_port . 'api/leads/all-leads.php';
$updateStatusApi= $ip_port . 'api/leads/update-status.php';
$deleteLeadApi  = $ip_port . 'api/leads/delete.php';
$moveToWorkApi  = $ip_port . 'api/leads/move-to-work.php';
$storeLeadApi   = $ip_port . 'api/leads/store.php';
$assignLeadApi  = $ip_port . 'api/leads/assign.php';
$clientsApi     = $ip_port . 'api/clients/all-clients.php';
$employeesApi   = $ip_port . 'api/employees/all-employees.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lead List – TravHub</title>
<link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.badge{display:inline-flex;align-items:center;padding:2px 10px;border-radius:999px;font-size:.72rem;font-weight:600;}
.badge-pending  {background:#fef9c3;color:#854d0e;}
.badge-active   {background:#dcfce7;color:#166534;}
.badge-converted{background:#dbeafe;color:#1e40af;}
.badge-hold     {background:#f3e8ff;color:#6b21a8;}
.badge-closed   {background:#fee2e2;color:#991b1b;}
.svc-pill{display:inline-flex;align-items:center;gap:4px;padding:2px 9px;border-radius:999px;font-size:.68rem;font-weight:600;white-space:nowrap;}
.pill-air_ticket   {background:#e0f2fe;color:#075985;}
.pill-visa         {background:#ede9fe;color:#5b21b6;}
.pill-hotel        {background:#fce7f3;color:#9d174d;}
.pill-tour_package {background:#dcfce7;color:#14532d;}
.pill-umrah        {background:#fef3c7;color:#92400e;}
.pill-transport    {background:#f0fdf4;color:#166534;}
.action-btn{padding:5px 9px;border-radius:6px;font-size:.78rem;transition:all .15s;cursor:pointer;border:none;}
.lead-card{transition:all .2s;border:2px solid #f3f4f6;}
.lead-card:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(99,102,241,.1);border-color:#c7d2fe;}
.view-btn.active{background:#4f46e5;color:#fff;}
#advSearchPanel{max-height:0;overflow:hidden;transition:max-height .35s ease;}
#advSearchPanel.open{max-height:560px;}
.modal-bg{background:rgba(0,0,0,.5);backdrop-filter:blur(4px);}
.modal-box{max-height:90vh;display:flex;flex-direction:column;}
.modal-body{overflow-y:auto;flex:1;}
tr:hover td{background:#f8f7ff;}
/* Show modal */
.svc-section{background:#f8fafc;border-radius:12px;padding:14px;margin-bottom:10px;}
.seg-box{border-radius:9px;padding:11px;background:#fff;margin-bottom:8px;}
.seg-box-air  {border:1px solid #bfdbfe;}
.seg-box-hotel{border:1px solid #fbcfe8;}
.seg-box-visa {border:1px solid #ddd6fe;}
.seg-box-tr   {border:1px solid #bbf7d0;}
.seg-box-pkg  {border:1px solid #a7f3d0;}
.seg-box-umrah{border:1px solid #fde68a;}
.seg-label{font-size:.65rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;margin-bottom:7px;}
.kv-row{display:flex;justify-content:space-between;font-size:.8rem;padding:3px 0;border-bottom:1px solid #f1f5f9;}
.kv-row:last-child{border-bottom:none;}
.kv-key{color:#94a3b8;}
.kv-val{font-weight:500;color:#1e293b;text-align:right;max-width:60%;}
.sp-ins{font-size:.74rem;color:#d97706;display:flex;gap:5px;align-items:flex-start;margin-top:1px;}
</style>
</head>
<body class="bg-gray-50 font-sans">
<?php include '../elements/header.php'; ?>
<?php include '../elements/aside.php'; ?>
<?php include '../elements/preview-model.php'; ?>

<main id="mainContent" class="pt-16 pl-64 transition-all duration-300">
<div class="p-6">

<!-- Top Bar -->
<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <div>
        <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-list-check mr-2 text-indigo-500"></i>Lead List</h1>
        <p class="text-sm text-gray-500 mt-0.5">Manage and track all generated leads</p>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
        <div class="relative" id="downloadWrap">
            <button onclick="toggleDownloadMenu()" class="flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 hover:bg-gray-50 text-gray-600 rounded-lg font-medium text-sm transition shadow-sm">
                <i class="fas fa-download"></i> Export <i class="fas fa-chevron-down text-xs ml-1"></i>
            </button>
            <div id="downloadMenu" class="hidden absolute right-0 top-full mt-1.5 bg-white rounded-xl shadow-xl border border-gray-200 z-30 w-48 py-1">
                <button onclick="exportData('csv',false)"   class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-3"><i class="fas fa-file-csv text-green-500 w-4"></i>Export CSV</button>
                <button onclick="exportData('excel',false)" class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-3"><i class="fas fa-file-excel text-emerald-600 w-4"></i>Export Excel</button>
                <button onclick="exportData('report',false)"class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-3"><i class="fas fa-file-pdf text-red-500 w-4"></i>Download Report</button>
                <hr class="my-1 border-gray-100">
                <p class="px-4 py-1 text-[10px] text-gray-400 uppercase tracking-wide">With current filters</p>
                <button onclick="exportData('csv',true)"   class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-3"><i class="fas fa-file-csv text-green-500 w-4"></i>Filtered CSV</button>
                <button onclick="exportData('excel',true)" class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-3"><i class="fas fa-file-excel text-emerald-600 w-4"></i>Filtered Excel</button>
                <button onclick="exportData('report',true)"class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-3"><i class="fas fa-file-pdf text-red-500 w-4"></i>Filtered Report</button>
            </div>
        </div>
        <a href="trash-leads.php" class="flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 hover:bg-red-50 text-gray-500 hover:text-red-600 rounded-lg font-medium text-sm transition shadow-sm"><i class="fas fa-trash-alt"></i> Trash</a>
        <div class="flex bg-gray-100 rounded-lg p-1">
            <button id="btnList" onclick="setView('list')" class="view-btn active px-3 py-1.5 rounded-md text-sm font-medium transition" title="List view"><i class="fas fa-list"></i></button>
            <button id="btnCard" onclick="setView('card')" class="view-btn px-3 py-1.5 rounded-md text-sm font-medium text-gray-500 transition" title="Card view"><i class="fas fa-th-large"></i></button>
        </div>
        <a href="create-leads.php" class="flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium text-sm transition shadow-sm"><i class="fas fa-plus"></i> Generate Lead</a>
    </div>
</div>

<!-- Stats -->
<div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-5">
    <div class="bg-white rounded-xl p-4 shadow-sm border-l-4 border-gray-300 cursor-pointer stat-card hover:shadow-md transition" data-filter=""><div class="text-2xl font-bold text-gray-700" id="statAll">—</div><div class="text-xs text-gray-500 mt-0.5">All Leads</div></div>
    <div class="bg-white rounded-xl p-4 shadow-sm border-l-4 border-yellow-400 cursor-pointer stat-card hover:shadow-md transition" data-filter="pending"><div class="text-2xl font-bold text-yellow-600" id="statPending">—</div><div class="text-xs text-gray-500 mt-0.5">Pending</div></div>
    <div class="bg-white rounded-xl p-4 shadow-sm border-l-4 border-green-400 cursor-pointer stat-card hover:shadow-md transition" data-filter="active"><div class="text-2xl font-bold text-green-600" id="statActive">—</div><div class="text-xs text-gray-500 mt-0.5">Active</div></div>
    <div class="bg-white rounded-xl p-4 shadow-sm border-l-4 border-blue-400 cursor-pointer stat-card hover:shadow-md transition" data-filter="converted"><div class="text-2xl font-bold text-blue-600" id="statConverted">—</div><div class="text-xs text-gray-500 mt-0.5">Converted</div></div>
    <div class="bg-white rounded-xl p-4 shadow-sm border-l-4 border-red-400 cursor-pointer stat-card hover:shadow-md transition" data-filter="closed"><div class="text-2xl font-bold text-red-500" id="statClosed">—</div><div class="text-xs text-gray-500 mt-0.5">Closed</div></div>
</div>

<!-- Main Card -->
<div class="bg-white rounded-xl shadow-sm p-5">
    <!-- Filters -->
    <div class="flex flex-wrap items-center gap-3 mb-4">
        <div class="relative flex-1 min-w-[200px]">
            <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-sm"></i>
            <input type="text" id="searchInput" placeholder="Search by name, ID, phone, destination…" class="pl-9 pr-4 py-2.5 border border-gray-200 rounded-lg text-sm w-full focus:ring-2 focus:ring-indigo-300 focus:outline-none">
        </div>
        <select id="filterStatus" class="px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
            <option value="">All Status</option><option value="pending">Pending</option><option value="active">Active</option>
            <option value="converted">Converted</option><option value="hold">On Hold</option><option value="closed">Closed</option>
        </select>
        <select id="filterService" class="px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
            <option value="">All Services</option><option value="air_ticket">Air Ticket</option><option value="visa">Visa</option>
            <option value="hotel">Hotel</option><option value="tour_package">Tour Package</option>
            <option value="umrah">Umrah</option><option value="transport">Transport</option>
        </select>
        <button onclick="toggleAdvSearch()" id="advBtn" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg text-sm flex items-center gap-2 transition"><i class="fas fa-sliders"></i> Advanced</button>
        <button onclick="resetFilters()" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg text-sm transition" title="Reset"><i class="fas fa-rotate-right"></i></button>
    </div>

    <!-- Advanced Filter -->
    <div id="advSearchPanel" class="bg-gray-50 rounded-lg px-5 mb-3">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 py-4">
            <div><label class="block text-xs font-medium text-gray-600 mb-1">Date From</label><input type="date" id="advDateFrom" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none"></div>
            <div><label class="block text-xs font-medium text-gray-600 mb-1">Date To</label><input type="date" id="advDateTo" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none"></div>
            <div><label class="block text-xs font-medium text-gray-600 mb-1">Lead Source</label>
                <select id="advSource" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                    <option value="">Any Source</option><option value="System">System</option><option value="walk_in">Walk-in</option>
                    <option value="referral">Referral</option><option value="facebook">Facebook</option>
                    <option value="website">Website</option><option value="phone_call">Phone Call</option><option value="other">Other</option>
                </select>
            </div>
            <div><label class="block text-xs font-medium text-gray-600 mb-1">Created By</label>
                <select id="advCreator" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none"><option value="">Any Creator</option></select>
            </div>
            <div><label class="block text-xs font-medium text-gray-600 mb-1">Destination</label>
                <input type="text" id="advDestination" placeholder="e.g. Dubai, Bangkok…" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
            </div>
            <div><label class="block text-xs font-medium text-gray-600 mb-1">Assigned To</label>
                <input type="text" id="advAssigned" placeholder="Employee name…" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
            </div>
        </div>
        <div class="pb-3 flex justify-end">
            <button onclick="applyFilters()" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition">Apply Filters</button>
        </div>
    </div>

    <!-- List View -->
    <div id="listView" class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-100">
                    <th class="px-4 py-3 w-8"></th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-8">#</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Lead ID</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Client</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Services</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Assigned To</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Created By</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody id="leadsTableBody" class="divide-y divide-gray-50">
                <tr><td colspan="10" class="text-center py-12 text-gray-400"><i class="fas fa-spinner fa-spin text-2xl"></i></td></tr>
            </tbody>
        </table>
    </div>

    <!-- Card View -->
    <div id="cardView" class="hidden">
        <div id="leadsCardGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"></div>
    </div>

    <!-- Pagination -->
    <div class="flex items-center justify-between mt-5 pt-4 border-t border-gray-100">
        <div class="text-sm text-gray-500" id="paginationInfo">—</div>
        <div class="flex items-center gap-2" id="paginationBtns"></div>
    </div>
</div>
</div>
</main>

<!-- ══════════ MODALS ══════════ -->

<!-- SHOW LEAD -->
<div id="showModal" class="fixed inset-0 z-50 hidden modal-bg flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl modal-box">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
            <h3 class="text-lg font-semibold text-gray-800"><i class="fas fa-eye mr-2 text-indigo-500"></i>Lead Details</h3>
            <button onclick="closeModal('showModal')" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-400"><i class="fas fa-times"></i></button>
        </div>
        <div id="showModalContent" class="modal-body p-6"></div>
    </div>
</div>

<!-- STATUS -->
<div id="statusModal" class="fixed inset-0 z-50 hidden modal-bg flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
        <div class="flex items-center justify-between p-5 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-800"><i class="fas fa-tag mr-2 text-indigo-500"></i>Change Status</h3>
            <button onclick="closeModal('statusModal')" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-400"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-5">
            <input type="hidden" id="statusLeadId">
            <div class="grid grid-cols-2 gap-3">
                <button onclick="changeStatus('pending')"   class="py-2.5 rounded-lg bg-yellow-50 text-yellow-700 border border-yellow-200 font-medium text-sm hover:bg-yellow-100 transition">⏳ Pending</button>
                <button onclick="changeStatus('active')"    class="py-2.5 rounded-lg bg-green-50 text-green-700 border border-green-200 font-medium text-sm hover:bg-green-100 transition">✅ Active</button>
                <button onclick="changeStatus('converted')" class="py-2.5 rounded-lg bg-blue-50 text-blue-700 border border-blue-200 font-medium text-sm hover:bg-blue-100 transition">🔄 Converted</button>
                <button onclick="changeStatus('hold')"      class="py-2.5 rounded-lg bg-purple-50 text-purple-700 border border-purple-200 font-medium text-sm hover:bg-purple-100 transition">⏸ On Hold</button>
                <button onclick="changeStatus('closed')"    class="py-2.5 col-span-2 rounded-lg bg-red-50 text-red-700 border border-red-200 font-medium text-sm hover:bg-red-100 transition">❌ Closed</button>
            </div>
        </div>
    </div>
</div>

<!-- DELETE -->
<div id="deleteModal" class="fixed inset-0 z-50 hidden modal-bg flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between p-5 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-800"><i class="fas fa-trash-alt mr-2 text-red-500"></i>Move to Trash</h3>
            <button onclick="closeModal('deleteModal')" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-400"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-6">
            <div class="bg-red-50 border border-red-100 rounded-xl p-4 mb-5 text-sm text-red-700"><i class="fas fa-info-circle mr-1.5"></i>This lead will be moved to <strong>Trash</strong>. Restorable later.</div>
            <p class="text-sm text-gray-600 mb-1.5">Type the Lead ID to confirm:</p>
            <p id="deleteLeadIdDisplay" class="font-mono text-indigo-600 font-bold text-sm mb-3"></p>
            <input type="hidden" id="deleteLeadId">
            <input type="text" id="deleteConfirmInput" placeholder="Paste or type Lead ID here…" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm font-mono focus:ring-2 focus:ring-red-300 focus:outline-none mb-4">
            <div class="flex gap-3">
                <button onclick="closeModal('deleteModal')" class="flex-1 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium text-sm transition">Cancel</button>
                <button onclick="confirmDelete()" id="deleteConfirmBtn" disabled class="flex-1 py-2.5 bg-red-300 text-white rounded-lg font-medium text-sm transition cursor-not-allowed"><i class="fas fa-trash-alt mr-1"></i> Move to Trash</button>
            </div>
        </div>
    </div>
</div>

<!-- MOVE TO WORK -->
<div id="moveModal" class="fixed inset-0 z-50 hidden modal-bg flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg modal-box">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
            <h3 class="text-base font-semibold text-gray-800"><i class="fas fa-briefcase mr-2 text-indigo-500"></i>Move to Workboard</h3>
            <button onclick="closeModal('moveModal')" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-400"><i class="fas fa-times"></i></button>
        </div>
        <div id="moveModalContent" class="modal-body px-6 py-4"></div>
        <div class="px-6 py-4 border-t border-gray-100 flex gap-3 flex-shrink-0">
            <button onclick="closeModal('moveModal')" class="flex-1 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium text-sm transition">Cancel</button>
            <button onclick="confirmMove()" class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium text-sm transition"><i class="fas fa-arrow-right mr-1"></i> Move to Work</button>
        </div>
    </div>
</div>

<!-- DUPLICATE -->
<div id="duplicateModal" class="fixed inset-0 z-50 hidden modal-bg flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
        <div class="flex items-center justify-between p-5 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-800"><i class="fas fa-copy mr-2 text-purple-500"></i>Duplicate Lead</h3>
            <button onclick="closeModal('duplicateModal')" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-400"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-5 space-y-4">
            <input type="hidden" id="dupSourceId">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Lead Title <span class="font-normal text-gray-400">(optional)</span></label>
                <input type="text" id="dupTitle" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-purple-300 focus:outline-none" placeholder="e.g. Dubai Trip — Copy">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Client <span class="text-red-400">*</span></label>
                <div class="relative" id="dupClientWrap">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                    <input type="text" id="dupClientInput" placeholder="Search client…" autocomplete="off" class="w-full pl-8 pr-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-purple-300 focus:outline-none">
                    <ul id="dupClientDrop" class="absolute w-full bg-white border border-gray-200 rounded-lg mt-1 max-h-48 overflow-auto shadow-xl hidden z-50 text-sm"></ul>
                </div>
                <div id="dupClientBadge" class="hidden mt-2 flex items-center gap-2 bg-purple-50 border border-purple-200 rounded-lg px-3 py-2">
                    <div class="w-7 h-7 bg-purple-500 rounded-full text-white flex items-center justify-center font-bold text-xs flex-shrink-0" id="dupBadgeAvatar">?</div>
                    <div class="flex-1 min-w-0"><div class="font-semibold text-gray-800 text-sm truncate" id="dupBadgeName">—</div><div class="text-xs text-gray-400 font-mono" id="dupBadgeMeta">—</div></div>
                    <button onclick="clearDupClient()" class="text-gray-300 hover:text-red-400 transition"><i class="fas fa-times text-xs"></i></button>
                </div>
                <div class="mt-2 flex gap-3">
                    <a href="create-client.php" target="_blank" class="inline-flex items-center gap-1.5 text-xs text-purple-600 hover:text-purple-800 font-semibold"><i class="fas fa-plus-circle"></i>New client</a>
                    <button onclick="refreshDupClients()" class="inline-flex items-center gap-1.5 text-xs text-gray-500 hover:text-gray-700"><i class="fas fa-rotate-right text-xs"></i>Refresh</button>
                </div>
            </div>
            <div class="bg-amber-50 border border-amber-100 rounded-lg px-4 py-2.5 text-xs text-amber-700"><i class="fas fa-info-circle mr-1"></i>All service data and notes will be copied. Status → <strong>Pending</strong>.</div>
        </div>
        <div class="flex gap-3 px-5 pb-5">
            <button onclick="closeModal('duplicateModal')" class="flex-1 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition">Cancel</button>
            <button onclick="confirmDuplicate()" id="dupSubmitBtn" class="flex-1 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium transition"><i class="fas fa-copy mr-1"></i>Duplicate Lead</button>
        </div>
    </div>
</div>

<!-- ASSIGN -->
<div id="assignModal" class="fixed inset-0 z-50 hidden modal-bg flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between p-5 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-800"><i class="fas fa-user-check mr-2 text-teal-500"></i>Assign Employee</h3>
            <button onclick="closeModal('assignModal')" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-400"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-5">
            <input type="hidden" id="assignLeadId">
            <p class="text-xs text-gray-500 mb-3">Assign an employee as owner of this lead.</p>
            <div id="currentAssigneeBadge" class="hidden mb-3 flex items-center gap-2 bg-teal-50 border border-teal-200 rounded-lg px-3 py-2">
                <div class="w-7 h-7 bg-teal-500 rounded-full text-white flex items-center justify-center font-bold text-xs flex-shrink-0" id="assigneeBadgeAvatar">?</div>
                <div class="flex-1 min-w-0"><div class="text-xs text-teal-500 uppercase font-bold mb-0.5">Currently Assigned</div><div class="font-semibold text-gray-800 text-sm" id="assigneeBadgeName">—</div></div>
                <button onclick="clearAssignee()" class="text-gray-300 hover:text-red-400 transition text-xs" title="Remove assignee"><i class="fas fa-user-minus"></i></button>
            </div>
            <div class="relative mb-4" id="assignEmpWrap">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" id="assignEmpInput" placeholder="Search employee by name…" autocomplete="off" class="w-full pl-8 pr-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-300 focus:outline-none">
                <ul id="assignEmpDrop" class="absolute w-full bg-white border border-gray-200 rounded-lg mt-1 max-h-52 overflow-auto shadow-xl hidden z-50 text-sm"></ul>
            </div>
            <div id="assignSelectedBadge" class="hidden mb-4 flex items-center gap-2 bg-teal-50 border border-teal-200 rounded-lg px-3 py-2">
                <div class="w-7 h-7 bg-teal-600 rounded-full text-white flex items-center justify-center font-bold text-xs flex-shrink-0" id="selEmpAvatar">?</div>
                <div class="flex-1 min-w-0"><div class="font-semibold text-gray-800 text-sm truncate" id="selEmpName">—</div><div class="text-xs text-gray-400 font-mono" id="selEmpMeta">—</div></div>
                <button onclick="clearSelectedEmp()" class="text-gray-300 hover:text-red-400 transition"><i class="fas fa-times text-xs"></i></button>
            </div>
            <div class="flex gap-3">
                <button onclick="closeModal('assignModal')" class="flex-1 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition">Cancel</button>
                <button onclick="confirmAssign()" id="assignSubmitBtn" class="flex-1 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-sm font-medium transition"><i class="fas fa-user-check mr-1"></i>Assign</button>
            </div>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="toast" class="fixed bottom-6 right-6 z-50 hidden">
    <div id="toastInner" class="flex items-center gap-3 px-5 py-3 rounded-xl shadow-lg text-white text-sm font-medium">
        <i id="toastIcon" class="fas fa-check-circle text-lg"></i><span id="toastMsg"></span>
    </div>
</div>

<?php include '../elements/floating-menus.php'; ?>
<script src="../assets/js/script.js?t=<?php echo time(); ?>"></script>
<script>
/* ══════════════════════════════════════════
   CONFIG
══════════════════════════════════════════ */
const API = {
    all:      '<?php echo $getAllLeadsApi; ?>',
    status:   '<?php echo $updateStatusApi; ?>',
    delete:   '<?php echo $deleteLeadApi; ?>',
    move:     '<?php echo $moveToWorkApi; ?>',
    store:    '<?php echo $storeLeadApi; ?>',
    assign:   '<?php echo $assignLeadApi; ?>',
    clients:  '<?php echo $clientsApi; ?>',
    employees:'<?php echo $employeesApi; ?>',
};

// SVC — note: both 'package' and 'tour_package' slugs supported
const SVC = {
    air_ticket:   { icon:'fa-plane',           label:'Air Ticket',   cls:'pill-air_ticket'   },
    visa:         { icon:'fa-passport',         label:'Visa',         cls:'pill-visa'         },
    hotel:        { icon:'fa-hotel',            label:'Hotel',        cls:'pill-hotel'        },
    package:      { icon:'fa-suitcase-rolling', label:'Tour Package', cls:'pill-tour_package' },
    tour_package: { icon:'fa-suitcase-rolling', label:'Tour Package', cls:'pill-tour_package' },
    umrah:        { icon:'fa-kaaba',            label:'Umrah',        cls:'pill-umrah'        },
    transport:    { icon:'fa-bus',              label:'Transport',    cls:'pill-transport'    },
};

/* ══════════════════════════════════════════
   STATE
══════════════════════════════════════════ */
let allLeads      = [];
let filteredLeads = [];
let currentPage   = 1;
let currentView   = 'list';
let moveLeadSysId = null;
const PER_PAGE    = 15;

/* ══════════════════════════════════════════
   LOAD
══════════════════════════════════════════ */
async function loadLeads() {
    try {
        const res  = await fetch(API.all, { cache: 'no-store' });
        const json = await res.json();
        allLeads   = json.leads ?? [];

        const s = json.stats ?? {};
        document.getElementById('statAll').textContent       = s.all       ?? allLeads.length;
        document.getElementById('statPending').textContent   = s.pending   ?? 0;
        document.getElementById('statActive').textContent    = s.active    ?? 0;
        document.getElementById('statConverted').textContent = s.converted ?? 0;
        document.getElementById('statClosed').textContent    = s.closed    ?? 0;

        const sel = document.getElementById('advCreator');
        sel.innerHTML = '<option value="">Any Creator</option>';
        [...new Set(allLeads.map(l => l.created_by_name).filter(Boolean))].sort()
            .forEach(c => { const o = document.createElement('option'); o.value = o.textContent = c; sel.appendChild(o); });

        applyFilters();
    } catch {
        document.getElementById('leadsTableBody').innerHTML =
            `<tr><td colspan="10" class="text-center py-10 text-red-400"><i class="fas fa-exclamation-circle mr-2"></i>Failed to load leads.</td></tr>`;
    }
}

/* ══════════════════════════════════════════
   FILTERS
══════════════════════════════════════════ */
document.querySelectorAll('.stat-card').forEach(card =>
    card.addEventListener('click', () => { document.getElementById('filterStatus').value = card.dataset.filter; applyFilters(); }));
['searchInput','filterStatus','filterService'].forEach(id =>
    document.getElementById(id).addEventListener('input', applyFilters));

function applyFilters() {
    const search   = document.getElementById('searchInput').value.toLowerCase().trim();
    const status   = document.getElementById('filterStatus').value;
    const service  = document.getElementById('filterService').value;
    const dateFrom = document.getElementById('advDateFrom').value;
    const dateTo   = document.getElementById('advDateTo').value;
    const source   = document.getElementById('advSource').value;
    const creator  = document.getElementById('advCreator').value;
    const dest     = document.getElementById('advDestination').value.toLowerCase().trim();
    const assigned = document.getElementById('advAssigned').value.toLowerCase().trim();

    filteredLeads = allLeads.filter(l => {
        const ci     = sp(l.client_info)  ?? {};
        const li     = sp(l.lead_info)    ?? {};
        const sd     = sp(l.service_data) ?? {};
        const st     = sp(l.service_type) ?? [];
        const svcs   = Array.isArray(st) ? st : [st];
        const at     = sp(l.assigned_to);
        const common = sd.common ?? {};

        const countryStr = (common.countries ?? []).map(c => (c.name ?? c).toLowerCase()).join(' ');
        const segDests   = svcs.flatMap(s => {
            const d = sd[s] ?? {};
            if (s === 'hotel')      return (d.segments ?? []).map(seg => [seg.country_name, seg.city_name].filter(Boolean).join(' '));
            if (s === 'air_ticket') return (d.segments ?? []).map(seg => [seg.route, seg.from, seg.to].filter(Boolean).join(' '));
            return [];
        }).join(' ').toLowerCase();
        const allDests = (countryStr + ' ' + segDests).trim();

        const matchSearch   = !search   || (ci.name??'').toLowerCase().includes(search) || flatPhone(ci).includes(search) || (l.sys_id??'').toLowerCase().includes(search) || allDests.includes(search);
        const matchService  = !service  || svcs.includes(service) || (service === 'tour_package' && svcs.includes('package'));
        const matchStatus   = !status   || l.lead_status === status;
        const matchSource   = !source   || (li.source??'') === source;
        const matchCreator  = !creator  || (l.created_by_name??'') === creator;
        const matchDest     = !dest     || allDests.includes(dest);
        const matchAssigned = !assigned || (at?.name??'').toLowerCase().includes(assigned);

        let matchDate = true;
        if (dateFrom || dateTo) {
            const raw = l.extracted_date;
            if (raw) {
                const d = new Date(raw);
                if (dateFrom && d < new Date(dateFrom)) matchDate = false;
                if (dateTo   && d > new Date(dateTo + 'T23:59:59')) matchDate = false;
            }
        }
        return matchSearch && matchService && matchStatus && matchSource && matchCreator && matchDest && matchAssigned && matchDate;
    });

    currentPage = 1;
    render();
}

function resetFilters() {
    ['searchInput','advDateFrom','advDateTo','advDestination','advAssigned'].forEach(id => document.getElementById(id).value = '');
    ['filterStatus','filterService','advSource','advCreator'].forEach(id => document.getElementById(id).value = '');
    applyFilters();
}

function toggleAdvSearch() {
    document.getElementById('advSearchPanel').classList.toggle('open');
    document.getElementById('advBtn').classList.toggle('bg-indigo-50');
    document.getElementById('advBtn').classList.toggle('text-indigo-600');
}

/* ══════════════════════════════════════════
   VIEW
══════════════════════════════════════════ */
function setView(v) {
    currentView = v;
    document.getElementById('listView').classList.toggle('hidden', v !== 'list');
    document.getElementById('cardView').classList.toggle('hidden', v !== 'card');
    document.getElementById('btnList').classList.toggle('active', v === 'list');
    document.getElementById('btnList').classList.toggle('text-gray-500', v !== 'list');
    document.getElementById('btnCard').classList.toggle('active', v === 'card');
    document.getElementById('btnCard').classList.toggle('text-gray-500', v !== 'card');
    render();
}

function render() { currentView === 'list' ? renderTable() : renderCards(); renderPagination(); }

/* ══════════════════════════════════════════
   TABLE
══════════════════════════════════════════ */
function renderTable() {
    const tbody = document.getElementById('leadsTableBody');
    const page  = paginate();
    const start = (currentPage - 1) * PER_PAGE;

    if (!page.length) {
        tbody.innerHTML = `<tr><td colspan="10" class="text-center py-12 text-gray-400"><i class="fas fa-inbox text-3xl mb-2 block"></i>No leads found.</td></tr>`;
        return;
    }

    tbody.innerHTML = page.map((l, i) => {
        const ci = sp(l.client_info) ?? {};
        const at = sp(l.assigned_to);
        return `<tr class="hover:bg-gray-50 transition-colors">
            <td class="px-4 py-3 w-8"><input type="checkbox" class="row-cb w-4 h-4 accent-indigo-600 cursor-pointer rounded" data-id="${esc(l.sys_id)}"></td>
            <td class="px-4 py-3 text-gray-400 text-xs">${start + i + 1}</td>
            <td class="px-4 py-3"><span class="font-mono text-xs text-indigo-600 font-semibold bg-indigo-50 px-2 py-0.5 rounded">${esc(l.sys_id)}</span></td>
            <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center font-bold text-sm flex-shrink-0">${(ci.name?.[0]??'?').toUpperCase()}</div>
                    <div><div class="font-medium text-gray-800 text-sm">${esc(ci.name??'—')}</div><div class="text-xs text-gray-400">${esc(flatPhone(ci))}</div></div>
                </div>
            </td>
            <td class="px-4 py-3"><div class="flex flex-wrap gap-1">${svcPills(l)}</div></td>
            <td class="px-4 py-3">${badgeHtml(l.lead_status)}</td>
            <td class="px-4 py-3">
                ${at?.name
                    ? `<div class="flex items-center gap-1.5">
                           <div class="w-6 h-6 bg-teal-100 text-teal-700 rounded-full flex items-center justify-center font-bold text-xs flex-shrink-0">${at.name[0].toUpperCase()}</div>
                           <span class="text-xs font-medium text-teal-700">${esc(at.name)}</span>
                       </div>`
                    : `<span class="text-xs text-gray-300 italic">Not assigned yet</span>`}
            </td>
            <td class="px-4 py-3 text-xs text-gray-600">${esc(l.created_by_name??'—')}</td>
            <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap">${fmtDate(l.extracted_date)}</td>
            <td class="px-4 py-3">
                <div class="flex items-center gap-1 flex-wrap">
                    <button onclick="showLead('${esc(l.sys_id)}')"           class="action-btn bg-gray-100 hover:bg-gray-200 text-gray-600"    title="View"><i class="fas fa-eye"></i></button>
                    <a      href="edit-lead.php?id=${esc(l.sys_id)}"         class="action-btn bg-yellow-50 hover:bg-yellow-100 text-yellow-700" title="Edit"><i class="fas fa-pencil"></i></a>
                    <button onclick="openDuplicateModal('${esc(l.sys_id)}')" class="action-btn bg-purple-50 hover:bg-purple-100 text-purple-600" title="Duplicate"><i class="fas fa-copy"></i></button>
                    <button onclick="openAssignModal('${esc(l.sys_id)}')"    class="action-btn bg-teal-50 hover:bg-teal-100 text-teal-600"      title="Assign"><i class="fas fa-user-check"></i></button>
                    <button onclick="openMoveModal('${esc(l.sys_id)}')"      class="action-btn bg-indigo-50 hover:bg-indigo-100 text-indigo-700" title="Move to Work"><i class="fas fa-briefcase"></i></button>
                    <button onclick="openStatusModal('${esc(l.sys_id)}')"    class="action-btn bg-blue-50 hover:bg-blue-100 text-blue-700"      title="Change Status"><i class="fas fa-tag"></i></button>
                    <button onclick="openDeleteModal('${esc(l.sys_id)}')"    class="action-btn bg-red-50 hover:bg-red-100 text-red-600"         title="Trash"><i class="fas fa-trash-alt"></i></button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

/* ══════════════════════════════════════════
   CARDS
══════════════════════════════════════════ */
function renderCards() {
    const grid = document.getElementById('leadsCardGrid');
    const page = paginate();
    if (!page.length) { grid.innerHTML = `<div class="col-span-3 text-center py-12 text-gray-400"><i class="fas fa-inbox text-3xl mb-2 block"></i>No leads found.</div>`; return; }
    grid.innerHTML = page.map(l => {
        const ci = sp(l.client_info) ?? {};
        const at = sp(l.assigned_to);
        return `<div class="lead-card bg-white rounded-xl p-5">
            <div class="flex items-start justify-between mb-3">
                <span class="font-mono text-xs text-indigo-600 font-semibold bg-indigo-50 px-2 py-1 rounded-md">${esc(l.sys_id)}</span>
                ${badgeHtml(l.lead_status)}
            </div>
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center font-bold flex-shrink-0">${(ci.name?.[0]??'?').toUpperCase()}</div>
                <div><div class="font-semibold text-gray-800 text-sm">${esc(ci.name??'—')}</div><div class="text-xs text-gray-400">${esc(flatPhone(ci))}</div></div>
            </div>
            <div class="flex flex-wrap gap-1 mb-2">${svcPills(l)}</div>
            <div class="text-xs mb-1">
                ${at?.name
                    ? `<span class="text-teal-600 font-medium"><i class="fas fa-user-check text-teal-400 mr-1"></i>${esc(at.name)}</span>`
                    : `<span class="text-gray-300 italic">Not assigned yet</span>`}
            </div>
            <div class="text-xs text-gray-400 mb-4"><i class="fas fa-calendar mr-1"></i>${fmtDate(l.extracted_date)}</div>
            <div class="flex items-center gap-1 pt-3 border-t border-gray-100 flex-wrap">
                <button onclick="showLead('${esc(l.sys_id)}')"           class="action-btn bg-gray-100 hover:bg-gray-200 text-gray-600"    title="View"><i class="fas fa-eye"></i></button>
                <a      href="edit-lead.php?id=${esc(l.sys_id)}"         class="action-btn bg-yellow-50 hover:bg-yellow-100 text-yellow-700" title="Edit"><i class="fas fa-pencil"></i></a>
                <button onclick="openDuplicateModal('${esc(l.sys_id)}')" class="action-btn bg-purple-50 hover:bg-purple-100 text-purple-600" title="Duplicate"><i class="fas fa-copy"></i></button>
                <button onclick="openAssignModal('${esc(l.sys_id)}')"    class="action-btn bg-teal-50 hover:bg-teal-100 text-teal-600"      title="Assign"><i class="fas fa-user-check"></i></button>
                <button onclick="openMoveModal('${esc(l.sys_id)}')"      class="action-btn bg-indigo-50 hover:bg-indigo-100 text-indigo-700" title="Move to Work"><i class="fas fa-briefcase"></i></button>
                <button onclick="openStatusModal('${esc(l.sys_id)}')"    class="action-btn bg-blue-50 hover:bg-blue-100 text-blue-700"      title="Change Status"><i class="fas fa-tag"></i></button>
                <button onclick="openDeleteModal('${esc(l.sys_id)}')"    class="action-btn bg-red-50 hover:bg-red-100 text-red-600"         title="Trash"><i class="fas fa-trash-alt"></i></button>
            </div>
        </div>`;
    }).join('');
}

/* ══════════════════════════════════════════
   PAGINATION
══════════════════════════════════════════ */
function paginate() { const s=(currentPage-1)*PER_PAGE; return filteredLeads.slice(s,s+PER_PAGE); }
function renderPagination() {
    const total=filteredLeads.length, pages=Math.ceil(total/PER_PAGE);
    const start=total?(currentPage-1)*PER_PAGE+1:0, end=Math.min(currentPage*PER_PAGE,total);
    document.getElementById('paginationInfo').textContent = total ? `Showing ${start}–${end} of ${total} leads` : 'No results';
    const btns = document.getElementById('paginationBtns'); btns.innerHTML='';
    if (pages<=1) return;
    const btn=(label,page,disabled,active)=>{const b=document.createElement('button');b.innerHTML=label;b.className=`px-3 py-1.5 rounded-lg text-sm border font-medium transition ${active?'bg-indigo-600 text-white border-indigo-600':disabled?'bg-gray-50 text-gray-300 border-gray-200 cursor-not-allowed':'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'}`;if(!disabled&&!active)b.onclick=()=>{currentPage=page;render();};btns.appendChild(b);};
    btn('<i class="fas fa-chevron-left text-xs"></i>',currentPage-1,currentPage===1);
    for(let p=1;p<=pages;p++){if(pages>7&&p>2&&p<pages-1&&Math.abs(p-currentPage)>1){if(p===3||p===pages-2){const s=document.createElement('span');s.textContent='…';s.className='px-2 text-gray-400';btns.appendChild(s);}continue;}btn(p,p,false,p===currentPage);}
    btn('<i class="fas fa-chevron-right text-xs"></i>',currentPage+1,currentPage===pages);
}

/* ══════════════════════════════════════════
   SHOW LEAD MODAL
══════════════════════════════════════════ */
function showLead(sysId) {
    const lead = allLeads.find(l => l.sys_id === sysId); if (!lead) return;
    const ci=sp(lead.client_info)??{}, li=sp(lead.lead_info)??{}, sd=sp(lead.service_data)??{};
    const st=sp(lead.service_type)??[], svcs=Array.isArray(st)?st:[st];
    const at=sp(lead.assigned_to), common=sd.common??{};

    const countries=(common.countries??[]).map(c=>c.name??c).join(', ');
    const paxParts=[];
    if((common.pax_adult??0)>0)  paxParts.push(`Adult: ${common.pax_adult}`);
    if((common.pax_child??0)>0)  paxParts.push(`Child: ${common.pax_child}`);
    if((common.pax_infant??0)>0) paxParts.push(`Infant: ${common.pax_infant}`);

    const commonRows=[
        common.title           &&['Title',       common.title],
        countries              &&['Destination',  countries],
        paxParts.length        &&['Pax',          paxParts.join(' · ')],
        common.tentative_start &&['Start',        fmtDate(common.tentative_start)],
        common.tentative_end   &&['End',          fmtDate(common.tentative_end)],
        common.budget          &&['Budget',       'BDT '+Number(common.budget).toLocaleString()],
        li.source              &&['Source',       li.source],
        at?.name               &&['Assigned To',  at.name],
    ].filter(Boolean);

    const notesRaw=li.notes||common.notes||'';
    let notesHtml;
    if(typeof notesRaw==='string'&&notesRaw.trim()) notesHtml=`<p class="text-sm text-gray-700 whitespace-pre-wrap">${esc(notesRaw)}</p>`;
    else if(Array.isArray(notesRaw)&&notesRaw.length) notesHtml=notesRaw.map(n=>`<p class="text-sm text-gray-700 py-0.5">${esc(typeof n==='object'?(n.text||n.note||''):n)}</p>`).join('');
    else notesHtml=`<p class="text-sm text-gray-400">No notes.</p>`;

    const svcHtml=svcs.map(svc=>renderSvcSection(svc,sd[svc]??{})).join('');

    document.getElementById('showModalContent').innerHTML=`
        <div class="flex items-center gap-4 mb-5 pb-5 border-b border-gray-100">
            <div class="w-14 h-14 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-600 font-bold text-2xl flex-shrink-0">${(ci.name?.[0]??'?').toUpperCase()}</div>
            <div class="flex-1">
                <div class="text-xl font-bold text-gray-800">${esc(ci.name??'—')}</div>
                <div class="text-sm text-gray-500 mt-0.5">${esc(flatPhone(ci))}${ci.email?' · '+esc(ci.email):''}</div>
                <div class="flex flex-wrap items-center gap-2 mt-2">
                    <span class="font-mono text-xs text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded">${esc(lead.sys_id)}</span>
                    ${badgeHtml(lead.lead_status)}
                    ${at?.name?`<span class="text-xs text-teal-600 bg-teal-50 px-2 py-0.5 rounded flex items-center gap-1"><i class="fas fa-user-check"></i>${esc(at.name)}</span>`:''}
                </div>
            </div>
            <div class="text-right text-xs text-gray-400 flex-shrink-0"><div>By ${esc(lead.created_by_name??'—')}</div><div>${fmtDate(lead.extracted_date)}</div></div>
        </div>
        ${commonRows.length?`<div class="grid grid-cols-2 gap-2 mb-5">${commonRows.map(([k,v])=>`<div class="bg-gray-50 rounded-lg p-3"><div class="text-[10px] text-gray-400 uppercase tracking-wide mb-1">${esc(k)}</div><div class="text-sm font-medium text-gray-800">${esc(String(v))}</div></div>`).join('')}</div>`:''}
        <div class="flex flex-wrap gap-1.5 mb-5">${svcPills(lead)}</div>
        <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Service Details</h4>
        ${svcHtml}
        <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2 mt-5">Notes</h4>
        <div class="bg-blue-50 rounded-xl px-4 py-3 mb-4">${notesHtml}</div>
        <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
            <a href="edit-lead.php?id=${esc(lead.sys_id)}" class="px-4 py-2 bg-yellow-50 hover:bg-yellow-100 text-yellow-700 rounded-lg text-sm font-medium transition"><i class="fas fa-pencil mr-1"></i>Edit</a>
            <button onclick="closeModal('showModal');openMoveModal('${esc(lead.sys_id)}')" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition"><i class="fas fa-briefcase mr-1"></i>Move to Work</button>
        </div>`;

    document.getElementById('showModal').classList.remove('hidden');
}

/* ── Service section renderers ── */
function kv(k,v){ return `<div class="kv-row"><span class="kv-key">${esc(k)}</span><span class="kv-val">${esc(String(v))}</span></div>`; }
function spInsBlock(list){ if(!list?.length)return''; return`<div class="mt-2 pt-2 border-t border-amber-50 space-y-0.5">${list.map(s=>`<div class="sp-ins"><i class="fas fa-triangle-exclamation mt-0.5 flex-shrink-0"></i>${esc(s)}</div>`).join('')}</div>`; }
function noData(){ return`<p class="text-sm text-gray-400 italic">No details.</p>`; }

const SVC_COLORS = {air_ticket:'text-sky-400',visa:'text-violet-500',hotel:'text-pink-400',tour_package:'text-emerald-500',package:'text-emerald-500',umrah:'text-amber-500',transport:'text-green-500'};

function renderSvcSection(svc, data) {
    const info = SVC[svc] ?? {icon:'fa-circle',label:svc.replace(/_/g,' '),cls:''};
    let inner  = '';

    if (svc === 'air_ticket') {
        const segs = data.segments ?? [];
        inner = segs.length ? segs.map((seg,i)=>{
            const rows=[
                seg.route            && kv('Route',      seg.route),
                (seg.from||seg.to)   && kv('From → To',  `${seg.from??'?'} → ${seg.to??'?'}`),
                seg.airline          && kv('Airline',    seg.airline),
                seg.class            && kv('Class',      seg.class),
                seg.luggage?.value   && kv('Luggage',    `${seg.luggage.value} ${seg.luggage.unit??'Pieces'}`),
                seg.departure_date   && kv('Departure',  fmtDate(seg.departure_date)),
                seg.arrival_date     && kv('Arrival',    fmtDate(seg.arrival_date)),
                seg.date_flexibility && kv('Flexibility',seg.date_flexibility),
            ].filter(Boolean).join('');
            return`<div class="seg-box seg-box-air"><div class="seg-label ${SVC_COLORS.air_ticket}"><i class="fas fa-plane mr-1"></i>Segment ${i+1}${seg.route?' — '+seg.route:''}</div><div>${rows}</div>${spInsBlock(seg.special_instruction)}</div>`;
        }).join('') : noData();

    } else if (svc === 'hotel') {
        const segs=data.segments??[], flex=data.booking_flexibility;
        inner = segs.length ? segs.map((seg,i)=>{
            const rows=[
                seg.country_name && kv('Country', seg.country_name),
                seg.city_name    && kv('City',    seg.city_name),
                seg.hotel_name   && kv('Hotel',   seg.hotel_name),
                seg.check_in     && kv('Check-in',fmtDate(seg.check_in)),
                seg.check_out    && kv('Check-out',fmtDate(seg.check_out)),
            ].filter(Boolean).join('');
            return`<div class="seg-box seg-box-hotel"><div class="seg-label ${SVC_COLORS.hotel}"><i class="fas fa-hotel mr-1"></i>Segment ${i+1}${seg.hotel_name?' — '+seg.hotel_name:''}</div><div>${rows}</div>${spInsBlock(seg.special_instruction)}</div>`;
        }).join('')+(flex?`<div class="kv-row mt-2">${kv('Booking Flexibility',flex)}</div>`:'') : noData();

    } else if (svc === 'visa') {
        const segs=data.segments??[];
        inner = segs.length ? segs.map((seg,i)=>{
            const rows=[
                seg.country_name      && kv('Country',   seg.country_name),
                seg.category_name     && kv('Category',  seg.category_name),
                seg.sub_category      && kv('Sub Cat.',  seg.sub_category),
                seg.invitation_status && kv('Invitation',seg.invitation_status),
            ].filter(Boolean).join('');
            const apps=(seg.applicants??[]).map((a,ai)=>
                `<div class="flex items-center gap-1.5 text-xs text-gray-600 py-0.5">
                    ${a.is_main?'<i class="fas fa-star text-amber-400 text-[10px]"></i>':'<i class="fas fa-user text-gray-300 text-[10px]"></i>'}
                    <span>${esc(a.name||`Applicant ${ai+1}`)}${a.profession?' · '+esc(a.profession):''}</span>
                </div>`).join('');
            const cost=(seg.cost_bearer??[]).map(c=>c.replace(/_/g,' ')).join(', ');
            return`<div class="seg-box seg-box-visa">
                <div class="seg-label ${SVC_COLORS.visa}"><i class="fas fa-passport mr-1"></i>Segment ${i+1}${seg.country_name?' — '+seg.country_name:''}</div>
                <div>${rows}</div>
                ${apps?`<div class="mt-2 pt-2 border-t border-violet-50"><p class="text-[10px] text-gray-400 uppercase mb-1">Applicants</p>${apps}</div>`:''}
                ${cost?`<div class="text-xs text-gray-500 mt-1"><i class="fas fa-money-bill-wave text-green-400 mr-1"></i>Cost: ${esc(cost)}</div>`:''}
                ${spInsBlock(seg.special_instruction)}
            </div>`;
        }).join('') : noData();

    } else if (svc === 'tour_package' || svc === 'package') {
        const dests=(data.destinations??[]).map(d=>`${d.country_name??''}${(d.city_names??[]).length?' ('+d.city_names.join(', ')+')':''}`).filter(Boolean).join(' · ');
        const accs=(data.accommodations??[]).map(a=>
            `<div class="text-xs text-gray-600 flex gap-1.5 py-0.5">
                <i class="fas fa-hotel text-gray-300 text-[10px] mt-0.5"></i>
                <span>${esc([a.hotel_name,a.city_name].filter(Boolean).join(' · '))}${a.check_in?` <span class="text-gray-400">${fmtDate(a.check_in)} → ${fmtDate(a.check_out)}</span>`:''}</span>
            </div>`).join('');
        const rows=[
            data.title    && kv('Title',   data.title),
            data.type     && kv('Type',    data.type),
            data.currency && kv('Currency',data.currency),
            dests         && kv('Destinations',dests),
        ].filter(Boolean).join('');
        inner=`<div class="seg-box seg-box-pkg">
            <div>${rows}</div>
            ${data.description?`<p class="text-xs text-gray-500 mt-2">${esc(data.description)}</p>`:''}
            ${accs?`<div class="mt-2 pt-2 border-t border-emerald-50"><p class="text-[10px] text-gray-400 uppercase mb-1">Accommodations</p>${accs}</div>`:''}
            ${spInsBlock(data.special_instruction)}
        </div>`;

    } else if (svc === 'umrah') {
        const rows=[
            data.umrah_type       && kv('Umrah Type',  data.umrah_type.replace(/_/g,' ')),
            data.package_type     && kv('Package',     data.package_type.replace(/_/g,' ')),
            data.flight_date_type && kv('Flight Type', data.flight_date_type.replace(/_/g,' ')),
            data.departure_date   && kv('Departure',   fmtDate(data.departure_date)),
            data.total_nights     && kv('Total Nights',data.total_nights),
            data.makkah_nights    && kv('Makkah',      data.makkah_nights+' nights'),
            data.madina_nights    && kv('Madina',      data.madina_nights+' nights'),
            data.has_transport    && kv('Transport',   data.has_transport),
        ].filter(Boolean).join('');
        inner=`<div class="seg-box seg-box-umrah">
            <div>${rows}</div>
            ${data.description?`<p class="text-xs text-gray-500 mt-2">${esc(data.description)}</p>`:''}
            ${spInsBlock(data.special_instruction)}
        </div>`;

    } else if (svc === 'transport') {
        const segs=data.segments??[];
        inner = segs.length ? segs.map((seg,i)=>{
            const rows=[
                seg.type           && kv('Type',   seg.type),
                seg.route          && kv('Route',  seg.route),
                (seg.from||seg.to) && kv('From → To',`${seg.from??'?'} → ${seg.to??'?'}`),
                seg.start_datetime && kv('Start',  fmtDateTime(seg.start_datetime)),
                seg.end_datetime   && kv('End',    fmtDateTime(seg.end_datetime)),
                seg.luggage?.value && kv('Luggage',`${seg.luggage.value} ${seg.luggage.unit??'Pieces'}`),
            ].filter(Boolean).join('');
            return`<div class="seg-box seg-box-tr"><div class="seg-label ${SVC_COLORS.transport}"><i class="fas fa-bus mr-1"></i>Segment ${i+1}${seg.route?' — '+seg.route:''}</div><div>${rows}</div>${spInsBlock(seg.special_instruction)}</div>`;
        }).join('') : noData();

    } else {
        const ignore=new Set(['segments','destinations','accommodations','special_instruction']);
        const rows=Object.entries(data).filter(([k,v])=>!ignore.has(k)&&v!==''&&v!==null)
            .map(([k,v])=>kv(k.replace(/_/g,' '),typeof v==='object'?JSON.stringify(v):v)).join('');
        inner=rows?`<div>${rows}</div>`:noData();
    }

    return`<div class="svc-section">
        <div class="flex items-center gap-2 mb-3">
            <i class="fas ${info.icon} text-sm ${SVC_COLORS[svc]??'text-gray-400'}"></i>
            <span class="font-semibold text-gray-700 text-sm">${esc(info.label)}</span>
        </div>
        ${inner}
    </div>`;
}

/* ══════════════════════════════════════════
   STATUS
══════════════════════════════════════════ */
function openStatusModal(sysId) { document.getElementById('statusLeadId').value=sysId; document.getElementById('statusModal').classList.remove('hidden'); }
async function changeStatus(newStatus) {
    const sysId=document.getElementById('statusLeadId').value; closeModal('statusModal');
    try {
        const r=await fetch(API.status,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({sys_id:sysId,status:newStatus})});
        const j=await r.json();
        if(j.success||j.status==='success'){showToast('success','Status updated.');loadLeads();}else showToast('error',j.message||'Failed.');
    }catch{showToast('error','Network error.');}
}

/* ══════════════════════════════════════════
   DELETE
══════════════════════════════════════════ */
function openDeleteModal(sysId) {
    document.getElementById('deleteLeadId').value=sysId;
    document.getElementById('deleteLeadIdDisplay').textContent=sysId;
    document.getElementById('deleteConfirmInput').value='';
    const btn=document.getElementById('deleteConfirmBtn');
    btn.disabled=true;btn.className='flex-1 py-2.5 bg-red-300 text-white rounded-lg font-medium text-sm transition cursor-not-allowed';
    document.getElementById('deleteModal').classList.remove('hidden');
}
document.getElementById('deleteConfirmInput').addEventListener('input',function(){
    const match=this.value.trim()===document.getElementById('deleteLeadId').value;
    const btn=document.getElementById('deleteConfirmBtn');btn.disabled=!match;
    btn.className=`flex-1 py-2.5 rounded-lg font-medium text-sm transition ${match?'bg-red-600 hover:bg-red-700 text-white cursor-pointer':'bg-red-300 text-white cursor-not-allowed'}`;
});
async function confirmDelete() {
    const sysId=document.getElementById('deleteLeadId').value;
    if(document.getElementById('deleteConfirmInput').value.trim()!==sysId)return;
    closeModal('deleteModal');
    try{const r=await fetch(API.delete,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'soft',sys_id:sysId})});const j=await r.json();if(j.success){showToast('success','Lead moved to trash.');loadLeads();}else showToast('error',j.message||'Failed.');}catch{showToast('error','Network error.');}
}

/* ══════════════════════════════════════════
   MOVE TO WORK
══════════════════════════════════════════ */
function openMoveModal(sysId) {
    moveLeadSysId=sysId;
    const lead=allLeads.find(l=>l.sys_id===sysId);
    const ci=sp(lead?.client_info)??{},sd=sp(lead?.service_data)??{},st=sp(lead?.service_type)??[];
    const svcs=Array.isArray(st)?st:[st],common=sd.common??{};
    const title=common.title||(SVC[svcs[0]]?.label??svcs[0]??'Lead')+' – '+(ci.name??sysId);
    const dests=(common.countries??[]).map(c=>c.name??c).join(', ')||'—';
    document.getElementById('moveModalContent').innerHTML=`
        <div class="bg-indigo-50 rounded-xl p-4 mb-4"><p class="text-xs text-indigo-400 uppercase tracking-wide mb-1">Lead Title</p><p class="font-semibold text-indigo-800 text-base">${esc(title)}</p></div>
        <div class="grid grid-cols-2 gap-3 mb-4">
            <div class="bg-gray-50 rounded-lg p-3"><p class="text-xs text-gray-400 uppercase mb-1">Client</p><p class="text-sm font-semibold text-gray-800">${esc(ci.name??'—')}</p><p class="text-xs text-gray-400">${esc(flatPhone(ci))}</p></div>
            <div class="bg-gray-50 rounded-lg p-3"><p class="text-xs text-gray-400 uppercase mb-1">Destination</p><p class="text-sm font-semibold text-gray-800">${esc(dests)}</p></div>
        </div>
        <div class="mb-4"><p class="text-xs text-gray-400 uppercase mb-2">Services</p><div class="flex flex-wrap gap-1.5">${svcPills(lead)}</div></div>
        <div class="bg-amber-50 border border-amber-100 rounded-lg p-3 text-xs text-amber-700"><i class="fas fa-info-circle mr-1.5"></i>Lead status → <strong>Converted</strong> after moving.</div>`;
    document.getElementById('moveModal').classList.remove('hidden');
}
async function confirmMove() {
    if(!moveLeadSysId)return; closeModal('moveModal');
    try{const r=await fetch(API.move,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({sys_id:moveLeadSysId})});const j=await r.json();if(j.success||j.status==='success'){showToast('success','Lead moved to workboard!');setTimeout(()=>{window.location.href='index-works.php';},1200);}else showToast('error',j.message||'Failed.');}catch{showToast('error','Network error.');}
}

/* ══════════════════════════════════════════
   EXPORT
══════════════════════════════════════════ */
function toggleDownloadMenu(){document.getElementById('downloadMenu').classList.toggle('hidden');}
document.addEventListener('click',e=>{if(!e.target.closest('#downloadWrap'))document.getElementById('downloadMenu').classList.add('hidden');});
function exportData(type,filtered){
    document.getElementById('downloadMenu').classList.add('hidden');
    const data=filtered?filteredLeads:allLeads;
    if(!data.length){showToast('error','No data to export.');return;}
    if(type==='csv'||type==='excel'){
        const rows=[['Lead ID','Client','Phone','Services','Status','Assigned To','Source','Created By','Date']];
        data.forEach(l=>{const ci=sp(l.client_info)??{},li=sp(l.lead_info)??{},at=sp(l.assigned_to),st=sp(l.service_type)??[],svcs=Array.isArray(st)?st:[st];rows.push([l.sys_id,ci.name??'',flatPhone(ci),svcs.map(s=>SVC[s]?.label??s).join(', '),l.lead_status,at?.name??'',li.source??'',l.created_by_name??'',fmtDate(l.extracted_date)]);});
        const csv=rows.map(r=>r.map(c=>`"${String(c).replace(/"/g,'""')}"`).join(',')).join('\n');
        const ext=type==='excel'?'xls':'csv';
        const blob=new Blob([csv],{type:type==='excel'?'application/vnd.ms-excel':'text/csv'});
        const a=document.createElement('a');a.href=URL.createObjectURL(blob);a.download=`leads_${new Date().toISOString().slice(0,10)}.${ext}`;a.click();
        showToast('success',`${type.toUpperCase()} downloaded.`);
    }else{
        const rows=data.map((l,i)=>{const ci=sp(l.client_info)??{},li=sp(l.lead_info)??{},at=sp(l.assigned_to),st=sp(l.service_type)??[],svcs=Array.isArray(st)?st:[st];return`<tr><td>${i+1}</td><td>${esc(l.sys_id)}</td><td>${esc(ci.name??'')}</td><td>${esc(flatPhone(ci))}</td><td>${svcs.map(s=>SVC[s]?.label??s).join(', ')}</td><td>${esc(l.lead_status)}</td><td>${esc(at?.name??'—')}</td><td>${esc(li.source??'')}</td><td>${esc(l.created_by_name??'')}</td><td>${fmtDate(l.extracted_date)}</td></tr>`;}).join('');
        const win=window.open('','_blank');
        win.document.write(`<!DOCTYPE html><html><head><title>Lead Report</title><style>body{font-family:sans-serif;font-size:11px;padding:20px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #ddd;padding:5px 8px;text-align:left}th{background:#f3f4f6;font-weight:600}tr:nth-child(even){background:#f9fafb}h2{margin-bottom:4px}p{color:#666;margin:0 0 12px}</style></head><body><h2>Lead Report – TravHub</h2><p>Generated: ${new Date().toLocaleString()} · ${filtered?'Filtered':'All'} (${data.length} records)</p><table><thead><tr><th>#</th><th>Lead ID</th><th>Client</th><th>Phone</th><th>Services</th><th>Status</th><th>Assigned To</th><th>Source</th><th>Created By</th><th>Date</th></tr></thead><tbody>${rows}</tbody></table></body></html>`);
        win.document.close();win.print();
    }
}

/* ══════════════════════════════════════════
   DUPLICATE
══════════════════════════════════════════ */
let dupClientsData=[],dupSelectedClient=null,dupCTimer;
async function loadDupClients(){if(dupClientsData.length)return;try{const res=await fetch(API.clients,{cache:'no-store'});const j=await res.json();dupClientsData=(j.clients??[]).map(c=>{let phone='';try{const p=typeof c.phone==='string'?JSON.parse(c.phone):c.phone;phone=p?.primary_no??p?.primary??'';}catch{}return{...c,_phone:phone};});}catch{}}
function openDuplicateModal(sysId){
    const lead=allLeads.find(l=>l.sys_id===sysId);if(!lead)return;
    document.getElementById('dupSourceId').value=sysId;
    const sd=sp(lead.service_data)??{},common=sd.common??{},ci=sp(lead.client_info)??{},svcs=sp(lead.service_type)??[];
    document.getElementById('dupTitle').value=((common.title||((SVC[svcs[0]]?.label??svcs[0]??'Lead')+' — '+(ci.name??'')))+' — Copy').trim();
    clearDupClient();document.getElementById('dupClientInput').value='';
    document.getElementById('duplicateModal').classList.remove('hidden');loadDupClients();
}
const dupInput=document.getElementById('dupClientInput'),dupDrop=document.getElementById('dupClientDrop');
dupInput?.addEventListener('input',()=>{clearTimeout(dupCTimer);dupCTimer=setTimeout(()=>filterDupClients(dupInput.value),250);});
dupInput?.addEventListener('focus',()=>filterDupClients(dupInput.value));
document.addEventListener('click',e=>{if(!e.target.closest('#dupClientWrap'))dupDrop?.classList.add('hidden');});
function filterDupClients(q){const v=q.toLowerCase().trim();const list=v?dupClientsData.filter(c=>(c.name||'').toLowerCase().includes(v)||(c.sys_id||'').includes(v)):dupClientsData.slice(0,20);dupDrop.classList.remove('hidden');if(!list.length){dupDrop.innerHTML=`<li class="px-4 py-3 text-center text-gray-400 text-xs">No clients found</li>`;return;}dupDrop.innerHTML=list.map(c=>`<li class="px-4 py-2.5 cursor-pointer hover:bg-purple-50 border-b border-gray-50 last:border-0 text-sm" onclick='selectDupClient(${JSON.stringify(JSON.stringify(c))})'><div class="flex items-center gap-2"><div class="w-7 h-7 bg-purple-500 rounded-full text-white flex items-center justify-center font-bold text-xs flex-shrink-0">${'?'}</div><div><div class="font-medium">${esc(c.name)}</div><div class="text-xs text-gray-400 font-mono">${esc(c.sys_id)}</div></div></div></li>`).join('');}
function selectDupClient(jsonStr){const c=JSON.parse(jsonStr);dupSelectedClient=c;dupInput.value=`${c.sys_id} | ${c.name}`;document.getElementById('dupBadgeAvatar').textContent=(c.name?.[0]??'C').toUpperCase();document.getElementById('dupBadgeName').textContent=c.name;document.getElementById('dupBadgeMeta').textContent=`${c.sys_id}${c._phone?' · '+c._phone:''}`;document.getElementById('dupClientBadge').classList.remove('hidden');dupDrop.classList.add('hidden');}
function clearDupClient(){dupSelectedClient=null;document.getElementById('dupClientBadge').classList.add('hidden');}
async function refreshDupClients(){dupClientsData=[];await loadDupClients();filterDupClients(dupInput?.value??'');showToast('success','Client list refreshed');}
async function confirmDuplicate(){
    if(!dupSelectedClient){showToast('error','Please select a client');return;}
    const sourceId=document.getElementById('dupSourceId').value,source=allLeads.find(l=>l.sys_id===sourceId);if(!source)return;
    const btn=document.getElementById('dupSubmitBtn');btn.disabled=true;btn.innerHTML='<i class="fas fa-spinner fa-spin text-xs mr-1"></i>Duplicating…';
    const newSd=JSON.parse(JSON.stringify(sp(source.service_data)??{}));
    if(newSd.common)newSd.common.title=document.getElementById('dupTitle').value.trim();
    try{
        const res=await fetch(API.store,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({client_info:{sys_id:dupSelectedClient.sys_id,name:dupSelectedClient.name,phone:dupSelectedClient._phone??'',email:dupSelectedClient._email??''},service_type:sp(source.service_type)??[],service_count:(sp(source.service_type)??[]).length,service_data:newSd,special_instruction:sp(source.special_instruction)??[],lead_info:sp(source.lead_info)??{}})});
        const json=await res.json();
        if(json.status==='success'){showToast('success','Lead duplicated!');closeModal('duplicateModal');loadLeads();}else showToast('error',json.message??'Failed');
    }catch{showToast('error','Network error');}
    btn.disabled=false;btn.innerHTML='<i class="fas fa-copy mr-1"></i>Duplicate Lead';
}

/* ══════════════════════════════════════════
   ASSIGN — uses dedicated assign.php API
   assigned_to stored as {name, sys_id} JSON
══════════════════════════════════════════ */
let allEmployees=[],selEmployee=null,assignEmpTimer;
async function loadEmployees(){if(allEmployees.length)return;try{const res=await fetch(API.employees,{cache:'no-store'});const json=await res.json();allEmployees=(json.employees??[]).filter(e=>e.status!=='inactive');}catch{}}
function openAssignModal(sysId){
    const lead=allLeads.find(l=>l.sys_id===sysId);if(!lead)return;
    document.getElementById('assignLeadId').value=sysId;
    document.getElementById('assignEmpInput').value='';
    clearSelectedEmp();
    // Show current assignee — parsed from {name, sys_id}
    const at=sp(lead.assigned_to);
    const curBadge=document.getElementById('currentAssigneeBadge');
    if(at?.name){
        document.getElementById('assigneeBadgeAvatar').textContent=at.name[0].toUpperCase();
        document.getElementById('assigneeBadgeName').textContent=at.name;
        curBadge.classList.remove('hidden');
    }else{curBadge.classList.add('hidden');}
    document.getElementById('assignModal').classList.remove('hidden');
    loadEmployees().then(()=>filterAssignEmps(''));
}
const assignInp=document.getElementById('assignEmpInput'),assignDrop=document.getElementById('assignEmpDrop');
assignInp?.addEventListener('input',()=>{clearTimeout(assignEmpTimer);assignEmpTimer=setTimeout(()=>filterAssignEmps(assignInp.value),200);});
assignInp?.addEventListener('focus',()=>filterAssignEmps(assignInp.value));
document.addEventListener('click',e=>{if(!e.target.closest('#assignEmpWrap'))assignDrop?.classList.add('hidden');});
function filterAssignEmps(q){
    const v=q.toLowerCase().trim();
    const list=v?allEmployees.filter(e=>(e.name||'').toLowerCase().includes(v)||(e.sys_id||'').includes(v)):allEmployees.slice(0,25);
    if(!assignDrop)return;assignDrop.classList.remove('hidden');
    if(!list.length){assignDrop.innerHTML=`<li class="px-4 py-3 text-center text-gray-400 text-xs">No employees found</li>`;return;}
    assignDrop.innerHTML=list.map(e=>`<li class="px-4 py-2.5 cursor-pointer hover:bg-teal-50 border-b border-gray-50 last:border-0 text-sm" onclick="selectAssignEmp('${esc(e.sys_id)}','${esc(e.name)}','${esc(e.department_name??'')}')"><div class="flex items-center gap-2"><div class="w-7 h-7 bg-teal-600 rounded-full text-white flex items-center justify-center font-bold text-xs flex-shrink-0">${(e.name?.[0]??'E').toUpperCase()}</div><div><div class="font-medium text-gray-800">${esc(e.name)}</div><div class="text-xs text-gray-400">${esc(e.department_name??'')}${e.sys_id?' · '+e.sys_id:''}</div></div></div></li>`).join('');
}
function selectAssignEmp(sysId,name,dept){
    selEmployee={sys_id:sysId,name,dept};assignInp.value=name;
    document.getElementById('selEmpAvatar').textContent=(name[0]??'E').toUpperCase();
    document.getElementById('selEmpName').textContent=name;
    document.getElementById('selEmpMeta').textContent=dept?`${dept} · ${sysId}`:sysId;
    document.getElementById('assignSelectedBadge').classList.remove('hidden');assignDrop.classList.add('hidden');
}
function clearSelectedEmp(){selEmployee=null;document.getElementById('assignSelectedBadge').classList.add('hidden');if(assignInp)assignInp.value='';}
function clearAssignee(){selEmployee={sys_id:null,name:null,clear:true};document.getElementById('currentAssigneeBadge').classList.add('hidden');showToast('success','Assignee will be removed on save');}
async function confirmAssign(){
    const sysId=document.getElementById('assignLeadId').value;
    if(!selEmployee){showToast('error','Please select an employee');return;}
    // Build object or null
    const assignTo=selEmployee.clear ? null : {name:selEmployee.name, sys_id:selEmployee.sys_id};
    const btn=document.getElementById('assignSubmitBtn');btn.disabled=true;btn.innerHTML='<i class="fas fa-spinner fa-spin text-xs mr-1"></i>Saving…';
    try{
        const res=await fetch(API.assign,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({sys_id:sysId,assigned_to:assignTo})});
        const json=await res.json();
        if(json.status==='success'){
            showToast('success',selEmployee.clear?'Assignee removed.':`Assigned to ${selEmployee.name}.`);
            closeModal('assignModal');loadLeads();
        }else showToast('error',json.message??'Failed');
    }catch{showToast('error','Network error');}
    btn.disabled=false;btn.innerHTML='<i class="fas fa-user-check mr-1"></i>Assign';
}

/* ══════════════════════════════════════════
   HELPERS
══════════════════════════════════════════ */
function closeModal(id){document.getElementById(id).classList.add('hidden');}
function sp(v){if(!v)return null;if(typeof v==='object')return v;try{return JSON.parse(v);}catch{return null;}}
function esc(s){return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
function flatPhone(ci){if(!ci?.phone)return'';if(typeof ci.phone==='string'){try{const p=JSON.parse(ci.phone);return p.primary_no??Object.values(p).filter(Boolean).join(', ');}catch{}return ci.phone;}if(typeof ci.phone==='object')return ci.phone.primary_no??Object.values(ci.phone).filter(Boolean).join(', ');return'';}
function svcPills(lead){const st=sp(lead.service_type)??[];const svcs=Array.isArray(st)?st:[st];return svcs.map(s=>{const info=SVC[s]??{icon:'fa-circle',label:s.replace(/_/g,' '),cls:''};return`<span class="svc-pill ${info.cls}"><i class="fas ${info.icon}"></i>${info.label}</span>`;}).join('');}
function fmtDate(d){if(!d)return'—';try{return new Date(d).toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'});}catch{return d;}}
function fmtDateTime(d){if(!d)return'—';try{return new Date(d).toLocaleString('en-GB',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'});}catch{return d;}}
function badgeHtml(s){const m={pending:['badge-pending','⏳ Pending'],active:['badge-active','✅ Active'],converted:['badge-converted','🔄 Converted'],hold:['badge-hold','⏸ On Hold'],closed:['badge-closed','❌ Closed']};const[c,l]=m[s]??['',s];return`<span class="badge ${c}">${l}</span>`;}
function showToast(type,msg){const t=document.getElementById('toast'),i=document.getElementById('toastInner');document.getElementById('toastMsg').textContent=msg;document.getElementById('toastIcon').className=`fas ${type==='success'?'fa-check-circle':'fa-exclamation-circle'} text-lg`;i.className=`flex items-center gap-3 px-5 py-3 rounded-xl shadow-lg text-white text-sm font-medium ${type==='success'?'bg-green-600':'bg-red-500'}`;t.classList.remove('hidden');setTimeout(()=>t.classList.add('hidden'),3500);}

/* ══════════════════════════════════════════
   INIT
══════════════════════════════════════════ */
loadLeads();
loadEmployees();
</script>
</body>
</html>