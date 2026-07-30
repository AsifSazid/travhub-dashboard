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
            min-width:200px; max-width:480px;
            position:relative;
        }
        #sidebarResizeHandle {
            position:absolute; left:0; top:0; bottom:0; width:4px;
            cursor:col-resize; background:transparent; z-index:10;
            transition:background .15s;
        }
        #sidebarResizeHandle:hover, #sidebarResizeHandle.dragging {
            background:#6366f1;
        }
        #rightSidebar::-webkit-scrollbar{width:4px;}
        #rightSidebar::-webkit-scrollbar-thumb{background:#e5e7eb;border-radius:4px;}

        /* ── Accordion ──────────────────────────────────────── */
        .sc{background:#fff;border-radius:12px;border:1px solid #f1f5f9;box-shadow:0 1px 3px rgba(0,0,0,.05);}
        .f-input{width:100%;padding:7px 11px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:.83rem;color:#1f2937;outline:none;transition:border .15s;background:#fff;}
        .f-input:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.07);}
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
            <!-- Common info chips -->
            <div class="flex items-center gap-3 ml-3" id="commonInfoStrip"></div>
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

                <!-- Service Module -->
                <div class="bg-white rounded-xl shadow-sm mb-4">
                    <!-- Service selector — sticky inside mainArea (overflow-y-auto) -->
                    <div class="flex border-b border-gray-100 bg-gray-50 px-2 pt-1.5 sticky top-0 z-20" id="svcModuleTabBar" style="border-radius:12px 12px 0 0;"></div>
                    <!-- Service info strip -->
                    <div id="svcInfoStrip" class="hidden items-center gap-3 px-4 py-1.5 border-b border-gray-100 bg-gray-50 text-xs text-gray-500 flex-wrap sticky z-10" style="top:42px;"></div>
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
                        <div class="flex items-center gap-2">
                            <button id="mergeTasksBtn" onclick="mergeSelectedTasks()" class="hidden px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg transition">
                                <i class="fas fa-compress-arrows-alt mr-1"></i>Merge Selected
                            </button>
                            <span class="text-xs text-gray-400" id="confirmedTasksCount">0 tasks</span>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mb-2 hidden" id="mergeHint"><i class="fas fa-info-circle mr-1"></i>Click cards to select, then merge</p>
                    <div id="confirmedTasksList">
                        <p class="text-xs text-gray-400 italic">Tasks auto-created when a confirmation is marked Confirmed.</p>
                    </div>
                </div>

            </div>

            <!-- ── RIGHT SIDEBAR ──────────────────────────── -->
            <div id="rightSidebar">
                <div id="sidebarResizeHandle"></div>

                <!-- Travelers (always open, no accordion) -->
                <div class="sc p-3">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 bg-teal-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-users text-teal-500 text-xs"></i>
                            </div>
                            <span class="text-sm font-semibold text-gray-700">Travelers</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <button onclick="openPassportCarousel()" id="btnPassportCarousel"
                                class="hidden px-2 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-500 rounded text-xs" title="View Passports">
                                <i class="fas fa-id-card"></i>
                            </button>
                            <button onclick="openTravelerTableModal()" id="btnTravelerTable"
                                class="hidden px-2 py-1 bg-teal-50 hover:bg-teal-100 text-teal-500 rounded text-xs" title="Expand Table">
                                <i class="fas fa-table"></i>
                            </button>
                            <button onclick="openNewTravelerModal()" class="text-xs text-teal-500 hover:text-teal-700 font-semibold px-2 py-1">
                                <i class="fas fa-plus mr-1"></i>Add
                            </button>
                        </div>
                    </div>
                    <div class="relative mb-2">
                        <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-300 text-xs pointer-events-none"></i>
                        <input type="text" id="travelerSearchInput" placeholder="Search & link existing traveler…"
                            class="f-input pl-7 text-xs" autocomplete="off"
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
                            <!-- Quick note input -->
                            <div class="flex gap-1.5">
                                <label class="w-7 h-7 bg-gray-100 hover:bg-gray-200 rounded-lg flex items-center justify-center cursor-pointer flex-shrink-0 transition" title="Attach file">
                                    <i class="fas fa-paperclip text-gray-400 text-[10px]"></i>
                                    <input type="file" id="noteboardFileInput" class="hidden" onchange="noteboardFileSelected(this)">
                                </label>
                                <button id="nb-sidebar-rec-btn" onclick="nbSidebarRecToggle()"
                                    class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0 transition"
                                    style="background:#f1f5f9;" title="Voice record">
                                    <i class="fas fa-microphone text-gray-400 text-[10px]"></i>
                                </button>
                                <input type="text" id="noteboardInput" placeholder="Add a note…"
                                    class="flex-1 px-2.5 py-1.5 text-xs border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-200"
                                    onkeydown="if(event.key==='Enter')noteboardSendText()">
                                <button onclick="noteboardSendText()" class="px-2.5 py-1.5 bg-indigo-500 hover:bg-indigo-600 text-white rounded-lg text-xs transition">
                                    <i class="fas fa-paper-plane text-[10px]"></i>
                                </button>
                            </div>
                            <div id="noteboardFilePreview" class="hidden text-[10px] text-indigo-600 bg-indigo-50 rounded-lg px-2 py-1 flex items-center gap-1.5">
                                <i class="fas fa-paperclip flex-shrink-0"></i>
                                <span id="noteboardFileName" class="flex-1 truncate"></span>
                                <button onclick="noteboardClearFile()" class="text-red-400 hover:text-red-600"><i class="fas fa-times"></i></button>
                            </div>
                            <button onclick="openNoteBoardModal()"
                                class="w-full py-1.5 text-indigo-500 hover:text-indigo-700 text-xs font-semibold text-center border border-indigo-100 rounded-lg hover:bg-indigo-50 transition">
                                Open Note Board <i class="fas fa-arrow-right ml-1"></i>
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

<!-- New Traveler Modal -->
<div id="newTravelerModal" class="fixed inset-0 z-[70] hidden modal-bg flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md flex flex-col" style="max-height:90vh;">
        <div class="flex items-center justify-between p-4 border-b border-gray-100 flex-shrink-0">
            <h3 class="font-semibold text-gray-800 text-sm"><i class="fas fa-user-plus mr-2 text-teal-500"></i>New Traveler</h3>
            <button onclick="closeModal('newTravelerModal')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <div class="overflow-y-auto flex-1 p-4 space-y-3">
            <label id="ntDropZone" class="flex flex-col items-center gap-2 px-4 py-5 border-2 border-dashed border-teal-300 rounded-xl cursor-pointer hover:border-teal-400 hover:bg-teal-50 transition-colors">
                <i class="fas fa-id-card text-teal-400 text-2xl"></i>
                <span class="text-sm text-gray-600 font-medium">Upload Passport Scan</span>
                <span class="text-xs text-gray-400">JPG, PNG, PDF, WebP</span>
                <input type="file" id="ntFileInput" accept=".jpg,.jpeg,.png,.webp,.pdf" class="hidden" onchange="ntFileSelected(this)">
            </label>
            <div id="ntFilePreview" class="hidden text-xs text-teal-600 bg-teal-50 rounded-lg px-3 py-2 flex items-center gap-2">
                <i class="fas fa-file"></i>
                <span id="ntFileName" class="flex-1 truncate"></span>
                <button onclick="ntClearFile()" class="text-red-400 hover:text-red-600"><i class="fas fa-times"></i></button>
            </div>
            <div id="ntProgress" class="hidden text-center py-2 text-xs text-teal-600">
                <i class="fas fa-spinner fa-spin mr-1"></i><span id="ntProgressText">Extracting...</span>
            </div>
            <div id="ntExtracted" class="hidden bg-gray-50 rounded-xl p-3 text-xs space-y-1"></div>
            <div id="ntDuplicateBox" class="hidden border border-yellow-300 bg-yellow-50 rounded-xl p-3">
                <div class="flex items-center gap-2 mb-2">
                    <i class="fas fa-exclamation-triangle text-yellow-500"></i>
                    <span class="text-xs font-bold text-yellow-700">Traveler Already Exists</span>
                </div>
                <div id="ntDuplicateInfo" class="text-xs text-gray-700 mb-3"></div>
                <button onclick="ntLinkExisting()"
                    class="w-full py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg text-xs font-semibold transition">
                    <i class="fas fa-link mr-1"></i>Link This Traveler
                </button>
            </div>
        </div>
        <div class="p-4 border-t border-gray-100 flex-shrink-0 flex gap-2">
            <button onclick="ntExtractAndCheck()" id="ntExtractBtn"
                class="flex-1 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-sm font-semibold transition">
                <i class="fas fa-wand-magic-sparkles mr-1.5"></i>Extract & Check
            </button>
            <button onclick="ntCreate()" id="ntCreateBtn" class="hidden flex-1 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-xl text-sm font-semibold transition">
                <i class="fas fa-user-plus mr-1.5"></i>Create & Link
            </button>
        </div>
    </div>
