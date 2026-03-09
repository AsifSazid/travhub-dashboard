<?php
// Get current page file name
$currentPage = basename($_SERVER['PHP_SELF']);

?>

<aside id="sidebar" class="fixed top-16 left-0 h-[calc(100vh-4rem)] bg-slate-800 text-white z-20 w-64 flex flex-col transition-all duration-300">
    <nav class="flex-1 overflow-y-auto p-4 ">
        <ul class="space-y-2">

            <li>
                <a href="index.php"
                    class="flex items-center p-3 rounded-lg 
                   <?= $currentPage == 'index.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                    <i class="fas fa-home mr-3"></i>
                    <span class="sidebar-text transition-all duration-300">Dashboard</span>
                </a>
            </li>

            <!-- Stake Holder Accordion -->
            <div class="accordion-item mt-4" data-accordion="stake-holder">
                <button type="button" class="accordion-toggle flex items-center justify-between w-full p-3 text-left rounded-lg text-gray-300 hover:bg-slate-700 transition">
                    <div class="flex items-center">
                        <i class="fas fa-briefcase mr-3"></i>
                        <span class="font-medium sidebar-text transition-all duration-300">Stake Holders</span>
                    </div>
                    <i class="fas fa-chevron-down accordion-arrow transition-transform duration-200 sidebar-text"></i>
                </button>

                <div class="accordion-content ml-7 mt-1 space-y-1 hidden transition-all duration-300">
                    <a href="index-clients.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'index-clients.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fas fa-users mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Clients</span>
                    </a>
                    <a href="index-travelers.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'index-travelers.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fas fa-users mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Traveller's Profile</span>
                    </a>
                    <a href="index-vendors.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'index-vendors.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fas fa-users mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Vendors</span>
                    </a>
                </div>
            </div>
            
            <!-- Working Area Accordion -->
            <div class="accordion-item mt-4" data-accordion="work-areas">
                <button type="button" class="accordion-toggle flex items-center justify-between w-full p-3 text-left rounded-lg text-gray-300 hover:bg-slate-700 transition">
                    <div class="flex items-center">
                        <i class="fas fa-briefcase mr-3"></i>
                        <span class="font-medium sidebar-text transition-all duration-300">Working Area</span>
                    </div>
                    <i class="fas fa-chevron-down accordion-arrow transition-transform duration-200 sidebar-text"></i>
                </button>

                <div class="accordion-content ml-7 mt-1 space-y-1 hidden transition-all duration-300">
                    <a href="generate-leads.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'generate-leads.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fas fa-circle-plus mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Generate Lead</span>
                    </a>
                    <a href="completed-work-entry.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'completed-work-entry.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fas fa-tasks mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Completed Work Entry</span>
                    </a>
                </div>
            </div>
            
            <!-- Finance Accordion -->
            <div class="accordion-item mt-4" data-accordion="finance">
                <button type="button" class="accordion-toggle flex items-center justify-between w-full p-3 text-left rounded-lg text-gray-300 hover:bg-slate-700 transition">
                    <div class="flex items-center">
                        <i class="fas fa-coins mr-3"></i>
                        <span class="font-medium sidebar-text transition-all duration-300">Finance</span>
                    </div>
                    <i class="fas fa-chevron-down accordion-arrow transition-transform duration-200 sidebar-text"></i>
                </button>

                <div class="accordion-content ml-7 mt-1 space-y-1 hidden transition-all duration-300">
                    <a href="accounts.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'accounts.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fa-solid fa-bangladeshi-taka-sign mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Accounting</span>
                    </a>
                    <a href="index-invoice.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'index-invoice.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fa-solid fa-receipt mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Invoice Lists</span>
                    </a>
                    <a href="analytics.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'analytics.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fas fa-chart-bar mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Analytics</span>
                    </a>
                </div>
            </div>
            
            <!-- HR Accordion -->
            <div class="accordion-item mt-4" data-accordion="hr">
                <button type="button" class="accordion-toggle flex items-center justify-between w-full p-3 text-left rounded-lg text-gray-300 hover:bg-slate-700 transition">
                    <div class="flex items-center">
                        <i class="fas fa-user-tie mr-3"></i>
                        <span class="font-medium sidebar-text transition-all duration-300">Human Resource (HR)</span>
                    </div>
                    <i class="fas fa-chevron-down accordion-arrow transition-transform duration-200 sidebar-text"></i>
                </button>

                <div class="accordion-content ml-7 mt-1 space-y-1 hidden transition-all duration-300">
                    <a href="index-employees.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'index-employees.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fas fa-user-tie mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Employees</span>
                    </a>
                    <?php if ($_SESSION['role'] == '0') { ?>
                        <a href="pms.php"
                           class="flex items-center p-3 rounded-lg 
                           <?= $currentPage == 'pms.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                            <i class="fa-solid fa-wallet mr-3"></i>
                            <span class="sidebar-text transition-all duration-300">PMS</span>
                        </a>
                    <?php } ?>
                </div>
            </div>
            
            <!-- Tools Accordion -->
            <div class="accordion-item mt-4" data-accordion="tools">
                <button type="button" class="accordion-toggle flex items-center justify-between w-full p-3 text-left rounded-lg text-gray-300 hover:bg-slate-700 transition">
                    <div class="flex items-center">
                        <i class="fas fa-gears mr-3"></i>
                        <span class="font-medium sidebar-text transition-all duration-300">Tools</span>
                    </div>
                    <i class="fas fa-chevron-down accordion-arrow transition-transform duration-200 sidebar-text"></i>
                </button>

                <div class="accordion-content ml-7 mt-1 space-y-1 hidden transition-all duration-300">
                    <a href="passport-info-extraction.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'passport-info-extraction.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fa-solid fa-passport mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Passport Info Extraction</span>
                    </a>
                    <a href="hotel-info-extraction.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'hotel-info-extraction.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fa-solid fa-hotel mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Hotel Info Extraction</span>
                    </a>
                </div>
            </div>
            
            <!-- Managements Accordion -->
            <div class="accordion-item mt-4" data-accordion="managements">
                <button type="button" class="accordion-toggle flex items-center justify-between w-full p-3 text-left rounded-lg text-gray-300 hover:bg-slate-700 transition">
                    <div class="flex items-center">
                        <i class="fa-solid fa-users-gear mr-3"></i>
                        <span class="font-medium sidebar-text transition-all duration-300">Managements</span>
                    </div>
                    <i class="fas fa-chevron-down accordion-arrow transition-transform duration-200 sidebar-text"></i>
                </button>

                <div class="accordion-content ml-7 mt-1 space-y-1 hidden transition-all duration-300">
                    <a href="index-directors.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'index-directors.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fa-solid fa-chess-knight mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Directors</span>
                    </a>
                    <a href="index-investors.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'index-investors.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fa-solid fa-hand-holding-dollar mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Investors</span>
                    </a>
                </div>
            </div>
            
            <li>
                <a href="settings.php"
                    class="flex items-center p-3 rounded-lg 
                   <?= $currentPage == 'settings.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                    <i class="fas fa-cog mr-3"></i>
                    <span class="sidebar-text transition-all duration-300">Settings</span>
                </a>
            </li>

        </ul>
    </nav>
    
    <!-- User info section at bottom -->
    <div class="border-t border-slate-700 p-4 user-info-section">
        <div class="flex items-center justify-between">
            <div class="flex items-center min-w-0">
                <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-semibold flex-shrink-0">
                    <?php echo $initialName ?>
                </div>
                <div class="ml-3 overflow-hidden user-info-text">
                    <p class="text-white font-medium truncate"><?php echo $_SESSION['user_name'] ?></p>
                    <p class="text-gray-400 text-sm truncate">Managing Director</p>
                </div>
            </div>
            <a href="../auth/logout.php"
                class="flex items-center justify-center w-10 h-10 rounded-lg hover:bg-slate-700 transition-colors flex-shrink-0 logout-icon" title="logout">
                <i class="fa-solid fa-arrow-right-from-bracket text-white text-xl"></i>
            </a>
        </div>
    </div>
