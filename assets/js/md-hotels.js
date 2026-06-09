const MdHotels = (() => {
    'use strict';
    const MEAL_PLANS=['room_only','bb','hb','fb','ai'];
    const st={page:1,limit:12,search:'',country:'',city:'',status:'active',timer:null,viewHotel:null};
    let countriesCache=[], citiesCache=[];

    function init(){renderShell();bindEvents();loadCountries().then(load);}

    async function loadCountries(){
        const d=await thApi(`${API_BASE}api/masterdata/countries/list.php?limit=200&status=active&for_package=1`);
        countriesCache=d.data||[];
        refreshCountrySelects();
    }
    function refreshCountrySelects(){
        ['fltCountry','fHCountry'].forEach(id=>{
            const el=document.getElementById(id); if(!el)return;
            const label=id==='fltCountry'?'All Countries':'Select country';
            el.innerHTML=`<option value="">${label}</option>`+countriesCache.map(c=>`<option value="${c.sys_id}" data-name="${c.name}">${c.name}</option>`).join('');
        });
    }

    function renderShell(){
        document.getElementById('mainContent').innerHTML=`
        <div class="px-6 py-6 max-w-screen-xl mx-auto">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div><h1 class="text-2xl font-bold text-[#1A2039]">Hotels</h1><p class="text-sm text-gray-400 mt-0.5">Hotel catalog with room types and rates</p></div>
                <button id="btnAdd" class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold bg-[#1A2039] hover:bg-[#252d4a] text-white transition shadow-sm"><i class="fa-solid fa-plus"></i> Add Hotel</button>
            </div>
            <!-- Filters -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-5 flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1"><i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input id="srch" type="text" placeholder="Search hotel name…" class="w-full pl-9 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#50BC81]"></div>
                <select id="fltCountry" class="text-sm px-3 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#50BC81] bg-white"></select>
                <div class="flex gap-2">${['active','trash'].map(t=>`<button data-tab="${t}" class="tab-btn px-4 py-2 text-sm font-medium rounded-xl border transition ${t==='active'?'bg-[#1A2039] text-white border-[#1A2039]':'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'}">${t==='active'?'Active':'<i class="fa-solid fa-trash mr-1"></i>Trash'}</button>`).join('')}</div>
            </div>
            <!-- Grid -->
            <div id="hotelGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-5"></div>
            <div id="tLoading" class="text-center py-16 text-gray-300"><i class="fa-solid fa-spinner fa-spin text-3xl"></i></div>
            <div id="tEmpty" class="hidden text-center py-16 text-gray-300"><i class="fa-solid fa-hotel text-5xl mb-3 block opacity-30"></i><p class="text-sm font-medium">No hotels found</p></div>
            <div id="pgBox"></div>
        </div>

        <!-- Hotel Form Modal -->
        <div id="hotelModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
          <div class="w-full max-w-2xl max-h-[90vh] flex flex-col bg-white rounded-2xl shadow-2xl">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
                <h2 id="hotelModalTitle" class="text-lg font-bold text-[#1A2039]">Add Hotel</h2>
                <button onclick="thCloseModal('hotelModal')" class="text-gray-400 hover:text-gray-600 w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100"><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="flex-1 overflow-y-auto px-6 py-5 space-y-4">
                <input type="hidden" id="fHSysId">
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="th-label">Country <span class="text-red-500">*</span></label><select id="fHCountry" class="th-input"></select></div>
                    <div><label class="th-label">Star Rating</label>
                        <select id="fHStars" class="th-input"><option value="">Unrated</option>${[1,2,3,4,5].map(s=>`<option value="${s}">${'⭐'.repeat(s)} ${s}-star</option>`).join('')}</select>
                    </div>
                </div>
                <div><label class="th-label">Hotel Name <span class="text-red-500">*</span></label><input id="fHName" type="text" placeholder="Hotel name" class="th-input"></div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="th-label">Check-in Time</label><input id="fHCi" type="time" class="th-input"></div>
                    <div><label class="th-label">Check-out Time</label><input id="fHCo" type="time" class="th-input"></div>
                </div>
                <div><label class="th-label">Address</label><input id="fHAddr" type="text" placeholder="Full address" class="th-input"></div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="th-label">Phone</label><input id="fHPhone" type="text" placeholder="+66 2 xxx xxxx" class="th-input"></div>
                    <div><label class="th-label">Email</label><input id="fHEmail" type="email" placeholder="info@hotel.com" class="th-input"></div>
                </div>
                <div><label class="th-label">Description</label><textarea id="fHDesc" rows="2" class="th-input resize-none" placeholder="Brief hotel description"></textarea></div>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3 flex-shrink-0">
                <button onclick="thCloseModal('hotelModal')" class="px-5 py-2 rounded-xl text-sm font-medium border border-gray-200 text-gray-600 hover:bg-gray-50">Cancel</button>
                <button id="btnHSave" class="px-6 py-2 rounded-xl text-sm font-semibold bg-[#1A2039] hover:bg-[#252d4a] text-white transition shadow-sm">Save Hotel</button>
            </div>
          </div>
        </div>

        <!-- Hotel Detail Panel (Room Types + Rates) -->
        <div id="detailPanel" class="hidden fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 backdrop-blur-sm p-4">
          <div class="w-full max-w-3xl max-h-[90vh] flex flex-col bg-white rounded-2xl shadow-2xl">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
                <div><h2 id="detailTitle" class="text-lg font-bold text-[#1A2039]">Hotel Detail</h2><p id="detailSub" class="text-xs text-gray-400"></p></div>
                <button onclick="thCloseModal('detailPanel')" class="text-gray-400 hover:text-gray-600 w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100"><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="flex-1 overflow-y-auto px-6 py-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-[#1A2039]">Room Types</h3>
                    <button id="btnAddRoomType" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-[#50BC81] hover:bg-[#3da868] text-white transition"><i class="fa-solid fa-plus"></i> Add Room Type</button>
                </div>
                <div id="roomTypeList"></div>
            </div>
          </div>
        </div>

        <!-- Room Type Modal -->
        <div id="rtModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
          <div class="w-full max-w-lg bg-white rounded-2xl shadow-2xl">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h2 id="rtModalTitle" class="text-lg font-bold text-[#1A2039]">Add Room Type</h2>
                <button onclick="thCloseModal('rtModal')" class="text-gray-400 hover:text-gray-600 w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100"><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="px-6 py-5 space-y-4">
                <input type="hidden" id="fRTSysId"><input type="hidden" id="fRTHotelSysId">
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="th-label">Room Name <span class="text-red-500">*</span></label><input id="fRTName" type="text" placeholder="Deluxe Twin" class="th-input"></div>
                    <div><label class="th-label">Bed Config</label><input id="fRTBed" type="text" placeholder="1 King Bed" class="th-input"></div>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div><label class="th-label">Max Adults</label><input id="fRTAdults" type="number" min="1" value="2" class="th-input"></div>
                    <div><label class="th-label">Max Children</label><input id="fRTChildren" type="number" min="0" value="0" class="th-input"></div>
                    <div><label class="th-label">Size (sqm)</label><input id="fRTSize" type="number" min="0" placeholder="28" class="th-input"></div>
                </div>
                <div><label class="th-label">Description</label><textarea id="fRTDesc" rows="2" class="th-input resize-none" placeholder="Brief description"></textarea></div>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3">
                <button onclick="thCloseModal('rtModal')" class="px-5 py-2 rounded-xl text-sm font-medium border border-gray-200 text-gray-600 hover:bg-gray-50">Cancel</button>
                <button id="btnRTSave" class="px-6 py-2 rounded-xl text-sm font-semibold bg-[#1A2039] hover:bg-[#252d4a] text-white transition shadow-sm">Save Room Type</button>
            </div>
          </div>
        </div>

        <!-- Room Rate Modal -->
        <div id="rrModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
          <div class="w-full max-w-lg bg-white rounded-2xl shadow-2xl">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h2 id="rrModalTitle" class="text-lg font-bold text-[#1A2039]">Add Room Rate</h2>
                <button onclick="thCloseModal('rrModal')" class="text-gray-400 hover:text-gray-600 w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100"><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="px-6 py-5 space-y-4">
                <input type="hidden" id="fRRSysId"><input type="hidden" id="fRRRoomTypeSysId"><input type="hidden" id="fRRHotelSysId">
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="th-label">Meal Plan</label><select id="fRRMeal" class="th-input">${MEAL_PLANS.map(m=>`<option value="${m}">${m.toUpperCase()}</option>`).join('')}</select></div>
                    <div><label class="th-label">Currency <span class="text-red-500">*</span></label><input id="fRRCcy" type="text" maxlength="5" placeholder="THB" class="th-input uppercase"></div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="th-label">Valid From <span class="text-red-500">*</span></label><input id="fRRFrom" type="date" class="th-input"></div>
                    <div><label class="th-label">Valid To <span class="text-red-500">*</span></label><input id="fRRTo" type="date" class="th-input"></div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="th-label">Net Cost/night <span class="text-red-500">*</span></label><input id="fRRCost" type="number" step="0.01" placeholder="0.00" class="th-input"></div>
                    <div><label class="th-label">Sell Price/night <span class="text-red-500">*</span></label><input id="fRRSell" type="number" step="0.01" placeholder="0.00" class="th-input"></div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="th-label">Markup Type</label><select id="fRRMkType" class="th-input"><option value="percent">Percent %</option><option value="fixed">Fixed Amount</option></select></div>
                    <div><label class="th-label">Markup Value</label><input id="fRRMkVal" type="number" step="0.01" value="0" class="th-input"></div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3">
                <button onclick="thCloseModal('rrModal')" class="px-5 py-2 rounded-xl text-sm font-medium border border-gray-200 text-gray-600 hover:bg-gray-50">Cancel</button>
                <button id="btnRRSave" class="px-6 py-2 rounded-xl text-sm font-semibold bg-[#1A2039] hover:bg-[#252d4a] text-white transition shadow-sm">Save Rate</button>
            </div>
          </div>
        </div>`;
        document.head.insertAdjacentHTML('beforeend',`<style>.th-label{display:block;font-size:.75rem;font-weight:600;color:#4b5563;margin-bottom:.375rem}.th-input{width:100%;padding:.5rem .75rem;border:1px solid #e5e7eb;border-radius:.75rem;font-size:.875rem;outline:none}.th-input:focus{border-color:#50BC81;box-shadow:0 0 0 2px rgba(80,188,129,.25)}</style>`);
    }

    function bindEvents(){
        document.getElementById('btnAdd').onclick=()=>openHotelForm();
        document.getElementById('srch').oninput=e=>{clearTimeout(st.timer);st.timer=setTimeout(()=>{st.search=e.target.value;st.page=1;load();},400);};
        document.getElementById('fltCountry').onchange=e=>{st.country=e.target.value;st.page=1;load();};
        document.querySelectorAll('.tab-btn').forEach(b=>b.onclick=()=>{
            st.status=b.dataset.tab;st.page=1;
            document.querySelectorAll('.tab-btn').forEach(x=>{const on=x.dataset.tab===st.status;x.className=`tab-btn px-4 py-2 text-sm font-medium rounded-xl border transition ${on?'bg-[#1A2039] text-white border-[#1A2039]':'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'}`;});
            load();
        });
        document.getElementById('btnHSave').onclick=saveHotel;
        document.getElementById('btnAddRoomType').onclick=()=>openRoomTypeForm(null,st.viewHotel);
        document.getElementById('btnRTSave').onclick=saveRoomType;
        document.getElementById('btnRRSave').onclick=saveRoomRate;
    }

    async function load(){
        document.getElementById('tLoading').classList.remove('hidden');
        document.getElementById('tEmpty').classList.add('hidden');
        document.getElementById('hotelGrid').innerHTML='';
        const p=new URLSearchParams({page:st.page,limit:st.limit,search:st.search,country_sys_id:st.country,status:st.status});
        const data=await thApi(`${API_BASE}api/masterdata/hotels/list.php?${p}`);
        document.getElementById('tLoading').classList.add('hidden');
        if (!data.success||!data.data?.length){document.getElementById('tEmpty').classList.remove('hidden');return;}
        document.getElementById('hotelGrid').innerHTML=data.data.map(hotelCard).join('');
        thPagination('pgBox',data.pagination,'MdHotels._page');
    }
    function _page(p){st.page=p;load();}

    function hotelCard(h){
        const stars=h.star_rating?'⭐'.repeat(h.star_rating):'';
        return `<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition">
            <div class="h-36 bg-gradient-to-br from-[#1A2039] to-[#2d3a5c] flex items-center justify-center relative">
                ${h.thumb?`<img src="${h.thumb}" class="w-full h-full object-cover" onerror="this.style.display='none'">`:''}
                <i class="fa-solid fa-hotel text-4xl text-white/20 absolute"></i>
            </div>
            <div class="p-4">
                <div class="flex items-start justify-between gap-2 mb-1">
                    <div class="font-semibold text-gray-800 leading-tight">${h.name}</div>
                    <div class="text-sm flex-shrink-0">${stars}</div>
                </div>
                <div class="text-xs text-gray-400 mb-3">${h.address||''}${h.country_name?', '+h.country_name:''}</div>
                <div class="flex items-center gap-2">
                    <button onclick="MdHotels._detail('${h.sys_id}')" class="flex-1 py-1.5 rounded-lg text-xs font-medium bg-[#1A2039]/10 text-[#1A2039] hover:bg-[#1A2039]/20 transition">
                        <i class="fa-solid fa-door-open mr-1"></i> Rooms
                    </button>
                    <button onclick="MdHotels._edit('${h.sys_id}')" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-blue-50 text-blue-500"><i class="fa-solid fa-pen text-xs"></i></button>
                    <button onclick="MdHotels._del('${h.sys_id}')" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-red-50 text-red-400"><i class="fa-solid fa-trash text-xs"></i></button>
                </div>
            </div>
        </div>`;
    }

    // Hotel CRUD
    async function openHotelForm(sys_id=null){
        ['fHSysId','fHName','fHAddr','fHPhone','fHEmail','fHDesc','fHStars','fHCi','fHCo'].forEach(id=>thSetVal(id,''));
        thSetVal('fHCountry',''); document.getElementById('hotelModalTitle').textContent=sys_id?'Edit Hotel':'Add Hotel';
        if (sys_id){
            thSetVal('fHSysId',sys_id);
            const d=await thApi(`${API_BASE}api/masterdata/hotels/get.php?sys_id=${sys_id}`);
            const h=d.data;
            thSetVal('fHCountry',h.country_sys_id); thSetVal('fHStars',h.star_rating||'');
            thSetVal('fHName',h.name); thSetVal('fHAddr',h.address); thSetVal('fHPhone',h.phone);
            thSetVal('fHEmail',h.email); thSetVal('fHDesc',h.description);
            thSetVal('fHCi',h.check_in_time||''); thSetVal('fHCo',h.check_out_time||'');
        }
        thOpenModal('hotelModal');
    }
    async function _edit(sys_id){await openHotelForm(sys_id);}

    async function saveHotel(){
        const cSel=document.getElementById('fHCountry');
        const cName=cSel.options[cSel.selectedIndex]?.dataset.name||'';
        const body={sys_id:thVal('fHSysId')||undefined,country_sys_id:thVal('fHCountry'),country_name:cName,
            name:thVal('fHName'),star_rating:thVal('fHStars')||undefined,
            address:thVal('fHAddr'),phone:thVal('fHPhone'),email:thVal('fHEmail'),
            description:thVal('fHDesc'),check_in_time:thVal('fHCi'),check_out_time:thVal('fHCo'),amenities:[],images:[]};
        if (!body.name) return thToast('Hotel name required','error');
        document.getElementById('btnHSave').disabled=true;
        const res=await thApi(`${API_BASE}api/masterdata/hotels/save.php`,'POST',body);
        document.getElementById('btnHSave').disabled=false;
        if (!res.success) return thToast(res.message||'Error','error');
        thToast(res.message||'Saved!'); thCloseModal('hotelModal'); load();
    }
    async function _del(sys_id){
        if (!thConfirm('Delete this hotel?')) return;
        const res=await thApi(`${API_BASE}api/masterdata/hotels/delete.php`,'POST',{sys_id});
        thToast(res.message||'Deleted'); load();
    }

    // Detail panel - room types + rates
    async function _detail(sys_id){
        st.viewHotel=sys_id;
        const d=await thApi(`${API_BASE}api/masterdata/hotels/get.php?sys_id=${sys_id}`);
        document.getElementById('detailTitle').textContent=d.data?.name||'Hotel Detail';
        document.getElementById('detailSub').textContent=`${d.data?.city_name||''}, ${d.data?.country_name||''}`;
        thOpenModal('detailPanel');
        await loadRoomTypes(sys_id);
    }

    async function loadRoomTypes(hotel_sys_id){
        const el=document.getElementById('roomTypeList');
        el.innerHTML='<div class="text-center py-8 text-gray-300"><i class="fa-solid fa-spinner fa-spin"></i></div>';
        const d=await thApi(`${API_BASE}api/masterdata/hotels/room-type-list.php?hotel_sys_id=${hotel_sys_id}&status=active`);
        const rts=d.data||[];
        if (!rts.length){el.innerHTML='<p class="text-sm text-gray-400 py-4 italic">No room types yet.</p>';return;}
        el.innerHTML=rts.map(rt=>`
            <div class="bg-gray-50 rounded-xl p-4 mb-3">
                <div class="flex items-center justify-between mb-2">
                    <div>
                        <div class="font-semibold text-gray-800">${rt.room_name}</div>
                        <div class="text-xs text-gray-400">${rt.bed_config||''} · ${rt.max_adults} adults · ${rt.max_children} children ${rt.size_sqm?'· '+rt.size_sqm+'sqm':''}</div>
                    </div>
                    <div class="flex gap-1">
                        <button onclick="MdHotels._addRate('${rt.sys_id}','${hotel_sys_id}')" class="flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium bg-[#50BC81] text-white hover:bg-[#3da868] transition"><i class="fa-solid fa-plus"></i> Rate</button>
                        <button onclick="MdHotels._delRoomType('${rt.sys_id}')" class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-red-50 text-red-400"><i class="fa-solid fa-trash text-xs"></i></button>
                    </div>
                </div>
                <div id="rates-${rt.sys_id}"><div class="text-xs text-gray-400">Loading rates…</div></div>
            </div>`).join('');
        rts.forEach(rt=>loadRates(rt.sys_id));
    }

    async function loadRates(room_type_sys_id){
        const el=document.getElementById(`rates-${room_type_sys_id}`);
        const d=await thApi(`${API_BASE}api/masterdata/hotels/room-rate-list.php?room_type_sys_id=${room_type_sys_id}&status=active`);
        const rates=d.data||[];
        if (!rates.length){el.innerHTML='<p class="text-xs text-gray-400 italic">No rates yet.</p>';return;}
        el.innerHTML=`<div class="space-y-1 mt-2">${rates.map(r=>`
            <div class="flex items-center justify-between bg-white rounded-lg px-3 py-2 text-xs border border-gray-100">
                <span class="font-medium text-gray-700">${r.meal_plan.toUpperCase()}</span>
                <span class="text-gray-500">${r.valid_from} → ${r.valid_to}</span>
                <span class="font-mono text-[#1A2039] font-semibold">${r.currency_code} ${Number(r.sell_price).toLocaleString()}/night</span>
                <button onclick="MdHotels._delRate('${r.sys_id}','${room_type_sys_id}')" class="text-red-400 hover:text-red-600"><i class="fa-solid fa-times text-xs"></i></button>
            </div>`).join('')}</div>`;
    }

    function openRoomTypeForm(sys_id=null,hotel_sys_id){
        ['fRTSysId','fRTName','fRTBed','fRTDesc'].forEach(id=>thSetVal(id,''));
        thSetVal('fRTAdults','2'); thSetVal('fRTChildren','0'); thSetVal('fRTSize','');
        thSetVal('fRTHotelSysId', hotel_sys_id||'');
        document.getElementById('rtModalTitle').textContent=sys_id?'Edit Room Type':'Add Room Type';
        if (sys_id) thSetVal('fRTSysId',sys_id);
        thOpenModal('rtModal');
    }
    async function saveRoomType(){
        const hotel_sys_id=thVal('fRTHotelSysId');
        const body={sys_id:thVal('fRTSysId')||undefined,hotel_sys_id,hotel_name:'',
            room_name:thVal('fRTName'),description:thVal('fRTDesc'),bed_config:thVal('fRTBed'),
            max_adults:parseInt(thVal('fRTAdults')||2),max_children:parseInt(thVal('fRTChildren')||0),
            standard_occupancy:parseInt(thVal('fRTAdults')||2),size_sqm:thVal('fRTSize')||undefined};
        if (!body.room_name) return thToast('Room name required','error');
        document.getElementById('btnRTSave').disabled=true;
        const res=await thApi(`${API_BASE}api/masterdata/hotels/room-type-save.php`,'POST',body);
        document.getElementById('btnRTSave').disabled=false;
        if (!res.success) return thToast(res.message||'Error','error');
        thToast(res.message||'Saved!'); thCloseModal('rtModal'); await loadRoomTypes(hotel_sys_id);
    }
    async function _delRoomType(sys_id){
        if (!thConfirm('Delete this room type?')) return;
        const res=await thApi(`${API_BASE}api/masterdata/hotels/room-type-delete.php`,'POST',{sys_id});
        thToast(res.message||'Deleted'); await loadRoomTypes(st.viewHotel);
    }

    function _addRate(room_type_sys_id, hotel_sys_id){
        ['fRRSysId'].forEach(id=>thSetVal(id,''));
        thSetVal('fRRRoomTypeSysId',room_type_sys_id); thSetVal('fRRHotelSysId',hotel_sys_id);
        thSetVal('fRRMeal','bb'); thSetVal('fRRCcy',''); thSetVal('fRRCost',''); thSetVal('fRRSell','');
        thSetVal('fRRMkType','percent'); thSetVal('fRRMkVal','0');
        const today=new Date().toISOString().split('T')[0];
        const nextYear=new Date(Date.now()+365*86400000).toISOString().split('T')[0];
        thSetVal('fRRFrom',today); thSetVal('fRRTo',nextYear);
        document.getElementById('rrModalTitle').textContent='Add Room Rate';
        thOpenModal('rrModal');
    }
    async function saveRoomRate(){
        const body={sys_id:thVal('fRRSysId')||undefined,
            room_type_sys_id:thVal('fRRRoomTypeSysId'),hotel_sys_id:thVal('fRRHotelSysId'),
            meal_plan:thVal('fRRMeal'),currency_code:thVal('fRRCcy').toUpperCase(),
            net_cost:parseFloat(thVal('fRRCost')||0),sell_price:parseFloat(thVal('fRRSell')||0),
            markup_type:thVal('fRRMkType'),markup_value:parseFloat(thVal('fRRMkVal')||0),
            valid_from:thVal('fRRFrom'),valid_to:thVal('fRRTo'),occupancy_basis:'per_room'};
        if (!body.currency_code||!body.net_cost) return thToast('Currency and cost required','error');
        document.getElementById('btnRRSave').disabled=true;
        const res=await thApi(`${API_BASE}api/masterdata/hotels/room-rate-save.php`,'POST',body);
        document.getElementById('btnRRSave').disabled=false;
        if (!res.success) return thToast(res.message||'Error','error');
        thToast(res.message||'Rate saved!'); thCloseModal('rrModal'); await loadRates(body.room_type_sys_id);
    }
    async function _delRate(sys_id, room_type_sys_id){
        if (!thConfirm('Delete this rate?')) return;
        const res=await thApi(`${API_BASE}api/masterdata/hotels/room-rate-delete.php`,'POST',{sys_id});
        thToast(res.message||'Deleted'); await loadRates(room_type_sys_id);
    }

    return {init,_page,_edit,_del,_detail,_addRate,_delRoomType,_delRate};
})();