</div>

<!-- Passport Carousel Modal -->
<div id="passportCarouselModal" class="fixed inset-0 z-[70] hidden modal-bg flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl">
        <div class="flex items-center justify-between p-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800 text-sm"><i class="fas fa-id-card mr-2 text-indigo-500"></i>Passports</h3>
            <button onclick="closeModal('passportCarouselModal')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-4">
            <div class="relative">
                <div id="carouselSlides" class="overflow-hidden rounded-xl bg-gray-50 min-h-[300px] flex items-center justify-center">
                    <i class="fas fa-spinner fa-spin text-gray-300 text-2xl"></i>
                </div>
                <button onclick="carouselPrev()" class="absolute left-2 top-1/2 -translate-y-1/2 w-9 h-9 bg-white shadow-lg rounded-full flex items-center justify-center text-gray-600 hover:bg-gray-50 transition">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button onclick="carouselNext()" class="absolute right-2 top-1/2 -translate-y-1/2 w-9 h-9 bg-white shadow-lg rounded-full flex items-center justify-center text-gray-600 hover:bg-gray-50 transition">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            <div class="mt-3 text-center">
                <p id="carouselName" class="text-sm font-semibold text-gray-700 mb-2"></p>
                <div id="carouselDots" class="flex justify-center gap-1.5"></div>
            </div>
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
            <h3 class="font-semibold text-gray-800 text-sm"><i class="fas fa-comments mr-2 text-indigo-400"></i>Mind Board <span class="text-xs text-gray-400 font-normal ml-1" id="mbServiceLabel"></span></h3>
            <div class="flex items-center gap-2">
                <button onclick="openAiModal()"
                    class="flex items-center gap-1.5 px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 rounded-lg text-xs font-semibold transition"
                    title="AI Planning Briefing">
                    <i class="fas fa-wand-magic-sparkles text-[10px]"></i>AI Brief
                </button>
                <button onclick="closeModal('mindBoardModal')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
            </div>
        </div>
        <!-- AI Planning Board — shown after generation -->
        <div id="aiPlanningBoard" class="hidden flex-shrink-0 border-b border-indigo-100 bg-indigo-50 px-4 py-3 text-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider"><i class="fas fa-brain mr-1.5"></i>AI Planning Briefing</span>
                <button onclick="document.getElementById('aiPlanningBoard').classList.add('hidden')" class="text-indigo-300 hover:text-indigo-500 text-xs"><i class="fas fa-times"></i></button>
            </div>
            <div id="aiPlanningContent" class="text-gray-700 text-xs leading-relaxed max-h-36 overflow-y-auto"></div>
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

<!-- Note Board Modal (right sidebar Notes — noteboard) -->
<div id="noteBoardModal" class="fixed inset-0 z-50 hidden modal-bg flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg flex flex-col" style="height:85vh;">
        <div class="flex items-center justify-between p-4 border-b border-gray-100 flex-shrink-0">
            <h3 class="font-semibold text-gray-800 text-sm"><i class="fas fa-sticky-note mr-2 text-blue-400"></i>Note Board <span class="text-xs text-gray-400 font-normal ml-1" id="nbServiceLabel"></span></h3>
            <button onclick="closeModal('noteBoardModal')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <div id="nbChatArea" class="flex-1 overflow-y-auto px-4 py-3 flex flex-col gap-2">
            <div class="text-center py-6 text-gray-300 text-xs"><i class="fas fa-spinner fa-spin"></i></div>
        </div>
        <div class="flex-shrink-0 border-t border-gray-100 bg-white p-3">
            <div id="nbFilePreview" class="hidden mb-2 bg-indigo-50 rounded-lg px-3 py-1.5 text-xs text-indigo-600 flex items-center gap-2">
                <i class="fas fa-paperclip flex-shrink-0"></i>
                <span id="nbFilePreviewName" class="flex-1 truncate"></span>
                <button onclick="nbClearFile()" class="text-red-400 hover:text-red-600"><i class="fas fa-times"></i></button>
            </div>
            <div class="flex items-end gap-2">
                <label class="w-9 h-9 bg-gray-100 hover:bg-gray-200 rounded-full flex items-center justify-center cursor-pointer flex-shrink-0 transition">
                    <i class="fas fa-paperclip text-gray-500 text-sm"></i>
                    <input type="file" id="nbFileInput" class="hidden" onchange="nbFileSelected(this)">
                </label>
                <button id="nb-rec-btn" onclick="nbRecToggle()"
                    class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0 transition"
                    style="background:#f1f5f9;">
                    <i class="fas fa-microphone" style="color:#64748b;font-size:.75rem;"></i>
                </button>
                <textarea id="nbTextInput" rows="1" placeholder="Write a note… (Enter to send)"
                    onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();nbSend();}"
                    oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,100)+'px'"
                    style="flex:1;resize:none;border:1.5px solid #e5e7eb;border-radius:20px;padding:8px 14px;font-size:.83rem;outline:none;max-height:100px;overflow-y:auto;transition:border .15s;"></textarea>
                <button onclick="nbSend()"
                    class="w-9 h-9 bg-blue-500 hover:bg-blue-600 rounded-full flex items-center justify-center flex-shrink-0 transition">
                    <i class="fas fa-paper-plane text-white text-sm"></i>
                </button>
            </div>
        </div>
    </div>
</div>

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

<!-- AI Modal -->
<div id="aiModal" class="fixed inset-0 z-[60] hidden modal-bg flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between p-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800 text-sm"><i class="fas fa-wand-magic-sparkles mr-2 text-indigo-500"></i>AI Planning Briefing</h3>
            <button onclick="closeModal('aiModal')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-4">
            <p class="text-xs text-gray-400 mb-3">AI will analyze your Mind Board notes and generate a planning briefing. The result will appear inside the Mind Board.</p>
            <button onclick="aiGenerate()" id="aiGenBtn"
                class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold transition">
                <i class="fas fa-wand-magic-sparkles mr-1.5"></i>Generate Briefing
            </button>
            <div id="aiGenStatus" class="hidden mt-3 text-center text-xs text-indigo-500">
                <i class="fas fa-spinner fa-spin mr-1"></i>Analyzing notes…
            </div>
        </div>
    </div>
