/**
 * TravHub – Package Calculation Module
 * assets/js/package-calculation.js
 */

const PackageCalculation = (() => {

    /* ──────────────────────────────────────────────────────────
       STATE
    ────────────────────────────────────────────────────────── */
    const state = {
        packageUuid : null,
        mode        : 'activity',   // 'activity' | 'hotel'
        activityRows: [],
        hotelRows   : [],
        countries   : [],
        exchangeRate: 1,
        profitPct   : 15,
        savedCalcId : null,
    };

    // default_rate now comes from countries.json → no hardcoded map needed
    const PERSONS_PER_ROOM = { 'Single':1, 'Double':2, 'Twin':2, 'Triple':3, 'Suite':2 };

    /* ──────────────────────────────────────────────────────────
       INIT
    ────────────────────────────────────────────────────────── */
    async function init() {
        const params = new URLSearchParams(window.location.search);
        state.packageUuid = params.get('uuid') || null;

        renderShell();
        await loadCountries();
        bindGlobalEvents();

        if (state.packageUuid) {
            await loadExistingData(state.packageUuid);
        } else {
            addActivityRow();
        }
    }

    /* ──────────────────────────────────────────────────────────
       SHELL
    ────────────────────────────────────────────────────────── */
    function renderShell() {
        document.getElementById('mainContent').innerHTML = `
        <div class="px-6 py-6 max-w-screen-xl mx-auto">

            <!-- Header -->
            <div class="flex items-center gap-4 mb-6">
                <button id="backBtn" class="text-gray-400 hover:text-gray-700 transition">
                    <i class="fa-solid fa-arrow-left text-lg"></i>
                </button>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Package Pricing Calculator</h1>
                    <p class="text-sm text-gray-400" id="calcPackageId">${state.packageUuid ? 'Package: ' + state.packageUuid : 'No package linked'}</p>
                </div>
                <div class="ml-auto flex items-center gap-2">
                    <span id="savedBadge" class="hidden text-xs bg-green-100 text-green-700 font-semibold px-3 py-1 rounded-full border border-green-200">
                        <i class="fa-solid fa-circle-check mr-1"></i>Saved
                    </span>
                </div>
            </div>

            <!-- Config Panel -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
                <h2 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4">Configuration</h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <!-- Country -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                            <i class="fa-solid fa-globe text-blue-400 mr-1"></i> Country
                        </label>
                        <select id="countrySelect"
                                class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white">
                            <option value="">-- Select Country --</option>
                        </select>
                        <p id="currencyHint" class="text-xs text-gray-400 mt-1 hidden">
                            <i class="fa-solid fa-coins mr-1"></i>
                            <span id="currencyHintText"></span>
                        </p>
                    </div>
                    <!-- Exchange Rate -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                            <i class="fa-solid fa-arrow-right-arrow-left text-emerald-400 mr-1"></i>
                            Exchange Rate <span class="text-gray-400 font-normal">(1 Local = ? BDT)</span>
                        </label>
                        <div class="relative">
                            <input id="exchangeRate" type="number" min="0" step="0.0001" value="1"
                                   class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 pr-14">
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs text-gray-400 font-semibold">BDT</span>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Default rate auto-filled. Edit to override.</p>
                    </div>
                    <!-- Profit % -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                            <i class="fa-solid fa-percent text-violet-400 mr-1"></i>
                            Profit Percentage
                        </label>
                        <div class="relative">
                            <input id="profitPct" type="number" min="0" max="100" step="0.01" value="15"
                                   class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 pr-10">
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 font-semibold text-sm">%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mode Toggle -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6 flex items-center gap-6">
                <span class="text-sm font-semibold text-gray-600">Mode:</span>
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input type="radio" name="calcMode" value="activity" checked
                           class="w-4 h-4 text-blue-600 focus:ring-blue-400">
                    <span class="text-sm font-medium text-gray-700">
                        <i class="fa-solid fa-person-hiking text-blue-500 mr-1"></i> Activity
                    </span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input type="radio" name="calcMode" value="hotel"
                           class="w-4 h-4 text-violet-600 focus:ring-violet-400">
                    <span class="text-sm font-medium text-gray-700">
                        <i class="fa-solid fa-hotel text-violet-500 mr-1"></i> Hotel
                    </span>
                </label>
                <div class="ml-auto text-xs text-gray-400 hidden sm:block">
                    Switching mode preserves each tab's data separately
                </div>
            </div>

            <!-- Tables Container -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-6 overflow-hidden">
                <!-- Add Row Bar -->
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                    <h2 id="tableTitle" class="font-bold text-gray-700 text-sm">
                        <i class="fa-solid fa-person-hiking text-blue-500 mr-1.5"></i> Activity Rows
                    </h2>
                    <button id="addRowBtn"
                            class="flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-xl transition shadow-sm">
                        <i class="fa-solid fa-plus text-xs"></i> Add Row
                    </button>
                </div>

                <!-- Activity Table -->
                <div id="activityTableWrap" class="overflow-x-auto">
                    <table class="w-full text-sm min-w-[900px]">
                        <thead>
                            <tr class="bg-blue-50 text-blue-800 text-xs font-semibold uppercase tracking-wide">
                                <th class="px-3 py-3 text-center w-10">Sl</th>
                                <th class="px-3 py-3 text-center w-20">Days</th>
                                <th class="px-3 py-3 text-center w-32">Date</th>
                                <th class="px-3 py-3 text-left">Particular</th>
                                <th class="px-3 py-3 text-right w-32">Price (Local)</th>
                                <th class="px-3 py-3 text-center w-24">Total PAX</th>
                                <th class="px-3 py-3 text-right w-36">Per Person (BDT)</th>
                                <th class="px-3 py-3 text-right w-36">Sale Price/Person</th>
                                <th class="px-3 py-3 text-center w-14"></th>
                            </tr>
                        </thead>
                        <tbody id="activityBody" class="divide-y divide-gray-50"></tbody>
                    </table>
                </div>

                <!-- Hotel Table -->
                <div id="hotelTableWrap" class="overflow-x-auto hidden">
                    <table class="w-full text-sm min-w-[1100px]">
                        <thead>
                            <tr class="bg-violet-50 text-violet-800 text-xs font-semibold uppercase tracking-wide">
                                <th class="px-3 py-3 text-center w-10">Sl</th>
                                <th class="px-3 py-3 text-left">Hotel Name</th>
                                <th class="px-3 py-3 text-center w-28">Check-In</th>
                                <th class="px-3 py-3 text-center w-28">Check-Out</th>
                                <th class="px-3 py-3 text-center w-20">Rooms</th>
                                <th class="px-3 py-3 text-center w-20">Nights</th>
                                <th class="px-3 py-3 text-center w-24">Room Type</th>
                                <th class="px-3 py-3 text-right w-32">Price Total (Local)</th>
                                <th class="px-3 py-3 text-right w-36">Per Person/Night (BDT)</th>
                                <th class="px-3 py-3 text-right w-36">Sale Price/Night</th>
                                <th class="px-3 py-3 text-center w-14"></th>
                            </tr>
                        </thead>
                        <tbody id="hotelBody" class="divide-y divide-gray-50"></tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div id="emptyState" class="text-center py-14 text-gray-300">
                    <i class="fa-solid fa-table text-5xl mb-3 block"></i>
                    <p class="text-sm font-medium">No rows yet. Click "Add Row" to begin.</p>
                </div>
            </div>

            <!-- Summary -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
                <h2 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4">Summary</h2>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="bg-gray-50 rounded-xl p-4 text-center">
                        <p class="text-xs text-gray-400 font-medium mb-1">Total Items</p>
                        <p id="sumItems" class="text-2xl font-bold text-gray-700">0</p>
                    </div>
                    <div class="bg-blue-50 rounded-xl p-4 text-center">
                        <p class="text-xs text-blue-500 font-medium mb-1">Subtotal (BDT)</p>
                        <p id="sumSubtotal" class="text-2xl font-bold text-blue-700">0.00</p>
                    </div>
                    <div class="bg-amber-50 rounded-xl p-4 text-center">
                        <p class="text-xs text-amber-500 font-medium mb-1">Total Profit (BDT)</p>
                        <p id="sumProfit" class="text-2xl font-bold text-amber-700">0.00</p>
                        <p id="sumProfitPct" class="text-xs text-amber-400 mt-0.5">15%</p>
                    </div>
                    <div class="bg-green-50 rounded-xl p-4 text-center border-2 border-green-200">
                        <p class="text-xs text-green-600 font-medium mb-1">Grand Total (BDT)</p>
                        <p id="sumGrandTotal" class="text-2xl font-bold text-green-700">0.00</p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap items-center gap-3">
                <button id="saveBtn"
                        class="flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white font-semibold px-6 py-2.5 rounded-xl shadow transition">
                    <i class="fa-solid fa-floppy-disk"></i> Save Calculation
                </button>
                <button id="backBtn2"
                        class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2.5 rounded-xl shadow transition">
                    <i class="fa-solid fa-arrow-left"></i> Back to Package
                </button>
                <button id="resetBtn"
                        class="flex items-center gap-2 border border-gray-200 text-gray-600 hover:bg-gray-50 font-medium px-5 py-2.5 rounded-xl transition">
                    <i class="fa-solid fa-rotate-left"></i> Reset
                </button>
                <button id="exportBtn"
                        class="flex items-center gap-2 border border-gray-200 text-gray-600 hover:bg-gray-50 font-medium px-5 py-2.5 rounded-xl transition">
                    <i class="fa-solid fa-file-export"></i> Export CSV
                </button>
            </div>

        </div>

        <!-- Toast -->
        <div id="calcToast" class="fixed bottom-6 right-6 z-50 hidden items-center gap-2 px-4 py-3 rounded-xl shadow-xl text-sm font-medium text-white max-w-xs"></div>

        <!-- Loader -->
        <div id="calcLoader" class="fixed inset-0 z-50 hidden items-center justify-center bg-white/70 backdrop-blur-sm">
            <div class="flex flex-col items-center gap-3">
                <i class="fa-solid fa-spinner fa-spin text-3xl text-blue-600"></i>
                <p id="calcLoaderMsg" class="text-sm text-gray-500">Loading…</p>
            </div>
        </div>
        `;
    }

    /* ──────────────────────────────────────────────────────────
       COUNTRIES
       Reads default_rate directly from countries.json via
       api/countries.php — no hardcoded rate map needed.
    ────────────────────────────────────────────────────────── */
    async function loadCountries() {
        try {
            const res  = await fetch('../../api/countries.php');
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Failed to load countries');

            state.countries = json.data || [];
            const sel = document.getElementById('countrySelect');

            state.countries.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id;
                // Show: "Malaysia (MYR)"
                opt.textContent     = `${c.name} (${c.currency_code || c.code})`;
                // Store the default rate and currency info as data attributes
                opt.dataset.rate    = c.default_rate   ?? 1;
                opt.dataset.currency     = c.currency      ?? '';
                opt.dataset.currencyCode = c.currency_code ?? '';
                sel.appendChild(opt);
            });
        } catch(e) {
            console.error('Failed to load countries:', e);
            toast('error', 'Could not load country list');
        }
    }

    /* ──────────────────────────────────────────────────────────
       LOAD EXISTING
    ────────────────────────────────────────────────────────── */
    async function loadExistingData(uuid) {
        showLoader('Loading saved calculation…');
        try {
            const res  = await fetch(`../api/package-calculation/get.php?uuid=${encodeURIComponent(uuid)}`);
            const json = await res.json();
            hideLoader();

            if (!json.success || !json.exists) {
                addActivityRow();
                return;
            }
            populateCalculationData(json.data);
        } catch(e) {
            hideLoader();
            toast('error', 'Failed to load saved calculation');
            addActivityRow();
        }
    }

    function populateCalculationData(data) {
        // Config fields
        const sel = document.getElementById('countrySelect');
        if (data.country_id) {
            sel.value = data.country_id;
            // Trigger the currency hint display
            updateCurrencyHint(sel);
        }
        document.getElementById('exchangeRate').value = data.exchange_rate     || 1;
        document.getElementById('profitPct').value    = data.profit_percentage || 15;
        state.exchangeRate = parseFloat(data.exchange_rate     || 1);
        state.profitPct    = parseFloat(data.profit_percentage || 15);
        state.savedCalcId  = data.id || null;

        // Mode
        const mode = data.mode || 'activity';
        state.mode = mode;
        document.querySelector(`input[name="calcMode"][value="${mode}"]`).checked = true;
        switchMode(mode);

        // Rows
        const rows = data.calculation_data || [];
        if (mode === 'activity') {
            rows.forEach(r => addActivityRow(r));
        } else {
            rows.forEach(r => addHotelRow(r));
        }

        if (rows.length === 0) {
            mode === 'activity' ? addActivityRow() : addHotelRow();
        }

        updateSummary();
        document.getElementById('savedBadge').classList.remove('hidden');
    }

    /* ──────────────────────────────────────────────────────────
       CURRENCY HINT HELPER
    ────────────────────────────────────────────────────────── */
    function updateCurrencyHint(sel) {
        const opt          = sel.options[sel.selectedIndex];
        const hintEl       = document.getElementById('currencyHint');
        const hintTextEl   = document.getElementById('currencyHintText');
        if (!opt || !opt.value) {
            hintEl.classList.add('hidden');
            return;
        }
        const currName = opt.dataset.currency     || '';
        const currCode = opt.dataset.currencyCode || '';
        const rate     = opt.dataset.rate         || '1';
        hintTextEl.textContent = `${currName} (${currCode}) · default rate: ${rate} BDT`;
        hintEl.classList.remove('hidden');
    }

    /* ──────────────────────────────────────────────────────────
       GLOBAL EVENTS
    ────────────────────────────────────────────────────────── */
    function bindGlobalEvents() {
        // Country select → auto-fill exchange rate from data-rate attribute
        document.getElementById('countrySelect').addEventListener('change', e => {
            const opt  = e.target.options[e.target.selectedIndex];
            const rate = parseFloat(opt.dataset.rate) || 1;
            document.getElementById('exchangeRate').value = rate;
            state.exchangeRate = rate;
            updateCurrencyHint(e.target);
            recalculateAll();
        });

        // Exchange rate manual override
        document.getElementById('exchangeRate').addEventListener('input', e => {
            state.exchangeRate = parseFloat(e.target.value) || 1;
            recalculateAll();
        });

        // Profit %
        document.getElementById('profitPct').addEventListener('input', e => {
            state.profitPct = parseFloat(e.target.value) || 0;
            document.getElementById('sumProfitPct').textContent = state.profitPct + '%';
            recalculateAll();
        });

        // Mode toggle
        document.querySelectorAll('input[name="calcMode"]').forEach(radio => {
            radio.addEventListener('change', e => switchMode(e.target.value));
        });

        // Add row
        document.getElementById('addRowBtn').addEventListener('click', () => {
            if (state.mode === 'activity') addActivityRow();
            else addHotelRow();
        });

        // Save
        document.getElementById('saveBtn').addEventListener('click', saveCalculation);

        // Back buttons
        ['backBtn','backBtn2'].forEach(id => {
            document.getElementById(id)?.addEventListener('click', goBackToPackage);
        });

        // Reset
        document.getElementById('resetBtn').addEventListener('click', () => {
            if (!confirm('Reset all rows? This will clear all entered data.')) return;
            document.getElementById('activityBody').innerHTML = '';
            document.getElementById('hotelBody').innerHTML    = '';
            updateEmptyState();
            updateSummary();
        });

        // Export
        document.getElementById('exportBtn').addEventListener('click', exportCSV);
    }

    /* ──────────────────────────────────────────────────────────
       MODE SWITCH
    ────────────────────────────────────────────────────────── */
    function switchMode(mode) {
        state.mode = mode;
        const actWrap   = document.getElementById('activityTableWrap');
        const hotelWrap = document.getElementById('hotelTableWrap');
        const title     = document.getElementById('tableTitle');
        const addBtn    = document.getElementById('addRowBtn');

        if (mode === 'activity') {
            actWrap.classList.remove('hidden');
            hotelWrap.classList.add('hidden');
            title.innerHTML  = '<i class="fa-solid fa-person-hiking text-blue-500 mr-1.5"></i> Activity Rows';
            addBtn.className = addBtn.className.replace('bg-violet-600 hover:bg-violet-700','bg-blue-600 hover:bg-blue-700');
        } else {
            hotelWrap.classList.remove('hidden');
            actWrap.classList.add('hidden');
            title.innerHTML  = '<i class="fa-solid fa-hotel text-violet-500 mr-1.5"></i> Hotel Rows';
            addBtn.className = addBtn.className.replace('bg-blue-600 hover:bg-blue-700','bg-violet-600 hover:bg-violet-700');
        }
        updateEmptyState();
        updateSummary();
    }

    /* ──────────────────────────────────────────────────────────
       ACTIVITY ROWS
    ────────────────────────────────────────────────────────── */
    function addActivityRow(saved = null) {
        const tbody = document.getElementById('activityBody');
        const sl    = tbody.querySelectorAll('tr').length + 1;
        const tr    = document.createElement('tr');
        tr.className  = 'hover:bg-gray-50/80 transition';
        tr.dataset.sl = sl;

        tr.innerHTML = `
        <td class="px-3 py-2 text-center text-xs font-bold text-gray-400 sl-cell">${sl}</td>
        <td class="px-2 py-2">
            <input type="number" min="1" value="${saved?.days_count ?? sl}" placeholder="1"
                   class="w-full px-2 py-1.5 border border-gray-200 rounded-lg text-sm text-center focus:outline-none focus:ring-2 focus:ring-blue-300 days-count">
        </td>
        <td class="px-2 py-2">
            <input type="date" value="${saved?.date || ''}"
                   class="w-full px-2 py-1.5 border border-gray-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-blue-300 act-date">
        </td>
        <td class="px-2 py-2">
            <input type="text" value="${escHtml(saved?.particular || '')}" placeholder="Activity description"
                   class="w-full px-3 py-1.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-300 particular">
        </td>
        <td class="px-2 py-2">
            <input type="number" min="0" step="0.01" value="${saved?.price_local || ''}" placeholder="0.00"
                   class="w-full px-2 py-1.5 border border-gray-200 rounded-lg text-sm text-right focus:outline-none focus:ring-2 focus:ring-blue-300 price-local">
        </td>
        <td class="px-2 py-2">
            <input type="number" min="1" value="${saved?.total_pax || 1}" placeholder="1"
                   class="w-full px-2 py-1.5 border border-gray-200 rounded-lg text-sm text-center focus:outline-none focus:ring-2 focus:ring-blue-300 total-pax">
        </td>
        <td class="px-3 py-2 text-right">
            <span class="per-person-bdt font-semibold text-gray-700 text-sm">${fmt(saved?.per_person_bdt || 0)}</span>
        </td>
        <td class="px-3 py-2 text-right">
            <span class="sale-price font-bold text-green-700 text-sm">${fmt(saved?.sale_price_per_person || 0)}</span>
        </td>
        <td class="px-3 py-2 text-center">
            <button type="button" class="delete-row w-7 h-7 flex items-center justify-center rounded-lg bg-red-50 text-red-400 hover:bg-red-100 hover:text-red-600 transition mx-auto">
                <i class="fa-solid fa-trash-can text-xs"></i>
            </button>
        </td>`;

        tbody.appendChild(tr);
        bindActivityRowEvents(tr);
        calculateActivityRow(tr);
        updateEmptyState();
        updateSummary();
    }

    function bindActivityRowEvents(tr) {
        tr.querySelectorAll('input').forEach(inp => {
            inp.addEventListener('input', () => { calculateActivityRow(tr); updateSummary(); });
        });
        tr.querySelector('.delete-row').addEventListener('click', () => deleteRow(tr, 'activity'));
    }

    function calculateActivityRow(tr) {
        const priceLocal      = parseFloat(tr.querySelector('.price-local').value) || 0;
        const totalPax        = parseFloat(tr.querySelector('.total-pax').value)   || 1;
        const perPersonBdt    = totalPax > 0 ? (priceLocal * state.exchangeRate) / totalPax : 0;
        const salePricePerPerson = perPersonBdt + (perPersonBdt * state.profitPct / 100);

        tr.querySelector('.per-person-bdt').textContent = fmt(perPersonBdt);
        tr.querySelector('.sale-price').textContent     = fmt(salePricePerPerson);
        tr.dataset.subtotal  = perPersonBdt;
        tr.dataset.saleTotal = salePricePerPerson;
    }

    /* ──────────────────────────────────────────────────────────
       HOTEL ROWS
    ────────────────────────────────────────────────────────── */
    function addHotelRow(saved = null) {
        const tbody = document.getElementById('hotelBody');
        const sl    = tbody.querySelectorAll('tr').length + 1;
        const tr    = document.createElement('tr');
        tr.className  = 'hover:bg-gray-50/80 transition';
        tr.dataset.sl = sl;

        const roomTypes = ['Single','Double','Twin','Triple','Suite'];

        tr.innerHTML = `
        <td class="px-3 py-2 text-center text-xs font-bold text-gray-400 sl-cell">${sl}</td>
        <td class="px-2 py-2">
            <input type="text" value="${escHtml(saved?.hotel_name || '')}" placeholder="Hotel name"
                   class="w-full px-3 py-1.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-300 hotel-name">
        </td>
        <td class="px-2 py-2">
            <input type="date" value="${saved?.check_in || ''}"
                   class="w-full px-2 py-1.5 border border-gray-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-violet-300 check-in">
        </td>
        <td class="px-2 py-2">
            <input type="date" value="${saved?.check_out || ''}"
                   class="w-full px-2 py-1.5 border border-gray-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-violet-300 check-out">
        </td>
        <td class="px-2 py-2">
            <input type="number" min="1" value="${saved?.no_of_rooms || 1}" placeholder="1"
                   class="w-full px-2 py-1.5 border border-gray-200 rounded-lg text-sm text-center focus:outline-none focus:ring-2 focus:ring-violet-300 no-of-rooms">
        </td>
        <td class="px-3 py-2 text-center">
            <span class="no-of-nights font-semibold text-gray-600 text-sm">${saved?.no_of_nights || 0}</span>
        </td>
        <td class="px-2 py-2">
            <select class="w-full px-2 py-1.5 border border-gray-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-violet-300 room-type">
                ${roomTypes.map(t => `<option value="${t}" ${saved?.room_type === t ? 'selected' : ''}>${t}</option>`).join('')}
            </select>
        </td>
        <td class="px-2 py-2">
            <input type="number" min="0" step="0.01" value="${saved?.price_total || ''}" placeholder="0.00"
                   class="w-full px-2 py-1.5 border border-gray-200 rounded-lg text-sm text-right focus:outline-none focus:ring-2 focus:ring-violet-300 price-total">
        </td>
        <td class="px-3 py-2 text-right">
            <span class="per-person-per-night font-semibold text-gray-700 text-sm">${fmt(saved?.price_per_person_per_night_bdt || 0)}</span>
        </td>
        <td class="px-3 py-2 text-right">
            <span class="sale-price-night font-bold text-green-700 text-sm">${fmt(saved?.sale_price_per_night_bdt || 0)}</span>
        </td>
        <td class="px-3 py-2 text-center">
            <button type="button" class="delete-row w-7 h-7 flex items-center justify-center rounded-lg bg-red-50 text-red-400 hover:bg-red-100 hover:text-red-600 transition mx-auto">
                <i class="fa-solid fa-trash-can text-xs"></i>
            </button>
        </td>`;

        tbody.appendChild(tr);
        bindHotelRowEvents(tr);
        calculateHotelRow(tr);
        updateEmptyState();
        updateSummary();
    }

    function bindHotelRowEvents(tr) {
        tr.querySelectorAll('input, select').forEach(inp => {
            inp.addEventListener('input',  () => { calculateHotelRow(tr); updateSummary(); });
            inp.addEventListener('change', () => { calculateHotelRow(tr); updateSummary(); });
        });
        tr.querySelector('.delete-row').addEventListener('click', () => deleteRow(tr, 'hotel'));
    }

    function calculateHotelRow(tr) {
        const checkIn    = tr.querySelector('.check-in').value;
        const checkOut   = tr.querySelector('.check-out').value;
        const rooms      = parseInt(tr.querySelector('.no-of-rooms').value) || 1;
        const roomType   = tr.querySelector('.room-type').value;
        const priceTotal = parseFloat(tr.querySelector('.price-total').value) || 0;

        const nights         = calculateNights(checkIn, checkOut);
        const personsPerRoom = PERSONS_PER_ROOM[roomType] || 2;
        const totalPersons   = rooms * personsPerRoom;

        let perPersonPerNight = 0;
        if (totalPersons > 0 && nights > 0) {
            perPersonPerNight = (priceTotal * state.exchangeRate) / totalPersons / nights;
        }
        const salePriceNight = perPersonPerNight + (perPersonPerNight * state.profitPct / 100);

        tr.querySelector('.no-of-nights').textContent         = nights;
        tr.querySelector('.per-person-per-night').textContent = fmt(perPersonPerNight);
        tr.querySelector('.sale-price-night').textContent     = fmt(salePriceNight);
        tr.dataset.subtotal  = perPersonPerNight;
        tr.dataset.saleTotal = salePriceNight;
        tr.dataset.nights    = nights;
    }

    function calculateNights(checkIn, checkOut) {
        if (!checkIn || !checkOut) return 0;
        const diff = Math.round((new Date(checkOut) - new Date(checkIn)) / 86400000);
        return diff > 0 ? diff : 0;
    }

    /* ──────────────────────────────────────────────────────────
       DELETE ROW
    ────────────────────────────────────────────────────────── */
    function deleteRow(tr, mode) {
        tr.remove();
        renumberRows(mode === 'activity' ? 'activityBody' : 'hotelBody');
        updateEmptyState();
        updateSummary();
    }

    function renumberRows(tbodyId) {
        document.getElementById(tbodyId).querySelectorAll('tr').forEach((tr, i) => {
            const sl = tr.querySelector('.sl-cell');
            if (sl) sl.textContent = i + 1;
            tr.dataset.sl = i + 1;
        });
    }

    /* ──────────────────────────────────────────────────────────
       RECALCULATE ALL
    ────────────────────────────────────────────────────────── */
    function recalculateAll() {
        document.getElementById('activityBody').querySelectorAll('tr').forEach(tr => calculateActivityRow(tr));
        document.getElementById('hotelBody').querySelectorAll('tr').forEach(tr => calculateHotelRow(tr));
        updateSummary();
    }

    /* ──────────────────────────────────────────────────────────
       SUMMARY
    ────────────────────────────────────────────────────────── */
    function updateSummary() {
        const tbody = document.getElementById(state.mode === 'activity' ? 'activityBody' : 'hotelBody');
        const rows  = tbody.querySelectorAll('tr');
        let subtotal = 0, grandTotal = 0;
        rows.forEach(tr => {
            subtotal   += parseFloat(tr.dataset.subtotal  || 0);
            grandTotal += parseFloat(tr.dataset.saleTotal || 0);
        });

        document.getElementById('sumItems').textContent      = rows.length;
        document.getElementById('sumSubtotal').textContent   = fmt(subtotal);
        document.getElementById('sumProfit').textContent     = fmt(grandTotal - subtotal);
        document.getElementById('sumGrandTotal').textContent = fmt(grandTotal);
        document.getElementById('sumProfitPct').textContent  = state.profitPct + '%';
    }

    /* ──────────────────────────────────────────────────────────
       EMPTY STATE
    ────────────────────────────────────────────────────────── */
    function updateEmptyState() {
        const tbody     = document.getElementById(state.mode === 'activity' ? 'activityBody' : 'hotelBody');
        const emptyEl   = document.getElementById('emptyState');
        const actWrap   = document.getElementById('activityTableWrap');
        const hotelWrap = document.getElementById('hotelTableWrap');
        const hasRows   = tbody.querySelectorAll('tr').length > 0;

        if (state.mode === 'activity') {
            actWrap.classList.toggle('hidden', !hasRows);
        } else {
            hotelWrap.classList.toggle('hidden', !hasRows);
        }
        emptyEl.classList.toggle('hidden', hasRows);
    }

    /* ──────────────────────────────────────────────────────────
       GET TABLE DATA
    ────────────────────────────────────────────────────────── */
    function getCurrentTableData() {
        if (state.mode === 'activity') {
            return Array.from(document.getElementById('activityBody').querySelectorAll('tr')).map((tr, i) => ({
                sl:                    i + 1,
                days_count:            parseInt(tr.querySelector('.days-count').value) || 0,
                date:                  tr.querySelector('.act-date').value,
                particular:            tr.querySelector('.particular').value,
                price_local:           parseFloat(tr.querySelector('.price-local').value) || 0,
                total_pax:             parseInt(tr.querySelector('.total-pax').value) || 1,
                per_person_bdt:        parseFloat(tr.querySelector('.per-person-bdt').textContent.replace(/,/g,'')) || 0,
                sale_price_per_person: parseFloat(tr.querySelector('.sale-price').textContent.replace(/,/g,'')) || 0,
            }));
        } else {
            return Array.from(document.getElementById('hotelBody').querySelectorAll('tr')).map((tr, i) => ({
                sl:                            i + 1,
                hotel_name:                    tr.querySelector('.hotel-name').value,
                check_in:                      tr.querySelector('.check-in').value,
                check_out:                     tr.querySelector('.check-out').value,
                no_of_rooms:                   parseInt(tr.querySelector('.no-of-rooms').value) || 1,
                no_of_nights:                  parseInt(tr.querySelector('.no-of-nights').textContent) || 0,
                room_type:                     tr.querySelector('.room-type').value,
                price_total:                   parseFloat(tr.querySelector('.price-total').value) || 0,
                price_per_person_per_night_bdt: parseFloat(tr.querySelector('.per-person-per-night').textContent.replace(/,/g,'')) || 0,
                sale_price_per_night_bdt:      parseFloat(tr.querySelector('.sale-price-night').textContent.replace(/,/g,'')) || 0,
            }));
        }
    }

    /* ──────────────────────────────────────────────────────────
       SAVE
    ────────────────────────────────────────────────────────── */
    async function saveCalculation() {
        if (!state.packageUuid) {
            toast('error', 'No package UUID – open this page from a package');
            return;
        }

        const countrySelect = document.getElementById('countrySelect');
        const countryId     = parseInt(countrySelect.value) || 0;
        const countryName   = countrySelect.options[countrySelect.selectedIndex]?.text?.split(' (')[0] || '';
        const calcData      = getCurrentTableData();

        const tbody = document.getElementById(state.mode === 'activity' ? 'activityBody' : 'hotelBody');
        let subtotal = 0, grandTotal = 0;
        tbody.querySelectorAll('tr').forEach(tr => {
            subtotal   += parseFloat(tr.dataset.subtotal  || 0);
            grandTotal += parseFloat(tr.dataset.saleTotal || 0);
        });

        const payload = {
            package_uuid:      state.packageUuid,
            country_id:        countryId,
            country_name:      countryName,
            exchange_rate:     state.exchangeRate,
            profit_percentage: state.profitPct,
            mode:              state.mode,
            calculation_data:  calcData,
            total_subtotal:    subtotal,
            total_profit:      grandTotal - subtotal,
            grand_total:       grandTotal,
        };

        showLoader('Saving calculation…');
        try {
            const res  = await fetch('../api/package-calculation/save.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(payload),
            });
            const json = await res.json();
            hideLoader();
            if (!json.success) throw new Error(json.message);
            state.savedCalcId = json.calculation_id;
            toast('success', `Calculation saved! (ID: ${json.calculation_id})`);
            document.getElementById('savedBadge').classList.remove('hidden');
        } catch(e) {
            hideLoader();
            toast('error', e.message || 'Save failed');
        }
    }

    /* ──────────────────────────────────────────────────────────
       EXPORT CSV
    ────────────────────────────────────────────────────────── */
    function exportCSV() {
        const data = getCurrentTableData();
        if (!data.length) { toast('error', 'No data to export'); return; }

        let csv = '';
        if (state.mode === 'activity') {
            csv += 'Sl,Days Count,Date,Particular,Price (Local),Total PAX,Per Person (BDT),Sale Price/Person\n';
            data.forEach(r => {
                csv += `${r.sl},${r.days_count},${r.date},"${r.particular}",${r.price_local},${r.total_pax},${r.per_person_bdt},${r.sale_price_per_person}\n`;
            });
        } else {
            csv += 'Sl,Hotel Name,Check-In,Check-Out,Rooms,Nights,Room Type,Price Total (Local),Per Person/Night (BDT),Sale Price/Night\n';
            data.forEach(r => {
                csv += `${r.sl},"${r.hotel_name}",${r.check_in},${r.check_out},${r.no_of_rooms},${r.no_of_nights},${r.room_type},${r.price_total},${r.price_per_person_per_night_bdt},${r.sale_price_per_night_bdt}\n`;
            });
        }
        csv += `\nSubtotal BDT,,,,,,,,${document.getElementById('sumSubtotal').textContent}\n`;
        csv += `Total Profit (${state.profitPct}%),,,,,,,,${document.getElementById('sumProfit').textContent}\n`;
        csv += `Grand Total BDT,,,,,,,,${document.getElementById('sumGrandTotal').textContent}\n`;

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url  = URL.createObjectURL(blob);
        const a    = Object.assign(document.createElement('a'), { href: url, download: `calc_${state.packageUuid || 'export'}_${Date.now()}.csv` });
        a.click();
        URL.revokeObjectURL(url);
    }

    /* ──────────────────────────────────────────────────────────
       NAVIGATION
    ────────────────────────────────────────────────────────── */
    function goBackToPackage() {
        window.location.href = state.packageUuid
            ? `create-package.php?uuid=${state.packageUuid}&calc=saved`
            : 'index-packages.php';
    }

    /* ──────────────────────────────────────────────────────────
       UTILITIES
    ────────────────────────────────────────────────────────── */
    function fmt(n, d = 2) {
        return Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: d, maximumFractionDigits: d });
    }

    function escHtml(str) {
        return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function showLoader(msg = 'Loading…') {
        const el = document.getElementById('calcLoader');
        document.getElementById('calcLoaderMsg').textContent = msg;
        el.classList.remove('hidden');
        el.classList.add('flex');
    }

    function hideLoader() {
        const el = document.getElementById('calcLoader');
        el.classList.add('hidden');
        el.classList.remove('flex');
    }

    function toast(type, msg) {
        const el = document.getElementById('calcToast');
        if (!el) return;
        el.className = `fixed bottom-6 right-6 z-50 flex items-center gap-2 px-4 py-3 rounded-xl shadow-xl text-sm font-medium text-white ${type === 'success' ? 'bg-green-600' : 'bg-red-600'}`;
        el.innerHTML = `<i class="fa-solid fa-${type === 'success' ? 'circle-check' : 'circle-exclamation'}"></i> ${msg}`;
        el.classList.remove('hidden');
        setTimeout(() => el.classList.add('hidden'), 4000);
    }

    return { init };
})();