/**
 * TravHub - Package Builder (Multi-Step Wizard)
 * package-builder.js
 */

const PackageBuilder = (() => {
    const BASE_API = '../api/packages';
    const TOTAL_STEPS = 8;

    let state = {
        uuid: null,
        sys_id: null,
        currentStep: 1,
        autoSaveTimer: null,
        steps: {
            1: { title:'', description:'', rating:0, image:'' },
            2: { countries:[], cities:[], activities:[] },
            3: { duration:'', start_date:'', end_date:'', no_of_pax:{adult:0,child:0,infant:0} },
            4: { hotels:[] },
            5: { currency_title:'', currency_code:'', currency_symbol:'', overall_price:'', air_ticket_details:'', pack_price:[] },
            6: { pack_itenaries:[] },
            7: { pack_inclusions:[], pack_exclusions:[] },
            8: {}
        },
        allCountries: [],
        allCities: [],
    };

    // ─── Init ───────────────────────────────────────────────────
    async function init() {
        renderShell();
        bindGlobalEvents();
        await loadCountries();

        const params = new URLSearchParams(window.location.search);
        
        // If returning from calculator, jump to step 5
        const calcParam = params.get('calc');
        if (calcParam === 'saved' && state.sys_id) {
            state.currentStep = 5;
        }
        
        const editUUID = params.get('uuid');
        if (editUUID) {
            await loadExistingPackage(editUUID);
        } else {
            showStep(1);
        }
        startAutoSave();
    }

    async function loadExistingPackage(uuid) {
        showLoader('Loading package…');
        try {
            const res  = await fetch(`${BASE_API}/get.php?uuid=${uuid}`);
            const json = await res.json();
            if (!json.success) throw new Error(json.message);
            const pkg = json.data;
            state.sys_id   = pkg.uuid;
            state.sys_id = pkg.sys_id;

            // Populate state
            state.steps[1] = { title: pkg.title||'', description: pkg.description||'', rating: pkg.rating||0, image: pkg.image||'' };
            state.steps[2] = { countries: pkg.countries||[], cities: pkg.cities||[], activities: pkg.activities||[] };
            state.steps[3] = { duration: pkg.duration||'', start_date: pkg.start_date||'', end_date: pkg.end_date||'', no_of_pax: pkg.no_of_pax||{adult:0,child:0,infant:0} };
            state.steps[4] = { hotels: pkg.hotels||[] };
            state.steps[5] = { currency_title: pkg.currency_title||'', currency_code: pkg.currency_code||'', currency_symbol: pkg.currency_symbol||'', overall_price: pkg.overall_price||'', air_ticket_details: pkg.air_ticket_details||'', pack_price: pkg.pack_price||[] };
            state.steps[6] = { pack_itenaries: pkg.pack_itenaries||[] };
            state.steps[7] = { pack_inclusions: pkg.pack_inclusions||[], pack_exclusions: pkg.pack_exclusions||[] };
            state.currentStep = pkg.progress_step || 1;
        } catch(e) { toast('error', e.message); state.currentStep = 1; }
        hideLoader();
        showStep(state.currentStep);
    }

    // ─── Shell ───────────────────────────────────────────────────
    function renderShell() {
        document.getElementById('mainContent').innerHTML = `
        <div class="px-6 py-6 max-w-screen-xl mx-auto">

            <!-- Header -->
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

            <!-- Step Indicator -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-6">
                <div class="flex items-center justify-between gap-1 overflow-x-auto" id="stepIndicator"></div>
            </div>

            <!-- Step Content -->
            <div id="stepContent" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6 min-h-[400px]">
                <div class="flex items-center justify-center py-20 text-gray-300">
                    <i class="fa-solid fa-spinner fa-spin text-3xl"></i>
                </div>
            </div>

            <!-- Navigation -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex items-center justify-between">
                <button id="btnPrev" class="flex items-center gap-2 px-5 py-2.5 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 font-medium transition disabled:opacity-40 disabled:cursor-not-allowed">
                    <i class="fa-solid fa-chevron-left text-xs"></i> Previous
                </button>
                <div class="text-sm text-gray-400" id="stepCounter">Step 1 of 8</div>
                <button id="btnNext" class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold transition">
                    Next <i class="fa-solid fa-chevron-right text-xs"></i>
                </button>
            </div>
        </div>

        <!-- Loader Overlay -->
        <div id="builderLoader" class="fixed inset-0 z-50 hidden items-center justify-center bg-white/80 backdrop-blur-sm flex-col gap-3">
            <i class="fa-solid fa-spinner fa-spin text-3xl text-blue-600"></i>
            <p id="loaderMsg" class="text-sm text-gray-500">Saving…</p>
        </div>

        <!-- Toast -->
        <div id="builderToast" class="fixed bottom-6 right-6 z-50 hidden items-center gap-2 px-4 py-3 rounded-xl shadow-xl text-sm font-medium text-white"></div>
        `;
    }

    // ─── Step Indicator ──────────────────────────────────────────
    const STEP_LABELS = ['Basic Info','Destination','Quotation','Accommodation','Pricing','Itinerary','Inc/Exc','Review'];

    function renderStepIndicator() {
        const el = document.getElementById('stepIndicator');
        el.innerHTML = STEP_LABELS.map((label, i) => {
            const n = i + 1;
            const done    = n < state.currentStep;
            const active  = n === state.currentStep;
            const future  = n > state.currentStep;
            return `
            <div class="flex items-center flex-1 min-w-0" style="min-width:0">
                <div class="flex flex-col items-center flex-1 cursor-pointer step-jump" data-step="${n}" title="${label}">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold transition
                        ${done   ? 'bg-green-500 text-white' : ''}
                        ${active ? 'bg-blue-600 text-white ring-4 ring-blue-100' : ''}
                        ${future ? 'bg-gray-100 text-gray-400' : ''}">
                        ${done ? '<i class="fa-solid fa-check text-xs"></i>' : n}
                    </div>
                    <span class="text-xs mt-1 whitespace-nowrap hidden sm:block ${active ? 'text-blue-600 font-semibold' : 'text-gray-400'}">${label}</span>
                </div>
                ${n < TOTAL_STEPS ? `<div class="h-0.5 flex-1 mx-1 ${done ? 'bg-green-400' : 'bg-gray-200'} transition hidden sm:block"></div>` : ''}
            </div>`;
        }).join('');

        el.querySelectorAll('.step-jump').forEach(btn => {
            btn.addEventListener('click', () => {
                const target = parseInt(btn.dataset.step);
                if (target < state.currentStep && state.sys_id) navigateToStep(target);
            });
        });

        document.getElementById('stepCounter').textContent = `Step ${state.currentStep} of ${TOTAL_STEPS}`;
        document.getElementById('btnPrev').disabled = state.currentStep === 1;
        const btnNext = document.getElementById('btnNext');
        if (state.currentStep === TOTAL_STEPS) {
            btnNext.innerHTML = '<i class="fa-solid fa-flag-checkered mr-1"></i> Finalize';
            btnNext.className = 'flex items-center gap-2 px-5 py-2.5 rounded-xl bg-green-600 hover:bg-green-700 text-white font-semibold transition';
        } else {
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

        if (!state.sys_id) {
            // First save - create package
            if (state.currentStep === 1) {
                const ok = await createPackage();
                if (!ok) return;
            }
        } else {
            await saveStep(state.currentStep);
        }

        if (state.currentStep < TOTAL_STEPS) {
            navigateToStep(state.currentStep + 1);
        } else {
            await finalizePackage();
        }
    }

    async function handlePrev() {
        if (state.currentStep > 1) {
            collectStepData();
            if (state.sys_id) await saveStep(state.currentStep);
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
        const fn = [null, renderStep1, renderStep2, renderStep3, renderStep4, renderStep5, renderStep6, renderStep7, renderStep8][n];
        if (fn) fn();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // ─── Create Package ─────────────────────────────────────────
    async function createPackage() {
        showLoader('Creating package…');
        try {
            const res  = await fetch(`${BASE_API}/create.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ title: state.steps[1].title, description: state.steps[1].description, rating: state.steps[1].rating })
            });
            const json = await res.json();
            if (!json.success) throw new Error(json.message);
            state.sys_id   = json.uuid;
            state.sys_id = json.sys_id;
            document.getElementById('builderSysId').textContent = json.sys_id;
            document.getElementById('builderTitle').textContent = state.steps[1].title || 'New Package';
            // Update URL without reload
            history.replaceState({}, '', `?uuid=${state.sys_id}`);
            hideLoader();
            return true;
        } catch(e) { hideLoader(); toast('error', e.message); return false; }
    }

    // ─── Save Step ───────────────────────────────────────────────
    async function saveStep(step, silent = false) {
        if (!state.sys_id) return;
        if (!silent) showLoader('Saving…');
        try {
            const res  = await fetch(`${BASE_API}/step-save.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ uuid: state.sys_id, step_number: step, step_data: state.steps[step] })
            });
            const json = await res.json();
            if (!json.success) throw new Error(json.message);
            if (!silent) toast('success', 'Saved');
        } catch(e) { if (!silent) toast('error', e.message); }
        if (!silent) hideLoader();
    }

    // ─── Auto-save ───────────────────────────────────────────────
    function startAutoSave() {
        state.autoSaveTimer = setInterval(async () => {
            if (state.sys_id && state.currentStep > 0 && state.currentStep < 8) {
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
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ uuid: state.sys_id, step_number: 8, step_data: {} })
            });
            const json = await res.json();
            hideLoader();
            if (json.success) {
                toast('success', 'Package completed!');
                setTimeout(() => window.location.href = 'index-packages.php', 1500);
            } else { toast('error', json.message); }
        } catch(e) { hideLoader(); toast('error', e.message); }
    }

    // ─── Collect Step Data ───────────────────────────────────────
    function collectStepData() {
        const s = state.currentStep;
        const get = id => { const el = document.getElementById(id); return el ? el.value : ''; };

        if (s === 1) {
            state.steps[1].title       = get('s1_title');
            state.steps[1].description = get('s1_description');
            state.steps[1].rating      = parseInt(get('s1_rating_val') || 0);
        }
        if (s === 3) {
            state.steps[3].duration   = get('s3_duration');
            state.steps[3].start_date = get('s3_start_date');
            state.steps[3].end_date   = get('s3_end_date');
            state.steps[3].no_of_pax  = {
                adult:  parseInt(get('s3_adult')||0),
                child:  parseInt(get('s3_child')||0),
                infant: parseInt(get('s3_infant')||0),
            };
        }
        if (s === 5) {
            state.steps[5].currency_title    = get('s5_currency_title');
            state.steps[5].currency_code     = get('s5_currency_code');
            state.steps[5].currency_symbol   = get('s5_currency_symbol');
            state.steps[5].overall_price     = parseFloat(get('s5_overall_price')||0);
            state.steps[5].air_ticket_details = get('s5_air_ticket');
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

    // ─── Step 1: Basic Info ──────────────────────────────────────
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

            <!-- Image Upload -->
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
        </div>
        `;

        // Star rating
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

        // Image upload
        const fileInput = document.getElementById('imgFileInput');
        const dropZone  = document.getElementById('imgDropZone');
        if (fileInput) {
            fileInput.addEventListener('change', e => handleImageUpload(e.target.files[0]));
        }
        if (dropZone) {
            dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('border-blue-400'); });
            dropZone.addEventListener('drop', e => { e.preventDefault(); handleImageUpload(e.dataTransfer.files[0]); });
        }
        document.getElementById('removeImg')?.addEventListener('click', e => {
            e.stopPropagation();
            state.steps[1].image = '';
            renderStep1();
        });
    }

    async function handleImageUpload(file) {
        if (!file) return;
        if (!state.sys_id) {
            toast('error', 'Save basic info first (click Next once)');
            return;
        }
        const formData = new FormData();
        formData.append('uuid', state.sys_id);
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

    // ─── Step 2: Destination ─────────────────────────────────────
    function renderStep2() {
        const d = state.steps[2];
        document.getElementById('stepContent').innerHTML = `
        <h2 class="text-lg font-bold text-gray-800 mb-1">Destination</h2>
        <p class="text-sm text-gray-400 mb-6">Select countries, cities, and activities</p>

        <div class="space-y-6">
            <!-- Countries -->
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

            <!-- Cities -->
            <div id="citiesSection" class="${d.countries.length ? '' : 'hidden'}">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Cities</label>
                <div id="cityList" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2 max-h-48 overflow-y-auto p-1"></div>
                <div id="selectedCities" class="flex flex-wrap gap-2 mt-2"></div>
            </div>

            <!-- Activities -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Activities</label>
                <div class="flex gap-2 mb-2">
                    <input id="activityInput" type="text" placeholder="e.g. City Tour, Safari, Snorkeling…"
                           class="flex-1 px-4 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <button id="addActivityBtn" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-medium transition flex items-center gap-1">
                        <i class="fa-solid fa-plus text-xs"></i> Add
                    </button>
                </div>
                <div id="activityList" class="flex flex-wrap gap-2"></div>
            </div>
        </div>`;

        renderCountryGrid();
        renderCityGrid();
        renderActivities();

        // Country search
        document.getElementById('countrySearch').addEventListener('input', e => renderCountryGrid(e.target.value));

        // Activity add
        const addBtn = document.getElementById('addActivityBtn');
        const actInput = document.getElementById('activityInput');
        const doAddActivity = () => {
            const val = actInput.value.trim();
            if (!val) return;
            const exists = state.steps[2].activities.find(a => a.title.toLowerCase() === val.toLowerCase());
            if (!exists) {
                state.steps[2].activities.push({ id: Date.now(), title: val });
                renderActivities();
            }
            actInput.value = '';
        };
        addBtn.addEventListener('click', doAddActivity);
        actInput.addEventListener('keydown', e => { if (e.key === 'Enter') doAddActivity(); });
    }

    function renderCountryGrid(search = '') {
        const list = document.getElementById('countryList');
        const sel  = document.getElementById('selectedCountries');
        const filtered = state.allCountries.filter(c => c.name.toLowerCase().includes(search.toLowerCase()));

        list.innerHTML = filtered.map(c => {
            const selected = state.steps[2].countries.find(x => x.id === c.id);
            return `<button type="button" data-cid="${c.id}"
                            class="country-btn text-xs px-3 py-2 rounded-xl border font-medium transition
                                   ${selected ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'}">
                        ${c.code} · ${c.name}</button>`;
        }).join('');

        list.querySelectorAll('.country-btn').forEach(btn => {
            btn.addEventListener('click', () => toggleCountry(parseInt(btn.dataset.cid)));
        });

        // Selected badges
        sel.innerHTML = state.steps[2].countries.map(c =>
            `<span class="inline-flex items-center gap-1 bg-blue-100 text-blue-800 text-xs px-3 py-1 rounded-full font-medium">
                ${c.name}
                <button data-cid="${c.id}" class="remove-country ml-1 text-blue-600 hover:text-blue-900">×</button>
             </span>`).join('');
        sel.querySelectorAll('.remove-country').forEach(b => b.addEventListener('click', () => toggleCountry(parseInt(b.dataset.cid))));
    }

    function toggleCountry(cid) {
        const country = state.allCountries.find(c => c.id === cid);
        if (!country) return;
        const idx = state.steps[2].countries.findIndex(c => c.id === cid);
        if (idx >= 0) {
            state.steps[2].countries.splice(idx, 1);
            state.steps[2].cities = state.steps[2].cities.filter(city => city.country_id !== cid);
        } else {
            state.steps[2].countries.push(country);
        }
        renderCountryGrid(document.getElementById('countrySearch')?.value || '');
        renderCityGrid();
        document.getElementById('citiesSection')?.classList.toggle('hidden', !state.steps[2].countries.length);
    }

    function renderCityGrid() {
        const selectedCountryIds = state.steps[2].countries.map(c => c.id);
        const availableCities    = state.allCities.filter(c => selectedCountryIds.includes(c.country_id));
        const listEl = document.getElementById('cityList');
        const selEl  = document.getElementById('selectedCities');
        if (!listEl) return;

        listEl.innerHTML = availableCities.map(c => {
            const selected = state.steps[2].cities.find(x => x.id === c.id);
            const country  = state.allCountries.find(x => x.id === c.country_id);
            return `<button type="button" data-cityid="${c.id}"
                            class="city-btn text-xs px-3 py-2 rounded-xl border font-medium transition
                                   ${selected ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'}">
                        ${c.name}<span class="opacity-60 ml-1">(${country?.code||''})</span></button>`;
        }).join('');

        listEl.querySelectorAll('.city-btn').forEach(btn => {
            btn.addEventListener('click', () => toggleCity(parseInt(btn.dataset.cityid)));
        });

        if (selEl) selEl.innerHTML = state.steps[2].cities.map(c =>
            `<span class="inline-flex items-center gap-1 bg-emerald-100 text-emerald-800 text-xs px-3 py-1 rounded-full font-medium">
                ${c.name}
                <button data-cityid="${c.id}" class="remove-city ml-1 text-emerald-600 hover:text-emerald-900">×</button>
             </span>`).join('');
        selEl?.querySelectorAll('.remove-city').forEach(b => b.addEventListener('click', () => toggleCity(parseInt(b.dataset.cityid))));
    }

    function toggleCity(cityId) {
        const city = state.allCities.find(c => c.id === cityId);
        if (!city) return;
        const idx = state.steps[2].cities.findIndex(c => c.id === cityId);
        if (idx >= 0) state.steps[2].cities.splice(idx, 1);
        else state.steps[2].cities.push(city);
        renderCityGrid();
    }

    function renderActivities() {
        const el = document.getElementById('activityList');
        if (!el) return;
        el.innerHTML = state.steps[2].activities.map((a,i) =>
            `<span class="inline-flex items-center gap-1 bg-violet-100 text-violet-800 text-xs px-3 py-1.5 rounded-full font-medium">
                <i class="fa-solid fa-person-hiking text-xs"></i> ${escHtml(a.title)}
                <button data-idx="${i}" class="remove-act ml-1 text-violet-600 hover:text-violet-900">×</button>
             </span>`).join('');
        el.querySelectorAll('.remove-act').forEach(b => b.addEventListener('click', () => {
            state.steps[2].activities.splice(parseInt(b.dataset.idx), 1);
            renderActivities();
        }));
    }

    // ─── Step 3: Quotation ───────────────────────────────────────
    function renderStep3() {
        const d = state.steps[3];
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

    // ─── Step 4: Accommodation ───────────────────────────────────
    function renderStep4() {
        const cities = state.steps[2].cities;
        document.getElementById('stepContent').innerHTML = `
        <h2 class="text-lg font-bold text-gray-800 mb-1">Accommodation</h2>
        <p class="text-sm text-gray-400 mb-6">Add hotels for each city</p>
        ${cities.length === 0
            ? `<div class="text-center py-12 text-gray-400">
                   <i class="fa-solid fa-city text-4xl mb-3 block opacity-30"></i>
                   <p>No cities selected. Go back to Step 2 to select cities.</p>
               </div>`
            : `<div class="space-y-5" id="hotelsContainer">${cities.map(city => renderCityHotels(city)).join('')}</div>`}`;

        if (cities.length) rebindHotelEvents();
    }

    function renderCityHotels(city) {
        const cityHotels = state.steps[4].hotels.filter(h => h.city_id === city.id);
        return `
        <div class="border border-gray-200 rounded-2xl p-4" data-city-id="${city.id}">
            <div class="flex items-center gap-2 mb-3">
                <i class="fa-solid fa-location-dot text-blue-500"></i>
                <h3 class="font-semibold text-gray-700">${escHtml(city.name)}</h3>
            </div>
            <div class="space-y-2" id="hotelsList_${city.id}">
                ${cityHotels.map((h,i) => hotelRowHTML(city.id, i, h)).join('')}
            </div>
            <button type="button" data-city-id="${city.id}" class="add-hotel-btn mt-3 text-sm text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1">
                <i class="fa-solid fa-plus-circle"></i> Add Hotel
            </button>
        </div>`;
    }

    function hotelRowHTML(cityId, idx, hotel = {}) {
        return `
        <div class="flex items-center gap-2 hotel-row" data-city-id="${cityId}" data-idx="${idx}">
            <input type="text" placeholder="Hotel name" value="${escHtml(hotel.hotel_title||'')}"
                   class="flex-1 px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 hotel-name">
            <select class="px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 hotel-type">
                ${['3 Star','4 Star','5 Star','Budget','Boutique','Resort'].map(t=>
                    `<option value="${t}" ${hotel.type===t?'selected':''}>${t}</option>`).join('')}
            </select>
            <button type="button" class="remove-hotel w-8 h-8 flex items-center justify-center rounded-xl bg-red-50 text-red-500 hover:bg-red-100 transition text-xs">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>`;
    }

    function rebindHotelEvents() {
        document.querySelectorAll('.add-hotel-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const cityId = parseInt(btn.dataset.cityId);
                const city   = state.steps[2].cities.find(c => c.id === cityId);
                state.steps[4].hotels.push({ city_id: cityId, city_name: city?.name||'', hotel_title:'', type:'3 Star' });
                const container = document.getElementById(`hotelsList_${cityId}`);
                const idx = state.steps[4].hotels.filter(h => h.city_id === cityId).length - 1;
                container.insertAdjacentHTML('beforeend', hotelRowHTML(cityId, idx));
                rebindHotelEvents();
            });
        });

        document.querySelectorAll('.remove-hotel').forEach(btn => {
            btn.addEventListener('click', () => {
                const row = btn.closest('.hotel-row');
                const cityId = parseInt(row.dataset.cityId);
                const idx    = parseInt(row.dataset.idx);
                const cityHotels = state.steps[4].hotels.filter(h => h.city_id === cityId);
                const globalIdx  = state.steps[4].hotels.indexOf(cityHotels[idx]);
                if (globalIdx >= 0) state.steps[4].hotels.splice(globalIdx, 1);
                row.remove();
            });
        });

        // Sync hotel input changes to state
        document.querySelectorAll('.hotel-row').forEach(row => {
            const cityId = parseInt(row.dataset.cityId);
            const idx    = parseInt(row.dataset.idx);
            const cityHotels = state.steps[4].hotels.filter(h => h.city_id === cityId);
            const hotel  = cityHotels[idx];
            if (!hotel) return;
            row.querySelector('.hotel-name').addEventListener('input', e => { hotel.hotel_title = e.target.value; });
            row.querySelector('.hotel-type').addEventListener('change', e => { hotel.type = e.target.value; });
        });
    }

    // ─── Step 5: Pricing ─────────────────────────────────────────
    function renderStep5() {
        const d = state.steps[5];
        document.getElementById('stepContent').innerHTML = `
        <h2 class="text-lg font-bold text-gray-800 mb-1">Pricing</h2>
        <p class="text-sm text-gray-400 mb-6">Set currency, price, and pricing options</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Currency Name</label>
                    <input id="s5_currency_title" type="text" value="${escHtml(d.currency_title)}" placeholder="e.g. US Dollar"
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Currency Code</label>
                        <input id="s5_currency_code" type="text" value="${escHtml(d.currency_code)}" placeholder="USD"
                               class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Symbol</label>
                        <input id="s5_currency_symbol" type="text" value="${escHtml(d.currency_symbol)}" placeholder="$"
                               class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Overall Price</label>
                    <input id="s5_overall_price" type="number" min="0" value="${d.overall_price||''}" placeholder="0.00"
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Air Ticket Details</label>
                <textarea id="s5_air_ticket" rows="6" placeholder="Flight details, airline, class…"
                          class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 resize-none">${escHtml(d.air_ticket_details)}</textarea>
            </div>
        </div>

        <!-- Pack Price Options -->
        <div>
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-gray-700">Pricing Options</h3>
                <button id="addPriceOption" class="text-sm bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl font-medium transition flex items-center gap-1">
                    <i class="fa-solid fa-plus text-xs"></i> Add Option
                </button>
            </div>
            <div id="priceOptionsList" class="space-y-3"></div>
        </div>

        <!-- Calculator Link -->
        <div class="col-span-full mt-4">
            <div id="calcStatusBanner" class="hidden bg-green-50 border border-green-200 rounded-xl p-3 flex items-center gap-3 mb-3">
                <i class="fa-solid fa-circle-check text-green-500"></i>
                <div class="flex-1">
                    <p class="text-sm font-semibold text-green-700">Advanced calculation saved</p>
                    <p id="calcGrandTotal" class="text-xs text-green-600 mt-0.5"></p>
                </div>
                <a id="editCalcLink" href="#"
                   class="text-xs text-green-700 underline hover:text-green-900 font-medium">Edit Calculation</a>
            </div>
            <button id="openCalculatorBtn" type="button"
                    class="flex items-center gap-2 bg-violet-600 hover:bg-violet-700 text-white font-semibold px-5 py-2.5 rounded-xl shadow transition">
                <i class="fa-solid fa-calculator"></i> Open Advanced Calculator
            </button>
        </div>`;

        renderPriceOptions();
        document.getElementById('addPriceOption').addEventListener('click', () => {
            state.steps[5].pack_price.push({ id: Date.now(), title:'', price:'', hotels:{} });
            renderPriceOptions();
        });
        
        if (state.sys_id) {
            fetch(`../api/package-calculation/get.php?packageId=${state.sys_id}`)
                .then(r => r.json())
                .then(json => {
                    if (json.success && json.exists) {
                        const banner    = document.getElementById('calcStatusBanner');
                        const totalEl   = document.getElementById('calcGrandTotal');
                        const editLink  = document.getElementById('editCalcLink');
                        if (banner) {
                            banner.classList.remove('hidden');
                            totalEl.textContent = `Grand Total: ৳ ${Number(json.data.grand_total).toLocaleString()} BDT  ·  Mode: ${json.data.mode}`;
                            editLink.href = `package-calculation.php?packageId=${state.sys_id}`;
                        }
                    }
                })
                .catch(() => {});
        }
        
        // Open Calculator button
        document.getElementById('openCalculatorBtn')?.addEventListener('click', () => {
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
        el.innerHTML = state.steps[5].pack_price.map((opt, i) => `
        <div class="border border-gray-200 rounded-xl p-4 price-opt" data-idx="${i}">
            <div class="flex items-center gap-3 mb-3">
                <input type="text" placeholder="Option title (e.g. Standard)" value="${escHtml(opt.title)}"
                       class="flex-1 px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 opt-title">
                <input type="number" placeholder="Price" value="${opt.price||''}"
                       class="w-32 px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 opt-price">
                <button type="button" data-idx="${i}" class="remove-opt w-8 h-8 flex items-center justify-center rounded-xl bg-red-50 text-red-500 hover:bg-red-100 transition text-xs">
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
            row.querySelector('.opt-title').addEventListener('input', e => { state.steps[5].pack_price[i].title = e.target.value; });
            row.querySelector('.opt-price').addEventListener('input', e => { state.steps[5].pack_price[i].price = e.target.value; });
            row.querySelectorAll('.opt-hotel').forEach(sel => {
                sel.addEventListener('change', e => {
                    const cityId = parseInt(sel.dataset.city);
                    state.steps[5].pack_price[i].hotels[cityId] = e.target.value;
                });
            });
        });
        el.querySelectorAll('.remove-opt').forEach(btn => {
            btn.addEventListener('click', () => {
                state.steps[5].pack_price.splice(parseInt(btn.dataset.idx), 1);
                renderPriceOptions();
            });
        });
    }

    // ─── Step 6: Itinerary ───────────────────────────────────────
    function renderStep6() {
        document.getElementById('stepContent').innerHTML = `
        <h2 class="text-lg font-bold text-gray-800 mb-1">Itinerary</h2>
        <p class="text-sm text-gray-400 mb-4">Build your day-by-day travel plan</p>
        <button id="addDayBtn" class="mb-4 text-sm bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl font-medium transition flex items-center gap-1">
            <i class="fa-solid fa-plus text-xs"></i> Add Day
        </button>
        <div id="itineraryDays" class="space-y-4"></div>`;

        renderItineraryDays();
        document.getElementById('addDayBtn').addEventListener('click', () => {
            const days = state.steps[6].pack_itenaries;
            days.push({ day_number: days.length + 1, title:'', date:'', overnight_stay:'', meals:[], activities:[] });
            renderItineraryDays();
        });
    }

    function renderItineraryDays() {
        const el = document.getElementById('itineraryDays');
        if (!el) return;
        const days = state.steps[6].pack_itenaries;
        const mealOptions = ['Breakfast','Lunch','Dinner','Snacks'];

        el.innerHTML = days.map((day, i) => `
        <div class="border border-gray-200 rounded-2xl overflow-hidden">
            <div class="bg-gray-50 px-4 py-3 flex items-center justify-between">
                <span class="font-bold text-gray-700 text-sm">Day ${day.day_number}</span>
                <button type="button" data-idx="${i}" class="remove-day text-red-400 hover:text-red-600 text-xs transition">
                    <i class="fa-solid fa-trash"></i> Remove
                </button>
            </div>
            <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
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
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Overnight Stay (City)</label>
                    <select class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 day-overnight" data-idx="${i}">
                        <option value="">-- Select city --</option>
                        ${state.steps[2].cities.map(c=>`<option value="${escHtml(c.name)}" ${day.overnight_stay===c.name?'selected':''}>${escHtml(c.name)}</option>`).join('')}
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Meals</label>
                    <div class="flex flex-wrap gap-2">
                        ${mealOptions.map(m=>`
                        <label class="inline-flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" value="${m}" class="day-meal rounded" data-idx="${i}"
                                   ${(day.meals||[]).includes(m)?'checked':''}>
                            <span class="text-xs text-gray-600">${m}</span>
                        </label>`).join('')}
                    </div>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Activities</label>
                    <div class="flex flex-wrap gap-1.5 mb-2">
                        ${state.steps[2].activities.map(a=>`
                        <button type="button" data-act="${escHtml(a.title)}" data-idx="${i}"
                                class="act-toggle text-xs px-2.5 py-1 rounded-full border font-medium transition
                                       ${(day.activities||[]).includes(a.title)?'bg-violet-600 text-white border-violet-600':'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'}">
                            ${escHtml(a.title)}</button>`).join('')}
                    </div>
                </div>
            </div>
        </div>`).join('');

        // Bind all day inputs
        el.querySelectorAll('.day-title').forEach(inp => {
            inp.addEventListener('input', e => { days[parseInt(e.target.dataset.idx)].title = e.target.value; });
        });
        el.querySelectorAll('.day-date').forEach(inp => {
            inp.addEventListener('input', e => { days[parseInt(e.target.dataset.idx)].date = e.target.value; });
        });
        el.querySelectorAll('.day-overnight').forEach(sel => {
            sel.addEventListener('change', e => { days[parseInt(e.target.dataset.idx)].overnight_stay = e.target.value; });
        });
        el.querySelectorAll('.day-meal').forEach(cb => {
            cb.addEventListener('change', e => {
                const d = days[parseInt(cb.dataset.idx)];
                if (!d.meals) d.meals = [];
                if (cb.checked && !d.meals.includes(cb.value)) d.meals.push(cb.value);
                else d.meals = d.meals.filter(m => m !== cb.value);
            });
        });
        el.querySelectorAll('.act-toggle').forEach(btn => {
            btn.addEventListener('click', () => {
                const d = days[parseInt(btn.dataset.idx)];
                if (!d.activities) d.activities = [];
                const act = btn.dataset.act;
                const has = d.activities.includes(act);
                if (has) d.activities = d.activities.filter(a => a !== act);
                else d.activities.push(act);
                btn.classList.toggle('bg-violet-600', !has);
                btn.classList.toggle('text-white', !has);
                btn.classList.toggle('border-violet-600', !has);
                btn.classList.toggle('bg-white', has);
                btn.classList.toggle('text-gray-600', has);
                btn.classList.toggle('border-gray-200', has);
            });
        });
        el.querySelectorAll('.remove-day').forEach(btn => {
            btn.addEventListener('click', () => {
                days.splice(parseInt(btn.dataset.idx), 1);
                days.forEach((d, i) => d.day_number = i + 1);
                renderItineraryDays();
            });
        });
    }

    // ─── Step 7: Inclusions & Exclusions ─────────────────────────
    const FA_ICONS = ['fa-check-circle','fa-plane','fa-hotel','fa-bus','fa-utensils','fa-camera','fa-mountain','fa-ship','fa-ticket','fa-umbrella-beach','fa-passport','fa-suitcase','fa-headset','fa-wifi','fa-car'];

    function renderStep7() {
        document.getElementById('stepContent').innerHTML = `
        <h2 class="text-lg font-bold text-gray-800 mb-1">Inclusions & Exclusions</h2>
        <p class="text-sm text-gray-400 mb-6">Define what's included and excluded in the package</p>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Inclusions -->
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

            <!-- Exclusions -->
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

        // Render sub-titles
        state.steps[7].pack_inclusions.forEach((inc, i) => {
            const subEl = document.getElementById(`incSubs_${i}`);
            if (subEl) subEl.innerHTML = (inc.sub_titles||[]).map((s, si) => `
            <div class="flex items-center gap-1">
                <input type="text" value="${escHtml(s)}" placeholder="Sub-item"
                       class="flex-1 px-2 py-1 border border-gray-200 rounded-lg text-xs focus:outline-none inc-sub" data-i="${i}" data-si="${si}">
                <button data-i="${i}" data-si="${si}" class="remove-sub text-red-400 hover:text-red-600 text-xs"><i class="fa-solid fa-times"></i></button>
            </div>`).join('');
        });

        el.querySelectorAll('.inc-title').forEach(inp => {
            inp.addEventListener('input', e => { state.steps[7].pack_inclusions[parseInt(e.target.dataset.idx)].title = e.target.value; });
        });
        el.querySelectorAll('.inc-icon').forEach(sel => {
            sel.addEventListener('change', e => {
                const idx = parseInt(e.target.dataset.idx);
                state.steps[7].pack_inclusions[idx].icon = e.target.value;
                const row = sel.closest('[data-idx]') || sel.parentElement;
                const preview = row.querySelector('.inc-preview-icon');
                if (preview) preview.className = `fa-solid ${e.target.value} text-green-500 w-5 inc-preview-icon`;
            });
        });
        el.querySelectorAll('.remove-inc').forEach(btn => {
            btn.addEventListener('click', () => {
                state.steps[7].pack_inclusions.splice(parseInt(btn.dataset.idx), 1);
                renderInclusions();
            });
        });
        el.querySelectorAll('.add-sub').forEach(btn => {
            btn.addEventListener('click', () => {
                state.steps[7].pack_inclusions[parseInt(btn.dataset.idx)].sub_titles.push('');
                renderInclusions();
            });
        });
        el.querySelectorAll('.inc-sub').forEach(inp => {
            inp.addEventListener('input', e => {
                state.steps[7].pack_inclusions[parseInt(e.target.dataset.i)].sub_titles[parseInt(e.target.dataset.si)] = e.target.value;
            });
        });
        el.querySelectorAll('.remove-sub').forEach(btn => {
            btn.addEventListener('click', () => {
                state.steps[7].pack_inclusions[parseInt(btn.dataset.i)].sub_titles.splice(parseInt(btn.dataset.si), 1);
                renderInclusions();
            });
        });
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

        el.querySelectorAll('.exc-input').forEach(inp => {
            inp.addEventListener('input', e => { state.steps[7].pack_exclusions[parseInt(e.target.dataset.idx)] = e.target.value; });
        });
        el.querySelectorAll('.remove-exc').forEach(btn => {
            btn.addEventListener('click', () => {
                state.steps[7].pack_exclusions.splice(parseInt(btn.dataset.idx), 1);
                renderExclusions();
            });
        });
    }

    // ─── Step 8: Review ──────────────────────────────────────────
    function renderStep8() {
        const s = state.steps;
        const sym = s[5].currency_symbol || '';
        document.getElementById('stepContent').innerHTML = `
        <h2 class="text-lg font-bold text-gray-800 mb-1">Final Review</h2>
        <p class="text-sm text-gray-400 mb-6">Review all details before finalizing</p>

        <div class="space-y-5">
            ${reviewSection('Basic Info', `
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div><span class="text-gray-400">Title:</span> <strong>${escHtml(s[1].title)}</strong></div>
                    <div><span class="text-gray-400">Rating:</span> <strong>${s[1].rating} ★</strong></div>
                    <div class="col-span-2"><span class="text-gray-400">Description:</span> ${escHtml(s[1].description)||'—'}</div>
                </div>`, '1')}

            ${reviewSection('Destination', `
                <p class="text-sm text-gray-500">Countries: ${(s[2].countries||[]).map(c=>c.name).join(', ')||'None'}</p>
                <p class="text-sm text-gray-500">Cities: ${(s[2].cities||[]).map(c=>c.name).join(', ')||'None'}</p>
                <p class="text-sm text-gray-500">Activities: ${(s[2].activities||[]).map(a=>a.title).join(', ')||'None'}</p>`, '2')}

            ${reviewSection('Quotation', `
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div><span class="text-gray-400">Duration:</span> ${escHtml(s[3].duration)||'—'}</div>
                    <div><span class="text-gray-400">Dates:</span> ${s[3].start_date||'—'} → ${s[3].end_date||'—'}</div>
                    <div><span class="text-gray-400">Pax:</span> ${s[3].no_of_pax?.adult||0}A / ${s[3].no_of_pax?.child||0}C / ${s[3].no_of_pax?.infant||0}I</div>
                </div>`, '3')}

            ${reviewSection('Pricing', `
                <div class="text-sm">
                    <div><span class="text-gray-400">Currency:</span> ${escHtml(s[5].currency_title)} (${escHtml(s[5].currency_code)}) ${escHtml(s[5].currency_symbol)}</div>
                    <div class="mt-1"><span class="text-gray-400">Overall Price:</span> <strong class="text-blue-600">${sym}${Number(s[5].overall_price||0).toLocaleString()}</strong></div>
                    <div class="mt-1 text-gray-400">${s[5].pack_price?.length||0} pricing option(s)</div>
                </div>`, '5')}

            ${reviewSection('Itinerary', `
                <p class="text-sm text-gray-500">${s[6].pack_itenaries?.length||0} day(s) planned</p>
                ${(s[6].pack_itenaries||[]).map(d=>`<p class="text-xs text-gray-400 mt-1">Day ${d.day_number}: ${escHtml(d.title)||'—'} ${d.overnight_stay?'('+d.overnight_stay+')':''}</p>`).join('')}
                `, '6')}

            ${reviewSection('Inclusions & Exclusions', `
                <p class="text-sm text-gray-500">${s[7].pack_inclusions?.length||0} inclusion(s) · ${s[7].pack_exclusions?.length||0} exclusion(s)</p>`, '7')}
        </div>

        <div class="mt-6 p-4 bg-green-50 border border-green-200 rounded-2xl text-center">
            <i class="fa-solid fa-circle-check text-green-500 text-2xl mb-2 block"></i>
            <p class="text-sm font-semibold text-green-700">Ready to finalize</p>
            <p class="text-xs text-green-600 mt-0.5">Click "Finalize" to mark this package as completed.</p>
        </div>`;

        document.querySelectorAll('.review-edit').forEach(btn => {
            btn.addEventListener('click', () => navigateToStep(parseInt(btn.dataset.step)));
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
    
    // console.log(API_COUNTRIES);

    // ─── Countries Load ──────────────────────────────────────────
    async function loadCountries() {
        try {
            const res  = await fetch(API_COUNTRIES);
            const json = await res.json();
            state.allCountries = json.data || [];
            const citiesRes  = await fetch(API_CITIES);
            const citiesJson = await citiesRes.json();
            state.allCities  = citiesJson.data || [];
        } catch(e) { console.error('Failed to load countries:', e); }
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