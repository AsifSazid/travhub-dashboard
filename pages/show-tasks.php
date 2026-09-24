<?php
// FILE PATH: /pages/show-tasks.php
include_once('./authenticate.php');
$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) { $ip_port = "http://103.104.219.3:898/"; }
$taskSysId = $_GET['id'] ?? '';

$API = [
    'getTask'        => $ip_port . "api/tasks/get-task.php",
    'updateStatus'   => $ip_port . "api/tasks/update-status.php",
    'saveFinancial'  => $ip_port . "api/financial_entries_v2/store.php",
    'updateFinancial'=> $ip_port . "api/financial_entries_v2/update.php",
    'deleteFinancial'=> $ip_port . "api/financial_entries_v2/delete.php",
    'payOutstanding' => $ip_port . "api/financial_entries_v2/pay-outstanding.php",
    'receiveOutstanding' => $ip_port . "api/financial_entries_v2/receive-outstanding.php",
    'refundVendor'       => $ip_port . "api/financial_entries_v2/refund-vendor.php",
    'refundClient'       => $ip_port . "api/financial_entries_v2/refund-client.php",
    'refundSettleVendor' => $ip_port . "api/financial_entries_v2/refund-settle-vendor.php",
    'refundSettleClient' => $ip_port . "api/financial_entries_v2/refund-settle-client.php",
    'uploadFinFile'  => $ip_port . "api/financial_entries_v2/upload-file.php",
    'taskFinEntries' => $ip_port . "api/financial_entries_v2/task-fin-entries.php",
    'journeyTimeline'=> $ip_port . "api/tasks/get-journey-timeline.php",
    'allVendors'     => $ip_port . "api/vendors/all-vendors.php",
    'allAccounts'    => $ip_port . "api/accounts/all-trxnable-accounts.php",
    'assign'         => $ip_port . "api/tasks/assign.php",
    'employees'      => $ip_port . "api/employees/all-employees.php",
    'travelers'      => $ip_port . "api/travelers/all-travelers.php",
    'workTravelers'  => $ip_port . "api/works/travelers.php",
    'extractDocument'=> $ip_port . "api/travelers/extract-document.php",
    'storeNewTraveler'=> $ip_port . "api/travelers/store.php",
    'checkDuplicate'  => $ip_port . "api/travelers/check-duplicate.php",
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
        .mb-image{background:#fff;border:1px solid #e5e7eb;padding:4px;align-self:flex-start;border-radius:10px;max-width:780px;}
        .mb-image img{width:100%;max-height:400px;object-fit:contain;border-radius:7px;cursor:pointer;display:block;}
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
            <!-- Holding On -->
            <button onclick="openHoldingOnModal()" id="holdingOnBtn"
                class="flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-gray-50 text-gray-600 border border-gray-200 rounded-lg text-xs font-medium transition">
                <i class="fas fa-pause-circle text-xs text-amber-400"></i><span id="holdingOnLabel">Holding On</span>
            </button>
            <!-- Back -->
            <a id="backToWorkBtn" href="index-works.php"
                class="flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-gray-50 text-gray-600 border border-gray-200 rounded-lg text-xs font-semibold transition">
                <i class="fas fa-arrow-left text-xs"></i>Back to Work
            </a>
        </div>

        <!-- BODY: left content + right sidebar -->
        <div class="flex flex-1 overflow-hidden">

            <!-- LEFT: tab bar + content area -->
            <div class="flex-1 flex flex-col overflow-hidden">
                <!-- Top-level tabs -->
                <div class="flex items-center gap-1 px-4 pt-3 bg-white border-b border-gray-100 flex-shrink-0">
                    <button id="mtab-financial" onclick="switchMainTab('financial')"
                        class="main-tab-btn px-4 py-2 text-sm font-semibold rounded-t-lg border-b-2 transition">
                        <i class="fas fa-wallet mr-1.5"></i>Financial
                    </button>
                    <button id="mtab-documents" onclick="switchMainTab('documents')"
                        class="main-tab-btn px-4 py-2 text-sm font-semibold rounded-t-lg border-b-2 transition">
                        <i class="fas fa-folder-open mr-1.5"></i>Documents
                    </button>
                    <button id="mtab-service" onclick="switchMainTab('service')"
                        class="main-tab-btn px-4 py-2 text-sm font-semibold rounded-t-lg border-b-2 transition">
                        <i class="fas fa-layer-group mr-1.5"></i>Service Infos
                    </button>
                </div>
                <style>
                    .main-tab-btn{color:#9ca3af;border-color:transparent;}
                    .main-tab-btn:hover{color:#4b5563;background:#f9fafb;}
                    .main-tab-btn.active{color:#4f46e5;border-color:#4f46e5;background:#eef2ff;}
                </style>

                <div class="flex-1 overflow-y-auto p-4" id="financialTabArea"></div>
                <div class="flex-1 overflow-y-auto p-4 hidden" id="documentsTabArea"></div>
                <div class="flex-1 overflow-y-auto p-4 hidden" id="serviceTabArea">
                    <div class="text-center py-10 text-gray-300 text-sm">
                        <i class="fas fa-spinner fa-spin text-xl mb-2 block"></i>Loading service view…
                    </div>
                </div>
            </div>

            <!-- RIGHT: accordion sidebar -->
            <div id="rightSidebar" class="w-72 flex-shrink-0 border-l border-gray-100 bg-white p-3 space-y-2"
                style="max-height:calc(100vh - 104px);">

                <!-- 1. Task Overview -->
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

                <!-- 2. Instructions -->
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
                            <button onclick="openNewTravelerModal()" class="text-xs text-teal-500 hover:text-teal-700 font-semibold px-2 py-1">
                                <i class="fas fa-plus mr-1"></i>Add
                            </button>
                        </div>
                    </div>
                    <!-- Search field — always visible -->
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

<!-- Holding On -->
<div id="holdingOnModal" class="fixed inset-0 z-50 hidden modal-bg flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
        <div class="flex items-center justify-between p-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800 text-sm"><i class="fas fa-pause-circle mr-2 text-amber-400"></i>Holding On</h3>
            <button onclick="closeModal('holdingOnModal')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-4 space-y-3">
            <label class="text-xs font-bold text-gray-400 uppercase block mb-1">Reason / Note</label>
            <input id="holdingOnInput" class="f-input" placeholder="e.g. Waiting for passport">
            <div class="flex gap-2">
                <button onclick="saveHoldingOn(true)" class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg text-xs font-semibold transition">
                    <i class="fas fa-times mr-1"></i>Clear
                </button>
                <button onclick="saveHoldingOn(false)" class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold transition">
                    <i class="fas fa-save mr-1.5"></i>Save
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Financial Entry Edit -->
<div id="finEditModal" class="fixed inset-0 z-50 hidden modal-bg flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800 text-sm"><i class="fas fa-edit mr-2 text-indigo-500"></i>Edit Transaction</h3>
            <button onclick="closeModal('finEditModal')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-4 space-y-3">
            <input type="hidden" id="fin_edit_id">
            <input type="hidden" id="fin_edit_original_type">
            <div>
                <label class="text-xs font-bold text-gray-400 uppercase block mb-1">Type</label>
                <div id="fin_edit_type_display" class="px-3 py-2 bg-gray-50 rounded-lg text-sm font-semibold border border-gray-100"></div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Purpose</label>
                <textarea id="fin_edit_purpose" rows="2" class="f-input"></textarea>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Note <span class="text-gray-300 font-normal">(optional)</span></label>
                <input id="fin_edit_note" placeholder="Any notes…" class="f-input">
            </div>
            <div class="p-2.5 bg-indigo-50 rounded-lg border border-indigo-100">
                <p class="text-xs text-indigo-600 font-medium mb-1.5"><i class="fas fa-calculator mr-1"></i>যেকোনো দুইটা দিলে তৃতীয়টা auto হবে</p>
                <div class="grid grid-cols-3 gap-1.5">
                    <div><label class="block text-[10px] text-gray-500 mb-0.5">QTY</label><input type="number" step="0.01" min="0" id="fin_edit_qty" class="f-input text-xs"></div>
                    <div><label class="block text-[10px] text-gray-500 mb-0.5">Rate</label><input type="number" step="0.01" min="0" id="fin_edit_rate" class="f-input text-xs"></div>
                    <div><label class="block text-[10px] text-gray-500 mb-0.5">Amount ৳</label><input type="number" step="0.01" min="0" id="fin_edit_amount" class="f-input text-xs"></div>
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Date</label>
                <input type="date" id="fin_edit_date" class="f-input">
            </div>
            <div class="p-2.5 bg-amber-50 rounded-lg border border-amber-200 space-y-2">
                <p class="text-xs text-amber-700 font-semibold"><i class="fas fa-shield-alt mr-1"></i>Edit করতে reason ও evidence বাধ্যতামূলক</p>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Reason for this edit <span class="text-red-500">*</span></label>
                    <textarea id="fin_edit_reason" rows="2" placeholder="কেন এই পরিবর্তন করা হচ্ছে…" class="f-input"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Evidence file <span class="text-red-500">*</span></label>
                    <label class="flex items-center gap-2 px-3 py-2 border border-dashed border-amber-300 rounded-lg cursor-pointer hover:border-amber-400 hover:bg-amber-100/50 transition text-xs">
                        <i class="fas fa-cloud-upload-alt text-gray-400"></i>
                        <span id="fin_edit_evidenceLabel" class="text-gray-500 truncate">Browse or drop a file</span>
                        <input type="file" id="fin_edit_evidenceInput" class="hidden" onchange="_finEditEvidenceSelected(this)">
                    </label>
                    <input type="hidden" id="fin_edit_evidenceFile">
                </div>
            </div>
            <div class="flex gap-2 pt-1">
                <button onclick="closeModal('finEditModal')" class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg text-xs font-semibold transition">Cancel</button>
                <button onclick="finUpdateTransaction()" class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold transition">
                    <i class="fas fa-save mr-1.5"></i>Update
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ── NEW TRAVELER MODAL ───────────────────────────────────── -->
<div id="newTravelerModal" class="fixed inset-0 z-[70] hidden modal-bg flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md flex flex-col" style="max-height:90vh;">
        <div class="flex items-center justify-between p-4 border-b border-gray-100 flex-shrink-0">
            <h3 class="font-semibold text-gray-800 text-sm"><i class="fas fa-user-plus mr-2 text-teal-500"></i>New Traveler</h3>
            <button onclick="closeModal('newTravelerModal')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <div class="overflow-y-auto flex-1 p-4 space-y-3">
            <!-- Upload area -->
            <label id="ntDropZone" class="flex flex-col items-center gap-2 px-4 py-5 border-2 border-dashed border-teal-300 rounded-xl cursor-pointer hover:border-teal-400 hover:bg-teal-50 transition-colors">
                <i class="fas fa-id-card text-teal-400 text-2xl"></i>
                <span class="text-sm text-gray-600 font-medium">Upload Passport Scan</span>
                <span class="text-xs text-gray-400">JPG, PNG, PDF, WebP</span>
                <input type="file" id="ntFileInput" accept=".jpg,.jpeg,.png,.webp,.pdf" class="hidden"
                    onchange="ntFileSelected(this)">
            </label>
            <div id="ntFilePreview" class="hidden text-xs text-teal-600 bg-teal-50 rounded-lg px-3 py-2 flex items-center gap-2">
                <i class="fas fa-file"></i>
                <span id="ntFileName" class="flex-1 truncate"></span>
                <button onclick="ntClearFile()" class="text-red-400 hover:text-red-600"><i class="fas fa-times"></i></button>
            </div>

            <!-- Extract progress -->
            <div id="ntProgress" class="hidden text-center py-2 text-xs text-teal-600">
                <i class="fas fa-spinner fa-spin mr-1"></i><span id="ntProgressText">Extracting...</span>
            </div>

            <!-- Extracted info -->
            <div id="ntExtracted" class="hidden bg-gray-50 rounded-xl p-3 text-xs space-y-1" id="ntExtractedData"></div>

            <!-- Duplicate found -->
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

        <!-- Actions -->
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
let taskData = null, workData = null, serviceWorkData = null;

// ── Load ─────────────────────────────────────────────────────
async function loadTask() {
    if (!TASK_SYS_ID) { document.getElementById('loadingState').innerHTML='<p class="text-red-400 text-center text-sm">No task ID.</p>'; return; }
    try {
        const res  = await fetch(API.getTask + '?id=' + TASK_SYS_ID);
        const json = await res.json();
        if (json.status !== 'success') throw new Error(json.message);
        taskData = json.task; workData = json.work; serviceWorkData = json.service_work ?? null;
        renderCommonUI();
        _loadAtDataForDocuments(); // async, non-blocking — used only by Documents tab raw files
        switchMainTab('financial');
    } catch(e) {
        document.getElementById('loadingState').innerHTML = `<p class="text-red-400 text-center text-sm py-10">${escHtml(e.message)}</p>`;
    }
}

async function _loadAtDataForDocuments() {
    if (taskData?.service_slug !== 'air_ticket' || !taskData?.work_sys_id) return;
    try {
        const res  = await fetch(API.airTickets + '?action=get&work_sys_id=' + encodeURIComponent(taskData.work_sys_id));
        const json = await res.json();
        if (json.status === 'success') {
            _atData = json.data ?? null;
            if (document.getElementById('documentsTabArea') && !document.getElementById('documentsTabArea').classList.contains('hidden')) {
                document.getElementById('doc_rawFiles').innerHTML = _docRawFilesHtml();
            }
        }
    } catch(e) { /* Documents tab shows empty state if this fails — non-critical */ }
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
    document.getElementById('ov-assigned').textContent= serviceWorkData?.assigned_to_name ?? 'Not assigned';
    // Holding On state
    _currentHoldingOn = taskData.holding_on ? (typeof taskData.holding_on==='object'?JSON.stringify(taskData.holding_on):taskData.holding_on) : '';
    renderHoldingOnBadge();
    // Instructions
    document.getElementById('instructionDisplay').textContent = taskData.instruction ?? '—';
    _renderSpecialIns(taskData.special_ins);
    // Travelers — load from API (work level)
    loadLinkedTravelers();
}

// ════════════════════════════════════════════════════════════
// TOP-LEVEL TABS: Financial | Documents | Service Infos
// ════════════════════════════════════════════════════════════
let _atData = null;
let _financialLoaded = false, _documentsLoaded = false, _serviceLoaded = false;

function switchMainTab(tab) {
    ['financial','documents','service'].forEach(t => {
        document.getElementById(`mtab-${t}`).classList.toggle('active', t === tab);
        document.getElementById(`${t}TabArea`).classList.toggle('hidden', t !== tab);
    });
    if (tab === 'financial' && !_financialLoaded) { _financialLoaded = true; initFinancialTab(); }
    if (tab === 'documents' && !_documentsLoaded) { _documentsLoaded = true; initDocumentsTab(); }
    if (tab === 'service'   && !_serviceLoaded)   { _serviceLoaded = true; loadServiceTab(taskData?.service_slug ?? null, _atData); }
}

// ── Service tab ───────────────────────────────────────────────
function loadServiceTab(slug, atData) {
    const area = document.getElementById('serviceTabArea');
    switch (slug) {
        case 'air_ticket': _loadAirTicketTab(); break;
        default:
            area.innerHTML = `<div class="sc p-6 text-center text-gray-400 text-sm"><i class="fas fa-question-circle text-3xl mb-3 block opacity-30"></i>Service <b>${escHtml(slug??'unknown')}</b> — no dedicated view yet.</div>`;
    }
}

async function _loadAirTicketTab() {
    const area = document.getElementById('serviceTabArea');
    area.innerHTML = `<div class="text-center py-10 text-gray-300 text-sm"><i class="fas fa-spinner fa-spin text-xl mb-2 block"></i>Loading journey…</div>`;
    try {
        const res  = await fetch(API.journeyTimeline + '?task_sys_id=' + encodeURIComponent(TASK_SYS_ID));
        const json = await res.json();
        if (json.status !== 'success') throw new Error(json.message || 'Failed to load');
        _renderJourneyTimeline(json.events ?? []);
    } catch(e) {
        area.innerHTML = `<div class="sc p-6 text-center text-red-400 text-sm">${escHtml(e.message)}</div>`;
    }
}

const _JOURNEY_STAGE_INFO = {
    note:         { icon: 'fa-lightbulb',     color: 'bg-amber-100 text-amber-600',   label: 'Mind Board' },
    quotation:    { icon: 'fa-file-invoice',  color: 'bg-blue-100 text-blue-600',     label: 'Quotation' },
    booking:      { icon: 'fa-bookmark',      color: 'bg-purple-100 text-purple-600', label: 'Booking' },
    confirmation: { icon: 'fa-check-circle',  color: 'bg-emerald-100 text-emerald-600', label: 'Confirmation' },
    task:         { icon: 'fa-flag-checkered',color: 'bg-indigo-100 text-indigo-600', label: 'Task Created' },
};

let _journeyEvents = [];

function _renderJourneyTimeline(events) {
    _journeyEvents = events;
    const area = document.getElementById('serviceTabArea');
    if (!events.length) {
        area.innerHTML = `<div class="sc p-6 text-center text-gray-400 text-sm"><i class="fas fa-route text-3xl mb-3 block opacity-30"></i>এই task-এর journey data পাওয়া যায়নি।</div>`;
        return;
    }
    area.innerHTML = `
        <div class="sc p-4">
            <h3 class="text-sm font-semibold text-gray-800 mb-4"><i class="fas fa-route mr-1.5 text-indigo-500"></i>Journey Timeline</h3>
            <div class="relative pl-8">
                <div class="absolute left-[13px] top-2 bottom-2 w-0.5 bg-gray-100"></div>
                ${events.map((ev, i) => {
                    const info = _JOURNEY_STAGE_INFO[ev.stage] ?? { icon: 'fa-circle', color: 'bg-gray-100 text-gray-500', label: ev.stage };
                    return `
                    <div class="relative pb-6 last:pb-0">
                        <div class="absolute -left-8 w-7 h-7 rounded-full ${info.color} flex items-center justify-center text-xs ring-4 ring-white">
                            <i class="fas ${info.icon}"></i>
                        </div>
                        <button onclick="_toggleJourneyCard(${i})" class="w-full text-left rounded-lg hover:bg-gray-50 transition px-2 py-1.5 -ml-2">
                            <div class="flex items-center gap-2 mb-0.5">
                                <span class="text-xs font-bold uppercase tracking-wide text-gray-400">${escHtml(info.label)}</span>
                                ${i===0 ? '<span class="px-1.5 py-0.5 bg-indigo-50 text-indigo-500 text-[10px] rounded-full font-semibold">Latest</span>' : ''}
                                <i class="fas fa-chevron-down ml-auto text-gray-300 text-xs transition-transform" id="jc-chevron-${i}"></i>
                            </div>
                            <p class="text-sm font-semibold text-gray-800">${escHtml(ev.title || '')}</p>
                            ${ev.summary ? `<p class="text-xs text-gray-500 mt-0.5">${escHtml(ev.summary)}</p>` : ''}
                            <p class="text-[11px] text-gray-400 mt-1">${ev.at ? escHtml(ev.at) : 'সময় জানা নেই'}${ev.by ? ' · ' + escHtml(ev.by) : ''}</p>
                        </button>
                        <div id="jc-detail-${i}" class="hidden mt-2 ml-1 p-3 bg-gray-50 rounded-lg border border-gray-100 text-xs"></div>
                    </div>`;
                }).join('')}
            </div>
        </div>
    `;
}

function _toggleJourneyCard(i) {
    const detail  = document.getElementById(`jc-detail-${i}`);
    const chevron = document.getElementById(`jc-chevron-${i}`);
    const opening = detail.classList.contains('hidden');
    detail.classList.toggle('hidden');
    chevron.style.transform = opening ? 'rotate(180deg)' : '';
    if (opening && !detail.dataset.loaded) {
        detail.innerHTML = _renderJourneyDetail(_journeyEvents[i]);
        detail.dataset.loaded = '1';
    }
}

function _kv(label, value) {
    if (value === null || value === undefined || value === '') return '';
    return `<div class="flex justify-between gap-3 py-1 border-b border-gray-100 last:border-b-0"><span class="text-gray-400">${escHtml(label)}</span><span class="text-gray-700 font-medium text-right">${escHtml(String(value))}</span></div>`;
}

function _renderJourneyDetail(ev) {
    const r = ev.raw ?? {};
    switch (ev.stage) {
        case 'note': {
            if (r.note_type === 'text') {
                return `<p class="text-gray-700 whitespace-pre-wrap leading-relaxed">${escHtml(r.content || '')}</p>`;
            }
            return `<p class="text-gray-500 italic">${escHtml(r.note_type || 'media')} note — ${escHtml(r.content || '')}</p>`;
        }
        case 'quotation':
        case 'booking': {
            const fd = r.form_data ?? {};
            const segs = Array.isArray(r.segments_json) ? r.segments_json : [];
            return `
                ${_kv('Type', (r.type || '').toUpperCase())}
                ${_kv('Airline', r.airline)}
                ${ev.stage === 'booking' ? _kv('PNR', r.pnr) : ''}
                ${ev.stage === 'booking' ? _kv('Ticket No(s)', (r.ticket_nos||[]).join(', ')) : ''}
                ${_kv('Adult / Child / Infant', (fd.pax_adult!==undefined) ? `${fd.pax_adult??0} / ${fd.pax_child??0} / ${fd.pax_infant??0}` : null)}
                ${_kv('Route', fd.route)}
                ${_kv('Class', fd.class)}
                ${_kv('Gross Fare', r.gross_fare ? '৳'+Number(r.gross_fare).toFixed(2) : null)}
                ${_kv('Net Fare', r.net_fare ? '৳'+Number(r.net_fare).toFixed(2) : null)}
                ${_kv('Total Payable', r.total_payable ? '৳'+Number(r.total_payable).toFixed(2) : null)}
                ${_kv('Status', r.status)}
                ${segs.length ? `<div class="mt-2 pt-2 border-t border-gray-200">
                    <p class="text-gray-400 font-semibold mb-1">Segments</p>
                    ${segs.map(s => `<div class="py-1">${escHtml(s.dep_airport||'')} ${escHtml(s.dep_time||'')} → ${escHtml(s.arr_airport||'')} ${escHtml(s.arr_time||'')} · ${escHtml(s.date||'')} ${s.flight_no?('· '+escHtml(s.airline||'')+' '+escHtml(s.flight_no)):''}</div>`).join('')}
                </div>` : ''}
                ${r.copy_text ? `<div class="mt-2 pt-2 border-t border-gray-200"><p class="text-gray-400 font-semibold mb-1">Raw Text</p><pre class="whitespace-pre-wrap font-sans text-gray-600">${escHtml(r.copy_text)}</pre></div>` : ''}
            `;
        }
        case 'confirmation': {
            const files = Array.isArray(r.files_json) ? r.files_json : [];
            const srcBooking = _journeyEvents.find(e => e.stage === 'booking' && e.sys_id === r.booking_sys_id)?.raw ?? {};
            const fd = srcBooking.form_data ?? {};
            return `
                ${_kv('Status', r.status)}
                ${_kv('Ticket No(s)', (r.ticket_nos||[]).join(', '))}
                ${_kv('Note', r.note)}
                ${srcBooking.sys_id ? `<div class="mt-2 pt-2 border-t border-gray-200">
                    <p class="text-gray-400 font-semibold mb-1">From Booking ${escHtml(srcBooking.sys_id)}</p>
                    ${_kv('Airline', srcBooking.airline)}
                    ${_kv('PNR', srcBooking.pnr)}
                    ${_kv('Route', fd.route)}
                    ${_kv('Total Payable', srcBooking.total_payable ? '৳'+Number(srcBooking.total_payable).toFixed(2) : null)}
                </div>` : ''}
                ${files.length ? `<div class="mt-2 pt-2 border-t border-gray-200">
                    <p class="text-gray-400 font-semibold mb-1">Files (${files.length})</p>
                    <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                        ${files.map((f, idx) => {
                            const isImg = (f.mime_type||'').startsWith('image/');
                            const url = `${API.fileServe}?conf_id=${ev.sys_id}&work_id=${taskData.work_sys_id}&page=${idx}`;
                            return `<a href="${url}" target="_blank" class="block border border-gray-200 rounded-lg overflow-hidden hover:border-indigo-300 transition bg-white">
                                ${isImg
                                    ? `<img src="${url}" class="w-full h-16 object-cover" loading="lazy">`
                                    : `<div class="w-full h-16 flex items-center justify-center bg-gray-50"><i class="fas fa-file-pdf text-red-300 text-xl"></i></div>`}
                                <div class="px-1.5 py-1 text-[10px] text-gray-500 truncate">${escHtml(f.name || f.file_name || 'file')}</div>
                            </a>`;
                        }).join('')}
                    </div>
                </div>` : ''}
            `;
        }
        case 'task': {
            return `
                ${_kv('Task ID', r.sys_id)}
                ${_kv('Work', r.work_sys_id)}
                ${_kv('Client', r.client_name)}
                ${_kv('Status', r.status)}
                ${_kv('Service', r.service_slug)}
            `;
        }
        default:
            return `<pre class="whitespace-pre-wrap font-sans text-gray-500">${escHtml(JSON.stringify(r, null, 2))}</pre>`;
    }
}

// ════════════════════════════════════════════════════════════
// FINANCIAL TAB (client-fin-trxn.php প্যাটার্ন অনুসরণ করে, financial_entries_v2 API দিয়ে)
// ════════════════════════════════════════════════════════════
let _finVendors = [], _finAccounts = [], _finTransactions = [];

function initFinancialTab() {
    const area = document.getElementById('financialTabArea');
    const ci = workData?.client_info ?? {};
    area.innerHTML = `
        <!-- Task Info strip -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
            <div class="rounded-xl p-3.5 border border-indigo-100 bg-gradient-to-br from-indigo-50 to-white">
                <p class="text-[11px] text-indigo-400 font-semibold uppercase tracking-wide">Task ID</p>
                <p class="text-sm font-bold text-indigo-900 mt-0.5">${escHtml(TASK_SYS_ID)}</p>
            </div>
            <div class="rounded-xl p-3.5 border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white">
                <p class="text-[11px] text-emerald-500 font-semibold uppercase tracking-wide">Client</p>
                <p class="text-sm font-bold text-emerald-900 mt-0.5 truncate">${escHtml(taskData?.client_name ?? ci.name ?? 'N/A')}</p>
            </div>
            <div class="rounded-xl p-3.5 border border-purple-100 bg-gradient-to-br from-purple-50 to-white">
                <p class="text-[11px] text-purple-400 font-semibold uppercase tracking-wide">Work</p>
                <p class="text-sm font-bold text-purple-900 mt-0.5 truncate">${escHtml(taskData?.workname ?? 'N/A')}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
            <!-- Client Sale -->
            <div class="sc overflow-hidden">
                <div class="p-3 sm:p-4 flex items-center justify-between" style="background:linear-gradient(135deg,#6366f1,#4f46e5);">
                    <div>
                        <h3 class="text-sm font-semibold text-white">Client Sale</h3>
                        <p class="text-indigo-100 text-xs">Record a sale to this client</p>
                    </div>
                    <i class="fas fa-arrow-down text-white/80 text-xl"></i>
                </div>
                <div class="p-3 sm:p-4 space-y-3">
                    <div class="flex items-center gap-2 pb-2 border-b border-gray-100">
                        <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-600 font-bold text-xs flex-shrink-0">${(taskData?.client_name ?? ci.name ?? 'C')[0]?.toUpperCase()}</div>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-gray-700 truncate">${escHtml(taskData?.client_name ?? ci.name ?? 'Unknown Client')}</p>
                            <p class="text-[11px] text-gray-400 truncate">${escHtml(ci.email ?? ci.phone ?? '')}</p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1"><i class="fas fa-bullseye mr-1"></i>Purpose</label>
                        <textarea id="fin_client_purpose" rows="2" placeholder="e.g., Initial Payment" class="f-input"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1"><i class="fas fa-pen mr-1"></i>Note <span class="text-gray-300 font-normal">(optional)</span></label>
                        <input id="fin_client_note" placeholder="Any notes…" class="f-input">
                    </div>
                    <div class="p-2.5 bg-indigo-50 rounded-lg border border-indigo-100">
                        <p class="text-xs text-indigo-600 font-medium mb-1.5"><i class="fas fa-calculator mr-1"></i>যেকোনো দুইটা দিলে তৃতীয়টা auto হবে</p>
                        <div class="grid grid-cols-3 gap-1.5">
                            <div><label class="block text-[10px] text-gray-500 mb-0.5">QTY</label><input type="number" step="0.01" min="0" id="fin_client_qty" class="f-input text-xs fin-calc" placeholder="0"></div>
                            <div><label class="block text-[10px] text-gray-500 mb-0.5">Rate</label><input type="number" step="0.01" min="0" id="fin_client_rate" class="f-input text-xs fin-calc" placeholder="0.00"></div>
                            <div><label class="block text-[10px] text-gray-500 mb-0.5">Amount ৳</label><input type="number" step="0.01" min="0" id="fin_client_amount" class="f-input text-xs fin-calc" placeholder="0.00"></div>
                        </div>
                    </div>
                    <div><label class="block text-xs font-medium text-gray-700 mb-1"><i class="far fa-calendar mr-1"></i>Date</label>
                        <input type="date" id="fin_client_date" value="${new Date().toISOString().slice(0,10)}" class="f-input"></div>
                    <div>
                        <label class="flex items-center gap-2 px-3 py-2 border border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-indigo-400 hover:bg-indigo-50 transition text-xs">
                            <i class="fas fa-cloud-upload-alt text-gray-400"></i>
                            <span id="fin_clientFileLabel" class="text-gray-500 truncate">Browse or drop files</span>
                            <input type="file" id="fin_clientFiles" multiple class="hidden" onchange="document.getElementById('fin_clientFileLabel').textContent=this.files.length>1?this.files.length+' files':(this.files[0]?.name||'Browse or drop files')">
                        </label>
                    </div>
                    <button onclick="finRecordTransaction('client_submit')" class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold transition"><i class="fas fa-plus mr-1"></i>Record Sale</button>
                    <p class="text-[11px] text-gray-400 text-center">Payment receive করতে, নিচের transaction list-এ Sale-টা খুলে "Receive Now" ব্যবহার করুন।</p>
                </div>
            </div>

            <!-- Vendor Payment -->
            <div class="sc overflow-hidden">
                <div class="p-3 sm:p-4 flex items-center justify-between" style="background:linear-gradient(135deg,#10b981,#059669);">
                    <div>
                        <h3 class="text-sm font-semibold text-white">Vendor Payment</h3>
                        <p class="text-emerald-100 text-xs">Record vendor expenses</p>
                    </div>
                    <i class="fas fa-arrow-up text-white/80 text-xl"></i>
                </div>
                <div class="p-3 sm:p-4 space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1"><i class="fas fa-building mr-1"></i>Vendor</label>
                        <div class="relative" id="fin_vendorWrap">
                            <input id="fin_vendorSearch" placeholder="Search for a vendor…" class="f-input" autocomplete="off"
                                oninput="_finFilterList('vendor', this.value)" onfocus="_finFilterList('vendor', this.value)">
                            <ul id="fin_vendorDrop" class="absolute w-full bg-white border border-gray-200 rounded-lg mt-1 max-h-44 overflow-auto shadow-xl hidden z-50"></ul>
                        </div>
                        <input type="hidden" id="fin_vendorSelect">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5"><i class="fa-solid fa-clock mr-1"></i>Transaction Type</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="flex items-center justify-center gap-1.5 p-2 border-2 border-emerald-500 bg-emerald-50 text-emerald-700 rounded-lg cursor-pointer text-xs font-semibold" id="fin_modeRealtimeLbl">
                                <input type="radio" name="fin_txn_mode" value="realtime" class="hidden" checked onchange="finToggleTxnMode()"><i class="fa-solid fa-bolt"></i>Real-time</label>
                            <label class="flex items-center justify-center gap-1.5 p-2 border-2 border-gray-200 text-gray-500 rounded-lg cursor-pointer text-xs font-semibold" id="fin_modeNonRealtimeLbl">
                                <input type="radio" name="fin_txn_mode" value="non_realtime" class="hidden" onchange="finToggleTxnMode()"><i class="fa-solid fa-clock-rotate-left"></i>Non-real-time</label>
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1">Real-time: এখনই কোনো account থেকে vendor-কে payment দেওয়া হচ্ছে। Non-real-time: শুধু service purchase রেকর্ড হবে, payment পরে।</p>
                    </div>
                    <div id="fin_accountSection">
                        <label class="block text-xs font-medium text-gray-700 mb-1"><i class="fas fa-university mr-1"></i>Own Account</label>
                        <div class="relative" id="fin_accountWrap">
                            <input id="fin_accountSearch" placeholder="Search for an account…" class="f-input" autocomplete="off"
                                oninput="_finFilterList('account', this.value)" onfocus="_finFilterList('account', this.value)">
                            <ul id="fin_accountDrop" class="absolute w-full bg-white border border-gray-200 rounded-lg mt-1 max-h-44 overflow-auto shadow-xl hidden z-50"></ul>
                        </div>
                        <input type="hidden" id="fin_accountSelect">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1"><i class="fas fa-bullseye mr-1"></i>Purpose</label>
                        <textarea id="fin_vendor_purpose" rows="2" placeholder="e.g., Hotel Booking" class="f-input"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1"><i class="fas fa-pen mr-1"></i>Note <span class="text-gray-300 font-normal">(optional)</span></label>
                        <input id="fin_vendor_note" placeholder="Any notes…" class="f-input">
                    </div>
                    <div class="p-2.5 rounded-lg border border-emerald-200" style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);">
                        <p class="text-xs text-emerald-700 font-medium mb-1.5"><i class="fas fa-calculator mr-1"></i>যেকোনো দুইটা দিলে তৃতীয়টা auto হবে</p>
                        <div class="grid grid-cols-3 gap-1.5">
                            <div><label class="block text-[10px] text-emerald-700 mb-0.5">QTY</label><input type="number" step="0.01" min="0" id="fin_vendor_qty" class="f-input text-xs fin-calc" placeholder="0"></div>
                            <div><label class="block text-[10px] text-emerald-700 mb-0.5">Rate</label><input type="number" step="0.01" min="0" id="fin_vendor_rate" class="f-input text-xs fin-calc" placeholder="0.00"></div>
                            <div><label class="block text-[10px] text-emerald-700 mb-0.5">Amount ৳</label><input type="number" step="0.01" min="0" id="fin_vendor_amount" class="f-input text-xs fin-calc" placeholder="0.00"></div>
                        </div>
                    </div>
                    <div><label class="block text-xs font-medium text-gray-700 mb-1"><i class="far fa-calendar mr-1"></i>Date</label>
                        <input type="date" id="fin_vendor_date" value="${new Date().toISOString().slice(0,10)}" class="f-input"></div>
                    <div>
                        <label class="flex items-center gap-2 px-3 py-2 border border-dashed border-emerald-300 rounded-lg cursor-pointer hover:border-emerald-400 hover:bg-emerald-50 transition text-xs">
                            <i class="fas fa-cloud-upload-alt text-gray-400"></i>
                            <span id="fin_vendorFileLabel" class="text-gray-500 truncate">Browse or drop files</span>
                            <input type="file" id="fin_vendorFiles" multiple class="hidden" onchange="document.getElementById('fin_vendorFileLabel').textContent=this.files.length>1?this.files.length+' files':(this.files[0]?.name||'Browse or drop files')">
                        </label>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <button onclick="finRecordTransaction('debit_refund')" class="py-2 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-lg text-xs font-semibold transition"><i class="fas fa-undo mr-1"></i>Refund</button>
                        <button onclick="finRecordTransaction('credit')" class="py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold transition"><i class="fas fa-plus mr-1"></i>Record Payment</button>
                    </div>
                </div>
            </div>

            <!-- Stats sidebar -->
            <div class="space-y-4">
                <div class="sc p-4">
                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-3"><i class="fas fa-chart-pie mr-1.5"></i>Statistics</h4>
                    <div id="fin_statTotalCount" class="flex items-center justify-between text-xs mb-2">
                        <span class="text-gray-500">Total Transactions</span><span class="font-bold text-gray-800">0</span>
                    </div>
                    <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden mb-3"><div id="fin_statProgressBar" class="h-full bg-indigo-400 transition-all" style="width:0%"></div></div>
                    <div id="fin_statRatio" class="flex items-center justify-between text-xs mb-3">
                        <span class="text-gray-500">Deposit : Payment</span><span class="font-bold text-gray-800">0:0</span>
                    </div>
                    <div class="pt-3 border-t border-gray-100 text-center">
                        <p id="fin_statNetBalance" class="text-2xl font-bold text-gray-800">৳0.00</p>
                        <p class="text-[11px] text-gray-400">Net Balance</p>
                    </div>
                </div>
                <div class="sc p-4">
                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-2"><i class="fas fa-receipt mr-1.5"></i>Evidence Files</h4>
                    <p id="fin_statFileCount" class="text-xs text-gray-400">No files attached yet</p>
                </div>
            </div>
        </div>

        <!-- Financial Summary + Table -->
        <div class="sc p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-800"><i class="fas fa-chart-line mr-1.5 text-indigo-500"></i>Financial Summary</h3>
                <button onclick="finLoadEntries()" class="px-2.5 py-1 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg text-xs transition"><i class="fas fa-redo-alt mr-1"></i>Refresh</button>
            </div>
            <div id="fin_summaryCards" class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4"></div>
            <div class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-xs">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-500 uppercase">Type / Purpose</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-500 uppercase hidden sm:table-cell">Group ID</th>
                            <th class="px-3 py-2 text-right font-medium text-gray-500 uppercase">Amount</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-500 uppercase">Files</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="fin_tableBody" class="bg-white divide-y divide-gray-100"></tbody>
                </table>
            </div>
        </div>
    `;
    _finSetupCalc('fin_client_qty','fin_client_rate','fin_client_amount');
    _finSetupCalc('fin_vendor_qty','fin_vendor_rate','fin_vendor_amount');
    _finSetupCalc('fin_edit_qty','fin_edit_rate','fin_edit_amount');
    finLoadVendorsAccounts();
    finLoadEntries();
}

function finToggleTxnMode() {
    const isRealtime = document.querySelector('input[name="fin_txn_mode"]:checked').value === 'realtime';
    document.getElementById('fin_accountSection').classList.toggle('hidden', !isRealtime);
    document.getElementById('fin_modeRealtimeLbl').className    = 'flex items-center justify-center gap-1.5 p-2 border-2 rounded-lg cursor-pointer text-xs font-semibold ' + (isRealtime ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-gray-200 text-gray-500');
    document.getElementById('fin_modeNonRealtimeLbl').className = 'flex items-center justify-center gap-1.5 p-2 border-2 rounded-lg cursor-pointer text-xs font-semibold ' + (!isRealtime ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-gray-200 text-gray-500');
}

// ── QTY/Rate/Amount smart auto-calc (যেকোনো দুইটা দিলে তৃতীয়টা) ──
function _finSetupCalc(qtyId, rateId, amtId) {
    const q = document.getElementById(qtyId), r = document.getElementById(rateId), a = document.getElementById(amtId);
    if (!q || !r || !a) return;
    let lastEdited = null;
    function calc() {
        const qty = parseFloat(q.value) || null, rate = parseFloat(r.value) || null, amt = parseFloat(a.value) || null;
        if (lastEdited !== 'amount' && qty && rate) a.value = (qty*rate).toFixed(2);
        else if (lastEdited !== 'rate' && qty && amt) r.value = (amt/qty).toFixed(2);
        else if (lastEdited !== 'qty' && rate && amt) q.value = (amt/rate).toFixed(2);
    }
    q.addEventListener('input', () => { lastEdited='qty'; calc(); });
    r.addEventListener('input', () => { lastEdited='rate'; calc(); });
    a.addEventListener('input', () => { lastEdited='amount'; calc(); });
}

function _finBuildQtyRate(qtyId, rateId) {
    const qty = parseFloat(document.getElementById(qtyId)?.value) || null;
    const rate = parseFloat(document.getElementById(rateId)?.value) || null;
    if (!qty && !rate) return null;
    return JSON.stringify({ qty: qty||0, rate: rate||0 });
}

async function finLoadVendorsAccounts() {
    try {
        const [vRes, aRes] = await Promise.all([fetch(API.allVendors), fetch(API.allAccounts)]);
        const vJson = await vRes.json(), aJson = await aRes.json();
        _finVendors  = vJson.vendors  ?? [];
        _finAccounts = aJson.accounts ?? [];
    } catch(e) {
        showToast('error', 'Vendor/Account list load failed');
    }
}

const _finPickerMap = {
    vendor:         { dropId: 'fin_vendorDrop',        searchId: 'fin_vendorSearch',        selectId: 'fin_vendorSelect',        source: () => _finVendors,  nameKey: 'name' },
    account:        { dropId: 'fin_accountDrop',       searchId: 'fin_accountSearch',       selectId: 'fin_accountSelect',       source: () => _finAccounts, nameKey: 'acc_name' },
    client_account: { dropId: 'fin_client_accountDrop',searchId: 'fin_client_accountSearch',selectId: 'fin_client_accountSelect',source: () => _finAccounts, nameKey: 'acc_name' },
};

function _finFilterList(kind, q) {
    const cfg = _finPickerMap[kind];
    const dd = document.getElementById(cfg.dropId);
    const source = cfg.source();
    const v = q.toLowerCase().trim();
    const list = v ? source.filter(x => (x[cfg.nameKey]||'').toLowerCase().includes(v)) : source.slice(0, 15);
    if (!list.length) {
        dd.innerHTML = `<li class="px-4 py-3 text-center text-gray-400 text-xs">কিছু পাওয়া যায়নি</li>`;
        dd.classList.remove('hidden');
        return;
    }
    dd.innerHTML = list.map(x => `
        <li class="px-3 py-2 cursor-pointer hover:bg-indigo-50 border-b last:border-b-0 text-sm text-gray-800"
            onclick="_finSelectItem('${kind}','${x.sys_id}','${escHtml(x[cfg.nameKey]||x.sys_id).replace(/'/g,"\\'")}')">
            ${escHtml(x[cfg.nameKey] ?? x.sys_id)}
        </li>`).join('');
    dd.classList.remove('hidden');
}

function _finSelectItem(kind, sysId, name) {
    const cfg = _finPickerMap[kind];
    document.getElementById(cfg.searchId).value = name;
    document.getElementById(cfg.selectId).value = sysId;
    document.getElementById(cfg.dropId).classList.add('hidden');
}

document.addEventListener('click', e => {
    const wrapToDrop = { fin_vendorWrap:'fin_vendorDrop', fin_accountWrap:'fin_accountDrop', fin_client_accountWrap:'fin_client_accountDrop' };
    Object.keys(wrapToDrop).forEach(wrapId => {
        const wrap = document.getElementById(wrapId);
        if (wrap && !wrap.contains(e.target)) {
            document.getElementById(wrapToDrop[wrapId])?.classList.add('hidden');
        }
    });
});

// ── Record / Refund transaction ──────────────────────────────
// mode: 'debit' (client deposit) | 'credit_refund' (client refund)
//       'credit' (vendor/own payment) | 'debit_refund' (vendor/own refund)
async function finRecordTransaction(mode) {
    const isClientSide = mode === 'client_submit';
    try {
        if (isClientSide) {
            const purpose = document.getElementById('fin_client_purpose').value.trim();
            const amount  = parseFloat(document.getElementById('fin_client_amount').value);
            const date    = document.getElementById('fin_client_date').value;
            const note    = document.getElementById('fin_client_note').value.trim();
            const qtyRate = _finBuildQtyRate('fin_client_qty','fin_client_rate');
            if (!purpose || !amount || amount <= 0) { showToast('error','Purpose ও সঠিক amount দিন'); return; }
            if (!taskData?.client_sys_id && !workData?.client_info?.sys_id) { showToast('error','Client not found on this task'); return; }

            const payload = {
                type: 'debit', // Sale only — Receive happens via Receive Now on the group
                amount, purpose,
                client_id: taskData?.client_sys_id ?? workData?.client_info?.sys_id,
                work_id: taskData?.work_sys_id, task_id: TASK_SYS_ID,
                date, qty_rate: qtyRate,
                ref: note || undefined,
            };
            const result = await _finSave(payload);
            if (result?.success) {
                const files = document.getElementById('fin_clientFiles').files;
                if (files.length && result.sys_id) await finUploadFile(files, result.sys_id, payload.type);
                document.getElementById('fin_client_purpose').value = '';
                document.getElementById('fin_client_amount').value = '';
                document.getElementById('fin_client_qty').value = '';
                document.getElementById('fin_client_rate').value = '';
                document.getElementById('fin_client_note').value = '';
                document.getElementById('fin_clientFiles').value = '';
                document.getElementById('fin_clientFileLabel').textContent = 'Browse or drop files';
            }
        } else {
            const purpose = document.getElementById('fin_vendor_purpose').value.trim();
            const amount  = parseFloat(document.getElementById('fin_vendor_amount').value);
            const date    = document.getElementById('fin_vendor_date').value;
            const note    = document.getElementById('fin_vendor_note').value.trim();
            const qtyRate = _finBuildQtyRate('fin_vendor_qty','fin_vendor_rate');
            const txnMode = document.querySelector('input[name="fin_txn_mode"]:checked').value; // 'realtime' | 'non_realtime'
            if (!purpose || !amount || amount <= 0) { showToast('error','Purpose ও সঠিক amount দিন'); return; }

            const vId = document.getElementById('fin_vendorSelect').value;
            if (!vId) { showToast('error','একটা Vendor সিলেক্ট করুন'); return; }

            const payload = {
                type: mode === 'credit' ? 'credit' : 'debit',
                amount, purpose,
                vendor_id: vId,
                transaction_mode: txnMode,
                work_id: taskData?.work_sys_id, task_id: TASK_SYS_ID,
                date, qty_rate: qtyRate,
                ref: note || undefined,
            };
            if (txnMode === 'realtime') {
                const aId = document.getElementById('fin_accountSelect').value;
                if (!aId) { showToast('error','Real-time payment-এর জন্য একটা Account সিলেক্ট করুন'); return; }
                payload.account_id = aId;
            }
            const result = await _finSave(payload);
            if (result?.success) {
                const files = document.getElementById('fin_vendorFiles').files;
                if (files.length && result.sys_id) await finUploadFile(files, result.sys_id, payload.type);
                document.getElementById('fin_vendor_purpose').value = '';
                document.getElementById('fin_vendor_amount').value = '';
                document.getElementById('fin_vendor_qty').value = '';
                document.getElementById('fin_vendor_rate').value = '';
                document.getElementById('fin_vendor_note').value = '';
                document.getElementById('fin_vendorFiles').value = '';
                document.getElementById('fin_vendorFileLabel').textContent = 'Browse or drop files';
            }
        }
    } catch(e) {
        console.error(e);
        showToast('error', 'Transaction save failed');
    }
}

async function _finSave(payload) {
    const res = await fetch(API.saveFinancial, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
    const json = await res.json();
    if (json.success) { showToast('success', json.message || 'Transaction recorded'); finLoadEntries(); }
    else showToast('error', json.message || 'Failed to save transaction');
    return json;
}

async function finUploadFile(files, entrySysId, type) {
    const entityType = type === 'debit' ? 'receive' : 'payment';
    const fd = new FormData();
    fd.append('entity_type', entityType);
    fd.append('entity_id', entrySysId);
    fd.append('work_sys_id', taskData?.work_sys_id ?? '');
    fd.append('task_sys_id', TASK_SYS_ID);
    for (const f of files) fd.append('files[]', f);
    try {
        const res = await fetch(API.uploadFinFile, { method:'POST', body: fd });
        const json = await res.json();
        if (!json.success) showToast('error', 'File attach failed: ' + (json.message||''));
    } catch(e) { showToast('error','File upload error'); }
}

async function finDeleteTransaction(id) {
    if (!confirm('এই transaction টা মুছে ফেলতে চান?')) return;
    try {
        const res = await fetch(API.deleteFinancial, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ id }) });
        const json = await res.json();
        if (json.success) { showToast('success','Transaction deleted'); finLoadEntries(); }
        else showToast('error', json.message || 'Delete failed');
    } catch(e) { showToast('error','Delete request failed'); }
}

// ── Edit Transaction ──────────────────────────────────────────
function finEditTransaction(id) {
    const t = _finTransactions.find(x => x.sys_id === id);
    if (!t) { showToast('error', 'Transaction not found'); return; }

    document.getElementById('fin_edit_id').value = t.sys_id;
    document.getElementById('fin_edit_original_type').value = t.type;
    document.getElementById('fin_edit_purpose').value = t.purpose || '';
    document.getElementById('fin_edit_note').value = (t.ref && !t.ref.includes('||')) ? t.ref : '';
    document.getElementById('fin_edit_amount').value = t.amount;
    document.getElementById('fin_edit_qty').value = '';
    document.getElementById('fin_edit_rate').value = '';
    if (t.qty_rate) {
        try { const p = typeof t.qty_rate==='string'?JSON.parse(t.qty_rate):t.qty_rate; if (p.qty) document.getElementById('fin_edit_qty').value=p.qty; if (p.rate) document.getElementById('fin_edit_rate').value=p.rate; } catch(e){}
    }
    document.getElementById('fin_edit_date').value = (t.date||'').slice(0,10);

    const type = (t.type||'').toLowerCase();
    const typeDisplay = document.getElementById('fin_edit_type_display');
    const utype = t.user_type || '';
    if (utype === 'client') {
        typeDisplay.innerHTML = type === 'debit'
            ? '<span class="text-indigo-600">DEBIT — Client Deposit</span>'
            : '<span class="text-indigo-600">CREDIT — Client Refund</span>';
    } else if (utype === 'vendor') {
        typeDisplay.innerHTML = `<span class="text-emerald-600">${type==='credit'?'CREDIT — Vendor Payment':'DEBIT — Vendor Refund'} (${escHtml(t.user_name||'')})</span>`;
    } else if (utype === 'account') {
        typeDisplay.innerHTML = `<span class="text-emerald-600">${type==='credit'?'CREDIT — Paid from Account':'DEBIT — Received to Account'} (${escHtml(t.user_name||'')})</span>`;
    } else {
        typeDisplay.innerHTML = type === 'debit' ? 'DEBIT' : 'CREDIT';
    }

    // Vendor/Account/Client identity is fixed per entry now (each is its own
    // linked row) — no re-selection here, only the shared fields below are
    // editable. Editing amount/purpose/date/qty_rate propagates to every
    // linked leg in this transaction (see the amber notice in the modal).
    document.getElementById('fin_edit_reason').value = '';
    document.getElementById('fin_edit_evidenceFile').value = '';
    document.getElementById('fin_edit_evidenceLabel').textContent = 'Browse or drop a file';

    document.getElementById('finEditModal').classList.remove('hidden');
}

async function _finEditEvidenceSelected(input) {
    const file = input.files[0];
    if (!file) return;
    document.getElementById('fin_edit_evidenceLabel').textContent = file.name;

    // Upload immediately so we have a smb_token/file_name to send with the update.
    const id = document.getElementById('fin_edit_id').value;
    const t  = _finTransactions.find(x => x.sys_id === id);
    const fd = new FormData();
    fd.append('entity_type', (t?.type === 'debit') ? 'receive' : 'payment');
    fd.append('entity_id', id);
    fd.append('work_sys_id', taskData?.work_sys_id ?? '');
    fd.append('task_sys_id', TASK_SYS_ID);
    fd.append('files[]', file);

    try {
        const res = await fetch(API.uploadFinFile, { method:'POST', body: fd });
        const json = await res.json();
        if (json.success && json.uploaded?.length) {
            document.getElementById('fin_edit_evidenceFile').value = json.uploaded[0].saved_name || file.name;
        } else {
            showToast('error', 'Evidence upload failed');
            document.getElementById('fin_edit_evidenceLabel').textContent = 'Browse or drop a file';
        }
    } catch(e) {
        showToast('error', 'Evidence upload request failed');
    }
}

async function finUpdateTransaction() {
    const id      = document.getElementById('fin_edit_id').value;
    const purpose = document.getElementById('fin_edit_purpose').value.trim();
    const amount  = parseFloat(document.getElementById('fin_edit_amount').value);
    const date    = document.getElementById('fin_edit_date').value;
    const note    = document.getElementById('fin_edit_note').value.trim();
    const qtyRate = _finBuildQtyRate('fin_edit_qty','fin_edit_rate');
    const reason  = document.getElementById('fin_edit_reason').value.trim();
    const evidenceFile = document.getElementById('fin_edit_evidenceFile').value.trim();

    if (!purpose || !amount || amount <= 0) { showToast('error','Purpose ও সঠিক amount দিন'); return; }
    if (!reason) { showToast('error','Edit-এর reason দিন'); return; }
    if (!evidenceFile) { showToast('error','একটা evidence file upload করুন'); return; }

    // Vendor/Account/Client identity is fixed per entry — only these shared
    // fields are editable, and the change propagates to every linked leg.
    const payload = { id, purpose, amount, date, qty_rate: qtyRate, ref: note || undefined, reason, evidence_file: evidenceFile };

    try {
        const res = await fetch(API.updateFinancial, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
        const json = await res.json();
        if (json.success) { showToast('success', json.message || 'Transaction updated'); closeModal('finEditModal'); finLoadEntries(); }
        else showToast('error', json.message || 'Update failed');
    } catch(e) { showToast('error','Network error'); }
}

let _finGroups = [];

async function finLoadEntries() {
    try {
        const res = await fetch(API.taskFinEntries + '?task_id=' + encodeURIComponent(TASK_SYS_ID));
        const json = await res.json();
        if (!json.success) return;
        _finTransactions = json.finStmts ?? [];
        _finGroups = json.groups ?? [];
        _finRenderSummary(json.summary ?? {});
        _finRenderTable(_finGroups);
    } catch(e) { console.error(e); }
}

function _finRenderSummary(s) {
    const bal = s.balance ?? 0;
    const balPositive = bal >= 0;
    document.getElementById('fin_summaryCards').innerHTML = `
        <div class="rounded-xl p-4 text-white shadow-sm relative overflow-hidden" style="background:linear-gradient(135deg,#6366f1,#4f46e5);">
            <i class="fas fa-arrow-down absolute -right-2 -bottom-2 text-6xl text-white/10"></i>
            <p class="text-xs text-indigo-100 font-medium">Total Client Deposit</p>
            <p class="text-2xl font-bold mt-1">৳${(s.total_deposit ?? 0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2})}</p>
            ${s.total_receivable_open ? `<p class="text-[11px] text-indigo-100 mt-1">৳${s.total_receivable_open.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2})} still receivable</p>` : ''}
        </div>
        <div class="rounded-xl p-4 text-white shadow-sm relative overflow-hidden" style="background:linear-gradient(135deg,#10b981,#059669);">
            <i class="fas fa-arrow-up absolute -right-2 -bottom-2 text-6xl text-white/10"></i>
            <p class="text-xs text-emerald-100 font-medium">Total Vendor Payment</p>
            <p class="text-2xl font-bold mt-1">৳${(s.total_vendor_payment ?? 0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2})}</p>
            ${s.total_payable_open ? `<p class="text-[11px] text-emerald-100 mt-1">৳${s.total_payable_open.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2})} still payable</p>` : ''}
        </div>
        <div class="rounded-xl p-4 text-white shadow-sm relative overflow-hidden" style="background:${balPositive?'linear-gradient(135deg,#0ea5e9,#0284c7)':'linear-gradient(135deg,#f43f5e,#e11d48)'};">
            <i class="fas fa-balance-scale absolute -right-2 -bottom-2 text-6xl text-white/10"></i>
            <p class="text-xs ${balPositive?'text-sky-100':'text-rose-100'} font-medium">Net Balance</p>
            <p class="text-2xl font-bold mt-1">৳${Math.abs(bal).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2})}</p>
            <p class="text-[11px] ${balPositive?'text-sky-100':'text-rose-100'} mt-0.5">${balPositive?'In hand':'Over-paid'}</p>
        </div>
    `;

    // Stats sidebar
    const total = s.entry_count ?? 0;
    document.querySelector('#fin_statTotalCount span:last-child').textContent = s.group_count ?? total;
    document.getElementById('fin_statProgressBar').style.width = Math.min(((s.group_count ?? total)/10)*100, 100) + '%';
    const depositCount = _finTransactions.filter(t => t.user_type==='client').length;
    const paymentCount = _finTransactions.filter(t => t.user_type==='vendor' || t.user_type==='account').length;
    document.querySelector('#fin_statRatio span:last-child').textContent = `${depositCount}:${paymentCount}`;
    document.getElementById('fin_statNetBalance').textContent = `৳${Math.abs(bal).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2})}`;
    document.getElementById('fin_statNetBalance').className = `text-2xl font-bold ${balPositive?'text-emerald-600':'text-rose-600'}`;
    const fc = s.file_count ?? 0;
    document.getElementById('fin_statFileCount').textContent = fc ? `${fc} file(s) attached across ${total} entries` : 'No files attached yet';
}

function _finRenderTable(groups) {
    const body = document.getElementById('fin_tableBody');
    if (!groups.length) {
        body.innerHTML = `<tr><td colspan="7" class="px-4 py-6 text-center text-gray-400"><i class="fas fa-wallet text-xl mb-2 block"></i>কোনো transaction নেই</td></tr>`;
        return;
    }
    const eventBadge = { purchase:'bg-amber-100 text-amber-700', payment:'bg-emerald-100 text-emerald-700', sale:'bg-indigo-100 text-indigo-700', receive:'bg-sky-100 text-sky-700', refund:'bg-rose-100 text-rose-700', other:'bg-gray-100 text-gray-600' };
    body.innerHTML = groups.map((g, gi) => {
        const legs = g.legs || [];
        const primary = legs[0] || {};
        const headBadge = { accounts_payable:'bg-amber-100 text-amber-700', accounts_receivable:'bg-sky-100 text-sky-700', purchase:'bg-emerald-100 text-emerald-700', sales:'bg-indigo-100 text-indigo-700', bank_account:'bg-gray-100 text-gray-600' };
        let fileCount = 0;
        legs.forEach(l => { try { fileCount += (JSON.parse(l.files_json||'[]')||[]).length; } catch(e){} });
        const amount = g.amount ?? parseFloat(primary.amount||0);
        const due = g.due ?? 0;

        return `
        <tr class="hover:bg-gray-50 transition cursor-pointer border-t-2 border-gray-100" onclick="_finToggleGroup(${gi})">
            <td class="px-3 py-2 whitespace-nowrap text-gray-600"><i class="fas fa-chevron-right text-gray-300 text-[10px] mr-1.5 transition-transform" id="fin-chevron-${gi}"></i>${(g.date||'').slice(0,10)}</td>
            <td class="px-3 py-2">
                <span class="px-1.5 py-0.5 rounded ${eventBadge[g.event_type]||'bg-gray-100 text-gray-600'} text-[10px] font-semibold">${escHtml(g.event_label||'Transaction')}</span>
                <span class="text-gray-800 ml-1.5">${escHtml(g.purpose||'—')}</span>
                <div class="text-[11px] text-gray-400 mt-0.5">${escHtml(g.who||'')}</div>
            </td>
            <td class="px-3 py-2 text-gray-400 font-mono text-[11px] hidden sm:table-cell">${escHtml(g.transaction_group_id||'')}</td>
            <td class="px-3 py-2 text-right">
                <div class="font-semibold text-gray-700">৳${amount.toFixed(2)}</div>
                ${due > 0 ? `<div class="text-[11px] text-rose-500 font-medium">৳${due.toFixed(2)} due</div>` : (g.event_type==='purchase'||g.event_type==='sale' ? `<div class="text-[11px] text-emerald-500 font-medium">Fully settled</div>` : '')}
            </td>
            <td class="px-3 py-2">${fileCount ? `<i class="fas fa-paperclip text-indigo-400"></i> ${fileCount}` : '—'}</td>
            <td class="px-3 py-2 whitespace-nowrap" onclick="event.stopPropagation()">
                <button onclick="finEditTransaction('${primary.sys_id}')" class="px-1.5 py-1 bg-blue-50 hover:bg-blue-100 text-blue-600 rounded mr-1" title="Edit"><i class="fas fa-edit text-[10px]"></i></button>
                <button onclick="finDeleteTransaction('${primary.sys_id}')" class="px-1.5 py-1 bg-red-50 hover:bg-red-100 text-red-600 rounded" title="Delete (removes all linked legs)"><i class="fas fa-trash text-[10px]"></i></button>
            </td>
        </tr>
        <tr id="fin-group-${gi}" class="hidden">
            <td colspan="6" class="px-3 pb-3 bg-gray-50">
                <p class="text-[11px] text-gray-400 px-1 py-1.5"><i class="fas fa-info-circle mr-1"></i>Edit করলে এই transaction-এর সব লিংকড অংশ (নিচের প্রতিটা row) একসাথে পরিবর্তিত হবে।</p>
                <table class="w-full text-xs border border-gray-200 rounded-lg overflow-hidden bg-white">
                    <thead class="bg-gray-100"><tr>
                        <th class="px-2 py-1.5 text-left font-medium text-gray-500">Account Head</th>
                        <th class="px-2 py-1.5 text-left font-medium text-gray-500">Who</th>
                        <th class="px-2 py-1.5 text-left font-medium text-gray-500">Type</th>
                        <th class="px-2 py-1.5 text-right font-medium text-gray-500">Amount</th>
                        <th class="px-2 py-1.5 text-left font-medium text-gray-500">Files</th>
                    </tr></thead>
                    <tbody>
                        ${legs.map(l => {
                            const isDebit = (l.type||'').toLowerCase() === 'debit';
                            let n = 0; try { n = (JSON.parse(l.files_json||'[]')||[]).length; } catch(e){}
                            return `<tr class="border-t border-gray-100">
                                <td class="px-2 py-1.5"><span class="px-1.5 py-0.5 rounded ${headBadge[l.account_head]||'bg-gray-100 text-gray-600'} text-[10px] font-semibold">${escHtml(l.account_head||'—')}</span></td>
                                <td class="px-2 py-1.5 text-gray-700">${escHtml(l.user_name||'—')}</td>
                                <td class="px-2 py-1.5"><span class="text-[10px] font-bold ${isDebit?'text-green-600':'text-red-600'}">${isDebit?'DEBIT':'CREDIT'}</span></td>
                                <td class="px-2 py-1.5 text-right font-medium">৳${parseFloat(l.amount||0).toFixed(2)}</td>
                                <td class="px-2 py-1.5">${n ? `<a href="${API.fileServe}?fin_id=${l.sys_id}" target="_blank" class="text-indigo-500 hover:underline" onclick="event.stopPropagation()"><i class="fas fa-paperclip"></i> ${n}</a>` : '—'}
                                    <label class="ml-1 cursor-pointer text-amber-500" title="Attach file" onclick="event.stopPropagation()">
                                        <i class="fas fa-plus-circle"></i>
                                        <input type="file" multiple class="hidden" onchange="finUploadFile(this.files,'${l.sys_id}','${l.type}',this)">
                                    </label>
                                </td>
                            </tr>`;
                        }).join('')}
                    </tbody>
                </table>
                ${g.payable ? `
                <div class="mt-2 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs font-semibold text-amber-700"><i class="fas fa-hand-holding-usd mr-1"></i>৳${due.toFixed(2)} বাকি আছে ${escHtml(g.who)}-কে</p>
                        <button onclick="_finTogglePayForm(${gi})" class="text-xs font-semibold text-amber-700 hover:text-amber-900"><i class="fas fa-plus-circle mr-1"></i>Pay Now</button>
                    </div>
                    <div id="fin-payform-${gi}" class="hidden space-y-2">
                        <div>
                            <label class="block text-[11px] font-medium text-gray-600 mb-1">Own Account</label>
                            <div class="relative" id="fin_payAccountWrap${gi}">
                                <input id="fin_payAccountSearch${gi}" placeholder="Search for an account…" class="f-input text-xs" autocomplete="off"
                                    oninput="_finFilterPayAccount(${gi}, this.value)" onfocus="_finFilterPayAccount(${gi}, this.value)">
                                <ul id="fin_payAccountDrop${gi}" class="absolute w-full bg-white border border-gray-200 rounded-lg mt-1 max-h-40 overflow-auto shadow-xl hidden z-50"></ul>
                            </div>
                            <input type="hidden" id="fin_payAccountId${gi}">
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-gray-600 mb-1">Amount ৳ <span class="text-gray-400 font-normal">(সম্পূর্ণ বা আংশিক)</span></label>
                            <input type="number" step="0.01" min="0.01" max="${due}" id="fin_payAmount${gi}" value="${due.toFixed(2)}" class="f-input text-xs">
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-gray-600 mb-1">Payment Method</label>
                            <select id="fin_payMethod${gi}" class="f-input text-xs" onchange="_finTogglePayInstrument(${gi})">
                                <option value="cash">Cash</option>
                                <option value="npsb">NPSB</option>
                                <option value="rtgs">RTGS</option>
                                <option value="bftn">BFTN</option>
                                <option value="eft">EFT</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>
                        <div id="fin_payInstrumentWrap${gi}" class="hidden">
                            <label class="block text-[11px] font-medium text-gray-600 mb-1">Cheque/Instrument No.</label>
                            <input type="text" id="fin_payInstrumentNo${gi}" placeholder="Cheque number" class="f-input text-xs">
                            <p class="text-[10px] text-gray-400 mt-0.5">এই পেমেন্ট hold থাকবে instrument clear না হওয়া পর্যন্ত — account balance তখনই কমবে।</p>
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-gray-600 mb-1">Date</label>
                            <input type="date" id="fin_payDate${gi}" value="${new Date().toISOString().slice(0,10)}" class="f-input text-xs">
                        </div>
                        <button onclick="_finSubmitPayNow(${gi}, '${g.transaction_group_id}', '${g.vendor_id||''}')" class="w-full py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-semibold transition">
                            <i class="fas fa-check mr-1"></i>Confirm Payment
                        </button>
                    </div>
                </div>` : ''}
                ${(g.event_type === 'purchase') ? `
                <div class="mt-2 p-3 bg-rose-50 border border-rose-200 rounded-lg">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs font-semibold text-rose-700"><i class="fas fa-undo mr-1"></i>${escHtml(g.who)}-এর থেকে Refund</p>
                        <button onclick="_finToggleRefundForm(${gi})" class="text-xs font-semibold text-rose-700 hover:text-rose-900"><i class="fas fa-plus-circle mr-1"></i>Refund</button>
                    </div>
                    <div id="fin-refundform-${gi}" class="hidden space-y-2">
                        <p class="text-[11px] text-gray-400">যেকোনো একটা দিন — Refund Amount অথবা Refund Charge, অন্যটা auto হবে।</p>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-[11px] font-medium text-gray-600 mb-1">Refund Amount ৳</label>
                                <input type="number" step="0.01" min="0" id="fin_refundAmount${gi}" placeholder="0.00" class="f-input text-xs" oninput="_finRefundCalc(${gi}, 'amount', ${g.amount})">
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-gray-600 mb-1">Refund Charge ৳</label>
                                <input type="number" step="0.01" min="0" id="fin_refundCharge${gi}" placeholder="0.00" class="f-input text-xs" oninput="_finRefundCalc(${gi}, 'charge', ${g.amount})">
                            </div>
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-gray-600 mb-1">Date</label>
                            <input type="date" id="fin_refundDate${gi}" value="${new Date().toISOString().slice(0,10)}" class="f-input text-xs">
                        </div>
                        <button onclick="_finSubmitRefundVendor(${gi}, '${g.transaction_group_id}', '${g.vendor_id||''}')" class="w-full py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-semibold transition">
                            <i class="fas fa-check mr-1"></i>Declare Refund
                        </button>
                    </div>
                </div>` : ''}
                ${(g.event_type === 'vendor_refund' && g.refund_receivable) ? `
                <div class="mt-2 p-3 bg-teal-50 border border-teal-200 rounded-lg">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs font-semibold text-teal-700"><i class="fas fa-hourglass-half mr-1"></i>৳${due.toFixed(2)} refund এখনো account-এ আসেনি</p>
                        <button onclick="_finToggleRefundSettleForm(${gi})" class="text-xs font-semibold text-teal-700 hover:text-teal-900"><i class="fas fa-plus-circle mr-1"></i>Money Received</button>
                    </div>
                    <div id="fin-refundsettleform-${gi}" class="hidden space-y-2">
                        <div>
                            <label class="block text-[11px] font-medium text-gray-600 mb-1">Own Account</label>
                            <div class="relative" id="fin_refundSettleAccountWrap${gi}">
                                <input id="fin_refundSettleAccountSearch${gi}" placeholder="Search for an account…" class="f-input text-xs" autocomplete="off"
                                    oninput="_finFilterRefundSettleAccount(${gi}, this.value)" onfocus="_finFilterRefundSettleAccount(${gi}, this.value)">
                                <ul id="fin_refundSettleAccountDrop${gi}" class="absolute w-full bg-white border border-gray-200 rounded-lg mt-1 max-h-40 overflow-auto shadow-xl hidden z-50"></ul>
                            </div>
                            <input type="hidden" id="fin_refundSettleAccountId${gi}">
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-gray-600 mb-1">Amount ৳</label>
                            <input type="number" step="0.01" min="0.01" max="${due}" id="fin_refundSettleAmount${gi}" value="${due.toFixed(2)}" class="f-input text-xs">
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-gray-600 mb-1">Payment Method</label>
                            <select id="fin_refundSettleMethod${gi}" class="f-input text-xs" onchange="_finToggleRefundSettleInstrument(${gi})">
                                <option value="cash">Cash</option>
                                <option value="npsb">NPSB</option>
                                <option value="rtgs">RTGS</option>
                                <option value="bftn">BFTN</option>
                                <option value="eft">EFT</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>
                        <div id="fin_refundSettleInstrumentWrap${gi}" class="hidden">
                            <label class="block text-[11px] font-medium text-gray-600 mb-1">Cheque/Instrument No.</label>
                            <input type="text" id="fin_refundSettleInstrumentNo${gi}" placeholder="Cheque number" class="f-input text-xs">
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-gray-600 mb-1">Date</label>
                            <input type="date" id="fin_refundSettleDate${gi}" value="${new Date().toISOString().slice(0,10)}" class="f-input text-xs">
                        </div>
                        <button onclick="_finSubmitRefundSettleVendor(${gi}, '${g.transaction_group_id}', '${g.vendor_id||''}')" class="w-full py-1.5 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-xs font-semibold transition">
                            <i class="fas fa-check mr-1"></i>Confirm Received
                        </button>
                    </div>
                </div>` : ''}
                ${g.receivable ? `
                <div class="mt-2 p-3 bg-sky-50 border border-sky-200 rounded-lg">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs font-semibold text-sky-700"><i class="fas fa-hand-holding-usd mr-1"></i>৳${due.toFixed(2)} বাকি আছে ${escHtml(g.who)}-এর কাছ থেকে</p>
                        <button onclick="_finToggleReceiveForm(${gi})" class="text-xs font-semibold text-sky-700 hover:text-sky-900"><i class="fas fa-plus-circle mr-1"></i>Receive Now</button>
                    </div>
                    <div id="fin-receiveform-${gi}" class="hidden space-y-2">
                        <div>
                            <label class="block text-[11px] font-medium text-gray-600 mb-1">Own Account</label>
                            <div class="relative" id="fin_receiveAccountWrap${gi}">
                                <input id="fin_receiveAccountSearch${gi}" placeholder="Search for an account…" class="f-input text-xs" autocomplete="off"
                                    oninput="_finFilterReceiveAccount(${gi}, this.value)" onfocus="_finFilterReceiveAccount(${gi}, this.value)">
                                <ul id="fin_receiveAccountDrop${gi}" class="absolute w-full bg-white border border-gray-200 rounded-lg mt-1 max-h-40 overflow-auto shadow-xl hidden z-50"></ul>
                            </div>
                            <input type="hidden" id="fin_receiveAccountId${gi}">
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-gray-600 mb-1">Amount ৳ <span class="text-gray-400 font-normal">(সম্পূর্ণ বা আংশিক)</span></label>
                            <input type="number" step="0.01" min="0.01" max="${due}" id="fin_receiveAmount${gi}" value="${due.toFixed(2)}" class="f-input text-xs">
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-gray-600 mb-1">Payment Method</label>
                            <select id="fin_receiveMethod${gi}" class="f-input text-xs" onchange="_finToggleReceiveInstrument(${gi})">
                                <option value="cash">Cash</option>
                                <option value="npsb">NPSB</option>
                                <option value="rtgs">RTGS</option>
                                <option value="bftn">BFTN</option>
                                <option value="eft">EFT</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>
                        <div id="fin_receiveInstrumentWrap${gi}" class="hidden">
                            <label class="block text-[11px] font-medium text-gray-600 mb-1">Cheque/Instrument No.</label>
                            <input type="text" id="fin_receiveInstrumentNo${gi}" placeholder="Cheque number" class="f-input text-xs">
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-gray-600 mb-1">Date</label>
                            <input type="date" id="fin_receiveDate${gi}" value="${new Date().toISOString().slice(0,10)}" class="f-input text-xs">
                        </div>
                        <button onclick="_finSubmitReceiveNow(${gi}, '${g.transaction_group_id}', '${g.client_id||''}')" class="w-full py-1.5 bg-sky-600 hover:bg-sky-700 text-white rounded-lg text-xs font-semibold transition">
                            <i class="fas fa-check mr-1"></i>Confirm Receive
                        </button>
                    </div>
                </div>` : ''}
                ${(g.event_type === 'sale') ? `
                <div class="mt-2 p-3 bg-rose-50 border border-rose-200 rounded-lg">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs font-semibold text-rose-700"><i class="fas fa-undo mr-1"></i>${escHtml(g.who)}-কে Refund</p>
                        <button onclick="_finToggleRefundForm(${gi})" class="text-xs font-semibold text-rose-700 hover:text-rose-900"><i class="fas fa-plus-circle mr-1"></i>Refund</button>
                    </div>
                    <div id="fin-refundform-${gi}" class="hidden space-y-2">
                        <p class="text-[11px] text-gray-400">যেকোনো একটা দিন — Refund Amount অথবা Refund Charge, অন্যটা auto হবে।</p>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-[11px] font-medium text-gray-600 mb-1">Refund Amount ৳</label>
                                <input type="number" step="0.01" min="0" id="fin_refundAmount${gi}" placeholder="0.00" class="f-input text-xs" oninput="_finRefundCalc(${gi}, 'amount', ${g.amount})">
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-gray-600 mb-1">Refund Charge ৳</label>
                                <input type="number" step="0.01" min="0" id="fin_refundCharge${gi}" placeholder="0.00" class="f-input text-xs" oninput="_finRefundCalc(${gi}, 'charge', ${g.amount})">
                            </div>
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-gray-600 mb-1">Date</label>
                            <input type="date" id="fin_refundDate${gi}" value="${new Date().toISOString().slice(0,10)}" class="f-input text-xs">
                        </div>
                        <button onclick="_finSubmitRefundClient(${gi}, '${g.transaction_group_id}', '${g.client_id||''}')" class="w-full py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-semibold transition">
                            <i class="fas fa-check mr-1"></i>Declare Refund
                        </button>
                    </div>
                </div>` : ''}
                ${(g.event_type === 'client_refund' && g.refund_payable) ? `
                <div class="mt-2 p-3 bg-orange-50 border border-orange-200 rounded-lg">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs font-semibold text-orange-700"><i class="fas fa-hourglass-half mr-1"></i>৳${due.toFixed(2)} refund এখনো দেওয়া হয়নি</p>
                        <button onclick="_finToggleRefundSettleForm(${gi})" class="text-xs font-semibold text-orange-700 hover:text-orange-900"><i class="fas fa-plus-circle mr-1"></i>Money Paid</button>
                    </div>
                    <div id="fin-refundsettleform-${gi}" class="hidden space-y-2">
                        <div>
                            <label class="block text-[11px] font-medium text-gray-600 mb-1">Own Account</label>
                            <div class="relative" id="fin_refundSettleAccountWrap${gi}">
                                <input id="fin_refundSettleAccountSearch${gi}" placeholder="Search for an account…" class="f-input text-xs" autocomplete="off"
                                    oninput="_finFilterRefundSettleAccount(${gi}, this.value)" onfocus="_finFilterRefundSettleAccount(${gi}, this.value)">
                                <ul id="fin_refundSettleAccountDrop${gi}" class="absolute w-full bg-white border border-gray-200 rounded-lg mt-1 max-h-40 overflow-auto shadow-xl hidden z-50"></ul>
                            </div>
                            <input type="hidden" id="fin_refundSettleAccountId${gi}">
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-gray-600 mb-1">Amount ৳</label>
                            <input type="number" step="0.01" min="0.01" max="${due}" id="fin_refundSettleAmount${gi}" value="${due.toFixed(2)}" class="f-input text-xs">
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-gray-600 mb-1">Payment Method</label>
                            <select id="fin_refundSettleMethod${gi}" class="f-input text-xs" onchange="_finToggleRefundSettleInstrument(${gi})">
                                <option value="cash">Cash</option>
                                <option value="npsb">NPSB</option>
                                <option value="rtgs">RTGS</option>
                                <option value="bftn">BFTN</option>
                                <option value="eft">EFT</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>
                        <div id="fin_refundSettleInstrumentWrap${gi}" class="hidden">
                            <label class="block text-[11px] font-medium text-gray-600 mb-1">Cheque/Instrument No.</label>
                            <input type="text" id="fin_refundSettleInstrumentNo${gi}" placeholder="Cheque number" class="f-input text-xs">
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-gray-600 mb-1">Date</label>
                            <input type="date" id="fin_refundSettleDate${gi}" value="${new Date().toISOString().slice(0,10)}" class="f-input text-xs">
                        </div>
                        <button onclick="_finSubmitRefundSettleClient(${gi}, '${g.transaction_group_id}', '${g.client_id||''}')" class="w-full py-1.5 bg-orange-600 hover:bg-orange-700 text-white rounded-lg text-xs font-semibold transition">
                            <i class="fas fa-check mr-1"></i>Confirm Paid
                        </button>
                    </div>
                </div>` : ''}
            </td>
        </tr>`;
    }).join('');
}

// ── Pay Now (settling an unpaid/partially-paid vendor purchase) ──────────
function _finTogglePayForm(gi) {
    document.getElementById(`fin-payform-${gi}`).classList.toggle('hidden');
}

function _finFilterPayAccount(gi, q) {
    const dd = document.getElementById(`fin_payAccountDrop${gi}`);
    const v = q.toLowerCase().trim();
    const list = v ? _finAccounts.filter(a => (a.acc_name||'').toLowerCase().includes(v)) : _finAccounts.slice(0, 15);
    if (!list.length) { dd.innerHTML = `<li class="px-3 py-2 text-center text-gray-400 text-xs">কিছু পাওয়া যায়নি</li>`; dd.classList.remove('hidden'); return; }
    dd.innerHTML = list.map(a => `
        <li class="px-3 py-2 cursor-pointer hover:bg-amber-50 border-b last:border-b-0 text-xs text-gray-800"
            onclick="_finSelectPayAccount(${gi},'${a.sys_id}','${escHtml(a.acc_name||a.sys_id).replace(/'/g,"\\'")}')">
            ${escHtml(a.acc_name ?? a.sys_id)}
        </li>`).join('');
    dd.classList.remove('hidden');
}
function _finSelectPayAccount(gi, sysId, name) {
    document.getElementById(`fin_payAccountSearch${gi}`).value = name;
    document.getElementById(`fin_payAccountId${gi}`).value = sysId;
    document.getElementById(`fin_payAccountDrop${gi}`).classList.add('hidden');
}

// Generic instrument-field toggle, reused by every Payment Method dropdown
// in this file (Pay Now, Receive Now, Refund Settlement forms) — shows the
// Instrument No. field only when Cheque is selected.
function _finToggleInstrumentField(selectId, wrapId) {
    const method = document.getElementById(selectId).value;
    document.getElementById(wrapId).classList.toggle('hidden', method !== 'cheque');
}
function _finTogglePayInstrument(gi) { _finToggleInstrumentField(`fin_payMethod${gi}`, `fin_payInstrumentWrap${gi}`); }
function _finToggleReceiveInstrument(gi) { _finToggleInstrumentField(`fin_receiveMethod${gi}`, `fin_receiveInstrumentWrap${gi}`); }
function _finToggleRefundSettleInstrument(gi) { _finToggleInstrumentField(`fin_refundSettleMethod${gi}`, `fin_refundSettleInstrumentWrap${gi}`); }

async function _finSubmitPayNow(gi, purchaseGroupId, vendorId) {
    const accountId = document.getElementById(`fin_payAccountId${gi}`).value;
    const amount = parseFloat(document.getElementById(`fin_payAmount${gi}`).value);
    const date = document.getElementById(`fin_payDate${gi}`).value;
    const paymentMethod = document.getElementById(`fin_payMethod${gi}`).value;
    const instrumentNo = document.getElementById(`fin_payInstrumentNo${gi}`)?.value.trim() || '';

    if (!accountId) { showToast('error', 'একটা Account সিলেক্ট করুন'); return; }
    if (!amount || amount <= 0) { showToast('error', 'সঠিক amount দিন'); return; }
    if (!vendorId) { showToast('error', 'Vendor তথ্য পাওয়া যায়নি'); return; }
    if (paymentMethod === 'cheque' && !instrumentNo) { showToast('error', 'Cheque number দিন'); return; }

    try {
        const res = await fetch(API.payOutstanding, {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({
                purchase_group_id: purchaseGroupId, vendor_id: vendorId, account_id: accountId,
                amount, date, payment_method: paymentMethod, instrument_no: instrumentNo || undefined,
                work_id: taskData?.work_sys_id, task_id: TASK_SYS_ID,
            }),
        });
        const json = await res.json();
        if (json.success) {
            showToast('success', json.message || 'Payment recorded');
            finLoadEntries();
        } else showToast('error', json.message || 'Payment failed');
    } catch(e) { showToast('error', 'Network error'); }
}

// ── Receive Now (settling an unreceived/partially-received client sale) ──
function _finToggleReceiveForm(gi) {
    document.getElementById(`fin-receiveform-${gi}`).classList.toggle('hidden');
}

function _finFilterReceiveAccount(gi, q) {
    const dd = document.getElementById(`fin_receiveAccountDrop${gi}`);
    const v = q.toLowerCase().trim();
    const list = v ? _finAccounts.filter(a => (a.acc_name||'').toLowerCase().includes(v)) : _finAccounts.slice(0, 15);
    if (!list.length) { dd.innerHTML = `<li class="px-3 py-2 text-center text-gray-400 text-xs">কিছু পাওয়া যায়নি</li>`; dd.classList.remove('hidden'); return; }
    dd.innerHTML = list.map(a => `
        <li class="px-3 py-2 cursor-pointer hover:bg-sky-50 border-b last:border-b-0 text-xs text-gray-800"
            onclick="_finSelectReceiveAccount(${gi},'${a.sys_id}','${escHtml(a.acc_name||a.sys_id).replace(/'/g,"\\'")}')">
            ${escHtml(a.acc_name ?? a.sys_id)}
        </li>`).join('');
    dd.classList.remove('hidden');
}
function _finSelectReceiveAccount(gi, sysId, name) {
    document.getElementById(`fin_receiveAccountSearch${gi}`).value = name;
    document.getElementById(`fin_receiveAccountId${gi}`).value = sysId;
    document.getElementById(`fin_receiveAccountDrop${gi}`).classList.add('hidden');
}

async function _finSubmitReceiveNow(gi, saleGroupId, clientId) {
    const accountId = document.getElementById(`fin_receiveAccountId${gi}`).value;
    const amount = parseFloat(document.getElementById(`fin_receiveAmount${gi}`).value);
    const date = document.getElementById(`fin_receiveDate${gi}`).value;
    const paymentMethod = document.getElementById(`fin_receiveMethod${gi}`).value;
    const instrumentNo = document.getElementById(`fin_receiveInstrumentNo${gi}`)?.value.trim() || '';

    if (!accountId) { showToast('error', 'একটা Account সিলেক্ট করুন'); return; }
    if (!amount || amount <= 0) { showToast('error', 'সঠিক amount দিন'); return; }
    if (!clientId) { showToast('error', 'Client তথ্য পাওয়া যায়নি'); return; }
    if (paymentMethod === 'cheque' && !instrumentNo) { showToast('error', 'Cheque number দিন'); return; }

    try {
        const res = await fetch(API.receiveOutstanding, {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({
                sale_group_id: saleGroupId, client_id: clientId, account_id: accountId,
                amount, date, payment_method: paymentMethod, instrument_no: instrumentNo || undefined,
                work_id: taskData?.work_sys_id, task_id: TASK_SYS_ID,
            }),
        });
        const json = await res.json();
        if (json.success) {
            showToast('success', json.message || 'Receive recorded');
            finLoadEntries();
        } else showToast('error', json.message || 'Receive failed');
    } catch(e) { showToast('error', 'Network error'); }
}

// ── Refund (declare) — shared toggle + auto-calc for both vendor and client ──
function _finToggleRefundForm(gi) {
    document.getElementById(`fin-refundform-${gi}`).classList.toggle('hidden');
}

function _finRefundCalc(gi, editedField, refundableTotal) {
    const amountEl = document.getElementById(`fin_refundAmount${gi}`);
    const chargeEl = document.getElementById(`fin_refundCharge${gi}`);
    if (editedField === 'amount') {
        const amt = parseFloat(amountEl.value) || 0;
        chargeEl.value = Math.max(refundableTotal - amt, 0).toFixed(2);
    } else {
        const chg = parseFloat(chargeEl.value) || 0;
        amountEl.value = Math.max(refundableTotal - chg, 0).toFixed(2);
    }
}

async function _finSubmitRefundVendor(gi, purchaseGroupId, vendorId) {
    const amountVal = document.getElementById(`fin_refundAmount${gi}`).value;
    const chargeVal = document.getElementById(`fin_refundCharge${gi}`).value;
    const date = document.getElementById(`fin_refundDate${gi}`).value;

    if (!vendorId) { showToast('error', 'Vendor তথ্য পাওয়া যায়নি'); return; }
    if (!amountVal && !chargeVal) { showToast('error', 'Refund Amount অথবা Refund Charge দিন'); return; }

    try {
        const payload = { purchase_group_id: purchaseGroupId, vendor_id: vendorId, date, work_id: taskData?.work_sys_id, task_id: TASK_SYS_ID };
        if (amountVal) payload.refund_amount = parseFloat(amountVal); else payload.refund_charge = parseFloat(chargeVal);
        const res = await fetch(API.refundVendor, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
        const json = await res.json();
        if (json.success) { showToast('success', json.message || 'Refund declared'); finLoadEntries(); }
        else showToast('error', json.message || 'Refund failed');
    } catch(e) { showToast('error', 'Network error'); }
}

async function _finSubmitRefundClient(gi, saleGroupId, clientId) {
    const amountVal = document.getElementById(`fin_refundAmount${gi}`).value;
    const chargeVal = document.getElementById(`fin_refundCharge${gi}`).value;
    const date = document.getElementById(`fin_refundDate${gi}`).value;

    if (!clientId) { showToast('error', 'Client তথ্য পাওয়া যায়নি'); return; }
    if (!amountVal && !chargeVal) { showToast('error', 'Refund Amount অথবা Refund Charge দিন'); return; }

    try {
        const payload = { sale_group_id: saleGroupId, client_id: clientId, date, work_id: taskData?.work_sys_id, task_id: TASK_SYS_ID };
        if (amountVal) payload.refund_amount = parseFloat(amountVal); else payload.refund_charge = parseFloat(chargeVal);
        const res = await fetch(API.refundClient, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
        const json = await res.json();
        if (json.success) { showToast('success', json.message || 'Refund declared'); finLoadEntries(); }
        else showToast('error', json.message || 'Refund failed');
    } catch(e) { showToast('error', 'Network error'); }
}

// ── Refund settlement — shared toggle/account-picker for both sides ──
function _finToggleRefundSettleForm(gi) {
    document.getElementById(`fin-refundsettleform-${gi}`).classList.toggle('hidden');
}

function _finFilterRefundSettleAccount(gi, q) {
    const dd = document.getElementById(`fin_refundSettleAccountDrop${gi}`);
    const v = q.toLowerCase().trim();
    const list = v ? _finAccounts.filter(a => (a.acc_name||'').toLowerCase().includes(v)) : _finAccounts.slice(0, 15);
    if (!list.length) { dd.innerHTML = `<li class="px-3 py-2 text-center text-gray-400 text-xs">কিছু পাওয়া যায়নি</li>`; dd.classList.remove('hidden'); return; }
    dd.innerHTML = list.map(a => `
        <li class="px-3 py-2 cursor-pointer hover:bg-teal-50 border-b last:border-b-0 text-xs text-gray-800"
            onclick="_finSelectRefundSettleAccount(${gi},'${a.sys_id}','${escHtml(a.acc_name||a.sys_id).replace(/'/g,"\\'")}')">
            ${escHtml(a.acc_name ?? a.sys_id)}
        </li>`).join('');
    dd.classList.remove('hidden');
}
function _finSelectRefundSettleAccount(gi, sysId, name) {
    document.getElementById(`fin_refundSettleAccountSearch${gi}`).value = name;
    document.getElementById(`fin_refundSettleAccountId${gi}`).value = sysId;
    document.getElementById(`fin_refundSettleAccountDrop${gi}`).classList.add('hidden');
}

async function _finSubmitRefundSettleVendor(gi, refundGroupId, vendorId) {
    const accountId = document.getElementById(`fin_refundSettleAccountId${gi}`).value;
    const amount = parseFloat(document.getElementById(`fin_refundSettleAmount${gi}`).value);
    const date = document.getElementById(`fin_refundSettleDate${gi}`).value;
    const paymentMethod = document.getElementById(`fin_refundSettleMethod${gi}`).value;
    const instrumentNo = document.getElementById(`fin_refundSettleInstrumentNo${gi}`)?.value.trim() || '';

    if (!accountId) { showToast('error', 'একটা Account সিলেক্ট করুন'); return; }
    if (!amount || amount <= 0) { showToast('error', 'সঠিক amount দিন'); return; }
    if (!vendorId) { showToast('error', 'Vendor তথ্য পাওয়া যায়নি'); return; }
    if (paymentMethod === 'cheque' && !instrumentNo) { showToast('error', 'Cheque number দিন'); return; }

    try {
        const res = await fetch(API.refundSettleVendor, {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ refund_group_id: refundGroupId, vendor_id: vendorId, account_id: accountId, amount, date, payment_method: paymentMethod, instrument_no: instrumentNo || undefined, work_id: taskData?.work_sys_id, task_id: TASK_SYS_ID }),
        });
        const json = await res.json();
        if (json.success) { showToast('success', json.message || 'Refund settled'); finLoadEntries(); }
        else showToast('error', json.message || 'Settlement failed');
    } catch(e) { showToast('error', 'Network error'); }
}

async function _finSubmitRefundSettleClient(gi, refundGroupId, clientId) {
    const accountId = document.getElementById(`fin_refundSettleAccountId${gi}`).value;
    const amount = parseFloat(document.getElementById(`fin_refundSettleAmount${gi}`).value);
    const date = document.getElementById(`fin_refundSettleDate${gi}`).value;
    const paymentMethod = document.getElementById(`fin_refundSettleMethod${gi}`).value;
    const instrumentNo = document.getElementById(`fin_refundSettleInstrumentNo${gi}`)?.value.trim() || '';

    if (!accountId) { showToast('error', 'একটা Account সিলেক্ট করুন'); return; }
    if (!amount || amount <= 0) { showToast('error', 'সঠিক amount দিন'); return; }
    if (!clientId) { showToast('error', 'Client তথ্য পাওয়া যায়নি'); return; }
    if (paymentMethod === 'cheque' && !instrumentNo) { showToast('error', 'Cheque number দিন'); return; }

    try {
        const res = await fetch(API.refundSettleClient, {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ refund_group_id: refundGroupId, client_id: clientId, account_id: accountId, amount, date, payment_method: paymentMethod, instrument_no: instrumentNo || undefined, work_id: taskData?.work_sys_id, task_id: TASK_SYS_ID }),
        });
        const json = await res.json();
        if (json.success) { showToast('success', json.message || 'Refund settled'); finLoadEntries(); }
        else showToast('error', json.message || 'Settlement failed');
    } catch(e) { showToast('error', 'Network error'); }
}


