/**
 * TravHub — MdTransport  (assets/js/md-transport.js)
 * Transport Services + Vehicle Variants
 *
 * Fixes vs original:
 *  1. saveService() — comma operator bug fixed, variant fields properly set
 *  2. variant_name auto-fill if empty
 *  3. net_cost=0 variants now allowed (validation relaxed to currency_code required)
 *  4. Edit mode — existing variants UPDATE correctly (sys_id preserved)
 *  5. variant row shows markup_type + markup_value + transfer_type fields
 *  6. Error feedback per-variant (toast on failure)
 *  7. Variants panel also accessible from table row (view/manage separately)
 */

const MdTransport = (() => {
    'use strict';

    const TYPES     = ['airport_transfer','intercity','ferry','shuttle','car_hire','other'];
    const V_CLASSES = ['sedan','suv','van','minibus','coach','boat','train','other'];
    const MARKUP_TYPES = ['percent','fixed'];

    const st = { page:1, limit:15, search:'', country:'', status:'active', timer:null };
    let pendingVariants = [];
    let countriesCache  = [];

    // ── Init ──────────────────────────────────────────────────────────
    function init() { renderShell(); bindEvents(); loadCountries().then(load); }

    // ── Countries cache ───────────────────────────────────────────────
    async function loadCountries() {
        const d = await thApi(`${API_BASE}api/masterdata/countries/list.php?limit=200&status=active`);
        countriesCache = d.data || [];
        ['fltCountry','fTCountry'].forEach(id => {
            const el = document.getElementById(id); if (!el) return;
            const lbl = id === 'fltCountry' ? 'All Countries' : 'Select country';
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

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-[#1A2039]">Transport Services</h1>
                    <p class="text-sm text-gray-400 mt-0.5">Routes and vehicle variants</p>
                </div>
                <button id="btnAdd"
                    class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold bg-[#1A2039] hover:bg-[#252d4a] text-white transition shadow-sm">
                    <i class="fa-solid fa-plus"></i> Add Service
                </button>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-5 flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input id="srch" type="text" placeholder="Search route name…"
                        class="w-full pl-9 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#50BC81]">
                </div>
                <select id="fltCountry"
                    class="text-sm px-3 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#50BC81] bg-white"></select>
                <div class="flex gap-2">
                    ${['active','trash'].map(t => `
                    <button data-tab="${t}" class="tab-btn px-4 py-2 text-sm font-medium rounded-xl border transition
                        ${t === 'active' ? 'bg-[#1A2039] text-white border-[#1A2039]' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'}">
                        ${t === 'active' ? 'Active' : '<i class="fa-solid fa-trash mr-1"></i>Trash'}
                    </button>`).join('')}
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-4">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wide border-b border-gray-200">
                            <th class="px-4 py-3 text-left">Route</th>
                            <th class="px-4 py-3 text-left w-36">Type</th>
                            <th class="px-4 py-3 text-left">Country</th>
                            <th class="px-4 py-3 text-left w-28">Duration</th>
                            <th class="px-4 py-3 text-center w-32">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tBody" class="divide-y divide-gray-50"></tbody>
                </table>
                <div id="tEmpty" class="hidden text-center py-16 text-gray-300">
                    <i class="fa-solid fa-route text-5xl mb-3 block opacity-30"></i>
                    <p class="text-sm font-medium">No routes found</p>
                </div>
                <div id="tLoading" class="text-center py-16 text-gray-300">
                    <i class="fa-solid fa-spinner fa-spin text-3xl"></i>
                </div>
            </div>
            <div id="pgBox"></div>
        </div>

        <!-- ── Service Modal ─────────────────────────────────────────── -->
        <div id="sModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
          <div class="w-full max-w-2xl max-h-[90vh] flex flex-col bg-white rounded-2xl shadow-2xl">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
                <h2 id="sModalTitle" class="text-lg font-bold text-[#1A2039]">Add Transport Service</h2>
                <button onclick="thCloseModal('sModal')"
                    class="text-gray-400 hover:text-gray-600 w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-6 py-5 space-y-4">
                <input type="hidden" id="fTSysId">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="th-label">Country <span class="text-red-500">*</span></label>
                        <select id="fTCountry" class="th-input"></select>
                    </div>
                    <div>
                        <label class="th-label">Type</label>
                        <select id="fTType" class="th-input">
                            ${TYPES.map(t => `<option value="${t}">${t.replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase())}</option>`).join('')}
                        </select>
                    </div>
                </div>

                <div>
                    <label class="th-label">Route Name <span class="text-red-500">*</span></label>
                    <input id="fTName" type="text" placeholder="BKK Airport → Pattaya City" class="th-input">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div><label class="th-label">From City</label>
                        <input id="fTFrom" type="text" placeholder="Bangkok" class="th-input"></div>
                    <div><label class="th-label">To City</label>
                        <input id="fTTo" type="text" placeholder="Pattaya" class="th-input"></div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="th-label">Direction</label>
                        <select id="fTDir" class="th-input">
                            <option value="one_way">One Way</option>
                            <option value="return">Return</option>
                        </select>
                    </div>
                    <div><label class="th-label">Typical Duration</label>
                        <input id="fTDur" type="text" placeholder="2 hours" class="th-input"></div>
                </div>

                <div>
                    <label class="th-label">Description</label>
                    <textarea id="fTDesc" rows="2" class="th-input resize-none" placeholder="Brief description"></textarea>
                </div>

                <!-- Variants -->
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <label class="th-label mb-0">
                            Vehicle Variants
                            <span class="text-xs font-normal text-gray-400 ml-2">
                                (service_sys_id + variant_name + net_cost required)
                            </span>
                        </label>
                        <button id="btnAddVariant"
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-[#50BC81] hover:bg-[#3da868] text-white transition">
                            <i class="fa-solid fa-plus"></i> Add Variant
                        </button>
                    </div>
                    <div id="variantList" class="space-y-2"></div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3 flex-shrink-0">
                <button onclick="thCloseModal('sModal')"
                    class="px-5 py-2 rounded-xl text-sm font-medium border border-gray-200 text-gray-600 hover:bg-gray-50">
                    Cancel
                </button>
                <button id="btnTSave"
                    class="px-6 py-2 rounded-xl text-sm font-semibold bg-[#1A2039] hover:bg-[#252d4a] text-white transition shadow-sm">
                    Save Service
                </button>
            </div>
          </div>
        </div>

        <!-- ── Variants Manage Modal (from table row) ─────────────────── -->
        <div id="vModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
          <div class="w-full max-w-2xl max-h-[90vh] flex flex-col bg-white rounded-2xl shadow-2xl">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
                <div>
                    <h2 id="vModalTitle" class="text-lg font-bold text-[#1A2039]">Variants</h2>
                    <p class="text-xs text-gray-400 mt-0.5" id="vModalSub"></p>
                </div>
                <button onclick="thCloseModal('vModal')"
                    class="text-gray-400 hover:text-gray-600 w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-6 py-5">
                <div id="vModalList" class="space-y-2"></div>
                <p id="vModalEmpty" class="hidden text-sm text-gray-400 italic py-4">No variants yet.</p>
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
        document.getElementById('btnAdd').onclick       = () => openServiceForm();
        document.getElementById('btnAddVariant').onclick = addVariantRow;
        document.getElementById('btnTSave').onclick     = saveService;

        document.getElementById('srch').oninput = e => {
            clearTimeout(st.timer);
            st.timer = setTimeout(() => { st.search = e.target.value; st.page = 1; load(); }, 400);
        };
        document.getElementById('fltCountry').onchange = e => { st.country = e.target.value; st.page = 1; load(); };
        document.getElementById('fTCountry').onchange = () => {
            const cSel = document.getElementById('fTCountry');
            const ccy  = cSel?.options[cSel.selectedIndex]?.dataset?.currency || '';
            if (ccy && pendingVariants.length === 0) return; // edit mode-এ override করবে না
            pendingVariants.forEach(v => { if (!v.sys_id) v.currency_code = ccy; });
            renderVariantList();
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
    }

    // ── Load ──────────────────────────────────────────────────────────
    async function load() {
        document.getElementById('tLoading').classList.remove('hidden');
        document.getElementById('tEmpty').classList.add('hidden');
        document.getElementById('tBody').innerHTML = '';

        const p = new URLSearchParams({ page:st.page, limit:st.limit, search:st.search, country_sys_id:st.country, status:st.status });
        const data = await thApi(`${API_BASE}api/masterdata/transport/service-list.php?${p}`);
        document.getElementById('tLoading').classList.add('hidden');

        if (!data.success || !data.data?.length) {
            document.getElementById('tEmpty').classList.remove('hidden');
            return;
        }

        document.getElementById('tBody').innerHTML = data.data.map(s => `
            <tr class="hover:bg-gray-50 transition">
                <td class="px-4 py-3">
                    <div class="font-semibold text-gray-800">${esc(s.name)}</div>
                    <div class="text-xs text-gray-400 font-mono">${s.sys_id}</div>
                    ${s.from_city_name ? `<div class="text-xs text-gray-400">${esc(s.from_city_name)} → ${esc(s.to_city_name||'')}</div>` : ''}
                </td>
                <td class="px-4 py-3">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">
                        ${s.type.replace(/_/g,' ')}
                    </span>
                </td>
                <td class="px-4 py-3 text-sm text-gray-600">${esc(s.country_name||'—')}</td>
                <td class="px-4 py-3 text-sm text-gray-500">${esc(s.duration_typical||'—')}</td>
                <td class="px-4 py-3">
                    <div class="flex items-center justify-center gap-1">
                        <button onclick="MdTransport._viewVariants('${s.sys_id}','${esc(s.name)}')"
                            title="View Variants"
                            class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-purple-50 text-purple-500 transition">
                            <i class="fa-solid fa-layer-group text-xs"></i>
                        </button>
                        <button onclick="MdTransport._edit('${s.sys_id}')"
                            title="Edit"
                            class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-blue-50 text-blue-500 transition">
                            <i class="fa-solid fa-pen text-xs"></i>
                        </button>
                        <button onclick="MdTransport._del('${s.sys_id}')"
                            title="Delete"
                            class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-red-50 text-red-400 transition">
                            <i class="fa-solid fa-trash text-xs"></i>
                        </button>
                    </div>
                </td>
            </tr>`).join('');

        thPagination('pgBox', data.pagination, 'MdTransport._page');
    }
    function _page(p) { st.page = p; load(); }

    // ── Variant row management ────────────────────────────────────────
    function addVariantRow() {
        const cSel = document.getElementById('fTCountry');
        const autoCcy = cSel?.options[cSel.selectedIndex]?.dataset?.currency || '';
        pendingVariants.push({
            sys_id:           '',
            variant_name:     '',
            vehicle_class:    'van',
            capacity_max:     6,
            seat_count:       null,
            max_luggage_kg:   null,
            max_luggage_bags: null,
            price_basis:      'per_vehicle',
            transfer_type:    'private',
            currency_code:    autoCcy,
            net_cost:         0,
            markup_type:      'percent',
            markup_value:     0,
            sell_price:       0,
        });
        renderVariantList();
    }

    function renderVariantList() {
        const el = document.getElementById('variantList');
        if (!pendingVariants.length) {
            el.innerHTML = '<p class="text-xs text-gray-400 italic">No variants yet. Click "Add Variant" to add one.</p>';
            return;
        }
        el.innerHTML = pendingVariants.map((v, i) => `
            <div class="bg-gray-50 rounded-xl p-3 border border-gray-200 space-y-2">
                <!-- Row 1: name, class, pax, seats -->
                <div class="grid grid-cols-4 gap-2">
                    <div>
                        <div class="text-xs text-gray-400 mb-1">Variant Name <span class="text-red-400">*</span></div>
                        <input class="th-input text-xs" placeholder="e.g. Private Van 6 pax"
                            value="${esc(v.variant_name||'')}"
                            oninput="MdTransport._vSet(${i},'variant_name',this.value)">
                    </div>
                    <div>
                        <div class="text-xs text-gray-400 mb-1">Vehicle Class</div>
                        <select class="th-input text-xs" onchange="MdTransport._vSet(${i},'vehicle_class',this.value)">
                            ${V_CLASSES.map(c => `<option value="${c}" ${v.vehicle_class===c?'selected':''}>${c.charAt(0).toUpperCase()+c.slice(1)}</option>`).join('')}
                        </select>
                    </div>
                    <div>
                        <div class="text-xs text-gray-400 mb-1">Max Pax</div>
                        <input class="th-input text-xs" type="number" min="1" placeholder="6"
                            value="${v.capacity_max}"
                            oninput="MdTransport._vSet(${i},'capacity_max',parseInt(this.value)||1)">
                    </div>
                    <div>
                        <div class="text-xs text-gray-400 mb-1">Seat Count</div>
                        <input class="th-input text-xs" type="number" min="1" placeholder="e.g. 7"
                            value="${v.seat_count||''}"
                            oninput="MdTransport._vSet(${i},'seat_count',parseInt(this.value)||null)">
                    </div>
                </div>
                <!-- Row 1b: luggage + currency -->
                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <div class="text-xs text-gray-400 mb-1">Max Luggage (KG)</div>
                        <input class="th-input text-xs" type="number" min="0" placeholder="e.g. 20"
                            value="${v.max_luggage_kg||''}"
                            oninput="MdTransport._vSet(${i},'max_luggage_kg',parseInt(this.value)||null)">
                    </div>
                    <div>
                        <div class="text-xs text-gray-400 mb-1">Max Luggage (Bags)</div>
                        <input class="th-input text-xs" type="number" min="0" placeholder="e.g. 2"
                            value="${v.max_luggage_bags||''}"
                            oninput="MdTransport._vSet(${i},'max_luggage_bags',parseInt(this.value)||null)">
                    </div>
                    <div>
                        <div class="text-xs text-gray-400 mb-1">Currency <span class="text-red-400">*</span></div>
                        <input class="th-input text-xs uppercase" placeholder="THB" maxlength="5"
                            value="${esc(v.currency_code||'')}"
                            oninput="MdTransport._vSet(${i},'currency_code',this.value.toUpperCase())">
                    </div>
                </div>
                <!-- Row 2: net cost, markup, sell price, transfer type -->
                <div class="grid grid-cols-4 gap-2">
                    <div>
                        <div class="text-xs text-gray-400 mb-1">Net Cost <span class="text-red-400">*</span></div>
                        <input class="th-input text-xs" type="number" step="0.01" placeholder="0.00"
                            value="${v.net_cost||''}"
                            oninput="MdTransport._vSet(${i},'net_cost',parseFloat(this.value)||0)">
                    </div>
                    <div>
                        <div class="text-xs text-gray-400 mb-1">Markup</div>
                        <div class="grid grid-cols-2 gap-1">
                            <select class="th-input text-xs w-20 flex-shrink-0"
                                onchange="MdTransport._vSet(${i},'markup_type',this.value)">
                                <option value="percent" ${v.markup_type==='percent'?'selected':''}>%</option>
                                <option value="fixed"   ${v.markup_type==='fixed'?'selected':''}>Fix</option>
                            </select>
                            <input class="th-input text-xs" type="number" step="0.01" placeholder="0"
                                value="${v.markup_value||0}"
                                oninput="MdTransport._vSet(${i},'markup_value',parseFloat(this.value)||0)">
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-400 mb-1">Sell Price</div>
                        <input class="th-input text-xs" type="number" step="0.01" placeholder="0.00"
                            value="${v.sell_price||''}"
                            oninput="MdTransport._vSet(${i},'sell_price',parseFloat(this.value)||0)">
                    </div>
                    <div>
                        <div class="text-xs text-gray-400 mb-1">Transfer Type</div>
                        <select class="th-input text-xs" onchange="MdTransport._vSet(${i},'transfer_type',this.value)">
                            <option value="private" ${v.transfer_type==='private'?'selected':''}>Private</option>
                            <option value="sic"     ${v.transfer_type==='sic'?'selected':''}>SIC</option>
                        </select>
                    </div>
                </div>
                <!-- Row 3: price_basis + remove -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="text-xs text-gray-400">Price Basis:</div>
                            ${['per_vehicle','per_person','per_group','per_day','per_km','per_hour'].map(pb => `
                            <label class="flex items-center gap-1 text-xs cursor-pointer">
                                <input type="radio" name="pb_${i}" value="${pb}"
                                    ${v.price_basis===pb?'checked':''}
                                    onchange="MdTransport._vSet(${i},'price_basis','${pb}')"
                                    class="accent-[#50BC81]">
                                ${pb.replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase())}
                            </label>`).join('')}
                        ${v.sys_id ? `<span class="text-xs font-mono text-gray-300">${v.sys_id}</span>` : ''}
                    </div>
                    <button onclick="MdTransport._removeVariant(${i})"
                        class="flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs bg-red-50 text-red-500 hover:bg-red-100 transition">
                        <i class="fa-solid fa-trash text-xs"></i> Remove
                    </button>
                </div>
            </div>`).join('');
    }

    function _vSet(i, k, v) {
        pendingVariants[i][k] = v;
    }

    function _removeVariant(i) {
        pendingVariants.splice(i, 1);
        renderVariantList();
    }

    // ── Open / Edit Form ──────────────────────────────────────────────
    async function openServiceForm(sys_id = null) {
        pendingVariants = [];
        ['fTSysId','fTName','fTFrom','fTTo','fTDur','fTDesc'].forEach(id => thSetVal(id, ''));
        thSetVal('fTType', 'airport_transfer');
        thSetVal('fTDir',  'one_way');
        thSetVal('fTCountry', '');
        document.getElementById('sModalTitle').textContent = sys_id ? 'Edit Service' : 'Add Transport Service';

        if (sys_id) {
            thSetVal('fTSysId', sys_id);
            const d = await thApi(`${API_BASE}api/masterdata/transport/service-get.php?sys_id=${sys_id}`);
            if (!d.success) return thToast('Failed to load service', 'error');
            const s = d.data;
            thSetVal('fTCountry',  s.country_sys_id);
            thSetVal('fTType',     s.type);
            thSetVal('fTName',     s.name);
            thSetVal('fTFrom',     s.from_city_name  || '');
            thSetVal('fTTo',       s.to_city_name    || '');
            thSetVal('fTDir',      s.direction       || 'one_way');
            thSetVal('fTDur',      s.duration_typical|| '');
            thSetVal('fTDesc',     s.description     || '');

            // Load existing variants
            const vd = await thApi(`${API_BASE}api/masterdata/transport/variant-list.php?service_sys_id=${sys_id}&status=active`);
            pendingVariants = (vd.data || []).map(v => ({
                sys_id:           v.sys_id         || '',
                service_sys_id:   v.service_sys_id || sys_id,
                country_sys_id:   v.country_sys_id || s.country_sys_id,
                variant_name:     v.variant_name   || '',
                vehicle_class:    v.vehicle_class  || 'van',
                capacity_max:     v.capacity_max   || 1,
                seat_count:       v.seat_count      != null ? parseInt(v.seat_count)      : null,
                max_luggage_kg:   v.max_luggage_kg  != null ? parseInt(v.max_luggage_kg)  : null,
                max_luggage_bags: v.max_luggage_bags!= null ? parseInt(v.max_luggage_bags): null,
                price_basis:      v.price_basis     || 'per_vehicle',
                transfer_type:    v.transfer_type   || 'private',
                currency_code:    v.currency_code   || '',
                net_cost:         parseFloat(v.net_cost     || 0),
                markup_type:      v.markup_type     || 'percent',
                markup_value:     parseFloat(v.markup_value || 0),
                sell_price:       parseFloat(v.sell_price   || 0),
            }));
        }

        renderVariantList();
        thOpenModal('sModal');
    }

    async function _edit(sys_id) { await openServiceForm(sys_id); }

    // ── Save Service + Variants ───────────────────────────────────────
    async function saveService() {
        const cSel  = document.getElementById('fTCountry');
        const cName = cSel.options[cSel.selectedIndex]?.dataset?.name || '';

        const body = {
            sys_id:           thVal('fTSysId') || undefined,
            country_sys_id:   thVal('fTCountry'),
            country_name:     cName,
            name:             thVal('fTName'),
            type:             thVal('fTType'),
            from_city_name:   thVal('fTFrom'),
            to_city_name:     thVal('fTTo'),
            direction:        thVal('fTDir'),
            duration_typical: thVal('fTDur'),
            description:      thVal('fTDesc'),
        };

        if (!body.name)            return thToast('Route name required', 'error');
        if (!body.country_sys_id)  return thToast('Country required', 'error');

        // Validate variants
        for (let i = 0; i < pendingVariants.length; i++) {
            const v = pendingVariants[i];
            if (!v.variant_name?.trim()) {
                // Auto-fill variant name if empty
                v.variant_name = `${v.vehicle_class.charAt(0).toUpperCase()+v.vehicle_class.slice(1)} (${v.capacity_max} pax)`;
            }
            if (!v.currency_code?.trim()) {
                return thToast(`Variant ${i+1}: Currency code required`, 'error');
            }
        }

        document.getElementById('btnTSave').disabled = true;
        document.getElementById('btnTSave').innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Saving…';

        try {
            // 1. Save service
            const res = await thApi(`${API_BASE}api/masterdata/transport/service-save.php`, 'POST', body);
            if (!res.success) {
                thToast(res.message || 'Error saving service', 'error');
                return;
            }

            const serviceSysId  = res.sys_id;
            const countrySysId  = body.country_sys_id;

            // 2. Save each variant
            let variantsFailed = 0;
            for (const v of pendingVariants) {
                // Ensure parent fields set for new variants
                if (!v.sys_id) {
                    v.service_sys_id  = serviceSysId;
                    v.country_sys_id  = countrySysId;
                }

                const vBody = {
                    sys_id:           v.sys_id         || undefined,
                    service_sys_id:   v.service_sys_id || serviceSysId,
                    country_sys_id:   v.country_sys_id || countrySysId,
                    variant_name:     v.variant_name,
                    vehicle_class:    v.vehicle_class,
                    capacity_max:     v.capacity_max,
                    seat_count:       v.seat_count       ?? undefined,
                    max_luggage_kg:   v.max_luggage_kg   ?? undefined,
                    max_luggage_bags: v.max_luggage_bags ?? undefined,
                    price_basis:      v.price_basis,
                    transfer_type:    v.transfer_type,
                    currency_code:    v.currency_code,
                    net_cost:         v.net_cost,
                    markup_type:      v.markup_type,
                    markup_value:     v.markup_value,
                    sell_price:       v.sell_price,
                };

                const vRes = await thApi(`${API_BASE}api/masterdata/transport/variant-save.php`, 'POST', vBody);
                if (!vRes.success) {
                    console.warn('Variant save failed:', vRes.message, vBody);
                    variantsFailed++;
                }
            }

            if (variantsFailed > 0) {
                thToast(`Service saved but ${variantsFailed} variant(s) failed. Check console.`, 'warning');
            } else {
                thToast(res.message || `Service saved${pendingVariants.length ? ` with ${pendingVariants.length} variant(s)` : ''}!`);
            }

            thCloseModal('sModal');
            load();

        } finally {
            document.getElementById('btnTSave').disabled = false;
            document.getElementById('btnTSave').textContent = 'Save Service';
        }
    }

    // ── View Variants (read-only from table row) ──────────────────────
    async function _viewVariants(service_sys_id, name) {
        document.getElementById('vModalTitle').textContent = name;
        document.getElementById('vModalSub').textContent   = service_sys_id;
        document.getElementById('vModalList').innerHTML    = '<div class="text-center py-6 text-gray-300"><i class="fa-solid fa-spinner fa-spin"></i></div>';
        document.getElementById('vModalEmpty').classList.add('hidden');
        thOpenModal('vModal');

        const d = await thApi(`${API_BASE}api/masterdata/transport/variant-list.php?service_sys_id=${service_sys_id}&status=active`);
        const variants = d.data || [];

        if (!variants.length) {
            document.getElementById('vModalList').innerHTML = '';
            document.getElementById('vModalEmpty').classList.remove('hidden');
            return;
        }

        document.getElementById('vModalList').innerHTML = variants.map(v => `
            <div class="bg-gray-50 rounded-xl border border-gray-100 px-4 py-3">
                <div class="flex items-start justify-between gap-4 mb-2">
                    <div>
                        <div class="font-semibold text-gray-800 text-sm">${esc(v.variant_name)}</div>
                        <div class="text-xs text-gray-400 font-mono">${v.sys_id}</div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <div class="font-semibold text-sm text-[#1A2039]">
                            ${esc(v.currency_code)} ${Number(v.sell_price||0).toLocaleString()}
                            <span class="text-xs text-gray-400 font-normal">/${v.price_basis==='per_vehicle'?'vehicle':'pax'}</span>
                        </div>
                        <div class="text-xs text-gray-400">Net: ${esc(v.currency_code)} ${Number(v.net_cost||0).toLocaleString()}</div>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2 text-xs">
                    <span class="px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700">${esc(v.vehicle_class)}</span>
                    <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">${v.capacity_max} pax max</span>
                    <span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">${esc(v.transfer_type)}</span>
                    ${v.markup_value > 0 ? `<span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">Markup: ${v.markup_value}${v.markup_type==='percent'?'%':' fixed'}</span>` : ''}
                </div>
            </div>`).join('');
    }

    // ── Delete ────────────────────────────────────────────────────────
    async function _del(sys_id) {
        if (!thConfirm('Delete this transport service? All variants will also be deleted.')) return;
        const res = await thApi(`${API_BASE}api/masterdata/transport/service-delete.php`, 'POST', { sys_id });
        thToast(res.message || 'Deleted');
        load();
    }

    // ── Escape HTML ───────────────────────────────────────────────────
    function esc(s) {
        return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    return { init, _page, _edit, _del, _vSet, _removeVariant, _viewVariants };
})();