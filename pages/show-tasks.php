<?php
// FILE PATH: /pages/show-tasks.php
include_once('./authenticate.php');
$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) { $ip_port = "http://103.104.219.3:898/"; }
$taskSysId = $_GET['id'] ?? '';

$API = [
    'getTask'        => $ip_port . "api/tasks/get-task.php",
    'updateStatus'   => $ip_port . "api/tasks/update-status.php",
    'saveFinancial'  => $ip_port . "api/financial_entries/store.php",
    'taskFinEntries' => $ip_port . "api/financial_entries/task-fin-entries.php",
    'aiMindboard'    => $ip_port . "api/tasks/ai-mindboard.php",
    'notes'          => $ip_port . "api/tasks/notes.php",
    'assign'         => $ip_port . "api/tasks/assign.php",
    'employees'      => $ip_port . "api/employees/all-employees.php",
    'travelers'      => $ip_port . "api/travelers/all-travelers.php",
    'workTravelers'  => $ip_port . "api/works/travelers.php",
    'extractDocument'=> $ip_port . "api/travelers/extract-document.php",
    'storeNewTraveler'=> $ip_port . "api/travelers/store.php",
    'fileServe'      => $ip_port . "api/file/serve.php",
    'vendors'        => $ip_port . "api/vendors/all-vendors.php",
    'airTickets'     => $ip_port . "api/air-tickets/endpoints.php",
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task — TravHub</title>
    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png" sizes="16x16">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .sc{background:#fff;border-radius:12px;border:1px solid #f1f5f9;box-shadow:0 1px 3px rgba(0,0,0,.05);}
        .f-input{width:100%;padding:7px 11px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:.83rem;color:#1f2937;outline:none;transition:border .15s;background:#fff;}
        .f-input:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.07);}
        .kv-row{display:flex;align-items:flex-start;gap:8px;padding:6px 0;border-bottom:1px solid #f3f4f6;font-size:.8rem;}
        .kv-key{min-width:80px;color:#9ca3af;font-weight:600;flex-shrink:0;}

        /* Accordion */
        .acc-header{display:flex;align-items:center;justify-content:space-between;padding:11px 13px;cursor:pointer;user-select:none;transition:background .12s;}
        .acc-header:hover{background:#f9fafb;}
        .acc-body{overflow:hidden;max-height:0;opacity:0;transition:max-height .25s ease,opacity .18s;}
        .acc-body.open{opacity:1;}
        .acc-chevron{transition:transform .2s;}
        .acc-chevron.open{transform:rotate(180deg);}

        /* WhatsApp chat */
        #mbChatArea{flex:1;overflow-y:auto;padding:12px 14px;display:flex;flex-direction:column;gap:8px;}
        #mbChatArea::-webkit-scrollbar{width:4px;}
        #mbChatArea::-webkit-scrollbar-thumb{background:#e5e7eb;border-radius:4px;}
        .mb-bubble{max-width:82%;padding:8px 12px;border-radius:12px;font-size:.82rem;word-break:break-word;position:relative;}
        .mb-text{background:#f3f4f6;color:#1f2937;align-self:flex-start;border-bottom-left-radius:3px;}
        .mb-image{background:#fff;border:1px solid #e5e7eb;padding:4px;align-self:flex-start;border-radius:10px;max-width:240px;}
        .mb-image img{width:100%;max-height:180px;object-fit:contain;border-radius:7px;cursor:pointer;display:block;}
        .mb-audio{background:#f5f3ff;border:1px solid #ede9fe;align-self:flex-start;}
        .mb-file{background:#f0fdf4;border:1px solid #bbf7d0;align-self:flex-start;}
        .mb-time{font-size:.65rem;color:#9ca3af;margin-top:3px;}
        .mb-del{position:absolute;top:-6px;right:-6px;width:18px;height:18px;background:#ef4444;color:#fff;border-radius:50%;display:none;align-items:center;justify-content:center;cursor:pointer;font-size:.6rem;box-shadow:0 1px 3px rgba(0,0,0,.2);}
        .mb-bubble:hover .mb-del{display:flex;}
        #mbInputBar{border-top:1px solid #f1f5f9;padding:10px 12px;background:#fff;display:flex;align-items:flex-end;gap:8px;flex-shrink:0;}
        #mbTextInput{flex:1;resize:none;border:1.5px solid #e5e7eb;border-radius:20px;padding:8px 14px;font-size:.83rem;outline:none;max-height:100px;overflow-y:auto;transition:border .15s;}
        #mbTextInput:focus{border-color:#6366f1;}

        /* Right sidebar */
        #rightSidebar{overflow-y:auto;overflow-x:hidden;}
        #rightSidebar::-webkit-scrollbar{width:4px;}
        #rightSidebar::-webkit-scrollbar-thumb{background:#e5e7eb;border-radius:4px;}

        /* Modal */
        .modal-bg{background:rgba(0,0,0,.45);backdrop-filter:blur(3px);}
        #toast{position:fixed;bottom:24px;right:24px;z-index:9999;}
    </style>
</head>
<body class="bg-gray-50 font-sans">

<?php include '../elements/header.php'; ?>
<?php include '../elements/aside.php'; ?>
<?php include '../elements/preview-model.php'; ?>

<main id="mainContent" class="transition-all duration-300" style="padding-top:64px;padding-left:256px;">

    <!-- Loading -->
    <div id="loadingState" class="text-center py-24 text-gray-400">
        <i class="fas fa-spinner fa-spin text-3xl mb-3 block"></i>Loading task…
    </div>

    <!-- Page (hidden until loaded) — flex column full viewport -->
    <div id="pageContent" class="hidden flex flex-col" style="height:calc(100vh - 64px);">

        <!-- TOP BAR -->
        <div class="flex items-center gap-2 px-5 py-2.5 bg-white border-b border-gray-100 flex-shrink-0 flex-wrap">
            <a href="index-works.php" class="text-gray-400 hover:text-indigo-600 text-xs transition"><i class="fas fa-briefcase mr-1"></i>Works</a>
            <i class="fas fa-chevron-right text-gray-300 text-[10px]"></i>
            <a id="breadWorkLink" href="#" class="text-gray-400 hover:text-indigo-600 text-xs transition font-mono"></a>
            <i class="fas fa-chevron-right text-gray-300 text-[10px]"></i>
            <span id="breadTaskTitle" class="text-gray-800 font-semibold text-sm"></span>
            <div class="flex-1"></div>
            <!-- Status (clickable) -->
            <button onclick="openStatusModal()" id="statusBadgeBtn" class="transition hover:opacity-80"></button>
            <!-- Assign -->
            <button onclick="openAssignModal()"
                class="flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 border border-indigo-200 rounded-lg text-xs font-semibold transition">
                <i class="fas fa-user-check text-xs"></i>Assign
            </button>
            <!-- Back -->
            <a id="backToWorkBtn" href="index-works.php"
                class="flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-gray-50 text-gray-600 border border-gray-200 rounded-lg text-xs font-semibold transition">
                <i class="fas fa-arrow-left text-xs"></i>Back to Work
            </a>
        </div>

        <!-- BODY: left content + right sidebar -->
        <div class="flex flex-1 overflow-hidden">

            <!-- LEFT: service tab area -->
            <div class="flex-1 overflow-y-auto p-4" id="serviceTabArea">
                <div class="text-center py-10 text-gray-300 text-sm">
                    <i class="fas fa-spinner fa-spin text-xl mb-2 block"></i>Loading service view…
                </div>
            </div>

            <!-- RIGHT: accordion sidebar -->
            <div id="rightSidebar" class="w-72 flex-shrink-0 border-l border-gray-100 bg-white p-3 space-y-2"
                style="max-height:calc(100vh - 104px);">

                <!-- 1. Mind Board -->
                <div class="sc overflow-hidden">
                    <div class="acc-header rounded-xl" onclick="toggleAcc('acc-mb',this)">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 bg-indigo-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-brain text-indigo-500 text-xs"></i>
                            </div>
                            <span class="text-sm font-semibold text-gray-700">Mind Board</span>
                        </div>
                        <i class="fas fa-chevron-down text-gray-400 text-xs acc-chevron"></i>
                    </div>
                    <div id="acc-mb" class="acc-body">
                        <div class="px-3 pb-3 space-y-2">
                            <button onclick="openAiModal()"
                                class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold transition flex items-center justify-center gap-2">
                                <i class="fas fa-wand-magic-sparkles"></i>AI Generate Briefing
                            </button>
                            <!-- File + message count only -->
                            <div id="mbSidePreview" class="flex items-center gap-3 text-xs text-gray-400 py-1">
                                <div class="flex items-center gap-1">
                                    <i class="fas fa-comment text-blue-300 text-xs"></i>
                                    <span id="mbMsgCount">0</span> notes
                                </div>
                                <div class="flex items-center gap-1">
                                    <i class="fas fa-paperclip text-green-300 text-xs"></i>
                                    <span id="mbFileCount">0</span> files
                                </div>
                            </div>
                            <button onclick="openMindBoardModal()"
                                class="w-full py-1.5 text-indigo-500 hover:text-indigo-700 text-xs font-semibold text-center border border-indigo-100 rounded-lg hover:bg-indigo-50 transition">
                                Open Mind Board <i class="fas fa-arrow-right ml-1"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 2. Task Overview -->
                <div class="sc overflow-hidden">
                    <div class="acc-header rounded-xl" onclick="toggleAcc('acc-ov',this)">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-info-circle text-blue-500 text-xs"></i>
                            </div>
                            <span class="text-sm font-semibold text-gray-700">Task Overview</span>
                        </div>
                        <i class="fas fa-chevron-down text-gray-400 text-xs acc-chevron"></i>
                    </div>
                    <div id="acc-ov" class="acc-body">
                        <div class="px-3 pb-3">
                            <div class="kv-row"><span class="kv-key">Task ID</span><span class="font-mono text-indigo-500 text-xs" id="ov-taskid">—</span></div>
                            <div class="kv-row"><span class="kv-key">Work</span><span class="font-mono text-xs text-gray-700" id="ov-workid">—</span></div>
                            <div class="kv-row"><span class="kv-key">Service</span><span id="ov-service">—</span></div>
                            <div class="kv-row"><span class="kv-key">Status</span><span id="ov-status">—</span></div>
                            <div class="kv-row"><span class="kv-key">Client</span><span class="text-gray-700 text-xs" id="ov-client">—</span></div>
                            <div class="kv-row border-b-0"><span class="kv-key">Assigned</span><span class="text-gray-700 text-xs" id="ov-assigned">—</span></div>
                        </div>
                    </div>
                </div>

                <!-- 3. Instructions -->
                <div class="sc overflow-hidden">
                    <div class="acc-header rounded-xl" onclick="toggleAcc('acc-ins',this)">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-clipboard-list text-amber-500 text-xs"></i>
                            </div>
                            <span class="text-sm font-semibold text-gray-700">Instructions</span>
                        </div>
                        <i class="fas fa-chevron-down text-gray-400 text-xs acc-chevron"></i>
                    </div>
                    <div id="acc-ins" class="acc-body">
                        <div class="px-3 pb-3 space-y-2">
                            <div class="text-xs text-gray-600 bg-gray-50 rounded-lg p-2.5 leading-relaxed" id="instructionDisplay">—</div>
                            <div id="specialInsPanel" class="hidden space-y-1.5"></div>
                        </div>
                    </div>
                </div>

                <!-- 4. Travelers (always open, no accordion) -->
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
                            <button onclick="addTravelerRow()" class="text-xs text-teal-500 hover:text-teal-700 font-semibold px-2 py-1">
                                <i class="fas fa-plus mr-1"></i>Add
                            </button>
                        </div>
                    </div>
                    <div id="travelerRows" class="space-y-2 mb-1.5"></div>
                    <div id="linkedTravelersList"></div>
                </div>

            </div><!-- /rightSidebar -->
        </div>
    </div><!-- /pageContent -->
</main>

<!-- ══ MODALS ══════════════════════════════════════════════ -->

<!-- Status -->
<div id="statusModal" class="fixed inset-0 z-50 hidden modal-bg flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
        <div class="flex items-center justify-between p-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800 text-sm"><i class="fas fa-tag mr-2 text-indigo-500"></i>Change Status</h3>
            <button onclick="closeModal('statusModal')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-4 grid grid-cols-2 gap-2">
            <button onclick="changeStatus('open')"        class="py-2.5 rounded-xl bg-yellow-50 text-yellow-700 border border-yellow-200 text-sm font-medium hover:bg-yellow-100">🟡 Open</button>
            <button onclick="changeStatus('in_progress')" class="py-2.5 rounded-xl bg-blue-50 text-blue-700 border border-blue-200 text-sm font-medium hover:bg-blue-100">🔵 In Progress</button>
            <button onclick="changeStatus('done')"        class="py-2.5 rounded-xl bg-green-50 text-green-700 border border-green-200 text-sm font-medium hover:bg-green-100">✅ Done</button>
            <button onclick="changeStatus('on_hold')"     class="py-2.5 rounded-xl bg-purple-50 text-purple-700 border border-purple-200 text-sm font-medium hover:bg-purple-100">⏸ On Hold</button>
            <button onclick="changeStatus('cancelled')"   class="col-span-2 py-2.5 rounded-xl bg-red-50 text-red-700 border border-red-200 text-sm font-medium hover:bg-red-100">❌ Cancelled</button>
        </div>
    </div>
</div>

<!-- Assign -->
<div id="assignModal" class="fixed inset-0 z-50 hidden modal-bg flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
        <div class="flex items-center justify-between p-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800 text-sm"><i class="fas fa-user-check mr-2 text-indigo-500"></i>Assignment</h3>
            <button onclick="closeModal('assignModal')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-4 space-y-3">
            <div>
                <label class="text-xs font-bold text-gray-400 uppercase block mb-1">Assigned To</label>
                <div class="relative" id="assignedToWrap">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-300 text-xs pointer-events-none"></i>
                    <input type="text" id="assignedToInput" placeholder="Search employee…" class="f-input pl-8" autocomplete="off"
                        oninput="filterEmp(this.value)" onfocus="filterEmp(this.value)">
                    <ul id="assignedToDrop" class="absolute w-full bg-white border border-gray-200 rounded-lg mt-1 max-h-44 overflow-auto shadow-xl hidden z-50"></ul>
                </div>
                <input type="hidden" id="assignedToVal">
            </div>
            <div>
                <label class="text-xs font-bold text-gray-400 uppercase block mb-1">Holding On</label>
                <input id="holdingOn" class="f-input" placeholder="e.g. Waiting for passport">
            </div>
            <button onclick="saveAssignment()"
                class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold transition">
                <i class="fas fa-save mr-1.5"></i>Save Assignment
            </button>
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

        <!-- AI Planning Board (hidden by default, shown after generate) -->
        <div id="aiPlanningBoard" class="hidden flex-shrink-0 border-b border-indigo-100 bg-indigo-50 px-4 py-3 text-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider"><i class="fas fa-brain mr-1.5"></i>AI Planning Briefing</span>
                <button onclick="document.getElementById('aiPlanningBoard').classList.add('hidden')" class="text-indigo-300 hover:text-indigo-500 text-xs"><i class="fas fa-times"></i></button>
            </div>
            <div id="aiPlanningContent" class="text-gray-700 text-xs leading-relaxed max-h-32 overflow-y-auto"></div>
        </div>

        <!-- Chat area — newest at bottom (flex-col, scroll to bottom) -->
        <div id="mbChatArea" class="flex-1 overflow-y-auto px-4 py-3 flex flex-col gap-2">
            <div class="text-center py-6 text-gray-300 text-xs"><i class="fas fa-spinner fa-spin"></i></div>
        </div>

        <!-- Input bar — fixed at bottom of modal -->
        <div id="mbInputBar" class="flex-shrink-0 border-t border-gray-100 bg-white p-3">
            <!-- File preview chip -->
            <div id="mbFilePreview" class="hidden mb-2 bg-indigo-50 rounded-lg px-3 py-1.5 text-xs text-indigo-600 flex items-center gap-2">
                <i class="fas fa-paperclip flex-shrink-0"></i>
                <span id="mbFilePreviewName" class="flex-1 truncate"></span>
                <button onclick="mbClearFile()" class="text-red-400 hover:text-red-600 flex-shrink-0"><i class="fas fa-times"></i></button>
            </div>
            <div class="flex items-end gap-2">
                <!-- Attach file -->
                <label class="w-9 h-9 bg-gray-100 hover:bg-gray-200 rounded-full flex items-center justify-center cursor-pointer flex-shrink-0 transition" title="Attach file">
                    <i class="fas fa-paperclip text-gray-500 text-sm"></i>
                    <input type="file" id="mbFileInput" class="hidden" onchange="mbFileSelected(this)">
                </label>
                <!-- Text input -->
                <textarea id="mbTextInput" rows="1" placeholder="Write a note… (Enter to send, Shift+Enter for new line)"
                    onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();mbSend();}"
                    oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,100)+'px'"
                    style="flex:1;resize:none;border:1.5px solid #e5e7eb;border-radius:20px;padding:8px 14px;font-size:.83rem;outline:none;max-height:100px;overflow-y:auto;transition:border .15s;"></textarea>
                <!-- Send -->
                <button onclick="mbSend()"
                    class="w-9 h-9 bg-indigo-600 hover:bg-indigo-700 rounded-full flex items-center justify-center flex-shrink-0 transition">
                    <i class="fas fa-paper-plane text-white text-sm"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- AI Modal -->
<div id="aiModal" class="fixed inset-0 z-50 hidden modal-bg flex items-center justify-center p-4">
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

<!-- ── NEW TRAVELER MODAL ───────────────────────────────────── -->
<div id="newTravelerModal" class="fixed inset-0 z-[70] hidden modal-bg flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between p-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800 text-sm"><i class="fas fa-user-plus mr-2 text-teal-500"></i>New Traveler</h3>
            <button onclick="closeModal('newTravelerModal')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-4 space-y-4">
            <p class="text-xs text-gray-500 bg-gray-50 rounded-lg px-3 py-2">
                Upload a passport scan — AI will extract the traveler's information automatically.
            </p>
            <!-- Upload area -->
            <label class="flex flex-col items-center gap-2 px-4 py-6 border-2 border-dashed border-teal-300 rounded-xl cursor-pointer hover:border-teal-400 hover:bg-teal-50 transition-colors">
                <i class="fas fa-id-card text-teal-400 text-3xl"></i>
                <span class="text-sm text-gray-600 font-medium">Upload Passport Scan</span>
                <span class="text-xs text-gray-400">JPG, PNG, PDF, WebP accepted</span>
                <input type="file" id="newTravelerFile" accept=".jpg,.jpeg,.png,.webp,.pdf" class="hidden"
                    onchange="previewNewTravelerFile(this)">
            </label>
            <div id="newTravelerFilePreview" class="hidden text-xs text-teal-600 bg-teal-50 rounded-lg px-3 py-2 flex items-center gap-2">
                <i class="fas fa-file"></i>
                <span id="newTravelerFileName"></span>
            </div>
            <!-- Extract progress -->
            <div id="newTravelerProgress" class="hidden text-center py-2">
                <i class="fas fa-spinner fa-spin text-teal-500 mr-2"></i>
                <span class="text-xs text-teal-600">Extracting traveler info...</span>
            </div>
            <!-- Extracted preview -->
            <div id="newTravelerExtracted" class="hidden space-y-2">
                <p class="text-xs font-bold text-gray-500 uppercase">Extracted Info</p>
                <div id="newTravelerExtractedData" class="bg-gray-50 rounded-lg p-3 text-xs space-y-1"></div>
            </div>
            <div class="flex gap-2">
                <button onclick="extractAndCreateTraveler()" id="newTravelerExtractBtn"
                    class="flex-1 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-sm font-semibold transition">
                    <i class="fas fa-wand-magic-sparkles mr-1.5"></i>Extract & Create
                </button>
                <button id="newTravelerSaveBtn" onclick="saveNewTraveler()" class="hidden flex-1 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-xl text-sm font-semibold transition">
                    <i class="fas fa-save mr-1.5"></i>Save & Link
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ── PASSPORT CAROUSEL MODAL ──────────────────────────────── -->
<div id="passportCarouselModal" class="fixed inset-0 z-[70] hidden modal-bg flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl">
        <div class="flex items-center justify-between p-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800 text-sm"><i class="fas fa-id-card mr-2 text-indigo-500"></i>Passports</h3>
            <button onclick="closeModal('passportCarouselModal')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-4">
            <!-- Carousel -->
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
            <!-- Dots + name -->
            <div class="mt-3 text-center">
                <p id="carouselName" class="text-sm font-semibold text-gray-700 mb-2"></p>
                <div id="carouselDots" class="flex justify-center gap-1.5"></div>
            </div>
        </div>
    </div>
</div>

<!-- ── TRAVELER TABLE MODAL ─────────────────────────────────── -->
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

<!-- Toast copy notification -->
<div id="copyToast" class="fixed bottom-16 right-6 z-[9999] hidden">
    <div class="bg-gray-800 text-white text-xs px-3 py-1.5 rounded-lg shadow-lg flex items-center gap-2">
        <i class="fas fa-check text-green-400"></i> Copied!
    </div>
</div>

<!-- Special Instructions popup -->
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

<!-- Toast -->
<div id="toast" class="hidden">
    <div id="toastInner" class="flex items-center gap-3 px-5 py-3 rounded-xl shadow-lg text-white text-sm font-medium">
        <i id="toastIcon" class="fas fa-check-circle text-lg"></i>
        <span id="toastMsg"></span>
    </div>
</div>

<?php include '../elements/floating-menus.php'; ?>
<script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>
<script>
// ════════════════════════════════════════════════════════════
// CORE
// ════════════════════════════════════════════════════════════
const TASK_SYS_ID = "<?php echo htmlspecialchars($taskSysId); ?>";
const API = <?php echo json_encode($API); ?>;
let taskData = null, workData = null, _mbNotes = [], _mbFile = null;

// ── Load ─────────────────────────────────────────────────────
async function loadTask() {
    if (!TASK_SYS_ID) { document.getElementById('loadingState').innerHTML='<p class="text-red-400 text-center text-sm">No task ID.</p>'; return; }
    try {
        const res  = await fetch(API.getTask + '?id=' + TASK_SYS_ID);
        const json = await res.json();
        if (json.status !== 'success') throw new Error(json.message);
        taskData = json.task; workData = json.work;
        renderCommonUI();
        loadServiceTab(taskData.service_slug ?? null, json.at_data ?? null);
        mbLoadNotes();
    } catch(e) {
        document.getElementById('loadingState').innerHTML = `<p class="text-red-400 text-center text-sm py-10">${escHtml(e.message)}</p>`;
    }
}

// ── Common UI ─────────────────────────────────────────────────
function renderCommonUI() {
    document.getElementById('loadingState').classList.add('hidden');
    document.getElementById('pageContent').classList.remove('hidden');
    const ci = workData?.client_info ?? {};
    // Top bar
    document.getElementById('breadWorkLink').textContent  = taskData.work_sys_id;
    document.getElementById('breadWorkLink').href         = 'show-works.php?id=' + taskData.work_sys_id;
    document.getElementById('breadTaskTitle').textContent = taskData.workname ?? TASK_SYS_ID;
    document.getElementById('backToWorkBtn').href         = 'show-works.php?id=' + taskData.work_sys_id;
    document.getElementById('statusBadgeBtn').innerHTML   = badgeHtml(taskData.status);
    // Overview
    document.getElementById('ov-taskid').textContent  = TASK_SYS_ID;
    document.getElementById('ov-workid').textContent  = taskData.work_sys_id;
    document.getElementById('ov-service').innerHTML   = serviceLabel(taskData.service_slug);
    document.getElementById('ov-status').innerHTML    = badgeHtml(taskData.status);
    document.getElementById('ov-client').textContent  = taskData.client_name ?? ci.name ?? '—';
    document.getElementById('ov-assigned').textContent= taskData.assigned_to ?? '—';
    // Assignment prefill
    if (taskData.assigned_to) { document.getElementById('assignedToInput').value = taskData.assigned_to; document.getElementById('assignedToVal').value = taskData.assigned_to; }
    document.getElementById('holdingOn').value = taskData.holding_on ? (typeof taskData.holding_on==='object'?JSON.stringify(taskData.holding_on):taskData.holding_on) : '';
    // Instructions
    document.getElementById('instructionDisplay').textContent = taskData.instruction ?? '—';
    _renderSpecialIns(taskData.special_ins);
    // Travelers — load from API (work level)
    loadLinkedTravelers();
}

// ── Service tab ───────────────────────────────────────────────
function loadServiceTab(slug, atData) {
    const area = document.getElementById('serviceTabArea');
    switch (slug) {
        case 'air_ticket': _loadAirTicketTab(atData); break;
        default:
            area.innerHTML = `<div class="sc p-6 text-center text-gray-400 text-sm"><i class="fas fa-question-circle text-3xl mb-3 block opacity-30"></i>Service <b>${escHtml(slug??'unknown')}</b> — no dedicated view yet.</div>`;
    }
}

function _loadAirTicketTab(atData) {
    document.getElementById('serviceTabArea').innerHTML = '<div id="at-tab-mount"></div>';

    // আগের script tag থাকলে remove করো — duplicate IIFE আটকাতে
    const old = document.getElementById('at-script-tag');
    if (old) old.remove();

    const s = document.createElement('script');
    s.id  = 'at-script-tag';
    s.src = 'task-tabs/tt-air-ticket.js?v=' + Date.now();
    s.onload = () => { if (typeof initAirTicketTab==='function') initAirTicketTab({ taskSysId:TASK_SYS_ID, workSysId:taskData?.work_sys_id??'', leadSysId:workData?.lead_sys_id??null, clientName:taskData?.client_name??'', workname:taskData?.workname??'', atData, api:API }); };
    s.onerror = () => { document.getElementById('serviceTabArea').innerHTML=`<div class="sc p-6 text-center text-red-400 text-sm">Air ticket module load failed.</div>`; };
    document.body.appendChild(s);
}

// ════════════════════════════════════════════════════════════
// ACCORDION
// ════════════════════════════════════════════════════════════
function toggleAcc(id, header) {
    const body = document.getElementById(id), chev = header?.querySelector('.acc-chevron');
    const open = body.classList.contains('open');
    if (open) { body.style.maxHeight='0'; body.classList.remove('open'); chev?.classList.remove('open'); }
    else       { body.style.maxHeight=body.scrollHeight+200+'px'; body.classList.add('open'); chev?.classList.add('open'); }
}
function accRefresh(id) { const b=document.getElementById(id); if (b?.classList.contains('open')) b.style.maxHeight=b.scrollHeight+200+'px'; }

// ════════════════════════════════════════════════════════════
// STATUS
// ════════════════════════════════════════════════════════════
function openStatusModal() { document.getElementById('statusModal').classList.remove('hidden'); }
async function changeStatus(s) {
    closeModal('statusModal');
    try {
        const res=await fetch(API.updateStatus,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({sys_id:TASK_SYS_ID,status:s})});
        const j=await res.json();
        if(j.status==='success'){showToast('success','Status updated!');document.getElementById('statusBadgeBtn').innerHTML=badgeHtml(s);document.getElementById('ov-status').innerHTML=badgeHtml(s);}
        else showToast('error',j.message);
    } catch { showToast('error','Network error'); }
}

// ════════════════════════════════════════════════════════════
// ASSIGN
// ════════════════════════════════════════════════════════════
function openAssignModal() { document.getElementById('assignModal').classList.remove('hidden'); filterEmp(''); }
let _empData=[], _empLoaded=false;
async function _loadEmployees() { if(_empLoaded)return; try{const r=await fetch(API.employees);const j=await r.json();_empData=j.employees??[];_empLoaded=true;}catch{} }
function filterEmp(q) {
    const dd=document.getElementById('assignedToDrop'); if(!dd)return;
    _loadEmployees().then(()=>{
        const v=q.toLowerCase().trim(), list=v?_empData.filter(e=>(e.name||'').toLowerCase().includes(v)):_empData.slice(0,15);
        if(!list.length){dd.innerHTML=`<li class="px-4 py-3 text-center text-gray-400 text-xs">No employees</li>`;dd.classList.remove('hidden');return;}
        dd.innerHTML=list.map(e=>{const n=_empName(e);return`<li class="px-3 py-2 cursor-pointer hover:bg-purple-50 border-b last:border-b-0 flex items-center gap-2" onclick="_selEmp('${e.sys_id}','${n.replace(/'/g,"\\'")}')"><div class="w-7 h-7 bg-purple-600 rounded-full text-white flex items-center justify-center text-xs font-bold">${n[0]?.toUpperCase()??'E'}</div><div><div class="text-sm font-medium text-gray-800">${escHtml(n)}</div><div class="text-xs text-gray-400 font-mono">${e.sys_id}</div></div></li>`;}).join('');
        dd.classList.remove('hidden');
    });
}
function _empName(e){try{if(e.name?.startsWith('{'))return JSON.parse(e.name).primary??e.name;return e.name??'Unknown';}catch{return e.name??'Unknown';}}
function _selEmp(id,name){document.getElementById('assignedToInput').value=`${id} | ${name}`;document.getElementById('assignedToVal').value=id;document.getElementById('assignedToDrop').classList.add('hidden');}
document.addEventListener('click',e=>{if(!document.getElementById('assignedToWrap')?.contains(e.target))document.getElementById('assignedToDrop')?.classList.add('hidden');});
async function saveAssignment() {
    const a=document.getElementById('assignedToVal').value.trim()||document.getElementById('assignedToInput').value.trim();
    const h=document.getElementById('holdingOn').value.trim();
    try{const r=await fetch(API.assign,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({sys_id:TASK_SYS_ID,assigned_to:a||null,holding_on:h||null})});const j=await r.json();
    if(j.status==='success'){showToast('success','Saved!');document.getElementById('ov-assigned').textContent=a||'—';closeModal('assignModal');}else showToast('error',j.message);}
    catch{showToast('error','Network error');}
}

// ════════════════════════════════════════════════════════════
// MIND BOARD
// ════════════════════════════════════════════════════════════
function openMindBoardModal(){
    document.getElementById('mindBoardModal').classList.remove('hidden');
    mbRenderChat();
}
async function mbLoadNotes(){
    try{const r=await fetch(`${API.notes}?action=list&task_sys_id=${encodeURIComponent(TASK_SYS_ID)}`);const j=await r.json();_mbNotes=j.status==='success'?(j.data??[]):[];}catch{_mbNotes=[];}
    mbRenderSidePreview();
    if(!document.getElementById('mindBoardModal').classList.contains('hidden'))mbRenderChat();
}
function mbRenderSidePreview(){
    const msgCount  = _mbNotes.filter(n=>n.note_type==='text').length;
    const fileCount = _mbNotes.filter(n=>n.note_type!=='text').length;
    const mc = document.getElementById('mbMsgCount');
    const fc = document.getElementById('mbFileCount');
    if (mc) mc.textContent = msgCount;
    if (fc) fc.textContent = fileCount;
    accRefresh('acc-mb');
}
function mbRenderChat(){
    const area=document.getElementById('mbChatArea'); if(!area)return;
    if(!_mbNotes.length){area.innerHTML='<div class="text-center py-8 text-gray-300 text-xs">No notes yet. Write something below.</div>';return;}
    // Newest at bottom — normal flex-col order (not reversed)
    area.innerHTML=_mbNotes.map(n=>_mbBubble(n)).join('');
    // Auto-scroll to bottom
    setTimeout(()=>{ area.scrollTop = area.scrollHeight; }, 50);
}
function _mbBubble(n){
    const d=n.meta_data?.created_by_date?.date??'';
    const del=`<div class="mb-del" onclick="mbDel('${n.sys_id}')"><i class="fas fa-times"></i></div>`;
    if(n.note_type==='text')return`<div class="mb-bubble mb-text">${del}<div class="whitespace-pre-line">${escHtml(n.content??'')}</div><div class="mb-time">${escHtml(d)}</div></div>`;
    if(n.note_type==='image')return`<div class="mb-bubble mb-image">${del}<img src="${n.file_url}" alt="${escHtml(n.file_name??'')}" onclick="mbImg('${n.file_url}')">${n.content?`<div class="text-xs text-gray-500 mt-1 px-1">${escHtml(n.content)}</div>`:''}<div class="mb-time px-1">${escHtml(d)}</div></div>`;
    if(n.note_type==='audio')return`<div class="mb-bubble mb-audio">${del}<div class="flex items-center gap-2 mb-1"><i class="fas fa-microphone text-purple-400 text-xs"></i><span class="text-xs">${escHtml(n.file_name??'')}</span></div><audio controls class="w-full h-8" src="${n.file_url}"></audio><div class="mb-time">${escHtml(d)}</div></div>`;
    if(n.note_type==='video')return`<div class="mb-bubble mb-audio">${del}<video controls style="max-height:160px" class="w-full rounded-lg mb-1" src="${n.file_url}"></video><div class="mb-time">${escHtml(d)}</div></div>`;
    return`<div class="mb-bubble mb-file">${del}<i class="fas fa-paperclip text-green-500 mr-2"></i><a href="${n.file_url}" target="_blank" download class="text-green-700 hover:underline text-sm truncate">${escHtml(n.file_name??'')}</a><div class="mb-time mt-1">${escHtml(d)}</div></div>`;
}
window.mbImg=function(url){const o=document.createElement('div');o.style.cssText='position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:9999;display:flex;align-items:center;justify-content:center;cursor:zoom-out;';o.innerHTML=`<img src="${url}" style="max-width:90vw;max-height:90vh;border-radius:8px;">`;o.onclick=()=>o.remove();document.body.appendChild(o);};
window.mbFileSelected=function(i){if(!i.files[0])return;_mbFile=i.files[0];document.getElementById('mbFilePreview').classList.remove('hidden');document.getElementById('mbFilePreviewName').textContent=_mbFile.name;};
window.mbClearFile=function(){_mbFile=null;document.getElementById('mbFilePreview').classList.add('hidden');document.getElementById('mbFileInput').value='';};
window.mbSend=async function(){
    const txt=document.getElementById('mbTextInput').value.trim();
    if(!txt&&!_mbFile)return;
    if(_mbFile){
        const fd=new FormData();fd.append('action','upload');fd.append('task_sys_id',TASK_SYS_ID);fd.append('work_sys_id',taskData?.work_sys_id??'');fd.append('content',txt);fd.append('file',_mbFile);
        try{const r=await fetch(API.notes,{method:'POST',body:fd});const j=await r.json();if(j.status==='success'){document.getElementById('mbTextInput').value='';document.getElementById('mbTextInput').style.height='auto';mbClearFile();await mbLoadNotes();}else showToast('error',j.message);}
        catch{showToast('error','Upload failed');}
    }else{
        try{const r=await fetch(API.notes,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'store',task_sys_id:TASK_SYS_ID,work_sys_id:taskData?.work_sys_id??'',content:txt})});const j=await r.json();if(j.status==='success'){document.getElementById('mbTextInput').value='';document.getElementById('mbTextInput').style.height='auto';await mbLoadNotes();}else showToast('error',j.message);}
        catch{showToast('error','Network error');}
    }
};
window.mbDel=async function(id){if(!confirm('Delete?'))return;try{const r=await fetch(API.notes,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'delete',sys_id:id})});const j=await r.json();if(j.status==='success')await mbLoadNotes();else showToast('error',j.message);}catch{showToast('error','Network error');}};

