<?php
// FILE PATH: /pages/show-works.php
include_once('./authenticate.php');
$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) { $ip_port = 'http://103.104.219.3:898/'; }
if (substr($ip_port, -1) !== '/') { $ip_port .= '/'; }

$getWorkApi       = $ip_port . 'api/works/get-work.php';
$updateStatusApi  = $ip_port . 'api/works/update-status.php';
$assignApi        = $ip_port . 'api/works/assign.php';
$addServiceApi    = $ip_port . 'api/works/add-service.php';
$deptApi          = $ip_port . 'api/masterdata/departments/endpoints.php';
$workTravelersApi = $ip_port . 'api/works/travelers.php';
$allTravelersApi  = $ip_port . 'api/travelers/all-travelers.php';
$airTicketsApi    = $ip_port . 'api/air-tickets/endpoints.php';
$notesApi         = $ip_port . 'api/works/notes.php';

$workSysId  = $_GET['id'] ?? '';
$deepLinkSw = $_GET['sw'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Work — TravHub</title>
    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root { --accent:#4f46e5; }

        /* ── Layout ─────────────────────────────────────────── */
        #pageContent { display:flex; flex-direction:column; height:calc(100vh - 64px); }
        #bodyWrap    { display:flex; flex:1; overflow:hidden; }
        #mainArea    { flex:1; overflow-y:auto; padding:16px; }
        #rightSidebar{
            width:288px; flex-shrink:0;
            border-left:1px solid #f1f5f9;
            background:#fff;
            overflow-y:auto; overflow-x:hidden;
            padding:10px;
        }
        #rightSidebar::-webkit-scrollbar{width:4px;}
        #rightSidebar::-webkit-scrollbar-thumb{background:#e5e7eb;border-radius:4px;}

        /* ── Accordion ──────────────────────────────────────── */
        .sc { background:#fff; border-radius:12px; border:1px solid #f1f5f9; box-shadow:0 1px 3px rgba(0,0,0,.05); margin-bottom:6px; }
        .acc-header { display:flex; align-items:center; justify-content:space-between; padding:10px 12px; cursor:pointer; user-select:none; transition:background .12s; }
        .acc-header:hover { background:#f9fafb; }
        .acc-body { overflow:hidden; max-height:0; opacity:0; transition:max-height .25s ease,opacity .18s; }
        .acc-body.open { opacity:1; }
        .acc-chevron { transition:transform .2s; }
        .acc-chevron.open { transform:rotate(180deg); }

        /* ── Badges ─────────────────────────────────────────── */
        .badge { display:inline-flex; align-items:center; padding:2px 10px; border-radius:999px; font-size:.72rem; font-weight:600; }
        .badge-open        { background:#fef9c3; color:#854d0e; }
        .badge-in_progress { background:#dbeafe; color:#1e40af; }
        .badge-done        { background:#dcfce7; color:#166534; }
        .badge-cancelled   { background:#fee2e2; color:#991b1b; }

        /* ── KV rows ─────────────────────────────────────────── */
        .kv-row { display:flex; align-items:flex-start; gap:8px; padding:6px 0; border-bottom:1px solid #f3f4f6; }
        .kv-key { min-width:90px; font-size:.7rem; font-weight:600; color:#9ca3af; text-transform:uppercase; letter-spacing:.04em; flex-shrink:0; }
        .kv-val { font-size:.8rem; color:#1f2937; }

        /* ── Notes in sidebar ───────────────────────────────── */
        .note-chip {
            display:flex; align-items:flex-start; gap:8px;
            padding:8px 10px; border-radius:10px;
            background:#f8fafc; border:1px solid #f1f5f9;
            cursor:pointer; transition:all .15s; margin-bottom:5px;
        }
        .note-chip:hover { border-color:#c7d2fe; background:#eef2ff; }
        .note-chip-text { font-size:.76rem; color:#374151; line-height:1.4; flex:1; overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; }

        /* ── Confirmed task cards ───────────────────────────── */
        .conf-task-card { border:1.5px solid #e5e7eb; border-radius:10px; padding:12px 14px; background:#fff; margin-bottom:8px; transition:all .15s; }
        .conf-task-card:hover { border-color:#c7d2fe; box-shadow:0 2px 8px rgba(99,102,241,.08); }

        /* ── Modals ─────────────────────────────────────────── */
        .modal-bg { background:rgba(0,0,0,.45); backdrop-filter:blur(3px); }

        #toast { position:fixed; bottom:24px; right:24px; z-index:9999; }
    </style>
</head>
<body class="bg-gray-50 font-sans">

<?php include '../elements/header.php'; ?>
<?php include '../elements/aside.php'; ?>
<?php include '../elements/preview-model.php'; ?>

<main id="mainContent" class="transition-all duration-300 pl-64" style="padding-top:64px;">

    <!-- Loading -->
    <div id="loadingState" class="text-center py-24 text-gray-400">
        <i class="fas fa-spinner fa-spin text-3xl mb-3 block"></i>Loading…
    </div>

    <!-- Page body -->
    <div id="pageContent" class="hidden">

        <!-- TOP BAR -->
        <div class="flex items-center gap-2 px-5 py-2.5 bg-white border-b border-gray-100 flex-shrink-0 flex-wrap">
            <a href="index-works.php" class="text-gray-400 hover:text-indigo-600 text-xs transition"><i class="fas fa-briefcase mr-1"></i>Works</a>
            <i class="fas fa-chevron-right text-gray-300 text-[10px]"></i>
            <span class="font-mono text-xs text-gray-600" id="breadWorkId">—</span>
            <span id="workStatusBadge" class="ml-1"></span>
            <div class="flex-1"></div>
            <button onclick="openStatusModal()" class="px-3 py-1.5 bg-white border border-gray-200 hover:border-indigo-300 text-gray-600 rounded-lg text-xs font-medium transition">
                <i class="fas fa-tag mr-1.5"></i>Status
            </button>
            <a href="index-works.php" class="px-3 py-1.5 bg-white border border-gray-200 text-gray-600 rounded-lg text-xs font-medium transition hover:border-gray-300">
                <i class="fas fa-arrow-left mr-1.5"></i>Back
            </a>
        </div>

        <!-- BODY: main + right sidebar -->
        <div id="bodyWrap">

            <!-- ── MAIN AREA ───────────────────────────────── -->
            <div id="mainArea">

                <!-- Service Module Tabs -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-4">
                    <div class="flex border-b border-gray-100 overflow-x-auto bg-gray-50 px-2 pt-2" id="svcModuleTabBar"></div>
                    <div id="at-tab-mount"></div>
                    <div id="svc-module-empty" class="hidden p-10 text-center text-gray-400 text-sm">
                        <i class="fas fa-layer-group text-2xl mb-2 block opacity-30"></i>
                        No services yet.
                    </div>
                </div>

                <!-- Confirmed Tasks -->
                <div class="bg-white rounded-xl shadow-sm p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-semibold text-gray-800 text-sm"><i class="fas fa-check-circle mr-2 text-emerald-500"></i>Confirmed Tasks</h3>
                        <span class="text-xs text-gray-400" id="confirmedTasksCount">0 tasks</span>
                    </div>
                    <div id="confirmedTasksList">
                        <p class="text-xs text-gray-400 italic">Tasks auto-created when a confirmation is marked Confirmed.</p>
                    </div>
                </div>

            </div>

            <!-- ── RIGHT SIDEBAR ──────────────────────────── -->
            <div id="rightSidebar">

                <!-- 1. Work Overview -->
                <div class="sc">
                    <div class="acc-header rounded-xl" onclick="toggleAcc('acc-ov',this)">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 bg-indigo-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-info-circle text-indigo-500 text-xs"></i>
                            </div>
                            <span class="text-sm font-semibold text-gray-700">Work Overview</span>
                        </div>
                        <i class="fas fa-chevron-down text-gray-400 text-xs acc-chevron open"></i>
                    </div>
                    <div id="acc-ov" class="acc-body open" style="max-height:400px;">
                        <div class="px-3 pb-3" id="overviewKV"></div>
                    </div>
                </div>

                <!-- 2. Travelers (always open, table modal) -->
                <div class="sc p-3">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 bg-teal-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-users text-teal-500 text-xs"></i>
                            </div>
                            <span class="text-sm font-semibold text-gray-700">Travelers</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <button onclick="openTravelerTableModal()" id="btnTravelerTable"
                                class="hidden px-2 py-1 bg-teal-50 hover:bg-teal-100 text-teal-500 rounded text-xs" title="Expand Table">
                                <i class="fas fa-table"></i>
                            </button>
                            <button onclick="openAddTravelerModal()" class="text-xs text-teal-500 hover:text-teal-700 font-semibold px-2 py-1">
                                <i class="fas fa-plus mr-1"></i>Add
                            </button>
                        </div>
                    </div>
                    <div class="relative mb-2">
                        <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-300 text-xs pointer-events-none"></i>
                        <input type="text" id="travelerSearchInput" placeholder="Search & link traveler…"
                            class="w-full pl-7 pr-3 py-2 text-xs border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-200"
                            autocomplete="off"
                            oninput="travelerSearchFilter(this.value)"
                            onfocus="travelerSearchFilter(this.value)">
                        <ul id="travelerSearchDrop" class="absolute w-full bg-white border border-gray-200 rounded-lg mt-1 max-h-44 overflow-auto shadow-xl hidden z-50 text-xs"></ul>
                    </div>
                    <div id="linkedTravelersList"></div>
                </div>

                <!-- 3. Notes (scrollable, click → modal) -->
                <div class="sc overflow-hidden">
                    <div class="acc-header rounded-xl" onclick="toggleAcc('acc-notes',this)">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-comments text-blue-500 text-xs"></i>
                            </div>
                            <span class="text-sm font-semibold text-gray-700">Notes</span>
                            <span class="text-[10px] bg-blue-100 text-blue-600 font-bold px-1.5 py-0.5 rounded-full hidden" id="notesBadge">0</span>
                        </div>
                        <i class="fas fa-chevron-down text-gray-400 text-xs acc-chevron"></i>
                    </div>
                    <div id="acc-notes" class="acc-body">
                        <div class="px-3 pb-3 space-y-1">
                            <div id="notesSideList" class="max-h-48 overflow-y-auto space-y-1 mb-2">
                                <p class="text-xs text-gray-400 py-2 text-center">No notes yet.</p>
                            </div>
                            <button onclick="openMindBoardModal()"
                                class="w-full py-1.5 text-indigo-500 hover:text-indigo-700 text-xs font-semibold text-center border border-indigo-100 rounded-lg hover:bg-indigo-50 transition">
                                Open Mind Board <i class="fas fa-arrow-right ml-1"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 4. Special Instructions (shown immediately, modal style) -->
                <div class="sc overflow-hidden">
                    <div class="acc-header rounded-xl" onclick="toggleAcc('acc-spins',this)">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-triangle-exclamation text-amber-500 text-xs"></i>
                            </div>
                            <span class="text-sm font-semibold text-gray-700">Special Instructions</span>
                            <span class="text-[10px] bg-amber-100 text-amber-600 font-bold px-1.5 py-0.5 rounded-full hidden" id="spInsBadge">0</span>
                        </div>
                        <i class="fas fa-chevron-down text-gray-400 text-xs acc-chevron open"></i>
                    </div>
                    <div id="acc-spins" class="acc-body open" style="max-height:400px;">
                        <div class="px-3 pb-3" id="spInsPanel">
                            <p class="text-xs text-gray-400 italic py-1">No special instructions.</p>
                        </div>
                    </div>
                </div>

                <!-- 5. Mind Board (quick preview + open button) -->
                <div class="sc overflow-hidden">
                    <div class="acc-header rounded-xl" onclick="toggleAcc('acc-mb',this)">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 bg-violet-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-brain text-violet-500 text-xs"></i>
                            </div>
                            <span class="text-sm font-semibold text-gray-700">Mind Board</span>
                        </div>
                        <i class="fas fa-chevron-down text-gray-400 text-xs acc-chevron"></i>
                    </div>
                    <div id="acc-mb" class="acc-body">
                        <div class="px-3 pb-3 space-y-2">
                            <div class="flex items-center gap-3 text-xs text-gray-400 py-1">
                                <div class="flex items-center gap-1"><i class="fas fa-comment text-blue-300 text-xs"></i><span id="mbMsgCount">0</span> notes</div>
                                <div class="flex items-center gap-1"><i class="fas fa-paperclip text-green-300 text-xs"></i><span id="mbFileCount">0</span> files</div>
                            </div>
                            <button onclick="openMindBoardModal()"
                                class="w-full py-1.5 text-indigo-500 hover:text-indigo-700 text-xs font-semibold text-center border border-indigo-100 rounded-lg hover:bg-indigo-50 transition">
                                Open Mind Board <i class="fas fa-arrow-right ml-1"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 6. Source Lead -->
                <div class="sc overflow-hidden">
                    <div class="acc-header rounded-xl" onclick="toggleAcc('acc-lead',this)">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 bg-gray-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-link text-gray-400 text-xs"></i>
                            </div>
                            <span class="text-sm font-semibold text-gray-700">Source Lead</span>
                        </div>
                        <i class="fas fa-chevron-down text-gray-400 text-xs acc-chevron"></i>
                    </div>
                    <div id="acc-lead" class="acc-body">
                        <div class="px-3 pb-3" id="sourceLeadContent">
                            <span class="text-xs text-gray-400">—</span>
                        </div>
                    </div>
                </div>

            </div><!-- /rightSidebar -->
        </div>
    </div>
</main>

<!-- ══ MODALS ══════════════════════════════════════════════════ -->

<!-- Status Modal -->
<div id="statusModal" class="fixed inset-0 z-50 hidden modal-bg flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
        <div class="flex items-center justify-between p-5 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-800"><i class="fas fa-tag mr-2 text-indigo-500"></i>Change Status</h3>
            <button onclick="closeModal('statusModal')" class="text-gray-400 hover:text-gray-700 text-xl"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-5 grid grid-cols-2 gap-3">
            <button onclick="changeStatus('open')"        class="py-2 rounded-lg bg-yellow-50 text-yellow-700 border border-yellow-200 text-sm font-medium hover:bg-yellow-100 transition">🟡 Open</button>
            <button onclick="changeStatus('in_progress')" class="py-2 rounded-lg bg-blue-50 text-blue-700 border border-blue-200 text-sm font-medium hover:bg-blue-100 transition">🔵 In Progress</button>
            <button onclick="changeStatus('done')"        class="py-2 rounded-lg bg-green-50 text-green-700 border border-green-200 text-sm font-medium hover:bg-green-100 transition">✅ Done</button>
            <button onclick="changeStatus('cancelled')"   class="py-2 rounded-lg bg-red-50 text-red-700 border border-red-200 text-sm font-medium hover:bg-red-100 transition">❌ Cancelled</button>
        </div>
    </div>
</div>

<!-- Add Service Modal -->
<div id="addServiceModal" class="fixed inset-0 z-50 hidden modal-bg flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between p-5 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800"><i class="fas fa-plus-circle mr-2 text-indigo-500"></i>Add Service</h3>
            <button onclick="closeModal('addServiceModal')" class="text-gray-400 hover:text-gray-700 text-xl"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-5 space-y-4">
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Service Type</label>
                <select id="newServiceSlug" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                    onchange="document.getElementById('newServiceName').value=this.options[this.selectedIndex].text.replace(/^.. /,'')">
                    <option value="">Select…</option>
                    <option value="air_ticket">✈ Air Ticket</option>
                    <option value="visa">🛂 Visa</option>
                    <option value="hotel">🏨 Hotel</option>
                    <option value="tour_package">🧳 Tour Package</option>
                    <option value="umrah">🕌 Umrah</option>
                    <option value="transport">🚐 Transport</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Display Name</label>
                <input type="text" id="newServiceName" placeholder="e.g. Air Ticket — DAC to DXB"
                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Department</label>
                <select id="newServiceDept" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <option value="">No department</option>
                </select>
            </div>
        </div>
        <div class="flex gap-3 p-5 border-t border-gray-100">
            <button onclick="closeModal('addServiceModal')" class="flex-1 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition">Cancel</button>
            <button onclick="confirmAddService()" class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition">Add Service</button>
        </div>
    </div>
</div>

<!-- Add Traveler Modal -->
<div id="addTravelerModal" class="fixed inset-0 z-50 hidden modal-bg flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-[85vh] flex flex-col">
        <div class="flex items-center justify-between p-5 border-b border-gray-100 flex-shrink-0">
            <h3 class="font-semibold text-gray-800"><i class="fas fa-user-plus mr-2 text-indigo-500"></i>Add Traveler</h3>
            <button onclick="closeModal('addTravelerModal')" class="text-gray-400 hover:text-gray-700 text-xl"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-4 flex-shrink-0">
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" id="modalTravelerSearch" placeholder="Search by name, passport, phone…"
                    autocomplete="off"
                    class="w-full pl-9 pr-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none"
                    oninput="filterModalTravelers(this.value)">
            </div>
        </div>
        <div id="modalTravelerResults" class="overflow-y-auto flex-1 px-4 pb-3 space-y-2">
            <div class="text-center py-8 text-gray-400 text-sm"><i class="fas fa-spinner fa-spin"></i></div>
        </div>
        <div class="p-4 border-t border-gray-100 flex-shrink-0">
            <a href="create-traveler.php" target="_blank"
                class="flex items-center justify-center gap-2 w-full py-2.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 rounded-lg text-sm font-semibold transition">
                <i class="fas fa-plus"></i>Create New Traveler
            </a>
        </div>
    </div>
</div>

<!-- Traveler Table Modal -->
<div id="travelerTableModal" class="fixed inset-0 z-[70] hidden modal-bg flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl">
        <div class="flex items-center justify-between p-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800 text-sm">
                <i class="fas fa-table mr-2 text-teal-500"></i>Travelers
                <span class="text-xs text-gray-400 font-normal ml-2">Click any cell to copy</span>
            </h3>
            <button onclick="closeModal('travelerTableModal')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-4 overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-xs text-gray-500 uppercase">
                        <th class="px-3 py-2 text-left">Name</th>
                        <th class="px-3 py-2 text-left">Given Name</th>
                        <th class="px-3 py-2 text-left">Surname</th>
                        <th class="px-3 py-2 text-left">Passport No</th>
                        <th class="px-3 py-2 text-left">Expiry</th>
                        <th class="px-3 py-2 text-left">DOB</th>
                        <th class="px-3 py-2 text-left">Action</th>
                    </tr>
                </thead>
                <tbody id="travelerTableBody" class="divide-y divide-gray-100"></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Mind Board Modal -->
<div id="mindBoardModal" class="fixed inset-0 z-50 hidden modal-bg flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg flex flex-col" style="height:85vh;">
        <div class="flex items-center justify-between p-4 border-b border-gray-100 flex-shrink-0">
            <h3 class="font-semibold text-gray-800 text-sm"><i class="fas fa-comments mr-2 text-indigo-400"></i>Mind Board</h3>
            <button onclick="closeModal('mindBoardModal')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <div id="mbChatArea" class="flex-1 overflow-y-auto px-4 py-3 flex flex-col gap-2"
            style="--scrollbar-width:4px;">
            <div class="text-center py-6 text-gray-300 text-xs"><i class="fas fa-spinner fa-spin"></i></div>
        </div>
        <div class="flex-shrink-0 border-t border-gray-100 bg-white p-3">
            <div id="mbFilePreview" class="hidden mb-2 bg-indigo-50 rounded-lg px-3 py-1.5 text-xs text-indigo-600 flex items-center gap-2">
                <i class="fas fa-paperclip flex-shrink-0"></i>
                <span id="mbFilePreviewName" class="flex-1 truncate"></span>
                <button onclick="mbClearFile()" class="text-red-400 hover:text-red-600 flex-shrink-0"><i class="fas fa-times"></i></button>
            </div>
            <div class="flex items-end gap-2">
                <label class="w-9 h-9 bg-gray-100 hover:bg-gray-200 rounded-full flex items-center justify-center cursor-pointer flex-shrink-0 transition" title="Attach file">
                    <i class="fas fa-paperclip text-gray-500 text-sm"></i>
                    <input type="file" id="mbFileInput" class="hidden" onchange="mbFileSelected(this)">
                </label>
                <textarea id="mbTextInput" rows="1" placeholder="Write a note… (Enter to send)"
                    onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();mbSend();}"
                    oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,100)+'px'"
                    style="flex:1;resize:none;border:1.5px solid #e5e7eb;border-radius:20px;padding:8px 14px;font-size:.83rem;outline:none;max-height:100px;overflow-y:auto;transition:border .15s;"></textarea>
                <button onclick="mbSend()"
                    class="w-9 h-9 bg-indigo-600 hover:bg-indigo-700 rounded-full flex items-center justify-center flex-shrink-0 transition">
                    <i class="fas fa-paper-plane text-white text-sm"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Note Detail Modal (click note → show full) -->
<div id="noteDetailModal" class="fixed inset-0 z-[60] hidden modal-bg flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between p-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800 text-sm"><i class="fas fa-comment mr-2 text-blue-400"></i>Note</h3>
            <button onclick="closeModal('noteDetailModal')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-4">
            <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-wrap" id="noteDetailText"></p>
            <p class="text-xs text-gray-400 mt-3" id="noteDetailTime"></p>
        </div>
    </div>
</div>

<!-- Special Instructions Modal -->
<div id="specialInsModal" class="fixed inset-0 z-[60] hidden flex items-center justify-center p-4" style="background:rgba(0,0,0,.5);backdrop-filter:blur(3px);">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between p-4 border-b border-amber-100 bg-amber-50 rounded-t-2xl">
            <h3 class="font-bold text-amber-800 text-sm"><i class="fas fa-triangle-exclamation mr-2 text-amber-500"></i>Special Instructions</h3>
            <button onclick="closeModal('specialInsModal')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-4" id="specialInsModalList"></div>
        <div class="px-4 pb-4">
            <button onclick="closeModal('specialInsModal')"
                class="w-full py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-sm font-semibold transition">
                Understood <i class="fas fa-check ml-1"></i>
            </button>
        </div>
    </div>
</div>

<!-- Copy Toast -->
<div id="copyToast" class="fixed bottom-16 right-6 z-[9999] hidden">
    <div class="bg-gray-800 text-white text-xs px-3 py-1.5 rounded-lg shadow-lg flex items-center gap-2">
        <i class="fas fa-check text-green-400"></i> Copied!
    </div>
</div>

<!-- Toast -->
<div id="toast" class="hidden">
    <div id="toastInner" class="flex items-center gap-3 px-5 py-3 rounded-xl shadow-lg text-white text-sm font-medium">
        <i id="toastIcon" class="fas fa-check-circle text-lg"></i>
        <span id="toastMsg"></span>
    </div>
</div>

<?php include '../elements/floating-menus.php'; ?>
<script src="../assets/js/script.js?t=<?php echo time(); ?>"></script>
<script src="../pages/task-tabs/ww-air-ticket.js?t=<?php echo time(); ?>"></script>
<script>
const WORK_SYS_ID  = "<?php echo htmlspecialchars($workSysId); ?>";
const DEEP_LINK_SW = "<?php echo htmlspecialchars($deepLinkSw); ?>";
const API = {
    getWork:      "<?php echo $getWorkApi; ?>",
    status:       "<?php echo $updateStatusApi; ?>",
    addService:   "<?php echo $addServiceApi; ?>",
    depts:        "<?php echo $deptApi; ?>",
    workTravelers:"<?php echo $workTravelersApi; ?>",
    allTravelers: "<?php echo $allTravelersApi; ?>",
    airTickets:   "<?php echo $airTicketsApi; ?>",
    notes:        "<?php echo $notesApi; ?>",
};

const SVC_INFO = {
    air_ticket:   { icon:'fa-plane',           label:'Air Ticket',   color:'text-sky-500' },
    visa:         { icon:'fa-passport',         label:'Visa',         color:'text-violet-500' },
    hotel:        { icon:'fa-hotel',            label:'Hotel',        color:'text-pink-500' },
    tour_package: { icon:'fa-suitcase-rolling', label:'Tour Package', color:'text-green-500' },
    umrah:        { icon:'fa-kaaba',            label:'Umrah',        color:'text-amber-500' },
    transport:    { icon:'fa-van-shuttle',      label:'Transport',    color:'text-teal-500' },
};

let workData        = null;
let serviceWorks    = [];
let confirmedTasks  = [];
let airTicketData   = null;
let _mbNotes        = [];
let _mbFile         = null;
let _linkedTravelers = [];
let _allTravelers    = [];
let allDepts         = [];

// ════════════════════════════════════════════════════════════
// LOAD
// ════════════════════════════════════════════════════════════
async function loadWork() {
    if (!WORK_SYS_ID) { document.getElementById('loadingState').innerHTML='<p class="text-red-400 text-center">No work ID.</p>'; return; }
    try {
        const res  = await fetch(API.getWork + '?id=' + encodeURIComponent(WORK_SYS_ID));
        const json = await res.json();
        if (json.status !== 'success') throw new Error(json.message);

        workData       = json.work;
        serviceWorks   = json.service_works  ?? [];
        confirmedTasks = json.confirmed_tasks ?? [];
        airTicketData  = json.air_ticket_data ?? null;

        renderPage();
        mbLoadNotes();
        loadLinkedTravelers();
    } catch(e) {
        document.getElementById('loadingState').innerHTML = `<p class="text-red-400 text-center text-sm"><i class="fas fa-exclamation-circle mr-2"></i>${esc(e.message)}</p>`;
    }
}

function renderPage() {
    document.getElementById('loadingState').classList.add('hidden');
    document.getElementById('pageContent').classList.remove('hidden');

    const ci       = sp(workData.client_info) ?? {};
    const svcTypes = sp(workData.service_type) ?? [];
    const services = Array.isArray(svcTypes) ? svcTypes : [svcTypes];

    document.getElementById('breadWorkId').textContent   = WORK_SYS_ID;
    document.getElementById('workStatusBadge').innerHTML = badgeHtml(workData.work_status);

    renderOverview(ci, workData, services);
    renderSpecialIns(workData);
    renderServiceModuleTabs(serviceWorks);
    renderConfirmedTasks();

    document.getElementById('sourceLeadContent').innerHTML = workData.lead_sys_id
        ? `<a href="create-leads.php?id=${esc(workData.lead_sys_id)}" class="font-mono text-xs text-indigo-500 hover:underline">${esc(workData.lead_sys_id)}</a>`
        : '<span class="text-xs text-gray-400">—</span>';
}

// ════════════════════════════════════════════════════════════
// OVERVIEW
// ════════════════════════════════════════════════════════════
function renderOverview(ci, w, services) {
    const segLabels = { one_way:'One Way', round_trip:'Round Trip', multi_city:'Multi City' };
    const rows = [
        ['Client',   ci.name   ?? '—'],
        ['Phone',    ci.phone  ?? '—'],
        ['Service',  services.map(s => SVC_INFO[s]?.label ?? s).join(', ')],
        ['Seg. Type',segLabels[w.segment_type ?? ''] ?? (w.segment_type || '—')],
        ['Status',   badgeHtml(w.work_status)],
        ['Created',  fmtDate(w.meta_data?.created_by_date?.date ?? '')],
    ];
    document.getElementById('overviewKV').innerHTML = rows.map(([k,v]) =>
        `<div class="kv-row"><span class="kv-key">${k}</span><span class="kv-val">${v}</span></div>`
    ).join('');
    accRefresh('acc-ov');
}

// ════════════════════════════════════════════════════════════
// SPECIAL INSTRUCTIONS
// ════════════════════════════════════════════════════════════
function renderSpecialIns(w) {
    const segData = sp(w.segment_data) ?? {};
    const items   = [];

    // Collect from all services in segment_data
    const svcTypes = sp(w.service_type) ?? [];
    const services = Array.isArray(svcTypes) ? svcTypes : [svcTypes];

    services.forEach(slug => {
        const svcData = segData[slug] ?? {};
        const segs    = svcData.segments ?? [svcData];
        segs.forEach(seg => {
            (seg.special_instruction ?? []).forEach(ins => {
                if (ins) items.push({ service: SVC_INFO[slug]?.label ?? slug, text: ins });
            });
        });
        // top-level special_instruction
        (svcData.special_instruction ?? []).forEach(ins => {
            if (ins) items.push({ service: SVC_INFO[slug]?.label ?? slug, text: ins });
        });
    });

    // Also check work-level special_instruction
    const workSpIns = sp(w.special_instruction) ?? [];
    (Array.isArray(workSpIns) ? workSpIns : [workSpIns]).forEach(ins => {
        if (ins) items.push({ service: 'Work', text: ins });
    });

    const badge = document.getElementById('spInsBadge');
    if (items.length) {
        badge.textContent = items.length;
        badge.classList.remove('hidden');
    }

    const panel = document.getElementById('spInsPanel');
    if (!items.length) {
        panel.innerHTML = '<p class="text-xs text-gray-400 italic py-1">No special instructions.</p>';
        accRefresh('acc-spins');
        return;
    }

    // Show immediately in sidebar (no accordion collapse needed for instructions)
    panel.innerHTML = items.map((item, i) =>
        `<div class="flex items-start gap-2 bg-amber-50 border border-amber-100 rounded-lg px-2.5 py-2 mb-1.5 text-xs text-amber-800">
            <span class="flex-shrink-0 w-4 h-4 bg-amber-400 text-white rounded-full flex items-center justify-center text-[9px] font-bold mt-0.5">${i+1}</span>
            <div class="flex-1 min-w-0">
                <span class="font-semibold text-amber-600 text-[10px] uppercase tracking-wide">${esc(item.service)}</span>
                <p class="mt-0.5 leading-snug">${esc(item.text)}</p>
            </div>
        </div>`
    ).join('');

    // Also populate modal list
    document.getElementById('specialInsModalList').innerHTML =
        `<div class="space-y-2">` + items.map((item, i) =>
            `<div class="flex items-start gap-3 p-3 bg-amber-50 border border-amber-200 rounded-xl">
                <span class="flex-shrink-0 w-6 h-6 bg-amber-400 text-white rounded-full flex items-center justify-center text-xs font-bold">${i+1}</span>
                <div>
                    <p class="text-[10px] font-bold text-amber-500 uppercase mb-0.5">${esc(item.service)}</p>
                    <p class="text-sm text-amber-900 leading-relaxed">${esc(item.text)}</p>
                </div>
            </div>`
        ).join('') + `</div>`;

    // Show modal automatically if there are instructions
    if (items.length) {
        setTimeout(() => document.getElementById('specialInsModal').classList.remove('hidden'), 600);
    }

    accRefresh('acc-spins');
}

// ════════════════════════════════════════════════════════════
// SERVICE MODULE TABS
// ════════════════════════════════════════════════════════════
function renderServiceModuleTabs(sws) {
    const bar   = document.getElementById('svcModuleTabBar');
    const empty = document.getElementById('svc-module-empty');

    if (!sws.length) { bar.innerHTML=''; document.getElementById('at-tab-mount').innerHTML=''; empty.classList.remove('hidden'); return; }
    empty.classList.add('hidden');

    bar.innerHTML = sws.map((sw, i) => {
        const info = SVC_INFO[sw.service_slug] ?? { icon:'fa-circle', label:sw.service_name, color:'text-gray-400' };
        return `<button class="svc-mod-tab flex items-center gap-2 px-4 py-2.5 text-sm font-semibold border-b-2 transition whitespace-nowrap
                    ${i===0?'border-indigo-500 text-indigo-600 bg-white':'border-transparent text-gray-500 hover:text-gray-700'}"
                    data-slug="${esc(sw.service_slug)}" data-sw="${esc(sw.sys_id)}"
                    onclick="switchServiceModule('${esc(sw.service_slug)}','${esc(sw.sys_id)}',this)">
                <i class="fas ${info.icon} ${info.color} text-xs"></i>${esc(sw.service_name||info.label)}
            </button>`;
    }).join('') +
    `<button onclick="openAddServiceModal()"
        class="ml-auto flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-indigo-500 hover:text-indigo-700 hover:bg-indigo-50 transition border-b-2 border-transparent whitespace-nowrap">
        <i class="fas fa-plus text-[10px]"></i>Add Service
    </button>`;

    const target = DEEP_LINK_SW ? sws.find(s=>s.sys_id===DEEP_LINK_SW)??sws[0] : sws[0];
    if (DEEP_LINK_SW) {
        bar.querySelectorAll('.svc-mod-tab').forEach(btn => {
            const on = btn.dataset.sw === target.sys_id;
            btn.className = btn.className.replace(/border-indigo-500 text-indigo-600 bg-white|border-transparent text-gray-500 hover:text-gray-700/g,'').trim()
                + (on ? ' border-indigo-500 text-indigo-600 bg-white' : ' border-transparent text-gray-500 hover:text-gray-700');
        });
    }
    loadServiceModule(target.service_slug, target.sys_id);
}

function switchServiceModule(slug, swSysId, btn) {
    document.querySelectorAll('.svc-mod-tab').forEach(b => {
        b.className = b.className.replace(/border-indigo-500 text-indigo-600 bg-white/g,'').replace(/border-transparent text-gray-500 hover:text-gray-700/g,'').trim() + ' border-transparent text-gray-500 hover:text-gray-700';
    });
    btn.className = btn.className.replace(/border-transparent text-gray-500 hover:text-gray-700/g,'').trim() + ' border-indigo-500 text-indigo-600 bg-white';
    loadServiceModule(slug, swSysId);
}

function loadServiceModule(slug, swSysId) {
    const mount = document.getElementById('at-tab-mount');
    mount.innerHTML = '';
    if (slug === 'air_ticket') {
        const ci = sp(workData.client_info) ?? {};
        initWorkAirTicketTab({
            workSysId:  WORK_SYS_ID,
            leadSysId:  workData.lead_sys_id ?? '',
            clientName: ci.name ?? '',
            atData:     airTicketData,
            api: { airTickets:API.airTickets, notes:API.notes, workTravelers:API.workTravelers },
        });
    } else {
        const info = SVC_INFO[slug] ?? { icon:'fa-circle', label:slug };
        mount.innerHTML = `<div class="p-10 text-center text-gray-400">
            <i class="fas ${info.icon} text-3xl mb-3 block opacity-20"></i>
            <p class="text-sm font-medium">${esc(info.label)} module coming soon</p>
        </div>`;
    }
}

// ════════════════════════════════════════════════════════════
// CONFIRMED TASKS
// ════════════════════════════════════════════════════════════
function renderConfirmedTasks() {
    const list  = document.getElementById('confirmedTasksList');
    const count = document.getElementById('confirmedTasksCount');
    count.textContent = confirmedTasks.length + ' task(s)';
    if (!confirmedTasks.length) {
        list.innerHTML = '<p class="text-xs text-gray-400 italic">Tasks auto-created when a confirmation is marked Confirmed.</p>';
        return;
    }
    list.innerHTML = confirmedTasks.map(t => `
        <div class="conf-task-card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-semibold text-gray-800">${esc(t.workname??'Task')}</p>
                    <p class="text-xs font-mono text-indigo-400 mt-0.5">${esc(t.sys_id)}</p>
                </div>
                <span class="text-[10px] font-semibold px-2 py-1 rounded-full ${t.status==='done'?'bg-green-100 text-green-700':'bg-yellow-100 text-yellow-700'}">
                    ${t.status==='done'?'Done':'Open'}
                </span>
            </div>
            <div class="flex items-center justify-between mt-2 pt-2 border-t border-gray-100">
                <span class="text-xs text-gray-400">Financial only</span>
                <a href="show-tasks.php?id=${esc(t.sys_id)}" class="text-xs text-indigo-500 hover:text-indigo-700 font-semibold">
                    Open <i class="fas fa-arrow-right ml-1 text-[9px]"></i>
                </a>
            </div>
        </div>`).join('');
}

// ════════════════════════════════════════════════════════════
// MIND BOARD
// ════════════════════════════════════════════════════════════
async function mbLoadNotes() {
    try {
        const r = await fetch(`${API.notes}?action=list&work_sys_id=${encodeURIComponent(WORK_SYS_ID)}`);
        const j = await r.json();
        _mbNotes = j.status==='success' ? (j.data??[]) : [];
    } catch { _mbNotes = []; }
    renderMbSidebar();
    renderMbChat();
}

function renderMbSidebar() {
    const msgCount  = _mbNotes.filter(n=>n.note_type==='text').length;
    const fileCount = _mbNotes.filter(n=>n.note_type!=='text').length;
    document.getElementById('mbMsgCount').textContent  = msgCount;
    document.getElementById('mbFileCount').textContent = fileCount;

    // Notes badge
    const badge = document.getElementById('notesBadge');
    if (_mbNotes.length) { badge.textContent=_mbNotes.length; badge.classList.remove('hidden'); }

    // Notes side list — show text notes as clickable chips
    const sideList = document.getElementById('notesSideList');
    const textNotes = _mbNotes.filter(n=>n.note_type==='text').slice(0,8);
    if (!textNotes.length) {
        sideList.innerHTML = '<p class="text-xs text-gray-400 py-2 text-center">No notes yet.</p>';
    } else {
        sideList.innerHTML = textNotes.map(n => `
            <div class="note-chip" onclick="openNoteDetail('${esc(n.sys_id)}')">
                <i class="fas fa-comment text-blue-300 text-xs flex-shrink-0 mt-0.5"></i>
                <span class="note-chip-text">${esc(n.content ?? '')}</span>
            </div>`).join('');
    }
    accRefresh('acc-notes');
    accRefresh('acc-mb');
}

function openNoteDetail(sysId) {
    const note = _mbNotes.find(n=>n.sys_id===sysId);
    if (!note) return;
    document.getElementById('noteDetailText').textContent = note.content ?? '';
    const meta = note.meta_data?.created_by_date;
    document.getElementById('noteDetailTime').textContent = meta ? `${meta.user} · ${meta.date}` : '';
    document.getElementById('noteDetailModal').classList.remove('hidden');
}

function renderMbChat() {
    const area = document.getElementById('mbChatArea');
    if (!_mbNotes.length) {
        area.innerHTML = '<div class="text-center py-8 text-gray-300 text-xs">No notes yet. Write something below.</div>';
        return;
    }
    area.innerHTML = _mbNotes.map(n => _mbBubble(n)).join('');
    setTimeout(()=>{ area.scrollTop = area.scrollHeight; }, 100);
}

function _mbBubble(n) {
    const meta = n.meta_data?.created_by_date;
    const time = meta ? `${meta.user} · ${meta.date}` : '';
    const del  = `<button onclick="mbDel('${n.sys_id}')" class="absolute -top-1.5 -right-1.5 w-4 h-4 bg-red-500 text-white rounded-full text-[9px] hidden items-center justify-center hover:bg-red-600 mb-del"><i class="fas fa-times"></i></button>`;

    if (n.note_type === 'text') return `
        <div class="flex flex-col items-start relative group mb-bubble-wrap">
            <div class="relative bg-gray-100 rounded-2xl rounded-bl-sm px-3 py-2 text-sm text-gray-800 max-w-[85%] whitespace-pre-wrap">${esc(n.content??'')}${del}</div>
            <span class="text-[10px] text-gray-400 mt-1 ml-1">${esc(time)}</span>
        </div>`;

    if (n.note_type === 'image') return `
        <div class="flex flex-col items-start relative group mb-bubble-wrap">
            <div class="relative bg-white border border-gray-200 rounded-xl p-1 max-w-[85%]">
                <img src="${esc(n.serve_url??'')}" class="rounded-lg max-h-52 object-contain cursor-pointer" onclick="window._previewOpen&&window._previewOpen(this.src)">
                ${del}
            </div>
            <span class="text-[10px] text-gray-400 mt-1 ml-1">${esc(time)}</span>
        </div>`;

    if (n.note_type === 'audio') return `
        <div class="flex flex-col items-start relative group mb-bubble-wrap">
            <div class="relative bg-violet-50 border border-violet-100 rounded-xl px-3 py-2 max-w-[85%]">
                <audio controls src="${esc(n.serve_url??'')}" class="h-8 w-48"></audio>
                ${del}
            </div>
            <span class="text-[10px] text-gray-400 mt-1 ml-1">${esc(time)}</span>
        </div>`;

    // file / pdf_images / other
    return `
        <div class="flex flex-col items-start relative group mb-bubble-wrap">
            <div class="relative bg-green-50 border border-green-100 rounded-xl px-3 py-2 flex items-center gap-2 max-w-[85%]">
                <i class="fas fa-file text-green-500 text-sm flex-shrink-0"></i>
                <a href="${esc(n.serve_url??'')}?dl=1" class="text-xs text-green-700 font-medium truncate max-w-[140px]" download>${esc(n.file_name??'file')}</a>
                ${del}
            </div>
            <span class="text-[10px] text-gray-400 mt-1 ml-1">${esc(time)}</span>
        </div>`;
}

// Hover to show delete button
document.addEventListener('mouseover', e => {
    const wrap = e.target.closest('.mb-bubble-wrap');
    document.querySelectorAll('.mb-del').forEach(b => b.style.display='none');
    if (wrap) { const btn = wrap.querySelector('.mb-del'); if(btn) btn.style.display='flex'; }
});

function openMindBoardModal() {
    document.getElementById('mindBoardModal').classList.remove('hidden');
    renderMbChat();
}

function mbFileSelected(input) {
    _mbFile = input.files[0] ?? null;
    if (_mbFile) {
        document.getElementById('mbFilePreview').classList.remove('hidden');
        document.getElementById('mbFilePreviewName').textContent = _mbFile.name;
    }
}
function mbClearFile() { _mbFile=null; document.getElementById('mbFilePreview').classList.add('hidden'); document.getElementById('mbFileInput').value=''; }

async function mbSend() {
    const txt = document.getElementById('mbTextInput').value.trim();
    if (_mbFile) {
        const fd = new FormData();
        fd.append('action','upload'); fd.append('work_sys_id',WORK_SYS_ID); fd.append('file',_mbFile);
        try { const r=await fetch(API.notes,{method:'POST',body:fd}); const j=await r.json(); if(j.status==='success'){mbClearFile();await mbLoadNotes();}else showToast('error',j.message); } catch{showToast('error','Upload failed');}
        return;
    }
    if (!txt) return;
    document.getElementById('mbTextInput').value=''; document.getElementById('mbTextInput').style.height='auto';
    try {
        const r=await fetch(API.notes,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'store',work_sys_id:WORK_SYS_ID,content:txt})});
        const j=await r.json(); if(j.status==='success') await mbLoadNotes(); else showToast('error',j.message);
    } catch{showToast('error','Network error');}
}

async function mbDel(id) {
    if(!confirm('Delete this note?')) return;
    try {
        const r=await fetch(API.notes,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'delete',sys_id:id})});
        const j=await r.json(); if(j.status==='success') await mbLoadNotes(); else showToast('error',j.message);
    } catch{showToast('error','Network error');}
}

// ════════════════════════════════════════════════════════════
// TRAVELERS
// ════════════════════════════════════════════════════════════
async function loadLinkedTravelers() {
    try {
        const r=await fetch(`${API.workTravelers}?action=list&work_sys_id=${encodeURIComponent(WORK_SYS_ID)}`);
        const j=await r.json();
        _linkedTravelers = j.status==='success' ? (j.data??[]) : [];
    } catch { _linkedTravelers=[]; }
    renderLinkedTravelers();
}

function renderLinkedTravelers() {
    const list = document.getElementById('linkedTravelersList');
    const tableBtn = document.getElementById('btnTravelerTable');

    if (!_linkedTravelers.length) {
        list.innerHTML = '<p class="text-xs text-gray-400 italic">No travelers linked.</p>';
        tableBtn?.classList.add('hidden');
        return;
    }
    tableBtn?.classList.remove('hidden');

    list.innerHTML = _linkedTravelers.map(t => {
        const initial = (t.name||'?').charAt(0).toUpperCase();
        const pi = t.passport_info?.[0]?.bio_info ?? {};
        const sub = [t.passport_no||pi.passport_number, t.phone].filter(Boolean).join(' · ');
        return `<div class="flex items-center gap-2 group py-1">
            <div class="w-7 h-7 rounded-full bg-teal-100 text-teal-600 flex items-center justify-center font-bold text-xs flex-shrink-0">${initial}</div>
            <div class="flex-1 min-w-0">
                <p class="text-gray-700 font-medium text-xs truncate">${esc(t.name)}</p>
                ${sub?`<p class="text-gray-400 text-[10px] truncate">${esc(sub)}</p>`:''}
            </div>
            <button onclick="unlinkTraveler('${esc(t.sys_id)}')"
                class="opacity-0 group-hover:opacity-100 text-gray-300 hover:text-red-400 text-xs flex-shrink-0 transition">
                <i class="fas fa-times"></i>
            </button>
        </div>`;
    }).join('');
}

async function unlinkTraveler(sysId) {
    if(!confirm('Remove this traveler?')) return;
    try {
        const r=await fetch(API.workTravelers,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'unlink',work_sys_id:WORK_SYS_ID,traveler_sys_id:sysId})});
        const j=await r.json(); if(j.status==='success'){_linkedTravelers=_linkedTravelers.filter(t=>t.sys_id!==sysId);renderLinkedTravelers();}else showToast('error',j.message);
    } catch{showToast('error','Network error');}
}

// Inline search (sidebar)
let _travelerTimer;
function travelerSearchFilter(q) {
    clearTimeout(_travelerTimer);
    const drop = document.getElementById('travelerSearchDrop');
    if (!q || q.length < 2) { drop.classList.add('hidden'); return; }
    _travelerTimer = setTimeout(async () => {
        if (!_allTravelers.length) {
            const r=await fetch(API.allTravelers); const j=await r.json();
            _allTravelers = j.success ? (j.travelers??[]) : [];
        }
        const ql = q.toLowerCase();
        const results = _allTravelers.filter(t =>
            (t.name||'').toLowerCase().includes(ql) || (t.passport_no||'').toLowerCase().includes(ql)
        ).slice(0,8);
        const linkedIds = new Set(_linkedTravelers.map(t=>t.sys_id));
        drop.innerHTML = results.length
            ? results.map(t => {
                const isLinked = linkedIds.has(t.sys_id);
                return `<li class="flex items-center justify-between px-3 py-2 hover:bg-indigo-50 cursor-pointer">
                    <div class="min-w-0"><p class="font-medium text-gray-700 truncate">${esc(t.name)}</p><p class="text-gray-400 text-[10px] truncate">${esc(t.passport_no??'')}</p></div>
                    ${isLinked
                        ? '<span class="text-green-500 text-xs font-bold flex-shrink-0"><i class="fas fa-check mr-1"></i>Added</span>'
                        : `<button onclick="linkTravelerInline('${esc(t.sys_id)}')" class="text-indigo-500 text-xs font-semibold flex-shrink-0 hover:text-indigo-700">Add</button>`}
                </li>`;
              }).join('')
            : '<li class="px-3 py-3 text-xs text-gray-400 text-center">No results</li>';
        drop.classList.remove('hidden');
    }, 250);
}

async function linkTravelerInline(sysId) {
    try {
        const r=await fetch(API.workTravelers,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'link',work_sys_id:WORK_SYS_ID,traveler_sys_id:sysId})});
        const j=await r.json(); if(j.status==='success'){document.getElementById('travelerSearchInput').value='';document.getElementById('travelerSearchDrop').classList.add('hidden');await loadLinkedTravelers();}else showToast('error',j.message);
    } catch{showToast('error','Network error');}
}

