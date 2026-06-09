const MdCars = (() => {
    'use strict';
    const TYPES = ['sedan','van','suv','minibus','microbus','coaster','bus','other'];
    const st = { page:1, limit:15, search:'', country:'', type:'', status:'active', timer:null };
    let countriesCache = [];

    function init() { renderShell(); bindEvents(); loadCountries().then(load); }

    async function loadCountries() {
        const d = await thApi(`${API_BASE}api/masterdata/countries/list.php?limit=200&status=active&for_package=1`);
        countriesCache = d.data||[];
        const sel = document.getElementById('fltCountry');
        if (sel) sel.innerHTML = '<option value="">All Countries</option>' + countriesCache.map(c=>`<option value="${c.sys_id}">${c.name}</option>`).join('');
        const mSel = document.getElementById('fCountry');
        if (mSel) mSel.innerHTML = '<option value="">Select country</option>' + countriesCache.map(c=>`<option value="${c.sys_id}" data-name="${c.name}">${c.name}</option>`).join('');
    }

    function renderShell() {
        document.getElementById('mainContent').innerHTML = `
        <div class="px-6 py-6 max-w-screen-xl mx-auto">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div><h1 class="text-2xl font-bold text-[#1A2039]">Cars / Vehicles</h1>
                    <p class="text-sm text-gray-400 mt-0.5">Vehicle catalog scoped by country</p></div>
                <button id="btnAdd" class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold bg-[#1A2039] hover:bg-[#252d4a] text-white transition shadow-sm">
                    <i class="fa-solid fa-plus"></i> Add Car
                </button>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-5 flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1"><i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input id="srch" type="text" placeholder="Search car name…" class="w-full pl-9 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#50BC81]"></div>
                <select id="fltCountry" class="text-sm px-3 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#50BC81] bg-white"></select>
                <select id="fltType" class="text-sm px-3 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#50BC81] bg-white">
                    <option value="">All Types</option>${TYPES.map(t=>`<option value="${t}">${t.charAt(0).toUpperCase()+t.slice(1)}</option>`).join('')}
                </select>
                <div class="flex gap-2">${['active','trash'].map(t=>`<button data-tab="${t}" class="tab-btn px-4 py-2 text-sm font-medium rounded-xl border transition ${t==='active'?'bg-[#1A2039] text-white border-[#1A2039]':'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'}">${t==='active'?'Active':'<i class="fa-solid fa-trash mr-1"></i>Trash'}</button>`).join('')}</div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-4">
                <table class="w-full text-sm">
                    <thead><tr class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wide border-b border-gray-200">
                        <th class="px-4 py-3 text-left">Car / Vehicle</th>
                        <th class="px-4 py-3 text-left w-28">Type</th>
                        <th class="px-4 py-3 text-left">Country</th>
                        <th class="px-4 py-3 text-center w-20">Seats</th>
                        <th class="px-4 py-3 text-left w-28">Luggage</th>
                        <th class="px-4 py-3 text-center w-28">Actions</th>
                    </tr></thead>
                    <tbody id="tBody" class="divide-y divide-gray-50"></tbody>
                </table>
                <div id="tEmpty" class="hidden text-center py-16 text-gray-300"><i class="fa-solid fa-car-side text-5xl mb-3 block opacity-30"></i><p class="text-sm font-medium">No vehicles found</p></div>
                <div id="tLoading" class="text-center py-16 text-gray-300"><i class="fa-solid fa-spinner fa-spin text-3xl"></i></div>
            </div>
            <div id="pgBox"></div>
        </div>
        <div id="modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
          <div class="w-full max-w-lg bg-white rounded-2xl shadow-2xl">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h2 id="modalTitle" class="text-lg font-bold text-[#1A2039]">Add Car</h2>
                <button onclick="thCloseModal('modal')" class="text-gray-400 hover:text-gray-600 w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100"><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="px-6 py-5 space-y-4">
                <input type="hidden" id="fSysId">
                <div><label class="th-label">Country <span class="text-red-500">*</span></label><select id="fCountry" class="th-input"></select></div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="th-label">Car Name <span class="text-red-500">*</span></label><input id="fName" type="text" placeholder="Toyota Hiace Van" class="th-input"></div>
                    <div><label class="th-label">Type <span class="text-red-500">*</span></label><select id="fType" class="th-input">${TYPES.map(t=>`<option value="${t}">${t.charAt(0).toUpperCase()+t.slice(1)}</option>`).join('')}</select></div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="th-label">Seats <span class="text-red-500">*</span></label><input id="fSeats" type="number" min="1" max="60" value="4" class="th-input"></div>
                    <div><label class="th-label">Max Luggage</label><input id="fLuggage" type="text" placeholder="20kg / 2 bags" class="th-input"></div>
                </div>
                <div class="flex items-center gap-2">
                    <input id="fHasLuggage" type="checkbox" checked class="w-4 h-4 rounded accent-[#50BC81]">
                    <label class="text-sm text-gray-700">Has luggage space</label>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3">
                <button onclick="thCloseModal('modal')" class="px-5 py-2 rounded-xl text-sm font-medium border border-gray-200 text-gray-600 hover:bg-gray-50">Cancel</button>
                <button id="btnSave" class="px-6 py-2 rounded-xl text-sm font-semibold bg-[#1A2039] hover:bg-[#252d4a] text-white transition shadow-sm">Save Car</button>
            </div>
          </div>
        </div>`;
        document.head.insertAdjacentHTML('beforeend',`<style>.th-label{display:block;font-size:.75rem;font-weight:600;color:#4b5563;margin-bottom:.375rem}.th-input{width:100%;padding:.5rem .75rem;border:1px solid #e5e7eb;border-radius:.75rem;font-size:.875rem;outline:none}.th-input:focus{border-color:#50BC81;box-shadow:0 0 0 2px rgba(80,188,129,.25)}</style>`);
    }

    function bindEvents() {
        document.getElementById('btnAdd').onclick = () => openForm();
        document.getElementById('srch').oninput = e => { clearTimeout(st.timer); st.timer=setTimeout(()=>{st.search=e.target.value;st.page=1;load();},400); };
        document.getElementById('fltCountry').onchange = e => { st.country=e.target.value; st.page=1; load(); };
        document.getElementById('fltType').onchange    = e => { st.type=e.target.value;    st.page=1; load(); };
        document.querySelectorAll('.tab-btn').forEach(b=>b.onclick=()=>{
            st.status=b.dataset.tab; st.page=1;
            document.querySelectorAll('.tab-btn').forEach(x=>{const on=x.dataset.tab===st.status;x.className=`tab-btn px-4 py-2 text-sm font-medium rounded-xl border transition ${on?'bg-[#1A2039] text-white border-[#1A2039]':'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'}`;});
            load();
        });
        document.getElementById('btnSave').onclick = save;
    }

    async function load() {
        document.getElementById('tLoading').classList.remove('hidden');
        document.getElementById('tEmpty').classList.add('hidden');
        document.getElementById('tBody').innerHTML='';
        const p=new URLSearchParams({page:st.page,limit:st.limit,search:st.search,country_sys_id:st.country,type:st.type,status:st.status});
        const data=await thApi(`${API_BASE}api/masterdata/cars/list.php?${p}`);
        document.getElementById('tLoading').classList.add('hidden');
        if (!data.success||!data.data?.length){document.getElementById('tEmpty').classList.remove('hidden');return;}
        document.getElementById('tBody').innerHTML=data.data.map(c=>`
            <tr class="hover:bg-gray-50 transition">
                <td class="px-4 py-3"><div class="font-semibold text-gray-800">${c.name}</div><div class="text-xs text-gray-400">${c.sys_id}</div></td>
                <td class="px-4 py-3"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700">${c.type}</span></td>
                <td class="px-4 py-3 text-sm text-gray-600">${c.country_name||'—'}</td>
                <td class="px-4 py-3 text-center text-sm font-medium text-gray-700"><i class="fa-solid fa-users mr-1 text-gray-400"></i>${c.seats}</td>
                <td class="px-4 py-3 text-sm text-gray-600">${c.max_luggage||'—'}</td>
                <td class="px-4 py-3"><div class="flex items-center justify-center gap-1">
                    <button onclick="MdCars._edit('${c.sys_id}')" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-blue-50 text-blue-500"><i class="fa-solid fa-pen text-xs"></i></button>
                    <button onclick="MdCars._del('${c.sys_id}')" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-red-50 text-red-400"><i class="fa-solid fa-trash text-xs"></i></button>
                </div></td>
            </tr>`).join('');
        thPagination('pgBox',data.pagination,'MdCars._page');
    }
    function _page(p){st.page=p;load();}

    async function openForm(sys_id=null){
        ['fSysId','fName','fLuggage'].forEach(id=>thSetVal(id,''));
        thSetVal('fType','sedan'); thSetVal('fSeats','4'); thSetVal('fCountry','');
        document.getElementById('fHasLuggage').checked=true;
        document.getElementById('modalTitle').textContent=sys_id?'Edit Car':'Add Car';
        if (sys_id){
            thSetVal('fSysId',sys_id);
            const d=await thApi(`${API_BASE}api/masterdata/cars/get.php?sys_id=${sys_id}`);
            const c=d.data; thSetVal('fCountry',c.country_sys_id); thSetVal('fName',c.name);
            thSetVal('fType',c.type); thSetVal('fSeats',c.seats); thSetVal('fLuggage',c.max_luggage);
            document.getElementById('fHasLuggage').checked=!!c.has_luggage;
        }
        thOpenModal('modal');
    }
    async function _edit(sys_id){await openForm(sys_id);}

    async function save(){
        const cSel=document.getElementById('fCountry');
        const cName=cSel.options[cSel.selectedIndex]?.dataset.name||'';
        const body={sys_id:thVal('fSysId')||undefined, country_sys_id:thVal('fCountry'), country_name:cName,
            name:thVal('fName'), type:thVal('fType'), seats:parseInt(thVal('fSeats')||4),
            has_luggage:document.getElementById('fHasLuggage').checked, max_luggage:thVal('fLuggage')};
        if (!body.name) return thToast('Name required','error');
        document.getElementById('btnSave').disabled=true;
        const res=await thApi(`${API_BASE}api/masterdata/cars/save.php`,'POST',body);
        document.getElementById('btnSave').disabled=false;
        if (!res.success) return thToast(res.message||'Error','error');
        thToast(res.message||'Saved!'); thCloseModal('modal'); load();
    }
    async function _del(sys_id){
        if (!thConfirm('Delete this vehicle?')) return;
        const res=await thApi(`${API_BASE}api/masterdata/cars/delete.php`,'POST',{sys_id});
        thToast(res.message||'Deleted'); load();
    }
    return {init,_page,_edit,_del};
})();
