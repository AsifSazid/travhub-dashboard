<?php
/**
 * FILE PATH: pages/st-documents.php
 * Documents tab — SMB folder explorer style
 * Left: folder list, Right: files in folder + properties panel
 */
?>

<div class="flex flex-col overflow-hidden" style="height: calc(100vh - 260px); min-height: 400px;">

    <!-- Header -->
    <div class="flex items-center justify-between mb-3 flex-shrink-0">
        <h3 class="text-sm font-semibold text-gray-700">Documents</h3>
        <div class="flex items-center gap-2">
            <button onclick="togglePropsPanel()"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-gray-300 text-gray-600 text-xs rounded-lg hover:bg-gray-50">
                <i class="fas fa-info-circle"></i> Properties
            </button>
            <a href="smart-upload.php?traveler=<?= htmlspecialchars($travelerId) ?>"
               class="inline-flex items-center gap-2 px-3 py-1.5 bg-emerald-600 text-white text-xs rounded-lg hover:bg-emerald-700">
                <i class="fas fa-upload"></i> Smart Upload
            </a>
        </div>
    </div>

    <!-- Loading -->
    <div id="docsLoading" class="flex items-center gap-2 text-sm text-gray-500 py-4">
        <span class="w-4 h-4 border-2 border-gray-300 border-t-blue-500 rounded-full animate-spin"></span>
        Loading documents...
    </div>

    <!-- Expiring soon strip -->
    <div id="expiringSoonStrip" class="hidden mb-2 flex-shrink-0"></div>

    <!-- File Explorer -->
    <div id="docsExplorer" class="hidden flex-1 min-h-0 flex gap-3 overflow-hidden">

        <!-- Left: Folder list -->
        <div class="w-52 flex-shrink-0 overflow-y-auto">
            <div id="folderList" class="space-y-0.5"></div>
        </div>

        <!-- Right: Files + Properties -->
        <div class="flex-1 min-w-0 flex gap-3 overflow-hidden">

            <!-- Files grid -->
            <div id="filesPanel" class="flex-1 min-w-0 overflow-y-auto">
                <div id="filesGrid" class="grid grid-cols-3 gap-2 content-start"></div>
                <div id="filesEmpty" class="hidden flex flex-col items-center justify-center h-40 text-gray-400 text-sm">
                    <i class="fas fa-folder-open text-3xl mb-2"></i>
                    <span>Empty folder</span>
                </div>
            </div>

            <!-- Properties panel -->
            <div id="propsPanel" class="hidden w-56 flex-shrink-0 overflow-y-auto border-l border-gray-200 pl-3">
                <div id="propsContent"></div>
            </div>
        </div>
    </div>

    <!-- Empty state -->
    <div id="docsEmpty" class="hidden flex-1 flex flex-col items-center justify-center text-center text-gray-400 py-12">
        <i class="fas fa-folder-open text-4xl mb-3"></i>
        <p class="font-medium">No documents uploaded yet</p>
        <a href="smart-upload.php?traveler=<?= htmlspecialchars($travelerId) ?>"
           class="mt-3 px-4 py-2 bg-emerald-600 text-white text-sm rounded-lg hover:bg-emerald-700">
            Upload Now
        </a>
    </div>

</div>

