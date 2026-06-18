/**
 * TravHub — PkgBuilder v2  (assets/js/pkg-builder.js)
 * Depends on: th-utils.js  |  Globals: API_BASE, PKG_SYS_ID
 */
const PkgBuilder = (() => {
    'use strict';

    function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
    function fmt(n,d=2){ return Number(n||0).toLocaleString(undefined,{minimumFractionDigits:d,maximumFractionDigits:d}); }
    function setBusy(id,busy){ const b=document.getElementById(id); if(!b) return; b.disabled=busy; if(busy){b.dataset.orig=b.innerHTML; b.innerHTML='<i class="fa-solid fa-spinner fa-spin mr-2"></i>Please wait…';} else b.innerHTML=b.dataset.orig||''; }

    const STEPS  = ['Basic Info','Destinations','Itinerary','Inclusions','Pricing','AI & Cover','Review'];
    const PTYPES = ['group','fit','corporate','factory_tour','custom','fixed','umrah'];
    const MPLANS = {room_only:'Room Only',bb:'Bed & Breakfast',hb:'Half Board',fb:'Full Board',ai:'All Inclusive'};
    const TTYPES = ['airport_transfer','intercity','ferry','shuttle','car_hire','other'];
    const VCLS   = ['sedan','suv','van','minibus','coach','boat','train','other'];
    const PBASES = ['per_pax','per_group','per_vehicle','per_day','per_person'];

    let step=1, cc=[], iMode='manual', hd={}, id_draft={}, id_di=null, id_ii=null;
    let pendingSuggs={};   // keyed by day index → suggs array; set by _genExNBuild, consumed by renderDays
    let pkg={
        sys_id:'',title:'',package_type:'custom',description:'',
        start_date:'',end_date:'',duration:'',
        adults:1,children:0,infants:0,sell_currency_code:'BDT',
        client_sys_id:'',client_name:'',
        countries:[],hotels:[],pack_itenaries:[],
        pack_inclusions:[],pack_exclusions:[],pack_components:[],
        pricing_config:{activity_profit:15,hotel_profit:12,transport_profit:10,component_profit:8,exchange_rate:1,exchange_country_sys_id:''},
        pack_price:[],highlights:[],
        full_description:'',cover_image:'',notes:'',rating:0,
        overall_price:0,progress_step:1,completion_status:'draft',genImgUrl:''
    };

    async function init(sys_id=''){
        css(); shell();
        const d=await thApi(`${API_BASE}api/masterdata/countries/list.php?for_package=1&limit=300&status=active`);
        cc=d.data||[];
        if(sys_id){
            const r=await thApi(`${API_BASE}api/packages/get.php?sys_id=${sys_id}`);
            if(r.success&&r.data){
                Object.keys(pkg).forEach(k=>{ if(r.data[k]!==undefined) pkg[k]=r.data[k]; });
                pkg.sys_id=r.data.sys_id;
                ['countries','hotels','pack_itenaries','pack_inclusions','pack_exclusions','pack_price','highlights','pack_components'].forEach(k=>{ if(!Array.isArray(pkg[k])) pkg[k]=[]; });
                if(!pkg.pricing_config||typeof pkg.pricing_config!=='object') pkg.pricing_config={activity_profit:15,hotel_profit:12,transport_profit:10,component_profit:8,exchange_rate:1,exchange_country_sys_id:''};
                step=r.data.progress_step||1;
                pkg.cover_image = `${API_BASE}uploads/packages/${pkg.sys_id}/cover_img.jpg`;
            }
        }
        go(step);
    }

    function shell(){
        document.getElementById('mainContent').innerHTML=`
        <div class="px-4 py-6 max-w-5xl mx-auto">
            <div class="flex items-center gap-3 mb-6">
                <button onclick="window.history.back()" class="p-2 rounded-xl border border-gray-200 hover:bg-gray-100 text-gray-500 transition"><i class="fa-solid fa-arrow-left text-sm"></i></button>
                <div class="flex-1 min-w-0"><h1 class="text-xl font-bold text-[#1A2039] truncate" id="bT">New Package</h1><p class="text-xs text-gray-400 mt-0.5" id="bS">Step 1 of 7</p></div>
                <span class="text-xs font-mono text-gray-300" id="bID"></span>
            </div>
            <div class="flex items-center gap-1 mb-7 overflow-x-auto pb-1" id="sNav"></div>
            <div id="sC" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-5"></div>
            <div class="flex items-center justify-between gap-3">
                <button id="bPrev" class="hidden px-5 py-2.5 rounded-xl text-sm font-medium border border-gray-200 text-gray-600 hover:bg-gray-50 transition"><i class="fa-solid fa-arrow-left mr-2"></i>Previous</button>
                <div class="flex gap-3 ml-auto">
                    <button id="bDft" class="px-5 py-2.5 rounded-xl text-sm font-medium border border-gray-200 text-gray-600 hover:bg-gray-50 transition"><i class="fa-regular fa-floppy-disk mr-2"></i>Save Draft</button>
                    <button id="bNxt" class="px-6 py-2.5 rounded-xl text-sm font-semibold bg-[#1A2039] hover:bg-[#252d4a] text-white transition shadow-sm">Next <i class="fa-solid fa-arrow-right ml-2"></i></button>
                </div>
            </div>
        </div>
        <div id="cMod" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
                <h3 class="text-base font-bold text-[#1A2039] mb-4">Add Destination</h3>
                <div class="mb-3"><label class="pb-lbl">Country *</label>
                    <select id="cSel" class="pb-inp"><option value="">Select country…</option></select>
                </div>
                <div class="mb-4"><label class="pb-lbl">Select Cities</label>
                    <div id="cCities" class="flex flex-wrap gap-2 mt-1 min-h-10 p-2 border border-gray-200 rounded-xl">
                        <span class="text-xs text-gray-300 italic">Select a country first…</span>
                    </div>
                </div>
                <div class="flex justify-end gap-3">
                    <button onclick="thCloseModal('cMod')" class="px-4 py-2 rounded-xl text-sm border border-gray-200 text-gray-600 hover:bg-gray-50">Cancel</button>
                    <button id="bCO" class="px-5 py-2 rounded-xl text-sm font-semibold bg-[#1A2039] text-white hover:bg-[#252d4a]">Add</button>
                </div>
            </div>
        </div>
        <div id="hMod" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg flex flex-col max-h-[90vh]">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
                    <h3 class="font-bold text-[#1A2039]" id="hMT">Add Hotel</h3>
                    <button onclick="thCloseModal('hMod')" class="text-gray-400 hover:text-gray-600 w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100"><i class="fa-solid fa-times"></i></button>
                </div>
                <div class="flex-1 overflow-y-auto px-6 py-5" id="hMB"></div>
                <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-100 flex-shrink-0">
                    <button onclick="thCloseModal('hMod')" class="px-4 py-2 rounded-xl text-sm border border-gray-200 text-gray-600 hover:bg-gray-50">Cancel</button>
                    <button id="bHO" class="px-5 py-2 rounded-xl text-sm font-semibold bg-[#1A2039] text-white hover:bg-[#252d4a]">Next →</button>
                </div>
            </div>
        </div>
        <div id="iMod" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl flex flex-col max-h-[90vh]">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
                    <h3 class="font-bold text-[#1A2039]" id="iMT">Add Item</h3>
                    <button onclick="thCloseModal('iMod')" class="text-gray-400 hover:text-gray-600 w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100"><i class="fa-solid fa-times"></i></button>
                </div>
                <div class="flex-1 overflow-y-auto px-6 py-5" id="iMB"></div>
                <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-100 flex-shrink-0">
                    <button onclick="thCloseModal('iMod')" class="px-4 py-2 rounded-xl text-sm border border-gray-200 text-gray-600 hover:bg-gray-50">Cancel</button>
                    <button id="bIO" class="hidden px-5 py-2 rounded-xl text-sm font-semibold bg-[#1A2039] text-white hover:bg-[#252d4a]">Save Item</button>
                </div>
            </div>
        </div>
        `;
    }

    function nav(){
        document.getElementById('sNav').innerHTML=STEPS.map((l,i)=>{
            const n=i+1,done=n<step,act=n===step,ok=pkg.sys_id||n<=step;
            return `<button onclick="PkgBuilder._go(${n})" ${!ok?'disabled':''}
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium transition flex-shrink-0
                    ${act?'bg-[#1A2039] text-white':done?'bg-[#50BC81]/15 text-[#2e9460] hover:bg-[#50BC81]/25':'bg-gray-100 text-gray-400 '+(ok?'hover:bg-gray-200':'cursor-not-allowed')}">
                <span class="w-4 h-4 rounded-full flex items-center justify-center text-[10px] font-bold flex-shrink-0
                    ${act?'bg-white text-[#1A2039]':done?'bg-[#50BC81] text-white':'bg-gray-300 text-gray-500'}">
                    ${done?'<i class="fa-solid fa-check" style="font-size:8px"></i>':n}</span>
                <span class="hidden sm:inline">${l}</span></button>
                ${n<7?'<i class="fa-solid fa-chevron-right text-gray-200 text-[10px] flex-shrink-0"></i>':''}`;
        }).join('');
        const bT=document.getElementById('bT'); if(bT) bT.textContent=pkg.title||'New Package';
        const bS=document.getElementById('bS'); if(bS) bS.textContent=`Step ${step} of 7 — ${STEPS[step-1]}`;
        const bID=document.getElementById('bID'); if(bID) bID.textContent=pkg.sys_id||'';
        const bp=document.getElementById('bPrev'),bn=document.getElementById('bNxt'),bd=document.getElementById('bDft');
        if(bp){bp.classList.toggle('hidden',step===1);bp.onclick=()=>go(step-1);}
        if(bn){bn.innerHTML=step===7?'<i class="fa-solid fa-flag-checkered mr-2"></i>Finalize':'Next <i class="fa-solid fa-arrow-right ml-2"></i>';bn.onclick=next;}
        if(bd) bd.onclick=draft;
    }

    function go(n){
        step=Math.max(1,Math.min(7,n)); nav();
        const el=document.getElementById('sC'); el.innerHTML='';
        [null,s1,s2,s3,s4,s5,s6,s7][step]?.(el);
    }


    // ═══ STEP 1 ═══
    function s1(el){
        el.innerHTML=`
        <h2 class="text-lg font-bold text-[#1A2039] mb-5">Basic Information</h2>
        <div class="space-y-4">
            <div><label class="pb-lbl">Package Title *</label><input id="t1" class="pb-inp" placeholder="e.g. Thailand 5D4N Group Tour" value="${esc(pkg.title||'')}"></div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="pb-lbl">Package Type *</label><select id="t2" class="pb-inp">${PTYPES.map(t=>`<option value="${t}" ${pkg.package_type===t?'selected':''}>${t.replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase())}</option>`).join('')}</select></div>
                <div><label class="pb-lbl">Sell Currency</label><input id="t3" class="pb-inp uppercase" maxlength="5" placeholder="BDT" value="${esc(pkg.sell_currency_code||'BDT')}"></div>
            </div>
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="pb-lbl" style="margin-bottom:0">Short Description</label>
                    <button type="button" id="sttToggle" onclick="PkgBuilder._sttToggle()"
                        class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold border border-gray-200 text-gray-500 hover:border-[#50BC81] hover:text-[#50BC81] transition">
                        <i class="fa-solid fa-microphone text-xs"></i> Dictate
                    </button>
                </div>
            
                <!-- ── Speech Widget (hidden until toggled) ── -->
                <div id="sttPanel" class="hidden mb-3 border border-gray-200 rounded-xl overflow-hidden bg-gray-50">
            
                    <!-- Header row: status + language -->
                    <div class="flex items-center gap-2 px-3 py-2 border-b border-gray-200 bg-white">
                        <span class="w-2 h-2 rounded-full bg-gray-300 flex-shrink-0" id="sttDot"></span>
                        <span class="text-xs text-gray-400 flex-1" id="sttStatus">Ready</span>
                        <select id="sttLang" class="text-xs border border-gray-200 rounded-lg px-2 py-1 bg-white text-gray-600 outline-none">
                            <option value="bn-BD">বাংলা (BD)</option>
                            <option value="en-US">English (US)</option>
                            <option value="en-GB">English (UK)</option>
                            <option value="bn-IN">বাংলা (IN)</option>
                            <option value="hi-IN">हिन्दी</option>
                        </select>
                    </div>
            
                    <!-- Live transcript preview -->
                    <div id="sttPreview" class="px-3 py-2 text-xs text-gray-500 min-h-10 leading-relaxed"
                        style="max-height:80px;overflow-y:auto">
                        Speak to see text here…
                    </div>
            
                    <!-- Control buttons -->
                    <div class="flex items-center gap-2 px-3 py-2 border-t border-gray-200 bg-white flex-wrap">
                        <button type="button" id="sttStart" onclick="PkgBuilder._sttStart()"
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-[#22c55e] text-white hover:bg-[#16a34a] transition">
                            <span class="w-2 h-2 rounded-full bg-white inline-block"></span> Start
                        </button>
                        <button type="button" id="sttPause" onclick="PkgBuilder._sttPause()" disabled
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-[#f59e0b] text-white hover:bg-[#d97706] transition disabled:opacity-30 disabled:cursor-not-allowed">
                            ⏸ Pause
                        </button>
                        <button type="button" id="sttStop" onclick="PkgBuilder._sttStop()" disabled
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-[#ef4444] text-white hover:bg-[#dc2626] transition disabled:opacity-30 disabled:cursor-not-allowed">
                            ⏹ Stop
                        </button>
            
                        <!-- Push buttons — hidden until stopped -->
                        <div id="sttActions" class="hidden flex items-center gap-2 ml-auto">
                            <button type="button" onclick="PkgBuilder._sttPush(false)"
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold border border-gray-300 text-gray-600 hover:bg-gray-100 transition">
                                Push →
                            </button>
                            <button type="button" id="sttPolish" onclick="PkgBuilder._sttPush(true)"
                                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-gradient-to-r from-purple-500 to-indigo-500 text-white hover:from-purple-600 hover:to-indigo-600 transition">
                                <i class="fa-solid fa-wand-magic-sparkles text-xs"></i> Polish & Push
                            </button>
                        </div>
                    </div>
                </div>
                <!-- ── End Speech Widget ── -->
                
                <textarea id="t4" class="pb-inp resize-none" rows="9"
                placeholder="Brief summary…">${esc(pkg.full_description||'')}</textarea>

                <button type="button" id="exNBuild" onclick="PkgBuilder._genExNBuild()"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold border border-gray-300 text-gray-600 hover:bg-gray-100 transition">
                    Extract & Build
                </button>
            </div>
            <div class="border-t border-gray-100 pt-4">
                <p class="pb-lbl mb-3">Travel Duration <span class="text-gray-400 font-normal text-xs">(fill any 2 — third auto-calculates)</span></p>
                <div class="grid grid-cols-3 gap-4">
                    <div><label class="text-xs text-gray-400 mb-1 block">Start Date</label><input id="t5" type="date" class="pb-inp" value="${pkg.start_date||''}"></div>
                    <div><label class="text-xs text-gray-400 mb-1 block">End Date</label><input id="t6" type="date" class="pb-inp" value="${pkg.end_date||''}"></div>
                    <div><label class="text-xs text-gray-400 mb-1 block">Nights</label><input id="t7" type="number" min="1" class="pb-inp" placeholder="Auto" value="${pkg.duration||''}"></div>
                </div>
            </div>
        </div>`;
        ['t5','t6','t7'].forEach(id=>document.getElementById(id).addEventListener('change',dateCalc));
    }
    function dateCalc(){
        const sd=thVal('t5'),ed=thVal('t6'),dur=parseInt(thVal('t7'))||0;
        if(sd&&ed&&!thVal('t7')){const n=Math.round((new Date(ed)-new Date(sd))/86400000);if(n>0)thSetVal('t7',n);}
        else if(sd&&dur&&!thVal('t6')){const d=new Date(sd);d.setDate(d.getDate()+dur);thSetVal('t6',d.toISOString().slice(0,10));}
        else if(ed&&dur&&!thVal('t5')){const d=new Date(ed);d.setDate(d.getDate()-dur);thSetVal('t5',d.toISOString().slice(0,10));}
    }
    function c1(){
        pkg.title=thVal('t1');pkg.package_type=thVal('t2');
        pkg.full_description=thVal('t4');pkg.description=pkg.full_description;
        pkg.sell_currency_code=thVal('t3').toUpperCase()||'BDT';
        pkg.start_date=thVal('t5');pkg.end_date=thVal('t6');pkg.duration=parseInt(thVal('t7'))||null;
        if(!pkg.title){thToast('Package title required','error');return false;}return true;
    }

    // ═══ STEP 2 ═══
    function s2(el){
        if(!Array.isArray(pkg.countries))pkg.countries=[];
        if(!Array.isArray(pkg.hotels))pkg.hotels=[];
        el.innerHTML=`
        <h2 class="text-lg font-bold text-[#1A2039] mb-5">Destinations & Accommodation</h2>
        <div class="mb-6">
            <p class="pb-lbl mb-3">Passengers</p>
            <div class="grid grid-cols-3 gap-4">
                <div><label class="text-xs text-gray-400 mb-1 block">Adults *</label><input id="pA" type="number" min="1" class="pb-inp" value="${pkg.adults||1}"></div>
                <div><label class="text-xs text-gray-400 mb-1 block">Children</label><input id="pC" type="number" min="0" class="pb-inp" value="${pkg.children||0}"></div>
                <div><label class="text-xs text-gray-400 mb-1 block">Infants</label><input id="pI" type="number" min="0" class="pb-inp" value="${pkg.infants||0}"></div>
            </div>
        </div>
        <div class="mb-6">
            <div class="flex items-center justify-between mb-3">
                <p class="pb-lbl">Destinations</p>
                <button id="bAC" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-[#50BC81] hover:bg-[#3da868] text-white transition"><i class="fa-solid fa-plus"></i> Add Country</button>
            </div>
            <div id="cList" class="space-y-2"></div>
        </div>
        <div>
            <div class="flex items-center justify-between mb-3">
                <p class="pb-lbl">Accommodation</p>
                <button id="bAH" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-[#1A2039] hover:bg-[#252d4a] text-white transition" ${!pkg.countries.length?'disabled':''}>
                    <i class="fa-solid fa-plus"></i> Add Hotel
                </button>
            </div>
            <div id="hList" class="space-y-2"></div>
        </div>`;
        rCntList();rHtlList();bindS2();
    }

    function bindS2(){
        document.getElementById('bAC').onclick=()=>{
            const cSel=document.getElementById('cSel');
            if(cSel) cSel.innerHTML='<option value="">Select country…</option>'+cc.map(c=>`<option value="${c.sys_id}" data-n="${esc(c.name)}" data-cy="${c.currency_code}" data-r="${c.default_rate}" data-cs='${esc(JSON.stringify(c.cities||[]))}'>${esc(c.name)}</option>`).join('');
            const cCit=document.getElementById('cCities');
            if(cCit)cCit.innerHTML='<span class="text-xs text-gray-300 italic">Select a country first…</span>';
            thOpenModal('cMod');
        };
        document.getElementById('cSel').onchange=function(){
            const opt=this.options[this.selectedIndex];
            let cities=[];try{cities=JSON.parse(opt.dataset.cs||'[]');}catch(e){}
            document.getElementById('cCities').innerHTML=cities.length
                ?cities.map(c=>`<label class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs border border-gray-200 cursor-pointer hover:bg-gray-50 select-none"><input type="checkbox" class="city-chk accent-[#50BC81]" value="${c.sys_id}" data-n="${esc(c.name)}">${esc(c.name)}</label>`).join('')
                :'<span class="text-xs text-gray-300 italic">No cities</span>';
        };
        document.getElementById('bCO').onclick=()=>{
            const sel=document.getElementById('cSel');
            if(!sel.value){thToast('Select a country','error');return;}
            if(pkg.countries.find(c=>c.sys_id===sel.value)){thToast('Already added','warning');thCloseModal('cMod');return;}
            const opt=sel.options[sel.selectedIndex];
            const cities=[...document.querySelectorAll('.city-chk:checked')].map(c=>({sys_id:c.value,name:c.dataset.n}));
            pkg.countries.push({sys_id:sel.value,name:opt.dataset.n,currency_code:opt.dataset.cy,default_rate:parseFloat(opt.dataset.r)||1,cities});
            thCloseModal('cMod');rCntList();
            const b=document.getElementById('bAH');if(b)b.disabled=false;
        };
        document.getElementById('bAH').onclick=()=>openHtl(null);
    }

    function rCntList(){
        const el=document.getElementById('cList');if(!el)return;
        el.innerHTML=pkg.countries.length
            ?pkg.countries.map((c,i)=>`
                <div class="flex items-center gap-3 px-4 py-3 bg-gray-50 rounded-xl border border-gray-200">
                    <i class="fa-solid fa-globe text-[#50BC81] text-sm"></i>
                    <div class="flex-1"><div class="text-sm font-semibold text-[#1A2039]">${esc(c.name)}</div>
                        ${c.cities?.length?`<div class="flex flex-wrap gap-1 mt-1">${c.cities.map(ct=>`<span class="px-2 py-0.5 rounded-full text-xs bg-white border border-gray-200 text-gray-600">${esc(ct.name)}</span>`).join('')}</div>`:''}
                    </div>
                    <button onclick="PkgBuilder._rmC(${i})" class="p-1.5 rounded-lg hover:bg-red-50 text-red-300 hover:text-red-500 transition"><i class="fa-solid fa-times text-xs"></i></button>
                </div>`).join('')
            :'<p class="text-xs text-gray-300 italic px-1">No destinations added yet</p>';
    }

    async function openHtl(idx){
        hd=idx!==null&&pkg.hotels[idx]
            ?{...pkg.hotels[idx],_idx:idx,_step:'city'}
            :{_idx:null,_step:'city',city_sys_id:'',city_name:'',hotel_sys_id:'',hotel_name:'',room_type_sys_id:'',room_type_name:'',rate_sys_id:'',meal_plan:'bb',net_per_night:0,net_cost:0,currency_code:'',nights:1,rooms:1};
        document.getElementById('hMT').textContent=idx!==null?'Edit Hotel':'Add Hotel';
        htlStep('city');thOpenModal('hMod');
    }

    function htlStep(s){
        hd._step=s;
        const body=document.getElementById('hMB'),btn=document.getElementById('bHO');if(!body||!btn)return;
        const cities=pkg.countries.flatMap(c=>(c.cities||[]).map(ct=>({...ct,country_name:c.name,ccy:c.currency_code})));

        if(s==='city'){
            body.innerHTML=`<p class="pb-lbl mb-3">Select City</p>
                <div class="space-y-2">${cities.map(c=>`
                <button onclick="PkgBuilder._hCity('${c.sys_id}','${esc(c.name)}','${esc(c.ccy||'')}')"
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-xl border transition text-left ${hd.city_sys_id===c.sys_id?'border-[#50BC81] bg-[#50BC81]/5':'border-gray-200 hover:bg-gray-50'}">
                    <i class="fa-solid fa-city text-[#1A2039] text-sm"></i>
                    <div><div class="text-sm font-medium text-[#1A2039]">${esc(c.name)}</div><div class="text-xs text-gray-400">${esc(c.country_name||'')}</div></div>
                    ${hd.city_sys_id===c.sys_id?'<i class="fa-solid fa-check ml-auto text-[#50BC81]"></i>':''}
                </button>`).join('')||'<p class="text-xs text-gray-300 italic">No cities selected in destinations</p>'}
                </div>`;
            btn.textContent='Next → Hotels';btn.onclick=()=>{if(!hd.city_sys_id){thToast('Select a city','error');return;}htlStep('hotel');};
        }
        else if(s==='hotel'){
            body.innerHTML=`
                <div class="flex items-center gap-2 mb-3"><button onclick="PkgBuilder._hBack('city')" class="text-gray-400 hover:text-gray-600"><i class="fa-solid fa-arrow-left"></i></button>
                    <p class="pb-lbl">Hotels in <strong>${esc(hd.city_name)}</strong></p></div>
                <input id="hSrch" class="pb-inp text-xs mb-3" placeholder="Search hotel name…">
                <div id="hPick" class="space-y-1.5 max-h-64 overflow-y-auto"><div class="text-center py-4 text-gray-300"><i class="fa-solid fa-spinner fa-spin"></i></div></div>`;
            btn.textContent='Next → Rooms';btn.onclick=()=>{if(!hd.hotel_sys_id){thToast('Select a hotel','error');return;}htlStep('room');};
            document.getElementById('hSrch').oninput=e=>loadHotels(e.target.value);
            loadHotels('');
        }
        else if(s==='room'){
            body.innerHTML=`
                <div class="flex items-center gap-2 mb-3"><button onclick="PkgBuilder._hBack('hotel')" class="text-gray-400 hover:text-gray-600"><i class="fa-solid fa-arrow-left"></i></button>
                    <p class="pb-lbl">Room Types — <strong>${esc(hd.hotel_name)}</strong></p></div>
                <div id="rPick" class="space-y-1.5 max-h-44 overflow-y-auto mb-4"><div class="text-center py-4 text-gray-300"><i class="fa-solid fa-spinner fa-spin"></i></div></div>
                <div id="rateSec" class="hidden mb-4">
                    <p class="pb-lbl mb-2">Rates — <span id="rNm" class="font-semibold text-[#1A2039]"></span></p>
                    <div id="ratePick" class="space-y-1.5 max-h-32 overflow-y-auto"></div>
                </div>
                <div class="grid grid-cols-2 gap-3 mt-2">
                    <div><label class="pb-lbl">Nights *</label><input id="hN" type="number" min="1" class="pb-inp" value="${hd.nights||1}"></div>
                    <div><label class="pb-lbl">Rooms</label><input id="hR" type="number" min="1" class="pb-inp" value="${hd.rooms||1}"></div>
                </div>`;
            btn.textContent='Save Hotel';btn.onclick=saveHotel;
            loadRooms();
        }
    }

    async function loadHotels(q){
        const el=document.getElementById('hPick');if(!el)return;
        el.innerHTML='<div class="text-center py-4 text-gray-300"><i class="fa-solid fa-spinner fa-spin"></i></div>';
        const d=await thApi(`${API_BASE}api/masterdata/hotels/list.php?city_sys_id=${hd.city_sys_id}&search=${encodeURIComponent(q)}&limit=20&status=active`);
        el.innerHTML=(d.data||[]).map(h=>`
            <button onclick="PkgBuilder._hHotel('${h.sys_id}','${esc(h.name)}')"
                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl border transition text-left ${hd.hotel_sys_id===h.sys_id?'border-[#50BC81] bg-[#50BC81]/5':'border-gray-200 hover:bg-gray-50'}">
                ${h.thumb?`<img src="${esc(h.thumb)}" class="w-10 h-10 rounded-lg object-cover flex-shrink-0" onerror="this.remove()">`:
                '<div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center flex-shrink-0"><i class="fa-solid fa-hotel text-gray-300 text-sm"></i></div>'}
                <div class="flex-1 min-w-0"><div class="text-sm font-medium text-[#1A2039] truncate">${esc(h.name)}</div>
                    <div class="text-xs text-gray-400">${h.star_rating?'★'.repeat(h.star_rating):''} ${esc(h.city_name||'')}</div></div>
                ${hd.hotel_sys_id===h.sys_id?'<i class="fa-solid fa-check text-[#50BC81]"></i>':''}
            </button>`).join('')||'<p class="text-xs text-gray-300 italic text-center py-4">No hotels found</p>';
    }

    async function loadRooms(){
        const el=document.getElementById('rPick');if(!el)return;
        const d=await thApi(`${API_BASE}api/masterdata/hotels/room-type-list.php?hotel_sys_id=${hd.hotel_sys_id}&status=active&limit=20`);
        el.innerHTML=(d.data||[]).map(r=>`
            <button onclick="PkgBuilder._hRoom('${r.sys_id}','${esc(r.room_name)}')"
                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl border transition text-left ${hd.room_type_sys_id===r.sys_id?'border-[#50BC81] bg-[#50BC81]/5':'border-gray-200 hover:bg-gray-50'}">
                <i class="fa-solid fa-bed text-[#1A2039] text-sm"></i>
                <div class="flex-1"><div class="text-sm font-medium">${esc(r.room_name)}</div><div class="text-xs text-gray-400">Max ${r.max_adults||'?'} adults</div></div>
                ${hd.room_type_sys_id===r.sys_id?'<i class="fa-solid fa-check text-[#50BC81]"></i>':''}
            </button>`).join('')||'<p class="text-xs text-gray-300 italic text-center py-4">No room types found</p>';
    }

    async function loadRates(rtId){
        const sec=document.getElementById('rateSec');if(sec)sec.classList.remove('hidden');
        const el=document.getElementById('ratePick');if(!el)return;
        const rn=document.getElementById('rNm');if(rn)rn.textContent=hd.room_type_name||'';
        el.innerHTML='<div class="text-center py-2 text-gray-300"><i class="fa-solid fa-spinner fa-spin text-sm"></i></div>';
        const d=await thApi(`${API_BASE}api/masterdata/hotels/room-rate-list.php?room_type_sys_id=${rtId}&status=active&limit=10`);
        const rates=d.data||[];
        if(rates.length){
            el.innerHTML=rates.map(r=>`
                <button onclick="PkgBuilder._hRate('${r.sys_id}','${esc(r.meal_plan||'bb')}','${r.net_cost}','${esc(r.currency_code||'')}')"
                    class="w-full flex items-center gap-3 px-3 py-2 rounded-xl border transition text-left ${hd.rate_sys_id===r.sys_id?'border-[#50BC81] bg-[#50BC81]/5':'border-gray-200 hover:bg-gray-50'}">
                    <div class="flex-1"><div class="text-xs font-medium">${esc(MPLANS[r.meal_plan]||r.meal_plan||'')}</div>
                        <div class="text-[10px] text-gray-400">${esc(r.occupancy_basis||'')}</div></div>
                    <div class="text-sm font-bold text-[#1A2039]">${esc(r.currency_code||'')} ${fmt(r.net_cost)}<span class="text-xs font-normal text-gray-400">/night</span></div>
                    ${hd.rate_sys_id===r.sys_id?'<i class="fa-solid fa-check text-[#50BC81]"></i>':''}
                </button>`).join('');
        } else {
            el.innerHTML=`<p class="text-xs text-gray-300 italic text-center py-2">No rates — enter manually:</p>
            <div class="grid grid-cols-2 gap-2 mt-2">
                <div><label class="pb-lbl">Meal Plan</label><select id="hMP" class="pb-inp text-xs">${Object.entries(MPLANS).map(([v,l])=>`<option value="${v}" ${hd.meal_plan===v?'selected':''}>${l}</option>`).join('')}</select></div>
                <div><label class="pb-lbl">Net/Night</label><div class="flex gap-1">
                    <input id="hCC" class="pb-inp text-xs w-16 uppercase" maxlength="5" placeholder="THB" value="${esc(hd.currency_code||'')}">
                    <input id="hNC" type="number" step="0.01" class="pb-inp text-xs flex-1" placeholder="0.00" value="${hd.net_per_night||''}">
                </div></div>
            </div>`;
        }
    }

    function saveHotel(){
        if(!hd.hotel_sys_id){thToast('Select a hotel','error');return;}
        if(!hd.room_type_sys_id){thToast('Select a room type','error');return;}
        if(!hd.rate_sys_id){
            hd.meal_plan=thVal('hMP')||'bb';
            hd.currency_code=thVal('hCC').toUpperCase()||pkg.sell_currency_code;
            hd.net_per_night=parseFloat(thVal('hNC'))||0;
        }
        // Always ensure net_per_night is set
        if(!hd.net_per_night && hd.net_cost) hd.net_per_night=hd.net_cost;
        const hotel={
            hotel_sys_id:hd.hotel_sys_id, hotel_name:hd.hotel_name,
            city_sys_id:hd.city_sys_id,   city_name:hd.city_name,
            room_type_sys_id:hd.room_type_sys_id, room_type_name:hd.room_type_name,
            rate_sys_id:hd.rate_sys_id||'', meal_plan:hd.meal_plan||'bb',
            net_per_night:hd.net_per_night||0,
            net_cost:hd.net_per_night||0,        // ← FIX: net_cost = net_per_night
            currency_code:hd.currency_code||pkg.sell_currency_code,
            nights:parseInt(thVal('hN'))||1, rooms:parseInt(thVal('hR'))||1
        };
        if(hd._idx!==null) pkg.hotels[hd._idx]=hotel;
        else pkg.hotels.push(hotel);

        // ── এই block টা যোগ করো ──
        const prof = pkg.pricing_config.hotel_profit || 12;
        const netPN = hotel.net_per_night || 0;
        const sellPN = netPN > 0 ? parseFloat((netPN / (1 - prof/100)).toFixed(2)) : 0;
        const priceKey = `hotel:${hotel.hotel_name}:${hotel.room_type_name}`;
        const existIdx = pkg.pack_price.findIndex(p => p._key === priceKey);
        const priceRow = {
            _key: priceKey, type: 'hotel',
            name: hotel.hotel_name,
            variant_name: `${hotel.room_type_name||''} · ${MPLANS[hotel.meal_plan]||hotel.meal_plan}`,
            meal_plan: hotel.meal_plan,
            currency_code: hotel.currency_code,
            net_cost: netPN,
            net_per_night: netPN,
            sell_price: sellPN,
            nights: hotel.nights || 1,
            rooms: hotel.rooms || 1,
            profit_pct: prof,
            qty: 1
        };
        if (existIdx >= 0) pkg.pack_price[existIdx] = priceRow;
        else pkg.pack_price.push(priceRow);
        // ─────────────────────────────


        thCloseModal('hMod');rHtlList();
    }

    function rHtlList(){
        const el=document.getElementById('hList');if(!el)return;
        el.innerHTML=pkg.hotels.length
            ?pkg.hotels.map((h,i)=>`
                <div class="flex items-center gap-3 px-4 py-3 bg-gray-50 rounded-xl border border-gray-200">
                    <i class="fa-solid fa-hotel text-[#1A2039] text-sm"></i>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-semibold text-[#1A2039] truncate">${esc(h.hotel_name)}</div>
                        <div class="text-xs text-gray-400 mt-0.5">${esc(h.city_name)} · ${esc(h.room_type_name||'')} · ${esc(MPLANS[h.meal_plan]||h.meal_plan)} · ${h.nights}N · ${h.rooms} room(s)${h.net_per_night?` · ${esc(h.currency_code)} ${fmt(h.net_per_night)}/night`:''}</div>
                    </div>
                    <div class="flex gap-1">
                        <button onclick="PkgBuilder._eH(${i})" class="p-1.5 rounded-lg hover:bg-blue-50 text-blue-400 transition"><i class="fa-solid fa-pen text-xs"></i></button>
                        <button onclick="PkgBuilder._rmH(${i})" class="p-1.5 rounded-lg hover:bg-red-50 text-red-300 hover:text-red-500 transition"><i class="fa-solid fa-times text-xs"></i></button>
                    </div>
                </div>`).join('')
            :'<p class="text-xs text-gray-300 italic px-1">No hotels added yet</p>';
    }

    function c2(){
        pkg.adults=Math.max(1,parseInt(thVal('pA'))||1);
        pkg.children=Math.max(0,parseInt(thVal('pC'))||0);
        pkg.infants=Math.max(0,parseInt(thVal('pI'))||0);
        if(!pkg.countries.length){thToast('Add at least one destination','error');return false;}return true;
    }


    // ═══ STEP 3 ═══
    function s3(el){
        if(!Array.isArray(pkg.pack_itenaries))pkg.pack_itenaries=[];
        const n=parseInt(pkg.duration)||pkg.pack_itenaries.length||1;
        while(pkg.pack_itenaries.length<n)newDay(pkg.pack_itenaries.length+1);
        el.innerHTML=`
        <div class="flex items-center justify-between mb-5">
            <h2 class="text-lg font-bold text-[#1A2039]">Itinerary</h2>
            <div class="flex gap-2">
                <button onclick="PkgBuilder._iM('manual')" class="px-4 py-2 rounded-xl text-xs font-semibold border transition ${iMode==='manual'?'bg-[#1A2039] text-white border-[#1A2039]':'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'}"><i class="fa-solid fa-pen mr-1.5"></i>Manual</button>
                <button onclick="PkgBuilder._iM('ai')" class="px-4 py-2 rounded-xl text-xs font-semibold border transition ${iMode==='ai'?'bg-[#50BC81] text-white border-[#50BC81]':'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'}"><i class="fa-solid fa-wand-magic-sparkles mr-1.5"></i>AI Mode</button>
            </div>
        </div>
        <div id="aiP" class="${iMode==='manual'?'hidden':''}">
            <div class="bg-gradient-to-r from-purple-50 to-blue-50 border border-purple-200 rounded-xl p-4 mb-4">
                <p class="text-sm font-semibold text-purple-800 mb-1"><i class="fa-solid fa-wand-magic-sparkles mr-2"></i>AI Full Itinerary Builder</p>
                <p class="text-xs text-purple-600">Describe your package — AI searches your database and builds the complete day-wise itinerary.</p>
            </div>
            <label class="pb-lbl mb-2 block">Describe the Package</label>
            <textarea id="aiPr" class="pb-inp font-mono text-xs resize-none mb-3" rows="5" placeholder="e.g. 5 days Thailand family tour…"></textarea>
            <button id="bBuild" class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold bg-[#50BC81] hover:bg-[#3da868] text-white transition">
                <i class="fa-solid fa-wand-magic-sparkles"></i>Build Full Itinerary
            </button>
            <div id="aiFull" class="hidden mt-5">
                <div class="bg-blue-50 border border-blue-200 rounded-xl px-4 py-3 mb-4 text-sm text-blue-700">
                    <i class="fa-solid fa-circle-info mr-2"></i>Review below. <span class="bg-purple-100 text-purple-700 px-1.5 py-0.5 rounded text-xs font-medium">⚡ AI</span> = estimated.
                </div>
                <div id="aiFCards" class="space-y-3 mb-4"></div>
                <button id="bAccept" class="px-5 py-2.5 rounded-xl text-sm font-semibold bg-[#1A2039] text-white hover:bg-[#252d4a] transition"><i class="fa-solid fa-check mr-2"></i>Accept & Edit Manually</button>
            </div>
        </div>
        <div id="mnP" class="${iMode==='ai'?'hidden':''}">
            <div id="dCards" class="space-y-3 mb-3"></div>
            <button id="bAD" class="w-full py-3 rounded-xl text-sm border-2 border-dashed border-gray-200 text-gray-400 hover:border-[#50BC81] hover:text-[#50BC81] transition"><i class="fa-solid fa-plus mr-2"></i>Add Day</button>
        </div>`;
        renderDays();
        document.getElementById('bAD').onclick=()=>{newDay(pkg.pack_itenaries.length+1);renderDays();};
        document.getElementById('bBuild').onclick=buildItinerary;
    }

    function newDay(n){pkg.pack_itenaries.push({day_number:n,title:`Day ${n}`,city_name:'',hotel_ref:null,meal_breakfast:false,meal_lunch:false,meal_dinner:false,raw_text:'',items:[]});}

    // ── Builds the indigo suggestion panel for Extract-&-Build results ──
    function buildExtractPanel(di, suggs){
        return `<div id="suggPanel${di}" class="bg-indigo-50 border border-indigo-200 rounded-xl p-3 mb-2">
            <p class="text-xs font-semibold text-indigo-700 mb-2"><i class="fa-solid fa-wand-magic-sparkles mr-1"></i>Extract & Build — Day ${di+1} suggestions:</p>
            <div class="space-y-1.5" id="suggList${di}">
                ${suggs.map((s,si)=>`
                <div class="flex items-center gap-2 px-3 py-2 bg-white rounded-lg border border-indigo-100 text-xs">
                    <i class="fa-solid ${s.type==='activity'?'fa-person-hiking text-green-500':'fa-van-shuttle text-blue-500'} flex-shrink-0"></i>
                    <div class="flex-1 min-w-0">
                        <div class="font-medium text-[#1A2039] truncate">${esc(s.name)}</div>
                        <div class="text-[10px] text-gray-400">${esc(s.reason||'')} ⚡ AI</div>
                    </div>
                    ${s.net_cost?`<span class="text-gray-400 font-mono flex-shrink-0">${esc(s.currency_code||'')} ${fmt(s.net_cost)}</span>`:''}
                    <button onclick="PkgBuilder._accS(${di},${si})"
                        class="px-2.5 py-1 rounded-lg bg-[#50BC81] text-white text-xs font-semibold hover:bg-[#3da868] transition flex-shrink-0">+ Add</button>
                </div>`).join('')}
            </div>
        </div>`;
    }

    function renderDays(){
        const el=document.getElementById('dCards');if(!el)return;
        el.innerHTML=pkg.pack_itenaries.map((day,di)=>`
            <div class="border border-gray-200 rounded-xl overflow-hidden">
                <div class="flex items-center gap-3 px-4 py-3 bg-gray-50 cursor-pointer select-none" onclick="PkgBuilder._tog(${di})">
                    <span class="w-7 h-7 rounded-full bg-[#1A2039] text-white text-xs font-bold flex items-center justify-center flex-shrink-0">${day.day_number}</span>
                    <input class="flex-1 text-sm font-semibold text-[#1A2039] bg-transparent border-none outline-none cursor-text" value="${esc(day.title||'Day '+day.day_number)}" onclick="event.stopPropagation()" oninput="PkgBuilder._df(${di},'title',this.value)">
                    ${day.items?.length?`<span class="px-2 py-0.5 bg-[#50BC81]/15 text-[#2e9460] rounded-full text-xs">${day.items.length} item(s)</span>`:''}
                    <button onclick="event.stopPropagation();PkgBuilder._rmD(${di})" class="p-1 hover:text-red-500 text-red-300 transition flex-shrink-0"><i class="fa-solid fa-times text-xs"></i></button>
                    <i class="fa-solid fa-chevron-down text-gray-300 text-xs flex-shrink-0" id="chev${di}"></i>
                </div>
                <div id="dB${di}" class="px-4 py-4 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="text-xs text-gray-400 mb-1 block">Hotel Tonight</label>
                            <select class="pb-inp text-xs" onchange="PkgBuilder._df(${di},'hotel_ref',this.value?JSON.parse(this.value):null)">
                                <option value="">None</option>
                                ${pkg.hotels.map(h=>`<option value='${JSON.stringify({name:h.hotel_name,meal_plan:h.meal_plan}).replace(/'/g,"&#39;")}' ${day.hotel_ref?.name===h.hotel_name?'selected':''}>${esc(h.hotel_name)}</option>`).join('')}
                            </select>
                        </div>
                        <div><label class="text-xs text-gray-400 mb-1 block">Meals</label>
                            <div class="flex gap-2 mt-1.5">
                                ${[['B','meal_breakfast'],['L','meal_lunch'],['D','meal_dinner']].map(([l,k])=>`
                                <label class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg border text-xs cursor-pointer hover:bg-gray-50 select-none ${day[k]?'border-[#50BC81] bg-[#50BC81]/10 text-[#2e9460]':'border-gray-200'}">
                                    <input type="checkbox" class="hidden" ${day[k]?'checked':''} onchange="PkgBuilder._df(${di},'${k}',this.checked);this.closest('label').className='flex items-center gap-1 px-2.5 py-1.5 rounded-lg border text-xs cursor-pointer hover:bg-gray-50 select-none '+(this.checked?'border-[#50BC81] bg-[#50BC81]/10 text-[#2e9460]':'border-gray-200')">${l}
                                </label>`).join('')}
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs text-gray-400 mb-1 block">Day Description / Raw Text</label>
                        <textarea class="pb-inp text-xs resize-none" rows="3" id="raw${di}"
                            placeholder="Paste day plan here… AI will extract activities and transport from this text."
                            onchange="PkgBuilder._df(${di},'raw_text',this.value)">${esc(day.raw_text||'')}</textarea>
                        <div class="flex gap-2 mt-2">
                            <button onclick="PkgBuilder._ext(${di})" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-50 text-blue-700 hover:bg-blue-100 transition">
                                <i class="fa-solid fa-wand-magic-sparkles"></i>Extract by AI
                            </button>
                            <button onclick="PkgBuilder._sug(${di})" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-purple-50 text-purple-700 hover:bg-purple-100 transition">
                                <i class="fa-solid fa-lightbulb"></i>AI Suggest
                            </button>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Activities & Transport</span>
                            <button onclick="PkgBuilder._openI(${di},null)" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-[#1A2039] hover:bg-[#252d4a] text-white transition"><i class="fa-solid fa-plus"></i> Add</button>
                        </div>
                        <div id="iL${di}" class="space-y-1.5">${rItems(di)}</div>
                    </div>
                </div>
            </div>`).join('');
        // ── Inject any pending Extract-&-Build suggestion panels ──────
        console.log('[renderDays] pendingSuggs keys:', Object.keys(pendingSuggs));
        Object.keys(pendingSuggs).forEach(diStr => {
            const di    = parseInt(diStr);
            const suggs = pendingSuggs[di];
            if (!suggs || !suggs.length) return;
            const lel = document.getElementById(`iL${di}`);
            console.log(`[renderDays] iL${di}:`, lel ? 'FOUND' : 'NOT FOUND', '| suggs:', suggs.length);
            if (!lel) return;
            lel._suggs = [...suggs];
            lel.insertAdjacentHTML('afterbegin', buildExtractPanel(di, suggs));
            console.log(`[renderDays] panel injected for day ${di}`);
        });
    }

    function rItems(di){
        const items=pkg.pack_itenaries[di]?.items||[];
        if(!items.length)return'<p class="text-xs text-gray-300 italic">No items yet</p>';
        return items.map((item,ii)=>{
            const ai=item.source==='ai',act=item.type==='activity',flt=item.type==='flight';
            const durMins=getDurationMins(item);

            if(flt){
                const segs=item.segments||[];
                const firstSeg=segs[0]||{};
                const lastSeg=segs[segs.length-1]||{};
                const totalPay=(item.fares||[]).reduce((s,f)=>s+(parseFloat(f.payable||0)*parseInt(f.pax||1)),0);
                return`<div class="flex items-start gap-2 px-3 py-2 rounded-lg border bg-sky-50 border-sky-200">
                    <i class="fa-solid fa-plane text-sky-600 text-xs mt-1.5 flex-shrink-0"></i>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="text-xs font-semibold text-[#1A2039]">${esc(item.name||item.airline||'Flight')}</span>
                            ${item.overnight?'<span class="px-1.5 py-0.5 rounded text-[10px] bg-indigo-100 text-indigo-700">🌙 Overnight</span>':''}
                        </div>
                        <div class="text-[10px] text-gray-400 mt-0.5">
                            ${segs.map(s=>`${esc(s.flight||'')} ${esc(s.route||'')} ${esc(s.dep||'')}→${esc(s.arr||'')}${s.arr_offset>0?'+'+s.arr_offset:''}`).join(' · ')}
                        </div>
                        ${(item.fares||[]).map(f=>`<span class="text-[10px] text-gray-500">${esc(f.type)} ×${f.pax} ${esc(item.currency_code||'')} ${fmt(f.payable)}/pax</span>`).join(' · ')}
                        <div class="flex items-center gap-1.5 mt-1">
                            <input type="time" value="${esc(item.start_time||'')}"
                                class="text-[10px] border border-gray-200 rounded-lg px-1.5 py-0.5 bg-white text-gray-600 outline-none w-20"
                                onchange="PkgBuilder._setTime(${di},${ii},'start',this.value)" title="Departure time">
                            ${item.end_time&&!item.overnight?`<span class="text-[10px] text-gray-400">→ ${esc(item.end_time)}</span>`:''}
                            ${item.overnight?`<span class="text-[10px] text-indigo-500">→ Day +${item.arrive_day_offset||1}</span>`:''}
                        </div>
                    </div>
                    <div class="flex flex-col gap-0.5 flex-shrink-0">
                        <button onclick="PkgBuilder._swapI(${di},${ii},-1)" ${ii===0?'disabled':''} class="p-1 rounded hover:bg-gray-200 text-gray-400 disabled:opacity-20 transition leading-none"><i class="fa-solid fa-chevron-up text-[10px]"></i></button>
                        <button onclick="PkgBuilder._swapI(${di},${ii},1)" ${ii===items.length-1?'disabled':''} class="p-1 rounded hover:bg-gray-200 text-gray-400 disabled:opacity-20 transition leading-none"><i class="fa-solid fa-chevron-down text-[10px]"></i></button>
                    </div>
                    <div class="flex gap-1 flex-shrink-0">
                        <button onclick="PkgBuilder._openI(${di},${ii})" class="p-1 hover:text-sky-500 text-gray-300 transition"><i class="fa-solid fa-pen text-xs"></i></button>
                        <button onclick="PkgBuilder._rmI(${di},${ii})" class="p-1 hover:text-red-500 text-red-300 transition"><i class="fa-solid fa-times text-xs"></i></button>
                    </div>
                </div>`;
            }

            return`<div class="flex items-start gap-2 px-3 py-2 rounded-lg border ${act?'bg-green-50 border-green-100':'bg-blue-50 border-blue-100'}">
                <i class="fa-solid ${act?'fa-person-hiking text-green-600':'fa-van-shuttle text-blue-500'} text-xs mt-1.5 flex-shrink-0"></i>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="text-xs font-semibold text-[#1A2039] truncate">${esc(item.name)}</span>
                        ${ai?'<span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-purple-100 text-purple-700 flex-shrink-0">⚡ AI</span>':''}
                    </div>
                    ${act&&item.category?`<div class="text-[10px] text-gray-400">${esc(item.category)}${item.duration_hours?' · '+item.duration_hours+'h':''}</div>`:''}
                    ${!act&&(item.from_city||item.to_city)?`<div class="text-[10px] text-gray-400">${esc(item.from_city||'')}${item.from_city&&item.to_city?' → ':''}${esc(item.to_city||'')}${item.vehicle_class?' · '+item.vehicle_class:''}</div>`:''}
                    ${item.net_cost?`<div class="text-[10px] text-gray-500 font-mono">${esc(item.currency_code||'')} ${fmt(item.net_cost)} net</div>`:''}
                    <div class="flex items-center gap-1.5 mt-1">
                        <input type="time" value="${esc(item.start_time||'')}"
                            class="text-[10px] border border-gray-200 rounded-lg px-1.5 py-0.5 bg-white text-gray-600 outline-none w-20"
                            placeholder="Start"
                            onchange="PkgBuilder._setTime(${di},${ii},'start',this.value)"
                            title="Start time">
                        ${item.end_time?`<span class="text-[10px] text-gray-400">→ ${esc(item.end_time)}</span>`:''}
                        ${durMins?`<span class="text-[10px] text-gray-300">(${durMins>=60?Math.floor(durMins/60)+'h'+(durMins%60?durMins%60+'m':''):''})</span>`:''}
                    </div>
                </div>
                <div class="flex flex-col gap-0.5 flex-shrink-0">
                    <button onclick="PkgBuilder._swapI(${di},${ii},-1)" ${ii===0?'disabled':''} class="p-1 rounded hover:bg-gray-200 text-gray-400 disabled:opacity-20 transition leading-none"><i class="fa-solid fa-chevron-up text-[10px]"></i></button>
                    <button onclick="PkgBuilder._swapI(${di},${ii},1)" ${ii===items.length-1?'disabled':''} class="p-1 rounded hover:bg-gray-200 text-gray-400 disabled:opacity-20 transition leading-none"><i class="fa-solid fa-chevron-down text-[10px]"></i></button>
                </div>
                <div class="flex gap-1 flex-shrink-0">
                    <button onclick="PkgBuilder._openI(${di},${ii})" class="p-1 hover:text-blue-500 text-gray-300 transition"><i class="fa-solid fa-pen text-xs"></i></button>
                    <button onclick="PkgBuilder._rmI(${di},${ii})" class="p-1 hover:text-red-500 text-red-300 transition"><i class="fa-solid fa-times text-xs"></i></button>
                </div>
            </div>`;
        }).join('');
    }

    // ── Time Cascade ──────────────────────────────────────────────────
    // Adds minutes to a "HH:MM" string, returns "HH:MM"
    function addMinutes(timeStr, mins){
        if(!timeStr||!mins) return timeStr||'';
        const [h,m]=timeStr.split(':').map(Number);
        const total=h*60+m+Math.round(mins);
        return `${String(Math.floor(total/60)%24).padStart(2,'0')}:${String(total%60).padStart(2,'0')}`;
    }
    function getDurationMins(item){
        if(item.type==='flight') return 0; // flight duration handled by segments
        if(item.type==='activity') return (parseFloat(item.duration_hours)||0)*60;
        const raw=String(item.duration_typical||'');
        const n=parseFloat(raw);
        return isNaN(n)?0:n<24?n*60:n;
    }
    function cascadeTimes(di, startIdx=0){
        const items=pkg.pack_itenaries[di]?.items;
        if(!items||!items.length) return;
        for(let i=startIdx; i<items.length; i++){
            const item=items[i];
            if(item.type==='flight'){
                // For flights: end_time is arrival, but if overnight skip end_time on this day
                if(item.start_time && !item.overnight){
                    // Use last segment arr as end_time
                    const lastArr=(item.segments||[]).slice(-1)[0]?.arr||'';
                    if(lastArr) item.end_time=lastArr;
                }
                // Set next day's cascade start if overnight
                if(item.overnight && item.arrive_day_offset>0){
                    const nextDi=di+item.arrive_day_offset;
                    const nextDay=pkg.pack_itenaries[nextDi];
                    if(nextDay){
                        const arrTime=(item.segments||[]).slice(-1)[0]?.arr||'';
                        if(arrTime && nextDay.items.length>0 && !nextDay.items[0].start_time){
                            nextDay.items[0].start_time=arrTime;
                            cascadeTimes(nextDi,0);
                        }
                    }
                }
            } else {
                const durMins=getDurationMins(item);
                if(item.start_time && durMins>0) item.end_time=addMinutes(item.start_time,durMins);
            }
            // Set next item's start from this end_time
            if(i+1<items.length && item.end_time && !item.overnight){
                if(!items[i+1].start_time) items[i+1].start_time=item.end_time;
            }
        }
    }

    // ── AI Suggest — FIX: suggestion panel stays after adding item ────
    async function suggestDay(di){
        const day=pkg.pack_itenaries[di];
        if(!pkg.countries.length){thToast('Add destinations in Step 2 first','warning');return;}
        const btn=document.querySelector(`button[onclick="PkgBuilder._sug(${di})"]`);
        if(btn){btn.disabled=true;btn.innerHTML='<i class="fa-solid fa-spinner fa-spin mr-1"></i>Suggesting…';}
        const res=await thApi(`${API_BASE}api/ai/day-suggest.php`,'POST',{
            package_title:pkg.title||'Travel Package',day_title:day.title,day_number:day.day_number,
            country_sys_ids:pkg.countries.map(c=>c.sys_id),country_names:pkg.countries.map(c=>c.name),
            existing_item_names:day.items.map(i=>i.name),
        });
        if(btn){btn.disabled=false;btn.innerHTML='<i class="fa-solid fa-lightbulb mr-1"></i>AI Suggest';}
        if(!res.success){thToast(res.message||'Failed','error');return;}
        const suggs=res.suggestions||[];if(!suggs.length){thToast('No suggestions found','warning');return;}

        const lel=document.getElementById(`iL${di}`);
        // Render items first (base layer)
        lel.innerHTML=rItems(di);
        lel._suggs=[...suggs];

        // Build suggestion panel HTML
        function buildSuggPanel(currentSuggs){
            return `<div id="suggPanel${di}" class="bg-purple-50 border border-purple-200 rounded-xl p-3 mb-2">
                <p class="text-xs font-semibold text-purple-700 mb-2"><i class="fa-solid fa-lightbulb mr-1"></i>AI Suggestions — click + to add:</p>
                <div class="space-y-1.5" id="suggList${di}">
                    ${currentSuggs.map((s,si)=>`
                    <div class="flex items-center gap-2 px-3 py-2 bg-white rounded-lg border border-purple-100 text-xs" id="suggItem${di}_${si}">
                        <i class="fa-solid ${s.type==='activity'?'fa-person-hiking text-green-500':'fa-van-shuttle text-blue-500'} flex-shrink-0"></i>
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-[#1A2039] truncate">${esc(s.name)}</div>
                            <div class="text-[10px] text-gray-400">${esc(s.reason||'')} ${s.source==='ai'?'⚡ AI estimated':''}</div>
                        </div>
                        ${s.net_cost?`<span class="text-gray-400 font-mono flex-shrink-0">${esc(s.currency_code||'')} ${fmt(s.net_cost)}</span>`:''}
                        <button onclick="PkgBuilder._accS(${di},${si})"
                            class="px-2.5 py-1 rounded-lg bg-[#50BC81] text-white text-xs font-semibold hover:bg-[#3da868] transition flex-shrink-0">+ Add</button>
                    </div>`).join('')}
                </div>
            </div>`;
        }

        // Insert suggestion panel BEFORE items
        lel.insertAdjacentHTML('afterbegin', buildSuggPanel(suggs));
        thToast(`${suggs.length} suggestion(s) ready`);
    }

    // ── acceptSugg: removes clicked item, panel stays, saves AI item to DB ──
    function acceptSugg(di,si){
        const lel=document.getElementById(`iL${di}`);
        if(!lel?._suggs) return;
        const s=lel._suggs[si];
        if(!s) return;

        // Add to itinerary items
        pkg.pack_itenaries[di].items.push({...s});

        // ── Auto-save AI activity/transport to masterdata (fire-and-forget) ────
        if(s.source==='ai' && !s.source_sys_id && pkg.countries.length){
            const countrySysId = pkg.countries[0].sys_id;
            if(s.type==='activity'){
                thApi(`${API_BASE}api/ai/activity-quick-save.php`,'POST',{
                    country_sys_id: countrySysId,
                    name:           s.name,
                    category:       s.category||'',
                    duration_hours: s.duration_hours||0,
                    currency_code:  s.currency_code||'BDT',
                    net_cost:       s.net_cost||0,
                    sell_price:     s.sell_price||0,
                    price_basis:    s.price_basis||'per_pax',
                }).then(r=>{
                    if(r.success && !r.already_exists){
                        const items=pkg.pack_itenaries[di].items;
                        const added=items.find(it=>it.name===s.name&&it.source==='ai'&&!it.source_sys_id);
                        if(added){ added.source='masterdata'; added.source_sys_id=r.sys_id; added.variant_sys_id=r.variant_sys_id||''; }
                    }
                }).catch(()=>{});
            } else if(s.type==='transport'){
                thApi(`${API_BASE}api/ai/transport-quick-save.php`,'POST',{
                    country_sys_id:  countrySysId,
                    name:            s.name,
                    transport_type:  s.transport_type||'intercity',
                    from_city:       s.from_city||'',
                    to_city:         s.to_city||'',
                    direction:       s.direction||'one_way',
                    vehicle_class:   s.vehicle_class||'van',
                    duration_typical: s.duration_typical||'',
                    currency_code:   s.currency_code||'BDT',
                    net_cost:        s.net_cost||0,
                    sell_price:      s.sell_price||0,
                    price_basis:     s.price_basis||'per_vehicle',
                }).then(r=>{
                    if(r.success && !r.already_exists){
                        const items=pkg.pack_itenaries[di].items;
                        const added=items.find(it=>it.name===s.name&&it.source==='ai'&&!it.source_sys_id);
                        if(added){ added.source='masterdata'; added.source_sys_id=r.sys_id; added.variant_sys_id=r.variant_sys_id||''; }
                    }
                }).catch(()=>{});
            }
        }

        // Remove from suggs array
        lel._suggs.splice(si,1);

        if(lel._suggs.length){
            // Re-render only the suggestion list (panel stays)
            const suggList=document.getElementById(`suggList${di}`);
            if(suggList){
                suggList.innerHTML=lel._suggs.map((sg,newSi)=>`
                <div class="flex items-center gap-2 px-3 py-2 bg-white rounded-lg border border-purple-100 text-xs" id="suggItem${di}_${newSi}">
                    <i class="fa-solid ${sg.type==='activity'?'fa-person-hiking text-green-500':'fa-van-shuttle text-blue-500'} flex-shrink-0"></i>
                    <div class="flex-1 min-w-0">
                        <div class="font-medium text-[#1A2039] truncate">${esc(sg.name)}</div>
                        <div class="text-[10px] text-gray-400">${esc(sg.reason||'')} ${sg.source==='ai'?'⚡ AI estimated':''}</div>
                    </div>
                    ${sg.net_cost?`<span class="text-gray-400 font-mono flex-shrink-0">${esc(sg.currency_code||'')} ${fmt(sg.net_cost)}</span>`:''}
                    <button onclick="PkgBuilder._accS(${di},${newSi})"
                        class="px-2.5 py-1 rounded-lg bg-[#50BC81] text-white text-xs font-semibold hover:bg-[#3da868] transition flex-shrink-0">+ Add</button>
                </div>`).join('');
            }
        } else {
            // Last item added — remove panel entirely and clear pending store
            document.getElementById(`suggPanel${di}`)?.remove();
            delete pendingSuggs[di];
        }

        // Re-render items list (preserve _suggs ref)
        const savedSuggs=lel._suggs;
        // Update only items portion — find items container after panel
        // Re-render full lel but re-insert panel
        const panel=document.getElementById(`suggPanel${di}`);
        lel.innerHTML=rItems(di);
        lel._suggs=savedSuggs;
        if(panel && savedSuggs.length) lel.insertAdjacentElement('afterbegin',panel);

        thToast(`${s.name} added`);
    }

    // ── Extract by AI ─────────────────────────────────────────────────
    async function extractDay(di){
        const day=pkg.pack_itenaries[di];
        const raw=day.raw_text||document.getElementById(`raw${di}`)?.value||'';
        if(!raw){thToast('Enter text in the day description first','warning');return;}
        const btn=document.querySelector(`button[onclick="PkgBuilder._ext(${di})"]`);
        if(btn){btn.disabled=true;btn.innerHTML='<i class="fa-solid fa-spinner fa-spin mr-1"></i>Extracting…';}
        const res=await thApi(`${API_BASE}api/ai/day-extract.php`,'POST',{
            raw_text:raw,day_title:day.title,day_number:day.day_number,
            country_sys_ids:pkg.countries.map(c=>c.sys_id),country_names:pkg.countries.map(c=>c.name),
        });
        if(btn){btn.disabled=false;btn.innerHTML='<i class="fa-solid fa-wand-magic-sparkles mr-1"></i>Extract by AI';}
        if(!res.success){thToast(res.message||'Extraction failed','error');return;}
        const data=res.data;
        const existing=new Set(pkg.pack_itenaries[di].items.map(i=>i.name));
        const newItems=(data.items||[]).filter(i=>!existing.has(i.name));
        pkg.pack_itenaries[di].items.push(...newItems);
        if(data.meal_breakfast)pkg.pack_itenaries[di].meal_breakfast=true;
        if(data.meal_lunch)pkg.pack_itenaries[di].meal_lunch=true;
        if(data.meal_dinner)pkg.pack_itenaries[di].meal_dinner=true;
        const lel=document.getElementById(`iL${di}`);
        if(lel){
            const savedSuggs=lel._suggs;
            const panel=document.getElementById(`suggPanel${di}`);
            lel.innerHTML=rItems(di);
            lel._suggs=savedSuggs;
            if(panel && savedSuggs?.length) lel.insertAdjacentElement('afterbegin',panel);
        }
        thToast(`${newItems.length} item(s) extracted`);
    }


    // ── Item Modal ────────────────────────────────────────────────────
    function openItem(di,ii){
        id_di=di;id_ii=ii;
        const ex=ii!==null?pkg.pack_itenaries[di].items[ii]:null;
        // Flight type — use flight modal
        if(ex?.type==='flight'){
            fd={...flightDefault(),...ex};
            const titleEl=document.getElementById('iMT');
            if(titleEl)titleEl.textContent='Edit Flight';
            const bIO=document.getElementById('bIO');
            if(bIO){bIO.classList.remove('hidden');bIO.onclick=saveFlightItem;}
            rFlightModal();thOpenModal('iMod');return;
        }
        const def={type:'activity',source:'manual',source_sys_id:'',variant_sys_id:'',_variants:[],
            name:'',category:'',duration_hours:0,
            transport_type:'airport_transfer',from_city:'',to_city:'',direction:'one_way',
            vehicle_class:'van',duration_typical:'',
            start_time:'',end_time:'',
            currency_code:pkg.sell_currency_code||'BDT',
            net_cost:0,sell_price:0,child_price:null,price_basis:'per_pax',description_points:[]};
        id_draft=ex?Object.assign({},def,ex):{...def};
        if(!['activity','transport','flight'].includes(id_draft.type))id_draft.type='activity';
        const bIO=document.getElementById('bIO');
        const titleEl=document.getElementById('iMT');
        if(ex){
            if(titleEl)titleEl.textContent='Edit Item';
            if(bIO){bIO.classList.remove('hidden');bIO.onclick=saveItem;}
            rItemModal();thOpenModal('iMod');
        } else {
            if(titleEl)titleEl.textContent='Add Item';
            if(bIO)bIO.classList.add('hidden');
            rTypePicker(di);thOpenModal('iMod');
        }
    }

    function rTypePicker(di){
        const el=document.getElementById('iMB');if(!el)return;
        el.innerHTML=`
        <p class="text-xs text-gray-400 mb-4 text-center">What do you want to add?</p>
        <div class="space-y-2">
            <button onclick="PkgBuilder._pickT(${di},'activity')"
                class="w-full flex items-center gap-3 px-4 py-3.5 rounded-xl border-2 border-gray-200 hover:border-[#50BC81] hover:bg-[#50BC81]/5 transition text-left">
                <div class="w-10 h-10 rounded-xl bg-green-100 flex items-center justify-center flex-shrink-0"><i class="fa-solid fa-person-hiking text-green-600 text-lg"></i></div>
                <div><div class="text-sm font-semibold text-[#1A2039]">Activity</div><div class="text-xs text-gray-400">Tours, sightseeing, experiences</div></div>
                <i class="fa-solid fa-chevron-right text-gray-300 ml-auto text-xs"></i>
            </button>
            <button onclick="PkgBuilder._pickT(${di},'transport')"
                class="w-full flex items-center gap-3 px-4 py-3.5 rounded-xl border-2 border-gray-200 hover:border-blue-400 hover:bg-blue-50 transition text-left">
                <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center flex-shrink-0"><i class="fa-solid fa-van-shuttle text-blue-600 text-lg"></i></div>
                <div><div class="text-sm font-semibold text-[#1A2039]">Transport</div><div class="text-xs text-gray-400">Transfers, intercity, ferry</div></div>
                <i class="fa-solid fa-chevron-right text-gray-300 ml-auto text-xs"></i>
            </button>
            <button onclick="PkgBuilder._pickT(${di},'flight')"
                class="w-full flex items-center gap-3 px-4 py-3.5 rounded-xl border-2 border-gray-200 hover:border-sky-400 hover:bg-sky-50 transition text-left">
                <div class="w-10 h-10 rounded-xl bg-sky-100 flex items-center justify-center flex-shrink-0"><i class="fa-solid fa-plane text-sky-600 text-lg"></i></div>
                <div><div class="text-sm font-semibold text-[#1A2039]">Flight</div><div class="text-xs text-gray-400">International & domestic flights</div></div>
                <i class="fa-solid fa-chevron-right text-gray-300 ml-auto text-xs"></i>
            </button>
            <button onclick="PkgBuilder._pickT(${di},'custom')"
                class="w-full flex items-center gap-3 px-4 py-3.5 rounded-xl border-2 border-gray-200 hover:border-gray-400 hover:bg-gray-50 transition text-left">
                <div class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center flex-shrink-0"><i class="fa-solid fa-pen text-gray-500 text-lg"></i></div>
                <div><div class="text-sm font-semibold text-[#1A2039]">Custom</div><div class="text-xs text-gray-400">Free text entry, manual pricing</div></div>
                <i class="fa-solid fa-chevron-right text-gray-300 ml-auto text-xs"></i>
            </button>
        </div>`;
    }

    async function pickType(di,type){
        id_draft.type=type==='custom'?'activity':type;
        const bIO=document.getElementById('bIO');
        const titleEl=document.getElementById('iMT');
        if(bIO){bIO.classList.remove('hidden');bIO.onclick=saveItem;}
        if(type==='custom'){if(titleEl)titleEl.textContent='Custom Item';rItemModal();return;}
        if(type==='flight'){
            if(titleEl)titleEl.textContent='Add Flight';
            id_draft.type='flight';
            fd=flightDefault();
            rFlightModal();return;
        }
        const el=document.getElementById('iMB');if(!el)return;
        el.innerHTML='<div class="text-center py-8 text-gray-300"><i class="fa-solid fa-spinner fa-spin text-2xl mb-2 block"></i><p class="text-xs">Loading...</p></div>';
        if(titleEl)titleEl.textContent=type==='activity'?'Select Activity':'Select Transport';
        const csid=pkg.countries[0]?.sys_id||'';
        const currentDay=pkg.pack_itenaries[di]||{};
        const prevDay=di>0?pkg.pack_itenaries[di-1]:{};
        const cityName=currentDay.city_name||'';
        const prevCity=prevDay.city_name||'';
        let items=[],variantMap={};
        if(type==='activity'){
            const d=await thApi(`${API_BASE}api/masterdata/activities/list.php?country_sys_id=${csid}&limit=60&status=active`);
            items=d.data||[];
        } else {
            const d=await thApi(`${API_BASE}api/masterdata/transport/service-list.php?country_sys_id=${csid}&limit=60&status=active`);
            items=d.data||[];
        }
        if(items.length){
            const ids=items.map(i=>i.sys_id).join(',');
            const vd=type==='activity'
                ?await thApi(`${API_BASE}api/masterdata/activities/variant-list.php?activity_sys_ids=${ids}&status=active&cheapest=1`)
                :await thApi(`${API_BASE}api/masterdata/transport/variant-list.php?service_sys_ids=${ids}&status=active&cheapest=1`);
            (vd.data||[]).forEach(v=>{
                const k=type==='activity'?v.activity_sys_id:v.service_sys_id;
                if(!variantMap[k])variantMap[k]=v;
            });
        }
        el.innerHTML=`
        <div class="mb-3"><input id="catSrch" class="pb-inp text-xs" placeholder="Filter by name or city..." oninput="PkgBuilder._filterCat(this.value)"></div>
        <div id="catList" class="space-y-1.5 max-h-[55vh] overflow-y-auto"></div>`;
        el._items=items;el._variants=variantMap;el._type=type;
        el._cityName=cityName;el._prevCity=prevCity;
        renderCatList(items,variantMap,type,cityName,prevCity,'');
    }

    function renderCatList(items,variantMap,type,cityName,prevCity,filter){
        const el=document.getElementById('catList');if(!el)return;
        const isAct=type==='activity';
        let list=items;
        if(filter){
            const q=filter.toLowerCase();
            list=items.filter(i=>(i.name||'').toLowerCase().includes(q)||(i.city_name||i.from_city_name||'').toLowerCase().includes(q)||(i.category||'').toLowerCase().includes(q));
        }
        if(!list.length){el.innerHTML='<p class="text-xs text-gray-300 italic text-center py-4">No results</p>';return;}
        el.innerHTML=list.map(item=>{
            const v=variantMap[item.sys_id];
            const cityMatch=isAct&&cityName&&(item.city_name||'').toLowerCase().includes(cityName.toLowerCase());
            const routeMatch=!isAct&&prevCity&&(item.from_city_name||'').toLowerCase().includes(prevCity.toLowerCase());
            const match=cityMatch||routeMatch;
            return`<button onclick="PkgBuilder._selCat('${item.sys_id}')"
                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl border transition text-left hover:bg-gray-50 ${match?'border-[#50BC81]/50 bg-[#50BC81]/5':'border-gray-200'}">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs font-semibold text-[#1A2039] truncate">${esc(item.name)}</span>
                        ${match?'<span class="px-1.5 py-0.5 rounded text-[10px] bg-[#50BC81]/20 text-[#2e9460] flex-shrink-0 font-medium">★ Match</span>':''}
                    </div>
                    <div class="text-[10px] text-gray-400 mt-0.5">
                        ${isAct?`${esc(item.city_name||'')}${item.category?' · '+esc(item.category):''}${item.duration_hours?' · ⏱'+item.duration_hours+'h':''}`:`${esc(item.from_city_name||'')}${item.to_city_name?' → '+esc(item.to_city_name):''}${item.type?' · '+item.type:''}`}
                    </div>
                </div>
                ${v?`<div class="text-right flex-shrink-0"><div class="text-xs font-bold text-[#1A2039]">${esc(v.currency_code||'')} ${fmt(parseFloat(v.net_cost)||0)}</div><div class="text-[10px] text-gray-400">net</div></div>`:''}
            </button>`;
        }).join('');
        el._items=list;
    }

    function filterCat(q){
        const mEl=document.getElementById('iMB');if(!mEl)return;
        renderCatList(mEl._items||[],mEl._variants||{},mEl._type||'activity',mEl._cityName||'',mEl._prevCity||'',q);
    }

    async function selCatItem(sid){
        const mEl=document.getElementById('iMB');if(!mEl)return;
        const type=mEl._type||'activity';
        const items=mEl._items||[],variantMap=mEl._variants||{};
        const hit=items.find(i=>i.sys_id===sid);if(!hit)return;
        id_draft.source='masterdata';id_draft.source_sys_id=sid;
        if(type==='activity'){
            id_draft.type='activity';id_draft.name=hit.name;
            id_draft.category=hit.category||'';id_draft.duration_hours=parseFloat(hit.duration_hours)||0;
            const vd=await thApi(`${API_BASE}api/masterdata/activities/variant-list.php?activity_sys_id=${sid}&status=active&limit=10`);
            id_draft._variants=vd.data||[];
            const v=variantMap[sid]||id_draft._variants[0];
            if(v){id_draft.variant_sys_id=v.sys_id;id_draft.currency_code=v.currency_code;id_draft.net_cost=parseFloat(v.net_cost)||0;id_draft.sell_price=parseFloat(v.sell_price)||0;id_draft.child_price=v.child_price?parseFloat(v.child_price):null;id_draft.price_basis=v.price_basis||'per_pax';}
            try{const pts=JSON.parse(hit.short_description||'[]');if(Array.isArray(pts))id_draft.description_points=pts;}catch(e){}
        } else {
            id_draft.type='transport';id_draft.name=hit.name;id_draft.transport_type=hit.type;
            id_draft.from_city=hit.from_city_name||'';id_draft.to_city=hit.to_city_name||'';
            id_draft.direction=hit.direction||'one_way';id_draft.duration_typical=hit.duration_typical||'';
            const vd=await thApi(`${API_BASE}api/masterdata/transport/variant-list.php?service_sys_id=${sid}&status=active&limit=10`);
            id_draft._variants=vd.data||[];
            const v=variantMap[sid]||id_draft._variants[0];
            if(v){id_draft.variant_sys_id=v.sys_id;id_draft.vehicle_class=v.vehicle_class;id_draft.currency_code=v.currency_code;id_draft.net_cost=parseFloat(v.net_cost)||0;id_draft.sell_price=parseFloat(v.sell_price)||0;id_draft.price_basis=v.price_basis||'per_vehicle';}
        }
        const titleEl=document.getElementById('iMT');
        if(titleEl)titleEl.textContent=type==='activity'?'Activity Detail':'Transport Detail';
        rItemModal();
    }

    // ── Flight Modal ──────────────────────────────────────────────────
    // Flight item data model:
    // { type:'flight', airline:'', segments:[{flight,class,date,route,dep,arr,arr_offset}],
    //   fares:[{type:'ADT',pax:1,base_fare:0,taxes:0,gross_fare:0,iata_charge:0,net_fare:0,payable:0}],
    //   from_city:'', to_city:'', overnight:false, arrive_day_offset:0,
    //   raw_gds:'', source:'manual', name:'', start_time:'', end_time:'',
    //   currency_code:'BDT', total_net:0, total_sell:0 }

    let fd = {}; // flight draft — parallel to id_draft

    function flightDefault(){
        return {
            type:'flight', source:'manual', name:'',
            airline:'', raw_gds:'',
            segments:[{flight:'',class:'',date:'',route:'',dep:'',arr:'',arr_offset:0}],
            fares:[{type:'ADT',pax:1,base_fare:0,taxes:0,gross_fare:0,iata_charge:0,net_fare:0,payable:0,payable_edited:false}],
            from_city:'', to_city:'', overnight:false, arrive_day_offset:0,
            start_time:'', end_time:'',
            currency_code:pkg.sell_currency_code||'BDT',
            total_net:0, total_sell:0,
            commission_pct:7, govt_tax_pct:0.3,
        };
    }

    function rFlightModal(tab='manual'){
        const el=document.getElementById('iMB'); if(!el)return;
        const bIO=document.getElementById('bIO');
        if(bIO){bIO.classList.remove('hidden');bIO.onclick=saveFlightItem;}
        const titleEl=document.getElementById('iMT');
        if(titleEl)titleEl.textContent=id_ii!==null?'Edit Flight':'Add Flight';

        el.innerHTML=`
        <!-- Tabs -->
        <div class="flex gap-2 mb-4 border-b border-gray-100 pb-3">
            <button id="ftabManual" onclick="PkgBuilder._fTab('manual')"
                class="px-4 py-1.5 rounded-lg text-xs font-semibold transition ${tab==='manual'?'bg-[#1A2039] text-white':'bg-gray-100 text-gray-600 hover:bg-gray-200'}">
                <i class="fa-solid fa-pen mr-1"></i>Manual
            </button>
            <button id="ftabGds" onclick="PkgBuilder._fTab('gds')"
                class="px-4 py-1.5 rounded-lg text-xs font-semibold transition ${tab==='gds'?'bg-sky-600 text-white':'bg-gray-100 text-gray-600 hover:bg-gray-200'}">
                <i class="fa-solid fa-code mr-1"></i>GDS Paste
            </button>
            <button id="ftabExport" onclick="PkgBuilder._fTab('export')"
                class="px-4 py-1.5 rounded-lg text-xs font-semibold transition ${tab==='export'?'bg-gray-700 text-white':'bg-gray-100 text-gray-600 hover:bg-gray-200'}">
                <i class="fa-solid fa-file-export mr-1"></i>GDS Export
            </button>
        </div>

        <!-- Manual Tab -->
        <div id="ftManual" class="${tab!=='manual'?'hidden':''}">
            <div class="grid grid-cols-2 gap-3 mb-3">
                <div><label class="pb-lbl">Airline</label><input id="fAl" class="pb-inp text-xs" placeholder="Turkish Airlines" value="${esc(fd.airline||'')}"></div>
                <div><label class="pb-lbl">Currency</label><input id="fCy" class="pb-inp text-xs uppercase" maxlength="5" value="${esc(fd.currency_code||'BDT')}"></div>
            </div>
            <!-- Segments -->
            <div class="mb-3">
                <div class="flex items-center justify-between mb-2">
                    <label class="pb-lbl" style="margin-bottom:0">Segments</label>
                    <button onclick="PkgBuilder._fAddSeg()" class="text-xs px-2 py-1 rounded-lg bg-sky-50 text-sky-700 hover:bg-sky-100 font-semibold">+ Add</button>
                </div>
                <div class="overflow-x-auto">
                    <div class="min-w-[520px]">
                        <div class="grid grid-cols-[90px_50px_70px_90px_60px_60px_30px] gap-1 text-[10px] font-bold text-gray-400 uppercase px-1 mb-1">
                            <div>Flight</div><div>Class</div><div>Date</div><div>Route</div><div>Dep</div><div>Arr</div><div></div>
                        </div>
                        <div id="fSegs" class="space-y-1"></div>
                    </div>
                </div>
            </div>
            <!-- Fares -->
            <div class="mb-3">
                <div class="flex items-center justify-between mb-2">
                    <label class="pb-lbl" style="margin-bottom:0">Fares</label>
                    <button onclick="PkgBuilder._fAddFare()" class="text-xs px-2 py-1 rounded-lg bg-green-50 text-green-700 hover:bg-green-100 font-semibold">+ Add</button>
                </div>
                <div class="grid grid-cols-[60px_40px_90px_80px_90px_80px_80px] gap-1 text-[10px] font-bold text-gray-400 uppercase px-1 mb-1">
                    <div>Type</div><div>Pax</div><div>Gross</div><div>IATA</div><div>Net</div><div>Payable</div><div></div>
                </div>
                <div id="fFares" class="space-y-1"></div>
            </div>
            <!-- Overnight flag -->
            <div class="flex items-center gap-3 mt-2 pt-2 border-t border-gray-100">
                <label class="flex items-center gap-2 text-xs cursor-pointer select-none">
                    <input type="checkbox" id="fOvn" ${fd.overnight?'checked':''} onchange="fd.overnight=this.checked"
                        class="accent-sky-600">
                    <span class="text-gray-600">Overnight flight</span>
                </label>
                <div id="fOffsetWrap" class="${fd.overnight?'':'hidden'} flex items-center gap-1">
                    <span class="text-xs text-gray-400">Arrives</span>
                    <input type="number" min="1" max="3" value="${fd.arrive_day_offset||1}" id="fOff"
                        class="w-12 text-xs border border-gray-200 rounded-lg px-2 py-1 text-center"
                        onchange="fd.arrive_day_offset=parseInt(this.value)||1">
                    <span class="text-xs text-gray-400">day(s) later</span>
                </div>
            </div>
        </div>

        <!-- GDS Paste Tab -->
        <div id="ftGds" class="${tab!=='gds'?'hidden':''}">
            <p class="text-xs text-gray-400 mb-2">Paste raw GDS text — AI will extract segments and fares.</p>
            <textarea id="fGdsRaw" class="pb-inp font-mono text-xs resize-none mb-3" rows="7"
                placeholder="1. TK 713 M 19MAY DACIST HK3 0650 1245&#10;ADT 1 BDT 42758 + 7047 = 49805">${esc(fd.raw_gds||'')}</textarea>
            <button onclick="PkgBuilder._fExtractGds()" id="bFExtract"
                class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold bg-sky-600 hover:bg-sky-700 text-white transition">
                <i class="fa-solid fa-wand-magic-sparkles"></i>Extract from GDS
            </button>
        </div>

        <!-- GDS Export Tab -->
        <div id="ftExport" class="${tab!=='export'?'hidden':''}">
            <p class="text-xs text-gray-400 mb-2">AI-generated GDS format from your flight data.</p>
            <div id="fGdsOut" class="bg-gray-900 text-green-400 font-mono text-xs p-3 rounded-xl min-h-24 whitespace-pre-wrap mb-3">${esc(fd.raw_gds||'(Generate to see GDS output)')}</div>
            <div class="flex gap-2">
                <button onclick="PkgBuilder._fGenGds()" id="bFGenGds"
                    class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold bg-gray-700 hover:bg-gray-800 text-white transition">
                    <i class="fa-solid fa-code"></i>Generate GDS
                </button>
                <button onclick="navigator.clipboard.writeText(document.getElementById('fGdsOut').textContent).then(()=>thToast('Copied'))"
                    class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition">
                    <i class="fa-solid fa-copy"></i>Copy
                </button>
            </div>
        </div>`;

        renderFlightSegs();
        renderFlightFares();

        // Bind overnight toggle
        const ovn=document.getElementById('fOvn');
        if(ovn) ovn.addEventListener('change',function(){
            fd.overnight=this.checked;
            const w=document.getElementById('fOffsetWrap');
            if(w)w.classList.toggle('hidden',!this.checked);
        });
    }

    function renderFlightSegs(){
        const el=document.getElementById('fSegs'); if(!el)return;
        el.innerHTML=(fd.segments||[]).map((s,si)=>`
        <div class="grid grid-cols-[90px_50px_70px_90px_60px_60px_30px] gap-1 min-w-[520px]">
            <input class="pb-inp text-xs py-1 px-2" value="${esc(s.flight||'')}" placeholder="TK713" oninput="PkgBuilder._fSeg(${si},'flight',this.value)">
            <input class="pb-inp text-xs py-1 px-2" value="${esc(s.class||'')}" placeholder="M" oninput="PkgBuilder._fSeg(${si},'class',this.value)">
            <input class="pb-inp text-xs py-1 px-2" value="${esc(s.date||'')}" placeholder="19MAY" oninput="PkgBuilder._fSeg(${si},'date',this.value)">
            <input class="pb-inp text-xs py-1 px-2" value="${esc(s.route||'')}" placeholder="DAC-IST" oninput="PkgBuilder._fSeg(${si},'route',this.value)">
            <input class="pb-inp text-xs py-1 px-2" value="${esc(s.dep||'')}" placeholder="0650" oninput="PkgBuilder._fSeg(${si},'dep',this.value)">
            <input class="pb-inp text-xs py-1 px-2" value="${esc(s.arr||'')}" placeholder="1245" oninput="PkgBuilder._fSeg(${si},'arr',this.value)">
            <button onclick="PkgBuilder._fRmSeg(${si})" class="p-1 text-red-300 hover:text-red-500"><i class="fa-solid fa-times text-xs"></i></button>
        </div>`).join('');
    }

    function calcFlightFare(f){
        const comm=parseFloat(fd.commission_pct||7)/100;
        const govt=parseFloat(fd.govt_tax_pct||0.3)/100;
        const gross=parseFloat(f.gross_fare||0);
        const base=parseFloat(f.base_fare||gross*0.8);
        f.commission_a=Math.round(base*comm);
        f.govt_tax_b=Math.round(gross*govt);
        f.net_fare=Math.round(gross-f.commission_a+f.govt_tax_b+(parseFloat(f.iata_charge||0)));
        if(!f.payable_edited||!f.payable) f.payable=Math.round((gross+f.net_fare)/2);
        return f;
    }

    function renderFlightFares(){
        const el=document.getElementById('fFares'); if(!el)return;
        el.innerHTML=(fd.fares||[]).map((f,fi)=>{
            const cf=calcFlightFare({...f});
            return`<div class="grid grid-cols-[60px_40px_90px_80px_90px_80px_80px] gap-1 min-w-[540px]">
            <input class="pb-inp text-xs py-1 px-2 font-bold" value="${esc(f.type||'ADT')}" oninput="PkgBuilder._fFare(${fi},'type',this.value,false)" placeholder="ADT">
            <input type="number" min="1" class="pb-inp text-xs py-1 px-2" value="${f.pax||1}" oninput="PkgBuilder._fFare(${fi},'pax',this.value,true)">
            <input type="number" class="pb-inp text-xs py-1 px-2" value="${f.gross_fare||0}" oninput="PkgBuilder._fFare(${fi},'gross_fare',this.value,true)" placeholder="Gross">
            <input type="number" class="pb-inp text-xs py-1 px-2" value="${f.iata_charge||0}" oninput="PkgBuilder._fFare(${fi},'iata_charge',this.value,true)" placeholder="IATA">
            <input class="pb-inp text-xs py-1 px-2 bg-gray-50" value="${cf.net_fare||0}" readonly>
            <input type="number" class="pb-inp text-xs py-1 px-2 border-green-400 bg-green-50 font-bold" value="${f.payable||0}"
                oninput="PkgBuilder._fFare(${fi},'payable',this.value,true);fd.fares[${fi}].payable_edited=true">
            <button onclick="PkgBuilder._fRmFare(${fi})" class="p-1 text-red-300 hover:text-red-500"><i class="fa-solid fa-times text-xs"></i></button>
        </div>`;
        }).join('');
    }

    function flightTotals(){
        let net=0,sell=0;
        (fd.fares||[]).forEach(f=>{
            const cf=calcFlightFare({...f});
            const pax=parseInt(f.pax||1);
            net+=cf.net_fare*pax;
            sell+=(f.payable||0)*pax;
        });
        return {net,sell};
    }

    function buildFlightName(){
        const segs=fd.segments||[];
        if(!segs.length) return fd.airline||'Flight';
        const first=segs[0], last=segs[segs.length-1];
        const route=segs.length===1
            ? (first.route||first.flight||'')
            : `${(first.route||'').split('-')[0]||''} → ${(last.route||'').split('-')[1]||''}`;
        return `${fd.airline||segs[0].flight||'Flight'} ${route}`.trim();
    }

    function saveFlightItem(){
        // Collect airline from input
        fd.airline = thVal('fAl') || fd.airline;
        fd.currency_code = thVal('fCy')?.toUpperCase() || 'BDT';
        const overnight = document.getElementById('fOvn')?.checked || false;
        fd.overnight = overnight;
        fd.arrive_day_offset = parseInt(thVal('fOff'))||1;
        if(!overnight) fd.arrive_day_offset=0;

        // Build name, from/to from segments
        const segs=fd.segments||[];
        if(segs.length){
            const first=segs[0],last=segs[segs.length-1];
            const r0=(first.route||'').split('-');
            const rN=(last.route||'').split('-');
            fd.from_city=r0[0]||'';
            fd.to_city=rN[1]||rN[0]||'';
            // start/end time from first/last segment
            fd.start_time=first.dep||'';
            if(!overnight) fd.end_time=last.arr||'';
        }
        fd.name=buildFlightName();

        // Totals
        const tot=flightTotals();
        fd.total_net=tot.net;
        fd.total_sell=tot.sell;

        const item={...fd};
        if(id_ii!==null) pkg.pack_itenaries[id_di].items[id_ii]=item;
        else pkg.pack_itenaries[id_di].items.push(item);

        // Cascade times — overnight sets next day start
        cascadeTimes(id_di, id_ii!==null?id_ii:pkg.pack_itenaries[id_di].items.length-1);
        if(overnight && fd.arrive_day_offset>0){
            const nextDi=id_di+fd.arrive_day_offset;
            if(pkg.pack_itenaries[nextDi]){
                const lastSeg=fd.segments[fd.segments.length-1];
                if(lastSeg?.arr && !pkg.pack_itenaries[nextDi].items.length){
                    // Pre-set next day's first item cascade start
                    pkg.pack_itenaries[nextDi]._overnight_arrive=lastSeg.arr;
                }
            }
        }

        thCloseModal('iMod');
        const lel=document.getElementById(`iL${id_di}`);
        if(lel){
            const ss=lel._suggs,panel=document.getElementById(`suggPanel${id_di}`);
            lel.innerHTML=rItems(id_di);lel._suggs=ss;
            if(panel&&ss?.length)lel.insertAdjacentElement('afterbegin',panel);
        }
    }

    function rItemModal(){
        const el=document.getElementById('iMB');if(!el)return;
        const act=id_draft.type==='activity';
        const variants=id_draft._variants||[];
        const defProf=act?(pkg.pricing_config.activity_profit||15):(pkg.pricing_config.transport_profit||10);
        el.innerHTML=`
        ${variants.length>1?`
        <div class="mb-4">
            <label class="pb-lbl">Select Variant</label>
            <div class="space-y-1.5 max-h-36 overflow-y-auto">
                ${variants.map(v=>`
                <button onclick="PkgBuilder._selV('${v.sys_id}','${v.currency_code||"BDT"}',${parseFloat(v.net_cost)||0},${parseFloat(v.sell_price)||0},${parseFloat(v.child_price||0)},'${v.price_basis||"per_pax"}'${!act?`,'${v.vehicle_class||""}'`:''})"
                    class="w-full flex items-center gap-2 px-3 py-2 rounded-lg border transition text-left text-xs ${id_draft.variant_sys_id===v.sys_id?'border-[#50BC81] bg-[#50BC81]/5':'border-gray-200 hover:bg-gray-50'}">
                    <div class="flex-1 truncate font-medium">${esc(v.variant_name||v.vehicle_class||'Option')}</div>
                    <div class="text-right flex-shrink-0"><div class="font-semibold text-[#1A2039]">${esc(v.currency_code||'')} ${fmt(parseFloat(v.net_cost)||0)}</div><div class="text-[10px] text-gray-400">${v.price_basis||'per_pax'}</div></div>
                    ${id_draft.variant_sys_id===v.sys_id?'<i class="fa-solid fa-check text-[#50BC81] flex-shrink-0"></i>':''}
                </button>`).join('')}
            </div>
        </div>
        <div class="border-t border-gray-100 pt-3 mb-3"></div>`:''}
        <div class="space-y-3">
            <div><label class="pb-lbl">Name *</label><input id="iNm" class="pb-inp text-sm" placeholder="Item name" value="${esc(id_draft.name||'')}"></div>
            ${act?`
            <div class="grid grid-cols-2 gap-3">
                <div><label class="pb-lbl">Category</label><input id="iCa" class="pb-inp text-xs" placeholder="amusement park…" value="${esc(id_draft.category||'')}"></div>
                <div><label class="pb-lbl">Duration (hrs)</label><input id="iDh" type="number" step="0.5" class="pb-inp text-xs" value="${id_draft.duration_hours||0}"></div>
            </div>`:`
            <div class="grid grid-cols-2 gap-3">
                <div><label class="pb-lbl">Transport Type</label><select id="iTt" class="pb-inp text-xs">${TTYPES.map(t=>`<option value="${t}" ${id_draft.transport_type===t?'selected':''}>${t.replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase())}</option>`).join('')}</select></div>
                <div><label class="pb-lbl">Vehicle Class</label><select id="iVc" class="pb-inp text-xs">${VCLS.map(v=>`<option value="${v}" ${id_draft.vehicle_class===v?'selected':''}>${v.charAt(0).toUpperCase()+v.slice(1)}</option>`).join('')}</select></div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="pb-lbl">From City</label><input id="iFc" class="pb-inp text-xs" placeholder="Bangkok" value="${esc(id_draft.from_city||'')}"></div>
                <div><label class="pb-lbl">To City</label><input id="iTc" class="pb-inp text-xs" placeholder="Pattaya" value="${esc(id_draft.to_city||'')}"></div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="pb-lbl">Duration (hrs)</label><input id="iDt" type="number" step="0.5" min="0" class="pb-inp text-xs" placeholder="e.g. 2.5" value="${esc(id_draft.duration_typical||'')}"></div>
            </div>`}
            <div class="bg-gray-50 rounded-xl p-3">
                <label class="pb-lbl mb-2 block">Pricing</label>
                <div class="flex items-end gap-2 mb-2">
                    <div class="w-14 flex-shrink-0"><label class="text-[10px] text-gray-400 mb-1 block">CCY</label><input id="iCy" class="pb-inp text-xs uppercase text-center px-1" maxlength="5" value="${esc(id_draft.currency_code||'BDT')}"></div>
                    <div class="flex-1"><label class="text-[10px] text-gray-400 mb-1 block">Net Cost</label><input id="iNc" type="number" step="0.01" class="pb-inp text-xs" value="${id_draft.net_cost||0}" oninput="PkgBuilder._aS()"></div>
                    <span class="text-gray-400 text-sm flex-shrink-0 mb-2">+</span>
                    <div class="w-20 flex-shrink-0"><label class="text-[10px] text-gray-400 mb-1 block">Profit %</label>
                        <div class="relative"><input id="iPct" type="number" step="0.1" min="0" class="pb-inp text-xs pr-4 text-right" value="${defProf}" oninput="PkgBuilder._aS()"><span class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] text-gray-400">%</span></div>
                    </div>
                    <span class="text-[#50BC81] font-bold text-base flex-shrink-0 mb-2">=</span>
                    <div class="flex-1"><label class="text-[10px] text-[#50BC81] font-semibold mb-1 block">Sell Price</label><input id="iSp" type="number" step="0.01" class="pb-inp text-xs border-[#50BC81]/50 bg-[#50BC81]/5 font-semibold" value="${fmt(id_draft.sell_price||0)}"></div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div><label class="text-[10px] text-gray-400 mb-1 block">Child Price (opt.)</label><input id="iCp" type="number" step="0.01" class="pb-inp text-xs" placeholder="0.00" value="${id_draft.child_price||''}"></div>
                    <div><label class="text-[10px] text-gray-400 mb-1 block">Price Basis</label><select id="iPb" class="pb-inp text-xs">${PBASES.map(b=>`<option value="${b}" ${id_draft.price_basis===b?'selected':''}>${b.replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase())}</option>`).join('')}</select></div>
                </div>
            </div>
        </div>`;
    }

    function saveItem(){
        const name=thVal('iNm');if(!name){thToast('Item name required','error');return;}
        id_draft.name=name;
        if(id_draft.type==='activity'){
            id_draft.category=thVal('iCa');
            id_draft.duration_hours=parseFloat(thVal('iDh'))||0;
        } else {
            id_draft.transport_type=thVal('iTt');id_draft.vehicle_class=thVal('iVc');
            id_draft.from_city=thVal('iFc');id_draft.to_city=thVal('iTc');
            id_draft.duration_typical=thVal('iDt')||'';
        }
        id_draft.currency_code=thVal('iCy').toUpperCase()||'BDT';
        id_draft.net_cost=parseFloat(thVal('iNc'))||0;id_draft.sell_price=parseFloat(thVal('iSp'))||0;
        id_draft.child_price=thVal('iCp')?parseFloat(thVal('iCp')):null;id_draft.price_basis=thVal('iPb')||'per_pax';
        const savedIdx=id_ii!==null?id_ii:null;
        if(id_ii!==null) pkg.pack_itenaries[id_di].items[id_ii]={...id_draft};
        else pkg.pack_itenaries[id_di].items.push({...id_draft});
        // Cascade from this item's position
        const pos=savedIdx!==null?savedIdx:pkg.pack_itenaries[id_di].items.length-1;
        cascadeTimes(id_di, pos);
        thCloseModal('iMod');
        const lel=document.getElementById(`iL${id_di}`);
        if(lel){
            const savedSuggs=lel._suggs;
            const panel=document.getElementById(`suggPanel${id_di}`);
            lel.innerHTML=rItems(id_di);
            lel._suggs=savedSuggs;
            if(panel&&savedSuggs?.length)lel.insertAdjacentElement('afterbegin',panel);
        }
    }

    // ── AI Build Full Itinerary ───────────────────────────────────────
    async function buildItinerary(){
        const prompt=thVal('aiPr');
        if(!prompt){thToast('Enter a description first','error');return;}
        if(!pkg.countries.length){thToast('Add destinations in Step 2 first','warning');return;}
        setBusy('bBuild',true);
        const res=await thApi(`${API_BASE}api/ai/itinerary-build.php`,'POST',{
            prompt,package_type:pkg.package_type,duration:pkg.duration||5,adults:pkg.adults||2,
            countries:pkg.countries.map(c=>({sys_id:c.sys_id,name:c.name})),
        });
        setBusy('bBuild',false);
        if(!res.success){thToast(res.message||'AI failed','error');return;}
        const days=res.days||[];
        document.getElementById('aiFCards').innerHTML=days.map(d=>`
            <div class="border border-gray-200 rounded-xl px-4 py-3">
                <div class="flex items-center gap-2 mb-2">
                    <span class="w-6 h-6 rounded-full bg-[#50BC81] text-white text-xs font-bold flex items-center justify-center">${d.day_number}</span>
                    <span class="text-sm font-semibold text-[#1A2039]">${esc(d.title||'')}</span>
                    <span class="text-sm">${d.meal_breakfast?'🍳':''}${d.meal_lunch?'🥗':''}${d.meal_dinner?'🍽️':''}</span>
                </div>
                ${(d.items||[]).length?`<div class="space-y-1">${(d.items||[]).map(item=>`
                <div class="flex items-center gap-2 text-xs px-2 py-1.5 rounded-lg ${item.source==='ai'?'bg-purple-50 border border-purple-200':'bg-gray-50'}">
                    <i class="fa-solid ${item.type==='activity'?'fa-person-hiking text-green-600':'fa-van-shuttle text-blue-500'} w-4 text-center"></i>
                    <span class="flex-1 font-medium">${esc(item.name)}</span>
                    ${item.net_cost?`<span class="text-gray-400 font-mono">${esc(item.currency_code||'')} ${fmt(item.net_cost)}</span>`:''}
                    ${item.source==='ai'?'<span class="px-1.5 py-0.5 rounded text-[10px] bg-purple-100 text-purple-700">⚡ AI</span>':''}
                </div>`).join('')}</div>`:'<p class="text-xs text-gray-300 italic">No items</p>'}
            </div>`).join('');
        document.getElementById('aiFull').classList.remove('hidden');
        document.getElementById('bAccept').onclick=()=>{
            pkg.pack_itenaries=days.map(d=>({day_number:d.day_number,title:d.title||`Day ${d.day_number}`,city_name:d.city_name||'',hotel_ref:null,meal_breakfast:d.meal_breakfast||false,meal_lunch:d.meal_lunch||false,meal_dinner:d.meal_dinner||false,raw_text:'',items:d.items||[]}));
            iMode='manual';s3(document.getElementById('sC'));thToast(`${days.length} days built — review in manual editor`);
        };
    }


    // ═══ STEP 4 — Inclusions ═══
    function s4(el){
        if(!Array.isArray(pkg.pack_inclusions))pkg.pack_inclusions=[];
        if(!Array.isArray(pkg.pack_exclusions))pkg.pack_exclusions=[];
        if(!Array.isArray(pkg.pack_components))pkg.pack_components=[];
        el.innerHTML=`
        <h2 class="text-lg font-bold text-[#1A2039] mb-5">Inclusions, Exclusions & Components</h2>
        <div class="grid grid-cols-2 gap-5 mb-5">
            <div>
                <h3 class="text-sm font-bold text-green-700 mb-3"><i class="fa-solid fa-circle-check mr-2"></i>Inclusions</h3>
                <div class="flex gap-2 mb-2">
                    <input id="incI" class="pb-inp text-sm flex-1" placeholder="Type inclusion text…" onkeydown="if(event.key==='Enter'){event.preventDefault();PkgBuilder._addInc();}">
                    <button onclick="PkgBuilder._addInc()" class="px-3 py-2 rounded-xl text-sm font-semibold bg-green-600 hover:bg-green-700 text-white transition flex-shrink-0">Add</button>
                </div>
                <div id="incL" class="space-y-1.5 max-h-40 overflow-y-auto mb-4"></div>
                <div class="border-t border-gray-100 pt-3">
                    <p class="text-xs font-semibold text-gray-500 mb-2"><i class="fa-solid fa-puzzle-piece text-blue-400 mr-1.5"></i>Add from Components <span class="font-normal text-gray-400">(priceable)</span></p>
                    <input id="compSrch" class="pb-inp text-xs mb-2" placeholder="Search components…" oninput="PkgBuilder._srchComp(this.value)">
                    <div id="compDrop" class="border border-gray-200 rounded-xl overflow-hidden bg-white max-h-44 overflow-y-auto">
                        <div class="text-center py-3 text-gray-300 text-xs">Type to search or leave blank…</div>
                    </div>
                </div>
            </div>
            <div>
                <h3 class="text-sm font-bold text-red-600 mb-3"><i class="fa-solid fa-circle-xmark mr-2"></i>Exclusions</h3>
                <div class="flex gap-2 mb-2">
                    <input id="excI" class="pb-inp text-sm flex-1" placeholder="Type exclusion text…" onkeydown="if(event.key==='Enter'){event.preventDefault();PkgBuilder._addExc();}">
                    <button onclick="PkgBuilder._addExc()" class="px-3 py-2 rounded-xl text-sm font-semibold bg-red-500 hover:bg-red-600 text-white transition flex-shrink-0">Add</button>
                </div>
                <div id="excL" class="space-y-1.5 max-h-40 overflow-y-auto mb-4"></div>
                <div class="border-t border-gray-100 pt-3">
                    <p class="text-xs font-semibold text-gray-500 mb-2"><i class="fa-solid fa-puzzle-piece text-blue-400 mr-1.5"></i>Added Components</p>
                    <div id="compList" class="space-y-1.5 max-h-44 overflow-y-auto"></div>
                </div>
            </div>
        </div>`;
        rIncExc();rCompList();srchComp('');
    }

    let _srchTimer=null;
    async function srchComp(q){
        clearTimeout(_srchTimer);
        _srchTimer=setTimeout(async()=>{
            const el=document.getElementById('compDrop');if(!el)return;
            el.innerHTML='<div class="text-center py-2 text-gray-300 text-xs"><i class="fa-solid fa-spinner fa-spin mr-1"></i></div>';
            const d=await thApi(`${API_BASE}api/masterdata/components/list.php?search=${encodeURIComponent(q)}&limit=20&status=active`);
            const rows=d.data||[];
            if(!rows.length){el.innerHTML='<p class="text-xs text-gray-300 italic text-center py-2">No components found</p>';return;}
            el.innerHTML=rows.map(c=>`
                <button onclick="PkgBuilder._pickComp('${c.sys_id}','${esc(c.name)}','${esc(c.category||'')}')"
                    class="w-full flex items-center gap-2 px-3 py-2 hover:bg-blue-50 transition text-left border-b border-gray-100 last:border-0">
                    <i class="fa-solid fa-puzzle-piece text-blue-400 text-xs flex-shrink-0"></i>
                    <div class="flex-1 min-w-0"><div class="text-xs font-medium text-[#1A2039] truncate">${esc(c.name)}</div><div class="text-[10px] text-gray-400">${esc(c.category||'')}</div></div>
                    ${pkg.pack_components?.find(x=>x.sys_id===c.sys_id)?'<i class="fa-solid fa-check text-[#50BC81] text-xs flex-shrink-0"></i>':'<i class="fa-solid fa-plus text-gray-300 text-xs flex-shrink-0"></i>'}
                </button>`).join('');
        },q?300:0);
    }

    async function pickComp(sys_id,name,category){
        if(pkg.pack_components?.find(c=>c.sys_id===sys_id)){thToast('Already added','warning');return;}
        if(!Array.isArray(pkg.pack_components))pkg.pack_components=[];
        const vd=await thApi(`${API_BASE}api/masterdata/components/variant-list.php?component_sys_id=${sys_id}&status=active&limit=5`);
        const v=(vd.data||[])[0];
        pkg.pack_components.push({sys_id,name,category,
            variant_sys_id:v?.sys_id||'',variant_name:v?.variant_name||'',
            currency_code:v?.currency_code||pkg.sell_currency_code||'BDT',
            net_cost:parseFloat(v?.net_cost||0),sell_price:parseFloat(v?.sell_price||0),
            unit_basis:v?.unit_basis||'per_pax',qty:1});
        if (!pkg.pack_inclusions.includes(name)) pkg.pack_inclusions.push(name);

        // ── এই block টা যোগ করো ──
        const compProf = pkg.pricing_config.component_profit || 8;
        const compNet = parseFloat(v?.net_cost || 0);
        const compSell = (compNet > 0 ? parseFloat((compNet/(1-compProf/100)).toFixed(2)) : 0) || parseFloat(v?.sell_price || 0);
        const compKey = `component:${name}`;
        console.log(compNet, compSell);
        if (!pkg.pack_price.find(p => p._key === compKey)) {
            pkg.pack_price.push({
                _key: compKey, type: 'component',
                name, variant_name: v?.variant_name || '',
                currency_code: v?.currency_code || pkg.sell_currency_code || 'BDT',
                net_cost: compNet, sell_price: compSell,
                price_basis: v?.unit_basis || 'per_pax',
                profit_pct: compProf, qty: 1
            });
        }
        // ─────────────────────────────
        rIncExc();rCompList();
        srchComp(thVal('compSrch')||'');
        thToast(`${name} added`);
    }

    function rCompList(){
        const el=document.getElementById('compList');if(!el)return;
        if(!Array.isArray(pkg.pack_components))pkg.pack_components=[];
        el.innerHTML=pkg.pack_components.map((c,ci)=>`
            <div class="flex items-center gap-2 px-3 py-2 bg-blue-50 rounded-xl border border-blue-100">
                <i class="fa-solid fa-puzzle-piece text-blue-500 text-xs flex-shrink-0"></i>
                <div class="flex-1 min-w-0">
                    <div class="text-xs font-semibold text-[#1A2039] truncate">${esc(c.name)}</div>
                    <div class="text-[10px] text-gray-400">${esc(c.currency_code)} ${fmt(c.net_cost)} net</div>
                </div>
                <input type="number" min="1" class="w-10 text-xs text-center border border-gray-200 rounded-lg px-1 py-0.5" value="${c.qty||1}"
                    onchange="PkgBuilder._compQty(${ci},parseInt(this.value)||1)" title="Qty">
                <button onclick="PkgBuilder._rmComp(${ci})" class="p-1 hover:text-red-500 text-red-300 transition"><i class="fa-solid fa-times text-xs"></i></button>
            </div>`).join('')||'<p class="text-xs text-gray-300 italic">No components yet</p>';
    }

    function rIncExc(){
        const iEl=document.getElementById('incL'),eEl=document.getElementById('excL');
        if(iEl) iEl.innerHTML=pkg.pack_inclusions.map((item,i)=>`
            <div class="flex items-center gap-2 px-3 py-2 bg-green-50 rounded-xl border border-green-100">
                <i class="fa-solid fa-check text-green-600 text-xs flex-shrink-0"></i>
                <span class="flex-1 text-sm text-gray-700">${esc(item)}</span>
                <button onclick="PkgBuilder._rmInc(${i})" class="text-red-300 hover:text-red-500 transition text-xs"><i class="fa-solid fa-times"></i></button>
            </div>`).join('')||'<p class="text-xs text-gray-300 italic">None yet</p>';
        if(eEl) eEl.innerHTML=pkg.pack_exclusions.map((item,i)=>`
            <div class="flex items-center gap-2 px-3 py-2 bg-red-50 rounded-xl border border-red-100">
                <i class="fa-solid fa-xmark text-red-500 text-xs flex-shrink-0"></i>
                <span class="flex-1 text-sm text-gray-700">${esc(item)}</span>
                <button onclick="PkgBuilder._rmExc(${i})" class="text-red-300 hover:text-red-500 transition text-xs"><i class="fa-solid fa-times"></i></button>
            </div>`).join('')||'<p class="text-xs text-gray-300 italic">None yet</p>';
    }

    // ═══ STEP 5 — Pricing ═══  FIX: components+hotel price now show
    function s5(el){
        if(!Array.isArray(pkg.pack_price))pkg.pack_price=[];
        if(!pkg.pricing_config||typeof pkg.pricing_config!=='object')
            pkg.pricing_config={activity_profit:15,hotel_profit:12,transport_profit:10,component_profit:8,exchange_rate:1,exchange_country_sys_id:''};
        // FIX: sync ALL sources before rendering
        syncPrice();
        syncCompPrice();
        const pc=pkg.pricing_config;
        el.innerHTML=`
        <div class="flex gap-4">
            <div class="w-60 flex-shrink-0">
                <div class="bg-gray-50 rounded-2xl border border-gray-200 p-4 sticky top-4">
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-3">Global Configuration</p>
                    <div class="space-y-3">
                        <div>
                            <label class="text-xs text-gray-400 mb-1 block"><i class="fa-solid fa-globe mr-1"></i>Country</label>
                            <select id="pcC" class="pb-inp text-xs" onchange="PkgBuilder._pcC(this)">
                                <option value="">-- Select country --</option>
                                ${cc.map(c=>`<option value="${c.sys_id}" data-r="${c.default_rate}" ${pc.exchange_country_sys_id===c.sys_id?'selected':''}>${esc(c.name)}</option>`).join('')}
                            </select>
                        </div>
                        <div>
                            <label class="text-xs text-gray-400 mb-1 block"><i class="fa-solid fa-arrow-right-arrow-left mr-1"></i>Exchange rate (1 local = ? BDT)</label>
                            <div class="flex items-center gap-2">
                                <input id="pcR" type="number" step="0.0001" class="pb-inp text-xs flex-1" value="${pc.exchange_rate||1}" onchange="PkgBuilder._pcR(this.value)">
                                <span class="text-xs text-gray-400 font-mono flex-shrink-0">BDT</span>
                            </div>
                            <p class="text-[10px] text-gray-400 mt-1">Auto-filled from country. Edit to override.</p>
                        </div>
                        <div class="border-t border-gray-200 pt-3">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase mb-2">Profit % by Category</p>
                            <div class="space-y-2">
                                ${[['pcAP','Activity profit %',pc.activity_profit||15],['pcHP','Hotel profit %',pc.hotel_profit||12],['pcTP','Transport profit %',pc.transport_profit||10],['pcCP','Component profit %',pc.component_profit||8]].map(([id,lbl,val])=>`
                                <div class="flex items-center gap-2">
                                    <label class="text-[10px] text-gray-400 flex-1">${lbl}</label>
                                    <div class="flex items-center gap-1">
                                        <input id="${id}" type="number" step="0.1" class="w-16 text-xs text-right border border-gray-200 rounded-lg px-2 py-1" value="${val}" onchange="PkgBuilder._apP()">
                                        <span class="text-[10px] text-gray-400">%</span>
                                    </div>
                                </div>`).join('')}
                            </div>
                        </div>
                        <div class="border-t border-gray-200 pt-3">
                            <p class="text-[10px] font-bold text-gray-400 uppercase mb-2">Combined Summary</p>
                            <div class="grid grid-cols-2 gap-1.5">
                                <div class="bg-green-50 rounded-xl p-2 text-center"><p class="text-[10px] text-green-500 mb-0.5">Activity total</p><p class="text-sm font-bold text-green-700" id="sA">0.00</p><p class="text-[10px] text-green-400">BDT</p></div>
                                <div class="bg-purple-50 rounded-xl p-2 text-center"><p class="text-[10px] text-purple-500 mb-0.5">Hotel total</p><p class="text-sm font-bold text-purple-700" id="sH">0.00</p><p class="text-[10px] text-purple-400">BDT</p></div>
                                <div class="bg-yellow-50 rounded-xl p-2 text-center"><p class="text-[10px] text-yellow-600 mb-0.5">Transport total</p><p class="text-sm font-bold text-yellow-700" id="sTr">0.00</p><p class="text-[10px] text-yellow-500">BDT</p></div>
                                <div class="bg-blue-50 rounded-xl p-2 text-center"><p class="text-[10px] text-blue-500 mb-0.5">Total rows</p><p class="text-sm font-bold text-blue-700" id="sRows">0</p><p class="text-[10px] text-blue-400">act + hotel</p></div>
                            </div>
                            <div class="bg-yellow-50 rounded-xl p-2 mt-1.5 text-center"><p class="text-[10px] text-yellow-600">Total profit</p><p class="text-base font-bold text-yellow-700" id="sProfit">0.00</p><p class="text-[10px] text-yellow-500">BDT</p></div>
                            <div class="bg-[#50BC81]/10 border border-[#50BC81]/30 rounded-xl p-3 mt-2 text-center">
                                <p class="text-xs font-bold text-[#1A2039]">Grand Total</p>
                                <p class="text-xl font-bold text-[#50BC81]" id="sGrand">0.00</p>
                                <p class="text-[10px] text-gray-500" id="sGrandL">BDT combined</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex-1 space-y-4 min-w-0">
                ${['hotel','activity','transport','flight','component'].map(type=>priceSeg(type)).join('')}
            </div>
        </div>`;
        renderPriceRows();
    }

    function priceSeg(type){
        const items = pkg.pack_price.filter(p => p.type === type);
        const cfg = {
            hotel:     {lbl:'Hotels',     icon:'fa-hotel',         cls:'purple'},
            activity:  {lbl:'Activities', icon:'fa-person-hiking', cls:'green'},
            transport: {lbl:'Transport',  icon:'fa-van-shuttle',   cls:'yellow'},
            flight:    {lbl:'Flights',    icon:'fa-plane',         cls:'sky'},
            component: {lbl:'Components', icon:'fa-puzzle-piece',  cls:'blue'}
        };
        const c = cfg[type];
        const hdr = {
            purple: 'bg-purple-50 border-purple-200',
            green:  'bg-green-50 border-green-200',
            yellow: 'bg-yellow-50 border-yellow-200',
            sky:    'bg-sky-50 border-sky-200',
            blue:   'bg-blue-50 border-blue-200'
        }[c.cls];

        return `<div class="border border-gray-200 rounded-xl overflow-hidden mb-4">
            <div class="flex items-center gap-2 px-4 py-2.5 border-b border-gray-200 ${hdr}">
                <i class="fa-solid ${c.icon} text-sm"></i>
                <span class="text-sm font-bold">${c.lbl}</span>
                <span class="text-xs ml-auto text-gray-500">${items.length} item(s)</span>
            </div>
            ${items.length ? `<div class="overflow-x-auto"><table class="w-full text-xs white-space-nowrap">
                <thead class="bg-gray-50 text-gray-400 uppercase text-[10px] tracking-wide">
                    <tr>
                        <th class="px-3 py-2 text-left min-w-[160px]">Item</th> ${type === 'hotel' ? `
                            <th class="px-3 py-2 text-center w-14">Nights</th>
                            <th class="px-3 py-2 text-center w-14">Rooms</th>
                            <th class="px-3 py-2 text-center w-12">Pax</th>
                            <th class="px-3 py-2 text-right w-36">Net/Night</th>
                            <th class="px-3 py-2 text-right w-28">Per Pax/Night</th>
                        ` : `
                            <th class="px-3 py-2 text-right w-16">CCY</th>
                            <th class="px-3 py-2 text-right w-32">Net Cost</th>
                        `}
                        <th class="px-3 py-2 text-right w-24">Profit %</th>
                        <th class="px-3 py-2 text-right w-40">Sell Price</th> <th class="px-3 py-2 text-right w-32">Total (BDT)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100" id="pRows_${type}"></tbody>
            </table></div>` : `<p class="text-xs text-gray-300 italic px-4 py-3">No ${c.lbl.toLowerCase()} yet</p>`}
        </div>`;
    }

    function renderPriceRows(){
        const rate = parseFloat(pkg.pricing_config.exchange_rate) || 1;
        const pax = (pkg.adults || 1) + (pkg.children || 0);
        
        ['hotel', 'activity', 'transport', 'flight', 'component'].forEach(type => {
            const tbody = document.getElementById(`pRows_${type}`); if (!tbody) return;
            
            tbody.innerHTML = pkg.pack_price.map((item, gi) => { 
                if (item.type !== type) return '';
                
                const nights = type === 'hotel' ? (item.nights || 1) : 1;
                const rooms = type === 'hotel' ? (item.rooms || 1) : 1;
                const mult = type === 'hotel' ? nights * rooms : (item.qty || 1);
                const sellBDT = (item.sell_price || 0) * mult * rate;
                const ppn = type === 'hotel' && pax > 0 ? (item.net_cost || item.net_per_night || 0) / pax : null;

                console.log(item.net_cost);
                
                return `<tr class="hover:bg-gray-50 transition">
                    <td class="px-3 py-2 min-w-[160px]">
                        <div class="text-xs font-medium text-[#1A2039] truncate max-w-[200px]" title="${esc(item.name)}">${esc(item.name)}</div>
                        <div class="text-[10px] text-gray-400 truncate max-w-[200px]">${esc(item.variant_name || item.meal_plan || '')}</div>
                    </td>
                    ${type === 'hotel' ? `
                        <td class="px-3 py-2 text-center"><input type="number" min="1" class="w-12 text-xs text-center border border-gray-200 rounded-lg px-1 py-1" value="${nights}" onchange="PkgBuilder._pi(${gi},'nights',parseInt(this.value)||1);PkgBuilder._rp()"></td>
                        <td class="px-3 py-2 text-center"><input type="number" min="1" class="w-11 text-xs text-center border border-gray-200 rounded-lg px-1 py-1" value="${rooms}" onchange="PkgBuilder._pi(${gi},'rooms',parseInt(this.value)||1);PkgBuilder._rp()"></td>
                        <td class="px-3 py-2 text-center text-xs text-gray-500">${pax}</td>
                        <td class="px-3 py-2"><div class="flex items-center gap-1 justify-end">
                            <input class="w-11 text-[10px] text-center border border-gray-200 rounded px-1 py-1 uppercase" maxlength="5" value="${esc(item.currency_code || '')}" onchange="PkgBuilder._pi(${gi},'currency_code',this.value.toUpperCase());PkgBuilder._rp()">
                            <input type="number" step="0.01" class="w-20 text-xs text-right border border-gray-200 rounded-lg px-1.5 py-1" value="${fmt(item.net_cost || 0)}" onchange="PkgBuilder._pi(${gi},'net_cost',parseFloat(this.value)||0);PkgBuilder._aSI(${gi},'hotel');PkgBuilder._rp()">
                        </div></td>
                        <td class="px-3 py-2 text-right text-xs text-gray-500 font-mono">${ppn !== null ? fmt(ppn) : '-'}</td>
                    ` : `
                        <td class="px-3 py-2 text-right"><input class="w-12 text-[10px] text-center border border-gray-200 rounded px-1 py-1 uppercase" maxlength="5" value="${esc(item.currency_code || '')}" onchange="PkgBuilder._pi(${gi},'currency_code',this.value.toUpperCase());PkgBuilder._rp()"></td>
                        <td class="px-3 py-2"><input type="text" inputmode="decimal" step="0.01" class="w-full min-w-[80px] text-xs text-right border border-gray-200 rounded-lg px-1.5 py-1" value="${fmt(item.net_cost || 0)}" onchange="PkgBuilder._pi(${gi},'net_cost',parseFloat(this.value)||0);PkgBuilder._aSI(${gi},'${type}');PkgBuilder._rp()"></td>
                    `}
                    <td class="px-3 py-2">
                        <div class="flex items-center gap-1 justify-end">
                            <input type="number" step="0.1" class="w-14 text-xs text-right border border-gray-200 rounded-lg px-1.5 py-1" value="${fmt(item.profit_pct || 0, 1)}" onchange="PkgBuilder._pi(${gi},'profit_pct',parseFloat(this.value)||0);PkgBuilder._aSI(${gi},'${type}');PkgBuilder._rp()">
                            <span class="text-[10px] text-gray-400">%</span>
                        </div>
                    </td>
                    <td class="px-3 py-2 w-40 min-w-[130px]">
                        <input type="text" 
                            inputmode="decimal"
                            class="w-full text-xs text-right border-2 border-[#50BC81]/30 bg-[#50BC81]/5 rounded-lg px-1.5 py-1 font-semibold" 
                            value="${fmt(item.sell_price || 0)}" 
                            onchange="PkgBuilder._pi(${gi},'sell_price',parseFloat(this.value.replace(/,/g, ''))||0);PkgBuilder._rp()">
                    </td>
                    <td class="px-3 py-2 text-right font-semibold text-[#1A2039] text-xs font-mono">${fmt(sellBDT)}</td>
                </tr>`;
            }).join('');
        });
        recalc();
    }

    // FIX: syncPrice uses net_cost (which now = net_per_night for hotels)
    function syncPrice(){
        const existing=new Map(pkg.pack_price.map(p=>[p._key||`${p.type}:${p.name}`,p]));
        const pc=pkg.pricing_config;
        // Hotels
        (pkg.hotels||[]).forEach(h=>{
            const key=`hotel:${h.hotel_name}:${h.room_type_name}`;
            if(!existing.has(key)){
                const netPerNight=h.net_per_night||h.net_cost||0;
                const prof=pc.hotel_profit||12;
                const sell=netPerNight>0?parseFloat((netPerNight/(1-prof/100)).toFixed(2)):0;
                pkg.pack_price.push({_key:key,type:'hotel',
                    name:h.hotel_name,
                    variant_name:`${h.room_type_name||''} · ${MPLANS[h.meal_plan]||h.meal_plan}`,
                    meal_plan:h.meal_plan,currency_code:h.currency_code,
                    net_cost:netPerNight,net_per_night:netPerNight,
                    sell_price:sell,nights:h.nights||1,rooms:h.rooms||1,profit_pct:prof,qty:1});
                existing.set(key,pkg.pack_price[pkg.pack_price.length-1]);
            }
        });
        // Itinerary items
        (pkg.pack_itenaries||[]).forEach(day=>(day.items||[]).forEach(item=>{
            if(item.type==='flight'){
                // Each fare row = separate pack_price entry
                (item.fares||[]).forEach(fare=>{
                    const key=`flight:${item.name}:${fare.type}`;
                    if(!existing.has(key)){
                        const cf=calcFlightFare({...fare});
                        pkg.pack_price.push({
                            _key:key, type:'flight',
                            name:item.name,
                            variant_name:`${fare.type} × ${fare.pax}`,
                            currency_code:item.currency_code||pkg.sell_currency_code,
                            net_cost:cf.net_fare||0,
                            sell_price:parseFloat(fare.payable||0),
                            price_basis:'per_pax',
                            profit_pct:0, qty:parseInt(fare.pax||1),
                        });
                        existing.set(key,pkg.pack_price[pkg.pack_price.length-1]);
                    }
                });
                return;
            }
            const type=item.type==='transport'?'transport':'activity';
            const key=`${type}:${item.name}`;
            if(!existing.has(key)){
                const prof=type==='transport'?(pc.transport_profit||10):(pc.activity_profit||15);
                const net=item.net_cost||0;
                const sell=(net>0?parseFloat((net/(1-prof/100)).toFixed(2)):0)||item.sell_price||0;
                pkg.pack_price.push({_key:key,type,name:item.name,variant_name:item.variant_name||'',
                    currency_code:item.currency_code||pkg.sell_currency_code,
                    net_cost:net,sell_price:parseFloat((sell||item.sell_price).toFixed(2)),
                    child_price:item.child_price||null,price_basis:item.price_basis||'per_pax',
                    profit_pct:prof,qty:1});
                existing.set(key,pkg.pack_price[pkg.pack_price.length-1]);
            }
        }));
    }

    // FIX: components sync — always run fresh to catch newly added components
    function syncCompPrice(){
        if(!Array.isArray(pkg.pack_components)||!pkg.pack_components.length) return;
        const existing=new Map(pkg.pack_price.map(p=>[p._key||`${p.type}:${p.name}`,p]));
        const pc=pkg.pricing_config;
        pkg.pack_components.forEach(comp=>{
            const key=`component:${comp.name}`;
            if(!existing.has(key)){
                const prof=pc.component_profit||8;
                const net=parseFloat(comp.net_cost)||0;
                const sell=(net>0?parseFloat((net/(1-prof/100)).toFixed(2)):0)||parseFloat(comp.sell_price)||0;
                pkg.pack_price.push({_key:key,type:'component',name:comp.name,
                    variant_name:comp.variant_name||'',
                    currency_code:comp.currency_code||pkg.sell_currency_code,
                    net_cost:net,sell_price:sell,
                    price_basis:comp.unit_basis||'per_pax',
                    profit_pct:prof,qty:comp.qty||1});
                existing.set(key,pkg.pack_price[pkg.pack_price.length-1]);
            }
        });
    }

    function recalc(){
        const rate=parseFloat(pkg.pricing_config.exchange_rate)||1;
        const pax=(pkg.adults||1)+(pkg.children||0);
        const tots={activity:0,hotel:0,transport:0,component:0,cost:0,sell:0};
        pkg.pack_price.forEach(item=>{
            const mult=item.type==='hotel'?(item.nights||1)*(item.rooms||1):(item.qty||1);
            const sBDT=(item.sell_price||0)*mult*rate,cBDT=(item.net_cost||0)*mult*rate;
            tots[item.type]=(tots[item.type]||0)+sBDT;tots.cost+=cBDT;tots.sell+=sBDT;
        });
        const profit=tots.sell-tots.cost,grand=tots.sell;
        const set=(id,v)=>{const e=document.getElementById(id);if(e)e.textContent=fmt(v);};
        set('sA',tots.activity);set('sH',tots.hotel);set('sTr',tots.transport);set('sProfit',profit);set('sGrand',grand);
        const sr=document.getElementById('sRows');if(sr)sr.textContent=pkg.pack_price.length;
        const sl=document.getElementById('sGrandL');if(sl)sl.textContent=`BDT combined · ${pax} pax · ${pax>0?fmt(grand/pax):'-'}/pax`;
        pkg.overall_price=grand;
    }


    // ═══ STEP 6 — AI & Cover ═══
    function s6(el){
        if(!Array.isArray(pkg.highlights))pkg.highlights=[];
        el.innerHTML=`
        <h2 class="text-lg font-bold text-[#1A2039] mb-5">AI & Cover</h2>
        <div class="grid grid-cols-2 gap-5 mb-5">
            <div>
                <label class="pb-lbl mb-2 block">Cover Image</label>
                <div class="border-2 border-dashed border-gray-300 rounded-xl p-4 text-center hover:border-[#50BC81] transition mb-2 cursor-pointer"
                    ondragover="event.preventDefault()" ondrop="PkgBuilder._drop(event)"
                    onclick="document.getElementById('cvFile').click()">
                    <i class="fa-solid fa-cloud-upload-alt text-gray-300 text-2xl mb-1 block"></i>
                    <p class="text-xs text-gray-400">Click to select file or drag & drop</p>
                    <p class="text-xs text-gray-300 mt-0.5">JPG, PNG, WEBP</p>
                </div>
                <input id="cvFile" type="file" accept="image/*" class="hidden" onchange="PkgBuilder._fileIn(this)">
                <div class="flex gap-2 mb-2">
                    <input id="s6Cv" class="pb-inp text-sm flex-1" placeholder="or paste image URL…" value="${esc(pkg.cover_image||'')}" oninput="PkgBuilder._prvw(this.value)">
                    <button id="bGenImg" onclick="PkgBuilder._genImg()"
                        class="px-3 py-2 rounded-xl text-xs font-semibold bg-gradient-to-r from-purple-500 to-pink-500 text-white hover:from-purple-600 hover:to-pink-600 transition flex-shrink-0 flex items-center gap-1.5">
                        <i class="fa-solid fa-wand-magic-sparkles"></i>AI Image
                    </button>
                </div>
                <div id="genPrv" class="hidden mb-3 bg-gray-50 border border-gray-200 rounded-xl p-3">
                    <p class="text-[10px] text-gray-400 mb-2">AI Generated Preview:</p>
                    <img id="genImg" class="w-full h-28 object-cover rounded-lg mb-2">
                    <div class="flex gap-2">
                        <button id="sGenImg" onclick="PkgBuilder._useGenImg()" class="flex-1 py-1.5 rounded-lg text-xs font-semibold bg-[#50BC81] text-white hover:bg-[#3da868] transition">✓ Use This</button>
                        <button onclick="PkgBuilder._genImg()" class="flex-1 py-1.5 rounded-lg text-xs font-semibold bg-gray-100 text-gray-600 hover:bg-gray-200 transition">↺ Regenerate</button>
                    </div>
                </div>
                <div id="cvPrv" class="${pkg.cover_image?'':'hidden'}">
                    <img id="cvImg" src="${esc(pkg.cover_image||'')}" class="w-full h-36 object-cover rounded-xl border border-gray-200" onerror="this.parentElement.classList.add('hidden')">
                </div>
                <div class="mt-4"><label class="pb-lbl mb-2 block">Package Rating</label>
                    <div class="flex gap-1" id="starRow">${[1,2,3,4,5].map(n=>`<button data-star="${n}" onclick="PkgBuilder._star(${n})" class="text-2xl leading-none transition hover:scale-110">${n<=(pkg.rating||0)?'⭐':'☆'}</button>`).join('')}</div>
                </div>
            </div>
            <div class="space-y-3">
                <p class="pb-lbl">AI Content Generator</p>
                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-3 bg-gray-50">
                        <div><p class="text-xs font-semibold text-[#1A2039]"><i class="fa-solid fa-pen-fancy mr-2 text-purple-500"></i>Package Description</p><p class="text-[10px] text-gray-400">Full marketing description</p></div>
                        <button id="bGD" onclick="PkgBuilder._gDesc()" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-purple-50 text-purple-700 hover:bg-purple-100 transition"><i class="fa-solid fa-wand-magic-sparkles mr-1"></i>Generate</button>
                    </div>
                    <textarea id="s6Ds" class="w-full px-4 py-3 text-xs resize-none border-none outline-none" rows="4" placeholder="Click Generate or write manually…">${esc(pkg.full_description||pkg.description||'')}</textarea>
                </div>
                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-3 bg-gray-50">
                        <div><p class="text-xs font-semibold text-[#1A2039]"><i class="fa-solid fa-star mr-2 text-yellow-500"></i>Highlights</p><p class="text-[10px] text-gray-400">Key selling points</p></div>
                        <button id="bGH" onclick="PkgBuilder._gHL()" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-yellow-50 text-yellow-700 hover:bg-yellow-100 transition"><i class="fa-solid fa-wand-magic-sparkles mr-1"></i>Generate</button>
                    </div>
                    <div class="px-4 py-3 space-y-1.5 min-h-12" id="hlL">${rHL()}</div>
                    <div class="flex gap-2 px-4 pb-3">
                        <input id="hlI" class="pb-inp text-xs flex-1" placeholder="Add highlight…" onkeydown="if(event.key==='Enter'){event.preventDefault();PkgBuilder._addHL();}">
                        <button onclick="PkgBuilder._addHL()" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition">Add</button>
                    </div>
                </div>
                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-3 bg-gray-50">
                        <div><p class="text-xs font-semibold text-[#1A2039]"><i class="fa-solid fa-list-check mr-2 text-green-500"></i>Inclusion Drafter</p><p class="text-[10px] text-gray-400">AI drafts inclusions from itinerary</p></div>
                        <button id="bGI" onclick="PkgBuilder._gInc()" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-green-50 text-green-700 hover:bg-green-100 transition"><i class="fa-solid fa-wand-magic-sparkles mr-1"></i>Draft</button>
                    </div>
                    <p class="text-[10px] text-gray-400 px-4 pb-3">Adds suggested inclusions to Step 4.</p>
                </div>
            </div>
        </div>
        <div class="border-t border-gray-100 pt-5">
            <label class="pb-lbl mb-2 block"><i class="fa-solid fa-lock text-gray-400 mr-1.5 text-xs"></i>Internal Notes <span class="text-gray-400 font-normal text-xs">(private)</span></label>
            <textarea id="s6Nt" class="pb-inp resize-none" rows="3" placeholder="Internal remarks for your team…">${esc(pkg.notes||'')}</textarea>
        </div>`;
    }

    function rHL(){
        return (pkg.highlights||[]).map((h,i)=>`
            <div class="flex items-center gap-2 px-3 py-1.5 bg-yellow-50 rounded-lg border border-yellow-100 text-xs">
                <i class="fa-solid fa-star text-yellow-500 text-[10px] flex-shrink-0"></i>
                <span class="flex-1">${esc(h)}</span>
                <button onclick="PkgBuilder._rmHL(${i})" class="text-red-300 hover:text-red-500"><i class="fa-solid fa-times"></i></button>
            </div>`).join('')||'<p class="text-xs text-gray-300 italic">No highlights yet</p>';
    }

    async function genDesc(){
        setBusy('bGD',true);
        const ctx=[pkg.title,`${pkg.duration||'?'}N`,pkg.package_type,pkg.countries.map(c=>c.name).join(' & '),pkg.hotels.slice(0,2).map(h=>h.hotel_name).join(', '),pkg.pack_itenaries.flatMap(d=>d.items.filter(i=>i.type==='activity').map(i=>i.name)).slice(0,5).join(', ')].filter(Boolean).join(' | ');
        const res=await thApi(`${API_BASE}api/ai/decompose-day.php`,'POST',{day_text:`Write 2-paragraph marketing description (plain text, no markdown): ${ctx}`,day_number:0,context:'Return text in JSON title field. Keep components empty.'});
        setBusy('bGD',false);
        let desc='';
        if(res.success&&res.data?.title&&res.data.title.length>40)desc=res.data.title;
        else desc=`Discover ${pkg.countries.map(c=>c.name).join(' and ')} with our ${pkg.package_type} package — ${pkg.title}. This ${pkg.duration||'?'}-night journey features handpicked accommodations and immersive experiences.`;
        thSetVal('s6Ds',desc);pkg.full_description=desc;thToast('Description generated');
    }

    async function genHL(){
        setBusy('bGH',true);
        const acts=pkg.pack_itenaries.flatMap(d=>d.items.filter(i=>i.type==='activity').map(i=>i.name)).slice(0,8);
        const res=await thApi(`${API_BASE}api/ai/decompose-day.php`,'POST',{day_text:`List 5-7 highlight points. Activities: ${acts.join(', ')}. Hotels: ${pkg.hotels.map(h=>h.hotel_name).join(', ')}. Destinations: ${pkg.countries.map(c=>c.name).join(', ')}. Return JSON {"highlights":["point1","point2"...]}`,day_number:0,context:'Return ONLY JSON with highlights array of short strings.'});
        setBusy('bGH',false);
        let items=[];try{items=res.data?.highlights||[];}catch(e){}
        if(!items.length)items=acts.slice(0,5);
        if(!Array.isArray(pkg.highlights))pkg.highlights=[];
        pkg.highlights=[...new Set([...pkg.highlights,...items])];
        const el=document.getElementById('hlL');if(el)el.innerHTML=rHL();
        thToast(`${items.length} highlight(s) generated`);
    }

    async function genInc(){
        setBusy('bGI',true);
        const acts=pkg.pack_itenaries.flatMap(d=>d.items.filter(i=>i.type==='activity').map(i=>i.name)).slice(0,6);
        const htls=pkg.hotels.map(h=>`${h.hotel_name} (${MPLANS[h.meal_plan]||h.meal_plan})`).slice(0,3);
        const res=await thApi(`${API_BASE}api/ai/decompose-day.php`,'POST',{day_text:`Generate inclusion list. Hotels: ${htls.join(', ')}. Activities: ${acts.join(', ')}. Return JSON {"inclusions":["item1","item2"...]}`,day_number:0,context:'Return ONLY JSON {"inclusions":[...]}'});
        setBusy('bGI',false);
        let items=[];try{items=res.data?.inclusions||[];}catch(e){}
        if(!items.length)items=['Hotel accommodation','Airport transfer','Breakfast'];
        const newItems=items.filter(i=>!pkg.pack_inclusions.includes(i));
        pkg.pack_inclusions.push(...newItems);
        thToast(`${newItems.length} inclusion(s) drafted — review in Step 4`);
    }

    function c6(){ pkg.full_description=thVal('s6Ds');pkg.description=pkg.full_description;pkg.notes=thVal('s6Nt');pkg.cover_image=thVal('s6Cv'); }

    // ═══ STEP 7 — Review ═══
    function s7(el){
        const rate=parseFloat(pkg.pricing_config?.exchange_rate)||1;
        const grand=pkg.pack_price.reduce((s,i)=>s+(i.sell_price||0)*(i.type==='hotel'?(i.nights||1)*(i.rooms||1):(i.qty||1)),0)*rate;
        const cost=pkg.pack_price.reduce((s,i)=>s+(i.net_cost||0)*(i.type==='hotel'?(i.nights||1)*(i.rooms||1):(i.qty||1)),0)*rate;
        const pax=(pkg.adults||1)+(pkg.children||0);
        el.innerHTML=`
        <h2 class="text-lg font-bold text-[#1A2039] mb-5">Review & Finalize</h2>
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="bg-gray-50 rounded-xl p-4 text-sm space-y-1.5">
                <p class="text-xs font-bold text-gray-400 uppercase mb-2">Basic Info</p>
                <div><span class="text-gray-400">Title:</span> <span class="font-semibold">${esc(pkg.title)}</span></div>
                <div><span class="text-gray-400">Type:</span> <span class="capitalize">${pkg.package_type}</span></div>
                <div><span class="text-gray-400">Duration:</span> ${pkg.duration||'?'} nights</div>
                <div><span class="text-gray-400">Dates:</span> ${pkg.start_date||'—'} → ${pkg.end_date||'—'}</div>
                <div><span class="text-gray-400">Pax:</span> ${pkg.adults}A ${pkg.children}C ${pkg.infants}I</div>
            </div>
            <div class="bg-[#50BC81]/5 border border-[#50BC81]/20 rounded-xl p-4 text-sm space-y-1.5">
                <p class="text-xs font-bold text-gray-400 uppercase mb-2">Pricing (BDT)</p>
                <div><span class="text-gray-400">Grand Total:</span> <span class="font-bold text-[#1A2039]">${fmt(grand)}</span></div>
                <div><span class="text-gray-400">Per Person:</span> <span class="font-semibold">${pax>0?fmt(grand/pax):'—'}</span></div>
                <div><span class="text-gray-400">Gross Profit:</span> <span class="font-semibold" style="color:${grand-cost>=0?'#50BC81':'#EF4444'}">${fmt(grand-cost)}</span></div>
                <div><span class="text-gray-400">Rate:</span> 1 local = ${pkg.pricing_config?.exchange_rate||1} BDT</div>
            </div>
        </div>
        <div class="bg-gray-50 rounded-xl p-4 mb-4">
            <p class="text-xs font-bold text-gray-400 uppercase mb-2">Destinations & Hotels</p>
            <div class="flex flex-wrap gap-2 mb-2">${pkg.countries.map(c=>`<span class="px-3 py-1 rounded-full bg-white border border-gray-200 text-xs font-medium">${esc(c.name)}</span>`).join('')||'<span class="text-xs text-gray-300">None</span>'}</div>
            ${pkg.hotels.length?`<div class="space-y-0.5">${pkg.hotels.map(h=>`<div class="text-xs text-gray-600">• ${esc(h.hotel_name)} — ${esc(h.city_name)} · ${h.nights}N · ${esc(MPLANS[h.meal_plan]||h.meal_plan)}</div>`).join('')}</div>`:''}
        </div>
        <div class="bg-gray-50 rounded-xl p-4 mb-5">
            <p class="text-xs font-bold text-gray-400 uppercase mb-2">Itinerary (${pkg.pack_itenaries?.length||0} days)</p>
            <div class="space-y-0.5 max-h-32 overflow-y-auto">
                ${(pkg.pack_itenaries||[]).map(d=>`<div class="flex gap-3 text-xs"><span class="font-semibold text-[#1A2039] w-12 flex-shrink-0">Day ${d.day_number}</span><span class="text-gray-600 truncate">${esc(d.title||'')} <span class="text-gray-400">(${d.items?.length||0} items)</span></span></div>`).join('')||'<span class="text-xs text-gray-300">No itinerary</span>'}
            </div>
        </div>
        <div class="border-t border-gray-100 pt-5">
            <p class="text-xs font-bold text-gray-400 uppercase mb-3">Final Actions</p>
            <div class="flex flex-wrap gap-3">
                <button onclick="PkgBuilder._fin('draft')" class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold border border-gray-200 text-gray-600 hover:bg-gray-50 transition"><i class="fa-regular fa-floppy-disk"></i>Save Draft</button>
                <button onclick="PkgBuilder._fin('publish')" class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold bg-[#50BC81] hover:bg-[#3da868] text-white transition"><i class="fa-solid fa-paper-plane"></i>Publish</button>
                <button onclick="PkgBuilder._fin('quote')" class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold bg-[#1A2039] hover:bg-[#252d4a] text-white transition"><i class="fa-solid fa-file-invoice-dollar"></i>Generate Quotation</button>
                ${pkg.sys_id?`<button onclick="PkgBuilder._clone()" class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold border border-gray-200 text-gray-600 hover:bg-gray-50 transition"><i class="fa-solid fa-copy"></i>Clone</button>`:''}
            </div>
        </div>`;
    }


    // ═══ Save / Navigation ═══
    async function save(cs=null){
        const payload={sys_id:pkg.sys_id||undefined,title:pkg.title,package_type:pkg.package_type,description:pkg.description,full_description:pkg.full_description,
            start_date:pkg.start_date,end_date:pkg.end_date,duration:pkg.duration,adults:pkg.adults,children:pkg.children,infants:pkg.infants,
            sell_currency_code:pkg.sell_currency_code,client_sys_id:pkg.client_sys_id,client_name:pkg.client_name,
            countries:pkg.countries,cities:pkg.countries.flatMap(c=>c.cities||[]),hotels:pkg.hotels,
            pack_itenaries:pkg.pack_itenaries,pack_price:pkg.pack_price,
            pack_inclusions:pkg.pack_inclusions,pack_exclusions:pkg.pack_exclusions,
            pack_components:pkg.pack_components||[],
            pricing_config:pkg.pricing_config,cover_image:pkg.cover_image,rating:pkg.rating,notes:pkg.notes,
            highlights:pkg.highlights,overall_price:pkg.overall_price,
            progress_step:step,completion_status:cs||pkg.completion_status||'draft'};
        if(!pkg.sys_id){
            const r=await thApi(`${API_BASE}api/packages/create.php`,'POST',payload);
            if(!r.success){thToast(r.message||'Save failed','error');return false;}
            pkg.sys_id=r.sys_id;history.replaceState(null,'',`?sys_id=${pkg.sys_id}`);
            const b=document.getElementById('bID');if(b)b.textContent=pkg.sys_id;
        } else {
            payload.sys_id=pkg.sys_id;
            const r=await thApi(`${API_BASE}api/packages/save.php`,'POST',payload);
            if(!r.success){thToast(r.message||'Save failed','error');return false;}
        }
        return true;
    }

    async function next(){
        let ok=true;
        if(step===1)ok=c1();
        else if(step===2)ok=c2();
        else if(step===6)c6();
        if(!ok)return;
        setBusy('bNxt',true);
        const saved=await save(step===7?'saved':null);
        setBusy('bNxt',false);
        if(!saved)return;
        thToast('Saved ✓');
        if(step<7)go(step+1);else nav();
    }

    async function draft(){
        if(step===1)c1();if(step===2)c2();if(step===6)c6();
        setBusy('bDft',true);const ok=await save('draft');setBusy('bDft',false);
        if(ok)thToast('Draft saved');
    }

    async function _fin(action){
        if(action==='quote'){
            if(!pkg.sys_id){thToast('Save the package first','error');return;}
            const r=await thApi(`${API_BASE}api/quotes/generate.php`,'POST',{package_sys_id:pkg.sys_id,markup_type:'percent',markup_value:0});
            if(r.success)thToast(`Quote v${r.version} — BDT ${fmt(r.grand_total)}`);
            else thToast(r.message||'Quote failed','error');return;
        }
        const ok=await save(action==='publish'?'saved':'draft');
        if(ok){thToast(action==='publish'?'Published!':'Draft saved!');if(action==='publish')setTimeout(()=>window.location.href='packages.php',1200);}
    }

    async function _clone(){
        if(!pkg.sys_id)return;
        const r=await thApi(`${API_BASE}api/packages/create.php`,'POST',{...pkg,sys_id:undefined,title:pkg.title+' (Copy)',completion_status:'draft',progress_step:1,booking_ref:''});
        if(r.success){thToast('Cloned!');window.location.href=`create-package.php?sys_id=${r.sys_id}`;}
        else thToast(r.message||'Clone failed','error');
    }

    // ═══ CSS ═══
    function css(){
        if(document.getElementById('pbCSS'))return;
        const s=document.createElement('style');s.id='pbCSS';
        s.textContent=`.pb-lbl{display:block;font-size:.75rem;color:#9CA3AF;margin-bottom:.25rem;font-weight:500}.pb-inp{width:100%;padding:.5rem .75rem;font-size:.875rem;border:1px solid #E5E7EB;border-radius:.75rem;outline:none;background:#fff;transition:border-color .15s,box-shadow .15s;color:#1A2039}.pb-inp:focus{border-color:#50BC81;box-shadow:0 0 0 3px rgba(80,188,129,.15)}textarea.pb-inp{resize:vertical}`;
        document.head.appendChild(s);
    }

    // ═══ Public API ═══
    return {
        init,
        _go:(n)=>go(n),
        _fin, _clone,
        // Step 2
        _rmC:(i)=>{pkg.countries.splice(i,1);rCntList();},
        _eH:(i)=>openHtl(i),
        _rmH:(i)=>{pkg.hotels.splice(i,1);rHtlList();},
        _hCity:(sid,name,ccy)=>{hd.city_sys_id=sid;hd.city_name=name;hd.currency_code=hd.currency_code||ccy;htlStep('city');},
        _hHotel:(sid,name)=>{hd.hotel_sys_id=sid;hd.hotel_name=name;htlStep('hotel');},
        _hRoom:async(sid,name)=>{hd.room_type_sys_id=sid;hd.room_type_name=name;await loadRates(sid);},
        _hRate:(sid,meal,cost,ccy)=>{hd.rate_sys_id=sid;hd.meal_plan=meal;hd.net_per_night=parseFloat(cost)||0;hd.net_cost=parseFloat(cost)||0;hd.currency_code=ccy;htlStep('room');},
        _hBack:(s)=>htlStep(s),
        // Step 3
        _iM:(m)=>{iMode=m;s3(document.getElementById('sC'));},
        _tog:(di)=>{const b=document.getElementById(`dB${di}`),c=document.getElementById(`chev${di}`);if(!b)return;const h=b.style.display==='none';b.style.display=h?'':'none';if(c)c.style.transform=h?'':'rotate(-90deg)';},
        _df:(di,k,v)=>{if(pkg.pack_itenaries[di])pkg.pack_itenaries[di][k]=v;},
        _rmD:(di)=>{pkg.pack_itenaries.splice(di,1);pkg.pack_itenaries.forEach((d,i)=>d.day_number=i+1);renderDays();},
        _openI:(di,ii)=>openItem(di,ii),
        _iT:(t)=>{id_draft.type=t;rItemModal();},
        _aS:()=>{const net=parseFloat(thVal('iNc'))||0;const pct=parseFloat(thVal('iPct'))||(id_draft.type==='activity'?(pkg.pricing_config.activity_profit||15):(pkg.pricing_config.transport_profit||10));const sell=net>0?parseFloat((net/(1-pct/100)).toFixed(2)):0;thSetVal('iSp',sell);},
        _rmI:(di,ii)=>{
            pkg.pack_itenaries[di].items.splice(ii,1);
            cascadeTimes(di, Math.max(0,ii-1));
            const el=document.getElementById('iL'+di);
            if(el){
                const savedSuggs=el._suggs;
                const panel=document.getElementById(`suggPanel${di}`);
                el.innerHTML=rItems(di);
                el._suggs=savedSuggs;
                if(panel&&savedSuggs?.length)el.insertAdjacentElement('afterbegin',panel);
            }
        },
        _swapI:(di,ii,dir)=>{
            const arr=pkg.pack_itenaries[di].items;
            const ni=ii+dir;
            if(ni<0||ni>=arr.length)return;
            [arr[ii],arr[ni]]=[arr[ni],arr[ii]];
            // After swap, recalculate from whichever index is earlier
            cascadeTimes(di, Math.min(ii,ni));
            const el=document.getElementById('iL'+di);
            if(el){
                const savedSuggs=el._suggs;
                const panel=document.getElementById(`suggPanel${di}`);
                el.innerHTML=rItems(di);
                el._suggs=savedSuggs;
                if(panel&&savedSuggs?.length)el.insertAdjacentElement('afterbegin',panel);
            }
        },
        _setTime:(di,ii,field,val)=>{
            const item=pkg.pack_itenaries[di]?.items[ii];
            if(!item) return;
            if(field==='start'){
                item.start_time=val;
                // Recalculate this item's end_time + cascade forward
                cascadeTimes(di, ii);
            }
            // Re-render items list preserving sugg panel
            const el=document.getElementById('iL'+di);
            if(el){
                const savedSuggs=el._suggs;
                const panel=document.getElementById(`suggPanel${di}`);
                el.innerHTML=rItems(di);
                el._suggs=savedSuggs;
                if(panel&&savedSuggs?.length)el.insertAdjacentElement('afterbegin',panel);
            }
        },
        _pickT:(di,t)=>pickType(di,t),
        _filterCat:(q)=>filterCat(q),
        _selCat:(sid)=>selCatItem(sid),
        // Flight modal
        _fTab:(tab)=>rFlightModal(tab),
        _fAddSeg:()=>{ fd.segments.push({flight:'',class:'',date:'',route:'',dep:'',arr:'',arr_offset:0}); renderFlightSegs(); },
        _fRmSeg:(i)=>{ fd.segments.splice(i,1); renderFlightSegs(); },
        _fSeg:(i,k,v)=>{ if(fd.segments[i]) fd.segments[i][k]=v; },
        _fAddFare:()=>{ fd.fares.push({type:'ADT',pax:1,base_fare:0,taxes:0,gross_fare:0,iata_charge:0,net_fare:0,payable:0,payable_edited:false}); renderFlightFares(); },
        _fRmFare:(i)=>{ fd.fares.splice(i,1); renderFlightFares(); },
        _fFare:(i,k,v,isNum)=>{
            if(!fd.fares[i])return;
            fd.fares[i][k]=isNum?parseFloat(v)||0:v;
            renderFlightFares();
        },
        _fExtractGds: async ()=>{
            const raw=thVal('fGdsRaw'); if(!raw){thToast('Paste GDS text first','warning');return;}
            setBusy('bFExtract',true);
            try{
                const res=await thApi(`${API_BASE}api/ticket-calculation/extract-gds.php`,'POST',{raw_gds:raw});
                if(!res.success){thToast(res.message||'GDS extraction failed','error');return;}
                const d=res.data||{};
                fd.airline=d.airline||fd.airline;
                fd.raw_gds=raw;
                if(Array.isArray(d.segments)&&d.segments.length){
                    fd.segments=d.segments.map(s=>({
                        flight:s.flight||'',class:s.class||'',date:s.date||'',
                        route:s.route||'',dep:s.departure||'',arr:s.arrival||'',arr_offset:0
                    }));
                }
                if(Array.isArray(d.fares)&&d.fares.length){
                    fd.fares=d.fares.map(f=>({
                        type:f.type||'ADT',pax:parseInt(f.pax||1),
                        base_fare:parseFloat(f.base_fare||0),taxes:parseFloat(f.taxes||0),
                        gross_fare:parseFloat(f.gross_fare||0),iata_charge:parseFloat(f.iata_charge||0),
                        net_fare:0,payable:0,payable_edited:false
                    }));
                }
                thToast('GDS extracted ✓');
                rFlightModal('manual'); // Switch to manual tab to review
            }catch(e){thToast('Error: '+e.message,'error');}
            setBusy('bFExtract',false);
        },
        _fGenGds: async ()=>{
            setBusy('bFGenGds',true);
            try{
                const res=await thApi(`${API_BASE}api/ai/flight-to-gds.php`,'POST',{
                    airline:fd.airline, segments:fd.segments, fares:fd.fares, currency_code:fd.currency_code
                });
                if(res.success){
                    fd.raw_gds=res.gds_text||'';
                    const el=document.getElementById('fGdsOut');
                    if(el) el.textContent=res.gds_text||'';
                    thToast('GDS generated ✓');
                } else thToast(res.message||'Failed','error');
            }catch(e){thToast('Error: '+e.message,'error');}
            setBusy('bFGenGds',false);
        },
        _selV:(vid,ccy,net,sell,child,basis,vcls)=>{
            id_draft.variant_sys_id=vid;id_draft.currency_code=ccy;
            id_draft.net_cost=net;id_draft.sell_price=sell;
            id_draft.child_price=child||null;id_draft.price_basis=basis;
            if(vcls)id_draft.vehicle_class=vcls;
            rItemModal();
        },
        _ext:(di)=>extractDay(di),
        _sug:(di)=>suggestDay(di),
        _accS:(di,si)=>acceptSugg(di,si),
        // Step 4
        _addInc:()=>{const v=thVal('incI');if(!v)return;pkg.pack_inclusions.push(v);thSetVal('incI','');rIncExc();},
        _rmInc:(i)=>{pkg.pack_inclusions.splice(i,1);rIncExc();},
        _addExc:()=>{const v=thVal('excI');if(!v)return;pkg.pack_exclusions.push(v);thSetVal('excI','');rIncExc();},
        _rmExc:(i)=>{pkg.pack_exclusions.splice(i,1);rIncExc();},
        _srchComp:(q)=>srchComp(q),
        _pickComp:(sid,name,cat)=>pickComp(sid,name,cat),
        _rmComp:(i)=>{pkg.pack_components.splice(i,1);rCompList();srchComp(thVal('compSrch')||'');},
        _compQty:(i,v)=>{if(pkg.pack_components[i])pkg.pack_components[i].qty=v;},
        // Step 5
        _pcC:(sel)=>{const opt=sel.options[sel.selectedIndex];pkg.pricing_config.exchange_country_sys_id=sel.value;if(opt.dataset.r){const r=parseFloat(opt.dataset.r)||1;pkg.pricing_config.exchange_rate=r;thSetVal('pcR',r);}recalc();renderPriceRows();},
        _pcR:(v)=>{pkg.pricing_config.exchange_rate=parseFloat(v)||1;recalc();},
        _apP:()=>{
            pkg.pricing_config.activity_profit=parseFloat(thVal('pcAP'))||15;
            pkg.pricing_config.hotel_profit=parseFloat(thVal('pcHP'))||12;
            pkg.pricing_config.transport_profit=parseFloat(thVal('pcTP'))||10;
            pkg.pricing_config.component_profit=parseFloat(thVal('pcCP'))||8;
            pkg.pack_price.forEach(item=>{
                const map={activity:pkg.pricing_config.activity_profit,hotel:pkg.pricing_config.hotel_profit,transport:pkg.pricing_config.transport_profit,component:pkg.pricing_config.component_profit};
                const prof=map[item.type]||15;item.profit_pct=prof;
                if(item.net_cost>0)item.sell_price=parseFloat((item.net_cost/(1-prof/100)).toFixed(2));
            });
            renderPriceRows();
        },
        _pi:(gi,k,v)=>{if(pkg.pack_price[gi])pkg.pack_price[gi][k]=v;},
        _aSI:(gi,type)=>{const item=pkg.pack_price[gi];if(!item)return;const map={activity:pkg.pricing_config.activity_profit||15,hotel:pkg.pricing_config.hotel_profit||12,transport:pkg.pricing_config.transport_profit||10,component:pkg.pricing_config.component_profit||8};const prof=item.profit_pct||map[type]||15;if(item.net_cost>0)item.sell_price=parseFloat((item.net_cost/(1-prof/100)).toFixed(2));},
        _rp:()=>renderPriceRows(),
        // Step 6
        _drop:(e)=>{e.preventDefault();const url=e.dataTransfer.getData('text/plain')||'';if(url){thSetVal('s6Cv',url);PkgBuilder._prvw(url);}},
        _fileIn:(inp)=>{
            const file=inp.files?.[0];if(!file)return;
            const reader=new FileReader();
            reader.onload=e=>{const url=e.target.result;thSetVal('s6Cv',url);PkgBuilder._prvw(url);};
            reader.readAsDataURL(file);
        },
        _genImg: async () => {
            setBusy('bGenImg', true);
            try {
                const prompt = `${pkg.title||'travel package'}, ${pkg.countries.map(c=>c.name).join(', ')}, beautiful travel photography, high quality, landscape`;
                const res = await thApi(`${API_BASE}api/ai/gen-image.php`, 'POST', { prompt });
                if (!res.success) {
                    thToast(res.message || 'AI image generation failed', 'error');
                    setBusy('bGenImg', false);
                    return;
                }
                // res.img = base64 data URL (for preview)
                // res.url = server file path (for saving)
                pkg.genImgUrl = res.url;  // string, not array
                const gImg = document.getElementById('genImg');
                const gPrv = document.getElementById('genPrv');
                if (gImg) { gImg.src = res.img; gImg._url = res.url; }
                if (gPrv) gPrv.classList.remove('hidden');
            } catch(e) {
                thToast('Generation error: ' + e.message, 'error');
            }
            setBusy('bGenImg', false);
        },

        _useGenImg: async () => {
            if (!pkg.sys_id) {
                thToast('Save the package first before setting cover image', 'warn');
                return;
            }
            const imgUrl = pkg.genImgUrl;
            if (!imgUrl) {
                thToast('No generated image found', 'error');
                return;
            }
            setBusy('bGenImg', true);
            try {
                const res = await thApi(`${API_BASE}api/packages/img-save.php`, 'POST', {
                    img_url:    imgUrl,
                    package_id: pkg.sys_id
                });
                if (res.success) {
                    pkg.cover_image = res.cover_image || `${API_BASE}uploads/packages/${pkg.sys_id}/cover_img.jpg`;
                    pkg.genImgUrl   = '';
                    // Update preview
                    PkgBuilder._prvw(pkg.cover_image);
                    document.getElementById('genPrv')?.classList.add('hidden');
                    thToast('Cover image saved ✓');
                } else {
                    thToast(res.message || 'Failed to save image', 'error');
                }
            } catch(e) {
                thToast('Error: ' + e.message, 'error');
            }
            setBusy('bGenImg', false);
        },
        _prvw:(url)=>{const p=document.getElementById('cvPrv'),img=document.getElementById('cvImg');if(!p||!img)return;if(url){img.src=url;p.classList.remove('hidden');}else p.classList.add('hidden');pkg.cover_image=url;},
        _star:(n)=>{pkg.rating=n;document.querySelectorAll('#starRow button').forEach(b=>b.textContent=parseInt(b.dataset.star)<=n?'⭐':'☆');},
        _addHL:()=>{const v=thVal('hlI');if(!v)return;if(!Array.isArray(pkg.highlights))pkg.highlights=[];pkg.highlights.push(v);thSetVal('hlI','');const el=document.getElementById('hlL');if(el)el.innerHTML=rHL();},
        _rmHL:(i)=>{pkg.highlights.splice(i,1);const el=document.getElementById('hlL');if(el)el.innerHTML=rHL();},
        _gDesc:()=>genDesc(),
        _gHL:()=>genHL(),
        _gInc:()=>genInc(),
        _sttToggle: () => {
            const panel = document.getElementById('sttPanel');
            if (!panel) return;
            const hidden = panel.classList.contains('hidden');
            panel.classList.toggle('hidden', !hidden);
            const btn = document.getElementById('sttToggle');
            if (btn) {
                btn.classList.toggle('border-[#50BC81]', hidden);
                btn.classList.toggle('text-[#50BC81]', hidden);
            }
        },
 
        _sttStart: () => {
            if (!('webkitSpeechRecognition' in window || 'SpeechRecognition' in window)) {
                thToast('Speech recognition not supported. Use Chrome.', 'error');
                return;
            }
            // init or reuse
            if (!window._sttRec) {
                const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
                window._sttRec = new SR();
                window._sttRec.continuous     = true;
                window._sttRec.interimResults  = true;
                window._sttRec.onresult = (e) => {
                    let interim = '';
                    for (let i = e.resultIndex; i < e.results.length; i++) {
                        if (e.results[i].isFinal) window._sttFinal += e.results[i][0].transcript + ' ';
                        else interim += e.results[i][0].transcript;
                    }
                    const prev = document.getElementById('sttPreview');
                    if (prev) prev.textContent = window._sttFinal + interim;
                };
                window._sttRec.onerror = (e) => {
                    if (e.error !== 'aborted') PkgBuilder._sttSetStatus('stopped', 'Error: ' + e.error);
                };
                window._sttRec.onend = () => {
                    if (window._sttRecording && !window._sttPaused) {
                        window._sttRec.lang = thVal('sttLang') || 'bn-BD';
                        window._sttRec.start();
                    }
                };
            }
            window._sttFinal    = '';
            window._sttRecording = true;
            window._sttPaused   = false;
            window._sttRec.lang = thVal('sttLang') || 'bn-BD';
            window._sttRec.start();
 
            const prev = document.getElementById('sttPreview');
            if (prev) prev.textContent = 'Listening…';
            document.getElementById('sttActions')?.classList.add('hidden');
            document.getElementById('sttStart')?.setAttribute('disabled', true);
            document.getElementById('sttPause')?.removeAttribute('disabled');
            document.getElementById('sttStop')?.removeAttribute('disabled');
            PkgBuilder._sttSetStatus('active', 'Recording…');
        },
 
        _sttPause: () => {
            if (!window._sttPaused) {
                window._sttPaused = true;
                window._sttRec?.stop();
                const btn = document.getElementById('sttPause');
                if (btn) btn.innerHTML = '▶ Resume';
                PkgBuilder._sttSetStatus('paused', 'Paused — tap Resume');
            } else {
                window._sttPaused   = false;
                window._sttRec.lang = thVal('sttLang') || 'bn-BD';
                window._sttRec?.start();
                const btn = document.getElementById('sttPause');
                if (btn) btn.innerHTML = '⏸ Pause';
                PkgBuilder._sttSetStatus('active', 'Recording…');
            }
        },
 
        _sttStop: () => {
            window._sttRecording = false;
            window._sttPaused   = false;
            window._sttRec?.stop();
 
            document.getElementById('sttStart')?.removeAttribute('disabled');
            document.getElementById('sttPause')?.setAttribute('disabled', true);
            document.getElementById('sttStop')?.setAttribute('disabled', true);
            const pauseBtn = document.getElementById('sttPause');
            if (pauseBtn) pauseBtn.innerHTML = '⏸ Pause';
 
            const hasText = (window._sttFinal || '').trim().length > 0;
            document.getElementById('sttActions')?.classList.toggle('hidden', !hasText);
            PkgBuilder._sttSetStatus('stopped', hasText ? 'Stopped — push or polish below' : 'Stopped');
        },
 
        _sttSetStatus: (state, text) => {
            const dot = document.getElementById('sttDot');
            const txt = document.getElementById('sttStatus');
            if (txt) txt.textContent = text;
            if (!dot) return;
            const map = {
                idle:    'bg-gray-300',
                active:  'bg-[#22c55e] animate-pulse',
                paused:  'bg-[#f59e0b]',
                stopped: 'bg-[#ef4444]',
            };
            dot.className = `w-2 h-2 rounded-full flex-shrink-0 ${map[state] || map.idle}`;
        },
 
        _sttPush: async (polish) => {
            const raw = (window._sttFinal || '').trim();
            if (!raw) { thToast('No transcript to push', 'warning'); return; }
 
            if (!polish) {
                // Raw push — replace textarea content
                thSetVal('t4', raw);
                pkg.full_description = raw;
                thToast('Transcript pushed ✓');
                return;
            }
 
            // Polish via api/ai/speech-polish.php
            const btn = document.getElementById('sttPolish');
            if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-xs mr-1"></i>Polishing…'; }
 
            try {
                const fd = new FormData();
                fd.append('raw_text', raw);
                const res  = await fetch(`${API_BASE}api/ai/speech-polish.php`, { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    thSetVal('t4', data.corrected_text);
                    pkg.full_description = data.corrected_text;
                    thToast('Polished & pushed ✓');
                } else {
                    thToast(data.error || 'Polish failed', 'error');
                }
            } catch (e) {
                thToast('Network error: ' + e.message, 'error');
            }
 
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles text-xs"></i> Polish & Push'; }
        
        },

        _genExNBuild: async () => {
            const desc = thVal('t4') || pkg.full_description || '';
            if (!desc.trim()) { thToast('Write or dictate a description first', 'warning'); return; }
            setBusy('exNBuild', true);
            try {
                const res = await thApi(`${API_BASE}api/ai/extract-n-build.php`, 'POST', { prompt: desc });
                console.log('[ExNBuild] API response:', JSON.stringify(res, null, 2));
                if (!res.success) {
                    thToast(res.message || 'Extraction failed', 'error');
                    setBusy('exNBuild', false);
                    return;
                }

                const f = res.fields || {};

                // ── Apply basic fields to pkg + DOM ─────────────────
                if (f.title)               { pkg.title = f.title;                     thSetVal('t1', f.title); }
                if (f.package_type)        { pkg.package_type = f.package_type;       const s=document.getElementById('t2'); if(s) s.value=f.package_type; }
                if (f.sell_currency_code)  { pkg.sell_currency_code = f.sell_currency_code; thSetVal('t3', f.sell_currency_code); }
                if (f.start_date)          { pkg.start_date = f.start_date;           thSetVal('t5', f.start_date); }
                if (f.end_date)            { pkg.end_date   = f.end_date;             thSetVal('t6', f.end_date); }
                if (f.duration > 0)        { pkg.duration   = f.duration;             thSetVal('t7', f.duration); }
                if (f.adults  > 0)         pkg.adults   = f.adults;
                if (f.children >= 0)       pkg.children = f.children;
                if (f.infants  >= 0)       pkg.infants  = f.infants;

                // ── Merge inclusions / exclusions / highlights ───────
                if (Array.isArray(f.inclusions) && f.inclusions.length) {
                    const existing = new Set(pkg.pack_inclusions);
                    f.inclusions.forEach(i => { if (!existing.has(i)) pkg.pack_inclusions.push(i); });
                }
                if (Array.isArray(f.exclusions) && f.exclusions.length) {
                    const existing = new Set(pkg.pack_exclusions);
                    f.exclusions.forEach(i => { if (!existing.has(i)) pkg.pack_exclusions.push(i); });
                }
                if (Array.isArray(f.highlights) && f.highlights.length) {
                    if (!Array.isArray(pkg.highlights)) pkg.highlights = [];
                    const existing = new Set(pkg.highlights);
                    f.highlights.forEach(h => { if (!existing.has(h)) pkg.highlights.push(h); });
                }

                // ── Matched countries: pre-populate pkg.countries ────
                if (Array.isArray(f.matched_countries) && f.matched_countries.length) {
                    const existSids = new Set(pkg.countries.map(c=>c.sys_id));
                    f.matched_countries.forEach(c => {
                        if (!existSids.has(c.sys_id)) {
                            pkg.countries.push({ sys_id:c.sys_id, name:c.name, currency_code:c.currency_code||'', default_rate:parseFloat(c.default_rate)||1, cities:[] });
                            existSids.add(c.sys_id);
                        }
                    });
                }

                // ── Store day suggestions + flight items ─────────────
                const days = res.days || [];
                if (days.length) {
                    while (pkg.pack_itenaries.length < days.length) newDay(pkg.pack_itenaries.length+1);
                    days.forEach((d, di) => {
                        if (!pkg.pack_itenaries[di]) return;
                        if (d.title)          pkg.pack_itenaries[di].title        = d.title;
                        if (d.city_name)      pkg.pack_itenaries[di].city_name    = d.city_name;
                        if (d.country_name)   pkg.pack_itenaries[di].country_name = d.country_name;
                        if (d.meal_breakfast) pkg.pack_itenaries[di].meal_breakfast = true;
                        if (d.meal_lunch)     pkg.pack_itenaries[di].meal_lunch   = true;
                        if (d.meal_dinner)    pkg.pack_itenaries[di].meal_dinner  = true;
                        // Inject flight items directly (not as suggestions)
                        if (Array.isArray(d.flights) && d.flights.length) {
                            d.flights.forEach(fl => {
                                const exists = pkg.pack_itenaries[di].items.find(it=>it.type==='flight'&&it.name===fl.name);
                                if (!exists) pkg.pack_itenaries[di].items.push(fl);
                            });
                        }
                    });
                    // Store activity/transport suggestions
                    pendingSuggs = {};
                    days.forEach((d, di) => {
                        const suggs = d.suggestions || [];
                        if (!suggs.length) return;
                        const existing = new Set(pkg.pack_itenaries[di]?.items.map(i=>i.name) || []);
                        const filtered = suggs.filter(s => s.name && !existing.has(s.name));
                        if (filtered.length) pendingSuggs[di] = filtered;
                    });
                    console.log('[ExNBuild] pendingSuggs:', JSON.stringify(pendingSuggs));
                    Object.keys(pendingSuggs).forEach(diStr => {
                        const di=parseInt(diStr), suggs=pendingSuggs[di];
                        const lel=document.getElementById(`iL${di}`);
                        if (!lel) return;
                        lel._suggs=[...suggs];
                        document.getElementById(`suggPanel${di}`)?.remove();
                        lel.insertAdjacentHTML('afterbegin', buildExtractPanel(di, suggs));
                    });
                }

                // ── Toast + live header title ─────────────────────────
                const nSuggs = Object.values(pendingSuggs).reduce((s,a)=>s+a.length, 0);
                thToast(`Extracted fields${nSuggs ? ` · ${nSuggs} day item(s) ready in Step 3` : ''} ✓`);
                const bTEl = document.getElementById('bT');
                if (bTEl && f.title) bTEl.textContent = f.title;

            } catch(e) {
                thToast('Error: ' + e.message, 'error');
            }
            setBusy('exNBuild', false);
        }
    };
})();