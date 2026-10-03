            <!-- ═══════════════════════════════════════════════
                 SECTION: Annual Calendar (Office Journal)
            ════════════════════════════════════════════════ -->
            <div id="mpsec-annual" class="mp-section" style="display:none">
                <div class="bg-white rounded-2xl shadow p-5">

                    <!-- Header -->
                    <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
                        <div>
                            <h3 class="font-semibold text-gray-800 flex items-center gap-2 text-base">
                                <i class="fas fa-calendar-days text-blue-500"></i> Annual Calendar
                                <span class="text-xs font-normal text-gray-400">— Office Journal</span>
                                <?php if ($isHR): ?>
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 flex items-center gap-1">
                                    <i class="fas fa-shield-halved"></i> Admin View
                                </span>
                                <?php endif; ?>
                            </h3>
                            <p class="text-xs text-gray-400 mt-0.5">
                                <?php if ($isHR): ?>
                                    All employees' leave, attendance, and office events are visible
                                <?php else: ?>
                                    Click any day to see details and add planning notes
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <button id="annualPrevYear" class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm flex items-center justify-center transition">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <span id="annualYearLabel" class="font-bold text-gray-800 text-base min-w-[3rem] text-center"></span>
                            <button id="annualNextYear" class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm flex items-center justify-center transition">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                            <button id="annualTodayBtn" class="px-3 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-medium flex items-center gap-1.5 transition">
                                <i class="fas fa-crosshairs"></i> Today
                            </button>
                            <?php if ($isHR): ?>
                            <button id="annualAddEventBtn" class="px-3 py-1.5 rounded-lg bg-purple-50 hover:bg-purple-100 text-purple-700 text-xs font-medium flex items-center gap-1.5 transition">
                                <i class="fas fa-plus"></i> Add Event
                            </button>
                            <?php endif; ?>
                            <button id="annualPrintBtn" class="px-3 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-medium flex items-center gap-1.5 transition">
                                <i class="fas fa-print"></i> Print
                            </button>
                        </div>
                    </div>

                    <!-- Legend -->
                    <div class="flex flex-wrap gap-x-4 gap-y-1.5 mb-4 text-xs text-gray-500">
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm inline-block bg-red-100 border border-red-300"></span>Public Holiday</span>
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm inline-block bg-orange-100 border border-orange-300"></span>Office Closed</span>
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm inline-block bg-yellow-50 border border-yellow-300"></span>Optional</span>
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm inline-block bg-pink-100 border border-pink-300"></span>On Leave</span>
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm inline-block bg-green-100 border border-green-300"></span>Present</span>
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm inline-block bg-rose-50 border border-rose-300"></span>Absent</span>
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm inline-block bg-slate-100 border border-slate-300"></span>Weekend</span>
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm inline-block bg-violet-100 border border-violet-300"></span>Has Note</span>
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm inline-block bg-indigo-100 border border-indigo-300"></span>Event</span>
                    </div>

                    <!-- Stats bar -->
                    <div id="annualStats" class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4"></div>

                    <!-- Main layout: calendar + day planner panel -->
                    <div class="flex gap-4 items-start" id="annualMainLayout">

                        <!-- Calendar grid (left) -->
                        <div class="flex-1 min-w-0">
                            <div id="annualGrid" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                                <div class="col-span-full text-center py-12 text-gray-400">
                                    <i class="fas fa-spinner fa-spin text-2xl mb-2 block"></i>
                                    Loading calendar…
                                </div>
                            </div>
                        </div>

                        <!-- Day Planner panel (right) -->
                        <div id="annualDayPanel" class="hidden w-80 flex-shrink-0 border border-gray-100 rounded-2xl overflow-hidden shadow-sm sticky top-4">
                            <!-- Panel header -->
                            <div class="bg-gradient-to-r from-blue-600 to-blue-500 text-white px-4 py-3 flex items-center justify-between">
                                <div>
                                    <div id="annualDayPanelDate" class="font-bold text-sm leading-tight"></div>
                                    <div id="annualDayPanelSub" class="text-xs opacity-80 mt-0.5"></div>
                                </div>
                                <button id="annualDayPanelClose" class="w-6 h-6 rounded-full bg-white/20 hover:bg-white/30 flex items-center justify-center text-xs transition">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>

                            <!-- Day status chip(s) -->
                            <div id="annualDayStatusChip" class="px-4 py-2 border-b border-gray-100 text-xs flex flex-wrap gap-1"></div>

                            <!-- Who's away today (admin: all employees; employee: hidden) -->
                            <div id="annualDayAwayBlock" class="hidden px-4 py-2 border-b border-gray-100">
                                <div class="flex items-center justify-between mb-1">
                                    <div class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Away This Day</div>
                                    <span id="annualDayAwayCount" class="text-[10px] text-gray-400"></span>
                                </div>
                                <div id="annualDayAwayList" class="space-y-1 text-xs text-gray-700 max-h-32 overflow-y-auto"></div>
                            </div>

                            <!-- Events block -->
                            <div id="annualDayEventsBlock" class="hidden px-4 py-2 border-b border-gray-100">
                                <div class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Events</div>
                                <div id="annualDayEventsList" class="space-y-1 text-xs"></div>
                            </div>

                            <!-- Notes section -->
                            <div class="p-4">
                                <div class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-2">Planning Notes</div>

                                <!-- Add note form -->
                                <div id="annualNoteForm" class="mb-3">
                                    <input type="hidden" id="annualNoteDate">
                                    <input type="hidden" id="annualNoteSysId">
                                    <input id="annualNoteTitle" type="text" placeholder="Title (optional)"
                                        class="w-full border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs mb-1.5 focus:outline-none focus:ring-2 focus:ring-blue-300">
                                    <textarea id="annualNoteBody" rows="3" placeholder="Write a planning note…"
                                        class="w-full border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs resize-none focus:outline-none focus:ring-2 focus:ring-blue-300"></textarea>
                                    <!-- Repeat yearly toggle -->
                                    <label class="flex items-center gap-2 mt-2 cursor-pointer select-none">
                                        <div class="relative">
                                            <input type="checkbox" id="annualNoteRepeat" class="sr-only peer">
                                            <div class="w-8 h-4 bg-gray-200 peer-checked:bg-blue-500 rounded-full transition"></div>
                                            <div class="absolute top-0.5 left-0.5 w-3 h-3 bg-white rounded-full shadow transition peer-checked:translate-x-4"></div>
                                        </div>
                                        <span class="text-[10px] text-gray-500 font-medium">
                                            <i class="fas fa-rotate mr-0.5"></i> Remind every year on this date
                                        </span>
                                    </label>
                                    <div class="flex gap-1.5 mt-2">
                                        <button id="annualNoteSave" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg py-1.5 transition">
                                            <i class="fas fa-save mr-1"></i>Save
                                        </button>
                                        <button id="annualNoteCancel" class="hidden px-3 bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs rounded-lg py-1.5 transition">Cancel</button>
                                    </div>
                                </div>

                                <!-- Notes list -->
                                <div id="annualNotesList" class="space-y-2 max-h-64 overflow-y-auto"></div>
                            </div>
                        </div>

                    </div><!-- /annualMainLayout -->

                    <!-- Holiday list -->
                    <div id="annualHolidayList" class="mt-6 hidden">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                            <i class="fas fa-star text-yellow-500"></i> Holidays This Year
                        </h4>
                        <div id="annualHolidayItems" class="divide-y divide-gray-100 rounded-xl border border-gray-100 overflow-hidden text-sm"></div>
                    </div>

                </div>
            </div><!-- /annual -->

