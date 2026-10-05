/**
 * FILE PATH: /pages/task-tabs/ww-umrah/summary.js
 * Tab: Trip Summary — read-only view of confirmed service data
 */

window._renderUmSummary = function() {
    const panel   = document.getElementById('um-panel-summary');
    const summary = window._um.summary;

    if (!summary) {
        panel.innerHTML = '<div class="text-center py-12 text-gray-300"><i class="fas fa-spinner fa-spin text-2xl"></i></div>';
        return;
    }

    const flights   = summary.flights   ?? [];
    const hotels    = summary.hotels    ?? [];
    const itinerary = summary.itinerary ?? [];

    panel.innerHTML = `
    <div class="space-y-5">

        <!-- Flights -->
        <div>
            <div class="flex items-center gap-2 mb-3">
                <i class="fas fa-plane text-indigo-500"></i>
                <h3 class="text-sm font-bold text-gray-700">Flights</h3>
                <span class="text-[10px] bg-indigo-50 text-indigo-600 px-2 py-0.5 rounded font-semibold">${flights.length} confirmed</span>
                ${!flights.length ? `<span class="text-[10px] text-gray-400 ml-auto">No confirmed air tickets yet</span>` : ''}
            </div>
            ${flights.map(f => {
                const segs = f.segments ?? [];
                return `<div class="bg-indigo-50 border border-indigo-100 rounded-xl p-3 mb-2">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-indigo-800">${_ume(f.airline||'—')}</span>
                        ${f.pnr ? `<span class="text-[10px] bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded font-mono">PNR: ${_ume(f.pnr)}</span>` : ''}
                    </div>
                    ${segs.length ? `<div class="space-y-1">
                        ${segs.map((s,i) => `<div class="flex items-center gap-2 text-xs text-gray-600">
                            <span class="text-[10px] text-indigo-400 font-mono w-4">${i+1}</span>
                            <span class="font-semibold">${_ume(s.flight||s.flight_no||'')}</span>
                            <span>${_ume(s.route||s.route_code||'')}</span>
                            <span class="text-gray-400">${_ume(s.date||'')} ${_ume(s.departure||s.dep_time||'')}</span>
                            <i class="fas fa-arrow-right text-[9px] text-gray-300"></i>
                            <span class="text-gray-400">${_ume(s.arrival||s.arr_time||'')}</span>
                        </div>`).join('')}
                    </div>` : '<p class="text-xs text-gray-400">No segment data</p>'}
                    ${f.ticket_nos?.length ? `<div class="mt-2 text-[10px] text-gray-400">Tickets: ${f.ticket_nos.join(', ')}</div>` : ''}
                </div>`;
            }).join('')}
        </div>

        <!-- Hotels -->
        <div>
            <div class="flex items-center gap-2 mb-3">
                <i class="fas fa-hotel text-orange-500"></i>
                <h3 class="text-sm font-bold text-gray-700">Hotels</h3>
                <span class="text-[10px] bg-orange-50 text-orange-600 px-2 py-0.5 rounded font-semibold">${hotels.length} confirmed</span>
                ${!hotels.length ? `<span class="text-[10px] text-gray-400 ml-auto">No confirmed hotels yet</span>` : ''}
            </div>
            ${hotels.map(h => `
            <div class="bg-orange-50 border border-orange-100 rounded-xl p-3 mb-2">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-bold text-orange-800">${_ume(h.hotel_name||'—')} ${h.star_rating ? '<span style="color:#F59E0B;">'+'★'.repeat(h.star_rating)+'</span>' : ''}</span>
                    <span class="text-[10px] text-orange-600">${_ume(h.city||'')}${h.city&&h.country?', ':' '}${_ume(h.country||'')}</span>
                </div>
                <div class="grid grid-cols-3 gap-2 text-xs text-gray-600">
                    <div><span class="text-gray-400">Check-in</span><br><b>${_ume(h.check_in||'—')}</b></div>
                    <div><span class="text-gray-400">Check-out</span><br><b>${_ume(h.check_out||'—')}</b></div>
                    <div><span class="text-gray-400">Nights</span><br><b>${h.nights||'—'}</b></div>
                    <div><span class="text-gray-400">Room</span><br><b>${_ume(h.room_type||'—')}</b></div>
                    <div><span class="text-gray-400">Meal</span><br><b>${_ume(UM_MEAL_PLANS[h.meal_plan]||h.meal_plan||'—')}</b></div>
                    <div><span class="text-gray-400">Rooms</span><br><b>${h.rooms||1}</b></div>
                </div>
                ${h.booking_ref ? `<div class="mt-1 text-[10px] text-gray-400">Booking Ref: ${_ume(h.booking_ref)}</div>` : ''}
            </div>`).join('')}
        </div>

        <!-- Itinerary -->
        <div>
            <div class="flex items-center gap-2 mb-3">
                <i class="fas fa-map-marked-alt text-green-500"></i>
                <h3 class="text-sm font-bold text-gray-700">Itinerary</h3>
                ${!itinerary.length ? `<span class="text-[10px] text-gray-400 ml-auto">No itinerary from Package service yet</span>` : ''}
            </div>
            ${itinerary.length ? `
            <div class="space-y-2">
                ${itinerary.map((item,i) => `
                <div class="flex gap-3 p-2">
                    <div class="flex flex-col items-center">
                        <div class="w-2 h-2 rounded-full bg-green-500 mt-1 flex-shrink-0"></div>
                        ${i < itinerary.length-1 ? '<div class="w-px flex-1 bg-green-200 mt-1"></div>' : ''}
                    </div>
                    <div class="flex-1 pb-2">
                        <div class="text-xs font-semibold text-gray-800">${_ume(item.title||item.day_title||'')}</div>
                        <div class="text-[11px] text-gray-400">${_ume(item.date||'')} ${_ume(item.time||'')}</div>
                        ${item.description ? `<div class="text-[11px] text-gray-500 mt-0.5">${_ume(item.description)}</div>` : ''}
                    </div>
                </div>`).join('')}
            </div>` : ''}
        </div>

        ${!flights.length && !hotels.length && !itinerary.length ? `
        <div class="text-center py-12 text-gray-300">
            <i class="fas fa-list-check text-4xl mb-3 block"></i>
            <p class="text-sm font-medium">No confirmed services yet</p>
            <p class="text-xs mt-1">Confirm Air Ticket, Hotel, or Package tasks to see data here.</p>
        </div>` : ''}

    </div>`;
};