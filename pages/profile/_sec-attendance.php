            <!-- ═══════════════════════════════════════════════
                 SECTION: Attendance & Leave
            ════════════════════════════════════════════════ -->
            <div id="mpsec-attendance" class="mp-section" style="display:none">

                <!-- Leave balance cards -->
                <div class="bg-white rounded-2xl shadow p-5 mb-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-umbrella-beach text-blue-500"></i> Leave Balance
                            <span id="mpLbYear" class="text-sm text-gray-400 font-normal"></span>
                        </h3>
                        <button id="mpApplyLeaveBtn"
                            class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg flex items-center gap-2 transition">
                            <i class="fas fa-plus"></i> Apply Leave
                        </button>
                    </div>
                    <div id="mpLeaveBalances" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                        <div class="col-span-full text-center py-6 text-gray-400 text-sm">
                            <i class="fas fa-spinner fa-spin mr-2"></i>Loading balances…
                        </div>
                    </div>
                </div>

                <!-- Calendar + Notes -->
                <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">

                    <!-- Calendar -->
                    <div class="lg:col-span-3 bg-white rounded-2xl shadow p-5">
                        <!-- Nav -->
                        <div class="flex items-center justify-between mb-4">
                            <button id="mpCalPrev" class="w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center text-gray-500 transition">
                                <i class="fas fa-chevron-left text-sm"></i>
                            </button>
                            <h3 id="mpCalTitle" class="font-semibold text-gray-800 text-base"></h3>
                            <button id="mpCalNext" class="w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center text-gray-500 transition">
                                <i class="fas fa-chevron-right text-sm"></i>
                            </button>
                        </div>

                        <!-- Day headers -->
                        <div class="mp-cal-grid mb-2">
                            <?php foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d): ?>
                            <div class="text-center text-xs font-semibold text-gray-400 py-1"><?= $d ?></div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Day cells -->
                        <div class="mp-cal-grid" id="mpCalGrid">
                            <div class="col-span-7 text-center py-6 text-gray-400 text-sm">
                                <i class="fas fa-spinner fa-spin mr-2"></i>Loading calendar…
                            </div>
                        </div>

                        <!-- Legend -->
                        <div class="mt-4 flex flex-wrap gap-2 text-xs">
                            <?php
                            $legends = [
                                ['mp-d-present','P','Present'],
                                ['mp-d-absent','A','Absent'],
                                ['mp-d-late','L','Late'],
                                ['mp-d-on_leave','OL','On Leave'],
                                ['mp-d-holiday','H','Holiday'],
                                ['mp-d-weekend','','Weekend'],
                            ];
                            foreach ($legends as [$cls,$code,$lbl]): ?>
                            <span class="flex items-center gap-1">
                                <span class="w-5 h-5 rounded <?= $cls ?> flex items-center justify-center font-bold text-xs"><?= $code ?></span>
                                <span class="text-gray-500"><?= $lbl ?></span>
                            </span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Notes for this month (beside calendar) -->
                    <div class="lg:col-span-2 bg-white rounded-2xl shadow p-5 flex flex-col">
                        <h3 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                            <i class="fas fa-sticky-note text-yellow-500"></i> Notes
                            <span id="mpNotesMonthLabel" class="text-xs font-normal text-gray-400 ml-1"></span>
                        </h3>
                        <p id="mpNotesHint" class="text-xs text-gray-400 mb-3">Select a day on the calendar to view or add notes.</p>
                        <div id="mpNotesSidebar" class="flex-1 space-y-2 overflow-y-auto" style="max-height:420px">
                            <p class="text-xs text-gray-400 text-center py-6">No notes this month.</p>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Leaves (below calendar) -->
                <div class="bg-white rounded-2xl shadow p-5 mt-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-list-check text-indigo-500"></i> My Leaves
                        </h3>
                        <button id="mpViewAllLeavesBtn" class="text-xs text-blue-600 hover:text-blue-800 font-semibold border border-blue-200 rounded-lg px-3 py-1 hover:bg-blue-50 transition">
                            View All
                        </button>
                    </div>
                    <div id="mpLeaveList" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="col-span-full text-center py-6 text-gray-400 text-sm">
                            <i class="fas fa-spinner fa-spin mr-2"></i>Loading…
                        </div>
                    </div>
                </div>

                <!-- Monthly summary (attendance stats) -->
                <div class="bg-white rounded-2xl shadow p-5 mt-4">
                    <h3 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                        <i class="fas fa-chart-bar text-green-500"></i> Monthly Summary
                    </h3>
                    <div id="mpAttSummary" class="grid grid-cols-3 sm:grid-cols-6 gap-3 text-center">
                        <?php
                        $sumItems = [
                            ['id'=>'mpS-present',  'label'=>'Present',  'color'=>'text-green-600',  'bg'=>'bg-green-50'],
                            ['id'=>'mpS-absent',   'label'=>'Absent',   'color'=>'text-red-600',    'bg'=>'bg-red-50'],
                            ['id'=>'mpS-late',     'label'=>'Late',     'color'=>'text-yellow-600', 'bg'=>'bg-yellow-50'],
                            ['id'=>'mpS-half_day', 'label'=>'Half Day', 'color'=>'text-indigo-600', 'bg'=>'bg-indigo-50'],
                            ['id'=>'mpS-on_leave', 'label'=>'On Leave', 'color'=>'text-pink-600',   'bg'=>'bg-pink-50'],
                            ['id'=>'mpS-not_marked','label'=>'Unknown', 'color'=>'text-gray-400',   'bg'=>'bg-gray-50'],
                        ];
                        foreach ($sumItems as $s): ?>
                        <div class="<?= $s['bg'] ?> rounded-xl p-3">
                            <div id="<?= $s['id'] ?>" class="text-2xl font-bold <?= $s['color'] ?>">—</div>
                            <div class="text-xs text-gray-500 mt-0.5"><?= $s['label'] ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div><!-- /attendance -->


