<?php
// FILE PATH: /pages/create-work.php
// NOTE: No sidebar — custom header only
include_once('./authenticate.php');
$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) { $ip_port = "http://103.104.219.3:898"; }

$workType  = $_GET['type']    ?? '';
$workId    = $_GET['work_id'] ?? '';
$leadSysId = $_GET['lead_id'] ?? '';

$typeMap = [
    'visa'      => ['label'=>'VISA APPLICATION',  'icon'=>'fa-passport',         'hex'=>'#7c3aed', 'bg'=>'#f5f3ff', 'border'=>'#ddd6fe'],
    'hotel'     => ['label'=>'HOTEL BOOKING',     'icon'=>'fa-hotel',            'hex'=>'#db2777', 'bg'=>'#fdf2f8', 'border'=>'#fbcfe8'],
    'air'       => ['label'=>'AIR TICKET',        'icon'=>'fa-plane',            'hex'=>'#0284c7', 'bg'=>'#f0f9ff', 'border'=>'#bae6fd'],
    'tour'      => ['label'=>'TOUR PACKAGE',      'icon'=>'fa-suitcase-rolling', 'hex'=>'#059669', 'bg'=>'#f0fdf4', 'border'=>'#a7f3d0'],
    'umrah'     => ['label'=>'UMRAH',             'icon'=>'fa-kaaba',            'hex'=>'#d97706', 'bg'=>'#fffbeb', 'border'=>'#fde68a'],
    'transport' => ['label'=>'TRANSPORT',         'icon'=>'fa-bus',              'hex'=>'#ea580c', 'bg'=>'#fff7ed', 'border'=>'#fed7aa'],
];
$cur = $typeMap[$workType] ?? null;

