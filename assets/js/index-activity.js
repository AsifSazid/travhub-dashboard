/**
 * TravHub — Activity Manager
 * assets/js/index-activity.js
 *
 * Follows the exact same pattern as index-country.js:
 *   loadActivities() → inserts rows → renderPagination()
 * No loadFlat / loadGrouped split — that was the bug source.
 */

const ActivityManager = (() => {

    /* ── State ────────────────────────────────────────────────── */
    const state = {
        page        : 1,
        limit       : 50,
        search      : '',
        type        : '',
        priceRange  : '',
        status      : 'active',
        searchTimer : null,
        allCountries: [],
        cityNameMap : {},   // city_sys_id    → city name
        ctryNameMap : {},   // country_sys_id → country name
    };

    const ACTIVITY_TYPES = ['adventure','business','conference','cultural',
                            'education','nightlife','religious','shopping','sports','tourism'];
    const PRICE_RANGES   = ['free','low','medium','high'];

    const TYPE_COLORS = {
        adventure  : 'bg-orange-50 text-orange-700',
        cultural   : 'bg-violet-50 text-violet-700',
        tourism    : 'bg-blue-50 text-blue-700',
        shopping   : 'bg-pink-50 text-pink-700',
        religious  : 'bg-amber-50 text-amber-700',
        sports     : 'bg-green-50 text-green-700',
        nightlife  : 'bg-indigo-50 text-indigo-700',
        education  : 'bg-teal-50 text-teal-700',
        business   : 'bg-gray-100 text-gray-700',
        conference : 'bg-slate-100 text-slate-700',
    };
    const PRICE_COLORS = {
        free  : 'bg-green-50 text-green-700',
        low   : 'bg-blue-50 text-blue-700',
        medium: 'bg-amber-50 text-amber-700',
        high  : 'bg-red-50 text-red-700',
    };

    /* ── Init ─────────────────────────────────────────────────── */
    async function init() {
        renderShell();
        bindGlobalEvents();
        await loadCountriesForModal();
        loadActivities();
    }

    /* ── Shell ────────────────────────────────────────────────── */
    function renderShell() {
        document.getElementById('mainContent').innerHTML = `
        <div class="px-6 py-6 max-w-screen-xl mx-auto">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Activities</h1>
                    <p class="text-sm text-gray-400 mt-0.5">Manage activities by country and city</p>
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
                    <button id="btnAddActivity"
                            class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold bg-blue-600 hover:bg-blue-700 text-white transition shadow-sm">
                        <i class="fa-solid fa-plus"></i> Add Activity
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-5 flex flex-col sm:flex-row gap-3 flex-wrap">
                <div class="relative flex-1">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input id="actSearch" type="text" placeholder="Search activities, cities, countries…"
                           class="w-full pl-9 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
                <select id="actTypeFilter"
                        class="text-sm px-3 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white text-gray-700">
                    <option value="">All Types</option>
                    ${ACTIVITY_TYPES.map(t => `<option value="${t}">${t.charAt(0).toUpperCase()+t.slice(1)}</option>`).join('')}
                </select>
                <select id="actPriceFilter"
                        class="text-sm px-3 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white text-gray-700">
                    <option value="">All Prices</option>
                    ${PRICE_RANGES.map(p => `<option value="${p}">${p.charAt(0).toUpperCase()+p.slice(1)}</option>`).join('')}
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
                            <th class="px-4 py-3 text-left">Activity</th>
                            <th class="px-4 py-3 text-left w-24">Type</th>
                            <th class="px-4 py-3 text-left w-20">Price</th>
                            <th class="px-4 py-3 text-center w-20">Duration</th>
                            <th class="px-4 py-3 text-center w-24">Popularity</th>
                            <th class="px-4 py-3 text-left w-40">City</th>
                            <th class="px-4 py-3 text-left w-32">Country</th>
                            <th class="px-4 py-3 text-center w-28">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="activityTableBody" class="divide-y divide-gray-50"></tbody>
                </table>
                <div id="activityEmpty" class="hidden text-center py-16 text-gray-300">
                    <i class="fa-solid fa-person-hiking text-5xl mb-3 block opacity-30"></i>
                    <p class="text-sm font-medium">No activities found</p>
                    <p class="text-xs mt-1">Try syncing from JSON or adjusting filters</p>
                </div>
                <div id="activityLoading" class="text-center py-16 text-gray-300">
                    <i class="fa-solid fa-spinner fa-spin text-3xl"></i>
                </div>
            </div>

            <!-- Pagination -->
            <div id="activityPagination" class="flex items-center justify-between"></div>

        </div>

        <!-- ── Activity Modal ──────────────────────────────────── -->
        <div id="actModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm px-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <h2 id="actModalTitle" class="text-lg font-bold text-gray-800">Add Activity</h2>
                    <button id="actModalClose"
                            class="text-gray-400 hover:text-gray-600 w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100 transition">
                        <i class="fa-solid fa-times"></i>
                    </button>
                </div>
                <div class="px-6 py-5 space-y-4">
                    <input type="hidden" id="actModalSysId">

                    <!-- Location selectors (new only) -->
                    <div id="actLocationFields">
                        <div class="mb-3">
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Country <span class="text-red-500">*</span></label>
                            <select id="fActCountry"
                                    class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white">
                                <option value="">-- Select country --</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">City <span class="text-red-500">*</span></label>
                            <select id="fActCity"
                                    class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white">
                                <option value="">-- Select country first --</option>
                            </select>
                        </div>
                    </div>

                    <!-- Location read-only (edit only) -->
                    <div id="actLocationReadOnly" class="hidden">
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Location</label>
                        <div id="actLocationDisplay"
                             class="text-sm text-gray-600 bg-gray-50 px-3 py-2 rounded-xl border border-gray-200"></div>
                    </div>

                    <!-- Name -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Activity Name <span class="text-red-500">*</span></label>
                        <input id="fActName" type="text" placeholder="e.g. Boat Safari"
                               class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>

                    <!-- Type + Price -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Type</label>
                            <select id="fActType"
                                    class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white">
                                ${ACTIVITY_TYPES.map(t => `<option value="${t}">${t.charAt(0).toUpperCase()+t.slice(1)}</option>`).join('')}
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Price Range</label>
                            <select id="fActPrice"
                                    class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white">
                                ${PRICE_RANGES.map(p => `<option value="${p}">${p.charAt(0).toUpperCase()+p.slice(1)}</option>`).join('')}
                            </select>
                        </div>
                    </div>

                    <!-- Duration + Popularity -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Duration (hours)</label>
                            <input id="fActDuration" type="number" min="0.5" step="0.5" value="2"
                                   class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Popularity (1–5)</label>
                            <div class="flex items-center gap-1 mt-1">
                                ${[1,2,3,4,5].map(n => `
                                <button type="button" data-pop="${n}"
                                        class="pop-star text-xl transition ${n <= 3 ? 'text-amber-400' : 'text-gray-300'}">
                                    <i class="fa-${n <= 3 ? 'solid' : 'regular'} fa-star"></i>
                                </button>`).join('')}
                                <input type="hidden" id="fActPopularity" value="3">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-between px-6 py-4 border-t border-gray-100 bg-gray-50 rounded-b-2xl">
                    <button id="actModalCancel"
                            class="px-5 py-2 text-sm font-medium text-gray-600 border border-gray-200 rounded-xl hover:bg-gray-100 transition">
                        Cancel
                    </button>
                    <button id="actModalSave"
                            class="flex items-center gap-2 px-6 py-2 text-sm font-semibold bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition shadow-sm">
                        <i class="fa-solid fa-floppy-disk"></i> Save Activity
                    </button>
                </div>
            </div>
        </div>

        <!-- ── Confirm Modal ──────────────────────────────────── -->
        <div id="actConfirmModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm px-4">
            <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-sm">
                <div id="actConfirmIcon" class="text-center text-4xl mb-3"></div>
                <h3 id="actConfirmTitle" class="text-base font-bold text-gray-800 text-center mb-1"></h3>
                <p id="actConfirmMsg" class="text-sm text-gray-500 text-center mb-5"></p>
                <div class="flex gap-3">
                    <button id="actConfirmCancel"
                            class="flex-1 py-2.5 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 font-medium transition text-sm">
                        Cancel
                    </button>
                    <button id="actConfirmOk" class="flex-1 py-2.5 rounded-xl font-semibold text-white transition text-sm"></button>
                </div>
            </div>
        </div>

        <!-- ── Sync Modal ─────────────────────────────────────── -->
        <div id="actSyncModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm px-4">
            <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md">
                <h3 id="actSyncTitle" class="text-base font-bold text-gray-800 mb-4"></h3>
                <div id="actSyncBody" class="text-sm text-gray-600 space-y-1 max-h-64 overflow-y-auto"></div>
                <div class="mt-5 text-right">
                    <button id="actSyncClose"
                            class="px-5 py-2 text-sm font-semibold bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition">
                        Done
                    </button>
                </div>
            </div>
        </div>

        <!-- Toast -->
        <div id="actToast"
             class="hidden fixed bottom-6 right-6 z-50 flex items-center gap-2 px-4 py-3 rounded-xl shadow-xl text-sm font-medium text-white max-w-xs">
        </div>

        <!-- Loader -->
        <div id="actLoader"
             class="hidden fixed inset-0 z-50 flex items-center justify-center bg-white/60 backdrop-blur-sm flex-col gap-3">
            <i class="fa-solid fa-spinner fa-spin text-3xl text-blue-600"></i>
            <p id="actLoaderMsg" class="text-sm text-gray-500">Loading…</p>
        </div>
        `;
    }

    /* ── Global Events ────────────────────────────────────────── */
    function bindGlobalEvents() {
        document.getElementById('actSearch').addEventListener('input', e => {
            clearTimeout(state.searchTimer);
            state.searchTimer = setTimeout(() => {
                state.search = e.target.value.trim();
                state.page   = 1;
                loadActivities();
            }, 380);
        });

        document.getElementById('actTypeFilter').addEventListener('change', e => {
            state.type = e.target.value;
            state.page = 1;
            loadActivities();
        });

        document.getElementById('actPriceFilter').addEventListener('change', e => {
            state.priceRange = e.target.value;
            state.page = 1;
            loadActivities();
        });

        document.querySelectorAll('.status-tab').forEach(btn => {
            btn.addEventListener('click', () => {
                state.status = btn.dataset.status;
                state.page   = 1;
                document.querySelectorAll('.status-tab').forEach(b => {
                    b.classList.remove('bg-blue-600','text-white','border-blue-600');
                    b.classList.add('bg-white','text-gray-600','border-gray-200');
                });
                btn.classList.add('bg-blue-600','text-white','border-blue-600');
                btn.classList.remove('bg-white','text-gray-600','border-gray-200');
                loadActivities();
            });
        });

        document.getElementById('btnAddActivity').addEventListener('click', () => openActivityModal());

        document.getElementById('actModalClose').addEventListener('click',  closeActivityModal);
        document.getElementById('actModalCancel').addEventListener('click', closeActivityModal);
        document.getElementById('actModal').addEventListener('click', e => {
            if (e.target === e.currentTarget) closeActivityModal();
        });
        document.getElementById('actModalSave').addEventListener('click', saveActivity);

        document.getElementById('fActCountry').addEventListener('change', e => {
            populateCityDropdown(e.target.value);
        });

        document.querySelectorAll('.pop-star').forEach(btn => {
            btn.addEventListener('click', () => {
                const v = parseInt(btn.dataset.pop);
                document.getElementById('fActPopularity').value = v;
                document.querySelectorAll('.pop-star').forEach(b => {
                    const n = parseInt(b.dataset.pop);
                    b.querySelector('i').className = `fa-${n <= v ? 'solid' : 'regular'} fa-star`;
                    b.classList.toggle('text-amber-400', n <= v);
                    b.classList.toggle('text-gray-300',  n > v);
                });
            });
        });

        document.getElementById('btnSyncImport').addEventListener('click', () => runSync('import'));
        document.getElementById('btnSyncExport').addEventListener('click', () => runSync('export'));
        document.getElementById('actSyncClose').addEventListener('click', () => {
            document.getElementById('actSyncModal').classList.add('hidden');
            loadActivities();
        });
        document.getElementById('actConfirmCancel').addEventListener('click', () =>
            document.getElementById('actConfirmModal').classList.add('hidden')
        );
    }

    /* ── Load Activities — one function, same as loadCountries() ─ */
    async function loadActivities() {
        const body    = document.getElementById('activityTableBody');
        const empty   = document.getElementById('activityEmpty');
        const loading = document.getElementById('activityLoading');

        body.innerHTML = '';
        empty.classList.add('hidden');
        loading.classList.remove('hidden');

        const params = new URLSearchParams({
            search     : state.search,
            type       : state.type,
            price_range: state.priceRange,
            status     : state.status,
            page       : state.page,
            limit      : state.limit,
        });

        try {
            const res  = await fetch(API_ACTIVITIES_BASE + 'list.php?' + params);
            const json = await res.json();
            loading.classList.add('hidden');

            if (!json.success) throw new Error(json.message);

            if (!json.data.length) {
                empty.classList.remove('hidden');
                document.getElementById('activityPagination').innerHTML = '';
                return;
            }

            json.data.forEach(act => body.insertAdjacentHTML('beforeend', activityRow(act)));
            bindRowEvents();
            renderPagination(json.pagination);

        } catch(e) {
            loading.classList.add('hidden');
            body.innerHTML = `<tr><td colspan="8" class="text-center py-10 text-red-400 text-sm">${e.message}</td></tr>`;
        }
    }

    /* ── Row HTML ─────────────────────────────────────────────── */
    function activityRow(a) {
        const isDeleted = a.status === 'deleted';
        const typeCls   = TYPE_COLORS[a.type]        || 'bg-gray-100 text-gray-600';
        const priceCls  = PRICE_COLORS[a.price_range] || 'bg-gray-100 text-gray-600';
        const stars     = Array.from({length:5}, (_, i) =>
            `<i class="fa-${i < a.popularity ? 'solid' : 'regular'} fa-star text-amber-400" style="font-size:10px"></i>`
        ).join('');

        const cityName    = state.cityNameMap[a.city_sys_id]    || '';
        const countryName = state.ctryNameMap[a.country_sys_id] || '';

        const actions = isDeleted
            ? `<button data-action="restore" data-sys="${a.sys_id}" data-name="${esc(a.name)}"
                       class="text-xs text-green-600 hover:text-green-800 font-medium transition">
                   <i class="fa-solid fa-rotate-left mr-1"></i>Restore
               </button>`
            : `<button data-action="edit-act" data-sys="${a.sys_id}"
                       class="text-xs text-blue-600 hover:text-blue-800 font-medium transition mr-3">
                   <i class="fa-solid fa-pen mr-1"></i>Edit
               </button>
               <button data-action="delete-act" data-sys="${a.sys_id}" data-name="${esc(a.name)}"
                       class="text-xs text-red-500 hover:text-red-700 font-medium transition">
                   <i class="fa-solid fa-trash mr-1"></i>Delete
               </button>`;

        return `
        <tr class="hover:bg-gray-50/60 transition ${isDeleted ? 'opacity-60' : ''}">
            <td class="px-4 py-3">
                <p class="font-semibold text-gray-800 text-sm">${esc(a.name)}</p>
                <p class="text-xs text-gray-400 font-mono mt-0.5">${a.sys_id}</p>
            </td>
            <td class="px-4 py-3">
                <span class="text-xs font-medium px-2 py-1 rounded-lg ${typeCls}">${a.type}</span>
            </td>
            <td class="px-4 py-3">
                <span class="text-xs font-medium px-2 py-1 rounded-lg ${priceCls}">${a.price_range}</span>
            </td>
            <td class="px-4 py-3 text-center text-sm text-gray-600">${a.duration_hours}h</td>
            <td class="px-4 py-3 text-center">${stars}</td>
            <td class="px-4 py-3">
                <p class="text-sm text-gray-700">${esc(cityName)}</p>
                <p class="text-xs text-gray-400 font-mono">${a.city_sys_id}</p>
            </td>
            <td class="px-4 py-3">
                <p class="text-sm text-gray-700">${esc(countryName)}</p>
                <p class="text-xs text-gray-400 font-mono">${a.country_sys_id}</p>
            </td>
            <td class="px-4 py-3 text-center">${actions}</td>
        </tr>`;
    }

    /* ── Row Events ───────────────────────────────────────────── */
    function bindRowEvents() {
        document.querySelectorAll('[data-action="edit-act"]').forEach(btn => {
            btn.addEventListener('click', () => openActivityModal(btn.dataset.sys));
        });
        document.querySelectorAll('[data-action="delete-act"]').forEach(btn => {
            btn.addEventListener('click', () => confirmActAction({
                icon : '🗑️', title: 'Move to Trash',
                msg  : `Delete <b>${btn.dataset.name}</b>?`,
                color: 'bg-red-500', label: 'Delete',
                onOk : () => doActDelete(btn.dataset.sys, false),
            }));
        });
        document.querySelectorAll('[data-action="restore"]').forEach(btn => {
            btn.addEventListener('click', () => confirmActAction({
                icon : '♻️', title: 'Restore Activity',
                msg  : `Restore <b>${btn.dataset.name}</b>?`,
                color: 'bg-green-500', label: 'Restore',
                onOk : () => doActDelete(btn.dataset.sys, true),
            }));
        });
    }

    /* ── Pagination — identical to index-country.js ───────────── */
    function renderPagination(p) {
        const el = document.getElementById('activityPagination');
        if (!p || p.total_pages <= 1) { el.innerHTML = ''; return; }

        let btns = '';
        for (let i = 1; i <= p.total_pages; i++) {
            btns += `<button data-pg="${i}"
                             class="pg-btn w-9 h-9 rounded-xl text-sm font-medium border transition
                                    ${i === p.page ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'}">
                         ${i}
                     </button>`;
        }

        el.innerHTML = `
            <p class="text-sm text-gray-500">
                Showing ${((p.page - 1) * p.limit) + 1}–${Math.min(p.page * p.limit, p.total)} of ${p.total}
            </p>
            <div class="flex gap-1.5">${btns}</div>`;

        el.querySelectorAll('.pg-btn').forEach(b =>
            b.addEventListener('click', () => {
                state.page = parseInt(b.dataset.pg);
                loadActivities();
            })
        );
    }

    /* ── Countries for modal ──────────────────────────────────── */
    async function loadCountriesForModal() {
        try {
            const res  = await fetch(API_COUNTRIES_BASE + 'list.php?limit=200&status=active');
            const json = await res.json();
            if (!json.success) return;

            state.allCountries = json.data || [];

            // Build name lookup maps — used in activityRow()
            state.cityNameMap = {};
            state.ctryNameMap = {};
            state.allCountries.forEach(c => {
                state.ctryNameMap[c.sys_id] = c.name;
                (c.cities || []).forEach(city => {
                    state.cityNameMap[city.id] = city.name;
                });
            });

            // Populate country dropdown
            const sel = document.getElementById('fActCountry');
            state.allCountries.forEach(c => {
                const opt          = document.createElement('option');
                opt.value          = c.sys_id;
                opt.textContent    = `${c.name} (${c.code})`;
                opt.dataset.cities = JSON.stringify(c.cities || []);
                sel.appendChild(opt);
            });

        } catch(e) {
            console.warn('Failed to load countries for modal:', e);
        }
    }

    function populateCityDropdown(countrySysId) {
        const sel = document.getElementById('fActCity');
        sel.innerHTML = '<option value="">-- Select city --</option>';
        if (!countrySysId) return;
        const opt    = document.querySelector(`#fActCountry option[value="${countrySysId}"]`);
        if (!opt) return;
        const cities = JSON.parse(opt.dataset.cities || '[]');
        cities.forEach(city => {
            const o       = document.createElement('option');
            o.value       = city.id;
            o.textContent = city.name;
            sel.appendChild(o);
        });
    }

    /* ── Activity Modal ───────────────────────────────────────── */
    async function openActivityModal(sysId = null) {
        resetActivityModal();
        document.getElementById('actModalTitle').textContent = sysId ? 'Edit Activity' : 'Add Activity';
        document.getElementById('actModalSysId').value       = sysId || '';

        if (sysId) {
            document.getElementById('actLocationFields').classList.add('hidden');
            document.getElementById('actLocationReadOnly').classList.remove('hidden');

            showLoader('Loading…');
            try {
                const res  = await fetch(API_ACTIVITIES_BASE + 'get.php?sys_id=' + encodeURIComponent(sysId));
                const json = await res.json();
                hideLoader();
                if (!json.success) throw new Error(json.message);

                const a = json.data;
                document.getElementById('fActName').value     = a.name;
                document.getElementById('fActType').value     = a.type;
                document.getElementById('fActPrice').value    = a.price_range;
                document.getElementById('fActDuration').value = a.duration_hours;

                const cityName    = state.cityNameMap[a.city_sys_id]    || a.city_sys_id;
                const countryName = state.ctryNameMap[a.country_sys_id] || a.country_sys_id;
                document.getElementById('actLocationDisplay').textContent =
                    `${countryName} → ${cityName}`;

                const pop = a.popularity || 3;
                document.getElementById('fActPopularity').value = pop;
                document.querySelectorAll('.pop-star').forEach(b => {
                    const n = parseInt(b.dataset.pop);
                    b.querySelector('i').className = `fa-${n <= pop ? 'solid' : 'regular'} fa-star`;
                    b.classList.toggle('text-amber-400', n <= pop);
                    b.classList.toggle('text-gray-300',  n > pop);
                });

            } catch(e) {
                hideLoader();
                toast('error', e.message);
                return;
            }
        } else {
            document.getElementById('actLocationFields').classList.remove('hidden');
            document.getElementById('actLocationReadOnly').classList.add('hidden');
        }

        document.getElementById('actModal').classList.remove('hidden');
    }

    function closeActivityModal() {
        document.getElementById('actModal').classList.add('hidden');
        resetActivityModal();
    }

    function resetActivityModal() {
        document.getElementById('actModalSysId').value  = '';
        document.getElementById('fActName').value       = '';
        document.getElementById('fActType').value       = 'tourism';
        document.getElementById('fActPrice').value      = 'medium';
        document.getElementById('fActDuration').value   = '2';
        document.getElementById('fActPopularity').value = '3';
        document.getElementById('fActCountry').value    = '';
        document.getElementById('fActCity').innerHTML   = '<option value="">-- Select country first --</option>';
    }

    /* ── Save Activity ────────────────────────────────────────── */
    async function saveActivity() {
        const sysId    = document.getElementById('actModalSysId').value;
        const name     = document.getElementById('fActName').value.trim();
        const type     = document.getElementById('fActType').value;
        const price    = document.getElementById('fActPrice').value;
        const duration = parseFloat(document.getElementById('fActDuration').value) || 1;
        const pop      = parseInt(document.getElementById('fActPopularity').value) || 3;

        if (!name) { toast('error', 'Activity name is required'); return; }

        const payload = { name, type, price_range: price, duration_hours: duration, popularity: pop };

        if (sysId) {
            payload.sys_id = sysId;
        } else {
            const citySysId    = document.getElementById('fActCity').value;
            const countrySysId = document.getElementById('fActCountry').value;
            if (!citySysId || !countrySysId) {
                toast('error', 'Please select a country and city');
                return;
            }
            payload.city_sys_id    = citySysId;
            payload.country_sys_id = countrySysId;
        }

        showLoader('Saving…');
        try {
            const res  = await fetch(API_ACTIVITIES_BASE + 'save.php', {
                method : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body   : JSON.stringify(payload),
            });
            const json = await res.json();
            hideLoader();
            if (!json.success) throw new Error(json.message);
            toast('success', json.message);
            closeActivityModal();
            await autoExportJSON();
            loadActivities();
        } catch(e) {
            hideLoader();
            toast('error', e.message);
        }
    }

    /* ── Delete / Restore ─────────────────────────────────────── */
    async function doActDelete(sysId, restore) {
        showLoader(restore ? 'Restoring…' : 'Deleting…');
        try {
            const res  = await fetch(API_ACTIVITIES_BASE + 'delete.php', {
                method : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body   : JSON.stringify({ sys_id: sysId, restore }),
            });
            const json = await res.json();
            hideLoader();
            if (!json.success) throw new Error(json.message);
            toast('success', json.message);
            await autoExportJSON();
            loadActivities();
        } catch(e) {
            hideLoader();
            toast('error', e.message);
        }
    }

    /* ── Sync ─────────────────────────────────────────────────── */
    async function runSync(action) {
        showLoader(action === 'import' ? 'Syncing JSON → DB…' : 'Exporting DB → JSON…');
        try {
            const res  = await fetch(API_ACTIVITIES_SYNC, {
                method : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body   : JSON.stringify({ action }),
            });
            const json = await res.json();
            hideLoader();
            if (!json.success) throw new Error(json.message);

            document.getElementById('actSyncTitle').textContent =
                action === 'import' ? '✅ Import Complete' : '✅ Export Complete';

            const body = document.getElementById('actSyncBody');
            if (action === 'import') {
                body.innerHTML = `
                    <p class="font-semibold text-green-700">${json.message}</p>
                    <p class="text-gray-500 mt-1">
                        Inserted: <b>${json.inserted}</b> &nbsp;|&nbsp;
                        Updated: <b>${json.updated}</b> &nbsp;|&nbsp;
                        Skipped: <b>${json.skipped}</b>
                    </p>
                    ${json.log?.length
                        ? `<div class="mt-3 space-y-0.5 text-xs text-gray-400 font-mono max-h-40 overflow-y-auto">
                               ${json.log.slice(0,50).map(l => `<p>${esc(l)}</p>`).join('')}
                               ${json.log.length > 50 ? `<p>…and ${json.log.length - 50} more</p>` : ''}
                           </div>`
                        : ''}`;
            } else {
                body.innerHTML = `
                    <p class="font-semibold text-green-700">${json.message}</p>
                    <p class="text-gray-500 mt-1">Activities exported: <b>${json.activities}</b></p>`;
            }
            document.getElementById('actSyncModal').classList.remove('hidden');

        } catch(e) {
            hideLoader();
            toast('error', e.message);
        }
    }

    /* ── Auto-export JSON after DB change ─────────────────────── */
    async function autoExportJSON() {
        try {
            await fetch(API_ACTIVITIES_SYNC, {
                method : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body   : JSON.stringify({ action: 'export' }),
            });
        } catch(e) {
            console.warn('Auto JSON export failed:', e.message);
        }
    }

    /* ── Confirm Dialog ───────────────────────────────────────── */
    function confirmActAction({ icon, title, msg, color, label, onOk }) {
        document.getElementById('actConfirmIcon').textContent  = icon;
        document.getElementById('actConfirmTitle').textContent = title;
        document.getElementById('actConfirmMsg').innerHTML     = msg;
        const okBtn       = document.getElementById('actConfirmOk');
        okBtn.textContent = label;
        okBtn.className   = `flex-1 py-2.5 rounded-xl font-semibold text-white transition text-sm ${color}`;
        document.getElementById('actConfirmModal').classList.remove('hidden');
        okBtn.onclick = () => {
            document.getElementById('actConfirmModal').classList.add('hidden');
            onOk();
        };
    }

    /* ── Utilities ────────────────────────────────────────────── */
    function esc(s) {
        return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function showLoader(msg = 'Loading…') {
        document.getElementById('actLoaderMsg').textContent = msg;
        const el = document.getElementById('actLoader');
        el.classList.remove('hidden');
        el.classList.add('flex');
    }
    function hideLoader() {
        const el = document.getElementById('actLoader');
        el.classList.add('hidden');
        el.classList.remove('flex');
    }
    function toast(type, msg) {
        const el = document.getElementById('actToast');
        el.className = `fixed bottom-6 right-6 z-50 flex items-center gap-2 px-4 py-3 rounded-xl shadow-xl text-sm font-medium text-white max-w-xs ${type === 'success' ? 'bg-green-600' : 'bg-red-600'}`;
        el.innerHTML = `<i class="fa-solid fa-${type === 'success' ? 'circle-check' : 'circle-exclamation'}"></i> ${msg}`;
        el.classList.remove('hidden');
        setTimeout(() => el.classList.add('hidden'), 4000);
    }

    return { init };
})();