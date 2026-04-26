/**
 * TravHub - Package Listing Module
 * packages-list.js
 */

const PackagesList = (() => {
    const BASE_API = '../api/packages';
    let currentPage = 1;
    let currentTab = 'all';
    let searchQuery = '';
    let searchTimeout = null;

    // ─── Bootstrap ──────────────────────────────────────────────
    function init() {
        renderShell();
        bindEvents();
        loadPackages();
    }

    // ─── Shell HTML ─────────────────────────────────────────────
    function renderShell() {
        const main = document.getElementById('mainContent');
        main.innerHTML = `
        <div class="px-6 py-6 max-w-screen-2xl mx-auto">

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Tour Packages</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Manage and publish travel packages</p>
                </div>
                <a href="create-package.php"
                   class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2.5 rounded-xl shadow transition">
                    <i class="fa-solid fa-plus"></i> New Package
                </a>
            </div>

            <!-- Search + Filter Bar -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-5 flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input id="pkgSearch" type="text" placeholder="Search by title or ID…"
                           class="w-full pl-9 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
                <div class="flex gap-2 flex-wrap">
                    ${['all','draft','completed','published','trash'].map(t=>`
                    <button data-tab="${t}"
                            class="tab-btn px-4 py-2 text-sm font-medium rounded-xl border transition
                                   ${t==='all' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'}">
                        ${t.charAt(0).toUpperCase()+t.slice(1)}
                        <span id="tab-count-${t}" class="ml-1 text-xs opacity-70"></span>
                    </button>`).join('')}
                </div>
            </div>

            <!-- Cards Grid -->
            <div id="pkgGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5 min-h-[200px]">
                <div class="col-span-full flex items-center justify-center py-16 text-gray-400">
                    <i class="fa-solid fa-spinner fa-spin text-2xl mr-2"></i> Loading packages…
                </div>
            </div>

            <!-- Pagination -->
            <div id="pkgPagination" class="flex items-center justify-between mt-6"></div>

        </div>

        <!-- Confirm Modal -->
        <div id="confirmModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-sm mx-4">
                <div id="confirmIcon" class="text-center text-4xl mb-3"></div>
                <h3 id="confirmTitle" class="text-lg font-bold text-gray-800 text-center mb-1"></h3>
                <p id="confirmMsg" class="text-sm text-gray-500 text-center mb-5"></p>
                <div class="flex gap-3">
                    <button id="confirmCancel" class="flex-1 py-2.5 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 font-medium transition">Cancel</button>
                    <button id="confirmOk" class="flex-1 py-2.5 rounded-xl font-semibold text-white transition"></button>
                </div>
            </div>
        </div>

        <!-- Toast -->
        <div id="pkgToast" class="fixed bottom-6 right-6 z-50 hidden items-center gap-3 px-4 py-3 rounded-xl shadow-xl text-sm font-medium text-white max-w-xs"></div>
        `;
    }

    // ─── Events ─────────────────────────────────────────────────
    function bindEvents() {
        document.getElementById('pkgSearch').addEventListener('input', e => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                searchQuery = e.target.value.trim();
                currentPage = 1;
                loadPackages();
            }, 400);
        });

        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                currentTab = btn.dataset.tab;
                currentPage = 1;
                document.querySelectorAll('.tab-btn').forEach(b => {
                    b.classList.remove('bg-blue-600','text-white','border-blue-600');
                    b.classList.add('bg-white','text-gray-600','border-gray-200');
                });
                btn.classList.add('bg-blue-600','text-white','border-blue-600');
                btn.classList.remove('bg-white','text-gray-600','border-gray-200');
                loadPackages();
            });
        });
    }

    // ─── Load Packages ───────────────────────────────────────────
    async function loadPackages() {
        const grid = document.getElementById('pkgGrid');
        grid.innerHTML = `<div class="col-span-full flex items-center justify-center py-16 text-gray-400">
            <i class="fa-solid fa-spinner fa-spin text-2xl mr-2"></i> Loading…</div>`;

        const params = new URLSearchParams({
            page: currentPage,
            limit: 12,
            status: currentTab,
            search: searchQuery
        });

        try {
            const res = await fetch(`${BASE_API}/list.php?${params}`);
            const json = await res.json();
            if (!json.success) throw new Error(json.message);

            renderCards(json.data);
            renderPagination(json.pagination);
        } catch (err) {
            grid.innerHTML = `<div class="col-span-full text-center py-16 text-red-400">
                <i class="fa-solid fa-circle-exclamation text-3xl mb-2 block"></i>${err.message}</div>`;
        }
    }

    // ─── Render Cards ───────────────────────────────────────────
    function renderCards(packages) {
        const grid = document.getElementById('pkgGrid');
        if (!packages.length) {
            grid.innerHTML = `<div class="col-span-full text-center py-20 text-gray-400">
                <i class="fa-solid fa-box-open text-5xl mb-3 block opacity-30"></i>
                <p class="font-medium">No packages found</p></div>`;
            return;
        }

        grid.innerHTML = packages.map(pkg => cardHTML(pkg)).join('');

        // Bind card actions
        grid.querySelectorAll('[data-action]').forEach(btn => {
            btn.addEventListener('click', () => handleAction(btn.dataset.action, btn.dataset.uuid, btn.dataset.title));
        });
    }

    function cardHTML(pkg) {
        const statusClasses = {
            draft:     'bg-amber-50 text-amber-700 border-amber-200 uppercase',
            completed: 'bg-green-50 text-green-700 border-green-200 uppercase',
            published: 'bg-blue-50 text-blue-700 border-blue-200 uppercase',
            deleted:   'bg-red-50 text-red-700 border-red-200 uppercase',
        };
        const completionLabel = pkg.status === 'deleted' ? 'Deleted' : pkg.completion_status;
        const statusClass = pkg.status === 'deleted' ? statusClasses.deleted : (statusClasses[pkg.completion_status] || statusClasses.draft);

        const countries = (pkg.countries || []).slice(0,3).map(c =>
            `<span class="inline-block bg-blue-50 text-blue-700 text-xs px-2 py-0.5 rounded-full">${c.name}</span>`).join('');
        const extraCountries = (pkg.countries || []).length > 3
            ? `<span class="text-xs text-gray-400">+${pkg.countries.length-3} more</span>` : '';

        const stars = Array.from({length:5},(_,i)=>
            `<i class="fa-${i < pkg.rating ? 'solid' : 'regular'} fa-star text-amber-400 text-xs"></i>`).join('');

        const imgSrc = pkg.image ? `../${pkg.image}` : `../assets/images/pkg-placeholder.jpg`;

        const isDeleted = pkg.status === 'deleted';
        const actionBtns = isDeleted
            ? `<button data-action="restore" data-uuid="${pkg.uuid}" data-title="${escHtml(pkg.title)}"
                       class="flex-1 flex items-center justify-center gap-1 text-xs py-2 rounded-lg bg-green-50 text-green-700 hover:bg-green-100 transition font-medium">
                   <i class="fa-solid fa-rotate-left"></i> Restore</button>
               <button data-action="force-delete" data-uuid="${pkg.uuid}" data-title="${escHtml(pkg.title)}"
                       class="flex-1 flex items-center justify-center gap-1 text-xs py-2 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition font-medium">
                   <i class="fa-solid fa-trash-can"></i> Delete</button>`
            : `<a href="create-package.php?packageId=${pkg.sys_id}"
                  class="flex-1 flex items-center justify-center gap-1 text-xs py-2 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 transition font-medium">
                  <i class="fa-solid fa-pen-to-square"></i> Edit</a>
               <button data-action="delete" data-uuid="${pkg.uuid}" data-title="${escHtml(pkg.title)}"
                       class="flex-1 flex items-center justify-center gap-1 text-xs py-2 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition font-medium">
                   <i class="fa-solid fa-trash"></i> Delete</button>`;

        return `
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition group flex flex-col">
            <div class="relative h-40 bg-gray-100 overflow-hidden">
                <img src="${imgSrc}" alt="${escHtml(pkg.title)}"
                     class="w-full h-full object-cover group-hover:scale-105 transition duration-500"
                     onerror="this.src='https://via.placeholder.com/400x200?text=No+Image'">
                <span class="absolute top-3 right-3 text-xs font-semibold px-2.5 py-1 rounded-full border ${statusClass}">
                    ${completionLabel}
                </span>
                <div class="absolute bottom-3 left-3 flex gap-0.5">${stars}</div>
            </div>
            <div class="p-4 flex flex-col flex-1">
                <div class="flex items-start justify-between gap-2 mb-1">
                    <h3 class="font-bold text-gray-800 text-sm leading-tight line-clamp-2 flex-1">${escHtml(pkg.title)}</h3>
                </div>
                <p class="text-xs text-gray-400 font-mono mb-2">${pkg.sys_id}</p>
                <div class="flex flex-wrap gap-1 mb-3">${countries}${extraCountries}</div>
                <div class="flex items-center gap-4 text-xs text-gray-500 mb-3">
                    ${pkg.duration ? `<span><i class="fa-regular fa-clock mr-1"></i>${escHtml(pkg.duration)}</span>` : ''}
                    ${pkg.overall_price ? `<span class="font-semibold text-gray-700">${pkg.currency_symbol || ''}${Number(pkg.overall_price).toLocaleString()}</span>` : ''}
                </div>
                <div class="text-xs text-gray-400 mb-3 mt-auto">
                    <i class="fa-regular fa-user mr-1"></i>${escHtml(pkg.created_by)}
                    &nbsp;·&nbsp;<i class="fa-regular fa-calendar mr-1"></i>${formatDate(pkg.created_at)}
                </div>
                <div class="flex gap-2 pt-2 border-t border-gray-50">${actionBtns}</div>
            </div>
        </div>`;
    }

    // ─── Pagination ─────────────────────────────────────────────
    function renderPagination(p) {
        const el = document.getElementById('pkgPagination');
        if (!p || p.total_pages <= 1) { el.innerHTML = ''; return; }
        let btns = '';
        for (let i = 1; i <= p.total_pages; i++) {
            btns += `<button data-pg="${i}" class="pg-btn w-9 h-9 rounded-xl text-sm font-medium border transition
                ${i === p.page ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'}">${i}</button>`;
        }
        el.innerHTML = `
            <p class="text-sm text-gray-500">Showing ${((p.page-1)*p.limit)+1}–${Math.min(p.page*p.limit,p.total)} of ${p.total}</p>
            <div class="flex gap-1.5">${btns}</div>`;
        el.querySelectorAll('.pg-btn').forEach(b => {
            b.addEventListener('click', () => { currentPage = parseInt(b.dataset.pg); loadPackages(); });
        });
    }

    // ─── Actions ────────────────────────────────────────────────
    function handleAction(action, uuid, title) {
        const cfg = {
            delete:       { icon:'🗑️', color:'bg-red-500', label:'Move to Trash',  msg:`Move "<b>${title}</b>" to trash?` },
            restore:      { icon:'♻️', color:'bg-green-500',label:'Restore',        msg:`Restore "<b>${title}</b>"?` },
            'force-delete':{ icon:'💀', color:'bg-red-700', label:'Delete Forever', msg:`Permanently delete "<b>${title}</b>"? This cannot be undone.` },
        };
        const c = cfg[action];
        if (!c) return;

        const modal   = document.getElementById('confirmModal');
        document.getElementById('confirmIcon').textContent  = c.icon;
        document.getElementById('confirmTitle').textContent = c.label;
        document.getElementById('confirmMsg').innerHTML     = c.msg;
        const okBtn = document.getElementById('confirmOk');
        okBtn.className = `flex-1 py-2.5 rounded-xl font-semibold text-white transition ${c.color}`;
        okBtn.textContent = c.label;
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        const close = () => { modal.classList.add('hidden'); modal.classList.remove('flex'); };
        document.getElementById('confirmCancel').onclick = close;
        modal.onclick = e => { if (e.target === modal) close(); };

        okBtn.onclick = async () => {
            close();
            await doAction(action, uuid);
        };
    }

    async function doAction(action, uuid) {
        const endpoints = {
            delete: `${BASE_API}/delete.php`,
            restore: `${BASE_API}/restore.php`,
            'force-delete': `${BASE_API}/force-delete.php`,
        };
        try {
            const res  = await fetch(endpoints[action], { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({uuid}) });
            const json = await res.json();
            toast(json.success ? 'success' : 'error', json.message);
            if (json.success) loadPackages();
        } catch(e) { toast('error', 'Network error'); }
    }

    // ─── Helpers ─────────────────────────────────────────────────
    function toast(type, msg) {
        const el = document.getElementById('pkgToast');
        el.className = `fixed bottom-6 right-6 z-50 flex items-center gap-3 px-4 py-3 rounded-xl shadow-xl text-sm font-medium text-white max-w-xs
            ${type === 'success' ? 'bg-green-600' : 'bg-red-600'}`;
        el.innerHTML = `<i class="fa-solid fa-${type === 'success' ? 'circle-check' : 'circle-exclamation'}"></i> ${msg}`;
        el.classList.remove('hidden');
        setTimeout(() => el.classList.add('hidden'), 3500);
    }

    function escHtml(str) {
        return String(str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function formatDate(str) {
        if (!str) return '';
        return new Date(str).toLocaleDateString('en-GB', {day:'2-digit',month:'short',year:'numeric'});
    }

    return { init };
})();