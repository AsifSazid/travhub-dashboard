/**
 * TravHub - Package Builder (Multi-Step Wizard)
 * package-builder.js
 *
 * Key fix:  state.uuid  holds packages.uuid  (for API calls)
 *           state.sys_id holds packages.sys_id (for display + calculator URL)
 *           Calculator URL: package-calculation.php?packageId={sys_id}
 *           get.php param:  ?packageId={sys_id}  (reads package_calculations_details)
 */

const PackageBuilder = (() => {
    const BASE_API   = '../api/packages';
    const TOTAL_STEPS = 8;

    let state = {
        uuid       : null,   // packages.uuid  — used for all API calls
        sys_id     : null,   // packages.sys_id — used for display + calculator
        calcDetails : null,    // ← ADD: stores package_calculations_details
        currentStep: 1,
        autoSaveTimer: null,
        steps: {
            1: { title:'', description:'', rating:0, image:'' },
            2: { countries:[], cities:[], activities:[] },
            3: { duration:'', start_date:'', end_date:'', no_of_pax:{adult:0,child:0,infant:0} },
            4: { hotels:[] },
            5: { pack_itenaries:[] },
            6: { currency_title:'', currency_code:'', currency_symbol:'', overall_price:'', air_ticket_details:'', pack_price:[] },
            7: { pack_inclusions:[], pack_exclusions:[] },
            8: {}
        },
        allCountries   : [],
        allCities      : [],
        allActivities  : {},   // city_sys_id → [activity objects] from DB
    };

    // ─── Init ───────────────────────────────────────────────────
    async function init() {
        renderShell();
        bindGlobalEvents();
        await loadCountries();

        const params    = new URLSearchParams(window.location.search);
        const editSysId  = params.get('packageId');
        const calcParam = params.get('calc');  // 'saved' when returning from calculator

        if (editSysId) {
            await loadExistingPackage(editSysId);
            // If returning from calculator, jump to Step 5 to show the banner
            if (calcParam === 'saved') {
                state.currentStep = 6;
                showStep(6);
            }
        } else {
            showStep(1);
        }
        startAutoSave();
    }

    async function loadExistingPackage(editSysId) {
        showLoader('Loading package…');
        try {
            const res  = await fetch(`${BASE_API}/get.php?packageId=${editSysId}`);
            const json = await res.json();
            if (!json.success) throw new Error(json.message);
            const pkg = json.data;

            // ⚠️ Fixed: uuid and sys_id are different fields
            state.uuid   = pkg.uuid;
            state.sys_id = pkg.sys_id;

            state.steps[1] = { title: pkg.title||'', description: pkg.description||'', rating: pkg.rating||0, image: pkg.image||'' };
            state.steps[2] = { countries: pkg.countries||[], cities: pkg.cities||[], activities: pkg.activities||[] };
            // Pre-fetch DB activity suggestions for already-selected cities
            // Pre-cache activities for each country in the package
            const cachedCountries = new Set();
            for (const city of (pkg.cities || [])) {
                const countrySysId = city.country_sys_id || '';
                if (countrySysId && !cachedCountries.has(countrySysId)) {
                    cachedCountries.add(countrySysId);
                    fetchActivitiesByCountry(countrySysId);  // fire-and-forget
                }
            }
            state.steps[3] = { duration: pkg.duration||'', start_date: pkg.start_date||'', end_date: pkg.end_date||'', no_of_pax: pkg.no_of_pax||{adult:0,child:0,infant:0} };
            state.steps[4] = { hotels: pkg.hotels||[] };
            state.steps[5] = { pack_itenaries: pkg.pack_itenaries||[] };
            state.steps[6] = { currency_title: pkg.currency_title||'', currency_code: pkg.currency_code||'', currency_symbol: pkg.currency_symbol||'', overall_price: pkg.overall_price||'', air_ticket_details: pkg.air_ticket_details||'', pack_price: pkg.pack_price||[] };
            state.steps[7] = { pack_inclusions: pkg.pack_inclusions||[], pack_exclusions: pkg.pack_exclusions||[] };
            state.calcDetails = pkg.package_calculations_details || null;
            state.currentStep = pkg.progress_step || 1;
        } catch(e) {
            toast('error', e.message);
            state.currentStep = 1;
        }
        hideLoader();
        showStep(state.currentStep);
    }

    // ─── Shell ───────────────────────────────────────────────────
    function renderShell() {
        document.getElementById('mainContent').innerHTML = `
        <div class="px-6 py-6 max-w-screen-xl mx-auto">

            <div class="flex items-center gap-4 mb-6">
                <a href="index-packages.php" class="text-gray-400 hover:text-gray-600 transition">
                    <i class="fa-solid fa-arrow-left text-lg"></i>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800" id="builderTitle">New Package</h1>
                    <p class="text-sm text-gray-400" id="builderSysId"></p>
                </div>
                <div class="ml-auto flex items-center gap-2 text-xs text-gray-400" id="autoSaveStatus">
                    <i class="fa-regular fa-clock"></i> Auto-save active
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-6 overflow-hidden">
                <div class="flex items-center gap-1" id="stepIndicator"></div>
            </div>

            <div id="stepContent" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6 min-h-[400px] max-h-[70vh] overflow-y-auto overflow-x-hidden">
                <div class="flex items-center justify-center py-20 text-gray-300">
                    <i class="fa-solid fa-spinner fa-spin text-3xl"></i>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex items-center justify-between gap-3">
                <button id="btnPrev" class="flex items-center gap-2 px-5 py-2.5 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 font-medium transition disabled:opacity-40 disabled:cursor-not-allowed">
                    <i class="fa-solid fa-chevron-left text-xs"></i> Previous
                </button>
                <div class="text-sm text-gray-400" id="stepCounter">Step 1 of 8</div>
                <button id="btnNext" class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold transition">
                    Next <i class="fa-solid fa-chevron-right text-xs"></i>
                </button>
            </div>
        </div>

        <div id="builderLoader" class="fixed inset-0 z-50 hidden items-center justify-center bg-white/80 backdrop-blur-sm flex-col gap-3">
            <i class="fa-solid fa-spinner fa-spin text-3xl text-blue-600"></i>
            <p id="loaderMsg" class="text-sm text-gray-500">Saving…</p>
        </div>

        <div id="builderToast" class="fixed bottom-6 right-6 z-50 hidden items-center gap-2 px-4 py-3 rounded-xl shadow-xl text-sm font-medium text-white"></div>
        `;
    }

    // ─── Step Indicator ──────────────────────────────────────────
    const STEP_LABELS = ['Basic Info','Destination','Quotation','Accommodation','Itinerary','Pricing','Inc/Exc','Review'];

    function renderStepIndicator() {
        const el = document.getElementById('stepIndicator');
        el.innerHTML = STEP_LABELS.map((label, i) => {
            const n      = i + 1;
            const done   = n < state.currentStep;
            const active = n === state.currentStep;
            return `
            <div class="flex items-center flex-1" style="min-width:0">
                <div class="flex flex-col items-center flex-1 cursor-pointer step-jump gap-1" data-step="${n}" title="${label}">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition flex-shrink-0
                        ${done   ? 'bg-green-500 text-white' : ''}
                        ${active ? 'bg-blue-600 text-white ring-4 ring-blue-100' : ''}
                        ${!done && !active ? 'bg-gray-100 text-gray-400' : ''}">
                        ${done ? '<i class="fa-solid fa-check" style="font-size:10px"></i>' : n}
                    </div>
                    <span class="text-xs leading-tight text-center hidden sm:block max-w-[64px] ${active ? 'text-blue-600 font-semibold' : 'text-gray-400'}">${label}</span>
                </div>
                ${n < TOTAL_STEPS ? `<div class="h-0.5 flex-1 mx-0.5 mt-[-12px] ${done ? 'bg-green-400' : 'bg-gray-200'} transition hidden sm:block flex-shrink-0" style="min-width:8px"></div>` : ''}
            </div>`;
        }).join('');

        el.querySelectorAll('.step-jump').forEach(btn => {
            btn.addEventListener('click', () => {
                const target = parseInt(btn.dataset.step);
                if (target < state.currentStep && state.uuid) navigateToStep(target);
            });
        });

        document.getElementById('stepCounter').textContent = `Step ${state.currentStep} of ${TOTAL_STEPS}`;
        document.getElementById('btnPrev').disabled = state.currentStep === 1;
        const btnNext = document.getElementById('btnNext');
        if (state.currentStep === TOTAL_STEPS) {
            // Step 8: hide Next — actions are Save/Finalize buttons inside the step
            btnNext.classList.add('hidden');
        } else {
            btnNext.classList.remove('hidden');
            btnNext.innerHTML = 'Next <i class="fa-solid fa-chevron-right text-xs"></i>';
            btnNext.className = 'flex items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold transition';
        }
    }

    // ─── Navigation ─────────────────────────────────────────────
    function bindGlobalEvents() {
        document.addEventListener('click', e => {
            if (e.target.closest('#btnNext')) handleNext();
            if (e.target.closest('#btnPrev')) handlePrev();
        });
    }

    async function handleNext() {
        if (!validateStep(state.currentStep)) return;
        collectStepData();

        if (!state.uuid) {
            if (state.currentStep === 1) {
                const ok = await createPackage();
                if (!ok) return;
            }
        } else {
            await saveStep(state.currentStep);
        }

        if (state.currentStep < TOTAL_STEPS) {
            navigateToStep(state.currentStep + 1);
        }
        // Step 8 actions (Save / Finalize) are handled by buttons inside renderStep8
    }

    async function handlePrev() {
        if (state.currentStep > 1) {
            collectStepData();
            if (state.uuid) await saveStep(state.currentStep);
            navigateToStep(state.currentStep - 1);
        }
    }

    async function navigateToStep(n) {
        state.currentStep = n;
        showStep(n);
    }

    function showStep(n) {
        state.currentStep = n;
        renderStepIndicator();
        const fn = [null, renderStep1, renderStep2, renderStep3, renderStep4, renderStep5_itinerary, renderStep6_pricing, renderStep7, renderStep8][n];
        if (fn) fn();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // ─── Create Package ─────────────────────────────────────────
    async function createPackage() {
        showLoader('Creating package…');
        try {
            const res  = await fetch(`${BASE_API}/create.php`, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ title: state.steps[1].title, description: state.steps[1].description, rating: state.steps[1].rating })
            });
            const json = await res.json();
            if (!json.success) throw new Error(json.message);

            // ⚠️ Fixed: store both uuid and sys_id correctly
            state.uuid   = json.uuid;
            state.sys_id = json.sys_id;

            document.getElementById('builderSysId').textContent = json.sys_id;
            document.getElementById('builderTitle').textContent = state.steps[1].title || 'New Package';
            history.replaceState({}, '', `?uuid=${state.uuid}`);
            hideLoader();
            return true;
        } catch(e) {
            hideLoader();
            toast('error', e.message);
            return false;
        }
    }

    // ─── Save Step ───────────────────────────────────────────────
    async function saveStep(step, silent = false) {
        if (!state.uuid) return;
        if (!silent) showLoader('Saving…');
        try {
            const res  = await fetch(`${BASE_API}/step-save.php`, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ uuid: state.uuid, step_number: step, step_data: state.steps[step] })
            });
            const json = await res.json();
            if (!json.success) throw new Error(json.message);
            if (!silent) toast('success', 'Saved');
        } catch(e) {
            if (!silent) toast('error', e.message);
        }
        if (!silent) hideLoader();
    }

    // ─── Auto-save ───────────────────────────────────────────────
    function startAutoSave() {
        state.autoSaveTimer = setInterval(async () => {
            if (state.uuid && state.currentStep > 0 && state.currentStep < 8) {  // skip review step
                collectStepData();
                await saveStep(state.currentStep, true);
                updateAutoSaveStatus();
            }
        }, 30000);
    }

    function updateAutoSaveStatus() {
        const el = document.getElementById('autoSaveStatus');
        if (el) {
            const now = new Date().toLocaleTimeString('en-GB', {hour:'2-digit',minute:'2-digit'});
            el.innerHTML = `<i class="fa-regular fa-circle-check text-green-400"></i> Saved at ${now}`;
            setTimeout(() => { if(el) el.innerHTML = `<i class="fa-regular fa-clock"></i> Auto-save active`; }, 5000);
        }
    }

    // ─── Finalize ────────────────────────────────────────────────
    async function finalizePackage() {
        showLoader('Finalizing package…');
        try {
            const res  = await fetch(`${BASE_API}/step-save.php`, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ uuid: state.uuid, step_number: 8, step_data: {} })
            });
            const json = await res.json();
            hideLoader();
            if (json.success) {
                toast('success', 'Package completed!');
                setTimeout(() => window.location.href = 'index-packages.php', 1500);
            } else {
                toast('error', json.message);
            }
        } catch(e) {
            hideLoader();
            toast('error', e.message);
        }
    }

    // ─── Collect Step Data ───────────────────────────────────────
    function collectStepData() {
        const s   = state.currentStep;
        const get = id => { const el = document.getElementById(id); return el ? el.value : ''; };

        if (s === 1) {
            state.steps[1].title         = get('s1_title');
            state.steps[1].description   = get('s1_description');
            state.steps[1].rating        = parseInt(get('s1_rating_val') || 0);
        }
        if (s === 3) {
            state.steps[3].duration      = get('s3_duration');
            state.steps[3].start_date    = get('s3_start_date');
            state.steps[3].end_date      = get('s3_end_date');
            state.steps[3].no_of_pax    = {
                adult:  parseInt(get('s3_adult')  || 0),
                child:  parseInt(get('s3_child')  || 0),
                infant: parseInt(get('s3_infant') || 0),
            };
        }
        if (s === 6) {
            state.steps[6].currency_title     = get('s6_currency_title');
            state.steps[6].currency_code      = get('s6_currency_code');
            state.steps[6].currency_symbol    = get('s6_currency_symbol');
            state.steps[6].overall_price      = parseFloat(get('s6_overall_price') || 0);
            state.steps[6].air_ticket_details = get('s6_air_ticket');
        }
    }

    // ─── Validation ──────────────────────────────────────────────
    function validateStep(step) {
        if (step === 1) {
            const title = document.getElementById('s1_title')?.value?.trim();
            if (!title) { toast('error', 'Package title is required'); return false; }
        }
        return true;
    }

    // ═══════════════════════════════════════════════════════════
    // STEP RENDERERS
    // ═══════════════════════════════════════════════════════════

    function renderStep1() {
        const d = state.steps[1];
        document.getElementById('stepContent').innerHTML = `
        <h2 class="text-lg font-bold text-gray-800 mb-1">Basic Information</h2>
        <p class="text-sm text-gray-400 mb-6">Enter the core details of your travel package</p>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Package Title <span class="text-red-500">*</span></label>
                    <input id="s1_title" type="text" value="${escHtml(d.title)}" placeholder="e.g. Amazing Thailand 5D4N"
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Description</label>
                    <textarea id="s1_description" rows="4" placeholder="Describe this package…"
                              class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 resize-none">${escHtml(d.description)}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Rating</label>
                    <div class="flex items-center gap-2" id="starRating">
                        ${[1,2,3,4,5].map(n=>`
                        <button type="button" data-star="${n}"
                                class="text-2xl transition star-btn ${n <= d.rating ? 'text-amber-400' : 'text-gray-300 hover:text-amber-300'}">
                            <i class="fa-solid fa-star"></i>
                        </button>`).join('')}
                        <input type="hidden" id="s1_rating_val" value="${d.rating}">
                        <span id="ratingLabel" class="ml-2 text-sm text-gray-500">${d.rating > 0 ? d.rating + ' star' + (d.rating>1?'s':'') : 'No rating'}</span>
                    </div>
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Cover Image</label>
                <div id="imgDropZone"
                     class="relative border-2 border-dashed border-gray-200 rounded-2xl h-56 flex flex-col items-center justify-center cursor-pointer hover:border-blue-400 hover:bg-blue-50/30 transition overflow-hidden"
                     onclick="document.getElementById('imgFileInput').click()">
                    ${d.image
                        ? `<img id="imgPreview" src="../${d.image}" class="absolute inset-0 w-full h-full object-cover rounded-2xl">`
                        : `<div id="imgPlaceholder" class="text-center">
                               <i class="fa-solid fa-cloud-arrow-up text-3xl text-gray-300 mb-2 block"></i>
                               <p class="text-sm text-gray-400">Click or drag & drop</p>
                               <p class="text-xs text-gray-300 mt-0.5">JPG, PNG, WEBP — Max 5MB</p>
                           </div>`}
                    <input type="file" id="imgFileInput" accept="image/*" class="hidden">
                </div>
                ${d.image ? `<button id="removeImg" class="mt-2 text-xs text-red-500 hover:underline"><i class="fa-solid fa-trash mr-1"></i>Remove image</button>` : ''}
            </div>
        </div>`;

        document.querySelectorAll('.star-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const v = parseInt(btn.dataset.star);
                document.getElementById('s1_rating_val').value = v;
                document.getElementById('ratingLabel').textContent = v + ' star' + (v>1?'s':'');
                document.querySelectorAll('.star-btn').forEach(b => {
                    b.classList.toggle('text-amber-400', parseInt(b.dataset.star) <= v);
                    b.classList.toggle('text-gray-300',  parseInt(b.dataset.star) > v);
                });
            });
        });

        const fileInput = document.getElementById('imgFileInput');
        const dropZone  = document.getElementById('imgDropZone');
        if (fileInput) fileInput.addEventListener('change', e => handleImageUpload(e.target.files[0]));
        if (dropZone) {
            dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('border-blue-400'); });
            dropZone.addEventListener('drop',     e => { e.preventDefault(); handleImageUpload(e.dataTransfer.files[0]); });
        }
        document.getElementById('removeImg')?.addEventListener('click', e => {
            e.stopPropagation();
            state.steps[1].image = '';
            renderStep1();
        });
    }

    async function handleImageUpload(file) {
        if (!file) return;
        if (!state.uuid) { toast('error', 'Save basic info first (click Next once)'); return; }
        const formData = new FormData();
        formData.append('uuid',  state.uuid);
        formData.append('image', file);
        showLoader('Uploading image…');
        try {
            const res  = await fetch(`${BASE_API}/upload-image.php`, { method:'POST', body: formData });
            const json = await res.json();
            if (!json.success) throw new Error(json.message);
            state.steps[1].image = json.image;
            hideLoader();
            toast('success', 'Image uploaded');
            renderStep1();
        } catch(e) { hideLoader(); toast('error', e.message); }
    }

    function renderStep2() {
        const d = state.steps[2];
        document.getElementById('stepContent').innerHTML = `
        <h2 class="text-lg font-bold text-gray-800 mb-1">Destination</h2>
        <p class="text-sm text-gray-400 mb-6">Select countries, cities, and activities</p>
        <div class="space-y-6">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Countries</label>
                <div class="relative mb-2">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                    <input id="countrySearch" type="text" placeholder="Search country…"
                           class="w-full pl-8 pr-4 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
                <div id="countryList" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2 max-h-48 overflow-y-auto p-1"></div>
                <div id="selectedCountries" class="flex flex-wrap gap-2 mt-2"></div>
            </div>
            <div id="citiesSection" class="${d.countries.length ? '' : 'hidden'}">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Cities</label>
                <div id="cityList" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2 max-h-48 overflow-y-auto p-1"></div>
                <div id="selectedCities" class="flex flex-wrap gap-2 mt-2"></div>
            </div>
        </div>`;
        // Activities are now managed in Step 5 (Itinerary) — removed from Step 2

        renderCountryGrid();
        renderCityGrid();
        document.getElementById('countrySearch').addEventListener('input', e => renderCountryGrid(e.target.value));
        // Activities managed in Step 5 (Itinerary)
    }

    function renderCountryGrid(search = '') {
        const list     = document.getElementById('countryList');
        const sel      = document.getElementById('selectedCountries');
        const filtered = state.allCountries.filter(c => c.name.toLowerCase().includes(search.toLowerCase()));
        list.innerHTML = filtered.map(c => {
            const selected = state.steps[2].countries.find(x => x.id === c.id);
            return `<button type="button" data-cid="${c.id}"
                            class="country-btn text-xs px-3 py-2 rounded-xl border font-medium transition
                                   ${selected ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'}">
                        ${c.code} · ${c.name}</button>`;
        }).join('');
        list.querySelectorAll('.country-btn').forEach(btn =>
            btn.addEventListener('click', () => toggleCountry(parseInt(btn.dataset.cid)))
        );
        sel.innerHTML = state.steps[2].countries.map(c =>
            `<span class="inline-flex items-center gap-1 bg-blue-100 text-blue-800 text-xs px-3 py-1 rounded-full font-medium">
                ${c.name}
                <button data-cid="${c.id}" class="remove-country ml-1 text-blue-600 hover:text-blue-900">×</button>
             </span>`).join('');
        sel.querySelectorAll('.remove-country').forEach(b =>
            b.addEventListener('click', () => toggleCountry(parseInt(b.dataset.cid)))
        );
    }

    function toggleCountry(cid) {
        const country = state.allCountries.find(c => c.id === cid);
        if (!country) return;
        const idx = state.steps[2].countries.findIndex(c => c.id === cid);
        if (idx >= 0) {
            state.steps[2].countries.splice(idx, 1);
            // Remove cities belonging to this country — match by DB id or sys_id
            state.steps[2].cities = state.steps[2].cities.filter(city =>
                city.country_id !== cid && city.country_sys_id !== country.sys_id
            );
        } else {
            // Always store the full country object including sys_id
            state.steps[2].countries.push({ ...country });
        }
        renderCountryGrid(document.getElementById('countrySearch')?.value || '');
        renderCityGrid();
        document.getElementById('citiesSection')?.classList.toggle('hidden', !state.steps[2].countries.length);
    }

    function renderCityGrid() {
        // Match by DB integer id OR by country sys_id string (handles both DB and JSON fallback)
        const selectedCountries  = state.steps[2].countries;
        const selectedIds        = selectedCountries.map(c => c.id);
        const selectedSysIds     = selectedCountries.map(c => c.sys_id).filter(Boolean);
        const availableCities    = state.allCities.filter(c =>
            selectedIds.includes(c.country_id) || selectedSysIds.includes(c.country_sys_id)
        );
        console.log(selectedCountries, selectedIds, selectedSysIds, availableCities);
        const listEl = document.getElementById('cityList');
        const selEl  = document.getElementById('selectedCities');
        if (!listEl) return;
        listEl.innerHTML = availableCities.map(c => {
            const selected = state.steps[2].cities.find(x => String(x.id) === String(c.id));
            const country  = state.allCountries.find(x => x.id === c.country_id);
            return `<button type="button" data-cityid="${c.id}"
                            class="city-btn text-xs px-3 py-2 rounded-xl border font-medium transition
                                   ${selected ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'}">
                        ${c.name}<span class="opacity-60 ml-1">(${country?.code||''})</span></button>`;
        }).join('');
        listEl.querySelectorAll('.city-btn').forEach(btn =>
            btn.addEventListener('click', () => toggleCity(btn.dataset.cityid))
        );
        if (selEl) {
            selEl.innerHTML = state.steps[2].cities.map(c =>
                `<span class="inline-flex items-center gap-1 bg-emerald-100 text-emerald-800 text-xs px-3 py-1 rounded-full font-medium">
                    ${c.name}
                    <button data-cityid="${c.id}" class="remove-city ml-1 text-emerald-600 hover:text-emerald-900">×</button>
                 </span>`).join('');
            selEl.querySelectorAll('.remove-city').forEach(b =>
                b.addEventListener('click', () => toggleCity(b.dataset.cityid))
            );
        }
    }

    async function toggleCity(cityId) {
        // cityId is a string sys_id e.g. "THR-26-CNT-01-CTS-01"
        const city = state.allCities.find(c => String(c.id) === String(cityId));
        if (!city) return;
        const idx = state.steps[2].cities.findIndex(c => String(c.id) === String(cityId));
        if (idx >= 0) {
            state.steps[2].cities.splice(idx, 1);
        } else {
            state.steps[2].cities.push(city);
            // Fetch DB activity suggestions for this city (cached after first fetch)
            const sysId = city.sys_id || city.id;
            // activities fetched on-demand in Step 5 picker
        }
        renderCityGrid();
    }

    function renderStep3() {
        const d   = state.steps[3];
        const pax = d.no_of_pax || {};
        document.getElementById('stepContent').innerHTML = `
        <h2 class="text-lg font-bold text-gray-800 mb-1">Quotation Details</h2>
        <p class="text-sm text-gray-400 mb-6">Set duration, dates and passenger count</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Duration</label>
                    <input id="s3_duration" type="text" value="${escHtml(d.duration)}" placeholder="e.g. 5 Days 4 Nights"
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Start Date</label>
                    <input id="s3_start_date" type="date" value="${d.start_date||''}"
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">End Date</label>
                    <input id="s3_end_date" type="date" value="${d.end_date||''}"
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-3">Number of Passengers</label>
                <div class="space-y-3">
                    ${[['adult','Adult','fa-person'],['child','Child','fa-child'],['infant','Infant','fa-baby']].map(([key,label,icon])=>`
                    <div class="flex items-center justify-between bg-gray-50 rounded-xl px-4 py-3">
                        <div class="flex items-center gap-2 text-sm font-medium text-gray-700">
                            <i class="fa-solid ${icon} text-gray-400 w-5"></i> ${label}
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" data-pax="${key}" data-op="dec"
                                    class="w-8 h-8 rounded-full bg-white border border-gray-200 hover:bg-gray-100 text-gray-600 flex items-center justify-center font-bold transition">−</button>
                            <input id="s3_${key}" type="number" min="0" value="${pax[key]||0}"
                                   class="w-14 text-center border border-gray-200 rounded-lg py-1 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-blue-400">
                            <button type="button" data-pax="${key}" data-op="inc"
                                    class="w-8 h-8 rounded-full bg-white border border-gray-200 hover:bg-gray-100 text-gray-600 flex items-center justify-center font-bold transition">+</button>
                        </div>
                    </div>`).join('')}
                </div>
            </div>
        </div>`;
        document.querySelectorAll('[data-pax]').forEach(btn => {
            btn.addEventListener('click', () => {
                const key = btn.dataset.pax;
                const el  = document.getElementById(`s3_${key}`);
                let v = parseInt(el.value || 0);
                if (btn.dataset.op === 'inc') v++;
                else if (btn.dataset.op === 'dec' && v > 0) v--;
                el.value = v;
            });
        });
    }

    function renderStep4() {
        const cities = state.steps[2].cities;
        const sc = document.getElementById('stepContent');
        sc.innerHTML = `
        <h2 class="text-lg font-bold text-gray-800 mb-1">Accommodation</h2>
        <p class="text-sm text-gray-400 mb-6">Add hotels for each city</p>
        ${cities.length === 0
            ? `<div class="text-center py-12 text-gray-400">
                   <i class="fa-solid fa-city text-4xl mb-3 block opacity-30"></i>
                   <p>No cities selected. Go back to Step 2 to select cities.</p>
               </div>`
            : `<div class="space-y-5" id="hotelsContainer">${cities.map(city => renderCityHotels(city)).join('')}</div>`}`;

        if (!cities.length) return;

        // ── Single delegated listener on the whole container ─────────
        const container = document.getElementById('hotelsContainer');

        container.addEventListener('click', e => {
            // Add Hotel
            const addBtn = e.target.closest('.add-hotel-btn');
            if (addBtn) {
                const cityId = addBtn.dataset.cityId;  // keep as raw string
                const city   = state.steps[2].cities.find(c => String(c.id) === String(cityId));
                const hotel  = { city_id: cityId, city_name: city?.name||'', hotel_title:'' };
                state.steps[4].hotels.push(hotel);
                const list = document.getElementById(`hotelsList_${cityId}`);
                if (!list) { console.error('hotelsList not found for cityId:', cityId); return; }
                const idx  = state.steps[4].hotels.filter(h => String(h.city_id) === String(cityId)).length - 1;
                const div  = document.createElement('div');
                div.innerHTML = hotelRowHTML(cityId, idx);
                list.appendChild(div.firstElementChild);
                return;
            }
            // Remove Hotel
            const removeBtn = e.target.closest('.remove-hotel');
            if (removeBtn) {
                const row        = removeBtn.closest('.hotel-row');
                const cityId     = row.dataset.cityId;
                const idx        = parseInt(row.dataset.idx);
                const cityHotels = state.steps[4].hotels.filter(h => String(h.city_id) === String(cityId));
                const globalIdx  = state.steps[4].hotels.indexOf(cityHotels[idx]);
                if (globalIdx >= 0) state.steps[4].hotels.splice(globalIdx, 1);
                row.remove();
                return;
            }
        });

        // Input delegation — update state on typing
        container.addEventListener('input', e => {
            const inp = e.target.closest('.hotel-name');
            if (!inp) return;
            const row        = inp.closest('.hotel-row');
            const cityId     = row.dataset.cityId;
            const idx        = parseInt(row.dataset.idx);
            const cityHotels = state.steps[4].hotels.filter(h => String(h.city_id) === String(cityId));
            if (cityHotels[idx]) cityHotels[idx].hotel_title = inp.value;
        });
    }

    function renderCityHotels(city) {
        const cityId     = city.id;  // keep raw — may be sys_id string or integer
        const cityHotels = state.steps[4].hotels.filter(h => String(h.city_id) === String(cityId));
        return `
        <div class="border border-gray-200 rounded-2xl p-4">
            <div class="flex items-center gap-2 mb-3">
                <i class="fa-solid fa-location-dot text-blue-500"></i>
                <h3 class="font-semibold text-gray-700">${escHtml(city.name)}</h3>
            </div>
            <div class="space-y-2" id="hotelsList_${cityId}">
                ${cityHotels.map((h,i) => hotelRowHTML(cityId, i, h)).join('')}
            </div>
            <button type="button" data-city-id="${cityId}"
                    class="add-hotel-btn mt-3 text-sm text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1">
                <i class="fa-solid fa-plus-circle"></i> Add Hotel
            </button>
        </div>`;
    }

    function hotelRowHTML(cityId, idx, hotel = {}) {
        return `
        <div class="flex items-center gap-2 hotel-row" data-city-id="${cityId}" data-idx="${idx}">
            <i class="fa-solid fa-hotel text-gray-300 text-sm flex-shrink-0"></i>
            <input type="text" placeholder="Hotel name"
                   value="${escHtml(hotel?.hotel_title||'')}"
                   class="flex-1 px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 hotel-name">
            <button type="button" class="remove-hotel w-8 h-8 flex items-center justify-center rounded-xl bg-red-50 text-red-500 hover:bg-red-100 transition text-xs flex-shrink-0">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>`;
    }

    // ─── Step 5: Pricing ─────────────────────────────────────────
    function showCalcBanner() {
        const banner = document.getElementById('calcStatusBanner');
        if (!banner) return;
     
        const raw = state.calcDetails;
        if (!raw) return;
     
        // May be a parsed object (if get.php JSON-decoded it) or still a string
        const cd = (typeof raw === 'string') ? JSON.parse(raw) : raw;
        if (!cd || !cd.has_calculation) return;
     
        // ── Pull values directly from the stored JSON ──
        const grandTotal     = Number(cd.grand_total    || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const localCurrency  = cd.local_currency        || '';
        const sellingCurrency = cd.selling_currency     || 'BDT';
        const calcId         = cd.calculation_id        || '';
        const details        = cd.details              || {};  // { activity:[], hotel:[] }
        const actRows        = (details.activity       || []);
        const hotelRows      = (details.hotel          || []);
     
        // Activity subtotals from stored rows
        const actTotal    = actRows.reduce((s, r) => s + (parseFloat(r.sale_price_per_person)  || 0), 0);
        const hotelTotal  = hotelRows.reduce((s, r) => s + (parseFloat(r.sale_price_per_night_bdt) || 0), 0);
     
        // Build banner detail HTML
        const detailEl = document.getElementById('calcBannerDetail');
        detailEl.innerHTML = `
            <p class="text-xs text-green-600">
                <span class="font-semibold">Grand Total:</span>
                ৳${grandTotal} ${sellingCurrency}
                ${localCurrency ? `<span class="text-green-500 ml-1">(${localCurrency} → ${sellingCurrency})</span>` : ''}
            </p>
            ${actRows.length ? `
            <p class="text-xs text-green-600">
                <i class="fa-solid fa-person-hiking mr-1"></i>
                ${actRows.length} activity row(s)
                · Total: ৳${actTotal.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}
            </p>` : ''}
            ${hotelRows.length ? `
            <p class="text-xs text-green-600">
                <i class="fa-solid fa-hotel mr-1"></i>
                ${hotelRows.length} hotel row(s)
                · Total: ৳${hotelTotal.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}
            </p>` : ''}
            ${calcId ? `<p class="text-xs text-green-500 mt-0.5">Ref: ${calcId}</p>` : ''}
        `;
     
        // Set edit link
        document.getElementById('editCalcLink').href = `package-calculation.php?packageId=${state.sys_id}`;
     
        // Show the banner
        banner.classList.remove('hidden');
    }

    function renderStep6_pricing() {
        const d = state.steps[6];
        document.getElementById('stepContent').innerHTML = `
        <h2 class="text-lg font-bold text-gray-800 mb-1">Pricing</h2>
        <p class="text-sm text-gray-400 mb-6">Set currency, price, and pricing options</p>
     
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Currency Name</label>
                    <input id="s6_currency_title" type="text" value="${escHtml(d.currency_title)}" placeholder="e.g. US Dollar"
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Currency Code</label>
                        <input id="s6_currency_code" type="text" value="${escHtml(d.currency_code)}" placeholder="USD"
                               class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Symbol</label>
                        <input id="s6_currency_symbol" type="text" value="${escHtml(d.currency_symbol)}" placeholder="$"
                               class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Overall Price</label>
                    <input id="s6_overall_price" type="number" min="0" value="${d.overall_price||''}" placeholder="0.00"
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Air Ticket Details</label>
                <textarea id="s6_air_ticket" rows="6" placeholder="Flight details, airline, class…"
                          class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 resize-none">${escHtml(d.air_ticket_details)}</textarea>
            </div>
        </div>
     
        <!-- Pack Price Options -->
        <div class="mb-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-gray-700">Pricing Options</h3>
                <button id="addPriceOption"
                        class="text-sm bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl font-medium transition flex items-center gap-1">
                    <i class="fa-solid fa-plus text-xs"></i> Add Option
                </button>
            </div>
            <div id="priceOptionsList" class="space-y-3"></div>
        </div>
     
        <!-- Advanced Calculator -->
        <div class="border-t border-gray-100 pt-5">
     
            <!-- Banner — only visible when package_calculations_details exists -->
            <div id="calcStatusBanner"
                 class="hidden mb-4 flex items-start gap-3 bg-green-50 border border-green-200 rounded-xl px-4 py-3">
                <i class="fa-solid fa-circle-check text-green-500 text-lg flex-shrink-0 mt-0.5"></i>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-green-700">Advanced calculation saved</p>
                    <div id="calcBannerDetail" class="mt-1 space-y-0.5"></div>
                </div>
                <a id="editCalcLink" href="#"
                   class="flex-shrink-0 text-xs font-semibold text-green-700 underline hover:text-green-900 mt-0.5">
                    Edit
                </a>
            </div>
     
            <button id="openCalculatorBtn" type="button"
                    class="flex items-center gap-2 bg-violet-600 hover:bg-violet-700 text-white font-semibold px-5 py-2.5 rounded-xl shadow transition">
                <i class="fa-solid fa-calculator"></i> Open Advanced Calculator
            </button>
        </div>`;
     
        // Render pack price options
        renderPriceOptions();
     
        // ── Show calc banner from state.calcDetails (already in memory — no API call) ──
        showCalcBanner();
     
        // Event listeners
        document.getElementById('addPriceOption').addEventListener('click', () => {
            state.steps[6].pack_price.push({ id: Date.now(), title:'', price:'', hotels:{} });
            renderPriceOptions();
        });
     
        document.getElementById('openCalculatorBtn').addEventListener('click', () => {
            if (!state.sys_id) {
                toast('error', 'Save the package first (complete Step 1)');
                return;
            }
            window.location.href = `package-calculation.php?packageId=${state.sys_id}`;
        });
    }

    function renderPriceOptions() {
        const el = document.getElementById('priceOptionsList');
        if (!el) return;
        el.innerHTML = state.steps[6].pack_price.map((opt, i) => `
        <div class="border border-gray-200 rounded-xl p-4 price-opt" data-idx="${i}">
            <div class="flex items-center gap-3 mb-3">
                <input type="text" placeholder="Option title (e.g. Standard)" value="${escHtml(opt.title)}"
                       class="flex-1 px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 opt-title">
                <input type="number" placeholder="Price" value="${opt.price||''}"
                       class="w-32 px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 opt-price">
                <button type="button" data-idx="${i}"
                        class="remove-opt w-8 h-8 flex items-center justify-center rounded-xl bg-red-50 text-red-500 hover:bg-red-100 transition text-xs">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            ${state.steps[2].cities.length ? `
            <div class="text-xs font-medium text-gray-500 mb-2">Hotel per city:</div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                ${state.steps[2].cities.map(city => `
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500 w-24 truncate">${escHtml(city.name)}</span>
                    <select class="flex-1 px-2 py-1.5 border border-gray-200 rounded-lg text-xs focus:outline-none opt-hotel" data-city="${city.id}">
                        <option value="">-- Select hotel --</option>
                        ${state.steps[4].hotels.filter(h=>h.city_id===city.id).map(h=>
                            `<option value="${escHtml(h.hotel_title)}" ${(opt.hotels[city.id]===h.hotel_title)?'selected':''}>${escHtml(h.hotel_title)}</option>`).join('')}
                    </select>
                </div>`).join('')}
            </div>` : ''}
        </div>`).join('');

        el.querySelectorAll('.price-opt').forEach((row, i) => {
            row.querySelector('.opt-title').addEventListener('input', e => { state.steps[6].pack_price[i].title = e.target.value; });
            row.querySelector('.opt-price').addEventListener('input', e => { state.steps[6].pack_price[i].price = e.target.value; });
            row.querySelectorAll('.opt-hotel').forEach(sel => {
                sel.addEventListener('change', e => {
                    state.steps[6].pack_price[i].hotels[parseInt(sel.dataset.city)] = e.target.value;
                });
            });
        });
        el.querySelectorAll('.remove-opt').forEach(btn => {
            btn.addEventListener('click', () => {
                state.steps[6].pack_price.splice(parseInt(btn.dataset.idx), 1);
                renderPriceOptions();
            });
        });
    }

    function renderStep5_itinerary() {
        const days = state.steps[5].pack_itenaries;
        document.getElementById('stepContent').innerHTML = `
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-bold text-gray-800">Itinerary</h2>
                <p class="text-sm text-gray-400">Build your day-by-day travel plan with activities</p>
            </div>
            <button id="addDayBtn" class="flex items-center gap-1.5 text-sm bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl font-medium transition">
                <i class="fa-solid fa-plus text-xs"></i> Add Day
            </button>
        </div>
        <div id="itineraryDays" class="space-y-5"></div>`;

        renderItineraryDays();

        document.getElementById('addDayBtn').addEventListener('click', () => {
            const d = state.steps[5].pack_itenaries;
            const dayNum   = d.length + 1;
            const startDate = state.steps[3]?.start_date || '';
            let autoDate = '';
            if (startDate) {
                const base = new Date(startDate);
                base.setDate(base.getDate() + (dayNum - 1));
                autoDate = base.toISOString().split('T')[0];
            }
            d.push({
                day_number    : dayNum,
                title         : '',
                date          : autoDate,
                day_start_time: '08:00',
                day_hours     : 16,
                overnight_stay: '',
                meals         : [],
                activities    : [],
                transfers     : [],
                flights       : [],
            });
            renderItineraryDays();
        });
    }

    // ── Itinerary day renderer ────────────────────────────────────────
    function renderItineraryDays() {
        const el   = document.getElementById('itineraryDays');
        if (!el) return;
        const days = state.steps[5].pack_itenaries;
        const mealOptions = ['Breakfast','Lunch','Dinner','Snacks'];

        el.innerHTML = days.map((day, i) => {
            const usedHours  = (day.activities||[]).reduce((s,a)=>s+(parseFloat(a.duration_hours)||0),0);
            const limitHours = parseFloat(day.day_hours||16);
            const pct        = Math.min(100, Math.round((usedHours/limitHours)*100));
            const barColor   = pct >= 100 ? 'bg-red-500' : pct >= 80 ? 'bg-amber-400' : 'bg-green-500';

            return `
            <div class="border border-gray-200 rounded-2xl overflow-hidden" data-day-idx="${i}">
                <!-- Day header -->
                <div class="bg-gray-50 px-4 py-3 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="font-bold text-gray-700 text-sm">Day ${day.day_number}</span>
                        <div class="flex items-center gap-1.5">
                            <div class="h-1.5 w-24 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full ${barColor} rounded-full transition-all" style="width:${pct}%"></div>
                            </div>
                            <span class="text-xs ${pct>=100?'text-red-500 font-semibold':'text-gray-400'}">${usedHours}h / ${limitHours}h</span>
                        </div>
                    </div>
                    <button type="button" data-idx="${i}" class="remove-day text-red-400 hover:text-red-600 text-xs transition">
                        <i class="fa-solid fa-trash"></i> Remove
                    </button>
                </div>

                <!-- Day config -->
                <div class="p-4 grid grid-cols-2 md:grid-cols-4 gap-3 border-b border-gray-100">
                    <div class="col-span-2">
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Day Title</label>
                        <input type="text" placeholder="e.g. Arrival & City Tour" value="${escHtml(day.title)}"
                               class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 day-title" data-idx="${i}">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Date</label>
                        <input type="date" value="${day.date||''}"
                               class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 day-date" data-idx="${i}">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Overnight Stay</label>
                        <select class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 day-overnight" data-idx="${i}">
                            <option value="">-- Select city --</option>
                            ${state.steps[2].cities.map(c=>`<option value="${escHtml(c.name)}" ${day.overnight_stay===c.name?'selected':''}>${escHtml(c.name)}</option>`).join('')}
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Day Start Time</label>
                        <input type="time" value="${day.day_start_time||'08:00'}"
                               class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 day-start-time" data-idx="${i}">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Day Length (hours)</label>
                        <input type="number" min="1" max="24" step="0.5" value="${day.day_hours||16}"
                               class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 day-hours" data-idx="${i}">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Meals</label>
                        <div class="flex flex-wrap gap-3 pt-1">
                            ${mealOptions.map(m=>`
                            <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" value="${m}" class="day-meal rounded" data-idx="${i}" ${(day.meals||[]).includes(m)?'checked':''}>
                                <span class="text-xs text-gray-600">${m}</span>
                            </label>`).join('')}
                        </div>
                    </div>
                </div>

                <!-- Activity list for this day -->
                <div class="p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Activities & Transfers</h4>
                        <div class="flex gap-2 flex-wrap">
                            <button type="button" data-idx="${i}"
                                    class="btn-open-activity-picker text-xs px-3 py-1.5 rounded-lg border border-blue-400 text-blue-600 hover:bg-blue-50 transition flex items-center gap-1">
                                <i class="fa-solid fa-person-hiking text-xs"></i> Add Activity
                            </button>
                            <button type="button" data-idx="${i}"
                                    class="btn-add-standalone-transfer text-xs px-3 py-1.5 rounded-lg border border-orange-400 text-orange-600 hover:bg-orange-50 transition flex items-center gap-1">
                                <i class="fa-solid fa-van-shuttle text-xs"></i> Add Transfer
                            </button>
                            <button type="button" data-idx="${i}"
                                    class="btn-add-flight text-xs px-3 py-1.5 rounded-lg border border-sky-400 text-sky-600 hover:bg-sky-50 transition flex items-center gap-1">
                                <i class="fa-solid fa-plane text-xs"></i> Add Flight
                            </button>
                        </div>
                    </div>
                    <div class="day-activity-list space-y-2" data-day-idx="${i}" id="dayActList_${i}">
                        ${buildDayActivityList(day, i)}
                    </div>
                    <!-- Standalone transfers -->
                    <div class="day-transfer-list mt-2 space-y-2" id="dayTransList_${i}">
                        ${buildStandaloneTransferList(day, i)}
                    </div>
                    <!-- Flights -->
                    <div class="day-flight-list mt-2 space-y-2" id="dayFlightList_${i}">
                        ${buildFlightList(day, i)}
                    </div>
                </div>
            </div>`;
        }).join('');

        // ── Bind all events ──────────────────────────────────────────
        el.querySelectorAll('.day-title').forEach(inp =>
            inp.addEventListener('input', e => { days[+e.target.dataset.idx].title = e.target.value; })
        );
        el.querySelectorAll('.day-date').forEach(inp =>
            inp.addEventListener('input', e => { days[+e.target.dataset.idx].date = e.target.value; })
        );
        el.querySelectorAll('.day-start-time').forEach(inp =>
            inp.addEventListener('input', e => { days[+e.target.dataset.idx].day_start_time = e.target.value; })
        );
        el.querySelectorAll('.day-hours').forEach(inp =>
            inp.addEventListener('input', e => { days[+e.target.dataset.idx].day_hours = parseFloat(e.target.value)||16; renderItineraryDays(); })
        );
        el.querySelectorAll('.day-overnight').forEach(sel =>
            sel.addEventListener('change', e => { days[+e.target.dataset.idx].overnight_stay = e.target.value; })
        );
        el.querySelectorAll('.day-meal').forEach(cb =>
            cb.addEventListener('change', e => {
                const d = days[+cb.dataset.idx];
                if (!d.meals) d.meals = [];
                if (cb.checked && !d.meals.includes(cb.value)) d.meals.push(cb.value);
                else d.meals = d.meals.filter(m => m !== cb.value);
            })
        );
        el.querySelectorAll('.remove-day').forEach(btn =>
            btn.addEventListener('click', () => {
                days.splice(+btn.dataset.idx, 1);
                days.forEach((d,i) => d.day_number = i+1);
                renderItineraryDays();
            })
        );

        // Activity picker open
        el.querySelectorAll('.btn-open-activity-picker').forEach(btn =>
            btn.addEventListener('click', () => openActivityPicker(+btn.dataset.idx))
        );

        // Standalone transfer add
        el.querySelectorAll('.btn-add-standalone-transfer').forEach(btn =>
            btn.addEventListener('click', () => {
                const di = +btn.dataset.idx;
                if (!days[di].transfers) days[di].transfers = [];
                days[di].transfers.push({ title:'', type:'sic', notes:'', start_time:'', end_time:'', pricing:[] });
                const listEl = document.getElementById(`dayTransList_${di}`);
                if (listEl) {
                    listEl.innerHTML = buildStandaloneTransferList(days[di], di);
                    bindStandaloneTransferEvents(days);
                }
            })
        );

        // Flight add
        el.querySelectorAll('.btn-add-flight').forEach(btn =>
            btn.addEventListener('click', () => {
                const di = +btn.dataset.idx;
                if (!days[di].flights) days[di].flights = [];
                days[di].flights.push({
                    flight_number : '',
                    dep_airport   : '',
                    dep_date      : days[di].date || '',
                    dep_time      : '',
                    arr_airport   : '',
                    arr_date      : days[di].date || '',
                    arr_time      : '',
                });
                const listEl = document.getElementById(`dayFlightList_${di}`);
                if (listEl) {
                    const div = document.createElement('div');
                    const fi  = days[di].flights.length - 1;
                    div.innerHTML = buildFlightCard(days[di].flights[fi], di, fi);
                    const card = div.firstElementChild;
                    listEl.appendChild(card);
                    bindFlightCardEvents(card, days, di, fi);
                }
            })
        );

        // Activity card events (edit fields + remove + drag)
        bindActivityCardEvents(days);
        // Standalone transfer events
        bindStandaloneTransferEvents(days);
        // Flight events
        bindAllFlightEvents(days);
    }

    // ── Build activity cards for a day ──────────────────────────────
    const CAR_TYPES_LIST = ['sedan','van','suv','minibus','microbus','coaster','bus','other'];

    function buildTransferRows(act, dayIdx, ai) {
        const transfers = act.transfers || [];
        return transfers.map((tr, ti) => {
            const isSIC = (tr.type || 'sic') === 'sic';
            return `
            <div class="transfer-row border border-gray-100 rounded-xl overflow-hidden mb-2" data-tr-idx="${ti}">
                <!-- Transfer header -->
                <div class="flex items-center gap-2 px-3 py-2 bg-gray-50 border-b border-gray-100">
                    <i class="fa-solid fa-van-shuttle text-gray-400 text-xs"></i>
                    <input type="text" value="${escHtml(tr.title||'')}" placeholder="Transfer title (e.g. Airport → Hotel)"
                           class="flex-1 text-xs border border-gray-200 rounded-lg px-2 py-1 bg-white focus:outline-none focus:ring-1 focus:ring-blue-400 tr-title"
                           data-day-idx="${dayIdx}" data-act-idx="${ai}" data-tr-idx="${ti}">
                    <select class="text-xs border border-gray-200 rounded-lg px-2 py-1 bg-white focus:outline-none tr-type"
                            data-day-idx="${dayIdx}" data-act-idx="${ai}" data-tr-idx="${ti}">
                        <option value="sic"     ${isSIC    ?'selected':''}>SIC</option>
                        <option value="private" ${!isSIC   ?'selected':''}>Private</option>
                    </select>
                    <button type="button" class="btn-remove-transfer text-red-400 hover:text-red-600 text-xs px-1"
                            data-day-idx="${dayIdx}" data-act-idx="${ai}" data-tr-idx="${ti}" title="Remove transfer">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
                <!-- Pricing rows -->
                <div class="px-3 py-2 space-y-1.5 transfer-pricing-list">
                    <div class="grid grid-cols-6 gap-1 text-xs text-gray-400 font-medium px-1">
                        <span class="col-span-2">Car Name</span><span>Type</span><span>Adult</span><span>Child</span><span>Full</span>
                    </div>
                    ${(tr.pricing||[]).map((p, pi) => buildPricingRow(p, dayIdx, ai, ti, pi, isSIC)).join('')}
                </div>
                <!-- Add car row -->
                <div class="px-3 pb-2">
                    <button type="button" class="btn-add-car-row text-xs px-2 py-1 rounded-lg border border-dashed border-gray-300 text-gray-500 hover:border-blue-400 hover:text-blue-600 transition"
                            data-day-idx="${dayIdx}" data-act-idx="${ai}" data-tr-idx="${ti}">
                        <i class="fa-solid fa-plus mr-1"></i> Add Car
                    </button>
                </div>
            </div>`;
        }).join('');
    }

    function buildPricingRow(p, dayIdx, ai, ti, pi, isSIC, standalone = false) {
        return `
        <div class="pricing-row grid grid-cols-6 gap-1 items-center bg-blue-50 rounded-lg px-1 py-1.5"
             data-day-idx="${dayIdx}" data-act-idx="${ai}" data-tr-idx="${ti}" data-pr-idx="${pi}">
            <input type="text" value="${escHtml(p.car_name||'')}" placeholder="Car name"
                   class="col-span-2 px-1.5 py-1 text-xs border border-gray-200 rounded bg-white focus:outline-none pr-car-name"
                   data-day-idx="${dayIdx}" data-act-idx="${ai}" data-tr="${ti}" data-pr="${pi}">
            <select class="px-1 py-1 text-xs border border-gray-200 rounded bg-white focus:outline-none pr-car-type"
                    data-day-idx="${dayIdx}" data-act-idx="${ai}" data-tr="${ti}" data-pr="${pi}">
                ${CAR_TYPES_LIST.map(t=>`<option value="${t}" ${(p.car_type||'sedan')===t?'selected':''}>${t}</option>`).join('')}
            </select>
            <input type="number" min="0" step="0.01" value="${p.price_adult??''}" placeholder="0"
                   class="px-1.5 py-1 text-xs border border-gray-200 rounded bg-white focus:outline-none pr-adult ${!isSIC?'opacity-30 pointer-events-none':''}"
                   data-day-idx="${dayIdx}" data-act-idx="${ai}" data-tr="${ti}" data-pr="${pi}">
            <input type="number" min="0" step="0.01" value="${p.price_child??''}" placeholder="0"
                   class="px-1.5 py-1 text-xs border border-gray-200 rounded bg-white focus:outline-none pr-child ${!isSIC?'opacity-30 pointer-events-none':''}"
                   data-day-idx="${dayIdx}" data-act-idx="${ai}" data-tr="${ti}" data-pr="${pi}">
            <input type="number" min="0" step="0.01" value="${p.price_full??''}" placeholder="0"
                   class="px-1.5 py-1 text-xs border border-gray-200 rounded bg-white focus:outline-none pr-full ${isSIC?'opacity-30 pointer-events-none':''}"
                   data-day-idx="${dayIdx}" data-act-idx="${ai}" data-tr="${ti}" data-pr="${pi}">
        </div>`;
    }

    function buildDayActivityList(day, dayIdx) {
        const acts = day.activities || [];
        if (!acts.length) return `<p class="text-xs text-gray-400 italic py-2">No activities added yet. Click "Add Activity" to search.</p>`;

        return acts.map((act, ai) => {
            const typeColors = { tour:'bg-blue-50 text-blue-700', transfer:'bg-violet-50 text-violet-700', both:'bg-teal-50 text-teal-700' };
            const tc = typeColors[act.type] || 'bg-gray-100 text-gray-600';

            return `
            <div class="activity-card border border-gray-200 rounded-xl bg-white overflow-hidden"
                 draggable="true" data-day-idx="${dayIdx}" data-act-idx="${ai}">

                <!-- Card header: drag + name + type badge + duration + remove -->
                <div class="flex items-center gap-2 px-3 py-2.5 bg-gray-50 border-b border-gray-100">
                    <i class="fa-solid fa-grip-lines text-gray-300 cursor-grab active:cursor-grabbing" style="font-size:12px"></i>
                    <span class="flex-1 text-sm font-semibold text-gray-700">${escHtml(act.name)}</span>
                    <span class="text-xs px-2 py-0.5 rounded-full ${tc}">${act.type||'tour'}</span>
                    <span class="text-xs font-mono text-gray-400 act-dur-display">${act.duration_hours||0}h</span>
                    <button type="button" data-day-idx="${dayIdx}" data-act-idx="${ai}"
                            class="btn-remove-act text-red-400 hover:text-red-600 ml-1" title="Remove activity">
                        <i class="fa-solid fa-xmark text-xs"></i>
                    </button>
                </div>

                <!-- Editable fields: time, duration, location, note -->
                <div class="p-3 grid grid-cols-2 md:grid-cols-4 gap-2 border-b border-gray-50">
                    <div>
                        <label class="block text-xs text-gray-400 mb-0.5">Start Time</label>
                        <input type="time" value="${act.start_time||''}"
                               class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-400 act-start-time"
                               data-day-idx="${dayIdx}" data-act-idx="${ai}">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 mb-0.5">End Time</label>
                        <input type="time" value="${act.end_time||''}"
                               class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-400 act-end-time"
                               data-day-idx="${dayIdx}" data-act-idx="${ai}">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 mb-0.5">Duration (hrs)</label>
                        <input type="number" min="0" step="0.5" value="${act.duration_hours||0}"
                               class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-400 act-duration bg-gray-50"
                               data-day-idx="${dayIdx}" data-act-idx="${ai}" readonly>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 mb-0.5">Location</label>
                        <input type="text" value="${escHtml(act.location||'')}" placeholder="Venue"
                               class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-400 act-location"
                               data-day-idx="${dayIdx}" data-act-idx="${ai}">
                    </div>
                    <div class="col-span-2 md:col-span-4">
                        <label class="block text-xs text-gray-400 mb-0.5">Note</label>
                        <input type="text" value="${escHtml(act.note||'')}" placeholder="Optional note"
                               class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-400 act-note"
                               data-day-idx="${dayIdx}" data-act-idx="${ai}">
                    </div>
                </div>

                <!-- Transfers section -->
                <div class="p-3">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Transfers</span>
                        <button type="button" class="btn-add-transfer text-xs px-2 py-1 rounded-lg border border-orange-300 text-orange-600 hover:bg-orange-50 transition"
                                data-day-idx="${dayIdx}" data-act-idx="${ai}">
                            <i class="fa-solid fa-plus mr-1"></i> Add Transfer
                        </button>
                    </div>
                    <div class="transfer-list" id="transferList_${dayIdx}_${ai}">
                        ${buildTransferRows(act, dayIdx, ai)}
                        ${!(act.transfers||[]).length ? '<p class="text-xs text-gray-400 italic">No transfers. Click Add Transfer to add one.</p>' : ''}
                    </div>
                </div>

            </div>`;
        }).join('');
    }

    // ── Standalone Transfer list (parent-level, not child of activity) ──
    function buildStandaloneTransferList(day, dayIdx) {
        const transfers = day.transfers || [];
        if (!transfers.length) return '';
        return transfers.map((tr, ti) => {
            const isSIC = (tr.type || 'sic') === 'sic';
            return `
            <div class="standalone-transfer-card border border-orange-200 rounded-xl bg-orange-50/30 overflow-hidden cursor-grab active:cursor-grabbing"
                 draggable="true" data-day-idx="${dayIdx}" data-tr-idx="${ti}">
                <!-- Header -->
                <div class="flex items-center gap-2 px-3 py-2.5 bg-orange-50 border-b border-orange-100">
                    <i class="fa-solid fa-grip-lines text-orange-300 cursor-grab" style="font-size:11px"></i>
                    <i class="fa-solid fa-van-shuttle text-orange-400 text-xs"></i>
                    <input type="text" value="${escHtml(tr.title||'')}" placeholder="Transfer title (e.g. Airport → Hotel)"
                           class="flex-1 text-sm font-semibold bg-transparent border-none outline-none text-orange-800 placeholder:text-orange-300 st-title"
                           data-day-idx="${dayIdx}" data-tr-idx="${ti}">
                    <select class="text-xs border border-orange-200 rounded-lg px-2 py-1 bg-white focus:outline-none st-type"
                            data-day-idx="${dayIdx}" data-tr-idx="${ti}">
                        <option value="sic"     ${isSIC    ?'selected':''}>SIC</option>
                        <option value="private" ${!isSIC   ?'selected':''}>Private</option>
                    </select>
                    <button type="button" class="btn-remove-st text-red-400 hover:text-red-600 ml-1"
                            data-day-idx="${dayIdx}" data-tr-idx="${ti}" title="Remove transfer">
                        <i class="fa-solid fa-xmark text-xs"></i>
                    </button>
                </div>
                <!-- Time + note -->
                <div class="px-3 py-2 grid grid-cols-3 gap-2">
                    <div>
                        <label class="block text-xs text-gray-400 mb-0.5">Start Time</label>
                        <input type="time" value="${tr.start_time||''}"
                               class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-orange-400 st-start-time"
                               data-day-idx="${dayIdx}" data-tr-idx="${ti}">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 mb-0.5">End Time</label>
                        <input type="time" value="${tr.end_time||''}"
                               class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-orange-400 st-end-time"
                               data-day-idx="${dayIdx}" data-tr-idx="${ti}">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 mb-0.5">Note</label>
                        <input type="text" value="${escHtml(tr.notes||'')}" placeholder="Optional note"
                               class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-orange-400 st-notes"
                               data-day-idx="${dayIdx}" data-tr-idx="${ti}">
                    </div>
                </div>
                <!-- Car pricing rows -->
                <div class="px-3 pb-2">
                    <div class="grid grid-cols-6 gap-1 text-xs text-gray-400 font-medium px-1 mb-1">
                        <span class="col-span-2">Car Name</span><span>Type</span><span>Adult</span><span>Child</span><span>Full</span>
                    </div>
                    <div class="st-pricing-list space-y-1.5" id="stPricingList_${dayIdx}_${ti}">
                        ${(tr.pricing||[]).map((p, pi) => buildPricingRow(p, dayIdx, -1, ti, pi, isSIC, true)).join('')}
                    </div>
                    <button type="button" class="btn-add-st-car mt-1.5 text-xs px-2 py-1 rounded-lg border border-dashed border-orange-300 text-orange-500 hover:border-orange-500 hover:text-orange-700 transition"
                            data-day-idx="${dayIdx}" data-tr-idx="${ti}">
                        <i class="fa-solid fa-plus mr-1"></i> Add Car
                    </button>
                </div>
            </div>`;
        }).join('');
    }

    // Bind inputs on a single pricing row (avoids full re-render)
    function bindPricingRowInputs(row, days, di, ai, ti, pi, standalone = false) {
        const getAct  = () => standalone ? days[di].transfers[ti] : days[di].activities[ai].transfers[ti];
        row.querySelector('.pr-car-name')?.addEventListener('input', e => {
            const p = getAct().pricing[pi]; if (p) p.car_name = e.target.value;
        });
        row.querySelector('.pr-car-type')?.addEventListener('change', e => {
            const p = getAct().pricing[pi]; if (p) p.car_type = e.target.value;
        });
        row.querySelector('.pr-adult')?.addEventListener('input', e => {
            const p = getAct().pricing[pi]; if (p) p.price_adult = parseFloat(e.target.value)||null;
        });
        row.querySelector('.pr-child')?.addEventListener('input', e => {
            const p = getAct().pricing[pi]; if (p) p.price_child = parseFloat(e.target.value)||null;
        });
        row.querySelector('.pr-full')?.addEventListener('input', e => {
            const p = getAct().pricing[pi]; if (p) p.price_full = parseFloat(e.target.value)||null;
        });
    }

        // ── Flight list builder ──────────────────────────────────────────
    function buildFlightList(day, dayIdx) {
        const flights = day.flights || [];
        if (!flights.length) return '';
        return flights.map((f, fi) => buildFlightCard(f, dayIdx, fi)).join('');
    }

    function buildTransitLeg(leg, dayIdx, fi, ti) {
        return `
        <div class="transit-leg border-t border-sky-100 pt-2 mt-2" data-tr-leg="${ti}">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-semibold text-amber-600 uppercase tracking-wide flex items-center gap-1">
                    <i class="fa-solid fa-arrows-turn-right text-amber-400" style="font-size:10px"></i>
                    Transit ${ti + 1}
                </p>
                <button type="button" class="btn-remove-transit text-red-400 hover:text-red-600 text-xs"
                        data-day-idx="${dayIdx}" data-fl-idx="${fi}" data-ti="${ti}" title="Remove transit">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                    <p class="text-xs text-gray-400 flex items-center gap-1">
                        <i class="fa-solid fa-plane-departure text-amber-400" style="font-size:10px"></i> Departure
                    </p>
                    <input type="text" value="${escHtml(leg.dep_airport||'')}" placeholder="Airport"
                           class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-amber-400 tr-dep-airport"
                           data-day-idx="${dayIdx}" data-fl-idx="${fi}" data-ti="${ti}">
                    <div class="grid grid-cols-2 gap-2">
                        <input type="date" value="${leg.dep_date||''}"
                               class="px-2 py-1.5 text-xs border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-amber-400 tr-dep-date"
                               data-day-idx="${dayIdx}" data-fl-idx="${fi}" data-ti="${ti}">
                        <input type="time" value="${leg.dep_time||''}"
                               class="px-2 py-1.5 text-xs border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-amber-400 tr-dep-time"
                               data-day-idx="${dayIdx}" data-fl-idx="${fi}" data-ti="${ti}">
                    </div>
                </div>
                <div class="space-y-2">
                    <p class="text-xs text-gray-400 flex items-center gap-1">
                        <i class="fa-solid fa-plane-arrival text-amber-400" style="font-size:10px"></i> Arrival
                    </p>
                    <input type="text" value="${escHtml(leg.arr_airport||'')}" placeholder="Airport"
                           class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-amber-400 tr-arr-airport"
                           data-day-idx="${dayIdx}" data-fl-idx="${fi}" data-ti="${ti}">
                    <div class="grid grid-cols-2 gap-2">
                        <input type="date" value="${leg.arr_date||''}"
                               class="px-2 py-1.5 text-xs border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-amber-400 tr-arr-date"
                               data-day-idx="${dayIdx}" data-fl-idx="${fi}" data-ti="${ti}">
                        <input type="time" value="${leg.arr_time||''}"
                               class="px-2 py-1.5 text-xs border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-amber-400 tr-arr-time"
                               data-day-idx="${dayIdx}" data-fl-idx="${fi}" data-ti="${ti}">
                    </div>
                </div>
            </div>
        </div>`;
    }

    function buildTransitLeg(leg, dayIdx, fi, ti) {
        return `
        <div class="transit-leg border border-amber-200 rounded-xl bg-amber-50/40 overflow-hidden mb-2"
             data-day-idx="${dayIdx}" data-fl-idx="${fi}" data-tr-idx="${ti}">
            <div class="flex items-center gap-2 px-3 py-2 bg-amber-50 border-b border-amber-100">
                <i class="fa-solid fa-arrows-turn-right text-amber-500" style="font-size:10px"></i>
                <span class="flex-1 text-xs font-semibold text-amber-700">Transit ${ti + 1}</span>
                <button type="button" class="btn-remove-transit text-red-400 hover:text-red-600"
                        data-day-idx="${dayIdx}" data-fl-idx="${fi}" data-tr-idx="${ti}">
                    <i class="fa-solid fa-xmark text-xs"></i>
                </button>
            </div>
            <div class="p-3 grid grid-cols-2 gap-4">
                <!-- Transit Departure -->
                <div class="space-y-2">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide flex items-center gap-1">
                        <i class="fa-solid fa-plane-departure text-amber-400" style="font-size:10px"></i> Dep
                    </p>
                    <input type="text" value="${escHtml(leg.dep_airport||'')}" placeholder="Airport"
                           class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-amber-400 tr-dep-airport"
                           data-day-idx="${dayIdx}" data-fl-idx="${fi}" data-tr-idx="${ti}">
                    <div class="grid grid-cols-2 gap-2">
                        <input type="date" value="${leg.dep_date||''}"
                               class="px-2 py-1.5 text-xs border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-amber-400 tr-dep-date"
                               data-day-idx="${dayIdx}" data-fl-idx="${fi}" data-tr-idx="${ti}">
                        <input type="time" value="${leg.dep_time||''}"
                               class="px-2 py-1.5 text-xs border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-amber-400 tr-dep-time"
                               data-day-idx="${dayIdx}" data-fl-idx="${fi}" data-tr-idx="${ti}">
                    </div>
                </div>
                <!-- Transit Arrival -->
                <div class="space-y-2">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide flex items-center gap-1">
                        <i class="fa-solid fa-plane-arrival text-amber-400" style="font-size:10px"></i> Arr
                    </p>
                    <input type="text" value="${escHtml(leg.arr_airport||'')}" placeholder="Airport"
                           class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-amber-400 tr-arr-airport"
                           data-day-idx="${dayIdx}" data-fl-idx="${fi}" data-tr-idx="${ti}">
                    <div class="grid grid-cols-2 gap-2">
                        <input type="date" value="${leg.arr_date||''}"
                               class="px-2 py-1.5 text-xs border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-amber-400 tr-arr-date"
                               data-day-idx="${dayIdx}" data-fl-idx="${fi}" data-tr-idx="${ti}">
                        <input type="time" value="${leg.arr_time||''}"
                               class="px-2 py-1.5 text-xs border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-amber-400 tr-arr-time"
                               data-day-idx="${dayIdx}" data-fl-idx="${fi}" data-tr-idx="${ti}">
                    </div>
                </div>
            </div>
        </div>`;
    }

        function buildFlightCard(f, dayIdx, fi) {
        const transits = f.transits || [];
        return `
        <div class="flight-card border border-sky-200 rounded-xl bg-sky-50/30 overflow-hidden cursor-grab active:cursor-grabbing"
             draggable="true" data-day-idx="${dayIdx}" data-fl-idx="${fi}">

            <!-- Header -->
            <div class="flex items-center gap-2 px-3 py-2.5 bg-sky-50 border-b border-sky-100">
                <i class="fa-solid fa-grip-lines text-sky-300 cursor-grab" style="font-size:11px"></i>
                <i class="fa-solid fa-plane text-sky-500 text-xs"></i>
                <span class="text-xs font-semibold text-sky-700 uppercase tracking-wide">Flight</span>
                <input type="text" value="${escHtml(f.flight_number||'')}" placeholder="Flight no. (e.g. BG-001)"
                       class="flex-1 text-sm font-bold bg-transparent border-none outline-none text-sky-800 placeholder:text-sky-300 fl-number"
                       data-day-idx="${dayIdx}" data-fl-idx="${fi}">
                <button type="button" class="btn-remove-flight text-red-400 hover:text-red-600 ml-1"
                        data-day-idx="${dayIdx}" data-fl-idx="${fi}" title="Remove flight">
                    <i class="fa-solid fa-xmark text-xs"></i>
                </button>
            </div>

            <!-- Departure + Arrival -->
            <div class="p-3">
                <div class="grid grid-cols-2 gap-4 mb-3">
                    <!-- Departure -->
                    <div class="space-y-2">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide flex items-center gap-1">
                            <i class="fa-solid fa-plane-departure text-sky-400" style="font-size:10px"></i> Departure
                        </p>
                        <input type="text" value="${escHtml(f.dep_airport||'')}" placeholder="e.g. DAC / Hazrat Shahjalal"
                               class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-sky-400 fl-dep-airport"
                               data-day-idx="${dayIdx}" data-fl-idx="${fi}">
                        <div class="grid grid-cols-2 gap-2">
                            <input type="date" value="${f.dep_date||''}"
                                   class="px-2 py-1.5 text-xs border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-sky-400 fl-dep-date"
                                   data-day-idx="${dayIdx}" data-fl-idx="${fi}">
                            <input type="time" value="${f.dep_time||''}"
                                   class="px-2 py-1.5 text-xs border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-sky-400 fl-dep-time"
                                   data-day-idx="${dayIdx}" data-fl-idx="${fi}">
                        </div>
                    </div>
                    <!-- Arrival -->
                    <div class="space-y-2">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide flex items-center gap-1">
                            <i class="fa-solid fa-plane-arrival text-sky-400" style="font-size:10px"></i> Arrival
                        </p>
                        <input type="text" value="${escHtml(f.arr_airport||'')}" placeholder="e.g. BKK / Suvarnabhumi"
                               class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-sky-400 fl-arr-airport"
                               data-day-idx="${dayIdx}" data-fl-idx="${fi}">
                        <div class="grid grid-cols-2 gap-2">
                            <input type="date" value="${f.arr_date||''}"
                                   class="px-2 py-1.5 text-xs border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-sky-400 fl-arr-date"
                                   data-day-idx="${dayIdx}" data-fl-idx="${fi}">
                            <input type="time" value="${f.arr_time||''}"
                                   class="px-2 py-1.5 text-xs border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-sky-400 fl-arr-time"
                                   data-day-idx="${dayIdx}" data-fl-idx="${fi}">
                        </div>
                    </div>
                </div>

                <!-- Transit legs -->
                <div class="transit-legs-container">
                    ${transits.map((leg, ti) => buildTransitLeg(leg, dayIdx, fi, ti)).join('')}
                </div>

                <!-- Add Transit button -->
                <button type="button" class="btn-add-transit mt-2 text-xs px-3 py-1.5 rounded-lg border border-amber-300 text-amber-600 hover:bg-amber-50 transition flex items-center gap-1"
                        data-day-idx="${dayIdx}" data-fl-idx="${fi}">
                    <i class="fa-solid fa-arrows-turn-right text-xs"></i> Add Transit
                </button>
            </div>
        </div>`;
    }

    function bindFlightCardEvents(card, days, di, fi) {
        const get = () => (days[di].flights || [])[fi];

        // Main flight fields
        card.querySelector('.fl-number')?.addEventListener('input',     e => { const f=get(); if(f) f.flight_number = e.target.value; });
        card.querySelector('.fl-dep-airport')?.addEventListener('input', e => { const f=get(); if(f) f.dep_airport   = e.target.value; });
        card.querySelector('.fl-dep-date')?.addEventListener('change',   e => { const f=get(); if(f) f.dep_date      = e.target.value; });
        card.querySelector('.fl-dep-time')?.addEventListener('change',   e => { const f=get(); if(f) f.dep_time      = e.target.value; });
        card.querySelector('.fl-arr-airport')?.addEventListener('input', e => { const f=get(); if(f) f.arr_airport   = e.target.value; });
        card.querySelector('.fl-arr-date')?.addEventListener('change',   e => { const f=get(); if(f) f.arr_date      = e.target.value; });
        card.querySelector('.fl-arr-time')?.addEventListener('change',   e => { const f=get(); if(f) f.arr_time      = e.target.value; });

        // Remove flight
        card.querySelector('.btn-remove-flight')?.addEventListener('click', () => {
            days[di].flights.splice(fi, 1);
            const listEl = document.getElementById(`dayFlightList_${di}`);
            if (listEl) { listEl.innerHTML = buildFlightList(days[di], di); bindAllFlightEvents(days); }
        });

        // Add Transit
        card.querySelector('.btn-add-transit')?.addEventListener('click', () => {
            const f = get(); if (!f) return;
            if (!f.transits) f.transits = [];
            const ti     = f.transits.length;
            const newLeg = { dep_airport:'', dep_date:'', dep_time:'', arr_airport:'', arr_date:'', arr_time:'' };
            f.transits.push(newLeg);
            const container = card.querySelector('.transit-legs-container');
            if (container) {
                const div = document.createElement('div');
                div.innerHTML = buildTransitLeg(newLeg, di, fi, ti);
                const leg = div.firstElementChild;
                container.appendChild(leg);
                bindTransitLegEvents(leg, days, di, fi, ti);
            }
        });

        // Bind existing transit legs on load
        card.querySelectorAll('.transit-leg').forEach(leg => {
            const ti = +leg.dataset.trIdx;
            bindTransitLegEvents(leg, days, di, fi, ti);
        });
    }

    function bindTransitLegEvents(leg, days, di, fi, ti) {
        const getTr = () => ((days[di].flights||[])[fi]?.transits||[])[ti];
        leg.querySelector('.tr-dep-airport')?.addEventListener('input',  e => { const t=getTr(); if(t) t.dep_airport = e.target.value; });
        leg.querySelector('.tr-dep-date')?.addEventListener('change',    e => { const t=getTr(); if(t) t.dep_date    = e.target.value; });
        leg.querySelector('.tr-dep-time')?.addEventListener('change',    e => { const t=getTr(); if(t) t.dep_time    = e.target.value; });
        leg.querySelector('.tr-arr-airport')?.addEventListener('input',  e => { const t=getTr(); if(t) t.arr_airport = e.target.value; });
        leg.querySelector('.tr-arr-date')?.addEventListener('change',    e => { const t=getTr(); if(t) t.arr_date    = e.target.value; });
        leg.querySelector('.tr-arr-time')?.addEventListener('change',    e => { const t=getTr(); if(t) t.arr_time    = e.target.value; });
        leg.querySelector('.btn-remove-transit')?.addEventListener('click', () => {
            const f = (days[di].flights||[])[fi]; if (!f) return;
            f.transits.splice(ti, 1);
            const listEl = document.getElementById(`dayFlightList_${di}`);
            if (listEl) { listEl.innerHTML = buildFlightList(days[di], di); bindAllFlightEvents(days); }
        });
    }

    function bindAllFlightEvents(days) {
        const el = document.getElementById('itineraryDays');
        if (!el) return;
        el.querySelectorAll('.flight-card').forEach(card => {
            const di = +card.dataset.dayIdx;
            const fi = +card.dataset.flIdx;
            bindFlightCardEvents(card, days, di, fi);

            // Drag & drop reorder
            card.addEventListener('dragstart', e => {
                e.dataTransfer.setData('text/plain', JSON.stringify({ type:'flight', di, fi }));
                card.classList.add('opacity-50');
            });
            card.addEventListener('dragend', () => card.classList.remove('opacity-50'));
            card.addEventListener('dragover', e => { e.preventDefault(); card.classList.add('ring-2','ring-sky-400'); });
            card.addEventListener('dragleave', () => card.classList.remove('ring-2','ring-sky-400'));
            card.addEventListener('drop', e => {
                e.preventDefault();
                card.classList.remove('ring-2','ring-sky-400');
                try {
                    const from = JSON.parse(e.dataTransfer.getData('text/plain'));
                    if (from.type !== 'flight') return;
                    const toDi = +card.dataset.dayIdx, toFi = +card.dataset.flIdx;
                    if (from.di !== toDi || from.fi === toFi) return;
                    const arr = days[from.di].flights;
                    const [moved] = arr.splice(from.fi, 1);
                    arr.splice(toFi, 0, moved);
                    const listEl = document.getElementById(`dayFlightList_${toDi}`);
                    if (listEl) { listEl.innerHTML = buildFlightList(days[toDi], toDi); bindAllFlightEvents(days); }
                } catch(e) {}
            });
        });
    }

        function bindStandaloneTransferEvents(days) {
        const el = document.getElementById('itineraryDays');
        if (!el) return;

        el.querySelectorAll('.st-title').forEach(inp =>
            inp.addEventListener('input', e => {
                const di = +e.target.dataset.dayIdx, ti = +e.target.dataset.trIdx;
                if (!days[di].transfers) return;
                days[di].transfers[ti].title = e.target.value;
            })
        );
        el.querySelectorAll('.st-type').forEach(sel =>
            sel.addEventListener('change', e => {
                const di = +e.target.dataset.dayIdx, ti = +e.target.dataset.trIdx;
                if (!days[di].transfers) return;
                days[di].transfers[ti].type = e.target.value;
                const isSIC = e.target.value === 'sic';
                const listEl = document.getElementById(`stPricingList_${di}_${ti}`);
                if (listEl) {
                    listEl.querySelectorAll('.pr-adult,.pr-child').forEach(i => {
                        i.classList.toggle('opacity-30', !isSIC);
                        i.classList.toggle('pointer-events-none', !isSIC);
                    });
                    listEl.querySelectorAll('.pr-full').forEach(i => {
                        i.classList.toggle('opacity-30', isSIC);
                        i.classList.toggle('pointer-events-none', isSIC);
                    });
                }
            })
        );
        el.querySelectorAll('.st-start-time').forEach(inp =>
            inp.addEventListener('change', e => {
                const di = +e.target.dataset.dayIdx, ti = +e.target.dataset.trIdx;
                if (days[di].transfers) days[di].transfers[ti].start_time = e.target.value;
            })
        );
        el.querySelectorAll('.st-end-time').forEach(inp =>
            inp.addEventListener('change', e => {
                const di = +e.target.dataset.dayIdx, ti = +e.target.dataset.trIdx;
                if (days[di].transfers) days[di].transfers[ti].end_time = e.target.value;
            })
        );
        el.querySelectorAll('.st-notes').forEach(inp =>
            inp.addEventListener('input', e => {
                const di = +e.target.dataset.dayIdx, ti = +e.target.dataset.trIdx;
                if (days[di].transfers) days[di].transfers[ti].notes = e.target.value;
            })
        );
        el.querySelectorAll('.btn-remove-st').forEach(btn =>
            btn.addEventListener('click', () => {
                const di = +btn.dataset.dayIdx, ti = +btn.dataset.trIdx;
                if (days[di].transfers) days[di].transfers.splice(ti, 1);
                const listEl = document.getElementById(`dayTransList_${di}`);
                if (listEl) {
                    listEl.innerHTML = buildStandaloneTransferList(days[di], di);
                    bindStandaloneTransferEvents(days);
                }
            })
        );
        // Drag & drop reorder for standalone transfers
        el.querySelectorAll('.standalone-transfer-card').forEach(card => {
            card.addEventListener('dragstart', e => {
                e.dataTransfer.setData('text/plain', JSON.stringify({ type:'transfer', di: +card.dataset.dayIdx, ti: +card.dataset.trIdx }));
                card.classList.add('opacity-50');
            });
            card.addEventListener('dragend', () => card.classList.remove('opacity-50'));
            card.addEventListener('dragover', e => { e.preventDefault(); card.classList.add('ring-2','ring-orange-400'); });
            card.addEventListener('dragleave', () => card.classList.remove('ring-2','ring-orange-400'));
            card.addEventListener('drop', e => {
                e.preventDefault();
                card.classList.remove('ring-2','ring-orange-400');
                try {
                    const from = JSON.parse(e.dataTransfer.getData('text/plain'));
                    if (from.type !== 'transfer') return;
                    const toDi = +card.dataset.dayIdx, toTi = +card.dataset.trIdx;
                    if (from.di !== toDi || from.ti === toTi) return;
                    const arr = days[from.di].transfers;
                    const [moved] = arr.splice(from.ti, 1);
                    arr.splice(toTi, 0, moved);
                    const listEl = document.getElementById(`dayTransList_${toDi}`);
                    if (listEl) { listEl.innerHTML = buildStandaloneTransferList(days[toDi], toDi); bindStandaloneTransferEvents(days); }
                } catch(e) {}
            });
        });

        el.querySelectorAll('.btn-add-st-car').forEach(btn =>
            btn.addEventListener('click', () => {
                const di = +btn.dataset.dayIdx, ti = +btn.dataset.trIdx;
                if (!days[di].transfers) return;
                const tr = days[di].transfers[ti];
                if (!tr.pricing) tr.pricing = [];
                const pi     = tr.pricing.length;
                const isSIC  = (tr.type || 'sic') === 'sic';
                const newPricing = { car_name:'', car_type:'sedan', price_adult:null, price_child:null, price_full:null };
                tr.pricing.push(newPricing);
                // Only append the new row — do NOT re-render the whole list
                const listEl = document.getElementById(`stPricingList_${di}_${ti}`);
                if (listEl) {
                    const div = document.createElement('div');
                    div.innerHTML = buildPricingRow(newPricing, di, -1, ti, pi, isSIC, true);
                    const row = div.firstElementChild;
                    listEl.appendChild(row);
                    // Bind inputs on just the new row
                    bindPricingRowInputs(row, days, di, -1, ti, pi, true);
                }
            })
        );
    }

        // ── Bind activity card field events ─────────────────────────────
    function bindActivityCardEvents(days) {
        const el = document.getElementById('itineraryDays');
        if (!el) return;

        // Time change → recalculate duration
        el.querySelectorAll('.act-start-time, .act-end-time').forEach(inp => {
            inp.addEventListener('change', e => {
                const di  = +e.target.dataset.dayIdx;
                const ai  = +e.target.dataset.actIdx;
                const act = days[di].activities[ai];
                if (e.target.classList.contains('act-start-time')) act.start_time = e.target.value;
                else act.end_time = e.target.value;
                if (act.start_time && act.end_time) {
                    const [sh,sm] = act.start_time.split(':').map(Number);
                    const [eh,em] = act.end_time.split(':').map(Number);
                    const diff = ((eh*60+em) - (sh*60+sm)) / 60;
                    act.duration_hours = Math.max(0, Math.round(diff * 10) / 10);
                }
                const durInput = el.querySelector(`.act-duration[data-day-idx="${di}"][data-act-idx="${ai}"]`);
                if (durInput) durInput.value = act.duration_hours;
                const durDisp = el.querySelector(`[data-day-idx="${di}"][data-act-idx="${ai}"] .act-dur-display`);
                if (durDisp) durDisp.textContent = act.duration_hours + 'h';
                updateDayProgress(di, days[di]);
            });
        });

        el.querySelectorAll('.act-location').forEach(inp =>
            inp.addEventListener('input', e => { days[+e.target.dataset.dayIdx].activities[+e.target.dataset.actIdx].location = e.target.value; })
        );
        el.querySelectorAll('.act-note').forEach(inp =>
            inp.addEventListener('input', e => { days[+e.target.dataset.dayIdx].activities[+e.target.dataset.actIdx].note = e.target.value; })
        );

        // ── Transfer title + type ────────────────────────────────────
        el.querySelectorAll('.tr-title').forEach(inp =>
            inp.addEventListener('input', e => {
                const act = days[+e.target.dataset.dayIdx].activities[+e.target.dataset.actIdx];
                act.transfers[+e.target.dataset.trIdx].title = e.target.value;
            })
        );
        el.querySelectorAll('.tr-type').forEach(sel =>
            sel.addEventListener('change', e => {
                const di = +e.target.dataset.dayIdx, ai = +e.target.dataset.actIdx, ti = +e.target.dataset.trIdx;
                const tr = days[di].activities[ai].transfers[ti];
                tr.type = e.target.value;
                // Re-render just this transfer's pricing rows to toggle adult/child vs full opacity
                const listEl = document.getElementById(`transferList_${di}_${ai}`);
                if (listEl) {
                    const tRow = listEl.querySelector(`[data-tr-idx="${ti}"] .transfer-pricing-list`);
                    if (tRow) {
                        const isSIC = tr.type === 'sic';
                        tRow.querySelectorAll('.pr-adult,.pr-child').forEach(i => {
                            i.classList.toggle('opacity-30', !isSIC);
                            i.classList.toggle('pointer-events-none', !isSIC);
                        });
                        tRow.querySelectorAll('.pr-full').forEach(i => {
                            i.classList.toggle('opacity-30', isSIC);
                            i.classList.toggle('pointer-events-none', isSIC);
                        });
                    }
                }
            })
        );

        // ── Remove transfer ─────────────────────────────────────────
        el.querySelectorAll('.btn-remove-transfer').forEach(btn =>
            btn.addEventListener('click', () => {
                const di = +btn.dataset.dayIdx, ai = +btn.dataset.actIdx, ti = +btn.dataset.trIdx;
                days[di].activities[ai].transfers.splice(ti, 1);
                // Re-render just the transfer list for this activity card
                const listEl = document.getElementById(`transferList_${di}_${ai}`);
                if (listEl) {
                    const act = days[di].activities[ai];
                    listEl.innerHTML = buildTransferRows(act, di, ai) ||
                        '<p class="text-xs text-gray-400 italic">No transfers.</p>';
                    bindActivityCardEvents(days);  // rebind after re-render
                }
            })
        );

        // ── Add transfer ────────────────────────────────────────────
        el.querySelectorAll('.btn-add-transfer').forEach(btn =>
            btn.addEventListener('click', () => {
                const di = +btn.dataset.dayIdx, ai = +btn.dataset.actIdx;
                const act = days[di].activities[ai];
                if (!act.transfers) act.transfers = [];
                const ti  = act.transfers.length;
                const newTr = { title:'', type:'sic', notes:'', pricing:[] };
                act.transfers.push(newTr);
                // Append just the new transfer row
                const listEl = document.getElementById(`transferList_${di}_${ai}`);
                if (listEl) {
                    const noMsg = listEl.querySelector('p');
                    if (noMsg) noMsg.remove();
                    const div = document.createElement('div');
                    div.innerHTML = buildTransferRows({ transfers:[newTr] }, di, ai).replace(
                        /data-tr-idx="0"/g, `data-tr-idx="${ti}"`
                    );
                    const row = div.firstElementChild;
                    listEl.appendChild(row);
                    // Bind just this new transfer row
                    row.querySelector('.tr-title')?.addEventListener('input', e => { act.transfers[ti].title = e.target.value; });
                    row.querySelector('.tr-type')?.addEventListener('change', e => { act.transfers[ti].type = e.target.value; });
                    row.querySelector('.btn-remove-transfer')?.addEventListener('click', () => {
                        act.transfers.splice(ti, 1);
                        renderItineraryDays();
                    });
                    row.querySelector('.btn-add-car-row')?.setAttribute('data-tr-idx', ti);
                    // re-bind add-car for this row
                    row.querySelector('.btn-add-car-row')?.addEventListener('click', () => {
                        if (!act.transfers[ti].pricing) act.transfers[ti].pricing = [];
                        const pi = act.transfers[ti].pricing.length;
                        const isSIC = (act.transfers[ti].type || 'sic') === 'sic';
                        const newP = { car_name:'', car_type:'sedan', price_adult:null, price_child:null, price_full:null };
                        act.transfers[ti].pricing.push(newP);
                        const pList = row.querySelector('.transfer-pricing-list');
                        if (pList) {
                            const d2 = document.createElement('div');
                            d2.innerHTML = buildPricingRow(newP, di, ai, ti, pi, isSIC, false);
                            const pRow = d2.firstElementChild;
                            pList.appendChild(pRow);
                            bindPricingRowInputs(pRow, days, di, ai, ti, pi, false);
                        }
                    });
                }
            })
        );

        // ── Add car row inside a transfer ───────────────────────────
        el.querySelectorAll('.btn-add-car-row').forEach(btn =>
            btn.addEventListener('click', () => {
                const di = +btn.dataset.dayIdx, ai = +btn.dataset.actIdx, ti = +btn.dataset.trIdx;
                const tr = days[di].activities[ai].transfers[ti];
                if (!tr.pricing) tr.pricing = [];
                const pi     = tr.pricing.length;
                const isSIC  = (tr.type || 'sic') === 'sic';
                const newP   = { car_name:'', car_type:'sedan', price_adult:null, price_child:null, price_full:null };
                tr.pricing.push(newP);
                // Only append the new row — find the specific pricing list div
                const tRow = document.querySelector(`#transferList_${di}_${ai} [data-tr-idx="${ti}"] .transfer-pricing-list`);
                if (tRow) {
                    const div = document.createElement('div');
                    div.innerHTML = buildPricingRow(newP, di, ai, ti, pi, isSIC, false);
                    const row = div.firstElementChild;
                    tRow.appendChild(row);
                    bindPricingRowInputs(row, days, di, ai, ti, pi, false);
                }
            })
        );

        // ── Pricing inputs ──────────────────────────────────────────
        el.querySelectorAll('.pr-car-name').forEach(inp =>
            inp.addEventListener('input', e => {
                const act = days[+e.target.dataset.dayIdx].activities[+e.target.dataset.actIdx];
                act.transfers[+e.target.dataset.tr].pricing[+e.target.dataset.pr].car_name = e.target.value;
            })
        );
        el.querySelectorAll('.pr-car-type').forEach(sel =>
            sel.addEventListener('change', e => {
                const act = days[+e.target.dataset.dayIdx].activities[+e.target.dataset.actIdx];
                act.transfers[+e.target.dataset.tr].pricing[+e.target.dataset.pr].car_type = e.target.value;
            })
        );
        el.querySelectorAll('.pr-adult').forEach(inp =>
            inp.addEventListener('input', e => {
                const act = days[+e.target.dataset.dayIdx].activities[+e.target.dataset.actIdx];
                act.transfers[+e.target.dataset.tr].pricing[+e.target.dataset.pr].price_adult = parseFloat(e.target.value)||null;
            })
        );
        el.querySelectorAll('.pr-child').forEach(inp =>
            inp.addEventListener('input', e => {
                const act = days[+e.target.dataset.dayIdx].activities[+e.target.dataset.actIdx];
                act.transfers[+e.target.dataset.tr].pricing[+e.target.dataset.pr].price_child = parseFloat(e.target.value)||null;
            })
        );
        el.querySelectorAll('.pr-full').forEach(inp =>
            inp.addEventListener('input', e => {
                const act = days[+e.target.dataset.dayIdx].activities[+e.target.dataset.actIdx];
                act.transfers[+e.target.dataset.tr].pricing[+e.target.dataset.pr].price_full = parseFloat(e.target.value)||null;
            })
        );

        // ── Remove activity ─────────────────────────────────────────
        el.querySelectorAll('.btn-remove-act').forEach(btn =>
            btn.addEventListener('click', () => {
                const di = +btn.dataset.dayIdx, ai = +btn.dataset.actIdx;
                days[di].activities.splice(ai, 1);
                renderItineraryDays();
            })
        );

        // Drag & drop reorder
        el.querySelectorAll('.activity-card').forEach(card => {
            card.addEventListener('dragstart', e => {
                e.dataTransfer.setData('text/plain', JSON.stringify({
                    di: +card.dataset.dayIdx, ai: +card.dataset.actIdx
                }));
                card.classList.add('opacity-50');
            });
            card.addEventListener('dragend', () => card.classList.remove('opacity-50'));
            card.addEventListener('dragover', e => { e.preventDefault(); card.classList.add('ring-2','ring-blue-400'); });
            card.addEventListener('dragleave', () => card.classList.remove('ring-2','ring-blue-400'));
            card.addEventListener('drop', e => {
                e.preventDefault();
                card.classList.remove('ring-2','ring-blue-400');
                const from = JSON.parse(e.dataTransfer.getData('text/plain'));
                const toDi = +card.dataset.dayIdx, toAi = +card.dataset.actIdx;
                if (from.di !== toDi || from.ai === toAi) return;
                const acts = days[from.di].activities;
                const [moved] = acts.splice(from.ai, 1);
                acts.splice(toAi, 0, moved);
                renderItineraryDays();  // re-render immediately on drop
            });
        });
    }

    // ── Update day progress bar without full re-render ───────────────
    function updateDayProgress(dayIdx, day) {
        const usedHours  = (day.activities||[]).reduce((s,a)=>s+(parseFloat(a.duration_hours)||0),0);
        const limitHours = parseFloat(day.day_hours||16);
        const pct        = Math.min(100, Math.round((usedHours/limitHours)*100));
        const barColor   = pct >= 100 ? 'bg-red-500' : pct >= 80 ? 'bg-amber-400' : 'bg-green-500';
        const container  = document.querySelector(`[data-day-idx="${dayIdx}"]`);
        if (!container) return;
        const bar   = container.querySelector('.h-1\\.5.w-24 > div');
        const label = container.querySelector('.h-1\\.5.w-24 + span');
        if (bar)   { bar.style.width = pct+'%'; bar.className = `h-full ${barColor} rounded-full transition-all`; }
        if (label) { label.textContent = `${usedHours}h / ${limitHours}h`; label.className = `text-xs ${pct>=100?'text-red-500 font-semibold':'text-gray-400'}`; }
    }

    // ── Activity Picker (search modal per day) ───────────────────────
    async function openActivityPicker(dayIdx) {
        // Remove existing picker if open
        document.getElementById('actPickerModal')?.remove();

        const day       = state.steps[5].pack_itenaries[dayIdx];
        const limitHrs  = parseFloat(day.day_hours||16);
        const usedHrs   = (day.activities||[]).reduce((s,a)=>s+(parseFloat(a.duration_hours)||0),0);
        const remaining = Math.max(0, limitHrs - usedHrs);

        // Get country sys_ids from Step 2 selected countries
        // Resolve country_sys_id — country objects in step 2 may only have integer id
        // Map through allCountries to get the proper sys_id string
        const countrySysIds = state.steps[2].countries.map(c => {
            if (c.sys_id && c.sys_id.includes('-')) return c.sys_id;  // already a sys_id string
            // Look up by integer id in allCountries
            const full = state.allCountries.find(x => x.id === c.id || x.id === parseInt(c.id));
            return full?.sys_id || null;
        }).filter(Boolean);

        // Create picker modal
        const modal = document.createElement('div');
        modal.id = 'actPickerModal';
        modal.className = 'fixed inset-0 z-[60] flex items-center justify-center bg-black/50 backdrop-blur-sm p-6';
        modal.innerHTML = `
        <div class="w-full max-w-[900px] h-[680px] max-h-[calc(100vh-3rem)] flex flex-col bg-white rounded-2xl shadow-2xl">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
                <div>
                    <h3 class="text-base font-bold text-gray-800">Add Activity — Day ${day.day_number}</h3>
                    <p class="text-xs text-gray-400 mt-0.5">
                        ${remaining > 0
                            ? `${usedHrs}h used · <span class="text-green-600 font-medium">${remaining}h remaining</span>`
                            : `<span class="text-red-500 font-medium">Day is full (${limitHrs}h)</span>`}
                    </p>
                </div>
                <button id="closeActPicker" class="text-gray-400 hover:text-gray-600 text-xl w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="px-6 py-3 border-b border-gray-100 flex-shrink-0 flex gap-2">
                <div class="relative flex-1">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                    <input id="actPickerSearch" type="text" placeholder="Search activities…"
                           class="w-full pl-8 pr-4 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
                <select id="actPickerType" class="text-sm border border-gray-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <option value="">All Types</option>
                    <option value="tour">Tour</option>
                    <option value="transfer">Transfer</option>
                    <option value="both">Both</option>
                </select>
            </div>
            <div id="actPickerList" class="flex-1 overflow-y-auto overflow-x-hidden px-6 py-4">
                <div class="text-center py-8 text-gray-400"><i class="fa-solid fa-spinner fa-spin mr-2"></i>Loading activities…</div>
            </div>
        </div>`;

        document.body.appendChild(modal);

        modal.querySelector('#closeActPicker').addEventListener('click', () => modal.remove());
        modal.addEventListener('click', e => { if (e.target === modal) modal.remove(); });

        // Load activities from all selected countries
        let allActivities = [];
        for (const cSysId of countrySysIds) {
            if (!state.allActivities[cSysId]) {
                await fetchActivitiesByCountry(cSysId);
            }
            allActivities = allActivities.concat(state.allActivities[cSysId] || []);
        }

        // Render picker list
        function renderPickerList() {
            const search  = document.getElementById('actPickerSearch')?.value.toLowerCase() || '';
            const typeF   = document.getElementById('actPickerType')?.value || '';
            const listEl  = document.getElementById('actPickerList');
            if (!listEl) return;

            let filtered = allActivities;
            if (search)  filtered = filtered.filter(a => a.name.toLowerCase().includes(search) || (a.location||'').toLowerCase().includes(search));
            if (typeF)   filtered = filtered.filter(a => a.type === typeF);

            const addedIds = new Set((day.activities||[]).map(a => a.sys_id));

            if (!filtered.length) {
                listEl.innerHTML = `<div class="text-center py-8 text-gray-400">No activities found</div>`;
                return;
            }

            const typeColors = { tour:'bg-blue-50 text-blue-700', transfer:'bg-violet-50 text-violet-700', both:'bg-teal-50 text-teal-700' };

            listEl.innerHTML = filtered.map(a => {
                const isAdded    = addedIds.has(a.sys_id);
                const wouldExceed = !isAdded && remaining < (parseFloat(a.duration_hours)||0) && remaining > 0;
                const tc = typeColors[a.type] || 'bg-gray-100 text-gray-600';
                return `
                <div class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 hover:border-blue-200 hover:bg-blue-50/30 transition mb-2">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                            <span class="text-sm font-semibold text-gray-800 truncate">${escHtml(a.name)}</span>
                            <span class="text-xs px-1.5 py-0.5 rounded-full ${tc} flex-shrink-0">${a.type}</span>
                        </div>
                        <div class="flex items-center gap-3 text-xs text-gray-400">
                            ${a.location ? `<span><i class="fa-solid fa-location-dot mr-1"></i>${escHtml(a.location)}</span>` : ''}
                            ${a.start_time ? `<span><i class="fa-regular fa-clock mr-1"></i>${a.start_time}–${a.end_time||'?'}</span>` : ''}
                            ${a.duration_hours ? `<span><i class="fa-solid fa-hourglass-half mr-1"></i>${a.duration_hours}h</span>` : ''}
                        </div>
                    </div>
                    ${wouldExceed
                        ? `<span class="text-xs text-amber-600 flex-shrink-0"><i class="fa-solid fa-triangle-exclamation mr-1"></i>Exceeds limit</span>`
                        : ''}
                    <button type="button" data-sys-id="${escHtml(a.sys_id)}"
                            class="btn-pick-act flex-shrink-0 text-xs px-3 py-1.5 rounded-lg font-semibold transition
                                   ${isAdded
                                       ? 'bg-green-100 text-green-700 cursor-default'
                                       : remaining > 0 && wouldExceed
                                           ? 'bg-amber-50 text-amber-600 border border-amber-300 hover:bg-amber-100'
                                           : 'bg-blue-600 hover:bg-blue-700 text-white'}"
                            ${isAdded ? 'disabled' : ''}>
                        ${isAdded ? '<i class="fa-solid fa-check mr-1"></i>Added' : '<i class="fa-solid fa-plus mr-1"></i>Add'}
                    </button>
                </div>`;
            }).join('');

            // Bind pick buttons
            listEl.querySelectorAll('.btn-pick-act').forEach(btn => {
                if (btn.disabled) return;
                btn.addEventListener('click', async () => {
                    const act = allActivities.find(a => a.sys_id === btn.dataset.sysId);
                    if (!act) return;

                    const curUsed  = (day.activities||[]).reduce((s,a)=>s+(parseFloat(a.duration_hours)||0),0);
                    const actDur   = parseFloat(act.duration_hours)||0;
                    const dayLimit = parseFloat(day.day_hours||16);

                    if (curUsed + actDur > dayLimit) {
                        if (!confirm(`Adding "${act.name}" (${actDur}h) will exceed this day's ${dayLimit}h limit. Add anyway?`)) return;
                    }

                    // Fetch full activity data (list.php only returns summary — get.php has transfers etc.)
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i>Adding…';
                    let fullAct = act;
                    try {
                        const url = (typeof API_ACTIVITIES_BASE !== 'undefined')
                            ? `${API_ACTIVITIES_BASE}get.php?sys_id=${encodeURIComponent(act.sys_id)}`
                            : `../api/masterdata/activities/get.php?sys_id=${encodeURIComponent(act.sys_id)}`;
                        const res  = await fetch(url);
                        const json = await res.json();
                        if (json.success && json.data) fullAct = json.data;
                    } catch(e) { /* use summary data if fetch fails */ }

                    if (!day.activities) day.activities = [];
                    day.activities.push({
                        sys_id          : fullAct.sys_id,
                        original_sys_id : fullAct.sys_id,
                        country_sys_id  : fullAct.country_sys_id,
                        name            : fullAct.name,
                        type            : fullAct.type,
                        location        : fullAct.location        || '',
                        start_time      : fullAct.start_time      || '',
                        end_time        : fullAct.end_time        || '',
                        duration_hours  : fullAct.duration_hours  || 0,
                        popularity      : fullAct.popularity      || 3,
                        note            : '',
                        itineraries     : fullAct.itineraries     || [],
                        inclusions      : fullAct.inclusions      || [],
                        exclusions      : fullAct.exclusions      || [],
                        transfers       : fullAct.transfers       || [],
                        pickup_from_city: fullAct.pickup_from_city || [],
                        dropoff_city    : fullAct.dropoff_city    || [],
                    });

                    modal.remove();
                    renderItineraryDays();
                });
            });
        }

        renderPickerList();
        document.getElementById('actPickerSearch')?.addEventListener('input', renderPickerList);
        document.getElementById('actPickerType')?.addEventListener('change', renderPickerList);
    }

    // ── Fetch activities by country from masterdata API ──────────────
    async function fetchActivitiesByCountry(countrySysId) {
        if (!countrySysId || state.allActivities[countrySysId]) return;
        try {
            const url  = (typeof API_ACTIVITIES_BASE !== 'undefined')
                ? `${API_ACTIVITIES_BASE}list.php?country_sys_id=${encodeURIComponent(countrySysId)}&status=active&limit=100`
                : `../api/masterdata/activities/list.php?country_sys_id=${encodeURIComponent(countrySysId)}&status=active&limit=100`;
            const res  = await fetch(url);
            const json = await res.json();
            state.allActivities[countrySysId] = (json.data || []).map(a => ({
                sys_id         : a.sys_id,
                country_sys_id : a.country_sys_id,
                name           : a.name,
                type           : a.type,
                location       : a.location,
                start_time     : a.start_time,
                end_time       : a.end_time,
                duration_hours : a.duration_hours,
                popularity     : a.popularity,
                itineraries    : a.itineraries    || [],
                inclusions     : a.inclusions     || [],
                exclusions     : a.exclusions     || [],
                transfers      : a.transfers      || [],
                pickup_from_city: a.pickup_from_city || [],
                dropoff_city   : a.dropoff_city   || [],
            }));
        } catch(e) {
            console.warn('Failed to fetch activities for country', countrySysId, e);
            state.allActivities[countrySysId] = [];
        }
    }

    function renderPriceOptions() {
        const el = document.getElementById('priceOptionsList');
        if (!el) return;
        el.innerHTML = state.steps[6].pack_price.map((opt, i) => `
        <div class="border border-gray-200 rounded-xl p-4 price-opt" data-idx="${i}">
            <div class="flex items-center gap-3 mb-3">
                <input type="text" placeholder="Option title (e.g. Standard)" value="${escHtml(opt.title)}"
                       class="flex-1 px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 opt-title">
                <input type="number" placeholder="Price" value="${opt.price||''}"
                       class="w-32 px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 opt-price">
                <button type="button" data-idx="${i}"
                        class="remove-opt w-8 h-8 flex items-center justify-center rounded-xl bg-red-50 text-red-500 hover:bg-red-100 transition text-xs">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            ${state.steps[2].cities.length ? `
            <div class="text-xs font-medium text-gray-500 mb-2">Hotel per city:</div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                ${state.steps[2].cities.map(city => `
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500 w-24 truncate">${escHtml(city.name)}</span>
                    <select class="flex-1 px-2 py-1.5 border border-gray-200 rounded-lg text-xs focus:outline-none opt-hotel" data-city="${city.id}">
                        <option value="">-- Select hotel --</option>
                        ${state.steps[4].hotels.filter(h=>h.city_id===city.id).map(h=>
                            `<option value="${escHtml(h.hotel_title)}" ${(opt.hotels[city.id]===h.hotel_title)?'selected':''}>${escHtml(h.hotel_title)}</option>`).join('')}
                    </select>
                </div>`).join('')}
            </div>` : ''}
        </div>`).join('');

        el.querySelectorAll('.price-opt').forEach((row, i) => {
            row.querySelector('.opt-title').addEventListener('input', e => { state.steps[6].pack_price[i].title = e.target.value; });
            row.querySelector('.opt-price').addEventListener('input', e => { state.steps[6].pack_price[i].price = e.target.value; });
            row.querySelectorAll('.opt-hotel').forEach(sel => {
                sel.addEventListener('change', e => {
                    state.steps[6].pack_price[i].hotels[parseInt(sel.dataset.city)] = e.target.value;
                });
            });
        });
        el.querySelectorAll('.remove-opt').forEach(btn => {
            btn.addEventListener('click', () => {
                state.steps[6].pack_price.splice(parseInt(btn.dataset.idx), 1);
                renderPriceOptions();
            });
        });
    }

    const FA_ICONS = ['fa-check-circle','fa-plane','fa-hotel','fa-bus','fa-utensils','fa-camera','fa-mountain','fa-ship','fa-ticket','fa-umbrella-beach','fa-passport','fa-suitcase','fa-headset','fa-wifi','fa-car'];

    function renderStep7() {
        document.getElementById('stepContent').innerHTML = `
        <h2 class="text-lg font-bold text-gray-800 mb-1">Inclusions & Exclusions</h2>
        <p class="text-sm text-gray-400 mb-6">Define what's included and excluded</p>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold text-gray-700 flex items-center gap-2">
                        <i class="fa-solid fa-circle-check text-green-500"></i> Inclusions
                    </h3>
                    <button id="addInclusionBtn" class="text-xs bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg font-medium transition flex items-center gap-1">
                        <i class="fa-solid fa-plus text-xs"></i> Add
                    </button>
                </div>
                <div id="inclusionsList" class="space-y-3"></div>
            </div>
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold text-gray-700 flex items-center gap-2">
                        <i class="fa-solid fa-circle-xmark text-red-500"></i> Exclusions
                    </h3>
                    <button id="addExclusionBtn" class="text-xs bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded-lg font-medium transition flex items-center gap-1">
                        <i class="fa-solid fa-plus text-xs"></i> Add
                    </button>
                </div>
                <div id="exclusionsList" class="space-y-2"></div>
            </div>
        </div>`;
        renderInclusions();
        renderExclusions();
        document.getElementById('addInclusionBtn').addEventListener('click', () => {
            state.steps[7].pack_inclusions.push({ id: Date.now(), title:'', icon:'fa-check-circle', sub_titles:[] });
            renderInclusions();
        });
        document.getElementById('addExclusionBtn').addEventListener('click', () => {
            state.steps[7].pack_exclusions.push('');
            renderExclusions();
        });
    }

    function renderInclusions() {
        const el = document.getElementById('inclusionsList');
        if (!el) return;
        el.innerHTML = state.steps[7].pack_inclusions.map((inc, i) => `
        <div class="border border-gray-200 rounded-xl p-3 space-y-2">
            <div class="flex items-center gap-2">
                <select class="w-10 border border-gray-200 rounded-lg px-1 py-1.5 text-xs inc-icon" data-idx="${i}">
                    ${FA_ICONS.map(ic=>`<option value="${ic}" ${inc.icon===ic?'selected':''}>${ic.replace('fa-','')}</option>`).join('')}
                </select>
                <i class="fa-solid ${inc.icon||'fa-check-circle'} text-green-500 w-5 inc-preview-icon"></i>
                <input type="text" placeholder="Inclusion title" value="${escHtml(inc.title)}"
                       class="flex-1 px-3 py-1.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 inc-title" data-idx="${i}">
                <button type="button" data-idx="${i}" class="remove-inc text-red-400 hover:text-red-600 text-xs"><i class="fa-solid fa-times"></i></button>
            </div>
            <div id="incSubs_${i}" class="space-y-1.5 pl-7"></div>
            <button type="button" data-idx="${i}" class="add-sub text-xs text-blue-600 hover:text-blue-800 pl-7 flex items-center gap-1">
                <i class="fa-solid fa-plus-circle text-xs"></i> Add sub-item
            </button>
        </div>`).join('');

        state.steps[7].pack_inclusions.forEach((inc, i) => {
            const subEl = document.getElementById(`incSubs_${i}`);
            if (subEl) subEl.innerHTML = (inc.sub_titles||[]).map((s, si) => `
            <div class="flex items-center gap-1">
                <input type="text" value="${escHtml(s)}" placeholder="Sub-item"
                       class="flex-1 px-2 py-1 border border-gray-200 rounded-lg text-xs focus:outline-none inc-sub" data-i="${i}" data-si="${si}">
                <button data-i="${i}" data-si="${si}" class="remove-sub text-red-400 hover:text-red-600 text-xs"><i class="fa-solid fa-times"></i></button>
            </div>`).join('');
        });

        el.querySelectorAll('.inc-title').forEach(inp =>
            inp.addEventListener('input', e => { state.steps[7].pack_inclusions[parseInt(e.target.dataset.idx)].title = e.target.value; })
        );
        el.querySelectorAll('.inc-icon').forEach(sel => {
            sel.addEventListener('change', e => {
                const idx     = parseInt(e.target.dataset.idx);
                state.steps[7].pack_inclusions[idx].icon = e.target.value;
                const preview = sel.closest('div').querySelector('.inc-preview-icon');
                if (preview) preview.className = `fa-solid ${e.target.value} text-green-500 w-5 inc-preview-icon`;
            });
        });
        el.querySelectorAll('.remove-inc').forEach(btn => btn.addEventListener('click', () => {
            state.steps[7].pack_inclusions.splice(parseInt(btn.dataset.idx), 1);
            renderInclusions();
        }));
        el.querySelectorAll('.add-sub').forEach(btn => btn.addEventListener('click', () => {
            state.steps[7].pack_inclusions[parseInt(btn.dataset.idx)].sub_titles.push('');
            renderInclusions();
        }));
        el.querySelectorAll('.inc-sub').forEach(inp =>
            inp.addEventListener('input', e => {
                state.steps[7].pack_inclusions[parseInt(e.target.dataset.i)].sub_titles[parseInt(e.target.dataset.si)] = e.target.value;
            })
        );
        el.querySelectorAll('.remove-sub').forEach(btn => btn.addEventListener('click', () => {
            state.steps[7].pack_inclusions[parseInt(btn.dataset.i)].sub_titles.splice(parseInt(btn.dataset.si), 1);
            renderInclusions();
        }));
    }

    function renderExclusions() {
        const el = document.getElementById('exclusionsList');
        if (!el) return;
        el.innerHTML = state.steps[7].pack_exclusions.map((ex, i) => `
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-circle-xmark text-red-400 text-xs"></i>
            <input type="text" value="${escHtml(ex)}" placeholder="e.g. International flights not included"
                   class="flex-1 px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 exc-input" data-idx="${i}">
            <button data-idx="${i}" class="remove-exc text-red-400 hover:text-red-600 text-xs"><i class="fa-solid fa-times"></i></button>
        </div>`).join('');
        el.querySelectorAll('.exc-input').forEach(inp =>
            inp.addEventListener('input', e => { state.steps[7].pack_exclusions[parseInt(e.target.dataset.idx)] = e.target.value; })
        );
        el.querySelectorAll('.remove-exc').forEach(btn => btn.addEventListener('click', () => {
            state.steps[7].pack_exclusions.splice(parseInt(btn.dataset.idx), 1);
            renderExclusions();
        }));
    }

    function renderStep8() {
        const s   = state.steps;
        const sym = s[6].currency_symbol || '';
        document.getElementById('stepContent').innerHTML = `
        <h2 class="text-lg font-bold text-gray-800 mb-1">Final Review</h2>
        <p class="text-sm text-gray-400 mb-6">Review all details before saving or finalizing</p>
        <div class="space-y-5">
            ${reviewSection('Basic Info', `
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div><span class="text-gray-400">Title:</span> <strong>${escHtml(s[1].title)}</strong></div>
                    <div><span class="text-gray-400">Rating:</span> <strong>${s[1].rating} ★</strong></div>
                    <div class="col-span-2"><span class="text-gray-400">Description:</span> ${escHtml(s[1].description)||'—'}</div>
                </div>`, '1')}
            ${reviewSection('Destination', `
                <p class="text-sm text-gray-500">Countries: ${(s[2].countries||[]).map(c=>c.name).join(', ')||'None'}</p>
                <p class="text-sm text-gray-500">Cities: ${(s[2].cities||[]).map(c=>c.name).join(', ')||'None'}</p>`, '2')}
            ${reviewSection('Quotation', `
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div><span class="text-gray-400">Duration:</span> ${escHtml(s[3].duration)||'—'}</div>
                    <div><span class="text-gray-400">Dates:</span> ${s[3].start_date||'—'} → ${s[3].end_date||'—'}</div>
                    <div><span class="text-gray-400">Pax:</span> ${s[3].no_of_pax?.adult||0}A / ${s[3].no_of_pax?.child||0}C / ${s[3].no_of_pax?.infant||0}I</div>
                </div>`, '3')}
            ${reviewSection('Itinerary', `
                <p class="text-sm text-gray-500">${s[5].pack_itenaries?.length||0} day(s) planned</p>
                ${(s[5].pack_itenaries||[]).map(d=>`
                    <p class="text-xs text-gray-400 mt-1">
                        Day ${d.day_number}: ${escHtml(d.title)||'—'}
                        ${d.overnight_stay?'· '+d.overnight_stay:''}
                        · ${(d.activities||[]).length} activit${(d.activities||[]).length===1?'y':'ies'}
                    </p>`).join('')}`, '5')}
            ${reviewSection('Pricing', `
                <div class="text-sm">
                    <div><span class="text-gray-400">Currency:</span> ${escHtml(s[6].currency_title)} (${escHtml(s[6].currency_code)}) ${escHtml(s[6].currency_symbol)}</div>
                    <div class="mt-1"><span class="text-gray-400">Overall Price:</span> <strong class="text-blue-600">${sym}${Number(s[6].overall_price||0).toLocaleString()}</strong></div>
                    <div class="mt-1 text-gray-400">${s[6].pack_price?.length||0} pricing option(s)</div>
                </div>`, '6')}
            ${reviewSection('Inclusions & Exclusions', `
                <p class="text-sm text-gray-500">${s[7].pack_inclusions?.length||0} inclusion(s) · ${s[7].pack_exclusions?.length||0} exclusion(s)</p>`, '7')}
        </div>

        <!-- Action buttons -->
        <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <button id="btnSaveOnly"
                    class="flex items-center justify-center gap-2 py-3 rounded-2xl bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold text-sm transition">
                <i class="fa-solid fa-floppy-disk"></i> Save & Go to List
            </button>
            <button id="btnSaveFinalize"
                    class="flex items-center justify-center gap-2 py-3 rounded-2xl bg-green-600 hover:bg-green-700 text-white font-semibold text-sm transition shadow">
                <i class="fa-solid fa-flag-checkered"></i> Save & Finalize
            </button>
        </div>`;

        document.querySelectorAll('.review-edit').forEach(btn =>
            btn.addEventListener('click', () => navigateToStep(parseInt(btn.dataset.step)))
        );

        // Save only → mark as saved, go to list
        document.getElementById('btnSaveOnly').addEventListener('click', async () => {
            if (!state.uuid) return;
            showLoader('Saving…');
            try {
                await fetch(`${BASE_API}/step-save.php`, {
                    method: 'POST', headers: {'Content-Type':'application/json'},
                    body: JSON.stringify({ uuid: state.uuid, step_number: 8, step_data: {} })
                });
                hideLoader();
                toast('success', 'Package saved!');
                setTimeout(() => window.location.href = 'index-packages.php', 1200);
            } catch(e) { hideLoader(); toast('error', e.message); }
        });

        // Save & Finalize → show confirmation modal
        document.getElementById('btnSaveFinalize').addEventListener('click', () => showFinalizeModal());
    }

    function showFinalizeModal() {
        document.getElementById('finalizeModal')?.remove();

        const modal = document.createElement('div');
        modal.id = 'finalizeModal';
        modal.className = 'fixed inset-0 z-[60] flex items-center justify-center bg-black/50 backdrop-blur-sm p-6';
        modal.innerHTML = `
        <div class="w-full max-w-[900px] h-[680px] max-h-[calc(100vh-3rem)] flex flex-col bg-white rounded-2xl shadow-2xl">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
                <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-flag-checkered text-green-600"></i> Finalize Package
                </h3>
                <button id="closeFinalizeModal" class="text-gray-400 hover:text-gray-600 text-xl w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto overflow-x-hidden px-6 py-5 space-y-5">
                <!-- Warning -->
                <div class="bg-amber-50 border border-amber-200 rounded-2xl px-5 py-4 flex gap-3">
                    <i class="fa-solid fa-triangle-exclamation text-amber-500 text-lg flex-shrink-0 mt-0.5"></i>
                    <div>
                        <p class="text-sm font-semibold text-amber-800 mb-1">This action cannot be undone</p>
                        <ul class="text-sm text-amber-700 space-y-1 list-disc list-inside">
                            <li>Once finalized, this package will be <strong>locked</strong> and cannot be edited.</li>
                            <li>All activity modifications will be saved as permanent records.</li>
                            <li>The package status will change to <strong>Completed</strong>.</li>
                            <li>A PDF will be generated based on the selected template.</li>
                        </ul>
                    </div>
                </div>

                <!-- Package summary -->
                <div class="bg-gray-50 rounded-2xl px-5 py-4">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Package Summary</p>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div><span class="text-gray-400">Package:</span> <strong>${escHtml(state.steps[1].title)}</strong></div>
                        <div><span class="text-gray-400">ID:</span> <code class="text-xs bg-white px-2 py-0.5 rounded border border-gray-200">${state.sys_id||'—'}</code></div>
                        <div><span class="text-gray-400">Days:</span> ${state.steps[5].pack_itenaries?.length||0}</div>
                        <div><span class="text-gray-400">Activities:</span> ${(state.steps[5].pack_itenaries||[]).reduce((s,d)=>s+(d.activities||[]).length,0)}</div>
                    </div>
                </div>

                <!-- PDF template -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fa-solid fa-file-pdf text-red-500 mr-1"></i> PDF Template
                    </label>
                    <p class="text-xs text-gray-400 mb-3">Choose the format for the client-facing PDF document.</p>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="pdf-template-option cursor-pointer">
                            <input type="radio" name="pdf_template" value="detailed" class="sr-only" checked>
                            <div class="border-2 border-blue-500 bg-blue-50 rounded-xl p-4 text-center transition">
                                <i class="fa-solid fa-file-lines text-2xl text-blue-600 mb-2 block"></i>
                                <p class="text-sm font-semibold text-blue-700">Detailed</p>
                                <p class="text-xs text-blue-500 mt-1">Full descriptions, itinerary, pricing breakdown</p>
                            </div>
                        </label>
                        <label class="pdf-template-option cursor-pointer">
                            <input type="radio" name="pdf_template" value="bullet" class="sr-only">
                            <div class="border-2 border-gray-200 bg-white rounded-xl p-4 text-center hover:border-gray-400 transition">
                                <i class="fa-solid fa-list text-2xl text-gray-500 mb-2 block"></i>
                                <p class="text-sm font-semibold text-gray-700">Bullet Points</p>
                                <p class="text-xs text-gray-400 mt-1">Concise summary, key points only</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Confirmation checkbox -->
                <div class="flex items-start gap-3 bg-red-50 border border-red-100 rounded-xl px-4 py-3">
                    <input type="checkbox" id="finalizeConfirmCheck" class="mt-0.5 w-4 h-4 rounded text-red-600 cursor-pointer">
                    <label for="finalizeConfirmCheck" class="text-sm text-red-700 cursor-pointer">
                        I understand that finalizing this package is <strong>permanent</strong> and cannot be reversed.
                    </label>
                </div>

                <div id="finalizeError" class="hidden text-sm text-red-600 bg-red-50 rounded-xl px-4 py-3"></div>
            </div>
            <div class="flex gap-3 px-6 py-4 border-t border-gray-100 flex-shrink-0">
                <button id="cancelFinalizeBtn" class="flex-1 py-2.5 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 font-medium transition">Cancel</button>
                <button id="confirmFinalizeBtn" class="flex-1 py-2.5 rounded-xl bg-green-600 hover:bg-green-700 text-white font-semibold transition opacity-50 cursor-not-allowed" disabled>
                    <i class="fa-solid fa-flag-checkered mr-1"></i> Confirm & Finalize
                </button>
            </div>
        </div>`;

        document.body.appendChild(modal);

        // Template selection styling
        modal.querySelectorAll('.pdf-template-option input').forEach(radio => {
            radio.addEventListener('change', () => {
                modal.querySelectorAll('.pdf-template-option > div').forEach(d => {
                    d.classList.remove('border-blue-500','bg-blue-50');
                    d.classList.add('border-gray-200','bg-white');
                });
                if (radio.checked) {
                    radio.closest('.pdf-template-option').querySelector('div').classList.remove('border-gray-200','bg-white');
                    radio.closest('.pdf-template-option').querySelector('div').classList.add('border-blue-500','bg-blue-50');
                }
            });
        });

        // Checkbox enables confirm button
        modal.querySelector('#finalizeConfirmCheck').addEventListener('change', function() {
            const btn = modal.querySelector('#confirmFinalizeBtn');
            btn.disabled = !this.checked;
            btn.classList.toggle('opacity-50', !this.checked);
            btn.classList.toggle('cursor-not-allowed', !this.checked);
        });

        modal.querySelector('#closeFinalizeModal').addEventListener('click', () => modal.remove());
        modal.querySelector('#cancelFinalizeBtn').addEventListener('click', () => modal.remove());
        modal.addEventListener('click', e => { if (e.target === modal) modal.remove(); });

        // Confirm finalize
        modal.querySelector('#confirmFinalizeBtn').addEventListener('click', async () => {
            if (!state.uuid) return;

            const pdfTemplate = modal.querySelector('input[name="pdf_template"]:checked')?.value || 'detailed';

            const btn = modal.querySelector('#confirmFinalizeBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Finalizing…';

            try {
                const res  = await fetch(`${BASE_API}/finalize.php`, {
                    method: 'POST', headers: {'Content-Type':'application/json'},
                    body: JSON.stringify({ uuid: state.uuid, pdf_template: pdfTemplate })
                });
                const json = await res.json();
                if (!json.success) {
                    const errEl = modal.querySelector('#finalizeError');
                    errEl.textContent = json.message;
                    errEl.classList.remove('hidden');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-flag-checkered mr-1"></i> Confirm & Finalize';
                    return;
                }
                modal.remove();
                toast('success', 'Package finalized successfully!');
                // Open PDF modal so user can immediately download
                setTimeout(() => {
                    if (typeof PdfGenerator !== 'undefined') {
                        PdfGenerator.openAfterFinalize(state.sys_id);
                    } else {
                        window.location.href = 'index-packages.php';
                    }
                }, 600);
            } catch(e) {
                const errEl = modal.querySelector('#finalizeError');
                errEl.textContent = 'Network error: ' + e.message;
                errEl.classList.remove('hidden');
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-flag-checkered mr-1"></i> Confirm & Finalize';
            }
        });
    }

        function reviewSection(title, content, step) {
        return `
        <div class="border border-gray-200 rounded-2xl p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-gray-700">${title}</h3>
                <button class="review-edit text-xs text-blue-600 hover:text-blue-800 font-medium" data-step="${step}">
                    <i class="fa-solid fa-pen text-xs mr-1"></i>Edit
                </button>
            </div>
            ${content}
        </div>`;
    }




    // ─── Countries Load ──────────────────────────────────────────
    async function loadCountries() {
        try {
            const url  = (typeof API_COUNTRIES !== 'undefined') ? API_COUNTRIES : '../api/countries.php';
            const res  = await fetch(url);
            const json = await res.json();
            state.allCountries = json.data || json.countries || [];

            // Extract cities from each country's embedded cities array (DB source)
            // city.id = sys_id string e.g. "THR-26-CNT-01-CTS-01"
            // city.country_id = DB integer matching country.id
            state.allCities = [];
            state.allCountries.forEach(country => {
                if (Array.isArray(country.cities)) {
                    country.cities.forEach(city => state.allCities.push(city));
                }
            });

            // Fallback: if countries API didn't return cities embedded,
            // try the old flat cities array from JSON
            if (state.allCities.length === 0 && json.cities) {
                state.allCities = json.cities;
            }
        } catch(e) {
            console.error('Failed to load countries/cities:', e);
        }
    }

    // ─── Utilities ───────────────────────────────────────────────
    function showLoader(msg = 'Saving…') {
        const el = document.getElementById('builderLoader');
        document.getElementById('loaderMsg').textContent = msg;
        el.classList.remove('hidden');
        el.classList.add('flex');
    }
    function hideLoader() {
        const el = document.getElementById('builderLoader');
        el.classList.add('hidden');
        el.classList.remove('flex');
    }
    function toast(type, msg) {
        const el = document.getElementById('builderToast');
        if (!el) return;
        el.className = `fixed bottom-6 right-6 z-50 flex items-center gap-2 px-4 py-3 rounded-xl shadow-xl text-sm font-medium text-white
            ${type === 'success' ? 'bg-green-600' : 'bg-red-600'}`;
        el.innerHTML = `<i class="fa-solid fa-${type === 'success' ? 'circle-check' : 'circle-exclamation'}"></i> ${msg}`;
        el.classList.remove('hidden');
        setTimeout(() => el.classList.add('hidden'), 3500);
    }
    function escHtml(str) {
        return String(str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    return { init };
})();