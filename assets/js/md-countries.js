/**
 * TravHub — MdCountries  (assets/js/md-countries.js)
 * ════════════════════════════════════════════════════
 * Features:
 *  - List countries with accordion cities inline
 *  - Search + region filter + status tabs
 *  - for_package toggle (globe icon) — controls package builder availability
 *  - Sync from JSON / Upload JSON / Export to JSON
 *  - Add / Edit country modal with city builder
 *  - Soft delete + restore + confirm dialog
 *  - Pagination + auto-export after every DB change
 */

const MdCountries = (() => {
    'use strict';

    const REGIONS    = ['Africa','Asia','Europe','Middle East','North America','Oceania','South America'];
    const CITY_TYPES = ['tourism','business','religious','education','conference'];

    const st = {
        page: 1, limit: 15, search: '', region: '',
        status: 'active', for_package: '',
        expanded: new Set(), timer: null,
    };

    let pendingCities = [];

    // ── Init ──────────────────────────────────────────────────────────
    function init() { renderShell(); bindEvents(); load(); }

    // ── Shell ─────────────────────────────────────────────────────────
    function renderShell() {
        document.getElementById('mainContent').innerHTML = `
        <div class="px-6 py-6 max-w-screen-xl mx-auto">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-[#1A2039]">Countries</h1>
                    <p class="text-sm text-gray-400 mt-0.5">Manage countries and package availability</p>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <button id="btnSyncImport"
                        class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold border border-blue-300 text-blue-700 bg-blue-50 hover:bg-blue-100 transition">
                        <i class="fa-solid fa-cloud-arrow-down"></i> Sync from JSON
                    </button>
                    <button id="btnUploadJson"
                        class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold border border-green-300 text-green-700 bg-green-50 hover:bg-green-100 transition">
                        <i class="fa-solid fa-file-import"></i> Upload JSON
                    </button>
                    <input type="file" id="jsonFileInput" accept=".json" class="hidden">
                    <button id="btnSyncExport"
                        class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold border border-gray-200 text-gray-600 hover:bg-gray-50 transition">
                        <i class="fa-solid fa-cloud-arrow-up"></i> Export to JSON
                    </button>
                    <button id="btnAdd"
                        class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold bg-[#1A2039] hover:bg-[#252d4a] text-white transition shadow-sm">
                        <i class="fa-solid fa-plus"></i> Add Country
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-5 flex flex-col sm:flex-row gap-3 flex-wrap">
                <div class="relative flex-1 min-w-48">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input id="srch" type="text" placeholder="Search country, code, currency…"
                        class="w-full pl-9 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#50BC81]">
                </div>
                <select id="fltRegion" class="text-sm px-3 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#50BC81] bg-white text-gray-700">
                    <option value="">All Regions</option>
                    ${REGIONS.map(r => `<option value="${r}">${r}</option>`).join('')}
                </select>
                <!-- Package filter -->
                <select id="fltPackage" class="text-sm px-3 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#50BC81] bg-white text-gray-700">
                    <option value="">All Countries</option>
                    <option value="1">Package Enabled</option>
                    <option value="0">Package Disabled</option>
                </select>
                <div class="flex gap-2">
                    ${['active','trash'].map(t => `
                    <button data-tab="${t}" class="tab-btn px-4 py-2 text-sm font-medium rounded-xl border transition
                        ${t === 'active' ? 'bg-[#1A2039] text-white border-[#1A2039]' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'}">
                        ${t === 'active' ? 'Active' : '<i class="fa-solid fa-trash mr-1"></i>Trash'}
                    </button>`).join('')}
                </div>
            </div>

            <!-- Package filter info bar -->
            <div id="pkgFilterInfo" class="hidden mb-4 px-4 py-2.5 bg-[#50BC81]/10 border border-[#50BC81]/30 rounded-xl text-sm text-[#3da868] flex items-center justify-between">
                <span><i class="fa-solid fa-globe mr-2"></i>Showing package-enabled countries only</span>
                <button onclick="MdCountries._clearPkgFilter()" class="text-xs font-semibold hover:underline">Show all</button>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-4">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wide border-b border-gray-200">
                            <th class="px-4 py-3 w-8"></th>
                            <th class="px-4 py-3 text-left">Country</th>
                            <th class="px-4 py-3 text-left w-16">Code</th>
                            <th class="px-4 py-3 text-left">Currency</th>
                            <th class="px-4 py-3 text-right w-28">Rate (BDT)</th>
                            <th class="px-4 py-3 text-left w-32">Region</th>
                            <th class="px-4 py-3 text-center w-20">Cities</th>
                            <th class="px-4 py-3 text-center w-28"
                                title="Toggle to enable/disable this country in Package Builder">
                                <span class="flex items-center justify-center gap-1">
                                    <i class="fa-solid fa-globe text-[#50BC81]"></i> Package
                                </span>
                            </th>
                            <th class="px-4 py-3 text-center w-24">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tBody" class="divide-y divide-gray-50"></tbody>
                </table>
                <div id="tEmpty" class="hidden text-center py-16 text-gray-300">
                    <i class="fa-solid fa-earth-asia text-5xl mb-3 block opacity-30"></i>
                    <p class="text-sm font-medium">No countries found</p>
                    <p class="text-xs mt-1">Try syncing from JSON or adjusting filters</p>
                </div>
                <div id="tLoading" class="text-center py-16 text-gray-300">
                    <i class="fa-solid fa-spinner fa-spin text-3xl"></i>
                </div>
            </div>

            <!-- Pagination + package count -->
            <div id="pgBox" class="flex items-center justify-between"></div>
        </div>

        <!-- ── Country Modal ─────────────────────────────────────────── -->
        <div id="modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
          <div class="w-full max-w-3xl max-h-[90vh] flex flex-col bg-white rounded-2xl shadow-2xl">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
                <h2 id="modalTitle" class="text-lg font-bold text-[#1A2039]">Add Country</h2>
                <button id="modalClose" class="text-gray-400 hover:text-gray-600 w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">
                <input type="hidden" id="fSysId">
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="th-label">Country Name <span class="text-red-500">*</span></label>
                        <input id="fName" type="text" placeholder="e.g. Thailand" class="th-input"></div>
                    <div><label class="th-label">ISO Code <span class="text-red-500">*</span></label>
                        <input id="fCode" type="text" placeholder="TH" maxlength="3" class="th-input uppercase"></div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="th-label">Currency Name <span class="text-red-500">*</span></label>
                        <input id="fCurrency" type="text" placeholder="Thai Baht" class="th-input"></div>
                    <div><label class="th-label">Currency Code <span class="text-red-500">*</span></label>
                        <input id="fCurrCode" type="text" placeholder="THB" maxlength="5" class="th-input uppercase"></div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="th-label">BDT Rate <span class="text-red-500">*</span></label>
                        <input id="fRate" type="number" step="0.0001" placeholder="3.21" class="th-input"></div>
                    <div><label class="th-label">Region <span class="text-red-500">*</span></label>
                        <select id="fRegion" class="th-input">
                            <option value="">Select region</option>
                            ${REGIONS.map(r => `<option value="${r}">${r}</option>`).join('')}
                        </select>
                    </div>
                </div>

                <!-- for_package toggle in modal -->
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-200">
                    <div>
                        <div class="font-semibold text-sm text-gray-800 flex items-center gap-2">
                            <i class="fa-solid fa-globe text-[#50BC81]"></i>
                            Available in Package Builder
                        </div>
                        <div class="text-xs text-gray-400 mt-0.5">
                            When enabled, this country appears in destination selection when building packages
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer flex-shrink-0 ml-4">
                        <input type="checkbox" id="fForPackage" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer
                            peer-checked:after:translate-x-full peer-checked:after:border-white
                            after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                            after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all
                            peer-checked:bg-[#50BC81]"></div>
                    </label>
                </div>

                <!-- Cities -->
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <label class="th-label mb-0">Cities</label>
                        <button id="btnAddCity" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-[#50BC81] hover:bg-[#3da868] text-white transition">
                            <i class="fa-solid fa-plus"></i> Add City
                        </button>
                    </div>
                    <div id="cityList" class="space-y-2 max-h-52 overflow-y-auto pr-1"></div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3 flex-shrink-0">
                <button id="modalCancel" class="px-5 py-2 rounded-xl text-sm font-medium border border-gray-200 text-gray-600 hover:bg-gray-50">Cancel</button>
                <button id="btnSave" class="px-6 py-2 rounded-xl text-sm font-semibold bg-[#1A2039] hover:bg-[#252d4a] text-white transition shadow-sm">Save Country</button>
            </div>
          </div>
        </div>

        <!-- ── Sync Result Modal ──────────────────────────────────────── -->
        <div id="syncModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm px-4">
          <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl p-6">
            <h3 id="syncTitle" class="text-base font-bold text-gray-800 mb-4"></h3>
            <div id="syncBody" class="text-sm text-gray-600 space-y-1 max-h-64 overflow-y-auto"></div>
            <div class="flex justify-end mt-5">
                <button id="syncClose" class="px-5 py-2 text-sm font-semibold bg-[#1A2039] hover:bg-[#252d4a] text-white rounded-xl transition">Done</button>
            </div>
          </div>
        </div>

        <!-- ── Confirm Dialog ─────────────────────────────────────────── -->
        <div id="confirmModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm px-4">
          <div class="w-full max-w-sm bg-white rounded-2xl shadow-2xl p-6 text-center">
            <div id="confirmIcon" class="text-4xl mb-3"></div>
            <h3 id="confirmTitle" class="text-base font-bold text-gray-800 mb-1"></h3>
            <p id="confirmMsg" class="text-sm text-gray-500 mb-5"></p>
            <div class="flex gap-3">
                <button id="confirmCancel" class="flex-1 py-2.5 rounded-xl font-semibold border border-gray-200 text-gray-600 hover:bg-gray-50 transition text-sm">Cancel</button>
                <button id="confirmOk" class="flex-1 py-2.5 rounded-xl font-semibold text-white transition text-sm"></button>
            </div>
          </div>
        </div>

        <!-- ── Loader ─────────────────────────────────────────────────── -->
        <div id="cntLoader" class="hidden fixed inset-0 z-50 items-center justify-center bg-white/60 backdrop-blur-sm flex-col gap-3">
            <i class="fa-solid fa-spinner fa-spin text-3xl text-[#1A2039]"></i>
            <p id="cntLoaderMsg" class="text-sm text-gray-500">Loading…</p>
        </div>

        <!-- ── Toast ──────────────────────────────────────────────────── -->
        <div id="cntToast" class="hidden fixed bottom-6 right-6 z-[9999] flex items-center gap-2 px-4 py-3 rounded-xl shadow-xl text-sm font-medium text-white max-w-xs"></div>`;

        document.head.insertAdjacentHTML('beforeend', `<style>
            .th-label{display:block;font-size:.75rem;font-weight:600;color:#4b5563;margin-bottom:.375rem}
            .th-input{width:100%;padding:.5rem .75rem;border:1px solid #e5e7eb;border-radius:.75rem;font-size:.875rem;outline:none;background:#fff}
            .th-input:focus{border-color:#50BC81;box-shadow:0 0 0 2px rgba(80,188,129,.25)}
            .pkg-toggle{position:relative;display:inline-flex;align-items:center;cursor:pointer}
            .pkg-toggle input{position:absolute;opacity:0;width:0;height:0}
            .pkg-slider{display:block;width:40px;height:22px;background:#e5e7eb;border-radius:99px;transition:.25s;position:relative}
            .pkg-slider:after{content:'';position:absolute;top:3px;left:3px;width:16px;height:16px;background:#fff;border-radius:50%;transition:.25s}
            .pkg-toggle input:checked+.pkg-slider{background:#50BC81}
            .pkg-toggle input:checked+.pkg-slider:after{transform:translateX(18px)}
        </style>`);
    }

    // ── Events ────────────────────────────────────────────────────────
    function bindEvents() {
        document.getElementById('btnAdd').onclick          = () => openForm();
        document.getElementById('btnSyncImport').onclick   = () => runSync('import');
        document.getElementById('btnSyncExport').onclick   = () => runSync('export');
        document.getElementById('btnUploadJson').onclick   = () => document.getElementById('jsonFileInput').click();
        document.getElementById('jsonFileInput').onchange  = handleFileUpload;

        document.getElementById('srch').oninput = e => {
            clearTimeout(st.timer);
            st.timer = setTimeout(() => { st.search = e.target.value; st.page = 1; load(); }, 400);
        };
        document.getElementById('fltRegion').onchange  = e => { st.region     = e.target.value; st.page = 1; load(); };
        document.getElementById('fltPackage').onchange = e => {
            st.for_package = e.target.value; st.page = 1;
            document.getElementById('pkgFilterInfo').classList.toggle('hidden', st.for_package !== '1');
            load();
        };
        document.querySelectorAll('.tab-btn').forEach(b => b.onclick = () => {
            st.status = b.dataset.tab; st.page = 1;
            document.querySelectorAll('.tab-btn').forEach(x => {
                const on = x.dataset.tab === st.status;
                x.className = `tab-btn px-4 py-2 text-sm font-medium rounded-xl border transition
                    ${on ? 'bg-[#1A2039] text-white border-[#1A2039]' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'}`;
            });
            load();
        });

        document.getElementById('btnSave').onclick     = save;
        document.getElementById('modalClose').onclick  = () => closeModal('modal');
        document.getElementById('modalCancel').onclick = () => closeModal('modal');
        document.getElementById('btnAddCity').onclick  = () => addCityRow();

        document.getElementById('syncClose').onclick   = () => { closeModal('syncModal'); load(); };
        document.getElementById('confirmCancel').onclick = () => closeModal('confirmModal');
    }

    // ── Load ──────────────────────────────────────────────────────────
    async function load() {
        document.getElementById('tLoading').classList.remove('hidden');
        document.getElementById('tEmpty').classList.add('hidden');
        document.getElementById('tBody').innerHTML = '';

        const params = new URLSearchParams({
            page: st.page, limit: st.limit,
            search: st.search, region: st.region, status: st.status,
        });
        const data = await thApi(`${API_BASE}api/masterdata/countries/list.php?${params}`);
        document.getElementById('tLoading').classList.add('hidden');

        if (!data.success || !data.data?.length) {
            document.getElementById('tEmpty').classList.remove('hidden');
            return;
        }
        
        document.getElementById('tBody').innerHTML = data.data.map(renderRow).join('');
        thPagination('pgBox', data.pagination, 'MdCountries._page');
    }
    function _page(p) { st.page = p; load(); }
    function _clearPkgFilter() {
        st.for_package = '';
        document.getElementById('fltPackage').value = '';
        document.getElementById('pkgFilterInfo').classList.add('hidden');
        st.page = 1; load();
    }

    // ── Row ───────────────────────────────────────────────────────────
    function renderRow(c) {
        const isTrashed  = c.status === 'deleted';
        const forPackage = c.for_package == 1;
        const cities     = c.cities || [];
        const cityCount  = Array.isArray(cities) ? cities.length : (c.city_count || 0);

        return `
        <tr class="hover:bg-gray-50 transition ${isTrashed ? 'opacity-60' : ''}">
            <td class="px-4 py-3">
                <button onclick="MdCountries._toggle('${c.sys_id}')"
                    class="text-gray-400 hover:text-[#1A2039] w-6 h-6 flex items-center justify-center transition">
                    <i id="icon-${c.sys_id}" class="fa-solid fa-chevron-right text-xs transition-transform ${st.expanded.has(c.sys_id) ? 'rotate-90' : ''}"></i>
                </button>
            </td>
            <td class="px-4 py-3">
                <div class="font-semibold text-gray-800">${esc(c.name)}</div>
                <div class="text-xs text-gray-400 font-mono">${esc(c.sys_id)}</div>
            </td>
            <td class="px-4 py-3">
                <span class="font-mono text-xs bg-gray-100 px-2 py-0.5 rounded-lg">${esc(c.code)}</span>
            </td>
            <td class="px-4 py-3">
                <div class="text-gray-700">${esc(c.currency)}</div>
                <div class="text-xs text-gray-400">${esc(c.currency_code)}</div>
            </td>
            <td class="px-4 py-3 text-right font-mono text-sm">${Number(c.default_rate || 0).toFixed(4)}</td>
            <td class="px-4 py-3 text-sm text-gray-600">${esc(c.region || '')}</td>
            <td class="px-4 py-3 text-center">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-[#1A2039]/10 text-[#1A2039] text-xs font-bold">
                    ${cityCount}
                </span>
            </td>

            <!-- for_package toggle column -->
            <td class="px-4 py-3 text-center">
                <label class="pkg-toggle" title="${forPackage ? 'Disable for packages' : 'Enable for packages'}">
                    <input type="checkbox"
                        ${forPackage ? 'checked' : ''}
                        ${isTrashed ? 'disabled' : ''}
                        onchange="MdCountries._togglePackage('${c.sys_id}', this.checked, this)">
                    <span class="pkg-slider"></span>
                </label>
            </td>

            <td class="px-4 py-3">
                <div class="flex items-center justify-center gap-1">
                    ${!isTrashed ? `
                    <button onclick="MdCountries._edit('${c.sys_id}')"
                        class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-blue-50 text-blue-500 transition" title="Edit">
                        <i class="fa-solid fa-pen text-xs"></i>
                    </button>
                    <button onclick="MdCountries._del('${c.sys_id}', false)"
                        class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-red-50 text-red-400 transition" title="Delete">
                        <i class="fa-solid fa-trash text-xs"></i>
                    </button>
                    ` : `
                    <button onclick="MdCountries._del('${c.sys_id}', true)"
                        class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-green-50 text-green-500 transition" title="Restore">
                        <i class="fa-solid fa-rotate-left text-xs"></i>
                    </button>
                    `}
                </div>
            </td>
        </tr>
        <tr id="exp-${c.sys_id}" class="${st.expanded.has(c.sys_id) ? '' : 'hidden'}">
            <td colspan="9" class="px-8 pb-4 bg-[#1A2039]/5">
                <div id="cities-${c.sys_id}">
                    <div class="text-xs text-gray-400 py-2">Loading cities…</div>
                </div>
            </td>
        </tr>`;
    }

    // ── for_package toggle (inline, no page reload) ───────────────────
    async function _togglePackage(sys_id, newValue, checkboxEl) {
        // Optimistic UI — already toggled by browser
        try {
            const res = await thApi(
                `${API_BASE}api/masterdata/countries/toggle-package.php`,
                'POST',
                { sys_id, for_package: newValue ? 1 : 0 }
            );
            if (!res.success) {
                // Revert checkbox if API failed
                checkboxEl.checked = !newValue;
                return toast('error', res.message || 'Toggle failed');
            }
            toast('success', newValue ? `${res.name} enabled for packages` : `${res.name} removed from packages`);
            // Update footer badge
            const badge = document.getElementById('pkgCountBadge');
            if (badge) {
                const current = parseInt(badge.textContent) || 0;
                // Re-count from DOM
                const enabled = document.querySelectorAll('.pkg-toggle input:checked:not(:disabled)').length;
                const total   = parseInt(badge.textContent.match(/of (\d+)/)?.[1] || 0);
                badge.textContent = `${enabled} of ${total} package-enabled`;
            }
        } catch (e) {
            checkboxEl.checked = !newValue;
            toast('error', 'Network error');
        }
    }

    // ── Accordion ─────────────────────────────────────────────────────
    async function _toggle(sys_id) {
        const row  = document.getElementById(`exp-${sys_id}`);
        const icon = document.getElementById(`icon-${sys_id}`);
        if (st.expanded.has(sys_id)) {
            st.expanded.delete(sys_id);
            row.classList.add('hidden');
            icon?.classList.remove('rotate-90');
        } else {
            st.expanded.add(sys_id);
            row.classList.remove('hidden');
            icon?.classList.add('rotate-90');
            await loadCities(sys_id);
        }
    }

    async function loadCities(sys_id) {
        const data = await thApi(`${API_BASE}api/masterdata/countries/get.php?sys_id=${sys_id}`);
        const cities = data.data?.cities || [];
        const el = document.getElementById(`cities-${sys_id}`);
        if (!cities.length) {
            el.innerHTML = '<p class="text-xs text-gray-400 py-2 italic">No cities added yet.</p>';
            return;
        }
        el.innerHTML = `<div class="flex flex-wrap gap-2 py-2">
            ${cities.map(c => `
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white rounded-xl border border-gray-200 text-xs font-medium text-gray-700 shadow-sm">
                <i class="fa-solid fa-location-dot text-[#50BC81] text-xs"></i>
                ${esc(c.name)}
                ${c.type?.length ? `<span class="text-gray-400">${c.type.join(', ')}</span>` : ''}
            </span>`).join('')}
        </div>`;
    }

    // ── Form ──────────────────────────────────────────────────────────
    async function openForm(sys_id = null) {
        pendingCities = [];
        ['fSysId','fName','fCode','fCurrency','fCurrCode','fRate'].forEach(id => thSetVal(id, ''));
        thSetVal('fRegion', '');
        document.getElementById('fForPackage').checked = false;
        document.getElementById('cityList').innerHTML  = '';
        document.getElementById('modalTitle').textContent = sys_id ? 'Edit Country' : 'Add Country';

        if (sys_id) {
            thSetVal('fSysId', sys_id);
            showLoader('Loading…');
            const data = await thApi(`${API_BASE}api/masterdata/countries/get.php?sys_id=${sys_id}`);
            hideLoader();
            const c = data.data;
            thSetVal('fName',     c.name);
            thSetVal('fCode',     c.code);
            thSetVal('fCurrency', c.currency);
            thSetVal('fCurrCode', c.currency_code);
            thSetVal('fRate',     c.default_rate);
            thSetVal('fRegion',   c.region);
            document.getElementById('fForPackage').checked = c.for_package == 1;
            pendingCities = [...(c.cities || [])];
        }
        renderCityList();
        openModal('modal');
    }
    async function _edit(sys_id) { await openForm(sys_id); }

    function renderCityList() {
        document.getElementById('cityList').innerHTML = pendingCities.length
            ? pendingCities.map((c, i) => `
            <div class="flex items-center gap-3 bg-gray-50 rounded-xl px-3 py-2.5 border border-gray-100">
                <i class="fa-solid fa-location-dot text-[#50BC81] flex-shrink-0 text-xs"></i>
                <div class="flex-1 min-w-0">
                    <div class="font-medium text-sm text-gray-800">${esc(c.name)}</div>
                    ${c.type?.length ? `<div class="text-xs text-gray-400">${c.type.join(', ')}</div>` : ''}
                </div>
                <button onclick="MdCountries._removeCity(${i})"
                    class="text-red-400 hover:text-red-600 w-6 h-6 flex items-center justify-center rounded flex-shrink-0">
                    <i class="fa-solid fa-times text-xs"></i>
                </button>
            </div>`).join('')
            : '<p class="text-xs text-gray-400 italic">No cities yet — add one below.</p>';
    }

    function addCityRow() {
        const name = prompt('City name:');
        if (!name?.trim()) return;
        const typesRaw = prompt('City types (comma separated):\ntourism, business, religious, education, conference', 'tourism');
        const types = typesRaw ? typesRaw.split(',').map(t => t.trim()).filter(Boolean) : ['tourism'];
        pendingCities.push({ name: name.trim(), type: types, popularity: 3, cost_level: 'medium', visa_ease: 'medium' });
        renderCityList();
    }
    function _removeCity(i) { pendingCities.splice(i, 1); renderCityList(); }

    // ── Save ──────────────────────────────────────────────────────────
    async function save() {
        const sys_id = thVal('fSysId');
        const body   = {
            sys_id:        sys_id || undefined,
            name:          thVal('fName'),
            code:          thVal('fCode').toUpperCase(),
            currency:      thVal('fCurrency'),
            currency_code: thVal('fCurrCode').toUpperCase(),
            default_rate:  parseFloat(thVal('fRate') || 1),
            region:        thVal('fRegion'),
            for_package:   document.getElementById('fForPackage').checked ? 1 : 0,
        };
        if (!body.name || !body.code || !body.currency || !body.currency_code || !body.region) {
            return toast('error', 'Fill all required fields');
        }

        document.getElementById('btnSave').disabled = true;
        showLoader('Saving…');
        const res = await thApi(`${API_BASE}api/masterdata/countries/save.php`, 'POST', body);
        hideLoader();
        document.getElementById('btnSave').disabled = false;
        if (!res.success) return toast('error', res.message || 'Error');

        // Save cities for new country
        if (res.action === 'created' && pendingCities.length) {
            for (const city of pendingCities) {
                await thApi(`${API_BASE}api/masterdata/countries/city-save.php`, 'POST', {
                    country_sys_id: res.sys_id, ...city,
                });
            }
        }

        await autoExportJSON();
        toast('success', res.message || 'Saved!');
        closeModal('modal');
        load();
    }

    // ── Delete / Restore ──────────────────────────────────────────────
    async function _del(sys_id, restore) {
        if (!restore) {
            confirmAction({
                icon: '🗑️', title: 'Delete Country?',
                msg: 'Moves to Trash. Can be restored later.',
                color: 'bg-red-500 hover:bg-red-600', label: 'Delete',
                onOk: async () => {
                    showLoader('Deleting…');
                    const res = await thApi(`${API_BASE}api/masterdata/countries/delete.php`, 'POST', { sys_id, restore: false });
                    hideLoader();
                    toast(res.success ? 'success' : 'error', res.message);
                    await autoExportJSON(); load();
                },
            });
        } else {
            showLoader('Restoring…');
            const res = await thApi(`${API_BASE}api/masterdata/countries/delete.php`, 'POST', { sys_id, restore: true });
            hideLoader();
            toast(res.success ? 'success' : 'error', res.message);
            await autoExportJSON(); load();
        }
    }

    // ── Sync ──────────────────────────────────────────────────────────
    async function runSync(action) {
        showLoader(action === 'import' ? 'Syncing JSON → DB…' : 'Exporting DB → JSON…');
        try {
            const res = await thApi(`${API_BASE}api/masterdata/countries/sync.php`, 'POST', { action });
            hideLoader();
            if (!res.success) throw new Error(res.message);
            document.getElementById('syncTitle').textContent =
                action === 'import' ? '✅ Import Complete' : '✅ Export Complete';
            if (action === 'import') {
                document.getElementById('syncBody').innerHTML = `
                    <p class="font-semibold text-green-700">${esc(res.message)}</p>
                    <p class="text-gray-500 mt-1">Inserted: <b>${res.inserted}</b> &nbsp;|&nbsp; Updated: <b>${res.updated}</b> &nbsp;|&nbsp; Skipped: <b>${res.skipped}</b></p>
                    ${res.log?.length ? `<div class="mt-3 text-xs text-gray-400 font-mono max-h-40 overflow-y-auto border border-gray-100 rounded-xl p-2 bg-gray-50 space-y-0.5">${res.log.map(l=>`<p>${esc(l)}</p>`).join('')}</div>` : ''}`;
            } else {
                document.getElementById('syncBody').innerHTML = `
                    <p class="font-semibold text-green-700">${esc(res.message)}</p>
                    <p class="text-gray-500 mt-1">Countries: <b>${res.countries}</b> &nbsp;|&nbsp; Cities: <b>${res.cities}</b></p>`;
            }
            openModal('syncModal');
        } catch (e) { hideLoader(); toast('error', e.message); }
    }

    async function handleFileUpload(e) {
        const file = e.target.files[0]; if (!file) return; e.target.value = '';
        showLoader('Reading file…');
        try {
            const parsed    = JSON.parse(await file.text());
            const countries = parsed.countries || (Array.isArray(parsed) ? parsed : []);
            const cities    = parsed.cities || [];
            if (!countries.length) { hideLoader(); return toast('error', 'No countries in JSON'); }
            showLoader(`Importing ${countries.length} countries…`);
            const res = await thApi(`${API_BASE}api/masterdata/countries/sync.php`, 'POST', { action:'import', countries, cities });
            hideLoader();
            if (!res.success) throw new Error(res.message);
            document.getElementById('syncTitle').textContent = '✅ File Import Complete';
            document.getElementById('syncBody').innerHTML = `
                <p class="font-semibold text-green-700">${esc(res.message)}</p>
                <p class="text-gray-500 mt-1">Inserted: <b>${res.inserted}</b> &nbsp;|&nbsp; Updated: <b>${res.updated}</b></p>
                ${res.log?.length ? `<div class="mt-3 text-xs text-gray-400 font-mono max-h-40 overflow-y-auto border border-gray-100 rounded-xl p-2 bg-gray-50 space-y-0.5">${res.log.map(l=>`<p>${esc(l)}</p>`).join('')}</div>` : ''}`;
            openModal('syncModal');
        } catch (e) { hideLoader(); toast('error', 'File error: ' + e.message); }
    }

    async function autoExportJSON() {
        try { await thApi(`${API_BASE}api/masterdata/countries/sync.php`, 'POST', { action:'export' }); }
        catch (e) { console.warn('Auto-export failed:', e.message); }
    }

    // ── Confirm dialog ────────────────────────────────────────────────
    function confirmAction({ icon, title, msg, color, label, onOk }) {
        document.getElementById('confirmIcon').textContent  = icon;
        document.getElementById('confirmTitle').textContent = title;
        document.getElementById('confirmMsg').innerHTML     = msg;
        const btn = document.getElementById('confirmOk');
        btn.textContent = label;
        btn.className   = `flex-1 py-2.5 rounded-xl font-semibold text-white transition text-sm ${color}`;
        btn.onclick     = () => { closeModal('confirmModal'); onOk(); };
        openModal('confirmModal');
    }

    function openModal(id)  { document.getElementById(id)?.classList.remove('hidden'); }
    function closeModal(id) { document.getElementById(id)?.classList.add('hidden'); }

    function showLoader(msg = 'Loading…') {
        document.getElementById('cntLoaderMsg').textContent = msg;
        const el = document.getElementById('cntLoader');
        el.classList.remove('hidden'); el.classList.add('flex');
    }
    function hideLoader() {
        const el = document.getElementById('cntLoader');
        el.classList.add('hidden'); el.classList.remove('flex');
    }

    function toast(type, msg) {
        const el = document.getElementById('cntToast');
        el.className = `fixed bottom-6 right-6 z-[9999] flex items-center gap-2 px-4 py-3 rounded-xl shadow-xl text-sm font-medium text-white max-w-xs
            ${type==='success'?'bg-[#50BC81]':type==='error'?'bg-red-500':'bg-blue-500'}`;
        el.innerHTML = `<i class="fa-solid ${type==='success'?'fa-circle-check':type==='error'?'fa-circle-exclamation':'fa-info-circle'}"></i> ${msg}`;
        el.classList.remove('hidden');
        setTimeout(() => el.classList.add('hidden'), 4000);
    }

    function esc(s) {
        return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    return { init, _page, _toggle, _edit, _del, _removeCity, _togglePackage, _clearPkgFilter };
})();