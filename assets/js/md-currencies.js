/**
 * TravHub — MdCurrencies  (assets/js/md-currencies.js)
 * Currencies + FX Rates (X→BDT only in Gen-3)
 */
const MdCurrencies = (() => {
    'use strict';
    const st = { page:1, limit:20, search:'', status:'active', tab:'currencies' };

    function init() { renderShell(); bindEvents(); load(); }

    function renderShell() {
        document.getElementById('mainContent').innerHTML = `
        <div class="px-6 py-6 max-w-screen-xl mx-auto">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div><h1 class="text-2xl font-bold text-[#1A2039]">Currencies & FX Rates</h1>
                    <p class="text-sm text-gray-400 mt-0.5">All rates are X→BDT (1 unit of currency = ? BDT)</p></div>
                <button id="btnAdd" class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold bg-[#1A2039] hover:bg-[#252d4a] text-white transition shadow-sm">
                    <i class="fa-solid fa-plus"></i> <span id="btnAddLabel">Add Currency</span>
                </button>
            </div>

            <!-- Sub-tabs -->
            <div class="flex gap-2 mb-5">
                <button data-subtab="currencies" class="subtab-btn px-5 py-2 rounded-xl text-sm font-semibold border transition bg-[#1A2039] text-white border-[#1A2039]">Currencies</button>
                <button data-subtab="fx" class="subtab-btn px-5 py-2 rounded-xl text-sm font-semibold border transition bg-white text-gray-600 border-gray-200 hover:bg-gray-50">FX Rates</button>
            </div>

            <div id="tabContent"></div>
            <div id="pgBox"></div>
        </div>

        <!-- Currency Modal -->
        <div id="cModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
          <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h2 id="cModalTitle" class="text-lg font-bold text-[#1A2039]">Add Currency</h2>
                <button onclick="thCloseModal('cModal')" class="text-gray-400 hover:text-gray-600 w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100"><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="px-6 py-5 space-y-4">
                <input type="hidden" id="cSysId">
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="th-label">Code (ISO 4217) <span class="text-red-500">*</span></label><input id="cCode" type="text" maxlength="5" placeholder="THB" class="th-input uppercase"></div>
                    <div><label class="th-label">Symbol</label><input id="cSymbol" type="text" maxlength="8" placeholder="฿" class="th-input"></div>
                </div>
                <div><label class="th-label">Name <span class="text-red-500">*</span></label><input id="cName" type="text" placeholder="Thai Baht" class="th-input"></div>
                <div><label class="th-label">Decimal Places</label><input id="cDecimals" type="number" min="0" max="4" value="2" class="th-input"></div>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3">
                <button onclick="thCloseModal('cModal')" class="px-5 py-2 rounded-xl text-sm font-medium border border-gray-200 text-gray-600 hover:bg-gray-50">Cancel</button>
                <button id="cSaveBtn" class="px-6 py-2 rounded-xl text-sm font-semibold bg-[#1A2039] hover:bg-[#252d4a] text-white transition shadow-sm">Save</button>
            </div>
          </div>
        </div>

        <!-- FX Modal -->
        <div id="fxModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
          <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h2 id="fxModalTitle" class="text-lg font-bold text-[#1A2039]">Add FX Rate</h2>
                <button onclick="thCloseModal('fxModal')" class="text-gray-400 hover:text-gray-600 w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100"><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="px-6 py-5 space-y-4">
                <input type="hidden" id="fxSysId">
                <div class="p-3 bg-blue-50 rounded-xl text-xs text-blue-700 font-medium">
                    <i class="fa-solid fa-info-circle mr-1"></i> Rate = 1 unit of currency = ? BDT (Gen-3: X→BDT only)
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="th-label">Currency Code <span class="text-red-500">*</span></label><input id="fxCode" type="text" maxlength="5" placeholder="THB" class="th-input uppercase"></div>
                    <div><label class="th-label">Rate (→BDT) <span class="text-red-500">*</span></label><input id="fxRate" type="number" step="0.00000001" placeholder="3.21" class="th-input"></div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="th-label">Buffer % (markup)</label><input id="fxBuffer" type="number" step="0.01" value="0" placeholder="2.5" class="th-input"></div>
                    <div><label class="th-label">Effective Date <span class="text-red-500">*</span></label><input id="fxDate" type="date" class="th-input"></div>
                </div>
                <div><label class="th-label">Source</label>
                    <select id="fxSource" class="th-input"><option value="manual">Manual</option><option value="api">API</option></select>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3">
                <button onclick="thCloseModal('fxModal')" class="px-5 py-2 rounded-xl text-sm font-medium border border-gray-200 text-gray-600 hover:bg-gray-50">Cancel</button>
                <button id="fxSaveBtn" class="px-6 py-2 rounded-xl text-sm font-semibold bg-[#1A2039] hover:bg-[#252d4a] text-white transition shadow-sm">Save Rate</button>
            </div>
          </div>
        </div>`;
        document.head.insertAdjacentHTML('beforeend',`<style>.th-label{display:block;font-size:.75rem;font-weight:600;color:#4b5563;margin-bottom:.375rem}.th-input{width:100%;padding:.5rem .75rem;border:1px solid #e5e7eb;border-radius:.75rem;font-size:.875rem;outline:none}.th-input:focus{border-color:#50BC81;box-shadow:0 0 0 2px rgba(80,188,129,.25)}</style>`);
    }

    function bindEvents() {
        document.getElementById('btnAdd').onclick = () => st.tab==='currencies' ? openCurrencyForm() : openFxForm();
        document.querySelectorAll('.subtab-btn').forEach(b => b.onclick = () => {
            st.tab = b.dataset.subtab; st.page=1;
            document.getElementById('btnAddLabel').textContent = st.tab==='currencies' ? 'Add Currency' : 'Add FX Rate';
            document.querySelectorAll('.subtab-btn').forEach(x => {
                const on = x.dataset.subtab===st.tab;
                x.className=`subtab-btn px-5 py-2 rounded-xl text-sm font-semibold border transition ${on?'bg-[#1A2039] text-white border-[#1A2039]':'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'}`;
            }); load();
        });
        document.getElementById('cSaveBtn').onclick  = saveCurrency;
        document.getElementById('fxSaveBtn').onclick = saveFx;
        // Set today's date as default for fx
        thSetVal('fxDate', new Date().toISOString().split('T')[0]);
    }

    async function load() {
        document.getElementById('tabContent').innerHTML = '<div class="text-center py-16 text-gray-300"><i class="fa-solid fa-spinner fa-spin text-3xl"></i></div>';
        if (st.tab === 'currencies') await loadCurrencies();
        else await loadFx();
    }

    async function loadCurrencies() {
        const data = await thApi(`${API_BASE}api/masterdata/currencies/list.php?status=${st.status}`);
        if (!data.success) { document.getElementById('tabContent').innerHTML = '<p class="text-red-500 text-sm p-4">Failed to load</p>'; return; }
        const rows = (data.data||[]).map(c => `
            <tr class="hover:bg-gray-50 transition">
                <td class="px-4 py-3"><span class="font-mono text-sm font-bold text-[#1A2039]">${c.currency_code}</span></td>
                <td class="px-4 py-3"><div class="font-medium text-gray-800">${c.name}</div></td>
                <td class="px-4 py-3 text-center text-lg">${c.symbol||'—'}</td>
                <td class="px-4 py-3 text-center text-sm text-gray-600">${c.decimal_places}</td>
                <td class="px-4 py-3">${thStatusBadge(c.status)}</td>
                <td class="px-4 py-3">
                    <div class="flex items-center justify-center gap-1">
                        <button onclick="MdCurrencies._editCurrency('${c.sys_id}')" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-blue-50 text-blue-500"><i class="fa-solid fa-pen text-xs"></i></button>
                        <button onclick="MdCurrencies._delCurrency('${c.sys_id}')" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-red-50 text-red-400"><i class="fa-solid fa-trash text-xs"></i></button>
                    </div>
                </td>
            </tr>`).join('');
        document.getElementById('tabContent').innerHTML = `
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-4">
                <table class="w-full text-sm">
                    <thead><tr class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wide border-b border-gray-200">
                        <th class="px-4 py-3 text-left w-24">Code</th>
                        <th class="px-4 py-3 text-left">Name</th>
                        <th class="px-4 py-3 text-center w-20">Symbol</th>
                        <th class="px-4 py-3 text-center w-24">Decimals</th>
                        <th class="px-4 py-3 text-left w-24">Status</th>
                        <th class="px-4 py-3 text-center w-24">Actions</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-50">${rows||'<tr><td colspan="6" class="text-center py-8 text-gray-300"><i class="fa-solid fa-money-bills text-5xl mb-3 block opacity-30"></i><p>No currencies</p></td></tr>'}</tbody>
                </table>
            </div>`;
    }

    async function loadFx() {
        const data = await thApi(`${API_BASE}api/masterdata/fx-rates/list.php`);
        if (!data.success) { document.getElementById('tabContent').innerHTML = '<p class="text-red-500 text-sm p-4">Failed to load</p>'; return; }
        const rows = (data.data||[]).map(r => `
            <tr class="hover:bg-gray-50 transition">
                <td class="px-4 py-3 font-mono font-bold text-[#1A2039] text-sm">${r.currency_code} → BDT</td>
                <td class="px-4 py-3 text-right font-mono text-sm">${Number(r.rate).toFixed(6)}</td>
                <td class="px-4 py-3 text-right text-sm text-gray-600">${r.buffer_pct}%</td>
                <td class="px-4 py-3 text-right font-mono text-sm text-[#50BC81] font-semibold">${Number(r.rate_with_buffer).toFixed(6)}</td>
                <td class="px-4 py-3 text-center text-sm text-gray-500">${r.effective_date}</td>
                <td class="px-4 py-3 text-center"><span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">${r.source}</span></td>
                <td class="px-4 py-3">
                    <div class="flex items-center justify-center gap-1">
                        <button onclick="MdCurrencies._editFx('${r.sys_id}')" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-blue-50 text-blue-500"><i class="fa-solid fa-pen text-xs"></i></button>
                        <button onclick="MdCurrencies._delFx('${r.sys_id}')" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-red-50 text-red-400"><i class="fa-solid fa-trash text-xs"></i></button>
                    </div>
                </td>
            </tr>`).join('');
        document.getElementById('tabContent').innerHTML = `
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-4">
                <table class="w-full text-sm">
                    <thead><tr class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wide border-b border-gray-200">
                        <th class="px-4 py-3 text-left">Pair</th>
                        <th class="px-4 py-3 text-right">Mid Rate</th>
                        <th class="px-4 py-3 text-right w-24">Buffer %</th>
                        <th class="px-4 py-3 text-right">Effective Rate</th>
                        <th class="px-4 py-3 text-center w-32">Date</th>
                        <th class="px-4 py-3 text-center w-20">Source</th>
                        <th class="px-4 py-3 text-center w-24">Actions</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-50">${rows||'<tr><td colspan="7" class="text-center py-8 text-gray-300">No FX rates</td></tr>'}</tbody>
                </table>
            </div>`;
    }

    // Currency CRUD
    function openCurrencyForm(sys_id=null) {
        ['cSysId','cCode','cSymbol','cName'].forEach(id=>thSetVal(id,''));
        thSetVal('cDecimals','2');
        document.getElementById('cModalTitle').textContent = sys_id ? 'Edit Currency' : 'Add Currency';
        if (sys_id) thSetVal('cSysId', sys_id);
        thOpenModal('cModal');
    }
    async function _editCurrency(sys_id) {
        const d = await thApi(`${API_BASE}api/masterdata/currencies/list.php`);
        const c = d.data?.find(x=>x.sys_id===sys_id);
        if (!c) return;
        thSetVal('cSysId',c.sys_id); thSetVal('cCode',c.currency_code);
        thSetVal('cSymbol',c.symbol); thSetVal('cName',c.name);
        thSetVal('cDecimals',c.decimal_places);
        document.getElementById('cModalTitle').textContent = 'Edit Currency';
        thOpenModal('cModal');
    }
    async function saveCurrency() {
        const body = { sys_id:thVal('cSysId')||undefined, currency_code:thVal('cCode').toUpperCase(), name:thVal('cName'), symbol:thVal('cSymbol'), decimal_places:parseInt(thVal('cDecimals')||2) };
        if (!body.currency_code||!body.name) return thToast('Code and Name required','error');
        document.getElementById('cSaveBtn').disabled=true;
        const res = await thApi(`${API_BASE}api/masterdata/currencies/save.php`,'POST',body);
        document.getElementById('cSaveBtn').disabled=false;
        if (!res.success) return thToast(res.message||'Error','error');
        thToast(res.message||'Saved!'); thCloseModal('cModal'); load();
    }
    async function _delCurrency(sys_id) {
        if (!thConfirm('Delete this currency?')) return;
        const res = await thApi(`${API_BASE}api/masterdata/currencies/delete.php`,'POST',{sys_id});
        thToast(res.message||'Deleted'); load();
    }

    // FX CRUD
    function openFxForm(sys_id=null) {
        ['fxSysId','fxCode','fxRate','fxBuffer'].forEach(id=>thSetVal(id,''));
        thSetVal('fxDate', new Date().toISOString().split('T')[0]);
        thSetVal('fxSource','manual');
        document.getElementById('fxModalTitle').textContent = sys_id ? 'Edit FX Rate' : 'Add FX Rate';
        thOpenModal('fxModal');
    }
    async function _editFx(sys_id) {
        const d = await thApi(`${API_BASE}api/masterdata/fx-rates/list.php`);
        const r = d.data?.find(x=>x.sys_id===sys_id);
        if (!r) return;
        thSetVal('fxSysId',r.sys_id); thSetVal('fxCode',r.currency_code);
        thSetVal('fxRate',r.rate); thSetVal('fxBuffer',r.buffer_pct);
        thSetVal('fxDate',r.effective_date); thSetVal('fxSource',r.source);
        document.getElementById('fxModalTitle').textContent = 'Edit FX Rate';
        thOpenModal('fxModal');
    }
    async function saveFx() {
        const body = { sys_id:thVal('fxSysId')||undefined, currency_code:thVal('fxCode').toUpperCase(), rate:parseFloat(thVal('fxRate')||0), buffer_pct:parseFloat(thVal('fxBuffer')||0), effective_date:thVal('fxDate'), source:thVal('fxSource') };
        if (!body.currency_code||!body.rate) return thToast('Currency code and rate required','error');
        document.getElementById('fxSaveBtn').disabled=true;
        const res = await thApi(`${API_BASE}api/masterdata/fx-rates/save.php`,'POST',body);
        document.getElementById('fxSaveBtn').disabled=false;
        if (!res.success) return thToast(res.message||'Error','error');
        thToast(res.message||'Saved!'); thCloseModal('fxModal'); load();
    }
    async function _delFx(sys_id) {
        if (!thConfirm('Delete this FX rate?')) return;
        const res = await thApi(`${API_BASE}api/masterdata/fx-rates/delete.php`,'POST',{sys_id});
        thToast(res.message||'Deleted'); load();
    }

    return { init, _editCurrency, _delCurrency, _editFx, _delFx };
})();