function _finToggleGroup(gi) {
    const row = document.getElementById(`fin-group-${gi}`);
    const chevron = document.getElementById(`fin-chevron-${gi}`);
    row.classList.toggle('hidden');
    chevron.style.transform = row.classList.contains('hidden') ? '' : 'rotate(90deg)';
}

// ════════════════════════════════════════════════════════════
// DOCUMENTS TAB (Raw files / Generated documents / Financial evidence)
// ════════════════════════════════════════════════════════════
function initDocumentsTab() {
    const area = document.getElementById('documentsTabArea');
    area.innerHTML = `
        <div class="sc p-4 mb-4">
            <h3 class="text-sm font-semibold text-gray-800 mb-3"><i class="fas fa-file-import mr-1.5 text-blue-500"></i>Raw Files</h3>
            <div id="doc_rawFiles" class="text-xs text-gray-400 text-center py-6"><i class="fas fa-spinner fa-spin"></i></div>
        </div>
        <div class="sc p-4 mb-4">
            <h3 class="text-sm font-semibold text-gray-800 mb-3"><i class="fas fa-file-pdf mr-1.5 text-red-500"></i>Generated Documents</h3>
            <div class="text-xs text-gray-400 text-center py-6">Invoice/receipt PDF — on-the-fly generation যোগ হবে পরের ধাপে</div>
        </div>
        <div class="sc p-4">
            <h3 class="text-sm font-semibold text-gray-800 mb-3"><i class="fas fa-receipt mr-1.5 text-emerald-500"></i>Financial Evidence</h3>
            <div id="doc_finFiles" class="text-xs text-gray-400 text-center py-6"><i class="fas fa-spinner fa-spin"></i></div>
        </div>
    `;
    _docRenderFinEvidence();
}