// ════════════════════════════════════════════════════════════
// AI
// ════════════════════════════════════════════════════════════
function openAiModal(){document.getElementById('aiModal').classList.remove('hidden');}
async function aiGenerate(){
    const btn    = document.getElementById('aiGenBtn');
    const status = document.getElementById('aiGenStatus');
    btn.disabled = true;
    btn.innerHTML= '<i class="fas fa-spinner fa-spin mr-1.5"></i>Generating…';
    if (status) status.classList.remove('hidden');
    try {
        const r=await fetch(API.aiMindboard,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({task_sys_id:TASK_SYS_ID})});
        const j=await r.json();
        const board   = document.getElementById('aiPlanningBoard');
        const content = document.getElementById('aiPlanningContent');
        if (board && content) {
            content.innerHTML = j.status==='success' ? j.html : `<p class="text-red-400">${escHtml(j.message??'Failed')}</p>`;
            board.classList.remove('hidden');
        }
        closeModal('aiModal');
        // Open mind board modal to show result
        openMindBoardModal();
    } catch {
        const content = document.getElementById('aiPlanningContent');
        if (content) content.innerHTML = '<p class="text-red-400">Network error</p>';
    }
    btn.disabled = false;
    btn.innerHTML= '<i class="fas fa-wand-magic-sparkles mr-1.5"></i>Generate Briefing';
    if (status) status.classList.add('hidden');
}

