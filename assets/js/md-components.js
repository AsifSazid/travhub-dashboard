/**
 * TravHub — MdComponents  (assets/js/md-components.js)
 * ═══════════════════════════════════════════════════════
 * Production-ready:
 *  - Full component CRUD
 *  - Variant panel: all component-variant-save.php fields covered
 *    (component_sys_id, variant_name, unit_basis, currency_code,
 *     net_cost, markup_type, markup_value, sell_price, attributes)
 *  - net_cost > 0 validation
 *  - Edit variants (sys_id preserved for UPDATE)
 */

const MdComponents = (() => {
    'use strict';

    const CATS       = ['insurance','visa','guide','sim','tip','porterage','meal','ticket','fee','misc'];
    const UNIT_BASES = ['per_pax','per_group','per_day','flat'];

    const st = {
        page: 1, limit: 15, search: '', cat: '', status: 'active',
        timer: null, viewComp: null,
    };

    // ── Init ──────────────────────────────────────────────────────────
    function init() { renderShell(); bindEvents(); load(); }

    // ── Shell ─────────────────────────────────────────────────────────
    function renderShell() {
        document.getElementById('mainContent').innerHTML = `
        <div class="px-6 py-6 max-w-screen-xl mx-auto">

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-[#1A2039]">Components</h1>
                    <p class="text-sm text-gray-400 mt-0.5">Extras: insurance, visa, SIM, tips, fees…</p>
                </div>
                <button id="btnAdd"
                    class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold bg-[#1A2039] hover:bg-[#252d4a] text-white transition shadow-sm">
                    <i class="fa-solid fa-plus"></i> Add Component
                </button>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-5 flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input id="srch" type="text" placeholder="Search component…"
                        class="w-full pl-9 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#50BC81]">
                </div>
                <select id="fltCat"
                    class="text-sm px-3 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#50BC81] bg-white">
                    <option value="">All Categories</option>
                    ${CATS.map(c => `<option value="${c}">${c.charAt(0).toUpperCase()+c.slice(1)}</option>`).join('')}
                </select>
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
                            <th class="px-4 py-3 text-left">Component</th>
                            <th class="px-4 py-3 text-left w-32">Category</th>
                            <th class="px-4 py-3 text-center w-28">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tBody" class="divide-y divide-gray-50"></tbody>
                </table>
                <div id="tEmpty" class="hidden text-center py-16 text-gray-300">
                    <i class="fa-solid fa-puzzle-piece text-5xl mb-3 block opacity-30"></i>
                    <p class="text-sm font-medium">No components found</p>
                </div>
                <div id="tLoading" class="text-center py-16 text-gray-300">
                    <i class="fa-solid fa-spinner fa-spin text-3xl"></i>
                </div>
            </div>
            <div id="pgBox"></div>
        </div>

        <!-- ── Component Modal ───────────────────────────────────────── -->
        <div id="modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
          <div class="w-full max-w-lg bg-white rounded-2xl shadow-2xl">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h2 id="modalTitle" class="text-lg font-bold text-[#1A2039]">Add Component</h2>
                <button onclick="thCloseModal('modal')"
                    class="text-gray-400 hover:text-gray-600 w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="px-6 py-5 space-y-4">
                <input type="hidden" id="fSysId">
                <div>
                    <label class="th-label">Name <span class="text-red-500">*</span></label>
                    <input id="fName" type="text" placeholder="e.g. Travel Insurance" class="th-input">
                </div>
                <div>
                    <label class="th-label">Category</label>
                    <select id="fCat" class="th-input">
                        ${CATS.map(c => `<option value="${c}">${c.charAt(0).toUpperCase()+c.slice(1)}</option>`).join('')}
                    </select>
                </div>
                <div>
                    <label class="th-label">Description</label>
                    <textarea id="fDesc" rows="2" class="th-input resize-none"
                        placeholder="Brief description of this component…"></textarea>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3">
                <button onclick="thCloseModal('modal')"
                    class="px-5 py-2 rounded-xl text-sm font-medium border border-gray-200 text-gray-600 hover:bg-gray-50">
                    Cancel
                </button>
                <button id="btnSave"
                    class="px-6 py-2 rounded-xl text-sm font-semibold bg-[#1A2039] hover:bg-[#252d4a] text-white transition shadow-sm">
                    Save Component
                </button>
            </div>
          </div>
        </div>

        <!-- ── Variants Panel ─────────────────────────────────────────── -->
        <div id="varPanel" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
          <div class="w-full max-w-xl max-h-[90vh] flex flex-col bg-white rounded-2xl shadow-2xl">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
                <div>
                    <h2 id="varPanelTitle" class="text-lg font-bold text-[#1A2039]">Variants</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Priced options for this component</p>
                </div>
                <button onclick="thCloseModal('varPanel')"
                    class="text-gray-400 hover:text-gray-600 w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-6 py-5">

                <!-- Variant list -->
                <div id="varList" class="space-y-2 mb-4"></div>

                <!-- Add/Edit form -->
                <div id="varForm" class="hidden bg-gray-50 rounded-2xl border border-gray-200 p-4 space-y-3">
                    <input type="hidden" id="fVSysId">
                    <h4 id="varFormTitle" class="text-sm font-semibold text-[#1A2039]">Add Variant</h4>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="th-label">Variant Name <span class="text-red-500">*</span></label>
                            <input id="fVName" type="text" placeholder="e.g. Single Entry Visa" class="th-input">
                        </div>
                        <div>
                            <label class="th-label">Unit Basis</label>
                            <select id="fVBasis" class="th-input">
                                ${UNIT_BASES.map(b => `<option value="${b}">${b.replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase())}</option>`).join('')}
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="th-label">Currency <span class="text-red-500">*</span></label>
                            <input id="fVCcy" type="text" maxlength="5" placeholder="BDT" class="th-input uppercase">
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
                            <label class="th-label">Notes / Attributes</label>
                            <input id="fVNotes" type="text" placeholder="e.g. valid 30 days" class="th-input">
                        </div>
                    </div>

                    <div class="flex gap-2 justify-end pt-1">
                        <button onclick="MdComponents._cancelVarForm()"
                            class="px-4 py-1.5 rounded-lg text-xs font-medium border border-gray-200 text-gray-600 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button id="btnVSave"
                            class="px-5 py-1.5 rounded-lg text-xs font-semibold bg-[#1A2039] hover:bg-[#252d4a] text-white transition">
                            Save Variant
                        </button>
                    </div>
                </div>

                <!-- Add button -->
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
        document.getElementById('btnAdd').onclick    = () => openForm();
        document.getElementById('btnSave').onclick   = save;
        document.getElementById('btnVSave').onclick  = saveVariant;
        document.getElementById('btnAddVar').onclick = () => openVarForm();

        document.getElementById('srch').oninput = e => {
            clearTimeout(st.timer);
            st.timer = setTimeout(() => { st.search = e.target.value; st.page = 1; load(); }, 400);
        };
        document.getElementById('fltCat').onchange = e => { st.cat = e.target.value; st.page = 1; load(); };

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

        const p = new URLSearchParams({ page: st.page, limit: st.limit, search: st.search, status: st.status });
        if (st.cat) p.append('category', st.cat);

        const data = await thApi(`${API_BASE}api/masterdata/components/list.php?${p}`);
        document.getElementById('tLoading').classList.add('hidden');

        if (!data.success || !data.data?.length) {
            document.getElementById('tEmpty').classList.remove('hidden');
            return;
        }

        document.getElementById('tBody').innerHTML = data.data.map(c => {
            const isTrashed = c.status === 'deleted';
            return `
            <tr class="hover:bg-gray-50 transition ${isTrashed?'opacity-60':''}">
                <td class="px-4 py-3">
                    <div class="font-semibold text-gray-800">${esc(c.name)}</div>
                    <div class="text-xs text-gray-400">${esc(c.description||c.sys_id)}</div>
                </td>
                <td class="px-4 py-3">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                        ${esc(c.category)}
                    </span>
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center justify-center gap-1">
                        <button onclick="MdComponents._variants('${c.sys_id}','${esc(c.name)}')"
                            title="Variants"
                            class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-purple-50 text-purple-500 transition">
                            <i class="fa-solid fa-layer-group text-xs"></i>
                        </button>
                        ${!isTrashed ? `
                        <button onclick="MdComponents._edit('${c.sys_id}')"
                            class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-blue-50 text-blue-500 transition">
                            <i class="fa-solid fa-pen text-xs"></i>
                        </button>
                        <button onclick="MdComponents._del('${c.sys_id}',false)"
                            class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-red-50 text-red-400 transition">
                            <i class="fa-solid fa-trash text-xs"></i>
                        </button>` : `
                        <button onclick="MdComponents._del('${c.sys_id}',true)"
                            class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-green-50 text-green-500 transition">
                            <i class="fa-solid fa-rotate-left text-xs"></i>
                        </button>`}
                    </div>
                </td>
            </tr>`;
        }).join('');

        thPagination('pgBox', data.pagination, 'MdComponents._page');
    }
    function _page(p) { st.page = p; load(); }

    // ── Component CRUD ────────────────────────────────────────────────
    async function openForm(sys_id = null) {
        ['fSysId','fName','fDesc'].forEach(id => thSetVal(id,''));
        thSetVal('fCat', 'misc');
        document.getElementById('modalTitle').textContent = sys_id ? 'Edit Component' : 'Add Component';

        if (sys_id) {
            thSetVal('fSysId', sys_id);
            const d = await thApi(`${API_BASE}api/masterdata/components/get.php?sys_id=${sys_id}`);
            if (!d.success) return thToast('Failed to load', 'error');
            const c = d.data;
            thSetVal('fName', c.name);
            thSetVal('fCat',  c.category);
            thSetVal('fDesc', c.description || '');
        }
        thOpenModal('modal');
    }
    async function _edit(sys_id) { await openForm(sys_id); }

    async function save() {
        const body = {
            sys_id:      thVal('fSysId') || undefined,
            name:        thVal('fName'),
            category:    thVal('fCat'),
            description: thVal('fDesc'),
        };
        if (!body.name) return thToast('Name required', 'error');

        document.getElementById('btnSave').disabled = true;
        const res = await thApi(`${API_BASE}api/masterdata/components/save.php`, 'POST', body);
        document.getElementById('btnSave').disabled = false;
        if (!res.success) return thToast(res.message || 'Error', 'error');
        thToast(res.message || 'Saved!');
        thCloseModal('modal');
        load();
    }

    async function _del(sys_id, restore) {
        if (!restore && !thConfirm('Delete this component?')) return;
        const res = await thApi(`${API_BASE}api/masterdata/components/delete.php`, 'POST', { sys_id, restore });
        thToast(res.message || (restore ? 'Restored' : 'Deleted'));
        load();
    }

    // ── Variants Panel ────────────────────────────────────────────────
    async function _variants(sys_id, name) {
        st.viewComp = sys_id;
        document.getElementById('varPanelTitle').textContent = name;
        document.getElementById('varForm').classList.add('hidden');
        thOpenModal('varPanel');
        await loadVariants(sys_id);
    }

    async function loadVariants(component_sys_id) {
        const el = document.getElementById('varList');
        el.innerHTML = '<div class="text-center py-6 text-gray-300"><i class="fa-solid fa-spinner fa-spin"></i></div>';

        const d = await thApi(`${API_BASE}api/masterdata/components/variant-list.php?component_sys_id=${component_sys_id}&status=active`);
        const variants = d.data || [];

        if (!variants.length) {
            el.innerHTML = '<p class="text-sm text-gray-400 italic py-2">No variants yet. Add one below.</p>';
            return;
        }

        el.innerHTML = variants.map(v => `
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
                <div class="flex items-start justify-between gap-4 mb-1">
                    <div>
                        <div class="font-semibold text-gray-800 text-sm">${esc(v.variant_name)}</div>
                        <div class="text-xs text-gray-400 font-mono">${v.sys_id}</div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <div class="font-semibold text-sm text-[#1A2039]">
                            ${esc(v.currency_code)} ${Number(v.sell_price||0).toLocaleString()}
                            <span class="text-xs text-gray-400 font-normal">/${esc(v.unit_basis||'per_pax').replace(/_/g,' ')}</span>
                        </div>
                        <div class="text-xs text-gray-400">Net: ${esc(v.currency_code)} ${Number(v.net_cost||0).toLocaleString()}</div>
                    </div>
                </div>
                <div class="flex flex-wrap gap-1.5 mb-2 text-xs">
                    ${v.markup_value > 0 ? `<span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">+${v.markup_value}${v.markup_type==='percent'?'%':' flat'}</span>` : ''}
                    ${v.attributes && Object.keys(JSON.parse(v.attributes||'{}')).length ?
                        `<span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">${esc(Object.values(JSON.parse(v.attributes||'{}')).join(', '))}</span>` : ''}
                </div>
                <div class="flex gap-2">
                    <button onclick="MdComponents._editVariant('${v.sys_id}')"
                        class="flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium bg-blue-50 text-blue-600 hover:bg-blue-100 transition">
                        <i class="fa-solid fa-pen text-xs"></i> Edit
                    </button>
                    <button onclick="MdComponents._delVariant('${v.sys_id}')"
                        class="flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium bg-red-50 text-red-500 hover:bg-red-100 transition">
                        <i class="fa-solid fa-trash text-xs"></i> Delete
                    </button>
                </div>
            </div>`).join('');
    }

    // ── Variant Form ──────────────────────────────────────────────────
    function openVarForm(v = null) {
        ['fVSysId','fVName','fVCcy','fVCost','fVSell','fVNotes'].forEach(id => thSetVal(id,''));
        thSetVal('fVBasis',  'per_pax');
        thSetVal('fVMkType', 'percent');
        thSetVal('fVMkVal',  '0');
        document.getElementById('varFormTitle').textContent = v ? 'Edit Variant' : 'Add Variant';

        if (v) {
            thSetVal('fVSysId',  v.sys_id);
            thSetVal('fVName',   v.variant_name);
            thSetVal('fVBasis',  v.unit_basis    || 'per_pax');
            thSetVal('fVCcy',    v.currency_code || '');
            thSetVal('fVCost',   v.net_cost      || '');
            thSetVal('fVSell',   v.sell_price    || '');
            thSetVal('fVMkType', v.markup_type   || 'percent');
            thSetVal('fVMkVal',  v.markup_value  || 0);
            // attributes → notes field (simple string from first value)
            try {
                const attrs = JSON.parse(v.attributes || '{}');
                thSetVal('fVNotes', Object.values(attrs)[0] || '');
            } catch (e) {}
        }

        document.getElementById('varForm').classList.remove('hidden');
        document.getElementById('varForm').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function _cancelVarForm() {
        document.getElementById('varForm').classList.add('hidden');
        thSetVal('fVSysId', '');
    }

    async function _editVariant(sys_id) {
        const d = await thApi(`${API_BASE}api/masterdata/components/variant-list.php?component_sys_id=${st.viewComp}&status=active`);
        const v = (d.data || []).find(x => x.sys_id === sys_id);
        if (!v) return thToast('Variant not found', 'error');
        openVarForm(v);
    }

    async function saveVariant() {
        const variantName  = thVal('fVName');
        const currencyCode = thVal('fVCcy').toUpperCase();
        const netCost      = parseFloat(thVal('fVCost') || 0);
        const sellPrice    = parseFloat(thVal('fVSell') || 0);
        const notes        = thVal('fVNotes');

        // Validations matching component-variant-save.php
        if (!variantName)   return thToast('Variant name required', 'error');
        if (!currencyCode)  return thToast('Currency code required', 'error');
        if (netCost <= 0)   return thToast('Net cost must be greater than 0', 'error');
        if (!sellPrice)     return thToast('Sell price required', 'error');

        const body = {
            sys_id:           thVal('fVSysId') || undefined,
            component_sys_id: st.viewComp,
            variant_name:     variantName,
            unit_basis:       thVal('fVBasis'),
            currency_code:    currencyCode,
            net_cost:         netCost,
            markup_type:      thVal('fVMkType'),
            markup_value:     parseFloat(thVal('fVMkVal') || 0),
            sell_price:       sellPrice,
            attributes:       notes ? { note: notes } : {},
        };

        document.getElementById('btnVSave').disabled = true;
        document.getElementById('btnVSave').textContent = 'Saving…';
        const res = await thApi(`${API_BASE}api/masterdata/components/variant-save.php`, 'POST', body);
        document.getElementById('btnVSave').disabled = false;
        document.getElementById('btnVSave').textContent = 'Save Variant';

        if (!res.success) return thToast(res.message || 'Error saving variant', 'error');
        thToast(res.action === 'created' ? 'Variant added!' : 'Variant updated!');
        _cancelVarForm();
        await loadVariants(st.viewComp);
    }

    async function _delVariant(sys_id) {
        if (!thConfirm('Delete this variant?')) return;
        const res = await thApi(`${API_BASE}api/masterdata/components/variant-delete.php`, 'POST', { sys_id });
        thToast(res.message || 'Deleted');
        await loadVariants(st.viewComp);
    }

    // ── Escape HTML ───────────────────────────────────────────────────
    function esc(s) {
        return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    return { init, _page, _edit, _del, _variants, _editVariant, _delVariant, _cancelVarForm };
})();