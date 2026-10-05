<?php
// FILE PATH: /pages/masterdata-visa.php
include_once('./authenticate.php');
$ip_port     = @file_get_contents('../ippath.txt') ?: 'http://103.104.219.3:898';
$visaApi     = $ip_port . 'api/masterdata/visa/endpoints.php';
$countriesApi = $ip_port . 'api/masterdata/countries/endpoints.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Visa Master Data — TravHub</title>
<link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
:root{--accent:#4F46E5;--accent-h:#4338CA;--border:#E2E8F0;--surface:#F8FAFC;--text:#0F172A;--muted:#64748B;}
body{background:var(--surface);}
/* ── Form ── */
.f-label{display:block;font-size:.75rem;font-weight:600;color:#475569;margin-bottom:5px;}
.f-input{width:100%;padding:8px 12px;font-size:.875rem;color:var(--text);border:1.5px solid var(--border);
         border-radius:8px;background:#fff;outline:none;transition:border .15s;}
.f-input:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(79,70,229,.1);}
.f-select{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
          background-repeat:no-repeat;background-position:right 10px center;}
.card{background:#fff;border:1px solid var(--border);border-radius:12px;padding:20px;}
/* ── Main page tabs ── */
.page-tab-btn{padding:10px 22px;border:none;background:none;cursor:pointer;font-size:.875rem;font-weight:600;
              color:var(--muted);border-bottom:2px solid transparent;transition:all .15s;}
.page-tab-btn.active{color:var(--accent);border-color:var(--accent);}
/* ── Simple list rows ── */
.list-row{display:flex;align-items:center;justify-content:space-between;padding:10px 16px;
          background:#fff;border:1px solid var(--border);border-radius:8px;margin-bottom:6px;
          transition:border-color .15s;}
.list-row:hover{border-color:#C7D2FE;}
.badge-on{background:#DCFCE7;color:#15803D;padding:2px 9px;border-radius:999px;font-size:.7rem;font-weight:700;}
.badge-off{background:#FEF3C7;color:#92400E;padding:2px 9px;border-radius:999px;font-size:.7rem;font-weight:700;}
/* ── Service list ── */
.svc-row{display:grid;grid-template-columns:1fr auto auto auto;gap:12px;align-items:center;
         padding:14px 16px;background:#fff;border:1px solid var(--border);border-radius:10px;
         margin-bottom:8px;transition:border-color .15s;}
.svc-row:hover{border-color:#C7D2FE;background:#FAFBFF;}
/* ── Doc profession tabs ── */
.prof-tab-btn{padding:6px 14px;border-radius:6px 6px 0 0;border:1px solid var(--border);
              border-bottom:none;background:#F8FAFC;font-size:.78rem;font-weight:600;cursor:pointer;
              color:var(--muted);transition:all .15s;}
.prof-tab-btn.active{background:#fff;color:var(--accent);border-color:#C7D2FE;}
.prof-tab-panel{display:none;}
.prof-tab-panel.active{display:block;}
/* ── Doc items (drag) ── */
.doc-item{display:flex;align-items:center;gap:8px;padding:8px 10px;background:#F8FAFC;
          border:1px solid var(--border);border-radius:7px;margin-bottom:6px;cursor:grab;}
.doc-item:active{cursor:grabbing;opacity:.7;}
.doc-item.drag-over{border-color:var(--accent);background:#EEF2FF;}
.doc-handle{color:#CBD5E1;font-size:.8rem;cursor:grab;}
/* ── Rich desc sections ── */
.desc-section{border:1.5px solid var(--border);border-radius:10px;padding:14px;background:#fff;
              margin-bottom:10px;position:relative;}
.rich-editor{min-height:80px;border:1.5px solid var(--border);border-radius:8px;padding:8px 12px;
             font-size:.875rem;line-height:1.7;outline:none;background:#fff;color:var(--text);}
.rich-editor:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(79,70,229,.1);}
.editor-toolbar{display:flex;gap:4px;flex-wrap:wrap;padding:5px 8px;background:#F8FAFC;
                border:1.5px solid var(--border);border-bottom:none;border-radius:8px 8px 0 0;}
.editor-toolbar+.rich-editor{border-radius:0 0 8px 8px;}
.ed-btn{padding:3px 7px;border-radius:5px;border:1px solid var(--border);background:#fff;
        font-size:.75rem;cursor:pointer;color:var(--muted);transition:all .15s;}
.ed-btn:hover{background:var(--accent);color:#fff;border-color:var(--accent);}
/* ── Btn variants ── */
.btn-primary{display:inline-flex;align-items:center;gap:6px;padding:8px 18px;background:var(--accent);
             color:#fff;font-size:.875rem;font-weight:600;border-radius:8px;border:none;cursor:pointer;
             transition:background .15s;}
.btn-primary:hover{background:var(--accent-h);}
.btn-ghost{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;background:transparent;
           color:var(--muted);font-size:.875rem;font-weight:600;border-radius:8px;
           border:1px solid var(--border);cursor:pointer;transition:all .15s;}
.btn-ghost:hover{background:#F1F5F9;color:var(--text);}
.btn-sm{padding:5px 10px;font-size:.78rem;border-radius:6px;}
.btn-danger{background:#FEF2F2;color:#DC2626;border:1px solid #FECACA;}
.btn-danger:hover{background:#FEE2E2;}
.add-more-btn{display:inline-flex;align-items:center;gap:5px;font-size:.78rem;font-weight:600;
              color:var(--accent);background:#EEF2FF;border:none;border-radius:6px;padding:5px 10px;cursor:pointer;}
.add-more-btn:hover{background:#E0E7FF;}
/* ── Modal ── */
.modal-bg{position:fixed;inset:0;background:rgba(0,0,0,.5);backdrop-filter:blur(4px);z-index:50;
          display:flex;align-items:flex-start;justify-content:center;padding:20px;overflow-y:auto;}
.modal-box{background:#fff;border-radius:16px;width:100%;max-width:820px;box-shadow:0 20px 60px rgba(0,0,0,.2);margin:auto;}
.modal-header{padding:18px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;}
.modal-body{padding:24px;}
.modal-footer{padding:16px 24px;border-top:1px solid var(--border);display:flex;gap:10px;justify-content:flex-end;}
</style>
</head>
<body>
<?php include '../elements/header.php'; ?>
<?php include '../elements/aside.php'; ?>

<main id="mainContent" class="pt-16 pl-16 mt-16 transition-all duration-300">
<div style="padding:24px;max-width:1200px;">

  <!-- Page header -->
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
    <div>
      <h1 style="font-size:1.4rem;font-weight:700;color:var(--text);margin:0;">🛂 Visa Master Data</h1>
      <p style="font-size:.85rem;color:var(--muted);margin:4px 0 0;">Manage visa types, categories and service catalog</p>
    </div>
  </div>

  <!-- Page tab bar -->
  <div style="display:flex;gap:0;border-bottom:1px solid var(--border);margin-bottom:24px;">
    <button class="page-tab-btn active" data-page-tab="types"    onclick="_switchPageTab('types',this)">Visa Types</button>
    <button class="page-tab-btn"        data-page-tab="categories" onclick="_switchPageTab('categories',this)">Visa Categories</button>
    <button class="page-tab-btn"        data-page-tab="services"   onclick="_switchPageTab('services',this)">Master Visa Services</button>
  </div>

  <!-- ══ TAB: Visa Types ════════════════════════════════════ -->
  <div id="page-tab-types" class="page-tab-panel" style="display:block;">
    <div class="card" style="max-width:560px;">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
        <h3 style="font-size:1rem;font-weight:700;margin:0;color:var(--text);">Visa Types</h3>
        <button class="btn-primary btn-sm" onclick="_openTypeModal()">+ Add Type</button>
      </div>
      <div id="types-list">
        <div style="padding:32px;text-align:center;color:var(--muted);font-size:.85rem;">Loading…</div>
      </div>
    </div>
  </div>

  <!-- ══ TAB: Visa Categories ══════════════════════════════ -->
  <div id="page-tab-categories" class="page-tab-panel" style="display:none;">
    <div class="card" style="max-width:560px;">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
        <h3 style="font-size:1rem;font-weight:700;margin:0;color:var(--text);">Visa Categories</h3>
        <button class="btn-primary btn-sm" onclick="_openCatModal()">+ Add Category</button>
      </div>
      <div id="categories-list">
        <div style="padding:32px;text-align:center;color:var(--muted);font-size:.85rem;">Loading…</div>
      </div>
    </div>
  </div>

  <!-- ══ TAB: Master Visa Services ═════════════════════════ -->
  <div id="page-tab-services" class="page-tab-panel" style="display:none;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
      <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <select id="svc-filter-country" class="f-input f-select" style="width:220px;font-size:.83rem;"
                onchange="_loadServices()">
          <option value="">All Countries</option>
        </select>
        <select id="svc-filter-status" class="f-input f-select" style="width:150px;font-size:.83rem;"
                onchange="_loadServices()">
          <option value="">All Status</option>
          <option value="1">Active</option>
          <option value="0">Inactive</option>
        </select>
      </div>
      <button class="btn-primary" onclick="_openSvcModal()">+ Add Visa Service</button>
    </div>
    <div id="services-list">
      <div style="padding:48px;text-align:center;color:var(--muted);">Loading…</div>
    </div>
  </div>

</div><!-- /max-width -->
</main>

<!-- ════════════════════════════════════════════════════════
     MODALS
════════════════════════════════════════════════════════ -->

<!-- Type modal -->
<div id="type-modal" style="display:none;" class="modal-bg">
  <div class="modal-box" style="max-width:440px;">
    <div class="modal-header">
      <h3 style="font-size:1rem;font-weight:700;margin:0;" id="type-modal-title">Add Visa Type</h3>
      <button onclick="_closeTypeModal()" style="background:none;border:none;cursor:pointer;font-size:1.2rem;color:var(--muted);">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="type-sys-id" value="">
      <label class="f-label">Type Name *</label>
      <input class="f-input" id="type-name" placeholder="e.g. Single Entry" />
    </div>
    <div class="modal-footer">
      <button class="btn-ghost" onclick="_closeTypeModal()">Cancel</button>
      <button class="btn-primary" onclick="_saveType()">Save</button>
    </div>
  </div>
</div>

<!-- Category modal -->
<div id="cat-modal" style="display:none;" class="modal-bg">
  <div class="modal-box" style="max-width:440px;">
    <div class="modal-header">
      <h3 style="font-size:1rem;font-weight:700;margin:0;" id="cat-modal-title">Add Visa Category</h3>
      <button onclick="_closeCatModal()" style="background:none;border:none;cursor:pointer;font-size:1.2rem;color:var(--muted);">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="cat-sys-id" value="">
      <label class="f-label">Category Name *</label>
      <input class="f-input" id="cat-name" placeholder="e.g. Tourist" />
    </div>
    <div class="modal-footer">
      <button class="btn-ghost" onclick="_closeCatModal()">Cancel</button>
      <button class="btn-primary" onclick="_saveCat()">Save</button>
    </div>
  </div>
</div>

<!-- Service modal (full-screen style) -->
<div id="svc-modal" style="display:none;" class="modal-bg">
  <div class="modal-box">
    <div class="modal-header">
      <h3 style="font-size:1rem;font-weight:700;margin:0;" id="svc-modal-title">Add Visa Service</h3>
      <button onclick="_closeSvcModal()" style="background:none;border:none;cursor:pointer;font-size:1.2rem;color:var(--muted);">✕</button>
    </div>
    <div class="modal-body" style="max-height:calc(100vh - 180px);overflow-y:auto;">
      <input type="hidden" id="svc-sys-id" value="">

      <!-- Basic info -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
        <div style="grid-column:1/-1;">
          <label class="f-label">Service Title *</label>
          <input class="f-input" id="svc-title" placeholder="e.g. Dubai Tourist Visa — 30 Days Single Entry" />
        </div>
        <div>
          <label class="f-label">Country *</label>
          <select class="f-input f-select" id="svc-country-sys-id"></select>
        </div>
        <div>
          <label class="f-label">Visa Type *</label>
          <select class="f-input f-select" id="svc-type-sys-id"></select>
        </div>
        <div>
          <label class="f-label">Visa Category *</label>
          <select class="f-input f-select" id="svc-cat-sys-id"></select>
        </div>
        <div>
          <label class="f-label">Duration (days)</label>
          <input class="f-input" type="number" id="svc-duration-days" placeholder="30" min="0" />
        </div>
        <div>
          <label class="f-label">Duration Label</label>
          <input class="f-input" id="svc-duration-label" placeholder="e.g. 30 Days / 1 Year" />
        </div>
        <div>
          <label class="f-label">B2C Price (client) *</label>
          <input class="f-input" type="number" id="svc-b2c-price" placeholder="6000" min="0" step="0.01" />
        </div>
        <div>
          <label class="f-label">Purchase Price (vendor)</label>
          <input class="f-input" type="number" id="svc-purchase-price" placeholder="4500" min="0" step="0.01" />
        </div>
        <div>
          <label class="f-label">Currency</label>
          <select class="f-input f-select" id="svc-currency">
            <option value="BDT">BDT</option>
            <option value="USD">USD</option>
            <option value="EUR">EUR</option>
            <option value="AED">AED</option>
            <option value="SAR">SAR</option>
          </select>
        </div>
        <div>
          <label class="f-label">Max File Size per Doc (KB)</label>
          <input class="f-input" type="number" id="svc-file-size" value="300" min="50" max="5000" />
        </div>
      </div>

      <hr style="border:none;border-top:1px solid var(--border);margin:18px 0;">

      <!-- Required Documents per profession -->
      <div style="margin-bottom:18px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
          <label class="f-label" style="margin:0;">Required Documents <span style="color:var(--muted);font-weight:400;">(per profession)</span></label>
        </div>
        <!-- Profession tabs -->
        <div style="display:flex;gap:0;flex-wrap:wrap;margin-bottom:-1px;">
          <button class="prof-tab-btn active" data-prof="general"        onclick="_switchProfTab('general',this)">General</button>
          <button class="prof-tab-btn"        data-prof="student"        onclick="_switchProfTab('student',this)">Student</button>
          <button class="prof-tab-btn"        data-prof="employment"     onclick="_switchProfTab('employment',this)">Employment</button>
          <button class="prof-tab-btn"        data-prof="businessman"    onclick="_switchProfTab('businessman',this)">Businessman</button>
          <button class="prof-tab-btn"        data-prof="non_employment" onclick="_switchProfTab('non_employment',this)">Non-Employment</button>
        </div>
        <div style="border:1px solid var(--border);border-radius:0 8px 8px 8px;padding:14px;background:#FAFBFF;">
          <div id="prof-tab-general"        class="prof-tab-panel active"  data-prof-key="general"></div>
          <div id="prof-tab-student"        class="prof-tab-panel"         data-prof-key="student"></div>
          <div id="prof-tab-employment"     class="prof-tab-panel"         data-prof-key="employment"></div>
          <div id="prof-tab-businessman"    class="prof-tab-panel"         data-prof-key="businessman"></div>
          <div id="prof-tab-non_employment" class="prof-tab-panel"         data-prof-key="non_employment"></div>
        </div>
      </div>

      <hr style="border:none;border-top:1px solid var(--border);margin:18px 0;">

      <!-- Description sections -->
      <div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
          <label class="f-label" style="margin:0;">Description Sections <span style="color:var(--muted);font-weight:400;">(rich text)</span></label>
          <button class="add-more-btn" onclick="_addDescSection()">+ Add Section</button>
        </div>
        <div id="desc-sections-wrap"></div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-ghost" onclick="_closeSvcModal()">Cancel</button>
      <button class="btn-primary" onclick="_saveSvc()">
        <i class="fa fa-save"></i> Save Service
      </button>
    </div>
  </div>
</div>

<script>
const VISA_API     = '<?php echo $visaApi; ?>';
const COUNTRY_API  = '<?php echo $countriesApi; ?>';

// ── shared lookup data
let _types      = [];
let _categories = [];
let _countries  = [];

// ── Page tab switch ───────────────────────────────────────
function _switchPageTab(tab, btn) {
    document.querySelectorAll('.page-tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.page-tab-panel').forEach(p => p.style.display = 'none');
    if (btn) btn.classList.add('active');
    document.getElementById('page-tab-' + tab).style.display = 'block';

    if (tab === 'types')      _loadTypes();
    if (tab === 'categories') _loadCategories();
    if (tab === 'services')   _loadServices();
}

// ═══════════════════════════════════════════════════════════
// VISA TYPES
// ═══════════════════════════════════════════════════════════

async function _loadTypes() {
    const el = document.getElementById('types-list');
    el.innerHTML = '<div style="padding:24px;text-align:center;color:var(--muted);">Loading…</div>';
    const res = await fetch(`${VISA_API}?action=list_types`);
    const j   = await res.json();
    _types = j.data ?? [];
    if (!_types.length) {
        el.innerHTML = '<div style="padding:24px;text-align:center;color:var(--muted);">No types yet</div>';
        return;
    }
    el.innerHTML = _types.map(t => `
        <div class="list-row">
            <span style="font-size:.9rem;font-weight:600;color:var(--text);">${t.name}</span>
            <div style="display:flex;gap:8px;align-items:center;">
                <span class="${t.status == 1 ? 'badge-on' : 'badge-off'}">${t.status == 1 ? 'Active' : 'Inactive'}</span>
                <button class="btn-ghost btn-sm" onclick="_openTypeModal(${JSON.stringify(t).replace(/"/g,"&quot;")})">Edit</button>
                <button class="btn-ghost btn-sm" onclick="_toggleType('${t.sys_id}')">Toggle</button>
                <button class="btn-ghost btn-sm btn-danger" onclick="_deleteType('${t.sys_id}')">Delete</button>
            </div>
        </div>
    `).join('');
}

function _openTypeModal(t) {
    document.getElementById('type-modal-title').textContent = t ? 'Edit Visa Type' : 'Add Visa Type';
    document.getElementById('type-sys-id').value = t?.sys_id ?? '';
    document.getElementById('type-name').value   = t?.name   ?? '';
    document.getElementById('type-modal').style.display = 'flex';
}
function _closeTypeModal() { document.getElementById('type-modal').style.display = 'none'; }

async function _saveType() {
    const sysId = document.getElementById('type-sys-id').value.trim();
    const name  = document.getElementById('type-name').value.trim();
    if (!name) return alert('Name is required');
    const res = await fetch(VISA_API, { method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ action:'save_type', sys_id:sysId, name })
    });
    const j = await res.json();
    if (j.status !== 'success') return alert(j.message);
    _closeTypeModal();
    _loadTypes();
}

async function _toggleType(sysId) {
    const res = await fetch(VISA_API, { method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ action:'toggle_type', sys_id:sysId })
    });
    const j = await res.json();
    if (j.status !== 'success') return alert(j.message);
    _loadTypes();
}

async function _deleteType(sysId) {
    if (!confirm('Delete this visa type?')) return;
    const res = await fetch(VISA_API, { method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ action:'delete_type', sys_id:sysId })
    });
    const j = await res.json();
    if (j.status !== 'success') return alert(j.message);
    _loadTypes();
}

// ═══════════════════════════════════════════════════════════
// VISA CATEGORIES
// ═══════════════════════════════════════════════════════════

async function _loadCategories() {
    const el = document.getElementById('categories-list');
    el.innerHTML = '<div style="padding:24px;text-align:center;color:var(--muted);">Loading…</div>';
    const res = await fetch(`${VISA_API}?action=list_categories`);
    const j   = await res.json();
    _categories = j.data ?? [];
    if (!_categories.length) {
        el.innerHTML = '<div style="padding:24px;text-align:center;color:var(--muted);">No categories yet</div>';
        return;
    }
    el.innerHTML = _categories.map(c => `
        <div class="list-row">
            <span style="font-size:.9rem;font-weight:600;color:var(--text);">${c.name}</span>
            <div style="display:flex;gap:8px;align-items:center;">
                <span class="${c.status == 1 ? 'badge-on' : 'badge-off'}">${c.status == 1 ? 'Active' : 'Inactive'}</span>
                <button class="btn-ghost btn-sm" onclick="_openCatModal(${JSON.stringify(c).replace(/"/g,"&quot;")})">Edit</button>
                <button class="btn-ghost btn-sm" onclick="_toggleCat('${c.sys_id}')">Toggle</button>
                <button class="btn-ghost btn-sm btn-danger" onclick="_deleteCat('${c.sys_id}')">Delete</button>
            </div>
        </div>
    `).join('');
}

function _openCatModal(c) {
    document.getElementById('cat-modal-title').textContent = c ? 'Edit Category' : 'Add Visa Category';
    document.getElementById('cat-sys-id').value = c?.sys_id ?? '';
    document.getElementById('cat-name').value   = c?.name   ?? '';
    document.getElementById('cat-modal').style.display = 'flex';
}
function _closeCatModal() { document.getElementById('cat-modal').style.display = 'none'; }

async function _saveCat() {
    const sysId = document.getElementById('cat-sys-id').value.trim();
    const name  = document.getElementById('cat-name').value.trim();
    if (!name) return alert('Name is required');
    const res = await fetch(VISA_API, { method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ action:'save_category', sys_id:sysId, name })
    });
    const j = await res.json();
    if (j.status !== 'success') return alert(j.message);
    _closeCatModal();
    _loadCategories();
}

async function _toggleCat(sysId) {
    const res = await fetch(VISA_API, { method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ action:'toggle_category', sys_id:sysId })
    });
    const j = await res.json();
    if (j.status !== 'success') return alert(j.message);
    _loadCategories();
}

async function _deleteCat(sysId) {
    if (!confirm('Delete this category?')) return;
    const res = await fetch(VISA_API, { method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ action:'delete_category', sys_id:sysId })
    });
    const j = await res.json();
    if (j.status !== 'success') return alert(j.message);
    _loadCategories();
}

// ═══════════════════════════════════════════════════════════
// MASTER VISA SERVICES
// ═══════════════════════════════════════════════════════════

async function _loadServices() {
    const el = document.getElementById('services-list');
    el.innerHTML = '<div style="padding:48px;text-align:center;color:var(--muted);">Loading…</div>';

    const country = document.getElementById('svc-filter-country').value;
    const status  = document.getElementById('svc-filter-status').value;

    let url = `${VISA_API}?action=list_services`;
    if (country) url += `&country_sys_id=${encodeURIComponent(country)}`;
    if (status !== '') url += `&status=${status}`;

    const res = await fetch(url);
    const j   = await res.json();
    const rows = j.data ?? [];

    if (!rows.length) {
        el.innerHTML = '<div style="padding:48px;text-align:center;color:var(--muted);">No services found</div>';
        return;
    }

    el.innerHTML = rows.map(s => `
        <div class="svc-row">
            <div>
                <div style="font-size:.9rem;font-weight:700;color:var(--text);">${s.title}</div>
                <div style="font-size:.78rem;color:var(--muted);margin-top:3px;">
                    🌍 ${s.country_name} &nbsp;·&nbsp;
                    📋 ${s.visa_category_name ?? '—'} &nbsp;·&nbsp;
                    🔑 ${s.visa_type_name ?? '—'} &nbsp;·&nbsp;
                    ⏱ ${s.duration_label || (s.duration_days + ' days')}
                </div>
            </div>
            <div style="text-align:right;">
                <div style="font-size:.85rem;font-weight:700;color:var(--text);">${Number(s.b2c_price).toLocaleString()} ${s.currency}</div>
                <div style="font-size:.73rem;color:var(--muted);">Cost: ${Number(s.purchase_price).toLocaleString()}</div>
            </div>
            <span class="${s.status == 1 ? 'badge-on' : 'badge-off'}">${s.status == 1 ? 'Active' : 'Inactive'}</span>
            <div style="display:flex;gap:6px;">
                <button class="btn-ghost btn-sm" onclick="_openSvcModal('${s.sys_id}')">Edit</button>
                <button class="btn-ghost btn-sm" onclick="_toggleSvc('${s.sys_id}')">Toggle</button>
                <button class="btn-ghost btn-sm btn-danger" onclick="_deleteSvc('${s.sys_id}')">Delete</button>
            </div>
        </div>
    `).join('');
}

// ── profession tab state
let _activeProfTab = 'general';
// doc items per profession: { general:[], student:[], ... }
let _profDocs = { general:[], student:[], employment:[], businessman:[], non_employment:[] };
// description sections: [{ title:'', body:'' }]
let _descSections = [];

function _switchProfTab(prof, btn) {
    _activeProfTab = prof;
    document.querySelectorAll('.prof-tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.prof-tab-panel').forEach(p => p.classList.remove('active'));
    if (btn) btn.classList.add('active');
    document.getElementById('prof-tab-' + prof).classList.add('active');
}

function _renderProfDocs(prof) {
    const el = document.getElementById('prof-tab-' + prof);
    const items = _profDocs[prof] ?? [];
    const rows = items.map((d, i) => `
        <div class="doc-item" data-prof="${prof}" data-idx="${i}"
             draggable="true"
             ondragstart="_docDragStart(event)"
             ondragover="_docDragOver(event)"
             ondrop="_docDrop(event)">
            <span class="doc-handle"><i class="fa fa-grip-vertical"></i></span>
            <span style="font-size:.75rem;color:var(--muted);min-width:20px;">${i+1}.</span>
            <input type="text" value="${_esc(d.doc)}"
                   style="flex:1;border:none;outline:none;font-size:.85rem;background:transparent;"
                   onchange="_profDocChange('${prof}', ${i}, 'doc', this.value)" />
            <input type="text" value="${_esc(d.note ?? '')}" placeholder="note (optional)"
                   style="width:180px;border:none;outline:none;font-size:.78rem;color:var(--muted);background:transparent;"
                   onchange="_profDocChange('${prof}', ${i}, 'note', this.value)" />
            <button onclick="_removeDoc('${prof}', ${i})"
                    style="background:none;border:none;cursor:pointer;color:#EF4444;font-size:.85rem;padding:2px 4px;">
                <i class="fa fa-times"></i>
            </button>
        </div>
    `).join('');

    el.innerHTML = rows + `
        <button class="add-more-btn" style="margin-top:6px;" onclick="_addDoc('${prof}')">
            + Add Document
        </button>`;
}

function _addDoc(prof) {
    _profDocs[prof].push({ seq: _profDocs[prof].length + 1, doc: '', note: '' });
    _renderProfDocs(prof);
}

function _removeDoc(prof, idx) {
    _profDocs[prof].splice(idx, 1);
    _renderProfDocs(prof);
}

function _profDocChange(prof, idx, key, val) {
    if (_profDocs[prof][idx]) _profDocs[prof][idx][key] = val;
}

// drag & drop reorder
let _dragSrcProf = null, _dragSrcIdx = null;
function _docDragStart(e) {
    _dragSrcProf = e.currentTarget.dataset.prof;
    _dragSrcIdx  = parseInt(e.currentTarget.dataset.idx);
    e.dataTransfer.effectAllowed = 'move';
}
function _docDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    document.querySelectorAll('.doc-item').forEach(el => el.classList.remove('drag-over'));
    e.currentTarget.classList.add('drag-over');
}
function _docDrop(e) {
    e.preventDefault();
    const tgtProf = e.currentTarget.dataset.prof;
    const tgtIdx  = parseInt(e.currentTarget.dataset.idx);
    if (_dragSrcProf !== tgtProf || _dragSrcIdx === tgtIdx) {
        document.querySelectorAll('.doc-item').forEach(el => el.classList.remove('drag-over'));
        return;
    }
    const arr = _profDocs[tgtProf];
    const [moved] = arr.splice(_dragSrcIdx, 1);
    arr.splice(tgtIdx, 0, moved);
    _renderProfDocs(tgtProf);
}

// ── description sections
function _renderDescSections() {
    const wrap = document.getElementById('desc-sections-wrap');
    if (!_descSections.length) {
        wrap.innerHTML = '<div style="text-align:center;padding:16px;color:var(--muted);font-size:.85rem;">No sections yet. Click "+ Add Section".</div>';
        return;
    }
    wrap.innerHTML = _descSections.map((s, i) => `
        <div class="desc-section">
            <button onclick="_removeDescSection(${i})"
                    style="position:absolute;top:10px;right:10px;background:none;border:none;cursor:pointer;color:#EF4444;font-size:.85rem;">
                <i class="fa fa-times"></i>
            </button>
            <div style="margin-bottom:8px;">
                <label class="f-label">Section Title</label>
                <input class="f-input" value="${_esc(s.title)}" placeholder="e.g. Processing Time"
                       onchange="_descChange(${i}, 'title', this.value)" style="font-size:.85rem;" />
            </div>
            <div class="editor-toolbar" id="desc-toolbar-${i}">
                <button class="ed-btn" onclick="_edCmd('bold','desc-body-${i}')"><b>B</b></button>
                <button class="ed-btn" onclick="_edCmd('italic','desc-body-${i}')"><i>I</i></button>
                <button class="ed-btn" onclick="_edCmd('underline','desc-body-${i}')"><u>U</u></button>
                <button class="ed-btn" onclick="_edCmd('insertUnorderedList','desc-body-${i}')">• List</button>
                <button class="ed-btn" onclick="_edCmd('insertOrderedList','desc-body-${i}')">1. List</button>
            </div>
            <div class="rich-editor" id="desc-body-${i}" contenteditable="true"
                 oninput="_descChange(${i}, 'body', document.getElementById('desc-body-${i}').innerHTML)"
                 style="min-height:70px;"></div>
        </div>
    `).join('');

    // Fill body HTML
    _descSections.forEach((s, i) => {
        const el = document.getElementById('desc-body-' + i);
        if (el && s.body) el.innerHTML = s.body;
    });
}

function _addDescSection() {
    _descSections.push({ title: '', body: '' });
    _renderDescSections();
}

function _removeDescSection(i) {
    _descSections.splice(i, 1);
    _renderDescSections();
}

function _descChange(i, key, val) {
    if (_descSections[i]) _descSections[i][key] = val;
}

function _edCmd(cmd, edId) {
    document.getElementById(edId).focus();
    document.execCommand(cmd, false, null);
}

// ── open service modal
async function _openSvcModal(sysId) {
    // Reset state
    _profDocs    = { general:[], student:[], employment:[], businessman:[], non_employment:[] };
    _descSections = [];
    _activeProfTab = 'general';

    document.getElementById('svc-sys-id').value        = '';
    document.getElementById('svc-title').value         = '';
    document.getElementById('svc-duration-days').value = '';
    document.getElementById('svc-duration-label').value= '';
    document.getElementById('svc-b2c-price').value     = '';
    document.getElementById('svc-purchase-price').value= '';
    document.getElementById('svc-currency').value      = 'BDT';
    document.getElementById('svc-file-size').value     = '300';

    // Populate dropdowns (types/cats/countries)
    await _ensureLookups();
    _fillCountryDropdown('svc-country-sys-id');
    _fillTypeDropdown('svc-type-sys-id');
    _fillCatDropdown('svc-cat-sys-id');

    document.getElementById('svc-modal-title').textContent = sysId ? 'Edit Visa Service' : 'Add Visa Service';

    if (sysId) {
        // Load existing
        const res = await fetch(`${VISA_API}?action=get_service&sys_id=${encodeURIComponent(sysId)}`);
        const j   = await res.json();
        if (j.status === 'success' && j.data) {
            const d = j.data;
            document.getElementById('svc-sys-id').value         = d.sys_id;
            document.getElementById('svc-title').value          = d.title;
            document.getElementById('svc-country-sys-id').value = d.country_sys_id;
            document.getElementById('svc-type-sys-id').value    = d.visa_type_sys_id;
            document.getElementById('svc-cat-sys-id').value     = d.visa_category_sys_id;
            document.getElementById('svc-duration-days').value  = d.duration_days;
            document.getElementById('svc-duration-label').value = d.duration_label;
            document.getElementById('svc-b2c-price').value      = d.b2c_price;
            document.getElementById('svc-purchase-price').value = d.purchase_price;
            document.getElementById('svc-currency').value       = d.currency;
            document.getElementById('svc-file-size').value      = d.file_size_limit_kb;

            // required_documents
            const rd = d.required_documents ?? {};
            Object.keys(_profDocs).forEach(p => {
                _profDocs[p] = Array.isArray(rd[p]) ? rd[p] : [];
            });

            // description
            _descSections = Array.isArray(d.description) ? d.description : [];
        }
    }

    // Render
    Object.keys(_profDocs).forEach(p => _renderProfDocs(p));
    _renderDescSections();
    _switchProfTab('general', document.querySelector('.prof-tab-btn[data-prof="general"]'));

    document.getElementById('svc-modal').style.display = 'flex';
}

function _closeSvcModal() {
    document.getElementById('svc-modal').style.display = 'none';
}

async function _saveSvc() {
    // Collect doc data from inputs (they may have been typed but not triggered onchange)
    Object.keys(_profDocs).forEach(prof => {
        const panel = document.getElementById('prof-tab-' + prof);
        panel.querySelectorAll('.doc-item').forEach((el, i) => {
            const inputs = el.querySelectorAll('input[type="text"]');
            if (inputs[0]) _profDocs[prof][i].doc  = inputs[0].value;
            if (inputs[1]) _profDocs[prof][i].note = inputs[1].value;
        });
    });

    // Collect desc bodies
    _descSections.forEach((s, i) => {
        const el = document.getElementById('desc-body-' + i);
        if (el) s.body = el.innerHTML;
        const ti = document.querySelector(`#desc-sections-wrap .desc-section:nth-child(${i+1}) input`);
        if (ti) s.title = ti.value;
    });

    const country = document.getElementById('svc-country-sys-id');
    const payload = {
        action:              'save_service',
        sys_id:              document.getElementById('svc-sys-id').value,
        title:               document.getElementById('svc-title').value.trim(),
        country_sys_id:      country.value,
        country_name:        country.options[country.selectedIndex]?.text ?? '',
        visa_type_sys_id:    document.getElementById('svc-type-sys-id').value,
        visa_category_sys_id: document.getElementById('svc-cat-sys-id').value,
        duration_days:       parseInt(document.getElementById('svc-duration-days').value) || 0,
        duration_label:      document.getElementById('svc-duration-label').value.trim(),
        b2c_price:           parseFloat(document.getElementById('svc-b2c-price').value) || 0,
        purchase_price:      parseFloat(document.getElementById('svc-purchase-price').value) || 0,
        currency:            document.getElementById('svc-currency').value,
        file_size_limit_kb:  parseInt(document.getElementById('svc-file-size').value) || 300,
        required_documents:  _profDocs,
        description:         _descSections,
    };

    if (!payload.title)            return alert('Title is required');
    if (!payload.country_sys_id)   return alert('Country is required');
    if (!payload.visa_type_sys_id) return alert('Visa type is required');
    if (!payload.visa_category_sys_id) return alert('Visa category is required');

    const btn = document.querySelector('#svc-modal .btn-primary');
    btn.disabled = true;
    btn.textContent = 'Saving…';

    const res = await fetch(VISA_API, { method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify(payload)
    });
    const j = await res.json();
    btn.disabled = false;
    btn.innerHTML = '<i class="fa fa-save"></i> Save Service';

    if (j.status !== 'success') return alert(j.message || 'Save failed');
    _closeSvcModal();
    _loadServices();
}

async function _toggleSvc(sysId) {
    const res = await fetch(VISA_API, { method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ action:'toggle_service', sys_id:sysId })
    });
    const j = await res.json();
    if (j.status !== 'success') return alert(j.message);
    _loadServices();
}

async function _deleteSvc(sysId) {
    if (!confirm('Delete this visa service? This cannot be undone.')) return;
    const res = await fetch(VISA_API, { method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ action:'delete_service', sys_id:sysId })
    });
    const j = await res.json();
    if (j.status !== 'success') return alert(j.message);
    _loadServices();
}

