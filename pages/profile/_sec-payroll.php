            <!-- ═══════════════════════════════════════════════
                 SECTION: Payroll
            ════════════════════════════════════════════════ -->
            <div id="mpsec-payroll" class="mp-section" style="display:none">
                <div class="bg-white rounded-2xl shadow p-5 mb-4 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-green-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-money-bill-wave text-green-600"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-bold text-gray-800">My Payroll</h3>
                        <p class="text-xs text-gray-400">Monthly salary slips & breakdown</p>
                    </div>
                    <select id="mpPayrollYear" class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-400"></select>
                </div>
                <div id="mpPayrollList" class="space-y-3">
                    <div class="text-center py-10 text-gray-400 text-sm">
                        <i class="fas fa-spinner fa-spin text-3xl mb-3 block text-gray-200"></i>
                        Loading payroll data…
                    </div>
                </div>

                <!-- Payroll Detail Modal -->
                <div id="mpPayrollModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999;align-items:center;justify-content:center" class="flex">
                    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
                        <div class="bg-gradient-to-r from-green-600 to-emerald-500 px-6 py-4 flex items-center justify-between">
                            <div>
                                <div id="mpPayrollModalMonth" class="text-white font-bold text-lg"></div>
                                <div id="mpPayrollModalStatus" class="text-green-100 text-xs mt-0.5"></div>
                            </div>
                            <button id="mpPayrollModalClose" class="text-white/70 hover:text-white text-2xl leading-none">&times;</button>
                        </div>
                        <div id="mpPayrollModalBody" class="p-5 max-h-[70vh] overflow-y-auto"></div>
                    </div>
                </div>
            </div>