// ════════════════════════════════════════════════════════════
// ════════════════════════════════════════════════════════════
// TRAVELERS
// ════════════════════════════════════════════════════════════
let _travData=[], _travLoaded=false, _travRows=[];
let _linkedTravelers=[], _carouselIdx=0, _newTravelerExtracted=null;

async function _loadTravelers() {
    if(_travLoaded)return;
    try{const r=await fetch(API.travelers);const j=await r.json();_travData=j.travelers??[];_travLoaded=true;}catch{}
}

function addTravelerRow() {
    _loadTravelers();
    const idx=_travRows.length; _travRows.push(null);
    const div=document.createElement('div'); div.className='space-y-1'; div.id=`tR${idx}`;
    div.innerHTML=`<div class="flex gap-1.5 items-center"><div class="relative flex-1"><input type="text" id="tI${idx}" placeholder="Search traveler…" class="f-input text-xs" autocomplete="off" oninput="fT(${idx},this.value)" onfocus="fT(${idx},this.value)"><ul id="tD${idx}" class="absolute w-full bg-white border border-gray-200 rounded-lg mt-1 max-h-44 overflow-auto shadow-xl hidden z-50 text-xs"></ul></div><button onclick="cancelTravelerRow(${idx})" class="w-7 h-7 bg-red-50 hover:bg-red-100 border border-red-200 text-red-400 rounded-lg flex items-center justify-center text-xs flex-shrink-0"><i class="fas fa-times"></i></button></div><div id="tNew${idx}" class="hidden"><button onclick="openNewTravelerModal()" class="w-full py-1.5 text-xs text-teal-600 border border-dashed border-teal-300 rounded-lg hover:bg-teal-50 transition"><i class="fas fa-user-plus mr-1"></i>New Traveler</button></div>`;
    document.getElementById('travelerRows').appendChild(div);
}