// Financial evidence — reuses whatever finLoadEntries already fetched (or fetches fresh if not loaded)
async function _docRenderFinEvidence() {
    try {
        if (!_finTransactions.length) await finLoadEntries();
        const withFiles = _finTransactions.filter(t => { try { return (JSON.parse(t.files_json||'[]')||[]).length>0; } catch(e){ return false; } });
        const box = document.getElementById('doc_finFiles');
        if (!withFiles.length) { box.innerHTML = '<div class="text-xs text-gray-400 text-center py-6">এখনো কোনো financial evidence file নেই</div>'; }
        else {
            box.innerHTML = `<div class="grid grid-cols-2 sm:grid-cols-4 gap-2">` + withFiles.map(t => {
                let n = 0; try { n = (JSON.parse(t.files_json||'[]')||[]).length; } catch(e){}
                return `<a href="${API.fileServe}?fin_id=${t.sys_id}" target="_blank" class="p-2.5 border border-gray-100 rounded-lg hover:bg-gray-50 transition text-xs">
                    <i class="fas fa-paperclip text-gray-400 mb-1 block"></i>
                    <span class="text-gray-700 font-medium block truncate">${escHtml(t.purpose||t.sys_id)}</span>
                    <span class="text-gray-400">${n} file(s)</span>
                </a>`;
            }).join('') + `</div>`;
        }
        document.getElementById('doc_rawFiles').innerHTML = _docRawFilesHtml();
    } catch(e) {
        console.error('_docRenderFinEvidence failed:', e);
        document.getElementById('doc_rawFiles').innerHTML = `<div class="text-xs text-red-400 text-center py-6">Error: ${escHtml(e.message)}</div>`;
    }
}

