<header class="fixed top-0 left-0 right-0 h-16 bg-white shadow-sm z-30 flex items-center justify-between px-4 md:px-6">
    <div class="flex items-center">
        <img src="../assets/images/logo/logo.png" width="40" class="hidden md:block">
        <h1 class="text-lg md:text-xl font-semibold text-gray-800 ml-2 truncate max-w-[150px] md:max-w-none">TravHub Dashboard</h1>
        <button id="sidebarToggle" class="p-2 rounded-lg hover:bg-gray-100 ml-2">
            <i class="fas fa-bars text-gray-600"></i>
        </button>
    </div>

    <div class="flex items-center space-x-2 md:space-x-4">
        <!-- Search - Hidden on mobile -->
        <div class="relative hidden md:block">
            <input type="text" placeholder="Search..." class="pl-10 pr-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent w-40 lg:w-64">
            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
        </div>

        <!-- Mobile search button -->
        <button id="mobileSearchBtn" class="md:hidden p-2 rounded-lg hover:bg-gray-100">
            <i class="fas fa-search text-gray-600"></i>
        </button>

        <!-- Mobile search input -->
        <div id="mobileSearch" class="md:hidden absolute top-16 left-0 right-0 bg-white p-3 shadow-lg hidden">
            <div class="relative">
                <input type="text" placeholder="Search..." class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
            </div>
        </div>

        <div class="relative">
            <button id="notificationBtn" class="p-2 rounded-lg hover:bg-gray-100 relative">
                <i class="fas fa-bell text-gray-600"></i>
                <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
            </button>

            <!-- Notification Panel -->
            <div id="notificationPanel" class="notification-panel absolute right-0 top-12 w-80 bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-40">
                <div class="px-4 py-2 border-b border-gray-200">
                    <h3 class="font-semibold text-gray-800">Notifications</h3>
                </div>
                <div class="max-h-80 overflow-y-auto">
                    <div class="px-4 py-3 hover:bg-gray-50 border-b border-gray-100">
                        <p class="text-sm font-medium">New lead added</p>
                        <p class="text-xs text-gray-500 mt-1">Sarah Johnson submitted a new business visa application</p>
                        <p class="text-xs text-gray-400 mt-1">2 minutes ago</p>
                    </div>
                    <div class="px-4 py-3 hover:bg-gray-50 border-b border-gray-100">
                        <p class="text-sm font-medium">Work status updated</p>
                        <p class="text-xs text-gray-500 mt-1">Michael Chen's work permit is now In Progress</p>
                        <p class="text-xs text-gray-400 mt-1">1 hour ago</p>
                    </div>
                    <div class="px-4 py-3 hover:bg-gray-50">
                        <p class="text-sm font-medium">Document received</p>
                        <p class="text-xs text-gray-500 mt-1">Emma Wilson submitted required passport copy</p>
                        <p class="text-xs text-gray-400 mt-1">3 hours ago</p>
                    </div>
                </div>
                <div class="px-4 py-2 border-t border-gray-200 text-center">
                    <button class="text-sm text-blue-500 hover:text-blue-700">View All Notifications</button>
                </div>
            </div>
        </div>

        <!-- Mobile menu button -->
        <button id="mobileMenuBtn" class="md:hidden p-2 rounded-lg hover:bg-gray-100">
            <i class="fas fa-ellipsis-v text-gray-600"></i>
        </button>

        <!-- Desktop user menu -->
        <div class="relative hidden md:block">
            <button id="userMenuBtn" class="flex items-center space-x-2 p-2 rounded-lg hover:bg-gray-100">
                <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white font-semibold">JD</div>
                <span class="text-gray-700 hidden lg:inline">John Doe</span>
                <i class="fas fa-chevron-down text-gray-500 text-xs"></i>
            </button>

            <!-- User Menu -->
            <div id="userMenu" class="user-menu absolute right-0 top-12 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-40 hidden">
                <a href="#" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-user mr-3 text-gray-400"></i>
                    <span>Profile</span>
                </a>
                <!-- More items... -->
            </div>
        </div>
    </div>
</header>

<!-- Mobile Menu -->
<div id="mobileMenu" class="fixed top-16 right-0 bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-40 w-48 hidden">
    <div class="flex items-center px-4 py-3 border-b border-gray-200">
        <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-semibold">JD</div>
        <div class="ml-3">
            <p class="text-gray-800 font-medium">John Doe</p>
            <p class="text-gray-500 text-sm">Administrator</p>
        </div>
    </div>
    <a href="#" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-100">
        <i class="fas fa-user mr-3 text-gray-400"></i>
        <span>Profile</span>
    </a>
    <!-- More items... -->
</div>