function cancelTravelerRow(idx){document.getElementById(`tR${idx}`)?.remove();_travRows[idx]=null;}

function fT(idx,q){
    const dd=document.getElementById(`tD${idx}`);if(!dd)return;
    _loadTravelers().then(()=>{
        const v=q.toLowerCase().trim(),list=v?_travData.filter(t=>(t.name||'').toLowerCase().includes(v)):_travData.slice(0,12);
        const newBtn=document.getElementById(`tNew${idx}`);
        if(newBtn)newBtn.classList.toggle('hidden',!v||list.length>0);
        if(!list.length){dd.innerHTML=`<li class="px-3 py-2 text-center text-gray-400 text-xs">No travelers found</li>`;dd.classList.remove('hidden');return;}
        dd.innerHTML=list.map(t=>`<li class="px-3 py-2 cursor-pointer hover:bg-teal-50 border-b last:border-b-0 flex items-center gap-2" onclick="sT(${idx},'${t.sys_id}','${(t.name||'').replace(/'/g,"\\'")}')"><div class="w-6 h-6 bg-teal-600 rounded-full text-white flex items-center justify-center text-xs font-bold">${(t.name?.[0]??'T').toUpperCase()}</div><div><div class="font-medium text-gray-800">${escHtml(t.name??'')}</div><div class="text-gray-400 font-mono text-[10px]">${t.sys_id}</div></div></li>`).join('');
        dd.classList.remove('hidden');
    });
}

