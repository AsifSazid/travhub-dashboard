<?php
// FILE PATH: /pages/create-leads.php
include_once('./authenticate.php');
$ip_port    = @file_get_contents('../ippath.txt') ?: 'http://103.104.219.3:898';
$lead_id    = $_GET['id'] ?? '';
$isEditMode = !empty($lead_id);
$pageTitle  = $isEditMode ? 'Edit Lead' : 'New Lead';

$servicesApi  = $ip_port . 'api/masterdata/services/endpoints.php';
$leadStoreApi = $isEditMode ? $ip_port . "api/leads/store.php?lead={$lead_id}" : $ip_port . 'api/leads/store.php';
$getLeadApi   = $isEditMode ? $ip_port . "api/leads/edit.php?lead={$lead_id}" : '';
$moveToWorkApi = $ip_port . 'api/leads/move-to-work.php';
$clientsApi   = $ip_port . 'api/clients/all-clients.php';
$extractApi   = $ip_port . 'api/ai/lead-extract.php';
$speechApi    = $ip_port . 'api/ai/lead-speech-polish.php';
$hotelsApi         = $ip_port . 'api/hotels/search.php';
$hotelQuickCreate  = $ip_port . 'api/masterdata/hotels/quick-create.php';
$countriesApi      = $ip_port . 'api/masterdata/countries/endpoints.php';
$visaApi           = $ip_port . 'api/masterdata/visa/endpoints.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $pageTitle; ?> — TravHub</title>
<link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
:root{--accent:#4F46E5;--accent-h:#4338CA;--surface:#F8FAFC;--border:#E2E8F0;--text:#0F172A;--muted:#64748B;}
body{background:var(--surface);}
.step-panel{display:none;}.step-panel.active{display:block;animation:fadeUp .2s ease;}
@keyframes fadeUp{from{opacity:0;transform:translateY(6px);}to{opacity:1;transform:translateY(0);}}
.step-circle{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;border:2px solid var(--border);background:#fff;color:var(--muted);transition:all .3s;flex-shrink:0;}
.step-circle.active{border-color:var(--accent);background:var(--accent);color:#fff;}
.step-circle.done{border-color:#22c55e;background:#22c55e;color:#fff;}
.step-line{flex:1;height:2px;background:var(--border);margin:0 4px;transition:background .3s;}
.step-line.done{background:#22c55e;}
.step-lbl{font-size:.7rem;font-weight:600;letter-spacing:.03em;color:var(--muted);white-space:nowrap;}
.step-lbl.active{color:var(--accent);}.step-lbl.done{color:#16a34a;}
.prog-bar{height:3px;background:var(--border);border-radius:999px;overflow:hidden;}
.prog-fill{height:100%;background:var(--accent);border-radius:999px;transition:width .4s ease;}
.card{background:#fff;border:1px solid var(--border);border-radius:12px;padding:24px;}
.card-title{font-size:.8rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);margin-bottom:16px;display:flex;align-items:center;gap:8px;}
.card-title i{color:var(--accent);}
.sec-divider{display:flex;align-items:center;gap:12px;margin:20px 0;}
.sec-divider span{font-size:.7rem;font-weight:600;color:#CBD5E1;letter-spacing:.05em;white-space:nowrap;}
.sec-divider::before,.sec-divider::after{content:'';flex:1;height:1px;background:#F1F5F9;}
.f-label{display:block;font-size:.75rem;font-weight:600;color:#475569;margin-bottom:5px;letter-spacing:.02em;}
.f-label .opt{font-weight:400;color:#CBD5E1;}
.f-input{width:100%;padding:9px 12px;font-size:.875rem;color:var(--text);border:1.5px solid var(--border);border-radius:8px;background:#fff;transition:border-color .15s,box-shadow .15s;outline:none;}
.f-input:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(79,70,229,.1);}
.f-input::placeholder{color:#CBD5E1;}
textarea.f-input{resize:vertical;}
.client-sel{display:flex;align-items:center;gap:10px;background:#F8F7FF;border:1.5px solid #C7D2FE;border-radius:8px;padding:10px 14px;margin-top:10px;}
.client-avatar{width:36px;height:36px;border-radius:50%;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;flex-shrink:0;}
.svc-card{cursor:pointer;border:1.5px solid var(--border);border-radius:10px;padding:12px 8px;text-align:center;transition:all .18s;background:#fff;position:relative;}
.svc-card:hover{border-color:#A5B4FC;transform:translateY(-1px);box-shadow:0 4px 12px rgba(79,70,229,.08);}
.svc-card.selected{border-color:var(--accent);background:#F5F3FF;}
.svc-check{position:absolute;top:6px;right:6px;width:16px;height:16px;border-radius:50%;background:var(--accent);color:#fff;display:none;align-items:center;justify-content:center;font-size:8px;}
.svc-card.selected .svc-check{display:flex;}
.svc-tab{padding:8px 14px;border-bottom:2px solid transparent;cursor:pointer;font-size:.8rem;font-weight:600;color:var(--muted);transition:all .15s;white-space:nowrap;letter-spacing:.02em;}
.svc-tab.active{border-color:var(--accent);color:var(--accent);}
.ai-panel{background:linear-gradient(135deg,#EEF2FF 0%,#FAF5FF 100%);border:1.5px solid #C7D2FE;border-radius:12px;padding:16px;}
.ai-btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:8px;font-size:.78rem;font-weight:600;cursor:pointer;transition:all .15s;border:none;letter-spacing:.02em;}
.ai-btn-voice{background:#fff;border:1.5px solid #C7D2FE;color:#4F46E5;}
.ai-btn-voice:hover,.ai-btn-voice.active{background:#EEF2FF;border-color:var(--accent);}
.ai-btn-refine{background:#7C3AED;color:#fff;}
.ai-btn-refine:hover{background:#6D28D9;}
.ai-btn-extract{background:var(--accent);color:#fff;}
.ai-btn-extract:hover{background:var(--accent-h);}
.prompt-box{width:100%;padding:10px 12px;font-size:.875rem;line-height:1.7;border:1.5px solid #C7D2FE;border-radius:8px;background:#fff;resize:vertical;min-height:80px;transition:border .15s;outline:none;color:var(--text);}
.prompt-box:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(79,70,229,.1);}
.prompt-box::placeholder{color:#C7D2FE;font-style:italic;}
.stt-panel{background:#fff;border:1px solid #E0E7FF;border-radius:10px;padding:12px;}
.stt-dot-idle{background:#CBD5E1;}.stt-dot-active{background:#22c55e;animation:pulse 1.2s infinite;}
.stt-dot-paused{background:#f59e0b;}.stt-dot-stopped{background:#ef4444;}
@keyframes pulse{0%,100%{opacity:1;}50%{opacity:.4;}}
.stt-btn{flex:1;padding:7px 10px;border-radius:7px;font-size:.75rem;font-weight:600;border:1.5px solid var(--border);background:#fff;color:var(--muted);cursor:pointer;transition:all .15s;display:flex;align-items:center;justify-content:center;gap:5px;}
.stt-btn:hover:not(:disabled){border-color:#94A3B8;color:var(--text);background:#F8FAFC;}
.stt-btn:disabled{opacity:.35;cursor:not-allowed;}
.stt-btn.stt-start:not(:disabled){border-color:var(--accent);color:var(--accent);}
.stt-btn.stt-push{border-color:var(--accent);background:var(--accent);color:#fff;}
.stt-btn.stt-push:hover:not(:disabled){background:var(--accent-h);}
.multi-row{display:flex;gap:8px;align-items:center;margin-bottom:8px;}
.multi-row .f-input{flex:1;}
.multi-row .del-btn{width:32px;height:36px;border-radius:7px;background:#FEF2F2;border:1px solid #FECACA;color:#EF4444;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .15s;}
.multi-row .del-btn:hover{background:#FEE2E2;}
.add-more-btn{display:inline-flex;align-items:center;gap:5px;font-size:.75rem;font-weight:600;color:var(--accent);background:#EEF2FF;border:none;border-radius:6px;padding:5px 10px;cursor:pointer;transition:all .15s;margin-top:4px;}
.add-more-btn:hover{background:#E0E7FF;}
.hotel-drop{position:absolute;left:0;right:0;top:100%;margin-top:4px;background:#fff;border:1px solid var(--border);border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.1);z-index:50;max-height:220px;overflow-y:auto;}
.btn-back{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;border-radius:8px;font-size:.83rem;font-weight:600;background:#fff;border:1.5px solid var(--border);color:var(--muted);cursor:pointer;transition:all .15s;}
.btn-back:hover{border-color:#94A3B8;color:var(--text);}
.btn-next{display:inline-flex;align-items:center;gap:6px;padding:9px 22px;border-radius:8px;font-size:.83rem;font-weight:600;background:var(--accent);color:#fff;cursor:pointer;transition:all .15s;border:none;}
.btn-next:hover{background:var(--accent-h);}
.btn-save{display:inline-flex;align-items:center;gap:6px;padding:9px 24px;border-radius:8px;font-size:.83rem;font-weight:700;background:var(--accent);color:#fff;cursor:pointer;transition:all .15s;border:none;}
.btn-save:hover{background:var(--accent-h);box-shadow:0 4px 12px rgba(79,70,229,.3);}
.btn-save:disabled{opacity:.6;cursor:not-allowed;}
.loader-overlay{position:fixed;inset:0;background:rgba(248,250,252,.9);display:flex;align-items:center;justify-content:center;z-index:9999;backdrop-filter:blur(2px);}
.loader-overlay.hidden{display:none;}
.common-section{background:#F8F7FF;border:1px solid #E0E7FF;border-radius:10px;padding:16px;margin-bottom:20px;}
.page-grid{display:grid;grid-template-columns:1fr;gap:20px;align-items:start;}
@media(min-width:1024px){.page-grid{grid-template-columns:380px 1fr;gap:24px;}.ai-col{position:sticky;top:108px;}.prompt-box{min-height:160px;}}
@media(max-width:640px){.svc-grid{grid-template-columns:repeat(3,1fr)!important;}.step-lbl{display:none;}}
</style>
</head>
<body>
<?php include '../elements/header.php'; ?>
<?php include '../elements/aside.php'; ?>

<div id="loaderOverlay" class="loader-overlay hidden">
    <div class="text-center">
        <div class="w-10 h-10 border-[3px] border-indigo-600 border-t-transparent rounded-full animate-spin mx-auto"></div>
        <p id="loaderMsg" class="mt-3 text-slate-500 text-sm font-medium">Processing…</p>
    </div>
</div>

<main id="mainContent" class="pt-16 pl-16 mt-16 transition-all duration-300">
<div class="p-4 md:p-6 max-w-7xl mx-auto">

    <div class="flex items-center justify-between mb-6">
        <div>
            <p class="text-xs font-semibold text-indigo-500 uppercase tracking-widest mb-1"><?php echo $isEditMode?'Edit':'New'; ?> Lead</p>
            <h1 class="text-xl font-bold text-slate-900"><?php echo $isEditMode?'Update Lead Information':'Create a Lead'; ?></h1>
            <?php if ($isEditMode): ?>
            <!-- Assigned to badge — populated by JS after loadLeadData() -->
            <div id="assignedBadge" class="hidden mt-2 inline-flex items-center gap-2 px-3 py-1 rounded-full bg-violet-50 border border-violet-200 text-xs font-semibold text-violet-700">
                <i class="fas fa-user-check text-violet-400" style="font-size:10px"></i>
                <span id="assignedBadgeName">—</span>
            </div>
            <?php endif; ?>
        </div>
        <div class="flex items-center gap-2">
            <?php if ($isEditMode): ?>
            <button id="convertBtn" onclick="convertToWork()"
                class="hidden items-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold transition">
                <i class="fas fa-arrow-right-arrow-left text-xs"></i>
                <span>Convert to Work</span>
            </button>
            <?php endif; ?>
            <a href="index-leads.php" class="flex items-center gap-2 px-3 py-2 rounded-lg border border-slate-200 text-slate-500 text-sm font-medium hover:border-slate-300 hover:text-slate-700 transition bg-white">
                <i class="fas fa-arrow-left text-xs"></i><span class="hidden sm:inline">Back</span>
            </a>
        </div>
    </div>

    <div class="page-grid">

    <!-- LEFT — AI Panel -->
    <div class="ai-col">
    <div class="ai-panel mb-5">
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded-md bg-indigo-100 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-wand-magic-sparkles text-indigo-500" style="font-size:10px"></i>
                </div>
                <span class="text-sm font-bold text-slate-700">AI Extract</span>
                <span class="text-xs text-slate-400 font-normal">& Build</span>
                <!-- Service context badge — shown when a service is selected -->
                <span id="aiServiceBadge" class="hidden items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-600 text-white">
                    <i id="aiServiceBadgeIcon" class="fas fa-plane" style="font-size:8px"></i>
                    <span id="aiServiceBadgeLabel">Air Ticket</span>
                </span>
            </div>
            <button onclick="document.getElementById('promptArea').value='';document.getElementById('extractResult').classList.add('hidden');"
                class="w-6 h-6 rounded-md flex items-center justify-center text-slate-300 hover:text-slate-500 hover:bg-slate-100 transition text-xs">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <textarea id="promptArea" class="prompt-box mb-3"
            placeholder="Select a service above — then describe the client's request here…"></textarea>
        <div class="flex flex-wrap gap-2">
            <button id="sttToggle" onclick="sttToggle()" class="ai-btn ai-btn-voice"><i class="fas fa-microphone text-xs"></i> Voice</button>
            <button onclick="refinePrompt()" class="ai-btn ai-btn-refine"><i class="fas fa-wand-magic-sparkles text-xs"></i> Refine</button>
            <button onclick="extractAndBuild()" class="ai-btn ai-btn-extract"><i class="fas fa-bolt text-xs"></i> Extract & Build</button>
        </div>
        <div id="sttPanel" class="hidden mt-3 stt-panel">
            <div class="flex items-center gap-2 mb-2">
                <div id="sttDot" class="w-1.5 h-1.5 rounded-full flex-shrink-0 stt-dot-idle"></div>
                <span id="sttStatus" class="text-xs text-slate-400 flex-1">Ready</span>
                <select id="sttLang" class="text-xs border border-slate-200 rounded-md px-2 py-1 focus:outline-none bg-white text-slate-500">
                    <option value="bn-BD">বাংলা</option>
                    <option value="en-US">English</option>
                </select>
            </div>
            <div class="relative mb-2.5">
                <div id="sttPreview" contenteditable="true"
                    class="min-h-[40px] text-xs bg-slate-50 rounded-lg px-3 py-2 pr-7 leading-relaxed border border-slate-100 focus:outline-none text-slate-700"
                    style="cursor:text;white-space:pre-wrap;" oninput="_sttFinal=this.innerText"></div>
                <button onclick="_sttFinal='';document.getElementById('sttPreview').innerText='';document.getElementById('sttActions').classList.add('hidden');"
                    class="absolute top-1.5 right-1.5 w-5 h-5 rounded flex items-center justify-center text-slate-300 hover:text-slate-500 hover:bg-slate-200 transition flex-shrink-0">
                    <i class="fas fa-times" style="font-size:8px"></i>
                </button>
            </div>
            <div class="flex gap-1.5">
                <button id="sttStart" onclick="sttStart()" class="stt-btn stt-start"><i class="fas fa-play" style="font-size:9px"></i> Start</button>
                <button id="sttPause" onclick="sttPause()" disabled class="stt-btn"><i class="fas fa-pause" style="font-size:9px"></i> Pause</button>
                <button id="sttStop"  onclick="sttStop()"  disabled class="stt-btn"><i class="fas fa-stop"  style="font-size:9px"></i> Stop</button>
            </div>
            <div id="sttActions" class="hidden mt-2 flex gap-1.5">
                <button onclick="sttPush(false)" class="stt-btn">Push Raw</button>
                <button id="sttPolish" onclick="sttPush(true)" class="stt-btn stt-push"><i class="fas fa-wand-magic-sparkles" style="font-size:9px"></i> Polish & Push</button>
            </div>
        </div>
        <div id="extractResult" class="hidden mt-3 p-3 bg-white rounded-lg border border-indigo-100">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-2">Extracted</p>
            <div id="extractChips" class="flex flex-wrap gap-2"></div>
        </div>
    </div>

    <!-- Notices Panel -->
    <div id="noticesPanel" class="hidden">
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 space-y-2">
            <div class="flex items-center gap-2 mb-1">
                <i class="fas fa-circle-info text-amber-500 text-sm"></i>
                <span class="text-xs font-bold text-amber-700 uppercase tracking-wide">Notes & Instructions</span>
                <button onclick="clearNotices()" class="ml-auto w-5 h-5 flex items-center justify-center rounded text-amber-400 hover:text-amber-600 hover:bg-amber-100 transition text-xs">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="noticesList" class="space-y-2"></div>
        </div>
    </div>
    </div>

    <!-- RIGHT — Steps & Form -->
    <div class="form-col">

    <!-- Step tracker -->
    <div class="card mb-4 py-4 px-5">
        <div class="flex items-center mb-3">
            <div class="flex items-center gap-2">
                <div id="dot1" class="step-circle active">1</div>
                <span id="lbl1" class="step-lbl active">Info & Services</span>
            </div>
            <div id="line1" class="step-line flex-1 mx-2"></div>
            <div class="flex items-center gap-2">
                <div id="dot2" class="step-circle">2</div>
                <span id="lbl2" class="step-lbl">Details</span>
            </div>
        </div>
        <div class="prog-bar"><div id="progressBar" class="prog-fill" style="width:50%"></div></div>
    </div>

    <!-- STEP 1 — Services first, then Client Info -->
    <div id="step1" class="step-panel active">
    <div class="card">

        <!-- ① SERVICE SELECTION — top -->
        <div class="card-title"><i class="fas fa-layer-group"></i>Select Service(s)</div>
        <p class="text-xs text-slate-400 mb-4 -mt-2">Pick the service first — AI extract will be tailored to it</p>
        <div id="servicesGrid" class="svc-grid grid grid-cols-3 md:grid-cols-6 gap-3 mb-2">
            <div class="col-span-6 text-center py-8 text-slate-300 text-sm"><i class="fas fa-spinner fa-spin mr-2"></i>Loading…</div>
        </div>
        <div id="svcError" class="hidden mb-3 text-xs text-red-500 flex items-center gap-1">
            <i class="fas fa-exclamation-circle"></i> Please select at least one service.
        </div>

        <!-- ② CLIENT INFO — below services -->
        <div class="border-t border-slate-100 pt-5 mt-3">
            <div class="card-title"><i class="fas fa-user"></i>Client Information</div>

            <div class="mb-4">
                <label class="f-label">Search existing client</label>
                <div class="flex gap-2">
                    <div id="clientSearchWrap" class="relative flex-1">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                        <input type="text" id="clientInput" placeholder="Name or ID…" class="f-input pl-8" autocomplete="off">
                        <ul id="clientDropdown" class="absolute w-full bg-white border border-slate-200 rounded-lg mt-1 max-h-52 overflow-auto shadow-xl hidden z-50 text-sm"></ul>
                    </div>
                    <a href="./create-client.php" target="_blank"
                        class="w-9 h-9 flex items-center justify-center bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm transition flex-shrink-0" title="Add Client">
                        <i class="fas fa-plus text-xs"></i>
                    </a>
                    <button onclick="loadClients()"
                        class="w-9 h-9 flex items-center justify-center bg-white border border-slate-200 hover:border-slate-300 text-slate-400 rounded-lg text-sm transition flex-shrink-0">
                        <i class="fas fa-rotate-right text-xs"></i>
                    </button>
                </div>
                <div id="clientBadge" class="hidden client-sel">
                    <div class="client-avatar" id="badgeAvatar">?</div>
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-slate-800 text-sm truncate" id="badgeName">—</div>
                        <div class="text-xs text-slate-400 font-mono truncate" id="badgeMeta">—</div>
                    </div>
                    <button onclick="clearClient()" class="text-slate-300 hover:text-red-400 transition text-sm flex-shrink-0"><i class="fas fa-times"></i></button>
                </div>
            </div>

            <div class="sec-divider"><span>OR FILL MANUALLY</span></div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="f-label">Full Name <span class="text-red-400">*</span></label>
                    <input type="text" id="clientName" placeholder="e.g. Shakil Ahmed" class="f-input">
                </div>
                <div>
                    <label class="f-label">Phone</label>
                    <input type="text" id="clientPhone" placeholder="01700000000" class="f-input">
                </div>
                <div>
                    <label class="f-label">Email <span class="opt">(optional)</span></label>
                    <input type="email" id="clientEmail" placeholder="client@email.com" class="f-input">
                </div>
                <div>
                    <label class="f-label">Lead Source</label>
                    <select id="leadSource" class="f-input">
                        <option value="">Select…</option>
                        <option value="System">System</option>
                        <option value="walk_in">Walk-in</option>
                        <option value="referral">Referral</option>
                        <option value="facebook">Facebook</option>
                        <option value="website">Website</option>
                        <option value="phone_call">Phone Call</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="flex justify-end mt-5 pt-4 border-t border-slate-100">
            <button onclick="goToDetails()" class="btn-next">Next <i class="fas fa-arrow-right text-xs"></i></button>
        </div>
    </div>
    </div>

    <!-- STEP 2 — Details (was step 3) -->
    <div id="step2" class="step-panel">
    <div class="card">
        <div class="card-title"><i class="fas fa-list-check"></i>Lead Details</div>

        <!-- COMMON FIELDS -->
        <div class="common-section mb-5">
            <p class="text-[10px] font-bold uppercase tracking-widest text-indigo-400 mb-4"><i class="fas fa-layer-group mr-1"></i>Common Information</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="f-label">Lead Title <span class="opt">(optional)</span></label>
                    <input type="text" id="commonTitle" placeholder="e.g. Dubai Family Trip — Rahim — Air + Hotel" class="f-input">
                </div>
                <div class="sm:col-span-2">
                    <label class="f-label">Destination Country(s) <span class="text-red-400">*</span></label>
                    <div id="countryCheckboxGrid"
                        class="grid grid-cols-2 md:grid-cols-3 gap-2 p-3 bg-white rounded-lg border border-slate-200 max-h-48 overflow-y-auto">
                        <div class="col-span-full text-center text-slate-300 text-sm py-3">
                            <i class="fas fa-spinner fa-spin mr-1"></i>Loading…
                        </div>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1"><i class="fas fa-info-circle mr-1"></i>Only countries enabled for work are listed. Select one or more.</p>
                </div>
                <div>
                    <label class="f-label">Adult(s)</label>
                    <input type="number" id="paxAdult" min="0" value="1" class="f-input">
                </div>
                <div>
                    <label class="f-label">Child(ren) <span class="opt">(2–11 yrs)</span></label>
                    <input type="number" id="paxChild" min="0" value="0" class="f-input">
                </div>
                <div>
                    <label class="f-label">Infant(s) <span class="opt">(0–2 yrs)</span></label>
                    <input type="number" id="paxInfant" min="0" value="0" class="f-input">
                </div>
                <div>
                    <label class="f-label">Budget (BDT) <span class="opt">(optional)</span></label>
                    <input type="number" id="commonBudget" min="0" placeholder="e.g. 150000" class="f-input">
                </div>
                <div class="sm:col-span-2">
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="f-label mb-0"><i class="fas fa-sticky-note mr-1 text-blue-400"></i>Notes <span class="opt">(goes to Mindboard)</span></label>
                        <div class="flex items-center gap-1.5">
                            <span id="sttDotStep1" class="w-2 h-2 rounded-full bg-slate-300 flex-shrink-0"></span>
                            <span id="sttStatusStep1" class="text-[11px] text-slate-400">Ready</span>
                            <select id="sttLangStep1" class="text-xs border border-slate-200 rounded-md px-2 py-1 focus:outline-none bg-white text-slate-500 ml-1">
                                <option value="bn-BD">বাংলা</option>
                                <option value="en-US">English</option>
                            </select>
                        </div>
                    </div>
                    <textarea id="leadNotes" rows="4" class="f-input"
                        placeholder="Client wants business class only, window seat preferred…&#10;তিনি ডুবাই যেতে চান আগামী মাসে…"
                        style="resize:vertical;min-height:90px;"></textarea>
                    <div class="flex gap-2 mt-2">
                        <button type="button" id="sttStartStep1" onclick="step1SttStart()"
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border-2 border-indigo-300 bg-white text-indigo-600 text-xs font-semibold hover:bg-indigo-50 transition">
                            <i class="fas fa-microphone text-xs"></i> Start
                        </button>
                        <button type="button" id="sttPauseStep1" onclick="step1SttPause()" disabled
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-400 text-xs font-semibold transition disabled:opacity-40">
                            <i class="fas fa-pause text-xs"></i> Pause
                        </button>
                        <button type="button" id="sttStopStep1" onclick="step1SttStop()" disabled
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-400 text-xs font-semibold transition disabled:opacity-40">
                            <i class="fas fa-stop text-xs"></i> Stop
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- SERVICE TABS -->
        <div id="svcTabsNav" class="flex border-b border-slate-100 mb-5 gap-0 overflow-x-auto -mx-6 px-6"></div>
        <div id="svcPanels"></div>

        <div class="flex justify-between mt-6 pt-4 border-t border-slate-100">
            <button onclick="goStep(1)" class="btn-back"><i class="fas fa-arrow-left text-xs"></i> Back</button>
            <button onclick="submitLead()" id="submitBtn" class="btn-save">
                <i class="fas <?php echo $isEditMode?'fa-sync':'fa-save'; ?> text-xs"></i>
                <?php echo $isEditMode?'Update Lead':'Save Lead'; ?>
            </button>
        </div>
    </div>
    </div>

    </div>
    </div>
</div>
</main>

<!-- NEW HOTEL MODAL -->
<div id="newHotelModal" class="fixed inset-0 z-50 hidden" style="background:rgba(0,0,0,.5);backdrop-filter:blur(4px);">
    <div class="flex items-center justify-center min-h-screen p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800 text-base"><i class="fas fa-hotel mr-2 text-indigo-500"></i>Add New Hotel</h3>
            <button onclick="closeNewHotelModal()" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-400"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-6 space-y-4">
            <div id="newHotelContext" class="bg-indigo-50 rounded-lg px-4 py-2.5 text-sm text-indigo-700 hidden">
                <i class="fas fa-map-marker-alt mr-1.5"></i>
                <span id="newHotelContextText"></span>
            </div>
            <div>
                <label class="f-label">Hotel Name <span class="text-red-400">*</span></label>
                <input type="text" id="nhName" class="f-input" placeholder="e.g. Burj Al Arab">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="f-label">Star Rating <span class="opt">(optional)</span></label>
                    <select id="nhStar" class="f-input">
                        <option value="">—</option>
                        <option value="1">★</option>
                        <option value="2">★★</option>
                        <option value="3">★★★</option>
                        <option value="4">★★★★</option>
                        <option value="5">★★★★★</option>
                    </select>
                </div>
                <div>
                    <label class="f-label">Phone <span class="opt">(optional)</span></label>
                    <input type="text" id="nhPhone" class="f-input" placeholder="+971...">
                </div>
            </div>
            <div>
                <label class="f-label">Address <span class="opt">(optional)</span></label>
                <input type="text" id="nhAddress" class="f-input" placeholder="Street, area…">
            </div>
            <div>
                <label class="f-label">Email <span class="opt">(optional)</span></label>
                <input type="email" id="nhEmail" class="f-input" placeholder="hotel@email.com">
            </div>
        </div>
        <div class="flex gap-3 px-6 pb-6">
            <button onclick="closeNewHotelModal()" class="flex-1 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition">Cancel</button>
            <button onclick="submitNewHotel()" id="nhSubmitBtn" class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition">
                <i class="fas fa-plus mr-1"></i>Create Hotel
            </button>
        </div>
    </div>
    </div>
</div>

<!-- Toast -->
<div id="toast" class="hidden fixed bottom-6 right-6 z-50">
    <div id="toastInner" class="flex items-center gap-3 px-4 py-3 rounded-xl shadow-2xl text-white text-sm font-semibold min-w-[200px]">
        <i id="toastIcon" class="fas fa-check-circle flex-shrink-0"></i>
        <span id="toastMsg"></span>
    </div>
</div>

<?php include '../elements/floating-menus.php'; ?>
<script src="../assets/js/script.js?t=<?php echo time(); ?>"></script>
<script>
const IS_EDIT      = <?php echo json_encode($isEditMode); ?>;
const STORE_API    = '<?php echo $leadStoreApi; ?>';
const CLIENTS_API  = '<?php echo $clientsApi; ?>';
const GET_LEAD_API = '<?php echo $getLeadApi; ?>';
const SERVICES_API = '<?php echo $servicesApi; ?>';
const EXTRACT_API  = '<?php echo $extractApi; ?>';
const SPEECH_API   = '<?php echo $speechApi; ?>';
const HOTELS_API        = '<?php echo $hotelsApi; ?>';
const HOTEL_CREATE_API  = '<?php echo $hotelQuickCreate; ?>';
const COUNTRIES_API     = '<?php echo $countriesApi; ?>';
const VISA_API          = '<?php echo $visaApi; ?>';
const MOVE_TO_WORK_API  = '<?php echo $moveToWorkApi; ?>';
const LEAD_SYS_ID       = '<?php echo htmlspecialchars($lead_id); ?>';

const COLOR_HEX = {
    indigo:'#6366f1',sky:'#0ea5e9',purple:'#a855f7',pink:'#ec4899',
    green:'#22c55e',yellow:'#eab308',orange:'#f97316',red:'#ef4444',
    teal:'#14b8a6',gray:'#6b7280'
};

let allServices      = [];
let selectedServices = new Set();
let serviceAnswers   = {};
let commonData       = {};
let clientsData      = [];
let selectedClient   = null;
let hotelTimer    = null;
let allCountries  = [];

// ── Init ─────────────────────────────────────────────
loadClients();

if (IS_EDIT) {
    Promise.all([loadServices(), loadCountries()])
        .then(() => loadLeadData())
        .catch(e => { console.error('Init error:', e); hideLoader(); });
} else {
    Promise.all([loadServices(), loadCountries()]);
}

/* ═══════════════════════════════════════════════
   STEP 1 — NOTES (single textarea + STT)
═══════════════════════════════════════════════ */
function getLeadNote()    { return (document.getElementById('leadNotes')?.value ?? '').trim(); }

let _s1Rec = null, _s1Recording = false, _s1Paused = false;

function step1SttStart() {
    if (!('webkitSpeechRecognition' in window || 'SpeechRecognition' in window)) {
        showToast('error', 'Use Chrome for voice input.'); return;
    }
    if (!_s1Rec) {
        const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
        _s1Rec = new SR();
        _s1Rec.continuous = true;
        _s1Rec.interimResults = true;
        let _base = '';
        _s1Rec.onstart = () => { _base = document.getElementById('leadNotes').value; };
        _s1Rec.onresult = e => {
            let interim = '';
            let final_  = '';
            for (let i = e.resultIndex; i < e.results.length; i++) {
                if (e.results[i].isFinal) final_ += e.results[i][0].transcript;
                else interim += e.results[i][0].transcript;
            }
            if (final_) _base += (_base ? ' ' : '') + final_.trim();
            document.getElementById('leadNotes').value = _base + (interim ? ' ' + interim : '');
        };
        _s1Rec.onerror = e => { if (e.error !== 'aborted') s1SetStatus('stopped', 'Error: ' + e.error); };
        _s1Rec.onend   = () => { if (_s1Recording && !_s1Paused) { _s1Rec.lang = document.getElementById('sttLangStep1').value; _s1Rec.start(); } };
    }
    _s1Recording = true; _s1Paused = false;
    _s1Rec.lang = document.getElementById('sttLangStep1').value || 'bn-BD';
    _s1Rec.start();
    document.getElementById('sttStartStep1').disabled = true;
    document.getElementById('sttPauseStep1').disabled = false;
    document.getElementById('sttStopStep1').disabled  = false;
    s1SetStatus('active', 'Recording…');
}

function step1SttPause() {
    if (!_s1Paused) {
        _s1Paused = true; _s1Rec?.stop();
        document.getElementById('sttPauseStep1').innerHTML = '<i class="fas fa-play text-xs"></i> Resume';
        s1SetStatus('paused', 'Paused');
    } else {
        _s1Paused = false;
        _s1Rec.lang = document.getElementById('sttLangStep1').value;
        _s1Rec?.start();
        document.getElementById('sttPauseStep1').innerHTML = '<i class="fas fa-pause text-xs"></i> Pause';
        s1SetStatus('active', 'Recording…');
    }
}

function step1SttStop() {
    _s1Recording = false; _s1Paused = false; _s1Rec?.stop();
    document.getElementById('sttStartStep1').disabled = false;
    document.getElementById('sttPauseStep1').disabled = true;
    document.getElementById('sttStopStep1').disabled  = true;
    document.getElementById('sttPauseStep1').innerHTML = '<i class="fas fa-pause text-xs"></i> Pause';
    s1SetStatus('stopped', 'Done');
}

function s1SetStatus(state, text) {
    const dot = document.getElementById('sttDotStep1');
    const txt = document.getElementById('sttStatusStep1');
    if (txt) txt.textContent = text;
    const m = { idle:'bg-slate-300', active:'bg-green-500 animate-pulse', paused:'bg-amber-400', stopped:'bg-red-400' };
    if (dot) dot.className = `w-2 h-2 rounded-full flex-shrink-0 ${m[state] || m.idle}`;
}

/* ═══════════════════════════════════════════════
   SEGMENT SPECIAL INSTRUCTIONS
═══════════════════════════════════════════════ */
function addSegSpInsRow(containerId, val='') {
    const c = document.getElementById(containerId);
    if (!c) return;
    const d = document.createElement('div');
    d.className = 'multi-row';
    d.innerHTML = `<input type="text" class="f-input seg-spins-input" placeholder="e.g. Window seat preferred…" value="${esc(val)}">
        <button type="button" onclick="this.parentElement.remove()" class="del-btn"><i class="fas fa-times text-xs"></i></button>`;
    c.appendChild(d);
}

function collectSegSpIns(containerId) {
    const c = document.getElementById(containerId);
    if (!c) return [];
    return [...c.querySelectorAll('.seg-spins-input')].map(i=>i.value.trim()).filter(Boolean);
}

/* ═══════════════════════════════════════════════
   COUNTRIES (destination)
═══════════════════════════════════════════════ */
async function loadCountries() {
    try {
        const res = await fetch(COUNTRIES_API + '?action=for_work');
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const json = await res.json();
        allCountries = json.data ?? [];
        renderCountryCheckboxGrid();
    } catch(e) {
        console.error('Countries load failed:', e);
        document.getElementById('countryCheckboxGrid').innerHTML =
            `<div class="col-span-full text-center text-red-400 text-sm py-3">Failed to load countries.</div>`;
    }
}

function renderCountryCheckboxGrid(selectedIds = []) {
    const grid = document.getElementById('countryCheckboxGrid');
    if (!grid) return;
    if (!allCountries.length) {
        grid.innerHTML = `<div class="col-span-full text-center text-slate-300 text-sm py-3">No countries found.</div>`;
        return;
    }
    grid.innerHTML = allCountries.map(c => `
        <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-indigo-50 p-1.5 rounded select-none">
            <input type="checkbox" class="common-country-cb w-4 h-4 text-indigo-600 rounded border-slate-300 flex-shrink-0"
                value="${esc(c.sys_id)}" data-name="${esc(c.name)}"
                ${selectedIds.includes(c.sys_id) ? 'checked' : ''}
                onchange="onCommonCountryChange()">
            <span class="text-slate-700 leading-tight">${esc(c.name)}</span>
        </label>
    `).join('');
}

function onCommonCountryChange() {
    // ── Hotel panel: reload city dropdowns for all open segments ──
    if (selectedServices.has('hotel')) {
        document.querySelectorAll('.hotel-segment').forEach(seg => {
            const idx = parseInt(seg.dataset.idx ?? 0);
            loadHotelCitiesFromCommon(idx);
        });
    }

    // ── Package panel: rebuild destinations + refresh accommodation city selects ──
    if (selectedServices.has('package') || selectedServices.has('tour_package')) {
        const slug  = selectedServices.has('tour_package') ? 'tour_package' : 'package';
        const saved = serviceAnswers[slug] ?? {};
        buildPkgDestinations(saved.destinations ?? []).then(() => {
            renderPkgAccommodations(saved.accommodations ?? []);
        });
    }

    // ── Visa panel: reload categories for each segment ──
    if (selectedServices.has('visa')) {
        const commonCtries = collectCountries();
        document.querySelectorAll('.visa-segment').forEach(seg => {
            const idx = parseInt(seg.dataset.idx ?? 0);
            const sel = seg.querySelector('.visa-seg-country');
            if (!sel) return;
            // Rebuild country options
            sel.innerHTML = `<option value="">Select country…</option>` +
                commonCtries.map(c =>
                    `<option value="${esc(c.sys_id)}" data-name="${esc(c.name)}"
                        ${sel.value === c.sys_id ? 'selected' : ''}>${esc(c.name)}</option>`
                ).join('');
            if (sel.value) onVisaSegCountryChange(sel, idx);
        });
        if (!commonCtries.length) removeNotice('visa_req_0');
    }
}

function collectCountries() {
    return [...document.querySelectorAll('.common-country-cb:checked')]
        .map(cb => ({ sys_id: cb.value, name: cb.dataset.name ?? '' }));
}

function getSelectedCountryIds() {
    return [...document.querySelectorAll('.common-country-cb:checked')].map(cb => cb.value);
}

// Legacy stubs — kept so no reference breaks
function addCountryRow(val='') {
    // no-op: replaced by checkbox grid
    // called only from edit mode restore → handled by renderCountryCheckboxGrid
}
function countryOptions(selected='') { return ''; }

function collectNotes() { return getLeadNote(); }

/* ═══════════════════════════════════════════════
   SERVICES
═══════════════════════════════════════════════ */
async function loadServices() {
    try {
        const res  = await fetch(SERVICES_API + '?action=all');
        const json = await res.json();
        allServices = (json.data ?? []).filter(s => s.is_active == 1);
        renderServiceGrid();
        if (IS_EDIT && selectedServices.size > 0) renderServiceGrid();
    } catch {
        document.getElementById('servicesGrid').innerHTML =
            `<div class="col-span-6 text-center text-red-400 text-sm py-4">Failed to load services.</div>`;
    }
}

function renderServiceGrid() {
    const grid = document.getElementById('servicesGrid');
    if (!allServices.length) {
        grid.innerHTML = `<div class="col-span-6 text-center text-slate-300 text-sm py-4">No services found.</div>`;
        return;
    }
    grid.innerHTML = allServices.map(s => {
        const hex = COLOR_HEX[s.color] ?? '#6366f1';
        const sel = selectedServices.has(s.slug);
        return `<div class="svc-card ${sel?'selected':''}" data-slug="${s.slug}" onclick="toggleService('${s.slug}')">
            <div class="svc-check"><i class="fas fa-check" style="font-size:7px"></i></div>
            <i class="fas ${s.icon} text-xl mb-2 block" style="color:${sel?'#4F46E5':hex}"></i>
            <div class="text-xs font-semibold text-slate-600 leading-tight">${s.name}</div>
        </div>`;
    }).join('');
    updateAiPanelForService();
}

function toggleService(slug) {
    selectedServices.has(slug) ? selectedServices.delete(slug) : selectedServices.add(slug);
    renderServiceGrid();
    document.getElementById('svcError').classList.add('hidden');
    updateAiPanelForService();
}

// Service-specific AI panel config
const _svcAiConfig = {
    air_ticket:   { icon: 'fa-plane',         label: 'Air Ticket',   placeholder: 'e.g. Rahim wants DAC-DXB round trip for 2 adults next month, business class…' },
    visa:         { icon: 'fa-passport',       label: 'Visa',         placeholder: 'e.g. Karim needs Dubai tourist visa, 2 adults, traveling in August…' },
    hotel:        { icon: 'fa-hotel',          label: 'Hotel',        placeholder: 'e.g. Sakib wants 5-star hotel in Bangkok, check-in 10 Aug, 5 nights, 2 rooms…' },
    package:      { icon: 'fa-box-open',       label: 'Tour Package', placeholder: 'e.g. Family of 4 wants Thailand package 7 days, honeymoon couple, budget 3 lakh…' },
    tour_package: { icon: 'fa-box-open',       label: 'Tour Package', placeholder: 'e.g. Family of 4 wants Thailand package 7 days, honeymoon couple, budget 3 lakh…' },
    umrah:        { icon: 'fa-kaaba',          label: 'Umrah',        placeholder: 'e.g. Group of 5 for Umrah, 14 nights, fixed flight, departure January…' },
    transport:    { icon: 'fa-van-shuttle',    label: 'Transport',    placeholder: 'e.g. Microbus needed DAC-CTG on 15 Aug, 8 passengers, AC required…' },
};

function updateAiPanelForService() {
    const firstSlug = [...selectedServices][0] ?? '';
    const cfg       = _svcAiConfig[firstSlug];
    const badge     = document.getElementById('aiServiceBadge');
    const badgeIcon = document.getElementById('aiServiceBadgeIcon');
    const badgeLbl  = document.getElementById('aiServiceBadgeLabel');
    const textarea  = document.getElementById('promptArea');

    if (cfg && firstSlug) {
        // Show badge
        if (badge) {
            badge.classList.remove('hidden');
            badge.classList.add('inline-flex');
        }
        if (badgeIcon) badgeIcon.className = `fas ${cfg.icon}`;
        if (badgeLbl)  badgeLbl.textContent = cfg.label;
        // Update placeholder
        if (textarea && !textarea.value.trim()) textarea.placeholder = cfg.placeholder;
    } else {
        // No service selected — reset
        if (badge) { badge.classList.add('hidden'); badge.classList.remove('inline-flex'); }
        if (textarea && !textarea.value.trim())
            textarea.placeholder = 'Select a service above — then describe the client\'s request here…';
    }
}

/* ═══════════════════════════════════════════════
   CLIENT SEARCH
═══════════════════════════════════════════════ */
function _normalizeClients(arr) {
    return arr.map(c => {
        let phone=''; try{const p=typeof c.phone==='string'?JSON.parse(c.phone):c.phone; phone=p?.primary_no??p?.primary??'';}catch{}
        let email=''; try{const e=typeof c.email==='string'?JSON.parse(c.email):c.email; email=e?.primary??e?.primary_email??'';}catch{}
        return {...c,_phone:phone,_email:email};
    });
}

async function loadClients() {
    try { const res=await fetch(CLIENTS_API); const j=await res.json(); clientsData=_normalizeClients(j.clients??[]); } catch {}
}

const clientInput    = document.getElementById('clientInput');
const clientDropdown = document.getElementById('clientDropdown');
const clientWrap     = document.getElementById('clientSearchWrap');
let cTimer;

clientInput.addEventListener('input', ()=>{clearTimeout(cTimer);cTimer=setTimeout(()=>filterClients(clientInput.value),250);});
clientInput.addEventListener('focus', ()=>{filterClients(clientInput.value);clientDropdown.classList.remove('hidden');});
document.addEventListener('click', e=>{if(!clientWrap.contains(e.target))clientDropdown.classList.add('hidden');});

function filterClients(q) {
    const v=q.toLowerCase().trim();
    const list=v?clientsData.filter(c=>(c.name||'').toLowerCase().includes(v)||(c.sys_id||'').toLowerCase().includes(v)):clientsData.slice(0,20);
    clientDropdown.classList.remove('hidden');
    if(!list.length){clientDropdown.innerHTML=`<li class="px-4 py-3 text-center text-slate-400 text-xs">No clients found</li>`;return;}
    clientDropdown.innerHTML=list.map(c=>{
        const idx=clientsData.indexOf(c);
        return `<li class="px-4 py-2.5 cursor-pointer hover:bg-indigo-50 border-b border-slate-50 last:border-b-0 text-sm" onclick="selectClient(${idx})">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 bg-indigo-600 rounded-full text-white flex items-center justify-center font-bold text-xs flex-shrink-0">${(c.name?.[0]??'C').toUpperCase()}</div>
                <div><div class="font-medium text-slate-800">${c.name}</div><div class="text-xs text-slate-400 font-mono">${c.sys_id}${c._phone?' · '+c._phone:''}</div></div>
            </div></li>`;
    }).join('');
}

function selectClient(idx) {
    const c=clientsData[idx]; if(!c)return;
    selectedClient=c;
    clientInput.value=`${c.sys_id} | ${c.name}`;
    document.getElementById('clientName').value  = c.name   ?? '';
    document.getElementById('clientPhone').value = c._phone ?? '';
    document.getElementById('clientEmail').value = c._email ?? '';
    document.getElementById('clientBadge').classList.remove('hidden');
    document.getElementById('badgeAvatar').textContent=(c.name?.[0]??'C').toUpperCase();
    document.getElementById('badgeName').textContent=c.name;
    document.getElementById('badgeMeta').textContent=`${c.sys_id}${c._phone?' · '+c._phone:''}`;
    clientDropdown.classList.add('hidden');
}

function clearClient() {
    selectedClient=null; clientInput.value='';
    document.getElementById('clientBadge').classList.add('hidden');
    ['clientName','clientPhone','clientEmail'].forEach(id=>document.getElementById(id).value='');
}

/* ═══════════════════════════════════════════════
   STEPS
═══════════════════════════════════════════════ */
function updateProgress(step) {
    document.getElementById('progressBar').style.width=Math.round((step/2)*100)+'%';
    [1,2].forEach(i=>{
        const dot=document.getElementById('dot'+i),lbl=document.getElementById('lbl'+i),line=document.getElementById('line'+i);
        if(!dot) return;
        if(i<step){dot.className='step-circle done';dot.innerHTML='<i class="fas fa-check" style="font-size:10px"></i>';lbl.className='step-lbl done';if(line)line.classList.add('done');}
        else if(i===step){dot.className='step-circle active';dot.textContent=i;lbl.className='step-lbl active';}
        else{dot.className='step-circle';dot.textContent=i;lbl.className='step-lbl';if(line)line.classList.remove('done');}
    });
}

function goStep(n) {
    document.querySelectorAll('.step-panel').forEach(p=>p.classList.remove('active'));
    document.getElementById('step'+n).classList.add('active');
    updateProgress(n);
    window.scrollTo({top:0,behavior:'smooth'});
}

// New unified validation + transition: step 1 → step 2
function goToDetails() {
    if (!document.getElementById('clientName').value.trim() && !selectedClient) {
        showToast('error', 'Please enter or select a client.'); return;
    }
    if (selectedServices.size === 0) {
        document.getElementById('svcError').classList.remove('hidden');
        document.getElementById('servicesGrid').scrollIntoView({behavior:'smooth', block:'center'});
        return;
    }
    document.getElementById('svcError').classList.add('hidden');
    buildServicePanels();
    goStep(2);
}

// Legacy stubs — kept so any surviving references don't throw
function goStep2() { goToDetails(); }
function goStep3() { goToDetails(); }

/* ═══════════════════════════════════════════════
   STEP 2 — SERVICE PANELS
═══════════════════════════════════════════════ */
function buildServicePanels() {
    const slugs  = [...selectedServices];
    const tabNav = document.getElementById('svcTabsNav');
    const panels = document.getElementById('svcPanels');
    slugs.forEach(sl=>{if(!serviceAnswers[sl])serviceAnswers[sl]={};});
    tabNav.innerHTML='';
    if(slugs.length>1){
        slugs.forEach((sl,i)=>{
            const svc=allServices.find(s=>s.slug===sl);
            const btn=document.createElement('button');
            btn.className=`svc-tab ${i===0?'active':''}`;
            btn.innerHTML=`<i class="fas ${svc?.icon??'fa-circle'} mr-1.5"></i>${svc?.name??sl}`;
            btn.onclick=()=>switchSvcTab(sl,btn);
            tabNav.appendChild(btn);
        });
    }
    panels.innerHTML=slugs.map((sl,i)=>`
        <div id="panel_${sl}" class="svc-detail-panel ${i===0?'':'hidden'}">
            ${renderServicePanel(sl)}
        </div>`).join('');

    setTimeout(() => {
        if (selectedServices.has('air_ticket')) syncAllAtSegments();
        if (selectedServices.has('transport'))  syncAllTrSegments();
        if (selectedServices.has('hotel')) {
            document.querySelectorAll('.hotel-segment').forEach(seg => {
                const idx       = parseInt(seg.dataset.idx ?? 0);
                const savedCity = seg.querySelector('.hotel-seg-city')?.dataset.savedCity ?? '';
                loadHotelCitiesFromCommon(idx, savedCity);
            });
        }
        if (selectedServices.has('package') || selectedServices.has('tour_package')) {
            const slug   = selectedServices.has('tour_package') ? 'tour_package' : 'package';
            const saved  = serviceAnswers[slug] ?? {};
            buildPkgDestinations(saved.destinations ?? []).then(() => {
                renderPkgAccommodations(saved.accommodations ?? []);
            });
        }
        if (selectedServices.has('visa')) {
            const commonCtries = collectCountries();
            // Load categories for each segment's country
            document.querySelectorAll('.visa-segment').forEach(seg => {
                const idx = parseInt(seg.dataset.idx ?? 0);
                const sel = seg.querySelector('.visa-seg-country');
                if (sel?.value) {
                    onVisaSegCountryChange(sel, idx);
                } else if (commonCtries.length && sel) {
                    sel.value = commonCtries[0].sys_id;
                    onVisaSegCountryChange(sel, idx);
                }
                updateVisaSelfBearerList(idx);
            });
        }
    }, 50);
}

function renderServicePanel(slug) {
    const saved = serviceAnswers[slug] ?? {};
    if (slug === 'air_ticket')                         return renderAirTicketPanel(saved);
    if (slug === 'hotel')                              return renderHotelPanel(saved);
    if (slug === 'package' || slug === 'tour_package') return renderPackagePanel(saved);
    if (slug === 'visa')                               return renderVisaPanel(saved);
    if (slug === 'umrah')                              return renderUmrahPanel(saved);
    if (slug === 'transport')                          return renderTransportPanel(saved);
    const svc    = allServices.find(s=>s.slug===slug);
    const fields = svc?.fields ?? [];
    return fields.length ? renderDynamicFields(slug, fields, saved) : renderGenericPanel(slug, saved);
}

/* ── Air Ticket Panel ── */
function renderAirTicketPanel(saved) {
    const segType  = saved.segment_type ?? 'one_way';
    const segments = saved.segments ?? [{}];
    return `<div>
        <!-- Segment Type Selector -->
        <div class="mb-5">
            <label class="f-label mb-2 block"><i class="fas fa-route mr-1 text-indigo-400"></i>Journey Type</label>
            <div class="flex gap-2 flex-wrap" id="atSegTypeRow">
                ${[
                    ['one_way',    'fa-arrow-right',  'One Way'],
                    ['round_trip', 'fa-arrows-rotate','Round Trip'],
                    ['multi_city', 'fa-code-branch',  'Multi City'],
                ].map(([v, icon, label]) => `
                    <button type="button"
                        class="at-seg-type-btn flex items-center gap-2 px-4 py-2 rounded-lg border-2 text-sm font-semibold transition
                            ${segType === v
                                ? 'border-indigo-500 bg-indigo-50 text-indigo-700'
                                : 'border-slate-200 bg-white text-slate-500 hover:border-indigo-300'}"
                        data-type="${v}"
                        onclick="onAtSegTypeChange('${v}')">
                        <i class="fas ${icon} text-xs"></i>${label}
                    </button>
                `).join('')}
            </div>
        </div>

        <!-- Round Trip shorthand (shown only for round_trip) -->
        <div id="atRoundTripHint" class="${segType === 'round_trip' ? '' : 'hidden'} mb-4 bg-indigo-50 border border-indigo-200 rounded-lg px-4 py-2.5 text-xs text-indigo-700">
            <i class="fas fa-info-circle mr-1.5"></i>
            Round Trip: fill Outbound segment — Return segment auto-generates mirrored. Edit return details below if needed.
        </div>

        <div id="atSegments" class="space-y-4">
            ${segments.map((seg, i) => atSegmentHtml(seg, i)).join('')}
        </div>
        <button type="button" id="atAddSegBtn" onclick="addAtSegment()"
            class="add-more-btn mt-3 ${segType === 'round_trip' ? 'hidden' : ''}">
            <i class="fas fa-plus text-xs"></i>Add Segment
        </button>
    </div>`;
}

function onAtSegTypeChange(type) {
    // Update button styles
    document.querySelectorAll('.at-seg-type-btn').forEach(btn => {
        const active = btn.dataset.type === type;
        btn.className = btn.className
            .replace(/border-indigo-500 bg-indigo-50 text-indigo-700|border-slate-200 bg-white text-slate-500 hover:border-indigo-300/g, '').trim()
            + (active
                ? ' border-indigo-500 bg-indigo-50 text-indigo-700'
                : ' border-slate-200 bg-white text-slate-500 hover:border-indigo-300');
    });

    // Show/hide hint and add-segment button
    document.getElementById('atRoundTripHint')?.classList.toggle('hidden', type !== 'round_trip');
    document.getElementById('atAddSegBtn')?.classList.toggle('hidden', type === 'round_trip');

    // Adjust segments
    const container = document.getElementById('atSegments');
    if (!container) return;

    if (type === 'one_way') {
        // Keep only first segment
        while (container.children.length > 1) container.lastElementChild.remove();
    } else if (type === 'round_trip') {
        // Ensure exactly 2 segments
        while (container.children.length > 2) container.lastElementChild.remove();
        if (container.children.length < 2) {
            const existing = container.children[0];
            // Mirror return segment from outbound
            const fromEl = existing?.querySelector('.at-from');
            const toEl   = existing?.querySelector('.at-to');
            const from   = fromEl?.value?.trim() ?? '';
            const to     = toEl?.value?.trim()   ?? '';
            const tmp = document.createElement('div');
            tmp.innerHTML = atSegmentHtml({ from: to, to: from, route: to && from ? `${to} - ${from}` : '' }, 1);
            container.appendChild(tmp.firstElementChild);
            // Label the segments
            _labelAtSegments(type);
        }
    }
    // multi_city — no auto change, user adds manually

    // Store segment_type in serviceAnswers
    setAns('air_ticket', 'segment_type', type);
    syncAllAtSegments();
}

function _labelAtSegments(type) {
    const segs = document.querySelectorAll('.at-segment');
    segs.forEach((seg, i) => {
        const label = seg.querySelector('.at-seg-label');
        if (!label) return;
        if (type === 'round_trip') {
            label.textContent = i === 0 ? 'Outbound' : 'Return';
        } else {
            label.textContent = `Segment ${i + 1}`;
        }
    });
}

function atSegmentHtml(seg={}, idx=0) {
    const spInsId = `atSpIns${idx}`;
    const spIns   = seg.special_instruction ?? [];
    const spHtml  = spIns.length
        ? spIns.map(v=>`<div class="multi-row"><input type="text" class="f-input seg-spins-input" value="${esc(v)}" placeholder="e.g. Window seat…"><button type="button" onclick="this.parentElement.remove();syncAtSegment(${idx})" class="del-btn"><i class="fas fa-times text-xs"></i></button></div>`).join('')
        : '';
    return `<div class="at-segment bg-slate-50 border border-slate-200 rounded-xl p-4" id="atSeg${idx}" data-idx="${idx}">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wide"><i class="fas fa-plane mr-1 text-indigo-400"></i><span class="at-seg-label">Segment ${idx+1}</span></span>
            ${idx>0?`<button type="button" onclick="removeAtSegment(${idx})" class="text-red-400 hover:text-red-600 text-xs"><i class="fas fa-trash"></i></button>`:''}
        </div>
        <div class="grid grid-cols-3 gap-3 mb-3">
            <div>
                <label class="f-label">Route</label>
                <input type="text" class="f-input at-route" data-idx="${idx}"
                    placeholder="DAC - BKK" value="${esc(seg.route??'')}"
                    oninput="onAtRouteInput(this,${idx})"
                    onblur="onAtRouteBlur(this,${idx})">
            </div>
            <div>
                <label class="f-label">From</label>
                <input type="text" class="f-input at-from" data-idx="${idx}"
                    placeholder="DAC" value="${esc(seg.from??'')}" maxlength="5"
                    oninput="onAtFromTo(${idx})" style="text-transform:uppercase;">
            </div>
            <div>
                <label class="f-label">To</label>
                <input type="text" class="f-input at-to" data-idx="${idx}"
                    placeholder="BKK" value="${esc(seg.to??'')}" maxlength="5"
                    oninput="onAtFromTo(${idx})" style="text-transform:uppercase;">
            </div>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-3">
            <div class="sm:col-span-2">
                <label class="f-label">Airline Preference <span class="opt">(opt)</span></label>
                <input type="text" class="f-input at-airline" data-idx="${idx}"
                    placeholder="e.g. Emirates, Biman…" value="${esc(seg.airline??'')}"
                    oninput="syncAtSegment(${idx})">
            </div>
            <div>
                <label class="f-label">Class</label>
                <select class="f-input at-class" data-idx="${idx}" onchange="syncAtSegment(${idx})">
                    <option value="">—</option>
                    <option value="Economy"  ${(seg.class??'')==='Economy' ?'selected':''}>Economy</option>
                    <option value="Premium"  ${(seg.class??'')==='Premium' ?'selected':''}>Premium</option>
                    <option value="Business" ${(seg.class??'')==='Business'?'selected':''}>Business</option>
                    <option value="First"    ${(seg.class??'')==='First'   ?'selected':''}>First</option>
                </select>
            </div>
            <div>
                <label class="f-label">Luggage</label>
                <div class="flex gap-1">
                    <input type="number" class="f-input at-lug-val" data-idx="${idx}"
                        min="0" placeholder="0" value="${esc(seg.luggage?.value??'')}"
                        style="width:60px;flex-shrink:0" oninput="syncAtSegment(${idx})">
                    <select class="f-input at-lug-unit" data-idx="${idx}" onchange="syncAtSegment(${idx})">
                        <option value="Pieces" ${(seg.luggage?.unit??'')==='Pieces'?'selected':''}>Pcs</option>
                        <option value="Kg"     ${(seg.luggage?.unit??'')==='Kg'    ?'selected':''}>Kg</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
            <div>
                <label class="f-label">Departure <span class="opt">(opt)</span></label>
                <input type="date" class="f-input at-dep" data-idx="${idx}"
                    value="${esc(seg.departure_date??'')}" onchange="syncAtSegment(${idx})">
            </div>
            <div>
                <label class="f-label">Arrival <span class="opt">(opt)</span></label>
                <input type="date" class="f-input at-arr" data-idx="${idx}"
                    value="${esc(seg.arrival_date??'')}" onchange="syncAtSegment(${idx})">
            </div>
            <div>
                <label class="f-label">Journey Flexibility</label>
                <select class="f-input at-flex" data-idx="${idx}" onchange="syncAtSegment(${idx})">
                    <option value="">—</option>
                    <option value="Fixed"          ${(seg.date_flexibility??'')==='Fixed'         ?'selected':''}>Fixed</option>
                    <option value="±3 days"        ${(seg.date_flexibility??'')==='±3 days'       ?'selected':''}>±3 days</option>
                    <option value="±7 days"        ${(seg.date_flexibility??'')==='±7 days'       ?'selected':''}>±7 days</option>
                    <option value="Flexible"       ${(seg.date_flexibility??'')==='Flexible'      ?'selected':''}>Flexible</option>
                    <option value="Specific month" ${(seg.date_flexibility??'')==='Specific month'?'selected':''}>Specific month</option>
                </select>
            </div>
        </div>
        <div class="pt-3 border-t border-slate-200">
            <label class="f-label text-amber-600"><i class="fas fa-triangle-exclamation mr-1"></i>Special Instructions</label>
            <div id="${spInsId}" class="space-y-1.5 mb-1.5">${spHtml}</div>
            <button type="button" onclick="addSegSpInsRow('${spInsId}');syncAtSegment(${idx})"
                class="add-more-btn"><i class="fas fa-plus text-xs"></i>Add</button>
        </div>
    </div>`;
}

function onAtRouteInput(inp, idx) {
    const val = inp.value.toUpperCase();
    inp.value = val;
    const match = val.match(/^([A-Z]{2,5})\s*[-–]\s*([A-Z]{2,5})$/);
    if (match) {
        const seg = document.getElementById(`atSeg${idx}`);
        const fEl = seg?.querySelector('.at-from');
        const tEl = seg?.querySelector('.at-to');
        if (fEl) fEl.value = match[1];
        if (tEl) tEl.value = match[2];
    }
    syncAtSegment(idx);
}

function onAtRouteBlur(inp, idx) {
    const val = inp.value.trim().toUpperCase();
    const match = val.match(/^([A-Z]{2,5})\s*[-–]?\s*([A-Z]{2,5})$/);
    if (match && !val.includes('-')) inp.value = match[1] + ' - ' + match[2];
    syncAtSegment(idx);
}

function onAtFromTo(idx) {
    const seg  = document.getElementById(`atSeg${idx}`);
    const from = (seg?.querySelector('.at-from')?.value ?? '').toUpperCase().trim();
    const to   = (seg?.querySelector('.at-to')?.value   ?? '').toUpperCase().trim();
    const rEl  = seg?.querySelector('.at-route');
    if (from && to && rEl) rEl.value = from + ' - ' + to;
    else if (from && rEl) rEl.value = from;
    if (seg?.querySelector('.at-from')) seg.querySelector('.at-from').value = from;
    if (seg?.querySelector('.at-to'))   seg.querySelector('.at-to').value   = to;
    syncAtSegment(idx);
}

function addAtSegment() {
    const c   = document.getElementById('atSegments');
    const idx = c.children.length;
    const tmp = document.createElement('div');
    tmp.innerHTML = atSegmentHtml({}, idx);
    c.appendChild(tmp.firstElementChild);
}

function removeAtSegment(idx) {
    document.getElementById(`atSeg${idx}`)?.remove();
    syncAllAtSegments();
}

function syncAtSegment(idx) { syncAllAtSegments(); }

function syncAllAtSegments() {
    const segments = [];
    document.querySelectorAll('.at-segment').forEach(seg => {
        const spInsId = `atSpIns${seg.dataset.idx}`;
        segments.push({
            route:            seg.querySelector('.at-route')?.value?.trim() ?? '',
            from:             seg.querySelector('.at-from')?.value?.trim()?.toUpperCase() ?? '',
            to:               seg.querySelector('.at-to')?.value?.trim()?.toUpperCase() ?? '',
            airline:          seg.querySelector('.at-airline')?.value?.trim() ?? '',
            class:            seg.querySelector('.at-class')?.value ?? '',
            luggage: {
                value: seg.querySelector('.at-lug-val')?.value?.trim() ?? '',
                unit:  seg.querySelector('.at-lug-unit')?.value ?? 'Pieces',
            },
            departure_date:   seg.querySelector('.at-dep')?.value ?? '',
            arrival_date:     seg.querySelector('.at-arr')?.value ?? '',
            date_flexibility: seg.querySelector('.at-flex')?.value ?? '',
            special_instruction: collectSegSpIns(spInsId),
        });
    });
    setAns('air_ticket', 'segments', segments);
    // Preserve segment_type from active button
    const activeTypeBtn = document.querySelector('.at-seg-type-btn.border-indigo-500');
    if (activeTypeBtn) setAns('air_ticket', 'segment_type', activeTypeBtn.dataset.type);
}

/* ── Hotel Panel ── */
function renderHotelPanel(saved) {
    const segments = saved.segments ?? [{}];
    return `<div>
        <div id="hotelSegments" class="space-y-4">
            ${segments.map((seg,i) => hotelSegmentHtml(seg, i)).join('')}
        </div>
        <button type="button" onclick="addHotelSegment()" class="add-more-btn mt-3">
            <i class="fas fa-plus text-xs"></i>Add Hotel Segment
        </button>
        <div class="mt-4 pt-4 border-t border-slate-100">
            <label class="f-label">Booking Date Flexibility</label>
            <select class="f-input" id="hotelFlexibility" onchange="setAns('hotel','booking_flexibility',this.value)">
                <option value="">Select…</option>
                <option value="Fixed"    ${(saved.booking_flexibility??'')==='Fixed'   ?'selected':''}>Fixed dates</option>
                <option value="±2 days"  ${(saved.booking_flexibility??'')==='±2 days' ?'selected':''}>± 2 days</option>
                <option value="±5 days"  ${(saved.booking_flexibility??'')==='±5 days' ?'selected':''}>± 5 days</option>
                <option value="Flexible" ${(saved.booking_flexibility??'')==='Flexible'?'selected':''}>Fully flexible</option>
            </select>
        </div>
    </div>`;
}

function hotelSegmentHtml(seg={}, idx=0) {
    const segId   = `hotelSeg${idx}`;
    const spInsId = `htSpIns${idx}`;
    const spIns   = seg.special_instruction ?? [];
    const spHtml  = spIns.map(v=>`<div class="multi-row"><input type="text" class="f-input seg-spins-input" value="${esc(v)}" placeholder="e.g. High floor preferred…"><button type="button" onclick="this.parentElement.remove();syncAllHotelSegments()" class="del-btn"><i class="fas fa-times text-xs"></i></button></div>`).join('');

    // Build city options from common selected countries
    const commonCountries = collectCountries();

    return `<div class="hotel-segment bg-slate-50 border border-slate-200 rounded-xl p-4 relative" id="${segId}" data-idx="${idx}">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wide"><i class="fas fa-hotel mr-1 text-indigo-400"></i>Segment ${idx+1}</span>
            ${idx>0 ? `<button type="button" onclick="removeHotelSegment(${idx})" class="text-red-400 hover:text-red-600 text-xs"><i class="fas fa-trash"></i></button>` : ''}
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="f-label">City / Location</label>
                <select class="f-input hotel-seg-city" data-idx="${idx}" data-saved-city="${esc(seg.city_sys_id??'')}" onchange="onHotelCityChange(this,${idx})">
                    <option value="">Loading cities…</option>
                </select>
            </div>
            <div class="sm:col-span-1">
                <label class="f-label">Hotel Name <span class="opt">(search or type)</span></label>
                <div class="relative hotel-name-wrap">
                    <i class="fas fa-hotel absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs pointer-events-none"></i>
                    <input type="text" class="f-input pl-8 hotel-seg-name" data-idx="${idx}"
                        placeholder="Search hotel…" value="${esc(seg.hotel_name??'')}"
                        autocomplete="off"
                        oninput="onHotelSegNameInput(this, ${idx})"
                        onfocus="onHotelSegNameInput(this, ${idx})">
                    <input type="hidden" class="hotel-sys-id" data-idx="${idx}" value="${esc(seg.hotel_sys_id??'')}">
                    <input type="hidden" class="hotel-seg-country-id" data-idx="${idx}" value="${esc(seg.country_sys_id??'')}">
                    <div class="hotel-drop hidden hotel-name-drop" id="hotelNameDrop${idx}"></div>
                </div>
                <div class="flex items-center gap-2 mt-1.5">
                    <p class="text-[10px] text-slate-400 flex-1">Not found? Create it:</p>
                    <button type="button"
                        onclick="openNewHotelModal(${idx})"
                        class="flex items-center gap-1 px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 rounded-lg text-xs font-semibold transition">
                        <i class="fas fa-plus text-[10px]"></i>New Hotel
                    </button>
                    <button type="button"
                        onclick="refreshHotelSearch(${idx})"
                        class="flex items-center gap-1 px-2.5 py-1 bg-gray-50 hover:bg-gray-100 text-gray-500 rounded-lg text-xs font-semibold transition" title="Refresh hotel list">
                        <i class="fas fa-rotate-right text-[10px]"></i>Refresh
                    </button>
                </div>
            </div>
            <div>
                <label class="f-label">Check-in Date</label>
                <input type="date" class="f-input hotel-seg-checkin" data-idx="${idx}"
                    value="${esc(seg.check_in??'')}" onchange="syncAllHotelSegments()">
            </div>
            <div>
                <label class="f-label">Check-out Date</label>
                <input type="date" class="f-input hotel-seg-checkout" data-idx="${idx}"
                    value="${esc(seg.check_out??'')}" onchange="syncAllHotelSegments()">
            </div>
            <div class="sm:col-span-2 pt-3 border-t border-slate-200">
                <label class="f-label text-amber-600"><i class="fas fa-triangle-exclamation mr-1"></i>Special Instructions</label>
                <div id="${spInsId}" class="space-y-1.5 mb-1.5">${spHtml}</div>
                <button type="button" onclick="addSegSpInsRow('${spInsId}');syncAllHotelSegments()"
                    class="add-more-btn"><i class="fas fa-plus text-xs"></i>Add</button>
            </div>
        </div>
    </div>`;
}

// Load cities for a hotel segment from all common-selected countries
let hotelSegTimers = {};
async function loadHotelCitiesFromCommon(idx, savedCityId = '') {
    const citySelect = document.querySelector(`.hotel-seg-city[data-idx="${idx}"]`);
    if (!citySelect) return;

    const commonCountries = collectCountries();
    if (!commonCountries.length) {
        citySelect.innerHTML = '<option value="">Select destination countries first (Common section)</option>';
        return;
    }

    citySelect.innerHTML = '<option value="">Loading cities…</option>';
    try {
        const allCities = [];
        for (const c of commonCountries) {
            const res  = await fetch(`${COUNTRIES_API}?action=cities&country=${encodeURIComponent(c.sys_id)}`);
            const json = await res.json();
            (json.data ?? []).forEach(city => {
                if (!allCities.find(x => x.sys_id === city.sys_id))
                    allCities.push({ ...city, country_sys_id: c.sys_id, country_name: c.name });
            });
        }
        allCities.sort((a, b) => a.name.localeCompare(b.name));

        if (!allCities.length) {
            citySelect.innerHTML = '<option value="">No cities found</option>';
        } else {
            citySelect.innerHTML = `<option value="">Select city…</option>` +
                allCities.map(c =>
                    `<option value="${esc(c.sys_id)}" data-name="${esc(c.name)}" data-country="${esc(c.country_sys_id)}"
                        ${c.sys_id === savedCityId ? 'selected' : ''}>${esc(c.name)}${commonCountries.length > 1 ? ' ('+esc(c.country_name)+')' : ''}</option>`
                ).join('');
        }
    } catch {
        citySelect.innerHTML = '<option value="">Failed to load cities</option>';
    }
    syncAllHotelSegments();
}

// Hotel panel hotel cache: cityId → hotels[]
const _hotelCache = {};

function onHotelCityChange(sel, idx) {
    const nameInp = document.querySelector(`.hotel-seg-name[data-idx="${idx}"]`);
    const sysInp  = document.querySelector(`.hotel-sys-id[data-idx="${idx}"]`);
    if (nameInp) nameInp.value = '';
    if (sysInp)  sysInp.value  = '';
    syncAllHotelSegments();
    // Load all hotels for this city
    const cityId    = sel.value;
    const countryId = sel.options[sel.selectedIndex]?.dataset.country ?? '';
    if (cityId) loadHotelSegHotels(idx, cityId, countryId);
    else {
        const drop = document.getElementById(`hotelNameDrop${idx}`);
        if (drop) { drop.innerHTML = ''; drop.classList.add('hidden'); }
    }
}

async function loadHotelSegHotels(idx, cityId, countryId) {
    if (!_hotelCache[cityId]) {
        try {
            let url = `${COUNTRIES_API}?action=hotels&limit=50`;
            if (countryId) url += `&country=${encodeURIComponent(countryId)}`;
            if (cityId)    url += `&city=${encodeURIComponent(cityId)}`;
            const res  = await fetch(url);
            const json = await res.json();
            _hotelCache[cityId] = json.data ?? [];
        } catch { _hotelCache[cityId] = []; }
    }
    renderHotelSegList(idx, cityId, '');
}

function renderHotelSegList(idx, cityId, query) {
    const drop = document.getElementById(`hotelNameDrop${idx}`);
    if (!drop) return;
    const all      = _hotelCache[cityId] ?? [];
    const q        = query.toLowerCase();
    const filtered = q ? all.filter(h => h.name.toLowerCase().includes(q)) : all;
    const nameInp  = document.querySelector(`.hotel-seg-name[data-idx="${idx}"]`);

    if (!filtered.length && !all.length) {
        drop.innerHTML = `<div class="px-4 py-3 text-xs text-slate-400">No hotels in this city yet — create new</div>`;
    } else if (!filtered.length) {
        drop.innerHTML = `<div class="px-4 py-3 text-xs text-slate-400">No match — type name directly or create new</div>`;
    } else {
        drop.innerHTML = filtered.map(h => {
            const isSelected = nameInp?.value === h.name;
            return `<div class="hotel-drop-item px-4 py-2.5 cursor-pointer hover:bg-indigo-50 text-sm border-b border-slate-50 last:border-0 flex items-center justify-between ${isSelected ? 'bg-indigo-50' : ''}"
                data-seg="${idx}" data-hotel-id="${esc(h.sys_id ?? h.id ?? '')}" data-hotel-name="${esc(h.name)}">
                <div>
                    <div class="font-medium text-slate-800">${esc(h.name)}</div>
                    ${h.star_rating ? `<div class="text-[10px] text-amber-400">${'★'.repeat(parseInt(h.star_rating))}</div>` : ''}
                </div>
                ${isSelected ? '<i class="fas fa-check text-indigo-500 text-xs flex-shrink-0"></i>' : ''}
            </div>`;
        }).join('');
    }
    drop.classList.remove('hidden');
}

function onHotelSegNameInput(inp, idx) {
    syncHotelSegment(idx);
    const citySel = document.querySelector(`.hotel-seg-city[data-idx="${idx}"]`);
    const cityId  = citySel?.value ?? '';
    const drop    = document.getElementById(`hotelNameDrop${idx}`);
    if (!drop) return;
    if (!cityId) {
        drop.innerHTML = `<div class="px-4 py-3 text-xs text-slate-400">Select a city first</div>`;
        drop.classList.remove('hidden');
        return;
    }
    renderHotelSegList(idx, cityId, inp.value.trim());
}

// Delegate click for hotel drop items (hotel segment)
document.addEventListener('click', e => {
    const item = e.target.closest('.hotel-drop-item[data-seg]');
    if (item) {
        e.stopPropagation();
        const segIdx  = parseInt(item.dataset.seg);
        const hotelName = item.dataset.hotelName ?? '';
        const hotelId   = item.dataset.hotelId   ?? '';
        const seg      = document.getElementById(`hotelSeg${segIdx}`);
        const nameInp  = seg?.querySelector('.hotel-seg-name');
        const sysIdInp = seg?.querySelector('.hotel-sys-id');
        if (nameInp)  nameInp.value  = hotelName;
        if (sysIdInp) sysIdInp.value = hotelId;
        // Re-render to show checkmark, then hide
        const citySel = seg?.querySelector('.hotel-seg-city');
        if (citySel?.value) renderHotelSegList(segIdx, citySel.value, '');
        setTimeout(() => {
            document.getElementById(`hotelNameDrop${segIdx}`)?.classList.add('hidden');
        }, 600);
        syncAllHotelSegments();
        return;
    }
    if (!e.target.closest('.hotel-name-wrap')) {
        document.querySelectorAll('.hotel-name-drop').forEach(d => d.classList.add('hidden'));
    }
});

// Show hotel list on focus
document.addEventListener('focusin', e => {
    const inp = e.target.closest('.hotel-seg-name');
    if (inp) {
        const idx     = parseInt(inp.dataset.idx);
        const citySel = document.querySelector(`.hotel-seg-city[data-idx="${idx}"]`);
        const cityId  = citySel?.value ?? '';
        if (cityId) renderHotelSegList(idx, cityId, inp.value.trim());
        else {
            const drop = document.getElementById(`hotelNameDrop${idx}`);
            if (drop) { drop.innerHTML = `<div class="px-4 py-3 text-xs text-slate-400">Select a city first</div>`; drop.classList.remove('hidden'); }
        }
    }
});

function addHotelSegment() {
    const container = document.getElementById('hotelSegments');
    const idx = container.children.length;
    const tmp = document.createElement('div');
    tmp.innerHTML = hotelSegmentHtml({}, idx);
    container.appendChild(tmp.firstElementChild);
    loadHotelCitiesFromCommon(idx);
}

function removeHotelSegment(idx) {
    document.getElementById(`hotelSeg${idx}`)?.remove();
    syncAllHotelSegments();
}

function syncHotelSegment(idx) {
    syncAllHotelSegments();
}

function syncAllHotelSegments() {
    const segments = [];
    document.querySelectorAll('.hotel-segment').forEach((seg, i) => {
        const citySel    = seg.querySelector('.hotel-seg-city');
        const nameInp    = seg.querySelector('.hotel-seg-name');
        const sysIdInp   = seg.querySelector('.hotel-sys-id');
        const checkin    = seg.querySelector('.hotel-seg-checkin');
        const checkout   = seg.querySelector('.hotel-seg-checkout');
        const cityOpt    = citySel?.options[citySel.selectedIndex];
        const spInsId    = `htSpIns${seg.dataset.idx ?? i}`;
        segments.push({
            country_sys_id:      cityOpt?.dataset.country ?? '',
            country_name:        (() => { const cId = cityOpt?.dataset.country; return allCountries.find(c => c.sys_id === cId)?.name ?? ''; })(),
            city_sys_id:         citySel?.value    ?? '',
            city_name:           cityOpt?.dataset.name ?? cityOpt?.text ?? '',
            hotel_name:          nameInp?.value?.trim()  ?? '',
            hotel_sys_id:        sysIdInp?.value   ?? '',
            check_in:            checkin?.value    ?? '',
            check_out:           checkout?.value   ?? '',
            special_instruction: collectSegSpIns(spInsId),
        });
    });
    setAns('hotel', 'segments', segments);
}

/* ── PACKAGE PANEL ── */
/* ══════════════════════════════════════════════════════
   PACKAGE PANEL — rebuilt
   Destinations  → from common countries (no separate select)
   Accommodations → city from pkg-selected cities, hotel search
══════════════════════════════════════════════════════ */
function renderPackagePanel(saved) {
    const accommodations = saved.accommodations ?? [{}];
    return `<div class="space-y-6">

        <!-- Basic Info -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label class="f-label">Package Title <span class="text-red-400">*</span></label>
                <input type="text" id="pkgTitle" class="f-input"
                    placeholder="e.g. 5 Days Dubai Honeymoon Package"
                    value="${esc(saved.title ?? '')}"
                    oninput="setAns('package','title',this.value)">
            </div>
            <div>
                <label class="f-label">Package Type</label>
                <select id="pkgType" class="f-input" onchange="setAns('package','type',this.value)">
                    <option value="">Select…</option>
                    ${['Group Tour','FIT (Individual)','Honeymoon','Family','Corporate','Adventure','Luxury','Custom'].map(t=>{
                        const v=t==='FIT (Individual)'?'FIT':t==='Group Tour'?'Group':t;
                        return `<option value="${v}" ${(saved.type??'')===v?'selected':''}>${t}</option>`;
                    }).join('')}
                </select>
            </div>
            <div>
                <label class="f-label">Sell Currency</label>
                <select id="pkgCurrency" class="f-input" onchange="setAns('package','currency',this.value)">
                    <option value="">Select…</option>
                    ${['BDT','USD','EUR','GBP','AED','SAR'].map(c=>`<option value="${c}" ${(saved.currency??'')===c?'selected':''}>${c}</option>`).join('')}
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="f-label">Description <span class="opt">(optional)</span></label>
                <textarea id="pkgDesc" class="f-input" rows="3"
                    placeholder="Inclusions, exclusions, highlights…"
                    oninput="setAns('package','description',this.value)">${esc(saved.description ?? '')}</textarea>
            </div>
        </div>

        <!-- DESTINATIONS — driven by common countries -->
        <div class="border-t border-slate-100 pt-4">
            <div class="flex items-center justify-between mb-3">
                <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider">
                    <i class="fas fa-map-marker-alt mr-1 text-indigo-400"></i>Destinations
                </h4>
                <span class="text-[10px] text-slate-400">Countries from common section</span>
            </div>
            <div id="pkgDestWrapper" class="space-y-3"></div>
            <p id="pkgDestEmpty" class="hidden text-xs text-amber-600 bg-amber-50 rounded-lg px-3 py-2 mt-2">
                <i class="fas fa-triangle-exclamation mr-1"></i>
                Select destination countries in the Common section above first.
            </p>
        </div>

        <!-- ACCOMMODATIONS -->
        <div class="border-t border-slate-100 pt-4">
            <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">
                <i class="fas fa-hotel mr-1 text-indigo-400"></i>Accommodations
                <span class="font-normal text-slate-400 normal-case ml-1">(per selected city)</span>
            </h4>
            <div id="pkgAccommodations" class="space-y-4"></div>
        </div>

        <!-- Special Instruction -->
        <div class="border-t border-slate-100 pt-4">
            <label class="f-label text-amber-600"><i class="fas fa-triangle-exclamation mr-1"></i>Special Instructions <span class="opt font-normal text-slate-400">(for entire package)</span></label>
            <div id="pkgSpIns" class="space-y-1.5 mb-1.5">
                ${(saved.special_instruction ?? []).map(v =>
                    `<div class="multi-row"><input type="text" class="f-input seg-spins-input" value="${esc(v)}" placeholder="e.g. Vegetarian meals required…">
                    <button type="button" onclick="this.parentElement.remove();syncPackageData()" class="del-btn"><i class="fas fa-times text-xs"></i></button></div>`
                ).join('')}
            </div>
            <button type="button" onclick="addSegSpInsRow('pkgSpIns');syncPackageData()"
                class="add-more-btn"><i class="fas fa-plus text-xs"></i>Add</button>
        </div>
    </div>`;
}

/* ── Destinations: build from common countries + city checkboxes ── */
async function buildPkgDestinations(savedDestinations = []) {
    const wrapper  = document.getElementById('pkgDestWrapper');
    const emptyMsg = document.getElementById('pkgDestEmpty');
    if (!wrapper) return;

    const commonCtries = collectCountries();
    if (!commonCtries.length) {
        wrapper.innerHTML = '';
        emptyMsg?.classList.remove('hidden');
        return;
    }
    emptyMsg?.classList.add('hidden');
    wrapper.innerHTML = `<div class="text-slate-400 text-sm py-2"><i class="fas fa-spinner fa-spin mr-1"></i>Loading cities…</div>`;

    try {
        // Fetch cities for all common countries
        const countryCity = []; // [{country, cities:[]}]
        for (const c of commonCtries) {
            const res  = await fetch(`${COUNTRIES_API}?action=cities&country=${encodeURIComponent(c.sys_id)}`);
            const json = await res.json();
            countryCity.push({ country: c, cities: json.data ?? [] });
        }

        // Flatten saved city ids for pre-check
        const savedCityIds = (savedDestinations ?? []).flatMap(d => d.city_ids ?? []);

        wrapper.innerHTML = countryCity.map(({ country, cities }) => {
            if (!cities.length) return `
                <div class="bg-slate-50 border border-slate-200 rounded-lg px-4 py-3">
                    <p class="text-xs font-bold text-slate-500 mb-1">${esc(country.name)}</p>
                    <p class="text-xs text-slate-400">No cities configured for this country.</p>
                </div>`;
            return `
            <div class="bg-slate-50 border border-slate-200 rounded-lg p-3" data-country-dest="${esc(country.sys_id)}">
                <p class="text-xs font-bold text-slate-600 mb-2 flex items-center gap-1.5">
                    <i class="fas fa-flag text-indigo-400" style="font-size:9px"></i>${esc(country.name)}
                    <span class="font-normal text-slate-400">(select cities)</span>
                </p>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-1.5">
                    ${cities.map(city => `
                        <label class="flex items-center gap-2 text-xs cursor-pointer hover:bg-indigo-50 px-2 py-1.5 rounded select-none">
                            <input type="checkbox" class="pkg-city-cb w-3.5 h-3.5 text-indigo-600 rounded border-slate-300 flex-shrink-0"
                                value="${esc(city.sys_id)}" data-name="${esc(city.name)}"
                                data-country="${esc(country.sys_id)}" data-country-name="${esc(country.name)}"
                                ${savedCityIds.includes(city.sys_id) ? 'checked' : ''}
                                onchange="onPkgCityCbChange()">
                            <span class="text-slate-700 leading-tight">${esc(city.name)}</span>
                        </label>
                    `).join('')}
                </div>
            </div>`;
        }).join('');

    } catch(e) {
        console.error('Pkg dest load error:', e);
        wrapper.innerHTML = `<div class="text-red-400 text-sm py-2">Failed to load cities.</div>`;
    }
    syncPackageData();
}

function onPkgCityCbChange() {
    syncPackageData();
    const slug  = selectedServices.has('tour_package') ? 'tour_package' : 'package';
    const saved = serviceAnswers[slug] ?? {};
    renderPkgAccommodations(saved.accommodations ?? []);
}


/* ── Accommodation: city-keyed hotel cards ── */
// _pkgHotelCache[city_sys_id] = [{sys_id, name, city_name, country_name, star_rating}]
const _pkgHotelCache = {};

// Get all currently checked cities from destination checkboxes
function getPkgSelectedCities() {
    return [...document.querySelectorAll('.pkg-city-cb:checked')].map(cb => ({
        sys_id:       cb.value,
        name:         cb.dataset.name         ?? '',
        country:      cb.dataset.country      ?? '',
        country_name: cb.dataset.countryName  ?? '',
    }));
}

function pkgAccHtml(a = {}, idx = 0) {
    // This is now a legacy wrapper — real render is via renderPkgAccommodations()
    // Keep for edit-mode data restore only
    return '';
}

function addPkgAcc() {
    // no-op: accommodations are now city-driven, not manually added
}

// Render accommodation section: one card per selected city, multiple hotels per city
async function renderPkgAccommodations(savedAccommodations = []) {
    const container = document.getElementById('pkgAccommodations');
    if (!container) return;

    const cities = getPkgSelectedCities();
    if (!cities.length) {
        container.innerHTML = `<p class="text-xs text-slate-400 italic">Select cities in Destinations above to add hotels.</p>`;
        syncPackageData();
        return;
    }

    const multiCountry = new Set(cities.map(c => c.country)).size > 1;

    container.innerHTML = cities.map(city => {
        const cityAccs = savedAccommodations.filter(a => a.city_id === city.sys_id);
        const rows = cityAccs.length ? cityAccs : [{}];
        return `<div class="pkg-acc-card bg-slate-50 border border-slate-200 rounded-xl p-4"
                    data-city-id="${esc(city.sys_id)}"
                    data-city-name="${esc(city.name)}"
                    data-country-id="${esc(city.country)}">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <i class="fas fa-hotel text-indigo-400 text-xs"></i>
                    <span class="text-xs font-bold text-slate-600 uppercase tracking-wide">
                        ${esc(city.name)}${multiCountry ? ` <span class="text-slate-400 font-normal normal-case">(${esc(city.country_name)})</span>` : ''}
                    </span>
                </div>
            </div>
            <div class="pkg-hotel-rows space-y-3">
                ${rows.map((acc, rowIdx) => pkgHotelRowHtml(city.sys_id, city.name, city.country, city.country_name, rowIdx, acc)).join('')}
            </div>
            <button type="button"
                onclick="addPkgHotelRow('${esc(city.sys_id)}','${esc(city.name)}','${esc(city.country)}','${esc(city.country_name)}')"
                class="add-more-btn mt-3">
                <i class="fas fa-plus text-xs"></i>Add Hotel
            </button>
        </div>`;
    }).join('');

    for (const city of cities) {
        await loadPkgHotelsForCity(city.sys_id, city.country);
        // Render drop for each row of this city
        const card = document.querySelector(`.pkg-acc-card[data-city-id="${city.sys_id}"]`);
        card?.querySelectorAll('.pkg-hotel-row').forEach((row, rowIdx) => {
            const dropId = `pkgHotelDrop_${city.sys_id}_${rowIdx}`;
            renderPkgHotelListForDrop(dropId, city.sys_id, '', rowIdx);
        });
    }
    syncPackageData();
}

function pkgHotelRowHtml(cityId, cityName, countryId, countryName, rowIdx, acc = {}) {
    const dropId = `pkgHotelDrop_${cityId}_${rowIdx}`;
    return `<div class="pkg-hotel-row bg-white border border-slate-200 rounded-lg p-3 space-y-2 relative"
                data-city="${esc(cityId)}" data-row="${rowIdx}">
        ${rowIdx > 0 ? `<button type="button" onclick="removePkgHotelRow(this)"
            class="absolute top-2 right-2 w-6 h-6 flex items-center justify-center rounded-md bg-red-50 hover:bg-red-100 text-red-400 text-xs">
            <i class="fas fa-times"></i></button>` : ''}
        <div>
            <label class="f-label text-xs">Hotel <span class="text-slate-400 font-normal">#${rowIdx+1}</span></label>
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs pointer-events-none"></i>
                <input type="text" class="f-input pl-8 pkg-acc-hotel-search text-sm"
                    data-city="${esc(cityId)}" data-row="${rowIdx}" data-drop="${dropId}"
                    placeholder="Search hotel…" autocomplete="off"
                    value="${esc(acc.hotel_name ?? '')}"
                    oninput="filterPkgHotels(this,'${esc(cityId)}','${dropId}',${rowIdx})">
                <input type="hidden" class="pkg-acc-hotel-sysid"
                    data-city="${esc(cityId)}" data-row="${rowIdx}" value="${esc(acc.hotel_sys_id ?? '')}">
            </div>
            <div class="pkg-hotel-list mt-1 max-h-36 overflow-y-auto rounded-lg border border-slate-200 bg-white hidden shadow-sm" id="${dropId}">
                <div class="px-3 py-2 text-xs text-slate-400 italic">Loading…</div>
            </div>
            <div class="flex items-center gap-2 mt-1.5">
                <p class="text-[10px] text-slate-400 flex-1">Not found?</p>
                <button type="button"
                    onclick="openPkgNewHotelModal_v2('${esc(cityId)}','${esc(cityName)}','${esc(countryId)}','${esc(countryName)}',${rowIdx})"
                    class="flex items-center gap-1 px-2 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 rounded-lg text-xs font-semibold transition">
                    <i class="fas fa-plus text-[10px]"></i>Create New
                </button>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="f-label text-xs">Check-in</label>
                <input type="date" class="f-input pkg-acc-checkin"
                    data-city="${esc(cityId)}" data-row="${rowIdx}"
                    value="${esc(acc.check_in ?? '')}" onchange="syncPackageData()">
            </div>
            <div>
                <label class="f-label text-xs">Check-out</label>
                <input type="date" class="f-input pkg-acc-checkout"
                    data-city="${esc(cityId)}" data-row="${rowIdx}"
                    value="${esc(acc.check_out ?? '')}" onchange="syncPackageData()">
            </div>
        </div>
    </div>`;
}

function addPkgHotelRow(cityId, cityName, countryId, countryName) {
    const card = document.querySelector(`.pkg-acc-card[data-city-id="${cityId}"]`);
    if (!card) return;
    const rowsContainer = card.querySelector('.pkg-hotel-rows');
    const rowIdx = rowsContainer.children.length;
    const tmp = document.createElement('div');
    tmp.innerHTML = pkgHotelRowHtml(cityId, cityName, countryId, countryName, rowIdx, {});
    rowsContainer.appendChild(tmp.firstElementChild);
    // Pre-populate hotel list for new row
    if (_pkgHotelCache[cityId]) {
        renderPkgHotelListForDrop(`pkgHotelDrop_${cityId}_${rowIdx}`, cityId, '', rowIdx);
    } else {
        loadPkgHotelsForCity(cityId, countryId).then(() => {
            renderPkgHotelListForDrop(`pkgHotelDrop_${cityId}_${rowIdx}`, cityId, '', rowIdx);
        });
    }
}

function removePkgHotelRow(btn) {
    btn.closest('.pkg-hotel-row')?.remove();
    syncPackageData();
}


async function loadPkgHotelsForCity(cityId, countryId) {
    if (_pkgHotelCache[cityId]) {
        renderPkgHotelList(cityId, '');
        return;
    }
    try {
        let url = `${COUNTRIES_API}?action=hotels&limit=50`;
        if (countryId) url += `&country=${encodeURIComponent(countryId)}`;
        if (cityId)    url += `&city=${encodeURIComponent(cityId)}`;
        const res  = await fetch(url);
        const json = await res.json();
        _pkgHotelCache[cityId] = json.data ?? [];
    } catch {
        _pkgHotelCache[cityId] = [];
    }
    renderPkgHotelList(cityId, '');
}

function filterPkgHotels(inp, cityId, dropId, rowIdx) {
    syncPackageData();
    renderPkgHotelListForDrop(dropId, cityId, inp.value.trim(), rowIdx);
}

function renderPkgHotelListForDrop(dropId, cityId, query, rowIdx) {
    const listEl = document.getElementById(dropId);
    if (!listEl) return;
    const all      = _pkgHotelCache[cityId] ?? [];
    const q        = query.toLowerCase();
    const filtered = q ? all.filter(h => h.name.toLowerCase().includes(q)) : all;
    const searchInp = document.querySelector(`.pkg-acc-hotel-search[data-city="${cityId}"][data-row="${rowIdx}"]`);
    const selected  = searchInp?.value ?? '';

    if (!filtered.length) {
        listEl.innerHTML = `<div class="px-3 py-2 text-xs text-slate-400">${q ? 'No match — type name or create new' : 'No hotels in this city yet'}</div>`;
    } else {
        listEl.innerHTML = filtered.map(h => {
            const isSel = selected === h.name;
            return `<div class="pkg-hotel-list-item px-3 py-2 cursor-pointer hover:bg-indigo-50 text-sm border-b border-slate-50 last:border-0 flex items-center justify-between ${isSel ? 'bg-indigo-50' : ''}"
                data-drop="${dropId}" data-city="${esc(cityId)}" data-row="${rowIdx}"
                data-hotel-name="${esc(h.name)}" data-hotel-id="${esc(h.sys_id ?? h.id ?? '')}">
                <div>
                    <div class="font-medium text-slate-800 text-xs">${esc(h.name)}</div>
                    ${h.star_rating ? `<div class="text-[10px] text-amber-400">${'★'.repeat(parseInt(h.star_rating))}</div>` : ''}
                </div>
                ${isSel ? '<i class="fas fa-check text-indigo-500 text-xs flex-shrink-0"></i>' : ''}
            </div>`;
        }).join('');
    }
    listEl.classList.remove('hidden');
}

// Legacy alias for old calls
function renderPkgHotelList(cityId, query) {
    // find all drops for this city and render each
    document.querySelectorAll(`.pkg-acc-hotel-search[data-city="${cityId}"]`).forEach(inp => {
        const dropId = inp.dataset.drop;
        const rowIdx = parseInt(inp.dataset.row ?? 0);
        if (dropId) renderPkgHotelListForDrop(dropId, cityId, query, rowIdx);
    });
}

// Delegate click for pkg hotel list items
document.addEventListener('click', e => {
    const item = e.target.closest('.pkg-hotel-list-item');
    if (item) {
        e.stopPropagation();
        const cityId    = item.dataset.city;
        const rowIdx    = item.dataset.row;
        const dropId    = item.dataset.drop;
        const hotelName = item.dataset.hotelName;
        const hotelId   = item.dataset.hotelId;
        const inp    = document.querySelector(`.pkg-acc-hotel-search[data-city="${cityId}"][data-row="${rowIdx}"]`);
        const sysInp = document.querySelector(`.pkg-acc-hotel-sysid[data-city="${cityId}"][data-row="${rowIdx}"]`);
        if (inp)    inp.value    = hotelName;
        if (sysInp) sysInp.value = hotelId;
        // Refresh to show checkmark then hide
        renderPkgHotelListForDrop(dropId, cityId, '', parseInt(rowIdx));
        setTimeout(() => { document.getElementById(dropId)?.classList.add('hidden'); }, 600);
        syncPackageData();
        return;
    }
    // Hide all pkg hotel lists on outside click
    if (!e.target.closest('.pkg-acc-card')) {
        document.querySelectorAll('.pkg-hotel-list').forEach(l => l.classList.add('hidden'));
    }
});

// Show hotel list on search input focus (pkg)
document.addEventListener('focusin', e => {
    const inp = e.target.closest('.pkg-acc-hotel-search');
    if (inp) {
        const cityId = inp.dataset.city;
        const dropId = inp.dataset.drop;
        const rowIdx = parseInt(inp.dataset.row ?? 0);
        if (dropId) renderPkgHotelListForDrop(dropId, cityId, inp.value.trim(), rowIdx);
    }
});

/* ── New hotel modal for pkg (v2 — city-keyed) ── */
let _pkgNewHotelCityData = null;

function openPkgNewHotelModal_v2(cityId, cityName, countryId, countryName, rowIdx = 0) {
    _pkgNewHotelCityData = { cityId, cityName, countryId, countryName, rowIdx };
    _pkgAccIdx  = null; // disable old path
    _nhSegIdx   = null;

    const ctx    = document.getElementById('newHotelContext');
    const ctxTxt = document.getElementById('newHotelContextText');
    ctxTxt.textContent = [cityName, countryName].filter(Boolean).join(', ');
    ctx.classList.remove('hidden');

    ['nhName','nhAddress','nhPhone','nhEmail'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('nhStar').value = '';
    document.getElementById('newHotelModal').classList.remove('hidden');
    setTimeout(() => document.getElementById('nhName').focus(), 100);
}

/* Build city options for accommodation selects — kept as stub */
function refreshPkgAccCitySelects() {
    // Now driven by renderPkgAccommodations — re-render if cities changed
    const slug  = selectedServices.has('tour_package') ? 'tour_package' : 'package';
    const saved = serviceAnswers[slug] ?? {};
    renderPkgAccommodations(saved.accommodations ?? []);
}

/* ── Package Accommodation Hotel Search ── */
let pkgAccTimers = {};

function onPkgAccHotelInput(inp, idx) {
    const val  = inp.value.trim();
    const drop = document.getElementById(`pkgAccHotelDrop${idx}`);
    if (!drop) return;

    const citySel   = document.querySelector(`.pkg-acc-city[data-idx="${idx}"]`);
    const cityId    = citySel?.value || '';
    const countryId = citySel?.options[citySel.selectedIndex]?.dataset.country || '';

    if (!val || val.length < 2) { drop.classList.add('hidden'); return; }

    clearTimeout(pkgAccTimers[idx]);
    pkgAccTimers[idx] = setTimeout(async () => {
        try {
            let url = `${COUNTRIES_API}?action=hotels&q=${encodeURIComponent(val)}&limit=10`;
            if (countryId) url += `&country=${encodeURIComponent(countryId)}`;
            if (cityId)    url += `&city=${encodeURIComponent(cityId)}`;

            const res  = await fetch(url);
            const json = await res.json();
            const list = json.data ?? [];

            if (!list.length) {
                drop.innerHTML = `<div class="px-4 py-3 text-xs text-slate-400">No hotels found — type name directly or create new</div>`;
                drop.classList.remove('hidden'); return;
            }
            drop.innerHTML = list.map(h => {
                const safe = encodeURIComponent(JSON.stringify(h));
                return `<div class="hotel-drop-item px-4 py-2.5 cursor-pointer hover:bg-indigo-50 text-sm border-b border-slate-50 last:border-0"
                    data-acc-idx="${idx}" data-hotel='${safe}'>
                    <div class="font-medium text-slate-800">${esc(h.name)}</div>
                    <div class="text-xs text-slate-400">${esc([h.city_name,h.country_name].filter(Boolean).join(', '))}${h.star_rating?' · ★'+h.star_rating:''}</div>
                </div>`;
            }).join('');
            drop.classList.remove('hidden');
        } catch { drop.classList.add('hidden'); }
    }, 300);
}

/* ── Delegate click: pkg accommodation hotel drop ── */
document.addEventListener('click', e => {
    const item = e.target.closest('.hotel-drop-item[data-acc-idx]');
    if (item) {
        e.stopPropagation();
        const accIdx = parseInt(item.dataset.accIdx);
        try {
            const h = JSON.parse(decodeURIComponent(item.dataset.hotel));
            const div = document.querySelector(`.pkg-accommodation[data-idx="${accIdx}"]`);
            const nameInp  = div?.querySelector('.pkg-acc-hotel-name');
            const sysIdInp = div?.querySelector('.pkg-acc-hotel-sysid');
            if (nameInp)  nameInp.value  = h.name    || '';
            if (sysIdInp) sysIdInp.value = h.sys_id  || h.id || '';
            document.getElementById(`pkgAccHotelDrop${accIdx}`)?.classList.add('hidden');
            syncPackageData();
        } catch(err) { console.error('Pkg hotel select error:', err); }
        return;
    }
    if (!e.target.closest('.relative'))
        document.querySelectorAll('.pkg-acc-hotel-drop').forEach(d => d.classList.add('hidden'));
});

/* ── Package New Hotel Modal (pkg) ── */
let _pkgAccIdx = null;

function openPkgNewHotelModal(accIdx) {
    _pkgAccIdx = accIdx;
    const div       = document.querySelector(`.pkg-accommodation[data-idx="${accIdx}"]`);
    const citySel   = div?.querySelector('.pkg-acc-city');
    const cityOpt   = citySel?.options[citySel.selectedIndex];
    const cityName  = cityOpt?.dataset?.name  || cityOpt?.text  || '';
    const countryId = cityOpt?.dataset?.country || '';
    const countryName = allCountries.find(c => c.sys_id === countryId)?.name ?? '';

    const ctx    = document.getElementById('newHotelContext');
    const ctxTxt = document.getElementById('newHotelContextText');
    if (cityName || countryName) {
        ctxTxt.textContent = [cityName, countryName].filter(Boolean).join(', ');
        ctx.classList.remove('hidden');
    } else { ctx.classList.add('hidden'); }

    ['nhName','nhAddress','nhPhone','nhEmail'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('nhStar').value = '';
    document.getElementById('newHotelModal').classList.remove('hidden');
    setTimeout(() => document.getElementById('nhName').focus(), 100);
}

/* ── syncPackageData ── */
function syncPackageData() {
    const slug = selectedServices.has('tour_package') ? 'tour_package' : 'package';

    // Destinations: one entry per common country, with its selected cities
    const destinations = [];
    document.querySelectorAll('[data-country-dest]').forEach(div => {
        const countryId   = div.dataset.countryDest;
        const countryObj  = allCountries.find(c => c.sys_id === countryId);
        const checkedCities = [...div.querySelectorAll('.pkg-city-cb:checked')];
        destinations.push({
            country_id:    countryId,
            country_name:  countryObj?.name ?? '',
            city_ids:      checkedCities.map(cb => cb.value),
            city_names:    checkedCities.map(cb => cb.dataset.name ?? ''),
        });
    });
    setAns(slug, 'destinations', destinations);

    // Accommodations — multiple rows per city card
    const accommodations = [];
    document.querySelectorAll('.pkg-acc-card').forEach(card => {
        const cityId      = card.dataset.cityId   ?? '';
        const cityName    = card.dataset.cityName  ?? '';
        const countryId   = card.dataset.countryId ?? '';
        const countryName = allCountries.find(c => c.sys_id === countryId)?.name ?? '';
        card.querySelectorAll('.pkg-hotel-row').forEach(row => {
            const rowIdx = row.dataset.row;
            accommodations.push({
                country_id:   countryId,
                country_name: countryName,
                city_id:      cityId,
                city_name:    cityName,
                hotel_name:   row.querySelector(`.pkg-acc-hotel-search`)?.value?.trim() || '',
                hotel_sys_id: row.querySelector(`.pkg-acc-hotel-sysid`)?.value || '',
                check_in:     row.querySelector('.pkg-acc-checkin')?.value  || '',
                check_out:    row.querySelector('.pkg-acc-checkout')?.value || '',
            });
        });
    });
    setAns(slug, 'accommodations', accommodations);

    // Basic fields
    const $v = id => document.getElementById(id)?.value ?? '';
    setAns(slug, 'title',                $v('pkgTitle'));
    setAns(slug, 'type',                 $v('pkgType'));
    setAns(slug, 'currency',             $v('pkgCurrency'));
    setAns(slug, 'description',          $v('pkgDesc'));
    setAns(slug, 'special_instruction',  collectSegSpIns('pkgSpIns'));
}

// Legacy stubs — keep so old references don't throw
function packageDestinationHtml() { return ''; }
function packageAccommodationHtml(a={},idx=0) { return pkgAccHtml(a,idx); }
function addPackageDestination()  { /* no-op */ }
function addPackageAccommodation(){ addPkgAcc(); }
function onPkgCountryCbChange()   { /* no-op */ }
function onPkgAccCountryChange()  { /* no-op: country now from city option */ }
/* ══════════════════════════════════════════════════════
   VISA PANEL
══════════════════════════════════════════════════════ */
/* ══════════════════════════════════════════════════════
   VISA PANEL — segment-based
══════════════════════════════════════════════════════ */
function renderVisaPanel(saved) {
    const segments = saved.segments ?? [{}];
    return `<div>
        <div id="visaSegments" class="space-y-4">
            ${segments.map((seg, i) => visaSegmentHtml(seg, i)).join('')}
        </div>
        <button type="button" onclick="addVisaSegment()" class="add-more-btn mt-3">
            <i class="fas fa-plus text-xs"></i>Add Visa Segment
        </button>
    </div>`;
}

function visaSegmentHtml(seg = {}, idx = 0) {
    const countries  = collectCountries();
    const adultCount = parseInt(document.getElementById('paxAdult')?.value ?? 1) || 1;
    const applicants = seg.applicants ?? Array.from({length: adultCount}, (_, i) => ({
        name: '', profession: '', is_main: i === 0
    }));

    return `<div class="visa-segment bg-slate-50 border border-slate-200 rounded-xl p-4 space-y-4" id="visaSeg${idx}" data-idx="${idx}">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wide">
                <i class="fas fa-passport mr-1 text-indigo-400"></i>Visa Segment ${idx+1}
            </span>
            ${idx > 0 ? `<button type="button" onclick="removeVisaSegment(${idx})" class="text-red-400 hover:text-red-600 text-xs"><i class="fas fa-trash"></i></button>` : ''}
        </div>

        <!-- Country -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="f-label">Visa Country <span class="text-red-400">*</span>
                    <span class="opt font-normal">(from common)</span></label>
                <select class="f-input visa-seg-country" data-idx="${idx}" onchange="onVisaSegCountryChange(this, ${idx})">
                    <option value="">Select country…</option>
                    ${countries.map(c =>
                        `<option value="${esc(c.sys_id)}" data-name="${esc(c.name)}"
                            ${(seg.country_sys_id ?? countries[0]?.sys_id ?? '') === c.sys_id ? 'selected' : ''}>
                            ${esc(c.name)}</option>`
                    ).join('')}
                </select>
            </div>
            <div>
                <label class="f-label">Invitation Status</label>
                <div class="flex flex-wrap gap-3 mt-2">
                    ${['No','Organization','Another Person'].map(v => `
                        <label class="flex items-center gap-2 text-sm cursor-pointer select-none">
                            <input type="radio" name="visaInvitation_${idx}" value="${v}" class="text-indigo-600"
                                ${(seg.invitation_status ?? 'No') === v ? 'checked' : ''}
                                onchange="syncAllVisaSegments()">
                            <span class="text-slate-700">${v}</span>
                        </label>`).join('')}
                </div>
            </div>
        </div>

        <!-- Category + Sub Category -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 visa-seg-cat-section" data-idx="${idx}">
            <div>
                <label class="f-label">Visa Category</label>
                <select class="f-input visa-seg-category" data-idx="${idx}" onchange="onVisaSegCategoryChange(this, ${idx})">
                    <option value="">Select country first…</option>
                </select>
            </div>
            <div class="visa-seg-subcat-wrap" data-idx="${idx}" style="display:none">
                <label class="f-label">Sub Category</label>
                <select class="f-input visa-seg-subcategory" data-idx="${idx}" onchange="onVisaSegSubCatChange(this, ${idx})">
                    <option value="">Select…</option>
                </select>
            </div>
        </div>

        <!-- Applicants -->
        <div class="border-t border-slate-100 pt-3">
            <div class="flex items-center justify-between mb-2">
                <label class="f-label mb-0"><i class="fas fa-users mr-1 text-indigo-400"></i>Applicants</label>
                <button type="button" onclick="syncVisaSegPaxRows(${idx})"
                    class="flex items-center gap-1 px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-500 rounded-lg text-xs font-semibold transition">
                    <i class="fas fa-rotate-right text-[10px]"></i> Sync PAX
                </button>
            </div>
            <div class="visa-seg-applicants space-y-2" data-idx="${idx}">
                ${applicants.map((a, i) => visaApplicantHtml(idx, a, i)).join('')}
            </div>
        </div>

        <!-- Cost Bearer -->
        <div class="border-t border-slate-100 pt-3">
            <label class="f-label mb-2 block"><i class="fas fa-money-bill-wave mr-1 text-green-500"></i>Cost borne by</label>
            <div class="flex flex-wrap gap-4 mb-2">
                ${['self','organization','another_person'].map((v, i) => `
                    <label class="flex items-center gap-2 text-sm cursor-pointer select-none">
                        <input type="checkbox" class="visa-cost-cb w-4 h-4 text-indigo-600 rounded"
                            data-idx="${idx}" value="${v}"
                            ${(seg.cost_bearer ?? ['self']).includes(v) ? 'checked' : ''}
                            onchange="syncAllVisaSegments()">
                        <span class="text-slate-700">${['Self','Organization','Another Person'][i]}</span>
                    </label>`).join('')}
            </div>
            <div class="visa-self-bearer-wrap text-xs" data-idx="${idx}" ${!(seg.cost_bearer ?? ['self']).includes('self') ? 'style="display:none"' : ''}>
                <label class="f-label text-xs mb-1">Self bearer(s):</label>
                <div class="visa-self-bearer-list flex flex-wrap gap-2" data-idx="${idx}"></div>
            </div>
        </div>

        <!-- Special Instruction -->
        <div class="border-t border-slate-100 pt-3">
            <label class="f-label text-amber-600"><i class="fas fa-triangle-exclamation mr-1"></i>Special Instructions</label>
            <div id="visaSpIns${idx}" class="space-y-1.5 mb-1.5"></div>
            <button type="button" onclick="addSegSpInsRow('visaSpIns${idx}');syncAllVisaSegments()"
                class="add-more-btn"><i class="fas fa-plus text-xs"></i>Add</button>
        </div>
    </div>`;
}

function visaApplicantHtml(segIdx, a = {}, appIdx = 0) {
    return `<div class="visa-applicant bg-white border border-slate-200 rounded-lg p-3 grid grid-cols-1 sm:grid-cols-3 gap-3 items-center"
                data-seg="${segIdx}" data-app="${appIdx}">
        <div>
            <label class="f-label text-xs">Name <span class="opt">(optional)</span></label>
            <input type="text" class="f-input visa-app-name" data-seg="${segIdx}" data-app="${appIdx}"
                placeholder="e.g. Rahim Ahmed" value="${esc(a.name ?? '')}"
                oninput="syncAllVisaSegments();updateVisaSelfBearerList(${segIdx})">
        </div>
        <div>
            <label class="f-label text-xs">Profession</label>
            <input type="text" class="f-input visa-app-prof" data-seg="${segIdx}" data-app="${appIdx}"
                placeholder="e.g. Engineer" value="${esc(a.profession ?? '')}"
                oninput="syncAllVisaSegments()">
        </div>
        <div class="flex items-center gap-4">
            <label class="flex items-center gap-1.5 text-xs cursor-pointer select-none">
                <input type="radio" name="visaMain_${segIdx}" value="${appIdx}" class="text-indigo-600"
                    ${(a.is_main ?? appIdx === 0) ? 'checked' : ''}
                    onchange="syncAllVisaSegments()">
                <span class="text-slate-600 font-medium">Main</span>
            </label>
        </div>
    </div>`;
}

function addVisaSegment() {
    const c   = document.getElementById('visaSegments');
    const idx = c.children.length;
    const tmp = document.createElement('div');
    tmp.innerHTML = visaSegmentHtml({}, idx);
    c.appendChild(tmp.firstElementChild);
    // auto-select first common country
    const sel = document.querySelector(`.visa-seg-country[data-idx="${idx}"]`);
    if (sel?.options.length > 1) { sel.selectedIndex = 1; onVisaSegCountryChange(sel, idx); }
}

function removeVisaSegment(idx) {
    document.getElementById(`visaSeg${idx}`)?.remove();
    removeNotice(`visa_req_${idx}`);
    syncAllVisaSegments();
}

// Per-segment visa category maps
const _visaSegCats = {};

async function onVisaSegCountryChange(sel, idx) {
    const countryId   = sel.value;
    const countryName = sel.options[sel.selectedIndex]?.dataset.name ?? '';
    const catSel      = document.querySelector(`.visa-seg-category[data-idx="${idx}"]`);
    const subWrap     = document.querySelector(`.visa-seg-subcat-wrap[data-idx="${idx}"]`);
    if (!catSel) return;
    catSel.innerHTML = '<option value="">Loading…</option>';
    if (subWrap) subWrap.style.display = 'none';
    removeNotice(`visa_req_${idx}`);
    if (!countryId) { catSel.innerHTML = '<option value="">Select country first…</option>'; return; }
    try {
        const res  = await fetch(`${VISA_API}?action=get&country=${encodeURIComponent(countryId)}`);
        const json = await res.json();
        const cats = json.data?.categories ?? [];
        _visaSegCats[idx] = cats;
        if (!cats.length) { catSel.innerHTML = '<option value="">No visa data for this country</option>'; return; }
        catSel.innerHTML = `<option value="">Select category…</option>` +
            cats.map((c, i) => `<option value="${i}">${esc(c.name)}</option>`).join('');
    } catch { catSel.innerHTML = '<option value="">Failed to load</option>'; }
    syncAllVisaSegments();
}

function onVisaSegCategoryChange(sel, idx) {
    const catIdx  = parseInt(sel.value);
    const cat     = _visaSegCats[idx]?.[catIdx];
    const subWrap = document.querySelector(`.visa-seg-subcat-wrap[data-idx="${idx}"]`);
    const subSel  = document.querySelector(`.visa-seg-subcategory[data-idx="${idx}"]`);
    removeNotice(`visa_req_${idx}`);
    if (!cat) { if (subWrap) subWrap.style.display = 'none'; return; }
    const subs = cat.sub_categories ?? [];
    if (subs.length) {
        subSel.innerHTML = `<option value="">Select sub-category…</option>` +
            subs.map((s, i) => `<option value="${i}">${esc(s.name)}</option>`).join('');
        if (subWrap) subWrap.style.display = '';
        // show category-level info if no sub selected yet
        showVisaNotice(idx, cat);
    } else {
        if (subWrap) subWrap.style.display = 'none';
        showVisaNotice(idx, cat);
    }
    syncAllVisaSegments();
}

function onVisaSegSubCatChange(sel, idx) {
    const catIdx = parseInt(document.querySelector(`.visa-seg-category[data-idx="${idx}"]`)?.value ?? 0);
    const subIdx = parseInt(sel.value);
    const cat    = _visaSegCats[idx]?.[catIdx];
    const sub    = cat?.sub_categories?.[subIdx];
    if (sub) showVisaNotice(idx, sub);
    syncAllVisaSegments();
}

function showVisaNotice(idx, item) {
    if (!item) { removeNotice(`visa_req_${idx}`); return; }
    const parts = [];
    if (item.instruction) parts.push(`<div class="leading-relaxed mb-1">${item.instruction}</div>`);
    if (item.document_list?.length) {
        parts.push(`<p class="font-bold mt-1 mb-0.5"><i class="fas fa-file-alt mr-1 text-indigo-400"></i>Documents:</p>`);
        parts.push(`<ul class="list-disc list-inside space-y-0.5">${item.document_list.map(d => `<li>${esc(d)}</li>`).join('')}</ul>`);
    }
    if (item.requirements?.length) {
        parts.push(`<p class="font-bold mt-1 mb-0.5"><i class="fas fa-check-circle mr-1 text-green-400"></i>Requirements:</p>`);
        parts.push(`<ul class="list-disc list-inside space-y-0.5">${item.requirements.map(r => `<li>${esc(r)}</li>`).join('')}</ul>`);
    }
    if (parts.length) addNotice(`visa_req_${idx}`, 'requirement', `Visa Requirements (Segment ${idx+1})`, parts.join(''));
}

function syncVisaSegPaxRows(idx) {
    const adultCount  = parseInt(document.getElementById('paxAdult')?.value ?? 1) || 1;
    const container   = document.querySelector(`.visa-seg-applicants[data-idx="${idx}"]`);
    if (!container) return;
    const existing    = container.querySelectorAll('.visa-applicant').length;
    if (existing < adultCount) {
        for (let i = existing; i < adultCount; i++) {
            const tmp = document.createElement('div');
            tmp.innerHTML = visaApplicantHtml(idx, {}, i);
            container.appendChild(tmp.firstElementChild);
        }
    } else {
        const rows = container.querySelectorAll('.visa-applicant');
        for (let i = adultCount; i < existing; i++) rows[i]?.remove();
    }
    updateVisaSelfBearerList(idx);
    syncAllVisaSegments();
}

function updateVisaSelfBearerList(segIdx) {
    const listEl = document.querySelector(`.visa-self-bearer-list[data-idx="${segIdx}"]`);
    if (!listEl) return;
    const saved  = serviceAnswers.visa?.segments?.[segIdx]?.self_bearers ?? [];
    const names  = [...document.querySelectorAll(`.visa-app-name[data-seg="${segIdx}"]`)];
    listEl.innerHTML = names.map((inp, i) => {
        const label = inp.value.trim() || `Applicant ${i+1}`;
        return `<label class="flex items-center gap-1.5 text-xs cursor-pointer select-none bg-white border border-slate-200 rounded-lg px-2.5 py-1.5">
            <input type="checkbox" class="visa-self-bearer w-3.5 h-3.5 text-indigo-600 rounded"
                data-seg="${segIdx}" value="${i}"
                ${saved.includes(i) ? 'checked' : ''} onchange="syncAllVisaSegments()">
            <span class="text-slate-700">${esc(label)}</span>
        </label>`;
    }).join('');
}

function syncAllVisaSegments() {
    const segments = [];
    document.querySelectorAll('.visa-segment').forEach((seg, i) => {
        const idx        = parseInt(seg.dataset.idx ?? i);
        const countrySel = seg.querySelector('.visa-seg-country');
        const catSel     = seg.querySelector('.visa-seg-category');
        const subSel     = seg.querySelector('.visa-seg-subcategory');
        const countryOpt = countrySel?.options[countrySel.selectedIndex];
        const catIdx     = parseInt(catSel?.value ?? -1);
        const subIdx     = parseInt(subSel?.value ?? -1);
        const cat        = _visaSegCats[idx]?.[catIdx];
        const sub        = cat?.sub_categories?.[subIdx];
        const invRadio   = seg.querySelector(`input[name="visaInvitation_${idx}"]:checked`);
        const applicants = [...seg.querySelectorAll('.visa-applicant')].map((row, ai) => ({
            name:       row.querySelector('.visa-app-name')?.value?.trim() ?? '',
            profession: row.querySelector('.visa-app-prof')?.value?.trim() ?? '',
            is_main:    row.querySelector(`input[name="visaMain_${idx}"][value="${ai}"]`)?.checked ?? (ai === 0),
        }));
        const costCbs    = [...seg.querySelectorAll('.visa-cost-cb:checked')].map(cb => cb.value);
        const selfBearers= [...seg.querySelectorAll(`.visa-self-bearer[data-seg="${idx}"]:checked`)].map(cb => parseInt(cb.value));

        // Toggle self-bearer panel
        const selfWrap = seg.querySelector('.visa-self-bearer-wrap');
        if (selfWrap) selfWrap.style.display = costCbs.includes('self') ? '' : 'none';
        if (costCbs.includes('self')) updateVisaSelfBearerList(idx);

        // Special instructions
        const spIns = collectSegSpIns(`visaSpIns${idx}`);

        segments.push({
            country_sys_id:    countrySel?.value ?? '',
            country_name:      countryOpt?.dataset?.name ?? '',
            category_idx:      catIdx >= 0 ? catIdx : null,
            category_name:     cat?.name ?? '',
            sub_cat_idx:       subIdx >= 0 ? subIdx : null,
            sub_category:      sub?.name ?? '',
            invitation_status: invRadio?.value ?? 'No',
            applicants,
            cost_bearer:       costCbs,
            self_bearers:      selfBearers,
            special_instruction: spIns,
        });
    });
    setAns('visa', 'segments', segments);
}

// Legacy stubs
function syncVisaData() { syncAllVisaSegments(); }
function updateSelfBearerList() {}
function syncVisaPaxRows() { document.querySelectorAll('.visa-segment').forEach(s => syncVisaSegPaxRows(parseInt(s.dataset.idx ?? 0))); }
function onVisaCountryChange() {}
function onVisaCategoryChange() {}
function onVisaSubCatChange() {}
function showVisaInfo() {}


/* ══════════════════════════════════════════════════════
   UMRAH PANEL
══════════════════════════════════════════════════════ */
function renderUmrahPanel(saved) {
    const totalNights  = saved.total_nights  ?? '';
    const makkahNights = saved.makkah_nights ?? '';
    const madinaNights = saved.madina_nights ?? '';

    return `<div class="space-y-5">

        <!-- Umrah Type -->
        <div>
            <label class="f-label mb-2 block"><i class="fas fa-kaaba mr-1 text-indigo-400"></i>Umrah Type</label>
            <div class="flex gap-5">
                ${[['umrah_visa','Umrah Visa'],['no_visa','Visa Not Required']].map(([v,l]) => `
                    <label class="flex items-center gap-2 text-sm cursor-pointer select-none">
                        <input type="radio" name="umrahType" value="${v}" class="text-indigo-600"
                            ${(saved.umrah_type ?? 'umrah_visa') === v ? 'checked' : ''}
                            onchange="setAns('umrah','umrah_type',this.value)">
                        <span class="text-slate-700 font-medium">${l}</span>
                    </label>`).join('')}
            </div>
        </div>

        <!-- Package Type -->
        <div class="border-t border-slate-100 pt-4">
            <label class="f-label mb-2 block"><i class="fas fa-box-open mr-1 text-indigo-400"></i>Package Type</label>
            <div class="flex flex-wrap gap-5">
                ${[['flight','Flight Package'],['land','Land Package'],['group','Group Package']].map(([v,l]) => `
                    <label class="flex items-center gap-2 text-sm cursor-pointer select-none">
                        <input type="radio" name="umrahPkgType" value="${v}" class="text-indigo-600"
                            ${(saved.package_type ?? 'flight') === v ? 'checked' : ''}
                            onchange="setAns('umrah','package_type',this.value)">
                        <span class="text-slate-700 font-medium">${l}</span>
                    </label>`).join('')}
            </div>
        </div>

        <!-- Flight Date Type + Departure Date -->
        <div class="border-t border-slate-100 pt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="f-label mb-2 block"><i class="fas fa-plane mr-1 text-indigo-400"></i>Flight Date Type</label>
                <div class="flex gap-5">
                    ${[['fixed','Fixed Flight'],['flexible','Flexible Flight']].map(([v,l]) => `
                        <label class="flex items-center gap-2 text-sm cursor-pointer select-none">
                            <input type="radio" name="umrahFlightType" value="${v}" class="text-indigo-600"
                                ${(saved.flight_date_type ?? 'fixed') === v ? 'checked' : ''}
                                onchange="setAns('umrah','flight_date_type',this.value)">
                            <span class="text-slate-700 font-medium">${l}</span>
                        </label>`).join('')}
                </div>
            </div>
            <div>
                <label class="f-label">Preferred Departure Date</label>
                <input type="date" id="umrahDeparture" class="f-input"
                    value="${esc(saved.departure_date ?? '')}"
                    onchange="setAns('umrah','departure_date',this.value)">
            </div>
        </div>

        <!-- Duration -->
        <div class="border-t border-slate-100 pt-4">
            <label class="f-label mb-3 block"><i class="fas fa-moon mr-1 text-indigo-400"></i>Duration (Nights)</label>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="f-label text-xs text-indigo-600 font-bold">Total Nights</label>
                    <input type="number" id="umrahTotal" class="f-input" min="0" placeholder="e.g. 14"
                        value="${esc(totalNights)}"
                        oninput="onUmrahTotalChange(this)">
                </div>
                <div>
                    <label class="f-label text-xs text-amber-600 font-bold">Makkah Nights</label>
                    <input type="number" id="umrahMakkah" class="f-input" min="0" placeholder="auto 60%"
                        value="${esc(makkahNights)}"
                        oninput="onUmrahMadMakChange()">
                </div>
                <div>
                    <label class="f-label text-xs text-green-600 font-bold">Madina Nights</label>
                    <input type="number" id="umrahMadina" class="f-input" min="0" placeholder="auto 40%"
                        value="${esc(madinaNights)}"
                        oninput="onUmrahMadMakChange()">
                </div>
            </div>
            <p id="umrahNightHint" class="text-[11px] text-slate-400 mt-2 hidden"></p>
        </div>

        <!-- Has Transport -->
        <div class="border-t border-slate-100 pt-4">
            <label class="f-label mb-2 block"><i class="fas fa-bus mr-1 text-indigo-400"></i>Transport Included?</label>
            <div class="flex gap-5">
                ${[['yes','Yes'],['no','No']].map(([v,l]) => `
                    <label class="flex items-center gap-2 text-sm cursor-pointer select-none">
                        <input type="radio" name="umrahTransport" value="${v}" class="text-indigo-600"
                            ${(saved.has_transport ?? 'no') === v ? 'checked' : ''}
                            onchange="setAns('umrah','has_transport',this.value)">
                        <span class="text-slate-700 font-medium">${l}</span>
                    </label>`).join('')}
            </div>
        </div>

        <!-- Description -->
        <div class="border-t border-slate-100 pt-4">
            <label class="f-label"><i class="fas fa-align-left mr-1 text-indigo-400"></i>Description <span class="opt">(optional)</span></label>
            <textarea id="umrahDesc" class="f-input" rows="3"
                placeholder="Inclusions, exclusions, special notes…"
                oninput="setAns('umrah','description',this.value)">${esc(saved.description ?? '')}</textarea>
        </div>

        <!-- Special Instruction -->
        <div class="border-t border-slate-100 pt-4">
            <label class="f-label text-amber-600"><i class="fas fa-triangle-exclamation mr-1"></i>Special Instructions</label>
            <div id="umrahSpIns" class="space-y-1.5 mb-1.5">
                ${(saved.special_instruction ?? []).map(v =>
                    `<div class="multi-row"><input type="text" class="f-input seg-spins-input" value="${esc(v)}" placeholder="e.g. Wheelchair access required…">
                    <button type="button" onclick="this.parentElement.remove();syncUmrahData()" class="del-btn"><i class="fas fa-times text-xs"></i></button></div>`
                ).join('')}
            </div>
            <button type="button" onclick="addSegSpInsRow('umrahSpIns');syncUmrahData()"
                class="add-more-btn"><i class="fas fa-plus text-xs"></i>Add</button>
        </div>
    </div>`;
}

function onUmrahTotalChange(inp) {
    const total = parseInt(inp.value) || 0;
    setAns('umrah', 'total_nights', total);
    if (total > 0) {
        const makkah = Math.round(total * 0.6);
        const madina = total - makkah;
        const mEl = document.getElementById('umrahMakkah');
        const dEl = document.getElementById('umrahMadina');
        if (mEl && !mEl.value) mEl.value = makkah;
        if (dEl && !dEl.value) dEl.value = madina;
        const hint = document.getElementById('umrahNightHint');
        if (hint) {
            hint.textContent = `Suggested: ${makkah} Makkah + ${madina} Madina (60/40 split)`;
            hint.classList.remove('hidden');
        }
        setAns('umrah', 'makkah_nights', parseInt(mEl?.value) || makkah);
        setAns('umrah', 'madina_nights', parseInt(dEl?.value) || madina);
    }
}

function onUmrahMadMakChange() {
    const makkah = parseInt(document.getElementById('umrahMakkah')?.value) || 0;
    const madina = parseInt(document.getElementById('umrahMadina')?.value) || 0;
    const total  = makkah + madina;
    const tEl    = document.getElementById('umrahTotal');
    if (tEl && total > 0) tEl.value = total;
    setAns('umrah', 'makkah_nights', makkah);
    setAns('umrah', 'madina_nights', madina);
    setAns('umrah', 'total_nights',  total);
    const hint = document.getElementById('umrahNightHint');
    if (hint && total > 0) {
        hint.textContent = `Total: ${total} nights`;
        hint.classList.remove('hidden');
    }
}

function syncUmrahData() {
    setAns('umrah', 'description',         document.getElementById('umrahDesc')?.value?.trim() ?? '');
    setAns('umrah', 'special_instruction', collectSegSpIns('umrahSpIns'));
}

/* ══════════════════════════════════════════════════════
   TRANSPORT PANEL
══════════════════════════════════════════════════════ */
function renderTransportPanel(saved) {
    const segments = saved.segments ?? [{}];
    return `<div>
        <div id="trSegments" class="space-y-4">
            ${segments.map((seg, i) => trSegmentHtml(seg, i)).join('')}
        </div>
        <button type="button" onclick="addTrSegment()" class="add-more-btn mt-3">
            <i class="fas fa-plus text-xs"></i>Add Segment
        </button>
    </div>`;
}

function trSegmentHtml(seg = {}, idx = 0) {
    const spInsId = `trSpIns${idx}`;
    const spIns   = seg.special_instruction ?? [];
    const spHtml  = spIns.map(v =>
        `<div class="multi-row"><input type="text" class="f-input seg-spins-input" value="${esc(v)}" placeholder="e.g. AC required…">
        <button type="button" onclick="this.parentElement.remove();syncAllTrSegments()" class="del-btn"><i class="fas fa-times text-xs"></i></button></div>`
    ).join('');

    const trTypes = ['Car','Microbus','Bus','Train','Ferry','CNG','Rickshaw','Boat','Other'];

    return `<div class="tr-segment bg-slate-50 border border-slate-200 rounded-xl p-4" id="trSeg${idx}" data-idx="${idx}">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wide">
                <i class="fas fa-route mr-1 text-indigo-400"></i>Segment ${idx+1}
            </span>
            ${idx > 0 ? `<button type="button" onclick="removeTrSegment(${idx})" class="text-red-400 hover:text-red-600 text-xs"><i class="fas fa-trash"></i></button>` : ''}
        </div>

        <!-- Type + Route -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-3">
            <div>
                <label class="f-label">Transport Type</label>
                <select class="f-input tr-type" data-idx="${idx}" onchange="syncAllTrSegments()">
                    <option value="">Select…</option>
                    ${trTypes.map(t => `<option value="${t}" ${(seg.type??'')===t?'selected':''}>${t}</option>`).join('')}
                </select>
            </div>
            <div>
                <label class="f-label">Route</label>
                <input type="text" class="f-input tr-route" data-idx="${idx}"
                    placeholder="DAC - CTG" value="${esc(seg.route ?? '')}"
                    oninput="onTrRouteInput(this,${idx})"
                    onblur="onTrRouteBlur(this,${idx})"
                    style="text-transform:uppercase;">
            </div>
            <div>
                <label class="f-label">From</label>
                <input type="text" class="f-input tr-from" data-idx="${idx}"
                    placeholder="DAC" value="${esc(seg.from ?? '')}" maxlength="5"
                    oninput="onTrFromTo(${idx})" style="text-transform:uppercase;">
            </div>
            <div>
                <label class="f-label">To</label>
                <input type="text" class="f-input tr-to" data-idx="${idx}"
                    placeholder="CTG" value="${esc(seg.to ?? '')}" maxlength="5"
                    oninput="onTrFromTo(${idx})" style="text-transform:uppercase;">
            </div>
        </div>

        <!-- DateTime + Luggage -->
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-3">
            <div>
                <label class="f-label">Start Date & Time <span class="opt">(opt)</span></label>
                <input type="datetime-local" class="f-input tr-start" data-idx="${idx}"
                    value="${esc(seg.start_datetime ?? '')}" onchange="syncAllTrSegments()">
            </div>
            <div>
                <label class="f-label">End Date & Time <span class="opt">(opt)</span></label>
                <input type="datetime-local" class="f-input tr-end" data-idx="${idx}"
                    value="${esc(seg.end_datetime ?? '')}" onchange="syncAllTrSegments()">
            </div>
            <div>
                <label class="f-label">Luggage <span class="opt">(opt)</span></label>
                <div class="flex gap-1">
                    <input type="number" class="f-input tr-lug-val" data-idx="${idx}"
                        min="0" placeholder="0" value="${esc(seg.luggage?.value??'')}"
                        style="width:60px;flex-shrink:0" oninput="syncAllTrSegments()">
                    <select class="f-input tr-lug-unit" data-idx="${idx}" onchange="syncAllTrSegments()">
                        <option value="Pieces" ${(seg.luggage?.unit??'')==='Pieces'?'selected':''}>Pcs</option>
                        <option value="Kg"     ${(seg.luggage?.unit??'')==='Kg'    ?'selected':''}>Kg</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Special Instructions -->
        <div class="pt-3 border-t border-slate-200">
            <label class="f-label text-amber-600"><i class="fas fa-triangle-exclamation mr-1"></i>Special Instructions</label>
            <div id="${spInsId}" class="space-y-1.5 mb-1.5">${spHtml}</div>
            <button type="button" onclick="addSegSpInsRow('${spInsId}');syncAllTrSegments()"
                class="add-more-btn"><i class="fas fa-plus text-xs"></i>Add</button>
        </div>
    </div>`;
}

function onTrRouteInput(inp, idx) {
    const val   = inp.value.toUpperCase();
    inp.value   = val;
    const match = val.match(/^([A-Z]{2,5})\s*[-–]\s*([A-Z]{2,5})$/);
    if (match) {
        const seg = document.getElementById(`trSeg${idx}`);
        const fEl = seg?.querySelector('.tr-from');
        const tEl = seg?.querySelector('.tr-to');
        if (fEl) fEl.value = match[1];
        if (tEl) tEl.value = match[2];
    }
    syncAllTrSegments();
}

function onTrRouteBlur(inp, idx) {
    const val   = inp.value.trim().toUpperCase();
    const match = val.match(/^([A-Z]{2,5})\s*[-–]?\s*([A-Z]{2,5})$/);
    if (match && !val.includes('-')) inp.value = match[1] + ' - ' + match[2];
    syncAllTrSegments();
}

function onTrFromTo(idx) {
    const seg  = document.getElementById(`trSeg${idx}`);
    const from = (seg?.querySelector('.tr-from')?.value ?? '').toUpperCase().trim();
    const to   = (seg?.querySelector('.tr-to')?.value   ?? '').toUpperCase().trim();
    const rEl  = seg?.querySelector('.tr-route');
    if (from && to && rEl) rEl.value = from + ' - ' + to;
    else if (from && rEl)  rEl.value = from;
    if (seg?.querySelector('.tr-from')) seg.querySelector('.tr-from').value = from;
    if (seg?.querySelector('.tr-to'))   seg.querySelector('.tr-to').value   = to;
    syncAllTrSegments();
}

function addTrSegment() {
    const c   = document.getElementById('trSegments');
    const idx = c.children.length;
    const tmp = document.createElement('div');
    tmp.innerHTML = trSegmentHtml({}, idx);
    c.appendChild(tmp.firstElementChild);
}

function removeTrSegment(idx) {
    document.getElementById(`trSeg${idx}`)?.remove();
    syncAllTrSegments();
}

function syncAllTrSegments() {
    const segments = [];
    document.querySelectorAll('.tr-segment').forEach(seg => {
        const spInsId = `trSpIns${seg.dataset.idx}`;
        segments.push({
            type:             seg.querySelector('.tr-type')?.value  ?? '',
            route:            seg.querySelector('.tr-route')?.value?.trim() ?? '',
            from:             seg.querySelector('.tr-from')?.value?.trim()?.toUpperCase() ?? '',
            to:               seg.querySelector('.tr-to')?.value?.trim()?.toUpperCase()   ?? '',
            start_datetime:   seg.querySelector('.tr-start')?.value ?? '',
            end_datetime:     seg.querySelector('.tr-end')?.value   ?? '',
            luggage: {
                value: seg.querySelector('.tr-lug-val')?.value?.trim() ?? '',
                unit:  seg.querySelector('.tr-lug-unit')?.value ?? 'Pieces',
            },
            special_instruction: collectSegSpIns(spInsId),
        });
    });
    setAns('transport', 'segments', segments);
}

/* ── Generic/Dynamic fields ── */
function renderGenericPanel(slug, saved) {
    return `<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="sm:col-span-2">
            <label class="f-label">Details / Notes</label>
            <textarea class="f-input" rows="3" placeholder="Any specific details…"
                oninput="setAns('${slug}','details',this.value)">${esc(saved.details??'')}</textarea>
        </div>
    </div>`;
}

function renderDynamicFields(slug, fields, saved) {
    return `<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">` + fields.map(f=>{
        const val=saved[f.id]??'';
        let inp='';
        if(f.type==='text'||f.type==='input')  inp=`<input type="text" class="f-input" placeholder="${f.placeholder??''}" value="${esc(val)}" oninput="setAns('${slug}','${f.id}',this.value)">`;
        else if(f.type==='number') inp=`<input type="number" class="f-input" min="0" value="${esc(val||0)}" oninput="setAns('${slug}','${f.id}',this.value)">`;
        else if(f.type==='date')   inp=`<input type="date" class="f-input" value="${esc(val)}" onchange="setAns('${slug}','${f.id}',this.value)">`;
        else if(f.type==='select') inp=`<select class="f-input" onchange="setAns('${slug}','${f.id}',this.value)"><option value="">Select…</option>${(f.options??[]).map(o=>`<option value="${o}" ${val===o?'selected':''}>${o}</option>`).join('')}</select>`;
        else if(f.type==='textarea') inp=`<textarea class="f-input" rows="2" oninput="setAns('${slug}','${f.id}',this.value)">${esc(val)}</textarea>`;
        return `<div class="${f.span===2?'sm:col-span-2':''}"><label class="f-label">${f.label}${f.required?'<span class="text-red-400 ml-0.5">*</span>':''}</label>${inp}</div>`;
    }).join('')+`</div>`;
}

function switchSvcTab(slug,btn) {
    document.querySelectorAll('.svc-detail-panel').forEach(p=>p.classList.add('hidden'));
    document.querySelectorAll('#svcTabsNav .svc-tab').forEach(b=>b.classList.remove('active'));
    document.getElementById('panel_'+slug).classList.remove('hidden');
    btn.classList.add('active');
}

function setAns(slug,field,value) {
    if(!serviceAnswers[slug]) serviceAnswers[slug]={};
    serviceAnswers[slug][field]=value;
}

/* ═══════════════════════════════════════════════
   NOTICES SYSTEM
═══════════════════════════════════════════════ */
// notices: { id, type:'info'|'warning'|'requirement', title, body }
const _notices = {};

function addNotice(id, type, title, body) {
    _notices[id] = { type, title, body };
    renderNotices();
}

function removeNotice(id) {
    delete _notices[id];
    renderNotices();
}

function clearNotices() {
    Object.keys(_notices).forEach(k => delete _notices[k]);
    renderNotices();
}

function renderNotices() {
    const panel = document.getElementById('noticesPanel');
    const list  = document.getElementById('noticesList');
    if (!panel || !list) return;
    const entries = Object.entries(_notices);
    if (!entries.length) { panel.classList.add('hidden'); return; }
    panel.classList.remove('hidden');

    const colors = {
        info:        { bg: 'bg-blue-50',   border: 'border-blue-200',   icon: 'fa-circle-info text-blue-500',    text: 'text-blue-800' },
        warning:     { bg: 'bg-red-50',    border: 'border-red-200',    icon: 'fa-triangle-exclamation text-red-500', text: 'text-red-800' },
        requirement: { bg: 'bg-indigo-50', border: 'border-indigo-200', icon: 'fa-list-check text-indigo-500',   text: 'text-indigo-800' },
    };

    list.innerHTML = entries.map(([id, n]) => {
        const c = colors[n.type] ?? colors.info;
        return `<div class="${c.bg} border ${c.border} rounded-lg p-3">
            <div class="flex items-start gap-2">
                <i class="fas ${c.icon} text-xs mt-0.5 flex-shrink-0"></i>
                <div class="flex-1 min-w-0">
                    ${n.title ? `<p class="text-xs font-bold ${c.text} mb-1">${esc(n.title)}</p>` : ''}
                    <div class="text-xs ${c.text} leading-relaxed notice-body">${n.body}</div>
                </div>
                <button onclick="removeNotice('${id}')"
                    class="flex-shrink-0 w-4 h-4 flex items-center justify-center rounded text-slate-300 hover:text-slate-500 transition">
                    <i class="fas fa-times" style="font-size:8px"></i>
                </button>
            </div>
        </div>`;
    }).join('');
}

/* ═══════════════════════════════════════════════
   STT
═══════════════════════════════════════════════ */
let _sttRec=null,_sttFinal='',_sttRecording=false,_sttPaused=false;
function sttToggle(){const p=document.getElementById('sttPanel');const h=p.classList.contains('hidden');p.classList.toggle('hidden',!h);document.getElementById('sttToggle').classList.toggle('active',h);}
function sttStart(){if(!('webkitSpeechRecognition'in window||'SpeechRecognition'in window)){showToast('error','Use Chrome for voice.');return;}if(!_sttRec){const SR=window.SpeechRecognition||window.webkitSpeechRecognition;_sttRec=new SR();_sttRec.continuous=true;_sttRec.interimResults=true;_sttRec.onresult=e=>{let t='';for(let i=e.resultIndex;i<e.results.length;i++){if(e.results[i].isFinal)_sttFinal+=e.results[i][0].transcript+' ';else t+=e.results[i][0].transcript;}const p=document.getElementById('sttPreview');if(p)p.innerText=_sttFinal+t;};_sttRec.onerror=e=>{if(e.error!=='aborted')sttSetStatus('stopped','Error: '+e.error);};_sttRec.onend=()=>{if(_sttRecording&&!_sttPaused){_sttRec.lang=document.getElementById('sttLang').value||'bn-BD';_sttRec.start();}};}_sttFinal='';_sttRecording=true;_sttPaused=false;_sttRec.lang=document.getElementById('sttLang').value||'bn-BD';_sttRec.start();const p=document.getElementById('sttPreview');if(p)p.innerText='';document.getElementById('sttActions')?.classList.add('hidden');document.getElementById('sttStart')?.setAttribute('disabled',true);document.getElementById('sttPause')?.removeAttribute('disabled');document.getElementById('sttStop')?.removeAttribute('disabled');sttSetStatus('active','Recording…');}
function sttPause(){if(!_sttPaused){_sttPaused=true;_sttRec?.stop();document.getElementById('sttPause').innerHTML='▶ Resume';sttSetStatus('paused','Paused');}else{_sttPaused=false;_sttRec.lang=document.getElementById('sttLang').value||'bn-BD';_sttRec?.start();document.getElementById('sttPause').innerHTML='⏸ Pause';sttSetStatus('active','Recording…');}}
function sttStop(){_sttRecording=false;_sttPaused=false;_sttRec?.stop();document.getElementById('sttStart')?.removeAttribute('disabled');document.getElementById('sttPause')?.setAttribute('disabled',true);document.getElementById('sttStop')?.setAttribute('disabled',true);const pb=document.getElementById('sttPause');if(pb)pb.innerHTML='⏸ Pause';const has=(_sttFinal||'').trim().length>0;document.getElementById('sttActions')?.classList.toggle('hidden',!has);sttSetStatus('stopped',has?'Stopped — push below':'Stopped');}
function sttSetStatus(state,text){const dot=document.getElementById('sttDot'),txt=document.getElementById('sttStatus');if(txt)txt.textContent=text;if(!dot)return;const m={idle:'stt-dot-idle',active:'stt-dot-active',paused:'stt-dot-paused',stopped:'stt-dot-stopped'};dot.className='w-2 h-2 rounded-full flex-shrink-0 '+(m[state]||m.idle);}
async function sttPush(polish){const prev=document.getElementById('sttPreview');const raw=(prev?prev.innerText:_sttFinal).trim();if(!raw){showToast('error','No transcript');return;}if(!polish){const ex=document.getElementById('promptArea').value.trim();document.getElementById('promptArea').value=ex?ex+' '+raw:raw;showToast('success','Pushed ✓');return;}const btn=document.getElementById('sttPolish');if(btn){btn.disabled=true;btn.innerHTML='<i class="fas fa-spinner fa-spin text-xs mr-1"></i>Polishing…';}try{const fd=new FormData();fd.append('raw_text',raw);fd.append('service_type',[...selectedServices][0]??'');const res=await fetch(SPEECH_API,{method:'POST',body:fd});const d=await res.json();if(d.success){const ex=document.getElementById('promptArea').value.trim();document.getElementById('promptArea').value=ex?ex+' '+d.corrected_text:d.corrected_text;showToast('success','Polished & pushed ✓');}else showToast('error',d.error||'Failed');}catch{showToast('error','Network error');}if(btn){btn.disabled=false;btn.innerHTML='<i class="fas fa-wand-magic-sparkles text-xs"></i> Polish & Push';}}
async function refinePrompt(){const text=document.getElementById('promptArea').value.trim();if(!text){showToast('error','Type something first');return;}showLoader('Refining…');try{const fd=new FormData();fd.append('raw_text',text);fd.append('service_type',[...selectedServices][0]??'');const res=await fetch(SPEECH_API,{method:'POST',body:fd});const d=await res.json();if(d.success){document.getElementById('promptArea').value=d.corrected_text||text;showToast('success','Refined ✓');}else showToast('error',d.error||'Failed');}catch{showToast('error','Network error');}hideLoader();}

/* ═══════════════════════════════════════════════
   AI EXTRACT & BUILD
═══════════════════════════════════════════════ */
async function extractAndBuild() {
    const prompt = document.getElementById('promptArea').value.trim();
    if (!prompt) { showToast('error', 'Please enter a prompt first'); return; }

    showLoader('AI extracting via pre-prompter…');
    try {
        // Pass pre-selected service_type for service-aware pre-prompting
        const firstService = [...selectedServices][0] ?? '';
        const res  = await fetch(EXTRACT_API, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({
                prompt,
                service_type: firstService,
                countries: allCountries.map(c => ({ sys_id: c.sys_id, name: c.name })),
                services:  allServices.map(s  => ({ slug: s.slug,    name: s.name  })),
            }),
        });
        const json = await res.json();
        if (json.status !== 'success') throw new Error(json.message ?? 'Extraction failed');

        const d = json.data;

        // ── 1. Client ──────────────────────────────────────────
        if (d.client?.name)  document.getElementById('clientName').value  = d.client.name;
        if (d.client?.phone) document.getElementById('clientPhone').value = d.client.phone;
        if (d.client?.email) document.getElementById('clientEmail').value = d.client.email;

        // Fuzzy client match — search by each word in the extracted name
        if (d.client?.name && clientsData.length) {
            const words = d.client.name.toLowerCase().split(/\s+/).filter(w => w.length > 2);
            const scored = clientsData.map((c, idx) => {
                const cn = (c.name || '').toLowerCase();
                const matchCount = words.filter(w => cn.includes(w)).length;
                return { idx, matchCount };
            }).filter(x => x.matchCount > 0)
              .sort((a, b) => b.matchCount - a.matchCount);

            if (scored.length === 1 || (scored.length > 0 && scored[0].matchCount === words.length)) {
                // Single strong match — auto-select silently
                selectClient(scored[0].idx);
            } else if (scored.length > 1) {
                // Multiple candidates — show them in the dropdown for user to pick
                const inp = document.getElementById('clientInput');
                const drop = document.getElementById('clientDropdown');
                if (inp && drop) {
                    inp.value = d.client.name + ' (select below ↓)';
                    drop.innerHTML = `<li class="px-4 py-2 text-[10px] font-bold text-indigo-500 uppercase tracking-wider bg-indigo-50">Possible matches — click to select</li>`
                        + scored.slice(0, 5).map(({ idx }) => {
                            const c = clientsData[idx];
                            return `<li class="px-4 py-2.5 cursor-pointer hover:bg-indigo-50 border-b border-slate-50 text-sm" onclick="selectClient(${idx})">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 bg-indigo-600 rounded-full text-white flex items-center justify-center font-bold text-xs flex-shrink-0">${(c.name?.[0]??'C').toUpperCase()}</div>
                                    <div><div class="font-medium text-slate-800">${c.name}</div><div class="text-xs text-slate-400 font-mono">${c.sys_id}${c._phone?' · '+c._phone:''}</div></div>
                                </div></li>`;
                        }).join('');
                    drop.classList.remove('hidden');
                }
            }
        }

        // ── 2. Source ──────────────────────────────────────────
        if (d.source) {
            const srcEl = document.getElementById('leadSource');
            if (srcEl) srcEl.value = d.source;
        }

        // ── 3. Services ────────────────────────────────────────
        if (d.services?.length) {
            selectedServices.clear();
            d.services.forEach(sl => {
                if (allServices.find(s => s.slug === sl)) selectedServices.add(sl);
            });
            renderServiceGrid();
        }

        // ── 4. Common fields ───────────────────────────────────
        const common = d.common ?? {};

        if (common.title)  { const el=document.getElementById('commonTitle'); if(el) el.value=common.title; }
        if (common.budget) { const el=document.getElementById('commonBudget'); if(el) el.value=common.budget; }
        if (common.notes)  { const el=document.getElementById('leadNotes');   if(el) el.value=common.notes; }
        if (common.pax_adult  >= 0) { const el=document.getElementById('paxAdult');  if(el) el.value=common.pax_adult;  }
        if (common.pax_child  >= 0) { const el=document.getElementById('paxChild');  if(el) el.value=common.pax_child;  }
        if (common.pax_infant >= 0) { const el=document.getElementById('paxInfant'); if(el) el.value=common.pax_infant; }

        // ── 5. Countries → checkbox grid ──────────────────────
        if (common.countries?.length) {
            // Fuzzy match against allCountries + check checkboxes
            const toCheck = common.countries.map(ec => {
                // Try exact sys_id match first
                if (ec.sys_id) {
                    const exact = allCountries.find(c => c.sys_id === ec.sys_id);
                    if (exact) return exact.sys_id;
                }
                // Fuzzy name match
                const q = (ec.name ?? '').toLowerCase();
                const fuzzy = allCountries.find(c =>
                    c.name.toLowerCase().includes(q) || q.includes(c.name.toLowerCase())
                );
                return fuzzy?.sys_id ?? null;
            }).filter(Boolean);

            // Check matched country checkboxes
            document.querySelectorAll('.common-country-cb').forEach(cb => {
                cb.checked = toCheck.includes(cb.value);
            });
            onCommonCountryChange();
        }

        // ── 6. service_data → serviceAnswers ──────────────────
        if (d.service_data) {
            Object.assign(serviceAnswers, d.service_data);
        }

        // ── 6b. Top-level segment_type → air_ticket panel ─────
        const topSegType = d.segment_type
            || d.service_data?.air_ticket?.segment_type
            || '';
        if (topSegType && (selectedServices.has('air_ticket') || d.services?.includes('air_ticket'))) {
            if (!serviceAnswers.air_ticket) serviceAnswers.air_ticket = {};
            serviceAnswers.air_ticket.segment_type = topSegType;
        }

        // ── 7. Rebuild service panels with filled data ─────────
        await buildServicePanels();

        // ── 7b. Apply segment_type to UI after panel renders ──
        if (topSegType && document.getElementById('atSegTypeRow')) {
            setTimeout(() => onAtSegTypeChange(topSegType), 80);
        }

        // ── 8. Chips display ───────────────────────────────────
        const segTypeLabels = { one_way:'One Way', round_trip:'Round Trip', multi_city:'Multi City' };
        const chips = [];
        if (d.client?.name)    chips.push({ icon:'fa-user',          label: d.client.name,              color:'indigo' });
        if (d.services?.length)chips.push({ icon:'fa-layer-group',   label: d.services.length+' service(s)', color:'green'  });
        if (topSegType)        chips.push({ icon:'fa-route',         label: segTypeLabels[topSegType] ?? topSegType, color:'violet' });
        if (common.countries?.length) chips.push({ icon:'fa-globe', label: common.countries.map(c=>c.name).join(', '), color:'blue' });
        if (common.budget)     chips.push({ icon:'fa-money-bill',    label: 'BDT '+Number(common.budget).toLocaleString(), color:'emerald' });
        if (common.pax_adult)  chips.push({ icon:'fa-users',         label: `${common.pax_adult} adult${common.pax_adult>1?'s':''}`, color:'purple' });

        document.getElementById('extractChips').innerHTML = chips.map(i =>
            `<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-${i.color}-100 text-${i.color}-700">
                <i class="fas ${i.icon}"></i>${i.label}
            </span>`
        ).join('');
        document.getElementById('extractResult').classList.remove('hidden');

        showToast('success', 'Form auto-filled ✓');

    } catch(e) {
        showToast('error', e.message ?? 'Extraction failed');
    }
    hideLoader();
}

function clearContainer(id) {
    const c=document.getElementById(id);
    if(c) c.innerHTML='';
}

/* ═══════════════════════════════════════════════
   SUBMIT
═══════════════════════════════════════════════ */
async function convertToWork() {
    const btn = document.getElementById('convertBtn');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin text-xs"></i> Converting…'; }
    try {
        const res  = await fetch(MOVE_TO_WORK_API, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ sys_id: LEAD_SYS_ID }),
        });
        const json = await res.json();
        if (json.success || json.status === 'success') {
            showToast('success', 'Converted to Work ✓');
            setTimeout(() => {
                window.location.href = 'show-works.php?id=' + (json.work_sys_id ?? '');
            }, 1000);
        } else {
            throw new Error(json.message ?? 'Conversion failed');
        }
    } catch(e) {
        showToast('error', e.message);
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-arrow-right-arrow-left text-xs"></i> Convert to Work'; }
    }
}

async function submitLead() {
    const name=document.getElementById('clientName').value.trim();
    if(!name){showToast('error','Client name is required');return;}
    if(selectedServices.size===0){showToast('error','Please select at least one service');return;}
    const countries=collectCountries();
    if(!countries.length){showToast('error','Please add at least one destination country');return;}

    if (selectedServices.has('air_ticket')) syncAllAtSegments();
    if (selectedServices.has('hotel'))      syncAllHotelSegments();
    if (selectedServices.has('transport'))  syncAllTrSegments();
    if (selectedServices.has('visa'))       syncAllVisaSegments();
    if (selectedServices.has('umrah'))      syncUmrahData();
    if (selectedServices.has('package') || selectedServices.has('tour_package')) syncPackageData();

    const common={
        title:           document.getElementById('commonTitle').value.trim(),
        countries:       countries,
        pax_adult:   parseInt(document.getElementById('paxAdult').value)||0,
        pax_child:   parseInt(document.getElementById('paxChild').value)||0,
        pax_infant:  parseInt(document.getElementById('paxInfant').value)||0,
        budget:      document.getElementById('commonBudget').value||'',
    };

    const finalServiceData = JSON.parse(JSON.stringify(serviceAnswers));
    finalServiceData.common = common;

    if (selectedServices.has('package') || selectedServices.has('tour_package')) {
        // syncPackageData() already called above; just ensure basic fields are also in
        const slug    = selectedServices.has('tour_package') ? 'tour_package' : 'package';
        const pkgData = serviceAnswers[slug] || {};
        finalServiceData[slug] = {
            ...pkgData,
            title:       document.getElementById('pkgTitle')?.value?.trim()  || pkgData.title       || '',
            type:        document.getElementById('pkgType')?.value            || pkgData.type        || '',
            currency:    document.getElementById('pkgCurrency')?.value        || pkgData.currency    || '',
            description: document.getElementById('pkgDesc')?.value?.trim()   || pkgData.description || '',
        };
    }

    const btn=document.getElementById('submitBtn');
    btn.disabled=true; btn.innerHTML='<i class="fas fa-spinner fa-spin text-xs"></i> Saving…';

    const payload={
        client_info:{
            sys_id: selectedClient?.sys_id ?? null,
            name:   selectedClient?.name   ?? name,
            phone: document.getElementById('clientPhone').value.trim(),
            email: document.getElementById('clientEmail').value.trim(),
        },
        service_type:        [...selectedServices],
        service_count:       selectedServices.size,
        service_data:        finalServiceData,
        special_instruction: [],
        lead_info:{
            source: document.getElementById('leadSource').value,
            notes:  collectNotes(),
        },
    };

    console.log('Submitting payload:', JSON.stringify(payload, null, 2));

    try {
        const res=await fetch(STORE_API,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
        const json=await res.json();
        if(json.status==='success'){
            showToast('success',IS_EDIT?'Lead updated!':'Lead saved!');
            setTimeout(()=>{window.location.href='index-leads.php';},1300);
        } else {
            showToast('error',json.message??'Save failed');
            btn.disabled=false;
            btn.innerHTML=`<i class="fas fa-save text-xs"></i> ${IS_EDIT?'Update Lead':'Save Lead'}`;
        }
    } catch(e){
        console.error('Submit error:', e);
        showToast('error','Network error');
        btn.disabled=false;
        btn.innerHTML=`<i class="fas fa-save text-xs"></i> ${IS_EDIT?'Update Lead':'Save Lead'}`;
    }
}

/* ═══════════════════════════════════════════════
   EDIT MODE
═══════════════════════════════════════════════ */
async function loadLeadData() {
    showLoader('Loading lead data…');
    try {
        const res = await fetch(GET_LEAD_API);
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const json = await res.json();
        if (json.status !== 'success') throw new Error(json.message ?? 'Lead not found');

        const d  = json.data;
        const ci = d.client_info ?? {};

        // ── Assigned to badge ──────────────────────────────────
        const assignedTo = d.assigned_to;
        const badge      = document.getElementById('assignedBadge');
        const badgeName  = document.getElementById('assignedBadgeName');
        if (assignedTo?.name && badge) {
            badgeName.textContent = 'Assigned: ' + assignedTo.name;
            badge.classList.remove('hidden');
            badge.classList.add('inline-flex');
        }

        // ── Convert to Work button — show only if not yet converted ──
        const convertBtn = document.getElementById('convertBtn');
        if (convertBtn && d.lead_status !== 'converted') {
            convertBtn.classList.remove('hidden');
            convertBtn.classList.add('flex');
        }

        document.getElementById('clientName').value  = ci.name  ?? '';
        document.getElementById('clientPhone').value = flatVal(ci.phone);
        document.getElementById('clientEmail').value = flatVal(ci.email);
        if (ci.sys_id) {
            selectedClient = { ...ci, _phone: flatVal(ci.phone), _email: flatVal(ci.email) };
            if (clientInput) clientInput.value = `${ci.sys_id} | ${ci.name}`;
            document.getElementById('clientBadge')?.classList.remove('hidden');
            document.getElementById('badgeAvatar').textContent = (ci.name?.[0] ?? 'C').toUpperCase();
            document.getElementById('badgeName').textContent   = ci.name ?? '';
            document.getElementById('badgeMeta').textContent   = ci.sys_id ?? '';
        }

        const li = d.lead_info ?? {};
        document.getElementById('leadSource').value = li.source ?? '';
        const notes  = li.notes;
        const noteTa = document.getElementById('leadNotes');
        if (noteTa) {
            if (Array.isArray(notes))
                noteTa.value = notes.map(n => typeof n === 'object' ? (n.text || n.note || '') : n).filter(Boolean).join('\n');
            else if (typeof notes === 'string' && notes)
                noteTa.value = notes;
        }

        const svcType = Array.isArray(d.service_type) ? d.service_type : [];
        svcType.forEach(sl => selectedServices.add(sl));
        renderServiceGrid();

        serviceAnswers = (d.service_data && typeof d.service_data === 'object') ? d.service_data : {};

        const common = serviceAnswers.common ?? {};
        const $el = id => document.getElementById(id);
        if ($el('commonTitle')    && common.title)           $el('commonTitle').value    = common.title;
        if ($el('commonBudget')   && common.budget)          $el('commonBudget').value   = common.budget;
        if ($el('paxAdult'))  $el('paxAdult').value  = common.pax_adult  ?? 1;
        if ($el('paxChild'))  $el('paxChild').value  = common.pax_child  ?? 0;
        if ($el('paxInfant')) $el('paxInfant').value = common.pax_infant ?? 0;

        clearContainer('countryCheckboxGrid');
        const ctries = common.countries ?? [];
        const selectedIds = ctries.map(c => c.sys_id ?? c).filter(Boolean);
        // ensure any saved country that might not be in for_work list is added
        ctries.forEach(c => {
            const sysId = c.sys_id ?? c;
            if (sysId && !allCountries.find(x => x.sys_id === sysId))
                allCountries.push({ sys_id: sysId, name: c.name ?? sysId });
        });
        renderCountryCheckboxGrid(selectedIds);

        if (selectedServices.size > 0) buildServicePanels();

        // Restore Package data — basic fields only; destinations+accommodations
        // are rebuilt by buildServicePanels → buildPkgDestinations (which uses
        // serviceAnswers for pre-checked cities) and refreshPkgAccCitySelects.
        const pkgSlug = selectedServices.has('tour_package') ? 'tour_package' : 'package';
        if ((selectedServices.has('package') || selectedServices.has('tour_package')) && serviceAnswers[pkgSlug]) {
            const pkg = serviceAnswers[pkgSlug];
            if ($el('pkgTitle'))    $el('pkgTitle').value    = pkg.title       || '';
            if ($el('pkgType'))     $el('pkgType').value     = pkg.type        || '';
            if ($el('pkgCurrency')) $el('pkgCurrency').value = pkg.currency    || '';
            if ($el('pkgDesc'))     $el('pkgDesc').value     = pkg.description || '';

            // Rebuild accommodations with saved data
            const accContainer = document.getElementById('pkgAccommodations');
            if (accContainer && pkg.accommodations?.length) {
                accContainer.innerHTML = '';
                pkg.accommodations.forEach((a, i) => {
                    const tmp = document.createElement('div');
                    tmp.innerHTML = pkgAccHtml(a, i);
                    accContainer.appendChild(tmp.firstElementChild);
                });
                // city selects populated after buildPkgDestinations resolves (via refreshPkgAccCitySelects)
            }
        }

    } catch(e) {
        console.error('Load error:', e);
        showToast('error', 'Failed to load: ' + e.message);
    }
    hideLoader();
}

/* ═══════════════════════════════════════════════
   NEW HOTEL MODAL
═══════════════════════════════════════════════ */
let _nhSegIdx = null;

function openNewHotelModal(segIdx) {
    _nhSegIdx = segIdx;
    const seg        = document.getElementById(`hotelSeg${segIdx}`);
    const citySel    = seg?.querySelector('.hotel-seg-city');
    const cityOpt    = citySel?.options[citySel.selectedIndex];
    const countryId  = cityOpt?.dataset.country ?? '';
    const countryName = allCountries.find(c => c.sys_id === countryId)?.name ?? '';
    const cityName    = cityOpt?.dataset.name ?? cityOpt?.text ?? '';

    const ctx    = document.getElementById('newHotelContext');
    const ctxTxt = document.getElementById('newHotelContextText');
    if (countryName || cityName) {
        ctxTxt.textContent = [cityName, countryName].filter(Boolean).join(', ');
        ctx.classList.remove('hidden');
    } else {
        ctx.classList.add('hidden');
    }

    ['nhName','nhAddress','nhPhone','nhEmail'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('nhStar').value = '';
    document.getElementById('newHotelModal').classList.remove('hidden');
    setTimeout(() => document.getElementById('nhName').focus(), 100);
}

function closeNewHotelModal() {
    document.getElementById('newHotelModal').classList.add('hidden');
    _nhSegIdx           = null;
    _pkgAccIdx          = null;
    _pkgNewHotelCityData = null;
}

async function submitNewHotel() {
    const name = document.getElementById('nhName').value.trim();
    if (!name) { showToast('error', 'Hotel name is required'); return; }

    let countryId = '', countryName = '', cityId = '', cityName = '';

    if (_pkgNewHotelCityData) {
        // Called from pkg accommodation city card (new system)
        ({ cityId, cityName, countryId, countryName } = _pkgNewHotelCityData);
    } else if (_nhSegIdx !== null) {
        // Called from hotel segment
        const seg     = document.getElementById(`hotelSeg${_nhSegIdx}`);
        const citySel = seg?.querySelector('.hotel-seg-city');
        const cityOpt = citySel?.options[citySel.selectedIndex];
        cityId      = citySel?.value || '';
        cityName    = cityOpt?.dataset?.name || cityOpt?.text || '';
        countryId   = cityOpt?.dataset?.country || '';
        countryName = allCountries.find(c => c.sys_id === countryId)?.name ?? '';
    }

    const btn = document.getElementById('nhSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin text-xs mr-1"></i>Saving…';

    try {
        const res = await fetch(HOTEL_CREATE_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                name,
                country_sys_id: countryId,
                country_name:   countryName,
                city_sys_id:    cityId,
                city_name:      cityName,
                star_rating: document.getElementById('nhStar').value    || null,
                address:     document.getElementById('nhAddress').value  || null,
                phone:       document.getElementById('nhPhone').value    || null,
                email:       document.getElementById('nhEmail').value    || null,
            }),
        });
        const json = await res.json();
        if (json.status !== 'ok') throw new Error(json.message ?? 'Failed');

        const newHotel = { sys_id: json.hotel.sys_id || json.hotel.id, name: json.hotel.name, star_rating: '' };

        if (_pkgNewHotelCityData) {
            const { cityId, rowIdx = 0 } = _pkgNewHotelCityData;
            if (_pkgHotelCache[cityId]) _pkgHotelCache[cityId].unshift(newHotel);
            const searchInp = document.querySelector(`.pkg-acc-hotel-search[data-city="${cityId}"][data-row="${rowIdx}"]`);
            const sysIdInp  = document.querySelector(`.pkg-acc-hotel-sysid[data-city="${cityId}"][data-row="${rowIdx}"]`);
            if (searchInp) searchInp.value = newHotel.name;
            if (sysIdInp)  sysIdInp.value  = newHotel.sys_id;
            const dropId = searchInp?.dataset.drop;
            if (dropId) renderPkgHotelListForDrop(dropId, cityId, '', rowIdx);
            syncPackageData();
        } else if (_nhSegIdx !== null) {
            // Invalidate hotel cache for city + auto-select
            if (cityId && _hotelCache[cityId]) _hotelCache[cityId].unshift(newHotel);
            const seg      = document.getElementById(`hotelSeg${_nhSegIdx}`);
            const nameInp  = seg?.querySelector('.hotel-seg-name');
            const sysIdInp = seg?.querySelector('.hotel-sys-id');
            if (nameInp)  nameInp.value  = newHotel.name;
            if (sysIdInp) sysIdInp.value = newHotel.sys_id;
            syncAllHotelSegments();
        }

        showToast('success', `Hotel "${json.hotel.name}" created!`);
        closeNewHotelModal();
    } catch(e) {
        showToast('error', e.message ?? 'Failed to create hotel');
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-plus mr-1"></i>Create Hotel';
}

async function refreshHotelSearch(segIdx) {
    const seg    = document.getElementById(`hotelSeg${segIdx}`);
    const nameInp= seg?.querySelector('.hotel-seg-name');
    if (!nameInp) return;
    const val = nameInp.value.trim();
    if (val.length >= 2) {
        await onHotelSegNameInput(nameInp, segIdx);
        showToast('success', 'Hotel list refreshed');
    } else {
        showToast('error', 'Type at least 2 characters to search');
    }
}

/* ═══════════════════════════════════════════════
   HELPERS
═══════════════════════════════════════════════ */
function showLoader(msg='Processing…'){document.getElementById('loaderMsg').textContent=msg;document.getElementById('loaderOverlay').classList.remove('hidden');}
function hideLoader(){document.getElementById('loaderOverlay').classList.add('hidden');}
function esc(s){return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
function flatVal(v){
    if(!v) return '';
    if(typeof v==='string'){try{const p=JSON.parse(v);return p.primary_no??p.primary??Object.values(p).filter(Boolean)[0]??'';}catch{return v;}}
    if(typeof v==='object'){return v.primary_no??v.primary??Object.values(v).filter(Boolean)[0]??'';}
    return String(v);
}
function showToast(type,msg){const i=document.getElementById('toastInner');document.getElementById('toastMsg').textContent=msg;i.className=`flex items-center gap-3 px-4 py-3 rounded-xl shadow-2xl text-white text-sm font-semibold min-w-[200px] ${type==='success'?'bg-emerald-600':'bg-red-500'}`;document.getElementById('toastIcon').className=`fas ${type==='success'?'fa-check-circle':'fa-exclamation-circle'} flex-shrink-0`;document.getElementById('toast').classList.remove('hidden');setTimeout(()=>document.getElementById('toast').classList.add('hidden'),4000);}
</script>
</body>
</html>