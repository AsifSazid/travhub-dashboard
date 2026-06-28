<?php
// FILE PATH: /pages/masterdata-visa.php
include_once('./authenticate.php');
$ip_port = @file_get_contents('../ippath.txt') ?: 'http://103.104.219.3:898';
$visaApi     = $ip_port . 'api/masterdata/visa/endpoints.php';
$countriesApi = $ip_port . 'api/masterdata/countries/endpoints.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Visa Masterdata — TravHub</title>
<link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
:root{--accent:#4F46E5;--accent-h:#4338CA;--border:#E2E8F0;--surface:#F8FAFC;--text:#0F172A;--muted:#64748B;}
body{background:var(--surface);}
.f-label{display:block;font-size:.75rem;font-weight:600;color:#475569;margin-bottom:5px;}
.f-input{width:100%;padding:8px 12px;font-size:.875rem;color:var(--text);border:1.5px solid var(--border);border-radius:8px;background:#fff;outline:none;transition:border .15s;}
.f-input:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(79,70,229,.1);}
.card{background:#fff;border:1px solid var(--border);border-radius:12px;padding:20px;}
/* ── Rich text editor ── */
.rich-editor{min-height:100px;border:1.5px solid var(--border);border-radius:8px;padding:10px 12px;font-size:.875rem;line-height:1.7;outline:none;background:#fff;color:var(--text);}
.rich-editor:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(79,70,229,.1);}
.editor-toolbar{display:flex;gap:4px;flex-wrap:wrap;padding:6px 8px;background:#F8FAFC;border:1.5px solid var(--border);border-bottom:none;border-radius:8px 8px 0 0;}
.editor-toolbar + .rich-editor{border-radius:0 0 8px 8px;}
.ed-btn{padding:4px 8px;border-radius:5px;border:1px solid var(--border);background:#fff;font-size:.75rem;cursor:pointer;color:var(--muted);transition:all .15s;line-height:1;}
.ed-btn:hover{background:var(--accent);color:#fff;border-color:var(--accent);}
/* ── Tag input ── */
.tag-input-wrap{display:flex;flex-wrap:wrap;gap:5px;padding:6px 8px;border:1.5px solid var(--border);border-radius:8px;background:#fff;min-height:40px;cursor:text;}
.tag-input-wrap:focus-within{border-color:var(--accent);box-shadow:0 0 0 3px rgba(79,70,229,.1);}
.tag-chip{display:inline-flex;align-items:center;gap:4px;padding:2px 8px;background:#EEF2FF;color:#4338CA;border-radius:999px;font-size:.75rem;font-weight:600;}
.tag-chip button{background:none;border:none;cursor:pointer;color:#818CF8;line-height:1;padding:0;font-size:.7rem;}
.tag-chip button:hover{color:#EF4444;}
.tag-bare{border:none;outline:none;font-size:.82rem;flex:1;min-width:80px;background:transparent;color:var(--text);}
/* ── Category / Sub cards ── */
.cat-block{border:1.5px solid #C7D2FE;border-radius:10px;padding:16px;background:#F5F7FF;position:relative;margin-bottom:12px;}
.sub-block{border:1px solid #DDD6FE;border-radius:8px;padding:12px;background:#fff;position:relative;margin-bottom:8px;}
.del-btn{position:absolute;top:10px;right:10px;width:26px;height:26px;border-radius:6px;background:#FEF2F2;border:1px solid #FECACA;color:#EF4444;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.7rem;transition:all .15s;}
.del-btn:hover{background:#FEE2E2;}
.add-more-btn{display:inline-flex;align-items:center;gap:5px;font-size:.75rem;font-weight:600;color:var(--accent);background:#EEF2FF;border:none;border-radius:6px;padding:5px 10px;cursor:pointer;}
.add-more-btn:hover{background:#E0E7FF;}
/* ── Country list ── */
.country-row{display:grid;grid-template-columns:1fr auto auto auto;gap:12px;align-items:center;padding:12px 16px;background:#fff;border:1px solid var(--border);border-radius:8px;transition:all .15s;}
.country-row:hover{border-color:#C7D2FE;background:#FAFBFF;}
.badge-active{background:#DCFCE7;color:#166534;padding:2px 10px;border-radius:999px;font-size:.7rem;font-weight:600;}
.badge-inactive{background:#FEF3C7;color:#92400E;padding:2px 10px;border-radius:999px;font-size:.7rem;font-weight:600;}
/* Modal */
.modal-bg{position:fixed;inset:0;background:rgba(0,0,0,.5);backdrop-filter:blur(4px);z-index:50;display:flex;align-items:flex-start;justify-content:center;padding:20px;overflow-y:auto;}
.modal-box{background:#fff;border-radius:16px;width:100%;max-width:780px;box-shadow:0 20px 60px rgba(0,0,0,.2);margin:auto;}
</style>
</head>
<body>
<?php include '../elements/header.php'; ?>
<?php include '../elements/aside.php'; ?>

<main id="mainContent" class="pt-16 pl-16 mt-16 transition-all duration-300">
<div class="p-4 md:p-6 max-w-6xl mx-auto">

    <!-- Page Header -->
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <p class="text-xs font-semibold text-indigo-500 uppercase tracking-widest mb-1">Masterdata</p>
            <h1 class="text-xl font-bold text-slate-900"><i class="fas fa-passport mr-2 text-indigo-500"></i>Visa Masterdata</h1>
            <p class="text-sm text-slate-400 mt-0.5">Country-wise visa categories, sub-categories, documents & requirements</p>
        </div>
        <button onclick="openAddModal()" class="flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition shadow-sm">
            <i class="fas fa-plus"></i> Add Visa Data
        </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-5">
        <div class="card border-l-4 border-indigo-400">
            <div class="text-2xl font-bold text-slate-700" id="statTotal">—</div>
            <div class="text-xs text-slate-400 mt-0.5 uppercase tracking-wide font-semibold">Countries</div>
        </div>
        <div class="card border-l-4 border-green-400">
            <div class="text-2xl font-bold text-green-600" id="statActive">—</div>
            <div class="text-xs text-slate-400 mt-0.5 uppercase tracking-wide font-semibold">Active</div>
        </div>
        <div class="card border-l-4 border-amber-400">
            <div class="text-2xl font-bold text-amber-500" id="statInactive">—</div>
            <div class="text-xs text-slate-400 mt-0.5 uppercase tracking-wide font-semibold">Inactive</div>
        </div>
    </div>

    <!-- Search -->
    <div class="card mb-4">
        <div class="flex gap-3">
            <div class="relative flex-1">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                <input type="text" id="searchInput" placeholder="Search country…"
                    class="f-input pl-8" oninput="renderList()">
            </div>
            <select id="filterStatus" class="f-input" style="width:140px" onchange="renderList()">
                <option value="">All Status</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </select>
            <button onclick="loadList()" class="w-10 h-10 flex items-center justify-center bg-slate-100 hover:bg-slate-200 text-slate-500 rounded-lg transition flex-shrink-0">
                <i class="fas fa-rotate-right text-sm"></i>
            </button>
        </div>
    </div>

    <!-- List -->
    <div id="visaList" class="space-y-2">
        <div class="text-center py-12 text-slate-300"><i class="fas fa-spinner fa-spin mr-2"></i>Loading…</div>
    </div>

</div>
</main>

<!-- ═══════════════════════════════════════════════
     ADD / EDIT MODAL
═══════════════════════════════════════════════ -->
<div id="editModal" class="modal-bg hidden">
<div class="modal-box">
    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
        <h2 class="font-bold text-slate-800 text-base" id="modalTitle"><i class="fas fa-passport mr-2 text-indigo-500"></i>Add Visa Data</h2>
        <button onclick="closeModal()" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-slate-100 text-slate-400"><i class="fas fa-times"></i></button>
    </div>
    <div class="p-6 space-y-5 max-h-[80vh] overflow-y-auto">

        <!-- Country Select -->
        <div>
            <label class="f-label">Country <span class="text-red-400">*</span></label>
            <select id="modalCountry" class="f-input">
                <option value="">Select country…</option>
            </select>
            <input type="hidden" id="modalSysId">
            <input type="hidden" id="modalCountryName">
        </div>

        <!-- Categories -->
        <div>
            <div class="flex items-center justify-between mb-3">
                <label class="f-label mb-0">Visa Categories</label>
                <button type="button" onclick="addCategory()" class="add-more-btn">
                    <i class="fas fa-plus text-xs"></i> Add Category
                </button>
            </div>
            <div id="categoriesContainer" class="space-y-3"></div>
        </div>

    </div>
    <div class="flex gap-3 px-6 pb-6 pt-3 border-t border-slate-100">
        <button onclick="closeModal()" class="flex-1 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-sm font-semibold transition">Cancel</button>
        <button onclick="saveVisa()" id="saveBtn"
            class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition">
            <i class="fas fa-save mr-1"></i>Save
        </button>
    </div>
</div>
</div>

<!-- Toast -->
<div id="toast" class="hidden fixed bottom-6 right-6 z-[60]">
    <div id="toastInner" class="flex items-center gap-3 px-4 py-3 rounded-xl shadow-2xl text-white text-sm font-semibold min-w-[220px]">
        <i id="toastIcon" class="fas fa-check-circle flex-shrink-0"></i>
        <span id="toastMsg"></span>
    </div>
</div>

<?php include '../elements/floating-menus.php'; ?>
<script src="../assets/js/script.js?t=<?php echo time(); ?>"></script>
<script>
const VISA_API     = '<?php echo $visaApi; ?>';
const COUNTRY_API  = '<?php echo $countriesApi; ?>';

let allData      = [];   // list of visa masterdata rows
let allCountries = [];   // from countries API
let _editSysId   = null; // null = new, string = edit mode

// ─── Init ────────────────────────────────────────────────────
(async () => {
    await Promise.all([loadList(), loadCountries()]);
})();

async function loadList() {
    try {
        const res  = await fetch(`${VISA_API}?action=list`);
        const json = await res.json();
        allData    = json.data ?? [];
        renderStats();
        renderList();
    } catch(e) {
        document.getElementById('visaList').innerHTML =
            `<div class="text-center py-8 text-red-400 text-sm"><i class="fas fa-exclamation-circle mr-1"></i>Failed to load</div>`;
    }
}

async function loadCountries() {
    try {
        const res  = await fetch(`${COUNTRY_API}?action=all`);
        const json = await res.json();
        allCountries = json.data ?? [];
    } catch {}
}

function renderStats() {
    const active   = allData.filter(r => r.is_active == 1).length;
    const inactive = allData.length - active;
    document.getElementById('statTotal').textContent    = allData.length;
    document.getElementById('statActive').textContent   = active;
    document.getElementById('statInactive').textContent = inactive;
}

function renderList() {
    const q      = document.getElementById('searchInput').value.toLowerCase();
    const status = document.getElementById('filterStatus').value;
    const list   = document.getElementById('visaList');

    let rows = allData;
    if (q)      rows = rows.filter(r => r.country_name.toLowerCase().includes(q));
    if (status !== '') rows = rows.filter(r => String(r.is_active) === status);

    if (!rows.length) {
        list.innerHTML = `<div class="text-center py-12 text-slate-300 text-sm">No visa data found.</div>`;
        return;
    }

    list.innerHTML = rows.map(r => `
        <div class="country-row">
            <div>
                <div class="font-semibold text-slate-800">${esc(r.country_name)}</div>
                <div class="text-xs text-slate-400 mt-0.5 font-mono">${esc(r.sys_id)} · ${r.category_count} categor${r.category_count===1?'y':'ies'}</div>
            </div>
            <span class="${r.is_active ? 'badge-active' : 'badge-inactive'}">${r.is_active ? 'Active' : 'Inactive'}</span>
            <button onclick="editRow('${r.sys_id}')"
                class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 rounded-lg text-xs font-semibold transition">
                <i class="fas fa-edit mr-1"></i>Edit
            </button>
            <div class="flex gap-1.5">
                <button onclick="toggleRow('${r.sys_id}')" title="${r.is_active ? 'Deactivate' : 'Activate'}"
                    class="w-8 h-8 flex items-center justify-center rounded-lg border transition ${r.is_active ? 'border-green-200 text-green-500 hover:bg-green-50' : 'border-amber-200 text-amber-500 hover:bg-amber-50'}">
                    <i class="fas ${r.is_active ? 'fa-toggle-on' : 'fa-toggle-off'} text-sm"></i>
                </button>
                <button onclick="deleteRow('${r.sys_id}', '${esc(r.country_name)}')"
                    class="w-8 h-8 flex items-center justify-center rounded-lg border border-red-200 text-red-400 hover:bg-red-50 transition">
                    <i class="fas fa-trash text-xs"></i>
                </button>
            </div>
        </div>
    `).join('');
}

// ─── Modal ───────────────────────────────────────────────────
function openAddModal() {
    _editSysId = null;
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-passport mr-2 text-indigo-500"></i>Add Visa Data';
    document.getElementById('modalSysId').value     = '';
    document.getElementById('modalCountry').value   = '';
    document.getElementById('modalCountryName').value = '';
    buildCountrySelect('');
    document.getElementById('categoriesContainer').innerHTML = '';
    addCategory(); // start with one empty category
    document.getElementById('editModal').classList.remove('hidden');
}

async function editRow(sysId) {
    const row = allData.find(r => r.sys_id === sysId);
    if (!row) return;
    _editSysId = sysId;

    document.getElementById('modalTitle').innerHTML = `<i class="fas fa-passport mr-2 text-indigo-500"></i>Edit — ${esc(row.country_name)}`;
    document.getElementById('modalSysId').value       = sysId;

    buildCountrySelect(row.country_sys_id);
    document.getElementById('modalCountry').value     = row.country_sys_id;
    document.getElementById('modalCountryName').value = row.country_name;
    document.getElementById('modalCountry').disabled  = true; // can't change country on edit

    // Load full categories
    const res  = await fetch(`${VISA_API}?action=get&country=${encodeURIComponent(row.country_sys_id)}`);
    const json = await res.json();
    const cats = json.data?.categories ?? [];

    const container = document.getElementById('categoriesContainer');
    container.innerHTML = '';
    if (cats.length) {
        cats.forEach((cat, i) => addCategory(cat));
    } else {
        addCategory();
    }

    document.getElementById('editModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('editModal').classList.add('hidden');
    document.getElementById('modalCountry').disabled = false;
}

function buildCountrySelect(selected) {
    const sel = document.getElementById('modalCountry');
    // Filter out countries that already have visa data (only on add mode)
    const existingCountries = _editSysId ? [] : allData.map(r => r.country_sys_id);
    sel.innerHTML = `<option value="">Select country…</option>` +
        allCountries
            .filter(c => !existingCountries.includes(c.sys_id) || c.sys_id === selected)
            .map(c => `<option value="${esc(c.sys_id)}" data-name="${esc(c.name)}" ${c.sys_id === selected ? 'selected' : ''}>${esc(c.name)}</option>`)
            .join('');
}

// ─── Category Builder ────────────────────────────────────────
let _catIdx = 0;

function addCategory(data = {}) {
    const idx       = _catIdx++;
    const container = document.getElementById('categoriesContainer');
    const div       = document.createElement('div');
    div.className   = 'cat-block';
    div.dataset.catIdx = idx;
    div.innerHTML = `
        <button type="button" onclick="this.closest('.cat-block').remove()" class="del-btn"><i class="fas fa-times"></i></button>
        <div class="pr-8 space-y-3">
            <div>
                <label class="f-label">Category Name <span class="text-red-400">*</span></label>
                <input type="text" class="f-input cat-name" placeholder="e.g. Tourist Visa"
                    value="${esc(data.name ?? '')}">
                <input type="hidden" class="cat-id" value="${esc(data.id ?? '')}">
            </div>

            <!-- Instructions -->
            <div>
                <label class="f-label">Instructions <span class="text-slate-400 font-normal">(if no sub-category)</span></label>
                ${editorHtml('cat-instruction-' + idx, data.instruction ?? '')}
            </div>

            <!-- Document List -->
            <div>
                <label class="f-label"><i class="fas fa-file-alt mr-1 text-indigo-400"></i>Document List</label>
                ${tagInputHtml('cat-docs-' + idx, data.document_list ?? [])}
            </div>

            <!-- Requirements -->
            <div>
                <label class="f-label"><i class="fas fa-check-circle mr-1 text-green-400"></i>Requirements</label>
                ${tagInputHtml('cat-reqs-' + idx, data.requirements ?? [])}
            </div>

            <!-- Sub Categories -->
            <div class="border-t border-indigo-100 pt-3">
                <div class="flex items-center justify-between mb-2">
                    <label class="f-label mb-0 text-indigo-600"><i class="fas fa-sitemap mr-1"></i>Sub Categories</label>
                    <button type="button" onclick="addSubCategory(this, ${idx})" class="add-more-btn text-indigo-500">
                        <i class="fas fa-plus text-xs"></i> Add Sub
                    </button>
                </div>
                <div class="sub-container space-y-2">
                    ${(data.sub_categories ?? []).map(s => subCategoryHtml(idx, s)).join('')}
                </div>
            </div>
        </div>`;
    container.appendChild(div);
    initTagInputs(div);
}

function subCategoryHtml(catIdx, data = {}) {
    const subIdx = _catIdx++ + '_sub';
    return `<div class="sub-block">
        <button type="button" onclick="this.closest('.sub-block').remove()" class="del-btn" style="top:8px;right:8px;width:22px;height:22px;"><i class="fas fa-times" style="font-size:8px"></i></button>
        <div class="pr-8 space-y-3">
            <div>
                <label class="f-label text-xs">Sub Category Name</label>
                <input type="text" class="f-input sub-name" placeholder="e.g. Single Entry"
                    value="${esc(data.name ?? '')}">
                <input type="hidden" class="sub-id" value="${esc(data.id ?? '')}">
            </div>
            <div>
                <label class="f-label text-xs">Instructions</label>
                ${editorHtml('sub-instruction-' + subIdx, data.instruction ?? '')}
            </div>
            <div>
                <label class="f-label text-xs"><i class="fas fa-file-alt mr-1 text-indigo-400"></i>Document List</label>
                ${tagInputHtml('sub-docs-' + subIdx, data.document_list ?? [])}
            </div>
            <div>
                <label class="f-label text-xs"><i class="fas fa-check-circle mr-1 text-green-400"></i>Requirements</label>
                ${tagInputHtml('sub-reqs-' + subIdx, data.requirements ?? [])}
            </div>
        </div>
    </div>`;
}

function addSubCategory(btn, catIdx) {
    const catBlock  = document.querySelector(`.cat-block[data-cat-idx="${catIdx}"]`);
    const container = catBlock?.querySelector('.sub-container');
    if (!container) return;
    const tmp = document.createElement('div');
    tmp.innerHTML = subCategoryHtml(catIdx, {});
    const el = tmp.firstElementChild;
    container.appendChild(el);
    initTagInputs(el);
}

// ─── Editor HTML ─────────────────────────────────────────────
function editorHtml(id, content = '') {
    return `<div class="editor-toolbar" data-for="${id}">
        <button type="button" class="ed-btn" onclick="edCmd('bold')" title="Bold"><b>B</b></button>
        <button type="button" class="ed-btn" onclick="edCmd('italic')" title="Italic"><i>I</i></button>
        <button type="button" class="ed-btn" onclick="edCmd('underline')" title="Underline"><u>U</u></button>
        <button type="button" class="ed-btn" onclick="edCmd('insertUnorderedList')" title="Bullet list"><i class="fas fa-list-ul fa-xs"></i></button>
        <button type="button" class="ed-btn" onclick="edCmd('insertOrderedList')" title="Numbered list"><i class="fas fa-list-ol fa-xs"></i></button>
        <button type="button" class="ed-btn" onclick="edCmd('removeFormat')" title="Clear"><i class="fas fa-remove-format fa-xs"></i></button>
    </div>
    <div id="${id}" class="rich-editor" contenteditable="true">${content}</div>`;
}

function edCmd(cmd) {
    document.execCommand(cmd, false, null);
}

// ─── Tag Input HTML + Logic ───────────────────────────────────
function tagInputHtml(id, items = []) {
    return `<div class="tag-input-wrap" id="${id}" onclick="this.querySelector('.tag-bare').focus()">
        ${items.map(t => tagChipHtml(t)).join('')}
        <input type="text" class="tag-bare" placeholder="Type & press Enter…"
            onkeydown="onTagKey(event, this)">
    </div>`;
}

function tagChipHtml(text) {
    return `<span class="tag-chip">${esc(text)}<button type="button" onclick="this.closest('.tag-chip').remove()" title="Remove">×</button></span>`;
}

function onTagKey(e, inp) {
    if ((e.key === 'Enter' || e.key === ',') && inp.value.trim()) {
        e.preventDefault();
        const val  = inp.value.trim().replace(/,$/, '');
        if (val) {
            inp.insertAdjacentHTML('beforebegin', tagChipHtml(val));
        }
        inp.value = '';
    } else if (e.key === 'Backspace' && !inp.value) {
        const prev = inp.previousElementSibling;
        if (prev?.classList.contains('tag-chip')) prev.remove();
    }
}

function initTagInputs(container) {
    // nothing extra needed — event delegation via onkeydown inline
}

function getTagValues(wrapEl) {
    return [...wrapEl.querySelectorAll('.tag-chip')].map(c => c.firstChild.textContent.trim()).filter(Boolean);
}

// ─── Collect & Save ──────────────────────────────────────────
function collectCategories() {
    const cats = [];
    document.querySelectorAll('.cat-block').forEach(catEl => {
        const name        = catEl.querySelector('.cat-name')?.value?.trim() ?? '';
        const catId       = catEl.querySelector('.cat-id')?.value || ('cat_' + Date.now() + Math.random().toString(36).slice(2));
        const instrId     = catEl.querySelector('.rich-editor')?.id;
        const instruction = instrId ? (document.getElementById(instrId)?.innerHTML ?? '') : '';
        const docWrapId   = [...catEl.querySelectorAll('.tag-input-wrap')][0]?.id;
        const reqWrapId   = [...catEl.querySelectorAll('.tag-input-wrap')][1]?.id;
        const docList     = docWrapId ? getTagValues(document.getElementById(docWrapId)) : [];
        const reqs        = reqWrapId ? getTagValues(document.getElementById(reqWrapId)) : [];

        const subs = [];
        catEl.querySelectorAll('.sub-block').forEach(subEl => {
            const sName    = subEl.querySelector('.sub-name')?.value?.trim() ?? '';
            const subId    = subEl.querySelector('.sub-id')?.value || ('sub_' + Date.now() + Math.random().toString(36).slice(2));
            const editors  = subEl.querySelectorAll('.rich-editor');
            const sInstr   = editors[0]?.innerHTML ?? '';
            const tagWraps = subEl.querySelectorAll('.tag-input-wrap');
            const sDocs    = tagWraps[0] ? getTagValues(tagWraps[0]) : [];
            const sReqs    = tagWraps[1] ? getTagValues(tagWraps[1]) : [];
            subs.push({ id: subId, name: sName, instruction: sInstr, document_list: sDocs, requirements: sReqs });
        });

        cats.push({ id: catId, name, instruction, document_list: docList, requirements: reqs, sub_categories: subs });
    });
    return cats;
}

async function saveVisa() {
    const countrySel  = document.getElementById('modalCountry');
    const countryId   = countrySel.value;
    const countryName = countrySel.options[countrySel.selectedIndex]?.dataset.name || document.getElementById('modalCountryName').value;

    if (!countryId) { showToast('error', 'Please select a country'); return; }

    const categories = collectCategories();
    if (!categories.length) { showToast('error', 'Add at least one visa category'); return; }
    if (categories.some(c => !c.name)) { showToast('error', 'All category names are required'); return; }

    const btn = document.getElementById('saveBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Saving…';

    try {
        const res  = await fetch(`${VISA_API}?action=save`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ country_sys_id: countryId, country_name: countryName, categories }),
        });
        const json = await res.json();
        if (json.status !== 'ok') throw new Error(json.message ?? 'Failed');
        showToast('success', json.message);
        closeModal();
        await loadList();
    } catch(e) {
        showToast('error', e.message ?? 'Failed to save');
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-save mr-1"></i>Save';
}

async function toggleRow(sysId) {
    try {
        const res  = await fetch(`${VISA_API}?action=toggle`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ sys_id: sysId }),
        });
        const json = await res.json();
        if (json.status !== 'ok') throw new Error(json.message);
        showToast('success', `${json.is_active ? 'Activated' : 'Deactivated'}`);
        await loadList();
    } catch(e) { showToast('error', e.message); }
}

async function deleteRow(sysId, name) {
    if (!confirm(`Delete visa data for "${name}"? This cannot be undone.`)) return;
    try {
        const res  = await fetch(`${VISA_API}?action=delete`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ sys_id: sysId }),
        });
        const json = await res.json();
        if (json.status !== 'ok') throw new Error(json.message);
        showToast('success', 'Deleted');
        await loadList();
    } catch(e) { showToast('error', e.message); }
}

// ─── Helpers ─────────────────────────────────────────────────
function esc(s) { return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

function showToast(type, msg) {
    const i = document.getElementById('toastInner');
    document.getElementById('toastMsg').textContent = msg;
    i.className = `flex items-center gap-3 px-4 py-3 rounded-xl shadow-2xl text-white text-sm font-semibold min-w-[220px] ${type === 'success' ? 'bg-emerald-600' : 'bg-red-500'}`;
    document.getElementById('toastIcon').className = `fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} flex-shrink-0`;
    document.getElementById('toast').classList.remove('hidden');
    setTimeout(() => document.getElementById('toast').classList.add('hidden'), 4000);
}
</script>
</body>
</html>