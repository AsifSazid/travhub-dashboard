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
  <title>Hotel Quotation</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    body { font-family: 'Inter', system-ui, sans-serif; }
    .editor-layout { display:flex; gap:1.25rem; align-items:flex-start; }
    .editor-left   { flex:1; min-width:0; }
    .editor-right  { width:300px; flex-shrink:0; position:sticky; top:5rem; max-height:calc(100vh - 6rem); display:flex; flex-direction:column; }
    @media (max-width:1024px) { .editor-layout { flex-direction:column; } .editor-right { width:100%; position:static; max-height:none; } }
    .sidebar-box   { background:#fff; border:1px solid #e5e7eb; border-radius:0.875rem; overflow:hidden; display:flex; flex-direction:column; flex:1; min-height:0; }
    .sidebar-head  { padding:1rem 1.25rem; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; justify-content:space-between; flex-shrink:0; }
    .sidebar-body  { overflow-y:auto; flex:1; padding:0.75rem; }
    .quot-card { border:1px solid #e5e7eb; border-radius:0.5rem; padding:0.75rem; margin-bottom:0.5rem; cursor:pointer; transition:all 0.15s ease; background:#fafafa; }
    .quot-card:hover  { border-color:#94a3b8; background:#fff; }
    .quot-card.active { border-color:#0f172a; background:#fff; box-shadow:0 0 0 2px rgba(15,23,42,0.08); }
    .quot-id-tag { font-size:0.7rem; font-weight:700; color:#7e22ce; font-family:monospace; }
    .quot-meta   { font-size:0.72rem; color:#94a3b8; margin-top:0.2rem; }
    .input { width:100%; padding:0.625rem 0.875rem; border:1px solid #e5e7eb; border-radius:0.5rem; font-size:0.9rem; color:#0f172a; background:#fff; transition:all 0.15s ease; }
    .input:focus { outline:none; border-color:#0f172a; box-shadow:0 0 0 3px rgba(15,23,42,0.08); }
    .input[readonly] { background:#f9fafb; color:#6b7280; cursor:default; }
    .label { display:block; font-size:0.8rem; font-weight:600; color:#374151; margin-bottom:0.35rem; }
    .card  { background:#fff; border:1px solid #e5e7eb; border-radius:0.875rem; padding:1.5rem; margin-bottom:1.25rem; }
    .section-title { font-size:1.05rem; font-weight:600; color:#0f172a; margin-bottom:1rem; display:flex; align-items:center; gap:0.625rem; }
    .section-title .num { background:#0f172a; color:#fff; width:1.5rem; height:1.5rem; border-radius:9999px; display:inline-flex; align-items:center; justify-content:center; font-size:0.75rem; font-weight:600; }
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
    .room-card { border:1px solid #e5e7eb; border-radius:0.625rem; padding:1rem; margin-bottom:0.875rem; background:#f9fafb; }
    .ucpt-image-zone { border:2px dashed #cbd5e1; border-radius:0.75rem; padding:1.5rem; text-align:center; cursor:pointer; transition:all 0.2s ease; background:#fafafa; }
    .ucpt-image-zone.dragover  { border-color:#0f172a; background:#f8fafc; }
    .ucpt-image-zone.has-image { border-color:#16a34a; background:#f0fdf4; }
    .ucpt-preview-img { max-height:160px; max-width:100%; border-radius:0.5rem; margin:0.5rem auto 0; display:none; }
    .ucpt-divider { display:flex; align-items:center; gap:0.75rem; margin:0.75rem 0; }
    .ucpt-divider::before,.ucpt-divider::after { content:''; flex:1; height:1px; background:#e5e7eb; }
    .ucpt-divider span { font-size:0.78rem; color:#94a3b8; font-weight:500; }
    .preview { width:100%; height:22rem; padding:1rem; border:1px solid #e5e7eb; border-radius:0.5rem; font-family:'Courier New',monospace; font-size:0.82rem; line-height:1.55; resize:vertical; }
    .preview-business { background:#f0fdf4; border-color:#bbf7d0; }
    .save-status { display:inline-flex; align-items:center; gap:0.4rem; padding:0.35rem 0.75rem; border-radius:9999px; font-size:0.78rem; font-weight:500; }
    .save-status.unsaved { background:#fef3c7; color:#92400e; }
    .save-status.saved   { background:#d1fae5; color:#065f46; }
    .dot { width:0.5rem; height:0.5rem; border-radius:9999px; }
    .save-status.unsaved .dot { background:#f59e0b; }
    .save-status.saved   .dot { background:#10b981; }
    .toast { position:fixed; top:1.5rem; right:1.5rem; padding:0.85rem 1.15rem; border-radius:0.5rem; color:#fff; font-weight:500; font-size:0.88rem; box-shadow:0 8px 24px -8px rgba(0,0,0,0.2); z-index:9999; transform:translateX(120%); transition:transform 0.3s ease; }
    .toast.show    { transform:translateX(0); }
    .toast.success { background:#16a34a; }
    .toast.error   { background:#dc2626; }
    .toast.info    { background:#0f172a; }
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
      <a href="index-hotel-quotation.php" class="text-slate-400 hover:text-slate-700 transition-colors">
        <i class="fas fa-arrow-left"></i>
      </a>
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Hotel Quotation</h1>
        <p class="text-sm text-slate-500 mt-0.5" id="pageSubtitle">New document</p>
      </div>
    </div>
    <div id="saveStatus" class="save-status unsaved">
      <span class="dot"></span><span class="status-text">Unsaved</span>
    </div>
  </div>

  <div class="editor-layout">

    <!-- LEFT PANEL -->
    <div class="editor-left">

      <!-- OP: UCPT -->
      <div class="card">
        <div class="section-title"><span class="num">OP</span> Screenshot Extraction</div>
        <div id="ucptImageZone" class="ucpt-image-zone" onclick="document.getElementById('fileInput').click()">
          <input type="file" id="fileInput" accept="image/*" class="hidden" onchange="handleFileInput(this)">
          <i class="fas fa-cloud-upload-alt text-3xl text-slate-300 mb-2 block"></i>
          <p class="text-sm font-medium text-slate-600">Drop screenshot here, paste image (Ctrl+V), or click to browse</p>
          <p class="text-xs text-slate-400 mt-1">PNG, JPG, WEBP supported</p>
          <img id="ucptPreview" class="ucpt-preview-img">
        </div>
        <div class="ucpt-divider"><span>OR paste / type text</span></div>
        <textarea id="ucptText" class="input" rows="4" placeholder="No screenshot? Paste or type the hotel booking text here..."></textarea>
        <div class="flex gap-2 mt-3">
          <button onclick="extractUCPT()" class="btn-success flex-1">
            <i class="fas fa-magic mr-1.5"></i> Extract & Fill Form
          </button>
          <button onclick="clearUCPT()" class="btn-secondary"><i class="fas fa-times"></i></button>
        </div>
      </div>

      <!-- 1: Basic Information -->
      <div class="card">
        <div class="flex items-center justify-between mb-4">
          <div class="section-title mb-0"><span class="num">1</span> Basic Information</div>
          <div class="flex items-center gap-2">
            <span id="lockedNotice" class="locked-notice"><i class="fas fa-lock text-xs"></i> Locked</span>
            <button id="editBasicBtn" onclick="toggleBasicEdit()" class="btn-warning hidden"><i class="fas fa-pen text-xs mr-1"></i> Edit</button>
            <button id="saveBasicBtn" onclick="saveBasicInfo()" class="btn-success hidden"><i class="fas fa-save text-xs mr-1"></i> Save Basic Info</button>
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="label">Title</label>
            <input id="quoteTitle" class="input" placeholder="e.g. Bangkok Trip" readonly>
          </div>
          <div>
            <label class="label">Client</label>
            <div id="clientSearchContainer" class="relative w-full">
              <input type="text" id="clientInput" placeholder="Search for a client..." class="input" autocomplete="off" readonly>
              <ul id="clientDropdown" class="absolute w-full bg-white border border-gray-300 rounded-lg mt-1 max-h-60 overflow-auto shadow-xl hidden z-50"></ul>
            </div>
          </div>
        </div>
      </div>

      <!-- 2: Hotel Information -->
      <div class="card">
        <div class="section-title"><span class="num">2</span> Hotel Information</div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <div><label class="label">Hotel Name</label><input id="hotel_name" class="input" placeholder="e.g. Grand Hyatt Singapore"></div>
          <div><label class="label">Address</label><input id="address" class="input" placeholder="Full address"></div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div><label class="label">Check In</label><input id="check_in" type="date" class="input" onchange="calculateNights(); markUnsaved();"></div>
          <div><label class="label">Check Out</label><input id="check_out" type="date" class="input" onchange="calculateNights(); markUnsaved();"></div>
          <div><label class="label">No of Nights</label><input id="nights" class="input" readonly></div>
        </div>
      </div>

      <!-- 3: Rooms -->
      <div class="card">
        <div class="flex items-center justify-between mb-4">
          <div class="section-title mb-0"><span class="num">3</span> Room Information</div>
          <button onclick="addRoom(); markUnsaved();" class="btn-secondary">+ Add Room</button>
        </div>
        <div id="rooms"></div>
      </div>

      <!-- 4: Notes -->
      <div class="card">
        <div class="section-title"><span class="num">4</span> Notes</div>
        <div class="space-y-2 mb-4">
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" class="default_note w-4 h-4" value="City taxes payable at hotel" onchange="markUnsaved()">
            <span class="text-sm text-slate-700">City taxes payable at hotel</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" class="default_note w-4 h-4" value="Refundable security deposit applicable" onchange="markUnsaved()">
            <span class="text-sm text-slate-700">Refundable security deposit applicable</span>
          </label>
        </div>
        <div id="notes" class="mb-3"></div>
        <button onclick="addNote(); markUnsaved();" class="btn-secondary">+ Add Note</button>
      </div>

      <!-- 5: Business Markup -->
      <div class="card">
        <div class="section-title"><span class="num">5</span> Business Markup</div>
        <div>
          <label class="label">Business Percentage (%)</label>
          <input id="percentage" type="number" min="0" step="0.01" value="0" class="input max-w-xs" oninput="generateQuotation(); markUnsaved();">
        </div>
        <p class="text-xs text-slate-400 mt-3">Formula: <span class="font-mono">final = base × (1 + %/100)</span></p>
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

      <!-- Preview -->
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

    <!-- RIGHT PANEL — SIDEBAR -->
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

  </div>
</div>
</main>

<div id="toast" class="toast"></div>

<script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>
<script>
const API_BASE   = '<?php echo rtrim($ip_port, "/"); ?>/';
const STORE_API  = API_BASE + 'api/hotels/store-quotation.php';
const GET_API    = API_BASE + 'api/hotels/get-quotation.php';
const EXTRACT_API= API_BASE + 'api/hotels/ss-extraction.php';

const urlParams  = new URLSearchParams(window.location.search);
const PAGE_SYS_ID= urlParams.get('sys_id') || null;

const state = {
  sysId: PAGE_SYS_ID, currentQuotationId: null,
  action: PAGE_SYS_ID ? 'append' : 'create',
  isSaved: false, isBasicLocked: !!PAGE_SYS_ID, document: null, ucptFile: null
};

let selectedClientSysId = '';

window.addEventListener('DOMContentLoaded', async () => {
  addRoom();
  setSaveStatus(false);
  initClientSelector();
  if (PAGE_SYS_ID) {
    document.getElementById('pageSubtitle').textContent = PAGE_SYS_ID;
    document.getElementById('addMoreBtn').classList.remove('hidden');
    document.getElementById('editBasicBtn').classList.remove('hidden');
    document.getElementById('lockedNotice').classList.remove('hidden');
    await loadDocument();
  } else {
    document.getElementById('quoteTitle').removeAttribute('readonly');
    document.getElementById('clientInput').removeAttribute('readonly');
    document.getElementById('lockedNotice').classList.add('hidden');
    document.getElementById('editBasicBtn').classList.add('hidden');
  }
});

async function loadDocument() {
  try {
    const res = await fetch(`${GET_API}?sys_id=${encodeURIComponent(PAGE_SYS_ID)}`);
    const r   = await res.json();
    if (!r.success) { showToast('Failed to load document', 'error'); return; }
    state.document = r.data;
    document.getElementById('pageSubtitle').textContent = `${r.data.sys_id} — ${r.data.title || 'Untitled'}`;
    document.getElementById('quoteTitle').value = r.data.title || '';
    setClientDisplay(r.data.client_sys_id, r.data.client_name || r.data.client_sys_id);
    if (r.data.percentage) document.getElementById('percentage').value = r.data.percentage;
    renderSidebar();
  } catch (e) { showToast('Network error', 'error'); }
}

function renderSidebar() {
  const doc   = state.document;
  if (!doc) return;
  const forms = (doc.form_data || []).filter(f => !f.deleted);
  document.getElementById('sidebarCount').textContent = forms.length;
  document.getElementById('sidebarSubtitle').textContent = doc.sys_id;
  const body = document.getElementById('sidebarBody');
  if (!forms.length) { body.innerHTML = '<p class="text-xs text-slate-400 text-center py-8">No quotations saved yet.</p>'; return; }
  body.innerHTML = '';
  forms.forEach(f => {
    const card = document.createElement('div');
    card.className = 'quot-card' + (f.form_id === state.currentQuotationId ? ' active' : '');
    card.dataset.id = f.form_id;
    const createdBy   = f.meta_data?.created_by_date?.user || '';
    const createdDate = f.meta_data?.created_by_date?.date || '';
    const updCount    = (f.meta_data?.updated_by_date || []).length;
    const roomCount   = (f.rooms || []).length;
    card.innerHTML = `
      <div class="flex items-start justify-between gap-1">
        <span class="quot-id-tag">${f.form_id}</span>
        <button class="btn-danger-sm delete-quot-btn" style="padding:0.2rem 0.5rem;font-size:0.7rem;"><i class="fas fa-trash-alt"></i></button>
      </div>
      <p class="text-sm font-medium text-slate-800 mt-1">${f.hotel_name || '—'}</p>
      <p class="text-xs text-slate-500">${roomCount} room${roomCount !== 1 ? 's' : ''} · ${f.nights || '—'} nights</p>
      <div class="quot-meta"><i class="fas fa-user text-xs mr-0.5"></i>${createdBy} · ${createdDate}${updCount ? ` <span class="text-indigo-400">(${updCount} edit${updCount > 1 ? 's' : ''})</span>` : ''}</div>`;
    card.addEventListener('click', e => { if (e.target.closest('.delete-quot-btn')) return; loadQuotationIntoForm(f.form_id); });
    card.querySelector('.delete-quot-btn').addEventListener('click', e => { e.stopPropagation(); softDeleteQuotation(f.form_id); });
    body.appendChild(card);
  });
}

function loadQuotationIntoForm(quotId) {
  const f = (state.document?.form_data || []).find(x => x.form_id === quotId);
  if (!f) return;
  fillForm(f);
  const q = (state.document?.quotations || []).find(x => x.quot_id === quotId);
  if (q) document.getElementById('percentage').value = q.percentage || 0;
  state.currentQuotationId = quotId;
  state.action = 'update';
  setSaveStatus(true);
  document.querySelectorAll('.quot-card').forEach(c => c.classList.toggle('active', c.dataset.id === quotId));
  document.querySelector('.editor-left').scrollIntoView({ behavior: 'smooth', block: 'start' });
  showToast(`Loaded ${quotId}`, 'info');
}

async function softDeleteQuotation(quotId) {
  if (!confirm(`Delete quotation ${quotId}? It can be recovered within 30 days.`)) return;
  try {
    const res = await fetch(STORE_API, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({ action:'soft_delete', sys_id:state.sysId, quotation_id:quotId }) });
    const r = await res.json();
    if (r.success) { showToast('Quotation deleted'); await loadDocument(); renderSidebar(); if (state.currentQuotationId === quotId) { clearForm(); state.currentQuotationId = null; state.action = 'append'; setSaveStatus(false); } }
    else showToast(r.message || 'Delete failed', 'error');
  } catch { showToast('Network error', 'error'); }
}

let basicEditMode = false;
function toggleBasicEdit() {
  basicEditMode = !basicEditMode;
  const ti = document.getElementById('quoteTitle'), ci = document.getElementById('clientInput');
  const eb = document.getElementById('editBasicBtn'), sb = document.getElementById('saveBasicBtn'), ln = document.getElementById('lockedNotice');
  if (basicEditMode) {
    ti.removeAttribute('readonly'); ci.removeAttribute('readonly');
    eb.innerHTML = '<i class="fas fa-times text-xs mr-1"></i> Cancel'; sb.classList.remove('hidden'); ln.classList.add('hidden');
  } else {
    ti.setAttribute('readonly', true); ci.setAttribute('readonly', true);
    if (state.document) { ti.value = state.document.title || ''; setClientDisplay(state.document.client_sys_id, state.document.client_name || state.document.client_sys_id); }
    eb.innerHTML = '<i class="fas fa-pen text-xs mr-1"></i> Edit'; sb.classList.add('hidden'); ln.classList.remove('hidden');
  }
}

async function saveBasicInfo() {
  const title = document.getElementById('quoteTitle').value.trim();
  if (!title) { showToast('Title is required', 'error'); return; }
  try {
    const res = await fetch(STORE_API, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({ action:'update_basic', sys_id:state.sysId, title, client_sys_id:selectedClientSysId, client_name:document.getElementById('clientInput').value }) });
    const r = await res.json();
    if (r.success) { if (state.document) { state.document.title = title; state.document.client_sys_id = selectedClientSysId; } document.getElementById('pageSubtitle').textContent = `${state.sysId} — ${title}`; showToast('Basic info updated'); toggleBasicEdit(); }
    else showToast(r.message || 'Failed', 'error');
  } catch { showToast('Network error', 'error'); }
}

// UCPT
const imageZone = document.getElementById('ucptImageZone');
imageZone.addEventListener('dragover', e => { e.preventDefault(); imageZone.classList.add('dragover'); });
imageZone.addEventListener('dragleave', () => imageZone.classList.remove('dragover'));
imageZone.addEventListener('drop', e => { e.preventDefault(); imageZone.classList.remove('dragover'); const f = e.dataTransfer.files[0]; if (f?.type.startsWith('image/')) setUCPTFile(f); });
document.addEventListener('paste', e => { for (const item of e.clipboardData.items) { if (item.type.startsWith('image/')) { setUCPTFile(item.getAsFile()); return; } } });
function handleFileInput(input) { if (input.files[0]) setUCPTFile(input.files[0]); }
function setUCPTFile(file) {
  state.ucptFile = file;
  const p = document.getElementById('ucptPreview');
  p.src = URL.createObjectURL(file); p.style.display = 'block';
  imageZone.classList.add('has-image');
  imageZone.querySelector('p').textContent = file.name;
  document.getElementById('ucptText').value = '';
}
function clearUCPT() {
  state.ucptFile = null;
  const p = document.getElementById('ucptPreview'); p.src = ''; p.style.display = 'none';
  imageZone.classList.remove('has-image', 'dragover');
  imageZone.querySelector('p').textContent = 'Drop screenshot here, paste image (Ctrl+V), or click to browse';
  document.getElementById('fileInput').value = '';
  document.getElementById('ucptText').value  = '';
}
async function extractUCPT() {
  const text = document.getElementById('ucptText').value.trim();
  const fd   = new FormData();
  if (state.ucptFile) { fd.append('mode', 'image'); fd.append('screenshot', state.ucptFile); }
  else if (text)      { fd.append('mode', 'text');  fd.append('text_input', text); }
  else { showToast('Upload a screenshot or paste text first', 'error'); return; }
  showToast('Extracting…', 'info');
  try {
    const res = await fetch(EXTRACT_API, { method:'POST', body:fd });
    const r   = await res.json();
    if (!r.success) { showToast(r.message || 'Extraction failed', 'error'); return; }
    fillForm(r.data); showToast('Form filled from extraction');
  } catch (e) { showToast('Network error: ' + e.message, 'error'); }
}

// ROOMS
function addRoom(data = {}) {
  const div = document.createElement('div');
  div.className = 'room-card';
  div.innerHTML = `
    <div class="flex items-center justify-between mb-3">
      <h3 class="font-semibold text-slate-800">Room Details</h3>
      <button type="button" class="btn-danger-sm">Remove</button>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
      <div><label class="label">Room Type</label><input class="input room_type" placeholder="e.g. Deluxe King" value="${data.room_type||''}"></div>
      <div><label class="label">Room Size</label><input class="input room_size" placeholder="e.g. 32 sqm" value="${data.room_size||''}"></div>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-3">
      <div><label class="label">No of Rooms</label><input type="number" min="0" class="input no_rooms" value="${data.no_rooms||''}"></div>
      <div><label class="label">Adults</label><input type="number" min="0" class="input adults" value="${data.adults||''}"></div>
      <div><label class="label">Child Count</label><input type="number" min="0" class="input child_count" value="${data.child_count||''}"></div>
      <div><label class="label">Child Ages</label><input class="input child_ages" placeholder="e.g. 4Y, 7Y" value="${data.child_ages||''}"></div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="label">Room Only Price (BDT)</label>
        <div class="flex gap-2">
          <input type="number" min="0" class="input room_only flex-1" value="${data.room_only||''}">
          <select class="input room_only_type" style="width:auto;flex:none;">
            <option value="Total"${data.room_only_type==='Total'?' selected':''}>Total</option>
            <option value="PRPN"${data.room_only_type==='PRPN'?' selected':''}>Per Room/Night</option>
          </select>
        </div>
      </div>
      <div>
        <label class="label">With Breakfast Price (BDT)</label>
        <div class="flex gap-2">
          <input type="number" min="0" class="input breakfast flex-1" value="${data.breakfast||''}">
          <select class="input breakfast_type" style="width:auto;flex:none;">
            <option value="Total"${data.breakfast_type==='Total'?' selected':''}>Total</option>
            <option value="PRPN"${data.breakfast_type==='PRPN'?' selected':''}>Per Room/Night</option>
          </select>
        </div>
      </div>
    </div>`;
  div.querySelector('.btn-danger-sm').addEventListener('click', () => { div.remove(); markUnsaved(); });
  document.getElementById('rooms').appendChild(div);
}

function addNote(value = '') {
  const div = document.createElement('div');
  div.className = 'flex gap-2 mb-2';
  div.innerHTML = `<input class="input note" placeholder="Note" value="${value}"><button type="button" class="btn-danger-sm">Remove</button>`;
  div.querySelector('button').addEventListener('click', () => { div.remove(); markUnsaved(); });
  document.getElementById('notes').appendChild(div);
}

function calculateNights() {
  const ci = new Date(document.getElementById('check_in').value);
  const co = new Date(document.getElementById('check_out').value);
  if (!isNaN(ci) && !isNaN(co)) {
    const d = Math.round((co - ci) / 86400000);
    document.getElementById('nights').value = d > 0 ? d : 0;
  }
}

function formatDate(v) { if (!v) return ''; const d = new Date(v); return isNaN(d) ? v : d.toLocaleDateString('en-GB', {day:'2-digit',month:'short'}); }
function formatPrice(p) { return Number(p).toLocaleString(); }
function applyMarkup(price, pct) { const b = Number(price); if (!b || b <= 0) return ''; return Math.round(b + (b * Number(pct || 0) / 100)); }

function collectData() {
  const rooms = [...document.querySelectorAll('.room-card')].map(r => ({
    room_type: r.querySelector('.room_type').value,
    room_size: r.querySelector('.room_size').value,
    no_rooms:  r.querySelector('.no_rooms').value,
    adults:    r.querySelector('.adults').value,
    child_count: r.querySelector('.child_count').value,
    child_ages:  r.querySelector('.child_ages').value,
    room_only:   r.querySelector('.room_only').value,
    room_only_type: r.querySelector('.room_only_type').value,
    breakfast:   r.querySelector('.breakfast').value,
    breakfast_type: r.querySelector('.breakfast_type').value
  }));
  const manualNotes  = [...document.querySelectorAll('.note')].map(n => n.value).filter(Boolean);
  const defaultNotes = [...document.querySelectorAll('.default_note:checked')].map(n => n.value);
  return {
    hotel_name: document.getElementById('hotel_name').value,
    address:    document.getElementById('address').value,
    check_in:   document.getElementById('check_in').value,
    check_out:  document.getElementById('check_out').value,
    nights:     document.getElementById('nights').value,
    percentage: parseFloat(document.getElementById('percentage').value) || 0,
    rooms, notes: [...defaultNotes, ...manualNotes]
  };
}

function makeQuotationText(data, biz = false) {
  let t = `*${data.hotel_name || 'Hotel'}*\n`;
  if (data.address) t += `Address: ${data.address}\n`;
  t += `\nC/In: ${formatDate(data.check_in)} | C/Out: ${formatDate(data.check_out)} | ${data.nights} Nights\n`;

  data.rooms.forEach(room => {
    t += `\n${room.no_rooms} Room | ${room.adults} Adults`;
    if (Number(room.child_count) > 0) t += ` + ${room.child_count} Child (${room.child_ages})`;
    t += `\n${room.room_type}`;
    if (room.room_size) t += ` — ${room.room_size}`;
    t += `\n`;

    const ro = biz ? applyMarkup(room.room_only,  data.percentage) : room.room_only;
    const bf = biz ? applyMarkup(room.breakfast,   data.percentage) : room.breakfast;

    if (Number(ro) > 0 || Number(bf) > 0) {
      t += `*Price:*\n`;
      if (Number(ro) > 0) t += `- Room Only: BDT ${formatPrice(ro)} (${room.room_only_type})\n`;
      if (Number(bf) > 0) t += `- With Breakfast: BDT ${formatPrice(bf)} (${room.breakfast_type})\n`;
    }
  });

  if (data.notes.length) { t += `\n*Note:*\n`; data.notes.forEach(n => { t += `• ${n}\n`; }); }
  return t;
}

function generateQuotation() {
  const data = collectData();
  document.getElementById('raw_preview').value      = makeQuotationText(data, false);
  document.getElementById('business_preview').value = makeQuotationText(data, true);
  markUnsaved();
}

async function saveQuotation() {
  generateQuotation();
  const data    = collectData();
  const rawText = document.getElementById('raw_preview').value;
  const bizText = document.getElementById('business_preview').value;
  if (!rawText.trim()) { showToast('Generate the quotation first', 'error'); return; }
  const title = document.getElementById('quoteTitle').value.trim();
  if (!title) { showToast('Title is required', 'error'); return; }

  try {
    const res = await fetch(STORE_API, {
      method: 'POST', headers: {'Content-Type':'application/json'},
      body: JSON.stringify({
        action: state.action, sys_id: state.sysId, title,
        client_sys_id: selectedClientSysId, client_name: document.getElementById('clientInput').value,
        quotation_id: state.currentQuotationId,
        raw_text: rawText, business_text: bizText,
        form_snapshot: data, percentage: data.percentage
      })
    });
    const r = await res.json();
    if (r.success) {
      if (!state.sysId) {
        state.sysId = r.sys_id;
        history.replaceState(null, '', `?sys_id=${r.sys_id}`);
        document.getElementById('pageSubtitle').textContent = `${r.sys_id} — ${title}`;
        document.getElementById('addMoreBtn').classList.remove('hidden');
        document.getElementById('editBasicBtn').classList.remove('hidden');
      }
      state.currentQuotationId = r.quotation_id;
      state.action = 'update';
      setSaveStatus(true);
      showToast(r.message);
      await loadDocument();
      renderSidebar();
    } else { showToast(r.message || 'Save failed', 'error'); }
  } catch (e) { showToast('Network error: ' + e.message, 'error'); }
}

function addMoreQuotation() {
  if (!state.isSaved) { if (!confirm('Current quotation not saved. Discard and start new?')) return; }
  clearForm();
  state.currentQuotationId = null;
  state.action = 'append';
  setSaveStatus(false);
  document.querySelectorAll('.quot-card').forEach(c => c.classList.remove('active'));
  document.querySelector('.editor-left').scrollIntoView({ behavior:'smooth', block:'start' });
  showToast('Form cleared — fill in new quotation details', 'info');
}

function clearForm() {
  document.getElementById('hotel_name').value = '';
  document.getElementById('address').value    = '';
  document.getElementById('check_in').value   = '';
  document.getElementById('check_out').value  = '';
  document.getElementById('nights').value     = '';
  document.getElementById('notes').innerHTML  = '';
  document.getElementById('rooms').innerHTML  = '';
  document.getElementById('raw_preview').value = '';
  document.getElementById('business_preview').value = '';
  document.querySelectorAll('.default_note').forEach(c => c.checked = false);
  addRoom();
}

function fillForm(data) {
  document.getElementById('hotel_name').value = data.hotel_name || '';
  document.getElementById('address').value    = data.address    || '';
  document.getElementById('check_in').value   = data.check_in   || '';
  document.getElementById('check_out').value  = data.check_out  || '';
  calculateNights();
  document.getElementById('rooms').innerHTML = '';
  (data.rooms?.length ? data.rooms : [{}]).forEach(r => addRoom(r));
  document.getElementById('notes').innerHTML = '';
  (data.notes || []).forEach(n => addNote(n));
  generateQuotation();
  markUnsaved();
}

async function copyBusinessQuotation() {
  if (!state.isSaved) { showToast('Save first before copying', 'error'); return; }
  const biz = document.getElementById('business_preview').value;
  if (!biz.trim()) { showToast('Nothing to copy', 'error'); return; }
  try { await navigator.clipboard.writeText(biz); showToast('Business quotation copied'); }
  catch { showToast('Copy failed', 'error'); }
}

function setSaveStatus(saved) {
  state.isSaved = saved;
  const el = document.getElementById('saveStatus'), txt = el.querySelector('.status-text');
  if (saved) { el.classList.replace('unsaved','saved'); txt.textContent = 'Saved'; document.getElementById('copyBtn').disabled = false; }
  else        { el.classList.replace('saved','unsaved'); txt.textContent = 'Unsaved'; document.getElementById('copyBtn').disabled = true; }
}
function markUnsaved() { setSaveStatus(false); }
document.addEventListener('input', e => { if (!e.target.closest('#clientSearchContainer') && !e.target.closest('#ucptText') && !e.target.matches('#quoteTitle')) markUnsaved(); });
document.addEventListener('change', e => { if (e.target.matches('input[type="radio"],input[type="checkbox"]')) markUnsaved(); });
function showToast(msg, type = 'success') { const t = document.getElementById('toast'); t.textContent = msg; t.className = `toast ${type} show`; setTimeout(() => t.classList.remove('show'), 3000); }

// CLIENT SELECTOR
const GET_ALL_CLIENTS_API = '<?php echo $getAllClientsApi; ?>';
let clientsData = [];
function initClientSelector() {
  loadClients();
  const input = document.getElementById('clientInput'), dropdown = document.getElementById('clientDropdown'), container = document.getElementById('clientSearchContainer');
  let timer;
  input.addEventListener('input', () => {
    if (input.readOnly) return;
    clearTimeout(timer);
    timer = setTimeout(() => {
      const val = input.value.toLowerCase().trim();
      const filtered = val === '' ? clientsData : clientsData.filter(c => c.name?.toLowerCase().includes(val) || c.sys_id?.toLowerCase().includes(val));
      renderClientDropdown(filtered); dropdown.classList.remove('hidden');
    }, 300);
  });
  input.addEventListener('focus', () => { if (input.readOnly) return; renderClientDropdown(clientsData); dropdown.classList.remove('hidden'); });
  document.addEventListener('click', e => { if (!container.contains(e.target)) dropdown.classList.add('hidden'); });
}
function loadClients() { fetch(GET_ALL_CLIENTS_API).then(r=>r.json()).then(d=>{ clientsData = Array.isArray(d.clients)?d.clients:[]; }).catch(()=>{ clientsData=[]; }); }
function renderClientDropdown(list) {
  const dropdown = document.getElementById('clientDropdown');
  dropdown.innerHTML = '';
  if (!list.length) { dropdown.innerHTML = '<li class="px-4 py-3 text-center text-gray-500">No clients found</li>'; return; }
  list.forEach(client => {
    let phone = '';
    try { if (client.phone?.startsWith('{')) phone = JSON.parse(client.phone).primary_no ?? ''; } catch {}
    const li = document.createElement('li');
    li.className = 'px-4 py-3 cursor-pointer hover:bg-purple-50 border-b last:border-b-0';
    li.innerHTML = `<div class="flex items-center"><div class="w-8 h-8 bg-purple-600 rounded-full text-white flex items-center justify-center font-semibold">${client.name?.charAt(0).toUpperCase()?? 'C'}</div><div class="ml-3 flex-1"><div class="font-medium">${client.name}</div><div class="text-xs text-gray-500 flex gap-2"><span>ID: ${client.sys_id}</span>${phone?`<span>📞 ${phone}</span>`:''}</div></div></div>`;
    li.onclick = () => { document.getElementById('clientInput').value = `${client.sys_id} | ${client.name}`; selectedClientSysId = client.sys_id; document.getElementById('clientDropdown').classList.add('hidden'); };
    dropdown.appendChild(li);
  });
}
function setClientDisplay(sysId, name) { selectedClientSysId = sysId || ''; document.getElementById('clientInput').value = sysId && name ? `${sysId} | ${name}` : (sysId||''); }
</script>
</body>
</html>