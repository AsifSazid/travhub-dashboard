/**
 * TravHub — Country Manager
 * assets/js/index-country.js
 *
 * Features:
 *  - List countries with accordion cities inline
 *  - Search + region filter + status tabs
 *  - Sync from JSON (import) / Export to JSON
 *  - Add / Edit country modal (with dynamic city builder)
 *  - Soft delete + restore
 *  - Pagination
 */

const CountryManager = (() => {

    /* ── State ────────────────────────────────────────────────── */
    const state = {
        page        : 1,
        limit       : 15,
        search      : '',
        region      : '',
        status      : 'active',
        expandedRows: new Set(),   // sys_ids of expanded accordion rows
        searchTimer : null,
    };

    const REGIONS      = ['Africa','Asia','Europe','Middle East','North America','Oceania','South America'];
    const COST_LEVELS  = ['low','medium','high'];
    const VISA_EASE    = ['easy','medium','hard'];
    const CITY_TYPES   = ['tourism','business','religious','education','conference'];

    /* ── Init ─────────────────────────────────────────────────── */
    function init() {
        renderShell();
        bindGlobalEvents();
        loadCountries();
    }

    /* ── Shell ────────────────────────────────────────────────── */
    function renderShell() {
        document.getElementById('mainContent').innerHTML = `
        <div class="px-6 py-6 max-w-screen-xl mx-auto">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Countries</h1>
                    <p class="text-sm text-gray-400 mt-0.5">Manage countries and their cities</p>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <button id="btnSyncImport"
                            class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold border border-blue-300 text-blue-700 bg-blue-50 hover:bg-blue-100 transition">
                        <i class="fa-solid fa-cloud-arrow-down"></i> Sync from JSON
                    </button>
                    <button id="btnSyncExport"
                            class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold border border-gray-200 text-gray-600 hover:bg-gray-50 transition">
                        <i class="fa-solid fa-cloud-arrow-up"></i> Export to JSON
                    </button>
                    <button id="btnAddCountry"
                            class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold bg-blue-600 hover:bg-blue-700 text-white transition shadow-sm">
                        <i class="fa-solid fa-plus"></i> Add Country
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-5 flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input id="cntSearch" type="text" placeholder="Search country, code, currency…"
                           class="w-full pl-9 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
                <select id="cntRegion"
                        class="text-sm px-3 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white text-gray-700">
                    <option value="">All Regions</option>
                    ${REGIONS.map(r => `<option value="${r}">${r}</option>`).join('')}
                </select>
                <div class="flex gap-2">
                    ${['active','trash'].map(t => `
                    <button data-status="${t}"
                            class="status-tab px-4 py-2 text-sm font-medium rounded-xl border transition
                                   ${t === 'active' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'}">
                        ${t === 'active' ? 'Active' : '<i class="fa-solid fa-trash mr-1"></i>Trash'}
                    </button>`).join('')}
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
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
                            <th class="px-4 py-3 text-center w-28">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="countryTableBody" class="divide-y divide-gray-50"></tbody>
                </table>
                <div id="countryEmpty" class="hidden text-center py-16 text-gray-300">
                    <i class="fa-solid fa-earth-asia text-5xl mb-3 block opacity-30"></i>
                    <p class="text-sm font-medium">No countries found</p>
                    <p class="text-xs mt-1">Try syncing from JSON or adjusting filters</p>
                </div>
                <div id="countryLoading" class="text-center py-16 text-gray-300">
                    <i class="fa-solid fa-spinner fa-spin text-3xl"></i>
                </div>
            </div>

            <!-- Pagination -->
            <div id="countryPagination" class="flex items-center justify-between"></div>
        </div>

        <!-- ── Country Modal ──────────────────────────────────────── -->
        <div id="countryModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-6">
            <div class="w-full max-w-[900px] h-[680px] max-h-[calc(100vh-3rem)] flex flex-col bg-white rounded-2xl shadow-2xl">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
                    <h2 id="modalTitle" class="text-lg font-bold text-gray-800">Add Country</h2>
                    <button id="modalClose" class="text-gray-400 hover:text-gray-600 transition w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100">
                        <i class="fa-solid fa-times"></i>
                    </button>
                </div>
                <div class="px-6 py-5 space-y-5 flex-1 overflow-y-auto overflow-x-hidden">
                    <input type="hidden" id="modalSysId">

                    <!-- Row 1: Name + Code -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Country Name <span class="text-red-500">*</span></label>
                            <input id="fCountryName" type="text" placeholder="e.g. Thailand"
                                   class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">ISO Code <span class="text-red-500">*</span></label>
                            <input id="fCountryCode" type="text" placeholder="TH" maxlength="3"
                                   class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 uppercase">
                        </div>
                    </div>

                    <!-- Row 2: Currency + Code -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Currency Name <span class="text-red-500">*</span></label>
                            <input id="fCurrency" type="text" placeholder="e.g. Baht"
                                   class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Currency Code <span class="text-red-500">*</span></label>
                            <input id="fCurrencyCode" type="text" placeholder="THB" maxlength="5"
                                   class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 uppercase">
                        </div>
                    </div>

                    <!-- Row 3: Rate + Region -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Exchange Rate (1 local = ? BDT)</label>
                            <input id="fRate" type="number" min="0" step="0.0001" placeholder="3.35"
                                   class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Region <span class="text-red-500">*</span></label>
                            <select id="fRegion" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white">
                                <option value="">-- Select region --</option>
                                ${REGIONS.map(r => `<option value="${r}">${r}</option>`).join('')}
                            </select>
                        </div>
                    </div>

                    <!-- Cities -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-semibold text-gray-600">Cities</label>
                            <button id="btnAddCity" type="button"
                                    class="flex items-center gap-1 text-xs font-semibold text-blue-600 hover:text-blue-800 transition">
                                <i class="fa-solid fa-plus-circle"></i> Add City
                            </button>
                        </div>
                        <div id="citiesFormList" class="space-y-2"></div>
                        <p class="text-xs text-gray-400 mt-1">At least one city is recommended.</p>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-between px-6 py-4 border-t border-gray-100 flex-shrink-0">
                    <button id="modalCancel" class="px-5 py-2 text-sm font-medium text-gray-600 border border-gray-200 rounded-xl hover:bg-gray-100 transition">
                        Cancel
                    </button>
                    <button id="modalSave"
                            class="flex items-center gap-2 px-6 py-2 text-sm font-semibold bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition shadow-sm">
                        <i class="fa-solid fa-floppy-disk"></i> Save Country
                    </button>
                </div>
            </div>
        </div>

        <!-- ── Confirm Modal ──────────────────────────────────────── -->
        <div id="confirmModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm px-4">
            <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-sm">
                <div id="confirmIcon" class="text-center text-4xl mb-3"></div>
                <h3 id="confirmTitle" class="text-base font-bold text-gray-800 text-center mb-1"></h3>
                <p id="confirmMsg" class="text-sm text-gray-500 text-center mb-5"></p>
                <div class="flex gap-3">
                    <button id="confirmCancel" class="flex-1 py-2.5 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 font-medium transition text-sm">Cancel</button>
                    <button id="confirmOk" class="flex-1 py-2.5 rounded-xl font-semibold text-white transition text-sm"></button>
                </div>
            </div>
        </div>

        <!-- ── Sync Result Modal ──────────────────────────────────── -->
        <div id="syncModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm px-4">
            <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md">
                <h3 id="syncTitle" class="text-base font-bold text-gray-800 mb-4"></h3>
                <div id="syncBody" class="text-sm text-gray-600 space-y-1 max-h-64 overflow-y-auto"></div>
                <div class="mt-5 text-right">
                    <button id="syncClose" class="px-5 py-2 text-sm font-semibold bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition">Done</button>
                </div>
            </div>
        </div>

        <!-- Toast -->
        <div id="cntToast" class="hidden fixed bottom-6 right-6 z-50 flex items-center gap-2 px-4 py-3 rounded-xl shadow-xl text-sm font-medium text-white max-w-xs"></div>

        <!-- Loader -->
        <div id="cntLoader" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-white/60 backdrop-blur-sm flex-col gap-3">
            <i class="fa-solid fa-spinner fa-spin text-3xl text-blue-600"></i>
            <p id="cntLoaderMsg" class="text-sm text-gray-500">Loading…</p>
        </div>
        `;
    }

    /* ── Global Events ────────────────────────────────────────── */
    function bindGlobalEvents() {
        // Search
        document.getElementById('cntSearch').addEventListener('input', e => {
            clearTimeout(state.searchTimer);
            state.searchTimer = setTimeout(() => {
                state.search = e.target.value.trim();
                state.page   = 1;
                loadCountries();
            }, 380);
        });

        // Region filter
        document.getElementById('cntRegion').addEventListener('change', e => {
            state.region = e.target.value;
            state.page   = 1;
            loadCountries();
        });

        // Status tabs
        document.querySelectorAll('.status-tab').forEach(btn => {
            btn.addEventListener('click', () => {
                state.status = btn.dataset.status;
                state.page   = 1;
                document.querySelectorAll('.status-tab').forEach(b => {
                    b.className = b.className
                        .replace('bg-blue-600 text-white border-blue-600', '')
                        .replace('bg-white text-gray-600 border-gray-200', '');
                });
                btn.classList.add('bg-blue-600','text-white','border-blue-600');
                loadCountries();
            });
        });

        // Add country
        document.getElementById('btnAddCountry').addEventListener('click', () => openModal());

        // Modal close
        document.getElementById('modalClose').addEventListener('click',  closeModal);
        document.getElementById('modalCancel').addEventListener('click', closeModal);
        document.getElementById('countryModal').addEventListener('click', e => {
            if (e.target === e.currentTarget) closeModal();
        });

        // Add city button inside modal
        document.getElementById('btnAddCity').addEventListener('click', () => addCityRow());

        // Modal save
        document.getElementById('modalSave').addEventListener('click', saveCountry);

        // Sync buttons
        document.getElementById('btnSyncImport').addEventListener('click', () => runSync('import'));
        document.getElementById('btnSyncExport').addEventListener('click', () => runSync('export'));

        // Sync modal close
        document.getElementById('syncClose').addEventListener('click', () => {
            document.getElementById('syncModal').classList.add('hidden');
            loadCountries();
        });

        // Confirm modal cancel
        document.getElementById('confirmCancel').addEventListener('click', () =>
            document.getElementById('confirmModal').classList.add('hidden')
        );
    }

    /* ── Load Countries ───────────────────────────────────────── */
    async function loadCountries() {
        const body    = document.getElementById('countryTableBody');
        const empty   = document.getElementById('countryEmpty');
        const loading = document.getElementById('countryLoading');
        body.innerHTML = '';
        empty.classList.add('hidden');
        loading.classList.remove('hidden');

        const params = new URLSearchParams({
            search : state.search,
            region : state.region,
            status : state.status,
            page   : state.page,
            limit  : state.limit,
        });

        try {
            const res  = await fetch(API_COUNTRIES_BASE + 'list.php?' + params);
            const json = await res.json();
            loading.classList.add('hidden');

            if (!json.success) throw new Error(json.message);

            if (!json.data.length) {
                empty.classList.remove('hidden');
                document.getElementById('countryPagination').innerHTML = '';
                return;
            }

            json.data.forEach(country => body.insertAdjacentHTML('beforeend', countryRow(country)));
            bindRowEvents();
            renderPagination(json.pagination);

            // Restore expanded accordions
            state.expandedRows.forEach(sysId => {
                const btn = document.querySelector(`[data-expand="${sysId}"]`);
                if (btn) toggleAccordion(sysId, btn, true);
            });

        } catch(e) {
            loading.classList.add('hidden');
            body.innerHTML = `<tr><td colspan="8" class="text-center py-10 text-red-400 text-sm">${e.message}</td></tr>`;
        }
    }

    /* ── Row HTML ─────────────────────────────────────────────── */
    function countryRow(c) {
        const isDeleted = c.status === 'deleted';
        const regionColors = {
            'Asia'         : 'bg-blue-50 text-blue-700',
            'Europe'       : 'bg-violet-50 text-violet-700',
            'Middle East'  : 'bg-amber-50 text-amber-700',
            'Africa'       : 'bg-orange-50 text-orange-700',
            'North America': 'bg-green-50 text-green-700',
            'South America': 'bg-emerald-50 text-emerald-700',
            'Oceania'      : 'bg-cyan-50 text-cyan-700',
        };
        const regionCls = regionColors[c.region] || 'bg-gray-100 text-gray-600';

        const actions = isDeleted
            ? `<button data-action="restore" data-sys="${c.sys_id}" data-name="${esc(c.name)}"
                       class="text-xs text-green-600 hover:text-green-800 font-medium transition">
                   <i class="fa-solid fa-rotate-left mr-1"></i>Restore
               </button>`
            : `<button data-action="edit" data-sys="${c.sys_id}"
                       class="text-xs text-blue-600 hover:text-blue-800 font-medium transition mr-3">
                   <i class="fa-solid fa-pen mr-1"></i>Edit
               </button>
               <button data-action="delete" data-sys="${c.sys_id}" data-name="${esc(c.name)}"
                       class="text-xs text-red-500 hover:text-red-700 font-medium transition">
                   <i class="fa-solid fa-trash mr-1"></i>Delete
               </button>`;

        return `
        <tr class="hover:bg-gray-50/60 transition ${isDeleted ? 'opacity-60' : ''}">
            <td class="px-4 py-3 text-center">
                <button data-expand="${c.sys_id}"
                        class="w-6 h-6 flex items-center justify-center rounded-lg hover:bg-gray-100 transition text-gray-400 mx-auto expand-btn">
                    <i class="fa-solid fa-chevron-right text-xs transition-transform"></i>
                </button>
            </td>
            <td class="px-4 py-3">
                <div class="font-semibold text-gray-800 text-sm">${esc(c.name)}</div>
                <div class="text-xs text-gray-400 font-mono mt-0.5">${c.sys_id}</div>
            </td>
            <td class="px-4 py-3">
                <span class="inline-block bg-gray-100 text-gray-700 text-xs font-bold px-2 py-0.5 rounded-lg">${esc(c.code)}</span>
            </td>
            <td class="px-4 py-3 text-sm text-gray-600">
                ${esc(c.currency)}
                <span class="text-xs text-gray-400 ml-1">(${esc(c.currency_code)})</span>
            </td>
            <td class="px-4 py-3 text-right text-sm font-semibold text-gray-700">${c.default_rate}</td>
            <td class="px-4 py-3">
                <span class="text-xs font-medium px-2 py-1 rounded-lg ${regionCls}">${esc(c.region)}</span>
            </td>
            <td class="px-4 py-3 text-center">
                <span class="text-sm font-bold text-gray-700">${c.city_count}</span>
            </td>
            <td class="px-4 py-3 text-center">${actions}</td>
        </tr>
        <!-- Accordion row for cities -->
        <tr id="accordion-${c.sys_id}" class="hidden">
            <td colspan="8" class="px-0 py-0">
                <div class="bg-blue-50/40 border-t border-b border-blue-100 px-6 py-4">
                    ${citiesAccordionHTML(c)}
                </div>
            </td>
        </tr>`;
    }

    function citiesAccordionHTML(c) {
        if (!c.cities || !c.cities.length) {
            return `<p class="text-xs text-gray-400 italic">No cities defined for this country.</p>`;
        }

        const typeBadge = types => (types || []).map(t =>
            `<span class="text-xs bg-white border border-gray-200 text-gray-600 px-1.5 py-0.5 rounded-md">${t}</span>`
        ).join('');

        const popularityStars = n => Array.from({length:5},(_,i) =>
            `<i class="fa-${i<n?'solid':'regular'} fa-star text-amber-400" style="font-size:10px"></i>`
        ).join('');

        return `
        <div class="mb-2 flex items-center justify-between">
            <p class="text-xs font-semibold text-blue-700 uppercase tracking-wide">
                <i class="fa-solid fa-city mr-1"></i> Cities in ${esc(c.name)}
            </p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            ${c.cities.map(city => `
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
                <div class="flex items-start justify-between gap-2 mb-1.5">
                    <div>
                        <p class="text-sm font-semibold text-gray-800">${esc(city.name)}</p>
                        <p class="text-xs text-gray-400 font-mono">${city.id}</p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        ${popularityStars(city.popularity || 0)}
                    </div>
                </div>
                <div class="flex flex-wrap gap-1 mb-1.5">${typeBadge(city.type)}</div>
                <div class="flex gap-3 text-xs text-gray-500">
                    <span><i class="fa-solid fa-dollar-sign mr-1 text-gray-400"></i>${city.cost_level || '—'}</span>
                    <span><i class="fa-solid fa-passport mr-1 text-gray-400"></i>${city.visa_ease || '—'}</span>
                </div>
            </div>`).join('')}
        </div>`;
    }

    /* ── Row Events ───────────────────────────────────────────── */
    function bindRowEvents() {
        // Accordion expand
        document.querySelectorAll('.expand-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const sysId = btn.dataset.expand;
                toggleAccordion(sysId, btn);
            });
        });

        // Edit
        document.querySelectorAll('[data-action="edit"]').forEach(btn => {
            btn.addEventListener('click', () => openModal(btn.dataset.sys));
        });

        // Delete
        document.querySelectorAll('[data-action="delete"]').forEach(btn => {
            btn.addEventListener('click', () => confirmAction({
                icon : '🗑️',
                title: 'Move to Trash',
                msg  : `Delete <b>${btn.dataset.name}</b>?`,
                color: 'bg-red-500',
                label: 'Delete',
                onOk : () => doDelete(btn.dataset.sys, false),
            }));
        });

        // Restore
        document.querySelectorAll('[data-action="restore"]').forEach(btn => {
            btn.addEventListener('click', () => confirmAction({
                icon : '♻️',
                title: 'Restore Country',
                msg  : `Restore <b>${btn.dataset.name}</b>?`,
                color: 'bg-green-500',
                label: 'Restore',
                onOk : () => doDelete(btn.dataset.sys, true),
            }));
        });
    }

    function toggleAccordion(sysId, btn, forceOpen = false) {
        const row = document.getElementById(`accordion-${sysId}`);
        const icon = btn.querySelector('i');
        const isOpen = !row.classList.contains('hidden');

        if (forceOpen || !isOpen) {
            row.classList.remove('hidden');
            icon.classList.add('rotate-90');
            state.expandedRows.add(sysId);
        } else {
            row.classList.add('hidden');
            icon.classList.remove('rotate-90');
            state.expandedRows.delete(sysId);
        }
    }

    /* ── Pagination ───────────────────────────────────────────── */
    function renderPagination(p) {
        const el = document.getElementById('countryPagination');
        if (!p || p.total_pages <= 1) { el.innerHTML = ''; return; }
        let btns = '';
        for (let i = 1; i <= p.total_pages; i++) {
            btns += `<button data-pg="${i}"
                             class="pg-btn w-9 h-9 rounded-xl text-sm font-medium border transition
                                    ${i === p.page ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'}">${i}</button>`;
        }
        el.innerHTML = `
            <p class="text-sm text-gray-500">
                Showing ${((p.page-1)*p.limit)+1}–${Math.min(p.page*p.limit, p.total)} of ${p.total}
            </p>
            <div class="flex gap-1.5">${btns}</div>`;
        el.querySelectorAll('.pg-btn').forEach(b =>
            b.addEventListener('click', () => { state.page = parseInt(b.dataset.pg); loadCountries(); })
        );
    }

    /* ── Modal ────────────────────────────────────────────────── */
    async function openModal(sysId = null) {
        resetModal();
        document.getElementById('modalTitle').textContent = sysId ? 'Edit Country' : 'Add Country';
        document.getElementById('modalSysId').value = sysId || '';

        if (sysId) {
            showLoader('Loading…');
            try {
                const res  = await fetch(API_COUNTRIES_BASE + 'get.php?sys_id=' + encodeURIComponent(sysId));
                const json = await res.json();
                hideLoader();
                if (!json.success) throw new Error(json.message);

                const c = json.data;
                document.getElementById('fCountryName').value  = c.name;
                document.getElementById('fCountryCode').value  = c.code;
                document.getElementById('fCurrency').value     = c.currency;
                document.getElementById('fCurrencyCode').value = c.currency_code;
                document.getElementById('fRate').value         = c.default_rate;
                document.getElementById('fRegion').value       = c.region;

                (c.cities || []).forEach(city => addCityRow(city));
            } catch(e) {
                hideLoader();
                toast('error', e.message);
                return;
            }
        } else {
            addCityRow();  // start with one empty city
        }

        document.getElementById('countryModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('countryModal').classList.add('hidden');
        resetModal();
    }

    function resetModal() {
        ['fCountryName','fCountryCode','fCurrency','fCurrencyCode','fRate'].forEach(id => {
            document.getElementById(id).value = '';
        });
        document.getElementById('fRegion').value         = '';
        document.getElementById('modalSysId').value      = '';
        document.getElementById('citiesFormList').innerHTML = '';
    }

    /* ── City Rows inside Modal ───────────────────────────────── */
    function addCityRow(city = null) {
        const list = document.getElementById('citiesFormList');
        const div  = document.createElement('div');
        div.className = 'border border-gray-200 rounded-xl p-3 space-y-3 city-form-row';
        if (city?.id) div.dataset.cityId = city.id;

        div.innerHTML = `
        <div class="flex items-center justify-between">
            <p class="text-xs font-semibold text-gray-600">City</p>
            <button type="button" class="remove-city-btn text-red-400 hover:text-red-600 text-xs transition">
                <i class="fa-solid fa-times"></i> Remove
            </button>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs text-gray-500 mb-1">City Name <span class="text-red-500">*</span></label>
                <input type="text" placeholder="e.g. Bangkok" value="${esc(city?.name || '')}"
                       class="w-full px-3 py-1.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-blue-300 city-name">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Type(s)</label>
                <div class="flex flex-wrap gap-1 city-types-wrap">
                    ${CITY_TYPES.map(t => `
                    <label class="inline-flex items-center gap-1 cursor-pointer">
                        <input type="checkbox" value="${t}" class="city-type-check"
                               ${(city?.type||[]).includes(t) ? 'checked' : ''}>
                        <span class="text-xs text-gray-600">${t}</span>
                    </label>`).join('')}
                </div>
            </div>
        </div>
        <div class="grid grid-cols-3 gap-3">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Popularity (1–5)</label>
                <input type="number" min="1" max="5" value="${city?.popularity || 3}"
                       class="w-full px-3 py-1.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-blue-300 city-popularity">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Cost Level</label>
                <select class="w-full px-3 py-1.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-blue-300 city-cost bg-white">
                    ${COST_LEVELS.map(l => `<option value="${l}" ${city?.cost_level===l?'selected':''}>${l}</option>`).join('')}
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Visa Ease</label>
                <select class="w-full px-3 py-1.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-blue-300 city-visa bg-white">
                    ${VISA_EASE.map(v => `<option value="${v}" ${city?.visa_ease===v?'selected':''}>${v}</option>`).join('')}
                </select>
            </div>
        </div>`;

        div.querySelector('.remove-city-btn').addEventListener('click', () => div.remove());
        list.appendChild(div);
    }

    /* ── Save Country ─────────────────────────────────────────── */
    async function saveCountry() {
        const sysId   = document.getElementById('modalSysId').value;
        const name    = document.getElementById('fCountryName').value.trim();
        const code    = document.getElementById('fCountryCode').value.trim();
        const curr    = document.getElementById('fCurrency').value.trim();
        const currCode= document.getElementById('fCurrencyCode').value.trim();
        const rate    = parseFloat(document.getElementById('fRate').value || 1);
        const region  = document.getElementById('fRegion').value;

        if (!name || !code || !curr || !currCode || !region) {
            toast('error', 'Please fill all required fields');
            return;
        }

        // Collect cities
        const cities = [];
        document.querySelectorAll('.city-form-row').forEach(row => {
            const cityName = row.querySelector('.city-name').value.trim();
            if (!cityName) return;
            const types = [...row.querySelectorAll('.city-type-check:checked')].map(c => c.value);
            const entry = {
                name       : cityName,
                type       : types,
                popularity : parseInt(row.querySelector('.city-popularity').value) || 3,
                cost_level : row.querySelector('.city-cost').value,
                visa_ease  : row.querySelector('.city-visa').value,
            };
            if (row.dataset.cityId) entry.id = row.dataset.cityId;
            cities.push(entry);
        });

        const payload = { name, code, currency: curr, currency_code: currCode,
                          default_rate: rate, region, cities };
        if (sysId) payload.sys_id = sysId;

        showLoader('Saving…');
        try {
            const res  = await fetch(API_COUNTRIES_BASE + 'save.php', {
                method : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body   : JSON.stringify(payload),
            });
            const json = await res.json();
            hideLoader();
            if (!json.success) throw new Error(json.message);
            toast('success', json.message);
            closeModal();
            await autoExportJSON();
            loadCountries();
        } catch(e) {
            hideLoader();
            toast('error', e.message);
        }
    }

    /* ── Delete / Restore ─────────────────────────────────────── */
    async function doDelete(sysId, restore) {
        showLoader(restore ? 'Restoring…' : 'Deleting…');
        try {
            const res  = await fetch(API_COUNTRIES_BASE + 'delete.php', {
                method : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body   : JSON.stringify({ sys_id: sysId, restore }),
            });
            const json = await res.json();
            hideLoader();
            if (!json.success) throw new Error(json.message);
            toast('success', json.message);
            await autoExportJSON();
            loadCountries();
        } catch(e) {
            hideLoader();
            toast('error', e.message);
        }
    }

    /* ── Sync ─────────────────────────────────────────────────── */
    async function runSync(action) {
        const label = action === 'import' ? 'Syncing JSON → DB…' : 'Exporting DB → JSON…';
        showLoader(label);
        try {
            const res  = await fetch(API_COUNTRIES_SYNC, {
                method : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body   : JSON.stringify({ action }),
            });
            const json = await res.json();
            hideLoader();
            if (!json.success) throw new Error(json.message);

            // Show sync result modal
            const title = document.getElementById('syncTitle');
            const body  = document.getElementById('syncBody');
            title.textContent = action === 'import' ? '✅ Import Complete' : '✅ Export Complete';

            if (action === 'import') {
                body.innerHTML = `
                    <p class="font-semibold text-green-700">${json.message}</p>
                    <p class="text-gray-500 mt-1">Inserted: <b>${json.inserted}</b> &nbsp;|&nbsp; Updated: <b>${json.updated}</b></p>
                    ${json.log?.length ? `<div class="mt-3 space-y-0.5 text-xs text-gray-400 font-mono max-h-40 overflow-y-auto">${json.log.map(l=>`<p>${esc(l)}</p>`).join('')}</div>` : ''}`;
            } else {
                body.innerHTML = `
                    <p class="font-semibold text-green-700">${json.message}</p>
                    <p class="text-gray-500 mt-1">Countries: <b>${json.countries}</b> &nbsp;|&nbsp; Cities: <b>${json.cities}</b></p>`;
            }
            document.getElementById('syncModal').classList.remove('hidden');

        } catch(e) {
            hideLoader();
            toast('error', e.message);
        }
    }

    /* ── Confirm Dialog ───────────────────────────────────────── */
    function confirmAction({ icon, title, msg, color, label, onOk }) {
        document.getElementById('confirmIcon').textContent  = icon;
        document.getElementById('confirmTitle').textContent = title;
        document.getElementById('confirmMsg').innerHTML     = msg;
        const okBtn = document.getElementById('confirmOk');
        okBtn.textContent = label;
        okBtn.className   = `flex-1 py-2.5 rounded-xl font-semibold text-white transition text-sm ${color}`;
        document.getElementById('confirmModal').classList.remove('hidden');
        okBtn.onclick = () => {
            document.getElementById('confirmModal').classList.add('hidden');
            onOk();
        };
    }

    /* ── Utilities ────────────────────────────────────────────── */
    function esc(s) {
        return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function showLoader(msg='Loading…') {
        document.getElementById('cntLoaderMsg').textContent = msg;
        document.getElementById('cntLoader').classList.remove('hidden');
        document.getElementById('cntLoader').classList.add('flex');
    }
    function hideLoader() {
        document.getElementById('cntLoader').classList.add('hidden');
        document.getElementById('cntLoader').classList.remove('flex');
    }
    function toast(type, msg) {
        const el = document.getElementById('cntToast');
        el.className = `fixed bottom-6 right-6 z-50 flex items-center gap-2 px-4 py-3 rounded-xl shadow-xl text-sm font-medium text-white max-w-xs ${type==='success'?'bg-green-600':'bg-red-600'}`;
        el.innerHTML = `<i class="fa-solid fa-${type==='success'?'circle-check':'circle-exclamation'}"></i> ${msg}`;
        el.classList.remove('hidden');
        setTimeout(() => el.classList.add('hidden'), 4000);
    }


    /* ── Auto-export to JSON after any DB change ──────────────── */
    async function autoExportJSON() {
        try {
            await fetch(API_COUNTRIES_SYNC, {
                method : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body   : JSON.stringify({ action: 'export' }),
            });
        } catch(e) {
            console.warn('Auto JSON export failed:', e.message);
        }
    }

    return { init };
})();