async function sT(idx,travelerSysId,name){
    document.getElementById(`tI${idx}`).value=`${travelerSysId} | ${name}`;
    document.getElementById(`tD${idx}`).classList.add('hidden');
    document.getElementById(`tNew${idx}`)?.classList.add('hidden');
    _travRows[idx]=travelerSysId;
    const workSysId=taskData?.work_sys_id??'';
    if(!workSysId){showToast('error','Work ID not found');return;}
    try{
        const r=await fetch(API.workTravelers,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'link',work_sys_id:workSysId,traveler_sys_id:travelerSysId})});
        const j=await r.json();
        if(j.status==='success'){showToast('success',`${name} linked!`);document.getElementById(`tR${idx}`)?.remove();_travRows[idx]=null;await loadLinkedTravelers();}
        else showToast('error',j.message??'Failed');
    }catch{showToast('error','Network error');}
}

async function loadLinkedTravelers(){
    const workSysId=taskData?.work_sys_id??'';if(!workSysId)return;
    try{
        const r=await fetch(`${API.workTravelers}?action=list&work_sys_id=${encodeURIComponent(workSysId)}`);
        const j=await r.json();
        if(j.status==='success'){_linkedTravelers=j.data??[];renderLinkedTravelers(_linkedTravelers);}
    }catch{}
}