</div>

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
const CURRENT_USER = "<?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>";
const API = {
    getWork:       "<?php echo $getWorkApi; ?>",
    status:        "<?php echo $updateStatusApi; ?>",
    addService:    "<?php echo $addServiceApi; ?>",
    depts:         "<?php echo $deptApi; ?>",
    workTravelers: "<?php echo $workTravelersApi; ?>",
    travelers:     "<?php echo $allTravelersApi; ?>",
    airTickets:    "<?php echo $airTicketsApi; ?>",
    notes:         "<?php echo $notesApi; ?>",
    extractDocument:  "<?php echo $ip_port; ?>api/travelers/extract-document.php",
    storeNewTraveler: "<?php echo $ip_port; ?>api/travelers/store.php",
    checkDuplicate:   "<?php echo $ip_port; ?>api/travelers/check-duplicate.php",
    aiMindboard:      "<?php echo $ip_port; ?>api/works/ai-mindboard.php",
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
let allDepts         = [];
let activeServiceSlug = null;

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

    // Common info strip — breadcrumb এর পাশে
    const segLabels = { one_way:'One Way', round_trip:'Round Trip', multi_city:'Multi City' };
    const dot = `<span class="text-gray-300 text-xs">·</span>`;
    const chips = [
        ci.name  ? `<span class="text-xs font-semibold text-gray-700"><i class="fas fa-user text-gray-300 mr-1 text-[10px]"></i>${esc(ci.name)}</span>` : '',
        ci.phone ? `<span class="text-xs text-gray-400"><i class="fas fa-phone text-gray-300 mr-1 text-[10px]"></i>${esc(ci.phone)}</span>` : '',
        workData.segment_type ? `<span class="text-xs px-2 py-0.5 bg-indigo-50 text-indigo-600 rounded-full font-medium">${segLabels[workData.segment_type] ?? workData.segment_type}</span>` : '',
    ].filter(Boolean);
    document.getElementById('commonInfoStrip').innerHTML = chips.join(` ${dot} `);

    renderSpecialIns(workData, null);
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
// SPECIAL INSTRUCTIONS — service-specific
// ════════════════════════════════════════════════════════════
function renderSpecialIns(w, activeSlug) {
    const segData  = sp(w.segment_data) ?? {};
    const svcTypes = sp(w.service_type) ?? [];
    const services = Array.isArray(svcTypes) ? svcTypes : [svcTypes];
    const items    = [];

    // Only show active service's instructions (or all if no active)
    const targetServices = activeSlug ? [activeSlug] : services;

    targetServices.forEach(slug => {
        const svcData = segData[slug] ?? {};
        const segs    = svcData.segments ?? [svcData];
        segs.forEach(seg => {
            (seg.special_instruction ?? []).forEach(ins => {
                if (ins) items.push({ service: SVC_INFO[slug]?.label ?? slug, text: ins });
            });
        });
        (svcData.special_instruction ?? []).forEach(ins => {
            if (ins) items.push({ service: SVC_INFO[slug]?.label ?? slug, text: ins });
        });
    });

    // Work-level instructions always show
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
    activeServiceSlug = slug;
    const mount = document.getElementById('at-tab-mount');
    mount.innerHTML = '';

    // Service-specific info strip (segment type, route etc)
    renderSvcInfoStrip(slug);

    // Reload right sidebar Notes (noteboard) for this service
    loadNoteboard(slug);

    // Reload special instructions for this service
    renderSpecialIns(workData, slug);

    if (slug === 'air_ticket') {
        const ci = sp(workData.client_info) ?? {};
        initWorkAirTicketTab({
            workSysId:    WORK_SYS_ID,
            leadSysId:    workData.lead_sys_id ?? '',
            clientName:   ci.name ?? '',
            serviceSlug:  slug,
            currentUser:  CURRENT_USER,
            atData:       airTicketData,
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

// Service-specific info strip — slim single/double line below tab bar
function renderSvcInfoStrip(slug) {
    const strip   = document.getElementById('svcInfoStrip');
    const innerTab = document.getElementById('at-inner-tab-bar');
    const segData = sp(workData.segment_data) ?? {};
    const svcData = segData[slug] ?? {};
    const chips   = [];
    const dot     = `<span class="text-gray-300">·</span>`;

    if (slug === 'air_ticket') {
        const segLabels = { one_way:'One Way', round_trip:'Round Trip', multi_city:'Multi City' };
        if (svcData.segment_type) chips.push(`<span class="font-medium text-sky-600">${segLabels[svcData.segment_type] ?? svcData.segment_type}</span>`);
        const segs = svcData.segments ?? [];
        segs.forEach(seg => {
            const from = seg.from_city || seg.from || (seg.route?.split(/[=\-→]/)[0]?.trim()) || '';
            const to   = seg.to_city   || seg.to   || (seg.route?.split(/[=\-→]/).pop()?.trim()) || '';
            const date = seg.departure_date || seg.travel_date || seg.date || '';
            const dateStr = date ? ` <span class="text-gray-400">(${fmtDateShort(date)})</span>` : '';
            if (from || to) chips.push(`<span class="flex items-center gap-1"><i class="fas fa-plane text-sky-400 text-[9px]"></i>${esc(from)} → ${esc(to)}${dateStr}</span>`);
        });
        // Pax breakdown
        const pa = svcData.pax_adult ?? '', pc = svcData.pax_child ?? '', pi = svcData.pax_infant ?? '';
        if (pa !== '' || pc !== '' || pi !== '') {
            const paxStr = [pa !== '' ? `${pa}A` : '', pc !== '' ? `${pc}C` : '', pi !== '' ? `${pi}I` : ''].filter(Boolean).join(' ');
            chips.push(`<span class="text-gray-600 font-medium">${paxStr}</span>`);
        } else if (svcData.pax) {
            chips.push(`<span><i class="fas fa-user text-gray-300 mr-1"></i>${esc(String(svcData.pax))} Pax</span>`);
        }
        // Flexibility from first segment
        const flex = segs[0]?.date_flexibility || '';
        if (flex) chips.push(`<span class="text-gray-400 text-[10px]">${esc(flex)}</span>`);

    } else if (slug === 'hotel') {
        const segs = svcData.segments ?? [];
        segs.forEach(seg => {
            const loc    = [seg.hotel_name, seg.city_name].filter(Boolean).join(', ');
            const nights = seg.nights || seg.total_nights || '';
            const rooms  = seg.rooms  || '';
            if (loc)    chips.push(`<span><i class="fas fa-hotel text-pink-400 mr-1 text-[9px]"></i>${esc(loc)}</span>`);
            if (nights) chips.push(`<span>${esc(String(nights))} Nights</span>`);
            if (rooms)  chips.push(`<span>${esc(String(rooms))} Rooms</span>`);
        });

    } else if (slug === 'visa') {
        const segs = svcData.segments ?? [];
        segs.forEach(seg => {
            const country = seg.country_name  || '';
            const cat     = seg.category_name || seg.visa_type || '';
            if (country) chips.push(`<span><i class="fas fa-passport text-violet-400 mr-1 text-[9px]"></i>${esc(country)}</span>`);
            if (cat)     chips.push(`<span>${esc(cat)}</span>`);
        });

    } else if (slug === 'tour_package' || slug === 'package') {
        const dests = svcData.destinations ?? [];
        dests.forEach(d => {
            const loc = [d.country_name, ...(d.city_names ?? [])].filter(Boolean).join(', ');
            if (loc) chips.push(`<span><i class="fas fa-suitcase-rolling text-green-400 mr-1 text-[9px]"></i>${esc(loc)}</span>`);
        });
        if (svcData.duration) chips.push(`<span>${esc(String(svcData.duration))} Days</span>`);

    } else if (slug === 'umrah') {
        if (svcData.umrah_type)    chips.push(`<span class="capitalize font-medium text-amber-600">${esc(svcData.umrah_type.replace('_',' '))}</span>`);
        if (svcData.total_nights)  chips.push(`<span>${esc(String(svcData.total_nights))} Nights</span>`);
        if (svcData.pax)           chips.push(`<span><i class="fas fa-user text-gray-300 mr-1"></i>${esc(String(svcData.pax))} Pax</span>`);

    } else if (slug === 'transport') {
        const segs = svcData.segments ?? [];
        segs.forEach(seg => {
            const route = [seg.from, seg.to].filter(Boolean).join(' → ');
            if (seg.type)  chips.push(`<span class="capitalize">${esc(seg.type)}</span>`);
            if (route)     chips.push(`<span><i class="fas fa-van-shuttle text-teal-400 mr-1 text-[9px]"></i>${esc(route)}</span>`);
        });
    }

    if (!chips.length) {
        strip.classList.add('hidden');
        strip.classList.remove('flex');
        if (innerTab) innerTab.style.top = '42px';
        return;
    }
    strip.classList.remove('hidden');
    strip.classList.add('flex');
    strip.innerHTML = chips.join(` ${dot} `);
    if (innerTab) innerTab.style.top = '74px'; // 42px service nav + 32px info strip
}

// ════════════════════════════════════════════════════════════
// CONFIRMED TASKS
// ════════════════════════════════════════════════════════════
let _selectedTaskIds = new Set();

function renderConfirmedTasks() {
    const list  = document.getElementById('confirmedTasksList');
    const count = document.getElementById('confirmedTasksCount');
    const hint  = document.getElementById('mergeHint');
    _selectedTaskIds.clear();
    updateMergeBtn();

    count.textContent = confirmedTasks.length + ' task(s)';
    if (!confirmedTasks.length) {
        list.innerHTML = '<p class="text-xs text-gray-400 italic">Tasks auto-created when a confirmation is marked Confirmed.</p>';
        hint?.classList.add('hidden');
        return;
    }
    if (confirmedTasks.length > 1) hint?.classList.remove('hidden');

    list.innerHTML = confirmedTasks.map(t => `
        <div class="conf-task-card cursor-pointer select-none" id="ctc-${esc(t.sys_id)}"
            onclick="toggleTaskSelect('${esc(t.sys_id)}')">
            <div class="flex items-start justify-between">
                <div class="flex items-start gap-2">
                    <div class="w-4 h-4 rounded border-2 border-gray-300 flex-shrink-0 mt-0.5 flex items-center justify-center transition ctc-check" id="chk-${esc(t.sys_id)}"></div>
                    <div>
                        <p class="text-sm font-semibold text-gray-800">${esc(t.workname ?? 'Task')}</p>
                        <p class="text-xs font-mono text-indigo-400 mt-0.5">${esc(t.sys_id)}</p>
                    </div>
                </div>
                <span class="text-[10px] font-semibold px-2 py-1 rounded-full flex-shrink-0 ${t.status==='done'?'bg-green-100 text-green-700':'bg-yellow-100 text-yellow-700'}">
                    ${t.status === 'done' ? 'Done' : 'Open'}
                </span>
            </div>
            <div class="flex items-center justify-between mt-2 pt-2 border-t border-gray-100">
                <span class="text-xs text-gray-400">Financial only</span>
                <a href="show-tasks.php?id=${esc(t.sys_id)}" onclick="event.stopPropagation()"
                    class="text-xs text-indigo-500 hover:text-indigo-700 font-semibold">
                    Open <i class="fas fa-arrow-right ml-1 text-[9px]"></i>
                </a>
            </div>
        </div>`).join('');
}

function toggleTaskSelect(sysId) {
    const card = document.getElementById(`ctc-${sysId}`);
    const chk  = document.getElementById(`chk-${sysId}`);
    if (_selectedTaskIds.has(sysId)) {
        _selectedTaskIds.delete(sysId);
        card.classList.remove('border-indigo-500', 'bg-indigo-50');
        chk.innerHTML = '';
        chk.classList.remove('bg-indigo-500', 'border-indigo-500');
    } else {
        _selectedTaskIds.add(sysId);
        card.classList.add('border-indigo-500', 'bg-indigo-50');
        chk.innerHTML = '<i class="fas fa-check text-white text-[8px]"></i>';
        chk.classList.add('bg-indigo-500', 'border-indigo-500');
    }
    updateMergeBtn();
}

function updateMergeBtn() {
    const btn = document.getElementById('mergeTasksBtn');
    if (!btn) return;
    if (_selectedTaskIds.size >= 2) {
        btn.classList.remove('hidden');
        btn.textContent = '';
        btn.innerHTML = `<i class="fas fa-compress-arrows-alt mr-1"></i>Merge ${_selectedTaskIds.size} Tasks`;
    } else {
        btn.classList.add('hidden');
    }
}

async function mergeSelectedTasks() {
    if (_selectedTaskIds.size < 2) return;
    if (!confirm(`Merge ${_selectedTaskIds.size} tasks into one?`)) return;
    try {
        const r = await fetch(`<?php echo $ip_port; ?>api/works/merge-tasks.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ work_sys_id: WORK_SYS_ID, task_ids: [..._selectedTaskIds] }),
        });
        const j = await r.json();
        if (j.status === 'success') {
            showToast('success', `Merged! New task: ${j.new_task_id}`);
            await reloadConfirmedTasks();
        } else showToast('error', j.message ?? 'Merge failed');
    } catch { showToast('error', 'Network error'); }
}

// ════════════════════════════════════════════════════════════
// AI PLANNING BRIEFING
// ════════════════════════════════════════════════════════════
function openAiModal() {
    document.getElementById('aiModal').classList.remove('hidden');
}

async function aiGenerate() {
    const btn    = document.getElementById('aiGenBtn');
    const status = document.getElementById('aiGenStatus');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5"></i>Generating…';
    if (status) status.classList.remove('hidden');
    try {
        const r = await fetch(API.aiMindboard, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                work_sys_id:  WORK_SYS_ID,
                service_slug: activeServiceSlug ?? 'air_ticket',
            }),
        });
        const j = await r.json();
        const board   = document.getElementById('aiPlanningBoard');
        const content = document.getElementById('aiPlanningContent');
        if (board && content) {
            content.innerHTML = j.status === 'success'
                ? j.html
                : `<p class="text-red-400">${esc(j.message ?? 'Failed')}</p>`;
            board.classList.remove('hidden');
        }
        closeModal('aiModal');
        openMindBoardModal();
    } catch {
        const content = document.getElementById('aiPlanningContent');
        if (content) content.innerHTML = '<p class="text-red-400">Network error</p>';
    }
    btn.disabled  = false;
    btn.innerHTML = '<i class="fas fa-wand-magic-sparkles mr-1.5"></i>Generate Briefing';
    if (status) status.classList.add('hidden');
}


let _nbNotes = [], _nbFile = null, _noteboardFile = null;

async function loadNoteboard(slug) {
    if (!slug) return;
    try {
        const r = await fetch(`${API.notes}?action=list&work_sys_id=${encodeURIComponent(WORK_SYS_ID)}&service_slug=${encodeURIComponent(slug)}&board=noteboard`);
        const j = await r.json();
        _nbNotes = j.status === 'success' ? (j.data ?? []) : [];
    } catch { _nbNotes = []; }
    renderNoteboardSidebar();
    if (!document.getElementById('noteBoardModal').classList.contains('hidden')) renderNbChat();
}

function renderNoteboardSidebar() {
    const badge    = document.getElementById('notesBadge');
    const sideList = document.getElementById('notesSideList');
    if (_nbNotes.length) { badge.textContent = _nbNotes.length; badge.classList.remove('hidden'); }
    else badge.classList.add('hidden');
    const textNotes = _nbNotes.filter(n => n.note_type === 'text').slice(0, 8);
    if (!textNotes.length) {
        sideList.innerHTML = '<p class="text-xs text-gray-400 py-2 text-center">No notes yet.</p>';
    } else {
        sideList.innerHTML = textNotes.map(n => `
            <div class="note-chip" onclick="openNoteDetail('${esc(n.sys_id)}','nb')">
                <i class="fas fa-comment text-blue-300 text-xs flex-shrink-0 mt-0.5"></i>
                <span class="note-chip-text">${esc(n.content ?? '')}</span>
            </div>`).join('');
    }
    accRefresh('acc-notes');
}

function openNoteDetail(sysId, board) {
    const notes = board === 'nb' ? _nbNotes : _mbNotes;
    const note  = notes.find(n => n.sys_id === sysId);
    if (!note) return;
    document.getElementById('noteDetailText').textContent = note.content ?? '';
    const meta = note.meta_data?.created_by_date;
    document.getElementById('noteDetailTime').textContent = meta ? `${meta.user} · ${meta.date}` : '';
    document.getElementById('noteDetailModal').classList.remove('hidden');
}

function openNoteBoardModal() {
    document.getElementById('noteBoardModal').classList.remove('hidden');
    const label = document.getElementById('nbServiceLabel');
    if (label) label.textContent = activeServiceSlug ? '— ' + (SVC_INFO[activeServiceSlug]?.label ?? activeServiceSlug) : '';
    renderNbChat();
}

function renderNbChat() {
    const area = document.getElementById('nbChatArea'); if (!area) return;
    if (!_nbNotes.length) { area.innerHTML = '<div class="text-center py-8 text-gray-300 text-xs">No notes yet.</div>'; return; }
    area.innerHTML = _nbNotes.map(n => _nbBubble(n)).join('');
    setTimeout(() => { area.scrollTop = area.scrollHeight; }, 50);
}

function _nbBubble(n) {
    const meta    = n.meta_data?.created_by_date;
    const time    = meta ? `${meta.user} · ${meta.date}` : '';
    const creator = meta?.user ?? n.created_by ?? '';
    const canDel  = !creator || creator === CURRENT_USER;
    const del     = canDel
        ? `<button onclick="nbDel('${n.sys_id}')" class="absolute -top-1.5 -right-1.5 w-4 h-4 bg-red-500 text-white rounded-full text-[9px] hidden items-center justify-center hover:bg-red-600 nb-del"><i class="fas fa-times"></i></button>`
        : '';
    if (n.note_type === 'text') return `
        <div class="flex flex-col items-start relative group nb-bubble-wrap">
            <div class="relative bg-blue-50 border border-blue-100 rounded-2xl rounded-bl-sm px-3 py-2 text-sm text-gray-800 max-w-[85%] whitespace-pre-wrap">${esc(n.content??'')}${del}</div>
            <span class="text-[10px] text-gray-400 mt-1 ml-1">${esc(time)}</span>
        </div>`;
    if (n.note_type === 'image') return `
        <div class="flex flex-col items-start relative group nb-bubble-wrap">
            <div class="relative bg-white border border-gray-200 rounded-xl p-1 max-w-[85%]">
                <img src="${esc(n.serve_url??'')}" class="rounded-lg max-h-52 object-contain cursor-pointer" onclick="window._previewOpen&&window._previewOpen(this.src)">${del}
            </div>
            <span class="text-[10px] text-gray-400 mt-1 ml-1">${esc(time)}</span>
        </div>`;
    if (n.note_type === 'audio') return `
        <div class="flex flex-col items-start relative group nb-bubble-wrap">
            <div class="relative bg-violet-50 border border-violet-100 rounded-xl px-3 py-2 max-w-[85%]">
                <audio controls src="${esc(n.serve_url??'')}" class="h-8 w-48"></audio>${del}
            </div>
            <span class="text-[10px] text-gray-400 mt-1 ml-1">${esc(time)}</span>
        </div>`;
    return `
        <div class="flex flex-col items-start relative group nb-bubble-wrap">
            <div class="relative bg-green-50 border border-green-100 rounded-xl px-3 py-2 flex items-center gap-2 max-w-[85%]">
                <i class="fas fa-file text-green-500 text-sm flex-shrink-0"></i>
                <a href="${esc(n.serve_url??'')}?dl=1" class="text-xs text-green-700 font-medium truncate max-w-[140px]" download>${esc(n.file_name??'file')}</a>${del}
            </div>
            <span class="text-[10px] text-gray-400 mt-1 ml-1">${esc(time)}</span>
        </div>`;
}

document.addEventListener('mouseover', e => {
    const nw = e.target.closest('.nb-bubble-wrap');
    document.querySelectorAll('.nb-del').forEach(b => b.style.display='none');
    if (nw) { const btn = nw.querySelector('.nb-del'); if(btn) btn.style.display='flex'; }
});

// Sidebar file input
function noteboardFileSelected(input) {
    _noteboardFile = input.files[0] ?? null;
    if (_noteboardFile) {
        document.getElementById('noteboardFileName').textContent = _noteboardFile.name;
        document.getElementById('noteboardFilePreview').classList.remove('hidden');
    }
}
function noteboardClearFile() {
    _noteboardFile = null;
    document.getElementById('noteboardFileInput').value = '';
    document.getElementById('noteboardFilePreview').classList.add('hidden');
}

// Voice recording for Note Board sidebar
let _nbSidebarRecorder = null, _nbSidebarRecChunks = [], _nbSidebarRecording = false;
async function nbSidebarRecToggle() {
    const btn = document.getElementById('nb-sidebar-rec-btn');
    if (_nbSidebarRecording) {
        _nbSidebarRecorder?.stop();
        return;
    }
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        _nbSidebarRecChunks = [];
        _nbSidebarRecorder  = new MediaRecorder(stream);
        _nbSidebarRecorder.ondataavailable = e => { if (e.data.size > 0) _nbSidebarRecChunks.push(e.data); };
        _nbSidebarRecorder.onstop = async () => {
            stream.getTracks().forEach(t => t.stop());
            _nbSidebarRecording = false;
            if (btn) { btn.style.background = '#f1f5f9'; btn.innerHTML = '<i class="fas fa-microphone text-gray-400 text-[10px]"></i>'; }
            const blob = new Blob(_nbSidebarRecChunks, { type: 'audio/webm' });
            const slug = activeServiceSlug; if (!slug) return;
            const fd = new FormData();
            fd.append('action', 'upload');
            fd.append('work_sys_id', WORK_SYS_ID);
            fd.append('service_slug', slug);
            fd.append('board', 'noteboard');
            fd.append('file', blob, 'voice_' + Date.now() + '.webm');
            try {
                const r = await fetch(API.notes, { method:'POST', body:fd });
                const j = await r.json();
                if (j.status === 'success') await loadNoteboard(slug);
                else showToast('error', j.message);
            } catch { showToast('error', 'Upload failed'); }
        };
        _nbSidebarRecorder.start();
        _nbSidebarRecording = true;
        if (btn) { btn.style.background = '#fee2e2'; btn.innerHTML = '<i class="fas fa-stop text-red-500 text-[10px]"></i>'; }
    } catch { alert('Microphone access denied'); }
}

async function noteboardSendText() {
    const slug = activeServiceSlug; if (!slug) return;
    if (_noteboardFile) {
        const fd = new FormData();
        fd.append('action','upload'); fd.append('work_sys_id', WORK_SYS_ID);
        fd.append('service_slug', slug); fd.append('board', 'noteboard');
        fd.append('file', _noteboardFile);
        try {
            const r = await fetch(API.notes, {method:'POST', body:fd});
            const j = await r.json();
            if (j.status==='success') { noteboardClearFile(); await loadNoteboard(slug); }
            else showToast('error', j.message);
        } catch { showToast('error','Upload failed'); }
        return;
    }
    const input = document.getElementById('noteboardInput');
    const txt   = input?.value.trim();
    if (!txt) return;
    input.value = '';
    try {
        const r = await fetch(API.notes, {method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({action:'store', work_sys_id:WORK_SYS_ID, service_slug:slug, board:'noteboard', content:txt})});
        const j = await r.json();
        if (j.status==='success') await loadNoteboard(slug); else showToast('error', j.message);
    } catch { showToast('error','Network error'); }
}

// Note Board modal file
function nbFileSelected(input) {
    _nbFile = input.files[0] ?? null;
    if (_nbFile) {
        document.getElementById('nbFilePreviewName').textContent = _nbFile.name;
        document.getElementById('nbFilePreview').classList.remove('hidden');
    }
}
function nbClearFile() {
    _nbFile = null;
    document.getElementById('nbFileInput').value = '';
    document.getElementById('nbFilePreview').classList.add('hidden');
}

// Voice recording for Note Board
let _nbRecorder = null, _nbRecChunks = [], _nbRecording = false;
async function nbRecToggle() {
    const btn = document.getElementById('nb-rec-btn');
    if (_nbRecording) {
        _nbRecorder?.stop();
        return;
    }
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        _nbRecChunks = [];
        _nbRecorder  = new MediaRecorder(stream);
        _nbRecorder.ondataavailable = e => { if (e.data.size > 0) _nbRecChunks.push(e.data); };
        _nbRecorder.onstop = async () => {
            stream.getTracks().forEach(t => t.stop());
            _nbRecording = false;
            if (btn) { btn.style.background = '#f1f5f9'; btn.innerHTML = '<i class="fas fa-microphone" style="color:#64748b;font-size:.75rem;"></i>'; }
            const blob = new Blob(_nbRecChunks, { type: 'audio/webm' });
            const slug = activeServiceSlug; if (!slug) return;
            const fd = new FormData();
            fd.append('action', 'upload');
            fd.append('work_sys_id', WORK_SYS_ID);
            fd.append('service_slug', slug);
            fd.append('board', 'noteboard');
            fd.append('file', blob, 'voice_' + Date.now() + '.webm');
            try {
                const r = await fetch(API.notes, { method:'POST', body:fd });
                const j = await r.json();
                if (j.status === 'success') await loadNoteboard(slug);
                else showToast('error', j.message);
            } catch { showToast('error', 'Upload failed'); }
        };
        _nbRecorder.start();
        _nbRecording = true;
        if (btn) { btn.style.background = '#fee2e2'; btn.innerHTML = '<i class="fas fa-stop" style="color:#dc2626;font-size:.75rem;"></i>'; }
    } catch(e) {
        alert('Microphone access denied');
    }
}

async function nbSend() {
    const slug = activeServiceSlug; if (!slug) return;
    if (_nbFile) {
        const fd = new FormData();
        fd.append('action','upload'); fd.append('work_sys_id', WORK_SYS_ID);
        fd.append('service_slug', slug); fd.append('board', 'noteboard');
        fd.append('file', _nbFile);
        try {
            const r = await fetch(API.notes, {method:'POST', body:fd});
            const j = await r.json();
            if (j.status==='success') { nbClearFile(); await loadNoteboard(slug); }
            else showToast('error', j.message);
        } catch { showToast('error','Upload failed'); }
        return;
    }
    const txt = document.getElementById('nbTextInput').value.trim();
    if (!txt) return;
    document.getElementById('nbTextInput').value = ''; document.getElementById('nbTextInput').style.height = 'auto';
    try {
        const r = await fetch(API.notes, {method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({action:'store', work_sys_id:WORK_SYS_ID, service_slug:slug, board:'noteboard', content:txt})});
        const j = await r.json();
        if (j.status==='success') await loadNoteboard(slug); else showToast('error', j.message);
    } catch { showToast('error','Network error'); }
}

async function nbDel(id) {
    const note = _nbNotes.find(n => n.sys_id === id);
    const creator = note?.meta_data?.created_by_date?.user ?? note?.created_by ?? '';
    if (creator && creator !== CURRENT_USER) {
        showToast('error', 'Permission denied — only the creator can delete this note');
        return;
    }
    if (!confirm('Delete this note?')) return;
    const slug = activeServiceSlug;
    try {
        const r = await fetch(API.notes, {method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({action:'delete', sys_id:id})});
        const j = await r.json();
        if (j.status==='success') await loadNoteboard(slug); else showToast('error', j.message);
    } catch { showToast('error','Network error'); }
}

// ════════════════════════════════════════════════════════════
// MIND BOARD (tab inside service module — mindboard)
// ════════════════════════════════════════════════════════════
function openMindBoardModal() {
    document.getElementById('mindBoardModal').classList.remove('hidden');
    const label = document.getElementById('mbServiceLabel');
    if (label) label.textContent = activeServiceSlug ? '— ' + (SVC_INFO[activeServiceSlug]?.label ?? activeServiceSlug) : '';
    if (activeServiceSlug) mbLoadMindboard(activeServiceSlug);
    else renderMbChat();
}

async function mbLoadMindboard(slug) {
    try {
        const r = await fetch(`${API.notes}?action=list&work_sys_id=${encodeURIComponent(WORK_SYS_ID)}&service_slug=${encodeURIComponent(slug)}&board=mindboard`);
        const j = await r.json();
        _mbNotes = j.status === 'success' ? (j.data ?? []) : [];
    } catch { _mbNotes = []; }
    // Update mind board counts in sidebar
    document.getElementById('mbMsgCount').textContent  = _mbNotes.filter(n=>n.note_type==='text').length;
    document.getElementById('mbFileCount').textContent = _mbNotes.filter(n=>n.note_type!=='text').length;
    accRefresh('acc-mb');
    renderMbChat();
}

function renderMbChat() {
    const area = document.getElementById('mbChatArea'); if (!area) return;
    if (!_mbNotes.length) { area.innerHTML = '<div class="text-center py-8 text-gray-300 text-xs">No notes yet. Write something below.</div>'; return; }
    area.innerHTML = _mbNotes.map(n => _mbBubble(n)).join('');
    setTimeout(()=>{ area.scrollTop = area.scrollHeight; }, 100);
}

function _mbBubble(n) {
    const meta    = n.meta_data?.created_by_date;
    const time    = meta ? `${meta.user} · ${meta.date}` : '';
    const creator = meta?.user ?? n.created_by ?? '';
    const canDel  = !creator || creator === CURRENT_USER;
    const del     = canDel
        ? `<button onclick="mbDel('${n.sys_id}')" class="absolute -top-1.5 -right-1.5 w-4 h-4 bg-red-500 text-white rounded-full text-[9px] hidden items-center justify-center hover:bg-red-600 mb-del"><i class="fas fa-times"></i></button>`
        : '';
    if (n.note_type === 'text') return `
        <div class="flex flex-col items-start relative group mb-bubble-wrap">
            <div class="relative bg-gray-100 rounded-2xl rounded-bl-sm px-3 py-2 text-sm text-gray-800 max-w-[85%] whitespace-pre-wrap">${esc(n.content??'')}${del}</div>
            <span class="text-[10px] text-gray-400 mt-1 ml-1">${esc(time)}</span>
        </div>`;
    if (n.note_type === 'image') return `
        <div class="flex flex-col items-start relative group mb-bubble-wrap">
            <div class="relative bg-white border border-gray-200 rounded-xl p-1 max-w-[85%]">
                <img src="${esc(n.serve_url??'')}" class="rounded-lg max-h-52 object-contain cursor-pointer" onclick="window._previewOpen&&window._previewOpen(this.src)">${del}
            </div>
            <span class="text-[10px] text-gray-400 mt-1 ml-1">${esc(time)}</span>
        </div>`;
    if (n.note_type === 'audio') return `
        <div class="flex flex-col items-start relative group mb-bubble-wrap">
            <div class="relative bg-violet-50 border border-violet-100 rounded-xl px-3 py-2 max-w-[85%]">
                <audio controls src="${esc(n.serve_url??'')}" class="h-8 w-48"></audio>${del}
            </div>
            <span class="text-[10px] text-gray-400 mt-1 ml-1">${esc(time)}</span>
        </div>`;
    return `
        <div class="flex flex-col items-start relative group mb-bubble-wrap">
            <div class="relative bg-green-50 border border-green-100 rounded-xl px-3 py-2 flex items-center gap-2 max-w-[85%]">
                <i class="fas fa-file text-green-500 text-sm flex-shrink-0"></i>
                <a href="${esc(n.serve_url??'')}?dl=1" class="text-xs text-green-700 font-medium truncate max-w-[140px]" download>${esc(n.file_name??'file')}</a>${del}
            </div>
            <span class="text-[10px] text-gray-400 mt-1 ml-1">${esc(time)}</span>
        </div>`;
}

document.addEventListener('mouseover', e => {
    const mw = e.target.closest('.mb-bubble-wrap');
    document.querySelectorAll('.mb-del').forEach(b => b.style.display='none');
    if (mw) { const btn = mw.querySelector('.mb-del'); if(btn) btn.style.display='flex'; }
});

function mbFileSelected(input) {
    _mbFile = input.files[0] ?? null;
    if (_mbFile) {
        document.getElementById('mbFilePreview').classList.remove('hidden');
        document.getElementById('mbFilePreviewName').textContent = _mbFile.name;
    }
}
function mbClearFile() { _mbFile=null; document.getElementById('mbFilePreview').classList.add('hidden'); document.getElementById('mbFileInput').value=''; }

async function mbSend() {
    const txt  = document.getElementById('mbTextInput').value.trim();
    const slug = activeServiceSlug ?? 'general';
    if (_mbFile) {
        const fd = new FormData();
        fd.append('action','upload'); fd.append('work_sys_id',WORK_SYS_ID);
        fd.append('service_slug',slug); fd.append('board','mindboard');
        fd.append('file',_mbFile);
        try {
            const r=await fetch(API.notes,{method:'POST',body:fd});
            const j=await r.json();
            if(j.status==='success'){mbClearFile();await mbLoadMindboard(slug);}
            else showToast('error',j.message);
        } catch{showToast('error','Upload failed');}
        return;
    }
    if (!txt) return;
    document.getElementById('mbTextInput').value=''; document.getElementById('mbTextInput').style.height='auto';
    try {
        const r=await fetch(API.notes,{method:'POST',headers:{'Content-Type':'application/json'},
            body:JSON.stringify({action:'store',work_sys_id:WORK_SYS_ID,service_slug:slug,board:'mindboard',content:txt})});
        const j=await r.json();
        if(j.status==='success') await mbLoadMindboard(slug); else showToast('error',j.message);
    } catch{showToast('error','Network error');}
}

async function mbDel(id) {
    const note = _mbNotes.find(n => n.sys_id === id);
    const creator = note?.meta_data?.created_by_date?.user ?? note?.created_by ?? '';
    if (creator && creator !== CURRENT_USER) {
        showToast('error', 'Permission denied — only the creator can delete this note');
        return;
    }
    if(!confirm('Delete this note?')) return;
    const slug = activeServiceSlug ?? 'general';
    try {
        const r=await fetch(API.notes,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'delete',sys_id:id})});
        const j=await r.json();
        if(j.status==='success') await mbLoadMindboard(slug); else showToast('error',j.message);
    } catch{showToast('error','Network error');}
}

// ════════════════════════════════════════════════════════════
// TRAVELERS — exact from show-tasks.php (adapted for WORK_SYS_ID)
// ════════════════════════════════════════════════════════════
let _travData=[], _travLoaded=false;
let _linkedTravelers=[], _carouselIdx=0, _newTravelerExtracted=null, _ntFile=null, _ntDuplicateSysId=null;

async function _loadTravelers() {
    if(_travLoaded)return;
    try{const r=await fetch(API.travelers);const j=await r.json();_travData=j.travelers??[];_travLoaded=true;}catch{}
}

function travelerSearchFilter(q) {
    const dd = document.getElementById('travelerSearchDrop'); if (!dd) return;
    _loadTravelers().then(() => {
        const v = q.toLowerCase().trim();
        if (!v) { dd.classList.add('hidden'); return; }
        const list = _travData.filter(t => (t.name||'').toLowerCase().includes(v) ||
            (t.passport_no||'').toLowerCase().includes(v)).slice(0, 12);
        if (!list.length) {
            dd.innerHTML = `<li class="px-3 py-2 text-center text-gray-400 text-xs">No travelers found</li>`;
            dd.classList.remove('hidden'); return;
        }
        dd.innerHTML = list.map(t => `<li class="px-3 py-2 cursor-pointer hover:bg-teal-50 border-b last:border-b-0 flex items-center gap-2"
            onclick="travelerSearchSelect('${t.sys_id}','${(t.name||'').replace(/'/g,"\\'")}')">
            <div class="w-6 h-6 bg-teal-600 rounded-full text-white flex items-center justify-center text-xs font-bold">${(t.name?.[0]??'T').toUpperCase()}</div>
            <div><div class="font-medium text-gray-800">${escHtml(t.name??'')}</div>
            <div class="text-gray-400 font-mono text-[10px]">${t.sys_id} ${t.passport_no?'· '+t.passport_no:''}</div></div>
        </li>`).join('');
        dd.classList.remove('hidden');
    });
}

async function travelerSearchSelect(travelerSysId, name) {
    document.getElementById('travelerSearchInput').value = '';
    document.getElementById('travelerSearchDrop').classList.add('hidden');
    try {
        const r = await fetch(API.workTravelers, {method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({action:'link', work_sys_id:WORK_SYS_ID, traveler_sys_id:travelerSysId})});
        const j = await r.json();
        if (j.status === 'success') { showToast('success', `${name} linked!`); await loadLinkedTravelers(); }
        else showToast('error', j.message ?? 'Failed');
    } catch { showToast('error', 'Network error'); }
}

document.addEventListener('click', e => {
    if (!document.getElementById('travelerSearchInput')?.parentElement?.contains(e.target))
        document.getElementById('travelerSearchDrop')?.classList.add('hidden');
});

async function loadLinkedTravelers(){
    try{
        const r=await fetch(`${API.workTravelers}?action=list&work_sys_id=${encodeURIComponent(WORK_SYS_ID)}`);
        const j=await r.json();
        if(j.status==='success'){_linkedTravelers=j.data??[];renderLinkedTravelers(_linkedTravelers);}
    }catch{}
}

async function unlinkTraveler(travelerSysId,name){
    if(!confirm(`Remove ${name}?`))return;
    try{
        const r=await fetch(API.workTravelers,{method:'POST',headers:{'Content-Type':'application/json'},
            body:JSON.stringify({action:'unlink',work_sys_id:WORK_SYS_ID,traveler_sys_id:travelerSysId})});
        const j=await r.json();
        if(j.status==='success'){showToast('success',`${name} removed`);await loadLinkedTravelers();}
        else showToast('error',j.message??'Failed');
    }catch{showToast('error','Network error');}
}

function _parsePassport(t){
    try {
        const raw = JSON.parse(t.passport_info || 'null');
        if (!raw) return {};
        if (Array.isArray(raw)) {
            const bio = raw.find(p => p.page_type === 'bio_page');
            return bio?.bio_info ?? raw[0]?.bio_info ?? {};
        }
        return raw;
    } catch { return {}; }
}

function renderLinkedTravelers(travelers){
    const list=document.getElementById('linkedTravelersList');if(!list)return;
    const hasPassports=travelers.some(t=>t.passport_token);
    document.getElementById('btnPassportCarousel')?.classList.toggle('hidden',!travelers.length||!hasPassports);
    document.getElementById('btnTravelerTable')?.classList.toggle('hidden',!travelers.length);
    if(!travelers.length){list.innerHTML='<p class="text-xs text-gray-400 text-center py-2">No travelers linked</p>';return;}
    list.innerHTML=`<div class="overflow-x-auto mt-1"><table class="w-full text-xs"><thead><tr class="text-gray-400 border-b border-gray-100"><th class="pb-1 text-left font-medium">Name</th><th class="pb-1 text-left font-medium">PP No</th><th class="pb-1"></th></tr></thead><tbody class="divide-y divide-gray-50">${travelers.map(t=>{const p=_parsePassport(t);const pNo=p.passport_number||p.passport_no||t.passport_no||'—';const name=t.name||'—';return`<tr class="hover:bg-gray-50"><td class="py-1.5 pr-2 truncate max-w-[70px] font-medium text-gray-700">${escHtml(name)}</td><td class="py-1.5 pr-2 font-mono text-gray-500">${escHtml(pNo)}</td><td class="py-1.5 flex items-center gap-1"><a href="show-travelers.php?id=${t.sys_id}" target="_blank" class="text-teal-400 hover:text-teal-600"><i class="fas fa-arrow-up-right-from-square text-[10px]"></i></a><button onclick="unlinkTraveler('${t.sys_id}','${(name).replace(/'/g,"\\'")}')" class="text-red-300 hover:text-red-500 ml-1" title="Remove"><i class="fas fa-times text-[10px]"></i></button></td></tr>`;}).join('')}</tbody></table></div>`;
}

function openNewTravelerModal(){
    _ntFile=null; _newTravelerExtracted=null; _ntDuplicateSysId=null;
    document.getElementById('ntFileInput').value='';
    document.getElementById('ntFilePreview').classList.add('hidden');
    document.getElementById('ntProgress').classList.add('hidden');
    document.getElementById('ntExtracted').classList.add('hidden');
    document.getElementById('ntDuplicateBox').classList.add('hidden');
    document.getElementById('ntExtractBtn').classList.remove('hidden');
    document.getElementById('ntCreateBtn').classList.add('hidden');
    document.getElementById('newTravelerModal').classList.remove('hidden');
}

window.ntFileSelected = function(input) {
    if (!input.files[0]) return;
    _ntFile = input.files[0];
    document.getElementById('ntFileName').textContent = _ntFile.name;
    document.getElementById('ntFilePreview').classList.remove('hidden');
};

window.ntClearFile = function() {
    _ntFile = null;
    document.getElementById('ntFileInput').value = '';
    document.getElementById('ntFilePreview').classList.add('hidden');
    document.getElementById('ntExtracted').classList.add('hidden');
    document.getElementById('ntDuplicateBox').classList.add('hidden');
    document.getElementById('ntCreateBtn').classList.add('hidden');
    _newTravelerExtracted = null; _ntDuplicateSysId = null;
};

window.ntExtractAndCheck = async function() {
    if (!_ntFile) { showToast('error', 'Upload a passport scan first'); return; }
    const btn = document.getElementById('ntExtractBtn');
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5"></i>Extracting…';
    const prog = document.getElementById('ntProgress');
    prog.classList.remove('hidden');
    document.getElementById('ntProgressText').textContent = 'Extracting passport info…';
    const fd = new FormData(); fd.append('file', _ntFile); fd.append('document_type', 'passport');
    try {
        const res = await fetch(API.extractDocument, {method:'POST', body:fd});
        const j   = await res.json();
        if (!j.success) { showToast('error', j.message ?? 'Extraction failed'); return; }
        _newTravelerExtracted = j;
        const p = j.passport_info ?? j.data ?? {};
        const bio = p.bio_info ?? p;
        const fields = [
            ['Name', bio.full_name ?? p.full_name ?? ''],
            ['Given Name', bio.given_names ?? ''],
            ['Surname', bio.surname ?? ''],
            ['Passport No', bio.passport_number ?? p.document_number ?? ''],
            ['Expiry', bio.date_of_expiry ?? ''],
            ['DOB', bio.date_of_birth ?? p.date_of_birth ?? ''],
        ].filter(([,v]) => v);
        document.getElementById('ntExtracted').innerHTML = fields.map(([k,v]) =>
            `<div class="flex gap-2"><span class="text-gray-400 w-24 flex-shrink-0">${k}</span><span class="font-medium text-gray-700">${escHtml(String(v))}</span></div>`
        ).join('');
        document.getElementById('ntExtracted').classList.remove('hidden');
        document.getElementById('ntProgressText').textContent = 'Checking for duplicates…';
        const docNum = bio.passport_number ?? p.document_number ?? '';
        const fullName = bio.full_name ?? p.full_name ?? '';
        const dob = bio.date_of_birth ?? p.date_of_birth ?? '';
        const dupRes = await fetch(API.checkDuplicate, {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({full_name:fullName, document_number:docNum, document_type:'passport', date_of_birth:dob})
        });
        const dupJ = await dupRes.json();
        if (dupJ.has_duplicates) {
            const dup = dupJ.duplicates[0];
            _ntDuplicateSysId = dup.sys_id;
            document.getElementById('ntDuplicateInfo').innerHTML =
                `<div class="space-y-1">
                    <div><span class="text-gray-400">Name:</span> <span class="font-medium">${escHtml(dup.name??'')}</span></div>
                    <div><span class="text-gray-400">PP No:</span> <span class="font-mono">${escHtml(dup.document_number??'')}</span></div>
                    <div><span class="text-gray-400">DOB:</span> ${escHtml(dup.date_of_birth??'')}</div>
                    <div><span class="text-gray-400">ID:</span> <span class="font-mono text-[10px]">${escHtml(dup.sys_id??'')}</span></div>
                </div>`;
            document.getElementById('ntDuplicateBox').classList.remove('hidden');
            document.getElementById('ntCreateBtn').classList.add('hidden');
        } else {
            _ntDuplicateSysId = null;
            document.getElementById('ntDuplicateBox').classList.add('hidden');
            document.getElementById('ntCreateBtn').classList.remove('hidden');
        }
    } catch(e) {
        showToast('error', 'Network error'); console.error(e);
    } finally {
        prog.classList.add('hidden');
        btn.disabled = false; btn.innerHTML = '<i class="fas fa-wand-magic-sparkles mr-1.5"></i>Extract & Check';
    }
};

window.ntLinkExisting = async function() {
    if (!_ntDuplicateSysId) return;
    try {
        const r = await fetch(API.workTravelers, {method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({action:'link', work_sys_id:WORK_SYS_ID, traveler_sys_id:_ntDuplicateSysId})});
        const j = await r.json();
        if (j.status === 'success') {
            showToast('success', 'Traveler linked!');
            closeModal('newTravelerModal');
            _travLoaded = false;
            await loadLinkedTravelers();
        } else showToast('error', j.message ?? 'Failed');
    } catch { showToast('error', 'Network error'); }
};

window.ntCreate = async function() {
    if (!_newTravelerExtracted) { showToast('error', 'Extract data first'); return; }
    const btn = document.getElementById('ntCreateBtn');
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5"></i>Creating…';
    const p = _newTravelerExtracted.passport_info ?? _newTravelerExtracted.data ?? {};
    const bio = p.bio_info ?? p;
    const payload = {
        full_name:       bio.full_name ?? p.full_name ?? '',
        date_of_birth:   bio.date_of_birth ?? p.date_of_birth ?? null,
        document_type:   'passport',
        document_number: bio.passport_number ?? p.document_number ?? null,
        file_path:       _newTravelerExtracted.file_path ?? null,
        extracted_data:  _newTravelerExtracted.full_extracted_data ?? _newTravelerExtracted ?? null,
        work_sys_id:     WORK_SYS_ID,
        force_create:    false,
    };
    try {
        const res = await fetch(API.storeNewTraveler, {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload)});
        const j = await res.json();
        if (j.success && j.sys_id) {
            showToast('success', 'Traveler created & linked!');
            closeModal('newTravelerModal');
            _travLoaded = false;
            await _loadTravelers();
            await loadLinkedTravelers();
        } else showToast('error', j.message ?? 'Save failed');
    } catch { showToast('error', 'Network error'); }
    btn.disabled = false; btn.innerHTML = '<i class="fas fa-user-plus mr-1.5"></i>Create & Link';
};

// ── Passport Carousel ─────────────────────────────────────────
function openPassportCarousel(){_carouselIdx=0;document.getElementById('passportCarouselModal').classList.remove('hidden');_renderCarouselSlide();}
function _renderCarouselSlide(){
    const travelers=_linkedTravelers.filter(t=>t.passport_token);
    if(!travelers.length){document.getElementById('carouselSlides').innerHTML='<p class="text-gray-400 text-sm text-center">No passport images available</p>';return;}
    const t=travelers[_carouselIdx];
    document.getElementById('carouselName').textContent=t.name??'—';
    document.getElementById('carouselSlides').innerHTML=`<img src="${t.passport_token}" alt="${escHtml(t.name??'')}" class="max-h-[400px] max-w-full object-contain rounded-xl" onerror="this.parentElement.innerHTML='<p class=\\'text-gray-400 text-sm text-center\\'>Image not available</p>'">`;
    document.getElementById('carouselDots').innerHTML=travelers.map((_,i)=>`<button onclick="_cGo(${i})" class="w-2 h-2 rounded-full transition ${i===_carouselIdx?'bg-indigo-500':'bg-gray-300'}"></button>`).join('');
}
window._cGo=function(i){_carouselIdx=i;_renderCarouselSlide();};
function carouselPrev(){const t=_linkedTravelers.filter(x=>x.passport_token);_carouselIdx=(_carouselIdx-1+t.length)%t.length;_renderCarouselSlide();}
function carouselNext(){const t=_linkedTravelers.filter(x=>x.passport_token);_carouselIdx=(_carouselIdx+1)%t.length;_renderCarouselSlide();}

// ── Traveler Table Modal ──────────────────────────────────────
function openTravelerTableModal(){
    document.getElementById('travelerTableModal').classList.remove('hidden');
    const tbody=document.getElementById('travelerTableBody');
    const cell=(v)=>`<td class="px-3 py-2 cursor-pointer hover:bg-teal-50 transition" onclick="copyCell('${escHtml(String(v))}')" title="Click to copy">${escHtml(String(v))}</td>`;
    tbody.innerHTML=_linkedTravelers.map(t=>{
        const p=_parsePassport(t);
        const given=p.given_names||p.given_name||p.first_name||'—';
        const surname=p.surname||p.last_name||'—';
        const pNo=p.passport_number||p.passport_no||'—';
        const expiry=p.date_of_expiry||p.expiry_date||'—';
        const dob=p.date_of_birth||p.dob||'—';
        return`<tr class="hover:bg-gray-50">${cell(t.name||'—')}${cell(given)}${cell(surname)}${cell(pNo)}${cell(expiry)}${cell(dob)}<td class="px-3 py-2"><a href="show-travelers.php?id=${t.sys_id}" target="_blank" class="text-teal-500 hover:text-teal-700 text-xs"><i class="fas fa-arrow-up-right-from-square"></i></a></td></tr>`;
    }).join('');
}
function copyCell(text){navigator.clipboard.writeText(text).then(()=>{const t=document.getElementById('copyToast');t.classList.remove('hidden');setTimeout(()=>t.classList.add('hidden'),1500);});}

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
function escHtml(s){return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
function badgeHtml(status){const m={open:['badge-open','🟡 Open'],in_progress:['badge-in_progress','🔵 In Progress'],done:['badge-done','✅ Done'],cancelled:['badge-cancelled','❌ Cancelled']};const[cls,label]=m[status]??['',status];return`<span class="badge ${cls}">${label}</span>`;}
function fmtDate(s){if(!s)return'—';const m=s.match(/^(\d{2})-(\d{2})-(\d{4})/);if(m)return`${m[1]} ${['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][+m[2]-1]} ${m[3]}`;return s;}
function fmtDateShort(s){if(!s)return'';const m=s.match(/^(\d{4})-(\d{2})-(\d{2})/)||s.match(/^(\d{2})-(\d{2})-(\d{4})/);if(!m)return s;const months=['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];if(s.match(/^\d{4}/))return`${+m[3]} ${months[+m[2]-1]}`;return`${+m[1]} ${months[+m[2]-1]}`;}

function showToast(type,msg){
    const inner=document.getElementById('toastInner');
    document.getElementById('toastMsg').textContent=msg;
    inner.className=`flex items-center gap-3 px-5 py-3 rounded-xl shadow-lg text-white text-sm font-medium ${type==='success'?'bg-green-600':'bg-red-500'}`;
    document.getElementById('toastIcon').className=`fas ${type==='success'?'fa-check-circle':'fa-exclamation-circle'} text-lg`;
    document.getElementById('toast').classList.remove('hidden');
    setTimeout(()=>document.getElementById('toast').classList.add('hidden'),3500);
}

// ── Sidebar resize ────────────────────────────────────────────
(function() {
    const handle  = document.getElementById('sidebarResizeHandle');
    const sidebar = document.getElementById('rightSidebar');
    if (!handle || !sidebar) return;

    // Restore saved width
    const saved = localStorage.getItem('sw_sidebar_width');
    if (saved) sidebar.style.width = saved + 'px';

    let startX, startW;

    handle.addEventListener('mousedown', e => {
        startX = e.clientX;
        startW = sidebar.offsetWidth;
        handle.classList.add('dragging');
        document.body.style.userSelect = 'none';
        document.body.style.cursor = 'col-resize';

        function onMove(e) {
            const dx  = startX - e.clientX; // drag left = wider
            const newW = Math.min(480, Math.max(200, startW + dx));
            sidebar.style.width = newW + 'px';
        }
        function onUp() {
            localStorage.setItem('sw_sidebar_width', sidebar.offsetWidth);
            handle.classList.remove('dragging');
            document.body.style.userSelect = '';
            document.body.style.cursor = '';
            document.removeEventListener('mousemove', onMove);
            document.removeEventListener('mouseup', onUp);
        }
        document.addEventListener('mousemove', onMove);
        document.addEventListener('mouseup', onUp);
        e.preventDefault();
    });
})();

// Global — called by ww-air-ticket.js after task creation
window.reloadConfirmedTasks = async function() {
    try {
        const r = await fetch(API.getWork + '?id=' + encodeURIComponent(WORK_SYS_ID));
        const j = await r.json();
        if (j.status === 'success') {
            confirmedTasks = j.confirmed_tasks ?? [];
            renderConfirmedTasks();
        }
    } catch {}
};

loadWork();
loadDepts();
</script>
</body>
</html>