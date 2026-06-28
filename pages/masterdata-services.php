<?php
// FILE PATH: /pages/masterdata-services.php
include_once('./authenticate.php');
$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) { $ip_port = "http://103.104.219.3:898"; }
$servicesApi = $ip_port . "api/masterdata/services/endpoints.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services — TravHub</title>
    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png" sizes="16x16">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root { --accent:#4F46E5; --accent-h:#4338CA; --border:#E2E8F0; --surface:#F8FAFC; --text:#0F172A; --muted:#64748B; }
        body { background:var(--surface); }

        /* ── Stat cards ── */
        .stat-card {
            background:#fff; border:1px solid var(--border); border-radius:10px;
            padding:16px 20px; border-left:3px solid transparent;
        }
        .stat-card.c-all    { border-left-color:#94A3B8; }
        .stat-card.c-active { border-left-color:#22c55e; }
        .stat-card.c-off    { border-left-color:#CBD5E1; }
        .stat-card.c-field  { border-left-color:var(--accent); }
        .stat-num   { font-size:1.6rem; font-weight:800; color:var(--text); line-height:1; }
        .stat-label { font-size:.7rem; font-weight:600; letter-spacing:.06em; text-transform:uppercase; color:var(--muted); margin-top:4px; }

        /* ── Toolbar ── */
        .toolbar { background:#fff; border:1px solid var(--border); border-radius:10px; padding:12px 16px; display:flex; flex-wrap:wrap; gap:10px; align-items:center; }
        .tb-input {
            flex:1; min-width:160px; padding:8px 12px 8px 34px; font-size:.83rem;
            border:1.5px solid var(--border); border-radius:8px; outline:none;
            transition:border .15s; color:var(--text); background:#fff;
        }
        .tb-input:focus { border-color:var(--accent); box-shadow:0 0 0 3px rgba(79,70,229,.08); }
        .tb-select {
            padding:8px 12px; font-size:.83rem; border:1.5px solid var(--border);
            border-radius:8px; outline:none; color:var(--muted); background:#fff; cursor:pointer;
        }
        .tb-select:focus { border-color:var(--accent); }
        .tb-icon-btn {
            width:36px; height:36px; border-radius:8px; border:1.5px solid var(--border);
            background:#fff; color:var(--muted); display:flex; align-items:center;
            justify-content:center; cursor:pointer; transition:all .15s; font-size:.85rem;
        }
        .tb-icon-btn:hover { border-color:#94A3B8; color:var(--text); }

        /* ── Service cards ── */
        .svc-card {
            background:#fff; border:1px solid var(--border); border-radius:10px;
            padding:18px; transition:all .18s; position:relative; overflow:hidden;
        }
        .svc-card::before {
            content:''; position:absolute; top:0; left:0; right:0; height:3px;
            background:var(--card-accent, #E2E8F0); transition:background .2s;
        }
        .svc-card:hover { border-color:#C7D2FE; box-shadow:0 4px 16px rgba(79,70,229,.08); transform:translateY(-1px); }
        .svc-card.inactive { opacity:.6; }

        /* ── Icon circle ── */
        .svc-icon-wrap {
            width:40px; height:40px; border-radius:10px;
            display:flex; align-items:center; justify-content:center;
            font-size:1.1rem; color:#fff; flex-shrink:0;
        }

        /* ── Toggle switch ── */
        .toggle-wrap { position:relative; width:36px; height:20px; flex-shrink:0; }
        .toggle-wrap input { opacity:0; width:0; height:0; }
        .toggle-track {
            position:absolute; inset:0; border-radius:999px; background:#E2E8F0;
            cursor:pointer; transition:background .2s;
        }
        .toggle-wrap input:checked + .toggle-track { background:#22c55e; }
        .toggle-thumb {
            position:absolute; width:14px; height:14px; border-radius:50%;
            background:#fff; top:3px; left:3px; transition:transform .2s;
            box-shadow:0 1px 3px rgba(0,0,0,.15);
        }
        .toggle-wrap input:checked ~ .toggle-thumb { transform:translateX(16px); }

        /* ── Slug badge ── */
        .slug-badge {
            display:inline-block; padding:2px 8px; border-radius:4px;
            font-size:.65rem; font-weight:700; letter-spacing:.04em;
            font-family:monospace; background:#F1F5F9; color:#64748B;
        }

        /* ── Action buttons ── */
        .act-btn {
            width:30px; height:30px; border-radius:6px; border:1px solid transparent;
            display:flex; align-items:center; justify-content:center;
            cursor:pointer; font-size:.78rem; transition:all .15s;
        }
        .act-btn.edit   { color:#D97706; background:#FFFBEB; border-color:#FDE68A; }
        .act-btn.edit:hover { background:#FEF3C7; }
        .act-btn.del    { color:#DC2626; background:#FEF2F2; border-color:#FECACA; }
        .act-btn.del:hover { background:#FEE2E2; }

        /* ── Modal ── */
        .modal-bg { position:fixed; inset:0; background:rgba(15,23,42,.4); backdrop-filter:blur(3px); z-index:50; display:flex; align-items:flex-start; justify-content:center; padding:20px; overflow-y:auto; }
        .modal-box { background:#fff; border-radius:14px; width:100%; max-width:480px; margin:auto; box-shadow:0 25px 60px rgba(0,0,0,.15); }
        .modal-header { padding:18px 22px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; }
        .modal-title { font-size:.95rem; font-weight:700; color:var(--text); }
        .modal-close { width:30px; height:30px; border-radius:6px; border:none; background:transparent; color:var(--muted); cursor:pointer; display:flex; align-items:center; justify-content:center; transition:background .15s; }
        .modal-close:hover { background:#F1F5F9; color:var(--text); }
        .modal-body { padding:20px 22px; }
        .modal-footer { padding:14px 22px; border-top:1px solid var(--border); display:flex; justify-content:flex-end; gap:10px; }

        /* ── Form ── */
        .f-label { display:block; font-size:.72rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase; color:var(--muted); margin-bottom:5px; }
        .f-input { width:100%; padding:9px 12px; font-size:.875rem; color:var(--text); border:1.5px solid var(--border); border-radius:8px; background:#fff; outline:none; transition:border .15s; }
        .f-input:focus { border-color:var(--accent); box-shadow:0 0 0 3px rgba(79,70,229,.08); }

        /* ── Icon grid ── */
        .icon-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(38px,1fr)); gap:5px; max-height:160px; overflow-y:auto; padding:8px; background:#F8FAFC; border:1px solid var(--border); border-radius:8px; }
        .icon-btn { width:38px; height:38px; border-radius:7px; border:1.5px solid transparent; display:flex; align-items:center; justify-content:center; cursor:pointer; transition:all .12s; font-size:.95rem; color:var(--muted); background:#fff; }
        .icon-btn:hover { border-color:#C7D2FE; color:var(--accent); background:#EEF2FF; }
        .icon-btn.selected { border-color:var(--accent); background:#EEF2FF; color:var(--accent); }

        /* ── Color picker ── */
        .color-dot { width:26px; height:26px; border-radius:50%; cursor:pointer; transition:transform .12s; border:2px solid transparent; }
        .color-dot:hover { transform:scale(1.15); }
        .color-dot.selected { outline:2.5px solid var(--accent); outline-offset:2px; }

        /* ── Primary/secondary buttons ── */
        .btn-primary { padding:9px 20px; border-radius:8px; background:var(--accent); color:#fff; font-size:.82rem; font-weight:700; border:none; cursor:pointer; transition:background .15s; letter-spacing:.03em; }
        .btn-primary:hover { background:var(--accent-h); }
        .btn-primary:disabled { opacity:.6; cursor:not-allowed; }
        .btn-ghost  { padding:9px 16px; border-radius:8px; background:#fff; color:var(--muted); font-size:.82rem; font-weight:600; border:1.5px solid var(--border); cursor:pointer; transition:all .15s; }
        .btn-ghost:hover { border-color:#94A3B8; color:var(--text); }

        /* ── Empty / loader ── */
        .empty-state { text-align:center; padding:48px 20px; color:var(--muted); }
        .empty-state i { font-size:2rem; opacity:.3; display:block; margin-bottom:8px; }

        /* ── Delete confirm ── */
        .del-icon { width:56px; height:56px; border-radius:50%; background:#FEF2F2; display:flex; align-items:center; justify-content:center; margin:0 auto 12px; }

        /* ── Toast ── */
        #toast { position:fixed; bottom:24px; right:24px; z-index:9999; }

        @keyframes fadeUp { from{opacity:0;transform:translateY(8px);} to{opacity:1;transform:translateY(0);} }
        .fade-up { animation:fadeUp .2s ease; }
    </style>
</head>
<body>

<?php include '../elements/header.php'; ?>
<?php include '../elements/aside.php'; ?>

<main id="mainContent" class="pt-16 pl-16 mt-16 transition-all duration-300">
<div class="p-4 md:p-6 max-w-5xl mx-auto">

    <!-- ── Header ── -->
    <div class="flex flex-wrap items-end justify-between gap-4 mb-6">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-indigo-500 mb-1">Master Data</p>
            <h1 class="text-2xl font-extrabold text-slate-900">Services</h1>
            <p class="text-sm text-slate-400 mt-0.5">Manage service types available in the lead module</p>
        </div>
        <button onclick="openModal()" class="flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-bold text-white transition shadow-sm" style="background:var(--accent);" onmouseover="this.style.background='var(--accent-h)'" onmouseout="this.style.background='var(--accent)'">
            <i class="fas fa-plus text-xs"></i> Add Service
        </button>
    </div>

    <!-- ── Stats ── -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
        <div class="stat-card c-all">
            <div class="stat-num" id="statTotal">—</div>
            <div class="stat-label">Total</div>
        </div>
        <div class="stat-card c-active">
            <div class="stat-num text-emerald-600" id="statActive">—</div>
            <div class="stat-label">Active</div>
        </div>
        <div class="stat-card c-off">
            <div class="stat-num text-slate-400" id="statInactive">—</div>
            <div class="stat-label">Inactive</div>
        </div>
        <div class="stat-card c-field">
            <div class="stat-num text-indigo-600" id="statFields">—</div>
            <div class="stat-label">With Fields</div>
        </div>
    </div>

    <!-- ── Toolbar ── -->
    <div class="toolbar mb-5">
        <div class="relative flex-1 min-w-[160px]">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
            <input type="text" id="searchInput" placeholder="Search services…" class="tb-input" oninput="renderGrid()">
        </div>
        <select id="filterActive" onchange="renderGrid()" class="tb-select">
            <option value="">All Status</option>
            <option value="1">Active</option>
            <option value="0">Inactive</option>
        </select>
        <button onclick="loadServices()" class="tb-icon-btn" title="Refresh">
            <i class="fas fa-rotate-right text-xs"></i>
        </button>
    </div>

    <!-- ── Grid ── -->
    <div id="servicesGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <div class="col-span-full empty-state"><i class="fas fa-spinner fa-spin"></i>Loading…</div>
    </div>

</div>
</main>

<!-- ══ ADD / EDIT MODAL ══ -->
<div id="svcModal" class="modal-bg hidden">
    <div class="modal-box fade-up">
        <div class="modal-header">
            <span id="modalTitle" class="modal-title">Add Service</span>
            <button onclick="closeModal()" class="modal-close"><i class="fas fa-times text-sm"></i></button>
        </div>
        <div class="modal-body space-y-4">

            <div>
                <label class="f-label">Service Name <span class="text-red-400">*</span></label>
                <input type="text" id="fName" placeholder="e.g. Air Ticket" class="f-input">
            </div>

            <div>
                <label class="f-label">Icon</label>
                <div class="flex gap-2 mb-2">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center text-white flex-shrink-0" id="iconPreview" style="background:var(--accent)">
                        <i id="iconPreviewI" class="fas fa-circle text-sm"></i>
                    </div>
                    <input type="text" id="fIcon" value="fa-circle" placeholder="fa-plane" class="f-input"
                        oninput="updateIconPreview()">
                </div>
                <div class="icon-grid">
                    <?php foreach(['fa-plane','fa-passport','fa-hotel','fa-suitcase-rolling','fa-kaaba','fa-bus','fa-ship','fa-train','fa-car','fa-bicycle','fa-motorcycle','fa-taxi','fa-helicopter','fa-globe','fa-map-marker-alt','fa-umbrella-beach','fa-mountain','fa-hiking','fa-camera','fa-concierge-bell','fa-user-tie','fa-briefcase','fa-heart','fa-star','fa-gem','fa-crown','fa-ticket-alt','fa-tags','fa-receipt','fa-credit-card'] as $ic): ?>
                        <button type="button" class="icon-btn" onclick="pickIcon('<?= $ic ?>')" title="<?= $ic ?>"><i class="fas <?= $ic ?>"></i></button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div>
                <label class="f-label">Color</label>
                <div class="flex flex-wrap gap-2" id="colorPicker">
                    <?php foreach(['indigo'=>'#6366f1','sky'=>'#0ea5e9','purple'=>'#a855f7','pink'=>'#ec4899','green'=>'#22c55e','yellow'=>'#eab308','orange'=>'#f97316','red'=>'#ef4444','teal'=>'#14b8a6','gray'=>'#6b7280'] as $name=>$hex): ?>
                        <button type="button" class="color-dot" style="background:<?= $hex ?>" data-color="<?= $name ?>" onclick="pickColor('<?= $name ?>')" title="<?= $name ?>"></button>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" id="fColor" value="indigo">
            </div>

            <div>
                <label class="f-label">Description <span class="text-slate-300 font-normal normal-case">(optional)</span></label>
                <textarea id="fDesc" rows="2" placeholder="Short description…" class="f-input resize-none"></textarea>
            </div>

            <div>
                <label class="f-label">Sort Order</label>
                <input type="number" id="fSort" value="0" min="0" class="f-input">
            </div>

            <input type="hidden" id="fSysId">
        </div>
        <div class="modal-footer">
            <button onclick="closeModal()" class="btn-ghost">Cancel</button>
            <button onclick="saveService()" id="saveBtn" class="btn-primary">
                <i class="fas fa-save mr-1.5 text-xs"></i>Save Service
            </button>
        </div>
    </div>
</div>

<!-- ══ DELETE MODAL ══ -->
<div id="deleteModal" class="modal-bg hidden">
    <div class="modal-box fade-up" style="max-width:360px">
        <div class="modal-body text-center pt-6">
            <div class="del-icon"><i class="fas fa-trash-alt text-red-500 text-xl"></i></div>
            <h3 class="font-bold text-slate-800 mb-1">Delete Service?</h3>
            <p class="text-sm text-slate-400 mb-5">This will permanently remove the service. Existing leads won't be affected.</p>
            <input type="hidden" id="deleteSysId">
        </div>
        <div class="modal-footer justify-center gap-3">
            <button onclick="closeDeleteModal()" class="btn-ghost flex-1">Cancel</button>
            <button onclick="confirmDelete()" class="flex-1 py-2.5 rounded-lg text-sm font-bold text-white bg-red-500 hover:bg-red-600 transition">Delete</button>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="toast" class="hidden">
    <div id="toastInner" class="flex items-center gap-3 px-4 py-3 rounded-xl shadow-2xl text-white text-sm font-semibold min-w-[200px]">
        <i id="toastIcon" class="fas fa-check-circle flex-shrink-0"></i>
        <span id="toastMsg"></span>
    </div>
</div>

<?php include '../elements/floating-menus.php'; ?>
<script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>
<script>
const API = "<?php echo $servicesApi; ?>";
let allServices = [];

const COLOR_MAP = {
    indigo:'#6366f1', sky:'#0ea5e9', purple:'#a855f7', pink:'#ec4899',
    green:'#22c55e', yellow:'#eab308', orange:'#f97316', red:'#ef4444',
    teal:'#14b8a6', gray:'#6b7280'
};

// ── Load ──────────────────────────────────────────────
async function loadServices() {
    document.getElementById('servicesGrid').innerHTML =
        `<div class="col-span-full empty-state"><i class="fas fa-spinner fa-spin"></i>Loading…</div>`;
    try {
        const res  = await fetch(API + '?action=all');
        const json = await res.json();
        allServices = json.data ?? [];
        updateStats();
        renderGrid();
    } catch {
        document.getElementById('servicesGrid').innerHTML =
            `<div class="col-span-full empty-state"><i class="fas fa-exclamation-circle text-red-300"></i>Failed to load.</div>`;
    }
}

function updateStats() {
    const active    = allServices.filter(s => s.is_active == 1).length;
    const withFields= allServices.filter(s => s.fields && s.fields.length > 0).length;
    document.getElementById('statTotal').textContent    = allServices.length;
    document.getElementById('statActive').textContent   = active;
    document.getElementById('statInactive').textContent = allServices.length - active;
    document.getElementById('statFields').textContent   = withFields;
}

function renderGrid() {
    const q      = document.getElementById('searchInput').value.toLowerCase();
    const active = document.getElementById('filterActive').value;
    let list = allServices.filter(s => {
        const matchQ = !q || s.name.toLowerCase().includes(q) || s.slug.includes(q);
        const matchA = active === '' || String(s.is_active) === active;
        return matchQ && matchA;
    });

    const grid = document.getElementById('servicesGrid');
    if (!list.length) {
        grid.innerHTML = `<div class="col-span-full empty-state"><i class="fas fa-inbox"></i>No services found.</div>`;
        return;
    }

    grid.innerHTML = list.map(s => {
        const hex    = COLOR_MAP[s.color] ?? '#6366f1';
        const active = s.is_active == 1;
        return `
        <div class="svc-card ${active?'':'inactive'}" style="--card-accent:${hex}">
            <div class="flex items-start justify-between mb-3">
                <div class="svc-icon-wrap" style="background:${hex}">
                    <i class="fas ${s.icon}"></i>
                </div>
                <label class="toggle-wrap cursor-pointer">
                    <input type="checkbox" ${active?'checked':''} onchange="toggleService('${s.sys_id}')">
                    <div class="toggle-track"></div>
                    <div class="toggle-thumb"></div>
                </label>
            </div>

            <div class="font-bold text-slate-800 text-sm mb-0.5">${s.name}</div>
            <div class="slug-badge mb-2">${s.slug}</div>
            ${s.description ? `<p class="text-xs text-slate-400 mb-3 line-clamp-2 leading-relaxed">${s.description}</p>` : '<div class="mb-3"></div>'}

            <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                <span class="text-[10px] font-bold uppercase tracking-wider ${active ? 'text-emerald-500' : 'text-slate-300'}">
                    ${active ? '● Active' : '○ Inactive'}
                </span>
                <div class="flex gap-1.5">
                    <button onclick="editService('${s.sys_id}')" class="act-btn edit" title="Edit">
                        <i class="fas fa-pencil" style="font-size:10px"></i>
                    </button>
                    <button onclick="openDeleteModal('${s.sys_id}')" class="act-btn del" title="Delete">
                        <i class="fas fa-trash-alt" style="font-size:10px"></i>
                    </button>
                </div>
            </div>
        </div>`;
    }).join('');
}

// ── Modal ─────────────────────────────────────────────
function openModal(sysId = null) {
    clearForm();
    document.getElementById('modalTitle').textContent = sysId ? 'Edit Service' : 'Add Service';
    document.getElementById('svcModal').classList.remove('hidden');
}
function closeModal() { document.getElementById('svcModal').classList.add('hidden'); }

function clearForm() {
    ['fSysId','fDesc'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('fName').value  = '';
    document.getElementById('fIcon').value  = 'fa-circle';
    document.getElementById('fSort').value  = '0';
    document.getElementById('fColor').value = 'indigo';
    updateIconPreview();
    highlightColor('indigo');
    document.querySelectorAll('.icon-btn').forEach(b => b.classList.remove('selected'));
}

function editService(sysId) {
    const s = allServices.find(x => x.sys_id === sysId);
    if (!s) return;
    openModal(sysId);
    document.getElementById('fSysId').value = s.sys_id;
    document.getElementById('fName').value  = s.name;
    document.getElementById('fIcon').value  = s.icon;
    document.getElementById('fDesc').value  = s.description ?? '';
    document.getElementById('fSort').value  = s.sort_order ?? 0;
    document.getElementById('fColor').value = s.color ?? 'indigo';
    updateIconPreview();
    highlightColor(s.color ?? 'indigo');
    document.querySelectorAll('.icon-btn').forEach(b => {
        b.classList.toggle('selected', b.querySelector('i')?.className.includes(s.icon));
    });
}

// ── Save ──────────────────────────────────────────────
async function saveService() {
    const sysId = document.getElementById('fSysId').value.trim();
    const name  = document.getElementById('fName').value.trim();
    if (!name) { showToast('error','Service name is required'); return; }
    const btn = document.getElementById('saveBtn');
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5 text-xs"></i>Saving…';
    const payload = {
        action: sysId ? 'update' : 'store',
        sys_id: sysId || undefined,
        name,
        icon:        document.getElementById('fIcon').value.trim() || 'fa-circle',
        color:       document.getElementById('fColor').value || 'indigo',
        description: document.getElementById('fDesc').value.trim(),
        sort_order:  parseInt(document.getElementById('fSort').value) || 0,
    };
    try {
        const res  = await fetch(API, {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
        const json = await res.json();
        if (json.status === 'success') { showToast('success', sysId ? 'Service updated!' : 'Service created!'); closeModal(); loadServices(); }
        else showToast('error', json.message);
    } catch { showToast('error','Network error'); }
    btn.disabled = false; btn.innerHTML = '<i class="fas fa-save mr-1.5 text-xs"></i>Save Service';
}

// ── Toggle ────────────────────────────────────────────
async function toggleService(sysId) {
    try {
        const res  = await fetch(API,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'toggle',sys_id:sysId})});
        const json = await res.json();
        if (json.status === 'success') loadServices();
        else showToast('error', json.message);
    } catch { showToast('error','Network error'); }
}

// ── Delete ────────────────────────────────────────────
function openDeleteModal(sysId) { document.getElementById('deleteSysId').value=sysId; document.getElementById('deleteModal').classList.remove('hidden'); }
function closeDeleteModal()      { document.getElementById('deleteModal').classList.add('hidden'); }

async function confirmDelete() {
    const sysId = document.getElementById('deleteSysId').value;
    closeDeleteModal();
    try {
        const res  = await fetch(API,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'delete',sys_id:sysId})});
        const json = await res.json();
        if (json.status === 'success') { showToast('success','Deleted'); loadServices(); }
        else showToast('error', json.message);
    } catch { showToast('error','Network error'); }
}

// ── Icon picker ───────────────────────────────────────
function pickIcon(cls) {
    document.getElementById('fIcon').value = cls;
    updateIconPreview();
    document.querySelectorAll('.icon-btn').forEach(b => b.classList.toggle('selected', b.querySelector('i')?.className.includes(cls)));
}
function updateIconPreview() {
    const cls = document.getElementById('fIcon').value.trim() || 'fa-circle';
    document.getElementById('iconPreviewI').className = 'fas ' + cls + ' text-sm';
    const hex = COLOR_MAP[document.getElementById('fColor').value] ?? '#6366f1';
    document.getElementById('iconPreview').style.background = hex;
}

// ── Color picker ──────────────────────────────────────
function pickColor(name) { document.getElementById('fColor').value = name; highlightColor(name); updateIconPreview(); }
function highlightColor(name) {
    document.querySelectorAll('.color-dot').forEach(b => b.classList.toggle('selected', b.dataset.color === name));
}

// ── Modal backdrop ────────────────────────────────────
document.querySelectorAll('.modal-bg').forEach(m => m.addEventListener('click', e => { if (e.target === m) m.classList.add('hidden'); }));

// ── Toast ─────────────────────────────────────────────
function showToast(type, msg) {
    const inner = document.getElementById('toastInner');
    document.getElementById('toastMsg').textContent = msg;
    inner.className = `flex items-center gap-3 px-4 py-3 rounded-xl shadow-2xl text-white text-sm font-semibold min-w-[200px] ${type==='success'?'bg-emerald-600':'bg-red-500'}`;
    document.getElementById('toastIcon').className  = `fas ${type==='success'?'fa-check-circle':'fa-exclamation-circle'} flex-shrink-0`;
    document.getElementById('toast').classList.remove('hidden');
    setTimeout(() => document.getElementById('toast').classList.add('hidden'), 3500);
}

loadServices();
</script>
</body>
</html>