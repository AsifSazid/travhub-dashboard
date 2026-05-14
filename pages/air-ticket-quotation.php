<?php
include_once('./authenticate.php');
$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) $ip_port = "http://103.104.219.3:898/";
$getAllClientsApi = $ip_port . "api/clients/all-clients.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Air Ticket Quotation</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    body { font-family: 'Inter', system-ui, sans-serif; }
    /* ── Layout ── */
    .editor-layout { display: flex; gap: 1.25rem; align-items: flex-start; }
    .editor-left   { flex: 1; min-width: 0; }
    .editor-right  { width: 300px; flex-shrink: 0; position: sticky; top: 5rem; max-height: calc(100vh - 6rem); display: flex; flex-direction: column; }
    @media (max-width: 1024px) { .editor-layout { flex-direction: column; } .editor-right { width: 100%; position: static; max-height: none; } }
    /* ── Sidebar ── */
    .sidebar-box { background:#fff; border:1px solid #e5e7eb; border-radius:0.875rem; overflow:hidden; display:flex; flex-direction:column; flex:1; min-height:0; }
    .sidebar-head { padding:1rem 1.25rem; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; justify-content:space-between; flex-shrink:0; }
    .sidebar-body { overflow-y:auto; flex:1; padding:0.75rem; }
    .quot-card {
      border:1px solid #e5e7eb; border-radius:0.5rem; padding:0.75rem;
      margin-bottom:0.5rem; cursor:pointer; transition:all 0.15s ease; background:#fafafa;
    }
    .quot-card:hover   { border-color:#94a3b8; background:#fff; }
    .quot-card.active  { border-color:#0f172a; background:#fff; box-shadow:0 0 0 2px rgba(15,23,42,0.08); }
    .quot-card.deleted { opacity:0.4; pointer-events:none; }
    .quot-id-tag { font-size:0.7rem; font-weight:700; color:#6366f1; font-family:monospace; }
    .quot-meta   { font-size:0.72rem; color:#94a3b8; margin-top:0.2rem; }
    /* ── Base UI ── */
    .input {
      width:100%; padding:0.625rem 0.875rem; border:1px solid #e5e7eb;
      border-radius:0.5rem; font-size:0.9rem; color:#0f172a; background:#fff; transition:all 0.15s ease;
    }
    .input:focus    { outline:none; border-color:#0f172a; box-shadow:0 0 0 3px rgba(15,23,42,0.08); }
    .input[readonly]{ background:#f9fafb; color:#6b7280; cursor:default; }
    .label { display:block; font-size:0.8rem; font-weight:600; color:#374151; margin-bottom:0.35rem; }
    .card  { background:#fff; border:1px solid #e5e7eb; border-radius:0.875rem; padding:1.5rem; margin-bottom:1.25rem; }
    .section-title { font-size:1.05rem; font-weight:600; color:#0f172a; margin-bottom:1rem; display:flex; align-items:center; gap:0.625rem; }
    .section-title .num { background:#0f172a; color:#fff; width:1.5rem; height:1.5rem; border-radius:9999px; display:inline-flex; align-items:center; justify-content:center; font-size:0.75rem; font-weight:600; }
    .pill-group { display:flex; flex-wrap:wrap; gap:0.5rem; }
    .pill-group input { display:none; }
    .pill-group label { cursor:pointer; padding:0.55rem 1rem; border:1px solid #e5e7eb; border-radius:9999px; font-size:0.85rem; font-weight:500; color:#374151; transition:all 0.15s ease; user-select:none; background:#fff; }
    .pill-group input:checked + label { background:#0f172a; color:#fff; border-color:#0f172a; }
    .btn-primary  { background:#0f172a; color:#fff; padding:0.65rem 1.25rem; border-radius:0.5rem; font-weight:600; font-size:0.88rem; border:none; cursor:pointer; transition:all 0.15s ease; }
    .btn-primary:hover  { background:#1e293b; }
    .btn-success  { background:#16a34a; color:#fff; padding:0.65rem 1.25rem; border-radius:0.5rem; font-weight:600; font-size:0.88rem; border:none; cursor:pointer; transition:all 0.15s ease; }
    .btn-success:hover  { background:#15803d; }
    .btn-copy     { background:#fff; color:#0f172a; padding:0.65rem 1.25rem; border:1px solid #e5e7eb; border-radius:0.5rem; font-weight:600; font-size:0.88rem; cursor:pointer; transition:all 0.15s ease; }
    .btn-copy:hover:not(:disabled) { background:#f9fafb; border-color:#cbd5e1; }
    .btn-copy:disabled { opacity:0.45; cursor:not-allowed; }
    .btn-secondary{ background:#fff; color:#0f172a; padding:0.5rem 0.95rem; border:1px solid #e5e7eb; border-radius:0.45rem; font-weight:500; font-size:0.85rem; cursor:pointer; transition:all 0.15s ease; }
    .btn-secondary:hover { background:#f9fafb; border-color:#cbd5e1; }
    .btn-warning  { background:#fffbeb; color:#92400e; padding:0.5rem 0.95rem; border:1px solid #fde68a; border-radius:0.45rem; font-weight:500; font-size:0.85rem; cursor:pointer; transition:all 0.15s ease; }
    .btn-warning:hover { background:#fef3c7; }
    .btn-danger-sm{ background:#fef2f2; color:#dc2626; padding:0.35rem 0.7rem; border:1px solid #fecaca; border-radius:0.4rem; font-weight:500; font-size:0.78rem; cursor:pointer; transition:all 0.15s ease; }
    .btn-danger-sm:hover { background:#fee2e2; }
    /* ── Segment & Connection ── */
    .segment-card   { border:1px solid #e5e7eb; border-radius:0.625rem; padding:1rem; margin-bottom:0.875rem; background:#f9fafb; }
    .connection-card{ border:1px dashed #cbd5e1; border-radius:0.5rem; padding:0.875rem; margin-bottom:0.625rem; background:#fff; }
    .transit-row    { background:#fef3c7; border:1px solid #fde68a; padding:0.75rem 1rem; border-radius:0.5rem; margin:0.75rem 0; }
    .price-card     { border:1px solid #e5e7eb; border-radius:0.625rem; padding:1rem; margin-bottom:0.875rem; background:#f9fafb; }
    /* ── UCPT ── */
    .ucpt-image-zone {
      border:2px dashed #cbd5e1; border-radius:0.75rem; padding:1.5rem;
      text-align:center; cursor:pointer; transition:all 0.2s ease; background:#fafafa;
      position:relative;
    }
    .ucpt-image-zone.dragover { border-color:#0f172a; background:#f8fafc; }
    .ucpt-image-zone.has-image{ border-color:#16a34a; background:#f0fdf4; }
    .ucpt-preview-img { max-height:160px; max-width:100%; border-radius:0.5rem; margin:0.5rem auto 0; display:none; }
    .ucpt-divider { display:flex; align-items:center; gap:0.75rem; margin:0.75rem 0; }
    .ucpt-divider::before,.ucpt-divider::after { content:''; flex:1; height:1px; background:#e5e7eb; }
    .ucpt-divider span { font-size:0.78rem; color:#94a3b8; font-weight:500; }
    /* ── Preview ── */
    .preview { width:100%; height:22rem; padding:1rem; border:1px solid #e5e7eb; border-radius:0.5rem; font-family:'Courier New',monospace; font-size:0.82rem; line-height:1.55; resize:vertical; }
    .preview-business { background:#f0fdf4; border-color:#bbf7d0; }
    /* ── Switch ── */
    .switch { position:relative; display:inline-block; width:40px; height:22px; }
    .switch input { opacity:0; width:0; height:0; }
    .slider { position:absolute; cursor:pointer; inset:0; background:#cbd5e1; border-radius:9999px; transition:0.2s; }
    .slider::before { content:""; position:absolute; height:16px; width:16px; left:3px; top:3px; background:#fff; border-radius:50%; transition:0.2s; }
    .switch input:checked + .slider { background:#0f172a; }
    .switch input:checked + .slider::before { transform:translateX(18px); }
    /* ── Save status ── */
    .save-status { display:inline-flex; align-items:center; gap:0.4rem; padding:0.35rem 0.75rem; border-radius:9999px; font-size:0.78rem; font-weight:500; }
    .save-status.unsaved { background:#fef3c7; color:#92400e; }
    .save-status.saved   { background:#d1fae5; color:#065f46; }
    .dot { width:0.5rem; height:0.5rem; border-radius:9999px; }
    .save-status.unsaved .dot { background:#f59e0b; }
    .save-status.saved   .dot { background:#10b981; }
    /* ── Toast ── */
    .toast { position:fixed; top:1.5rem; right:1.5rem; padding:0.85rem 1.15rem; border-radius:0.5rem; color:#fff; font-weight:500; font-size:0.88rem; box-shadow:0 8px 24px -8px rgba(0,0,0,0.2); z-index:9999; transform:translateX(120%); transition:transform 0.3s ease; }
    .toast.show    { transform:translateX(0); }
    .toast.success { background:#16a34a; }
    .toast.error   { background:#dc2626; }
    .toast.info    { background:#0f172a; }
    /* ── Locked notice ── */
    .locked-notice { display:flex; align-items:center; gap:0.4rem; font-size:0.78rem; color:#92400e; background:#fef3c7; border:1px solid #fde68a; border-radius:0.4rem; padding:0.3rem 0.65rem; }
  </style>
</head>
<body class="bg-gray-50 font-sans">

<?php include '../elements/header.php'; ?>
<?php include '../elements/aside.php'; ?>
<?php include '../elements/preview-model.php'; ?>
<?php include '../elements/floating-menus.php'; ?>

<main id="mainContent" class="pt-16 pb-16 pl-64 md:pb-0 md:pl-16 lg:pl-64 transition-all duration-300">
<div class="max-w-screen-2xl mx-auto px-4 py-8">

  <!-- Page header -->
  <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div class="flex items-center gap-3">
      <a href="index-air-ticket-quotation.php" class="text-slate-400 hover:text-slate-700 transition-colors">
        <i class="fas fa-arrow-left"></i>
      </a>
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Air Ticket Quotation</h1>
        <p class="text-sm text-slate-500 mt-0.5" id="pageSubtitle">New document</p>
      </div>
    </div>
    <div id="saveStatus" class="save-status unsaved">
      <span class="dot"></span><span class="status-text">Unsaved</span>
    </div>
  </div>

  <div class="editor-layout">

    <!-- ══════════════════════════════════════════════════════════
         LEFT PANEL — FORM
    ══════════════════════════════════════════════════════════ -->
    <div class="editor-left">

      <!-- OP: UCPT -->
      <div class="card">
        <div class="section-title"><span class="num">OP</span> Screenshot Extraction</div>

        <!-- Image zone -->
        <div id="ucptImageZone" class="ucpt-image-zone" onclick="document.getElementById('fileInput').click()">
          <input type="file" id="fileInput" accept="image/*" class="hidden" onchange="handleFileInput(this)">
          <i class="fas fa-cloud-upload-alt text-3xl text-slate-300 mb-2 block"></i>
          <p class="text-sm font-medium text-slate-600">Drop screenshot here, paste image (Ctrl+V), or click to browse</p>
          <p class="text-xs text-slate-400 mt-1">PNG, JPG, WEBP supported</p>
          <img id="ucptPreview" class="ucpt-preview-img">
        </div>

        <div class="ucpt-divider"><span>OR paste / type text</span></div>

        <!-- Text zone -->
        <textarea id="ucptText" class="input" rows="4"
          placeholder="No screenshot? Paste or type the flight quotation text here..."></textarea>

        <div class="flex gap-2 mt-3">
          <button onclick="extractUCPT()" class="btn-success flex-1">
            <i class="fas fa-magic mr-1.5"></i> Extract & Fill Form
          </button>
          <button onclick="clearUCPT()" class="btn-secondary">
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>

      <!-- 1: Basic Information -->
      <div class="card">
        <div class="flex items-center justify-between mb-4">
          <div class="section-title mb-0"><span class="num">1</span> Basic Information</div>
          <div class="flex items-center gap-2">
            <span id="lockedNotice" class="locked-notice">
              <i class="fas fa-lock text-xs"></i> Locked
            </span>
            <button id="editBasicBtn" onclick="toggleBasicEdit()" class="btn-warning hidden">
              <i class="fas fa-pen text-xs mr-1"></i> Edit
            </button>
            <button id="saveBasicBtn" onclick="saveBasicInfo()" class="btn-success hidden">
              <i class="fas fa-save text-xs mr-1"></i> Save Basic Info
            </button>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="label">Title</label>
            <input id="quoteTitle" class="input" placeholder="e.g. Singapore Tour" readonly>
          </div>
          <div>
            <label class="label">Client</label>
            <!-- Client selector injected — kept unchanged per rule -->
            <div id="clientSearchContainer" class="relative w-full">
              <input type="text" id="clientInput" placeholder="Search for a client..."
                class="input" autocomplete="off" readonly>
              <ul id="clientDropdown"
                class="absolute w-full bg-white border border-gray-300 rounded-lg mt-1 max-h-60 overflow-auto shadow-xl hidden z-50"></ul>
            </div>
          </div>
        </div>
      </div>

      <!-- 2: General Information -->
      <div class="card">
        <div class="section-title"><span class="num">2</span> General Information</div>
        <div class="mb-5">
          <label class="label">Trip Type</label>
          <div class="pill-group">
            <input type="radio" name="trip_option" id="trip_oneway" value="One Way" checked>
            <label for="trip_oneway">One Way Ticket</label>
            <input type="radio" name="trip_option" id="trip_round" value="Round Trip">
            <label for="trip_round">Round Trip Ticket</label>
            <input type="radio" name="trip_option" id="trip_multi" value="Multi City">
            <label for="trip_multi">Multi City Ticket</label>
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
          <div>
            <label class="label">Route</label>
            <input id="route" class="input" placeholder="e.g. DAC - MNL">
          </div>
          <div>
            <label class="label">Class</label>
            <div class="pill-group">
              <input type="radio" name="class" id="cls_eco" value="Economy" checked>
              <label for="cls_eco">Economy</label>
              <input type="radio" name="class" id="cls_biz" value="Business">
              <label for="cls_biz">Business</label>
            </div>
          </div>
        </div>
        <div class="grid grid-cols-3 gap-4">
          <div><label class="label">Adult</label><input id="pax_adult" type="number" min="0" value="1" class="input"></div>
          <div><label class="label">Child</label><input id="pax_child" type="number" min="0" value="0" class="input"></div>
          <div><label class="label">Infant</label><input id="pax_infant" type="number" min="0" value="0" class="input"></div>
        </div>
      </div>

      <!-- 3: Flight Segments -->
      <div class="card">
        <div class="flex items-center justify-between mb-4">
          <div class="section-title mb-0"><span class="num">3</span> Flight Segments</div>
          <button id="add_segment_btn" onclick="addMultiCitySegment(); markUnsaved();" class="btn-secondary hidden">
            + Add Segment
          </button>
        </div>
        <div id="segments"></div>
      </div>

      <!-- 4: Pricing -->
      <div class="card">
        <div class="section-title"><span class="num">4</span> Pricing</div>
        <div class="price-card">
          <div class="mb-3"><label class="label">Baggage Option 1</label><input id="baggage_1_desc" class="input" value="Without Baggage"></div>
          <div class="grid grid-cols-3 gap-4">
            <div><label class="label">Adult (BDT)</label><input id="price_1_adult" type="number" min="0" class="input"></div>
            <div><label class="label">Child (BDT)</label><input id="price_1_child" type="number" min="0" class="input"></div>
            <div><label class="label">Infant (BDT)</label><input id="price_1_infant" type="number" min="0" class="input"></div>
          </div>
        </div>
        <div class="price-card">
          <div class="mb-3"><label class="label">Baggage Option 2</label><input id="baggage_2_desc" class="input" value="With 30 Kg Check-IN + 7 Kg Cabin Baggage"></div>
          <div class="grid grid-cols-3 gap-4">
            <div><label class="label">Adult (BDT)</label><input id="price_2_adult" type="number" min="0" class="input"></div>
            <div><label class="label">Child (BDT)</label><input id="price_2_child" type="number" min="0" class="input"></div>
            <div><label class="label">Infant (BDT)</label><input id="price_2_infant" type="number" min="0" class="input"></div>
          </div>
        </div>
      </div>

      <!-- 5: Conditions & Notes -->
      <div class="card">
        <div class="section-title"><span class="num">5</span> Conditions & Notes</div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
          <div>
            <label class="label">Refundable Status</label>
            <div class="pill-group">
              <input type="radio" name="refundable_status" id="ref_yes" value="Refundable" checked>
              <label for="ref_yes">Refundable</label>
              <input type="radio" name="refundable_status" id="ref_no" value="Non Refundable">
              <label for="ref_no">Non Refundable</label>
            </div>
          </div>
          <div>
            <label class="label">Changeable Status</label>
            <div class="pill-group">
              <input type="radio" name="changeable_status" id="chg_yes" value="Changeable" checked>
              <label for="chg_yes">Changeable</label>
              <input type="radio" name="changeable_status" id="chg_no" value="Not Changeable">
              <label for="chg_no">Not Changeable</label>
            </div>
          </div>
        </div>
        <div class="space-y-2 mb-4">
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" class="default_note w-4 h-4" value="Subject to availability at the time of booking" onchange="markUnsaved()">
            <span class="text-sm text-slate-700">Subject to availability at the time of booking</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" class="default_note w-4 h-4" value="Fare may change without prior notice" onchange="markUnsaved()">
            <span class="text-sm text-slate-700">Fare may change without prior notice</span>
          </label>
        </div>
        <div id="notes" class="mb-3"></div>
        <button onclick="addNote(); markUnsaved();" class="btn-secondary">+ Add Note</button>
      </div>

      <!-- 6: Business Markup -->
      <div class="card">
        <div class="section-title"><span class="num">6</span> Business Markup</div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="label">Business Percentage (%)</label>
            <input id="percentage" type="number" min="0" step="0.01" value="0" class="input" oninput="generateQuotation(); markUnsaved();">
          </div>
          <div>
            <label class="label">Vendor Fixed Price (BDT)</label>
            <input id="ve_fixed_price" type="number" min="0" step="0.01" value="0" class="input" oninput="generateQuotation(); markUnsaved();">
          </div>
        </div>
        <p class="text-xs text-slate-400 mt-3">Formula: <span class="font-mono">final = (base × (1 + %/100)) + fixed</span></p>
      </div>

      <!-- Actions -->
      <div class="flex flex-wrap gap-3 items-center justify-between mb-6">
        <button id="addMoreBtn" onclick="addMoreQuotation()" class="btn-warning hidden">
          <i class="fas fa-plus mr-1.5"></i> Add More Quotation
        </button>
        <div class="flex gap-3 ml-auto">
          <button onclick="generateQuotation()" class="btn-primary">Generate</button>
          <button onclick="saveQuotation()" class="btn-success">Save</button>
          <button id="copyBtn" onclick="copyBusinessQuotation()" class="btn-copy" disabled>Copy</button>
        </div>
      </div>

      <!-- Preview Boxes -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="label flex items-center gap-2">Raw Formatted Quotation <span class="text-xs font-normal text-slate-400">(original prices)</span></label>
          <textarea id="raw_preview" class="preview" readonly></textarea>
        </div>
        <div>
          <label class="label flex items-center gap-2">Business Quotation <span class="text-xs font-normal text-emerald-600">(with markup — to send)</span></label>
          <textarea id="business_preview" class="preview preview-business" readonly></textarea>
        </div>
      </div>

    </div><!-- /editor-left -->

    <!-- ══════════════════════════════════════════════════════════
         RIGHT PANEL — SIDEBAR
    ══════════════════════════════════════════════════════════ -->
    <div class="editor-right" id="editorRight">
      <div class="sidebar-box">
        <div class="sidebar-head">
          <div>
            <p class="font-semibold text-slate-800 text-sm">Saved Quotations</p>
            <p class="text-xs text-slate-400" id="sidebarSubtitle">—</p>
          </div>
          <span id="sidebarCount" class="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full font-semibold">0</span>
        </div>
        <div class="sidebar-body" id="sidebarBody">
          <p class="text-xs text-slate-400 text-center py-8">No quotations saved yet.</p>
        </div>
      </div>
    </div>

  </div><!-- /editor-layout -->
</div>
</main>

<div id="toast" class="toast"></div>

<script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>
<script>
// ════════════════════════════════════════════════════════════════
// STATE
// ════════════════════════════════════════════════════════════════
const API_BASE = '<?php echo rtrim($ip_port, "/"); ?>/';
const STORE_API = API_BASE + 'api/tickets/store-quotation.php';
const GET_API   = API_BASE + 'api/tickets/get-quotation.php';
const EXTRACT_API = API_BASE + 'api/tickets/ss-extraction.php';

const urlParams = new URLSearchParams(window.location.search);
const PAGE_SYS_ID = urlParams.get('sys_id') || null;

const state = {
  sysId: PAGE_SYS_ID,
  currentQuotationId: null,   // null = new, "THR-AQ-26-00K005-02" = editing
  action: PAGE_SYS_ID ? 'append' : 'create',
  isSaved: false,
  isBasicLocked: !!PAGE_SYS_ID,
  document: null,
  ucptFile: null,
};

let selectedClientSysId = '';
let computedBizPrices   = {};

// ════════════════════════════════════════════════════════════════
// INIT
// ════════════════════════════════════════════════════════════════
window.addEventListener('DOMContentLoaded', async () => {
  renderSegments();
  setSaveStatus(false);
  initClientSelector();

  if (PAGE_SYS_ID) {
    // Existing document
    document.getElementById('pageSubtitle').textContent = PAGE_SYS_ID;
    document.getElementById('addMoreBtn').classList.remove('hidden');
    document.getElementById('editBasicBtn').classList.remove('hidden');
    document.getElementById('lockedNotice').classList.remove('hidden');
    await loadDocument();
  } else {
    // New document — basic info is editable immediately
    document.getElementById('quoteTitle').removeAttribute('readonly');
    document.getElementById('clientInput').removeAttribute('readonly');
    document.getElementById('lockedNotice').classList.add('hidden');
    document.getElementById('editBasicBtn').classList.add('hidden');
  }
});

// ════════════════════════════════════════════════════════════════
// LOAD DOCUMENT
// ════════════════════════════════════════════════════════════════
async function loadDocument() {
  try {
    const res  = await fetch(`${GET_API}?sys_id=${encodeURIComponent(PAGE_SYS_ID)}`);
    const r    = await res.json();
    if (!r.success) { showToast('Failed to load document', 'error'); return; }

    state.document = r.data;
    document.getElementById('pageSubtitle').textContent = `${r.data.sys_id} — ${r.data.title || 'Untitled'}`;

    // Fill locked basic info
    document.getElementById('quoteTitle').value = r.data.title || '';
    setClientDisplay(r.data.client_sys_id, r.data.client_name || r.data.client_sys_id);

    // Restore last percentage / ve_fixed_price
    if (r.data.percentage)     document.getElementById('percentage').value     = r.data.percentage;
    if (r.data.ve_fixed_price) document.getElementById('ve_fixed_price').value = r.data.ve_fixed_price;

    renderSidebar();
  } catch (e) {
    showToast('Network error loading document', 'error');
  }
}

// ════════════════════════════════════════════════════════════════
// SIDEBAR
// ════════════════════════════════════════════════════════════════
function renderSidebar() {
  const doc  = state.document;
  if (!doc) return;

  const forms = (doc.form_data || []).filter(f => !f.deleted);
  const body  = document.getElementById('sidebarBody');
  const count = document.getElementById('sidebarCount');

  count.textContent = forms.length;
  document.getElementById('sidebarSubtitle').textContent = doc.sys_id;

  if (!forms.length) {
    body.innerHTML = '<p class="text-xs text-slate-400 text-center py-8">No quotations saved yet.</p>';
    return;
  }

  body.innerHTML = '';

  forms.forEach((f, i) => {
    const card = document.createElement('div');
    card.className = 'quot-card' + (f.form_id === state.currentQuotationId ? ' active' : '');
    card.dataset.id = f.form_id;

    const createdBy   = f.meta_data?.created_by_date?.user || '';
    const createdDate = f.meta_data?.created_by_date?.date || '';
    const updCount    = (f.meta_data?.updated_by_date || []).length;
    const pax = [
      f.pax_adult > 0 ? `${f.pax_adult}A` : '',
      f.pax_child > 0 ? `${f.pax_child}C` : '',
      f.pax_infant > 0 ? `${f.pax_infant}I` : '',
    ].filter(Boolean).join(' ');

    card.innerHTML = `
      <div class="flex items-start justify-between gap-1">
        <span class="quot-id-tag">${f.form_id}</span>
        <button class="btn-danger-sm delete-quot-btn" style="padding:0.2rem 0.5rem;font-size:0.7rem;">
          <i class="fas fa-trash-alt"></i>
        </button>
      </div>
      <p class="text-sm font-medium text-slate-800 mt-1 leading-tight">${f.trip_option || ''} — ${f.route || ''}</p>
      <p class="text-xs text-slate-500">${f.class || ''} ${pax ? '· ' + pax : ''}</p>
      <div class="quot-meta">
        <i class="fas fa-user text-xs mr-0.5"></i>${createdBy}
        · ${createdDate}
        ${updCount ? `<span class="ml-1 text-indigo-400">(${updCount} edit${updCount > 1 ? 's' : ''})</span>` : ''}
      </div>
    `;

    card.addEventListener('click', (e) => {
      if (e.target.closest('.delete-quot-btn')) return;
      loadQuotationIntoForm(f.form_id);
    });

    card.querySelector('.delete-quot-btn').addEventListener('click', (e) => {
      e.stopPropagation();
      softDeleteQuotation(f.form_id);
    });

    body.appendChild(card);
  });
}

function loadQuotationIntoForm(quotId) {
  const f = (state.document?.form_data || []).find(x => x.form_id === quotId);
  if (!f) return;

  fillForm(f);

  // Restore markup values for this quotation
  const q = (state.document?.quotations || []).find(x => x.quot_id === quotId);
  if (q) {
    document.getElementById('percentage').value     = q.percentage     || 0;
    document.getElementById('ve_fixed_price').value = q.ve_fixed_price || 0;
  }

  state.currentQuotationId = quotId;
  state.action             = 'update';
  setSaveStatus(true); // it IS saved — just loaded for editing

  document.querySelectorAll('.quot-card').forEach(c =>
    c.classList.toggle('active', c.dataset.id === quotId)
  );

  // Scroll form to top smoothly
  document.querySelector('.editor-left').scrollIntoView({ behavior: 'smooth', block: 'start' });
  showToast(`Loaded ${quotId}`, 'info');
}

async function softDeleteQuotation(quotId) {
  if (!confirm(`Delete quotation ${quotId}? It can be recovered within 30 days.`)) return;

  try {
    const res = await fetch(STORE_API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'soft_delete', sys_id: state.sysId, quotation_id: quotId })
    });
    const r = await res.json();
    if (r.success) {
      showToast('Quotation deleted');
      await loadDocument();
      renderSidebar();
      if (state.currentQuotationId === quotId) {
        clearForm();
        state.currentQuotationId = null;
        state.action = 'append';
        setSaveStatus(false);
      }
    } else {
      showToast(r.message || 'Delete failed', 'error');
    }
  } catch (e) {
    showToast('Network error', 'error');
  }
}

// ════════════════════════════════════════════════════════════════
// BASIC INFO LOCK / UNLOCK
// ════════════════════════════════════════════════════════════════
let basicEditMode = false;

function toggleBasicEdit() {
  basicEditMode = !basicEditMode;

  const titleInput  = document.getElementById('quoteTitle');
  const clientInput = document.getElementById('clientInput');
  const editBtn     = document.getElementById('editBasicBtn');
  const saveBtn     = document.getElementById('saveBasicBtn');
  const lockNote    = document.getElementById('lockedNotice');

  if (basicEditMode) {
    titleInput.removeAttribute('readonly');
    clientInput.removeAttribute('readonly');
    editBtn.innerHTML  = '<i class="fas fa-times text-xs mr-1"></i> Cancel';
    saveBtn.classList.remove('hidden');
    lockNote.classList.add('hidden');
  } else {
    titleInput.setAttribute('readonly', true);
    clientInput.setAttribute('readonly', true);
    // Restore original values
    if (state.document) {
      titleInput.value = state.document.title || '';
      setClientDisplay(state.document.client_sys_id, state.document.client_name || state.document.client_sys_id);
    }
    editBtn.innerHTML = '<i class="fas fa-pen text-xs mr-1"></i> Edit';
    saveBtn.classList.add('hidden');
    lockNote.classList.remove('hidden');
  }
}

async function saveBasicInfo() {
  const title     = document.getElementById('quoteTitle').value.trim();
  const clientVal = document.getElementById('clientInput').value;

  if (!title) { showToast('Title is required', 'error'); return; }

  try {
    const res = await fetch(STORE_API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action:        'update_basic',
        sys_id:        state.sysId,
        title,
        client_sys_id: selectedClientSysId,
        client_name:   clientVal
      })
    });
    const r = await res.json();
    if (r.success) {
      if (state.document) { state.document.title = title; state.document.client_sys_id = selectedClientSysId; }
      document.getElementById('pageSubtitle').textContent = `${state.sysId} — ${title}`;
      showToast('Basic info updated');
      toggleBasicEdit(); // re-lock
    } else {
      showToast(r.message || 'Failed', 'error');
    }
  } catch (e) {
    showToast('Network error', 'error');
  }
}

// ════════════════════════════════════════════════════════════════
// UCPT
// ════════════════════════════════════════════════════════════════
const imageZone = document.getElementById('ucptImageZone');

imageZone.addEventListener('dragover', e => { e.preventDefault(); imageZone.classList.add('dragover'); });
imageZone.addEventListener('dragleave', ()  => imageZone.classList.remove('dragover'));
imageZone.addEventListener('drop', e => {
  e.preventDefault();
  imageZone.classList.remove('dragover');
  const file = e.dataTransfer.files[0];
  if (file && file.type.startsWith('image/')) setUCPTFile(file);
});

document.addEventListener('paste', e => {
  for (const item of e.clipboardData.items) {
    if (item.type.startsWith('image/')) {
      setUCPTFile(item.getAsFile());
      return;
    }
  }
});

function handleFileInput(input) {
  if (input.files[0]) setUCPTFile(input.files[0]);
}

function setUCPTFile(file) {
  state.ucptFile = file;
  const preview = document.getElementById('ucptPreview');
  preview.src   = URL.createObjectURL(file);
  preview.style.display = 'block';
  imageZone.classList.add('has-image');
  imageZone.querySelector('p').textContent = file.name;
  document.getElementById('ucptText').value = '';
}

function clearUCPT() {
  state.ucptFile = null;
  const preview  = document.getElementById('ucptPreview');
  preview.src    = '';
  preview.style.display = 'none';
  imageZone.classList.remove('has-image', 'dragover');
  imageZone.querySelector('p').textContent = 'Drop screenshot here, paste image (Ctrl+V), or click to browse';
  document.getElementById('fileInput').value = '';
  document.getElementById('ucptText').value  = '';
}

async function extractUCPT() {
  const text = document.getElementById('ucptText').value.trim();
  const fd   = new FormData();

  if (state.ucptFile) {
    fd.append('mode', 'image');
    fd.append('screenshot', state.ucptFile);
  } else if (text) {
    fd.append('mode', 'text');
    fd.append('text_input', text);
  } else {
    showToast('Upload a screenshot or paste text first', 'error');
    return;
  }

  showToast('Extracting…', 'info');

  try {
    const res = await fetch(EXTRACT_API, { method: 'POST', body: fd });
    const r   = await res.json();
    if (!r.success) { showToast(r.message || 'Extraction failed', 'error'); return; }
    fillForm(r.data);
    showToast('Form filled from extraction');
  } catch (e) {
    showToast('Network error: ' + e.message, 'error');
  }
}

// ════════════════════════════════════════════════════════════════
// SEGMENT / CONNECTION BUILDERS
// ════════════════════════════════════════════════════════════════
function buildConnectionHTML(data = {}) {
  const div = document.createElement('div');
  div.className = 'connection-card';
  div.innerHTML = `
    <div class="flex items-center justify-between mb-2">
      <span class="text-xs font-semibold text-slate-600 uppercase tracking-wide">Connection Flight</span>
      <button type="button" class="btn-danger-sm remove-conn">Remove</button>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-3">
      <div><label class="label">Dep Airport</label><input class="input conn_dep_airport" placeholder="SIN" value="${data.dep_airport||''}"></div>
      <div><label class="label">Dep Time</label><input type="time" class="input conn_dep_time" value="${data.dep_time||''}"></div>
      <div><label class="label">Arr Airport</label><input class="input conn_arr_airport" placeholder="MNL" value="${data.arr_airport||''}"></div>
      <div><label class="label">Arr Time</label><input type="time" class="input conn_arr_time" value="${data.arr_time||''}"></div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
      <div class="flex items-end pb-1">
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="checkbox" class="conn_arr_day_indicator w-4 h-4" ${data.arr_day_indicator?'checked':''}>
          <span class="text-sm text-slate-700">Next day (+1)</span>
        </label>
      </div>
      <div class="md:col-span-2">
        <label class="label">Flight No (optional)</label>
        <input class="input conn_flight_no" placeholder="e.g. SQ44" value="${data.flight_no||''}">
      </div>
    </div>`;
  div.querySelector('.remove-conn').addEventListener('click', () => { div.remove(); markUnsaved(); });
  return div;
}

function buildSegmentHTML(data, heading, removable) {
  const div = document.createElement('div');
  div.className = 'segment-card';
  const hasTransit = data.has_transit === true || data.has_transit === 'true';

  div.innerHTML = `
    <div class="flex items-center justify-between mb-3">
      <h3 class="font-semibold text-slate-800 seg-heading">${heading}</h3>
      ${removable ? '<button type="button" class="btn-danger-sm remove-seg">Remove</button>' : ''}
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
      <div><label class="label">Segment Title</label><input class="input segment_title" placeholder="e.g. DAC-MNL" value="${data.segment_title||''}"></div>
      <div><label class="label">Date</label><input type="date" class="input segment_date" value="${data.date||''}"></div>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-3">
      <div><label class="label">Airline</label><input class="input airline" placeholder="e.g. SQ" value="${data.airline||''}"></div>
      <div><label class="label">Flight No</label><input class="input flight_no" placeholder="e.g. SQ988" value="${data.flight_no||''}"></div>
      <div class="flex items-end pb-1">
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="checkbox" class="arr_day_indicator w-4 h-4" ${data.arr_day_indicator?'checked':''}>
          <span class="text-sm text-slate-700">Arr Next day (+1)</span>
        </label>
      </div>
      <div></div>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-2">
      <div><label class="label">Dep Airport</label><input class="input dep_airport" placeholder="DAC" value="${data.dep_airport||''}"></div>
      <div><label class="label">Dep Time</label><input type="time" class="input dep_time" value="${data.dep_time||''}"></div>
      <div><label class="label">Arr Airport</label><input class="input arr_airport" placeholder="SIN" value="${data.arr_airport||''}"></div>
      <div><label class="label">Arr Time</label><input type="time" class="input arr_time" value="${data.arr_time||''}"></div>
    </div>
    <div class="flex items-center justify-between mt-4 pt-3 border-t border-slate-200">
      <div class="flex items-center gap-3">
        <span class="text-sm font-medium text-slate-700">Has Transit?</span>
        <label class="switch"><input type="checkbox" class="has_transit" ${hasTransit?'checked':''}><span class="slider"></span></label>
        <span class="transit-state-label text-xs font-medium ${hasTransit?'text-emerald-700':'text-slate-500'}">${hasTransit?'Yes':'No'}</span>
      </div>
      <button type="button" class="btn-secondary add-conn-btn ${hasTransit?'':'hidden'}">+ Add Connection Flight</button>
    </div>
    <div class="transit-wrapper ${hasTransit?'':'hidden'}">
      <div class="transit-row mt-3">
        <label class="label">Transit Duration</label>
        <input class="input transit_time" placeholder="e.g. Tr 3 Hours" value="${data.transit_time||''}">
      </div>
      <div class="connections-container mt-3"></div>
    </div>`;

  if (removable) {
    div.querySelector('.remove-seg').addEventListener('click', () => { div.remove(); renumberMultiCity(); markUnsaved(); });
  }

  const toggle        = div.querySelector('.has_transit');
  const transitWrapper= div.querySelector('.transit-wrapper');
  const addConnBtn    = div.querySelector('.add-conn-btn');
  const stateLabel    = div.querySelector('.transit-state-label');
  const connContainer = div.querySelector('.connections-container');

  toggle.addEventListener('change', () => {
    const on = toggle.checked;
    transitWrapper.classList.toggle('hidden', !on);
    addConnBtn.classList.toggle('hidden', !on);
    stateLabel.textContent = on ? 'Yes' : 'No';
    stateLabel.classList.toggle('text-emerald-700', on);
    stateLabel.classList.toggle('text-slate-500', !on);
    if (!on) { connContainer.innerHTML = ''; div.querySelector('.transit_time').value = ''; }
    markUnsaved();
  });

  addConnBtn.addEventListener('click', () => { connContainer.appendChild(buildConnectionHTML({})); markUnsaved(); });

  if (hasTransit && Array.isArray(data.connections)) {
    data.connections.forEach(c => connContainer.appendChild(buildConnectionHTML(c)));
  }

  return div;
}

function renumberMultiCity() {
  if (document.querySelector('input[name="trip_option"]:checked')?.value !== 'Multi City') return;
  [...document.querySelectorAll('.segment-card')].forEach((s, i) => s.querySelector('.seg-heading').textContent = `Route ${i + 1}`);
}

function addMultiCitySegment(data = {}) {
  const container = document.getElementById('segments');
  const nextNum   = container.children.length + 1;
  const node      = buildSegmentHTML(data, `Route ${nextNum}`, nextNum > 1);
  container.appendChild(node);
  return node;
}

function renderSegments(segments = null) {
  const tripType  = document.querySelector('input[name="trip_option"]:checked')?.value || 'One Way';
  const container = document.getElementById('segments');
  const addBtn    = document.getElementById('add_segment_btn');
  container.innerHTML = '';

  if (segments) {
    // Fill from data
    segments.forEach((seg, i) => {
      let heading = 'Flight', removable = false;
      if (tripType === 'Round Trip') { heading = i === 0 ? 'Outbound Flight' : 'Inbound Flight'; }
      else if (tripType === 'Multi City') { heading = `Route ${i + 1}`; removable = i > 0; }
      container.appendChild(buildSegmentHTML(seg, heading, removable));
    });
    addBtn.classList.toggle('hidden', tripType !== 'Multi City');
    return;
  }

  if (tripType === 'One Way') {
    container.appendChild(buildSegmentHTML({}, 'Flight', false));
    addBtn.classList.add('hidden');
  } else if (tripType === 'Round Trip') {
    container.appendChild(buildSegmentHTML({}, 'Outbound Flight', false));
    container.appendChild(buildSegmentHTML({}, 'Inbound Flight', false));
    addBtn.classList.add('hidden');
  } else {
    container.appendChild(buildSegmentHTML({}, 'Route 1', false));
    container.appendChild(buildSegmentHTML({}, 'Route 2', true));
    addBtn.classList.remove('hidden');
  }
}

document.querySelectorAll('input[name="trip_option"]').forEach(r =>
  r.addEventListener('change', () => { renderSegments(); markUnsaved(); })
);

// ════════════════════════════════════════════════════════════════
// NOTES
// ════════════════════════════════════════════════════════════════
function addNote(value = '') {
  const div = document.createElement('div');
  div.className = 'flex gap-2 mb-2';
  div.innerHTML = `<input class="input note" placeholder="Note" value="${value}"><button type="button" class="btn-danger-sm">Remove</button>`;
  div.querySelector('button').addEventListener('click', () => { div.remove(); markUnsaved(); });
  document.getElementById('notes').appendChild(div);
}

// ════════════════════════════════════════════════════════════════
// HELPERS
// ════════════════════════════════════════════════════════════════
function formatDate(v) {
  if (!v) return '';
  const d = new Date(v);
  return isNaN(d) ? v : d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' });
}
function formatTime(v) {
  if (!v) return '';
  const [h, m] = v.split(':').map(Number);
  if (isNaN(h)) return v;
  const p = h >= 12 ? 'PM' : 'AM', h12 = h % 12 || 12, mm = String(m).padStart(2, '0');
  return `${String(h).padStart(2,'0')}:${mm} (${String(h12).padStart(2,'0')}:${mm}${p})`;
}
function formatPrice(p) { return Number(p).toLocaleString(); }
function applyMarkup(price, pct, fix) {
  const b = Number(price);
  if (!b || b <= 0) return '';
  return Math.round(b + (b * Number(pct || 0) / 100) + Number(fix || 0));
}

// ════════════════════════════════════════════════════════════════
// DATA COLLECTION
// ════════════════════════════════════════════════════════════════
function collectData() {
  const segments = [...document.querySelectorAll('.segment-card')].map(seg => {
    const hasT = seg.querySelector('.has_transit').checked;
    return {
      heading:         seg.querySelector('.seg-heading').textContent,
      segment_title:   seg.querySelector('.segment_title').value,
      date:            seg.querySelector('.segment_date').value,
      airline:         seg.querySelector('.airline').value,
      flight_no:       seg.querySelector('.flight_no').value,
      dep_airport:     seg.querySelector('.dep_airport').value,
      dep_time:        seg.querySelector('.dep_time').value,
      arr_airport:     seg.querySelector('.arr_airport').value,
      arr_time:        seg.querySelector('.arr_time').value,
      arr_day_indicator: seg.querySelector('.arr_day_indicator').checked,
      has_transit:     hasT,
      transit_time:    hasT ? seg.querySelector('.transit_time').value : '',
      connections:     hasT ? [...seg.querySelectorAll('.connection-card')].map(c => ({
        dep_airport:   c.querySelector('.conn_dep_airport').value,
        dep_time:      c.querySelector('.conn_dep_time').value,
        arr_airport:   c.querySelector('.conn_arr_airport').value,
        arr_time:      c.querySelector('.conn_arr_time').value,
        arr_day_indicator: c.querySelector('.conn_arr_day_indicator').checked,
        flight_no:     c.querySelector('.conn_flight_no').value
      })) : []
    };
  });

  const manualNotes  = [...document.querySelectorAll('.note')].map(n => n.value).filter(Boolean);
  const defaultNotes = [...document.querySelectorAll('.default_note:checked')].map(n => n.value);

  return {
    trip_option:       document.querySelector('input[name="trip_option"]:checked').value,
    route:             document.getElementById('route').value,
    class:             document.querySelector('input[name="class"]:checked').value,
    pax_adult:         parseInt(document.getElementById('pax_adult').value) || 0,
    pax_child:         parseInt(document.getElementById('pax_child').value) || 0,
    pax_infant:        parseInt(document.getElementById('pax_infant').value) || 0,
    segments,
    baggage_1_desc:    document.getElementById('baggage_1_desc').value,
    price_1_adult:     parseFloat(document.getElementById('price_1_adult').value) || 0,
    price_1_child:     parseFloat(document.getElementById('price_1_child').value) || 0,
    price_1_infant:    parseFloat(document.getElementById('price_1_infant').value) || 0,
    baggage_2_desc:    document.getElementById('baggage_2_desc').value,
    price_2_adult:     parseFloat(document.getElementById('price_2_adult').value) || 0,
    price_2_child:     parseFloat(document.getElementById('price_2_child').value) || 0,
    price_2_infant:    parseFloat(document.getElementById('price_2_infant').value) || 0,
    refundable_status: document.querySelector('input[name="refundable_status"]:checked').value,
    changeable_status: document.querySelector('input[name="changeable_status"]:checked').value,
    percentage:        parseFloat(document.getElementById('percentage').value) || 0,
    ve_fixed_price:    parseFloat(document.getElementById('ve_fixed_price').value) || 0,
    notes:             [...defaultNotes, ...manualNotes]
  };
}

// ════════════════════════════════════════════════════════════════
// QUOTATION TEXT BUILDER
// ════════════════════════════════════════════════════════════════
function makeQuotationText(data, biz = false) {
  const label = data.trip_option === 'One Way' ? 'One Way Ticket'
    : data.trip_option === 'Round Trip' ? 'Round Ticket' : 'Multi City Ticket';

  let t = `*${label}*\n`;
  const pax = [
    data.pax_adult  > 0 ? `${data.pax_adult} Adult`  : '',
    data.pax_child  > 0 ? `${data.pax_child} Child`  : '',
    data.pax_infant > 0 ? `${data.pax_infant} Infant` : '',
  ].filter(Boolean).join(', ');
  t += `Route: ${data.route} ${pax}\n${data.class}\n\n`;

  data.segments.forEach(seg => {
    t += `*${seg.heading}:*\n`;
    if (seg.segment_title) t += `${seg.segment_title}\n`;
    t += `${formatDate(seg.date)} | ${seg.flight_no || seg.airline}\n`;
    t += `${seg.dep_airport} ${formatTime(seg.dep_time)} -> ${seg.arr_airport} ${formatTime(seg.arr_time)}${seg.arr_day_indicator ? ' (+1)' : ''}\n`;
    if (seg.has_transit) {
      if (seg.transit_time) t += `${seg.transit_time}\n`;
      (seg.connections || []).forEach(c => {
        const tag = c.flight_no ? `${c.flight_no} | ` : '';
        t += `${tag}${c.dep_airport} ${formatTime(c.dep_time)} -> ${c.arr_airport} ${formatTime(c.arr_time)}${c.arr_day_indicator ? ' (+1)' : ''}\n`;
      });
    }
    t += `\n`;
  });

  t += `*Price:*\n`;
  const pct = data.percentage, fix = data.ve_fixed_price;

  const p1a = biz ? applyMarkup(data.price_1_adult,  pct, fix) : data.price_1_adult;
  const p1c = biz ? applyMarkup(data.price_1_child,  pct, fix) : data.price_1_child;
  const p1i = biz ? applyMarkup(data.price_1_infant, pct, fix) : data.price_1_infant;

  if (biz) { computedBizPrices.p1a = p1a; computedBizPrices.p1c = p1c; computedBizPrices.p1i = p1i; }

  if (Number(p1a) > 0 || Number(p1c) > 0 || Number(p1i) > 0) {
    const parts = [];
    if (Number(p1a) > 0) parts.push(`Adult ${formatPrice(p1a)}`);
    if (Number(p1c) > 0) parts.push(`Child ${formatPrice(p1c)}`);
    if (Number(p1i) > 0) parts.push(`Infant ${formatPrice(p1i)}`);
    t += `• ${data.baggage_1_desc}: BDT -> ${parts.join(' | ')}\n`;
  }

  const p2a = biz ? applyMarkup(data.price_2_adult,  pct, fix) : data.price_2_adult;
  const p2c = biz ? applyMarkup(data.price_2_child,  pct, fix) : data.price_2_child;
  const p2i = biz ? applyMarkup(data.price_2_infant, pct, fix) : data.price_2_infant;

  if (biz) { computedBizPrices.p2a = p2a; computedBizPrices.p2c = p2c; computedBizPrices.p2i = p2i; }

  if (Number(p2a) > 0 || Number(p2c) > 0 || Number(p2i) > 0) {
    const parts = [];
    if (Number(p2a) > 0) parts.push(`Adult ${formatPrice(p2a)}`);
    if (Number(p2c) > 0) parts.push(`Child ${formatPrice(p2c)}`);
    if (Number(p2i) > 0) parts.push(`Infant ${formatPrice(p2i)}`);
    t += `• ${data.baggage_2_desc}: BDT -> ${parts.join(' | ')}\n`;
  }

  t += `\n${data.refundable_status} | ${data.changeable_status}\n`;
  if (data.notes.length) { t += `\n*Note:*\n`; data.notes.forEach(n => { t += `• ${n}\n`; }); }
  return t;
}

function generateQuotation() {
  const data = collectData();
  computedBizPrices = {};
  document.getElementById('raw_preview').value      = makeQuotationText(data, false);
  document.getElementById('business_preview').value = makeQuotationText(data, true);
  markUnsaved();
}

// ════════════════════════════════════════════════════════════════
// SAVE
// ════════════════════════════════════════════════════════════════
async function saveQuotation() {
  generateQuotation();

  const data     = collectData();
  const rawText  = document.getElementById('raw_preview').value;
  const bizText  = document.getElementById('business_preview').value;

  if (!rawText.trim()) { showToast('Generate the quotation first', 'error'); return; }

  const title    = document.getElementById('quoteTitle').value.trim();
  const clientVal= document.getElementById('clientInput').value.trim();

  if (!title) { showToast('Title is required', 'error'); return; }

  const payload = {
    action:          state.action,
    sys_id:          state.sysId,
    title,
    client_sys_id:   selectedClientSysId,
    client_name:     clientVal,
    quotation_id:    state.currentQuotationId,
    raw_text:        rawText,
    business_text:   bizText,
    form_snapshot:   data,
    percentage:      data.percentage,
    ve_fixed_price:  data.ve_fixed_price,
    biz_price_1_adult:  computedBizPrices.p1a || 0,
    biz_price_1_child:  computedBizPrices.p1c || 0,
    biz_price_1_infant: computedBizPrices.p1i || 0,
    biz_price_2_adult:  computedBizPrices.p2a || 0,
    biz_price_2_child:  computedBizPrices.p2c || 0,
    biz_price_2_infant: computedBizPrices.p2i || 0,
  };

  try {
    const res = await fetch(STORE_API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const r = await res.json();

    if (r.success) {
      // Update state
      if (!state.sysId) {
        state.sysId = r.sys_id;
        history.replaceState(null, '', `?sys_id=${r.sys_id}`);
        document.getElementById('pageSubtitle').textContent = `${r.sys_id} — ${title}`;
        document.getElementById('addMoreBtn').classList.remove('hidden');
        document.getElementById('editBasicBtn').classList.remove('hidden');
      }

      state.currentQuotationId = r.quotation_id;
      state.action             = 'update'; // next save updates this quotation
      setSaveStatus(true);
      showToast(r.message);

      // Reload document + sidebar
      await loadDocument();
      renderSidebar();
    } else {
      showToast(r.message || 'Save failed', 'error');
    }
  } catch (e) {
    showToast('Network error: ' + e.message, 'error');
  }
}

// ════════════════════════════════════════════════════════════════
// ADD MORE QUOTATION
// ════════════════════════════════════════════════════════════════
function addMoreQuotation() {
  if (!state.isSaved) {
    if (!confirm('Current quotation is not saved. Discard changes and start a new one?')) return;
  }
  clearForm();
  state.currentQuotationId = null;
  state.action             = 'append';
  setSaveStatus(false);
  document.querySelectorAll('.quot-card').forEach(c => c.classList.remove('active'));
  document.querySelector('.editor-left').scrollIntoView({ behavior: 'smooth', block: 'start' });
  showToast('Form cleared — fill in new quotation details', 'info');
}

function clearForm() {
  // Reset pills
  document.getElementById('trip_oneway').checked = true;
  document.getElementById('cls_eco').checked     = true;
  document.getElementById('ref_yes').checked     = true;
  document.getElementById('chg_yes').checked     = true;
  // Reset fields
  ['route','price_1_adult','price_1_child','price_1_infant',
   'price_2_adult','price_2_child','price_2_infant'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.value = '';
  });
  document.getElementById('pax_adult').value    = 1;
  document.getElementById('pax_child').value    = 0;
  document.getElementById('pax_infant').value   = 0;
  document.getElementById('baggage_1_desc').value = 'Without Baggage';
  document.getElementById('baggage_2_desc').value = 'With 30 Kg Check-IN + 7 Kg Cabin Baggage';
  document.getElementById('notes').innerHTML    = '';
  document.getElementById('raw_preview').value  = '';
  document.getElementById('business_preview').value = '';
  document.querySelectorAll('.default_note').forEach(c => c.checked = false);
  renderSegments();
}

// ════════════════════════════════════════════════════════════════
// FILL FORM FROM DATA
// ════════════════════════════════════════════════════════════════
function fillForm(data) {
  if (data.trip_option) {
    const r = document.querySelector(`input[name="trip_option"][value="${data.trip_option}"]`);
    if (r) r.checked = true;
  }
  document.getElementById('route').value      = data.route || '';
  if (data.class) {
    const r = document.querySelector(`input[name="class"][value="${data.class}"]`);
    if (r) r.checked = true;
  }
  document.getElementById('pax_adult').value  = data.pax_adult  || data.pax?.adult  || 0;
  document.getElementById('pax_child').value  = data.pax_child  || data.pax?.child  || 0;
  document.getElementById('pax_infant').value = data.pax_infant || data.pax?.infant || 0;

  renderSegments(data.segments?.length ? data.segments : null);

  document.getElementById('baggage_1_desc').value = data.baggage_1_desc || 'Without Baggage';
  document.getElementById('price_1_adult').value  = data.price_1_adult  || '';
  document.getElementById('price_1_child').value  = data.price_1_child  || '';
  document.getElementById('price_1_infant').value = data.price_1_infant || '';
  document.getElementById('baggage_2_desc').value = data.baggage_2_desc || 'With 30 Kg Check-IN + 7 Kg Cabin Baggage';
  document.getElementById('price_2_adult').value  = data.price_2_adult  || '';
  document.getElementById('price_2_child').value  = data.price_2_child  || '';
  document.getElementById('price_2_infant').value = data.price_2_infant || '';

  if (data.refundable_status) {
    const r = document.querySelector(`input[name="refundable_status"][value="${data.refundable_status}"]`);
    if (r) r.checked = true;
  }
  if (data.changeable_status) {
    const r = document.querySelector(`input[name="changeable_status"][value="${data.changeable_status}"]`);
    if (r) r.checked = true;
  }

  document.getElementById('notes').innerHTML = '';
  (data.notes || []).forEach(n => addNote(n));

  generateQuotation();
  markUnsaved();
}

// ════════════════════════════════════════════════════════════════
// COPY
// ════════════════════════════════════════════════════════════════
async function copyBusinessQuotation() {
  if (!state.isSaved) { showToast('Save first before copying', 'error'); return; }
  const biz = document.getElementById('business_preview').value;
  if (!biz.trim()) { showToast('Nothing to copy', 'error'); return; }
  try {
    await navigator.clipboard.writeText(biz);
    showToast('Business quotation copied');
  } catch { showToast('Copy failed', 'error'); }
}

// ════════════════════════════════════════════════════════════════
// SAVE STATUS & DIRTY TRACKING
// ════════════════════════════════════════════════════════════════
function setSaveStatus(saved) {
  state.isSaved = saved;
  const el  = document.getElementById('saveStatus');
  const txt = el.querySelector('.status-text');
  if (saved) {
    el.classList.replace('unsaved', 'saved');
    txt.textContent = 'Saved';
    document.getElementById('copyBtn').disabled = false;
  } else {
    el.classList.replace('saved', 'unsaved');
    txt.textContent = 'Unsaved';
    document.getElementById('copyBtn').disabled = true;
  }
}

function markUnsaved() { setSaveStatus(false); }

document.addEventListener('input', e => {
  if (!e.target.closest('#clientSearchContainer') &&
      !e.target.closest('#ucptText') &&
      !e.target.matches('#quoteTitle')) {
    markUnsaved();
  }
});
document.addEventListener('change', e => {
  if (e.target.matches('input[type="radio"], input[type="checkbox"]')) markUnsaved();
});

function showToast(msg, type = 'success') {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className   = `toast ${type} show`;
  setTimeout(() => t.classList.remove('show'), 3000);
}

// ════════════════════════════════════════════════════════════════
// CLIENT SELECTOR (unchanged component as per rule)
// ════════════════════════════════════════════════════════════════
const GET_ALL_CLIENTS_API = '<?php echo $getAllClientsApi; ?>';
let clientsData = [];

function initClientSelector() {
  loadClients();
  const input    = document.getElementById('clientInput');
  const dropdown = document.getElementById('clientDropdown');
  const container= document.getElementById('clientSearchContainer');

  let timer;
  input.addEventListener('input', () => {
    if (input.readOnly) return;
    clearTimeout(timer);
    timer = setTimeout(() => {
      const val      = input.value.toLowerCase().trim();
      const filtered = val === '' ? clientsData
        : clientsData.filter(c => c.name?.toLowerCase().includes(val) || c.sys_id?.toLowerCase().includes(val));
      renderClientDropdown(filtered);
      dropdown.classList.remove('hidden');
    }, 300);
  });

  input.addEventListener('focus', () => {
    if (input.readOnly) return;
    renderClientDropdown(clientsData);
    dropdown.classList.remove('hidden');
  });

  document.addEventListener('click', e => {
    if (!container.contains(e.target)) dropdown.classList.add('hidden');
  });
}

function loadClients() {
  fetch(GET_ALL_CLIENTS_API)
    .then(r => r.json())
    .then(d => { clientsData = Array.isArray(d.clients) ? d.clients : []; })
    .catch(() => { clientsData = []; });
}

function renderClientDropdown(list) {
  const dropdown = document.getElementById('clientDropdown');
  dropdown.innerHTML = '';
  if (!list.length) {
    dropdown.innerHTML = '<li class="px-4 py-3 text-center text-gray-500">No clients found</li>';
    return;
  }
  list.forEach(client => {
    let phone = '';
    try { if (client.phone?.startsWith('{')) phone = JSON.parse(client.phone).primary_no ?? ''; } catch {}
    const li = document.createElement('li');
    li.className = 'px-4 py-3 cursor-pointer hover:bg-purple-50 border-b last:border-b-0';
    li.innerHTML = `
      <div class="flex items-center">
        <div class="w-8 h-8 bg-purple-600 rounded-full text-white flex items-center justify-center font-semibold">
          ${client.name?.charAt(0).toUpperCase() ?? 'C'}
        </div>
        <div class="ml-3 flex-1">
          <div class="font-medium">${client.name}</div>
          <div class="text-xs text-gray-500 flex gap-2">
            <span>ID: ${client.sys_id}</span>
            ${phone ? `<span>📞 ${phone}</span>` : ''}
          </div>
        </div>
      </div>`;
    li.onclick = () => {
      document.getElementById('clientInput').value = `${client.sys_id} | ${client.name}`;
      selectedClientSysId = client.sys_id;
      document.getElementById('clientDropdown').classList.add('hidden');
    };
    dropdown.appendChild(li);
  });
}

function setClientDisplay(sysId, name) {
  selectedClientSysId = sysId || '';
  document.getElementById('clientInput').value = sysId && name ? `${sysId} | ${name}` : (sysId || '');
}
</script>
</body>
</html>