function _docRawFilesHtml() {
    const allConfs = _atData?.at_confirmations ?? [];
    // Only this task's own confirmation — a work can have multiple
    // confirmations (and multiple tasks), each task shows only its own files.
    const confs = taskData?.confirmation_sys_id
        ? allConfs.filter(c => c.sys_id === taskData.confirmation_sys_id)
        : allConfs;
    const withFiles = confs.filter(c => Array.isArray(c.files_json) && c.files_json.length > 0);
    if (!withFiles.length) return '<div class="text-xs text-gray-400 text-center py-6">কোনো raw confirmation file নেই</div>';
    return `<div class="grid grid-cols-2 sm:grid-cols-4 gap-2">` + withFiles.map(c => `
        <a href="${API.fileServe}?conf_id=${c.sys_id}&work_id=${taskData.work_sys_id}" target="_blank" class="p-2.5 border border-gray-100 rounded-lg hover:bg-gray-50 transition text-xs">
            <i class="fas fa-file-image text-gray-400 mb-1 block"></i>
            <span class="text-gray-700 font-medium block truncate">${escHtml(c.sys_id||'Confirmation')}</span>
            <span class="text-gray-400">${c.files_json.length} file(s)</span>
        </a>
    `).join('') + `</div>`;
}
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
// HOLDING ON
// ════════════════════════════════════════════════════════════
let _currentHoldingOn = '';
function renderHoldingOnBadge() {
    const lbl = document.getElementById('holdingOnLabel');
    const btn = document.getElementById('holdingOnBtn');
    if (_currentHoldingOn) {
        lbl.textContent = _currentHoldingOn;
        btn.classList.add('border-amber-300', 'text-amber-600');
    } else {
        lbl.textContent = 'Holding On';
        btn.classList.remove('border-amber-300', 'text-amber-600');
    }
}
function openHoldingOnModal() {
    document.getElementById('holdingOnModal').classList.remove('hidden');
    document.getElementById('holdingOnInput').value = _currentHoldingOn;
}
async function saveHoldingOn(clear) {
    const h = clear ? '' : document.getElementById('holdingOnInput').value.trim();
    try {
        const r = await fetch(API.assign, {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({sys_id:TASK_SYS_ID, assigned_to:null, holding_on:h||null})});
        const j = await r.json();
        if (j.status==='success') { _currentHoldingOn = h; renderHoldingOnBadge(); showToast('success','Saved!'); closeModal('holdingOnModal'); }
        else showToast('error', j.message);
    } catch { showToast('error','Network error'); }
}

