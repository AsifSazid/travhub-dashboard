/**
 * TravHub — PkgList  (assets/js/pkg-list.js)
 * Package list page — Grid + List view toggle
 */
const PkgList = (() => {
    'use strict';

    function esc(s) {
        return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    const COMPLETION_LABELS = {
        draft:'Draft', saved:'Saved', quoted:'Quoted',
        confirmed:'Confirmed', in_progress:'In Progress',
        completed:'Completed', cancelled:'Cancelled',
    };

    const st = { page:1, limit:15, search:'', status:'active', completion:'', timer:null, view:'list' };

    function init() { renderShell(); bindEvents(); load(); }

    function renderShell() {
        document.getElementById('mainContent').innerHTML = `
        <div class="px-6 py-6 max-w-screen-xl mx-auto">

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-[#1A2039]">Packages</h1>
                    <p class="text-sm text-gray-400 mt-0.5">Travel package management</p>
                </div>
                <div class="flex items-center gap-2">
                    <!-- View toggle -->
                    <div class="flex border border-gray-200 rounded-xl overflow-hidden">
                        <button id="btnViewList" onclick="PkgList._view('list')"
                            class="px-3 py-2 text-sm transition bg-[#1A2039] text-white" title="List view">
                            <i class="fa-solid fa-list"></i>
                        </button>
                        <button id="btnViewGrid" onclick="PkgList._view('grid')"
                            class="px-3 py-2 text-sm transition bg-white text-gray-500 hover:bg-gray-50" title="Grid view">
                            <i class="fa-solid fa-grip"></i>
                        </button>
                    </div>
                    <button id="btnNew"
                        class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold bg-[#1A2039] hover:bg-[#252d4a] text-white transition shadow-sm">
                        <i class="fa-solid fa-plus"></i> New Package
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-5 flex flex-col sm:flex-row gap-3 flex-wrap">
                <div class="relative flex-1 min-w-48">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input id="srch" type="text" placeholder="Search package, ref, client…"
                        class="w-full pl-9 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#50BC81]">
                </div>
                <select id="fltCompletion"
                    class="text-sm px-3 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#50BC81] bg-white">
                    <option value="">All Statuses</option>
                    ${Object.entries(COMPLETION_LABELS).map(([v,l])=>`<option value="${v}">${l}</option>`).join('')}
                </select>
                <div class="flex gap-2">
                    ${['active','trash'].map(t => `
                    <button data-tab="${t}" class="tab-btn px-4 py-2 text-sm font-medium rounded-xl border transition
                        ${t==='active'?'bg-[#1A2039] text-white border-[#1A2039]':'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'}">
                        ${t==='active'?'Active':'<i class="fa-solid fa-trash mr-1"></i>Trash'}
                    </button>`).join('')}
                </div>
            </div>

            <!-- List view -->
            <div id="viewList" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-4">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wide border-b border-gray-200">
                            <th class="px-4 py-3 text-left">Package</th>
                            <th class="px-4 py-3 text-left w-28">Type</th>
                            <th class="px-4 py-3 text-left w-24">Duration</th>
                            <th class="px-4 py-3 text-left w-28">Pax</th>
                            <th class="px-4 py-3 text-left w-28">Status</th>
                            <th class="px-4 py-3 text-right w-32">Price</th>
                            <th class="px-4 py-3 text-center w-32">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tBody" class="divide-y divide-gray-50"></tbody>
                </table>
                <div id="tEmpty" class="hidden text-center py-16 text-gray-300">
                    <i class="fa-solid fa-box-open text-5xl mb-3 block opacity-30"></i>
                    <p class="text-sm font-medium">No packages found</p>
                </div>
                <div id="tLoading" class="text-center py-16 text-gray-300">
                    <i class="fa-solid fa-spinner fa-spin text-3xl"></i>
                </div>
            </div>

            <!-- Grid view -->
            <div id="viewGrid" class="hidden mb-4">
                <div id="gLoading" class="text-center py-16 text-gray-300">
                    <i class="fa-solid fa-spinner fa-spin text-3xl"></i>
                </div>
                <div id="gEmpty" class="hidden text-center py-16 text-gray-300">
                    <i class="fa-solid fa-box-open text-5xl mb-3 block opacity-30"></i>
                    <p class="text-sm font-medium">No packages found</p>
                </div>
                <div id="gBody" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5"></div>
            </div>

            <div id="pgBox"></div>
        </div>`;
    }

    function bindEvents() {
        document.getElementById('btnNew').onclick = () => {
            window.location.href = 'create-package.php';
        };

        document.getElementById('srch').oninput = e => {
            clearTimeout(st.timer);
            st.timer = setTimeout(() => { st.search = e.target.value; st.page = 1; load(); }, 350);
        };

        document.getElementById('fltCompletion').onchange = e => {
            st.completion = e.target.value; st.page = 1; load();
        };

        document.querySelectorAll('.tab-btn').forEach(b => b.onclick = () => {
            st.status = b.dataset.tab; st.page = 1;
            document.querySelectorAll('.tab-btn').forEach(x => {
                const on = x.dataset.tab === st.status;
                x.className = `tab-btn px-4 py-2 text-sm font-medium rounded-xl border transition
                    ${on?'bg-[#1A2039] text-white border-[#1A2039]':'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'}`;
            });
            load();
        });
    }

    function _view(v) {
        st.view = v;
        // Toggle buttons
        document.getElementById('btnViewList').className = `px-3 py-2 text-sm transition ${v==='list'?'bg-[#1A2039] text-white':'bg-white text-gray-500 hover:bg-gray-50'}`;
        document.getElementById('btnViewGrid').className = `px-3 py-2 text-sm transition ${v==='grid'?'bg-[#1A2039] text-white':'bg-white text-gray-500 hover:bg-gray-50'}`;
        // Toggle panels
        document.getElementById('viewList').classList.toggle('hidden', v !== 'list');
        document.getElementById('viewGrid').classList.toggle('hidden', v !== 'grid');
        load();
    }

    async function load() {
        // Show loaders
        if (st.view === 'list') {
            document.getElementById('tLoading').classList.remove('hidden');
            document.getElementById('tEmpty').classList.add('hidden');
            document.getElementById('tBody').innerHTML = '';
        } else {
            document.getElementById('gLoading').classList.remove('hidden');
            document.getElementById('gEmpty').classList.add('hidden');
            document.getElementById('gBody').innerHTML = '';
        }

        let url = `${API_BASE}api/packages/list.php?page=${st.page}&limit=${st.limit}&status=${st.status}`;
        if (st.search)     url += `&search=${encodeURIComponent(st.search)}`;
        if (st.completion) url += `&completion_status=${st.completion}`;

        const data = await thApi(url);

        // Hide loaders
        document.getElementById('tLoading').classList.add('hidden');
        document.getElementById('gLoading').classList.add('hidden');

        if (!data.success || !data.data?.length) {
            if (st.view === 'list') document.getElementById('tEmpty').classList.remove('hidden');
            else document.getElementById('gEmpty').classList.remove('hidden');
            return;
        }

        if (st.view === 'list') {
            document.getElementById('tBody').innerHTML = data.data.map(row => renderListRow(row)).join('');
        } else {
            document.getElementById('gBody').innerHTML = data.data.map(row => renderGridCard(row)).join('');
        }

        thPagination('pgBox', data.pagination, 'PkgList._page');
    }

    function renderListRow(row) {
        const badge = completionBadge(row.completion_status);
        const pax   = [row.adults>0?`${row.adults}A`:'', row.children>0?`${row.children}C`:'', row.infants>0?`${row.infants}I`:''].filter(Boolean).join(' ');
        const dates = row.start_date ? `${row.start_date}` : '—';
        const price = row.overall_price ? `${row.sell_currency_code} ${Number(row.overall_price).toLocaleString()}` : '—';

        return `<tr class="hover:bg-gray-50 transition">
            <td class="px-4 py-3">
                <div class="font-semibold text-[#1A2039] truncate max-w-xs">${esc(row.title)}</div>
                <div class="text-xs text-gray-400 mt-0.5">${esc(row.booking_ref||row.sys_id)} ${row.client_name?'· '+esc(row.client_name):''}</div>
            </td>
            <td class="px-4 py-3 text-xs text-gray-500 capitalize">${esc(row.package_type)}</td>
            <td class="px-4 py-3 text-xs text-gray-500">${row.duration?row.duration+' N':dates}</td>
            <td class="px-4 py-3 text-xs text-gray-500">${pax||'—'}</td>
            <td class="px-4 py-3"><span class="px-2.5 py-0.5 rounded-full text-xs font-medium ${badge.cls}">${badge.label}</span></td>
            <td class="px-4 py-3 text-right text-sm font-semibold text-[#1A2039]">${price}</td>
            <td class="px-4 py-3">
                <div class="flex items-center justify-center gap-1">
                    <button onclick="PkgList._edit('${row.sys_id}')" title="Edit"
                        class="p-1.5 rounded-lg hover:bg-blue-50 text-blue-500 transition">
                        <i class="fa-solid fa-pen text-xs"></i>
                    </button>
                    <button onclick="PkgList._delete('${row.sys_id}','${esc(row.title)}')" title="${st.status==='trash'?'Restore':'Delete'}"
                        class="p-1.5 rounded-lg hover:bg-red-50 text-red-400 transition">
                        <i class="fa-solid fa-${st.status==='trash'?'rotate-left':'trash'} text-xs"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    }

    function renderGridCard(row) {
        const badge = completionBadge(row.completion_status);
        const pax   = [row.adults>0?`${row.adults}A`:'', row.children>0?`${row.children}C`:'', row.infants>0?`${row.infants}I`:''].filter(Boolean).join(' ');
        const price = row.overall_price ? `${row.sell_currency_code} ${Number(row.overall_price).toLocaleString()}` : null;
        const stars = row.rating ? '⭐'.repeat(Math.min(parseInt(row.rating)||0, 5)) : '';
        const type  = (row.package_type||'').replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase());

        return `
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition flex flex-col">
            <!-- Cover / placeholder -->
            <div class="h-36 bg-gradient-to-br from-[#1A2039] to-[#2d3a5c] relative flex items-center justify-center overflow-hidden">
                ${row.cover_image
                    ? `<img src="${API_BASE}${esc(row.cover_image)}" class="w-full h-full object-cover absolute inset-0" onerror="this.style.display='none'">`
                    : ''}
                <i class="fa-solid fa-map-location-dot text-4xl text-white/20 relative z-10"></i>
                <!-- Status badge on image -->
                <span class="absolute top-2 right-2 z-10 px-2 py-0.5 rounded-full text-xs font-medium ${badge.cls}">${badge.label}</span>
            </div>

            <div class="p-4 flex flex-col flex-1">
                <!-- Title + rating -->
                <div class="flex items-start justify-between gap-2 mb-1">
                    <div class="font-semibold text-[#1A2039] leading-tight line-clamp-2 flex-1">${esc(row.title)}</div>
                    ${stars?`<span class="text-xs flex-shrink-0">${stars}</span>`:''}
                </div>
                <!-- Ref + type -->
                <div class="text-xs text-gray-400 mb-3">${esc(row.booking_ref||row.sys_id)} · ${type}</div>

                <!-- Meta chips -->
                <div class="flex flex-wrap gap-1.5 mb-3">
                    ${row.duration?`<span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600">${row.duration} nights</span>`:''}
                    ${pax?`<span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600">${pax}</span>`:''}
                    ${row.start_date?`<span class="px-2 py-0.5 rounded-full text-xs bg-blue-50 text-blue-600">${row.start_date}</span>`:''}
                </div>

                <!-- Price -->
                <div class="mt-auto flex items-center justify-between pt-3 border-t border-gray-100">
                    <div class="text-base font-bold text-[#1A2039]">${price||'—'}</div>
                    <div class="flex gap-1">
                        <button onclick="PkgList._edit('${row.sys_id}')"
                            class="p-1.5 rounded-lg hover:bg-blue-50 text-blue-500 transition" title="Edit">
                            <i class="fa-solid fa-pen text-xs"></i>
                        </button>
                        <button onclick="PkgList._delete('${row.sys_id}','${esc(row.title)}')"
                            class="p-1.5 rounded-lg hover:bg-red-50 text-red-400 transition" title="${st.status==='trash'?'Restore':'Delete'}">
                            <i class="fa-solid fa-${st.status==='trash'?'rotate-left':'trash'} text-xs"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>`;
    }

    function completionBadge(s) {
        const map = {
            draft:       { cls:'bg-gray-100 text-gray-600',      label:'Draft' },
            saved:       { cls:'bg-blue-50 text-blue-600',       label:'Saved' },
            quoted:      { cls:'bg-yellow-100 text-yellow-700',  label:'Quoted' },
            confirmed:   { cls:'bg-green-100 text-green-700',    label:'Confirmed' },
            in_progress: { cls:'bg-indigo-100 text-indigo-700',  label:'In Progress' },
            completed:   { cls:'bg-[#50BC81]/20 text-[#3da868]', label:'Completed' },
            cancelled:   { cls:'bg-red-100 text-red-700',        label:'Cancelled' },
        };
        return map[s] || { cls:'bg-gray-100 text-gray-500', label:s };
    }

    function _page(p) { st.page = p; load(); }
    function _edit(sys_id) { window.location.href = `create-package.php?sys_id=${sys_id}`; }
    function _view(v) {
        st.view = v;
        document.getElementById('btnViewList').className = `px-3 py-2 text-sm transition ${v==='list'?'bg-[#1A2039] text-white':'bg-white text-gray-500 hover:bg-gray-50'}`;
        document.getElementById('btnViewGrid').className = `px-3 py-2 text-sm transition ${v==='grid'?'bg-[#1A2039] text-white':'bg-white text-gray-500 hover:bg-gray-50'}`;
        document.getElementById('viewList').classList.toggle('hidden', v !== 'list');
        document.getElementById('viewGrid').classList.toggle('hidden', v !== 'grid');
        load();
    }
    async function _delete(sys_id, title) {
        const restore = st.status === 'trash';
        if (!restore && !confirm(`Delete "${title}"?`)) return;
        const res = await thApi(`${API_BASE}api/packages/delete.php`, 'POST', { sys_id, restore });
        if (res.success) { thToast(restore?'Restored':'Deleted'); load(); }
        else thToast(res.message || 'Error', 'error');
    }

    return { init, _page, _edit, _delete, _view };
})();