async function unlinkTraveler(travelerSysId,name){
    if(!confirm(`Remove ${name}?`))return;
    const workSysId=taskData?.work_sys_id??'';
    try{
        const r=await fetch(API.workTravelers,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'unlink',work_sys_id:workSysId,traveler_sys_id:travelerSysId})});
        const j=await r.json();
        if(j.status==='success'){showToast('success',`${name} removed`);await loadLinkedTravelers();}
        else showToast('error',j.message??'Failed');
    }catch{showToast('error','Network error');}
}

function _parsePassport(t){
    try {
        const raw = JSON.parse(t.passport_info || 'null');
        if (!raw) return {};
        // Array format: [{page_type, bio_info, ...}]
        if (Array.isArray(raw)) {
            const bio = raw.find(p => p.page_type === 'bio_page');
            return bio?.bio_info ?? raw[0]?.bio_info ?? {};
        }
        // Object format (legacy)
        return raw;
    } catch { return {}; }
}

function renderLinkedTravelers(travelers){
    const list=document.getElementById('linkedTravelersList');if(!list)return;
    const hasPassports=travelers.some(t=>t.passport_token);
    document.getElementById('btnPassportCarousel')?.classList.toggle('hidden',!travelers.length||!hasPassports);
    document.getElementById('btnTravelerTable')?.classList.toggle('hidden',!travelers.length);
    if(!travelers.length){list.innerHTML='<p class="text-xs text-gray-400 text-center py-2">No travelers linked</p>';return;}
    list.innerHTML=`<div class="overflow-x-auto mt-1"><table class="w-full text-xs"><thead><tr class="text-gray-400 border-b border-gray-100"><th class="pb-1 text-left font-medium">Name</th><th class="pb-1 text-left font-medium">PP No</th><th class="pb-1"></th></tr></thead><tbody class="divide-y divide-gray-50">${travelers.map(t=>{const p=_parsePassport(t);const pNo=p.passport_number||p.passport_no||'—';const name=t.name||'—';return`<tr class="hover:bg-gray-50"><td class="py-1.5 pr-2 truncate max-w-[70px] font-medium text-gray-700">${escHtml(name)}</td><td class="py-1.5 pr-2 font-mono text-gray-500">${escHtml(pNo)}</td><td class="py-1.5 flex items-center gap-1"><a href="show-travelers.php?id=${t.sys_id}" target="_blank" class="text-teal-400 hover:text-teal-600"><i class="fas fa-arrow-up-right-from-square text-[10px]"></i></a><button onclick="unlinkTraveler('${t.sys_id}','${(name).replace(/'/g,"\\'")} ')" class="text-red-300 hover:text-red-500 ml-1"><i class="fas fa-times text-[10px]"></i></button></td></tr>`;}).join('')}</tbody></table></div>`;
}

