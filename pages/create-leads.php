<?php
// FILE PATH: /pages/create-leads.php
include_once('./authenticate.php');
$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) { $ip_port = "http://103.104.219.3:898"; }

// Check if we're in edit mode
$lead_id = isset($_GET['id']) ? $_GET['id'] : '';
$isEditMode = !empty($lead_id);
$pageTitle = $isEditMode ? 'Lead Edit' : 'Lead Generate';

// API endpoints
$leadStoreApi = $isEditMode ? $ip_port . "api/leads/update.php?lead=$lead_id" : $ip_port . "api/leads/store.php";
$getLeadApi = $isEditMode ? $ip_port . "api/leads/edit.php?lead=$lead_id" : '';
$getAllClientsApi = $ip_port . "api/clients/all-clients.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png" sizes="16x16">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .step-panel { display: none; }
        .step-panel.active { display: block; }

        .service-card { transition: all .2s; cursor: pointer; border: 2px solid #e5e7eb; border-radius: 12px; }
        .service-card:hover { transform: translateY(-2px); border-color: #a5b4fc; }
        .service-card.selected { border-color: #6366f1; background: #eef2ff; }
        .service-card.selected .service-icon { color: #6366f1; }

        .qa-block { animation: fadeUp .2s ease; }
        @keyframes fadeUp { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }

        .answer-card {
            border: 2px solid #e5e7eb; border-radius: 10px;
            padding: 9px 14px; cursor: pointer; transition: all .15s;
            display: inline-flex; align-items: center; gap: 8px;
            background: #fff; font-size: .85rem;
        }
        .answer-card:hover  { border-color: #a5b4fc; background: #f5f3ff; }
        .answer-card.picked { border-color: #6366f1; background: #eef2ff; color: #4338ca; font-weight: 600; }
        .radio-dot {
            width: 16px; height: 16px; border-radius: 50%;
            border: 2px solid #d1d5db; flex-shrink: 0; transition: all .15s;
            display: flex; align-items: center; justify-content: center;
        }
        .answer-card.picked .radio-dot { border-color: #6366f1; background: #6366f1; }
        .answer-card.picked .radio-dot::after { content:''; width:5px; height:5px; border-radius:50%; background:#fff; }

        .qa-input { width:100%; padding:9px 14px; border:2px solid #e5e7eb; border-radius:10px; font-size:.875rem; transition:border .15s; }
        .qa-input:focus { outline:none; border-color:#6366f1; }

        .svc-tab { padding:8px 16px; border-bottom:2px solid transparent; cursor:pointer; font-size:.83rem; font-weight:500; color:#6b7280; transition:all .15s; white-space:nowrap; }
        .svc-tab.active { border-color:#6366f1; color:#6366f1; }

        .step-dot { width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:.75rem; font-weight:700; transition:all .3s; }
        .progress-fill { transition: width .4s ease; }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        .loading-overlay.hidden { display: none; }

        /* Meta info styling */
        .meta-info {
            background: #f8f7ff;
            border-radius: 8px;
            padding: 8px 14px;
            font-size: 0.7rem;
            color: #6b7280;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 4px;
        }
        .meta-info .label { font-weight: 500; color: #4b5563; }
        .meta-info .value { color: #1f2937; }
    </style>
</head>
<body class="bg-gray-50">

<?php include '../elements/header.php'; ?>
<?php include '../elements/aside.php'; ?>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="loading-overlay <?php echo $isEditMode ? '' : 'hidden'; ?>">
    <div class="text-center">
        <div class="w-12 h-12 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin mx-auto"></div>
        <p class="mt-3 text-gray-600 text-sm">Loading lead data...</p>
    </div>
</div>

<main id="mainContent" class="pt-16 pl-16 mt-16 transition-all duration-300">
<div class="p-6 max-w-4xl mx-auto">

    <!-- Page Header -->
    <div class="mb-5 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-800">
                <i class="fas <?php echo $isEditMode ? 'fa-edit' : 'fa-plus-circle'; ?> mr-2 text-indigo-500"></i>
                <?php echo $pageTitle; ?>
            </h1>
            <p class="text-gray-400 text-xs mt-0.5">
                <?php echo $isEditMode ? 'Update lead information' : 'Answer questions step by step to create a lead'; ?>
            </p>
            <!-- Meta info will be inserted here by JS in edit mode -->
            <div id="metaInfoContainer"></div>
        </div>
        <a href="index-leads.php" class="flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg text-sm transition">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

    <!-- Progress -->
    <div class="bg-white rounded-xl shadow-sm p-4 mb-4">
        <div class="flex items-center justify-between mb-2">
            <span id="stepLabel" class="text-sm font-medium text-gray-600">Step 1 of 3 — Client Information</span>
            <span id="stepPct" class="text-sm font-semibold text-indigo-600">33%</span>
        </div>
        <div class="w-full bg-gray-100 rounded-full h-1.5 mb-3">
            <div id="progressBar" class="progress-fill bg-indigo-500 h-1.5 rounded-full" style="width:33%"></div>
        </div>
        <div class="flex justify-between">
            <div class="flex items-center gap-2">
                <div id="dot1" class="step-dot bg-indigo-500 text-white">1</div>
                <span class="text-xs font-medium text-indigo-600">Client Info</span>
            </div>
            <div class="flex items-center gap-2">
                <div id="dot2" class="step-dot bg-gray-200 text-gray-500">2</div>
                <span class="text-xs text-gray-400">Service Types</span>
            </div>
            <div class="flex items-center gap-2">
                <div id="dot3" class="step-dot bg-gray-200 text-gray-500">3</div>
                <span class="text-xs text-gray-400">Details</span>
            </div>
        </div>
    </div>

    <!-- STEP 1: CLIENT INFO -->
    <div id="step1" class="step-panel active">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-base font-semibold text-gray-800 mb-4"><i class="fas fa-user mr-2 text-indigo-400"></i>Client Information</h2>

            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">Search Existing Client</label>
                <div class="grid grid-cols-4 gap-2">
                    <div id="clientSearchContainer" class="relative col-span-3">
                        <input type="text" id="clientInput" placeholder="Search by name or ID..."
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" autocomplete="off">
                        <ul id="clientDropdown" class="absolute w-full bg-white border border-gray-300 rounded-lg mt-1 max-h-52 overflow-auto shadow-xl hidden z-50 text-sm"></ul>
                    </div>
                    <div class="flex gap-2">
                        <a href="./create-client.php" target="_blank" class="flex-1 flex items-center justify-center bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm" title="Add Client"><i class="fas fa-plus"></i></a>
                        <button onclick="loadClients()" class="flex-1 flex items-center justify-center bg-gray-100 hover:bg-gray-200 text-gray-600 border border-gray-300 rounded-lg text-sm" title="Refresh"><i class="fas fa-rotate-right"></i></button>
                    </div>
                </div>
                <div id="selectedClientBadge" class="hidden mt-3 flex items-center gap-2 bg-indigo-50 border border-indigo-200 rounded-lg px-4 py-2">
                    <div class="w-8 h-8 bg-indigo-600 rounded-full text-white flex items-center justify-center font-bold text-sm" id="selAvatar">?</div>
                    <div><div class="font-medium text-gray-800 text-sm" id="selName">—</div><div class="text-xs text-gray-400" id="selMeta">—</div></div>
                    <button onclick="clearClient()" class="ml-auto text-gray-300 hover:text-red-500 transition"><i class="fas fa-times"></i></button>
                </div>
            </div>

            <div class="flex items-center gap-3 my-4">
                <div class="flex-1 border-t border-dashed border-gray-200"></div>
                <span class="text-xs text-gray-400">OR fill manually</span>
                <div class="flex-1 border-t border-dashed border-gray-200"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                    <input type="text" id="clientName" placeholder="e.g. Shakil Ahmed" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="text" id="clientPhone" placeholder="e.g. 01700000000" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" id="clientEmail" placeholder="e.g. client@email.com" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Lead Source</label>
                    <select id="leadSource" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="">Select source</option>
                        <option value="walk_in">Walk-in</option>
                        <option value="referral">Referral</option>
                        <option value="facebook">Facebook</option>
                        <option value="website">Website</option>
                        <option value="phone_call">Phone Call</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea id="leadNotes" rows="2" placeholder="Any initial notes..." class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none resize-none"></textarea>
                </div>
            </div>

            <div class="mt-5 flex justify-end">
                <button onclick="goToStep2()" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition flex items-center gap-2">Next <i class="fas fa-arrow-right"></i></button>
            </div>
        </div>
    </div>

    <!-- STEP 2: SERVICE TYPE SELECTION -->
    <div id="step2" class="step-panel">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-base font-semibold text-gray-800 mb-1"><i class="fas fa-layer-group mr-2 text-indigo-400"></i>Select Service Type(s)</h2>
            <p class="text-xs text-gray-400 mb-5">Each selected service will be stored as a separate lead entry.</p>
            <div class="grid grid-cols-3 md:grid-cols-6 gap-3">
                <div class="service-card p-4 text-center" data-service="visa" onclick="toggleService('visa')">
                    <i class="service-icon fas fa-passport text-2xl text-gray-400 mb-2 block"></i>
                    <div class="text-xs font-semibold text-gray-600">Visa</div>
                    <div class="mt-2 hidden check-mark text-indigo-500 text-sm"><i class="fas fa-check-circle"></i></div>
                </div>
                <div class="service-card p-4 text-center" data-service="hotel" onclick="toggleService('hotel')">
                    <i class="service-icon fas fa-hotel text-2xl text-gray-400 mb-2 block"></i>
                    <div class="text-xs font-semibold text-gray-600">Hotel</div>
                    <div class="mt-2 hidden check-mark text-indigo-500 text-sm"><i class="fas fa-check-circle"></i></div>
                </div>
                <div class="service-card p-4 text-center" data-service="air" onclick="toggleService('air')">
                    <i class="service-icon fas fa-plane text-2xl text-gray-400 mb-2 block"></i>
                    <div class="text-xs font-semibold text-gray-600">Air Ticket</div>
                    <div class="mt-2 hidden check-mark text-indigo-500 text-sm"><i class="fas fa-check-circle"></i></div>
                </div>
                <div class="service-card p-4 text-center" data-service="tour" onclick="toggleService('tour')">
                    <i class="service-icon fas fa-suitcase-rolling text-2xl text-gray-400 mb-2 block"></i>
                    <div class="text-xs font-semibold text-gray-600">Tour Package</div>
                    <div class="mt-2 hidden check-mark text-indigo-500 text-sm"><i class="fas fa-check-circle"></i></div>
                </div>
                <div class="service-card p-4 text-center" data-service="umrah" onclick="toggleService('umrah')">
                    <i class="service-icon fas fa-kaaba text-2xl text-gray-400 mb-2 block"></i>
                    <div class="text-xs font-semibold text-gray-600">Umrah</div>
                    <div class="mt-2 hidden check-mark text-indigo-500 text-sm"><i class="fas fa-check-circle"></i></div>
                </div>
                <div class="service-card p-4 text-center" data-service="transport" onclick="toggleService('transport')">
                    <i class="service-icon fas fa-bus text-2xl text-gray-400 mb-2 block"></i>
                    <div class="text-xs font-semibold text-gray-600">Transport</div>
                    <div class="mt-2 hidden check-mark text-indigo-500 text-sm"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
            <div id="serviceError" class="hidden mt-3 text-xs text-red-500"><i class="fas fa-exclamation-circle mr-1"></i>Please select at least one service.</div>
            <div class="mt-5 flex justify-between">
                <button onclick="goToStep(1)" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg text-sm font-medium transition flex items-center gap-2"><i class="fas fa-arrow-left"></i> Back</button>
                <button onclick="goToStep3()" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition flex items-center gap-2">Next <i class="fas fa-arrow-right"></i></button>
            </div>
        </div>
    </div>

    <!-- STEP 3: CONVERSATIONAL Q&A -->
    <div id="step3" class="step-panel">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-base font-semibold text-gray-800 mb-4"><i class="fas fa-comments mr-2 text-indigo-400"></i>Service Details</h2>
            <div id="svcTabsNav" class="flex border-b mb-6 gap-1 flex-wrap overflow-x-auto"></div>
            <div id="qaContainer"></div>
            <div class="mt-6 flex justify-between">
                <button onclick="goToStep(2)" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg text-sm font-medium transition flex items-center gap-2"><i class="fas fa-arrow-left"></i> Back</button>
                <button onclick="submitLeads()" id="submitBtn" class="px-8 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition flex items-center gap-2">
                    <i class="fas <?php echo $isEditMode ? 'fa-sync' : 'fa-save'; ?>"></i> <?php echo $isEditMode ? 'Update Lead' : 'Save Lead(s)'; ?>
                </button>
            </div>
        </div>
    </div>

</div>
</main>

<!-- Toast -->
<div id="toast" class="fixed bottom-6 right-6 z-50 hidden">
    <div id="toastInner" class="flex items-center gap-3 px-5 py-3 rounded-xl shadow-lg text-white text-sm font-medium">
        <i id="toastIcon" class="fas fa-check-circle text-lg"></i>
        <span id="toastMsg"></span>
    </div>
</div>

<?php include '../elements/floating-menus.php'; ?>
<script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>
<script>
const IS_EDIT_MODE = <?php echo json_encode($isEditMode); ?>;
const LEAD_STORE_API = "<?php echo $leadStoreApi; ?>";
const GET_CLIENTS_API = "<?php echo $getAllClientsApi; ?>";
const GET_LEAD_API = "<?php echo $getLeadApi; ?>";

// ─── Q&A definitions per service ─────────────────────
const SQ = {
    visa: [
        { id:'country',     q:'Which country are they applying for?',
          type:'cards', opts:['UAE','Saudi Arabia','United Kingdom','United States','Canada','Australia','Schengen','Malaysia','Singapore','Thailand','Japan','South Korea','Turkey','Other'] },
        { id:'category',    q:'What type of visa?',
          type:'cards', opts:['Tourist','Business','Student','Work','Medical','Transit','Family / Dependent'] },
        { id:'subtype',     q:'Single or multiple entry?',
          type:'cards', opts:['Single Entry','Multiple Entry','Long Term','Short Term'] },
        { id:'app_type',    q:'Who is this application for?',
          type:'cards', opts:['Single Person','Couple','Family','Group'] },
        { id:'count',       q:'How many applicants?',         type:'number' },
        { id:'travel_date', q:'Planned travel date?',         type:'date' },
        { id:'return_date', q:'Return date?',                 type:'date',   opt:true },
        { id:'urgency',     q:'How urgent is this?',
          type:'cards', opts:['Normal','Urgent','Express'] },
        { id:'notes',       q:'Any special requirements?',    type:'text',   opt:true },
    ],
    hotel: [
        { id:'country',   q:'Which country?',
          type:'cards', opts:['Bangladesh','UAE','Saudi Arabia','Thailand','Malaysia','Singapore','Turkey','Maldives','India','Other'] },
        { id:'city',      q:'Which city or area?',            type:'input' },
        { id:'category',  q:'What hotel category?',
          type:'cards', opts:['3 Star','4 Star','5 Star','Budget / Economy','Any'] },
        { id:'checkin',   q:'Check-in date?',                 type:'date' },
        { id:'checkout',  q:'Check-out date?',                type:'date' },
        { id:'rooms',     q:'How many rooms?',                type:'number' },
        { id:'guests',    q:'How many guests?',               type:'number' },
        { id:'room_type', q:'Room type preference?',
          type:'cards', opts:['Single','Double','Twin','Triple','Suite','Any'] },
        { id:'meal',      q:'Meal plan?',
          type:'cards', opts:['Room Only','Bed & Breakfast','Half Board','Full Board','All Inclusive'] },
        { id:'notes',     q:'Special requirements?',          type:'text',   opt:true },
    ],
    air: [
        { id:'trip_type', q:'What type of trip?',
          type:'cards', opts:['One Way','Round Trip','Multi-City'] },
        { id:'from',      q:'Flying from (origin)?',          type:'input' },
        { id:'to',        q:'Flying to (destination)?',       type:'input' },
        { id:'depart',    q:'Departure date?',                type:'date' },
        { id:'return',    q:'Return date?',                   type:'date',   opt:true },
        { id:'class',     q:'Cabin class?',
          type:'cards', opts:['Economy','Premium Economy','Business','First Class'] },
        { id:'adults',    q:'Number of adults?',              type:'number' },
        { id:'children',  q:'Number of children (age 2–11)?', type:'number' },
        { id:'airline',   q:'Preferred airline?',             type:'input',  opt:true },
        { id:'notes',     q:'Special requirements?',          type:'text',   opt:true },
    ],
    tour: [
        { id:'destination', q:'Where do they want to go?',   type:'input' },
        { id:'type',        q:'Type of tour package?',
          type:'cards', opts:['Honeymoon','Family','Group','Adventure','Corporate','Custom'] },
        { id:'nights',      q:'How many nights?',            type:'number' },
        { id:'depart',      q:'Departure date?',             type:'date' },
        { id:'pax',         q:'Number of travelers?',        type:'number' },
        { id:'flight',      q:'Include flight?',
          type:'cards', opts:['Yes','No','Not decided yet'] },
        { id:'hotel',       q:'Include hotel?',
          type:'cards', opts:['Yes','No','Not decided yet'] },
        { id:'budget',      q:'Budget range (BDT)?',
          type:'cards', opts:['Under 50,000','50,000 – 1,00,000','1,00,000 – 2,00,000','2,00,000+','Flexible'] },
        { id:'notes',       q:'Special requirements?',       type:'text',   opt:true },
    ],
    umrah: [
        { id:'package_type',  q:'What type of Umrah package?',
          type:'cards', opts:['Economy','Standard','Premium','VIP','Custom'] },
        { id:'duration',      q:'How many days?',
          type:'cards', opts:['7 Days','10 Days','14 Days','21 Days','Custom'] },
        { id:'depart',        q:'Preferred departure date?', type:'date' },
        { id:'pax',           q:'Number of pilgrims?',       type:'number' },
        { id:'hotel_makkah',  q:'Hotel category in Makkah?',
          type:'cards', opts:['3 Star','4 Star','5 Star','Any'] },
        { id:'hotel_madinah', q:'Hotel category in Madinah?',
          type:'cards', opts:['3 Star','4 Star','5 Star','Any'] },
        { id:'visa',          q:'Visa included?',
          type:'cards', opts:['Yes','No'] },
        { id:'flight',        q:'Flight included?',
          type:'cards', opts:['Yes','No'] },
        { id:'notes',         q:'Special requirements?',     type:'text',   opt:true },
    ],
    transport: [
        { id:'type',     q:'What type of transport?',
          type:'cards', opts:['Car / SUV','Microbus','Bus / Coach','Airport Transfer','City Tour','Other'] },
        { id:'from',     q:'Pick-up location?',              type:'input' },
        { id:'to',       q:'Drop-off location?',             type:'input' },
        { id:'date',     q:'Travel date?',                   type:'date' },
        { id:'pax',      q:'Number of passengers?',          type:'number' },
        { id:'duration', q:'Duration needed?',
          type:'cards', opts:['One Way','Full Day','Half Day','Multiple Days'] },
        { id:'notes',    q:'Special requirements?',          type:'text',   opt:true },
    ],
};

const SVC_LABELS = {
    visa:      { icon:'fa-passport',         label:'Visa' },
    hotel:     { icon:'fa-hotel',            label:'Hotel' },
    air:       { icon:'fa-plane',            label:'Air Ticket' },
    tour:      { icon:'fa-suitcase-rolling', label:'Tour Package' },
    umrah:     { icon:'fa-kaaba',            label:'Umrah' },
    transport: { icon:'fa-bus',              label:'Transport' },
};

// ─── State ────────────────────────────────────────────
let selectedServices  = new Set();
let qaAnswers         = {};
let selectedClientObj = null;
let clientsData       = [];
let leadData          = null;

// ─── Client Search ────────────────────────────────────
const clientInput    = document.getElementById('clientInput');
const clientDropdown = document.getElementById('clientDropdown');
const clientCont     = document.getElementById('clientSearchContainer');

function loadClients() {
    fetch(GET_CLIENTS_API).then(r=>r.json()).then(d=>{ clientsData = d.clients??[]; }).catch(()=>{});
}
let cTimer;
clientInput.addEventListener('input', ()=>{
    clearTimeout(cTimer);
    cTimer = setTimeout(()=>{
        const v = clientInput.value.toLowerCase().trim();
        const f = v ? clientsData.filter(c=>c.name?.toLowerCase().includes(v)||c.sys_id?.toLowerCase().includes(v)) : clientsData;
        renderClientDropdown(f);
        clientDropdown.classList.remove('hidden');
    }, 300);
});
clientInput.addEventListener('focus', ()=>{ renderClientDropdown(clientsData); clientDropdown.classList.remove('hidden'); });
document.addEventListener('click', e=>{ if (!clientCont.contains(e.target)) clientDropdown.classList.add('hidden'); });

function renderClientDropdown(list) {
    clientDropdown.innerHTML = '';
    if (!list.length) { clientDropdown.innerHTML=`<li class="px-4 py-3 text-center text-gray-400 text-xs">No clients found</li>`; return; }
    list.forEach(c => {
        let phone='';
        try { if(c.phone?.startsWith('{')) phone=JSON.parse(c.phone).primary_no??''; } catch{}
        const li = document.createElement('li');
        li.className = 'px-4 py-2.5 cursor-pointer hover:bg-indigo-50 border-b last:border-b-0 text-sm';
        li.innerHTML = `<div class="flex items-center gap-2">
            <div class="w-7 h-7 bg-indigo-600 rounded-full text-white flex items-center justify-center font-bold text-xs">${c.name?.[0]?.toUpperCase()??'C'}</div>
            <div><div class="font-medium text-gray-800">${c.name}</div><div class="text-xs text-gray-400">ID: ${c.sys_id}${phone?' · '+phone:''}</div></div>
        </div>`;
        li.onclick = () => {
            selectedClientObj = c;
            clientInput.value = `${c.sys_id} | ${c.name}`;
            document.getElementById('clientName').value  = c.name??'';
            document.getElementById('clientPhone').value = phone;
            document.getElementById('clientEmail').value = c.email??'';
            document.getElementById('selectedClientBadge').classList.remove('hidden');
            document.getElementById('selAvatar').textContent = c.name?.[0]?.toUpperCase()??'C';
            document.getElementById('selName').textContent   = c.name;
            document.getElementById('selMeta').textContent   = `ID: ${c.sys_id}${phone?' · '+phone:''}`;
            clientDropdown.classList.add('hidden');
        };
        clientDropdown.appendChild(li);
    });
}
function clearClient() {
    selectedClientObj=null; clientInput.value='';
    document.getElementById('selectedClientBadge').classList.add('hidden');
}
loadClients();

// ─── Load Lead Data (Edit Mode) ─────────────────────
if (IS_EDIT_MODE) {
    document.getElementById('loadingOverlay').classList.remove('hidden');
    
    fetch(GET_LEAD_API)
        .then(res => res.json())
        .then(response => {
            if (response.status === 'success') {
                leadData = response.data;
                populateLeadData(leadData);
            } else {
                showToast('error', 'Failed to load lead data: ' + response.message);
            }
        })
        .catch(err => {
            showToast('error', 'Error loading lead data');
            console.error(err);
        })
        .finally(() => {
            document.getElementById('loadingOverlay').classList.add('hidden');
        });
}

function populateLeadData(data) {
    // Populate client info
    const clientInfo = data.client_info || {};
    document.getElementById('clientName').value = clientInfo.name || '';
    document.getElementById('clientPhone').value = clientInfo.phone || '';
    document.getElementById('clientEmail').value = clientInfo.email || '';
    
    // Populate lead info
    const leadInfo = data.lead_info || {};
    document.getElementById('leadSource').value = leadInfo.source || '';
    document.getElementById('leadNotes').value = leadInfo.notes || '';
    
    // If client has sys_id, show badge
    if (clientInfo.sys_id) {
        selectedClientObj = {
            sys_id: clientInfo.sys_id,
            name: clientInfo.name,
            phone: clientInfo.phone,
            email: clientInfo.email
        };
        clientInput.value = `${clientInfo.sys_id} | ${clientInfo.name}`;
        document.getElementById('selectedClientBadge').classList.remove('hidden');
        document.getElementById('selAvatar').textContent = clientInfo.name?.[0]?.toUpperCase() || 'C';
        document.getElementById('selName').textContent = clientInfo.name;
        document.getElementById('selMeta').textContent = `ID: ${clientInfo.sys_id}${clientInfo.phone ? ' · '+clientInfo.phone : ''}`;
    }
    
    // Populate services
    const serviceTypes = data.service_type || [];
    serviceTypes.forEach(svc => {
        toggleService(svc);
    });
    
    // Populate service data
    const serviceData = data.service_data || {};
    Object.keys(serviceData).forEach(svc => {
        qaAnswers[svc] = serviceData[svc] || {};
    });
    
    // Show meta data info
    const metaData = data.meta_data || {};
    let metaHtml = '';
    if (metaData.created_by_date) {
        metaHtml += `<span class="label">Created:</span> <span class="value">${metaData.created_by_date.date} by ${metaData.created_by_date.user}</span>`;
    }
    if (metaData.updated_by_date && metaData.updated_by_date.length > 0) {
        const lastUpdate = metaData.updated_by_date[metaData.updated_by_date.length - 1];
        metaHtml += ` | <span class="label">Last Updated:</span> <span class="value">${lastUpdate.date} by ${lastUpdate.user}</span>`;
        if (metaData.updated_by_date.length > 1) {
            metaHtml += ` | <span class="label">Total Updates:</span> <span class="value">${metaData.updated_by_date.length}</span>`;
        }
    }
    
    if (metaHtml) {
        const container = document.getElementById('metaInfoContainer');
        container.innerHTML = `<div class="meta-info">${metaHtml}</div>`;
    }
    
    // Update step indicator
    goToStep(2);
}

// ─── Steps ────────────────────────────────────────────
function updateProgress(step) {
    const pct = Math.round((step/3)*100);
    document.getElementById('progressBar').style.width = pct+'%';
    document.getElementById('stepPct').textContent = pct+'%';
    const labels = ['Client Information','Service Types','Service Details'];
    document.getElementById('stepLabel').textContent = `Step ${step} of 3 — ${labels[step-1]}`;
    [1,2,3].forEach(i=>{
        const dot = document.getElementById('dot'+i);
        if(i<step)      { dot.className='step-dot bg-green-500 text-white'; dot.innerHTML='<i class="fas fa-check text-xs"></i>'; }
        else if(i===step){ dot.className='step-dot bg-indigo-500 text-white'; dot.textContent=i; }
        else            { dot.className='step-dot bg-gray-200 text-gray-500'; dot.textContent=i; }
    });
}
function goToStep(n) {
    document.querySelectorAll('.step-panel').forEach(p=>p.classList.remove('active'));
    document.getElementById('step'+n).classList.add('active');
    updateProgress(n);
    window.scrollTo({top:0,behavior:'smooth'});
}
function goToStep2() {
    if (!document.getElementById('clientName').value.trim() && !selectedClientObj) {
        showToast('error','Please enter or select a client.'); return;
    }
    goToStep(2);
}
function goToStep3() {
    if (selectedServices.size===0) { document.getElementById('serviceError').classList.remove('hidden'); return; }
    document.getElementById('serviceError').classList.add('hidden');
    buildQA(); goToStep(3);
}

// ─── Service Toggle ───────────────────────────────────
function toggleService(key) {
    const card = document.querySelector(`[data-service="${key}"]`);
    if (selectedServices.has(key)) {
        selectedServices.delete(key);
        card.classList.remove('selected');
        card.querySelector('.check-mark').classList.add('hidden');
    } else {
        selectedServices.add(key);
        card.classList.add('selected');
        card.querySelector('.check-mark').classList.remove('hidden');
    }
}

// ─── Build Q&A ────────────────────────────────────────
function buildQA() {
    const svcs    = [...selectedServices];
    const tabNav  = document.getElementById('svcTabsNav');
    const cont    = document.getElementById('qaContainer');

    svcs.forEach(s=>{ if(!qaAnswers[s]) qaAnswers[s]={}; });

    tabNav.innerHTML = '';
    if (svcs.length > 1) {
        svcs.forEach((s,i)=>{
            const info = SVC_LABELS[s];
            const btn  = document.createElement('button');
            btn.className = `svc-tab ${i===0?'active':''}`;
            btn.innerHTML = `<i class="fas ${info.icon} mr-1"></i>${info.label}`;
            btn.onclick = ()=>switchTab(s,btn);
            tabNav.appendChild(btn);
        });
    }

    cont.innerHTML = svcs.map((s,i)=>`
        <div id="qa_${s}" class="qa-svc-panel ${i===0?'':'hidden'}">
            ${renderQAPanel(s)}
        </div>`).join('');
}

function renderQAPanel(svc) {
    return (SQ[svc]??[]).map((q,idx)=>{
        const saved = qaAnswers[svc]?.[q.id] ?? '';
        let inp = '';

        if (q.type==='cards') {
            const cards = q.opts.map(o=>`
                <button type="button" class="answer-card ${saved===o?'picked':''}"
                    onclick="pickCard('${svc}','${q.id}',this,'${o.replace(/'/g,"\\'")}')">
                    <div class="radio-dot"></div><span>${o}</span>
                </button>`).join('');
            inp = `<div class="flex flex-wrap gap-2 mb-2">${cards}</div>
                   <input type="text" class="qa-input mt-1" placeholder="Other — write here..."
                       id="other_${svc}_${q.id}"
                       value="${saved && !q.opts.includes(saved) ? saved : ''}"
                       oninput="setAns('${svc}','${q.id}',this.value)">`;
        } else if (q.type==='input') {
            inp = `<input type="text" class="qa-input" placeholder="Type your answer..." value="${saved}"
                       oninput="setAns('${svc}','${q.id}',this.value)">`;
        } else if (q.type==='date') {
            inp = `<input type="date" class="qa-input" value="${saved}"
                       onchange="setAns('${svc}','${q.id}',this.value)">`;
        } else if (q.type==='number') {
            inp = `<input type="number" class="qa-input" min="1" value="${saved||1}"
                       oninput="setAns('${svc}','${q.id}',this.value)">`;
        } else if (q.type==='text') {
            inp = `<textarea class="qa-input" rows="2" placeholder="Type here..."
                       oninput="setAns('${svc}','${q.id}',this.value)">${saved}</textarea>`;
        }

        return `<div class="qa-block mb-7">
            <div class="flex items-start gap-3 mb-3">
                <div class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold flex-shrink-0 mt-0.5">${idx+1}</div>
                <div class="font-medium text-gray-800 text-sm leading-snug">${q.q}${q.opt?' <span class="text-gray-400 text-xs font-normal">(optional)</span>':''}</div>
            </div>
            <div class="pl-9">${inp}</div>
        </div>`;
    }).join('');
}

function switchTab(svc, btn) {
    document.querySelectorAll('.qa-svc-panel').forEach(p=>p.classList.add('hidden'));
    document.querySelectorAll('#svcTabsNav .svc-tab').forEach(b=>b.classList.remove('active'));
    document.getElementById('qa_'+svc).classList.remove('hidden');
    btn.classList.add('active');
}

function pickCard(svc, qId, btn, value) {
    btn.closest('.flex').querySelectorAll('.answer-card').forEach(b=>b.classList.remove('picked'));
    btn.classList.add('picked');
    qaAnswers[svc] = qaAnswers[svc]||{};
    qaAnswers[svc][qId] = value;
    const other = document.getElementById(`other_${svc}_${qId}`);
    if (other) other.value = '';
}

function setAns(svc, qId, value) {
    qaAnswers[svc] = qaAnswers[svc]||{};
    qaAnswers[svc][qId] = value;
    // deselect cards when typing in "other"
    const otherEl = document.getElementById(`other_${svc}_${qId}`);
    if (otherEl && document.activeElement === otherEl) {
        const wrapper = otherEl.closest('.pl-9');
        if (wrapper) wrapper.querySelectorAll('.answer-card').forEach(b=>b.classList.remove('picked'));
    }
}

// ─── Submit ────────────────────────────────────────────
async function submitLeads() {
    const name = document.getElementById('clientName').value.trim();
    if (!name) { showToast('error','Client name is required.'); return; }

    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    const loadingText = IS_EDIT_MODE ? 'Updating...' : 'Saving...';
    btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${loadingText}`;

    const clientInfo = {
        sys_id: selectedClientObj?.sys_id ?? null,
        name,
        phone: document.getElementById('clientPhone').value.trim(),
        email: document.getElementById('clientEmail').value.trim(),
    };
    const leadInfo = {
        source: document.getElementById('leadSource').value,
        notes:  document.getElementById('leadNotes').value.trim(),
    };

    const svcs = [...selectedServices];
    let ok = 0, fail = 0;

    if (IS_EDIT_MODE) {
        // Edit mode - update all services at once
        const payload = {
            serviceCount: svcs.length,
            serviceType: svcs,
            clientInfo,
            serviceData: qaAnswers,
            leadInfo,
            leadStatus: leadData?.lead_status || 'pending'
        };
        
        try {
            const res = await fetch(LEAD_STORE_API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            const json = await res.json();
            if (json.status === 'success') {
                showToast('success', 'Lead updated successfully!');
                setTimeout(() => { window.location.href = 'index-leads.php'; }, 1300);
            } else {
                showToast('error', 'Update failed: ' + json.message);
                btn.disabled = false;
                btn.innerHTML = `<i class="fas fa-sync"></i> Update Lead`;
            }
        } catch (err) {
            showToast('error', 'Error updating lead');
            btn.disabled = false;
            btn.innerHTML = `<i class="fas fa-sync"></i> Update Lead`;
        }
    } else {
        // Create mode - create separate lead per service
        for (const svc of svcs) {
            const payload = {
                serviceCount: 1,
                serviceType: [svc],
                clientInfo,
                serviceData: { [svc]: qaAnswers[svc] ?? {} },
                leadInfo,
            };
            try {
                const res = await fetch(LEAD_STORE_API, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                });
                const json = await res.json();
                if (json.status === 'success') ok++;
                else fail++;
            } catch { fail++; }
        }

        if (fail === 0) {
            showToast('success', `${ok} lead${ok > 1 ? 's' : ''} saved successfully!`);
            setTimeout(() => { window.location.href = 'index-leads.php'; }, 1300);
        } else {
            showToast('error', `${ok} saved, ${fail} failed. Please retry.`);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save"></i> Save Lead(s)';
        }
    }
}

// ─── Toast ────────────────────────────────────────────
function showToast(type, msg) {
    const inner = document.getElementById('toastInner');
    document.getElementById('toastMsg').textContent = msg;
    inner.className = `flex items-center gap-3 px-5 py-3 rounded-xl shadow-lg text-white text-sm font-medium ${type==='success'?'bg-green-600':'bg-red-500'}`;
    document.getElementById('toastIcon').className = `fas ${type==='success'?'fa-check-circle':'fa-exclamation-circle'} text-lg`;
    document.getElementById('toast').classList.remove('hidden');
    setTimeout(()=>document.getElementById('toast').classList.add('hidden'), 4000);
}
</script>
</body>
</html>