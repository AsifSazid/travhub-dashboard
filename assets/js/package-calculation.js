/**
 * TravHub – Package Calculation Module
 * assets/js/package-calculation.js  v5
 *
 * Key identifiers:
 *   URL param      : ?packageId={packages.sys_id}
 *   API field      : package_sys_id
 *   state field    : state.packageSysId
 *   DB columns     : package_calculations.package_sys_id
 *                    packages.package_calculations_details
 */

const PackageCalculation = (() => {

    /* ──────────────────────────────────────────────────────────
       STATE
    ────────────────────────────────────────────────────────── */
    const state = {
        packageSysId     : null,   // packages.sys_id  (e.g. THR-PK-26-00K001)
        countries        : [],
        exchangeRate     : 1,
        localCurrency    : '',     // e.g. "MYR", "THB"
        activityProfitPct: 15,
        hotelProfitPct   : 12,
        savedCalcSysId   : null,   // package_calculations.sys_id after first save
    };

    const PERSONS_PER_ROOM = { Single:1, Double:2, Twin:2, Triple:3, Suite:2 };

    /* ──────────────────────────────────────────────────────────
       INIT
    ────────────────────────────────────────────────────────── */
    async function init() {
        const params         = new URLSearchParams(window.location.search);
        state.packageSysId   = params.get('packageId') || null;

        setupMainLayout();
        renderShell();
        await loadCountries();
        bindGlobalEvents();

        if (state.packageSysId) {
            await loadExistingData(state.packageSysId);
        } else {
            addActivityRow();
            addHotelRow();
        }
        updateAllSummaries();
    }

    /* ──────────────────────────────────────────────────────────
       LAYOUT SETUP
    ────────────────────────────────────────────────────────── */
    function setupMainLayout() {
        const main = document.getElementById('mainContent');
        main.classList.add('flex', 'flex-col', 'overflow-hidden');
        main.style.height = 'calc(100vh - 64px)';
    }

    /* ──────────────────────────────────────────────────────────
       SHELL
    ────────────────────────────────────────────────────────── */
    function renderShell() {
        document.getElementById('mainContent').innerHTML = `

        <!-- ── Top bar ── -->
        <div class="flex items-center gap-3 px-5 py-3 bg-white border-b border-gray-200 flex-shrink-0 mx-4 mb-2">
            <button id="backBtn"
                    class="text-gray-400 hover:text-gray-700 transition w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100">
                <i class="fa-solid fa-arrow-left"></i>
            </button>
            <div>
                <h1 class="text-base font-semibold text-gray-800 leading-tight">Package Pricing Calculator</h1>
                <p id="calcPackageId" class="text-xs text-gray-400 mt-0.5">
                    ${state.packageSysId ? 'Package: ' + state.packageSysId : 'No package linked'}
                </p>
            </div>
            <span id="savedBadge"
                  class="hidden ml-auto text-xs font-semibold px-3 py-1 rounded-full bg-green-100 text-green-700 border border-green-200">
                <i class="fa-solid fa-circle-check mr-1"></i>Saved
            </span>
        </div>

        <!-- ── Two-column body ── -->
        <div class="flex flex-1 min-h-0 overflow-hidden mx-4 mb-2">

            <!-- LEFT 60% ── Activity + Hotel panes -->
            <div class="flex flex-col border-r border-gray-200 overflow-hidden" style="width:60%">

                <!-- ACTIVITY PANE -->
                <div class="flex flex-col flex-1 min-h-0 overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-2.5 bg-white border-b border-gray-200 flex-shrink-0">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-person-hiking text-blue-500 text-sm"></i>
                            <span class="text-sm font-semibold text-gray-700">Activity</span>
                            <span id="actRowBadge"
                                  class="text-xs font-semibold px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">0 rows</span>
                        </div>
                        <button id="addActivityRowBtn"
                                class="flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg border border-blue-500 text-blue-600 hover:bg-blue-50 transition">
                            <i class="fa-solid fa-plus text-xs"></i> Add row
                        </button>
                    </div>

                    <div class="flex-1 overflow-auto min-h-0">
                        <table class="w-full text-xs border-collapse" style="min-width:800px">
                            <thead class="sticky top-0 z-10 bg-gray-50">
                                <tr>
                                    <th class="${thCls()} w-8 text-center">Sl</th>
                                    <th class="${thCls()} w-12 text-center">Days</th>
                                    <th class="${thCls()} w-28">Date</th>
                                    <th class="${thCls()}">Particular</th>
                                    <th class="${thCls()} w-28 text-right">
                                        Price
                                        (<span id="actCurrencyLabel" class="text-blue-500 font-bold">local</span>)
                                    </th>
                                    <th class="${thCls()} w-14 text-center">PAX</th>
                                    <th class="${thCls()} w-28 text-right">Per person (BDT)</th>
                                    <th class="${thCls()} w-28 text-right">Sale price (BDT)</th>
                                    <th class="${thCls()} w-8"></th>
                                </tr>
                            </thead>
                            <tbody id="activityBody" class="divide-y divide-gray-100 bg-white"></tbody>
                        </table>
                        <div id="activityEmpty"
                             class="hidden flex flex-col items-center justify-center py-10 text-gray-300">
                            <i class="fa-solid fa-person-hiking text-4xl mb-2"></i>
                            <p class="text-sm">No activity rows — click "+ Add row"</p>
                        </div>
                    </div>

                    <!-- Activity subtotal bar -->
                    <div class="flex items-center flex-wrap gap-x-3 gap-y-1 px-4 py-2 bg-blue-50 border-t border-blue-100 flex-shrink-0 text-xs">
                        <span class="text-gray-500">Subtotal</span>
                        <span id="actSubtotal" class="font-semibold text-gray-700">0.00</span>
                        <span class="text-gray-300">|</span>
                        <span class="text-gray-500">Profit (<span id="actPctLabel">15</span>%)</span>
                        <span id="actProfitAmt" class="font-semibold text-amber-600">0.00</span>
                        <span class="text-gray-300">|</span>
                        <span class="text-gray-500">Activity total</span>
                        <span id="actTotalAmt" class="font-bold text-blue-600">0.00 BDT</span>
                    </div>
                </div>

                <!-- Divider -->
                <div class="border-t-2 border-gray-300 flex-shrink-0"></div>

                <!-- HOTEL PANE -->
                <div class="flex flex-col flex-1 min-h-0 overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-2.5 bg-white border-b border-gray-200 flex-shrink-0">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-hotel text-violet-500 text-sm"></i>
                            <span class="text-sm font-semibold text-gray-700">Hotel</span>
                            <span id="hotelRowBadge"
                                  class="text-xs font-semibold px-2 py-0.5 rounded-full bg-violet-100 text-violet-700">0 rows</span>
                        </div>
                        <button id="addHotelRowBtn"
                                class="flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg border border-violet-500 text-violet-600 hover:bg-violet-50 transition">
                            <i class="fa-solid fa-plus text-xs"></i> Add row
                        </button>
                    </div>

                    <div class="flex-1 overflow-auto min-h-0">
                        <table class="w-full text-xs border-collapse" style="min-width:980px">
                            <thead class="sticky top-0 z-10 bg-gray-50">
                                <tr>
                                    <th class="${thCls()} w-8 text-center">Sl</th>
                                    <th class="${thCls()}">Hotel name</th>
                                    <th class="${thCls()} w-28">Check-in</th>
                                    <th class="${thCls()} w-28">Check-out</th>
                                    <th class="${thCls()} w-14 text-center">Rooms</th>
                                    <th class="${thCls()} w-14 text-center">Nights</th>
                                    <th class="${thCls()} w-20">Room type</th>
                                    <th class="${thCls()} w-28 text-right">
                                        Price total
                                        (<span id="hotelCurrencyLabel" class="text-violet-500 font-bold">local</span>)
                                    </th>
                                    <th class="${thCls()} w-28 text-right">Per room/night (BDT)</th>
                                    <th class="${thCls()} w-28 text-right">Sale/night (BDT)</th>
                                    <th class="${thCls()} w-8"></th>
                                </tr>
                            </thead>
                            <tbody id="hotelBody" class="divide-y divide-gray-100 bg-white"></tbody>
                        </table>
                        <div id="hotelEmpty"
                             class="hidden flex flex-col items-center justify-center py-10 text-gray-300">
                            <i class="fa-solid fa-hotel text-4xl mb-2"></i>
                            <p class="text-sm">No hotel rows — click "+ Add row"</p>
                        </div>
                    </div>

                    <!-- Hotel subtotal bar -->
                    <div class="flex items-center flex-wrap gap-x-3 gap-y-1 px-4 py-2 bg-violet-50 border-t border-violet-100 flex-shrink-0 text-xs">
                        <span class="text-gray-500">Subtotal</span>
                        <span id="hotelSubtotal" class="font-semibold text-gray-700">0.00</span>
                        <span class="text-gray-300">|</span>
                        <span class="text-gray-500">Profit (<span id="hotelPctLabel">12</span>%)</span>
                        <span id="hotelProfitAmt" class="font-semibold text-amber-600">0.00</span>
                        <span class="text-gray-300">|</span>
                        <span class="text-gray-500">Hotel total</span>
                        <span id="hotelTotalAmt" class="font-bold text-violet-600">0.00 BDT</span>
                    </div>
                </div>

            </div><!-- end left -->

            <!-- RIGHT 40% ── Config + Summary + Actions -->
            <div class="flex flex-col overflow-y-auto bg-gray-50" style="width:40%">

                <!-- Global config -->
                <div class="p-5 border-b border-gray-200 bg-white">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-4">
                        Global Configuration
                    </p>

                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-600 mb-1">
                            <i class="fa-solid fa-globe text-blue-400 mr-1"></i>Country
                        </label>
                        <select id="countrySelect"
                                class="w-full text-sm px-3 py-2 border border-gray-200 rounded-xl bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-400">
                            <option value="">-- Select country --</option>
                        </select>
                        <p id="currencyHint" class="hidden text-xs text-gray-400 mt-1.5">
                            <i class="fa-solid fa-coins text-amber-400 mr-1"></i>
                            <span id="currencyHintText"></span>
                        </p>
                    </div>

                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-600 mb-1">
                            <i class="fa-solid fa-arrow-right-arrow-left text-emerald-400 mr-1"></i>
                            Exchange rate
                            <span class="text-gray-400 font-normal">(1 local = ? BDT)</span>
                        </label>
                        <div class="relative">
                            <input id="exchangeRate" type="number" min="0" step="0.0001" value="1"
                                   class="w-full text-sm px-3 py-2 pr-14 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-400">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400 font-semibold">BDT</span>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Auto-filled from country. Edit to override.</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">
                                <i class="fa-solid fa-percent text-blue-400 mr-1"></i>Activity profit %
                            </label>
                            <div class="relative">
                                <input id="activityProfitPct" type="number" min="0" max="100" step="0.01" value="15"
                                       class="w-full text-sm px-3 py-2 pr-8 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-400 text-center">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400">%</span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">
                                <i class="fa-solid fa-percent text-violet-400 mr-1"></i>Hotel profit %
                            </label>
                            <div class="relative">
                                <input id="hotelProfitPct" type="number" min="0" max="100" step="0.01" value="12"
                                       class="w-full text-sm px-3 py-2 pr-8 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-violet-400 text-center">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400">%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Combined summary -->
                <div class="p-5 border-b border-gray-200 bg-white mt-2">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-4">
                        Combined Summary
                    </p>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-blue-50 rounded-xl p-3 text-center">
                            <p class="text-xs text-blue-500 font-medium mb-1">Activity total</p>
                            <p id="sumActivityTotal" class="text-xl font-bold text-blue-700">0.00</p>
                            <p class="text-xs text-blue-400 mt-0.5">BDT</p>
                        </div>
                        <div class="bg-violet-50 rounded-xl p-3 text-center">
                            <p class="text-xs text-violet-500 font-medium mb-1">Hotel total</p>
                            <p id="sumHotelTotal" class="text-xl font-bold text-violet-700">0.00</p>
                            <p class="text-xs text-violet-400 mt-0.5">BDT</p>
                        </div>
                        <div class="bg-gray-100 rounded-xl p-3 text-center">
                            <p class="text-xs text-gray-500 font-medium mb-1">Total rows</p>
                            <p id="sumItems" class="text-xl font-bold text-gray-700">0</p>
                            <p class="text-xs text-gray-400 mt-0.5">act + hotel</p>
                        </div>
                        <div class="bg-amber-50 rounded-xl p-3 text-center">
                            <p class="text-xs text-amber-500 font-medium mb-1">Total profit</p>
                            <p id="sumProfit" class="text-xl font-bold text-amber-700">0.00</p>
                            <p class="text-xs text-amber-400 mt-0.5">BDT</p>
                        </div>
                        <div class="col-span-2 bg-green-50 border-2 border-green-200 rounded-xl p-4 text-center">
                            <p class="text-xs text-green-600 font-semibold mb-1">Grand Total</p>
                            <p id="sumGrandTotal" class="text-3xl font-bold text-green-700">0.00</p>
                            <p class="text-xs text-green-500 mt-1">BDT combined</p>
                        </div>
                    </div>
                </div>

                <!-- Action buttons -->
                <div class="p-5 bg-white mt-2 flex flex-col gap-2">
                    <button id="saveBtn"
                            class="flex items-center gap-2 w-full px-4 py-2.5 rounded-xl font-semibold text-sm bg-green-600 hover:bg-green-700 text-white transition shadow-sm">
                        <i class="fa-solid fa-floppy-disk"></i> Save Calculation
                    </button>
                    <button id="backBtn2"
                            class="flex items-center gap-2 w-full px-4 py-2.5 rounded-xl font-semibold text-sm bg-blue-600 hover:bg-blue-700 text-white transition shadow-sm">
                        <i class="fa-solid fa-arrow-left"></i> Back to Package
                    </button>
                    <div class="grid grid-cols-2 gap-2 mt-1">
                        <button id="resetBtn"
                                class="flex items-center justify-center gap-2 px-4 py-2 rounded-xl text-sm font-medium border border-gray-200 text-gray-600 hover:bg-gray-100 transition">
                            <i class="fa-solid fa-rotate-left text-xs"></i> Reset
                        </button>
                        <button id="exportBtn"
                                class="flex items-center justify-center gap-2 px-4 py-2 rounded-xl text-sm font-medium border border-gray-200 text-gray-600 hover:bg-gray-100 transition">
                            <i class="fa-solid fa-file-export text-xs"></i> Export CSV
                        </button>
                    </div>
                </div>

                <p class="text-xs text-gray-400 text-center px-5 py-4">
                    Both panels scroll independently. Changing exchange rate or profit % recalculates all rows instantly.
                </p>

            </div><!-- end right -->

        </div><!-- end two-column -->

        <!-- Toast -->
        <div id="calcToast"
             class="hidden fixed bottom-6 right-6 z-50 flex items-center gap-2 px-4 py-3 rounded-xl shadow-xl text-sm font-medium text-white max-w-xs">
        </div>

        <!-- Loader -->
        <div id="calcLoader"
             class="hidden fixed inset-0 z-50 flex items-center justify-center bg-white/70 backdrop-blur-sm flex-col gap-3">
            <i class="fa-solid fa-spinner fa-spin text-3xl text-blue-600"></i>
            <p id="calcLoaderMsg" class="text-sm text-gray-500">Loading…</p>
        </div>
        `;
    }

    /* ── Tailwind class helpers ── */
    function thCls() {
        return 'px-3 py-2 text-left text-xs font-semibold text-gray-500 border-b border-gray-200 whitespace-nowrap bg-gray-50 ';
    }
    function tdCls(extra = '') {
        return `px-3 py-1.5 border-b border-gray-100 align-middle ${extra}`;
    }
    function cellInputCls(extra = '') {
        return `w-full text-xs px-2 py-1 border border-gray-200 rounded-lg bg-white text-gray-700 focus:outline-none focus:ring-1 focus:ring-blue-300 ${extra}`;
    }
    function delBtnCls() {
        return 'w-6 h-6 flex items-center justify-center rounded-md bg-red-50 text-red-400 hover:bg-red-100 hover:text-red-600 transition mx-auto text-xs border border-red-100';
    }

    /* ──────────────────────────────────────────────────────────
       COUNTRIES  — uses API_COUNTRIES global from PHP
    ────────────────────────────────────────────────────────── */
    async function loadCountries() {
        try {
            const url  = (typeof API_COUNTRIES !== 'undefined') ? API_COUNTRIES : '../api/countries.php';
            const res  = await fetch(url);
            const json = await res.json();
            const list = json.data || json.countries || [];
            state.countries = list;
            const sel = document.getElementById('countrySelect');
            list.forEach(c => {
                const opt            = document.createElement('option');
                opt.value            = c.id;
                opt.textContent      = `${c.name} (${c.currency_code || c.code})`;
                opt.dataset.rate     = c.default_rate   ?? 1;
                opt.dataset.currency = c.currency       ?? '';
                opt.dataset.code     = c.currency_code  ?? c.code ?? '';
                sel.appendChild(opt);
            });
        } catch(e) {
            console.error('Countries load failed:', e);
            toast('error', 'Could not load country list');
        }
    }

    function updateCurrencyHint(sel) {
        const opt  = sel.options[sel.selectedIndex];
        const hint = document.getElementById('currencyHint');
        if (!opt?.value) { hint.classList.add('hidden'); return; }
        const code = opt.dataset.code || opt.dataset.currency || '';
        state.localCurrency = code;
        document.getElementById('currencyHintText').textContent =
            `${opt.dataset.currency} (${code}) · default rate: ${opt.dataset.rate} BDT`;
        hint.classList.remove('hidden');
        // Update column headers to show currency code
        const actLabel   = document.getElementById('actCurrencyLabel');
        const hotelLabel = document.getElementById('hotelCurrencyLabel');
        if (actLabel)   actLabel.textContent   = code;
        if (hotelLabel) hotelLabel.textContent = code;
    }

    /* ──────────────────────────────────────────────────────────
       GLOBAL EVENTS
    ────────────────────────────────────────────────────────── */
    function bindGlobalEvents() {
        document.getElementById('countrySelect').addEventListener('change', e => {
            const opt  = e.target.options[e.target.selectedIndex];
            const rate = parseFloat(opt.dataset.rate) || 1;
            document.getElementById('exchangeRate').value = rate;
            state.exchangeRate = rate;
            updateCurrencyHint(e.target);
            recalculateAll();
        });

        document.getElementById('exchangeRate').addEventListener('input', e => {
            state.exchangeRate = parseFloat(e.target.value) || 1;
            recalculateAll();
        });

        document.getElementById('activityProfitPct').addEventListener('input', e => {
            state.activityProfitPct = parseFloat(e.target.value) || 0;
            document.getElementById('actPctLabel').textContent = state.activityProfitPct;
            recalculateActivity();
            updateAllSummaries();
        });

        document.getElementById('hotelProfitPct').addEventListener('input', e => {
            state.hotelProfitPct = parseFloat(e.target.value) || 0;
            document.getElementById('hotelPctLabel').textContent = state.hotelProfitPct;
            recalculateHotel();
            updateAllSummaries();
        });

        document.getElementById('addActivityRowBtn').addEventListener('click', () => addActivityRow());
        document.getElementById('addHotelRowBtn').addEventListener('click',   () => addHotelRow());

        document.getElementById('saveBtn').addEventListener('click',   saveCalculation);
        document.getElementById('resetBtn').addEventListener('click',  resetAll);
        document.getElementById('exportBtn').addEventListener('click', exportCSV);
        ['backBtn','backBtn2'].forEach(id =>
            document.getElementById(id)?.addEventListener('click', goBackToPackage)
        );
    }

    /* ──────────────────────────────────────────────────────────
       LOAD EXISTING
       Reads from packages.package_calculations_details via get.php
    ────────────────────────────────────────────────────────── */
    async function loadExistingData(packageSysId) {
        showLoader('Loading saved calculation…');
        try {
            const res  = await fetch(`../api/package-calculation/get.php?packageId=${encodeURIComponent(packageSysId)}`);
            const json = await res.json();
            hideLoader();

            // Package data from response
            const pkgHotels    = json.package_hotels    || json.data?.package_hotels    || [];
            const pkgItenaries = json.package_itenaries || json.data?.package_itenaries || [];

            // Build activity list from itinerary (one row per activity per day)
            function prefillActivities() {
                const actBody = document.getElementById('activityBody');
                if (!actBody) return;
                actBody.innerHTML = '';  // clear blank row
                let added = 0;
                pkgItenaries.forEach(day => {
                    (day.activities || []).forEach(act => {
                        addActivityRow({
                            days_count : 1,
                            date       : day.date || '',
                            particular : act.name || '',
                            price_local: '',
                            total_pax  : 1,
                        });
                        added++;
                    });
                });
                if (!added) addActivityRow();
            }

            function prefillHotels() {
                const hotelBody = document.getElementById('hotelBody');
                if (!hotelBody) return;
                if (pkgHotels.length) {
                    hotelBody.innerHTML = '';
                    pkgHotels.forEach(h => {
                        if (h.hotel_title) addHotelRow({
                            hotel_name : h.hotel_title,
                            check_in   : h.check_in  || '',
                            check_out  : h.check_out || '',
                        });
                    });
                } else {
                    addHotelRow();
                }
            }

            if (!json.success || !json.exists) {
                prefillActivities();
                prefillHotels();
                return;
            }

            populateCalculationData(json.data);

            // After loading saved calculation, check if activity/hotel rows are empty
            // and prefill from package if so
            const actBody   = document.getElementById('activityBody');
            const hotelBody = document.getElementById('hotelBody');
            if (actBody && actBody.querySelectorAll('tr').length === 0)   prefillActivities();
            if (hotelBody && hotelBody.querySelectorAll('tr').length === 0) prefillHotels();
        } catch(e) {
            hideLoader();
            toast('error', 'Failed to load saved calculation');
            addActivityRow();
            addHotelRow();
        }
    }

    function populateCalculationData(data) {
        // Country selector
        const sel = document.getElementById('countrySelect');
        if (data.country_id) {
            sel.value = data.country_id;
            updateCurrencyHint(sel);
        }

        const rate    = parseFloat(data.exchange_rate        || 1);
        const actPct  = parseFloat(data.activity_profit_pct  ?? data.profit_percentage ?? 15);
        const htlPct  = parseFloat(data.hotel_profit_pct     ?? 12);

        document.getElementById('exchangeRate').value      = rate;
        document.getElementById('activityProfitPct').value = actPct;
        document.getElementById('hotelProfitPct').value    = htlPct;
        state.exchangeRate       = rate;
        state.activityProfitPct  = actPct;
        state.hotelProfitPct     = htlPct;
        state.savedCalcSysId     = data.sys_id || null;
        state.localCurrency      = data.local_currency || '';

        document.getElementById('actPctLabel').textContent   = actPct;
        document.getElementById('hotelPctLabel').textContent = htlPct;

        // calculation_data may arrive as a JSON string or already-parsed object
        let cd = data.calculation_data || {};
        if (typeof cd === 'string') {
            try { cd = JSON.parse(cd); } catch(e) { cd = {}; }
        }
        // Also supports legacy flat array (old single-mode records)
        const acts = Array.isArray(cd) ? cd : (cd.activity || []);
        const htls = Array.isArray(cd) ? [] : (cd.hotel    || []);

        acts.forEach(r => addActivityRow(r));
        htls.forEach(r => addHotelRow(r));
        if (!acts.length) addActivityRow();
        if (!htls.length) addHotelRow();

        updateAllSummaries();
        document.getElementById('savedBadge').classList.remove('hidden');
    }

    /* ──────────────────────────────────────────────────────────
       ACTIVITY ROWS
    ────────────────────────────────────────────────────────── */
    function addActivityRow(saved = null) {
        const tbody = document.getElementById('activityBody');
        const sl    = tbody.querySelectorAll('tr').length + 1;
        const tr    = document.createElement('tr');
        tr.className  = 'hover:bg-blue-50/30 transition-colors';
        tr.dataset.sl = sl;

        tr.innerHTML = `
        <td class="${tdCls('text-center text-xs text-gray-400 font-medium sl-cell')}">${sl}</td>
        <td class="${tdCls()}">
            <input type="number" min="1" value="${saved?.days_count ?? sl}"
                   class="${cellInputCls('w-12 text-center')}">
        </td>
        <td class="${tdCls()}">
            <input type="date" value="${saved?.date || ''}"
                   class="${cellInputCls('w-28')}">
        </td>
        <td class="${tdCls()}">
            <input type="text" value="${escHtml(saved?.particular || '')}" placeholder="Activity description"
                   class="${cellInputCls('min-w-[140px]')}">
        </td>
        <td class="${tdCls()}">
            <input type="number" min="0" step="0.01" value="${saved?.price_local || ''}" placeholder="0.00"
                   class="${cellInputCls('text-right')}">
        </td>
        <td class="${tdCls()}">
            <input type="number" min="1" value="${saved?.total_pax || 1}"
                   class="${cellInputCls('w-14 text-center')}">
        </td>
        <td class="${tdCls('text-right')}">
            <input type="number" min="0" step="0.01" value="${saved?.per_person_bdt ? parseFloat(saved.per_person_bdt).toFixed(2) : ''}" placeholder="0.00"
                   class="${cellInputCls('text-right')} per-person-bdt" title="Edit to back-calculate total price (local)">
        </td>
        <td class="${tdCls('text-right')}">
            <input type="number" min="0" step="0.01" value="${saved?.sale_price_per_person ? parseFloat(saved.sale_price_per_person).toFixed(2) : ''}" placeholder="0.00"
                   class="${cellInputCls('text-right')} sale-price" title="Edit to back-calculate per person BDT">
        </td>
        <td class="${tdCls('text-center')}">
            <button class="del-act ${delBtnCls()}">
                <i class="fa-solid fa-trash-can"></i>
            </button>
        </td>`;

        tbody.appendChild(tr);
        tr.querySelectorAll('input').forEach(i => {
            i.addEventListener('input', () => {
                let dir = 'local';
                if (i.classList.contains('per-person-bdt')) dir = 'perPerson';
                else if (i.classList.contains('sale-price'))    dir = 'sale';
                calcActivityRow(tr, dir);
                updateAllSummaries();
            });
        });
        tr.querySelector('.del-act').addEventListener('click', () => {
            tr.remove();
            renumber('activityBody');
            refreshActivityState();
            updateAllSummaries();
        });

        calcActivityRow(tr);
        refreshActivityState();
        updateAllSummaries();
    }

    // direction: 'local' (price_local edited), 'perPerson' (per_person_bdt edited), 'sale' (sale_price edited)
    function calcActivityRow(tr, direction = 'local') {
        const inputs     = tr.querySelectorAll('input');
        const pax        = parseFloat(inputs[4].value) || 1;
        const rate       = state.exchangeRate || 1;
        const profitPct  = state.activityProfitPct || 0;
        const ppEl       = tr.querySelector('.per-person-bdt');
        const saleEl     = tr.querySelector('.sale-price');
        const localEl    = inputs[3];

        let priceLocal, ppBdt, sale;

        if (direction === 'perPerson') {
            // per_person_bdt edited → back-calc price_local, forward-calc sale
            ppBdt      = parseFloat(ppEl.value) || 0;
            priceLocal = rate > 0 ? (ppBdt * pax) / rate : 0;
            sale       = ppBdt + (ppBdt * profitPct / 100);
            localEl.value    = priceLocal > 0 ? priceLocal.toFixed(2) : '';
            saleEl.value     = sale > 0 ? sale.toFixed(2) : '';
        } else if (direction === 'sale') {
            // sale_price edited → back-calc per_person_bdt and price_local
            sale       = parseFloat(saleEl.value) || 0;
            ppBdt      = profitPct < 100 ? sale / (1 + profitPct / 100) : sale;
            priceLocal = rate > 0 ? (ppBdt * pax) / rate : 0;
            ppEl.value       = ppBdt > 0 ? ppBdt.toFixed(2) : '';
            localEl.value    = priceLocal > 0 ? priceLocal.toFixed(2) : '';
        } else {
            // price_local edited (default) → forward-calc per_person_bdt and sale
            priceLocal = parseFloat(localEl.value) || 0;
            ppBdt      = pax > 0 ? (priceLocal * rate) / pax : 0;
            sale       = ppBdt + (ppBdt * profitPct / 100);
            ppEl.value       = ppBdt > 0 ? ppBdt.toFixed(2) : '';
            saleEl.value     = sale > 0 ? sale.toFixed(2) : '';
        }

        tr.dataset.subtotal  = ppBdt  || 0;
        tr.dataset.saleTotal = sale   || 0;
    }

    function recalculateActivity() {
        document.getElementById('activityBody').querySelectorAll('tr').forEach(tr => calcActivityRow(tr, 'local'));
    }

    function refreshActivityState() {
        const count = document.getElementById('activityBody').querySelectorAll('tr').length;
        document.getElementById('activityEmpty').classList.toggle('hidden', count > 0);
        document.getElementById('actRowBadge').textContent = count + (count === 1 ? ' row' : ' rows');
    }

    /* ──────────────────────────────────────────────────────────
       HOTEL ROWS
    ────────────────────────────────────────────────────────── */
    function addHotelRow(saved = null) {
        const tbody     = document.getElementById('hotelBody');
        const sl        = tbody.querySelectorAll('tr').length + 1;
        const tr        = document.createElement('tr');
        tr.className    = 'hover:bg-violet-50/30 transition-colors';
        tr.dataset.sl   = sl;
        const roomTypes = ['Single','Double','Twin','Triple','Suite'];

        tr.innerHTML = `
        <td class="${tdCls('text-center text-xs text-gray-400 font-medium sl-cell')}">${sl}</td>
        <td class="${tdCls()}">
            <input type="text" value="${escHtml(saved?.hotel_name || '')}" placeholder="Hotel name"
                   class="${cellInputCls('min-w-[120px]')}">
        </td>
        <td class="${tdCls()}">
            <input type="date" value="${saved?.check_in || ''}"
                   class="${cellInputCls('w-28')} check-in">
        </td>
        <td class="${tdCls()}">
            <input type="date" value="${saved?.check_out || ''}"
                   class="${cellInputCls('w-28')} check-out">
        </td>
        <td class="${tdCls()}">
            <input type="number" min="1" value="${saved?.no_of_rooms || 1}"
                   class="${cellInputCls('w-14 text-center')} rooms">
        </td>
        <td class="${tdCls('text-center')}">
            <span class="nights text-xs text-gray-600 font-semibold tabular-nums">${saved?.no_of_nights || 0}</span>
        </td>
        <td class="${tdCls()}">
            <select class="${cellInputCls()} room-type">
                ${roomTypes.map(t => `<option value="${t}" ${saved?.room_type===t?'selected':''}>${t}</option>`).join('')}
            </select>
        </td>
        <td class="${tdCls()}">
            <input type="number" min="0" step="0.01" value="${saved?.price_total || ''}" placeholder="0.00"
                   class="${cellInputCls('text-right')} price-total">
        </td>
        <td class="${tdCls('text-right')}">
            <input type="number" min="0" step="0.01" value="${saved?.price_per_person_per_night_bdt ? parseFloat(saved.price_per_person_per_night_bdt).toFixed(2) : ''}" placeholder="0.00"
                   class="${cellInputCls('text-right')} ppn" title="Edit to back-calculate total price (local)">
        </td>
        <td class="${tdCls('text-right')}">
            <input type="number" min="0" step="0.01" value="${saved?.sale_price_per_night_bdt ? parseFloat(saved.sale_price_per_night_bdt).toFixed(2) : ''}" placeholder="0.00"
                   class="${cellInputCls('text-right')} sale-night" title="Edit to back-calculate per room/night">
        </td>
        <td class="${tdCls('text-center')}">
            <button class="del-hotel ${delBtnCls()}">
                <i class="fa-solid fa-trash-can"></i>
            </button>
        </td>`;

        tbody.appendChild(tr);
        tr.querySelectorAll('input, select').forEach(i => {
            const handler = () => {
                let dir = 'total';
                if (i.classList.contains('ppn'))        dir = 'ppn';
                else if (i.classList.contains('sale-night')) dir = 'sale';
                calcHotelRow(tr, dir);
                updateAllSummaries();
            };
            i.addEventListener('input',  handler);
            i.addEventListener('change', handler);
        });
        tr.querySelector('.del-hotel').addEventListener('click', () => {
            tr.remove();
            renumber('hotelBody');
            refreshHotelState();
            updateAllSummaries();
        });

        calcHotelRow(tr);
        refreshHotelState();
        updateAllSummaries();
    }

    function calcHotelRow(tr, direction = 'total') {
        const checkIn    = tr.querySelector('.check-in').value;
        const checkOut   = tr.querySelector('.check-out').value;
        const rooms      = parseInt(tr.querySelector('.rooms').value) || 1;
        const roomType   = tr.querySelector('.room-type').value;
        const nights     = nightsBetween(checkIn, checkOut);
        const persons    = rooms * (PERSONS_PER_ROOM[roomType] || 2);
        const rate       = state.exchangeRate || 1;
        const profitPct  = state.hotelProfitPct || 0;
        const totalEl    = tr.querySelector('.price-total');
        const ppnEl      = tr.querySelector('.ppn');
        const saleEl     = tr.querySelector('.sale-night');

        tr.querySelector('.nights').textContent = nights;

        let priceTotal, ppn, saleNight;

        if (direction === 'ppn') {
            // per room/night BDT edited → back-calc price_total (local), forward-calc sale_night
            ppn        = parseFloat(ppnEl.value) || 0;
            priceTotal = (rate > 0 && nights > 0 && persons > 0)
                ? (ppn * persons * nights) / rate : 0;
            saleNight  = ppn + (ppn * profitPct / 100);
            totalEl.value  = priceTotal > 0 ? priceTotal.toFixed(2) : '';
            saleEl.value   = saleNight  > 0 ? saleNight.toFixed(2)  : '';
        } else if (direction === 'sale') {
            // sale_night edited → back-calc ppn and price_total
            saleNight  = parseFloat(saleEl.value) || 0;
            ppn        = profitPct < 100 ? saleNight / (1 + profitPct / 100) : saleNight;
            priceTotal = (rate > 0 && nights > 0 && persons > 0)
                ? (ppn * persons * nights) / rate : 0;
            ppnEl.value    = ppn        > 0 ? ppn.toFixed(2)        : '';
            totalEl.value  = priceTotal > 0 ? priceTotal.toFixed(2) : '';
        } else {
            // price_total (local) edited (default) → forward-calc ppn and sale_night
            priceTotal = parseFloat(totalEl.value) || 0;
            ppn        = (persons > 0 && nights > 0)
                ? (priceTotal * rate) / persons / nights : 0;
            saleNight  = ppn + (ppn * profitPct / 100);
            ppnEl.value    = ppn       > 0 ? ppn.toFixed(2)       : '';
            saleEl.value   = saleNight > 0 ? saleNight.toFixed(2) : '';
        }

        tr.dataset.subtotal  = ppn       || 0;
        tr.dataset.saleTotal = saleNight || 0;
        tr.dataset.nights    = nights;
    }

    function recalculateHotel() {
        document.getElementById('hotelBody').querySelectorAll('tr').forEach(tr => calcHotelRow(tr));
    }

    function refreshHotelState() {
        const count = document.getElementById('hotelBody').querySelectorAll('tr').length;
        document.getElementById('hotelEmpty').classList.toggle('hidden', count > 0);
        document.getElementById('hotelRowBadge').textContent = count + (count === 1 ? ' row' : ' rows');
    }

    function nightsBetween(a, b) {
        if (!a || !b) return 0;
        const d = Math.round((new Date(b) - new Date(a)) / 86400000);
        return d > 0 ? d : 0;
    }

    /* ──────────────────────────────────────────────────────────
       RECALCULATE ALL
    ────────────────────────────────────────────────────────── */
    function recalculateAll() {
        recalculateActivity();
        recalculateHotel();
        updateAllSummaries();
    }

    /* ──────────────────────────────────────────────────────────
       SUMMARIES
    ────────────────────────────────────────────────────────── */
    function updateAllSummaries() {
        let actSub=0, actTotal=0, htlSub=0, htlTotal=0;

        document.getElementById('activityBody').querySelectorAll('tr').forEach(tr => {
            actSub   += parseFloat(tr.dataset.subtotal  || 0);
            actTotal += parseFloat(tr.dataset.saleTotal || 0);
        });
        document.getElementById('hotelBody').querySelectorAll('tr').forEach(tr => {
            htlSub   += parseFloat(tr.dataset.subtotal  || 0);
            htlTotal += parseFloat(tr.dataset.saleTotal || 0);
        });

        // Activity bottom bar
        document.getElementById('actSubtotal').textContent  = fmt(actSub);
        document.getElementById('actProfitAmt').textContent = fmt(actTotal - actSub);
        document.getElementById('actTotalAmt').textContent  = fmt(actTotal) + ' BDT';

        // Hotel bottom bar
        document.getElementById('hotelSubtotal').textContent  = fmt(htlSub);
        document.getElementById('hotelProfitAmt').textContent = fmt(htlTotal - htlSub);
        document.getElementById('hotelTotalAmt').textContent  = fmt(htlTotal) + ' BDT';

        // Summary cards
        const grandTotal  = actTotal + htlTotal;
        const totalProfit = (actTotal - actSub) + (htlTotal - htlSub);
        const actRows     = document.getElementById('activityBody').querySelectorAll('tr').length;
        const htlRows     = document.getElementById('hotelBody').querySelectorAll('tr').length;

        document.getElementById('sumActivityTotal').textContent = fmt(actTotal);
        document.getElementById('sumHotelTotal').textContent    = fmt(htlTotal);
        document.getElementById('sumItems').textContent         = actRows + htlRows;
        document.getElementById('sumProfit').textContent        = fmt(totalProfit);
        document.getElementById('sumGrandTotal').textContent    = fmt(grandTotal);
    }

    /* ──────────────────────────────────────────────────────────
       HELPERS
    ────────────────────────────────────────────────────────── */
    function renumber(tbodyId) {
        document.getElementById(tbodyId).querySelectorAll('tr').forEach((tr, i) => {
            const sl = tr.querySelector('.sl-cell');
            if (sl) sl.textContent = i + 1;
            tr.dataset.sl = i + 1;
        });
    }

    /* ──────────────────────────────────────────────────────────
       GET TABLE DATA  →  { activity:[], hotel:[] }
    ────────────────────────────────────────────────────────── */
    function getCurrentTableData() {
        const activity = Array.from(document.getElementById('activityBody').querySelectorAll('tr')).map((tr, i) => {
            const ins = tr.querySelectorAll('input');
            return {
                sl:                    i + 1,
                days_count:            parseInt(ins[0].value)   || 0,
                date:                  ins[1].value,
                particular:            ins[2].value,
                price_local:           parseFloat(ins[3].value) || 0,
                total_pax:             parseInt(ins[4].value)   || 1,
                per_person_bdt:        parseFloat(tr.querySelector('.per-person-bdt').value) || 0,
                sale_price_per_person: parseFloat(tr.querySelector('.sale-price').value)        || 0,
            };
        });

        const hotel = Array.from(document.getElementById('hotelBody').querySelectorAll('tr')).map((tr, i) => ({
            sl:                             i + 1,
            hotel_name:                     tr.querySelectorAll('input')[0].value,
            check_in:                       tr.querySelector('.check-in').value,
            check_out:                      tr.querySelector('.check-out').value,
            no_of_rooms:                    parseInt(tr.querySelector('.rooms').value)          || 1,
            no_of_nights:                   parseInt(tr.querySelector('.nights').textContent)   || 0,
            room_type:                      tr.querySelector('.room-type').value,
            price_total:                    parseFloat(tr.querySelector('.price-total').value)  || 0,
            price_per_person_per_night_bdt: parseFloat(tr.querySelector('.ppn').value)        || 0,
            sale_price_per_night_bdt:       parseFloat(tr.querySelector('.sale-night').value)  || 0,
        }));

        return { activity, hotel };
    }

    /* ──────────────────────────────────────────────────────────
       SAVE  →  POST to api/package-calculation/save.php
       Matches the actual save.php field names exactly:
         package_sys_id, country_id, country_name, local_currency,
         exchange_rate, activity_profit_pct, hotel_profit_pct,
         profit_percentage, mode, calculation_data,
         total_subtotal, total_profit, grand_total
    ────────────────────────────────────────────────────────── */
    async function saveCalculation() {
        if (!state.packageSysId) {
            toast('error', 'No package ID — open this page from a package');
            return;
        }

        const sel          = document.getElementById('countrySelect');
        const selectedOpt  = sel.options[sel.selectedIndex];
        const countryId    = parseInt(sel.value) || 0;
        const countryName  = selectedOpt?.text?.split(' (')[0] || '';
        const localCurrency = state.localCurrency || selectedOpt?.dataset?.code || '';
        const tableData    = getCurrentTableData();

        let actSub=0, actTotal=0, htlSub=0, htlTotal=0;
        document.getElementById('activityBody').querySelectorAll('tr').forEach(tr => {
            actSub   += parseFloat(tr.dataset.subtotal  || 0);
            actTotal += parseFloat(tr.dataset.saleTotal || 0);
        });
        document.getElementById('hotelBody').querySelectorAll('tr').forEach(tr => {
            htlSub   += parseFloat(tr.dataset.subtotal  || 0);
            htlTotal += parseFloat(tr.dataset.saleTotal || 0);
        });

        // Payload matches save.php parameter names exactly
        const payload = {
            package_sys_id      : state.packageSysId,
            country_id          : countryId,
            country_name        : countryName,
            local_currency      : localCurrency,
            exchange_rate       : state.exchangeRate,
            activity_profit_pct : state.activityProfitPct,
            hotel_profit_pct    : state.hotelProfitPct,
            profit_percentage   : state.activityProfitPct,   // backward-compat column
            mode                : 'both',
            calculation_data    : tableData,                  // { activity:[], hotel:[] }
            total_subtotal      : actSub + htlSub,
            total_profit        : (actTotal - actSub) + (htlTotal - htlSub),
            grand_total         : actTotal + htlTotal,
        };

        showLoader('Saving…');
        try {
            const res  = await fetch('../api/package-calculation/save.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(payload),
            });
            const json = await res.json();
            hideLoader();
            if (!json.success) throw new Error(json.message);
            state.savedCalcSysId = json.calculation_id;  // package_calculations.sys_id
            toast('success', `Saved! (${json.calculation_id})`);
            document.getElementById('savedBadge').classList.remove('hidden');
        } catch(e) {
            hideLoader();
            toast('error', e.message || 'Save failed');
        }
    }

    /* ──────────────────────────────────────────────────────────
       RESET
    ────────────────────────────────────────────────────────── */
    function resetAll() {
        if (!confirm('Reset all rows in both Activity and Hotel sections?')) return;
        document.getElementById('activityBody').innerHTML = '';
        document.getElementById('hotelBody').innerHTML    = '';
        refreshActivityState();
        refreshHotelState();
        updateAllSummaries();
    }

    /* ──────────────────────────────────────────────────────────
       EXPORT CSV
    ────────────────────────────────────────────────────────── */
    function exportCSV() {
        const { activity, hotel } = getCurrentTableData();
        let csv = '';

        if (activity.length) {
            csv += `=== ACTIVITY (Profit: ${state.activityProfitPct}%) ===\n`;
            csv += 'Sl,Days,Date,Particular,Price (local),PAX,Per person (BDT),Sale price\n';
            activity.forEach(r => {
                csv += `${r.sl},${r.days_count},${r.date},"${r.particular}",${r.price_local},${r.total_pax},${r.per_person_bdt},${r.sale_price_per_person}\n`;
            });
            csv += `Activity total BDT,${document.getElementById('actTotalAmt').textContent}\n\n`;
        }

        if (hotel.length) {
            csv += `=== HOTEL (Profit: ${state.hotelProfitPct}%) ===\n`;
            csv += 'Sl,Hotel name,Check-in,Check-out,Rooms,Nights,Room type,Price total (local),Per p/night (BDT),Sale/night\n';
            hotel.forEach(r => {
                csv += `${r.sl},"${r.hotel_name}",${r.check_in},${r.check_out},${r.no_of_rooms},${r.no_of_nights},${r.room_type},${r.price_total},${r.price_per_person_per_night_bdt},${r.sale_price_per_night_bdt}\n`;
            });
            csv += `Hotel total BDT,${document.getElementById('hotelTotalAmt').textContent}\n\n`;
        }

        csv += `=== COMBINED ===\nGrand total BDT,${document.getElementById('sumGrandTotal').textContent}\n`;

        if (!activity.length && !hotel.length) { toast('error', 'No data to export'); return; }

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url  = URL.createObjectURL(blob);
        Object.assign(document.createElement('a'), {
            href:     url,
            download: `calc_${state.packageSysId || 'export'}_${Date.now()}.csv`,
        }).click();
        URL.revokeObjectURL(url);
    }

    /* ──────────────────────────────────────────────────────────
       NAVIGATION  →  back to create-package.php?uuid={sys_id}&calc=saved
    ────────────────────────────────────────────────────────── */
    function goBackToPackage() {
        window.location.href = state.packageSysId
            ? `create-package.php?packageId=${state.packageSysId}&calc=saved`
            : 'index-packages.php';
    }

    /* ──────────────────────────────────────────────────────────
       UTILITIES
    ────────────────────────────────────────────────────────── */
    function fmt(n, d = 2) {
        return Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: d, maximumFractionDigits: d });
    }
    function escHtml(s) {
        return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function showLoader(msg = 'Loading…') {
        document.getElementById('calcLoaderMsg').textContent = msg;
        const el = document.getElementById('calcLoader');
        el.classList.remove('hidden'); el.classList.add('flex');
    }
    function hideLoader() {
        const el = document.getElementById('calcLoader');
        el.classList.add('hidden'); el.classList.remove('flex');
    }
    function toast(type, msg) {
        const el = document.getElementById('calcToast');
        if (!el) return;
        el.className = [
            'fixed bottom-6 right-6 z-50 flex items-center gap-2 px-4 py-3',
            'rounded-xl shadow-xl text-sm font-medium text-white max-w-xs',
            type === 'success' ? 'bg-green-600' : 'bg-red-600',
        ].join(' ');
        el.innerHTML = `<i class="fa-solid fa-${type === 'success' ? 'circle-check' : 'circle-exclamation'}"></i> ${msg}`;
        el.classList.remove('hidden');
        setTimeout(() => el.classList.add('hidden'), 4000);
    }

    return { init };
})();