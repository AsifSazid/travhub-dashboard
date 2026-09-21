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
<body>
<?php include '../elements/header.php'; ?>
<?php include '../elements/aside.php'; ?>

<main id="mainContent" class="pt-16 pl-64 mt-16 transition-all duration-300">
<div class="p-6">

    <!-- Top Bar -->
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-globe mr-2 text-blue-500"></i>Portal Links</h1>
            <p class="text-sm text-gray-500 mt-0.5">Visa/Airline/Hotel portal-এর shared credentials — এখানে সংরক্ষিত থাকবে</p>
        </div>
        <div class="flex items-center gap-2">
            <div class="flex bg-gray-100 rounded-lg p-1">
                <button id="btnList" onclick="setView('list')" class="view-btn px-3 py-1.5 rounded-md text-sm font-medium text-gray-500 transition" title="List view"><i class="fas fa-list"></i></button>
                <button id="btnCard" onclick="setView('card')" class="view-btn active px-3 py-1.5 rounded-md text-sm font-medium transition" title="Grid view"><i class="fas fa-th-large"></i></button>
            </div>
            <button onclick="openCreatePortalModal()"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">
                <i class="fas fa-plus"></i> New Portal
            </button>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5" id="statsRow">
        <div class="bg-white rounded-xl p-4 shadow-sm border-l-4 border-gray-300 cursor-pointer stat-card" data-filter="">
            <div class="text-2xl font-bold text-gray-700" id="statAll">—</div>
            <div class="text-xs text-gray-500 mt-0.5">All Portals</div>
        </div>
        <!-- বাকি stat card গুলো টাইপ অনুযায়ী JS-এ ডাইনামিক ভাবে বসবে -->
    </div>

    <!-- Main Card -->
    <div class="bg-white rounded-xl shadow-sm p-5">

        <!-- Search & Filter -->
        <div class="flex flex-wrap items-center gap-3 mb-4">
            <div class="relative flex-1 min-w-[200px]">
                <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-sm"></i>
                <input type="text" id="searchInput" placeholder="Search by portal name…"
                    class="pl-9 pr-4 py-2.5 border border-gray-300 rounded-lg text-sm w-full focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>
            <select id="filterType" class="px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                <option value="">All Types</option>
                <option value="visa">Visa</option>
                <option value="air_ticket">Air Ticket</option>
                <option value="hotel">Hotel</option>
                <option value="package">Package</option>
                <option value="umrah">Umrah</option>
                <option value="transport">Transport</option>
                <option value="other">Other</option>
            </select>
            <button onclick="resetFilters()" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg text-sm transition">
                <i class="fas fa-rotate-right"></i>
            </button>
        </div>

        <div id="portalsLoading" class="flex items-center gap-2 text-sm text-gray-500 py-6">
            <span class="w-4 h-4 border-2 border-gray-300 border-t-blue-500 rounded-full animate-spin"></span>
            Loading...
        </div>

        <!-- List (table) View -->
        <div id="listView" class="hidden overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase w-10">#</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Portal Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Credentials</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Link</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody id="portalsTableBody" class="divide-y divide-gray-50"></tbody>
            </table>
        </div>

        <!-- Grid (3-column, expand-on-click) View -->
        <div id="gridView" class="hidden">
            <div id="portalsGrid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;"></div>
        </div>

        <div id="portalsEmpty" class="hidden text-sm text-gray-400 py-12 text-center">
            <i class="fas fa-inbox text-3xl mb-2 block"></i>No portals found.
        </div>

        <!-- Pagination (Grid view-তে pagination দেখানো হয় না — expand-card layout এর সাথে pagination মেলে না, শুধু List view-তে) -->
        <div class="flex items-center justify-between mt-5 pt-4 border-t border-gray-100" id="paginationBar">
            <div class="text-sm text-gray-500" id="paginationInfo">—</div>
            <div class="flex items-center gap-2" id="paginationBtns"></div>
        </div>
    </div>
</div>
</main>

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

<!-- Portal Detail Modal (List view row → Actions → View, shows same expand-card content) -->
<div id="portalDetailModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
            <h3 class="font-semibold text-gray-800" id="portalDetailTitle">Portal</h3>
            <button onclick="closePortalDetailModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-5" id="portalDetailBody"></div>
    </div>