</aside>

<style>
    /* Accordion Styles */
    .accordion-item {
        position: relative;
    }

    .accordion-toggle {
        cursor: pointer;
        outline: none;
        user-select: none;
        border: none;
        background: none;
        width: 100%;
    }

    .accordion-arrow {
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        font-size: 0.875rem;
    }

    .accordion-content {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
    }

    .accordion-content a {
        display: flex;
        align-items: center;
        padding: 0.75rem;
        border-radius: 0.5rem;
        color: rgb(209 213 219);
        text-decoration: none;
        transition: all 0.2s;
    }

    .accordion-content a:hover {
        background-color: rgb(51 65 85);
        color: white;
    }

    .accordion-content a.active {
        background-color: rgb(51 65 85);
        color: white;
        font-weight: 500;
    }

    /* Sidebar collapsed styles */
    .sidebar-collapsed {
        width: 4rem !important; /* 64px */
    }

    .sidebar-collapsed .sidebar-text {
        opacity: 0;
        width: 0;
        margin-left: 0;
        overflow: hidden;
        white-space: nowrap;
    }

    .sidebar-collapsed .accordion-toggle {
        justify-content: center;
        padding: 0.75rem;
    }

    .sidebar-collapsed .accordion-toggle i:first-child {
        margin-right: 0 !important;
    }

    .sidebar-collapsed .accordion-arrow {
        display: none;
    }

    .sidebar-collapsed .accordion-content {
        display: none !important;
    }

    /* Hide user info text when collapsed */
    .sidebar-collapsed .user-info-text {
        opacity: 0;
        width: 0;
        overflow: hidden;
    }

    /* Center logout icon when collapsed */
    .sidebar-collapsed .user-info-section {
        padding: 1rem 0.5rem;
    }

    .sidebar-collapsed .user-info-section .flex {
        justify-content: center;
    }

    .sidebar-collapsed .user-info-section .logout-icon {
        margin-left: 0;
    }

    /* Hide user image when collapsed? (optional - remove if you want to keep it) */
    .sidebar-collapsed .user-info-section .w-10.h-10 {
        margin-right: 0;
    }

    /* Transition for smooth animation */
    #sidebar,
    .sidebar-text,
    .user-info-text,
    .accordion-toggle i,
    .user-info-section {
        transition: all 0.3s ease;
    }

    /* Remove margin from icons when collapsed */
    .sidebar-collapsed a i,
    .sidebar-collapsed .accordion-toggle i {
        margin-right: 0 !important;
    }

    /* Center icons in regular links when collapsed */
    .sidebar-collapsed a {
        justify-content: center;
        padding: 0.75rem;
    }

    .sidebar-collapsed a i {
        margin-right: 0;
    }
</style>