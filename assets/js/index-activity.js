/**
 * TravHub — Activity Master Data Manager  (v2)
 * assets/js/index-activity.js
 *
 * Modal tabs:
 *   1. Basic      — name, type, country, location, times, duration, popularity
 *   2. Cities     — pickup_from_city + dropoff_city
 *   3. Itinerary  — itineraries array
 *   4. Inc / Exc  — inclusions + exclusions
 *   5. Transfers  — transfers array with per-transfer pricing tiers
 */

const ActivityManager = (() => {

    let countries     = [];
    let carsCache     = {};
    let currentPage   = 1;
    let filterCountry = '';
    let filterType    = '';
    let filterStatus  = 'active';
    let searchQuery   = '';
    let searchTimer   = null;
    let activeTab     = 'basic';
    let editingSysId  = null;
    let formState     = newFormState();

    function newFormState() {
        return {
            sys_id: null, country_sys_id: '',
            name: '', type: 'tour', location: '',
            start_time: '', end_time: '',
            duration_hours: 0, popularity: 3,
            pickup_from_city: [], dropoff_city: [],
            itineraries: [], inclusions: [], exclusions: [], transfers: [],
        };
    }

    const ACTIVITY_TYPES = ['tour','transfer','both'];

    // ── Init ────────────────────────────────────────────────────
    async function init() {
        renderShell();
        await loadCountries();
        bindEvents();
        loadActivities();
    }

    // ── Shell ───────────────────────────────────────────────────
    function renderShell() {
        document.getElementById('mainContent').innerHTML = `
        <div class="px-6 py-6 max-w-screen-xl mx-auto">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Activities</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Masterdata — tours, transfers and combined experiences</p>
                </div>
                <div class="flex gap-2">
                    <button id="btnSync" class="inline-flex items-center gap-2 border border-gray-200 hover:bg-gray-50 text-gray-600 font-medium px-4 py-2.5 rounded-xl transition text-sm">
                        <i class="fa-solid fa-arrows-rotate"></i> Sync JSON
                    </button>
                    <button id="btnAddActivity" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2.5 rounded-xl shadow transition">
                        <i class="fa-solid fa-plus"></i> Add Activity
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-5 flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input id="actSearch" type="text" placeholder="Search name or location…"
                        class="w-full pl-9 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
                <select id="filterCountry" class="text-sm border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <option value="">All Countries</option>
                </select>
                <select id="filterType" class="text-sm border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <option value="">All Types</option>
                    <option value="tour">Tour</option>
                    <option value="transfer">Transfer</option>
                    <option value="both">Both</option>
                </select>
                <select id="filterStatus" class="text-sm border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <option value="active">Active</option>
                    <option value="all">All</option>
                    <option value="trash">Trash</option>
                </select>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100 text-xs text-gray-500 uppercase tracking-wide">
                        <tr>
                            <th class="px-4 py-3 text-left">Name</th>
                            <th class="px-4 py-3 text-left">Country</th>
                            <th class="px-4 py-3 text-left">Type</th>
                            <th class="px-4 py-3 text-left">Location</th>
                            <th class="px-4 py-3 text-center">Dur.</th>
                            <th class="px-4 py-3 text-center">Transfers</th>
                            <th class="px-4 py-3 text-center">Pop.</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="actTableBody" class="divide-y divide-gray-50">
                        <tr><td colspan="9" class="text-center py-12 text-gray-400">
                            <i class="fa-solid fa-spinner fa-spin text-xl mr-2"></i>Loading…</td></tr>
                    </tbody>
                </table>
            </div>
            <div id="actPagination" class="flex items-center justify-between mt-5"></div>
        </div>

        <!-- Activity Modal -->
        <div id="actModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm p-6">
            <div class="w-full max-w-[900px] h-[680px] max-h-[calc(100vh-3rem)] flex flex-col bg-white rounded-2xl shadow-2xl">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
                    <h2 id="actModalTitle" class="text-lg font-bold text-gray-800">Add Activity</h2>
                    <button id="actModalClose" class="text-gray-400 hover:text-gray-600 text-xl w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="flex border-b border-gray-100 px-6 flex-shrink-0 overflow-x-auto gap-1">
                    <button data-tab="basic"     class="tab-btn px-4 py-3 text-sm font-medium border-b-2 border-blue-600 text-blue-600 flex-shrink-0 transition">Basic Info</button>
                    <button data-tab="cities"    class="tab-btn px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 flex-shrink-0 transition">Cities</button>
                    <button data-tab="itinerary" class="tab-btn px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 flex-shrink-0 transition">Itinerary</button>
                    <button data-tab="inc-exc"   class="tab-btn px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 flex-shrink-0 transition">Inc / Exc</button>
                    <button data-tab="transfers" class="tab-btn px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 flex-shrink-0 transition">Transfers</button>
                </div>
                <div class="flex-1 overflow-y-auto overflow-x-hidden px-6 py-5">
                    <div id="tab-basic">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-2"><label class="block text-xs font-semibold text-gray-600 mb-1">Activity Name <span class="text-red-500">*</span></label>
                                <input id="fName" type="text" placeholder="e.g. Bangkok City Tour" class="w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white"></div>
                            <div><label class="block text-xs font-semibold text-gray-600 mb-1">Country <span class="text-red-500">*</span></label>
                                <select id="fCountry" class="w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white"></select></div>
                            <div><label class="block text-xs font-semibold text-gray-600 mb-1">Type</label>
                                <select id="fType" class="w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white">
                                    <option value="tour">Tour</option>
                                    <option value="transfer">Transfer</option>
                                    <option value="both">Both</option>
                                </select></div>
                            <div class="col-span-2"><label class="block text-xs font-semibold text-gray-600 mb-1">Location / Venue</label>
                                <input id="fLocation" type="text" placeholder="e.g. Suvarnabhumi Airport" class="w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white"></div>
                            <div><label class="block text-xs font-semibold text-gray-600 mb-1">Start Time</label>
                                <input id="fStartTime" type="time" class="w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white"></div>
                            <div><label class="block text-xs font-semibold text-gray-600 mb-1">End Time</label>
                                <input id="fEndTime" type="time" class="w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white"></div>
                            <div><label class="block text-xs font-semibold text-gray-600 mb-1">Duration (hours)</label>
                                <input id="fDuration" type="number" min="0" step="0.5" value="0" class="w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white"></div>
                            <div><label class="block text-xs font-semibold text-gray-600 mb-1">Popularity (1–5)</label>
                                <input id="fPopularity" type="number" min="1" max="5" value="3" class="w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white"></div>
                        </div>
                    </div>
                    <div id="tab-cities" class="hidden space-y-6">
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-sm font-semibold text-gray-700">Pickup From Cities</h3>
                                <button id="btnAddPickup" class="text-xs px-3 py-1.5 rounded-lg border border-blue-400 text-blue-600 hover:bg-blue-50 transition"><i class="fa-solid fa-plus mr-1"></i>Add City</button>
                            </div>
                            <div id="pickupList" class="space-y-2"></div>
                        </div>
                        <hr class="border-gray-100">
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-sm font-semibold text-gray-700">Dropoff Cities</h3>
                                <button id="btnAddDropoff" class="text-xs px-3 py-1.5 rounded-lg border border-violet-400 text-violet-600 hover:bg-violet-50 transition"><i class="fa-solid fa-plus mr-1"></i>Add City</button>
                            </div>
                            <div id="dropoffList" class="space-y-2"></div>
                        </div>
                    </div>
                    <div id="tab-itinerary" class="hidden">
                        <div class="flex items-center justify-between mb-3">
                            <div><h3 class="text-sm font-semibold text-gray-700">Itinerary</h3><p class="text-xs text-gray-400">Day-by-day plan</p></div>
                            <button onclick="ActivityManager._addListItem('itineraries')" class="text-xs px-3 py-1.5 rounded-lg border border-blue-400 text-blue-600 hover:bg-blue-50 transition"><i class="fa-solid fa-plus mr-1"></i>Add</button>
                        </div>
                        <div id="itinerariesList" class="space-y-2"></div>
                        <div class="flex items-center justify-between mt-3">
                            <div></div>
                            <button onclick="ActivityManager._addListItem('itineraries')" class="text-xs px-3 py-1.5 rounded-lg border border-blue-400 text-blue-600 hover:bg-blue-50 transition"><i class="fa-solid fa-plus mr-1"></i>Add</button>
                        </div>
                    </div>
                    <div id="tab-inc-exc" class="hidden">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-sm font-semibold text-green-700">Inclusions</h3>
                                <button onclick="ActivityManager._addListItem('inclusions')" class="text-xs px-3 py-1.5 rounded-lg border border-green-400 text-green-600 hover:bg-green-50 transition"><i class="fa-solid fa-plus mr-1"></i>Add</button>
                            </div>
                            <div id="inclusionsList" class="space-y-2"></div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-sm font-semibold text-red-600">Exclusions</h3>
                                <button onclick="ActivityManager._addListItem('exclusions')" class="text-xs px-3 py-1.5 rounded-lg border border-red-400 text-red-500 hover:bg-red-50 transition"><i class="fa-solid fa-plus mr-1"></i>Add</button>
                            </div>
                            <div id="exclusionsList" class="space-y-2"></div>
                        </div>
                    </div>
                    </div>
                    <div id="tab-transfers" class="hidden">
                        <div class="flex items-center justify-between mb-3">
                            <div><h3 class="text-sm font-semibold text-gray-700">Transfers</h3><p class="text-xs text-gray-400">Optional — leave empty if no transfer</p></div>
                            <button id="btnAddTransfer" class="text-xs px-3 py-1.5 rounded-lg border border-orange-400 text-orange-600 hover:bg-orange-50 transition"><i class="fa-solid fa-plus mr-1"></i>Add Transfer</button>
                        </div>
                        <div id="transfersList" class="space-y-3"></div>
                        <div class="flex items-center justify-between mb-3">
                            <div></div>
                            <button id="btnAddTransfer" class="text-xs px-3 py-1.5 rounded-lg border border-orange-400 text-orange-600 hover:bg-orange-50 transition"><i class="fa-solid fa-plus mr-1"></i>Add Transfer</button>
                        </div>
                    </div>
                </div>
                <div id="actError" class="hidden mx-6 mb-2 text-sm text-red-600 bg-red-50 rounded-xl px-4 py-3 flex-shrink-0"></div>
                <div class="flex gap-3 px-6 py-4 border-t border-gray-100 flex-shrink-0">
                    <button id="actModalCancel" class="flex-1 py-2.5 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 font-medium transition">Cancel</button>
                    <button id="actModalSave" class="flex-1 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold transition">
                        <i class="fa-solid fa-floppy-disk mr-1"></i> Save Activity
                    </button>
                </div>
            </div>
        </div>
        <div id="actToast" class="fixed bottom-6 right-6 z-50 hidden items-center gap-3 px-4 py-3 rounded-xl shadow-xl text-sm font-medium text-white max-w-xs"></div>
        `;
    }

    // ── Load Countries ───────────────────────────────────────────
    async function loadCountries() {
        try {
            const res  = await fetch(`${API_COUNTRIES_BASE}list.php?limit=200&status=active`);
            const json = await res.json();
            if (json.success) {
                countries = json.data || [];
                const opts = countries.map(c => `<option value="${c.sys_id}">${esc(c.name)}</option>`).join('');
                document.getElementById('filterCountry').innerHTML = '<option value="">All Countries</option>' + opts;
            }
        } catch(e) {}
    }

    async function loadCarsForCountry(country_sys_id) {
        if (!country_sys_id) return [];
        if (carsCache[country_sys_id]) return carsCache[country_sys_id];
        try {
            const res  = await fetch(`${API_CARS_BASE}list.php?country_sys_id=${country_sys_id}&status=active&limit=100`);
            const json = await res.json();
            carsCache[country_sys_id] = json.success ? (json.data || []) : [];
        } catch(e) { carsCache[country_sys_id] = []; }
        return carsCache[country_sys_id];
    }

    // ── Events ───────────────────────────────────────────────────
    function bindEvents() {
        document.getElementById('btnAddActivity').addEventListener('click', () => openModal());
        document.getElementById('actSearch').addEventListener('input', e => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => { searchQuery = e.target.value.trim(); currentPage = 1; loadActivities(); }, 400);
        });
        ['filterCountry','filterType','filterStatus'].forEach(id => {
            document.getElementById(id)?.addEventListener('change', () => {
                filterCountry = document.getElementById('filterCountry').value;
                filterType    = document.getElementById('filterType').value;
                filterStatus  = document.getElementById('filterStatus').value;
                currentPage = 1; loadActivities();
            });
        });
        document.getElementById('btnSync').addEventListener('click', async () => {
            const action = prompt('Enter "export" (DB→JSON) or "import" (JSON→DB):');
            if (!action) return;
            try {
                const res  = await fetch(`${API_ACTIVITIES_SYNC}`, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action}) });
                const json = await res.json();
                alert(json.message || JSON.stringify(json));
                if (json.success) loadActivities();
            } catch(e) { alert('Error: '+e.message); }
        });
    }

    // ── Load List ────────────────────────────────────────────────
    async function loadActivities() {
        const tbody = document.getElementById('actTableBody');
        tbody.innerHTML = `<tr><td colspan="9" class="text-center py-12 text-gray-400"><i class="fa-solid fa-spinner fa-spin text-xl mr-2"></i>Loading…</td></tr>`;
        const params = new URLSearchParams({ page:currentPage, limit:20, search:searchQuery, country_sys_id:filterCountry, type:filterType, status:filterStatus });
        try {
            const res  = await fetch(`${API_ACTIVITIES_BASE}list.php?${params}`);
            const json = await res.json();
            if (!json.success) throw new Error(json.message);
            renderTable(json.data);
            renderPagination(json.pagination);
        } catch(err) {
            tbody.innerHTML = `<tr><td colspan="9" class="text-center py-12 text-red-400">${err.message}</td></tr>`;
        }
    }

    function renderTable(acts) {
        const tbody = document.getElementById('actTableBody');
        if (!acts.length) {
            tbody.innerHTML = `<tr><td colspan="9" class="text-center py-16 text-gray-400"><i class="fa-solid fa-person-hiking text-3xl mb-2 block opacity-30"></i>No activities found</td></tr>`;
            return;
        }
        const typeColors = { tour:'bg-blue-50 text-blue-700', transfer:'bg-violet-50 text-violet-700', both:'bg-teal-50 text-teal-700' };
        tbody.innerHTML = acts.map(act => {
            const country = countries.find(c => c.sys_id === act.country_sys_id);
            const statusPill = act.status === 'active'
                ? '<span class="px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-700">Active</span>'
                : '<span class="px-2 py-0.5 rounded-full text-xs bg-red-100 text-red-700">Deleted</span>';
            const stars = Array.from({length:5},(_,i)=>`<i class="fa-${i<act.popularity?'solid':'regular'} fa-star text-amber-400" style="font-size:10px"></i>`).join('');
            const isDeleted = act.status === 'deleted';
            const tc = typeColors[act.type] || 'bg-gray-100 text-gray-600';
            return `<tr class="hover:bg-gray-50 transition">
                <td class="px-4 py-3 font-medium text-gray-800 max-w-[160px] truncate">${esc(act.name)}</td>
                <td class="px-4 py-3 text-xs text-gray-500">${esc(country?.name||act.country_sys_id)}</td>
                <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-xs ${tc}">${cap(act.type)}</span></td>
                <td class="px-4 py-3 text-xs text-gray-500 max-w-[120px] truncate">${esc(act.location||'—')}</td>
                <td class="px-4 py-3 text-center text-xs text-gray-500">${act.duration_hours?act.duration_hours+'h':'—'}</td>
                <td class="px-4 py-3 text-center">${act.transfer_count>0?`<span class="px-2 py-0.5 rounded-full text-xs bg-orange-50 text-orange-600">${act.transfer_count}</span>`:'<span class="text-gray-300 text-xs">—</span>'}</td>
                <td class="px-4 py-3 text-center">${stars}</td>
                <td class="px-4 py-3 text-center">${statusPill}</td>
                <td class="px-4 py-3 text-center">
                    <div class="flex items-center justify-center gap-1">
                    ${isDeleted
                        ?`<button onclick="ActivityManager.restoreActivity('${act.sys_id}','${esc(act.name)}')" class="p-1.5 rounded-lg text-green-600 hover:bg-green-50 text-xs" title="Restore"><i class="fa-solid fa-rotate-left"></i></button>`
                        :`<button onclick="ActivityManager.editActivity('${act.sys_id}')" class="p-1.5 rounded-lg text-blue-600 hover:bg-blue-50 text-xs" title="Edit"><i class="fa-solid fa-pen-to-square"></i></button>
                          <button onclick="ActivityManager.deleteActivity('${act.sys_id}','${esc(act.name)}')" class="p-1.5 rounded-lg text-red-500 hover:bg-red-50 text-xs" title="Delete"><i class="fa-solid fa-trash"></i></button>`}
                    </div>
                </td></tr>`;
        }).join('');
    }

    function renderPagination(p) {
        const el = document.getElementById('actPagination');
        if (!p || p.total_pages <= 1) { el.innerHTML = ''; return; }
        let btns = '';
        for (let i = 1; i <= p.total_pages; i++) {
            btns += `<button data-pg="${i}" class="pg-btn w-9 h-9 rounded-xl text-sm font-medium border transition ${i===p.page?'bg-blue-600 text-white border-blue-600':'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'}">${i}</button>`;
        }
        el.innerHTML = `<p class="text-sm text-gray-500">Showing ${((p.page-1)*p.limit)+1}–${Math.min(p.page*p.limit,p.total)} of ${p.total}</p><div class="flex gap-1.5">${btns}</div>`;
        el.querySelectorAll('.pg-btn').forEach(b => b.addEventListener('click', () => { currentPage=parseInt(b.dataset.pg); loadActivities(); }));
    }

    // ── Modal ────────────────────────────────────────────────────
    async function openModal(activity = null) {
        editingSysId = activity?.sys_id || null;
        formState    = newFormState();
        if (activity) Object.assign(formState, activity);

        document.getElementById('actModalTitle').textContent = activity ? 'Edit Activity' : 'Add Activity';

        const opts = countries.map(c=>`<option value="${c.sys_id}">${esc(c.name)}</option>`).join('');
        document.getElementById('fCountry').innerHTML = '<option value="">— Select Country —</option>'+opts;

        populateBasicTab();
        if (formState.country_sys_id) await loadCarsForCountry(formState.country_sys_id);
        populateCitiesTab();
        populateListTab('itineraries','itinerariesList');
        populateListTab('inclusions','inclusionsList');
        populateListTab('exclusions','exclusionsList');
        populateTransfersTab();

        document.getElementById('fCountry').addEventListener('change', async function() {
            formState.country_sys_id = this.value;
            await loadCarsForCountry(this.value);
            updateCarSelects();
            // Auto-update any existing empty city rows to the newly selected country
            ['pickupList','dropoffList'].forEach(listId => {
                document.getElementById(listId)?.querySelectorAll('.city-country').forEach(cntSel => {
                    // Only update rows that haven't had a country manually chosen yet
                    if (!cntSel.value) {
                        cntSel.value = this.value;
                        const citySel = cntSel.closest('div')?.querySelector('.city-city');
                        if (citySel) fillCities(cntSel, citySel, '');
                    }
                });
            });
        });
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => switchTab(btn.dataset.tab));
        });
        document.getElementById('actModalClose').addEventListener('click', closeModal);
        document.getElementById('actModalCancel').addEventListener('click', closeModal);
        document.getElementById('actModalSave').addEventListener('click', saveActivity);
        document.getElementById('actModal').addEventListener('click', e => { if(e.target===document.getElementById('actModal'))closeModal(); });
        document.getElementById('btnAddPickup').addEventListener('click', ()=>addCityRow('pickup'));
        document.getElementById('btnAddDropoff').addEventListener('click', ()=>addCityRow('dropoff'));
        document.getElementById('btnAddTransfer').addEventListener('click', ()=>addTransferRow());

        switchTab('basic');
        hideError();
        const modal = document.getElementById('actModal');
        modal.classList.remove('hidden'); modal.classList.add('flex');
    }

    function closeModal() {
        const modal = document.getElementById('actModal');
        modal.classList.add('hidden'); modal.classList.remove('flex');
        editingSysId = null;
    }

    function switchTab(tab) {
        activeTab = tab;
        document.querySelectorAll('.tab-btn').forEach(b => {
            const on = b.dataset.tab === tab;
            b.classList.toggle('border-blue-600', on); b.classList.toggle('text-blue-600', on);
            b.classList.toggle('border-transparent', !on); b.classList.toggle('text-gray-500', !on);
        });
        ['basic','cities','itinerary','inc-exc','transfers'].forEach(t => {
            document.getElementById(`tab-${t}`)?.classList.toggle('hidden', t !== tab);
        });
    }

    // ── Tab populators ───────────────────────────────────────────
    function populateBasicTab() {
        document.getElementById('fName').value       = formState.name;
        document.getElementById('fCountry').value    = formState.country_sys_id;
        document.getElementById('fType').value       = formState.type;
        document.getElementById('fLocation').value   = formState.location;
        document.getElementById('fStartTime').value  = formState.start_time || '';
        document.getElementById('fEndTime').value    = formState.end_time   || '';
        document.getElementById('fDuration').value   = formState.duration_hours;
        document.getElementById('fPopularity').value = formState.popularity;
        if (editingSysId) document.getElementById('fCountry').disabled = true;
    }

    function populateCitiesTab() {
        document.getElementById('pickupList').innerHTML  = '';
        document.getElementById('dropoffList').innerHTML = '';
        (formState.pickup_from_city || []).forEach(item => addCityRow('pickup', item));
        (formState.dropoff_city     || []).forEach(item => addCityRow('dropoff', item));
    }

    function addCityRow(listKey, item = null) {
        const listId = listKey === 'pickup' ? 'pickupList' : 'dropoffList';
        const list   = document.getElementById(listId);
        const row    = document.createElement('div');
        row.className = 'flex items-center gap-2 bg-gray-50 rounded-xl p-3';
        const countryOpts = '<option value="">Country</option>' + countries.map(c=>`<option value="${c.sys_id}">${esc(c.name)}</option>`).join('');
        row.innerHTML = `
            <select class="city-country flex-1 text-xs border border-gray-200 rounded-lg px-2 py-2 focus:outline-none">${countryOpts}</select>
            <select class="city-city flex-1 text-xs border border-gray-200 rounded-lg px-2 py-2 focus:outline-none"><option value="">— City —</option></select>
            <button class="text-red-400 hover:text-red-600 px-1"><i class="fa-solid fa-xmark"></i></button>`;
        const cntSel  = row.querySelector('.city-country');
        const citySel = row.querySelector('.city-city');
        cntSel.addEventListener('change', () => fillCities(cntSel, citySel, ''));
        row.querySelector('button').addEventListener('click', () => row.remove());
        if (item) {
            // Editing existing — restore saved country + city
            cntSel.value = item.country_sys_id;
            fillCities(cntSel, citySel, item.city_sys_id);
        } else {
            // New row — auto-select the country chosen in Basic Info tab
            const basicCountry = document.getElementById('fCountry')?.value || '';
            if (basicCountry) {
                cntSel.value = basicCountry;
                fillCities(cntSel, citySel, '');
            }
        }
        list.appendChild(row);
    }

    function fillCities(cntSel, citySel, selectedId) {
        const country = countries.find(c => c.sys_id === cntSel.value);
        const cities  = country?.cities || [];
        citySel.innerHTML = '<option value="">— City —</option>' +
            cities.map(c=>`<option value="${c.id}" data-name="${esc(c.name)}" ${c.id===selectedId?'selected':''}>${esc(c.name)}</option>`).join('');
    }

    function populateListTab(key, listId) {
        const list = document.getElementById(listId);
        if (!list) return;
        list.innerHTML = '';
        (formState[key] || []).forEach(item => addListItemRow(listId, item));
    }

    function _addListItem(key) {
        const listId = key==='itineraries'?'itinerariesList':key+'List';
        addListItemRow(listId, {title:'',description:'',icon:''});
    }

    function addListItemRow(listId, item={}) {
        const list = document.getElementById(listId);
        if (!list) return;
        const row  = document.createElement('div');
        row.className = 'bg-gray-50 rounded-xl p-3 space-y-2';
        row.innerHTML = `
            <div class="flex items-center gap-2">
                <input type="text" placeholder="Time" value="${esc(item.title||'')}" class="item-title flex-1 text-xs border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400">
                <button class="text-red-400 hover:text-red-600"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <textarea placeholder="Description…" rows="2" class="item-desc w-full text-xs border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400">${esc(item.description||'')}</textarea>`;
        row.querySelector('button').addEventListener('click', () => row.remove());
        list.appendChild(row);
    }

    function populateTransfersTab() {
        const list = document.getElementById('transfersList');
        if (!list) return;
        list.innerHTML = '';
        (formState.transfers || []).forEach(t => addTransferRow(t));
    }

    function addTransferRow(t = null) {
        const list = document.getElementById('transfersList');
        if (!list) return;
        const transfer = t || {title:'',type:'sic',notes:'',pricing:[]};
        const row = document.createElement('div');
        row.className = 'border border-gray-200 rounded-xl overflow-hidden';
        row.innerHTML = `
            <div class="bg-gray-50 px-4 py-3 flex items-center gap-3">
                <input type="text" placeholder="e.g. Airport → Hotel" value="${esc(transfer.title)}" class="tr-title flex-1 text-sm border border-gray-200 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-1 focus:ring-blue-400">
                <select class="tr-type text-sm border border-gray-200 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-1 focus:ring-blue-400">
                    <option value="sic"     ${transfer.type==='sic'    ?'selected':''}>SIC</option>
                    <option value="private" ${transfer.type==='private'?'selected':''}>Private</option>
                </select>
                <button class="btn-remove-tr text-red-400 hover:text-red-600 px-1"><i class="fa-solid fa-trash text-xs"></i></button>
            </div>
            <div class="px-4 py-3 space-y-2">
                <input type="text" placeholder="Notes (optional)" value="${esc(transfer.notes||'')}" class="tr-notes w-full text-xs border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400">
                <div class="tr-pricing-labels grid grid-cols-5 gap-2 text-xs text-gray-400 font-medium px-1 mt-1">
                    <span class="col-span-2">Car</span><span>Adult</span><span>Child</span><span>Full</span>
                </div>
                <div class="tr-pricing space-y-2"></div>
                <button class="btn-add-pricing text-xs px-3 py-1.5 rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50 transition mt-1">
                    <i class="fa-solid fa-plus mr-1"></i> Add Car / Pricing
                </button>
            </div>`;
        row.querySelector('.btn-remove-tr').addEventListener('click', () => row.remove());
        row.querySelector('.btn-add-pricing').addEventListener('click', () => addPricingRow(row));
        (transfer.pricing || []).forEach(p => addPricingRow(row, p));
        list.appendChild(row);
    }

    function addPricingRow(tRow, p = null) {
        const pr      = p || {car_sys_id:'',car_name:'',price_adult:null,price_child:null,price_full:null};
        const country = formState.country_sys_id || document.getElementById('fCountry')?.value || '';
        const cars    = carsCache[country] || [];
        const typeVal = tRow.querySelector('.tr-type')?.value || 'sic';
        const isSIC   = typeVal === 'sic';
        const pRow    = document.createElement('div');
        pRow.className = 'grid grid-cols-5 gap-2 items-center bg-blue-50 rounded-lg p-2';
        pRow.innerHTML = `
            <div class="col-span-2">
                <select class="car-select w-full text-xs border border-gray-200 rounded-lg px-2 py-1.5 bg-white focus:outline-none">
                    <option value="">— Car —</option>
                    ${cars.map(c=>`<option value="${c.sys_id}" data-name="${esc(c.name)}" ${c.sys_id===pr.car_sys_id?'selected':''}>${esc(c.name)} (${c.seats})</option>`).join('')}
                </select>
            </div>
            <input type="number" placeholder="Adult" value="${pr.price_adult??''}" min="0" step="0.01" class="pr-adult text-xs border border-gray-200 rounded-lg px-2 py-1.5 bg-white focus:outline-none ${!isSIC?'opacity-40 pointer-events-none':''}">
            <input type="number" placeholder="Child" value="${pr.price_child??''}" min="0" step="0.01" class="pr-child text-xs border border-gray-200 rounded-lg px-2 py-1.5 bg-white focus:outline-none ${!isSIC?'opacity-40 pointer-events-none':''}">
            <input type="number" placeholder="Full"  value="${pr.price_full??''}"  min="0" step="0.01" class="pr-full  text-xs border border-gray-200 rounded-lg px-2 py-1.5 bg-white focus:outline-none ${isSIC?'opacity-40 pointer-events-none':''}">`;
        tRow.querySelector('.tr-type').addEventListener('change', function() {
            const sic = this.value==='sic';
            ['pr-adult','pr-child'].forEach(c => { pRow.querySelector('.'+c).classList.toggle('opacity-40',!sic); pRow.querySelector('.'+c).classList.toggle('pointer-events-none',!sic); });
            pRow.querySelector('.pr-full').classList.toggle('opacity-40',sic); pRow.querySelector('.pr-full').classList.toggle('pointer-events-none',sic);
        });
        tRow.querySelector('.tr-pricing').appendChild(pRow);
    }

    function updateCarSelects() {
        const country = formState.country_sys_id;
        const cars    = carsCache[country] || [];
        const opts    = '<option value="">— Car —</option>' + cars.map(c=>`<option value="${c.sys_id}" data-name="${esc(c.name)}">${esc(c.name)} (${c.seats})</option>`).join('');
        document.querySelectorAll('.car-select').forEach(sel => sel.innerHTML = opts);
    }

    // ── Collect + Save ───────────────────────────────────────────
    function collectFormState() {
        formState.country_sys_id = document.getElementById('fCountry').value;
        formState.name           = document.getElementById('fName').value.trim();
        formState.type           = document.getElementById('fType').value;
        formState.location       = document.getElementById('fLocation').value.trim();
        formState.start_time     = document.getElementById('fStartTime').value;
        formState.end_time       = document.getElementById('fEndTime').value;
        formState.duration_hours = parseFloat(document.getElementById('fDuration').value)||0;
        formState.popularity     = parseInt(document.getElementById('fPopularity').value)||3;

        // Cities
        const collectCities = listId => {
            const result = [];
            document.getElementById(listId)?.querySelectorAll('.city-country')?.forEach(cntSel => {
                const row     = cntSel.closest('.flex');
                const citySel = row?.querySelector('.city-city');
                if (!cntSel.value || !citySel?.value) return;
                const country = countries.find(c => c.sys_id === cntSel.value);
                const cityOpt = citySel.options[citySel.selectedIndex];
                result.push({ country_sys_id: cntSel.value, country_name: country?.name||'', city_sys_id: citySel.value, city_name: cityOpt?.dataset?.name || cityOpt?.text || '' });
            });
            return result;
        };
        formState.pickup_from_city = collectCities('pickupList');
        formState.dropoff_city     = collectCities('dropoffList');

        // List items
        const collectItems = listId => {
            const result = [];
            document.getElementById(listId)?.children && Array.from(document.getElementById(listId).children).forEach(row => {
                const title = row.querySelector('.item-title')?.value.trim();
                if (!title) return;
                result.push({ title, description: row.querySelector('.item-desc')?.value.trim()||'', icon: row.querySelector('.item-icon')?.value.trim()||'' });
            });
            return result;
        };
        formState.itineraries = collectItems('itinerariesList');
        formState.inclusions  = collectItems('inclusionsList');
        formState.exclusions  = collectItems('exclusionsList');

        // Transfers
        const transfers = [];
        document.getElementById('transfersList')?.children && Array.from(document.getElementById('transfersList').children).forEach(tRow => {
            const title = tRow.querySelector('.tr-title')?.value.trim();
            if (!title) return;
            const type  = tRow.querySelector('.tr-type')?.value || 'sic';
            const notes = tRow.querySelector('.tr-notes')?.value.trim()||'';
            const pricing = [];
            tRow.querySelectorAll('.tr-pricing > div').forEach(pRow => {
                const carSel = pRow.querySelector('.car-select');
                if (!carSel?.value) return;
                const carOpt = carSel.options[carSel.selectedIndex];
                const pa = pRow.querySelector('.pr-adult')?.value;
                const pc = pRow.querySelector('.pr-child')?.value;
                const pf = pRow.querySelector('.pr-full')?.value;
                pricing.push({ car_sys_id: carSel.value, car_name: carOpt?.dataset?.name||'', price_adult: type==='sic'&&pa!==''?parseFloat(pa):null, price_child: type==='sic'&&pc!==''?parseFloat(pc):null, price_full: type==='private'&&pf!==''?parseFloat(pf):null });
            });
            transfers.push({title, type, notes, pricing});
        });
        formState.transfers = transfers;
    }

    async function saveActivity() {
        collectFormState();
        if (!formState.country_sys_id && !editingSysId) return showError('Please select a country.');
        if (!formState.name) return showError('Activity name is required.');
        const btn = document.getElementById('actModalSave');
        btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i>Saving…';
        const body = {...formState};
        if (editingSysId) body.sys_id = editingSysId;
        try {
            const res  = await fetch(`${API_ACTIVITIES_BASE}save.php`, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body) });
            const json = await res.json();
            if (!json.success) { showError(json.message); return; }
            closeModal(); toast('success', json.message); loadActivities();
        } catch(e) { showError('Network error.'); }
        finally { btn.disabled=false; btn.innerHTML='<i class="fa-solid fa-floppy-disk mr-1"></i> Save Activity'; }
    }

    // ── Public actions ───────────────────────────────────────────
    async function editActivity(sys_id) {
        try {
            const res  = await fetch(`${API_ACTIVITIES_BASE}get.php?sys_id=${sys_id}`);
            const json = await res.json();
            if (json.success) openModal(json.data); else toast('error', json.message);
        } catch(e) { toast('error','Could not load activity.'); }
    }

    async function deleteActivity(sys_id, name) {
        if (!confirm(`Delete "${name}"?`)) return;
        try {
            const res  = await fetch(`${API_ACTIVITIES_BASE}delete.php`, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({sys_id}) });
            const json = await res.json();
            toast(json.success?'success':'error', json.message);
            if (json.success) loadActivities();
        } catch(e) { toast('error','Network error.'); }
    }

    async function restoreActivity(sys_id, name) {
        if (!confirm(`Restore "${name}"?`)) return;
        try {
            const res  = await fetch(`${API_ACTIVITIES_BASE}delete.php`, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({sys_id, restore:true}) });
            const json = await res.json();
            toast(json.success?'success':'error', json.message);
            if (json.success) loadActivities();
        } catch(e) { toast('error','Network error.'); }
    }

    // ── Helpers ──────────────────────────────────────────────────
    function showError(msg) { const el=document.getElementById('actError'); el.textContent=msg; el.classList.remove('hidden'); }
    function hideError()    { document.getElementById('actError')?.classList.add('hidden'); }
    function toast(type, msg) {
        const el = document.getElementById('actToast');
        el.className=`fixed bottom-6 right-6 z-50 flex items-center gap-3 px-4 py-3 rounded-xl shadow-xl text-sm font-medium text-white max-w-xs ${type==='success'?'bg-green-600':'bg-red-600'}`;
        el.innerHTML=`<i class="fa-solid fa-${type==='success'?'circle-check':'circle-exclamation'}"></i>${msg}`;
        el.classList.remove('hidden'); setTimeout(()=>el.classList.add('hidden'),3500);
    }
    function esc(str) { return String(str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
    function cap(str) { return str?str.charAt(0).toUpperCase()+str.slice(1):''; }

    return { init, editActivity, deleteActivity, restoreActivity, _addListItem };
})();