document.addEventListener('click', e => {
    if (!e.target.closest('#travelerSearchInput') && !e.target.closest('#travelerSearchDrop'))
        document.getElementById('travelerSearchDrop').classList.add('hidden');
});

// Modal traveler search (Add Traveler Modal)
async function openAddTravelerModal() {
    document.getElementById('addTravelerModal').classList.remove('hidden');
    document.getElementById('modalTravelerSearch').value = '';
    if (!_allTravelers.length) {
        const r=await fetch(API.allTravelers); const j=await r.json();
        _allTravelers = j.success ? (j.travelers??[]) : [];
    }
    filterModalTravelers('');
}

function filterModalTravelers(q) {
    const ql = q.toLowerCase();
    const linkedIds = new Set(_linkedTravelers.map(t=>t.sys_id));
    let list = _allTravelers;
    if (q) list = list.filter(t=>(t.name||'').toLowerCase().includes(ql)||(t.passport_no||'').toLowerCase().includes(ql));
    list = list.slice(0,40);
    const wrap = document.getElementById('modalTravelerResults');
    wrap.innerHTML = list.map(t => {
        const isLinked = linkedIds.has(t.sys_id);
        const initial  = (t.name||'?').charAt(0).toUpperCase();
        const sub = [t.passport_no,t.phone].filter(Boolean).join(' · ');
        return `<div class="flex items-center gap-3 p-2.5 rounded-lg border border-gray-100 hover:border-indigo-200 transition">
            <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-sm flex-shrink-0">${initial}</div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-700 truncate">${esc(t.name)}</p>
                ${sub?`<p class="text-xs text-gray-400 truncate">${esc(sub)}</p>`:''}
            </div>
            ${isLinked
                ? '<span class="text-xs text-green-600 font-semibold flex-shrink-0"><i class="fas fa-check mr-1"></i>Added</span>'
                : `<button onclick="linkTravelerModal('${esc(t.sys_id)}')" class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 rounded-lg text-xs font-semibold transition flex-shrink-0">Add</button>`}
        </div>`;
    }).join('') || '<p class="text-center text-gray-400 text-sm py-6">No travelers found.</p>';
}

async function linkTravelerModal(sysId) {
    try {
        const r=await fetch(API.workTravelers,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'link',work_sys_id:WORK_SYS_ID,traveler_sys_id:sysId})});
        const j=await r.json(); if(j.status==='success'){await loadLinkedTravelers();filterModalTravelers(document.getElementById('modalTravelerSearch').value);}else showToast('error',j.message);
    } catch{showToast('error','Network error');}
}

// Traveler Table Modal
function openTravelerTableModal() {
    document.getElementById('travelerTableModal').classList.remove('hidden');
    const tbody = document.getElementById('travelerTableBody');
    tbody.innerHTML = _linkedTravelers.map(t => {
        const pi = t.passport_info?.[0]?.bio_info ?? {};
        const cells = [
            t.name, pi.given_names||'', pi.surname||'',
            t.passport_no||pi.passport_number||'',
            t.passport_expiry||pi.date_of_expiry||'',
            pi.date_of_birth||'',
        ];
        return `<tr>
            ${cells.map(c=>`<td class="px-3 py-2 cursor-pointer hover:bg-indigo-50 transition" onclick="copyCellText('${esc(c)}')">${esc(c||'—')}</td>`).join('')}
            <td class="px-3 py-2">
                <button onclick="unlinkTraveler('${esc(t.sys_id)}')" class="text-xs text-red-400 hover:text-red-600">Remove</button>
            </td>
        </tr>`;
    }).join('');
}

function copyCellText(text) {
    navigator.clipboard.writeText(text).catch(()=>{});
    const toast = document.getElementById('copyToast');
    toast.classList.remove('hidden');
    setTimeout(()=>toast.classList.add('hidden'),1500);
}

// ════════════════════════════════════════════════════════════
// STATUS
// ════════════════════════════════════════════════════════════
function openStatusModal() { document.getElementById('statusModal').classList.remove('hidden'); }
async function changeStatus(s) {
    closeModal('statusModal');
    try {
        const r=await fetch(API.status,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({sys_id:WORK_SYS_ID,status:s})});
        const j=await r.json(); if(j.status==='success'){showToast('success','Status updated');loadWork();}else showToast('error',j.message);
    } catch{showToast('error','Network error');}
}

// ════════════════════════════════════════════════════════════
// ADD SERVICE
// ════════════════════════════════════════════════════════════
async function loadDepts() {
    try {
        const r=await fetch(API.depts+'?action=all'); const j=await r.json();
        allDepts=(j.data??[]).filter(d=>d.is_active==1);
        const sel=document.getElementById('newServiceDept');
        if(sel) sel.innerHTML='<option value="">No department</option>'+allDepts.map(d=>`<option value="${esc(d.sys_id)}">${esc(d.name)}</option>`).join('');
    } catch{}
}
function openAddServiceModal() { document.getElementById('addServiceModal').classList.remove('hidden'); }
async function confirmAddService() {
    const slug=document.getElementById('newServiceSlug').value.trim();
    const name=document.getElementById('newServiceName').value.trim();
    const dept=document.getElementById('newServiceDept').value;
    if(!slug||!name){showToast('error','Service type and name required');return;}
    try {
        const r=await fetch(API.addService,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({work_sys_id:WORK_SYS_ID,service_slug:slug,service_name:name,department_sys_id:dept})});
        const j=await r.json(); if(j.status==='success'){showToast('success','Service added!');closeModal('addServiceModal');loadWork();}else showToast('error',j.message);
    } catch{showToast('error','Network error');}
}

// ════════════════════════════════════════════════════════════
// ACCORDION
// ════════════════════════════════════════════════════════════
function toggleAcc(id, header) {
    const body=document.getElementById(id), chev=header?.querySelector('.acc-chevron');
    const open=body.classList.contains('open');
    if(open){body.style.maxHeight='0';body.classList.remove('open');chev?.classList.remove('open');}
    else{body.style.maxHeight=body.scrollHeight+200+'px';body.classList.add('open');chev?.classList.add('open');}
}
function accRefresh(id){const b=document.getElementById(id);if(b?.classList.contains('open'))b.style.maxHeight=b.scrollHeight+200+'px';}

// ════════════════════════════════════════════════════════════
// HELPERS
// ════════════════════════════════════════════════════════════
function closeModal(id){ document.getElementById(id).classList.add('hidden'); }
document.querySelectorAll('.modal-bg').forEach(m=>m.addEventListener('click',e=>{if(e.target===m)m.classList.add('hidden');}));

function sp(v){if(!v)return null;if(typeof v==='object')return v;try{return JSON.parse(v);}catch{return null;}}
function esc(s){return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
function badgeHtml(status){const m={open:['badge-open','🟡 Open'],in_progress:['badge-in_progress','🔵 In Progress'],done:['badge-done','✅ Done'],cancelled:['badge-cancelled','❌ Cancelled']};const[cls,label]=m[status]??['',status];return`<span class="badge ${cls}">${label}</span>`;}
function fmtDate(s){if(!s)return'—';const m=s.match(/^(\d{2})-(\d{2})-(\d{4})/);if(m)return`${m[1]} ${['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][+m[2]-1]} ${m[3]}`;return s;}

function showToast(type,msg){
    const inner=document.getElementById('toastInner');
    document.getElementById('toastMsg').textContent=msg;
    inner.className=`flex items-center gap-3 px-5 py-3 rounded-xl shadow-lg text-white text-sm font-medium ${type==='success'?'bg-green-600':'bg-red-500'}`;
    document.getElementById('toastIcon').className=`fas ${type==='success'?'fa-check-circle':'fa-exclamation-circle'} text-lg`;
    document.getElementById('toast').classList.remove('hidden');
    setTimeout(()=>document.getElementById('toast').classList.add('hidden'),3500);
}

loadWork();
loadDepts();
</script>
</body>
</html>