// ═══════════════════════════════════════════════════════════
// LOOKUP HELPERS
// ═══════════════════════════════════════════════════════════

async function _ensureLookups() {
    const promises = [];
    if (!_types.length)     promises.push(fetch(`${VISA_API}?action=list_types`).then(r=>r.json()).then(j=>{ _types=j.data??[]; }));
    if (!_categories.length) promises.push(fetch(`${VISA_API}?action=list_categories`).then(r=>r.json()).then(j=>{ _categories=j.data??[]; }));
    if (!_countries.length)  promises.push(fetch(`${COUNTRY_API}?action=all`).then(r=>r.json()).then(j=>{ _countries=j.data??j??[]; }));
    await Promise.all(promises);
}

function _fillCountryDropdown(id) {
    const sel = document.getElementById(id);
    const cur = sel.value;
    sel.innerHTML = '<option value="">Select Country</option>' +
        _countries.map(c => `<option value="${c.sys_id}">${c.name ?? c.country_name ?? c.country}</option>`).join('');
    if (cur) sel.value = cur;
}

function _fillTypeDropdown(id) {
    const sel = document.getElementById(id);
    sel.innerHTML = '<option value="">Select Type</option>' +
        _types.filter(t=>t.status==1).map(t=>`<option value="${t.sys_id}">${t.name}</option>`).join('');
}

function _fillCatDropdown(id) {
    const sel = document.getElementById(id);
    sel.innerHTML = '<option value="">Select Category</option>' +
        _categories.filter(c=>c.status==1).map(c=>`<option value="${c.sys_id}">${c.name}</option>`).join('');
}

// Populate country filter on services tab
async function _populateCountryFilter() {
    await _ensureLookups();
    const sel = document.getElementById('svc-filter-country');
    sel.innerHTML = '<option value="">All Countries</option>' +
        _countries.map(c=>`<option value="${c.sys_id}">${c.name ?? c.country_name ?? c.country}</option>`).join('');
}

function _esc(str) {
    return String(str ?? '').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ── Boot
(async function() {
    await _ensureLookups();
    _populateCountryFilter();
    _loadTypes();
})();
</script>
</body>
</html>