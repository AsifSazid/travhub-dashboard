/**
 * FILE PATH: /pages/task-tabs/ww-umrah/idcard.js
 * Tab: ID Cards — link to generate-umrah-id-card.php with group + summary data
 */

window._renderUmIdCard = function() {
    const panel   = document.getElementById('um-panel-idcard');
    const group   = window._um.group;
    const gData   = group?.group;
    const members = group?.members ?? [];
    const summary = window._um.summary;

    if (!gData) {
        panel.innerHTML = `<div class="text-center py-12 text-gray-300">
            <i class="fas fa-id-card text-4xl mb-2 block"></i>
            <p class="text-sm">Create a group and add travelers first</p>
        </div>`;
        return;
    }

    // Pull flight/hotel data for ID card context
    const flights = summary?.flights ?? [];
    const hotels  = summary?.hotels  ?? [];

    // Derive arrival/return from first confirmed flight
    const firstFlight = flights[0];
    const segs        = firstFlight?.segments ?? [];
    const arrivalDate = segs[0]?.arrival_date ?? segs[0]?.date ?? '';
    const returnDate  = segs.length > 1 ? (segs[segs.length-1]?.departure_date ?? segs[segs.length-1]?.date ?? '') : '';

    // Makkah + Madinah hotels
    const makkahHotel  = hotels.find(h => (h.city||'').toLowerCase().includes('mak') || (h.city||'').toLowerCase().includes('mec'));
    const madinahHotel = hotels.find(h => (h.city||'').toLowerCase().includes('mad') || (h.city||'').toLowerCase().includes('med'));

    const idCardUrl = `generate-umrah-id-card.php?group_sys_id=${encodeURIComponent(gData.sys_id)}`;

    panel.innerHTML = `
    <div class="max-w-lg">
        <!-- Summary for ID card -->
        <div class="bg-amber-50 border border-amber-100 rounded-xl p-4 mb-5">
            <div class="text-xs font-bold text-amber-700 uppercase mb-2"><i class="fas fa-info-circle mr-1"></i>Data for ID Card</div>
            <div class="grid grid-cols-2 gap-2 text-xs">
                <div><span class="text-gray-400">Group</span><br><b>${_ume(gData.group_name)}</b></div>
                <div><span class="text-gray-400">Travelers</span><br><b>${members.length}</b></div>
                <div><span class="text-gray-400">Arrival KSA</span><br><b class="${arrivalDate?'text-green-600':'text-red-400'}">${_ume(arrivalDate||'Not set — confirm air ticket')}</b></div>
                <div><span class="text-gray-400">Return</span><br><b class="${returnDate?'text-green-600':'text-red-400'}">${_ume(returnDate||'Not set')}</b></div>
                <div><span class="text-gray-400">Makkah Hotel</span><br><b class="${makkahHotel?'text-green-600':'text-red-400'}">${_ume(makkahHotel?.hotel_name||'Not set — confirm hotel')}</b></div>
                <div><span class="text-gray-400">Madinah Hotel</span><br><b class="${madinahHotel?'text-green-600':'text-red-400'}">${_ume(madinahHotel?.hotel_name||'Not set — confirm hotel')}</b></div>
            </div>
            ${(!arrivalDate || !makkahHotel) ? `
            <div class="mt-2 text-[10px] text-amber-600">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                ID Card-এ কিছু data missing — Air Ticket ও Hotel সার্ভিস confirm করলে automatically আসবে।
            </div>` : ''}
        </div>

        <!-- Travelers list preview -->
        <div class="mb-5">
            <div class="text-xs font-bold text-gray-500 uppercase mb-2">Travelers (${members.length})</div>
            <div class="space-y-1.5">
                ${members.map(m => `
                <div class="flex items-center gap-2 text-xs p-2 bg-white border border-gray-100 rounded-lg">
                    ${m.is_leader ? '<span class="text-amber-500 text-[10px]"><i class="fas fa-star"></i></span>' : '<span class="w-3"></span>'}
                    <span class="font-medium text-gray-800 flex-1">${_ume(m.name||'Unknown')}</span>
                    <span class="text-gray-400">${_ume(m.passport_no||'—')}</span>
                    <span class="text-gray-300">·</span>
                    <span class="text-gray-400">${_ume(m.roaming_phone||'—')}</span>
                </div>`).join('')}
            </div>
        </div>

        <!-- Generate button -->
        <a href="${idCardUrl}" target="_blank"
            class="flex items-center justify-center gap-2 w-full py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-sm font-semibold transition">
            <i class="fas fa-id-card"></i>Generate ID Cards
        </a>
        <p class="text-[10px] text-gray-400 text-center mt-2">Opens in a new tab — Print / PDF / Download per card</p>

        <!-- QR Public page link -->
        ${gData.sys_id ? `
        <div class="mt-4 p-3 bg-gray-50 border border-gray-100 rounded-xl flex items-center justify-between">
            <div>
                <div class="text-xs font-semibold text-gray-700">QR Public Page</div>
                <div class="text-[10px] text-gray-400 mt-0.5">Scan করলে traveler info দেখাবে (login ছাড়া)</div>
            </div>
            <a href="umrah-group-public.php?g=${encodeURIComponent(gData.sys_id)}" target="_blank"
                class="flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold transition">
                <i class="fas fa-qrcode"></i>Open
            </a>
        </div>` : ''}
    </div>`;
};