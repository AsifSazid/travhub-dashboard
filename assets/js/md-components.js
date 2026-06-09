const MdComponents = (() => {
    'use strict';
    const CATS=['insurance','visa','guide','sim','tip','porterage','meal','ticket','fee','misc'];
    const st={page:1,limit:15,search:'',status:'active',timer:null,viewComp:null};

    function init(){renderShell();bindEvents();load();}

    function renderShell(){
        document.getElementById('mainContent').innerHTML=`
        <div class="px-6 py-6 max-w-screen-xl mx-auto">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div><h1 class="text-2xl font-bold text-[#1A2039]">Components</h1><p class="text-sm text-gray-400 mt-0.5">Extras: insurance, visa, SIM, tips, fees…</p></div>
                <button id="btnAdd" class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold bg-[#1A2039] hover:bg-[#252d4a] text-white transition shadow-sm"><i class="fa-solid fa-plus"></i> Add Component</button>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-5 flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1"><i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input id="srch" type="text" placeholder="Search component…" class="w-full pl-9 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#50BC81]"></div>
                <select id="fltCat" class="text-sm px-3 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#50BC81] bg-white">
                    <option value="">All Categories</option>${CATS.map(c=>`<option value="${c}">${c.charAt(0).toUpperCase()+c.slice(1)}</option>`).join('')}
                </select>
                <div class="flex gap-2">${['active','trash'].map(t=>`<button data-tab="${t}" class="tab-btn px-4 py-2 text-sm font-medium rounded-xl border transition ${t==='active'?'bg-[#1A2039] text-white border-[#1A2039]':'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'}">${t==='active'?'Active':'<i class="fa-solid fa-trash mr-1"></i>Trash'}</button>`).join('')}</div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-4">
                <table class="w-full text-sm">
                    <thead><tr class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wide border-b border-gray-200">
                        <th class="px-4 py-3 text-left">Component</th>
                        <th class="px-4 py-3 text-left w-28">Category</th>
                        <th class="px-4 py-3 text-center w-28">Actions</th>
                    </tr></thead>
                    <tbody id="tBody" class="divide-y divide-gray-50"></tbody>
                </table>
                <div id="tEmpty" class="hidden text-center py-16 text-gray-300"><i class="fa-solid fa-puzzle-piece text-5xl mb-3 block opacity-30"></i><p class="text-sm font-medium">No components found</p></div>
                <div id="tLoading" class="text-center py-16 text-gray-300"><i class="fa-solid fa-spinner fa-spin text-3xl"></i></div>
            </div>
            <div id="pgBox"></div>
        </div>

        <!-- Modal -->
        <div id="modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
          <div class="w-full max-w-lg bg-white rounded-2xl shadow-2xl">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h2 id="modalTitle" class="text-lg font-bold text-[#1A2039]">Add Component</h2>
                <button onclick="thCloseModal('modal')" class="text-gray-400 hover:text-gray-600 w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100"><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="px-6 py-5 space-y-4">
                <input type="hidden" id="fSysId">
                <div><label class="th-label">Name <span class="text-red-500">*</span></label><input id="fName" type="text" placeholder="Travel Insurance" class="th-input"></div>
                <div><label class="th-label">Category</label>
                    <select id="fCat" class="th-input">${CATS.map(c=>`<option value="${c}">${c.charAt(0).toUpperCase()+c.slice(1)}</option>`).join('')}</select>
                </div>
                <div><label class="th-label">Description</label><textarea id="fDesc" rows="2" class="th-input resize-none" placeholder="Brief description"></textarea></div>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3">
                <button onclick="thCloseModal('modal')" class="px-5 py-2 rounded-xl text-sm font-medium border border-gray-200 text-gray-600 hover:bg-gray-50">Cancel</button>
                <button id="btnSave" class="px-6 py-2 rounded-xl text-sm font-semibold bg-[#1A2039] hover:bg-[#252d4a] text-white transition shadow-sm">Save</button>
            </div>
          </div>
        </div>`;
        document.head.insertAdjacentHTML('beforeend',`<style>.th-label{display:block;font-size:.75rem;font-weight:600;color:#4b5563;margin-bottom:.375rem}.th-input{width:100%;padding:.5rem .75rem;border:1px solid #e5e7eb;border-radius:.75rem;font-size:.875rem;outline:none}.th-input:focus{border-color:#50BC81;box-shadow:0 0 0 2px rgba(80,188,129,.25)}</style>`);
    }

    function bindEvents(){
        document.getElementById('btnAdd').onclick=()=>openForm();
        document.getElementById('srch').oninput=e=>{clearTimeout(st.timer);st.timer=setTimeout(()=>{st.search=e.target.value;st.page=1;load();},400);};
        document.getElementById('fltCat').onchange=e=>{st.page=1;load();};
        document.querySelectorAll('.tab-btn').forEach(b=>b.onclick=()=>{st.status=b.dataset.tab;st.page=1;document.querySelectorAll('.tab-btn').forEach(x=>{const on=x.dataset.tab===st.status;x.className=`tab-btn px-4 py-2 text-sm font-medium rounded-xl border transition ${on?'bg-[#1A2039] text-white border-[#1A2039]':'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'}`;});load();});
        document.getElementById('btnSave').onclick=save;
    }

    async function load(){
        document.getElementById('tLoading').classList.remove('hidden');
        document.getElementById('tEmpty').classList.add('hidden');
        document.getElementById('tBody').innerHTML='';
        const cat=document.getElementById('fltCat')?.value||'';
        const p=new URLSearchParams({page:st.page,limit:st.limit,search:st.search,status:st.status});
        if (cat) p.append('category',cat);
        const data=await thApi(`${API_BASE}api/masterdata/components/list.php?${p}`);
        document.getElementById('tLoading').classList.add('hidden');
        if (!data.success||!data.data?.length){document.getElementById('tEmpty').classList.remove('hidden');return;}
        document.getElementById('tBody').innerHTML=data.data.map(c=>`
            <tr class="hover:bg-gray-50 transition">
                <td class="px-4 py-3"><div class="font-semibold text-gray-800">${c.name}</div><div class="text-xs text-gray-400">${c.description||c.sys_id}</div></td>
                <td class="px-4 py-3"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">${c.category}</span></td>
                <td class="px-4 py-3"><div class="flex items-center justify-center gap-1">
                    <button onclick="MdComponents._edit('${c.sys_id}')" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-blue-50 text-blue-500"><i class="fa-solid fa-pen text-xs"></i></button>
                    <button onclick="MdComponents._del('${c.sys_id}')" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-red-50 text-red-400"><i class="fa-solid fa-trash text-xs"></i></button>
                </div></td>
            </tr>`).join('');
        thPagination('pgBox',data.pagination,'MdComponents._page');
    }
    function _page(p){st.page=p;load();}

    async function openForm(sys_id=null){
        ['fSysId','fName','fDesc'].forEach(id=>thSetVal(id,'')); thSetVal('fCat','misc');
        document.getElementById('modalTitle').textContent=sys_id?'Edit Component':'Add Component';
        if (sys_id){
            thSetVal('fSysId',sys_id);
            const d=await thApi(`${API_BASE}api/masterdata/components/get.php?sys_id=${sys_id}`);
            const c=d.data; thSetVal('fName',c.name); thSetVal('fCat',c.category); thSetVal('fDesc',c.description);
        }
        thOpenModal('modal');
    }
    async function _edit(sys_id){await openForm(sys_id);}
    async function save(){
        const body={sys_id:thVal('fSysId')||undefined,name:thVal('fName'),category:thVal('fCat'),description:thVal('fDesc')};
        if (!body.name) return thToast('Name required','error');
        document.getElementById('btnSave').disabled=true;
        const res=await thApi(`${API_BASE}api/masterdata/components/save.php`,'POST',body);
        document.getElementById('btnSave').disabled=false;
        if (!res.success) return thToast(res.message||'Error','error');
        thToast(res.message||'Saved!'); thCloseModal('modal'); load();
    }
    async function _del(sys_id){
        if (!thConfirm('Delete this component?')) return;
        const res=await thApi(`${API_BASE}api/masterdata/components/delete.php`,'POST',{sys_id});
        thToast(res.message||'Deleted'); load();
    }
    return {init,_page,_edit,_del};
})();