</div>

<!-- Toast -->
<div id="toast" class="fixed bottom-6 right-6 z-50 hidden">
    <div id="toastInner" class="flex items-center gap-3 px-5 py-3 rounded-xl shadow-lg text-white text-sm font-medium">
        <i id="toastIcon" class="fas fa-check-circle text-lg"></i>
        <span id="toastMsg"></span>
    </div>
</div>

<style>
.view-btn.active { background:#2563eb; color:#fff; }
</style>

<?php include '../elements/floating-menus.php'; ?>
<script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>

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
    const TYPE_ICONS = {
        visa: 'fa-passport', air_ticket: 'fa-plane', hotel: 'fa-hotel',
        package: 'fa-suitcase-rolling', umrah: 'fa-kaaba', transport: 'fa-bus', other: 'fa-circle',
    };

    let allPortals       = [];
    let filteredPortals  = [];
    let currentPage      = 1;
    let currentView      = 'card'; // ডিফল্ট grid — আগের ব্যবহারকারীর পছন্দ অনুযায়ী
    const perPage         = 15;
    let expandedPortalId = null; // grid view-এ কোন card expand আছে
    let employeesData     = [];

    // ═══════════════════════ Credential field-set (Add/Edit modal) ═══════
    function credFieldsHtml(prefix, existing) {
        existing = existing || {};
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

    // ═══════════════════════ Load & stats ═══════════════════════
    async function loadPortals() {
        document.getElementById('portalsLoading').classList.remove('hidden');
        document.getElementById('listView').classList.add('hidden');
        document.getElementById('gridView').classList.add('hidden');
        document.getElementById('portalsEmpty').classList.add('hidden');

        const res  = await fetch(API, { credentials: 'include' });
        const data = await res.json();

        document.getElementById('portalsLoading').classList.add('hidden');

        if (!data.success) {
            document.getElementById('portalsEmpty').textContent = 'Error: ' + (data.message || '');
            document.getElementById('portalsEmpty').classList.remove('hidden');
            return;
        }

        allPortals = data.portals || [];
        updateStats();
        applyFilters();
    }

    function updateStats() {
        const counts = {};
        allPortals.forEach(p => { counts[p.portal_type] = (counts[p.portal_type] || 0) + 1; });

        const row = document.getElementById('statsRow');
        const colorMap = {
            visa: 'border-violet-400 text-violet-600', air_ticket: 'border-sky-400 text-sky-600',
            hotel: 'border-pink-400 text-pink-600', package: 'border-green-400 text-green-600',
            umrah: 'border-amber-400 text-amber-600', transport: 'border-teal-400 text-teal-600',
            other: 'border-gray-400 text-gray-600',
        };

        const cards = [`<div class="bg-white rounded-xl p-4 shadow-sm border-l-4 border-gray-300 cursor-pointer stat-card" data-filter="">
            <div class="text-2xl font-bold text-gray-700">${allPortals.length}</div>
            <div class="text-xs text-gray-500 mt-0.5">All Portals</div>
        </div>`];

        Object.keys(TYPE_LABELS).forEach(type => {
            if (!counts[type]) return; // শুধু ব্যবহৃত টাইপগুলোরই কার্ড দেখাও
            const cls = colorMap[type] || 'border-gray-400 text-gray-600';
            cards.push(`<div class="bg-white rounded-xl p-4 shadow-sm border-l-4 ${cls.split(' ')[0]} cursor-pointer stat-card" data-filter="${type}">
                <div class="text-2xl font-bold ${cls.split(' ')[1]}">${counts[type]}</div>
                <div class="text-xs text-gray-500 mt-0.5">${TYPE_LABELS[type]}</div>
            </div>`);
        });

        row.innerHTML = cards.join('');
        row.className = `grid grid-cols-2 md:grid-cols-${Math.min(cards.length, 5)} gap-3 mb-5`;
        row.querySelectorAll('.stat-card').forEach(card => {
            card.addEventListener('click', () => {
                document.getElementById('filterType').value = card.dataset.filter;
                applyFilters();
            });
        });
    }

    // ═══════════════════════ Filters ═══════════════════════
    function applyFilters() {
        const search = document.getElementById('searchInput').value.toLowerCase().trim();
        const type   = document.getElementById('filterType').value;

        filteredPortals = allPortals.filter(p => {
            const matchSearch = !search || (p.portal_name || '').toLowerCase().includes(search);
            const matchType   = !type || p.portal_type === type;
            return matchSearch && matchType;
        });

        currentPage = 1;
        expandedPortalId = null;
        render();
    }

    window.resetFilters = function() {
        document.getElementById('searchInput').value = '';
        document.getElementById('filterType').value  = '';
        applyFilters();
    };

    document.getElementById('searchInput').addEventListener('input', applyFilters);
    document.getElementById('filterType').addEventListener('change', applyFilters);

    // ═══════════════════════ View toggle ═══════════════════════
    window.setView = function(view) {
        currentView = view;
        const list = document.getElementById('listView');
        const grid = document.getElementById('gridView');
        const btnL = document.getElementById('btnList');
        const btnC = document.getElementById('btnCard');

        if (view === 'list') {
            grid.classList.add('hidden');  list.classList.remove('hidden');
            btnL.classList.add('active');  btnC.classList.remove('active'); btnC.classList.add('text-gray-500');
            document.getElementById('paginationBar').classList.remove('hidden');
        } else {
            list.classList.add('hidden');  grid.classList.remove('hidden');
            btnC.classList.add('active');  btnL.classList.remove('active'); btnL.classList.add('text-gray-500');
            document.getElementById('paginationBar').classList.add('hidden'); // grid-এ expand-card এর জন্য pagination রাখা হয়নি
        }
        render();
    };

    function render() {
        if (!filteredPortals.length) {
            document.getElementById('listView').classList.add('hidden');
            document.getElementById('gridView').classList.add('hidden');
            document.getElementById('portalsEmpty').classList.remove('hidden');
            document.getElementById('paginationInfo').textContent = 'No results';
            document.getElementById('paginationBtns').innerHTML = '';
            return;
        }
        document.getElementById('portalsEmpty').classList.add('hidden');

        if (currentView === 'list') {
            document.getElementById('listView').classList.remove('hidden');
            renderTable();
            renderPagination();
        } else {
            document.getElementById('gridView').classList.remove('hidden');
            renderGrid();
        }
    }

    // ═══════════════════════ List (table) render ═══════════════════════
    function renderTable() {
        const tbody = document.getElementById('portalsTableBody');
        const start = (currentPage - 1) * perPage;
        const page  = filteredPortals.slice(start, start + perPage);

        tbody.innerHTML = page.map((p, idx) => {
            const creds = p.credentials ?? [];
            const url   = p.portal_url ? escHtml(p.portal_url) : '';
            return `
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-4 py-3 text-gray-500 text-xs">${start + idx + 1}</td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center flex-shrink-0">
                            <i class="fas ${TYPE_ICONS[p.portal_type] || 'fa-circle'} text-xs"></i>
                        </div>
                        <span class="font-medium text-gray-800 text-sm">${escHtml(p.portal_name)}</span>
                    </div>
                </td>
                <td class="px-4 py-3"><span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">${TYPE_LABELS[p.portal_type] || p.portal_type}</span></td>
                <td class="px-4 py-3 text-xs text-gray-500">${creds.length} credential${creds.length!==1?'s':''}</td>
                <td class="px-4 py-3 text-xs">${url ? `<a href="${url}" target="_blank" class="text-blue-500 hover:underline"><i class="fas fa-external-link-alt mr-1"></i>Open</a>` : '<span class="text-gray-300">—</span>'}</td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-1">
                        <button onclick="openPortalDetailModal('${p.sys_id}')" class="px-2.5 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs" title="View / Manage">
                            <i class="fas fa-eye"></i>
                        </button>
                        ${p.can_manage ? `<button onclick="deletePortal('${p.sys_id}')" class="px-2.5 py-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 text-xs" title="Delete"><i class="fas fa-trash"></i></button>` : ''}
                    </div>
                </td>
            </tr>`;
        }).join('');
    }

    function renderPagination() {
        const total = filteredPortals.length;
        const pages = Math.ceil(total / perPage);
        const start = total ? (currentPage - 1) * perPage + 1 : 0;
        const end   = Math.min(currentPage * perPage, total);
        document.getElementById('paginationInfo').textContent = total ? `Showing ${start}–${end} of ${total} portals` : 'No results';

        const btns = document.getElementById('paginationBtns');
        btns.innerHTML = '';
        if (pages <= 1) return;

        const addBtn = (label, page, disabled, active) => {
            const btn = document.createElement('button');
            btn.innerHTML = label;
            btn.className = `px-3 py-1.5 rounded-lg text-sm border font-medium transition ${active ? 'bg-blue-600 text-white border-blue-600' : disabled ? 'bg-gray-50 text-gray-300 border-gray-200 cursor-not-allowed' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'}`;
            if (!disabled && !active) btn.onclick = () => { currentPage = page; renderTable(); renderPagination(); };
            btns.appendChild(btn);
        };
        addBtn('<i class="fas fa-chevron-left text-xs"></i>', currentPage - 1, currentPage === 1);
        for (let p = 1; p <= pages; p++) addBtn(p, p, false, p === currentPage);
        addBtn('<i class="fas fa-chevron-right text-xs"></i>', currentPage + 1, currentPage === pages);
    }

    // ═══════════════════════ Grid (3-column expand-card) render ═══════════
    function renderGrid() {
        const grid = document.getElementById('portalsGrid');
        grid.innerHTML = filteredPortals.map(p => expandedPortalId === p.sys_id
            ? `<div style="grid-column:1/-1;">${renderExpandedCard(p)}</div>`
            : renderCollapsedCard(p)
        ).join('');
    }

    function renderCollapsedCard(p) {
        const creds = p.credentials ?? [];
        const mine  = creds.find(c => c.is_mine);
        const url   = p.portal_url ? escHtml(p.portal_url) : '';

        return `<div class="bg-white border border-gray-200 rounded-xl p-4 cursor-pointer hover:border-blue-300 transition"
                     onclick="expandPortal('${p.sys_id}')">
            <div class="flex items-center justify-between mb-1">
                <span class="font-semibold text-gray-800 text-sm truncate flex-1">${escHtml(p.portal_name)}</span>
                ${url ? `<a href="${url}" target="_blank" onclick="event.stopPropagation()" class="text-blue-400 hover:text-blue-600 flex-shrink-0 ml-2"><i class="fas fa-external-link-alt text-xs"></i></a>` : ''}
            </div>
            <span class="text-[10px] bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">${TYPE_LABELS[p.portal_type] || p.portal_type}</span>
            ${mine ? `<div class="text-xs text-gray-400 font-mono mt-2 truncate">${escHtml(mine.user_name || '')}</div>` : ''}
            ${creds.length ? `<div class="text-[10px] text-gray-300 mt-1">${creds.length} credential${creds.length!==1?'s':''}</div>` : '<div class="text-[10px] text-gray-300 mt-1">No credentials</div>'}
        </div>`;
    }

    function renderExpandedCard(p) {
        const creds = p.credentials ?? []; // backend আগেই শুধু can_view=true credential পাঠায়
        const url   = p.portal_url ? escHtml(p.portal_url) : '';

        return `<div class="bg-white border-2 border-blue-300 rounded-xl p-4">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <span class="font-semibold text-gray-800">${escHtml(p.portal_name)}</span>
                    <span class="text-[10px] bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full ml-2">${TYPE_LABELS[p.portal_type] || p.portal_type}</span>
                    ${url ? `<a href="${url}" target="_blank" class="text-[11px] text-blue-500 hover:underline ml-2">${url}</a>` : ''}
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="openAddCredentialModal('${p.sys_id}')" class="text-xs text-blue-600 hover:underline">+ Add Credential</button>
                    ${p.can_manage ? `<button onclick="deletePortal('${p.sys_id}')" class="text-gray-300 hover:text-red-500"><i class="fas fa-trash text-xs"></i></button>` : ''}
                    <button onclick="collapsePortal()" class="text-gray-300 hover:text-gray-600" title="Collapse"><i class="fas fa-compress-alt text-xs"></i></button>
                </div>
            </div>
            <div class="space-y-1.5">
                ${creds.map(c => renderCredRow(p.sys_id, c)).join('') || '<p class="text-xs text-gray-400">No accessible credentials</p>'}
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
                <span class="font-mono text-gray-500" id="pw_${c.cred_id}">••••••••</span>
                <button onclick="togglePwShow('${c.cred_id}', ${JSON.stringify(c.password || '')})" class="text-gray-300 hover:text-gray-600"><i class="fas fa-eye text-[10px]"></i></button>
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

    window.expandPortal = function(sysId) { expandedPortalId = sysId; renderGrid(); };
    window.collapsePortal = function() { expandedPortalId = null; renderGrid(); };

    // ═══════════════════════ List view "View" → modal (same expand content) ═
    window.openPortalDetailModal = function(sysId) {
        const p = allPortals.find(x => x.sys_id === sysId);
        if (!p) return;
        document.getElementById('portalDetailTitle').textContent = p.portal_name;
        document.getElementById('portalDetailBody').innerHTML = renderExpandedCard(p).replace(
            /onclick="collapsePortal\(\)"/, 'onclick="closePortalDetailModal()"'
        ).replace('<i class="fas fa-compress-alt text-xs"></i>', '<i class="fas fa-times text-xs"></i>');
        document.getElementById('portalDetailModal').classList.remove('hidden');
    };
    window.closePortalDetailModal = () => document.getElementById('portalDetailModal').classList.add('hidden');

    // ═══════════════════════ Create Portal modal ═══════════════════════
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
        if (!name) { alert('Please enter a portal name'); return; }

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
        showToast('success', 'Portal created!');
        loadPortals();
    };

    // ═══════════════════════ Add/Edit Credential modal ═══════════════════
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
        if (!cred.user_name) { alert('Please enter a username'); return; }

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
        showToast('success', 'Saved!');
        await refreshAndReExpand(credModalContext.portalSysId);
    };

    window.deleteCredential = async function(portalSysId, credId) {
        if (!confirm('Delete this credential?')) return;
        const res = await fetch(API, {
            method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'include',
            body: JSON.stringify({ action: 'delete_credential', portal_sys_id: portalSysId, cred_id: credId })
        });
        const data = await res.json();
        if (!data.success) { alert('Failed: ' + (data.message || '')); return; }
        showToast('success', 'Deleted');
        await refreshAndReExpand(portalSysId);
    };

    window.deletePortal = async function(sysId) {
        if (!confirm('Delete this entire portal? This cannot be undone.')) return;
        const res = await fetch(`${API}?sys_id=${encodeURIComponent(sysId)}`, { method: 'DELETE', credentials: 'include' });
        const data = await res.json();
        if (!data.success) { alert('Failed: ' + (data.message || '')); return; }
        expandedPortalId = null;
        closePortalDetailModal();
        showToast('success', 'Portal deleted');
        loadPortals();
    };

    // credential add/edit/delete-এর পর পুরো তালিকা রিফ্রেশ, expand state ধরে রেখে
    async function refreshAndReExpand(portalSysId) {
        const res  = await fetch(API, { credentials: 'include' });
        const data = await res.json();
        if (!data.success) return;
        allPortals = data.portals || [];
        updateStats();
        applyFilters();
        expandedPortalId = portalSysId;
        if (currentView === 'card') renderGrid();
        else {
            // List view-এ থাকলে detail modal-টাও রিফ্রেশ করি (খোলা থাকলে)
            const p = allPortals.find(x => x.sys_id === portalSysId);
            if (p && !document.getElementById('portalDetailModal').classList.contains('hidden')) {
                openPortalDetailModal(portalSysId);
            }
        }
    }

    function escHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function showToast(type, msg) {
        const toast = document.getElementById('toast');
        const inner = document.getElementById('toastInner');
        document.getElementById('toastMsg').textContent = msg;
        inner.className = `flex items-center gap-3 px-5 py-3 rounded-xl shadow-lg text-white text-sm font-medium ${type==='success' ? 'bg-green-600' : 'bg-red-500'}`;
        document.getElementById('toastIcon').className  = `fas ${type==='success' ? 'fa-check-circle' : 'fa-exclamation-circle'} text-lg`;
        toast.classList.remove('hidden');
        setTimeout(() => toast.classList.add('hidden'), 3500);
    }

    // প্রাথমিক view-বাটন visual state (default grid/card)
    document.getElementById('btnCard').classList.add('active');

    loadEmployees();
    loadPortals();
})();
</script>
</body>
</html>