/**
 * TravHub — MdActivities  (assets/js/md-activities.js)
 * ═══════════════════════════════════════════════════════
 * Production-ready:
 *  - Full activity CRUD with all API fields
 *  - Variant panel: all variant-save.php fields covered
 *    (activity_sys_id, country_sys_id, variant_name, transport_mode,
 *     meal_*, ticket_included, guide_included, price_basis,
 *     currency_code, net_cost, markup_type, markup_value, sell_price)
 *  - net_cost > 0 validation
 *  - country_sys_id passed correctly from parent activity
 *  - Edit variants (sys_id preserved for UPDATE)
 */

const MdActivities = (() => {
    'use strict';

    const TYPES        = ['tour','transfer','both'];
    const TRANSPORT_MODES = ['none','sic','sedan','suv','van','minibus','coach','boat'];
    const PRICE_BASES  = ['per_pax','per_group'];

    const st = {
        page: 1, limit: 12, search: '', country: '', type: '', status: 'active',
        timer: null, viewActivity: null, viewActivityCountry: null, viewActivityCurrency: ''
    };

    let countriesCache = [];

    // ── Init ──────────────────────────────────────────────────────────
    function init() { renderShell(); bindEvents(); loadCountries().then(load); }

    async function loadCountries() {
        const d = await thApi(`${API_BASE}api/masterdata/countries/list.php?limit=200&status=active&for_package=1`);
        countriesCache = d.data || [];
        ['fltCountry','fACountry'].forEach(id => {
            const el = document.getElementById(id); if (!el) return;
            const lbl = id === 'fltCountry' ? 'All Countries' : 'Select country';
            // el.innerHTML = `<option value="">${lbl}</option>` +
            //     countriesCache.map(c =>
            //         `<option value="${c.sys_id}" data-name="${esc(c.name)}">${esc(c.name)}</option>`
            //     ).join('');
            el.innerHTML = `<option value="">${lbl}</option>` +
            countriesCache.map(c =>
                `<option value="${c.sys_id}" data-name="${esc(c.name)}" data-currency="${c.currency_code}">${esc(c.name)}</option>`
            ).join('');
        });
    }

    // ── Shell ─────────────────────────────────────────────────────────
    function renderShell() {
        document.getElementById('mainContent').innerHTML = `
        <div class="px-6 py-6 max-w-screen-xl mx-auto">

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-[#1A2039]">Activities</h1>
                    <p class="text-sm text-gray-400 mt-0.5">Tours, transfers and priced variants</p>
                </div>
                <button id="btnAdd"
                    class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold bg-[#1A2039] hover:bg-[#252d4a] text-white transition shadow-sm">
                    <i class="fa-solid fa-plus"></i> Add Activity
                </button>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-5 flex flex-col sm:flex-row gap-3 flex-wrap">
                <div class="relative flex-1 min-w-48">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input id="srch" type="text" placeholder="Search activity…"
                        class="w-full pl-9 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#50BC81]">
                </div>
                <select id="fltCountry"
                    class="text-sm px-3 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#50BC81] bg-white"></select>
                <select id="fltType"
                    class="text-sm px-3 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#50BC81] bg-white">
                    <option value="">All Types</option>
                    ${TYPES.map(t => `<option value="${t}">${t.charAt(0).toUpperCase()+t.slice(1)}</option>`).join('')}
                </select>
                <div class="flex gap-2">
                    ${['active','trash'].map(t => `
                    <button data-tab="${t}" class="tab-btn px-4 py-2 text-sm font-medium rounded-xl border transition
                        ${t === 'active' ? 'bg-[#1A2039] text-white border-[#1A2039]' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'}">
                        ${t === 'active' ? 'Active' : '<i class="fa-solid fa-trash mr-1"></i>Trash'}
                    </button>`).join('')}
                </div>
            </div>

            <!-- Card grid -->
            <div id="actGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5 mb-5"></div>
            <div id="tLoading" class="text-center py-16 text-gray-300">
                <i class="fa-solid fa-spinner fa-spin text-3xl"></i>
            </div>
            <div id="tEmpty" class="hidden text-center py-16 text-gray-300">
                <i class="fa-solid fa-person-hiking text-5xl mb-3 block opacity-30"></i>
                <p class="text-sm font-medium">No activities found</p>
            </div>
            <div id="pgBox"></div>
        </div>

        <!-- ── Activity Modal ─────────────────────────────────────────── -->
        <div id="actModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
          <div class="w-full max-w-2xl max-h-[90vh] flex flex-col bg-white rounded-2xl shadow-2xl">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
                <h2 id="actModalTitle" class="text-lg font-bold text-[#1A2039]">Add Activity</h2>
                <button onclick="thCloseModal('actModal')"
                    class="text-gray-400 hover:text-gray-600 w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-6 py-5 space-y-4">
                <input type="hidden" id="fASysId">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="th-label">Country <span class="text-red-500">*</span></label>
                        <select id="fACountry" class="th-input"></select>
                    </div>
                    <div>
                        <label class="th-label">Type</label>
                        <select id="fAType" class="th-input">
                            ${TYPES.map(t => `<option value="${t}">${t.charAt(0).toUpperCase()+t.slice(1)}</option>`).join('')}
                        </select>
                    </div>
                </div>

                <div>
                    <label class="th-label">Activity Name <span class="text-red-500">*</span></label>
                    <input id="fAName" type="text" placeholder="e.g. Bangkok City & Temples Tour" class="th-input">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="th-label">Category</label>
                        <input id="fACat" type="text" placeholder="Sightseeing, Cultural, Adventure…" class="th-input">
                    </div>
                    <div>
                        <label class="th-label">Duration (hours)</label>
                        <input id="fADur" type="number" step="0.5" min="0" placeholder="8" class="th-input">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="th-label">City / Location</label>
                        <input id="fALoc" type="text" placeholder="Bangkok" class="th-input">
                    </div>
                    <div>
                        <label class="th-label">Popularity (1–5)</label>
                        <input id="fAPop" type="number" min="1" max="5" value="3" class="th-input">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="th-label">Min Pax</label>
                        <input id="fAMinPax" type="number" min="1" placeholder="1" class="th-input">
                    </div>
                    <div>
                        <label class="th-label">Max Pax</label>
                        <input id="fAMaxPax" type="number" min="1" placeholder="50" class="th-input">
                    </div>
                </div>

                <div>
                    <label class="th-label">Short Description</label>
                    <textarea id="fADesc" rows="2" class="th-input resize-none"
                        placeholder="Brief description for catalog listing…"></textarea>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3 flex-shrink-0">
                <button onclick="thCloseModal('actModal')"
                    class="px-5 py-2 rounded-xl text-sm font-medium border border-gray-200 text-gray-600 hover:bg-gray-50">
                    Cancel
                </button>
                <button id="btnASave"
                    class="px-6 py-2 rounded-xl text-sm font-semibold bg-[#1A2039] hover:bg-[#252d4a] text-white transition shadow-sm">
                    Save Activity
                </button>
            </div>
          </div>
        </div>

        <!-- ── Variants Panel ─────────────────────────────────────────── -->
        <div id="varPanel" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
          <div class="w-full max-w-2xl max-h-[90vh] flex flex-col bg-white rounded-2xl shadow-2xl">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
                <div>
                    <h2 id="varPanelTitle" class="text-lg font-bold text-[#1A2039]">Variants</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Priced options of this activity</p>
                </div>
                <button onclick="thCloseModal('varPanel')"
                    class="text-gray-400 hover:text-gray-600 w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-6 py-5">

                <!-- Existing variants list -->
                <div id="varList" class="space-y-2 mb-4"></div>

                <!-- Add / Edit form -->
                <div id="varForm" class="hidden bg-gray-50 rounded-2xl border border-gray-200 p-4 space-y-3">
                    <input type="hidden" id="fVSysId">
                    <h4 id="varFormTitle" class="text-sm font-semibold text-[#1A2039]">Add Variant</h4>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="th-label">Variant Name <span class="text-red-500">*</span></label>
                            <input id="fVName" type="text" placeholder="e.g. SIC with Lunch" class="th-input">
                        </div>
                        <div>
                            <label class="th-label">Price Basis</label>
                            <select id="fVBasis" class="th-input">
                                ${PRICE_BASES.map(p => `<option value="${p}">${p.replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase())}</option>`).join('')}
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="th-label">Currency <span class="text-red-500">*</span></label>
                            <input id="fVCcy" type="text" maxlength="5" placeholder="THB" class="th-input uppercase">
                        </div>
                        <div>
                            <label class="th-label">Net Cost <span class="text-red-500">*</span></label>
                            <input id="fVCost" type="number" step="0.01" placeholder="0.00" class="th-input">
                        </div>
                        <div>
                            <label class="th-label">Sell Price <span class="text-red-500">*</span></label>
                            <input id="fVSell" type="number" step="0.01" placeholder="0.00" class="th-input">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="th-label">Markup</label>
                            <div class="grid grid-cols-2 gap-2">
                                <select id="fVMkType" class="th-input w-24 flex-shrink-0">
                                    <option value="percent">%</option>
                                    <option value="fixed">Fix</option>
                                </select>
                                <input id="fVMkVal" type="number" step="0.01" value="0" placeholder="0" class="th-input">
                            </div>
                        </div>
                        <div>
                            <label class="th-label">Child Price</label>
                            <input id="fVChild" type="number" step="0.01" placeholder="optional" class="th-input">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="th-label">Transport Mode</label>
                            <select id="fVTransport" class="th-input">
                                ${TRANSPORT_MODES.map(m => `<option value="${m}">${m.charAt(0).toUpperCase()+m.slice(1)}</option>`).join('')}
                            </select>
                        </div>
                        <div>
                            <label class="th-label">Capacity</label>
                            <div class="flex gap-2">
                                <input id="fVCapMin" type="number" min="1" placeholder="Min" class="th-input">
                                <input id="fVCapMax" type="number" min="1" placeholder="Max" class="th-input">
                            </div>
                        </div>
                    </div>

                    <!-- Inclusions -->
                    <div>
                        <label class="th-label flex items-center gap-4">
                            Includes
                            <span class="flex gap-3 font-normal">
                                ${[['fVMealB','Breakfast'],['fVMealL','Lunch'],['fVMealD','Dinner'],['fVTicket','Ticket'],['fVGuide','Guide']].map(([id,lbl]) => `
                                <label class="flex items-center gap-1 text-xs font-normal cursor-pointer">
                                    <input type="checkbox" id="${id}" class="w-3.5 h-3.5 rounded accent-[#50BC81]"> ${lbl}
                                </label>`).join('')}
                            </span>
                        </label>
                    </div>

                    <div>
                        <label class="th-label">Guide Language</label>
                        <input id="fVGuideLang" type="text" placeholder="English, Thai…" class="th-input">
                    </div>

                    <div class="flex gap-2 justify-end pt-1">
                        <button onclick="MdActivities._cancelVarForm()"
                            class="px-4 py-1.5 rounded-lg text-xs font-medium border border-gray-200 text-gray-600 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button id="btnVSave"
                            class="px-5 py-1.5 rounded-lg text-xs font-semibold bg-[#1A2039] hover:bg-[#252d4a] text-white transition">
                            Save Variant
                        </button>
                    </div>
                </div>

                <!-- Add new button -->
                <button id="btnAddVar"
                    class="w-full mt-3 py-2 rounded-xl text-sm font-semibold border-2 border-dashed border-[#50BC81] text-[#50BC81] hover:bg-[#50BC81]/5 transition">
                    <i class="fa-solid fa-plus mr-2"></i>Add Variant
                </button>
            </div>
          </div>
        </div>`;

        document.head.insertAdjacentHTML('beforeend', `<style>
            .th-label{display:block;font-size:.75rem;font-weight:600;color:#4b5563;margin-bottom:.375rem}
            .th-input{width:100%;padding:.5rem .75rem;border:1px solid #e5e7eb;border-radius:.75rem;font-size:.875rem;outline:none;background:#fff}
            .th-input:focus{border-color:#50BC81;box-shadow:0 0 0 2px rgba(80,188,129,.25)}
        </style>`);
    }

    // ── Events ────────────────────────────────────────────────────────
    function bindEvents() {
        document.getElementById('btnAdd').onclick         = () => openActForm();
        document.getElementById('btnASave').onclick       = saveActivity;
        document.getElementById('btnVSave').onclick       = saveVariant;
        document.getElementById('btnAddVar').onclick      = () => openVarForm();

        document.getElementById('srch').oninput = e => {
            clearTimeout(st.timer);
            st.timer = setTimeout(() => { st.search = e.target.value; st.page = 1; load(); }, 400);
        };
        document.getElementById('fltCountry').onchange = e => { st.country = e.target.value; st.page = 1; load(); };
        document.getElementById('fltType').onchange    = e => { st.page = 1; load(); };

        document.querySelectorAll('.tab-btn').forEach(b => b.onclick = () => {
            st.status = b.dataset.tab; st.page = 1;
            document.querySelectorAll('.tab-btn').forEach(x => {
                const on = x.dataset.tab === st.status;
                x.className = `tab-btn px-4 py-2 text-sm font-medium rounded-xl border transition
                    ${on ? 'bg-[#1A2039] text-white border-[#1A2039]' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'}`;
            });
            load();
        });
    }

    // ── Load ──────────────────────────────────────────────────────────
    async function load() {
        document.getElementById('tLoading').classList.remove('hidden');
        document.getElementById('tEmpty').classList.add('hidden');
        document.getElementById('actGrid').innerHTML = '';

        const type = document.getElementById('fltType')?.value || '';
        const p = new URLSearchParams({
            page: st.page, limit: st.limit, search: st.search,
            country_sys_id: st.country, status: st.status,
        });
        if (type) p.append('type', type);

        const data = await thApi(`${API_BASE}api/masterdata/activities/list.php?${p}`);
        document.getElementById('tLoading').classList.add('hidden');

        if (!data.success || !data.data?.length) {
            document.getElementById('tEmpty').classList.remove('hidden');
            return;
        }
        document.getElementById('actGrid').innerHTML = data.data.map(actCard).join('');
        thPagination('pgBox', data.pagination, 'MdActivities._page');
    }
    function _page(p) { st.page = p; load(); }

    // ── Activity Card ─────────────────────────────────────────────────
    function actCard(a) {
        const stars   = '★'.repeat(a.popularity || 0) + '☆'.repeat(5 - (a.popularity || 0));
        const typeBg  = { tour:'bg-green-100 text-green-700', transfer:'bg-blue-100 text-blue-700', both:'bg-purple-100 text-purple-700' };
        const isTrashed = a.status === 'deleted';
        return `
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition ${isTrashed?'opacity-60':''}">
            <div class="h-32 bg-gradient-to-br from-[#50BC81]/20 to-[#1A2039]/20 flex items-center justify-center relative">
                ${a.thumb ? `<img src="${a.thumb}" class="w-full h-full object-cover absolute inset-0" onerror="this.style.display='none'">` : ''}
                <i class="fa-solid fa-person-hiking text-3xl text-[#1A2039]/30 absolute"></i>
            </div>
            <div class="p-4">
                <div class="flex items-start justify-between gap-2 mb-1">
                    <div class="font-semibold text-gray-800 text-sm leading-tight line-clamp-2">${esc(a.name)}</div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium flex-shrink-0
                        ${typeBg[a.type] || 'bg-gray-100 text-gray-600'}">
                        ${a.type}
                    </span>
                </div>
                <div class="text-xs text-gray-400 mb-1">
                    ${esc(a.city_name||a.country_name||'')}
                    ${a.duration_hours ? ' · ' + a.duration_hours + 'h' : ''}
                </div>
                <div class="text-xs text-yellow-500 mb-3">${stars}</div>
                <div class="flex items-center gap-2">
                    <button onclick="MdActivities._variants('${a.sys_id}','${esc(a.name)}','${a.country_sys_id}')"
                        class="flex-1 py-1.5 rounded-lg text-xs font-medium bg-[#1A2039]/10 text-[#1A2039] hover:bg-[#1A2039]/20 transition">
                        <i class="fa-solid fa-list mr-1"></i> Variants
                    </button>
                    ${!isTrashed ? `
                    <button onclick="MdActivities._edit('${a.sys_id}')"
                        class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-blue-50 text-blue-500">
                        <i class="fa-solid fa-pen text-xs"></i>
                    </button>
                    <button onclick="MdActivities._del('${a.sys_id}',false)"
                        class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-red-50 text-red-400">
                        <i class="fa-solid fa-trash text-xs"></i>
                    </button>` : `
                    <button onclick="MdActivities._del('${a.sys_id}',true)"
                        class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-green-50 text-green-500">
                        <i class="fa-solid fa-rotate-left text-xs"></i>
                    </button>`}
                </div>
            </div>
        </div>`;
    }

    // ── Activity CRUD ─────────────────────────────────────────────────
    async function openActForm(sys_id = null) {
        ['fASysId','fAName','fACat','fALoc','fADesc','fAMinPax','fAMaxPax'].forEach(id => thSetVal(id,''));
        thSetVal('fAType', 'tour'); thSetVal('fADur', '0'); thSetVal('fAPop', '3'); thSetVal('fACountry', '');
        document.getElementById('actModalTitle').textContent = sys_id ? 'Edit Activity' : 'Add Activity';

        if (sys_id) {
            thSetVal('fASysId', sys_id);
            const d = await thApi(`${API_BASE}api/masterdata/activities/get.php?sys_id=${sys_id}`);
            if (!d.success) return thToast('Failed to load', 'error');
            const a = d.data;
            thSetVal('fACountry', a.country_sys_id);
            thSetVal('fAType',    a.type);
            thSetVal('fAName',    a.name);
            thSetVal('fACat',     a.category       || '');
            thSetVal('fALoc',     a.location       || a.city_name || '');
            thSetVal('fADur',     a.duration_hours || 0);
            thSetVal('fAPop',     a.popularity     || 3);
            thSetVal('fADesc',    a.short_description || '');
            thSetVal('fAMinPax',  a.min_pax || '');
            thSetVal('fAMaxPax',  a.max_pax || '');
        }
        thOpenModal('actModal');
    }
    async function _edit(sys_id) { await openActForm(sys_id); }

    async function saveActivity() {
        const cSel  = document.getElementById('fACountry');
        const cName = cSel.options[cSel.selectedIndex]?.dataset?.name || '';
        const body  = {
            sys_id:            thVal('fASysId') || undefined,
            country_sys_id:    thVal('fACountry'),
            country_name:      cName,
            type:              thVal('fAType'),
            name:              thVal('fAName'),
            category:          thVal('fACat'),
            location:          thVal('fALoc'),
            duration_hours:    parseFloat(thVal('fADur') || 0),
            popularity:        parseInt(thVal('fAPop') || 3),
            short_description: thVal('fADesc'),
            min_pax:           thVal('fAMinPax') || undefined,
            max_pax:           thVal('fAMaxPax') || undefined,
        };
        if (!body.name)           return thToast('Activity name required', 'error');
        if (!body.country_sys_id) return thToast('Country required', 'error');

        document.getElementById('btnASave').disabled = true;
        const res = await thApi(`${API_BASE}api/masterdata/activities/save.php`, 'POST', body);
        document.getElementById('btnASave').disabled = false;
        if (!res.success) return thToast(res.message || 'Error', 'error');
        thToast(res.message || 'Saved!');
        thCloseModal('actModal');
        load();
    }

    async function _del(sys_id, restore) {
        if (!restore && !thConfirm('Delete this activity?')) return;
        const res = await thApi(`${API_BASE}api/masterdata/activities/delete.php`, 'POST', { sys_id, restore });
        thToast(res.message || (restore ? 'Restored' : 'Deleted'));
        load();
    }

    // ── Variants Panel ────────────────────────────────────────────────
    async function _variants(sys_id, name, country_sys_id) {
        st.viewActivity        = sys_id;
        st.viewActivityCountry = country_sys_id;

        // ── এই line যোগ করো:
        const country = countriesCache.find(c => c.sys_id === country_sys_id);
        st.viewActivityCurrency = country?.currency_code || '';
        // ──────────────────────

        document.getElementById('varPanelTitle').textContent = name;
        document.getElementById('varForm').classList.add('hidden');
        thOpenModal('varPanel');
        await loadVariants(sys_id);
    }

    async function loadVariants(activity_sys_id) {
        const el = document.getElementById('varList');
        el.innerHTML = '<div class="text-center py-6 text-gray-300"><i class="fa-solid fa-spinner fa-spin"></i></div>';

        const d = await thApi(`${API_BASE}api/masterdata/activities/variant-list.php?activity_sys_id=${activity_sys_id}&status=active`);
        const variants = d.data || [];

        if (!variants.length) {
            el.innerHTML = '<p class="text-sm text-gray-400 italic py-2">No variants yet. Add one below.</p>';
            return;
        }

        el.innerHTML = variants.map(v => `
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
                <div class="flex items-start justify-between gap-4 mb-2">
                    <div>
                        <div class="font-semibold text-gray-800 text-sm">${esc(v.variant_name)}</div>
                        <div class="text-xs text-gray-400 font-mono">${v.sys_id}</div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <div class="font-semibold text-sm text-[#1A2039]">
                            ${esc(v.currency_code)} ${Number(v.sell_price||0).toLocaleString()}
                            <span class="text-xs text-gray-400 font-normal">/${v.price_basis==='per_pax'?'pax':'group'}</span>
                        </div>
                        <div class="text-xs text-gray-400">Net: ${esc(v.currency_code)} ${Number(v.net_cost||0).toLocaleString()}</div>
                    </div>
                </div>
                <div class="flex flex-wrap gap-1.5 mb-2 text-xs">
                    ${v.transport_mode && v.transport_mode!=='none' ? `<span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">${esc(v.transport_mode)}</span>` : ''}
                    ${v.meal_breakfast ? '<span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">Breakfast</span>' : ''}
                    ${v.meal_lunch     ? '<span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">Lunch</span>' : ''}
                    ${v.meal_dinner    ? '<span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">Dinner</span>' : ''}
                    ${v.ticket_included? '<span class="px-2 py-0.5 rounded-full bg-green-100 text-green-700">Ticket</span>' : ''}
                    ${v.guide_included ? `<span class="px-2 py-0.5 rounded-full bg-purple-100 text-purple-700">Guide${v.guide_language?' ('+esc(v.guide_language)+')':''}</span>` : ''}
                    ${v.markup_value > 0 ? `<span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">+${v.markup_value}${v.markup_type==='percent'?'%':' flat'}</span>` : ''}
                </div>
                <div class="flex gap-2">
                    <button onclick="MdActivities._editVariant('${v.sys_id}')"
                        class="flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium bg-blue-50 text-blue-600 hover:bg-blue-100 transition">
                        <i class="fa-solid fa-pen text-xs"></i> Edit
                    </button>
                    <button onclick="MdActivities._delVariant('${v.sys_id}')"
                        class="flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium bg-red-50 text-red-500 hover:bg-red-100 transition">
                        <i class="fa-solid fa-trash text-xs"></i> Delete
                    </button>
                </div>
            </div>`).join('');
    }

    // ── Variant Form ──────────────────────────────────────────────────
    function openVarForm(v = null) {
        // Clear form
        ['fVSysId','fVName','fVCcy','fVCost','fVSell','fVChild','fVGuideLang','fVCapMin','fVCapMax'].forEach(id => thSetVal(id,''));
        thSetVal('fVBasis', 'per_pax');
        thSetVal('fVMkType', 'percent');
        thSetVal('fVMkVal', '0');
        thSetVal('fVTransport', 'none');
        ['fVMealB','fVMealL','fVMealD','fVTicket','fVGuide'].forEach(id => {
            const el = document.getElementById(id); if (el) el.checked = false;
        });
        document.getElementById('varFormTitle').textContent = v ? 'Edit Variant' : 'Add Variant';

        if (v) {
            thSetVal('fVSysId',     v.sys_id);
            thSetVal('fVName',      v.variant_name);
            thSetVal('fVBasis',     v.price_basis    || 'per_pax');
            thSetVal('fVCcy',       v.currency_code  || '');
            thSetVal('fVCost',      v.net_cost       || '');
            thSetVal('fVSell',      v.sell_price     || '');
            thSetVal('fVChild',     v.child_price    || '');
            thSetVal('fVMkType',    v.markup_type    || 'percent');
            thSetVal('fVMkVal',     v.markup_value   || 0);
            thSetVal('fVTransport', v.transport_mode || 'none');
            thSetVal('fVCapMin',    v.capacity_min   || '');
            thSetVal('fVCapMax',    v.capacity_max   || '');
            thSetVal('fVGuideLang', v.guide_language || '');
            const setCheck = (id, val) => { const el = document.getElementById(id); if (el) el.checked = !!val; };
            setCheck('fVMealB', v.meal_breakfast);
            setCheck('fVMealL', v.meal_lunch);
            setCheck('fVMealD', v.meal_dinner);
            setCheck('fVTicket', v.ticket_included);
            setCheck('fVGuide',  v.guide_included);
        }

        if (!v) {
            // নতুন variant — country থেকে auto-fill
            const cSel = document.getElementById('fACountry');
            const autoCcy = cSel?.options[cSel.selectedIndex]?.dataset?.currency || '';
            thSetVal('fVCcy', st.viewActivityCurrency || '');
        }

        document.getElementById('varForm').classList.remove('hidden');
        document.getElementById('varForm').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function _cancelVarForm() {
        document.getElementById('varForm').classList.add('hidden');
        thSetVal('fVSysId', '');
    }

    async function _editVariant(sys_id) {
        // Load variant data then open form
        const d = await thApi(`${API_BASE}api/masterdata/activities/variant-list.php?activity_sys_id=${st.viewActivity}&status=active`);
        const v = (d.data || []).find(x => x.sys_id === sys_id);
        if (!v) return thToast('Variant not found', 'error');
        openVarForm(v);
    }

    async function saveVariant() {
        const variantName = thVal('fVName');
        const currencyCode = thVal('fVCcy').toUpperCase();
        const netCost  = parseFloat(thVal('fVCost') || 0);
        const sellPrice = parseFloat(thVal('fVSell') || 0);

        // Validations matching variant-save.php requirements
        if (!variantName)   return thToast('Variant name required', 'error');
        if (!currencyCode)  return thToast('Currency code required', 'error');
        if (netCost <= 0)   return thToast('Net cost must be greater than 0', 'error');
        if (!sellPrice)     return thToast('Sell price required', 'error');

        const body = {
            sys_id:           thVal('fVSysId') || undefined,
            activity_sys_id:  st.viewActivity,
            country_sys_id:   st.viewActivityCountry || '',
            variant_name:     variantName,
            price_basis:      thVal('fVBasis'),
            currency_code:    currencyCode,
            net_cost:         netCost,
            markup_type:      thVal('fVMkType'),
            markup_value:     parseFloat(thVal('fVMkVal') || 0),
            sell_price:       sellPrice,
            child_price:      thVal('fVChild') ? parseFloat(thVal('fVChild')) : undefined,
            transport_mode:   thVal('fVTransport') || 'none',
            capacity_min:     thVal('fVCapMin') ? parseInt(thVal('fVCapMin')) : undefined,
            capacity_max:     thVal('fVCapMax') ? parseInt(thVal('fVCapMax')) : undefined,
            guide_language:   thVal('fVGuideLang') || undefined,
            // checkbox fields
            meal_breakfast:   document.getElementById('fVMealB')?.checked ? 1 : 0,
            meal_lunch:       document.getElementById('fVMealL')?.checked ? 1 : 0,
            meal_dinner:      document.getElementById('fVMealD')?.checked ? 1 : 0,
            ticket_included:  document.getElementById('fVTicket')?.checked ? 1 : 0,
            guide_included:   document.getElementById('fVGuide')?.checked  ? 1 : 0,
        };

        document.getElementById('btnVSave').disabled = true;
        document.getElementById('btnVSave').textContent = 'Saving…';
        const res = await thApi(`${API_BASE}api/masterdata/activities/variant-save.php`, 'POST', body);
        document.getElementById('btnVSave').disabled = false;
        document.getElementById('btnVSave').textContent = 'Save Variant';

        if (!res.success) return thToast(res.message || 'Error saving variant', 'error');
        thToast(res.action === 'created' ? 'Variant added!' : 'Variant updated!');
        _cancelVarForm();
        await loadVariants(st.viewActivity);
    }

    async function _delVariant(sys_id) {
        if (!thConfirm('Delete this variant?')) return;
        const res = await thApi(`${API_BASE}api/masterdata/activities/variant-delete.php`, 'POST', { sys_id });
        thToast(res.message || 'Deleted');
        await loadVariants(st.viewActivity);
    }

    // ── Escape HTML ───────────────────────────────────────────────────
    function esc(s) {
        return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    return { init, _page, _edit, _del, _variants, _editVariant, _delVariant, _cancelVarForm };
})();