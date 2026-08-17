<?php
/**
 * FILE PATH: pages/st-work-board.php
 * Work Board tab — show-travelers.php এ include হয়।
 * এই traveler কোন কোন work এ আছে সেটার list।
 */
?>

<div class="h-full flex flex-col overflow-hidden">

    <div class="flex items-center justify-between mb-3 flex-shrink-0">
        <h3 class="text-sm font-semibold text-gray-700">Linked Works</h3>
        <span id="workBoardCount" class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full hidden"></span>
    </div>

    <!-- Loading -->
    <div id="workBoardLoading" class="flex items-center gap-2 text-sm text-gray-500 py-4">
        <span class="w-4 h-4 border-2 border-gray-300 border-t-blue-500 rounded-full animate-spin"></span>
        Loading works...
    </div>

    <!-- List -->
    <div id="workBoardList" class="hidden flex-1 overflow-y-auto space-y-2"></div>

    <!-- Empty -->
    <div id="workBoardEmpty" class="hidden flex-1 flex flex-col items-center justify-center text-center text-gray-400 py-12">
        <i class="fas fa-briefcase text-4xl mb-3"></i>
        <p class="font-medium">No works linked</p>
        <p class="text-sm mt-1">This traveler has not been added to any work yet</p>
    </div>

</div>

<script>
(function() {
    const TRAVELER_ID = <?= json_encode($travelerId) ?>;

    const SERVICE_LABELS = {
        air_ticket: 'Air Ticket', hotel: 'Hotel', visa: 'Visa',
        package: 'Package', umrah: 'Umrah', transport: 'Transport',
    };
    const SERVICE_COLORS = {
        air_ticket: 'blue', hotel: 'purple', visa: 'green',
        package: 'orange', umrah: 'emerald', transport: 'gray',
    };

    async function loadWorks() {
        try {
            const res  = await fetch(`/api/travelers/get-traveler-works.php?traveler_id=${TRAVELER_ID}`);
            const data = await res.json();

            document.getElementById('workBoardLoading').classList.add('hidden');

            if (!data.success) {
                document.getElementById('workBoardList').innerHTML =
                    `<p class="text-red-500 text-sm">${data.message || 'Failed to load'}</p>`;
                document.getElementById('workBoardList').classList.remove('hidden');
                return;
            }

            if (data.total === 0) {
                document.getElementById('workBoardEmpty').classList.remove('hidden');
                return;
            }

            const countEl = document.getElementById('workBoardCount');
            countEl.textContent = data.total;
            countEl.classList.remove('hidden');

            const list = document.getElementById('workBoardList');
            list.classList.remove('hidden');
            list.innerHTML = data.works.map(work => renderWorkCard(work)).join('');

        } catch (err) {
            document.getElementById('workBoardLoading').classList.add('hidden');
            document.getElementById('workBoardList').innerHTML =
                `<p class="text-red-500 text-sm">Error: ${err.message}</p>`;
            document.getElementById('workBoardList').classList.remove('hidden');
        }
    }

    function renderWorkCard(work) {
        const services = (work.services || []).map(s => {
            const color = SERVICE_COLORS[s] || 'gray';
            const label = SERVICE_LABELS[s] || s;
            return `<span class="text-xs px-2 py-0.5 rounded-full bg-${color}-100 text-${color}-700">${label}</span>`;
        }).join('');

        const date = work.created_at
            ? new Date(work.created_at).toLocaleDateString('en-GB', {day:'2-digit', month:'short', year:'numeric'})
            : '';

        const statusColor = {
            active: 'green', completed: 'blue', cancelled: 'red', pending: 'amber'
        }[work.status] || 'gray';

        return `
            <a href="${work.work_url}"
               class="block bg-white border border-gray-200 rounded-xl px-4 py-3 hover:border-blue-300 hover:shadow-sm transition-all">
                <div class="flex items-start justify-between gap-2">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-mono text-xs font-semibold text-gray-600">${work.sys_id}</span>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-${statusColor}-100 text-${statusColor}-700 capitalize">${work.status || 'active'}</span>
                        </div>
                        <p class="text-sm font-medium text-gray-800 truncate">${escHtml(work.client_name)}</p>
                        ${work.client_phone ? `<p class="text-xs text-gray-400">${escHtml(work.client_phone)}</p>` : ''}
                        <div class="flex flex-wrap gap-1 mt-2">${services}</div>
                    </div>
                    <div class="flex-shrink-0 text-right">
                        <p class="text-xs text-gray-400">${date}</p>
                        <i class="fas fa-chevron-right text-gray-300 text-xs mt-2"></i>
                    </div>
                </div>
            </a>`;
    }

    function escHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({
            '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
        }[c]));
    }

    loadWorks();
})();
</script>