<?php if ($isHR): ?>
<!-- ── Add/Edit Office Event Modal (admin only) ─────────── -->
<div id="annualEventModal" class="mp-modal-bg hidden" style="z-index:10000">
    <div class="mp-modal max-w-md">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                <i class="fas fa-calendar-plus text-purple-500"></i>
                <span id="annualEventModalTitle">Add Office Event</span>
            </h3>
            <button onclick="document.getElementById('annualEventModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
        </div>
        <form id="annualEventForm" class="space-y-3">
            <input type="hidden" id="annualEventSysId">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Date *</label>
                <input type="date" id="annualEventDate" required
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Title *</label>
                <input type="text" id="annualEventTitle" required placeholder="e.g. Team Outing, Training Day"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
                <textarea id="annualEventDesc" rows="2" placeholder="Optional details…"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-purple-300"></textarea>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Visibility *</label>
                <select id="annualEventVisibility"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300">
                    <option value="public">🌐 Public — visible to all employees</option>
                    <option value="private">🔒 Private — specific employees only</option>
                </select>
            </div>
            <!-- Specific employees picker (shown when private) -->
            <div id="annualEventEmpPicker" class="hidden">
                <label class="block text-xs font-medium text-gray-600 mb-1">Select Employees</label>
                <div id="annualEventEmpList" class="border border-gray-200 rounded-lg p-2 max-h-40 overflow-y-auto space-y-1 text-sm text-gray-700">
                    <div class="text-center text-gray-400 text-xs py-2"><i class="fas fa-spinner fa-spin"></i> Loading…</div>
                </div>
            </div>
            <div class="flex gap-2 pt-2">
                <button type="submit" id="annualEventSaveBtn" class="flex-1 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-xl py-2 transition">
                    <i class="fas fa-save mr-1"></i>Save Event
                </button>
                <button type="button" onclick="document.getElementById('annualEventModal').classList.add('hidden')"
                    class="px-4 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm rounded-xl py-2 transition">Cancel</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