<script>
(function() {
    const TRAVELER_ID = <?= json_encode($travelerId) ?>;

    // SMB folder order — label = smb_folder name অবিকল (doc requirement অনুযায়ী)
    const FOLDERS = [
        { key: 'passport_identity',      icon: 'fa-passport',    color: 'blue' },
        { key: 'nid',                    icon: 'fa-id-card',     color: 'indigo' },
        { key: 'countries_documents',    icon: 'fa-stamp',       color: 'green' },
        { key: 'travel_documents',       icon: 'fa-plane',       color: 'cyan' },
        { key: 'financial_documents',    icon: 'fa-university',  color: 'yellow' },
        { key: 'professional_documents', icon: 'fa-briefcase',   color: 'orange' },
        { key: 'personal_documents',     icon: 'fa-file-alt',    color: 'pink' },
        { key: 'photos_signature',       icon: 'fa-image',       color: 'purple' },
        { key: 'travel_history',         icon: 'fa-history',     color: 'teal' },
        // 'all_documents' folder সরিয়ে দেওয়া হয়েছে — session doc "Design/UX" নোট অনুযায়ী
    ];

    let allDocs = {};       // smb_folder → docs[]
    let selectedFolder = null;
    let selectedDoc    = null;

    // ── Load ──────────────────────────────────────────────────────────────────
    async function load() {
        try {
            const res  = await fetch(`/api/travelers/list-documents.php?traveler_sys_id=${TRAVELER_ID}&include_pages=1&group_by=smb_folder`);
            const data = await res.json();

            document.getElementById('docsLoading').classList.add('hidden');

            if (!data.success) return;

            if (data.total_docs === 0) {
                document.getElementById('docsEmpty').classList.remove('hidden');
                return;
            }

            // API already groups by smb_folder
            allDocs = data.groups || {};

            renderExpiringSoon(data.expiring_soon || []);
            renderFolders();

            document.getElementById('docsExplorer').classList.remove('hidden');

            // Auto-select first non-empty folder — FOLDERS প্রথমে, তারপর কোনো
            // extra/unlisted folder (যেমন all_documents) থাকলে সেটাও fallback হিসেবে
            const knownFirst = FOLDERS.find(f => allDocs[f.key]?.length);
            const anyFirst   = knownFirst?.key
                || Object.keys(allDocs).find(k => allDocs[k]?.length);
            if (anyFirst) selectFolder(anyFirst);

        } catch (err) {
            document.getElementById('docsLoading').classList.add('hidden');
            document.getElementById('docsLoading').innerHTML =
                `<span class="text-red-500 text-sm">Error: ${err.message}</span>`;
            document.getElementById('docsLoading').classList.remove('hidden');
        }
    }

    // ── Expiring soon ─────────────────────────────────────────────────────────
    function renderExpiringSoon(items) {
        if (!items.length) return;
        const strip = document.getElementById('expiringSoonStrip');
        strip.classList.remove('hidden');
        strip.innerHTML = `
            <div class="bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 flex flex-wrap gap-1.5 items-center">
                <span class="text-xs font-semibold text-amber-800"><i class="fas fa-exclamation-triangle mr-1"></i>Attention</span>
                ${items.map(item => {
                    const isExp = item.is_expired;
                    const color = isExp ? 'red' : 'amber';
                    const label = isExp ? `Expired ${Math.abs(item.days_left)}d ago` : `${item.days_left}d left`;
                    return `<span class="text-xs px-2 py-0.5 rounded-full bg-${color}-100 text-${color}-800 cursor-pointer"
                                  onclick="jumpToDoc('${item.sys_id}')">
                        ${item.doc_number || item.doc_type} · ${label}
                    </span>`;
                }).join('')}
            </div>`;
    }

    // ── Folders ───────────────────────────────────────────────────────────────
    function renderFolders() {
        const list = document.getElementById('folderList');
        list.innerHTML = '';

        // all_documents folder-এ কোনো data না থাকলে list-এ দেখাবো না, কিন্তু data
        // থাকলে অবশ্যই দেখাবো — নাহলে সেই doc গুলো UI থেকে হারিয়ে যায়
        const knownKeys = new Set(FOLDERS.map(f => f.key));
        const extraFolders = Object.keys(allDocs)
            .filter(k => !knownKeys.has(k) && (allDocs[k]?.length > 0))
            .map(k => ({ key: k, icon: 'fa-folder', color: 'gray' }));

        [...FOLDERS, ...extraFolders].forEach(folder => {
            const docs  = allDocs[folder.key] || [];
            const count = docs.length;

            const item = document.createElement('button');
            item.id        = `folder-${folder.key}`;
            item.className = `w-full flex items-center gap-2 px-3 py-2 rounded-lg text-left transition-colors
                              ${count ? 'hover:bg-gray-100 text-gray-700' : 'text-gray-300 cursor-default'}`;
            item.onclick   = () => count && selectFolder(folder.key);
            item.innerHTML = `
                <i class="fas ${folder.icon} w-4 text-center text-${folder.color}-500 text-xs"></i>
                <span class="flex-1 text-xs truncate">${folder.key}</span>
                ${count ? `<span class="text-xs bg-gray-200 text-gray-600 px-1.5 py-0.5 rounded-full">${count}</span>` : ''}`;

            list.appendChild(item);
        });
    }

    // ── Select folder ─────────────────────────────────────────────────────────
    function selectFolder(folderKey) {
        selectedFolder = folderKey;
        selectedDoc    = null;

        // Highlight active folder
        document.querySelectorAll('[id^="folder-"]').forEach(el => {
            el.classList.remove('bg-blue-50', 'text-blue-700', 'font-medium');
        });
        const active = document.getElementById(`folder-${folderKey}`);
        if (active) active.classList.add('bg-blue-50', 'text-blue-700', 'font-medium');

        renderFiles(folderKey);
        hideProps();
    }

    // ── Files ─────────────────────────────────────────────────────────────────
    function renderFiles(folderKey) {
        const docs  = allDocs[folderKey] || [];
        const grid  = document.getElementById('filesGrid');
        const empty = document.getElementById('filesEmpty');

        grid.innerHTML = '';
        docsBySysId = {}; // right-click context menu থেকে doc lookup করার জন্য

        if (!docs.length) {
            empty.classList.remove('hidden');
            return;
        }
        empty.classList.add('hidden');

        docs.forEach(doc => {
            docsBySysId[doc.sys_id] = doc;

            const card = document.createElement('div');
            card.id        = `file-${doc.sys_id}`;
            card.className = `flex flex-col items-center gap-1.5 p-3 rounded-xl border border-gray-100
                              cursor-pointer hover:bg-blue-50 hover:border-blue-200 transition-all text-center`;
            card.onclick        = () => selectDoc(doc);
            card.oncontextmenu  = (e) => { e.preventDefault(); openContextMenu(e, doc); };

            const pages    = doc.pages || [];
            const thumbUrl = pages[0]?.url;
            const isExpired= doc.is_expired;
            const expBadge = isExpired
                ? `<span class="text-[9px] bg-red-100 text-red-700 px-1 rounded">Expired</span>`
                : (doc.is_expiring_soon
                    ? `<span class="text-[9px] bg-amber-100 text-amber-700 px-1 rounded">${doc.days_until_expiry}d</span>`
                    : '');

            card.innerHTML = `
                <div class="w-14 h-16 rounded-lg overflow-hidden bg-gray-100 flex items-center justify-center flex-shrink-0 border border-gray-200">
                    ${thumbUrl
                        ? `<img src="${escHtml(thumbUrl)}" class="w-full h-full object-cover"
                               onerror="this.parentNode.innerHTML='<i class=\\'fas fa-file text-gray-400 text-2xl\\'></i>'">`
                        : `<i class="fas fa-file text-gray-400 text-2xl"></i>`}
                </div>
                <div class="w-full">
                    <p class="text-xs font-medium text-gray-700 truncate" title="${escHtml(doc.suggested_filename_stem || doc.doc_number || '')}">
                        ${escHtml(doc.suggested_filename_stem || doc.doc_number || 'unnamed')}
                    </p>
                    ${expBadge}
                </div>`;

            grid.appendChild(card);
        });
    }

    // ── Right-click context menu (Windows File Explorer style) ─────────────────
    let docsBySysId = {};

    function openContextMenu(e, doc) {
        closeContextMenu();
        selectDoc(doc); // highlight + properties panel (খোলা থাকলে) sync করে দেয়

        const pages = doc.pages || [];
        const isPassport = doc.doc_type === 'passport';

        const menu = document.createElement('div');
        menu.id = 'fileContextMenu';
        menu.className = 'fixed z-50 bg-white rounded-lg shadow-xl border border-gray-200 py-1 text-xs w-48';
        menu.style.left = `${e.clientX}px`;
        menu.style.top  = `${e.clientY}px`;

        menu.innerHTML = `
            <button onclick="closeContextMenu(); openDocViewer('${doc.sys_id}', ${escHtml(JSON.stringify(pages.map(p=>p.url).filter(Boolean)))})"
                class="w-full text-left px-3 py-1.5 hover:bg-gray-100 text-gray-700 flex items-center gap-2">
                <i class="fas fa-eye w-3 text-gray-500"></i> View
            </button>
            ${pages.length && pages[0].url ? `
            <button onclick="copyImageToClipboard('${escHtml(pages[0].url)}', this); "
                class="w-full text-left px-3 py-1.5 hover:bg-gray-100 text-gray-700 flex items-center gap-2">
                <i class="fas fa-copy w-3 text-gray-500"></i> Copy Image
            </button>
            ` : ''}
            ${!isPassport ? `
            <button onclick="closeContextMenu(); startRenameDoc('${doc.sys_id}', '${escHtml(doc.suggested_filename_stem || '')}')"
                class="w-full text-left px-3 py-1.5 hover:bg-gray-100 text-gray-700 flex items-center gap-2">
                <i class="fas fa-i-cursor w-3 text-gray-500"></i> Rename
            </button>
            <button onclick="closeContextMenu(); startMoveDoc('${doc.sys_id}', '${escHtml(doc.smb_folder)}')"
                class="w-full text-left px-3 py-1.5 hover:bg-gray-100 text-gray-700 flex items-center gap-2">
                <i class="fas fa-folder-open w-3 text-gray-500"></i> Move to Folder
            </button>
            ` : `
            <div class="px-3 py-1 text-[10px] text-gray-400 border-t border-b border-gray-100 my-0.5">Passport — systematic naming</div>
            `}
            <div class="border-t border-gray-100 my-0.5"></div>
            <button onclick="closeContextMenu(); togglePropsPanel(true)"
                class="w-full text-left px-3 py-1.5 hover:bg-gray-100 text-gray-700 flex items-center gap-2">
                <i class="fas fa-info-circle w-3 text-gray-500"></i> Properties
            </button>
            <button onclick="closeContextMenu(); startDeleteDoc('${doc.sys_id}')"
                class="w-full text-left px-3 py-1.5 hover:bg-red-50 text-red-600 flex items-center gap-2">
                <i class="fas fa-trash w-3"></i> Delete
            </button>`;

        document.body.appendChild(menu);

        // viewport এর বাইরে চলে গেলে position ঠিক করো
        const rect = menu.getBoundingClientRect();
        if (rect.right > window.innerWidth)  menu.style.left = `${window.innerWidth - rect.width - 8}px`;
        if (rect.bottom > window.innerHeight) menu.style.top  = `${window.innerHeight - rect.height - 8}px`;

        // menu-র বাইরে click করলে বন্ধ হয়ে যাবে
        setTimeout(() => document.addEventListener('click', closeContextMenu, { once: true }), 0);
    }

    window.closeContextMenu = function() {
        document.getElementById('fileContextMenu')?.remove();
    }

    // ── Properties panel toggle (Windows-এর মতো on/off) ─────────────────────────
    // window.-তে attach করা আবশ্যক: এই ফাংশন HTML onclick="" attribute থেকে
    // কল হয়, যা সবসময় global (window) scope-এ resolve হয় — IIFE-এর ভেতরের
    // local function হিসেবে থাকলে "not defined" error দেয়
    window.togglePropsPanel = function(forceOpen) {
        const panel = document.getElementById('propsPanel');
        const shouldShow = forceOpen !== undefined ? forceOpen : panel.classList.contains('hidden');

        if (shouldShow) {
            if (!selectedDoc) {
                alert('আগে একটা file select করুন (click করে)');
                return;
            }
            showProps(selectedDoc);
        } else {
            panel.classList.add('hidden');
        }
    }

    // ── Select doc → show properties ─────────────────────────────────────────
    function selectDoc(doc) {
        selectedDoc = doc;

        // Highlight selected file
        document.querySelectorAll('[id^="file-"]').forEach(el => {
            el.classList.remove('bg-blue-100', 'border-blue-400');
        });
        const el = document.getElementById(`file-${doc.sys_id}`);
        if (el) el.classList.add('bg-blue-100', 'border-blue-400');

        // Properties panel খোলা থাকলে content sync করো, বন্ধ থাকলে খুলবে না
        // (Windows explorer এর মতো — click শুধু select করে, properties আলাদা)
        if (!document.getElementById('propsPanel').classList.contains('hidden')) {
            showProps(doc);
        }
    }

    // ── Properties panel header (toggle close বাটনসহ) ───────────────────────────
    function propsHeaderHtml() {
        return `
            <div class="flex items-center justify-between mb-2 pb-2 border-b border-gray-200">
                <span class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Properties</span>
                <button onclick="togglePropsPanel(false)" class="text-gray-400 hover:text-gray-700 text-sm px-1">
                    <i class="fas fa-times"></i>
                </button>
            </div>`;
    }

    // ── Properties panel ──────────────────────────────────────────────────────
    function showProps(doc) {
        const panel   = document.getElementById('propsPanel');
        const content = document.getElementById('propsContent');
        panel.classList.remove('hidden');

        const pages   = doc.pages || [];
        const folder  = FOLDERS.find(f => f.key === doc.smb_folder) || { key: doc.smb_folder };

        // Expiry badge
        let expBadge = '';
        if (doc.expiry_date) {
            if (doc.is_expired) {
                expBadge = `<span class="text-xs px-2 py-0.5 rounded-full bg-red-100 text-red-700">Expired</span>`;
            } else if (doc.is_expiring_soon) {
                expBadge = `<span class="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">${doc.days_until_expiry}d left</span>`;
            }
        }

        content.innerHTML = `
            ${propsHeaderHtml()}

            <!-- Thumbnail / preview -->
            <div class="mb-3">
                ${pages.length && pages[0].url
                    ? `<img src="${escHtml(pages[0].url)}"
                           class="w-full rounded-lg border border-gray-200 cursor-pointer"
                           onclick="openDocViewer('${doc.sys_id}', ${escHtml(JSON.stringify(pages.map(p=>p.url).filter(Boolean)))})"
                           onerror="this.style.display='none'">`
                    : `<div class="w-full h-28 bg-gray-100 rounded-lg flex items-center justify-center">
                           <i class="fas fa-file text-gray-400 text-4xl"></i>
                       </div>`}
            </div>

            <!-- Info -->
            <div class="space-y-2 text-xs">
                <div>
                    <p class="text-gray-400 uppercase tracking-wide mb-0.5">Document Number</p>
                    <p class="font-mono font-semibold text-gray-800">${escHtml(doc.doc_number || '—')}</p>
                </div>
                ${doc.issue_date ? `<div>
                    <p class="text-gray-400 uppercase tracking-wide mb-0.5">Issue Date</p>
                    <p class="text-gray-700">${formatDate(doc.issue_date)}</p>
                </div>` : ''}
                ${doc.expiry_date ? `<div>
                    <p class="text-gray-400 uppercase tracking-wide mb-0.5">Expiry Date</p>
                    <div class="flex items-center gap-1">
                        <p class="text-gray-700">${formatDate(doc.expiry_date)}</p>
                        ${expBadge}
                    </div>
                </div>` : ''}
                ${doc.country ? `<div>
                    <p class="text-gray-400 uppercase tracking-wide mb-0.5">Country</p>
                    <p class="text-gray-700">${escHtml(doc.country)}</p>
                </div>` : ''}
                <div>
                    <p class="text-gray-400 uppercase tracking-wide mb-0.5">Folder</p>
                    <p class="text-gray-700">${escHtml(folder.key)}</p>
                </div>
                <div>
                    <p class="text-gray-400 uppercase tracking-wide mb-0.5">Confidence</p>
                    <p class="text-gray-700">${doc.confidence ? Math.round(doc.confidence * 100) + '%' : '—'}</p>
                </div>
                ${pages.length > 1 ? `<div>
                    <p class="text-gray-400 uppercase tracking-wide mb-0.5">Pages</p>
                    <div class="flex flex-wrap gap-1">
                        ${pages.map((p, i) => `
                            <button onclick="openDocViewer('${doc.sys_id}', ${escHtml(JSON.stringify(pages.map(pp=>pp.url).filter(Boolean)))}, ${i})"
                                    class="text-[10px] bg-gray-100 hover:bg-blue-100 text-gray-600 px-1.5 py-0.5 rounded">
                                p${p.page_no} ${p.country ? '· '+p.country : ''}
                            </button>`).join('')}
                    </div>
                </div>` : ''}
                ${doc.summary ? `<div>
                    <p class="text-gray-400 uppercase tracking-wide mb-0.5">Summary</p>
                    <p class="text-gray-600 leading-relaxed">${escHtml(doc.summary)}</p>
                </div>` : ''}
            </div>

            <!-- File actions — right-click context menu-এও একই action গুলো আছে -->
            <div class="mt-4 pt-3 border-t border-gray-200 space-y-1.5">
                <button onclick="openDocViewer('${doc.sys_id}', ${escHtml(JSON.stringify(pages.map(p=>p.url).filter(Boolean)))})"
                    class="w-full text-left text-xs px-2 py-1.5 rounded hover:bg-gray-100 text-gray-700 flex items-center gap-2">
                    <i class="fas fa-eye w-3 text-gray-500"></i> View
                </button>
                ${pages.length && pages[0].url ? `
                <button onclick="copyImageToClipboard('${escHtml(pages[0].url)}', this)"
                    class="w-full text-left text-xs px-2 py-1.5 rounded hover:bg-gray-100 text-gray-700 flex items-center gap-2">
                    <i class="fas fa-copy w-3 text-gray-500"></i> Copy Image
                </button>
                ` : ''}
                ${doc.doc_type !== 'passport' ? `
                <button onclick="startRenameDoc('${doc.sys_id}', '${escHtml(doc.suggested_filename_stem || '')}')"
                    class="w-full text-left text-xs px-2 py-1.5 rounded hover:bg-gray-100 text-gray-700 flex items-center gap-2">
                    <i class="fas fa-i-cursor w-3 text-gray-500"></i> Rename
                </button>
                <button onclick="startMoveDoc('${doc.sys_id}', '${escHtml(doc.smb_folder)}')"
                    class="w-full text-left text-xs px-2 py-1.5 rounded hover:bg-gray-100 text-gray-700 flex items-center gap-2">
                    <i class="fas fa-folder-open w-3 text-gray-500"></i> Move to Folder
                </button>
                ` : `
                <p class="text-[10px] text-gray-400 px-2 py-1">Passport ফাইলের নাম/folder systematic — rename/move নেই</p>
                `}
                <button onclick="startDeleteDoc('${doc.sys_id}')"
                    class="w-full text-left text-xs px-2 py-1.5 rounded hover:bg-red-50 text-red-600 flex items-center gap-2">
                    <i class="fas fa-trash w-3"></i> Delete
                </button>
            </div>`;
    }

    // ── Copy Image (clipboard) ──────────────────────────────────────────────────
    // Client-side, কোনো API লাগে না — image fetch করে blob বানিয়ে সরাসরি
    // clipboard-এ কপি করে দেয়, যেকোনো জায়গায় paste করা যাবে
    window.copyImageToClipboard = async function(url, btnEl) {
        const original = btnEl.innerHTML;
        try {
            const res  = await fetch(url);
            const blob = await res.blob();

            // clipboard.write() strict user-gesture এর মধ্যেই হতে হয়, তাই
            // window.focus() + ছোট delay — না হলে কিছু browser এ
            // NotAllowedError আসে
            window.focus();
            await new Promise(r => setTimeout(r, 100));

            await navigator.clipboard.write([
                new ClipboardItem({ [blob.type]: blob })
            ]);

            btnEl.innerHTML = '<i class="fas fa-check w-3 text-green-500"></i> Copied!';
            setTimeout(() => { btnEl.innerHTML = original; }, 1500);
        } catch (err) {
            alert('Copy ব্যর্থ হয়েছে: ' + err.message);
            btnEl.innerHTML = original;
        }
    }

    // ── Move ──────────────────────────────────────────────────────────────────
    window.startMoveDoc = function(sysId, currentFolder) {
        const options = FOLDERS
            .filter(f => f.key !== currentFolder)
            .map(f => `<option value="${f.key}">${f.key}</option>`).join('');

        const overlay = document.createElement('div');
        overlay.className = 'fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4';
        overlay.innerHTML = `
            <div class="bg-white rounded-lg shadow-xl max-w-sm w-full p-5">
                <h4 class="text-sm font-semibold text-gray-800 mb-3">
                    <i class="fas fa-folder-open mr-1"></i> কোন Folder-এ Move করবেন?
                </h4>
                <p class="text-xs text-gray-500 mb-2">বর্তমান folder: <span class="font-mono">${escHtml(currentFolder)}</span></p>
                <select id="moveTargetFolder" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs mb-3">
                    ${options}
                </select>
                <div class="flex gap-2 justify-end">
                    <button onclick="this.closest('.fixed').remove()"
                        class="text-xs px-3 py-1.5 rounded border border-gray-300 text-gray-600 hover:bg-gray-50">Cancel</button>
                    <button onclick="confirmMoveDoc('${sysId}')"
                        class="text-xs px-3 py-1.5 rounded bg-blue-600 text-white hover:bg-blue-700">Move করুন</button>
                </div>
            </div>`;
        document.body.appendChild(overlay);
    }

    window.confirmMoveDoc = function(sysId) {
        const newFolder = document.getElementById('moveTargetFolder').value;

        fetch(`/api/travelers/move-document.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ sys_id: sysId, new_smb_folder: newFolder })
        })
        .then(r => r.json())
        .then(res => {
            document.querySelectorAll('.fixed.inset-0.bg-black\\/50').forEach(el => el.remove());
            if (res.success) {
                hideProps();
                load();
            } else {
                alert('Move ব্যর্থ: ' + (res.message || 'অজানা কারণ'));
            }
        })
        .catch(err => alert('Move এ সমস্যা: ' + err.message));
    }

    // ── Rename ────────────────────────────────────────────────────────────────
    window.startRenameDoc = function(sysId, currentStem) {
        const newStem = prompt('নতুন filename (extension ছাড়া, শুধু letters/numbers/underscore):', currentStem);
        if (newStem === null) return; // cancelled
        if (!newStem.trim()) { alert('নাম খালি রাখা যাবে না'); return; }

        fetch(`/api/travelers/rename-document.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ sys_id: sysId, new_stem: newStem.trim() })
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                load(); // পুরো তালিকা রিফ্রেশ, নতুন filename দেখানোর জন্য
            } else {
                alert('Rename ব্যর্থ: ' + (res.message || 'অজানা কারণ'));
            }
        })
        .catch(err => alert('Rename এ সমস্যা: ' + err.message));
    }

    // ── Delete (soft) — reason আবশ্যক + sys_id confirm করতে হবে ────────────────
    window.startDeleteDoc = function(sysId) {
        const overlay = document.createElement('div');
        overlay.className = 'fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4';
        overlay.innerHTML = `
            <div class="bg-white rounded-lg shadow-xl max-w-sm w-full p-5">
                <h4 class="text-sm font-semibold text-red-700 mb-2">
                    <i class="fas fa-exclamation-triangle mr-1"></i> Document Delete করবেন?
                </h4>
                <p class="text-xs text-gray-500 mb-3">
                    এটা soft-delete — DB তে status বদলাবে, SMB ফাইল থেকে যাবে (recoverable)।
                    নিশ্চিত করতে নিচে exact Document ID টাইপ করুন এবং কারণ লিখুন।
                </p>
                <p class="text-[10px] text-gray-400 mb-1">Document ID</p>
                <p class="font-mono text-xs bg-gray-100 px-2 py-1 rounded mb-2 select-all">${escHtml(sysId)}</p>
                <input id="delConfirmInput" type="text" placeholder="এখানে Document ID টাইপ করুন"
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs mb-2">
                <textarea id="delReasonInput" placeholder="Delete করার কারণ (আবশ্যক, কমপক্ষে ৫ অক্ষর)"
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs mb-3" rows="2"></textarea>
                <div class="flex gap-2 justify-end">
                    <button onclick="this.closest('.fixed').remove()"
                        class="text-xs px-3 py-1.5 rounded border border-gray-300 text-gray-600 hover:bg-gray-50">Cancel</button>
                    <button onclick="confirmDeleteDoc('${escHtml(sysId)}')"
                        class="text-xs px-3 py-1.5 rounded bg-red-600 text-white hover:bg-red-700">Delete করুন</button>
                </div>
            </div>`;
        document.body.appendChild(overlay);
    }

    window.confirmDeleteDoc = function(sysId) {
        const confirmInput = document.getElementById('delConfirmInput').value.trim();
        const reason       = document.getElementById('delReasonInput').value.trim();

        if (confirmInput !== sysId) {
            alert('Document ID মেলেনি — আবার চেষ্টা করুন');
            return;
        }
        if (reason.length < 5) {
            alert('Delete করার কারণ লিখুন (কমপক্ষে ৫ অক্ষর)');
            return;
        }

        fetch(`/api/travelers/delete-document.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ sys_id: sysId, confirm_sys_id: confirmInput, reason: reason })
        })
        .then(r => r.json())
        .then(res => {
            document.querySelectorAll('.fixed.inset-0.bg-black\\/50').forEach(el => el.remove());
            if (res.success) {
                hideProps();
                load();
            } else {
                alert('Delete ব্যর্থ: ' + (res.message || 'অজানা কারণ'));
            }
        })
        .catch(err => alert('Delete এ সমস্যা: ' + err.message));
    }

    function hideProps() {
        document.getElementById('propsPanel').classList.add('hidden');
    }

    // ── openDocViewer ─────────────────────────────────────────────────────────
    window.openDocViewer = function(docSysId, pageUrls, startPage) {
        const urls = Array.isArray(pageUrls) && pageUrls.length ? pageUrls : [];
        if (!urls.length) return;

        let currentPage = startPage || 0;

        const overlay = document.createElement('div');
        overlay.className = 'fixed inset-0 bg-black/80 z-50 flex items-center justify-center p-4';
        overlay.onclick = (e) => { if (e.target === overlay) overlay.remove(); };

        function render() {
            overlay.innerHTML = `
                <div class="relative max-w-4xl max-h-full flex flex-col items-center gap-3" onclick="event.stopPropagation()">
                    <img src="${escHtml(urls[currentPage])}"
                         class="max-w-full max-h-[80vh] object-contain rounded-lg bg-white"
                         onerror="this.alt='Could not load'">
                    ${urls.length > 1 ? `
                    <div class="flex items-center gap-4">
                        <button onclick="window._dvPrev()" class="px-3 py-1 bg-white/20 text-white rounded hover:bg-white/40" ${currentPage===0?'disabled':''}>← Prev</button>
                        <span class="text-white text-sm">${currentPage+1} / ${urls.length}</span>
                        <button onclick="window._dvNext()" class="px-3 py-1 bg-white/20 text-white rounded hover:bg-white/40" ${currentPage===urls.length-1?'disabled':''}>Next →</button>
                    </div>` : ''}
                    <button onclick="document.querySelector('.fixed.z-50').remove()"
                            class="absolute top-0 right-0 w-8 h-8 bg-white/20 text-white rounded-full flex items-center justify-center hover:bg-white/40">✕</button>
                </div>`;
        }

        window._dvPrev = () => { if (currentPage > 0) { currentPage--; render(); } };
        window._dvNext = () => { if (currentPage < urls.length-1) { currentPage++; render(); } };
        render();
        document.body.appendChild(overlay);
    };

    // ── jumpToDoc ─────────────────────────────────────────────────────────────
    window.jumpToDoc = function(sysId) {
        for (const [folder, docs] of Object.entries(allDocs)) {
            const doc = docs.find(d => d.sys_id === sysId);
            if (doc) {
                selectFolder(folder);
                setTimeout(() => selectDoc(doc), 50);
                return;
            }
        }
    };

    // ── Helpers ───────────────────────────────────────────────────────────────
    function formatDate(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        if (isNaN(d)) return dateStr;
        return d.toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' });
    }

    function escHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    load();
})();
</script>