$getAllClientsApi   = $ip_port . "api/clients/all-clients.php";
$getAllTravelersApi = $ip_port . "api/travelers/all-travelers.php";
$getLeadApi        = $ip_port . "api/leads/get.php";
$fileExplorerApi   = $ip_port . "api/travelers/file-explorer.php";
$aiChatApi         = $ip_port . "api/running-works/ai-chat.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $cur ? $cur['label'] : 'Create Work'; ?> — TravHub</title>
<link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
* { box-sizing: border-box; }
body { background: #f8fafc; font-family: 'Segoe UI', sans-serif; height: 100vh; overflow: hidden; color: #1e293b; }

/* ── HEADER ── */
#cw-hdr {
    height: 56px; background: #fff; border-bottom: 1px solid #e2e8f0;
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 20px; position: fixed; top: 0; left: 0; right: 0; z-index: 50;
}
.type-pill {
    display: flex; align-items: center; gap: 8px; padding: 5px 16px;
    border-radius: 999px; font-weight: 700; font-size: .75rem; letter-spacing: .08em; border: 1.5px solid;
}
.exit-btn {
    display: flex; align-items: center; gap: 6px; padding: 7px 14px;
    background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;
    border-radius: 8px; font-size: .78rem; font-weight: 600; text-decoration: none; transition: all .15s;
}
.exit-btn:hover { background: #fee2e2; }

/* ── BODY ── */
#cw-body { display: flex; height: calc(100vh - 56px); margin-top: 56px; }
#cw-main { flex: 1; overflow-y: auto; padding: 16px; display: flex; flex-direction: column; gap: 12px; min-width: 0; }
#cw-ai   { width: 300px; flex-shrink: 0; background: #fff; border-left: 1px solid #e2e8f0; display: flex; flex-direction: column; }

/* ── CARDS ── */
.cw-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
.cw-card-hdr {
    padding: 11px 16px; border-bottom: 1px solid #f1f5f9;
    display: flex; align-items: center; justify-content: space-between;
    font-size: .8rem; font-weight: 700; color: #475569;
}
.cw-card-hdr i { color: #6366f1; margin-right: 6px; }
.cw-card-body { padding: 14px; }

/* ── BUTTONS ── */
.btn { display: inline-flex; align-items: center; gap: 5px; padding: 7px 13px; border-radius: 8px; font-size: .78rem; font-weight: 600; cursor: pointer; transition: all .15s; border: none; font-family: inherit; }
.btn-indigo { background: #6366f1; color: #fff; } .btn-indigo:hover { background: #4f46e5; }
.btn-ghost  { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; } .btn-ghost:hover { background: #e2e8f0; }
.btn-green  { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; } .btn-green:hover { background: #dcfce7; }
.btn-amber  { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; } .btn-amber:hover { background: #fef3c7; }
.btn-red    { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; } .btn-red:hover { background: #fee2e2; }

/* ── INPUTS ── */
.inp { width: 100%; padding: 9px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: .83rem; font-family: inherit; color: #1e293b; transition: border .15s; background: #fff; }
.inp:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.08); }
.lbl { font-size: .75rem; font-weight: 600; color: #64748b; margin-bottom: 4px; display: block; }
textarea.inp { resize: vertical; min-height: 68px; line-height: 1.6; }

/* ── TRAVELER CHIP ── */
.t-chip { display: inline-flex; align-items: center; gap: 5px; padding: 3px 9px 3px 3px; border-radius: 999px; background: #eef2ff; border: 1.5px solid #c7d2fe; font-size: .74rem; font-weight: 600; color: #4338ca; }
.t-av { width: 20px; height: 20px; border-radius: 50%; background: #6366f1; color: #fff; font-size: .6rem; font-weight: 700; display: flex; align-items: center; justify-content: center; }
.t-rm { color: #a5b4fc; cursor: pointer; margin-left: 2px; font-size: .65rem; }
.t-rm:hover { color: #ef4444; }

/* ── TRAVELER DROPDOWN ── */
#tDrop { position: absolute; width: 100%; background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; margin-top: 4px; max-height: 200px; overflow-y: auto; z-index: 60; box-shadow: 0 8px 24px rgba(0,0,0,.1); display: none; }
.t-di { padding: 9px 13px; cursor: pointer; border-bottom: 1px solid #f8fafc; display: flex; align-items: center; gap: 9px; font-size: .8rem; }
.t-di:hover { background: #f5f3ff; } .t-di:last-child { border: none; }

/* ── TRAVELER TAB ── */
.t-tab { padding: 9px 16px; font-size: .79rem; font-weight: 600; border-bottom: 2px solid transparent; color: #64748b; cursor: pointer; transition: all .15s; white-space: nowrap; }
.t-tab.active { border-color: #6366f1; color: #6366f1; background: #f5f3ff; }
.t-tab:hover:not(.active) { background: #f8fafc; }

/* ── CHECKLIST ── */
.chk-row { display: flex; align-items: center; gap: 7px; padding: 5px 7px; border-radius: 7px; cursor: pointer; transition: background .12s; font-size: .79rem; color: #475569; }
.chk-row:hover { background: #f5f3ff; }
.chk-row input { accent-color: #6366f1; width: 14px; height: 14px; flex-shrink: 0; cursor: pointer; }
.chk-row label { cursor: pointer; }
.chk-row.done { color: #cbd5e1; text-decoration: line-through; }
#chk-scroll { overflow-y: auto; max-height: 220px; display: flex; flex-direction: column; gap: 1px; padding-right: 4px; }

/* ── FILE EXPLORER ── */
.fe-bar { display: flex; align-items: center; gap: 5px; padding: 7px 10px; border-bottom: 1px solid #f1f5f9; flex-wrap: wrap; }
.fe-btn { padding: 4px 9px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; font-size: .7rem; cursor: pointer; color: #475569; transition: all .13s; display: inline-flex; align-items: center; gap: 4px; }
.fe-btn:hover { background: #eef2ff; color: #6366f1; border-color: #c7d2fe; }
.fe-bc { font-size: .7rem; color: #94a3b8; padding: 5px 10px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 4px; flex-wrap: wrap; }
.fe-bc-p { cursor: pointer; color: #6366f1; } .fe-bc-p:hover { text-decoration: underline; }
.fe-grid { display: grid; grid-template-columns: repeat(auto-fill,minmax(68px,1fr)); gap: 5px; padding: 8px; max-height: 180px; overflow-y: auto; }
.fe-item { display: flex; flex-direction: column; align-items: center; gap: 3px; padding: 5px 3px; border-radius: 7px; cursor: pointer; transition: background .12s; text-align: center; }
.fe-item:hover { background: #eef2ff; }
.fe-icon { font-size: 1.6rem; line-height: 1; }
.fe-name { font-size: .6rem; color: #374151; word-break: break-all; max-width: 64px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.fe-size { font-size: .57rem; color: #9ca3af; }

/* ── WORD PROCESSOR TOOLBAR ── */
.wp-toolbar {
    display: flex; align-items: center; gap: 2px; padding: 7px 10px;
    border-bottom: 1px solid #e2e8f0; background: #f8fafc;
    flex-wrap: wrap;
}
.wp-btn {
    width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;
    border: none; background: transparent; border-radius: 5px; cursor: pointer;
    color: #475569; font-size: .78rem; transition: all .12s;
}
.wp-btn:hover { background: #e2e8f0; color: #1e293b; }
.wp-btn.active { background: #eef2ff; color: #6366f1; }
.wp-sep { width: 1px; height: 20px; background: #e2e8f0; margin: 0 4px; flex-shrink: 0; }
.wp-select { padding: 3px 6px; border: 1px solid #e2e8f0; border-radius: 5px; font-size: .72rem; color: #374151; background: #fff; cursor: pointer; }
#clEditor {
    flex: 1; padding: 16px; font-size: .85rem; line-height: 1.9;
    font-family: 'Times New Roman', Times, serif; color: #1e293b;
    border: none; outline: none; overflow-y: auto;
    min-height: 220px; max-height: 100%;
}
#clEditor:empty:before { content: attr(data-placeholder); color: #94a3b8; pointer-events: none; }
#clEditor b, #clEditor strong { font-weight: 700; }
#clEditor i, #clEditor em { font-style: italic; }
#clEditor u { text-decoration: underline; }
#clEditor table { border-collapse: collapse; width: 100%; margin: 8px 0; }
#clEditor td, #clEditor th { border: 1px solid #cbd5e1; padding: 5px 8px; font-size: .82rem; }
#clEditor th { background: #f1f5f9; font-weight: 700; }

/* ── COVER LETTER WRAPPER ── */
#cl-wrapper { display: flex; flex-direction: column; flex: 1; overflow: hidden; }

/* ── ACTION BAR ── */
.act-bar { display: flex; flex-wrap: wrap; gap: 7px; padding: 10px 14px; border-top: 1px solid #f1f5f9; background: #f8fafc; }

/* ── RIGHT PANEL LAYOUT (screen 2) ── */
#s2-layout { display: grid; grid-template-columns: 240px 1fr; min-height: 0; flex: 1; }
#s2-left { border-right: 1px solid #f1f5f9; display: flex; flex-direction: column; overflow: hidden; }
#s2-right { display: flex; flex-direction: column; overflow: hidden; min-height: 0; }

/* ── SCREENS ── */
.screen { display: none; flex-direction: column; gap: 12px; }
.screen.active { display: flex; }

/* ── GEN BUTTON ── */
.gen-btn {
    width: 72px; height: 72px; border-radius: 50%; background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: #fff; border: none; cursor: pointer; font-size: .68rem; font-weight: 700;
    display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 3px;
    box-shadow: 0 4px 14px rgba(99,102,241,.35); transition: all .2s; flex-shrink: 0;
}
.gen-btn:hover { transform: scale(1.06); box-shadow: 0 6px 20px rgba(99,102,241,.45); }

/* ── TYPE SELECTOR ── */
.type-card { background: #fff; border: 2px solid #f1f5f9; border-radius: 14px; padding: 22px 14px; text-align: center; cursor: pointer; transition: all .2s; text-decoration: none; display: block; }
.type-card:hover { transform: translateY(-3px); border-color: #c7d2fe; box-shadow: 0 8px 22px rgba(99,102,241,.12); }

/* ── AI PANEL ── */
.ai-hdr { padding: 11px 14px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; gap: 8px; }
.ai-dot { width: 7px; height: 7px; border-radius: 50%; background: #22c55e; animation: pulse 2s infinite; }
@keyframes pulse { 0%,100%{opacity:1;} 50%{opacity:.4;} }
#aiMessages { flex: 1; overflow-y: auto; padding: 12px; display: flex; flex-direction: column; gap: 9px; }
.ai-msg { max-width: 92%; padding: 9px 12px; border-radius: 11px; font-size: .79rem; line-height: 1.5; }
.ai-msg.bot  { background: #f0f4ff; color: #1e1b4b; align-self: flex-start; border: 1px solid #e0e7ff; border-bottom-left-radius: 3px; }
.ai-msg.user { background: #6366f1; color: #fff; align-self: flex-end; border-bottom-right-radius: 3px; }
.ai-msg.loading { background: #f8fafc; color: #94a3b8; align-self: flex-start; border: 1px solid #e2e8f0; }
#aiBar { border-top: 1px solid #e2e8f0; padding: 9px; display: flex; gap: 7px; }
#aiInp { flex: 1; border: 1.5px solid #e2e8f0; border-radius: 7px; padding: 8px 11px; font-size: .78rem; resize: none; font-family: inherit; max-height: 70px; transition: border .15s; background: #fff; }
#aiInp:focus { outline: none; border-color: #6366f1; }
#aiSend { padding: 8px 11px; background: #6366f1; color: #fff; border: none; border-radius: 7px; cursor: pointer; }
#aiSend:hover { background: #4f46e5; }

/* ── LEAD FIELDS GRID ── */
.lead-field { background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 7px; padding: 8px 10px; }
.lead-field .k { font-size: .65rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 2px; }
.lead-field .v { font-size: .8rem; font-weight: 600; color: #1e293b; }

/* ── MODAL ── */
.modal-bg { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.4); backdrop-filter: blur(4px); z-index: 70; align-items: center; justify-content: center; padding: 16px; }
.modal-box { background: #fff; border-radius: 16px; width: 100%; max-width: 580px; max-height: 92vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,.15); }
.modal-hdr { padding: 15px 18px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; font-weight: 700; font-size: .9rem; color: #1e293b; }
.modal-body { padding: 18px; display: flex; flex-direction: column; gap: 13px; }
.g2 { display: grid; grid-template-columns: 1fr 1fr; gap: 11px; }

/* Scrollbar */
::-webkit-scrollbar { width: 4px; height: 4px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 99px; }

/* Toast */
#toast { position: fixed; bottom: 18px; right: 18px; z-index: 99; display: none; }
#toastIn { display: flex; align-items: center; gap: 9px; padding: 10px 16px; border-radius: 10px; color: #fff; font-size: .79rem; font-weight: 600; box-shadow: 0 8px 24px rgba(0,0,0,.15); }
</style>
</head>
<body>

<!-- ══════════ HEADER ══════════ -->
<header id="cw-hdr">
    <a href="index.php"><img src="../assets/images/logo/logo.png" style="height:34px;" alt="TravHub"></a>

    <?php if($cur): ?>
    <div class="type-pill" style="color:<?=$cur['hex']?>;background:<?=$cur['bg']?>;border-color:<?=$cur['border']?>;">
        <i class="fas <?=$cur['icon']?>"></i><?=$cur['label']?>
    </div>
    <?php else: ?>
    <span style="font-size:.8rem;font-weight:700;color:#64748b;letter-spacing:.07em;">SELECT WORK TYPE</span>
    <?php endif; ?>

    <div style="display:flex;align-items:center;gap:10px;">
        <div style="display:flex;align-items:center;gap:7px;">
            <div style="width:30px;height:30px;border-radius:50%;background:#6366f1;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.75rem;">
                <?=strtoupper(substr($_SESSION['user_name']??'U',0,1))?>
            </div>
            <span style="font-size:.78rem;color:#64748b;font-weight:500;"><?=$_SESSION['user_name']??'User'?></span>
        </div>
        <a href="index.php" class="exit-btn"><i class="fas fa-sign-out-alt"></i>Exit</a>
    </div>
</header>

<!-- ══════════ TYPE SELECTOR ══════════ -->
<?php if(!$cur): ?>
<div style="display:flex;align-items:center;justify-content:center;padding:40px;margin-top:56px;min-height:calc(100vh - 56px);">
    <div style="max-width:660px;width:100%;text-align:center;">
        <h2 style="font-size:1.3rem;font-weight:800;color:#1e293b;margin-bottom:6px;">What type of work?</h2>
        <p style="color:#94a3b8;font-size:.86rem;margin-bottom:26px;">Select a service type to begin</p>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;">
            <?php foreach($typeMap as $k=>$v): ?>
            <a href="create-work.php?type=<?=$k?><?=$workId?'&work_id='.$workId:''?><?=$leadSysId?'&lead_id='.$leadSysId:''?>" class="type-card">
                <i class="fas <?=$v['icon']?>" style="font-size:2rem;color:<?=$v['hex']?>;margin-bottom:10px;display:block;"></i>
                <div style="font-weight:700;font-size:.8rem;color:#374151;"><?=$v['label']?></div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php else: ?>

<!-- ══════════ MAIN BODY ══════════ -->
<div id="cw-body">

    <!-- LEFT MAIN -->
    <div id="cw-main">

        <!-- SCREEN 1: Overview -->
        <div id="s1" class="screen active">

            <!-- Lead Details Card -->
            <div class="cw-card">
                <div class="cw-card-hdr">
                    <span><i class="fas fa-file-alt"></i>Lead Details</span>
                    <button class="btn btn-ghost" style="padding:4px 10px;font-size:.72rem;" onclick="openLeadModal()">
                        <i class="fas fa-pencil"></i> Edit
                    </button>
                </div>
                <div class="cw-card-body" id="leadDisplay">
                    <div style="color:#94a3b8;font-size:.82rem;padding:6px 0;">
                        No lead details yet. Click <b style="color:#6366f1;cursor:pointer;" onclick="openLeadModal()">Edit</b> to fill in.
                    </div>
                </div>
            </div>

            <!-- Summary -->
            <div class="cw-card">
                <div class="cw-card-hdr"><span><i class="fas fa-pen-to-square"></i>Summary</span></div>
                <div class="cw-card-body">
                    <textarea id="workSummary" class="inp" placeholder="Brief summary — client needs, urgency, follow-ups..."></textarea>
                </div>
            </div>

            <!-- Traveler Selection -->
            <div class="cw-card">
                <div class="cw-card-hdr">
                    <span><i class="fas fa-users"></i>Traveler Selection</span>
                    <span style="font-size:.7rem;color:#94a3b8;font-weight:400;">Each traveler gets individual documents</span>
                </div>
                <div class="cw-card-body" style="display:flex;flex-direction:column;gap:10px;">
                    <div style="display:grid;grid-template-columns:1fr auto auto;gap:7px;align-items:center;">
                        <div style="position:relative;" id="tWrap">
                            <i class="fas fa-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:.75rem;pointer-events:none;"></i>
                            <input type="text" id="tInp" class="inp" style="padding-left:30px;" placeholder="Search traveler..." autocomplete="off">
                            <div id="tDrop"></div>
                        </div>
                        <a href="create-traveler.php" target="_blank" class="btn btn-indigo" style="padding:8px 11px;" title="Add traveler"><i class="fas fa-plus"></i></a>
                        <button onclick="loadTravelers()" class="btn btn-ghost" style="padding:8px 11px;" title="Refresh"><i class="fas fa-rotate-right"></i></button>
                    </div>
                    <div id="tChips" style="display:flex;flex-wrap:wrap;gap:5px;min-height:24px;">
                        <span style="font-size:.76rem;color:#94a3b8;">No travelers selected</span>
                    </div>
                    <div style="display:flex;align-items:flex-end;justify-content:space-between;gap:10px;flex-wrap:wrap;">
                        <div id="tMiniCards" style="display:flex;flex-wrap:wrap;gap:7px;flex:1;"></div>
                        <button class="gen-btn" onclick="genAndProceed()" title="Generate AI prompt & open per-traveler view">
                            <i class="fas fa-wand-magic-sparkles" style="font-size:1.15rem;"></i>
                            <span style="line-height:1.3;">Generate<br>Prompt</span>
                        </button>
                    </div>
                    <div id="tWarn" style="display:none;padding:7px 11px;background:#fef2f2;border:1px solid #fecaca;border-radius:7px;color:#dc2626;font-size:.76rem;">
                        <i class="fas fa-exclamation-triangle mr-1"></i>Select at least one traveler.
                    </div>
                </div>
            </div>

        </div><!-- /s1 -->

        <!-- SCREEN 2: Per-traveler -->
        <div id="s2" class="screen" style="flex:1;min-height:0;">
            <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
                <button onclick="goS(1)" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> Back</button>
                <span style="font-size:.76rem;color:#94a3b8;">Per-traveler documents</span>
            </div>
            <!-- Traveler tabs card — full height -->
            <div class="cw-card" style="flex:1;display:flex;flex-direction:column;min-height:0;overflow:hidden;">
                <div style="border-bottom:1px solid #f1f5f9;overflow-x:auto;flex-shrink:0;">
                    <div id="tTabNav" style="display:flex;padding:0 4px;"></div>
                </div>
                <div id="tTabCont" style="flex:1;min-height:0;overflow:hidden;display:flex;flex-direction:column;"></div>
            </div>
        </div><!-- /s2 -->

    </div><!-- /cw-main -->

    <!-- RIGHT: AI CHATBOT -->
    <div id="cw-ai">
        <div class="ai-hdr">
            <div class="ai-dot"></div>
            <div>
                <div style="font-size:.8rem;font-weight:700;color:#1e293b;">AI Assistant</div>
                <div style="font-size:.67rem;color:#94a3b8;">Gemini · <?=$cur['label']?></div>
            </div>
        </div>
        <div id="aiMessages">
            <div class="ai-msg bot">
                👋 I'm your AI assistant for this <b><?=$cur['label']?></b> work.<br><br>
                I can generate visa checklists, write cover letters, list requirements, and more. Type below or click any action button.
            </div>
        </div>
        <div id="aiBar">
            <textarea id="aiInp" rows="1" placeholder="Ask or instruct..."
                onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendAI();}"></textarea>
            <button id="aiSend" onclick="sendAI()"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>

</div><!-- /cw-body -->
<?php endif; ?>

<!-- ══════════ LEAD DETAILS MODAL ══════════ -->
<div id="leadModal" class="modal-bg">
    <div class="modal-box">
        <div class="modal-hdr">
            <span><i class="fas fa-file-alt mr-2" style="color:#6366f1;"></i>Visa Lead Details</span>
            <button onclick="closeLeadModal()" style="background:none;border:none;cursor:pointer;color:#94a3b8;font-size:1.1rem;"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">

            <!-- Client Selection — from form-selects/clients.php pattern -->
            <div>
                <label class="lbl">Client *</label>
                <?php
                // Inline the clients.php component (adjusted for modal context)
                $getAllClientsApiForModal = $ip_port . "api/clients/all-clients.php";
                ?>
                <div class="grid grid-cols-4 gap-3 items-center">
                    <div id="clientSearchContainer" class="relative w-full col-span-3">
                        <input type="text" id="clientInput"
                            placeholder="Search for a client..."
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none shadow-sm transition-all text-sm"
                            autocomplete="off">
                        <ul id="clientDropdown"
                            class="absolute w-full bg-white border border-gray-300 rounded-lg mt-1 max-h-60 overflow-auto shadow-xl hidden z-50"></ul>
                    </div>
                    <div class="col-span-1 flex gap-2">
                        <a href="./create-client.php" target="_blank" title="Add New Client"
                           class="flex items-center justify-center w-full bg-indigo-600 hover:bg-indigo-700 text-white py-2.5 rounded-lg transition-colors shadow-sm text-sm">
                            <i class="fas fa-plus"></i>
                        </a>
                        <button type="button" onclick="loadClients()" title="Refresh List"
                                class="flex items-center justify-center w-full bg-gray-100 hover:bg-gray-200 text-gray-700 py-2.5 rounded-lg border border-gray-300 transition-all shadow-sm text-sm">
                            <i class="fa-solid fa-arrows-rotate"></i>
                        </button>
                    </div>
                </div>
                <!-- Selected client badge -->
                <div id="selClientBadge" class="hidden mt-2 flex items-center gap-3 px-3 py-2 bg-indigo-50 border border-indigo-200 rounded-lg">
                    <div id="selClientAv" class="w-8 h-8 bg-indigo-600 rounded-full text-white flex items-center justify-center font-bold text-sm">?</div>
                    <div>
                        <div id="selClientName" class="font-semibold text-sm text-gray-800">—</div>
                        <div id="selClientMeta" class="text-xs text-gray-500">—</div>
                    </div>
                    <button onclick="clearClient()" class="ml-auto text-gray-400 hover:text-red-500"><i class="fas fa-times text-xs"></i></button>
                </div>
            </div>

            <!-- Visa Details -->
            <div class="g2">
                <div><label class="lbl">Destination Country *</label><input type="text" id="ld_country" class="inp" placeholder="e.g. Thailand"></div>
                <div><label class="lbl">Visa Type</label>
                    <select id="ld_visaType" class="inp">
                        <option value="">Select</option>
                        <option>Tourist</option><option>Business</option><option>Student</option>
                        <option>Work</option><option>Medical</option><option>Transit</option><option>Family</option>
                    </select>
                </div>
            </div>
            <div class="g2">
                <div><label class="lbl">Entry Type</label>
                    <select id="ld_entry" class="inp">
                        <option value="">Select</option><option>Single Entry</option><option>Multiple Entry</option>
                    </select>
                </div>
                <div><label class="lbl">Urgency</label>
                    <select id="ld_urgency" class="inp">
                        <option value="">Select</option><option>Normal</option><option>Urgent</option><option>Express</option>
                    </select>
                </div>
            </div>
            <div class="g2">
                <div><label class="lbl">Travel Date (From)</label><input type="date" id="ld_from" class="inp"></div>
                <div><label class="lbl">Travel Date (To)</label><input type="date" id="ld_to" class="inp"></div>
            </div>
            <div><label class="lbl">Embassy / Consulate</label><input type="text" id="ld_embassy" class="inp" placeholder="e.g. Royal Thai Embassy, Dhaka, Bangladesh"></div>
            <div><label class="lbl">Notes / Purpose</label><textarea id="ld_notes" class="inp" placeholder="Brief purpose or special notes..."></textarea></div>

            <div style="display:flex;justify-content:flex-end;gap:8px;padding-top:4px;">
                <button onclick="closeLeadModal()" class="btn btn-ghost">Cancel</button>
                <button onclick="saveLeadDetails()" class="btn btn-indigo"><i class="fas fa-check mr-1"></i>Save Details</button>
            </div>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="toast"><div id="toastIn"><i id="toastIco" class="fas fa-check-circle"></i><span id="toastMsg"></span></div></div>

<script>
const WT         = "<?=$workType?>";
const FE_API     = "<?=$fileExplorerApi?>";
const AI_API     = "<?=$aiChatApi?>";
const T_API      = "<?=$getAllTravelersApi?>";
const LEAD_API   = "<?=$getLeadApi?>";
const CL_API_URL = "<?=$getAllClientsApiForModal?>";
const LEAD_ID    = "<?=$leadSysId?>";

let selClient     = null;
let clientsData   = [];
let travelersData = [];
let selTravelers  = [];
let leadData      = {};
let aiHistory     = [];
// tracks which traveler has a generated cover letter
let clGenerated   = {};

// ══ INIT ══════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', () => {
    loadClients();
    loadTravelers();
    if (LEAD_ID) fetchLead(LEAD_ID);
    setupTSearch();
    setupAIInp();
    // close modal on backdrop click
    document.getElementById('leadModal').addEventListener('click', e => {
        if (e.target === document.getElementById('leadModal')) closeLeadModal();
    });
});

// ══ CLIENTS (exact pattern from form-selects/clients.php) ══
const GET_ALL_CLIENTS_API = CL_API_URL;

function loadClients() {
    fetch(GET_ALL_CLIENTS_API)
        .then(r => r.json())
        .then(d => { clientsData = Array.isArray(d.clients) ? d.clients : []; })
        .catch(() => clientsData = []);
}

const clientInput     = document.getElementById('clientInput');
const clientDropdown  = document.getElementById('clientDropdown');
const clientContainer = document.getElementById('clientSearchContainer');

let clientTypingTimer;
clientInput.addEventListener('input', () => {
    clearTimeout(clientTypingTimer);
    clientTypingTimer = setTimeout(() => {
        const value = clientInput.value.toLowerCase().trim();
        const filtered = value === ''
            ? clientsData
            : clientsData.filter(c => c.name?.toLowerCase().includes(value) || c.sys_id?.toLowerCase().includes(value));
        renderClientDropdown(filtered);
        clientDropdown.classList.remove('hidden');
    }, 300);
});

clientInput.addEventListener('focus', () => {
    renderClientDropdown(clientsData);
    clientDropdown.classList.remove('hidden');
});

function renderClientDropdown(list) {
    clientDropdown.innerHTML = '';
    if (!list.length) {
        clientDropdown.innerHTML = `<li class="px-4 py-3 text-center text-gray-500 text-sm">No clients found</li>`;
        return;
    }
    list.forEach(client => {
        let phone = '';
        try { if (client.phone?.startsWith('{')) phone = JSON.parse(client.phone).primary_no ?? ''; } catch {}
        const li = document.createElement('li');
        li.className = 'px-4 py-3 cursor-pointer hover:bg-indigo-50 border-b last:border-b-0 text-sm';
        li.innerHTML = `
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-indigo-600 rounded-full text-white flex items-center justify-center font-semibold text-sm flex-shrink-0">
                    ${client.name?.charAt(0).toUpperCase() ?? 'C'}
                </div>
                <div>
                    <div class="font-medium text-gray-800">${client.name}</div>
                    <div class="text-xs text-gray-500">ID: ${client.sys_id}${phone ? ' · 📞 ' + phone : ''}</div>
                </div>
            </div>`;
        li.onclick = () => {
            selClient = client;
            clientInput.value = `${client.sys_id} | ${client.name}`;
            clientDropdown.classList.add('hidden');
            // Show selected badge
            document.getElementById('selClientAv').textContent = client.name?.charAt(0).toUpperCase() ?? 'C';
            document.getElementById('selClientName').textContent = client.name;
            document.getElementById('selClientMeta').textContent = `ID: ${client.sys_id}${phone ? ' · ' + phone : ''}`;
            document.getElementById('selClientBadge').classList.remove('hidden');
        };
        clientDropdown.appendChild(li);
    });
}

document.addEventListener('click', e => {
    if (!clientContainer.contains(e.target)) clientDropdown.classList.add('hidden');
});

function clearClient() {
    selClient = null;
    clientInput.value = '';
    document.getElementById('selClientBadge').classList.add('hidden');
}

// ══ FETCH LEAD FROM API ═══════════════════════════════
async function fetchLead(id) {
    try {
        const r = await fetch(LEAD_API + '?sys_id=' + id);
        const j = await r.json();
        if (j.lead) {
            const ci = safeJ(j.lead.client_info) ?? {};
            const sd = safeJ(j.lead.service_data) ?? {};
            const vd = sd[WT] ?? {};
            // Pre-fill client input if name available
            if (ci.name) {
                clientInput.value = (ci.sys_id ? ci.sys_id + ' | ' : '') + ci.name;
                selClient = ci;
            }
            saveAndRenderLead({
                country: vd.country ?? '', visaType: vd.category ?? '',
                entry: vd.subtype ?? '', urgency: vd.urgency ?? '',
                from: vd.travel_date ?? '', to: vd.return_date ?? '',
                embassy: '', notes: vd.notes ?? '',
            });
        }
    } catch {}
}

// ══ LEAD MODAL ════════════════════════════════════════
function openLeadModal() {
    // Pre-fill fields
    document.getElementById('ld_country').value   = leadData.country  ?? '';
    document.getElementById('ld_visaType').value  = leadData.visaType ?? '';
    document.getElementById('ld_entry').value     = leadData.entry    ?? '';
    document.getElementById('ld_urgency').value   = leadData.urgency  ?? '';
    document.getElementById('ld_from').value      = leadData.from     ?? '';
    document.getElementById('ld_to').value        = leadData.to       ?? '';
    document.getElementById('ld_embassy').value   = leadData.embassy  ?? '';
    document.getElementById('ld_notes').value     = leadData.notes    ?? '';
    document.getElementById('leadModal').style.display = 'flex';
}

function closeLeadModal() { document.getElementById('leadModal').style.display = 'none'; }

function saveLeadDetails() {
    saveAndRenderLead({
        country:  document.getElementById('ld_country').value.trim(),
        visaType: document.getElementById('ld_visaType').value,
        entry:    document.getElementById('ld_entry').value,
        urgency:  document.getElementById('ld_urgency').value,
        from:     document.getElementById('ld_from').value,
        to:       document.getElementById('ld_to').value,
        embassy:  document.getElementById('ld_embassy').value.trim(),
        notes:    document.getElementById('ld_notes').value.trim(),
    });
    closeLeadModal();
    showToast('success', 'Lead details saved.');
}

function saveAndRenderLead(d) {
    leadData = d;
    const items = [
        ['Country', d.country], ['Visa Type', d.visaType], ['Entry', d.entry],
        ['Urgency', d.urgency], ['From', d.from], ['To', d.to],
        ['Embassy', d.embassy], ['Notes', d.notes],
    ].filter(([, v]) => v);

    const el = document.getElementById('leadDisplay');
    if (items.length) {
        el.innerHTML = `<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:7px;">
            ${items.map(([k, v]) => `<div class="lead-field"><div class="k">${k}</div><div class="v">${v}</div></div>`).join('')}
        </div>`;
    } else {
        el.innerHTML = `<div style="color:#94a3b8;font-size:.82rem;">No lead details yet. Click <b style="color:#6366f1;cursor:pointer;" onclick="openLeadModal()">Edit</b> to fill in.</div>`;
    }
}

// ══ TRAVELERS ═════════════════════════════════════════
async function loadTravelers() {
    try { const r = await fetch(T_API); const j = await r.json(); travelersData = j.travelers ?? []; } catch { travelersData = []; }
}

function setupTSearch() {
    const inp  = document.getElementById('tInp');
    const drop = document.getElementById('tDrop');
    const wrap = document.getElementById('tWrap');
    let tm;
    inp.addEventListener('input', () => {
        clearTimeout(tm); tm = setTimeout(() => {
            const v = inp.value.toLowerCase().trim();
            const f = v ? travelersData.filter(t => t.name?.toLowerCase().includes(v) || t.sys_id?.toLowerCase().includes(v)) : travelersData.slice(0, 8);
            renderTDrop(f, drop);
        }, 250);
    });
    inp.addEventListener('focus', () => { renderTDrop(travelersData.slice(0, 8), drop); });
    document.addEventListener('click', e => { if (!wrap.contains(e.target)) drop.style.display = 'none'; });
}

function renderTDrop(list, drop) {
    if (!list.length) { drop.innerHTML = `<div class="t-di" style="color:#94a3b8;justify-content:center;">No travelers found</div>`; }
    else {
        drop.innerHTML = list.map(t => {
            let ph = ''; try { if (t.phone?.startsWith('{')) ph = JSON.parse(t.phone).primary_no ?? ''; } catch {}
            return `<div class="t-di" onclick="selT('${t.sys_id}','${(t.name ?? '').replace(/'/g, "\\'")}','${ph}','${t.type ?? ''}')">
                <div class="t-av">${t.name?.[0]?.toUpperCase() ?? 'T'}</div>
                <div><div style="font-weight:600;color:#1e293b;font-size:.81rem;">${t.name ?? '—'}</div>
                <div style="font-size:.68rem;color:#64748b;">${t.sys_id}${ph ? ' · ' + ph : ''}</div></div>
            </div>`;
        }).join('');
    }
    drop.style.display = 'block';
}

function selT(id, name, phone, type) {
    if (selTravelers.find(t => t.sys_id === id)) { showToast('error', 'Already added.'); return; }
    selTravelers.push({ sys_id: id, name, phone, type });
    document.getElementById('tInp').value = '';
    document.getElementById('tDrop').style.display = 'none';
    renderTUI();
    document.getElementById('tWarn').style.display = 'none';
}

function rmT(id) { selTravelers = selTravelers.filter(t => t.sys_id !== id); renderTUI(); }

function renderTUI() {
    const chips = document.getElementById('tChips');
    const minis = document.getElementById('tMiniCards');
    if (!selTravelers.length) {
        chips.innerHTML = `<span style="font-size:.76rem;color:#94a3b8;">No travelers selected</span>`;
        minis.innerHTML = ''; return;
    }
    chips.innerHTML = selTravelers.map(t => `
        <div class="t-chip">
            <div class="t-av">${t.name[0].toUpperCase()}</div>${t.name}
            <span class="t-rm" onclick="rmT('${t.sys_id}')"><i class="fas fa-times"></i></span>
        </div>`).join('');
    minis.innerHTML = selTravelers.map(t => `
        <div style="padding:6px 12px;background:#eef2ff;border:1.5px solid #c7d2fe;border-radius:8px;font-size:.76rem;font-weight:600;color:#4338ca;cursor:pointer;"
            onclick="goS(2);setTimeout(()=>switchTab('${t.sys_id}'),80);">
            <i class="fas fa-user mr-1"></i>${t.name}
        </div>`).join('');
}

// ══ GENERATE & PROCEED ════════════════════════════════
function genAndProceed() {
    if (!selTravelers.length) { document.getElementById('tWarn').style.display = 'block'; return; }
    document.getElementById('tWarn').style.display = 'none';
    const names   = selTravelers.map(t => t.name).join(', ');
    const country = leadData.country  || 'the destination country';
    const vType   = leadData.visaType || 'Tourist';
    const prompt  = `Generate a detailed visa requirements checklist for the following traveler(s): ${names}. They are applying for a ${vType} visa to ${country}. List all required documents with a brief 1-line explanation for each. Format as a numbered list.`;

    appendMsg('user', prompt);
    callAI(prompt, reply => {
        // Parse numbered list from AI response and push into all traveler checklists
        const lines = reply.split('\n').filter(l => /^\d+\./.test(l.trim()));
        const items = lines.map(l => l.replace(/^\d+\.\s*/, '').replace(/\*\*/g, '').trim()).filter(Boolean);
        if (items.length) window._aiChkItems = items;
        // Inject into existing panels if already built
        selTravelers.forEach(t => {
            const cont = document.getElementById(`chk_ai_${t.sys_id}`);
            if (cont) renderAIChecklist(t.sys_id, items);
        });
    });
    goS(2);
}

// ══ SCREENS ═══════════════════════════════════════════
function goS(n) {
    document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
    document.getElementById('s' + n).classList.add('active');
    if (n === 2) buildTabs();
}

// ══ TRAVELER TABS ═════════════════════════════════════
function buildTabs() {
    const nav  = document.getElementById('tTabNav');
    const cont = document.getElementById('tTabCont');
    // only rebuild if needed
    if (nav.children.length === selTravelers.length) return;
    nav.innerHTML = cont.innerHTML = '';
    selTravelers.forEach((t, i) => {
        const btn = document.createElement('button');
        btn.className = `t-tab ${i === 0 ? 'active' : ''}`;
        btn.id = `ttab_${t.sys_id}`;
        btn.innerHTML = `<i class="fas fa-user mr-1"></i>${t.name}`;
        btn.onclick = () => switchTab(t.sys_id);
        nav.appendChild(btn);

        const panel = document.createElement('div');
        panel.id = `tp_${t.sys_id}`;
        panel.style.cssText = `display:${i === 0 ? 'flex' : 'none'};flex-direction:column;flex:1;min-height:0;`;
        panel.innerHTML = tPanelHTML(t);
        cont.appendChild(panel);
    });
    // Load file explorer for first traveler
    if (selTravelers.length) setTimeout(() => loadFE(selTravelers[0].sys_id, ''), 50);
    // Inject AI checklist if already generated
    if (window._aiChkItems) selTravelers.forEach(t => renderAIChecklist(t.sys_id, window._aiChkItems));
}

function switchTab(id) {
    selTravelers.forEach(t => {
        document.getElementById(`ttab_${t.sys_id}`)?.classList.toggle('active', t.sys_id === id);
        const p = document.getElementById(`tp_${t.sys_id}`);
        if (p) p.style.display = t.sys_id === id ? 'flex' : 'none';
    });
    loadFE(id, '');
}

// ══ TRAVELER PANEL HTML ═══════════════════════════════
const DEF_CHK = [
    ['passport',     'Passport (valid 6+ months)'],
    ['photo',        'Passport Size Photo (recent)'],
    ['nid',          'NID / Birth Certificate'],
    ['bank_stmt',    'Bank Statement (last 3 months)'],
    ['noc',          'NOC / Employment Letter'],
    ['cover_letter', 'Cover Letter'],
    ['hotel',        'Hotel Booking Confirmation'],
    ['flight',       'Flight Ticket (tentative)'],
    ['insurance',    'Travel Insurance'],
    ['prev_visa',    'Previous Visa Copies'],
    ['tin',          'TIN Certificate'],
    ['trade',        'Trade License'],
    ['app_form',     'Visa Application Form'],
];

function tPanelHTML(t) {
    const chkHTML = DEF_CHK.map(([k, l]) => `
        <div class="chk-row" id="chk_${t.sys_id}_${k}" onclick="toggleChk('${t.sys_id}','${k}')">
            <input type="checkbox" id="chkb_${t.sys_id}_${k}" onclick="event.stopPropagation();" onchange="toggleChk('${t.sys_id}','${k}')">
            <label for="chkb_${t.sys_id}_${k}">${l}</label>
        </div>`).join('');

    return `
    <div id="s2-layout" style="display:grid;grid-template-columns:240px 1fr;flex:1;min-height:0;overflow:hidden;">

        <!-- LEFT: File Explorer + Checklist -->
        <div id="s2-left" style="border-right:1px solid #f1f5f9;display:flex;flex-direction:column;overflow:hidden;min-height:0;">

            <!-- File Explorer -->
            <div style="flex-shrink:0;">
                <div class="fe-bar">
                    <span style="font-size:.72rem;font-weight:700;color:#475569;flex:1;"><i class="fas fa-folder-open mr-1" style="color:#6366f1;"></i>File Explorer</span>
                    <button class="fe-btn" onclick="loadFE('${t.sys_id}','')" title="Root"><i class="fas fa-home"></i></button>
                    <button class="fe-btn" onclick="feMkdir('${t.sys_id}')" title="New folder"><i class="fas fa-folder-plus"></i></button>
                    <button class="fe-btn" onclick="feUpload('${t.sys_id}')" title="Upload"><i class="fas fa-upload"></i></button>
                </div>
                <div class="fe-bc" id="febc_${t.sys_id}">
                    <span class="fe-bc-p" onclick="loadFE('${t.sys_id}','')"><i class="fas fa-home"></i></span>
                </div>
                <div class="fe-grid" id="fegrid_${t.sys_id}" data-path="" data-loaded="0">
                    <div style="grid-column:1/-1;padding:14px;text-align:center;color:#94a3b8;font-size:.74rem;"><i class="fas fa-spinner fa-spin mr-1"></i>Loading...</div>
                </div>
            </div>

            <!-- Checklist — y-scroll -->
            <div style="flex:1;overflow:hidden;display:flex;flex-direction:column;min-height:0;">
                <div style="padding:10px 10px 6px;flex-shrink:0;display:flex;align-items:center;justify-content:space-between;">
                    <span style="font-size:.7rem;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.04em;"><i class="fas fa-list-check mr-1" style="color:#6366f1;"></i>Visa Checklist</span>
                    <button onclick="addCustomChk('${t.sys_id}')" title="Add item" style="background:none;border:none;cursor:pointer;color:#6366f1;font-size:.72rem;">
                        <i class="fas fa-plus"></i> Add
                    </button>
                </div>
                <!-- OVERFLOW SCROLL HERE -->
                <div style="flex:1;overflow-y:auto;padding:0 10px 10px;">
                    <div id="chklist_${t.sys_id}">${chkHTML}</div>
                    <div id="chk_ai_${t.sys_id}"></div>
                </div>
            </div>
        </div>

        <!-- RIGHT: Cover Letter (fixed, responsive, word processor) -->
        <div id="s2-right" style="display:flex;flex-direction:column;min-height:0;overflow:hidden;">

            <!-- Word Processor Toolbar -->
            <div class="wp-toolbar">
                <!-- Text style -->
                <select class="wp-select" onchange="execCmd('formatBlock',this.value);this.value='';">
                    <option value="">Paragraph</option>
                    <option value="H1">H1</option><option value="H2">H2</option>
                    <option value="H3">H3</option><option value="P">Normal</option>
                </select>
                <div class="wp-sep"></div>
                <button class="wp-btn" onclick="execCmd('bold')" title="Bold"><b>B</b></button>
                <button class="wp-btn" onclick="execCmd('italic')" title="Italic"><i>I</i></button>
                <button class="wp-btn" onclick="execCmd('underline')" title="Underline"><u>U</u></button>
                <button class="wp-btn" onclick="execCmd('strikeThrough')" title="Strikethrough"><s>S</s></button>
                <div class="wp-sep"></div>
                <button class="wp-btn" onclick="execCmd('justifyLeft')" title="Align left"><i class="fas fa-align-left"></i></button>
                <button class="wp-btn" onclick="execCmd('justifyCenter')" title="Center"><i class="fas fa-align-center"></i></button>
                <button class="wp-btn" onclick="execCmd('justifyRight')" title="Align right"><i class="fas fa-align-right"></i></button>
                <div class="wp-sep"></div>
                <button class="wp-btn" onclick="execCmd('insertUnorderedList')" title="Bullet list"><i class="fas fa-list-ul"></i></button>
                <button class="wp-btn" onclick="execCmd('insertOrderedList')" title="Numbered list"><i class="fas fa-list-ol"></i></button>
                <div class="wp-sep"></div>
                <button class="wp-btn" onclick="insertTable('${t.sys_id}')" title="Insert table"><i class="fas fa-table"></i></button>
                <div class="wp-sep"></div>
                <button class="wp-btn" onclick="execCmd('undo')" title="Undo"><i class="fas fa-rotate-left"></i></button>
                <button class="wp-btn" onclick="execCmd('redo')" title="Redo"><i class="fas fa-rotate-right"></i></button>
                <div class="wp-sep"></div>
                <button class="wp-btn" onclick="genCL('${t.sys_id}','${t.name.replace(/'/g, "\\'")}')" title="Generate with AI" style="color:#6366f1;font-weight:700;">
                    <i class="fas fa-wand-magic-sparkles"></i>
                </button>
                <button class="wp-btn" onclick="copyText('${t.sys_id}')" title="Copy all"><i class="fas fa-copy"></i></button>
                <button class="wp-btn" onclick="printCL('${t.sys_id}')" title="Print"><i class="fas fa-print"></i></button>
            </div>

            <!-- Editable cover letter area -->
            <div id="clEditor_${t.sys_id}"
                contenteditable="true"
                data-placeholder="Cover letter will appear here after generation, or start typing..."
                style="flex:1;padding:16px;font-size:.85rem;line-height:1.9;font-family:'Times New Roman',Times,serif;color:#1e293b;border:none;outline:none;overflow-y:auto;min-height:0;"></div>

            <!-- Action bar -->
            <div class="act-bar">
                <button class="btn btn-indigo" onclick="genCL('${t.sys_id}','${t.name.replace(/'/g, "\\'")}')">
                    <i class="fas fa-wand-magic-sparkles"></i> Generate Cover Letter
                </button>
                <button class="btn btn-green" onclick="copyText('${t.sys_id}')">
                    <i class="fas fa-copy"></i> Copy
                </button>
                <button class="btn btn-ghost" onclick="printCL('${t.sys_id}')">
                    <i class="fas fa-print"></i> Print
                </button>
                <!-- Submission Docs button — hidden until CL is generated -->
                <button id="subDocBtn_${t.sys_id}" class="btn btn-amber" style="display:none;" onclick="genSubDocs('${t.sys_id}','${t.name.replace(/'/g, "\\'")}')">
                    <i class="fas fa-file-zipper"></i> Submission Documents
                </button>
            </div>
        </div>
    </div>
    <input type="file" id="feup_${t.sys_id}" multiple style="display:none;" onchange="feDoUpload('${t.sys_id}',this)">`;
}

// ══ WORD PROCESSOR ════════════════════════════════════
function execCmd(cmd, val = null) {
    document.execCommand(cmd, false, val);
}
function insertTable(tId) {
    const rows = prompt('Number of rows:', '3');
    const cols = prompt('Number of columns:', '3');
    if (!rows || !cols) return;
    let html = '<table><thead><tr>' + Array(+cols).fill('<th>Header</th>').join('') + '</tr></thead><tbody>';
    for (let r = 0; r < +rows - 1; r++) html += '<tr>' + Array(+cols).fill('<td>Cell</td>').join('') + '</tr>';
    html += '</tbody></table><p><br></p>';
    document.execCommand('insertHTML', false, html);
}

// ══ CHECKLIST ═════════════════════════════════════════
function toggleChk(tId, key) {
    const row  = document.getElementById(`chk_${tId}_${key}`);
    const chkb = document.getElementById(`chkb_${tId}_${key}`);
    chkb.checked = !chkb.checked;
    row.classList.toggle('done', chkb.checked);
}
function addCustomChk(tId) {
    const label = prompt('Add checklist item:');
    if (!label) return;
    const key  = 'custom_' + Date.now();
    const cont = document.getElementById(`chklist_${tId}`);
    const div  = document.createElement('div');
    div.className = 'chk-row';
    div.id = `chk_${tId}_${key}`;
    div.onclick = () => toggleChk(tId, key);
    div.innerHTML = `<input type="checkbox" id="chkb_${tId}_${key}" onclick="event.stopPropagation();" onchange="toggleChk('${tId}','${key}')">
        <label for="chkb_${tId}_${key}" style="cursor:pointer;">${label}</label>`;
    cont.appendChild(div);
    showToast('success', 'Item added.');
}
function renderAIChecklist(tId, items) {
    const cont = document.getElementById(`chk_ai_${tId}`);
    if (!cont || !items.length) return;
    cont.innerHTML = `<div style="font-size:.65rem;color:#94a3b8;margin:8px 0 4px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;"><i class="fas fa-wand-magic-sparkles mr-1" style="color:#6366f1;"></i>AI Suggestions</div>`;
    items.forEach((label, i) => {
        const key = `ai_${i}`;
        const div = document.createElement('div');
        div.className = 'chk-row';
        div.id = `chk_${tId}_${key}`;
        div.onclick = () => toggleChk(tId, key);
        div.innerHTML = `<input type="checkbox" id="chkb_${tId}_${key}" onclick="event.stopPropagation();" onchange="toggleChk('${tId}','${key}')">
            <label for="chkb_${tId}_${key}" style="cursor:pointer;color:#6366f1;">${label}</label>`;
        cont.appendChild(div);
    });
}
function getCheckedDocs(tId) {
    const docs = [];
    document.querySelectorAll(`[id^="chkb_${tId}_"]`).forEach(chk => {
        if (chk.checked) {
            const label = chk.parentElement?.querySelector('label');
            if (label) docs.push(label.textContent.trim());
        }
    });
    return docs;
}

// ══ FILE EXPLORER ═════════════════════════════════════
const FE_ICONS = { image:'🖼️', pdf:'📄', archive:'🗜️', document:'📝', spreadsheet:'📊', audio:'🎵', video:'🎬', folder:'📁', file:'📎' };

async function loadFE(tId, path) {
    const grid = document.getElementById(`fegrid_${tId}`);
    const bc   = document.getElementById(`febc_${tId}`);
    if (!grid) return;
    grid.innerHTML = `<div style="grid-column:1/-1;padding:12px;text-align:center;color:#94a3b8;font-size:.74rem;"><i class="fas fa-spinner fa-spin mr-1"></i>Loading...</div>`;
    grid.dataset.path = path; grid.dataset.loaded = '1';
    try {
        const r = await fetch(`${FE_API}?action=list&traveler_id=${tId}&path=${encodeURIComponent(path)}`);
        const j = await r.json();
        if (!j.success) { grid.innerHTML = `<div style="grid-column:1/-1;padding:10px;color:#dc2626;font-size:.73rem;text-align:center;">⚠️ ${j.error ?? 'Error'}</div>`; return; }
        // Breadcrumb
        const parts = path ? path.split('/') : []; let cum = '';
        let bcHTML = `<span class="fe-bc-p" onclick="loadFE('${tId}','')"><i class="fas fa-home"></i></span>`;
        parts.forEach(p => { cum += (cum ? '/' : '') + p; const cp = cum; bcHTML += ` <i class="fas fa-chevron-right" style="font-size:.58rem;color:#cbd5e1;"></i> <span class="fe-bc-p" onclick="loadFE('${tId}','${cp}')">${p}</span>`; });
        bc.innerHTML = bcHTML;
        if (!j.contents.length) { grid.innerHTML = `<div style="grid-column:1/-1;padding:12px;text-align:center;color:#94a3b8;font-size:.73rem;">📂 Empty</div>`; return; }
        grid.innerHTML = j.contents.map(item => {
            const icon = FE_ICONS[item.icon] ?? '📎';
            const oc = item.type === 'folder' ? `loadFE('${tId}','${item.path}')` : `showToast('success','Selected: ${item.name}')`;
            return `<div class="fe-item" onclick="${oc}" title="${item.name}">
                <span class="fe-icon">${icon}</span>
                <span class="fe-name">${item.name}</span>
                <span class="fe-size">${item.size}</span>
            </div>`;
        }).join('');
    } catch { grid.innerHTML = `<div style="grid-column:1/-1;padding:10px;color:#dc2626;font-size:.73rem;text-align:center;">❌ Failed to load</div>`; }
}

function feMkdir(tId) {
    const name = prompt('Folder name:'); if (!name) return;
    const path = document.getElementById(`fegrid_${tId}`)?.dataset.path ?? '';
    fetch(`${FE_API}?action=create_folder&traveler_id=${tId}`, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({ name, path }) })
        .then(r => r.json()).then(j => { j.success ? showToast('success','Folder created.') : showToast('error', j.error ?? 'Failed.'); loadFE(tId, path); });
}
function feUpload(tId) { document.getElementById(`feup_${tId}`)?.click(); }
async function feDoUpload(tId, inp) {
    const path = document.getElementById(`fegrid_${tId}`)?.dataset.path ?? '';
    const fd = new FormData(); fd.append('path', path);
    Array.from(inp.files).forEach(f => fd.append(f.name, f));
    showToast('success', 'Uploading...');
    const r = await fetch(`${FE_API}?action=upload&traveler_id=${tId}`, { method:'POST', body: fd });
    const j = await r.json();
    showToast(j.success ? 'success' : 'error', j.message ?? j.error ?? 'Done.');
    loadFE(tId, path); inp.value = '';
}

// ══ COVER LETTER GENERATION ═══════════════════════════
async function genCL(tId, tName) {
    const client   = selClient?.name  || 'the main applicant';
    const country  = leadData.country || 'the destination country';
    const vType    = leadData.visaType || 'Tourist';
    const entry    = leadData.entry   || 'Single Entry';
    const fromDate = leadData.from    || '';
    const toDate   = leadData.to      || '';
    const embassy  = leadData.embassy || `The ${country} Embassy, Dhaka, Bangladesh`;
    const summary  = document.getElementById('workSummary')?.value || '';
    const checkedDocs = getCheckedDocs(tId);
    const allNames = selTravelers.map(t => t.name).join(', ');

    // Build traveler details for the letter
    const tObj = selTravelers.find(t => t.sys_id === tId) ?? { name: tName, phone: '', type: '' };
    let tPhone = ''; try { if (tObj.phone?.startsWith('{')) tPhone = JSON.parse(tObj.phone).primary_no ?? ''; } catch {}

    const today = new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'long', year: 'numeric' });

    const prompt = `Write a professional visa cover letter using the format below.

DETAILS TO USE:
- Date: ${today}
- Embassy: ${embassy}
- Subject: Application for ${country} ${vType} Visa (${entry})
- Primary Applicant / Client: ${client}
- Traveler Name: ${tName}
- All travelers in group: ${allNames}
- Travel period: ${fromDate || 'TBD'} to ${toDate || 'TBD'}
- Purpose / summary: ${summary || 'Personal trip'}
- Occupation type: ${tObj.type || 'not specified'}
- Phone: ${tPhone || 'not provided'}
- Submitted/checked documents: ${checkedDocs.length ? checkedDocs.join(', ') : 'Standard document set'}

FORMAT TO FOLLOW (adapt content, keep structure):
Date: [Date]
To
The Visa Officer
[Embassy]
Dhaka, Bangladesh

Subject: Application for [Country] [Visa Type] Visa ([Entry Type]).

Dear Sir/Madam,

I, [PRIMARY APPLICANT NAME], a Bangladeshi national, along with my [family/colleagues], respectfully submit this application for a [Visa Type] Visa to visit [Country]. We intend to travel from [From Date] to [To Date] for [purpose].

[If group travel, include an applicant table with columns: Name | Relationship | Passport No | Date of Birth]

[Mention occupation and company if applicable. If employee, mention NOC. If businessman, mention trade license.]

I confirm that I will personally bear all expenses including airfare, accommodation, meals, and local transportation.

[Describe trip purpose briefly.]

I have enclosed all necessary documents including: [list checked docs].

Thank you for your time and consideration.

Sincerely,
[Name]
[Title if applicable]
[Phone] [Email]

Return ONLY the complete letter text, properly formatted with line breaks. No extra commentary.`;

    appendMsg('user', `Generating cover letter for ${tName} — ${vType} visa to ${country}`);
    callAI(prompt, reply => {
        // Set content in editable div
        const editor = document.getElementById(`clEditor_${tId}`);
        if (editor) {
            editor.innerHTML = reply.replace(/\n\n/g, '</p><p>').replace(/\n/g, '<br>');
            if (!editor.innerHTML.startsWith('<p>')) editor.innerHTML = '<p>' + editor.innerHTML + '</p>';
        }
        // Mark CL as generated and show Submission Docs button
        clGenerated[tId] = true;
        const subBtn = document.getElementById(`subDocBtn_${tId}`);
        if (subBtn) subBtn.style.display = '';
        showToast('success', `Cover letter ready for ${tName}`);
    });
}

// ══ SUBMISSION DOCUMENTS ══════════════════════════════
async function genSubDocs(tId, tName) {
    const editor = document.getElementById(`clEditor_${tId}`);
    const clText = editor ? editor.innerText : '(not generated)';
    const country = leadData.country || 'destination';
    const vType   = leadData.visaType || 'Tourist';
    const checked = getCheckedDocs(tId);

    const prompt = `Create a formal visa submission document checklist for ${tName} applying for ${vType} visa to ${country}.

Checked/available documents: ${checked.length ? checked.join(', ') : 'Standard set'}

Cover letter summary: ${clText.substring(0, 400)}...

Generate a numbered submission document list maintaining this serial order:
1. Cover Letter
2. NOC (if employee)
3. Travel History / Previous Visa Copies
4. Trade License (if businessman) / Office ID (if employee)
5. Visiting Card (if available)
6. TIN/BIN Certificate
7. Tax Return / Tax Assessment
8–onwards: any additional documents from the checked list

For each item: Serial | Document Name | Status (Provided / Pending) | Brief Note
Return as a clean formatted list.`;

    appendMsg('user', `Generating submission documents list for ${tName}`);
    callAI(prompt, reply => {
        const win = window.open('', '_blank');
        win.document.write(`<html><head><title>Submission Documents — ${tName}</title>
            <style>body{font-family:Georgia,serif;padding:40px;max-width:780px;margin:0 auto;line-height:1.8;color:#1e293b;}
            h2{color:#1e293b;font-size:1.2rem;margin-bottom:4px;}h3{color:#475569;font-size:.95rem;margin-bottom:16px;}
            hr{border:none;border-top:1px solid #e2e8f0;margin:16px 0;}pre{white-space:pre-wrap;font-family:Georgia,serif;font-size:.9rem;}</style></head>
            <body><h2>Submission Documents</h2><h3>${tName} — ${vType} Visa to ${country}</h3><hr><pre>${reply}</pre></body></html>`);
        win.document.close();
        showToast('success', 'Submission doc list opened in new window.');
    });
}

// ══ COPY + PRINT ══════════════════════════════════════
function copyText(tId) {
    const editor = document.getElementById(`clEditor_${tId}`);
    navigator.clipboard.writeText(editor ? editor.innerText : '')
        .then(() => showToast('success', 'Copied to clipboard!'));
}
function printCL(tId) {
    const editor = document.getElementById(`clEditor_${tId}`);
    const html   = editor ? editor.innerHTML : '';
    const win    = window.open('', '_blank');
    win.document.write(`<html><head><style>body{font-family:'Times New Roman',serif;padding:50px;max-width:720px;margin:0 auto;line-height:1.9;font-size:14px;color:#000;}table{border-collapse:collapse;width:100%;}td,th{border:1px solid #ccc;padding:5px 8px;}</style></head><body>${html}</body></html>`);
    win.document.close(); win.print();
}

// ══ AI CHATBOT (Gemini proxy) ══════════════════════════
function setupAIInp() {
    document.getElementById('aiInp').addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 70) + 'px';
    });
}

async function sendAI() {
    const inp = document.getElementById('aiInp');
    const msg = inp.value.trim(); if (!msg) return;
    inp.value = ''; inp.style.height = 'auto';
    appendMsg('user', msg);

    // Check if it's a cover letter or checklist trigger from the chat
    const isClPrompt = /cover letter/i.test(msg);
    const isChkPrompt = /checklist|requirements|documents needed/i.test(msg);

    callAI(msg, reply => {
        // If user typed a cover letter request manually, inject into active traveler's editor
        if (isClPrompt && selTravelers.length) {
            const active = selTravelers.find(t => {
                const p = document.getElementById(`tp_${t.sys_id}`);
                return p && p.style.display !== 'none';
            }) ?? selTravelers[0];
            if (active) {
                const editor = document.getElementById(`clEditor_${active.sys_id}`);
                if (editor) {
                    editor.innerHTML = reply.replace(/\n\n/g, '</p><p>').replace(/\n/g, '<br>');
                    clGenerated[active.sys_id] = true;
                    const subBtn = document.getElementById(`subDocBtn_${active.sys_id}`);
                    if (subBtn) subBtn.style.display = '';
                    showToast('success', 'Cover letter generated from chat.');
                }
            }
        }
        // If checklist response, inject into all checklists
        if (isChkPrompt) {
            const lines = reply.split('\n').filter(l => /^\d+\./.test(l.trim()));
            const items = lines.map(l => l.replace(/^\d+\.\s*/, '').replace(/\*\*/g, '').trim()).filter(Boolean);
            if (items.length) {
                window._aiChkItems = items;
                selTravelers.forEach(t => renderAIChecklist(t.sys_id, items));
            }
        }
    });
}

async function callAI(msg, cb) {
    const el = appendMsg('loading', '<i class="fas fa-circle-notch fa-spin"></i> Thinking...');
    const sys = `You are an AI assistant in TravHub, a travel agency system. Work: ${WT}. Country: ${leadData.country || 'unknown'}. Visa: ${leadData.visaType || 'unknown'}. Client: ${selClient?.name || 'unknown'}. Travelers: ${selTravelers.map(t => t.name).join(', ') || 'none'}. Be concise, professional, and practical.`;
    aiHistory.push({ role: 'user', content: msg });
    try {
        const r = await fetch(AI_API, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({ message: msg, history: aiHistory.slice(-10), system: sys }) });
        const j = await r.json();
        const reply = j.success ? j.reply : '⚠️ ' + (j.message ?? 'Error.');
        aiHistory.push({ role: 'assistant', content: reply });
        el.remove(); appendMsg('bot', reply);
        if (cb) cb(reply);
    } catch { el.remove(); appendMsg('bot', '⚠️ Network error.'); }
}

function appendMsg(role, text) {
    const c  = document.getElementById('aiMessages');
    const el = document.createElement('div');
    el.className = `ai-msg ${role}`;
    el.innerHTML = text.replace(/\n/g, '<br>');
    c.appendChild(el); c.scrollTop = c.scrollHeight; return el;
}

// ══ HELPERS ═══════════════════════════════════════════
function safeJ(v) { if (!v) return null; if (typeof v === 'object') return v; try { return JSON.parse(v); } catch { return null; } }
function showToast(type, msg) {
    document.getElementById('toastMsg').textContent = msg;
    document.getElementById('toastIn').style.background = type === 'success' ? '#15803d' : '#b91c1c';
    document.getElementById('toastIco').className = `fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}`;
    document.getElementById('toast').style.display = 'block';
    setTimeout(() => document.getElementById('toast').style.display = 'none', 3200);
}
</script>
</body>
</html>