document.addEventListener('click',e=>{_travRows.forEach((_,i)=>{const w=document.getElementById(`tI${i}`)?.parentElement;if(w&&!w.contains(e.target))document.getElementById(`tD${i}`)?.classList.add('hidden');});});

// ── NEW TRAVELER ─────────────────────────────────────────────
function openNewTravelerModal(){
    _newTravelerExtracted=null;
    document.getElementById('newTravelerFile').value='';
    document.getElementById('newTravelerFilePreview').classList.add('hidden');
    document.getElementById('newTravelerProgress').classList.add('hidden');
    document.getElementById('newTravelerExtracted').classList.add('hidden');
    document.getElementById('newTravelerExtractBtn').classList.remove('hidden');
    document.getElementById('newTravelerSaveBtn').classList.add('hidden');
    document.getElementById('newTravelerModal').classList.remove('hidden');
}
function previewNewTravelerFile(input){if(!input.files[0])return;document.getElementById('newTravelerFileName').textContent=input.files[0].name;document.getElementById('newTravelerFilePreview').classList.remove('hidden');}

async function extractAndCreateTraveler(){
    const file=document.getElementById('newTravelerFile').files[0];
    if(!file){showToast('error','Please upload a passport scan');return;}
    document.getElementById('newTravelerProgress').classList.remove('hidden');
    document.getElementById('newTravelerExtractBtn').disabled=true;
    const fd=new FormData();fd.append('file',file);fd.append('document_type','passport');
    try{
        const res=await fetch(API.extractDocument,{method:'POST',body:fd});
        const j=await res.json();
        document.getElementById('newTravelerProgress').classList.add('hidden');
        document.getElementById('newTravelerExtractBtn').disabled=false;
        if(!j.success){showToast('error',j.message??'Extraction failed');return;}
        _newTravelerExtracted=j;
        const p=j.passport_info??j.data??{};
        const fields=[['Name',p.name??p.full_name??''],['Given Name',p.given_name??p.first_name??''],['Surname',p.surname??p.last_name??''],['Passport No',p.passport_no??p.passport_number??''],['Expiry',p.expiry_date??p.date_of_expiry??''],['DOB',p.date_of_birth??p.dob??'']].filter(([,v])=>v);
        document.getElementById('newTravelerExtractedData').innerHTML=fields.map(([k,v])=>`<div class="flex gap-2"><span class="text-gray-400 w-24 flex-shrink-0">${k}</span><span class="font-medium text-gray-700">${escHtml(String(v))}</span></div>`).join('');
        document.getElementById('newTravelerExtracted').classList.remove('hidden');
        document.getElementById('newTravelerExtractBtn').classList.add('hidden');
        document.getElementById('newTravelerSaveBtn').classList.remove('hidden');
    }catch{document.getElementById('newTravelerProgress').classList.add('hidden');document.getElementById('newTravelerExtractBtn').disabled=false;showToast('error','Network error');}
}

