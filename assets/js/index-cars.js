/**
 * TravHub — Cars Master Data Manager  (v2)
 * assets/js/index-cars.js
 *
 * Changes from v1:
 *  - Sync button (import / export) with JSON template shown on first use
 *  - Country field locked on edit (cannot change after creation)
 *  - All modals full-screen with padding (inset-0)
 */

const CarsManager = (() => {

    let countries   = [];
    let currentPage = 1;
    let filterCountry = '';
    let filterType    = '';
    let filterStatus  = 'active';
    let searchQuery   = '';
    let searchTimer   = null;
    let editingSysId  = null;

    const CAR_TYPES = ['sedan','van','suv','minibus','microbus','coaster','bus','other'];

    // ── Init ─────────────────────────────────────────────────────
    async function init() {
        renderShell();
        await loadCountries();
        bindEvents();
        loadCars();
    }

    // ── Shell ────────────────────────────────────────────────────
    function renderShell() {
        document.getElementById('mainContent').innerHTML = `
        <div class="px-6 py-6 max-w-screen-xl mx-auto">

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Cars</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Masterdata — per-country car definitions</p>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <button id="btnSyncImport" class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold border border-blue-300 text-blue-700 bg-blue-50 hover:bg-blue-100 transition">
                        <i class="fa-solid fa-cloud-arrow-down"></i> Sync from JSON
                    </button>
                    <button id="btnSyncExport" class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold border border-gray-200 text-gray-600 hover:bg-gray-50 transition">
                        <i class="fa-solid fa-cloud-arrow-up"></i> Export to JSON
                    </button>
                    <button id="btnAddCar" class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold bg-blue-600 hover:bg-blue-700 text-white shadow transition">
                        <i class="fa-solid fa-plus"></i> Add Car
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-5 flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input id="carSearch" type="text" placeholder="Search by name…"
                        class="w-full pl-9 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
                <select id="filterCountry" class="text-sm border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <option value="">All Countries</option>
                </select>
                <select id="filterType" class="text-sm border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <option value="">All Types</option>
                    ${CAR_TYPES.map(t => `<option value="${t}">${cap(t)}</option>`).join('')}
                </select>
                <select id="filterStatus" class="text-sm border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <option value="active">Active</option>
                    <option value="all">All</option>
                    <option value="trash">Trash</option>
                </select>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100 text-xs text-gray-500 uppercase tracking-wide">
                        <tr>
                            <th class="px-4 py-3 text-left">Name</th>
                            <th class="px-4 py-3 text-left">Country</th>
                            <th class="px-4 py-3 text-left">Type</th>
                            <th class="px-4 py-3 text-center">Seats</th>
                            <th class="px-4 py-3 text-center">Luggage</th>
                            <th class="px-4 py-3 text-left">Sys ID</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="carTableBody" class="divide-y divide-gray-50">
                        <tr><td colspan="8" class="text-center py-12 text-gray-400">
                            <i class="fa-solid fa-spinner fa-spin text-xl mr-2"></i>Loading…
                        </td></tr>
                    </tbody>
                </table>
            </div>
            <div id="carPagination" class="flex items-center justify-between mt-5"></div>
        </div>

        <!-- ══ Car Modal (full-screen with padding) ══ -->
        <div id="carModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm p-6">
            <div class="w-full max-w-[900px] h-[680px] max-h-[calc(100vh-3rem)] flex flex-col bg-white rounded-2xl shadow-2xl">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
                    <h2 id="carModalTitle" class="text-lg font-bold text-gray-800">Add Car</h2>
                    <button id="carModalClose" class="text-gray-400 hover:text-gray-600 text-xl w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="overflow-y-auto overflow-x-hidden flex-1 px-6 py-5 space-y-4">

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Country <span class="text-red-500">*</span></label>
                        <select id="fCountry" class="w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400 disabled:bg-gray-50 disabled:text-gray-400">
                            <option value="">— Select Country —</option>
                        </select>
                        <p id="fCountryLock" class="hidden mt-1 text-xs text-amber-600"><i class="fa-solid fa-lock mr-1"></i>Country cannot be changed after creation.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Car Name <span class="text-red-500">*</span></label>
                        <input id="fName" type="text" placeholder="e.g. Toyota Hiace Van"
                            class="w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Type</label>
                            <select id="fType" class="w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
                                ${CAR_TYPES.map(t => `<option value="${t}">${cap(t)}</option>`).join('')}
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Seats</label>
                            <input id="fSeats" type="number" min="1" max="60" value="4"
                                class="w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 items-end">
                        <div class="flex items-center gap-3 pb-1">
                            <input id="fHasLuggage" type="checkbox" checked class="w-4 h-4 rounded text-blue-600 cursor-pointer">
                            <label for="fHasLuggage" class="text-sm text-gray-700 cursor-pointer">Has Luggage Space</label>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Max Luggage</label>
                            <input id="fMaxLuggage" type="text" placeholder="e.g. 20kg"
                                class="w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
                        </div>
                    </div>

                    <div id="fError" class="hidden text-sm text-red-600 bg-red-50 rounded-xl px-4 py-3"></div>
                </div>
                <div class="flex gap-3 px-6 py-4 border-t border-gray-100 flex-shrink-0">
                    <button id="carModalCancel" class="flex-1 py-2.5 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 font-medium transition">Cancel</button>
                    <button id="carModalSave" class="flex-1 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold transition">
                        <i class="fa-solid fa-floppy-disk mr-1"></i> Save
                    </button>
                </div>
            </div>
        </div>

        <!-- ══ Sync Result Modal (full-screen with padding) ══ -->
        <div id="syncModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm p-6">
            <div class="w-full max-w-[900px] h-[680px] max-h-[calc(100vh-3rem)] flex flex-col bg-white rounded-2xl shadow-2xl">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
                    <h2 id="syncModalTitle" class="text-lg font-bold text-gray-800">Sync Result</h2>
                    <button id="syncModalClose" class="text-gray-400 hover:text-gray-600 text-xl w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="overflow-y-auto overflow-x-hidden flex-1 px-6 py-5">
                    <div id="syncModalBody"></div>
                </div>
                <div class="px-6 py-4 border-t border-gray-100 flex-shrink-0">
                    <button id="syncModalOk" class="w-full py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold transition">OK</button>
                </div>
            </div>
        </div>

        <!-- Toast -->
        <div id="carToast" class="fixed bottom-6 right-6 z-50 hidden items-center gap-3 px-4 py-3 rounded-xl shadow-xl text-sm font-medium text-white max-w-xs"></div>
        `;
    }

    // ── Countries ────────────────────────────────────────────────
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

    // ── Events ───────────────────────────────────────────────────
    function bindEvents() {
        document.getElementById('btnAddCar').addEventListener('click', () => openModal());

        // Car modal
        document.getElementById('carModalClose').addEventListener('click', closeModal);
        document.getElementById('carModalCancel').addEventListener('click', closeModal);
        document.getElementById('carModalSave').addEventListener('click', saveCar);
        document.getElementById('carModal').addEventListener('click', e => { if (e.target === document.getElementById('carModal')) closeModal(); });

        // Sync modal
        document.getElementById('syncModalClose').addEventListener('click', closeSyncModal);
        document.getElementById('syncModalOk').addEventListener('click', closeSyncModal);
        document.getElementById('syncModal').addEventListener('click', e => { if (e.target === document.getElementById('syncModal')) closeSyncModal(); });

        // Sync buttons
        document.getElementById('btnSyncImport').addEventListener('click', () => runSync('import'));
        document.getElementById('btnSyncExport').addEventListener('click', () => runSync('export'));

        // Search
        document.getElementById('carSearch').addEventListener('input', e => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => { searchQuery = e.target.value.trim(); currentPage = 1; loadCars(); }, 400);
        });

        // Filters
        ['filterCountry','filterType','filterStatus'].forEach(id => {
            document.getElementById(id).addEventListener('change', () => {
                filterCountry = document.getElementById('filterCountry').value;
                filterType    = document.getElementById('filterType').value;
                filterStatus  = document.getElementById('filterStatus').value;
                currentPage = 1; loadCars();
            });
        });
    }

    // ── Load / Render ────────────────────────────────────────────
    async function loadCars() {
        const tbody = document.getElementById('carTableBody');
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-12 text-gray-400"><i class="fa-solid fa-spinner fa-spin text-xl mr-2"></i>Loading…</td></tr>`;
        const params = new URLSearchParams({ page:currentPage, limit:25, search:searchQuery, country_sys_id:filterCountry, type:filterType, status:filterStatus });
        try {
            const res  = await fetch(`${API_CARS_BASE}list.php?${params}`);
            const json = await res.json();
            if (!json.success) throw new Error(json.message);
            renderTable(json.data);
            renderPagination(json.pagination);
        } catch(err) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-12 text-red-400">${err.message}</td></tr>`;
        }
    }

    function renderTable(cars) {
        const tbody = document.getElementById('carTableBody');
        if (!cars.length) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-16 text-gray-400"><i class="fa-solid fa-car text-3xl mb-2 block opacity-30"></i>No cars found</td></tr>`;
            return;
        }
        tbody.innerHTML = cars.map(car => {
            const country   = countries.find(c => c.sys_id === car.country_sys_id);
            const statusPill = car.status === 'active'
                ? '<span class="px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-700">Active</span>'
                : '<span class="px-2 py-0.5 rounded-full text-xs bg-red-100 text-red-700">Deleted</span>';
            const isDeleted = car.status === 'deleted';
            return `<tr class="hover:bg-gray-50 transition">
                <td class="px-4 py-3 font-medium text-gray-800">${esc(car.name)}</td>
                <td class="px-4 py-3 text-xs text-gray-500">${esc(country?.name || car.country_sys_id)}</td>
                <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-xs bg-blue-50 text-blue-700">${cap(car.type)}</span></td>
                <td class="px-4 py-3 text-center text-gray-600">${car.seats}</td>
                <td class="px-4 py-3 text-center">
                    ${car.has_luggage ? `<span class="text-green-600 text-xs"><i class="fa-solid fa-check mr-1"></i>${car.max_luggage||'—'}</span>` : '<span class="text-gray-400 text-xs">No</span>'}
                </td>
                <td class="px-4 py-3 text-xs font-mono text-gray-400">${car.sys_id}</td>
                <td class="px-4 py-3 text-center">${statusPill}</td>
                <td class="px-4 py-3 text-center">
                    <div class="flex items-center justify-center gap-1">
                        ${isDeleted
                            ? `<button onclick="CarsManager.restoreCar('${car.sys_id}','${esc(car.name)}')" class="p-1.5 rounded-lg text-green-600 hover:bg-green-50 text-xs" title="Restore"><i class="fa-solid fa-rotate-left"></i></button>`
                            : `<button onclick="CarsManager.editCar('${car.sys_id}')" class="p-1.5 rounded-lg text-blue-600 hover:bg-blue-50 text-xs" title="Edit"><i class="fa-solid fa-pen-to-square"></i></button>
                               <button onclick="CarsManager.deleteCar('${car.sys_id}','${esc(car.name)}')" class="p-1.5 rounded-lg text-red-500 hover:bg-red-50 text-xs" title="Delete"><i class="fa-solid fa-trash"></i></button>`}
                    </div>
                </td>
            </tr>`;
        }).join('');
    }

    function renderPagination(p) {
        const el = document.getElementById('carPagination');
        if (!p || p.total_pages <= 1) { el.innerHTML = ''; return; }
        let btns = '';
        for (let i = 1; i <= p.total_pages; i++) {
            btns += `<button data-pg="${i}" class="pg-btn w-9 h-9 rounded-xl text-sm font-medium border transition ${i===p.page?'bg-blue-600 text-white border-blue-600':'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'}">${i}</button>`;
        }
        el.innerHTML = `<p class="text-sm text-gray-500">Showing ${((p.page-1)*p.limit)+1}–${Math.min(p.page*p.limit,p.total)} of ${p.total}</p><div class="flex gap-1.5">${btns}</div>`;
        el.querySelectorAll('.pg-btn').forEach(b => b.addEventListener('click', () => { currentPage = parseInt(b.dataset.pg); loadCars(); }));
    }

    // ── Car Modal ────────────────────────────────────────────────
    function openModal(car = null) {
        editingSysId = car?.sys_id || null;
        const isEdit = !!editingSysId;

        document.getElementById('carModalTitle').textContent = isEdit ? 'Edit Car' : 'Add Car';

        // Populate country dropdown
        const opts = countries.map(c => `<option value="${c.sys_id}">${esc(c.name)}</option>`).join('');
        document.getElementById('fCountry').innerHTML = '<option value="">— Select Country —</option>' + opts;

        document.getElementById('fCountry').value       = car?.country_sys_id || '';
        document.getElementById('fName').value           = car?.name          || '';
        document.getElementById('fType').value           = car?.type          || 'sedan';
        document.getElementById('fSeats').value          = car?.seats         || 4;
        document.getElementById('fHasLuggage').checked   = car ? !!car.has_luggage : true;
        document.getElementById('fMaxLuggage').value     = car?.max_luggage   || '';

        // Lock country on edit
        document.getElementById('fCountry').disabled = isEdit;
        document.getElementById('fCountryLock').classList.toggle('hidden', !isEdit);

        hideError();
        const modal = document.getElementById('carModal');
        modal.classList.remove('hidden'); modal.classList.add('flex');
    }

    function closeModal() {
        document.getElementById('carModal').classList.add('hidden');
        document.getElementById('carModal').classList.remove('flex');
        editingSysId = null;
    }

    async function saveCar() {
        const country_sys_id = document.getElementById('fCountry').value;
        const name           = document.getElementById('fName').value.trim();
        const type           = document.getElementById('fType').value;
        const seats          = parseInt(document.getElementById('fSeats').value) || 4;
        const has_luggage    = document.getElementById('fHasLuggage').checked;
        const max_luggage    = document.getElementById('fMaxLuggage').value.trim();

        if (!country_sys_id && !editingSysId) return showError('Please select a country.');
        if (!name) return showError('Car name is required.');

        const btn = document.getElementById('carModalSave');
        btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i>Saving…';

        const body = { name, type, seats, has_luggage, max_luggage };
        if (editingSysId) body.sys_id = editingSysId;
        else body.country_sys_id = country_sys_id;

        try {
            const res  = await fetch(`${API_CARS_BASE}save.php`, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body) });
            const json = await res.json();
            if (!json.success) { showError(json.message); return; }
            closeModal(); toast('success', json.message); loadCars();
        } catch(e) { showError('Network error.'); }
        finally { btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-floppy-disk mr-1"></i> Save'; }
    }

    // ── Sync ─────────────────────────────────────────────────────
    async function runSync(action) {
        const btn = action === 'import' ? document.getElementById('btnSyncImport') : document.getElementById('btnSyncExport');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i>' + (action === 'import' ? 'Importing…' : 'Exporting…');

        try {
            const res  = await fetch(`${API_CARS_BASE}sync.php`, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action}) });
            const json = await res.json();
            showSyncResult(json, action);
            if (json.success) loadCars();
        } catch(e) {
            showSyncResult({ success:false, message: 'Network error: ' + e.message }, action);
        } finally {
            btn.disabled = false;
            btn.innerHTML = action === 'import'
                ? '<i class="fa-solid fa-cloud-arrow-down"></i> Sync from JSON'
                : '<i class="fa-solid fa-cloud-arrow-up"></i> Export to JSON';
        }
    }

    function showSyncResult(json, action) {
        document.getElementById('syncModalTitle').textContent = action === 'import' ? 'Import Result' : 'Export Result';

        let html = '';
        if (!json.success) {
            html = `<div class="bg-red-50 text-red-700 rounded-xl px-4 py-3 text-sm">${esc(json.message)}</div>`;
            if (action === 'import') {
                html += `
                <div class="mt-4">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Expected JSON format <span class="text-gray-400 font-normal normal-case">(save as <code>cars.json</code> in <code>api/</code> folder)</span></p>
                    <pre class="bg-gray-50 rounded-xl p-4 text-xs overflow-x-auto text-gray-700">${esc(JSON.stringify({
                        cars: [
                            { id:1, country_id:1, name:"Toyota Hiace Van", car_type:"van", seat:7, is_luggage:"yes", max_luggage:"20kg" },
                            { id:2, country_id:1, name:"Toyota Camry",     car_type:"sedan", seat:4, is_luggage:"yes", max_luggage:"10kg" },
                            { id:3, country_id:2, name:"Nissan Urvan",     car_type:"van", seat:12, is_luggage:"no",  max_luggage:"" },
                        ]
                    }, null, 2))}</pre>
                    <p class="text-xs text-gray-400 mt-2"><code>country_id</code> is the sequential number of the country in your DB (1 = first active country by insert order).</p>
                </div>`;
            }
        } else {
            const isImport = action === 'import';
            html = `
            <div class="bg-green-50 text-green-700 rounded-xl px-4 py-3 text-sm mb-4">${esc(json.message)}</div>`;
            if (isImport) {
                html += `
                <div class="grid grid-cols-3 gap-3 mb-4">
                    <div class="bg-blue-50 rounded-xl px-4 py-3 text-center">
                        <p class="text-2xl font-bold text-blue-700">${json.inserted}</p>
                        <p class="text-xs text-blue-500 mt-0.5">Inserted</p>
                    </div>
                    <div class="bg-amber-50 rounded-xl px-4 py-3 text-center">
                        <p class="text-2xl font-bold text-amber-600">${json.updated}</p>
                        <p class="text-xs text-amber-500 mt-0.5">Updated</p>
                    </div>
                    <div class="bg-gray-50 rounded-xl px-4 py-3 text-center">
                        <p class="text-2xl font-bold text-gray-500">${json.skipped}</p>
                        <p class="text-xs text-gray-400 mt-0.5">Skipped</p>
                    </div>
                </div>`;
                if (json.log?.length) {
                    html += `<div class="bg-gray-50 rounded-xl p-3 max-h-48 overflow-y-auto">
                        <p class="text-xs font-semibold text-gray-500 mb-2">Log</p>
                        ${json.log.map(l => `<p class="text-xs text-gray-600 py-0.5 border-b border-gray-100 last:border-0">${esc(l)}</p>`).join('')}
                    </div>`;
                }
            } else {
                html += `<p class="text-sm text-gray-500">Exported <strong>${json.cars}</strong> car(s) to <code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded">api/cars.json</code>.</p>`;
            }
        }

        document.getElementById('syncModalBody').innerHTML = html;
        const modal = document.getElementById('syncModal');
        modal.classList.remove('hidden'); modal.classList.add('flex');
    }

    function closeSyncModal() {
        document.getElementById('syncModal').classList.add('hidden');
        document.getElementById('syncModal').classList.remove('flex');
    }

    // ── Public actions ───────────────────────────────────────────
    async function editCar(sys_id) {
        try {
            const res  = await fetch(`${API_CARS_BASE}get.php?sys_id=${sys_id}`);
            const json = await res.json();
            if (json.success) openModal(json.data); else toast('error', json.message);
        } catch(e) { toast('error', 'Could not load car.'); }
    }

    async function deleteCar(sys_id, name) {
        if (!confirm(`Delete "${name}"?`)) return;
        try {
            const res  = await fetch(`${API_CARS_BASE}delete.php`, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({sys_id}) });
            const json = await res.json();
            toast(json.success?'success':'error', json.message);
            if (json.success) loadCars();
        } catch(e) { toast('error', 'Network error.'); }
    }

    async function restoreCar(sys_id, name) {
        if (!confirm(`Restore "${name}"?`)) return;
        try {
            const res  = await fetch(`${API_CARS_BASE}delete.php`, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({sys_id, restore:true}) });
            const json = await res.json();
            toast(json.success?'success':'error', json.message);
            if (json.success) loadCars();
        } catch(e) { toast('error', 'Network error.'); }
    }

    // ── Helpers ──────────────────────────────────────────────────
    function showError(msg) { const el=document.getElementById('fError'); el.textContent=msg; el.classList.remove('hidden'); }
    function hideError()    { document.getElementById('fError')?.classList.add('hidden'); }
    function toast(type, msg) {
        const el = document.getElementById('carToast');
        el.className = `fixed bottom-6 right-6 z-50 flex items-center gap-3 px-4 py-3 rounded-xl shadow-xl text-sm font-medium text-white max-w-xs ${type==='success'?'bg-green-600':'bg-red-600'}`;
        el.innerHTML = `<i class="fa-solid fa-${type==='success'?'circle-check':'circle-exclamation'}"></i>${msg}`;
        el.classList.remove('hidden'); setTimeout(() => el.classList.add('hidden'), 3500);
    }
    function esc(str) { return String(str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
    function cap(str) { return str ? str.charAt(0).toUpperCase() + str.slice(1) : ''; }

    return { init, editCar, deleteCar, restoreCar };
})();