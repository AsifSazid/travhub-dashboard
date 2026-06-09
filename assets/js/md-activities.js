const MdActivities = (() => {
    'use strict';
    const TYPES=['tour','transfer','both'];
    const st={page:1,limit:12,search:'',country:'',status:'active',timer:null,viewActivity:null};
    let countriesCache=[];

    function init(){renderShell();bindEvents();loadCountries().then(load);}
    async function loadCountries(){
        const d=await thApi(`${API_BASE}api/masterdata/countries/list.php?limit=200&status=active&for_package=1`);
        countriesCache=d.data||[];
        ['fltCountry','fACountry'].forEach(id=>{
            const el=document.getElementById(id);if(!el)return;
            const lbl=id==='fltCountry'?'All Countries':'Select country';
            el.innerHTML=`<option value="">${lbl}</option>`+countriesCache.map(c=>`<option value="${c.sys_id}" data-name="${c.name}">${c.name}</option>`).join('');
        });
    }

    function renderShell(){
        document.getElementById('mainContent').innerHTML=`
        <div class="px-6 py-6 max-w-screen-xl mx-auto">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div><h1 class="text-2xl font-bold text-[#1A2039]">Activities</h1><p class="text-sm text-gray-400 mt-0.5">Tours, transfers and activity variants</p></div>
                <button id="btnAdd" class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold bg-[#1A2039] hover:bg-[#252d4a] text-white transition shadow-sm"><i class="fa-solid fa-plus"></i> Add Activity</button>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-5 flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1"><i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input id="srch" type="text" placeholder="Search activity…" class="w-full pl-9 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#50BC81]"></div>
                <select id="fltCountry" class="text-sm px-3 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#50BC81] bg-white"></select>
                <select id="fltType" class="text-sm px-3 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#50BC81] bg-white">
                    <option value="">All Types</option>${TYPES.map(t=>`<option value="${t}">${t.charAt(0).toUpperCase()+t.slice(1)}</option>`).join('')}
                </select>
                <div class="flex gap-2">${['active','trash'].map(t=>`<button data-tab="${t}" class="tab-btn px-4 py-2 text-sm font-medium rounded-xl border transition ${t==='active'?'bg-[#1A2039] text-white border-[#1A2039]':'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'}">${t==='active'?'Active':'<i class="fa-solid fa-trash mr-1"></i>Trash'}</button>`).join('')}</div>
            </div>
            <!-- Card grid -->
            <div id="actGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-5"></div>
            <div id="tLoading" class="text-center py-16 text-gray-300"><i class="fa-solid fa-spinner fa-spin text-3xl"></i></div>
            <div id="tEmpty" class="hidden text-center py-16 text-gray-300"><i class="fa-solid fa-person-hiking text-5xl mb-3 block opacity-30"></i><p class="text-sm font-medium">No activities found</p></div>
            <div id="pgBox"></div>
        </div>

        <!-- Activity Modal -->
        <div id="actModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
          <div class="w-full max-w-2xl max-h-[90vh] flex flex-col bg-white rounded-2xl shadow-2xl">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
                <h2 id="actModalTitle" class="text-lg font-bold text-[#1A2039]">Add Activity</h2>
                <button onclick="thCloseModal('actModal')" class="text-gray-400 hover:text-gray-600 w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100"><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="flex-1 overflow-y-auto px-6 py-5 space-y-4">
                <input type="hidden" id="fASysId">
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="th-label">Country <span class="text-red-500">*</span></label><select id="fACountry" class="th-input"></select></div>
                    <div><label class="th-label">Type</label>
                        <select id="fAType" class="th-input">${TYPES.map(t=>`<option value="${t}">${t.charAt(0).toUpperCase()+t.slice(1)}</option>`).join('')}</select>
                    </div>
                </div>
                <div><label class="th-label">Activity Name <span class="text-red-500">*</span></label><input id="fAName" type="text" placeholder="Bangkok City & Temples Tour" class="th-input"></div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="th-label">Category</label><input id="fACat" type="text" placeholder="Sightseeing, Cultural…" class="th-input"></div>
                    <div><label class="th-label">Duration (hours)</label><input id="fADur" type="number" step="0.5" min="0" placeholder="8" class="th-input"></div>
                </div>
                <div><label class="th-label">City / Location</label><input id="fALoc" type="text" placeholder="Bangkok" class="th-input"></div>
                <div><label class="th-label">Short Description</label><textarea id="fADesc" rows="2" class="th-input resize-none" placeholder="Brief description for catalog"></textarea></div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="th-label">Min Pax</label><input id="fAMinPax" type="number" min="1" placeholder="1" class="th-input"></div>
                    <div><label class="th-label">Max Pax</label><input id="fAMaxPax" type="number" min="1" placeholder="50" class="th-input"></div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3 flex-shrink-0">
                <button onclick="thCloseModal('actModal')" class="px-5 py-2 rounded-xl text-sm font-medium border border-gray-200 text-gray-600 hover:bg-gray-50">Cancel</button>
                <button id="btnASave" class="px-6 py-2 rounded-xl text-sm font-semibold bg-[#1A2039] hover:bg-[#252d4a] text-white transition shadow-sm">Save Activity</button>
            </div>
          </div>
        </div>

        <!-- Variants Panel -->
        <div id="varPanel" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
          <div class="w-full max-w-2xl max-h-[90vh] flex flex-col bg-white rounded-2xl shadow-2xl">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
                <div><h2 id="varPanelTitle" class="text-lg font-bold text-[#1A2039]">Variants</h2><p class="text-xs text-gray-400">Priced options of this activity</p></div>
                <button onclick="thCloseModal('varPanel')" class="text-gray-400 hover:text-gray-600 w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100"><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="flex-1 overflow-y-auto px-6 py-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-[#1A2039] text-sm">Price Variants</h3>
                    <button id="btnAddVar" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-[#50BC81] hover:bg-[#3da868] text-white transition"><i class="fa-solid fa-plus"></i> Add Variant</button>
                </div>
                <div id="varList"></div>
                <!-- Quick add form -->
                <div id="varForm" class="hidden bg-gray-50 rounded-xl p-4 mt-4 space-y-3">
                    <input type="hidden" id="fVSysId">
                    <div class="grid grid-cols-2 gap-3">
                        <div><label class="th-label">Variant Name <span class="text-red-500">*</span></label><input id="fVName" type="text" placeholder="SIC with Lunch" class="th-input"></div>
                        <div><label class="th-label">Price Basis</label><select id="fVBasis" class="th-input"><option value="per_pax">Per Pax</option><option value="per_group">Per Group</option></select></div>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div><label class="th-label">Currency <span class="text-red-500">*</span></label><input id="fVCcy" type="text" maxlength="5" placeholder="THB" class="th-input uppercase"></div>
                        <div><label class="th-label">Net Cost</label><input id="fVCost" type="number" step="0.01" placeholder="0.00" class="th-input"></div>
                        <div><label class="th-label">Sell Price <span class="text-red-500">*</span></label><input id="fVSell" type="number" step="0.01" placeholder="0.00" class="th-input"></div>
                    </div>
                    <div class="flex gap-2 justify-end">
                        <button onclick="document.getElementById('varForm').classList.add('hidden')" class="px-4 py-1.5 rounded-lg text-xs font-medium border border-gray-200 text-gray-600">Cancel</button>
                        <button id="btnVSave" class="px-4 py-1.5 rounded-lg text-xs font-semibold bg-[#1A2039] text-white hover:bg-[#252d4a]">Save Variant</button>
                    </div>
                </div>
            </div>
          </div>
        </div>`;
        document.head.insertAdjacentHTML('beforeend',`<style>.th-label{display:block;font-size:.75rem;font-weight:600;color:#4b5563;margin-bottom:.375rem}.th-input{width:100%;padding:.5rem .75rem;border:1px solid #e5e7eb;border-radius:.75rem;font-size:.875rem;outline:none}.th-input:focus{border-color:#50BC81;box-shadow:0 0 0 2px rgba(80,188,129,.25)}</style>`);
    }

    function bindEvents(){
        document.getElementById('btnAdd').onclick=()=>openActForm();
        document.getElementById('srch').oninput=e=>{clearTimeout(st.timer);st.timer=setTimeout(()=>{st.search=e.target.value;st.page=1;load();},400);};
        document.getElementById('fltCountry').onchange=e=>{st.country=e.target.value;st.page=1;load();};
        document.getElementById('fltType').onchange=e=>{st.page=1;load();};
        document.querySelectorAll('.tab-btn').forEach(b=>b.onclick=()=>{st.status=b.dataset.tab;st.page=1;document.querySelectorAll('.tab-btn').forEach(x=>{const on=x.dataset.tab===st.status;x.className=`tab-btn px-4 py-2 text-sm font-medium rounded-xl border transition ${on?'bg-[#1A2039] text-white border-[#1A2039]':'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'}`;});load();});
        document.getElementById('btnASave').onclick=saveActivity;
        document.getElementById('btnAddVar').onclick=()=>{['fVSysId','fVName','fVCcy','fVCost','fVSell'].forEach(id=>thSetVal(id,''));thSetVal('fVBasis','per_pax');document.getElementById('varForm').classList.remove('hidden');};
        document.getElementById('btnVSave').onclick=saveVariant;
    }

    async function load(){
        document.getElementById('tLoading').classList.remove('hidden');
        document.getElementById('tEmpty').classList.add('hidden');
        document.getElementById('actGrid').innerHTML='';
        const type=document.getElementById('fltType')?.value||'';
        const p=new URLSearchParams({page:st.page,limit:st.limit,search:st.search,country_sys_id:st.country,type,status:st.status});
        const data=await thApi(`${API_BASE}api/masterdata/activities/list.php?${p}`);
        document.getElementById('tLoading').classList.add('hidden');
        if (!data.success||!data.data?.length){document.getElementById('tEmpty').classList.remove('hidden');return;}
        document.getElementById('actGrid').innerHTML=data.data.map(actCard).join('');
        thPagination('pgBox',data.pagination,'MdActivities._page');
    }
    function _page(p){st.page=p;load();}

    function actCard(a){
        const stars='★'.repeat(a.popularity||0)+'☆'.repeat(5-(a.popularity||0));
        const typeBg={tour:'bg-green-100 text-green-700',transfer:'bg-blue-100 text-blue-700',both:'bg-purple-100 text-purple-700'};
        return `<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition">
            <div class="h-32 bg-gradient-to-br from-[#50BC81]/20 to-[#1A2039]/20 flex items-center justify-center relative">
                ${a.thumb?`<img src="${a.thumb}" class="w-full h-full object-cover" onerror="this.style.display='none'">`:''}
                <i class="fa-solid fa-person-hiking text-3xl text-[#1A2039]/30 absolute"></i>
            </div>
            <div class="p-4">
                <div class="flex items-start justify-between gap-2 mb-1">
                    <div class="font-semibold text-gray-800 text-sm leading-tight">${a.name}</div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium flex-shrink-0 ${typeBg[a.type]||'bg-gray-100 text-gray-600'}">${a.type}</span>
                </div>
                <div class="text-xs text-gray-400 mb-1">${a.city_name||a.country_name||''} ${a.duration_hours?'· '+a.duration_hours+'h':''}</div>
                <div class="text-xs text-yellow-500 mb-3">${stars}</div>
                <div class="flex items-center gap-2">
                    <button onclick="MdActivities._variants('${a.sys_id}','${a.name.replace(/'/g,"\\'")}')" class="flex-1 py-1.5 rounded-lg text-xs font-medium bg-[#1A2039]/10 text-[#1A2039] hover:bg-[#1A2039]/20 transition"><i class="fa-solid fa-list mr-1"></i> Variants</button>
                    <button onclick="MdActivities._edit('${a.sys_id}')" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-blue-50 text-blue-500"><i class="fa-solid fa-pen text-xs"></i></button>
                    <button onclick="MdActivities._del('${a.sys_id}')" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-red-50 text-red-400"><i class="fa-solid fa-trash text-xs"></i></button>
                </div>
            </div>
        </div>`;
    }

    async function openActForm(sys_id=null){
        ['fASysId','fAName','fACat','fALoc','fADesc','fAMinPax','fAMaxPax'].forEach(id=>thSetVal(id,''));
        thSetVal('fAType','tour'); thSetVal('fADur','0'); thSetVal('fACountry','');
        document.getElementById('actModalTitle').textContent=sys_id?'Edit Activity':'Add Activity';
        if (sys_id){
            thSetVal('fASysId',sys_id);
            const d=await thApi(`${API_BASE}api/masterdata/activities/get.php?sys_id=${sys_id}`);
            const a=d.data;
            thSetVal('fACountry',a.country_sys_id); thSetVal('fAType',a.type);
            thSetVal('fAName',a.name); thSetVal('fACat',a.category);
            thSetVal('fALoc',a.location); thSetVal('fADur',a.duration_hours);
            thSetVal('fADesc',a.short_description); thSetVal('fAMinPax',a.min_pax);
            thSetVal('fAMaxPax',a.max_pax);
        }
        thOpenModal('actModal');
    }
    async function _edit(sys_id){await openActForm(sys_id);}

    async function saveActivity(){
        const cSel=document.getElementById('fACountry');
        const cName=cSel.options[cSel.selectedIndex]?.dataset.name||'';
        const body={sys_id:thVal('fASysId')||undefined,country_sys_id:thVal('fACountry'),country_name:cName,
            type:thVal('fAType'),name:thVal('fAName'),category:thVal('fACat'),
            location:thVal('fALoc'),duration_hours:parseFloat(thVal('fADur')||0),
            short_description:thVal('fADesc'),min_pax:thVal('fAMinPax')||undefined,max_pax:thVal('fAMaxPax')||undefined};
        if (!body.name) return thToast('Name required','error');
        document.getElementById('btnASave').disabled=true;
        const res=await thApi(`${API_BASE}api/masterdata/activities/save.php`,'POST',body);
        document.getElementById('btnASave').disabled=false;
        if (!res.success) return thToast(res.message||'Error','error');
        thToast(res.message||'Saved!'); thCloseModal('actModal'); load();
    }
    async function _del(sys_id){
        if (!thConfirm('Delete this activity?')) return;
        const res=await thApi(`${API_BASE}api/masterdata/activities/delete.php`,'POST',{sys_id});
        thToast(res.message||'Deleted'); load();
    }

    // Variants
    async function _variants(sys_id, name){
        st.viewActivity=sys_id;
        document.getElementById('varPanelTitle').textContent=name;
        document.getElementById('varForm').classList.add('hidden');
        thOpenModal('varPanel');
        await loadVariants(sys_id);
    }
    async function loadVariants(activity_sys_id){
        const el=document.getElementById('varList');
        el.innerHTML='<div class="text-center py-4 text-gray-300"><i class="fa-solid fa-spinner fa-spin"></i></div>';
        const d=await thApi(`${API_BASE}api/masterdata/activities/variant-list.php?activity_sys_id=${activity_sys_id}&status=active`);
        const variants=d.data||[];
        if (!variants.length){el.innerHTML='<p class="text-sm text-gray-400 py-4 italic">No variants yet. Add one above.</p>';return;}
        el.innerHTML=`<div class="space-y-2">${variants.map(v=>`
            <div class="flex items-center justify-between bg-gray-50 rounded-xl px-4 py-3 border border-gray-100">
                <div>
                    <div class="font-medium text-gray-800 text-sm">${v.variant_name}</div>
                    <div class="text-xs text-gray-400">${v.price_basis.replace('_',' ')} · ${v.currency_code} ${Number(v.sell_price).toLocaleString()}/pax</div>
                </div>
                <button onclick="MdActivities._delVariant('${v.sys_id}')" class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-red-50 text-red-400"><i class="fa-solid fa-trash text-xs"></i></button>
            </div>`).join('')}</div>`;
    }
    async function saveVariant(){
        const body={sys_id:thVal('fVSysId')||undefined,activity_sys_id:st.viewActivity,
            country_sys_id:'',variant_name:thVal('fVName'),price_basis:thVal('fVBasis'),
            currency_code:thVal('fVCcy').toUpperCase(),net_cost:parseFloat(thVal('fVCost')||0),
            sell_price:parseFloat(thVal('fVSell')||0),markup_type:'percent',markup_value:0};
        if (!body.variant_name||!body.currency_code||!body.sell_price) return thToast('Name, currency, sell price required','error');
        document.getElementById('btnVSave').disabled=true;
        const res=await thApi(`${API_BASE}api/masterdata/activities/variant-save.php`,'POST',body);
        document.getElementById('btnVSave').disabled=false;
        if (!res.success) return thToast(res.message||'Error','error');
        thToast('Variant saved!'); document.getElementById('varForm').classList.add('hidden'); await loadVariants(st.viewActivity);
    }
    async function _delVariant(sys_id){
        if (!thConfirm('Delete this variant?')) return;
        const res=await thApi(`${API_BASE}api/masterdata/activities/variant-delete.php`,'POST',{sys_id});
        thToast(res.message||'Deleted'); await loadVariants(st.viewActivity);
    }

    return {init,_page,_edit,_del,_variants,_delVariant};
})();