async function saveNewTraveler(){
    if(!_newTravelerExtracted){showToast('error','Extract data first');return;}
    const btn=document.getElementById('newTravelerSaveBtn');
    btn.disabled=true;btn.innerHTML='<i class="fas fa-spinner fa-spin mr-1.5"></i>Saving...';
    const file=document.getElementById('newTravelerFile').files[0];
    const fd=new FormData();
    if(file)fd.append('file',file);
    fd.append('extracted_data',JSON.stringify(_newTravelerExtracted));
    fd.append('work_sys_id',taskData?.work_sys_id??'');
    try{
        const res=await fetch(API.storeNewTraveler,{method:'POST',body:fd});
        const j=await res.json();
        if(j.success&&j.sys_id){
            showToast('success','Traveler created!');
            await fetch(API.workTravelers,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'link',work_sys_id:taskData?.work_sys_id??'',traveler_sys_id:j.sys_id})});
            closeModal('newTravelerModal');
            _travLoaded=false;await _loadTravelers();await loadLinkedTravelers();
        }else showToast('error',j.message??'Save failed');
    }catch{showToast('error','Network error');}
    btn.disabled=false;btn.innerHTML='<i class="fas fa-save mr-1.5"></i>Save & Link';
}

// ── PASSPORT CAROUSEL ────────────────────────────────────────
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

// ── TRAVELER TABLE MODAL ─────────────────────────────────────
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
// SPECIAL INSTRUCTIONS
// ════════════════════════════════════════════════════════════
function _renderSpecialIns(s){let items=[];if(s){if(Array.isArray(s))items=s.filter(Boolean);else if(typeof s==='string')items=[s];else if(typeof s==='object')items=Object.values(s).filter(Boolean);}const panel=document.getElementById('specialInsPanel');if(items.length&&panel){panel.classList.remove('hidden');panel.innerHTML=items.map(x=>`<div class="flex items-start gap-2 bg-amber-50 border border-amber-100 rounded-lg px-2.5 py-2 text-xs text-amber-800"><i class="fas fa-exclamation-circle text-amber-500 mt-0.5 flex-shrink-0"></i><span>${escHtml(String(x))}</span></div>`).join('');setTimeout(()=>{const ml=document.getElementById('specialInsModalList');if(ml){ml.innerHTML=`<div class="space-y-2">`+items.map((x,i)=>`<div class="flex items-start gap-3 p-3 bg-amber-50 border border-amber-200 rounded-xl"><span class="flex-shrink-0 w-6 h-6 bg-amber-400 text-white rounded-full flex items-center justify-center text-xs font-bold">${i+1}</span><span class="text-sm text-amber-900 leading-relaxed">${escHtml(String(x))}</span></div>`).join('')+'</div>';document.getElementById('specialInsModal').classList.remove('hidden');}},700);}}

// ════════════════════════════════════════════════════════════
// HELPERS
// ════════════════════════════════════════════════════════════
function closeModal(id){document.getElementById(id)?.classList.add('hidden');}
document.addEventListener('click',e=>{['statusModal','assignModal','mindBoardModal','aiModal','specialInsModal'].forEach(id=>{const el=document.getElementById(id);if(el&&e.target===el)el.classList.add('hidden');});});

function badgeHtml(s){const m={open:['bg-yellow-100 text-yellow-700 border-yellow-200','🟡 Open'],in_progress:['bg-blue-100 text-blue-700 border-blue-200','🔵 In Progress'],done:['bg-green-100 text-green-700 border-green-200','✅ Done'],cancelled:['bg-red-100 text-red-700 border-red-200','❌ Cancelled'],on_hold:['bg-purple-100 text-purple-700 border-purple-200','⏸ On Hold']};const[c,l]=m[s]??['bg-gray-100 text-gray-600 border-gray-200',s];return`<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border ${c}">${l}</span>`;}
function serviceLabel(slug){const m={air_ticket:'✈ Air Ticket',visa:'🛂 Visa',hotel:'🏨 Hotel',tour_package:'🧳 Tour Package',umrah:'🕋 Umrah',transport:'🚌 Transport'};return`<span class="font-medium text-gray-700 text-xs">${m[slug]??slug??'—'}</span>`;}
function escHtml(s){return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
function showToast(type,msg){const i=document.getElementById('toastInner');document.getElementById('toastMsg').textContent=msg;i.className=`flex items-center gap-3 px-5 py-3 rounded-xl shadow-lg text-white text-sm font-medium ${type==='success'?'bg-green-600':type==='info'?'bg-indigo-500':'bg-red-500'}`;document.getElementById('toastIcon').className=`fas ${type==='success'?'fa-check-circle':type==='info'?'fa-info-circle':'fa-exclamation-circle'} text-lg`;document.getElementById('toast').classList.remove('hidden');setTimeout(()=>document.getElementById('toast').classList.add('hidden'),3500);}
window.showToast = showToast;

loadTask();
</script>
</body>
</html>