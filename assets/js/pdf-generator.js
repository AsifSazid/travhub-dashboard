/**
 * TravHub — PDF Generator
 * assets/js/pdf-generator.js
 *
 * Usage:
 *   PdfGenerator.open(sys_id)          — show modal, user picks template, downloads
 *   PdfGenerator.openAfterFinalize(sys_id) — same but with "finalized" banner
 */

const PdfGenerator = (() => {

    const API_PATH = '../api/packages/generate-pdf.php';

    function open(sys_id, afterFinalize = false) {
        // Remove existing modal if any
        document.getElementById('pdfGenModal')?.remove();

        const modal = document.createElement('div');
        modal.id = 'pdfGenModal';
        modal.className = 'fixed inset-0 z-[70] flex items-center justify-center bg-black/50 backdrop-blur-sm p-6';
        modal.innerHTML = `
        <div class="w-full max-w-[900px] h-[680px] max-h-[calc(100vh-3rem)] flex flex-col bg-white rounded-2xl shadow-2xl">

            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:#1A2039;">
                        <i class="fa-solid fa-file-pdf text-white text-sm"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-800">Download Package PDF</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Choose your preferred document format</p>
                    </div>
                </div>
                <button id="pdfModalClose" class="text-gray-400 hover:text-gray-600 w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="flex-1 overflow-y-auto overflow-x-hidden px-6 py-5">

                <!-- Finalized banner -->
                ${afterFinalize ? `
                <div class="flex items-center gap-3 bg-green-50 border border-green-200 rounded-xl px-4 py-3 mb-5">
                    <i class="fa-solid fa-flag-checkered text-green-600 text-lg flex-shrink-0"></i>
                    <div>
                        <p class="text-sm font-semibold text-green-800">Package Finalized Successfully!</p>
                        <p class="text-xs text-green-600 mt-0.5">Your package has been locked and saved. Select a PDF template to generate your client document.</p>
                    </div>
                </div>` : ''}

                <!-- Template cards -->
                <p class="text-sm font-semibold text-gray-700 mb-3">Select PDF Template</p>
                <div class="grid grid-cols-2 gap-4 mb-6">

                    <!-- Detailed -->
                    <label class="pdf-tmpl-option cursor-pointer block" data-value="detailed">
                        <input type="radio" name="pdf_tmpl" value="detailed" class="sr-only" checked>
                        <div class="tmpl-card border-2 border-[#1A2039] bg-[#1A2039]/5 rounded-2xl p-4 transition">
                            <div class="flex items-start gap-3 mb-3">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background:#1A2039;">
                                    <i class="fa-solid fa-file-lines text-white"></i>
                                </div>
                                <div>
                                    <p class="font-bold text-gray-800">Detailed</p>
                                    <p class="text-xs text-gray-500 mt-0.5">Full client proposal document</p>
                                </div>
                            </div>
                            <!-- Mini preview of what's inside -->
                            <div class="bg-white rounded-xl border border-gray-100 p-3 space-y-1.5">
                                ${['Cover page with package info','Day-by-day itinerary','Activity & transfer details','Hotel accommodation','Inclusions & Exclusions','Full pricing breakdown'].map(i =>
                                    `<div class="flex items-center gap-2 text-xs text-gray-600">
                                        <i class="fa-solid fa-check text-[#50BC81] flex-shrink-0" style="font-size:10px"></i>${i}
                                    </div>`
                                ).join('')}
                            </div>
                        </div>
                    </label>

                    <!-- Bullet Points -->
                    <label class="pdf-tmpl-option cursor-pointer block" data-value="bullet">
                        <input type="radio" name="pdf_tmpl" value="bullet" class="sr-only">
                        <div class="tmpl-card border-2 border-gray-200 bg-white rounded-2xl p-4 hover:border-gray-400 transition">
                            <div class="flex items-start gap-3 mb-3">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 bg-gray-100">
                                    <i class="fa-solid fa-list text-gray-600"></i>
                                </div>
                                <div>
                                    <p class="font-bold text-gray-800">Bullet Points</p>
                                    <p class="text-xs text-gray-500 mt-0.5">Concise summary format</p>
                                </div>
                            </div>
                            <div class="bg-white rounded-xl border border-gray-100 p-3 space-y-1.5">
                                ${['Package overview','Day-by-day in bullet format','Activities & transfers listed','Inclusions & Exclusions','Price summary only'].map(i =>
                                    `<div class="flex items-center gap-2 text-xs text-gray-600">
                                        <i class="fa-solid fa-check text-[#50BC81] flex-shrink-0" style="font-size:10px"></i>${i}
                                    </div>`
                                ).join('')}
                            </div>
                        </div>
                    </label>
                </div>

                <!-- Info note -->
                <div class="flex items-start gap-2 bg-blue-50 border border-blue-100 rounded-xl px-4 py-3 text-xs text-blue-700">
                    <i class="fa-solid fa-circle-info mt-0.5 flex-shrink-0"></i>
                    <span>PDF is generated in real-time and opens in a new tab. No file is stored on the server.</span>
                </div>

                <div id="pdfGenError" class="hidden mt-3 text-sm text-red-600 bg-red-50 rounded-xl px-4 py-3"></div>
            </div>

            <!-- Footer -->
            <div class="flex gap-3 px-6 py-4 border-t border-gray-100 flex-shrink-0">
                <button id="pdfModalCancel" class="flex-1 py-2.5 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 font-medium transition">
                    ${afterFinalize ? 'Skip for now' : 'Cancel'}
                </button>
                <button id="pdfDownloadBtn" class="flex-1 py-2.5 rounded-xl text-white font-semibold transition flex items-center justify-center gap-2" style="background:#1A2039;">
                    <i class="fa-solid fa-file-arrow-down"></i> Generate & Open PDF
                </button>
            </div>
        </div>`;

        document.body.appendChild(modal);

        // Template selection styling
        modal.querySelectorAll('.pdf-tmpl-option').forEach(opt => {
            opt.addEventListener('click', () => {
                modal.querySelectorAll('.tmpl-card').forEach(c => {
                    c.style.borderColor = '#e5e7eb';
                    c.style.background  = '#fff';
                });
                const card = opt.querySelector('.tmpl-card');
                card.style.borderColor = '#1A2039';
                card.style.background  = 'rgba(26,32,57,0.05)';
                opt.querySelector('input').checked = true;
            });
        });

        // Close handlers
        modal.querySelector('#pdfModalClose').addEventListener('click',  () => modal.remove());
        modal.querySelector('#pdfModalCancel').addEventListener('click', () => modal.remove());
        modal.addEventListener('click', e => { if (e.target === modal) modal.remove(); });

        // Download
        modal.querySelector('#pdfDownloadBtn').addEventListener('click', () => {
            const template = modal.querySelector('input[name="pdf_tmpl"]:checked')?.value || 'detailed';
            const btn      = modal.querySelector('#pdfDownloadBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Generating…';

            const url = `${API_PATH}?sys_id=${encodeURIComponent(sys_id)}&template=${template}`;
            const win = window.open(url, '_blank');

            // If popup blocked, fallback to direct link
            setTimeout(() => {
                if (!win || win.closed || typeof win.closed === 'undefined') {
                    const a   = document.createElement('a');
                    a.href    = url;
                    a.target  = '_blank';
                    a.click();
                }
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-file-arrow-down mr-2"></i>Generate & Open PDF';
                modal.remove();
            }, 800);
        });
    }

    function openAfterFinalize(sys_id) {
        open(sys_id, true);
    }

    return { open, openAfterFinalize };

})();