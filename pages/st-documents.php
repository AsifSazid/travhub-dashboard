<?php
/**
 * FILE PATH: pages/st-documents.php
 * Documents tab — show-travelers.php এ include হয়।
 * traveler_documents table থেকে documents দেখায়।
 * smart-upload.php এ যাওয়ার button আছে।
 */
?>

<div class="h-full flex flex-col overflow-hidden">

    <!-- Header bar -->
    <div class="flex items-center justify-between mb-3 flex-shrink-0">
        <h3 class="text-sm font-semibold text-gray-700">Documents</h3>
        <a href="smart-upload.php?traveler=<?= htmlspecialchars($travelerId) ?>"
           class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white text-sm rounded-lg hover:bg-emerald-700">
            <i class="fas fa-upload"></i> Smart Upload
        </a>
    </div>

    <!-- Loading state -->
    <div id="docsLoading" class="flex items-center gap-2 text-sm text-gray-500 py-4">
        <span class="w-4 h-4 border-2 border-gray-300 border-t-blue-500 rounded-full animate-spin"></span>
        Loading documents...
    </div>

    <!-- Expiring soon strip -->
    <div id="expiringSoonStrip" class="hidden mb-3 flex-shrink-0"></div>

    <!-- Summary panel -->
    <div id="docsSummaryPanel" class="hidden mb-3 flex-shrink-0"></div>

    <!-- Documents grid -->
    <div id="docsGrid" class="hidden flex-1 overflow-y-auto space-y-4"></div>

    <!-- Empty state -->
    <div id="docsEmpty" class="hidden flex-1 flex flex-col items-center justify-center text-center text-gray-400 py-12">
        <i class="fas fa-folder-open text-4xl mb-3"></i>
        <p class="font-medium">No documents uploaded yet</p>
        <p class="text-sm mt-1">Use Smart Upload to add passport, visa, NID and other documents</p>
        <a href="smart-upload.php?traveler=<?= htmlspecialchars($travelerId) ?>"
           class="mt-4 px-4 py-2 bg-emerald-600 text-white text-sm rounded-lg hover:bg-emerald-700">
            Upload Now
        </a>
    </div>

</div>

