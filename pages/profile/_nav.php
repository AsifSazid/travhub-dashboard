        <!-- Left sidebar nav -->
        <div class="mp-sidenav">
            <div class="bg-white rounded-2xl shadow p-3 flex flex-row md:flex-col gap-1 overflow-x-auto md:overflow-x-visible">
                <p class="hidden md:block text-xs font-semibold text-gray-400 uppercase tracking-widest px-2 mb-1 mt-1">Account</p>

                <button class="mp-nav-btn mp-active" data-mpsec="myinfo">
                    <span class="mp-nav-icon"><i class="fas fa-id-card"></i></span>
                    <span class="hidden md:inline">My Info</span>
                </button>

                <button class="mp-nav-btn" data-mpsec="attendance">
                    <span class="mp-nav-icon"><i class="fas fa-calendar-check"></i></span>
                    <span class="hidden md:inline">Attendance & Leave</span>
                </button>

                <div class="hidden md:block border-t border-gray-100 my-2"></div>
                <p class="hidden md:block text-xs font-semibold text-gray-400 uppercase tracking-widest px-2 mb-1">More</p>

                <button class="mp-nav-btn" data-mpsec="documents">
                    <span class="mp-nav-icon"><i class="fas fa-folder-open"></i></span>
                    <span class="hidden md:inline">Documents</span>
                </button>

                <button class="mp-nav-btn" data-mpsec="credentials">
                    <span class="mp-nav-icon"><i class="fas fa-key"></i></span>
                    <span class="hidden md:inline">Credentials</span>
                </button>

                <button class="mp-nav-btn" data-mpsec="notifications">
                    <span class="mp-nav-icon"><i class="fas fa-bell"></i></span>
                    <span class="hidden md:inline">Notifications</span>
                    <span id="mpNotifBadge" class="mp-nav-badge hidden">0</span>
                </button>

                <button class="mp-nav-btn" data-mpsec="payroll">
                    <span class="mp-nav-icon"><i class="fas fa-money-bill-wave"></i></span>
                    <span class="hidden md:inline">Payroll</span>
                </button>

                <button class="mp-nav-btn" data-mpsec="annual">
                    <span class="mp-nav-icon"><i class="fas fa-calendar-days"></i></span>
                    <span class="hidden md:inline">Annual Calendar</span>
                </button>

                <button class="mp-nav-btn" data-mpsec="explore">
                    <span class="mp-nav-icon"><i class="fas fa-compass"></i></span>
                    <span class="hidden md:inline">Explore</span>
                </button>

                <?php if ($canManagePerms): ?>
                <div class="hidden md:block border-t border-gray-100 my-2"></div>
                <p class="hidden md:block text-xs font-semibold text-gray-400 uppercase tracking-widest px-2 mb-1">Admin</p>
                <button class="mp-nav-btn" data-mpsec="permissions">
                    <span class="mp-nav-icon"><i class="fas fa-shield-halved"></i></span>
                    <span class="hidden md:inline">Permissions</span>
                </button>
                <?php endif; ?>
            </div>
        </div><!-- /mp-sidenav -->