// ════════════════════════════════════════════════════════════
// TRAVELERS
// ════════════════════════════════════════════════════════════
let _travData=[], _travLoaded=false;
let _linkedTravelers=[], _carouselIdx=0, _newTravelerExtracted=null, _ntFile=null, _ntDuplicateSysId=null;

async function _loadTravelers() {
    if(_travLoaded)return;
    try{const r=await fetch(API.travelers);const j=await r.json();_travData=j.travelers??[];_travLoaded=true;}catch{}
}

// ── Search & link existing traveler ──────────────────────────
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
    const workSysId = taskData?.work_sys_id ?? '';
    if (!workSysId) { showToast('error', 'Work ID not found'); return; }
    try {
        const r = await fetch(API.workTravelers, {method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({action:'link', work_sys_id:workSysId, traveler_sys_id:travelerSysId})});
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
        const r=await fetch(API.workTravelers,{method:'POST',headers:{'Content-Type':'application/json'},
            body:JSON.stringify({action:'unlink',work_sys_id:workSysId,traveler_sys_id:travelerSysId})});
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

// ── New Traveler Modal ────────────────────────────────────────
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
    const workSysId = taskData?.work_sys_id ?? '';
    try {
        const r = await fetch(API.workTravelers, {method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({action:'link', work_sys_id:workSysId, traveler_sys_id:_ntDuplicateSysId})});
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

    // store.php JSON expect করে
    const p = _newTravelerExtracted.passport_info ?? _newTravelerExtracted.data ?? {};
    const bio = p.bio_info ?? p;
    const payload = {
        full_name:       bio.full_name ?? p.full_name ?? '',
        date_of_birth:   bio.date_of_birth ?? p.date_of_birth ?? null,
        document_type:   'passport',
        document_number: bio.passport_number ?? p.document_number ?? null,
        file_path:       _newTravelerExtracted.file_path ?? null,
        extracted_data:  _newTravelerExtracted.full_extracted_data ?? _newTravelerExtracted ?? null,
        work_sys_id:     taskData?.work_sys_id ?? '',
        force_create:    false,
    };

    try {
        const res = await fetch(API.storeNewTraveler, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload),
        });
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
document.addEventListener('click',e=>{['statusModal','holdingOnModal','mindBoardModal','aiModal','specialInsModal'].forEach(id=>{const el=document.getElementById(id);if(el&&e.target===el)el.classList.add('hidden');});});

function badgeHtml(s){const m={open:['bg-yellow-100 text-yellow-700 border-yellow-200','🟡 Open'],in_progress:['bg-blue-100 text-blue-700 border-blue-200','🔵 In Progress'],done:['bg-green-100 text-green-700 border-green-200','✅ Done'],cancelled:['bg-red-100 text-red-700 border-red-200','❌ Cancelled'],on_hold:['bg-purple-100 text-purple-700 border-purple-200','⏸ On Hold']};const[c,l]=m[s]??['bg-gray-100 text-gray-600 border-gray-200',s];return`<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border ${c}">${l}</span>`;}
function serviceLabel(slug){const m={air_ticket:'✈ Air Ticket',visa:'🛂 Visa',hotel:'🏨 Hotel',tour_package:'🧳 Tour Package',umrah:'🕋 Umrah',transport:'🚌 Transport'};return`<span class="font-medium text-gray-700 text-xs">${m[slug]??slug??'—'}</span>`;}
function escHtml(s){return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
function showToast(type,msg){const i=document.getElementById('toastInner');document.getElementById('toastMsg').textContent=msg;i.className=`flex items-center gap-3 px-5 py-3 rounded-xl shadow-lg text-white text-sm font-medium ${type==='success'?'bg-green-600':type==='info'?'bg-indigo-500':'bg-red-500'}`;document.getElementById('toastIcon').className=`fas ${type==='success'?'fa-check-circle':type==='info'?'fa-info-circle':'fa-exclamation-circle'} text-lg`;document.getElementById('toast').classList.remove('hidden');setTimeout(()=>document.getElementById('toast').classList.add('hidden'),3500);}
window.showToast = showToast;

loadTask();
</script>
</body>
</html>