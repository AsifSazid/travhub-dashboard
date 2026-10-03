<?php if ($canManagePerms): ?>
<!-- ── Permissions Section ─────────────────────────────────────── -->
<section id="mpsec-permissions" class="mp-section hidden">

    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-2">
            <i class="fas fa-shield-halved text-lg text-purple-500"></i>
            <div>
                <h2 class="text-base font-bold text-gray-800">Permissions</h2>
                <p class="text-xs text-gray-500">Manage employee access rights</p>
            </div>
        </div>
        <?php if ($myRole === '0'): ?>
        <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-red-50 text-red-600 border border-red-200">
            <i class="fas fa-crown text-[9px]"></i> Super Admin
        </span>
        <?php else: ?>
        <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-purple-50 text-purple-600 border border-purple-200">
            <i class="fas fa-key text-[9px]"></i> Full Access
        </span>
        <?php endif; ?>
    </div>

    <div class="flex flex-col lg:flex-row gap-4">

        <!-- Left: Employee list -->
        <div class="w-full lg:w-72 flex-shrink-0">
            <div class="bg-white rounded-2xl shadow p-3">
                <!-- Search -->
                <div class="relative mb-2">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                    <input id="permEmpSearch" type="text" placeholder="Search employees..."
                        class="w-full pl-8 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-purple-300 focus:border-purple-400 outline-none"
                        oninput="permFilterEmployees()">
                </div>
                <!-- Department filter -->
                <select id="permDeptFilter" onchange="permFilterEmployees()"
                    class="w-full text-xs border border-gray-200 rounded-lg px-2 py-1.5 mb-3 focus:ring-2 focus:ring-purple-300 outline-none text-gray-600">
                    <option value="">All Departments</option>
                </select>

                <!-- Stats row -->
                <div class="flex gap-2 mb-3 text-center">
                    <div class="flex-1 bg-gray-50 rounded-lg py-1.5">
                        <div id="permStatTotal" class="text-base font-bold text-gray-700">—</div>
                        <div class="text-[10px] text-gray-400">Employees</div>
                    </div>
                    <div class="flex-1 bg-purple-50 rounded-lg py-1.5">
                        <div id="permStatMaster" class="text-base font-bold text-purple-600">—</div>
                        <div class="text-[10px] text-gray-400">Full Access</div>
                    </div>
                    <div class="flex-1 bg-blue-50 rounded-lg py-1.5">
                        <div id="permStatAny" class="text-base font-bold text-blue-600">—</div>
                        <div class="text-[10px] text-gray-400">Any Perm</div>
                    </div>
                </div>

                <!-- Loading state -->
                <div id="permEmpLoading" class="py-8 text-center text-gray-400 text-sm">
                    <i class="fas fa-spinner fa-spin mr-1"></i> Loading...
                </div>

                <!-- Employee list -->
                <div id="permEmpList" class="space-y-1 max-h-[420px] overflow-y-auto pr-1"></div>
            </div>
        </div><!-- /employee list -->

        <!-- Right: Permission grid -->
        <div class="flex-1 min-w-0">
            <!-- Placeholder when no employee selected -->
            <div id="permNoSelection" class="bg-white rounded-2xl shadow flex flex-col items-center justify-center py-16 text-center text-gray-400">
                <i class="fas fa-user-shield text-4xl mb-3 text-gray-200"></i>
                <p class="text-sm font-medium">Select an employee</p>
                <p class="text-xs mt-1">Choose an employee from the list to manage their permissions</p>
            </div>

            <!-- Permission grid (shown when employee selected) -->
            <div id="permGrid" class="hidden">
                <!-- Employee header -->
                <div class="bg-white rounded-2xl shadow p-4 mb-3 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-400 to-blue-500 flex items-center justify-center text-white font-bold text-sm flex-shrink-0" id="permSelAvatar"></div>
                    <div class="min-w-0">
                        <div class="font-semibold text-gray-800 text-sm truncate" id="permSelName"></div>
                        <div class="text-xs text-gray-400 truncate" id="permSelDept"></div>
                    </div>
                    <div class="ml-auto flex items-center gap-2">
                        <span id="permSelGrantedBadge" class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-blue-50 text-blue-600 border border-blue-200 hidden"></span>
                        <!-- Master switch — super admin only -->
                        <?php if ($myRole === '0'): ?>
                        <label class="flex items-center gap-1.5 cursor-pointer" title="Full Access — implies all permissions">
                            <span class="text-xs font-semibold text-purple-700">Full Access</span>
                            <div class="relative">
                                <input type="checkbox" id="permMasterToggle" class="sr-only peer" onchange="permToggleMaster(this.checked)">
                                <div class="w-9 h-5 bg-gray-200 rounded-full peer peer-checked:bg-purple-500 transition-colors"></div>
                                <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-all peer-checked:translate-x-4"></div>
                            </div>
                        </label>
                        <?php endif; ?>
                        <button onclick="permGrantAll()" title="Grant all individual permissions"
                            class="text-xs px-2 py-1 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition-colors font-medium">
                            Grant All
                        </button>
                        <button onclick="permRevokeAll()" title="Revoke all individual permissions"
                            class="text-xs px-2 py-1 rounded-lg bg-red-50 text-red-600 border border-red-200 hover:bg-red-100 transition-colors font-medium">
                            Revoke All
                        </button>
                    </div>
                </div>

                <!-- Permission groups -->
                <div id="permGroupsContainer" class="space-y-3"></div>

                <!-- Save notice -->
                <div id="permSaveNotice" class="hidden mt-3 text-center text-xs text-gray-400">
                    <i class="fas fa-check-circle text-emerald-500 mr-1"></i> Changes saved automatically
                </div>
            </div><!-- /permGrid -->
        </div><!-- /right -->

    </div><!-- /flex layout -->

</section><!-- /mpsec-permissions -->
<?php endif; ?>
