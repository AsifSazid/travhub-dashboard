/**
 * TravHub — MdActivities (Full Version)
 * ═══════════════════════════════════════════════════════
 * Complete activity management with all API fields
 */

const MdActivities = (() => {
    'use strict';

    const TYPES        = ['tour','transfer','both'];
    const TRANSPORT_MODES = ['none','sic','sedan','suv','van','minibus','coach','boat'];
    const PRICE_BASES  = ['per_pax','per_group'];
    const OPERATING_DAYS = ['mon','tue','wed','thu','fri','sat','sun'];
    const PREDEFINED_TAGS = [
        'beach','family','adventure','luxury','honeymoon','cultural',
        'sightseeing','nature','water_sports','theme_park','nightlife',
        'shopping','religious','wildlife','city_tour',
    ];

    const st = {
        page: 1, limit: 12, search: '', country: '', type: '', tag: '', status: 'active',
        timer: null, viewActivity: null, viewActivityCountry: null, viewActivityCurrency: ''
    };

    let countriesCache = [];
    let citiesCache = [];
    let vendorsCache = [];
    let pendingTags = [];

    // ── Init ──────────────────────────────────────────────────────────
    function init() { 
        renderShell(); 
        bindEvents(); 
        loadCountries().then(() => { 
            loadCities('');
            loadTagFilter(); 
            load(); 
        }); 
    }

    async function loadCountries() {
        const d = await thApi(`${API_BASE}api/masterdata/countries/list.php?limit=200&status=active&for_package=1`);
        countriesCache = d.data || [];
        
        ['fltCountry', 'fACountry'].forEach(id => {
            const el = document.getElementById(id); 
            if (!el) return;
            const lbl = id === 'fltCountry' ? 'All Countries' : 'Select country';
            el.innerHTML = `<option value="">${lbl}</option>` +
                countriesCache.map(c =>
                    `<option value="${c.sys_id}" 
                        data-name="${esc(c.name)}" 
                        data-currency="${c.currency_code}"
                        data-cities='${JSON.stringify(c.cities || [])}'>
                        ${esc(c.name)} (${c.cities?.length || 0} cities)
                    </option>`
                ).join('');
        });

        // দেশ নির্বাচনের ইভেন্ট
        const countrySelect = document.getElementById('fACountry');
        if (countrySelect) {
            countrySelect.onchange = (e) => {
                const selectedCountry = countriesCache.find(c => c.sys_id === e.target.value);
                if (selectedCountry) {
                    loadCities(selectedCountry.sys_id);
                    // currency ও আপডেট করা যায় চাইলে
                    if (st.viewActivityCurrency) {
                        st.viewActivityCurrency = selectedCountry.currency_code;
                    }
                } else {
                    loadCities('');
                }
            };
        }
    }

    function loadCities(countrySysId) {
        const citySelect = document.getElementById('fACity');
        if (!countrySysId) {
            citySelect.innerHTML = '<option value="">Select country first</option>';
            return;
        }
        
        // দেশের তথ্য খুঁজে বের করা
        const country = countriesCache.find(c => c.sys_id === countrySysId);
        
        if (!country || !country.cities || country.cities.length === 0) {
            citySelect.innerHTML = '<option value="">No cities available</option>';
            return;
        }
        
        // শহরগুলो সিলেক্টে দেখানো
        citySelect.innerHTML = '<option value="">Select city</option>' +
            country.cities.map(city => 
                `<option value="${city.sys_id || city.id || city.name}" data-name="${esc(city.name)}">
                    ${esc(city.name)}
                </option>`
            ).join('');
    }

    async function loadTagFilter() {
        const d  = await thApi(`${API_BASE}api/masterdata/activities/tag-list.php?all=1`);
        const el = document.getElementById('fltTag'); 
        if (!el) return;
        const usedTags = (d.data || []).map(t => t.tag);
        const allTags  = [...new Set([...PREDEFINED_TAGS, ...usedTags])].sort();
        el.innerHTML   = `<option value="">All Tags</option>` +
            allTags.map(t => `<option value="${t}">${t.replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase())}</option>`).join('');
    }

    // ── Shell ─────────────────────────────────────────────────────────
    function renderShell() {
        document.getElementById('mainContent').innerHTML = `
        <div class="px-6 py-6 max-w-screen-xl mx-auto">

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-[#1A2039]">Activities</h1>
                    <p class="text-sm text-gray-400 mt-0.5">Tours, transfers and priced variants</p>
                </div>
                <button id="btnAdd"
                    class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold bg-[#1A2039] hover:bg-[#252d4a] text-white transition shadow-sm">
                    <i class="fa-solid fa-plus"></i> Add Activity
                </button>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-5 flex flex-col sm:flex-row gap-3 flex-wrap">
                <div class="relative flex-1 min-w-48">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input id="srch" type="text" placeholder="Search activity…"
                        class="w-full pl-9 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#50BC81]">
                </div>
                <select id="fltCountry"
                    class="text-sm px-3 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#50BC81] bg-white"></select>
                <select id="fltType"
                    class="text-sm px-3 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#50BC81] bg-white">
                    <option value="">All Types</option>
                    ${TYPES.map(t => `<option value="${t}">${t.charAt(0).toUpperCase()+t.slice(1)}</option>`).join('')}
                </select>
                <select id="fltTag"
                    class="text-sm px-3 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#50BC81] bg-white">
                    <option value="">All Tags</option>
                </select>
                <div class="flex gap-2">
                    ${['active','trash'].map(t => `
                    <button data-tab="${t}" class="tab-btn px-4 py-2 text-sm font-medium rounded-xl border transition
                        ${t === 'active' ? 'bg-[#1A2039] text-white border-[#1A2039]' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'}">
                        ${t === 'active' ? 'Active' : '<i class="fa-solid fa-trash mr-1"></i>Trash'}
                    </button>`).join('')}
                </div>
            </div>

            <!-- Card grid -->
            <div id="actGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5 mb-5"></div>
            <div id="tLoading" class="text-center py-16 text-gray-300">
                <i class="fa-solid fa-spinner fa-spin text-3xl"></i>
            </div>
            <div id="tEmpty" class="hidden text-center py-16 text-gray-300">
                <i class="fa-solid fa-person-hiking text-5xl mb-3 block opacity-30"></i>
                <p class="text-sm font-medium">No activities found</p>
            </div>
            <div id="pgBox"></div>
        </div>

        <!-- ── Activity Modal (Complete) ─────────────────────────────────────────── -->
        <div id="actModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
          <div class="w-full max-w-4xl max-h-[90vh] flex flex-col bg-white rounded-2xl shadow-2xl">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
                <h2 id="actModalTitle" class="text-lg font-bold text-[#1A2039]">Add Activity</h2>
                <button onclick="thCloseModal('actModal')"
                    class="text-gray-400 hover:text-gray-600 w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-6 py-5 space-y-4">
                <input type="hidden" id="fASysId">

                <!-- Basic Info Row 1 -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="th-label">Country <span class="text-red-500">*</span></label>
                        <select id="fACountry" class="th-input"></select>
                    </div>
                    <div>
                        <label class="th-label">City</label>
                        <select id="fACity" class="th-input">
                            <option value="">Select city first</option>
                        </select>
                    </div>
                    <div>
                        <label class="th-label">Type</label>
                        <select id="fAType" class="th-input">
                            ${TYPES.map(t => `<option value="${t}">${t.charAt(0).toUpperCase()+t.slice(1)}</option>`).join('')}
                        </select>
                    </div>
                </div>

                <!-- Activity Name -->
                <div>
                    <label class="th-label">Activity Name <span class="text-red-500">*</span></label>
                    <input id="fAName" type="text" placeholder="e.g. Bangkok City & Temples Tour" class="th-input">
                </div>

                <!-- Category & Search Terms -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="th-label">Category</label>
                        <input id="fACat" type="text" placeholder="Sightseeing, Cultural, Adventure…" class="th-input">
                    </div>
                    <div>
                        <label class="th-label">Search Terms (comma separated)</label>
                        <input id="fASearchTerms" type="text" placeholder="bangkok, temple, tour, private" class="th-input">
                    </div>
                </div>

                <!-- Location & Duration -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="th-label">Location</label>
                        <input id="fALocation" type="text" placeholder="Specific location" class="th-input">
                    </div>
                    <div>
                        <label class="th-label">Duration (hours)</label>
                        <input id="fADur" type="number" step="0.5" min="0" placeholder="8" class="th-input">
                    </div>
                    <div>
                        <label class="th-label">Duration (typical text)</label>
                        <input id="fADurTypical" type="text" placeholder="Full day" class="th-input">
                    </div>
                </div>

                <!-- Time Fields -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="th-label">Start Time</label>
                        <input id="fAStartTime" type="time" class="th-input">
                    </div>
                    <div>
                        <label class="th-label">End Time</label>
                        <input id="fAEndTime" type="time" class="th-input">
                    </div>
                </div>

                <!-- Operating Days -->
                <div>
                    <label class="th-label block mb-2">Operating Days</label>
                    <div id="fAOperDays" class="flex flex-wrap gap-3">
                        ${OPERATING_DAYS.map(day => `
                            <label class="flex items-center gap-2">
                                <input type="checkbox" value="${day}" class="oper-day-checkbox">
                                <span class="text-sm">${day.charAt(0).toUpperCase() + day.slice(1)}</span>
                            </label>
                        `).join('')}
                    </div>
                </div>

                <!-- Pax & Age -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="th-label">Min Pax</label>
                        <input id="fAMinPax" type="number" min="1" placeholder="1" class="th-input">
                    </div>
                    <div>
                        <label class="th-label">Max Pax</label>
                        <input id="fAMaxPax" type="number" min="1" placeholder="50" class="th-input">
                    </div>
                    <div>
                        <label class="th-label">Min Age</label>
                        <input id="fAAgeMin" type="number" min="0" placeholder="5" class="th-input">
                    </div>
                </div>

                <!-- Languages & Meeting Point -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="th-label">Languages (comma separated)</label>
                        <input id="fALanguages" type="text" placeholder="English, Thai, Chinese" class="th-input">
                    </div>
                    <div>
                        <label class="th-label">Meeting Point</label>
                        <input id="fAMeetingPoint" type="text" placeholder="Hotel lobby or specific location" class="th-input">
                    </div>
                </div>

                <!-- Booking Lead & Popularity -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="th-label">Booking Lead Days</label>
                        <input id="fABookingLead" type="number" min="0" placeholder="2" class="th-input">
                    </div>
                    <div>
                        <label class="th-label">Popularity (1–5)</label>
                        <input id="fAPop" type="number" min="1" max="5" value="3" class="th-input">
                    </div>
                </div>

                <!-- Cancellation Policy -->
                <div>
                    <label class="th-label">Cancellation Policy</label>
                    <textarea id="fACancelPolicy" rows="2" placeholder="Free cancellation up to 24 hours before" class="th-input"></textarea>
                </div>

                <!-- Pickup & Dropoff Cities -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="th-label">Pickup From Cities (comma separated)</label>
                        <input id="fAPickupCities" type="text" placeholder="Bangkok, Nonthaburi, Pathum Thani" class="th-input">
                    </div>
                    <div>
                        <label class="th-label">Dropoff Cities (comma separated)</label>
                        <input id="fADropoffCities" type="text" placeholder="Same as pickup" class="th-input">
                    </div>
                </div>

                <!-- Tags -->
                <div>
                    <label class="th-label mb-2 block">Tags</label>
                    <div class="flex flex-wrap gap-1.5 mb-2" id="tagChips"></div>
                    <div class="grid grid-cols-3 gap-2">
                        <select id="tagPreset" class="th-input text-xs">
                            <option value="">Pick predefined tag…</option>
                            ${PREDEFINED_TAGS.map(t=>`<option value="${t}">${t.replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase())}</option>`).join('')}
                        </select>
                        <div class="grid grid-cols-3 col-span-2 gap-2">
                            <input id="tagCustom" class="th-input col-span-2 text-xs" placeholder="Custom tag…" maxlength="30">
                            <button id="btnAddTag" type="button"
                                class="px-3 py-2 rounded-xl text-xs font-semibold bg-[#50BC81] hover:bg-[#3da868] text-white transition">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Description Points (short_description + long_description combined) -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="th-label mb-0">Description & Itinerary Points</label>
                        <button type="button" id="btnAddDescPoint"
                            class="flex items-center gap-1 px-3 py-1 rounded-lg text-xs font-semibold bg-[#50BC81]/10 text-[#50BC81] hover:bg-[#50BC81]/20 transition">
                            <i class="fa-solid fa-plus text-xs"></i> Add Point
                        </button>
                    </div>
                    <div id="descPointsContainer" class="space-y-2"></div>
                </div>

                <!-- Inclusions & Exclusions -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="th-label">Inclusions (comma separated)</label>
                        <textarea id="fAInclusions" rows="3" placeholder="Hotel pickup, Professional guide, Lunch, Entrance fees" class="th-input"></textarea>
                    </div>
                    <div>
                        <label class="th-label">Exclusions (comma separated)</label>
                        <textarea id="fAExclusions" rows="3" placeholder="Personal expenses, Alcoholic drinks, Tips" class="th-input"></textarea>
                    </div>
                </div>

                <!-- Images -->
                <div>
                    <label class="th-label">Images (URLs, one per line)</label>
                    <textarea id="fAImages" rows="3" placeholder="https://example.com/image1.jpg&#10;https://example.com/image2.jpg" class="th-input"></textarea>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3 flex-shrink-0">
                <button onclick="thCloseModal('actModal')"
                    class="px-5 py-2 rounded-xl text-sm font-medium border border-gray-200 text-gray-600 hover:bg-gray-50">
                    Cancel
                </button>
                <button id="btnASave"
                    class="px-6 py-2 rounded-xl text-sm font-semibold bg-[#1A2039] hover:bg-[#252d4a] text-white transition shadow-sm">
                    Save Activity
                </button>
            </div>
          </div>
        </div>

        <!-- ── Variants Panel ─────────────────────────────────────────── -->
        <div id="varPanel" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
          <div class="w-full max-w-2xl max-h-[90vh] flex flex-col bg-white rounded-2xl shadow-2xl">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
                <div>
                    <h2 id="varPanelTitle" class="text-lg font-bold text-[#1A2039]">Variants</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Priced options of this activity</p>
                </div>
                <button onclick="thCloseModal('varPanel')"
                    class="text-gray-400 hover:text-gray-600 w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-6 py-5">
                <div id="varList" class="space-y-2 mb-4"></div>
                <div id="varForm" class="hidden bg-gray-50 rounded-2xl border border-gray-200 p-4 space-y-3">
                    <input type="hidden" id="fVSysId">
                    <h4 id="varFormTitle" class="text-sm font-semibold text-[#1A2039]">Add Variant</h4>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="th-label">Variant Name <span class="text-red-500">*</span></label>
                            <input id="fVName" type="text" placeholder="e.g. SIC with Lunch" class="th-input">
                        </div>
                        <div>
                            <label class="th-label">Price Basis</label>
                            <select id="fVBasis" class="th-input">
                                ${PRICE_BASES.map(p => `<option value="${p}">${p.replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase())}</option>`).join('')}
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="th-label">Currency <span class="text-red-500">*</span></label>
                            <input id="fVCcy" type="text" maxlength="5" placeholder="THB" class="th-input uppercase">
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
                                <select id="fVMkType" class="th-input">
                                    <option value="percent">%</option>
                                    <option value="fixed">Fix</option>
                                </select>
                                <input id="fVMkVal" type="number" step="0.01" value="0" placeholder="0" class="th-input">
                            </div>
                        </div>
                        <div>
                            <label class="th-label">Child Price</label>
                            <input id="fVChild" type="number" step="0.01" placeholder="optional" class="th-input">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="th-label">Transport Mode</label>
                            <select id="fVTransport" class="th-input">
                                ${TRANSPORT_MODES.map(m => `<option value="${m}">${m.charAt(0).toUpperCase()+m.slice(1)}</option>`).join('')}
                            </select>
                        </div>
                        <div>
                            <label class="th-label">Capacity</label>
                            <div class="flex gap-2">
                                <input id="fVCapMin" type="number" min="1" placeholder="Min" class="th-input">
                                <input id="fVCapMax" type="number" min="1" placeholder="Max" class="th-input">
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="th-label flex items-center gap-4">
                            Includes
                            <span class="flex gap-3 font-normal">
                                ${[['fVMealB','Breakfast'],['fVMealL','Lunch'],['fVMealD','Dinner'],['fVTicket','Ticket'],['fVGuide','Guide']].map(([id,lbl]) => `
                                <label class="flex items-center gap-1 text-xs font-normal cursor-pointer">
                                    <input type="checkbox" id="${id}" class="w-3.5 h-3.5 rounded accent-[#50BC81]"> ${lbl}
                                </label>`).join('')}
                            </span>
                        </label>
                    </div>
                    <div>
                        <label class="th-label">Guide Language</label>
                        <input id="fVGuideLang" type="text" placeholder="English, Thai…" class="th-input">
                    </div>
                    <div class="flex gap-2 justify-end pt-1">
                        <button onclick="MdActivities._cancelVarForm()"
                            class="px-4 py-1.5 rounded-lg text-xs font-medium border border-gray-200 text-gray-600 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button id="btnVSave"
                            class="px-5 py-1.5 rounded-lg text-xs font-semibold bg-[#1A2039] hover:bg-[#252d4a] text-white transition">
                            Save Variant
                        </button>
                    </div>
                </div>
                <button id="btnAddVar"
                    class="w-full mt-3 py-2 rounded-xl text-sm font-semibold border-2 border-dashed border-[#50BC81] text-[#50BC81] hover:bg-[#50BC81]/5 transition">
                    <i class="fa-solid fa-plus mr-2"></i>Add Variant
                </button>
            </div>
          </div>
        </div>`;

        // Load Quill
        if (!document.getElementById('quill-css')) {
            const ql = document.createElement('link');
            ql.id = 'quill-css'; ql.rel = 'stylesheet';
            ql.href = 'https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css';
            document.head.appendChild(ql);
        }
        if (!document.getElementById('quill-js')) {
            const qs = document.createElement('script');
            qs.id = 'quill-js';
            qs.src = 'https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.min.js';
            document.head.appendChild(qs);
        }

        document.head.insertAdjacentHTML('beforeend', `<style>
            .th-label{display:block;font-size:.75rem;font-weight:600;color:#4b5563;margin-bottom:.375rem}
            .th-input{width:100%;padding:.5rem .75rem;border:1px solid #e5e7eb;border-radius:.75rem;font-size:.875rem;outline:none;background:#fff}
            .th-input:focus{border-color:#50BC81;box-shadow:0 0 0 2px rgba(80,188,129,.25)}
            .desc-point-row .ql-toolbar.ql-snow{border:none;border-bottom:1px solid #e5e7eb;padding:4px 6px;border-radius:.75rem .75rem 0 0;background:#f9fafb;}
            .desc-point-row .ql-container.ql-snow{border:none;font-size:.875rem;border-radius:0 0 .75rem .75rem;min-height:60px;}
            .desc-point-row .ql-editor{min-height:60px;padding:.5rem .75rem;}
            .desc-point-row .ql-editor.ql-blank::before{color:#9ca3af;font-style:normal;}
            .desc-point-row .quill-wrap{border:1px solid #e5e7eb;border-radius:.75rem;overflow:hidden;flex:1;background:#fff;transition:border-color .15s,box-shadow .15s;}
            .desc-point-row .quill-wrap:focus-within{border-color:#50BC81;box-shadow:0 0 0 2px rgba(80,188,129,.25);}
        </style>`);
    }

    // ── Events ────────────────────────────────────────────────────────
    function bindEvents() {
        document.getElementById('btnAdd').onclick         = () => openActForm();
        document.getElementById('btnASave').onclick       = saveActivity;
        document.getElementById('btnVSave').onclick       = saveVariant;
        document.getElementById('btnAddVar').onclick      = () => openVarForm();
        document.getElementById('btnAddDescPoint').onclick = () => addDescPoint();

        document.getElementById('srch').oninput = e => {
            clearTimeout(st.timer);
            st.timer = setTimeout(() => { st.search = e.target.value; st.page = 1; load(); }, 400);
        };
        document.getElementById('fltCountry').onchange = e => { st.country = e.target.value; st.page = 1; load(); };
        document.getElementById('fltType').onchange    = e => { st.page = 1; load(); };
        document.getElementById('fltTag').onchange     = e => { st.tag = e.target.value; st.page = 1; load(); };

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

    // ── Load Activities ──────────────────────────────────────────────────────────
    async function load() {
        document.getElementById('tLoading').classList.remove('hidden');
        document.getElementById('tEmpty').classList.add('hidden');
        document.getElementById('actGrid').innerHTML = '';

        const type = document.getElementById('fltType')?.value || '';
        const p = new URLSearchParams({
            page: st.page, limit: st.limit, search: st.search,
            country_sys_id: st.country, status: st.status,
        });
        if (type)   p.append('type', type);
        if (st.tag) p.append('tag',  st.tag);

        const data = await thApi(`${API_BASE}api/masterdata/activities/list.php?${p}`);
        document.getElementById('tLoading').classList.add('hidden');

        if (!data.success || !data.data?.length) {
            document.getElementById('tEmpty').classList.remove('hidden');
            return;
        }
        document.getElementById('actGrid').innerHTML = data.data.map(actCard).join('');
        thPagination('pgBox', data.pagination, 'MdActivities._page');
    }
    
    function _page(p) { st.page = p; load(); }

    // ── Activity Card ─────────────────────────────────────────────────
    function actCard(a) {
        const stars   = '★'.repeat(a.popularity || 0) + '☆'.repeat(5 - (a.popularity || 0));
        const typeBg  = { tour:'bg-green-100 text-green-700', transfer:'bg-blue-100 text-blue-700', both:'bg-purple-100 text-purple-700' };
        const isTrashed = a.status === 'deleted';
        return `
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition ${isTrashed?'opacity-60':''}">
            <div class="h-32 bg-gradient-to-br from-[#50BC81]/20 to-[#1A2039]/20 flex items-center justify-center relative">
                ${a.thumb ? `<img src="${a.thumb}" class="w-full h-full object-cover absolute inset-0" onerror="this.style.display='none'">` : ''}
                <i class="fa-solid fa-person-hiking text-3xl text-[#1A2039]/30 absolute"></i>
            </div>
            <div class="p-4">
                <div class="flex items-start justify-between gap-2 mb-1">
                    <div class="font-semibold text-gray-800 text-sm leading-tight line-clamp-2">${esc(a.name)}</div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium flex-shrink-0
                        ${typeBg[a.type] || 'bg-gray-100 text-gray-600'}">
                        ${a.type}
                    </span>
                </div>
                <div class="text-xs text-gray-400 mb-1">
                    ${esc(a.city_name||a.country_name||'')}
                    ${a.duration_hours ? ' · ' + a.duration_hours + 'h' : ''}
                </div>
                <div class="text-xs text-yellow-500 mb-2">${stars}</div>
                ${a.tags?.length ? `<div class="flex flex-wrap gap-1 mb-3">
                    ${a.tags.slice(0,4).map(t=>`<span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-[#50BC81]/15 text-[#2e9460]">${t.replace(/_/g,' ')}</span>`).join('')}
                    ${a.tags.length>4?`<span class="px-2 py-0.5 rounded-full text-[10px] text-gray-400">+${a.tags.length-4}</span>`:''}
                </div>` : '<div class="mb-3"></div>'}
                <div class="flex items-center gap-2">
                    <button onclick="MdActivities._variants('${a.sys_id}','${esc(a.name)}','${a.country_sys_id}')"
                        class="flex-1 py-1.5 rounded-lg text-xs font-medium bg-[#1A2039]/10 text-[#1A2039] hover:bg-[#1A2039]/20 transition">
                        <i class="fa-solid fa-list mr-1"></i> Variants
                    </button>
                    ${!isTrashed ? `
                    <button onclick="MdActivities._edit('${a.sys_id}')"
                        class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-blue-50 text-blue-500">
                        <i class="fa-solid fa-pen text-xs"></i>
                    </button>
                    <button onclick="MdActivities._del('${a.sys_id}',false)"
                        class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-red-50 text-red-400">
                        <i class="fa-solid fa-trash text-xs"></i>
                    </button>` : `
                    <button onclick="MdActivities._del('${a.sys_id}',true)"
                        class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-green-50 text-green-500">
                        <i class="fa-solid fa-rotate-left text-xs"></i>
                    </button>`}
                </div>
            </div>
        </div>`;
    }

    // ── Activity CRUD ─────────────────────────────────────────────────
    async function openActForm(sys_id = null) {
        // Clear all fields
        ['fASysId','fAName','fACat','fALocation','fAMinPax','fAMaxPax','fAAgeMin',
         'fASearchTerms','fADurTypical','fAStartTime','fAEndTime','fALanguages',
         'fAMeetingPoint','fABookingLead','fACancelPolicy','fAPickupCities',
         'fADropoffCities','fAInclusions','fAExclusions','fAImages'].forEach(id => thSetVal(id,''));
        
        thSetVal('fAType', 'tour');
        thSetVal('fADur', '0');
        thSetVal('fAPop', '3');
        thSetVal('fACountry', '');
        thSetVal('fACity', '');
        thSetVal('fAVendor', '');
        
        // Clear operating days checkboxes
        document.querySelectorAll('.oper-day-checkbox').forEach(cb => cb.checked = false);
        
        renderDescPoints([]);
        pendingTags = [];
        renderTagChips();
        document.getElementById('actModalTitle').textContent = sys_id ? 'Edit Activity' : 'Add Activity';

        if (sys_id) {
            thSetVal('fASysId', sys_id);
            const [d, td] = await Promise.all([
                thApi(`${API_BASE}api/masterdata/activities/get.php?sys_id=${sys_id}`),
                thApi(`${API_BASE}api/masterdata/activities/tag-list.php?activity_sys_id=${sys_id}`),
            ]);
            if (!d.success) return thToast('Failed to load', 'error');
            const a = d.data;
            
            thSetVal('fACountry', a.country_sys_id);
            await loadCities(a.country_sys_id);
            thSetVal('fACity', a.city_sys_id || '');
            thSetVal('fAVendor', a.vendor_sys_id || '');
            thSetVal('fAType', a.type);
            thSetVal('fAName', a.name);
            thSetVal('fACat', a.category || '');
            thSetVal('fALocation', a.location || '');
            thSetVal('fADur', a.duration_hours || 0);
            thSetVal('fADurTypical', a.duration_typical || '');
            thSetVal('fAStartTime', a.start_time || '');
            thSetVal('fAEndTime', a.end_time || '');
            thSetVal('fAMinPax', a.min_pax || '');
            thSetVal('fAMaxPax', a.max_pax || '');
            thSetVal('fAAgeMin', a.age_min || '');
            thSetVal('fALanguages', a.languages || '');
            thSetVal('fAMeetingPoint', a.meeting_point || '');
            thSetVal('fABookingLead', a.booking_lead_days || '');
            thSetVal('fAPop', a.popularity || 3);
            thSetVal('fACancelPolicy', a.cancellation_policy || '');
            thSetVal('fASearchTerms', a.search_terms || '');
            
            // Operating days
            if (a.operating_days) {
                const days = a.operating_days.split(',');
                document.querySelectorAll('.oper-day-checkbox').forEach(cb => {
                    cb.checked = days.includes(cb.value);
                });
            }
            
            // Pickup/Dropoff cities
            if (a.pickup_from_city) {
                const pickup = typeof a.pickup_from_city === 'string' ? JSON.parse(a.pickup_from_city) : a.pickup_from_city;
                thSetVal('fAPickupCities', Array.isArray(pickup) ? pickup.join(', ') : '');
            }
            if (a.dropoff_city) {
                const dropoff = typeof a.dropoff_city === 'string' ? JSON.parse(a.dropoff_city) : a.dropoff_city;
                thSetVal('fADropoffCities', Array.isArray(dropoff) ? dropoff.join(', ') : '');
            }
            
            // Inclusions/Exclusions
            if (a.inclusions) {
                const inclusions = typeof a.inclusions === 'string' ? JSON.parse(a.inclusions) : a.inclusions;
                thSetVal('fAInclusions', Array.isArray(inclusions) ? inclusions.join('\n') : '');
            }
            if (a.exclusions) {
                const exclusions = typeof a.exclusions === 'string' ? JSON.parse(a.exclusions) : a.exclusions;
                thSetVal('fAExclusions', Array.isArray(exclusions) ? exclusions.join('\n') : '');
            }
            
            // Images
            if (a.images) {
                const images = typeof a.images === 'string' ? JSON.parse(a.images) : a.images;
                thSetVal('fAImages', Array.isArray(images) ? images.map(img => img.url).join('\n') : '');
            }
            
            // Tags
            pendingTags = td.tags || [];
            renderTagChips();
            
            // Description points
            let points = [];
            try { 
                points = JSON.parse(a.short_description || '[]'); 
                if (!Array.isArray(points)) points = [];
                // পুরনো ডাটার জন্য backward compatibility
                if (points.length === 0 && a.short_description && typeof a.short_description === 'string') {
                    // চেক করুন এটা পুরনো format কিনা (plain text)
                    if (!a.short_description.startsWith('[') && !a.short_description.startsWith('{')) {
                        points = [{ time: '', duration: '', description: a.short_description }];
                    }
                }
                // নিশ্চিত করুন প্রতিটি পয়েন্টে duration field আছে
                points = points.map(p => ({ 
                    time: p.time || '', 
                    duration: p.duration || '', 
                    description: p.description || '' 
                }));
            } catch(e) {
                if (a.short_description) {
                    points = [{ time: '', duration: '', description: a.short_description }];
                }
            }
            renderDescPoints(points);
        }

        // Bind tag events
        const btnAddTag = document.getElementById('btnAddTag');
        btnAddTag.onclick = () => {
            const preset = thVal('tagPreset');
            const custom = thVal('tagCustom').toLowerCase().replace(/[^a-z0-9_]/g,'_').replace(/_+/g,'_').replace(/^_|_$/g,'');
            const tag    = preset || custom;
            if (!tag) { thToast('Pick or type a tag', 'warning'); return; }
            if (pendingTags.includes(tag)) { thToast('Tag already added', 'warning'); return; }
            pendingTags.push(tag);
            renderTagChips();
            thSetVal('tagPreset', '');
            thSetVal('tagCustom', '');
        };

        thOpenModal('actModal');
    }

    function renderTagChips() {
        const el = document.getElementById('tagChips'); 
        if (!el) return;
        el.innerHTML = pendingTags.length
            ? pendingTags.map((t,i) => `
                <span class="flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-[#50BC81]/15 text-[#2e9460] border border-[#50BC81]/30">
                    ${t.replace(/_/g,' ')}
                    <button type="button" onclick="MdActivities._rmTag(${i})"
                        class="ml-0.5 hover:text-red-500 transition leading-none">×</button>
                </span>`).join('')
            : '<span class="text-xs text-gray-300 italic">No tags yet</span>';
    }
    
    async function _edit(sys_id) { await openActForm(sys_id); }

    async function saveActivity() {
        const cSel  = document.getElementById('fACountry');
        const cName = cSel.options[cSel.selectedIndex]?.dataset?.name || '';
        const citySel = document.getElementById('fACity');
        const cityName = citySel.options[citySel.selectedIndex]?.dataset?.name || '';
        
        // Get operating days
        const operatingDays = Array.from(document.querySelectorAll('.oper-day-checkbox:checked'))
            .map(cb => cb.value).join(',');
        
        // Get description points
        const descPoints = getDescPoints();
        const shortDesc = JSON.stringify(descPoints);
        
        // Build long description from points
        const longDesc = descPoints.map(p => {
            let header = [];
            if (p.time) header.push(`🕐 ${p.time}`);
            if (p.duration) header.push(`⏱️ ${p.duration}`);
            const headerText = header.length ? `**${header.join(' | ')}**` : '';
            return headerText ? `${headerText}\n${p.description}` : p.description;
        }).join('\n\n---\n\n');
        
        // Highlights from points with 'highlight' in time or first 3 points
        const highlights = descPoints.filter(p => 
            p.time.toLowerCase().includes('highlight') || 
            p.duration.toLowerCase().includes('highlight') ||
            p.description.toLowerCase().includes('highlight')
        ).slice(0, 3).map(p => p.description.replace(/<[^>]*>/g, '').substring(0, 100)).join(', ');
        
        const body = {
            sys_id:            thVal('fASysId') || undefined,
            country_sys_id:    thVal('fACountry'),
            country_name:      cName,
            city_sys_id:       thVal('fACity') || undefined,
            city_name:         cityName || thVal('fALocation'),
            vendor_sys_id:     thVal('fAVendor') || undefined,
            name:              thVal('fAName'),
            search_terms:      thVal('fASearchTerms') || thVal('fAName'),
            type:              thVal('fAType'),
            category:          thVal('fACat') || undefined,
            location:          thVal('fALocation') || undefined,
            short_description: shortDesc,
            long_description:  longDesc || undefined,
            highlights:        highlights || undefined,
            start_time:        thVal('fAStartTime') || undefined,
            end_time:          thVal('fAEndTime') || undefined,
            duration_hours:    parseFloat(thVal('fADur') || 0),
            duration_typical:  thVal('fADurTypical') || undefined,
            operating_days:    operatingDays || undefined,
            min_pax:           thVal('fAMinPax') ? parseInt(thVal('fAMinPax')) : null,
            max_pax:           thVal('fAMaxPax') ? parseInt(thVal('fAMaxPax')) : null,
            age_min:           thVal('fAAgeMin') ? parseInt(thVal('fAAgeMin')) : null,
            languages:         thVal('fALanguages') || undefined,
            meeting_point:     thVal('fAMeetingPoint') || undefined,
            booking_lead_days: thVal('fABookingLead') ? parseInt(thVal('fABookingLead')) : null,
            cancellation_policy: thVal('fACancelPolicy') || undefined,
            popularity:        parseInt(thVal('fAPop') || 3),
            pickup_from_city:  thVal('fAPickupCities') ? thVal('fAPickupCities').split(',').map(s => s.trim()).filter(Boolean) : [],
            dropoff_city:      thVal('fADropoffCities') ? thVal('fADropoffCities').split(',').map(s => s.trim()).filter(Boolean) : [],
            itineraries:       [], // Can be extended if needed
            inclusions:        thVal('fAInclusions') ? thVal('fAInclusions').split('\n').map(s => s.trim()).filter(Boolean) : [],
            exclusions:        thVal('fAExclusions') ? thVal('fAExclusions').split('\n').map(s => s.trim()).filter(Boolean) : [],
            images:            thVal('fAImages') ? thVal('fAImages').split('\n').map((url, idx) => ({
                url: url.trim(),
                caption: '',
                sort_order: idx,
                is_primary: idx === 0
            })).filter(img => img.url) : [],
        };
        
        if (!body.name)           return thToast('Activity name required', 'error');
        if (!body.country_sys_id) return thToast('Country required', 'error');

        document.getElementById('btnASave').disabled = true;
        const res = await thApi(`${API_BASE}api/masterdata/activities/save.php`, 'POST', body);
        document.getElementById('btnASave').disabled = false;
        
        if (!res.success) return thToast(res.message || 'Error', 'error');

        // Save tags
        const actSysId = res.sys_id || thVal('fASysId');
        if (actSysId && pendingTags.length) {
            await thApi(`${API_BASE}api/masterdata/activities/tag-save.php`, 'POST', {
                activity_sys_id: actSysId,
                tags: pendingTags,
            });
            loadTagFilter();
        }

        thToast(res.message || 'Saved!');
        thCloseModal('actModal');
        load();
    }

    async function _del(sys_id, restore) {
        if (!restore && !thConfirm('Delete this activity?')) return;
        const res = await thApi(`${API_BASE}api/masterdata/activities/delete.php`, 'POST', { sys_id, restore });
        thToast(res.message || (restore ? 'Restored' : 'Deleted'));
        load();
    }

    // ── Variants Panel ────────────────────────────────────────────────
    async function _variants(sys_id, name, country_sys_id) {
        st.viewActivity        = sys_id;
        st.viewActivityCountry = country_sys_id;
        const country = countriesCache.find(c => c.sys_id === country_sys_id);
        st.viewActivityCurrency = country?.currency_code || '';
        document.getElementById('varPanelTitle').textContent = name;
        document.getElementById('varForm').classList.add('hidden');
        thOpenModal('varPanel');
        await loadVariants(sys_id);
    }

    async function loadVariants(activity_sys_id) {
        const el = document.getElementById('varList');
        el.innerHTML = '<div class="text-center py-6 text-gray-300"><i class="fa-solid fa-spinner fa-spin"></i></div>';
        const d = await thApi(`${API_BASE}api/masterdata/activities/variant-list.php?activity_sys_id=${activity_sys_id}&status=active`);
        const variants = d.data || [];
        if (!variants.length) {
            el.innerHTML = '<p class="text-sm text-gray-400 italic py-2">No variants yet. Add one below.</p>';
            return;
        }
        el.innerHTML = variants.map(v => `
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
                <div class="flex items-start justify-between gap-4 mb-2">
                    <div>
                        <div class="font-semibold text-gray-800 text-sm">${esc(v.variant_name)}</div>
                        <div class="text-xs text-gray-400 font-mono">${v.sys_id}</div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <div class="font-semibold text-sm text-[#1A2039]">
                            ${esc(v.currency_code)} ${Number(v.sell_price||0).toLocaleString()}
                            <span class="text-xs text-gray-400 font-normal">/${v.price_basis==='per_pax'?'pax':'group'}</span>
                        </div>
                        <div class="text-xs text-gray-400">Net: ${esc(v.currency_code)} ${Number(v.net_cost||0).toLocaleString()}</div>
                    </div>
                </div>
                <div class="flex flex-wrap gap-1.5 mb-2 text-xs">
                    ${v.transport_mode && v.transport_mode!=='none' ? `<span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">${esc(v.transport_mode)}</span>` : ''}
                    ${v.meal_breakfast ? '<span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">Breakfast</span>' : ''}
                    ${v.meal_lunch     ? '<span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">Lunch</span>' : ''}
                    ${v.meal_dinner    ? '<span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">Dinner</span>' : ''}
                    ${v.ticket_included? '<span class="px-2 py-0.5 rounded-full bg-green-100 text-green-700">Ticket</span>' : ''}
                    ${v.guide_included ? `<span class="px-2 py-0.5 rounded-full bg-purple-100 text-purple-700">Guide${v.guide_language?' ('+esc(v.guide_language)+')':''}</span>` : ''}
                </div>
                <div class="flex gap-2">
                    <button onclick="MdActivities._editVariant('${v.sys_id}')"
                        class="flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium bg-blue-50 text-blue-600 hover:bg-blue-100 transition">
                        <i class="fa-solid fa-pen text-xs"></i> Edit
                    </button>
                    <button onclick="MdActivities._delVariant('${v.sys_id}')"
                        class="flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium bg-red-50 text-red-500 hover:bg-red-100 transition">
                        <i class="fa-solid fa-trash text-xs"></i> Delete
                    </button>
                </div>
            </div>`).join('');
    }

    function openVarForm(v = null) {
        ['fVSysId','fVName','fVCcy','fVCost','fVSell','fVChild','fVGuideLang','fVCapMin','fVCapMax'].forEach(id => thSetVal(id,''));
        thSetVal('fVBasis', 'per_pax');
        thSetVal('fVMkType', 'percent');
        thSetVal('fVMkVal', '0');
        thSetVal('fVTransport', 'none');
        ['fVMealB','fVMealL','fVMealD','fVTicket','fVGuide'].forEach(id => {
            const el = document.getElementById(id); if (el) el.checked = false;
        });
        document.getElementById('varFormTitle').textContent = v ? 'Edit Variant' : 'Add Variant';
        if (v) {
            thSetVal('fVSysId',     v.sys_id);
            thSetVal('fVName',      v.variant_name);
            thSetVal('fVBasis',     v.price_basis    || 'per_pax');
            thSetVal('fVCcy',       v.currency_code  || '');
            thSetVal('fVCost',      v.net_cost       || '');
            thSetVal('fVSell',      v.sell_price     || '');
            thSetVal('fVChild',     v.child_price    || '');
            thSetVal('fVMkType',    v.markup_type    || 'percent');
            thSetVal('fVMkVal',     v.markup_value   || 0);
            thSetVal('fVTransport', v.transport_mode || 'none');
            thSetVal('fVCapMin',    v.capacity_min   || '');
            thSetVal('fVCapMax',    v.capacity_max   || '');
            thSetVal('fVGuideLang', v.guide_language || '');
            document.getElementById('fVMealB').checked = !!v.meal_breakfast;
            document.getElementById('fVMealL').checked = !!v.meal_lunch;
            document.getElementById('fVMealD').checked = !!v.meal_dinner;
            document.getElementById('fVTicket').checked = !!v.ticket_included;
            document.getElementById('fVGuide').checked = !!v.guide_included;
        }
        if (!v) {
            thSetVal('fVCcy', st.viewActivityCurrency || '');
        }
        document.getElementById('varForm').classList.remove('hidden');
        document.getElementById('varForm').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function _cancelVarForm() {
        document.getElementById('varForm').classList.add('hidden');
        thSetVal('fVSysId', '');
    }

    async function _editVariant(sys_id) {
        const d = await thApi(`${API_BASE}api/masterdata/activities/variant-list.php?activity_sys_id=${st.viewActivity}&status=active`);
        const v = (d.data || []).find(x => x.sys_id === sys_id);
        if (!v) return thToast('Variant not found', 'error');
        openVarForm(v);
    }

    async function saveVariant() {
        const variantName = thVal('fVName');
        const currencyCode = thVal('fVCcy').toUpperCase();
        const netCost  = parseFloat(thVal('fVCost') || 0);
        const sellPrice = parseFloat(thVal('fVSell') || 0);
        if (!variantName)   return thToast('Variant name required', 'error');
        if (!currencyCode)  return thToast('Currency code required', 'error');
        if (netCost <= 0)   return thToast('Net cost must be greater than 0', 'error');
        if (!sellPrice)     return thToast('Sell price required', 'error');
        const body = {
            sys_id:           thVal('fVSysId') || undefined,
            activity_sys_id:  st.viewActivity,
            country_sys_id:   st.viewActivityCountry || '',
            variant_name:     variantName,
            price_basis:      thVal('fVBasis'),
            currency_code:    currencyCode,
            net_cost:         netCost,
            markup_type:      thVal('fVMkType'),
            markup_value:     parseFloat(thVal('fVMkVal') || 0),
            sell_price:       sellPrice,
            child_price:      thVal('fVChild') ? parseFloat(thVal('fVChild')) : undefined,
            transport_mode:   thVal('fVTransport') || 'none',
            capacity_min:     thVal('fVCapMin') ? parseInt(thVal('fVCapMin')) : undefined,
            capacity_max:     thVal('fVCapMax') ? parseInt(thVal('fVCapMax')) : undefined,
            guide_language:   thVal('fVGuideLang') || undefined,
            meal_breakfast:   document.getElementById('fVMealB')?.checked ? 1 : 0,
            meal_lunch:       document.getElementById('fVMealL')?.checked ? 1 : 0,
            meal_dinner:      document.getElementById('fVMealD')?.checked ? 1 : 0,
            ticket_included:  document.getElementById('fVTicket')?.checked ? 1 : 0,
            guide_included:   document.getElementById('fVGuide')?.checked  ? 1 : 0,
        };
        document.getElementById('btnVSave').disabled = true;
        document.getElementById('btnVSave').textContent = 'Saving…';
        const res = await thApi(`${API_BASE}api/masterdata/activities/variant-save.php`, 'POST', body);
        document.getElementById('btnVSave').disabled = false;
        document.getElementById('btnVSave').textContent = 'Save Variant';
        if (!res.success) return thToast(res.message || 'Error saving variant', 'error');
        thToast(res.action === 'created' ? 'Variant added!' : 'Variant updated!');
        _cancelVarForm();
        await loadVariants(st.viewActivity);
    }

    async function _delVariant(sys_id) {
        if (!thConfirm('Delete this variant?')) return;
        const res = await thApi(`${API_BASE}api/masterdata/activities/variant-delete.php`, 'POST', { sys_id });
        thToast(res.message || 'Deleted');
        await loadVariants(st.viewActivity);
    }

    // ── Description Points ────────────────────────────────────────────
    function renderDescPoints(points = []) {
        const container = document.getElementById('descPointsContainer');
        container.innerHTML = '';
        if (!points.length) {
            addDescPoint();
            return;
        }
        points.forEach(p => addDescPoint(p.time || '', p.description || '', p.duration || ''));
    }

    function addDescPoint(time = '', description = '', duration = '') {
        const container = document.getElementById('descPointsContainer');
        const row = document.createElement('div');
        row.className = 'desc-point-row flex gap-2 items-start mb-3';
        row.innerHTML = `
            <div class="flex items-start gap-4 w-full">
                
                <!-- বাম পাশের অংশ: Time এবং Duration (একটির নিচে আরেকটি) -->
                <div class="flex-shrink-0 flex flex-col gap-2" style="width: 200px;">
                    <input type="text" placeholder="Time (e.g., 09:00 AM)"
                        class="th-input desc-point-time text-sm w-full" 
                        value="${esc(time)}">
                        
                    <input type="text" placeholder="Duration (e.g., 2 hours)"
                        class="th-input desc-point-duration text-sm w-full" 
                        value="${esc(duration)}">
                </div>

                <!-- ডান পাশের অংশ: Text Editor (বাকি পুরো জায়গা নিবে) -->
                <div class="quill-wrap flex-1">
                    <div class="desc-point-quill" style="min-height: 86px;"></div>
                </div>

            </div>
            <button type="button" onclick="this.closest('.desc-point-row').remove()"
                class="flex-shrink-0 w-7 h-7 mt-1 flex items-center justify-center rounded-lg text-gray-400 hover:text-red-400 hover:bg-red-50 transition">
                <i class="fa-solid fa-xmark text-xs"></i>
            </button>`;
        container.appendChild(row);
        
        const initQuill = () => {
            const q = new Quill(row.querySelector('.desc-point-quill'), {
                theme: 'snow',
                placeholder: 'Description…',
                modules: { 
                    toolbar: [
                        ['bold', 'italic', 'underline'],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        ['clean']
                    ] 
                }
            });
            if (description) q.clipboard.dangerouslyPasteHTML(description);
            row._quill = q;
        };
        
        if (typeof Quill !== 'undefined') {
            initQuill();
        } else {
            document.getElementById('quill-js').addEventListener('load', initQuill, { once: true });
        }
    }

    function getDescPoints() {
        return [...document.querySelectorAll('.desc-point-row')].map(row => {
            const time = row.querySelector('.desc-point-time').value.trim();
            const duration = row.querySelector('.desc-point-duration').value.trim();
            const description = row._quill ? row._quill.root.innerHTML.trim() : '';
            const isEmpty = !description || description === '<p><br></p>';
            return { 
                time, 
                duration,
                description: isEmpty ? '' : description 
            };
        }).filter(p => p.description);
    }

    // ── Utilities ───────────────────────────────────────────────────
    function esc(s) {
        return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function _rmTag(i) { pendingTags.splice(i, 1); renderTagChips(); }

    return { 
        init, _page, _edit, _del, _variants, _editVariant, 
        _delVariant, _cancelVarForm, _rmTag 
    };
})();

// Initialize when DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => MdActivities.init());
} else {
    MdActivities.init();
}