<script>
(function() {
    const TRAVELER_ID = <?= json_encode($travelerId) ?>;

    // Doc type display labels
    const DOC_LABELS = {
        passport:             'Passport',
        nid:                  'National ID',
        visa:                 'Visa',
        visa_stamp:           'Visa Stamp / Entry-Exit',
        air_ticket:           'Air Ticket',
        hotel_voucher:        'Hotel Voucher',
        invitation_letter:    'Invitation Letter',
        bank_statement:       'Bank Statement',
        sponsor_letter:       'Sponsor Letter',
        employment_letter:    'Employment Letter',
        education_certificate:'Education Certificate',
        medical_report:       'Medical Report',
        vaccination_card:     'Vaccination Card',
        marriage_certificate: 'Marriage Certificate',
        birth_certificate:    'Birth Certificate',
        photo:                'Photograph',
        signature:            'Signature',
        other:                'Other',
    };

    const DOC_ICONS = {
        passport:             'fa-passport',
        nid:                  'fa-id-card',
        visa:                 'fa-stamp',
        visa_stamp:           'fa-stamp',
        air_ticket:           'fa-plane',
        hotel_voucher:        'fa-hotel',
        bank_statement:       'fa-university',
        sponsor_letter:       'fa-file-signature',
        employment_letter:    'fa-briefcase',
        education_certificate:'fa-graduation-cap',
        medical_report:       'fa-notes-medical',
        vaccination_card:     'fa-syringe',
        marriage_certificate: 'fa-ring',
        birth_certificate:    'fa-baby',
        photo:                'fa-image',
        signature:            'fa-signature',
        other:                'fa-file',
    };

    // ── Load documents ────────────────────────────────────────────────────────
    async function loadDocuments() {
        try {
            const res = await fetch(
                `/api/travelers/list-documents.php?traveler_sys_id=${TRAVELER_ID}&include_pages=1`
            );
            const data = await res.json();

            document.getElementById('docsLoading').classList.add('hidden');

            if (!data.success) {
                showError(data.error || 'Failed to load documents');
                return;
            }

            if (data.total_docs === 0) {
                document.getElementById('docsEmpty').classList.remove('hidden');
                return;
            }

            renderExpiringSoon(data.expiring_soon || []);
            renderGroups(data.groups || {});

        } catch (err) {
            document.getElementById('docsLoading').classList.add('hidden');
            showError('Network error: ' + err.message);
        }
    }

    // ── Expiring soon strip ───────────────────────────────────────────────────
    function renderExpiringSoon(items) {
        if (!items.length) return;
        const strip = document.getElementById('expiringSoonStrip');
        strip.classList.remove('hidden');

        strip.innerHTML = `
            <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-2 flex flex-wrap gap-2 items-center">
                <span class="text-xs font-semibold text-amber-800 uppercase tracking-wide">
                    <i class="fas fa-exclamation-triangle mr-1"></i> Attention
                </span>
                ${items.map(item => {
                    const isExp = item.is_expired;
                    const color = isExp ? 'red' : 'amber';
                    const label = isExp
                        ? `Expired ${Math.abs(item.days_left)}d ago`
                        : `${item.days_left}d left`;
                    return `
                        <span class="px-2 py-0.5 text-xs rounded-full bg-${color}-100 text-${color}-800 font-medium">
                            ${DOC_LABELS[item.doc_type] || item.doc_type}
                            ${item.doc_number ? `· ${item.doc_number}` : ''}
                            · <strong>${label}</strong>
                        </span>`;
                }).join('')}
            </div>`;
    }

    // ── Document groups ───────────────────────────────────────────────────────
    function renderGroups(groups) {
        const grid = document.getElementById('docsGrid');
        grid.classList.remove('hidden');
        grid.innerHTML = '';

        Object.entries(groups).forEach(([docType, docs]) => {
            const section = document.createElement('div');
            section.className = 'bg-white border border-gray-200 rounded-xl overflow-hidden';

            const icon = DOC_ICONS[docType] || 'fa-file';
            const label = DOC_LABELS[docType] || docType;
            const count = docs.length;

            section.innerHTML = `
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fas ${icon} text-gray-500 w-4 text-center"></i>
                        <span class="font-medium text-gray-800 text-sm">${label}</span>
                        <span class="text-xs bg-gray-200 text-gray-600 px-2 py-0.5 rounded-full">${count}</span>
                    </div>
                </div>
                <div class="divide-y divide-gray-100">
                    ${docs.map(doc => renderDocCard(doc)).join('')}
                </div>`;

            grid.appendChild(section);
        });
    }

    // ── Single doc card ───────────────────────────────────────────────────────
    function renderDocCard(doc) {
        // Expiry badge
        let expiryBadge = '';
        if (doc.expiry_date) {
            if (doc.is_expired) {
                expiryBadge = `<span class="text-xs px-2 py-0.5 rounded-full bg-red-100 text-red-700 font-medium">Expired</span>`;
            } else if (doc.is_expiring_soon) {
                expiryBadge = `<span class="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 font-medium">${doc.days_until_expiry}d left</span>`;
            } else {
                expiryBadge = `<span class="text-xs text-gray-400">Exp: ${formatDate(doc.expiry_date)}</span>`;
            }
        }

        // Passport status badge
        let passportBadge = '';
        if (doc.passport_status) {
            const colors = { current: 'emerald', previous: 'gray', historical: 'gray' };
            const c = colors[doc.passport_status] || 'gray';
            passportBadge = `<span class="text-xs px-2 py-0.5 rounded-full bg-${c}-100 text-${c}-700 capitalize">${doc.passport_status}</span>`;
        }

        // Confidence badge
        const confPct = doc.confidence ? Math.round(doc.confidence * 100) : null;
        const confBadge = confPct
            ? `<span class="text-xs text-gray-400">${confPct}% confidence</span>`
            : '';

        // Pages
        const pages = doc.pages || [];
        const pageBadges = pages.map(p => {
            const label = p.country ? `${p.page_type} · ${p.country}` : p.page_type;
            return `<span class="text-[10px] bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded">${escHtml(label)}</span>`;
        }).join(' ');

        // Thumbnail — first page image
        let thumbnail = '';
        if (pages.length && pages[0].url) {
            const pagesJson = escHtml(JSON.stringify(pages.map(p => p.url).filter(Boolean)));
            thumbnail = `
                <div class="flex-shrink-0">
                    <img src="${escHtml(pages[0].url)}"
                         alt="preview"
                         class="w-16 h-20 object-cover rounded border border-gray-200 cursor-pointer"
                         onclick="openDocViewer('${escHtml(doc.sys_id)}', ${pagesJson})"
                         onerror="this.parentNode.innerHTML='<div class=\'w-16 h-20 bg-gray-100 rounded border border-gray-200 flex items-center justify-center text-gray-400 text-xl\'><i class=\'fas fa-file\'></i></div>'">
                </div>`;
        }

        return `
            <div class="flex items-start gap-4 px-4 py-3 hover:bg-gray-50">
                ${thumbnail}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center flex-wrap gap-1.5 mb-1">
                        ${doc.doc_number
                            ? `<span class="font-mono text-sm font-semibold text-gray-800">${escHtml(doc.doc_number)}</span>`
                            : `<span class="text-sm text-gray-500 italic">No document number</span>`}
                        ${passportBadge}
                        ${expiryBadge}
                        ${doc.needs_review ? '<span class="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">Review</span>' : ''}
                    </div>
                    ${doc.summary
                        ? `<p class="text-xs text-gray-600 line-clamp-2 mb-1">${escHtml(doc.summary)}</p>`
                        : ''}
                    <div class="flex flex-wrap items-center gap-2 text-xs text-gray-400">
                        ${doc.page_count > 1 ? `<span>${doc.page_count} pages</span>` : ''}
                        ${doc.issue_date ? `<span>Issued: ${formatDate(doc.issue_date)}</span>` : ''}
                        ${doc.country ? `<span>${escHtml(doc.country)}</span>` : ''}
                        ${confBadge}
                    </div>
                    ${pageBadges ? `<div class="mt-1 flex flex-wrap gap-1">${pageBadges}</div>` : ''}
                </div>
            </div>`;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────
    function formatDate(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        if (isNaN(d)) return dateStr;
        return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    function escHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({
            '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
        }[c]));
    }

    function showError(msg) {
        document.getElementById('docsGrid').innerHTML =
            `<div class="text-red-600 text-sm py-4"><i class="fas fa-exclamation-circle mr-1"></i>${msg}</div>`;
        document.getElementById('docsGrid').classList.remove('hidden');
    }

    // ── Document viewer ──────────────────────────────────────────────────────
    window.openDocViewer = function(docSysId, pageUrls) {
        const urls = Array.isArray(pageUrls) && pageUrls.length ? pageUrls : [];
        if (!urls.length) return;

        let currentPage = 0;

        const overlay = document.createElement('div');
        overlay.className = 'fixed inset-0 bg-black/80 z-50 flex items-center justify-center p-4';
        overlay.onclick = (e) => { if (e.target === overlay) overlay.remove(); };

        function render() {
            overlay.innerHTML = `
                <div class="relative max-w-4xl max-h-full flex flex-col items-center gap-3" onclick="event.stopPropagation()">
                    <img src="${escHtml(urls[currentPage])}"
                         class="max-w-full max-h-[80vh] object-contain rounded-lg bg-white"
                         onerror="this.src=''; this.alt='Could not load file'">
                    ${urls.length > 1 ? `
                    <div class="flex items-center gap-4">
                        <button onclick="window._docViewerPrev()"
                                class="px-3 py-1 bg-white/20 text-white rounded hover:bg-white/40 disabled:opacity-30"
                                ${currentPage === 0 ? 'disabled' : ''}>← Prev</button>
                        <span class="text-white text-sm">${currentPage + 1} / ${urls.length}</span>
                        <button onclick="window._docViewerNext()"
                                class="px-3 py-1 bg-white/20 text-white rounded hover:bg-white/40 disabled:opacity-30"
                                ${currentPage === urls.length - 1 ? 'disabled' : ''}>Next →</button>
                    </div>` : ''}
                    <button onclick="document.querySelector('.fixed.z-50').remove()"
                            class="absolute top-0 right-0 w-8 h-8 bg-white/20 text-white rounded-full flex items-center justify-center hover:bg-white/40">
                        ✕
                    </button>
                </div>`;
        }

        window._docViewerPrev = () => { if (currentPage > 0) { currentPage--; render(); } };
        window._docViewerNext = () => { if (currentPage < urls.length - 1) { currentPage++; render(); } };

        render();
        document.body.appendChild(overlay);
    };

    // Load on init
    loadDocuments();
})();
</script>