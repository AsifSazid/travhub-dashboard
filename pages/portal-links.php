<?php
/**
 * FILE PATH: pages/portal-links.php
 * Master Data — Portal Links
 */
require_once '../pages/authenticate.php'; // $authUser, $authUserId, $_SESSION['role'] ইত্যাদি সেট করার জন্য (existing convention অনুযায়ী)

?>

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
        <div class="max-w-6xl mx-auto p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-xl font-bold text-gray-800">Portal Links</h1>
                    <p class="text-sm text-gray-500">Visa/Airline/Hotel portal-এর shared credentials — এখানে সংরক্ষিত থাকবে</p>
                </div>
                <button onclick="openCreatePortalModal()"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">
                    <i class="fas fa-plus"></i> New Portal
                </button>
            </div>
        
            <!-- Type filter -->
            <div class="flex gap-2 mb-4 flex-wrap" id="typeFilterBar">
                <button onclick="setTypeFilter('')" data-type="" class="type-filter-btn px-3 py-1.5 text-xs rounded-full border border-blue-500 bg-blue-50 text-blue-700 font-medium">All</button>
            </div>
        
            <div id="portalsLoading" class="flex items-center gap-2 text-sm text-gray-500 py-6">
                <span class="w-4 h-4 border-2 border-gray-300 border-t-blue-500 rounded-full animate-spin"></span>
                Loading...
            </div>
            <div id="portalsList" class="hidden space-y-3"></div>
            <div id="portalsEmpty" class="hidden text-sm text-gray-400 py-8 text-center">No portals added yet.</div>
        </div>
        
        <!-- Create Portal Modal -->
        <div id="createPortalModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
                    <h3 class="font-semibold text-gray-800">New Portal</h3>
                    <button onclick="closeCreatePortalModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
                </div>
                <div class="p-5 space-y-3">
                    <div>
                        <label class="text-xs font-medium text-gray-600 mb-1 block">Portal Name</label>
                        <input type="text" id="newPortalName" placeholder="e.g. Thailand e-Visa"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-600 mb-1 block">Portal URL</label>
                        <input type="text" id="newPortalUrl" placeholder="https://..."
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-600 mb-1 block">Portal Type</label>
                        <select id="newPortalType" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="visa">Visa</option>
                            <option value="air_ticket">Air Ticket</option>
                            <option value="hotel">Hotel</option>
                            <option value="package">Package</option>
                            <option value="umrah">Umrah</option>
                            <option value="transport">Transport</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
        
                    <div class="border-t border-gray-100 pt-3 mt-2">
                        <p class="text-xs font-semibold text-gray-600 mb-2">First Credential (optional, can add later)</p>
                        <div id="createCredFields"></div>
                    </div>
                </div>
                <div class="flex gap-2 px-5 pb-5">
                    <button onclick="submitCreatePortal()" class="flex-1 bg-blue-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-blue-700">Create</button>
                    <button onclick="closeCreatePortalModal()" class="px-4 py-2 border border-gray-300 text-gray-600 rounded-lg text-sm hover:bg-gray-50">Cancel</button>
                </div>
            </div>
        </div>
        
        <!-- Add/Edit Credential Modal -->
        <div id="credModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
                    <h3 class="font-semibold text-gray-800" id="credModalTitle">Add Credential</h3>
                    <button onclick="closeCredModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
                </div>
                <div class="p-5" id="credFieldsContainer"></div>
                <div class="flex gap-2 px-5 pb-5">
                    <button onclick="submitCredModal()" class="flex-1 bg-blue-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-blue-700">Save</button>
                    <button onclick="closeCredModal()" class="px-4 py-2 border border-gray-300 text-gray-600 rounded-lg text-sm hover:bg-gray-50">Cancel</button>
                </div>
            </div>
        </div>
    </main>
    <script>
    (function() {
        const CURRENT_USER_ID = <?= json_encode($authUserId ?? '') ?>;
        const IS_ADMIN = <?= json_encode(($_SESSION['role'] ?? null) == '0') ?>;
        const API = '/api/masterdata/portal-links.php';
        const EMPLOYEES_API = '/api/employees/all-employees.php';
    
        const TYPE_LABELS = {
            visa: 'Visa', air_ticket: 'Air Ticket', hotel: 'Hotel',
            package: 'Package', umrah: 'Umrah', transport: 'Transport', other: 'Other',
        };
    
        let portals = [];
        let employeesData = [];
        let currentTypeFilter = '';
    
        // ── Credential field-set builder (reused by create + add/edit modal) ────
        // access_user select এর জন্য নিজস্ব multi-select chip picker — existing
        // pages/form-select/employees.php single-select, তাই এখানে আলাদা বানানো
        function credFieldsHtml(prefix, existing) {
            existing = existing || {};
            const accessUsers = existing.access_user || [];
            return `
                <div class="space-y-3">
                    <div>
                        <label class="text-xs font-medium text-gray-600 mb-1 block">Username</label>
                        <input type="text" id="${prefix}UserName" value="${escHtml(existing.user_name || '')}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-600 mb-1 block">
                            Password ${existing.cred_id ? '<span class="text-[10px] text-gray-400">(খালি রাখলে আগেরটাই থাকবে)</span>' : ''}
                        </label>
                        <input type="password" id="${prefix}Password" placeholder="${existing.cred_id ? '••••••••' : ''}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="${prefix}IsHide" ${existing.is_hide ? 'checked' : ''} onchange="toggleAccessUserVisibility('${prefix}')"
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <label for="${prefix}IsHide" class="text-xs text-gray-600">Hide from others (শুধু নির্দিষ্ট employee-রা দেখতে পারবে)</label>
                    </div>
                    <div id="${prefix}AccessUserWrap" class="${existing.is_hide ? '' : 'hidden'}">
                        <label class="text-xs font-medium text-gray-600 mb-1 block">Who can view (আপনি নিজে সবসময় দেখতে পারবেন)</label>
                        <input type="text" id="${prefix}EmpSearch" placeholder="Search employee by name..."
                               oninput="onEmpSearchInput('${prefix}')"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <div id="${prefix}EmpResults" class="hidden mt-1 border border-gray-200 rounded-lg max-h-32 overflow-y-auto"></div>
                        <div id="${prefix}AccessChips" class="flex flex-wrap gap-1 mt-2"></div>
                    </div>
                </div>`;
        }
    
        window.toggleAccessUserVisibility = function(prefix) {
            const checked = document.getElementById(`${prefix}IsHide`).checked;
            document.getElementById(`${prefix}AccessUserWrap`).classList.toggle('hidden', !checked);
        };
    
        // চিপ-ভিত্তিক multi-select state, prefix অনুযায়ী আলাদা রাখা হচ্ছে
        let accessUserState = {};
    
        function renderAccessChips(prefix) {
            const ids = accessUserState[prefix] || [];
            const box = document.getElementById(`${prefix}AccessChips`);
            if (!box) return;
            box.innerHTML = ids.map(id => {
                const emp = employeesData.find(e => e.sys_id === id);
                const name = emp ? parseEmpName(emp) : id;
                return `<span class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 text-[10px] px-2 py-1 rounded-full">
                    ${escHtml(name)}
                    <button type="button" onclick="removeAccessUser('${prefix}', '${id}')" class="text-blue-400 hover:text-blue-700"><i class="fas fa-times"></i></button>
                </span>`;
            }).join('');
        }
    
        window.removeAccessUser = function(prefix, id) {
            accessUserState[prefix] = (accessUserState[prefix] || []).filter(x => x !== id);
            renderAccessChips(prefix);
        };
    
        window.onEmpSearchInput = function(prefix) {
            const q = document.getElementById(`${prefix}EmpSearch`).value.trim().toLowerCase();
            const box = document.getElementById(`${prefix}EmpResults`);
            if (!q) { box.classList.add('hidden'); return; }
    
            const filtered = employeesData.filter(e => parseEmpName(e).toLowerCase().includes(q));
            if (!filtered.length) {
                box.innerHTML = `<div class="px-3 py-2 text-xs text-gray-400">No employee found</div>`;
                box.classList.remove('hidden');
                return;
            }
            box.innerHTML = filtered.slice(0, 10).map(e => `
                <div class="px-3 py-2 text-sm hover:bg-gray-50 cursor-pointer" onclick="addAccessUser('${prefix}', '${e.sys_id}')">
                    ${escHtml(parseEmpName(e))} <span class="text-[10px] text-gray-400">${escHtml(e.sys_id)}</span>
                </div>
            `).join('');
            box.classList.remove('hidden');
        };
    
        window.addAccessUser = function(prefix, sysId) {
            if (!accessUserState[prefix]) accessUserState[prefix] = [];
            if (!accessUserState[prefix].includes(sysId)) accessUserState[prefix].push(sysId);
            document.getElementById(`${prefix}EmpSearch`).value = '';
            document.getElementById(`${prefix}EmpResults`).classList.add('hidden');
            renderAccessChips(prefix);
        };
    
        function parseEmpName(emp) {
            try {
                if (emp.name && typeof emp.name === 'string' && emp.name.startsWith('{')) {
                    return JSON.parse(emp.name).primary || 'Unnamed';
                }
                return emp.name || 'Unnamed';
            } catch (e) { return 'Unnamed'; }
        }
    
        function readCredFields(prefix) {
            return {
                user_name:   document.getElementById(`${prefix}UserName`).value.trim(),
                password:    document.getElementById(`${prefix}Password`).value,
                is_hide:     document.getElementById(`${prefix}IsHide`).checked,
                access_user: accessUserState[prefix] || [],
            };
        }
    
        async function loadEmployees() {
            try {
                const res  = await fetch(EMPLOYEES_API);
                const data = await res.json();
                employeesData = Array.isArray(data.employees) ? data.employees : [];
            } catch (e) { employeesData = []; }
        }
    
        // ── Load & render portals ────────────────────────────────────────────────
        async function loadPortals() {
            document.getElementById('portalsLoading').classList.remove('hidden');
            document.getElementById('portalsList').classList.add('hidden');
            document.getElementById('portalsEmpty').classList.add('hidden');
    
            const url = currentTypeFilter ? `${API}?portal_type=${encodeURIComponent(currentTypeFilter)}` : API;
            const res  = await fetch(url, { credentials: 'include' });
            const data = await res.json();
    
            document.getElementById('portalsLoading').classList.add('hidden');
    
            if (!data.success) {
                document.getElementById('portalsEmpty').textContent = 'Error: ' + (data.message || '');
                document.getElementById('portalsEmpty').classList.remove('hidden');
                return;
            }
    
            portals = data.portals || [];
            if (!portals.length) {
                document.getElementById('portalsEmpty').classList.remove('hidden');
                return;
            }
    
            const list = document.getElementById('portalsList');
            list.classList.remove('hidden');
            list.innerHTML = portals.map(renderPortalCard).join('');
        }
    
        function renderPortalCard(p) {
            return `
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="flex items-center justify-between mb-2">
                    <div>
                        <span class="font-semibold text-gray-800">${escHtml(p.portal_name)}</span>
                        <span class="text-[10px] bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full ml-2">${TYPE_LABELS[p.portal_type] || p.portal_type}</span>
                        ${p.portal_url ? `<a href="${escHtml(p.portal_url)}" target="_blank" class="text-[11px] text-blue-500 hover:underline ml-2">${escHtml(p.portal_url)}</a>` : ''}
                    </div>
                    <div class="flex items-center gap-2">
                        <button onclick="openAddCredentialModal('${p.sys_id}')" class="text-xs text-blue-600 hover:underline">+ Add Credential</button>
                        ${p.can_manage ? `<button onclick="deletePortal('${p.sys_id}')" class="text-gray-300 hover:text-red-500"><i class="fas fa-trash text-xs"></i></button>` : ''}
                    </div>
                </div>
                <div class="space-y-1.5">
                    ${p.credentials.map(c => renderCredRow(p.sys_id, c)).join('') || '<p class="text-xs text-gray-400">No credentials added</p>'}
                </div>
            </div>`;
        }
    
        function renderCredRow(portalSysId, c) {
            const canManageCred = IS_ADMIN || c.is_mine;
            return `
            <div class="flex items-center justify-between bg-gray-50 rounded-lg px-3 py-2">
                <div class="flex items-center gap-2 text-xs">
                    <i class="fas fa-user-circle text-gray-300"></i>
                    <span class="font-medium text-gray-700">${escHtml(c.user_name)}</span>
                    ${c.can_view
                        ? `<span class="font-mono text-gray-500" id="pw_${c.cred_id}">••••••••</span>
                           <button onclick="togglePwShow('${c.cred_id}', '${escHtml(c.password || '')}')" class="text-gray-300 hover:text-gray-600"><i class="fas fa-eye text-[10px]"></i></button>`
                        : `<span class="text-gray-300 italic">Hidden</span>`}
                    ${c.is_hide ? '<span class="text-[9px] bg-amber-50 text-amber-600 px-1.5 py-0.5 rounded-full">Restricted</span>' : ''}
                </div>
                ${canManageCred ? `
                <div class="flex items-center gap-2">
                    <button onclick='openEditCredentialModal("${portalSysId}", ${JSON.stringify(c)})' class="text-gray-300 hover:text-blue-500"><i class="fas fa-pen text-[10px]"></i></button>
                    <button onclick="deleteCredential('${portalSysId}', '${c.cred_id}')" class="text-gray-300 hover:text-red-500"><i class="fas fa-times text-[10px]"></i></button>
                </div>` : ''}
            </div>`;
        }
    
        window.togglePwShow = function(credId, pw) {
            const el = document.getElementById(`pw_${credId}`);
            el.textContent = el.textContent === '••••••••' ? (pw || '(empty)') : '••••••••';
        };
    
        // ── Type filter ───────────────────────────────────────────────────────────
        window.setTypeFilter = function(type) {
            currentTypeFilter = type;
            document.querySelectorAll('.type-filter-btn').forEach(b => {
                const active = b.dataset.type === type;
                b.className = 'type-filter-btn px-3 py-1.5 text-xs rounded-full border ' +
                    (active ? 'border-blue-500 bg-blue-50 text-blue-700 font-medium' : 'border-gray-300 text-gray-600 hover:bg-gray-50');
            });
            loadPortals();
        };
    
        function buildTypeFilterBar() {
            const bar = document.getElementById('typeFilterBar');
            Object.entries(TYPE_LABELS).forEach(([key, label]) => {
                const btn = document.createElement('button');
                btn.dataset.type = key;
                btn.className = 'type-filter-btn px-3 py-1.5 text-xs rounded-full border border-gray-300 text-gray-600 hover:bg-gray-50';
                btn.textContent = label;
                btn.onclick = () => setTypeFilter(key);
                bar.appendChild(btn);
            });
        }
    
        // ── Create Portal modal ───────────────────────────────────────────────────
        window.openCreatePortalModal = function() {
            document.getElementById('newPortalName').value = '';
            document.getElementById('newPortalUrl').value = '';
            document.getElementById('newPortalType').value = 'visa';
            accessUserState['create'] = [];
            document.getElementById('createCredFields').innerHTML = credFieldsHtml('create', {});
            document.getElementById('createPortalModal').classList.remove('hidden');
        };
        window.closeCreatePortalModal = () => document.getElementById('createPortalModal').classList.add('hidden');
    
        window.submitCreatePortal = async function() {
            const name = document.getElementById('newPortalName').value.trim();
            if (!name) { alert('Portal name দিন'); return; }
    
            const cred = readCredFields('create');
            const payload = {
                action: 'create_portal',
                portal_name: name,
                portal_url: document.getElementById('newPortalUrl').value.trim(),
                portal_type: document.getElementById('newPortalType').value,
            };
            if (cred.user_name) payload.credential = cred;
    
            const res = await fetch(API, {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'include',
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (!data.success) { alert('Failed: ' + (data.message || '')); return; }
            closeCreatePortalModal();
            loadPortals();
        };
    
        // ── Add/Edit Credential modal ─────────────────────────────────────────────
        let credModalContext = { portalSysId: null, credId: null };
    
        window.openAddCredentialModal = function(portalSysId) {
            credModalContext = { portalSysId, credId: null };
            accessUserState['cred'] = [];
            document.getElementById('credModalTitle').textContent = 'Add Credential';
            document.getElementById('credFieldsContainer').innerHTML = credFieldsHtml('cred', {});
            document.getElementById('credModal').classList.remove('hidden');
        };
    
        window.openEditCredentialModal = function(portalSysId, cred) {
            credModalContext = { portalSysId, credId: cred.cred_id };
            accessUserState['cred'] = cred.access_user || [];
            document.getElementById('credModalTitle').textContent = 'Edit Credential';
            document.getElementById('credFieldsContainer').innerHTML = credFieldsHtml('cred', cred);
            renderAccessChips('cred');
            document.getElementById('credModal').classList.remove('hidden');
        };
    
        window.closeCredModal = () => document.getElementById('credModal').classList.add('hidden');
    
        window.submitCredModal = async function() {
            const cred = readCredFields('cred');
            if (!cred.user_name) { alert('Username দিন'); return; }
    
            const isEdit = !!credModalContext.credId;
            const payload = isEdit
                ? { action: 'update_credential', portal_sys_id: credModalContext.portalSysId, cred_id: credModalContext.credId, credential: cred }
                : { action: 'add_credential', portal_sys_id: credModalContext.portalSysId, credential: cred };
    
            const res = await fetch(API, {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'include',
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (!data.success) { alert('Failed: ' + (data.message || '')); return; }
            closeCredModal();
            loadPortals();
        };
    
        window.deleteCredential = async function(portalSysId, credId) {
            if (!confirm('Delete this credential?')) return;
            const res = await fetch(API, {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'include',
                body: JSON.stringify({ action: 'delete_credential', portal_sys_id: portalSysId, cred_id: credId })
            });
            const data = await res.json();
            if (!data.success) { alert('Failed: ' + (data.message || '')); return; }
            loadPortals();
        };
    
        window.deletePortal = async function(sysId) {
            if (!confirm('Delete this entire portal? This cannot be undone.')) return;
            const res = await fetch(`${API}?sys_id=${encodeURIComponent(sysId)}`, { method: 'DELETE', credentials: 'include' });
            const data = await res.json();
            if (!data.success) { alert('Failed: ' + (data.message || '')); return; }
            loadPortals();
        };
    
        function escHtml(s) {
            return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        }
    
        buildTypeFilterBar();
        loadEmployees();
        loadPortals();
    })();
    </script>
</body>
</html>